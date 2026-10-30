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
            $where[] = '(u.school_id LIKE :q OR u.first_name LIKE :q OR u.last_name LIKE :q OR u.email LIKE :q)';
            $args[':q'] = '%' . addcslashes($t, '%_\\') . '%';
        }
        $w = implode(' AND ', $where);
        $cst = db()->prepare("SELECT COUNT(*) FROM users u WHERE $w"); $cst->execute($args);
        $total = (int)$cst->fetchColumn();
        $st = db()->prepare("SELECT u.user_id, u.role, u.school_id, u.first_name, u.middle_initial, u.last_name, u.email, u.contact_number, u.position, u.course, u.year_level, u.section,
                                    u.account_status, u.availability, u.department_id, d.department_name, u.last_login_at, u.created_at
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
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) json_fail('Enter a valid email address.');
        if (!preg_match('/^[A-Z0-9-]{3,30}$/', $sid)) json_fail('Enter a valid ID number.');
        if (q('SELECT 1 FROM users WHERE (email = ? OR school_id = ?) AND user_id <> ?', [$email, $sid, $id])->fetchColumn()) json_fail('That email or ID is already in use.', 409);
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
            audit('user_edited', "Edited {$old['email']} ({$role})");
            json_out(['ok' => true, 'message' => 'User updated.']);
        }

        $pw = temp_password();
        q('INSERT INTO users (role, school_id, first_name, middle_initial, last_name, email, password_hash, department_id, position, contact_number, course, year_level, section, must_change_password)
           VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,1)',
            [$role, $sid, $first, $mi ?: null, $last, $email, password_hash($pw, PASSWORD_DEFAULT), $dept, $position, $contact, $course, $year, $section]);
        audit('user_added', "Added {$role} {$email}");
        json_out(['ok' => true, 'message' => 'User created.', 'temp_password' => $pw, 'email' => $email]);
    }

    case 'user_status': {
        $id = input_int('user_id'); $status = input('status');
        if (!in_array($status, ['active', 'inactive', 'suspended'], true)) json_fail('Invalid status.');
        if ($id === $uid) json_fail('You cannot change your own account status.');
        $t = user_row($id);
        q('UPDATE users SET account_status = ? WHERE user_id = ?', [$status, $id]);
        audit('user_edited', "{$t['email']} set to {$status}");
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
        audit('user_deleted', "Deleted {$t['role']} {$t['email']}");
        json_out(['ok' => true, 'message' => 'User deleted.']);
    }

    case 'reset_code': {
        $id = input_int('user_id'); $t = user_row($id);
        $code = new_reset_token($id, true);
        audit('password_reset_issued', "Issued reset code for {$t['email']}");
        json_out(['ok' => true, 'code' => $code, 'email' => $t['email'], 'minutes' => (int)cfg('security.reset_token_minutes')]);
    }

    /* ---------- campus data ---------- */
    case 'categories': {
        json_out(['ok' => true, 'rows' => q('SELECT c.category_id, c.category_name, c.description, c.status, c.default_department_id, d.department_name,
                    (SELECT COUNT(*) FROM concerns x WHERE x.category_id = c.category_id) AS concerns
                    FROM categories c LEFT JOIN departments d ON d.department_id = c.default_department_id ORDER BY c.category_name')->fetchAll()]);
    }

    case 'category_save': {
        $id = input_int('category_id'); $name = input('category_name'); $desc = mb_substr(input('description'), 0, 255) ?: null; $dept = input_int('default_department_id') ?: null;
        if ($name === '' || mb_strlen($name) > 100) json_fail('Enter a category name.');
        if (q('SELECT 1 FROM categories WHERE category_name = ? AND category_id <> ?', [$name, $id])->fetchColumn()) json_fail('That category already exists.', 409);
        if ($id) { q('UPDATE categories SET category_name=?, description=?, default_department_id=? WHERE category_id=?', [$name, $desc, $dept, $id]); }
        else { q('INSERT INTO categories (category_name, description, default_department_id) VALUES (?,?,?)', [$name, $desc, $dept]); }
        audit($id ? 'category_edited' : 'category_added', $name);
        json_out(['ok' => true, 'message' => 'Category saved.']);
    }

    case 'category_toggle': {
        $id = input_int('category_id');
        q("UPDATE categories SET status = IF(status = 'active','inactive','active') WHERE category_id = ?", [$id]);
        audit('category_edited', "Toggled category #{$id}");
        json_out(['ok' => true, 'message' => 'Category updated.']);
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
