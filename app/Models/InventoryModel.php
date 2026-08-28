<?php
declare(strict_types=1);

require_once APP_ROOT . '/core/Model.php';

class InventoryModel extends Model
{
    public function __construct()
    {
        parent::__construct();
        $this->ensureInventoryTables();
    }

    private function ensureInventoryTables(): void
    {
        $this->query('CREATE TABLE IF NOT EXISTS inventory_categories (id INT PRIMARY KEY AUTO_INCREMENT, company_id INT NOT NULL DEFAULT 1, name VARCHAR(100) NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY uq_inventory_category_company_name (company_id, name))');
        $this->query('CREATE TABLE IF NOT EXISTS inventory_items (id INT PRIMARY KEY AUTO_INCREMENT, company_id INT NOT NULL DEFAULT 1, item_code VARCHAR(50) NOT NULL, name VARCHAR(150) NOT NULL, category_id INT NOT NULL, unit VARCHAR(50) NOT NULL, supplier_name VARCHAR(150) NULL, supplier_contact VARCHAR(150) NULL, supplier_phone VARCHAR(50) NULL, supplier_address VARCHAR(255) NULL, cost_price DECIMAL(12,2) DEFAULT 0.00, selling_price DECIMAL(12,2) DEFAULT 0.00, opening_stock INT DEFAULT 0, current_stock INT DEFAULT 0, reorder_level INT DEFAULT 0, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY uq_inventory_item_company_code (company_id, item_code), FOREIGN KEY (category_id) REFERENCES inventory_categories(id))');
        foreach (['supplier_name' => 'VARCHAR(150) NULL', 'supplier_contact' => 'VARCHAR(150) NULL', 'supplier_phone' => 'VARCHAR(50) NULL', 'supplier_address' => 'VARCHAR(255) NULL'] as $column => $definition) {
            $this->query('ALTER TABLE inventory_items ADD COLUMN IF NOT EXISTS ' . $column . ' ' . $definition);
        }
        $this->query('ALTER TABLE inventory_items ADD COLUMN IF NOT EXISTS free_stock INT NOT NULL DEFAULT 0 AFTER current_stock');
        $this->query('ALTER TABLE inventory_items ADD COLUMN IF NOT EXISTS allocated_stock INT NOT NULL DEFAULT 0 AFTER free_stock');
        $this->query('ALTER TABLE inventory_items ADD COLUMN IF NOT EXISTS allocation_status ENUM("free", "allocated") NOT NULL DEFAULT "free" AFTER allocated_stock');
        $this->query('ALTER TABLE inventory_items ADD COLUMN IF NOT EXISTS allocated_to VARCHAR(255) NULL AFTER allocation_status');
        $this->query('UPDATE inventory_items SET free_stock = current_stock WHERE free_stock = 0 AND allocated_stock = 0 AND current_stock > 0');
        $this->query('CREATE TABLE IF NOT EXISTS inventory_change_history (id INT PRIMARY KEY AUTO_INCREMENT, item_id INT NOT NULL, change_reason TEXT NOT NULL, before_data TEXT NOT NULL, after_data TEXT NOT NULL, changed_at DATETIME DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (item_id) REFERENCES inventory_items(id) ON DELETE CASCADE)');
        $this->query('CREATE TABLE IF NOT EXISTS inventory_issues (id INT PRIMARY KEY AUTO_INCREMENT, company_id INT NOT NULL, item_id INT NOT NULL, issued_to VARCHAR(255) NOT NULL, quantity INT NOT NULL, stock_source ENUM("free", "allocated") NOT NULL, issued_date DATE NOT NULL, issued_by INT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (item_id) REFERENCES inventory_items(id) ON DELETE CASCADE)');
        foreach (['inventory_categories', 'inventory_items'] as $table) {
            $this->query('UPDATE `' . $table . '` SET company_id = 1 WHERE company_id IS NULL');
        }
    }

    public function getCategories(): array
    {
        $stmt = $this->query('SELECT * FROM inventory_categories WHERE company_id = ? ORDER BY name ASC', [$this->currentCompanyId()]);
        return $stmt->fetchAll();
    }

    public function getItems(string $search = '', string $searchField = 'all'): array
    {
        $search = trim($search);
        $searchFields = [
            'item_code' => 'i.item_code',
            'name' => 'i.name',
            'category' => 'c.name',
            'supplier' => 'i.supplier_name',
        ];
        $sql = 'SELECT i.*, c.name AS category_name FROM inventory_items i LEFT JOIN inventory_categories c ON c.id = i.category_id WHERE i.company_id = ?';
        $params = [$this->currentCompanyId()];
        if ($search !== '') {
            $searchTerm = '%' . $search . '%';
            if (isset($searchFields[$searchField])) {
                $sql .= ' AND ' . $searchFields[$searchField] . ' LIKE ?';
                $params[] = $searchTerm;
            } else {
                $sql .= ' AND (i.item_code LIKE ? OR i.name LIKE ? OR c.name LIKE ? OR i.supplier_name LIKE ?)';
                $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
            }
        }
        $sql .= ' ORDER BY i.created_at DESC';
        $items = $this->query($sql, $params);
        return $items->fetchAll();
    }

    public function searchItems(string $search, int $limit = 10): array
    {
        $search = trim($search);
        if ($search === '') {
            return [];
        }

        $limit = max(1, min($limit, 20));
        $stmt = $this->query(
            'SELECT item_code, name, unit, current_stock, cost_price, selling_price FROM inventory_items WHERE company_id = ? AND (name LIKE ? OR item_code LIKE ?) ORDER BY name ASC LIMIT ' . $limit,
            [$this->currentCompanyId(), '%' . $search . '%', '%' . $search . '%']
        );
        return $stmt->fetchAll();
    }

    public function getItemById(int $id): ?array
    {
        $stmt = $this->query('SELECT i.*, c.name AS category_name FROM inventory_items i LEFT JOIN inventory_categories c ON c.id = i.category_id WHERE i.id = ? AND i.company_id = ? LIMIT 1', [$id, $this->currentCompanyId()]);
        return $stmt->fetch() ?: null;
    }

    public function getItemChangeHistory(int $id): array
    {
        $stmt = $this->query('SELECT * FROM inventory_change_history WHERE item_id = ? ORDER BY changed_at DESC', [$id]);
        return $stmt->fetchAll();
    }

    public function getItemIssueHistory(int $id): array
    {
        return $this->query(
            'SELECT ii.*, u.id AS issuer_user_id, COALESCE(e.id, u.id) AS issuer_id, u.name AS issuer_name FROM inventory_issues ii LEFT JOIN users u ON u.id = ii.issued_by LEFT JOIN employees e ON e.id = u.employee_id AND e.company_id = ii.company_id WHERE ii.item_id = ? AND ii.company_id = ? ORDER BY ii.issued_date DESC, ii.id DESC',
            [$id, $this->currentCompanyId()]
        )->fetchAll();
    }

    public function issueItem(int $itemId, string $issuedTo, int $quantity, string $issuedDate, string $stockSource, ?int $issuedBy): void
    {
        $issuedTo = trim($issuedTo);
        if ($issuedTo === '') {
            throw new InvalidArgumentException('Specify who or where the item is being issued to.');
        }
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Quantity issued must be greater than zero.');
        }
        if (!in_array($stockSource, ['free', 'allocated'], true)) {
            throw new InvalidArgumentException('Select a valid stock source.');
        }
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', trim($issuedDate));
        $dateErrors = DateTimeImmutable::getLastErrors();
        if ($date === false || ($dateErrors !== false && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))) {
            throw new InvalidArgumentException('Enter a valid issue date.');
        }
        $companyId = $this->currentCompanyId();
        $this->db->beginTransaction();
        try {
            $item = $this->query('SELECT name, free_stock, allocated_stock FROM inventory_items WHERE id = ? AND company_id = ? FOR UPDATE', [$itemId, $companyId])->fetch();
            if (!$item) {
                throw new InvalidArgumentException('Inventory item not found.');
            }
            if ((int)$item[$stockSource . '_stock'] < $quantity) {
                throw new InvalidArgumentException('There is not enough ' . $stockSource . ' stock available.');
            }
            $this->query('UPDATE inventory_items SET ' . $stockSource . '_stock = ' . $stockSource . '_stock - ?, current_stock = free_stock + allocated_stock WHERE id = ? AND company_id = ?', [$quantity, $itemId, $companyId]);
            $this->query('INSERT INTO inventory_issues (company_id, item_id, issued_to, quantity, stock_source, issued_date, issued_by) VALUES (?, ?, ?, ?, ?, ?, ?)', [$companyId, $itemId, $issuedTo, $quantity, $stockSource, $date->format('Y-m-d'), $issuedBy]);
            $this->query('INSERT INTO inventory_change_history (item_id, change_reason, before_data, after_data, changed_at) VALUES (?, ?, ?, ?, NOW())', [$itemId, 'Issued ' . $quantity . ' unit(s) to ' . $issuedTo . ' from ' . $stockSource . ' stock.', json_encode($item), json_encode($this->query('SELECT current_stock, free_stock, allocated_stock FROM inventory_items WHERE id = ?', [$itemId])->fetch())]);
            $this->db->commit();
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }

    public function createCategory(string $name): int
    {
        $trimmed = trim($name);
        if ($trimmed === '') {
            throw new InvalidArgumentException('Category name is required.');
        }

        $stmt = $this->query('SELECT id FROM inventory_categories WHERE LOWER(name) = LOWER(?) AND company_id = ? LIMIT 1', [$trimmed, $this->currentCompanyId()]);
        $row = $stmt->fetch();
        if ($row) {
            return (int)$row['id'];
        }

        $legacyCategory = $this->query('SELECT id FROM inventory_categories WHERE LOWER(name) = LOWER(?) LIMIT 1', [$trimmed])->fetch();
        if ($legacyCategory) {
            return (int)$legacyCategory['id'];
        }

        $this->query('INSERT INTO inventory_categories (company_id, name, created_at) VALUES (?, ?, NOW())', [$this->currentCompanyId(), $trimmed]);
        return (int)$this->db->lastInsertId();
    }

    public function createItem(array $data): int
    {
        $name = trim((string)($data['name'] ?? ''));
        $categoryName = trim((string)($data['category_name'] ?? $data['category'] ?? ''));
        if ($name === '') {
            throw new InvalidArgumentException('Item name is required.');
        }

        $categoryId = $this->createCategory($categoryName ?: 'General');
        $itemCode = trim((string)($data['item_code'] ?? '')) ?: 'INV-' . strtoupper(substr(str_replace(' ', '', $name), 0, 6)) . '-' . date('YmdHis');
        $unit = trim((string)($data['unit'] ?? 'pcs')) ?: 'pcs';
        $costPrice = (float)($data['cost_price'] ?? 0.0);
        $sellingPrice = (float)($data['selling_price'] ?? $costPrice);
        $openingStock = (int)($data['opening_stock'] ?? 0);
        $currentStock = (int)($data['current_stock'] ?? $openingStock);
        $reorderLevel = (int)($data['reorder_level'] ?? 0);
        $supplierName = trim((string)($data['supplier_name'] ?? '')) ?: null;
        $supplierContact = trim((string)($data['supplier_contact'] ?? '')) ?: null;
        $supplierPhone = trim((string)($data['supplier_phone'] ?? '')) ?: null;
        $supplierAddress = trim((string)($data['supplier_address'] ?? '')) ?: null;
        $allocationStatus = ($data['allocation_status'] ?? 'free') === 'allocated' ? 'allocated' : 'free';
        $allocatedTo = $allocationStatus === 'allocated' ? (trim((string)($data['allocated_to'] ?? '')) ?: null) : null;
        if ($allocationStatus === 'allocated' && $allocatedTo === null) {
            throw new InvalidArgumentException('Specify who or where the item is allocated to.');
        }

        $this->query(
            'INSERT INTO inventory_items (company_id, item_code, name, category_id, unit, supplier_name, supplier_contact, supplier_phone, supplier_address, cost_price, selling_price, opening_stock, current_stock, free_stock, allocated_stock, allocation_status, allocated_to, reorder_level, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())',
            [$this->currentCompanyId(), $itemCode, $name, $categoryId, $unit, $supplierName, $supplierContact, $supplierPhone, $supplierAddress, number_format($costPrice, 2, '.', ''), number_format($sellingPrice, 2, '.', ''), $openingStock, $currentStock, $currentStock, 0, $allocationStatus, $allocatedTo, $reorderLevel]
        );

        return (int)$this->db->lastInsertId();
    }

    public function updateItem(int $id, array $data, string $changeReason): bool
    {
        $existing = $this->getItemById($id);
        if (!$existing) {
            return false;
        }

        $fields = [];
        $params = [];
        $before = $existing;

        if (($data['name'] ?? '') !== '') {
            $fields[] = 'name = ?';
            $params[] = trim((string)$data['name']);
        }

        if (($data['unit'] ?? '') !== '') {
            $fields[] = 'unit = ?';
            $params[] = trim((string)$data['unit']);
        }

        foreach (['supplier_name', 'supplier_contact', 'supplier_phone', 'supplier_address'] as $supplierField) {
            if (array_key_exists($supplierField, $data)) {
                $fields[] = $supplierField . ' = ?';
                $params[] = trim((string)$data[$supplierField]) ?: null;
            }
        }

        if (array_key_exists('allocation_status', $data) || array_key_exists('allocated_to', $data)) {
            $allocationStatus = ($data['allocation_status'] ?? $existing['allocation_status'] ?? 'free') === 'allocated' ? 'allocated' : 'free';
            $allocatedTo = trim((string)($data['allocated_to'] ?? ''));
            if ($allocationStatus === 'allocated' && $allocatedTo === '') {
                throw new InvalidArgumentException('Specify who or where the item is allocated to.');
            }
            $fields[] = 'allocation_status = ?';
            $params[] = $allocationStatus;
            $fields[] = 'allocated_to = ?';
            $params[] = $allocationStatus === 'allocated' ? $allocatedTo : null;
        }

        if (isset($data['cost_price'])) {
            $fields[] = 'cost_price = ?';
            $params[] = number_format((float)$data['cost_price'], 2, '.', '');
        }

        if (isset($data['selling_price'])) {
            $fields[] = 'selling_price = ?';
            $params[] = number_format((float)$data['selling_price'], 2, '.', '');
        }

        if (isset($data['current_stock'])) {
            $fields[] = 'current_stock = ?';
            $params[] = (int)$data['current_stock'];
        }

        if (isset($data['reorder_level'])) {
            $fields[] = 'reorder_level = ?';
            $params[] = (int)$data['reorder_level'];
        }

        if (!empty($fields)) {
            $params[] = $id;
            $params[] = $this->currentCompanyId();
            $this->query('UPDATE inventory_items SET ' . implode(', ', $fields) . ' WHERE id = ? AND company_id = ?', $params);
            $this->query('INSERT INTO inventory_change_history (item_id, change_reason, before_data, after_data, changed_at) VALUES (?, ?, ?, ?, NOW())', [$id, $changeReason, json_encode($before), json_encode($this->getItemById($id))]);
            return true;
        }

        return false;
    }
}
