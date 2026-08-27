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

class ModuleController extends BaseController
{
    private InventoryModel $inventoryModel;
    private PayrollModel $payrollModel;
    private ProjectModel $projectModel;
    private EmployeeModel $employeeModel;
    private WorkflowModel $workflowModel;
    private ContractAdminModel $contractAdminModel;

    public function __construct()
    {
        $this->inventoryModel = new InventoryModel();
        $this->payrollModel = new PayrollModel();
        $this->projectModel = new ProjectModel();
        $this->employeeModel = new EmployeeModel();
        $this->workflowModel = new WorkflowModel();
        $this->contractAdminModel = new ContractAdminModel();
    }

    public function index(): void
    {
        $this->requireAccess();
        $this->view('modules/index', ['title' => 'Module Center']);
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
        $items = $this->inventoryModel->getItems();
        $categories = $this->inventoryModel->getCategories();
        $this->view('modules/inventory', ['title' => 'Inventory', 'items' => $items, 'categories' => $categories]);
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
        $this->view('modules/item_detail', ['title' => 'Inventory Item', 'item' => $item, 'changeHistory' => $changeHistory]);
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
}
