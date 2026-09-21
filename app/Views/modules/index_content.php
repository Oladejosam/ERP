<div class="card shadow-sm border-0">
    <div class="card-body">
        <h4 class="fw-bold">ERP Module Center</h4>
        <p class="text-muted">Access inventory, accounting, HR, and procurement from one hub.</p>
        <div class="row g-3 mt-2">
            <div class="col-md-3"><a class="btn btn-outline-primary w-100" href="/ERP/public/modules/inventory">Inventory</a></div>
            <div class="col-md-3"><a class="btn btn-outline-primary w-100" href="/ERP/public/modules/accounting">Accounting</a></div>
            <div class="col-md-3"><a class="btn btn-outline-secondary w-100" href="/ERP/public/management/employees">Employees</a></div>
            <div class="col-md-3"><a class="btn btn-outline-secondary w-100" href="/ERP/public/management/hr">HR</a></div>
            <div class="col-md-3"><a class="btn btn-outline-secondary w-100" href="/ERP/public/management/procurement">Procurement</a></div>
            <div class="col-md-3"><a class="btn btn-outline-secondary w-100" href="/ERP/public/modules/projects">Projects</a></div>
            <div class="col-md-3"><a class="btn btn-outline-secondary w-100" href="/ERP/public/modules/contract-admin"><i class="bi bi-file-earmark-check me-1"></i>Contract Admin</a></div>
            <?php foreach (['sales_marketing' => 'Sales & Marketing', 'quality_control' => 'Quality Control', 'workshop_maintenance' => 'Workshop & Maintenance', 'mix_design' => 'Mix Design', 'dispatch' => 'Dispatch Management', 'business_intelligence' => 'Business Intelligence'] as $rmcKey => $rmcLabel): ?>
                <?php if (($companyModel ?? null) && $companyModel->hasCurrentUserModuleAccess($rmcKey)): ?><div class="col-md-3"><a class="btn btn-outline-success w-100" href="/ERP/public/modules/rmc?module=<?php echo urlencode($rmcKey); ?>"><?php echo htmlspecialchars($rmcLabel); ?></a></div><?php endif; ?>
            <?php endforeach; ?>
            <?php foreach (($customModules ?? []) as $customModule): ?>
                <?php $customKey = (string)$customModule['module_key']; if (!($companyModel ?? null) || !($companyModel->hasModuleAccess($customKey) ?? false)) { continue; } ?>
                <div class="col-md-3"><a class="btn btn-outline-info w-100" href="/ERP/public/modules/custom?module=<?php echo urlencode($customKey); ?>"><?php echo htmlspecialchars((string)$customModule['module_name']); ?></a></div>
            <?php endforeach; ?>
            <?php if ($isSuperAdmin ?? false): ?><div class="col-md-3"><a class="btn btn-outline-dark w-100" href="/ERP/public/modules/requisition-form"><i class="bi bi-ui-checks-grid me-1"></i>Requisition Form</a></div><?php endif; ?>
            <div class="col-md-3"><a class="btn btn-outline-secondary w-100" href="/ERP/public/management/sales">Sales</a></div>
        </div>
    </div>
</div>
