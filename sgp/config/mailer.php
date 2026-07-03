<?php
define('GMAIL_ADDRESS',      '24dce068@charusat.edu.in');
define('GMAIL_APP_PASSWORD', 'aumuidzvjiqzedew');
define('SMTP_FROM',          '24dce068@charusat.edu.in');
define('GMAIL_FROM_NAME',    'University Medical System');

function sendOTPEmail($toEmail, $toName, $otp, $purpose = 'registration') {
    if ($purpose === 'registration') {
        $subject = 'Your OTP for Registration - University Medical System';
        $heading = 'Email Verification OTP';
        $message = 'You registered on University Medical Application System. Use OTP below to verify.';
    } else {
        $subject = 'Your OTP for Password Reset - University Medical System';
        $heading = 'Password Reset OTP';
        $message = 'We received a request to reset your password. Use OTP below.';
    }
    $year = date('Y');
    $safeOtp  = htmlspecialchars($otp);
    $safeName = htmlspecialchars($toName);
    $htmlBody = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f0f4f8;font-family:Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f0f4f8;padding:40px 0;">
<tr><td align="center">
<table width="520" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:16px;overflow:hidden;">
<tr><td style="background:#0d2d3e;padding:32px 36px;text-align:center;">
<h1 style="color:#fff;font-size:20px;margin:0;">University Medical System</h1>
<p style="color:#a8c4d4;font-size:13px;margin:6px 0 0;">'.htmlspecialchars($heading).'</p>
</td></tr>
<tr><td style="padding:36px;">
<p style="color:#1a2535;font-size:16px;margin:0 0 8px;">Hello, <strong>'.$safeName.'</strong></p>
<p style="color:#5a7184;font-size:14px;margin:0 0 28px;">'.htmlspecialchars($message).'</p>
<table width="100%" cellpadding="0" cellspacing="0">
<tr><td style="background:#e8f4fc;border:2px dashed #2d87ad;border-radius:12px;padding:28px;text-align:center;">
<p style="color:#5a7184;font-size:11px;margin:0 0 12px;text-transform:uppercase;letter-spacing:0.12em;font-weight:700;">Your One-Time Password</p>
<p style="color:#1a5f7a;font-size:42px;font-weight:900;letter-spacing:0.4em;margin:0;font-family:Courier New,monospace;">'.$safeOtp.'</p>
<p style="color:#5a7184;font-size:12px;margin:12px 0 0;">Valid for <strong>15 minutes</strong> only</p>
</td></tr></table>
<p style="color:#c0392b;font-size:13px;margin:20px 0 0;padding:12px 16px;background:#fdecea;border-radius:8px;">
<strong>Do not share this OTP</strong> with anyone.</p>
<p style="color:#5a7184;font-size:13px;margin:20px 0 0;">If you did not request this, ignore this email.</p>
</td></tr>
<tr><td style="background:#f8fafd;border-top:1px solid #d0dde8;padding:16px 36px;text-align:center;">
<p style="color:#a0b0be;font-size:12px;margin:0;">&copy; '.$year.' University Medical System.</p>
</td></tr>
</table></td></tr></table></body></html>';
    return gmailSmtpSend($toEmail, $toName, $subject, $htmlBody);
}

function gmailSmtpSend($toEmail, $toName, $subject, $htmlBody) {
    $smtpHost  = 'smtp.gmail.com';
    $smtpPort  = 587;
    $username  = GMAIL_ADDRESS;
    $password  = GMAIL_APP_PASSWORD;
    $fromEmail = GMAIL_ADDRESS;
    $fromName  = GMAIL_FROM_NAME;

    $read = function($socket) {
        $res = '';
        while ($line = fgets($socket, 515)) {
            $res .= $line;
            if (isset($line[3]) && $line[3] === ' ') break;
        }
        return trim($res);
    };
    $cmd = function($socket, $command) use ($read) {
        fwrite($socket, $command . "\r\n");
        return $read($socket);
    };

    $errno = 0; $errstr = '';
    $socket = @fsockopen($smtpHost, $smtpPort, $errno, $errstr, 15);
    if (!$socket) { error_log("[Mail] Connect failed: {$errno} {$errstr}"); return false; }
    stream_set_timeout($socket, 15);
    $read($socket);

    $r = $cmd($socket, 'EHLO ' . (gethostname() ?: 'localhost'));
    if (substr($r, 0, 3) !== '250') { fclose($socket); return false; }

    $r = $cmd($socket, 'STARTTLS');
    if (substr($r, 0, 3) !== '220') { fclose($socket); return false; }

    $crypto = @stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT);
    if (!$crypto) $crypto = @stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
    if (!$crypto) { fclose($socket); error_log("[Mail] TLS failed"); return false; }

    $cmd($socket, 'EHLO ' . (gethostname() ?: 'localhost'));

    $r = $cmd($socket, 'AUTH LOGIN');
    if (substr($r, 0, 3) !== '334') { fclose($socket); return false; }
    $r = $cmd($socket, base64_encode($username));
    if (substr($r, 0, 3) !== '334') { fclose($socket); return false; }
    $r = $cmd($socket, base64_encode($password));
    if (substr($r, 0, 3) !== '235') { fclose($socket); error_log("[Mail] Wrong App Password"); return false; }

    $cmd($socket, "MAIL FROM:<{$fromEmail}>");
    $cmd($socket, "RCPT TO:<{$toEmail}>");
    $cmd($socket, 'DATA');

    $encodedBody = chunk_split(base64_encode($htmlBody));
    $msg  = "Date: " . date('r') . "\r\n";
    $msg .= "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <{$fromEmail}>\r\n";
    $msg .= "To: =?UTF-8?B?" . base64_encode($toName) . "?= <{$toEmail}>\r\n";
    $msg .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
    $msg .= "MIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\n";
    $msg .= "Content-Transfer-Encoding: base64\r\n\r\n";
    $msg .= $encodedBody . "\r\n.\r\n";

    fwrite($socket, $msg);
    $r = $read($socket);
    $cmd($socket, 'QUIT');
    fclose($socket);

    return substr($r, 0, 3) === '250';
}
?>