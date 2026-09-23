<ul class="nav nav-tabs mb-4" role="tablist">
    <li class="nav-item" role="presentation"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#salary-pane" type="button" role="tab">Salary</button></li>
    <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#purchase-payments-pane" type="button" role="tab">Purchase Payments &amp; Scheduling</button></li>
</ul>

<div class="tab-content">
<div class="tab-pane fade show active" id="salary-pane" role="tabpanel">
<div class="card shadow-sm border-0">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div>
                <h4 class="fw-bold mb-1">Accounting & Payroll Dashboard</h4>
                <small class="text-muted">Salary processing, reports, and employee pay records</small>
            </div>
            <div class="d-flex gap-2 align-items-center flex-wrap">
                <a href="/ERP/public/modules/accounting/payroll" class="btn btn-primary btn-sm">Process Payroll</a>
                <a href="/ERP/public/management/payroll-reports" class="btn btn-outline-primary btn-sm">Payroll Reports</a>
                <a href="/ERP/public/management/payroll-configuration" class="btn btn-outline-secondary btn-sm">Settings</a>
                <a href="/ERP/public/portal/payroll" class="btn btn-outline-secondary btn-sm">Employee Portal</a>
                <form method="post" action="/ERP/public/modules/accounting/send-all" class="d-inline" id="bulkSendSelectedForm">
                    <button type="submit" class="btn btn-warning btn-sm">Send All</button>
                </form>
                <button type="button" id="bulkSendSelectedButton" class="btn btn-outline-warning btn-sm">Send Selected</button>
                <form method="post" action="/ERP/public/modules/accounting/upload" enctype="multipart/form-data" class="d-flex gap-2 align-items-center m-0">
                    <label class="btn btn-outline-primary btn-sm mb-0">
                        Choose File
                        <input type="file" name="payroll_file" accept=".csv" hidden required>
                    </label>
                    <button type="submit" class="btn btn-secondary btn-sm">Upload</button>
                </form>
                <a href="/ERP/public/modules/accounting/template" class="btn btn-info btn-sm">Download Template</a>
                <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addPayrollModal">New Entry</button>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white h-100 border-0">
                    <div class="card-body">
                        <small class="text-white-50">This Month</small>
                        <h4 class="fw-bold mb-0"><?php echo count($payrolls); ?></h4>
                        <small>Payroll records</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white h-100 border-0">
                    <div class="card-body">
                        <small class="text-white-50">Gross Pay</small>
                        <h4 class="fw-bold mb-0">₦<?php echo number_format(array_sum(array_map(fn($p) => (float)($p['basic_salary'] + $p['allowances']), $payrolls)), 2); ?></h4>
                        <small>Across employees</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white h-100 border-0">
                    <div class="card-body">
                        <small class="text-white-50">Deductions</small>
                        <h4 class="fw-bold mb-0">₦<?php echo number_format(array_sum(array_map(fn($p) => (float)$p['deductions'], $payrolls)), 2); ?></h4>
                        <small>Scheduled deductions</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white h-100 border-0">
                    <div class="card-body">
                        <small class="text-white-50">Net Pay</small>
                        <h4 class="fw-bold mb-0">₦<?php echo number_format(array_sum(array_map(fn($p) => (float)$p['net_pay'], $payrolls)), 2); ?></h4>
                        <small>Disbursable pay</small>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($_SESSION['accounting_flash'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($_SESSION['accounting_flash']); unset($_SESSION['accounting_flash']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="table-responsive mt-4">
            <div class="d-flex justify-content-end mb-2">
                <label class="form-check form-check-inline mb-0">
                    <input class="form-check-input" type="checkbox" id="selectAllPayrollRows">
                    <span class="form-check-label">Select all</span>
                </label>
            </div>
            <table id="payrollTable" class="table table-striped">
                <thead>
                    <tr>
                        <th style="width: 40px;">
                            <input class="form-check-input payroll-row-select-all" type="checkbox" aria-label="Select all payroll rows">
                        </th>
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
                            <td colspan="10" class="text-center text-muted">No payroll records found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($payrolls as $payroll): ?>
                            <tr>
                                <td>
                                    <input class="form-check-input payroll-row-select" type="checkbox" value="<?php echo (int)$payroll['id']; ?>" aria-label="Select payroll for <?php echo htmlspecialchars(($payroll['first_name'] ?? '') . ' ' . ($payroll['last_name'] ?? '')); ?>">
                                </td>
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

</div>

<div class="tab-pane fade" id="purchase-payments-pane" role="tabpanel">
    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <div><h4 class="fw-bold mb-1">Purchase Payments</h4><small class="text-muted">Schedule payments against purchase orders approved by Procurement.</small></div>
                <span class="badge bg-success-subtle text-success">Approved procurement orders only</span>
            </div>
            <?php if (empty($approvedPurchaseOrders)): ?>
                <p class="text-muted mb-0">No approved purchase orders are available for payment scheduling.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead><tr><th>PO Number</th><th>Supplier</th><th>Order Date</th><th>Total</th><th>Scheduled</th><th>Balance</th><th>Action</th></tr></thead>
                        <tbody>
                        <?php foreach ($approvedPurchaseOrders as $order): ?>
                            <?php $orderTotal = (float)($order['total_amount'] ?? 0); $scheduledAmount = (float)($order['scheduled_amount'] ?? 0); ?>
                            <tr>
                                <td class="fw-semibold"><?php echo htmlspecialchars($order['po_number'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($order['supplier'] ?? 'Not specified'); ?></td>
                                <td><?php echo htmlspecialchars($order['order_date'] ?? ''); ?></td>
                                <td>₦<?php echo number_format($orderTotal, 2); ?></td>
                                <td>₦<?php echo number_format($scheduledAmount, 2); ?></td>
                                <td>₦<?php echo number_format(max(0, $orderTotal - $scheduledAmount), 2); ?></td>
                                <td><button class="btn btn-sm btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#paymentScheduleModal-<?php echo (int)$order['id']; ?>">Schedule Payment</button></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <h5 class="fw-bold mt-4 mb-3">Payment Schedule</h5>
            <?php if (empty($paymentSchedules)): ?>
                <p class="text-muted mb-0">No purchase payments have been scheduled.</p>
            <?php else: ?>
                <div class="table-responsive"><table class="table table-sm align-middle"><thead><tr><th>Date</th><th>PO</th><th>Supplier</th><th>Amount</th><th>Status</th><th>Reference</th><th>Action</th></tr></thead><tbody>
                    <?php foreach ($paymentSchedules as $payment): ?><tr><td><?php echo htmlspecialchars($payment['scheduled_date'] ?? ''); ?></td><td><?php echo htmlspecialchars($payment['po_number'] ?? ''); ?></td><td><?php echo htmlspecialchars($payment['supplier'] ?? 'Not specified'); ?></td><td>₦<?php echo number_format((float)($payment['amount'] ?? 0), 2); ?></td><td><span class="badge <?php echo ($payment['status'] ?? '') === 'paid' ? 'bg-success' : (($payment['status'] ?? '') === 'cancelled' ? 'bg-secondary' : 'bg-warning text-dark'); ?>"><?php echo htmlspecialchars(ucfirst((string)($payment['status'] ?? 'scheduled'))); ?></span></td><td><?php echo htmlspecialchars($payment['payment_reference'] ?? ''); ?></td><td><?php if (($payment['status'] ?? '') === 'scheduled'): ?><form method="post" action="/ERP/public/modules/accounting/purchase-payment/save" class="d-inline"><input type="hidden" name="payment_schedule_id" value="<?php echo (int)$payment['id']; ?>"><input type="hidden" name="purchase_order_id" value="<?php echo (int)$payment['purchase_order_id']; ?>"><input type="hidden" name="scheduled_date" value="<?php echo htmlspecialchars($payment['scheduled_date'] ?? ''); ?>"><input type="hidden" name="amount" value="<?php echo htmlspecialchars((string)$payment['amount']); ?>"><input type="hidden" name="payment_status" value="paid"><input type="hidden" name="payment_reference" value="<?php echo htmlspecialchars($payment['payment_reference'] ?? ''); ?>"><input type="hidden" name="payment_notes" value="<?php echo htmlspecialchars($payment['notes'] ?? ''); ?>"><button class="btn btn-sm btn-outline-success" type="submit">Mark Paid</button></form><?php else: ?>-<?php endif; ?></td></tr><?php endforeach; ?>
                </tbody></table></div>
            <?php endif; ?>
        </div>
    </div>
</div>
</div>

<?php foreach (($approvedPurchaseOrders ?? []) as $order): ?>
    <?php $orderTotal = (float)($order['total_amount'] ?? 0); $scheduledAmount = (float)($order['scheduled_amount'] ?? 0); ?>
    <div class="modal fade" id="paymentScheduleModal-<?php echo (int)$order['id']; ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog"><div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Schedule Payment: <?php echo htmlspecialchars($order['po_number'] ?? ''); ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <form method="post" action="/ERP/public/modules/accounting/purchase-payment/save">
                <div class="modal-body"><input type="hidden" name="purchase_order_id" value="<?php echo (int)$order['id']; ?>">
                    <div class="small text-muted mb-3">Remaining balance: ₦<?php echo number_format(max(0, $orderTotal - $scheduledAmount), 2); ?></div>
                    <div class="mb-3"><label class="form-label">Payment Date</label><input class="form-control" type="date" name="scheduled_date" value="<?php echo date('Y-m-d'); ?>" required></div>
                    <div class="mb-3"><label class="form-label">Amount</label><input class="form-control" type="number" name="amount" min="0.01" max="<?php echo number_format(max(0, $orderTotal - $scheduledAmount), 2, '.', ''); ?>" step="0.01" required></div>
                    <div class="mb-3"><label class="form-label">Status</label><select class="form-select" name="payment_status"><option value="scheduled">Scheduled</option><option value="paid">Paid</option><option value="cancelled">Cancelled</option></select></div>
                    <div class="mb-3"><label class="form-label">Payment Reference</label><input class="form-control" name="payment_reference"></div>
                    <div><label class="form-label">Notes</label><textarea class="form-control" name="payment_notes" rows="2"></textarea></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Save Payment Schedule</button></div>
            </form>
        </div></div>
    </div>
<?php endforeach; ?>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var searchInput = document.getElementById('payrollSearch');
        var rows = Array.from(document.querySelectorAll('#payrollTable tbody tr'));
        var headerSelectAll = document.querySelector('.payroll-row-select-all');
        var rowChecks = Array.from(document.querySelectorAll('.payroll-row-select'));
        var bulkForm = document.getElementById('bulkSendSelectedForm');
        var bulkSelectedButton = document.getElementById('bulkSendSelectedButton');

        if (headerSelectAll) {
            headerSelectAll.addEventListener('change', function () {
                rowChecks.forEach(function (checkbox) {
                    checkbox.checked = headerSelectAll.checked;
                });
            });
        }

        rowChecks.forEach(function (checkbox) {
            checkbox.addEventListener('change', function () {
                if (!checkbox.checked && headerSelectAll) {
                    headerSelectAll.checked = false;
                    return;
                }
                if (headerSelectAll && rowChecks.every(function (check) { return check.checked; })) {
                    headerSelectAll.checked = true;
                }
            });
        });

        if (bulkForm && bulkSelectedButton) {
            bulkSelectedButton.addEventListener('click', function (event) {
                event.preventDefault();
                var selectedIds = rowChecks.filter(function (checkbox) { return checkbox.checked; }).map(function (checkbox) { return checkbox.value; });

                if (!selectedIds.length) {
                    alert('Please select at least one payroll record to send to the portal.');
                    return;
                }

                var hiddenInputs = bulkForm.querySelectorAll('input[name="payroll_ids[]"]');
                hiddenInputs.forEach(function (input) {
                    input.remove();
                });

                selectedIds.forEach(function (id) {
                    var input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'payroll_ids[]';
                    input.value = id;
                    bulkForm.appendChild(input);
                });

                bulkForm.submit();
            });
        }

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