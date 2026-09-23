<?php
declare(strict_types=1);

require_once APP_ROOT . '/core/Model.php';

class RoleModel extends Model
{
    public function __construct()
    {
        parent::__construct();
        $this->query(
            'CREATE TABLE IF NOT EXISTS workflow_settings (
                company_id INT PRIMARY KEY,
                management_level_count INT NOT NULL DEFAULT 3
            )'
        );
    }

    public static function defaultRoleNames(): array
    {
        return [
            'Super Admin',
            'Admin',
            'Managing Director',
            'Finance Manager',
            'HR Manager',
            'Project Manager',
            'Site Engineer',
            'Procurement Officer',
            'Logistics Officer',
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

        $existing = $this->getRoleIdByName($trimmed);
        if ($existing !== null) {
            return $existing;
        }

        $this->query(
            'INSERT INTO roles (name, description, created_at) VALUES (?, ?, NOW())',
            [$trimmed, $description]
        );

        return (int)$this->db->lastInsertId();
    }

    public function getRoleIdByName(string $name): ?int
    {
        $roleName = trim($name);
        if ($roleName === '') {
            return null;
        }

        $stmt = $this->query('SELECT id FROM roles WHERE LOWER(name) = LOWER(?) LIMIT 1', [$roleName]);
        $row = $stmt->fetch();
        return $row ? (int)$row['id'] : null;
    }

    public function getRoleNameById(int $id): string
    {
        $stmt = $this->query('SELECT name FROM roles WHERE id = ? LIMIT 1', [$id]);
        $row = $stmt->fetch();
        return $row ? (string)$row['name'] : '';
    }

    public function getRoles(): array
    {
        $this->ensureStandardRoleSet();
        return $this->query(
            'SELECT r.id, r.company_id, r.name, r.description
             FROM roles r
             WHERE (r.company_id IS NULL OR r.company_id = ?)
               AND NOT EXISTS (
                   SELECT 1 FROM roles duplicate
                   WHERE LOWER(TRIM(duplicate.name)) = LOWER(TRIM(r.name))
                     AND (duplicate.company_id IS NULL OR duplicate.company_id = ?)
                     AND (
                         (duplicate.company_id = ? AND r.company_id IS NULL)
                         OR (duplicate.company_id = r.company_id AND duplicate.id < r.id)
                     )
               )
             ORDER BY r.name ASC, r.id ASC',
            [$this->currentCompanyId(), $this->currentCompanyId(), $this->currentCompanyId()]
        )->fetchAll();
    }

    public function createCompanyRole(string $name, string $description = ''): int
    {
        $trimmed = trim($name);
        if ($trimmed === '') {
            throw new InvalidArgumentException('Role name cannot be empty.');
        }

        $existing = $this->query(
            'SELECT id FROM roles WHERE company_id = ? AND LOWER(name) = LOWER(?) LIMIT 1',
            [$this->currentCompanyId(), $trimmed]
        )->fetchColumn();
        if ($existing !== false) {
            return (int)$existing;
        }

        $this->query(
            'INSERT INTO roles (company_id, name, description, created_at) VALUES (?, ?, ?, NOW())',
            [$this->currentCompanyId(), $trimmed, trim($description)]
        );
        return (int)$this->db->lastInsertId();
    }

    public function getManagementRoles(): array
    {
        return $this->query(
            'SELECT DISTINCT r.id, r.company_id, r.name, r.created_at
             FROM roles r
             INNER JOIN workflow_role_links link ON link.role_id = r.id AND link.company_id = ?
             INNER JOIN workflow_levels level ON level.id = link.level_id AND level.company_id = link.company_id
             WHERE r.company_id = ? AND level.sort_order <= COALESCE((
                 SELECT management_level_count FROM workflow_settings settings WHERE settings.company_id = ? LIMIT 1
             ), 3)
             ORDER BY level.sort_order ASC, r.name ASC, r.id ASC',
            [$this->currentCompanyId(), $this->currentCompanyId(), $this->currentCompanyId()]
        )->fetchAll();
    }

    public function deleteRole(int $roleId): void
    {
        if ($roleId <= 0) {
            throw new InvalidArgumentException('Select a valid role.');
        }
        $role = $this->query('SELECT id, company_id, name FROM roles WHERE id = ? AND (company_id IS NULL OR company_id = ?) LIMIT 1', [$roleId, $this->currentCompanyId()])->fetch();
        if (!$role) {
            throw new InvalidArgumentException('The selected role could not be found.');
        }
        if (in_array(strtolower(trim((string)$role['name'])), ['super admin', 'superadministrator', 'super administrator'], true)) {
            throw new InvalidArgumentException('The Super Admin role cannot be deleted.');
        }
        if ($this->query('SELECT 1 FROM users WHERE role_id = ? LIMIT 1', [$roleId])->fetchColumn()) {
            throw new InvalidArgumentException('This role is assigned to a user and cannot be deleted.');
        }
        $this->query('DELETE FROM roles WHERE id = ?', [$roleId]);
    }

    public function deleteManagementRole(int $roleId): void
    {
        $this->deleteRole($roleId);
    }
}
