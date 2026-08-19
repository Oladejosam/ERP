<?php
/**
 * Simple installer for the ERP database scripts.
 */
declare(strict_types=1);

$host = 'localhost';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    $baseDir = __DIR__ . '/SQL';
    $files = [
        'create_database.sql',
        'create_tables.sql',
        'dummy_data.sql',
        'triggers.sql',
        'procedures.sql',
        'indexes.sql',
        'views.sql',
        'sample_backup.sql',
    ];

    foreach ($files as $file) {
        $sql = file_get_contents($baseDir . '/' . $file);
        if ($sql === false) {
            throw new RuntimeException("Could not read SQL file: {$file}");
        }
        $pdo->exec($sql);
    }

    echo 'ERP database installed successfully.';
} catch (Throwable $e) {
    echo 'Installation failed: ' . $e->getMessage();
}
