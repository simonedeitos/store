<?php
/**
 * AirDirector Store - Admin: Dettaglio Ordine
 */
require_once __DIR__ . '/../../functions.php';
requireAdmin();
$conn = getDBConnection();
$id = (int)($_GET['id'] ?? 0);

$order = mysqli_fetch_assoc(mysqli_query($conn, "SELECT o.*, u.first_name, u.last_name, u.email, u.company, u.billing_address, u.vat_id FROM orders o JOIN users u ON u.id = o.user_id WHERE o.id = $id"));
if (!$order) { header('Location: ' . SITE_URL . '/admin/orders/'); exit; }

// Azioni
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'confirm') {
        mysqli_query($conn, "UPDATE orders SET status = 'confirmed' WHERE id = $id");

        // Activate pending subscriptions for this user and sync station to client DB
        $pendingSubs = mysqli_query($conn, "SELECT cs.* FROM client_subscriptions cs WHERE cs.user_id = " . (int)$order['user_id'] . " AND cs.status = 'pending'");
        while ($pSub = mysqli_fetch_assoc($pendingSubs)) {
            $pSubId = (int)$pSub['id'];
            mysqli_query($conn, "UPDATE client_subscriptions SET status = 'active', started_at = NOW() WHERE id = $pSubId");

            // Sync station to client DB
            $clientConn = mysqli_connect(CLIENT_DB_HOST, CLIENT_DB_USER, CLIENT_DB_PASS, CLIENT_DB_NAME, CLIENT_DB_PORT);
            if ($clientConn) {
                mysqli_set_charset($clientConn, 'utf8mb4');
                $cToken  = mysqli_real_escape_string($clientConn, $pSub['station_token']);
                $cName   = mysqli_real_escape_string($clientConn, $pSub['radio_name']);
                $cUserId = (int)$pSub['user_id'];
                mysqli_query($clientConn, "
                    INSERT INTO stations (store_subscription_id, store_user_id, station_name, token, is_active)
                    VALUES ($pSubId, $cUserId, '$cName', '$cToken', 1)
                    ON DUPLICATE KEY UPDATE station_name='$cName', is_active=1
                ");
                mysqli_close($clientConn);
            }
        }
        
        // Prepara dati email
        $orderData = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM orders WHERE id = $id"));
        $userData = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id = " . (int)$order['user_id']));
        
        // Costruisci HTML licenze e download
        $licHtml = '';
        $dlHtml = '';
        $seenSw = [];
        $licsR = mysqli_query($conn, "
            SELECT l.serial_key, s.name, s.setup_download_url 
            FROM licenses l 
            JOIN software s ON s.id = l.software_id 
            WHERE l.order_item_id IN (SELECT id FROM order_items WHERE order_id = $id)
        ");
        while ($lc = mysqli_fetch_assoc($licsR)) {
            $licHtml .= '<p style="margin:0 0 8px;"><strong>' . htmlspecialchars($lc['name']) . ':</strong> <code style="font-size:14px;letter-spacing:1px;background:#e2e8f0;padding:2px 8px;border-radius:4px;">' . htmlspecialchars($lc['serial_key']) . '</code></p>';
            if ($lc['setup_download_url'] && !in_array($lc['name'], $seenSw)) {
                $dlHtml .= '<p style="margin:0 0 8px;"><a href="' . htmlspecialchars($lc['setup_download_url']) . '" style="color:#2563eb;text-decoration:none;">📥 Scarica ' . htmlspecialchars($lc['name']) . '</a></p>';
                $seenSw[] = $lc['name'];
            }
        }
        
        // Invia email al cliente con licenze e download
        sendOrderConfirmedEmail($userData, $orderData, $licHtml, $dlHtml);
        
        flash('success', 'Ordine #' . $id . ' confermato! Email con licenze inviata al cliente.');
        
    } elseif ($action === 'reject') {
        mysqli_query($conn, "UPDATE orders SET status = 'rejected' WHERE id = $id");
        
        // Invia email rifiuto al cliente
        $orderData = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM orders WHERE id = $id"));
        $userData = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id = " . (int)$order['user_id']));
        sendOrderRejectedEmail($userData, $orderData);
        
        flash('success', 'Ordine #' . $id . ' rifiutato. Email inviata al cliente.');
    }
    
    header('Location: ' . SITE_URL . '/admin/orders/view.php?id=' . $id);
    exit;
}

$itemsR = mysqli_query($conn, "SELECT * FROM order_items WHERE order_id = $id");
$licensesR = mysqli_query($conn, "SELECT l.*, s.name as software_name FROM licenses l JOIN software s ON s.id = l.software_id WHERE l.order_item_id IN (SELECT id FROM order_items WHERE order_id = $id)");
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ordine #<?= $id ?> - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= SITE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="d-flex">
    <?php include __DIR__ . '/../../includes/admin_sidebar.php'; ?>
    <div class="admin-content">
        <a href="<?= SITE_URL ?>/admin/orders/" class="btn btn-outline-secondary mb-3"><i class="bi bi-arrow-left me-1"></i>Indietro</a>
        
        <?php $msg = flash('success'); if ($msg): ?><div class="alert alert-success"><?= h($msg) ?></div><?php endif; ?>
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="fw-bold">Ordine #<?= $id ?></h1>
            <div>
                <span class="badge badge-<?= $order['status'] ?> fs-6"><?= ucfirst($order['status']) ?></span>
                <?php if ($order['status'] === 'pending'): ?>
                    <form method="POST" class="d-inline ms-2">
                        <button name="action" value="confirm" class="btn btn-success" onclick="return confirm('Confermare l\'ordine? Verrà inviata email al cliente con licenze e download.')">
                            <i class="bi bi-check-lg me-1"></i>Conferma
                        </button>
                        <button name="action" value="reject" class="btn btn-danger" onclick="return confirm('Rifiutare l\'ordine? Verrà inviata email al cliente.')">
                            <i class="bi bi-x-lg me-1"></i>Rifiuta
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <!-- Items -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="fw-bold mb-3">Prodotti</h5>
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

                <!-- Licenze -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="fw-bold mb-3">Licenze Generate</h5>
                        <?php if (mysqli_num_rows($licensesR) > 0): ?>
                            <table class="table table-sm">
                                <thead><tr><th>Software</th><th>Seriale</th><th>Stato</th><th>Hardware ID</th></tr></thead>
                                <tbody>
                                    <?php while ($lic = mysqli_fetch_assoc($licensesR)): ?>
                                        <tr>
                                            <td><?= h($lic['software_name']) ?></td>
                                            <td><span class="serial-key" style="font-size:0.85rem;"><?= h($lic['serial_key']) ?></span></td>
                                            <td><span class="badge <?= $lic['is_active'] ? 'badge-active' : 'badge-purchased' ?>"><?= $lic['is_active'] ? 'Attiva' : 'Non attivata' ?></span></td>
                                            <td class="small"><?= h($lic['hardware_id']) ?: '-' ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p class="text-muted">Nessuna licenza generata.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Cliente -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="fw-bold mb-3">Cliente</h5>
                        <p class="fw-bold mb-1"><?= h($order['first_name'] . ' ' . $order['last_name']) ?></p>
                        <p class="small mb-1"><?= h($order['email']) ?></p>
                        <?php if ($order['company']): ?><p class="small mb-1"><?= h($order['company']) ?></p><?php endif; ?>
                        <p class="small mb-1"><?= nl2br(h($order['billing_address'])) ?></p>
                        <?php if ($order['vat_id']): ?><p class="small mb-0">P.IVA: <?= h($order['vat_id']) ?></p><?php endif; ?>
                    </div>
                </div>

                <!-- Totali -->
                <div class="cart-summary">
                    <div class="d-flex justify-content-between mb-2"><span>Subtotale</span><span><?= formatPrice($order['subtotal']) ?></span></div>
                    <?php if ($order['discount_amount'] > 0): ?>
                        <div class="d-flex justify-content-between mb-2 text-success"><span>Sconti</span><span>-<?= formatPrice($order['discount_amount']) ?></span></div>
                    <?php endif; ?>
                    <hr>
                    <div class="d-flex justify-content-between"><strong class="fs-5">Totale</strong><strong class="fs-5 text-primary"><?= formatPrice($order['total']) ?></strong></div>
                    <div class="small text-muted mt-2"><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>