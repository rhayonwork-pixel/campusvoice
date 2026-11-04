<?php
/** Admin: audit trail, calendar, reports, system settings. */
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/concerns.php';
require_once __DIR__ . '/../includes/reports.php';
require_once __DIR__ . '/../includes/mail.php';
require_once __DIR__ . '/../includes/email.php';

$action = input('action');
$reads = ['activity', 'calendar_month', 'calendar_day', 'reports', 'export', 'settings'];
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
    case 'calendar_month': {
        // One cheap query per month: COUNTS per day by severity (never one row per concern), so 10 or 10,000 concerns look the same.
        $m = input('month');
        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $m)) $m = date('Y-m');
        $from = $m . '-01 00:00:00'; $to = date('Y-m-t 23:59:59', strtotime($m . '-01'));
        $mode = input('mode') === 'new' ? 'new' : 'due';
        $col = $mode === 'new' ? 'c.created_at' : 'c.due_at';
        $where = "$col BETWEEN ? AND ?"; $args = [$from, $to];
        if ($mode === 'due') $where .= ' AND c.status NOT IN ' . CV_OPEN_STATUSES;
        if (($cat = input_int('category_id')) > 0) { $where .= ' AND c.category_id = ?'; $args[] = $cat; }
        $days = [];
        foreach (q("SELECT DATE($col) d, c.severity, COUNT(*) n, SUM(c.due_at < NOW() AND c.status NOT IN " . CV_OPEN_STATUSES . ") late
                    FROM concerns c WHERE $where GROUP BY DATE($col), c.severity", $args)->fetchAll() as $r) {
            $d = &$days[$r['d']];
            $d['sev'][$r['severity']] = (int)$r['n'];
            $d['late'] = ($d['late'] ?? 0) + (int)$r['late'];
            unset($d);
        }
        foreach (q('SELECT DATE(start_at) d, COUNT(*) n FROM events WHERE start_at BETWEEN ? AND ? GROUP BY DATE(start_at)', [$from, $to])->fetchAll() as $r) {
            $days[$r['d']]['ev'] = (int)$r['n'];
        }
        $overdueNow = (int)q('SELECT COUNT(*) FROM concerns WHERE due_at < NOW() AND status NOT IN ' . CV_OPEN_STATUSES)->fetchColumn();
        json_out(['ok' => true, 'month' => $m, 'mode' => $mode, 'days' => $days, 'overdue_now' => $overdueNow]);
    }

    case 'calendar_day': {
        $date = input('date');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !strtotime($date)) json_fail('Choose a valid day.');
        $mode = input('mode') === 'new' ? 'new' : 'due';
        $col = $mode === 'new' ? 'c.created_at' : 'c.due_at';
        $where = "DATE($col) = ?"; $args = [$date];
        if ($mode === 'due') $where .= ' AND c.status NOT IN ' . CV_OPEN_STATUSES;
        if (($cat = input_int('category_id')) > 0) { $where .= ' AND c.category_id = ?'; $args[] = $cat; }
        $counts = ['Urgent' => 0, 'Major' => 0, 'Minor' => 0];
        foreach (q("SELECT c.severity, COUNT(*) n FROM concerns c WHERE $where GROUP BY c.severity", $args)->fetchAll() as $r) $counts[$r['severity']] = (int)$r['n'];
        $sevs = array_values(array_intersect(['Urgent', 'Major', 'Minor'], array_map('trim', explode(',', input('severity')))));
        if ($sevs && count($sevs) < 3) { $where .= ' AND c.severity IN (' . implode(',', array_fill(0, count($sevs), '?')) . ')'; $args = array_merge($args, $sevs); }
        [$per, $page, $offset] = paginate_params(10);
        $total = (int)q("SELECT COUNT(*) FROM concerns c WHERE $where", $args)->fetchColumn();
        // No reporter columns here: the calendar never needs to know who sent a concern.
        $rows = q("SELECT c.concern_id, c.concern_code, c.title, c.severity, c.status, c.due_at, c.created_at, cat.category_name,
                          (c.due_at < NOW() AND c.status NOT IN " . CV_OPEN_STATUSES . ") AS overdue,
                          (SELECT CONCAT(u.first_name, ' ', u.last_name) FROM assignments a JOIN users u ON u.user_id = a.staff_id WHERE a.concern_id = c.concern_id AND a.assignment_status = 'active' LIMIT 1) AS staff_name
                   FROM concerns c JOIN categories cat ON cat.category_id = c.category_id
                   WHERE $where ORDER BY FIELD(c.severity,'Urgent','Major','Minor'), c.due_at ASC, c.concern_id LIMIT $per OFFSET $offset", $args)->fetchAll();
        $events = q('SELECT event_id, title, start_at, end_at, notes FROM events WHERE DATE(start_at) = ? ORDER BY start_at', [$date])->fetchAll();
        json_out(['ok' => true, 'date' => $date, 'mode' => $mode, 'counts' => $counts, 'total' => $total, 'page' => $page, 'per' => $per, 'rows' => $rows, 'events' => $events]);
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
        // Designed Excel report (default) or plain CSV for the chosen period. Anonymous reporters stay masked for everyone.
        [$from, $to, $label] = report_range();
        $csv = input('format') === 'csv';
        audit('report_exported', 'Exported concerns ' . ($csv ? 'CSV' : 'Excel') . ' (' . $label . ')');
        $st = db()->prepare('SELECT ' . concern_columns() . ', ' . reporter_columns() . ' ' . concern_joins() . ' WHERE c.created_at BETWEEN ? AND ? ORDER BY c.created_at DESC');
        $st->execute([$from, $to]);
        $rows = $st->fetchAll();

        if ($csv) {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="campusvoice-concerns-' . date('Ymd') . '.csv"');
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Code', 'Type', 'Title', 'Location', 'Severity', 'Status', 'Reporter', 'Course/Year/Section', 'Submitted', 'Due', 'Resolved']);
            foreach ($rows as $r) {
                $clean = function ($v) { return is_string($v) && preg_match('/^[=+\-@]/', $v) ? "'" . $v : $v; };   // CSV-injection guard
                fputcsv($out, array_map($clean, [$r['concern_code'], $r['category_name'], $r['title'], $r['location'], $r['severity'], $r['status'], $r['reporter_name'], reporter_context($r), $r['created_at'], $r['due_at'], $r['resolved_at']]));
            }
            fclose($out);
            exit;
        }

        require_once __DIR__ . '/../includes/xlsx.php';
        $fmt = function ($d) { return $d ? date('M j, Y g:i A', strtotime($d)) : '—'; };
        $stStyle = function ($s) {
            if (in_array($s, ['Resolved', 'Closed'], true)) return 'st_done';
            if (in_array($s, ['Additional Info Needed', 'Waiting for Resources'], true)) return 'st_wait';
            if ($s === 'Reopened') return 'st_bad';
            if ($s === 'Cancelled') return 'st_off';
            return 'st_open';
        };
        $book = new XlsxBook();
        $sh = $book->sheet('Concerns', [15, 24, 40, 28, 11, 22, 22, 26, 19, 19, 19]);
        $sh->add([['CampusVoice  ·  Concern report', 'title']], 36, 11, 'band');
        $sh->add([['City of Malabon University · Student Affairs and Services Office  ·  ' . $label, 'subband']], 22, 11, 'band');
        $sh->add([['Generated ' . date('M j, Y g:i A') . '  ·  ' . count($rows) . ' concern' . (count($rows) === 1 ? '' : 's') . '  ·  anonymous senders are shown as “Anonymous Student”', 'sub']], 20);
        $sh->blank(8);
        $hr = $sh->add([['Code', 'headleft'], ['Type', 'headleft'], ['Title', 'headleft'], ['Location', 'headleft'], ['Severity', 'head'], ['Status', 'head'], ['Reporter', 'headleft'], ['Course · Year · Section', 'headleft'], ['Submitted', 'head'], ['Due', 'head'], ['Resolved', 'head']], 28);
        foreach ($rows as $i => $r) {
            $z = $i % 2 ? 'z' : '';
            $sh->add([[$r['concern_code'], 'text' . $z], [$r['category_name'], 'text' . $z], [$r['title'], 'text' . $z], [$r['location'] ?: '—', 'text' . $z],
                [$r['severity'], strtolower($r['severity'])], [$r['status'], $stStyle($r['status'])], [$r['reporter_name'], 'text' . $z], [reporter_context($r) ?: '—', 'text' . $z],
                [$fmt($r['created_at']), 'ctr' . $z], [$fmt($r['due_at']), 'ctr' . $z], [$fmt($r['resolved_at']), 'ctr' . $z]], 24);
        }
        $sh->freezeRows($hr);
        $sh->filter('A' . $hr . ':K' . ($hr + max(1, count($rows))));
        $sh->merge('A1:K1'); $sh->merge('A2:K2'); $sh->merge('A3:K3');

        $d = report_data($from, $to, false);
        $sm = $d['summary'];
        $sum = $book->sheet('Summary', [34, 14, 16, 16, 16, 16], 'D9A441');
        $sum->add([['CampusVoice  ·  Summary', 'title']], 36, 6, 'band');
        $sum->add([[$label, 'subband']], 22, 6, 'band');
        $sum->blank(12);
        $sum->add([['Total concerns', 'kpilabel'], ['Resolved', 'kpilabel'], ['Closed', 'kpilabel'], ['Overdue', 'kpilabel'], ['Anonymous', 'kpilabel'], ['Avg. rating', 'kpilabel']], 22);
        $sum->add([[(int)$sm['total'], 'kpi'], [(int)$sm['resolved'], 'kpi'], [(int)$sm['closed'], 'kpi'], [(int)$sm['overdue'], 'kpi'], [(int)$sm['anonymous'], 'kpi'], [$d['rating']['avg_rating'] !== null ? (float)$d['rating']['avg_rating'] : '—', 'kpi']], 36);
        $block = function ($title, $list) use ($sum) {
            $sum->blank(14);
            $sum->add([[$title, 'h2'], ['', 'h2']], 26);
            if (!$list) { $sum->add([['No data in this period', 'label'], ['', 'label']], 22); return; }
            foreach ($list as $x) $sum->add([[$x['label'], 'label'], [(int)$x['n'], 'num']], 22);
        };
        $block('By concern type', $d['by_category']);
        $block('By severity', $d['by_severity']);
        $block('By status', $d['by_status']);
        $block('By location (campus facilities)', $d['by_location']);
        $sum->blank(14);
        $sum->add([['Staff · completed concerns', 'h2'], ['Count', 'h2'], ['Avg. hours', 'h2']], 26);
        foreach ($d['staff'] as $x) $sum->add([[$x['label'], 'label'], [(int)$x['n'], 'num'], [$x['avg_hours'] !== null ? (float)$x['avg_hours'] : '—', 'num']], 22);
        if (!$d['staff']) $sum->add([['No completed assignments in this period', 'label'], ['', 'label'], ['', 'label']], 22);
        $sum->merge('A1:F1'); $sum->merge('A2:F2');

        $book->download('campusvoice-concerns-' . date('Ymd') . '.xlsx');
        exit;
    }

    case 'test_email': {
        // Sends a real test email to the admin's own address so Gmail / mail() can be checked from Settings.
        if (!$u['email']) json_fail('Add an email address to your admin account first.');
        $recent = (int)q("SELECT COUNT(*) FROM audit_logs WHERE action = 'email_test' AND user_id = ? AND created_at > (NOW() - INTERVAL 1 MINUTE)", [$uid])->fetchColumn();
        if ($recent >= 3) json_fail('Please wait a minute before sending another test.', 429);
        $via = cfg('smtp.enabled') ? 'Gmail SMTP' : 'PHP mail()';
        [$subject, $html] = email_test($u, $via);
        $ok = send_mail($u['email'], $subject, $html);
        audit('email_test', $ok ? 'Test email sent via ' . mail_last()['via'] : 'Test email failed: ' . mail_last()['error']);
        if (!$ok) json_fail('Could not send the test email. ' . (mail_last()['error'] ?: 'Check the email settings in config/config.php.'), 502);
        json_out(['ok' => true, 'message' => 'Test email sent to ' . $u['email'] . ' using ' . mail_last()['via'] . '. Check your inbox (and spam).']);
    }

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
