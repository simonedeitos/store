<?php
/**
 * AirDirector Client - Middleware autenticazione
 * Include questo file in ogni pagina protetta.
 */
require_once __DIR__ . '/../functions.php';

if (!isClientLoggedIn()) {
    header('Location: ' . CLIENT_SITE_URL . '/index.php?error=session_expired');
    exit;
}

$clientUser = getClientUser();
$currentStation = getCurrentStation();

// Controlla regole accesso per subuser
if (($clientUser['user_type'] ?? '') === 'subuser') {
    if (!checkAccessTime($clientUser)) {
        session_destroy();
        header('Location: ' . CLIENT_SITE_URL . '/index.php?error=access_denied');
        exit;
    }
}
