<?php
/**
 * AirDirector Store - Gestione Sottoscrizioni Client (Area Utente)
 */
require_once __DIR__ . '/../functions.php';
requireLogin();

$conn = getDBConnection();
$userId = (int)$_SESSION['user_id'];
$user = getCurrentUser();

$message = '';
$error   = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'subscribe') {
        $planId    = (int)($_POST['plan_id'] ?? 0);
        $radioName = trim($_POST['radio_name'] ?? '');

        if (!$planId || !$radioName) {
            $error = 'Seleziona un piano e inserisci il nome della radio.';
        } else {
            $planEsc = (int)$planId;
            $planRow = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM client_subscription_plans WHERE id = $planEsc AND is_active = 1"));
            if (!$planRow) {
                $error = 'Piano non trovato.';
            } else {
                // Generate unique station token
                do {
                    $token    = bin2hex(random_bytes(32));
                    $tokenEsc = dbEsc($token);
                    $exists   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM client_subscriptions WHERE station_token = '$tokenEsc'"));
                } while ($exists);

                $expiresAt = match($planRow['billing_cycle']) {
                    'monthly'    => date('Y-m-d H:i:s', strtotime('+1 month')),
                    'semiannual' => date('Y-m-d H:i:s', strtotime('+6 months')),
                    'annual'     => date('Y-m-d H:i:s', strtotime('+1 year')),
                    default      => date('Y-m-d H:i:s', strtotime('+1 month')),
                };

                $radioNameEsc = dbEsc($radioName);
                mysqli_query($conn, "
                    INSERT INTO client_subscriptions (user_id, plan_id, radio_name, station_token, status, expires_at)
                    VALUES ($userId, $planEsc, '$radioNameEsc', '$tokenEsc', 'active', '$expiresAt')
                ");
                $subId = mysqli_insert_id($conn);

                // Sync station to client DB
                $clientConn = mysqli_connect(CLIENT_DB_HOST, CLIENT_DB_USER, CLIENT_DB_PASS, CLIENT_DB_NAME, CLIENT_DB_PORT);
                if ($clientConn) {
                    mysqli_set_charset($clientConn, 'utf8mb4');
                    $cRadioName = mysqli_real_escape_string($clientConn, $radioName);
                    $cToken     = mysqli_real_escape_string($clientConn, $token);
                    mysqli_query($clientConn, "
                        INSERT INTO stations (store_subscription_id, store_user_id, station_name, token, is_active)
                        VALUES ($subId, $userId, '$cRadioName', '$cToken', 1)
                        ON DUPLICATE KEY UPDATE station_name = '$cRadioName'
                    ");
                    mysqli_close($clientConn);
                }

                $message = 'Sottoscrizione attivata con successo! Il tuo token è: <code>' . h($token) . '</code>';
            }
        }
    } elseif ($action === 'add_subuser') {
        $subId   = (int)($_POST['subscription_id'] ?? 0);
        $suName  = trim($_POST['su_name'] ?? '');
        $suEmail = trim($_POST['su_email'] ?? '');
        $suPass  = $_POST['su_password'] ?? '';
        $suLang  = in_array($_POST['su_language'] ?? '', ['it','en','es','de','fr','pt']) ? $_POST['su_language'] : 'it';
        $suDays  = trim($_POST['access_days'] ?? '1,2,3,4,5,6,7');
        $suStart = $_POST['access_time_start'] ?? '00:00:00';
        $suEnd   = $_POST['access_time_end']   ?? '23:59:59';

        if (!$suName || !$suEmail || !$suPass) {
            $error = 'Nome, email e password sono obbligatori.';
        } else {
            $subRow = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM client_subscriptions WHERE id = $subId AND user_id = $userId"));
            if (!$subRow) {
                $error = 'Sottoscrizione non trovata.';
            } else {
                $suEmailEsc = dbEsc($suEmail);
                $dupCheck   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM client_station_subusers WHERE email = '$suEmailEsc' AND subscription_id = $subId"));
                if ($dupCheck) {
                    $error = 'Email già registrata per questa stazione.';
                } else {
                    $hash     = hashPassword($suPass);
                    $nameEsc  = dbEsc($suName);
                    $hashEsc  = dbEsc($hash);
                    $langEsc  = dbEsc($suLang);
                    $daysEsc  = dbEsc(preg_replace('/[^0-9,]/', '', $suDays));
                    $startEsc = dbEsc($suStart);
                    $endEsc   = dbEsc($suEnd);

                    mysqli_query($conn, "
                        INSERT INTO client_station_subusers
                        (subscription_id, name, email, password_hash, is_active, language, access_days, access_time_start, access_time_end)
                        VALUES ($subId, '$nameEsc', '$suEmailEsc', '$hashEsc', 1, '$langEsc', '$daysEsc', '$startEsc', '$endEsc')
                    ");

                    // Sync subuser to client DB
                    $clientConn = mysqli_connect(CLIENT_DB_HOST, CLIENT_DB_USER, CLIENT_DB_PASS, CLIENT_DB_NAME, CLIENT_DB_PORT);
                    if ($clientConn) {
                        mysqli_set_charset($clientConn, 'utf8mb4');
                        $tokenRow = mysqli_fetch_assoc(mysqli_query($conn, "SELECT station_token FROM client_subscriptions WHERE id = $subId"));
                        if ($tokenRow) {
                            $cToken = mysqli_real_escape_string($clientConn, $tokenRow['station_token']);
                            $stRow  = mysqli_fetch_assoc(mysqli_query($clientConn, "SELECT id FROM stations WHERE token = '$cToken'"));
                            if ($stRow) {
                                $stId   = (int)$stRow['id'];
                                $cName  = mysqli_real_escape_string($clientConn, $suName);
                                $cEmail = mysqli_real_escape_string($clientConn, $suEmail);
                                $cHash  = mysqli_real_escape_string($clientConn, $hash);
                                $cLang  = mysqli_real_escape_string($clientConn, $suLang);
                                $cDays  = mysqli_real_escape_string($clientConn, preg_replace('/[^0-9,]/', '', $suDays));
                                $cStart = mysqli_real_escape_string($clientConn, $suStart);
                                $cEnd   = mysqli_real_escape_string($clientConn, $suEnd);
                                mysqli_query($clientConn, "
                                    INSERT INTO station_users (station_id, name, email, password_hash, is_active, language, access_days, access_time_start, access_time_end)
                                    VALUES ($stId, '$cName', '$cEmail', '$cHash', 1, '$cLang', '$cDays', '$cStart', '$cEnd')
                                    ON DUPLICATE KEY UPDATE name='$cName', password_hash='$cHash', language='$cLang', access_days='$cDays', access_time_start='$cStart', access_time_end='$cEnd'
                                ");
                            }
                        }
                        mysqli_close($clientConn);
                    }
                    $message = 'Sottoutente aggiunto con successo.';
                }
            }
        }
    } elseif ($action === 'toggle_subuser') {
        $suId  = (int)($_POST['subuser_id'] ?? 0);
        $subId = (int)($_POST['subscription_id'] ?? 0);
        $subCheck = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM client_subscriptions WHERE id = $subId AND user_id = $userId"));
        if ($subCheck) {
            mysqli_query($conn, "UPDATE client_station_subusers SET is_active = 1 - is_active WHERE id = $suId AND subscription_id = $subId");
            $message = 'Sottoutente aggiornato.';
        }
    } elseif ($action === 'delete_subuser') {
        $suId  = (int)($_POST['subuser_id'] ?? 0);
        $subId = (int)($_POST['subscription_id'] ?? 0);
        $subCheck = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM client_subscriptions WHERE id = $subId AND user_id = $userId"));
        if ($subCheck) {
            mysqli_query($conn, "DELETE FROM client_station_subusers WHERE id = $suId AND subscription_id = $subId");
            $message = 'Sottoutente eliminato.';
        }
    } elseif ($action === 'update_reminders') {
        $subId = (int)($_POST['subscription_id'] ?? 0);
        $r30   = isset($_POST['reminder_30']) ? 1 : 0;
        $r7    = isset($_POST['reminder_7'])  ? 1 : 0;
        $r48   = isset($_POST['reminder_48']) ? 1 : 0;
        $subCheck = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM client_subscriptions WHERE id = $subId AND user_id = $userId"));
        if ($subCheck) {
            mysqli_query($conn, "UPDATE client_subscriptions SET reminder_30=$r30, reminder_7=$r7, reminder_48=$r48 WHERE id = $subId");
            $message = 'Preferenze notifiche aggiornate.';
        }
    }
}

// Fetch user subscriptions
$subscriptions = [];
$subsResult = mysqli_query($conn, "
    SELECT cs.*, p.name as plan_name, p.billing_cycle, p.price
    FROM client_subscriptions cs
    JOIN client_subscription_plans p ON p.id = cs.plan_id
    WHERE cs.user_id = $userId
    ORDER BY cs.created_at DESC
");
while ($s = mysqli_fetch_assoc($subsResult)) {
    $sid = (int)$s['id'];
    $suResult = mysqli_query($conn, "SELECT * FROM client_station_subusers WHERE subscription_id = $sid ORDER BY name");
    $s['subusers'] = [];
    while ($su = mysqli_fetch_assoc($suResult)) {
        $s['subusers'][] = $su;
    }
    $subscriptions[] = $s;
}

// Available plans for purchase
$plans = mysqli_query($conn, "SELECT * FROM client_subscription_plans WHERE is_active = 1 ORDER BY sort_order");
$billingLabels = ['monthly' => 'Mensile', 'semiannual' => 'Semestrale', 'annual' => 'Annuale'];
$dayNames = ['1'=>'Lun','2'=>'Mar','3'=>'Mer','4'=>'Gio','5'=>'Ven','6'=>'Sab','7'=>'Dom'];

define('PAGE_TITLE', 'AirDirector Client - Sottoscrizioni');
include __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <h1 class="section-title"><i class="bi bi-broadcast me-2"></i>AirDirector Client</h1>
    <p class="text-muted mb-4">Gestisci le tue stazioni radio remote e i sottoutenti.</p>

    <?php if ($message): ?>
    <div class="alert alert-success"><i class="bi bi-check-circle me-2"></i><?= $message ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i><?= h($error) ?></div>
    <?php endif; ?>

    <!-- Active Subscriptions -->
    <?php if (!empty($subscriptions)): ?>
    <h4 class="fw-bold mb-3">Le mie Stazioni</h4>
    <?php foreach ($subscriptions as $sub): ?>
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0 fw-bold"><i class="bi bi-broadcast me-2"></i><?= h($sub['radio_name']) ?></h5>
                <small class="text-muted"><?= h($sub['plan_name']) ?> — <?= formatPrice($sub['price']) ?>/<?= $billingLabels[$sub['billing_cycle']] ?? $sub['billing_cycle'] ?></small>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-<?= $sub['status'] === 'active' ? 'success' : ($sub['status'] === 'expired' ? 'danger' : 'warning') ?>">
                    <?= ucfirst($sub['status']) ?>
                </span>
                <?php if ($sub['status'] === 'active'): ?>
                <a href="https://client.airdirector.app" target="_blank" class="btn btn-sm btn-primary">
                    <i class="bi bi-box-arrow-up-right me-1"></i>Apri Client
                </a>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body">
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Token AirDirector</label>
                    <div class="input-group">
                        <input type="text" class="form-control font-monospace" value="<?= h($sub['station_token']) ?>" id="token_<?= $sub['id'] ?>" readonly>
                        <button class="btn btn-outline-secondary" onclick="copyToken('<?= h($sub['station_token']) ?>')" title="Copia">
                            <i class="bi bi-clipboard"></i>
                        </button>
                    </div>
                    <small class="text-muted">Incolla questo token in AirDirector → Remote Control</small>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Scadenza</label>
                    <div class="<?= strtotime($sub['expires_at']) < time() ? 'text-danger' : (strtotime($sub['expires_at']) < strtotime('+30 days') ? 'text-warning' : 'text-success') ?> fw-bold">
                        <?= date('d/m/Y', strtotime($sub['expires_at'])) ?>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Notifiche Scadenza</label>
                    <form method="post" class="d-flex flex-column gap-1">
                        <input type="hidden" name="action" value="update_reminders">
                        <input type="hidden" name="subscription_id" value="<?= $sub['id'] ?>">
                        <div class="form-check">
                            <input type="checkbox" name="reminder_30" id="r30_<?= $sub['id'] ?>" class="form-check-input" <?= $sub['reminder_30'] ? 'checked' : '' ?> onchange="this.form.submit()">
                            <label class="form-check-label small" for="r30_<?= $sub['id'] ?>">30 giorni prima</label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" name="reminder_7" id="r7_<?= $sub['id'] ?>" class="form-check-input" <?= $sub['reminder_7'] ? 'checked' : '' ?> onchange="this.form.submit()">
                            <label class="form-check-label small" for="r7_<?= $sub['id'] ?>">7 giorni prima</label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" name="reminder_48" id="r48_<?= $sub['id'] ?>" class="form-check-input" <?= $sub['reminder_48'] ? 'checked' : '' ?> onchange="this.form.submit()">
                            <label class="form-check-label small" for="r48_<?= $sub['id'] ?>">48 ore prima</label>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Subusers -->
            <hr>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0"><i class="bi bi-people me-1"></i>Sottoutenti (<?= count($sub['subusers']) ?>)</h6>
                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#addSubuser_<?= $sub['id'] ?>">
                    <i class="bi bi-person-plus me-1"></i>Aggiungi
                </button>
            </div>

            <!-- Add Subuser Form -->
            <div class="collapse mb-3" id="addSubuser_<?= $sub['id'] ?>">
                <div class="card card-body bg-light">
                    <form method="post">
                        <input type="hidden" name="action" value="add_subuser">
                        <input type="hidden" name="subscription_id" value="<?= $sub['id'] ?>">
                        <div class="row g-2">
                            <div class="col-md-3">
                                <input type="text" name="su_name" placeholder="Nome *" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-md-3">
                                <input type="email" name="su_email" placeholder="Email *" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-md-2">
                                <input type="password" name="su_password" placeholder="Password *" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-md-2">
                                <select name="su_language" class="form-select form-select-sm">
                                    <option value="it">Italiano</option>
                                    <option value="en">English</option>
                                    <option value="es">Español</option>
                                    <option value="de">Deutsch</option>
                                    <option value="fr">Français</option>
                                    <option value="pt">Português</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-sm btn-primary w-100">Aggiungi</button>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Giorni accesso</label>
                                <div class="d-flex flex-wrap gap-1">
                                    <?php foreach ($dayNames as $num => $name): ?>
                                    <div class="form-check form-check-inline">
                                        <input type="checkbox" id="day_<?= $sub['id'] ?>_<?= $num ?>" value="<?= $num ?>" class="form-check-input day-check-<?= $sub['id'] ?>" checked>
                                        <label for="day_<?= $sub['id'] ?>_<?= $num ?>" class="form-check-label small"><?= $name ?></label>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <input type="hidden" name="access_days" id="access_days_input_<?= $sub['id'] ?>" value="1,2,3,4,5,6,7">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Orario accesso</label>
                                <div class="d-flex align-items-center gap-2">
                                    <input type="time" name="access_time_start" value="00:00" class="form-control form-control-sm">
                                    <span>-</span>
                                    <input type="time" name="access_time_end" value="23:59" class="form-control form-control-sm">
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Subusers Table -->
            <?php if (!empty($sub['subusers'])): ?>
            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead><tr><th>Nome</th><th>Email</th><th>Accesso</th><th>Lingua</th><th>Stato</th><th></th></tr></thead>
                    <tbody>
                        <?php foreach ($sub['subusers'] as $su): ?>
                        <tr>
                            <td><?= h($su['name']) ?></td>
                            <td class="small"><?= h($su['email']) ?></td>
                            <td class="small text-muted">
                                <?= h($su['access_time_start']) ?> - <?= h($su['access_time_end']) ?>
                                <br><span style="font-size:0.75em">
                                    <?php
                                    $days = explode(',', $su['access_days']);
                                    echo implode(' ', array_map(fn($d) => $dayNames[$d] ?? '', $days));
                                    ?>
                                </span>
                            </td>
                            <td><?= strtoupper(h($su['language'])) ?></td>
                            <td><span class="badge bg-<?= $su['is_active'] ? 'success' : 'secondary' ?>"><?= $su['is_active'] ? 'Attivo' : 'Sospeso' ?></span></td>
                            <td>
                                <div class="d-flex gap-1">
                                    <form method="post">
                                        <input type="hidden" name="action" value="toggle_subuser">
                                        <input type="hidden" name="subuser_id" value="<?= $su['id'] ?>">
                                        <input type="hidden" name="subscription_id" value="<?= $sub['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-warning py-0" title="<?= $su['is_active'] ? 'Sospendi' : 'Riattiva' ?>">
                                            <i class="bi bi-<?= $su['is_active'] ? 'pause' : 'play' ?>"></i>
                                        </button>
                                    </form>
                                    <form method="post" onsubmit="return confirm('Eliminare questo sottoutente?')">
                                        <input type="hidden" name="action" value="delete_subuser">
                                        <input type="hidden" name="subuser_id" value="<?= $su['id'] ?>">
                                        <input type="hidden" name="subscription_id" value="<?= $sub['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger py-0" title="Elimina">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <p class="text-muted small">Nessun sottoutente. Aggiungi i tuoi speaker!</p>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>

    <!-- New Subscription -->
    <h4 class="fw-bold mb-3 mt-4"><i class="bi bi-plus-circle me-2"></i>Attiva Nuova Stazione</h4>
    <div class="card">
        <div class="card-body">
            <form method="post">
                <input type="hidden" name="action" value="subscribe">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Nome Radio *</label>
                        <input type="text" name="radio_name" class="form-control" placeholder="Es: Radio Sole" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Piano *</label>
                        <div class="row g-2">
                            <?php while ($plan = mysqli_fetch_assoc($plans)): ?>
                            <div class="col-md-4">
                                <div class="card h-100 plan-card" style="cursor:pointer" onclick="document.getElementById('plan_<?= $plan['id'] ?>').checked=true;document.querySelectorAll('.plan-card').forEach(c=>c.classList.remove('border-primary'));this.classList.add('border-primary')">
                                    <div class="card-body p-2 text-center">
                                        <input type="radio" name="plan_id" id="plan_<?= $plan['id'] ?>" value="<?= $plan['id'] ?>" required class="d-none">
                                        <div class="fw-bold small"><?= $billingLabels[$plan['billing_cycle']] ?></div>
                                        <div class="fs-5 fw-bold text-primary"><?= formatPrice($plan['price']) ?></div>
                                        <?php if ($plan['description']): ?>
                                        <div class="text-muted" style="font-size:0.7rem"><?= h($plan['description']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-broadcast me-1"></i>Attiva
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function copyToken(token) {
    navigator.clipboard.writeText(token).then(() => {
        alert('Token copiato negli appunti!');
    }).catch(() => {
        const el = document.createElement('textarea');
        el.value = token;
        document.body.appendChild(el);
        el.select();
        document.execCommand('copy');
        document.body.removeChild(el);
        alert('Token copiato!');
    });
}

// Sync day checkboxes → hidden input for each subscription form
document.querySelectorAll('[class*="day-check-"]').forEach(function(cb) {
    var match = cb.className.match(/day-check-(\d+)/);
    if (!match) return;
    var subId = match[1];
    cb.addEventListener('change', function() {
        var days = [];
        document.querySelectorAll('.day-check-' + subId + ':checked').forEach(function(c) {
            days.push(c.value);
        });
        var hidden = document.getElementById('access_days_input_' + subId);
        if (hidden) hidden.value = days.sort(function(a,b){return a-b;}).join(',');
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
