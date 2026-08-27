<?php
$contracts = $contracts ?? [];
$events = $events ?? [];
$documents = $documents ?? [];
$statusLabels = [
    'opportunity' => 'Opportunity', 'tendering' => 'Tendering', 'under_evaluation' => 'Under evaluation',
    'awarded' => 'Awarded', 'active' => 'Active', 'completed' => 'Completed',
    'unsuccessful' => 'Unsuccessful', 'cancelled' => 'Cancelled',
];
$statusClasses = [
    'opportunity' => 'text-bg-secondary', 'tendering' => 'text-bg-info', 'under_evaluation' => 'text-bg-warning',
    'awarded' => 'text-bg-primary', 'active' => 'text-bg-success', 'completed' => 'text-bg-dark',
    'unsuccessful' => 'text-bg-danger', 'cancelled' => 'text-bg-danger',
];
$activeCount = count(array_filter($contracts, static fn (array $contract): bool => in_array($contract['status'], ['awarded', 'active'], true)));
$evaluationCount = count(array_filter($contracts, static fn (array $contract): bool => $contract['status'] === 'under_evaluation'));
$awardedValue = array_sum(array_map(static fn (array $contract): float => (float)$contract['awarded_value'], $contracts));
?>
<div class="card shadow-sm border-0">
    <div class="card-body p-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <p class="text-uppercase text-muted small fw-semibold mb-1">Commercial governance</p>
                <h2 class="fw-bold mb-1">Contract Admin</h2>
                <p class="text-muted mb-0">Control the tender-to-closeout record for building contracts.</p>
            </div>
            <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#newContractModal"><i class="bi bi-plus-lg me-1"></i>New contract record</button>
        </div>

        <?php if (!empty($_SESSION['contract_admin_flash'])): ?>
            <div class="alert alert-info"><?php echo htmlspecialchars($_SESSION['contract_admin_flash']); unset($_SESSION['contract_admin_flash']); ?></div>
        <?php endif; ?>

        <div class="row g-3 mb-4">
            <div class="col-md-4"><div class="border rounded p-3 h-100"><div class="small text-muted">Pipeline records</div><div class="fs-3 fw-bold"><?php echo count($contracts); ?></div><div class="small text-muted">Opportunities and concluded tenders</div></div></div>
            <div class="col-md-4"><div class="border rounded p-3 h-100"><div class="small text-muted">Awaiting evaluation</div><div class="fs-3 fw-bold text-warning"><?php echo $evaluationCount; ?></div><div class="small text-muted">Keep the scoring and decision trail attached</div></div></div>
            <div class="col-md-4"><div class="border rounded p-3 h-100"><div class="small text-muted">Awarded / active value</div><div class="fs-3 fw-bold text-success"><?php echo number_format($awardedValue, 2); ?></div><div class="small text-muted"><?php echo $activeCount; ?> awarded or active records</div></div></div>
        </div>

        <div class="alert alert-light border mb-4"><strong>Process record:</strong> add one dated milestone for tender issue, clarification, evaluation, approval, award, pre-start, instruction, variation, payment, claim, completion, or closeout. Files are stored under their contract and sorted by document date.</div>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>Contract</th><th>Client / contractor</th><th>Route</th><th>Value</th><th>Key dates</th><th>Status</th><th></th></tr></thead>
                <tbody>
                <?php if ($contracts === []): ?>
                    <tr><td colspan="7" class="text-center text-muted py-5">No contract records yet. Start with an opportunity or tender.</td></tr>
                <?php else: foreach ($contracts as $contract): $contractId = (int)$contract['id']; ?>
                    <tr>
                        <td><div class="fw-semibold"><?php echo htmlspecialchars($contract['contract_number']); ?></div><div><?php echo htmlspecialchars($contract['title']); ?></div><div class="small text-muted">Created <?php echo htmlspecialchars(date('j M Y', strtotime((string)$contract['created_at']))); ?></div></td>
                        <td><div><?php echo htmlspecialchars($contract['client_name']); ?></div><div class="small text-muted"><?php echo htmlspecialchars($contract['contractor_name'] ?: 'Contractor not selected'); ?></div></td>
                        <td><?php echo htmlspecialchars($contract['procurement_route']); ?></td>
                        <td><div><?php echo number_format((float)$contract['awarded_value'], 2); ?></div><div class="small text-muted">Estimate <?php echo number_format((float)$contract['estimated_value'], 2); ?></div></td>
                        <td><div class="small">Bid: <?php echo htmlspecialchars($contract['bid_deadline'] ?: 'Not set'); ?></div><div class="small">Start: <?php echo htmlspecialchars($contract['commencement_date'] ?: 'Not set'); ?></div></td>
                        <td><span class="badge <?php echo $statusClasses[$contract['status']] ?? 'text-bg-secondary'; ?>"><?php echo htmlspecialchars($statusLabels[$contract['status']] ?? $contract['status']); ?></span></td>
                        <td><button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#contract-<?php echo $contractId; ?>" aria-expanded="false"><i class="bi bi-journal-text me-1"></i>Process log</button></td>
                    </tr>
                    <tr class="collapse" id="contract-<?php echo $contractId; ?>"><td colspan="7" class="bg-light">
                        <div class="p-2">
                            <div class="d-flex justify-content-between align-items-center mb-3"><strong>Process documentation</strong><span class="small text-muted"><?php echo count($events[$contractId] ?? []); ?> milestone(s)</span></div>
                            <?php if (!empty($contract['description'])): ?><p class="small mb-3"><?php echo nl2br(htmlspecialchars($contract['description'])); ?></p><?php endif; ?>
                            <?php if (!empty($events[$contractId])): ?><div class="vstack gap-2 mb-3"><?php foreach ($events[$contractId] as $event): ?><div class="border rounded bg-white p-2"><div class="d-flex justify-content-between"><strong><?php echo htmlspecialchars($event['event_type']); ?></strong><span class="small text-muted"><?php echo htmlspecialchars($event['event_date']); ?></span></div><div class="small mt-1"><?php echo nl2br(htmlspecialchars($event['notes'])); ?></div></div><?php endforeach; ?></div><?php else: ?><p class="small text-muted">No milestones recorded yet.</p><?php endif; ?>
                            <div class="border rounded bg-white p-3 mb-3"><div class="d-flex justify-content-between align-items-center mb-2"><strong>Contract files</strong><span class="small text-muted"><?php echo count($documents[$contractId] ?? []); ?> file(s), newest first</span></div><?php if (!empty($documents[$contractId])): ?><div class="list-group list-group-flush mb-3"><?php foreach ($documents[$contractId] as $document): ?><a class="list-group-item list-group-item-action px-0 d-flex justify-content-between align-items-center" href="/ERP/public/contract-admin/documents/download?id=<?php echo (int)$document['id']; ?>"><span><i class="bi bi-paperclip me-2"></i><strong><?php echo htmlspecialchars($document['label']); ?></strong><small class="text-muted ms-2"><?php echo htmlspecialchars($document['document_date'] . ' - ' . $document['original_name']); ?></small></span><i class="bi bi-download"></i></a><?php endforeach; ?></div><?php else: ?><p class="small text-muted">No files attached to this contract.</p><?php endif; ?><form method="post" action="/ERP/public/contract-admin/documents/upload" enctype="multipart/form-data" class="row g-2 align-items-end"><input type="hidden" name="contract_id" value="<?php echo $contractId; ?>"><div class="col-md-2"><label class="form-label small">Document date</label><input class="form-control form-control-sm" type="date" name="document_date" required></div><div class="col-md-3"><label class="form-label small">File label</label><input class="form-control form-control-sm" name="label" placeholder="Tender document" required></div><div class="col-md-5"><label class="form-label small">File</label><input class="form-control form-control-sm" type="file" name="contract_file" accept=".pdf,.jpg,.jpeg,.png,.webp,.txt,.doc,.docx,.xls,.xlsx" required></div><div class="col-md-2"><button class="btn btn-sm btn-outline-primary w-100" type="submit">Upload file</button></div></form><div class="form-text">PDF, Word, Excel, image, or text files. Maximum 10 MB.</div></div>
                            <form method="post" action="/ERP/public/contract-admin/events/add" class="row g-2 align-items-end"><input type="hidden" name="contract_id" value="<?php echo $contractId; ?>"><div class="col-md-3"><label class="form-label small">Milestone</label><select class="form-select form-select-sm" name="event_type" required><option value="Tender issued">Tender issued</option><option value="Clarification">Clarification</option><option value="Evaluation">Evaluation</option><option value="Approval">Approval</option><option value="Award">Award</option><option value="Pre-start">Pre-start</option><option value="Instruction">Instruction</option><option value="Variation">Variation</option><option value="Payment / claim">Payment / claim</option><option value="Completion">Completion</option><option value="Closeout">Closeout</option></select></div><div class="col-md-2"><label class="form-label small">Date</label><input class="form-control form-control-sm" type="date" name="event_date" required></div><div class="col-md-5"><label class="form-label small">Record note</label><input class="form-control form-control-sm" name="notes" maxlength="1000" placeholder="Decision, action, reference, or outcome" required></div><div class="col-md-2"><button class="btn btn-sm btn-outline-primary w-100" type="submit">Add milestone</button></div></form>
                        </div>
                    </td></tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="newContractModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">New contract record</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div><form method="post" action="/ERP/public/contract-admin/save"><div class="modal-body"><div class="row g-3"><div class="col-md-4"><label class="form-label">Contract number</label><input class="form-control" name="contract_number" placeholder="CNT-001" required></div><div class="col-md-8"><label class="form-label">Opportunity / contract title</label><input class="form-control" name="title" required></div><div class="col-md-6"><label class="form-label">Client / employer</label><input class="form-control" name="client_name" required></div><div class="col-md-6"><label class="form-label">Contractor / winning bidder</label><input class="form-control" name="contractor_name"></div><div class="col-md-4"><label class="form-label">Procurement route</label><select class="form-select" name="procurement_route"><option>Open tender</option><option>Selective tender</option><option>Negotiated</option><option>Framework / call-off</option><option>Direct award</option></select></div><div class="col-md-4"><label class="form-label">Status</label><select class="form-select" name="status"><?php foreach ($statusLabels as $key => $label): ?><option value="<?php echo $key; ?>"><?php echo htmlspecialchars($label); ?></option><?php endforeach; ?></select></div><div class="col-md-4"><label class="form-label">Estimated value</label><input class="form-control" type="number" min="0" step="0.01" name="estimated_value" value="0"></div><div class="col-md-4"><label class="form-label">Awarded value</label><input class="form-control" type="number" min="0" step="0.01" name="awarded_value" value="0"></div><div class="col-md-4"><label class="form-label">Bid deadline</label><input class="form-control" type="date" name="bid_deadline"></div><div class="col-md-4"><label class="form-label">Award date</label><input class="form-control" type="date" name="award_date"></div><div class="col-md-4"><label class="form-label">Commencement</label><input class="form-control" type="date" name="commencement_date"></div><div class="col-md-4"><label class="form-label">Completion target</label><input class="form-control" type="date" name="completion_date"></div><div class="col-12"><label class="form-label">Scope and decision context</label><textarea class="form-control" name="description" rows="3" placeholder="Scope, evaluation basis, approvals, or risks to carry forward"></textarea></div></div></div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Save contract record</button></div></form></div></div></div>
