<?php
/**
 * AirDirector Store - Registrazione Cliente
 */
require_once __DIR__ . '/functions.php';

if (isLoggedIn()) {
    header('Location: ' . SITE_URL . '/account/');
    exit;
}

$error = '';
$old = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = $_POST;
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $company = trim($_POST['company'] ?? '');
    $billingAddress = trim($_POST['billing_address'] ?? '');
    $vatId = trim($_POST['vat_id'] ?? '');

    if (!$email || !$password || !$firstName || !$lastName) {
        $error = 'Compila tutti i campi obbligatori.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email non valida.';
    } elseif (strlen($password) < 6) {
        $error = 'La password deve avere almeno 6 caratteri.';
    } elseif ($password !== $passwordConfirm) {
        $error = 'Le password non coincidono.';
    } else {
        $conn = getDBConnection();
        $emailEsc = dbEsc($email);
        $check = mysqli_query($conn, "SELECT id FROM users WHERE email = '$emailEsc'");
        
        if (mysqli_num_rows($check) > 0) {
            $error = 'Esiste già un account con questa email.';
        } else {
            $hash = hashPassword($password);
            $stmt = mysqli_prepare($conn, "INSERT INTO users (email, password, first_name, last_name, company, billing_address, vat_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, 'sssssss', $email, $hash, $firstName, $lastName, $company, $billingAddress, $vatId);
            
            if (mysqli_stmt_execute($stmt)) {
                $newUserId = mysqli_insert_id($conn);
                $_SESSION['user_id'] = $newUserId;
                $_SESSION['user_name'] = $firstName . ' ' . $lastName;
                
                // Invia email di benvenuto + notifica admin
                sendWelcomeEmail([
                    'email' => $email,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'company' => $company,
                    'vat_id' => $vatId
                ]);
                
                $redirect = $_SESSION['redirect_after_login'] ?? SITE_URL . '/account/';
                unset($_SESSION['redirect_after_login']);
                header('Location: ' . $redirect);
                exit;
            } else {
                $error = 'Errore durante la registrazione. Riprova.';
            }
        }
    }
}

define('PAGE_TITLE', 'Registrati');
include __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-7">
            <div class="card">
                <div class="card-body p-4">
                    <h2 class="text-center fw-bold mb-4"><i class="bi bi-person-plus me-2"></i>Registrati</h2>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= h($error) ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nome *</label>
                                <input type="text" name="first_name" class="form-control" required value="<?= h($old['first_name'] ?? '') ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Cognome *</label>
                                <input type="text" name="last_name" class="form-control" required value="<?= h($old['last_name'] ?? '') ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Email *</label>
                            <input type="email" name="email" class="form-control" required value="<?= h($old['email'] ?? '') ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Ragione Sociale</label>
                            <input type="text" name="company" class="form-control" value="<?= h($old['company'] ?? '') ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Indirizzo di Fatturazione *</label>
                            <textarea name="billing_address" class="form-control" rows="2" required><?= h($old['billing_address'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Partita IVA / VAT ID</label>
                            <input type="text" name="vat_id" class="form-control" value="<?= h($old['vat_id'] ?? '') ?>">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Password *</label>
                                <input type="password" name="password" class="form-control" required minlength="6">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Conferma Password *</label>
                                <input type="password" name="password_confirm" class="form-control" required>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100 btn-lg">Registrati</button>
                    </form>
                    
                    <div class="text-center mt-3">
                        <p class="mb-0">Hai già un account? <a href="<?= SITE_URL ?>/login.php">Accedi</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>