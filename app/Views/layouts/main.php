<?php
require_once APP_ROOT . '/app/Models/CompanyModel.php';
$companySettings = (new CompanyModel())->getSettings();
$companyName = trim((string)($companySettings['company_name'] ?? '')) ?: APP_NAME;
$companyLogo = trim((string)($companySettings['logo_path'] ?? ''));
$companyThemeColor = preg_match('/^#[0-9a-fA-F]{6}$/', (string)($companySettings['theme_color'] ?? ''))
    ? strtolower((string)$companySettings['theme_color'])
    : '#1d4ed8';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title ?? APP_NAME); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f4f7fb; }
        .sidebar-card { border-radius: 18px; }
        .nav-link { color: #475569; border-radius: 10px; padding: 10px 12px; }
        .nav-link.active { background: #e0ecff; color: #0f172a; font-weight: 600; }
        .nav-link:hover { background: #edf3ff; }
        :root { --theme-color: <?php echo htmlspecialchars($companyThemeColor); ?>; }
        .topbar { background: linear-gradient(135deg, #0f172a, var(--theme-color)); }
        .brand-logo { max-height: 38px; max-width: 180px; object-fit: contain; }
    </style>
</head>
<body>
<?php
$currentUser = $_SESSION['user'] ?? null;
$roleName = trim((string)($currentUser['role_name'] ?? ''));
$currentPath = strtolower((string)($_SERVER['REQUEST_URI'] ?? ''));
$currentPath = parse_url($currentPath, PHP_URL_PATH) ?? '';
$currentPath = rtrim($currentPath, '/');

function isActiveNav(string $href, string $currentPath): bool {
    $href = strtolower($href);
    $hrefPath = parse_url($href, PHP_URL_PATH) ?? '';
    $hrefPath = rtrim($hrefPath, '/');
    if ($hrefPath === '') { return false; }
    if ($currentPath === $hrefPath) { return true; }
    return strpos($currentPath, $hrefPath . '/') === 0;
}
?>
<div class="container-fluid p-0">
    <nav class="topbar navbar navbar-expand-lg navbar-dark px-4 py-3">
        <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="/ERP/public/">
            <?php if ($companyLogo !== ''): ?>
                <img class="brand-logo" src="<?php echo htmlspecialchars(BASE_URL . '/uploads/' . ltrim($companyLogo, '/')); ?>" alt="<?php echo htmlspecialchars($companyName); ?>">
            <?php else: ?>
                <?php echo htmlspecialchars($companyName); ?>
            <?php endif; ?>
        </a>
        <div class="ms-auto d-flex align-items-center gap-3 text-white">
            <span class="badge bg-success-subtle text-success px-2 py-2"><?php echo htmlspecialchars($roleName !== '' ? $roleName : 'User'); ?></span>
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-person-circle fs-4"></i>
                <div>
                    <div class="fw-semibold"><?php echo htmlspecialchars($currentUser['name'] ?? 'Guest'); ?></div>
                    <small class="text-white-50">Portal User</small>
                </div>
            </div>
        </div>
    </nav>

    <div class="row g-0">
        <aside class="col-lg-2 p-3">
            <div class="card shadow-sm border-0 sidebar-card">
                <div class="card-body p-3">
                    <h6 class="text-uppercase text-muted mb-3">Main Menu</h6>
                    <ul class="nav flex-column gap-1">
                        <li><a class="nav-link<?php echo isActiveNav('/ERP/public/', $currentPath) ? ' active' : ''; ?>" href="/ERP/public/"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a></li>
                        <li><a class="nav-link<?php echo isActiveNav('/ERP/public/modules/inventory', $currentPath) ? ' active' : ''; ?>" href="/ERP/public/modules/inventory"><i class="bi bi-box-seam me-2"></i>Inventory</a></li>
                        <li><a class="nav-link<?php echo isActiveNav('/ERP/public/modules/accounting', $currentPath) ? ' active' : ''; ?>" href="/ERP/public/modules/accounting"><i class="bi bi-cash-stack me-2"></i>Accounting</a></li>
                        <li><a class="nav-link<?php echo isActiveNav('/ERP/public/setup', $currentPath) ? ' active' : ''; ?>" href="/ERP/public/setup"><i class="bi bi-building-gear me-2"></i>Company Setup</a></li>
                        <li><a class="nav-link" href="/ERP/public/logout"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </aside>

        <main class="col-lg-10 p-4">
            <?php if (isset($contentView) && file_exists($contentView)) { require $contentView; } ?>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
