<?php
declare(strict_types=1);

require_once APP_ROOT . '/core/Model.php';

class WorkflowModel extends Model
{
    public function __construct()
    {
        parent::__construct();
        $this->query(
            'CREATE TABLE IF NOT EXISTS workflow_levels (
                id INT PRIMARY KEY AUTO_INCREMENT,
                company_id INT NOT NULL,
                name VARCHAR(100) NOT NULL,
                sort_order INT NOT NULL DEFAULT 0,
                UNIQUE KEY unique_workflow_level_company (company_id, name)
            )'
        );
        $this->query(
            'CREATE TABLE IF NOT EXISTS workflow_role_links (
                company_id INT NOT NULL,
                role_id INT NOT NULL,
                parent_role_id INT NULL,
                level_id INT NULL,
                PRIMARY KEY (company_id, role_id)
            )'
        );
        $this->query('ALTER TABLE workflow_role_links ADD COLUMN IF NOT EXISTS level_id INT NULL AFTER parent_role_id');
    }

    public function getRoles(): array
    {
        return $this->query(
            'SELECT id, name FROM roles WHERE company_id = ? ORDER BY name ASC',
            [$this->currentCompanyId()]
        )->fetchAll();
    }

    public function getParentLinks(): array
    {
        $rows = $this->query(
            'SELECT role_id, parent_role_id FROM workflow_role_links WHERE company_id = ?',
            [$this->currentCompanyId()]
        )->fetchAll();
        $links = [];
        foreach ($rows as $row) {
            $links[(int)$row['role_id']] = $row['parent_role_id'] === null ? null : (int)$row['parent_role_id'];
        }
        return $links;
    }

    public function getRoleLevels(): array
    {
        $rows = $this->query('SELECT role_id, level_id FROM workflow_role_links WHERE company_id = ?', [$this->currentCompanyId()])->fetchAll();
        $levels = [];
        foreach ($rows as $row) {
            $levels[(int)$row['role_id']] = $row['level_id'] === null ? null : (int)$row['level_id'];
        }
        return $levels;
    }

    public function getLevels(): array
    {
        return $this->query('SELECT id, name, sort_order FROM workflow_levels WHERE company_id = ? ORDER BY sort_order ASC, name ASC', [$this->currentCompanyId()])->fetchAll();
    }

    public function createLevel(string $name): void
    {
        $name = trim($name);
        if ($name === '' || strlen($name) > 100) {
            throw new InvalidArgumentException('A level name between 1 and 100 characters is required.');
        }
        $companyId = $this->currentCompanyId();
        $existing = $this->query('SELECT id FROM workflow_levels WHERE company_id = ? AND LOWER(name) = LOWER(?) LIMIT 1', [$companyId, $name])->fetch();
        if ($existing) {
            throw new InvalidArgumentException('A level with this name already exists for this company.');
        }
        $sortOrder = (int)$this->query('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM workflow_levels WHERE company_id = ?', [$companyId])->fetchColumn();
        $this->query('INSERT INTO workflow_levels (company_id, name, sort_order) VALUES (?, ?, ?)', [$companyId, $name, $sortOrder]);
    }

    public function deleteLevel(int $levelId): void
    {
        $companyId = $this->currentCompanyId();
        if (!$this->query('SELECT id FROM workflow_levels WHERE id = ? AND company_id = ?', [$levelId, $companyId])->fetch()) {
            throw new InvalidArgumentException('Level not found for this company.');
        }
        $this->query('UPDATE workflow_role_links SET level_id = NULL WHERE company_id = ? AND level_id = ?', [$companyId, $levelId]);
        $this->query('DELETE FROM workflow_levels WHERE id = ? AND company_id = ?', [$levelId, $companyId]);
    }

    public function saveParentLinks(array $submittedLinks, array $submittedLevels = []): void
    {
        $companyId = $this->currentCompanyId();
        $roleRows = $this->query('SELECT id FROM roles WHERE company_id = ?', [$companyId])->fetchAll();
        $roleIds = array_map(static fn (array $row): int => (int)$row['id'], $roleRows);
        $roleIdSet = array_fill_keys($roleIds, true);
        $levelRows = $this->query('SELECT id FROM workflow_levels WHERE company_id = ?', [$companyId])->fetchAll();
        $levelIdSet = array_fill_keys(array_map(static fn (array $row): int => (int)$row['id'], $levelRows), true);
        $links = [];

        foreach ($roleIds as $roleId) {
            $parentRoleId = (int)($submittedLinks[$roleId] ?? 0);
            if ($parentRoleId === 0) {
                $parentRoleId = null;
            } elseif ($parentRoleId === $roleId || !isset($roleIdSet[$parentRoleId])) {
                throw new InvalidArgumentException('Each role must have a valid parent role or no parent.');
            }
            $levelId = (int)($submittedLevels[$roleId] ?? 0);
            if ($levelId !== 0 && !isset($levelIdSet[$levelId])) {
                throw new InvalidArgumentException('Each role must use a valid level.');
            }
            $links[$roleId] = [$parentRoleId, $levelId > 0 ? $levelId : null];
        }

        foreach ($roleIds as $roleId) {
            $visited = [];
            $current = $roleId;
            while ($current !== null) {
                if (isset($visited[$current])) {
                    throw new InvalidArgumentException('The organogram cannot contain circular reporting lines.');
                }
                $visited[$current] = true;
                $current = $links[$current][0] ?? null;
            }
        }

        $this->db->beginTransaction();
        try {
            $this->query('DELETE FROM workflow_role_links WHERE company_id = ?', [$companyId]);
            foreach ($links as $roleId => [$parentRoleId, $levelId]) {
                $this->query(
                    'INSERT INTO workflow_role_links (company_id, role_id, parent_role_id, level_id) VALUES (?, ?, ?, ?)',
                    [$companyId, $roleId, $parentRoleId, $levelId]
                );
            }
            $this->db->commit();
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }
}