<div class="card shadow-sm border-0">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="fw-bold">Inventory</h4>
                <p class="text-muted mb-0">Stock levels, reorder alerts, and warehouse control.</p>
            </div>
            <div class="d-flex gap-2">
                <a class="btn btn-outline-secondary" href="/ERP/public/management/procurement">Create PO</a>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addItemModal">Add Item</button>
            </div>
        </div>

        <?php if (!empty($_SESSION['inventory_flash'])): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['inventory_flash']); ?></div>
            <?php unset($_SESSION['inventory_flash']); ?>
        <?php endif; ?>

        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th>Item Code</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Stock</th>
                    <th>Reorder</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">No inventory items yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['item_code'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($item['name'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($item['category_name'] ?? ''); ?></td>
                            <td><?php echo (int)($item['current_stock'] ?? 0); ?></td>
                            <td><?php echo (int)($item['reorder_level'] ?? 0); ?></td>
                            <td><a class="btn btn-sm btn-outline-primary" href="/ERP/public/inventory/detail?id=<?php echo (int)$item['id']; ?>">Edit</a></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="addItemModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Inventory Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="post" action="/ERP/public/inventory/save">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Item Code</label>
                            <input class="form-control" name="item_code" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Item Name</label>
                            <input class="form-control" name="name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Category</label>
                            <input class="form-control" name="category" placeholder="e.g. Steel">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Unit</label>
                            <input class="form-control" name="unit" value="pcs">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Cost Price</label>
                            <input type="number" step="0.01" class="form-control" name="cost_price" value="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Selling Price</label>
                            <input type="number" step="0.01" class="form-control" name="selling_price" value="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Opening Stock</label>
                            <input type="number" class="form-control" name="opening_stock" value="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Current Stock</label>
                            <input type="number" class="form-control" name="current_stock" value="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Reorder Level</label>
                            <input type="number" class="form-control" name="reorder_level" value="0">
                        </div>
                    </div>
                    <div class="mt-4 d-flex justify-content-end">
                        <button class="btn btn-primary">Save Item</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
