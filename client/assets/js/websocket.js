/**
 * AirDirector Client - WebSocket Manager
 */
class AirDirectorWS {
    constructor(options = {}) {
        this.wsUrl      = options.wsUrl || '';
        this.wsToken    = options.wsToken || '';
        this.onMessage  = options.onMessage || (() => {});
        this.onOpen     = options.onOpen || (() => {});
        this.onClose    = options.onClose || (() => {});
        this.onError    = options.onError || (() => {});

        this.ws              = null;
        this.reconnectDelay  = 2000;
        this.maxReconnect    = 10;
        this._reconnectCount = 0;
        this._reconnectTimer = null;
        this._intentionalClose = false;
    }

    async connect() {
        // Get WS token from server
        try {
            const res = await fetch('/api/websocket_auth.php');
            const data = await res.json();
            if (data.success) {
                this.wsToken    = data.ws_token;
                this.stationToken = data.station_token;
            }
        } catch(e) { console.error('WS auth failed', e); }

        const url = `${this.wsUrl}?token=${encodeURIComponent(this.wsToken)}`;
        this.ws = new WebSocket(url);

        this.ws.onopen = () => {
            console.log('[WS] Connected');
            this._reconnectCount = 0;
            this.onOpen();
        };

        this.ws.onmessage = (event) => {
            try {
                const msg = JSON.parse(event.data);
                this.onMessage(msg);
            } catch(e) { /* non-JSON */ }
        };

        this.ws.onclose = (event) => {
            this.onClose(event);
            if (!this._intentionalClose && this._reconnectCount < this.maxReconnect) {
                this._reconnectCount++;
                console.log(`[WS] Reconnecting in ${this.reconnectDelay}ms (attempt ${this._reconnectCount})`);
                this._reconnectTimer = setTimeout(() => this.connect(), this.reconnectDelay);
                this.reconnectDelay = Math.min(this.reconnectDelay * 1.5, 30000);
            }
        };

        this.ws.onerror = (err) => {
            this.onError(err);
        };
    }

    send(data) {
        if (this.ws && this.ws.readyState === WebSocket.OPEN) {
            this.ws.send(JSON.stringify(data));
            return true;
        }
        return false;
    }

    sendCommand(action, extra = {}) {
        return this.send({ type: 'command', action, ...extra });
    }

    close() {
        this._intentionalClose = true;
        if (this._reconnectTimer) clearTimeout(this._reconnectTimer);
        if (this.ws) this.ws.close();
    }

    get isConnected() {
        return this.ws && this.ws.readyState === WebSocket.OPEN;
    }
}

window.AirDirectorWS = AirDirectorWS;
