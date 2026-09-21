<?php
declare(strict_types=1);

require_once APP_ROOT . '/core/Model.php';

class RoleModel extends Model
{
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
            'SELECT id, company_id, name, description FROM roles WHERE company_id IS NULL OR company_id = ? ORDER BY name ASC, id ASC',
            [$this->currentCompanyId()]
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
            'SELECT id, company_id, name, created_at FROM management_roles WHERE company_id IS NULL OR company_id = ? ORDER BY name ASC, id ASC',
            [$this->currentCompanyId()]
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
        if ($roleId <= 0) {
            throw new InvalidArgumentException('Select a valid management role.');
        }
        $this->query('DELETE FROM management_roles WHERE id = ? AND (company_id IS NULL OR company_id = ?)', [$roleId, $this->currentCompanyId()]);
    }
}
