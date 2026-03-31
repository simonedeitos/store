/**
 * AirDirector Client - Audio Monitor & Mic Control
 */
class AudioMonitor {
    constructor(audioManager, ws) {
        this.audioManager = audioManager;
        this.ws           = ws;
        this._rafId       = null;
        this._micActive   = false;
        this._init();
    }

    async _init() {
        await this._populateDevices();

        document.getElementById('btnSendMic')?.addEventListener('click', () => this._toggleMic());

        document.getElementById('btnStartSkip')?.addEventListener('click', () => {
            this._deactivateMic();
            if (this.ws) this.ws.sendCommand('skip');
        });

        document.getElementById('btnMuteAudio')?.addEventListener('click', () => this._toggleMute());

        document.querySelectorAll('.quality-option').forEach(el => {
            el.addEventListener('click', (e) => {
                e.preventDefault();
                const quality = el.dataset.quality;
                if (this.audioManager) this.audioManager.setQuality(quality);
                document.querySelectorAll('.quality-option').forEach(q => q.classList.remove('active'));
                el.classList.add('active');
            });
        });

        document.getElementById('audioOutputSelect')?.addEventListener('change', (e) => {
            if (this.audioManager) this.audioManager._outputDeviceId = e.target.value || null;
        });

        // Init analyser
        if (this.audioManager) {
            try { this.audioManager.initAnalyser(); } catch(e) {}

            // Resume AudioContext on any user interaction (browser autoplay policy).
            // Use persistent listeners so new AudioContext instances created later
            // (e.g. when the first audio_data arrives) are also resumed.
            const resumeAudio = () => {
                this.audioManager.resumeAudioContext();
            };
            ['click', 'touchstart', 'keydown'].forEach(evt => {
                document.body.addEventListener(evt, resumeAudio, { capture: true, passive: true });
            });
        }

        this._startMeter();
    }

    async _populateDevices() {
        const devices = await AudioManager.getAudioDevices();
        const inputSel  = document.getElementById('audioInputSelect');
        const outputSel = document.getElementById('audioOutputSelect');

        devices.inputs.forEach(d => {
            const opt = document.createElement('option');
            opt.value = d.deviceId;
            opt.textContent = d.label || `Input ${d.deviceId.substr(0, 8)}`;
            inputSel?.appendChild(opt);
        });

        devices.outputs.forEach(d => {
            const opt = document.createElement('option');
            opt.value = d.deviceId;
            opt.textContent = d.label || `Output ${d.deviceId.substr(0, 8)}`;
            outputSel?.appendChild(opt);
        });
    }

    async _toggleMic() {
        if (this._micActive) {
            this._deactivateMic();
        } else {
            try {
                const inputDeviceId = document.getElementById('audioInputSelect')?.value || null;
                await this.audioManager?.startMicrophone(inputDeviceId);
                this._micActive = true;
                const btn = document.getElementById('btnSendMic');
                btn?.classList.add('active');
                btn && (btn.querySelector('[data-lang]') || btn).setAttribute('data-lang', 'audio.stop_mic');
                this._showMicUI();
                if (window.LanguageManager) window.LanguageManager.apply();
            } catch(e) {
                alert(window.LanguageManager ? window.LanguageManager.get('audio.mic_error', 'Impossibile accedere al microfono.') : 'Mic error');
            }
        }
    }

    _deactivateMic() {
        this.audioManager?.stopMicrophone();
        this._micActive = false;
        const btn = document.getElementById('btnSendMic');
        btn?.classList.remove('active');
        btn && (btn.querySelector('[data-lang]') || btn).setAttribute('data-lang', 'audio.send_mic');
        this._hideMicUI();
        if (window.LanguageManager) window.LanguageManager.apply();
    }

    _toggleMute() {
        if (!this.audioManager) return;
        const muted = !this.audioManager.isMuted;
        this.audioManager.setMuted(muted);
        const btn = document.getElementById('btnMuteAudio');
        const icon = document.getElementById('muteIcon');
        if (btn) btn.classList.toggle('muted', muted);
        if (icon) {
            icon.className = muted ? 'bi bi-volume-mute-fill me-1' : 'bi bi-volume-up-fill me-1';
        }
    }

    _showMicUI() {
        const meter = document.getElementById('micVuMeter');
        const skipBtn = document.getElementById('btnStartSkip');
        if (meter)   meter.style.display   = 'flex';
        if (skipBtn) skipBtn.style.display = 'inline-flex';
    }

    _hideMicUI() {
        const meter = document.getElementById('micVuMeter');
        const skipBtn = document.getElementById('btnStartSkip');
        if (meter)   meter.style.display   = 'none';
        if (skipBtn) skipBtn.style.display = 'none';
        const fill = document.getElementById('micVuFill');
        if (fill) fill.style.width = '0%';
    }

    _startMeter() {
        const fillL   = document.getElementById('audioMeterFillL');
        const fillR   = document.getElementById('audioMeterFillR');
        const micFill = document.getElementById('micVuFill');

        const tick = () => {
            if (this.audioManager) {
                const { l, r } = this.audioManager.getLevel();
                if (fillL) fillL.style.width = l + '%';
                if (fillR) fillR.style.width = r + '%';

                if (this._micActive) {
                    const micLevel = this.audioManager.getMicLevel();
                    if (micFill) micFill.style.width = micLevel + '%';
                } else {
                    if (micFill) micFill.style.width = '0%';
                }
            }
            this._rafId = requestAnimationFrame(tick);
        };
        tick();
    }

    updateConnectedUsers(users) {
        const container = document.getElementById('connectedUsersList');
        if (!container) return;

        if (!users || users.length === 0) {
            container.innerHTML = `<div class="text-muted small ps-2">${LanguageManager.get('users.no_users', 'Nessun utente')}</div>`;
            return;
        }

        container.innerHTML = users.map(u => `
            <div class="connected-user-item">
                <span class="user-dot"></span>
                <span class="user-name">${u.name ? u.name.replace(/</g,'&lt;') : 'User'}</span>
                ${u.mic_active ? '<span class="mic-indicator"><i class="bi bi-mic-fill"></i></span>' : ''}
            </div>
        `).join('');
    }

    destroy() {
        if (this._rafId) cancelAnimationFrame(this._rafId);
    }
}

window.AudioMonitor = AudioMonitor;
