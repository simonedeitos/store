<?php
require_once __DIR__ . '/../../functions.php';
requireAdmin();
$conn = getDBConnection();
$id = (int)($_GET['id'] ?? 0);
$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id = $id"));
if (!$user) { header('Location: ' . SITE_URL . '/admin/customers/'); exit; }

$ordersR = mysqli_query($conn, "SELECT * FROM orders WHERE user_id = $id ORDER BY created_at DESC");
$licensesR = mysqli_query($conn, "SELECT l.*, s.name as software_name FROM licenses l JOIN software s ON s.id = l.software_id WHERE l.user_id = $id ORDER BY l.created_at DESC");
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cliente: <?= h($user['first_name']) ?> - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= SITE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="d-flex">
    <?php include __DIR__ . '/../../includes/admin_sidebar.php'; ?>
    <div class="admin-content">
        <a href="<?= SITE_URL ?>/admin/customers/" class="btn btn-outline-secondary mb-3"><i class="bi bi-arrow-left me-1"></i>Indietro</a>
        <h1 class="fw-bold mb-4"><?= h($user['first_name'] . ' ' . $user['last_name']) ?></h1>
        <div class="row">
            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="fw-bold mb-3">Dati</h5>
                        <table class="table table-sm table-borderless">
                            <tr><td class="text-muted">Email</td><td><?= h($user['email']) ?></td></tr>
                            <tr><td class="text-muted">Azienda</td><td><?= h($user['company']) ?: '-' ?></td></tr>
                            <tr><td class="text-muted">Indirizzo</td><td><?= nl2br(h($user['billing_address'])) ?></td></tr>
                            <tr><td class="text-muted">P.IVA</td><td><?= h($user['vat_id']) ?: '-' ?></td></tr>
                            <tr><td class="text-muted">Registrato</td><td><?= date('d/m/Y H:i', strtotime($user['created_at'])) ?></td></tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="fw-bold mb-3">Ordini</h5>
                        <table class="table table-sm">
                            <thead><tr><th>#</th><th>Totale</th><th>Stato</th><th>Data</th><th></th></tr></thead>
                            <tbody>
                                <?php while ($o = mysqli_fetch_assoc($ordersR)): ?>
                                    <tr>
                                        <td>#<?= $o['id'] ?></td>
                                        <td class="fw-bold"><?= formatPrice($o['total']) ?></td>
                                        <td><span class="badge badge-<?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span></td>
                                        <td class="small"><?= date('d/m/Y', strtotime($o['created_at'])) ?></td>
                                        <td><a href="<?= SITE_URL ?>/admin/orders/view.php?id=<?= $o['id'] ?>" class="btn btn-sm btn-outline-primary">Vedi</a></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body">
                        <h5 class="fw-bold mb-3">Licenze</h5>
                        <table class="table table-sm">
                            <thead><tr><th>Software</th><th>Seriale</th><th>Attiva</th><th>Hardware</th></tr></thead>
                            <tbody>
                                <?php while ($l = mysqli_fetch_assoc($licensesR)): ?>
                                    <tr>
                                        <td><?= h($l['software_name']) ?></td>
                                        <td><span class="serial-key" style="font-size:0.8rem;"><?= h($l['serial_key']) ?></span></td>
                                        <td><?= $l['is_active'] ? '<i class="bi bi-check-circle-fill text-success"></i>' : '<i class="bi bi-circle text-muted"></i>' ?></td>
                                        <td class="small"><?= h($l['hardware_id']) ?: '-' ?></td>
                                    </tr>
                                <?php endwhile; ?>
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