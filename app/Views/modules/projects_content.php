<div class="card shadow-sm border-0">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold mb-1">Projects</h2>
                <p class="text-muted mb-0">Manage projects for the selected company.</p>
            </div>
            <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#addProjectModal"><i class="bi bi-plus-lg me-1"></i>New Project</button>
        </div>

        <?php if (!empty($_SESSION['project_flash'])): ?>
            <div class="alert alert-info"><?php echo htmlspecialchars($_SESSION['project_flash']); unset($_SESSION['project_flash']); ?></div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead><tr><th>Project No.</th><th>Name</th><th>Client</th><th>Location</th><th>Dates</th><th>Progress</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                <?php if (empty($projects)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">No projects registered for this company.</td></tr>
                <?php else: foreach ($projects as $project): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($project['project_number']); ?></td>
                        <td class="fw-semibold"><?php echo htmlspecialchars($project['name']); ?></td>
                        <td><?php echo htmlspecialchars($project['client_name'] ?: 'Not specified'); ?></td>
                        <td><?php echo htmlspecialchars($project['site_location']); ?></td>
                        <td><?php echo htmlspecialchars($project['start_date'] . ' to ' . $project['end_date']); ?></td>
                        <td style="min-width: 130px"><div class="progress" role="progressbar" aria-valuenow="<?php echo (int)$project['progress_percent']; ?>" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar" style="width: <?php echo (int)$project['progress_percent']; ?>%"> <?php echo (int)$project['progress_percent']; ?>%</div></div></td>
                        <td><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $project['status']))); ?></td>
                        <td><a class="btn btn-sm btn-outline-primary" href="/ERP/public/modules/projects/view?id=<?php echo (int)$project['id']; ?>"><i class="bi bi-eye me-1"></i>View</a></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="addProjectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title">New Project</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
        <form method="post" action="/ERP/public/projects/save" enctype="multipart/form-data">
            <div class="modal-body"><div class="row g-3">
                <div class="col-md-6"><label class="form-label">Project Number</label><input class="form-control" name="project_number" placeholder="PRJ-001" required></div>
                <div class="col-md-6"><label class="form-label">Project Name</label><input class="form-control" name="name" required></div>
                <div class="col-md-6"><label class="form-label">Client Name</label><input class="form-control" name="client_name"></div>
                <div class="col-md-6"><label class="form-label">Consultant</label><input class="form-control" name="consultant"></div>
                <div class="col-md-6"><label class="form-label">Contract Value</label><input class="form-control" type="number" min="0" step="0.01" name="contract_value" value="0"></div>
                <div class="col-md-6"><label class="form-label">Budget</label><input class="form-control" type="number" min="0" step="0.01" name="budget" value="0"></div>
                <div class="col-md-4"><label class="form-label">Start Date</label><input class="form-control" type="date" name="start_date" required></div>
                <div class="col-md-4"><label class="form-label">End Date</label><input class="form-control" type="date" name="end_date" required></div>
                <div class="col-md-4"><label class="form-label">Progress %</label><input class="form-control" type="number" min="0" max="100" name="progress_percent" value="0"></div>
                <div class="col-md-8"><label class="form-label">Site Location</label><input class="form-control" name="site_location" required></div>
                <div class="col-md-4"><label class="form-label">Status</label><select class="form-select" name="status"><option value="planned">Planned</option><option value="in_progress">In progress</option><option value="completed">Completed</option><option value="on_hold">On hold</option><option value="cancelled">Cancelled</option></select></div>
                <div class="col-12"><hr><label class="form-label">Related project files</label><div id="projectDocuments"><div class="row g-2 mb-2 project-document-row"><div class="col-md-5"><input class="form-control" type="file" name="project_files[]" accept=".pdf,.jpg,.jpeg,.png,.webp,.txt,.doc,.docx,.xls,.xlsx"></div><div class="col-md-5"><input class="form-control" name="file_labels[]" placeholder="File label, e.g. Contract"></div><div class="col-md-2"><button class="btn btn-outline-secondary w-100" type="button" data-remove-project-file>Remove</button></div></div></div><button class="btn btn-sm btn-outline-primary" type="button" id="addProjectDocument"><i class="bi bi-plus-lg me-1"></i>Add another file</button><div class="form-text">Each file must have a label. Maximum 10 MB per file.</div></div>
            </div></div>
            <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Save Project</button></div>
        </form>
    </div></div>
</div>

<script>
    document.getElementById('addProjectDocument')?.addEventListener('click', function () {
        const container = document.getElementById('projectDocuments');
        const row = container.querySelector('.project-document-row').cloneNode(true);
        row.querySelectorAll('input').forEach(function (input) { input.value = ''; });
        container.appendChild(row);
    });
    document.addEventListener('click', function (event) {
        const button = event.target.closest('[data-remove-project-file]');
        if (!button) return;
        const rows = document.querySelectorAll('.project-document-row');
        if (rows.length > 1) button.closest('.project-document-row').remove();
    });
</script>
