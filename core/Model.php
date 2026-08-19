<?php
/**
 * Base model for database operations.
 */
declare(strict_types=1);

class Model
{
    protected PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    protected function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    protected function currentCompanyId(): int
    {
        return max(1, (int)($_SESSION['selected_company_id'] ?? 1));
    }
}
