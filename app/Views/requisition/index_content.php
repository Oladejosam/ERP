<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Requisition</h4>
        <p class="text-muted mb-0">Create one requisition with multiple requested items.</p>
    </div>
</div>

<?php if (!empty($_SESSION['requisition_flash'])): ?>
    <div class="alert alert-info"><?php echo htmlspecialchars($_SESSION['requisition_flash']); unset($_SESSION['requisition_flash']); ?></div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h5 class="fw-bold mb-3">New requisition</h5>
                <form method="post" action="/ERP/public/requisition/save">
                    <div class="mb-3">
                        <label class="form-label" for="requisitionDate">Date</label>
                        <input id="requisitionDate" type="date" class="form-control" name="date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="projectTitle">Project Title</label>
                        <input id="projectTitle" class="form-control" name="project_title" maxlength="150" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="trade">Trade</label>
                        <input id="trade" class="form-control" name="trade" maxlength="100">
                    </div>
                    <div class="mb-3"><label class="form-label" for="supplier">Supplier</label><input id="supplier" class="form-control" name="supplier" maxlength="150"></div>
                    <div class="mb-3"><label class="form-label" for="supplierAddress">Supplier Address</label><textarea id="supplierAddress" class="form-control" name="supplier_address" rows="2"></textarea></div>
                    <h6 class="fw-bold border-top pt-3">Items to request</h6>
                    <div id="requisitionItems">
                        <div class="requisition-item border rounded p-3 mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2"><span class="fw-semibold item-number">Item 1</span><button type="button" class="btn btn-sm btn-outline-danger remove-item">Remove</button></div>
                            <div class="mb-2"><label class="form-label">Description of Item</label><textarea class="form-control" name="items[0][description]" rows="2"></textarea></div>
                            <div class="row g-2">
                                <div class="col-6"><label class="form-label">Code</label><input class="form-control" name="items[0][code]" maxlength="80"></div>
                                <div class="col-6"><label class="form-label">Unit</label><input class="form-control" name="items[0][unit]" maxlength="50"></div>
                                <div class="col-6"><label class="form-label">Quantity Required</label><input type="number" class="form-control" name="items[0][quantity_required]" min="0" step="0.01"></div>
                                <div class="col-6"><label class="form-label">Quantity in Stock</label><input type="number" class="form-control" name="items[0][quantity_in_stock]" min="0" step="0.01"></div>
                                <div class="col-6"><label class="form-label">Quantity to Purchase</label><input type="number" class="form-control" name="items[0][quantity_to_purchase]" min="0" step="0.01"></div>
                                <div class="col-6"><label class="form-label">Price</label><input type="number" class="form-control" name="items[0][price]" min="0" step="0.01"></div>
                            </div>
                        </div>
                    </div>
                    <button type="button" id="addRequisitionItem" class="btn btn-outline-secondary w-100 mb-3">Add Another Item</button>
                    <button class="btn btn-primary w-100" type="submit">Submit Requisition</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
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
                                        <td><?php echo htmlspecialchars($requisition['title']); ?></td>
                                        <td><?php echo htmlspecialchars($requisition['trade'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($requisition['supplier'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($requisition['supplier_address'] ?? ''); ?></td>
                                        <td><?php foreach (($requisition['items'] ?? []) as $item): ?><div class="small mb-1"><?php echo htmlspecialchars($item['description'] ?? ''); ?> · <?php echo number_format((float)$item['quantity_to_purchase'], 2); ?> <?php echo htmlspecialchars($item['unit'] ?? ''); ?> · ₦<?php echo number_format((float)$item['value'], 2); ?></div><?php endforeach; ?></td>
                                        <td>₦<?php echo number_format((float)$requisition['amount'], 2); ?></td>
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
    const container = document.getElementById('requisitionItems');
    const addButton = document.getElementById('addRequisitionItem');
    let nextIndex = 1;
    addButton.addEventListener('click', function () {
        const item = container.querySelector('.requisition-item').cloneNode(true);
        item.querySelector('.item-number').textContent = 'Item ' + (nextIndex + 1);
        item.querySelectorAll('input, textarea').forEach(function (input) { input.value = ''; input.name = input.name.replace(/items\[0\]/, 'items[' + nextIndex + ']'); });
        item.querySelector('.remove-item').addEventListener('click', function () { item.remove(); });
        container.appendChild(item);
        nextIndex++;
    });
    container.querySelector('.remove-item').addEventListener('click', function (event) { if (container.querySelectorAll('.requisition-item').length > 1) event.currentTarget.closest('.requisition-item').remove(); });
});
</script>
