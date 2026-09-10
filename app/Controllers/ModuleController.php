<?php
/**
 * Generic module controller for ERP management screens.
 */
declare(strict_types=1);

require_once APP_ROOT . '/app/Controllers/BaseController.php';
require_once APP_ROOT . '/app/Models/InventoryModel.php';
require_once APP_ROOT . '/app/Models/PayrollModel.php';
require_once APP_ROOT . '/app/Models/ProjectModel.php';
require_once APP_ROOT . '/app/Models/EmployeeModel.php';
require_once APP_ROOT . '/app/Models/WorkflowModel.php';
require_once APP_ROOT . '/app/Models/ContractAdminModel.php';
require_once APP_ROOT . '/app/Models/RequisitionModel.php';
require_once APP_ROOT . '/app/Models/ChatModel.php';

class ModuleController extends BaseController
{
    private InventoryModel $inventoryModel;
    private PayrollModel $payrollModel;
    private ProjectModel $projectModel;
    private EmployeeModel $employeeModel;
    private WorkflowModel $workflowModel;
    private ContractAdminModel $contractAdminModel;
    private RequisitionModel $requisitionModel;
    private ChatModel $chatModel;

    public function __construct()
    {
        $this->inventoryModel = new InventoryModel();
        $this->payrollModel = new PayrollModel();
        $this->projectModel = new ProjectModel();
        $this->employeeModel = new EmployeeModel();
        $this->workflowModel = new WorkflowModel();
        $this->contractAdminModel = new ContractAdminModel();
        $this->requisitionModel = new RequisitionModel();
        $this->chatModel = new ChatModel();
    }

    public function requisitionForm(): void
    {
        $this->requireSuperAdmin();
        $this->view('modules/requisition_form', [
            'title' => 'Requisition Form Designer',
            'fields' => $this->requisitionModel->getFormFields(),
        ]);
    }

    public function saveRequisitionForm(): void
    {
        $this->requireSuperAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/modules/requisition-form');
        }

        try {
            $rawFields = (array)($_POST['fields'] ?? []);
            $prepared = [];
            foreach ($rawFields as $index => $field) {
                if (!is_array($field)) {
                    continue;
                }
                $prepared[] = [
                    'key' => (string)($field['key'] ?? ''),
                    'label' => (string)($field['label'] ?? ''),
                    'type' => (string)($field['type'] ?? 'text'),
                    'required' => !empty($field['required']) ? 1 : 0,
                    'placeholder' => (string)($field['placeholder'] ?? ''),
                    'help_text' => (string)($field['help_text'] ?? ''),
                ];
            }
            $this->requisitionModel->saveFormFields($prepared);
            $_SESSION['workflow_flash'] = 'Requisition form saved successfully.';
        } catch (Throwable $exception) {
            $_SESSION['workflow_flash'] = 'Unable to save requisition form: ' . $exception->getMessage();
        }
        $this->redirect('/modules/requisition-form');
    }

    public function index(): void
    {
        $this->requireAccess();
        $companyModel = new CompanyModel();
        $this->view('modules/index', [
            'title' => 'Module Center',
            'customModules' => $companyModel->getCustomModulesForCompany((int)($_SESSION['selected_company_id'] ?? 1)),
            'companyModel' => $companyModel,
        ]);
    }

    public function customModule(): void
    {
        $this->requireAccess();
        $companyModel = new CompanyModel();
        $companyId = (int)($_SESSION['selected_company_id'] ?? 1);
        $moduleKey = trim((string)($_GET['module'] ?? $_GET['key'] ?? ''));

        if ($moduleKey === '' || !$companyModel->hasModuleAccess($moduleKey)) {
            $_SESSION['company_flash'] = 'This module is not enabled for the selected company.';
            $this->redirect('/');
        }

        if (!$companyModel->hasCurrentUserModuleAccess($moduleKey)) {
            $_SESSION['company_flash'] = 'This module is not enabled for your staff account.';
            $this->redirect('/');
        }

        $customModule = $companyModel->getCustomModuleByKey($companyId, $moduleKey);
        if ($customModule === null) {
            $_SESSION['company_flash'] = 'This custom module could not be found.';
            $this->redirect('/');
        }

        $this->view('modules/custom_module', [
            'title' => (string)$customModule['module_name'],
            'module' => $customModule,
        ]);
    }

    public function workflow(): void
    {
        $this->requireSuperAdmin();
        $this->view('modules/workflow', [
            'title' => 'Workflow',
            'roles' => $this->workflowModel->getRoles(),
            'parentLinks' => $this->workflowModel->getParentLinks(),
            'roleLevels' => $this->workflowModel->getRoleLevels(),
            'levels' => $this->workflowModel->getLevels(),
        ]);
    }

    public function createWorkflowLevel(): void
    {
        $this->requireSuperAdmin();
        try {
            $this->workflowModel->createLevel((string)($_POST['name'] ?? ''));
            $_SESSION['workflow_flash'] = 'Level created successfully.';
        } catch (Throwable $exception) {
            $_SESSION['workflow_flash'] = 'Unable to create level: ' . $exception->getMessage();
        }
        $this->redirect('/modules/workflow');
    }

    public function deleteWorkflowLevel(): void
    {
        $this->requireSuperAdmin();
        try {
            $this->workflowModel->deleteLevel((int)($_POST['level_id'] ?? 0));
            $_SESSION['workflow_flash'] = 'Level deleted successfully.';
        } catch (Throwable $exception) {
            $_SESSION['workflow_flash'] = 'Unable to delete level: ' . $exception->getMessage();
        }
        $this->redirect('/modules/workflow');
    }

    public function saveWorkflow(): void
    {
        $this->requireSuperAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/modules/workflow');
        }

        try {
            $this->workflowModel->saveParentLinks((array)($_POST['parent_role'] ?? []), (array)($_POST['role_level'] ?? []));
            $_SESSION['workflow_flash'] = 'Approval organogram saved successfully.';
        } catch (Throwable $exception) {
            $_SESSION['workflow_flash'] = 'Unable to save the organogram: ' . $exception->getMessage();
        }
        $this->redirect('/modules/workflow');
    }

    public function inventory(): void
    {
        $this->requireCompanyModule('inventory');
        $search = trim((string)($_GET['search'] ?? ''));
        $searchField = trim((string)($_GET['search_field'] ?? 'all'));
        $availableSearchFields = [
            'all' => 'All columns',
            'item_code' => 'Item Code',
            'name' => 'Name',
            'category' => 'Category',
            'supplier' => 'Supplier',
        ];
        if (!array_key_exists($searchField, $availableSearchFields)) {
            $searchField = 'all';
        }
        $items = $this->inventoryModel->getItems($search, $searchField);
        $categories = $this->inventoryModel->getCategories();
        $this->view('modules/inventory', ['title' => 'Inventory', 'items' => $items, 'categories' => $categories, 'search' => $search, 'searchField' => $searchField, 'availableSearchFields' => $availableSearchFields]);
    }

    public function contractAdmin(): void
    {
        $this->requireCompanyModule('contract_admin');
        $contracts = $this->contractAdminModel->getContracts();
        $events = [];
        $documents = [];
        foreach ($contracts as $contract) {
            $events[(int)$contract['id']] = $this->contractAdminModel->getEvents((int)$contract['id']);
            $documents[(int)$contract['id']] = $this->contractAdminModel->getDocuments((int)$contract['id']);
        }
        $this->view('modules/contract_admin', [
            'title' => 'Contract Admin',
            'contracts' => $contracts,
            'events' => $events,
            'documents' => $documents,
        ]);
    }

    public function saveContract(): void
    {
        $this->requireCompanyModule('contract_admin');
        try {
            $this->contractAdminModel->saveContract($_POST, (int)($_SESSION['user']['id'] ?? 0) ?: null);
            $_SESSION['contract_admin_flash'] = 'Contract record saved successfully.';
        } catch (Throwable $exception) {
            $_SESSION['contract_admin_flash'] = 'Unable to save contract: ' . $exception->getMessage();
        }
        $this->redirect('/modules/contract-admin');
    }

    public function addContractEvent(): void
    {
        $this->requireCompanyModule('contract_admin');
        $contractId = (int)($_POST['contract_id'] ?? 0);
        try {
            $this->contractAdminModel->addEvent($contractId, $_POST, (int)($_SESSION['user']['id'] ?? 0) ?: null);
            $_SESSION['contract_admin_flash'] = 'Process milestone recorded.';
        } catch (Throwable $exception) {
            $_SESSION['contract_admin_flash'] = 'Unable to record milestone: ' . $exception->getMessage();
        }
        $this->redirect('/modules/contract-admin');
    }

    public function uploadContractDocument(): void
    {
        $this->requireCompanyModule('contract_admin');
        $contractId = (int)($_POST['contract_id'] ?? 0);
        try {
            $file = $_FILES['contract_file'] ?? null;
            if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                throw new RuntimeException('Please select a contract document to upload.');
            }
            if (($file['size'] ?? 0) > 10 * 1024 * 1024) {
                throw new RuntimeException('Each contract document must be 10 MB or smaller.');
            }
            $allowedTypes = [
                'application/pdf', 'image/jpeg', 'image/png', 'image/webp', 'text/plain',
                'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ];
            $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'txt', 'doc', 'docx', 'xls', 'xlsx'];
            $originalName = basename((string)($file['name'] ?? ''));
            $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            $mimeType = (new finfo(FILEINFO_MIME_TYPE))->file((string)$file['tmp_name']);
            if (!in_array($extension, $allowedExtensions, true) || !in_array($mimeType, $allowedTypes, true)) {
                throw new RuntimeException('Contract documents must be PDF, Word, Excel, image, or text files.');
            }
            $uploadDirectory = APP_ROOT . '/public/uploads/contracts/' . $contractId;
            if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0775, true) && !is_dir($uploadDirectory)) {
                throw new RuntimeException('The contract document directory could not be created.');
            }
            $storedName = bin2hex(random_bytes(16)) . '.' . $extension;
            $storedPath = $uploadDirectory . '/' . $storedName;
            if (!move_uploaded_file((string)$file['tmp_name'], $storedPath)) {
                throw new RuntimeException('The contract document could not be saved.');
            }
            try {
                $this->contractAdminModel->addDocument($contractId, (string)($_POST['document_date'] ?? ''), (string)($_POST['label'] ?? ''), $originalName, $storedName, (string)$mimeType, (int)$file['size'], (int)($_SESSION['user']['id'] ?? 0) ?: null);
            } catch (Throwable $exception) {
                @unlink($storedPath);
                throw $exception;
            }
            $_SESSION['contract_admin_flash'] = 'Contract document uploaded and filed by date.';
        } catch (Throwable $exception) {
            $_SESSION['contract_admin_flash'] = 'Unable to upload document: ' . $exception->getMessage();
        }
        $this->redirect('/modules/contract-admin');
    }

    public function contractDocumentDownload(): void
    {
        $this->requireCompanyModule('contract_admin');
        $document = $this->contractAdminModel->getDocument((int)($_GET['id'] ?? 0));
        if (!$document) {
            http_response_code(404);
            echo 'Document not found.';
            return;
        }
        $path = APP_ROOT . '/public/uploads/contracts/' . (int)$document['contract_id'] . '/' . basename((string)$document['stored_name']);
        if (!is_file($path)) {
            http_response_code(404);
            echo 'Document file not found.';
            return;
        }
        header('Content-Type: ' . (string)$document['file_type']);
        header('Content-Disposition: attachment; filename="' . str_replace('"', '', basename((string)$document['original_name'])) . '"');
        header('Content-Length: ' . (string)filesize($path));
        readfile($path);
        exit;
    }

    public function projects(): void
    {
        $this->requireCompanyModule('projects');
        $this->view('modules/projects', [
            'title' => 'Projects',
            'projects' => $this->projectModel->getProjects(),
        ]);
    }

    public function projectDetail(): void
    {
        $this->requireCompanyModule('projects');
        $projectId = (int)($_GET['id'] ?? 0);
        $project = $this->projectModel->getProjectById($projectId);
        if (!$project) {
            $_SESSION['project_flash'] = 'Project not found.';
            $this->redirect('/modules/projects');
        }

        $this->view('modules/project_detail', [
            'title' => 'Project Details',
            'project' => $project,
            'documents' => $this->projectModel->getProjectDocuments($projectId),
            'assignments' => $this->projectModel->getProjectAssignments($projectId),
            'schedule' => $this->projectModel->getProjectSchedule($projectId),
            'employees' => $this->employeeModel->getEmployees(),
            'budgets' => $this->projectModel->getProjectBudgets($projectId),
            'deletedBudgets' => $this->projectModel->getDeletedProjectBudgets($projectId),
        ]);
    }

    public function addProjectBudget(): void
    {
        $this->requireCompanyModule('projects');
        $projectId = (int)($_POST['project_id'] ?? 0);
        try {
            $this->projectModel->addProjectBudget($projectId, $_POST);
            $_SESSION['project_flash'] = 'Budget line added successfully.';
        } catch (Throwable $exception) {
            $_SESSION['project_flash'] = 'Unable to add budget line: ' . $exception->getMessage();
        }
        $this->redirect('/modules/projects/view?id=' . $projectId);
    }

    public function saveProjectSchedule(): void
    {
        $this->requireCompanyModule('projects');
        $projectId = (int)($_POST['project_id'] ?? 0);
        try {
            $this->projectModel->saveSchedule($_POST);
            $_SESSION['project_flash'] = 'Project schedule saved successfully.';
        } catch (Throwable $exception) {
            $_SESSION['project_flash'] = 'Unable to save schedule: ' . $exception->getMessage();
        }
        $this->redirect('/modules/projects/view?id=' . $projectId);
    }

    public function deleteProjectBudget(): void
    {
        $this->requireCompanyModule('projects');
        $projectId = (int)($_POST['project_id'] ?? 0);
        try {
            $this->projectModel->deleteProjectBudget((int)($_POST['budget_id'] ?? 0), (string)($_POST['deletion_reason'] ?? ''), (int)($_SESSION['user']['id'] ?? 0) ?: null);
            $_SESSION['project_flash'] = 'Budget item deleted and recorded in the audit history.';
        } catch (Throwable $exception) {
            $_SESSION['project_flash'] = 'Unable to delete budget item: ' . $exception->getMessage();
        }
        $this->redirect('/modules/projects/view?id=' . $projectId);
    }

    public function assignProjectEmployee(): void
    {
        $this->requireCompanyModule('projects');
        try {
            $this->projectModel->assignEmployee(
                (int)($_POST['project_id'] ?? 0),
                (int)($_POST['employee_id'] ?? 0),
                trim((string)($_POST['job_title'] ?? ''))
            );
            $_SESSION['project_flash'] = 'Employee assigned to the project site.';
        } catch (Throwable $exception) {
            $_SESSION['project_flash'] = 'Unable to assign employee: ' . $exception->getMessage();
        }
        $this->redirect('/modules/projects/view?id=' . (int)($_POST['project_id'] ?? 0));
    }

    public function removeProjectEmployee(): void
    {
        $this->requireCompanyModule('projects');
        $projectId = (int)($_POST['project_id'] ?? 0);
        $this->projectModel->removeAssignment((int)($_POST['assignment_id'] ?? 0));
        $_SESSION['project_flash'] = 'Employee removed from the project site.';
        $this->redirect('/modules/projects/view?id=' . $projectId);
    }

    public function projectDocumentDownload(): void
    {
        $this->requireCompanyModule('projects');
        $documentId = (int)($_GET['id'] ?? 0);
        $document = $this->projectModel->getProjectDocument($documentId);
        if (!$document) {
            http_response_code(404);
            echo 'Document not found.';
            return;
        }

        $path = APP_ROOT . '/public/uploads/projects/' . (int)$document['project_id'] . '/' . basename((string)$document['stored_name']);
        if (!is_file($path)) {
            http_response_code(404);
            echo 'Document file not found.';
            return;
        }

        header('Content-Type: ' . (string)$document['file_type']);
        header('Content-Disposition: attachment; filename="' . str_replace('"', '', basename((string)$document['original_name'])) . '"');
        header('Content-Length: ' . (string)filesize($path));
        readfile($path);
        exit;
    }

    public function saveProject(): void
    {
        $this->requireCompanyModule('projects');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $projectId = $this->projectModel->saveProject($_POST);
                $this->storeProjectDocuments($projectId, $_FILES['project_files'] ?? null, (array)($_POST['file_labels'] ?? []));
                $_SESSION['project_flash'] = 'Project saved successfully.';
            } catch (Throwable $exception) {
                $_SESSION['project_flash'] = 'Unable to save project: ' . $exception->getMessage();
            }
        }
        $this->redirect('/modules/projects');
    }

    private function storeProjectDocuments(int $projectId, ?array $files, array $labels): void
    {
        if (!$files || !isset($files['tmp_name']) || !is_array($files['tmp_name'])) {
            return;
        }

        $allowedTypes = [
            'application/pdf',
            'image/jpeg',
            'image/png',
            'image/webp',
            'text/plain',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ];
        $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'txt', 'doc', 'docx', 'xls', 'xlsx'];
        $uploadDirectory = APP_ROOT . '/public/uploads/projects/' . $projectId;
        if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0775, true) && !is_dir($uploadDirectory)) {
            throw new RuntimeException('The project document directory could not be created.');
        }

        foreach ($files['tmp_name'] as $index => $temporaryPath) {
            if (($files['error'][$index] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            if (($files['error'][$index] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
                throw new RuntimeException('One of the project documents failed to upload.');
            }
            if (($files['size'][$index] ?? 0) > 10 * 1024 * 1024) {
                throw new RuntimeException('Each project document must be 10 MB or smaller.');
            }

            $originalName = basename((string)($files['name'][$index] ?? ''));
            $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            $mimeType = (new finfo(FILEINFO_MIME_TYPE))->file((string)$temporaryPath);
            if (!in_array($extension, $allowedExtensions, true) || !in_array($mimeType, $allowedTypes, true)) {
                throw new RuntimeException('Project documents must be PDF, Word, Excel, image, or text files.');
            }

            $label = trim((string)($labels[$index] ?? ''));
            if ($label === '') {
                throw new RuntimeException('Every project document must have a label.');
            }
            $storedName = bin2hex(random_bytes(16)) . '.' . $extension;
            if (!move_uploaded_file((string)$temporaryPath, $uploadDirectory . '/' . $storedName)) {
                throw new RuntimeException('A project document could not be saved.');
            }
            $this->projectModel->addProjectDocument($projectId, $label, $originalName, $storedName, $mimeType, (int)$files['size'][$index]);
        }
    }

    public function saveItem(): void
    {
        $this->requireCompanyModule('inventory');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $itemId = (int)($_POST['item_id'] ?? 0);
            $changeReason = trim((string)($_POST['change_reason'] ?? ''));

            if ($itemId > 0) {
                if ($changeReason === '') {
                    $_SESSION['inventory_flash'] = 'Please provide a reason for the inventory item change.';
                    $this->redirect('/inventory/detail?id=' . $itemId);
                }
                $this->inventoryModel->updateItem($itemId, $_POST, $changeReason);
                $_SESSION['inventory_flash'] = 'Inventory item updated successfully.';
            } else {
                $this->inventoryModel->createItem($_POST);
                $_SESSION['inventory_flash'] = 'Item saved successfully.';
            }
        }
        $this->redirect('/modules/inventory');
    }

    public function itemDetail(): void
    {
        $this->requireCompanyModule('inventory');
        $itemId = (int)($_GET['id'] ?? 0);
        if ($itemId <= 0) {
            $this->redirect('/modules/inventory');
        }

        $item = $this->inventoryModel->getItemById($itemId);
        if (!$item) {
            $_SESSION['inventory_flash'] = 'Item not found.';
            $this->redirect('/modules/inventory');
        }

        $changeHistory = $this->inventoryModel->getItemChangeHistory($itemId);
        $issueHistory = $this->inventoryModel->getItemIssueHistory($itemId);
        $this->view('modules/item_detail', ['title' => 'Inventory Item', 'item' => $item, 'changeHistory' => $changeHistory, 'issueHistory' => $issueHistory, 'canIssueInventory' => $this->canHandleStoreInventory()]);
    }

    public function issueItem(): void
    {
        $this->requireCompanyModule('inventory');
        if (!$this->canHandleStoreInventory()) {
            $_SESSION['inventory_flash'] = 'Only personnel in the Store department can issue inventory items.';
            $this->redirect('/inventory/detail?id=' . (int)($_POST['item_id'] ?? 0));
        }
        $itemId = (int)($_POST['item_id'] ?? 0);
        try {
            $this->inventoryModel->issueItem($itemId, (string)($_POST['issued_to'] ?? ''), (int)($_POST['quantity'] ?? 0), (string)($_POST['issued_date'] ?? ''), (string)($_POST['stock_source'] ?? ''), (int)($_SESSION['user']['id'] ?? 0) ?: null);
            $_SESSION['inventory_flash'] = 'Inventory issue recorded successfully.';
        } catch (Throwable $exception) {
            $_SESSION['inventory_flash'] = 'Unable to record inventory issue: ' . $exception->getMessage();
        }
        $this->redirect('/inventory/detail?id=' . $itemId);
    }

    private function canHandleStoreInventory(): bool
    {
        $roleName = strtolower(trim((string)($_SESSION['user']['role_name'] ?? '')));
        if (in_array($roleName, ['super admin', 'superadministrator', 'super administrator'], true)) {
            return true;
        }
        $employeeId = (int)($_SESSION['user']['employee_id'] ?? 0);
        $employee = $employeeId > 0 ? $this->employeeModel->getEmployeeById($employeeId) : null;
        return $employee !== null && strpos(strtolower(trim((string)($employee['department'] ?? ''))), 'store') !== false;
    }

    public function accounting(): void
    {
        $this->requireCompanyModule('accounting');
        $payrolls = $this->payrollModel->getPayrolls();
        $employees = $this->payrollModel->getEmployees();
        $this->view('modules/accounting', [
            'title' => 'Accounting',
            'payrolls' => $payrolls,
            'employees' => $employees,
        ]);
    }

    public function portalPayroll(): void
    {
        $this->requireCompanyModule('accounting');
        $portalPayrolls = $this->payrollModel->getPortalPayrolls();
        $this->view('portal/payroll', [
            'title' => 'Payroll Portal',
            'portalPayrolls' => $portalPayrolls,
        ]);
    }

    public function accountingPayrollPortal(): void
    {
        $this->requireCompanyModule('accounting');
        $employees = $this->payrollModel->getEmployees();
        $this->view('modules/accounting_payroll_portal', [
            'title' => 'Process Payroll',
            'employees' => $employees,
            'current_month' => date('Y-m'),
        ]);
    }

    public function processAccountingPayroll(): void
    {
        $this->requireCompanyModule('accounting');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['accounting_flash'] = 'Invalid request method.';
            $this->redirect('/modules/accounting/payroll');
        }

        try {
            $payrollMonth = trim((string)($_POST['payroll_month'] ?? date('Y-m')));
            $selectedEmployees = (array)($_POST['employee_ids'] ?? []);
            $employeeIds = array_values(array_filter(array_map('intval', $selectedEmployees)));

            if ($payrollMonth === '') {
                throw new InvalidArgumentException('Payroll month is required.');
            }

            $results = $this->payrollModel->processMonthlyPayroll($payrollMonth, $employeeIds);
            $_SESSION['accounting_flash'] = sprintf(
                'Payroll processed: %d successful, %d failed.',
                $results['success'] ?? 0,
                $results['failed'] ?? 0
            );

            if (!empty($results['errors'])) {
                $_SESSION['accounting_errors'] = $results['errors'];
            }
        } catch (Throwable $exception) {
            $_SESSION['accounting_flash'] = 'Error processing payroll: ' . $exception->getMessage();
        }

        $this->redirect('/modules/accounting/payroll');
    }

    public function savePayroll(): void
    {
        $this->requireCompanyModule('accounting');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $payrollId = (int)($_POST['payroll_id'] ?? 0);
            if ($payrollId > 0) {
                $this->payrollModel->updatePayroll($payrollId, $_POST);
                $_SESSION['accounting_flash'] = 'Payroll entry updated successfully.';
            } else {
                $this->payrollModel->savePayroll($_POST);
                $_SESSION['accounting_flash'] = 'Payroll entry added successfully.';
            }
        }
        $this->redirect('/modules/accounting');
    }

    public function sendPayroll(): void
    {
        $this->requireCompanyModule('accounting');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $payrollId = (int)($_POST['payroll_id'] ?? 0);
            if ($payrollId > 0) {
                $this->payrollModel->markPayrollSent($payrollId);
                $_SESSION['accounting_flash'] = 'Payroll sent to employee portal.';
            }
        }
        $this->redirect('/modules/accounting');
    }

    public function bulkSendPayrolls(): void
    {
        $this->requireCompanyModule('accounting');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $sentCount = $this->payrollModel->markAllPayrollsSent();
            $_SESSION['accounting_flash'] = $sentCount > 0 ? "$sentCount payroll records sent to employee portals." : 'No pending payroll records to send.';
        }
        $this->redirect('/modules/accounting');
    }

    public function uploadPayrolls(): void
    {
        $this->requireCompanyModule('accounting');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (empty($_FILES['payroll_file']['tmp_name']) || ($_FILES['payroll_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                $_SESSION['accounting_flash'] = 'Please choose a valid payroll CSV file to upload.';
            } else {
                $fileName = $_FILES['payroll_file']['name'] ?? '';
                $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

                if ($extension !== 'csv') {
                    $_SESSION['accounting_flash'] = 'Unsupported file type. Upload the payroll CSV template.';
                } else {
                    $imported = $this->payrollModel->bulkUploadPayrolls($_FILES['payroll_file']);
                    $_SESSION['accounting_flash'] = $imported > 0
                        ? "$imported payroll records imported successfully."
                        : 'No payroll records were imported. Please verify the file format and required headers.';
                }
            }
        }

        $this->redirect('/modules/accounting');
    }

    public function downloadPayrollTemplate(): void
    {
        $this->requireCompanyModule('accounting');
        $templatePath = APP_ROOT . '/payroll_template.csv';
        if (!is_file($templatePath)) {
            $_SESSION['accounting_flash'] = 'The payroll template is unavailable.';
            $this->redirect('/modules/accounting');
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="payroll_template.csv"');
        header('Content-Length: ' . (string)filesize($templatePath));
        readfile($templatePath);
        exit;
    }

    public function chat(): void
    {
        $this->requireCompanyModule('chat');
        $currentUserId = (int)($_SESSION['user']['id'] ?? 0);
        $colleagues = $this->chatModel->getColleaguesWithHistory($currentUserId);
        $groups = $this->chatModel->getGroupsForUser($currentUserId);
        $conversations = [];
        foreach ($groups as $group) {
            $conversations[] = [
                'type' => 'group',
                'id' => (int)$group['id'],
                'latest' => (string)($group['last_message_at'] ?? ''),
                'data' => $group,
            ];
        }
        foreach ($colleagues as $colleague) {
            $conversations[] = [
                'type' => 'direct',
                'id' => (int)$colleague['id'],
                'latest' => (string)($colleague['last_message_at'] ?? ''),
                'data' => $colleague,
            ];
        }
        usort($conversations, static function (array $left, array $right): int {
            $leftTime = $left['latest'] !== '' ? strtotime($left['latest']) : 0;
            $rightTime = $right['latest'] !== '' ? strtotime($right['latest']) : 0;
            return ($rightTime <=> $leftTime) ?: strcmp((string)($left['data']['group_name'] ?? $left['data']['name'] ?? ''), (string)($right['data']['group_name'] ?? $right['data']['name'] ?? ''));
        });
        $selectedColleagueId = (int)($_GET['with'] ?? 0);
        $selectedColleague = $selectedColleagueId > 0 ? $this->chatModel->getColleague($currentUserId, $selectedColleagueId) : null;
        if ($selectedColleague !== null) {
            $this->chatModel->markConversationRead($currentUserId, $selectedColleagueId);
        }
        $selectedGroupId = (int)($_GET['group'] ?? 0);
        $selectedGroup = $selectedGroupId > 0 ? $this->chatModel->getGroup($selectedGroupId, $currentUserId) : null;
        if ($selectedGroup !== null) {
            $this->chatModel->markGroupRead($selectedGroupId, $currentUserId);
        }
        $canManageSelectedGroup = $selectedGroup !== null && $this->chatModel->canManageGroup($selectedGroupId, $currentUserId);

        $this->view('modules/chat', [
            'title' => 'Team Chat',
            'colleagues' => $colleagues,
            'groups' => $groups,
            'conversations' => $conversations,
            'selectedColleague' => $selectedColleague,
            'selectedGroup' => $selectedGroup,
            'messages' => $selectedColleagueId > 0 ? $this->chatModel->getMessages($currentUserId, $selectedColleagueId) : [],
            'groupMessages' => $selectedGroupId > 0 ? $this->chatModel->getGroupMessages($selectedGroupId, $currentUserId) : [],
            'groupMembers' => $selectedGroupId > 0 ? $this->chatModel->getGroupMembers($selectedGroupId, $currentUserId) : [],
            'availableGroupMembers' => $canManageSelectedGroup ? $this->chatModel->getAvailableGroupMembers($selectedGroupId, $currentUserId) : [],
            'canManageSelectedGroup' => $canManageSelectedGroup,
            'currentUserId' => $currentUserId,
            'canCreateGroup' => $this->canCreateChatGroup(),
        ]);
    }

    public function createChatGroup(): void
    {
        $this->requireCompanyModule('chat');
        if (!$this->canCreateChatGroup()) {
            $_SESSION['chat_flash'] = 'Only the Managing Director can create custom group chats.';
            $this->redirect('/modules/chat');
        }
        try {
            $groupId = $this->chatModel->createGroup((int)($_SESSION['user']['id'] ?? 0), (string)($_POST['group_name'] ?? ''));
            $this->redirect('/modules/chat?group=' . $groupId);
        } catch (Throwable $exception) {
            $_SESSION['chat_flash'] = 'Unable to create group chat: ' . $exception->getMessage();
            $this->redirect('/modules/chat');
        }
    }

    public function addChatGroupMember(): void
    {
        $this->requireCompanyModule('chat');
        $groupId = (int)($_POST['group_id'] ?? 0);
        try {
            $this->chatModel->addGroupMember($groupId, (int)($_POST['user_id'] ?? 0), (int)($_SESSION['user']['id'] ?? 0));
        } catch (Throwable $exception) {
            $_SESSION['chat_flash'] = 'Unable to add member: ' . $exception->getMessage();
        }
        $this->redirect('/modules/chat?group=' . $groupId);
    }

    public function removeChatGroupMember(): void
    {
        $this->requireCompanyModule('chat');
        $groupId = (int)($_POST['group_id'] ?? 0);
        try {
            $this->chatModel->removeGroupMember($groupId, (int)($_POST['user_id'] ?? 0), (int)($_SESSION['user']['id'] ?? 0));
        } catch (Throwable $exception) {
            $_SESSION['chat_flash'] = 'Unable to remove member: ' . $exception->getMessage();
        }
        $this->redirect('/modules/chat?group=' . $groupId);
    }

    public function chatColleagues(): void
    {
        $this->requireCompanyModule('chat');
        $currentUserId = (int)($_SESSION['user']['id'] ?? 0);
        $this->json(['colleagues' => $this->chatModel->searchColleagues($currentUserId, (string)($_GET['search'] ?? ''))]);
    }

    public function chatMessages(): void
    {
        $this->requireCompanyModule('chat');
        $currentUserId = (int)($_SESSION['user']['id'] ?? 0);
        $groupId = (int)($_GET['group'] ?? 0);
        if ($groupId > 0) {
            $this->json(['messages' => $this->chatModel->getGroupMessages($groupId, $currentUserId, (int)($_GET['after'] ?? 0))]);
        }
        $colleagueId = (int)($_GET['with'] ?? 0);
        if ($colleagueId <= 0 || $this->chatModel->getColleague($currentUserId, $colleagueId) === null) {
            $this->json(['messages' => []]);
        }

        $this->json([
            'messages' => $this->chatModel->getMessagesSince($currentUserId, $colleagueId, (int)($_GET['after'] ?? 0)),
        ]);
    }

    public function sendChatMessage(): void
    {
        $this->requireCompanyModule('chat');
        $recipientId = (int)($_POST['recipient_id'] ?? 0);
        $groupId = (int)($_POST['group_id'] ?? 0);
        $storedPath = null;
        try {
            [$attachment, $storedPath] = $this->prepareChatAttachment();
            if ($groupId > 0) {
                $this->chatModel->sendGroupMessage((int)($_SESSION['user']['id'] ?? 0), $groupId, (string)($_POST['message'] ?? ''), $attachment);
            } else {
                $this->chatModel->sendMessage((int)($_SESSION['user']['id'] ?? 0), $recipientId, (string)($_POST['message'] ?? ''), $attachment);
            }
        } catch (Throwable $exception) {
            if ($storedPath !== null && is_file($storedPath)) {
                @unlink($storedPath);
            }
            $_SESSION['chat_flash'] = 'Unable to send message: ' . $exception->getMessage();
        }
        $this->redirect($groupId > 0 ? '/modules/chat?group=' . $groupId : '/modules/chat?with=' . $recipientId);
    }

    public function chatAttachmentDownload(): void
    {
        $this->requireCompanyModule('chat');
        $file = $this->chatModel->getAttachment((int)($_GET['id'] ?? 0), (int)($_SESSION['user']['id'] ?? 0));
        if (!$file) {
            http_response_code(404);
            echo 'Chat file not found.';
            return;
        }
        $path = APP_ROOT . '/public/uploads/chat/' . (int)$file['company_id'] . '/' . basename((string)$file['stored_name']);
        if (!is_file($path)) {
            http_response_code(404);
            echo 'Chat file not found.';
            return;
        }
        header('Content-Type: ' . (string)$file['file_type']);
        $disposition = strpos((string)$file['file_type'], 'audio/') === 0 ? 'inline' : 'attachment';
        header('Content-Disposition: ' . $disposition . '; filename="' . str_replace('"', '', basename((string)$file['original_name'])) . '"');
        header('Content-Length: ' . (string)filesize($path));
        readfile($path);
        exit;
    }

    private function prepareChatAttachment(): array
    {
        $file = $_FILES['chat_file'] ?? null;
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return [null, null];
        }
        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK || ($file['size'] ?? 0) > 10 * 1024 * 1024) {
            throw new RuntimeException('Chat files must be 10 MB or smaller.');
        }
        $allowedTypes = [
            'application/pdf', 'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'text/plain',
            'application/zip', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'audio/webm', 'audio/ogg', 'audio/mp4', 'audio/mpeg', 'audio/wav', 'audio/x-wav',
            'video/webm', 'application/ogg', 'video/mp4',
        ];
        $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'txt', 'zip', 'doc', 'docx', 'xls', 'xlsx', 'webm', 'ogg', 'm4a', 'mp3', 'wav'];
        $originalName = basename((string)($file['name'] ?? ''));
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $mimeType = (new finfo(FILEINFO_MIME_TYPE))->file((string)$file['tmp_name']);
        if (!in_array($extension, $allowedExtensions, true) || !in_array($mimeType, $allowedTypes, true)) {
            throw new RuntimeException('Chat files must be PDF, image, Word, Excel, ZIP, text, or audio files.');
        }
        if ($extension === 'webm' && $mimeType === 'video/webm') {
            $mimeType = 'audio/webm';
        } elseif ($extension === 'ogg' && $mimeType === 'application/ogg') {
            $mimeType = 'audio/ogg';
        } elseif ($extension === 'm4a' && $mimeType === 'video/mp4') {
            $mimeType = 'audio/mp4';
        }
        $uploadDirectory = APP_ROOT . '/public/uploads/chat/' . (int)($_SESSION['selected_company_id'] ?? 1);
        if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0775, true) && !is_dir($uploadDirectory)) {
            throw new RuntimeException('The chat file directory could not be created.');
        }
        $storedName = bin2hex(random_bytes(16)) . '.' . $extension;
        $storedPath = $uploadDirectory . '/' . $storedName;
        if (!move_uploaded_file((string)$file['tmp_name'], $storedPath)) {
            throw new RuntimeException('The chat file could not be saved.');
        }
        return [[
            'original_name' => $originalName,
            'stored_name' => $storedName,
            'file_type' => (string)$mimeType,
            'file_size' => (int)$file['size'],
        ], $storedPath];
    }

    private function canCreateChatGroup(): bool
    {
        $roleName = strtolower(trim((string)($_SESSION['user']['role_name'] ?? '')));
        return in_array($roleName, ['managing director', 'managing_director', 'super admin', 'superadministrator', 'super administrator'], true);
    }
}
