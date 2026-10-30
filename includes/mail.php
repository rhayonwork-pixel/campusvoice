<?php
/** Minimal mail wrapper. InfinityFree limits outbound mail, so callers must tolerate failure. */
function send_mail(string $to, string $subject, string $htmlBody): bool
{
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) return false;
    $from = cfg('app.mail_from');
    $name = cfg('app.mail_from_name');
    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . $name . ' <' . $from . '>',
    ];
    $subject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    return @mail($to, $subject, $htmlBody, implode("\r\n", $headers));
}
