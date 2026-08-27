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
