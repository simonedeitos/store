<?php
/**
 * AirDirector Client - Admin Switch Stazione
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

if (!isClientLoggedIn() || !isClientAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Admin only']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

// Toggle admin interaction mode
if (!empty($input['toggle_interaction'])) {
    $_SESSION['admin_interaction'] = !($_SESSION['admin_interaction'] ?? false);
    echo json_encode(['success' => true, 'interaction' => $_SESSION['admin_interaction']]);
    exit;
}

$stationId = (int)($input['station_id'] ?? 0);

if (!$stationId) {
    echo json_encode(['success' => false, 'error' => 'Missing station_id']);
    exit;
}

$station = getStationById($stationId);
if (!$station) {
    echo json_encode(['success' => false, 'error' => 'Station not found']);
    exit;
}

$_SESSION['client_station'] = $station;

echo json_encode(['success' => true, 'station' => $station]);
