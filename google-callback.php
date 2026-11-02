<?php
/** Google sends the user back here after "Continue with Google" (sign in, or link from Settings). */
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/google.php';
start_session();

$st = $_SESSION['google_state'] ?? null;
unset($_SESSION['google_state']);
$mode = $st['mode'] ?? 'login';

/** Leave with a message: back to Settings when linking, back to the login page otherwise. */
$finish = function (string $msg, bool $ok = false) use ($mode) {
    if ($mode === 'link' && ($u = current_user())) {
        $_SESSION['flash_settings'] = ['text' => $msg, 'ok' => $ok];
        redirect($u['role'] . '/settings.php#linked');
    }
    $_SESSION[$ok ? 'flash_login' : 'flash_login_error'] = $msg;
    redirect('login.php');
};

if (!google_enabled()) $finish('Google sign-in is not set up yet.');
if (isset($_GET['error'])) $finish('Google sign-in was cancelled.');
$state = is_string($_GET['state'] ?? null) ? $_GET['state'] : '';
if (!$st || !hash_equals($st['value'], $state) || time() - (int)$st['at'] > 600) $finish('That Google sign-in link expired. Please try again.');
$code = is_string($_GET['code'] ?? null) ? $_GET['code'] : '';
if ($code === '') $finish('Google did not send a sign-in code.');

$id = google_identity($code);
if (is_string($id)) $finish($id);

if ($mode === 'link') {
    $u = current_user();
    if (!$u || (int)$u['user_id'] !== (int)($_SESSION['google_link_uid'] ?? 0)) $finish('Please sign in again, then link Google from Settings.');
    unset($_SESSION['google_link_uid']);
    $other = q('SELECT user_id FROM users WHERE google_sub = ? AND user_id <> ?', [$id['sub'], $u['user_id']])->fetchColumn();
    if ($other) $finish('That Google account is already linked to another CampusVoice account.');
    q('UPDATE users SET google_sub = ?, google_email = ?, google_linked_at = NOW() WHERE user_id = ?', [$id['sub'], $id['email'], $u['user_id']]);
    audit('google_linked', 'Linked Google account ' . $id['email']);
    $finish('Google account ' . $id['email'] . ' is now linked. You can use “Continue with Google” to sign in.', true);
}

// sign in
$user = q('SELECT * FROM users WHERE google_sub = ?', [$id['sub']])->fetch();
if (!$user) $finish('No CampusVoice account is linked to ' . $id['email'] . '. Sign in with your username first, then link Google in Settings → Linked accounts.');
if ($user['account_status'] !== 'active') $finish('This account is not active. Please contact the administrator.');
if (lockout_seconds_left('uid:' . $user['user_id']) !== null) $finish('This account is locked for a few minutes after too many wrong passwords. Please wait.');
establish_session($user);
q('UPDATE users SET google_email = ? WHERE user_id = ?', [$id['email'], $user['user_id']]);
audit('login', 'Signed in with Google', $user);
redirect(home_for($user['role']));
