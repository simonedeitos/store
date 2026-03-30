/**
 * AirDirector Client - WebSocket Signaling Server
 * 
 * Handles:
 * - Authentication via JWT
 * - Room management (one room per station token)
 * - Message routing: AirDirector <-> Browser clients
 * - WebRTC signaling (offer/answer/ICE)
 * - Connected users list broadcast
 * - Microphone status broadcast
 */

const WebSocket = require('ws');
const jwt       = require('jsonwebtoken');
const http      = require('http');
const { RoomManager } = require('./rooms');

// --- Config ---
const PORT       = process.env.PORT || 8080;
const JWT_SECRET = process.env.JWT_SECRET || 'airdirector-admin-sso-secret-2025';
if (!process.env.JWT_SECRET) {
    console.warn('[WS Server] WARNING: JWT_SECRET env variable not set, using default. Set JWT_SECRET in production!');
}
const PING_INTERVAL = 30000; // 30s

const roomManager = new RoomManager();

// --- HTTP Server (for health check) ---
const server = http.createServer((req, res) => {
    if (req.url === '/health') {
        res.writeHead(200, { 'Content-Type': 'application/json' });
        res.end(JSON.stringify({ status: 'ok', rooms: roomManager.rooms.size }));
    } else {
        res.writeHead(404);
        res.end();
    }
});

// --- WebSocket Server ---
const wss = new WebSocket.Server({ server });

wss.on('connection', (ws, req) => {
    const url    = new URL(req.url, `http://${req.headers.host}`);
    const token  = url.searchParams.get('token');
    const adToken= url.searchParams.get('ad_token'); // AirDirector device token (station token)

    let userInfo = null;
    let room     = null;

    // --- Authenticate ---
    if (adToken) {
        // AirDirector software connecting with station token directly
        userInfo = {
            userId:    0,
            userName:  'AirDirector',
            userType:  'airdirector',
            stationToken: adToken,
        };
        room = roomManager.getOrCreate(adToken);
        room.add(ws, userInfo);
        console.log(`[WS] AirDirector connected to room: ${adToken}`);
    } else if (token) {
        // Browser client connecting with JWT
        try {
            const payload = jwt.verify(token, JWT_SECRET);
            userInfo = {
                userId:       payload.user_id,
                userName:     payload.user_name,
                userType:     payload.user_type,
                stationToken: payload.station_token,
                stationId:    payload.station_id,
            };
            room = roomManager.getOrCreate(payload.station_token);
            room.add(ws, userInfo);
            console.log(`[WS] Client connected: ${userInfo.userName} -> room: ${payload.station_token}`);

            // Send welcome + current users list
            ws.send(JSON.stringify({ type: 'welcome', userId: userInfo.userId }));
            broadcastUserList(room);
        } catch(e) {
            console.warn('[WS] Auth failed:', e.message);
            ws.send(JSON.stringify({ type: 'error', message: 'Authentication failed' }));
            ws.close(1008, 'Auth failed');
            return;
        }
    } else {
        ws.send(JSON.stringify({ type: 'error', message: 'Token required' }));
        ws.close(1008, 'No token');
        return;
    }

    // --- Ping/Pong ---
    ws.isAlive = true;
    ws.on('pong', () => { ws.isAlive = true; });

    // --- Message handling ---
    ws.on('message', (raw) => {
        let msg;
        try { msg = JSON.parse(raw); } catch { return; }

        const currentRoom = roomManager.findRoom(ws);
        if (!currentRoom) return;

        const senderInfo = currentRoom.getUserInfo(ws);

        switch (msg.type) {
            // From browser → AirDirector
            case 'command':
                currentRoom.sendToAirDirector(msg);
                // Log: optional
                break;

            // From AirDirector → all browser clients
            case 'player_state':
            case 'playlist_queue':
            case 'archive_list':
            case 'countdown':
                currentRoom.sendToClients(msg);
                break;

            // Mic status from browser client
            case 'mic-status':
                currentRoom.setMicActive(ws, msg.active === true);
                broadcastUserList(currentRoom);
                // Forward to AirDirector
                if (senderInfo) {
                    currentRoom.sendToAirDirector({
                        type: 'mic-status',
                        userId: senderInfo.userId,
                        userName: senderInfo.userName,
                        active: msg.active,
                    });
                }
                break;

            // WebRTC signaling
            case 'webrtc-offer':
                // Route to specific peer or AirDirector
                if (msg.peerId === 'airdirector') {
                    currentRoom.sendToAirDirector({ ...msg, fromId: senderInfo?.userId });
                } else {
                    routeToPeer(currentRoom, msg.peerId, msg);
                }
                break;

            case 'webrtc-answer':
                routeToPeer(currentRoom, msg.peerId, msg);
                break;

            case 'webrtc-ice':
                if (msg.peerId === 'airdirector') {
                    currentRoom.sendToAirDirector({ ...msg, fromId: senderInfo?.userId });
                } else {
                    routeToPeer(currentRoom, msg.peerId, msg);
                }
                break;

            default:
                // Forward unknown messages to whole room
                currentRoom.broadcast(msg, ws);
        }
    });

    // --- Disconnect ---
    ws.on('close', () => {
        const currentRoom = roomManager.findRoom(ws);
        if (currentRoom) {
            const info = currentRoom.getUserInfo(ws);
            console.log(`[WS] Disconnected: ${info?.userName || 'unknown'}`);
            currentRoom.remove(ws);
            broadcastUserList(currentRoom);
            roomManager.cleanup();
        }
    });

    ws.on('error', (err) => {
        console.error('[WS] Error:', err.message);
    });
});

// --- Ping interval ---
const pingInterval = setInterval(() => {
    wss.clients.forEach(ws => {
        if (!ws.isAlive) { ws.terminate(); return; }
        ws.isAlive = false;
        ws.ping();
    });
}, PING_INTERVAL);

wss.on('close', () => clearInterval(pingInterval));

// --- Helpers ---
function broadcastUserList(room) {
    const users = room.getConnectedUsers();
    room.broadcast({ type: 'connected_users', data: users });
}

function routeToPeer(room, peerId, msg) {
    room.users.forEach((info, ws) => {
        if (String(info.userId) === String(peerId) && ws.readyState === WebSocket.OPEN) {
            ws.send(JSON.stringify(msg));
        }
    });
}

// --- Start ---
server.listen(PORT, () => {
    console.log(`[WS Server] AirDirector Client WS Server listening on port ${PORT}`);
    console.log(`[WS Server] Health check: http://localhost:${PORT}/health`);
});
