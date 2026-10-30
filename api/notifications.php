<?php
require __DIR__ . '/../includes/bootstrap.php';

$action = input('action');
$read = ['count', 'list'];
$u = api_guard(['student', 'staff', 'admin'], !in_array($action, $read, true));
$uid = (int)$u['user_id'];

switch ($action) {
    case 'count':
        json_out(['ok' => true, 'unread' => (int)q('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0', [$uid])->fetchColumn()]);

    case 'list': {
        $per  = max(1, min(50, input_int('per', 10)));
        $page = max(1, input_int('page', 1));
        $total  = (int)q('SELECT COUNT(*) FROM notifications WHERE user_id = ?', [$uid])->fetchColumn();
        $unread = (int)q('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0', [$uid])->fetchColumn();
        $st = db()->prepare('SELECT notification_id, concern_id, title, message, is_urgent, is_read, created_at FROM notifications
                             WHERE user_id = :u ORDER BY created_at DESC, notification_id DESC LIMIT :l OFFSET :o');
        $st->bindValue(':u', $uid, PDO::PARAM_INT);
        $st->bindValue(':l', $per, PDO::PARAM_INT);
        $st->bindValue(':o', ($page - 1) * $per, PDO::PARAM_INT);
        $st->execute();
        json_out(['ok' => true, 'rows' => $st->fetchAll(), 'total' => $total, 'page' => $page, 'per' => $per, 'unread' => $unread]);
    }

    case 'read':
        q('UPDATE notifications SET is_read = 1 WHERE notification_id = ? AND user_id = ?', [input_int('id'), $uid]);
        json_out(['ok' => true]);

    case 'read_all':
        q('UPDATE notifications SET is_read = 1 WHERE user_id = ?', [$uid]);
        json_out(['ok' => true]);

    default:
        json_fail('Unknown action.', 404);
}
