<?php
$statusLabel = ucwords(str_replace('_', ' ', (string)($project['status'] ?? 'planned')));
$progress = max(0, min(100, (int)($project['progress_percent'] ?? 0)));
$schedule = $schedule ?? [];
$employeeSearch = (string)($employeeSearch ?? '');
$employeeStatus = (string)($employeeStatus ?? '');
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <p class="text-muted mb-1"><?php echo htmlspecialchars($project['project_number']); ?></p>
        <h2 class="fw-bold mb-0"><?php echo htmlspecialchars($project['name']); ?></h2>
    </div>
    <a class="btn btn-outline-secondary" href="/ERP/public/modules/projects"><i class="bi bi-arrow-left me-1"></i>Back to Projects</a>
</div>

<?php if (!empty($_SESSION['project_flash'])): ?>
    <div class="alert alert-info"><?php echo htmlspecialchars($_SESSION['project_flash']); unset($_SESSION['project_flash']); ?></div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-4">Project Overview</h5>
                <div class="row g-4">
                    <div class="col-md-6"><span class="text-muted d-block small">Client</span><span class="fw-semibold"><?php echo htmlspecialchars($project['client_name'] ?: 'Not specified'); ?></span></div>
                    <div class="col-md-6"><span class="text-muted d-block small">Consultant</span><span class="fw-semibold"><?php echo htmlspecialchars($project['consultant'] ?: 'Not specified'); ?></span></div>
                    <div class="col-md-6"><span class="text-muted d-block small">Site Location</span><span class="fw-semibold"><?php echo htmlspecialchars($project['site_location']); ?></span></div>
                    <div class="col-md-6"><span class="text-muted d-block small">Status</span><span class="badge text-bg-secondary"><?php echo htmlspecialchars($statusLabel); ?></span></div>
                    <div class="col-md-6"><span class="text-muted d-block small">Start Date</span><span class="fw-semibold"><?php echo htmlspecialchars($project['start_date']); ?></span></div>
                    <div class="col-md-6"><span class="text-muted d-block small">End Date</span><span class="fw-semibold"><?php echo htmlspecialchars($project['end_date']); ?></span></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-3">Financials</h5>
                <div class="d-flex justify-content-between mb-2"><span class="text-muted">Contract value</span><strong>₦<?php echo number_format((float)$project['contract_value'], 2); ?></strong></div>
                <div class="d-flex justify-content-between"><span class="text-muted">Budget</span><strong>₦<?php echo number_format((float)$project['budget'], 2); ?></strong></div>
            </div>
        </div>
        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-3">Progress</h5>
                <div class="progress mb-2" style="height: 24px"><div class="progress-bar" style="width: <?php echo $progress; ?>%" aria-valuenow="<?php echo $progress; ?>" aria-valuemin="0" aria-valuemax="100"><?php echo $progress; ?>%</div></div>
                <span class="text-muted small"><?php echo !empty($project['progress_from_schedule']) ? 'Calculated from schedule activities' : 'Current completion'; ?></span>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0 mt-4">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div><h5 class="fw-bold mb-1">Project Schedule</h5><p class="text-muted mb-0">Plan activities and update progress as the work moves forward.</p></div>
        </div>
        <form method="post" action="/ERP/public/projects/schedule/save" class="row g-2 align-items-end mb-4">
            <input type="hidden" name="project_id" value="<?php echo (int)$project['id']; ?>">
            <div class="col-lg-3 col-md-6"><label class="form-label">Activity</label><input class="form-control" name="task_name" placeholder="e.g. Foundation works" maxlength="180" required></div>
            <div class="col-lg-2 col-md-3"><label class="form-label">Start date</label><input class="form-control" type="date" name="schedule_start_date" required></div>
            <div class="col-lg-2 col-md-3"><label class="form-label">End date</label><input class="form-control" type="date" name="schedule_end_date" required></div>
            <div class="col-lg-2 col-md-4"><label class="form-label">Status</label><select class="form-select" name="schedule_status"><option value="planned">Planned</option><option value="in_progress">In progress</option><option value="completed">Completed</option><option value="on_hold">On hold</option></select></div>
            <div class="col-lg-1 col-md-4"><label class="form-label">Progress %</label><input class="form-control" type="number" name="schedule_progress_percent" min="0" max="100" value="0"></div>
            <div class="col-lg-2 col-md-4"><label class="form-label">Assigned to</label><input class="form-control" name="assigned_to" maxlength="150" placeholder="Team / person"></div>
            <div class="col-12"><label class="form-label">Notes</label><input class="form-control" name="schedule_notes" maxlength="1000" placeholder="Dependencies, deliverables, or remarks"></div>
            <div class="col-12"><button class="btn btn-primary" type="submit"><i class="bi bi-calendar-plus me-1"></i>Add schedule activity</button></div>
        </form>
        <?php if (empty($schedule)): ?>
            <p class="text-muted mb-0">No schedule activities have been added.</p>
        <?php else: ?>
            <div class="table-responsive"><table class="table table-striped align-middle mb-0"><thead><tr><th>Activity</th><th>Dates</th><th>Progress</th><th>Assigned to</th><th>Status</th><th>Action</th></tr></thead><tbody>
                <?php foreach ($schedule as $scheduleItem): $scheduleId = (int)$scheduleItem['id']; ?><tr><td><span class="fw-semibold"><?php echo htmlspecialchars($scheduleItem['task_name']); ?></span><?php if (!empty($scheduleItem['notes'])): ?><small class="text-muted d-block"><?php echo htmlspecialchars($scheduleItem['notes']); ?></small><?php endif; ?></td><td><?php echo htmlspecialchars($scheduleItem['start_date'] . ' to ' . $scheduleItem['end_date']); ?></td><td style="min-width: 140px"><div class="progress" role="progressbar" aria-valuenow="<?php echo (int)$scheduleItem['progress_percent']; ?>" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar" style="width: <?php echo (int)$scheduleItem['progress_percent']; ?>%"><?php echo (int)$scheduleItem['progress_percent']; ?>%</div></div></td><td><?php echo htmlspecialchars($scheduleItem['assigned_to'] ?: 'Not assigned'); ?></td><td><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $scheduleItem['status']))); ?></td><td><button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#editSchedule<?php echo $scheduleId; ?>"><i class="bi bi-pencil me-1"></i>Edit</button></td></tr>
                <div class="modal fade" id="editSchedule<?php echo $scheduleId; ?>" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Edit schedule activity</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div><form method="post" action="/ERP/public/projects/schedule/save"><div class="modal-body"><input type="hidden" name="project_id" value="<?php echo (int)$project['id']; ?>"><input type="hidden" name="schedule_id" value="<?php echo $scheduleId; ?>"><div class="row g-3"><div class="col-md-8"><label class="form-label">Activity</label><input class="form-control" name="task_name" value="<?php echo htmlspecialchars($scheduleItem['task_name']); ?>" maxlength="180" required></div><div class="col-md-4"><label class="form-label">Assigned to</label><input class="form-control" name="assigned_to" value="<?php echo htmlspecialchars($scheduleItem['assigned_to'] ?? ''); ?>" maxlength="150"></div><div class="col-md-4"><label class="form-label">Start date</label><input class="form-control" type="date" name="schedule_start_date" value="<?php echo htmlspecialchars($scheduleItem['start_date']); ?>" required></div><div class="col-md-4"><label class="form-label">End date</label><input class="form-control" type="date" name="schedule_end_date" value="<?php echo htmlspecialchars($scheduleItem['end_date']); ?>" required></div><div class="col-md-2"><label class="form-label">Status</label><select class="form-select" name="schedule_status"><?php foreach (['planned' => 'Planned', 'in_progress' => 'In progress', 'completed' => 'Completed', 'on_hold' => 'On hold'] as $statusKey => $statusName): ?><option value="<?php echo $statusKey; ?>" <?php echo $scheduleItem['status'] === $statusKey ? 'selected' : ''; ?>><?php echo $statusName; ?></option><?php endforeach; ?></select></div><div class="col-md-2"><label class="form-label">Progress %</label><input class="form-control" type="number" name="schedule_progress_percent" min="0" max="100" value="<?php echo (int)$scheduleItem['progress_percent']; ?>"></div><div class="col-12"><label class="form-label">Notes</label><textarea class="form-control" name="schedule_notes" rows="3" maxlength="1000"><?php echo htmlspecialchars($scheduleItem['notes'] ?? ''); ?></textarea></div></div></div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Save changes</button></div></form></div></div></div>
                <?php endforeach; ?>
            </tbody></table></div>
        <?php endif; ?>
    </div>
</div>

<div class="card shadow-sm border-0 mt-4">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div><h5 class="fw-bold mb-1">Site Team</h5><p class="text-muted mb-0">Assign employees and name their jobs on this project site.</p></div>
        </div>
        <form method="get" action="/ERP/public/modules/projects/view" class="row g-2 align-items-end mb-4">
            <input type="hidden" name="id" value="<?php echo (int)$project['id']; ?>">
            <div class="col-md-7"><label class="form-label" for="projectEmployeeSearch">Search employees</label><input class="form-control" id="projectEmployeeSearch" name="employee_search" value="<?php echo htmlspecialchars($employeeSearch); ?>" placeholder="Name, employee ID, position, department, email or phone"></div>
            <div class="col-md-3"><label class="form-label" for="projectEmployeeStatus">Status</label><select class="form-select" id="projectEmployeeStatus" name="employee_status"><option value="">All statuses</option><option value="active" <?php echo $employeeStatus === 'active' ? 'selected' : ''; ?>>Active</option><option value="inactive" <?php echo $employeeStatus === 'inactive' ? 'selected' : ''; ?>>Inactive</option><option value="terminated" <?php echo $employeeStatus === 'terminated' ? 'selected' : ''; ?>>Terminated</option></select></div>
            <div class="col-md-2 d-flex gap-2"><button class="btn btn-outline-primary flex-fill" type="submit"><i class="bi bi-search me-1"></i>Search</button><a class="btn btn-outline-secondary" href="/ERP/public/modules/projects/view?id=<?php echo (int)$project['id']; ?>" title="Clear employee search" aria-label="Clear employee search"><i class="bi bi-x-lg"></i></a></div>
        </form>
        <form method="post" action="/ERP/public/projects/assign-employee" class="row g-2 align-items-end mb-4">
            <input type="hidden" name="project_id" value="<?php echo (int)$project['id']; ?>">
            <div class="col-md-5"><label class="form-label">Employee</label><select class="form-select" name="employee_id" required><option value="">Select employee</option><?php foreach ($employees as $employee): ?><option value="<?php echo (int)$employee['id']; ?>"><?php echo htmlspecialchars(trim($employee['first_name'] . ' ' . $employee['last_name']) . ' - ' . $employee['employee_code']); ?></option><?php endforeach; ?></select></div>
            <div class="col-md-5"><label class="form-label">Named job on site</label><input class="form-control" name="job_title" placeholder="e.g. Site Supervisor" required maxlength="150"></div>
            <div class="col-md-2"><button class="btn btn-primary w-100" type="submit"><i class="bi bi-person-plus me-1"></i>Assign</button></div>
        </form>
        <?php if (empty($assignments)): ?>
            <p class="text-muted mb-0">No employees have been assigned to this site.</p>
        <?php else: ?>
            <div class="table-responsive"><table class="table table-striped align-middle mb-0"><thead><tr><th>Employee</th><th>Department</th><th>Site Job</th><th>Action</th></tr></thead><tbody>
                <?php foreach ($assignments as $assignment): ?><tr><td><?php echo htmlspecialchars(trim($assignment['first_name'] . ' ' . $assignment['last_name'])); ?><small class="text-muted d-block"><?php echo htmlspecialchars($assignment['employee_code']); ?></small></td><td><?php echo htmlspecialchars($assignment['department']); ?></td><td class="fw-semibold"><?php echo htmlspecialchars($assignment['job_title']); ?></td><td><form method="post" action="/ERP/public/projects/remove-employee" onsubmit="return confirm('Remove this employee from the project site?');"><input type="hidden" name="project_id" value="<?php echo (int)$project['id']; ?>"><input type="hidden" name="assignment_id" value="<?php echo (int)$assignment['id']; ?>"><button class="btn btn-sm btn-outline-danger" type="submit"><i class="bi bi-person-dash me-1"></i>Remove</button></form></td></tr><?php endforeach; ?>
            </tbody></table></div>
        <?php endif; ?>
    </div>
</div>

<div class="card shadow-sm border-0 mt-4">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div><h5 class="fw-bold mb-1">Project Budget</h5><p class="text-muted mb-0">Add separate budget lines for materials, labor, equipment, or services.</p></div>
        </div>
        <form method="post" action="/ERP/public/projects/budget/add" class="row g-2 align-items-end mb-4">
            <input type="hidden" name="project_id" value="<?php echo (int)$project['id']; ?>">
            <div class="col-md-4"><label class="form-label">What are you budgeting?</label><input class="form-control" name="budget_name" placeholder="e.g. Cement" required maxlength="150"></div>
            <div class="col-md-2"><label class="form-label">Category</label><input class="form-control" name="category" placeholder="Material" value="General"></div>
            <div class="col-md-2"><label class="form-label">Unit</label><input class="form-control" name="unit_of_measure" placeholder="Bags" required maxlength="50"></div>
            <div class="col-md-2"><label class="form-label">Quantity</label><input class="form-control" type="number" name="quantity" min="0.01" step="0.01" required></div>
            <div class="col-md-2"><label class="form-label">Unit cost</label><input class="form-control" type="number" name="unit_cost" min="0" step="0.01" value="0" required></div>
            <div class="col-md-4"><label class="form-label">Supplier</label><input class="form-control" name="supplier" maxlength="150"></div>
            <div class="col-md-6"><label class="form-label">Notes</label><input class="form-control" name="notes"></div>
            <div class="col-md-2"><button class="btn btn-primary w-100" type="submit"><i class="bi bi-plus-lg me-1"></i>Add Budget</button></div>
        </form>
        <?php if (empty($budgets)): ?>
            <p class="text-muted mb-0">No budget lines have been added.</p>
        <?php else: ?>
            <div class="table-responsive"><table class="table table-striped align-middle mb-0"><thead><tr><th>Item</th><th>Category</th><th>Quantity</th><th>Unit Cost</th><th>Total</th><th>Supplier</th><th>Action</th></tr></thead><tbody>
                <?php foreach ($budgets as $budget): ?><tr><td class="fw-semibold"><?php echo htmlspecialchars($budget['budget_name']); ?><small class="text-muted d-block"><?php echo htmlspecialchars($budget['notes'] ?? ''); ?></small></td><td><?php echo htmlspecialchars($budget['category']); ?></td><td><?php echo number_format((float)$budget['quantity'], 2) . ' ' . htmlspecialchars($budget['unit_of_measure']); ?></td><td>₦<?php echo number_format((float)$budget['unit_cost'], 2); ?></td><td class="fw-semibold">₦<?php echo number_format((float)$budget['total_cost'], 2); ?></td><td><?php echo htmlspecialchars($budget['supplier'] ?: 'Not specified'); ?></td><td><button class="btn btn-sm btn-outline-danger" type="button" data-bs-toggle="modal" data-bs-target="#deleteBudgetModal<?php echo (int)$budget['id']; ?>"><i class="bi bi-trash me-1"></i>Delete</button></td></tr><div class="modal fade" id="deleteBudgetModal<?php echo (int)$budget['id']; ?>" tabindex="-1" aria-hidden="true"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Delete Budget Item</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div><form method="post" action="/ERP/public/projects/budget/delete"><div class="modal-body"><p>Enter the reason for deleting <strong><?php echo htmlspecialchars($budget['budget_name']); ?></strong>.</p><input type="hidden" name="project_id" value="<?php echo (int)$project['id']; ?>"><input type="hidden" name="budget_id" value="<?php echo (int)$budget['id']; ?>"><label class="form-label" for="deletionReason<?php echo (int)$budget['id']; ?>">Reason for deletion</label><textarea class="form-control" id="deletionReason<?php echo (int)$budget['id']; ?>" name="deletion_reason" rows="3" required></textarea></div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-danger" type="submit"><i class="bi bi-trash me-1"></i>Delete and Record</button></div></form></div></div></div><?php endforeach; ?>
            </tbody></table></div>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($deletedBudgets)): ?>
<div class="card shadow-sm border-0 mt-4">
    <div class="card-body p-4">
        <h5 class="fw-bold mb-3">Deleted Budget History</h5>
        <div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead><tr><th>Item</th><th>Original Total</th><th>Reason</th><th>Deleted At</th></tr></thead><tbody>
            <?php foreach ($deletedBudgets as $deletedBudget): ?><tr><td><?php echo htmlspecialchars($deletedBudget['budget_name']); ?></td><td>₦<?php echo number_format((float)$deletedBudget['total_cost'], 2); ?></td><td><?php echo htmlspecialchars($deletedBudget['deletion_reason']); ?></td><td><?php echo htmlspecialchars($deletedBudget['deleted_at']); ?></td></tr><?php endforeach; ?>
        </tbody></table></div>
    </div>
</div>
<?php endif; ?>

<div class="card shadow-sm border-0 mt-4">
    <div class="card-body p-4">
        <h5 class="fw-bold mb-3">Related Project Files</h5>
        <?php if (empty($documents)): ?>
            <p class="text-muted mb-0">No files have been attached to this project.</p>
        <?php else: ?>
            <div class="list-group list-group-flush">
                <?php foreach ($documents as $document): ?>
                    <a class="list-group-item list-group-item-action px-0 d-flex justify-content-between align-items-center" href="/ERP/public/modules/projects/document?id=<?php echo (int)$document['id']; ?>">
                        <span><i class="bi bi-paperclip me-2"></i><strong><?php echo htmlspecialchars($document['label']); ?></strong><small class="text-muted ms-2"><?php echo htmlspecialchars($document['original_name']); ?></small></span>
                        <i class="bi bi-download"></i>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
