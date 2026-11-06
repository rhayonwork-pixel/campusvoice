<?php
/** Settings every role can use for their own account. */
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/upload.php';

$u = api_guard(['student', 'staff', 'admin']);
$uid = (int)$u['user_id'];
$action = input('action');

switch ($action) {

    case 'profile': {
        $first = input('first_name'); $last = input('last_name'); $mi = mb_substr(input('middle_initial'), 0, 2);
        $contact = mb_substr(input('contact_number'), 0, 30);
        $nameRe = "/^[\\p{L} .'-]{1,80}$/u";
        if (!preg_match($nameRe, $first) || !preg_match($nameRe, $last)) json_fail('Please enter a valid first and last name.');
        if ($contact !== '' && !preg_match('/^[0-9 +()-]{6,30}$/', $contact)) json_fail('Enter a valid contact number.');
        $sql = 'UPDATE users SET first_name = ?, middle_initial = ?, last_name = ?, contact_number = ?';
        $args = [$first, $mi ?: null, $last, $contact ?: null];
        if ($u['role'] === 'student') {
            $course = input('course'); $year = input_int('year_level'); $section = input('section');
            if ($course === '' || $section === '' || $year < 1 || $year > 6) json_fail('Please complete your course, year level and section.');
            $sql .= ', course = ?, year_level = ?, section = ?';
            array_push($args, mb_substr($course, 0, 60), $year, mb_substr($section, 0, 20));
        }
        if ($u['role'] !== 'student') {
            $sql .= ', position = ?';
            $args[] = mb_substr(input('position'), 0, 120) ?: null;
        }
        q($sql . ' WHERE user_id = ?', array_merge($args, [$uid]));
        audit('profile_updated', 'Updated own profile');
        json_out(['ok' => true, 'message' => 'Profile saved.']);
    }

    case 'password': {
        $cur = (string)($_POST['current_password'] ?? '');
        $new = (string)($_POST['password'] ?? '');
        if (!password_verify($cur, $u['password_hash'])) json_fail('Your current password is incorrect.');
        if ($new !== (string)($_POST['password_confirm'] ?? '')) json_fail('New passwords do not match.');
        if (hash_equals($cur, $new)) json_fail('Choose a password different from your current one.');
        if ($p = password_problem($new)) json_fail($p);
        q('UPDATE users SET password_hash = ?, must_change_password = 0 WHERE user_id = ?', [password_hash($new, PASSWORD_DEFAULT), $uid]);
        session_regenerate_id(true);
        remember_forget($uid);
        audit('password_changed', 'Changed own password');
        json_out(['ok' => true, 'message' => 'Password updated.', 'redirect' => url(home_for($u['role']))]);
    }

    case 'google_unlink': {
        if (!$u['google_sub']) json_fail('No Google account is linked.');
        q('UPDATE users SET google_sub = NULL, google_email = NULL, google_linked_at = NULL WHERE user_id = ?', [$uid]);
        audit('google_unlinked', 'Unlinked Google account ' . $u['google_email']);
        json_out(['ok' => true, 'message' => 'Google account unlinked. Sign in with your username from now on.']);
    }

    case 'prefs': {
        $inapp = input('pref_notify_inapp') === '1' ? 1 : 0;
        $email = input('pref_notify_email') === '1' ? 1 : 0;
        $anon  = ($u['role'] === 'student' && input('pref_default_anonymous') === '1') ? 1 : 0;
        q('UPDATE users SET pref_notify_inapp = ?, pref_notify_email = ?, pref_default_anonymous = ? WHERE user_id = ?', [$inapp, $email, $anon, $uid]);
        json_out(['ok' => true, 'message' => 'Preferences saved.']);
    }

    case 'availability': {
        if ($u['role'] !== 'staff') json_fail('Only staff can set availability.', 403);
        $a = input('availability');
        if (!in_array($a, ['available', 'busy', 'on_leave'], true)) json_fail('Invalid availability.');
        q('UPDATE users SET availability = ? WHERE user_id = ?', [$a, $uid]);
        audit('availability_changed', 'Availability set to ' . $a);
        json_out(['ok' => true, 'message' => 'Availability updated.']);
    }

    case 'avatar': {
        try {
            $files = files_of('avatar');
            if (!$files) json_fail('Choose a photo first.');
            $path = save_image($files[0], 'avatars');
        } catch (RuntimeException $ex) { json_fail($ex->getMessage()); }
        if ($u['avatar'] && is_file(CV_ROOT . '/uploads/' . $u['avatar'])) @unlink(CV_ROOT . '/uploads/' . $u['avatar']);
        q('UPDATE users SET avatar = ? WHERE user_id = ?', [$path, $uid]);
        json_out(['ok' => true, 'message' => 'Profile photo updated.', 'reload' => true]);
    }

    case 'avatar_remove': {
        if ($u['avatar'] && is_file(CV_ROOT . '/uploads/' . $u['avatar'])) @unlink(CV_ROOT . '/uploads/' . $u['avatar']);
        q('UPDATE users SET avatar = NULL WHERE user_id = ?', [$uid]);
        json_out(['ok' => true, 'message' => 'Profile photo removed.', 'reload' => true]);
    }

    default:
        json_fail('Unknown action.', 404);
}
