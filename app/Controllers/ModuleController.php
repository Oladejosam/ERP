<?php
/**
 * Generic module controller for ERP management screens.
 */
declare(strict_types=1);

require_once APP_ROOT . '/app/Controllers/BaseController.php';
require_once APP_ROOT . '/app/Models/InventoryModel.php';
require_once APP_ROOT . '/app/Models/PayrollModel.php';

class ModuleController extends BaseController
{
    private InventoryModel $inventoryModel;
    private PayrollModel $payrollModel;

    public function __construct()
    {
        $this->inventoryModel = new InventoryModel();
        $this->payrollModel = new PayrollModel();
    }

    public function index(): void
    {
        $this->requireAccess();
        $this->view('modules/index', ['title' => 'Module Center']);
    }

    public function inventory(): void
    {
        $this->requireAccess();
        $items = $this->inventoryModel->getItems();
        $categories = $this->inventoryModel->getCategories();
        $this->view('modules/inventory', ['title' => 'Inventory', 'items' => $items, 'categories' => $categories]);
    }

    public function saveItem(): void
    {
        $this->requireAccess();
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
        $this->requireAccess();
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
        $this->requireAccess();
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
        $this->requireAccess();
        $portalPayrolls = $this->payrollModel->getPortalPayrolls();
        $this->view('portal/payroll', [
            'title' => 'Payroll Portal',
            'portalPayrolls' => $portalPayrolls,
        ]);
    }

    public function savePayroll(): void
    {
        $this->requireAccess();
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
        $this->requireAccess();
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
        $this->requireAccess();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $sentCount = $this->payrollModel->markAllPayrollsSent();
            $_SESSION['accounting_flash'] = $sentCount > 0 ? "$sentCount payroll records sent to employee portals." : 'No pending payroll records to send.';
        }
        $this->redirect('/modules/accounting');
    }

    public function uploadPayrolls(): void
    {
        $this->requireAccess();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (empty($_FILES['payroll_file']['tmp_name']) || ($_FILES['payroll_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                $_SESSION['accounting_flash'] = 'Please choose a valid payroll file (CSV/XLSX) to upload.';
            } else {
                $fileName = $_FILES['payroll_file']['name'] ?? '';
                $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

                if (!in_array($extension, ['csv', 'xlsx', 'xls'], true)) {
                    $_SESSION['accounting_flash'] = 'Unsupported file type. Upload a CSV, XLSX, or XLS file.';
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
}
