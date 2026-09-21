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
        $this->query(
            'CREATE TABLE IF NOT EXISTS project_schedule (
                id INT PRIMARY KEY AUTO_INCREMENT,
                company_id INT NOT NULL,
                project_id INT NOT NULL,
                task_name VARCHAR(180) NOT NULL,
                start_date DATE NOT NULL,
                end_date DATE NOT NULL,
                status ENUM("planned","in_progress","completed","on_hold") NOT NULL DEFAULT "planned",
                progress_percent INT NOT NULL DEFAULT 0,
                assigned_to VARCHAR(150) DEFAULT NULL,
                notes TEXT DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
            )'
        );
        $this->query(
            'CREATE TABLE IF NOT EXISTS role_project_access (
                company_id INT NOT NULL,
                role_id INT NOT NULL,
                project_id INT NOT NULL,
                PRIMARY KEY (company_id, role_id, project_id),
                FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
            )'
        );
    }

    public function getProjects(): array
    {
        $projects = $this->query(
            'SELECT * FROM projects WHERE company_id = ? AND deleted_at IS NULL AND (' . $this->projectVisibilitySql() . ') ORDER BY created_at DESC, id DESC',
            array_merge([$this->currentCompanyId()], $this->projectVisibilityParams())
        )->fetchAll();
        foreach ($projects as &$project) {
            $this->applyScheduleProgress($project);
        }
        unset($project);
        return $projects;
    }

    public function getProjectsForAccessManagement(): array
    {
        return $this->query(
            'SELECT id, project_number, name FROM projects WHERE company_id = ? AND deleted_at IS NULL ORDER BY name ASC, id ASC',
            [$this->currentCompanyId()]
        )->fetchAll();
    }

    public function getProjectById(int $id): ?array
    {
        $stmt = $this->query('SELECT * FROM projects WHERE id = ? AND company_id = ? AND deleted_at IS NULL AND (' . $this->projectVisibilitySql('projects.id') . ') LIMIT 1', array_merge([$id, $this->currentCompanyId()], $this->projectVisibilityParams()));
        $project = $stmt->fetch();
        if (!$project) {
            return null;
        }
        $this->applyScheduleProgress($project);
        return $project;
    }

    public function getRoleProjectAccessMap(): array
    {
        $map = [];
        foreach ($this->query('SELECT role_id, project_id FROM role_project_access WHERE company_id = ? ORDER BY role_id ASC, project_id ASC', [$this->currentCompanyId()])->fetchAll() as $row) {
            $map[(int)$row['role_id']][] = (int)$row['project_id'];
        }
        return $map;
    }

    public function saveRoleProjectAccess(int $roleId, array $projectIds): void
    {
        $companyId = $this->currentCompanyId();
        if (!$this->query('SELECT 1 FROM roles WHERE id = ? AND company_id = ? LIMIT 1', [$roleId, $companyId])->fetchColumn()) {
            throw new InvalidArgumentException('The selected role does not belong to this company.');
        }
        $validProjectIds = array_values(array_unique(array_filter(array_map('intval', $projectIds), function (int $projectId) use ($companyId): bool {
            return $projectId > 0 && (bool)$this->query('SELECT 1 FROM projects WHERE id = ? AND company_id = ? AND deleted_at IS NULL LIMIT 1', [$projectId, $companyId])->fetchColumn();
        })));
        $this->query('DELETE FROM role_project_access WHERE company_id = ? AND role_id = ?', [$companyId, $roleId]);
        foreach ($validProjectIds as $projectId) {
            $this->query('INSERT INTO role_project_access (company_id, role_id, project_id) VALUES (?, ?, ?)', [$companyId, $roleId, $projectId]);
        }
    }

    private function projectVisibilitySql(string $projectColumn = 'id'): string
    {
        $roleName = strtolower(trim((string)($_SESSION['user']['role_name'] ?? '')));
        if (in_array($roleName, ['super admin', 'superadministrator', 'super administrator'], true)) {
            return '1 = 1';
        }
        return '(NOT EXISTS (SELECT 1 FROM role_project_access rpa WHERE rpa.company_id = projects.company_id AND rpa.role_id = ?)
            OR EXISTS (SELECT 1 FROM role_project_access rpa WHERE rpa.company_id = projects.company_id AND rpa.role_id = ? AND rpa.project_id = ' . $projectColumn . '))';
    }

    private function projectVisibilityParams(): array
    {
        $roleName = strtolower(trim((string)($_SESSION['user']['role_name'] ?? '')));
        if (in_array($roleName, ['super admin', 'superadministrator', 'super administrator'], true)) {
            return [];
        }
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);
        return [$roleId, $roleId];
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

    public function getQuantitySurveyorProjects(int $employeeId): array
    {
        return $this->query(
            'SELECT DISTINCT p.* FROM projects p INNER JOIN project_assignments a ON a.project_id = p.id AND a.company_id = p.company_id WHERE p.company_id = ? AND p.deleted_at IS NULL AND a.employee_id = ? AND LOWER(TRIM(a.job_title)) IN ("quantity surveyor", "site quantity surveyor") ORDER BY p.name ASC, p.id ASC',
            [$this->currentCompanyId(), $employeeId]
        )->fetchAll();
    }

    public function isQuantitySurveyorForProject(int $employeeId, int $projectId): bool
    {
        return (bool)$this->query(
            'SELECT 1 FROM project_assignments a INNER JOIN projects p ON p.id = a.project_id AND p.company_id = a.company_id WHERE a.company_id = ? AND a.employee_id = ? AND a.project_id = ? AND p.deleted_at IS NULL AND LOWER(TRIM(a.job_title)) IN ("quantity surveyor", "site quantity surveyor") LIMIT 1',
            [$this->currentCompanyId(), $employeeId, $projectId]
        )->fetchColumn();
    }

    public function getProjectSchedule(int $projectId): array
    {
        return $this->query(
            'SELECT * FROM project_schedule WHERE project_id = ? AND company_id = ? ORDER BY start_date ASC, end_date ASC, id ASC',
            [$projectId, $this->currentCompanyId()]
        )->fetchAll();
    }

    public function saveSchedule(array $data): int
    {
        $scheduleId = (int)($data['schedule_id'] ?? 0);
        $projectId = (int)($data['project_id'] ?? 0);
        $taskName = trim((string)($data['task_name'] ?? ''));
        $startDate = trim((string)($data['schedule_start_date'] ?? ''));
        $endDate = trim((string)($data['schedule_end_date'] ?? ''));
        $status = trim((string)($data['schedule_status'] ?? 'planned'));
        $progress = max(0, min(100, (int)($data['schedule_progress_percent'] ?? 0)));
        if (!$this->getProjectById($projectId) || $taskName === '' || $startDate === '' || $endDate === '') {
            throw new InvalidArgumentException('Project, task name, and schedule dates are required.');
        }
        if ($endDate < $startDate) {
            throw new InvalidArgumentException('The schedule end date cannot be before the start date.');
        }
        if (!in_array($status, ['planned', 'in_progress', 'completed', 'on_hold'], true)) {
            throw new InvalidArgumentException('Invalid schedule status.');
        }
        $values = [$taskName, $startDate, $endDate, $status, $progress, trim((string)($data['assigned_to'] ?? '')) ?: null, trim((string)($data['schedule_notes'] ?? '')) ?: null];
        if ($scheduleId > 0) {
            $this->query(
                'UPDATE project_schedule SET task_name = ?, start_date = ?, end_date = ?, status = ?, progress_percent = ?, assigned_to = ?, notes = ? WHERE id = ? AND project_id = ? AND company_id = ?',
                array_merge($values, [$scheduleId, $projectId, $this->currentCompanyId()])
            );
            $this->refreshProjectProgress($projectId);
            return $scheduleId;
        }
        $this->query(
            'INSERT INTO project_schedule (company_id, project_id, task_name, start_date, end_date, status, progress_percent, assigned_to, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            array_merge([$this->currentCompanyId(), $projectId], $values)
        );
        $this->refreshProjectProgress($projectId);
        return (int)$this->db->lastInsertId();
    }

    private function applyScheduleProgress(array &$project): void
    {
        $scheduleProgress = $this->query(
            'SELECT ROUND(SUM((DATEDIFF(end_date, start_date) + 1) * progress_percent) / NULLIF(SUM(DATEDIFF(end_date, start_date) + 1), 0)) AS progress_percent, COUNT(*) AS activity_count FROM project_schedule WHERE project_id = ? AND company_id = ?',
            [(int)$project['id'], $this->currentCompanyId()]
        )->fetch();
        if ((int)($scheduleProgress['activity_count'] ?? 0) > 0) {
            $project['progress_percent'] = max(0, min(100, (int)$scheduleProgress['progress_percent']));
            $project['progress_from_schedule'] = true;
        }
    }

    private function refreshProjectProgress(int $projectId): void
    {
        $project = $this->query('SELECT id FROM projects WHERE id = ? AND company_id = ? AND deleted_at IS NULL LIMIT 1', [$projectId, $this->currentCompanyId()])->fetch();
        if (!$project) {
            return;
        }
        $scheduleProgress = $this->query(
            'SELECT ROUND(SUM((DATEDIFF(end_date, start_date) + 1) * progress_percent) / NULLIF(SUM(DATEDIFF(end_date, start_date) + 1), 0)) AS progress_percent, COUNT(*) AS activity_count FROM project_schedule WHERE project_id = ? AND company_id = ?',
            [$projectId, $this->currentCompanyId()]
        )->fetch();
        if ((int)($scheduleProgress['activity_count'] ?? 0) > 0) {
            $this->query('UPDATE projects SET progress_percent = ? WHERE id = ? AND company_id = ?', [(int)$scheduleProgress['progress_percent'], $projectId, $this->currentCompanyId()]);
        }
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
        $clientId = (int)($data['client_id'] ?? 0);
        $startDate = trim((string)($data['start_date'] ?? ''));
        $endDate = trim((string)($data['end_date'] ?? ''));
        $location = trim((string)($data['site_location'] ?? ''));
        $status = trim((string)($data['status'] ?? 'planned'));
        if ($projectNumber === '' || $name === '' || $startDate === '' || $endDate === '' || $location === '') {
            throw new InvalidArgumentException('Project number, name, dates, and site location are required.');
        }
        $customer = $clientId > 0
            ? $this->query('SELECT id, company_name FROM customers WHERE id = ? LIMIT 1', [$clientId])->fetch()
            : false;
        if (!$customer) {
            throw new InvalidArgumentException('Select a valid customer for this project.');
        }
        if (!in_array($status, ['planned', 'in_progress', 'completed', 'on_hold', 'cancelled'], true)) {
            throw new InvalidArgumentException('Invalid project status.');
        }

        $values = [
            $projectNumber,
            $name,
            $clientId,
            (string)$customer['company_name'],
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

    public function getCustomers(): array
    {
        return $this->query('SELECT id, company_name FROM customers WHERE status = "active" ORDER BY company_name ASC')->fetchAll();
    }

    public function createCustomer(array $data): array
    {
        $companyName = trim((string)($data['company_name'] ?? ''));
        $contactPerson = trim((string)($data['contact_person'] ?? ''));
        $email = trim((string)($data['email'] ?? ''));
        $phone = trim((string)($data['phone'] ?? ''));
        $address = trim((string)($data['address'] ?? ''));
        if ($companyName === '' || $contactPerson === '' || $email === '' || $phone === '') {
            throw new InvalidArgumentException('Client name, contact person, email, and phone are required.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Enter a valid client email address.');
        }

        $customerCode = 'CLI-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(3)));
        $this->query(
            'INSERT INTO customers (customer_code, company_name, contact_person, email, phone, address, balance, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 0, "active", NOW())',
            [$customerCode, $companyName, $contactPerson, $email, $phone, $address !== '' ? $address : null]
        );
        return [
            'id' => (int)$this->db->lastInsertId(),
            'company_name' => $companyName,
        ];
    }
}
