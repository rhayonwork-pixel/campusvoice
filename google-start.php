<?php
/** Starts "Continue with Google": ?mode=login (from the login page) or ?mode=link (from Settings, signed in). */
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/google.php';
start_session();

$mode = ($_GET['mode'] ?? '') === 'link' ? 'link' : 'login';
if (!google_enabled()) {
    $_SESSION['flash_login_error'] = 'Google sign-in is not set up yet. Please use your username.';
    redirect('login.php');
}
if ($mode === 'link') {
    $u = current_user();
    if (!$u) redirect('login.php');
    $_SESSION['google_link_uid'] = (int)$u['user_id'];
}
$state = bin2hex(random_bytes(16));
$_SESSION['google_state'] = ['value' => $state, 'mode' => $mode, 'at' => time()];
header('Location: ' . google_auth_url($state));
exit;
