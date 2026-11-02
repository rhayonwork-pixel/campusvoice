<?php
/**
 * Concern queries shared by every role.
 *
 * ANONYMITY RULE: when concerns.is_anonymous = 1, the reporter's name, ID, email and contact are
 * replaced in SQL (never fetched) for STAFF views and exports. Course / year / section stay visible so
 * staff have context. Only the admin sees who the anonymous sender is (reporter_columns(true)).
 */

const CV_OPEN_STATUSES = "('Resolved','Closed','Cancelled')";
/** Waiting for someone to pick it up (admin queue when nobody is assigned). 'Submitted' is the pre-SASO name of 'Pending'. */
const CV_QUEUE_STATUSES = "('Submitted','Pending','Under Review','Reopened')";

function concern_columns(): string
{
    return "c.concern_id, c.concern_code, c.title, c.description, c.severity, c.status, c.is_anonymous,
            c.due_at, c.created_at, c.updated_at, c.resolved_at, c.closed_at,
            (c.due_at IS NOT NULL AND c.due_at < NOW() AND c.status NOT IN " . CV_OPEN_STATUSES . ") AS overdue,
            cat.category_id, cat.category_name,
            cat.needs_location, cat.next_steps,
            loc.location_id, CONCAT(loc.building, ' · ', loc.floor, ' · ', loc.area) AS location,
            (SELECT COUNT(*) FROM photos p WHERE p.concern_id = c.concern_id) AS photo_count";
}

/**
 * Reporter columns. Staff (and exports) get anonymous reporters masked at the SQL level.
 * $admin = true: the admin sees the real sender; the row still carries is_anonymous so the UI marks it.
 */
function reporter_columns(bool $admin = false): string
{
    if ($admin) {
        return "CONCAT(s.first_name, ' ', s.last_name) AS reporter_name, s.school_id AS reporter_school_id,
                s.email AS reporter_email, s.contact_number AS reporter_contact,
                s.course AS reporter_course, s.year_level AS reporter_year, s.section AS reporter_section";
    }
    return "IF(c.is_anonymous = 1, 'Anonymous Student', CONCAT(s.first_name, ' ', s.last_name)) AS reporter_name,
            IF(c.is_anonymous = 1, NULL, s.school_id) AS reporter_school_id,
            IF(c.is_anonymous = 1, NULL, s.email) AS reporter_email,
            IF(c.is_anonymous = 1, NULL, s.contact_number) AS reporter_contact,
            s.course AS reporter_course, s.year_level AS reporter_year, s.section AS reporter_section";
}

function concern_joins(): string
{
    return "FROM concerns c
            JOIN categories cat ON cat.category_id = c.category_id
            LEFT JOIN locations loc ON loc.location_id = c.location_id
            JOIN users s ON s.user_id = c.student_id";
}

/** Public-safe label for a student's context line, e.g. "BS Information Technology · Year 3 · 3A". */
function reporter_context(array $r): string
{
    $parts = array_filter([$r['reporter_course'] ?? null, !empty($r['reporter_year']) ? 'Year ' . $r['reporter_year'] : null, $r['reporter_section'] ?? null]);
    return implode(' · ', $parts);
}

function next_concern_code(int $id): string
{
    return sprintf('CV-%s-%04d', date('Y'), $id);
}

function paginate_params(int $defaultPer = 10): array
{
    $per  = max(1, min(100, input_int('per', $defaultPer)));
    $page = max(1, input_int('page', 1));
    return [$per, $page, ($page - 1) * $per];
}

/** Update timeline rows with the reporter's identity masked when the concern is anonymous (unless the admin is looking). */
function concern_timeline(int $concernId, bool $admin = false): array
{
    $mask = $admin ? '0' : 'c.is_anonymous = 1';
    return q("SELECT cu.update_id, cu.status, cu.update_type, cu.response, cu.created_at,
                     IF(cu.user_id IS NULL, 'CampusVoice', IF($mask AND cu.user_id = c.student_id, 'Anonymous Student', CONCAT(u.first_name, ' ', u.last_name))) AS actor,
                     IF(cu.user_id IS NULL, 'system', IF(cu.user_id = c.student_id, 'student', u.role)) AS actor_role
              FROM concern_updates cu
              JOIN concerns c ON c.concern_id = cu.concern_id
              LEFT JOIN users u ON u.user_id = cu.user_id
              WHERE cu.concern_id = ? ORDER BY cu.created_at ASC, cu.update_id ASC", [$concernId])->fetchAll();
}

function concern_photos(int $concernId): array
{
    $rows = q('SELECT photo_id, photo_type, created_at FROM photos WHERE concern_id = ? ORDER BY photo_id', [$concernId])->fetchAll();
    foreach ($rows as &$r) $r['src'] = url('file.php?photo=' . $r['photo_id']);
    return $rows;
}

function add_update(int $concernId, ?int $userId, ?string $status, string $type, ?string $response): void
{
    q('INSERT INTO concern_updates (concern_id, user_id, status, update_type, response) VALUES (?,?,?,?,?)',
        [$concernId, $userId, $status, $type, $response]);
}

/** Audit that never reveals an anonymous reporter: logged as "Anonymous Student" with no user link. */
function audit_anonymous(string $action, string $details): void
{
    q('INSERT INTO audit_logs (user_id, user_label, role, action, details, ip_address) VALUES (NULL, ?, "student", ?, ?, ?)',
        ['Anonymous Student', $action, mb_substr($details, 0, 500), client_ip()]);
}

/** Staff the concern's category suggests (category handlers first, then matching office, then lightest workload). */
function suggested_staff(int $categoryId): array
{
    return q("SELECT u.user_id, CONCAT(u.first_name, ' ', u.last_name) AS name, u.availability, d.department_name,
                     (SELECT COUNT(*) FROM assignments a WHERE a.staff_id = u.user_id AND a.assignment_status = 'active') AS active_load,
                     EXISTS (SELECT 1 FROM category_handlers h WHERE h.category_id = cat.category_id AND h.staff_id = u.user_id) AS is_handler,
                     (u.department_id <=> cat.default_department_id) AS dept_match
              FROM users u
              LEFT JOIN departments d ON d.department_id = u.department_id
              JOIN categories cat ON cat.category_id = ?
              WHERE u.role = 'staff' AND u.account_status = 'active' AND u.availability <> 'on_leave'
              ORDER BY is_handler DESC, dept_match DESC, active_load ASC, u.last_name", [$categoryId])->fetchAll();
}

/**
 * Category routing: when auto-assign is on, hand a new concern to the least-busy handler of its type
 * (available before busy, nobody on leave). No handler = it waits in the admin's Assignment Queue.
 * Call AFTER the concern row is committed. Returns the staff row or null.
 */
function auto_route(int $concernId): ?array
{
    if (setting('auto_assign', '1') !== '1') return null;
    $c = q('SELECT c.*, cat.category_name FROM concerns c JOIN categories cat ON cat.category_id = c.category_id WHERE c.concern_id = ?', [$concernId])->fetch();
    if (!$c) return null;
    $staff = q("SELECT u.*, d.department_name,
                       (SELECT COUNT(*) FROM assignments a WHERE a.staff_id = u.user_id AND a.assignment_status = 'active') AS active_load
                FROM category_handlers h
                JOIN users u ON u.user_id = h.staff_id
                LEFT JOIN departments d ON d.department_id = u.department_id
                WHERE h.category_id = ? AND u.role = 'staff' AND u.account_status = 'active' AND u.availability <> 'on_leave'
                ORDER BY (u.availability = 'available') DESC, active_load ASC, RAND() LIMIT 1", [$c['category_id']])->fetch();
    if (!$staff) return null;

    $pdo = db(); $pdo->beginTransaction();
    try {
        if (q("SELECT 1 FROM assignments WHERE concern_id = ? AND assignment_status = 'active'", [$concernId])->fetchColumn()) { $pdo->rollBack(); return null; }
        q('INSERT INTO assignments (concern_id, staff_id, assigned_by, reason) VALUES (?,?,NULL,?)', [$concernId, $staff['user_id'], 'Routed by concern type']);
        q("UPDATE concerns SET status = 'Assigned' WHERE concern_id = ?", [$concernId]);
        add_update($concernId, null, 'Assigned', 'Assigned', 'Routed automatically to ' . ($staff['department_name'] ?: display_name($staff)) . '.');
        $pdo->commit();
    } catch (Throwable $ex) {
        $pdo->rollBack(); error_log('auto_route: ' . $ex->getMessage());
        return null;
    }
    notify((int)$staff['user_id'], 'New assignment', "{$c['concern_code']} ({$c['severity']}, {$c['category_name']}) was routed to you: {$c['title']}", $concernId, $c['severity'] === 'Urgent');
    q('INSERT INTO audit_logs (user_id, user_label, role, action, details, ip_address) VALUES (NULL, "System", "system", "concern_assigned", ?, ?)',
        ["{$c['concern_code']} routed automatically to " . display_name($staff), client_ip()]);
    return $staff;
}
