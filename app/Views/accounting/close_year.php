<?php
?>
<style>
    .header-actions { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 20px; }
    .btn { padding: 10px 20px; background: #c62828; color: #fff; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; font-size: 16px; font-weight:bold;}
    .btn:hover { background: #b71c1c; }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; margin-bottom: 5px; font-size: 14px; font-weight: 500; }
    .form-control { width: 100%; padding: 10px 12px; border: 1px solid var(--mac-border); border-radius: 4px; background: transparent; color: var(--text-main); font-size: 16px; box-sizing: border-box;}
    
    .warning-box { background: #ffebee; border-left: 4px solid #c62828; padding: 20px; border-radius: 4px; margin-bottom: 30px; color: #c62828; }
    .warning-box h3 { margin-top: 0; color: #c62828; }
</style>

<div class="header-actions">
    <div>
        <h2 style="margin: 0 0 5px 0;">Close Financial Year</h2>
        <p style="margin: 0; color: #666;">Permanently lock the ledger and roll over Net Income.</p>
    </div>
</div>

<?php if(!empty($data['error'])): ?>
    <div style="padding: 10px; background:#ffebee; color:#c62828; border-radius:4px; margin-bottom:15px; border: 1px solid #ef9a9a;"><?= $data['error'] ?></div>
<?php endif; ?>
<?php if(!empty($data['success'])): ?>
    <div style="padding: 15px; background:#e8f5e9; color:#2e7d32; border-radius:4px; margin-bottom:15px; border: 1px solid #a5d6a7; font-weight: bold;">✓ <?= $data['success'] ?></div>
<?php endif; ?>

<div class="warning-box">
    <h3>⚠ Critical Accounting Operation</h3>
    <p>Running the Year-End Close will perform the following irreversible actions:</p>
    <ul style="line-height: 1.6;">
        <li>Calculate total Net Income/Loss for all unclosed transactions up to your chosen End Date.</li>
        <li>Generate a Journal Entry to force all Revenue and Expense accounts to a balance of Rs: 0.00.</li>
        <li>Transfer the Net Income directly into your selected Retained Earnings account.</li>
        <li>Permanently lock all journal entries prior to the End Date so they can no longer be edited or deleted.</li>
    </ul>
    <p><strong>Please ensure you have printed your final Trial Balance, P&L, and Balance Sheet before clicking this button.</strong></p>
</div>

<form action="<?= APP_URL ?>/accounting/close_year" method="POST" style="background: rgba(0,0,0,0.02); padding: 25px; border-radius: 8px; border: 1px solid var(--mac-border); max-width: 600px;">
    <input type="hidden" name="action" value="close_books">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
    
    <div class="form-group">
        <label>Financial Year End Date (Cutoff Date)</label>
        <input type="date" name="end_date" class="form-control" required>
    </div>

    <div class="form-group">
        <label>Transfer Net Income To (Equity Account)</label>
        <select name="retained_earnings_id" class="form-control" required>
            <?php foreach($data['equity_accounts'] as $acc): ?>
                <option value="<?= $acc->id ?>" <?= stripos($acc->account_name, 'retained') !== false ? 'selected' : '' ?>>
                    <?= $acc->account_code ?> - <?= htmlspecialchars($acc->account_name) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <label>Confirm Account Password (Security Verification)</label>
        <input type="password" name="confirm_password" class="form-control" placeholder="Enter your password to verify closing" required>
    </div>

    <div style="margin-top: 30px; text-align: right;">
        <button type="submit" class="btn" onclick="return confirm('Are you absolutely sure you want to close the books? This cannot be undone.');">Close Financial Year</button>
    </div>
</form>