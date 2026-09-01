<?php
$modules = $modules ?? [];
$access = $access ?? [];
$departmentGroups = $departmentGroups ?? [];
$unassignedRoles = $unassignedRoles ?? [];
?>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div><p class="text-muted mb-1">Administration</p><h2 class="fw-bold mb-1">Role Module Access</h2><p class="text-muted mb-0">Choose which company modules each department role can open.</p></div>
    <a class="btn btn-outline-secondary" href="/ERP/public/management/hr"><i class="bi bi-arrow-left me-1"></i>Back to HR</a>
</div>
<?php if (!empty($_SESSION['employee_flash'])): ?><div class="alert alert-info"><?php echo htmlspecialchars($_SESSION['employee_flash']); unset($_SESSION['employee_flash']); ?></div><?php endif; ?>
<div class="alert alert-light border"><i class="bi bi-info-circle me-2"></i>Roles assigned to a department are shown below. Roles without a department assignment are listed separately so you can still decide the modules they should access.</div>
<form method="post" action="/ERP/public/management/module-access/save">
    <?php if ($departmentGroups === [] && $unassignedRoles === []): ?><div class="card shadow-sm border-0"><div class="card-body text-muted">No roles are available for this company yet.</div></div><?php endif; ?>
    <?php foreach ($departmentGroups as $departmentGroup): $departmentName = $departmentGroup['name'] ?? 'Department'; $roles = $departmentGroup['roles'] ?? []; ?>
        <div class="card shadow-sm border-0 mb-4"><div class="card-body p-4"><div class="d-flex justify-content-between align-items-center mb-3"><h5 class="fw-bold mb-0"><i class="bi bi-diagram-3 me-2"></i><?php echo htmlspecialchars($departmentName); ?></h5><span class="badge text-bg-light"><?php echo count($roles); ?> roles</span></div>
            <div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th style="min-width: 220px">Role</th><?php foreach ($modules as $moduleKey => $moduleLabel): ?><th class="text-center" style="min-width: 120px"><?php echo htmlspecialchars($moduleLabel); ?></th><?php endforeach; ?></tr></thead><tbody>
            <?php foreach ($roles as $role): $roleId = (int)($role['id'] ?? 0); ?>
                <tr><td><div class="fw-semibold"><?php echo htmlspecialchars((string)($role['name'] ?? 'Role')); ?></div><small class="text-muted"><?php echo $roleId > 0 ? 'Role ID ' . $roleId : 'No role ID'; ?></small></td>
                <?php echo '<input type="hidden" name="role_modules[' . $roleId . '][]" value="">'; foreach ($modules as $moduleKey => $moduleLabel): ?><td class="text-center"><label class="form-check d-inline-flex justify-content-center"><input class="form-check-input" type="checkbox" name="role_modules[<?php echo $roleId; ?>][]" value="<?php echo htmlspecialchars($moduleKey); ?>" <?php echo in_array($moduleKey, $access[$roleId] ?? [], true) ? 'checked' : ''; ?>><span class="visually-hidden"><?php echo htmlspecialchars($moduleLabel); ?> for <?php echo htmlspecialchars((string)($role['name'] ?? 'role')); ?></span></label></td><?php endforeach; ?></tr>
            <?php endforeach; ?></tbody></table></div>
        </div></div>
    <?php endforeach; ?>
    <?php if ($unassignedRoles !== []): ?>
        <div class="card shadow-sm border-0 mb-4"><div class="card-body p-4"><div class="d-flex justify-content-between align-items-center mb-3"><h5 class="fw-bold mb-0"><i class="bi bi-slash-circle me-2"></i>Roles not assigned to a department</h5><span class="badge text-bg-light"><?php echo count($unassignedRoles); ?> roles</span></div>
            <div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th style="min-width: 220px">Role</th><?php foreach ($modules as $moduleKey => $moduleLabel): ?><th class="text-center" style="min-width: 120px"><?php echo htmlspecialchars($moduleLabel); ?></th><?php endforeach; ?></tr></thead><tbody>
            <?php foreach ($unassignedRoles as $role): $roleId = (int)($role['id'] ?? 0); ?>
                <tr><td><div class="fw-semibold"><?php echo htmlspecialchars((string)($role['name'] ?? 'Role')); ?></div><small class="text-muted"><?php echo $roleId > 0 ? 'Role ID ' . $roleId : 'No role ID'; ?></small></td>
                <?php echo '<input type="hidden" name="role_modules[' . $roleId . '][]" value="">'; foreach ($modules as $moduleKey => $moduleLabel): ?><td class="text-center"><label class="form-check d-inline-flex justify-content-center"><input class="form-check-input" type="checkbox" name="role_modules[<?php echo $roleId; ?>][]" value="<?php echo htmlspecialchars($moduleKey); ?>" <?php echo in_array($moduleKey, $access[$roleId] ?? [], true) ? 'checked' : ''; ?>><span class="visually-hidden"><?php echo htmlspecialchars($moduleLabel); ?> for <?php echo htmlspecialchars((string)($role['name'] ?? 'role')); ?></span></label></td><?php endforeach; ?></tr>
            <?php endforeach; ?></tbody></table></div>
        </div></div>
    <?php endif; ?>
    <?php if ($departmentGroups !== [] || $unassignedRoles !== []): ?><button class="btn btn-primary" type="submit"><i class="bi bi-save me-2"></i>Save Role Module Access</button><?php endif; ?>
</form>
