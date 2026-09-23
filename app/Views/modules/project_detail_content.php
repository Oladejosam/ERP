<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1">Project Details</h2>
        <p class="text-muted mb-0"><?php echo htmlspecialchars($project['project_number'] ?? ''); ?> · <?php echo htmlspecialchars($project['name'] ?? ''); ?></p>
    </div>
    <a class="btn btn-outline-secondary" href="/ERP/public/modules/projects">Back to Projects</a>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
            <div>
                <div class="text-uppercase small text-muted mb-1">Project</div>
                <h4 class="fw-bold mb-0"><?php echo htmlspecialchars($project['name'] ?? ''); ?></h4>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-primary-subtle text-primary rounded-pill"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', (string)($project['status'] ?? 'planned')))); ?></span>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-3"><label class="form-label text-muted">Project Number</label><input class="form-control" value="<?php echo htmlspecialchars($project['project_number'] ?? ''); ?>" readonly></div>
            <div class="col-md-3"><label class="form-label text-muted">Client</label><input class="form-control" value="<?php echo htmlspecialchars($project['client_name'] ?: 'Not specified'); ?>" readonly></div>
            <div class="col-md-3"><label class="form-label text-muted">Site Location</label><input class="form-control" value="<?php echo htmlspecialchars($project['site_location'] ?? ''); ?>" readonly></div>
            <div class="col-md-3"><label class="form-label text-muted">Progress</label><input class="form-control" value="<?php echo (int)($project['progress_percent'] ?? 0); ?>%" readonly></div>
            <div class="col-md-3"><label class="form-label text-muted">Start Date</label><input class="form-control" value="<?php echo htmlspecialchars($project['start_date'] ?? ''); ?>" readonly></div>
            <div class="col-md-3"><label class="form-label text-muted">End Date</label><input class="form-control" value="<?php echo htmlspecialchars($project['end_date'] ?? ''); ?>" readonly></div>
            <div class="col-md-3"><label class="form-label text-muted">Consultant</label><input class="form-control" value="<?php echo htmlspecialchars($project['consultant'] ?? 'Not specified'); ?>" readonly></div>
            <div class="col-md-3"><label class="form-label text-muted">Budget</label><input class="form-control" value="<?php echo number_format((float)($project['budget'] ?? 0), 2); ?>" readonly></div>
        </div>

        <div class="mt-4">
            <div class="d-flex justify-content-between small text-muted mb-2">
                <span>Overall progress</span>
                <span><?php echo (int)($project['progress_percent'] ?? 0); ?>%</span>
            </div>
            <div class="progress" role="progressbar" aria-valuenow="<?php echo (int)($project['progress_percent'] ?? 0); ?>" aria-valuemin="0" aria-valuemax="100">
                <div class="progress-bar" style="width: <?php echo (int)($project['progress_percent'] ?? 0); ?>%"><?php echo (int)($project['progress_percent'] ?? 0); ?>%</div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <ul class="nav nav-tabs flex-wrap" id="projectDetailTabs" role="tablist">
            <li class="nav-item" role="presentation"><button class="nav-link active" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview-pane" type="button" role="tab" aria-controls="overview-pane" aria-selected="true">Overview</button></li>
            <li class="nav-item" role="presentation"><button class="nav-link" id="budget-tab" data-bs-toggle="tab" data-bs-target="#budget-pane" type="button" role="tab" aria-controls="budget-pane" aria-selected="false">Budget & Cost</button></li>
            <li class="nav-item" role="presentation"><button class="nav-link" id="schedule-tab" data-bs-toggle="tab" data-bs-target="#schedule-pane" type="button" role="tab" aria-controls="schedule-pane" aria-selected="false">Schedule</button></li>
            <li class="nav-item" role="presentation"><button class="nav-link" id="daily-progress-tab" data-bs-toggle="tab" data-bs-target="#daily-progress-pane" type="button" role="tab" aria-controls="daily-progress-pane" aria-selected="false">Daily Progress</button></li>
            <li class="nav-item" role="presentation"><button class="nav-link" id="productivity-tab" data-bs-toggle="tab" data-bs-target="#productivity-pane" type="button" role="tab" aria-controls="productivity-pane" aria-selected="false">Productivity</button></li>
            <li class="nav-item" role="presentation"><button class="nav-link" id="forecast-tab" data-bs-toggle="tab" data-bs-target="#forecast-pane" type="button" role="tab" aria-controls="forecast-pane" aria-selected="false">Forecast & Resources</button></li>
            <li class="nav-item" role="presentation"><button class="nav-link" id="procurement-tab" data-bs-toggle="tab" data-bs-target="#procurement-pane" type="button" role="tab" aria-controls="procurement-pane" aria-selected="false">Procurement & Materials</button></li>
            <li class="nav-item" role="presentation"><button class="nav-link" id="documents-tab" data-bs-toggle="tab" data-bs-target="#documents-pane" type="button" role="tab" aria-controls="documents-pane" aria-selected="false">Documents</button></li>
            <li class="nav-item" role="presentation"><button class="nav-link" id="rfi-tab" data-bs-toggle="tab" data-bs-target="#rfi-pane" type="button" role="tab" aria-controls="rfi-pane" aria-selected="false">RFI & Approvals</button></li>
            <li class="nav-item" role="presentation"><button class="nav-link" id="change-orders-tab" data-bs-toggle="tab" data-bs-target="#change-orders-pane" type="button" role="tab" aria-controls="change-orders-pane" aria-selected="false">Change Orders</button></li>
            <li class="nav-item" role="presentation"><button class="nav-link" id="safety-tab" data-bs-toggle="tab" data-bs-target="#safety-pane" type="button" role="tab" aria-controls="safety-pane" aria-selected="false">Safety & Compliance</button></li>
        </ul>

        <div class="tab-content pt-4" id="projectDetailTabsContent">
            <div class="tab-pane fade show active" id="overview-pane" role="tabpanel" aria-labelledby="overview-tab">
                <div class="row g-4">
                    <div class="col-lg-8">
                        <div class="card border-0 bg-light-subtle h-100">
                            <div class="card-body">
                                <h5 class="fw-bold mb-3">Project Controls</h5>
                                <div class="row g-3 text-center">
                                    <div class="col-6 col-md-3">
                                        <div class="border rounded-3 p-3 bg-white h-100">
                                            <div class="small text-muted">Budget</div>
                                            <div class="fw-bold fs-5"><?php echo number_format((float)($costSummary['budget'] ?? 0), 2); ?></div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <div class="border rounded-3 p-3 bg-white h-100">
                                            <div class="small text-muted">Contract</div>
                                            <div class="fw-bold fs-5"><?php echo number_format((float)($costSummary['contract_value'] ?? 0), 2); ?></div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <div class="border rounded-3 p-3 bg-white h-100">
                                            <div class="small text-muted">Approved CO</div>
                                            <div class="fw-bold fs-5"><?php echo number_format((float)($costSummary['approved_change_orders'] ?? 0), 2); ?></div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <div class="border rounded-3 p-3 bg-white h-100">
                                            <div class="small text-muted">Variance</div>
                                            <div class="fw-bold fs-5"><?php echo number_format((float)($costSummary['variance'] ?? 0), 2); ?></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row g-3 mt-2">
                                    <div class="col-md-4">
                                        <div class="border rounded-3 p-3 bg-white h-100">
                                            <div class="small text-muted">Committed Budget</div>
                                            <div class="fw-bold fs-6"><?php echo number_format((float)($costSummary['committed_budget'] ?? 0), 2); ?></div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="border rounded-3 p-3 bg-white h-100">
                                            <div class="small text-muted">Remaining Budget</div>
                                            <div class="fw-bold fs-6"><?php echo number_format((float)($costSummary['remaining_budget'] ?? 0), 2); ?></div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="border rounded-3 p-3 bg-white h-100">
                                            <div class="small text-muted">Budget Utilization</div>
                                            <div class="fw-bold fs-6"><?php echo number_format((float)($costSummary['budget_utilization_percent'] ?? 0), 1); ?>%</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="card border-0 bg-light-subtle h-100 mb-3">
                            <div class="card-body">
                                <h5 class="fw-bold mb-3">Assign Site Staff</h5>
                                <form method="post" action="/ERP/public/projects/assign-employee">
                                    <input type="hidden" name="project_id" value="<?php echo (int)($project['id'] ?? 0); ?>">
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label class="form-label">Employee</label>
                                            <select class="form-select" name="employee_id" required>
                                                <option value="">Select staff member</option>
                                                <?php foreach ($employees ?? [] as $employee): ?>
                                                    <option value="<?php echo (int)$employee['id']; ?>"><?php echo htmlspecialchars(($employee['first_name'] ?? '') . ' ' . ($employee['last_name'] ?? '')); ?> (<?php echo htmlspecialchars($employee['employee_code'] ?? ''); ?>)</option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Role on project</label>
                                            <input class="form-control" type="text" name="job_title" placeholder="Site Engineer" required>
                                        </div>
                                        <div class="col-12">
                                            <button class="btn btn-primary w-100" type="submit">Assign Staff</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="card border-0 bg-light-subtle h-100">
                            <div class="card-body">
                                <h5 class="fw-bold mb-3">Assigned Team</h5>
                                <?php if (empty($assignments)): ?>
                                    <p class="text-muted mb-0">No staff assigned to this project yet.</p>
                                <?php else: ?>
                                    <ul class="list-group list-group-flush">
                                        <?php foreach ($assignments as $assignment): ?>
                                            <li class="list-group-item px-0 bg-transparent d-flex justify-content-between align-items-center gap-3">
                                                <div>
                                                    <div class="fw-semibold"><?php echo htmlspecialchars(($assignment['first_name'] ?? '') . ' ' . ($assignment['last_name'] ?? '')); ?></div>
                                                    <div class="small text-muted"><?php echo htmlspecialchars($assignment['job_title'] ?? ''); ?> · <?php echo htmlspecialchars($assignment['position'] ?? ''); ?></div>
                                                </div>
                                                <form method="post" action="/ERP/public/projects/remove-employee" class="d-inline">
                                                    <input type="hidden" name="project_id" value="<?php echo (int)($project['id'] ?? 0); ?>">
                                                    <input type="hidden" name="assignment_id" value="<?php echo (int)($assignment['id'] ?? 0); ?>">
                                                    <button class="btn btn-sm btn-outline-danger" type="submit">Remove</button>
                                                </form>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="budget-pane" role="tabpanel" aria-labelledby="budget-tab">
                <div class="row g-4">
                    <div class="col-lg-6">
                        <div class="card border-0 bg-light-subtle h-100">
                            <div class="card-body">
                                <h5 class="fw-bold mb-3">Project Budget</h5>
                                <?php if (empty($budgets)): ?>
                                    <p class="text-muted mb-0">No budget items recorded for this project.</p>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm align-middle mb-0">
                                            <thead><tr><th>Item</th><th>Cost</th><th>Status</th></tr></thead>
                                            <tbody>
                                                <?php foreach ($budgets as $budget): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($budget['budget_name'] ?? ''); ?></td>
                                                        <td><?php echo number_format((float)($budget['total_cost'] ?? 0), 2); ?></td>
                                                        <td><span class="badge bg-light text-dark"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', (string)($budget['status'] ?? 'pending')))); ?></span></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card border-0 bg-light-subtle h-100">
                            <div class="card-body">
                                <h5 class="fw-bold mb-3">Cost Summary</h5>
                                <div class="table-responsive">
                                    <table class="table table-sm align-middle mb-0">
                                        <tbody>
                                            <tr><th class="bg-transparent">Budget</th><td class="text-end"><?php echo number_format((float)($costSummary['budget'] ?? 0), 2); ?></td></tr>
                                            <tr><th class="bg-transparent">Contract Value</th><td class="text-end"><?php echo number_format((float)($costSummary['contract_value'] ?? 0), 2); ?></td></tr>
                                            <tr><th class="bg-transparent">Approved Change Orders</th><td class="text-end"><?php echo number_format((float)($costSummary['approved_change_orders'] ?? 0), 2); ?></td></tr>
                                            <tr><th class="bg-transparent">Revised Contract Total</th><td class="text-end"><?php echo number_format((float)($costSummary['revised_contract_value'] ?? $costSummary['projected_total'] ?? 0), 2); ?></td></tr>
                                            <tr><th class="bg-transparent">Committed Budget</th><td class="text-end"><?php echo number_format((float)($costSummary['committed_budget'] ?? 0), 2); ?></td></tr>
                                            <tr><th class="bg-transparent">Remaining Budget</th><td class="text-end"><?php echo number_format((float)($costSummary['remaining_budget'] ?? 0), 2); ?></td></tr>
                                            <tr><th class="bg-transparent">Variance</th><td class="text-end"><?php echo number_format((float)($costSummary['variance'] ?? 0), 2); ?></td></tr>
                                            <tr><th class="bg-transparent">Budget Utilization</th><td class="text-end"><?php echo number_format((float)($costSummary['budget_utilization_percent'] ?? 0), 1); ?>%</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="schedule-pane" role="tabpanel" aria-labelledby="schedule-tab">
                <div class="card border-0 bg-light-subtle mb-4">
                    <div class="card-body">
                        <h5 class="fw-bold mb-3">Create Schedule Activity</h5>
                        <form method="post" action="/ERP/public/projects/schedule/save" class="row g-3">
                            <input type="hidden" name="project_id" value="<?php echo (int)($project['id'] ?? 0); ?>">
                            <div class="col-md-4"><label class="form-label">Task</label><input class="form-control" name="task_name" required></div>
                            <div class="col-md-2"><label class="form-label">Start</label><input class="form-control" type="date" name="schedule_start_date" required></div>
                            <div class="col-md-2"><label class="form-label">End</label><input class="form-control" type="date" name="schedule_end_date" required></div>
                            <div class="col-md-2"><label class="form-label">Assigned to</label><input class="form-control" name="assigned_to"></div>
                            <div class="col-md-2"><label class="form-label">Initial progress</label><input class="form-control" type="number" min="0" max="100" name="schedule_progress_percent" value="0"></div>
                            <div class="col-md-3"><label class="form-label">Status</label><select class="form-select" name="schedule_status"><option value="planned">Planned</option><option value="in_progress">In progress</option><option value="on_hold">On hold</option><option value="completed">Completed</option></select></div>
                            <div class="col-md-7"><label class="form-label">Notes</label><input class="form-control" name="schedule_notes"></div>
                            <div class="col-md-2 d-flex align-items-end"><button class="btn btn-primary w-100" type="submit">Create Schedule</button></div>
                        </form>
                    </div>
                </div>
                <div class="card border-0 bg-light-subtle">
                    <div class="card-body">
                        <h5 class="fw-bold mb-3">Schedule Monitor</h5>
                        <?php if (empty($schedule)): ?>
                            <p class="text-muted mb-0">No schedule items have been added yet.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0">
                                    <thead><tr><th>Task</th><th>Dates</th><th>Status</th><th>Progress</th><th>Owner</th></tr></thead>
                                    <tbody>
                                        <?php foreach ($schedule as $item): ?>
                                            <?php
                                                $scheduleStatus = (string)($item['status'] ?? 'planned');
                                                $scheduleIsCompleted = $scheduleStatus === 'completed' || (int)($item['progress_percent'] ?? 0) >= 100;
                                                $scheduleIsOverdue = !$scheduleIsCompleted && !empty($item['end_date']) && $item['end_date'] < date('Y-m-d');
                                                $scheduleRowClass = $scheduleIsCompleted ? 'table-success' : ($scheduleIsOverdue ? 'table-danger' : '');
                                            ?>
                                            <tr class="<?php echo $scheduleRowClass; ?>">
                                                <td><?php echo htmlspecialchars($item['task_name'] ?? ''); ?></td>
                                                <td><?php echo htmlspecialchars(($item['start_date'] ?? '') . ' to ' . ($item['end_date'] ?? '')); ?></td>
                                                <td><span class="badge <?php echo $scheduleIsCompleted ? 'bg-success' : ($scheduleIsOverdue ? 'bg-danger' : 'bg-secondary'); ?>"><?php echo $scheduleIsCompleted ? 'Completed' : ($scheduleIsOverdue ? 'Overdue' : htmlspecialchars(ucwords(str_replace('_', ' ', $scheduleStatus)))); ?></span></td>
                                                <td style="min-width: 140px"><div class="progress"><div class="progress-bar" style="width: <?php echo (int)($item['progress_percent'] ?? 0); ?>%"><?php echo (int)($item['progress_percent'] ?? 0); ?>%</div></div></td>
                                                <td><?php echo htmlspecialchars($item['assigned_to'] ?? ''); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="daily-progress-pane" role="tabpanel" aria-labelledby="daily-progress-tab">
                <div class="row g-4">
                    <div class="col-lg-6">
                        <div class="card border-0 bg-light-subtle h-100">
                            <div class="card-body">
                                <h5 class="fw-bold mb-3">Record Daily Progress</h5>
                                <form method="post" action="/ERP/public/projects/daily-progress/add">
                                    <input type="hidden" name="project_id" value="<?php echo (int)($project['id'] ?? 0); ?>">
                                    <div class="row g-3">
                                        <div class="col-md-6"><label class="form-label">Report Date</label><input class="form-control" type="date" name="report_date" value="<?php echo date('Y-m-d'); ?>" required></div>
                                        <div class="col-md-6"><label class="form-label">Progress %</label><input class="form-control" type="number" min="0" max="100" name="progress_percent" value="<?php echo (int)($project['progress_percent'] ?? 0); ?>" required></div>
                                        <div class="col-12"><label class="form-label">Schedule task</label><select class="form-select" name="schedule_id"><option value="0">Overall project progress</option><?php foreach ($schedule ?? [] as $item): ?><option value="<?php echo (int)$item['id']; ?>"><?php echo htmlspecialchars($item['task_name'] . ' (' . $item['start_date'] . ' to ' . $item['end_date'] . ')'); ?></option><?php endforeach; ?></select></div>
                                        <div class="col-md-6"><label class="form-label">Manpower</label><input class="form-control" type="number" min="0" name="manpower_count" value="0"></div>
                                        <div class="col-md-6"><label class="form-label">Weather</label><input class="form-control" name="weather_condition" value="Normal"></div>
                                        <div class="col-md-6"><label class="form-label">Site Condition</label><input class="form-control" name="site_condition" value="Stable"></div>
                                        <div class="col-12"><label class="form-label">Activities Completed</label><textarea class="form-control" name="activities_completed" rows="3" required></textarea></div>
                                        <div class="col-12"><label class="form-label">Remarks</label><textarea class="form-control" name="remarks" rows="3"></textarea></div>
                                        <div class="col-12 border-top pt-3"><div class="form-check"><input class="form-check-input" type="checkbox" id="delayReported" name="delay_reported" value="1"><label class="form-check-label" for="delayReported">This progress report is delayed and requires rescheduling</label></div></div>
                                        <div class="col-md-4 delay-fields d-none"><label class="form-label">Delay days</label><input class="form-control" type="number" min="1" name="delay_days" value="1"></div>
                                        <div class="col-md-8 delay-fields d-none"><label class="form-label">Delay reason</label><input class="form-control" name="delay_reason" placeholder="Material shortage, weather, approval, etc."></div>
                                        <div class="col-12 delay-fields d-none"><label class="form-label">Delay details</label><textarea class="form-control" name="delay_details" rows="2"></textarea><div class="form-text">The affected schedule task will be moved forward by the delay days.</div></div>
                                    </div>
                                    <button class="btn btn-success mt-3" type="submit">Save Daily Progress</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card border-0 bg-light-subtle h-100">
                            <div class="card-body">
                                <h5 class="fw-bold mb-3">Site Progress Log</h5>
                                <?php if (empty($dailyProgress)): ?>
                                    <p class="text-muted mb-0">No daily site progress has been captured yet.</p>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm align-middle mb-0">
                                            <thead><tr><th>Date</th><th>Task</th><th>%</th><th>Manpower</th><th>Condition</th></tr></thead>
                                            <tbody>
                                                <?php foreach ($dailyProgress as $entry): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($entry['report_date'] ?? ''); ?></td>
                                                        <td><?php echo htmlspecialchars($entry['task_name'] ?? 'Overall project'); ?></td>
                                                        <td><?php echo (int)($entry['progress_percent'] ?? 0); ?>%</td>
                                                        <td><?php echo (int)($entry['manpower_count'] ?? 0); ?></td>
                                                        <td><?php echo htmlspecialchars($entry['site_condition'] ?? 'Stable'); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($delayLogs)): ?>
                                    <h6 class="fw-bold mt-4">Delay Log</h6>
                                    <div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead><tr><th>Date</th><th>Task</th><th>Delay</th><th>Reason</th></tr></thead><tbody>
                                        <?php foreach ($delayLogs as $delay): ?><tr><td><?php echo htmlspecialchars($delay['delay_date'] ?? ''); ?></td><td><?php echo htmlspecialchars($delay['task_name'] ?? ''); ?></td><td><?php echo (int)($delay['delay_days'] ?? 0); ?> day(s)</td><td><?php echo htmlspecialchars($delay['reason'] ?? ''); ?></td></tr><?php endforeach; ?>
                                    </tbody></table></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="productivity-pane" role="tabpanel" aria-labelledby="productivity-tab">
                <div class="row g-4">
                    <div class="col-lg-5">
                        <div class="card border-0 bg-light-subtle h-100">
                            <div class="card-body">
                                <h5 class="fw-bold mb-3">Track Labor Productivity</h5>
                                <form method="post" action="/ERP/public/projects/productivity/add">
                                    <input type="hidden" name="project_id" value="<?php echo (int)($project['id'] ?? 0); ?>">
                                    <div class="row g-3">
                                        <div class="col-md-6"><label class="form-label">Report Date</label><input class="form-control" type="date" name="report_date" value="<?php echo date('Y-m-d'); ?>" required></div>
                                        <div class="col-md-6"><label class="form-label">Trade</label><input class="form-control" name="trade" placeholder="Brickwork" required></div>
                                        <div class="col-md-6"><label class="form-label">Crew Size</label><input class="form-control" type="number" min="0" name="crew_size" value="0"></div>
                                        <div class="col-md-6"><label class="form-label">Unit</label><input class="form-control" name="unit_of_measure" value="m2"></div>
                                        <div class="col-md-6"><label class="form-label">Planned Output</label><input class="form-control" type="number" min="0" step="0.01" name="planned_output" value="0"></div>
                                        <div class="col-md-6"><label class="form-label">Actual Output</label><input class="form-control" type="number" min="0" step="0.01" name="actual_output" value="0"></div>
                                        <div class="col-12"><label class="form-label">Notes</label><textarea class="form-control" name="notes" rows="3"></textarea></div>
                                    </div>
                                    <button class="btn btn-primary mt-3" type="submit">Save Productivity</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-7">
                        <div class="card border-0 bg-light-subtle h-100">
                            <div class="card-body">
                                <h5 class="fw-bold mb-3">Productivity Register</h5>
                                <?php if (empty($productivity)): ?>
                                    <p class="text-muted mb-0">No productivity logs have been captured yet.</p>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm align-middle mb-0">
                                            <thead><tr><th>Date</th><th>Trade</th><th>Crew</th><th>Planned</th><th>Actual</th><th>Variance</th></tr></thead>
                                            <tbody>
                                                <?php foreach ($productivity as $entry): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($entry['report_date'] ?? ''); ?></td>
                                                        <td><?php echo htmlspecialchars($entry['trade'] ?? ''); ?></td>
                                                        <td><?php echo (int)($entry['crew_size'] ?? 0); ?></td>
                                                        <td><?php echo number_format((float)($entry['planned_output'] ?? 0), 2); ?> <?php echo htmlspecialchars($entry['unit_of_measure'] ?? 'm2'); ?></td>
                                                        <td><?php echo number_format((float)($entry['actual_output'] ?? 0), 2); ?> <?php echo htmlspecialchars($entry['unit_of_measure'] ?? 'm2'); ?></td>
                                                        <td><?php echo number_format((float)($entry['actual_output'] ?? 0) - (float)($entry['planned_output'] ?? 0), 2); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="forecast-pane" role="tabpanel" aria-labelledby="forecast-tab">
                <?php
                    $assignedCount = count($assignments ?? []);
                    $forecastVariance = (float)($costSummary['variance'] ?? 0);
                    $latestProgress = max(0, min(100, (int)($project['progress_percent'] ?? 0)));
                    $avgProductivity = 0.0;
                    if (!empty($productivity)) {
                        $totalRate = 0.0;
                        foreach ($productivity as $entry) {
                            $planned = (float)($entry['planned_output'] ?? 0);
                            $actual = (float)($entry['actual_output'] ?? 0);
                            $totalRate += $planned > 0 ? ($actual / $planned) : 0;
                        }
                        $avgProductivity = count($productivity) > 0 ? ($totalRate / count($productivity)) * 100 : 0;
                    }
                ?>
                <div class="row g-4">
                    <div class="col-lg-5">
                        <div class="card border-0 bg-light-subtle h-100">
                            <div class="card-body">
                                <h5 class="fw-bold mb-3">Forecast Summary</h5>
                                <div class="table-responsive">
                                    <table class="table table-sm align-middle mb-0">
                                        <tbody>
                                            <tr><th class="bg-transparent">Progress to date</th><td class="text-end"><?php echo $latestProgress; ?>%</td></tr>
                                            <tr><th class="bg-transparent">Budget variance</th><td class="text-end"><?php echo number_format($forecastVariance, 2); ?></td></tr>
                                            <tr><th class="bg-transparent">Average productivity</th><td class="text-end"><?php echo number_format($avgProductivity, 1); ?>%</td></tr>
                                            <tr><th class="bg-transparent">Assigned staff</th><td class="text-end"><?php echo $assignedCount; ?></td></tr>
                                            <tr><th class="bg-transparent">Projected completion</th><td class="text-end"><?php echo $latestProgress >= 100 ? 'Completed' : 'Monitor schedule'; ?></td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-7">
                        <div class="card border-0 bg-light-subtle h-100">
                            <div class="card-body">
                                <h5 class="fw-bold mb-3">Resource Allocation</h5>
                                <?php if (empty($assignments)): ?>
                                    <p class="text-muted mb-0">No project team has been assigned yet.</p>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm align-middle mb-0">
                                            <thead><tr><th>Resource</th><th>Role</th><th>Department</th></tr></thead>
                                            <tbody>
                                                <?php foreach ($assignments as $assignment): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars(($assignment['first_name'] ?? '') . ' ' . ($assignment['last_name'] ?? '')); ?></td>
                                                        <td><?php echo htmlspecialchars($assignment['job_title'] ?? ''); ?></td>
                                                        <td><?php echo htmlspecialchars($assignment['department'] ?? 'General'); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="procurement-pane" role="tabpanel" aria-labelledby="procurement-tab">
                <div class="row g-4">
                    <div class="col-lg-6">
                        <div class="card border-0 bg-light-subtle h-100">
                            <div class="card-body">
                                <h5 class="fw-bold mb-3">Project Purchase Orders</h5>
                                <?php if (empty($procurementOrders)): ?>
                                    <p class="text-muted mb-0">No purchase orders are linked to this project yet.</p>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm align-middle mb-0">
                                            <thead><tr><th>PO</th><th>Supplier</th><th>Date</th><th>Value</th><th>Status</th></tr></thead>
                                            <tbody>
                                                <?php foreach ($procurementOrders as $po): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($po['po_number'] ?? ''); ?></td>
                                                        <td><?php echo htmlspecialchars($po['supplier'] ?? 'Unassigned'); ?></td>
                                                        <td><?php echo htmlspecialchars($po['order_date'] ?? ''); ?></td>
                                                        <td><?php echo number_format((float)($po['total_amount'] ?? 0), 2); ?></td>
                                                        <td><span class="badge bg-light text-dark"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', (string)($po['workflow_status'] ?? $po['status'] ?? 'draft')))); ?></span></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card border-0 bg-light-subtle h-100">
                            <div class="card-body">
                                <h5 class="fw-bold mb-3">Received Materials</h5>
                                <?php if (empty($materialReceipts)): ?>
                                    <p class="text-muted mb-0">No material receipts have been recorded for this project.</p>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm align-middle mb-0">
                                            <thead><tr><th>GRN</th><th>PO</th><th>Supplier</th><th>Qty</th><th>Date</th></tr></thead>
                                            <tbody>
                                                <?php foreach ($materialReceipts as $receipt): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($receipt['receipt_number'] ?? ''); ?></td>
                                                        <td><?php echo htmlspecialchars($receipt['po_number'] ?? ''); ?></td>
                                                        <td><?php echo htmlspecialchars($receipt['supplier'] ?? ''); ?></td>
                                                        <td><?php echo number_format((float)($receipt['quantity_received'] ?? 0), 2); ?></td>
                                                        <td><?php echo htmlspecialchars($receipt['received_at'] ?? ''); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="documents-pane" role="tabpanel" aria-labelledby="documents-tab">
                <div class="card border-0 bg-light-subtle">
                    <div class="card-body">
                        <h5 class="fw-bold mb-3">Project Documents</h5>
                        <?php if (empty($documents)): ?>
                            <p class="text-muted mb-0">No documents uploaded for this project.</p>
                        <?php else: ?>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($documents as $document): ?>
                                    <li class="list-group-item px-0 bg-transparent d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="fw-semibold"><?php echo htmlspecialchars($document['label'] ?? ''); ?></div>
                                            <div class="small text-muted"><?php echo htmlspecialchars($document['original_name'] ?? ''); ?></div>
                                        </div>
                                        <a class="btn btn-sm btn-outline-primary" href="/ERP/public/modules/projects/document?id=<?php echo (int)$document['id']; ?>" target="_blank" rel="noreferrer">Download</a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="rfi-pane" role="tabpanel" aria-labelledby="rfi-tab">
                <div class="row g-4">
                    <div class="col-lg-6">
                        <div class="card border-0 bg-light-subtle h-100">
                            <div class="card-body">
                                <h5 class="fw-bold mb-3">Issue New RFI</h5>
                                <form method="post" action="/ERP/public/projects/rfi/add">
                                    <input type="hidden" name="project_id" value="<?php echo (int)($project['id'] ?? 0); ?>">
                                    <div class="row g-3">
                                        <div class="col-md-6"><label class="form-label">RFI No.</label><input class="form-control" name="rfi_no" placeholder="RFI-2026001"></div>
                                        <div class="col-md-6"><label class="form-label">Status</label><select class="form-select" name="status"><option value="draft">Draft</option><option value="submitted" selected>Submitted</option><option value="under_review">Under review</option><option value="approved">Approved</option><option value="rejected">Rejected</option><option value="closed">Closed</option></select></div>
                                        <div class="col-12"><label class="form-label">Title</label><input class="form-control" name="title" required></div>
                                        <div class="col-md-6"><label class="form-label">Drawing Ref.</label><input class="form-control" name="drawing_ref" placeholder="A-101"></div>
                                        <div class="col-md-6"><label class="form-label">Issued By</label><input class="form-control" name="issued_by" placeholder="Site Engineer"></div>
                                        <div class="col-md-6"><label class="form-label">Due Date</label><input class="form-control" type="date" name="due_date"></div>
                                        <div class="col-12"><label class="form-label">Question / Description</label><textarea class="form-control" name="description" rows="3" required></textarea></div>
                                        <div class="col-12"><label class="form-label">Response / Resolution</label><textarea class="form-control" name="response" rows="3"></textarea></div>
                                    </div>
                                    <button class="btn btn-primary mt-3" type="submit">Save RFI</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card border-0 bg-light-subtle h-100">
                            <div class="card-body">
                                <h5 class="fw-bold mb-3">RFI Register</h5>
                                <?php if (empty($rfis)): ?>
                                    <p class="text-muted mb-0">No RFIs have been raised for this project.</p>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm align-middle mb-0">
                                            <thead><tr><th>No.</th><th>Title</th><th>Status</th><th>Due</th><th>Update</th></tr></thead>
                                            <tbody>
                                                <?php foreach ($rfis as $rfi): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($rfi['rfi_no'] ?? ''); ?></td>
                                                        <td><?php echo htmlspecialchars($rfi['title'] ?? ''); ?></td>
                                                        <td><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', (string)($rfi['status'] ?? 'submitted')))); ?></td>
                                                        <td><?php echo htmlspecialchars($rfi['due_date'] ?? ''); ?></td>
                                                        <td>
                                                            <form method="post" action="/ERP/public/projects/rfi/update" class="d-flex gap-2 align-items-center">
                                                                <input type="hidden" name="project_id" value="<?php echo (int)($project['id'] ?? 0); ?>">
                                                                <input type="hidden" name="rfi_id" value="<?php echo (int)($rfi['id'] ?? 0); ?>">
                                                                <select class="form-select form-select-sm" name="status">
                                                                    <option value="submitted" <?php echo ((string)($rfi['status'] ?? 'submitted') === 'submitted') ? 'selected' : ''; ?>>Submitted</option>
                                                                    <option value="under_review" <?php echo ((string)($rfi['status'] ?? 'submitted') === 'under_review') ? 'selected' : ''; ?>>Under review</option>
                                                                    <option value="approved" <?php echo ((string)($rfi['status'] ?? 'submitted') === 'approved') ? 'selected' : ''; ?>>Approved</option>
                                                                    <option value="rejected" <?php echo ((string)($rfi['status'] ?? 'submitted') === 'rejected') ? 'selected' : ''; ?>>Rejected</option>
                                                                    <option value="closed" <?php echo ((string)($rfi['status'] ?? 'submitted') === 'closed') ? 'selected' : ''; ?>>Closed</option>
                                                                </select>
                                                                <button type="submit" class="btn btn-sm btn-outline-primary">Update</button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="change-orders-pane" role="tabpanel" aria-labelledby="change-orders-tab">
                <div class="row g-4">
                    <div class="col-lg-6">
                        <div class="card border-0 bg-light-subtle h-100">
                            <div class="card-body">
                                <h5 class="fw-bold mb-3">Add Change Order</h5>
                                <form method="post" action="/ERP/public/projects/change-order/add">
                                    <input type="hidden" name="project_id" value="<?php echo (int)($project['id'] ?? 0); ?>">
                                    <div class="row g-3">
                                        <div class="col-md-6"><label class="form-label">Order No.</label><input class="form-control" name="change_order_no" placeholder="CO-2026001"></div>
                                        <div class="col-md-6"><label class="form-label">Status</label><select class="form-select" name="status"><option value="draft">Draft</option><option value="pending_approval" selected>Pending approval</option><option value="approved">Approved</option><option value="rejected">Rejected</option><option value="applied">Applied</option></select></div>
                                        <div class="col-12"><label class="form-label">Title</label><input class="form-control" name="title" required></div>
                                        <div class="col-12"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="3"></textarea></div>
                                        <div class="col-md-6"><label class="form-label">Amount</label><input class="form-control" type="number" min="0" step="0.01" name="amount" value="0"></div>
                                        <div class="col-md-6"><label class="form-label">Effective Date</label><input class="form-control" type="date" name="effective_date"></div>
                                    </div>
                                    <button class="btn btn-primary mt-3" type="submit">Save Change Order</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card border-0 bg-light-subtle h-100">
                            <div class="card-body">
                                <h5 class="fw-bold mb-3">Change Order Log</h5>
                                <?php if (empty($changeOrders)): ?>
                                    <p class="text-muted mb-0">No change orders logged yet.</p>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm align-middle mb-0">
                                            <thead><tr><th>No.</th><th>Title</th><th>Amount</th><th>Status</th><th>Update</th></tr></thead>
                                            <tbody>
                                                <?php foreach ($changeOrders as $changeOrder): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($changeOrder['change_order_no'] ?? ''); ?></td>
                                                        <td><?php echo htmlspecialchars($changeOrder['title'] ?? ''); ?></td>
                                                        <td><?php echo number_format((float)($changeOrder['amount'] ?? 0), 2); ?></td>
                                                        <td><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', (string)($changeOrder['status'] ?? 'draft')))); ?></td>
                                                        <td>
                                                            <form method="post" action="/ERP/public/projects/change-order/update" class="d-flex gap-2 align-items-center">
                                                                <input type="hidden" name="project_id" value="<?php echo (int)($project['id'] ?? 0); ?>">
                                                                <input type="hidden" name="change_order_id" value="<?php echo (int)($changeOrder['id'] ?? 0); ?>">
                                                                <select class="form-select form-select-sm" name="status">
                                                                    <option value="pending_approval" <?php echo ((string)($changeOrder['status'] ?? 'pending_approval') === 'pending_approval') ? 'selected' : ''; ?>>Pending</option>
                                                                    <option value="approved" <?php echo ((string)($changeOrder['status'] ?? 'pending_approval') === 'approved') ? 'selected' : ''; ?>>Approve</option>
                                                                    <option value="rejected" <?php echo ((string)($changeOrder['status'] ?? 'pending_approval') === 'rejected') ? 'selected' : ''; ?>>Reject</option>
                                                                    <option value="applied" <?php echo ((string)($changeOrder['status'] ?? 'pending_approval') === 'applied') ? 'selected' : ''; ?>>Apply</option>
                                                                </select>
                                                                <button type="submit" class="btn btn-sm btn-outline-primary">Update</button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="safety-pane" role="tabpanel" aria-labelledby="safety-tab">
                <div class="row g-4">
                    <div class="col-lg-6">
                        <div class="card border-0 bg-light-subtle h-100">
                            <div class="card-body">
                                <h5 class="fw-bold mb-3">Add Safety / Compliance Log</h5>
                                <form method="post" action="/ERP/public/projects/safety-log/add">
                                    <input type="hidden" name="project_id" value="<?php echo (int)($project['id'] ?? 0); ?>">
                                    <div class="row g-3">
                                        <div class="col-md-6"><label class="form-label">Log No.</label><input class="form-control" name="log_no" placeholder="SAF-2026001"></div>
                                        <div class="col-md-6"><label class="form-label">Category</label><select class="form-select" name="category"><option value="safety">Safety</option><option value="compliance">Compliance</option><option value="incident">Incident</option></select></div>
                                        <div class="col-md-6"><label class="form-label">Severity</label><select class="form-select" name="severity"><option value="low">Low</option><option value="medium" selected>Medium</option><option value="high">High</option><option value="critical">Critical</option></select></div>
                                        <div class="col-md-6"><label class="form-label">Status</label><select class="form-select" name="status"><option value="open" selected>Open</option><option value="under_review">Under review</option><option value="resolved">Resolved</option><option value="closed">Closed</option></select></div>
                                        <div class="col-12"><label class="form-label">Title</label><input class="form-control" name="title" required></div>
                                        <div class="col-md-6"><label class="form-label">Incident Date</label><input class="form-control" type="date" name="incident_date"></div>
                                        <div class="col-12"><label class="form-label">Notes</label><textarea class="form-control" name="notes" rows="3"></textarea></div>
                                    </div>
                                    <button class="btn btn-warning mt-3" type="submit">Save Safety Log</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card border-0 bg-light-subtle h-100">
                            <div class="card-body">
                                <h5 class="fw-bold mb-3">Safety & Compliance Log</h5>
                                <?php if (empty($safetyLogs)): ?>
                                    <p class="text-muted mb-0">No safety or compliance logs recorded.</p>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm align-middle mb-0">
                                            <thead><tr><th>No.</th><th>Title</th><th>Severity</th><th>Status</th></tr></thead>
                                            <tbody>
                                                <?php foreach ($safetyLogs as $log): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($log['log_no'] ?? ''); ?></td>
                                                        <td><?php echo htmlspecialchars($log['title'] ?? ''); ?></td>
                                                        <td><?php echo htmlspecialchars(ucfirst((string)($log['severity'] ?? 'medium'))); ?></td>
                                                        <td><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', (string)($log['status'] ?? 'open')))); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('delayReported')?.addEventListener('change', function () {
        document.querySelectorAll('.delay-fields').forEach(function (field) { field.classList.toggle('d-none', !this.checked); }, this);
    });
</script>
