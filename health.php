<?php
/**
 * TEMPORARY diagnostic page — open /health.php to see exactly where the server fails, then DELETE this file.
 * It is self-contained on purpose (does not load the rest of the app) and never prints the database password.
 */
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store');

echo "CampusVoice health check\n========================\n";
echo 'PHP version     : ' . PHP_VERSION . "\n";
foreach (['pdo_mysql', 'mbstring', 'fileinfo', 'session'] as $ext) {
    echo str_pad($ext, 16) . ': ' . (extension_loaded($ext) ? 'yes' : 'MISSING') . "\n";
}

$cfgFile = __DIR__ . '/config/config.php';
echo 'config/config.php: ' . (is_file($cfgFile) ? 'found' : 'MISSING') . "\n";
if (!is_file($cfgFile)) exit;

try {
    $c = require $cfgFile;
} catch (Throwable $e) {
    echo 'config.php has an error: ' . $e->getMessage() . "\n";
    exit;
}
$db = $c['db'] ?? [];
echo 'db host          : ' . ($db['host'] ?? '?') . "\n";
echo 'db name          : ' . ($db['name'] ?? '?') . "\n";
echo 'db user          : ' . ($db['user'] ?? '?') . "\n";

try {
    $dsn = 'mysql:host=' . $db['host'] . ';dbname=' . $db['name'] . ';charset=' . ($db['charset'] ?? 'utf8mb4');
    $pdo = new PDO($dsn, $db['user'], $db['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5]);
    echo "DB connection    : OK\n";
    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    echo 'tables found     : ' . count($tables) . " (expected 16)\n";
    if (in_array('users', $tables, true)) {
        echo 'users in table   : ' . $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() . "\n";
    } else {
        echo "users table      : MISSING -> import database/schema.sql then seed.sql\n";
    }
} catch (Throwable $e) {
    echo 'DB connection    : FAILED -> ' . $e->getMessage() . "\n";
}

$up = __DIR__ . '/uploads';
echo 'uploads/ folder  : ' . (is_dir($up) ? (is_writable($up) ? 'exists, writable' : 'exists, NOT writable') : 'MISSING') . "\n";
echo "\nIf everything above is OK but pages still fail, the problem is the hosting (see the notes in chat).\n";
echo "Delete health.php when you are done.\n";
