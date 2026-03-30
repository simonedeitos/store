/**
 * AirDirector Client - Room Manager
 * Manages connected users grouped by station token
 */
const WS_OPEN = 1; // WebSocket.OPEN

class Room {
    constructor(token) {
        this.token = token;
        this.users = new Map(); // ws -> userInfo
    }

    add(ws, userInfo) {
        this.users.set(ws, {
            id:        userInfo.userId,
            name:      userInfo.userName,
            type:      userInfo.userType,   // 'owner' | 'subuser' | 'admin' | 'airdirector'
            micActive: false,
        });
    }

    remove(ws) {
        this.users.delete(ws);
    }

    get size() { return this.users.size; }

    getUserInfo(ws) { return this.users.get(ws); }

    setMicActive(ws, active) {
        const u = this.users.get(ws);
        if (u) u.micActive = active;
    }

    /**
     * Broadcast to all users in the room (optionally excluding sender)
     */
    broadcast(message, excludeWs = null) {
        const data = typeof message === 'string' ? message : JSON.stringify(message);
        this.users.forEach((info, ws) => {
            if (ws !== excludeWs && ws.readyState === WS_OPEN) {
                ws.send(data);
            }
        });
    }

    /**
     * Send only to AirDirector client(s) in this room
     */
    sendToAirDirector(message) {
        const data = typeof message === 'string' ? message : JSON.stringify(message);
        this.users.forEach((info, ws) => {
            if (info.type === 'airdirector' && ws.readyState === WS_OPEN) {
                ws.send(data);
            }
        });
    }

    /**
     * Send to all browser clients (not AirDirector)
     */
    sendToClients(message, excludeWs = null) {
        const data = typeof message === 'string' ? message : JSON.stringify(message);
        this.users.forEach((info, ws) => {
            if (info.type !== 'airdirector' && ws !== excludeWs && ws.readyState === WS_OPEN) {
                ws.send(data);
            }
        });
    }

    getConnectedUsers() {
        const list = [];
        this.users.forEach((info) => {
            if (info.type !== 'airdirector') {
                list.push({
                    id:        info.id,
                    name:      info.name,
                    type:      info.type,
                    mic_active: info.micActive,
                });
            }
        });
        return list;
    }
}

class RoomManager {
    constructor() {
        this.rooms = new Map(); // token -> Room
    }

    getOrCreate(token) {
        if (!this.rooms.has(token)) {
            this.rooms.set(token, new Room(token));
        }
        return this.rooms.get(token);
    }

    get(token) { return this.rooms.get(token); }

    remove(token) { this.rooms.delete(token); }

    /**
     * Find the room a given ws client is in
     */
    findRoom(ws) {
        for (const [, room] of this.rooms) {
            if (room.users.has(ws)) return room;
        }
        return null;
    }

    cleanup() {
        for (const [token, room] of this.rooms) {
            if (room.size === 0) this.rooms.delete(token);
        }
    }
}

module.exports = { Room, RoomManager };
