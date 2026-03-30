<?php
/**
 * AirDirector Store - Configurazione
 */

// Fuso orario
date_default_timezone_set('Europe/Rome');

// Database
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'u362062795_airdirector');
define('DB_USER', 'u362062795_airdirector');
define('DB_PASS', '^4ir;Dir3ct0r-database=2025%');

// Client DB (AirDirector Client app)
define('CLIENT_DB_HOST', '127.0.0.1');
define('CLIENT_DB_PORT', '3306');
define('CLIENT_DB_NAME', '4362062795_adclient');
define('CLIENT_DB_USER', '4362062795_adclient');
define('CLIENT_DB_PASS', '^4ir;Dir3ctOr-database=2025%');

// JWT secret for admin SSO to AirDirector Client
define('CLIENT_ADMIN_JWT_SECRET', 'airdirector-admin-sso-secret-2025');

// Costanti generali
define('SITE_NAME', 'AirDirector Store');
define('SITE_URL', 'https://store.airdirector.app');

// Percorso base
define('BASE_PATH', __DIR__);
define('UPLOADS_PATH', BASE_PATH . '/uploads/software/');
define('UPLOADS_URL', SITE_URL . '/uploads/software/');

// Connessione DB
function getDBConnection() {
    static $conn = null;
    if ($conn === null) {
        $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
        if ($conn === false) {
            die("Errore di connessione al Database: " . mysqli_connect_error());
        }
        mysqli_set_charset($conn, "utf8mb4");
    }
    return $conn;
}

// Escape stringhe
function dbEsc($str) {
    $conn = getDBConnection();
    return mysqli_real_escape_string($conn, $str);
}

// Avvia sessione
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>