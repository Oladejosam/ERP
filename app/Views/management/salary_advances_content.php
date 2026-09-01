<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold">Salary Advances</h3>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#requestAdvanceModal">+ Request Advance</button>
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
                    <a class="nav-link active" data-bs-toggle="tab" href="#all">All Requests</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#pending">Pending</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#approved">Approved</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#rejected">Rejected</a>
                </li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade show active" id="all">
                    <?php renderAdvancesTable($advances); ?>
                </div>
                <div class="tab-pane fade" id="pending">
                    <?php renderAdvancesTable(array_filter($advances, fn($a) => $a['status'] === 'pending')); ?>
                </div>
                <div class="tab-pane fade" id="approved">
                    <?php renderAdvancesTable(array_filter($advances, fn($a) => $a['status'] === 'approved')); ?>
                </div>
                <div class="tab-pane fade" id="rejected">
                    <?php renderAdvancesTable(array_filter($advances, fn($a) => $a['status'] === 'rejected')); ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Request Advance Modal -->
<div class="modal fade" id="requestAdvanceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Request Salary Advance</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" action="/ERP/public/management/salary-advances/request">
                <div class="modal-body">
                    <div class="mb-3">
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
                    <div class="mb-3">
                        <label class="form-label">Amount (₦)</label>
                        <input type="number" step="0.01" class="form-control" name="amount" value="0" required>
                        <small class="text-muted">Maximum 50% of monthly salary</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Remarks</label>
                        <textarea class="form-control" name="remarks" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Submit Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
function renderAdvancesTable($advances) {
    echo '<div class="table-responsive">';
    echo '<table class="table table-striped align-middle">';
    echo '<thead><tr><th>Employee</th><th>Amount</th><th>Status</th><th>Requested Date</th><th>Approved Date</th><th>Recovery %</th><th>Actions</th></tr></thead>';
    echo '<tbody>';

    if (empty($advances)) {
        echo '<tr><td colspan="7" class="text-center text-muted py-4">No records found.</td></tr>';
    } else {
        foreach ($advances as $advance) {
            $statusBadge = match($advance['status']) {
                'pending' => '<span class="badge bg-warning">Pending</span>',
                'approved' => '<span class="badge bg-success">Approved</span>',
                'rejected' => '<span class="badge bg-danger">Rejected</span>',
                'recovered' => '<span class="badge bg-info">Recovered</span>',
                default => '<span class="badge bg-secondary">Unknown</span>'
            };

            echo '<tr>';
            echo '<td>';
            echo '<strong>' . htmlspecialchars($advance['first_name'] . ' ' . $advance['last_name']) . '</strong>';
            echo '<br><small class="text-muted">' . htmlspecialchars($advance['employee_code']) . '</small>';
            echo '</td>';
            echo '<td>₦' . number_format((float)$advance['amount'], 2) . '</td>';
            echo '<td>' . $statusBadge . '</td>';
            echo '<td>' . htmlspecialchars($advance['requested_date']) . '</td>';
            echo '<td>' . (empty($advance['approved_date']) ? '-' : htmlspecialchars($advance['approved_date'])) . '</td>';
            echo '<td>' . number_format((float)$advance['recovery_deduction'], 1) . '%</td>';
            echo '<td>';

            if ($advance['status'] === 'pending') {
                echo '<button class="btn btn-sm btn-success" onclick="approveAdvance(' . (int)$advance['id'] . ')">Approve</button> ';
                echo '<button class="btn btn-sm btn-danger" onclick="rejectAdvance(' . (int)$advance['id'] . ')">Reject</button>';
            } else {
                echo '-';
            }

            echo '</td>';
            echo '</tr>';
        }
    }

    echo '</tbody></table></div>';
}
?>

<script>
function approveAdvance(advanceId) {
    if (confirm('Approve this salary advance?')) {
        fetch('/ERP/public/management/salary-advances/approve', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'advance_id=' + advanceId
        })
        .then(r => r.json())
        .then(d => {
            alert(d.message || 'Approved');
            location.reload();
        })
        .catch(e => alert('Error: ' + e.message));
    }
}

function rejectAdvance(advanceId) {
    const reason = prompt('Rejection reason:');
    if (reason !== null) {
        fetch('/ERP/public/management/salary-advances/reject', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'advance_id=' + advanceId + '&reason=' + encodeURIComponent(reason)
        })
        .then(r => r.json())
        .then(d => {
            alert(d.message || 'Rejected');
            location.reload();
        })
        .catch(e => alert('Error: ' + e.message));
    }
}
</script>
