<?php
$user = $user ?? [];
$employee = $employee ?? null;
$photoPath = trim((string)($employee['profile_picture'] ?? ''));
$photoUrl = $photoPath !== '' ? BASE_URL . '/uploads/' . ltrim($photoPath, '/') : '';
$displayName = trim((string)($user['name'] ?? 'User'));
$initials = strtoupper(substr($displayName, 0, 1));
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">My Profile</h4>
        <p class="text-muted mb-0">Your account and employee information.</p>
    </div>
    <a class="btn btn-outline-secondary" href="/ERP/public/"><i class="bi bi-arrow-left me-1"></i>Back to Dashboard</a>
</div>
<?php if (!empty($_SESSION['employee_flash'])): ?><div class="alert alert-info"><?php echo htmlspecialchars($_SESSION['employee_flash']); unset($_SESSION['employee_flash']); ?></div><?php endif; ?>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body text-center p-4">
                <?php if ($photoUrl !== ''): ?>
                    <img src="<?php echo htmlspecialchars($photoUrl); ?>" alt="Profile picture" class="rounded-circle border shadow-sm mb-3" style="width: 112px; height: 112px; object-fit: cover;">
                <?php else: ?>
                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 112px; height: 112px; font-size: 2.5rem;"><?php echo htmlspecialchars($initials); ?></div>
                <?php endif; ?>
                <?php if ($employee): ?>
                    <form action="/ERP/public/management/employees/update-photo" method="post" enctype="multipart/form-data" class="mb-3">
                        <input type="hidden" name="employee_id" value="<?php echo (int)$employee['id']; ?>">
                        <label class="form-label small" for="myProfilePicture">Update profile picture</label>
                        <input class="form-control form-control-sm" id="myProfilePicture" type="file" name="profile_picture" accept="image/jpeg,image/png,image/webp" required>
                        <button class="btn btn-primary btn-sm mt-2" type="submit"><i class="bi bi-upload me-1"></i>Upload Picture</button>
                    </form>
                <?php endif; ?>
                <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($displayName); ?></h5>
                <div class="text-muted"><?php echo htmlspecialchars((string)($user['role_name'] ?? 'User')); ?></div>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-3">Account Information</h5>
                <div class="row g-3">
                    <div class="col-md-6"><div class="small text-muted">Name</div><div class="fw-semibold"><?php echo htmlspecialchars($displayName); ?></div></div>
                    <div class="col-md-6"><div class="small text-muted">Email</div><div class="fw-semibold"><?php echo htmlspecialchars((string)($user['email'] ?? $employee['email'] ?? 'Not provided')); ?></div></div>
                    <div class="col-md-6"><div class="small text-muted">Role</div><div class="fw-semibold"><?php echo htmlspecialchars((string)($user['role_name'] ?? 'User')); ?></div></div>
                    <div class="col-md-6"><div class="small text-muted">Account ID</div><div class="fw-semibold"><?php echo (int)($user['id'] ?? 0); ?></div></div>
                </div>
            </div>
        </div>
        <?php if ($employee): ?>
            <div class="card shadow-sm border-0 mt-4">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-3">Employee Information</h5>
                    <div class="row g-3">
                        <div class="col-md-6"><div class="small text-muted">Employee Code</div><div class="fw-semibold"><?php echo htmlspecialchars((string)($employee['employee_code'] ?? 'Not provided')); ?></div></div>
                        <div class="col-md-6"><div class="small text-muted">Department</div><div class="fw-semibold"><?php echo htmlspecialchars((string)($employee['department'] ?? 'Not provided')); ?></div></div>
                        <div class="col-md-6"><div class="small text-muted">Position</div><div class="fw-semibold"><?php echo htmlspecialchars((string)($employee['position'] ?? 'Not provided')); ?></div></div>
                        <div class="col-md-6"><div class="small text-muted">Phone</div><div class="fw-semibold"><?php echo htmlspecialchars((string)($employee['phone'] ?? 'Not provided')); ?></div></div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
