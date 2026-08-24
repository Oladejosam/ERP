<?php
$roleNames = [];
foreach (($roles ?? []) as $role) {
    $roleNames[(int)$role['id']] = (string)$role['name'];
}
$parentLinks = $parentLinks ?? [];
$roleLevels = $roleLevels ?? [];
$levels = $levels ?? [];
?>
<div class="card shadow-sm border-0">
    <div class="card-body p-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <p class="text-uppercase text-muted small fw-semibold mb-1">Super Admin Module</p>
                <h2 class="fw-bold mb-1">Workflow</h2>
                <p class="text-muted mb-0">Arrange HR roles into the reporting structure used for approval routing.</p>
            </div>
            <span class="badge text-bg-dark px-3 py-2">Global access</span>
        </div>

        <?php if (!empty($_SESSION['workflow_flash'])): ?>
            <div class="alert alert-info"><?php echo htmlspecialchars($_SESSION['workflow_flash']); unset($_SESSION['workflow_flash']); ?></div>
        <?php endif; ?>

        <div class="card border mb-4">
            <div class="card-body">
                <div class="d-flex flex-column flex-md-row justify-content-between gap-3">
                    <div>
                        <h5 class="fw-bold mb-1">Organogram levels</h5>
                        <p class="text-muted small mb-0">Create level names for this company, such as Executive, Management, or Operations.</p>
                    </div>
                    <form method="post" action="/ERP/public/modules/workflow/levels/create" class="d-flex gap-2 align-items-start">
                        <label class="visually-hidden" for="workflowLevelName">Level name</label>
                        <input id="workflowLevelName" class="form-control" name="name" maxlength="100" placeholder="New level name" required>
                        <button class="btn btn-outline-primary text-nowrap" type="submit"><i class="bi bi-plus-lg me-1"></i>Create level</button>
                    </form>
                </div>
                <?php if ($levels !== []): ?>
                    <div class="d-flex flex-wrap gap-2 mt-3">
                        <?php foreach ($levels as $level): ?>
                            <span class="badge text-bg-light border d-inline-flex align-items-center gap-2 px-3 py-2">
                                <?php echo htmlspecialchars($level['name']); ?>
                                <form method="post" action="/ERP/public/modules/workflow/levels/delete" class="d-inline" onsubmit="return confirm('Delete this level? Roles using it will become unassigned.');">
                                    <input type="hidden" name="level_id" value="<?php echo (int)$level['id']; ?>">
                                    <button class="btn btn-sm p-0 text-danger" type="submit" title="Delete level" aria-label="Delete <?php echo htmlspecialchars($level['name']); ?>"><i class="bi bi-trash"></i></button>
                                </form>
                            </span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($roleNames === []): ?>
            <div class="alert alert-light border mb-0">Create company roles in the HR module before designing the organogram.</div>
        <?php else: ?>
            <form method="post" action="/ERP/public/modules/workflow/save">
                <div class="border rounded p-3 workflow-canvas">
                    <div class="d-flex flex-column flex-md-row justify-content-between gap-2 mb-3">
                        <div>
                            <h5 class="fw-bold mb-1">Design organogram</h5>
                            <p class="text-muted small mb-0">Drag a role onto another role to make it a direct report. Drop it in the top-level area to move it back.</p>
                        </div>
                        <span class="small text-muted"><i class="bi bi-arrows-move me-1"></i>Drag and drop roles</span>
                    </div>
                    <div id="organogramTree" class="organogram-tree" data-drop-parent="root"></div>
                </div>
                <div id="workflowInputs"></div>
                <button class="btn btn-primary mt-3" type="submit"><i class="bi bi-save me-2"></i>Save organogram</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php if ($roleNames !== []): ?>
<style>
.workflow-canvas { background: #f8fafc; }
.organogram-tree { min-height: 180px; padding: 1rem; border: 2px dashed #cbd5e1; border-radius: .5rem; background: #fff; overflow-x: auto; }
.level-band { min-width: 42rem; padding: 1rem; margin-bottom: 1rem; border: 1px solid #e2e8f0; border-radius: .75rem; background: #f8fafc; }
.level-band:last-child { margin-bottom: 0; }
.level-band-title { display: flex; align-items: center; gap: .5rem; color: #334155; font-weight: 700; margin-bottom: .75rem; }
.level-band-roles { display: flex; flex-wrap: wrap; align-items: flex-start; gap: .75rem; min-height: 4rem; padding: .5rem; border: 2px dashed #cbd5e1; border-radius: .5rem; }
.role-branch { margin: .75rem 0; }
.role-children { margin: .5rem 0 0 1.25rem; padding-left: 1rem; border-left: 2px solid #dbeafe; min-height: 1rem; }
.role-card { max-width: 34rem; padding: .75rem 1rem; border: 2px solid #bfdbfe; border-radius: 1.25rem; background: #fff; box-shadow: 0 3px 8px rgba(15, 23, 42, .08); cursor: grab; }
.role-card:active { cursor: grabbing; }
.role-card.dragging { opacity: .45; }
.role-level { font-size: .75rem; margin-top: .15rem; }
.role-level-select { max-width: 10rem; }
.tree-actions { display: flex; gap: .25rem; }
.tree-move { width: 2rem; height: 2rem; padding: 0; border-radius: 50%; }
.tree-move:not(:disabled):hover { color: #1d4ed8; background: #dbeafe; }
.drop-target { border-color: #2563eb !important; background: #eff6ff !important; }
</style>
<script>
(() => {
    const roleNames = <?php echo json_encode($roleNames, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
    const tree = document.getElementById('organogramTree');
    const inputContainer = document.getElementById('workflowInputs');
    const levels = <?php echo json_encode($levels, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
    const parents = <?php echo json_encode(array_map('intval', $parentLinks), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
    const roleLevels = <?php echo json_encode(array_map('intval', $roleLevels), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
    let children = {};

    function render() {
        children = {};
        Object.keys(roleNames).forEach((roleId) => {
            const parentId = parents[roleId] || 'root';
            (children[parentId] ??= []).push(roleId);
        });
        const bands = levels.map((level, index) => `<section class="level-band" data-level-id="${level.id}"><div class="level-band-title"><span class="badge text-bg-primary">${index + 1}</span>${escapeHtml(level.name)}</div><div class="level-band-roles" data-drop-parent="level-${level.id}">${drawLevel(level.id)}</div></section>`);
        const unassigned = `<section class="level-band" data-level-id="unassigned"><div class="level-band-title"><span class="badge text-bg-secondary">-</span>Unassigned level</div><div class="level-band-roles" data-drop-parent="root-level">${drawLevel(0)}</div></section>`;
        tree.innerHTML = bands.join('') + unassigned;
        inputContainer.innerHTML = Object.keys(roleNames).map((roleId) =>
            `<input type="hidden" name="parent_role[${roleId}]" value="${parents[roleId] || 0}"><input type="hidden" name="role_level[${roleId}]" value="${roleLevels[roleId] || 0}">`
        ).join('');
        bindInteractions();
    }

    function drawLevel(levelFilterId) {
        return Object.keys(roleNames).filter((roleId) => Number(roleLevels[roleId] || 0) === Number(levelFilterId)).map((roleId) => {
            const levelId = roleLevels[roleId] || 0;
            const level = levels.find((item) => Number(item.id) === Number(levelId));
            const siblings = childrenFor(parents[roleId] || 'root');
            const position = siblings.indexOf(roleId);
            const canMoveUp = Boolean(parents[roleId]);
            const canMoveDown = position > 0;
            const parentName = parents[roleId] ? roleNames[parents[roleId]] : 'Top level';
            return `<div class="role-branch" data-role-id="${roleId}">
                <div class="role-card" draggable="true" data-role-id="${roleId}" data-drop-parent="${roleId}">
                    <div class="d-flex align-items-start gap-2"><i class="bi bi-grip-vertical drag-handle text-muted"></i><div class="flex-grow-1"><strong>${escapeHtml(roleNames[roleId])}</strong><div class="role-level text-muted">Reports to: ${escapeHtml(parentName)}</div></div>
                    <select class="form-select form-select-sm role-level-select" data-role-level="${roleId}" aria-label="Level for ${escapeHtml(roleNames[roleId])}"><option value="0">No level</option>${levels.map((item) => `<option value="${item.id}" ${Number(item.id) === Number(levelId) ? 'selected' : ''}>${escapeHtml(item.name)}</option>`).join('')}</select>
                    <div class="tree-actions"><button type="button" class="btn btn-sm btn-light tree-move" data-move-role="${roleId}" data-direction="up" title="Move up one level" aria-label="Move ${escapeHtml(roleNames[roleId])} up" ${canMoveUp ? '' : 'disabled'}><i class="bi bi-arrow-up"></i></button><button type="button" class="btn btn-sm btn-light tree-move" data-move-role="${roleId}" data-direction="down" title="Move down one level" aria-label="Move ${escapeHtml(roleNames[roleId])} down" ${canMoveDown ? '' : 'disabled'}><i class="bi bi-arrow-down"></i></button></div></div>
                </div>
            </div>`;
        }).join('');
    }

    function childrenFor(parentId) {
        return Object.keys(roleNames).filter((roleId) => (parents[roleId] || 'root') === parentId);
    }

    function bindInteractions() {
        document.querySelectorAll('[data-drop-parent]').forEach((target) => {
            target.addEventListener('dragover', (event) => { event.preventDefault(); target.classList.add('drop-target'); });
            target.addEventListener('dragleave', () => target.classList.remove('drop-target'));
            target.addEventListener('drop', (event) => {
                event.preventDefault();
                event.stopPropagation();
                target.classList.remove('drop-target');
                const roleId = event.dataTransfer.getData('text/plain');
                const parentId = target.dataset.dropParent;
                if (roleId && parentId.startsWith('level-')) {
                    roleLevels[roleId] = Number(parentId.replace('level-', ''));
                    render();
                } else if (roleId && parentId === 'root-level') {
                    roleLevels[roleId] = 0;
                    render();
                } else if (roleId && roleId !== parentId && !isDescendant(parentId, roleId)) {
                    parents[roleId] = Number(parentId);
                    render();
                }
            });
        });
        document.querySelectorAll('.role-card[draggable="true"]').forEach((card) => {
            card.addEventListener('dragstart', (event) => { event.dataTransfer.setData('text/plain', card.dataset.roleId); card.classList.add('dragging'); });
            card.addEventListener('dragend', () => card.classList.remove('dragging'));
        });
        document.querySelectorAll('[data-role-level]').forEach((select) => {
            select.addEventListener('change', () => { roleLevels[select.dataset.roleLevel] = Number(select.value); render(); });
            select.addEventListener('mousedown', (event) => event.stopPropagation());
        });
        document.querySelectorAll('[data-move-role]').forEach((button) => {
            button.addEventListener('click', () => moveRole(button.dataset.moveRole, button.dataset.direction));
            button.addEventListener('mousedown', (event) => event.stopPropagation());
        });
    }

    function moveRole(roleId, direction) {
        if (direction === 'up' && parents[roleId]) {
            parents[roleId] = parents[parents[roleId]] || 0;
        }
        if (direction === 'down') {
            const siblings = childrenFor(parents[roleId] || 'root');
            const position = siblings.indexOf(roleId);
            if (position > 0) parents[roleId] = Number(siblings[position - 1]);
        }
        render();
    }

    function isDescendant(candidateParent, roleId) {
        let current = candidateParent;
        while (current && current !== 'root') {
            if (Number(current) === Number(roleId)) return true;
            current = parents[current] || 'root';
        }
        return false;
    }

    function escapeHtml(value) {
        return value.replace(/[&<>'"]/g, (character) => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;'}[character]));
    }
    render();
})();
</script>
<?php endif; ?>