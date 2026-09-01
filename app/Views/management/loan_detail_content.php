<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold"><?php echo htmlspecialchars($loan['first_name'] . ' ' . $loan['last_name']); ?> - Loan Details</h3>
            <small class="text-muted">Employee Code: <?php echo htmlspecialchars($loan['employee_code']); ?></small>
        </div>
        <a href="/ERP/public/management/employee-loans" class="btn btn-outline-secondary">Back to Loans</a>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">Loan Summary</h5>
                    <div class="mb-3">
                        <small class="text-muted">Loan Amount</small>
                        <h6 class="fw-bold">₦<?php echo number_format((float)$loan['loan_amount'], 2); ?></h6>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">EMI Amount</small>
                        <h6 class="fw-bold">₦<?php echo number_format((float)$loan['emi_amount'], 2); ?></h6>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">Remaining Balance</small>
                        <h6 class="fw-bold">₦<?php echo number_format((float)$loan['balance_amount'], 2); ?></h6>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">Interest Rate (Annual)</small>
                        <h6 class="fw-bold"><?php echo number_format((float)$loan['interest_rate'], 2); ?>%</h6>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">Tenure</small>
                        <h6 class="fw-bold"><?php echo (int)$loan['tenure_months']; ?> Months</h6>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">Installments Completed</small>
                        <h6 class="fw-bold"><?php echo (int)$loan['no_of_installments_completed']; ?> / <?php echo (int)$loan['tenure_months']; ?></h6>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">Status</small>
                        <div>
                            <?php
                            $statusBadge = match($loan['status']) {
                                'active' => '<span class="badge bg-success">Active</span>',
                                'closed' => '<span class="badge bg-info">Closed</span>',
                                'defaulted' => '<span class="badge bg-danger">Defaulted</span>',
                                default => '<span class="badge bg-secondary">Unknown</span>'
                            };
                            echo $statusBadge;
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">EMI Schedule</h5>
                    <div class="table-responsive">
                        <table class="table table-striped align-middle">
                            <thead>
                                <tr>
                                    <th>Installment #</th>
                                    <th>Due Date</th>
                                    <th>Principal</th>
                                    <th>Interest</th>
                                    <th>EMI Amount</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($loan['installments'])): ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">No installments found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($loan['installments'] as $installment): ?>
                                        <tr>
                                            <td><strong>#<?php echo (int)$installment['installment_number']; ?></strong></td>
                                            <td><?php echo htmlspecialchars($installment['due_date']); ?></td>
                                            <td>₦<?php echo number_format((float)$installment['principal'], 2); ?></td>
                                            <td>₦<?php echo number_format((float)$installment['interest'], 2); ?></td>
                                            <td><strong>₦<?php echo number_format((float)$installment['emi_amount'], 2); ?></strong></td>
                                            <td>
                                                <?php if ($installment['status'] === 'paid'): ?>
                                                    <span class="badge bg-success">Paid</span>
                                                <?php elseif ($installment['status'] === 'pending'): ?>
                                                    <span class="badge bg-warning">Pending</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger"><?php echo ucfirst(htmlspecialchars($installment['status'])); ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($installment['status'] === 'pending'): ?>
                                                    <button class="btn btn-sm btn-success" onclick="markPaid(<?php echo (int)$installment['id']; ?>)">Mark Paid</button>
                                                <?php else: ?>
                                                    -
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
    </div>
</div>

<script>
function markPaid(installmentId) {
    if (confirm('Mark this installment as paid?')) {
        fetch('/ERP/public/management/employee-loans/installment/paid', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'installment_id=' + installmentId
        })
        .then(r => r.json())
        .then(d => {
            alert(d.message || 'Installment marked as paid');
            location.reload();
        })
        .catch(e => alert('Error: ' + e.message));
    }
}
</script>
