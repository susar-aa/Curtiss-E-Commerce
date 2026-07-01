<?php
?>
<style>
    .header-actions { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .btn { padding: 8px 16px; background: #0066cc; color: #fff; border: none; border-radius: 4px; cursor: pointer; text-decoration: none;}
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; margin-bottom: 5px; font-size: 13px; font-weight: 500; }
    .form-control { width: 100%; padding: 8px 12px; border: 1px solid var(--mac-border); border-radius: 4px; background: transparent; color: var(--text-main); box-sizing: border-box; }
    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
    .grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; }
</style>

<div class="card">
    <div class="header-actions">
        <h2>Record an Expense</h2>
        <!-- FIXED: Changed /expense to /expenses -->
        <a href="<?= APP_URL ?>/expenses" style="color: #666; text-decoration:none;">&larr; Back</a>
    </div>

    <?php if(!empty($data['error'])): ?>
        <div style="padding: 10px; background:#ffebee; color:#c62828; border-radius:4px; margin-bottom:15px;"><?= $data['error'] ?></div>
    <?php endif; ?>

    <!-- FIXED: Changed action from /expense/create to /expenses/create -->
    <form action="<?= APP_URL ?>/expenses/create" method="POST">
        
        <!-- Accounting Logic Section -->
        <div style="background: rgba(239, 108, 0, 0.05); padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid rgba(239, 108, 0, 0.2);">
            <h4 style="margin-top:0; color:#ef6c00;">ERP Accounting Routing</h4>
            <div class="grid-2">
                <div class="form-group">
                    <label>Debit Account (What are you paying for?)</label>
                    <select name="expense_account" class="form-control" required>
                        <option value="">Select Expense Account...</option>
                        <?php foreach($data['expense_accounts'] as $acc): ?>
                            <option value="<?= $acc->id ?>"><?= $acc->account_code ?> - <?= htmlspecialchars($acc->account_name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Credit Account (How are you paying?)</label>
                    <select name="payment_account" class="form-control" required>
                        <option value="">Select Asset/Bank Account...</option>
                        <?php foreach($data['payment_accounts'] as $acc): ?>
                            <option value="<?= $acc->id ?>"><?= $acc->account_code ?> - <?= htmlspecialchars($acc->account_name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="grid-3">
            <div class="form-group">
                <label>Vendor / Payee</label>
                <select name="vendor_id" class="form-control">
                    <option value="">None / Miscellaneous</option>
                    <?php foreach($data['vendors'] as $ven): ?>
                        <option value="<?= $ven->id ?>"><?= htmlspecialchars($ven->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Expense Date</label>
                <input type="date" name="expense_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="form-group">
                <label>Reference # / Receipt #</label>
                <input type="text" name="reference" class="form-control" value="<?= $data['reference'] ?>" required>
            </div>
        </div>

        <div class="form-group">
            <label>Description / Memo *</label>
            <input type="text" name="description" class="form-control" placeholder="Office supplies, Internet bill, etc." required>
        </div>

        <div class="form-group" style="width: 30%;">
            <label>Amount (Rs:) *</label>
            <input type="number" name="amount" class="form-control" step="0.01" min="0.01" required style="font-size: 18px; font-weight: bold;">
        </div>

        <div style="margin-top: 20px; text-align: right;">
            <button type="submit" class="btn" style="padding: 12px 24px; font-size: 16px;">Save & Post Expense to Ledger</button>
        </div>
    </form>
</div>