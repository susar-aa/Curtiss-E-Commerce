<style>
    .btn { padding: 10px 20px; background: #0066cc; color: #fff; border: none; border-radius: 6px; cursor: pointer; text-decoration: none; font-size: 14px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; transition: background 0.2s;}
    .btn:hover { background: #005bb5; }
    .btn-outline { background: transparent; border: 1px solid #0066cc; color: #0066cc; }
    .btn-outline:hover { background: rgba(0,102,204,0.05); }
    .btn-danger { background: #ff3b30; color: #fff; }
    .btn-danger:hover { background: #e0352b; }
    
    .role-card {
        background: var(--mega-bg);
        border: 1px solid var(--mac-border);
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.02);
    }
    
    .role-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid var(--mac-border);
        padding-bottom: 12px;
        margin-bottom: 15px;
    }
    
    .role-badge {
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        background: rgba(0,102,204,0.1);
        color: #0066cc;
    }
    
    .role-badge.admin {
        background: rgba(198,40,40,0.1);
        color: #c62828;
    }

    .module-tag {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 3px 8px;
        background: rgba(0,0,0,0.04);
        border: 1px solid var(--mac-border);
        border-radius: 4px;
        font-size: 11px;
        font-weight: 600;
        margin: 2px;
    }
    
    .action-links {
        display: flex;
        gap: 8px;
    }
</style>

<div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--mac-border); padding-bottom: 15px; margin-bottom: 20px;">
    <div>
        <h2 style="margin: 0; font-weight: 700;"><i class="ph ph-shield-check" style="color:#0066cc;"></i> Roles & Permissions Management</h2>
        <p style="margin: 5px 0 0 0; font-size: 13px; color: var(--text-muted);">Define user roles, aggregate modular access policies, and manage authorization levels.</p>
    </div>
    <div style="display: flex; gap: 10px;">
        <a href="<?= APP_URL ?>/user" class="btn btn-outline"><i class="ph ph-arrow-left"></i> Back to Users</a>
        <a href="<?= APP_URL ?>/user/create_role" class="btn"><i class="ph ph-plus"></i> + Create New Role</a>
    </div>
</div>

<?php if(!empty($_SESSION['flash_success'])): ?>
    <div style="padding: 12px 16px; background:#e8f5e9; color:#2e7d32; border-radius:6px; margin-bottom:20px; font-weight:500; display:flex; align-items:center; gap:8px;">
        <i class="ph-bold ph-check-circle" style="font-size: 18px;"></i>
        <?= $_SESSION['flash_success']; unset($_SESSION['flash_success']); ?>
    </div>
<?php endif; ?>

<?php if(!empty($_SESSION['flash_error'])): ?>
    <div style="padding: 12px 16px; background:#ffebee; color:#c62828; border-radius:6px; margin-bottom:20px; font-weight:500; display:flex; align-items:center; gap:8px;">
        <i class="ph-bold ph-warning-circle" style="font-size: 18px;"></i>
        <?= $_SESSION['flash_error']; unset($_SESSION['flash_error']); ?>
    </div>
<?php endif; ?>

<?php foreach ($data['roles'] as $role): 
    $isAdmin = (strtolower($role->name) === 'admin');
    $isProtected = in_array(strtolower($role->name), ['admin', 'office staff', 'driver', 'rep (sales representative)', 'accountant']);
?>
<div class="role-card">
    <div class="role-header">
        <div>
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 4px;">
                <h3 style="margin: 0; font-size: 18px; font-weight: 700; color: var(--text-main);"><?= htmlspecialchars($role->name) ?></h3>
                <span class="role-badge <?= $isAdmin ? 'admin' : '' ?>"><?= $isAdmin ? 'Superadmin' : 'Role' ?></span>
            </div>
            <p style="margin: 0; font-size: 13px; color: var(--text-muted);"><?= htmlspecialchars($role->description ?? 'No description provided.') ?></p>
        </div>
        
        <div class="action-links">
            <a href="<?= APP_URL ?>/user/edit_role/<?= $role->id ?>" class="btn btn-outline" style="padding: 6px 12px; font-size: 13px;">
                <i class="ph ph-pencil"></i> Edit Role & Permissions
            </a>
            <?php if (!$isProtected): ?>
                <a href="<?= APP_URL ?>/user/delete_role/<?= $role->id ?>" class="btn btn-danger" style="padding: 6px 12px; font-size: 13px;" onclick="return confirm('Are you sure you want to delete this role? Any users with this role will lose its inherited permissions.');">
                    <i class="ph ph-trash"></i> Delete
                </a>
            <?php else: ?>
                <span style="font-size: 11px; color: var(--text-muted); align-self: center; font-style: italic;"><i class="ph ph-lock"></i> Protected System Role</span>
            <?php endif; ?>
        </div>
    </div>
    
    <div>
        <h4 style="margin: 0 0 10px 0; font-size: 12px; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.5px;">Granted Module Permissions</h4>
        <?php if ($isAdmin): ?>
            <div style="color: #c62828; font-weight: 600; font-size: 13px;">
                <i class="ph-bold ph-shield-check"></i> Super Administrator Bypass: Full Read, Write, Edit, and Delete access to all system modules.
            </div>
        <?php else: ?>
            <div style="display: flex; flex-wrap: wrap; gap: 4px;">
                <?php 
                $hasPerms = false;
                foreach ($role->permissions as $mod => $p): 
                    if ($p['can_view'] || $p['can_create_edit'] || $p['can_delete']):
                        $hasPerms = true;
                        $actions = [];
                        if ($p['can_view']) $actions[] = 'View';
                        if ($p['can_create_edit']) $actions[] = 'Write';
                        if ($p['can_delete']) $actions[] = 'Delete';
                ?>
                    <span class="module-tag">
                        <strong><?= htmlspecialchars($mod) ?></strong>: 
                        <span style="color:#0066cc; font-size:10px;"><?= implode(', ', $actions) ?></span>
                    </span>
                <?php 
                    endif;
                endforeach; 
                if (!$hasPerms):
                ?>
                    <span style="color: var(--text-muted); font-size: 13px; font-style: italic;">No permissions explicitly granted.</span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>
