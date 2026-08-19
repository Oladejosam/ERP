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
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )'
        );

        $this->query('ALTER TABLE employees ADD COLUMN IF NOT EXISTS profile_picture VARCHAR(255) NULL AFTER status');
    }

    public function getEmployees(): array
    {
        $stmt = $this->query('SELECT * FROM employees WHERE company_id = ? ORDER BY created_at DESC, id DESC', [$this->currentCompanyId()]);
        return $stmt->fetchAll();
    }

    public function getDepartments(): array
    {
        $stmt = $this->query('SELECT DISTINCT department AS name FROM employees WHERE company_id = ? AND department IS NOT NULL AND department <> "" ORDER BY department ASC', [$this->currentCompanyId()]);
        return $stmt->fetchAll();
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
            'INSERT INTO employees (company_id, employee_code, first_name, last_name, email, phone, department, position, designation, hire_date, salary, status, profile_picture, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())',
            [$this->currentCompanyId(), $employeeCode, $firstName, $lastName, $email, $phone, $department, $position, $designation !== '' ? $designation : null, $hireDate, number_format($salary, 2, '.', ''), $status, $profilePicture !== '' ? $profilePicture : null]
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

    public function deleteEmployee(int $employeeId): void
    {
        $this->query('DELETE FROM employees WHERE id = ? AND company_id = ?', [$employeeId, $this->currentCompanyId()]);
    }
}
