<?php
declare(strict_types=1);

require_once APP_ROOT . '/core/Model.php';

class RoleModel extends Model
{
    public function __construct()
    {
        parent::__construct();
        $this->query(
            'CREATE TABLE IF NOT EXISTS management_roles (
                id INT PRIMARY KEY AUTO_INCREMENT,
                company_id INT NULL,
                name VARCHAR(100) NOT NULL UNIQUE,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )'
        );
        $this->query('ALTER TABLE roles ADD COLUMN IF NOT EXISTS company_id INT NULL AFTER id');
        try {
            $this->query('ALTER TABLE roles DROP INDEX name');
        } catch (Throwable $exception) {
        }
        try {
            $this->query('ALTER TABLE roles ADD UNIQUE KEY unique_role_company_name (company_id, name)');
        } catch (Throwable $exception) {
        }
        $this->query('ALTER TABLE users MODIFY COLUMN role_id INT NULL');
        $this->query('ALTER TABLE management_roles ADD COLUMN IF NOT EXISTS company_id INT NULL AFTER id');
        foreach (['name', 'unique_management_role_name'] as $indexName) {
            try {
                $this->query('ALTER TABLE management_roles DROP INDEX ' . $indexName);
            } catch (Throwable $exception) {
            }
        }
        try {
            $this->query('ALTER TABLE management_roles ADD UNIQUE KEY unique_management_role_company (company_id, name)');
        } catch (Throwable $exception) {
        }
        foreach (['General Manager', 'Deputy General Manager', 'Head of Department', 'Manager'] as $name) {
            $this->query('INSERT IGNORE INTO management_roles (company_id, name) VALUES (NULL, ?)', [$name]);
        }
    }

    public static function defaultRoleNames(): array
    {
        return [
            'Super Admin',
            'Admin',
            'Managing Director',
            'Finance Manager',
            'HR Manager',
            'Site Engineer',
            'Site Quantity Surveyor',
            'Procurement Officer',
            'Logistics Officer',
            'Head Store Keeper',
            'Accountant',
            'Staff',
            'Department Head',
            'Human Resource Manager',
            'General Manager',
        ];
    }

    public function ensureStandardRoleSet(): void
    {
        foreach (self::defaultRoleNames() as $roleName) {
            $this->createRoleIfMissing($roleName, $roleName . ' access role');
        }
    }

    public function createRoleIfMissing(string $name, string $description = ''): int
    {
        $trimmed = trim($name);
        if ($trimmed === '') {
            throw new InvalidArgumentException('Role name cannot be empty.');
        }

        $isDefault = in_array($trimmed, self::defaultRoleNames(), true);
        $existing = $this->getRoleIdByName($trimmed);
        if ($existing !== null) {
            return $existing;
        }

        $this->query(
            'INSERT INTO roles (company_id, name, description, created_at) VALUES (?, ?, ?, NOW())',
            [$isDefault ? null : $this->currentCompanyId(), $trimmed, $description]
        );

        return (int)$this->db->lastInsertId();
    }

    public function createCompanyRole(string $name, string $description = ''): int
    {
        $trimmed = trim($name);
        if ($trimmed === '') {
            throw new InvalidArgumentException('Role name cannot be empty.');
        }

        $companyId = $this->currentCompanyId();
        $existing = $this->query(
            'SELECT id FROM roles WHERE LOWER(name) = LOWER(?) AND company_id = ? LIMIT 1',
            [$trimmed, $companyId]
        )->fetch();
        if ($existing) {
            return (int)$existing['id'];
        }

        $this->query(
            'INSERT INTO roles (company_id, name, description, created_at) VALUES (?, ?, ?, NOW())',
            [$companyId, $trimmed, $description]
        );

        return (int)$this->db->lastInsertId();
    }

    public function getRoleIdByName(string $name): ?int
    {
        $roleName = trim($name);
        if ($roleName === '') {
            return null;
        }

        $stmt = $this->query('SELECT id FROM roles WHERE LOWER(name) = LOWER(?) AND (company_id IS NULL OR company_id = ?) ORDER BY company_id IS NOT NULL DESC LIMIT 1', [$roleName, $this->currentCompanyId()]);
        $row = $stmt->fetch();
        return $row ? (int)$row['id'] : null;
    }

    public function getRoles(): array
    {
        $stmt = $this->query('SELECT id, name, description FROM roles WHERE company_id = ? ORDER BY name ASC', [$this->currentCompanyId()]);
        return $stmt->fetchAll();
    }

    public function deleteRole(int $roleId): void
    {
        $role = $this->query('SELECT company_id, name FROM roles WHERE id = ? LIMIT 1', [$roleId])->fetch();
        if (!$role || $role['company_id'] === null || (int)$role['company_id'] !== $this->currentCompanyId()) {
            throw new InvalidArgumentException('Only roles created for this company can be deleted.');
        }
        $this->db->beginTransaction();
        try {
            $this->query('UPDATE users SET role_id = NULL WHERE role_id = ?', [$roleId]);
            $this->query('UPDATE departments SET role_id = NULL, head_role_id = NULL WHERE company_id = ? AND (role_id = ? OR head_role_id = ?)', [$this->currentCompanyId(), $roleId, $roleId]);
            $this->query('DELETE FROM department_roles WHERE company_id = ? AND role_id = ?', [$this->currentCompanyId(), $roleId]);
            $this->query('UPDATE workflow_role_links SET parent_role_id = NULL WHERE company_id = ? AND parent_role_id = ?', [$this->currentCompanyId(), $roleId]);
            $this->query('DELETE FROM workflow_role_links WHERE company_id = ? AND role_id = ?', [$this->currentCompanyId(), $roleId]);
            $this->query('DELETE FROM roles WHERE id = ? AND company_id = ?', [$roleId, $this->currentCompanyId()]);
            $this->db->commit();
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }

    public function getManagementRoles(): array
    {
        $stmt = $this->query('SELECT id, name FROM management_roles WHERE company_id = ? ORDER BY name ASC', [$this->currentCompanyId()]);
        return $stmt->fetchAll();
    }

    public function createManagementRole(string $name): void
    {
        $name = trim($name);
        if ($name === '' || strlen($name) > 100) {
            throw new InvalidArgumentException('A management role name between 1 and 100 characters is required.');
        }
        $this->query('INSERT INTO management_roles (company_id, name) VALUES (?, ?)', [$this->currentCompanyId(), $name]);
    }

    public function deleteManagementRole(int $roleId): void
    {
        $role = $this->query('SELECT company_id, name FROM management_roles WHERE id = ? LIMIT 1', [$roleId])->fetch();
        if (!$role) {
            throw new InvalidArgumentException('Management role not found.');
        }
        if ($role['company_id'] === null || (int)$role['company_id'] !== $this->currentCompanyId()) {
            throw new InvalidArgumentException('Only management roles created for this company can be deleted.');
        }
        $usedByDepartments = (int)$this->query('SELECT COUNT(*) FROM departments WHERE head_title = ?', [$role['name']])->fetchColumn();
        if ($usedByDepartments > 0) {
            throw new InvalidArgumentException('This management role is assigned to a department and cannot be deleted.');
        }
        $this->query('DELETE FROM management_roles WHERE id = ?', [$roleId]);
    }

    public function getRoleNameById(int $id): string
    {
        $stmt = $this->query('SELECT name FROM roles WHERE id = ? LIMIT 1', [$id]);
        $row = $stmt->fetch();
        return $row ? (string)$row['name'] : '';
    }
}
