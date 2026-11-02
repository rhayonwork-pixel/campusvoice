<?php
/**
 * "Continue with Google" — plain OAuth 2.0 / OpenID Connect, no library.
 * A student first signs in with their username, then links Google in Settings → Linked accounts.
 * After that the Google button on the login page signs them straight in. Unlinking removes it again.
 *
 * Setup: fill the 'google' block in config/config.php (see README_DEPLOY.md).
 */

/** Google keys live in config/config.php ('google' block). The system itself only offers link / unlink. */
function google_cfg(string $key)
{
    return cfg('google.' . $key);
}

function google_enabled(): bool
{
    return (bool)google_cfg('enabled') && google_cfg('client_id') && google_cfg('client_secret');
}

function google_redirect_uri(): string
{
    $configured = (string)cfg('google.redirect_uri');          // optional override in config.php; normally empty
    if ($configured !== '') return $configured;
    return (is_https() ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . base_path() . '/google-callback.php';
}

function google_auth_url(string $state): string
{
    $q = [
        'client_id'     => google_cfg('client_id'),
        'redirect_uri'  => google_redirect_uri(),
        'response_type' => 'code',
        'scope'         => 'openid email profile',
        'state'         => $state,
        'prompt'        => 'select_account',
    ];
    if ($d = (string)google_cfg('allowed_domain')) $q['hd'] = $d;      // a hint for the account picker; enforced again on return
    return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($q);
}

/** POST to Google's token endpoint. Uses cURL when present, else an HTTPS stream. */
function google_post(string $url, array $fields): ?array
{
    $body = http_build_query($fields);
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $body, CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15, CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded']]);
        $raw = curl_exec($ch);
    } else {
        $raw = @file_get_contents($url, false, stream_context_create(['http' => [
            'method' => 'POST', 'header' => "Content-Type: application/x-www-form-urlencoded\r\n", 'content' => $body, 'timeout' => 15, 'ignore_errors' => true]]));
    }
    $json = is_string($raw) ? json_decode($raw, true) : null;
    return is_array($json) ? $json : null;
}

/**
 * Trade the one-time code for the user's Google identity.
 * The id_token comes straight from Google over TLS, so its claims are trusted after the checks below.
 * @return array{sub:string,email:string,name:string}|string  identity, or an error message
 */
function google_identity(string $code)
{
    $tok = google_post('https://oauth2.googleapis.com/token', [
        'code' => $code, 'client_id' => google_cfg('client_id'), 'client_secret' => google_cfg('client_secret'),
        'redirect_uri' => google_redirect_uri(), 'grant_type' => 'authorization_code',
    ]);
    if (!$tok || empty($tok['id_token'])) return 'Google did not accept the sign-in. Please try again.';
    $parts = explode('.', $tok['id_token']);
    if (count($parts) !== 3) return 'Google sent an unexpected response.';
    $claims = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
    if (!is_array($claims)) return 'Google sent an unexpected response.';
    if (($claims['aud'] ?? '') !== google_cfg('client_id')) return 'This Google sign-in was meant for another app.';
    if (!in_array($claims['iss'] ?? '', ['accounts.google.com', 'https://accounts.google.com'], true)) return 'Unexpected Google issuer.';
    if ((int)($claims['exp'] ?? 0) < time()) return 'The Google sign-in expired. Please try again.';
    if (empty($claims['email']) || empty($claims['email_verified'])) return 'Your Google email is not verified.';
    $domain = strtolower((string)google_cfg('allowed_domain'));
    if ($domain !== '' && strtolower(substr(strrchr($claims['email'], '@'), 1)) !== $domain) {
        return "Use your university Google account (@{$domain}).";
    }
    return ['sub' => (string)$claims['sub'], 'email' => strtolower($claims['email']), 'name' => (string)($claims['name'] ?? '')];
}
