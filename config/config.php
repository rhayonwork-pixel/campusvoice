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
