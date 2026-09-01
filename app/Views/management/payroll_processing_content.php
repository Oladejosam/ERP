<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold">Payroll Processing</h3>
        <a href="/ERP/public/management/payroll-reports" class="btn btn-outline-secondary">View Payroll Reports</a>
    </div>

    <?php if (!empty($_SESSION['payroll_flash'])): ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_SESSION['payroll_flash']); unset($_SESSION['payroll_flash']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="fw-bold mb-4">Process Monthly Payroll</h5>
                    <form method="post" action="/ERP/public/management/payroll/run">
                        <div class="mb-3">
                            <label class="form-label">Payroll Month</label>
                            <input type="month" class="form-control" name="payroll_month" value="<?php echo htmlspecialchars($current_month); ?>" required>
                            <small class="text-muted">Select the month for which payroll should be processed</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Select Employees to Process</label>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="selectAll">
                                <label class="form-check-label" for="selectAll">
                                    <strong>Select All Employees</strong>
                                </label>
                            </div>
                            <div class="border rounded p-3" style="max-height: 400px; overflow-y: auto;">
                                <?php foreach ($employees as $employee): ?>
                                    <div class="form-check">
                                        <input class="form-check-input employee-checkbox" type="checkbox" name="employee_ids[]" value="<?php echo (int)$employee['id']; ?>" id="emp_<?php echo (int)$employee['id']; ?>">
                                        <label class="form-check-label" for="emp_<?php echo (int)$employee['id']; ?>">
                                            <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name'] . ' (' . ($employee['position'] ?? 'Employee') . ')'); ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">Process Payroll</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm bg-light">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">Payroll Summary</h5>
                    <ul class="list-unstyled">
                        <li class="mb-3">
                            <span class="badge bg-primary"><?php echo count($employees); ?></span>
                            <span class="ms-2">Total Employees</span>
                        </li>
                        <li class="mb-3">
                            <span class="badge bg-success">$0</span>
                            <span class="ms-2">Total Payroll (Current Month)</span>
                        </li>
                        <li class="mb-3">
                            <span class="badge bg-warning">-</span>
                            <span class="ms-2">Last Processed</span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="card shadow-sm mt-3">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">Quick Actions</h5>
                    <a href="/ERP/public/management/payroll-configuration" class="btn btn-outline-primary btn-sm d-block mb-2">Configure Payroll Settings</a>
                    <a href="/ERP/public/management/salary-structures" class="btn btn-outline-primary btn-sm d-block mb-2">Manage Salary Structures</a>
                    <a href="/ERP/public/management/salary-advances" class="btn btn-outline-primary btn-sm d-block mb-2">Review Salary Advances</a>
                    <a href="/ERP/public/management/employee-loans" class="btn btn-outline-primary btn-sm d-block">Manage Employee Loans</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('selectAll').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.employee-checkbox');
    checkboxes.forEach(cb => cb.checked = this.checked);
});
</script>
