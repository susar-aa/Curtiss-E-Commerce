<?php
?>
<style>
    .header-actions { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .btn { padding: 8px 16px; background: #0066cc; color: #fff; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; font-size: 14px;}
    .data-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    .data-table th, .data-table td { padding: 12px; text-align: left; border-bottom: 1px solid var(--mac-border); }
    .data-table th { background-color: rgba(0,0,0,0.02); font-weight: 600; font-size: 13px; }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; margin-bottom: 5px; font-size: 13px; font-weight: 500; }
    .form-control { width: 100%; padding: 8px 12px; border: 1px solid var(--mac-border); border-radius: 4px; background: transparent; color: var(--text-main); box-sizing: border-box;}
    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
</style>

<div class="card">
    <div class="header-actions">
        <h2>Payroll Processing</h2>
        <a href="<?= APP_URL ?>/user" style="color: #666; text-decoration:none;">&larr; Back to Directory</a>
    </div>

    <?php if(!empty($data['error'])): ?>
        <div style="padding: 10px; background:#ffebee; color:#c62828; border-radius:4px; margin-bottom:15px;"><?= $data['error'] ?></div>
    <?php endif; ?>
    <?php if(!empty($data['success'])): ?>
        <div style="padding: 10px; background:#e8f5e9; color:#2e7d32; border-radius:4px; margin-bottom:15px;"><?= $data['success'] ?></div>
    <?php endif; ?>

    <form action="<?= APP_URL ?>/payroll/run" method="POST" style="background: rgba(0,0,0,0.02); padding: 20px; border-radius: 8px; margin-bottom: 30px; border: 1px solid var(--mac-border);">
        <input type="hidden" name="action" value="run_payroll">
        <h4 style="margin-top:0; border-bottom: 1px solid var(--mac-border); padding-bottom: 10px;">New Payroll Run</h4>
        
        <div class="grid-2">
            <div class="form-group">
                <label>Period Start Date</label>
                <input type="date" name="period_start" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Period End Date</label>
                <input type="date" name="period_end" class="form-control" required>
            </div>
        </div>

        <div class="grid-2">
            <div class="form-group">
                <label>Run / Payment Date</label>
                <input type="date" name="run_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div style="padding: 15px; background: rgba(0,102,204,0.05); border-radius: 8px; text-align: center;">
                <div style="font-size: 12px; color: #666; text-transform: uppercase;">Estimated Total Gross (<?= $data['active_employees_count'] ?> Employees)</div>
                <div style="font-size: 24px; font-weight: bold; color: #0066cc;">Rs: <?= number_format($data['estimated_gross'], 2) ?></div>
            </div>
        </div>

        <div class="grid-2">
            <div class="form-group">
                <label>Debit Account (Wage/Salary Expense)</label>
                <select name="wage_expense_account_id" class="form-control" required>
                    <?php foreach($data['expenses'] as $acc): ?>
                        <option value="<?= $acc->id ?>"><?= $acc->account_code ?> - <?= htmlspecialchars($acc->account_name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Credit Account (Paying Bank Account)</label>
                <select name="bank_account_id" class="form-control" required>
                    <?php foreach($data['banks'] as $acc): ?>
                        <option value="<?= $acc->id ?>"><?= $acc->account_code ?> - <?= htmlspecialchars($acc->account_name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div style="text-align: right; margin-top: 10px;">
            <button type="submit" class="btn" onclick="return confirm('Are you sure? This will permanently post a large journal entry to your ledger.')">Run Payroll & Post to Ledger</button>
        </div>
    </form>

    <h3 style="margin-top: 30px;">Past Payroll Runs</h3>
    <table class="data-table">
        <thead>
            <tr>
                <th>Run Date</th>
                <th>Pay Period</th>
                <th style="text-align: right;">Total Gross (Rs:)</th>
                <th>Processed By</th>
            </tr>
        </thead>
        <tbody>
            <?php if(empty($data['payroll_runs'])): ?>
            <tr><td colspan="4" style="text-align: center; color: #888;">No payroll runs found.</td></tr>
            <?php else: foreach($data['payroll_runs'] as $run): ?>
            <tr>
                <td><strong><?= date('M d, Y', strtotime($run->run_date)) ?></strong></td>
                <td><?= date('M d, Y', strtotime($run->period_start)) ?> - <?= date('M d, Y', strtotime($run->period_end)) ?></td>
                <td style="text-align: right; font-weight:bold; color: #c62828;">Rs: <?= number_format($run->total_gross, 2) ?></td>
                <td><?= htmlspecialchars($run->username) ?></td>
            </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>