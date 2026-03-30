<?php
require_once __DIR__ . '/../../functions.php';
requireAdmin();
$conn = getDBConnection();
$error = '';
$generated = null;

$allSoftware = mysqli_query($conn, "SELECT id, name, license_prefix FROM software ORDER BY name ASC");
$allUsers = mysqli_query($conn, "SELECT id, email, first_name, last_name FROM users ORDER BY last_name ASC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $softwareId = (int)($_POST['software_id'] ?? 0);
    $userId = (int)($_POST['user_id'] ?? 0); // 0 = nessun utente
    $qty = max(1, (int)($_POST['qty'] ?? 1));
    $notes = trim($_POST['notes'] ?? '');

    $swR = mysqli_query($conn, "SELECT license_prefix FROM software WHERE id = $softwareId");
    $sw = mysqli_fetch_assoc($swR);
    
    if (!$sw) {
        $error = 'Seleziona un software.';
    } else {
        $generated = [];
        for ($i = 0; $i < $qty; $i++) {
            $serial = generateUniqueSerial($sw['license_prefix']);
            $userIdVal = $userId > 0 ? $userId : 'NULL';
            $notesEsc = dbEsc($notes);
            mysqli_query($conn, "INSERT INTO licenses (user_id, software_id, serial_key, status, is_active, notes) VALUES ($userIdVal, $softwareId, '$serial', 'purchased', 0, '$notesEsc')");
            $generated[] = $serial;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Genera Licenza - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= SITE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="d-flex">
    <?php include __DIR__ . '/../../includes/admin_sidebar.php'; ?>
    <div class="admin-content">
        <a href="<?= SITE_URL ?>/admin/licenses/" class="btn btn-outline-secondary mb-3"><i class="bi bi-arrow-left me-1"></i>Indietro</a>
        <h1 class="fw-bold mb-4">Genera Licenza Manuale</h1>
        <?php if ($error): ?><div class="alert alert-danger"><?= h($error) ?></div><?php endif; ?>
        
        <?php if ($generated): ?>
            <div class="alert alert-success">
                <h5 class="fw-bold"><i class="bi bi-check-circle me-2"></i><?= count($generated) ?> licenza/e generata/e:</h5>
                <?php foreach ($generated as $serial): ?>
                    <div class="my-2"><span class="serial-key"><?= h($serial) ?></span></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="card" style="max-width:600px;">
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Software *</label>
                        <select name="software_id" class="form-select" required>
                            <option value="">Seleziona...</option>
                            <?php while ($s = mysqli_fetch_assoc($allSoftware)): ?>
                                <option value="<?= $s['id'] ?>">[<?= h($s['license_prefix']) ?>] <?= h($s['name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Assegna a Cliente <small class="text-muted">(opzionale)</small></label>
                        <select name="user_id" class="form-select">
                            <option value="0">Nessuno (uso personale)</option>
                            <?php while ($u = mysqli_fetch_assoc($allUsers)): ?>
                                <option value="<?= $u['id'] ?>"><?= h($u['last_name'] . ' ' . $u['first_name'] . ' - ' . $u['email']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Quantità</label>
                        <input type="number" name="qty" class="form-control" min="1" max="100" value="1">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Note</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Es: uso interno, omaggio, ecc."></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg w-100"><i class="bi bi-key me-2"></i>Genera</button>
                </form>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>