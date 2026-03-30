<?php
require_once __DIR__ . '/../../functions.php';
requireAdmin();
$conn = getDBConnection();
$admin = getCurrentAdmin();
$success = '';
$error = '';

// Rigenera API Key
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['regenerate_api_key'])) {
    $newKey = bin2hex(random_bytes(32));
    $existing = mysqli_query($conn, "SELECT id FROM api_settings LIMIT 1");
    if (mysqli_num_rows($existing) > 0) {
        $row = mysqli_fetch_assoc($existing);
        mysqli_query($conn, "UPDATE api_settings SET api_key = '$newKey' WHERE id = " . (int)$row['id']);
    } else {
        mysqli_query($conn, "INSERT INTO api_settings (api_key) VALUES ('$newKey')");
    }
    $success = 'Nuova API Key generata! Aggiorna la chiave nei tuoi software.';
}

// Cambio password admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_admin_password'])) {
    $current = $_POST['current_password'] ?? '';
    $newPass = $_POST['new_password'] ?? '';
    $confirmPass = $_POST['confirm_password'] ?? '';

    if (!verifyPassword($current, $admin['password'])) {
        $error = 'Password attuale non corretta.';
    } elseif (strlen($newPass) < 6) {
        $error = 'La nuova password deve avere almeno 6 caratteri.';
    } elseif ($newPass !== $confirmPass) {
        $error = 'Le password non coincidono.';
    } else {
        $hash = hashPassword($newPass);
        mysqli_query($conn, "UPDATE admins SET password = '" . dbEsc($hash) . "' WHERE id = " . (int)$admin['id']);
        $success = 'Password admin aggiornata!';
    }
}

$apiKey = getApiKey();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Impostazioni - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= SITE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="d-flex">
    <?php include __DIR__ . '/../../includes/admin_sidebar.php'; ?>
    <div class="admin-content">
        <h1 class="fw-bold mb-4">Impostazioni</h1>
        
        <?php if ($success): ?><div class="alert alert-success"><?= h($success) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?= h($error) ?></div><?php endif; ?>

        <div class="row">
            <!-- API KEY -->
            <div class="col-lg-6">
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="fw-bold mb-3"><i class="bi bi-key me-2 text-warning"></i>API Key per i Software</h5>
                        <p class="text-muted small">Questa chiave deve essere inclusa nell'header <code>X-API-Key</code> di ogni richiesta API dai tuoi software.</p>
                        
                        <div class="mb-3">
                            <label class="form-label">API Key attuale</label>
                            <div class="input-group">
                                <input type="text" class="form-control font-monospace" value="<?= h($apiKey) ?>" id="apiKeyField" readonly>
                                <button class="btn btn-outline-secondary" type="button" onclick="navigator.clipboard.writeText(document.getElementById('apiKeyField').value);this.innerHTML='<i class=\'bi bi-check\'></i> Copiato';">
                                    <i class="bi bi-clipboard"></i>
                                </button>
                            </div>
                        </div>
                        
                        <form method="POST">
                            <button type="submit" name="regenerate_api_key" class="btn btn-warning" onclick="return confirm('Rigenerare la API Key? Dovrai aggiornare la chiave in tutti i software!')">
                                <i class="bi bi-arrow-clockwise me-1"></i>Rigenera API Key
                            </button>
                        </form>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="fw-bold mb-3"><i class="bi bi-link-45deg me-2 text-info"></i>Endpoint API</h5>
                        <table class="table table-sm table-borderless mb-0">
                            <tr>
                                <td class="fw-bold">Verifica Licenza</td>
                                <td><code class="small">GET <?= SITE_URL ?>/api/license_check.php?serial=XXXX</code></td>
                            </tr>
                            <tr>
                                <td class="fw-bold">Attiva Licenza</td>
                                <td><code class="small">POST <?= SITE_URL ?>/api/license_activate.php</code></td>
                            </tr>
                            <tr>
                                <td class="fw-bold">Disattiva Licenza</td>
                                <td><code class="small">POST <?= SITE_URL ?>/api/license_deactivate.php</code></td>
                            </tr>
                        </table>
                        <p class="small text-muted mt-2 mb-0">Documentazione completa: <code>API_INSTRUCTIONS.md</code></p>
                    </div>
                </div>
            </div>

            <!-- CAMBIO PASSWORD ADMIN -->
            <div class="col-lg-6">
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="fw-bold mb-3"><i class="bi bi-shield-lock me-2 text-primary"></i>Cambia Password Admin</h5>
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Password attuale</label>
                                <input type="password" name="current_password" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Nuova password</label>
                                <input type="password" name="new_password" class="form-control" required minlength="6">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Conferma nuova password</label>
                                <input type="password" name="confirm_password" class="form-control" required>
                            </div>
                            <button type="submit" name="change_admin_password" class="btn btn-primary w-100">Aggiorna Password</button>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h5 class="fw-bold mb-3"><i class="bi bi-info-circle me-2 text-success"></i>Info Sistema</h5>
                        <table class="table table-sm table-borderless mb-0">
                            <tr><td class="text-muted">PHP Version</td><td><?= phpversion() ?></td></tr>
                            <tr><td class="text-muted">Server</td><td><?= h($_SERVER['SERVER_SOFTWARE'] ?? 'N/A') ?></td></tr>
                            <tr><td class="text-muted">Database</td><td><?= DB_NAME ?></td></tr>
                            <tr><td class="text-muted">Uploads Path</td><td class="small"><?= UPLOADS_PATH ?></td></tr>
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