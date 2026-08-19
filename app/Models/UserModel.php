<?php
declare(strict_types=1);

require_once APP_ROOT . '/core/Model.php';
require_once APP_ROOT . '/app/Models/EmployeeModel.php';
require_once APP_ROOT . '/app/Models/RoleModel.php';

class UserModel extends Model
{
    public function __construct()
    {
        parent::__construct();
        $this->ensureUserChangeTable();
    }

    private function ensureUserChangeTable(): void
    {
        $this->query(
            'CREATE TABLE IF NOT EXISTS user_change_history (
                id INT PRIMARY KEY AUTO_INCREMENT,
                employee_id INT NOT NULL,
                change_reason TEXT NOT NULL,
                before_data TEXT NOT NULL,
                after_data TEXT NOT NULL,
                changed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
            )'
        );
    }

    private function normalizeEmail(string $email): string
    {
        return trim(strtolower($email));
    }

    public function authenticate(string $email, string $password): ?array
    {
        $email = $this->normalizeEmail($email);
        $stmt = $this->query('SELECT * FROM users WHERE email = ? LIMIT 1', [$email]);
        $user = $stmt->fetch();
        if ($user) {
            $storedHash = (string)($user['password_hash'] ?? '');
            if (password_verify($password, $storedHash) || $storedHash === $password) {
                return $user;
            }
        }
        return null;
    }

    public function getUserByEmail(string $email): ?array
    {
        $email = $this->normalizeEmail($email);
        $stmt = $this->query('SELECT * FROM users WHERE email = ? LIMIT 1', [$email]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function getUserByEmployeeId(int $employeeId): ?array
    {
        $stmt = $this->query('SELECT * FROM users WHERE employee_id = ? LIMIT 1', [$employeeId]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function deleteUserByEmployeeId(int $employeeId): void
    {
        $this->query('DELETE FROM users WHERE employee_id = ?', [$employeeId]);
    }

    public function updateUserByEmployeeId(int $employeeId, array $data, string $changeReason = ''): bool
    {
        $existing = $this->getUserByEmployeeId($employeeId);
        if (!$existing) {
            return false;
        }

        $fields = [];
        $params = [];
        $after = [
            'name' => $existing['name'] ?? '',
            'email' => $existing['email'] ?? '',
            'role_id' => (int)($existing['role_id'] ?? 0),
            'status' => $existing['status'] ?? '',
            'password_changed' => false,
        ];

        if (!empty($data['name'])) {
            $fields[] = 'name = ?';
            $params[] = $data['name'];
            $after['name'] = $data['name'];
        }

        if (!empty($data['email'])) {
            $fields[] = 'email = ?';
            $params[] = $this->normalizeEmail($data['email']);
            $after['email'] = $this->normalizeEmail($data['email']);
        }

        if (!empty($data['password'])) {
            $fields[] = 'password_hash = ?';
            $params[] = password_hash($data['password'], PASSWORD_BCRYPT);
            $after['password_changed'] = true;
        }

        if (!empty($data['role_id'])) {
            $fields[] = 'role_id = ?';
            $params[] = (int)$data['role_id'];
            $after['role_id'] = (int)$data['role_id'];
        }

        if (empty($fields)) {
            return false;
        }

        $params[] = $employeeId;
        $sql = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE employee_id = ?';
        $this->query($sql, $params);

        if (trim($changeReason) !== '') {
            $this->recordUserChange($employeeId, $existing, $after, trim($changeReason));
        }

        return true;
    }

    public function createUser(array $data): int
    {
        $email = $this->normalizeEmail($data['email'] ?? '');
        $sql = 'INSERT INTO users (name, email, password_hash, role_id, employee_id, created_at) VALUES (?, ?, ?, ?, ?, NOW())';
        $this->query($sql, [
            $data['name'],
            $email,
            password_hash($data['password'], PASSWORD_BCRYPT),
            $data['role_id'],
            $data['employee_id'] ?? null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function ensureSuperAdminUser(): int
    {
        $roleModel = new RoleModel();
        $superAdminRoleId = $roleModel->createRoleIfMissing('Super Admin', 'Super admin privilege');

        $email = 'superadmin@erp.local';
        $existing = $this->getUserByEmail($email);
        if ($existing !== null) {
            $needsUpdate = false;
            $updateValues = [];

            if ((int)($existing['role_id'] ?? 0) !== $superAdminRoleId) {
                $needsUpdate = true;
                $updateValues[] = 'role_id = ' . (int)$superAdminRoleId;
            }

            if (trim((string)($existing['name'] ?? '')) !== 'Super Admin') {
                $needsUpdate = true;
                $updateValues[] = 'name = ?';
                $updateValuesParams[] = 'Super Admin';
            }

            if (!password_verify('SuperAdmin123!', (string)($existing['password_hash'] ?? ''))) {
                $needsUpdate = true;
                $updateValues[] = 'password_hash = ?';
                $updateValuesParams[] = password_hash('SuperAdmin123!', PASSWORD_BCRYPT);
            }

            if ($needsUpdate) {
                $params = $updateValuesParams ?? [];
                $sql = 'UPDATE users SET ' . implode(', ', $updateValues) . ' WHERE email = ? LIMIT 1';
                $params[] = $email;
                $this->query($sql, $params);
            }

            return (int)$existing['id'];
        }

        $this->createUser([
            'name' => 'Super Admin',
            'email' => $email,
            'password' => 'SuperAdmin123!',
            'role_id' => $superAdminRoleId,
            'employee_id' => null,
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function ensureUsersForEmployees(): int
    {
        $employeeModel = new EmployeeModel();
        $roleModel = new RoleModel();
        $staffRoleId = $roleModel->getRoleIdByName('Staff');
        if ($staffRoleId === null) {
            $staffRoleId = $roleModel->createRoleIfMissing('Staff', 'Standard staff account');
        }

        $created = 0;
        $employees = $employeeModel->getEmployees();
        foreach ($employees as $employee) {
            $email = trim((string)($employee['email'] ?? ''));
            if ($email === '' || $this->getUserByEmail($email) !== null) {
                continue;
            }

            $name = trim((string)($employee['first_name'] ?? '') . ' ' . ($employee['last_name'] ?? ''));
            if ($name === '') {
                $name = 'Employee ' . ($employee['id'] ?? '');
            }

            $this->createUser([
                'name' => $name,
                'email' => $email,
                'password' => 'Welcome123!',
                'role_id' => $staffRoleId,
                'employee_id' => $employee['id'],
            ]);
            $created++;
        }

        return $created;
    }

    public function createMissingUserCredentials(): array
    {
        $employeeModel = new EmployeeModel();
        $roleModel = new RoleModel();
        $created = [];

        $employees = $employeeModel->getEmployees();
        foreach ($employees as $employee) {
            $email = trim((string)($employee['email'] ?? ''));
            if ($email === '' || $this->getUserByEmail($email) !== null) {
                continue;
            }

            $name = trim((string)($employee['first_name'] ?? '') . ' ' . ($employee['last_name'] ?? ''));
            if ($name === '') {
                $name = 'Employee ' . ($employee['id'] ?? '');
            }

            $roleName = trim((string)($employee['role_name'] ?? 'Staff')) ?: 'Staff';
            $roleId = $roleModel->getRoleIdByName($roleName);
            if ($roleId === null) {
                $roleId = $roleModel->createRoleIfMissing($roleName, $roleName . ' role');
            }

            $password = substr(bin2hex(random_bytes(6)), 0, 12) . 'A1!';

            $this->createUser([
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'role_id' => $roleId,
                'employee_id' => $employee['id'],
            ]);

            $created[] = ['email' => $email, 'password' => $password, 'role' => $roleModel->getRoleNameById($roleId)];
        }

        return $created;
    }

    public function syncEmployeeProfile(array $user): array
    {
        $email = trim((string)($user['email'] ?? ''));
        if ($email === '') {
            return $user;
        }

        $employeeModel = new EmployeeModel();
        $employee = $employeeModel->getEmployeeByEmail($email);
        if (!$employee) {
            $name = trim((string)($user['name'] ?? ''));
            $parts = preg_split('/\s+/', $name, 2);
            $firstName = trim((string)($parts[0] ?? 'Test'));
            $lastName = trim((string)($parts[1] ?? 'Staff'));

            $employeeId = $employeeModel->createEmployee([
                'employee_code' => 'STF-' . substr(strtoupper(sha1($email)), 0, 4),
                'first_name' => $firstName !== '' ? $firstName : 'Test',
                'last_name' => $lastName !== '' ? $lastName : 'Staff',
                'email' => $email,
                'phone' => '0000000000',
                'department' => 'Store',
                'position' => 'Staff',
                'hire_date' => date('Y-m-d'),
                'salary' => 0,
            ]);
            $employee = $employeeModel->getEmployeeById($employeeId);
        }

        $employeeId = (int)($employee['id'] ?? 0);
        if ($employeeId <= 0) {
            return $user;
        }

        $currentEmployeeId = (int)($user['employee_id'] ?? 0);
        if ($currentEmployeeId !== $employeeId) {
            $this->query('UPDATE users SET employee_id = ? WHERE email = ? LIMIT 1', [$employeeId, $email]);
        }

        $user['employee_id'] = $employeeId;
        return $user;
    }

    public function ensureTestLogisticsAccounts(): void
    {
        $roleNames = $this->getDefaultDemoRoles();
        $roleModel = new RoleModel();
        $employeeModel = new EmployeeModel();

        foreach ($roleNames as $roleName) {
            $roleId = $roleModel->createRoleIfMissing($roleName, $roleName . ' role');
            if ($roleId === null) {
                continue;
            }

            for ($index = 1; $index <= 2; $index++) {
                $emailBase = strtolower(preg_replace('/[^a-z0-9]+/i', '', $roleName));
                $email = $emailBase . $index . '@test.com';
                if ($this->getUserByEmail($email) !== null) {
                    continue;
                }

                $name = $roleName . ' Demo ' . $index;
                $employee = $employeeModel->getEmployeeByEmail($email);
                if ($employee === null) {
                    $baseCode = strtoupper(substr($emailBase, 0, 6));
                    $generatedCode = $baseCode . '-' . str_pad((string)$index, 3, '0', STR_PAD_LEFT);
                    if ($employeeModel->getEmployeeByCode($generatedCode) !== null) {
                        $generatedCode = $employeeModel->generateUniqueEmployeeCode($generatedCode);
                    }

                    $employeeId = $employeeModel->createEmployee([
                        'employee_code' => $generatedCode,
                        'first_name' => $roleName,
                        'last_name' => 'Demo ' . $index,
                        'email' => $email,
                        'phone' => '0800000000' . $index,
                        'department' => $roleName,
                        'position' => $roleName,
                        'hire_date' => date('Y-m-d'),
                        'salary' => 0,
                    ]);
                } else {
                    $employeeId = (int)($employee['id'] ?? 0);
                }

                $this->query(
                    'INSERT INTO users (name, email, password_hash, role_id, employee_id, created_at) VALUES (?, ?, ?, ?, ?, NOW())',
                    [$name, $email, password_hash($this->demoPasswordForRole($roleName, $index), PASSWORD_BCRYPT), $roleId, $employeeId]
                );
            }
        }
    }

    private function getDefaultDemoRoles(): array
    {
        $roles = RoleModel::defaultRoleNames();
        $normalized = [];
        foreach ($roles as $role) {
            $key = strtolower(trim($role));
            if (!in_array($key, $normalized, true)) {
                $normalized[$key] = $role;
            }
        }
        return array_values($normalized);
    }

    private function demoPasswordForRole(string $roleName, int $index): string
    {
        $key = strtolower(preg_replace('/[^a-z0-9]+/', '', $roleName));
        return ucfirst($key) . '@' . $index . '000!';
    }

    private function recordUserChange(int $employeeId, array $before, array $after, string $reason): void
    {
        $beforeJson = json_encode($before);
        $afterJson = json_encode($after);
        $this->query(
            'INSERT INTO user_change_history (employee_id, change_reason, before_data, after_data, changed_at) VALUES (?, ?, ?, ?, NOW())',
            [$employeeId, $reason, $beforeJson, $afterJson]
        );
    }
}
