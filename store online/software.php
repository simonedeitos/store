<?php
/**
 * AirDirector Store - Dettaglio Software
 */
require_once __DIR__ . '/functions.php';

$conn = getDBConnection();
$slug = dbEsc($_GET['slug'] ?? '');
$sw = null;

if ($slug) {
    $r = mysqli_query($conn, "SELECT * FROM software WHERE slug = '$slug' AND is_active = 1");
    $sw = mysqli_fetch_assoc($r);
}

if (!$sw) {
    header('Location: ' . SITE_URL);
    exit;
}

define('PAGE_TITLE', $sw['name']);
include __DIR__ . '/includes/header.php';

// Carica immagini gallery
$imagesR = mysqli_query($conn, "SELECT * FROM software_images WHERE software_id = " . (int)$sw['id'] . " ORDER BY sort_order ASC");
$images = [];
while ($img = mysqli_fetch_assoc($imagesR)) $images[] = $img;
?>

<script>window.SITE_URL = '<?= SITE_URL ?>';</script>

<!-- HERO -->
<section class="software-hero">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="display-5 fw-bold mb-3"><?= h($sw['name']) ?></h1>
                <p class="lead opacity-75"><?= h($sw['short_description']) ?></p>
                <div class="d-flex align-items-center gap-3 mt-4">
                    <span class="price" style="color: var(--accent); font-size: 2rem;"><?= formatPrice($sw['price']) ?></span>
                    <button class="btn btn-accent btn-lg btn-add-cart" data-type="software" data-id="<?= $sw['id'] ?>">
                        <i class="bi bi-cart-plus me-2"></i>Aggiungi al Carrello
                    </button>
                </div>
                <div class="mt-3">
                    <?php if ($sw['demo_download_url']): ?>
                        <a href="<?= h($sw['demo_download_url']) ?>" class="btn btn-outline-light me-2">
                            <i class="bi bi-download me-1"></i>Scarica Demo
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-6 text-center mt-4 mt-md-0">
                <?php if ($sw['main_image']): ?>
                    <img src="<?= UPLOADS_URL . h($sw['main_image']) ?>" class="img-fluid rounded shadow" alt="<?= h($sw['name']) ?>" style="max-height: 350px;">
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<div class="container">
    <div class="row">
        <!-- CONTENT -->
        <div class="col-lg-8">
            <!-- Descrizione -->
            <div class="card mb-4">
                <div class="card-body">
                    <h3 class="fw-bold mb-3">Descrizione</h3>
                    <div class="long-desc"><?= nl2br(h($sw['long_description'])) ?></div>
                </div>
            </div>

            <!-- Gallery -->
            <?php if ($images): ?>
                <div class="card mb-4">
                    <div class="card-body">
                        <h3 class="fw-bold mb-3">Screenshot</h3>
                        <div class="row g-3 software-gallery">
                            <?php foreach ($images as $img): ?>
                                <div class="col-6 col-md-4">
                                    <img src="<?= UPLOADS_URL . h($img['image_path']) ?>" alt="Screenshot" class="img-fluid" 
                                         data-bs-toggle="modal" data-bs-target="#imgModal" 
                                         onclick="document.getElementById('modalImg').src=this.src">
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- SIDEBAR -->
        <div class="col-lg-4">
            <!-- Features -->
            <?php if ($sw['features']): ?>
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="fw-bold mb-3"><i class="bi bi-star me-2 text-warning"></i>Funzionalità</h5>
                        <ul class="features-list">
                            <?php foreach (explode("\n", $sw['features']) as $feat): ?>
                                <?php if (trim($feat)): ?>
                                    <li><?= h(trim($feat)) ?></li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Acquista -->
            <div class="card border-primary">
                <div class="card-body text-center">
                    <div class="price mb-3"><?= formatPrice($sw['price']) ?></div>
                    <button class="btn btn-primary btn-lg w-100 btn-add-cart" data-type="software" data-id="<?= $sw['id'] ?>">
                        <i class="bi bi-cart-plus me-2"></i>Aggiungi al Carrello
                    </button>
                    <?php if ($sw['demo_download_url']): ?>
                        <a href="<?= h($sw['demo_download_url']) ?>" class="btn btn-outline-secondary w-100 mt-2">
                            <i class="bi bi-download me-1"></i>Scarica Demo Gratuita
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal immagine -->
<div class="modal fade" id="imgModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content bg-transparent border-0">
            <div class="modal-body p-0">
                <img id="modalImg" src="" class="img-fluid rounded w-100" alt="Screenshot">
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>