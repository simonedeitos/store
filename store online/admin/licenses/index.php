<?php
require_once __DIR__ . '/../../functions.php';
requireAdmin();
$conn = getDBConnection();

$search = trim($_GET['search'] ?? '');
$where = '';
if ($search) {
    $s = dbEsc($search);
    $where = "WHERE l.serial_key LIKE '%$s%' OR s.name LIKE '%$s%' OR u.email LIKE '%$s%'";
}

$licensesR = mysqli_query($conn, "SELECT l.*, s.name as software_name, u.email, u.first_name, u.last_name FROM licenses l JOIN software s ON s.id = l.software_id LEFT JOIN users u ON u.id = l.user_id $where ORDER BY l.created_at DESC LIMIT 200");
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Licenze - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= SITE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="d-flex">
    <?php include __DIR__ . '/../../includes/admin_sidebar.php'; ?>
    <div class="admin-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="fw-bold">Licenze</h1>
            <a href="generate.php" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Genera Licenza</a>
        </div>
        <?php $msg = flash('success'); if ($msg): ?><div class="alert alert-success"><?= h($msg) ?></div><?php endif; ?>
        
        <form class="mb-3 d-flex gap-2" method="GET">
            <input type="text" name="search" class="form-control" placeholder="Cerca seriale, software, email..." value="<?= h($search) ?>">
            <button class="btn btn-primary"><i class="bi bi-search"></i></button>
        </form>

        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0 table-sm">
                    <thead class="table-admin">
                        <tr><th>Seriale</th><th>Software</th><th>Cliente</th><th>Stato</th><th>Attiva</th><th>Hardware</th><th>Data</th></tr>
                    </thead>
                    <tbody>
                        <?php while ($l = mysqli_fetch_assoc($licensesR)): ?>
                            <tr>
                                <td><span class="serial-key" style="font-size:0.8rem;"><?= h($l['serial_key']) ?></span></td>
                                <td><?= h($l['software_name']) ?></td>
                                <td class="small"><?= $l['email'] ? h($l['first_name'] . ' ' . $l['last_name'] . ' (' . $l['email'] . ')') : '<em class="text-muted">Manuale</em>' ?></td>
                                <td><span class="badge badge-<?= $l['status'] ?>"><?= ucfirst($l['status']) ?></span></td>
                                <td><?= $l['is_active'] ? '<i class="bi bi-check-circle-fill text-success"></i>' : '<i class="bi bi-circle text-muted"></i>' ?></td>
                                <td class="small"><?= h($l['hardware_id']) ?: '-' ?></td>
                                <td class="small"><?= date('d/m/Y', strtotime($l['created_at'])) ?></td>
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