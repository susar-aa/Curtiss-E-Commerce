<?php
?>
<style>
    .header-actions { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .btn { padding: 8px 16px; background: #0066cc; color: #fff; border: none; border-radius: 6px; cursor: pointer; text-decoration: none; font-size: 14px; font-weight: 500; display: inline-flex; align-items: center; gap: 8px; transition: background 0.2s; }
    .btn:hover { background: #0052a3; }
    .btn-outline { background: transparent; border: 1px solid #0066cc; color: #0066cc; }
    .btn-outline:hover { background: rgba(0, 102, 204, 0.05); }

    .quick-links { display: flex; gap: 10px; margin-bottom: 25px; align-items: center; background: rgba(0,0,0,0.02); padding: 10px 15px; border-radius: 8px; border: 1px solid var(--mac-border); }
    .btn-quick { padding: 6px 12px; background: #fff; color: #555; border: 1px solid var(--mac-border); border-radius: 6px; font-size: 12px; font-weight: 600; text-decoration: none; transition: 0.2s; }
    .btn-quick:hover { background: rgba(0,102,204,0.05); color: #0066cc; border-color: #0066cc; }
    .btn-quick.active { background: #0066cc; color: #fff; border-color: #0066cc; }

    .card-panel { background: #fff; border: 1px solid var(--mac-border); border-radius: 12px; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.02); margin-bottom: 20px; }
    @media (prefers-color-scheme: dark) { .card-panel { background: #1e1e2d; } }

    .product-info-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 30px; margin-bottom: 25px; border-bottom: 1px solid var(--mac-border); padding-bottom: 20px; }
    
    .filter-bar { display: flex; gap: 15px; align-items: flex-end; margin-bottom: 20px; background: rgba(0,0,0,0.01); padding: 15px; border-radius: 8px; border: 1px solid var(--mac-border); }
    
    .form-group { margin: 0; }
    .form-group label { display: block; margin-bottom: 5px; font-size: 11px; font-weight: 600; color: #888; text-transform: uppercase; }
    .form-control { padding: 8px 12px; border: 1px solid var(--mac-border); border-radius: 6px; background: transparent; color: var(--text-main); font-size: 13px; box-sizing: border-box;}

    /* Stock Card Timeline Table */
    .ledger-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
    .ledger-table th, .ledger-table td { padding: 12px 15px; border-bottom: 1px solid var(--mac-border); font-size: 13px; text-align: left; }
    .ledger-table th { background-color: rgba(0,0,0,0.02); font-weight: 600; color: #555; }
    .ledger-table tr:hover { background-color: rgba(0,0,0,0.01); }

    .opening-row { background-color: rgba(0,102,204,0.03); font-weight: 600; }
    .closing-row { background-color: rgba(46,125,50,0.03); font-weight: 700; border-top: 2px solid #2e7d32; border-bottom: 2px solid #2e7d32; }

    .badge { padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; display: inline-block; }
    .badge-in { background-color: #e8f5e9; color: #2e7d32; }
    .badge-out { background-color: #ffebee; color: #c62828; }
</style>

<div class="header-actions">
    <h2><i class="ph ph-chart-line"></i> Stock Card / History</h2>
    <a href="<?= APP_URL ?>/stockledger" class="btn btn-outline">&larr; Back to Ledger</a>
</div>

<div class="quick-links">
    <span style="font-size: 11px; color: #888; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px;">Navigation:</span>
    <a href="<?= APP_URL ?>/inventory" class="btn-quick">🗄️ Inventory List</a>
    <a href="<?= APP_URL ?>/stockledger" class="btn-quick">📜 General Stock Ledger</a>
    <a href="#" class="btn-quick active">📈 Stock Card Detail</a>
</div>

<div class="card-panel">
    <div class="product-info-grid">
        <div>
            <span style="font-size:12px; font-weight:700; color:#0066cc; text-transform:uppercase; letter-spacing:0.5px;">Product Stock Card</span>
            <h1 style="margin:5px 0 10px 0; font-size:24px; color:var(--text-main); font-weight: 700;"><?= htmlspecialchars($data['item']->name) ?></h1>
            <p style="margin:0; font-size:14px; color:#666;">
                <strong>SKU:</strong> <span style="font-family:monospace;"><?= htmlspecialchars($data['item']->item_code ?? 'N/A') ?></span> &nbsp;&bull;&nbsp;
                <strong>Barcode:</strong> <?= htmlspecialchars($data['item']->barcode ?? 'N/A') ?> &nbsp;&bull;&nbsp;
                <strong>Brand:</strong> <?= htmlspecialchars($data['item']->brand ?? 'N/A') ?>
            </p>
        </div>
        <div style="text-align: right; display: flex; flex-direction: column; justify-content: center; align-items: flex-end; border-left: 1px solid var(--mac-border); padding-left: 30px;">
            <div style="font-size: 11px; font-weight: 600; color: #888; text-transform: uppercase;">Current System Stock</div>
            <div style="font-size: 32px; font-weight: 800; color: #2e7d32;"><?= number_format($data['item']->quantity_on_hand, 0) ?></div>
            <div style="font-size: 11px; color: #666; font-style: italic;">Physical Qty On Hand</div>
        </div>
    </div>

    <!-- Filter Bar -->
    <form method="GET" action="<?= APP_URL ?>/stockledger/product/<?= $data['item']->id ?>">
        <div class="filter-bar">
            <?php if(!empty($data['variations'])): ?>
                <div class="form-group" style="flex: 1;">
                    <label>Product Variation</label>
                    <select name="variation_option_id" class="form-control" style="width:100%;">
                        <option value="">-- Main Product / No Variation --</option>
                        <?php foreach($data['variations'] as $v): ?>
                            <option value="<?= $v->id ?>" <?= $data['varOptId'] == $v->id ? 'selected' : '' ?>>
                                <?= htmlspecialchars($v->option_name) ?> (Stock: <?= number_format($v->quantity_on_hand, 0) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            
            <div class="form-group" style="flex: 1;">
                <label>Date From</label>
                <input type="date" name="start_date" class="form-control" style="width:100%;" value="<?= htmlspecialchars($data['startDate']) ?>">
            </div>
            
            <div class="form-group" style="flex: 1;">
                <label>Date To</label>
                <input type="date" name="end_date" class="form-control" style="width:100%;" value="<?= htmlspecialchars($data['endDate']) ?>">
            </div>
            
            <div>
                <button type="submit" class="btn" style="padding: 10px 20px;"><i class="ph ph-funnel"></i> Refresh Card</button>
            </div>
        </div>
    </form>

    <div style="overflow-x:auto;">
        <table class="ledger-table">
            <thead>
                <tr>
                    <th style="width: 140px;">Date & Time</th>
                    <th>Transaction Type</th>
                    <th>Reference</th>
                    <th>Location</th>
                    <th style="text-align: right;">Qty In</th>
                    <th style="text-align: right;">Qty Out</th>
                    <th style="text-align: right;">Running Balance</th>
                    <th>Recorded By</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                <!-- Opening Balance Row -->
                <tr class="opening-row">
                    <td style="color:#666; font-size:11px;"><?= htmlspecialchars($data['startDate']) ?> 00:00:00</td>
                    <td><span style="color:#0066cc;">OPENING BALANCE</span></td>
                    <td>-</td>
                    <td>-</td>
                    <td style="text-align: right;">-</td>
                    <td style="text-align: right;">-</td>
                    <td style="text-align: right; color:#0066cc; font-weight:bold; font-size:14px;">
                        <?= number_format($data['opening_balance'], 0) ?>
                    </td>
                    <td>-</td>
                    <td style="color:#666; font-style:italic;">Opening balance for selected date range</td>
                </tr>

                <!-- Movements Rows -->
                <?php if(empty($data['movements'])): ?>
                    <tr>
                        <td colspan="9" style="text-align:center; color:#999; padding:25px;">No inventory movements recorded in this period.</td>
                    </tr>
                <?php else: foreach($data['movements'] as $mv): ?>
                    <tr>
                        <td style="color:#666; font-size:11px;"><?= date('Y-m-d H:i:s', strtotime($mv->transaction_date)) ?></td>
                        <td><span style="font-weight: 600;"><?= htmlspecialchars($mv->transaction_type) ?></span></td>
                        <td>
                            <span style="font-weight:600; font-family:monospace; background:rgba(0,0,0,0.04); padding:2px 6px; border-radius:4px;"><?= htmlspecialchars($mv->reference_number) ?></span>
                        </td>
                        <td><?= htmlspecialchars($mv->warehouse_name ?? 'Default Location') ?></td>
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
                        <td style="text-align: right; font-weight:700; color:#333; font-size:13px;">
                            <?= number_format($mv->computed_running_balance, 0) ?>
                        </td>
                        <td><span style="font-size:12px; color:#555;"><?= htmlspecialchars($mv->user_name) ?></span></td>
                        <td><span style="font-size:11px; color:#666;"><?= htmlspecialchars($mv->remarks) ?></span></td>
                    </tr>
                <?php endforeach; endif; ?>

                <!-- Closing Balance Row -->
                <tr class="closing-row">
                    <td style="color:#666; font-size:11px;"><?= htmlspecialchars($data['endDate']) ?> 23:59:59</td>
                    <td><span style="color:#2e7d32;">CLOSING BALANCE</span></td>
                    <td>-</td>
                    <td>-</td>
                    <td style="text-align: right;">-</td>
                    <td style="text-align: right;">-</td>
                    <td style="text-align: right; color:#2e7d32; font-weight:bold; font-size:15px;">
                        <?= number_format($data['closing_balance'], 0) ?>
                    </td>
                    <td>-</td>
                    <td style="color:#2e7d32; font-style:italic;">Closing balance for selected date range</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
