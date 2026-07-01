<?php
?>
<style>
    .split-layout { display: flex; height: 75vh; background: #fff; border-radius: 8px; border: 1px solid var(--mac-border); overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.05);}
    @media (prefers-color-scheme: dark) { .split-layout { background: #1e1e2d; } }
    
    .left-pane { width: 300px; border-right: 1px solid var(--mac-border); display: flex; flex-direction: column; background: rgba(0,0,0,0.02); }
    .pane-header { padding: 15px; border-bottom: 1px solid var(--mac-border); background: var(--mac-bg); font-weight: bold; display: flex; justify-content: space-between; align-items: center;}
    .vendor-list { flex: 1; overflow-y: auto; }
    .vendor-item { padding: 15px; border-bottom: 1px solid var(--mac-border); cursor: pointer; text-decoration: none; color: var(--text-main); display: block; transition: 0.2s;}
    .vendor-item:hover { background: rgba(0,102,204,0.05); }
    .vendor-item.active { background: #0066cc; color: #fff; border-color: #0066cc; }
    .vendor-item.active .text-sub { color: rgba(255,255,255,0.7); }
    .text-sub { font-size: 11px; color: #888; display: block; margin-top: 4px; }
    
    .right-pane { flex: 1; display: flex; flex-direction: column; overflow: hidden; }
    .right-header { padding: 20px; border-bottom: 1px solid var(--mac-border); display: flex; justify-content: space-between; align-items: center;}
    
    .tabs { display: flex; border-bottom: 1px solid var(--mac-border); background: rgba(0,0,0,0.02); padding: 0 20px;}
    .tab-btn { padding: 12px 20px; border: none; background: transparent; cursor: pointer; font-size: 13px; font-weight: 600; color: #666; border-bottom: 3px solid transparent;}
    .tab-btn:hover { color: #0066cc; }
    .tab-btn.active { color: #0066cc; border-bottom-color: #0066cc; }
    
    .tab-content { flex: 1; padding: 20px; overflow-y: auto; display: none; }
    .tab-content.active { display: block; }
    
    .data-table { width: 100%; border-collapse: collapse; }
    .data-table th, .data-table td { padding: 10px; text-align: left; border-bottom: 1px solid var(--mac-border); font-size: 13px;}
    .data-table th { background: rgba(0,0,0,0.02); color: #888; }
    
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; margin-bottom: 5px; font-size: 13px; font-weight: 600; }
    .form-control { width: 100%; padding: 8px 12px; border: 1px solid var(--mac-border); border-radius: 4px; background: transparent; color: var(--text-main); box-sizing: border-box;}
    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    
    .btn { padding: 8px 16px; background: #0066cc; color: #fff; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; font-size: 13px;}
    .btn-small { padding: 4px 8px; font-size: 11px; }
    .status-badge { padding: 3px 6px; border-radius: 4px; font-size: 11px; font-weight: bold; }
    
    .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 2000; align-items: center; justify-content: center; }
    .modal-content { background: var(--mac-bg); backdrop-filter: blur(20px); padding: 30px; border-radius: 12px; width: 400px; border: 1px solid var(--mac-border); }
</style>

<?php if(!empty($data['error'])): ?>
    <div style="padding: 10px; background:#ffebee; color:#c62828; border-radius:4px; margin-bottom:15px;"><?= $data['error'] ?></div>
<?php endif; ?>
<?php if(!empty($data['success'])): ?>
    <div style="padding: 10px; background:#e8f5e9; color:#2e7d32; border-radius:4px; margin-bottom:15px;"><?= $data['success'] ?></div>
<?php endif; ?>

<div class="split-layout">
    <!-- Left Pane: List -->
    <div class="left-pane">
        <div class="pane-header">
            All Suppliers
            <button class="btn btn-small" onclick="document.getElementById('vendorModal').style.display='flex'">+ Add</button>
        </div>
        <div class="vendor-list">
            <?php if(empty($data['vendors'])): ?>
                <div style="padding: 20px; text-align: center; color: #888; font-size: 13px;">No vendors found.</div>
            <?php else: foreach($data['vendors'] as $v): ?>
                <a href="<?= APP_URL ?>/vendor/index/<?= $v->id ?>" class="vendor-item <?= ($data['selected_vendor'] && $data['selected_vendor']->id == $v->id) ? 'active' : '' ?>">
                    <strong><?= htmlspecialchars($v->name) ?></strong>
                    <span class="text-sub"><?= htmlspecialchars($v->email ?: $v->phone ?: 'No contact info') ?></span>
                </a>
            <?php endforeach; endif; ?>
        </div>
    </div>

    <!-- Right Pane: Details -->
    <div class="right-pane">
        <?php if(!$data['selected_vendor']): ?>
            <div style="flex:1; display:flex; align-items:center; justify-content:center; color:#888;">
                <p>Select a supplier from the left to view details.</p>
            </div>
        <?php else: ?>
            <div class="right-header">
                <div>
                    <h2 style="margin: 0 0 5px 0;"><?= htmlspecialchars($data['selected_vendor']->name) ?></h2>
                    <span style="font-size: 12px; color: #888;">Vendor ID: <?= $data['selected_vendor']->id ?> | Added: <?= date('M Y', strtotime($data['selected_vendor']->created_at)) ?></span>
                </div>
            </div>

            <!-- Tabs -->
            <div class="tabs">
                <button class="tab-btn active" onclick="switchTab('profile')" id="btn_profile">Supplier Details</button>
                <button class="tab-btn" onclick="switchTab('expenses')" id="btn_expenses">Past Expenses</button>
                <button class="tab-btn" onclick="switchTab('pos')" id="btn_pos">Purchase Orders</button>
            </div>

            <!-- Tab Content: Profile Details -->
            <div class="tab-content active" id="tab_profile">
                <form action="<?= APP_URL ?>/vendor/index/<?= $data['selected_vendor']->id ?>" method="POST">
                    <input type="hidden" name="action" value="update_vendor">
                    <input type="hidden" name="vendor_id" value="<?= $data['selected_vendor']->id ?>">
                    
                    <div class="grid-2">
                        <div class="form-group"><label>Supplier / Company Name</label><input type="text" name="name" class="form-control" value="<?= htmlspecialchars($data['selected_vendor']->name) ?>" required></div>
                        <div class="form-group"><label>Email Address</label><input type="email" name="email" class="form-control" value="<?= htmlspecialchars($data['selected_vendor']->email) ?>"></div>
                        <div class="form-group"><label>Phone Number</label><input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($data['selected_vendor']->phone) ?>"></div>
                        <div class="form-group"><label>Physical Address</label><textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($data['selected_vendor']->address) ?></textarea></div>
                    </div>
                    
                    <div style="margin-top: 20px; text-align: right;">
                        <button type="submit" class="btn">Save Changes</button>
                    </div>
                </form>
            </div>

            <!-- Tab Content: Expenses -->
            <div class="tab-content" id="tab_expenses">
                <table class="data-table">
                    <thead><tr><th>Date</th><th>Reference</th><th>Description</th><th style="text-align:right;">Amount (Rs:)</th></tr></thead>
                    <tbody>
                        <?php if(empty($data['expenses'])): ?><tr><td colspan="4" style="text-align:center; color:#888;">No recorded expenses.</td></tr><?php endif; ?>
                        <?php foreach($data['expenses'] as $exp): ?>
                            <tr>
                                <td><?= date('M d, Y', strtotime($exp->expense_date)) ?></td>
                                <td><strong><?= htmlspecialchars($exp->reference) ?></strong></td>
                                <td><?= htmlspecialchars($exp->description) ?></td>
                                <td style="text-align:right; font-weight:bold;"><?= number_format($exp->amount, 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Tab Content: Purchase Orders -->
            <div class="tab-content" id="tab_pos">
                <table class="data-table">
                    <thead><tr><th>Date</th><th>PO Number</th><th>Status</th><th style="text-align:right;">Amount (Rs:)</th><th></th></tr></thead>
                    <tbody>
                        <?php if(empty($data['pos'])): ?><tr><td colspan="5" style="text-align:center; color:#888;">No purchase orders issued.</td></tr><?php endif; ?>
                        <?php foreach($data['pos'] as $po): ?>
                            <tr>
                                <td><?= date('M d, Y', strtotime($po->po_date)) ?></td>
                                <td><strong><?= $po->po_number ?></strong></td>
                                <td><span class="status-badge" style="background:#f5f5f5; color:#555;"><?= $po->status ?></span></td>
                                <td style="text-align:right; font-weight:bold;"><?= number_format($po->total_amount, 2) ?></td>
                                <td style="text-align:right;"><a href="<?= APP_URL ?>/purchase/show/<?= $po->id ?>" target="_blank" style="color:#0066cc; font-size:12px;">View</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php endif; ?>
    </div>
</div>

<!-- Add Vendor Modal -->
<div class="modal" id="vendorModal">
    <div class="modal-content">
        <h3 style="margin-top:0;">Add New Supplier</h3>
        <form action="<?= APP_URL ?>/vendor" method="POST">
            <input type="hidden" name="action" value="add_vendor">
            
            <label style="font-size: 13px; font-weight:600;">Supplier / Company Name *</label>
            <input type="text" name="name" class="form-control" required>
            
            <label style="font-size: 13px; font-weight:600;">Email</label>
            <input type="email" name="email" class="form-control">
            
            <label style="font-size: 13px; font-weight:600;">Phone</label>
            <input type="text" name="phone" class="form-control">
            
            <label style="font-size: 13px; font-weight:600;">Address</label>
            <textarea name="address" class="form-control" rows="3"></textarea>
            
            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 10px;">
                <button type="button" class="btn" style="background:transparent; border:1px solid #ccc; color:#333;" onclick="document.getElementById('vendorModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn">Save Supplier</button>
            </div>
        </form>
    </div>
</div>

<script>
    function switchTab(tabName) {
        document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
        document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
        document.getElementById('tab_' + tabName).classList.add('active');
        document.getElementById('btn_' + tabName).classList.add('active');
    }
</script>