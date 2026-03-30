<?php
/**
 * AirDirector Client - API Autenticazione
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$email    = trim($input['email'] ?? '');
$password = $input['password'] ?? '';
$lang     = $input['lang'] ?? 'it';

if (!$email || !$password) {
    echo json_encode(['success' => false, 'error' => 'missing_fields']);
    exit;
}

// --- Try Owner login (store DB) ---
$storeConn = getStoreDB();
$emailEsc = dbEscStore($email);
$userRow = mysqli_fetch_assoc(mysqli_query($storeConn, "SELECT id, first_name, last_name, email, password, is_active FROM users WHERE email = '$emailEsc' LIMIT 1"));

if ($userRow && verifyPassword($password, $userRow['password'])) {
    if (!$userRow['is_active']) {
        echo json_encode(['success' => false, 'error' => 'account_suspended']);
        exit;
    }

    // Check active client subscriptions
    $uid = (int)$userRow['id'];
    $clientConn = getClientDB();
    $stationsResult = mysqli_query($clientConn, "SELECT * FROM stations WHERE store_user_id = $uid AND is_active = 1");
    $stations = [];
    while ($s = mysqli_fetch_assoc($stationsResult)) {
        $stations[] = $s;
    }

    if (empty($stations)) {
        echo json_encode(['success' => false, 'error' => 'no_active_subscription']);
        exit;
    }

    // Log into first station (or let user choose if multiple)
    $selectedStation = $stations[0];
    if (isset($input['station_id'])) {
        foreach ($stations as $s) {
            if ($s['id'] == (int)$input['station_id']) {
                $selectedStation = $s;
                break;
            }
        }
    }

    $_SESSION['client_user_id'] = $userRow['id'];
    $_SESSION['client_user'] = [
        'id'            => $userRow['id'],
        'name'          => $userRow['first_name'] . ' ' . $userRow['last_name'],
        'display_name'  => $userRow['first_name'] . ' ' . $userRow['last_name'],
        'email'         => $userRow['email'],
        'user_type'     => 'owner',
        'language'      => $lang,
    ];
    $_SESSION['client_station'] = $selectedStation;
    $_SESSION['client_lang'] = $lang;

    $sessionToken = generateSessionToken();
    $_SESSION['client_session_token'] = $sessionToken;

    // Register active session
    $stationId = (int)$selectedStation['id'];
    $ip = dbEscClient($_SERVER['REMOTE_ADDR'] ?? '');
    $tokenEsc = dbEscClient($sessionToken);
    mysqli_query($clientConn, "DELETE FROM active_sessions WHERE store_user_id = $uid AND station_id = $stationId");
    mysqli_query($clientConn, "INSERT INTO active_sessions (station_id, store_user_id, is_admin, session_token, ip_address) VALUES ($stationId, $uid, 0, '$tokenEsc', '$ip')");

    echo json_encode([
        'success' => true,
        'user_type' => 'owner',
        'stations' => $stations,
        'selected_station' => $selectedStation,
        'redirect' => CLIENT_SITE_URL . '/dashboard.php'
    ]);
    exit;
}

// --- Try Subuser login (client DB) ---
$clientConn = getClientDB();
$emailEsc2 = dbEscClient($email);
$suRow = mysqli_fetch_assoc(mysqli_query($clientConn, "SELECT su.*, s.station_name, s.token as station_token FROM station_users su JOIN stations s ON s.id = su.station_id WHERE su.email = '$emailEsc2' AND su.is_active = 1 AND s.is_active = 1 LIMIT 1"));

// --- Auto-repair: if subuser missing in client DB, sync from store DB ---
if (!$suRow) {
    $storeConn2   = getStoreDB();
    $emailEscStore = dbEscStore($email);
    $storeSubuser  = mysqli_fetch_assoc(mysqli_query($storeConn2,
        "SELECT css.*, cs.station_token FROM client_station_subusers css
         JOIN client_subscriptions cs ON cs.id = css.subscription_id
         WHERE css.email = '$emailEscStore' AND css.is_active = 1 AND cs.status = 'active' LIMIT 1"
    ));
    if ($storeSubuser) {
        $repairToken  = dbEscClient($storeSubuser['station_token']);
        $stationRow   = mysqli_fetch_assoc(mysqli_query($clientConn, "SELECT id FROM stations WHERE token = '$repairToken' AND is_active = 1 LIMIT 1"));
        if ($stationRow) {
            $repairStId  = (int)$stationRow['id'];
            $repairName  = dbEscClient($storeSubuser['name']);
            $repairEmail = dbEscClient($storeSubuser['email']);
            $repairHash  = dbEscClient($storeSubuser['password_hash']);
            $repairLang  = dbEscClient($storeSubuser['language'] ?? 'it');
            $repairDays  = dbEscClient(preg_replace('/[^0-9,]/', '', $storeSubuser['access_days'] ?? '1,2,3,4,5,6,7'));
            $repairStart = dbEscClient($storeSubuser['access_time_start'] ?? '00:00:00');
            $repairEnd   = dbEscClient($storeSubuser['access_time_end']   ?? '23:59:59');
            $repairResult = mysqli_query($clientConn,
                "INSERT INTO station_users (station_id, name, email, password_hash, is_active, language, access_days, access_time_start, access_time_end)
                 VALUES ($repairStId, '$repairName', '$repairEmail', '$repairHash', 1, '$repairLang', '$repairDays', '$repairStart', '$repairEnd')
                 ON DUPLICATE KEY UPDATE name='$repairName', password_hash='$repairHash', is_active=1, language='$repairLang', access_days='$repairDays', access_time_start='$repairStart', access_time_end='$repairEnd'"
            );
            if (!$repairResult) {
                error_log("[auth] Auto-repair subuser sync failed for email {$storeSubuser['email']}: " . mysqli_error($clientConn));
            }
            // Re-fetch after sync
            $suRow = mysqli_fetch_assoc(mysqli_query($clientConn,
                "SELECT su.*, s.station_name, s.token as station_token FROM station_users su
                 JOIN stations s ON s.id = su.station_id
                 WHERE su.email = '$emailEsc2' AND su.is_active = 1 AND s.is_active = 1 LIMIT 1"
            ));
        }
    }
}

if ($suRow && verifyPassword($password, $suRow['password_hash'])) {
    // Check access rules
    $now = new DateTime();
    $dayOfWeek = $now->format('N');
    $allowedDays = explode(',', $suRow['access_days'] ?? '1,2,3,4,5,6,7');

    if (!in_array($dayOfWeek, $allowedDays)) {
        echo json_encode(['success' => false, 'error' => 'access_day_denied']);
        exit;
    }

    $currentTime = $now->format('H:i:s');
    $startTime = $suRow['access_time_start'] ?? '00:00:00';
    $endTime   = $suRow['access_time_end']   ?? '23:59:59';
    if ($currentTime < $startTime || $currentTime > $endTime) {
        echo json_encode(['success' => false, 'error' => 'access_time_denied', 'start' => $startTime, 'end' => $endTime]);
        exit;
    }

    $station = [
        'id'           => $suRow['station_id'],
        'station_name' => $suRow['station_name'],
        'token'        => $suRow['station_token'],
    ];

    $_SESSION['client_user_id'] = $suRow['id'];
    $_SESSION['client_user'] = [
        'id'              => $suRow['id'],
        'name'            => $suRow['name'],
        'display_name'    => $suRow['name'],
        'email'           => $suRow['email'],
        'user_type'       => 'subuser',
        'language'        => $suRow['language'] ?? $lang,
        'access_days'     => $suRow['access_days'],
        'access_time_start' => $suRow['access_time_start'],
        'access_time_end'   => $suRow['access_time_end'],
    ];
    $_SESSION['client_station'] = $station;
    $_SESSION['client_lang'] = $suRow['language'] ?? $lang;

    $sessionToken = generateSessionToken();
    $_SESSION['client_session_token'] = $sessionToken;

    $stationId = (int)$suRow['station_id'];
    $suId = (int)$suRow['id'];
    $ip = dbEscClient($_SERVER['REMOTE_ADDR'] ?? '');
    $tokenEsc = dbEscClient($sessionToken);
    mysqli_query($clientConn, "DELETE FROM active_sessions WHERE station_user_id = $suId AND station_id = $stationId");
    mysqli_query($clientConn, "INSERT INTO active_sessions (station_id, station_user_id, is_admin, session_token, ip_address) VALUES ($stationId, $suId, 0, '$tokenEsc', '$ip')");

    echo json_encode([
        'success' => true,
        'user_type' => 'subuser',
        'selected_station' => $station,
        'redirect' => CLIENT_SITE_URL . '/dashboard.php'
    ]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'invalid_credentials']);
