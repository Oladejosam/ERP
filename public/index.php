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
require_once dirname(__DIR__) . '/app/Controllers/CompanyController.php';
require_once dirname(__DIR__) . '/app/Controllers/ModuleController.php';
require_once dirname(__DIR__) . '/app/Controllers/ManagementController.php';
require_once dirname(__DIR__) . '/app/Models/UserModel.php';
require_once dirname(__DIR__) . '/app/Models/RoleModel.php';
require_once dirname(__DIR__) . '/app/Models/EmployeeModel.php';
require_once dirname(__DIR__) . '/app/Models/CompanyModel.php';
require_once dirname(__DIR__) . '/app/Models/ProjectModel.php';
require_once dirname(__DIR__) . '/app/Models/RequisitionModel.php';

(new CompanyModel())->getCompanies();

$routes = [
    '/' => ['HomeController', 'index'],
    '/login' => ['AuthController', 'login'],
    '/super-admin/login' => ['AuthController', 'superAdminLogin'],
    '/logout' => ['AuthController', 'logout'],
    '/setup' => ['SetupController', 'index'],
    '/setup/select-company' => ['SetupController', 'selectCompany'],
    '/company/workspace' => ['CompanyController', 'workspace'],
    '/company/select' => ['CompanyController', 'select'],
    '/company/set-active' => ['CompanyController', 'setActive'],
    '/company/create' => ['CompanyController', 'create'],
    '/modules' => ['ModuleController', 'index'],
    '/modules/inventory' => ['ModuleController', 'inventory'],
    '/modules/projects' => ['ModuleController', 'projects'],
    '/modules/projects/view' => ['ModuleController', 'projectDetail'],
    '/modules/projects/document' => ['ModuleController', 'projectDocumentDownload'],
    '/projects/assign-employee' => ['ModuleController', 'assignProjectEmployee'],
    '/projects/remove-employee' => ['ModuleController', 'removeProjectEmployee'],
    '/projects/budget/add' => ['ModuleController', 'addProjectBudget'],
    '/projects/budget/delete' => ['ModuleController', 'deleteProjectBudget'],
    '/projects/save' => ['ModuleController', 'saveProject'],
    '/inventory/save' => ['ModuleController', 'saveItem'],
    '/inventory/detail' => ['ModuleController', 'itemDetail'],
    '/modules/accounting' => ['ModuleController', 'accounting'],
    '/management/employees' => ['ManagementController', 'employees'],
    '/management/employees/save' => ['ManagementController', 'saveEmployee'],
    '/management/employees/view' => ['ManagementController', 'viewEmployee'],
    '/management/employees/update-photo' => ['ManagementController', 'updateEmployeePhoto'],
    '/management/employees/custom-field' => ['ManagementController', 'addEmployeeCustomField'],
    '/management/employees/custom-field/delete' => ['ManagementController', 'deleteEmployeeCustomField'],
    '/management/employees/update' => ['ManagementController', 'updateEmployee'],
    '/management/employees/create-credentials' => ['ManagementController', 'populateEmployeeUsers'],
    '/management/employees/archive' => ['ManagementController', 'archivedEmployees'],
    '/management/employees/deactivate' => ['ManagementController', 'bulkEmployeeAction'],
    '/management/employees/template' => ['ManagementController', 'downloadEmployeeTemplate'],
    '/management/employees/upload' => ['ManagementController', 'uploadEmployees'],
    '/requisition' => ['ManagementController', 'requisition'],
    '/requisition/save' => ['ManagementController', 'saveRequisition'],
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
