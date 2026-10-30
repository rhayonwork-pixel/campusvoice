<?php
/** Login throttling, credential checks and password-reset helpers. */

const CV_DUMMY_HASH = '$2y$10$W09aob81AvkC6Oyh1TobMecDlPRyhRxU.FPBy0rFuQovbTayrWYru';

function record_attempt(string $email, bool $success): void
{
    q('INSERT INTO login_attempts (email, ip_address, success) VALUES (?,?,?)', [mb_substr($email, 0, 190), client_ip(), $success ? 1 : 0]);
    if (random_int(1, 40) === 1) {            // cheap housekeeping, no cron on free hosting
        q('DELETE FROM login_attempts WHERE created_at < (NOW() - INTERVAL 2 DAY)');
    }
}

/** Minutes left on a lockout, or null when the visitor may try. */
function lockout_minutes_left(string $email): ?int
{
    $window = sec('lockout_minutes');
    $byEmail = q('SELECT COUNT(*) c, MAX(created_at) last FROM login_attempts WHERE email = ? AND success = 0 AND created_at > (NOW() - INTERVAL ? MINUTE)',
        [$email, $window])->fetch();
    $byIp = q('SELECT COUNT(*) c, MAX(created_at) last FROM login_attempts WHERE ip_address = ? AND success = 0 AND created_at > (NOW() - INTERVAL ? MINUTE)',
        [client_ip(), $window])->fetch();

    $locked = null;
    foreach ([[$byEmail, 'max_failed_per_email'], [$byIp, 'max_failed_per_ip']] as [$row, $limitKey]) {
        if ((int)$row['c'] >= ($limitKey === 'max_failed_per_email' ? sec('max_failed_per_email') : (int)cfg('security.' . $limitKey))) {
            $left = $window - (int)floor((time() - strtotime($row['last'])) / 60);
            $locked = max($locked ?? 0, max(1, $left));
        }
    }
    return $locked;
}

/** Verify credentials without revealing whether the email exists. Returns the user row or null. */
function verify_credentials(string $email, string $password): ?array
{
    $user = q('SELECT * FROM users WHERE email = ?', [$email])->fetch();
    $hash = $user['password_hash'] ?? CV_DUMMY_HASH;
    $ok   = password_verify($password, $hash);
    if (!$user || !$ok) return null;
    if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
        q('UPDATE users SET password_hash = ? WHERE user_id = ?', [password_hash($password, PASSWORD_DEFAULT), $user['user_id']]);
    }
    return $user;
}

function establish_session(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['uid']       = (int)$user['user_id'];
    $_SESSION['csrf']      = bin2hex(random_bytes(32));
    $_SESSION['ua']        = hash('sha256', (string)($_SERVER['HTTP_USER_AGENT'] ?? ''));
    $_SESSION['last_seen'] = time();
    $_SESSION['greeted']   = false;           // staff greeting shows once per login
    q('UPDATE users SET last_login_at = NOW() WHERE user_id = ?', [$user['user_id']]);
}

function new_reset_token(int $userId, bool $short = false): string
{
    $secret = $short ? strtoupper(substr(strtr(bin2hex(random_bytes(6)), 'ol01', 'XYZW'), 0, 8)) : bin2hex(random_bytes(24));
    q('UPDATE password_resets SET used_at = NOW() WHERE user_id = ? AND used_at IS NULL', [$userId]);
    q('INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?, ?, NOW() + INTERVAL ? MINUTE)',
        [$userId, hash('sha256', $secret), (int)cfg('security.reset_token_minutes')]);
    return $secret;
}

/** Look up a live reset row by token (link) or by email+code. */
function find_reset(string $secret, string $email = ''): ?array
{
    $row = q('SELECT r.*, u.email FROM password_resets r JOIN users u ON u.user_id = r.user_id
              WHERE r.token_hash = ? AND r.used_at IS NULL AND r.expires_at > NOW()', [hash('sha256', trim($secret))])->fetch();
    if (!$row) return null;
    if ($email !== '' && strcasecmp($row['email'], $email) !== 0) return null;
    return $row;
}
