/**
 * AirDirector Client - Player Control UI
 */
class PlayerControl {
    constructor(ws) {
        this.ws       = ws;
        this._status  = 'stopped';
        this._track   = null;
        this._position= 0;
        this._duration= 0;
        this._mode    = 'auto';
        this._introEnd= 0;
        this._timer   = null;

        this._INTRO_URGENT_SECS = 5;

        this._init();
    }

    _init() {
        document.getElementById('btnPlay')?.addEventListener('click', () => this._command('play'));
        document.getElementById('btnStop')?.addEventListener('click', () => this._command('stop'));
        document.getElementById('btnPause')?.addEventListener('click', () => this._command('pause'));
        document.getElementById('btnSkip')?.addEventListener('click', () => this._command('skip'));
        document.getElementById('btnModeAuto')?.addEventListener('click', () => this._setMode('auto'));
        document.getElementById('btnModeManual')?.addEventListener('click', () => this._setMode('manual'));
    }

    _command(action) {
        if (this.ws) this.ws.sendCommand(action);
    }

    _setMode(mode) {
        this._mode = mode;
        if (this.ws) this.ws.sendCommand('set_mode', { mode });
        this._updateModeUI();
    }

    _updateModeUI() {
        const badge = document.getElementById('modeBadge');
        const btnAuto   = document.getElementById('btnModeAuto');
        const btnManual = document.getElementById('btnModeManual');
        if (!badge) return;
        if (this._mode === 'auto') {
            badge.textContent = LanguageManager.get('player.mode_auto', 'AUTO');
            badge.style.background = 'var(--accent-success)';
            btnAuto?.classList.replace('btn-outline-secondary', 'btn-outline-primary');
            btnManual?.classList.replace('btn-outline-primary', 'btn-outline-secondary');
        } else {
            badge.textContent = LanguageManager.get('player.mode_manual', 'MANUALE');
            badge.style.background = 'var(--accent-warning)';
            btnManual?.classList.replace('btn-outline-secondary', 'btn-outline-primary');
            btnAuto?.classList.replace('btn-outline-primary', 'btn-outline-secondary');
        }
    }

    update(data) {
        this._status   = data.status   || 'stopped';
        this._position = data.position || 0;
        this._duration = data.duration || 0;
        if (data.track     !== undefined) this._track    = data.track;
        if (data.mode      !== undefined) { this._mode = data.mode; this._updateModeUI(); }
        if (data.intro_end !== undefined) this._introEnd = data.intro_end || 0;

        // Update UI
        const titleEl   = document.getElementById('nowPlayingTitle');
        const artistEl  = document.getElementById('nowPlayingArtist');
        const statusBadge = document.getElementById('playerStatusBadge');
        const fill      = document.getElementById('progressFill');
        const timeEl    = document.getElementById('progressTime');
        const durEl     = document.getElementById('progressDuration');

        if (titleEl)  titleEl.textContent  = this._track || LanguageManager.get('player.no_track', 'Nessun brano');
        if (artistEl) artistEl.textContent = data.artist || '';

        const pct = this._duration > 0 ? (this._position / this._duration) * 100 : 0;
        if (fill) fill.style.width = pct + '%';
        if (timeEl) timeEl.textContent  = this._formatTime(this._position);
        if (durEl)  durEl.textContent   = this._formatTime(this._duration);

        const statusColors = { playing: 'bg-success', paused: 'bg-warning', stopped: 'bg-secondary' };
        if (statusBadge) {
            statusBadge.innerHTML = `<span class="badge ${statusColors[this._status] || 'bg-secondary'}">${this._status}</span>`;
        }

        this._updateIntroCountdown();

        // Start/stop local progress timer
        if (this._status === 'playing') {
            if (!this._timer) {
                this._timer = setInterval(() => {
                    this._position = Math.min(this._position + 1, this._duration);
                    const p2 = this._duration > 0 ? (this._position / this._duration) * 100 : 0;
                    if (fill) fill.style.width = p2 + '%';
                    if (timeEl) timeEl.textContent = this._formatTime(this._position);
                    this._updateIntroCountdown();
                }, 1000);
            }
        } else {
            clearInterval(this._timer);
            this._timer = null;
        }
    }

    _updateIntroCountdown() {
        const el = document.getElementById('introCountdown');
        if (!el) return;
        if (!this._introEnd || this._introEnd <= 0 || this._status === 'stopped') {
            el.style.display = 'none';
            return;
        }
        const remaining = Math.max(0, this._introEnd - this._position);
        el.style.display = 'inline-flex';
        el.textContent   = `INTRO: ${this._formatTime(remaining)}`;
        el.classList.toggle('urgent', remaining > 0 && remaining <= this._INTRO_URGENT_SECS);
    }

    _formatTime(sec) {
        sec = Math.floor(sec || 0);
        const m = Math.floor(sec / 60);
        const s = sec % 60;
        return `${m}:${s.toString().padStart(2, '0')}`;
    }
}

window.PlayerControl = PlayerControl;
