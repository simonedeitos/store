<?php
/**
 * AirDirector Client - Admin Dashboard
 * Access via JWT SSO from store admin
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

// SSO JWT Login
if (isset($_GET['token'])) {
    $payload = jwtDecode($_GET['token']);
    if ($payload && isset($payload['admin_id'])) {
        $_SESSION['client_user_id'] = 'admin_' . $payload['admin_id'];
        $_SESSION['client_is_admin'] = true;
        $_SESSION['client_user'] = [
            'id'         => $payload['admin_id'],
            'name'       => $payload['admin_name'] ?? 'Admin',
            'display_name' => $payload['admin_name'] ?? 'Admin',
            'email'      => $payload['admin_email'] ?? '',
            'user_type'  => 'admin',
            'language'   => 'it',
        ];
        $conn = getClientDB();
        $r = mysqli_query($conn, "SELECT * FROM stations WHERE is_active = 1 ORDER BY station_name LIMIT 1");
        $firstStation = mysqli_fetch_assoc($r);
        if ($firstStation) {
            $_SESSION['client_station'] = $firstStation;
        }
        $_SESSION['admin_interaction'] = false;
    } else {
        die('Invalid or expired admin token.');
    }
}

if (!isClientAdmin()) {
    header('Location: ' . CLIENT_SITE_URL . '/index.php');
    exit;
}

$conn = getClientDB();
$stationsResult = mysqli_query($conn, "SELECT * FROM stations ORDER BY station_name");
$stations = [];
while ($s = mysqli_fetch_assoc($stationsResult)) {
    $stations[] = $s;
}

// Count connected users per station
foreach ($stations as &$s) {
    $sid = (int)$s['id'];
    $r = mysqli_query($conn, "SELECT COUNT(*) as c FROM active_sessions WHERE station_id = $sid AND last_ping > DATE_SUB(NOW(), INTERVAL 2 MINUTE)");
    $s['connected'] = mysqli_fetch_assoc($r)['c'];
}
unset($s);
?>
<!DOCTYPE html>
<html lang="it" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - <?= CLIENT_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= CLIENT_SITE_URL ?>/assets/css/client.css" rel="stylesheet">
</head>
<body class="admin-page">
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="fw-bold"><i class="bi bi-shield-check me-2 text-warning"></i>Admin Panel</h1>
        <div>
            <a href="<?= CLIENT_SITE_URL ?>/dashboard.php" class="btn btn-outline-primary me-2">
                <i class="bi bi-speedometer2 me-1"></i>Dashboard
            </a>
            <a href="<?= CLIENT_SITE_URL ?>/logout.php" class="btn btn-outline-danger">
                <i class="bi bi-box-arrow-right me-1"></i>Logout
            </a>
        </div>
    </div>

    <div class="row g-3">
        <?php foreach ($stations as $s): ?>
        <div class="col-md-4">
            <div class="card station-card <?= $s['connected'] > 0 ? 'station-online' : '' ?>">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <h5 class="card-title"><?= h($s['station_name']) ?></h5>
                        <span class="badge <?= $s['connected'] > 0 ? 'bg-success' : 'bg-secondary' ?>">
                            <?= $s['connected'] > 0 ? 'Online' : 'Offline' ?>
                        </span>
                    </div>
                    <p class="small text-muted mb-1">
                        <i class="bi bi-people me-1"></i><?= $s['connected'] ?> utenti connessi
                    </p>
                    <p class="small text-muted mb-2">
                        <i class="bi bi-key me-1"></i><code><?= h(substr($s['token'], 0, 16)) ?>...</code>
                    </p>
                    <div class="d-flex gap-2">
                        <a href="<?= CLIENT_SITE_URL ?>/admin/station_view.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-eye me-1"></i>Visualizza
                        </a>
                        <form method="post" action="<?= CLIENT_SITE_URL ?>/api/admin_switch.php" class="d-inline">
                            <button type="submit" class="btn btn-sm btn-outline-warning" name="station_id" value="<?= $s['id'] ?>">
                                <i class="bi bi-arrow-left-right me-1"></i>Switch
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
