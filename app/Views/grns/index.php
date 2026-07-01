<?php
?>
<style>
    .header-actions { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .btn { padding: 8px 16px; background: #0066cc; color: #fff; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; font-size: 14px;}
    .btn-outline { background: transparent; border: 1px solid #0066cc; color: #0066cc; }
    .btn-danger { background: #ffebee; color: #c62828; border: none; }
    .btn-small { padding: 4px 8px; font-size: 11px; margin-right: 5px; cursor: pointer; border-radius: 4px;}
    
    .quick-links { display: flex; gap: 10px; margin-bottom: 20px; align-items: center; background: rgba(0,0,0,0.02); padding: 10px 15px; border-radius: 8px; border: 1px solid var(--mac-border); }
    .btn-quick { padding: 6px 12px; background: #fff; color: #555; border: 1px solid var(--mac-border); border-radius: 6px; font-size: 12px; font-weight: 600; text-decoration: none; transition: 0.2s; }
    .btn-quick:hover { background: rgba(0,102,204,0.05); color: #0066cc; border-color: #0066cc; }
    .btn-quick.active { background: #0066cc; color: #fff; border-color: #0066cc; }
    
    .filter-panel { background: #fff; padding: 15px; border-radius: 8px; border: 1px solid var(--mac-border); margin-bottom: 15px; display: flex; gap: 15px; align-items: flex-end;}
    @media (prefers-color-scheme: dark) { .filter-panel { background: #1e1e2d; } }
    .search-bar { width: 100%; padding: 10px 15px; border: 1px solid var(--mac-border); border-radius: 8px; font-size: 14px; margin-bottom: 15px; background: transparent; color: var(--text-main); box-sizing: border-box; }
    
    .data-table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.05);}
    @media (prefers-color-scheme: dark) { .data-table { background: #1e1e2d; } }
    .data-table th, .data-table td { padding: 12px; text-align: left; border-bottom: 1px solid var(--mac-border); font-size: 13px;}
    .data-table th { background-color: rgba(0,0,0,0.02); font-weight: 600; color:#555;}
    
    .pagination { display: flex; justify-content: flex-end; gap: 5px; margin-top: 15px; }
    .page-btn { padding: 6px 12px; border: 1px solid var(--mac-border); border-radius: 4px; background: #fff; color: #333; text-decoration: none; font-size: 12px;}
    .page-btn.active { background: #0066cc; color: #fff; border-color: #0066cc; }
    
    .form-group { margin:0; }
    .form-group label { display: block; margin-bottom: 5px; font-size: 11px; font-weight: 600; color: #888; text-transform: uppercase; }
    .form-control { width: 100%; padding: 8px 12px; border: 1px solid var(--mac-border); border-radius: 4px; background: transparent; color: var(--text-main); box-sizing: border-box;}
</style>

<div class="header-actions">
    <h2>Goods Receipt Notes (GRN)</h2>
    <a href="<?= APP_URL ?>/grn/create" class="btn">+ Create Manual GRN</a>
</div>

<div class="quick-links">
    <span style="font-size: 11px; color: #888; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px;">Supply Chain:</span>
    <a href="<?= APP_URL ?>/supplier" class="btn-quick">🏢 Suppliers</a>
    <a href="<?= APP_URL ?>/purchase" class="btn-quick">🛒 Purchase Orders</a>
    <a href="<?= APP_URL ?>/grn" class="btn-quick active">📦 Goods Receipts (GRN)</a>
    <a href="<?= APP_URL ?>/inventory" class="btn-quick">🗄️ Inventory</a>
</div>

<?php if(!empty($data['error'])): ?><div style="padding: 10px; background:#ffebee; color:#c62828; border-radius:4px; margin-bottom:15px;"><?= $data['error'] ?></div><?php endif; ?>
<?php if(!empty($data['success'])): ?><div style="padding: 15px; background:#e8f5e9; color:#2e7d32; border-radius:4px; margin-bottom:15px; font-weight:bold; font-size: 14px;">✓ <?= $data['success'] ?></div><?php endif; ?>

<input type="text" id="searchInput" class="search-bar" placeholder="🔍 Search GRN Number, PO Number or Supplier Name..." value="<?= htmlspecialchars($data['search']) ?>">

<div class="filter-panel">
    <div class="form-group" style="flex: 1;">
        <label>Filter by Supplier</label>
        <select id="filterVendor" class="form-control" onchange="triggerSearch()">
            <option value="">All Suppliers</option>
            <?php foreach($data['vendors'] as $ven): ?>
                <option value="<?= $ven->id ?>" <?= ($data['filters']['vendor_id'] == $ven->id) ? 'selected' : '' ?>><?= htmlspecialchars($ven->name) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <button class="btn btn-outline" style="padding: 8px 12px; border-color:#ccc; color:#666;" onclick="clearFilters()">Clear Filters</button>
</div>

<div id="tableContainer">
    <table class="data-table">
        <thead>
            <tr>
                <th>GRN Number & Date</th>
                <th>Supplier / Vendor</th>
                <th>Linked PO</th>
                <th style="text-align: right; width: 15%;">Total Amount</th>
                <th style="text-align: center; width: 25%;">Actions</th>
            </tr>
        </thead>
        <tbody id="tableBody">
            <?php if(empty($data['grns'])): ?>
            <tr><td colspan="5" style="text-align: center; color: #888; padding: 20px;">No Goods Receipt Notes found.</td></tr>
            <?php else: foreach($data['grns'] as $grn): ?>
            <tr>
                <td>
                    <strong><?= htmlspecialchars($grn->grn_number) ?></strong><br>
                    <span style="font-size: 11px; color: #888;">Rcvd: <?= date('M d, Y', strtotime($grn->grn_date)) ?></span>
                    <?php if(!empty($grn->receipt_number)): ?>
                        <br><span style="font-size: 11px; color: #2e7d32;">Inv: <?= htmlspecialchars($grn->receipt_number) ?></span>
                    <?php endif; ?>
                    <br>
                    <?php if($grn->is_approved): ?>
                        <span class="badge" style="background:#e8f5e9; color:#2e7d32; padding:3px 6px; border-radius:4px; font-size:10px; font-weight:bold; display:inline-block; margin-top:5px;">✓ Approved</span>
                        <?php if($grn->approver_name): ?>
                            <br><span style="font-size: 9px; color:#888;">Approved by: <?= htmlspecialchars($grn->approver_name) ?></span>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="badge" style="background:#fff3e0; color:#ef6c00; padding:3px 6px; border-radius:4px; font-size:10px; font-weight:bold; display:inline-block; margin-top:5px;">⏳ Pending Approval</span>
                    <?php endif; ?>
                </td>
                <td>
                    <span style="color:#0066cc; font-weight:bold;"><?= htmlspecialchars($grn->vendor_name) ?></span><br>
                    <span style="font-size: 10px; color:#888;">Created by: <?= htmlspecialchars($grn->creator_name) ?></span>
                </td>
                <td>
                    <?php if($grn->po_number): ?>
                        <span style="background: rgba(0,0,0,0.05); padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight:bold;">🔗 <?= htmlspecialchars($grn->po_number) ?></span>
                    <?php else: ?>
                        <span style="color: #aaa; font-style: italic; font-size: 11px;">Manual GRN</span>
                    <?php endif; ?>
                </td>
                <td style="text-align: right; font-weight: bold; color: var(--text-main);">
                    Rs: <?= number_format($grn->total_amount, 2) ?>
                </td>
                <td style="text-align: center;">
                    <a href="<?= APP_URL ?>/grn/show/<?= $grn->id ?>" class="btn btn-small btn-outline" target="_blank">Print</a>
                    <?php if(!$grn->is_approved): ?>
                        <a href="<?= APP_URL ?>/grn/edit/<?= $grn->id ?>" class="btn btn-small btn-outline" style="border-color: #2e7d32; color: #2e7d32;">Edit</a>
                        <form action="<?= APP_URL ?>/grn" method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="delete_grn">
                            <input type="hidden" name="grn_id" value="<?= $grn->id ?>">
                            <button type="submit" class="btn btn-small btn-danger" onclick="return confirm('Delete this pending GRN?');">Delete</button>
                        </form>
                        <?php if(strtolower($_SESSION['role'] ?? '') === 'admin'): ?>
                            <button class="btn btn-small" style="background:#2e7d32; color:#fff; font-weight:bold; margin-left: 5px; border:none; cursor:pointer;" onclick="showApprovalModal(<?= $grn->id ?>, '<?= htmlspecialchars($grn->grn_number) ?>')">Approve</button>
                        <?php endif; ?>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<script>
    let searchTimeout = null;
    document.getElementById('searchInput').addEventListener('input', triggerSearchDelay);
    function triggerSearchDelay() { clearTimeout(searchTimeout); searchTimeout = setTimeout(triggerSearch, 400); }

    function triggerSearch() {
        const query = encodeURIComponent(document.getElementById('searchInput').value);
        const venId = encodeURIComponent(document.getElementById('filterVendor').value);
        const url = `?search=${query}&vendor_id=${venId}&page=1`;
        
        fetch(url).then(response => response.text()).then(html => {
            const parser = new DOMParser(); const doc = parser.parseFromString(html, 'text/html');
            document.getElementById('tableBody').innerHTML = doc.getElementById('tableBody').innerHTML;
            window.history.pushState({}, '', url);
        });
    }

    function clearFilters() {
        document.getElementById('searchInput').value = '';
        document.getElementById('filterVendor').value = '';
        triggerSearch();
    }
</script>

<!-- Approval Password Modal -->
<div id="approvalModal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.6); align-items:center; justify-content:center; backdrop-filter: blur(2px);">
    <div style="background:#fff; padding:25px; border-radius:12px; width:100%; max-width:400px; box-shadow:0 8px 30px rgba(0,0,0,0.3); box-sizing:border-box; border-top: 5px solid #2e7d32; animation: fadeIn 0.2s ease-out;">
        <h3 style="margin-top:0; color:#333; font-size: 18px;" id="approvalTitle">Approve GRN</h3>
        <p style="font-size:13px; color:#666; margin-bottom:20px; line-height: 1.4;">For security verification, please enter your administrator password to authorize this Goods Receipt Note and update inventory levels.</p>
        
        <form id="approvalForm" method="POST" action="">
            <input type="hidden" name="action" value="approve_grn">
            <div style="margin-bottom:20px;">
                <label style="display:block; margin-bottom:8px; font-size:11px; font-weight:600; color:#888; text-transform: uppercase;">Admin Password</label>
                <input type="password" name="password" id="approvalPassword" class="form-control" style="width:100%; padding:10px 12px; border:1px solid var(--mac-border); border-radius:6px; box-sizing:border-box; font-size: 14px;" required placeholder="••••••••">
            </div>
            <div style="display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" class="btn btn-outline" style="border-color:#ccc; color:#666; padding:8px 16px; border-radius: 6px; font-weight: 500;" onclick="closeApprovalModal()">Cancel</button>
                <button type="submit" class="btn" style="background:#2e7d32; color:#fff; padding:8px 16px; border-radius: 6px; font-weight: 600;">Confirm & Approve</button>
            </div>
        </form>
    </div>
</div>

<script>
    function showApprovalModal(grnId, grnNumber) {
        const modal = document.getElementById('approvalModal');
        const form = document.getElementById('approvalForm');
        const title = document.getElementById('approvalTitle');
        const passwordInput = document.getElementById('approvalPassword');
        
        title.innerText = `Approve GRN: ${grnNumber}`;
        form.action = `<?= APP_URL ?>/grn/approve/${grnId}`;
        passwordInput.value = '';
        modal.style.display = 'flex';
        setTimeout(() => passwordInput.focus(), 50);
    }

    function closeApprovalModal() {
        document.getElementById('approvalModal').style.display = 'none';
    }
</script>

<style>
    @keyframes fadeIn {
        from { opacity: 0; transform: scale(0.95); }
        to { opacity: 1; transform: scale(1); }
    }
</style>