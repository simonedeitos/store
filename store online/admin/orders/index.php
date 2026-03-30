<?php
require_once __DIR__ . '/../../functions.php';
requireAdmin();
$conn = getDBConnection();

$statusFilter = $_GET['status'] ?? '';
$where = '';
if ($statusFilter && in_array($statusFilter, ['pending', 'confirmed', 'rejected', 'cancelled'])) {
    $where = "WHERE o.status = '" . dbEsc($statusFilter) . "'";
}

$ordersR = mysqli_query($conn, "SELECT o.*, u.first_name, u.last_name, u.email FROM orders o JOIN users u ON u.id = o.user_id $where ORDER BY o.created_at DESC");
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ordini - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= SITE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="d-flex">
    <?php include __DIR__ . '/../../includes/admin_sidebar.php'; ?>
    <div class="admin-content">
        <h1 class="fw-bold mb-4">Ordini</h1>
        
        <?php $msg = flash('success'); if ($msg): ?><div class="alert alert-success"><?= h($msg) ?></div><?php endif; ?>
        
        <div class="mb-3">
            <a href="?" class="btn btn-sm <?= !$statusFilter ? 'btn-primary' : 'btn-outline-primary' ?>">Tutti</a>
            <a href="?status=pending" class="btn btn-sm <?= $statusFilter === 'pending' ? 'btn-warning' : 'btn-outline-warning' ?>">In Attesa</a>
            <a href="?status=confirmed" class="btn btn-sm <?= $statusFilter === 'confirmed' ? 'btn-success' : 'btn-outline-success' ?>">Confermati</a>
            <a href="?status=rejected" class="btn btn-sm <?= $statusFilter === 'rejected' ? 'btn-danger' : 'btn-outline-danger' ?>">Rifiutati</a>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-admin">
                        <tr><th>#</th><th>Cliente</th><th>Email</th><th>Totale</th><th>Stato</th><th>Data</th><th></th></tr>
                    </thead>
                    <tbody>
                        <?php while ($o = mysqli_fetch_assoc($ordersR)): ?>
                            <tr>
                                <td class="fw-bold">#<?= $o['id'] ?></td>
                                <td><?= h($o['first_name'] . ' ' . $o['last_name']) ?></td>
                                <td class="small"><?= h($o['email']) ?></td>
                                <td class="fw-bold"><?= formatPrice($o['total']) ?></td>
                                <td><span class="badge badge-<?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span></td>
                                <td class="small"><?= date('d/m/Y H:i', strtotime($o['created_at'])) ?></td>
                                <td><a href="view.php?id=<?= $o['id'] ?>" class="btn btn-sm btn-outline-primary">Gestisci</a></td>
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