<?php
?>
<style>
    .header-actions { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .btn { padding: 8px 16px; background: #0066cc; color: #fff; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; font-size: 14px;}
    .btn:hover { background: #005bb5; }
    .btn-outline { background: transparent; border: 1px solid #0066cc; color: #0066cc; }
    .btn-success { background: #2e7d32; color: white; border: none; }
    
    .data-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    .data-table th, .data-table td { padding: 12px; text-align: left; border-bottom: 1px solid var(--mac-border); }
    .data-table th { background-color: rgba(0,0,0,0.02); font-weight: 600; font-size: 13px; }
    
    .status-badge { padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: bold; }
    .status-Draft { background: #f5f5f5; color: #666; }
    .status-Sent { background: #e3f2fd; color: #1565c0; }
    .status-Accepted { background: #e8f5e9; color: #2e7d32; }
    .status-Declined { background: #ffebee; color: #c62828; }
    .status-Invoiced { background: #f3e5f5; color: #6a1b9a; }
    
    .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 2000; align-items: center; justify-content: center; }
    .modal-content { background: var(--mac-bg); backdrop-filter: blur(20px); padding: 30px; border-radius: 12px; width: 500px; border: 1px solid var(--mac-border); }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; margin-bottom: 5px; font-size: 13px; font-weight: 500; }
    .form-control { width: 100%; padding: 8px 12px; border: 1px solid var(--mac-border); border-radius: 4px; background: transparent; color: var(--text-main); box-sizing: border-box;}
</style>

<div class="card">
    <div class="header-actions">
        <h2>Estimates & Quotes</h2>
        <a href="<?= APP_URL ?>/estimate/create" class="btn">+ Create Estimate</a>
    </div>

    <?php if(isset($_GET['success'])): ?>
        <div style="padding: 10px; background:#e8f5e9; color:#2e7d32; border-radius:4px; margin-bottom:15px;">Estimate generated successfully!</div>
    <?php endif; ?>
    <?php if(!empty($data['success'])): ?>
        <div style="padding: 10px; background:#e8f5e9; color:#2e7d32; border-radius:4px; margin-bottom:15px;"><?= $data['success'] ?></div>
    <?php endif; ?>
    <?php if(!empty($data['error'])): ?>
        <div style="padding: 10px; background:#ffebee; color:#c62828; border-radius:4px; margin-bottom:15px;"><?= $data['error'] ?></div>
    <?php endif; ?>

    <table class="data-table">
        <thead>
            <tr>
                <th>Estimate #</th>
                <th>Date</th>
                <th>Customer</th>
                <th>Status</th>
                <th style="text-align: right;">Amount (Rs:)</th>
                <th style="text-align: center;">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if(empty($data['estimates'])): ?>
            <tr><td colspan="6" style="text-align: center; color: #888;">No estimates found.</td></tr>
            <?php else: foreach($data['estimates'] as $est): ?>
            <tr>
                <td><strong><?= htmlspecialchars($est->estimate_number) ?></strong></td>
                <td><?= date('M d, Y', strtotime($est->estimate_date)) ?></td>
                <td><?= htmlspecialchars($est->customer_name) ?></td>
                <td>
                    <form action="<?= APP_URL ?>/estimate" method="POST" style="display:inline;">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="estimate_id" value="<?= $est->id ?>">
                        <select name="new_status" onchange="this.form.submit()" class="status-badge status-<?= $est->status ?>" style="border:none; outline:none; cursor:pointer;" <?= $est->status == 'Invoiced' ? 'disabled' : '' ?>>
                            <option value="Draft" <?= $est->status == 'Draft' ? 'selected' : '' ?>>Draft</option>
                            <option value="Sent" <?= $est->status == 'Sent' ? 'selected' : '' ?>>Sent</option>
                            <option value="Accepted" <?= $est->status == 'Accepted' ? 'selected' : '' ?>>Accepted</option>
                            <option value="Declined" <?= $est->status == 'Declined' ? 'selected' : '' ?>>Declined</option>
                            <?php if($est->status == 'Invoiced'): ?><option value="Invoiced" selected>Invoiced</option><?php endif; ?>
                        </select>
                    </form>
                </td>
                <td style="text-align: right; font-weight:bold;"><?= number_format($est->total_amount, 2) ?></td>
                <td style="text-align: center;">
                    <a href="<?= APP_URL ?>/estimate/show/<?= $est->id ?>" class="btn btn-outline" style="padding: 4px 8px; font-size: 11px;">View</a>
                    <?php if($est->status !== 'Invoiced'): ?>
                        <button onclick="openConvertModal(<?= $est->id ?>, '<?= htmlspecialchars($est->estimate_number) ?>')" class="btn btn-success" style="padding: 4px 8px; font-size: 11px; margin-left: 5px;">Convert to Invoice</button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<!-- Convert to Invoice Modal -->
<div class="modal" id="convertModal">
    <div class="modal-content">
        <h3 style="margin-top:0; color:#2e7d32;">Convert to Invoice & Post Ledger</h3>
        <p>You are about to turn Estimate <strong id="modalEstNum"></strong> into a real invoice. Please select the accounting routing details.</p>
        <form action="<?= APP_URL ?>/estimate" method="POST">
            <input type="hidden" name="action" value="convert_to_invoice">
            <input type="hidden" name="estimate_id" id="modalEstId">
            
            <div class="form-group">
                <label>Debit Account (Accounts Receivable)</label>
                <select name="ar_account" class="form-control" required>
                    <?php foreach($data['assets'] as $acc): ?>
                        <option value="<?= $acc->id ?>" <?= strpos(strtolower($acc->account_name), 'receivable') !== false ? 'selected' : '' ?>><?= $acc->account_code ?> - <?= htmlspecialchars($acc->account_name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Credit Account (Sales/Income)</label>
                <select name="revenue_account" class="form-control" required>
                    <?php foreach($data['revenues'] as $acc): ?>
                        <option value="<?= $acc->id ?>"><?= $acc->account_code ?> - <?= htmlspecialchars($acc->account_name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('convertModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-success">Generate Invoice</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openConvertModal(id, estNum) {
        document.getElementById('convertModal').style.display = 'flex';
        document.getElementById('modalEstId').value = id;
        document.getElementById('modalEstNum').innerText = estNum;
    }
</script>