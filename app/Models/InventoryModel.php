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
        $this->query('CREATE TABLE IF NOT EXISTS inventory_items (id INT PRIMARY KEY AUTO_INCREMENT, item_code VARCHAR(50) NOT NULL UNIQUE, name VARCHAR(150) NOT NULL, category_id INT NOT NULL, unit VARCHAR(50) NOT NULL, cost_price DECIMAL(12,2) DEFAULT 0.00, selling_price DECIMAL(12,2) DEFAULT 0.00, opening_stock INT DEFAULT 0, current_stock INT DEFAULT 0, reorder_level INT DEFAULT 0, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (category_id) REFERENCES inventory_categories(id))');
        $this->query('CREATE TABLE IF NOT EXISTS inventory_change_history (id INT PRIMARY KEY AUTO_INCREMENT, item_id INT NOT NULL, change_reason TEXT NOT NULL, before_data TEXT NOT NULL, after_data TEXT NOT NULL, changed_at DATETIME DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (item_id) REFERENCES inventory_items(id) ON DELETE CASCADE)');
    }

    public function getCategories(): array
    {
        $stmt = $this->query('SELECT * FROM inventory_categories ORDER BY name ASC');
        return $stmt->fetchAll();
    }

    public function getItems(): array
    {
        $items = $this->query('SELECT i.*, c.name AS category_name FROM inventory_items i LEFT JOIN inventory_categories c ON c.id = i.category_id ORDER BY i.created_at DESC');
        return $items->fetchAll();
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

        $this->query(
            'INSERT INTO inventory_items (item_code, name, category_id, unit, cost_price, selling_price, opening_stock, current_stock, reorder_level, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())',
            [$itemCode, $name, $categoryId, $unit, number_format($costPrice, 2, '.', ''), number_format($sellingPrice, 2, '.', ''), $openingStock, $currentStock, $reorderLevel]
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
            $this->query('INSERT INTO inventory_change_history (item_id, change_reason, before_data, after_data, changed_at) VALUES (?, ?, ?, ?, NOW())', [$id, $changeReason, json_encode($before), json_encode($this->getItemById($id))]);
            return true;
        }

        return false;
    }
}
