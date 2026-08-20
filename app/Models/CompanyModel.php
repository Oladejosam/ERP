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
