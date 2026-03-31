/**
 * AirDirector Client - AudioManager (WebRTC)
 */
class AudioManager {
    constructor() {
        this.peerConnections = new Map();  // peerId -> RTCPeerConnection
        this.localStream     = null;       // mic stream
        this.audioCtx        = null;
        this.analyser        = null;
        this.micActive       = false;
        this._ws             = null;       // reference to AirDirectorWS instance
        this._quality        = 'medium';
        this._outputDeviceId = null;
        this._inputDeviceId  = null;

        this._qualityPresets = {
            low:    { audioBitsPerSecond: 32000,  channelCount: 1, sampleRate: 22050 },
            medium: { audioBitsPerSecond: 128000, channelCount: 2, sampleRate: 44100 },
            high:   { audioBitsPerSecond: 256000, channelCount: 2, sampleRate: 48000 },
            studio: { audioBitsPerSecond: 320000, channelCount: 2, sampleRate: 48000 },
        };

        this._iceServers = [
            { urls: 'stun:stun.l.google.com:19302' },
            { urls: 'stun:stun1.l.google.com:19302' },
        ];

        this._micAnalyser = null;
        this._micSource   = null;
        this._mediaRecorder = null;

        // MP3 encoding state (lamejs)
        this._mp3Encoder      = null;
        this._mp3Chunks       = [];
        this._mp3ChunksSize   = 0;
        this._scriptProcessor = null;
        this._mp3SilentGain   = null;
        this._mp3FlushTimer   = null;

        // Streaming MP3 playback state
        this._nextPlaybackTime = 0;
        this._pendingMp3       = null;
        this._muted            = false;
        this._gainNode         = null;
        this._decodeErrCount   = 0;
    }

    setWS(ws) { this._ws = ws; }
    setQuality(preset) { this._quality = this._qualityPresets[preset] ? preset : 'medium'; }

    // --- Mute/unmute received audio from AirDirector ---
    setMuted(muted) {
        this._muted = !!muted;
        if (this._gainNode) {
            this._gainNode.gain.value = this._muted ? 0 : 1;
        }
    }

    get isMuted() { return this._muted; }

    // --- Receive streaming MP3 audio from AirDirector (base64 JSON protocol) ---
    async receiveAudioData(base64) {
        if (!base64) return;
        try {
            if (!this.audioCtx) {
                this._initAudioChain();
                this._nextPlaybackTime = 0;
                this._pendingMp3 = null;
            }
            // Resume context if suspended (browser autoplay policy)
            if (this.audioCtx.state === 'suspended') {
                await this.audioCtx.resume();
            }

            // Decode base64 → binary
            const binary = atob(base64);
            const incoming = new Uint8Array(binary.length);
            for (let i = 0; i < binary.length; i++) {
                incoming[i] = binary.charCodeAt(i);
            }

            // Prepend any pending data from a previous failed decode (partial MP3 frame)
            let data;
            if (this._pendingMp3 && this._pendingMp3.length > 0) {
                data = new Uint8Array(this._pendingMp3.length + incoming.length);
                data.set(this._pendingMp3, 0);
                data.set(incoming, this._pendingMp3.length);
                this._pendingMp3 = null;
            } else {
                data = incoming;
            }

            try {
                // decodeAudioData detaches the ArrayBuffer, so pass a copy
                const audioBuffer = await this.audioCtx.decodeAudioData(data.buffer.slice(0));
                this._decodeErrCount = 0;
                const source = this.audioCtx.createBufferSource();
                source.buffer = audioBuffer;
                // Route: source → analyser → gainNode → destination
                if (this.analyser) {
                    source.connect(this.analyser);
                } else {
                    source.connect(this._gainNode);
                }

                // Schedule playback at the correct time to avoid gaps and overlaps
                const now = this.audioCtx.currentTime;
                const maxLatency = 0.5; // Cap buffered-ahead time to 0.5 seconds for low-latency
                if (this._nextPlaybackTime < now) {
                    this._nextPlaybackTime = now;
                } else if (this._nextPlaybackTime > now + maxLatency) {
                    // Too far ahead — reset to reduce latency buildup
                    this._nextPlaybackTime = now + 0.05;
                }
                source.start(this._nextPlaybackTime);
                this._nextPlaybackTime += audioBuffer.duration;
            } catch (decErr) {
                // Not enough data for a complete MP3 frame; accumulate for next chunk.
                // Discard if over 64KB to avoid unbounded growth on wrong-format data.
                this._decodeErrCount++;
                if (this._decodeErrCount % 10 === 1) {
                    console.warn(`[AudioManager] decodeAudioData failed (attempt ${this._decodeErrCount}):`, decErr.message || decErr);
                }
                const maxPendingSize = 64 * 1024;
                if (data.length < maxPendingSize) {
                    this._pendingMp3 = data;
                }
            }
        } catch(e) {
            console.error('[AudioManager] receiveAudioData error:', e);
        }
    }

    // --- Receive audio from AirDirector (WebRTC legacy) ---
    async initReceiveAudio(outputDeviceId) {
        this._outputDeviceId = outputDeviceId || null;
    }

    async createReceivePeer(peerId) {
        const pc = new RTCPeerConnection({ iceServers: this._iceServers });
        this.peerConnections.set(peerId, pc);

        pc.ontrack = (event) => {
            const audio = new Audio();
            if (this._outputDeviceId && audio.setSinkId) {
                audio.setSinkId(this._outputDeviceId).catch(() => {});
            }
            audio.srcObject = event.streams[0];
            audio.play().catch(() => {});

            // Connect to analyser for level meter
            if (this.audioCtx && this.analyser) {
                const src = this.audioCtx.createMediaStreamSource(event.streams[0]);
                src.connect(this.analyser);
            }
        };

        pc.onicecandidate = (event) => {
            if (event.candidate && this._ws) {
                this._ws.send({
                    type: 'webrtc-ice',
                    peerId,
                    candidate: event.candidate
                });
            }
        };

        return pc;
    }

    // --- Handle WebRTC signaling ---
    async handleOffer(peerId, offer) {
        const pc = await this.createReceivePeer(peerId);
        await pc.setRemoteDescription(new RTCSessionDescription(offer));
        const answer = await pc.createAnswer();
        await pc.setLocalDescription(answer);
        if (this._ws) {
            this._ws.send({ type: 'webrtc-answer', peerId, answer });
        }
    }

    async handleAnswer(peerId, answer) {
        const pc = this.peerConnections.get(peerId);
        if (pc) await pc.setRemoteDescription(new RTCSessionDescription(answer));
    }

    async handleIceCandidate(peerId, candidate) {
        const pc = this.peerConnections.get(peerId);
        if (pc) await pc.addIceCandidate(new RTCIceCandidate(candidate));
    }

    // --- Send microphone (MP3 encoding via lamejs, fallback to MediaRecorder) ---
    async startMicrophone(inputDeviceId) {
        if (this.micActive) return;
        this._inputDeviceId = inputDeviceId || null;

        const constraints = {
            audio: {
                deviceId: inputDeviceId ? { exact: inputDeviceId } : undefined,
                channelCount: 1, // mono for mic transmission
                sampleRate: 44100,
                echoCancellation: true,
                noiseSuppression: true,
                autoGainControl: true,
            }
        };

        try {
            this.localStream = await navigator.mediaDevices.getUserMedia(constraints);
            this.micActive = true;

            // Ensure AudioContext exists
            if (!this.audioCtx) {
                this._initAudioChain();
            }
            if (this.audioCtx.state === 'suspended') {
                await this.audioCtx.resume();
            }

            // Connect mic stream to a dedicated analyser for VU meter
            this._micSource   = this.audioCtx.createMediaStreamSource(this.localStream);
            this._micAnalyser = this.audioCtx.createAnalyser();
            this._micAnalyser.fftSize = 256;
            this._micSource.connect(this._micAnalyser);

            // Prefer MediaRecorder with Opus (low-latency, like Cleanfeed) over lamejs MP3.
            // lamejs is used as fallback when MediaRecorder/Opus is unavailable.
            if (typeof MediaRecorder !== 'undefined' && MediaRecorder.isTypeSupported('audio/webm;codecs=opus')) {
                this._startMediaRecorderOpus();
            } else if (typeof lamejs !== 'undefined') {
                console.warn('[AudioManager] Opus MediaRecorder unavailable, falling back to lamejs MP3');
                this._startMp3Encoding();
            } else {
                console.warn('[AudioManager] Both Opus MediaRecorder and lamejs unavailable — using generic MediaRecorder fallback');
                this._startMediaRecorderFallback();
            }

            // Add track to all existing peer connections (WebRTC legacy)
            this.peerConnections.forEach((pc) => {
                this.localStream.getAudioTracks().forEach(track => {
                    pc.addTrack(track, this.localStream);
                });
            });

            if (this._ws) {
                this._ws.send({ type: 'mic-status', active: true });
            }
        } catch(e) {
            console.error('[AudioManager] Mic error:', e);
            throw e;
        }
    }

    /**
     * Start MP3 encoding pipeline: ScriptProcessorNode captures raw PCM,
     * lamejs encodes it to MP3, and chunks are sent over WebSocket.
     */
    _startMp3Encoding() {
        const sampleRate = this.audioCtx.sampleRate;
        const channels = 1;
        const kbps = 128;

        try {
            this._mp3Encoder = new lamejs.Mp3Encoder(channels, sampleRate, kbps);
        } catch (e) {
            console.warn('[AudioManager] lamejs Mp3Encoder init failed, falling back to MediaRecorder:', e);
            this._startMediaRecorderFallback();
            return;
        }

        this._mp3Chunks     = [];
        this._mp3ChunksSize = 0;

        // 2 KB threshold — lower latency; one 128kbps/44100Hz MP3 frame ≈ 418 bytes, so 2 KB ≈ 4-5 frames
        const MP3_SEND_THRESHOLD = 2048;

        const bufferSize = 4096;
        this._scriptProcessor = this.audioCtx.createScriptProcessor(bufferSize, 1, 1);

        this._scriptProcessor.onaudioprocess = (e) => {
            if (!this._mp3Encoder || !this._ws) return;

            const input = e.inputBuffer.getChannelData(0);
            const samples = new Int16Array(input.length);
            for (let i = 0; i < input.length; i++) {
                const s = Math.max(-1, Math.min(1, input[i]));
                samples[i] = s < 0 ? s * 0x8000 : s * 0x7FFF;
            }

            const mp3buf = this._mp3Encoder.encodeBuffer(samples);
            if (mp3buf.length > 0) {
                this._mp3Chunks.push(new Uint8Array(mp3buf));
                this._mp3ChunksSize += mp3buf.length;
            }

            if (this._mp3ChunksSize >= MP3_SEND_THRESHOLD) {
                this._flushMp3Chunks();
            }
        };

        // ScriptProcessorNode must be connected to destination to process audio.
        // Route through a silent gain node so mic audio doesn't play back to speakers.
        this._micSource.connect(this._scriptProcessor);
        this._mp3SilentGain = this.audioCtx.createGain();
        this._mp3SilentGain.gain.value = 0;
        this._scriptProcessor.connect(this._mp3SilentGain);
        this._mp3SilentGain.connect(this.audioCtx.destination);

        // Periodic flush to limit latency (send partial data every 100 ms)
        this._mp3FlushTimer = setInterval(() => {
            if (this._mp3ChunksSize > 0) {
                this._flushMp3Chunks();
            }
        }, 100);

        console.log(`[AudioManager] MP3 mic encoding started (${sampleRate}Hz, ${kbps}kbps mono)`);
    }

    /**
     * Concatenate accumulated MP3 frame data and send as base64 over WebSocket.
     */
    _flushMp3Chunks() {
        if (!this._mp3Chunks || this._mp3ChunksSize === 0) return;

        const combined = new Uint8Array(this._mp3ChunksSize);
        let offset = 0;
        for (const chunk of this._mp3Chunks) {
            combined.set(chunk, offset);
            offset += chunk.length;
        }
        this._mp3Chunks     = [];
        this._mp3ChunksSize = 0;

        // Convert to base64 — build string incrementally to avoid call-stack limits
        let binary = '';
        for (let i = 0; i < combined.length; i++) {
            binary += String.fromCharCode(combined[i]);
        }
        const base64 = btoa(binary);

        if (this._ws) {
            this._ws.send({ type: 'audio_data', direction: 'to_ad', data: base64 });
        }
    }

    /**
     * Primary: use MediaRecorder with WebM/Opus at 20ms chunks for low-latency transmission.
     */
    _startMediaRecorderOpus() {
        try {
            this._mediaRecorder = new MediaRecorder(this.localStream, { mimeType: 'audio/webm;codecs=opus' });
            this._mediaRecorder.ondataavailable = (e) => {
                if (e.data && e.data.size > 0 && this._ws) {
                    const reader = new FileReader();
                    reader.onload = () => {
                        const base64 = reader.result.split(',')[1];
                        if (base64) this._ws.send({ type: 'audio_data', direction: 'to_ad', data: base64 });
                    };
                    reader.readAsDataURL(e.data);
                }
            };
            this._mediaRecorder.start(20); // 20ms chunks — Opus low-latency (like Cleanfeed)
            console.log('[AudioManager] MediaRecorder Opus started (20ms chunks)');
        } catch (recErr) {
            console.warn('[AudioManager] MediaRecorder Opus init error, falling back to lamejs:', recErr);
            if (typeof lamejs !== 'undefined') {
                this._startMp3Encoding();
            } else {
                this._startMediaRecorderFallback();
            }
        }
    }

    /**
     * Fallback: use MediaRecorder with best available codec when Opus is not supported.
     */
    _startMediaRecorderFallback() {
        const mimeType = MediaRecorder.isTypeSupported('audio/webm;codecs=opus')
            ? 'audio/webm;codecs=opus'
            : (MediaRecorder.isTypeSupported('audio/webm') ? 'audio/webm' : '');
        const recorderOpts = mimeType ? { mimeType } : {};
        try {
            this._mediaRecorder = new MediaRecorder(this.localStream, recorderOpts);
            this._mediaRecorder.ondataavailable = (e) => {
                if (e.data && e.data.size > 0 && this._ws) {
                    const reader = new FileReader();
                    reader.onload = () => {
                        const base64 = reader.result.split(',')[1];
                        if (base64) this._ws.send({ type: 'audio_data', direction: 'to_ad', data: base64 });
                    };
                    reader.readAsDataURL(e.data);
                }
            };
            this._mediaRecorder.start(20); // 20ms chunks for lower latency
            console.log('[AudioManager] MediaRecorder fallback started (WebM/Opus)');
        } catch(recErr) {
            console.warn('[AudioManager] MediaRecorder init error:', recErr);
        }
    }

    stopMicrophone() {
        if (!this.micActive) return;

        // Flush and send any remaining MP3 data
        if (this._mp3Encoder) {
            try {
                const remaining = this._mp3Encoder.flush();
                if (remaining.length > 0) {
                    this._mp3Chunks.push(new Uint8Array(remaining));
                    this._mp3ChunksSize += remaining.length;
                }
                this._flushMp3Chunks();
            } catch (e) { /* ignore flush errors on stop */ }
            this._mp3Encoder    = null;
            this._mp3Chunks     = [];
            this._mp3ChunksSize = 0;
        }

        if (this._mp3FlushTimer) {
            clearInterval(this._mp3FlushTimer);
            this._mp3FlushTimer = null;
        }

        if (this._scriptProcessor) {
            this._scriptProcessor.disconnect();
            this._scriptProcessor.onaudioprocess = null;
            this._scriptProcessor = null;
        }

        if (this._mp3SilentGain) {
            this._mp3SilentGain.disconnect();
            this._mp3SilentGain = null;
        }

        // MediaRecorder fallback cleanup
        if (this._mediaRecorder && this._mediaRecorder.state !== 'inactive') {
            this._mediaRecorder.stop();
        }
        this._mediaRecorder = null;

        if (this.localStream) {
            this.localStream.getTracks().forEach(t => t.stop());
            this.localStream = null;
        }
        if (this._micSource)   { this._micSource.disconnect();   this._micSource   = null; }
        if (this._micAnalyser) { this._micAnalyser.disconnect(); this._micAnalyser = null; }
        this.micActive = false;
        if (this._ws) {
            this._ws.send({ type: 'mic-status', active: false });
        }
    }

    // --- Resume AudioContext (must be called from a user gesture) ---
    resumeAudioContext() {
        if (this.audioCtx && this.audioCtx.state === 'suspended') {
            // Resume failure is non-fatal; audio will retry on the next user interaction
            this.audioCtx.resume().catch(() => {});
        }
    }

    // --- Audio chain setup (shared between initAnalyser and receiveAudioData) ---
    _initAudioChain() {
        this.audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        this.analyser = this.audioCtx.createAnalyser();
        this.analyser.fftSize = 256;
        this._gainNode = this.audioCtx.createGain();
        this._gainNode.gain.value = this._muted ? 0 : 1;
        this.analyser.connect(this._gainNode);
        this._gainNode.connect(this.audioCtx.destination);
    }

    // --- Audio level analyser ---
    initAnalyser() {
        this._initAudioChain();
    }

    getLevel() {
        if (!this.analyser) return { l: 0, r: 0 };
        const buf = new Uint8Array(this.analyser.frequencyBinCount);
        this.analyser.getByteFrequencyData(buf);
        const avg = buf.reduce((a, b) => a + b, 0) / buf.length;
        const level = Math.min(100, (avg / 128) * 100);
        return { l: level, r: level * 0.95 };
    }

    getMicLevel() {
        if (!this._micAnalyser) return 0;
        const buf = new Uint8Array(this._micAnalyser.frequencyBinCount);
        this._micAnalyser.getByteFrequencyData(buf);
        const avg = buf.reduce((a, b) => a + b, 0) / buf.length;
        return Math.min(100, (avg / 128) * 100);
    }

    // --- Enumerate devices ---
    static async getAudioDevices() {
        try {
            await navigator.mediaDevices.getUserMedia({ audio: true });
            const devices = await navigator.mediaDevices.enumerateDevices();
            return {
                inputs:  devices.filter(d => d.kind === 'audioinput'),
                outputs: devices.filter(d => d.kind === 'audiooutput'),
            };
        } catch(e) {
            return { inputs: [], outputs: [] };
        }
    }

    dispose() {
        this.stopMicrophone();
        this.peerConnections.forEach(pc => pc.close());
        this.peerConnections.clear();
        if (this.audioCtx) this.audioCtx.close();
    }
}

window.AudioManager = AudioManager;
