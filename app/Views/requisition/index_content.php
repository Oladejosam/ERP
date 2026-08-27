<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Requisition</h4>
        <p class="text-muted mb-0">Submit and track company purchase or service requests.</p>
    </div>
</div>

<?php if (!empty($_SESSION['requisition_flash'])): ?>
    <div class="alert alert-info"><?php echo htmlspecialchars($_SESSION['requisition_flash']); unset($_SESSION['requisition_flash']); ?></div>
<?php endif; ?>

<div class="row g-4">
    <?php if (!empty($dispatchRequests)): ?>
        <div class="col-12">
            <div class="card border-warning shadow-sm">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">Material Dispatch Notifications</h5>
                    <?php foreach ($dispatchRequests as $dispatch): ?>
                        <div class="border-bottom py-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                            <div><strong><?php echo htmlspecialchars($dispatch['description'] ?? 'Inventory item'); ?></strong><div class="small text-muted"><?php echo htmlspecialchars($dispatch['title']); ?> · Requested by <?php echo htmlspecialchars($dispatch['requester_name']); ?> · Quantity: <?php echo number_format((float)$dispatch['quantity'], 2); ?> <?php echo htmlspecialchars($dispatch['unit'] ?? ''); ?></div></div>
                            <form method="post" action="/ERP/public/requisition/dispatch/approve"><input type="hidden" name="dispatch_id" value="<?php echo (int)$dispatch['id']; ?>"><button class="btn btn-success" type="submit" onclick="return confirm('Approve dispatch and deduct this quantity from inventory?');"><i class="bi bi-check-circle me-1"></i>Approve Dispatch</button></form>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
    <div class="col-lg-5">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h5 class="fw-bold mb-3">New requisition</h5>
                <form method="post" action="/ERP/public/requisition/save">
                    <div class="mb-3">
                        <label class="form-label" for="requisitionDate">Date</label>
                        <input id="requisitionDate" type="date" class="form-control" name="date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="mb-3">
                        <?php if (!empty($isSiteQuantitySurveyor)): ?>
                            <label class="form-label" for="projectSite">Project Site</label>
                            <select id="projectSite" class="form-select" name="project_id" required><option value="">Select project site</option><?php foreach (($projects ?? []) as $project): ?><option value="<?php echo (int)$project['id']; ?>"><?php echo htmlspecialchars($project['project_number'] . ' - ' . $project['name']); ?></option><?php endforeach; ?></select>
                            <div class="form-text">This requisition will be filed against the selected project.</div>
                        <?php else: ?>
                            <label class="form-label" for="projectTitle">Project Title</label>
                            <input id="projectTitle" class="form-control" name="project_title" maxlength="150" required>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="trade">Trade</label>
                        <input id="trade" class="form-control" name="trade" maxlength="100">
                    </div>
                    <div class="mb-3"><label class="form-label" for="supplier">Supplier</label><input id="supplier" class="form-control" name="supplier" maxlength="150"></div>
                    <div class="mb-3"><label class="form-label" for="supplierAddress">Supplier Address</label><textarea id="supplierAddress" class="form-control" name="supplier_address" rows="2"></textarea></div>
                    <div class="mb-3"><label class="form-label">Tag colleagues for approval</label><div class="border rounded p-2" style="max-height: 150px; overflow-y: auto;"><?php foreach (($companyUsers ?? []) as $companyUser): ?><label class="form-check"><input class="form-check-input" type="checkbox" name="participant_ids[]" value="<?php echo (int)$companyUser['id']; ?>"><span class="form-check-label"><?php echo htmlspecialchars($companyUser['name']); ?> <small class="text-muted">(<?php echo htmlspecialchars($companyUser['email']); ?>)</small></span></label><?php endforeach; ?></div><div class="form-text">Tagged colleagues will join the temporary requisition discussion.</div></div>
                    <div class="d-flex justify-content-between align-items-center mb-2"><h6 class="fw-bold mb-0">What is being requested</h6><button type="button" class="btn btn-sm btn-outline-primary" id="addRequisitionItem">Add item</button></div>
                    <div id="requisitionItems">
                        <div class="requisition-item border rounded p-3 mb-3">
                            <div class="d-flex justify-content-between mb-2"><span class="fw-semibold">Item 1</span><button type="button" class="btn btn-sm btn-outline-danger remove-requisition-item">Remove</button></div>
                            <div class="mb-2"><label class="form-label">Item Name</label><input class="form-control requisition-item-name" name="items[0][item_name]" list="inventoryItemSuggestions" autocomplete="off" placeholder="Start typing an inventory item"></div>
                            <div class="mb-2"><label class="form-label">Description of Item</label><textarea class="form-control requisition-item-description" name="items[0][description]" rows="2"></textarea><div class="form-text inventory-suggestion-status" aria-live="polite"></div></div>
                            <div class="row g-2"><div class="col-6"><label class="form-label">Code</label><input class="form-control" name="items[0][code]"></div><div class="col-6"><label class="form-label">Unit</label><input class="form-control" name="items[0][unit]"></div><div class="col-6"><label class="form-label">Quantity Required</label><input type="number" class="form-control" name="items[0][quantity_required]" min="0" step="0.01"></div><div class="col-6"><label class="form-label">Quantity in Stock</label><input type="number" class="form-control" name="items[0][quantity_in_stock]" min="0" step="0.01"></div><div class="col-6"><label class="form-label">Quantity to Purchase</label><input type="number" class="form-control" name="items[0][quantity_to_purchase]" min="0" step="0.01" required></div><div class="col-6"><label class="form-label">Price</label><input type="number" class="form-control" name="items[0][price]" min="0" step="0.01" required></div></div>
                        </div>
                    </div>
                    <datalist id="inventoryItemSuggestions"></datalist>
                    <button class="btn btn-primary w-100" type="submit">Submit Requisition</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h5 class="fw-bold mb-3">Recent requisitions</h5>
                <?php if (empty($requisitions)): ?>
                    <p class="text-muted mb-0">No requisitions have been submitted yet.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <thead><tr><th>Date</th><th>Project Title</th><th>Trade</th><th>Supplier</th><th>Supplier Address</th><th>Requested Items</th><th>Total Value</th><th>Status</th></tr></thead>
                            <tbody>
                                <?php foreach ($requisitions as $requisition): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($requisition['requisition_date'] ?? ''); ?></td>
                                        <td><a href="/ERP/public/requisition/view?id=<?php echo (int)$requisition['id']; ?>"><?php echo htmlspecialchars($requisition['title']); ?></a></td>
                                        <td><?php echo htmlspecialchars($requisition['trade'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($requisition['supplier'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($requisition['supplier_address'] ?? ''); ?></td>
                                        <td><?php foreach (($requisition['items'] ?? []) as $item): ?><div class="border-bottom py-1"><strong><?php echo htmlspecialchars($item['description'] ?? 'Item'); ?></strong> <span class="small text-muted"><?php echo htmlspecialchars($item['unit'] ?? ''); ?> × <?php echo number_format((float)$item['quantity_to_purchase'], 2); ?> at ₦<?php echo number_format((float)$item['price'], 2); ?></span></div><?php endforeach; ?></td>
                                        <td>₦<?php echo number_format((float)$requisition['value'], 2); ?></td>
                                        <td><span class="badge <?php echo $requisition['status'] === 'approved' ? 'text-bg-success' : ($requisition['status'] === 'rejected' ? 'text-bg-danger' : 'text-bg-warning'); ?>"><?php echo htmlspecialchars(ucfirst($requisition['status'])); ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const items = document.getElementById('requisitionItems');
    const addButton = document.getElementById('addRequisitionItem');
    const suggestions = document.getElementById('inventoryItemSuggestions');
    let itemIndex = 1;

    function updateSuggestions(item, matches) {
        suggestions.innerHTML = '';
        matches.forEach(function (match) {
            const option = document.createElement('option');
            option.value = match.name;
            option.label = match.item_code + ' - ' + match.unit + ' - stock: ' + match.current_stock;
            suggestions.appendChild(option);
        });
        item.querySelector('.inventory-suggestion-status').textContent = matches.length ? 'Select an inventory item to fill its details.' : 'No matching inventory item found.';
    }

    function populateItem(item, inventoryItem) {
        item.querySelector('.requisition-item-description').value = inventoryItem.name;
        item.querySelector('[name$="[code]"]').value = inventoryItem.item_code || '';
        item.querySelector('[name$="[unit]"]').value = inventoryItem.unit || '';
        item.querySelector('[name$="[quantity_in_stock]"]').value = inventoryItem.current_stock || 0;
        item.querySelector('[name$="[price]"]').value = inventoryItem.cost_price || inventoryItem.selling_price || 0;
        item.querySelector('.inventory-suggestion-status').textContent = 'Inventory details loaded.';
    }

    function fillFromInventory(item) {
        const nameField = item.querySelector('.requisition-item-name');
        if (nameField.value.trim() === '') {
            return;
        }

        fetch('/ERP/public/requisition/inventory-search?q=' + encodeURIComponent(nameField.value.trim()), { headers: { Accept: 'application/json' } })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                const inventoryItem = (data.items || []).find(function (candidate) {
                    return candidate.name.toLowerCase() === nameField.value.trim().toLowerCase();
                });
                if (inventoryItem) {
                    populateItem(item, inventoryItem);
                }
            })
            .catch(function () { item.querySelector('.inventory-suggestion-status').textContent = 'Inventory details are unavailable.'; });
    }

    items.addEventListener('input', function (event) {
        if (!event.target.classList.contains('requisition-item-name')) {
            return;
        }
        const item = event.target.closest('.requisition-item');
        const query = event.target.value.trim();
        if (query.length < 2) {
            updateSuggestions(item, []);
            return;
        }
        fetch('/ERP/public/requisition/inventory-search?q=' + encodeURIComponent(query), { headers: { Accept: 'application/json' } })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                const matches = data.items || [];
                updateSuggestions(item, matches);
                const exactMatch = matches.find(function (candidate) {
                    return candidate.name.toLowerCase() === query.toLowerCase();
                });
                if (exactMatch) {
                    populateItem(item, exactMatch);
                }
            })
            .catch(function () { item.querySelector('.inventory-suggestion-status').textContent = 'Inventory suggestions are unavailable.'; });
    });
    items.addEventListener('change', function (event) {
        if (event.target.classList.contains('requisition-item-name')) {
            fillFromInventory(event.target.closest('.requisition-item'));
        }
    });
    addButton.addEventListener('click', function () {
        const item = items.firstElementChild.cloneNode(true);
        item.querySelectorAll('input, textarea').forEach(function (field) {
            field.value = '';
            field.name = field.name.replace(/items\[0\]/, 'items[' + itemIndex + ']');
        });
        item.querySelector('.fw-semibold').textContent = 'Item ' + (itemIndex + 1);
        items.appendChild(item);
        itemIndex++;
    });
    items.addEventListener('click', function (event) {
        if (event.target.classList.contains('remove-requisition-item') && items.children.length > 1) {
            event.target.closest('.requisition-item').remove();
        }
    });
});
</script>
