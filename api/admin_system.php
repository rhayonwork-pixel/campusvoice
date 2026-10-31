<?php
/** Admin: audit trail, calendar, reports, system settings. */
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/concerns.php';

$action = input('action');
$reads = ['activity', 'calendar', 'reports', 'export', 'settings'];
$u = api_guard(['admin'], !in_array($action, $reads, true));
$uid = (int)$u['user_id'];

/** Report period: ?from=YYYY-MM-DD&to=YYYY-MM-DD, or ?days=N (0 = all time). Returns [from, to, label]. */
function report_range(): array
{
    $from = input('from'); $to = input('to');
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $to) && $from <= $to) {
        return [$from . ' 00:00:00', $to . ' 23:59:59', date('M j, Y', strtotime($from)) . ' – ' . date('M j, Y', strtotime($to))];
    }
    $days = input_int('days', 30);
    if ($days <= 0) return ['1970-01-01 00:00:00', '2999-12-31 23:59:59', 'All time'];
    $days = min($days, 3650);
    return [date('Y-m-d 00:00:00', strtotime("-{$days} days")), date('Y-m-d 23:59:59'), 'Last ' . $days . ' days'];
}

switch ($action) {

    /* ---------- audit trail ---------- */
    case 'activity': {
        [$per, $page, $offset] = paginate_params(15);
        $where = ['1=1']; $args = [];
        if (($t = input('q')) !== '') {
            $where[] = '(details LIKE :q OR user_label LIKE :q OR action LIKE :q OR ip_address LIKE :q)';
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

    /* ---------- system settings ---------- */
    case 'settings': {
        $keys = ['sla_minor', 'sla_major', 'sla_urgent', 'student_signup', 'signup_email_domain', 'contact_email', 'contact_address', 'sec_max_failed', 'sec_lockout_minutes', 'sec_idle_minutes'];
        $vals = [];
        foreach ($keys as $k) $vals[$k] = setting($k, '');
        $vals['sec_max_failed'] = $vals['sec_max_failed'] ?: (string)cfg('security.max_failed_per_email');
        $vals['sec_lockout_minutes'] = $vals['sec_lockout_minutes'] ?: (string)cfg('security.lockout_minutes');
        $vals['sec_idle_minutes'] = $vals['sec_idle_minutes'] ?: (string)cfg('security.idle_timeout_minutes');
        json_out(['ok' => true, 'settings' => $vals,
            'hours' => q('SELECT day_of_week, TIME_FORMAT(open_time, "%H:%i") open_time, TIME_FORMAT(close_time, "%H:%i") close_time, active FROM working_hours ORDER BY day_of_week')->fetchAll()]);
    }

    case 'settings_save': {
        $int = static function (string $k, int $min, int $max) {
            $v = input_int($k, -1);
            if ($v < $min || $v > $max) json_fail("Value for {$k} must be between {$min} and {$max}.");
            return (string)$v;
        };
        $domain = strtolower(input('signup_email_domain'));
        if ($domain !== '' && !preg_match('/^[a-z0-9.-]+\.[a-z]{2,}$/', $domain)) json_fail('Enter a valid email domain, e.g. school.edu.ph');
        $email = input('contact_email');
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) json_fail('Enter a valid contact email.');
        $set = [
            'sla_minor' => $int('sla_minor', 1, 2000), 'sla_major' => $int('sla_major', 1, 2000), 'sla_urgent' => $int('sla_urgent', 1, 2000),
            'student_signup' => input('student_signup') === '1' ? '1' : '0', 'signup_email_domain' => $domain,
            'contact_email' => $email, 'contact_address' => mb_substr(input('contact_address'), 0, 200),
            'sec_max_failed' => $int('sec_max_failed', 3, 20), 'sec_lockout_minutes' => $int('sec_lockout_minutes', 1, 240), 'sec_idle_minutes' => $int('sec_idle_minutes', 5, 480),
        ];
        foreach ($set as $k => $v) q('INSERT INTO settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)', [$k, $v]);
        audit('settings_changed', 'Updated system settings');
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
