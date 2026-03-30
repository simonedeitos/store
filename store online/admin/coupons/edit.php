<?php
require_once __DIR__ . '/../../functions.php';
requireAdmin();
$conn = getDBConnection();
$id = (int)($_GET['id'] ?? 0);
$c = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM coupons WHERE id = $id"));
if (!$c) { header('Location: ' . SITE_URL . '/admin/coupons/'); exit; }
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = strtoupper(trim($_POST['code'] ?? ''));
    $type = $_POST['discount_type'] ?? 'percent';
    $value = (float)($_POST['discount_value'] ?? 0);
    $minAmount = (float)($_POST['min_amount'] ?? 0);
    $maxUses = (int)($_POST['max_uses'] ?? 0);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $expires = trim($_POST['expires_at'] ?? '') ?: null;

    $stmt = mysqli_prepare($conn, "UPDATE coupons SET code=?, discount_type=?, discount_value=?, min_amount=?, max_uses=?, is_active=?, expires_at=? WHERE id=?");
    mysqli_stmt_bind_param($stmt, 'ssddiisi', $code, $type, $value, $minAmount, $maxUses, $isActive, $expires, $id);
    if (mysqli_stmt_execute($stmt)) {
        flash('success', 'Coupon aggiornato!');
        header('Location: ' . SITE_URL . '/admin/coupons/');
        exit;
    } else {
        $error = 'Errore durante il salvataggio.';
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifica Coupon - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= SITE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="d-flex">
    <?php include __DIR__ . '/../../includes/admin_sidebar.php'; ?>
    <div class="admin-content">
        <a href="<?= SITE_URL ?>/admin/coupons/" class="btn btn-outline-secondary mb-3"><i class="bi bi-arrow-left me-1"></i>Indietro</a>
        <h1 class="fw-bold mb-4">Modifica Coupon: <?= h($c['code']) ?></h1>
        <?php if ($error): ?><div class="alert alert-danger"><?= h($error) ?></div><?php endif; ?>
        <div class="card" style="max-width:600px;">
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Codice *</label>
                        <input type="text" name="code" class="form-control" required style="text-transform:uppercase" value="<?= h($c['code']) ?>">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tipo sconto</label>
                            <select name="discount_type" class="form-select">
                                <option value="percent" <?= $c['discount_type'] === 'percent' ? 'selected' : '' ?>>Percentuale (%)</option>
                                <option value="fixed" <?= $c['discount_type'] === 'fixed' ? 'selected' : '' ?>>Fisso (€)</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Valore *</label>
                            <input type="number" name="discount_value" class="form-control" step="0.01" required value="<?= $c['discount_value'] ?>">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Importo minimo (€)</label>
                            <input type="number" name="min_amount" class="form-control" step="0.01" value="<?= $c['min_amount'] ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Max utilizzi <small>(0=illimitato)</small></label>
                            <input type="number" name="max_uses" class="form-control" value="<?= $c['max_uses'] ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Scadenza</label>
                        <input type="datetime-local" name="expires_at" class="form-control" value="<?= $c['expires_at'] ? date('Y-m-d\TH:i', strtotime($c['expires_at'])) : '' ?>">
                    </div>
                    <div class="form-check mb-3">
                        <input type="checkbox" name="is_active" class="form-check-input" id="isActive" <?= $c['is_active'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="isActive">Attivo</label>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">Utilizzato: <?= $c['used_count'] ?> volte</small>
                    </div>
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-check-lg me-2"></i>Salva Modifiche</button>
                </form>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>