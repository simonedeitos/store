<?php
require_once __DIR__ . '/../../functions.php';
requireAdmin();
$conn = getDBConnection();
$success = '';
$error = '';
$testResult = '';

$settings = getEmailSettings();

// Salva settings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    $host = trim($_POST['smtp_host'] ?? '');
    $port = (int)($_POST['smtp_port'] ?? 587);
    $user = trim($_POST['smtp_username'] ?? '');
    $pass = trim($_POST['smtp_password'] ?? '');
    $enc = $_POST['smtp_encryption'] ?? 'tls';
    $fromEmail = trim($_POST['from_email'] ?? '');
    $fromName = trim($_POST['from_name'] ?? '');
    $replyEmail = trim($_POST['reply_to_email'] ?? '');
    $replyName = trim($_POST['reply_to_name'] ?? '');
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    if ($settings) {
        $stmt = mysqli_prepare($conn, "UPDATE email_settings SET smtp_host=?, smtp_port=?, smtp_username=?, smtp_password=?, smtp_encryption=?, from_email=?, from_name=?, reply_to_email=?, reply_to_name=?, is_active=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, 'sisssssssis', $host, $port, $user, $pass, $enc, $fromEmail, $fromName, $replyEmail, $replyName, $isActive, $settings['id']);
    } else {
        $stmt = mysqli_prepare($conn, "INSERT INTO email_settings (smtp_host, smtp_port, smtp_username, smtp_password, smtp_encryption, from_email, from_name, reply_to_email, reply_to_name, is_active) VALUES (?,?,?,?,?,?,?,?,?,?)");
        mysqli_stmt_bind_param($stmt, 'sissssssis', $host, $port, $user, $pass, $enc, $fromEmail, $fromName, $replyEmail, $replyName, $isActive);
    }
    
    if (mysqli_stmt_execute($stmt)) {
        $success = 'Impostazioni email salvate!';
        $settings = getEmailSettings();
    } else {
        $error = 'Errore nel salvataggio.';
    }
}

// Test email
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_email'])) {
    $testTo = trim($_POST['test_to'] ?? '');
    if ($testTo) {
        $result = sendEmail($testTo, 'Test', 'user_welcome', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => $testTo,
            'company' => 'Test Company'
        ]);
        $testResult = $result ? '✅ Email di test inviata con successo!' : '❌ Invio fallito. Controlla il Log Email per i dettagli.';
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Settings - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= SITE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="d-flex">
    <?php include __DIR__ . '/../../includes/admin_sidebar.php'; ?>
    <div class="admin-content">
        <h1 class="fw-bold mb-4"><i class="bi bi-envelope-at me-2"></i>Email Settings</h1>
        
        <?php if ($success): ?><div class="alert alert-success"><?= h($success) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?= h($error) ?></div><?php endif; ?>
        <?php if ($testResult): ?><div class="alert <?= str_contains($testResult, '✅') ? 'alert-success' : 'alert-danger' ?>"><?= $testResult ?></div><?php endif; ?>

        <div class="row">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="fw-bold mb-3">Configurazione SMTP</h5>
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-8 mb-3">
                                    <label class="form-label">SMTP Host</label>
                                    <input type="text" name="smtp_host" class="form-control" placeholder="smtp.gmail.com" value="<?= h($settings['smtp_host'] ?? '') ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Porta</label>
                                    <input type="number" name="smtp_port" class="form-control" value="<?= $settings['smtp_port'] ?? 587 ?>">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Username SMTP</label>
                                    <input type="text" name="smtp_username" class="form-control" value="<?= h($settings['smtp_username'] ?? '') ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Password SMTP</label>
                                    <input type="password" name="smtp_password" class="form-control" value="<?= h($settings['smtp_password'] ?? '') ?>">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Crittografia</label>
                                <select name="smtp_encryption" class="form-select">
                                    <option value="tls" <?= ($settings['smtp_encryption'] ?? '') === 'tls' ? 'selected' : '' ?>>TLS (porta 587)</option>
                                    <option value="ssl" <?= ($settings['smtp_encryption'] ?? '') === 'ssl' ? 'selected' : '' ?>>SSL (porta 465)</option>
                                    <option value="none" <?= ($settings['smtp_encryption'] ?? '') === 'none' ? 'selected' : '' ?>>Nessuna</option>
                                </select>
                            </div>
                            <hr>
                            <h5 class="fw-bold mb-3">Mittente</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email Mittente</label>
                                    <input type="email" name="from_email" class="form-control" placeholder="noreply@airdirector.app" value="<?= h($settings['from_email'] ?? '') ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Nome Mittente</label>
                                    <input type="text" name="from_name" class="form-control" value="<?= h($settings['from_name'] ?? 'AirDirector Store') ?>">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Reply-To Email <small class="text-muted">(opzionale)</small></label>
                                    <input type="email" name="reply_to_email" class="form-control" value="<?= h($settings['reply_to_email'] ?? '') ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Reply-To Nome</label>
                                    <input type="text" name="reply_to_name" class="form-control" value="<?= h($settings['reply_to_name'] ?? '') ?>">
                                </div>
                            </div>
                            <div class="form-check mb-3">
                                <input type="checkbox" name="is_active" class="form-check-input" id="isActive" <?= ($settings['is_active'] ?? 1) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="isActive"><strong>Invio email attivo</strong></label>
                            </div>
                            <button type="submit" name="save_settings" class="btn btn-primary btn-lg"><i class="bi bi-check-lg me-2"></i>Salva Impostazioni</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- TEST EMAIL -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="fw-bold mb-3"><i class="bi bi-send me-2 text-info"></i>Invia Email di Test</h5>
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Invia a</label>
                                <input type="email" name="test_to" class="form-control" placeholder="tua@email.com" required>
                            </div>
                            <button type="submit" name="test_email" class="btn btn-info w-100 text-white"><i class="bi bi-send me-1"></i>Invia Test</button>
                        </form>
                    </div>
                </div>

                <!-- GUIDE -->
                <div class="card">
                    <div class="card-body">
                        <h5 class="fw-bold mb-3"><i class="bi bi-info-circle me-2 text-warning"></i>Configurazioni Comuni</h5>
                        <div class="small">
                            <p class="fw-bold mb-1">Gmail:</p>
                            <p class="text-muted mb-2">Host: smtp.gmail.com | Porta: 587 | TLS<br>Usa "App Password" (non la password normale)</p>
                            <p class="fw-bold mb-1">Hostinger:</p>
                            <p class="text-muted mb-2">Host: smtp.hostinger.com | Porta: 465 | SSL</p>
                            <p class="fw-bold mb-1">Outlook/Office365:</p>
                            <p class="text-muted mb-0">Host: smtp.office365.com | Porta: 587 | TLS</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>