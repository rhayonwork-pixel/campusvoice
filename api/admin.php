<?php
/** Admin: dashboard, concern management, assignment, staff workload, anonymous reveal. */
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/concerns.php';

$action = input('action');
$reads = ['dashboard', 'list', 'workload', 'detail', 'staff_options'];
$u = api_guard(['admin'], !in_array($action, $reads, true));
$uid = (int)$u['user_id'];

const ALL_STATUSES = ['Submitted', 'Under Review', 'Assigned', 'In Progress', 'Waiting for Resources', 'Resolved', 'Reopened', 'Closed', 'Cancelled'];

function admin_concern(int $id): array
{
    $c = q('SELECT * FROM concerns WHERE concern_id = ?', [$id])->fetch();
    if (!$c) json_fail('Concern not found.', 404);
    return $c;
}

function active_assignment(int $concernId): ?array
{
    return q("SELECT a.*, CONCAT(u.first_name,' ',u.last_name) AS staff_name FROM assignments a JOIN users u ON u.user_id = a.staff_id
              WHERE a.concern_id = ? AND a.assignment_status = 'active' LIMIT 1", [$concernId])->fetch() ?: null;
}

switch ($action) {

    case 'dashboard': {
        $s = q("SELECT COUNT(*) total,
                       SUM(status IN ('Submitted','Under Review','Reopened') AND NOT EXISTS (SELECT 1 FROM assignments a WHERE a.concern_id = concerns.concern_id AND a.assignment_status = 'active')) unassigned,
                       SUM(status IN ('Assigned','In Progress','Waiting for Resources')) in_progress,
                       SUM(due_at < NOW() AND status NOT IN " . CV_OPEN_STATUSES . ") overdue,
                       SUM(status IN ('Resolved','Closed')) resolved
                FROM concerns")->fetch();
        // "Needs Your Attention": URGENT concerns only
        $attention = db()->query("SELECT c.concern_id, c.concern_code, c.title, c.status, c.due_at, c.created_at,
                                         (c.due_at < NOW()) AS overdue,
                                         (SELECT CONCAT(u.first_name,' ',u.last_name) FROM assignments a JOIN users u ON u.user_id = a.staff_id WHERE a.concern_id = c.concern_id AND a.assignment_status = 'active' LIMIT 1) AS staff_name
                                  FROM concerns c WHERE c.severity = 'Urgent' AND c.status NOT IN " . CV_OPEN_STATUSES . "
                                  ORDER BY c.due_at ASC LIMIT 8")->fetchAll();
        $recent = db()->query('SELECT ' . concern_columns() . ' ' . concern_joins() . ' ORDER BY c.created_at DESC LIMIT 6')->fetchAll();
        foreach ($recent as &$r) unset($r['description']);
        $by = db()->query('SELECT status, COUNT(*) n FROM concerns GROUP BY status')->fetchAll();
        json_out(['ok' => true, 'counts' => array_map('intval', $s), 'attention' => $attention, 'recent' => $recent, 'by_status' => $by]);
    }

    case 'list': {
        [$per, $page, $offset] = paginate_params();
        $tab = input('tab', 'all');
        $where = ['1=1']; $args = [];
        if ($tab === 'queue') {
            // unassigned only — reassign items now live under Current Assignments
            $where[] = "c.status IN ('Submitted','Under Review','Reopened')";
            $where[] = "NOT EXISTS (SELECT 1 FROM assignments a WHERE a.concern_id = c.concern_id AND a.assignment_status = 'active')";
        } elseif ($tab === 'assignments') {
            $where[] = "EXISTS (SELECT 1 FROM assignments a WHERE a.concern_id = c.concern_id AND a.assignment_status = 'active')";
        }
        if (($s = input('status')) !== '' && in_array($s, ALL_STATUSES, true)) { $where[] = 'c.status = :st'; $args[':st'] = $s; }
        if (($v = input('severity')) !== '' && in_array($v, ['Minor', 'Major', 'Urgent'], true)) { $where[] = 'c.severity = :sv'; $args[':sv'] = $v; }
        if (($t = input('q')) !== '') {
            $where[] = '(c.title LIKE :q OR c.concern_code LIKE :q OR cat.category_name LIKE :q)';
            $args[':q'] = '%' . addcslashes($t, '%_\\') . '%';
        }
        $w = implode(' AND ', $where);
        $from = concern_joins();
        $cst = db()->prepare("SELECT COUNT(*) $from WHERE $w"); $cst->execute($args);
        $total = (int)$cst->fetchColumn();
        $st = db()->prepare('SELECT ' . concern_columns() . ', ' . reporter_columns() . ",
                (SELECT a.staff_id FROM assignments a WHERE a.concern_id = c.concern_id AND a.assignment_status = 'active' LIMIT 1) AS staff_id,
                (SELECT CONCAT(u.first_name,' ',u.last_name) FROM assignments a JOIN users u ON u.user_id = a.staff_id WHERE a.concern_id = c.concern_id AND a.assignment_status = 'active' LIMIT 1) AS staff_name
                $from WHERE $w ORDER BY FIELD(c.severity,'Urgent','Major','Minor'), c.created_at DESC LIMIT $per OFFSET $offset");
        $st->execute($args);
        $rows = $st->fetchAll();
        foreach ($rows as &$r) unset($r['description']);
        $badge = [
            'queue' => (int)db()->query("SELECT COUNT(*) FROM concerns c WHERE c.status IN ('Submitted','Under Review','Reopened') AND NOT EXISTS (SELECT 1 FROM assignments a WHERE a.concern_id = c.concern_id AND a.assignment_status = 'active')")->fetchColumn(),
            'assignments' => (int)db()->query("SELECT COUNT(DISTINCT concern_id) FROM assignments WHERE assignment_status = 'active'")->fetchColumn(),
        ];
        json_out(['ok' => true, 'rows' => $rows, 'total' => $total, 'page' => $page, 'per' => $per, 'tab_counts' => $badge]);
    }

    case 'workload': {
        $rows = db()->query("SELECT u.user_id, CONCAT(u.first_name,' ',u.last_name) AS name, u.position, u.availability, d.department_name,
                    COALESCE(SUM(a.assignment_status = 'active'), 0) AS assigned,
                    COALESCE(SUM(a.assignment_status = 'active' AND c.status = 'In Progress'), 0) AS in_progress,
                    COALESCE(SUM(a.assignment_status = 'active' AND c.status IN ('Assigned','Waiting for Resources','Reopened')), 0) AS pending,
                    COALESCE(SUM(a.assignment_status = 'completed'), 0) AS resolved,
                    COALESCE(SUM(a.assignment_status = 'active' AND c.due_at < NOW()), 0) AS overdue
             FROM users u
             LEFT JOIN departments d ON d.department_id = u.department_id
             LEFT JOIN assignments a ON a.staff_id = u.user_id AND a.assignment_status IN ('active','completed')
             LEFT JOIN concerns c ON c.concern_id = a.concern_id
             WHERE u.role = 'staff' AND u.account_status = 'active'
             GROUP BY u.user_id, u.first_name, u.last_name, u.position, u.availability, d.department_name
             ORDER BY assigned DESC, u.last_name")->fetchAll();
        foreach ($rows as &$r) foreach (['assigned', 'in_progress', 'pending', 'resolved', 'overdue'] as $k) $r[$k] = (int)$r[$k];
        $status = static function (array $r): string {
            if ($r['availability'] === 'on_leave') return 'On leave';
            if ($r['in_progress'] > 0) return 'Working on ' . $r['in_progress'] . ' concern' . ($r['in_progress'] > 1 ? 's' : '');
            if ($r['pending'] > 0) return 'Has pending work';
            return $r['availability'] === 'busy' ? 'Busy' : 'Free';
        };
        foreach ($rows as &$r) $r['current_status'] = $status($r);
        json_out(['ok' => true, 'rows' => $rows, 'updated' => date('c')]);
    }

    case 'detail': {
        $id = input_int('id');
        admin_concern($id);
        $st = db()->prepare('SELECT ' . concern_columns() . ', ' . reporter_columns() . ' ' . concern_joins() . ' WHERE c.concern_id = :id');
        $st->execute([':id' => $id]);
        $c = $st->fetch();
        $history = q("SELECT a.assigned_at, a.assignment_status, a.reason, CONCAT(u.first_name,' ',u.last_name) AS staff_name
                      FROM assignments a JOIN users u ON u.user_id = a.staff_id WHERE a.concern_id = ? ORDER BY a.assigned_at DESC", [$id])->fetchAll();
        $fb = q('SELECT rating, comment FROM feedback WHERE concern_id = ?', [$id])->fetch();
        json_out(['ok' => true, 'concern' => $c, 'assignment' => active_assignment($id), 'history' => $history, 'feedback' => $fb ?: null,
                  'suggested' => suggested_staff((int)$c['category_id']), 'timeline' => concern_timeline($id), 'photos' => concern_photos($id)]);
    }

    case 'staff_options': {
        json_out(['ok' => true, 'staff' => suggested_staff(input_int('category_id'))]);
    }

    case 'assign': {
        $id = input_int('id'); $staffId = input_int('staff_id'); $reason = mb_substr(input('reason'), 0, 255);
        $c = admin_concern($id);
        if (in_array($c['status'], ['Closed', 'Cancelled'], true)) json_fail('This concern is closed and cannot be assigned.');
        $staff = q("SELECT * FROM users WHERE user_id = ? AND role = 'staff' AND account_status = 'active'", [$staffId])->fetch();
        if (!$staff) json_fail('Choose an active staff member.');
        if ($staff['availability'] === 'on_leave') json_fail($staff['first_name'] . ' is on leave. Choose someone else.');
        $prev = active_assignment($id);
        if ($prev && (int)$prev['staff_id'] === $staffId) json_fail('That staff member already has this concern.');
        if ($prev && mb_strlen($reason) < 3) json_fail('Please give a reason for reassigning.');

        $pdo = db(); $pdo->beginTransaction();
        try {
            if ($prev) q("UPDATE assignments SET assignment_status = 'reassigned' WHERE assignment_id = ?", [$prev['assignment_id']]);
            q('INSERT INTO assignments (concern_id, staff_id, assigned_by, reason) VALUES (?,?,?,?)', [$id, $staffId, $uid, $reason ?: null]);
            if (in_array($c['status'], ['Submitted', 'Under Review', 'Reopened'], true)) q("UPDATE concerns SET status = 'Assigned' WHERE concern_id = ?", [$id]);
            add_update($id, $uid, $c['status'] === 'In Progress' ? 'In Progress' : 'Assigned', $prev ? 'Reassigned' : 'Assigned',
                ($prev ? 'Reassigned to ' : 'Assigned to ') . display_name($staff) . ($reason ? ' — ' . $reason : '') . '.');
            $pdo->commit();
        } catch (Throwable $ex) { $pdo->rollBack(); error_log('assign: ' . $ex->getMessage()); json_fail('Could not assign the concern.', 500); }

        notify($staffId, 'New assignment', "You were assigned {$c['concern_code']} ({$c['severity']}): {$c['title']}", $id, $c['severity'] === 'Urgent');
        if ($prev) notify((int)$prev['staff_id'], 'Concern reassigned', "{$c['concern_code']} was reassigned to another staff member.", $id);
        notify((int)$c['student_id'], 'Concern update', "{$c['concern_code']} has been assigned to " . display_name($staff) . '.', $id);
        audit($prev ? 'concern_reassigned' : 'concern_assigned', "{$c['concern_code']} " . ($prev ? 'reassigned' : 'assigned') . ' to ' . display_name($staff));
        json_out(['ok' => true, 'message' => $prev ? 'Concern reassigned.' : 'Concern assigned.']);
    }

    case 'set_status': {
        $id = input_int('id'); $new = input('status'); $note = mb_substr(input('note'), 0, 500);
        $c = admin_concern($id);
        if (!in_array($new, ['Under Review', 'Closed', 'Cancelled', 'Reopened'], true)) json_fail('Choose a valid status.');
        if (in_array($new, ['Cancelled', 'Reopened'], true) && mb_strlen($note) < 3) json_fail('Please add a note.');
        if ($new === 'Closed') q("UPDATE concerns SET status = 'Closed', closed_at = NOW(), resolved_at = COALESCE(resolved_at, NOW()) WHERE concern_id = ?", [$id]);
        elseif ($new === 'Reopened') q("UPDATE concerns SET status = 'Reopened', resolved_at = NULL, closed_at = NULL, due_at = NOW() + INTERVAL ? HOUR WHERE concern_id = ?", [severity_hours($c['severity']), $id]);
        else q('UPDATE concerns SET status = ? WHERE concern_id = ?', [$new, $id]);
        add_update($id, $uid, $new, 'Status Updated', $note ?: 'Status changed by the administrator.');
        notify((int)$c['student_id'], 'Concern update', "{$c['concern_code']} is now {$new}.", $id);
        audit('concern_updated', "{$c['concern_code']} set to {$new}");
        json_out(['ok' => true, 'message' => 'Status updated.']);
    }

    case 'set_severity': {
        $id = input_int('id'); $sev = input('severity');
        $c = admin_concern($id);
        if (!in_array($sev, ['Minor', 'Major', 'Urgent'], true)) json_fail('Invalid severity.');
        if ($sev === $c['severity']) json_fail('That is already the severity.');
        q('UPDATE concerns SET severity = ?, due_at = created_at + INTERVAL ? HOUR WHERE concern_id = ?', [$sev, severity_hours($sev), $id]);
        add_update($id, $uid, null, 'Severity Changed', "Severity changed from {$c['severity']} to {$sev}.");
        notify((int)$c['student_id'], 'Concern update', "{$c['concern_code']} severity is now {$sev}.", $id);
        audit('concern_updated', "{$c['concern_code']} severity {$c['severity']} → {$sev}");
        json_out(['ok' => true, 'message' => 'Severity updated.']);
    }

    case 'reveal': {
        $id = input_int('id'); $reason = input('reason');
        $c = admin_concern($id);
        if (!(int)$c['is_anonymous']) json_fail('This concern is not anonymous.');
        if (mb_strlen($reason) < 10) json_fail('Please give a clear reason (at least 10 characters). This is recorded in the audit trail.');
        $s = q('SELECT school_id, first_name, middle_initial, last_name, email, contact_number, course, year_level, section FROM users WHERE user_id = ?', [$c['student_id']])->fetch();
        audit('identity_revealed', "Revealed reporter of {$c['concern_code']}. Reason: " . $reason);
        json_out(['ok' => true, 'reporter' => ['name' => formal_name($s), 'school_id' => $s['school_id'], 'email' => $s['email'], 'contact' => $s['contact_number']]]);
    }

    default:
        json_fail('Unknown action.', 404);
}
