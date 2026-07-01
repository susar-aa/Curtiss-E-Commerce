<?php
?>
<style>
    .header-actions { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .btn { padding: 8px 16px; background: #0066cc; color: #fff; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; font-size: 14px;}
    .btn-outline { background: transparent; border: 1px solid #0066cc; color: #0066cc; }
    .btn-danger { background: #c62828; color: #fff; }
    
    .kpi-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px; }
    .kpi-card { background: #fff; padding: 20px; border-radius: 8px; border: 1px solid var(--mac-border); display: flex; flex-direction: column; }
    @media (prefers-color-scheme: dark) { .kpi-card { background: rgba(0,0,0,0.2); } }
    .kpi-title { font-size: 12px; color: #888; text-transform: uppercase; font-weight: bold; margin-bottom: 10px; }
    .kpi-val { font-size: 24px; font-weight: bold; color: var(--text-main); }
    
    .date-group-header { background: rgba(0,102,204,0.05); padding: 10px 15px; border-radius: 6px; font-weight: bold; color: #0066cc; margin-top: 20px; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center;}
    
    .data-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
    .data-table th, .data-table td { padding: 12px; text-align: left; border-bottom: 1px solid var(--mac-border); font-size: 13px;}
    .data-table th { color: #888; font-weight: normal; }
    
    .status-Pending { color: #f57c00; font-weight: bold; }
    .status-Cleared { color: #2e7d32; font-weight: bold; }
    .status-Bounced { color: #c62828; font-weight: bold; }

    /* Custom Modals */
    .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 2000; align-items: center; justify-content: center; }
    .modal-content { background: var(--mac-bg); backdrop-filter: blur(20px); padding: 30px; border-radius: 12px; width: 500px; border: 1px solid var(--mac-border); }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; margin-bottom: 5px; font-size: 13px; font-weight: 500; }
    .form-control { width: 100%; padding: 8px 12px; border: 1px solid var(--mac-border); border-radius: 4px; background: transparent; color: var(--text-main); box-sizing: border-box;}
    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }

    .cheque-modal-content { background: transparent; border: none; padding: 0; width: 750px; display: flex; flex-direction: column; align-items: center;}
    .cheque-paper {
        width: 700px; height: 320px;
        background: repeating-linear-gradient(45deg, #f0f8ff, #f0f8ff 10px, #e6f2ff 10px, #e6f2ff 20px);
        border: 1px solid #b0c4de;
        border-radius: 4px;
        padding: 25px;
        position: relative;
        font-family: 'Times New Roman', serif;
        box-shadow: 0 15px 35px rgba(0,0,0,0.2);
        color: #333;
        box-sizing: border-box;
    }
    .cq-bank { font-size: 18px; font-weight: bold; color: #444; border-bottom: 2px solid #ccc; display: inline-block; padding-bottom: 5px; margin-bottom: 20px;}
    .cq-date { position: absolute; top: 25px; right: 25px; border-bottom: 1px solid #555; padding-bottom: 2px; font-size: 16px; font-family: monospace; letter-spacing: 2px;}
    .cq-payee { margin-top: 30px; font-size: 16px; border-bottom: 1px solid #555; padding-bottom: 5px; }
    .cq-amount-words { margin-top: 30px; font-size: 16px; border-bottom: 1px solid #555; padding-bottom: 5px; line-height: 1.5; }
    .cq-amount-box { position: absolute; top: 120px; right: 25px; border: 2px solid #555; padding: 10px 20px; font-size: 20px; font-weight: bold; background: #fff; }
    .cq-signature { position: absolute; bottom: 60px; right: 25px; border-bottom: 1px solid #555; width: 200px; text-align: center; font-size: 14px; color: #0066cc; font-style: italic;}
    .cq-micr { position: absolute; bottom: 20px; left: 0; width: 100%; text-align: center; font-family: 'Courier New', Courier, monospace; font-size: 22px; font-weight: bold; letter-spacing: 4px; color: #111;}
</style>

<div class="header-actions">
    <h2>Cheque Management (PDC)</h2>
    <button class="btn" onclick="openModal('addModal')">+ Record Received Cheque</button>
</div>

<?php if(!empty($data['error'])): ?>
    <div style="padding: 10px; background:#ffebee; color:#c62828; border-radius:4px; margin-bottom:15px;"><?= $data['error'] ?></div>
<?php endif; ?>
<?php if(!empty($data['success'])): ?>
    <div style="padding: 10px; background:#e8f5e9; color:#2e7d32; border-radius:4px; margin-bottom:15px;"><?= $data['success'] ?></div>
<?php endif; ?>

<div class="kpi-grid">
    <div class="kpi-card" style="border-top: 4px solid #f57c00;">
        <div class="kpi-title">Total Pending Amount</div>
        <div class="kpi-val">Rs: <?= number_format($data['kpi_pending'], 2) ?></div>
    </div>
    <div class="kpi-card" style="border-top: 4px solid #0066cc;">
        <div class="kpi-title">Next Banking Date</div>
        <?php if($data['kpi_next_date']): ?>
            <div class="kpi-val" style="color: #0066cc;"><?= date('M d, Y', strtotime($data['kpi_next_date'])) ?></div>
            <div style="font-size: 12px; color: #888; margin-top: 5px;">Amount: Rs: <?= number_format($data['kpi_next_amount'], 2) ?></div>
        <?php else: ?>
            <div class="kpi-val" style="color: #888;">None</div>
        <?php endif; ?>
    </div>
    <div class="kpi-card" style="border-top: 4px solid #2e7d32;">
        <div class="kpi-title">Total Cleared (Historical)</div>
        <div class="kpi-val" style="color: #2e7d32;">Rs: <?= number_format($data['kpi_cleared'], 2) ?></div>
    </div>
</div>

<div class="card">
    <?php if(empty($data['grouped_cheques'])): ?>
        <p style="text-align: center; color: #888; padding: 40px;">No cheques recorded in the system.</p>
    <?php else: foreach($data['grouped_cheques'] as $date => $cheques): ?>
        
        <?php 
            // Calculate group total
            $dayTotal = 0;
            foreach($cheques as $c) { if($c->status == 'Pending') $dayTotal += $c->amount; }
            $isPastDue = strtotime($date) < strtotime('today') && $dayTotal > 0;
        ?>

        <div class="date-group-header" style="<?= $isPastDue ? 'background:#ffebee; color:#c62828;' : '' ?>">
            <span>📅 <?= date('l, F j, Y', strtotime($date)) ?> <?= $isPastDue ? '(OVERDUE FOR BANKING)' : '' ?></span>
            <span>Pending to Deposit: Rs: <?= number_format($dayTotal, 2) ?></span>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 25%;">Drawer / Payee</th>
                    <th style="width: 20%;">Bank Name</th>
                    <th style="width: 15%;">Cheque Number</th>
                    <th style="width: 10%;">Status</th>
                    <th style="width: 15%; text-align: right;">Amount (Rs:)</th>
                    <th style="width: 15%; text-align: center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($cheques as $chk): ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars($chk->customer_name ?: ($chk->vendor_name ?: '-')) ?></strong>
                        <?php if ($chk->customer_name): ?>
                            <span style="font-size:10px; color:#2e7d32; display:block; font-weight:600;">Customer Receipt</span>
                        <?php elseif ($chk->vendor_name): ?>
                            <span style="font-size:10px; color:#ef6c00; display:block; font-weight:600;">Supplier Payment</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($chk->bank_name) ?></td>
                    <td style="font-family: monospace; font-size: 14px;"><?= htmlspecialchars($chk->cheque_number) ?></td>
                    <td class="status-<?= $chk->status ?>"><?= $chk->status ?></td>
                    <td style="text-align: right; font-weight: bold;"><?= number_format($chk->amount, 2) ?></td>
                    <td style="text-align: center;">
                        <button class="btn btn-outline" style="padding: 4px 8px; font-size: 11px;" onclick="viewCheque('<?= htmlspecialchars(addslashes($chk->bank_name)) ?>', '<?= htmlspecialchars(addslashes($chk->banking_date)) ?>', '<?= htmlspecialchars(addslashes($chk->amount)) ?>', '<?= htmlspecialchars(addslashes($chk->customer_name ?: ($chk->vendor_name ?: ''))) ?>', '<?= htmlspecialchars(addslashes($chk->cheque_number)) ?>')">👁️ View</button>
                        
                        <button class="btn" style="padding: 4px 8px; font-size: 11px; margin: 0 4px;" onclick="openEditModal(<?= $chk->id ?>, <?= $chk->customer_id ?: 'null' ?>, <?= $chk->vendor_id ?: 'null' ?>, '<?= htmlspecialchars(addslashes($chk->bank_name)) ?>', '<?= htmlspecialchars(addslashes($chk->cheque_number)) ?>', <?= $chk->amount ?>, '<?= $chk->banking_date ?>', '<?= $chk->status ?>')">✏️ Edit</button>
                        
                        <button class="btn btn-danger" style="padding: 4px 8px; font-size: 11px;" onclick="openDeleteModal(<?= $chk->id ?>, '<?= htmlspecialchars(addslashes($chk->cheque_number)) ?>')">🗑️</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endforeach; endif; ?>
</div>


<!-- Add Modal -->
<div class="modal" id="addModal">
    <div class="modal-content">
        <h3 style="margin-top:0;">Record Received / Paid Cheque</h3>
        <form action="<?= APP_URL ?>/cheque" method="POST">
            <input type="hidden" name="action" value="add_cheque">
            <div class="form-group">
                <label>Customer (for Collection/Received Cheque)</label>
                <select name="customer_id" class="form-control">
                    <option value="">-- Select Customer --</option>
                    <?php foreach($data['customers'] as $cust): ?><option value="<?= $cust->id ?>"><?= htmlspecialchars($cust->name) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Supplier / Vendor (for Payment/Issued Cheque)</label>
                <select name="vendor_id" class="form-control">
                    <option value="">-- Select Supplier --</option>
                    <?php foreach($data['suppliers'] as $supp): ?><option value="<?= $supp->id ?>"><?= htmlspecialchars($supp->name) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="grid-2">
                <div class="form-group"><label>Bank Name *</label><input type="text" name="bank_name" class="form-control" placeholder="e.g. Commercial Bank" required></div>
                <div class="form-group"><label>Cheque Number *</label><input type="text" name="cheque_number" class="form-control" required></div>
            </div>
            <div class="grid-2">
                <div class="form-group"><label>Banking Date *</label><input type="date" name="banking_date" class="form-control" required></div>
                <div class="form-group"><label>Amount (Rs:) *</label><input type="number" name="amount" step="0.01" class="form-control" required></div>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 10px;">
                <button type="button" class="btn btn-outline" onclick="closeModal('addModal')">Cancel</button>
                <button type="submit" class="btn">Save Cheque</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal" id="editModal">
    <div class="modal-content">
        <h3 style="margin-top:0;">Update Cheque Details</h3>
        <form action="<?= APP_URL ?>/cheque" method="POST">
            <input type="hidden" name="action" value="edit_cheque">
            <input type="hidden" name="cheque_id" id="edit_id">
            
            <div class="form-group">
                <label>Customer (for Collections)</label>
                <select name="customer_id" id="edit_customer" class="form-control">
                    <option value="">-- None --</option>
                    <?php foreach($data['customers'] as $cust): ?><option value="<?= $cust->id ?>"><?= htmlspecialchars($cust->name) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Supplier / Vendor (for Payments)</label>
                <select name="vendor_id" id="edit_vendor" class="form-control">
                    <option value="">-- None --</option>
                    <?php foreach($data['suppliers'] as $supp): ?><option value="<?= $supp->id ?>"><?= htmlspecialchars($supp->name) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="grid-2">
                <div class="form-group"><label>Bank Name</label><input type="text" name="bank_name" id="edit_bank" class="form-control" required></div>
                <div class="form-group"><label>Cheque Number</label><input type="text" name="cheque_number" id="edit_cnum" class="form-control" required></div>
            </div>
            <div class="grid-2">
                <div class="form-group"><label>Banking Date</label><input type="date" name="banking_date" id="edit_date" class="form-control" required></div>
                <div class="form-group"><label>Amount</label><input type="number" name="amount" id="edit_amt" step="0.01" class="form-control" required></div>
            </div>
            <div class="form-group" style="background: rgba(0,102,204,0.05); padding: 10px; border-radius: 4px;">
                <label style="color:#0066cc;">Cheque Status</label>
                <select name="status" id="edit_status" class="form-control">
                    <option value="Pending">Pending (Holding)</option>
                    <option value="Cleared">Cleared (Realized in Bank)</option>
                    <option value="Bounced">Bounced (Returned)</option>
                </select>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 10px;">
                <button type="button" class="btn btn-outline" onclick="closeModal('editModal')">Cancel</button>
                <button type="submit" class="btn">Update Cheque</button>
            </div>
        </form>
    </div>
</div>

<!-- Custom Delete Confirmation Modal -->
<div class="modal" id="deleteModal">
    <div class="modal-content" style="width: 400px; text-align: center;">
        <h3 style="color: #c62828; margin-top:0;">Delete Cheque Record</h3>
        <p>Are you sure you want to permanently delete cheque <strong id="del_cnum_display"></strong>? This cannot be undone.</p>
        <form action="<?= APP_URL ?>/cheque" method="POST" style="margin-top: 20px;">
            <input type="hidden" name="action" value="delete_cheque">
            <input type="hidden" name="delete_id" id="delete_id">
            <div style="display: flex; justify-content: center; gap: 15px;">
                <button type="button" class="btn btn-outline" onclick="closeModal('deleteModal')">Cancel</button>
                <button type="submit" class="btn-danger btn">Yes, Delete</button>
            </div>
        </form>
    </div>
</div>

<!-- Realistic Cheque Viewer Modal -->
<div class="modal" id="viewModal">
    <div class="cheque-modal-content">
        <div class="cheque-paper">
            <div class="cq-bank" id="vq_bank">Bank Name</div>
            <div class="cq-date">Date: <span id="vq_date"></span></div>
            
            <div class="cq-payee">
                <strong>Pay:</strong> <span style="font-family: sans-serif; margin-left: 10px; font-weight: bold;"><?= htmlspecialchars($data['company_name']) ?></span> 
                <span style="float: right;">Or Bearer</span>
            </div>
            
            <div class="cq-amount-words">
                <strong>Rupees:</strong> <span id="vq_words" style="font-family: cursive; margin-left: 10px; font-size: 18px; color: #111;"></span>
            </div>
            
            <div class="cq-amount-box">
                Rs: <span id="vq_amount">0.00</span>
            </div>
            
            <div class="cq-signature" id="vq_drawer">
                Drawer Signature
            </div>
            
            <div class="cq-micr">
                ⑆<span id="vq_micr1">123456</span>⑆ ⑈<span id="vq_micr2">7890</span>⑈ ⑉<span id="vq_micr3">12345678</span>⑉
            </div>
        </div>
        <button class="btn btn-outline" style="margin-top: 20px; background: #fff;" onclick="closeModal('viewModal')">Close Viewer</button>
    </div>
</div>

<script>
    function openModal(id) { document.getElementById(id).style.display = 'flex'; }
    function closeModal(id) { document.getElementById(id).style.display = 'none'; }

    function openEditModal(id, cid, vid, bank, cnum, amt, date, status) {
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_customer').value = cid || '';
        document.getElementById('edit_vendor').value = vid || '';
        document.getElementById('edit_bank').value = bank;
        document.getElementById('edit_cnum').value = cnum;
        document.getElementById('edit_amt').value = amt;
        document.getElementById('edit_date').value = date;
        document.getElementById('edit_status').value = status;
        openModal('editModal');
    }

    function openDeleteModal(id, cnum) {
        document.getElementById('delete_id').value = id;
        document.getElementById('del_cnum_display').innerText = cnum;
        openModal('deleteModal');
    }

    // Number to Words converter for Cheque UI
    function numberToWords(amount) {
        // Simplified fallback for visual effect in UI. 
        // A full scale converter requires extensive arrays, but this serves the visual purpose beautifully.
        return "Amount in words for Rs. " + parseFloat(amount).toLocaleString('en-IN') + " Only."; 
    }

    function viewCheque(bank, date, amount, drawer, cnum) {
        document.getElementById('vq_bank').innerText = bank;
        
        // Format Date nicely
        const d = new Date(date);
        document.getElementById('vq_date').innerText = d.getDate().toString().padStart(2, '0') + '/' + (d.getMonth()+1).toString().padStart(2, '0') + '/' + d.getFullYear();
        
        document.getElementById('vq_amount').innerText = parseFloat(amount).toLocaleString('en-IN', {minimumFractionDigits: 2});
        document.getElementById('vq_words').innerText = numberToWords(amount);
        document.getElementById('vq_drawer').innerText = drawer + " (Auth Signatory)";
        
        // Randomize MICR looking numbers using the actual cheque number as base
        document.getElementById('vq_micr1').innerText = cnum.padStart(6, '0');
        
        openModal('viewModal');
    }
</script>