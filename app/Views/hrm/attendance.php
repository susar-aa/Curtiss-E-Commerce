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
    .status-Present { 
        background: rgba(16, 185, 129, 0.15); 
        color: #10b981; 
    }
    .status-Late { 
        background: rgba(245, 158, 11, 0.15); 
        color: #f59e0b; 
    }
    .status-Absent { 
        background: rgba(239, 68, 68, 0.15); 
        color: #ef4444; 
    }

    /* modal/panel styling */
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
    
    .quick-clock-panel {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid var(--glass-border);
        border-radius: 16px;
        padding: 20px;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 20px;
    }
</style>

<div class="hrm-container">
    <!-- Quick Clock In/Out Panel -->
    <div class="quick-clock-panel">
        <div>
            <h3 style="margin: 0; font-size: 16px; font-weight: 700; color: var(--text-main); display: flex; align-items: center; gap: 8px;">
                <i class="ph ph-clock" style="color: var(--text-accent); font-size: 18px;"></i> Live Shift Clocking
            </h3>
            <p style="margin: 4px 0 0 0; font-size: 12.5px; color: var(--text-muted);">Quickly clock in or clock out active staff members for today's work date.</p>
        </div>
        <form action="<?= APP_URL ?>/attendance/clock" method="POST" style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin: 0;">
            <select name="employee_id" class="form-control" style="width: auto; min-width: 200px;" required>
                <option value="">Select Employee...</option>
                <?php foreach($data['employees'] as $emp): ?>
                    <option value="<?= $emp->id ?>"><?= htmlspecialchars($emp->first_name . ' ' . $emp->last_name) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" name="clock_action" value="in" class="btn" style="background: #10b981;"><i class="ph ph-sign-in"></i> Clock In</button>
            <button type="submit" name="clock_action" value="out" class="btn" style="background: #ef4444;"><i class="ph ph-sign-out"></i> Clock Out</button>
        </form>
    </div>

    <div class="glass-card">
        <div class="header-actions">
            <div class="header-title-wrap">
                <h2><i class="ph ph-fingerprint" style="color: var(--text-accent);"></i> Attendance Registry</h2>
                <p>Monitor daily attendance logs, work durations, and shift statuses.</p>
            </div>
            <div style="display: flex; gap: 10px;">
                <a href="<?= APP_URL ?>/user" class="btn btn-outline"><i class="ph ph-arrow-left"></i> Employee Directory</a>
                <button class="btn" onclick="document.getElementById('manualAttendanceModal').style.display='flex'"><i class="ph ph-plus-circle"></i> Log Manual Attendance</button>
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
                        <th>Work Date</th>
                        <th>Clock In</th>
                        <th>Clock Out</th>
                        <th>Total Hours</th>
                        <th>Status</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($data['attendance_records'])): ?>
                    <tr><td colspan="7" style="text-align: center; color: var(--text-muted); padding: 40px;">No attendance logs found.</td></tr>
                    <?php else: foreach($data['attendance_records'] as $record): ?>
                    <tr>
                        <td>
                            <strong style="color: var(--text-main);"><?= htmlspecialchars($record->first_name . ' ' . $record->last_name) ?></strong>
                            <div style="font-size:11px; color: var(--text-muted); margin-top: 2px;"><?= htmlspecialchars($record->job_title) ?> (<?= htmlspecialchars($record->department ?: 'N/A') ?>)</div>
                        </td>
                        <td><strong><?= date('d M Y', strtotime($record->work_date)) ?></strong></td>
                        <td>
                            <span style="font-family: monospace; font-weight: 600;"><?= date('h:i A', strtotime($record->clock_in)) ?></span>
                        </td>
                        <td>
                            <?php if(!empty($record->clock_out)): ?>
                                <span style="font-family: monospace; font-weight: 600;"><?= date('h:i A', strtotime($record->clock_out)) ?></span>
                            <?php else: ?>
                                <span style="font-size: 12px; color: #f59e0b; font-style: italic; font-weight: 600;">Active Session</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php 
                            if (!empty($record->clock_out)) {
                                $diff = strtotime($record->clock_out) - strtotime($record->clock_in);
                                $hours = round($diff / 3600, 2);
                                echo "<strong>" . $hours . " Hrs</strong>";
                            } else {
                                echo "—";
                            }
                            ?>
                        </td>
                        <td>
                            <span class="status-badge status-<?= $record->status ?>"><?= $record->status ?></span>
                        </td>
                        <td style="text-align: right;">
                            <a href="<?= APP_URL ?>/attendance/delete/<?= $record->id ?>" class="btn btn-outline" style="padding: 6px 12px; font-size: 11.5px; border-radius: 8px; border-color: rgba(239, 68, 68, 0.25); color: #ef4444 !important;" onclick="return confirm('Are you sure you want to delete this attendance log?')">
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

<div class="modal" id="manualAttendanceModal">
    <div class="modal-content">
        <h3 style="margin-top:0; margin-bottom: 20px; font-size: 18px; font-weight: 700; display: flex; align-items: center; gap: 8px; color: var(--text-main);">
            <i class="ph ph-calendar-plus" style="color: var(--text-accent);"></i> Record Manual Attendance
        </h3>
        <form action="<?= APP_URL ?>/attendance/manual" method="POST">
            <div class="form-group">
                <label>Employee *</label>
                <select name="employee_id" class="form-control" required>
                    <option value="">Select Employee...</option>
                    <?php foreach($data['employees'] as $emp): ?>
                        <option value="<?= $emp->id ?>"><?= htmlspecialchars($emp->first_name . ' ' . $emp->last_name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Work Date *</label>
                <input type="date" name="work_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label>Clock In Time *</label>
                    <input type="time" name="clock_in" class="form-control" value="09:00" required>
                </div>
                <div class="form-group">
                    <label>Clock Out Time</label>
                    <input type="time" name="clock_out" class="form-control" value="17:00">
                </div>
            </div>

            <div class="form-group">
                <label>Status *</label>
                <select name="status" class="form-control" required>
                    <option value="Present">Present</option>
                    <option value="Late">Late</option>
                    <option value="Absent">Absent</option>
                </select>
            </div>
            
            <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--glass-border);">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('manualAttendanceModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn">Record Logs</button>
            </div>
        </form>
    </div>
</div>
