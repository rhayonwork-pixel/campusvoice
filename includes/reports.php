<?php
/** Report period + aggregates, shared by the Reports screen, the CSV export and the printable report. */
require_once __DIR__ . '/concerns.php';

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

/** Everything the printable report needs for one period. Anonymous reporters are never exposed. */
function report_data(string $from, string $to, bool $withList = false): array
{
    $r = [];
    $sum = q("SELECT COUNT(*) total, SUM(status IN ('Resolved','Closed')) resolved, SUM(status = 'Closed') closed, SUM(is_anonymous = 1) anonymous,
                     SUM(due_at < NOW() AND status NOT IN " . CV_OPEN_STATUSES . ") overdue,
                     ROUND(AVG(CASE WHEN resolved_at IS NOT NULL THEN TIMESTAMPDIFF(HOUR, created_at, resolved_at) END), 1) avg_hours
              FROM concerns WHERE created_at BETWEEN ? AND ?", [$from, $to])->fetch();
    foreach (['total', 'resolved', 'closed', 'anonymous', 'overdue'] as $k) $sum[$k] = (int)$sum[$k];
    $r['summary'] = $sum;
    $r['by_category'] = q('SELECT cat.category_name label, COUNT(*) n FROM concerns c JOIN categories cat ON cat.category_id = c.category_id WHERE c.created_at BETWEEN ? AND ? GROUP BY cat.category_id, cat.category_name ORDER BY n DESC, label', [$from, $to])->fetchAll();
    $r['by_severity'] = q('SELECT severity label, COUNT(*) n FROM concerns WHERE created_at BETWEEN ? AND ? GROUP BY severity ORDER BY FIELD(severity,"Urgent","Major","Minor")', [$from, $to])->fetchAll();
    $r['by_status']   = q('SELECT status label, COUNT(*) n FROM concerns WHERE created_at BETWEEN ? AND ? GROUP BY status ORDER BY n DESC, label', [$from, $to])->fetchAll();
    $r['by_location'] = q("SELECT CONCAT(l.building, ' · ', l.area) label, COUNT(*) n FROM concerns c JOIN locations l ON l.location_id = c.location_id WHERE c.created_at BETWEEN ? AND ? GROUP BY l.location_id, l.building, l.area ORDER BY n DESC, label LIMIT 8", [$from, $to])->fetchAll();
    $r['rating'] = q('SELECT ROUND(AVG(f.rating), 2) avg_rating, COUNT(*) n FROM feedback f JOIN concerns c ON c.concern_id = f.concern_id WHERE c.created_at BETWEEN ? AND ?', [$from, $to])->fetch();
    $r['staff'] = q("SELECT CONCAT(u.first_name, ' ', u.last_name) label, COUNT(*) n, ROUND(AVG(TIMESTAMPDIFF(HOUR, a.assigned_at, a.completed_at)), 1) avg_hours
                     FROM assignments a JOIN users u ON u.user_id = a.staff_id
                     WHERE a.assignment_status = 'completed' AND a.completed_at BETWEEN ? AND ?
                     GROUP BY u.user_id, u.first_name, u.last_name ORDER BY n DESC, label LIMIT 12", [$from, $to])->fetchAll();
    $r['list'] = [];
    if ($withList) {
        $r['list'] = q("SELECT c.concern_code, c.title, cat.category_name, CONCAT(l.building, ' · ', l.area) location, c.severity, c.status, c.created_at, c.resolved_at
                        FROM concerns c JOIN categories cat ON cat.category_id = c.category_id JOIN locations l ON l.location_id = c.location_id
                        WHERE c.created_at BETWEEN ? AND ? ORDER BY c.created_at ASC, c.concern_id ASC LIMIT 1000", [$from, $to])->fetchAll();
    }
    return $r;
}
