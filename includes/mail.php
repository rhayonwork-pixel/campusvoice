<?php
/**
 * Outgoing email. Safe by design: it NEVER throws and never blocks the page for long, so a mail problem
 * cannot break login, password reset or any other feature — callers just get true/false.
 *
 *  - smtp.enabled = false  -> PHP mail() (default)
 *  - smtp.enabled = true   -> authenticated SMTP (e.g. Gmail with an App password); on any SMTP failure it
 *                             falls back to mail(). InfinityFree blocks outbound SMTP, so there it simply falls back.
 */
function send_mail(string $to, string $subject, string $htmlBody): bool
{
    try {
        $to = trim(str_replace(["\r", "\n"], '', $to));
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) return false;
        $subject = trim(str_replace(["\r", "\n"], ' ', $subject));      // header-injection guard

        if (cfg('smtp.enabled') && cfg('smtp.user') && cfg('smtp.pass')) {
            if (smtp_send($to, $subject, $htmlBody)) return true;
            error_log('CampusVoice mail: SMTP failed, falling back to mail()');
        }
        return mail_fallback($to, $subject, $htmlBody);
    } catch (Throwable $e) {
        error_log('CampusVoice mail: ' . $e->getMessage());
        return false;
    }
}

function mail_from_address(): string
{
    $from = cfg('smtp.enabled') && cfg('smtp.user') ? cfg('smtp.user') : cfg('app.mail_from');
    return trim(str_replace(["\r", "\n"], '', (string)$from));
}

function mail_fallback(string $to, string $subject, string $html): bool
{
    $name = trim(str_replace(["\r", "\n"], '', (string)cfg('app.mail_from_name')));
    $headers = ['MIME-Version: 1.0', 'Content-Type: text/html; charset=UTF-8', 'From: ' . $name . ' <' . mail_from_address() . '>'];
    return (bool)@mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $html, implode("\r\n", $headers));
}

/** Minimal SMTP client (STARTTLS or SSL + AUTH LOGIN). 6-second socket timeout; returns false on any problem. */
function smtp_send(string $to, string $subject, string $html): bool
{
    $host = (string)cfg('smtp.host'); $port = (int)cfg('smtp.port'); $secure = (string)cfg('smtp.secure');
    $user = (string)cfg('smtp.user'); $pass = (string)cfg('smtp.pass');
    $errno = 0; $errstr = '';
    $fp = @stream_socket_client(($secure === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port, $errno, $errstr, 6);
    if (!$fp) { error_log("CampusVoice SMTP connect: $errstr"); return false; }
    stream_set_timeout($fp, 6);

    $read = function () use ($fp): string {
        $out = '';
        while (($line = fgets($fp, 515)) !== false) { $out .= $line; if (strlen($line) < 4 || $line[3] === ' ') break; }
        return $out;
    };
    $cmd = function (string $c, string $expect) use ($fp, $read): bool {
        fwrite($fp, $c . "\r\n");
        return strpos($read(), $expect) === 0;
    };

    try {
        if (strpos($read(), '220') !== 0) return false;
        if (!$cmd('EHLO campusvoice', '250')) return false;
        if ($secure === 'tls') {
            if (!$cmd('STARTTLS', '220')) return false;
            if (!@stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) return false;
            if (!$cmd('EHLO campusvoice', '250')) return false;
        }
        if (!$cmd('AUTH LOGIN', '334') || !$cmd(base64_encode($user), '334') || !$cmd(base64_encode($pass), '235')) return false;
        $from = mail_from_address();
        if (!$cmd("MAIL FROM:<$from>", '250') || !$cmd("RCPT TO:<$to>", '250') || !$cmd('DATA', '354')) return false;

        $name = trim(str_replace(["\r", "\n"], '', (string)cfg('app.mail_from_name')));
        $msg = 'From: =?UTF-8?B?' . base64_encode($name) . "?= <$from>\r\n"
             . "To: <$to>\r\n"
             . 'Subject: =?UTF-8?B?' . base64_encode($subject) . "?=\r\n"
             . 'Date: ' . date('r') . "\r\n"
             . "MIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
             . chunk_split(base64_encode($html));
        fwrite($fp, $msg . "\r\n.\r\n");
        $ok = strpos($read(), '250') === 0;
        fwrite($fp, "QUIT\r\n");
        return $ok;
    } finally {
        @fclose($fp);
    }
}
