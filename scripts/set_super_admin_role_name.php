<?php
require_once __DIR__ . '/../config/config.php';
require_once APP_ROOT . '/core/Database.php';

try {
    $db = Database::connect();
    $roleId = (int)($argv[1] ?? 58);
    $newName = 'Super Admin';
    $stmt = $db->prepare('UPDATE roles SET name = ? WHERE id = ?');
    $stmt->execute([$newName, $roleId]);
    echo "Updated role id $roleId to name '$newName'\n";

    $stmt = $db->prepare('SELECT id, name, description FROM roles WHERE id = ? LIMIT 1');
    $stmt->execute([$roleId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        echo "Role row:\n";
        foreach ($row as $k => $v) {
            echo "  $k => $v\n";
        }
    }

} catch (Throwable $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
