<div class="container-fluid py-4">
    <h3 class="fw-bold mb-4">Payroll Configuration</h3>

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
                    <h5 class="fw-bold mb-4">System Settings</h5>
                    <form method="post" action="/ERP/public/management/payroll-configuration/save">
                        <div class="mb-3">
                            <label class="form-label">Financial Year Start Month</label>
                            <select class="form-select" name="financial_year_start_month">
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <option value="<?php echo $m; ?>" <?php echo (isset($config['financial_year_start_month']) && (int)$config['financial_year_start_month'] === $m) ? 'selected' : ''; ?>>
                                        <?php echo date('F', mktime(0, 0, 0, $m, 1)); ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Tax Calculation Method</label>
                            <select class="form-select" name="tax_calculation_method">
                                <option value="percentage" <?php echo (isset($config['tax_calculation_method']) && $config['tax_calculation_method'] === 'percentage') ? 'selected' : ''; ?>>Percentage Based</option>
                                <option value="slab" <?php echo (isset($config['tax_calculation_method']) && $config['tax_calculation_method'] === 'slab') ? 'selected' : ''; ?>>Tax Slab Based</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Default Income Tax Rate (%)</label>
                            <input type="number" step="0.01" class="form-control" name="default_tax_rate" value="<?php echo isset($config['default_tax_rate']) ? number_format((float)$config['default_tax_rate'], 2) : '10'; ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Default Pension Contribution Rate (%)</label>
                            <input type="number" step="0.01" class="form-control" name="default_pension_rate" value="<?php echo isset($config['default_pension_rate']) ? number_format((float)$config['default_pension_rate'], 2) : '8'; ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Maximum Salary Advance Percentage (%)</label>
                            <input type="number" step="1" class="form-control" name="max_salary_advance_percentage" value="<?php echo isset($config['max_salary_advance_percentage']) ? (int)$config['max_salary_advance_percentage'] : '50'; ?>" required>
                            <small class="text-muted">Maximum percentage of monthly salary an employee can request as advance</small>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="allow_multiple_loans" id="multiLoans" <?php echo !empty($config['allow_multiple_loans']) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="multiLoans">
                                    Allow employees to have multiple active loans
                                </label>
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">Save Configuration</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card bg-light">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">Configuration Guide</h5>
                    <div class="small">
                        <h6 class="fw-bold mb-2">Financial Year</h6>
                        <p class="text-muted mb-3">Set when your financial year starts for tax calculation purposes.</p>

                        <h6 class="fw-bold mb-2">Tax Settings</h6>
                        <p class="text-muted mb-3">Configure how income tax is calculated. Percentage-based is simpler; tax slab is progressive.</p>

                        <h6 class="fw-bold mb-2">Advance Rules</h6>
                        <p class="text-muted mb-3">Control how much salary an employee can request as advance each month.</p>

                        <h6 class="fw-bold mb-2">Loans</h6>
                        <p class="text-muted">Choose whether employees can have multiple active loans simultaneously.</p>
                    </div>
                </div>
            </div>

            <div class="card mt-3 bg-info text-white">
                <div class="card-body">
                    <h5 class="fw-bold mb-2">Quick Links</h5>
                    <a href="/ERP/public/management/salary-structures" class="link-light d-block mb-2">Manage Salary Structures</a>
                    <a href="/ERP/public/management/process-payroll" class="link-light d-block mb-2">Process Monthly Payroll</a>
                    <a href="/ERP/public/management/payroll-reports" class="link-light d-block">View Payroll Reports</a>
                </div>
            </div>
        </div>
    </div>
</div>
