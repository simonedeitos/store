<?php
// File temporaneo per resettare password admin
// ELIMINARE DOPO L'USO!
require_once __DIR__ . '/config.php';
$conn = getDBConnection();
$hash = password_hash('Admin2025!', PASSWORD_BCRYPT);

// Aggiorna o inserisci admin
$check = mysqli_query($conn, "SELECT id FROM admins WHERE username = 'admin'");
if (mysqli_num_rows($check) > 0) {
    mysqli_query($conn, "UPDATE admins SET password = '" . mysqli_real_escape_string($conn, $hash) . "' WHERE username = 'admin'");
    echo "✅ Password admin aggiornata!<br>";
} else {
    mysqli_query($conn, "INSERT INTO admins (username, email, password) VALUES ('admin', 'admin@airdirector.app', '" . mysqli_real_escape_string($conn, $hash) . "')");
    echo "✅ Admin creato!<br>";
}

// Rigenera API Key
$apiKey = bin2hex(random_bytes(32));
$checkApi = mysqli_query($conn, "SELECT id FROM api_settings LIMIT 1");
if (mysqli_num_rows($checkApi) > 0) {
    mysqli_query($conn, "UPDATE api_settings SET api_key = '$apiKey'");
} else {
    mysqli_query($conn, "INSERT INTO api_settings (api_key) VALUES ('$apiKey')");
}

echo "✅ API Key: <strong>$apiKey</strong><br>";
echo "<br>Username: <strong>admin</strong><br>Password: <strong>Admin2025!</strong>";
echo "<br><br>⚠️ ELIMINA QUESTO FILE SUBITO!";
?>