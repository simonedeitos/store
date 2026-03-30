<?php
/**
 * AirDirector Store - API: Attiva Licenza
 * 
 * POST /api/license_activate.php
 * Header: X-API-Key: <api_key>
 * Body (JSON o form): serial, hardware_id
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../email_functions.php';

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

// Leggi body (supporta JSON e form-data)
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$serial = trim($input['serial'] ?? '');
$hardwareId = trim($input['hardware_id'] ?? '');

if (!$serial || !$hardwareId) {
    http_response_code(400);
    echo json_encode(['error' => 'Bad Request', 'message' => 'Parametri serial e hardware_id obbligatori']);
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
    http_response_code(404);
    echo json_encode(['error' => 'Not Found', 'message' => 'Licenza non trovata']);
    exit;
}

// Controlla se l'ordine è confermato
if ($license['order_item_id']) {
    $oiR = mysqli_query($conn, "SELECT o.status FROM order_items oi JOIN orders o ON o.id = oi.order_id WHERE oi.id = " . (int)$license['order_item_id']);
    $oi = mysqli_fetch_assoc($oiR);
    if ($oi && $oi['status'] !== 'confirmed') {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden', 'message' => 'Ordine non ancora confermato']);
        exit;
    }
}

// Controlla scadenza
if ($license['expires_at'] && strtotime($license['expires_at']) < time()) {
    http_response_code(403);
    echo json_encode(['error' => 'Expired', 'message' => 'Licenza scaduta']);
    exit;
}

// Controlla se revocata
if ($license['status'] === 'revoked') {
    http_response_code(403);
    echo json_encode(['error' => 'Revoked', 'message' => 'Licenza revocata']);
    exit;
}

// Se già attiva
if ($license['is_active'] == 1) {
    if ($license['hardware_id'] === $hardwareId) {
        echo json_encode([
            'success' => true,
            'message' => 'Licenza già attiva su questo dispositivo',
            'already_active' => true,
            'serial' => $license['serial_key'],
            'software_name' => $license['software_name'],
            'hardware_id' => $license['hardware_id']
        ]);
        exit;
    } else {
        http_response_code(409);
        echo json_encode([
            'error' => 'Conflict',
            'message' => 'Licenza già attiva su un altro dispositivo. Disattivala prima dal tuo account.',
            'current_hardware_id' => $license['hardware_id']
        ]);
        exit;
    }
}

// Attiva licenza
$hwEsc = mysqli_real_escape_string($conn, $hardwareId);
$licId = (int)$license['id'];
$result = mysqli_query($conn, "UPDATE licenses SET is_active = 1, status = 'active', hardware_id = '$hwEsc', activated_at = NOW() WHERE id = $licId");

if ($result) {
    // Invia email notifica attivazione al cliente
    $updatedLic = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT l.*, s.name as software_name 
        FROM licenses l 
        JOIN software s ON s.id = l.software_id 
        WHERE l.id = $licId
    "));
    $updatedLic['hardware_id'] = $hardwareId;
    sendLicenseActivatedEmail($updatedLic);
    
    // Notifica admin se abilitata
    if (isAdminNotificationEnabled('license_activated')) {
        $adminEmail = getAdminEmail();
        if ($adminEmail) {
            $userName = 'N/A';
            if ($license['user_id']) {
                $userR = mysqli_query($conn, "SELECT first_name, last_name, email FROM users WHERE id = " . (int)$license['user_id']);
                $userData = mysqli_fetch_assoc($userR);
                if ($userData) $userName = $userData['first_name'] . ' ' . $userData['last_name'] . ' (' . $userData['email'] . ')';
            }
            // Log semplice per admin - usa direttamente sendEmail con un template generico
            logEmail($adminEmail, 'Admin', '[ADMIN] Licenza attivata: ' . $license['serial_key'] . ' - ' . $license['software_name'] . ' - ' . $userName, 'license_activated', 'sent');
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Licenza attivata con successo',
        'serial' => $license['serial_key'],
        'software_name' => $license['software_name'],
        'hardware_id' => $hardwareId,
        'activated_at' => date('Y-m-d H:i:s')
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Server Error', 'message' => 'Errore durante l\'attivazione']);
}
?>