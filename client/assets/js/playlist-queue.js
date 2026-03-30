/**
 * AirDirector Client - Playlist Queue UI with drag&drop
 */
class PlaylistQueue {
    constructor(ws) {
        this.ws      = ws;
        this._items  = [];
        this._sortable = null;
        this._init();
    }

    _init() {
        const container = document.getElementById('playlistQueue');
        if (!container || typeof Sortable === 'undefined') return;

        this._sortable = Sortable.create(container, {
            animation: 150,
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            group: { name: 'playlist', put: ['archive'] },
            onEnd: (evt) => {
                // Reorder in local array
                const moved = this._items.splice(evt.oldIndex, 1)[0];
                this._items.splice(evt.newIndex, 0, moved);
                // Send reorder command with ordered Guid ids
                const ids = this._items.map(i => i.id);
                if (this.ws) this.ws.sendCommand('queue_reorder', { ids });
                this._renderPositions();
            },
            onAdd: (evt) => {
                // Item dropped from archive
                const trackId = parseInt(evt.item.dataset.trackId, 10);
                const itemType = evt.item.dataset.itemType || 'music';
                if (this.ws) this.ws.sendCommand('queue_add', { type: itemType, trackId });
                // Remove the cloned element; actual data will come via WS update
                evt.item.remove();
            }
        });
    }

    update(items) {
        this._items = items || [];
        this._render();
    }

    _render() {
        const container = document.getElementById('playlistQueue');
        const countEl   = document.getElementById('queueCount');
        if (!container) return;
        if (countEl) countEl.textContent = this._items.length;

        if (this._items.length === 0) {
            container.innerHTML = `<div class="text-center text-muted py-3" data-lang="playlist.empty">${LanguageManager.get('playlist.empty', 'Coda vuota')}</div>`;
            return;
        }

        container.innerHTML = this._items.map((item, i) => `
            <div class="queue-item" data-item-id="${item.id}">
                <span class="queue-item-pos">${i + 1}</span>
                <div class="queue-item-info">
                    <div class="queue-item-title">${this._esc(item.title)}</div>
                    <div class="queue-item-artist">${this._esc(item.artist || '')}</div>
                </div>
                <span class="queue-item-duration">${this._fmt(item.duration)}</span>
                <button class="queue-item-remove" data-item-id="${item.id}" title="Rimuovi">
                    <i class="bi bi-x"></i>
                </button>
            </div>
        `).join('');

        // Remove buttons
        container.querySelectorAll('.queue-item-remove').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const id = btn.dataset.itemId; // Guid string — do NOT parseInt
                if (this.ws) this.ws.sendCommand('queue_remove', { id });
            });
        });

        // Re-init sortable on new elements
        if (this._sortable && typeof Sortable !== 'undefined') {
            this._sortable.destroy();
            this._init();
        }
    }

    _renderPositions() {
        document.querySelectorAll('#playlistQueue .queue-item-pos').forEach((el, i) => {
            el.textContent = i + 1;
        });
    }

    _esc(str) { return String(str||'').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
    _fmt(sec) {
        sec = Math.floor(sec || 0);
        return `${Math.floor(sec/60)}:${(sec%60).toString().padStart(2,'0')}`;
    }
}

window.PlaylistQueue = PlaylistQueue;
