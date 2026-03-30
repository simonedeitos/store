<?php
/**
 * AirDirector Store - Admin Login
 */
require_once __DIR__ . '/../functions.php';

if (isAdmin()) {
    header('Location: ' . SITE_URL . '/admin/');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        $conn = getDBConnection();
        $usernameEsc = dbEsc($username);
        $r = mysqli_query($conn, "SELECT * FROM admins WHERE username = '$usernameEsc' OR email = '$usernameEsc'");
        $admin = mysqli_fetch_assoc($r);

        if ($admin && verifyPassword($password, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_name'] = $admin['username'];
            header('Location: ' . SITE_URL . '/admin/');
            exit;
        } else {
            $error = 'Credenziali non valide.';
        }
    } else {
        $error = 'Compila tutti i campi.';
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= SITE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body class="d-flex align-items-center justify-content-center" style="min-height: 100vh; background: linear-gradient(135deg, #0f172a 0%, #1e40af 100%);">
    <div class="col-md-4">
        <div class="card shadow-lg">
            <div class="card-body p-5">
                <div class="text-center mb-4">
                    <i class="bi bi-shield-lock-fill text-primary" style="font-size: 3rem;"></i>
                    <h3 class="fw-bold mt-2">Admin Panel</h3>
                    <p class="text-muted"><?= SITE_NAME ?></p>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= h($error) ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Username o Email</label>
                        <input type="text" name="username" class="form-control" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 btn-lg">Accedi</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>