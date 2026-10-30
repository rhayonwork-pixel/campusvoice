<?php
/** Safe image uploads: server-side MIME sniffing, size cap, random names, stored outside public reach. */

function upload_dir(string $sub): string
{
    $dir = CV_ROOT . '/uploads/' . $sub;
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    return $dir;
}

/**
 * Validate one $_FILES entry and move it. Returns the stored relative path ("concerns/ab12.jpg").
 * Throws RuntimeException with a user-safe message.
 */
function save_image(array $file, string $sub): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException(($file['error'] ?? 0) === UPLOAD_ERR_INI_SIZE || ($file['error'] ?? 0) === UPLOAD_ERR_FORM_SIZE
            ? 'That photo is too large.' : 'The photo could not be uploaded.');
    }
    if ($file['size'] > cfg('uploads.max_bytes')) throw new RuntimeException('Photos must be 3 MB or smaller.');
    if (!is_uploaded_file($file['tmp_name'])) throw new RuntimeException('Invalid upload.');

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $allowed = cfg('uploads.allowed');
    if (!isset($allowed[$mime])) throw new RuntimeException('Only JPG, PNG or WebP photos are allowed.');
    if (@getimagesize($file['tmp_name']) === false) throw new RuntimeException('That file is not a valid image.');

    $name = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
    if (!move_uploaded_file($file['tmp_name'], upload_dir($sub) . '/' . $name)) throw new RuntimeException('Could not save the photo.');
    return $sub . '/' . $name;
}

/** Collect uploaded files from an input named e.g. photos[] into a flat list. */
function files_of(string $field): array
{
    if (empty($_FILES[$field])) return [];
    $f = $_FILES[$field];
    if (!is_array($f['name'])) return $f['error'] === UPLOAD_ERR_NO_FILE ? [] : [$f];
    $out = [];
    foreach ($f['name'] as $i => $n) {
        if ($f['error'][$i] === UPLOAD_ERR_NO_FILE) continue;
        $out[] = ['name' => $n, 'type' => $f['type'][$i], 'tmp_name' => $f['tmp_name'][$i], 'error' => $f['error'][$i], 'size' => $f['size'][$i]];
    }
    return $out;
}
