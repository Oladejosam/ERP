<?php
declare(strict_types=1);

require_once APP_ROOT . '/core/Model.php';

class ProjectModel extends Model
{
    public function __construct()
    {
        parent::__construct();
        $this->ensureProjectTable();
    }

    private function ensureProjectTable(): void
    {
        $this->query(
            'CREATE TABLE IF NOT EXISTS projects (
                id INT PRIMARY KEY AUTO_INCREMENT,
                company_id INT NOT NULL DEFAULT 1,
                project_number VARCHAR(50) NOT NULL,
                name VARCHAR(150) NOT NULL,
                client_id INT NOT NULL DEFAULT 0,
                client_name VARCHAR(150) NULL,
                consultant VARCHAR(150) NULL,
                contract_value DECIMAL(12,2) DEFAULT 0.00,
                budget DECIMAL(12,2) DEFAULT 0.00,
                start_date DATE NOT NULL,
                end_date DATE NOT NULL,
                site_location VARCHAR(255) NOT NULL,
                progress_percent INT DEFAULT 0,
                status ENUM("planned","in_progress","completed","on_hold","cancelled") DEFAULT "planned",
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                deleted_at DATETIME NULL,
                UNIQUE KEY uq_project_company_number (company_id, project_number)
            )'
        );
        $columnExists = $this->query('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = "projects" AND column_name = "company_id"')->fetchColumn();
        if ((int)$columnExists === 0) {
            $this->query('ALTER TABLE projects ADD COLUMN company_id INT NOT NULL DEFAULT 1');
        }
        $this->query('UPDATE projects SET company_id = 1 WHERE company_id IS NULL');
        $this->query(
            'CREATE TABLE IF NOT EXISTS project_documents (
                id INT PRIMARY KEY AUTO_INCREMENT,
                company_id INT NOT NULL,
                project_id INT NOT NULL,
                label VARCHAR(150) NOT NULL,
                original_name VARCHAR(255) NOT NULL,
                stored_name VARCHAR(255) NOT NULL,
                file_type VARCHAR(100) NOT NULL,
                file_size INT NOT NULL DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
            )'
        );
        $this->query(
            'CREATE TABLE IF NOT EXISTS project_assignments (
                id INT PRIMARY KEY AUTO_INCREMENT,
                company_id INT NOT NULL,
                project_id INT NOT NULL,
                employee_id INT NOT NULL,
                job_title VARCHAR(150) NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_project_employee (project_id, employee_id),
                FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
                FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
            )'
        );
        $this->query(
            'CREATE TABLE IF NOT EXISTS project_budgets (
                id INT PRIMARY KEY AUTO_INCREMENT,
                company_id INT NOT NULL DEFAULT 1,
                project_id INT NOT NULL,
                budget_name VARCHAR(150) NOT NULL,
                category VARCHAR(100) NOT NULL DEFAULT "General",
                unit_of_measure VARCHAR(50) NOT NULL,
                quantity DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                unit_cost DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                total_cost DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                supplier VARCHAR(150) DEFAULT "",
                notes TEXT NULL,
                status VARCHAR(50) DEFAULT "pending",
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
            )'
        );
        $budgetCompanyColumn = $this->query('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = "project_budgets" AND column_name = "company_id"')->fetchColumn();
        if ((int)$budgetCompanyColumn === 0) {
            $this->query('ALTER TABLE project_budgets ADD COLUMN company_id INT NOT NULL DEFAULT 1');
        }
        $this->query('UPDATE project_budgets b INNER JOIN projects p ON p.id = b.project_id SET b.company_id = p.company_id WHERE b.company_id IS NULL OR b.company_id = 0');
        foreach ([
            'deleted_at' => 'DATETIME NULL',
            'deleted_by' => 'INT NULL',
            'deletion_reason' => 'TEXT NULL',
        ] as $column => $definition) {
            $exists = $this->query('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = "project_budgets" AND column_name = ?', [$column])->fetchColumn();
            if ((int)$exists === 0) {
                $this->query('ALTER TABLE project_budgets ADD COLUMN `' . $column . '` ' . $definition);
            }
        }
        $this->query(
            'CREATE TABLE IF NOT EXISTS project_budget_deletions (
                id INT PRIMARY KEY AUTO_INCREMENT,
                company_id INT NOT NULL,
                project_id INT NOT NULL,
                budget_id INT NOT NULL,
                budget_name VARCHAR(150) NOT NULL,
                category VARCHAR(100) NOT NULL,
                unit_of_measure VARCHAR(50) NOT NULL,
                quantity DECIMAL(12,2) NOT NULL,
                unit_cost DECIMAL(12,2) NOT NULL,
                total_cost DECIMAL(12,2) NOT NULL,
                supplier VARCHAR(150) DEFAULT "",
                notes TEXT NULL,
                deletion_reason TEXT NOT NULL,
                deleted_by INT NULL,
                deleted_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )'
        );
    }

    public function getProjects(): array
    {
        return $this->query(
            'SELECT * FROM projects WHERE company_id = ? AND deleted_at IS NULL ORDER BY created_at DESC, id DESC',
            [$this->currentCompanyId()]
        )->fetchAll();
    }

    public function getProjectById(int $id): ?array
    {
        $stmt = $this->query('SELECT * FROM projects WHERE id = ? AND company_id = ? AND deleted_at IS NULL LIMIT 1', [$id, $this->currentCompanyId()]);
        return $stmt->fetch() ?: null;
    }

    public function getProjectDocuments(int $projectId): array
    {
        return $this->query(
            'SELECT * FROM project_documents WHERE project_id = ? AND company_id = ? ORDER BY created_at DESC, id DESC',
            [$projectId, $this->currentCompanyId()]
        )->fetchAll();
    }

    public function getProjectAssignments(int $projectId): array
    {
        return $this->query(
            'SELECT a.id, a.employee_id, a.job_title, e.employee_code, e.first_name, e.last_name, e.position, e.department
             FROM project_assignments a
             INNER JOIN employees e ON e.id = a.employee_id AND e.company_id = a.company_id
             WHERE a.project_id = ? AND a.company_id = ?
             ORDER BY e.first_name ASC, e.last_name ASC',
            [$projectId, $this->currentCompanyId()]
        )->fetchAll();
    }

    public function assignEmployee(int $projectId, int $employeeId, string $jobTitle): void
    {
        $project = $this->getProjectById($projectId);
        if (!$project || trim($jobTitle) === '') {
            throw new InvalidArgumentException('A valid project and site job title are required.');
        }
        $employee = $this->query('SELECT id FROM employees WHERE id = ? AND company_id = ? LIMIT 1', [$employeeId, $this->currentCompanyId()])->fetch();
        if (!$employee) {
            throw new InvalidArgumentException('The selected employee is not part of the current company.');
        }

        $this->query(
            'INSERT INTO project_assignments (company_id, project_id, employee_id, job_title) VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE job_title = VALUES(job_title)',
            [$this->currentCompanyId(), $projectId, $employeeId, trim($jobTitle)]
        );
    }

    public function removeAssignment(int $assignmentId): void
    {
        $this->query('DELETE FROM project_assignments WHERE id = ? AND company_id = ?', [$assignmentId, $this->currentCompanyId()]);
    }

    public function getProjectBudgets(int $projectId): array
    {
        return $this->query(
            'SELECT * FROM project_budgets WHERE project_id = ? AND company_id = ? AND deleted_at IS NULL ORDER BY created_at DESC, id DESC',
            [$projectId, $this->currentCompanyId()]
        )->fetchAll();
    }

    public function addProjectBudget(int $projectId, array $data): void
    {
        if (!$this->getProjectById($projectId)) {
            throw new InvalidArgumentException('Project not found.');
        }
        $budgetName = trim((string)($data['budget_name'] ?? ''));
        $unit = trim((string)($data['unit_of_measure'] ?? ''));
        $quantity = (float)($data['quantity'] ?? 0);
        $unitCost = (float)($data['unit_cost'] ?? 0);
        if ($budgetName === '' || $unit === '' || $quantity <= 0 || $unitCost < 0) {
            throw new InvalidArgumentException('Budget item, unit, and a positive quantity are required.');
        }
        $this->query(
            'INSERT INTO project_budgets (company_id, project_id, budget_name, category, unit_of_measure, quantity, unit_cost, total_cost, supplier, notes, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$this->currentCompanyId(), $projectId, $budgetName, trim((string)($data['category'] ?? 'General')) ?: 'General', $unit, $quantity, $unitCost, $quantity * $unitCost, trim((string)($data['supplier'] ?? '')), trim((string)($data['notes'] ?? '')), trim((string)($data['budget_status'] ?? 'pending')) ?: 'pending']
        );
    }

    public function deleteProjectBudget(int $budgetId, string $reason, ?int $userId = null): void
    {
        $reason = trim($reason);
        if ($reason === '') {
            throw new InvalidArgumentException('A deletion reason is required.');
        }
        $stmt = $this->query('SELECT * FROM project_budgets WHERE id = ? AND company_id = ? AND deleted_at IS NULL LIMIT 1', [$budgetId, $this->currentCompanyId()]);
        $budget = $stmt->fetch();
        if (!$budget) {
            throw new InvalidArgumentException('Budget item not found.');
        }

        $this->query(
            'INSERT INTO project_budget_deletions (company_id, project_id, budget_id, budget_name, category, unit_of_measure, quantity, unit_cost, total_cost, supplier, notes, deletion_reason, deleted_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$this->currentCompanyId(), $budget['project_id'], $budget['id'], $budget['budget_name'], $budget['category'], $budget['unit_of_measure'], $budget['quantity'], $budget['unit_cost'], $budget['total_cost'], $budget['supplier'], $budget['notes'], $reason, $userId]
        );
        $this->query('UPDATE project_budgets SET deleted_at = NOW(), deleted_by = ?, deletion_reason = ? WHERE id = ? AND company_id = ?', [$userId, $reason, $budgetId, $this->currentCompanyId()]);
    }

    public function getDeletedProjectBudgets(int $projectId): array
    {
        return $this->query('SELECT * FROM project_budget_deletions WHERE project_id = ? AND company_id = ? ORDER BY deleted_at DESC, id DESC', [$projectId, $this->currentCompanyId()])->fetchAll();
    }

    public function addProjectDocument(int $projectId, string $label, string $originalName, string $storedName, string $fileType, int $fileSize): int
    {
        $this->query(
            'INSERT INTO project_documents (company_id, project_id, label, original_name, stored_name, file_type, file_size) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$this->currentCompanyId(), $projectId, $label, $originalName, $storedName, $fileType, $fileSize]
        );
        return (int)$this->db->lastInsertId();
    }

    public function getProjectDocument(int $documentId): ?array
    {
        $stmt = $this->query(
            'SELECT * FROM project_documents WHERE id = ? AND company_id = ? LIMIT 1',
            [$documentId, $this->currentCompanyId()]
        );
        return $stmt->fetch() ?: null;
    }

    public function saveProject(array $data): int
    {
        $projectId = (int)($data['project_id'] ?? 0);
        $projectNumber = trim((string)($data['project_number'] ?? ''));
        $name = trim((string)($data['name'] ?? ''));
        $startDate = trim((string)($data['start_date'] ?? ''));
        $endDate = trim((string)($data['end_date'] ?? ''));
        $location = trim((string)($data['site_location'] ?? ''));
        $status = trim((string)($data['status'] ?? 'planned'));
        if ($projectNumber === '' || $name === '' || $startDate === '' || $endDate === '' || $location === '') {
            throw new InvalidArgumentException('Project number, name, dates, and site location are required.');
        }
        if (!in_array($status, ['planned', 'in_progress', 'completed', 'on_hold', 'cancelled'], true)) {
            throw new InvalidArgumentException('Invalid project status.');
        }

        $values = [
            $projectNumber,
            $name,
            (int)($data['client_id'] ?? 0),
            trim((string)($data['client_name'] ?? '')),
            trim((string)($data['consultant'] ?? '')),
            (float)($data['contract_value'] ?? 0),
            (float)($data['budget'] ?? 0),
            $startDate,
            $endDate,
            $location,
            max(0, min(100, (int)($data['progress_percent'] ?? 0))),
            $status,
        ];

        if ($projectId > 0) {
            $values[] = $projectId;
            $this->query(
                'UPDATE projects SET project_number = ?, name = ?, client_id = ?, client_name = ?, consultant = ?, contract_value = ?, budget = ?, start_date = ?, end_date = ?, site_location = ?, progress_percent = ?, status = ? WHERE id = ? AND company_id = ?',
                array_merge($values, [$this->currentCompanyId()])
            );
            return $projectId;
        }

        $this->query(
            'INSERT INTO projects (company_id, project_number, name, client_id, client_name, consultant, contract_value, budget, start_date, end_date, site_location, progress_percent, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())',
            array_merge([$this->currentCompanyId()], $values)
        );
        return (int)$this->db->lastInsertId();
    }
}
