<style>
    .filter-card {
        background: var(--card-bg);
        border: 1px solid var(--card-border);
        border-radius: 16px;
        padding: 20px;
        margin-bottom: 25px;
        box-shadow: var(--card-shadow);
        backdrop-filter: blur(var(--glass-blur));
    }
    .filter-form {
        display: flex;
        gap: 15px;
        align-items: flex-end;
        flex-wrap: wrap;
    }
    .report-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
        font-size: 13.5px;
    }
    .report-table th {
        background: rgba(0,0,0,0.02);
        padding: 12px 14px;
        text-align: left;
        font-weight: 600;
        color: var(--text-muted);
        border-bottom: 2px solid var(--card-border);
    }
    @media (prefers-color-scheme: dark) {
        .report-table th { background: rgba(255,255,255,0.03); }
    }
    .report-table td {
        padding: 12px 14px;
        border-bottom: 1px solid var(--mega-divider);
        vertical-align: middle;
    }
    .report-table tr:hover {
        background: rgba(0,0,0,0.01);
    }

    .report-summary-bar {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-bottom: 20px;
    }
    .summary-badge-card {
        background: rgba(0,0,0,0.02);
        border: 1px solid var(--card-border);
        padding: 15px;
        border-radius: 10px;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
    @media (prefers-color-scheme: dark) {
        .summary-badge-card { background: rgba(255,255,255,0.03); }
    }
    .badge-val {
        font-size: 18px;
        font-weight: 700;
        color: var(--text-main);
    }
    .badge-lbl {
        font-size: 11.5px;
        color: var(--text-muted);
        font-weight: 500;
    }
</style>

<div class="header-actions" style="margin-bottom: 25px;">
    <h2>E-Commerce Analytics Reports</h2>
    <p style="color: var(--text-muted); margin-top: 4px;">Run dedicated storefront sales summaries, track inventory conversion, and analyze buyer demographic segments.</p>
</div>

<!-- Filters Selector -->
<div class="filter-card">
    <form action="<?= APP_URL ?>/ecommerce/reports" method="GET" class="filter-form">
        <div class="form-box" style="flex: 1; min-width: 220px; margin-bottom: 0;">
            <label>Select Report Target</label>
            <select name="report_type" class="form-control">
                <option value="sales_summary" <?= ($data['report_type'] === 'sales_summary') ? 'selected' : '' ?>>Sales Summary &amp; Daily Revenue</option>
                <option value="product_performance" <?= ($data['report_type'] === 'product_performance') ? 'selected' : '' ?>>Product Sales Performance</option>
                <option value="customer_segmentation" <?= ($data['report_type'] === 'customer_segmentation') ? 'selected' : '' ?>>Wholesale vs Retail Segmentation</option>
                <option value="abandoned_carts" <?= ($data['report_type'] === 'abandoned_carts') ? 'selected' : '' ?>>Abandoned Shopping Carts Roll</option>
            </select>
        </div>
        <div class="form-box" style="width: 150px; margin-bottom: 0;">
            <label>Start Date</label>
            <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($data['start_date']) ?>">
        </div>
        <div class="form-box" style="width: 150px; margin-bottom: 0;">
            <label>End Date</label>
            <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($data['end_date']) ?>">
        </div>
        <button type="submit" class="btn-primary" style="padding: 10px 22px; border-radius:8px; font-size:13px; height: 38px;">
            <i class="ph ph-funnel" style="vertical-align: middle; margin-right: 5px;"></i> Run Report
        </button>
    </form>
</div>

<!-- Report Render Card -->
<div class="card">
    <div style="border-bottom: 1px solid var(--mega-divider); padding-bottom: 15px; margin-bottom: 20px; display:flex; justify-content:space-between; align-items:center;">
        <h3 style="font-size: 15px; font-weight:700; margin:0;">
            <i class="ph ph-file-text" style="vertical-align: middle; margin-right: 5px; color: var(--text-accent);"></i>
            Report: <?= ucwords(str_replace('_', ' ', $data['report_type'])) ?> 
            <span style="font-size: 12px; font-weight: 500; color:var(--text-muted); margin-left:10px;">
                (<?= date('M d, Y', strtotime($data['start_date'])) ?> &rarr; <?= date('M d, Y', strtotime($data['end_date'])) ?>)
            </span>
        </h3>
    </div>

    <?php if (empty($data['report_data'])): ?>
        <div style="text-align: center; color: var(--text-muted); padding: 50px;">
            <i class="ph ph-chart-pie-slice" style="font-size: 48px; opacity: 0.5; margin-bottom: 10px;"></i>
            <p>No transactions or records matched this criteria during the selected period.</p>
        </div>
    <?php else: ?>

        <!-- Daily Sales Summary Report View -->
        <?php if ($data['report_type'] === 'sales_summary'): 
            $totRev = 0; $totOrders = 0; $totAovSum = 0;
            foreach($data['report_data'] as $row) {
                $totRev += $row->revenue;
                $totOrders += $row->total_orders;
                $totAovSum += $row->aov;
            }
            $avgAov = count($data['report_data']) > 0 ? ($totAovSum / count($data['report_data'])) : 0;
        ?>
            <div class="report-summary-bar">
                <div class="summary-badge-card">
                    <span class="badge-val">$<?= number_format($totRev, 2) ?></span>
                    <span class="badge-lbl">Total Period Revenue</span>
                </div>
                <div class="summary-badge-card">
                    <span class="badge-val"><?= $totOrders ?></span>
                    <span class="badge-lbl">Total Period Orders</span>
                </div>
                <div class="summary-badge-card">
                    <span class="badge-val">$<?= number_format($avgAov, 2) ?></span>
                    <span class="badge-lbl">Average Order Value (AOV)</span>
                </div>
            </div>

            <table class="report-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Orders Count</th>
                        <th>Total Revenue</th>
                        <th>Average Order Value</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($data['report_data'] as $row): ?>
                        <tr>
                            <td><strong><?= date('M d, Y', strtotime($row->date)) ?></strong></td>
                            <td><?= $row->total_orders ?></td>
                            <td><strong>$<?= number_format($row->revenue, 2) ?></strong></td>
                            <td>$<?= number_format($row->aov, 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        <!-- Product Sales Performance View -->
        <?php elseif ($data['report_type'] === 'product_performance'): 
            $totUnits = 0; $totRev = 0;
            foreach($data['report_data'] as $row) {
                $totUnits += $row->units_sold;
                $totRev += $row->revenue;
            }
        ?>
            <div class="report-summary-bar">
                <div class="summary-badge-card">
                    <span class="badge-val"><?= $totUnits ?></span>
                    <span class="badge-lbl">Total Units Sold</span>
                </div>
                <div class="summary-badge-card">
                    <span class="badge-val">$<?= number_format($totRev, 2) ?></span>
                    <span class="badge-lbl">Total Generated Revenue</span>
                </div>
            </div>

            <table class="report-table">
                <thead>
                    <tr>
                        <th>Product name</th>
                        <th>SKU Code</th>
                        <th>Units Sold</th>
                        <th>Total Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($data['report_data'] as $row): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($row->name) ?></strong></td>
                            <td><code><?= htmlspecialchars($row->sku) ?></code></td>
                            <td><?= $row->units_sold ?> units</td>
                            <td><strong>$<?= number_format($row->revenue, 2) ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        <!-- Customer Segmentation View -->
        <?php elseif ($data['report_type'] === 'customer_segmentation'): ?>
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Buyer Type Segment</th>
                        <th>Total Orders</th>
                        <th>Total Revenue</th>
                        <th>Average Ticket (AOV)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($data['report_data'] as $row): ?>
                        <tr>
                            <td>
                                <strong>
                                    <?= ($row->billing_type === 'wholesale') ? 'Wholesale Business Accounts' : 'Retail General Buyers' ?>
                                </strong>
                            </td>
                            <td><?= $row->total_orders ?></td>
                            <td><strong>$<?= number_format($row->revenue, 2) ?></strong></td>
                            <td>$<?= number_format($row->aov, 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        <!-- Abandoned Carts View -->
        <?php elseif ($data['report_type'] === 'abandoned_carts'): ?>
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Customer / Buyer Profile</th>
                        <th>Account Type</th>
                        <th>Cart Items Content</th>
                        <th>Estimated Value</th>
                        <th>Last Modified</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($data['report_data'] as $row): 
                        $items = json_decode($row->cart_data, true) ?: [];
                        $totVal = 0;
                        $itemsText = [];
                        foreach($items as $i) {
                            $totVal += ($i['price'] * $i['qty']);
                            $itemsText[] = htmlspecialchars($i['name']) . " (x" . $i['qty'] . ")";
                        }
                    ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($row->customer_name ?: 'Guest Buyer') ?></strong></td>
                            <td>
                                <span class="pill-badge" style="background: rgba(0,0,0,0.05); color: var(--text-muted);">
                                    <?= ucfirst($row->customer_type) ?>
                                </span>
                            </td>
                            <td>
                                <div style="font-size: 11px; max-width: 400px; color:#555; max-height: 48px; overflow-y:auto;">
                                    <?= implode(', ', $itemsText) ?: 'Empty cart data' ?>
                                </div>
                            </td>
                            <td><strong>$<?= number_format($totVal, 2) ?></strong></td>
                            <td><?= date('M d, Y h:i A', strtotime($row->updated_at)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

    <?php endif; ?>
</div>
