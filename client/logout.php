<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

// Remove active session from DB
if (isClientLoggedIn() && isset($_SESSION['client_session_token'])) {
    $conn = getClientDB();
    $token = dbEscClient($_SESSION['client_session_token']);
    mysqli_query($conn, "DELETE FROM active_sessions WHERE session_token = '$token'");
}

session_destroy();
header('Location: ' . CLIENT_SITE_URL . '/index.php?logged_out=1');
exit;
