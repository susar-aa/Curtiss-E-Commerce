<style>
    .btn { padding: 10px 20px; background: #0066cc; color: #fff; border: none; border-radius: 6px; cursor: pointer; text-decoration: none; font-size: 14px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; transition: background 0.2s;}
    .btn:hover { background: #005bb5; }
    .btn-outline { background: transparent; border: 1px solid #0066cc; color: #0066cc; }
    .btn-outline:hover { background: rgba(0,102,204,0.05); }
    
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; margin-bottom: 6px; font-size: 13px; font-weight: 600; color: var(--text-main); }
    .form-control { width: 100%; padding: 10px 12px; border: 1px solid var(--mac-border); border-radius: 6px; background: var(--mega-bg); color: var(--text-main); box-sizing: border-box; font-size: 14px; transition: border 0.2s; }
    .form-control:focus { border-color: #0066cc; outline: none; }
    
    .perm-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
    .perm-table th, .perm-table td { padding: 10px 12px; border-bottom: 1px solid var(--mac-border); text-align: left; }
    .perm-table th { background: rgba(0,0,0,0.02); font-weight: 700; font-size: 12px; text-transform: uppercase; color: var(--text-muted); }
    .perm-table tr:hover { background: rgba(0,0,0,0.01); }
    
    .checkbox-cell { text-align: center; width: 120px; }
    .checkbox-cell input[type="checkbox"] { width: 16px; height: 16px; cursor: pointer; accent-color: #0066cc; }
</style>

<div class="card" style="max-width: 900px; margin: 0 auto;">
    <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--mac-border); padding-bottom: 15px; margin-bottom: 20px;">
        <div>
            <h2 style="margin: 0; font-weight: 700;"><i class="ph ph-shield" style="color:#0066cc;"></i> Edit System Role</h2>
            <p style="margin: 5px 0 0 0; font-size: 13px; color: var(--text-muted);">Modify role definition and authorization scope.</p>
        </div>
        <a href="<?= APP_URL ?>/user/roles" class="btn btn-outline" style="padding: 8px 14px; font-size: 13px;"><i class="ph ph-arrow-left"></i> Back to Roles</a>
    </div>

    <?php if(!empty($data['error'])): ?>
        <div style="padding: 12px 16px; background:#ffebee; color:#c62828; border-radius:6px; margin-bottom:20px; font-weight:500; display:flex; align-items:center; gap:8px;">
            <i class="ph-bold ph-warning-circle" style="font-size: 18px;"></i>
            <?= $data['error'] ?>
        </div>
    <?php endif; ?>

    <form action="<?= APP_URL ?>/user/edit_role/<?= $data['role']->id ?>" method="POST">
        <div class="form-group">
            <label>Role Name *</label>
            <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($data['role']->name) ?>" <?= (strtolower($data['role']->name) === 'admin') ? 'readonly style="background: rgba(0,0,0,0.05); cursor: not-allowed;"' : '' ?>>
        </div>
        
        <div class="form-group">
            <label>Description</label>
            <textarea name="description" class="form-control" rows="2" placeholder="Briefly describe the responsibilities..."><?= htmlspecialchars($data['role']->description ?? '') ?></textarea>
        </div>

        <?php if (strtolower($data['role']->name) === 'admin'): ?>
            <div style="padding: 20px; background: rgba(198,40,40,0.05); border: 1px solid rgba(198,40,40,0.15); border-radius: 8px; margin-top: 25px; color: #c62828;">
                <h4 style="margin: 0 0 8px 0; font-weight: 700;"><i class="ph-bold ph-shield-check"></i> Super Administrator Account Bypass</h4>
                <p style="margin: 0; font-size: 13px; font-weight: 500;">
                    The 'Admin' role automatically possesses all permissions across all modules. Direct permission mapping is not required and has been bypassed for security and system stability.
                </p>
            </div>
        <?php else: ?>
            <div class="card" style="background: rgba(0,0,0,0.01); border: 1px solid var(--mac-border); padding: 20px; border-radius: 8px; margin-top: 25px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
                    <h3 style="margin:0; font-size:16px; font-weight:700;"><i class="ph ph-key" style="color:#0066cc;"></i> Grant Modular Permissions</h3>
                    <div style="display:flex; gap:10px; font-size:12px;">
                        <button type="button" class="btn btn-outline" style="padding: 4px 10px; font-size: 11px;" onclick="toggleAllCheckboxes('view', true)">All View</button>
                        <button type="button" class="btn btn-outline" style="padding: 4px 10px; font-size: 11px;" onclick="toggleAllCheckboxes('create_edit', true)">All Create/Edit</button>
                        <button type="button" class="btn btn-outline" style="padding: 4px 10px; font-size: 11px;" onclick="toggleAllCheckboxes('delete', true)">All Delete</button>
                        <button type="button" class="btn btn-outline text-danger" style="border-color:#ff3b30; padding: 4px 10px; font-size: 11px;" onclick="clearAllCheckboxes()">Clear All</button>
                    </div>
                </div>

                <table class="perm-table">
                    <thead>
                        <tr>
                            <th>Module / Feature Section</th>
                            <th style="text-align: center; width:120px;">View Access</th>
                            <th style="text-align: center; width:120px;">Create / Edit</th>
                            <th style="text-align: center; width:120px;">Delete Access</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($data['modules'] as $moduleKey => $moduleName): 
                            $hasView = !empty($data['currentPermissions'][$moduleKey]['can_view']);
                            $hasCreate = !empty($data['currentPermissions'][$moduleKey]['can_create_edit']);
                            $hasDelete = !empty($data['currentPermissions'][$moduleKey]['can_delete']);
                        ?>
                        <tr>
                            <td>
                                <strong style="font-size: 13px; color: var(--text-main);"><?= htmlspecialchars($moduleName) ?></strong>
                                <span style="display:block; font-size:10px; color:var(--text-muted); font-family:mono;"><?= $moduleKey ?></span>
                            </td>
                            <td class="checkbox-cell">
                                <input type="checkbox" name="permissions[<?= $moduleKey ?>][view]" value="1" class="chk-view" <?= $hasView ? 'checked' : '' ?> onclick="autoCheckDependencies('<?= $moduleKey ?>', 'view')">
                            </td>
                            <td class="checkbox-cell">
                                <input type="checkbox" name="permissions[<?= $moduleKey ?>][create_edit]" value="1" class="chk-create" <?= $hasCreate ? 'checked' : '' ?> onclick="autoCheckDependencies('<?= $moduleKey ?>', 'create_edit')">
                            </td>
                            <td class="checkbox-cell">
                                <input type="checkbox" name="permissions[<?= $moduleKey ?>][delete]" value="1" class="chk-delete" <?= $hasDelete ? 'checked' : '' ?> onclick="autoCheckDependencies('<?= $moduleKey ?>', 'delete')">
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <div style="display: flex; justify-content: flex-end; gap: 12px; border-top: 1px solid var(--mac-border); padding-top: 20px; margin-top: 25px;">
            <a href="<?= APP_URL ?>/user/roles" class="btn btn-outline">Cancel</a>
            <button type="submit" class="btn"><i class="ph ph-check-square"></i> Save Changes</button>
        </div>
    </form>
</div>

<script>
    function toggleAllCheckboxes(type, state) {
        let selector = '';
        if (type === 'view') selector = '.chk-view';
        else if (type === 'create_edit') selector = '.chk-create';
        else if (type === 'delete') selector = '.chk-delete';
        
        document.querySelectorAll(selector).forEach(chk => {
            chk.checked = state;
        });

        // If checking create_edit or delete, automatically check view as well
        if (state && (type === 'create_edit' || type === 'delete')) {
            document.querySelectorAll('.chk-view').forEach(chk => {
                chk.checked = true;
            });
        }
    }

    function clearAllCheckboxes() {
        document.querySelectorAll('.perm-table input[type="checkbox"]').forEach(chk => {
            chk.checked = false;
        });
    }

    function autoCheckDependencies(module, action) {
        const viewChk = document.querySelector(`input[name="permissions[${module}][view]"]`);
        const createChk = document.querySelector(`input[name="permissions[${module}][create_edit]"]`);
        const deleteChk = document.querySelector(`input[name="permissions[${module}][delete]"]`);
        
        if (action === 'create_edit' && createChk.checked) {
            viewChk.checked = true;
        }
        if (action === 'delete' && deleteChk.checked) {
            viewChk.checked = true;
        }
        if (action === 'view' && !viewChk.checked) {
            createChk.checked = false;
            deleteChk.checked = false;
        }
    }
</script>
