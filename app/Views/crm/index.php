<?php
?>
<style>
    .header-actions { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .btn { padding: 8px 16px; background: #0066cc; color: #fff; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; font-size: 14px;}
    .btn:hover { background: #005bb5; }
    .btn-outline { background: transparent; border: 1px solid #0066cc; color: #0066cc; }
    .btn-small { padding: 4px 8px; font-size: 11px; }
    .btn-success { background: #2e7d32; color: white; border: none; }
    
    .data-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    .data-table th, .data-table td { padding: 12px; text-align: left; border-bottom: 1px solid var(--mac-border); }
    .data-table th { background-color: rgba(0,0,0,0.02); font-weight: 600; font-size: 13px; }
    
    .status-badge { padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: bold; }
    .status-New { background: #e3f2fd; color: #1565c0; }
    .status-Contacted { background: #fff3e0; color: #ef6c00; }
    .status-Qualified { background: #e8f5e9; color: #2e7d32; }
    .status-Lost { background: #ffebee; color: #c62828; }
    .status-Converted { background: #f3e5f5; color: #6a1b9a; }
    
    .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 2000; align-items: center; justify-content: center; }
    .modal-content { background: var(--mac-bg); backdrop-filter: blur(20px); padding: 30px; border-radius: 12px; width: 600px; border: 1px solid var(--mac-border); }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; margin-bottom: 5px; font-size: 13px; font-weight: 500; }
    .form-control { width: 100%; padding: 8px 12px; border: 1px solid var(--mac-border); border-radius: 4px; background: transparent; color: var(--text-main); box-sizing: border-box;}
    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
</style>

<div class="card">
    <div class="header-actions">
        <h2>CRM & Lead Management</h2>
        <button class="btn" onclick="document.getElementById('leadModal').style.display='flex'">+ Add New Lead</button>
    </div>

    <?php if(!empty($data['error'])): ?>
        <div style="padding: 10px; background:#ffebee; color:#c62828; border-radius:4px; margin-bottom:15px;"><?= $data['error'] ?></div>
    <?php endif; ?>
    <?php if(!empty($data['success'])): ?>
        <div style="padding: 10px; background:#e8f5e9; color:#2e7d32; border-radius:4px; margin-bottom:15px;"><?= $data['success'] ?></div>
    <?php endif; ?>

    <table class="data-table">
        <thead>
            <tr>
                <th>Lead Name & Company</th>
                <th>Contact Info</th>
                <th>Status</th>
                <th>Assigned To</th>
                <th style="text-align: center;">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if(empty($data['leads'])): ?>
            <tr><td colspan="5" style="text-align: center; color: #888;">No leads found in the pipeline.</td></tr>
            <?php else: foreach($data['leads'] as $lead): ?>
            <tr>
                <td>
                    <strong><?= htmlspecialchars($lead->first_name . ' ' . $lead->last_name) ?></strong><br>
                    <span style="font-size:12px; color:#666;"><?= htmlspecialchars($lead->company_name) ?></span>
                </td>
                <td style="font-size:13px;"><?= htmlspecialchars($lead->email) ?><br><?= htmlspecialchars($lead->phone) ?></td>
                <td>
                    <form action="<?= APP_URL ?>/crm" method="POST" style="display:inline;">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="lead_id" value="<?= $lead->id ?>">
                        <select name="new_status" onchange="this.form.submit()" class="status-badge status-<?= $lead->status ?>" style="border:none; outline:none; cursor:pointer;" <?= $lead->status == 'Converted' ? 'disabled' : '' ?>>
                            <option value="New" <?= $lead->status == 'New' ? 'selected' : '' ?>>New</option>
                            <option value="Contacted" <?= $lead->status == 'Contacted' ? 'selected' : '' ?>>Contacted</option>
                            <option value="Qualified" <?= $lead->status == 'Qualified' ? 'selected' : '' ?>>Qualified</option>
                            <option value="Lost" <?= $lead->status == 'Lost' ? 'selected' : '' ?>>Lost</option>
                            <?php if($lead->status == 'Converted'): ?>
                                <option value="Converted" selected>Converted</option>
                            <?php endif; ?>
                        </select>
                    </form>
                </td>
                <td style="font-size: 13px;"><?= htmlspecialchars($lead->assigned_user ?? 'Unassigned') ?></td>
                <td style="text-align: center;">
                    <?php if($lead->status != 'Converted'): ?>
                    <form action="<?= APP_URL ?>/crm" method="POST" style="display:inline;">
                        <input type="hidden" name="action" value="convert_lead">
                        <input type="hidden" name="lead_id" value="<?= $lead->id ?>">
                        <button type="submit" class="btn btn-small btn-success" onclick="return confirm('Convert this lead into a Customer? They will be permanently moved to Sales.')">Convert</button>
                    </form>
                    <?php else: ?>
                        <span style="font-size: 11px; color:#888;">Customer</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<div class="modal" id="leadModal">
    <div class="modal-content">
        <h3 style="margin-top:0;">Add New Lead</h3>
        <form action="<?= APP_URL ?>/crm" method="POST">
            <input type="hidden" name="action" value="add_lead">
            
            <div class="grid-2">
                <div class="form-group"><label>First Name *</label><input type="text" name="first_name" class="form-control" required></div>
                <div class="form-group"><label>Last Name</label><input type="text" name="last_name" class="form-control"></div>
            </div>
            
            <div class="form-group"><label>Company Name</label><input type="text" name="company_name" class="form-control" placeholder="Optional"></div>

            <div class="grid-2">
                <div class="form-group"><label>Email</label><input type="email" name="email" class="form-control"></div>
                <div class="form-group"><label>Phone</label><input type="text" name="phone" class="form-control"></div>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label>Lead Source</label>
                    <select name="source" class="form-control">
                        <option value="Website">Website</option>
                        <option value="Referral">Referral</option>
                        <option value="Cold Call">Cold Call</option>
                        <option value="Social Media">Social Media</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Assign To</label>
                    <select name="assigned_to" class="form-control">
                        <option value="">Unassigned</option>
                        <?php foreach($data['users'] as $u): ?>
                            <option value="<?= $u->id ?>"><?= htmlspecialchars($u->username) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <input type="hidden" name="status" value="New">
            
            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 10px;">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('leadModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn">Save Lead</button>
            </div>
        </form>
    </div>
</div>