<?php
$employees = $employees ?? [];
$modules = $modules ?? [];
$access = $access ?? [];
$departmentGroups = [];
foreach ($employees as $employee) {
    $department = trim((string)($employee['department'] ?? '')) ?: 'Unassigned Department';
    $departmentGroups[$department][] = $employee;
}
ksort($departmentGroups);
?>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div><p class="text-muted mb-1">Administration</p><h2 class="fw-bold mb-1">Department Staff Module Access</h2><p class="text-muted mb-0">Choose which company modules each staff role can open.</p></div>
    <a class="btn btn-outline-secondary" href="/ERP/public/management/employees"><i class="bi bi-arrow-left me-1"></i>Back to Employees</a>
</div>
<?php if (!empty($_SESSION['employee_flash'])): ?><div class="alert alert-info"><?php echo htmlspecialchars($_SESSION['employee_flash']); unset($_SESSION['employee_flash']); ?></div><?php endif; ?>
<div class="alert alert-light border"><i class="bi bi-info-circle me-2"></i>HR leadership and the top organogram role can manage all departments. Department heads can manage roles below them in their department. Staff without a saved selection inherit the company’s enabled modules.</div>
<form method="post" action="/ERP/public/management/module-access/save">
    <?php if ($departmentGroups === []): ?><div class="card shadow-sm border-0"><div class="card-body text-muted">No staff members are available for this company.</div></div><?php endif; ?>
    <?php foreach ($departmentGroups as $department => $departmentEmployees): ?>
        <div class="card shadow-sm border-0 mb-4"><div class="card-body p-4"><div class="d-flex justify-content-between align-items-center mb-3"><h5 class="fw-bold mb-0"><i class="bi bi-diagram-3 me-2"></i><?php echo htmlspecialchars($department); ?></h5><span class="badge text-bg-light"><?php echo count($departmentEmployees); ?> staff</span></div>
            <div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th style="min-width: 190px">Staff member</th><th>Role</th><?php foreach ($modules as $moduleKey => $moduleLabel): ?><th class="text-center" style="min-width: 120px"><?php echo htmlspecialchars($moduleLabel); ?></th><?php endforeach; ?></tr></thead><tbody>
            <?php foreach ($departmentEmployees as $employee): $employeeId = (int)$employee['id']; $hasExplicitAccess = array_key_exists($employeeId, $access); ?>
                <tr><td><div class="fw-semibold"><?php echo htmlspecialchars(trim(($employee['first_name'] ?? '') . ' ' . ($employee['last_name'] ?? ''))); ?></div><small class="text-muted"><?php echo htmlspecialchars($employee['employee_code'] ?? ''); ?><?php if ($hasExplicitAccess): ?> · Explicit access<?php else: ?> · Inherits company access<?php endif; ?></small></td><td><?php echo htmlspecialchars($employee['role_name'] ?? 'No login role'); ?></td>
                <?php echo '<input type="hidden" name="employee_modules[' . $employeeId . '][]" value="">'; foreach ($modules as $moduleKey => $moduleLabel): ?><td class="text-center"><label class="form-check d-inline-flex justify-content-center"><input class="form-check-input" type="checkbox" name="employee_modules[<?php echo $employeeId; ?>][]" value="<?php echo htmlspecialchars($moduleKey); ?>" <?php echo in_array($moduleKey, $access[$employeeId] ?? [], true) ? 'checked' : ''; ?>><span class="visually-hidden"><?php echo htmlspecialchars($moduleLabel); ?> for <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></span></label></td><?php endforeach; ?></tr>
            <?php endforeach; ?></tbody></table></div>
        </div></div>
    <?php endforeach; ?>
    <?php if ($departmentGroups !== []): ?><button class="btn btn-primary" type="submit"><i class="bi bi-save me-2"></i>Save Staff Module Access</button><?php endif; ?>
</form>
