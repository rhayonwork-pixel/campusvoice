<?php
/**
 * Serves private uploads after a permission check.
 *   file.php?photo=ID   concern photo  (student: own concerns, staff: assigned concerns, admin: all)
 *   file.php?avatar=ID  profile picture (any signed-in user)
 */
require __DIR__ . '/includes/bootstrap.php';

$u = current_user();
if (!$u) { http_response_code(401); exit; }

function send_file(string $rel): void
{
    $path = realpath(CV_ROOT . '/uploads/' . $rel);
    $root = realpath(CV_ROOT . '/uploads');
    if (!$path || !$root || strpos($path, $root) !== 0 || !is_file($path)) { http_response_code(404); exit; }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($path);
    if (strpos((string)$mime, 'image/') !== 0) { http_response_code(404); exit; }
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($path));
    header('Cache-Control: private, max-age=86400');
    header('X-Content-Type-Options: nosniff');
    readfile($path);
    exit;
}

if (isset($_GET['avatar'])) {
    $row = q('SELECT avatar FROM users WHERE user_id = ?', [(int)$_GET['avatar']])->fetchColumn();
    if (!$row) { http_response_code(404); exit; }
    send_file($row);
}

if (isset($_GET['photo'])) {
    $p = q('SELECT p.file_path, c.concern_id, c.student_id FROM photos p JOIN concerns c ON c.concern_id = p.concern_id WHERE p.photo_id = ?', [(int)$_GET['photo']])->fetch();
    if (!$p) { http_response_code(404); exit; }
    // (plain if/else so it also runs on PHP 7.4)
    $ok = false;
    if ($u['role'] === 'admin') $ok = true;
    elseif ($u['role'] === 'student') $ok = (int)$p['student_id'] === (int)$u['user_id'];
    elseif ($u['role'] === 'staff') $ok = (bool)q('SELECT 1 FROM assignments WHERE concern_id = ? AND staff_id = ?', [$p['concern_id'], $u['user_id']])->fetchColumn();
    if (!$ok) { http_response_code(403); exit; }
    send_file($p['file_path']);
}
http_response_code(404);
