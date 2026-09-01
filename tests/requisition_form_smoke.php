<?php
require_once __DIR__ . '/../app/Models/RequisitionModel.php';

$model = new RequisitionModel();
foreach (['saveFormFields', 'getFormFields', 'getDefaultFormFields', 'getFormValues'] as $method) {
    if (!method_exists($model, $method)) {
        fwrite(STDERR, "Missing method: {$method}\n");
        exit(1);
    }
}

echo "requisition form API present\n";
