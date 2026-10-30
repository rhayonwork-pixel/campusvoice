<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/concerns.php';
require __DIR__ . '/../includes/upload.php';

$action = input('action');
$u = api_guard(['student'], !in_array($action, ['list', 'options', 'detail', 'summary'], true));
$uid = (int)$u['user_id'];

/** Fetch one of THIS student's concerns or stop. */
function own_concern(int $id, int $uid): array
{
    $c = q('SELECT * FROM concerns WHERE concern_id = ? AND student_id = ?', [$id, $uid])->fetch();
    if (!$c) json_fail('Concern not found.', 404);
    return $c;
}

switch ($action) {

    case 'options': {
        json_out(['ok' => true,
            'categories' => q('SELECT category_id, category_name FROM categories WHERE status = "active" ORDER BY category_name')->fetchAll(),
            'locations'  => q('SELECT location_id, CONCAT(building, " · ", floor, " · ", area) AS label FROM locations WHERE status = "active" ORDER BY building, floor, area')->fetchAll(),
            'default_anonymous' => (int)$u['pref_default_anonymous'],
            'hours' => ['Minor' => severity_hours('Minor'), 'Major' => severity_hours('Major'), 'Urgent' => severity_hours('Urgent')],
        ]);
    }

    case 'summary': {
        $counts = q("SELECT COUNT(*) total,
                            SUM(status IN ('Submitted','Under Review','Assigned','Reopened')) pending,
                            SUM(status IN ('In Progress','Waiting for Resources')) in_progress,
                            SUM(status IN ('Resolved','Closed')) resolved
                     FROM concerns WHERE student_id = ?", [$uid])->fetch();
        $recent = q('SELECT cu.created_at, cu.update_type, cu.response, c.concern_id, c.concern_code, c.title
                     FROM concern_updates cu JOIN concerns c ON c.concern_id = cu.concern_id
                     WHERE c.student_id = ? ORDER BY cu.created_at DESC, cu.update_id DESC LIMIT 6', [$uid])->fetchAll();
        $awaiting = q("SELECT c.concern_id, c.concern_code, c.title FROM concerns c LEFT JOIN feedback f ON f.concern_id = c.concern_id
                       WHERE c.student_id = ? AND c.status IN ('Resolved','Closed') AND f.feedback_id IS NULL ORDER BY c.resolved_at DESC LIMIT 5", [$uid])->fetchAll();
        json_out(['ok' => true, 'counts' => array_map('intval', $counts), 'recent' => $recent, 'awaiting_rating' => $awaiting]);
    }

    case 'list': {
        [$per, $page, $offset] = paginate_params();
        $where = ['c.student_id = :uid']; $args = [':uid' => $uid];
        if (($s = input('status')) !== '') { $where[] = 'c.status = :st'; $args[':st'] = $s; }
        if (($v = input('severity')) !== '') { $where[] = 'c.severity = :sv'; $args[':sv'] = $v; }
        if (($t = input('q')) !== '') {
            $where[] = '(c.title LIKE :q OR c.concern_code LIKE :q OR cat.category_name LIKE :q)';
            $args[':q'] = '%' . addcslashes($t, '%_\\') . '%';
        }
        $w = implode(' AND ', $where);
        $from = concern_joins();
        $cst = db()->prepare("SELECT COUNT(*) $from WHERE $w"); $cst->execute($args);
        $total = (int)$cst->fetchColumn();
        $st = db()->prepare('SELECT ' . concern_columns() . " $from WHERE $w ORDER BY c.created_at DESC, c.concern_id DESC LIMIT $per OFFSET $offset");
        $st->execute($args);
        $rows = $st->fetchAll();
        foreach ($rows as &$r) unset($r['description']);
        json_out(['ok' => true, 'rows' => $rows, 'total' => $total, 'page' => $page, 'per' => $per]);
    }

    case 'create': {
        $title = input('title'); $desc = input('description');
        $cat = input_int('category_id'); $loc = input_int('location_id'); $sev = input('severity');
        $anon = input('is_anonymous') === '1' ? 1 : 0;
        if (mb_strlen($title) < 5 || mb_strlen($title) > 150) json_fail('Title must be 5–150 characters.');
        if (mb_strlen($desc) < 15 || mb_strlen($desc) > 3000) json_fail('Please describe the concern in at least 15 characters.');
        if (!in_array($sev, ['Minor', 'Major', 'Urgent'], true)) json_fail('Choose a severity level.');
        if (!q('SELECT 1 FROM categories WHERE category_id = ? AND status = "active"', [$cat])->fetchColumn()) json_fail('Choose a category.');
        if (!q('SELECT 1 FROM locations WHERE location_id = ? AND status = "active"', [$loc])->fetchColumn()) json_fail('Choose a location.');

        // rate limit: stop flooding (10 concerns / hour)
        if ((int)q('SELECT COUNT(*) FROM concerns WHERE student_id = ? AND created_at > (NOW() - INTERVAL 1 HOUR)', [$uid])->fetchColumn() >= 10) {
            json_fail('You have submitted many concerns recently. Please try again later.', 429);
        }

        $files = files_of('photos');
        if (count($files) > cfg('uploads.max_per_concern')) json_fail('You can attach up to ' . cfg('uploads.max_per_concern') . ' photos.');

        $saved = [];
        try { foreach ($files as $f) $saved[] = save_image($f, 'concerns'); }
        catch (RuntimeException $ex) { foreach ($saved as $p) @unlink(CV_ROOT . '/uploads/' . $p); json_fail($ex->getMessage()); }

        $pdo = db();
        $pdo->beginTransaction();
        try {
            q('INSERT INTO concerns (concern_code, student_id, category_id, location_id, severity, title, description, is_anonymous, due_at)
               VALUES (?,?,?,?,?,?,?,?, NOW() + INTERVAL ? HOUR)',
                ['TMP-' . bin2hex(random_bytes(6)), $uid, $cat, $loc, $sev, $title, $desc, $anon, severity_hours($sev)]);
            $id = (int)$pdo->lastInsertId();
            $code = next_concern_code($id);
            q('UPDATE concerns SET concern_code = ? WHERE concern_id = ?', [$code, $id]);
            foreach ($saved as $p) q('INSERT INTO photos (concern_id, uploaded_by, photo_type, file_path) VALUES (?,?,?,?)', [$id, $uid, 'supporting', $p]);
            add_update($id, $uid, 'Submitted', 'Concern Submitted', 'Your concern has been received and is awaiting review.');
            $pdo->commit();
        } catch (Throwable $ex) {
            $pdo->rollBack();
            foreach ($saved as $p) @unlink(CV_ROOT . '/uploads/' . $p);
            error_log('create concern: ' . $ex->getMessage());
            json_fail('Could not save your concern. Please try again.', 500);
        }

        // never name an anonymous reporter in notifications or the audit trail
        $who = $anon ? 'An anonymous student' : display_name($u);
        notify_role('admin', $sev === 'Urgent' ? 'Urgent concern' : 'New concern', "{$code} — {$title} ({$sev}) from {$who}.", $id, $sev === 'Urgent');
        if ($anon) audit_anonymous('concern_created', "Created {$code} (anonymous)");
        else audit('concern_created', "Created {$code}");
        json_out(['ok' => true, 'message' => "Concern {$code} submitted.", 'concern_id' => $id, 'code' => $code]);
    }

    case 'detail': {
        $id = input_int('id');
        own_concern($id, $uid);
        $c = q('SELECT ' . concern_columns() . ', c.student_id ' . concern_joins() . ' WHERE c.concern_id = ?', [$id])->fetch();
        $assign = q("SELECT CONCAT(u.first_name,' ',u.last_name) AS staff_name, u.position FROM assignments a JOIN users u ON u.user_id = a.staff_id
                     WHERE a.concern_id = ? AND a.assignment_status IN ('active','completed')
                     ORDER BY (a.assignment_status = 'active') DESC, a.assigned_at DESC LIMIT 1", [$id])->fetch();
        $fb = q('SELECT rating, comment, created_at FROM feedback WHERE concern_id = ?', [$id])->fetch();
        unset($c['student_id']);
        json_out(['ok' => true, 'concern' => $c, 'assignee' => $assign ?: null, 'feedback' => $fb ?: null,
                  'timeline' => concern_timeline($id), 'photos' => concern_photos($id)]);
    }

    case 'feedback': {
        $c = own_concern(input_int('id'), $uid);
        if (!in_array($c['status'], ['Resolved', 'Closed'], true)) json_fail('You can rate a concern once it is resolved.');
        $rating = input_int('rating');
        if ($rating < 1 || $rating > 5) json_fail('Choose a rating from 1 to 5 stars.');
        if (q('SELECT 1 FROM feedback WHERE concern_id = ?', [$c['concern_id']])->fetchColumn()) json_fail('You already rated this concern.');
        q('INSERT INTO feedback (concern_id, student_id, rating, comment) VALUES (?,?,?,?)', [$c['concern_id'], $uid, $rating, mb_substr(input('comment'), 0, 500) ?: null]);
        notify_role('admin', 'New feedback', "{$c['concern_code']} was rated {$rating}/5.", (int)$c['concern_id']);
        $c['is_anonymous'] ? audit_anonymous('feedback_given', "Rated {$c['concern_code']} {$rating}/5") : audit('feedback_given', "Rated {$c['concern_code']} {$rating}/5");
        json_out(['ok' => true, 'message' => 'Thanks for your feedback!']);
    }

    case 'confirm': case 'reopen': case 'cancel': {
        $c = own_concern(input_int('id'), $uid);
        $id = (int)$c['concern_id'];
        if ($action === 'confirm') {
            if ($c['status'] !== 'Resolved') json_fail('Only a resolved concern can be confirmed.');
            q("UPDATE concerns SET status = 'Closed', closed_at = NOW() WHERE concern_id = ?", [$id]);
            add_update($id, $uid, 'Closed', 'Closed', 'The reporter confirmed the concern is resolved.');
            $msg = 'Thanks — the concern is now closed.';
        } elseif ($action === 'reopen') {
            if (!in_array($c['status'], ['Resolved', 'Closed'], true)) json_fail('Only a resolved concern can be reopened.');
            $reason = input('reason');
            if (mb_strlen($reason) < 5) json_fail('Please tell us what is still wrong.');
            q("UPDATE concerns SET status = 'Reopened', resolved_at = NULL, closed_at = NULL, due_at = NOW() + INTERVAL ? HOUR WHERE concern_id = ?", [severity_hours($c['severity']), $id]);
            q("UPDATE assignments SET assignment_status = 'active', completed_at = NULL WHERE concern_id = ? AND assignment_status = 'completed' ORDER BY assigned_at DESC LIMIT 1", [$id]);
            add_update($id, $uid, 'Reopened', 'Reopened', mb_substr($reason, 0, 500));
            notify_role('admin', 'Concern reopened', "{$c['concern_code']} was reopened by the reporter.", $id, true);
            $msg = 'Concern reopened.';
        } else {
            if ($c['status'] !== 'Submitted') json_fail('A concern can only be cancelled before it is reviewed.');
            q("UPDATE concerns SET status = 'Cancelled' WHERE concern_id = ?", [$id]);
            add_update($id, $uid, 'Cancelled', 'Cancelled', 'Cancelled by the reporter.');
            $msg = 'Concern cancelled.';
        }
        $c['is_anonymous'] ? audit_anonymous('concern_updated', "{$c['concern_code']}: {$action}") : audit('concern_updated', "{$c['concern_code']}: {$action}");
        json_out(['ok' => true, 'message' => $msg]);
    }

    default:
        json_fail('Unknown action.', 404);
}
