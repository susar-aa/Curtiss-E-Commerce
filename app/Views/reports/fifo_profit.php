<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $data['title'] ?> - <?= APP_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #0b0f19;
            --card-bg: rgba(23, 28, 41, 0.65);
            --border-color: rgba(255, 255, 255, 0.08);
            --text-main: #f3f4f6;
            --text-muted: #9ca3af;
            --primary: #3b82f6;
            --primary-glow: rgba(59, 130, 246, 0.3);
            --success: #10b981;
            --success-glow: rgba(16, 185, 129, 0.2);
            --warning: #f59e0b;
            --danger: #ef4444;
            --font-family: 'Outfit', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: var(--font-family);
            background-color: var(--bg-color);
            color: var(--text-main);
            padding: 40px;
            min-height: 100vh;
            background-image: 
                radial-gradient(at 0% 0%, rgba(59, 130, 246, 0.12) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(16, 185, 129, 0.08) 0px, transparent 50%);
            background-attachment: fixed;
        }

        .container {
            max-width: 1300px;
            margin: 0 auto;
        }

        /* Header Style */
        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 35px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
        }

        .header-title h1 {
            font-size: 28px;
            font-weight: 700;
            background: linear-gradient(135deg, #fff 30%, #a5b4fc 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 5px;
        }

        .header-title p {
            color: var(--text-muted);
            font-size: 14px;
        }

        .company-badge {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            padding: 10px 18px;
            border-radius: 12px;
            text-align: right;
            backdrop-filter: blur(10px);
        }

        .company-name {
            font-weight: 600;
            font-size: 15px;
            color: #fff;
        }

        .company-date {
            font-size: 12px;
            color: var(--text-muted);
            margin-top: 2px;
        }

        /* Analytics Grid */
        .analytics-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 35px;
        }

        .analytics-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            padding: 24px;
            border-radius: 16px;
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(10px);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .analytics-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.2);
            border-color: rgba(59, 130, 246, 0.25);
        }

        .card-label {
            font-size: 13px;
            font-weight: 500;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .card-value {
            font-size: 26px;
            font-weight: 700;
            margin-top: 8px;
            color: #fff;
        }

        .card-subtext {
            font-size: 12px;
            color: var(--text-muted);
            margin-top: 6px;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .trend-up {
            color: var(--success);
            font-weight: 600;
        }

        .card-glow {
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100px;
            height: 100px;
            border-radius: 50%;
            filter: blur(50px);
            opacity: 0.15;
            pointer-events: none;
        }

        .glow-blue { background: var(--primary); }
        .glow-green { background: var(--success); }
        .glow-orange { background: var(--warning); }

        /* Search & Actions Bar */
        .toolbar {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            padding: 16px 20px;
            border-radius: 16px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            backdrop-filter: blur(10px);
        }

        .search-box {
            position: relative;
            width: 320px;
        }

        .search-input {
            width: 100%;
            background: rgba(10, 15, 30, 0.8);
            border: 1px solid var(--border-color);
            padding: 10px 16px 10px 38px;
            border-radius: 10px;
            color: #fff;
            font-family: var(--font-family);
            font-size: 13px;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px var(--primary-glow);
        }

        .search-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            pointer-events: none;
        }

        .filter-group {
            display: flex;
            gap: 12px;
        }

        .filter-select {
            background: rgba(10, 15, 30, 0.8);
            border: 1px solid var(--border-color);
            color: #fff;
            padding: 8px 14px;
            border-radius: 10px;
            font-family: var(--font-family);
            font-size: 13px;
            outline: none;
            cursor: pointer;
            transition: border 0.3s;
        }

        .filter-select:focus {
            border-color: var(--primary);
        }

        .btn-action {
            background: var(--primary);
            color: white;
            border: none;
            padding: 9px 16px;
            border-radius: 10px;
            font-family: var(--font-family);
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
        }

        .btn-action:hover {
            background: #2563eb;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        /* Table Card */
        .table-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            overflow: hidden;
            backdrop-filter: blur(10px);
        }

        .report-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        .report-table th {
            background: rgba(10, 15, 25, 0.6);
            color: var(--text-muted);
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 16px 20px;
            border-bottom: 1px solid var(--border-color);
        }

        .report-table td {
            padding: 16px 20px;
            border-bottom: 1px solid var(--border-color);
            font-size: 13.5px;
            color: var(--text-main);
            vertical-align: middle;
        }

        .report-table tr:last-child td {
            border-bottom: none;
        }

        .report-table tr:hover td {
            background: rgba(255, 255, 255, 0.015);
        }

        /* Badges */
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.3px;
        }

        .badge-erp {
            background: rgba(59, 130, 246, 0.15);
            color: #60a5fa;
            border: 1px solid rgba(59, 130, 246, 0.2);
        }

        .badge-wholesale {
            background: rgba(163, 163, 163, 0.15);
            color: #e5e7eb;
            border: 1px solid rgba(163, 163, 163, 0.2);
        }

        .badge-margin {
            background: rgba(16, 185, 129, 0.15);
            color: #34d399;
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .badge-margin-low {
            background: rgba(245, 158, 11, 0.15);
            color: #fbbf24;
            border: 1px solid rgba(245, 158, 11, 0.2);
        }

        /* Table Empty State */
        .empty-state {
            padding: 60px 40px;
            text-align: center;
            color: var(--text-muted);
        }

        .empty-icon {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.5;
        }

        .empty-title {
            font-size: 16px;
            font-weight: 600;
            color: #fff;
            margin-bottom: 5px;
        }

        .numeric {
            font-family: 'Courier New', monospace;
            text-align: right;
            font-weight: 500;
        }

        /* Print Styles */
        @media print {
            body {
                background: #white;
                color: #000;
                padding: 0;
            }
            .container {
                max-width: 100%;
            }
            .toolbar, .btn-action {
                display: none !important;
            }
            .analytics-card, .table-card {
                background: none !important;
                border: 1px solid #000 !important;
                color: #000 !important;
                backdrop-filter: none !important;
                box-shadow: none !important;
            }
            .card-value, .company-name, .header-title h1 {
                color: #000 !important;
                -webkit-text-fill-color: initial !important;
            }
            .report-table th {
                background: #f3f4f6 !important;
                color: #000 !important;
                border-bottom: 1px solid #000 !important;
            }
            .report-table td {
                color: #000 !important;
                border-bottom: 1px solid #ddd !important;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <!-- Header -->
    <div class="report-header">
        <div class="header-title">
            <h1>FIFO Profit & Margin Report</h1>
            <p>Real-time transaction log with exact cost-at-sale matching from First-In First-Out batches</p>
        </div>
        <div class="company-badge">
            <div class="company-name"><?= htmlspecialchars($data['company']->company_name ?? 'Curtiss-ERP') ?></div>
            <div class="company-date">Generated: <?= date('Y-m-d H:i:s') ?></div>
        </div>
    </div>

    <form method="GET" action="<?= APP_URL ?>/report/fifo_profit" style="display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end; margin-bottom:24px; padding:14px; background:var(--card-bg); border:1px solid var(--border-color); border-radius:12px;">
        <div><label style="font-size:11px;color:var(--text-muted);display:block;margin-bottom:4px;">FROM</label>
        <input type="date" name="start_date" value="<?= htmlspecialchars($data['start_date'] ?? '') ?>" style="padding:8px;border-radius:6px;border:1px solid var(--border-color);background:var(--bg-color);color:var(--text-main);"></div>
        <div><label style="font-size:11px;color:var(--text-muted);display:block;margin-bottom:4px;">TO</label>
        <input type="date" name="end_date" value="<?= htmlspecialchars($data['end_date'] ?? '') ?>" style="padding:8px;border-radius:6px;border:1px solid var(--border-color);background:var(--bg-color);color:var(--text-main);"></div>
        <button type="submit" style="padding:9px 18px;background:var(--primary);color:#fff;border:none;border-radius:6px;font-weight:600;cursor:pointer;">Apply</button>
        <a href="<?= APP_URL ?>/report" style="padding:9px 14px;color:var(--text-muted);text-decoration:none;font-size:13px;">← Hub</a>
    </form>

    <?php
    $totalRevenue = floatval($data['total_revenue'] ?? 0);
    $totalCost = floatval($data['total_cost'] ?? 0);
    $totalProfit = floatval($data['total_profit'] ?? 0);
    $transactionCount = count($data['invoices'] ?? []);
    $avgMargin = $totalRevenue > 0 ? ($totalProfit / $totalRevenue) * 100 : 0;
    ?>

    <!-- Analytics Dashboard Cards -->
    <div class="analytics-grid">
        <div class="analytics-card" id="card-revenue">
            <div class="card-glow glow-blue"></div>
            <div class="card-label">Total Revenue</div>
            <div class="card-value">Rs. <?= number_format($totalRevenue, 2) ?></div>
            <div class="card-subtext">Cumulative invoiced retail/wholesale turnover</div>
        </div>

        <div class="analytics-card" id="card-cogs">
            <div class="card-glow glow-orange"></div>
            <div class="card-label">Total FIFO COGS</div>
            <div class="card-value">Rs. <?= number_format($totalCost, 2) ?></div>
            <div class="card-subtext">Real cost dynamically attributed from batches</div>
        </div>

        <div class="analytics-card" id="card-profit">
            <div class="card-glow glow-green"></div>
            <div class="card-label">FIFO Net Profit</div>
            <div class="card-value" style="color: var(--success);">Rs. <?= number_format($totalProfit, 2) ?></div>
            <div class="card-subtext">
                <span class="trend-up">↑ 100%</span> true inventory margins
            </div>
        </div>

        <div class="analytics-card" id="card-margin">
            <div class="card-glow glow-blue"></div>
            <div class="card-label">Average Margin</div>
            <div class="card-value"><?= number_format($avgMargin, 1) ?>%</div>
            <div class="card-subtext">Weighted gross profit margin across sales</div>
        </div>
    </div>

    <!-- Toolbar Filters -->
    <div class="toolbar">
        <div class="search-box">
            <svg class="search-icon" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <input type="text" id="searchInput" class="search-input" onkeyup="filterReportTable()" placeholder="Search item description, invoice #...">
        </div>
        
        <div class="filter-group">
            <select id="channelFilter" class="filter-select" onchange="filterReportTable()">
                <option value="">All Channels</option>
                <option value="ERP Sales">ERP Sales</option>
                <option value="Wholesale POS">Wholesale POS</option>
            </select>
            <select id="marginFilter" class="filter-select" onchange="filterReportTable()">
                <option value="">All Margins</option>
                <option value="high">High (> 20%)</option>
                <option value="low">Low (<= 20%)</option>
            </select>
            <button class="btn-action" onclick="window.print()">
                <span>🖨️</span> Print Report
            </button>
        </div>
    </div>

    <!-- Table Details -->
    <div class="table-card">
        <table class="report-table" id="reportTable">
            <thead>
                <tr>
                    <th>Channel</th>
                    <th>Date</th>
                    <th>Invoice No</th>
                    <th>Product / Variation Name</th>
                    <th class="numeric">Qty</th>
                    <th class="numeric">Sale Price</th>
                    <th class="numeric">Revenue</th>
                    <th class="numeric">FIFO Cost</th>
                    <th class="numeric">Total Cost</th>
                    <th class="numeric">Net Profit</th>
                    <th style="text-align: center;">Margin</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($data['invoices'])): ?>
                    <tr>
                        <td colspan="11">
                            <div class="empty-state">
                                <div class="empty-icon">📊</div>
                                <div class="empty-title">No FIFO Sales Data Found</div>
                                <p>Begin processing inventory receipts and invoices to generate batch reports.</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($data['invoices'] as $inv): 
                        $qty = floatval($inv->qty);
                        $rev = floatval($inv->revenue);
                        $tcost = floatval($inv->total_cost);
                        $profit = floatval($inv->profit);
                        $margin = $rev > 0 ? ($profit / $rev) * 100 : 0;
                        ?>
                        <tr class="table-row" data-channel="<?= htmlspecialchars($inv->source) ?>" data-margin="<?= $margin > 20 ? 'high' : 'low' ?>">
                            <td>
                                <span class="badge <?= $inv->source === 'ERP Sales' ? 'badge-erp' : 'badge-wholesale' ?>">
                                    <?= htmlspecialchars($inv->source) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($inv->sale_date) ?></td>
                            <td><strong><?= htmlspecialchars($inv->invoice_number) ?></strong></td>
                            <td><?= htmlspecialchars($inv->item_name) ?></td>
                            <td class="numeric"><?= number_format($qty, 0) ?></td>
                            <td class="numeric">Rs. <?= number_format(floatval($inv->price), 2) ?></td>
                            <td class="numeric">Rs. <?= number_format($rev, 2) ?></td>
                            <td class="numeric" style="color: var(--text-muted);">Rs. <?= number_format(floatval($inv->unit_cost), 2) ?></td>
                            <td class="numeric" style="color: var(--text-muted);">Rs. <?= number_format($tcost, 2) ?></td>
                            <td class="numeric" style="color: var(--success); font-weight: 600;">Rs. <?= number_format($profit, 2) ?></td>
                            <td style="text-align: center;">
                                <span class="badge <?= $margin >= 20 ? 'badge-margin' : 'badge-margin-low' ?>">
                                    <?= number_format($margin, 1) ?>%
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    function filterReportTable() {
        const searchVal = document.getElementById('searchInput').value.toLowerCase();
        const channelVal = document.getElementById('channelFilter').value;
        const marginVal = document.getElementById('marginFilter').value;
        const rows = document.querySelectorAll('.table-row');

        rows.forEach(row => {
            const cells = row.getElementsByTagName('td');
            if (cells.length === 0) return;

            const matchesSearch = Array.from(cells).some(td => td.textContent.toLowerCase().includes(searchVal));
            const matchesChannel = !channelVal || row.getAttribute('data-channel') === channelVal;
            const matchesMargin = !marginVal || row.getAttribute('data-margin') === marginVal;

            if (matchesSearch && matchesChannel && matchesMargin) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }
</script>

</body>
</html>
