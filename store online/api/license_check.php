<?php
/**
 * AirDirector Store - API: Controlla Licenza
 * 
 * GET /api/license_check.php?serial=XXXX-XXXX-XXXX-XXXX
 * Header: X-API-Key: <api_key>
 * 
 * Risponde con:
 * - exists: true/false
 * - is_active: 0/1
 * - status: purchased/active/revoked
 * - software_name
 * - hardware_id (se attiva)
 * - expires_at (se impostato)
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

// Parametri
$serial = trim($_GET['serial'] ?? '');

if (!$serial) {
    http_response_code(400);
    echo json_encode(['error' => 'Bad Request', 'message' => 'Parametro serial obbligatorio']);
    exit;
}

// Cerca licenza
$serialEsc = mysqli_real_escape_string($conn, $serial);
$licR = mysqli_query($conn, "
    SELECT l.*, s.name as software_name 
    FROM licenses l 
    JOIN software s ON s.id = l.software_id 
    WHERE l.serial_key = '$serialEsc'
");
$license = mysqli_fetch_assoc($licR);

if (!$license) {
    echo json_encode([
        'exists' => false,
        'message' => 'Licenza non trovata'
    ]);
    exit;
}

// Controlla se l'ordine è confermato (per licenze da ordine)
$orderConfirmed = true;
if ($license['order_item_id']) {
    $oiR = mysqli_query($conn, "SELECT o.status FROM order_items oi JOIN orders o ON o.id = oi.order_id WHERE oi.id = " . (int)$license['order_item_id']);
    $oi = mysqli_fetch_assoc($oiR);
    if ($oi && $oi['status'] !== 'confirmed') {
        $orderConfirmed = false;
    }
}

// Controlla scadenza
$expired = false;
if ($license['expires_at'] && strtotime($license['expires_at']) < time()) {
    $expired = true;
}

echo json_encode([
    'exists' => true,
    'serial' => $license['serial_key'],
    'software_name' => $license['software_name'],
    'software_id' => (int)$license['software_id'],
    'status' => $license['status'],
    'is_active' => (int)$license['is_active'],
    'hardware_id' => $license['hardware_id'],
    'order_confirmed' => $orderConfirmed,
    'expired' => $expired,
    'expires_at' => $license['expires_at'],
    'activated_at' => $license['activated_at'],
    'created_at' => $license['created_at']
]);
?>