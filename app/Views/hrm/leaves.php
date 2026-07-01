<?php
?>
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
    
    .status-badge { 
        padding: 4px 10px; 
        border-radius: 20px; 
        font-size: 11px; 
        font-weight: 700; 
        display: inline-block;
    }
    .status-Pending { 
        background: rgba(245, 158, 11, 0.15); 
        color: #f59e0b; 
    }
    .status-Approved { 
        background: rgba(16, 185, 129, 0.15); 
        color: #10b981; 
    }
    .status-Rejected { 
        background: rgba(239, 68, 68, 0.15); 
        color: #ef4444; 
    }

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
        max-width: 600px; 
        color: var(--text-main);
        box-sizing: border-box;
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
</style>

<div class="hrm-container">
    <div class="glass-card">
        <div class="header-actions">
            <div class="header-title-wrap">
                <h2><i class="ph ph-calendar-blank" style="color: var(--text-accent);"></i> Leave Management</h2>
                <p>Track leave requests, balances, approvals, and absences.</p>
            </div>
            <div style="display: flex; gap: 10px;">
                <a href="<?= APP_URL ?>/user" class="btn btn-outline"><i class="ph ph-arrow-left"></i> Employee Directory</a>
                <button class="btn" onclick="document.getElementById('leaveModal').style.display='flex'"><i class="ph ph-plus-circle"></i> Request Leave</button>
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
                        <th>Employee</th>
                        <th>Leave Details</th>
                        <th>Duration</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($data['leave_requests'])): ?>
                    <tr><td colspan="6" style="text-align: center; color: var(--text-muted); padding: 40px;">No leave requests found.</td></tr>
                    <?php else: foreach($data['leave_requests'] as $req): ?>
                    <tr>
                        <td>
                            <strong style="color: var(--text-main);"><?= htmlspecialchars($req->first_name . ' ' . $req->last_name) ?></strong>
                            <div style="font-size:11px; color: var(--text-muted); margin-top: 2px;"><?= htmlspecialchars($req->job_title) ?> (<?= htmlspecialchars($req->department ?: 'N/A') ?>)</div>
                        </td>
                        <td>
                            <span style="font-weight: 600; color: var(--text-accent);"><?= htmlspecialchars($req->leave_type) ?></span>
                        </td>
                        <td>
                            <div><?= date('d M Y', strtotime($req->start_date)) ?> to <?= date('d M Y', strtotime($req->end_date)) ?></div>
                            <?php 
                            $diff = strtotime($req->end_date) - strtotime($req->start_date);
                            $days = round($diff / (60 * 60 * 24)) + 1;
                            ?>
                            <div style="font-size:11px; color: var(--text-muted); margin-top: 2px; font-weight: 600;"><?= $days ?> Day(s)</div>
                        </td>
                        <td>
                            <span style="font-size: 12.5px; opacity: 0.95;"><?= htmlspecialchars($req->reason ?: '—') ?></span>
                        </td>
                        <td>
                            <span class="status-badge status-<?= $req->status ?>"><?= $req->status ?></span>
                        </td>
                        <td style="text-align: right; white-space: nowrap;">
                            <?php if ($req->status === 'Pending'): ?>
                                <a href="<?= APP_URL ?>/leave/approve/<?= $req->id ?>" class="btn" style="padding: 6px 12px; font-size: 11.5px; border-radius: 8px; background: #10b981;">
                                    <i class="ph ph-check"></i> Approve
                                </a>
                                <a href="<?= APP_URL ?>/leave/reject/<?= $req->id ?>" class="btn btn-outline" style="padding: 6px 12px; font-size: 11.5px; border-radius: 8px; border-color: rgba(239, 68, 68, 0.25); color: #ef4444 !important; margin-left: 4px;">
                                    <i class="ph ph-x"></i> Reject
                                </a>
                            <?php endif; ?>
                            <a href="<?= APP_URL ?>/leave/delete/<?= $req->id ?>" class="btn btn-outline" style="padding: 6px 12px; font-size: 11.5px; border-radius: 8px; border-color: rgba(239, 68, 68, 0.25); color: #ef4444 !important; margin-left: 4px;" onclick="return confirm('Are you sure you want to delete this leave request?')">
                                <i class="ph ph-trash"></i> Delete
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal" id="leaveModal">
    <div class="modal-content">
        <h3 style="margin-top:0; margin-bottom: 20px; font-size: 18px; font-weight: 700; display: flex; align-items: center; gap: 8px; color: var(--text-main);">
            <i class="ph ph-calendar-plus" style="color: var(--text-accent);"></i> Request Leave
        </h3>
        <form action="<?= APP_URL ?>/leave/create" method="POST">
            <div class="form-group">
                <label>Employee *</label>
                <select name="employee_id" class="form-control" required>
                    <option value="">Select Employee...</option>
                    <?php foreach($data['employees'] as $emp): ?>
                        <option value="<?= $emp->id ?>"><?= htmlspecialchars($emp->first_name . ' ' . $emp->last_name) ?> (<?= htmlspecialchars($emp->job_title) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Leave Type *</label>
                <select name="leave_type" class="form-control" required>
                    <option value="Annual">Annual Leave</option>
                    <option value="Casual">Casual Leave</option>
                    <option value="Sick">Sick Leave</option>
                    <option value="Maternity/Paternity">Maternity/Paternity Leave</option>
                    <option value="Unpaid">Unpaid Leave</option>
                </select>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label>Start Date *</label>
                    <input type="date" name="start_date" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>End Date *</label>
                    <input type="date" name="end_date" class="form-control" required>
                </div>
            </div>

            <div class="form-group">
                <label>Reason / Comments</label>
                <textarea name="reason" rows="3" class="form-control" placeholder="Provide a brief explanation..."></textarea>
            </div>
            
            <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--glass-border);">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('leaveModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn">Submit Request</button>
            </div>
        </form>
    </div>
</div>
