<?php
$module = $module ?? null;
$moduleName = $module !== null ? (string)($module['module_name'] ?? 'Custom Module') : 'Custom Module';
$moduleDescription = $module !== null ? trim((string)($module['description'] ?? '')) : '';
?>
<div class="card shadow-sm border-0">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <p class="text-uppercase text-muted small fw-semibold mb-1">Custom Module</p>
                <h2 class="fw-bold mb-0"><?php echo htmlspecialchars($moduleName); ?></h2>
            </div>
            <a class="btn btn-outline-secondary" href="/ERP/public/modules">Back to Module Center</a>
        </div>

        <?php if ($moduleDescription !== ''): ?>
            <div class="alert alert-light border mb-4">
                <?php echo nl2br(htmlspecialchars($moduleDescription)); ?>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card border-0 bg-light h-100">
                    <div class="card-body">
                        <h5 class="fw-bold mb-3">Overview</h5>
                        <p class="text-muted mb-0">This module is configured for this company and can be extended with specific forms, records, or workflows based on your business needs.</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card border-0 bg-primary-subtle h-100">
                    <div class="card-body">
                        <h6 class="fw-bold text-primary mb-2">Module details</h6>
                        <ul class="list-unstyled mb-0 small">
                            <li><strong>Type:</strong> Custom</li>
                            <li><strong>Company:</strong> <?php echo htmlspecialchars((string)($_SESSION['company_name'] ?? 'Current company')); ?></li>
                            <li><strong>Status:</strong> Active</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
