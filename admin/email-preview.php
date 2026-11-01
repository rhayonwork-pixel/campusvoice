<?php
/** Admin-only preview of the styled emails (nothing is sent). */
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/mail.php';
require __DIR__ . '/../includes/email.php';
$u = require_role('admin');

$type = input('type', 'reset');
if ($type === 'test') {
    [$subject, $html] = email_test($u, 'Gmail SMTP');
} else {
    [$subject, $html] = email_password_reset($u, base_url() . '/reset-password.php?token=EXAMPLE', (int)cfg('security.reset_token_minutes'));
}
if (input('text') === '1') {
    header('Content-Type: text/plain; charset=utf-8');
    echo "Subject: $subject\n\n" . mail_html_to_text($html);
    exit;
}
echo $html;
