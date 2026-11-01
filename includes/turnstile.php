<?php
/**
 * Cloudflare Turnstile — the "I'm not a robot" check shown before Login / Register / Forgot password.
 * Off until config/config.php has turnstile.enabled = true plus both keys, so the site works without it.
 * The token is verified on the SERVER (siteverify); a widget alone is not protection.
 */

const CV_TURNSTILE_VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

function turnstile_enabled(): bool
{
    return (bool)cfg('turnstile.enabled') && (string)cfg('turnstile.site_key') !== '' && (string)cfg('turnstile.secret_key') !== '';
}

/** The widget element, or '' when Turnstile is off. */
function turnstile_widget(): string
{
    if (!turnstile_enabled()) return '';
    return '<div class="cf-turnstile cf-box" data-sitekey="' . e(cfg('turnstile.site_key')) . '" data-theme="auto" data-size="flexible"></div>';
}

/** Script URLs to pass to html_foot() as external scripts. */
function turnstile_scripts(): array
{
    return turnstile_enabled() ? ['https://challenges.cloudflare.com/turnstile/v0/api.js'] : [];
}

/** Verify the submitted token. Returns null when the visitor passed (or Turnstile is off), otherwise a message to show. */
function turnstile_check(): ?string
{
    if (!turnstile_enabled()) return null;

    $token = $_POST['cf-turnstile-response'] ?? '';
    if (!is_string($token) || $token === '' || strlen($token) > 2048) return 'Please complete the security check first.';

    $fields = http_build_query(['secret' => (string)cfg('turnstile.secret_key'), 'response' => $token, 'remoteip' => client_ip()]);
    $body = null;
    if (function_exists('curl_init')) {
        $ch = curl_init(CV_TURNSTILE_VERIFY_URL);
        curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $fields, CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_TIMEOUT => 8]);
        $body = curl_exec($ch);
    } elseif (ini_get('allow_url_fopen')) {
        $ctx = stream_context_create(['http' => ['method' => 'POST', 'header' => "Content-Type: application/x-www-form-urlencoded\r\n", 'content' => $fields, 'timeout' => 8]]);
        $body = @file_get_contents(CV_TURNSTILE_VERIFY_URL, false, $ctx);
    }
    if (!is_string($body) || $body === '') {
        error_log('CampusVoice Turnstile: could not reach Cloudflare to verify the token');
        return 'The security check could not be verified right now. Please try again in a moment.';
    }
    $json = json_decode($body, true);
    if (is_array($json) && !empty($json['success'])) return null;

    error_log('CampusVoice Turnstile: rejected (' . implode(',', (array)($json['error-codes'] ?? ['unknown'])) . ')');
    return 'The security check failed or expired. Please try again.';
}
