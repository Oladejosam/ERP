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
        $this->query('CREATE TABLE IF NOT EXISTS inventory_categories (id INT PRIMARY KEY AUTO_INCREMENT, name VARCHAR(100) NOT NULL UNIQUE, created_at DATETIME DEFAULT CURRENT_TIMESTAMP)');
        $this->query('CREATE TABLE IF NOT EXISTS inventory_items (id INT PRIMARY KEY AUTO_INCREMENT, item_code VARCHAR(50) NOT NULL UNIQUE, name VARCHAR(150) NOT NULL, category_id INT NOT NULL, unit VARCHAR(50) NOT NULL, cost_price DECIMAL(12,2) DEFAULT 0.00, selling_price DECIMAL(12,2) DEFAULT 0.00, opening_stock INT DEFAULT 0, current_stock INT DEFAULT 0, free_stock INT NOT NULL DEFAULT 0, allocated_stock INT NOT NULL DEFAULT 0, allocation_status ENUM("free", "allocated") NOT NULL DEFAULT "free", allocated_to VARCHAR(255) DEFAULT NULL, reorder_level INT DEFAULT 0, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, company_id INT DEFAULT NULL, supplier_name VARCHAR(150) DEFAULT NULL, supplier_contact VARCHAR(150) DEFAULT NULL, supplier_phone VARCHAR(50) DEFAULT NULL, supplier_address VARCHAR(255) DEFAULT NULL, FOREIGN KEY (category_id) REFERENCES inventory_categories(id))');
        $this->query('CREATE TABLE IF NOT EXISTS inventory_change_history (id INT PRIMARY KEY AUTO_INCREMENT, item_id INT NOT NULL, change_reason TEXT NOT NULL, before_data TEXT NOT NULL, after_data TEXT NOT NULL, changed_at DATETIME DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (item_id) REFERENCES inventory_items(id) ON DELETE CASCADE)');
        $this->query('CREATE TABLE IF NOT EXISTS inventory_item_changes (id INT PRIMARY KEY AUTO_INCREMENT, item_id INT NOT NULL, change_reason TEXT NOT NULL, before_data TEXT NOT NULL, after_data TEXT NOT NULL, changed_at DATETIME DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (item_id) REFERENCES inventory_items(id) ON DELETE CASCADE)');
        $this->query('CREATE TABLE IF NOT EXISTS inventory_issues (id INT PRIMARY KEY AUTO_INCREMENT, company_id INT NOT NULL DEFAULT 0, item_id INT NOT NULL, issued_to VARCHAR(255) NOT NULL, quantity INT NOT NULL, stock_source ENUM("free","allocated") NOT NULL, issued_date DATE NOT NULL, issued_by INT DEFAULT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (item_id) REFERENCES inventory_items(id) ON DELETE CASCADE)');

        $columns = $this->query('SHOW COLUMNS FROM inventory_items')->fetchAll();
        $existingColumns = array_map(static fn (array $column): string => (string)$column['Field'], $columns);
        $requiredColumns = [
            'free_stock' => 'ALTER TABLE inventory_items ADD COLUMN free_stock INT NOT NULL DEFAULT 0 AFTER current_stock',
            'allocated_stock' => 'ALTER TABLE inventory_items ADD COLUMN allocated_stock INT NOT NULL DEFAULT 0 AFTER free_stock',
            'allocation_status' => 'ALTER TABLE inventory_items ADD COLUMN allocation_status ENUM("free","allocated") NOT NULL DEFAULT "free" AFTER allocated_stock',
            'allocated_to' => 'ALTER TABLE inventory_items ADD COLUMN allocated_to VARCHAR(255) DEFAULT NULL AFTER allocation_status',
            'company_id' => 'ALTER TABLE inventory_items ADD COLUMN company_id INT DEFAULT NULL AFTER created_at',
            'supplier_name' => 'ALTER TABLE inventory_items ADD COLUMN supplier_name VARCHAR(150) DEFAULT NULL AFTER company_id',
            'supplier_contact' => 'ALTER TABLE inventory_items ADD COLUMN supplier_contact VARCHAR(150) DEFAULT NULL AFTER supplier_name',
            'supplier_phone' => 'ALTER TABLE inventory_items ADD COLUMN supplier_phone VARCHAR(50) DEFAULT NULL AFTER supplier_contact',
            'supplier_address' => 'ALTER TABLE inventory_items ADD COLUMN supplier_address VARCHAR(255) DEFAULT NULL AFTER supplier_phone',
        ];

        foreach ($requiredColumns as $columnName => $alterSql) {
            if (in_array($columnName, $existingColumns, true)) {
                continue;
            }
            try {
                $this->query($alterSql);
            } catch (Throwable $exception) {
                // Ignore migration failures for columns that may already exist from a parallel process.
            }
        }
    }

    public function getCategories(): array
    {
        $stmt = $this->query('SELECT * FROM inventory_categories ORDER BY name ASC');
        return $stmt->fetchAll();
    }

    public function getItems(?string $search = '', ?string $searchField = 'all'): array
    {
        $sql = 'SELECT i.*, c.name AS category_name FROM inventory_items i LEFT JOIN inventory_categories c ON c.id = i.category_id';
        $params = [];
        $search = trim((string)$search);
        $searchField = in_array($searchField, ['item_code', 'name', 'category', 'supplier'], true) ? $searchField : 'all';

        if ($search !== '') {
            $like = '%' . $search . '%';
            if ($searchField === 'item_code') {
                $sql .= ' WHERE i.item_code LIKE ?';
                $params[] = $like;
            } elseif ($searchField === 'name') {
                $sql .= ' WHERE i.name LIKE ?';
                $params[] = $like;
            } elseif ($searchField === 'category') {
                $sql .= ' WHERE c.name LIKE ?';
                $params[] = $like;
            } elseif ($searchField === 'supplier') {
                $sql .= ' WHERE i.supplier_name LIKE ? OR i.supplier_contact LIKE ? OR i.supplier_phone LIKE ?';
                $params[] = $like;
                $params[] = $like;
                $params[] = $like;
            } else {
                $sql .= ' WHERE i.item_code LIKE ? OR i.name LIKE ? OR c.name LIKE ? OR i.supplier_name LIKE ?';
                $params[] = $like;
                $params[] = $like;
                $params[] = $like;
                $params[] = $like;
            }
        }

        $sql .= ' ORDER BY i.created_at DESC';
        $items = $this->query($sql, $params);
        return $items->fetchAll();
    }

    public function searchItems(string $query): array
    {
        $term = trim($query);
        if ($term === '') {
            return [];
        }

        $like = '%' . $term . '%';
        $stmt = $this->query(
            'SELECT i.id, i.item_code, i.name, i.unit, i.cost_price, i.selling_price, c.name AS category_name FROM inventory_items i LEFT JOIN inventory_categories c ON c.id = i.category_id WHERE i.item_code LIKE ? OR i.name LIKE ? OR c.name LIKE ? OR i.supplier_name LIKE ? ORDER BY i.name ASC LIMIT 20',
            [$like, $like, $like, $like]
        );

        return $stmt->fetchAll();
    }

    public function getItemById(int $id): ?array
    {
        $stmt = $this->query('SELECT i.*, c.name AS category_name FROM inventory_items i LEFT JOIN inventory_categories c ON c.id = i.category_id WHERE i.id = ? LIMIT 1', [$id]);
        return $stmt->fetch() ?: null;
    }

    public function getItemChangeHistory(int $id): array
    {
        $stmt = $this->query('SELECT * FROM inventory_change_history WHERE item_id = ? ORDER BY changed_at DESC', [$id]);
        return $stmt->fetchAll();
    }

    public function getItemIssueHistory(int $id): array
    {
        $stmt = $this->query('SELECT h.*, u.name AS issuer_name FROM inventory_issues h LEFT JOIN users u ON u.id = h.issued_by WHERE h.item_id = ? ORDER BY h.created_at DESC', [$id]);
        return $stmt->fetchAll();
    }

    public function createCategory(string $name): int
    {
        $trimmed = trim($name);
        if ($trimmed === '') {
            throw new InvalidArgumentException('Category name is required.');
        }

        $stmt = $this->query('SELECT id FROM inventory_categories WHERE LOWER(name) = LOWER(?) LIMIT 1', [$trimmed]);
        $row = $stmt->fetch();
        if ($row) {
            return (int)$row['id'];
        }

        $this->query('INSERT INTO inventory_categories (name, created_at) VALUES (?, NOW())', [$trimmed]);
        return (int)$this->db->lastInsertId();
    }

    public function createItem(array $data): int
    {
        $name = trim((string)($data['name'] ?? ''));
        $categoryName = trim((string)($data['category_name'] ?? $data['category'] ?? ''));
        $itemCode = trim((string)($data['item_code'] ?? ''));
        if ($name === '') {
            throw new InvalidArgumentException('Item name is required.');
        }
        if ($itemCode === '') {
            $itemCode = 'INV-' . strtoupper(substr(str_replace(' ', '', $name), 0, 6)) . '-' . date('YmdHis');
        }

        $existing = $this->query('SELECT * FROM inventory_items WHERE LOWER(item_code) = LOWER(?) LIMIT 1', [$itemCode])->fetch();
        if ($existing) {
            $incomingQuantity = max(0, (int)($data['stock'] ?? $data['current_stock'] ?? $data['opening_stock'] ?? 0));
            if ($incomingQuantity <= 0) {
                throw new InvalidArgumentException('The submitted stock quantity must be greater than zero.');
            }

            $before = $existing;
            $updatedName = trim((string)($data['name'] ?? $existing['name'] ?? '')) !== '' ? trim((string)($data['name'] ?? $existing['name'])) : (string)$existing['name'];
            $updatedCategoryId = (int)($existing['category_id'] ?? 0);
            if ($categoryName !== '') {
                $updatedCategoryId = $this->createCategory($categoryName);
            }
            $updatedUnit = trim((string)($data['unit'] ?? $existing['unit'] ?? 'pcs')) ?: 'pcs';
            $updatedCostPrice = isset($data['cost_price']) ? (float)$data['cost_price'] : (float)($existing['cost_price'] ?? 0.0);
            $updatedSellingPrice = isset($data['selling_price']) ? (float)$data['selling_price'] : (float)($existing['selling_price'] ?? $updatedCostPrice);
            $updatedCurrentStock = (int)$existing['current_stock'] + $incomingQuantity;
            $updatedFreeStock = (int)$existing['free_stock'] + $incomingQuantity;
            $updatedReorderLevel = isset($data['reorder_level']) ? (int)$data['reorder_level'] : (int)($existing['reorder_level'] ?? 0);
            $updatedCompanyId = isset($data['company_id']) ? (int)$data['company_id'] : (int)($existing['company_id'] ?? $this->currentCompanyId());
            $updatedSupplierName = trim((string)($data['supplier_name'] ?? $existing['supplier_name'] ?? '')) ?: null;
            $updatedSupplierContact = trim((string)($data['supplier_contact'] ?? $existing['supplier_contact'] ?? '')) ?: null;
            $updatedSupplierPhone = trim((string)($data['supplier_phone'] ?? $existing['supplier_phone'] ?? '')) ?: null;
            $updatedSupplierAddress = trim((string)($data['supplier_address'] ?? $existing['supplier_address'] ?? '')) ?: null;

            $this->query(
                'UPDATE inventory_items SET name = ?, category_id = ?, unit = ?, cost_price = ?, selling_price = ?, current_stock = ?, free_stock = ?, allocation_status = ?, allocated_to = ?, reorder_level = ?, company_id = ?, supplier_name = ?, supplier_contact = ?, supplier_phone = ?, supplier_address = ? WHERE id = ?',
                [$updatedName, $updatedCategoryId, $updatedUnit, number_format($updatedCostPrice, 2, '.', ''), number_format($updatedSellingPrice, 2, '.', ''), $updatedCurrentStock, $updatedFreeStock, $updatedFreeStock > 0 ? 'free' : 'allocated', $updatedFreeStock > 0 ? ($existing['allocated_to'] ?? null) : null, $updatedReorderLevel, $updatedCompanyId, $updatedSupplierName, $updatedSupplierContact, $updatedSupplierPhone, $updatedSupplierAddress, (int)$existing['id']]
            );

            $after = $this->getItemById((int)$existing['id']);
            $this->query(
                'INSERT INTO inventory_change_history (item_id, change_reason, before_data, after_data, changed_at) VALUES (?, ?, ?, ?, NOW())',
                [(int)$existing['id'], 'Auto-restocked existing item code ' . $itemCode . ' with + ' . $incomingQuantity . ' units', json_encode($before), json_encode($after)]
            );

            return (int)$existing['id'];
        }

        $categoryId = $this->createCategory($categoryName ?: 'General');
        $unit = trim((string)($data['unit'] ?? 'pcs')) ?: 'pcs';
        $costPrice = (float)($data['cost_price'] ?? 0.0);
        $sellingPrice = (float)($data['selling_price'] ?? $costPrice);
        $openingStock = (int)($data['opening_stock'] ?? 0);
        $currentStock = (int)($data['current_stock'] ?? $openingStock);
        $freeStock = max(0, (int)($data['free_stock'] ?? $currentStock));
        $allocatedStock = max(0, (int)($data['allocated_stock'] ?? 0));
        $reorderLevel = (int)($data['reorder_level'] ?? 0);
        $companyId = isset($data['company_id']) ? (int)$data['company_id'] : $this->currentCompanyId();
        $supplierName = trim((string)($data['supplier_name'] ?? ''));
        $supplierContact = trim((string)($data['supplier_contact'] ?? ''));
        $supplierPhone = trim((string)($data['supplier_phone'] ?? ''));
        $supplierAddress = trim((string)($data['supplier_address'] ?? ''));

        $this->query(
            'INSERT INTO inventory_items (item_code, name, category_id, unit, cost_price, selling_price, opening_stock, current_stock, free_stock, allocated_stock, allocation_status, allocated_to, reorder_level, company_id, supplier_name, supplier_contact, supplier_phone, supplier_address, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())',
            [$itemCode, $name, $categoryId, $unit, number_format($costPrice, 2, '.', ''), number_format($sellingPrice, 2, '.', ''), $openingStock, $currentStock, $freeStock, $allocatedStock, $allocatedStock > 0 ? 'allocated' : 'free', $allocatedStock > 0 ? ($data['allocated_to'] ?? '') : null, $reorderLevel, $companyId, $supplierName !== '' ? $supplierName : null, $supplierContact !== '' ? $supplierContact : null, $supplierPhone !== '' ? $supplierPhone : null, $supplierAddress !== '' ? $supplierAddress : null]
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
            $this->query('UPDATE inventory_items SET ' . implode(', ', $fields) . ' WHERE id = ?', $params);
            $after = $this->getItemById($id);
            $this->query('INSERT INTO inventory_change_history (item_id, change_reason, before_data, after_data, changed_at) VALUES (?, ?, ?, ?, NOW())', [$id, $changeReason, json_encode($before), json_encode($after)]);
            return true;
        }

        return false;
    }

    public function issueItem(int $itemId, string $issuedTo, int $quantity, string $issuedDate, string $stockSource, ?int $userId = null): void
    {
        $itemId = (int)$itemId;
        $quantity = (int)$quantity;
        $issuedTo = trim($issuedTo);
        $stockSource = in_array($stockSource, ['free', 'allocated'], true) ? $stockSource : 'free';
        if ($itemId <= 0) {
            throw new InvalidArgumentException('Please select an inventory item.');
        }
        if ($issuedTo === '') {
            throw new InvalidArgumentException('Enter the person or location receiving the issue.');
        }
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Issue quantity must be greater than zero.');
        }
        if ($issuedDate === '') {
            throw new InvalidArgumentException('Issue date is required.');
        }

        $this->db->beginTransaction();
        try {
            $item = $this->query('SELECT * FROM inventory_items WHERE id = ? FOR UPDATE', [$itemId])->fetch();
            if (!$item) {
                throw new InvalidArgumentException('The selected item does not exist.');
            }

            $available = (int)($item[$stockSource === 'free' ? 'free_stock' : 'allocated_stock'] ?? 0);
            if ($available < $quantity) {
                throw new InvalidArgumentException('There is not enough ' . $stockSource . ' stock to issue this item.');
            }

            if ($stockSource === 'free') {
                $this->query('UPDATE inventory_items SET free_stock = free_stock - ?, current_stock = free_stock + allocated_stock WHERE id = ?', [$quantity, $itemId]);
            } else {
                $this->query('UPDATE inventory_items SET allocated_stock = allocated_stock - ?, current_stock = free_stock + allocated_stock WHERE id = ?', [$quantity, $itemId]);
            }

            $this->query(
                'INSERT INTO inventory_issues (company_id, item_id, issued_to, quantity, stock_source, issued_date, issued_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())',
                [$this->currentCompanyId(), $itemId, $issuedTo, $quantity, $stockSource, $issuedDate, $userId ?: null]
            );

            $updatedItem = $this->query('SELECT * FROM inventory_items WHERE id = ? LIMIT 1', [$itemId])->fetch();
            if ($updatedItem) {
                $this->query('UPDATE inventory_items SET allocation_status = ? , allocated_to = ? WHERE id = ?', [((int)($updatedItem['allocated_stock'] ?? 0) > 0) ? 'allocated' : 'free', ((int)($updatedItem['allocated_stock'] ?? 0) > 0) ? ($updatedItem['allocated_to'] ?? '') : null, $itemId]);
            }

            $this->db->commit();
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }

    public function allocateStock(int $itemId, int $quantity, string $allocatedTo, ?int $userId = null): void
    {
        $itemId = (int)$itemId;
        $quantity = (int)$quantity;
        $allocatedTo = trim($allocatedTo);

        if ($itemId <= 0) {
            throw new InvalidArgumentException('Please select an inventory item.');
        }
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Allocation quantity must be greater than zero.');
        }
        if ($allocatedTo === '') {
            throw new InvalidArgumentException('Enter the person, project, or site for this allocation.');
        }

        $this->db->beginTransaction();
        try {
            $item = $this->query('SELECT * FROM inventory_items WHERE id = ? FOR UPDATE', [$itemId])->fetch();
            if (!$item) {
                throw new InvalidArgumentException('The selected item does not exist.');
            }

            $available = (int)($item['free_stock'] ?? 0);
            if ($available < $quantity) {
                throw new InvalidArgumentException('There is not enough free stock to allocate.');
            }

            $this->query(
                'UPDATE inventory_items SET free_stock = free_stock - ?, allocated_stock = allocated_stock + ?, allocation_status = "allocated", allocated_to = ?, current_stock = free_stock + allocated_stock WHERE id = ?',
                [$quantity, $quantity, $allocatedTo, $itemId]
            );

            $this->query('INSERT INTO inventory_change_history (item_id, change_reason, before_data, after_data, changed_at) VALUES (?, ?, ?, ?, NOW())', [$itemId, 'Allocated stock to ' . $allocatedTo, json_encode($item), json_encode($this->getItemById($itemId))]);

            $this->db->commit();
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }
}
