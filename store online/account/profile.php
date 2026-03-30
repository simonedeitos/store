<?php
/**
 * AirDirector Store - Profilo
 */
require_once __DIR__ . '/../functions.php';
requireLogin();

$conn = getDBConnection();
$user = getCurrentUser();
$success = '';
$error = '';

// Cambio password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current = $_POST['current_password'] ?? '';
    $newPass = $_POST['new_password'] ?? '';
    $confirmPass = $_POST['confirm_password'] ?? '';

    if (!verifyPassword($current, $user['password'])) {
        $error = 'Password attuale non corretta.';
    } elseif (strlen($newPass) < 6) {
        $error = 'La nuova password deve avere almeno 6 caratteri.';
    } elseif ($newPass !== $confirmPass) {
        $error = 'Le password non coincidono.';
    } else {
        $hash = hashPassword($newPass);
        mysqli_query($conn, "UPDATE users SET password = '" . dbEsc($hash) . "' WHERE id = " . (int)$user['id']);
        $success = 'Password aggiornata con successo!';
        
        // Invia email conferma cambio password
        sendPasswordChangedEmail($user);
    }
}

// Richiesta modifica dati
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_change'])) {
    $field = dbEsc($_POST['field_name'] ?? '');
    $newValue = dbEsc($_POST['new_value'] ?? '');
    $oldValue = '';
    
    $fieldMap = [
        'first_name' => 'Nome',
        'last_name' => 'Cognome',
        'company' => 'Ragione Sociale',
        'billing_address' => 'Indirizzo Fatturazione',
        'vat_id' => 'Partita IVA'
    ];
    
    if (isset($fieldMap[$field])) {
        $oldValue = dbEsc($user[$field] ?? '');
        $userId = (int)$user['id'];
        mysqli_query($conn, "INSERT INTO profile_requests (user_id, field_name, old_value, new_value) VALUES ($userId, '$field', '$oldValue', '$newValue')");
        $success = 'Richiesta di modifica inviata! Verrà valutata dall\'amministratore.';
        
        // Notifica admin via email
        sendAdminProfileRequestEmail($user, $_POST['field_name'], $user[$_POST['field_name']] ?? '', $_POST['new_value']);
    }
}

define('PAGE_TITLE', 'Profilo');
include __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <h1 class="section-title">Il mio Profilo</h1>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?= h($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= h($error) ?></div>
    <?php endif; ?>

    <div class="row">
        <!-- DATI PROFILO -->
        <div class="col-lg-7">
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="fw-bold mb-3"><i class="bi bi-person me-2"></i>Dati Personali</h5>
                    <p class="text-muted small mb-3">Per modificare i dati di fatturazione, usa il modulo sottostante per inviare una richiesta.</p>
                    
                    <table class="table">
                        <tr><td class="text-muted fw-bold" style="width:35%">Email</td><td><?= h($user['email']) ?></td></tr>
                        <tr><td class="text-muted fw-bold">Nome</td><td><?= h($user['first_name']) ?></td></tr>
                        <tr><td class="text-muted fw-bold">Cognome</td><td><?= h($user['last_name']) ?></td></tr>
                        <tr><td class="text-muted fw-bold">Ragione Sociale</td><td><?= h($user['company']) ?: '-' ?></td></tr>
                        <tr><td class="text-muted fw-bold">Indirizzo Fatturazione</td><td><?= nl2br(h($user['billing_address'])) ?></td></tr>
                        <tr><td class="text-muted fw-bold">Partita IVA</td><td><?= h($user['vat_id']) ?: '-' ?></td></tr>
                        <tr><td class="text-muted fw-bold">Registrato il</td><td><?= date('d/m/Y', strtotime($user['created_at'])) ?></td></tr>
                    </table>
                </div>
            </div>

            <!-- Richiesta modifica -->
            <div class="card">
                <div class="card-body">
                    <h5 class="fw-bold mb-3"><i class="bi bi-pencil-square me-2"></i>Richiedi Modifica Dati</h5>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Campo da modificare</label>
                            <select name="field_name" class="form-select" required>
                                <option value="">Seleziona...</option>
                                <option value="first_name">Nome</option>
                                <option value="last_name">Cognome</option>
                                <option value="company">Ragione Sociale</option>
                                <option value="billing_address">Indirizzo Fatturazione</option>
                                <option value="vat_id">Partita IVA</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nuovo valore</label>
                            <textarea name="new_value" class="form-control" rows="2" required></textarea>
                        </div>
                        <button type="submit" name="request_change" class="btn btn-primary">Invia Richiesta</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- CAMBIO PASSWORD -->
        <div class="col-lg-5">
            <div class="card">
                <div class="card-body">
                    <h5 class="fw-bold mb-3"><i class="bi bi-shield-lock me-2"></i>Cambia Password</h5>
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
                        <button type="submit" name="change_password" class="btn btn-primary w-100">Aggiorna Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>