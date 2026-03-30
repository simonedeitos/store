<?php
/**
 * AirDirector Client - Auth WebSocket
 * Ritorna un token di connessione per il WS server
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

if (!isClientLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$user = getClientUser();
$station = getCurrentStation();

if (!$station) {
    echo json_encode(['success' => false, 'error' => 'No station']);
    exit;
}

$payload = [
    'user_id'      => $user['id'],
    'user_type'    => $user['user_type'],
    'user_name'    => $user['name'] ?? $user['display_name'] ?? '',
    'station_token'=> $station['token'],
    'station_id'   => $station['id'],
    'exp'          => time() + 3600,
];

$wsToken = jwtEncode($payload);

echo json_encode([
    'success'       => true,
    'ws_token'      => $wsToken,
    'ws_url'        => WS_SERVER_URL,
    'station_token' => $station['token'],
]);
