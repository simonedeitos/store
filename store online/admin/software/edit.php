<?php
require_once __DIR__ . '/../../functions.php';
requireAdmin();
$conn = getDBConnection();
$id = (int)($_GET['id'] ?? 0);
$sw = null;

if ($id) {
    $r = mysqli_query($conn, "SELECT * FROM software WHERE id = $id");
    $sw = mysqli_fetch_assoc($r);
}
if (!$sw) { header('Location: ' . SITE_URL . '/admin/software/'); exit; }

$error = '';
$galleryR = mysqli_query($conn, "SELECT * FROM software_images WHERE software_id = $id ORDER BY sort_order ASC");
$gallery = [];
while ($img = mysqli_fetch_assoc($galleryR)) $gallery[] = $img;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Elimina immagine gallery se richiesto
    if (isset($_POST['delete_gallery_image'])) {
        $imgId = (int)$_POST['delete_gallery_image'];
        $imgR = mysqli_query($conn, "SELECT image_path FROM software_images WHERE id = $imgId AND software_id = $id");
        $img = mysqli_fetch_assoc($imgR);
        if ($img) {
            deleteImage($img['image_path']);
            mysqli_query($conn, "DELETE FROM software_images WHERE id = $imgId");
        }
        header('Location: ' . SITE_URL . '/admin/software/edit.php?id=' . $id);
        exit;
    }

    $name = trim($_POST['name'] ?? '');
    $slug = slugify($name);
    $shortDesc = trim($_POST['short_description'] ?? '');
    $longDesc = trim($_POST['long_description'] ?? '');
    $features = trim($_POST['features'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $prefix = strtoupper(trim($_POST['license_prefix'] ?? ''));
    $demoUrl = trim($_POST['demo_download_url'] ?? '');
    $setupUrl = trim($_POST['setup_download_url'] ?? '');
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $sortOrder = (int)($_POST['sort_order'] ?? 0);
    $mainImage = $sw['main_image'];

    if (!$name || !$prefix) {
        $error = 'Nome e Prefisso sono obbligatori.';
    } else {
        // Upload nuova immagine principale
        if (isset($_FILES['main_image']) && $_FILES['main_image']['size'] > 0) {
            if ($sw['main_image']) deleteImage($sw['main_image']);
            $upload = uploadImage($_FILES['main_image'], 'main');
            if ($upload['success']) {
                $mainImage = 'main/' . basename($upload['path']);
            }
        }

        $stmt = mysqli_prepare($conn, "UPDATE software SET name=?, slug=?, short_description=?, long_description=?, features=?, price=?, main_image=?, license_prefix=?, demo_download_url=?, setup_download_url=?, is_active=?, sort_order=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, 'sssssdssssiis', $name, $slug, $shortDesc, $longDesc, $features, $price, $mainImage, $prefix, $demoUrl, $setupUrl, $isActive, $sortOrder, $id);
        mysqli_stmt_execute($stmt);

        // Upload nuove immagini gallery
        if (isset($_FILES['gallery_images'])) {
            $files = $_FILES['gallery_images'];
            $maxSort = count($gallery);
            for ($i = 0; $i < count($files['name']); $i++) {
                if ($files['size'][$i] > 0) {
                    $file = ['name' => $files['name'][$i], 'tmp_name' => $files['tmp_name'][$i], 'size' => $files['size'][$i], 'error' => $files['error'][$i]];
                    $upload = uploadImage($file, 'gallery');
                    if ($upload['success']) {
                        $path = 'gallery/' . basename($upload['path']);
                        $s = $maxSort + $i;
                        mysqli_query($conn, "INSERT INTO software_images (software_id, image_path, sort_order) VALUES ($id, '" . dbEsc($path) . "', $s)");
                    }
                }
            }
        }

        flash('success', 'Software aggiornato!');
        header('Location: ' . SITE_URL . '/admin/software/');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifica Software - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= SITE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="d-flex">
    <?php include __DIR__ . '/../../includes/admin_sidebar.php'; ?>
    <div class="admin-content">
        <a href="<?= SITE_URL ?>/admin/software/" class="btn btn-outline-secondary mb-3"><i class="bi bi-arrow-left me-1"></i>Indietro</a>
        <h1 class="fw-bold mb-4">Modifica: <?= h($sw['name']) ?></h1>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= h($error) ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="fw-bold mb-3">Informazioni</h5>
                            <div class="mb-3">
                                <label class="form-label">Nome *</label>
                                <input type="text" name="name" class="form-control" required value="<?= h($sw['name']) ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Descrizione Breve</label>
                                <textarea name="short_description" class="form-control" rows="2"><?= h($sw['short_description']) ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Descrizione Dettagliata</label>
                                <textarea name="long_description" class="form-control" rows="6"><?= h($sw['long_description']) ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Features (una per riga)</label>
                                <textarea name="features" class="form-control" rows="5"><?= h($sw['features']) ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="fw-bold mb-3">Immagini</h5>
                            <div class="mb-3">
                                <label class="form-label">Immagine Principale</label>
                                <?php if ($sw['main_image']): ?>
                                    <div class="mb-2">
                                        <img src="<?= UPLOADS_URL . h($sw['main_image']) ?>" class="rounded" style="max-height:120px;">
                                    </div>
                                <?php endif; ?>
                                <input type="file" name="main_image" class="form-control" accept="image/*">
                                <small class="text-muted">Lascia vuoto per mantenere l'immagine attuale</small>
                            </div>

                            <label class="form-label">Galleria attuale</label>
                            <?php if ($gallery): ?>
                                <div class="row g-2 mb-3">
                                    <?php foreach ($gallery as $img): ?>
                                        <div class="col-3 text-center">
                                            <img src="<?= UPLOADS_URL . h($img['image_path']) ?>" class="img-fluid rounded mb-1" style="max-height:80px;">
                                            <form method="POST" class="d-inline">
                                                <button type="submit" name="delete_gallery_image" value="<?= $img['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Eliminare?')">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted small">Nessuna immagine in galleria</p>
                            <?php endif; ?>

                            <div class="mb-3">
                                <label class="form-label">Aggiungi immagini alla galleria</label>
                                <input type="file" name="gallery_images[]" class="form-control" multiple accept="image/*">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="fw-bold mb-3">Impostazioni</h5>
                            <div class="mb-3">
                                <label class="form-label">Prezzo (€)</label>
                                <input type="number" name="price" class="form-control" step="0.01" value="<?= $sw['price'] ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Prefisso Licenza *</label>
                                <input type="text" name="license_prefix" class="form-control" required maxlength="10" style="text-transform:uppercase" value="<?= h($sw['license_prefix']) ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Ordine</label>
                                <input type="number" name="sort_order" class="form-control" value="<?= $sw['sort_order'] ?>">
                            </div>
                            <div class="form-check mb-3">
                                <input type="checkbox" name="is_active" class="form-check-input" id="isActive" <?= $sw['is_active'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="isActive">Attivo</label>
                            </div>
                        </div>
                    </div>
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="fw-bold mb-3">Download</h5>
                            <div class="mb-3">
                                <label class="form-label">URL Demo</label>
                                <input type="url" name="demo_download_url" class="form-control" value="<?= h($sw['demo_download_url']) ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">URL Setup</label>
                                <input type="url" name="setup_download_url" class="form-control" value="<?= h($sw['setup_download_url']) ?>">
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg w-100"><i class="bi bi-check-lg me-2"></i>Salva Modifiche</button>
                </div>
            </div>
        </form>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>