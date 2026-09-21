<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Model.php';
require_once __DIR__ . '/../app/Models/RmcOperationsModel.php';

$_SESSION['selected_company_id'] = 1;
$model = new RmcOperationsModel();
$report = $model->report();

foreach (['quality_pass_rate', 'dispatch_efficiency', 'on_time_dispatch_rate', 'erp_bi_readiness'] as $key) {
    if (!array_key_exists($key, $report)) {
        fwrite(STDERR, "Missing BI metric: {$key}\n");
        exit(1);
    }
}

echo "business intelligence metrics ok\n";
