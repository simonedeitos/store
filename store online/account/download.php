<?php
/**
 * AirDirector Store - Download Software Acquistato
 */
require_once __DIR__ . '/../functions.php';
requireLogin();

$conn = getDBConnection();
$userId = (int)$_SESSION['user_id'];

define('PAGE_TITLE', 'Download');
include __DIR__ . '/../includes/header.php';

// Software acquistati (ordini confermati)
$softwareR = mysqli_query($conn, "
    SELECT DISTINCT s.id, s.name, s.main_image, s.setup_download_url, s.demo_download_url
    FROM licenses l
    JOIN software s ON s.id = l.software_id
    JOIN order_items oi ON oi.id = l.order_item_id
    JOIN orders o ON o.id = oi.order_id
    WHERE l.user_id = $userId AND o.status = 'confirmed'
    ORDER BY s.name ASC
");
?>

<div class="container py-4">
    <h1 class="section-title">Download Software</h1>
    
    <?php if (mysqli_num_rows($softwareR) === 0): ?>
        <div class="text-center py-5">
            <i class="bi bi-download text-muted" style="font-size: 4rem;"></i>
            <h4 class="mt-3 text-muted">Nessun software disponibile per il download</h4>
            <p class="text-muted">I download saranno disponibili dopo la conferma dell'ordine.</p>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php while ($sw = mysqli_fetch_assoc($softwareR)): ?>
                <div class="col-md-4">
                    <div class="card h-100">
                        <?php if ($sw['main_image']): ?>
                            <img src="<?= UPLOADS_URL . h($sw['main_image']) ?>" class="card-img-top" alt="<?= h($sw['name']) ?>">
                        <?php endif; ?>
                        <div class="card-body text-center">
                            <h5 class="fw-bold"><?= h($sw['name']) ?></h5>
                            <?php if ($sw['setup_download_url']): ?>
                                <a href="<?= h($sw['setup_download_url']) ?>" class="btn btn-primary w-100 mt-2">
                                    <i class="bi bi-download me-2"></i>Scarica Setup
                                </a>
                            <?php else: ?>
                                <p class="text-muted small mt-2">Download non ancora disponibile</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>