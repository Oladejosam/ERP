<div class="card shadow-sm border-0">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="fw-bold">Employee Payroll Portal</h4>
                <p class="text-muted mb-0">View payroll entries that have been sent to the employee portal.</p>
            </div>
            <a class="btn btn-secondary" href="/ERP/public/modules/accounting">Back to Accounting</a>
        </div>

        <?php if (empty($portalPayrolls)): ?>
            <div class="alert alert-info">No payroll records have been sent to the portal yet.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Payroll Month</th>
                            <th>Basic Salary</th>
                            <th>Allowances</th>
                            <th>Deductions</th>
                            <th>Net Pay</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($portalPayrolls as $payroll): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($payroll['payroll_month']); ?></td>
                                <td>₦<?php echo number_format((float)$payroll['basic_salary'], 2); ?></td>
                                <td>₦<?php echo number_format((float)$payroll['allowances'], 2); ?></td>
                                <td>₦<?php echo number_format((float)$payroll['deductions'], 2); ?></td>
                                <td>₦<?php echo number_format((float)$payroll['net_pay'], 2); ?></td>
                                <td><span class="badge bg-success">Sent</span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
