<?php
/**
 * AirDirector Client - Funzioni Helper
 */

require_once __DIR__ . '/config.php';

// ============================================================
// UTILITY
// ============================================================

function h($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

function flash($key, $message = null) {
    if ($message !== null) {
        $_SESSION['client_flash'][$key] = $message;
    } else {
        $msg = $_SESSION['client_flash'][$key] ?? null;
        unset($_SESSION['client_flash'][$key]);
        return $msg;
    }
}

function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

function generateToken($length = 64) {
    return bin2hex(random_bytes($length / 2));
}

function generateSessionToken() {
    return bin2hex(random_bytes(64));
}

// ============================================================
// AUTH
// ============================================================

function isClientLoggedIn() {
    return isset($_SESSION['client_user_id']) && $_SESSION['client_user_id'] > 0;
}

function isClientAdmin() {
    return isset($_SESSION['client_is_admin']) && $_SESSION['client_is_admin'] === true;
}

function getClientUser() {
    if (!isClientLoggedIn()) return null;
    return $_SESSION['client_user'] ?? null;
}

function getCurrentStation() {
    return $_SESSION['client_station'] ?? null;
}

function requireClientLogin() {
    if (!isClientLoggedIn()) {
        header('Location: ' . CLIENT_SITE_URL . '/index.php');
        exit;
    }
}

// ============================================================
// ACCESS TIME CHECK
// ============================================================

function checkAccessTime($user) {
    if ($user['user_type'] !== 'subuser') return true;

    $now = new DateTime();
    $dayOfWeek = $now->format('N'); // 1=Mon ... 7=Sun

    $allowedDays = explode(',', $user['access_days'] ?? '1,2,3,4,5,6,7');
    if (!in_array($dayOfWeek, $allowedDays)) return false;

    $start = $user['access_time_start'] ?? '00:00:00';
    $end   = $user['access_time_end']   ?? '23:59:59';
    $currentTime = $now->format('H:i:s');

    if ($currentTime < $start || $currentTime > $end) return false;

    return true;
}

// ============================================================
// STATION LOOKUP
// ============================================================

function getStationById($stationId) {
    $conn = getClientDB();
    $id = (int)$stationId;
    $r = mysqli_query($conn, "SELECT * FROM stations WHERE id = $id AND is_active = 1");
    return mysqli_fetch_assoc($r);
}

function getStationByToken($token) {
    $conn = getClientDB();
    $t = dbEscClient($token);
    $r = mysqli_query($conn, "SELECT * FROM stations WHERE token = '$t' AND is_active = 1");
    return mysqli_fetch_assoc($r);
}

// ============================================================
// JWT MINI (for Admin SSO)
// ============================================================

function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64url_decode($data) {
    return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', (4 - strlen($data) % 4) % 4));
}

function jwtEncode($payload) {
    $header = base64url_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
    $body   = base64url_encode(json_encode($payload));
    $sig    = base64url_encode(hash_hmac('sha256', "$header.$body", JWT_SECRET, true));
    return "$header.$body.$sig";
}

function jwtDecode($token) {
    $parts = explode('.', $token);
    if (count($parts) !== 3) return null;
    [$header, $body, $sig] = $parts;
    $expected = base64url_encode(hash_hmac('sha256', "$header.$body", JWT_SECRET, true));
    if (!hash_equals($expected, $sig)) return null;
    $payload = json_decode(base64url_decode($body), true);
    if (!$payload || (isset($payload['exp']) && $payload['exp'] < time())) return null;
    return $payload;
}

// ============================================================
// LANGUAGE
// ============================================================

function getAvailableLanguages() {
    $path = CLIENT_LANGUAGE_PATH;
    $langs = [];
    if (is_dir($path)) {
        foreach (glob($path . '*.json') as $file) {
            $code = basename($file, '.json');
            $langs[] = $code;
        }
    }
    return $langs;
}

function getUserLanguage() {
    if (isClientLoggedIn()) {
        $user = getClientUser();
        if (isset($user['language'])) return $user['language'];
    }
    return $_SESSION['client_lang'] ?? 'it';
}
?>
