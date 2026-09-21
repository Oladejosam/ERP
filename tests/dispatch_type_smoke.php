<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Model.php';
require_once __DIR__ . '/../app/Models/RmcOperationsModel.php';

$_SESSION['selected_company_id'] = 1;
$model = new RmcOperationsModel();

$material = [
    'dispatch_number' => 'DM-1001',
    'dispatch_type' => 'materials',
    'inventory_item_id' => 1,
    'quantity' => '12',
    'unit' => 'bags',
    'delivery_location' => 'Store to site',
    'dispatch_date' => '2026-09-21',
    'status' => 'planned',
    'notes' => 'Material dispatch test',
];

$staff = [
    'dispatch_number' => 'DS-1001',
    'dispatch_type' => 'staff',
    'staff_id' => 1,
    'quantity' => '3',
    'unit' => 'persons',
    'issued_to' => 'Project team',
    'delivery_location' => 'Site office',
    'dispatch_date' => '2026-09-21',
    'status' => 'planned',
    'notes' => 'Staff dispatch test',
];

try {
    $model->create('dispatch', $material, 1);
    $model->create('dispatch', $staff, 1);
    echo "dispatch type coverage ok\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'dispatch type coverage failed: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
