/**
 * AirDirector Client - Countdown UI
 */
class Countdown {
    constructor() {
        this._schedSec  = null;
        this._adSec     = null;
        this._schedName = '';
        this._timer     = null;
        this._lastUpdate = null;
        this._init();
    }

    _init() {
        this._timer = setInterval(() => this._tick(), 1000);
    }

    update(data) {
        this._schedSec  = data.next_schedule !== undefined ? data.next_schedule : this._schedSec;
        this._adSec     = data.next_ad       !== undefined ? data.next_ad       : this._adSec;
        this._schedName = data.schedule_name || '';
        this._lastUpdate = Date.now();
        this._render();
    }

    _tick() {
        if (this._schedSec !== null && this._schedSec > 0) this._schedSec--;
        if (this._adSec    !== null && this._adSec    > 0) this._adSec--;
        this._render();
    }

    _render() {
        const schedEl = document.getElementById('countdownSchedule');
        const adEl    = document.getElementById('countdownAd');
        const nameEl  = document.getElementById('scheduleName');

        if (schedEl) {
            schedEl.textContent = this._schedSec !== null ? this._fmt(this._schedSec) : '--:--:--';
            schedEl.classList.toggle('urgent', this._schedSec !== null && this._schedSec <= 30 && this._schedSec > 0);
        }
        if (adEl) {
            adEl.textContent = this._adSec !== null ? this._fmt(this._adSec) : '--:--:--';
            adEl.classList.toggle('urgent', this._adSec !== null && this._adSec <= 30 && this._adSec > 0);
        }
        if (nameEl) nameEl.textContent = this._schedName;
    }

    _fmt(sec) {
        sec = Math.max(0, Math.floor(sec || 0));
        const h = Math.floor(sec / 3600);
        const m = Math.floor((sec % 3600) / 60);
        const s = sec % 60;
        if (h > 0) return `${h}:${m.toString().padStart(2,'0')}:${s.toString().padStart(2,'0')}`;
        return `${m.toString().padStart(2,'0')}:${s.toString().padStart(2,'0')}`;
    }

    destroy() {
        if (this._timer) clearInterval(this._timer);
    }
}

window.Countdown = Countdown;
