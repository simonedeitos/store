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
        if (data.music) this._music = data.music;
        if (data.clips) this._clips = data.clips;
        this._render();
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

        const icon = this._tab === 'music' ? 'bi-music-note' : 'bi-mic';
        container.innerHTML = items.map(item => `
            <div class="archive-item" draggable="true" data-item-id="${item.id}" data-item-type="${this._tab}">
                <i class="archive-item-icon bi ${icon}"></i>
                <div class="archive-item-info">
                    <div class="archive-item-title">${this._esc(item.title)}</div>
                    ${item.artist ? `<div class="archive-item-artist">${this._esc(item.artist)}</div>` : ''}
                </div>
                <span class="archive-item-duration">${this._fmt(item.duration)}</span>
            </div>
        `).join('');

        // Drag events for drag-to-queue
        container.querySelectorAll('.archive-item').forEach(el => {
            el.addEventListener('dragstart', (e) => {
                el.classList.add('dragging');
                e.dataTransfer.setData('text/plain', el.dataset.itemId);
                e.dataTransfer.setData('item-type', this._tab);
                e.dataTransfer.effectAllowed = 'copy';
            });
            el.addEventListener('dragend', () => el.classList.remove('dragging'));

            // Double-click to add to end of queue
            el.addEventListener('dblclick', () => {
                if (this.ws) {
                    this.ws.sendCommand('queue_add', { item_id: parseInt(el.dataset.itemId, 10), position: -1 });
                }
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
                const itemId = parseInt(e.dataTransfer.getData('text/plain'), 10);
                if (this.ws && itemId) {
                    this.ws.sendCommand('queue_add', { item_id: itemId, position: -1 });
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
