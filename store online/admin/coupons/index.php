<?php
require_once __DIR__ . '/../../functions.php';
requireAdmin();
$conn = getDBConnection();
$couponsR = mysqli_query($conn, "SELECT * FROM coupons ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coupon - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= SITE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="d-flex">
    <?php include __DIR__ . '/../../includes/admin_sidebar.php'; ?>
    <div class="admin-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="fw-bold">Coupon</h1>
            <a href="create.php" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Nuovo Coupon</a>
        </div>
        <?php $msg = flash('success'); if ($msg): ?><div class="alert alert-success"><?= h($msg) ?></div><?php endif; ?>
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-admin">
                        <tr><th>Codice</th><th>Sconto</th><th>Min.</th><th>Usi</th><th>Scadenza</th><th>Stato</th><th>Azioni</th></tr>
                    </thead>
                    <tbody>
                        <?php while ($c = mysqli_fetch_assoc($couponsR)): ?>
                            <tr>
                                <td><code class="fw-bold"><?= h($c['code']) ?></code></td>
                                <td><?= $c['discount_type'] === 'percent' ? $c['discount_value'] . '%' : formatPrice($c['discount_value']) ?></td>
                                <td><?= $c['min_amount'] > 0 ? formatPrice($c['min_amount']) : '-' ?></td>
                                <td><?= $c['used_count'] ?>/<?= $c['max_uses'] ?: '∞' ?></td>
                                <td class="small"><?= $c['expires_at'] ? date('d/m/Y', strtotime($c['expires_at'])) : 'Mai' ?></td>
                                <td><span class="badge <?= $c['is_active'] ? 'bg-success' : 'bg-secondary' ?>"><?= $c['is_active'] ? 'Attivo' : 'Disattivo' ?></span></td>
                                <td>
                                    <a href="edit.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                                    <a href="delete.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Eliminare?')"><i class="bi bi-trash"></i></a>
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
</body>
</html>