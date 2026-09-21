<?php
declare(strict_types=1);

$project = $project ?? null;
$detail = $detail ?? null;
$siteLogs = $siteLogs ?? [];
$delayLogs = $delayLogs ?? [];
$materialBudgets = $materialBudgets ?? [];
$labourBudgets = $labourBudgets ?? [];
$budgetEntries = $budgetEntries ?? [];

if (!$project) {
    echo '<div class="alert alert-warning">Project not found.</div>';
    return;
}

$status = $project['status'] ?? 'planned';
$statusText = ucwords(str_replace('_', ' ', $status));
$progress = (int)($project['progress_percent'] ?? 0);
$budget = (float)($project['budget'] ?? 0);
$contractValue = (float)($project['contract_value'] ?? $budget);
$materialTotal = array_reduce($materialBudgets, fn($carry, $row) => $carry + (float)($row['total_cost'] ?? 0), 0.0);
$labourTotal = array_reduce($labourBudgets, fn($carry, $row) => $carry + (float)($row['total_cost'] ?? 0), 0.0);
$budgetTotal = array_reduce($budgetEntries, fn($carry, $row) => $carry + (float)($row['total_cost'] ?? 0), 0.0);
?>
<style>
    body { background: #f4f7fb; }
    .topbar { background: linear-gradient(135deg, #0f172a, #1d4ed8); }
    .sidebar-card { border-radius: 18px; }
    .nav-link { color: #475569; border-radius: 10px; padding: 10px 12px; }
    .nav-link.active { background: #e0ecff; color: #0f172a; font-weight: 600; }
    .nav-link:hover { background: #edf3ff; }

    .project-detail-shell {
        background: #f4f7fb;
        min-height: calc(100vh - 72px);
    }

    .project-sidebar {
        transition: all 0.25s ease;
    }

    .project-sidebar.collapsed {
        width: 5.5rem;
        max-width: 5.5rem;
        flex: 0 0 5.5rem;
    }

    .project-sidebar.collapsed .project-sidebar-inner {
        display: none;
    }

    .project-sidebar.collapsed .project-toggle-label {
        display: none;
    }

    .project-toggle {
        border: 1px solid rgba(148, 163, 184, 0.35);
        background: #f8fafc;
        color: #0f172a;
        border-radius: 12px;
        font-weight: 600;
        padding: 0.5rem 0.8rem;
        cursor: pointer;
    }

    .project-panel .card-body,
    .project-metric .card-body {
        padding: 1.25rem 1.1rem;
    }

    .project-section-title {
        font-size: 1.05rem;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 1rem;
    }

    .project-form label,
    .project-form .form-label {
        font-size: 0.75rem;
        font-weight: 600;
        color: #475569;
        margin-bottom: 0.35rem;
    }

    .project-form .form-control,
    .project-form textarea,
    .project-form input {
        border-radius: 12px;
        border: 1px solid #dbe4f0;
        background: #fff;
        padding: 0.7rem 0.8rem;
        box-shadow: none;
    }

    .project-form .form-control:focus,
    .project-form textarea:focus,
    .project-form input:focus {
        border-color: #93c5fd;
        box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.12);
    }

    .project-form .btn,
    .project-panel .btn {
        border-radius: 12px;
        padding: 0.7rem 1.1rem;
        font-weight: 600;
    }

    .project-form .btn-primary { background: linear-gradient(135deg, #1d4ed8, #2563eb); border: 0; }
    .project-form .btn-success { background: linear-gradient(135deg, #16a34a, #22c55e); border: 0; }
    .project-form .btn-warning { background: linear-gradient(135deg, #f59e0b, #fbbf24); border: 0; }

    .project-subtle-box {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 1rem;
    }

    .project-log-item,
    .project-budget-item,
    .project-delay-item {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 0.9rem 1rem;
        margin-bottom: 0.8rem;
    }

    .project-delay-item {
        background: #fff7ed;
        border-color: #fed7aa;
    }

    .summary-label {
        color: #64748b;
        font-size: 0.76rem;
        text-transform: uppercase;
        letter-spacing: 0.07em;
        font-weight: 700;
    }

    .project-metric .metric-label {
        font-size: 12px;
        font-weight: 600;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #64748b;
    }

    .project-metric .metric-value {
        margin-top: 0.5rem;
        font-size: 1.5rem;
        font-weight: 700;
        color: #0f172a;
    }
</style>

<?php
$currentUser = $_SESSION['user'] ?? null;
$roleName = trim((string)($currentUser['role_name'] ?? ''));
$currentPath = strtolower((string)($_SERVER['REQUEST_URI'] ?? ''));
$currentPath = parse_url($currentPath, PHP_URL_PATH) ?? '';
$currentPath = rtrim($currentPath, '/');

function isProjectDetailNavActive(string $href, string $currentPath): bool {
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
        <a class="navbar-brand fw-bold" href="/ERP/public/">Construction ERP</a>
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

    <div class="row g-0 project-detail-shell">
        <main class="col-lg-10 p-4 order-lg-1">
            <div class="card project-header shadow-sm border-0 mb-4">
                <div class="card-body py-4 px-4 text-white rounded-4">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <div>
                            <div class="small text-white-50">Project Number</div>
                            <h2 class="fw-bold mb-1"><?php echo htmlspecialchars($project['project_number'] ?? ''); ?></h2>
                            <div class="fs-6"><?php echo htmlspecialchars($project['name'] ?? ''); ?></div>
                        </div>
                        <div class="text-end">
                            <div class="small text-white-50">Status</div>
                            <span class="project-badge badge px-3 py-2"><?php echo htmlspecialchars($statusText); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="card project-metric border-0 h-100">
                        <div class="card-body">
                            <div class="metric-label">Contract Value</div>
                            <div class="metric-value">₦<?php echo number_format($contractValue, 2); ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card project-metric border-0 h-100">
                        <div class="card-body">
                            <div class="metric-label">Budget</div>
                            <div class="metric-value">₦<?php echo number_format($budget, 2); ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card project-metric border-0 h-100">
                        <div class="card-body">
                            <div class="metric-label">Materials</div>
                            <div class="metric-value">₦<?php echo number_format($materialTotal, 2); ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card project-metric border-0 h-100">
                        <div class="card-body">
                            <div class="metric-label">Labour</div>
                            <div class="metric-value">₦<?php echo number_format($labourTotal, 2); ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card project-metric border-0 h-100">
                        <div class="card-body">
                            <div class="metric-label">Budget Session</div>
                            <div class="metric-value">₦<?php echo number_format($budgetTotal, 2); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4 project-section-stack">
                <div class="col-xl-8">
                    <div class="card project-panel border-0 mb-4">
                        <div class="card-body">
                            <h5 class="project-section-title">Project overview</h5>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between small text-muted mb-1"><span>Progress</span><span><?php echo $progress; ?>%</span></div>
                                <div class="progress" style="height: 12px;"><div class="progress-bar" style="width: <?php echo $progress; ?>%"></div></div>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6"><div class="project-subtle-box"><div class="project-summary-label">Client</div><div class="fw-semibold mt-2"><?php echo htmlspecialchars($project['client_name'] ?? 'Unknown Client'); ?></div></div></div>
                                <div class="col-md-6"><div class="project-subtle-box"><div class="project-summary-label">Site</div><div class="fw-semibold mt-2"><?php echo htmlspecialchars($project['site_location'] ?? 'Not specified'); ?></div></div></div>
                                <div class="col-md-6"><div class="project-subtle-box"><div class="project-summary-label">Start Date</div><div class="fw-semibold mt-2"><?php echo htmlspecialchars($project['start_date'] ?? 'N/A'); ?></div></div></div>
                                <div class="col-md-6"><div class="project-subtle-box"><div class="project-summary-label">End Date</div><div class="fw-semibold mt-2"><?php echo htmlspecialchars($project['end_date'] ?? 'N/A'); ?></div></div></div>
                            </div>
                            <div class="mt-4 project-subtle-box">
                                <div class="project-summary-label mb-2">Notes</div>
                                <div><?php echo nl2br(htmlspecialchars($detail['notes'] ?? 'No project notes recorded yet.')); ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="card project-panel border-0 mb-4">
                        <div class="card-body" id="budget-section">
                            <h5 class="project-section-title">Budget</h5>
                            <form method="post" action="/ERP/public/projects/budget" class="project-form row g-3 mb-4">
                                <input type="hidden" name="project_id" value="<?php echo (int)$project['id']; ?>">
                                <div class="col-md-6"><label class="form-label">Budget Name</label><input type="text" class="form-control" name="budget_name" placeholder="Concrete works" required></div>
                                <div class="col-md-6"><label class="form-label">Category</label><input type="text" class="form-control" name="category" placeholder="Materials / Labour / Plant" required></div>
                                <div class="col-md-4"><label class="form-label">Unit of Measure</label><input type="text" class="form-control" name="unit_of_measure" placeholder="m³ / Bags / Days" required></div>
                                <div class="col-md-4"><label class="form-label">Quantity</label><input type="number" step="0.01" class="form-control" name="quantity" value="0" required></div>
                                <div class="col-md-4"><label class="form-label">Unit Cost</label><input type="number" step="0.01" class="form-control" name="unit_cost" value="0" required></div>
                                <div class="col-md-6"><label class="form-label">Supplier / Vendor</label><input type="text" class="form-control" name="supplier" placeholder="Vendor / Contractor"></div>
                                <div class="col-md-6"><label class="form-label">Status</label>
                                    <select class="form-control" name="status">
                                        <option value="pending">Pending</option>
                                        <option value="estimated">Estimated</option>
                                        <option value="approved">Approved</option>
                                        <option value="actual">Actual</option>
                                    </select>
                                </div>
                                <div class="col-12"><label class="form-label">Notes</label><textarea class="form-control" rows="2" name="notes" placeholder="Explain the item, assumptions, or purpose of this budget entry..."></textarea></div>
                                <div class="col-12 d-flex justify-content-end"><button class="btn btn-primary">Save Budget</button></div>
                            </form>
                            <?php if (empty($budgetEntries)): ?><p class="text-muted">No budget entries recorded yet.</p><?php else: ?><?php foreach ($budgetEntries as $entry): ?><div class="project-budget-item"><div class="d-flex justify-content-between"><strong><?php echo htmlspecialchars($entry['budget_name'] ?? 'Budget item'); ?></strong><span>₦<?php echo number_format((float)($entry['total_cost'] ?? 0), 2); ?></span></div><div class="small text-muted mt-2"><?php echo htmlspecialchars($entry['category'] ?? 'General'); ?> • <?php echo htmlspecialchars($entry['unit_of_measure'] ?? 'Unit'); ?> • <?php echo (float)($entry['quantity'] ?? 0); ?> • <?php echo htmlspecialchars(ucwords((string)($entry['status'] ?? 'pending'))); ?></div><div class="mt-2 small text-muted">Unit cost: ₦<?php echo number_format((float)($entry['unit_cost'] ?? 0), 2); ?><?php if (($entry['supplier'] ?? '') !== ''): ?> • Supplier: <?php echo htmlspecialchars($entry['supplier']); ?><?php endif; ?></div></div><?php endforeach; ?><?php endif; ?>
                        </div>
                    </div>

                    <div class="card project-panel border-0 mb-4">
                        <div class="card-body">
                            <h5 class="project-section-title">Site Logs</h5>
                            <form method="post" action="/ERP/public/projects/site-log" class="project-form row g-3 mb-4">
                                <input type="hidden" name="project_id" value="<?php echo (int)$project['id']; ?>">
                                <div class="col-md-4"><label class="form-label">Date</label><input type="date" class="form-control" name="log_date" required></div>
                                <div class="col-md-4"><label class="form-label">Activity</label><input type="text" class="form-control" name="activity" placeholder="Excavation" required></div>
                                <div class="col-md-4"><label class="form-label">Progress %</label><input type="number" min="0" max="100" class="form-control" name="progress_percent" value="0"></div>
                                <div class="col-12"><label class="form-label">Details</label><textarea class="form-control" rows="3" name="description" placeholder="Describe the work done on site..." required></textarea></div>
                                <div class="col-12 d-flex justify-content-end"><button class="btn btn-primary">Save Site Log</button></div>
                            </form>
                            <?php if (empty($siteLogs)): ?><p class="text-muted mb-0">No site logs recorded yet.</p><?php else: ?><?php foreach ($siteLogs as $log): ?><div class="project-log-item"><div class="d-flex justify-content-between align-items-center"><strong><?php echo htmlspecialchars($log['activity'] ?? 'Site activity'); ?></strong><small class="text-muted"><?php echo htmlspecialchars($log['log_date'] ?? ''); ?></small></div><div class="small text-muted my-2">Progress: <?php echo (int)($log['progress_percent'] ?? 0); ?>%</div><div><?php echo nl2br(htmlspecialchars($log['description'] ?? '')); ?></div></div><?php endforeach; ?><?php endif; ?>
                        </div>
                    </div>
                </div>

                    <div class="card project-panel border-0">
                        <div class="card-body">
                            <h5 class="project-section-title">Delay records</h5>
                            <form method="post" action="/ERP/public/projects/delay-log" class="project-form row g-3 mb-4">
                                <input type="hidden" name="project_id" value="<?php echo (int)$project['id']; ?>">
                                <div class="col-md-4"><label class="form-label">Date</label><input type="date" class="form-control" name="log_date" required></div>
                                <div class="col-md-4"><label class="form-label">Title</label><input type="text" class="form-control" name="title" placeholder="Rain delay" required></div>
                                <div class="col-md-4"><label class="form-label">Impact Days</label><input type="number" min="0" class="form-control" name="impact_days" value="0"></div>
                                <div class="col-12"><label class="form-label">Details</label><textarea class="form-control" rows="3" name="description" placeholder="Explain the delay and reason..." required></textarea></div>
                                <div class="col-12 d-flex justify-content-end"><button class="btn btn-warning text-dark">Save Delay Log</button></div>
                            </form>
                            <?php if (empty($delayLogs)): ?><p class="text-muted">No delays recorded.</p><?php else: ?><?php foreach ($delayLogs as $delay): ?><div class="project-delay-item"><div class="d-flex justify-content-between"><strong><?php echo htmlspecialchars($delay['title'] ?? 'Delay'); ?></strong><small><?php echo htmlspecialchars($delay['log_date'] ?? ''); ?></small></div><div class="small text-muted mt-2">Impact: <?php echo (int)($delay['impact_days'] ?? 0); ?> days</div><div class="mt-2"><?php echo nl2br(htmlspecialchars($delay['description'] ?? '')); ?></div></div><?php endforeach; ?><?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <aside class="project-sidebar col-lg-2 p-3 order-lg-2">
            <div class="card shadow-sm border-0 sidebar-card">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="text-uppercase text-muted mb-0">Project Menu</h6>
                        <button type="button" class="project-toggle" data-project-menu-toggle aria-label="Toggle project menu">
                            <i class="bi bi-layout-sidebar-inset-reverse"></i>
                            <span class="project-toggle-label">Collapse</span>
                        </button>
                    </div>
                    <div class="project-sidebar-inner">
                        <ul class="nav flex-column gap-1">
                            <li><a class="nav-link active" href="/ERP/public/modules/projects"><i class="bi bi-building me-2"></i>Project List</a></li>
                            <li><a class="nav-link<?php echo isProjectDetailNavActive('/ERP/public/modules/projects', $currentPath) ? ' active' : ''; ?>" href="/ERP/public/modules/projects"><i class="bi bi-speedometer2 me-2"></i>Overview</a></li>
                            <li><a class="nav-link" href="/ERP/public/modules/projects"><i class="bi bi-cash-stack me-2"></i>Budgets</a></li>
                            <li><a class="nav-link" href="/ERP/public/modules/projects"><i class="bi bi-list-check me-2"></i>Site Logs</a></li>
                            <li><a class="nav-link" href="/ERP/public/modules/projects"><i class="bi bi-exclamation-triangle me-2"></i>Delay Logs</a></li>
                            <li><a class="nav-link" href="/ERP/public/logout"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const sidebar = document.querySelector('.project-sidebar');
        const toggle = document.querySelector('[data-project-menu-toggle]');
        const label = document.querySelector('.project-toggle-label');

        if (sidebar && toggle && label) {
            toggle.addEventListener('click', function () {
                sidebar.classList.toggle('collapsed');
                const collapsed = sidebar.classList.contains('collapsed');
                label.textContent = collapsed ? 'Expand' : 'Collapse';
                toggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
            });
        }
    });
</script>

