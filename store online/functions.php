<?php
/**
 * AirDirector Store - Funzioni Globali
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/email_functions.php';

// ============================================================
// AUTH HELPERS
// ============================================================

function isLoggedIn() {
    return isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0;
}

function isAdmin() {
    return isset($_SESSION['admin_id']) && $_SESSION['admin_id'] > 0;
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . SITE_URL . '/login.php');
        exit;
    }
}

function requireAdmin() {
    if (!isAdmin()) {
        header('Location: ' . SITE_URL . '/admin/login.php');
        exit;
    }
}

function getCurrentUser() {
    if (!isLoggedIn()) return null;
    $conn = getDBConnection();
    $id = (int)$_SESSION['user_id'];
    $r = mysqli_query($conn, "SELECT * FROM users WHERE id = $id");
    return mysqli_fetch_assoc($r);
}

function getCurrentAdmin() {
    if (!isAdmin()) return null;
    $conn = getDBConnection();
    $id = (int)$_SESSION['admin_id'];
    $r = mysqli_query($conn, "SELECT * FROM admins WHERE id = $id");
    return mysqli_fetch_assoc($r);
}

// ============================================================
// PASSWORD
// ============================================================

function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// ============================================================
// SERIAL KEY GENERATION
// ============================================================

function generateSerialKey($prefix) {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $block = function() use ($chars) {
        $b = '';
        for ($i = 0; $i < 4; $i++) {
            $b .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $b;
    };
    return strtoupper($prefix) . '-' . $block() . '-' . $block() . '-' . $block();
}

function generateUniqueSerial($prefix) {
    $conn = getDBConnection();
    $maxAttempts = 100;
    for ($i = 0; $i < $maxAttempts; $i++) {
        $serial = generateSerialKey($prefix);
        $escaped = dbEsc($serial);
        $r = mysqli_query($conn, "SELECT id FROM licenses WHERE serial_key = '$escaped'");
        if (mysqli_num_rows($r) === 0) {
            return $serial;
        }
    }
    return generateSerialKey($prefix) . '-' . time();
}

// ============================================================
// CART (SESSION BASED)
// ============================================================

function getCart() {
    return $_SESSION['cart'] ?? [];
}

function addToCart($type, $id, $qty = 1) {
    $key = $type . '_' . $id;
    if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
    if (isset($_SESSION['cart'][$key])) {
        $_SESSION['cart'][$key]['qty'] += $qty;
    } else {
        $_SESSION['cart'][$key] = [
            'type' => $type,
            'id' => (int)$id,
            'qty' => (int)$qty
        ];
    }
}

function updateCartQty($key, $qty) {
    if ($qty <= 0) {
        unset($_SESSION['cart'][$key]);
    } else {
        $_SESSION['cart'][$key]['qty'] = (int)$qty;
    }
}

function removeFromCart($key) {
    unset($_SESSION['cart'][$key]);
}

function clearCart() {
    $_SESSION['cart'] = [];
}

function getCartCount() {
    $count = 0;
    foreach (getCart() as $item) {
        $count += $item['qty'];
    }
    return $count;
}

function getCartDetails() {
    $conn = getDBConnection();
    $cart = getCart();
    $items = [];
    $subtotal = 0;

    foreach ($cart as $key => $item) {
        if ($item['type'] === 'software') {
            $id = (int)$item['id'];
            $r = mysqli_query($conn, "SELECT id, name, price, main_image, license_prefix FROM software WHERE id = $id AND is_active = 1");
            $sw = mysqli_fetch_assoc($r);
            if ($sw) {
                $lineTotal = $sw['price'] * $item['qty'];
                $items[] = [
                    'key' => $key,
                    'type' => 'software',
                    'id' => $sw['id'],
                    'name' => $sw['name'],
                    'price' => (float)$sw['price'],
                    'qty' => $item['qty'],
                    'image' => $sw['main_image'],
                    'line_total' => $lineTotal
                ];
                $subtotal += $lineTotal;
            }
        } elseif ($item['type'] === 'bundle') {
            $id = (int)$item['id'];
            $r = mysqli_query($conn, "SELECT id, name, price, full_price, main_image FROM bundles WHERE id = $id AND is_active = 1");
            $b = mysqli_fetch_assoc($r);
            if ($b) {
                $lineTotal = $b['price'] * $item['qty'];
                $items[] = [
                    'key' => $key,
                    'type' => 'bundle',
                    'id' => $b['id'],
                    'name' => $b['name'],
                    'price' => (float)$b['price'],
                    'full_price' => (float)$b['full_price'],
                    'qty' => $item['qty'],
                    'image' => $b['main_image'],
                    'line_total' => $lineTotal
                ];
                $subtotal += $lineTotal;
            }
        }
    }

    return ['items' => $items, 'subtotal' => $subtotal];
}

// ============================================================
// DISCOUNT CALCULATION
// ============================================================

function calculateDiscounts($cartDetails) {
    $conn = getDBConnection();
    $items = $cartDetails['items'];
    $totalDiscount = 0;

    foreach ($items as &$item) {
        $item['discount_amount'] = 0;
        if ($item['type'] === 'software' && $item['qty'] > 1) {
            $swId = (int)$item['id'];
            $qty = (int)$item['qty'];
            $r = mysqli_query($conn, "SELECT discount_percent FROM quantity_discounts WHERE software_id = $swId AND min_qty <= $qty ORDER BY min_qty DESC LIMIT 1");
            $d = mysqli_fetch_assoc($r);
            if ($d) {
                $discountedQty = $qty - 1;
                $discountAmount = ($item['price'] * $discountedQty) * ($d['discount_percent'] / 100);
                $item['discount_amount'] = $discountAmount;
                $item['line_total'] -= $discountAmount;
                $totalDiscount += $discountAmount;
            }
        }
    }
    unset($item);

    $subtotal = array_sum(array_column($items, 'line_total'));

    return [
        'items' => $items,
        'subtotal' => $subtotal,
        'discount' => $totalDiscount
    ];
}

function applyCoupon($code, $subtotal) {
    $conn = getDBConnection();
    $code = dbEsc($code);
    $r = mysqli_query($conn, "SELECT * FROM coupons WHERE code = '$code' AND is_active = 1");
    $coupon = mysqli_fetch_assoc($r);

    if (!$coupon) return ['valid' => false, 'error' => 'Coupon non valido'];
    if ($coupon['expires_at'] && strtotime($coupon['expires_at']) < time()) return ['valid' => false, 'error' => 'Coupon scaduto'];
    if ($coupon['max_uses'] > 0 && $coupon['used_count'] >= $coupon['max_uses']) return ['valid' => false, 'error' => 'Coupon esaurito'];
    if ($coupon['min_amount'] > 0 && $subtotal < $coupon['min_amount']) return ['valid' => false, 'error' => 'Importo minimo non raggiunto: €' . number_format($coupon['min_amount'], 2)];

    $discount = 0;
    if ($coupon['discount_type'] === 'percent') {
        $discount = $subtotal * ($coupon['discount_value'] / 100);
    } else {
        $discount = min($coupon['discount_value'], $subtotal);
    }

    return [
        'valid' => true,
        'coupon' => $coupon,
        'discount' => round($discount, 2)
    ];
}

// ============================================================
// UTILITY
// ============================================================

function slugify($text) {
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9-]/', '-', $text);
    $text = preg_replace('/-+/', '-', $text);
    return trim($text, '-');
}

function formatPrice($price) {
    return '€ ' . number_format((float)$price, 2, ',', '.');
}

function flash($key, $message = null) {
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
    } else {
        $msg = $_SESSION['flash'][$key] ?? null;
        unset($_SESSION['flash'][$key]);
        return $msg;
    }
}

function uploadImage($file, $subdir = '') {
    $targetDir = UPLOADS_PATH . $subdir;
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed)) {
        return ['success' => false, 'error' => 'Formato non supportato'];
    }

    if ($file['size'] > 5 * 1024 * 1024) {
        return ['success' => false, 'error' => 'File troppo grande (max 5MB)'];
    }

    $filename = uniqid('img_') . '.' . $ext;
    $targetPath = $targetDir . '/' . $filename;

    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        $relativePath = ($subdir ? $subdir . '/' : '') . $filename;
        return ['success' => true, 'path' => $relativePath];
    }

    return ['success' => false, 'error' => 'Errore upload'];
}

function deleteImage($path) {
    $fullPath = UPLOADS_PATH . $path;
    if (file_exists($fullPath)) {
        unlink($fullPath);
    }
}

function generateResetToken() {
    return bin2hex(random_bytes(32));
}

function getApiKey() {
    $conn = getDBConnection();
    $r = mysqli_query($conn, "SELECT api_key FROM api_settings ORDER BY id DESC LIMIT 1");
    $row = mysqli_fetch_assoc($r);
    return $row ? $row['api_key'] : null;
}

function h($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

function paginate($totalItems, $perPage = 20, $currentPage = 1) {
    $totalPages = max(1, ceil($totalItems / $perPage));
    $currentPage = max(1, min($currentPage, $totalPages));
    $offset = ($currentPage - 1) * $perPage;
    return [
        'total' => $totalItems,
        'per_page' => $perPage,
        'current' => $currentPage,
        'total_pages' => $totalPages,
        'offset' => $offset
    ];
}
?>