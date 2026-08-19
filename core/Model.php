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
}
