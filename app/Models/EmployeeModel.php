<?php
declare(strict_types=1);

require_once APP_ROOT . '/core/Model.php';

class EmployeeModel extends Model
{
    public function __construct()
    {
        parent::__construct();
        $this->ensureEmployeeTable();
    }

    private function ensureEmployeeTable(): void
    {
        $this->query(
            'CREATE TABLE IF NOT EXISTS employees (
                id INT PRIMARY KEY AUTO_INCREMENT,
                company_id INT NOT NULL DEFAULT 1,
                employee_code VARCHAR(50) NOT NULL UNIQUE,
                first_name VARCHAR(100) NOT NULL,
                last_name VARCHAR(100) NOT NULL,
                email VARCHAR(150) NOT NULL UNIQUE,
                phone VARCHAR(30) NOT NULL,
                department VARCHAR(100) NOT NULL,
                position VARCHAR(100) NOT NULL,
                designation VARCHAR(150) NULL,
                hire_date DATE NOT NULL,
                salary DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                status ENUM("active","inactive","terminated") DEFAULT "active",
                profile_picture VARCHAR(255) NULL,
                nin VARCHAR(50) NULL,
                account_number VARCHAR(50) NULL,
                account_name VARCHAR(150) NULL,
                bank_name VARCHAR(150) NULL,
                tin VARCHAR(50) NULL,
                pfa VARCHAR(150) NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )'
        );

        $this->query('ALTER TABLE employees ADD COLUMN IF NOT EXISTS profile_picture VARCHAR(255) NULL AFTER status');
        $this->query('ALTER TABLE employees ADD COLUMN IF NOT EXISTS nin VARCHAR(50) NULL AFTER profile_picture');
        $this->query('ALTER TABLE employees ADD COLUMN IF NOT EXISTS account_number VARCHAR(50) NULL AFTER nin');
        $this->query('ALTER TABLE employees ADD COLUMN IF NOT EXISTS account_name VARCHAR(150) NULL AFTER account_number');
        $this->query('ALTER TABLE employees ADD COLUMN IF NOT EXISTS bank_name VARCHAR(150) NULL AFTER account_name');
        $this->query('ALTER TABLE employees ADD COLUMN IF NOT EXISTS tin VARCHAR(50) NULL AFTER bank_name');
        $this->query('ALTER TABLE employees ADD COLUMN IF NOT EXISTS pfa VARCHAR(150) NULL AFTER tin');
        $this->query(
            'CREATE TABLE IF NOT EXISTS employee_custom_fields (
                id INT PRIMARY KEY AUTO_INCREMENT,
                company_id INT NOT NULL,
                field_name VARCHAR(100) NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_company_field (company_id, field_name)
            )'
        );
        $this->query(
            'CREATE TABLE IF NOT EXISTS employee_custom_field_values (
                id INT PRIMARY KEY AUTO_INCREMENT,
                employee_id INT NOT NULL,
                field_id INT NOT NULL,
                field_value TEXT NULL,
                UNIQUE KEY unique_employee_field (employee_id, field_id)
            )'
        );
        $this->query(
            'CREATE TABLE IF NOT EXISTS employee_disabled_columns (
                id INT PRIMARY KEY AUTO_INCREMENT,
                company_id INT NOT NULL,
                column_key VARCHAR(100) NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_company_column (company_id, column_key)
            )'
        );
        $this->query(
            "UPDATE employees e INNER JOIN employee_custom_field_values v ON v.employee_id = e.id INNER JOIN employee_custom_fields f ON f.id = v.field_id AND f.company_id = e.company_id SET e.account_name = v.field_value WHERE LOWER(f.field_name) = 'account name' AND (e.account_name IS NULL OR e.account_name = '')"
        );
        $this->query(
            "INSERT IGNORE INTO employee_disabled_columns (company_id, column_key) SELECT company_id, CONCAT('custom_', id) FROM employee_custom_fields WHERE LOWER(field_name) = 'account name'"
        );
        $this->query(
            'CREATE TABLE IF NOT EXISTS employee_archive (
                id INT PRIMARY KEY AUTO_INCREMENT,
                company_id INT NOT NULL,
                employee_id INT NOT NULL,
                employee_data LONGTEXT NOT NULL,
                deleted_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )'
        );
        $this->query(
            'CREATE TABLE IF NOT EXISTS departments (
                id INT PRIMARY KEY AUTO_INCREMENT,
                company_id INT NOT NULL,
                name VARCHAR(100) NOT NULL,
                role_id INT NULL,
                head_employee_id INT NULL,
                head_title VARCHAR(100) NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_company_department (company_id, name)
            )'
        );
        $this->query('ALTER TABLE departments ADD COLUMN IF NOT EXISTS role_id INT NULL AFTER name');
        $this->query('ALTER TABLE departments ADD COLUMN IF NOT EXISTS head_employee_id INT NULL AFTER role_id');
        $this->query('ALTER TABLE departments ADD COLUMN IF NOT EXISTS head_title VARCHAR(100) NULL AFTER head_employee_id');
        $this->query('ALTER TABLE departments ADD COLUMN IF NOT EXISTS head_role_id INT NULL AFTER role_id');
        $this->query('UPDATE departments SET head_role_id = role_id WHERE head_role_id IS NULL AND role_id IS NOT NULL');
        $this->query(
            'CREATE TABLE IF NOT EXISTS department_roles (
                department_id INT NOT NULL,
                company_id INT NOT NULL,
                role_id INT NOT NULL,
                PRIMARY KEY (department_id, role_id)
            )'
        );
        $this->query(
            'INSERT IGNORE INTO department_roles (department_id, company_id, role_id)
             SELECT id, company_id, role_id FROM departments WHERE role_id IS NOT NULL'
        );
        $this->query('INSERT IGNORE INTO departments (company_id, name) SELECT DISTINCT company_id, department FROM employees WHERE department IS NOT NULL AND department <> ""');
    }

    public function getEmployees(): array
    {
        $stmt = $this->query('SELECT e.*, r.name AS role_name FROM employees e LEFT JOIN users u ON u.employee_id = e.id AND (u.company_id = e.company_id OR u.company_id IS NULL) LEFT JOIN roles r ON r.id = u.role_id WHERE e.company_id = ? ORDER BY e.created_at DESC, e.id DESC', [$this->currentCompanyId()]);
        return $stmt->fetchAll();
    }

    public function getDepartments(): array
    {
        $stmt = $this->query(
            'SELECT d.id, d.name, d.role_id, d.head_role_id, d.head_employee_id, d.head_title, r.name AS role_name
             FROM departments d
             LEFT JOIN roles r ON r.id = d.head_role_id
             WHERE d.company_id = ? ORDER BY d.name ASC',
            [$this->currentCompanyId()]
        );
        $departments = $stmt->fetchAll();
        foreach ($departments as &$department) {
            $department['role_ids'] = array_map(
                static fn (array $role): int => (int)$role['id'],
                $this->query(
                    'SELECT r.id FROM department_roles dr INNER JOIN roles r ON r.id = dr.role_id AND r.company_id = dr.company_id WHERE dr.department_id = ? AND dr.company_id = ? ORDER BY r.name ASC',
                    [(int)$department['id'], $this->currentCompanyId()]
                )->fetchAll()
            );
        }
        return $departments;
    }

    public function createDepartment(string $name, array $roleIds = [], ?int $headRoleId = null, ?int $headEmployeeId = null, ?string $headTitle = null): void
    {
        $name = trim($name);
        if ($name === '' || strlen($name) > 100) {
            throw new InvalidArgumentException('A department name between 1 and 100 characters is required.');
        }
        $roleIds = array_values(array_unique(array_filter(array_map('intval', $roleIds), static fn (int $roleId): bool => $roleId > 0)));
        $this->validateDepartmentAssignments($name, $roleIds, $headRoleId, $headEmployeeId, $headTitle);
        $companyId = $this->currentCompanyId();
        $this->query('INSERT INTO departments (company_id, name, role_id, head_role_id, head_employee_id, head_title) VALUES (?, ?, ?, ?, ?, ?)', [$companyId, $name, $headRoleId, $headRoleId, $headEmployeeId, $headTitle]);
        $departmentId = (int)$this->db->lastInsertId();
        foreach ($roleIds as $roleId) {
            $this->query('INSERT INTO department_roles (department_id, company_id, role_id) VALUES (?, ?, ?)', [$departmentId, $companyId, $roleId]);
        }
    }

    public function updateDepartmentAssignments(int $departmentId, array $roleIds, ?int $headRoleId, ?int $headEmployeeId, ?string $headTitle): void
    {
        $department = $this->query('SELECT name FROM departments WHERE id = ? AND company_id = ? LIMIT 1', [$departmentId, $this->currentCompanyId()])->fetch();
        if (!$department) {
            throw new InvalidArgumentException('Department not found.');
        }
        $roleIds = array_values(array_unique(array_filter(array_map('intval', $roleIds), static fn (int $roleId): bool => $roleId > 0)));
        $this->validateDepartmentAssignments((string)$department['name'], $roleIds, $headRoleId, $headEmployeeId, $headTitle);
        $companyId = $this->currentCompanyId();
        $this->query('UPDATE departments SET role_id = ?, head_role_id = ?, head_employee_id = ?, head_title = ? WHERE id = ? AND company_id = ?', [$headRoleId, $headRoleId, $headEmployeeId, $headTitle, $departmentId, $companyId]);
        $this->query('DELETE FROM department_roles WHERE department_id = ? AND company_id = ?', [$departmentId, $companyId]);
        foreach ($roleIds as $roleId) {
            $this->query('INSERT INTO department_roles (department_id, company_id, role_id) VALUES (?, ?, ?)', [$departmentId, $companyId, $roleId]);
        }
    }

    private function validateDepartmentAssignments(string $departmentName, array $roleIds, ?int $headRoleId, ?int $headEmployeeId, ?string $headTitle): void
    {
        foreach ($roleIds as $roleId) {
            $roleExists = $this->query('SELECT id FROM roles WHERE id = ? AND company_id = ? LIMIT 1', [$roleId, $this->currentCompanyId()])->fetch();
            if (!$roleExists) {
                throw new InvalidArgumentException('One of the selected roles was not found for this company.');
            }
        }
        if ($headRoleId !== null && !in_array($headRoleId, $roleIds, true)) {
            throw new InvalidArgumentException('The department head must be one of the assigned roles.');
        }
        if ($headEmployeeId !== null) {
            $headExists = $this->query('SELECT id FROM employees WHERE id = ? AND company_id = ? AND department = ? LIMIT 1', [$headEmployeeId, $this->currentCompanyId(), $departmentName])->fetch();
            if (!$headExists) {
                throw new InvalidArgumentException('The department head must be an employee in this department.');
            }
        }
        if ($headTitle !== null) {
            $headRoleExists = $this->query('SELECT id FROM management_roles WHERE name = ? AND company_id = ? LIMIT 1', [$headTitle, $this->currentCompanyId()])->fetch();
            if (!$headRoleExists) {
                throw new InvalidArgumentException('Selected management role was not found.');
            }
        }
    }

    public function deleteDepartment(int $departmentId): void
    {
        $stmt = $this->query('SELECT name FROM departments WHERE id = ? AND company_id = ? LIMIT 1', [$departmentId, $this->currentCompanyId()]);
        $department = $stmt->fetch();
        if (!$department) {
            throw new InvalidArgumentException('Department not found.');
        }
        $companyId = $this->currentCompanyId();
        $this->db->beginTransaction();
        try {
            $this->query('UPDATE employees SET department = "" WHERE company_id = ? AND department = ?', [$companyId, $department['name']]);
            $this->query('DELETE FROM departments WHERE id = ? AND company_id = ?', [$departmentId, $companyId]);
            $this->query('DELETE FROM department_roles WHERE department_id = ? AND company_id = ?', [$departmentId, $companyId]);
            $this->db->commit();
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }

    public function getEmployeeById(int $id): ?array
    {
        $stmt = $this->query('SELECT * FROM employees WHERE id = ? AND company_id = ? LIMIT 1', [$id, $this->currentCompanyId()]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function getEmployeeByEmail(string $email): ?array
    {
        $email = trim(strtolower($email));
        if ($email === '') {
            return null;
        }

        $stmt = $this->query('SELECT * FROM employees WHERE LOWER(email) = ? AND company_id = ? LIMIT 1', [$email, $this->currentCompanyId()]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function getEmployeeByCode(string $code): ?array
    {
        $code = trim((string)$code);
        if ($code === '') {
            return null;
        }

        $stmt = $this->query('SELECT * FROM employees WHERE employee_code = ? AND company_id = ? LIMIT 1', [$code, $this->currentCompanyId()]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function getCustomFields(): array
    {
        $stmt = $this->query("SELECT id, field_name FROM employee_custom_fields WHERE company_id = ? AND CONCAT('custom_', id) NOT IN (SELECT column_key FROM employee_disabled_columns WHERE company_id = ?) ORDER BY field_name ASC", [$this->currentCompanyId(), $this->currentCompanyId()]);
        return $stmt->fetchAll();
    }

    public function getCustomFieldValues(int $employeeId): array
    {
        $stmt = $this->query(
            "SELECT f.id, f.field_name, v.field_value FROM employee_custom_fields f LEFT JOIN employee_custom_field_values v ON v.field_id = f.id AND v.employee_id = ? WHERE f.company_id = ? AND CONCAT('custom_', f.id) NOT IN (SELECT column_key FROM employee_disabled_columns WHERE company_id = ?) ORDER BY f.field_name ASC",
            [$employeeId, $this->currentCompanyId(), $this->currentCompanyId()]
        );
        return $stmt->fetchAll();
    }

    public function getEmployeeColumnOptions(): array
    {
        $columns = [
            ['key' => 'employee_code', 'label' => 'Employee Code'], ['key' => 'first_name', 'label' => 'First Name'],
            ['key' => 'last_name', 'label' => 'Last Name'], ['key' => 'email', 'label' => 'Email'],
            ['key' => 'phone', 'label' => 'Phone'], ['key' => 'department', 'label' => 'Department'],
            ['key' => 'position', 'label' => 'Position'], ['key' => 'designation', 'label' => 'Designation'],
            ['key' => 'hire_date', 'label' => 'Hire Date'], ['key' => 'salary', 'label' => 'Salary'],
            ['key' => 'status', 'label' => 'Status'], ['key' => 'profile_picture', 'label' => 'Profile Picture'],
            ['key' => 'nin', 'label' => 'NIN'], ['key' => 'account_number', 'label' => 'Account Number'],
            ['key' => 'account_name', 'label' => 'Account Name'], ['key' => 'bank_name', 'label' => 'Bank Name'], ['key' => 'tin', 'label' => 'TIN'], ['key' => 'pfa', 'label' => 'PFA'],
        ];
        foreach ($this->getCustomFields() as $field) {
            $columns[] = ['key' => 'custom_' . (int)$field['id'], 'label' => $field['field_name']];
        }
        return $columns;
    }

    public function getDisabledColumns(): array
    {
        $stmt = $this->query('SELECT column_key, created_at FROM employee_disabled_columns WHERE company_id = ? ORDER BY created_at DESC', [$this->currentCompanyId()]);
        $labels = [
            'employee_code' => 'Employee Code', 'first_name' => 'First Name', 'last_name' => 'Last Name',
            'email' => 'Email', 'phone' => 'Phone', 'department' => 'Department', 'position' => 'Position',
            'designation' => 'Designation', 'hire_date' => 'Hire Date', 'salary' => 'Salary', 'status' => 'Status',
            'profile_picture' => 'Profile Picture', 'nin' => 'NIN', 'account_number' => 'Account Number', 'account_name' => 'Account Name',
            'bank_name' => 'Bank Name', 'tin' => 'TIN', 'pfa' => 'PFA',
        ];
        $columns = [];
        foreach ($stmt->fetchAll() as $column) {
            $key = (string)$column['column_key'];
            $label = $labels[$key] ?? $key;
            if (strpos($key, 'custom_') === 0) {
                $fieldId = (int)substr($key, 7);
                $fieldStmt = $this->query('SELECT field_name FROM employee_custom_fields WHERE id = ? AND company_id = ? LIMIT 1', [$fieldId, $this->currentCompanyId()]);
                $field = $fieldStmt->fetch();
                $label = $field ? $field['field_name'] : $key;
            }
            $columns[] = ['column_key' => $key, 'field_name' => $label, 'created_at' => $column['created_at']];
        }
        return $columns;
    }

    public function archiveEmployee(array $employee): void
    {
        $this->query(
            'INSERT INTO employee_archive (company_id, employee_id, employee_data) VALUES (?, ?, ?)',
            [$this->currentCompanyId(), (int)$employee['id'], json_encode($employee, JSON_UNESCAPED_SLASHES)]
        );
    }

    public function getArchivedEmployees(): array
    {
        $stmt = $this->query('SELECT id, employee_id, employee_data, deleted_at FROM employee_archive WHERE company_id = ? ORDER BY deleted_at DESC, id DESC', [$this->currentCompanyId()]);
        $archived = [];
        foreach ($stmt->fetchAll() as $row) {
            $employee = json_decode((string)$row['employee_data'], true);
            if (is_array($employee)) {
                $employee['archived_id'] = (int)$row['id'];
                $employee['deleted_at'] = $row['deleted_at'];
                $archived[] = $employee;
            }
        }
        return $archived;
    }

    public function createCustomField(string $fieldName): int
    {
        $fieldName = trim($fieldName);
        if ($fieldName === '' || strlen($fieldName) > 100) {
            throw new InvalidArgumentException('A data column name between 1 and 100 characters is required.');
        }

        $this->query(
            'INSERT INTO employee_custom_fields (company_id, field_name) VALUES (?, ?) ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)',
            [$this->currentCompanyId(), $fieldName]
        );
        return (int)$this->db->lastInsertId();
    }

    public function saveCustomFieldValue(int $employeeId, int $fieldId, string $value): void
    {
        $this->query(
            'INSERT INTO employee_custom_field_values (employee_id, field_id, field_value) SELECT ?, id, ? FROM employee_custom_fields WHERE id = ? AND company_id = ? ON DUPLICATE KEY UPDATE field_value = VALUES(field_value)',
            [$employeeId, trim($value), $fieldId, $this->currentCompanyId()]
        );
    }

    public function saveCustomFieldValues(int $employeeId, array $values): void
    {
        foreach ($values as $fieldId => $value) {
            $this->saveCustomFieldValue($employeeId, (int)$fieldId, (string)$value);
        }
    }

    public function disableEmployeeColumn(string $columnKey): void
    {
        $fixedColumns = array_column($this->getEmployeeColumnOptions(), 'key');
        if (!in_array($columnKey, $fixedColumns, true)) {
            throw new InvalidArgumentException('Employee data column not found.');
        }

        $this->query(
            'INSERT INTO employee_disabled_columns (company_id, column_key) VALUES (?, ?) ON DUPLICATE KEY UPDATE column_key = VALUES(column_key)',
            [$this->currentCompanyId(), $columnKey]
        );
        if ($columnKey === 'designation') {
            $this->query('UPDATE employees SET designation = NULL WHERE company_id = ?', [$this->currentCompanyId()]);
        }
    }

    public function generateUniqueEmployeeCode(string $prefix = 'EMP'): string
    {
        $base = strtoupper(trim($prefix));
        if ($base === '') {
            $base = 'EMP';
        }

        do {
            $candidate = $base . '-' . str_pad((string)random_int(1000, 9999), 4, '0', STR_PAD_LEFT);
        } while ($this->getEmployeeByCode($candidate) !== null);

        return $candidate;
    }

    public function createEmployee(array $data): int
    {
        $employeeCode = trim((string)($data['employee_code'] ?? ''));
        if ($employeeCode === '') {
            $employeeCode = $this->generateUniqueEmployeeCode('EMP');
        }

        $firstName = trim((string)($data['first_name'] ?? ''));
        $lastName = trim((string)($data['last_name'] ?? ''));
        $email = trim(strtolower((string)($data['email'] ?? '')));
        $phone = trim((string)($data['phone'] ?? ''));
        $department = trim((string)($data['department'] ?? ''));
        $position = trim((string)($data['position'] ?? ''));
        $designation = trim((string)($data['designation'] ?? ''));
        $profilePicture = trim((string)($data['profile_picture'] ?? ''));
        $nin = trim((string)($data['nin'] ?? ''));
        $accountNumber = trim((string)($data['account_number'] ?? ''));
        $accountName = trim((string)($data['account_name'] ?? ''));
        $bankName = trim((string)($data['bank_name'] ?? ''));
        $tin = trim((string)($data['tin'] ?? ''));
        $pfa = trim((string)($data['pfa'] ?? ''));
        $hireDate = trim((string)($data['hire_date'] ?? date('Y-m-d')));
        $salary = (float)($data['salary'] ?? 0.0);
        $rawStatus = trim((string)($data['status'] ?? 'active'));
        $status = in_array($rawStatus, ['active', 'inactive', 'terminated'], true)
            ? $rawStatus
            : 'active';

        if ($firstName === '' || $lastName === '' || $email === '' || $phone === '' || $department === '' || $position === '') {
            throw new InvalidArgumentException('Employee first name, last name, email, phone, department and position are required.');
        }

        if ($this->getEmployeeByEmail($email) !== null) {
            return (int)$this->getEmployeeByEmail($email)['id'];
        }

        $this->query(
            'INSERT INTO employees (company_id, employee_code, first_name, last_name, email, phone, department, position, designation, hire_date, salary, status, profile_picture, nin, account_number, account_name, bank_name, tin, pfa, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())',
            [$this->currentCompanyId(), $employeeCode, $firstName, $lastName, $email, $phone, $department, $position, $designation !== '' ? $designation : null, $hireDate, number_format($salary, 2, '.', ''), $status, $profilePicture !== '' ? $profilePicture : null, $nin !== '' ? $nin : null, $accountNumber !== '' ? $accountNumber : null, $accountName !== '' ? $accountName : null, $bankName !== '' ? $bankName : null, $tin !== '' ? $tin : null, $pfa !== '' ? $pfa : null]
        );

        return (int)$this->db->lastInsertId();
    }

    public function updateEmployeeStatus(int $employeeId, string $status): void
    {
        if (!in_array($status, ['active', 'inactive'], true)) {
            throw new InvalidArgumentException('Invalid employee status.');
        }

        $this->query('UPDATE employees SET status = ? WHERE id = ? AND company_id = ?', [$status, $employeeId, $this->currentCompanyId()]);
    }

    public function updateEmployeeProfilePicture(int $employeeId, string $profilePicture): void
    {
        $this->query(
            'UPDATE employees SET profile_picture = ? WHERE id = ? AND company_id = ?',
            [$profilePicture !== '' ? $profilePicture : null, $employeeId, $this->currentCompanyId()]
        );
    }

    public function updateEmployee(int $employeeId, array $data): void
    {
        $this->query(
            'UPDATE employees SET employee_code = ?, first_name = ?, last_name = ?, email = ?, phone = ?, department = ?, position = ?, designation = ?, hire_date = ?, salary = ?, status = ?, nin = ?, account_number = ?, account_name = ?, bank_name = ?, tin = ?, pfa = ? WHERE id = ? AND company_id = ?',
            [
                trim((string)($data['employee_code'] ?? '')),
                trim((string)($data['first_name'] ?? '')),
                trim((string)($data['last_name'] ?? '')),
                trim(strtolower((string)($data['email'] ?? ''))),
                trim((string)($data['phone'] ?? '')),
                trim((string)($data['department'] ?? '')),
                trim((string)($data['position'] ?? '')),
                trim((string)($data['designation'] ?? '')) ?: null,
                trim((string)($data['hire_date'] ?? date('Y-m-d'))),
                number_format((float)($data['salary'] ?? 0), 2, '.', ''),
                in_array(($data['status'] ?? ''), ['active', 'inactive', 'terminated'], true) ? $data['status'] : 'active',
                trim((string)($data['nin'] ?? '')) ?: null,
                trim((string)($data['account_number'] ?? '')) ?: null,
                trim((string)($data['account_name'] ?? '')) ?: null,
                trim((string)($data['bank_name'] ?? '')) ?: null,
                trim((string)($data['tin'] ?? '')) ?: null,
                trim((string)($data['pfa'] ?? '')) ?: null,
                $employeeId,
                $this->currentCompanyId(),
            ]
        );
    }

    public function deleteEmployee(int $employeeId): void
    {
        $this->query('DELETE FROM employees WHERE id = ? AND company_id = ?', [$employeeId, $this->currentCompanyId()]);
    }
}
