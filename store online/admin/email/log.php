<?php
require_once __DIR__ . '/../../functions.php';
requireAdmin();
$conn = getDBConnection();

$statusFilter = $_GET['status'] ?? '';
$where = '';
if ($statusFilter === 'sent') $where = "WHERE status = 'sent'";
elseif ($statusFilter === 'failed') $where = "WHERE status = 'failed'";

$logsR = mysqli_query($conn, "SELECT * FROM email_log $where ORDER BY created_at DESC LIMIT 200");

// Svuota log
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_log'])) {
    mysqli_query($conn, "DELETE FROM email_log");
    header('Location: ' . SITE_URL . '/admin/email/log.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Email - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= SITE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="d-flex">
    <?php include __DIR__ . '/../../includes/admin_sidebar.php'; ?>
    <div class="admin-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="fw-bold"><i class="bi bi-clock-history me-2"></i>Log Email</h1>
            <form method="POST" class="d-inline">
                <button type="submit" name="clear_log" class="btn btn-outline-danger btn-sm" onclick="return confirm('Svuotare il log?')">
                    <i class="bi bi-trash me-1"></i>Svuota Log
                </button>
            </form>
        </div>

        <div class="mb-3">
            <a href="?" class="btn btn-sm <?= !$statusFilter ? 'btn-primary' : 'btn-outline-primary' ?>">Tutti</a>
            <a href="?status=sent" class="btn btn-sm <?= $statusFilter === 'sent' ? 'btn-success' : 'btn-outline-success' ?>">Inviati</a>
            <a href="?status=failed" class="btn btn-sm <?= $statusFilter === 'failed' ? 'btn-danger' : 'btn-outline-danger' ?>">Falliti</a>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0">
                    <thead class="table-admin">
                        <tr><th>Data</th><th>Destinatario</th><th>Oggetto</th><th>Template</th><th>Stato</th><th>Errore</th></tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($logsR) === 0): ?>
                            <tr><td colspan="6" class="text-center text-muted py-4">Nessun log</td></tr>
                        <?php endif; ?>
                        <?php while ($l = mysqli_fetch_assoc($logsR)): ?>
                            <tr>
                                <td class="small"><?= date('d/m/Y H:i:s', strtotime($l['created_at'])) ?></td>
                                <td class="small"><?= h($l['to_name'] ? $l['to_name'] . ' <' . $l['to_email'] . '>' : $l['to_email']) ?></td>
                                <td class="small fw-bold"><?= h($l['subject']) ?></td>
                                <td><code class="small"><?= h($l['template_key']) ?></code></td>
                                <td>
                                    <span class="badge <?= $l['status'] === 'sent' ? 'bg-success' : 'bg-danger' ?>"><?= ucfirst($l['status']) ?></span>
                                </td>
                                <td class="small text-danger"><?= h($l['error_message']) ?></td>
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