<?php
$selectedCompanyId = (int)($selectedCompanyId ?? 0);
$moduleAccess = [];
foreach ($companies as $company) {
    if ((int)$company['id'] === $selectedCompanyId) {
        $moduleAccess = $company['module_access'] ?? [];
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title ?? 'Select Company'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div><p class="text-uppercase text-muted small fw-semibold mb-1">Super Admin</p><h1 class="h3 mb-0">Choose a company workspace</h1></div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-primary" href="/ERP/public/"><i class="bi bi-arrow-left me-1"></i>Back to Dashboard</a>
            <a class="btn btn-outline-secondary" href="/ERP/public/logout"><i class="bi bi-box-arrow-right me-1"></i>Logout</a>
        </div>
    </div>
    <?php if (($error ?? '') !== ''): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
    <?php if (!empty($_SESSION['company_flash'])): ?><div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['company_flash']); unset($_SESSION['company_flash']); ?></div><?php endif; ?>
    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h2 class="h5 mb-3">Select existing company</h2>
                    <?php foreach ($companies as $company): ?>
                        <form method="post" action="/ERP/public/company/select" class="border rounded p-3 mb-3">
                            <input type="hidden" name="company_id" value="<?php echo (int)$company['id']; ?>">
                            <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
                                <div class="fw-semibold"><?php echo htmlspecialchars($company['company_name']); ?></div>
                                <div class="d-flex gap-2 align-items-center">
                                    <span class="badge <?php echo (int)$company['is_active'] === 1 ? 'text-bg-success' : 'text-bg-secondary'; ?>">
                                        <?php echo (int)$company['is_active'] === 1 ? 'Active' : 'Disabled'; ?>
                                    </span>
                                    <span class="badge text-bg-light"><?php echo htmlspecialchars($company['theme_color']); ?></span>
                                </div>
                            </div>
                            <div class="small text-muted mb-2">
                                Created: <?php echo !empty($company['created_at']) ? htmlspecialchars(date('F j, Y g:i A', strtotime((string)$company['created_at']))) : 'N/A'; ?>
                            </div>
                            <div class="small text-muted mb-2">Choose modules for this company before entering its workspace.</div>
                            <?php if ((int)$company['is_active'] === 1): ?>
                            <div class="row g-2 mb-3">
                                <?php $access = $company['module_access'] ?? []; foreach ($modules as $moduleKey => $moduleLabel): ?>
                                    <div class="col-md-6"><label class="form-check"><input class="form-check-input" type="checkbox" name="modules[]" value="<?php echo htmlspecialchars($moduleKey); ?>" <?php echo in_array($moduleKey, $access, true) ? 'checked' : ''; ?>><span class="form-check-label"><?php echo htmlspecialchars($moduleLabel); ?></span></label></div>
                                <?php endforeach; ?>
                            </div>
                            <button class="btn btn-primary" type="submit">Open Company</button>
                            <?php endif; ?>
                        </form>
                        <form method="post" action="/ERP/public/company/set-active" class="mt-n3 mb-3 px-3">
                            <input type="hidden" name="company_id" value="<?php echo (int)$company['id']; ?>">
                            <input type="hidden" name="active" value="<?php echo (int)$company['is_active'] === 1 ? '0' : '1'; ?>">
                            <button class="btn btn-sm <?php echo (int)$company['is_active'] === 1 ? 'btn-outline-danger' : 'btn-outline-success'; ?>" type="submit" onclick="return confirm('<?php echo (int)$company['is_active'] === 1 ? 'Disable this company and all its system features?' : 'Enable this company again?'; ?>');">
                                <?php echo (int)$company['is_active'] === 1 ? 'Disable Company' : 'Enable Company'; ?>
                            </button>
                        </form>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h2 class="h5 mb-3">Create new company</h2>
                    <form method="post" action="/ERP/public/company/create" enctype="multipart/form-data">
                        <div class="mb-3"><label class="form-label">Company name</label><input class="form-control" name="company_name" required maxlength="150"></div>
                        <div class="mb-3"><label class="form-label">Company logo</label><input class="form-control" type="file" name="company_logo" accept="image/jpeg,image/png,image/webp"></div>
                        <div class="mb-3"><label class="form-label">Theme color</label><input class="form-control form-control-color" type="color" name="theme_color" value="#1d4ed8"></div>
                        <div class="mb-3"><label class="form-label">Accessible modules</label>
                            <?php foreach ($modules as $moduleKey => $moduleLabel): ?><label class="form-check"><input class="form-check-input" type="checkbox" name="modules[]" value="<?php echo htmlspecialchars($moduleKey); ?>" checked><span class="form-check-label"><?php echo htmlspecialchars($moduleLabel); ?></span></label><?php endforeach; ?>
                        </div>
                        <button class="btn btn-success" type="submit">Create and Open Company</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
