<?php
/**
 * AirDirector Store - Login Cliente
 */
require_once __DIR__ . '/functions.php';

if (isLoggedIn()) {
    header('Location: ' . SITE_URL . '/account/');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email && $password) {
        $conn = getDBConnection();
        $emailEsc = dbEsc($email);
        $r = mysqli_query($conn, "SELECT * FROM users WHERE email = '$emailEsc'");
        $user = mysqli_fetch_assoc($r);

        if ($user && verifyPassword($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
            
            // Redirect a checkout se arriva da lì
            $redirect = $_SESSION['redirect_after_login'] ?? SITE_URL . '/account/';
            unset($_SESSION['redirect_after_login']);
            header('Location: ' . $redirect);
            exit;
        } else {
            $error = 'Email o password non corretti.';
        }
    } else {
        $error = 'Compila tutti i campi.';
    }
}

define('PAGE_TITLE', 'Accedi');
include __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card">
                <div class="card-body p-4">
                    <h2 class="text-center fw-bold mb-4"><i class="bi bi-box-arrow-in-right me-2"></i>Accedi</h2>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= h($error) ?></div>
                    <?php endif; ?>

                    <?php $msg = flash('success'); if ($msg): ?>
                        <div class="alert alert-success"><?= h($msg) ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" required value="<?= h($_POST['email'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 mb-3">Accedi</button>
                    </form>
                    
                    <div class="text-center">
                        <a href="<?= SITE_URL ?>/forgot_password.php" class="small">Password dimenticata?</a>
                        <hr>
                        <p class="mb-0">Non hai un account? <a href="<?= SITE_URL ?>/register.php">Registrati</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>