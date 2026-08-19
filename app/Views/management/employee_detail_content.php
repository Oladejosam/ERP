<div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1">Employee Details</h4>
                <p class="text-muted mb-0">Live employee information from the database.</p>
            </div>
            <a href="/ERP/public/management/employees" class="btn btn-outline-secondary">Back to Employees</a>
        </div>

<?php if (empty($employee)): ?>
    <div class="alert alert-warning">Employee record not found.</div>
<?php else: ?>
    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card border-0 bg-light h-100">
                <div class="card-body text-center">
                            <?php $photoPath = trim((string)($employee['profile_picture'] ?? '')); $photoUrl = $photoPath !== '' ? BASE_URL . '/uploads/' . ltrim($photoPath, '/') : ''; ?>
                            <?php if ($photoUrl !== ''): ?>
                                <img src="<?php echo htmlspecialchars($photoUrl); ?>" alt="Employee profile picture" class="rounded-circle border shadow-sm mx-auto d-block mb-3" style="width: 96px; height: 96px; object-fit: cover;">
                            <?php else: ?>
                                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 96px; height: 96px; font-size: 2rem;">
                                    <?php echo htmlspecialchars(strtoupper(substr(($employee['first_name'] ?? 'E'), 0, 1) . substr(($employee['last_name'] ?? 'M'), 0, 1))); ?>
                                </div>
                            <?php endif; ?>
                            <h5 class="fw-bold mb-1"><?php echo htmlspecialchars(trim((($employee['first_name'] ?? '') . ' ' . ($employee['last_name'] ?? '')))); ?></h5>
                            <div class="text-muted"><?php echo htmlspecialchars($employee['position'] ?? 'N/A'); ?></div>
                            <span class="badge bg-<?php echo strtolower((string)($employee['status'] ?? 'active')) === 'active' ? 'success' : 'secondary'; ?> mt-3">
                                <?php echo htmlspecialchars(ucfirst((string)($employee['status'] ?? 'active'))); ?>
                            </span>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="row g-3">
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <div class="small text-muted">Employee Code</div>
                                <div class="fw-semibold"><?php echo htmlspecialchars($employee['employee_code'] ?? 'N/A'); ?></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <div class="small text-muted">Email</div>
                                <div class="fw-semibold"><?php echo htmlspecialchars($employee['email'] ?? 'N/A'); ?></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <div class="small text-muted">Phone</div>
                                <div class="fw-semibold"><?php echo htmlspecialchars($employee['phone'] ?? 'N/A'); ?></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <div class="small text-muted">Department</div>
                                <div class="fw-semibold"><?php echo htmlspecialchars($employee['department'] ?? 'N/A'); ?></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <div class="small text-muted">Designation</div>
                                <div class="fw-semibold"><?php echo htmlspecialchars($employee['designation'] ?? 'N/A'); ?></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <div class="small text-muted">Hire Date</div>
                                <div class="fw-semibold"><?php echo htmlspecialchars($employee['hire_date'] ?? 'N/A'); ?></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <div class="small text-muted">Salary</div>
                                <div class="fw-semibold">₦<?php echo number_format((float)($employee['salary'] ?? 0), 2); ?></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <div class="small text-muted">Created</div>
                                <div class="fw-semibold"><?php echo htmlspecialchars($employee['created_at'] ?? 'N/A'); ?></div>
                            </div>
                        </div>
            </div>
        </div>
    </div>
<?php endif; ?>
