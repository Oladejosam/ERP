<?php
require_once __DIR__ . '/../app/Models/CompanyModel.php';

$companyModel = new CompanyModel();
if (!method_exists($companyModel, 'saveRoleModuleAccess')) {
    fwrite(STDERR, "Missing saveRoleModuleAccess method\n");
    exit(1);
}
if (!method_exists($companyModel, 'getRoleModuleAccessMap')) {
    fwrite(STDERR, "Missing getRoleModuleAccessMap method\n");
    exit(1);
}
if (!method_exists($companyModel, 'hasRoleModuleConfiguration')) {
    fwrite(STDERR, "Missing hasRoleModuleConfiguration method\n");
    exit(1);
}

echo "role module access API present\n";
