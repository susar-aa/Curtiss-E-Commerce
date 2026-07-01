<style>
    .header-actions { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .btn { padding: 8px 16px; background: #ff3b30; color: #fff; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; font-size: 13px; font-weight: 600; }
    .btn:hover { background: #e03026; }
    .metric-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px; }
    .metric-card { background: var(--bg-card); border: 1px solid var(--mac-border); padding: 20px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
    .metric-title { font-size: 12px; color: #86868b; text-transform: uppercase; font-weight: bold; margin-bottom: 6px; }
    .metric-value { font-size: 28px; font-weight: 700; color: #ff3b30; }
    .damaged-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    .damaged-table th, .damaged-table td { padding: 12px; border-bottom: 1px solid var(--mac-border); text-align: left; }
    .damaged-table th { background: rgba(255, 59, 48, 0.05); color: #ff3b30; font-weight: 600; }
    .damaged-table tr:hover { background: rgba(0, 0, 0, 0.01); }
    .search-input { width: 100%; max-width: 300px; padding: 8px 12px; border: 1px solid var(--mac-border); border-radius: 4px; background: transparent; color: var(--text-main); margin-bottom: 15px; }
</style>

<div class="card">
    <div class="header-actions">
        <h2 style="color: #ff3b30; display: flex; align-items: center; gap: 8px;">
            <span>⚠️</span> Damaged Products Log
        </h2>
        <div>
            <a href="<?= APP_URL ?>/creditnote" class="btn" style="background:#666; margin-right:10px;">Back to Credit Notes</a>
            <a href="<?= APP_URL ?>/creditnote/create" class="btn">Issue Return/CN</a>
        </div>
    </div>

    <?php
        $totalItems = 0;
        $totalLoss = 0;
        foreach ($data['items'] as $item) {
            $totalItems += $item->quantity;
            // Loss value calculation: qty * original cost
            $totalLoss += ($item->quantity * ($item->current_cost > 0 ? $item->current_cost : $item->unit_price));
        }
    ?>

    <div class="metric-grid">
        <div class="metric-card">
            <div class="metric-title">Total Damaged Qty Received</div>
            <div class="metric-value"><?= number_format($totalItems, 0) ?> Pcs</div>
        </div>
        <div class="metric-card">
            <div class="metric-title">Total Estimated Cost Loss (LKR)</div>
            <div class="metric-value">Rs: <?= number_format($totalLoss, 2) ?></div>
        </div>
    </div>

    <div style="display: flex; justify-content: space-between; align-items: center;">
        <input type="text" id="logSearch" class="search-input" placeholder="Search by product, customer, or CN#...">
    </div>

    <table class="damaged-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Credit Note #</th>
                <th>Customer</th>
                <th>Product Description</th>
                <th style="text-align: right;">Qty</th>
                <th style="text-align: right;">Est. Unit Cost</th>
                <th style="text-align: right;">Total Loss</th>
                <th>Original Invoice</th>
            </tr>
        </thead>
        <tbody id="logBody">
            <?php if (empty($data['items'])): ?>
                <tr>
                    <td colspan="8" style="text-align: center; color: #86868b; padding: 40px 0;">No damaged products have been logged yet.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($data['items'] as $item): ?>
                    <?php 
                        $unitCost = $item->current_cost > 0 ? $item->current_cost : $item->unit_price;
                        $lineLoss = $item->quantity * $unitCost;
                    ?>
                    <tr>
                        <td><?= date('Y-m-d', strtotime($item->note_date)) ?></td>
                        <td>
                            <a href="<?= APP_URL ?>/creditnote/show/<?= $item->credit_note_id ?>" style="font-weight: 600; color: #ff3b30; text-decoration: none;">
                                <?= htmlspecialchars($item->credit_note_number) ?>
                            </a>
                        </td>
                        <td><?= htmlspecialchars($item->customer_name) ?></td>
                        <td style="font-weight: 500;"><?= htmlspecialchars($item->description) ?></td>
                        <td style="text-align: right; font-weight: bold;"><?= number_format($item->quantity, 0) ?> Pcs</td>
                        <td style="text-align: right; color: #666;">Rs: <?= number_format($unitCost, 2) ?></td>
                        <td style="text-align: right; color: #ff3b30; font-weight: bold;">Rs: <?= number_format($lineLoss, 2) ?></td>
                        <td>
                            <?php if ($item->invoice_id): ?>
                                <span style="font-size: 11px; background: rgba(0,0,0,0.05); padding: 4px 8px; border-radius: 4px; font-weight: 500;">
                                    Invoice Link
                                </span>
                            <?php else: ?>
                                <span style="color:#aaa; font-style:italic;">Direct Return</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
    document.getElementById('logSearch').addEventListener('input', function() {
        const query = this.value.toLowerCase().trim();
        document.querySelectorAll('#logBody tr').forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(query) ? '' : 'none';
        });
    });
</script>
