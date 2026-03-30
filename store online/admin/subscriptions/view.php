<?php
/**
 * AirDirector Store Admin - Dettaglio Sottoscrizione
 */
require_once __DIR__ . '/../../functions.php';
requireAdmin();

$conn = getDBConnection();
$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ' . SITE_URL . '/admin/subscriptions/'); exit; }

$message = '';
$error   = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'toggle_status') {
        $allowed = ['active', 'suspended', 'expired', 'cancelled'];
        $newStatus = $_POST['new_status'] ?? 'suspended';
        if (!in_array($newStatus, $allowed, true)) {
            $error = 'Stato non valido.';
        } else {
            $newStatusEsc = dbEsc($newStatus);
            mysqli_query($conn, "UPDATE client_subscriptions SET status = '$newStatusEsc' WHERE id = $id");
            $message = 'Stato aggiornato.';

            // Sync station to client DB when activating
            if ($newStatus === 'active') {
                $subData = mysqli_fetch_assoc(mysqli_query($conn, "SELECT cs.*, u.id as uid FROM client_subscriptions cs JOIN users u ON u.id = cs.user_id WHERE cs.id = $id"));
                if ($subData) {
                    $clientConn = mysqli_connect(CLIENT_DB_HOST, CLIENT_DB_USER, CLIENT_DB_PASS, CLIENT_DB_NAME, CLIENT_DB_PORT);
                    if ($clientConn) {
                        mysqli_set_charset($clientConn, 'utf8mb4');
                        $cToken  = mysqli_real_escape_string($clientConn, $subData['station_token']);
                        $cName   = mysqli_real_escape_string($clientConn, $subData['radio_name']);
                        $cUserId = (int)$subData['uid'];
                        $cSubId  = (int)$subData['id'];
                        mysqli_query($clientConn, "
                            INSERT INTO stations (store_subscription_id, store_user_id, station_name, token, is_active)
                            VALUES ($cSubId, $cUserId, '$cName', '$cToken', 1)
                            ON DUPLICATE KEY UPDATE station_name='$cName', is_active=1
                        ");
                        mysqli_close($clientConn);
                    }
                }
            }
            // When suspending/cancelling, deactivate station in client DB
            if (in_array($newStatus, ['suspended', 'expired', 'cancelled'])) {
                $subData = mysqli_fetch_assoc(mysqli_query($conn, "SELECT station_token FROM client_subscriptions WHERE id = $id"));
                if ($subData) {
                    $clientConn = mysqli_connect(CLIENT_DB_HOST, CLIENT_DB_USER, CLIENT_DB_PASS, CLIENT_DB_NAME, CLIENT_DB_PORT);
                    if ($clientConn) {
                        mysqli_set_charset($clientConn, 'utf8mb4');
                        $cToken = mysqli_real_escape_string($clientConn, $subData['station_token']);
                        mysqli_query($clientConn, "UPDATE stations SET is_active = 0 WHERE token = '$cToken'");
                        mysqli_close($clientConn);
                    }
                }
            }
        }
    } elseif ($action === 'extend') {
        $days = (int)($_POST['extend_days'] ?? 30);
        mysqli_query($conn, "UPDATE client_subscriptions SET expires_at = DATE_ADD(expires_at, INTERVAL $days DAY) WHERE id = $id");
        $message = "Scadenza estesa di $days giorni.";
    } elseif ($action === 'generate_admin_token') {
        $admin     = getCurrentAdmin();
        $adminId   = (int)($admin['id'] ?? 0);
        $adminName = $admin['username'] ?? 'Admin';
        $jwtSecret = CLIENT_ADMIN_JWT_SECRET;
        $header    = rtrim(strtr(base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT'])), '+/', '-_'), '=');
        $body      = rtrim(strtr(base64_encode(json_encode([
            'admin_id'    => $adminId,
            'admin_name'  => $adminName,
            'admin_email' => $admin['email'] ?? '',
            'exp'         => time() + 3600,
        ])), '+/', '-_'), '=');
        $sig    = rtrim(strtr(base64_encode(hash_hmac('sha256', "$header.$body", $jwtSecret, true)), '+/', '-_'), '=');
        $token  = "$header.$body.$sig";
        header('Location: ' . CLIENT_SITE_URL . '/admin/?token=' . urlencode($token));
        exit;
    }
}

// Fetch subscription
$r = mysqli_query($conn, "
    SELECT cs.*, u.first_name, u.last_name, u.email as user_email, p.name as plan_name, p.billing_cycle, p.price
    FROM client_subscriptions cs
    JOIN users u ON u.id = cs.user_id
    JOIN client_subscription_plans p ON p.id = cs.plan_id
    WHERE cs.id = $id
");
$sub = mysqli_fetch_assoc($r);
if (!$sub) { die('Sottoscrizione non trovata.'); }

// Subusers
$subusers = mysqli_query($conn, "SELECT * FROM client_station_subusers WHERE subscription_id = $id ORDER BY name");
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sottoscrizione #<?= $id ?> - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= SITE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="d-flex">
    <?php include __DIR__ . '/../../includes/admin_sidebar.php'; ?>
    <div class="admin-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="fw-bold mb-0">Sottoscrizione #<?= $id ?></h1>
            <div class="d-flex gap-2">
                <form method="post">
                    <input type="hidden" name="action" value="generate_admin_token">
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-box-arrow-up-right me-1"></i>Apri nel Client
                    </button>
                </form>
                <a href="<?= SITE_URL ?>/admin/subscriptions/" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Indietro
                </a>
            </div>
        </div>

        <?php if ($message): ?><div class="alert alert-success"><?= h($message) ?></div><?php endif; ?>
        <?php if ($error):   ?><div class="alert alert-danger"><?= h($error) ?></div><?php endif; ?>

        <div class="row g-4">
            <!-- Subscription Details -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header"><h5 class="mb-0">Dettagli</h5></div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <tr><th width="40%">Cliente</th><td><?= h($sub['first_name'] . ' ' . $sub['last_name']) ?> <small class="text-muted">(<?= h($sub['user_email']) ?>)</small></td></tr>
                            <tr><th>Radio</th><td class="fw-bold"><?= h($sub['radio_name']) ?></td></tr>
                            <tr><th>Piano</th><td><?= h($sub['plan_name']) ?> — <?= formatPrice($sub['price']) ?></td></tr>
                            <tr><th>Inizio</th><td><?= date('d/m/Y', strtotime($sub['started_at'])) ?></td></tr>
                            <tr><th>Scadenza</th><td class="<?= strtotime($sub['expires_at']) < time() ? 'text-danger fw-bold' : '' ?>"><?= date('d/m/Y H:i', strtotime($sub['expires_at'])) ?></td></tr>
                            <tr><th>Stato</th><td><span class="badge bg-<?= $sub['status'] === 'active' ? 'success' : ($sub['status'] === 'expired' ? 'danger' : 'warning') ?>"><?= ucfirst($sub['status']) ?></span></td></tr>
                            <tr><th>Token</th><td><code><?= h($sub['station_token']) ?></code></td></tr>
                        </table>

                        <!-- Actions -->
                        <div class="d-flex gap-2 flex-wrap mt-2">
                            <form method="post" class="d-inline">
                                <input type="hidden" name="action" value="toggle_status">
                                <?php if ($sub['status'] === 'active'): ?>
                                <input type="hidden" name="new_status" value="suspended">
                                <button type="submit" class="btn btn-sm btn-warning">Sospendi</button>
                                <?php else: ?>
                                <input type="hidden" name="new_status" value="active">
                                <button type="submit" class="btn btn-sm btn-success">Riattiva</button>
                                <?php endif; ?>
                            </form>
                            <form method="post" class="d-inline">
                                <input type="hidden" name="action" value="extend">
                                <div class="input-group input-group-sm">
                                    <input type="number" name="extend_days" value="30" min="1" class="form-control" style="width:70px">
                                    <button type="submit" class="btn btn-outline-primary">Estendi gg</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Subusers -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Sottoutenti</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead><tr><th>Nome</th><th>Email</th><th>Accesso</th><th>Stato</th></tr></thead>
                            <tbody>
                                <?php if (mysqli_num_rows($subusers) === 0): ?>
                                <tr><td colspan="4" class="text-muted text-center py-3">Nessun sottoutente</td></tr>
                                <?php else: while ($su = mysqli_fetch_assoc($subusers)): ?>
                                <tr>
                                    <td><?= h($su['name']) ?></td>
                                    <td class="small"><?= h($su['email']) ?></td>
                                    <td class="small text-muted"><?= h($su['access_time_start']) ?> - <?= h($su['access_time_end']) ?></td>
                                    <td><span class="badge bg-<?= $su['is_active'] ? 'success' : 'secondary' ?>"><?= $su['is_active'] ? 'Attivo' : 'Sospeso' ?></span></td>
                                </tr>
                                <?php endwhile; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
