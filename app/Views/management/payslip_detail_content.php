<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold">Payslip - <?php echo htmlspecialchars($payslip['payroll_month']); ?></h3>
        <div>
            <a href="/ERP/public/management/payslip/download?id=<?php echo (int)$payslip['id']; ?>" class="btn btn-primary" target="_blank">
                <i class="bi bi-download"></i> View/Print
            </a>
            <a href="/ERP/public/management/payroll-reports" class="btn btn-outline-secondary">Back to Reports</a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <small class="text-muted">Employee Name</small>
                            <h6 class="fw-bold"><?php echo htmlspecialchars($payslip['first_name'] . ' ' . $payslip['last_name']); ?></h6>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted">Employee Code</small>
                            <h6 class="fw-bold"><?php echo htmlspecialchars($payslip['employee_code']); ?></h6>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted">Designation</small>
                            <h6 class="fw-bold"><?php echo htmlspecialchars($payslip['designation'] ?? 'N/A'); ?></h6>
                        </div>
                    </div>

                    <h5 class="fw-bold mt-4 mb-3">Earnings</h5>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Description</th>
                                    <th class="text-end">Amount (₦)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $allowances = array_filter($payslip['components'], fn($c) => $c['component_type'] === 'allowance');
                                if (!empty($allowances)):
                                    foreach ($allowances as $allowance):
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($allowance['component_name']); ?></td>
                                        <td class="text-end">₦<?php echo number_format((float)$allowance['component_value'], 2); ?></td>
                                    </tr>
                                <?php
                                    endforeach;
                                else:
                                ?>
                                    <tr>
                                        <td colspan="2" class="text-muted text-center py-2">No allowances</td>
                                    </tr>
                                <?php endif; ?>
                                <tr class="fw-bold bg-light">
                                    <td>Total Earnings</td>
                                    <td class="text-end">₦<?php echo number_format((float)$payslip['gross_salary'], 2); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <h5 class="fw-bold mt-4 mb-3">Deductions</h5>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Description</th>
                                    <th class="text-end">Amount (₦)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $deductions = array_filter($payslip['components'], fn($c) => $c['component_type'] === 'deduction');
                                if (!empty($deductions)):
                                    foreach ($deductions as $deduction):
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($deduction['component_name']); ?></td>
                                        <td class="text-end">₦<?php echo number_format((float)$deduction['component_value'], 2); ?></td>
                                    </tr>
                                <?php
                                    endforeach;
                                else:
                                ?>
                                    <tr>
                                        <td colspan="2" class="text-muted text-center py-2">No deductions</td>
                                    </tr>
                                <?php endif; ?>
                                <tr class="fw-bold bg-light">
                                    <td>Total Deductions</td>
                                    <td class="text-end">₦<?php echo number_format((float)$payslip['total_deductions'], 2); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="alert alert-success mt-4 mb-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-bold">NET SALARY</span>
                            <h5 class="mb-0">₦<?php echo number_format((float)$payslip['net_pay'], 2); ?></h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm bg-light">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">Summary</h5>
                    <div class="mb-3">
                        <small class="text-muted">Gross Salary</small>
                        <h6 class="fw-bold">₦<?php echo number_format((float)$payslip['gross_salary'], 2); ?></h6>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">Total Deductions</small>
                        <h6 class="fw-bold">₦<?php echo number_format((float)$payslip['total_deductions'], 2); ?></h6>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">Net Pay</small>
                        <h6 class="fw-bold text-success">₦<?php echo number_format((float)$payslip['net_pay'], 2); ?></h6>
                    </div>
                    <hr>
                    <div class="mb-3">
                        <small class="text-muted">Payroll Month</small>
                        <h6 class="fw-bold"><?php echo htmlspecialchars($payslip['payroll_month']); ?></h6>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">Generated On</small>
                        <h6 class="fw-bold"><?php echo htmlspecialchars($payslip['generated_at'] ?? 'Not yet generated'); ?></h6>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mt-3">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">Actions</h5>
                    <a href="/ERP/public/management/payslip/download?id=<?php echo (int)$payslip['id']; ?>" class="btn btn-outline-primary btn-sm d-block mb-2" target="_blank">View as HTML</a>
                    <a href="/ERP/public/management/payroll-reports" class="btn btn-outline-secondary btn-sm d-block">Back to All Payslips</a>
                </div>
            </div>
        </div>
    </div>
</div>
