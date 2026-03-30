<?php
require_once __DIR__ . '/../../functions.php';
requireAdmin();
$conn = getDBConnection();
$bundlesR = mysqli_query($conn, "SELECT * FROM bundles ORDER BY sort_order ASC, name ASC");
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bundle - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= SITE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="d-flex">
    <?php include __DIR__ . '/../../includes/admin_sidebar.php'; ?>
    <div class="admin-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="fw-bold">Bundle</h1>
            <a href="create.php" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Nuovo Bundle</a>
        </div>
        <?php $msg = flash('success'); if ($msg): ?>
            <div class="alert alert-success"><?= h($msg) ?></div>
        <?php endif; ?>
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-admin">
                        <tr><th>Nome</th><th>Software Inclusi</th><th>Prezzo</th><th>Prezzo Pieno</th><th>Stato</th><th>Azioni</th></tr>
                    </thead>
                    <tbody>
                        <?php while ($b = mysqli_fetch_assoc($bundlesR)): ?>
                            <?php
                            $itemsR = mysqli_query($conn, "SELECT s.name FROM bundle_items bi JOIN software s ON s.id = bi.software_id WHERE bi.bundle_id = " . (int)$b['id']);
                            $names = [];
                            while ($item = mysqli_fetch_assoc($itemsR)) $names[] = $item['name'];
                            ?>
                            <tr>
                                <td class="fw-bold"><?= h($b['name']) ?></td>
                                <td class="small"><?= h(implode(', ', $names)) ?></td>
                                <td class="fw-bold"><?= formatPrice($b['price']) ?></td>
                                <td class="text-muted"><?= $b['full_price'] ? formatPrice($b['full_price']) : '-' ?></td>
                                <td><span class="badge <?= $b['is_active'] ? 'bg-success' : 'bg-secondary' ?>"><?= $b['is_active'] ? 'Attivo' : 'Disattivo' ?></span></td>
                                <td>
                                    <a href="edit.php?id=<?= $b['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                                    <a href="delete.php?id=<?= $b['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Eliminare?')"><i class="bi bi-trash"></i></a>
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