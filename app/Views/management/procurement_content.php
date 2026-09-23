<?php
$pendingOrders = $pendingOrders ?? [];
$flaggedOrders = $flaggedOrders ?? [];
$logisticsOrders = $logisticsOrders ?? [];


?>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1">Construction Procurement</h4>
        <p class="text-muted mb-0">Track the full purchasing cycle from project need to supplier closeout.</p>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary" href="/ERP/public/modules/inventory"><i class="bi bi-box-seam me-1"></i>Inventory</a>
    </div>
</div>
<?php if (!empty($_SESSION['procurement_flash'])): ?><div class="alert alert-info"><?php echo htmlspecialchars($_SESSION['procurement_flash']); unset($_SESSION['procurement_flash']); ?></div><?php endif; ?>

<?php
$tabbedRequests = [
    'requisition' => [],
    'inventory' => [],
    'invoice_review' => [],
];

if (!empty($procurementRequests)) {
    foreach ($procurementRequests as $request) {
        $requestType = strtolower((string)($request['source_type'] ?? 'requisition'));
        $requestKey = ($requestType === 'inventory') ? 'inventory' : 'requisition';

        if (!empty($request['selected_invoice_id']) || !isset($request['selected_invoice_id']) && !empty($request['invoices'])) {
            $requestKey = 'invoice_review';
        }

        $tabbedRequests[$requestKey][] = $request;
    }
}
?>

<?php if (!empty($procurementRequests)): ?>
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
            <h5 class="fw-bold mb-0">Procurement Request Queue</h5>
            <div class="d-flex flex-wrap gap-2">
                <span class="badge bg-warning-subtle text-warning px-2 py-2">Requisition</span>
                <span class="badge bg-info-subtle text-info px-2 py-2">Inventory</span>
                <span class="badge bg-success-subtle text-success px-2 py-2">Invoice Review</span>
            </div>
        </div>

        <ul class="nav nav-tabs mb-3" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="req-tab" data-bs-toggle="tab" data-bs-target="#req-panel" type="button" role="tab" aria-controls="req-panel" aria-selected="true">
                    Requisition <span class="badge text-bg-light ms-1"><?php echo count($tabbedRequests['requisition']); ?></span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="inventory-tab" data-bs-toggle="tab" data-bs-target="#inventory-panel" type="button" role="tab" aria-controls="inventory-panel" aria-selected="false">
                    Inventory <span class="badge text-bg-light ms-1"><?php echo count($tabbedRequests['inventory']); ?></span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="invoice-tab" data-bs-toggle="tab" data-bs-target="#invoice-panel" type="button" role="tab" aria-controls="invoice-panel" aria-selected="false">
                    Invoice Review <span class="badge text-bg-light ms-1"><?php echo count($tabbedRequests['invoice_review']); ?></span>
                </button>
            </li>
        </ul>

        <div class="tab-content">
            <div class="tab-pane fade show active" id="req-panel" role="tabpanel" aria-labelledby="req-tab">
                <?php if (empty($tabbedRequests['requisition'])): ?>
                    <p class="text-muted mb-0">No requisition requests are awaiting procurement.</p>
                <?php else: ?>
                    <?php foreach ($tabbedRequests['requisition'] as $request): ?>
                        <?php
                        $sourceType = strtolower((string)($request['source_type'] ?? 'requisition'));
                        $sourceBadgeTone = ($sourceType === 'inventory') ? 'bg-info-subtle text-info' : 'bg-warning-subtle text-warning';
                        $requestStatus = 'Awaiting invoice';
                        $requestStatusTone = 'bg-secondary-subtle text-secondary';
                        if (!empty($request['selected_invoice_id'])) {
                            $requestStatus = 'Invoice selected';
                            $requestStatusTone = 'bg-success-subtle text-success';
                        } elseif (!empty($request['invoices'])) {
                            $requestStatus = 'Invoices received';
                            $requestStatusTone = 'bg-primary-subtle text-primary';
                        }
                        ?>
                        <div class="border rounded-4 p-3 mb-3">
                            <div class="d-flex flex-column flex-md-row justify-content-between gap-2 mb-2">
                                <div>
                                    <div class="fw-semibold"><?php echo htmlspecialchars((string)($request['source_label'] ?? $request['title'] ?? 'Procurement request')); ?></div>
                                    <div class="small text-muted"><?php echo htmlspecialchars((string)($request['source_reference'] ?? 'Request')); ?> · <?php echo htmlspecialchars((string)($request['item_name'] ?? 'Material request')); ?> · <?php echo number_format((float)($request['quantity'] ?? 0), 2); ?> units</div>
                                </div>
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    <span class="badge <?php echo $sourceBadgeTone; ?> px-2 py-1"><?php echo htmlspecialchars(ucfirst($sourceType === 'inventory' ? 'Inventory' : 'Requisition')); ?></span>
                                    <span class="badge <?php echo $requestStatusTone; ?> px-2 py-1"><?php echo htmlspecialchars($requestStatus); ?></span>
                                </div>
                            </div>

                            <div class="border rounded-3 p-3 bg-light-subtle mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="small text-uppercase text-muted fw-semibold">Invoice approval panel</div>
                                    <?php if (!empty($request['invoices'])): ?>
                                        <span class="badge text-bg-light"><?php echo count($request['invoices']); ?> invoice(s)</span>
                                    <?php endif; ?>
                                </div>

                                <?php if (empty($request['invoices'])): ?>
                                    <div class="text-muted small">No vendor invoice uploaded yet.</div>
                                <?php else: ?>
                                    <?php foreach ($request['invoices'] as $invoice): ?>
                                        <?php
                                        $invoiceStatus = !empty($invoice['is_selected']) ? 'Approved for PO' : 'Awaiting approval';
                                        $invoiceTone = !empty($invoice['is_selected']) ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning';
                                        ?>
                                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 border rounded p-2 mb-2 bg-white">
                                            <div>
                                                <div class="fw-semibold"><?php echo htmlspecialchars((string)($invoice['vendor_name'] ?? 'Vendor')); ?></div>
                                                <div class="small text-muted"><?php echo htmlspecialchars((string)($invoice['invoice_number'] ?? $invoice['label'] ?? 'Invoice')); ?> · <?php echo htmlspecialchars((string)($invoice['original_name'] ?? 'document')); ?></div>
                                            </div>
                                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                                <span class="badge <?php echo $invoiceTone; ?> px-2 py-1"><?php echo htmlspecialchars($invoiceStatus); ?></span>
                                                <form method="post" action="/ERP/public/management/procurement/invoice/select">
                                                    <input type="hidden" name="invoice_id" value="<?php echo (int)$invoice['id']; ?>">
                                                    <button class="btn btn-sm <?php echo !empty($invoice['is_selected']) ? 'btn-success' : 'btn-outline-success'; ?>" type="submit"><?php echo !empty($invoice['is_selected']) ? 'Selected' : 'Approve'; ?></button>
                                                </form>
                                                <a class="btn btn-sm btn-outline-secondary" href="/ERP/public/uploads/procurement_invoices/<?php echo rawurlencode((string)$invoice['stored_name']); ?>" target="_blank" rel="noopener">Open</a>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>

                            <form method="post" action="/ERP/public/management/procurement/invoice/upload" enctype="multipart/form-data" class="row g-2 align-items-end">
                                <input type="hidden" name="request_source" value="<?php echo htmlspecialchars((string)($request['source_type'] ?? 'requisition')); ?>">
                                <input type="hidden" name="source_id" value="<?php echo (int)($request['request_id'] ?? 0); ?>">
                                <div class="col-md-3">
                                    <label class="form-label">Vendor</label>
                                    <input class="form-control" name="vendor_name" placeholder="Vendor name">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Invoice No.</label>
                                    <input class="form-control" name="invoice_number" placeholder="INV-001">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Invoice Label</label>
                                    <input class="form-control" name="invoice_label" placeholder="Invoice / quotation">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">File</label>
                                    <input class="form-control" type="file" name="vendor_invoice" accept="application/pdf,image/*,.pdf,.png,.jpg,.jpeg">
                                </div>
                                <div class="col-12">
                                    <button class="btn btn-primary" type="submit"><i class="bi bi-file-earmark-arrow-up me-1"></i>Upload invoice</button>
                                </div>
                            </form>

                            <?php if (!empty($request['selected_invoice_id'])): ?>
                                <form method="post" action="/ERP/public/management/procurement/invoice/raise" class="mt-3">
                                    <input type="hidden" name="invoice_id" value="<?php echo (int)$request['selected_invoice_id']; ?>">
                                    <input type="hidden" name="source_reference" value="<?php echo htmlspecialchars((string)($request['source_reference'] ?? '')); ?>">
                                    <input type="hidden" name="source_label" value="<?php echo htmlspecialchars((string)($request['source_label'] ?? $request['title'] ?? 'Procurement request')); ?>">
                                    <div class="row g-2 align-items-end">
                                        <div class="col-md-4">
                                            <label class="form-label">Supplier</label>
                                            <select class="form-select" name="supplier_id" required>
                                                <option value="">Select supplier</option>
                                                <?php foreach (($suppliers ?? []) as $supplier): ?><option value="<?php echo (int)$supplier['id']; ?>"><?php echo htmlspecialchars($supplier['supplier_code'] . ' - ' . $supplier['company_name']); ?></option><?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Project</label>
                                            <select class="form-select" name="project_id">
                                                <option value="">Not linked to a project</option>
                                                <?php foreach (($projects ?? []) as $project): ?><option value="<?php echo (int)$project['id']; ?>"><?php echo htmlspecialchars($project['project_number'] . ' - ' . $project['name']); ?></option><?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">PO Number</label>
                                            <input class="form-control" name="po_number" placeholder="Optional">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Order Date</label>
                                            <input class="form-control" type="date" name="order_date" value="<?php echo date('Y-m-d'); ?>" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Item</label>
                                            <input class="form-control" name="product_name[]" value="<?php echo htmlspecialchars((string)($request['item_name'] ?? 'Procurement item')); ?>" required>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Qty</label>
                                            <input class="form-control" type="number" min="1" name="quantity[]" value="<?php echo (float)($request['quantity'] ?? 1); ?>" required>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Rate</label>
                                            <input class="form-control" type="number" min="0.01" step="0.01" name="price_rate[]" value="0.00" required>
                                        </div>
                                        <div class="col-12">
                                            <button class="btn btn-primary" type="submit"><i class="bi bi-file-earmark-check me-1"></i>Raise PO from selected invoice</button>
                                        </div>
                                    </div>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="tab-pane fade" id="inventory-panel" role="tabpanel" aria-labelledby="inventory-tab">
                <?php if (empty($tabbedRequests['inventory'])): ?>
                    <p class="text-muted mb-0">No inventory requests are awaiting procurement.</p>
                <?php else: ?>
                    <?php foreach ($tabbedRequests['inventory'] as $request): ?>
                        <?php
                        $requestStatus = 'Awaiting invoice';
                        $requestStatusTone = 'bg-secondary-subtle text-secondary';
                        if (!empty($request['selected_invoice_id'])) {
                            $requestStatus = 'Invoice selected';
                            $requestStatusTone = 'bg-success-subtle text-success';
                        } elseif (!empty($request['invoices'])) {
                            $requestStatus = 'Invoices received';
                            $requestStatusTone = 'bg-primary-subtle text-primary';
                        }
                        ?>
                        <div class="border rounded-4 p-3 mb-3">
                            <div class="d-flex flex-column flex-md-row justify-content-between gap-2 mb-2">
                                <div>
                                    <div class="fw-semibold"><?php echo htmlspecialchars((string)($request['source_label'] ?? $request['title'] ?? 'Inventory request')); ?></div>
                                    <div class="small text-muted"><?php echo htmlspecialchars((string)($request['source_reference'] ?? 'Request')); ?> · <?php echo htmlspecialchars((string)($request['item_name'] ?? 'Inventory item')); ?> · <?php echo number_format((float)($request['quantity'] ?? 0), 2); ?> units</div>
                                </div>
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    <span class="badge bg-info-subtle text-info px-2 py-1">Inventory</span>
                                    <span class="badge <?php echo $requestStatusTone; ?> px-2 py-1"><?php echo htmlspecialchars($requestStatus); ?></span>
                                </div>
                            </div>

                            <div class="border rounded-3 p-3 bg-light-subtle mb-3">
                                <div class="small text-uppercase text-muted fw-semibold mb-2">Invoice approval panel</div>
                                <?php if (empty($request['invoices'])): ?>
                                    <div class="text-muted small">No vendor invoice uploaded yet.</div>
                                <?php else: ?>
                                    <?php foreach ($request['invoices'] as $invoice): ?>
                                        <?php
                                        $invoiceStatus = !empty($invoice['is_selected']) ? 'Approved for PO' : 'Awaiting approval';
                                        $invoiceTone = !empty($invoice['is_selected']) ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning';
                                        ?>
                                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 border rounded p-2 mb-2 bg-white">
                                            <div>
                                                <div class="fw-semibold"><?php echo htmlspecialchars((string)($invoice['vendor_name'] ?? 'Vendor')); ?></div>
                                                <div class="small text-muted"><?php echo htmlspecialchars((string)($invoice['invoice_number'] ?? $invoice['label'] ?? 'Invoice')); ?> · <?php echo htmlspecialchars((string)($invoice['original_name'] ?? 'document')); ?></div>
                                            </div>
                                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                                <span class="badge <?php echo $invoiceTone; ?> px-2 py-1"><?php echo htmlspecialchars($invoiceStatus); ?></span>
                                                <form method="post" action="/ERP/public/management/procurement/invoice/select">
                                                    <input type="hidden" name="invoice_id" value="<?php echo (int)$invoice['id']; ?>">
                                                    <button class="btn btn-sm <?php echo !empty($invoice['is_selected']) ? 'btn-success' : 'btn-outline-success'; ?>" type="submit"><?php echo !empty($invoice['is_selected']) ? 'Selected' : 'Approve'; ?></button>
                                                </form>
                                                <a class="btn btn-sm btn-outline-secondary" href="/ERP/public/uploads/procurement_invoices/<?php echo rawurlencode((string)$invoice['stored_name']); ?>" target="_blank" rel="noopener">Open</a>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>

                            <form method="post" action="/ERP/public/management/procurement/invoice/upload" enctype="multipart/form-data" class="row g-2 align-items-end">
                                <input type="hidden" name="request_source" value="<?php echo htmlspecialchars((string)($request['source_type'] ?? 'inventory')); ?>">
                                <input type="hidden" name="source_id" value="<?php echo (int)($request['request_id'] ?? 0); ?>">
                                <div class="col-md-3">
                                    <label class="form-label">Vendor</label>
                                    <input class="form-control" name="vendor_name" placeholder="Vendor name">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Invoice No.</label>
                                    <input class="form-control" name="invoice_number" placeholder="INV-001">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Invoice Label</label>
                                    <input class="form-control" name="invoice_label" placeholder="Invoice / quotation">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">File</label>
                                    <input class="form-control" type="file" name="vendor_invoice" accept="application/pdf,image/*,.pdf,.png,.jpg,.jpeg">
                                </div>
                                <div class="col-12">
                                    <button class="btn btn-primary" type="submit"><i class="bi bi-file-earmark-arrow-up me-1"></i>Upload invoice</button>
                                </div>
                            </form>

                            <?php if (!empty($request['selected_invoice_id'])): ?>
                                <form method="post" action="/ERP/public/management/procurement/invoice/raise" class="mt-3">
                                    <input type="hidden" name="invoice_id" value="<?php echo (int)$request['selected_invoice_id']; ?>">
                                    <input type="hidden" name="source_reference" value="<?php echo htmlspecialchars((string)($request['source_reference'] ?? '')); ?>">
                                    <input type="hidden" name="source_label" value="<?php echo htmlspecialchars((string)($request['source_label'] ?? $request['title'] ?? 'Inventory request')); ?>">
                                    <div class="row g-2 align-items-end">
                                        <div class="col-md-4">
                                            <label class="form-label">Supplier</label>
                                            <select class="form-select" name="supplier_id" required>
                                                <option value="">Select supplier</option>
                                                <?php foreach (($suppliers ?? []) as $supplier): ?><option value="<?php echo (int)$supplier['id']; ?>"><?php echo htmlspecialchars($supplier['supplier_code'] . ' - ' . $supplier['company_name']); ?></option><?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Project</label>
                                            <select class="form-select" name="project_id">
                                                <option value="">Not linked to a project</option>
                                                <?php foreach (($projects ?? []) as $project): ?><option value="<?php echo (int)$project['id']; ?>"><?php echo htmlspecialchars($project['project_number'] . ' - ' . $project['name']); ?></option><?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">PO Number</label>
                                            <input class="form-control" name="po_number" placeholder="Optional">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Order Date</label>
                                            <input class="form-control" type="date" name="order_date" value="<?php echo date('Y-m-d'); ?>" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Item</label>
                                            <input class="form-control" name="product_name[]" value="<?php echo htmlspecialchars((string)($request['item_name'] ?? 'Inventory item')); ?>" required>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Qty</label>
                                            <input class="form-control" type="number" min="1" name="quantity[]" value="<?php echo (float)($request['quantity'] ?? 1); ?>" required>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Rate</label>
                                            <input class="form-control" type="number" min="0.01" step="0.01" name="price_rate[]" value="0.00" required>
                                        </div>
                                        <div class="col-12">
                                            <button class="btn btn-primary" type="submit"><i class="bi bi-file-earmark-check me-1"></i>Raise PO from selected invoice</button>
                                        </div>
                                    </div>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="tab-pane fade" id="invoice-panel" role="tabpanel" aria-labelledby="invoice-tab">
                <?php if (empty($tabbedRequests['invoice_review'])): ?>
                    <p class="text-muted mb-0">No invoice approval tasks are pending.</p>
                <?php else: ?>
                    <?php foreach ($tabbedRequests['invoice_review'] as $request): ?>
                        <?php
                        $requestStatus = 'Awaiting invoice';
                        $requestStatusTone = 'bg-secondary-subtle text-secondary';
                        if (!empty($request['selected_invoice_id'])) {
                            $requestStatus = 'Invoice selected';
                            $requestStatusTone = 'bg-success-subtle text-success';
                        } elseif (!empty($request['invoices'])) {
                            $requestStatus = 'Invoices received';
                            $requestStatusTone = 'bg-primary-subtle text-primary';
                        }
                        ?>
                        <div class="border rounded-4 p-3 mb-3">
                            <div class="d-flex flex-column flex-md-row justify-content-between gap-2 mb-2">
                                <div>
                                    <div class="fw-semibold"><?php echo htmlspecialchars((string)($request['source_label'] ?? $request['title'] ?? 'Vendor request')); ?></div>
                                    <div class="small text-muted"><?php echo htmlspecialchars((string)($request['source_reference'] ?? 'Request')); ?> · <?php echo htmlspecialchars((string)($request['item_name'] ?? 'Material request')); ?> · <?php echo number_format((float)($request['quantity'] ?? 0), 2); ?> units</div>
                                </div>
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    <span class="badge bg-success-subtle text-success px-2 py-1">Invoice review</span>
                                    <span class="badge <?php echo $requestStatusTone; ?> px-2 py-1"><?php echo htmlspecialchars($requestStatus); ?></span>
                                </div>
                            </div>

                            <div class="border rounded-3 p-3 bg-light-subtle">
                                <div class="small text-uppercase text-muted fw-semibold mb-2">Vendor invoice approval</div>
                                <?php foreach ($request['invoices'] as $invoice): ?>
                                    <?php
                                    $invoiceStatus = !empty($invoice['is_selected']) ? 'Selected for PO' : 'Pending approval';
                                    $invoiceTone = !empty($invoice['is_selected']) ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning';
                                    ?>
                                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 border rounded p-2 mb-2 bg-white">
                                        <div>
                                            <div class="fw-semibold"><?php echo htmlspecialchars((string)($invoice['vendor_name'] ?? 'Vendor')); ?></div>
                                            <div class="small text-muted"><?php echo htmlspecialchars((string)($invoice['invoice_number'] ?? $invoice['label'] ?? 'Invoice')); ?> · <?php echo htmlspecialchars((string)($invoice['original_name'] ?? 'document')); ?></div>
                                        </div>
                                        <div class="d-flex align-items-center gap-2 flex-wrap">
                                            <span class="badge <?php echo $invoiceTone; ?> px-2 py-1"><?php echo htmlspecialchars($invoiceStatus); ?></span>
                                            <form method="post" action="/ERP/public/management/procurement/invoice/select">
                                                <input type="hidden" name="invoice_id" value="<?php echo (int)$invoice['id']; ?>">
                                                <button class="btn btn-sm <?php echo !empty($invoice['is_selected']) ? 'btn-success' : 'btn-outline-success'; ?>" type="submit"><?php echo !empty($invoice['is_selected']) ? 'Selected' : 'Approve'; ?></button>
                                            </form>
                                            <a class="btn btn-sm btn-outline-secondary" href="/ERP/public/uploads/procurement_invoices/<?php echo rawurlencode((string)$invoice['stored_name']); ?>" target="_blank" rel="noopener">Open</a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <h5 class="fw-bold mb-3">Procurement Control Checklist</h5>
        <div class="row g-3">
            <div class="col-md-6 col-lg-3">
                <div class="border rounded-3 p-3 h-100">
                    <div class="small text-uppercase text-muted fw-semibold mb-2">Planning</div>
                    <ul class="small mb-0 ps-3 text-muted">
                        <li>Scope and project need defined</li>
                        <li>Quantity and specification confirmed</li>
                        <li>Budget aligned with project estimate</li>
                    </ul>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="border rounded-3 p-3 h-100">
                    <div class="small text-uppercase text-muted fw-semibold mb-2">Sourcing</div>
                    <ul class="small mb-0 ps-3 text-muted">
                        <li>RFQ or tender issued</li>
                        <li>Supplier shortlist approved</li>
                        <li>Quotes compared on value and risk</li>
                    </ul>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="border rounded-3 p-3 h-100">
                    <div class="small text-uppercase text-muted fw-semibold mb-2">Delivery</div>
                    <ul class="small mb-0 ps-3 text-muted">
                        <li>Shipment tracked to site milestone</li>
                        <li>Delivery dates confirmed</li>
                        <li>Goods inspected before acceptance</li>
                    </ul>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="border rounded-3 p-3 h-100">
                    <div class="small text-uppercase text-muted fw-semibold mb-2">Closeout</div>
                    <ul class="small mb-0 ps-3 text-muted">
                        <li>Invoices matched to order</li>
                        <li>Supplier performance reviewed</li>
                        <li>Records archived for future bids</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($canInitiatePurchaseOrder)): ?>
<div class="card shadow-sm border-0 mb-4"><div class="card-body">
    <h5 class="fw-bold mb-3">Initiate Purchase Order</h5>
    <form method="post" action="/ERP/public/management/procurement/create">
        <div class="row g-3">
            <div class="col-md-4"><label class="form-label">PO Number</label><input class="form-control" name="po_number" placeholder="Optional"></div>
            <div class="col-md-4"><label class="form-label">Supplier</label><select class="form-select" name="supplier_id" required><option value="">Select supplier</option><?php foreach (($suppliers ?? []) as $supplier): ?><option value="<?php echo (int)$supplier['id']; ?>"><?php echo htmlspecialchars($supplier['supplier_code'] . ' - ' . $supplier['company_name']); ?></option><?php endforeach; ?></select></div>
            <div class="col-md-4"><label class="form-label">Project</label><select class="form-select" name="project_id"><option value="">Not linked to a project</option><?php foreach (($projects ?? []) as $project): ?><option value="<?php echo (int)$project['id']; ?>"><?php echo htmlspecialchars($project['project_number'] . ' - ' . $project['name']); ?></option><?php endforeach; ?></select></div>
            <div class="col-md-4"><label class="form-label">Order Date</label><input class="form-control" type="date" name="order_date" value="<?php echo date('Y-m-d'); ?>" required></div>
        </div>
        <div class="table-responsive mt-3"><table class="table align-middle mb-0"><thead><tr><th>Item</th><th>Quantity</th><th>Price Rate</th></tr></thead><tbody><?php for ($row = 0; $row < 5; $row++): ?><tr><td><input class="form-control" name="product_name[]" placeholder="Item name"></td><td><input class="form-control" type="number" min="0" name="quantity[]" value="0"></td><td><input class="form-control" type="number" min="0" step="0.01" name="price_rate[]" value="0"></td></tr><?php endfor; ?></tbody></table></div>
        <button class="btn btn-primary mt-3" type="submit"><i class="bi bi-send me-1"></i><?php echo !empty($purchaseOrderDirectToProcurement) ? 'Send to Procurement' : 'Send for Head Store Approval'; ?></button>
    </form>
</div></div>
<?php endif; ?>

<?php if (!empty($canApprovePurchaseOrder)): ?>
<div class="card shadow-sm border-0 mb-4"><div class="card-body"><h5 class="fw-bold mb-3">Purchase Orders Awaiting Review</h5>
<?php if ($pendingOrders === [] && $flaggedOrders === []): ?><p class="text-muted mb-0">No purchase orders require a decision.</p><?php else: ?>
<?php foreach (array_merge($pendingOrders, $flaggedOrders) as $order): ?><div class="border-bottom py-3"><div class="d-flex justify-content-between gap-3"><div><strong><?php echo htmlspecialchars($order['po_number']); ?></strong><div><?php echo htmlspecialchars($order['supplier'] ?? 'Supplier not found'); ?> · <?php echo number_format((float)$order['total_amount'], 2); ?></div><div class="small text-muted">Requested by <?php echo htmlspecialchars($order['requester_name'] ?? 'Unknown'); ?> on <?php echo htmlspecialchars($order['order_date']); ?><?php if (($order['workflow_status'] ?? '') === 'flagged'): ?> · <span class="text-danger">Flagged: <?php echo htmlspecialchars($order['workflow_reason'] ?? ''); ?></span><?php endif; ?></div></div><div class="d-flex flex-wrap gap-2 align-items-start"><form method="post" action="/ERP/public/management/procurement/decision"><input type="hidden" name="purchase_order_id" value="<?php echo (int)$order['id']; ?>"><input type="hidden" name="decision" value="approved"><button class="btn btn-success btn-sm" type="submit"><i class="bi bi-check-circle me-1"></i>Approve</button></form><form method="post" action="/ERP/public/management/procurement/decision"><input type="hidden" name="purchase_order_id" value="<?php echo (int)$order['id']; ?>"><input type="hidden" name="decision" value="denied"><input class="form-control form-control-sm mb-1" name="reason" placeholder="Reason for denial" required><button class="btn btn-outline-danger btn-sm" type="submit"><i class="bi bi-x-circle me-1"></i>Deny</button></form><form method="post" action="/ERP/public/management/procurement/decision"><input type="hidden" name="purchase_order_id" value="<?php echo (int)$order['id']; ?>"><input type="hidden" name="decision" value="flagged"><input class="form-control form-control-sm mb-1" name="reason" placeholder="Reason for flag" required><button class="btn btn-outline-warning btn-sm" type="submit"><i class="bi bi-flag me-1"></i>Flag</button></form></div></div></div><?php endforeach; ?><?php endif; ?></div></div>
<?php endif; ?>

<?php if (!empty($canReceivePurchaseOrder)): ?><div class="card shadow-sm border-0"><div class="card-body"><h5 class="fw-bold mb-3">Orders Sent to Logistics</h5><?php if ($logisticsOrders === []): ?><p class="text-muted mb-0">No approved purchase orders are awaiting Logistics.</p><?php else: ?><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>PO Number</th><th>Supplier</th><th>Project</th><th>Amount</th><th>Order Date</th><th>Action</th></tr></thead><tbody><?php foreach ($logisticsOrders as $order): ?><tr><td><?php echo htmlspecialchars($order['po_number']); ?></td><td><?php echo htmlspecialchars($order['supplier'] ?? ''); ?></td><td><?php echo htmlspecialchars($order['project_id'] ?? 'Not linked'); ?></td><td><?php echo number_format((float)$order['total_amount'], 2); ?></td><td><?php echo htmlspecialchars($order['order_date']); ?></td><td><a class="btn btn-sm btn-success" href="/ERP/public/management/procurement/receive?id=<?php echo (int)$order['id']; ?>"><i class="bi bi-box-arrow-in-down me-1"></i>Receive</a></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></div></div><?php endif; ?>
