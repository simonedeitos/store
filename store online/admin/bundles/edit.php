<?php
require_once __DIR__ . '/../../functions.php';
requireAdmin();
$conn = getDBConnection();
$id = (int)($_GET['id'] ?? 0);
$b = null;
if ($id) { $r = mysqli_query($conn, "SELECT * FROM bundles WHERE id = $id"); $b = mysqli_fetch_assoc($r); }
if (!$b) { header('Location: ' . SITE_URL . '/admin/bundles/'); exit; }

$error = '';
$allSoftware = mysqli_query($conn, "SELECT id, name FROM software ORDER BY name ASC");
$currentItems = [];
$ciR = mysqli_query($conn, "SELECT software_id FROM bundle_items WHERE bundle_id = $id");
while ($ci = mysqli_fetch_assoc($ciR)) $currentItems[] = $ci['software_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $slug = slugify($name);
    $desc = trim($_POST['description'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $fullPrice = (float)($_POST['full_price'] ?? 0);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $sortOrder = (int)($_POST['sort_order'] ?? 0);
    $softwareIds = $_POST['software_ids'] ?? [];
    $mainImage = $b['main_image'];

    if (!$name || empty($softwareIds)) { $error = 'Nome e almeno un software richiesti.'; }
    else {
        if (isset($_FILES['main_image']) && $_FILES['main_image']['size'] > 0) {
            if ($b['main_image']) deleteImage($b['main_image']);
            $upload = uploadImage($_FILES['main_image'], 'bundles');
            if ($upload['success']) $mainImage = 'bundles/' . basename($upload['path']);
        }
        $fp = $fullPrice > 0 ? $fullPrice : null;
        $stmt = mysqli_prepare($conn, "UPDATE bundles SET name=?, slug=?, description=?, price=?, full_price=?, main_image=?, is_active=?, sort_order=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, 'sssddsiis', $name, $slug, $desc, $price, $fp, $mainImage, $isActive, $sortOrder, $id);
        mysqli_stmt_execute($stmt);

        // Aggiorna items
        mysqli_query($conn, "DELETE FROM bundle_items WHERE bundle_id = $id");
        foreach ($softwareIds as $swId) {
            $swId = (int)$swId;
            mysqli_query($conn, "INSERT INTO bundle_items (bundle_id, software_id) VALUES ($id, $swId)");
        }
        flash('success', 'Bundle aggiornato!');
        header('Location: ' . SITE_URL . '/admin/bundles/');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifica Bundle - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= SITE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="d-flex">
    <?php include __DIR__ . '/../../includes/admin_sidebar.php'; ?>
    <div class="admin-content">
        <a href="<?= SITE_URL ?>/admin/bundles/" class="btn btn-outline-secondary mb-3"><i class="bi bi-arrow-left me-1"></i>Indietro</a>
        <h1 class="fw-bold mb-4">Modifica: <?= h($b['name']) ?></h1>
        <?php if ($error): ?><div class="alert alert-danger"><?= h($error) ?></div><?php endif; ?>
        <form method="POST" enctype="multipart/form-data">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="mb-3"><label class="form-label">Nome *</label><input type="text" name="name" class="form-control" required value="<?= h($b['name']) ?>"></div>
                            <div class="mb-3"><label class="form-label">Descrizione</label><textarea name="description" class="form-control" rows="3"><?= h($b['description']) ?></textarea></div>
                            <div class="mb-3">
                                <label class="form-label">Software Inclusi *</label>
                                <div class="border rounded p-3" style="max-height:250px;overflow-y:auto;">
                                    <?php while ($s = mysqli_fetch_assoc($allSoftware)): ?>
                                        <div class="form-check">
                                            <input type="checkbox" name="software_ids[]" value="<?= $s['id'] ?>" class="form-check-input" id="sw<?= $s['id'] ?>"
                                                <?= in_array($s['id'], $currentItems) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="sw<?= $s['id'] ?>"><?= h($s['name']) ?></label>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                            </div>
                            <div class="mb-3">
                                <?php if ($b['main_image']): ?>
                                    <img src="<?= UPLOADS_URL . h($b['main_image']) ?>" class="rounded mb-2" style="max-height:100px;">
                                <?php endif; ?>
                                <label class="form-label">Immagine</label>
                                <input type="file" name="main_image" class="form-control" accept="image/*">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="mb-3"><label class="form-label">Prezzo (€)</label><input type="number" name="price" class="form-control" step="0.01" value="<?= $b['price'] ?>"></div>
                            <div class="mb-3"><label class="form-label">Prezzo Pieno (€)</label><input type="number" name="full_price" class="form-control" step="0.01" value="<?= $b['full_price'] ?>"></div>
                            <div class="mb-3"><label class="form-label">Ordine</label><input type="number" name="sort_order" class="form-control" value="<?= $b['sort_order'] ?>"></div>
                            <div class="form-check mb-3">
                                <input type="checkbox" name="is_active" class="form-check-input" id="isActive" <?= $b['is_active'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="isActive">Attivo</label>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg w-100"><i class="bi bi-check-lg me-2"></i>Salva</button>
                </div>
            </div>
        </form>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>