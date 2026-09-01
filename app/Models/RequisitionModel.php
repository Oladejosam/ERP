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
                inventory_item_id INT NULL,
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
        $this->query('ALTER TABLE requisition_items ADD COLUMN IF NOT EXISTS inventory_item_id INT NULL AFTER requisition_id');
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
        $this->query(
            'CREATE TABLE IF NOT EXISTS requisition_item_adjustments (
                id INT PRIMARY KEY AUTO_INCREMENT,
                requisition_id INT NOT NULL,
                requisition_item_id INT NOT NULL,
                company_id INT NOT NULL,
                user_id INT NOT NULL,
                field_name VARCHAR(40) NOT NULL DEFAULT "quantity",
                previous_value DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                new_value DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                delta DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                adjustment_reason VARCHAR(255) NULL,
                changed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_requisition_item_adjustments_item (requisition_item_id),
                INDEX idx_requisition_item_adjustments_requisition (requisition_id)
            )'
        );
        $this->query(
            'CREATE TABLE IF NOT EXISTS requisition_dispatch_requests (
                id INT PRIMARY KEY AUTO_INCREMENT,
                requisition_id INT NOT NULL,
                requisition_item_id INT NOT NULL,
                company_id INT NOT NULL,
                inventory_item_id INT NOT NULL,
                quantity DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                status ENUM("pending", "approved", "rejected") NOT NULL DEFAULT "pending",
                requested_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                decided_at DATETIME NULL,
                decided_by INT NULL,
                UNIQUE KEY unique_dispatch_item (requisition_item_id)
            )'
        );
        $this->query(
            'CREATE TABLE IF NOT EXISTS requisition_form_fields (
                id INT PRIMARY KEY AUTO_INCREMENT,
                company_id INT NOT NULL,
                field_key VARCHAR(80) NOT NULL,
                label VARCHAR(120) NOT NULL,
                field_type VARCHAR(30) NOT NULL DEFAULT "text",
                required TINYINT(1) NOT NULL DEFAULT 0,
                placeholder VARCHAR(255) NULL,
                help_text VARCHAR(255) NULL,
                sort_order INT NOT NULL DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_company_field (company_id, field_key)
            )'
        );
        $this->query(
            'CREATE TABLE IF NOT EXISTS requisition_form_data (
                id INT PRIMARY KEY AUTO_INCREMENT,
                requisition_id INT NOT NULL,
                company_id INT NOT NULL,
                field_key VARCHAR(80) NOT NULL,
                field_label VARCHAR(120) NOT NULL,
                field_value LONGTEXT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_requisition_field (requisition_id, field_key)
            )'
        );
        $this->query('ALTER TABLE requisitions ADD COLUMN IF NOT EXISTS chat_closed_at DATETIME NULL');
        $this->query('ALTER TABLE requisitions ADD COLUMN IF NOT EXISTS project_id INT NULL AFTER company_id');
        $this->query('ALTER TABLE requisitions ADD COLUMN IF NOT EXISTS urgent TINYINT(1) NOT NULL DEFAULT 0 AFTER project_id');
        $this->query('ALTER TABLE requisition_dispatch_requests ADD COLUMN IF NOT EXISTS stock_type ENUM("free", "allocated") NULL AFTER quantity');
        $this->query('ALTER TABLE requisition_dispatch_requests ADD COLUMN IF NOT EXISTS urgent TINYINT(1) NOT NULL DEFAULT 0 AFTER stock_type');
        $this->query('ALTER TABLE requisition_dispatch_requests ADD COLUMN IF NOT EXISTS decision ENUM("pending", "issued", "purchase_required", "rejected") NOT NULL DEFAULT "pending" AFTER urgent');
    }

    public function getAll(?int $userId = null, bool $isSuperAdmin = false): array
    {
        $companyId = $this->currentCompanyId();
        $visibilitySql = '';
        $params = [$companyId];
        if (!$isSuperAdmin && $userId !== null && $userId > 0) {
            $visibilitySql = ' AND EXISTS (SELECT 1 FROM requisition_participants rp WHERE rp.requisition_id = r.id AND rp.company_id = r.company_id AND rp.user_id = ?)';
            $params[] = $userId;
        }
        $stmt = $this->query(
            'SELECT r.*, u.name AS requester_name FROM requisitions r LEFT JOIN users u ON u.id = r.requested_by WHERE r.company_id = ?' . $visibilitySql . ' ORDER BY r.created_at DESC, r.id DESC',
            $params
        );
        $requisitions = $stmt->fetchAll();
        foreach ($requisitions as &$requisition) {
            $items = $this->query('SELECT * FROM requisition_items WHERE requisition_id = ? ORDER BY id ASC', [(int)$requisition['id']])->fetchAll();
            $requisition['items'] = $items;
        }
        return $requisitions;
    }

    public static function defaultFormFields(): array
    {
        return [
            ['key' => 'project_title', 'label' => 'Project Title', 'type' => 'text', 'required' => true, 'placeholder' => 'Enter a project title', 'help_text' => 'The title of the project or work request.', 'sort_order' => 1],
            ['key' => 'trade', 'label' => 'Trade', 'type' => 'text', 'required' => false, 'placeholder' => 'e.g. Masonry', 'help_text' => 'Trade or specialty involved in the request.', 'sort_order' => 2],
            ['key' => 'supplier', 'label' => 'Supplier', 'type' => 'text', 'required' => false, 'placeholder' => 'Supplier name', 'help_text' => 'Preferred vendor or supplier.', 'sort_order' => 3],
            ['key' => 'supplier_address', 'label' => 'Supplier Address', 'type' => 'textarea', 'required' => false, 'placeholder' => 'Supplier office address', 'help_text' => 'Where the supplier is located.', 'sort_order' => 4],
            ['key' => 'expected_delivery', 'label' => 'Expected Delivery', 'type' => 'date', 'required' => false, 'placeholder' => 'Select delivery date', 'help_text' => 'When the item is expected to be delivered.', 'sort_order' => 5],
        ];
    }

    public function getDefaultFormFields(): array
    {
        return self::defaultFormFields();
    }

    public function getFormFields(): array
    {
        $rows = $this->query('SELECT field_key, label, field_type, required, placeholder, help_text, sort_order FROM requisition_form_fields WHERE company_id = ? ORDER BY sort_order ASC, id ASC', [$this->currentCompanyId()])->fetchAll();
        if ($rows === []) {
            return $this->getDefaultFormFields();
        }
        return array_map(static function (array $row): array {
            return [
                'key' => (string)$row['field_key'],
                'label' => (string)$row['label'],
                'type' => (string)($row['field_type'] ?? 'text'),
                'required' => (bool)$row['required'],
                'placeholder' => (string)($row['placeholder'] ?? ''),
                'help_text' => (string)($row['help_text'] ?? ''),
                'sort_order' => (int)($row['sort_order'] ?? 0),
            ];
        }, $rows);
    }

    public function saveFormFields(array $fields): void
    {
        $companyId = $this->currentCompanyId();
        $normalized = [];
        foreach ($fields as $index => $field) {
            $key = strtolower(trim((string)($field['key'] ?? '')));
            $label = trim((string)($field['label'] ?? ''));
            if ($key === '' || $label === '') {
                continue;
            }
            $type = in_array(strtolower((string)($field['type'] ?? 'text')), ['text', 'textarea', 'number', 'date', 'select', 'checkbox'], true)
                ? strtolower((string)($field['type'] ?? 'text'))
                : 'text';
            $normalized[] = [
                'key' => $key,
                'label' => $label,
                'type' => $type,
                'required' => !empty($field['required']) ? 1 : 0,
                'placeholder' => trim((string)($field['placeholder'] ?? '')),
                'help_text' => trim((string)($field['help_text'] ?? '')),
                'sort_order' => (int)$index,
            ];
        }
        $this->query('DELETE FROM requisition_form_fields WHERE company_id = ?', [$companyId]);
        foreach ($normalized as $field) {
            $this->query(
                'INSERT INTO requisition_form_fields (company_id, field_key, label, field_type, required, placeholder, help_text, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                [$companyId, $field['key'], $field['label'], $field['type'], $field['required'], $field['placeholder'] !== '' ? $field['placeholder'] : null, $field['help_text'] !== '' ? $field['help_text'] : null, $field['sort_order']]
            );
        }
        if ($normalized === []) {
            foreach ($this->getDefaultFormFields() as $index => $field) {
                $this->query(
                    'INSERT INTO requisition_form_fields (company_id, field_key, label, field_type, required, placeholder, help_text, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                    [$companyId, $field['key'], $field['label'], $field['type'], !empty($field['required']) ? 1 : 0, $field['placeholder'] !== '' ? $field['placeholder'] : null, $field['help_text'] !== '' ? $field['help_text'] : null, $index]
                );
            }
        }
    }

    public function saveFormData(int $requisitionId, array $data, array $fields): void
    {
        $companyId = $this->currentCompanyId();
        foreach ($fields as $field) {
            $key = strtolower(trim((string)($field['key'] ?? '')));
            if ($key === '') {
                continue;
            }
            $value = $data[$key] ?? null;
            if (is_array($value)) {
                $value = implode(', ', array_filter(array_map('strval', $value), static fn (string $item): bool => trim($item) !== ''));
            }
            $value = is_null($value) ? null : trim((string)$value);
            if ($value === '' || $value === null) {
                continue;
            }
            $this->query(
                'INSERT INTO requisition_form_data (requisition_id, company_id, field_key, field_label, field_value) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE field_label = VALUES(field_label), field_value = VALUES(field_value)',
                [$requisitionId, $companyId, $key, trim((string)($field['label'] ?? $key)), $value]
            );
        }
    }

    public function getFormData(int $requisitionId): array
    {
        $rows = $this->query('SELECT field_key, field_label, field_value FROM requisition_form_data WHERE requisition_id = ? AND company_id = ? ORDER BY id ASC', [$requisitionId, $this->currentCompanyId()])->fetchAll();
        $out = [];
        foreach ($rows as $row) {
            $out[(string)$row['field_key']] = [
                'label' => (string)$row['field_label'],
                'value' => (string)$row['field_value'],
            ];
        }
        return $out;
    }

    public function create(array $data, ?int $requestedBy): int
    {
        $title = trim((string)($data['project_title'] ?? ''));
        $projectId = (int)($data['project_id'] ?? 0);
        if ($projectId > 0) {
            $project = $this->query('SELECT id, name FROM projects WHERE id = ? AND company_id = ? AND deleted_at IS NULL LIMIT 1', [$projectId, $this->currentCompanyId()])->fetch();
            if (!$project) {
                throw new InvalidArgumentException('The selected project is not part of the current company.');
            }
            $title = (string)$project['name'];
        }
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
                'INSERT INTO requisitions (company_id, project_id, urgent, requested_by, requisition_date, title, trade, supplier, supplier_address, amount) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0)',
                [$this->currentCompanyId(), $projectId > 0 ? $projectId : null, !empty($data['urgent']) ? 1 : 0, $requestedBy, $data['date'] ?: date('Y-m-d'), $title, trim((string)($data['trade'] ?? '')) ?: null, trim((string)($data['supplier'] ?? '')) ?: null, trim((string)($data['supplier_address'] ?? '')) ?: null]
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
                $itemCode = trim((string)($item['code'] ?? ''));
                $itemName = trim((string)($item['item_name'] ?? $item['description'] ?? ''));
                $inventoryItem = $this->findInventoryItem($itemCode, $itemName);
                $quantityToPurchase = max(0, (float)($item['quantity_to_purchase'] ?? 0));
                $quantityRequired = max(0, (float)($item['quantity_required'] ?? 0));
                $price = max(0, (float)($item['price'] ?? 0));
                $value = $quantityToPurchase * $price;
                $total += $value;
                $this->query(
                    'INSERT INTO requisition_items (requisition_id, inventory_item_id, description, item_code, unit, quantity_required, quantity_in_stock, quantity_to_purchase, price, value) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                    [$requisitionId, $inventoryItem ? (int)$inventoryItem['id'] : null, trim((string)($item['description'] ?? '')) ?: ($itemName ?: null), $itemCode ?: null, trim((string)($item['unit'] ?? '')) ?: null, $quantityRequired, max(0, (float)($item['quantity_in_stock'] ?? 0)), $quantityToPurchase, $price, number_format($value, 2, '.', '')]
                );
                if ($inventoryItem && ($quantityRequired > 0 || $quantityToPurchase > 0)) {
                    $this->query(
                        'INSERT INTO requisition_dispatch_requests (requisition_id, requisition_item_id, company_id, inventory_item_id, quantity, urgent) VALUES (?, ?, ?, ?, ?, ?)',
                        [$requisitionId, (int)$this->db->lastInsertId(), $this->currentCompanyId(), (int)$inventoryItem['id'], number_format($quantityRequired > 0 ? $quantityRequired : $quantityToPurchase, 2, '.', ''), !empty($data['urgent']) ? 1 : 0]
                    );
                }
            }
            $this->query('UPDATE requisitions SET amount = ?, value = ? WHERE id = ?', [number_format($total, 2, '.', ''), number_format($total, 2, '.', ''), $requisitionId]);
            $this->saveFormData($requisitionId, $data, $this->getFormFields());
            $this->db->commit();
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
        return $requisitionId;
    }

    private function findInventoryItem(string $itemCode, string $itemName): ?array
    {
        if ($itemCode === '' && $itemName === '') {
            return null;
        }
        return $this->query(
            'SELECT id, item_code, name, current_stock FROM inventory_items WHERE company_id = ? AND ((? <> "" AND item_code = ?) OR (? <> "" AND LOWER(name) = LOWER(?))) LIMIT 1',
            [$this->currentCompanyId(), $itemCode, $itemCode, $itemName, $itemName]
        )->fetch() ?: null;
    }

    public function getPendingDispatchRequests(): array
    {
        return $this->query(
            'SELECT d.*, r.title, r.requisition_date, r.urgent AS requisition_urgent, ri.description, ri.item_code, ri.unit, u.name AS requester_name, i.current_stock, i.free_stock, i.allocated_stock FROM requisition_dispatch_requests d INNER JOIN requisitions r ON r.id = d.requisition_id AND r.company_id = d.company_id INNER JOIN requisition_items ri ON ri.id = d.requisition_item_id INNER JOIN users u ON u.id = r.requested_by INNER JOIN inventory_items i ON i.id = d.inventory_item_id AND i.company_id = d.company_id WHERE d.company_id = ? AND r.status = "approved" AND d.status = "pending" ORDER BY d.requested_at ASC, d.id ASC',
            [$this->currentCompanyId()]
        )->fetchAll();
    }

    public function approveDispatch(int $dispatchId, int $userId): void
    {
        $companyId = $this->currentCompanyId();
        $this->db->beginTransaction();
        try {
            $dispatch = $this->query('SELECT d.*, r.title FROM requisition_dispatch_requests d INNER JOIN requisitions r ON r.id = d.requisition_id AND r.company_id = d.company_id WHERE d.id = ? AND d.company_id = ? AND d.status = "pending" FOR UPDATE', [$dispatchId, $companyId])->fetch();
            if (!$dispatch) {
                throw new InvalidArgumentException('This dispatch request has already been treated or was not found.');
            }
            $inventory = $this->query('SELECT current_stock, free_stock, name FROM inventory_items WHERE id = ? AND company_id = ? FOR UPDATE', [(int)$dispatch['inventory_item_id'], $companyId])->fetch();
            if (!$inventory || (float)$inventory['free_stock'] < (float)$dispatch['quantity']) {
                throw new InvalidArgumentException('Insufficient stock to approve this dispatch.');
            }
            $this->query('UPDATE inventory_items SET free_stock = free_stock - ?, current_stock = free_stock + allocated_stock WHERE id = ? AND company_id = ?', [$dispatch['quantity'], (int)$dispatch['inventory_item_id'], $companyId]);
            $this->query('UPDATE requisition_dispatch_requests SET status = "approved", decided_at = NOW(), decided_by = ? WHERE id = ? AND status = "pending"', [$userId, $dispatchId]);
            $this->query('INSERT INTO requisition_messages (requisition_id, company_id, user_id, message) VALUES (?, ?, ?, ?)', [(int)$dispatch['requisition_id'], $companyId, $userId, 'Head Store approved dispatch of ' . $dispatch['quantity'] . ' unit(s) of ' . $inventory['name'] . '. Stock was deducted.']);
            $this->db->commit();
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }

    public function decideDispatch(int $dispatchId, int $userId, string $stockType, bool $urgent): void
    {
        if (!in_array($stockType, ['free', 'allocated'], true)) {
            throw new InvalidArgumentException('Select whether the issue is from free or allocated stock.');
        }
        $companyId = $this->currentCompanyId();
        $this->db->beginTransaction();
        try {
            $dispatch = $this->query('SELECT d.*, r.title FROM requisition_dispatch_requests d INNER JOIN requisitions r ON r.id = d.requisition_id AND r.company_id = d.company_id WHERE d.id = ? AND d.company_id = ? AND r.status = "approved" AND d.status = "pending" FOR UPDATE', [$dispatchId, $companyId])->fetch();
            if (!$dispatch) {
                throw new InvalidArgumentException('This approved store request has already been treated or was not found.');
            }
            $inventory = $this->query('SELECT name, free_stock, allocated_stock FROM inventory_items WHERE id = ? AND company_id = ? FOR UPDATE', [(int)$dispatch['inventory_item_id'], $companyId])->fetch();
            if (!$inventory) {
                throw new InvalidArgumentException('The requested inventory item was not found.');
            }
            $quantity = (float)$dispatch['quantity'];
            $available = (float)$inventory[$stockType . '_stock'];
            if ($available < $quantity) {
                throw new InvalidArgumentException('There is not enough ' . $stockType . ' stock to issue this request.');
            }
            $this->query('UPDATE inventory_items SET ' . $stockType . '_stock = ' . $stockType . '_stock - ?, current_stock = free_stock + allocated_stock WHERE id = ? AND company_id = ?', [$quantity, (int)$dispatch['inventory_item_id'], $companyId]);
            $decision = $stockType === 'allocated' && $urgent ? 'purchase_required' : 'issued';
            $this->query('UPDATE requisition_dispatch_requests SET stock_type = ?, urgent = ?, decision = ?, status = "approved", decided_at = NOW(), decided_by = ? WHERE id = ? AND status = "pending"', [$stockType, $urgent ? 1 : 0, $decision, $userId, $dispatchId]);
            $message = $decision === 'purchase_required' ? 'Allocated stock issued urgently for ' . $inventory['name'] . '; procurement action is required.' : 'Store issued ' . $quantity . ' unit(s) of ' . $inventory['name'] . ' from ' . $stockType . ' stock.';
            $this->query('INSERT INTO requisition_messages (requisition_id, company_id, user_id, message) VALUES (?, ?, ?, ?)', [(int)$dispatch['requisition_id'], $companyId, $userId, $message]);
            $this->db->commit();
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }

    public function getById(int $requisitionId): ?array
    {
        $this->query('UPDATE requisitions SET chat_closed_at = COALESCE(chat_closed_at, NOW()) WHERE id = ? AND company_id = ? AND status IN ("approved", "rejected")', [$requisitionId, $this->currentCompanyId()]);
        $stmt = $this->query('SELECT r.*, u.name AS requester_name FROM requisitions r LEFT JOIN users u ON u.id = r.requested_by WHERE r.id = ? AND r.company_id = ? LIMIT 1', [$requisitionId, $this->currentCompanyId()]);
        $requisition = $stmt->fetch();
        if (!$requisition) {
            return null;
        }
        $items = $this->query('SELECT * FROM requisition_items WHERE requisition_id = ? ORDER BY id ASC', [$requisitionId])->fetchAll();
        foreach ($items as $index => $item) {
            $items[$index]['adjustments'] = $this->query(
                'SELECT a.*, u.name AS user_name FROM requisition_item_adjustments a INNER JOIN users u ON u.id = a.user_id WHERE a.requisition_id = ? AND a.requisition_item_id = ? AND a.company_id = ? ORDER BY a.changed_at DESC, a.id DESC',
                [$requisitionId, (int)$item['id'], $this->currentCompanyId()]
            )->fetchAll();
        }
        $requisition['items'] = $items;
        $requisition['form_data'] = $this->getFormData($requisitionId);
        $requisition['participants'] = $this->query('SELECT u.id, u.name, u.email FROM requisition_participants p INNER JOIN users u ON u.id = p.user_id WHERE p.requisition_id = ? AND p.company_id = ? ORDER BY u.name ASC', [$requisitionId, $this->currentCompanyId()])->fetchAll();
        $requisition['messages'] = $this->query('SELECT m.*, u.name AS author_name FROM requisition_messages m INNER JOIN users u ON u.id = m.user_id WHERE m.requisition_id = ? AND m.company_id = ? ORDER BY m.created_at ASC, m.id ASC', [$requisitionId, $this->currentCompanyId()])->fetchAll();
        return $requisition;
    }

    public function adjustItemQuantity(int $requisitionId, int $itemId, int $userId, float $delta, string $reason = ''): void
    {
        $companyId = $this->currentCompanyId();
        $item = $this->query('SELECT * FROM requisition_items WHERE id = ? AND requisition_id = ? AND requisition_id IN (SELECT id FROM requisitions WHERE company_id = ?) LIMIT 1', [$itemId, $requisitionId, $companyId])->fetch();
        if (!$item) {
            throw new InvalidArgumentException('The requisition item could not be found.');
        }

        $previousRequired = (float)($item['quantity_required'] ?? 0);
        $previousToPurchase = (float)($item['quantity_to_purchase'] ?? 0);
        $newRequired = max(0, $previousRequired + $delta);
        $newToPurchase = max(0, $previousToPurchase + $delta);
        $price = max(0, (float)($item['price'] ?? 0));
        $newValue = $newToPurchase * $price;

        $this->query(
            'UPDATE requisition_items SET quantity_required = ?, quantity_to_purchase = ?, value = ? WHERE id = ? AND requisition_id = ?',
            [number_format($newRequired, 2, '.', ''), number_format($newToPurchase, 2, '.', ''), number_format($newValue, 2, '.', ''), $itemId, $requisitionId]
        );

        $this->query(
            'INSERT INTO requisition_item_adjustments (requisition_id, requisition_item_id, company_id, user_id, field_name, previous_value, new_value, delta, adjustment_reason) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$requisitionId, $itemId, $companyId, $userId, 'quantity', number_format($previousRequired, 2, '.', ''), number_format($newRequired, 2, '.', ''), number_format($delta, 2, '.', ''), trim($reason) !== '' ? trim($reason) : null]
        );

        $this->query(
            'UPDATE requisitions SET amount = (SELECT ROUND(SUM(value), 2) FROM requisition_items WHERE requisition_id = ?) WHERE id = ?',
            [$requisitionId, $requisitionId]
        );
    }

    public function getCompanyUsers(): array
    {
        return $this->query('SELECT u.id, u.name, u.email FROM users u WHERE u.company_id = ? ORDER BY u.name ASC', [$this->currentCompanyId()])->fetchAll();
    }

    public function getPendingTaggedForUser(int $userId): array
    {
        return $this->query(
            'SELECT r.id, r.title, r.created_at, r.status FROM requisition_participants p INNER JOIN requisitions r ON r.id = p.requisition_id AND r.company_id = p.company_id WHERE p.company_id = ? AND p.user_id = ? AND r.status = "pending" ORDER BY r.created_at DESC, r.id DESC',
            [$this->currentCompanyId(), $userId]
        )->fetchAll();
    }

    public function getRecentActivityForUser(int $userId, int $limit = 8): array
    {
        $limit = max(1, min($limit, 25));
        return $this->query(
            'SELECT activity_type, requisition_id, title, activity_text, occurred_at FROM (
                SELECT "requisition" AS activity_type, r.id AS requisition_id, r.title, CONCAT("Requisition submitted: ", r.title) AS activity_text, r.created_at AS occurred_at
                FROM requisitions r
                INNER JOIN requisition_participants p ON p.requisition_id = r.id AND p.company_id = r.company_id AND p.user_id = ?
                WHERE r.company_id = ?
                UNION ALL
                SELECT "message" AS activity_type, r.id AS requisition_id, r.title, CONCAT("Discussion update: ", m.message) AS activity_text, m.created_at AS occurred_at
                FROM requisition_messages m
                INNER JOIN requisitions r ON r.id = m.requisition_id AND r.company_id = m.company_id
                INNER JOIN requisition_participants p ON p.requisition_id = r.id AND p.company_id = r.company_id AND p.user_id = ?
                WHERE m.company_id = ?
            ) AS account_activity ORDER BY occurred_at DESC LIMIT ' . $limit,
            [$userId, $this->currentCompanyId(), $userId, $this->currentCompanyId()]
        )->fetchAll();
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

    private function getRoleApprovalDepth(int $roleId): int
    {
        $companyId = $this->currentCompanyId();
        $depth = 0;
        $currentRoleId = $roleId;
        $visited = [];

        while ($currentRoleId > 0 && !isset($visited[$currentRoleId])) {
            $visited[$currentRoleId] = true;
            $parentRow = $this->query('SELECT parent_role_id FROM workflow_role_links WHERE company_id = ? AND role_id = ? LIMIT 1', [$companyId, $currentRoleId])->fetch();
            if (!$parentRow || $parentRow['parent_role_id'] === null) {
                break;
            }
            $depth++;
            $currentRoleId = (int)$parentRow['parent_role_id'];
        }

        return $depth;
    }

    private function getHighestLevelTaggedApprover(int $requisitionId): ?int
    {
        $companyId = $this->currentCompanyId();
        $rows = $this->query('SELECT p.user_id, u.role_id FROM requisition_participants p INNER JOIN users u ON u.id = p.user_id WHERE p.requisition_id = ? AND p.company_id = ? ORDER BY p.user_id ASC', [$requisitionId, $companyId])->fetchAll();
        $highestUserId = null;
        $highestDepth = -1;

        foreach ($rows as $row) {
            $userId = (int)$row['user_id'];
            $roleId = (int)($row['role_id'] ?? 0);
            $depth = $roleId > 0 ? $this->getRoleApprovalDepth($roleId) : 0;
            if ($depth > $highestDepth) {
                $highestDepth = $depth;
                $highestUserId = $userId;
            }
        }

        return $highestUserId;
    }

    private function sendToLogisticsOfficer(int $requisitionId, int $approvedBy): void
    {
        $companyId = $this->currentCompanyId();
        $logisticsUserIds = $this->query(
            'SELECT u.id FROM users u INNER JOIN roles r ON r.id = u.role_id WHERE u.company_id = ? AND LOWER(r.name) LIKE ? ORDER BY u.name ASC',
            [$companyId, '%logistics%']
        )->fetchAll();

        foreach ($logisticsUserIds as $row) {
            $logisticsUserId = (int)$row['id'];
            $this->query('INSERT IGNORE INTO requisition_participants (requisition_id, company_id, user_id) VALUES (?, ?, ?)', [$requisitionId, $companyId, $logisticsUserId]);
        }

        $this->query(
            'INSERT INTO requisition_messages (requisition_id, company_id, user_id, message) VALUES (?, ?, ?, ?)',
            [$requisitionId, $companyId, $approvedBy, 'Requisition approved by the top-level approver and sent to the Logistics Officer.']
        );
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

        if ($decision === 'rejected') {
            $this->query('UPDATE requisitions SET status = ?, chat_closed_at = NOW() WHERE id = ? AND company_id = ? AND status = "pending"', [$decision, $requisitionId, $companyId]);
            $this->query('INSERT INTO requisition_messages (requisition_id, company_id, user_id, message) VALUES (?, ?, ?, ?)', [$requisitionId, $companyId, $userId, 'Requisition rejected. The discussion is now closed.']);
            return;
        }

        $highestApproverId = $this->getHighestLevelTaggedApprover($requisitionId);
        if ($highestApproverId === null || (int)$userId !== $highestApproverId) {
            $this->query('INSERT INTO requisition_messages (requisition_id, company_id, user_id, message) VALUES (?, ?, ?, ?)', [$requisitionId, $companyId, $userId, 'Approved by this participant, but the requisition remains open pending the highest-level approver.']);
            return;
        }

        $this->query('UPDATE requisitions SET status = "approved", chat_closed_at = NOW() WHERE id = ? AND company_id = ? AND status = "pending"', [$requisitionId, $companyId]);
        $this->sendToLogisticsOfficer($requisitionId, $userId);
        $this->query('INSERT INTO requisition_messages (requisition_id, company_id, user_id, message) VALUES (?, ?, ?, ?)', [$requisitionId, $companyId, $userId, 'Requisition approved and the discussion is now closed.']);
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
