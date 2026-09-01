<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h3 class="fw-bold mb-1">Accounting Payroll Processing</h3>
            <small class="text-muted">Run payroll according to company payroll rules, allowances, deductions, advances, and loans.</small>
        </div>
        <div class="d-flex gap-2">
            <a href="/ERP/public/modules/accounting" class="btn btn-outline-secondary btn-sm">Back to Accounting</a>
            <a href="/ERP/public/management/payroll-reports" class="btn btn-outline-primary btn-sm">View Reports</a>
        </div>
    </div>

    <?php if (!empty($_SESSION['accounting_flash'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_SESSION['accounting_flash']); unset($_SESSION['accounting_flash']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($_SESSION['accounting_errors'])): ?>
        <div class="alert alert-warning">
            <strong>Processing Issues:</strong>
            <ul class="mb-0">
                <?php foreach ((array)$_SESSION['accounting_errors'] as $error): ?>
                    <li><?php echo htmlspecialchars((string)$error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php unset($_SESSION['accounting_errors']); ?>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h5 class="fw-bold mb-4">Payroll Run Setup</h5>
                    <form method="post" action="/ERP/public/modules/accounting/payroll/run">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Payroll Month</label>
                                <input type="month" class="form-control" name="payroll_month" value="<?php echo htmlspecialchars($current_month); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Payroll Basis</label>
                                <select class="form-select" name="payroll_basis">
                                    <option value="standard">Standard Accounting Payroll</option>
                                    <option value="gross_to_net">Gross-to-Net Calculation</option>
                                </select>
                            </div>
                        </div>

                        <div class="mt-4 mb-2 d-flex align-items-center justify-content-between">
                            <label class="form-label mb-0 fw-bold">Select Employees</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="selectAllEmployees">
                                <label class="form-check-label" for="selectAllEmployees">Select All</label>
                            </div>
                        </div>

                        <div class="border rounded p-3" style="max-height: 420px; overflow-y: auto;">
                            <?php if (empty($employees)): ?>
                                <div class="text-muted text-center py-4">No employees found for this company.</div>
                            <?php else: ?>
                                <?php foreach ($employees as $employee): ?>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input employee-check" type="checkbox" name="employee_ids[]" value="<?php echo (int)$employee['id']; ?>" id="emp_<?php echo (int)$employee['id']; ?>">
                                        <label class="form-check-label" for="emp_<?php echo (int)$employee['id']; ?>">
                                            <?php echo htmlspecialchars(($employee['first_name'] ?? '') . ' ' . ($employee['last_name'] ?? '')); ?>
                                            <span class="text-muted small">(<?php echo htmlspecialchars($employee['position'] ?? 'Employee'); ?>)</span>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-primary btn-lg">Process Payroll</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm border-0 bg-light h-100">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">Accounting Payroll Rules</h5>
                    <ul class="list-group list-group-flush small">
                        <li class="list-group-item bg-transparent px-0">Basic salary + taxable allowances</li>
                        <li class="list-group-item bg-transparent px-0">Income tax, pension, and insurance deduction</li>
                        <li class="list-group-item bg-transparent px-0">Salary advance recovery from net pay</li>
                        <li class="list-group-item bg-transparent px-0">Loan EMI deduction in arrears</li>
                        <li class="list-group-item bg-transparent px-0">Auto-generated payslip after processing</li>
                    </ul>

                    <div class="mt-4">
                        <a href="/ERP/public/management/salary-structures" class="btn btn-outline-primary btn-sm d-block mb-2">Manage Salary Structures</a>
                        <a href="/ERP/public/management/salary-advances" class="btn btn-outline-primary btn-sm d-block mb-2">Review Advances</a>
                        <a href="/ERP/public/management/employee-loans" class="btn btn-outline-primary btn-sm d-block">Handle Loans</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const selectAll = document.getElementById('selectAllEmployees');
    const checks = document.querySelectorAll('.employee-check');
    if (selectAll) {
        selectAll.addEventListener('change', function () {
            checks.forEach(function (box) {
                box.checked = selectAll.checked;
            });
        });
    }
});
</script>
