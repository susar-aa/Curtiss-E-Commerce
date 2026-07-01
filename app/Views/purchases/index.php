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
    
    .status-badge { padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: bold; }
    .status-Draft { background: #f5f5f5; color: #666; }
    .status-Sent { background: #fff3e0; color: #ef6c00; }
    .status-Received { background: #e8f5e9; color: #2e7d32; }
    
    .pagination { display: flex; justify-content: flex-end; gap: 5px; margin-top: 15px; }
    .page-btn { padding: 6px 12px; border: 1px solid var(--mac-border); border-radius: 4px; background: #fff; color: #333; text-decoration: none; font-size: 12px;}
    .page-btn.active { background: #0066cc; color: #fff; border-color: #0066cc; }
    
    .form-group { margin:0; }
    .form-group label { display: block; margin-bottom: 5px; font-size: 11px; font-weight: 600; color: #888; text-transform: uppercase; }
    .form-control { width: 100%; padding: 8px 12px; border: 1px solid var(--mac-border); border-radius: 4px; background: transparent; color: var(--text-main); box-sizing: border-box;}
</style>

<div class="header-actions">
    <h2>Procurement & Purchase Orders</h2>
    <a href="<?= APP_URL ?>/purchase/wizard" class="btn">+ Create Purchase Order</a>
</div>

<!-- Quick Navigation Links -->
<div class="quick-links">
    <span style="font-size: 11px; color: #888; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px;">Supply Chain:</span>
    <a href="<?= APP_URL ?>/supplier" class="btn-quick">🏢 Suppliers</a>
    <a href="<?= APP_URL ?>/purchase" class="btn-quick active">🛒 Purchase Orders</a>
    <a href="<?= APP_URL ?>/expenses" class="btn-quick">💳 Enter Bills & AP</a>
</div>

<?php if(!empty($data['error'])): ?><div style="padding: 10px; background:#ffebee; color:#c62828; border-radius:4px; margin-bottom:15px;"><?= htmlspecialchars($data['error']) ?></div><?php endif; ?>
<?php if(!empty($data['success'])): ?><div style="padding: 15px; background:#e8f5e9; color:#2e7d32; border-radius:4px; margin-bottom:15px; font-weight:bold; font-size: 14px;">✓ <?= htmlspecialchars($data['success']) ?></div><?php endif; ?>

<?php if(!empty($data['debug'])): ?>
    <script>console.error("Brevo API Error Response:", <?= json_encode(urldecode($data['debug'])) ?>);</script>
<?php endif; ?>

<input type="text" id="searchInput" class="search-bar" placeholder="🔍 Search PO Number or Supplier Name..." value="<?= htmlspecialchars($data['search']) ?>">

<!-- Advanced Filtering Panel -->
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
    <div class="form-group" style="flex: 1;">
        <label>Filter by Status</label>
        <select id="filterStatus" class="form-control" onchange="triggerSearch()">
            <option value="">All Statuses</option>
            <option value="Draft" <?= ($data['filters']['status'] == 'Draft') ? 'selected' : '' ?>>Draft</option>
            <option value="Sent" <?= ($data['filters']['status'] == 'Sent') ? 'selected' : '' ?>>Sent</option>
            <option value="Received" <?= ($data['filters']['status'] == 'Received') ? 'selected' : '' ?>>Received / Closed</option>
        </select>
    </div>
    <button class="btn btn-outline" style="padding: 8px 12px; border-color:#ccc; color:#666;" onclick="clearFilters()">Clear Filters</button>
</div>

<div id="tableContainer">
    <table class="data-table">
        <thead>
            <tr>
                <th>PO Number & Date</th>
                <th>Supplier / Vendor</th>
                <th>Status</th>
                <th style="text-align: right;">Total Amount</th>
                <th style="text-align: center;">Actions</th>
            </tr>
        </thead>
        <tbody id="tableBody">
            <?php if(empty($data['pos'])): ?>
            <tr><td colspan="5" style="text-align: center; color: #888; padding: 20px;">No Purchase Orders found.</td></tr>
            <?php else: foreach($data['pos'] as $po): ?>
            <tr>
                <td>
                    <strong><?= htmlspecialchars($po->po_number) ?></strong><br>
                    <span style="font-size: 11px; color: #888;"><?= date('M d, Y', strtotime($po->po_date)) ?></span>
                </td>
                <td>
                    <span style="color:#0066cc; font-weight:bold;"><?= htmlspecialchars($po->vendor_name) ?></span><br>
                    <span style="font-size: 10px; color:#888;">Created by: <?= htmlspecialchars($po->creator_name) ?></span>
                </td>
                <td><span class="status-badge status-<?= $po->status ?>"><?= $po->status ?></span></td>
                <td style="text-align: right; font-weight:bold;">Rs: <?= number_format($po->total_amount, 2) ?></td>
                
                <td style="text-align: center;">
                    <a href="<?= APP_URL ?>/purchase/show/<?= $po->id ?>" class="btn btn-small btn-outline" target="_blank">Print</a>
                    
                    <?php if($po->status !== 'Received'): ?>
                        <a href="<?= APP_URL ?>/purchase/edit/<?= $po->id ?>" class="btn btn-small" style="background:#f0ad4e; border:none; color:#fff; display:inline-block; text-decoration:none;">Edit</a>
                        
                        <!-- NEW: Intelligent Routing ensures we never bypass the GRN confirmation step -->
                        <?php if($po->has_mix): ?>
                            <!-- Routes to Variation Resolver first -->
                            <a href="<?= APP_URL ?>/purchase/resolve_mix_grn/<?= $po->id ?>" class="btn btn-small" style="background:#2e7d32; color:#fff; text-decoration:none; display:inline-block;">Receive GRN</a>
                        <?php else: ?>
                            <!-- Routes straight to the GRN Form (Since variations are already explicitly set) -->
                            <a href="<?= APP_URL ?>/grn/create?po_id=<?= $po->id ?>" class="btn btn-small" style="background:#2e7d32; color:#fff; text-decoration:none; display:inline-block;">Receive GRN</a>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php 
                        $btnText = '📧 Email';
                        $btnColor = '#673ab7'; 
                        if ($po->status === 'Sent') {
                            $btnText = '📧 Resend';
                            $btnColor = '#00897b'; // Changes to a Teal color to show it was sent
                        } elseif ($po->status === 'Received') {
                            $btnText = '📧 Email Copy';
                            $btnColor = '#888';
                        }
                    ?>
                    <!-- Email Dispatch Button -->
                    <form action="<?= APP_URL ?>/purchase/email_po" method="POST" style="display:inline;">
                        <input type="hidden" name="po_id" value="<?= $po->id ?>">
                        <button type="submit" class="btn btn-small" style="background:<?= $btnColor ?>; color:#fff; border:none; padding: 4px 8px; margin: 0 2px;" onclick="return confirm('Dispatch this Purchase Order via email to the supplier?');" title="<?= empty($po->email) ? 'No email found for vendor' : 'Send to ' . htmlspecialchars($po->email) ?>" <?= empty($po->email) ? 'disabled style="opacity:0.5; cursor:not-allowed;"' : '' ?>><?= $btnText ?></button>
                    </form>

                    <form action="<?= APP_URL ?>/purchase" method="POST" style="display:inline;">
                        <input type="hidden" name="action" value="delete_po">
                        <input type="hidden" name="po_id" value="<?= $po->id ?>">
                        <button type="submit" class="btn btn-small btn-danger" onclick="return confirm('Delete this PO permanently?');">Del</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>

    <div class="pagination" id="paginationContainer">
        <?php if($data['total_pages'] > 1): ?>
            <?php for($i = 1; $i <= $data['total_pages']; $i++): ?>
                <?php $params = http_build_query(['search' => $data['search'], 'vendor_id' => $data['filters']['vendor_id'], 'status' => $data['filters']['status'], 'page' => $i]); ?>
                <a href="?<?= $params ?>" class="page-btn <?= ($data['page'] == $i) ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
        <?php endif; ?>
    </div>
</div>

<script>
    let searchTimeout = null;
    document.getElementById('searchInput').addEventListener('input', triggerSearchDelay);
    function triggerSearchDelay() { clearTimeout(searchTimeout); searchTimeout = setTimeout(triggerSearch, 400); }

    function triggerSearch() {
        const query = encodeURIComponent(document.getElementById('searchInput').value);
        const venId = encodeURIComponent(document.getElementById('filterVendor').value);
        const status = encodeURIComponent(document.getElementById('filterStatus').value);
        const url = `?search=${query}&vendor_id=${venId}&status=${status}&page=1`;
        
        fetch(url).then(response => response.text()).then(html => {
            const parser = new DOMParser(); const doc = parser.parseFromString(html, 'text/html');
            document.getElementById('tableBody').innerHTML = doc.getElementById('tableBody').innerHTML;
            document.getElementById('paginationContainer').innerHTML = doc.getElementById('paginationContainer').innerHTML;
            window.history.pushState({}, '', url);
        });
    }

    function clearFilters() {
        document.getElementById('searchInput').value = '';
        document.getElementById('filterVendor').value = '';
        document.getElementById('filterStatus').value = '';
        triggerSearch();
    }
</script>