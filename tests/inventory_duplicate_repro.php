<?php
session_start();
$_SESSION['selected_company_id'] = 2;
require __DIR__ . '/../config/config.php';
require __DIR__ . '/../core/Database.php';
require __DIR__ . '/../core/Model.php';
require __DIR__ . '/../app/Models/InventoryModel.php';

$model = new InventoryModel();
$itemId = $model->createItem([
    'name' => 'Duplicate Safe Test',
    'category' => 'QA',
    'item_code' => 'CEM-001',
    'unit' => 'pcs',
    'cost_price' => 1,
    'selling_price' => 2,
    'current_stock' => 4,
    'free_stock' => 4,
    'company_id' => 2,
]);
$item = $model->getItemById($itemId);

echo "created_id={$itemId}\n";
echo "item_code={$item['item_code']}\n";
