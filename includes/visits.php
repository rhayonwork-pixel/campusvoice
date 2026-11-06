<?php
/**
 * Face-to-face visits. Most concerns end in a private meeting at the SASO office, so a concern can carry
 * a visit: staff set a date, the student confirms (or asks for another time), staff mark "attended".
 *
 * PRIVACY: nothing here ever reads the student's name. For an anonymous concern the staff member can still
 * schedule and mark attendance; the student shows up in person only when they choose to.
 */

const CV_VISIT_OPEN = "('scheduled','confirmed','reschedule')";

function visit_when(string $dt): string
{
    return date('D, M j · g:i A', strtotime($dt));
}

/** All visits of a concern, newest first. */
function visit_rows(int $concernId): array
{
    return q("SELECT v.visit_id, v.concern_id, v.scheduled_at, v.place, v.note, v.status, v.student_note, v.outcome_note, v.responded_at,
                     CONCAT(u.first_name, ' ', u.last_name) AS scheduled_by_name
              FROM visits v LEFT JOIN users u ON u.user_id = v.scheduled_by
              WHERE v.concern_id = ? ORDER BY v.scheduled_at DESC, v.visit_id DESC", [$concernId])->fetchAll();
}

/** The visit that is still waiting to happen (at most one). */
function visit_active(array $rows): ?array
{
    foreach ($rows as $r) if (in_array($r['status'], ['scheduled', 'confirmed', 'reschedule'], true)) return $r;
    return null;
}

/** Who should hear about a student's reply: the assigned staff member, else the admins. */
function visit_notify_handler(array $concern, string $title, string $message): void
{
    $staffId = (int)q("SELECT staff_id FROM assignments WHERE concern_id = ? AND assignment_status = 'active' LIMIT 1", [$concern['concern_id']])->fetchColumn();
    if ($staffId) notify($staffId, $title, $message, (int)$concern['concern_id']);
    notify_role('admin', $title, $message, (int)$concern['concern_id']);
}

/** Staff + admin actions: visit_schedule | visit_result | visit_cancel. Sends the JSON reply itself. */
function visit_staff_action(array $u, string $action): void
{
    $id = input_int('id');
    $c = q('SELECT * FROM concerns WHERE concern_id = ?', [$id])->fetch();
    if (!$c) json_fail('Concern not found.', 404);
    if ($u['role'] === 'staff' && !q("SELECT 1 FROM assignments WHERE concern_id = ? AND staff_id = ? AND assignment_status = 'active'", [$id, $u['user_id']])->fetchColumn()) {
        json_fail('This concern is not assigned to you.', 403);
    }
    if (in_array($c['status'], ['Closed', 'Cancelled'], true)) json_fail('This concern is closed.');
    $uid = (int)$u['user_id'];
    $code = $c['concern_code'];

    if ($action === 'visit_schedule') {
        $date = input('date'); $time = input('time');
        $place = mb_substr(input('place'), 0, 120) ?: 'SASO Office';
        $note = mb_substr(input('note'), 0, 255) ?: null;
        $dt = preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) && preg_match('/^\d{2}:\d{2}$/', $time) ? DateTime::createFromFormat('Y-m-d H:i', "$date $time") : false;
        if (!$dt) json_fail('Choose a date and a time.');
        if ($dt < new DateTime('-5 minutes')) json_fail('Choose a date and time that has not passed yet.');
        if ($dt > new DateTime('+180 days')) json_fail('That date is too far ahead.');
        $at = $dt->format('Y-m-d H:i:s');

        $pdo = db(); $pdo->beginTransaction();
        try {
            q("UPDATE visits SET status = 'cancelled' WHERE concern_id = ? AND status IN " . CV_VISIT_OPEN, [$id]);   // only one open visit at a time
            q('INSERT INTO visits (concern_id, scheduled_by, scheduled_at, place, note) VALUES (?,?,?,?,?)', [$id, $uid, $at, $place, $note]);
            add_update($id, $uid, null, 'Visit Scheduled', 'Face-to-face meeting set for ' . visit_when($at) . ' at ' . $place . ($note ? ' — ' . $note : '') . '.');
            $pdo->commit();
        } catch (Throwable $ex) { $pdo->rollBack(); throw $ex; }
        notify((int)$c['student_id'], 'Face-to-face visit', "{$code} — please come to {$place} on " . visit_when($at) . '. Open the concern to confirm or ask for another time.', $id, true);
        audit('visit_scheduled', "{$code} meeting set for " . visit_when($at));
        json_out(['ok' => true, 'message' => 'Visit scheduled. The student was notified.']);
    }

    $vid = input_int('visit_id');
    $v = q('SELECT * FROM visits WHERE visit_id = ? AND concern_id = ?', [$vid, $id])->fetch();
    if (!$v || !in_array($v['status'], ['scheduled', 'confirmed', 'reschedule'], true)) json_fail('That visit is no longer open.', 409);

    if ($action === 'visit_cancel') {
        q("UPDATE visits SET status = 'cancelled', responded_at = NOW() WHERE visit_id = ?", [$vid]);
        add_update($id, $uid, null, 'Visit Cancelled', 'The face-to-face meeting on ' . visit_when($v['scheduled_at']) . ' was cancelled. A new time will be set.');
        notify((int)$c['student_id'], 'Visit cancelled', "{$code} — the meeting on " . visit_when($v['scheduled_at']) . ' was cancelled. A new time will be set.', $id);
        audit('visit_cancelled', "{$code} meeting cancelled");
        json_out(['ok' => true, 'message' => 'Visit cancelled.']);
    }

    if ($action === 'visit_result') {
        $result = input('result'); $note = mb_substr(input('note'), 0, 255) ?: null;
        if (!in_array($result, ['attended', 'no_show'], true)) json_fail('Choose attended or did not come.');
        q('UPDATE visits SET status = ?, outcome_note = ?, responded_at = NOW() WHERE visit_id = ?', [$result, $note, $vid]);
        if ($result === 'attended') {
            add_update($id, $uid, null, 'Visit Attended', 'The student attended the face-to-face meeting.' . ($note ? ' ' . $note : ''));
            if (in_array($c['status'], ['Submitted', 'Pending', 'Under Review', 'Assigned'], true)) {          // meeting held = work has started
                q("UPDATE concerns SET status = 'In Progress' WHERE concern_id = ?", [$id]);
                add_update($id, $uid, 'In Progress', 'Status Updated', 'Work started after the face-to-face meeting.');
            }
            notify((int)$c['student_id'], 'Thank you for coming', "{$code} — your meeting on " . visit_when($v['scheduled_at']) . ' was recorded. We will keep you updated here.', $id);
            audit('visit_attended', "{$code} student attended");
        } else {
            add_update($id, $uid, null, 'Visit Missed', 'The student did not come to the meeting on ' . visit_when($v['scheduled_at']) . '.' . ($note ? ' ' . $note : ''));
            notify((int)$c['student_id'], 'We missed you', "{$code} — we did not see you on " . visit_when($v['scheduled_at']) . '. A new time will be set, or reply in the concern if you need help.', $id, true);
            audit('visit_missed', "{$code} student did not come");
        }
        json_out(['ok' => true, 'message' => $result === 'attended' ? 'Recorded: the student attended.' : 'Recorded: the student did not come.']);
    }
    json_fail('Unknown action.', 404);
}

/** Student actions: visit_confirm | visit_reschedule (the student owns the concern). */
function visit_student_action(array $u, array $c, string $action): void
{
    $id = (int)$c['concern_id'];
    $v = q('SELECT * FROM visits WHERE visit_id = ? AND concern_id = ?', [input_int('visit_id'), $id])->fetch();
    if (!$v || !in_array($v['status'], ['scheduled', 'confirmed'], true)) json_fail('That visit can no longer be changed.', 409);
    $who = $c['is_anonymous'] ? 'An anonymous student' : display_name($u);
    $uid = (int)$u['user_id'];

    if ($action === 'visit_confirm') {
        if ($v['status'] === 'confirmed') json_out(['ok' => true, 'message' => 'You already confirmed this visit.']);
        q("UPDATE visits SET status = 'confirmed', responded_at = NOW() WHERE visit_id = ?", [$v['visit_id']]);
        add_update($id, $uid, null, 'Visit Confirmed', 'The student will come on ' . visit_when($v['scheduled_at']) . '.');
        visit_notify_handler($c, 'Visit confirmed', "{$c['concern_code']} — {$who} confirmed the meeting on " . visit_when($v['scheduled_at']) . '.');
        json_out(['ok' => true, 'message' => 'Thanks! The office knows you are coming.']);
    }
    $why = input('reason');
    if (mb_strlen($why) < 5) json_fail('Tell us briefly why you need another time.');
    q("UPDATE visits SET status = 'reschedule', student_note = ?, responded_at = NOW() WHERE visit_id = ?", [mb_substr($why, 0, 255), $v['visit_id']]);
    add_update($id, $uid, null, 'Another Time Requested', mb_substr($why, 0, 255));
    visit_notify_handler($c, 'Another time requested', "{$c['concern_code']} — {$who} cannot make " . visit_when($v['scheduled_at']) . ': ' . mb_substr($why, 0, 160));
    json_out(['ok' => true, 'message' => 'Sent. The office will set a new time.']);
}
