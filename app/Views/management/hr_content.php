<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Human Resources</h4>
        <p class="text-muted mb-0">Manage departments, roles, and department heads.</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createRoleModal">Create Role</button>
        <a class="btn btn-outline-secondary" href="#roleManagement">Manage Roles</a>
    </div>
</div>
<?php if (!empty($_SESSION['hr_flash'])): ?>
    <div class="alert alert-info"><?php echo htmlspecialchars($_SESSION['hr_flash']); unset($_SESSION['hr_flash']); ?></div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h5 class="fw-bold mb-3">Create Department</h5>
                <form method="post" action="/ERP/public/management/departments/save">
                    <label class="form-label" for="departmentName">Department Name</label>
                    <input id="departmentName" class="form-control mb-3" name="name" maxlength="100" required>
                    <button class="btn btn-primary" type="submit">Create Department</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h5 class="fw-bold mb-3">Departments</h5>
                <?php if (empty($departments)): ?>
                    <p class="text-muted mb-0">No departments have been created yet.</p>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($departments as $department): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <strong><?php echo htmlspecialchars($department['name']); ?></strong>
                                    <form method="post" action="/ERP/public/management/departments/delete" onsubmit="return confirm('Delete this department? Employees assigned to it will have a blank department until updated.');">
                                        <input type="hidden" name="department_id" value="<?php echo (int)$department['id']; ?>">
                                        <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                                    </form>
                                </div>
                                <form method="post" action="/ERP/public/management/departments/update-assignments">
                                    <input type="hidden" name="department_id" value="<?php echo (int)$department['id']; ?>">
                                    <div class="row g-3">
                                        <div class="col-md-7">
                                            <label class="form-label">Assigned roles</label>
                                            <div class="border rounded p-2">
                                                <?php foreach (($roles ?? []) as $role): ?>
                                                    <label class="form-check"><input class="form-check-input department-role" type="checkbox" name="role_ids[]" value="<?php echo (int)$role['id']; ?>" data-department="<?php echo (int)$department['id']; ?>" <?php echo in_array((int)$role['id'], $department['role_ids'] ?? [], true) ? 'checked' : ''; ?>><span class="form-check-label"><?php echo htmlspecialchars($role['name']); ?></span></label>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label" for="head-role-<?php echo (int)$department['id']; ?>">Department Head</label>
                                            <select id="head-role-<?php echo (int)$department['id']; ?>" class="form-select head-role" name="head_role_id" data-department="<?php echo (int)$department['id']; ?>">
                                                <option value="">Choose assigned role</option>
                                                <?php foreach (($roles ?? []) as $role): ?>
                                                    <option value="<?php echo (int)$role['id']; ?>" <?php echo in_array((int)$role['id'], $department['role_ids'] ?? [], true) ? '' : 'hidden'; ?> <?php echo (int)($department['head_role_id'] ?? $department['role_id'] ?? 0) === (int)$role['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($role['name']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-2 d-flex align-items-end"><button class="btn btn-outline-primary w-100" type="submit">Save</button></div>
                                    </div>
                                </form>
                                <?php if (!empty($department['role_ids']) || !empty($department['head_title'])): ?>
                                    <div class="small text-muted mt-2">
                                        Roles assigned: <?php echo count($department['role_ids'] ?? []); ?>
                                        | Head: <?php echo htmlspecialchars($department['role_name'] ?? 'None'); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.department-role').forEach((checkbox) => {
    checkbox.addEventListener('change', () => {
        const departmentId = checkbox.dataset.department;
        const head = document.querySelector(`.head-role[data-department="${departmentId}"]`);
        const selected = [...document.querySelectorAll(`.department-role[data-department="${departmentId}"]:checked`)].map((item) => item.value);
        [...head.options].forEach((option) => { option.hidden = option.value !== '' && !selected.includes(option.value); });
        if (head.value !== '' && !selected.includes(head.value)) head.value = '';
    });
});
</script>
<div class="row g-4 mt-1" id="roleManagement">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h5 class="fw-bold mb-3">Roles</h5>
                <div class="list-group">
                    <?php foreach (($roles ?? []) as $role): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <span><?php echo htmlspecialchars($role['name']); ?></span>
                            <form method="post" action="/ERP/public/management/roles/delete" onsubmit="return confirm('Delete this role?');">
                                <input type="hidden" name="role_id" value="<?php echo (int)$role['id']; ?>">
                                <button class="btn btn-sm btn-outline-danger" type="submit">Delete Role</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h5 class="fw-bold mb-3">Management Roles</h5>
                <div class="list-group">
                    <?php foreach (($managementRoles ?? []) as $managementRole): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <span><?php echo htmlspecialchars($managementRole['name']); ?></span>
                            <form method="post" action="/ERP/public/management/management-roles/delete" onsubmit="return confirm('Delete this management role?');">
                                <input type="hidden" name="management_role_id" value="<?php echo (int)$managementRole['id']; ?>">
                                <button class="btn btn-sm btn-outline-danger" type="submit">Delete Management Role</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="createRoleModal" tabindex="-1" aria-labelledby="createRoleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createRoleModalLabel">Create Role</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="/ERP/public/management/roles/create" method="post">
                <div class="modal-body">
                    <label class="form-label" for="roleName">Role Names</label>
                    <textarea id="roleName" class="form-control" name="name" maxlength="2000" rows="5" placeholder="Enter one role per line" required></textarea>
                    <div class="form-text">Enter one role name per line to create multiple roles at once.</div>
                    <label class="form-label mt-3" for="roleDescription">Description</label>
                    <textarea id="roleDescription" class="form-control" name="description" maxlength="255" rows="3"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Role</button>
                </div>
            </form>
        </div>
    </div>
</div>
