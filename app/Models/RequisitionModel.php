<?php
declare(strict_types=1);

require_once APP_ROOT . '/core/Model.php';

class RequisitionModel extends Model
{
    public function __construct()
    {
        parent::__construct();
        $this->ensureTable();
    }

    private function ensureTable(): void
    {
        $this->query(
            'CREATE TABLE IF NOT EXISTS requisitions (
                id INT PRIMARY KEY AUTO_INCREMENT,
                company_id INT NOT NULL,
                requested_by INT NULL,
                requisition_date DATE NOT NULL,
                title VARCHAR(150) NOT NULL,
                trade VARCHAR(100) NULL,
                supplier VARCHAR(150) NULL,
                supplier_address VARCHAR(255) NULL,
                description TEXT NULL,
                item_code VARCHAR(80) NULL,
                unit VARCHAR(50) NULL,
                quantity_required DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                quantity_in_stock DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                quantity_to_purchase DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                value DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                status ENUM("pending", "approved", "rejected") NOT NULL DEFAULT "pending",
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )'
        );
        $columns = [
            'requisition_date' => 'DATE NULL', 'trade' => 'VARCHAR(100) NULL', 'supplier' => 'VARCHAR(150) NULL',
            'supplier_address' => 'VARCHAR(255) NULL', 'item_code' => 'VARCHAR(80) NULL', 'unit' => 'VARCHAR(50) NULL',
            'quantity_required' => 'DECIMAL(12,2) NOT NULL DEFAULT 0.00', 'quantity_in_stock' => 'DECIMAL(12,2) NOT NULL DEFAULT 0.00',
            'quantity_to_purchase' => 'DECIMAL(12,2) NOT NULL DEFAULT 0.00', 'price' => 'DECIMAL(12,2) NOT NULL DEFAULT 0.00',
            'value' => 'DECIMAL(12,2) NOT NULL DEFAULT 0.00',
        ];
        foreach ($columns as $column => $definition) {
            $this->query('ALTER TABLE requisitions ADD COLUMN IF NOT EXISTS ' . $column . ' ' . $definition);
        }
        $this->query('UPDATE requisitions SET requisition_date = COALESCE(requisition_date, DATE(created_at)) WHERE requisition_date IS NULL');
        $this->query(
            'CREATE TABLE IF NOT EXISTS requisition_items (
                id INT PRIMARY KEY AUTO_INCREMENT,
                requisition_id INT NOT NULL,
                description TEXT NULL,
                item_code VARCHAR(80) NULL,
                unit VARCHAR(50) NULL,
                quantity_required DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                quantity_in_stock DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                quantity_to_purchase DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                value DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )'
        );
        $this->query(
            'CREATE TABLE IF NOT EXISTS requisition_participants (
                requisition_id INT NOT NULL,
                company_id INT NOT NULL,
                user_id INT NOT NULL,
                PRIMARY KEY (requisition_id, user_id)
            )'
        );
        $this->query(
            'CREATE TABLE IF NOT EXISTS requisition_messages (
                id INT PRIMARY KEY AUTO_INCREMENT,
                requisition_id INT NOT NULL,
                company_id INT NOT NULL,
                user_id INT NOT NULL,
                message TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )'
        );
        $this->query('ALTER TABLE requisitions ADD COLUMN IF NOT EXISTS chat_closed_at DATETIME NULL');
    }

    public function getAll(): array
    {
        $stmt = $this->query(
            'SELECT r.*, u.name AS requester_name FROM requisitions r LEFT JOIN users u ON u.id = r.requested_by WHERE r.company_id = ? ORDER BY r.created_at DESC, r.id DESC',
            [$this->currentCompanyId()]
        );
        $requisitions = $stmt->fetchAll();
        foreach ($requisitions as &$requisition) {
            $items = $this->query('SELECT * FROM requisition_items WHERE requisition_id = ? ORDER BY id ASC', [(int)$requisition['id']])->fetchAll();
            $requisition['items'] = $items;
        }
        return $requisitions;
    }

    public function create(array $data, ?int $requestedBy): int
    {
        $title = trim((string)($data['project_title'] ?? ''));
        if ($title === '') {
            throw new InvalidArgumentException('A project title is required.');
        }
        $items = (array)($data['items'] ?? []);
        if ($items === []) {
            throw new InvalidArgumentException('Add at least one requested item.');
        }
        $this->db->beginTransaction();
        try {
            $this->query(
                'INSERT INTO requisitions (company_id, requested_by, requisition_date, title, trade, supplier, supplier_address, amount) VALUES (?, ?, ?, ?, ?, ?, ?, 0)',
                [$this->currentCompanyId(), $requestedBy, $data['date'] ?: date('Y-m-d'), $title, trim((string)($data['trade'] ?? '')) ?: null, trim((string)($data['supplier'] ?? '')) ?: null, trim((string)($data['supplier_address'] ?? '')) ?: null]
            );
            $requisitionId = (int)$this->db->lastInsertId();
            $participantIds = array_values(array_unique(array_filter(array_map('intval', (array)($data['participant_ids'] ?? [])), static fn (int $id): bool => $id > 0)));
            $participantIds[] = (int)$requestedBy;
            $participantIds = array_values(array_unique($participantIds));
            foreach ($participantIds as $participantId) {
                $this->query(
                    'INSERT IGNORE INTO requisition_participants (requisition_id, company_id, user_id) SELECT ?, ?, id FROM users WHERE id = ? AND (company_id = ? OR company_id IS NULL)',
                    [$requisitionId, $this->currentCompanyId(), $participantId, $this->currentCompanyId()]
                );
            }
            if ($requestedBy !== null) {
                $this->query('INSERT INTO requisition_messages (requisition_id, company_id, user_id, message) VALUES (?, ?, ?, ?)', [$requisitionId, $this->currentCompanyId(), $requestedBy, 'Requisition discussion started.']);
            }
            $total = 0.0;
            foreach ($items as $item) {
                $quantityToPurchase = max(0, (float)($item['quantity_to_purchase'] ?? 0));
                $price = max(0, (float)($item['price'] ?? 0));
                $value = $quantityToPurchase * $price;
                $total += $value;
                $this->query(
                    'INSERT INTO requisition_items (requisition_id, description, item_code, unit, quantity_required, quantity_in_stock, quantity_to_purchase, price, value) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                    [$requisitionId, trim((string)($item['description'] ?? '')) ?: null, trim((string)($item['code'] ?? '')) ?: null, trim((string)($item['unit'] ?? '')) ?: null, max(0, (float)($item['quantity_required'] ?? 0)), max(0, (float)($item['quantity_in_stock'] ?? 0)), $quantityToPurchase, $price, number_format($value, 2, '.', '')]
                );
            }
            $this->query('UPDATE requisitions SET amount = ?, value = ? WHERE id = ?', [number_format($total, 2, '.', ''), number_format($total, 2, '.', ''), $requisitionId]);
            $this->db->commit();
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
        return $requisitionId;
    }

    public function getById(int $requisitionId): ?array
    {
        $this->query('UPDATE requisitions SET chat_closed_at = COALESCE(chat_closed_at, NOW()) WHERE id = ? AND company_id = ? AND status IN ("approved", "rejected")', [$requisitionId, $this->currentCompanyId()]);
        $stmt = $this->query('SELECT r.*, u.name AS requester_name FROM requisitions r LEFT JOIN users u ON u.id = r.requested_by WHERE r.id = ? AND r.company_id = ? LIMIT 1', [$requisitionId, $this->currentCompanyId()]);
        $requisition = $stmt->fetch();
        if (!$requisition) {
            return null;
        }
        $requisition['items'] = $this->query('SELECT * FROM requisition_items WHERE requisition_id = ? ORDER BY id ASC', [$requisitionId])->fetchAll();
        $requisition['participants'] = $this->query('SELECT u.id, u.name, u.email FROM requisition_participants p INNER JOIN users u ON u.id = p.user_id WHERE p.requisition_id = ? AND p.company_id = ? ORDER BY u.name ASC', [$requisitionId, $this->currentCompanyId()])->fetchAll();
        $requisition['messages'] = $this->query('SELECT m.*, u.name AS author_name FROM requisition_messages m INNER JOIN users u ON u.id = m.user_id WHERE m.requisition_id = ? AND m.company_id = ? ORDER BY m.created_at ASC, m.id ASC', [$requisitionId, $this->currentCompanyId()])->fetchAll();
        return $requisition;
    }

    public function getCompanyUsers(): array
    {
        return $this->query('SELECT u.id, u.name, u.email FROM users u WHERE u.company_id = ? ORDER BY u.name ASC', [$this->currentCompanyId()])->fetchAll();
    }

    public function isParticipant(int $requisitionId, int $userId): bool
    {
        return (bool)$this->query(
            'SELECT 1 FROM requisition_participants WHERE requisition_id = ? AND company_id = ? AND user_id = ? LIMIT 1',
            [$requisitionId, $this->currentCompanyId(), $userId]
        )->fetchColumn();
    }

    public function addMessage(int $requisitionId, int $userId, string $message): void
    {
        $message = trim($message);
        if ($message === '') {
            throw new InvalidArgumentException('A message is required.');
        }
        $requisition = $this->query('SELECT status, chat_closed_at FROM requisitions WHERE id = ? AND company_id = ? LIMIT 1', [$requisitionId, $this->currentCompanyId()])->fetch();
        if (!$requisition || $requisition['chat_closed_at'] !== null || $requisition['status'] !== 'pending') {
            throw new InvalidArgumentException('This requisition discussion is closed.');
        }
        $participant = $this->query('SELECT 1 FROM requisition_participants WHERE requisition_id = ? AND company_id = ? AND user_id = ?', [$requisitionId, $this->currentCompanyId(), $userId])->fetchColumn();
        if (!$participant) {
            throw new InvalidArgumentException('You are not part of this requisition discussion.');
        }
        $this->query('INSERT INTO requisition_messages (requisition_id, company_id, user_id, message) VALUES (?, ?, ?, ?)', [$requisitionId, $this->currentCompanyId(), $userId, $message]);
    }

    public function getMessages(int $requisitionId): array
    {
        return $this->query(
            'SELECT m.id, m.message, m.created_at, u.name AS author_name FROM requisition_messages m INNER JOIN users u ON u.id = m.user_id WHERE m.requisition_id = ? AND m.company_id = ? ORDER BY m.created_at ASC, m.id ASC',
            [$requisitionId, $this->currentCompanyId()]
        )->fetchAll();
    }

    public function decide(int $requisitionId, int $userId, string $decision): void
    {
        if (!in_array($decision, ['approved', 'rejected'], true)) {
            throw new InvalidArgumentException('Invalid requisition decision.');
        }
        $companyId = $this->currentCompanyId();
        $requisition = $this->query('SELECT status FROM requisitions WHERE id = ? AND company_id = ? LIMIT 1', [$requisitionId, $companyId])->fetch();
        if (!$requisition || $requisition['status'] !== 'pending') {
            throw new InvalidArgumentException('This requisition has already been treated.');
        }
        if (!$this->isParticipant($requisitionId, $userId)) {
            throw new InvalidArgumentException('Only tagged colleagues can decide on this requisition.');
        }
        $this->query('UPDATE requisitions SET status = ?, chat_closed_at = NOW() WHERE id = ? AND company_id = ? AND status = "pending"', [$decision, $requisitionId, $companyId]);
        $this->query('INSERT INTO requisition_messages (requisition_id, company_id, user_id, message) VALUES (?, ?, ?, ?)', [$requisitionId, $companyId, $userId, 'Requisition ' . $decision . '. The discussion is now closed.']);
    }

    public function handoff(int $requisitionId, int $userId, int $nextUserId, string $note = ''): void
    {
        $companyId = $this->currentCompanyId();
        $requisition = $this->query('SELECT status FROM requisitions WHERE id = ? AND company_id = ? LIMIT 1', [$requisitionId, $companyId])->fetch();
        if (!$requisition || $requisition['status'] !== 'pending') {
            throw new InvalidArgumentException('This requisition is no longer open for handoff.');
        }
        if (!$this->isParticipant($requisitionId, $userId)) {
            throw new InvalidArgumentException('Only tagged colleagues can hand off this requisition.');
        }
        if ($nextUserId <= 0 || !$this->query('SELECT id FROM users WHERE id = ? AND company_id = ? LIMIT 1', [$nextUserId, $companyId])->fetch()) {
            throw new InvalidArgumentException('Select a valid colleague from this company.');
        }
        $this->query('INSERT IGNORE INTO requisition_participants (requisition_id, company_id, user_id) VALUES (?, ?, ?)', [$requisitionId, $companyId, $nextUserId]);
        $note = trim($note);
        $message = 'Requisition handed off to the next colleague.' . ($note !== '' ? ' Note: ' . $note : '');
        $this->query('INSERT INTO requisition_messages (requisition_id, company_id, user_id, message) VALUES (?, ?, ?, ?)', [$requisitionId, $companyId, $userId, $message]);
    }
}
