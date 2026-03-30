<?php
require_once __DIR__ . '/../../functions.php';
requireAdmin();
$conn = getDBConnection();
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $notifR = mysqli_query($conn, "SELECT id, notification_key FROM admin_notifications");
    while ($n = mysqli_fetch_assoc($notifR)) {
        $enabled = isset($_POST['notif_' . $n['notification_key']]) ? 1 : 0;
        mysqli_query($conn, "UPDATE admin_notifications SET is_enabled = $enabled WHERE id = " . (int)$n['id']);
    }
    $success = 'Preferenze notifiche salvate!';
}

$notifications = mysqli_query($conn, "SELECT * FROM admin_notifications ORDER BY id ASC");
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifiche Admin - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= SITE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="d-flex">
    <?php include __DIR__ . '/../../includes/admin_sidebar.php'; ?>
    <div class="admin-content">
        <h1 class="fw-bold mb-4"><i class="bi bi-bell me-2"></i>Notifiche Admin</h1>
        <p class="text-muted mb-4">Scegli quali notifiche email ricevi come amministratore. Le email vengono inviate all'indirizzo dell'admin principale.</p>

        <?php if ($success): ?><div class="alert alert-success"><?= h($success) ?></div><?php endif; ?>

        <div class="card" style="max-width:700px;">
            <div class="card-body">
                <form method="POST">
                    <?php while ($n = mysqli_fetch_assoc($notifications)): ?>
                        <div class="d-flex justify-content-between align-items-center py-3 border-bottom">
                            <div>
                                <h6 class="fw-bold mb-1"><?= h($n['name']) ?></h6>
                                <p class="text-muted small mb-0"><?= h($n['description']) ?></p>
                            </div>
                            <div class="form-check form-switch">
                                <input type="checkbox" name="notif_<?= h($n['notification_key']) ?>" class="form-check-input" role="switch" style="width:3em;height:1.5em;" <?= $n['is_enabled'] ? 'checked' : '' ?>>
                            </div>
                        </div>
                    <?php endwhile; ?>

                    <button type="submit" class="btn btn-primary mt-4"><i class="bi bi-check-lg me-2"></i>Salva Preferenze</button>
                </form>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>