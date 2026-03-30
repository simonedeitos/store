<?php
/**
 * AirDirector Store - Diagnostica Completa
 * Carica questo file nella root del sito e aprilo nel browser.
 * ELIMINALO dopo l'uso!
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">';
echo '<title>AirDirector Store - Diagnostica</title>';
echo '<style>
body{font-family:Segoe UI,Arial,sans-serif;background:#1e293b;color:#e2e8f0;padding:20px;max-width:1000px;margin:0 auto;}
h1{color:#38bdf8;border-bottom:2px solid #38bdf8;padding-bottom:10px;}
h2{color:#fbbf24;margin-top:30px;}
.ok{color:#4ade80;font-weight:bold;}
.fail{color:#f87171;font-weight:bold;}
.warn{color:#fbbf24;font-weight:bold;}
.box{background:#0f172a;border-radius:8px;padding:15px;margin:10px 0;border:1px solid #334155;}
.code{background:#020617;padding:10px;border-radius:4px;font-family:monospace;font-size:13px;overflow-x:auto;white-space:pre-wrap;word-break:break-all;color:#94a3b8;margin:5px 0;}
table{width:100%;border-collapse:collapse;margin:10px 0;}
td,th{padding:8px 12px;border:1px solid #334155;text-align:left;font-size:14px;}
th{background:#1e40af;color:white;}
tr:nth-child(even){background:#0f172a;}
.count{display:inline-block;background:#2563eb;color:white;padding:2px 10px;border-radius:10px;font-size:12px;margin-left:5px;}
</style></head><body>';

echo '<h1>🔍 AirDirector Store - Diagnostica Completa</h1>';
echo '<p>Eseguita: ' . date('d/m/Y H:i:s') . '</p>';

$errors = [];
$warnings = [];
$fixes = [];

// ============================================================
// 1. PHP VERSION & CONFIG
// ============================================================
echo '<h2>1. 🖥️ Ambiente PHP</h2><div class="box">';

$phpVer = phpversion();
echo "PHP Version: <strong>$phpVer</strong> ";
if (version_compare($phpVer, '7.4', '>=')) {
    echo '<span class="ok">✅ OK</span><br>';
} else {
    echo '<span class="fail">❌ Troppo vecchio (minimo 7.4)</span><br>';
    $errors[] = "PHP version $phpVer troppo vecchia";
}

$requiredExts = ['mysqli', 'json', 'mbstring', 'session', 'openssl'];
foreach ($requiredExts as $ext) {
    echo "Estensione <strong>$ext</strong>: ";
    if (extension_loaded($ext)) {
        echo '<span class="ok">✅ Caricata</span><br>';
    } else {
        echo '<span class="fail">❌ MANCANTE</span><br>';
        $errors[] = "Estensione PHP '$ext' non caricata";
    }
}

echo "display_errors: " . ini_get('display_errors') . '<br>';
echo "error_reporting: " . error_reporting() . '<br>';
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . '<br>';
echo "post_max_size: " . ini_get('post_max_size') . '<br>';
echo "allow_url_fopen: " . (ini_get('allow_url_fopen') ? 'On' : 'Off') . '<br>';
echo "fsockopen disponibile: " . (function_exists('fsockopen') ? '<span class="ok">Sì</span>' : '<span class="warn">No (email SMTP non funzionerà)</span>') . '<br>';
echo '</div>';

// ============================================================
// 2. FILE STRUCTURE
// ============================================================
echo '<h2>2. 📁 Struttura File</h2><div class="box">';

$requiredFiles = [
    'config.php' => 'Configurazione database',
    'functions.php' => 'Funzioni globali',
    'email_functions.php' => 'Funzioni email (NUOVO)',
    'index.php' => 'Homepage',
    'software.php' => 'Dettaglio software',
    'cart.php' => 'Carrello',
    'checkout.php' => 'Checkout',
    'login.php' => 'Login cliente',
    'register.php' => 'Registrazione',
    'forgot_password.php' => 'Password dimenticata',
    'reset_password.php' => 'Reset password',
    'logout.php' => 'Logout',
    '.htaccess' => 'Regole Apache',
    'includes/header.php' => 'Header HTML',
    'includes/footer.php' => 'Footer HTML',
    'includes/navbar.php' => 'Navbar pubblica',
    'includes/admin_sidebar.php' => 'Sidebar admin',
    'assets/css/style.css' => 'Foglio di stile',
    'assets/js/main.js' => 'JavaScript principale',
    'account/index.php' => 'Dashboard cliente',
    'account/orders.php' => 'Ordini cliente',
    'account/licenses.php' => 'Licenze cliente',
    'account/profile.php' => 'Profilo cliente',
    'account/download.php' => 'Download cliente',
    'admin/index.php' => 'Dashboard admin',
    'admin/login.php' => 'Login admin',
    'admin/logout.php' => 'Logout admin',
    'admin/software/index.php' => 'Lista software',
    'admin/software/create.php' => 'Crea software',
    'admin/software/edit.php' => 'Modifica software',
    'admin/software/delete.php' => 'Elimina software',
    'admin/bundles/index.php' => 'Lista bundle',
    'admin/bundles/create.php' => 'Crea bundle',
    'admin/bundles/edit.php' => 'Modifica bundle',
    'admin/bundles/delete.php' => 'Elimina bundle',
    'admin/orders/index.php' => 'Lista ordini',
    'admin/orders/view.php' => 'Dettaglio ordine',
    'admin/licenses/index.php' => 'Lista licenze',
    'admin/licenses/generate.php' => 'Genera licenza',
    'admin/customers/index.php' => 'Lista clienti',
    'admin/customers/view.php' => 'Dettaglio cliente',
    'admin/coupons/index.php' => 'Lista coupon',
    'admin/coupons/create.php' => 'Crea coupon',
    'admin/coupons/edit.php' => 'Modifica coupon',
    'admin/coupons/delete.php' => 'Elimina coupon',
    'admin/discounts/index.php' => 'Sconti quantità',
    'admin/requests/index.php' => 'Richieste modifica',
    'admin/settings/index.php' => 'Impostazioni',
    'admin/email/settings.php' => 'Email settings (NUOVO)',
    'admin/email/templates.php' => 'Email templates (NUOVO)',
    'admin/email/notifications.php' => 'Notifiche admin (NUOVO)',
    'admin/email/log.php' => 'Log email (NUOVO)',
    'api/index.php' => 'API info',
    'api/license_check.php' => 'API verifica licenza',
    'api/license_activate.php' => 'API attiva licenza',
    'api/license_deactivate.php' => 'API disattiva licenza',
];

$missingFiles = 0;
$foundFiles = 0;
echo '<table><tr><th>File</th><th>Descrizione</th><th>Stato</th><th>Dimensione</th></tr>';
foreach ($requiredFiles as $file => $desc) {
    $fullPath = __DIR__ . '/' . $file;
    echo '<tr><td><code>' . $file . '</code></td><td>' . $desc . '</td>';
    if (file_exists($fullPath)) {
        $size = filesize($fullPath);
        $sizeStr = $size > 1024 ? round($size/1024, 1) . ' KB' : $size . ' B';
        if ($size === 0) {
            echo '<td><span class="warn">⚠️ VUOTO</span></td>';
            $warnings[] = "File '$file' esiste ma è vuoto (0 bytes)";
        } else {
            echo '<td><span class="ok">✅ OK</span></td>';
        }
        echo '<td>' . $sizeStr . '</td>';
        $foundFiles++;
    } else {
        echo '<td><span class="fail">❌ MANCANTE</span></td><td>-</td>';
        $missingFiles++;
        $errors[] = "File mancante: $file ($desc)";
    }
    echo '</tr>';
}
echo '</table>';
echo "<p>Trovati: <span class='ok'>$foundFiles</span> | Mancanti: " . ($missingFiles > 0 ? "<span class='fail'>$missingFiles</span>" : "<span class='ok'>0</span>") . "</p>";
echo '</div>';

// ============================================================
// 3. DIRECTORIES & PERMISSIONS
// ============================================================
echo '<h2>3. 📂 Cartelle e Permessi</h2><div class="box">';

$requiredDirs = [
    'uploads/software' => 'Repository immagini',
    'uploads/software/main' => 'Immagini principali software',
    'uploads/software/gallery' => 'Galleria software',
    'uploads/software/bundles' => 'Immagini bundle',
    'account' => 'Area cliente',
    'admin' => 'Area admin',
    'admin/software' => 'Admin software',
    'admin/bundles' => 'Admin bundle',
    'admin/orders' => 'Admin ordini',
    'admin/licenses' => 'Admin licenze',
    'admin/customers' => 'Admin clienti',
    'admin/coupons' => 'Admin coupon',
    'admin/discounts' => 'Admin sconti',
    'admin/requests' => 'Admin richieste',
    'admin/settings' => 'Admin impostazioni',
    'admin/email' => 'Admin email (NUOVO)',
    'api' => 'API endpoint',
    'assets/css' => 'Fogli di stile',
    'assets/js' => 'JavaScript',
    'includes' => 'Include files',
];

echo '<table><tr><th>Cartella</th><th>Descrizione</th><th>Esiste</th><th>Scrivibile</th></tr>';
foreach ($requiredDirs as $dir => $desc) {
    $fullPath = __DIR__ . '/' . $dir;
    echo '<tr><td><code>' . $dir . '/</code></td><td>' . $desc . '</td>';
    if (is_dir($fullPath)) {
        echo '<td><span class="ok">✅ Sì</span></td>';
        if (is_writable($fullPath)) {
            echo '<td><span class="ok">✅ Sì</span></td>';
        } else {
            echo '<td><span class="warn">⚠️ No</span></td>';
            if (strpos($dir, 'uploads') !== false) {
                $warnings[] = "Cartella '$dir' non scrivibile (necessaria per upload)";
            }
        }
    } else {
        echo '<td><span class="fail">❌ No</span></td><td>-</td>';
        $errors[] = "Cartella mancante: $dir/";
        $fixes[] = "mkdir('$fullPath', 0755, true);";
    }
    echo '</tr>';
}
echo '</table>';
echo '</div>';

// ============================================================
// 4. CONFIG.PHP CHECK
// ============================================================
echo '<h2>4. ⚙️ Configurazione (config.php)</h2><div class="box">';

if (file_exists(__DIR__ . '/config.php')) {
    echo '<span class="ok">✅ config.php trovato</span><br>';
    
    // Prova a caricarlo e controlla errori
    ob_start();
    $configError = null;
    try {
        // Non ri-includere se già caricato
        if (!defined('DB_HOST')) {
            include __DIR__ . '/config.php';
        }
    } catch (Throwable $e) {
        $configError = $e->getMessage();
    }
    $configOutput = ob_get_clean();
    
    if ($configError) {
        echo '<span class="fail">❌ Errore in config.php: ' . htmlspecialchars($configError) . '</span><br>';
        $errors[] = "config.php ha un errore: $configError";
    } else {
        echo '<span class="ok">✅ config.php caricato senza errori</span><br>';
        
        if (defined('DB_HOST')) echo "DB_HOST: <strong>" . DB_HOST . "</strong><br>";
        if (defined('DB_NAME')) echo "DB_NAME: <strong>" . DB_NAME . "</strong><br>";
        if (defined('DB_USER')) echo "DB_USER: <strong>" . DB_USER . "</strong><br>";
        if (defined('DB_PASS')) echo "DB_PASS: <strong>***" . substr(DB_PASS, -4) . "</strong> (ultime 4 cifre)<br>";
        if (defined('SITE_URL')) echo "SITE_URL: <strong>" . SITE_URL . "</strong><br>";
        if (defined('UPLOADS_PATH')) echo "UPLOADS_PATH: <strong>" . UPLOADS_PATH . "</strong><br>";
    }
} else {
    echo '<span class="fail">❌ config.php NON TROVATO!</span><br>';
    $errors[] = "config.php mancante - il sito non può funzionare";
}
echo '</div>';

// ============================================================
// 5. DATABASE CONNECTION
// ============================================================
echo '<h2>5. 🗄️ Connessione Database</h2><div class="box">';

$dbConn = null;
if (defined('DB_HOST') && defined('DB_USER') && defined('DB_PASS') && defined('DB_NAME')) {
    $dbConn = @mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME, defined('DB_PORT') ? DB_PORT : 3306);
    if ($dbConn) {
        echo '<span class="ok">✅ Connessione al database riuscita!</span><br>';
        echo "Server: " . mysqli_get_server_info($dbConn) . "<br>";
        echo "Charset: " . mysqli_character_set_name($dbConn) . "<br>";
        mysqli_set_charset($dbConn, "utf8mb4");
    } else {
        echo '<span class="fail">❌ Connessione FALLITA: ' . htmlspecialchars(mysqli_connect_error()) . '</span><br>';
        $errors[] = "Impossibile connettersi al database: " . mysqli_connect_error();
    }
} else {
    echo '<span class="fail">❌ Costanti DB non definite (config.php mancante o con errori)</span><br>';
    $errors[] = "Costanti database non definite";
}
echo '</div>';

// ============================================================
// 6. DATABASE TABLES
// ============================================================
echo '<h2>6. 📊 Tabelle Database</h2><div class="box">';

if ($dbConn) {
    $requiredTables = [
        'admins' => 'Amministratori',
        'users' => 'Clienti',
        'software' => 'Software',
        'software_images' => 'Immagini software',
        'bundles' => 'Bundle',
        'bundle_items' => 'Items bundle',
        'coupons' => 'Coupon',
        'quantity_discounts' => 'Sconti quantità',
        'orders' => 'Ordini',
        'order_items' => 'Items ordine',
        'licenses' => 'Licenze',
        'profile_requests' => 'Richieste profilo',
        'api_settings' => 'Impostazioni API',
        'email_settings' => 'Impostazioni Email (NUOVO)',
        'email_templates' => 'Template Email (NUOVO)',
        'admin_notifications' => 'Notifiche Admin (NUOVO)',
        'email_log' => 'Log Email (NUOVO)',
    ];

    echo '<table><tr><th>Tabella</th><th>Descrizione</th><th>Stato</th><th>Righe</th></tr>';
    $missingTables = 0;
    foreach ($requiredTables as $table => $desc) {
        $r = @mysqli_query($dbConn, "SELECT COUNT(*) as cnt FROM `$table`");
        echo '<tr><td><code>' . $table . '</code></td><td>' . $desc . '</td>';
        if ($r) {
            $row = mysqli_fetch_assoc($r);
            echo '<td><span class="ok">✅ OK</span></td>';
            echo '<td>' . $row['cnt'] . '</td>';
        } else {
            echo '<td><span class="fail">❌ MANCANTE</span></td><td>-</td>';
            $missingTables++;
            $errors[] = "Tabella '$table' mancante nel database";
        }
        echo '</tr>';
    }
    echo '</table>';
    
    if ($missingTables > 0) {
        echo "<p class='fail'>⚠️ $missingTables tabelle mancanti! Devi eseguire le query SQL di creazione in phpMyAdmin.</p>";
    }
} else {
    echo '<span class="fail">❌ Impossibile verificare le tabelle - nessuna connessione DB</span><br>';
}
echo '</div>';

// ============================================================
// 7. CRITICAL DATA CHECK
// ============================================================
echo '<h2>7. 🔐 Dati Critici</h2><div class="box">';

if ($dbConn) {
    // Admin
    $r = @mysqli_query($dbConn, "SELECT id, username, email FROM admins LIMIT 5");
    if ($r && mysqli_num_rows($r) > 0) {
        echo '<span class="ok">✅ Admin trovato/i:</span><br>';
        while ($a = mysqli_fetch_assoc($r)) {
            echo "  - ID:{$a['id']} | Username: <strong>{$a['username']}</strong> | Email: {$a['email']}<br>";
        }
    } else {
        echo '<span class="fail">❌ Nessun admin nel database!</span><br>';
        $errors[] = "Nessun admin presente - impossibile accedere al pannello admin";
    }
    
    echo '<br>';
    
    // API Key
    $r = @mysqli_query($dbConn, "SELECT api_key FROM api_settings LIMIT 1");
    if ($r && $row = mysqli_fetch_assoc($r)) {
        echo '<span class="ok">✅ API Key presente:</span> <code>' . substr($row['api_key'], 0, 8) . '...' . substr($row['api_key'], -4) . '</code><br>';
    } else {
        echo '<span class="warn">⚠️ Nessuna API Key nel database</span><br>';
        $warnings[] = "API Key mancante - le API licenze non funzioneranno";
    }
    
    echo '<br>';
    
    // Email Settings
    $r = @mysqli_query($dbConn, "SELECT * FROM email_settings LIMIT 1");
    if ($r && $row = mysqli_fetch_assoc($r)) {
        if (!empty($row['smtp_host'])) {
            echo '<span class="ok">✅ SMTP configurato:</span> ' . htmlspecialchars($row['smtp_host']) . ':' . $row['smtp_port'] . '<br>';
        } else {
            echo '<span class="warn">⚠️ SMTP non ancora configurato (email non funzioneranno)</span><br>';
            $warnings[] = "SMTP non configurato - configura da Admin > Email Settings";
        }
    } else {
        echo '<span class="warn">⚠️ Tabella email_settings vuota o mancante</span><br>';
    }
    
    echo '<br>';
    
    // Email Templates
    $r = @mysqli_query($dbConn, "SELECT COUNT(*) as cnt FROM email_templates");
    if ($r && $row = mysqli_fetch_assoc($r)) {
        echo "Email Templates: <strong>{$row['cnt']}</strong> template nel database ";
        echo ($row['cnt'] >= 10 ? '<span class="ok">✅ OK</span>' : '<span class="warn">⚠️ Pochi template</span>');
        echo '<br>';
    }
    
    // Admin Notifications
    $r = @mysqli_query($dbConn, "SELECT COUNT(*) as cnt FROM admin_notifications");
    if ($r && $row = mysqli_fetch_assoc($r)) {
        echo "Notifiche Admin: <strong>{$row['cnt']}</strong> configurate<br>";
    }
}
echo '</div>';

// ============================================================
// 8. PHP SYNTAX CHECK ON KEY FILES
// ============================================================
echo '<h2>8. 🐛 Controllo Sintassi PHP (file critici)</h2><div class="box">';

$filesToCheck = [
    'config.php',
    'functions.php',
    'email_functions.php',
    'index.php',
    'login.php',
    'register.php',
    'checkout.php',
    'cart.php',
    'includes/header.php',
    'includes/footer.php',
    'includes/navbar.php',
    'includes/admin_sidebar.php',
    'admin/index.php',
    'admin/login.php',
    'account/index.php',
];

foreach ($filesToCheck as $file) {
    $fullPath = __DIR__ . '/' . $file;
    if (!file_exists($fullPath)) {
        echo "<code>$file</code>: <span class='fail'>❌ File non trovato</span><br>";
        continue;
    }
    
    // Controlla sintassi con php -l
    $output = [];
    $returnCode = 0;
    @exec("php -l " . escapeshellarg($fullPath) . " 2>&1", $output, $returnCode);
    
    if ($returnCode === 0) {
        echo "<code>$file</code>: <span class='ok'>✅ Sintassi OK</span><br>";
    } else {
        $errorMsg = implode(' ', $output);
        echo "<code>$file</code>: <span class='fail'>❌ ERRORE: " . htmlspecialchars($errorMsg) . "</span><br>";
        $errors[] = "Errore di sintassi in $file: $errorMsg";
    }
}

// Se php -l non è disponibile, prova con token_get_all
echo '<br><em class="warn">Nota: se tutti mostrano "Sintassi OK" ma il sito non va, il problema potrebbe essere runtime (non sintattico).</em>';
echo '</div>';

// ============================================================
// 9. REQUIRE/INCLUDE CHAIN TEST
// ============================================================
echo '<h2>9. 🔗 Test Catena Include</h2><div class="box">';

// Test functions.php caricamento
echo '<strong>Test: functions.php include chain</strong><br>';
$testError = null;
ob_start();
try {
    // Controlla se email_functions.php è richiesto da functions.php
    if (file_exists(__DIR__ . '/functions.php')) {
        $content = file_get_contents(__DIR__ . '/functions.php');
        if (strpos($content, 'email_functions.php') !== false) {
            echo '<span class="ok">✅ functions.php include email_functions.php</span><br>';
            
            if (!file_exists(__DIR__ . '/email_functions.php')) {
                echo '<span class="fail">❌ MA email_functions.php NON ESISTE! Questo causa il crash!</span><br>';
                $errors[] = "functions.php richiede email_functions.php che non esiste - QUESTO È PROBABILMENTE IL PROBLEMA PRINCIPALE";
                $fixes[] = "Caricare email_functions.php nella root del sito, oppure rimuovere la riga require da functions.php se non si vuole il sistema email";
            } else {
                echo '<span class="ok">✅ email_functions.php esiste</span><br>';
            }
        } else {
            echo '<span class="warn">⚠️ functions.php NON include email_functions.php</span><br>';
            $warnings[] = "functions.php non include email_functions.php - le email non funzioneranno";
        }
        
        // Controlla se include config.php
        if (strpos($content, 'config.php') !== false) {
            echo '<span class="ok">✅ functions.php include config.php</span><br>';
        } else {
            echo '<span class="fail">❌ functions.php non include config.php!</span><br>';
            $errors[] = "functions.php non include config.php";
        }
    }
} catch (Throwable $e) {
    echo '<span class="fail">❌ Errore: ' . htmlspecialchars($e->getMessage()) . '</span><br>';
    $errors[] = "Errore nel test include chain: " . $e->getMessage();
}
ob_end_clean();

// Test effettivo di caricamento
echo '<br><strong>Test: caricamento reale di functions.php</strong><br>';
ob_start();
$loadError = null;
try {
    if (!function_exists('isLoggedIn')) {
        if (file_exists(__DIR__ . '/functions.php')) {
            require_once __DIR__ . '/functions.php';
        }
    }
    
    // Verifica funzioni disponibili
    $requiredFunctions = [
        'isLoggedIn', 'isAdmin', 'requireLogin', 'requireAdmin',
        'getCurrentUser', 'getCurrentAdmin', 'hashPassword', 'verifyPassword',
        'generateSerialKey', 'generateUniqueSerial', 'getCart', 'addToCart',
        'getCartCount', 'getCartDetails', 'calculateDiscounts', 'applyCoupon',
        'slugify', 'formatPrice', 'flash', 'uploadImage', 'h', 'dbEsc', 'getDBConnection'
    ];
    
    $emailFunctions = [
        'getEmailSettings', 'getEmailTemplate', 'sendEmail', 'parseTemplate',
        'sendWelcomeEmail', 'sendPasswordResetEmail', 'sendPasswordChangedEmail',
        'sendOrderPlacedEmail', 'sendOrderConfirmedEmail', 'sendOrderRejectedEmail',
        'sendLicenseActivatedEmail', 'isAdminNotificationEnabled', 'getAdminEmail'
    ];
    
    echo '<br><em>Funzioni core:</em><br>';
    foreach ($requiredFunctions as $fn) {
        if (function_exists($fn)) {
            echo "<code>$fn()</code> <span class='ok'>✅</span> ";
        } else {
            echo "<code>$fn()</code> <span class='fail'>❌ MANCANTE</span> ";
            $errors[] = "Funzione $fn() non definita";
        }
    }
    
    echo '<br><br><em>Funzioni email:</em><br>';
    foreach ($emailFunctions as $fn) {
        if (function_exists($fn)) {
            echo "<code>$fn()</code> <span class='ok'>✅</span> ";
        } else {
            echo "<code>$fn()</code> <span class='warn'>⚠️ MANCANTE</span> ";
            $warnings[] = "Funzione email $fn() non definita - email_functions.php mancante o con errori";
        }
    }
    
} catch (Throwable $e) {
    $loadError = $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine();
    echo '<span class="fail">❌ ERRORE CRITICO durante il caricamento: ' . htmlspecialchars($loadError) . '</span><br>';
    $errors[] = "Errore critico caricando functions.php: $loadError";
}
$loadOutput = ob_get_clean();
echo $loadOutput;

echo '</div>';

// ============================================================
// 10. SESSION TEST
// ============================================================
echo '<h2>10. 🍪 Sessioni</h2><div class="box">';
if (session_status() === PHP_SESSION_ACTIVE) {
    echo '<span class="ok">✅ Sessione attiva</span><br>';
    echo 'Session ID: ' . session_id() . '<br>';
    echo 'Session Save Path: ' . (session_save_path() ?: 'default') . '<br>';
} elseif (session_status() === PHP_SESSION_NONE) {
    session_start();
    echo '<span class="ok">✅ Sessione avviata con successo</span><br>';
} else {
    echo '<span class="fail">❌ Sessioni disabilitate</span><br>';
    $errors[] = "Le sessioni PHP sono disabilitate";
}
echo '</div>';

// ============================================================
// 11. URL / SITE ACCESSIBILITY
// ============================================================
echo '<h2>11. 🌐 URL e Accessibilità</h2><div class="box">';

$siteUrl = defined('SITE_URL') ? SITE_URL : 'https://store.airdirector.app';
echo "SITE_URL: <strong>$siteUrl</strong><br>";

$currentUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
echo "URL corrente: <strong>$currentUrl</strong><br>";

$expectedBase = $siteUrl;
$actualBase = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
if (rtrim($actualBase, '/') === rtrim($expectedBase, '/')) {
    echo '<span class="ok">✅ SITE_URL corrisponde al dominio attuale</span><br>';
} else {
    echo '<span class="warn">⚠️ SITE_URL (' . $expectedBase . ') diverso dall\'URL attuale (' . $actualBase . ')</span><br>';
    $warnings[] = "SITE_URL potrebbe non corrispondere al dominio attuale";
}
echo '</div>';

// ============================================================
// SUMMARY
// ============================================================
echo '<h2>📋 RIEPILOGO</h2><div class="box">';

if (empty($errors) && empty($warnings)) {
    echo '<h3 class="ok">✅ TUTTO OK! Nessun problema trovato.</h3>';
} else {
    if (!empty($errors)) {
        echo '<h3 class="fail">❌ ERRORI CRITICI (' . count($errors) . ')</h3>';
        echo '<ol>';
        foreach ($errors as $e) {
            echo '<li class="fail">' . htmlspecialchars($e) . '</li>';
        }
        echo '</ol>';
    }
    
    if (!empty($warnings)) {
        echo '<h3 class="warn">⚠️ AVVISI (' . count($warnings) . ')</h3>';
        echo '<ol>';
        foreach ($warnings as $w) {
            echo '<li class="warn">' . htmlspecialchars($w) . '</li>';
        }
        echo '</ol>';
    }
}

echo '</div>';

// ============================================================
// AUTO-FIX
// ============================================================
if (!empty($fixes) || !empty($errors)) {
    echo '<h2>🔧 Soluzioni Suggerite</h2><div class="box">';
    
    // Fix cartelle mancanti
    $dirFixes = array_filter($errors, function($e) { return strpos($e, 'Cartella mancante') !== false; });
    if (!empty($dirFixes)) {
        echo '<h3>Crea cartelle mancanti</h3>';
        if (isset($_GET['fix_dirs'])) {
            foreach ($requiredDirs as $dir => $desc) {
                $fullPath = __DIR__ . '/' . $dir;
                if (!is_dir($fullPath)) {
                    if (@mkdir($fullPath, 0755, true)) {
                        echo '<span class="ok">✅ Creata: ' . $dir . '/</span><br>';
                    } else {
                        echo '<span class="fail">❌ Impossibile creare: ' . $dir . '/</span><br>';
                    }
                }
            }
            echo '<br><a href="diagnostics.php">🔄 Ricontrolla</a>';
        } else {
            echo '<a href="diagnostics.php?fix_dirs=1" style="color:#38bdf8;font-weight:bold;">👉 Clicca qui per creare automaticamente le cartelle mancanti</a><br>';
        }
    }
    
    // Il problema principale
    $mainProblem = array_filter($errors, function($e) { return strpos($e, 'email_functions.php') !== false; });
    if (!empty($mainProblem)) {
        echo '<h3 class="fail">🚨 PROBLEMA PRINCIPALE TROVATO!</h3>';
        echo '<p>Il file <code>functions.php</code> tenta di includere <code>email_functions.php</code> che non esiste sul server.</p>';
        echo '<p>Questo causa un <strong>fatal error</strong> che blocca TUTTO il sito.</p>';
        echo '<h4>Soluzioni:</h4>';
        echo '<ol>';
        echo '<li><strong>Carica il file</strong> <code>email_functions.php</code> nella root del sito (stesso livello di functions.php)</li>';
        echo '<li><strong>Oppure ripristina temporaneamente</strong> il vecchio functions.php senza la riga <code>require_once email_functions.php</code></li>';
        echo '</ol>';
    }
    
    echo '</div>';
}

// ============================================================
// RAW ERROR LOG
// ============================================================
echo '<h2>12. 📄 Ultimi Errori PHP</h2><div class="box">';
$errorLog = ini_get('error_log');
if ($errorLog && file_exists($errorLog)) {
    $lines = array_slice(file($errorLog), -20);
    echo '<div class="code">';
    foreach ($lines as $line) {
        echo htmlspecialchars($line);
    }
    echo '</div>';
} else {
    echo '<span class="warn">⚠️ Error log non accessibile (path: ' . ($errorLog ?: 'non impostato') . ')</span><br>';
    echo '<p>Prova a verificare gli errori dal pannello Hostinger > File Manager > logs/</p>';
}
echo '</div>';

echo '<hr><p style="text-align:center;color:#64748b;">⚠️ <strong>ELIMINA QUESTO FILE (diagnostics.php) dopo l\'uso!</strong></p>';
echo '</body></html>';
?>