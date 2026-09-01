<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold">Salary Structures</h3>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStructureModal">+ Add Structure</button>
    </div>

    <?php if (!empty($_SESSION['payroll_flash'])): ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_SESSION['payroll_flash']); unset($_SESSION['payroll_flash']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="alert alert-light border mb-3">
        <strong>Access Control:</strong> Salary structure updates are controlled by role-based and employee-based permission settings under the payroll module access configuration.
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0">Role-Based Structure Templates</h5>
                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addRoleStructureModal">+ Add Role Template</button>
            </div>
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Role</th>
                            <th>Basic Salary</th>
                            <th>Total Allowances</th>
                            <th>Gross Salary</th>
                            <th>Tax Rate</th>
                            <th>Pension Rate</th>
                            <th>Effective From</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($roleStructures)): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">No role salary templates configured.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($roleStructures as $roleStructure): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($roleStructure['role_name'] ?? 'Role'); ?></strong></td>
                                    <td>₦<?php echo number_format((float)$roleStructure['basic_salary'], 2); ?></td>
                                    <td>₦<?php echo number_format((float)($roleStructure['house_allowance'] + $roleStructure['transport_allowance'] + $roleStructure['meal_allowance'] + $roleStructure['other_allowances']), 2); ?></td>
                                    <td><strong>₦<?php echo number_format((float)($roleStructure['basic_salary'] + $roleStructure['house_allowance'] + $roleStructure['transport_allowance'] + $roleStructure['meal_allowance'] + $roleStructure['other_allowances']), 2); ?></strong></td>
                                    <td><?php echo number_format((float)$roleStructure['tax_rate'], 2); ?>%</td>
                                    <td><?php echo number_format((float)$roleStructure['pension_rate'], 2); ?>%</td>
                                    <td><?php echo htmlspecialchars($roleStructure['effective_from']); ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editRoleStructureModal-<?php echo (int)$roleStructure['id']; ?>">Edit</button>
                                    </td>
                                </tr>

                                <div class="modal fade" id="editRoleStructureModal-<?php echo (int)$roleStructure['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Edit Role Salary Structure - <?php echo htmlspecialchars($roleStructure['role_name'] ?? 'Role'); ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form method="post" action="/ERP/public/management/salary-structures/save">
                                                <div class="modal-body">
                                                    <input type="hidden" name="role_id" value="<?php echo (int)$roleStructure['role_id']; ?>">
                                                    <div class="row g-3">
                                                        <div class="col-md-6"><label class="form-label">Basic Salary</label><input type="number" step="0.01" class="form-control" name="basic_salary" value="<?php echo number_format((float)$roleStructure['basic_salary'], 2, '.', ''); ?>" required></div>
                                                        <div class="col-md-6"><label class="form-label">House Allowance</label><input type="number" step="0.01" class="form-control" name="house_allowance" value="<?php echo number_format((float)$roleStructure['house_allowance'], 2, '.', ''); ?>"></div>
                                                        <div class="col-md-6"><label class="form-label">Transport Allowance</label><input type="number" step="0.01" class="form-control" name="transport_allowance" value="<?php echo number_format((float)$roleStructure['transport_allowance'], 2, '.', ''); ?>"></div>
                                                        <div class="col-md-6"><label class="form-label">Meal Allowance</label><input type="number" step="0.01" class="form-control" name="meal_allowance" value="<?php echo number_format((float)$roleStructure['meal_allowance'], 2, '.', ''); ?>"></div>
                                                        <div class="col-md-6"><label class="form-label">Other Allowances</label><input type="number" step="0.01" class="form-control" name="other_allowances" value="<?php echo number_format((float)$roleStructure['other_allowances'], 2, '.', ''); ?>"></div>
                                                        <div class="col-md-6"><label class="form-label">Income Tax Rate (%)</label><input type="number" step="0.01" class="form-control" name="tax_rate" value="<?php echo number_format((float)$roleStructure['tax_rate'], 2, '.', ''); ?>"></div>
                                                        <div class="col-md-6"><label class="form-label">Pension Rate (%)</label><input type="number" step="0.01" class="form-control" name="pension_rate" value="<?php echo number_format((float)$roleStructure['pension_rate'], 2, '.', ''); ?>"></div>
                                                        <div class="col-md-6"><label class="form-label">Insurance Deduction</label><input type="number" step="0.01" class="form-control" name="insurance_deduction" value="<?php echo number_format((float)$roleStructure['insurance_deduction'], 2, '.', ''); ?>"></div>
                                                        <div class="col-md-6"><label class="form-label">Effective From</label><input type="date" class="form-control" name="effective_from" value="<?php echo htmlspecialchars($roleStructure['effective_from']); ?>"></div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-primary">Update Role Structure</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0">Individual Employee Structures</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Position</th>
                            <th>Basic Salary</th>
                            <th>Total Allowances</th>
                            <th>Gross Salary</th>
                            <th>Tax Rate</th>
                            <th>Pension Rate</th>
                            <th>Effective From</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($structures)): ?>
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">No salary structures configured.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($structures as $structure): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($structure['first_name'] . ' ' . $structure['last_name']); ?></strong>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($structure['employee_code']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($structure['position'] ?? 'N/A'); ?></td>
                                    <td>₦<?php echo number_format((float)$structure['basic_salary'], 2); ?></td>
                                    <td>₦<?php echo number_format((float)($structure['house_allowance'] + $structure['transport_allowance'] + $structure['meal_allowance'] + $structure['other_allowances']), 2); ?></td>
                                    <td><strong>₦<?php echo number_format((float)($structure['basic_salary'] + $structure['house_allowance'] + $structure['transport_allowance'] + $structure['meal_allowance'] + $structure['other_allowances']), 2); ?></strong></td>
                                    <td><?php echo number_format((float)$structure['tax_rate'], 2); ?>%</td>
                                    <td><?php echo number_format((float)$structure['pension_rate'], 2); ?>%</td>
                                    <td><?php echo htmlspecialchars($structure['effective_from']); ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editStructureModal-<?php echo (int)$structure['id']; ?>">Edit</button>
                                    </td>
                                </tr>

                                <!-- Edit Modal -->
                                <div class="modal fade" id="editStructureModal-<?php echo (int)$structure['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Edit Salary Structure - <?php echo htmlspecialchars($structure['first_name'] . ' ' . $structure['last_name']); ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form method="post" action="/ERP/public/management/salary-structures/save">
                                                <div class="modal-body">
                                                    <input type="hidden" name="employee_id" value="<?php echo (int)$structure['id']; ?>">
                                                    <div class="row g-3">
                                                        <div class="col-md-6">
                                                            <label class="form-label">Basic Salary</label>
                                                            <input type="number" step="0.01" class="form-control" name="basic_salary" value="<?php echo number_format((float)$structure['basic_salary'], 2); ?>" required>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label">House Allowance</label>
                                                            <input type="number" step="0.01" class="form-control" name="house_allowance" value="<?php echo number_format((float)$structure['house_allowance'], 2); ?>">
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label">Transport Allowance</label>
                                                            <input type="number" step="0.01" class="form-control" name="transport_allowance" value="<?php echo number_format((float)$structure['transport_allowance'], 2); ?>">
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label">Meal Allowance</label>
                                                            <input type="number" step="0.01" class="form-control" name="meal_allowance" value="<?php echo number_format((float)$structure['meal_allowance'], 2); ?>">
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label">Other Allowances</label>
                                                            <input type="number" step="0.01" class="form-control" name="other_allowances" value="<?php echo number_format((float)$structure['other_allowances'], 2); ?>">
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label">Income Tax Rate (%)</label>
                                                            <input type="number" step="0.01" class="form-control" name="tax_rate" value="<?php echo number_format((float)$structure['tax_rate'], 2); ?>">
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label">Pension Rate (%)</label>
                                                            <input type="number" step="0.01" class="form-control" name="pension_rate" value="<?php echo number_format((float)$structure['pension_rate'], 2); ?>">
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label">Insurance Deduction</label>
                                                            <input type="number" step="0.01" class="form-control" name="insurance_deduction" value="<?php echo number_format((float)$structure['insurance_deduction'], 2); ?>">
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label">Effective From</label>
                                                            <input type="date" class="form-control" name="effective_from" value="<?php echo htmlspecialchars($structure['effective_from']); ?>">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-primary">Update Structure</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Structure Modal -->
<div class="modal fade" id="addStructureModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Employee Salary Structure</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" action="/ERP/public/management/salary-structures/save">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">Employee</label>
                            <select class="form-select" name="employee_id" required>
                                <option value="">Select employee</option>
                                <?php foreach ($employees as $employee): ?>
                                    <option value="<?php echo (int)$employee['id']; ?>">
                                        <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name'] . ' (' . ($employee['position'] ?? 'Employee') . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Basic Salary</label>
                            <input type="number" step="0.01" class="form-control" name="basic_salary" value="0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">House Allowance</label>
                            <input type="number" step="0.01" class="form-control" name="house_allowance" value="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Transport Allowance</label>
                            <input type="number" step="0.01" class="form-control" name="transport_allowance" value="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Meal Allowance</label>
                            <input type="number" step="0.01" class="form-control" name="meal_allowance" value="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Other Allowances</label>
                            <input type="number" step="0.01" class="form-control" name="other_allowances" value="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Income Tax Rate (%)</label>
                            <input type="number" step="0.01" class="form-control" name="tax_rate" value="10">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Pension Rate (%)</label>
                            <input type="number" step="0.01" class="form-control" name="pension_rate" value="8">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Insurance Deduction</label>
                            <input type="number" step="0.01" class="form-control" name="insurance_deduction" value="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Effective From</label>
                            <input type="date" class="form-control" name="effective_from" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Structure</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="addRoleStructureModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Role Salary Structure</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" action="/ERP/public/management/salary-structures/save">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">Role</label>
                            <select class="form-select" name="role_id" required>
                                <option value="">Select role</option>
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?php echo (int)$role['id']; ?>"><?php echo htmlspecialchars($role['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6"><label class="form-label">Basic Salary</label><input type="number" step="0.01" class="form-control" name="basic_salary" value="0" required></div>
                        <div class="col-md-6"><label class="form-label">House Allowance</label><input type="number" step="0.01" class="form-control" name="house_allowance" value="0"></div>
                        <div class="col-md-6"><label class="form-label">Transport Allowance</label><input type="number" step="0.01" class="form-control" name="transport_allowance" value="0"></div>
                        <div class="col-md-6"><label class="form-label">Meal Allowance</label><input type="number" step="0.01" class="form-control" name="meal_allowance" value="0"></div>
                        <div class="col-md-6"><label class="form-label">Other Allowances</label><input type="number" step="0.01" class="form-control" name="other_allowances" value="0"></div>
                        <div class="col-md-6"><label class="form-label">Income Tax Rate (%)</label><input type="number" step="0.01" class="form-control" name="tax_rate" value="10"></div>
                        <div class="col-md-6"><label class="form-label">Pension Rate (%)</label><input type="number" step="0.01" class="form-control" name="pension_rate" value="8"></div>
                        <div class="col-md-6"><label class="form-label">Insurance Deduction</label><input type="number" step="0.01" class="form-control" name="insurance_deduction" value="0"></div>
                        <div class="col-md-6"><label class="form-label">Effective From</label><input type="date" class="form-control" name="effective_from" value="<?php echo date('Y-m-d'); ?>"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Role Template</button>
                </div>
            </form>
        </div>
    </div>
</div>
