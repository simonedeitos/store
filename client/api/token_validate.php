<?php
/**
 * AirDirector Client - Validazione Token (chiamata da AirDirector)
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

$token = $_GET['token'] ?? ($_POST['token'] ?? '');
if (!$token) {
    echo json_encode(['valid' => false, 'error' => 'Token missing']);
    exit;
}

$station = getStationByToken($token);
if (!$station) {
    echo json_encode(['valid' => false, 'error' => 'Invalid or inactive token']);
    exit;
}

echo json_encode([
    'valid'        => true,
    'station_id'   => $station['id'],
    'station_name' => $station['station_name'],
    'ws_url'       => WS_SERVER_URL . '?token=' . urlencode($token)
]);
