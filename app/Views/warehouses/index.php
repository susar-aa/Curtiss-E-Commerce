<?php
?>
<style>
    .header-actions { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .btn { padding: 8px 16px; background: #0066cc; color: #fff; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; font-size: 14px;}
    .btn-outline { background: transparent; border: 1px solid #0066cc; color: #0066cc; }
    .btn-danger { background: #ffebee; color: #c62828; border: none; }
    .btn-small { padding: 4px 8px; font-size: 11px; margin-right: 5px; cursor: pointer; border-radius: 4px;}
    
    .data-table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.05);}
    @media (prefers-color-scheme: dark) { .data-table { background: #1e1e2d; } }
    .data-table th, .data-table td { padding: 12px; text-align: left; border-bottom: 1px solid var(--mac-border); font-size: 14px;}
    .data-table th { background-color: rgba(0,0,0,0.02); font-weight: 600; color:#555;}
    
    .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 2000; align-items: center; justify-content: center; }
    .modal-content { background: var(--mac-bg); padding: 25px; border-radius: 12px; width: 450px; border: 1px solid var(--mac-border); }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; margin-bottom: 5px; font-size: 13px; font-weight: 600; }
    .form-control { width: 100%; padding: 8px 12px; border: 1px solid var(--mac-border); border-radius: 4px; background: transparent; color: var(--text-main); box-sizing: border-box;}
</style>

<div class="header-actions">
    <div>
        <h2 style="margin: 0 0 5px 0;">Warehouse Management</h2>
        <p style="margin: 0; color: #666; font-size: 14px;">Manage locations for your inventory.</p>
    </div>
    <button class="btn" onclick="openModal('add')">+ Add Warehouse</button>
</div>

<?php if(!empty($data['error'])): ?><div style="padding: 10px; background:#ffebee; color:#c62828; border-radius:4px; margin-bottom:15px;"><?= $data['error'] ?></div><?php endif; ?>
<?php if(!empty($data['success'])): ?><div style="padding: 10px; background:#e8f5e9; color:#2e7d32; border-radius:4px; margin-bottom:15px;"><?= $data['success'] ?></div><?php endif; ?>

<div id="tableContainer">
    <table class="data-table">
        <thead>
            <tr>
                <th>Warehouse Name</th>
                <th>Location / Address</th>
                <th style="text-align: center;">Status</th>
                <th style="text-align: center;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if(empty($data['warehouses'])): ?>
            <tr><td colspan="4" style="text-align: center; color: #888; padding: 20px;">No warehouses found.</td></tr>
            <?php else: foreach($data['warehouses'] as $wh): ?>
            <tr>
                <td><strong><?= htmlspecialchars($wh->name) ?></strong></td>
                <td style="color:#666; font-size:13px;"><?= htmlspecialchars($wh->location) ?: '<em style="color:#aaa;">No location provided</em>' ?></td>
                <td style="text-align: center;">
                    <?php if($wh->is_default): ?>
                        <span style="background: #e8f5e9; color: #2e7d32; padding: 4px 10px; border-radius: 12px; font-weight: bold; font-size:11px;">Default</span>
                    <?php else: ?>
                        <span style="color: #aaa; font-size: 11px;">Secondary</span>
                    <?php endif; ?>
                </td>
                <td style="text-align: center;">
                    <button class="btn btn-small btn-outline" onclick="openModal('edit', '<?= $wh->id ?>', '<?= htmlspecialchars(addslashes($wh->name)) ?>', '<?= htmlspecialchars(addslashes($wh->location)) ?>', <?= $wh->is_default ?>)">Edit</button>
                    <form action="<?= APP_URL ?>/warehouse" method="POST" style="display:inline;">
                        <input type="hidden" name="action" value="delete_warehouse">
                        <input type="hidden" name="warehouse_id" value="<?= $wh->id ?>">
                        <button type="submit" class="btn btn-small btn-danger" onclick="return confirm('Delete this warehouse? Items inside it will lose their location link.');" <?= $wh->is_default ? 'disabled title="Cannot delete the default warehouse"' : '' ?>>Del</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal Form -->
<div class="modal" id="whModal">
    <div class="modal-content">
        <h3 id="modalTitle" style="margin-top:0;">Add Warehouse</h3>
        <form action="<?= APP_URL ?>/warehouse" method="POST">
            <input type="hidden" name="action" id="formAction" value="add_warehouse">
            <input type="hidden" name="warehouse_id" id="formWhId" value="">
            
            <div class="form-group"><label>Warehouse Name *</label><input type="text" name="name" id="f_name" class="form-control" required></div>
            <div class="form-group"><label>Location / Address</label><textarea name="location" id="f_location" class="form-control" rows="2"></textarea></div>
            
            <div class="form-group" style="display:flex; align-items:center; gap:8px;">
                <input type="checkbox" name="is_default" id="f_default" value="1" style="width:16px; height:16px;">
                <label style="margin:0; font-size:14px; font-weight:normal; cursor:pointer;" for="f_default">Set as Default Warehouse</label>
            </div>
            
            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('whModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn" id="modalSubmitBtn">Save</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openModal(mode, id = '', name = '', location = '', is_default = 0) {
        document.getElementById('whModal').style.display = 'flex';
        if (mode === 'add') {
            document.getElementById('modalTitle').innerText = 'Add Warehouse';
            document.getElementById('formAction').value = 'add_warehouse';
            document.getElementById('f_name').value = '';
            document.getElementById('f_location').value = '';
            document.getElementById('f_default').checked = false;
        } else {
            document.getElementById('modalTitle').innerText = 'Edit Warehouse';
            document.getElementById('formAction').value = 'edit_warehouse';
            document.getElementById('formWhId').value = id;
            document.getElementById('f_name').value = name;
            document.getElementById('f_location').value = location;
            document.getElementById('f_default').checked = is_default == 1;
        }
    }
</script>