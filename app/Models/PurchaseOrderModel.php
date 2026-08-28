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
        $this->query('ALTER TABLE purchase_orders ADD COLUMN IF NOT EXISTS workflow_status ENUM("draft", "pending_head_approval", "approved", "denied", "flagged", "sent_to_logistics") NOT NULL DEFAULT "draft" AFTER status');
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

    public function createWorkflowOrder(array $data, int $requestedBy): int
    {
        $supplierId = (int)($data['supplier_id'] ?? 0);
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
        if ($supplierId <= 0 || $productNames === [] || $totalAmount <= 0) {
            throw new InvalidArgumentException('Select a supplier and add at least one priced item.');
        }
        $this->query('INSERT INTO purchase_orders (company_id, requested_by, po_number, supplier_id, project_id, order_date, total_amount, status, workflow_status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, "draft", "pending_head_approval", NOW())', [$this->currentCompanyId(), $requestedBy, $poNumber, $supplierId, $projectId, $orderDate, $totalAmount]);
        $purchaseOrderId = (int)$this->db->lastInsertId();
        $this->createPurchaseOrderItems($purchaseOrderId, $data);
        return $purchaseOrderId;
    }

    public function decideWorkflowOrder(int $purchaseOrderId, int $decidedBy, string $decision, string $reason = ''): void
    {
        if (!in_array($decision, ['approved', 'denied', 'flagged'], true)) {
            throw new InvalidArgumentException('Invalid purchase order decision.');
        }
        if ($decision === 'flagged' && trim($reason) === '') {
            throw new InvalidArgumentException('Provide a reason when flagging a purchase order.');
        }
        $this->query('UPDATE purchase_orders SET workflow_status = ?, workflow_reason = ?, workflow_decided_by = ?, workflow_decided_at = NOW() WHERE id = ? AND company_id = ? AND workflow_status IN ("pending_head_approval", "flagged")', [$decision, trim($reason) ?: null, $decidedBy, $purchaseOrderId, $this->currentCompanyId()]);
        if ($decision === 'approved') {
            $this->query('UPDATE purchase_orders SET workflow_status = "sent_to_logistics" WHERE id = ? AND company_id = ?', [$purchaseOrderId, $this->currentCompanyId()]);
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
            'SELECT product_name, quantity, price_rate, line_total
             FROM purchase_order_items
             WHERE purchase_order_id = ?
             ORDER BY id ASC',
            [$purchaseOrderId]
        );

        return $stmt->fetchAll();
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
