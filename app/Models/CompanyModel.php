<?php
declare(strict_types=1);

require_once APP_ROOT . '/core/Model.php';

class CompanyModel extends Model
{
    public function __construct()
    {
        parent::__construct();
        $this->ensureCompanySettingsTable();
    }

    private function ensureCompanySettingsTable(): void
    {
        $this->query(
            'CREATE TABLE IF NOT EXISTS company_settings (
                id TINYINT UNSIGNED PRIMARY KEY,
                company_name VARCHAR(150) NOT NULL,
                logo_path VARCHAR(255) NULL,
                theme_color CHAR(7) NOT NULL DEFAULT "#1d4ed8",
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )'
        );

        $this->query(
            'CREATE TABLE IF NOT EXISTS companies (
                id INT PRIMARY KEY AUTO_INCREMENT,
                company_name VARCHAR(150) NOT NULL,
                logo_path VARCHAR(255) NULL,
                theme_color CHAR(7) NOT NULL DEFAULT "#1d4ed8",
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )'
        );
        $this->query('ALTER TABLE companies ADD COLUMN IF NOT EXISTS created_at DATETIME DEFAULT CURRENT_TIMESTAMP AFTER is_active');
        $this->query(
            'CREATE TABLE IF NOT EXISTS company_modules (
                company_id INT NOT NULL,
                module_key VARCHAR(50) NOT NULL,
                PRIMARY KEY (company_id, module_key),
                FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
            )'
        );
        $this->query("INSERT IGNORE INTO company_modules (company_id, module_key) SELECT id, 'requisition' FROM companies WHERE is_active = 1");
        $this->query(
            'CREATE TABLE IF NOT EXISTS employee_module_access (
                company_id INT NOT NULL,
                employee_id INT NOT NULL,
                module_key VARCHAR(50) NOT NULL,
                PRIMARY KEY (company_id, employee_id, module_key),
                FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
                FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
            )'
        );

        $this->query(
            'INSERT INTO companies (id, company_name, logo_path, theme_color)
             SELECT 1, company_name, logo_path, theme_color FROM company_settings
             WHERE id = 1 AND company_name <> ""
             AND NOT EXISTS (SELECT 1 FROM companies WHERE id = 1)'
        );

        foreach (['employees', 'inventory_categories', 'inventory_items', 'payrolls', 'suppliers', 'projects', 'purchase_orders', 'invoices'] as $table) {
            $tableExists = $this->query('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?', [$table])->fetchColumn();
            if ((int)$tableExists === 0) {
                continue;
            }
            $columnExists = $this->query('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = "company_id"', [$table])->fetchColumn();
            if ((int)$columnExists === 0) {
                $this->query('ALTER TABLE `' . $table . '` ADD COLUMN company_id INT NULL');
            }
            $this->query('UPDATE `' . $table . '` SET company_id = 1 WHERE company_id IS NULL');
        }
    }

    public function getSettings(): array
    {
        $stmt = $this->query('SELECT * FROM companies WHERE id = ? AND is_active = 1 LIMIT 1', [$this->currentCompanyId()]);
        $settings = $stmt->fetch();

        return $settings ?: [
            'id' => 1,
            'company_name' => '',
            'logo_path' => null,
            'theme_color' => '#1d4ed8',
        ];
    }

    public function getCompanies(bool $includeInactive = false): array
    {
        $sql = $includeInactive
            ? 'SELECT * FROM companies ORDER BY company_name ASC'
            : 'SELECT * FROM companies WHERE is_active = 1 ORDER BY company_name ASC';
        $companies = $this->query($sql)->fetchAll();
        foreach ($companies as &$company) {
            $company['module_access'] = $this->getModuleAccess((int)$company['id']);
        }
        return $companies;
    }

    public function isCompanyActive(int $companyId): bool
    {
        if ($companyId <= 0) {
            return true;
        }
        $stmt = $this->query('SELECT is_active FROM companies WHERE id = ? LIMIT 1', [$companyId]);
        $row = $stmt->fetch();
        return $row !== false && (int)$row['is_active'] === 1;
    }

    public function setCompanyActive(int $companyId, bool $active): void
    {
        $exists = $this->query('SELECT 1 FROM companies WHERE id = ? LIMIT 1', [$companyId])->fetchColumn();
        if (!$exists) {
            throw new InvalidArgumentException('The company could not be found.');
        }
        $this->query('UPDATE companies SET is_active = ? WHERE id = ?', [$active ? 1 : 0, $companyId]);
    }

    public static function availableModules(): array
    {
        return [
            'dashboard' => 'Dashboard',
            'employees' => 'Employees',
            'hr' => 'Human Resources',
            'inventory' => 'Inventory',
            'accounting' => 'Accounting and Payroll',
            'procurement' => 'Procurement',
            'requisition' => 'Requisition',
            'projects' => 'Projects',
            'contract_admin' => 'Contract Admin',
            'reports' => 'Reports',
        ];
    }

    public function getModuleAccess(int $companyId): array
    {
        $stmt = $this->query('SELECT module_key FROM company_modules WHERE company_id = ?', [$companyId]);
        $modules = array_values(array_map(static fn (array $row): string => (string)$row['module_key'], $stmt->fetchAll()));
        return $modules !== [] ? $modules : array_keys(self::availableModules());
    }

    public function saveModuleAccess(int $companyId, array $modules): void
    {
        $companyExists = $this->query('SELECT 1 FROM companies WHERE id = ? AND is_active = 1 LIMIT 1', [$companyId])->fetchColumn();
        if (!$companyExists) {
            throw new InvalidArgumentException('The company could not be found while saving module access.');
        }
        $allowed = array_keys(self::availableModules());
        $modules = array_values(array_intersect($allowed, array_map('strval', $modules)));
        $this->query('DELETE FROM company_modules WHERE company_id = ?', [$companyId]);
        foreach ($modules as $module) {
            $this->query('INSERT INTO company_modules (company_id, module_key) VALUES (?, ?)', [$companyId, $module]);
        }
    }

    public function hasModuleAccess(string $moduleKey): bool
    {
        if ($moduleKey === 'dashboard') {
            return true;
        }
        $configured = $this->query('SELECT COUNT(*) FROM company_modules WHERE company_id = ?', [$this->currentCompanyId()])->fetchColumn();
        if ((int)$configured === 0) {
            return array_key_exists($moduleKey, self::availableModules());
        }
        $stmt = $this->query('SELECT 1 FROM company_modules WHERE company_id = ? AND module_key = ? LIMIT 1', [$this->currentCompanyId(), $moduleKey]);
        return (bool)$stmt->fetchColumn();
    }

    public function hasEmployeeModuleAccess(int $employeeId, string $moduleKey): bool
    {
        if ($moduleKey === 'dashboard') {
            return true;
        }
        $companyId = $this->currentCompanyId();
        $configured = $this->query('SELECT 1 FROM employee_module_access WHERE company_id = ? AND employee_id = ? AND module_key = "__configured__" LIMIT 1', [$companyId, $employeeId])->fetchColumn();
        if ((int)$configured === 0) {
            return $this->hasModuleAccess($moduleKey);
        }
        return (bool)$this->query('SELECT 1 FROM employee_module_access WHERE company_id = ? AND employee_id = ? AND module_key = ? LIMIT 1', [$companyId, $employeeId, $moduleKey])->fetchColumn();
    }

    public function getEmployeeModuleAccess(int $employeeId): array
    {
        return array_values(array_map(static fn (array $row): string => (string)$row['module_key'], $this->query('SELECT module_key FROM employee_module_access WHERE company_id = ? AND employee_id = ? AND module_key <> "__configured__" ORDER BY module_key ASC', [$this->currentCompanyId(), $employeeId])->fetchAll()));
    }

    public function hasEmployeeModuleConfiguration(int $employeeId): bool
    {
        return (bool)$this->query('SELECT 1 FROM employee_module_access WHERE company_id = ? AND employee_id = ? AND module_key = "__configured__" LIMIT 1', [$this->currentCompanyId(), $employeeId])->fetchColumn();
    }

    public function saveEmployeeModuleAccess(int $employeeId, array $modules): void
    {
        $companyId = $this->currentCompanyId();
        if (!$this->query('SELECT 1 FROM employees WHERE id = ? AND company_id = ? LIMIT 1', [$employeeId, $companyId])->fetchColumn()) {
            throw new InvalidArgumentException('The selected employee does not belong to this company.');
        }
        $allowed = array_keys(self::availableModules());
        $modules = array_values(array_intersect($allowed, array_map('strval', $modules)));
        $this->query('DELETE FROM employee_module_access WHERE company_id = ? AND employee_id = ?', [$companyId, $employeeId]);
        $this->query('INSERT INTO employee_module_access (company_id, employee_id, module_key) VALUES (?, ?, "__configured__")', [$companyId, $employeeId]);
        foreach ($modules as $module) {
            $this->query('INSERT INTO employee_module_access (company_id, employee_id, module_key) VALUES (?, ?, ?)', [$companyId, $employeeId, $module]);
        }
    }

    public function getManagedEmployeeIds(int $managerEmployeeId, int $managerRoleId, string $managerRoleName): array
    {
        $companyId = $this->currentCompanyId();
        $managerRoleName = strtolower(trim($managerRoleName));
        $isGlobalManager = in_array($managerRoleName, ['admin', 'hr manager', 'hr_manager', 'human resource manager', 'head of human resource', 'head of human resources', 'head hr', 'head of hr', 'super admin', 'superadministrator', 'super administrator'], true);
        $topRole = $managerRoleId > 0 && !$this->query('SELECT 1 FROM workflow_role_links WHERE company_id = ? AND role_id = ? AND parent_role_id IS NOT NULL LIMIT 1', [$companyId, $managerRoleId])->fetchColumn();
        $isDepartmentHead = $managerEmployeeId > 0 && (bool)$this->query('SELECT 1 FROM departments WHERE company_id = ? AND head_employee_id = ? LIMIT 1', [$companyId, $managerEmployeeId])->fetchColumn();
        if (!$isGlobalManager && !$topRole && !$isDepartmentHead) {
            return [];
        }
        $employees = $this->query(
            'SELECT e.id, e.department, u.role_id FROM employees e LEFT JOIN users u ON u.employee_id = e.id AND (u.company_id = e.company_id OR u.company_id IS NULL) WHERE e.company_id = ? AND e.status = "active" ORDER BY e.department ASC, e.first_name ASC, e.last_name ASC',
            [$companyId]
        )->fetchAll();
        if ($isGlobalManager || $topRole) {
            return array_map(static fn (array $employee): int => (int)$employee['id'], $employees);
        }
        $department = $this->query('SELECT department FROM employees WHERE id = ? AND company_id = ? LIMIT 1', [$managerEmployeeId, $companyId])->fetchColumn();
        $roleLinks = $this->query('SELECT role_id, parent_role_id FROM workflow_role_links WHERE company_id = ?', [$companyId])->fetchAll();
        $children = [];
        foreach ($roleLinks as $link) {
            if ($link['parent_role_id'] !== null) {
                $children[(int)$link['parent_role_id']][] = (int)$link['role_id'];
            }
        }
        $descendants = [];
        $queue = [$managerRoleId];
        while ($queue !== []) {
            $parent = array_shift($queue);
            foreach ($children[$parent] ?? [] as $child) {
                if (!in_array($child, $descendants, true)) {
                    $descendants[] = $child;
                    $queue[] = $child;
                }
            }
        }
        return array_values(array_map(static fn (array $employee): int => (int)$employee['id'], array_filter($employees, static function (array $employee) use ($managerEmployeeId, $department, $descendants): bool {
            return (int)$employee['id'] !== $managerEmployeeId && (string)$employee['department'] === (string)$department && ($descendants === [] || in_array((int)($employee['role_id'] ?? 0), $descendants, true));
        })));
    }

    public function selectCompany(int $companyId): bool
    {
        $stmt = $this->query('SELECT id FROM companies WHERE id = ? AND is_active = 1 LIMIT 1', [$companyId]);
        if (!$stmt->fetch()) {
            return false;
        }
        $_SESSION['selected_company_id'] = $companyId;
        return true;
    }

    public function createCompany(string $companyName, string $themeColor, ?string $logoPath = null): int
    {
        $this->db->beginTransaction();
        try {
            $this->query('INSERT INTO companies (company_name, logo_path, theme_color) VALUES (?, ?, ?)', [$companyName, $logoPath, $themeColor]);
            $companyId = (int)$this->db->lastInsertId();
            if ($companyId <= 0) {
                throw new RuntimeException('The company record was not created.');
            }
            $this->db->commit();
            $_SESSION['selected_company_id'] = $companyId;
            return $companyId;
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }

    public function saveSettings(string $companyName, string $themeColor, ?string $logoPath = null): void
    {
        $existing = $this->getSettings();
        $logoPath = $logoPath ?? ($existing['logo_path'] ?? null);
        $companyId = (int)($existing['id'] ?? 0);
        if ($companyId > 0) {
            $this->query('UPDATE companies SET company_name = ?, logo_path = ?, theme_color = ? WHERE id = ?', [$companyName, $logoPath, $themeColor, $companyId]);
            $_SESSION['selected_company_id'] = $companyId;
            return;
        }

        $this->query('INSERT INTO companies (company_name, logo_path, theme_color) VALUES (?, ?, ?)', [$companyName, $logoPath, $themeColor]);
        $_SESSION['selected_company_id'] = (int)$this->db->lastInsertId();
    }
}
