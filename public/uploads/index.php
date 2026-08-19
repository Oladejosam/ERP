<?php
/**
 * Front controller for the ERP application.
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/core/Database.php';
require_once dirname(__DIR__) . '/core/Router.php';
require_once dirname(__DIR__) . '/core/Controller.php';
require_once dirname(__DIR__) . '/core/Model.php';
require_once dirname(__DIR__) . '/core/Auth.php';

require_once dirname(__DIR__) . '/app/Controllers/HomeController.php';
require_once dirname(__DIR__) . '/app/Controllers/AuthController.php';
require_once dirname(__DIR__) . '/app/Controllers/ModuleController.php';
require_once dirname(__DIR__) . '/app/Controllers/ManagementController.php';
require_once dirname(__DIR__) . '/app/Controllers/WorkflowController.php';
require_once dirname(__DIR__) . '/app/Models/UserModel.php';
require_once dirname(__DIR__) . '/app/Models/PayrollModel.php';
require_once dirname(__DIR__) . '/app/Models/PurchaseOrderModel.php';

$routes = [
    '/' => ['HomeController', 'index'],
    '/login' => ['AuthController', 'login'],
    '/logout' => ['AuthController', 'logout'],
    '/modules' => ['ModuleController', 'index'],
    '/modules/inventory' => ['ModuleController', 'inventory'],
    '/inventory/save' => ['ModuleController', 'saveItem'],
    '/inventory/detail' => ['ModuleController', 'itemDetail'],
    '/modules/accounting' => ['ModuleController', 'accounting'],
    '/modules/accounting/save' => ['ModuleController', 'savePayroll'],
    '/modules/accounting/send' => ['ModuleController', 'sendPayroll'],
    '/modules/accounting/send-all' => ['ModuleController', 'bulkSendPayrolls'],
    '/modules/accounting/upload' => ['ModuleController', 'uploadPayrolls'],
    '/portal/payroll' => ['ModuleController', 'portalPayroll'],
    '/management/employees' => ['ManagementController', 'employees'],
    '/management/employees/view' => ['ManagementController', 'viewEmployee'],
    '/management/employees/save' => ['ManagementController', 'saveEmployee'],
    '/management/employees/template' => ['ManagementController', 'downloadEmployeeTemplate'],
    '/management/employees/upload' => ['ManagementController', 'uploadEmployees'],
    '/management/employees/upload_preview' => ['ManagementController', 'previewEmployeeUpload'],
    '/management/employees/upload_confirm' => ['ManagementController', 'confirmEmployeeUpload'],
    '/management/employees/update' => ['ManagementController', 'updateEmployee'],
    '/management/employees/create-credentials' => ['ManagementController', 'updateEmployee'],
    '/management/employees/deactivate' => ['ManagementController', 'deactivateEmployee'],
    '/management/employees/archive' => ['ManagementController', 'archivedEmployees'],
    '/management/employees/archive_action' => ['ManagementController', 'archiveAction'],
    '/management/departments' => ['ManagementController', 'departments'],
    '/management/departments/save' => ['ManagementController', 'saveDepartment'],
    '/management/departments/update' => ['ManagementController', 'updateDepartment'],
    '/management/hr' => ['ManagementController', 'hr'],
    '/hr/leave/add' => ['ManagementController', 'addLeave'],
    '/hr/memo/create' => ['ManagementController', 'createMemo'],
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
    '/portal/memo/mark-read' => ['ManagementController', 'markMemoRead'],
    '/portal/task/complete' => ['ManagementController', 'completeTask'],
    '/management/procurement' => ['ManagementController', 'procurement'],
    '/management/requisition/backfill' => ['ManagementController', 'backfillReview'],
    '/management/requisition/assign' => ['ManagementController', 'assignRequisition'],
    '/management/requisition/assign_bulk' => ['ManagementController', 'assignBulk'],
    '/requisition' => ['ManagementController', 'requisition'],
    '/requisition/save' => ['ManagementController', 'requisition'],
    '/requisition/decision' => ['ManagementController', 'requisitionDecision'],
    '/requisition/view' => ['ManagementController', 'viewRequisition'],
    '/api/employees/search' => ['ManagementController', 'searchEmployees'],
    '/management/procurement/save' => ['ManagementController', 'savePurchaseOrder'],
    '/management/procurement/upload' => ['ManagementController', 'uploadPurchaseOrders'],
    '/management/workflow-dependencies' => ['WorkflowController', 'dependencies'],
    '/management/workflow-dependencies/save' => ['WorkflowController', 'saveDependencies'],
    '/management/workflow-dependencies/create-role' => ['WorkflowController', 'createRole'],
    '/management/workflow-dependencies/delete-role' => ['WorkflowController', 'deleteRole'],
    '/management/workflow-dependencies/create-department' => ['WorkflowController', 'createDepartment'],
    '/management/workflow-dependencies/delete-department' => ['WorkflowController', 'deleteDepartment'],
    '/management/purchase-order' => ['ManagementController', 'viewPurchaseOrder'],
    '/management/purchase-order/audit' => ['ManagementController', 'purchaseOrderAudit'],
    '/management/sales' => ['ManagementController', 'sales'],
];

Router::route($routes);
