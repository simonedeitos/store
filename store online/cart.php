<?php
/**
 * AirDirector Store - Carrello
 */
require_once __DIR__ . '/functions.php';

// AJAX Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'add':
            $type = $_POST['type'] ?? '';
            $id = (int)($_POST['id'] ?? 0);
            $qty = max(1, (int)($_POST['qty'] ?? 1));
            if (in_array($type, ['software', 'bundle', 'subscription']) && $id > 0) {
                addToCart($type, $id, $qty);
                echo json_encode(['success' => true, 'cart_count' => getCartCount()]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Parametri non validi']);
            }
            exit;

        case 'update':
            $key = $_POST['key'] ?? '';
            $qty = (int)($_POST['qty'] ?? 0);
            updateCartQty($key, $qty);
            echo json_encode(['success' => true, 'cart_count' => getCartCount()]);
            exit;

        case 'remove':
            $key = $_POST['key'] ?? '';
            removeFromCart($key);
            echo json_encode(['success' => true, 'cart_count' => getCartCount()]);
            exit;

        case 'coupon':
            $code = trim($_POST['code'] ?? '');
            $cartData = getCartDetails();
            $discountData = calculateDiscounts($cartData);
            $result = applyCoupon($code, $discountData['subtotal']);
            if ($result['valid']) {
                $_SESSION['coupon'] = $result['coupon'];
                $_SESSION['coupon_discount'] = $result['discount'];
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => $result['error']]);
            }
            exit;

        case 'remove_coupon':
            unset($_SESSION['coupon'], $_SESSION['coupon_discount']);
            echo json_encode(['success' => true]);
            exit;
    }
    echo json_encode(['success' => false, 'error' => 'Azione non valida']);
    exit;
}

define('PAGE_TITLE', 'Carrello');
include __DIR__ . '/includes/header.php';

$cartData = getCartDetails();
$discountData = calculateDiscounts($cartData);
$items = $discountData['items'];
$subtotal = $discountData['subtotal'];
$qtyDiscount = $discountData['discount'];
$couponDiscount = $_SESSION['coupon_discount'] ?? 0;
$coupon = $_SESSION['coupon'] ?? null;
$total = max(0, $subtotal - $couponDiscount);
?>

<script>window.SITE_URL = '<?= SITE_URL ?>';</script>

<div class="container py-4">
    <h1 class="section-title">Carrello</h1>

    <?php if (empty($items)): ?>
        <div class="text-center py-5">
            <i class="bi bi-cart-x text-muted" style="font-size: 5rem;"></i>
            <h3 class="mt-3 text-muted">Il carrello è vuoto</h3>
            <a href="<?= SITE_URL ?>" class="btn btn-primary mt-3">Sfoglia il catalogo</a>
        </div>
    <?php else: ?>
        <div class="row">
            <!-- ITEMS -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body">
                        <?php foreach ($items as $item): ?>
                            <div class="cart-item">
                                <?php if ($item['type'] === 'subscription'): ?>
                                    <div style="width:80px;height:60px;" class="bg-primary bg-opacity-10 rounded d-flex align-items-center justify-content-center me-3">
                                        <i class="bi bi-broadcast text-primary fs-4"></i>
                                    </div>
                                <?php elseif ($item['image']): ?>
                                    <img src="<?= UPLOADS_URL . h($item['image']) ?>" alt="<?= h($item['name']) ?>">
                                <?php else: ?>
                                    <div style="width:80px;height:60px;" class="bg-light rounded d-flex align-items-center justify-content-center me-3">
                                        <i class="bi bi-box-seam text-muted"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="flex-grow-1">
                                    <h6 class="mb-1 fw-bold">
                                        <?= h($item['name']) ?>
                                        <?php if ($item['type'] === 'bundle'): ?>
                                            <span class="badge bg-info ms-1">Bundle</span>
                                        <?php endif; ?>
                                        <?php if ($item['type'] === 'subscription'): ?>
                                            <span class="badge bg-danger ms-1">Sottoscrizione</span>
                                        <?php endif; ?>
                                    </h6>
                                    <small class="text-muted"><?= formatPrice($item['price']) ?> cad.</small>
                                    <?php if (isset($item['discount_amount']) && $item['discount_amount'] > 0): ?>
                                        <br><small class="text-success">Sconto quantità: -<?= formatPrice($item['discount_amount']) ?></small>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="d-flex align-items-center gap-2">
                                    <?php if ($item['type'] === 'subscription'): ?>
                                        <span class="badge bg-primary">Sottoscrizione</span>
                                    <?php else: ?>
                                        <input type="number" class="form-control form-control-sm cart-qty-input" 
                                               style="width: 70px;" min="1" value="<?= $item['qty'] ?>" 
                                               data-key="<?= h($item['key']) ?>">
                                    <?php endif; ?>
                                    <strong><?= formatPrice($item['line_total']) ?></strong>
                                    <button class="btn btn-outline-danger btn-sm btn-remove-cart" data-key="<?= h($item['key']) ?>">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- SUMMARY -->
            <div class="col-lg-4">
                <div class="cart-summary">
                    <h5 class="fw-bold mb-3">Riepilogo</h5>
                    
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotale</span>
                        <span><?= formatPrice($subtotal + $qtyDiscount) ?></span>
                    </div>
                    
                    <?php if ($qtyDiscount > 0): ?>
                        <div class="d-flex justify-content-between mb-2 text-success">
                            <span>Sconto quantità</span>
                            <span>-<?= formatPrice($qtyDiscount) ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ($coupon): ?>
                        <div class="d-flex justify-content-between mb-2 text-success">
                            <span>
                                Coupon "<?= h($coupon['code']) ?>"
                                <button class="btn btn-sm btn-link text-danger p-0 ms-1 btn-remove-coupon" 
                                        onclick="fetch('<?= SITE_URL ?>/cart.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'action=remove_coupon'}).then(()=>location.reload())">
                                    <i class="bi bi-x-circle"></i>
                                </button>
                            </span>
                            <span>-<?= formatPrice($couponDiscount) ?></span>
                        </div>
                    <?php else: ?>
                        <!-- Coupon input -->
                        <div class="input-group mb-3 mt-3">
                            <input type="text" class="form-control form-control-sm" id="couponCode" placeholder="Codice coupon">
                            <button class="btn btn-outline-primary btn-sm" id="applyCoupon">Applica</button>
                        </div>
                    <?php endif; ?>

                    <hr>
                    <div class="d-flex justify-content-between mb-3">
                        <strong class="fs-5">Totale</strong>
                        <strong class="fs-5 text-primary"><?= formatPrice($total) ?></strong>
                    </div>
                    
                    <a href="<?= SITE_URL ?>/checkout.php" class="btn btn-primary w-100 btn-lg">
                        <i class="bi bi-credit-card me-2"></i>Procedi al Checkout
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>