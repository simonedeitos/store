<?php
/**
 * AirDirector Client - Dashboard
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/includes/auth_check.php';

define('PAGE_TITLE', 'Dashboard');

$user = getClientUser();
$station = getCurrentStation();
$isAdmin = isClientAdmin();

// Admin: load all stations for switcher
$allStations = [];
if ($isAdmin) {
    $conn = getClientDB();
    $r2 = mysqli_query($conn, "SELECT * FROM stations WHERE is_active = 1 ORDER BY station_name");
    while ($s = mysqli_fetch_assoc($r2)) {
        $allStations[] = $s;
    }
}
?>
<!DOCTYPE html>
<html lang="it" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?= CLIENT_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= CLIENT_SITE_URL ?>/assets/css/client.css" rel="stylesheet">
</head>
<body class="dashboard-body">

<!-- Navbar top -->
<nav class="dashboard-topbar">
    <div class="topbar-left">
        <button class="btn btn-icon" id="sidebarToggle" title="Toggle Sidebar">
            <i class="bi bi-layout-sidebar"></i>
        </button>
        <div class="topbar-brand">
            <i class="bi bi-broadcast-pin me-2"></i>
            <span><?= CLIENT_NAME ?></span>
        </div>
        <?php if ($station): ?>
        <div class="topbar-station ms-3">
            <span class="station-dot online"></span>
            <span id="topbarStationName"><?= h($station['station_name']) ?></span>
        </div>
        <?php endif; ?>
    </div>
    <div class="topbar-right">
        <?php if ($isAdmin && !empty($allStations)): ?>
        <div class="dropdown me-2">
            <button class="btn btn-sm btn-outline-warning dropdown-toggle" type="button" data-bs-toggle="dropdown">
                <i class="bi bi-arrow-left-right me-1"></i>
                <span data-lang="admin.switch_station">Switch Stazione</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <?php foreach ($allStations as $s): ?>
                <li>
                    <a class="dropdown-item admin-station-switch" href="#" data-station-id="<?= $s['id'] ?>">
                        <?= h($s['station_name']) ?>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php if ($isAdmin): ?>
        <div class="me-2">
            <button class="btn btn-sm <?= (($_SESSION['admin_interaction'] ?? false) ? 'btn-danger' : 'btn-outline-secondary') ?>" id="adminInteractionToggle" title="Admin Interaction">
                <i class="bi bi-shield-check me-1"></i>
                <span id="adminInteractionLabel"><?= ($_SESSION['admin_interaction'] ?? false) ? 'Interaction ON' : 'Interaction OFF' ?></span>
            </button>
        </div>
        <?php endif; ?>
        <?php endif; ?>
        <span class="topbar-user">
            <i class="bi bi-person-circle me-1"></i>
            <?= h($user['name'] ?? $user['display_name'] ?? '') ?>
        </span>
        <a href="<?= CLIENT_SITE_URL ?>/logout.php" class="btn btn-sm btn-outline-danger ms-2">
            <i class="bi bi-box-arrow-right"></i>
        </a>
    </div>
</nav>

<div class="dashboard-layout">
    <!-- Sidebar -->
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="dashboard-main" id="dashboardMain">

        <!-- Row 1: Player + Archive -->
        <div class="dashboard-row">

            <!-- Player Control Panel -->
            <div class="dashboard-panel panel-player" id="panelPlayer">
                <div class="panel-header">
                    <div class="panel-title">
                        <i class="bi bi-play-circle me-2"></i>
                        <span data-lang="player.title">Player Control</span>
                    </div>
                    <div class="panel-controls">
                        <span class="mode-badge" id="modeBadge" data-lang="player.mode_auto">AUTO</span>
                    </div>
                </div>
                <div class="panel-body">
                    <!-- Now Playing -->
                    <div class="now-playing">
                        <div class="now-playing-art">
                            <i class="bi bi-music-note-beamed"></i>
                        </div>
                        <div class="now-playing-info">
                            <div class="now-playing-title" id="nowPlayingTitle" data-lang="player.no_track">Nessun brano in onda</div>
                            <div class="now-playing-artist" id="nowPlayingArtist"></div>
                        </div>
                        <div class="now-playing-status" id="playerStatusBadge">
                            <span class="badge bg-secondary" data-lang="player.stopped">Stopped</span>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="progress-container">
                        <span id="progressTime">0:00</span>
                        <div class="progress flex-grow-1 mx-2" style="height: 6px; cursor:pointer;" id="progressBar">
                            <div class="progress-bar bg-primary" id="progressFill" role="progressbar" style="width: 0%"></div>
                        </div>
                        <span id="progressDuration">0:00</span>
                    </div>

                    <!-- Player Buttons -->
                    <div class="player-buttons">
                        <button class="btn btn-player btn-stop" id="btnStop" title="Stop">
                            <i class="bi bi-stop-fill"></i>
                        </button>
                        <button class="btn btn-player btn-play" id="btnPlay" title="Play">
                            <i class="bi bi-play-fill"></i>
                        </button>
                        <button class="btn btn-player btn-pause" id="btnPause" title="Pause">
                            <i class="bi bi-pause-fill"></i>
                        </button>
                        <button class="btn btn-player btn-skip" id="btnSkip" title="Skip">
                            <i class="bi bi-skip-end-fill"></i>
                        </button>
                        <div class="mode-toggle ms-3">
                            <button class="btn btn-sm btn-outline-primary" id="btnModeAuto" data-lang="player.auto">AUTO</button>
                            <button class="btn btn-sm btn-outline-secondary" id="btnModeManual" data-lang="player.manual">MANUALE</button>
                        </div>
                    </div>

                    <!-- Volume -->
                    <div class="volume-row mt-2">
                        <i class="bi bi-volume-down me-2"></i>
                        <input type="range" class="form-range flex-grow-1" id="volumeSlider" min="0" max="100" value="80">
                        <i class="bi bi-volume-up ms-2"></i>
                        <span class="ms-2" id="volumeValue">80%</span>
                    </div>
                </div>
            </div>

            <!-- Archive Control Panel -->
            <div class="dashboard-panel panel-archive" id="panelArchive">
                <div class="panel-header">
                    <div class="panel-title">
                        <i class="bi bi-collection-play me-2"></i>
                        <span data-lang="archive.title">Archivio</span>
                    </div>
                </div>
                <div class="panel-body">
                    <!-- Tabs -->
                    <ul class="nav nav-tabs nav-tabs-dark mb-3" id="archiveTabs">
                        <li class="nav-item">
                            <button class="nav-link active" id="tabMusic" data-tab="music">
                                <i class="bi bi-music-note me-1"></i>
                                <span data-lang="archive.music">Musica</span>
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" id="tabClips" data-tab="clips">
                                <i class="bi bi-mic me-1"></i>
                                <span data-lang="archive.clips">Clips</span>
                            </button>
                        </li>
                    </ul>

                    <!-- Search -->
                    <div class="input-group mb-3">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control" id="archiveSearch" placeholder="Cerca..." data-lang-placeholder="archive.search_placeholder">
                    </div>

                    <!-- Archive List -->
                    <div id="archiveList" class="archive-list">
                        <div class="text-center text-muted py-3" data-lang="archive.loading">Caricamento archivio...</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Row 2: Playlist Queue + Countdown -->
        <div class="dashboard-row">

            <!-- Playlist Queue Panel -->
            <div class="dashboard-panel panel-playlist" id="panelPlaylist">
                <div class="panel-header">
                    <div class="panel-title">
                        <i class="bi bi-list-ul me-2"></i>
                        <span data-lang="playlist.title">Coda Playlist</span>
                    </div>
                    <div class="panel-controls">
                        <span class="badge bg-secondary" id="queueCount">0</span>
                    </div>
                </div>
                <div class="panel-body">
                    <div id="playlistQueue" class="playlist-queue-list">
                        <div class="text-center text-muted py-3" data-lang="playlist.empty">Coda vuota</div>
                    </div>
                </div>
            </div>

            <!-- Countdown Panel -->
            <div class="dashboard-panel panel-countdown" id="panelCountdown">
                <div class="panel-header">
                    <div class="panel-title">
                        <i class="bi bi-alarm me-2"></i>
                        <span data-lang="countdown.title">Countdown</span>
                    </div>
                </div>
                <div class="panel-body">
                    <div class="countdown-grid">
                        <div class="countdown-item">
                            <div class="countdown-label" data-lang="countdown.next_schedule">Prossima Schedulazione</div>
                            <div class="countdown-value" id="countdownSchedule">--:--:--</div>
                            <div class="countdown-name text-muted small" id="scheduleName"></div>
                        </div>
                        <div class="countdown-divider"></div>
                        <div class="countdown-item">
                            <div class="countdown-label" data-lang="countdown.next_ad">Prossima Pubblicità</div>
                            <div class="countdown-value countdown-ad" id="countdownAd">--:--:--</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Audio Monitor Bar -->
        <div class="audio-monitor-bar" id="audioMonitorBar">
            <div class="audio-monitor-label">
                <i class="bi bi-soundwave me-2"></i>
                <span data-lang="audio.monitor_label">Audio Monitor</span>
            </div>
            <div class="audio-meter-container">
                <div class="audio-meter" id="audioMeterL">
                    <div class="audio-meter-fill" id="audioMeterFillL"></div>
                </div>
                <div class="audio-meter" id="audioMeterR">
                    <div class="audio-meter-fill" id="audioMeterFillR"></div>
                </div>
            </div>
            <div class="audio-monitor-controls">
                <select class="form-select form-select-sm me-2" id="audioOutputSelect" style="width:auto">
                    <option data-lang="audio.default_output">Output predefinito</option>
                </select>
                <button class="btn btn-sm btn-mic" id="btnSendMic" title="Send Microphone">
                    <i class="bi bi-mic-fill me-1"></i>
                    <span data-lang="audio.send_mic">SEND MIC</span>
                </button>
                <select class="form-select form-select-sm ms-2 me-2" id="audioInputSelect" style="width:auto">
                    <option data-lang="audio.default_input">Microfono predefinito</option>
                </select>
                <div class="dropdown ms-2">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-sliders me-1"></i>
                        <span data-lang="audio.quality">Qualità</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item quality-option" href="#" data-quality="low">Low (32kbps mono)</a></li>
                        <li><a class="dropdown-item quality-option active" href="#" data-quality="medium">Medium (128kbps stereo)</a></li>
                        <li><a class="dropdown-item quality-option" href="#" data-quality="high">High (256kbps)</a></li>
                        <li><a class="dropdown-item quality-option" href="#" data-quality="studio">Studio (320kbps)</a></li>
                    </ul>
                </div>
            </div>
        </div>

    </div><!-- end dashboard-main -->
</div><!-- end dashboard-layout -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
window.CLIENT_CONFIG = {
    wsUrl: '<?= WS_SERVER_URL ?>',
    siteUrl: '<?= CLIENT_SITE_URL ?>',
    stationToken: '<?= h($station['token'] ?? '') ?>',
    stationId: <?= (int)($station['id'] ?? 0) ?>,
    userType: '<?= h($user['user_type'] ?? '') ?>',
    userId: <?= (int)($user['id'] ?? 0) ?>,
    userName: '<?= h($user['name'] ?? $user['display_name'] ?? '') ?>',
    lang: '<?= h(getUserLanguage()) ?>',
    isAdmin: <?= $isAdmin ? 'true' : 'false' ?>,
    adminInteraction: <?= (($_SESSION['admin_interaction'] ?? false) ? 'true' : 'false') ?>
};
</script>
<script src="<?= CLIENT_SITE_URL ?>/assets/js/language.js"></script>
<script src="<?= CLIENT_SITE_URL ?>/assets/js/websocket.js"></script>
<script src="<?= CLIENT_SITE_URL ?>/assets/js/webrtc.js"></script>
<script src="<?= CLIENT_SITE_URL ?>/assets/js/player-control.js"></script>
<script src="<?= CLIENT_SITE_URL ?>/assets/js/playlist-queue.js"></script>
<script src="<?= CLIENT_SITE_URL ?>/assets/js/archive-control.js"></script>
<script src="<?= CLIENT_SITE_URL ?>/assets/js/countdown.js"></script>
<script src="<?= CLIENT_SITE_URL ?>/assets/js/audio-monitor.js"></script>
<script src="<?= CLIENT_SITE_URL ?>/assets/js/app.js"></script>
</body>
</html>
