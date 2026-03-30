<?php
/**
 * AirDirector Store - Reset Password
 */
require_once __DIR__ . '/functions.php';

$token = $_GET['token'] ?? '';
$error = '';
$success = '';
$validToken = false;

if ($token) {
    $conn = getDBConnection();
    $tokenEsc = dbEsc($token);
    $r = mysqli_query($conn, "SELECT * FROM users WHERE reset_token = '$tokenEsc' AND reset_expires > NOW()");
    $user = mysqli_fetch_assoc($r);
    if ($user) $validToken = true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';

    if (strlen($password) < 6) {
        $error = 'La password deve avere almeno 6 caratteri.';
    } elseif ($password !== $passwordConfirm) {
        $error = 'Le password non coincidono.';
    } else {
        $hash = hashPassword($password);
        $conn = getDBConnection();
        mysqli_query($conn, "UPDATE users SET password = '" . dbEsc($hash) . "', reset_token = NULL, reset_expires = NULL WHERE id = " . (int)$user['id']);
        flash('success', 'Password reimpostata con successo. Accedi con la nuova password.');
        header('Location: ' . SITE_URL . '/login.php');
        exit;
    }
}

define('PAGE_TITLE', 'Reimposta Password');
include __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card">
                <div class="card-body p-4">
                    <h2 class="text-center fw-bold mb-4">Nuova Password</h2>
                    
                    <?php if (!$validToken): ?>
                        <div class="alert alert-danger">Link non valido o scaduto. <a href="<?= SITE_URL ?>/forgot_password.php">Richiedi un nuovo link</a>.</div>
                    <?php else: ?>
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?= h($error) ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Nuova Password</label>
                                <input type="password" name="password" class="form-control" required minlength="6">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Conferma Password</label>
                                <input type="password" name="password_confirm" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Reimposta Password</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>