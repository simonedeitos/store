<?php
/**
 * AirDirector Store - Checkout
 */
require_once __DIR__ . '/functions.php';

$cartData = getCartDetails();
if (empty($cartData['items'])) {
    header('Location: ' . SITE_URL . '/cart.php');
    exit;
}

if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = SITE_URL . '/checkout.php';
    header('Location: ' . SITE_URL . '/login.php');
    exit;
}

$conn = getDBConnection();
$user = getCurrentUser();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $discountData = calculateDiscounts($cartData);
    $items = $discountData['items'];
    $subtotal = $discountData['subtotal'] + $discountData['discount'];
    $qtyDiscountTotal = $discountData['discount'];
    $coupon = $_SESSION['coupon'] ?? null;
    $couponDiscount = $_SESSION['coupon_discount'] ?? 0;
    $couponId = $coupon ? (int)$coupon['id'] : null;
    $totalDiscount = $qtyDiscountTotal + $couponDiscount;
    $total = max(0, $subtotal - $totalDiscount);

    $userId = (int)$_SESSION['user_id'];
    $stmt = mysqli_prepare($conn, "INSERT INTO orders (user_id, subtotal, discount_amount, coupon_id, total, status) VALUES (?, ?, ?, ?, ?, 'pending')");
    mysqli_stmt_bind_param($stmt, 'iddid', $userId, $subtotal, $totalDiscount, $couponId, $total);
    
    if (mysqli_stmt_execute($stmt)) {
        $orderId = mysqli_insert_id($conn);

        // Inserisci items e genera seriali
        foreach ($items as $item) {
            $softwareId = $item['type'] === 'software' ? (int)$item['id'] : null;
            $bundleId = $item['type'] === 'bundle' ? (int)$item['id'] : null;
            $itemName = $item['name'];
            $qty = (int)$item['qty'];
            $unitPrice = (float)$item['price'];
            $discountAmt = (float)($item['discount_amount'] ?? 0);
            $lineTotal = (float)$item['line_total'];

            $stmtItem = mysqli_prepare($conn, "INSERT INTO order_items (order_id, software_id, bundle_id, item_name, quantity, unit_price, discount_amount, line_total) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmtItem, 'iiisiddd', $orderId, $softwareId, $bundleId, $itemName, $qty, $unitPrice, $discountAmt, $lineTotal);
            mysqli_stmt_execute($stmtItem);
            $orderItemId = mysqli_insert_id($conn);

            if ($item['type'] === 'software') {
                $swR = mysqli_query($conn, "SELECT license_prefix FROM software WHERE id = $softwareId");
                $swData = mysqli_fetch_assoc($swR);
                $prefix = $swData['license_prefix'] ?? 'SW';

                for ($i = 0; $i < $qty; $i++) {
                    $serial = generateUniqueSerial($prefix);
                    $stmtLic = mysqli_prepare($conn, "INSERT INTO licenses (order_item_id, user_id, software_id, serial_key, status, is_active) VALUES (?, ?, ?, ?, 'purchased', 0)");
                    mysqli_stmt_bind_param($stmtLic, 'iiis', $orderItemId, $userId, $softwareId, $serial);
                    mysqli_stmt_execute($stmtLic);
                }
            } elseif ($item['type'] === 'bundle') {
                $bundleSwR = mysqli_query($conn, "SELECT s.id, s.license_prefix FROM bundle_items bi JOIN software s ON s.id = bi.software_id WHERE bi.bundle_id = $bundleId");
                while ($bsw = mysqli_fetch_assoc($bundleSwR)) {
                    $prefix = $bsw['license_prefix'] ?? 'SW';
                    for ($i = 0; $i < $qty; $i++) {
                        $serial = generateUniqueSerial($prefix);
                        $bswId = (int)$bsw['id'];
                        $stmtLic = mysqli_prepare($conn, "INSERT INTO licenses (order_item_id, user_id, software_id, serial_key, status, is_active) VALUES (?, ?, ?, ?, 'purchased', 0)");
                        mysqli_stmt_bind_param($stmtLic, 'iiis', $orderItemId, $userId, $bswId, $serial);
                        mysqli_stmt_execute($stmtLic);
                    }
                }
            }
        }

        // Aggiorna coupon usage
        if ($couponId) {
            mysqli_query($conn, "UPDATE coupons SET used_count = used_count + 1 WHERE id = $couponId");
        }

        // Svuota carrello e coupon
        clearCart();
        unset($_SESSION['coupon'], $_SESSION['coupon_discount']);

        // Invia email ordine al cliente e notifica admin
        $order = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM orders WHERE id = $orderId"));
        $itemsHtml = '';
        $orderItemsR2 = mysqli_query($conn, "SELECT * FROM order_items WHERE order_id = $orderId");
        while ($oi = mysqli_fetch_assoc($orderItemsR2)) {
            $itemsHtml .= '<p style="margin:0 0 8px;color:#1e293b;">• ' . htmlspecialchars($oi['item_name']) . ' x' . $oi['quantity'] . ' - ' . formatPrice($oi['line_total']) . '</p>';
        }
        sendOrderPlacedEmail($user, $order, $itemsHtml);

        flash('success', 'Ordine #' . $orderId . ' inviato! È in attesa di conferma da parte dell\'amministratore.');
        header('Location: ' . SITE_URL . '/account/orders.php');
        exit;
    } else {
        $error = 'Errore durante la creazione dell\'ordine. Riprova.';
    }
}

// Calcola totali per la pagina
$discountData = calculateDiscounts($cartData);
$items = $discountData['items'];
$subtotalGross = $discountData['subtotal'] + $discountData['discount'];
$qtyDiscount = $discountData['discount'];
$couponDiscount = $_SESSION['coupon_discount'] ?? 0;
$total = max(0, $discountData['subtotal'] - $couponDiscount);

define('PAGE_TITLE', 'Checkout');
include __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <h1 class="section-title">Checkout</h1>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= h($error) ?></div>
    <?php endif; ?>

    <div class="row">
        <!-- DATI FATTURAZIONE -->
        <div class="col-lg-7">
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="fw-bold mb-3"><i class="bi bi-person me-2"></i>Dati di Fatturazione</h5>
                    <table class="table table-borderless mb-0">
                        <tr><td class="text-muted" style="width:40%">Nome</td><td class="fw-bold"><?= h($user['first_name'] . ' ' . $user['last_name']) ?></td></tr>
                        <tr><td class="text-muted">Email</td><td><?= h($user['email']) ?></td></tr>
                        <?php if ($user['company']): ?>
                            <tr><td class="text-muted">Ragione Sociale</td><td><?= h($user['company']) ?></td></tr>
                        <?php endif; ?>
                        <tr><td class="text-muted">Indirizzo</td><td><?= nl2br(h($user['billing_address'])) ?></td></tr>
                        <?php if ($user['vat_id']): ?>
                            <tr><td class="text-muted">P.IVA / VAT</td><td><?= h($user['vat_id']) ?></td></tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h5 class="fw-bold mb-3"><i class="bi bi-bag me-2"></i>Prodotti nell'ordine</h5>
                    <?php foreach ($items as $item): ?>
                        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <div>
                                <strong><?= h($item['name']) ?></strong>
                                <?php if ($item['type'] === 'bundle'): ?>
                                    <span class="badge bg-info ms-1">Bundle</span>
                                <?php endif; ?>
                                <br><small class="text-muted">Qtà: <?= $item['qty'] ?> × <?= formatPrice($item['price']) ?></small>
                                <?php if (($item['discount_amount'] ?? 0) > 0): ?>
                                    <br><small class="text-success">Sconto quantità: -<?= formatPrice($item['discount_amount']) ?></small>
                                <?php endif; ?>
                            </div>
                            <strong><?= formatPrice($item['line_total']) ?></strong>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- RIEPILOGO & CONFERMA -->
        <div class="col-lg-5">
            <div class="cart-summary">
                <h5 class="fw-bold mb-3">Riepilogo Ordine</h5>
                
                <div class="d-flex justify-content-between mb-2">
                    <span>Subtotale</span>
                    <span><?= formatPrice($subtotalGross) ?></span>
                </div>
                
                <?php if ($qtyDiscount > 0): ?>
                    <div class="d-flex justify-content-between mb-2 text-success">
                        <span>Sconto quantità</span>
                        <span>-<?= formatPrice($qtyDiscount) ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if ($couponDiscount > 0): ?>
                    <div class="d-flex justify-content-between mb-2 text-success">
                        <span>Coupon</span>
                        <span>-<?= formatPrice($couponDiscount) ?></span>
                    </div>
                <?php endif; ?>
                
                <hr>
                <div class="d-flex justify-content-between mb-3">
                    <strong class="fs-5">Totale</strong>
                    <strong class="fs-5 text-primary"><?= formatPrice($total) ?></strong>
                </div>

                <div class="alert alert-info small">
                    <i class="bi bi-info-circle me-1"></i>
                    Il tuo ordine verrà posto in stato <strong>"In attesa"</strong>. 
                    Riceverai le licenze e i link per il download dopo la conferma dell'ordine.
                </div>
                
                <form method="POST">
                    <button type="submit" class="btn btn-primary btn-lg w-100">
                        <i class="bi bi-check-circle me-2"></i>Conferma Ordine
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>