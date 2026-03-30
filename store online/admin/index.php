<?php
/**
 * AirDirector Store - Admin Dashboard
 */
require_once __DIR__ . '/../functions.php';
requireAdmin();

$conn = getDBConnection();

// Stats
$stats = [];
$r = mysqli_query($conn, "SELECT COUNT(*) as c FROM software WHERE is_active = 1"); $stats['software'] = mysqli_fetch_assoc($r)['c'];
$r = mysqli_query($conn, "SELECT COUNT(*) as c FROM bundles WHERE is_active = 1"); $stats['bundles'] = mysqli_fetch_assoc($r)['c'];
$r = mysqli_query($conn, "SELECT COUNT(*) as c FROM users"); $stats['customers'] = mysqli_fetch_assoc($r)['c'];
$r = mysqli_query($conn, "SELECT COUNT(*) as c FROM orders"); $stats['orders'] = mysqli_fetch_assoc($r)['c'];
$r = mysqli_query($conn, "SELECT COUNT(*) as c FROM orders WHERE status = 'pending'"); $stats['pending'] = mysqli_fetch_assoc($r)['c'];
$r = mysqli_query($conn, "SELECT COUNT(*) as c FROM licenses"); $stats['licenses'] = mysqli_fetch_assoc($r)['c'];
$r = mysqli_query($conn, "SELECT COUNT(*) as c FROM licenses WHERE is_active = 1"); $stats['active_licenses'] = mysqli_fetch_assoc($r)['c'];
$r = mysqli_query($conn, "SELECT COALESCE(SUM(total), 0) as t FROM orders WHERE status = 'confirmed'"); $stats['revenue'] = mysqli_fetch_assoc($r)['t'];
$r = mysqli_query($conn, "SELECT COUNT(*) as c FROM profile_requests WHERE status = 'pending'"); $stats['pending_requests'] = mysqli_fetch_assoc($r)['c'];

// Ultimi ordini
$recentOrders = mysqli_query($conn, "SELECT o.*, u.first_name, u.last_name, u.email FROM orders o JOIN users u ON u.id = o.user_id ORDER BY o.created_at DESC LIMIT 10");
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Admin <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= SITE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="d-flex">
    <?php include __DIR__ . '/../includes/admin_sidebar.php'; ?>
    
    <div class="admin-content">
        <h1 class="fw-bold mb-4">Dashboard</h1>
        
        <!-- STATS -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number"><?= formatPrice($stats['revenue']) ?></div>
                    <div class="stat-label">Fatturato</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number"><?= $stats['orders'] ?></div>
                    <div class="stat-label">Ordini Totali</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number text-warning"><?= $stats['pending'] ?></div>
                    <div class="stat-label">Ordini in Attesa</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number"><?= $stats['customers'] ?></div>
                    <div class="stat-label">Clienti</div>
                </div>
            </div>
        </div>
        
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number"><?= $stats['software'] ?></div>
                    <div class="stat-label">Software Attivi</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number"><?= $stats['bundles'] ?></div>
                    <div class="stat-label">Bundle Attivi</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number"><?= $stats['licenses'] ?></div>
                    <div class="stat-label">Licenze Totali</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number text-success"><?= $stats['active_licenses'] ?></div>
                    <div class="stat-label">Licenze Attive</div>
                </div>
            </div>
        </div>

        <?php if ($stats['pending_requests'] > 0): ?>
            <div class="alert alert-info">
                <i class="bi bi-envelope me-2"></i>Hai <strong><?= $stats['pending_requests'] ?></strong> richiesta/e di modifica dati in sospeso.
                <a href="<?= SITE_URL ?>/admin/requests/">Gestisci</a>
            </div>
        <?php endif; ?>

        <!-- ULTIMI ORDINI -->
        <h4 class="fw-bold mb-3">Ultimi Ordini</h4>
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-admin">
                        <tr><th>#</th><th>Cliente</th><th>Email</th><th>Totale</th><th>Stato</th><th>Data</th><th></th></tr>
                    </thead>
                    <tbody>
                        <?php while ($o = mysqli_fetch_assoc($recentOrders)): ?>
                            <tr>
                                <td class="fw-bold">#<?= $o['id'] ?></td>
                                <td><?= h($o['first_name'] . ' ' . $o['last_name']) ?></td>
                                <td class="small"><?= h($o['email']) ?></td>
                                <td class="fw-bold"><?= formatPrice($o['total']) ?></td>
                                <td><span class="badge badge-<?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span></td>
                                <td class="small"><?= date('d/m/Y H:i', strtotime($o['created_at'])) ?></td>
                                <td><a href="<?= SITE_URL ?>/admin/orders/view.php?id=<?= $o['id'] ?>" class="btn btn-sm btn-outline-primary">Vedi</a></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>