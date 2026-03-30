<?php
/**
 * AirDirector Store - Email Functions
 * Posizione: /store/email_functions.php (root del sito)
 */

// ============================================================
// SMTP EMAIL SENDER
// ============================================================

function getEmailSettings() {
    $conn = getDBConnection();
    $r = mysqli_query($conn, "SELECT * FROM email_settings ORDER BY id DESC LIMIT 1");
    return mysqli_fetch_assoc($r);
}

function getEmailTemplate($templateKey) {
    $conn = getDBConnection();
    $key = dbEsc($templateKey);
    $r = mysqli_query($conn, "SELECT * FROM email_templates WHERE template_key = '$key' AND is_active = 1");
    return mysqli_fetch_assoc($r);
}

function isAdminNotificationEnabled($notificationKey) {
    $conn = getDBConnection();
    $key = dbEsc($notificationKey);
    $r = mysqli_query($conn, "SELECT is_enabled FROM admin_notifications WHERE notification_key = '$key'");
    $row = mysqli_fetch_assoc($r);
    return $row && $row['is_enabled'] == 1;
}

function parseTemplate($html, $variables = []) {
    foreach ($variables as $key => $value) {
        $html = str_replace('{{' . $key . '}}', $value ?? '', $html);
    }
    $html = preg_replace_callback('/\{\{#(\w+)\}\}(.*?)\{\{\/\1\}\}/s', function($matches) use ($variables) {
        $var = $matches[1];
        $content = $matches[2];
        if (!empty($variables[$var])) {
            foreach ($variables as $k => $v) {
                $content = str_replace('{{' . $k . '}}', $v ?? '', $content);
            }
            return $content;
        }
        return '';
    }, $html);
    return $html;
}

function sendEmail($toEmail, $toName, $templateKey, $variables = []) {
    $settings = getEmailSettings();
    $template = getEmailTemplate($templateKey);

    if (!$template) {
        logEmail($toEmail, $toName, "Template '$templateKey' non trovato", $templateKey, 'failed', "Template non trovato");
        return false;
    }

    if (!$settings || !$settings['is_active'] || empty($settings['smtp_host'])) {
        logEmail($toEmail, $toName, $template['subject'], $templateKey, 'failed', "SMTP non configurato o disattivo");
        return false;
    }

    $variables['site_name'] = defined('SITE_NAME') ? SITE_NAME : 'AirDirector Store';
    $variables['site_url'] = SITE_URL;
    $variables['year'] = date('Y');
    $variables['date'] = date('d/m/Y');
    $variables['time'] = date('H:i');

    $subject = parseTemplate($template['subject'], $variables);
    $body = parseTemplate($template['body_html'], $variables);

    $fullHtml = '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"></head><body style="margin:0;padding:20px;background:#f1f5f9;">' . $body . '</body></html>';

    $result = smtpSend(
        $settings['smtp_host'],
        (int)$settings['smtp_port'],
        $settings['smtp_username'],
        $settings['smtp_password'],
        $settings['smtp_encryption'],
        $settings['from_email'],
        $settings['from_name'],
        $toEmail,
        $toName,
        $subject,
        $fullHtml,
        $settings['reply_to_email'],
        $settings['reply_to_name']
    );

    if ($result === true) {
        logEmail($toEmail, $toName, $subject, $templateKey, 'sent');
        return true;
    } else {
        logEmail($toEmail, $toName, $subject, $templateKey, 'failed', $result);
        return false;
    }
}

function smtpSend($host, $port, $username, $password, $encryption, $fromEmail, $fromName, $toEmail, $toName, $subject, $htmlBody, $replyToEmail = null, $replyToName = null) {
    try {
        $timeout = 30;

        if ($encryption === 'ssl') {
            $socket = @fsockopen("ssl://$host", $port, $errno, $errstr, $timeout);
        } else {
            $socket = @fsockopen($host, $port, $errno, $errstr, $timeout);
        }

        if (!$socket) {
            return "Connessione fallita: $errstr ($errno)";
        }

        stream_set_timeout($socket, $timeout);
        $response = fgets($socket, 512);
        if (substr($response, 0, 3) !== '220') return "Server non pronto: $response";

        fwrite($socket, "EHLO " . gethostname() . "\r\n");
        $response = smtpReadResponse($socket);

        if ($encryption === 'tls') {
            fwrite($socket, "STARTTLS\r\n");
            $response = fgets($socket, 512);
            if (substr($response, 0, 3) !== '220') return "STARTTLS fallito: $response";

            $crypto = stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT | STREAM_CRYPTO_METHOD_TLS_CLIENT);
            if (!$crypto) return "Errore crittografia TLS";

            fwrite($socket, "EHLO " . gethostname() . "\r\n");
            $response = smtpReadResponse($socket);
        }

        if ($username && $password) {
            fwrite($socket, "AUTH LOGIN\r\n");
            $response = fgets($socket, 512);
            if (substr($response, 0, 3) !== '334') return "AUTH fallito: $response";

            fwrite($socket, base64_encode($username) . "\r\n");
            $response = fgets($socket, 512);
            if (substr($response, 0, 3) !== '334') return "Username rifiutato: $response";

            fwrite($socket, base64_encode($password) . "\r\n");
            $response = fgets($socket, 512);
            if (substr($response, 0, 3) !== '235') return "Password rifiutata: $response";
        }

        fwrite($socket, "MAIL FROM:<$fromEmail>\r\n");
        $response = fgets($socket, 512);
        if (substr($response, 0, 3) !== '250') return "MAIL FROM rifiutato: $response";

        fwrite($socket, "RCPT TO:<$toEmail>\r\n");
        $response = fgets($socket, 512);
        if (substr($response, 0, 3) !== '250') return "RCPT TO rifiutato: $response";

        fwrite($socket, "DATA\r\n");
        $response = fgets($socket, 512);
        if (substr($response, 0, 3) !== '354') return "DATA rifiutato: $response";

        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Date: " . date('r') . "\r\n";
        $headers .= "From: " . mb_encode_mimeheader($fromName, 'UTF-8') . " <$fromEmail>\r\n";
        $headers .= "To: " . mb_encode_mimeheader($toName ?: $toEmail, 'UTF-8') . " <$toEmail>\r\n";
        $headers .= "Subject: " . mb_encode_mimeheader($subject, 'UTF-8') . "\r\n";
        if ($replyToEmail) {
            $headers .= "Reply-To: " . mb_encode_mimeheader($replyToName ?: $replyToEmail, 'UTF-8') . " <$replyToEmail>\r\n";
        }
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "Content-Transfer-Encoding: base64\r\n";
        $headers .= "\r\n";
        $headers .= chunk_split(base64_encode($htmlBody));
        $headers .= "\r\n.\r\n";

        fwrite($socket, $headers);
        $response = fgets($socket, 512);
        if (substr($response, 0, 3) !== '250') return "Invio fallito: $response";

        fwrite($socket, "QUIT\r\n");
        fclose($socket);

        return true;

    } catch (Exception $e) {
        return "Eccezione: " . $e->getMessage();
    }
}

function smtpReadResponse($socket) {
    $response = '';
    while ($line = fgets($socket, 512)) {
        $response .= $line;
        if (substr($line, 3, 1) === ' ') break;
    }
    return $response;
}

function logEmail($toEmail, $toName, $subject, $templateKey, $status, $errorMessage = null) {
    $conn = getDBConnection();
    $stmt = mysqli_prepare($conn, "INSERT INTO email_log (to_email, to_name, subject, template_key, status, error_message) VALUES (?, ?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, 'ssssss', $toEmail, $toName, $subject, $templateKey, $status, $errorMessage);
    mysqli_stmt_execute($stmt);
}

// ============================================================
// CONVENIENCE FUNCTIONS
// ============================================================

function sendWelcomeEmail($user) {
    sendEmail($user['email'], $user['first_name'] . ' ' . $user['last_name'], 'user_welcome', [
        'first_name' => $user['first_name'],
        'last_name' => $user['last_name'],
        'email' => $user['email'],
        'company' => $user['company'] ?? ''
    ]);
    if (isAdminNotificationEnabled('new_user')) {
        $adminEmail = getAdminEmail();
        if ($adminEmail) {
            sendEmail($adminEmail, 'Admin', 'admin_new_user', [
                'customer_name' => $user['first_name'] . ' ' . $user['last_name'],
                'customer_email' => $user['email'],
                'customer_company' => $user['company'] ?? 'N/A',
                'customer_vat' => $user['vat_id'] ?? 'N/A'
            ]);
        }
    }
}

function sendPasswordResetEmail($user, $resetLink) {
    sendEmail($user['email'], $user['first_name'] . ' ' . $user['last_name'], 'password_reset', [
        'first_name' => $user['first_name'],
        'last_name' => $user['last_name'],
        'email' => $user['email'],
        'reset_link' => $resetLink
    ]);
}

function sendPasswordChangedEmail($user) {
    sendEmail($user['email'], $user['first_name'] . ' ' . $user['last_name'], 'password_changed', [
        'first_name' => $user['first_name'],
        'last_name' => $user['last_name'],
        'email' => $user['email']
    ]);
}

function sendOrderPlacedEmail($user, $order, $itemsHtml) {
    sendEmail($user['email'], $user['first_name'] . ' ' . $user['last_name'], 'order_placed', [
        'first_name' => $user['first_name'],
        'last_name' => $user['last_name'],
        'email' => $user['email'],
        'order_id' => $order['id'],
        'order_items' => $itemsHtml,
        'order_total' => formatPrice($order['total'])
    ]);
    if (isAdminNotificationEnabled('new_order')) {
        $adminEmail = getAdminEmail();
        if ($adminEmail) {
            sendEmail($adminEmail, 'Admin', 'admin_new_order', [
                'order_id' => $order['id'],
                'customer_name' => $user['first_name'] . ' ' . $user['last_name'],
                'customer_email' => $user['email'],
                'customer_company' => $user['company'] ?? 'N/A',
                'order_items' => $itemsHtml,
                'order_total' => formatPrice($order['total'])
            ]);
        }
    }
}

function sendOrderConfirmedEmail($user, $order, $licenseListHtml, $downloadListHtml) {
    sendEmail($user['email'], $user['first_name'] . ' ' . $user['last_name'], 'order_confirmed', [
        'first_name' => $user['first_name'],
        'last_name' => $user['last_name'],
        'email' => $user['email'],
        'order_id' => $order['id'],
        'license_list' => $licenseListHtml,
        'download_list' => $downloadListHtml,
        'has_downloads' => !empty($downloadListHtml) ? '1' : ''
    ]);
}

function sendOrderRejectedEmail($user, $order) {
    sendEmail($user['email'], $user['first_name'] . ' ' . $user['last_name'], 'order_rejected', [
        'first_name' => $user['first_name'],
        'last_name' => $user['last_name'],
        'email' => $user['email'],
        'order_id' => $order['id']
    ]);
}

function sendProfileChangeApprovedEmail($user, $fieldName, $oldValue, $newValue) {
    $fieldLabels = ['first_name'=>'Nome','last_name'=>'Cognome','company'=>'Ragione Sociale','billing_address'=>'Indirizzo Fatturazione','vat_id'=>'Partita IVA'];
    sendEmail($user['email'], $user['first_name'] . ' ' . $user['last_name'], 'profile_change_approved', [
        'first_name' => $user['first_name'],
        'last_name' => $user['last_name'],
        'email' => $user['email'],
        'field_name' => $fieldLabels[$fieldName] ?? $fieldName,
        'old_value' => $oldValue,
        'new_value' => $newValue
    ]);
}

function sendProfileChangeRejectedEmail($user, $fieldName, $oldValue, $newValue, $adminNotes = '') {
    $fieldLabels = ['first_name'=>'Nome','last_name'=>'Cognome','company'=>'Ragione Sociale','billing_address'=>'Indirizzo Fatturazione','vat_id'=>'Partita IVA'];
    sendEmail($user['email'], $user['first_name'] . ' ' . $user['last_name'], 'profile_change_rejected', [
        'first_name' => $user['first_name'],
        'last_name' => $user['last_name'],
        'email' => $user['email'],
        'field_name' => $fieldLabels[$fieldName] ?? $fieldName,
        'old_value' => $oldValue,
        'new_value' => $newValue,
        'admin_notes' => $adminNotes
    ]);
}

function sendLicenseActivatedEmail($license) {
    $conn = getDBConnection();
    if (!$license['user_id']) return;
    $user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id = " . (int)$license['user_id']));
    if (!$user) return;
    sendEmail($user['email'], $user['first_name'] . ' ' . $user['last_name'], 'license_activated', [
        'first_name' => $user['first_name'],
        'last_name' => $user['last_name'],
        'email' => $user['email'],
        'software_name' => $license['software_name'],
        'serial_key' => $license['serial_key'],
        'hardware_id' => $license['hardware_id']
    ]);
}

function getAdminEmail() {
    $conn = getDBConnection();
    $r = mysqli_query($conn, "SELECT email FROM admins ORDER BY id ASC LIMIT 1");
    $row = mysqli_fetch_assoc($r);
    return $row ? $row['email'] : null;
}

function sendAdminProfileRequestEmail($user, $fieldName, $oldValue, $newValue) {
    if (!isAdminNotificationEnabled('profile_request')) return;
    $adminEmail = getAdminEmail();
    if (!$adminEmail) return;
    $fieldLabels = ['first_name'=>'Nome','last_name'=>'Cognome','company'=>'Ragione Sociale','billing_address'=>'Indirizzo Fatturazione','vat_id'=>'Partita IVA'];
    sendEmail($adminEmail, 'Admin', 'admin_profile_request', [
        'customer_name' => $user['first_name'] . ' ' . $user['last_name'],
        'customer_email' => $user['email'],
        'field_name' => $fieldLabels[$fieldName] ?? $fieldName,
        'old_value' => $oldValue,
        'new_value' => $newValue
    ]);
}
?>