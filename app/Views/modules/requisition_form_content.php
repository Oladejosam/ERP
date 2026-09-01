<?php
$fields = $fields ?? [];
?>
<div class="card shadow-sm border-0">
    <div class="card-body p-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <p class="text-uppercase text-muted small fw-semibold mb-1">Super Admin Module</p>
                <h2 class="fw-bold mb-1">Requisition Form Designer</h2>
                <p class="text-muted mb-0">Define the fields a requisition should collect, and the requisition will carry those values.</p>
            </div>
            <a class="btn btn-outline-secondary" href="/ERP/public/modules/workflow"><i class="bi bi-arrow-left me-1"></i>Back to workflow</a>
        </div>

        <?php if (!empty($_SESSION['workflow_flash'])): ?>
            <div class="alert alert-info"><?php echo htmlspecialchars($_SESSION['workflow_flash']); unset($_SESSION['workflow_flash']); ?></div>
        <?php endif; ?>

        <div class="alert alert-light border mb-4">
            <i class="bi bi-info-circle me-2"></i>
            These are the current requisition fields for this company. Edit any field, remove outdated ones, or add more to capture additional information.
        </div>

        <form method="post" action="/ERP/public/modules/requisition-form/save">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0">Current company requisition fields</h5>
                <span class="badge text-bg-light border"><?php echo count($fields); ?> field(s)</span>
            </div>
            <div id="requisitionFieldList" class="d-grid gap-3 mb-4">
                <?php if ($fields === []): ?>
                    <div class="card border-dashed">
                        <div class="card-body text-muted">No fields configured yet. Add a new field to start designing the requisition form.</div>
                    </div>
                <?php else: ?>
                    <?php foreach ($fields as $index => $field): ?>
                        <div class="card border field-row" draggable="true">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="small text-uppercase text-muted fw-semibold">Field</span>
                                    <span class="field-drag-handle btn btn-light btn-sm border" title="Drag to reorder" aria-label="Drag to reorder"><i class="bi bi-arrows-move"></i></span>
                                </div>
                                <div class="row g-3 align-items-end">
                                    <div class="col-md-2">
                                        <label class="form-label">Field key</label>
                                        <input class="form-control" name="fields[<?php echo (int)$index; ?>][key]" value="<?php echo htmlspecialchars((string)($field['key'] ?? '')); ?>" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Label</label>
                                        <input class="form-control" name="fields[<?php echo (int)$index; ?>][label]" value="<?php echo htmlspecialchars((string)($field['label'] ?? '')); ?>" required>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Type</label>
                                        <select class="form-select" name="fields[<?php echo (int)$index; ?>][type]">
                                            <?php foreach (['text', 'textarea', 'number', 'date', 'select', 'checkbox'] as $type): ?>
                                                <option value="<?php echo htmlspecialchars($type); ?>" <?php echo ((string)($field['type'] ?? 'text')) === $type ? 'selected' : ''; ?>><?php echo htmlspecialchars(ucfirst($type)); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Required</label>
                                        <div class="form-check mt-2">
                                            <input class="form-check-input" type="checkbox" name="fields[<?php echo (int)$index; ?>][required]" value="1" <?php echo !empty($field['required']) ? 'checked' : ''; ?>>
                                            <label class="form-check-label">Yes</label>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Placeholder</label>
                                        <input class="form-control" name="fields[<?php echo (int)$index; ?>][placeholder]" value="<?php echo htmlspecialchars((string)($field['placeholder'] ?? '')); ?>">
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label">Help text</label>
                                        <input class="form-control" name="fields[<?php echo (int)$index; ?>][help_text]" value="<?php echo htmlspecialchars((string)($field['help_text'] ?? '')); ?>">
                                    </div>
                                    <div class="col-12 text-end">
                                        <button type="button" class="btn btn-sm btn-outline-danger remove-field">Remove</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="d-flex gap-2 mb-4">
                <button type="button" class="btn btn-outline-primary" id="addRequisitionField"><i class="bi bi-plus-lg me-1"></i>Add field</button>
            </div>

            <button class="btn btn-primary" type="submit"><i class="bi bi-save me-2"></i>Save requisition form</button>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const list = document.getElementById('requisitionFieldList');
    const addButton = document.getElementById('addRequisitionField');

    if (!list || !addButton) {
        return;
    }

    const emptyState = () => `
        <div class="card border-dashed">
            <div class="card-body text-muted">No fields configured yet. Add a new field to start designing the requisition form.</div>
        </div>
    `;

    function reindexRows() {
        list.querySelectorAll('.field-row').forEach((row, index) => {
            row.querySelectorAll('input, select').forEach((field) => {
                if (!field.name) {
                    return;
                }
                field.name = field.name.replace(/fields\[\d+\]/, `fields[${index}]`);
            });
        });
    }

    function createField(index) {
        return `
            <div class="card border field-row" draggable="true">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="small text-uppercase text-muted fw-semibold">Field</span>
                        <span class="field-drag-handle btn btn-light btn-sm border" title="Drag to reorder" aria-label="Drag to reorder"><i class="bi bi-arrows-move"></i></span>
                    </div>
                    <div class="row g-3 align-items-end">
                        <div class="col-md-2">
                            <label class="form-label">Field key</label>
                            <input class="form-control" name="fields[${index}][key]" placeholder="e.g. project_location" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Label</label>
                            <input class="form-control" name="fields[${index}][label]" placeholder="e.g. Project location" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Type</label>
                            <select class="form-select" name="fields[${index}][type]">
                                <option value="text">Text</option>
                                <option value="textarea">Textarea</option>
                                <option value="number">Number</option>
                                <option value="date">Date</option>
                                <option value="select">Select</option>
                                <option value="checkbox">Checkbox</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Required</label>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="fields[${index}][required]" value="1">
                                <label class="form-check-label">Yes</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Placeholder</label>
                            <input class="form-control" name="fields[${index}][placeholder]" placeholder="Placeholder text">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Help text</label>
                            <input class="form-control" name="fields[${index}][help_text]" placeholder="Short guidance for the user">
                        </div>
                        <div class="col-12 text-end">
                            <button type="button" class="btn btn-sm btn-outline-danger remove-field">Remove</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    let draggedRow = null;

    addButton.addEventListener('click', () => {
        const existingRows = list.querySelectorAll('.field-row').length;
        const newIndex = existingRows;
        list.insertAdjacentHTML('beforeend', createField(newIndex));
        const noFieldsNotice = list.querySelector('.card.border-dashed');
        if (noFieldsNotice) {
            noFieldsNotice.remove();
        }
        reindexRows();
    });

    list.addEventListener('click', (event) => {
        const removeButton = event.target.closest('.remove-field');
        if (!removeButton) {
            return;
        }

        const row = removeButton.closest('.field-row');
        if (!row) {
            return;
        }

        row.remove();
        reindexRows();

        if (list.querySelectorAll('.field-row').length === 0) {
            list.insertAdjacentHTML('beforeend', emptyState());
        }
    });

    list.addEventListener('dragstart', (event) => {
        const row = event.target.closest('.field-row');
        if (!row) {
            return;
        }
        draggedRow = row;
        row.classList.add('dragging');
        event.dataTransfer.effectAllowed = 'move';
    });

    list.addEventListener('dragover', (event) => {
        if (!draggedRow) {
            return;
        }
        const targetRow = event.target.closest('.field-row');
        if (!targetRow || targetRow === draggedRow) {
            return;
        }
        event.preventDefault();
        const rect = targetRow.getBoundingClientRect();
        const before = event.clientY < rect.top + rect.height / 2;
        list.insertBefore(draggedRow, before ? targetRow : targetRow.nextSibling);
        reindexRows();
    });

    list.addEventListener('dragend', () => {
        if (draggedRow) {
            draggedRow.classList.remove('dragging');
        }
        draggedRow = null;
        reindexRows();
    });
});
</script>
