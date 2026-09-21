<?php
/**
 * Purchase order model for procurement management.
 */
declare(strict_types=1);

require_once APP_ROOT . '/core/Model.php';

class PurchaseOrderModel extends Model
{
    public function __construct()
    {
        parent::__construct();
        $this->ensurePurchaseOrderTables();
    }

    private function ensurePurchaseOrderTables(): void
    {
        $this->query('ALTER TABLE purchase_orders ADD COLUMN IF NOT EXISTS company_id INT NULL AFTER id');
        $this->query('ALTER TABLE purchase_orders ADD COLUMN IF NOT EXISTS requested_by INT NULL AFTER company_id');
        $this->query('ALTER TABLE purchase_orders ADD COLUMN IF NOT EXISTS workflow_status ENUM("draft", "pending_head_approval", "pending_procurement", "approved", "denied", "flagged", "sent_to_logistics") NOT NULL DEFAULT "draft" AFTER status');
        $this->query('ALTER TABLE purchase_orders MODIFY COLUMN workflow_status ENUM("draft", "pending_head_approval", "pending_procurement", "approved", "denied", "flagged", "sent_to_logistics") NOT NULL DEFAULT "draft"');
        $this->query('ALTER TABLE purchase_orders ADD COLUMN IF NOT EXISTS workflow_reason TEXT NULL AFTER workflow_status');
        $this->query('ALTER TABLE purchase_orders ADD COLUMN IF NOT EXISTS workflow_decided_by INT NULL AFTER workflow_reason');
        $this->query('ALTER TABLE purchase_orders ADD COLUMN IF NOT EXISTS workflow_decided_at DATETIME NULL AFTER workflow_decided_by');
        $this->query('UPDATE purchase_orders SET company_id = 1 WHERE company_id IS NULL');
        $this->query(
            'CREATE TABLE IF NOT EXISTS purchase_order_items (
                id INT PRIMARY KEY AUTO_INCREMENT,
                purchase_order_id INT NOT NULL,
                product_name VARCHAR(255) NOT NULL,
                quantity INT NOT NULL DEFAULT 0,
                price_rate DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                line_total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders(id) ON DELETE CASCADE
            )'
        );

        $this->query(
            'CREATE TABLE IF NOT EXISTS purchase_order_changes (
                id INT PRIMARY KEY AUTO_INCREMENT,
                purchase_order_id INT NOT NULL,
                change_reason TEXT NOT NULL,
                before_data TEXT NOT NULL,
                after_data TEXT NOT NULL,
                changed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders(id) ON DELETE CASCADE
            )'
        );

        $this->query(
            'CREATE TABLE IF NOT EXISTS purchase_order_invoices (
                id INT PRIMARY KEY AUTO_INCREMENT,
                purchase_order_id INT NOT NULL,
                label VARCHAR(255) NOT NULL,
                original_name VARCHAR(255) NOT NULL,
                stored_name VARCHAR(255) NOT NULL,
                file_type VARCHAR(100) NOT NULL,
                file_size INT NOT NULL,
                uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders(id) ON DELETE CASCADE
            )'
        );
        $this->query(
            'CREATE TABLE IF NOT EXISTS inventory_receipts (
                id INT PRIMARY KEY AUTO_INCREMENT,
                company_id INT NOT NULL,
                purchase_order_id INT NOT NULL,
                receipt_number VARCHAR(50) NOT NULL,
                received_by INT NULL,
                received_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_inventory_receipt_number (company_id, receipt_number),
                FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders(id)
            )'
        );
        $this->query(
            'CREATE TABLE IF NOT EXISTS inventory_receipt_items (
                id INT PRIMARY KEY AUTO_INCREMENT,
                receipt_id INT NOT NULL,
                purchase_order_item_id INT NOT NULL,
                inventory_item_id INT NOT NULL,
                quantity_received DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                price_rate DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (receipt_id) REFERENCES inventory_receipts(id) ON DELETE CASCADE,
                FOREIGN KEY (purchase_order_item_id) REFERENCES purchase_order_items(id),
                FOREIGN KEY (inventory_item_id) REFERENCES inventory_items(id)
            )'
        );
    }

    public function getAllPurchaseOrders(): array
    {
        $stmt = $this->query(
            'SELECT po.id, po.po_number, s.company_name AS supplier, p.name AS project_name, po.order_date, po.total_amount, po.status,
                    COALESCE((SELECT product_name FROM purchase_order_items WHERE purchase_order_id = po.id LIMIT 1), "") AS product_name,
                    COALESCE((SELECT quantity FROM purchase_order_items WHERE purchase_order_id = po.id LIMIT 1), 0) AS quantity,
                    COALESCE((SELECT price_rate FROM purchase_order_items WHERE purchase_order_id = po.id LIMIT 1), 0.00) AS price_rate
             FROM purchase_orders po
             LEFT JOIN suppliers s ON po.supplier_id = s.id
             LEFT JOIN projects p ON po.project_id = p.id
             WHERE po.company_id = ?
             ORDER BY po.order_date DESC, po.id DESC',
            [$this->currentCompanyId()]
        );

        return $stmt->fetchAll();
    }

    public function getWorkflowOrders(string $status): array
    {
        return $this->query(
            'SELECT po.*, s.company_name AS supplier, u.name AS requester_name, d.name AS decider_name
             FROM purchase_orders po LEFT JOIN suppliers s ON s.id = po.supplier_id
             LEFT JOIN users u ON u.id = po.requested_by LEFT JOIN users d ON d.id = po.workflow_decided_by
             WHERE po.company_id = ? AND po.workflow_status = ? ORDER BY po.created_at DESC, po.id DESC',
            [$this->currentCompanyId(), $status]
        )->fetchAll();
    }

    public function createWorkflowOrder(array $data, int $requestedBy, string $initialWorkflowStatus = 'pending_head_approval'): int
    {
        $supplierSelection = trim((string)($data['supplier_id'] ?? ''));
        $projectId = !empty($data['project_id']) ? (int)$data['project_id'] : null;
        $poNumber = trim((string)($data['po_number'] ?? '')) ?: 'PO-' . date('YmdHis');
        $orderDate = trim((string)($data['order_date'] ?? date('Y-m-d')));
        $productNames = (array)($data['product_name'] ?? []);
        $quantities = (array)($data['quantity'] ?? []);
        $priceRates = (array)($data['price_rate'] ?? []);
        $totalAmount = 0.0;
        foreach ($productNames as $index => $productName) {
            if (trim((string)$productName) !== '' && (int)($quantities[$index] ?? 0) > 0) {
                $totalAmount += (int)$quantities[$index] * max(0, (float)($priceRates[$index] ?? 0));
            }
        }
        if ($supplierSelection === '' || $productNames === [] || $totalAmount <= 0) {
            throw new InvalidArgumentException('Select a supplier and add at least one priced item.');
        }
        if (!in_array($initialWorkflowStatus, ['pending_head_approval', 'pending_procurement'], true)) {
            throw new InvalidArgumentException('Invalid purchase order workflow status.');
        }
        $supplierId = $supplierSelection === 'new'
            ? $this->findOrCreateSupplier($data)
            : (int)$supplierSelection;
        $supplier = $this->query('SELECT id FROM suppliers WHERE id = ? AND company_id = ? AND status = "active" LIMIT 1', [$supplierId, $this->currentCompanyId()])->fetch();
        if (!$supplier) {
            throw new InvalidArgumentException('Select a valid active supplier.');
        }
        $this->query('INSERT INTO purchase_orders (company_id, requested_by, po_number, supplier_id, project_id, order_date, total_amount, status, workflow_status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, "draft", ?, NOW())', [$this->currentCompanyId(), $requestedBy, $poNumber, $supplierId, $projectId, $orderDate, $totalAmount, $initialWorkflowStatus]);
        $purchaseOrderId = (int)$this->db->lastInsertId();
        $this->createPurchaseOrderItems($purchaseOrderId, $data);
        return $purchaseOrderId;
    }

    private function findOrCreateSupplier(array $data): int
    {
        $companyName = trim((string)($data['supplier_company_name'] ?? ''));
        if ($companyName === '') {
            throw new InvalidArgumentException('Enter the new supplier name.');
        }

        $existing = $this->query(
            'SELECT id FROM suppliers WHERE company_id = ? AND LOWER(company_name) = LOWER(?) LIMIT 1',
            [$this->currentCompanyId(), $companyName]
        )->fetch();
        if ($existing) {
            $this->query('UPDATE suppliers SET status = "active" WHERE id = ? AND company_id = ?', [(int)$existing['id'], $this->currentCompanyId()]);
            return (int)$existing['id'];
        }

        $supplierCode = 'SUP-' . date('YmdHis') . '-' . random_int(100, 999);
        $this->query(
            'INSERT INTO suppliers (company_id, supplier_code, company_name, contact_person, email, phone, address, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, "active", NOW())',
            [
                $this->currentCompanyId(),
                $supplierCode,
                $companyName,
                trim((string)($data['supplier_contact_person'] ?? '')),
                trim((string)($data['supplier_email'] ?? '')),
                trim((string)($data['supplier_phone'] ?? '')),
                trim((string)($data['supplier_address'] ?? '')) ?: null,
            ]
        );

        return (int)$this->db->lastInsertId();
    }

    public function decideWorkflowOrder(int $purchaseOrderId, int $decidedBy, string $decision, string $reason = '', bool $isHeadStoreReviewer = false): void
    {
        if (!in_array($decision, ['approved', 'denied', 'flagged'], true)) {
            throw new InvalidArgumentException('Invalid purchase order decision.');
        }
        if ($decision === 'flagged' && trim($reason) === '') {
            throw new InvalidArgumentException('Provide a reason when flagging a purchase order.');
        }
        $currentStatus = $this->query('SELECT workflow_status FROM purchase_orders WHERE id = ? AND company_id = ? LIMIT 1', [$purchaseOrderId, $this->currentCompanyId()])->fetchColumn();
        if (!in_array($currentStatus, ['pending_head_approval', 'pending_procurement', 'flagged'], true)) {
            throw new InvalidArgumentException('This purchase order is no longer awaiting a decision.');
        }
        if ($currentStatus === 'pending_head_approval' && !$isHeadStoreReviewer) {
            throw new InvalidArgumentException('This purchase order must be reviewed by the Head Store Keeper first.');
        }
        if ($currentStatus === 'pending_procurement' && $isHeadStoreReviewer) {
            throw new InvalidArgumentException('This purchase order is awaiting Procurement review.');
        }
        $this->query('UPDATE purchase_orders SET workflow_status = ?, workflow_reason = ?, workflow_decided_by = ?, workflow_decided_at = NOW() WHERE id = ? AND company_id = ? AND workflow_status IN ("pending_head_approval", "pending_procurement", "flagged")', [$decision, trim($reason) ?: null, $decidedBy, $purchaseOrderId, $this->currentCompanyId()]);
        if ($decision === 'approved') {
            $nextStatus = ($currentStatus === 'pending_head_approval' || ($currentStatus === 'flagged' && $isHeadStoreReviewer)) ? 'pending_procurement' : 'sent_to_logistics';
            $this->query('UPDATE purchase_orders SET workflow_status = ? WHERE id = ? AND company_id = ?', [$nextStatus, $purchaseOrderId, $this->currentCompanyId()]);
        }
    }

    public function getPurchaseOrderById(int $id): ?array
    {
        $stmt = $this->query(
            'SELECT po.id, po.po_number, po.supplier_id, po.project_id, s.company_name AS supplier, p.name AS project_name, po.order_date, po.total_amount, po.status,
                    s.email AS supplier_email, s.phone AS supplier_phone, s.address AS supplier_address
             FROM purchase_orders po
             LEFT JOIN suppliers s ON po.supplier_id = s.id
             LEFT JOIN projects p ON po.project_id = p.id
             WHERE po.id = ? AND po.company_id = ? LIMIT 1',
            [$id, $this->currentCompanyId()]
        );

        $purchaseOrder = $stmt->fetch();
        if (!$purchaseOrder) {
            return null;
        }

        $purchaseOrder['items'] = $this->getPurchaseOrderItems($id);
        $purchaseOrder['invoices'] = $this->getPurchaseOrderInvoices($id);
        return $purchaseOrder;
    }

    public function getPurchaseOrderInvoices(int $purchaseOrderId): array
    {
        $stmt = $this->query(
            'SELECT id, label, original_name, stored_name, file_type, file_size, uploaded_at
             FROM purchase_order_invoices
             WHERE purchase_order_id = ?
             ORDER BY uploaded_at DESC, id DESC',
            [$purchaseOrderId]
        );

        return $stmt->fetchAll();
    }

    public function getPurchaseOrderItems(int $purchaseOrderId): array
    {
        $stmt = $this->query(
            'SELECT poi.id, poi.product_name, poi.quantity, poi.price_rate, poi.line_total
             FROM purchase_order_items poi
             WHERE poi.purchase_order_id = ?
             ORDER BY poi.id ASC',
            [$purchaseOrderId]
        );

        return $stmt->fetchAll();
    }

    public function getReceivablePurchaseOrder(int $id): ?array
    {
        $order = $this->query('SELECT po.*, s.company_name AS supplier, s.contact_person, s.email AS supplier_email, s.phone AS supplier_phone, s.address AS supplier_address FROM purchase_orders po LEFT JOIN suppliers s ON s.id = po.supplier_id WHERE po.id = ? AND po.company_id = ? AND po.workflow_status = "sent_to_logistics" LIMIT 1', [$id, $this->currentCompanyId()])->fetch();
        if (!$order) {
            return null;
        }
        $order['items'] = $this->getPurchaseOrderItems($id);
        return $order;
    }

    public function receivePurchaseOrder(int $purchaseOrderId, int $receivedBy, array $items): int
    {
        $companyId = $this->currentCompanyId();
        $this->db->beginTransaction();
        try {
            $order = $this->getReceivablePurchaseOrder($purchaseOrderId);
            if (!$order) {
                throw new InvalidArgumentException('This purchase order is not ready to be received.');
            }
            $receiptNumber = 'GRN-' . date('YmdHis') . '-' . random_int(100, 999);
            $this->query('INSERT INTO inventory_receipts (company_id, purchase_order_id, receipt_number, received_by) VALUES (?, ?, ?, ?)', [$companyId, $purchaseOrderId, $receiptNumber, $receivedBy ?: null]);
            $receiptId = (int)$this->db->lastInsertId();
            $receivedTotal = 0.0;
            foreach ($order['items'] as $orderItem) {
                $orderItemId = (int)$orderItem['id'];
                $quantity = max(0, (float)($items[$orderItemId]['quantity_received'] ?? 0));
                if ($quantity <= 0) {
                    continue;
                }
                $inventoryItemId = (int)($items[$orderItemId]['inventory_item_id'] ?? 0);
                if ($inventoryItemId <= 0) {
                    $inventoryItemId = (int)$this->query('SELECT id FROM inventory_items WHERE company_id = ? AND (item_code = ? OR LOWER(name) = LOWER(?)) LIMIT 1', [$companyId, trim((string)$orderItem['product_name']), trim((string)$orderItem['product_name'])])->fetchColumn();
                }
                $inventory = $this->query('SELECT id, cost_price FROM inventory_items WHERE id = ? AND company_id = ? FOR UPDATE', [$inventoryItemId, $companyId])->fetch();
                if (!$inventory) {
                    throw new InvalidArgumentException('Map every received PO item to an inventory item before receiving.');
                }
                $priceRate = max(0, (float)($items[$orderItemId]['price_rate'] ?? $orderItem['price_rate']));
                $this->query('UPDATE inventory_items SET current_stock = current_stock + ?, free_stock = free_stock + ?, cost_price = ?, supplier_name = ?, supplier_contact = ?, supplier_phone = ?, supplier_address = ? WHERE id = ? AND company_id = ?', [$quantity, $quantity, $priceRate, $order['supplier'], $order['contact_person'], $order['supplier_phone'], $order['supplier_address'], $inventoryItemId, $companyId]);
                $this->query('INSERT INTO inventory_receipt_items (receipt_id, purchase_order_item_id, inventory_item_id, quantity_received, price_rate) VALUES (?, ?, ?, ?, ?)', [$receiptId, $orderItemId, $inventoryItemId, $quantity, $priceRate]);
                $receivedTotal += $quantity * $priceRate;
            }
            if ($receivedTotal <= 0) {
                throw new InvalidArgumentException('Enter at least one received quantity.');
            }
            $this->query('UPDATE purchase_orders SET status = "received", workflow_status = "approved", total_amount = ? WHERE id = ? AND company_id = ?', [$receivedTotal, $purchaseOrderId, $companyId]);
            $this->db->commit();
            return $receiptId;
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }

    public function getReceipt(int $receiptId): ?array
    {
        $receipt = $this->query('SELECT ir.*, po.po_number, s.company_name AS supplier, u.name AS received_by_name FROM inventory_receipts ir INNER JOIN purchase_orders po ON po.id = ir.purchase_order_id AND po.company_id = ir.company_id LEFT JOIN suppliers s ON s.id = po.supplier_id LEFT JOIN users u ON u.id = ir.received_by WHERE ir.id = ? AND ir.company_id = ? LIMIT 1', [$receiptId, $this->currentCompanyId()])->fetch();
        if (!$receipt) {
            return null;
        }
        $receipt['items'] = $this->query('SELECT iri.*, poi.product_name FROM inventory_receipt_items iri INNER JOIN purchase_order_items poi ON poi.id = iri.purchase_order_item_id WHERE iri.receipt_id = ? ORDER BY iri.id ASC', [$receiptId])->fetchAll();
        return $receipt;
    }

    public function getSuppliers(): array
    {
        $stmt = $this->query(
            'SELECT id, supplier_code, company_name FROM suppliers WHERE company_id = ? AND status = "active" ORDER BY company_name ASC',
            [$this->currentCompanyId()]
        );
        return $stmt->fetchAll();
    }

    public function getProjects(): array
    {
        $stmt = $this->query(
            'SELECT id, project_number, name FROM projects WHERE company_id = ? ORDER BY project_number ASC',
            [$this->currentCompanyId()]
        );
        return $stmt->fetchAll();
    }

    public function getSupplierIdByCode(string $supplierCode): ?int
    {
        $stmt = $this->query('SELECT id FROM suppliers WHERE LOWER(supplier_code) = ? AND company_id = ? LIMIT 1', [trim(strtolower($supplierCode)), $this->currentCompanyId()]);
        $row = $stmt->fetch();
        return $row ? (int)$row['id'] : null;
    }

    public function getProjectIdByNumber(string $projectNumber): ?int
    {
        $stmt = $this->query('SELECT id FROM projects WHERE LOWER(project_number) = ? AND company_id = ? LIMIT 1', [trim(strtolower($projectNumber)), $this->currentCompanyId()]);
        $row = $stmt->fetch();
        return $row ? (int)$row['id'] : null;
    }

    public function createPurchaseOrder(array $data): int
    {
        $poNumber = trim((string)($data['po_number'] ?? '')) ?: 'PO-' . time();
        $supplierId = (int)($data['supplier_id'] ?? 0);
        $projectId = !empty($data['project_id']) ? (int)$data['project_id'] : null;
        $orderDate = $data['order_date'] ?? date('Y-m-d');
        $totalAmount = max(0, (float)($data['total_amount'] ?? 0));
        $status = trim((string)($data['status'] ?? 'draft')) ?: 'draft';

        $this->query(
            'INSERT INTO purchase_orders (company_id, po_number, supplier_id, project_id, order_date, total_amount, status, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, NOW())',
            [$this->currentCompanyId(), $poNumber, $supplierId, $projectId, $orderDate, $totalAmount, $status]
        );

        $purchaseOrderId = (int)$this->db->lastInsertId();
        $this->createPurchaseOrderItems($purchaseOrderId, $data);

        return $purchaseOrderId;
    }

    public function createPurchaseOrderItems(int $purchaseOrderId, array $data): void
    {
        $productNames = $data['product_name'] ?? [];
        $quantities = $data['quantity'] ?? [];
        $priceRates = $data['price_rate'] ?? [];

        foreach ($productNames as $index => $productName) {
            $productName = trim((string)$productName);
            $quantity = max(0, (int)($quantities[$index] ?? 0));
            $priceRate = max(0, (float)($priceRates[$index] ?? 0));

            if ($productName === '' || $quantity <= 0 || $priceRate <= 0) {
                continue;
            }

            $lineTotal = $quantity * $priceRate;
            $this->query(
                'INSERT INTO purchase_order_items (purchase_order_id, product_name, quantity, price_rate, line_total, created_at)
                 VALUES (?, ?, ?, ?, ?, NOW())',
                [$purchaseOrderId, $productName, $quantity, $priceRate, $lineTotal]
            );
        }
    }

    public function createPurchaseOrderInvoice(int $purchaseOrderId, string $label, string $originalName, string $storedName, string $fileType, int $fileSize): void
    {
        $this->query(
            'INSERT INTO purchase_order_invoices (purchase_order_id, label, original_name, stored_name, file_type, file_size, uploaded_at)
             VALUES (?, ?, ?, ?, ?, ?, NOW())',
            [$purchaseOrderId, $label, $originalName, $storedName, $fileType, $fileSize]
        );
    }

    public function updatePurchaseOrder(int $purchaseOrderId, array $data, string $reason = ''): void
    {
        $existing = $this->getPurchaseOrderById($purchaseOrderId);
        if (!$existing) {
            return;
        }

        $poNumber = trim((string)($data['po_number'] ?? $existing['po_number'] ?? '')) ?: 'PO-' . time();
        $supplierId = (int)($data['supplier_id'] ?? 0) ?: $this->getSupplierIdByCode(trim((string)($data['supplier_code'] ?? '')));
        $projectId = !empty($data['project_id']) ? (int)$data['project_id'] : null;
        $orderDate = $data['order_date'] ?? $existing['order_date'] ?? date('Y-m-d');
        $totalAmount = max(0, (float)($data['total_amount'] ?? $existing['total_amount'] ?? 0));
        $status = trim((string)($data['status'] ?? $existing['status'] ?? 'draft')) ?: 'draft';

        $this->query(
            'UPDATE purchase_orders SET po_number = ?, supplier_id = ?, project_id = ?, order_date = ?, total_amount = ?, status = ? WHERE id = ?',
            [$poNumber, $supplierId, $projectId, $orderDate, $totalAmount, $status, $purchaseOrderId]
        );

        $this->query('DELETE FROM purchase_order_items WHERE purchase_order_id = ?', [$purchaseOrderId]);
        $this->createPurchaseOrderItems($purchaseOrderId, $data);

        if (trim($reason) !== '') {
            $after = $this->getPurchaseOrderById($purchaseOrderId);
            $this->recordPurchaseOrderChange($purchaseOrderId, $existing, $after, trim($reason));
        }
    }

    public function savePurchaseOrderInvoices(int $purchaseOrderId): void
    {
        if (empty($_FILES['po_invoices']['name']) || !is_array($_FILES['po_invoices']['name'])) {
            return;
        }

        $uploadDir = UPLOAD_DIR . 'purchase_order_invoices/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $labels = $_POST['invoice_label'] ?? [];
        foreach ($_FILES['po_invoices']['name'] as $index => $name) {
            if (empty($name)) {
                continue;
            }

            $tmpName = $_FILES['po_invoices']['tmp_name'][$index] ?? '';
            if (!is_uploaded_file($tmpName)) {
                continue;
            }

            $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', $name);
            $storedName = uniqid('poinv_', true) . '_' . $safeName;
            $destination = $uploadDir . $storedName;

            if (!move_uploaded_file($tmpName, $destination)) {
                continue;
            }

            $label = trim((string)($labels[$index] ?? 'Invoice'));
            if ($label === '') {
                $label = 'Invoice';
            }

            $this->createPurchaseOrderInvoice(
                $purchaseOrderId,
                $label,
                $name,
                $storedName,
                mime_content_type($destination) ?: 'application/octet-stream',
                filesize($destination)
            );
        }
    }

    private function recordPurchaseOrderChange(int $purchaseOrderId, array $before, array $after, string $reason): void
    {
        $this->query(
            'INSERT INTO purchase_order_changes (purchase_order_id, change_reason, before_data, after_data, changed_at)
             VALUES (?, ?, ?, ?, NOW())',
            [$purchaseOrderId, $reason, json_encode($before), json_encode($after)]
        );
    }

    public function getPurchaseOrderChangeHistory(int $purchaseOrderId): array
    {
        $stmt = $this->query(
            'SELECT change_reason, before_data, after_data, changed_at
             FROM purchase_order_changes
             WHERE purchase_order_id = ?
             ORDER BY changed_at DESC',
            [$purchaseOrderId]
        );

        return $stmt->fetchAll();
    }

    public function createPurchaseOrdersFromRows(array $rows): array
    {
        $created = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $poNumber = trim((string)($row['po_number'] ?? ''));
            $supplierCode = trim((string)($row['supplier_code'] ?? ''));
            $projectNumber = trim((string)($row['project_number'] ?? ''));
            $orderDate = trim((string)($row['order_date'] ?? date('Y-m-d')));
            $totalAmount = max(0, (float)($row['total_amount'] ?? 0));
            $status = trim((string)($row['status'] ?? 'draft')) ?: 'draft';
            $productName = trim((string)($row['product_name'] ?? ''));
            $quantity = max(0, (int)($row['quantity'] ?? 0));
            $priceRate = max(0, (float)($row['price_rate'] ?? 0));

            if ($poNumber === '' || $supplierCode === '' || $productName === '' || $quantity <= 0 || $priceRate <= 0) {
                $skipped++;
                continue;
            }

            $supplierId = $this->getSupplierIdByCode($supplierCode);
            if ($supplierId === null) {
                $skipped++;
                continue;
            }

            $projectId = null;
            if ($projectNumber !== '') {
                $projectId = $this->getProjectIdByNumber($projectNumber);
            }

            $this->query(
                'INSERT IGNORE INTO purchase_orders (po_number, supplier_id, project_id, order_date, total_amount, status, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, NOW())',
                [$poNumber, $supplierId, $projectId, $orderDate, $totalAmount, $status]
            );

            $purchaseOrderId = (int)$this->db->lastInsertId();
            if ($purchaseOrderId > 0) {
                $this->createPurchaseOrderItems($purchaseOrderId, ['product_name' => [$productName], 'quantity' => [$quantity], 'price_rate' => [$priceRate]]);
                $created++;
            } else {
                $skipped++;
            }
        }

        return ['created' => $created, 'skipped' => $skipped];
    }
}
