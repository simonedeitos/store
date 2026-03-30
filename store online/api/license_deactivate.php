<?php
/**
 * AirDirector Store - API: Disattiva Licenza
 * 
 * POST /api/license_deactivate.php
 * Header: X-API-Key: <api_key>
 * Body (JSON o form): serial
 * 
 * Disattiva la licenza: is_active=0, hardware_id=NULL
 * Il software tornerà in modalità demo
 */
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=UTF-8');

// Verifica API Key
$apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
$conn = getDBConnection();

$storedKey = '';
$r = mysqli_query($conn, "SELECT api_key FROM api_settings ORDER BY id DESC LIMIT 1");
$row = mysqli_fetch_assoc($r);
if ($row) $storedKey = $row['api_key'];

if (!$apiKey || $apiKey !== $storedKey) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized', 'message' => 'API Key mancante o non valida']);
    exit;
}

// Leggi body
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$serial = trim($input['serial'] ?? '');

if (!$serial) {
    http_response_code(400);
    echo json_encode(['error' => 'Bad Request', 'message' => 'Parametro serial obbligatorio']);
    exit;
}

// Cerca licenza
$serialEsc = mysqli_real_escape_string($conn, $serial);
$licR = mysqli_query($conn, "SELECT l.*, s.name as software_name FROM licenses l JOIN software s ON s.id = l.software_id WHERE l.serial_key = '$serialEsc'");
$license = mysqli_fetch_assoc($licR);

if (!$license) {
    http_response_code(404);
    echo json_encode(['error' => 'Not Found', 'message' => 'Licenza non trovata']);
    exit;
}

if ($license['is_active'] == 0) {
    echo json_encode([
        'success' => true,
        'message' => 'Licenza già disattiva',
        'already_inactive' => true
    ]);
    exit;
}

// Disattiva
$licId = (int)$license['id'];
$result = mysqli_query($conn, "UPDATE licenses SET is_active = 0, status = 'purchased', hardware_id = NULL, activated_at = NULL WHERE id = $licId");

if ($result) {
    echo json_encode([
        'success' => true,
        'message' => 'Licenza disattivata. Il software tornerà in modalità demo.',
        'serial' => $license['serial_key'],
        'software_name' => $license['software_name']
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Server Error', 'message' => 'Errore durante la disattivazione']);
}
?>