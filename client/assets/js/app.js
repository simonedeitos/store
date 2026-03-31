/**
 * AirDirector Client - App Initialization
 */

let wsClient      = null;
let audioManager  = null;
let playerControl = null;
let playlistQueue = null;
let archiveControl= null;
let countdown     = null;
let audioMonitor  = null;

async function initApp() {
    const cfg = window.CLIENT_CONFIG;
    if (!cfg) { console.error('[App] CLIENT_CONFIG missing'); return; }

    // 1. Load language
    await LanguageManager.load(cfg.lang || localStorage.getItem('adc_lang') || 'it');

    // 2. Load language dropdown in sidebar
    try {
        const res = await fetch(`${cfg.siteUrl}/api/languages.php`);
        const data = await res.json();
        const menu = document.getElementById('langDropdownMenu');
        const label = document.getElementById('currentLangLabel');
        if (menu && data.languages) {
            menu.innerHTML = data.languages.map(l => `
                <li><a class="dropdown-item ${l.code === cfg.lang ? 'active' : ''}" href="#" data-lang-code="${l.code}">${l.name}</a></li>
            `).join('');
            menu.querySelectorAll('[data-lang-code]').forEach(el => {
                el.addEventListener('click', async (e) => {
                    e.preventDefault();
                    const code = el.dataset.langCode;
                    await LanguageManager.load(code);
                    if (label) label.textContent = code.toUpperCase();
                    menu.querySelectorAll('[data-lang-code]').forEach(x => x.classList.remove('active'));
                    el.classList.add('active');
                    // Save to server
                    fetch(`${cfg.siteUrl}/api/auth.php`, {
                        method: 'POST',
                        headers: {'Content-Type':'application/json'},
                        body: JSON.stringify({ update_lang: code })
                    }).catch(() => {});
                });
            });
        }
    } catch(e) {}

    // 3. Init WebSocket
    wsClient = new AirDirectorWS({
        wsUrl: cfg.wsUrl,
        onOpen:  () => updateConnectionStatus(true),
        onClose: () => updateConnectionStatus(false),
        onMessage: handleWSMessage,
        onError: (e) => console.error('[WS] Error', e),
    });
    await wsClient.connect();

    // 4. Init Audio
    audioManager  = new AudioManager();
    audioManager.setWS(wsClient);

    // 5. Init UI components
    playerControl  = new PlayerControl(wsClient);
    playlistQueue  = new PlaylistQueue(wsClient);
    archiveControl = new ArchiveControl(wsClient);
    countdown      = new Countdown();
    audioMonitor   = new AudioMonitor(audioManager, wsClient);

    // 6. Admin interaction toggle
    const adminToggle = document.getElementById('adminInteractionToggle');
    if (adminToggle) {
        adminToggle.addEventListener('click', async () => {
            const res = await fetch(`${cfg.siteUrl}/api/admin_switch.php`, {
                method: 'POST',
                headers: {'Content-Type':'application/json'},
                body: JSON.stringify({ toggle_interaction: true })
            });
            const data = await res.json();
            if (data.success) location.reload();
        });
    }

    // Apply interaction lock: when admin and interaction OFF, disable controls
    if (cfg.isAdmin && !cfg.adminInteraction) {
        document.getElementById('panelPlayer')?.classList.add('admin-readonly');
        document.getElementById('panelPlaylist')?.classList.add('admin-readonly');
        document.getElementById('panelArchive')?.classList.add('admin-readonly');
    }

    // 7. Station switcher for admin
    document.querySelectorAll('.admin-station-switch').forEach(el => {
        el.addEventListener('click', async (e) => {
            e.preventDefault();
            const stationId = el.dataset.stationId;
            const res = await fetch(`${cfg.siteUrl}/api/admin_switch.php`, {
                method: 'POST',
                headers: {'Content-Type':'application/json'},
                body: JSON.stringify({ station_id: parseInt(stationId, 10) })
            });
            const data = await res.json();
            if (data.success) location.reload();
        });
    });

    // 8. Sidebar toggle
    document.getElementById('sidebarToggle')?.addEventListener('click', () => {
        document.getElementById('clientSidebar')?.classList.toggle('collapsed');
    });

    // 9. Ping to keep session alive
    setInterval(() => {
        fetch(`${cfg.siteUrl}/api/websocket_auth.php`).catch(() => {});
    }, 60000);
}

function handleWSMessage(msg) {
    switch(msg.type) {
        case 'player_state':
            playerControl?.update(msg.data);
            break;
        case 'playlist_queue':
            playlistQueue?.update(msg.data);
            break;
        case 'archive_list':
        case 'archive':
            archiveControl?.update(msg.data);
            break;
        case 'music_archive':
            archiveControl?.updateMusic(msg.data || msg.tracks || []);
            break;
        case 'clip_archive':
            archiveControl?.updateClips(msg.data || msg.tracks || []);
            break;
        case 'audio_data':
            audioManager?.receiveAudioData(msg.data);
            break;
        case 'countdown':
            countdown?.update(msg.data);
            break;
        case 'connected_users':
            audioMonitor?.updateConnectedUsers(msg.data);
            break;
        case 'webrtc-offer':
            audioManager?.handleOffer(msg.peerId, msg.offer);
            break;
        case 'webrtc-answer':
            audioManager?.handleAnswer(msg.peerId, msg.answer);
            break;
        case 'webrtc-ice':
            audioManager?.handleIceCandidate(msg.peerId, msg.candidate);
            break;
        default:
            // Handle commands without type wrapper for robustness
            if (msg.command === 'audio_data' && msg.data) {
                audioManager?.receiveAudioData(msg.data);
            }
            break;
    }
}

function updateConnectionStatus(connected) {
    const dots = document.querySelectorAll('.station-dot');
    dots.forEach(d => {
        d.classList.toggle('online', connected);
    });
}

document.addEventListener('DOMContentLoaded', initApp);
