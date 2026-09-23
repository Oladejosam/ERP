<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Model.php';
require_once __DIR__ . '/../app/Models/InventoryModel.php';

$model = new InventoryModel();
$requiredMethods = [
    'getItems',
    'searchItems',
    'getItemIssueHistory',
    'issueItem',
    'allocateStock',
];

foreach ($requiredMethods as $method) {
    if (!method_exists($model, $method)) {
        fwrite(STDERR, "Missing method: {$method}\n");
        exit(1);
    }
}

echo "inventory stock flow API present\n";
