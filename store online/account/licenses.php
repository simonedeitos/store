<?php
/**
 * AirDirector Store - Le mie Licenze
 */
require_once __DIR__ . '/../functions.php';
requireLogin();

$conn = getDBConnection();
$userId = (int)$_SESSION['user_id'];

// Azione sgancia licenza
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deactivate_license'])) {
    $licId = (int)$_POST['license_id'];
    mysqli_query($conn, "UPDATE licenses SET is_active = 0, hardware_id = NULL, activated_at = NULL WHERE id = $licId AND user_id = $userId");
    flash('success', 'Licenza disattivata. Ora puoi attivarla su un altro dispositivo.');
    header('Location: ' . SITE_URL . '/account/licenses.php');
    exit;
}

define('PAGE_TITLE', 'Le mie Licenze');
include __DIR__ . '/../includes/header.php';

$licensesR = mysqli_query($conn, "
    SELECT l.*, s.name as software_name, s.license_prefix, o.status as order_status
    FROM licenses l 
    JOIN software s ON s.id = l.software_id 
    LEFT JOIN order_items oi ON oi.id = l.order_item_id
    LEFT JOIN orders o ON o.id = oi.order_id
    WHERE l.user_id = $userId 
    ORDER BY l.created_at DESC
");
?>

<script>window.SITE_URL = '<?= SITE_URL ?>';</script>

<div class="container py-4">
    <h1 class="section-title">Le mie Licenze</h1>
    
    <?php $msg = flash('success'); if ($msg): ?>
        <div class="alert alert-success"><?= h($msg) ?></div>
    <?php endif; ?>
    
    <?php if (mysqli_num_rows($licensesR) === 0): ?>
        <div class="text-center py-5">
            <i class="bi bi-key text-muted" style="font-size: 4rem;"></i>
            <h4 class="mt-3 text-muted">Nessuna licenza</h4>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php while ($lic = mysqli_fetch_assoc($licensesR)): ?>
                <div class="col-md-6">
                    <div class="card h-100 <?= $lic['order_status'] !== 'confirmed' ? 'opacity-50' : '' ?>">
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2">
                                <h5 class="fw-bold mb-0"><?= h($lic['software_name']) ?></h5>
                                <span class="badge <?= $lic['is_active'] ? 'badge-active' : 'badge-purchased' ?>">
                                    <?= $lic['is_active'] ? 'Attiva' : 'Non attivata' ?>
                                </span>
                            </div>
                            
                            <?php if ($lic['order_status'] === 'confirmed'): ?>
                                <div class="mb-3">
                                    <span class="serial-key"><?= h($lic['serial_key']) ?></span>
                                    <button class="btn btn-sm btn-outline-secondary ms-2 btn-copy-serial" data-serial="<?= h($lic['serial_key']) ?>">
                                        <i class="bi bi-clipboard"></i>
                                    </button>
                                </div>
                                
                                <?php if ($lic['hardware_id']): ?>
                                    <small class="text-muted d-block mb-2">
                                        <i class="bi bi-pc-display me-1"></i>Hardware ID: <?= h($lic['hardware_id']) ?>
                                    </small>
                                <?php endif; ?>
                                
                                <?php if ($lic['activated_at']): ?>
                                    <small class="text-muted d-block mb-2">
                                        <i class="bi bi-calendar-check me-1"></i>Attivata: <?= date('d/m/Y H:i', strtotime($lic['activated_at'])) ?>
                                    </small>
                                <?php endif; ?>
                                
                                <?php if ($lic['is_active']): ?>
                                    <form method="POST" class="mt-2" onsubmit="return confirm('Vuoi disattivare questa licenza? Il software tornerà in modalità demo.')">
                                        <input type="hidden" name="license_id" value="<?= $lic['id'] ?>">
                                        <button type="submit" name="deactivate_license" class="btn btn-outline-warning btn-sm">
                                            <i class="bi bi-unlock me-1"></i>Sgancia Licenza
                                        </button>
                                    </form>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="alert alert-warning small mb-0">
                                    <i class="bi bi-hourglass-split me-1"></i>In attesa di conferma ordine
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>