<?php
/**
 * CampusVoice core: config, DB, session, security helpers.
 * Included by every page and API endpoint.
 */
declare(strict_types=1);

define('CV_ROOT', dirname(__DIR__));
$GLOBALS['cv_config'] = require CV_ROOT . '/config/config.php';

date_default_timezone_set(cfg('app.timezone'));
error_reporting(E_ALL);
ini_set('display_errors', cfg('app.debug') ? '1' : '0');
ini_set('log_errors', '1');

/* Any uncaught error ends here instead of a blank HTTP 500, with a message that says what to fix. */
set_exception_handler(function (Throwable $ex) {
    error_log('CampusVoice: ' . get_class($ex) . ': ' . $ex->getMessage() . ' @ ' . $ex->getFile() . ':' . $ex->getLine());
    $msg = 'Something went wrong on our side. Please try again shortly.';
    if ($ex instanceof PDOException && in_array((string)$ex->getCode(), ['42S02', '42S22'], true)) {
        $msg = 'The database is connected, but its tables are missing or out of date. Import database/schema.sql and then database/seed.sql in phpMyAdmin.';
    }
    if (cfg('app.debug')) $msg .= ' [' . get_class($ex) . ': ' . $ex->getMessage() . ' in ' . basename($ex->getFile()) . ':' . $ex->getLine() . ']';
    fail_hard(500, $msg);
});

/* ---------- config / url helpers ---------- */

function cfg(string $path, $default = null)
{
    $node = $GLOBALS['cv_config'];
    foreach (explode('.', $path) as $key) {
        if (!is_array($node) || !array_key_exists($key, $node)) return $default;
        $node = $node[$key];
    }
    return $node;
}

/** Web path of the project root: "/CampusVoice" on XAMPP, "" on InfinityFree. */
function base_path(): string
{
    static $base = null;
    if ($base === null) {
        $doc  = rtrim(str_replace('\\', '/', (string)($_SERVER['DOCUMENT_ROOT'] ?? '')), '/');
        $root = str_replace('\\', '/', CV_ROOT);
        $base = (strpos($root, $doc) === 0) ? substr($root, strlen($doc)) : '';
    }
    return $base;
}

function url(string $path = ''): string
{
    return base_path() . '/' . ltrim($path, '/');
}

function e($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

function client_ip(): string
{
    return substr((string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), 0, 45);
}

function is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
}

/* ---------- database ---------- */

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $c = cfg('db');
        $port = !empty($c['port']) ? ";port={$c['port']}" : '';
        $dsn = "mysql:host={$c['host']}{$port};dbname={$c['name']};charset={$c['charset']}";
        try {
            $pdo = new PDO($dsn, $c['user'], $c['pass'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
            $pdo->exec("SET time_zone = '+08:00'");
        } catch (PDOException $ex) {
            error_log('CampusVoice DB: ' . $ex->getMessage());
            // set 'debug' => true in config/config.php to see the real reason, then set it back to false
            fail_hard(503, cfg('app.debug') ? 'Database connection failed: ' . $ex->getMessage() : 'The service is temporarily unavailable. Please try again shortly.');
        }
    }
    return $pdo;
}

/** Run a prepared statement and return the statement. */
function q(string $sql, array $params = []): PDOStatement
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st;
}

function fail_hard(int $code, string $message): void
{
    http_response_code($code);
    if (is_api_request()) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => $message]);
    } else {
        echo '<!doctype html><meta charset="utf-8"><title>CampusVoice</title>'
           . '<body style="font-family:sans-serif;padding:3rem;text-align:center"><h2>' . e($message) . '</h2></body>';
    }
    exit;
}

function is_api_request(): bool
{
    return strpos((string)($_SERVER['SCRIPT_NAME'] ?? ''), '/api/') !== false;
}

/* ---------- headers & session ---------- */

function send_security_headers(): void
{
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header("Content-Security-Policy: default-src 'self'; "
        . "script-src 'self' https://cdn.jsdelivr.net; "
        . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net; "
        . "font-src 'self' https://fonts.gstatic.com; "
        . "img-src 'self' data: blob:; connect-src 'self'; frame-ancestors 'self'; base-uri 'self'; form-action 'self'");
    if (is_https()) header('Strict-Transport-Security: max-age=15552000');
}

function start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) return;
    session_name('cv_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => base_path() ?: '/',
        'secure'   => is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_start();

    // Idle timeout + bind session to the browser fingerprint (UA) to blunt hijacking.
    $now  = time();
    $ua   = hash('sha256', (string)($_SERVER['HTTP_USER_AGENT'] ?? ''));
    $idle = isset($_SESSION['uid']) ? sec('idle_timeout_minutes') * 60 : 0;
    if (isset($_SESSION['uid'])) {
        $expired = isset($_SESSION['last_seen']) && ($now - $_SESSION['last_seen']) > $idle;
        $stolen  = isset($_SESSION['ua']) && !hash_equals($_SESSION['ua'], $ua);
        if ($expired || $stolen) {
            $why = $expired ? 'expired' : 'invalid';
            destroy_session();
            session_name('cv_session');
            session_start();
            $_SESSION['flash_login'] = $why === 'expired'
                ? 'Your session expired due to inactivity. Please sign in again.'
                : 'Please sign in again.';
        }
    }
    $_SESSION['ua'] = $ua;
    $_SESSION['last_seen'] = $now;
}

function destroy_session(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

send_security_headers();
start_session();

/* ---------- CSRF ---------- */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_ok(): bool
{
    $sent = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['_csrf'] ?? '');
    return is_string($sent) && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $sent);
}

/* ---------- auth state ---------- */

function current_user(): ?array
{
    static $user = false;
    if ($user === false) {
        $user = null;
        if (!empty($_SESSION['uid'])) {
            $row = q('SELECT * FROM users WHERE user_id = ? AND account_status = "active"', [(int)$_SESSION['uid']])->fetch();
            if ($row) {
                $user = $row;
            } else {
                destroy_session();
            }
        }
    }
    return $user ?: null;
}

function home_for(string $role): string
{
    return $role . '/dashboard.php';
}

/** Page guard: redirects to login when not signed in, or to the user's own area on a role mismatch. */
function require_role(string $role): array
{
    $u = current_user();
    if (!$u) redirect('login.php');
    if ($u['role'] !== $role) redirect(home_for($u['role']));
    if ((int)$u['must_change_password'] === 1 && basename($_SERVER['SCRIPT_NAME']) !== 'settings.php') {
        redirect($role . '/settings.php?force=1');
    }
    return $u;
}

/* ---------- JSON API helpers ---------- */

function json_out(array $payload, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function json_fail(string $message, int $code = 400, array $extra = []): void
{
    json_out(['ok' => false, 'error' => $message] + $extra, $code);
}

/**
 * API guard. $roles = allowed roles (empty = public). Mutating requests must be POST + valid CSRF.
 */
function api_guard(array $roles = [], bool $mutating = true): ?array
{
    if ($mutating) {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') json_fail('Method not allowed.', 405);
        if (!csrf_ok()) json_fail('Your session token is invalid. Please refresh the page.', 419);
    }
    if (!$roles) return current_user();
    $u = current_user();
    if (!$u) json_fail('Please sign in again.', 401, ['login' => url('login.php')]);
    if (!in_array($u['role'], $roles, true)) json_fail('You are not allowed to do that.', 403);
    return $u;
}

function input(string $key, string $default = ''): string
{
    $v = $_POST[$key] ?? $_GET[$key] ?? $default;
    return is_string($v) ? trim($v) : $default;
}

function input_int(string $key, int $default = 0): int
{
    $v = $_POST[$key] ?? $_GET[$key] ?? null;
    return is_numeric($v) ? (int)$v : $default;
}

/* ---------- audit trail & notifications ---------- */

function audit(string $action, string $details = '', ?array $actor = null): void
{
    $actor = $actor ?? current_user();
    q('INSERT INTO audit_logs (user_id, user_label, role, action, details, ip_address) VALUES (?,?,?,?,?,?)', [
        $actor['user_id'] ?? null,
        $actor ? display_name($actor) . ' <' . $actor['email'] . '>' : 'Guest',
        $actor['role'] ?? 'guest',
        $action,
        mb_substr($details, 0, 500),
        client_ip(),
    ]);
}

function notify(int $userId, string $title, string $message, ?int $concernId = null, bool $urgent = false): void
{
    $pref = q('SELECT pref_notify_inapp FROM users WHERE user_id = ?', [$userId])->fetchColumn();
    if ($pref === false || (int)$pref === 0) return;
    q('INSERT INTO notifications (user_id, concern_id, title, message, is_urgent) VALUES (?,?,?,?,?)',
        [$userId, $concernId, mb_substr($title, 0, 150), mb_substr($message, 0, 500), $urgent ? 1 : 0]);
}

function notify_role(string $role, string $title, string $message, ?int $concernId = null, bool $urgent = false): void
{
    foreach (q('SELECT user_id FROM users WHERE role = ? AND account_status = "active"', [$role])->fetchAll() as $r) {
        notify((int)$r['user_id'], $title, $message, $concernId, $urgent);
    }
}

/* ---------- formatting helpers ---------- */

/** "Juan A. Dela Cruz" for general display. */
function display_name(array $u): string
{
    $mi = trim((string)($u['middle_initial'] ?? ''));
    return trim($u['first_name'] . ' ' . ($mi !== '' ? rtrim($mi, '.') . '. ' : '') . $u['last_name']);
}

/** "Dela Cruz, Juan A." — registry format used in the admin user table. */
function formal_name(array $u): string
{
    $mi = trim((string)($u['middle_initial'] ?? ''));
    return $u['last_name'] . ', ' . $u['first_name'] . ($mi !== '' ? ' ' . rtrim($mi, '.') . '.' : '');
}

function greeting(): string
{
    $h = (int)date('G');
    return $h < 12 ? 'Good morning' : ($h < 18 ? 'Good afternoon' : 'Good evening');
}

/** Hours to resolve by severity. Admin can tune these in settings. */
function severity_hours(string $severity): int
{
    $defaults = ['Minor' => 168, 'Major' => 72, 'Urgent' => 24];
    $row = q('SELECT setting_value FROM settings WHERE setting_key = ?', ['sla_' . strtolower($severity)])->fetchColumn();
    return $row !== false ? max(1, (int)$row) : $defaults[$severity];
}

function setting(string $key, string $default = ''): string
{
    $v = q('SELECT setting_value FROM settings WHERE setting_key = ?', [$key])->fetchColumn();
    return $v === false ? $default : (string)$v;
}

/** Security value: admin-editable override from the settings table, else the config file default. */
function sec(string $name): int
{
    $map = ['max_failed_per_email' => 'sec_max_failed', 'lockout_minutes' => 'sec_lockout_minutes', 'idle_timeout_minutes' => 'sec_idle_minutes'];
    if (isset($map[$name])) {
        $v = setting($map[$name], '');
        if ($v !== '' && ctype_digit($v)) return (int)$v;
    }
    return (int)cfg('security.' . $name);
}

function password_problem(string $pw): ?string
{
    $min = (int)cfg('security.min_password_length');
    if (strlen($pw) < $min) return "Password must be at least {$min} characters.";
    if (!preg_match('/[A-Za-z]/', $pw) || !preg_match('/\d/', $pw)) return 'Password must contain letters and numbers.';
    return null;
}
