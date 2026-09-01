<div class="container-fluid py-4">
    <h3 class="fw-bold mb-4">Payroll Reports</h3>

    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6 class="text-white-50">Total Payrolls</h6>
                    <h2 class="fw-bold"><?php echo count($payrolls); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6 class="text-white-50">Total Gross</h6>
                    <h2 class="fw-bold">₦<?php echo number_format(array_sum(array_map(fn($p) => (float)($p['basic_salary'] + $p['allowances']), $payrolls)), 2); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h6 class="text-white-50">Total Deductions</h6>
                    <h2 class="fw-bold">₦<?php echo number_format(array_sum(array_map(fn($p) => (float)$p['deductions'], $payrolls)), 2); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h6 class="text-white-50">Total Net Pay</h6>
                    <h2 class="fw-bold">₦<?php echo number_format(array_sum(array_map(fn($p) => (float)$p['net_pay'], $payrolls)), 2); ?></h2>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <h5 class="fw-bold mb-3">Payroll Details</h5>
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Month</th>
                            <th>Basic Salary</th>
                            <th>Allowances</th>
                            <th>Gross</th>
                            <th>Deductions</th>
                            <th>Net Pay</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($payrolls)): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">No payroll records found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($payrolls as $payroll): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($payroll['first_name'] . ' ' . $payroll['last_name']); ?></strong>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($payroll['position'] ?? 'Employee'); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($payroll['payroll_month']); ?></td>
                                    <td>₦<?php echo number_format((float)$payroll['basic_salary'], 2); ?></td>
                                    <td>₦<?php echo number_format((float)$payroll['allowances'], 2); ?></td>
                                    <td>₦<?php echo number_format((float)($payroll['basic_salary'] + $payroll['allowances']), 2); ?></td>
                                    <td>₦<?php echo number_format((float)$payroll['deductions'], 2); ?></td>
                                    <td><strong>₦<?php echo number_format((float)$payroll['net_pay'], 2); ?></strong></td>
                                    <td>
                                        <?php if (!empty($payroll['sent_to_portal'])): ?>
                                            <span class="badge bg-success">Sent</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Pending</span>
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
</div>
