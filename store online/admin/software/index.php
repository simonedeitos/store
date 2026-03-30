<?php
require_once __DIR__ . '/../../functions.php';
requireAdmin();
$conn = getDBConnection();
$softwareR = mysqli_query($conn, "SELECT * FROM software ORDER BY sort_order ASC, name ASC");
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Software - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= SITE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="d-flex">
    <?php include __DIR__ . '/../../includes/admin_sidebar.php'; ?>
    <div class="admin-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="fw-bold">Software</h1>
            <a href="create.php" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Nuovo Software</a>
        </div>

        <?php $msg = flash('success'); if ($msg): ?>
            <div class="alert alert-success"><?= h($msg) ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-admin">
                        <tr><th>Img</th><th>Nome</th><th>Prefisso</th><th>Prezzo</th><th>Stato</th><th>Ordine</th><th>Azioni</th></tr>
                    </thead>
                    <tbody>
                        <?php while ($sw = mysqli_fetch_assoc($softwareR)): ?>
                            <tr>
                                <td>
                                    <?php if ($sw['main_image']): ?>
                                        <img src="<?= UPLOADS_URL . h($sw['main_image']) ?>" style="width:50px;height:35px;object-fit:cover;border-radius:4px;">
                                    <?php else: ?>
                                        <i class="bi bi-image text-muted"></i>
                                    <?php endif; ?>
                                </td>
                                <td class="fw-bold"><?= h($sw['name']) ?></td>
                                <td><code><?= h($sw['license_prefix']) ?></code></td>
                                <td><?= formatPrice($sw['price']) ?></td>
                                <td>
                                    <span class="badge <?= $sw['is_active'] ? 'bg-success' : 'bg-secondary' ?>">
                                        <?= $sw['is_active'] ? 'Attivo' : 'Disattivo' ?>
                                    </span>
                                </td>
                                <td><?= $sw['sort_order'] ?></td>
                                <td>
                                    <a href="edit.php?id=<?= $sw['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                                    <a href="delete.php?id=<?= $sw['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Eliminare questo software?')"><i class="bi bi-trash"></i></a>
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