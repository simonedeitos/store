<?php
require_once __DIR__ . '/../../functions.php';
requireAdmin();
$conn = getDBConnection();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
    $mainImage = '';

    if (!$name || !$prefix) {
        $error = 'Nome e Prefisso Licenza sono obbligatori.';
    } else {
        // Upload immagine principale
        if (isset($_FILES['main_image']) && $_FILES['main_image']['size'] > 0) {
            $upload = uploadImage($_FILES['main_image'], 'main');
            if ($upload['success']) {
                $mainImage = 'main/' . basename($upload['path']);
            } else {
                $error = $upload['error'];
            }
        }

        if (!$error) {
            $stmt = mysqli_prepare($conn, "INSERT INTO software (name, slug, short_description, long_description, features, price, main_image, license_prefix, demo_download_url, setup_download_url, is_active, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, 'sssssdssssii', $name, $slug, $shortDesc, $longDesc, $features, $price, $mainImage, $prefix, $demoUrl, $setupUrl, $isActive, $sortOrder);
            
            if (mysqli_stmt_execute($stmt)) {
                $softwareId = mysqli_insert_id($conn);

                // Upload immagini gallery
                if (isset($_FILES['gallery_images'])) {
                    $files = $_FILES['gallery_images'];
                    for ($i = 0; $i < count($files['name']); $i++) {
                        if ($files['size'][$i] > 0) {
                            $file = [
                                'name' => $files['name'][$i],
                                'tmp_name' => $files['tmp_name'][$i],
                                'size' => $files['size'][$i],
                                'error' => $files['error'][$i]
                            ];
                            $upload = uploadImage($file, 'gallery');
                            if ($upload['success']) {
                                $path = 'gallery/' . basename($upload['path']);
                                mysqli_query($conn, "INSERT INTO software_images (software_id, image_path, sort_order) VALUES ($softwareId, '" . dbEsc($path) . "', $i)");
                            }
                        }
                    }
                }

                flash('success', 'Software "' . $name . '" creato con successo!');
                header('Location: ' . SITE_URL . '/admin/software/');
                exit;
            } else {
                $error = 'Errore: ' . mysqli_error($conn);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuovo Software - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= SITE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="d-flex">
    <?php include __DIR__ . '/../../includes/admin_sidebar.php'; ?>
    <div class="admin-content">
        <a href="<?= SITE_URL ?>/admin/software/" class="btn btn-outline-secondary mb-3"><i class="bi bi-arrow-left me-1"></i>Indietro</a>
        <h1 class="fw-bold mb-4">Nuovo Software</h1>
        
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
                                <input type="text" name="name" class="form-control" required value="<?= h($_POST['name'] ?? '') ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Descrizione Breve</label>
                                <textarea name="short_description" class="form-control" rows="2"><?= h($_POST['short_description'] ?? '') ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Descrizione Dettagliata</label>
                                <textarea name="long_description" class="form-control" rows="6"><?= h($_POST['long_description'] ?? '') ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Features <small class="text-muted">(una per riga)</small></label>
                                <textarea name="features" class="form-control" rows="5" placeholder="Feature 1&#10;Feature 2&#10;Feature 3"><?= h($_POST['features'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="fw-bold mb-3">Immagini</h5>
                            <div class="mb-3">
                                <label class="form-label">Immagine Principale</label>
                                <input type="file" name="main_image" class="form-control" id="imageUpload" accept="image/*">
                                <img id="imagePreview" class="mt-2 rounded" style="max-height:150px;display:none;">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Galleria <small class="text-muted">(selezione multipla)</small></label>
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
                                <label class="form-label">Prezzo (€) *</label>
                                <input type="number" name="price" class="form-control" step="0.01" min="0" required value="<?= h($_POST['price'] ?? '0') ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Prefisso Licenza * <small class="text-muted">(es: ADR)</small></label>
                                <input type="text" name="license_prefix" class="form-control" required maxlength="10" style="text-transform:uppercase" value="<?= h($_POST['license_prefix'] ?? '') ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Ordine visualizzazione</label>
                                <input type="number" name="sort_order" class="form-control" value="<?= h($_POST['sort_order'] ?? '0') ?>">
                            </div>
                            <div class="form-check mb-3">
                                <input type="checkbox" name="is_active" class="form-check-input" id="isActive" <?= isset($_POST['is_active']) || !$_POST ? 'checked' : '' ?>>
                                <label class="form-check-label" for="isActive">Attivo (visibile nel catalogo)</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="fw-bold mb-3">Download</h5>
                            <div class="mb-3">
                                <label class="form-label">URL Demo Download</label>
                                <input type="url" name="demo_download_url" class="form-control" placeholder="https://..." value="<?= h($_POST['demo_download_url'] ?? '') ?>">
                                <small class="text-muted">Pubblico, visibile a tutti</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">URL Setup Download</label>
                                <input type="url" name="setup_download_url" class="form-control" placeholder="https://..." value="<?= h($_POST['setup_download_url'] ?? '') ?>">
                                <small class="text-muted">Solo per chi ha acquistato</small>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg w-100">
                        <i class="bi bi-check-lg me-2"></i>Crea Software
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= SITE_URL ?>/assets/js/main.js"></script>
</body>
</html>