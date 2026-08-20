<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title ?? 'Super Admin Login'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #111827; }
        .admin-shell { max-width: 920px; border: 1px solid #374151; border-radius: 18px; overflow: hidden; box-shadow: 0 24px 70px rgba(0, 0, 0, .35); }
        .admin-panel { background: #1f2937; }
        .admin-mark { width: 64px; height: 64px; border-radius: 14px; background: #f59e0b; color: #111827; }
        .form-control, .form-select, .btn { border-radius: 10px; }
    </style>
</head>
<body>
<div class="container d-flex justify-content-center align-items-center min-vh-100 py-4">
    <div class="admin-shell row g-0 bg-white w-100">
        <div class="admin-panel col-lg-5 p-5 text-white d-flex flex-column justify-content-between">
            <div>
                <div class="admin-mark d-flex align-items-center justify-content-center mb-4"><i class="bi bi-shield-lock fs-2"></i></div>
                <p class="text-uppercase small fw-semibold text-warning mb-2">Restricted access</p>
                <h1 class="h3 fw-bold">Super Admin Console</h1>
                <p class="text-white-50 mt-3 mb-0">Manage company workspaces, feature access, and system configuration.</p>
            </div>
            <div class="small text-white-50 mt-5">Use the regular login portal for employee and operational accounts.</div>
        </div>
        <div class="col-lg-7 p-4 p-lg-5">
            <div class="mb-4">
                <h2 class="h4 fw-bold mb-1">Administrator sign in</h2>
                <p class="text-muted mb-0">Verify your account to continue.</p>
            </div>
            <?php if (!empty($error)): ?><div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
            <form method="post" action="/ERP/public/super-admin/login">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? ''); ?>">
                <div class="mb-3">
                    <label class="form-label fw-semibold" for="adminEmail">Admin email</label>
                    <input id="adminEmail" type="email" class="form-control" name="email" autocomplete="username" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold" for="adminPassword">Password</label>
                    <input id="adminPassword" type="password" class="form-control" name="password" autocomplete="current-password" required>
                </div>
                <div class="mb-4">
                    <label class="form-label fw-semibold" for="adminCompany">Company workspace</label>
                    <select id="adminCompany" class="form-select" name="company_id" required>
                        <option value="">Select an active company</option>
                        <?php foreach (($companies ?? []) as $company): ?>
                            <option value="<?php echo (int)$company['id']; ?>"><?php echo htmlspecialchars($company['company_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-warning w-100 fw-semibold">Enter Super Admin Console</button>
            </form>
            <a class="btn btn-link text-muted w-100 mt-3" href="/ERP/public/login">Use employee login</a>
        </div>
    </div>
</div>
</body>
</html>
