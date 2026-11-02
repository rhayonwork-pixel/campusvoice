<?php
/** Admin: users & staff, reset codes, categories, locations. */
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/concerns.php';

$action = input('action');
$reads = ['users', 'departments', 'categories', 'locations'];
$u = api_guard(['admin'], !in_array($action, $reads, true));
$uid = (int)$u['user_id'];

function temp_password(): string
{
    $chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789';
    $pw = '';
    for ($i = 0; $i < 10; $i++) $pw .= $chars[random_int(0, strlen($chars) - 1)];
    return $pw . '#7';   // guarantees letters + numbers + symbol
}

/** Default first password for imported students: {StudentID}@cityofmalabonuniversity.edu.ph */
function student_pattern_password(string $sid): string
{
    $domain = setting('signup_email_domain', '') ?: ((string)cfg('google.allowed_domain') ?: 'cityofmalabonuniversity.edu.ph');
    return $sid . '@' . $domain;
}

function user_row(int $id): array
{
    $r = q('SELECT * FROM users WHERE user_id = ?', [$id])->fetch();
    if (!$r) json_fail('User not found.', 404);
    return $r;
}

switch ($action) {

    case 'departments':
        json_out(['ok' => true, 'rows' => q('SELECT department_id, department_name FROM departments ORDER BY department_type, department_name')->fetchAll()]);

    case 'users': {
        [$per, $page, $offset] = paginate_params();
        $where = ['1=1']; $args = [];
        if (($r = input('role')) !== '' && in_array($r, ['student', 'staff', 'admin'], true)) { $where[] = 'u.role = :role'; $args[':role'] = $r; }
        if (($s = input('status')) !== '' && in_array($s, ['active', 'inactive', 'suspended'], true)) { $where[] = 'u.account_status = :st'; $args[':st'] = $s; }
        if (($t = input('q')) !== '') {
            $where[] = "CONCAT_WS(' ', u.school_id, u.first_name, u.last_name, u.email, u.google_email) LIKE :q";
            $args[':q'] = '%' . addcslashes($t, '%_\\') . '%';
        }
        $w = implode(' AND ', $where);
        $cst = db()->prepare("SELECT COUNT(*) FROM users u WHERE $w"); $cst->execute($args);
        $total = (int)$cst->fetchColumn();
        $st = db()->prepare("SELECT u.user_id, u.role, u.school_id, u.first_name, u.middle_initial, u.last_name, u.email, u.contact_number, u.position, u.course, u.year_level, u.section,
                                    u.account_status, u.availability, u.department_id, d.department_name, u.last_login_at, u.created_at, u.google_email, u.google_linked_at, u.must_change_password
                             FROM users u LEFT JOIN departments d ON d.department_id = u.department_id
                             WHERE $w ORDER BY FIELD(u.role,'admin','staff','student'), u.last_name, u.first_name LIMIT $per OFFSET $offset");
        $st->execute($args);
        $rows = $st->fetchAll();
        foreach ($rows as &$r) {
            // Students: "Surname, Firstname, MI."   Others: "Firstname M. Surname"
            $r['display_name'] = $r['role'] === 'student' ? formal_name($r) : display_name($r);
        }
        json_out(['ok' => true, 'rows' => $rows, 'total' => $total, 'page' => $page, 'per' => $per]);
    }

    case 'user_save': {
        $id = input_int('user_id');
        $role = input('role'); $first = input('first_name'); $last = input('last_name'); $mi = mb_substr(input('middle_initial'), 0, 2);
        $email = strtolower(input('email')); $sid = strtoupper(input('school_id')); $dept = input_int('department_id') ?: null;
        $nameRe = "/^[\\p{L} .'-]{1,80}$/u";
        if (!in_array($role, ['student', 'staff', 'admin'], true)) json_fail('Choose a role.');
        if (!preg_match($nameRe, $first) || !preg_match($nameRe, $last)) json_fail('Enter a valid first and last name.');
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) json_fail('Enter a valid email address, or leave it empty.');
        if ($role !== 'student' && $email === '') json_fail('Staff and admin accounts need an email (for password resets).');
        if (!preg_match('/^[A-Z0-9-]{3,30}$/', $sid)) json_fail('Enter a valid username / ID number (letters, numbers, dashes).');
        if (q('SELECT 1 FROM users WHERE school_id = ? AND user_id <> ?', [$sid, $id])->fetchColumn()) json_fail('That username / ID is already in use.', 409);
        if ($email !== '' && q('SELECT 1 FROM users WHERE email = ? AND user_id <> ?', [$email, $id])->fetchColumn()) json_fail('That email is already in use.', 409);
        $email = $email !== '' ? $email : null;
        if ($dept && !q('SELECT 1 FROM departments WHERE department_id = ?', [$dept])->fetchColumn()) json_fail('Invalid department.');

        $contact = mb_substr(input('contact_number'), 0, 30) ?: null;
        $position = mb_substr(input('position'), 0, 120) ?: null;
        $course = $role === 'student' ? (mb_substr(input('course'), 0, 60) ?: null) : null;
        $year = $role === 'student' ? (input_int('year_level') ?: null) : null;
        $section = $role === 'student' ? (mb_substr(input('section'), 0, 20) ?: null) : null;
        if ($role === 'student' && (!$course || !$year || !$section)) json_fail('Students need a course, year level and section.');

        if ($id) {
            $old = user_row($id);
            if ($id === $uid && $role !== 'admin') json_fail('You cannot change your own role.');
            q('UPDATE users SET role=?, school_id=?, first_name=?, middle_initial=?, last_name=?, email=?, department_id=?, position=?, contact_number=?, course=?, year_level=?, section=?, must_change_password = GREATEST(must_change_password, ?) WHERE user_id=?',
                [$role, $sid, $first, $mi ?: null, $last, $email, $dept, $position, $contact, $course, $year, $section, input('force_change') === '1' ? 1 : 0, $id]);
            audit('user_edited', "Edited {$old['school_id']} ({$role})");
            json_out(['ok' => true, 'message' => 'User updated.']);
        }

        $pw = input('password_mode') === 'pattern' && $role === 'student' ? student_pattern_password($sid) : temp_password();
        q('INSERT INTO users (role, school_id, first_name, middle_initial, last_name, email, password_hash, department_id, position, contact_number, course, year_level, section, must_change_password)
           VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,1)',
            [$role, $sid, $first, $mi ?: null, $last, $email, password_hash($pw, PASSWORD_DEFAULT), $dept, $position, $contact, $course, $year, $section]);
        audit('user_added', "Added {$role} {$sid}");
        json_out(['ok' => true, 'message' => 'User created.', 'temp_password' => $pw, 'username' => $sid, 'email' => $email]);
    }

    case 'user_status': {
        $id = input_int('user_id'); $status = input('status');
        if (!in_array($status, ['active', 'inactive', 'suspended'], true)) json_fail('Invalid status.');
        if ($id === $uid) json_fail('You cannot change your own account status.');
        $t = user_row($id);
        q('UPDATE users SET account_status = ? WHERE user_id = ?', [$status, $id]);
        audit('user_edited', "{$t['school_id']} set to {$status}");
        json_out(['ok' => true, 'message' => 'Account ' . $status . '.']);
    }

    case 'user_delete': {
        $id = input_int('user_id');
        if ($id === $uid) json_fail('You cannot delete your own account.');
        $t = user_row($id);
        $used = (int)q('SELECT (SELECT COUNT(*) FROM concerns WHERE student_id = ?) + (SELECT COUNT(*) FROM assignments WHERE staff_id = ? OR assigned_by = ?)', [$id, $id, $id])->fetchColumn();
        if ($used > 0) json_fail('This user has concern history, so deleting would break records. Deactivate the account instead.', 409);
        if ($t['avatar'] && is_file(CV_ROOT . '/uploads/' . $t['avatar'])) @unlink(CV_ROOT . '/uploads/' . $t['avatar']);
        q('DELETE FROM users WHERE user_id = ?', [$id]);
        audit('user_deleted', "Deleted {$t['role']} {$t['school_id']}");
        json_out(['ok' => true, 'message' => 'User deleted.']);
    }

    case 'reset_code': {
        $id = input_int('user_id'); $t = user_row($id);
        $code = new_reset_token($id, true);
        audit('password_reset_issued', "Issued reset code for {$t['school_id']}");
        json_out(['ok' => true, 'code' => $code, 'username' => $t['school_id'], 'email' => $t['email'], 'minutes' => (int)cfg('security.reset_token_minutes')]);
    }

    /* ---------- bulk student import (masterlist .xlsx / .csv / .docx) ---------- */
    case 'import_preview': {
        require_once __DIR__ . '/../includes/spreadsheet.php';
        $f = $_FILES['file'] ?? null;
        if (!$f || ($f['error'] ?? 1) !== UPLOAD_ERR_OK) json_fail('Choose a file to upload.');
        if ($f['size'] > 2 * 1024 * 1024) json_fail('The file is too big (2 MB max).');
        $ext = strtolower(pathinfo((string)$f['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['xlsx', 'csv', 'docx'], true)) json_fail('Upload an Excel (.xlsx), CSV or Word (.docx) masterlist.');
        try { $sheet = sheet_read($f['tmp_name'], $ext); } catch (Throwable $e) { json_fail($e->getMessage()); }
        $head = sheet_find_header($sheet['rows']);
        if (!$head) json_fail('Could not find the header row. The list needs a "Student ID" column and a "Full name" (or "Last name" + "First name") column.');
        [$hi, $map] = $head;

        $existing = array_flip(array_map('strtoupper', q('SELECT school_id FROM users')->fetchAll(PDO::FETCH_COLUMN)));
        $seen = []; $rows = [];
        foreach (array_slice($sheet['rows'], $hi + 1) as $n => $r) {
            $get = function ($k) use ($r, $map) { return isset($map[$k]) ? trim((string)($r[$map[$k]] ?? '')) : ''; };
            $sid = strtoupper(preg_replace('/\s+/', '', $get('school_id')));
            $full = $get('full_name');
            if ($sid === '' && $full === '' && $get('last_name') === '') continue;           // blank line
            if (isset($map['full_name'])) {
                $on = sheet_split_name($full, true); $off = sheet_split_name($full, false);
            } else {
                $mi = mb_strtoupper(mb_substr($get('middle'), 0, 1));
                $on = $off = [sheet_title_case($get('last_name')), sheet_title_case($get('first_name')), $mi];
            }
            $email = strtolower($get('email'));
            $year = (int)preg_replace('/\D/', '', $get('year_level'));
            $problem = '';
            if (!preg_match('/^[A-Z0-9-]{3,30}$/', $sid)) $problem = 'Invalid student ID';
            elseif ($off[0] === '' || $off[1] === '') $problem = 'Name is incomplete';
            elseif (isset($seen[$sid])) $problem = 'Listed twice in this file';
            elseif (isset($existing[$sid])) $problem = 'Already has an account';
            elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $problem = 'Invalid email';
            $seen[$sid] = true;
            $rows[] = ['line' => $hi + $n + 2, 'school_id' => $sid, 'split_on' => $on, 'split_off' => $off,
                       'course' => mb_substr($get('course'), 0, 60), 'year_level' => $year >= 1 && $year <= 6 ? $year : null,
                       'section' => mb_substr($get('section'), 0, 20), 'email' => $email, 'problem' => $problem];
            if (count($rows) > 500) json_fail('That list is too long. Split it into files of up to 500 students.');
        }
        if (!$rows) json_fail('No student rows were found under the header.');
        $token = bin2hex(random_bytes(12));
        $_SESSION['import'] = ['token' => $token, 'rows' => $rows, 'at' => time()];
        json_out(['ok' => true, 'token' => $token, 'title' => $sheet['title'], 'rows' => $rows,
                  'columns' => array_keys($map), 'has_full_name' => isset($map['full_name']),
                  'ready' => count(array_filter($rows, function ($r) { return $r['problem'] === ''; }))]);
    }

    case 'import_commit': {
        $imp = $_SESSION['import'] ?? null;
        if (!$imp || !hash_equals($imp['token'], input('token')) || time() - $imp['at'] > 1800) json_fail('This preview has expired. Upload the file again.', 409);
        $course = mb_substr(input('course'), 0, 60); $year = input_int('year_level'); $section = mb_substr(strtoupper(input('section')), 0, 20);
        $dept = input_int('department_id') ?: null;
        $lastIsMiddle = input('middle_from_last') === '1';
        $mode = input('password_mode') === 'random' ? 'random' : 'pattern';
        if ($dept && !q('SELECT 1 FROM departments WHERE department_id = ?', [$dept])->fetchColumn()) json_fail('Invalid college.');
        $missing = array_filter($imp['rows'], function ($r) { return $r['problem'] === '' && (!$r['course'] || !$r['year_level'] || !$r['section']); });
        if ($missing && (!$course || $year < 1 || $year > 6 || $section === '')) json_fail('Set the course, year level and section for this batch.');
        @set_time_limit(180);

        $made = []; $skipped = 0;
        $ins = db()->prepare('INSERT INTO users (role, school_id, first_name, middle_initial, last_name, email, password_hash, department_id, course, year_level, section, must_change_password)
                              VALUES ("student",?,?,?,?,?,?,?,?,?,?,1)');
        db()->beginTransaction();
        try {
            foreach ($imp['rows'] as $r) {
                if ($r['problem'] !== '' || q('SELECT 1 FROM users WHERE school_id = ?', [$r['school_id']])->fetchColumn()) { $skipped++; continue; }
                $email = $r['email'] !== '' && !q('SELECT 1 FROM users WHERE email = ?', [$r['email']])->fetchColumn() ? $r['email'] : null;
                [$last, $first, $mi] = $lastIsMiddle ? $r['split_on'] : $r['split_off'];
                $c = $r['course'] ?: $course; $y = $r['year_level'] ?: $year; $sec = $r['section'] ?: $section;
                $pw = $mode === 'pattern' ? student_pattern_password($r['school_id']) : temp_password();
                // cost 8 keeps a big batch fast on free hosting; the hash is upgraded on the student's first sign-in
                $ins->execute([$r['school_id'], mb_substr($first, 0, 80), $mi ?: null, mb_substr($last, 0, 80), $email,
                               password_hash($pw, PASSWORD_BCRYPT, ['cost' => 8]), $dept, $c, $y, $sec]);
                $made[] = ['username' => $r['school_id'], 'name' => $last . ', ' . $first . ($mi ? ' ' . $mi . '.' : ''),
                           'password' => $pw, 'course' => $c, 'year_level' => $y, 'section' => $sec];
            }
            db()->commit();
        } catch (Throwable $e) {
            db()->rollBack();
            throw $e;
        }
        unset($_SESSION['import']);
        audit('students_imported', count($made) . ' student account(s) created from a masterlist' . ($skipped ? ", {$skipped} skipped" : '') . " ({$mode} passwords)");
        json_out(['ok' => true, 'created' => $made, 'skipped' => $skipped, 'mode' => $mode,
                  'message' => count($made) . ' account' . (count($made) === 1 ? '' : 's') . ' created' . ($skipped ? ", {$skipped} skipped" : '') . '.']);
    }

    /* ---------- campus data ---------- */
    case 'categories': {
        $rows = q('SELECT c.category_id, c.category_name, c.description, c.status, c.default_department_id, c.needs_location, c.next_steps, d.department_name,
                    (SELECT COUNT(*) FROM concerns x WHERE x.category_id = c.category_id) AS concerns
                    FROM categories c LEFT JOIN departments d ON d.department_id = c.default_department_id ORDER BY c.status = "inactive", c.category_name')->fetchAll();
        $handlers = q("SELECT h.category_id, u.user_id, CONCAT(u.first_name, ' ', u.last_name) AS name, u.availability
                       FROM category_handlers h JOIN users u ON u.user_id = h.staff_id WHERE u.account_status = 'active' ORDER BY u.first_name")->fetchAll();
        foreach ($rows as &$r) $r['handlers'] = array_values(array_filter($handlers, function ($h) use ($r) { return (int)$h['category_id'] === (int)$r['category_id']; }));
        $staff = q("SELECT u.user_id, CONCAT(u.first_name, ' ', u.last_name) AS name, u.availability, d.department_name
                    FROM users u LEFT JOIN departments d ON d.department_id = u.department_id
                    WHERE u.role = 'staff' AND u.account_status = 'active' ORDER BY u.first_name")->fetchAll();
        json_out(['ok' => true, 'rows' => $rows, 'staff' => $staff]);
    }

    case 'category_save': {
        $id = input_int('category_id'); $name = input('category_name'); $desc = mb_substr(input('description'), 0, 255) ?: null; $dept = input_int('default_department_id') ?: null;
        if ($name === '' || mb_strlen($name) > 100) json_fail('Enter a category name.');
        if (q('SELECT 1 FROM categories WHERE category_name = ? AND category_id <> ?', [$name, $id])->fetchColumn()) json_fail('That category already exists.', 409);
        $next = mb_substr(input('next_steps'), 0, 500) ?: null; $needLoc = input('needs_location') === '1' ? 1 : 0;
        $ids = array_values(array_unique(array_filter(array_map('intval', (array)($_POST['handlers'] ?? [])))));
        if ($ids) {
            $in = implode(',', array_fill(0, count($ids), '?'));
            if ((int)q("SELECT COUNT(*) FROM users WHERE role = 'staff' AND user_id IN ($in)", $ids)->fetchColumn() !== count($ids)) json_fail('Handlers must be staff accounts.');
        }
        $pdo = db(); $pdo->beginTransaction();
        try {
            if ($id) { q('UPDATE categories SET category_name=?, description=?, default_department_id=?, next_steps=?, needs_location=? WHERE category_id=?', [$name, $desc, $dept, $next, $needLoc, $id]); }
            else { q('INSERT INTO categories (category_name, description, default_department_id, next_steps, needs_location) VALUES (?,?,?,?,?)', [$name, $desc, $dept, $next, $needLoc]); $id = (int)$pdo->lastInsertId(); }
            q('DELETE FROM category_handlers WHERE category_id = ?', [$id]);
            foreach ($ids as $sid) q('INSERT INTO category_handlers (category_id, staff_id) VALUES (?,?)', [$id, $sid]);
            $pdo->commit();
        } catch (Throwable $ex) { $pdo->rollBack(); throw $ex; }
        audit('category_edited', "{$name}: " . count($ids) . ' handler(s)');
        json_out(['ok' => true, 'message' => 'Concern type saved.']);
    }

    case 'category_toggle': {
        $id = input_int('category_id');
        q("UPDATE categories SET status = IF(status = 'active','inactive','active') WHERE category_id = ?", [$id]);
        audit('category_edited', "Toggled category #{$id}");
        json_out(['ok' => true, 'message' => 'Concern type updated.']);
    }

    case 'locations': {
        json_out(['ok' => true, 'rows' => q('SELECT l.location_id, l.building, l.floor, l.area, l.status, (SELECT COUNT(*) FROM concerns x WHERE x.location_id = l.location_id) AS concerns
                    FROM locations l ORDER BY l.building, l.floor, l.area')->fetchAll()]);
    }

    case 'location_save': {
        $id = input_int('location_id'); $b = input('building'); $f = input('floor'); $a = input('area');
        if ($b === '' || $f === '' || $a === '' || mb_strlen($b) > 100 || mb_strlen($f) > 40 || mb_strlen($a) > 100) json_fail('Enter the building, floor and area.');
        if (q('SELECT 1 FROM locations WHERE building = ? AND floor = ? AND area = ? AND location_id <> ?', [$b, $f, $a, $id])->fetchColumn()) json_fail('That location already exists.', 409);
        if ($id) q('UPDATE locations SET building=?, floor=?, area=? WHERE location_id=?', [$b, $f, $a, $id]);
        else q('INSERT INTO locations (building, floor, area) VALUES (?,?,?)', [$b, $f, $a]);
        audit($id ? 'location_edited' : 'location_added', "$b · $f · $a");
        json_out(['ok' => true, 'message' => 'Location saved.']);
    }

    case 'location_toggle': {
        $id = input_int('location_id');
        q("UPDATE locations SET status = IF(status = 'active','inactive','active') WHERE location_id = ?", [$id]);
        audit('location_edited', "Toggled location #{$id}");
        json_out(['ok' => true, 'message' => 'Location updated.']);
    }

    default:
        json_fail('Unknown action.', 404);
}
