<?php
/**
 * AirDirector Client - Configurazione
 */

date_default_timezone_set('Europe/Rome');

// --- Client Database ---
define('CLIENT_DB_HOST', '127.0.0.1');
define('CLIENT_DB_PORT', '3306');
define('CLIENT_DB_NAME', 'u362062795_adclient');
define('CLIENT_DB_USER', 'u362062795_adclient');
define('CLIENT_DB_PASS', '^4ir;Dir3ctOr-database=2025%');

// --- Store Database (for owner auth validation) ---
define('STORE_DB_HOST', '127.0.0.1');
define('STORE_DB_PORT', '3306');
define('STORE_DB_NAME', 'u362062795_airdirector');
define('STORE_DB_USER', 'u362062795_airdirector');
define('STORE_DB_PASS', '^4ir;Dir3ct0r-database=2025%');

// --- URLs ---
define('CLIENT_SITE_URL', 'https://client.airdirector.app');
define('STORE_SITE_URL', 'https://store.airdirector.app');
define('WS_SERVER_URL', 'wss://store-uglh.onrender.com');
define('CLIENT_NAME', 'AirDirector Client');

// --- JWT Secret for admin SSO ---
define('JWT_SECRET', 'airdirector-admin-sso-secret-2025');

// --- Base Path ---
define('CLIENT_BASE_PATH', __DIR__);
define('CLIENT_LANGUAGE_PATH', CLIENT_BASE_PATH . '/language/');

// --- Client DB Connection ---
function getClientDB() {
    static $conn = null;
    if ($conn === null) {
        $conn = mysqli_connect(CLIENT_DB_HOST, CLIENT_DB_USER, CLIENT_DB_PASS, CLIENT_DB_NAME, CLIENT_DB_PORT);
        if ($conn === false) {
            die(json_encode(['error' => 'Client DB connection failed: ' . mysqli_connect_error()]));
        }
        mysqli_set_charset($conn, 'utf8mb4');
    }
    return $conn;
}

// --- Store DB Connection ---
function getStoreDB() {
    static $conn = null;
    if ($conn === null) {
        $conn = mysqli_connect(STORE_DB_HOST, STORE_DB_USER, STORE_DB_PASS, STORE_DB_NAME, STORE_DB_PORT);
        if ($conn === false) {
            die(json_encode(['error' => 'Store DB connection failed: ' . mysqli_connect_error()]));
        }
        mysqli_set_charset($conn, 'utf8mb4');
    }
    return $conn;
}

function dbEscClient($str) {
    return mysqli_real_escape_string(getClientDB(), $str);
}

function dbEscStore($str) {
    return mysqli_real_escape_string(getStoreDB(), $str);
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
