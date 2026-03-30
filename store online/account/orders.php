<?php
/**
 * AirDirector Store - I miei Ordini
 */
require_once __DIR__ . '/../functions.php';
requireLogin();

$conn = getDBConnection();
$userId = (int)$_SESSION['user_id'];

// Dettaglio singolo ordine
if (isset($_GET['id'])) {
    $orderId = (int)$_GET['id'];
    $orderR = mysqli_query($conn, "SELECT * FROM orders WHERE id = $orderId AND user_id = $userId");
    $order = mysqli_fetch_assoc($orderR);
    
    if (!$order) {
        header('Location: ' . SITE_URL . '/account/orders.php');
        exit;
    }
    
    $itemsR = mysqli_query($conn, "SELECT * FROM order_items WHERE order_id = $orderId");
    $licensesR = mysqli_query($conn, "SELECT l.*, s.name as software_name FROM licenses l JOIN software s ON s.id = l.software_id WHERE l.order_item_id IN (SELECT id FROM order_items WHERE order_id = $orderId) AND l.user_id = $userId");
    
    define('PAGE_TITLE', 'Ordine #' . $orderId);
    include __DIR__ . '/../includes/header.php';
    ?>
    
    <div class="container py-4">
        <a href="<?= SITE_URL ?>/account/orders.php" class="btn btn-outline-secondary mb-3"><i class="bi bi-arrow-left me-1"></i>Torna agli ordini</a>
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="section-title mb-0">Ordine #<?= $orderId ?></h1>
            <span class="badge badge-<?= $order['status'] ?> fs-6"><?= ucfirst($order['status']) ?></span>
        </div>
        
        <div class="row">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="fw-bold mb-3">Prodotti</h5>
                        <div class="table-responsive">
                            <table class="table">
                                <thead><tr><th>Prodotto</th><th>Qtà</th><th>Prezzo</th><th>Sconto</th><th>Totale</th></tr></thead>
                                <tbody>
                                    <?php while ($item = mysqli_fetch_assoc($itemsR)): ?>
                                        <tr>
                                            <td class="fw-bold"><?= h($item['item_name']) ?></td>
                                            <td><?= $item['quantity'] ?></td>
                                            <td><?= formatPrice($item['unit_price']) ?></td>
                                            <td><?= $item['discount_amount'] > 0 ? '-' . formatPrice($item['discount_amount']) : '-' ?></td>
                                            <td class="fw-bold"><?= formatPrice($item['line_total']) ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Licenze dell'ordine -->
                <?php if ($order['status'] === 'confirmed' && mysqli_num_rows($licensesR) > 0): ?>
                    <div class="card">
                        <div class="card-body">
                            <h5 class="fw-bold mb-3"><i class="bi bi-key me-2 text-success"></i>Licenze</h5>
                            <?php while ($lic = mysqli_fetch_assoc($licensesR)): ?>
                                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                    <div>
                                        <strong><?= h($lic['software_name']) ?></strong><br>
                                        <span class="serial-key"><?= h($lic['serial_key']) ?></span>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge <?= $lic['is_active'] ? 'badge-active' : 'badge-purchased' ?>">
                                            <?= $lic['is_active'] ? 'Attiva' : 'Non attivata' ?>
                                        </span>
                                        <br>
                                        <button class="btn btn-sm btn-outline-secondary mt-1 btn-copy-serial" data-serial="<?= h($lic['serial_key']) ?>">
                                            <i class="bi bi-clipboard"></i> Copia
                                        </button>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                <?php elseif ($order['status'] === 'pending'): ?>
                    <div class="alert alert-warning">
                        <i class="bi bi-hourglass-split me-2"></i>Le licenze saranno disponibili dopo la conferma dell'ordine.
                    </div>
                <?php endif; ?>
            </div>

            <div class="col-lg-4">
                <div class="cart-summary">
                    <h5 class="fw-bold mb-3">Riepilogo</h5>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotale</span>
                        <span><?= formatPrice($order['subtotal']) ?></span>
                    </div>
                    <?php if ($order['discount_amount'] > 0): ?>
                        <div class="d-flex justify-content-between mb-2 text-success">
                            <span>Sconti</span>
                            <span>-<?= formatPrice($order['discount_amount']) ?></span>
                        </div>
                    <?php endif; ?>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <strong class="fs-5">Totale</strong>
                        <strong class="fs-5 text-primary"><?= formatPrice($order['total']) ?></strong>
                    </div>
                    <div class="mt-3 small text-muted">
                        Ordine del <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php
    include __DIR__ . '/../includes/footer.php';
    exit;
}

// Lista ordini
define('PAGE_TITLE', 'I miei Ordini');
include __DIR__ . '/../includes/header.php';

$ordersR = mysqli_query($conn, "SELECT * FROM orders WHERE user_id = $userId ORDER BY created_at DESC");
?>

<div class="container py-4">
    <h1 class="section-title">I miei Ordini</h1>
    
    <?php $msg = flash('success'); if ($msg): ?>
        <div class="alert alert-success"><?= h($msg) ?></div>
    <?php endif; ?>
    
    <?php if (mysqli_num_rows($ordersR) === 0): ?>
        <div class="text-center py-5">
            <i class="bi bi-receipt text-muted" style="font-size: 4rem;"></i>
            <h4 class="mt-3 text-muted">Nessun ordine</h4>
            <a href="<?= SITE_URL ?>" class="btn btn-primary mt-2">Vai al catalogo</a>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-admin">
                        <tr><th>#</th><th>Data</th><th>Prodotti</th><th>Totale</th><th>Stato</th><th></th></tr>
                    </thead>
                    <tbody>
                        <?php while ($o = mysqli_fetch_assoc($ordersR)): ?>
                            <?php
                            $itemCountR = mysqli_query($conn, "SELECT SUM(quantity) as cnt FROM order_items WHERE order_id = " . (int)$o['id']);
                            $itemCount = mysqli_fetch_assoc($itemCountR)['cnt'] ?? 0;
                            ?>
                            <tr>
                                <td class="fw-bold">#<?= $o['id'] ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($o['created_at'])) ?></td>
                                <td><?= $itemCount ?> articolo/i</td>
                                <td class="fw-bold"><?= formatPrice($o['total']) ?></td>
                                <td><span class="badge badge-<?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span></td>
                                <td><a href="?id=<?= $o['id'] ?>" class="btn btn-sm btn-outline-primary">Dettagli</a></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>