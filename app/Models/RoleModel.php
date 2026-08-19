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
}
