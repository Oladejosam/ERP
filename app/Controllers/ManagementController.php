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
require_once APP_ROOT . '/app/Models/InventoryModel.php';
require_once APP_ROOT . '/app/Models/ProjectModel.php';
require_once APP_ROOT . '/app/Models/CompanyModel.php';
require_once APP_ROOT . '/app/Models/PurchaseOrderModel.php';
require_once APP_ROOT . '/app/Models/PayrollModel.php';

class ManagementController extends BaseController
{
    private EmployeeModel $employeeModel;
    private UserModel $userModel;
    private RoleModel $roleModel;
    private RequisitionModel $requisitionModel;
    private InventoryModel $inventoryModel;
    private ProjectModel $projectModel;
    private CompanyModel $companyModel;
    private PurchaseOrderModel $purchaseOrderModel;
    private PayrollModel $payrollModel;

    public function __construct()
    {
        $this->employeeModel = new EmployeeModel();
        $this->userModel = new UserModel();
        $this->roleModel = new RoleModel();
        $this->requisitionModel = new RequisitionModel();
        $this->inventoryModel = new InventoryModel();
        $this->projectModel = new ProjectModel();
        $this->companyModel = new CompanyModel();
        $this->purchaseOrderModel = new PurchaseOrderModel();
        $this->payrollModel = new PayrollModel();
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

    public function profile(): void
    {
        $this->requireAccess();
        $user = $this->currentUser() ?? [];
        $loginRoleName = $this->roleModel->getRoleNameById((int)($user['role_id'] ?? 0));
        if ($loginRoleName !== '') {
            $user['role_name'] = $loginRoleName;
        }
        $employeeId = (int)($user['employee_id'] ?? 0);
        $employee = $employeeId > 0 ? $this->employeeModel->getEmployeeById($employeeId) : null;
        $this->view('profile', [
            'title' => 'My Profile',
            'user' => $user,
            'employee' => $employee,
        ]);
    }

    public function portalAdmin(): void
    {
        $this->requireAccess();
        $roleName = trim((string)($this->currentUser()['role_name'] ?? ''));
        $this->view('portal/admin', ['title' => $roleName !== '' ? $roleName . ' Portal' : 'Admin Portal']);
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

    public function portalSiteQuantitySurveyor(): void
    {
        $this->requireAccess();
        $this->requireCompanyModule('requisition');
        $employeeId = (int)($_SESSION['user']['employee_id'] ?? 0);
        $projects = $this->projectModel->getQuantitySurveyorProjects($employeeId);
        if ($projects === []) {
            $_SESSION['requisition_flash'] = 'You do not have a Quantity Surveyor assignment on any project site.';
            $this->redirect('/requisition');
        }
        $this->view('portal/site_quantity_surveyor', [
            'title' => 'Site Quantity Surveyor Portal',
            'projects' => $projects,
        ]);
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
            'roles' => $this->roleModel->getRoles(),
            'search' => $search,
        ]);
    }

    public function moduleAccess(): void
    {
        $this->requireAccess();
        $this->requireStaffAccessManager();
        $roles = $this->roleModel->getRoles();
        $departments = $this->employeeModel->getDepartments();
        $assignedRoleIds = [];
        $departmentGroups = [];
        foreach ($departments as $department) {
            $departmentRoles = [];
            foreach ($department['role_ids'] ?? [] as $roleId) {
                $roleId = (int)$roleId;
                $role = null;
                foreach ($roles as $candidate) {
                    if ((int)$candidate['id'] === $roleId) {
                        $role = $candidate;
                        break;
                    }
                }
                if ($role === null) {
                    continue;
                }
                $assignedRoleIds[$roleId] = true;
                $departmentRoles[] = [
                    'id' => $roleId,
                    'name' => (string)$role['name'],
                    'modules' => $this->companyModel->getRoleModuleAccess($roleId),
                ];
            }
            if ($departmentRoles !== []) {
                $departmentGroups[] = [
                    'name' => trim((string)($department['name'] ?? '')) ?: 'Unassigned Department',
                    'roles' => $departmentRoles,
                ];
            }
        }
        $unassignedRoles = [];
        foreach ($roles as $role) {
            $roleId = (int)$role['id'];
            if (!isset($assignedRoleIds[$roleId])) {
                $unassignedRoles[] = [
                    'id' => $roleId,
                    'name' => (string)$role['name'],
                    'modules' => $this->companyModel->getRoleModuleAccess($roleId),
                ];
            }
        }
        $this->view('management/module_access', [
            'title' => 'Role Module Access',
            'departmentGroups' => $departmentGroups,
            'unassignedRoles' => $unassignedRoles,
            'modules' => array_diff_key(CompanyModel::availableModules(), ['dashboard' => true]),
            'access' => $this->companyModel->getRoleModuleAccessMap(),
            'projects' => $this->projectModel->getProjectsForAccessManagement(),
            'projectAccess' => $this->projectModel->getRoleProjectAccessMap(),
        ]);
    }

    public function saveModuleAccess(): void
    {
        $this->requireAccess();
        $this->requireStaffAccessManager();
        try {
            foreach ((array)($_POST['role_modules'] ?? []) as $roleId => $modules) {
                $this->companyModel->saveRoleModuleAccess((int)$roleId, (array)$modules);
            }
            $roleProjects = (array)($_POST['role_projects'] ?? []);
            foreach ((array)($_POST['role_modules'] ?? []) as $roleId => $modules) {
                $this->projectModel->saveRoleProjectAccess((int)$roleId, (array)($roleProjects[$roleId] ?? []));
            }
            $_SESSION['employee_flash'] = 'Role module access saved successfully.';
        } catch (Throwable $exception) {
            $_SESSION['employee_flash'] = 'Unable to save role module access: ' . $exception->getMessage();
        }
        $this->redirect('/management/module-access');
    }

    private function getEmployeeModuleAccessMap(): array
    {
        $access = [];
        foreach ($this->employeeModel->getEmployees() as $employee) {
            $employeeId = (int)$employee['id'];
            if ($this->companyModel->hasEmployeeModuleConfiguration($employeeId)) {
                $access[$employeeId] = $this->companyModel->getEmployeeModuleAccess($employeeId);
            }
        }
        return $access;
    }

    private function requireStaffAccessManager(): array
    {
        $roleName = strtolower(trim((string)($_SESSION['user']['role_name'] ?? '')));
        $managedEmployeeIds = $this->companyModel->getManagedEmployeeIds((int)($_SESSION['user']['employee_id'] ?? 0), (int)($_SESSION['user']['role_id'] ?? 0), $roleName);
        if ($managedEmployeeIds === []) {
            $_SESSION['company_flash'] = 'Only the Head of Human Resource, the top organogram role, or an authorized department head can manage role module access.';
            $this->redirect('/');
        }
        return $managedEmployeeIds;
    }

    public function createRole(): void
    {
        $this->requireCompanyModule('hr');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['hr_flash'] = 'Invalid role request.';
            $this->redirect('/management/hr');
        }

        $name = trim((string)($_POST['name'] ?? ''));
        $description = trim((string)($_POST['description'] ?? ''));
        $submittedNames = preg_split('/\r\n|\r|\n/', (string)($_POST['name'] ?? '')) ?: [];
        $names = [];
        foreach ($submittedNames as $submittedName) {
            $name = trim($submittedName);
            if ($name !== '' && !in_array(strtolower($name), array_map('strtolower', $names), true)) {
                $names[] = $name;
            }
        }
        if ($names === []) {
            $_SESSION['hr_flash'] = 'A role name is required.';
            $this->redirect('/management/hr');
        }

        try {
            $created = 0;
            $existing = 0;
            $companyRoles = $this->roleModel->getRoles();
            $companyRoleNames = array_map(static fn (array $role): string => strtolower((string)$role['name']), $companyRoles);
            foreach ($names as $name) {
                if (in_array(strtolower($name), $companyRoleNames, true)) {
                    $existing++;
                    continue;
                }
                $this->roleModel->createCompanyRole($name, $description);
                $companyRoleNames[] = strtolower($name);
                $created++;
            }
            $_SESSION['hr_flash'] = 'Roles processed. Created: ' . $created . '; already existed: ' . $existing . '.';
        } catch (Throwable $exception) {
            $_SESSION['hr_flash'] = 'Unable to create role: ' . $exception->getMessage();
        }
        $this->redirect('/management/hr');
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
        $employee['role_id'] = (int)(($this->userModel->getUserByEmployeeId($employeeId)['role_id'] ?? 0));

        $this->view('management/employee_detail', [
            'title' => 'Employee Details',
            'employee' => $employee,
            'departments' => $this->employeeModel->getDepartments(),
            'roles' => $this->roleModel->getRoles(),
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
            'account_name' => trim((string)($_POST['account_name'] ?? '')),
            'bank_name' => trim((string)($_POST['bank_name'] ?? '')),
            'tin' => trim((string)($_POST['tin'] ?? '')),
            'pfa' => trim((string)($_POST['pfa'] ?? '')),
        ];
        $positionNames = array_map(static fn (array $role): string => strtolower(trim((string)$role['name'])), $this->roleModel->getRoles());
        if (!in_array(strtolower($employeeData['position']), $positionNames, true)) {
            $_SESSION['employee_flash'] = 'Select a position created in the HR module.';
            $this->redirect('/management/employees');
        }

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
        $this->requireAccess();
        $employeeId = (int)($_POST['employee_id'] ?? 0);
        $currentEmployeeId = (int)($_SESSION['user']['employee_id'] ?? 0);
        $isOwnProfile = $employeeId > 0 && $employeeId === $currentEmployeeId;
        if (!$isOwnProfile) {
            $this->requireCompanyModule('employees');
        }
        $employee = $this->employeeModel->getEmployeeById($employeeId);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$employee) {
            $_SESSION['employee_flash'] = 'Invalid employee photo request.';
            $this->redirect($isOwnProfile ? '/profile' : '/management/employees');
        }

        $file = $_FILES['profile_picture'] ?? null;
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $_SESSION['employee_flash'] = 'Please choose a profile picture to upload.';
            $this->redirect($isOwnProfile ? '/profile' : '/management/employees/view?id=' . $employeeId);
        }

        $profilePicture = $this->handleEmployeePhotoUpload($file);
        if ($profilePicture === null) {
            $_SESSION['employee_flash'] = 'Profile picture must be a JPG, PNG, or WebP image.';
            $this->redirect($isOwnProfile ? '/profile' : '/management/employees/view?id=' . $employeeId);
        }

        $this->employeeModel->updateEmployeeProfilePicture($employeeId, $profilePicture);
        $_SESSION['employee_flash'] = 'Profile picture updated successfully.';
        $this->redirect($isOwnProfile ? '/profile' : '/management/employees/view?id=' . $employeeId);
    }

    public function loginAsEmployee(): void
    {
        $this->requireAccess();
        $roleName = strtolower(trim((string)($_SESSION['user']['role_name'] ?? '')));
        if (!in_array($roleName, ['super admin', 'superadministrator', 'super administrator'], true)) {
            $_SESSION['employee_flash'] = 'Only Super Admins can access an employee account without a password.';
            $this->redirect('/management/employees');
        }

        $employeeId = (int)($_POST['employee_id'] ?? 0);
        $employee = $this->employeeModel->getEmployeeById($employeeId);
        $user = $employee ? $this->userModel->getUserByEmployeeId($employeeId) : null;
        if (!$employee || !$user || strtolower((string)($employee['status'] ?? '')) !== 'active') {
            $_SESSION['employee_flash'] = 'This employee cannot be opened because the account is missing or inactive.';
            $this->redirect('/management/employees');
        }

        $_SESSION['impersonation_admin_user'] = $_SESSION['user'];
        $_SESSION['impersonation_company_id'] = (int)($_SESSION['selected_company_id'] ?? 0);
        $user['role_name'] = $this->roleModel->getRoleNameById((int)($user['role_id'] ?? 0));
        $_SESSION['user'] = $user;
        $this->redirect('/');
    }

    public function changeEmployeePassword(): void
    {
        $this->requireAccess();
        $roleName = strtolower(trim((string)($_SESSION['user']['role_name'] ?? '')));
        $employeeId = (int)($_POST['employee_id'] ?? 0);
        $employee = $this->employeeModel->getEmployeeById($employeeId);
        if (!in_array($roleName, ['super admin', 'superadministrator', 'super administrator'], true)) {
            $_SESSION['employee_flash'] = 'Only Super Admins can change employee login passwords.';
            $this->redirect('/management/employees');
        }
        if (!$employee) {
            $_SESSION['employee_flash'] = 'Employee not found.';
            $this->redirect('/management/employees');
        }

        $password = (string)($_POST['new_password'] ?? '');
        $confirmation = (string)($_POST['confirm_password'] ?? '');
        if (strlen($password) < 8) {
            $_SESSION['employee_flash'] = 'The new password must be at least 8 characters.';
        } elseif ($password !== $confirmation) {
            $_SESSION['employee_flash'] = 'The password confirmation does not match.';
        } elseif (!$this->userModel->updateUserByEmployeeId($employeeId, ['password' => $password], 'Employee login password changed')) {
            $_SESSION['employee_flash'] = 'No linked login account was found for this employee.';
        } else {
            $_SESSION['employee_flash'] = 'Employee login password changed successfully.';
        }
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
        $departmentNames = array_map(static fn (array $department): string => strtolower(trim((string)$department['name'])), $this->employeeModel->getDepartments());
        $roles = $this->roleModel->getRoles();
        $positionNames = array_map(static fn (array $role): string => strtolower(trim((string)$role['name'])), $roles);
        $roleId = (int)($_POST['role_id'] ?? 0);
        $validRoleIds = array_map(static fn (array $role): int => (int)$role['id'], $roles);
        if (!in_array(strtolower(trim((string)($_POST['department'] ?? ''))), $departmentNames, true) || !in_array(strtolower(trim((string)($_POST['position'] ?? ''))), $positionNames, true) || !in_array($roleId, $validRoleIds, true)) {
            $_SESSION['employee_flash'] = 'Select a department and position created in this company.';
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
                'account_name' => $_POST['account_name'] ?? '',
                'bank_name' => $_POST['bank_name'] ?? '',
                'tin' => $_POST['tin'] ?? '',
                'pfa' => $_POST['pfa'] ?? '',
            ]);
            $this->userModel->updateUserByEmployeeId($employeeId, [
                'name' => trim((string)($_POST['first_name'] ?? '') . ' ' . (string)($_POST['last_name'] ?? '')),
                'email' => $email,
                'role_id' => $roleId,
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

        try {
            $templatePath = $this->createEmployeeTemplateXlsx($this->roleModel->getRoles());
        } catch (Throwable $exception) {
            $_SESSION['employee_flash'] = 'Employee upload template is unavailable: ' . $exception->getMessage();
            $this->redirect('/management/employees');
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="employee_upload_template.xlsx"');
        header('Content-Length: ' . (string)filesize($templatePath));
        readfile($templatePath);
        unlink($templatePath);
        exit;
    }

    private function createEmployeeTemplateXlsx(array $roles): string
    {
        if (!class_exists('ZipArchive')) {
            throw new RuntimeException('The PHP ZIP extension is unavailable.');
        }
        $roleNames = array_values(array_unique(array_filter(array_map(static fn (array $role): string => trim((string)($role['name'] ?? '')), $roles))));
        if ($roleNames === []) {
            throw new RuntimeException('Create at least one HR role before downloading the template.');
        }

        $escape = static fn (string $value): string => htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $headers = ['Employee Code', 'First Name', 'Last Name', 'Email', 'Phone', 'Department', 'Position', 'Designation', 'Hire Date', 'Salary', 'Status', 'NIN', 'Account Number', 'Account Name', 'Bank Name', 'TIN', 'PFA', 'Role', 'Password'];
        $rows = [
            ['EMP001', 'Amina', 'Bello', 'amina.bello@example.com', '08031234567', 'Human Resources', $roleNames[0], $roleNames[0], '2026-01-15', '185000', 'active', '12345678901', '0123456789', 'Amina Bello', 'Access Bank', 'TIN001234567', 'Stanbic IBTC Pension', 'Staff', 'Welcome123!'],
        ];
        $cell = static function (string $value, int $column, int $row) use ($escape): string {
            $letters = '';
            while ($column > 0) {
                $remainder = ($column - 1) % 26;
                $letters = chr(65 + $remainder) . $letters;
                $column = intdiv($column - 1, 26);
            }
            return '<c r="' . $letters . $row . '" t="inlineStr"><is><t>' . $escape($value) . '</t></is></c>';
        };
        $sheetRows = '<row r="1">';
        foreach ($headers as $index => $header) {
            $sheetRows .= $cell($header, $index + 1, 1);
        }
        $sheetRows .= '</row>';
        foreach ($rows as $rowIndex => $row) {
            $excelRow = $rowIndex + 2;
            $sheetRows .= '<row r="' . $excelRow . '">';
            foreach ($row as $index => $value) {
                $sheetRows .= $cell((string)$value, $index + 1, $excelRow);
            }
            $sheetRows .= '</row>';
        }
        $roleRows = '';
        foreach ($roleNames as $index => $roleName) {
            $roleRows .= '<row r="' . ($index + 1) . '">' . $cell($roleName, 1, $index + 1) . '</row>';
        }
        $roleCount = count($roleNames);
        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/worksheets/sheet2.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>';
        $rootRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>';
        $workbookRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet2.xml"/><Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>';
        $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Employees" sheetId="1" r:id="rId1"/><sheet name="Roles" sheetId="2" state="hidden" r:id="rId2"/></sheets></workbook>';
        $sheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><dimension ref="A1:S' . (count($rows) + 1) . '"/><sheetData>' . $sheetRows . '</sheetData><dataValidations count="1"><dataValidation type="list" allowBlank="false" sqref="G2:G1000"><formula1>Roles!$A$1:$A$' . $roleCount . '</formula1></dataValidation></dataValidations></worksheet>';
        $rolesSheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><dimension ref="A1:A' . $roleCount . '"/><sheetData>' . $roleRows . '</sheetData></worksheet>';
        $styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="1"><font><sz val="11"/><name val="Calibri"/></font></fonts><fills count="1"><fill><patternFill patternType="none"/></fill></fills><borders count="1"><border/></borders><cellStyleXfs count="1"><xf/></cellStyleXfs><cellXfs count="1"><xf/></cellXfs></styleSheet>';
        $path = tempnam(sys_get_temp_dir(), 'employee_template_');
        $archive = new ZipArchive();
        if ($path === false || $archive->open($path, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Unable to create the Excel template.');
        }
        foreach (['[Content_Types].xml' => $contentTypes, '_rels/.rels' => $rootRels, 'xl/_rels/workbook.xml.rels' => $workbookRels, 'xl/workbook.xml' => $workbook, 'xl/worksheets/sheet1.xml' => $sheet, 'xl/worksheets/sheet2.xml' => $rolesSheet, 'xl/styles.xml' => $styles] as $name => $content) {
            $archive->addFromString($name, $content);
        }
        $archive->close();
        return $path;
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
        if (!in_array($extension, ['csv', 'xls', 'xlsx'], true)) {
            $_SESSION['employee_flash'] = 'Upload the provided .xls, .xlsx, or .csv employee template.';
            $this->redirect('/management/employees');
        }

        if ($extension === 'xlsx') {
            try {
                $rows = $this->readXlsxRows((string)$file['tmp_name']);
            } catch (Throwable $exception) {
                $_SESSION['employee_flash'] = 'The Excel file could not be read. Save it as .xlsx, .xls, or CSV and try again.';
                $this->redirect('/management/employees');
            }
            $delimiter = null;
        } else {
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
            $rows = [];
            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                $rows[] = $row;
            }
            fclose($handle);
        }

        if ($extension === 'xlsx') {
            $header = array_map(static function ($value): string {
                $value = preg_replace('/^\xEF\xBB\xBF/', '', (string)$value) ?? (string)$value;
                return strtolower(trim(preg_replace('/[^a-z0-9]+/i', '_', $value) ?? ''));
            }, $rows[0] ?? []);
            $rows = array_slice($rows, 1);
        }

        $requiredColumns = ['employee_code', 'first_name', 'last_name', 'email', 'phone', 'department', 'position', 'hire_date', 'salary'];
        $missingColumns = array_values(array_diff($requiredColumns, $header));
        if ($missingColumns !== []) {
            $_SESSION['employee_flash'] = 'Missing template columns: ' . implode(', ', $missingColumns) . '.';
            $this->redirect('/management/employees');
        }

        $columnIndex = array_flip($header);
        $positionNames = array_map(static fn (array $role): string => strtolower(trim((string)$role['name'])), $this->roleModel->getRoles());
        $imported = 0;
        $skipped = 0;
        $errors = [];
        $rowNumber = 1;

        foreach ($rows as $row) {
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
            if (!in_array(strtolower($value('position')), $positionNames, true)) {
                $invalidFields[] = 'position must be an HR role';
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
                    'designation' => $value('designation'),
                    'hire_date' => $hireDate,
                    'salary' => (float)$value('salary'),
                    'status' => $value('status') !== '' ? $value('status') : 'active',
                    'nin' => $value('nin'),
                    'account_number' => $value('account_number'),
                    'account_name' => $value('account_name'),
                    'bank_name' => $value('bank_name'),
                    'tin' => $value('tin'),
                    'pfa' => $value('pfa'),
                ]);

                if ($this->userModel->getUserByEmail($email) === null) {
                    $roleId = $this->userModel->resolveEmployeeRoleId([
                        'designation' => $value('designation'),
                        'position' => $value('position'),
                    ], $this->roleModel);
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
        $message = 'Employee upload complete. Imported: ' . $imported . '; skipped: ' . $skipped . '; errors: ' . count($errors) . '.';
        if ($errors !== []) {
            $message .= ' ' . implode(' ', array_slice($errors, 0, 5));
        }
        $_SESSION['employee_flash'] = $message;
        $this->redirect('/management/employees');
    }

    private function readXlsxRows(string $path): array
    {
        if (!class_exists('ZipArchive')) {
            throw new RuntimeException('The PHP ZIP extension is unavailable.');
        }

        $archive = new ZipArchive();
        if ($archive->open($path) !== true) {
            throw new RuntimeException('Invalid XLSX archive.');
        }

        $sharedStrings = [];
        $sharedXml = $archive->getFromName('xl/sharedStrings.xml');
        if ($sharedXml !== false) {
            $shared = simplexml_load_string($sharedXml);
            if ($shared !== false) {
                foreach ($shared->si as $stringItem) {
                    $sharedStrings[] = implode('', array_map('strval', iterator_to_array($stringItem->xpath('.//*[local-name()="t"]') ?: [])));
                }
            }
        }

        $sheetXml = $archive->getFromName('xl/worksheets/sheet1.xml');
        $archive->close();
        if ($sheetXml === false) {
            throw new RuntimeException('Worksheet not found.');
        }

        $sheet = simplexml_load_string($sheetXml);
        if ($sheet === false) {
            throw new RuntimeException('Invalid worksheet XML.');
        }

        $rows = [];
        foreach ($sheet->xpath('//*[local-name()="row"]') ?: [] as $xmlRow) {
            $values = [];
            foreach ($xmlRow->xpath('./*[local-name()="c"]') ?: [] as $cell) {
                $reference = (string)$cell['r'];
                preg_match('/^[A-Z]+/i', $reference, $columnMatch);
                $column = 0;
                foreach (str_split(strtoupper($columnMatch[0] ?? 'A')) as $letter) {
                    $column = ($column * 26) + ord($letter) - 64;
                }
                $type = (string)$cell['t'];
                $value = (string)$cell->v;
                if ($type === 's') {
                    $value = $sharedStrings[(int)$value] ?? '';
                } elseif ($type === 'inlineStr') {
                    $value = implode('', array_map('strval', $cell->xpath('.//*[local-name()="t"]') ?: []));
                }
                $values[$column - 1] = $value;
            }
            if ($values !== []) {
                ksort($values);
                $normalizedValues = array_fill(0, max(array_keys($values)) + 1, '');
                foreach ($values as $column => $value) {
                    $normalizedValues[$column] = $value;
                }
                $rows[] = $normalizedValues;
            }
        }
        return $rows;
    }

    private function normalizeEmployeeUploadDate(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        if (is_numeric($value) && (float)$value >= 1 && (float)$value <= 60000) {
            $date = new DateTimeImmutable('1899-12-30');
            return $date->modify('+' . (int)floor((float)$value) . ' days')->format('Y-m-d');
        }

        foreach (['Y-m-d', 'Y/m/d', 'd/m/Y', 'm/d/Y', 'd-m-Y', 'm-d-Y', 'd.m.Y', 'm.d.Y', 'Y-m-d H:i:s', 'd/m/Y H:i:s', 'm/d/Y H:i:s', 'd-m-Y H:i:s', 'm-d-Y H:i:s', 'd M Y', 'M d, Y'] as $format) {
            $date = DateTimeImmutable::createFromFormat('!' . $format, $value);
            $errors = DateTimeImmutable::getLastErrors();
            if ($date !== false && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))) {
                return $date->format('Y-m-d');
            }
        }

        return null;
    }

    public function hr(): void
    {
        $this->requireCompanyModule('hr');
        $this->view('management/hr', [
            'title' => 'Human Resources',
            'departments' => $this->employeeModel->getDepartments(),
            'employees' => $this->employeeModel->getEmployees(),
            'roles' => $this->roleModel->getRoles(),
            'managementRoles' => $this->roleModel->getManagementRoles(),
        ]);
    }

    public function deleteRole(): void
    {
        $this->requireCompanyModule('hr');
        try {
            $this->roleModel->deleteRole((int)($_POST['role_id'] ?? 0));
            $_SESSION['hr_flash'] = 'Role deleted successfully.';
        } catch (Throwable $exception) {
            $_SESSION['hr_flash'] = 'Unable to delete role: ' . $exception->getMessage();
        }
        $this->redirect('/management/hr');
    }

    public function deleteManagementRole(): void
    {
        $this->requireCompanyModule('hr');
        try {
            $this->roleModel->deleteManagementRole((int)($_POST['management_role_id'] ?? 0));
            $_SESSION['hr_flash'] = 'Management role deleted successfully.';
        } catch (Throwable $exception) {
            $_SESSION['hr_flash'] = 'Unable to delete management role: ' . $exception->getMessage();
        }
        $this->redirect('/management/hr');
    }

    public function saveDepartment(): void
    {
        $this->requireCompanyModule('hr');
        try {
            $roleIds = (array)($_POST['role_ids'] ?? []);
            $headRoleId = (int)($_POST['head_role_id'] ?? 0);
            $headEmployeeId = (int)($_POST['head_employee_id'] ?? 0);
            $headTitle = trim((string)($_POST['head_title'] ?? ''));
            $this->employeeModel->createDepartment((string)($_POST['name'] ?? ''), $roleIds, $headRoleId > 0 ? $headRoleId : null, $headEmployeeId > 0 ? $headEmployeeId : null, $headTitle !== '' ? $headTitle : null);
            $_SESSION['hr_flash'] = 'Department created successfully.';
        } catch (Throwable $exception) {
            $_SESSION['hr_flash'] = 'Unable to create department: ' . $exception->getMessage();
        }
        $this->redirect('/management/hr');
    }

    public function updateDepartmentAssignments(): void
    {
        $this->requireCompanyModule('hr');
        try {
            $roleIds = (array)($_POST['role_ids'] ?? []);
            $headRoleId = (int)($_POST['head_role_id'] ?? 0);
            $headEmployeeId = (int)($_POST['head_employee_id'] ?? 0);
            $headTitle = trim((string)($_POST['head_title'] ?? ''));
            $this->employeeModel->updateDepartmentAssignments((int)($_POST['department_id'] ?? 0), $roleIds, $headRoleId > 0 ? $headRoleId : null, $headEmployeeId > 0 ? $headEmployeeId : null, $headTitle !== '' ? $headTitle : null);
            $_SESSION['hr_flash'] = 'Department assignments updated successfully.';
        } catch (Throwable $exception) {
            $_SESSION['hr_flash'] = 'Unable to update department assignments: ' . $exception->getMessage();
        }
        $this->redirect('/management/hr');
    }

    public function deleteDepartment(): void
    {
        $this->requireCompanyModule('hr');
        try {
            $this->employeeModel->deleteDepartment((int)($_POST['department_id'] ?? 0));
            $_SESSION['hr_flash'] = 'Department deleted successfully.';
        } catch (Throwable $exception) {
            $_SESSION['hr_flash'] = 'Unable to delete department: ' . $exception->getMessage();
        }
        $this->redirect('/management/hr');
    }

    public function procurement(): void
    {
        $this->requireCompanyModule('procurement');
        $roleName = strtolower(trim((string)($_SESSION['user']['role_name'] ?? '')));
        $currentRoleName = $this->roleModel->getRoleNameById((int)($_SESSION['user']['role_id'] ?? 0));
        if ($currentRoleName !== '') {
            $roleName = strtolower(trim($currentRoleName));
            $_SESSION['user']['role_name'] = $currentRoleName;
        }
        $isSuperAdmin = in_array($roleName, ['super admin', 'superadministrator', 'super administrator'], true);
        $isLogisticsOfficer = strpos($roleName, 'logistics') !== false;
        $isProcurementOfficer = strpos($roleName, 'procurement') !== false;
        $isStore = $this->isStoreDepartmentPersonnel();
        $isHeadStore = in_array($roleName, ['head store keeper', 'head store', 'hod store', 'hod of store', 'head of store'], true);
        $isLogistics = $this->isDepartmentPersonnel('logistics');
        $this->view('management/procurement', [
            'title' => 'Procurement',
            'suppliers' => $this->purchaseOrderModel->getSuppliers(),
            'projects' => $this->purchaseOrderModel->getProjects(),
            'pendingOrders' => $isHeadStore || $isSuperAdmin ? $this->purchaseOrderModel->getWorkflowOrders('pending_head_approval') : ($isProcurementOfficer ? $this->purchaseOrderModel->getWorkflowOrders('pending_procurement') : []),
            'flaggedOrders' => $isHeadStore || $isProcurementOfficer || $isSuperAdmin ? $this->purchaseOrderModel->getWorkflowOrders('flagged') : [],
            'logisticsOrders' => $isLogistics || $isSuperAdmin ? $this->purchaseOrderModel->getWorkflowOrders('sent_to_logistics') : [],
            'procurementRequests' => $isProcurementOfficer || $isSuperAdmin || $isHeadStore ? $this->purchaseOrderModel->getProcurementRequests() : [],
            'canInitiatePurchaseOrder' => $isStore || $isSuperAdmin,
            'canApprovePurchaseOrder' => $isHeadStore || $isProcurementOfficer || $isSuperAdmin,
            'canReceivePurchaseOrder' => $isLogistics || $isSuperAdmin,
            'purchaseOrderDirectToProcurement' => $isHeadStore || $isSuperAdmin,
        ]);
    }

    public function uploadVendorInvoice(): void
    {
        $this->requireCompanyModule('procurement');
        $sourceType = trim((string)($_POST['request_source'] ?? ''));
        $sourceId = (int)($_POST['source_id'] ?? 0);
        try {
            $this->purchaseOrderModel->saveProcurementRequestInvoice($sourceType, $sourceId, (string)($_POST['vendor_name'] ?? ''), (string)($_POST['invoice_number'] ?? ''), (string)($_POST['invoice_label'] ?? 'Vendor Invoice'));
            $_SESSION['procurement_flash'] = 'Vendor invoice uploaded and added to the procurement review list.';
        } catch (Throwable $exception) {
            $_SESSION['procurement_flash'] = 'Unable to upload invoice: ' . $exception->getMessage();
        }
        $this->redirect('/management/procurement');
    }

    public function selectVendorInvoice(): void
    {
        $this->requireCompanyModule('procurement');
        $invoiceId = (int)($_POST['invoice_id'] ?? 0);
        try {
            $this->purchaseOrderModel->approveProcurementInvoice($invoiceId);
            $_SESSION['procurement_flash'] = 'Vendor invoice marked as the selected invoice for this request.';
        } catch (Throwable $exception) {
            $_SESSION['procurement_flash'] = 'Unable to select invoice: ' . $exception->getMessage();
        }
        $this->redirect('/management/procurement');
    }

    public function createPurchaseOrderFromSelectedInvoice(): void
    {
        $this->requireCompanyModule('procurement');
        $invoiceId = (int)($_POST['invoice_id'] ?? 0);
        try {
            $poId = $this->purchaseOrderModel->createPurchaseOrderFromSelectedInvoice($invoiceId, (int)($_SESSION['user']['id'] ?? 0), $_POST);
            $_SESSION['procurement_flash'] = 'Purchase order created from the approved vendor invoice. PO #' . rawurlencode((string)$this->purchaseOrderModel->getPurchaseOrderById($poId)['po_number'] ?? '');
        } catch (Throwable $exception) {
            $_SESSION['procurement_flash'] = 'Unable to create purchase order from selected invoice: ' . $exception->getMessage();
        }
        $this->redirect('/management/procurement');
    }

    public function createStorePurchaseOrder(): void
    {
        $this->requireCompanyModule('procurement');
        if (!$this->isStoreDepartmentPersonnel()) {
            $_SESSION['procurement_flash'] = 'Only Store department personnel can initiate purchase orders.';
            $this->redirect('/management/procurement');
        }
        try {
            $roleName = strtolower(trim((string)($_SESSION['user']['role_name'] ?? '')));
            $isHeadStore = in_array($roleName, ['head store keeper', 'head store', 'hod store', 'hod of store', 'head of store'], true);
            $isDirectToProcurement = $isHeadStore || in_array($roleName, ['super admin', 'superadministrator', 'super administrator'], true);
            $initialStatus = $isDirectToProcurement ? 'pending_procurement' : 'pending_head_approval';
            $this->purchaseOrderModel->createWorkflowOrder($_POST, (int)($_SESSION['user']['id'] ?? 0), $initialStatus);
            $_SESSION['procurement_flash'] = $isHeadStore
                ? 'Purchase order sent directly to the Procurement Officer for review.'
                : 'Purchase order sent to the Head Store Keeper for approval.';
        } catch (Throwable $exception) {
            $_SESSION['procurement_flash'] = 'Unable to initiate purchase order: ' . $exception->getMessage();
        }
        $this->redirect('/management/procurement');
    }

    public function decideStorePurchaseOrder(): void
    {
        $this->requireCompanyModule('procurement');
        $roleName = strtolower(trim((string)($_SESSION['user']['role_name'] ?? '')));
        if (!in_array($roleName, ['head store keeper', 'head store', 'hod store', 'hod of store', 'head of store', 'procurement officer', 'procurement', 'super admin', 'superadministrator', 'super administrator'], true) && strpos($roleName, 'procurement') === false) {
            $_SESSION['procurement_flash'] = 'Only the Head Store Keeper or Procurement Officer can decide on purchase orders.';
            $this->redirect('/management/procurement');
        }
        try {
            $isHeadStoreReviewer = in_array($roleName, ['head store keeper', 'head store', 'hod store', 'hod of store', 'head of store'], true);
            $this->purchaseOrderModel->decideWorkflowOrder((int)($_POST['purchase_order_id'] ?? 0), (int)($_SESSION['user']['id'] ?? 0), (string)($_POST['decision'] ?? ''), (string)($_POST['reason'] ?? ''), $isHeadStoreReviewer);
            $_SESSION['procurement_flash'] = 'Purchase order decision recorded.';
        } catch (Throwable $exception) {
            $_SESSION['procurement_flash'] = 'Unable to decide purchase order: ' . $exception->getMessage();
        }
        $this->redirect('/management/procurement');
    }

    public function receivePurchaseOrderForm(): void
    {
        $this->requireCompanyModule('procurement');
        $order = $this->purchaseOrderModel->getReceivablePurchaseOrder((int)($_GET['id'] ?? 0));
        if (!$order) {
            $_SESSION['procurement_flash'] = 'Purchase order is not available for receiving.';
            $this->redirect('/management/procurement');
        }
        $this->view('management/purchase_order_receive', [
            'title' => 'Receive Purchase Order',
            'order' => $order,
            'inventoryItems' => $this->inventoryModel->getItems(),
        ]);
    }

    public function receivePurchaseOrder(): void
    {
        $this->requireCompanyModule('procurement');
        $orderId = (int)($_POST['purchase_order_id'] ?? 0);
        try {
            $receiptId = $this->purchaseOrderModel->receivePurchaseOrder($orderId, (int)($_SESSION['user']['id'] ?? 0), (array)($_POST['items'] ?? []));
            $_SESSION['procurement_flash'] = 'Goods received and inventory updated.';
            $this->redirect('/management/procurement/receipt?id=' . $receiptId);
        } catch (Throwable $exception) {
            $_SESSION['procurement_flash'] = 'Unable to receive purchase order: ' . $exception->getMessage();
            $this->redirect('/management/procurement/receive?id=' . $orderId);
        }
    }

    public function printInventoryReceipt(): void
    {
        $this->requireCompanyModule('procurement');
        $receipt = $this->purchaseOrderModel->getReceipt((int)($_GET['id'] ?? 0));
        if (!$receipt) {
            $_SESSION['procurement_flash'] = 'Receiving order not found.';
            $this->redirect('/management/procurement');
        }
        $this->view('management/inventory_receipt', ['title' => 'Goods Received Note', 'receipt' => $receipt]);
    }

    private function isDepartmentPersonnel(string $departmentName): bool
    {
        $employeeId = (int)($_SESSION['user']['employee_id'] ?? 0);
        $employee = $employeeId > 0 ? $this->employeeModel->getEmployeeById($employeeId) : null;
        return $employee !== null && strpos(strtolower(trim((string)($employee['department'] ?? ''))), strtolower($departmentName)) !== false;
    }

    public function requisition(): void
    {
        $this->requireCompanyModule('requisition');
        $roleName = strtolower(trim((string)($_SESSION['user']['role_name'] ?? '')));
        $isSuperAdmin = in_array($roleName, ['super admin', 'superadministrator', 'super administrator'], true);
        $isStorePersonnel = $this->isStoreDepartmentPersonnel();
        $employeeId = (int)($_SESSION['user']['employee_id'] ?? 0);
        $quantitySurveyorProjects = $this->projectModel->getQuantitySurveyorProjects($employeeId);
        $isSiteQuantitySurveyor = $quantitySurveyorProjects !== [];
        $this->view('requisition/index', [
            'title' => 'Requisition',
            'requisitions' => $this->requisitionModel->getAll((int)($_SESSION['user']['id'] ?? 0), $isSuperAdmin),
            'companyUsers' => $this->requisitionModel->getCompanyUsers(),
            'projects' => $isSiteQuantitySurveyor ? $quantitySurveyorProjects : $this->projectModel->getProjects(),
            'isSiteQuantitySurveyor' => $isSiteQuantitySurveyor,
            'dispatchRequests' => $isStorePersonnel || $isSuperAdmin ? $this->requisitionModel->getPendingDispatchRequests() : [],
            'isStoreApprover' => $isStorePersonnel || $isSuperAdmin,
            'requisitionFields' => $this->requisitionModel->getFormFields(),
        ]);
    }

    public function approveDispatch(): void
    {
        $this->requireCompanyModule('requisition');
        $roleName = strtolower(trim((string)($_SESSION['user']['role_name'] ?? '')));
        if (!$this->isStoreDepartmentPersonnel()) {
            $_SESSION['requisition_flash'] = 'Only personnel in the Store department can process material dispatches.';
            $this->redirect('/requisition');
        }
        try {
            $this->requisitionModel->approveDispatch((int)($_POST['dispatch_id'] ?? 0), (int)($_SESSION['user']['id'] ?? 0));
            $_SESSION['requisition_flash'] = 'Dispatch approved and inventory stock deducted.';
        } catch (Throwable $exception) {
            $_SESSION['requisition_flash'] = 'Unable to approve dispatch: ' . $exception->getMessage();
        }
        $this->redirect('/requisition');
    }

    public function decideDispatch(): void
    {
        $this->requireCompanyModule('requisition');
        $roleName = strtolower(trim((string)($_SESSION['user']['role_name'] ?? '')));
        if (!$this->isStoreDepartmentPersonnel()) {
            $_SESSION['requisition_flash'] = 'Only personnel in the Store department can process material dispatches.';
            $this->redirect('/requisition');
        }
        try {
            $deliveryNoteId = $this->requisitionModel->decideDispatch(
                (int)($_POST['dispatch_id'] ?? 0),
                (int)($_SESSION['user']['id'] ?? 0),
                (string)($_POST['stock_type'] ?? ''),
                !empty($_POST['urgent']),
                (string)($_POST['issued_to'] ?? '')
            );
            $_SESSION['requisition_flash'] = 'Store decision recorded successfully.';
            $_SESSION['delivery_note_id'] = $deliveryNoteId;
        } catch (Throwable $exception) {
            $_SESSION['requisition_flash'] = 'Unable to process store request: ' . $exception->getMessage();
        }
        $this->redirect('/requisition');
    }

    public function printDeliveryNote(): void
    {
        $this->requireCompanyModule('requisition');
        $note = $this->requisitionModel->getDeliveryNote((int)($_GET['id'] ?? 0));
        if (!$note) {
            $_SESSION['requisition_flash'] = 'Delivery note not found.';
            $this->redirect('/requisition');
        }
        $this->view('requisition/delivery_note', ['title' => 'Delivery Note', 'note' => $note]);
    }

    public function decideRequisitionHandoff(): void
    {
        $this->requireCompanyModule('requisition');
        $requisitionId = (int)($_POST['requisition_id'] ?? 0);
        try {
            $this->requisitionModel->decideHandoff($requisitionId, (int)($_SESSION['user']['id'] ?? 0), (string)($_POST['decision'] ?? ''), (string)($_POST['reason'] ?? ''));
            $_SESSION['requisition_flash'] = 'Forwarded requisition decision saved.';
        } catch (Throwable $exception) {
            $_SESSION['requisition_flash'] = 'Unable to save forwarded requisition decision: ' . $exception->getMessage();
        }
        $this->redirect('/requisition/view?id=' . $requisitionId);
    }

    private function isStoreDepartmentPersonnel(): bool
    {
        $roleName = strtolower(trim((string)($_SESSION['user']['role_name'] ?? '')));
        if (in_array($roleName, ['super admin', 'superadministrator', 'super administrator'], true)) {
            return true;
        }
        $employeeId = (int)($_SESSION['user']['employee_id'] ?? 0);
        $employee = $employeeId > 0 ? $this->employeeModel->getEmployeeById($employeeId) : null;
        return $employee !== null && strpos(strtolower(trim((string)($employee['department'] ?? ''))), 'store') !== false;
    }

    public function saveRequisition(): void
    {
        $this->requireCompanyModule('requisition');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/requisition');
        }

        try {
            $userId = (int)($_SESSION['user']['id'] ?? 0);
            $projectId = (int)($_POST['project_id'] ?? 0);
            if ($projectId > 0 && !$this->projectModel->isQuantitySurveyorForProject((int)($_SESSION['user']['employee_id'] ?? 0), $projectId)) {
                throw new InvalidArgumentException('You can only raise a site requisition for a project assigned to you as Quantity Surveyor.');
            }
            $isAssignedQuantitySurveyor = $this->projectModel->getQuantitySurveyorProjects((int)($_SESSION['user']['employee_id'] ?? 0)) !== [];
            if ($isAssignedQuantitySurveyor && $projectId <= 0) {
                throw new InvalidArgumentException('Select the project site for this requisition.');
            }
            $this->requisitionModel->create(
                array_merge($_POST, ['participant_ids' => (array)($_POST['participant_ids'] ?? [])]),
                $userId > 0 ? $userId : null
            );
            $_SESSION['requisition_flash'] = 'Requisition submitted successfully.';
        } catch (Throwable $exception) {
            $_SESSION['requisition_flash'] = 'Unable to submit requisition: ' . $exception->getMessage();
        }
        $this->redirect('/requisition');
    }

    public function searchInventoryItems(): void
    {
        $this->requireCompanyModule('requisition');
        $this->json([
            'items' => $this->inventoryModel->searchItems((string)($_GET['q'] ?? '')),
        ]);
    }

    public function viewRequisition(): void
    {
        $this->requireCompanyModule('requisition');
        $requisition = $this->requisitionModel->getById((int)($_GET['id'] ?? 0));
        if (!$requisition) {
            $_SESSION['requisition_flash'] = 'Requisition not found.';
            $this->redirect('/requisition');
        }
        $roleName = strtolower(trim((string)($_SESSION['user']['role_name'] ?? '')));
        $isSuperAdmin = in_array($roleName, ['super admin', 'superadministrator', 'super administrator'], true);
        $isLogisticsOfficer = strpos($roleName, 'logistics') !== false;
        $isHeadStore = in_array($roleName, ['head store keeper', 'head store', 'hod store', 'hod of store', 'head of store'], true);
        if (!$isSuperAdmin && !$this->requisitionModel->isParticipant((int)$requisition['id'], (int)($_SESSION['user']['id'] ?? 0))) {
            $_SESSION['requisition_flash'] = 'You are not part of this requisition discussion.';
            $this->redirect('/requisition');
        }
        $this->view('requisition/detail', ['title' => 'Requisition Discussion', 'requisition' => $requisition, 'companyUsers' => $this->requisitionModel->getCompanyUsers(), 'isLogisticsOfficer' => $isLogisticsOfficer || $isSuperAdmin, 'isHeadStoreReviewer' => $isHeadStore || $isSuperAdmin]);
    }

    public function reviewRequisitionInventory(): void
    {
        $this->requireCompanyModule('requisition');
        $requisitionId = (int)($_POST['requisition_id'] ?? 0);
        try {
            $this->requisitionModel->reviewInventory($requisitionId, (int)($_SESSION['user']['id'] ?? 0), (array)($_POST['items'] ?? []));
            $_SESSION['requisition_flash'] = 'Inventory quantities and purchase requirements updated.';
        } catch (Throwable $exception) {
            $_SESSION['requisition_flash'] = 'Unable to complete inventory review: ' . $exception->getMessage();
        }
        $this->redirect('/requisition/view?id=' . $requisitionId);
    }

    public function adjustRequisitionItemQuantity(): void
    {
        $this->requireCompanyModule('requisition');
        $requisitionId = (int)($_POST['requisition_id'] ?? 0);
        $itemId = (int)($_POST['item_id'] ?? 0);
        $delta = (float)($_POST['delta'] ?? 0);
        $direction = strtolower(trim((string)($_POST['direction'] ?? '')));
        if ($direction === 'decrease') {
            $delta = -abs($delta);
        } elseif ($direction === 'increase') {
            $delta = abs($delta);
        }

        try {
            $this->requisitionModel->adjustItemQuantity($requisitionId, $itemId, (int)($_SESSION['user']['id'] ?? 0), $delta, (string)($_POST['reason'] ?? ''));
            $_SESSION['requisition_flash'] = 'Requested quantity updated successfully.';
        } catch (Throwable $exception) {
            $_SESSION['requisition_flash'] = 'Unable to update quantity: ' . $exception->getMessage();
        }
        $this->redirect('/requisition/view?id=' . $requisitionId);
    }

    public function addRequisitionMessage(): void
    {
        $this->requireCompanyModule('requisition');
        try {
            $this->requisitionModel->addMessage((int)($_POST['requisition_id'] ?? 0), (int)($_SESSION['user']['id'] ?? 0), (string)($_POST['message'] ?? ''));
            $_SESSION['requisition_flash'] = 'Message posted.';
        } catch (Throwable $exception) {
            $_SESSION['requisition_flash'] = 'Unable to post message: ' . $exception->getMessage();
        }
        $this->redirect('/requisition/view?id=' . (int)($_POST['requisition_id'] ?? 0));
    }

    public function requisitionMessages(): void
    {
        $this->requireCompanyModule('requisition');
        $requisitionId = (int)($_GET['id'] ?? 0);
        if (!$this->requisitionModel->isParticipant($requisitionId, (int)($_SESSION['user']['id'] ?? 0))) {
            $this->json(['error' => 'Not authorized.']);
        }
        $this->json([
            'messages' => $this->requisitionModel->getMessages($requisitionId),
        ]);
    }

    public function decideRequisition(): void
    {
        $this->requireCompanyModule('requisition');
        $requisitionId = (int)($_POST['requisition_id'] ?? 0);
        try {
            $this->requisitionModel->decide($requisitionId, (int)($_SESSION['user']['id'] ?? 0), (string)($_POST['decision'] ?? ''));
            $_SESSION['requisition_flash'] = 'Requisition decision saved.';
        } catch (Throwable $exception) {
            $_SESSION['requisition_flash'] = 'Unable to save decision: ' . $exception->getMessage();
        }
        $this->redirect('/requisition/view?id=' . $requisitionId);
    }

    public function handoffRequisition(): void
    {
        $this->requireCompanyModule('requisition');
        $requisitionId = (int)($_POST['requisition_id'] ?? 0);
        try {
            $this->requisitionModel->handoff($requisitionId, (int)($_SESSION['user']['id'] ?? 0), (int)($_POST['next_user_id'] ?? 0), (string)($_POST['handoff_note'] ?? ''));
            $_SESSION['requisition_flash'] = 'Requisition handed off successfully.';
        } catch (Throwable $exception) {
            $_SESSION['requisition_flash'] = 'Unable to hand off requisition: ' . $exception->getMessage();
        }
        $this->redirect('/requisition/view?id=' . $requisitionId);
    }

    public function sales(): void
    {
        $this->requireAccess();
        $this->view('modules/index', ['title' => 'Sales']);
    }

    // ========== SALARY STRUCTURE MANAGEMENT ==========

    public function manageSalaryStructures(): void
    {
        $this->requireCompanyModule('salary_structure');

        $userRoleId = (int)($_SESSION['user']['role_id'] ?? 0);
        $userEmployeeId = (int)($_SESSION['user']['employee_id'] ?? 0);
        $companyModel = new CompanyModel();
        $roleName = strtolower(trim((string)($_SESSION['user']['role_name'] ?? '')));
        $canManageSalaryStructure = in_array($roleName, ['super admin', 'superadministrator', 'super administrator'], true)
            || ($userRoleId > 0 && $companyModel->hasRoleModuleAccess($userRoleId, 'salary_structure'))
            || ($userEmployeeId > 0 && $companyModel->hasEmployeeModuleAccess($userEmployeeId, 'salary_structure'));

        if (!$canManageSalaryStructure) {
            $_SESSION['company_flash'] = 'You do not have permission to manage salary structures.';
            $this->redirect('/');
        }

        $structures = $this->payrollModel->getAllSalaryStructures();
        $roleStructures = $this->payrollModel->getAllRoleSalaryStructures();
        $employees = $this->payrollModel->getEmployees();
        $roles = $this->roleModel->getRoles();
        $this->view('management/salary_structures', [
            'title' => 'Salary Structures',
            'structures' => $structures,
            'roleStructures' => $roleStructures,
            'employees' => $employees,
            'roles' => $roles,
            'canManageSalaryStructure' => true,
        ]);
    }

    public function saveSalaryStructure(): void
    {
        $this->requireCompanyModule('salary_structure');
        $userRoleId = (int)($_SESSION['user']['role_id'] ?? 0);
        $userEmployeeId = (int)($_SESSION['user']['employee_id'] ?? 0);
        $companyModel = new CompanyModel();
        $roleName = strtolower(trim((string)($_SESSION['user']['role_name'] ?? '')));
        $canManageSalaryStructure = in_array($roleName, ['super admin', 'superadministrator', 'super administrator'], true)
            || ($userRoleId > 0 && $companyModel->hasRoleModuleAccess($userRoleId, 'salary_structure'))
            || ($userEmployeeId > 0 && $companyModel->hasEmployeeModuleAccess($userEmployeeId, 'salary_structure'));

        if (!$canManageSalaryStructure) {
            $_SESSION['payroll_flash'] = 'You do not have permission to update salary structures.';
            $this->redirect('/management/salary-structures');
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['payroll_flash'] = 'Invalid request method.';
            $this->redirect('/management/salary-structures');
        }

        try {
            $employeeId = (int)($_POST['employee_id'] ?? 0);
            $roleId = (int)($_POST['role_id'] ?? 0);

            if ($roleId > 0) {
                $this->payrollModel->saveRoleSalaryStructure($roleId, $_POST);
                $_SESSION['payroll_flash'] = 'Role salary structure saved successfully.';
            } elseif ($employeeId > 0) {
                $this->payrollModel->saveSalaryStructure($employeeId, $_POST);
                $_SESSION['payroll_flash'] = 'Employee salary structure saved successfully.';
            } else {
                throw new InvalidArgumentException('Employee or role is required.');
            }
        } catch (Throwable $exception) {
            $_SESSION['payroll_flash'] = 'Error saving salary structure: ' . $exception->getMessage();
        }
        $this->redirect('/management/salary-structures');
    }

    // ========== SALARY ADVANCE MANAGEMENT ==========

    public function manageSalaryAdvances(): void
    {
        $this->requireCompanyModule('payroll');
        $advances = $this->payrollModel->getSalaryAdvances();
        $employees = $this->payrollModel->getEmployees();
        $this->view('management/salary_advances', [
            'title' => 'Salary Advances',
            'advances' => $advances,
            'employees' => $employees
        ]);
    }

    public function requestSalaryAdvance(): void
    {
        $this->requireCompanyModule('payroll');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['payroll_flash'] = 'Invalid request method.';
            $this->redirect('/management/salary-advances');
        }

        try {
            $employeeId = (int)($_POST['employee_id'] ?? 0);
            $amount = (float)($_POST['amount'] ?? 0);
            $remarks = trim((string)($_POST['remarks'] ?? ''));

            if ($employeeId <= 0) {
                throw new InvalidArgumentException('Employee is required.');
            }
            if ($amount <= 0) {
                throw new InvalidArgumentException('Amount must be greater than 0.');
            }

            $this->payrollModel->requestSalaryAdvance($employeeId, $amount, $remarks);
            $_SESSION['payroll_flash'] = 'Salary advance request created successfully.';
        } catch (Throwable $exception) {
            $_SESSION['payroll_flash'] = 'Error creating advance request: ' . $exception->getMessage();
        }
        $this->redirect('/management/salary-advances');
    }

    public function approveSalaryAdvance(): void
    {
        $this->requireCompanyModule('payroll');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Invalid request method']);
            return;
        }

        try {
            $advanceId = (int)($_POST['advance_id'] ?? 0);
            $userId = (int)($_SESSION['user']['id'] ?? 0);

            if ($advanceId <= 0) {
                throw new InvalidArgumentException('Advance ID is required.');
            }

            $this->payrollModel->approveSalaryAdvance($advanceId, $userId);
            $this->json(['success' => true, 'message' => 'Salary advance approved successfully.']);
        } catch (Throwable $exception) {
            $this->json(['error' => $exception->getMessage()], 400);
        }
    }

    public function rejectSalaryAdvance(): void
    {
        $this->requireCompanyModule('payroll');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Invalid request method']);
            return;
        }

        try {
            $advanceId = (int)($_POST['advance_id'] ?? 0);
            $reason = trim((string)($_POST['reason'] ?? ''));

            if ($advanceId <= 0) {
                throw new InvalidArgumentException('Advance ID is required.');
            }

            $this->payrollModel->rejectSalaryAdvance($advanceId, $reason);
            $this->json(['success' => true, 'message' => 'Salary advance rejected.']);
        } catch (Throwable $exception) {
            $this->json(['error' => $exception->getMessage()], 400);
        }
    }

    // ========== EMPLOYEE LOAN MANAGEMENT ==========

    public function manageEmployeeLoans(): void
    {
        $this->requireCompanyModule('payroll');
        $loans = $this->payrollModel->getEmployeeLoans();
        $employees = $this->payrollModel->getEmployees();
        $this->view('management/employee_loans', [
            'title' => 'Employee Loans',
            'loans' => $loans,
            'employees' => $employees
        ]);
    }

    public function createEmployeeLoan(): void
    {
        $this->requireCompanyModule('payroll');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['payroll_flash'] = 'Invalid request method.';
            $this->redirect('/management/employee-loans');
        }

        try {
            $employeeId = (int)($_POST['employee_id'] ?? 0);
            if ($employeeId <= 0) {
                throw new InvalidArgumentException('Employee is required.');
            }

            $loanId = $this->payrollModel->createEmployeeLoan($employeeId, $_POST);
            $_SESSION['payroll_flash'] = 'Employee loan created successfully. EMI schedule generated.';
        } catch (Throwable $exception) {
            $_SESSION['payroll_flash'] = 'Error creating loan: ' . $exception->getMessage();
        }
        $this->redirect('/management/employee-loans');
    }

    public function viewEmployeeLoan(): void
    {
        $this->requireCompanyModule('payroll');
        $loanId = (int)($_GET['id'] ?? 0);
        $loan = $this->payrollModel->getEmployeeLoan($loanId);

        if (!$loan) {
            $_SESSION['payroll_flash'] = 'Loan not found.';
            $this->redirect('/management/employee-loans');
        }

        $this->view('management/loan_detail', [
            'title' => 'Loan Details',
            'loan' => $loan
        ]);
    }

    public function markLoanInstallmentPaid(): void
    {
        $this->requireCompanyModule('payroll');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Invalid request method']);
            return;
        }

        try {
            $installmentId = (int)($_POST['installment_id'] ?? 0);
            if ($installmentId <= 0) {
                throw new InvalidArgumentException('Installment ID is required.');
            }

            $this->payrollModel->markLoanInstallmentPaid($installmentId);
            $this->json(['success' => true, 'message' => 'Loan installment marked as paid.']);
        } catch (Throwable $exception) {
            $this->json(['error' => $exception->getMessage()], 400);
        }
    }

    // ========== PAYROLL PROCESSING ==========

    public function processPayroll(): void
    {
        $this->requireCompanyModule('payroll');
        $employees = $this->payrollModel->getEmployees();
        $this->view('management/payroll_processing', [
            'title' => 'Payroll Processing',
            'employees' => $employees,
            'current_month' => date('Y-m')
        ]);
    }

    public function runPayroll(): void
    {
        $this->requireCompanyModule('payroll');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['payroll_flash'] = 'Invalid request method.';
            $this->redirect('/management/process-payroll');
        }

        try {
            $payrollMonth = trim((string)($_POST['payroll_month'] ?? date('Y-m')));
            $selectedEmployees = (array)($_POST['employee_ids'] ?? []);

            if ($payrollMonth === '') {
                throw new InvalidArgumentException('Payroll month is required.');
            }

            $employeeIds = !empty($selectedEmployees) 
                ? array_map('intval', array_filter($selectedEmployees))
                : [];

            $results = $this->payrollModel->processMonthlyPayroll($payrollMonth, $employeeIds);

            $_SESSION['payroll_flash'] = sprintf(
                'Payroll processed: %d successful, %d failed.',
                $results['success'],
                $results['failed']
            );

            if (!empty($results['errors'])) {
                $_SESSION['payroll_errors'] = $results['errors'];
            }
        } catch (Throwable $exception) {
            $_SESSION['payroll_flash'] = 'Error processing payroll: ' . $exception->getMessage();
        }
        $this->redirect('/management/process-payroll');
    }

    public function payrollReports(): void
    {
        $this->requireCompanyModule('payroll');
        $payrolls = $this->payrollModel->getPayrolls();
        $this->view('management/payroll_reports', [
            'title' => 'Payroll Reports',
            'payrolls' => $payrolls
        ]);
    }

    public function payrollConfiguration(): void
    {
        $this->requireCompanyModule('payroll');
        $config = $this->payrollModel->getPayrollConfiguration() ?? [];
        $this->view('management/payroll_configuration', [
            'title' => 'Payroll Configuration',
            'config' => $config
        ]);
    }

    public function savePayrollConfiguration(): void
    {
        $this->requireCompanyModule('payroll');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['payroll_flash'] = 'Invalid request method.';
            $this->redirect('/management/payroll-configuration');
        }

        try {
            $this->payrollModel->savePayrollConfiguration($_POST);
            $_SESSION['payroll_flash'] = 'Payroll configuration saved successfully.';
        } catch (Throwable $exception) {
            $_SESSION['payroll_flash'] = 'Error saving configuration: ' . $exception->getMessage();
        }
        $this->redirect('/management/payroll-configuration');
    }

    // ========== PAYSLIP METHODS ==========

    public function generatePayslips(): void
    {
        $this->requireCompanyModule('payroll');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['payroll_flash'] = 'Invalid request method.';
            $this->redirect('/management/payroll-reports');
        }

        try {
            $payrollIds = (array)($_POST['payroll_ids'] ?? []);
            $generated = 0;
            $failed = 0;

            foreach ($payrollIds as $payrollId) {
                try {
                    $this->payrollModel->generatePayslip((int)$payrollId);
                    $generated++;
                } catch (Throwable $e) {
                    $failed++;
                }
            }

            $_SESSION['payroll_flash'] = "Payslips generated: $generated successful, $failed failed.";
        } catch (Throwable $exception) {
            $_SESSION['payroll_flash'] = 'Error generating payslips: ' . $exception->getMessage();
        }
        $this->redirect('/management/payroll-reports');
    }

    public function viewPayslip(): void
    {
        $this->requireCompanyModule('payroll');
        $payslipId = (int)($_GET['id'] ?? 0);
        $payslip = $this->payrollModel->getPayslip($payslipId);

        if (!$payslip) {
            $_SESSION['payroll_flash'] = 'Payslip not found.';
            $this->redirect('/management/payroll-reports');
        }

        $this->view('management/payslip_detail', [
            'title' => 'Payslip',
            'payslip' => $payslip
        ]);
    }

    public function downloadPayslipPDF(): void
    {
        $this->requireCompanyModule('payroll');
        $payslipId = (int)($_GET['id'] ?? 0);
        
        try {
            $html = $this->payrollModel->generatePayslipHTML($payslipId);
            
            header('Content-Type: text/html; charset=utf-8');
            header('Content-Disposition: inline; filename="payslip_' . $payslipId . '.html"');
            echo $html;
            exit;
        } catch (Throwable $exception) {
            $_SESSION['payroll_flash'] = 'Error generating payslip: ' . $exception->getMessage();
            $this->redirect('/management/payroll-reports');
        }
    }
}
