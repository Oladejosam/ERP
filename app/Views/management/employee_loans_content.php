<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold">Employee Loans</h3>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createLoanModal">+ Create Loan</button>
    </div>

    <?php if (!empty($_SESSION['payroll_flash'])): ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_SESSION['payroll_flash']); unset($_SESSION['payroll_flash']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <ul class="nav nav-tabs mb-3" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" data-bs-toggle="tab" href="#active">Active Loans</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#closed">Closed Loans</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#all">All Loans</a>
                </li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade show active" id="active">
                    <?php renderLoansTable(array_filter($loans, fn($l) => $l['status'] === 'active')); ?>
                </div>
                <div class="tab-pane fade" id="closed">
                    <?php renderLoansTable(array_filter($loans, fn($l) => $l['status'] === 'closed')); ?>
                </div>
                <div class="tab-pane fade" id="all">
                    <?php renderLoansTable($loans); ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Loan Modal -->
<div class="modal fade" id="createLoanModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create Employee Loan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" action="/ERP/public/management/employee-loans/create">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Employee</label>
                            <select class="form-select" name="employee_id" required>
                                <option value="">Select employee</option>
                                <?php foreach ($employees as $employee): ?>
                                    <option value="<?php echo (int)$employee['id']; ?>">
                                        <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Loan Amount (₦)</label>
                            <input type="number" step="0.01" class="form-control" name="loan_amount" value="0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Interest Rate (%)</label>
                            <input type="number" step="0.01" class="form-control" name="interest_rate" value="0">
                            <small class="text-muted">Annual interest rate</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tenure (Months)</label>
                            <input type="number" class="form-control" name="tenure_months" value="12" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Remarks</label>
                            <textarea class="form-control" name="remarks" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="alert alert-info mt-3 mb-0">
                        <small>EMI schedule will be automatically generated after creating the loan.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Loan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
function renderLoansTable($loans) {
    echo '<div class="table-responsive">';
    echo '<table class="table table-striped align-middle">';
    echo '<thead><tr><th>Employee</th><th>Loan Amount</th><th>Interest</th><th>EMI Amount</th><th>Balance</th><th>Status</th><th>Due Date</th><th>Actions</th></tr></thead>';
    echo '<tbody>';
    
    if (empty($loans)) {
        echo '<tr><td colspan="8" class="text-center text-muted py-4">No loans found.</td></tr>';
    } else {
        foreach ($loans as $loan) {
            echo '<tr>';
            echo '<td>';
            echo '<strong>' . htmlspecialchars($loan['first_name'] . ' ' . $loan['last_name']) . '</strong>';
            echo '<br><small class="text-muted">' . htmlspecialchars($loan['employee_code']) . '</small>';
            echo '</td>';
            echo '<td>₦' . number_format((float)$loan['loan_amount'], 2) . '</td>';
            echo '<td>' . number_format((float)$loan['interest_rate'], 2) . '%</td>';
            echo '<td>₦' . number_format((float)$loan['emi_amount'], 2) . '</td>';
            echo '<td><strong>₦' . number_format((float)$loan['balance_amount'], 2) . '</strong></td>';
            echo '<td>';
            
            $statusBadge = match($loan['status']) {
                'active' => '<span class="badge bg-success">Active</span>',
                'closed' => '<span class="badge bg-info">Closed</span>',
                'defaulted' => '<span class="badge bg-danger">Defaulted</span>',
                default => '<span class="badge bg-secondary">Unknown</span>'
            };
            
            echo $statusBadge;
            echo '</td>';
            echo '<td>' . htmlspecialchars($loan['due_date']) . '</td>';
            echo '<td>';
            echo '<a href="/ERP/public/management/employee-loans/view?id=' . (int)$loan['id'] . '" class="btn btn-sm btn-outline-primary">View EMI</a>';
            echo '</td>';
            echo '</tr>';
        }
    }
    
    echo '</tbody></table></div>';
}
?>
