<style>
    .hrm-container {
        padding: 24px;
    }
    .glass-card {
        background: var(--card-bg);
        border: 1px solid var(--card-border);
        box-shadow: var(--card-shadow);
        backdrop-filter: blur(24px);
        -webkit-backdrop-filter: blur(24px);
        border-radius: 20px;
        padding: 28px;
        margin-bottom: 24px;
    }
    .header-actions { 
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
        margin-bottom: 24px; 
        gap: 16px;
        flex-wrap: wrap;
    }
    .header-title-wrap h2 {
        font-size: 22px;
        font-weight: 700;
        color: var(--text-main);
        margin: 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .header-title-wrap p {
        font-size: 13px;
        color: var(--text-muted);
        margin: 4px 0 0 0;
    }
    
    .btn { 
        padding: 10px 20px; 
        background: var(--text-accent); 
        color: #fff !important; 
        border: none; 
        border-radius: 12px; 
        cursor: pointer; 
        text-decoration: none; 
        font-size: 13.5px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: background 0.2s, transform 0.15s;
    }
    .btn:hover { 
        background: var(--text-accent-light); 
        transform: translateY(-1px);
    }
    .btn-danger {
        background: #ef4444;
    }
    .btn-danger:hover {
        background: #dc2626;
    }
    .btn-outline { 
        background: transparent; 
        border: 1px solid var(--glass-border); 
        color: var(--text-main) !important; 
    }
    .btn-outline:hover {
        background: rgba(255, 255, 255, 0.08);
    }
    @media (prefers-color-scheme: dark) {
        .btn-outline:hover {
            background: rgba(255, 255, 255, 0.04);
        }
    }

    .data-table { 
        width: 100%; 
        border-collapse: collapse; 
        margin-top: 10px; 
    }
    .data-table th, .data-table td { 
        padding: 14px 16px; 
        text-align: left; 
        border-bottom: 1px solid var(--glass-border); 
    }
    .data-table th { 
        background-color: rgba(0, 0, 0, 0.03); 
        font-weight: 600; 
        font-size: 12px; 
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: var(--text-muted);
    }
    @media (prefers-color-scheme: dark) {
        .data-table th {
            background-color: rgba(255, 255, 255, 0.02);
        }
    }
    .data-table td {
        color: var(--text-main);
        font-size: 13.5px;
    }
    .data-table tr:hover td {
        background: rgba(255, 255, 255, 0.03);
    }
    @media (prefers-color-scheme: dark) {
        .data-table tr:hover td {
            background: rgba(255, 255, 255, 0.01);
        }
    }

    .status-badge { 
        padding: 4px 10px; 
        border-radius: 20px; 
        font-size: 11px; 
        font-weight: 700; 
        display: inline-block;
    }
    .status-Active { 
        background: rgba(16, 185, 129, 0.15); 
        color: #10b981; 
    }
    .status-Terminated, .status-Blocked { 
        background: rgba(239, 68, 68, 0.15); 
        color: #ef4444; 
    }

    .role-badge {
        padding: 4px 8px;
        border-radius: 6px;
        font-size: 10.5px;
        font-weight: 700;
        text-transform: uppercase;
        display: inline-block;
    }
    .role-admin { background: rgba(239, 68, 68, 0.12); color: #ef4444; }
    .role-office { background: rgba(139, 92, 246, 0.12); color: #8b5cf6; }
    .role-rep { background: rgba(245, 158, 11, 0.12); color: #f59e0b; }
    .role-driver { background: rgba(16, 185, 129, 0.12); color: #10b981; }
    .role-accountant { background: rgba(59, 130, 246, 0.12); color: #3b82f6; }
    
    /* modal styling */
    .modal { 
        display: none; 
        position: fixed; 
        top: 0; 
        left: 0; 
        width: 100%; 
        height: 100%; 
        background: rgba(8, 8, 16, 0.65); 
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        z-index: 9999; 
        align-items: center; 
        justify-content: center; 
    }
    .modal-content { 
        background: var(--glass-bg); 
        border: 1px solid var(--glass-border);
        box-shadow: var(--glass-shadow);
        backdrop-filter: blur(32px); 
        -webkit-backdrop-filter: blur(32px);
        padding: 30px; 
        border-radius: 20px; 
        width: 100%;
        max-width: 650px; 
        color: var(--text-main);
        box-sizing: border-box;
        max-height: 90vh;
        overflow-y: auto;
    }
    .form-group { margin-bottom: 16px; }
    .form-group label { 
        display: block; 
        margin-bottom: 6px; 
        font-size: 12.5px; 
        font-weight: 600; 
        color: var(--text-main);
        opacity: 0.85;
    }
    .form-control { 
        width: 100%; 
        padding: 10px 14px; 
        border: 1px solid var(--glass-border); 
        border-radius: 10px; 
        background: rgba(255, 255, 255, 0.08); 
        color: var(--text-main); 
        box-sizing: border-box;
        font-family: inherit;
        font-size: 13.5px;
        outline: none;
        transition: border-color 0.2s, box-shadow 0.2s;
    }
    @media (prefers-color-scheme: dark) {
        .form-control {
            background: rgba(0, 0, 0, 0.2);
        }
    }
    .form-control:focus {
        border-color: var(--text-accent);
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15);
    }
    select.form-control option {
        background: var(--bg-color);
        color: var(--text-main);
    }
    .grid-2 { 
        display: grid; 
        grid-template-columns: 1fr 1fr; 
        gap: 16px; 
    }
    @media (max-width: 580px) {
        .grid-2 {
            grid-template-columns: 1fr;
            gap: 0;
        }
    }
</style>

<div class="hrm-container">
    <div class="glass-card">
        <div class="header-actions">
            <div class="header-title-wrap">
                <h2><i class="ph ph-users-three" style="color: var(--text-accent);"></i> Staff & User Registry</h2>
                <p>Unified directory to manage employees, assign security roles, and control modular access permissions.</p>
            </div>
            <div style="display: flex; gap: 10px;">
                <a href="<?= APP_URL ?>/user/roles" class="btn btn-outline">
                    <i class="ph ph-shield"></i> Roles &amp; Permissions
                </a>
                <button class="btn" onclick="document.getElementById('staffModal').style.display='flex'">
                    <i class="ph ph-plus-circle"></i> Add Staff Member
                </button>
            </div>
        </div>

        <?php if(!empty($data['error'])): ?>
            <div style="padding: 12px 16px; background: rgba(239, 68, 68, 0.12); color: #ef4444; border-radius: 12px; margin-bottom: 20px; font-size: 13px; font-weight: 500; display: flex; align-items: center; gap: 8px; border: 1px solid rgba(239, 68, 68, 0.25);">
                <i class="ph ph-warning-circle" style="font-size: 16px;"></i>
                <?= $data['error'] ?>
            </div>
        <?php endif; ?>
        <?php if(!empty($data['success'])): ?>
            <div style="padding: 12px 16px; background: rgba(16, 185, 129, 0.12); color: #10b981; border-radius: 12px; margin-bottom: 20px; font-size: 13px; font-weight: 500; display: flex; align-items: center; gap: 8px; border: 1px solid rgba(16, 185, 129, 0.25);">
                <i class="ph ph-check-circle" style="font-size: 16px;"></i>
                <?= $data['success'] ?>
            </div>
        <?php endif; ?>

        <div style="overflow-x: auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Staff Member</th>
                        <th>Job Title &amp; Dept</th>
                        <th>Contact</th>
                        <th>System Access</th>
                        <th>Status</th>
                        <th style="text-align: right;">Base Salary</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($data['staff'])): ?>
                    <tr><td colspan="7" style="text-align: center; color: var(--text-muted); padding: 40px;">No staff records found.</td></tr>
                    <?php else: foreach($data['staff'] as $s): ?>
                    <tr>
                        <td>
                            <?php if(!empty($s->first_name)): ?>
                                <strong style="color: var(--text-main);"><?= htmlspecialchars($s->first_name . ' ' . $s->last_name) ?></strong>
                            <?php else: ?>
                                <strong style="color: var(--text-muted); font-style: italic;">System Account Only</strong>
                            <?php endif; ?>
                            
                            <?php if(!empty($s->hire_date)): ?>
                                <div style="font-size:11px; color: var(--text-muted); margin-top: 2px;">Hired: <?= date('d M Y', strtotime($s->hire_date)) ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if(!empty($s->job_title)): ?>
                                <span style="font-weight: 500;"><?= htmlspecialchars($s->job_title) ?></span>
                                <div style="font-size:11px; color: var(--text-muted); margin-top: 2px;"><?= htmlspecialchars($s->department ?: 'N/A') ?></div>
                            <?php else: ?>
                                <span style="color: var(--text-muted); font-style: italic;">N/A</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php 
                                $email = $s->employee_email ?: $s->user_email ?: '';
                                $phone = $s->phone ?: '';
                            ?>
                            <div><?= htmlspecialchars($email ?: '—') ?></div>
                            <div style="font-size:11px; color: var(--text-muted); margin-top: 2px;"><?= htmlspecialchars($phone ?: '—') ?></div>
                        </td>
                        <td>
                            <?php if(!empty($s->username)): ?>
                                <div style="display: flex; flex-direction: column; gap: 4px;">
                                    <span style="font-weight:600; color: var(--text-accent); font-size:12.5px; display: inline-flex; align-items: center; gap: 4px;">
                                        <i class="ph ph-shield-check" style="font-size: 15px;"></i> <?= htmlspecialchars($s->username) ?>
                                    </span>
                                    
                                    <div style="margin-top: 2px;">
                                        <?php if (!empty($s->roles)): ?>
                                            <?php foreach ($s->roles as $role): ?>
                                                <span class="role-badge role-<?= strtolower(str_replace(' ', '-', $role->name)) ?>" style="font-size: 9px; padding: 2px 6px;"><?= htmlspecialchars($role->name) ?></span>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <span class="role-badge role-<?= strtolower($s->user_role) ?>" style="font-size: 9px; padding: 2px 6px;"><?= htmlspecialchars($s->user_role) ?></span>
                                        <?php endif; ?>
                                    </div>

                                    <?php if(!empty($s->accessible_apps)): ?>
                                        <div style="display: flex; flex-wrap: wrap; gap: 3px; margin-top: 2px;">
                                            <?php foreach(explode(',', $s->accessible_apps) as $app): ?>
                                                <span style="font-size: 9px; padding: 1px 5px; background: rgba(79, 70, 229, 0.08); color: var(--text-accent); border-radius: 4px; font-weight: 600; border: 0.5px solid rgba(79, 70, 229, 0.15); display: inline-block;">
                                                    <?= htmlspecialchars(trim($app)) ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if(!empty($s->signature_path)): ?>
                                        <span style="font-size:11px; color:#10b981; font-weight:600; display:inline-flex; align-items:center; gap:3px;"><i class="ph ph-pencil-simple-line"></i> Signature Uploaded</span>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <span style="font-size:12px; color: var(--text-muted); font-style: italic;">No login created</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php 
                                $status = $s->employee_status ?: $s->user_status ?: 'Active';
                            ?>
                            <span class="status-badge status-<?= $status ?>"><?= $status ?></span>
                        </td>
                        <td style="text-align: right; font-weight: 700; color: var(--text-main);">
                            <?= $s->base_salary ? number_format($s->base_salary, 2) : '—' ?>
                        </td>
                        <td style="text-align: right;">
                            <button class="btn btn-outline" style="padding: 6px 12px; font-size: 12px; border-radius: 8px;" 
                                    data-employee-id="<?= htmlspecialchars($s->employee_id ?? '') ?>"
                                    data-user-id="<?= htmlspecialchars($s->user_id ?? '') ?>"
                                    data-fname="<?= htmlspecialchars($s->first_name ?? '') ?>"
                                    data-lname="<?= htmlspecialchars($s->last_name ?? '') ?>"
                                    data-email="<?= htmlspecialchars($s->employee_email ?: $s->user_email ?: '') ?>"
                                    data-phone="<?= htmlspecialchars($s->phone ?? '') ?>"
                                    data-dept="<?= htmlspecialchars($s->department ?? '') ?>"
                                    data-title="<?= htmlspecialchars($s->job_title ?? '') ?>"
                                    data-salary="<?= htmlspecialchars($s->base_salary ?? '0') ?>"
                                    data-hdate="<?= htmlspecialchars($s->hire_date ?? '') ?>"
                                    data-status="<?= htmlspecialchars($status) ?>"
                                    data-username="<?= htmlspecialchars($s->username ?? '') ?>"
                                    data-user-role-id="<?= htmlspecialchars(!empty($s->roles) ? $s->roles[0]->id : '') ?>"
                                    data-user-role-ids="<?= htmlspecialchars(!empty($s->roles) ? implode(',', array_column($s->roles, 'id')) : '') ?>"
                                    data-apps="<?= htmlspecialchars($s->accessible_apps ?? '') ?>"
                                    data-sig-path="<?= htmlspecialchars($s->signature_path ?? '') ?>"
                                    onclick="openEditModal(this)">
                                <i class="ph ph-note-pencil"></i> Edit
                            </button>
                            
                            <?php if (!empty($s->user_id)): ?>
                                <?php if ($s->user_id != $_SESSION['user_id']): ?>
                                    <button class="btn btn-outline" style="padding: 6px 12px; font-size: 12px; border-radius: 8px; border-color: rgba(239, 68, 68, 0.25); color: #ef4444 !important; margin-left: 4px;" 
                                            onclick="openDeleteTransferModal(<?= $s->user_id ?>, '<?= htmlspecialchars($s->username, ENT_QUOTES) ?>')">
                                        <i class="ph ph-trash"></i> Delete
                                    </button>
                                <?php endif; ?>
                            <?php elseif (!empty($s->employee_id)): ?>
                                <button class="btn btn-outline" style="padding: 6px 12px; font-size: 12px; border-radius: 8px; border-color: rgba(239, 68, 68, 0.25); color: #ef4444 !important; margin-left: 4px;" 
                                        onclick="confirmDeleteEmployee(<?= $s->employee_id ?>, '<?= htmlspecialchars($s->first_name . ' ' . $s->last_name, ENT_QUOTES) ?>')">
                                    <i class="ph ph-trash"></i> Delete
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ADD STAFF MODAL -->
<div class="modal" id="staffModal">
    <div class="modal-content">
        <h3 style="margin-top:0; margin-bottom: 20px; font-size: 18px; font-weight: 700; display: flex; align-items: center; gap: 8px; color: var(--text-main);">
            <i class="ph ph-user-plus" style="color: var(--text-accent);"></i> Add New Staff Member
        </h3>
        <form action="<?= APP_URL ?>/user/create" method="POST" enctype="multipart/form-data">
            
            <h4 style="margin: 0 0 12px 0; font-size: 14px; border-bottom: 1px solid var(--glass-border); padding-bottom: 6px;">1. Employee Details</h4>
            <div class="grid-2">
                <div class="form-group"><label>First Name *</label><input type="text" name="first_name" class="form-control" required></div>
                <div class="form-group"><label>Last Name *</label><input type="text" name="last_name" class="form-control" required></div>
            </div>
            
            <div class="grid-2">
                <div class="form-group"><label>Email</label><input type="email" name="email" class="form-control"></div>
                <div class="form-group"><label>Phone</label><input type="text" name="phone" class="form-control"></div>
            </div>
 
            <div class="grid-2">
                <div class="form-group"><label>Department</label><input type="text" name="department" class="form-control" placeholder="e.g. Operations"></div>
                <div class="form-group">
                    <label>Job Title / Role *</label>
                    <select name="job_title" class="form-control" required>
                        <option value="Office">Office Staff</option>
                        <option value="Rep">Rep (Sales Representative)</option>
                        <option value="Driver">Driver</option>
                        <option value="Admin">Admin</option>
                        <option value="Accountant">Accountant</option>
                    </select>
                </div>
            </div>
 
            <div class="grid-2">
                <div class="form-group"><label>Hire Date</label><input type="date" name="hire_date" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
                <div class="form-group"><label>Base Salary (Rs:) *</label><input type="number" name="base_salary" step="0.01" min="0" class="form-control" required></div>
            </div>

            <!-- Optional user login credentials -->
            <div style="margin-top: 15px; padding-top: 15px; border-top: 1px dashed var(--glass-border);">
                <label style="display: flex; align-items: center; gap: 8px; font-weight: 600; font-size: 13.5px; cursor: pointer; color: var(--text-main);">
                    <input type="checkbox" name="create_login" id="createLoginCheckbox" value="1" onchange="toggleLoginFields()">
                    <span>Enable System Access / Login Credentials</span>
                </label>
            </div>

            <div id="loginFieldsContainer" style="display: none; margin-top: 15px;">
                <h4 style="margin: 0 0 12px 0; font-size: 14px; border-bottom: 1px solid var(--glass-border); padding-bottom: 6px;">2. User Credentials &amp; Permissions</h4>
                <div class="grid-2">
                    <div class="form-group">
                        <label>Username *</label>
                        <input type="text" name="username" id="loginUsername" class="form-control" placeholder="e.g. johndoe">
                    </div>
                    <div class="form-group">
                        <label>Password *</label>
                        <input type="password" name="password" id="loginPassword" class="form-control" placeholder="Enter secure password">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Assigned System Roles *</label>
                    <div style="background: rgba(255, 255, 255, 0.05); border: 1px solid var(--glass-border); padding: 12px; border-radius: 10px; display: flex; flex-direction: column; gap: 8px; max-height: 120px; overflow-y: auto;">
                        <?php foreach ($data['roles'] as $role): ?>
                            <label style="display: flex; align-items: center; gap: 8px; font-weight: 500; cursor: pointer; margin: 0; color: var(--text-main);">
                                <input type="checkbox" name="roles[]" value="<?= $role->id ?>" style="width:16px; height:16px;">
                                <span><?= htmlspecialchars($role->name) ?></span>
                                <span style="font-size: 11px; color: var(--text-muted);"> - <?= htmlspecialchars($role->description) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="grid-2" style="grid-template-columns: 1fr; align-items: start;">
                    <div class="form-group">
                        <label style="margin-bottom: 8px;">Mobile/Web App Permissions *</label>
                        <div style="display: flex; flex-wrap: wrap; gap: 12px; background: rgba(255, 255, 255, 0.05); border: 1px solid var(--glass-border); padding: 12px; border-radius: 10px;">
                            <label style="display: inline-flex; align-items: center; gap: 6px; font-size: 12.5px; font-weight: 500; cursor: pointer; color: var(--text-main);">
                                <input type="checkbox" name="accessible_apps[]" value="ERP System" checked> ERP System
                            </label>
                            <label style="display: inline-flex; align-items: center; gap: 6px; font-size: 12.5px; font-weight: 500; cursor: pointer; color: var(--text-main);">
                                <input type="checkbox" name="accessible_apps[]" value="Driver App"> Driver App
                            </label>
                            <label style="display: inline-flex; align-items: center; gap: 6px; font-size: 12.5px; font-weight: 500; cursor: pointer; color: var(--text-main);">
                                <input type="checkbox" name="accessible_apps[]" value="Rep App"> Rep App
                            </label>
                            <label style="display: inline-flex; align-items: center; gap: 6px; font-size: 12.5px; font-weight: 500; cursor: pointer; color: var(--text-main);">
                                <input type="checkbox" name="accessible_apps[]" value="Curtiss Portal"> Curtiss Portal
                            </label>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Digital Signature (Optional PNG/JPG)</label>
                    <input type="file" name="signature" class="form-control" accept=".png, .jpg, .jpeg" style="padding: 6px;">
                </div>
            </div>
            
            <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--glass-border);">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('staffModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn">Save Staff Member</button>
            </div>
        </form>
    </div>
</div>

<!-- EDIT STAFF MODAL -->
<div class="modal" id="editStaffModal">
    <div class="modal-content">
        <h3 style="margin-top:0; margin-bottom: 20px; font-size: 18px; font-weight: 700; display: flex; align-items: center; gap: 8px; color: var(--text-main);">
            <i class="ph ph-note-pencil" style="color: var(--text-accent);"></i> Edit Staff Member Details
        </h3>
        <form action="" method="POST" id="editStaffForm" enctype="multipart/form-data">
            
            <h4 style="margin: 0 0 12px 0; font-size: 14px; border-bottom: 1px solid var(--glass-border); padding-bottom: 6px;">1. Employee Details</h4>
            <div class="grid-2">
                <div class="form-group"><label>First Name *</label><input type="text" name="first_name" id="edit_first_name" class="form-control" required></div>
                <div class="form-group"><label>Last Name *</label><input type="text" name="last_name" id="edit_last_name" class="form-control" required></div>
            </div>
            
            <div class="grid-2">
                <div class="form-group"><label>Email</label><input type="email" name="email" id="edit_email" class="form-control"></div>
                <div class="form-group"><label>Phone</label><input type="text" name="phone" id="edit_phone" class="form-control"></div>
            </div>
 
            <div class="grid-2">
                <div class="form-group"><label>Department</label><input type="text" name="department" id="edit_department" class="form-control" placeholder="e.g. Operations"></div>
                <div class="form-group">
                    <label>Job Title / Role *</label>
                    <select name="job_title" id="edit_job_title" class="form-control" required>
                        <option value="Office">Office Staff</option>
                        <option value="Rep">Rep (Sales Representative)</option>
                        <option value="Driver">Driver</option>
                        <option value="Admin">Admin</option>
                        <option value="Accountant">Accountant</option>
                    </select>
                </div>
            </div>
 
            <div class="grid-2">
                <div class="form-group"><label>Hire Date</label><input type="date" name="hire_date" id="edit_hire_date" class="form-control" required></div>
                <div class="form-group"><label>Base Salary (Rs:) *</label><input type="number" name="base_salary" id="edit_base_salary" step="0.01" min="0" class="form-control" required></div>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label>Staff Status *</label>
                    <select name="status" id="edit_status" class="form-control" required>
                        <option value="Active">Active</option>
                        <option value="Terminated">Terminated</option>
                    </select>
                </div>
            </div>

            <!-- Optional user login credentials -->
            <div style="margin-top: 15px; padding-top: 15px; border-top: 1px dashed var(--glass-border);">
                <label style="display: flex; align-items: center; gap: 8px; font-weight: 600; font-size: 13.5px; cursor: pointer; color: var(--text-main);">
                    <input type="checkbox" name="create_login" id="editCreateLoginCheckbox" value="1" onchange="toggleEditLoginFields()">
                    <span>Enable System Access / Login Credentials</span>
                </label>
            </div>

            <div id="editLoginFieldsContainer" style="display: none; margin-top: 15px;">
                <h4 style="margin: 0 0 12px 0; font-size: 14px; border-bottom: 1px solid var(--glass-border); padding-bottom: 6px;">2. User Credentials &amp; Permissions</h4>
                <div class="grid-2">
                    <div class="form-group">
                        <label>Username *</label>
                        <input type="text" name="username" id="editLoginUsername" class="form-control" placeholder="e.g. johndoe">
                    </div>
                    <div class="form-group">
                        <label>Password (Leave blank to keep current)</label>
                        <input type="password" name="password" id="editLoginPassword" class="form-control" placeholder="Enter secure password">
                    </div>
                </div>

                <div class="form-group">
                    <label>Assigned System Roles *</label>
                    <div style="background: rgba(255, 255, 255, 0.05); border: 1px solid var(--glass-border); padding: 12px; border-radius: 10px; display: flex; flex-direction: column; gap: 8px; max-height: 120px; overflow-y: auto;">
                        <?php foreach ($data['roles'] as $role): ?>
                            <label style="display: flex; align-items: center; gap: 8px; font-weight: 500; cursor: pointer; margin: 0; color: var(--text-main);">
                                <input type="checkbox" name="roles[]" class="edit-role-checkbox" id="edit_role_<?= $role->id ?>" value="<?= $role->id ?>" style="width:16px; height:16px;">
                                <span><?= htmlspecialchars($role->name) ?></span>
                                <span style="font-size: 11px; color: var(--text-muted);"> - <?= htmlspecialchars($role->description) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label>Login Status *</label>
                        <select name="user_status" id="editLoginStatus" class="form-control">
                            <option value="Active">Active</option>
                            <option value="Blocked">Blocked</option>
                        </select>
                    </div>
                </div>

                <div class="grid-2" style="grid-template-columns: 1fr; align-items: start;">
                    <div class="form-group">
                        <label style="margin-bottom: 8px;">Mobile/Web App Permissions *</label>
                        <div style="display: flex; flex-wrap: wrap; gap: 12px; background: rgba(255, 255, 255, 0.05); border: 1px solid var(--glass-border); padding: 12px; border-radius: 10px;">
                            <label style="display: inline-flex; align-items: center; gap: 6px; font-size: 12.5px; font-weight: 500; cursor: pointer; color: var(--text-main);">
                                <input type="checkbox" name="accessible_apps[]" value="ERP System" id="editAppERP"> ERP System
                            </label>
                            <label style="display: inline-flex; align-items: center; gap: 6px; font-size: 12.5px; font-weight: 500; cursor: pointer; color: var(--text-main);">
                                <input type="checkbox" name="accessible_apps[]" value="Driver App" id="editAppDriver"> Driver App
                            </label>
                            <label style="display: inline-flex; align-items: center; gap: 6px; font-size: 12.5px; font-weight: 500; cursor: pointer; color: var(--text-main);">
                                <input type="checkbox" name="accessible_apps[]" value="Rep App" id="editAppRep"> Rep App
                            </label>
                            <label style="display: inline-flex; align-items: center; gap: 6px; font-size: 12.5px; font-weight: 500; cursor: pointer; color: var(--text-main);">
                                <input type="checkbox" name="accessible_apps[]" value="Curtiss Portal" id="editAppPortal"> Curtiss Portal
                            </label>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Digital Signature (Optional PNG/JPG)</label>
                    <div id="editSigContainer" style="display:none; align-items:center; gap:15px; margin-bottom:10px; background:rgba(255,255,255,0.05); padding:8px; border-radius:6px; border:1px solid var(--glass-border);">
                        <img id="editSigPreview" src="" style="max-height:40px; background:white; padding:2px; border-radius:4px;" alt="Signature">
                        <label style="display:flex; align-items:center; gap:5px; font-weight:500; font-size:12px; margin:0; cursor:pointer; color: var(--text-main);">
                            <input type="checkbox" name="delete_signature" value="1"> Delete signature
                        </label>
                    </div>
                    <input type="file" name="signature" class="form-control" accept=".png, .jpg, .jpeg" style="padding: 6px;">
                </div>
            </div>
            
            <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--glass-border);">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('editStaffModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn">Update Staff Details</button>
            </div>
        </form>
    </div>
</div>

<!-- TRANSFER DATA & DELETE MODAL -->
<div class="modal" id="transferDeleteModal">
    <div class="modal-content" style="max-width: 500px;">
        <h3 style="margin-top:0; margin-bottom: 15px; font-size: 18px; font-weight: 700; color: #ef4444; display: flex; align-items: center; gap: 8px;">
            <i class="ph ph-warning-circle"></i> Transfer Data &amp; Delete User
        </h3>
        <p style="font-size: 13.5px; line-height: 1.5; margin-bottom: 20px; color: var(--text-muted);">
            The user <strong id="delete_username_label" style="color: var(--text-main);"></strong> has active sales, invoice, or route data associated with their account. Before deleting this account, you must select another active user/representative to transfer all their records to.
        </p>
        <form id="transferDeleteForm" method="POST" action="">
            <div class="form-group">
                <label>Transfer Associated Data To *</label>
                <select name="transfer_to" class="form-control" required id="transfer_to_select">
                    <!-- Populated dynamically via JS -->
                </select>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--glass-border);">
                <button type="button" class="btn btn-outline" onclick="closeDeleteTransferModal()">Cancel</button>
                <button type="submit" class="btn btn-danger" style="background: #ef4444; color: #fff !important;">Transfer Data &amp; Delete</button>
            </div>
        </form>
    </div>
</div>

<script>
// JSON array of system users for data transfer options
const allSystemUsers = <?= json_encode(array_map(function($u) {
    return [
        'id' => $u->id,
        'username' => $u->username,
        'role' => $u->role,
        'name' => ($u->first_name ? ($u->first_name . ' ' . $u->last_name) : '')
    ];
}, $data['users'])) ?>;

function toggleLoginFields() {
    const checked = document.getElementById('createLoginCheckbox').checked;
    const container = document.getElementById('loginFieldsContainer');
    container.style.display = checked ? 'block' : 'none';
    
    document.getElementById('loginUsername').required = checked;
    document.getElementById('loginPassword').required = checked;
}

function openEditModal(btn) {
    const employeeId = btn.getAttribute('data-employee-id');
    const userId = btn.getAttribute('data-user-id');
    const fname = btn.getAttribute('data-fname');
    const lname = btn.getAttribute('data-lname');
    const email = btn.getAttribute('data-email');
    const phone = btn.getAttribute('data-phone');
    const dept = btn.getAttribute('data-dept');
    const title = btn.getAttribute('data-title');
    const salary = btn.getAttribute('data-salary');
    const hdate = btn.getAttribute('data-hdate');
    const status = btn.getAttribute('data-status');
    
    const username = btn.getAttribute('data-username');
    const userRoleIdsStr = btn.getAttribute('data-user-role-ids') || '';
    const userRoleIds = userRoleIdsStr.split(',').filter(id => id).map(id => parseInt(id));
    const apps = btn.getAttribute('data-apps') || '';
    const sigPath = btn.getAttribute('data-sig-path') || '';

    // Set action URL
    // If it has user ID, we edit by user ID, else by employee ID
    const submitId = userId ? userId : employeeId;
    document.getElementById('editStaffForm').action = '<?= APP_URL ?>/user/edit/' + submitId;

    document.getElementById('edit_first_name').value = fname;
    document.getElementById('edit_last_name').value = lname;
    document.getElementById('edit_email').value = email;
    document.getElementById('edit_phone').value = phone;
    document.getElementById('edit_department').value = dept;
    document.getElementById('edit_job_title').value = title;
    document.getElementById('edit_base_salary').value = salary;
    document.getElementById('edit_hire_date').value = hdate;
    document.getElementById('edit_status').value = status;

    // Reset login fields
    const hasLogin = !!username;
    document.getElementById('editCreateLoginCheckbox').checked = hasLogin;
    document.getElementById('editLoginUsername').value = username || '';
    document.getElementById('editLoginPassword').value = '';
    document.getElementById('editLoginStatus').value = status || 'Active';

    // Clear role checkboxes first
    document.querySelectorAll('.edit-role-checkbox').forEach(cb => {
        cb.checked = false;
    });
    // Check relevant role checkboxes
    userRoleIds.forEach(id => {
        const cb = document.getElementById('edit_role_' + id);
        if (cb) cb.checked = true;
    });

    // Set apps checkboxes
    const appList = apps.split(',').map(s => s.trim());
    document.getElementById('editAppERP').checked = appList.includes('ERP System');
    document.getElementById('editAppDriver').checked = appList.includes('Driver App');
    document.getElementById('editAppRep').checked = appList.includes('Rep App');
    document.getElementById('editAppPortal').checked = appList.includes('Curtiss Portal');

    // Signature Preview
    if (sigPath) {
        document.getElementById('editSigPreview').src = '<?= APP_URL ?>/public/uploads/' + sigPath;
        document.getElementById('editSigContainer').style.display = 'flex';
    } else {
        document.getElementById('editSigContainer').style.display = 'none';
    }

    toggleEditLoginFields();
    document.getElementById('editStaffModal').style.display = 'flex';
}

function toggleEditLoginFields() {
    const checked = document.getElementById('editCreateLoginCheckbox').checked;
    const container = document.getElementById('editLoginFieldsContainer');
    container.style.display = checked ? 'block' : 'none';
    
    document.getElementById('editLoginUsername').required = checked;
}

function confirmDeleteEmployee(id, name) {
    if (confirm("Are you sure you want to delete employee " + name + "? This employee has no active system login or sales records.")) {
        window.location.href = "<?= APP_URL ?>/user/delete_employee/" + id;
    }
}

function openDeleteTransferModal(userId, username) {
    document.getElementById('delete_username_label').innerText = username;
    document.getElementById('transferDeleteForm').action = '<?= APP_URL ?>/user/delete/' + userId;
    
    // Populate select dropdown dynamic options excluding the user being deleted
    const select = document.getElementById('transfer_to_select');
    select.innerHTML = '<option value="">-- Select Active Representative/User --</option>';
    
    allSystemUsers.forEach(u => {
        if (u.id != userId) {
            const opt = document.createElement('option');
            opt.value = u.id;
            opt.innerText = u.username + (u.name ? ' (' + u.name + ')' : '') + ' - ' + u.role.toUpperCase();
            select.appendChild(opt);
        }
    });
    
    document.getElementById('transferDeleteModal').style.display = 'flex';
}

function closeDeleteTransferModal() {
    document.getElementById('transferDeleteModal').style.display = 'none';
}
</script>