<?php
/**
 * AirDirector Client - Lista Lingue Disponibili
 * Auto-discovery dalla cartella language/
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';

$langNames = [
    'it' => 'Italiano',
    'en' => 'English',
    'es' => 'Español',
    'de' => 'Deutsch',
    'fr' => 'Français',
    'pt' => 'Português',
];

$langs = [];
$path = CLIENT_BASE_PATH . '/language/';
if (is_dir($path)) {
    foreach (glob($path . '*.json') as $file) {
        $code = basename($file, '.json');
        $langs[] = [
            'code' => $code,
            'name' => $langNames[$code] ?? strtoupper($code),
        ];
    }
}

echo json_encode(['success' => true, 'languages' => $langs]);
