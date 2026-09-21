<?php
$pendingOrders = $pendingOrders ?? [];
$flaggedOrders = $flaggedOrders ?? [];
$logisticsOrders = $logisticsOrders ?? [];
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h4 class="fw-bold mb-1">Procurement</h4><p class="text-muted mb-0">Purchase-order requests and approvals.</p></div>
    <a class="btn btn-outline-secondary" href="/ERP/public/modules/inventory"><i class="bi bi-box-seam me-1"></i>Inventory</a>
</div>
<?php if (!empty($_SESSION['procurement_flash'])): ?><div class="alert alert-info"><?php echo htmlspecialchars($_SESSION['procurement_flash']); unset($_SESSION['procurement_flash']); ?></div><?php endif; ?>

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
<?php foreach (array_merge($pendingOrders, $flaggedOrders) as $order): ?><div class="border-bottom py-3"><div class="d-flex justify-content-between gap-3"><div><strong><?php echo htmlspecialchars($order['po_number']); ?></strong><div><?php echo htmlspecialchars($order['supplier'] ?? 'Supplier not found'); ?> · <?php echo number_format((float)$order['total_amount'], 2); ?></div><div class="small text-muted">Requested by <?php echo htmlspecialchars($order['requester_name'] ?? 'Unknown'); ?> on <?php echo htmlspecialchars($order['order_date']); ?><?php if (($order['workflow_status'] ?? '') === 'flagged'): ?> · <span class="text-danger">Flagged: <?php echo htmlspecialchars($order['workflow_reason'] ?? ''); ?></span><?php endif; ?></div></div><div class="d-flex flex-wrap gap-2 align-items-start"><form method="post" action="/ERP/public/management/procurement/decision"><input type="hidden" name="purchase_order_id" value="<?php echo (int)$order['id']; ?>"><input type="hidden" name="decision" value="approved"><button class="btn btn-success btn-sm" type="submit"><i class="bi bi-check-circle me-1"></i>Approve</button></form><form method="post" action="/ERP/public/management/procurement/decision"><input type="hidden" name="purchase_order_id" value="<?php echo (int)$order['id']; ?>"><input type="hidden" name="decision" value="denied"><input class="form-control form-control-sm mb-1" name="reason" placeholder="Reason for denial" required><button class="btn btn-outline-danger btn-sm" type="submit"><i class="bi bi-x-circle me-1"></i>Deny</button></form><form method="post" action="/ERP/public/management/procurement/decision"><input type="hidden" name="purchase_order_id" value="<?php echo (int)$order['id']; ?>"><input type="hidden" name="decision" value="flagged"><input class="form-control form-control-sm mb-1" name="reason" placeholder="Reason for flag" required><button class="btn btn-outline-warning btn-sm" type="submit"><i class="bi bi-flag me-1"></i>Flag</button></form></div></div></div><?php endforeach; ?>
<?php endif; ?></div></div>
<?php endif; ?>

<?php if (!empty($canReceivePurchaseOrder)): ?><div class="card shadow-sm border-0"><div class="card-body"><h5 class="fw-bold mb-3">Orders Sent to Logistics</h5><?php if ($logisticsOrders === []): ?><p class="text-muted mb-0">No approved purchase orders are awaiting Logistics.</p><?php else: ?><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>PO Number</th><th>Supplier</th><th>Project</th><th>Amount</th><th>Order Date</th><th>Action</th></tr></thead><tbody><?php foreach ($logisticsOrders as $order): ?><tr><td><?php echo htmlspecialchars($order['po_number']); ?></td><td><?php echo htmlspecialchars($order['supplier'] ?? ''); ?></td><td><?php echo htmlspecialchars($order['project_id'] ?? 'Not linked'); ?></td><td><?php echo number_format((float)$order['total_amount'], 2); ?></td><td><?php echo htmlspecialchars($order['order_date']); ?></td><td><a class="btn btn-sm btn-success" href="/ERP/public/management/procurement/receive?id=<?php echo (int)$order['id']; ?>"><i class="bi bi-box-arrow-in-down me-1"></i>Receive</a></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></div></div><?php endif; ?>
