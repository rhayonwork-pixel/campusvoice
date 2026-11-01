<?php
/**
 * Outgoing email. Safe by design: it NEVER throws and never blocks the page for long (6 s socket timeout),
 * so a mail problem cannot break login, password reset or any other feature — callers just get true/false.
 *
 *  - smtp.enabled = false  -> PHP mail()
 *  - smtp.enabled = true   -> authenticated SMTP (Gmail + App password); on failure it falls back to mail().
 *    InfinityFree blocks outbound SMTP, so there it simply falls back.
 * Every message is multipart: a styled HTML part (see email.php) plus a plain-text part.
 * mail_last() tells you how it went (used by Settings → "Send test email").
 */

/** @return array{via:string,error:string} */
function mail_last(?string $key = null, ?string $value = null): array
{
    static $s = ['via' => 'none', 'error' => ''];
    if ($key !== null) $s[$key] = (string)$value;
    return $s;
}

function mail_from_address(): string
{
    $from = cfg('smtp.enabled') && cfg('smtp.user') ? cfg('smtp.user') : cfg('app.mail_from');
    return trim(str_replace(["\r", "\n"], '', (string)$from));
}

function mail_clean_header(string $v): string { return trim(str_replace(["\r", "\n"], ' ', $v)); }

function mail_html_to_text(string $html): string
{
    $t = preg_replace('/<(style|head|script)[^>]*>.*?<\/\1>/is', '', $html);
    $t = preg_replace('/<div style="display:none[^"]*">.*?<\/div>/is', '', $t);          // hidden inbox-preview text
    $t = preg_replace_callback('/<a\b[^>]*href="([^"]+)"[^>]*>(.*?)<\/a>/is', function ($m) {
        $label = trim(strip_tags($m[2])); $url = html_entity_decode($m[1], ENT_QUOTES, 'UTF-8');
        return ($label === '' || $label === $url) ? $url : $label . ' (' . $url . ')';       // no "url (url)" duplicates
    }, $t);
    $t = preg_replace('/<br\s*\/?>|<\/(p|h1|h2|h3|tr|div)>/i', "\n", $t);
    $t = html_entity_decode(strip_tags($t), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $t = preg_replace("/[ \t\xC2\xA0\x{200C}]+/u", ' ', $t);
    return trim(preg_replace("/\n[ ]*\n[\s]*/", "\n\n", $t));
}

/** Build the MIME headers (without To/Subject) and the multipart body. @return array{0:string,1:string} */
function mail_build(string $htmlBody, string $textBody): array
{
    $b = 'cv_' . bin2hex(random_bytes(12));
    $name = mail_clean_header((string)cfg('app.mail_from_name'));
    $from = mail_from_address();
    $headers = 'From: =?UTF-8?B?' . base64_encode($name) . '?= <' . $from . ">\r\n"
        . 'Date: ' . date('r') . "\r\n"
        . 'Message-ID: <' . bin2hex(random_bytes(10)) . '@' . preg_replace('/[^a-z0-9.-]/i', '', substr(strrchr($from, '@') ?: '@localhost', 1)) . ">\r\n"
        . "MIME-Version: 1.0\r\n"
        . 'Content-Type: multipart/alternative; boundary="' . $b . '"';
    $part = function (string $type, string $content) use ($b): string {
        return '--' . $b . "\r\nContent-Type: $type; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . chunk_split(base64_encode($content), 76, "\r\n");
    };
    $body = $part('text/plain', $textBody) . $part('text/html', $htmlBody) . '--' . $b . "--\r\n";
    return [$headers, $body];
}

function send_mail(string $to, string $subject, string $htmlBody, ?string $textBody = null): bool
{
    mail_last('via', 'none'); mail_last('error', '');
    try {
        $to = mail_clean_header($to);
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) { mail_last('error', 'Invalid recipient address.'); return false; }
        $subject = mail_clean_header($subject);
        [$headers, $body] = mail_build($htmlBody, $textBody ?? mail_html_to_text($htmlBody));

        if (cfg('smtp.enabled')) {
            if (!cfg('smtp.user') || !cfg('smtp.pass')) {
                mail_last('error', 'Gmail is turned on but the email address or app password is empty in config/config.php.');
            } elseif (smtp_send($to, $subject, $headers, $body)) {
                mail_last('via', 'Gmail SMTP');
                return true;
            }
            error_log('CampusVoice mail: SMTP failed (' . mail_last()['error'] . '), falling back to mail()');
        }
        $ok = (bool)@mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, $headers);
        if ($ok) mail_last('via', 'PHP mail()');
        elseif (mail_last()['error'] === '') mail_last('error', 'PHP mail() is not available on this server.');
        return $ok;
    } catch (Throwable $e) {
        error_log('CampusVoice mail: ' . $e->getMessage());
        mail_last('error', 'Unexpected mail error.');
        return false;
    }
}

/** Minimal SMTP client (STARTTLS or SSL + AUTH LOGIN). Records a human-readable reason on failure. */
function smtp_send(string $to, string $subject, string $headers, string $body): bool
{
    $host = (string)cfg('smtp.host'); $port = (int)cfg('smtp.port'); $secure = (string)cfg('smtp.secure');
    $user = (string)cfg('smtp.user'); $pass = str_replace(' ', '', (string)cfg('smtp.pass'));   // Google shows the app password in groups of 4
    $fail = function (string $why): bool { mail_last('error', $why); return false; };

    // plain (no TLS) is only allowed for a local test server — never send a password in the clear over the internet
    if ($secure === 'none' && !in_array($host, ['127.0.0.1', 'localhost'], true)) return $fail('Unencrypted SMTP is only allowed for localhost.');

    $errno = 0; $errstr = '';
    $fp = @stream_socket_client(($secure === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port, $errno, $errstr, 6);
    if (!$fp) return $fail("Could not connect to $host:$port ($errstr). The hosting may block outgoing email connections.");
    stream_set_timeout($fp, 6);

    $read = function () use ($fp): string {
        $out = '';
        while (($line = fgets($fp, 515)) !== false) { $out .= $line; if (strlen($line) < 4 || $line[3] === ' ') break; }
        return $out;
    };
    $step = function (string $what, string $cmd, string $expect) use ($fp, $read, $fail): bool {
        if ($cmd !== '') fwrite($fp, $cmd . "\r\n");
        $resp = $read();
        return strpos($resp, $expect) === 0 ? true : $fail($what . ' was refused: ' . trim(substr($resp, 0, 160)));
    };

    try {
        if (!$step('Server greeting', '', '220')) return false;
        if (!$step('EHLO', 'EHLO campusvoice', '250')) return false;
        if ($secure === 'tls') {
            if (!$step('STARTTLS', 'STARTTLS', '220')) return false;
            if (!@stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) return $fail('Secure connection (TLS) could not be started.');
            if (!$step('EHLO', 'EHLO campusvoice', '250')) return false;
        }
        if (!$step('Login', 'AUTH LOGIN', '334') || !$step('Login (email address)', base64_encode($user), '334')) return false;
        if (!$step('Login (password) — check the app password', base64_encode($pass), '235')) return false;
        $from = mail_from_address();
        if (!$step('Sender', "MAIL FROM:<$from>", '250') || !$step('Recipient', "RCPT TO:<$to>", '250') || !$step('DATA', 'DATA', '354')) return false;

        $msg = $headers . "\r\nTo: <$to>\r\nSubject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n\r\n" . $body;
        fwrite($fp, $msg . "\r\n.\r\n");
        if (!$step('Message', '', '250')) return false;
        fwrite($fp, "QUIT\r\n");
        return true;
    } finally {
        @fclose($fp);
    }
}
