<?php
/**
 * AirDirector Store - API Router
 * Endpoint di base. Risponde con info API.
 */
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=UTF-8');

echo json_encode([
    'status' => 'ok',
    'service' => 'AirDirector Store License API',
    'version' => '1.0',
    'endpoints' => [
        'license_check' => 'GET /api/license_check.php?serial=XXXX-XXXX-XXXX-XXXX',
        'license_activate' => 'POST /api/license_activate.php (serial, hardware_id)',
        'license_deactivate' => 'POST /api/license_deactivate.php (serial)'
    ]
]);
?>