<?php
?>
<style>
    .header-actions { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .btn { padding: 8px 16px; background: #0066cc; color: #fff; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; font-size: 14px;}
    .btn:hover { background: #005bb5; }
    .btn-outline { background: transparent; border: 1px solid #0066cc; color: #0066cc; }
    .data-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    .data-table th, .data-table td { padding: 12px; text-align: left; border-bottom: 1px solid var(--mac-border); }
    .data-table th { background-color: rgba(0,0,0,0.02); font-weight: 600; font-size: 13px; }
    
    .status-badge { padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: bold; }
    .status-1 { background: #e8f5e9; color: #2e7d32; } /* Active */
    .status-0 { background: #ffebee; color: #c62828; } /* Inactive */
    
    .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 2000; align-items: center; justify-content: center; }
    .modal-content { background: var(--mac-bg); backdrop-filter: blur(20px); padding: 30px; border-radius: 12px; width: 500px; border: 1px solid var(--mac-border); }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; margin-bottom: 5px; font-size: 13px; font-weight: 500; }
    .form-control { width: 100%; padding: 8px 12px; border: 1px solid var(--mac-border); border-radius: 4px; background: transparent; color: var(--text-main); box-sizing: border-box;}
</style>

<div class="card">
    <div class="header-actions">
        <div>
            <h2 style="margin: 0 0 5px 0;">Tax Rates Configuration</h2>
            <p style="margin: 0; color: #666; font-size: 14px;">Manage VAT, GST, and Sales Tax profiles.</p>
        </div>
        <button class="btn" onclick="document.getElementById('taxModal').style.display='flex'">+ Add Tax Rate</button>
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
                <th>Tax Name</th>
                <th style="text-align: right;">Rate (%)</th>
                <th>Accounting Ledger (Liability)</th>
                <th style="text-align: center;">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if(empty($data['tax_rates'])): ?>
            <tr><td colspan="4" style="text-align: center; color: #888;">No tax rates configured.</td></tr>
            <?php else: foreach($data['tax_rates'] as $tax): ?>
            <tr>
                <td><strong><?= htmlspecialchars($tax->tax_name) ?></strong></td>
                <td style="text-align: right; font-weight:bold;"><?= number_format($tax->rate_percentage, 2) ?>%</td>
                <td><span style="font-size: 12px; color:#666;"><?= htmlspecialchars($tax->account_code) ?> - </span><?= htmlspecialchars($tax->account_name) ?></td>
                <td style="text-align: center;">
                    <form action="<?= APP_URL ?>/tax" method="POST" style="display:inline;">
                        <input type="hidden" name="action" value="toggle_status">
                        <input type="hidden" name="tax_id" value="<?= $tax->id ?>">
                        <select name="status" onchange="this.form.submit()" class="status-badge status-<?= $tax->is_active ?>" style="border:none; outline:none; cursor:pointer;">
                            <option value="1" <?= $tax->is_active ? 'selected' : '' ?>>Active</option>
                            <option value="0" <?= !$tax->is_active ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </form>
                </td>
            </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<!-- Add Tax Rate Modal -->
<div class="modal" id="taxModal">
    <div class="modal-content">
        <h3 style="margin-top:0;">Create Tax Rate</h3>
        <form action="<?= APP_URL ?>/tax" method="POST">
            <input type="hidden" name="action" value="add_tax">
            
            <div class="form-group">
                <label>Tax Name (e.g., "VAT 15%" or "State Tax") *</label>
                <input type="text" name="tax_name" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label>Tax Rate Percentage (%) *</label>
                <input type="number" name="rate_percentage" step="0.01" min="0" class="form-control" placeholder="15.00" required>
            </div>

            <div class="form-group" style="background: rgba(0,102,204,0.05); padding: 15px; border-radius: 8px; border: 1px solid rgba(0,102,204,0.2);">
                <label style="color:#0066cc;">Liability Account (Where collected tax is held) *</label>
                <p style="font-size: 11px; color:#666; margin-top:0;">Tax collected is owed to the government. Ensure you have a Liability account like "VAT Payable" created in your Chart of Accounts.</p>
                <select name="liability_account_id" class="form-control" required>
                    <option value="">Select Liability Account...</option>
                    <?php foreach($data['liabilities'] as $acc): ?>
                        <option value="<?= $acc->id ?>"><?= $acc->account_code ?> - <?= htmlspecialchars($acc->account_name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('taxModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn">Save Tax Rate</button>
            </div>
        </form>
    </div>
</div>