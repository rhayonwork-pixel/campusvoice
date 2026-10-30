<?php
/**
 * CampusVoice configuration.
 * Do not put real credentials here — copy config.local.sample.php to config.local.php
 * (it is git-ignored and overrides anything below).
 */
$config = [
    'db' => [
        'host'    => 'localhost',
        'name'    => 'campusvoice',
        'user'    => 'root',
        'pass'    => '',
        'charset' => 'utf8mb4',
    ],
    'app' => [
        'name'     => 'CampusVoice',
        'timezone' => 'Asia/Manila',
        'debug'    => false,
        'mail_from'      => 'no-reply@campusvoice.local',
        'mail_from_name' => 'CampusVoice',
    ],
    // PLACEHOLDER — Gmail / SMTP mailing. Not wired yet: includes/mail.php still uses PHP mail().
    // Gmail needs an "App password" (Google Account > Security > 2-Step Verification > App passwords).
    // InfinityFree blocks outbound SMTP, so this only works on a host that allows it.
    'smtp' => [
        'enabled' => false,
        'host'    => 'smtp.gmail.com',
        'port'    => 587,
        'secure'  => 'tls',
        'user'    => 'yourcampusvoice@gmail.com',
        'pass'    => '',                 // the 16-character app password goes in config.local.php, never here
    ],
    // PLACEHOLDER — "Continue with Google" sign-in. Not wired yet (needs a Google Cloud OAuth client).
    'google' => [
        'enabled'        => false,
        'client_id'      => '',
        'client_secret'  => '',          // keep the real value in config.local.php only
        'redirect_uri'   => '',          // e.g. https://yourdomain/api/google-callback.php
        'allowed_domain' => 'cityofmalabonuniversity.edu.ph',
    ],
    'security' => [
        'max_failed_per_email' => 5,    // lock after N failures ...
        'max_failed_per_ip'    => 20,
        'lockout_minutes'      => 15,   // ... within this window
        'idle_timeout_minutes' => 30,
        'reset_token_minutes'  => 30,
        'min_password_length'  => 8,
    ],
    'uploads' => [
        'max_bytes'   => 3 * 1024 * 1024,
        'allowed'     => ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'],
        'max_per_concern' => 4,
    ],
];

$local = __DIR__ . '/config.local.php';
if (is_file($local)) {
    $config = array_replace_recursive($config, require $local);
}
return $config;
