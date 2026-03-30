<?php
/**
 * AirDirector Client - Login
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

// Redirect if already logged in
if (isClientLoggedIn()) {
    header('Location: ' . CLIENT_SITE_URL . '/dashboard.php');
    exit;
}

$error = '';
$loggedOut = isset($_GET['logged_out']);
$sessionExpired = isset($_GET['error']) && $_GET['error'] === 'session_expired';
$accessDenied = isset($_GET['error']) && $_GET['error'] === 'access_denied';
?>
<!DOCTYPE html>
<html lang="it" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= CLIENT_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= CLIENT_SITE_URL ?>/assets/css/client.css" rel="stylesheet">
</head>
<body class="login-page">

<div class="login-container">
    <div class="login-card">
        <div class="login-logo">
            <i class="bi bi-broadcast-pin"></i>
            <h2><?= CLIENT_NAME ?></h2>
            <p class="text-muted" data-lang="login.subtitle">Remote Broadcasting Control</p>
        </div>

        <?php if ($loggedOut): ?>
            <div class="alert alert-success"><i class="bi bi-check-circle me-2"></i><span data-lang="login.logged_out">Logout effettuato con successo.</span></div>
        <?php endif; ?>
        <?php if ($sessionExpired): ?>
            <div class="alert alert-warning"><i class="bi bi-clock me-2"></i><span data-lang="login.session_expired">Sessione scaduta, effettua nuovamente il login.</span></div>
        <?php endif; ?>
        <?php if ($accessDenied): ?>
            <div class="alert alert-danger"><i class="bi bi-shield-x me-2"></i><span data-lang="login.access_denied">Accesso negato per orario o giorno non consentito.</span></div>
        <?php endif; ?>

        <div id="loginAlert" class="alert d-none"></div>

        <form id="loginForm" autocomplete="off">
            <div class="mb-3">
                <label for="loginEmail" class="form-label" data-lang="login.email">Email</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input type="email" class="form-control" id="loginEmail" name="email" required autofocus>
                </div>
            </div>
            <div class="mb-3">
                <label for="loginPassword" class="form-label" data-lang="login.password">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" class="form-control" id="loginPassword" name="password" required>
                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </div>

            <!-- Station selector (shown if owner has multiple stations) -->
            <div id="stationSelector" class="mb-3 d-none">
                <label for="stationSelect" class="form-label" data-lang="login.select_station">Seleziona Stazione</label>
                <select class="form-select" id="stationSelect" name="station_id"></select>
            </div>

            <div class="mb-3">
                <label for="langSelect" class="form-label" data-lang="login.language">Lingua</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-translate"></i></span>
                    <select class="form-select" id="langSelect" name="lang">
                        <!-- Populated by JS -->
                    </select>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 btn-login" id="loginBtn">
                <i class="bi bi-box-arrow-in-right me-2"></i>
                <span data-lang="login.submit">Accedi</span>
            </button>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= CLIENT_SITE_URL ?>/assets/js/language.js"></script>
<script>
const CLIENT_CONFIG = { siteUrl: '<?= CLIENT_SITE_URL ?>', lang: 'it' };

// Load languages
async function loadLanguages() {
    try {
        const res = await fetch('<?= CLIENT_SITE_URL ?>/api/languages.php');
        const data = await res.json();
        const select = document.getElementById('langSelect');
        const savedLang = localStorage.getItem('adc_lang') || 'it';
        if (data.languages) {
            data.languages.forEach(l => {
                const opt = document.createElement('option');
                opt.value = l.code;
                opt.textContent = l.name;
                if (l.code === savedLang) opt.selected = true;
                select.appendChild(opt);
            });
        }
        // Apply lang
        if (window.LanguageManager) {
            window.LanguageManager.load(savedLang);
        }
    } catch(e) {}
}

document.addEventListener('DOMContentLoaded', () => {
    loadLanguages();

    // Toggle password
    document.getElementById('togglePassword').addEventListener('click', function() {
        const pw = document.getElementById('loginPassword');
        const icon = this.querySelector('i');
        if (pw.type === 'password') {
            pw.type = 'text';
            icon.className = 'bi bi-eye-slash';
        } else {
            pw.type = 'password';
            icon.className = 'bi bi-eye';
        }
    });

    // Language change
    document.getElementById('langSelect').addEventListener('change', function() {
        localStorage.setItem('adc_lang', this.value);
        if (window.LanguageManager) {
            window.LanguageManager.load(this.value);
        }
    });

    // Login form
    let loginStations = [];
    document.getElementById('loginForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = document.getElementById('loginBtn');
        const alert = document.getElementById('loginAlert');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Accesso...';
        alert.className = 'alert d-none';

        const formData = {
            email: document.getElementById('loginEmail').value,
            password: document.getElementById('loginPassword').value,
            lang: document.getElementById('langSelect').value,
        };

        const stationSelect = document.getElementById('stationSelect');
        if (stationSelect.value) {
            formData.station_id = stationSelect.value;
        }

        try {
            const res = await fetch('<?= CLIENT_SITE_URL ?>/api/auth.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData)
            });
            const data = await res.json();

            if (data.success) {
                window.location.href = data.redirect;
            } else if (data.stations && data.stations.length > 1) {
                // Show station selector
                loginStations = data.stations;
                stationSelect.innerHTML = '';
                data.stations.forEach(s => {
                    const opt = document.createElement('option');
                    opt.value = s.id;
                    opt.textContent = s.station_name;
                    stationSelect.appendChild(opt);
                });
                document.getElementById('stationSelector').classList.remove('d-none');
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-box-arrow-in-right me-2"></i><span data-lang="login.submit">Accedi</span>';
            } else {
                const errors = {
                    'missing_fields': 'Compila tutti i campi.',
                    'invalid_credentials': window.LanguageManager ? window.LanguageManager.get('login.error_credentials') : 'Credenziali non valide.',
                    'account_suspended': window.LanguageManager ? window.LanguageManager.get('login.error_suspended') : 'Account sospeso.',
                    'no_active_subscription': window.LanguageManager ? window.LanguageManager.get('login.error_no_subscription') : 'Nessuna sottoscrizione attiva.',
                    'access_day_denied': window.LanguageManager ? window.LanguageManager.get('login.error_access_day') : 'Accesso non consentito in questo giorno.',
                    'access_time_denied': window.LanguageManager ? window.LanguageManager.get('login.error_access_time') : `Accesso consentito solo dalle ${data.start || ''} alle ${data.end || ''}.`,
                };
                alert.className = 'alert alert-danger';
                alert.innerHTML = '<i class="bi bi-exclamation-triangle me-2"></i>' + (errors[data.error] || data.error);
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-box-arrow-in-right me-2"></i><span data-lang="login.submit">Accedi</span>';
            }
        } catch(err) {
            alert.className = 'alert alert-danger';
            alert.textContent = 'Errore di connessione. Riprova.';
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-box-arrow-in-right me-2"></i><span data-lang="login.submit">Accedi</span>';
        }
    });
});
</script>
</body>
</html>
