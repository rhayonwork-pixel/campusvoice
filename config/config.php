<?php
/**
 * CampusVoice configuration (single file — no local override file).
 */
$config = [
    'db' => [
        'host'    => 'sql309.infinityfree.com',
        'name'    => 'if0_43316164_CampusVoice',
        'user'    => 'if0_43316164',
        'pass'    => '8Iha9ReGySMZ',
        'charset' => 'utf8mb4',
    ],
    'app' => [
        'name'     => 'CampusVoice',
        'timezone' => 'Asia/Manila',
        'debug'    => false,
        'mail_from'      => 'no-reply@campusvoice.local',
        'mail_from_name' => 'CampusVoice',
    ],
    // EMAIL (password-reset links). Off by default: the system then uses PHP mail() and keeps working either way.
    // To use Gmail: turn on 2-Step Verification, create an "App password" (Google Account > Security),
    // set enabled => true and fill in user + pass. If SMTP fails (InfinityFree blocks it) it falls back to mail().
    'smtp' => [
        'enabled' => false,
        'host'    => 'smtp.gmail.com',
        'port'    => 587,
        'secure'  => 'tls',              // 'tls' (port 587) or 'ssl' (port 465)
        'user'    => 'jafuro1302@gmail.com',                 // e.g. yourcampusvoice@gmail.com
        'pass'    => 'cmzr vcef ihyu hzeq',                 // the 16-character Google app password (NOT your Gmail password)
    ],
    // PLACEHOLDER — "Continue with Google" sign-in on the login page. Not connected yet
    'google' => [
        'enabled'        => false,
        'client_id'      => '',
        'client_secret'  => '',
        'redirect_uri'   => '',
        'allowed_domain' => 'cityofmalabonuniversity.edu.ph',
    ],
    // CLOUDFLARE TURNSTILE — the "verify you are human" check on Login / Register / Forgot password.
    // Create a free widget at dash.cloudflare.com -> Turnstile, then paste the two keys here and set enabled => true.
    'turnstile' => [
        'enabled'    => true,
        'site_key'   => '0x4AAAAAAFvWIDKWqgEKBgqz',              // public key (goes in the page)
        'secret_key' => '0x4AAAAAAFvWIAyjTCuWwq3rfb-CFrSBYwg',              // PRIVATE key (only the server uses it) — never share it
    ],
    'security' => [
        'max_failed_per_email' => 3,    // lock after N failures ...
        'max_failed_per_ip'    => 20,
        'lockout_minutes'      => 5,    // ... for this many minutes
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

return $config;
