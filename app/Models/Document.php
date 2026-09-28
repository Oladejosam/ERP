<?php
namespace App\Models;

use PDO;

class Document {
    protected PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function getByProject(?int $projectId, int $companyId): array {
        $sql = "SELECT d.*, p.name AS project_name, p.project_number 
                FROM documents d 
                LEFT JOIN projects p ON d.related_id = p.id 
                WHERE p.company_id = :company_id AND d.related_type = 'project_enable'";
        
        $params = [':company_id' => $companyId];
        if ($projectId) {
            $sql .= " AND d.related_id = :project_id";
            $params[':project_id'] = $projectId;
        }
        $sql .= " ORDER BY d.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id, int $companyId): ?array {
        $sql = "SELECT d.*, p.company_id 
                FROM documents d
                LEFT JOIN projects p ON d.related_id = p.id
                WHERE d.id = :id AND (p.company_id = :company_id OR d.related_id IS NULL)
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id, ':company_id' => $companyId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function create(array $data): int {
        $sql = "INSERT INTO documents (title, file_name, category, related_type, related_id, uploaded_by, created_at) 
                VALUES (:title, :file_name, :category, :related_type, :related_id, :uploaded_by, NOW())";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':title' => $data['title'],
            ':file_name' => $data['file_name'],
            ':category' => $data['category'],
            ':related_type' => $data['related_type'] ?? 'project_enable',
            ':related_id' => $data['related_id'],
            ':uploaded_by' => $data['uploaded_by']
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM documents WHERE id = :id LIMIT 1");
        return $stmt->execute([':id' => $id]);
    }
}
