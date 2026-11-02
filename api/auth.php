<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/mail.php';
require __DIR__ . '/../includes/email.php';
require __DIR__ . '/../includes/turnstile.php';

$action = input('action');
api_guard([], true);   // POST + CSRF for every auth action

// Cloudflare Turnstile on the public forms (a no-op until it is switched on in config)
if (in_array($action, ['login', 'signup', 'forgot'], true) && ($captchaError = turnstile_check()) !== null) {
    json_fail($captchaError, 400, ['captcha' => true]);
}

switch ($action) {

    case 'login': {
        $ident    = trim(input('username') ?: input('email'));
        $password = (string)($_POST['password'] ?? '');
        if ($ident === '' || $password === '') json_fail('Please enter your username and password.');

        $found = find_login_user($ident);
        $key   = login_key($ident, $found);
        $max   = sec('max_failed_per_email');
        if (($wait = lockout_seconds_left($key)) !== null) {
            audit('login_blocked', "Locked sign-in for {$ident}");
            json_fail('Too many failed attempts. Try again when the timer ends.', 429, ['retry_after' => $wait, 'attempts_left' => 0]);
        }

        $user = verify_credentials($ident, $password, $found);
        if (!$user) {
            record_attempt($key, false);
            audit('login_failed', "Failed sign-in for {$ident}");
            $left = attempts_left($key);
            if ($left === 0 && ($wait = lockout_seconds_left($key)) !== null) {
                json_fail("Too many failed attempts. Sign-in is locked for " . sec('lockout_minutes') . ' minute' . (sec('lockout_minutes') > 1 ? 's' : '') . '.', 429,
                    ['retry_after' => $wait, 'attempts_left' => 0]);
            }
            json_fail('Username or password is incorrect. ' . $left . ' attempt' . ($left === 1 ? '' : 's') . ' left.', 401,
                ['attempts_left' => $left, 'max_attempts' => $max]);
        }
        if ($user['account_status'] !== 'active') {
            record_attempt($key, false);
            json_fail('This account is not active. Please contact the administrator.', 403);
        }

        q('DELETE FROM login_attempts WHERE email = ?', [$key]);
        establish_session($user);
        audit('login', 'Signed in', $user);
        json_out(['ok' => true, 'redirect' => url(home_for($user['role']))]);
    }

    case 'logout': {
        if ($u = current_user()) audit('logout', 'Signed out', $u);
        destroy_session();
        json_out(['ok' => true, 'redirect' => url('login.php')]);
    }

    case 'signup': {
        // Public sign-up creates STUDENT accounts only.
        if (setting('student_signup', '0') !== '1') json_fail('Registration is currently closed.', 403);

        $recent = (int)q("SELECT COUNT(*) FROM audit_logs WHERE action = 'signup' AND ip_address = ? AND created_at > (NOW() - INTERVAL 1 HOUR)", [client_ip()])->fetchColumn();
        if ($recent >= 5) json_fail('Too many registrations from this connection. Try again later.', 429);

        $schoolId = strtoupper(input('school_id'));
        $first    = input('first_name');
        $mi       = mb_substr(input('middle_initial'), 0, 2);
        $last     = input('last_name');
        $email    = strtolower(input('email'));
        $course   = input('course');
        $year     = input_int('year_level');
        $section  = input('section');
        $pw       = (string)($_POST['password'] ?? '');
        $pw2      = (string)($_POST['password_confirm'] ?? '');
        $nameRe   = "/^[\\p{L} .'-]{1,80}$/u";

        if (!preg_match('/^[A-Z0-9-]{4,20}$/', $schoolId)) json_fail('Enter a valid student number (letters, numbers, dashes).');
        if (!preg_match($nameRe, $first) || !preg_match($nameRe, $last)) json_fail('Please enter your first and last name.');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) json_fail('Enter a valid email address.');
        $domain = strtolower(setting('signup_email_domain', ''));
        if ($domain !== '' && substr($email, -strlen($domain) - 1) !== '@' . $domain) json_fail("Please use your university email (@{$domain}).");
        if ($course === '' || mb_strlen($course) > 60) json_fail('Please enter your course or program.');
        if ($year < 1 || $year > 6) json_fail('Select your year level.');
        if ($section === '' || mb_strlen($section) > 20) json_fail('Please enter your section.');
        if ($pw !== $pw2) json_fail('Passwords do not match.');
        if ($p = password_problem($pw)) json_fail($p);

        if (q('SELECT 1 FROM users WHERE email = ? OR school_id = ?', [$email, $schoolId])->fetchColumn()) {
            json_fail('An account with that email or student number already exists.', 409);
        }

        q('INSERT INTO users (role, school_id, first_name, middle_initial, last_name, email, password_hash, course, year_level, section)
           VALUES ("student",?,?,?,?,?,?,?,?,?)',
            [$schoolId, $first, $mi ?: null, $last, $email, password_hash($pw, PASSWORD_DEFAULT), $course, $year, $section]);
        $new = q('SELECT * FROM users WHERE user_id = ?', [db()->lastInsertId()])->fetch();
        audit('signup', "Student account created: {$schoolId}", $new);
        notify_role('admin', 'New student registered', display_name($new) . " ({$schoolId}) created an account.");
        json_out(['ok' => true, 'message' => 'Account created. You can now sign in.', 'redirect' => url('login.php?registered=1')]);
    }

    case 'forgot': {
        $ident = trim(input('username') ?: input('email'));
        $generic = ['ok' => true, 'message' => 'If that account has an email on file, a reset link has been sent. It expires in '
            . cfg('security.reset_token_minutes') . ' minutes. No email? Ask the SASO admin for a reset code.'];
        if ($ident === '' || mb_strlen($ident) > 190) json_out($generic);

        $key = 'reset:' . strtolower($ident);
        if (lockout_minutes_left($key) !== null) json_out($generic);
        record_attempt($key, false);

        $user = find_login_user($ident);
        $to   = $user ? ($user['email'] ?: $user['google_email']) : null;
        if ($user && $user['account_status'] === 'active' && $to) {
            $token = new_reset_token((int)$user['user_id']);
            $link  = base_url() . '/reset-password.php?token=' . $token;
            [$subject, $html] = email_password_reset($user, $link, (int)cfg('security.reset_token_minutes'));
            $sent = send_mail($to, $subject, $html);
            audit('password_reset_requested', 'Reset link ' . ($sent ? 'emailed' : 'could not be emailed'), $user);
            if (cfg('app.debug')) $generic['debug_link'] = $link;     // local testing only
        }
        json_out($generic);
    }

    case 'reset': {
        $secret = input('token') ?: input('code');
        $email  = trim(input('username') ?: input('email'));
        $pw     = (string)($_POST['password'] ?? '');
        if ($pw !== (string)($_POST['password_confirm'] ?? '')) json_fail('Passwords do not match.');
        if ($p = password_problem($pw)) json_fail($p);

        $key = 'resetuse:' . (strtolower($email) ?: client_ip());
        if (lockout_minutes_left($key) !== null) json_fail('Too many attempts. Please wait a few minutes.', 429);

        $row = find_reset($secret, input('token') ? '' : $email);
        if (!$row) {
            record_attempt($key, false);
            json_fail('That reset link or code is invalid or has expired.', 400);
        }
        q('UPDATE users SET password_hash = ?, must_change_password = 0 WHERE user_id = ?', [password_hash($pw, PASSWORD_DEFAULT), $row['user_id']]);
        q('UPDATE password_resets SET used_at = NOW() WHERE user_id = ?', [$row['user_id']]);
        q('DELETE FROM login_attempts WHERE email = ?', ['uid:' . $row['user_id']]);
        $target = q('SELECT * FROM users WHERE user_id = ?', [$row['user_id']])->fetch();
        audit('password_reset', 'Password reset completed', $target);
        json_out(['ok' => true, 'message' => 'Password updated. You can sign in now.', 'redirect' => url('login.php?reset=1')]);
    }

    default:
        json_fail('Unknown action.', 404);
}
