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

        // Streaming MP3 playback state
        this._nextPlaybackTime = 0;
        this._pendingMp3       = null;
        this._muted            = false;
        this._gainNode         = null;
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
                if (this._nextPlaybackTime < now) {
                    this._nextPlaybackTime = now;
                }
                source.start(this._nextPlaybackTime);
                this._nextPlaybackTime += audioBuffer.duration;
            } catch (decErr) {
                // Not enough data for a complete MP3 frame; accumulate for next chunk.
                // Discard if over 64KB to avoid unbounded growth on wrong-format data.
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

    // --- Send microphone ---
    async startMicrophone(inputDeviceId) {
        if (this.micActive) return;
        this._inputDeviceId = inputDeviceId || null;

        const constraints = {
            audio: {
                deviceId: inputDeviceId ? { exact: inputDeviceId } : undefined,
                channelCount: this._qualityPresets[this._quality].channelCount,
                sampleRate: this._qualityPresets[this._quality].sampleRate,
                echoCancellation: true,
                noiseSuppression: true,
                autoGainControl: true,
            }
        };

        try {
            this.localStream = await navigator.mediaDevices.getUserMedia(constraints);
            this.micActive = true;

            // Connect mic stream to a dedicated analyser for VU meter
            if (this.audioCtx) {
                this._micSource   = this.audioCtx.createMediaStreamSource(this.localStream);
                this._micAnalyser = this.audioCtx.createAnalyser();
                this._micAnalyser.fftSize = 256;
                this._micSource.connect(this._micAnalyser);
            }

            // Start MediaRecorder to send audio as base64 JSON chunks
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
                this._mediaRecorder.start(100); // 100 ms chunks
            } catch(recErr) {
                console.warn('[AudioManager] MediaRecorder init error:', recErr);
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

    stopMicrophone() {
        if (!this.micActive) return;
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
