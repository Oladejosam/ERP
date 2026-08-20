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
    <?php $visibleColumnKeys = array_fill_keys(array_column(($employeeColumns ?? []), 'key'), true); ?>
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
                            <?php if (isset($visibleColumnKeys['profile_picture'])): ?>
                            <form action="/ERP/public/management/employees/update-photo" method="post" enctype="multipart/form-data" class="text-start mb-3">
                                <input type="hidden" name="employee_id" value="<?php echo (int)$employee['id']; ?>">
                                <label for="profilePicture" class="form-label small fw-semibold">Change profile picture</label>
                                <input id="profilePicture" type="file" class="form-control form-control-sm" name="profile_picture" accept="image/jpeg,image/png,image/webp" required>
                                <button type="submit" class="btn btn-sm btn-primary w-100 mt-2">Upload Picture</button>
                            </form>
                            <?php endif; ?>
                            <?php if (!empty($_SESSION['employee_flash'])): ?>
                                <div class="alert alert-success py-2"><?php echo htmlspecialchars($_SESSION['employee_flash']); ?></div>
                                <?php unset($_SESSION['employee_flash']); ?>
                            <?php endif; ?>
                            <h5 class="fw-bold mb-1"><?php echo htmlspecialchars(trim((($employee['first_name'] ?? '') . ' ' . ($employee['last_name'] ?? '')))); ?></h5>
                            <div class="text-muted"><?php echo htmlspecialchars($employee['position'] ?? 'N/A'); ?></div>
                            <span class="badge bg-<?php echo strtolower((string)($employee['status'] ?? 'active')) === 'active' ? 'success' : 'secondary'; ?> mt-3">
                                <?php echo htmlspecialchars(ucfirst((string)($employee['status'] ?? 'active'))); ?>
                            </span>
                            <?php $currentRole = strtolower(trim((string)($_SESSION['user']['role_name'] ?? ''))); ?>
                            <?php if (in_array($currentRole, ['super admin', 'superadministrator', 'super administrator'], true) && strtolower((string)($employee['status'] ?? '')) === 'active'): ?>
                                <form action="/ERP/public/management/employees/login-as" method="post" class="mt-3">
                                    <input type="hidden" name="employee_id" value="<?php echo (int)$employee['id']; ?>">
                                    <button type="submit" class="btn btn-outline-dark w-100" onclick="return confirm('Open this employee account without entering a password?');">Login as Employee</button>
                                </form>
                            <?php endif; ?>
                            <?php if (in_array($currentRole, ['super admin', 'superadministrator', 'super administrator'], true)): ?>
                                <form action="/ERP/public/management/employees/change-password" method="post" class="text-start border-top mt-3 pt-3">
                                    <input type="hidden" name="employee_id" value="<?php echo (int)$employee['id']; ?>">
                                    <div class="small fw-semibold mb-2">Change login password</div>
                                    <label class="form-label small" for="newEmployeePassword">New password</label>
                                    <input id="newEmployeePassword" type="password" class="form-control form-control-sm mb-2" name="new_password" minlength="8" required>
                                    <label class="form-label small" for="confirmEmployeePassword">Confirm password</label>
                                    <input id="confirmEmployeePassword" type="password" class="form-control form-control-sm" name="confirm_password" minlength="8" required>
                                    <button type="submit" class="btn btn-sm btn-outline-warning w-100 mt-2">Change Password</button>
                                </form>
                            <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <form action="/ERP/public/management/employees/update" method="post">
                <input type="hidden" name="employee_id" value="<?php echo (int)$employee['id']; ?>">
                <div class="row g-3">
                    <?php
                    $editableFields = [
                        'employee_code' => 'Employee Code', 'first_name' => 'First Name', 'last_name' => 'Last Name',
                        'email' => 'Email', 'phone' => 'Phone', 'department' => 'Department', 'position' => 'Position',
                        'designation' => 'Designation', 'hire_date' => 'Hire Date', 'salary' => 'Salary', 'nin' => 'NIN',
                        'account_number' => 'Account Number', 'account_name' => 'Account Name', 'bank_name' => 'Bank Name', 'tin' => 'TIN', 'pfa' => 'PFA',
                    ];
                    foreach ($editableFields as $fieldName => $fieldLabel):
                        if (!isset($visibleColumnKeys[$fieldName])) {
                            continue;
                        }
                        $fieldType = $fieldName === 'hire_date' ? 'date' : ($fieldName === 'salary' ? 'number' : ($fieldName === 'email' ? 'email' : 'text'));
                        $fieldValue = $employee[$fieldName] ?? '';
                    ?>
                        <div class="col-md-6">
                            <label class="form-label" for="<?php echo htmlspecialchars($fieldName); ?>"><?php echo htmlspecialchars($fieldLabel); ?></label>
                            <input id="<?php echo htmlspecialchars($fieldName); ?>" type="<?php echo $fieldType; ?>" class="form-control" name="<?php echo htmlspecialchars($fieldName); ?>" value="<?php echo htmlspecialchars((string)$fieldValue); ?>" <?php echo $fieldName === 'salary' ? 'step="0.01" min="0"' : ''; ?> <?php echo in_array($fieldName, ['employee_code', 'first_name', 'last_name', 'email', 'phone', 'department', 'position', 'hire_date'], true) ? 'required' : ''; ?>>
                        </div>
                    <?php endforeach; ?>
                    <div class="col-md-6">
                        <label class="form-label" for="status">Status</label>
                        <select id="status" class="form-select" name="status">
                            <?php foreach (['active' => 'Active', 'inactive' => 'Inactive', 'terminated' => 'Terminated'] as $statusValue => $statusLabel): ?>
                                <option value="<?php echo $statusValue; ?>" <?php echo ($employee['status'] ?? '') === $statusValue ? 'selected' : ''; ?>><?php echo $statusLabel; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php foreach (($customFields ?? []) as $customField): ?>
                        <div class="col-md-6">
                            <label class="form-label" for="custom_<?php echo (int)$customField['id']; ?>"><?php echo htmlspecialchars($customField['field_name']); ?></label>
                            <input id="custom_<?php echo (int)$customField['id']; ?>" class="form-control" name="custom_fields[<?php echo (int)$customField['id']; ?>]" value="<?php echo htmlspecialchars((string)($customField['field_value'] ?? '')); ?>">
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="submit" class="btn btn-primary mt-4">Save Employee Changes</button>
            </form>
        </div>
    </div>
<?php endif; ?>
