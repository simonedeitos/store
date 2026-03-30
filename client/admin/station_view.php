<?php
/**
 * AirDirector Client - Admin: Vista Singola Stazione
 * Switches the admin to the selected station and redirects to dashboard
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

if (!isClientAdmin()) {
    header('Location: ' . CLIENT_SITE_URL . '/index.php');
    exit;
}

$stationId = (int)($_GET['id'] ?? 0);
$station = getStationById($stationId);
if (!$station) {
    die('Stazione non trovata.');
}

// Set station in session
$_SESSION['client_station'] = $station;

// Toggle interaction if requested
if (isset($_GET['toggle_interaction'])) {
    $_SESSION['admin_interaction'] = !($_SESSION['admin_interaction'] ?? false);
}

// Redirect to dashboard
header('Location: ' . CLIENT_SITE_URL . '/dashboard.php');
exit;
