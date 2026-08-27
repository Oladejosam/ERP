<?php
declare(strict_types=1);

require_once APP_ROOT . '/core/Model.php';

class ContractAdminModel extends Model
{
    public function __construct()
    {
        parent::__construct();
        $this->ensureTables();
    }

    private function ensureTables(): void
    {
        $this->query(
            'CREATE TABLE IF NOT EXISTS contract_admin_contracts (
                id INT PRIMARY KEY AUTO_INCREMENT,
                company_id INT NOT NULL,
                contract_number VARCHAR(80) NOT NULL,
                title VARCHAR(180) NOT NULL,
                client_name VARCHAR(180) NOT NULL,
                contractor_name VARCHAR(180) DEFAULT NULL,
                procurement_route VARCHAR(80) NOT NULL DEFAULT "Open tender",
                status ENUM("opportunity","tendering","under_evaluation","awarded","active","completed","unsuccessful","cancelled") NOT NULL DEFAULT "opportunity",
                estimated_value DECIMAL(14,2) NOT NULL DEFAULT 0.00,
                awarded_value DECIMAL(14,2) NOT NULL DEFAULT 0.00,
                bid_deadline DATE DEFAULT NULL,
                award_date DATE DEFAULT NULL,
                commencement_date DATE DEFAULT NULL,
                completion_date DATE DEFAULT NULL,
                description TEXT DEFAULT NULL,
                created_by INT DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_contract_company_number (company_id, contract_number)
            )'
        );
        $this->query(
            'CREATE TABLE IF NOT EXISTS contract_admin_events (
                id INT PRIMARY KEY AUTO_INCREMENT,
                company_id INT NOT NULL,
                contract_id INT NOT NULL,
                event_type VARCHAR(80) NOT NULL,
                event_date DATE NOT NULL,
                notes TEXT NOT NULL,
                created_by INT DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (contract_id) REFERENCES contract_admin_contracts(id) ON DELETE CASCADE
            )'
        );
        $this->query(
            'CREATE TABLE IF NOT EXISTS contract_admin_documents (
                id INT PRIMARY KEY AUTO_INCREMENT,
                company_id INT NOT NULL,
                contract_id INT NOT NULL,
                document_date DATE NOT NULL,
                label VARCHAR(150) NOT NULL,
                original_name VARCHAR(255) NOT NULL,
                stored_name VARCHAR(255) NOT NULL,
                file_type VARCHAR(100) NOT NULL,
                file_size INT NOT NULL DEFAULT 0,
                created_by INT DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (contract_id) REFERENCES contract_admin_contracts(id) ON DELETE CASCADE
            )'
        );
    }

    public function getContracts(): array
    {
        return $this->query(
            'SELECT * FROM contract_admin_contracts WHERE company_id = ? ORDER BY updated_at DESC, id DESC',
            [$this->currentCompanyId()]
        )->fetchAll();
    }

    public function getEvents(int $contractId): array
    {
        return $this->query(
            'SELECT * FROM contract_admin_events WHERE contract_id = ? AND company_id = ? ORDER BY event_date DESC, id DESC',
            [$contractId, $this->currentCompanyId()]
        )->fetchAll();
    }

    public function getDocuments(int $contractId): array
    {
        return $this->query(
            'SELECT * FROM contract_admin_documents WHERE contract_id = ? AND company_id = ? ORDER BY document_date DESC, id DESC',
            [$contractId, $this->currentCompanyId()]
        )->fetchAll();
    }

    public function getDocument(int $documentId): ?array
    {
        $stmt = $this->query(
            'SELECT * FROM contract_admin_documents WHERE id = ? AND company_id = ? LIMIT 1',
            [$documentId, $this->currentCompanyId()]
        );
        return $stmt->fetch() ?: null;
    }

    public function addDocument(int $contractId, string $documentDate, string $label, string $originalName, string $storedName, string $fileType, int $fileSize, ?int $userId = null): void
    {
        $contract = $this->query('SELECT id FROM contract_admin_contracts WHERE id = ? AND company_id = ? LIMIT 1', [$contractId, $this->currentCompanyId()])->fetch();
        if (!$contract || trim($documentDate) === '' || trim($label) === '') {
            throw new InvalidArgumentException('A valid contract, document date, and label are required.');
        }
        $this->query(
            'INSERT INTO contract_admin_documents (company_id, contract_id, document_date, label, original_name, stored_name, file_type, file_size, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$this->currentCompanyId(), $contractId, $documentDate, trim($label), $originalName, $storedName, $fileType, $fileSize, $userId]
        );
    }

    public function saveContract(array $data, ?int $userId = null): int
    {
        $id = (int)($data['contract_id'] ?? 0);
        $number = trim((string)($data['contract_number'] ?? ''));
        $title = trim((string)($data['title'] ?? ''));
        $client = trim((string)($data['client_name'] ?? ''));
        $status = trim((string)($data['status'] ?? 'opportunity'));
        $allowedStatuses = ['opportunity', 'tendering', 'under_evaluation', 'awarded', 'active', 'completed', 'unsuccessful', 'cancelled'];
        if ($number === '' || $title === '' || $client === '') {
            throw new InvalidArgumentException('Contract number, title, and client are required.');
        }
        if (!in_array($status, $allowedStatuses, true)) {
            throw new InvalidArgumentException('The selected contract status is not valid.');
        }

        $values = [
            $number, $title, $client, trim((string)($data['contractor_name'] ?? '')) ?: null,
            trim((string)($data['procurement_route'] ?? 'Open tender')) ?: 'Open tender', $status,
            (float)($data['estimated_value'] ?? 0), (float)($data['awarded_value'] ?? 0),
            $this->nullableDate($data['bid_deadline'] ?? null), $this->nullableDate($data['award_date'] ?? null),
            $this->nullableDate($data['commencement_date'] ?? null), $this->nullableDate($data['completion_date'] ?? null),
            trim((string)($data['description'] ?? '')) ?: null,
        ];
        if ($id > 0) {
            $values[] = $id;
            $values[] = $this->currentCompanyId();
            $this->query(
                'UPDATE contract_admin_contracts SET contract_number = ?, title = ?, client_name = ?, contractor_name = ?, procurement_route = ?, status = ?, estimated_value = ?, awarded_value = ?, bid_deadline = ?, award_date = ?, commencement_date = ?, completion_date = ?, description = ? WHERE id = ? AND company_id = ?',
                $values
            );
            return $id;
        }

        $values[] = $userId;
        $this->query(
            'INSERT INTO contract_admin_contracts (company_id, contract_number, title, client_name, contractor_name, procurement_route, status, estimated_value, awarded_value, bid_deadline, award_date, commencement_date, completion_date, description, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            array_merge([$this->currentCompanyId()], $values)
        );
        return (int)$this->db->lastInsertId();
    }

    public function addEvent(int $contractId, array $data, ?int $userId = null): void
    {
        $contract = $this->query('SELECT id FROM contract_admin_contracts WHERE id = ? AND company_id = ? LIMIT 1', [$contractId, $this->currentCompanyId()])->fetch();
        $eventType = trim((string)($data['event_type'] ?? ''));
        $eventDate = $this->nullableDate($data['event_date'] ?? null);
        $notes = trim((string)($data['notes'] ?? ''));
        if (!$contract || $eventType === '' || $eventDate === null || $notes === '') {
            throw new InvalidArgumentException('A valid contract, event type, date, and note are required.');
        }
        $this->query(
            'INSERT INTO contract_admin_events (company_id, contract_id, event_type, event_date, notes, created_by) VALUES (?, ?, ?, ?, ?, ?)',
            [$this->currentCompanyId(), $contractId, $eventType, $eventDate, $notes, $userId]
        );
    }

    private function nullableDate($value): ?string
    {
        $date = trim((string)$value);
        return $date !== '' ? $date : null;
    }
}
