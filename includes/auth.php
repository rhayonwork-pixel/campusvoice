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

/** Find an account by username (school ID) or email. */
function find_login_user(string $identifier): ?array
{
    $identifier = trim($identifier);
    if ($identifier === '') return null;
    $row = q('SELECT * FROM users WHERE school_id = ? OR (email IS NOT NULL AND email = ?) LIMIT 1', [$identifier, $identifier])->fetch();
    return $row ?: null;
}

/** Throttle key for an identifier: one key per account, so username and email share the same counter. */
function login_key(string $identifier, ?array $user = null): string
{
    $user = $user ?? find_login_user($identifier);
    return $user ? 'uid:' . $user['user_id'] : strtolower(trim($identifier));
}

/** Failed tries left before the lock (per account key). */
function attempts_left(string $key): int
{
    $c = (int)q('SELECT COUNT(*) FROM login_attempts WHERE email = ? AND success = 0 AND created_at > (NOW() - INTERVAL ? MINUTE)',
        [$key, sec('lockout_minutes')])->fetchColumn();
    return max(0, sec('max_failed_per_email') - $c);
}

/** Seconds left on a lockout, or null when the visitor may try. Locks last lockout_minutes from the last failed try. */
function lockout_seconds_left(string $key): ?int
{
    $window = sec('lockout_minutes');
    $limits = [[ 'email = ?', $key, sec('max_failed_per_email') ], [ 'ip_address = ?', client_ip(), (int)cfg('security.max_failed_per_ip') ]];
    $locked = null;
    foreach ($limits as [$where, $val, $limit]) {
        $row = q("SELECT COUNT(*) c, UNIX_TIMESTAMP(MAX(created_at)) last, UNIX_TIMESTAMP(NOW()) now FROM login_attempts
                  WHERE {$where} AND success = 0 AND created_at > (NOW() - INTERVAL ? MINUTE)", [$val, $window])->fetch();
        if ((int)$row['c'] >= $limit && $limit > 0) {
            $left = (int)$row['last'] + $window * 60 - (int)$row['now'];
            if ($left > 0) $locked = max($locked ?? 0, $left);
        }
    }
    return $locked;
}

/** Minutes left on a lockout (rounded up), or null. Kept for the reset forms. */
function lockout_minutes_left(string $key): ?int
{
    $s = lockout_seconds_left($key);
    return $s === null ? null : (int)ceil($s / 60);
}

/** Verify credentials without revealing whether the account exists. Returns the user row or null. */
function verify_credentials(string $identifier, string $password, ?array $user = null): ?array
{
    $user = $user ?? find_login_user($identifier);
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

/* ---------- "Remember me": a long-lived, hashed token in a cookie (one remembered device per account) ---------- */
const CV_REMEMBER_DAYS = 30;

function remember_cookie(string $value, int $expires): void
{
    if (headers_sent()) return;
    setcookie('cv_remember', $value, ['expires' => $expires, 'path' => base_path() ?: '/', 'secure' => is_https(), 'httponly' => true, 'samesite' => 'Lax']);
}

function remember_issue(int $userId): void
{
    try {
        $selector = bin2hex(random_bytes(9)); $validator = bin2hex(random_bytes(24));
        q('UPDATE users SET remember_selector = ?, remember_hash = ?, remember_expires = NOW() + INTERVAL ? DAY WHERE user_id = ?',
            [$selector, hash('sha256', $validator), CV_REMEMBER_DAYS, $userId]);
        remember_cookie($selector . '.' . $validator, time() + CV_REMEMBER_DAYS * 86400);
    } catch (Throwable $e) {
        error_log('remember_issue: ' . $e->getMessage());   // columns missing until the upgrade SQL is run: just do not remember
    }
}

function remember_forget(?int $userId = null): void
{
    if ($userId) {
        try { q('UPDATE users SET remember_selector = NULL, remember_hash = NULL, remember_expires = NULL WHERE user_id = ?', [$userId]); } catch (Throwable $e) { /* not upgraded yet */ }
    }
    remember_cookie('', time() - 3600);
}

/** Sign the person back in from the cookie. Returns the user row or null. */
function remember_login(): ?array
{
    if (!preg_match('/^([a-f0-9]{18})\.([a-f0-9]{48})$/', (string)($_COOKIE['cv_remember'] ?? ''), $m)) return null;
    try {
        $row = q('SELECT * FROM users WHERE remember_selector = ? AND remember_expires > NOW() AND account_status = "active"', [$m[1]])->fetch();
    } catch (Throwable $e) { return null; }
    if (!$row || !hash_equals((string)$row['remember_hash'], hash('sha256', $m[2]))) { remember_cookie('', time() - 3600); return null; }
    establish_session($row);
    unset($_SESSION['flash_login']);
    remember_issue((int)$row['user_id']);                    // new token every time
    return $row;
}

function new_reset_token(int $userId, bool $short = false): string
{
    $secret = $short ? strtoupper(substr(strtr(bin2hex(random_bytes(6)), 'ol01', 'XYZW'), 0, 8)) : bin2hex(random_bytes(24));
    q('UPDATE password_resets SET used_at = NOW() WHERE user_id = ? AND used_at IS NULL', [$userId]);
    q('INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?, ?, NOW() + INTERVAL ? MINUTE)',
        [$userId, hash('sha256', $secret), (int)cfg('security.reset_token_minutes')]);
    return $secret;
}

/** Look up a live reset row by token (link) or by username/email + code. */
function find_reset(string $secret, string $identifier = ''): ?array
{
    $row = q('SELECT r.*, u.email, u.school_id FROM password_resets r JOIN users u ON u.user_id = r.user_id
              WHERE r.token_hash = ? AND r.used_at IS NULL AND r.expires_at > NOW()', [hash('sha256', trim($secret))])->fetch();
    if (!$row) return null;
    if ($identifier !== '' && strcasecmp($row['school_id'], $identifier) !== 0 && strcasecmp((string)$row['email'], $identifier) !== 0) return null;
    return $row;
}
