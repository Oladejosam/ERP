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
    }

    public function getAll(): array
    {
        $stmt = $this->query(
            'SELECT r.*, u.name AS requester_name FROM requisitions r LEFT JOIN users u ON u.id = r.requested_by WHERE r.company_id = ? ORDER BY r.created_at DESC, r.id DESC',
            [$this->currentCompanyId()]
        );
        $requisitions = $stmt->fetchAll();
        foreach ($requisitions as &$requisition) {
            $itemStmt = $this->query('SELECT * FROM requisition_items WHERE requisition_id = ? ORDER BY id ASC', [(int)$requisition['id']]);
            $requisition['items'] = $itemStmt->fetchAll();
        }
        return $requisitions;
    }

    public function create(array $data, array $items, ?int $requestedBy): int
    {
        $title = trim((string)($data['project_title'] ?? ''));
        if ($title === '') {
            throw new InvalidArgumentException('A project title is required.');
        }
        $cleanItems = [];
        $totalValue = 0.0;
        foreach ($items as $item) {
            $quantityToPurchase = max(0, (float)($item['quantity_to_purchase'] ?? 0));
            $price = max(0, (float)($item['price'] ?? 0));
            if (trim((string)($item['description'] ?? '')) === '' && $quantityToPurchase <= 0 && $price <= 0) {
                continue;
            }
            $value = $quantityToPurchase * $price;
            $totalValue += $value;
            $cleanItems[] = [$item, $quantityToPurchase, $price, $value];
        }
        if ($cleanItems === []) {
            throw new InvalidArgumentException('Add at least one requested item.');
        }

        $this->db->beginTransaction();
        try {
            $this->query(
                'INSERT INTO requisitions (company_id, requested_by, requisition_date, title, trade, supplier, supplier_address, amount) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                [$this->currentCompanyId(), $requestedBy, $data['date'] ?: date('Y-m-d'), $title, trim((string)($data['trade'] ?? '')) ?: null, trim((string)($data['supplier'] ?? '')) ?: null, trim((string)($data['supplier_address'] ?? '')) ?: null, number_format($totalValue, 2, '.', '')]
            );
            $requisitionId = (int)$this->db->lastInsertId();
            foreach ($cleanItems as [$item, $quantityToPurchase, $price, $value]) {
                $this->query(
                    'INSERT INTO requisition_items (requisition_id, description, item_code, unit, quantity_required, quantity_in_stock, quantity_to_purchase, price, value) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                    [$requisitionId, trim((string)($item['description'] ?? '')) ?: null, trim((string)($item['code'] ?? '')) ?: null, trim((string)($item['unit'] ?? '')) ?: null, max(0, (float)($item['quantity_required'] ?? 0)), max(0, (float)($item['quantity_in_stock'] ?? 0)), $quantityToPurchase, $price, number_format($value, 2, '.', '')]
                );
            }
            $this->db->commit();
            return $requisitionId;
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }
}
