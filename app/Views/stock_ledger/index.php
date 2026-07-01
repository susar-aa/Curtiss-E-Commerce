<?php
?>
<style>
    .header-actions { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .btn { padding: 8px 16px; background: #0066cc; color: #fff; border: none; border-radius: 6px; cursor: pointer; text-decoration: none; font-size: 14px; font-weight: 500; display: inline-flex; align-items: center; gap: 8px; transition: background 0.2s; }
    .btn:hover { background: #0052a3; }
    .btn-outline { background: transparent; border: 1px solid #0066cc; color: #0066cc; }
    .btn-outline:hover { background: rgba(0, 102, 204, 0.05); }
    .btn-small { padding: 4px 8px; font-size: 11px; border-radius: 4px;}

    .quick-links { display: flex; gap: 10px; margin-bottom: 25px; align-items: center; background: rgba(0,0,0,0.02); padding: 10px 15px; border-radius: 8px; border: 1px solid var(--mac-border); }
    .btn-quick { padding: 6px 12px; background: #fff; color: #555; border: 1px solid var(--mac-border); border-radius: 6px; font-size: 12px; font-weight: 600; text-decoration: none; transition: 0.2s; }
    .btn-quick:hover { background: rgba(0,102,204,0.05); color: #0066cc; border-color: #0066cc; }
    .btn-quick.active { background: #0066cc; color: #fff; border-color: #0066cc; }

    /* Summary Metric Cards */
    .metric-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px; }
    .metric-card { background: #fff; border: 1px solid var(--mac-border); border-radius: 10px; padding: 15px 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); transition: transform 0.2s, box-shadow 0.2s; }
    .metric-card:hover { transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.05); }
    @media (prefers-color-scheme: dark) { .metric-card { background: #1e1e2d; } }
    .metric-label { font-size: 11px; font-weight: 600; color: #888; text-transform: uppercase; margin-bottom: 5px; letter-spacing: 0.5px; }
    .metric-value { font-size: 20px; font-weight: 700; color: var(--text-main); }
    .metric-sub { font-size: 11px; color: #666; margin-top: 5px; }

    /* Filter Panel styling */
    .filter-panel { background: #fff; padding: 20px; border-radius: 10px; border: 1px solid var(--mac-border); margin-bottom: 20px; }
    @media (prefers-color-scheme: dark) { .filter-panel { background: #1e1e2d; } }
    .filter-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin-bottom: 15px; }
    .filter-footer { display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid var(--mac-border); padding-top: 15px; }
    
    .form-group { margin:0; }
    .form-group label { display: block; margin-bottom: 5px; font-size: 11px; font-weight: 600; color: #888; text-transform: uppercase; }
    .form-control { width: 100%; padding: 8px 12px; border: 1px solid var(--mac-border); border-radius: 6px; background: transparent; color: var(--text-main); box-sizing: border-box; font-size: 13px;}
    
    .search-bar { width: 100%; padding: 12px 15px; border: 1px solid var(--mac-border); border-radius: 8px; font-size: 14px; margin-bottom: 15px; background: transparent; color: var(--text-main); box-sizing: border-box; }
    
    .data-table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 10px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.05);}
    @media (prefers-color-scheme: dark) { .data-table { background: #1e1e2d; } }
    .data-table th, .data-table td { padding: 12px 15px; text-align: left; border-bottom: 1px solid var(--mac-border); font-size: 13px;}
    .data-table th { background-color: rgba(0,0,0,0.02); font-weight: 600; color:#555;}
    .data-table tr:hover { background-color: rgba(0,0,0,0.01); }

    .badge { padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; display: inline-block; }
    .badge-in { background-color: #e8f5e9; color: #2e7d32; }
    .badge-out { background-color: #ffebee; color: #c62828; }
    
    .pagination { display: flex; justify-content: flex-end; gap: 5px; margin-top: 20px; }
    .page-btn { padding: 6px 12px; border: 1px solid var(--mac-border); border-radius: 6px; background: #fff; color: #333; text-decoration: none; font-size: 12px; transition: 0.2s;}
    .page-btn:hover { background: rgba(0,0,0,0.02); }
    .page-btn.active { background: #0066cc; color: #fff; border-color: #0066cc; }
</style>

<div class="header-actions">
    <h2><i class="ph ph-receipt"></i> Stock Ledger / Audit Trail</h2>
    <div style="display: flex; gap: 10px;">
        <a href="<?= APP_URL ?>/stockledger/exportCsv?<?= http_build_query($data['filters']) ?>" class="btn btn-outline">
            <i class="ph ph-download"></i> Export CSV
        </a>
    </div>
</div>

<div class="quick-links">
    <span style="font-size: 11px; color: #888; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px;">Inventory:</span>
    <a href="<?= APP_URL ?>/inventory" class="btn-quick">🗄️ Product List / Stock</a>
    <a href="<?= APP_URL ?>/grn" class="btn-quick">📦 Goods Receipts (GRN)</a>
    <a href="<?= APP_URL ?>/warehouse/transfer" class="btn-quick">Warehouse Transfers</a>
    <a href="<?= APP_URL ?>/stockledger" class="btn-quick active">📜 Stock Ledger</a>
</div>

<!-- Metrics Cards -->
<div class="metric-grid">
    <div class="metric-card">
        <div class="metric-label">Total Stock In</div>
        <div class="metric-value" style="color: #2e7d32;">
            +<?= number_format($data['metrics']->total_in ?? 0, 2) ?>
        </div>
        <div class="metric-sub">Sum of additions</div>
    </div>
    <div class="metric-card">
        <div class="metric-label">Total Stock Out</div>
        <div class="metric-value" style="color: #c62828;">
            -<?= number_format($data['metrics']->total_out ?? 0, 2) ?>
        </div>
        <div class="metric-sub">Sum of depletions</div>
    </div>
    <div class="metric-card">
        <div class="metric-label">Net Movement</div>
        <div class="metric-value" style="color: <?= ($data['metrics']->net_movement ?? 0) >= 0 ? '#2e7d32' : '#c62828' ?>;">
            <?= ($data['metrics']->net_movement ?? 0) >= 0 ? '+' : '' ?><?= number_format($data['metrics']->net_movement ?? 0, 2) ?>
        </div>
        <div class="metric-sub">Total inventory variance</div>
    </div>
    <div class="metric-card">
        <div class="metric-label">Value Impact (Cost)</div>
        <div class="metric-value" style="color: #0066cc;">
            Rs: <?= number_format($data['metrics']->total_value_impact ?? 0, 2) ?>
        </div>
        <div class="metric-sub">Financial value of movement</div>
    </div>
</div>

<form id="ledgerFilterForm" method="GET" action="<?= APP_URL ?>/stockledger">
    <input type="text" name="search" id="searchInput" class="search-bar" placeholder="🔍 Search Product Name, SKU, Barcode, Reference Number..." value="<?= htmlspecialchars($data['filters']['search']) ?>">

    <!-- Advanced Filter Panel -->
    <div class="filter-panel">
        <div class="filter-grid">
            <div class="form-group">
                <label>Date Range (From)</label>
                <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($data['filters']['start_date']) ?>">
            </div>
            <div class="form-group">
                <label>Date Range (To)</label>
                <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($data['filters']['end_date']) ?>">
            </div>
            <div class="form-group">
                <label>Product</label>
                <select name="item_id" class="form-control">
                    <option value="">-- All Products --</option>
                    <?php foreach ($data['items'] as $item): ?>
                        <option value="<?= $item->id ?>" <?= $data['filters']['item_id'] == $item->id ? 'selected' : '' ?>>
                            <?= htmlspecialchars($item->name) ?> (<?= htmlspecialchars($item->item_code ?? '') ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Category</label>
                <select name="category_id" class="form-control">
                    <option value="">-- All Categories --</option>
                    <?php foreach ($data['categories'] as $cat): ?>
                        <option value="<?= $cat->id ?>" <?= $data['filters']['category_id'] == $cat->id ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat->name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Warehouse / Location</label>
                <select name="warehouse_id" class="form-control">
                    <option value="">-- All Locations --</option>
                    <?php foreach ($data['warehouses'] as $wh): ?>
                        <option value="<?= $wh->id ?>" <?= $data['filters']['warehouse_id'] == $wh->id ? 'selected' : '' ?>>
                            <?= htmlspecialchars($wh->name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Transaction Type</label>
                <select name="transaction_type" class="form-control">
                    <option value="">-- All Types --</option>
                    <option value="Opening Stock" <?= $data['filters']['transaction_type'] == 'Opening Stock' ? 'selected' : '' ?>>Opening Stock</option>
                    <option value="GRN" <?= $data['filters']['transaction_type'] == 'GRN' ? 'selected' : '' ?>>Goods Receipt (GRN)</option>
                    <option value="Sales Invoice" <?= $data['filters']['transaction_type'] == 'Sales Invoice' ? 'selected' : '' ?>>Sales Invoice</option>
                    <option value="Sales Return" <?= $data['filters']['transaction_type'] == 'Sales Return' ? 'selected' : '' ?>>Sales Return</option>
                    <option value="Purchase Return" <?= $data['filters']['transaction_type'] == 'Purchase Return' ? 'selected' : '' ?>>Purchase Return</option>
                    <option value="Stock Adjustment" <?= $data['filters']['transaction_type'] == 'Stock Adjustment' ? 'selected' : '' ?>>Stock Adjustment</option>
                    <option value="Stock Transfer" <?= $data['filters']['transaction_type'] == 'Stock Transfer' ? 'selected' : '' ?>>Warehouse Transfer</option>
                    <option value="Manual Correction" <?= $data['filters']['transaction_type'] == 'Manual Correction' ? 'selected' : '' ?>>Manual Correction</option>
                </select>
            </div>
            <div class="form-group">
                <label>Brand</label>
                <select name="brand" class="form-control">
                    <option value="">-- All Brands --</option>
                    <?php foreach ($data['brands'] as $b): ?>
                        <option value="<?= htmlspecialchars($b->brand) ?>" <?= $data['filters']['brand'] == $b->brand ? 'selected' : '' ?>>
                            <?= htmlspecialchars($b->brand) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>User</label>
                <select name="user_id" class="form-control">
                    <option value="">-- All Users --</option>
                    <?php foreach ($data['users'] as $u): ?>
                        <option value="<?= $u->id ?>" <?= $data['filters']['user_id'] == $u->id ? 'selected' : '' ?>>
                            <?= htmlspecialchars($u->username) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="filter-footer">
            <button type="button" class="btn btn-outline" onclick="clearAllFilters()">Reset Filters</button>
            <button type="submit" class="btn"><i class="ph ph-funnel"></i> Apply Filter</button>
        </div>
    </div>
</form>

<div style="overflow-x:auto;">
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 130px;">Date & Time</th>
                <th>Product details</th>
                <th>Location</th>
                <th>Type</th>
                <th>Reference</th>
                <th style="text-align: right;">Qty In</th>
                <th style="text-align: right;">Qty Out</th>
                <th style="text-align: right;">Running Balance</th>
                <th style="text-align: right;">Unit Cost (Rs)</th>
                <th style="text-align: right;">Value Impact (Rs)</th>
                <th>Remarks</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if(empty($data['movements'])): ?>
                <tr>
                    <td colspan="12" style="text-align:center; color:#999; padding:30px;">No stock ledger entries match the current filter criteria.</td>
                </tr>
            <?php else: foreach($data['movements'] as $mv): ?>
                <tr>
                    <td style="color:#666; font-size:11px;"><?= date('Y-m-d H:i:s', strtotime($mv->transaction_date)) ?></td>
                    <td>
                        <strong style="color:var(--text-main);"><?= htmlspecialchars($mv->item_name) ?></strong><br>
                        <span style="font-size:11px; color:#888;">SKU: <?= htmlspecialchars($mv->sku ?? '') ?></span>
                        <?php if($mv->variation_name): ?>
                            <span style="background:#f1f3f5; padding:2px 6px; border-radius:4px; font-size:10px; color:#555; font-weight:600; margin-left:5px;"><?= htmlspecialchars($mv->variation_name) ?></span>
                        <?php endif; ?>
                    </td>
                    <td><span style="color:#555;"><?= htmlspecialchars($mv->warehouse_name ?? 'Default Location') ?></span></td>
                    <td>
                        <span style="font-weight: 600; font-size: 11px; color: #0066cc;"><?= htmlspecialchars($mv->transaction_type) ?></span>
                    </td>
                    <td>
                        <span style="font-weight:600; font-family:monospace; background:rgba(0,0,0,0.04); padding:2px 6px; border-radius:4px;"><?= htmlspecialchars($mv->reference_number) ?></span>
                    </td>
                    <td style="text-align: right;">
                        <?php if($mv->quantity_in > 0): ?>
                            <span class="badge badge-in">+<?= number_format($mv->quantity_in, 0) ?></span>
                        <?php else: ?>
                            <span style="color:#ccc;">-</span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align: right;">
                        <?php if($mv->quantity_out > 0): ?>
                            <span class="badge badge-out">-<?= number_format($mv->quantity_out, 0) ?></span>
                        <?php else: ?>
                            <span style="color:#ccc;">-</span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align: right; font-weight:700; color:#333;">
                        <?= number_format($mv->running_balance, 0) ?>
                    </td>
                    <td style="text-align: right; color:#555;">
                        <?= number_format($mv->unit_cost, 2) ?>
                    </td>
                    <td style="text-align: right; font-weight:bold; color:#0066cc;">
                        <?= number_format($mv->total_value, 2) ?>
                    </td>
                    <td>
                        <span style="font-size:11px; color:#666;" title="Logged by <?= htmlspecialchars($mv->user_name) ?>"><?= htmlspecialchars($mv->remarks) ?></span>
                    </td>
                    <td>
                        <a href="<?= APP_URL ?>/stockledger/product/<?= $mv->item_id ?><?= $mv->variation_option_id ? '?variation_option_id='.$mv->variation_option_id : '' ?>" class="btn btn-small btn-outline" style="white-space:nowrap;">
                            <i class="ph ph-chart-line"></i> Stock Card
                        </a>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<?php if($data['totalPages'] > 1): ?>
    <div class="pagination">
        <?php if($data['currentPage'] > 1): ?>
            <a href="?<?= http_build_query(array_merge($data['filters'], ['page' => $data['currentPage'] - 1])) ?>" class="page-btn">&larr; Previous</a>
        <?php endif; ?>
        
        <?php for($i = 1; $i <= $data['totalPages']; $i++): ?>
            <a href="?<?= http_build_query(array_merge($data['filters'], ['page' => $i])) ?>" class="page-btn <?= $i == $data['currentPage'] ? 'active' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>
        
        <?php if($data['currentPage'] < $data['totalPages']): ?>
            <a href="?<?= http_build_query(array_merge($data['filters'], ['page' => $data['currentPage'] + 1])) ?>" class="page-btn">Next &rarr;</a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<script>
    function clearAllFilters() {
        document.getElementById('searchInput').value = '';
        const controls = document.querySelectorAll('.filter-panel select, .filter-panel input[type="date"]');
        controls.forEach(c => c.value = '');
        document.getElementById('ledgerFilterForm').submit();
    }
</script>
