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

        <form class="row g-2 align-items-end mb-3" method="get" action="/ERP/public/modules/inventory">
            <div class="col-md-3 col-lg-3">
            <label class="form-label" for="inventorySearchField">Filter by</label>
                <select id="inventorySearchField" class="form-select" name="search_field">
                    <?php foreach (($availableSearchFields ?? []) as $fieldValue => $fieldLabel): ?>
                        <option value="<?php echo htmlspecialchars($fieldValue); ?>" <?php echo ($searchField ?? 'all') === $fieldValue ? 'selected' : ''; ?>><?php echo htmlspecialchars($fieldLabel); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-5 col-lg-4">
                <label class="form-label" for="inventorySearch">Search inventory</label>
                <input id="inventorySearch" class="form-control" type="search" name="search" value="<?php echo htmlspecialchars($search ?? ''); ?>" placeholder="Search by item code, name, category, or supplier">
            </div>
            <div class="col-auto"><button class="btn btn-outline-primary" type="submit"><i class="bi bi-search me-1"></i>Search</button></div>
            <?php if (!empty($search)): ?><div class="col-auto"><a class="btn btn-outline-secondary" href="/ERP/public/modules/inventory">Clear</a></div><?php endif; ?>
        </form>

        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th>Item Code</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Stock</th>
                    <th>Free</th>
                    <th>Allocated</th>
                    <th>Reorder</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4"><?php echo !empty($search) ? 'No inventory items match your search.' : 'No inventory items yet.'; ?></td></tr>
                <?php else: ?>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['item_code'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($item['name'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($item['category_name'] ?? ''); ?></td>
                            <td><?php echo (int)($item['current_stock'] ?? 0); ?></td>
                            <td><?php echo (int)($item['free_stock'] ?? $item['current_stock'] ?? 0); ?></td>
                            <td><?php echo (int)($item['allocated_stock'] ?? 0); ?></td>
                            <td><?php echo (int)($item['reorder_level'] ?? 0); ?></td>
                            <td><a class="btn btn-sm btn-outline-primary" href="/ERP/public/inventory/detail?id=<?php echo (int)$item['id']; ?>">View</a></td>
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
                            <input class="form-control" name="item_code" id="inventoryItemCode" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Item Name</label>
                            <input class="form-control" name="name" id="inventoryItemName" list="inventoryItemNames" autocomplete="off" required>
                            <datalist id="inventoryItemNames"></datalist>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Category</label>
                            <input class="form-control" name="category" id="inventoryItemCategory" placeholder="e.g. Steel">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Unit</label>
                            <input class="form-control" name="unit" id="inventoryItemUnit" value="pcs">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Supplier Name</label>
                            <input class="form-control" name="supplier_name">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Supplier Contact Person</label>
                            <input class="form-control" name="supplier_contact">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Supplier Phone</label>
                            <input class="form-control" name="supplier_phone" type="tel">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Supplier Address</label>
                            <input class="form-control" name="supplier_address">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Price</label>
                            <input type="number" step="0.01" min="0" class="form-control" name="price" id="inventoryItemPrice" value="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Stock</label>
                            <input type="number" min="0" class="form-control" name="stock" id="inventoryItemStock" value="0" required>
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
<script>
document.addEventListener('DOMContentLoaded', function () {
    const nameInput = document.getElementById('inventoryItemName');
    const suggestions = document.getElementById('inventoryItemNames');
    const codeInput = document.getElementById('inventoryItemCode');
    const categoryInput = document.getElementById('inventoryItemCategory');
    const unitInput = document.getElementById('inventoryItemUnit');
    const priceInput = document.getElementById('inventoryItemPrice');
    if (!nameInput || !suggestions) return;
    let matches = [];
    nameInput.addEventListener('input', function () {
        const query = nameInput.value.trim();
        if (query.length < 2) return;
        fetch('/ERP/public/inventory/search?q=' + encodeURIComponent(query), {headers: {Accept: 'application/json'}})
            .then(response => response.json())
            .then(data => {
                matches = data.items || [];
                suggestions.innerHTML = matches.map(item => '<option value="' + String(item.name).replace(/"/g, '&quot;') + '">' + String(item.item_code).replace(/"/g, '&quot;') + '</option>').join('');
                const existing = matches.find(item => item.name.toLowerCase() === query.toLowerCase());
                if (!existing) return;
                codeInput.value = existing.item_code || '';
                categoryInput.value = existing.category_name || '';
                unitInput.value = existing.unit || 'pcs';
                priceInput.value = existing.cost_price || existing.selling_price || 0;
                codeInput.readOnly = true;
                categoryInput.readOnly = true;
                unitInput.readOnly = true;
            })
            .catch(() => {});
    });
});
</script>
