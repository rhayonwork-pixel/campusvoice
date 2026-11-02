<?php
/** Admin: audit trail, calendar, reports, system settings. */
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/concerns.php';
require_once __DIR__ . '/../includes/reports.php';
require_once __DIR__ . '/../includes/mail.php';
require_once __DIR__ . '/../includes/email.php';

$action = input('action');
$reads = ['activity', 'calendar', 'reports', 'export', 'settings'];
$u = api_guard(['admin'], !in_array($action, $reads, true));
$uid = (int)$u['user_id'];

switch ($action) {

    /* ---------- audit trail ---------- */
    case 'activity': {
        [$per, $page, $offset] = paginate_params(15);
        $where = ['1=1']; $args = [];
        if (($t = input('q')) !== '') {
            $where[] = "CONCAT_WS(' ', details, user_label, action, ip_address) LIKE :q";
            $args[':q'] = '%' . addcslashes($t, '%_\\') . '%';
        }
        if (($a = input('type')) !== '') { $where[] = 'action = :a'; $args[':a'] = $a; }
        if (($usr = input('user')) !== '') { $where[] = 'user_label LIKE :u'; $args[':u'] = '%' . addcslashes($usr, '%_\\') . '%'; }
        if (($r = input('role')) !== '') { $where[] = 'role = :r'; $args[':r'] = $r; }
        $from = input('from'); $to = input('to');
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) { $where[] = 'created_at >= :f'; $args[':f'] = $from . ' 00:00:00'; }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) { $where[] = 'created_at <= :t'; $args[':t'] = $to . ' 23:59:59'; }
        $w = implode(' AND ', $where);
        $cst = db()->prepare("SELECT COUNT(*) FROM audit_logs WHERE $w"); $cst->execute($args);
        $total = (int)$cst->fetchColumn();
        $st = db()->prepare("SELECT log_id, user_label, role, action, details, ip_address, created_at FROM audit_logs WHERE $w ORDER BY created_at DESC, log_id DESC LIMIT $per OFFSET $offset");
        $st->execute($args);
        $types = db()->query('SELECT DISTINCT action FROM audit_logs ORDER BY action')->fetchAll(PDO::FETCH_COLUMN);
        json_out(['ok' => true, 'rows' => $st->fetchAll(), 'total' => $total, 'page' => $page, 'per' => $per, 'types' => $types]);
    }

    /* ---------- calendar (FullCalendar feed) ---------- */
    case 'calendar': {
        $start = input('start'); $end = input('end');
        $s = preg_match('/^\d{4}-\d{2}-\d{2}/', $start) ? substr($start, 0, 10) . ' 00:00:00' : date('Y-m-01 00:00:00');
        $e = preg_match('/^\d{4}-\d{2}-\d{2}/', $end) ? substr($end, 0, 10) . ' 23:59:59' : date('Y-m-t 23:59:59');
        $colors = ['Minor' => '#0f7b4f', 'Major' => '#b25e09', 'Urgent' => '#b42318'];
        $ev = [];

        foreach (q("SELECT concern_id, concern_code, title, severity, status, due_at FROM concerns
                    WHERE due_at BETWEEN ? AND ? AND status NOT IN " . CV_OPEN_STATUSES, [$s, $e])->fetchAll() as $r) {
            $ev[] = ['id' => 'due-' . $r['concern_id'], 'title' => 'Due: ' . $r['concern_code'] . ' ' . $r['title'], 'start' => str_replace(' ', 'T', $r['due_at']),
                'color' => $colors[$r['severity']], 'url' => url('admin/concern.php?id=' . $r['concern_id']), 'extendedProps' => ['kind' => 'deadline', 'severity' => $r['severity'], 'status' => $r['status']]];
        }
        foreach (q("SELECT a.assignment_id, a.assigned_at, c.concern_id, c.concern_code, c.title, CONCAT(u.first_name,' ',u.last_name) AS staff
                    FROM assignments a JOIN concerns c ON c.concern_id = a.concern_id JOIN users u ON u.user_id = a.staff_id
                    WHERE a.assigned_at BETWEEN ? AND ?", [$s, $e])->fetchAll() as $r) {
            $ev[] = ['id' => 'asg-' . $r['assignment_id'], 'title' => $r['staff'] . ' ← ' . $r['concern_code'], 'start' => str_replace(' ', 'T', $r['assigned_at']),
                'color' => '#5440b8', 'url' => url('admin/concern.php?id=' . $r['concern_id']), 'extendedProps' => ['kind' => 'assignment']];
        }
        foreach (q('SELECT * FROM events WHERE start_at BETWEEN ? AND ?', [$s, $e])->fetchAll() as $r) {
            $ev[] = ['id' => 'evt-' . $r['event_id'], 'title' => $r['title'], 'start' => str_replace(' ', 'T', $r['start_at']), 'end' => $r['end_at'] ? str_replace(' ', 'T', $r['end_at']) : null,
                'color' => '#173d7a', 'extendedProps' => ['kind' => 'event', 'event_id' => (int)$r['event_id'], 'notes' => $r['notes']]];
        }
        json_out($ev === [] ? ['ok' => true, 'events' => []] : ['ok' => true, 'events' => $ev]);
    }

    case 'event_save': {
        $title = input('title'); $start = input('start_at'); $end = input('end_at'); $notes = mb_substr(input('notes'), 0, 255) ?: null;
        if ($title === '' || mb_strlen($title) > 150) json_fail('Enter an event title.');
        $s = date_create($start); if (!$s) json_fail('Choose a start date and time.');
        $e = $end !== '' ? date_create($end) : null;
        if ($end !== '' && !$e) json_fail('Invalid end time.');
        if ($e && $e < $s) json_fail('End time must be after the start.');
        q('INSERT INTO events (title, start_at, end_at, notes, created_by) VALUES (?,?,?,?,?)', [$title, $s->format('Y-m-d H:i:s'), $e ? $e->format('Y-m-d H:i:s') : null, $notes, $uid]);
        audit('event_added', $title);
        json_out(['ok' => true, 'message' => 'Event added.']);
    }

    case 'event_delete': {
        q('DELETE FROM events WHERE event_id = ?', [input_int('event_id')]);
        audit('event_deleted', 'Deleted calendar event #' . input_int('event_id'));
        json_out(['ok' => true, 'message' => 'Event removed.']);
    }

    /* ---------- reports ---------- */
    case 'reports': {
        [$from, $to, $label] = report_range();
        $sum = q("SELECT COUNT(*) total, SUM(status IN ('Resolved','Closed')) resolved, SUM(is_anonymous = 1) anonymous,
                         SUM(due_at < NOW() AND status NOT IN " . CV_OPEN_STATUSES . ") overdue,
                         ROUND(AVG(CASE WHEN resolved_at IS NOT NULL THEN TIMESTAMPDIFF(HOUR, created_at, resolved_at) END), 1) avg_hours
                  FROM concerns WHERE created_at BETWEEN ? AND ?", [$from, $to])->fetch();
        $byCat = q('SELECT cat.category_name label, COUNT(*) n FROM concerns c JOIN categories cat ON cat.category_id = c.category_id WHERE c.created_at BETWEEN ? AND ? GROUP BY cat.category_id ORDER BY n DESC', [$from, $to])->fetchAll();
        $bySev = q('SELECT severity label, COUNT(*) n FROM concerns WHERE created_at BETWEEN ? AND ? GROUP BY severity ORDER BY FIELD(severity,"Urgent","Major","Minor")', [$from, $to])->fetchAll();
        $byStatus = q('SELECT status label, COUNT(*) n FROM concerns WHERE created_at BETWEEN ? AND ? GROUP BY status ORDER BY n DESC', [$from, $to])->fetchAll();
        $byLoc = q("SELECT CONCAT(l.building, ' · ', l.area) label, COUNT(*) n FROM concerns c JOIN locations l ON l.location_id = c.location_id WHERE c.created_at BETWEEN ? AND ? GROUP BY l.location_id ORDER BY n DESC LIMIT 6", [$from, $to])->fetchAll();
        $rating = q('SELECT ROUND(AVG(f.rating), 2) avg_rating, COUNT(*) n FROM feedback f JOIN concerns c ON c.concern_id = f.concern_id WHERE c.created_at BETWEEN ? AND ?', [$from, $to])->fetch();
        $staff = q("SELECT CONCAT(u.first_name,' ',u.last_name) label, COUNT(*) n, ROUND(AVG(TIMESTAMPDIFF(HOUR, a.assigned_at, a.completed_at)), 1) avg_hours
                    FROM assignments a JOIN users u ON u.user_id = a.staff_id WHERE a.assignment_status = 'completed' AND a.completed_at BETWEEN ? AND ? GROUP BY u.user_id ORDER BY n DESC LIMIT 8", [$from, $to])->fetchAll();
        json_out(['ok' => true, 'label' => $label, 'from' => substr($from, 0, 10), 'to' => substr($to, 0, 10), 'summary' => $sum, 'by_category' => $byCat, 'by_severity' => $bySev, 'by_status' => $byStatus, 'by_location' => $byLoc, 'rating' => $rating, 'staff' => $staff]);
    }

    case 'export': {
        // CSV download for the chosen period; anonymous reporters stay masked.
        [$from, $to, $label] = report_range();
        audit('report_exported', 'Exported concerns CSV (' . $label . ')');
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="campusvoice-concerns-' . date('Ymd') . '.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['Code', 'Title', 'Category', 'Location', 'Severity', 'Status', 'Reporter', 'Course/Year/Section', 'Submitted', 'Due', 'Resolved']);
        $st = db()->prepare('SELECT ' . concern_columns() . ', ' . reporter_columns() . ' ' . concern_joins() . ' WHERE c.created_at BETWEEN ? AND ? ORDER BY c.created_at DESC');
        $st->execute([$from, $to]);
        while ($r = $st->fetch()) {
            $clean = static fn($v) => is_string($v) && preg_match('/^[=+\-@]/', $v) ? "'" . $v : $v;   // CSV-injection guard
            fputcsv($out, array_map($clean, [$r['concern_code'], $r['title'], $r['category_name'], $r['location'], $r['severity'], $r['status'], $r['reporter_name'], reporter_context($r), $r['created_at'], $r['due_at'], $r['resolved_at']]));
        }
        fclose($out);
        exit;
    }

    /* ---------- email ---------- */
    case 'test_email': {
        $last = null;
        [$subject, $html] = email_test($u, cfg('smtp.enabled') ? 'Gmail SMTP (' . cfg('smtp.user') . ')' : 'PHP mail()');
        $ok = send_mail($u['email'], $subject, $html);
        $info = mail_last();
        audit('email_test', ($ok ? 'Test email sent via ' . $info['via'] : 'Test email failed: ' . $info['error']) . ' to ' . $u['email']);
        if ($ok) json_out(['ok' => true, 'message' => 'Test email sent to ' . $u['email'] . ' via ' . $info['via'] . '. Check the inbox (and spam).']);
        json_fail('Could not send the test email. ' . $info['error'], 502);
    }

    /* ---------- system settings ---------- */
    case 'settings': {
        $keys = ['sla_minor', 'sla_major', 'sla_urgent', 'student_signup', 'auto_assign', 'signup_email_domain', 'contact_email', 'contact_address', 'sec_max_failed', 'sec_lockout_minutes', 'sec_idle_minutes'];
        $vals = [];
        foreach ($keys as $k) $vals[$k] = setting($k, '');
        $vals['auto_assign'] = $vals['auto_assign'] === '' ? '1' : $vals['auto_assign'];
        $vals['sec_max_failed'] = $vals['sec_max_failed'] ?: (string)cfg('security.max_failed_per_email');
        $vals['sec_lockout_minutes'] = $vals['sec_lockout_minutes'] ?: (string)cfg('security.lockout_minutes');
        $vals['sec_idle_minutes'] = $vals['sec_idle_minutes'] ?: (string)cfg('security.idle_timeout_minutes');
        json_out(['ok' => true, 'settings' => $vals,
            'hours' => q('SELECT day_of_week, TIME_FORMAT(open_time, "%H:%i") open_time, TIME_FORMAT(close_time, "%H:%i") close_time, active FROM working_hours ORDER BY day_of_week')->fetchAll()]);
    }

    case 'google_save': {
        // Simple form instead of editing config.php. The secret is never sent back to the browser; blank = keep the saved one.
        $cid = trim(input('client_id')); $secret = trim((string)($_POST['client_secret'] ?? '')); $domain = strtolower(trim(input('domain')));
        $on = input('enabled') === '1' ? '1' : '0';
        if ($cid !== '' && !preg_match('/^[0-9A-Za-z._-]{20,200}$/', $cid)) json_fail('That Client ID does not look right. Copy it again from Google (it ends with .apps.googleusercontent.com).');
        if ($secret !== '' && !preg_match('/^[0-9A-Za-z._-]{10,200}$/', $secret)) json_fail('That Client secret does not look right. Copy it again from Google.');
        if ($domain !== '' && !preg_match('/^[a-z0-9.-]+\.[a-z]{2,}$/', $domain)) json_fail('Enter the school email domain like school.edu.ph, or leave it empty.');
        $hasId = $cid !== '' || setting('google_client_id', '') !== '' || cfg('google.client_id');
        $hasSecret = $secret !== '' || setting('google_client_secret', '') !== '' || cfg('google.client_secret');
        if ($on === '1' && (!$hasId || !$hasSecret)) json_fail('Paste the Client ID and the Client secret first, then turn it on.');
        $put = function ($k, $v) { q('INSERT INTO settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)', [$k, $v]); };
        if ($cid !== '') $put('google_client_id', $cid);
        if ($secret !== '') $put('google_client_secret', $secret);
        $put('google_domain', $domain); $put('google_enabled', $on);
        audit('settings_changed', 'Google sign-in ' . ($on === '1' ? 'turned on' : 'turned off') . ($cid !== '' ? ' (Client ID updated)' : '') . ($secret !== '' ? ' (secret updated)' : ''));
        json_out(['ok' => true, 'message' => $on === '1' ? 'Google sign-in is on.' : 'Saved. Google sign-in is off.']);
    }

    case 'settings_save': {
        // Each Settings section saves only its own fields: validate and store whatever keys were sent.
        $int = static function (string $k, int $min, int $max) {
            $v = input_int($k, -1);
            if ($v < $min || $v > $max) json_fail("Value for {$k} must be between {$min} and {$max}.");
            return (string)$v;
        };
        $rules = [
            'sla_minor' => function () use ($int) { return $int('sla_minor', 1, 2000); },
            'sla_major' => function () use ($int) { return $int('sla_major', 1, 2000); },
            'sla_urgent' => function () use ($int) { return $int('sla_urgent', 1, 2000); },
            'sec_max_failed' => function () use ($int) { return $int('sec_max_failed', 3, 20); },
            'sec_lockout_minutes' => function () use ($int) { return $int('sec_lockout_minutes', 1, 240); },
            'sec_idle_minutes' => function () use ($int) { return $int('sec_idle_minutes', 5, 480); },
            'student_signup' => function () { return input('student_signup') === '1' ? '1' : '0'; },
            'auto_assign' => function () { return input('auto_assign') === '1' ? '1' : '0'; },
            'signup_email_domain' => function () {
                $d = strtolower(input('signup_email_domain'));
                if ($d !== '' && !preg_match('/^[a-z0-9.-]+\.[a-z]{2,}$/', $d)) json_fail('Enter a valid email domain, e.g. school.edu.ph');
                return $d;
            },
            'contact_email' => function () {
                $m = input('contact_email');
                if ($m !== '' && !filter_var($m, FILTER_VALIDATE_EMAIL)) json_fail('Enter a valid contact email.');
                return $m;
            },
            'contact_address' => function () { return mb_substr(input('contact_address'), 0, 200); },
        ];
        $set = [];
        foreach ($rules as $k => $fn) if (array_key_exists($k, $_POST)) $set[$k] = $fn();
        if (!$set) json_fail('Nothing to save.');
        foreach ($set as $k => $v) q('INSERT INTO settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)', [$k, $v]);
        audit('settings_changed', 'Updated ' . implode(', ', array_keys($set)));
        json_out(['ok' => true, 'message' => 'Settings saved.']);
    }

    case 'hours_save': {
        for ($d = 0; $d <= 6; $d++) {
            $active = input("active_$d") === '1' ? 1 : 0;
            $open = input("open_$d"); $close = input("close_$d");
            if ($active) {
                if (!preg_match('/^\d{2}:\d{2}$/', $open) || !preg_match('/^\d{2}:\d{2}$/', $close) || $open >= $close) json_fail('Check the opening and closing time for day ' . $d . '.');
                q('UPDATE working_hours SET active = 1, open_time = ?, close_time = ? WHERE day_of_week = ?', [$open, $close, $d]);
            } else q('UPDATE working_hours SET active = 0, open_time = NULL, close_time = NULL WHERE day_of_week = ?', [$d]);
        }
        audit('settings_changed', 'Updated working hours');
        json_out(['ok' => true, 'message' => 'Working hours saved.']);
    }

    default:
        json_fail('Unknown action.', 404);
}
