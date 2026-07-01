<?php
?>
<style>
    .header-actions { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 20px; }
    .btn { padding: 10px 20px; background: #0066cc; color: #fff; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; font-size: 15px; font-weight:bold;}
    
    .info-panel { background: rgba(0,102,204,0.05); border: 1px solid rgba(0,102,204,0.2); padding: 15px; border-radius: 8px; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center;}
    
    .data-table { width: 100%; border-collapse: collapse; margin-top: 10px; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.05);}
    @media (prefers-color-scheme: dark) { .data-table { background: #1e1e2d; } }
    .data-table th, .data-table td { padding: 12px; text-align: left; border-bottom: 1px solid var(--mac-border); font-size: 14px;}
    .data-table th { background-color: rgba(0,0,0,0.02); font-weight: 600; color:#555;}
    
    .form-control { width: 100px; padding: 6px; border: 1px solid var(--mac-border); border-radius: 4px; background: transparent; color: var(--text-main); text-align: center; font-weight: bold; font-size: 15px;}
    .form-control:focus { border-color: #0066cc; outline: none; }
    
    .num-col { text-align: center; }
</style>

<div class="card" style="max-width: 1000px; margin: auto;">
    <div class="header-actions">
        <div>
            <a href="<?= APP_URL ?>/purchase/wizard" style="color: #666; text-decoration:none; font-size: 13px;">&larr; Back to Wizard</a>
            <h2 style="margin: 10px 0 0 0;">AI Suggested Order</h2>
            <p style="margin: 0; color: #888; font-size: 14px;">Vendor: <?= htmlspecialchars($data['vendor']->name) ?></p>
        </div>
    </div>

    <div class="info-panel">
        <div>
            <span style="font-size: 12px; color: #666; text-transform: uppercase;">Analysis Period</span><br>
            <strong><?= date('M d, Y', strtotime($data['start_date'])) ?> to <?= date('M d, Y', strtotime($data['end_date'])) ?></strong>
        </div>
        <div>
            <span style="font-size: 12px; color: #666; text-transform: uppercase;">Safety Buffer Applied</span><br>
            <strong><?= $data['buffer'] ?>%</strong>
        </div>
        <div style="font-size: 12px; color: #555; max-width: 300px; text-align: right;">
            <em>Calculation: (Sold Qty + Buffer) - Current Stock = Suggested. Negative results default to 0.</em>
        </div>
    </div>

    <form action="<?= APP_URL ?>/purchase/create" method="POST">
        <input type="hidden" name="action" value="from_suggest">
        <input type="hidden" name="vendor_id" value="<?= $data['vendor']->id ?>">

        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 40px; text-align:center;"><input type="checkbox" id="selectAll" checked onchange="toggleAll(this)"></th>
                    <th>Product / Item</th>
                    <th class="num-col">Sold in Period</th>
                    <th class="num-col">Current Stock</th>
                    <th class="num-col" style="color:#0066cc;">Suggested Qty</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($data['sales_data'])): ?>
                    <tr><td colspan="5" style="text-align: center; color: #888; padding: 40px;">No sales history found for this vendor in the selected date range.</td></tr>
                <?php else: foreach($data['sales_data'] as $index => $item): ?>
                    <tr>
                        <td style="text-align:center;">
                            <input type="checkbox" name="selected_items[]" value="<?= $index ?>" class="item-cb" <?= $item->suggested_qty > 0 ? 'checked' : '' ?>>
                            
                            <!-- Hidden Data required for PO Creation mapping -->
                            <input type="hidden" name="item_id[<?= $index ?>]" value="<?= $item->id ?>">
                            <input type="hidden" name="item_name[<?= $index ?>]" value="<?= htmlspecialchars($item->item_name) ?>">
                            <input type="hidden" name="item_cost[<?= $index ?>]" value="<?= $item->cost ?>">
                        </td>
                        <td><strong><?= htmlspecialchars($item->item_name) ?></strong><br><span style="font-size:11px; color:#888;">Cost: Rs. <?= number_format($item->cost, 2) ?></span></td>
                        <td class="num-col"><?= $item->sold_qty ?></td>
                        <td class="num-col" style="color: <?= $item->quantity_on_hand <= 0 ? '#c62828' : '#2e7d32' ?>; font-weight:bold;"><?= $item->quantity_on_hand ?></td>
                        <td class="num-col">
                            <input type="number" name="suggested_qty[<?= $index ?>]" value="<?= $item->suggested_qty ?>" class="form-control" min="1" step="1">
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>

        <div style="margin-top: 30px; text-align: right;">
            <button type="submit" class="btn">Generate Draft PO with Selected Items &rarr;</button>
        </div>
    </form>
</div>

<script>
    function toggleAll(masterCb) {
        document.querySelectorAll('.item-cb').forEach(cb => cb.checked = masterCb.checked);
    }
</script>