<div class="card shadow-sm border-0">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="fw-bold">Payroll Management</h4>
            <div class="d-flex gap-2 align-items-center">
                <input id="payrollSearch" type="search" class="form-control form-control-sm" placeholder="Search payroll" style="min-width:220px;" />
                <a href="/ERP/public/portal/payroll" class="btn btn-outline-secondary btn-sm">Employee Portal</a>
                <form method="post" action="/ERP/public/modules/accounting/send-all" class="d-inline">
                    <button type="submit" class="btn btn-warning btn-sm">Send All</button>
                </form>
                <form method="post" action="/ERP/public/modules/accounting/upload" enctype="multipart/form-data" class="d-flex gap-2 align-items-center m-0">
                    <label class="btn btn-outline-primary btn-sm mb-0">
                        Choose File
                        <input type="file" name="payroll_file" accept=".csv,.xlsx,.xls" hidden required>
                    </label>
                    <button type="submit" class="btn btn-secondary btn-sm">Upload</button>
                </form>
                <a href="/ERP/public/payroll_template.xls" class="btn btn-info btn-sm" target="_blank">Download Template</a>
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addPayrollModal">New Entry</button>
            </div>
        </div>

        <?php if (!empty($_SESSION['accounting_flash'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($_SESSION['accounting_flash']); unset($_SESSION['accounting_flash']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="table-responsive mt-4">
            <table id="payrollTable" class="table table-striped">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Role</th>
                        <th>Payroll Month</th>
                        <th>Basic Salary</th>
                        <th>Allowances</th>
                        <th>Deductions</th>
                        <th>Net Pay</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($payrolls)): ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted">No payroll records found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($payrolls as $payroll): ?>
                            <tr>
                                <td><?php echo htmlspecialchars(($payroll['first_name'] ?? '') . ' ' . ($payroll['last_name'] ?? '')); ?></td>
                                <td><?php echo htmlspecialchars($payroll['position'] ?? 'Employee'); ?></td>
                                <td><?php echo htmlspecialchars($payroll['payroll_month'] ?? ''); ?></td>
                                <td>₦<?php echo number_format((float)$payroll['basic_salary'], 2); ?></td>
                                <td>₦<?php echo number_format((float)$payroll['allowances'], 2); ?></td>
                                <td>₦<?php echo number_format((float)$payroll['deductions'], 2); ?></td>
                                <td>₦<?php echo number_format((float)$payroll['net_pay'], 2); ?></td>
                                <td>
                                    <?php if (!empty($payroll['sent_to_portal'])): ?>
                                        <span class="badge bg-success">Sent</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editPayrollModal-<?php echo (int)$payroll['id']; ?>">Edit</button>
                                    <?php if (empty($payroll['sent_to_portal'])): ?>
                                        <form method="post" action="/ERP/public/modules/accounting/send" class="d-inline">
                                            <input type="hidden" name="payroll_id" value="<?php echo (int)$payroll['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-success">Send to Portal</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="addPayrollModal" tabindex="-1" aria-labelledby="addPayrollModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addPayrollModalLabel">New Payroll Entry</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="/ERP/public/modules/accounting/save">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Employee</label>
                            <select class="form-select" name="employee_id">
                                <option value="">Select employee</option>
                                <?php foreach ($employees as $employee): ?>
                                    <option value="<?php echo (int)$employee['id']; ?>"><?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name'] . ' (' . ($employee['position'] ?? 'Employee') . ')'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Employee Code</label>
                            <input type="text" class="form-control" name="employee_code" placeholder="Enter employee code if not selected">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Payroll Month</label>
                            <input type="month" class="form-control" name="payroll_month" value="<?php echo date('Y-m'); ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Basic Salary</label>
                            <input type="number" step="0.01" class="form-control" name="basic_salary" value="0.00" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Allowances</label>
                            <input type="number" step="0.01" class="form-control" name="allowances" value="0.00">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Deductions</label>
                            <input type="number" step="0.01" class="form-control" name="deductions" value="0.00">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Payroll</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php foreach ($payrolls as $payroll): ?>
    <div class="modal fade" id="editPayrollModal-<?php echo (int)$payroll['id']; ?>" tabindex="-1" aria-labelledby="editPayrollModalLabel-<?php echo (int)$payroll['id']; ?>" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editPayrollModalLabel-<?php echo (int)$payroll['id']; ?>">Edit Payroll for <?php echo htmlspecialchars(($payroll['first_name'] ?? '') . ' ' . ($payroll['last_name'] ?? '')); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" action="/ERP/public/modules/accounting/save">
                    <input type="hidden" name="payroll_id" value="<?php echo (int)$payroll['id']; ?>">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Payroll Month</label>
                                <input type="month" class="form-control" name="payroll_month" value="<?php echo htmlspecialchars($payroll['payroll_month']); ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Basic Salary</label>
                                <input type="number" step="0.01" class="form-control" name="basic_salary" value="<?php echo number_format((float)$payroll['basic_salary'], 2, '.', ''); ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Allowances</label>
                                <input type="number" step="0.01" class="form-control" name="allowances" value="<?php echo number_format((float)$payroll['allowances'], 2, '.', ''); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Deductions</label>
                                <input type="number" step="0.01" class="form-control" name="deductions" value="<?php echo number_format((float)$payroll['deductions'], 2, '.', ''); ?>">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endforeach; ?>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var searchInput = document.getElementById('payrollSearch');
        var rows = Array.from(document.querySelectorAll('#payrollTable tbody tr'));
        if (!searchInput || rows.length === 0) {
            return;
        }

        searchInput.addEventListener('input', function () {
            var query = this.value.trim().toLowerCase();
            rows.forEach(function (row) {
                if (row.querySelector('td') === null) {
                    return;
                }
                var text = row.textContent.toLowerCase();
                row.style.display = query === '' || text.indexOf(query) !== -1 ? '' : 'none';
            });
        });
    });
</script>