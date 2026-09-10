<div class="row g-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="fw-bold mb-1"><?php echo htmlspecialchars($companyName ?? 'Company'); ?> Dashboard</h2>
                <p class="text-muted mb-0">Executive view for finance, HR, inventory, and field operations.</p>
            </div>
            <span class="badge bg-success-subtle text-success px-3 py-2">Live Operations</span>
        </div>
    </div>

    <div class="col-12">
        <div class="card shadow-sm border-0">
            <div class="card-body d-flex flex-column flex-md-row justify-content-between gap-3">
                <div>
                    <div class="text-muted small">Signed in as</div>
                    <h4 class="fw-bold mb-1"><?php echo htmlspecialchars($currentUserName ?? 'User'); ?></h4>
                </div>
                <div>
                    <div class="text-muted small">Role</div>
                    <div class="fw-semibold"><?php echo htmlspecialchars($currentUserRole ?? 'User'); ?></div>
                </div>
                <div>
                    <div class="text-muted small">Designation</div>
                    <div class="fw-semibold"><?php echo htmlspecialchars($currentUserDesignation ?? 'Not specified'); ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted">Employees</h6>
                        <h3 class="fw-bold"><?php echo number_format((int)($employees ?? 0), 0); ?></h3>
                    </div>
                    <i class="bi bi-people fs-3 text-primary"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted">Inventory</h6>
                        <h3 class="fw-bold"><?php echo number_format((int)($inventory_items ?? 0), 0); ?></h3>
                    </div>
                    <i class="bi bi-box-seam fs-3 text-warning"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted">Revenue</h6>
                        <h3 class="fw-bold">₦<?php echo number_format((float)($revenue ?? 0), 2); ?></h3>
                    </div>
                    <i class="bi bi-cash-coin fs-3 text-success"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted">Expenses</h6>
                        <h3 class="fw-bold">₦<?php echo number_format((float)($expenses ?? 0), 2); ?></h3>
                    </div>
                    <i class="bi bi-wallet2 fs-3 text-warning"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted">Profit</h6>
                        <h3 class="fw-bold">₦<?php echo number_format((float)($profit ?? 0), 2); ?></h3>
                    </div>
                    <i class="bi bi-graph-up-arrow fs-3 text-info"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <h5 class="fw-bold mb-3">Recent Activity</h5>
                <ul class="list-group list-group-flush">
                    <?php if (empty($recentActivities)): ?>
                        <li class="list-group-item px-0 text-muted">No recent activity related to this account.</li>
                    <?php else: ?>
                        <?php foreach (array_slice($recentActivities, 0, 3) as $activity): ?>
                            <li class="list-group-item px-0">
                                <a class="text-decoration-none" href="/ERP/public/requisition/view?id=<?php echo (int)$activity['requisition_id']; ?>"><?php echo htmlspecialchars($activity['activity_text']); ?></a>
                                <div class="small text-muted"><?php echo htmlspecialchars($activity['occurred_at']); ?></div>
                            </li>
                        <?php endforeach; ?>
                        <?php if (count($recentActivities) > 3): ?><li class="list-group-item px-0"><a class="small" href="/ERP/public/requisition">See more activity</a></li><?php endif; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <h5 class="fw-bold mb-3">Notifications</h5>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span>Pending invoices</span>
                    <span class="badge bg-warning text-dark">4</span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span>Low stock alerts</span>
                    <span class="badge bg-danger">2</span>
                </div>
                <div class="border-top mt-3 pt-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>Requisitions tagged to you</span>
                        <span class="badge <?php echo !empty($taggedRequisitions) ? 'bg-primary' : 'bg-secondary'; ?>"><?php echo count($taggedRequisitions ?? []); ?></span>
                    </div>
                    <?php if (!empty($taggedRequisitions)): ?>
                        <?php foreach (array_slice($taggedRequisitions, 0, 3) as $taggedRequisition): ?>
                            <a class="d-block small text-decoration-none mb-1" href="/ERP/public/requisition/view?id=<?php echo (int)$taggedRequisition['id']; ?>"><i class="bi bi-arrow-right-circle me-1"></i><?php echo htmlspecialchars($taggedRequisition['title']); ?></a>
                        <?php endforeach; ?>
                        <?php if (count($taggedRequisitions) > 3): ?><a class="small" href="/ERP/public/requisition">View all tagged requisitions</a><?php endif; ?>
                    <?php else: ?>
                        <div class="small text-muted">No pending requisitions require your attention.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card shadow-sm border-0 mt-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="fw-bold mb-1">Requisition</h5>
                        <p class="text-muted mb-0">Access the requisition workflow from the dashboard.</p>
                    </div>
                    <a href="/ERP/public/requisition" class="btn btn-primary btn-sm">Open requisitions</a>
                </div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="border rounded p-3 bg-light h-100">
                            <div class="fw-semibold">Submit request</div>
                            <div class="small text-muted">Create a new materials or services requisition.</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded p-3 bg-light h-100">
                            <div class="fw-semibold">Track status</div>
                            <div class="small text-muted">View pending, approved, rejected and flagged items.</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded p-3 bg-light h-100">
                            <div class="fw-semibold">Respond & approve</div>
                            <div class="small text-muted">Respond to flagged requisitions or take approval actions.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
