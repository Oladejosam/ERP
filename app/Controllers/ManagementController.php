<?php
/**
 * Management and portal controller for ERP access routes.
 */
declare(strict_types=1);

require_once APP_ROOT . '/app/Controllers/BaseController.php';
require_once APP_ROOT . '/app/Models/EmployeeModel.php';
require_once APP_ROOT . '/app/Models/UserModel.php';
require_once APP_ROOT . '/app/Models/RoleModel.php';
require_once APP_ROOT . '/app/Models/RequisitionModel.php';

class ManagementController extends BaseController
{
    private EmployeeModel $employeeModel;
    private UserModel $userModel;
    private RoleModel $roleModel;
    private RequisitionModel $requisitionModel;

    public function __construct()
    {
        $this->employeeModel = new EmployeeModel();
        $this->userModel = new UserModel();
        $this->roleModel = new RoleModel();
        $this->requisitionModel = new RequisitionModel();
    }

    public function staffPortal(): void
    {
        $this->requireAccess();
        $this->view('portal/staff', ['title' => 'Staff Portal']);
    }

    public function portalSuperAdmin(): void
    {
        $this->requireAccess();
        $this->view('portal/super-admin', [
            'title' => 'Super Admin Portal',
            'user' => $this->currentUser(),
        ]);
    }

    public function portalAdmin(): void
    {
        $this->requireAccess();
        $this->view('portal/admin', ['title' => 'Admin Portal']);
    }

    public function portalHr(): void
    {
        $this->requireAccess();
        $this->view('portal/hr', ['title' => 'HR Portal']);
    }

    public function portalAccountant(): void
    {
        $this->requireAccess();
        $this->view('portal/accountant', ['title' => 'Accountant Portal']);
    }

    public function portalProcurementOfficer(): void
    {
        $this->requireAccess();
        $this->view('portal/procurement', ['title' => 'Procurement Portal']);
    }

    public function portalManagingDirector(): void
    {
        $this->requireAccess();
        $this->view('portal/managing_director', ['title' => 'Managing Director Portal']);
    }

    public function portalFinanceManager(): void
    {
        $this->requireAccess();
        $this->view('portal/finance_manager', ['title' => 'Finance Manager Portal']);
    }

    public function portalHrManager(): void
    {
        $this->requireAccess();
        $this->view('portal/hr_manager', ['title' => 'HR Manager Portal']);
    }

    public function portalSiteEngineer(): void
    {
        $this->requireAccess();
        $this->view('portal/site_engineer', ['title' => 'Site Engineer Portal']);
    }

    public function portalDepartmentHead(): void
    {
        $this->requireAccess();
        $this->view('portal/department_head', ['title' => 'Department Head Portal']);
    }

    public function portalLogisticsOfficer(): void
    {
        $this->requireAccess();
        $this->view('portal/logistics_officer', ['title' => 'Logistics Portal']);
    }

    public function employees(): void
    {
        $this->requireCompanyModule('employees');
        $search = trim((string)($_GET['search'] ?? ''));
        $employees = $this->employeeModel->getEmployees();

        if ($search !== '') {
            $needle = strtolower($search);
            $employees = array_values(array_filter($employees, function (array $employee) use ($needle): bool {
                $candidate = strtolower(trim((($employee['first_name'] ?? '') . ' ' . ($employee['last_name'] ?? '')) . ' ' . ($employee['employee_code'] ?? '') . ' ' . ($employee['department'] ?? '') . ' ' . ($employee['email'] ?? '') . ' ' . ($employee['position'] ?? '')));
                return strpos($candidate, $needle) !== false;
            }));
        }

        $this->view('management/employees', [
            'title' => 'Employees',
            'employees' => $employees,
            'departments' => $this->employeeModel->getDepartments(),
            'customFields' => $this->employeeModel->getCustomFields(),
            'employeeColumns' => $this->employeeModel->getEmployeeColumnOptions(),
            'search' => $search,
        ]);
    }

    public function viewEmployee(): void
    {
        $this->requireCompanyModule('employees');
        $employeeId = (int)($_GET['id'] ?? 0);
        $employee = $this->employeeModel->getEmployeeById($employeeId);

        if (!$employee) {
            $_SESSION['employee_flash'] = 'Employee not found.';
            $this->redirect('/management/employees');
        }

        $this->view('management/employee_detail', [
            'title' => 'Employee Details',
            'employee' => $employee,
            'departments' => $this->employeeModel->getDepartments(),
            'customFields' => $this->employeeModel->getCustomFieldValues($employeeId),
            'employeeColumns' => $this->employeeModel->getEmployeeColumnOptions(),
        ]);
    }

    public function saveEmployee(): void
    {
        $this->requireCompanyModule('employees');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['employee_flash'] = 'Invalid employee request.';
            $this->redirect('/management/employees');
        }

        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        $password = (string)($_POST['password'] ?? '');
        $roleName = trim((string)($_POST['role'] ?? 'Staff')) ?: 'Staff';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['employee_flash'] = 'A valid employee email is required.';
            $this->redirect('/management/employees');
        }
        if (strlen($password) < 8) {
            $_SESSION['employee_flash'] = 'The login password must be at least 8 characters.';
            $this->redirect('/management/employees');
        }
        if ($this->employeeModel->getEmployeeByEmail($email) !== null || $this->userModel->getUserByEmail($email) !== null) {
            $_SESSION['employee_flash'] = 'An employee or user with this email already exists.';
            $this->redirect('/management/employees');
        }

        $employeeData = [
            'employee_code' => trim((string)($_POST['employee_code'] ?? '')),
            'first_name' => trim((string)($_POST['first_name'] ?? '')),
            'last_name' => trim((string)($_POST['last_name'] ?? '')),
            'email' => $email,
            'phone' => trim((string)($_POST['phone'] ?? '')),
            'department' => trim((string)($_POST['department'] ?? '')),
            'position' => trim((string)($_POST['position'] ?? '')),
            'designation' => trim((string)($_POST['designation'] ?? '')),
            'hire_date' => trim((string)($_POST['hire_date'] ?? date('Y-m-d'))),
            'salary' => (float)($_POST['salary'] ?? 0),
            'status' => trim((string)($_POST['status'] ?? 'active')),
            'nin' => trim((string)($_POST['nin'] ?? '')),
            'account_number' => trim((string)($_POST['account_number'] ?? '')),
            'bank_name' => trim((string)($_POST['bank_name'] ?? '')),
            'tin' => trim((string)($_POST['tin'] ?? '')),
            'pfa' => trim((string)($_POST['pfa'] ?? '')),
        ];

        $profilePicture = $this->handleEmployeePhotoUpload($_FILES['profile_picture'] ?? null);
        if ($profilePicture !== null) {
            $employeeData['profile_picture'] = $profilePicture;
        }

        $employeeId = 0;
        try {
            $employeeId = $this->employeeModel->createEmployee($employeeData);
            $this->employeeModel->saveCustomFieldValues($employeeId, (array)($_POST['custom_fields'] ?? []));
            $roleId = $this->roleModel->createRoleIfMissing($roleName, $roleName . ' access role');
            $this->userModel->createUser([
                'name' => trim($employeeData['first_name'] . ' ' . $employeeData['last_name']),
                'email' => $email,
                'password' => $password,
                'role_id' => $roleId,
                'employee_id' => $employeeId,
            ]);
            $_SESSION['employee_flash'] = 'Employee and login account created successfully.';
            $this->redirect('/management/employees/view?id=' . (int)$employeeId);
        } catch (Throwable $e) {
            if ($employeeId > 0) {
                try {
                    $this->userModel->deleteUserByEmployeeId($employeeId);
                    $this->employeeModel->deleteEmployee($employeeId);
                } catch (Throwable $cleanupException) {
                }
            }
            $_SESSION['employee_flash'] = 'Unable to save employee: ' . $e->getMessage();
            $this->redirect('/management/employees');
        }
    }

    public function updateEmployeePhoto(): void
    {
        $this->requireCompanyModule('employees');
        $employeeId = (int)($_POST['employee_id'] ?? 0);
        $employee = $this->employeeModel->getEmployeeById($employeeId);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$employee) {
            $_SESSION['employee_flash'] = 'Invalid employee photo request.';
            $this->redirect('/management/employees');
        }

        $file = $_FILES['profile_picture'] ?? null;
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $_SESSION['employee_flash'] = 'Please choose a profile picture to upload.';
            $this->redirect('/management/employees/view?id=' . $employeeId);
        }

        $profilePicture = $this->handleEmployeePhotoUpload($file);
        if ($profilePicture === null) {
            $_SESSION['employee_flash'] = 'Profile picture must be a JPG, PNG, or WebP image.';
            $this->redirect('/management/employees/view?id=' . $employeeId);
        }

        $this->employeeModel->updateEmployeeProfilePicture($employeeId, $profilePicture);
        $_SESSION['employee_flash'] = 'Profile picture updated successfully.';
        $this->redirect('/management/employees/view?id=' . $employeeId);
    }

    public function populateEmployeeUsers(): void
    {
        $this->requireCompanyModule('employees');
        $created = $this->userModel->populateUsersFromEmployees();
        $_SESSION['employee_flash'] = $created . ' missing employee user account(s) populated successfully.';
        $this->redirect('/management/employees');
    }

    public function updateEmployee(): void
    {
        $this->requireCompanyModule('employees');
        $employeeId = (int)($_POST['employee_id'] ?? 0);
        $employee = $this->employeeModel->getEmployeeById($employeeId);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$employee) {
            $_SESSION['employee_flash'] = 'Invalid employee update request.';
            $this->redirect('/management/employees');
        }

        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['employee_flash'] = 'A valid employee email is required.';
            $this->redirect('/management/employees/view?id=' . $employeeId);
        }

        try {
            $this->employeeModel->updateEmployee($employeeId, [
                'employee_code' => $_POST['employee_code'] ?? '',
                'first_name' => $_POST['first_name'] ?? '',
                'last_name' => $_POST['last_name'] ?? '',
                'email' => $email,
                'phone' => $_POST['phone'] ?? '',
                'department' => $_POST['department'] ?? '',
                'position' => $_POST['position'] ?? '',
                'designation' => $_POST['designation'] ?? '',
                'hire_date' => $_POST['hire_date'] ?? '',
                'salary' => $_POST['salary'] ?? 0,
                'status' => $_POST['status'] ?? 'active',
                'nin' => $_POST['nin'] ?? '',
                'account_number' => $_POST['account_number'] ?? '',
                'bank_name' => $_POST['bank_name'] ?? '',
                'tin' => $_POST['tin'] ?? '',
                'pfa' => $_POST['pfa'] ?? '',
            ]);
            $this->userModel->updateUserByEmployeeId($employeeId, [
                'name' => trim((string)($_POST['first_name'] ?? '') . ' ' . (string)($_POST['last_name'] ?? '')),
                'email' => $email,
            ], 'Employee profile updated');
            $this->employeeModel->saveCustomFieldValues($employeeId, (array)($_POST['custom_fields'] ?? []));
            $_SESSION['employee_flash'] = 'Employee details updated successfully.';
        } catch (Throwable $exception) {
            $_SESSION['employee_flash'] = 'Unable to update employee: ' . $exception->getMessage();
        }
        $this->redirect('/management/employees/view?id=' . $employeeId);
    }

    public function addEmployeeCustomField(): void
    {
        $this->requireCompanyModule('employees');
        $employeeId = (int)($_POST['employee_id'] ?? 0);
        $employee = $employeeId > 0 ? $this->employeeModel->getEmployeeById($employeeId) : null;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ($employeeId > 0 && !$employee)) {
            $_SESSION['employee_flash'] = 'Invalid employee data column request.';
            $this->redirect('/management/employees');
        }

        try {
            $fieldId = $this->employeeModel->createCustomField((string)($_POST['field_name'] ?? ''));
            if ($employeeId > 0) {
                $this->employeeModel->saveCustomFieldValue($employeeId, $fieldId, (string)($_POST['field_value'] ?? ''));
            }
            $_SESSION['employee_flash'] = 'Employee data column added successfully. Existing employees remain blank.';
        } catch (Throwable $exception) {
            $_SESSION['employee_flash'] = 'Unable to save employee data column: ' . $exception->getMessage();
        }
        $this->redirect($employeeId > 0 ? '/management/employees/view?id=' . $employeeId : '/management/employees');
    }

    public function deleteEmployeeCustomField(): void
    {
        $this->requireCompanyModule('employees');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['employee_flash'] = 'Invalid employee data column request.';
            $this->redirect('/management/employees');
        }

        try {
            $this->employeeModel->disableEmployeeColumn((string)($_POST['column_key'] ?? ''));
            $_SESSION['employee_flash'] = 'Employee column disabled for the current company.';
        } catch (Throwable $exception) {
            $_SESSION['employee_flash'] = 'Unable to disable employee data column: ' . $exception->getMessage();
        }
        $this->redirect('/management/employees');
    }

    public function bulkEmployeeAction(): void
    {
        $this->requireCompanyModule('employees');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['employee_flash'] = 'Invalid employee action request.';
            $this->redirect('/management/employees');
        }

        $action = trim((string)($_POST['action'] ?? ''));
        $employeeIds = array_values(array_unique(array_filter(array_map('intval', (array)($_POST['employee_ids'] ?? [])), static fn (int $id): bool => $id > 0)));
        if ($employeeIds === []) {
            $_SESSION['employee_flash'] = 'Select at least one employee first.';
            $this->redirect('/management/employees');
        }

        $changed = 0;
        foreach ($employeeIds as $employeeId) {
            try {
                if ($action === 'delete') {
                    $employee = $this->employeeModel->getEmployeeById($employeeId);
                    if ($employee) {
                        $this->employeeModel->archiveEmployee($employee);
                    }
                    $this->userModel->deleteUserByEmployeeId($employeeId);
                    $this->employeeModel->deleteEmployee($employeeId);
                } elseif ($action === 'deactivate') {
                    $this->employeeModel->updateEmployeeStatus($employeeId, 'inactive');
                } elseif ($action === 'reactivate') {
                    $this->employeeModel->updateEmployeeStatus($employeeId, 'active');
                } else {
                    throw new InvalidArgumentException('Unknown employee action.');
                }
                $changed++;
            } catch (Throwable $exception) {
                $_SESSION['employee_flash'] = 'Employee action failed: ' . $exception->getMessage();
                $this->redirect('/management/employees');
            }
        }

        $labels = ['delete' => 'deleted', 'deactivate' => 'deactivated', 'reactivate' => 'reactivated'];
        $_SESSION['employee_flash'] = $changed . ' employee(s) ' . ($labels[$action] ?? 'updated') . ' successfully.';
        $this->redirect('/management/employees');
    }

    public function archivedEmployees(): void
    {
        $this->requireCompanyModule('employees');
        $this->view('management/employee_archive', [
            'title' => 'Employee Archive',
            'archivedEmployees' => $this->employeeModel->getArchivedEmployees(),
            'disabledColumns' => $this->employeeModel->getDisabledColumns(),
        ]);
    }

    private function handleEmployeePhotoUpload(?array $file): ?string
    {
        if (!$file || empty($file['name']) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }

        $fileName = basename((string)$file['name']);
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        $detectedType = strtolower((string)($file['type'] ?? ''));

        if (!in_array($detectedType, $allowedTypes, true) && !in_array($extension, $allowedExtensions, true)) {
            return null;
        }

        $uploadDir = APP_ROOT . '/public/uploads/passports';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', $fileName) ?: 'employee_photo_' . time() . '.jpg';
        $storedName = uniqid('emp_', true) . '.' . ($extension !== '' ? $extension : 'jpg');
        $destination = $uploadDir . '/' . $storedName;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            return null;
        }

        return 'passports/' . $storedName;
    }

    public function downloadEmployeeTemplate(): void
    {
        $this->requireAccess();

        $templatePath = APP_ROOT . '/downloads/employee_upload_template.xls';
        if (!is_file($templatePath)) {
            $_SESSION['employee_flash'] = 'Employee upload template is unavailable.';
            $this->redirect('/management/employees');
        }

        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="employee_upload_template.xls"');
        header('Content-Length: ' . (string)filesize($templatePath));
        readfile($templatePath);
        exit;
    }

    public function uploadEmployees(): void
    {
        $this->requireAccess();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['employee_flash'] = 'Invalid employee upload request.';
            $this->redirect('/management/employees');
        }

        $file = $_FILES['employee_file'] ?? null;
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $_SESSION['employee_flash'] = 'Please choose a valid employee template file.';
            $this->redirect('/management/employees');
        }

        $extension = strtolower(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
        if (!in_array($extension, ['csv', 'xls'], true)) {
            $_SESSION['employee_flash'] = 'Upload the provided .xls template or a .csv file. The template is tab-separated text.';
            $this->redirect('/management/employees');
        }

        $handle = fopen((string)$file['tmp_name'], 'rb');
        if ($handle === false) {
            $_SESSION['employee_flash'] = 'The uploaded employee file could not be read.';
            $this->redirect('/management/employees');
        }

        $header = fgetcsv($handle, 0, "\t");
        if ($header === false) {
            fclose($handle);
            $_SESSION['employee_flash'] = 'The employee file is empty.';
            $this->redirect('/management/employees');
        }

        $header = array_map(static function ($value): string {
            $value = preg_replace('/^\xEF\xBB\xBF/', '', (string)$value) ?? (string)$value;
            return strtolower(trim(preg_replace('/[^a-z0-9]+/i', '_', $value) ?? ''));
        }, $header);
        $delimiter = in_array('employee_code', $header, true) ? "\t" : ',';
        if ($delimiter === ',') {
            rewind($handle);
            $header = fgetcsv($handle, 0, ',');
            $header = array_map(static fn ($value): string => strtolower(trim(preg_replace('/[^a-z0-9]+/i', '_', (string)$value) ?? '')), $header ?: []);
        }

        $requiredColumns = ['employee_code', 'first_name', 'last_name', 'email', 'phone', 'department', 'position', 'hire_date', 'salary'];
        $missingColumns = array_values(array_diff($requiredColumns, $header));
        if ($missingColumns !== []) {
            fclose($handle);
            $_SESSION['employee_flash'] = 'Missing template columns: ' . implode(', ', $missingColumns) . '.';
            $this->redirect('/management/employees');
        }

        $columnIndex = array_flip($header);
        $imported = 0;
        $skipped = 0;
        $errors = [];
        $rowNumber = 1;

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rowNumber++;
            if (count(array_filter($row, static fn ($value): bool => trim((string)$value) !== '')) === 0) {
                continue;
            }

            $value = static function (string $column) use ($row, $columnIndex): string {
                return trim((string)($row[$columnIndex[$column]] ?? ''));
            };
            $email = strtolower($value('email'));
            $hireDate = $this->normalizeEmployeeUploadDate($value('hire_date'));
            $invalidFields = [];
            foreach (['employee_code', 'first_name', 'last_name', 'phone', 'department', 'position'] as $requiredField) {
                if ($value($requiredField) === '') {
                    $invalidFields[] = str_replace('_', ' ', $requiredField) . ' is blank';
                }
            }
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $invalidFields[] = 'email is invalid';
            }
            if ($hireDate === null) {
                $invalidFields[] = 'hire date must be YYYY-MM-DD or a valid Excel date';
            }
            if (!is_numeric($value('salary'))) {
                $invalidFields[] = 'salary is not numeric';
            }

            if ($invalidFields !== []) {
                $errors[] = 'Row ' . $rowNumber . ': ' . implode(', ', $invalidFields) . '.';
                continue;
            }

            try {
                $existingCode = $this->employeeModel->getEmployeeByCode($value('employee_code'));
                $existingEmail = $this->employeeModel->getEmployeeByEmail($email);
                if ($existingCode !== null && ($existingEmail === null || (int)$existingCode['id'] !== (int)$existingEmail['id'])) {
                    $errors[] = 'Row ' . $rowNumber . ': employee code already exists.';
                    continue;
                }
                if ($existingEmail !== null) {
                    $skipped++;
                    continue;
                }

                $employeeId = $this->employeeModel->createEmployee([
                    'employee_code' => $value('employee_code'),
                    'first_name' => $value('first_name'),
                    'last_name' => $value('last_name'),
                    'email' => $email,
                    'phone' => $value('phone'),
                    'department' => $value('department'),
                    'position' => $value('position'),
                    'hire_date' => $hireDate,
                    'salary' => (float)$value('salary'),
                ]);

                if ($this->userModel->getUserByEmail($email) === null) {
                    $roleName = $value('role') !== '' ? $value('role') : 'Staff';
                    $roleId = $this->roleModel->createRoleIfMissing($roleName, $roleName . ' access role');
                    $this->userModel->createUser([
                        'name' => trim($value('first_name') . ' ' . $value('last_name')),
                        'email' => $email,
                        'password' => $value('password') !== '' ? $value('password') : 'Welcome123!',
                        'role_id' => $roleId,
                        'employee_id' => $employeeId,
                    ]);
                }
                $imported++;
            } catch (Throwable $exception) {
                $errors[] = 'Row ' . $rowNumber . ': ' . $exception->getMessage();
            }
        }
        fclose($handle);

        $message = 'Employee upload complete. Imported: ' . $imported . '; skipped: ' . $skipped . '; errors: ' . count($errors) . '.';
        if ($errors !== []) {
            $message .= ' ' . implode(' ', array_slice($errors, 0, 5));
        }
        $_SESSION['employee_flash'] = $message;
        $this->redirect('/management/employees');
    }

    private function normalizeEmployeeUploadDate(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        foreach (['!Y-m-d', '!d/m/Y', '!m/d/Y', '!d-m-Y', '!m-d-Y'] as $format) {
            $date = DateTime::createFromFormat($format, $value);
            if ($date !== false && $date->format(str_replace('!', '', $format)) === $value) {
                return $date->format('Y-m-d');
            }
        }

        if (is_numeric($value) && (float)$value >= 1 && (float)$value <= 60000) {
            $date = new DateTime('1899-12-30');
            $date->modify('+' . (int)$value . ' days');
            return $date->format('Y-m-d');
        }

        return null;
    }

    public function hr(): void
    {
        $this->requireCompanyModule('hr');
        $this->view('management/hr', ['title' => 'Human Resources']);
    }

    public function procurement(): void
    {
        $this->requireCompanyModule('procurement');
        $this->view('modules/index', ['title' => 'Procurement']);
    }

    public function requisition(): void
    {
        $this->requireCompanyModule('requisition');
        $this->view('requisition/index', [
            'title' => 'Requisition',
            'requisitions' => $this->requisitionModel->getAll(),
        ]);
    }

    public function saveRequisition(): void
    {
        $this->requireCompanyModule('requisition');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/requisition');
        }

        try {
            $userId = (int)($_SESSION['user']['id'] ?? 0);
            $this->requisitionModel->create(
                $_POST,
                (array)($_POST['items'] ?? []),
                $userId > 0 ? $userId : null
            );
            $_SESSION['requisition_flash'] = 'Requisition submitted successfully.';
        } catch (Throwable $exception) {
            $_SESSION['requisition_flash'] = 'Unable to submit requisition: ' . $exception->getMessage();
        }
        $this->redirect('/requisition');
    }

    public function sales(): void
    {
        $this->requireAccess();
        $this->view('modules/index', ['title' => 'Sales']);
    }
}
