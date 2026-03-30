<?php
/**
 * AirDirector Store - Homepage / Catalogo
 */
require_once __DIR__ . '/functions.php';
define('PAGE_TITLE', 'Catalogo Software');
include __DIR__ . '/includes/header.php';

$conn = getDBConnection();

// Carica software attivi
$softwareResult = mysqli_query($conn, "SELECT * FROM software WHERE is_active = 1 ORDER BY sort_order ASC, name ASC");

// Carica bundle attivi
$bundleResult = mysqli_query($conn, "SELECT * FROM bundles WHERE is_active = 1 ORDER BY sort_order ASC, name ASC");
?>

<script>window.SITE_URL = '<?= SITE_URL ?>';</script>

<!-- HERO -->
<section class="software-hero text-center">
    <div class="container">
        <h1 class="display-5 fw-bold mb-3">AirDirector Software Store</h1>
        <p class="lead opacity-75">Soluzioni professionali per il tuo lavoro</p>
    </div>
</section>

<!-- SOFTWARE CATALOG -->
<section class="container mb-5">
    <h2 class="section-title">Software</h2>
    
    <?php if (mysqli_num_rows($softwareResult) === 0): ?>
        <p class="text-muted">Nessun software disponibile al momento.</p>
    <?php else: ?>
        <div class="row g-4">
            <?php while ($sw = mysqli_fetch_assoc($softwareResult)): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100">
                        <?php if ($sw['main_image']): ?>
                            <img src="<?= UPLOADS_URL . h($sw['main_image']) ?>" class="card-img-top" alt="<?= h($sw['name']) ?>">
                        <?php else: ?>
                            <div class="card-img-top d-flex align-items-center justify-content-center bg-light">
                                <i class="bi bi-box-seam text-muted" style="font-size: 4rem;"></i>
                            </div>
                        <?php endif; ?>
                        
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title fw-bold"><?= h($sw['name']) ?></h5>
                            <p class="card-text text-muted flex-grow-1"><?= h($sw['short_description']) ?></p>
                            
                            <?php if ($sw['features']): ?>
                                <ul class="features-list small mb-3">
                                    <?php foreach (array_slice(explode("\n", $sw['features']), 0, 4) as $feat): ?>
                                        <?php if (trim($feat)): ?>
                                            <li><?= h(trim($feat)) ?></li>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                            
                            <div class="d-flex justify-content-between align-items-center mt-auto">
                                <span class="price"><?= formatPrice($sw['price']) ?></span>
                                <div>
                                    <?php if ($sw['demo_download_url']): ?>
                                        <a href="<?= h($sw['demo_download_url']) ?>" class="btn btn-outline-secondary btn-sm me-1" title="Scarica Demo">
                                            <i class="bi bi-download"></i> Demo
                                        </a>
                                    <?php endif; ?>
                                    <button class="btn btn-primary btn-add-cart" data-type="software" data-id="<?= $sw['id'] ?>">
                                        <i class="bi bi-cart-plus"></i> Aggiungi
                                    </button>
                                </div>
                            </div>
                            
                            <a href="<?= SITE_URL ?>/software.php?slug=<?= h($sw['slug']) ?>" class="stretched-link-detail mt-2 text-center small text-primary">
                                Dettagli →
                            </a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>
</section>

<!-- BUNDLES -->
<section class="container mb-5" id="bundles">
    <h2 class="section-title">Software Bundle</h2>
    
    <?php if (mysqli_num_rows($bundleResult) === 0): ?>
        <p class="text-muted">Nessun bundle disponibile al momento.</p>
    <?php else: ?>
        <div class="row g-4">
            <?php while ($b = mysqli_fetch_assoc($bundleResult)): ?>
                <div class="col-md-6">
                    <div class="bundle-card">
                        <div class="row align-items-center">
                            <div class="col-md-4 text-center mb-3 mb-md-0">
                                <?php if ($b['main_image']): ?>
                                    <img src="<?= UPLOADS_URL . h($b['main_image']) ?>" class="img-fluid rounded" alt="<?= h($b['name']) ?>" style="max-height: 150px;">
                                <?php else: ?>
                                    <i class="bi bi-collection text-info" style="font-size: 4rem;"></i>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-8">
                                <h4 class="fw-bold mb-2"><?= h($b['name']) ?></h4>
                                <p class="opacity-75 mb-3"><?= h($b['description']) ?></p>
                                
                                <?php
                                // Mostra software inclusi
                                $bundleItemsR = mysqli_query($conn, "SELECT s.name FROM bundle_items bi JOIN software s ON s.id = bi.software_id WHERE bi.bundle_id = " . (int)$b['id']);
                                $included = [];
                                while ($bi = mysqli_fetch_assoc($bundleItemsR)) $included[] = $bi['name'];
                                ?>
                                <?php if ($included): ?>
                                    <p class="small opacity-75 mb-3">
                                        <i class="bi bi-check2-square me-1"></i>Include: <?= h(implode(', ', $included)) ?>
                                    </p>
                                <?php endif; ?>
                                
                                <div class="d-flex align-items-center gap-3">
                                    <div>
                                        <?php if ($b['full_price'] && $b['full_price'] > $b['price']): ?>
                                            <span class="price-old text-light"><?= formatPrice($b['full_price']) ?></span>
                                        <?php endif; ?>
                                        <span class="price" style="color: var(--accent);"><?= formatPrice($b['price']) ?></span>
                                    </div>
                                    <button class="btn btn-accent btn-add-cart" data-type="bundle" data-id="<?= $b['id'] ?>">
                                        <i class="bi bi-cart-plus"></i> Aggiungi
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>