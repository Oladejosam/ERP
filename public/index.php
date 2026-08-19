<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/core/Database.php';
require_once dirname(__DIR__) . '/core/Router.php';
require_once dirname(__DIR__) . '/core/Controller.php';
require_once dirname(__DIR__) . '/core/Model.php';
require_once dirname(__DIR__) . '/core/Auth.php';
require_once dirname(__DIR__) . '/app/Controllers/BaseController.php';
require_once dirname(__DIR__) . '/app/Controllers/HomeController.php';
require_once dirname(__DIR__) . '/app/Controllers/AuthController.php';
require_once dirname(__DIR__) . '/app/Controllers/SetupController.php';
require_once dirname(__DIR__) . '/app/Controllers/ModuleController.php';
require_once dirname(__DIR__) . '/app/Controllers/ManagementController.php';
require_once dirname(__DIR__) . '/app/Models/UserModel.php';
require_once dirname(__DIR__) . '/app/Models/RoleModel.php';
require_once dirname(__DIR__) . '/app/Models/EmployeeModel.php';
require_once dirname(__DIR__) . '/app/Models/CompanyModel.php';

$routes = [
    '/' => ['HomeController', 'index'],
    '/login' => ['AuthController', 'login'],
    '/logout' => ['AuthController', 'logout'],
    '/setup' => ['SetupController', 'index'],
    '/modules' => ['ModuleController', 'index'],
    '/modules/inventory' => ['ModuleController', 'inventory'],
    '/inventory/save' => ['ModuleController', 'saveItem'],
    '/inventory/detail' => ['ModuleController', 'itemDetail'],
    '/modules/accounting' => ['ModuleController', 'accounting'],
    '/management/employees' => ['ManagementController', 'employees'],
    '/management/employees/save' => ['ManagementController', 'saveEmployee'],
    '/management/employees/view' => ['ManagementController', 'viewEmployee'],
    '/management/employees/deactivate' => ['ManagementController', 'bulkEmployeeAction'],
    '/management/employees/template' => ['ManagementController', 'downloadEmployeeTemplate'],
    '/management/employees/upload' => ['ManagementController', 'uploadEmployees'],
    '/management/hr' => ['ManagementController', 'hr'],
    '/portal/payroll' => ['ModuleController', 'portalPayroll'],
    '/portal/staff' => ['ManagementController', 'staffPortal'],
    '/portal/admin' => ['ManagementController', 'portalAdmin'],
    '/portal/hr' => ['ManagementController', 'portalHr'],
    '/portal/accountant' => ['ManagementController', 'portalAccountant'],
    '/portal/procurement-officer' => ['ManagementController', 'portalProcurementOfficer'],
    '/portal/super-admin' => ['ManagementController', 'portalSuperAdmin'],
    '/portal/managing-director' => ['ManagementController', 'portalManagingDirector'],
    '/portal/finance-manager' => ['ManagementController', 'portalFinanceManager'],
    '/portal/hr-manager' => ['ManagementController', 'portalHrManager'],
    '/portal/site-engineer' => ['ManagementController', 'portalSiteEngineer'],
    '/portal/department-head' => ['ManagementController', 'portalDepartmentHead'],
    '/portal/logistics-officer' => ['ManagementController', 'portalLogisticsOfficer'],
];

Router::route($routes);
