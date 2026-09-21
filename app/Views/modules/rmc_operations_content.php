<div class="card shadow-sm border-0">
    <div class="card-body p-4">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
            <div>
                <span class="badge text-bg-success mb-2">Ready Mix Concrete Operations</span>
                <h4 class="fw-bold mb-1"><?php echo htmlspecialchars($moduleName ?? 'RMC Module'); ?></h4>
                <p class="text-muted mb-0">Module scaffold based on Inniti ERP's published RMC feature areas.</p>
            </div>
            <a class="btn btn-outline-secondary" href="/ERP/public/modules"><i class="bi bi-grid me-1"></i>Module Center</a>
        </div>
        <div class="alert alert-info">
            Transactions are stored for the selected company and remain subject to the existing module permissions.
        </div>
        <?php if (!empty($_SESSION['rmc_flash'])): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['rmc_flash']); unset($_SESSION['rmc_flash']); ?></div>
        <?php endif; ?>
        <?php if (($moduleName ?? '') === 'Dispatch Management'): ?>
            <?php $approvedCount = count((array)($approvedMixes ?? [])); ?>
            <div class="alert alert-light border mb-4">
                <strong>Live dispatch traceability:</strong>
                <?php echo $approvedCount; ?> approved mix design(s) are currently available for dispatch issuance, and this module also tracks inventory material delivery and staff dispatch records.
                <?php if (!empty($records)): ?>
                    Recent dispatch records show <?php echo count((array)$records); ?> active transaction(s) linked to production release and site operations.
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($report)): ?>
            <?php if (($moduleName ?? '') === 'Business Intelligence'): ?>
                <div class="alert alert-info mb-4">
                    <strong>ERP + BI connection:</strong> ERP captures the operational transactions; business intelligence converts those records into live dashboards, predictive alerts, and action-oriented decisions for production, dispatch, quality, and cost control.
                </div>
                <div class="row g-3 mb-4">
                    <?php foreach ([['orders', 'Orders', 'bi-cart-check'], ['order_value', 'Order value', 'bi-cash-stack'], ['dispatch_count', 'Dispatch count', 'bi-truck'], ['dispatched_m3', 'Dispatched volume', 'bi-graph-up-arrow'], ['quality_pass_rate', 'Quality pass rate', 'bi-check-circle'], ['dispatch_efficiency', 'Dispatch efficiency', 'bi-speedometer2'], ['on_time_dispatch_rate', 'On-time dispatch', 'bi-clock-history'], ['erp_bi_readiness', 'ERP-BI readiness', 'bi-database-check']] as [$key, $label, $icon]): ?>
                        <div class="col-6 col-lg-4 col-xl-3">
                            <div class="border rounded p-3 h-100">
                                <i class="bi <?php echo $icon; ?> text-success"></i>
                                <div class="small text-muted mt-2"><?php echo htmlspecialchars($label); ?></div>
                                <strong><?php echo in_array($key, ['quality_pass_rate', 'dispatch_efficiency', 'on_time_dispatch_rate', 'erp_bi_readiness'], true) ? number_format((float)$report[$key], 1) . '%' : number_format((float)$report[$key], 2); ?></strong>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                            <h6 class="fw-semibold mb-0">Monthly trend analysis</h6>
                            <div class="d-flex gap-2 align-items-center flex-wrap">
                                <form method="get" class="d-flex gap-2 align-items-center m-0">
                                    <input type="hidden" name="module" value="business_intelligence">
                                    <select class="form-select form-select-sm" name="period">
                                        <option value="6m" <?php echo (($biPeriod ?? '6m') === '6m' ? 'selected' : ''); ?>>Last 6 months</option>
                                        <option value="12m" <?php echo (($biPeriod ?? '6m') === '12m' ? 'selected' : ''); ?>>Last 12 months</option>
                                    </select>
                                    <select class="form-select form-select-sm" name="category">
                                        <option value="all" <?php echo (($biCategory ?? 'all') === 'all' ? 'selected' : ''); ?>>All operations</option>
                                        <option value="sales" <?php echo (($biCategory ?? 'all') === 'sales' ? 'selected' : ''); ?>>Sales</option>
                                        <option value="dispatch" <?php echo (($biCategory ?? 'all') === 'dispatch' ? 'selected' : ''); ?>>Dispatch</option>
                                        <option value="quality" <?php echo (($biCategory ?? 'all') === 'quality' ? 'selected' : ''); ?>>Quality</option>
                                        <option value="maintenance" <?php echo (($biCategory ?? 'all') === 'maintenance' ? 'selected' : ''); ?>>Maintenance</option>
                                        <option value="mix_design" <?php echo (($biCategory ?? 'all') === 'mix_design' ? 'selected' : ''); ?>>Mix design</option>
                                    </select>
                                    <button class="btn btn-sm btn-primary" type="submit">Filter</button>
                                </form>
                                <a class="btn btn-sm btn-outline-success" href="/ERP/public/modules/rmc?module=business_intelligence&period=<?php echo htmlspecialchars((string)($biPeriod ?? '6m')); ?>&category=<?php echo htmlspecialchars((string)($biCategory ?? 'all')); ?>&export=1">Export CSV</a>
                            </div>
                        </div>
                        <?php $chartValues = array_map(static fn (array $point): float => (float)$point['value'], $biTrendData ?? []); $chartMax = max($chartValues ?: [1]); ?>
                        <div class="d-flex align-items-end gap-2" style="height: 220px;">
                            <?php foreach (($biTrendData ?? []) as $point): ?>
                                <?php $height = $chartMax > 0 ? max(10, ((float)$point['value'] / $chartMax) * 100) : 0; ?>
                                <div class="flex-fill text-center">
                                    <div class="d-flex justify-content-center align-items-end h-100">
                                        <div class="bg-success rounded-top w-100" style="height: <?php echo $height; ?>%; min-height: 12px; max-width: 32px; margin: 0 auto; opacity: 0.9;"></div>
                                    </div>
                                    <div class="small text-muted mt-2"><?php echo htmlspecialchars((string)$point['label']); ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="row g-3 mb-4">
                    <?php foreach ([
                        ['Quality pass rate', (float)($report['quality_pass_rate'] ?? 0), (float)($report['target_quality_pass_rate'] ?? 92.0), 'bi-check-circle'],
                        ['Dispatch efficiency', (float)($report['dispatch_efficiency'] ?? 0), (float)($report['target_dispatch_efficiency'] ?? 90.0), 'bi-speedometer2'],
                        ['On-time dispatch', (float)($report['on_time_dispatch_rate'] ?? 0), (float)($report['target_on_time_dispatch_rate'] ?? 88.0), 'bi-clock-history'],
                    ] as [$title, $actual, $target, $icon]): ?>
                        <?php $gap = max(0, $target - $actual); ?>
                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="fw-semibold"><?php echo htmlspecialchars((string)$title); ?></div>
                                    <i class="bi <?php echo $icon; ?> text-success"></i>
                                </div>
                                <div class="display-6 fw-bold mb-1"><?php echo number_format($actual, 1); ?>%</div>
                                <div class="small text-muted">Target: <?php echo number_format($target, 1); ?>% · Gap: <?php echo number_format($gap, 1); ?>%</div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="row g-4 mb-4">
                    <div class="col-lg-6">
                        <div class="border rounded p-3 h-100">
                            <h6 class="fw-semibold mb-3">ERP data sources feeding BI decisions</h6>
                            <ul class="list-unstyled mb-0 small">
                                <li class="mb-2"><strong>Sales orders:</strong> <?php echo (int)($report['orders'] ?? 0); ?> records informing demand and order pipeline visibility.</li>
                                <li class="mb-2"><strong>Dispatch data:</strong> <?php echo (int)($report['dispatch_count'] ?? 0); ?> movement records capturing transport and delivery status.</li>
                                <li class="mb-2"><strong>Quality data:</strong> <?php echo (int)($report['quality_tests'] ?? 0); ?> QC checks supporting compliance and defect control.</li>
                                <li class="mb-2"><strong>Mix design data:</strong> <?php echo (int)($report['approved_mix_designs'] ?? 0); ?> approved designs supporting consistency and production readiness.</li>
                                <li class="mb-0"><strong>Maintenance data:</strong> <?php echo (int)($report['open_maintenance'] ?? 0); ?> open tasks helping teams act before downtime impacts delivery.</li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="border rounded p-3 h-100">
                            <h6 class="fw-semibold mb-3">BI-driven operational outlook</h6>
                            <div class="small text-muted mb-2">Actionable insight from ERP transactions</div>
                            <div class="d-flex justify-content-between border-bottom py-2"><span>Quality pass rate</span><strong><?php echo number_format((float)($report['quality_pass_rate'] ?? 0), 1); ?>%</strong></div>
                            <div class="d-flex justify-content-between border-bottom py-2"><span>Dispatch efficiency</span><strong><?php echo number_format((float)($report['dispatch_efficiency'] ?? 0), 1); ?>%</strong></div>
                            <div class="d-flex justify-content-between border-bottom py-2"><span>On-time dispatch</span><strong><?php echo number_format((float)($report['on_time_dispatch_rate'] ?? 0), 1); ?>%</strong></div>
                            <div class="d-flex justify-content-between py-2"><span>ERP-BI readiness</span><strong><?php echo number_format((float)($report['erp_bi_readiness'] ?? 0), 1); ?>%</strong></div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="row g-3 mb-4">
                    <?php foreach ([['orders', 'Orders', 'bi-cart-check'], ['order_value', 'Order value', 'bi-cash-stack'], ['dispatched_m3', 'Dispatched m3', 'bi-truck'], ['quality_pass_rate', 'Quality pass rate', 'bi-check-circle'], ['open_maintenance', 'Open maintenance', 'bi-tools'], ['approved_mix_designs', 'Approved mix designs', 'bi-bezier2']] as [$key, $label, $icon]): ?>
                        <div class="col-6 col-lg-4 col-xl-2"><div class="border rounded p-3 h-100"><i class="bi <?php echo $icon; ?> text-success"></i><div class="small text-muted mt-2"><?php echo htmlspecialchars($label); ?></div><strong><?php echo $key === 'quality_pass_rate' ? number_format((float)$report[$key], 1) . '%' : number_format((float)$report[$key], 2); ?></strong></div></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        <?php if (!empty($fields)): ?>
            <h5 class="fw-semibold mt-4">Create transaction</h5>
            <form method="post" action="/ERP/public/modules/rmc/create" class="row g-3 mb-4">
                <input type="hidden" name="module" value="<?php echo htmlspecialchars((string)($_GET['module'] ?? '')); ?>">
                <?php foreach ($fields as $field): ?>
                    <?php [$key, $label, $type] = $field; ?>
                    <?php $visibility = (string)($field[4] ?? 'mixed_cement'); ?>
                    <div class="col-md-6<?php echo $type === 'textarea' ? ' col-lg-12' : ''; ?> dispatch-field" data-dispatch-group="<?php echo htmlspecialchars($visibility); ?>">
                        <label class="form-label" for="rmc_<?php echo htmlspecialchars($key); ?>"><?php echo htmlspecialchars($label); ?></label>
                        <?php if ($key === 'dispatch_type' && (($_GET['module'] ?? '') === 'dispatch')): ?>
                            <select class="form-select" id="rmc_<?php echo htmlspecialchars($key); ?>" name="<?php echo htmlspecialchars($key); ?>" required>
                                <?php foreach ((array)($field[3] ?? []) as $value => $option): ?><option value="<?php echo htmlspecialchars((string)$value); ?>"><?php echo htmlspecialchars((string)$option); ?></option><?php endforeach; ?>
                            </select>
                        <?php elseif ($key === 'mix_design_id' && (($_GET['module'] ?? '') === 'dispatch')): ?>
                            <select class="form-select" id="rmc_<?php echo htmlspecialchars($key); ?>" name="<?php echo htmlspecialchars($key); ?>">
                                <option value="">Select approved mix design</option>
                                <?php foreach (($approvedMixes ?? []) as $mix): ?>
                                    <?php $mixValue = (string)($mix['mix_code'] ?? ''); $mixLabel = $mixValue !== '' ? $mixValue . ' - ' . ($mix['concrete_grade'] ?? '') : 'Mix design #' . (string)($mix['id'] ?? ''); ?>
                                    <option value="<?php echo (int)($mix['id'] ?? 0); ?>"><?php echo htmlspecialchars($mixLabel); ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php elseif ($key === 'inventory_item_id' && (($_GET['module'] ?? '') === 'dispatch')): ?>
                            <select class="form-select" id="rmc_<?php echo htmlspecialchars($key); ?>" name="<?php echo htmlspecialchars($key); ?>">
                                <option value="">Select inventory item</option>
                                <?php foreach (($inventoryItems ?? []) as $item): ?>
                                    <option value="<?php echo (int)($item['id'] ?? 0); ?>"><?php echo htmlspecialchars((string)($item['name'] ?? '')); ?> (<?php echo htmlspecialchars((string)($item['item_code'] ?? '')); ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        <?php elseif ($key === 'staff_id' && (($_GET['module'] ?? '') === 'dispatch')): ?>
                            <select class="form-select" id="rmc_<?php echo htmlspecialchars($key); ?>" name="<?php echo htmlspecialchars($key); ?>">
                                <option value="">Select staff member</option>
                                <?php foreach (($dispatchStaff ?? []) as $member): ?>
                                    <?php $memberName = trim((string)($member['first_name'] ?? '')) . ' ' . trim((string)($member['last_name'] ?? '')); ?>
                                    <option value="<?php echo (int)($member['id'] ?? 0); ?>"><?php echo htmlspecialchars($memberName ?: (string)($member['employee_code'] ?? '')); ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php elseif ($type === 'select'): ?>
                            <select class="form-select" id="rmc_<?php echo htmlspecialchars($key); ?>" name="<?php echo htmlspecialchars($key); ?>" required>
                                <?php foreach ((array)($field[3] ?? []) as $option): ?><option value="<?php echo htmlspecialchars((string)$option); ?>"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', (string)$option))); ?></option><?php endforeach; ?>
                            </select>
                        <?php elseif ($type === 'textarea'): ?>
                            <textarea class="form-control" id="rmc_<?php echo htmlspecialchars($key); ?>" name="<?php echo htmlspecialchars($key); ?>" rows="2"></textarea>
                        <?php else: ?>
                            <input class="form-control" id="rmc_<?php echo htmlspecialchars($key); ?>" name="<?php echo htmlspecialchars($key); ?>" type="<?php echo $type === 'number' ? 'number' : $type; ?>" <?php echo $type === 'number' ? 'step="0.01" min="0"' : ''; ?> <?php echo $key !== 'notes' ? 'required' : ''; ?>>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                <div class="col-12"><button class="btn btn-success"><i class="bi bi-plus-circle me-1"></i>Save transaction</button></div>
            </form>
        <?php endif; ?>
        <h5 class="fw-semibold mt-4">Recent transactions</h5>
        <div class="row g-3 mt-1">
            <?php if (empty($records)): ?>
                <div class="col-12 text-muted">No transactions have been recorded for this module yet.</div>
            <?php else: ?>
                <div class="col-12 table-responsive"><table class="table table-striped align-middle"><thead><tr><th>Reference</th><th>Primary details</th><th>Status</th><th>Date</th></tr></thead><tbody>
                    <?php foreach ($records as $record): ?>
                        <?php $reference = $record['order_number'] ?? $record['test_number'] ?? $record['dispatch_number'] ?? $record['job_card'] ?? $record['mix_code'] ?? ''; $genericType = (string)($record['dispatch_type'] ?? 'mixed_cement'); $primary = $record['customer_name'] ?? $record['batch_reference'] ?? $record['asset_name'] ?? $record['concrete_grade'] ?? $record['mix_code'] ?? $record['vehicle_number'] ?? ''; if ($genericType === 'materials') { $primary = $record['inventory_item_name'] ?? $record['item_name'] ?? $primary; } elseif ($genericType === 'staff') { $primary = $record['staff_name'] ?? $record['issued_to'] ?? $primary; } $date = $record['delivery_date'] ?? $record['tested_at'] ?? $record['scheduled_date'] ?? $record['dispatch_date'] ?? substr((string)($record['created_at'] ?? ''), 0, 10); ?>
                        <tr><td><?php echo htmlspecialchars((string)$reference); ?></td><td><?php echo htmlspecialchars((string)$primary); ?><?php if ($genericType !== 'mixed_cement'): ?><div class="small text-muted"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $genericType))); ?></div><?php endif; ?></td><td><span class="badge text-bg-secondary"><?php echo htmlspecialchars((string)($record['status'] ?? '')); ?></span></td><td><?php echo htmlspecialchars((string)$date); ?></td></tr>
                    <?php endforeach; ?>
                </tbody></table></div>
            <?php endif; ?>
        </div>
        <h5 class="fw-semibold mt-4">Published capability coverage</h5>
        <div class="row g-3 mt-1"><?php foreach (($features ?? []) as $feature): ?><div class="col-md-6 col-xl-4"><div class="border rounded p-3 h-100"><i class="bi bi-check2-circle text-success me-2"></i><?php echo htmlspecialchars($feature); ?></div></div><?php endforeach; ?></div>
    </div>
</div>
<script>
    const dispatchTypeField = document.querySelector('select[name="dispatch_type"]');
    function syncDispatchFields() {
        if (!dispatchTypeField) {
            return;
        }
        const active = dispatchTypeField.value || 'mixed_cement';
        document.querySelectorAll('.dispatch-field').forEach(function (field) {
            const group = field.dataset.dispatchGroup || 'mixed_cement';
            const visible = group === 'all' || group === active;
            field.style.display = visible ? '' : 'none';
            const input = field.querySelector('input, select, textarea');
            if (input) {
                input.disabled = !visible;
            }
        });
    }
    if (dispatchTypeField) {
        dispatchTypeField.addEventListener('change', syncDispatchFields);
        syncDispatchFields();
    }
    setTimeout(function () {
        window.location.reload();
    }, 15000);
</script>
