<div class="card shadow-sm border-0">
    <div class="card-body">
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
            <?php if (!empty($_SESSION['employee_flash'])): ?>
                <div class="alert alert-info"><?php echo htmlspecialchars($_SESSION['employee_flash']); unset($_SESSION['employee_flash']); ?></div>
            <?php endif; ?>
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

            <div class="card border-0 bg-light mt-4">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">Edit Employee Details</h5>
                    <form method="post" action="/ERP/public/management/employees/update">
                        <input type="hidden" name="employee_id" value="<?php echo (int)$employee['id']; ?>">
                        <div class="row g-3">
                            <div class="col-md-3"><label class="form-label">Employee Code</label><input class="form-control" name="employee_code" value="<?php echo htmlspecialchars($employee['employee_code'] ?? ''); ?>" required></div>
                            <div class="col-md-3"><label class="form-label">First Name</label><input class="form-control" name="first_name" value="<?php echo htmlspecialchars($employee['first_name'] ?? ''); ?>" required></div>
                            <div class="col-md-3"><label class="form-label">Last Name</label><input class="form-control" name="last_name" value="<?php echo htmlspecialchars($employee['last_name'] ?? ''); ?>" required></div>
                            <div class="col-md-3"><label class="form-label">Email</label><input class="form-control" type="email" name="email" value="<?php echo htmlspecialchars($employee['email'] ?? ''); ?>" required></div>
                            <div class="col-md-3"><label class="form-label">Phone</label><input class="form-control" name="phone" value="<?php echo htmlspecialchars($employee['phone'] ?? ''); ?>" required></div>
                            <div class="col-md-3"><label class="form-label">Department</label><select class="form-select" name="department" required><option value="">Select department</option><?php foreach ($departments ?? [] as $department): ?><option value="<?php echo htmlspecialchars($department['name']); ?>" <?php echo ($employee['department'] ?? '') === $department['name'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($department['name']); ?></option><?php endforeach; ?></select></div>
                            <div class="col-md-3"><label class="form-label">Position / Job Role</label><select class="form-select" name="position" required><option value="">Select position</option><?php foreach ($roles ?? [] as $role): ?><option value="<?php echo htmlspecialchars($role['name']); ?>" <?php echo strtolower((string)($employee['position'] ?? '')) === strtolower((string)$role['name']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($role['name']); ?></option><?php endforeach; ?></select></div>
                            <div class="col-md-3"><label class="form-label">Role</label><select class="form-select" name="role_id" required><option value="">Select role</option><?php foreach ($roles ?? [] as $role): ?><option value="<?php echo (int)$role['id']; ?>" <?php echo (int)($employee['role_id'] ?? 0) === (int)$role['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($role['name']); ?></option><?php endforeach; ?></select></div>
                            <div class="col-md-3"><label class="form-label">Designation</label><input class="form-control" name="designation" value="<?php echo htmlspecialchars($employee['designation'] ?? ''); ?>" required></div>
                            <div class="col-md-3"><label class="form-label">Hire Date</label><input class="form-control" type="date" name="hire_date" value="<?php echo htmlspecialchars($employee['hire_date'] ?? ''); ?>" required></div>
                            <div class="col-md-3"><label class="form-label">Salary</label><input class="form-control" type="number" min="0" step="0.01" name="salary" value="<?php echo htmlspecialchars((string)($employee['salary'] ?? 0)); ?>"></div>
                            <div class="col-md-3"><label class="form-label">Status</label><select class="form-select" name="status"><option value="active" <?php echo ($employee['status'] ?? '') === 'active' ? 'selected' : ''; ?>>Active</option><option value="inactive" <?php echo ($employee['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option><option value="terminated" <?php echo ($employee['status'] ?? '') === 'terminated' ? 'selected' : ''; ?>>Terminated</option></select></div>
                            <?php foreach (['nin' => 'NIN', 'account_number' => 'Account Number', 'account_name' => 'Account Name', 'bank_name' => 'Bank Name', 'tin' => 'TIN', 'pfa' => 'PFA'] as $field => $label): ?><div class="col-md-3"><label class="form-label"><?php echo $label; ?></label><input class="form-control" name="<?php echo $field; ?>" value="<?php echo htmlspecialchars($employee[$field] ?? ''); ?>"></div><?php endforeach; ?>
                        </div>
                        <button class="btn btn-primary mt-3" type="submit">Save Employee Details</button>
                    </form>
                </div>
            </div>

            <div class="card border-0 bg-light mt-4">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">Change Login Password</h5>
                    <form method="post" action="/ERP/public/management/employees/change-password" class="row g-3">
                        <input type="hidden" name="employee_id" value="<?php echo (int)$employee['id']; ?>">
                        <div class="col-md-4"><label class="form-label">New Password</label><input class="form-control" type="password" name="new_password" minlength="8" required></div>
                        <div class="col-md-4"><label class="form-label">Confirm Password</label><input class="form-control" type="password" name="confirm_password" minlength="8" required></div>
                        <div class="col-md-4 d-flex align-items-end"><button class="btn btn-outline-danger" type="submit">Update Login Password</button></div>
                    </form>
                    <div class="form-text mt-2">Only Super Admins can change another employee's login password.</div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
