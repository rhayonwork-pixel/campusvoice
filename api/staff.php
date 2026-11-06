<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/concerns.php';
require __DIR__ . '/../includes/upload.php';
require __DIR__ . '/../includes/visits.php';

$action = input('action');
$u = api_guard(['staff'], !in_array($action, ['summary', 'today', 'list', 'detail', 'visits', 'calendar_month'], true));
$uid = (int)$u['user_id'];
auto_close_resolved();

/** Assignment row for this staff member on a concern, or stop. */
function my_assignment(int $concernId, int $uid): array
{
    $a = q("SELECT * FROM assignments WHERE concern_id = ? AND staff_id = ? AND assignment_status IN ('active','completed') ORDER BY assigned_at DESC LIMIT 1", [$concernId, $uid])->fetch();
    if (!$a) json_fail('Concern not found.', 404);
    return $a;
}

function staff_list_sql(string $extraWhere = ''): string
{
    return 'SELECT ' . concern_columns() . ', ' . reporter_columns() . ', a.assignment_id, a.assigned_at, (a.seen_at IS NULL AND a.assignment_status = "active") AS is_new, a.assignment_status '
         . concern_joins() . ' JOIN assignments a ON a.concern_id = c.concern_id AND a.staff_id = :uid '
         . "WHERE a.assignment_status IN ('active','completed') $extraWhere";
}

switch ($action) {

    case 'summary': {
        $s = q("SELECT SUM(a.assignment_status = 'active') active_total,
                       SUM(a.assignment_status = 'active' AND c.status = 'In Progress') in_progress,
                       SUM(a.assignment_status = 'active' AND c.status IN ('Waiting for Resources','Additional Info Needed')) waiting,
                       SUM(a.assignment_status = 'active' AND c.due_at < NOW()) overdue,
                       SUM(a.assignment_status = 'completed' AND a.completed_at > (NOW() - INTERVAL 7 DAY)) resolved_week
                FROM assignments a JOIN concerns c ON c.concern_id = a.concern_id WHERE a.staff_id = ?", [$uid])->fetch();
        json_out(['ok' => true, 'counts' => array_map('intval', $s), 'availability' => $u['availability']]);
    }

    case 'today': {
        // New assignments, anything assigned today, due today, or overdue
        $st = db()->prepare(staff_list_sql("AND a.assignment_status = 'active' AND (a.seen_at IS NULL OR DATE(a.assigned_at) = CURDATE() OR DATE(c.due_at) = CURDATE() OR c.due_at < NOW())")
            . ' ORDER BY is_new DESC, FIELD(c.severity,"Urgent","Major","Minor"), c.due_at ASC LIMIT 30');
        $st->execute([':uid' => $uid]);
        $rows = $st->fetchAll();
        foreach ($rows as &$r) unset($r['description']);
        json_out(['ok' => true, 'rows' => $rows, 'new_count' => count(array_filter($rows, fn($r) => (int)$r['is_new'] === 1))]);
    }

    case 'list': {
        [$per, $page, $offset] = paginate_params();
        $where = ''; $args = [':uid' => $uid];
        $tab = input('tab', 'active');
        if ($tab === 'active') $where .= " AND a.assignment_status = 'active'";
        elseif ($tab === 'done') $where .= " AND a.assignment_status = 'completed'";
        if (($s = input('status')) !== '') { $where .= ' AND c.status = :st'; $args[':st'] = $s; }
        if (($v = input('severity')) !== '') { $where .= ' AND c.severity = :sv'; $args[':sv'] = $v; }
        if (($t = input('q')) !== '') { $where .= " AND CONCAT_WS(' ', c.title, c.concern_code) LIKE :q"; $args[':q'] = '%' . addcslashes($t, '%_\\') . '%'; }

        $base = staff_list_sql($where);
        $cst = db()->prepare('SELECT COUNT(*) FROM (' . $base . ') t'); $cst->execute($args);
        $total = (int)$cst->fetchColumn();
        $st = db()->prepare($base . " ORDER BY is_new DESC, FIELD(c.severity,'Urgent','Major','Minor'), c.due_at ASC LIMIT $per OFFSET $offset");
        $st->execute($args);
        $rows = $st->fetchAll();
        foreach ($rows as &$r) unset($r['description']);
        json_out(['ok' => true, 'rows' => $rows, 'total' => $total, 'page' => $page, 'per' => $per]);
    }

    case 'detail': {
        $id = input_int('id');
        $a = my_assignment($id, $uid);
        if ($a['seen_at'] === null && $a['assignment_status'] === 'active') q('UPDATE assignments SET seen_at = NOW() WHERE assignment_id = ?', [$a['assignment_id']]);
        $st = db()->prepare('SELECT ' . concern_columns() . ', ' . reporter_columns() . ' ' . concern_joins() . ' WHERE c.concern_id = :id');
        $st->execute([':id' => $id]);
        $visits = visit_rows($id);
        json_out(['ok' => true, 'concern' => $st->fetch(), 'assignment_status' => $a['assignment_status'], 'visit' => visit_active($visits), 'visits' => $visits,
                  'timeline' => concern_timeline($id), 'photos' => concern_photos($id)]);
    }

    /** My calendar: my meetings and the due dates of my open concerns, for one month. No student names. */
    case 'calendar_month': {
        $start = input('start'); $end = input('end');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end)) json_fail('Invalid dates.');
        $visits = q("SELECT v.visit_id, v.concern_id, v.scheduled_at, v.place, v.status, c.concern_code, c.title, c.is_anonymous
                     FROM visits v JOIN concerns c ON c.concern_id = v.concern_id
                     WHERE EXISTS (SELECT 1 FROM assignments a WHERE a.concern_id = c.concern_id AND a.staff_id = ? AND a.assignment_status IN ('active','completed'))
                       AND v.status <> 'cancelled' AND v.scheduled_at >= ? AND v.scheduled_at < ? + INTERVAL 1 DAY
                     ORDER BY v.scheduled_at", [$uid, $start, $end])->fetchAll();
        $due = q("SELECT c.concern_id, c.concern_code, c.title, c.severity, c.status, c.due_at, c.is_anonymous, (c.due_at < NOW()) AS overdue
                  FROM concerns c
                  WHERE EXISTS (SELECT 1 FROM assignments a WHERE a.concern_id = c.concern_id AND a.staff_id = ? AND a.assignment_status = 'active')
                    AND c.status NOT IN ('Resolved','Closed','Cancelled') AND c.due_at >= ? AND c.due_at < ? + INTERVAL 1 DAY
                  ORDER BY c.due_at", [$uid, $start, $end])->fetchAll();
        json_out(['ok' => true, 'visits' => $visits, 'due' => $due]);
    }

    case 'visits': {
        // my face-to-face meetings: everything open in the next 2 weeks, plus ones that already passed and still need a result
        $rows = q("SELECT v.visit_id, v.concern_id, v.scheduled_at, v.place, v.status, (v.scheduled_at < NOW()) AS needs_result, c.concern_code, c.title, c.is_anonymous
                   FROM visits v JOIN concerns c ON c.concern_id = v.concern_id
                   JOIN assignments a ON a.concern_id = c.concern_id AND a.staff_id = ? AND a.assignment_status = 'active'
                   WHERE v.status IN " . CV_VISIT_OPEN . " AND v.scheduled_at < (NOW() + INTERVAL 14 DAY)
                   ORDER BY v.scheduled_at LIMIT 30", [$uid])->fetchAll();
        json_out(['ok' => true, 'rows' => $rows]);
    }

    case 'visit_schedule': case 'visit_result': case 'visit_cancel':
        visit_staff_action($u, $action);

    case 'start': {
        $id = input_int('id');
        $a = my_assignment($id, $uid);
        $c = q('SELECT * FROM concerns WHERE concern_id = ?', [$id])->fetch();
        if ($a['assignment_status'] !== 'active') json_fail('This concern is no longer active for you.');
        if (!in_array($c['status'], ['Assigned', 'Reopened', 'Under Review', 'Submitted', 'Pending'], true)) json_fail('Work has already started on this concern.');
        q("UPDATE concerns SET status = 'In Progress' WHERE concern_id = ?", [$id]);
        q('UPDATE assignments SET seen_at = COALESCE(seen_at, NOW()) WHERE assignment_id = ?', [$a['assignment_id']]);
        add_update($id, $uid, 'In Progress', 'Work Started', display_name($u) . ' started working on this concern.');
        notify((int)$c['student_id'], 'Concern update', "{$c['concern_code']} is now In Progress.", $id);
        audit('concern_updated', "{$c['concern_code']} set to In Progress");
        json_out(['ok' => true, 'message' => 'Marked as In Progress.']);
    }

    case 'update': {
        $id = input_int('id');
        $a = my_assignment($id, $uid);
        $c = q('SELECT * FROM concerns WHERE concern_id = ?', [$id])->fetch();
        if (concern_locked($c)) json_fail(CV_LOCKED_MSG, 409);
        if ($a['assignment_status'] !== 'active') json_fail('This concern is no longer active for you.');
        $new = input('status'); $note = input('note');
        if (!in_array($new, ['In Progress', 'Waiting for Resources', 'Additional Info Needed', 'Resolved'], true)) json_fail('Choose a valid status.');
        if ($new === 'Additional Info Needed' && mb_strlen($note) < 10) json_fail('Tell the student exactly what information you need (at least 10 characters).');
        if (in_array($new, ['Resolved', 'Waiting for Resources'], true) && mb_strlen($note) < 5) json_fail('Please add a short note about what was done or what is needed.');
        if (mb_strlen($note) > 1000) json_fail('Note is too long.');

        $photo = null;
        $files = files_of('proof');
        if ($files) {
            try { $photo = save_image($files[0], 'concerns'); } catch (RuntimeException $ex) { json_fail($ex->getMessage()); }
        }

        $pdo = db(); $pdo->beginTransaction();
        try {
            if ($new === 'Resolved') {
                q("UPDATE concerns SET status = 'Resolved', resolved_at = NOW() WHERE concern_id = ?", [$id]);
                q("UPDATE assignments SET assignment_status = 'completed', completed_at = NOW() WHERE assignment_id = ?", [$a['assignment_id']]);
                cancel_open_visits($id);
            } else {
                q('UPDATE concerns SET status = ? WHERE concern_id = ?', [$new, $id]);
            }
            q('UPDATE assignments SET seen_at = COALESCE(seen_at, NOW()) WHERE assignment_id = ?', [$a['assignment_id']]);
            if ($photo) q('INSERT INTO photos (concern_id, uploaded_by, photo_type, file_path) VALUES (?,?,?,?)', [$id, $uid, 'after', $photo]);
            add_update($id, $uid, $new, $new === 'Resolved' ? 'Resolved' : ($new === 'Additional Info Needed' ? 'Information Requested' : 'Status Updated'), $note ?: null);
            $pdo->commit();
        } catch (Throwable $ex) {
            $pdo->rollBack(); if ($photo) @unlink(CV_ROOT . '/uploads/' . $photo);
            error_log('staff update: ' . $ex->getMessage());
            json_fail('Could not save the update.', 500);
        }

        if ($new === 'Additional Info Needed') notify((int)$c['student_id'], 'More information needed', "{$c['concern_code']} — the office asked: " . mb_substr($note, 0, 300) . ' Open the concern to reply.', $id, true);
        else notify((int)$c['student_id'], $new === 'Resolved' ? 'Concern resolved' : 'Concern update', "{$c['concern_code']} is now {$new}." . ($new === 'Resolved' ? ' Please confirm and rate the result.' : ''), $id);
        if ($new === 'Resolved') notify_role('admin', 'Concern resolved', "{$c['concern_code']} was marked Resolved by " . display_name($u) . '.', $id);
        audit($new === 'Resolved' ? 'concern_resolved' : ($new === 'Additional Info Needed' ? 'info_requested' : 'concern_updated'), "{$c['concern_code']} set to {$new}");
        json_out(['ok' => true, 'message' => $new === 'Resolved' ? 'Great — marked as resolved.' : ($new === 'Additional Info Needed' ? 'The student was asked for more information.' : 'Update saved.')]);
    }

    case 'reopen': {
        $id = input_int('id');
        my_assignment($id, $uid);                       // only the staff member who handled it
        $c = q('SELECT * FROM concerns WHERE concern_id = ?', [$id])->fetch();
        reopen_concern($c, $u, input('reason'));
    }

    default:
        json_fail('Unknown action.', 404);
}
