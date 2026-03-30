<?php
/**
 * AirDirector Store - Password Dimenticata
 */
require_once __DIR__ . '/functions.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if (!$email) {
        $error = 'Inserisci la tua email.';
    } else {
        $conn = getDBConnection();
        $emailEsc = dbEsc($email);
        $r = mysqli_query($conn, "SELECT * FROM users WHERE email = '$emailEsc'");
        $user = mysqli_fetch_assoc($r);
        
        if ($user) {
            $token = generateResetToken();
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            mysqli_query($conn, "UPDATE users SET reset_token = '$token', reset_expires = '$expires' WHERE id = " . (int)$user['id']);
            
            $resetLink = SITE_URL . "/reset_password.php?token=$token";
            
            // Invia email reset password tramite sistema template
            sendPasswordResetEmail($user, $resetLink);
        }
        
        // Mostra sempre successo per non rivelare se l'email esiste
        $success = 'Se l\'email è registrata, riceverai un link per reimpostare la password.';
    }
}

define('PAGE_TITLE', 'Password Dimenticata');
include __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card">
                <div class="card-body p-4">
                    <h2 class="text-center fw-bold mb-4"><i class="bi bi-key me-2"></i>Reset Password</h2>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= h($error) ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?= h($success) ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Email del tuo account</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Invia Link di Reset</button>
                    </form>
                    
                    <div class="text-center mt-3">
                        <a href="<?= SITE_URL ?>/login.php">Torna al login</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>