<?php
require_once __DIR__ . '/../../functions.php';
requireAdmin();
$conn = getDBConnection();
$error = '';
$allSoftware = mysqli_query($conn, "SELECT id, name FROM software ORDER BY name ASC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $slug = slugify($name);
    $desc = trim($_POST['description'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $fullPrice = (float)($_POST['full_price'] ?? 0);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $sortOrder = (int)($_POST['sort_order'] ?? 0);
    $softwareIds = $_POST['software_ids'] ?? [];
    $mainImage = '';

    if (!$name) { $error = 'Nome obbligatorio.'; }
    elseif (empty($softwareIds)) { $error = 'Seleziona almeno un software.'; }
    else {
        if (isset($_FILES['main_image']) && $_FILES['main_image']['size'] > 0) {
            $upload = uploadImage($_FILES['main_image'], 'bundles');
            if ($upload['success']) $mainImage = 'bundles/' . basename($upload['path']);
        }

        $fp = $fullPrice > 0 ? $fullPrice : null;
        $stmt = mysqli_prepare($conn, "INSERT INTO bundles (name, slug, description, price, full_price, main_image, is_active, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'sssddsii', $name, $slug, $desc, $price, $fp, $mainImage, $isActive, $sortOrder);
        
        if (mysqli_stmt_execute($stmt)) {
            $bundleId = mysqli_insert_id($conn);
            foreach ($softwareIds as $swId) {
                $swId = (int)$swId;
                mysqli_query($conn, "INSERT INTO bundle_items (bundle_id, software_id) VALUES ($bundleId, $swId)");
            }
            flash('success', 'Bundle creato!');
            header('Location: ' . SITE_URL . '/admin/bundles/');
            exit;
        } else { $error = 'Errore: ' . mysqli_error($conn); }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuovo Bundle - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= SITE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="d-flex">
    <?php include __DIR__ . '/../../includes/admin_sidebar.php'; ?>
    <div class="admin-content">
        <a href="<?= SITE_URL ?>/admin/bundles/" class="btn btn-outline-secondary mb-3"><i class="bi bi-arrow-left me-1"></i>Indietro</a>
        <h1 class="fw-bold mb-4">Nuovo Bundle</h1>
        <?php if ($error): ?><div class="alert alert-danger"><?= h($error) ?></div><?php endif; ?>
        <form method="POST" enctype="multipart/form-data">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Nome *</label>
                                <input type="text" name="name" class="form-control" required value="<?= h($_POST['name'] ?? '') ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Descrizione</label>
                                <textarea name="description" class="form-control" rows="3"><?= h($_POST['description'] ?? '') ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Software Inclusi *</label>
                                <div class="border rounded p-3" style="max-height:250px;overflow-y:auto;">
                                    <?php while ($s = mysqli_fetch_assoc($allSoftware)): ?>
                                        <div class="form-check">
                                            <input type="checkbox" name="software_ids[]" value="<?= $s['id'] ?>" class="form-check-input" id="sw<?= $s['id'] ?>"
                                                <?= in_array($s['id'], $_POST['software_ids'] ?? []) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="sw<?= $s['id'] ?>"><?= h($s['name']) ?></label>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Immagine</label>
                                <input type="file" name="main_image" class="form-control" accept="image/*">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Prezzo Bundle (€) *</label>
                                <input type="number" name="price" class="form-control" step="0.01" required value="<?= h($_POST['price'] ?? '0') ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Prezzo Pieno (€) <small class="text-muted">(barrato)</small></label>
                                <input type="number" name="full_price" class="form-control" step="0.01" value="<?= h($_POST['full_price'] ?? '') ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Ordine</label>
                                <input type="number" name="sort_order" class="form-control" value="0">
                            </div>
                            <div class="form-check mb-3">
                                <input type="checkbox" name="is_active" class="form-check-input" id="isActive" checked>
                                <label class="form-check-label" for="isActive">Attivo</label>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg w-100"><i class="bi bi-check-lg me-2"></i>Crea Bundle</button>
                </div>
            </div>
        </form>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>