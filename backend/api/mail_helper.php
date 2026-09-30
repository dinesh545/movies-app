<?php
// backend/api/mail_helper.php - Lightweight SMTP Socket Mailer Helper

require_once __DIR__ . '/../db.php';

function send_smtp_email($pdo, $toEmail, $subject, $bodyHtml) {
    $host = get_site_setting($pdo, 'smtp_host', '');
    $port = (int)get_site_setting($pdo, 'smtp_port', '587');
    $user = get_site_setting($pdo, 'smtp_user', '');
    $pass = get_site_setting($pdo, 'smtp_pass', '');
    $encryption = strtolower(get_site_setting($pdo, 'smtp_encryption', 'tls'));
    $fromEmail = get_site_setting($pdo, 'smtp_from_email', $user);
    $fromName = get_site_setting($pdo, 'smtp_from_name', 'Soni Cinemas');

    if (empty($fromEmail)) {
        $fromEmail = $user ?: 'noreply@mybhiwani.in';
    }

    if (empty($host)) {
        // Fallback to PHP native mail() if SMTP is not configured
        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=utf-8\r\n";
        $headers .= "From: {$fromName} <{$fromEmail}>\r\n";
        return @mail($toEmail, $subject, $bodyHtml, $headers);
    }

    try {
        $prefix = '';
        if ($encryption === 'ssl') {
            $prefix = 'ssl://';
        }

        $socketHost = $prefix . $host;
        $timeout = 15;
        $socket = @fsockopen($socketHost, $port, $errno, $errstr, $timeout);

        if (!$socket) {
            throw new Exception("Socket connection to {$host}:{$port} failed: {$errstr} ({$errno})");
        }

        stream_set_timeout($socket, $timeout);

        $getResponse = function() use ($socket) {
            $response = '';
            while ($str = fgets($socket, 512)) {
                $response .= $str;
                if (substr($str, 3, 1) == ' ') break;
            }
            return $response;
        };

        $sendCommand = function($cmd) use ($socket, $getResponse) {
            fputs($socket, $cmd . "\r\n");
            return $getResponse();
        };

        $getResponse(); // Initial banner

        $heloRes = $sendCommand("EHLO " . gethostname());

        if ($encryption === 'tls') {
            $sendCommand("STARTTLS");
            $cryptoMethod = STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT;
            if (!stream_socket_enable_crypto($socket, true, $cryptoMethod)) {
                // Fallback for older TLS support
                stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            }
            $sendCommand("EHLO " . gethostname());
        }

        if (!empty($user) && !empty($pass)) {
            $sendCommand("AUTH LOGIN");
            $sendCommand(base64_encode($user));
            $authRes = $sendCommand(base64_encode($pass));
            if (substr($authRes, 0, 3) !== '235') {
                throw new Exception("SMTP Authentication failed: " . trim($authRes));
            }
        }

        $sendCommand("MAIL FROM: <{$fromEmail}>");
        $sendCommand("RCPT TO: <{$toEmail}>");
        $sendCommand("DATA");

        $emailHeaders  = "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <{$fromEmail}>\r\n";
        $emailHeaders .= "To: <{$toEmail}>\r\n";
        $emailHeaders .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
        $emailHeaders .= "MIME-Version: 1.0\r\n";
        $emailHeaders .= "Content-Type: text/html; charset=UTF-8\r\n";
        $emailHeaders .= "Date: " . date('r') . "\r\n\r\n";

        $dataContent = $emailHeaders . $bodyHtml . "\r\n.";
        $dataRes = $sendCommand($dataContent);

        $sendCommand("QUIT");
        fclose($socket);

        return true;
    } catch (Exception $e) {
        error_log("SMTP Error: " . $e->getMessage());
        throw $e;
    }
}
