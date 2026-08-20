<?php $visibleColumnKeys = array_fill_keys(array_column(($employeeColumns ?? []), 'key'), true); ?>
<div class="card shadow-sm border-0">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="fw-bold">Employee Management</h4>
                <p class="text-muted mb-0">Staff records, departments, and employment status.</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a class="btn btn-outline-secondary" href="/ERP/public/management/employees/template" download>Download Template</a>
                <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#uploadEmployeeModal">Upload Employees</button>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEmployeeModal">Add Employee</button>
                <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addEmployeeColumnModal">Add Data Column</button>
                <?php if (!empty($employeeColumns)): ?>
                    <button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteEmployeeColumnModal">Delete Data Column</button>
                <?php endif; ?>
                <form method="post" action="/ERP/public/management/employees/create-credentials" style="display:inline-block;">
                    <input type="hidden" name="form_action" value="create_missing_credentials">
                    <button type="submit" class="btn btn-warning">Create Missing Credentials</button>
                </form>
                <a class="btn btn-outline-dark" href="/ERP/public/management/employees/archive">View Archive</a>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-lg-6">
                <form class="d-flex" method="get" action="/ERP/public/management/employees">
                    <input class="form-control me-2" type="search" name="search" placeholder="Search employees" value="<?php echo htmlspecialchars($search ?? ''); ?>">
                    <button class="btn btn-outline-secondary" type="submit">Search</button>
                </form>
            </div>
        </div>

        <?php if (!empty($_SESSION['employee_flash'])): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['employee_flash']); ?></div>
            <?php unset($_SESSION['employee_flash']); ?>
        <?php endif; ?>

        <form method="post" action="/ERP/public/management/employees/deactivate" id="bulkEmployeesForm">
            <div class="mb-2 d-flex gap-2 flex-wrap">
                <button type="submit" name="action" value="deactivate" class="btn btn-sm btn-danger">Deactivate Selected</button>
                <button type="submit" name="action" value="reactivate" class="btn btn-sm btn-success">Reactivate Selected</button>
                <button type="submit" name="action" value="delete" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete selected employees and their accounts? This cannot be undone.');">Delete Selected</button>
            </div>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAllEmployeesList"></th>
                        <th>Employee Code</th>
                        <th>Name</th>
                        <th>Department</th>
                        <th>Designation</th>
                        <th>Position</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($employees)): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No employees found yet. Add a new employee to begin.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($employees as $employee): ?>
                            <tr>
                                <td><input type="checkbox" name="employee_ids[]" value="<?php echo (int)$employee['id']; ?>" class="selectEmployeeList"></td>
                                <td><?php echo htmlspecialchars($employee['employee_code'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars(trim(($employee['first_name'] ?? '') . ' ' . ($employee['last_name'] ?? ''))); ?></td>
                                <td><?php echo htmlspecialchars($employee['department'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($employee['designation'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($employee['position'] ?? ''); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo strtolower($employee['status'] ?? 'active') === 'active' ? 'success' : 'secondary'; ?>">
                                        <?php echo htmlspecialchars(ucfirst($employee['status'] ?? 'Active')); ?>
                                    </span>
                                </td>
                                <td>
                                    <a class="btn btn-sm btn-outline-primary" href="/ERP/public/management/employees/view?id=<?php echo (int)$employee['id']; ?>">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </form>
    </div>
</div>

<div class="modal fade" id="addEmployeeColumnModal" tabindex="-1" aria-labelledby="addEmployeeColumnModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addEmployeeColumnModalLabel">Add Employee Data Column</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="/ERP/public/management/employees/custom-field" method="post">
                <div class="modal-body">
                    <label class="form-label" for="employeeColumnName">Column Name</label>
                    <input id="employeeColumnName" class="form-control" name="field_name" maxlength="100" placeholder="e.g. Emergency Contact" required>
                    <div class="form-text">This column will be added for all employees. Existing employees will start with a blank value.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Column</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if (!empty($employeeColumns)): ?>
<div class="modal fade" id="deleteEmployeeColumnModal" tabindex="-1" aria-labelledby="deleteEmployeeColumnModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteEmployeeColumnModalLabel">Delete Employee Data Column</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="/ERP/public/management/employees/custom-field/delete" method="post" onsubmit="return confirm('Delete this column and all values saved under it? This cannot be undone.');">
                <div class="modal-body">
                    <label class="form-label" for="employeeColumnToDelete">Column</label>
                    <select id="employeeColumnToDelete" class="form-select" name="column_key" required>
                        <?php foreach ($employeeColumns as $employeeColumn): ?>
                            <option value="<?php echo htmlspecialchars($employeeColumn['key']); ?>"><?php echo htmlspecialchars($employeeColumn['label']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text text-danger">This disables the column for the current company. Stored values are preserved.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Column</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="modal fade" id="uploadEmployeeModal" tabindex="-1" aria-labelledby="uploadEmployeeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="uploadEmployeeModalLabel">Upload Employees</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="/ERP/public/management/employees/upload" enctype="multipart/form-data">
                <div class="modal-body">
                    <p class="text-muted">Use the downloaded template. Keep the column headings unchanged and save it as the supplied tab-separated .xls file or as CSV.</p>
                    <label class="form-label" for="employee_file">Employee template</label>
                    <input class="form-control" type="file" id="employee_file" name="employee_file" accept=".xls,.csv,text/csv,application/vnd.ms-excel" required>
                    <div class="form-text">Existing employees with the same email are skipped. New login credentials use the Role and Password columns.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Upload Employees</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="addEmployeeModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Employee</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="post" action="/ERP/public/management/employees/save" enctype="multipart/form-data">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Employee Code</label>
                            <input class="form-control" name="employee_code" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">First Name</label>
                            <input class="form-control" name="first_name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Last Name</label>
                            <input class="form-control" name="last_name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Login Role</label>
                            <input class="form-control" name="role" value="Staff" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Login Password</label>
                            <input type="password" class="form-control" name="password" minlength="8" required>
                            <div class="form-text">Minimum 8 characters.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input class="form-control" name="phone" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Department</label>
                            <select class="form-select" name="department" required>
                                <option value="">Select department</option>
                                <?php if (!empty($departments) && is_array($departments)): ?>
                                    <?php foreach ($departments as $department): ?>
                                        <option value="<?php echo htmlspecialchars($department['name']); ?>"><?php echo htmlspecialchars($department['name']); ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Position</label>
                            <input class="form-control" name="position" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Designation</label>
                            <input class="form-control" name="designation" placeholder="Enter designation or job title">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Hire Date</label>
                            <input type="date" class="form-control" name="hire_date" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Salary</label>
                            <input type="number" step="0.01" min="0" class="form-control" name="salary" value="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <option value="active" selected>Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="terminated">Terminated</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Profile Picture</label>
                            <input type="file" class="form-control" name="profile_picture" accept="image/jpeg,image/png,image/webp">
                        </div>
                        <?php if (isset($visibleColumnKeys['nin'])): ?><div class="col-md-6">
                            <label class="form-label">NIN</label>
                            <input class="form-control" name="nin" maxlength="50">
                        </div><?php endif; ?>
                        <?php if (isset($visibleColumnKeys['account_number'])): ?><div class="col-md-6">
                            <label class="form-label">Account Number</label>
                            <input class="form-control" name="account_number" maxlength="50">
                        </div><?php endif; ?>
                        <?php if (isset($visibleColumnKeys['account_name'])): ?><div class="col-md-6">
                            <label class="form-label">Account Name</label>
                            <input class="form-control" name="account_name" maxlength="150">
                        </div><?php endif; ?>
                        <?php if (isset($visibleColumnKeys['bank_name'])): ?><div class="col-md-6">
                            <label class="form-label">Bank Name</label>
                            <input class="form-control" name="bank_name" maxlength="150">
                        </div><?php endif; ?>
                        <?php if (isset($visibleColumnKeys['tin'])): ?><div class="col-md-6">
                            <label class="form-label">TIN</label>
                            <input class="form-control" name="tin" maxlength="50">
                        </div><?php endif; ?>
                        <?php if (isset($visibleColumnKeys['pfa'])): ?><div class="col-md-6">
                            <label class="form-label">PFA</label>
                            <input class="form-control" name="pfa" maxlength="150">
                        </div><?php endif; ?>
                        <?php if (!empty($customFields)): ?>
                            <?php foreach ($customFields as $customField): ?>
                                <div class="col-md-6">
                                    <label class="form-label"><?php echo htmlspecialchars($customField['field_name']); ?></label>
                                    <input class="form-control" name="custom_fields[<?php echo (int)$customField['id']; ?>]">
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Employee</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const selectAll = document.getElementById('selectAllEmployeesList');
        const employeeCheckboxes = Array.from(document.querySelectorAll('.selectEmployeeList'));

        if (!selectAll || employeeCheckboxes.length === 0) {
            return;
        }

        const updateSelectAllState = function () {
            const selectedCount = employeeCheckboxes.filter(function (checkbox) {
                return checkbox.checked;
            }).length;
            selectAll.checked = selectedCount === employeeCheckboxes.length;
            selectAll.indeterminate = selectedCount > 0 && selectedCount < employeeCheckboxes.length;
        };

        selectAll.addEventListener('change', function () {
            employeeCheckboxes.forEach(function (checkbox) {
                checkbox.checked = selectAll.checked;
            });
            selectAll.indeterminate = false;
        });

        employeeCheckboxes.forEach(function (checkbox) {
            checkbox.addEventListener('change', updateSelectAllState);
        });
    });
</script>
