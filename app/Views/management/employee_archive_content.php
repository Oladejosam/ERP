<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Employee Archive</h4>
        <p class="text-muted mb-0">Deleted employees and columns disabled for the current company.</p>
    </div>
    <a href="/ERP/public/management/employees" class="btn btn-outline-secondary">Back to Employees</a>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <h5 class="fw-bold mb-3">Deleted Columns</h5>
        <?php if (empty($disabledColumns)): ?>
            <p class="text-muted mb-0">No columns have been disabled for this company.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead><tr><th>Column</th><th>Disabled On</th></tr></thead>
                    <tbody>
                        <?php foreach ($disabledColumns as $column): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($column['field_name']); ?></td>
                                <td><?php echo htmlspecialchars($column['created_at']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <h5 class="fw-bold mb-3">Deleted Employees</h5>
        <?php if (empty($archivedEmployees)): ?>
            <p class="text-muted mb-0">No deleted employees have been archived.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead><tr><th>Employee Code</th><th>Name</th><th>Email</th><th>Department</th><th>Deleted On</th></tr></thead>
                    <tbody>
                        <?php foreach ($archivedEmployees as $employee): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($employee['employee_code'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars(trim(($employee['first_name'] ?? '') . ' ' . ($employee['last_name'] ?? ''))); ?></td>
                                <td><?php echo htmlspecialchars($employee['email'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($employee['department'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($employee['deleted_at'] ?? ''); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
