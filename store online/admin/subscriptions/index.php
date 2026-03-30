<?php
/**
 * AirDirector Store Admin - Gestione Sottoscrizioni Client
 */
require_once __DIR__ . '/../../functions.php';
requireAdmin();

$conn = getDBConnection();

// Stats
$r = mysqli_query($conn, "SELECT COUNT(*) as c FROM client_subscriptions WHERE status = 'active'");
$activeCount = mysqli_fetch_assoc($r)['c'];

$r = mysqli_query($conn, "SELECT COUNT(*) as c FROM client_subscriptions WHERE status = 'expired'");
$expiredCount = mysqli_fetch_assoc($r)['c'];

$r = mysqli_query($conn, "SELECT COUNT(*) as c FROM client_subscriptions WHERE expires_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 30 DAY) AND status = 'active'");
$expiringCount = mysqli_fetch_assoc($r)['c'];

// All subscriptions with user info
$subs = mysqli_query($conn, "
    SELECT cs.*, u.first_name, u.last_name, u.email as user_email, p.name as plan_name, p.billing_cycle, p.price
    FROM client_subscriptions cs
    JOIN users u ON u.id = cs.user_id
    JOIN client_subscription_plans p ON p.id = cs.plan_id
    ORDER BY cs.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sottoscrizioni Client - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= SITE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="d-flex">
    <?php include __DIR__ . '/../../includes/admin_sidebar.php'; ?>
    <div class="admin-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="fw-bold mb-0"><i class="bi bi-broadcast me-2 text-primary"></i>Sottoscrizioni Client</h1>
            <a href="<?= SITE_URL ?>/admin/subscriptions/plans.php" class="btn btn-outline-primary">
                <i class="bi bi-gear me-1"></i>Gestisci Piani
            </a>
        </div>

        <!-- Stats -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-number text-success"><?= $activeCount ?></div>
                    <div class="stat-label">Attive</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-number text-warning"><?= $expiringCount ?></div>
                    <div class="stat-label">In Scadenza (30gg)</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-number text-danger"><?= $expiredCount ?></div>
                    <div class="stat-label">Scadute</div>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-admin">
                        <tr>
                            <th>#</th>
                            <th>Cliente</th>
                            <th>Radio</th>
                            <th>Piano</th>
                            <th>Token</th>
                            <th>Scadenza</th>
                            <th>Stato</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($sub = mysqli_fetch_assoc($subs)): ?>
                        <tr>
                            <td><?= $sub['id'] ?></td>
                            <td>
                                <div><?= h($sub['first_name'] . ' ' . $sub['last_name']) ?></div>
                                <small class="text-muted"><?= h($sub['user_email']) ?></small>
                            </td>
                            <td class="fw-semibold"><?= h($sub['radio_name']) ?></td>
                            <td>
                                <div class="small"><?= h($sub['plan_name']) ?></div>
                                <small class="text-muted"><?= formatPrice($sub['price']) ?>/<?= $sub['billing_cycle'] ?></small>
                            </td>
                            <td>
                                <code class="small"><?= h(substr($sub['station_token'], 0, 12)) ?>...</code>
                                <button class="btn btn-sm btn-link p-0 ms-1" onclick="copyToken('<?= h($sub['station_token']) ?>')" title="Copia token">
                                    <i class="bi bi-clipboard"></i>
                                </button>
                            </td>
                            <td>
                                <span class="<?= strtotime($sub['expires_at']) < time() ? 'text-danger' : (strtotime($sub['expires_at']) < strtotime('+30 days') ? 'text-warning' : '') ?>">
                                    <?= date('d/m/Y', strtotime($sub['expires_at'])) ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?= $sub['status'] === 'active' ? 'success' : ($sub['status'] === 'expired' ? 'danger' : 'secondary') ?>">
                                    <?= ucfirst($sub['status']) ?>
                                </span>
                            </td>
                            <td>
                                <a href="<?= SITE_URL ?>/admin/subscriptions/view.php?id=<?= $sub['id'] ?>" class="btn btn-sm btn-outline-primary">Vedi</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function copyToken(token) {
    navigator.clipboard.writeText(token).then(() => alert('Token copiato!'));
}
</script>
</body>
</html>
