<style>
    .btn { padding: 10px 20px; background: #0066cc; color: #fff; border: none; border-radius: 6px; cursor: pointer; text-decoration: none; font-size: 14px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; transition: background 0.2s;}
    .btn:hover { background: #005bb5; }
    .btn-outline { background: transparent; border: 1px solid #0066cc; color: #0066cc; }
    .btn-outline:hover { background: rgba(0,102,204,0.05); }
    
    .form-grid { display: grid; grid-template-cols: 1fr 1fr; gap: 20px; margin-bottom: 25px; }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; margin-bottom: 6px; font-size: 13px; font-weight: 600; color: var(--text-main); }
    .form-control { width: 100%; padding: 10px 12px; border: 1px solid var(--mac-border); border-radius: 6px; background: var(--mega-bg); color: var(--text-main); box-sizing: border-box; font-size: 14px; transition: border 0.2s; }
    .form-control:focus { border-color: #0066cc; outline: none; }
    
    .perm-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
    .perm-table th, .perm-table td { padding: 10px 12px; border-bottom: 1px solid var(--mac-border); text-align: left; }
    .perm-table th { background: rgba(0,0,0,0.02); font-weight: 700; font-size: 12px; text-transform: uppercase; color: var(--text-muted); }
    .perm-table tr:hover { background: rgba(0,0,0,0.01); }
    
    .checkbox-cell { text-align: center; width: 120px; }
    .checkbox-cell input[type="checkbox"] { width: 16px; height: 16px; cursor: pointer; accent-color: #0066cc; }

    .module-group-title { font-weight: 700; font-size: 14px; color: #0066cc; padding: 12px 12px 6px; }
</style>

<div class="card" style="max-width: 900px; margin: 0 auto;">
    <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--mac-border); padding-bottom: 15px; margin-bottom: 20px;">
        <div>
            <h2 style="margin: 0; font-weight: 700;"><i class="ph ph-user-plus" style="color:#0066cc;"></i> Create User Account</h2>
            <p style="margin: 5px 0 0 0; font-size: 13px; color: var(--text-muted);">Configure login details and granular access privileges.</p>
        </div>
        <a href="<?= APP_URL ?>/user" class="btn btn-outline" style="padding: 8px 14px; font-size: 13px;"><i class="ph ph-arrow-left"></i> Back to List</a>
    </div>

    <?php if(!empty($data['error'])): ?>
        <div style="padding: 12px 16px; background:#ffebee; color:#c62828; border-radius:6px; margin-bottom:20px; font-weight:500; display:flex; align-items:center; gap:8px;">
            <i class="ph-bold ph-warning-circle" style="font-size: 18px;"></i>
            <?= $data['error'] ?>
        </div>
    <?php endif; ?>

    <form action="<?= APP_URL ?>/user/create" method="POST" enctype="multipart/form-data">
        <div class="form-grid">
            <div>
                <div class="form-group">
                    <label>Username *</label>
                    <input type="text" name="username" class="form-control" required placeholder="e.g. jason.m" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label>Email Address *</label>
                    <input type="email" name="email" class="form-control" required placeholder="e.g. jason@curtiss.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Link to Employee Profile</label>
                    <select name="employee_id" class="form-control">
                        <option value="">-- No Linked Employee --</option>
                        <?php foreach($data['employees'] as $emp): ?>
                            <option value="<?= $emp->id ?>" <?= (($_POST['employee_id'] ?? '') == $emp->id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($emp->first_name . ' ' . $emp->last_name) ?> (<?= htmlspecialchars($emp->job_title) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div>
                <div class="form-group">
                    <label>Assigned System Roles *</label>
                    <div style="background: var(--mega-bg); border: 1px solid var(--mac-border); padding: 12px; border-radius: 6px; display: flex; flex-direction: column; gap: 8px;">
                        <?php foreach ($data['roles'] as $role): ?>
                            <label style="display: flex; align-items: center; gap: 8px; font-weight: 500; cursor: pointer; margin: 0;">
                                <input type="checkbox" name="roles[]" value="<?= $role->id ?>" style="width:16px; height:16px;">
                                <span><?= htmlspecialchars($role->name) ?></span>
                                <span style="font-size: 11px; color: var(--text-muted);"> - <?= htmlspecialchars($role->description) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label>Temporary Password *</label>
                    <input type="password" name="password" class="form-control" required placeholder="Min 4 characters">
                </div>

                <div class="form-group">
                    <label>Account Status *</label>
                    <select name="status" class="form-control" required>
                        <option value="Active" selected>Active</option>
                        <option value="Blocked">Blocked / Inactive</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Digital Signature (Optional PNG/JPG)</label>
                    <input type="file" name="signature" class="form-control" accept=".png, .jpg, .jpeg" style="padding: 6px;">
                </div>
            </div>
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 12px; border-top: 1px solid var(--mac-border); padding-top: 20px; margin-top: 20px;">
            <a href="<?= APP_URL ?>/user" class="btn btn-outline">Cancel</a>
            <button type="submit" class="btn"><i class="ph ph-check-square"></i> Save User Account</button>
        </div>
    </form>
</div>

