<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Human Resources</h4>
        <p class="text-muted mb-0">Manage departments used by employee records.</p>
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
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <span><?php echo htmlspecialchars($department['name']); ?></span>
                                <form method="post" action="/ERP/public/management/departments/delete" onsubmit="return confirm('Delete this department?');">
                                    <input type="hidden" name="department_id" value="<?php echo (int)$department['id']; ?>">
                                    <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
