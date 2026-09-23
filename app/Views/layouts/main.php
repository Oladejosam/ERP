<?php
require_once APP_ROOT . '/app/Models/CompanyModel.php';
require_once APP_ROOT . '/app/Models/ChatModel.php';
$companySettings = (new CompanyModel())->getSettings();
$availableCompanies = (new CompanyModel())->getCompanies();
$companyModel = new CompanyModel();
$chatUnreadCount = !empty($_SESSION['user']['id']) ? (new ChatModel())->getUnreadCount((int)$_SESSION['user']['id']) : 0;
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
        @media (min-width: 992px) {
            .sidebar-column { position: sticky; top: 1rem; align-self: flex-start; max-height: calc(100vh - 2rem); overflow-y: auto; }
        }
        :root { --theme-color: <?php echo htmlspecialchars($companyThemeColor); ?>; }
        .topbar { background: linear-gradient(135deg, #0f172a, var(--theme-color)); }
        .brand-logo { max-height: 38px; max-width: 180px; object-fit: contain; }
        .menu-heading { display: flex; align-items: center; justify-content: space-between; gap: .5rem; }
        .menu-toggle { color: #475569; border-color: #cbd5e1; flex-shrink: 0; }
        .menu-toggle:hover { color: #0f172a; background: #edf3ff; border-color: #94a3b8; }
        .sidebar-column, main { transition: flex-basis .2s ease, max-width .2s ease; }
        .menu-collapsed .sidebar-column { flex: 0 0 76px; max-width: 76px; }
        .menu-collapsed main { flex: 0 0 calc(100% - 76px); max-width: calc(100% - 76px); }
        .menu-collapsed .sidebar-column .card-body { padding: .75rem .5rem !important; }
        .menu-collapsed .sidebar-column h6,
        .menu-collapsed .sidebar-column .nav-link { font-size: 0; text-align: center; }
        .menu-collapsed .sidebar-column .nav-link { padding-left: .5rem; padding-right: .5rem; }
        .menu-collapsed .sidebar-column .nav-link i { font-size: 1.1rem; margin-right: 0 !important; }
        @media (max-width: 991.98px) {
            .menu-collapsed .sidebar-column { display: none; }
            .menu-collapsed main { flex-basis: 100%; max-width: 100%; }
        }
    </style>
</head>
<body>
<?php
$currentUser = $_SESSION['user'] ?? null;
$roleName = trim((string)($currentUser['role_name'] ?? ''));
$isSuperAdmin = in_array(strtolower($roleName), ['super admin', 'superadministrator', 'super administrator'], true);
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
            <?php if (!empty($_SESSION['impersonation_admin_user'])): ?>
                <a class="btn btn-warning btn-sm" href="/ERP/public/stop-impersonation">Return to Super Admin</a>
            <?php endif; ?>
            <?php if ($isSuperAdmin && count($availableCompanies) > 1): ?>
                <form method="post" action="/ERP/public/setup/select-company" class="d-flex align-items-center">
                    <label class="visually-hidden" for="headerCompanySelect">Company</label>
                    <select class="form-select form-select-sm" id="headerCompanySelect" name="company_id" onchange="this.form.submit()">
                        <?php foreach ($availableCompanies as $availableCompany): ?>
                            <option value="<?php echo (int)$availableCompany['id']; ?>" <?php echo (int)$availableCompany['id'] === (int)($companySettings['id'] ?? 0) ? 'selected' : ''; ?>><?php echo htmlspecialchars($availableCompany['company_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>
            <?php endif; ?>
            <span class="badge bg-success-subtle text-success px-2 py-2"><?php echo htmlspecialchars($roleName !== '' ? $roleName : 'User'); ?></span>
            <?php if ($chatUnreadCount > 0): ?><a class="text-white text-decoration-none position-relative" href="/ERP/public/modules/chat" title="Unread chat messages"><i class="bi bi-chat-dots fs-5"></i><span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"><?php echo $chatUnreadCount > 99 ? '99+' : $chatUnreadCount; ?></span><span class="visually-hidden">Unread chat messages</span></a><?php endif; ?>
            <div class="d-flex align-items-center gap-2">
                <a class="text-white text-decoration-none d-flex align-items-center gap-2" href="/ERP/public/profile" title="View my profile">
                    <i class="bi bi-person-circle fs-4"></i>
                    <div>
                    <div class="fw-semibold"><?php echo htmlspecialchars($currentUser['name'] ?? 'Guest'); ?></div>
                    <small class="text-white-50">Portal User</small>
                    </div>
                </a>
            </div>
        </div>
    </nav>

    <?php if (!empty($_SESSION['chat_login_notification'])): ?>
        <div class="alert alert-info alert-dismissible fade show m-3 mb-0" role="alert">
            <i class="bi bi-chat-dots me-2"></i>You have <?php echo (int)$_SESSION['chat_login_notification']; ?> new chat message<?php echo (int)$_SESSION['chat_login_notification'] === 1 ? '' : 's'; ?>.
            <a class="alert-link ms-1" href="/ERP/public/modules/chat">Open Team Chat</a>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['chat_login_notification']); ?>
    <?php endif; ?>

    <div class="row g-0">
        <aside class="sidebar-column col-lg-2 p-3" id="mainMenu">
            <div class="card shadow-sm border-0 sidebar-card">
                <div class="card-body p-3">
                    <div class="menu-heading mb-3">
                        <h6 class="text-uppercase text-muted mb-0">Main Menu</h6>
                        <button type="button" class="btn btn-sm menu-toggle" id="menuToggle" aria-controls="mainMenu" aria-expanded="true" title="Collapse menu">
                            <i class="bi bi-layout-sidebar-inset"></i><span class="visually-hidden">Toggle menu</span>
                        </button>
                    </div>
                    <ul class="nav flex-column gap-1">
                        <li><a class="nav-link<?php echo isActiveNav('/ERP/public/', $currentPath) ? ' active' : ''; ?>" href="/ERP/public/"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a></li>
                        <?php if ($companyModel->hasCurrentUserModuleAccess('inventory')): ?><li><a class="nav-link<?php echo isActiveNav('/ERP/public/modules/inventory', $currentPath) ? ' active' : ''; ?>" href="/ERP/public/modules/inventory"><i class="bi bi-box-seam me-2"></i>Inventory</a></li><?php endif; ?>
                        <?php if ($companyModel->hasCurrentUserModuleAccess('accounting')): ?><li><a class="nav-link<?php echo isActiveNav('/ERP/public/modules/accounting', $currentPath) ? ' active' : ''; ?>" href="/ERP/public/modules/accounting"><i class="bi bi-cash-stack me-2"></i>Accounting</a></li><?php endif; ?>
                        <?php if ($companyModel->hasCurrentUserModuleAccess('employees')): ?><li><a class="nav-link<?php echo isActiveNav('/ERP/public/management/employees', $currentPath) ? ' active' : ''; ?>" href="/ERP/public/management/employees"><i class="bi bi-people me-2"></i>Employees</a></li><?php endif; ?>
                        <?php if ($companyModel->hasCurrentUserModuleAccess('employees')): ?><li><a class="nav-link<?php echo isActiveNav('/ERP/public/management/module-access', $currentPath) ? ' active' : ''; ?>" href="/ERP/public/management/module-access"><i class="bi bi-person-lock me-2"></i>Role Module Access</a></li><?php endif; ?>
                        <?php if ($companyModel->hasCurrentUserModuleAccess('hr')): ?><li><a class="nav-link<?php echo isActiveNav('/ERP/public/management/hr', $currentPath) ? ' active' : ''; ?>" href="/ERP/public/management/hr"><i class="bi bi-person-badge me-2"></i>HR</a></li><?php endif; ?>
                        <?php if ($companyModel->hasCurrentUserModuleAccess('procurement')): ?><li><a class="nav-link<?php echo isActiveNav('/ERP/public/management/procurement', $currentPath) ? ' active' : ''; ?>" href="/ERP/public/management/procurement"><i class="bi bi-cart3 me-2"></i>Procurement</a></li><?php endif; ?>
                        <?php if ($companyModel->hasCurrentUserModuleAccess('requisition')): ?><li><a class="nav-link<?php echo isActiveNav('/ERP/public/requisition', $currentPath) ? ' active' : ''; ?>" href="/ERP/public/requisition"><i class="bi bi-file-earmark-text me-2"></i>Requisition</a></li><?php endif; ?>
                        <?php if ($companyModel->hasCurrentUserModuleAccess('projects')): ?><li><a class="nav-link<?php echo isActiveNav('/ERP/public/modules/projects', $currentPath) ? ' active' : ''; ?>" href="/ERP/public/modules/projects"><i class="bi bi-building me-2"></i>Projects</a></li><?php endif; ?>
                        <?php if ($companyModel->hasCurrentUserModuleAccess('contract_admin')): ?><li><a class="nav-link<?php echo isActiveNav('/ERP/public/modules/contract-admin', $currentPath) ? ' active' : ''; ?>" href="/ERP/public/modules/contract-admin"><i class="bi bi-file-earmark-check me-2"></i>Contract Admin</a></li><?php endif; ?>
                        <?php if ($companyModel->hasCurrentUserModuleAccess('chat')): ?><li><a class="nav-link<?php echo isActiveNav('/ERP/public/modules/chat', $currentPath) ? ' active' : ''; ?>" href="/ERP/public/modules/chat"><i class="bi bi-chat-dots me-2"></i>Team Chat</a></li><?php endif; ?>
                        <?php foreach (['sales_marketing' => 'Sales & Marketing', 'quality_control' => 'Quality Control', 'workshop_maintenance' => 'Workshop & Maintenance', 'mix_design' => 'Mix Design', 'dispatch' => 'Dispatch Management', 'business_intelligence' => 'Business Intelligence'] as $rmcKey => $rmcLabel): ?>
                            <?php if ($companyModel->hasCurrentUserModuleAccess($rmcKey)): ?><li><a class="nav-link<?php echo isActiveNav('/ERP/public/modules/rmc', $currentPath) && (string)($_GET['module'] ?? '') === $rmcKey ? ' active' : ''; ?>" href="/ERP/public/modules/rmc?module=<?php echo urlencode($rmcKey); ?>"><i class="bi bi-kanban me-2"></i><?php echo htmlspecialchars($rmcLabel); ?></a></li><?php endif; ?>
                        <?php endforeach; ?>
                        <?php foreach ($companyModel->getCustomModulesForCompany((int)($_SESSION['selected_company_id'] ?? 1)) as $customModule): ?>
                            <?php $customKey = (string)$customModule['module_key']; if (!$companyModel->hasCurrentUserModuleAccess($customKey)) { continue; } ?>
                            <li><a class="nav-link<?php echo isActiveNav('/ERP/public/modules/custom', $currentPath) && (string)($_GET['module'] ?? '') === $customKey ? ' active' : ''; ?>" href="/ERP/public/modules/custom?module=<?php echo urlencode($customKey); ?>"><i class="bi bi-puzzle me-2"></i><?php echo htmlspecialchars((string)$customModule['module_name']); ?></a></li>
                        <?php endforeach; ?>
                        <?php if ($isSuperAdmin): ?><li><a class="nav-link<?php echo isActiveNav('/ERP/public/company/workspace', $currentPath) ? ' active' : ''; ?>" href="/ERP/public/company/workspace"><i class="bi bi-buildings me-2"></i>Company Workspace</a></li><?php endif; ?>
                        <?php if ($isSuperAdmin): ?><li><a class="nav-link<?php echo isActiveNav('/ERP/public/modules/workflow', $currentPath) ? ' active' : ''; ?>" href="/ERP/public/modules/workflow"><i class="bi bi-diagram-3 me-2"></i>Workflow</a></li><?php endif; ?>
                        <?php if ($isSuperAdmin): ?><li><a class="nav-link<?php echo isActiveNav('/ERP/public/modules/requisition-form', $currentPath) ? ' active' : ''; ?>" href="/ERP/public/modules/requisition-form"><i class="bi bi-ui-checks-grid me-2"></i>Requisition Form</a></li><?php endif; ?>
                        <li><a class="nav-link<?php echo isActiveNav('/ERP/public/setup', $currentPath) ? ' active' : ''; ?>" href="/ERP/public/setup"><i class="bi bi-building-gear me-2"></i>Company Setup</a></li>
                        <li><a class="nav-link" href="/ERP/public/logout"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </aside>

        <main class="col-lg-10 p-4">
            <button type="button" class="btn btn-outline-secondary btn-sm mb-3" onclick="window.history.back();">
                <i class="bi bi-arrow-left"></i> Back
            </button>
            <?php if (isset($contentView) && file_exists($contentView)) { require $contentView; } ?>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
<script>
(() => {
    const toggle = document.getElementById('menuToggle');
    const page = document.querySelector('.container-fluid');
    if (!toggle || !page) return;

    const setMenuState = (collapsed) => {
        page.classList.toggle('menu-collapsed', collapsed);
        toggle.setAttribute('aria-expanded', String(!collapsed));
        toggle.setAttribute('title', collapsed ? 'Expand menu' : 'Collapse menu');
        toggle.querySelector('i').className = collapsed ? 'bi bi-layout-sidebar' : 'bi bi-layout-sidebar-inset';
    };

    setMenuState(localStorage.getItem('erpMenuCollapsed') === 'true');
    toggle.addEventListener('click', () => {
        const collapsed = !page.classList.contains('menu-collapsed');
        setMenuState(collapsed);
        localStorage.setItem('erpMenuCollapsed', String(collapsed));
    });
})();
</script>
</body>
</html>
