<?php
$itemId = (int)($item['id'] ?? 0);
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Inventory Item</h4>
        <p class="text-muted mb-0">Update item and supplier information.</p>
    </div>
    <a class="btn btn-outline-secondary" href="/ERP/public/modules/inventory">Back to Inventory</a>
</div>

<?php if (!empty($_SESSION['inventory_flash'])): ?>
    <div class="alert alert-info"><?php echo htmlspecialchars($_SESSION['inventory_flash']); unset($_SESSION['inventory_flash']); ?></div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <form method="post" action="/ERP/public/inventory/save">
                    <input type="hidden" name="item_id" value="<?php echo $itemId; ?>">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">Item Code</label><input class="form-control" value="<?php echo htmlspecialchars($item['item_code'] ?? ''); ?>" readonly></div>
                        <div class="col-md-6"><label class="form-label">Item Name</label><input class="form-control" name="name" value="<?php echo htmlspecialchars($item['name'] ?? ''); ?>" required></div>
                        <div class="col-md-6"><label class="form-label">Unit</label><input class="form-control" name="unit" value="<?php echo htmlspecialchars($item['unit'] ?? ''); ?>" required></div>
                        <div class="col-md-6"><label class="form-label">Supplier Name</label><input class="form-control" name="supplier_name" value="<?php echo htmlspecialchars($item['supplier_name'] ?? ''); ?>"></div>
                        <div class="col-md-6"><label class="form-label">Supplier Contact Person</label><input class="form-control" name="supplier_contact" value="<?php echo htmlspecialchars($item['supplier_contact'] ?? ''); ?>"></div>
                        <div class="col-md-6"><label class="form-label">Supplier Phone</label><input class="form-control" name="supplier_phone" type="tel" value="<?php echo htmlspecialchars($item['supplier_phone'] ?? ''); ?>"></div>
                        <div class="col-12"><label class="form-label">Supplier Address</label><input class="form-control" name="supplier_address" value="<?php echo htmlspecialchars($item['supplier_address'] ?? ''); ?>"></div>
                        <div class="col-md-6"><label class="form-label">Cost Price</label><input class="form-control" type="number" min="0" step="0.01" name="cost_price" value="<?php echo htmlspecialchars((string)($item['cost_price'] ?? 0)); ?>"></div>
                        <div class="col-md-6"><label class="form-label">Selling Price</label><input class="form-control" type="number" min="0" step="0.01" name="selling_price" value="<?php echo htmlspecialchars((string)($item['selling_price'] ?? 0)); ?>"></div>
                        <div class="col-md-6"><label class="form-label">Current Stock</label><input class="form-control" type="number" min="0" name="current_stock" value="<?php echo (int)($item['current_stock'] ?? 0); ?>"></div>
                        <div class="col-md-6"><label class="form-label">Reorder Level</label><input class="form-control" type="number" min="0" name="reorder_level" value="<?php echo (int)($item['reorder_level'] ?? 0); ?>"></div>
                        <div class="col-md-6"><label class="form-label">Free stock</label><input class="form-control" value="<?php echo (int)($item['free_stock'] ?? 0); ?>" readonly></div>
                        <div class="col-md-6"><label class="form-label">Allocated stock</label><input class="form-control" value="<?php echo (int)($item['allocated_stock'] ?? 0); ?><?php echo !empty($item['allocated_to']) ? ' - ' . htmlspecialchars($item['allocated_to']) : ''; ?>" readonly></div>
                        <div class="col-12"><label class="form-label">Reason for Change</label><textarea class="form-control" name="change_reason" rows="3" required></textarea></div>
                    </div>
                    <button class="btn btn-primary mt-4" type="submit">Save Changes</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h5 class="fw-bold mb-3">Change History</h5>
                <?php if (empty($changeHistory)): ?>
                    <p class="text-muted mb-0">No changes recorded yet.</p>
                <?php else: ?>
                    <?php foreach ($changeHistory as $history): ?>
                        <div class="border-bottom pb-3 mb-3">
                            <div class="small text-muted"><?php echo htmlspecialchars($history['changed_at'] ?? ''); ?></div>
                            <div class="fw-semibold"><?php echo htmlspecialchars($history['change_reason'] ?? ''); ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php if (!empty($canIssueInventory)): ?>
<div class="row g-4 mt-1">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h5 class="fw-bold mb-3">Allocate Stock</h5>
                <p class="text-muted">Move free stock into an allocation for a person, project, or site before issuing it.</p>
                <form method="post" action="/ERP/public/inventory/allocate">
                    <input type="hidden" name="item_id" value="<?php echo $itemId; ?>">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label" for="allocationQuantity">Quantity to allocate</label><input class="form-control" id="allocationQuantity" type="number" name="quantity" min="1" max="<?php echo (int)($item['free_stock'] ?? 0); ?>" required></div>
                        <div class="col-md-6"><label class="form-label" for="allocationDestination">Allocate to / location</label><input class="form-control" id="allocationDestination" name="allocated_to" maxlength="255" placeholder="Person, project, or site" required></div>
                    </div>
                    <button class="btn btn-outline-primary mt-3" type="submit"><i class="bi bi-box-seam me-1"></i>Allocate Stock</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
<div class="row g-4 mt-1">
    <?php if (!empty($canIssueInventory)): ?><div class="col-lg-8">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h5 class="fw-bold mb-3">Issue Item</h5>
                <form method="post" action="/ERP/public/inventory/issue">
                    <input type="hidden" name="item_id" value="<?php echo $itemId; ?>">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label" for="issuedTo">Issued to / location</label><input class="form-control" id="issuedTo" name="issued_to" maxlength="255" placeholder="Person, project, or site" required></div>
                        <div class="col-md-3"><label class="form-label" for="issueQuantity">Quantity issued</label><input class="form-control" id="issueQuantity" type="number" name="quantity" min="1" max="<?php echo (int)($item['current_stock'] ?? 0); ?>" required></div>
                        <div class="col-md-3"><label class="form-label" for="issuedDate">Date issued</label><input class="form-control" id="issuedDate" type="date" name="issued_date" value="<?php echo date('Y-m-d'); ?>" required></div>
                        <div class="col-md-6"><label class="form-label" for="stockSource">Issue from</label><select class="form-select" id="stockSource" name="stock_source" required><option value="free">Free stock (<?php echo (int)($item['free_stock'] ?? 0); ?> available)</option><option value="allocated">Allocated stock (<?php echo (int)($item['allocated_stock'] ?? 0); ?> available)</option></select></div>
                    </div>
                    <button class="btn btn-warning mt-3" type="submit"><i class="bi bi-box-arrow-up me-1"></i>Record Issue</button>
                </form>
            </div>
        </div>
    </div><?php endif; ?>
    <div class="col-lg-4">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h5 class="fw-bold mb-3">Issue History</h5>
                <?php if (empty($issueHistory)): ?>
                    <p class="text-muted mb-0">No issues recorded yet.</p>
                <?php else: ?>
                    <?php foreach ($issueHistory as $issue): ?>
                        <div class="border-bottom pb-2 mb-2"><div class="fw-semibold"><?php echo htmlspecialchars($issue['issued_to']); ?></div><div class="small text-muted"><?php echo (int)$issue['quantity']; ?> from <?php echo htmlspecialchars($issue['stock_source']); ?> stock on <?php echo htmlspecialchars($issue['issued_date']); ?><?php if (!empty($issue['issuer_name'])): ?> · Issued by <?php echo htmlspecialchars($issue['issuer_name']); ?> (ID: <?php echo (int)$issue['issuer_id']; ?>)<?php endif; ?></div></div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php if (!empty($canRaisePurchaseOrder)): ?>
<div class="row g-4 mt-1">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h5 class="fw-bold mb-1">Raise Purchase Order</h5>
                <p class="text-muted mb-3">Send this item to Procurement for Head Store approval.</p>
                <form method="post" action="/ERP/public/management/procurement/create">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label" for="inventoryPoNumber">PO Number</label><input class="form-control" id="inventoryPoNumber" name="po_number" placeholder="Optional"></div>
                        <div class="col-md-6"><label class="form-label" for="inventoryPoSupplier">Supplier</label><select class="form-select" id="inventoryPoSupplier" name="supplier_id" required><option value="">Select supplier</option><?php foreach (($procurementSuppliers ?? []) as $supplier): ?><option value="<?php echo (int)$supplier['id']; ?>" <?php echo strcasecmp(trim((string)($item['supplier_name'] ?? '')), trim((string)($supplier['company_name'] ?? ''))) === 0 ? 'selected' : ''; ?>><?php echo htmlspecialchars($supplier['supplier_code'] . ' - ' . $supplier['company_name']); ?></option><?php endforeach; ?><option value="new">Write in a new supplier</option></select></div>
                        <div class="col-md-4"><label class="form-label" for="inventoryPoDate">Order Date</label><input class="form-control" id="inventoryPoDate" type="date" name="order_date" value="<?php echo date('Y-m-d'); ?>" required></div>
                        <div class="col-md-4"><label class="form-label" for="inventoryPoQuantity">Quantity</label><input class="form-control" id="inventoryPoQuantity" type="number" name="quantity[]" min="1" value="<?php echo max(1, (int)($item['reorder_level'] ?? 0) - (int)($item['current_stock'] ?? 0)); ?>" required></div>
                        <div class="col-md-4"><label class="form-label" for="inventoryPoRate">Price Rate</label><input class="form-control" id="inventoryPoRate" type="number" name="price_rate[]" min="0.01" step="0.01" value="<?php echo htmlspecialchars((string)($item['cost_price'] ?? 0)); ?>" required></div>
                        <div class="col-12"><label class="form-label" for="inventoryPoItem">Item</label><input class="form-control" id="inventoryPoItem" name="product_name[]" value="<?php echo htmlspecialchars(($item['item_code'] ?? '') . ' - ' . ($item['name'] ?? '')); ?>" readonly></div>
                        <div class="col-12 d-none" id="newInventorySupplierFields"><div class="border rounded p-3"><h6 class="fw-bold mb-3">New Supplier Details</h6><div class="row g-3"><div class="col-md-6"><label class="form-label" for="newSupplierName">Supplier Name</label><input class="form-control" id="newSupplierName" name="supplier_company_name" maxlength="150" value="<?php echo htmlspecialchars((string)($item['supplier_name'] ?? '')); ?>"></div><div class="col-md-6"><label class="form-label" for="newSupplierContact">Contact Person</label><input class="form-control" id="newSupplierContact" name="supplier_contact_person" maxlength="150" value="<?php echo htmlspecialchars((string)($item['supplier_contact'] ?? '')); ?>"></div><div class="col-md-4"><label class="form-label" for="newSupplierEmail">Email</label><input class="form-control" id="newSupplierEmail" type="email" name="supplier_email" maxlength="150"></div><div class="col-md-4"><label class="form-label" for="newSupplierPhone">Phone</label><input class="form-control" id="newSupplierPhone" type="tel" name="supplier_phone" maxlength="30" value="<?php echo htmlspecialchars((string)($item['supplier_phone'] ?? '')); ?>"></div><div class="col-md-4"><label class="form-label" for="newSupplierAddress">Address</label><input class="form-control" id="newSupplierAddress" name="supplier_address" maxlength="255" value="<?php echo htmlspecialchars((string)($item['supplier_address'] ?? '')); ?>"></div></div></div></div>
                    </div>
                    <button class="btn btn-primary mt-3" type="submit"><i class="bi bi-send me-1"></i>Send to Procurement</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
<script>
(() => {
    const supplier = document.getElementById('inventoryPoSupplier');
    const supplierFields = document.getElementById('newInventorySupplierFields');
    const supplierName = document.getElementById('newSupplierName');
    if (!supplier || !supplierFields || !supplierName) return;
    const syncSupplierFields = () => {
        const isNewSupplier = supplier.value === 'new';
        supplierFields.classList.toggle('d-none', !isNewSupplier);
        supplierName.required = isNewSupplier;
    };
    supplier.addEventListener('change', syncSupplierFields);
    syncSupplierFields();
})();
</script>
