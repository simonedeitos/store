/**
 * AirDirector Client - Archive Control UI (Music + Clips tabs)
 */
class ArchiveControl {
    constructor(ws) {
        this.ws      = ws;
        this._music  = [];
        this._clips  = [];
        this._tab    = 'music';
        this._search = '';
        this._init();
    }

    _init() {
        document.getElementById('tabMusic')?.addEventListener('click', () => this._switchTab('music'));
        document.getElementById('tabClips')?.addEventListener('click', () => this._switchTab('clips'));

        const searchInput = document.getElementById('archiveSearch');
        if (searchInput) {
            searchInput.addEventListener('input', () => {
                this._search = searchInput.value.toLowerCase();
                this._render();
            });
        }
    }

    _switchTab(tab) {
        this._tab = tab;
        document.getElementById('tabMusic')?.classList.toggle('active', tab === 'music');
        document.getElementById('tabClips')?.classList.toggle('active', tab === 'clips');
        this._render();
    }

    update(data) {
        if (data && data.music !== undefined) this._music = data.music || [];
        if (data && data.clips !== undefined) this._clips = data.clips || [];
        this._render();
    }

    updateMusic(tracks) {
        this._music = tracks || [];
        if (this._tab === 'music') this._render();
    }

    updateClips(tracks) {
        this._clips = tracks || [];
        if (this._tab === 'clips') this._render();
    }

    _render() {
        const container = document.getElementById('archiveList');
        if (!container) return;
        const items = (this._tab === 'music' ? this._music : this._clips)
            .filter(i => !this._search || (i.title||'').toLowerCase().includes(this._search) || (i.artist||'').toLowerCase().includes(this._search));

        if (items.length === 0) {
            container.innerHTML = `<div class="text-center text-muted py-3">${LanguageManager.get('archive.empty', 'Nessun elemento')}</div>`;
            return;
        }

        const icon     = this._tab === 'music' ? 'bi-music-note' : 'bi-mic';
        const itemType = this._tab === 'music' ? 'music' : 'clip';
        container.innerHTML = items.map(item => `
            <div class="archive-item" draggable="true" data-track-id="${item.trackId}" data-item-type="${itemType}">
                <i class="archive-item-icon bi ${icon}"></i>
                <div class="archive-item-info">
                    <div class="archive-item-title">${this._esc(item.title)}</div>
                    ${item.artist ? `<div class="archive-item-artist">${this._esc(item.artist)}</div>` : ''}
                </div>
                <span class="archive-item-duration">${this._fmt(item.duration)}</span>
                <button class="archive-item-add" data-track-id="${item.trackId}" data-item-type="${itemType}" title="${LanguageManager.get('archive.add_to_queue','Aggiungi alla coda')}">
                    <i class="bi bi-plus"></i>
                </button>
            </div>
        `).join('');

        // "+" button to add to end of queue
        container.querySelectorAll('.archive-item-add').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const trackId  = parseInt(btn.dataset.trackId, 10); // trackId is a numeric int, not a Guid
                const type     = btn.dataset.itemType;
                if (this.ws) this.ws.sendCommand('queue_add', { type, trackId });
            });
        });

        // Drag events for drag-to-queue
        container.querySelectorAll('.archive-item').forEach(el => {
            el.addEventListener('dragstart', (e) => {
                el.classList.add('dragging');
                e.dataTransfer.setData('text/plain', el.dataset.trackId);
                e.dataTransfer.setData('item-type', el.dataset.itemType);
                e.dataTransfer.effectAllowed = 'copy';
            });
            el.addEventListener('dragend', () => el.classList.remove('dragging'));

            // Double-click to add to end of queue
            el.addEventListener('dblclick', () => {
                const trackId = parseInt(el.dataset.trackId, 10); // trackId is a numeric int, not a Guid
                const type    = el.dataset.itemType;
                if (this.ws) this.ws.sendCommand('queue_add', { type, trackId });
            });
        });

        // Drop zone on playlist queue
        const queueEl = document.getElementById('playlistQueue');
        if (queueEl) {
            queueEl.addEventListener('dragover', (e) => {
                e.preventDefault();
                queueEl.classList.add('drag-over');
            });
            queueEl.addEventListener('dragleave', () => queueEl.classList.remove('drag-over'));
            queueEl.addEventListener('drop', (e) => {
                e.preventDefault();
                queueEl.classList.remove('drag-over');
                const trackId = parseInt(e.dataTransfer.getData('text/plain'), 10); // trackId is a numeric int, not a Guid
                const type    = e.dataTransfer.getData('item-type') || 'music';
                if (this.ws && trackId) {
                    this.ws.sendCommand('queue_add', { type, trackId });
                }
            });
        }
    }

    _esc(str) { return String(str||'').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
    _fmt(sec) {
        sec = Math.floor(sec || 0);
        return `${Math.floor(sec/60)}:${(sec%60).toString().padStart(2,'0')}`;
    }
}

window.ArchiveControl = ArchiveControl;
