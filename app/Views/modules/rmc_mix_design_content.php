<?php $mix = $mixDesignData ?? []; $summary = $mix['summary'] ?? []; ?>
<div class="card shadow-sm border-0">
    <div class="card-body p-4">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
            <div>
                <span class="badge text-bg-success mb-2">Concrete Design and Trial Approval</span>
                <h4 class="fw-bold mb-1">Mix Design</h4>
                <p class="text-muted mb-0">Manage proportioning, trial batches, approval gates, revision control, and production readiness for ready-mix concrete.</p>
            </div>
            <a class="btn btn-outline-secondary" href="/ERP/public/modules"><i class="bi bi-grid me-1"></i>Module Center</a>
        </div>
        <?php if (!empty($_SESSION['rmc_flash'])): ?><div class="alert alert-info"><?php echo htmlspecialchars($_SESSION['rmc_flash']); unset($_SESSION['rmc_flash']); ?></div><?php endif; ?>

        <div class="row g-3 mb-4">
            <?php foreach ([['total_designs', 'Total designs', 'bi-bezier2'], ['approved_designs', 'Approved designs', 'bi-check-circle'], ['pending_approval', 'Pending review', 'bi-hourglass-split'], ['average_wc_ratio', 'Average w/c ratio', 'bi-droplet'], ['avg_target_strength', 'Avg target strength', 'bi-clipboard-data']] as [$key,$label,$icon]): ?>
                <div class="col-6 col-lg-2">
                    <div class="border rounded p-3 h-100">
                        <i class="bi <?php echo $icon; ?> text-success"></i>
                        <div class="small text-muted mt-2"><?php echo $label; ?></div>
                        <strong>
                            <?php
                            $value = (float)($summary[$key] ?? 0);
                            if ($key === 'average_wc_ratio' || $key === 'avg_target_strength') {
                                echo number_format($value, 2);
                            } else {
                                echo (int)$value;
                            }
                            ?>
                        </strong>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-lg-6">
                <div class="border rounded p-3 h-100">
                    <h6 class="fw-semibold mb-3">Approved for production</h6>
                    <?php if (empty($mix['approvedMixes'])): ?>
                        <div class="text-muted small">No approved concrete mix designs are currently released for production.</div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach (($mix['approvedMixes'] ?? []) as $approved): ?>
                                <div class="list-group-item px-0">
                                    <div class="d-flex justify-content-between align-items-center gap-2">
                                        <strong><?php echo htmlspecialchars((string)($approved['mix_code'] ?? '')); ?></strong>
                                        <span class="badge text-bg-success">Approved</span>
                                    </div>
                                    <div class="small text-muted mt-1"><?php echo htmlspecialchars((string)($approved['concrete_grade'] ?? '')); ?> · Target <?php echo number_format((float)($approved['target_strength_mpa'] ?? 0), 2); ?> MPa · W/C <?php echo number_format((float)($approved['water_cement_ratio'] ?? 0), 3); ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="border rounded p-3 h-100">
                    <h6 class="fw-semibold mb-3">Approval queue</h6>
                    <?php if (empty($mix['approvalQueue'])): ?>
                        <div class="text-muted small">All mix designs are in a stable, released state.</div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach (($mix['approvalQueue'] ?? []) as $queued): ?>
                                <div class="list-group-item px-0">
                                    <div class="d-flex justify-content-between align-items-center gap-2">
                                        <strong><?php echo htmlspecialchars((string)($queued['mix_code'] ?? '')); ?></strong>
                                        <span class="badge text-bg-warning"><?php echo htmlspecialchars((string)($queued['status'] ?? '')); ?></span>
                                    </div>
                                    <div class="small text-muted mt-1"><?php echo htmlspecialchars((string)($queued['concrete_grade'] ?? '')); ?> · Revision <?php echo (int)($queued['revision_no'] ?? 1); ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <ul class="nav nav-tabs" role="tablist">
            <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#mixForm" type="button">Design formulation</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#mixTrial" type="button">Trial & quality</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#mixHistory" type="button">Design history</button></li>
        </ul>

        <div class="tab-content border border-top-0 rounded-bottom p-3">
            <div class="tab-pane fade show active" id="mixForm">
                <div class="row g-4">
                    <div class="col-lg-6">
                        <h5>Create or revise mix design</h5>
                        <form method="post" action="/ERP/public/modules/rmc/mix-design/create" class="row g-2">
                            <input class="form-control" name="mix_code" placeholder="Mix code e.g. M30-01" required>
                            <input class="form-control" name="concrete_grade" placeholder="Concrete grade e.g. M30" required>
                            <input class="form-control" name="project_reference" placeholder="Project or work package reference">
                            <input class="form-control" name="design_method" placeholder="Design method / standard (ACI, DOE, IS, etc.)">
                            <input class="form-control" name="target_strength_mpa" type="number" step="0.01" min="0" placeholder="Target strength (MPa)" required>
                            <input class="form-control" name="slump_mm" type="number" step="0.01" min="0" placeholder="Slump (mm)">
                            <input class="form-control" name="water_cement_ratio" type="number" step="0.001" min="0.01" placeholder="Water/cement ratio" required>
                            <input class="form-control" name="cement_kg_m3" type="number" step="0.01" min="0" placeholder="Cement (kg/m3)" required>
                            <input class="form-control" name="fine_aggregate_kg_m3" type="number" step="0.01" min="0" placeholder="Fine aggregate (kg/m3)">
                            <input class="form-control" name="coarse_aggregate_kg_m3" type="number" step="0.01" min="0" placeholder="Coarse aggregate (kg/m3)">
                            <input class="form-control" name="water_kg_m3" type="number" step="0.01" min="0" placeholder="Water (kg/m3)">
                            <input class="form-control" name="admixture_kg_m3" type="number" step="0.01" min="0" placeholder="Admixture (kg/m3)">
                            <input class="form-control" name="max_aggregate_size_mm" type="number" step="0.01" min="0" placeholder="Max aggregate size (mm)">
                            <input class="form-control" name="air_content_percent" type="number" step="0.01" min="0" placeholder="Air content (%)">
                            <input class="form-control" name="batch_volume_m3" type="number" step="0.01" min="0" placeholder="Batch volume (m3)">
                            <input class="form-control" name="trial_number" placeholder="Trial batch / lab batch number">
                            <input class="form-control" name="revision_no" type="number" min="1" value="1" placeholder="Revision number">
                            <select class="form-select" name="status">
                                <option value="draft">Draft</option>
                                <option value="trial">Trial in progress</option>
                                <option value="pending_approval">Pending approval</option>
                                <option value="approved">Approved for production</option>
                                <option value="rejected">Rejected</option>
                                <option value="archived">Archived</option>
                            </select>
                            <input class="form-control" name="approved_by" placeholder="Approved by / engineering reviewer">
                            <textarea class="form-control" name="notes" rows="3" placeholder="Design rationale, material notes, production considerations, and special requirements"></textarea>
                            <button class="btn btn-success">Save mix design</button>
                        </form>
                    </div>

                    <div class="col-lg-6">
                        <h5>Design intent and production workflow</h5>
                        <div class="border rounded p-3 mb-3 bg-light">
                            <strong>Design checklist</strong>
                            <ul class="mb-0 mt-2 small">
                                <li>Confirm target strength and service exposure condition.</li>
                                <li>Check workability, slump range, and placement method.</li>
                                <li>Validate water-cement ratio against durability and curing needs.</li>
                                <li>Match aggregate grading and admixture dosage for pumpability.</li>
                                <li>Align approved design with QC tests and production dispatch batches.</li>
                            </ul>
                        </div>

                        <div class="border rounded p-3 bg-light">
                            <strong>Approval gate</strong>
                            <ul class="mb-0 mt-2 small">
                                <li>Draft or trial: engineering design under development.</li>
                                <li>Pending approval: lab results or site trial are being reviewed.</li>
                                <li>Approved: released for production and dispatch planning.</li>
                                <li>Rejected: update formula or revise materials before reuse.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="mixTrial">
                <div class="row g-4">
                    <div class="col-lg-6">
                        <h5>Trial batch acceptance criteria</h5>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle">
                                <thead><tr><th>Parameter</th><th>Target</th><th>QC linkage</th></tr></thead>
                                <tbody>
                                    <tr><td>Compressive strength</td><td>Meets target MPa at 7/28 days</td><td>Cube test / lab verification</td></tr>
                                    <tr><td>Slump</td><td>Within placement range</td><td>Workability test</td></tr>
                                    <tr><td>Water-cement ratio</td><td>Within design range</td><td>Durability watchpoint</td></tr>
                                    <tr><td>Air content</td><td>Within specification</td><td>Weather and freeze-thaw control</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <h5>Operational notes</h5>
                        <div class="border rounded p-3 bg-light small">
                            <p class="mb-2">For ready-mix operations, a mix design is not only a formula; it is a controlled release for production. It should reference the job requirement, expected placement method, curing conditions, and the quality control tests used to confirm compliance.</p>
                            <p class="mb-0">Use design revisions to track material changes, water adjustments, admixture updates, or source substitutions without losing traceability.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="mixHistory">
                <h5>Recent mix designs</h5>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th>Mix code</th>
                                <th>Grade</th>
                                <th>Target strength</th>
                                <th>W/C</th>
                                <th>Cement</th>
                                <th>Slump</th>
                                <th>Status</th>
                                <th>Revision</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (($mix['mixes'] ?? []) as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars((string)($row['mix_code'] ?? '')); ?></td>
                                    <td><?php echo htmlspecialchars((string)($row['concrete_grade'] ?? '')); ?></td>
                                    <td><?php echo number_format((float)($row['target_strength_mpa'] ?? 0), 2); ?> MPa</td>
                                    <td><?php echo number_format((float)($row['water_cement_ratio'] ?? 0), 3); ?></td>
                                    <td><?php echo number_format((float)($row['cement_kg_m3'] ?? 0), 2); ?> kg/m3</td>
                                    <td><?php echo number_format((float)($row['slump_mm'] ?? 0), 2); ?> mm</td>
                                    <td><span class="badge text-bg-secondary"><?php echo htmlspecialchars((string)($row['status'] ?? '')); ?></span></td>
                                    <td><?php echo (int)($row['revision_no'] ?? 1); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    setTimeout(function () {
        window.location.reload();
    }, 30000);
</script>
<script>
    setTimeout(function () {
        window.location.reload();
    }, 30000);
</script>
