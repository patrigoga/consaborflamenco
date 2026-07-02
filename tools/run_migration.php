<?php
require_once __DIR__ . '/../app/auth.php';

if ($argc < 2) {
    echo "Usage: php run_migration.php path/to/migration.sql\n";
    exit(1);
}

$path = $argv[1];
if (!is_file($path)) {
    echo "Migration file not found: $path\n";
    exit(1);
}

$sql = file_get_contents($path);
if ($sql === false) {
    echo "Failed to read migration file.\n";
    exit(1);
}

$pdo = db();
if (!$pdo) {
    echo "Database connection not available. Check DB env.\n";
    exit(1);
}

try {
    $pdo->exec($sql);
    echo "Migration executed: $path\n";
} catch (Throwable $e) {
    echo "Migration error: " . $e->getMessage() . "\n";
    exit(1);
}

exit(0);
