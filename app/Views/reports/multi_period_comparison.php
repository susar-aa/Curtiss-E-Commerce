<style>
    /* Styling & Theme Variables */
    .report-container {
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        color: var(--text-dark, #333);
        margin: 20px auto;
        max-width: 1000px;
    }
    .report-card {
        background: #fff;
        border: 1px solid var(--mac-border, #e2e8f0);
        border-radius: 12px;
        padding: 30px;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
        margin-bottom: 25px;
    }
    @media (prefers-color-scheme: dark) {
        .report-card { background: #1e1e2d; border-color: #2d2d3d; color: #f1f5f9; }
    }
    
    .report-header {
        text-align: center;
        margin-bottom: 25px;
        border-bottom: 1px solid var(--mac-border, #e2e8f0);
        padding-bottom: 15px;
    }
    @media (prefers-color-scheme: dark) {
        .report-header { border-color: #27272a; }
    }
    
    .company-name { font-size: 22px; font-weight: 800; color: #0066cc; margin-bottom: 5px; }
    .report-title { font-size: 18px; font-weight: 700; margin: 5px 0; }
    .report-date { font-size: 13px; color: #64748b; margin: 0; }
    
    /* Filters and Controls */
    .filter-bar {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        align-items: flex-end;
        background: rgba(0,0,0,0.02);
        padding: 15px 20px;
        border-radius: 10px;
        border: 1px solid var(--mac-border, #e2e8f0);
        margin-bottom: 20px;
    }
    @media (prefers-color-scheme: dark) {
        .filter-bar { background: rgba(255,255,255,0.02); border-color: #27272a; }
    }
    .filter-group { display: flex; flex-direction: column; gap: 5px; }
    .filter-group label { font-size: 11px; font-weight: bold; text-transform: uppercase; color: #64748b; }
    .form-control {
        padding: 8px 12px;
        border: 1px solid var(--mac-border, #e2e8f0);
        border-radius: 6px;
        background: transparent;
        color: inherit;
        font-size: 14px;
    }
    @media (prefers-color-scheme: dark) {
        .form-control { border-color: #3f3f46; background: #181825; }
    }
    
    /* Summary Cards */
    .summary-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 15px;
        margin-bottom: 25px;
    }
    .summary-mini-card {
        background: rgba(0,0,0,0.01);
        border: 1px solid var(--mac-border, #e2e8f0);
        border-radius: 8px;
        padding: 15px;
    }
    @media (prefers-color-scheme: dark) {
        .summary-mini-card { background: rgba(255,255,255,0.01); border-color: #2d2d3d; }
    }
    .card-label { font-size: 11px; font-weight: bold; text-transform: uppercase; color: #64748b; }
    .card-val { font-size: 20px; font-weight: 800; margin-top: 5px; font-family: monospace; }
    
    /* Table Styling */
    .comparison-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
    .comparison-table th, .comparison-table td { padding: 10px 12px; text-align: left; border-bottom: 1px solid var(--mac-border, #e2e8f0); font-size: 13px; }
    @media (prefers-color-scheme: dark) {
        .comparison-table th, .comparison-table td { border-color: #27272a; }
    }
    .comparison-table th { background: rgba(0,0,0,0.01); font-weight: 700; font-size: 11px; text-transform: uppercase; color: #64748b; }
    @media (prefers-color-scheme: dark) {
        .comparison-table th { background: rgba(255,255,255,0.01); }
    }
    .num { text-align: right !important; font-family: monospace; font-size: 14px; font-weight: 500; }
    .type-group-header { background: rgba(0,102,204,0.03); font-weight: bold; font-size: 14px; color: #0066cc; }
    @media (prefers-color-scheme: dark) {
        .type-group-header { background: rgba(59,130,246,0.03); color: #3b82f6; }
    }
    
    /* Change indicators */
    .change-up { color: #2e7d32; font-weight: bold; }
    .change-down { color: #c62828; font-weight: bold; }
    .change-flat { color: #64748b; }
    
    .actions-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .btn {
        padding: 9px 16px;
        background: #0066cc;
        color: #fff;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 13px;
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .btn:hover { background: #0056b3; }
    
    @media print {
        .glass-nav, .nav-back-btn, header, footer, .actions-bar, .filter-bar, .fs-overlay, .fs-inner, .fs-close, button {
            display: none !important;
        }
        body, .main-content, .report-container, .report-card {
            background: #fff !important;
            color: #000 !important;
            box-shadow: none !important;
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
            border: none !important;
        }
    }
</style>

<div class="report-container">
    <div class="actions-bar">
        <a href="<?= APP_URL ?>/report" class="btn" style="background:#64748b;">&larr; Back to Hub</a>
        <button onclick="window.print()" class="btn">🖨️ Print Report</button>
    </div>

    <!-- Filter Panel -->
    <form method="GET" action="<?= APP_URL ?>/report/viewer/multi_period_comparison" class="filter-bar">
        <div class="filter-group">
            <label>Comparison Type</label>
            <select name="comparison_type" class="form-control" style="width: 180px;">
                <option value="mom" <?= $data['comparison_type'] === 'mom' ? 'selected' : '' ?>>Month-over-Month (MoM)</option>
                <option value="yoy" <?= $data['comparison_type'] === 'yoy' ? 'selected' : '' ?>>Year-over-Year (YoY)</option>
            </select>
        </div>
        
        <div class="filter-group">
            <label>Base Start Date</label>
            <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($data['start_date']) ?>">
        </div>
        
        <div class="filter-group">
            <label>Base End Date</label>
            <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($data['end_date']) ?>">
        </div>
        
        <button type="submit" class="btn" style="height: 38px; align-self: flex-end;">🔍 Run Comparison</button>
    </form>

    <div class="report-card">
        <div class="report-header">
            <div class="company-name"><?= htmlspecialchars($data['company']->company_name) ?></div>
            <h2 class="report-title">Multi-Period Comparative Statement</h2>
            <p class="report-date">
                <strong>Base Period:</strong> <?= date('M d, Y', strtotime($data['start_date'])) ?> to <?= date('M d, Y', strtotime($data['end_date'])) ?><br>
                <strong>Comparison Period (<?= strtoupper($data['comparison_type']) ?>):</strong> <?= date('M d, Y', strtotime($data['comp_start_date'])) ?> to <?= date('M d, Y', strtotime($data['comp_end_date'])) ?>
            </p>
        </div>

        <?php
            // Calculate aggregate summaries for Revenue & Expense
            $baseRevenue = 0; $baseExpense = 0;
            $compRevenue = 0; $compExpense = 0;

            foreach ($data['comparison_data'] as $row) {
                if ($row['account_type'] === 'Revenue') {
                    $baseRevenue += $row['base_balance'];
                    $compRevenue += $row['comp_balance'];
                } elseif ($row['account_type'] === 'Expense') {
                    $baseExpense += $row['base_balance'];
                    $compExpense += $row['comp_balance'];
                }
            }

            $baseNet = $baseRevenue - $baseExpense;
            $compNet = $compRevenue - $compExpense;
            $netVariance = $baseNet - $compNet;
            $netPct = 0;
            if (round($compNet, 2) != 0.0) {
                $netPct = ($netVariance / abs($compNet)) * 100;
            } elseif (round($baseNet, 2) != 0.0) {
                $netPct = 100.0;
            }
        ?>

        <!-- Summary Statistics Grid -->
        <div class="summary-grid">
            <div class="summary-mini-card">
                <span class="card-label">Base Net Profit</span>
                <div class="card-val" style="color:#0066cc;">Rs <?= number_format($baseNet, 2) ?></div>
            </div>
            
            <div class="summary-mini-card">
                <span class="card-label">Comparison Net Profit</span>
                <div class="card-val" style="color:#64748b;">Rs <?= number_format($compNet, 2) ?></div>
            </div>
            
            <div class="summary-mini-card">
                <span class="card-label">Net Variance</span>
                <div class="card-val <?= $netVariance >= 0 ? 'change-up' : 'change-down' ?>">
                    Rs <?= ($netVariance >= 0 ? '+' : '') . number_format($netVariance, 2) ?>
                </div>
            </div>

            <div class="summary-mini-card">
                <span class="card-label">% Profit Variance</span>
                <div class="card-val <?= $netVariance >= 0 ? 'change-up' : 'change-down' ?>">
                    <?= ($netVariance >= 0 ? '▲ ' : '▼ ') . number_format($netPct, 1) ?>%
                </div>
            </div>
        </div>

        <!-- Search Filter -->
        <div style="margin-bottom: 15px; display: flex; justify-content: flex-end;">
            <input type="text" id="comparisonSearch" class="form-control" placeholder="Type to filter accounts..." onkeyup="filterComparisonTable()" style="width: 250px;">
        </div>

        <!-- Detailed Account Table -->
        <table class="comparison-table" id="comparisonTable">
            <thead>
                <tr>
                    <th style="width: 100px;">Code</th>
                    <th>Account Title</th>
                    <th class="num" style="width: 150px;">Base Balance</th>
                    <th class="num" style="width: 150px;">Comp Balance</th>
                    <th class="num" style="width: 140px;">Variance</th>
                    <th class="num" style="width: 100px;">% Change</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Group comparison data by category
                $grouped = [
                    'Asset' => [],
                    'Liability' => [],
                    'Equity' => [],
                    'Revenue' => [],
                    'Expense' => []
                ];
                foreach ($data['comparison_data'] as $row) {
                    if (isset($grouped[$row['account_type']])) {
                        $grouped[$row['account_type']][] = $row;
                    }
                }

                foreach ($grouped as $type => $rows):
                    if (empty($rows)) continue;
                ?>
                    <tr class="type-group-header">
                        <td colspan="6"><?= $type ?> Accounts</td>
                    </tr>
                    <?php 
                    $typeBaseSum = 0; $typeCompSum = 0;
                    foreach ($rows as $row): 
                        $typeBaseSum += $row['base_balance'];
                        $typeCompSum += $row['comp_balance'];
                        
                        // Variance formatting
                        $var = $row['variance'];
                        $varStr = number_format($var, 2);
                        $pctStr = number_format($row['pct_change'], 1) . '%';
                        
                        $changeClass = 'change-flat';
                        $changeSymbol = '';
                        if (round($var, 2) > 0.0) {
                            $changeClass = 'change-up';
                            $changeSymbol = '▲ ';
                        } elseif (round($var, 2) < 0.0) {
                            $changeClass = 'change-down';
                            $changeSymbol = '▼ ';
                        }
                    ?>
                        <tr class="account-row" data-search="<?= htmlspecialchars(strtolower($row['account_code'] . ' ' . $row['account_name'])) ?>">
                            <td style="font-weight: 700; color: #64748b;"><?= htmlspecialchars($row['account_code']) ?></td>
                            <td><?= htmlspecialchars($row['account_name']) ?></td>
                            <td class="num">Rs <?= number_format($row['base_balance'], 2) ?></td>
                            <td class="num">Rs <?= number_format($row['comp_balance'], 2) ?></td>
                            <td class="num <?= $changeClass ?>"><?= $changeSymbol . 'Rs ' . $varStr ?></td>
                            <td class="num <?= $changeClass ?>"><?= $pctStr ?></td>
                        </tr>
                    <?php endforeach; ?>
                    
                    <!-- Subtotal row for each type -->
                    <?php
                        $typeVar = $typeBaseSum - $typeCompSum;
                        $typePct = 0;
                        if (round($typeCompSum, 2) != 0.0) {
                            $typePct = ($typeVar / abs($typeCompSum)) * 100;
                        } elseif (round($typeBaseSum, 2) != 0.0) {
                            $typePct = 100.0;
                        }
                        
                        $typeChangeClass = 'change-flat';
                        if ($typeVar > 0) $typeChangeClass = 'change-up';
                        elseif ($typeVar < 0) $typeChangeClass = 'change-down';
                    ?>
                    <tr style="font-weight: bold; background: rgba(0,0,0,0.015);">
                        <td colspan="2">Total <?= $type ?>s</td>
                        <td class="num">Rs <?= number_format($typeBaseSum, 2) ?></td>
                        <td class="num">Rs <?= number_format($typeCompSum, 2) ?></td>
                        <td class="num <?= $typeChangeClass ?>">Rs <?= number_format($typeVar, 2) ?></td>
                        <td class="num <?= $typeChangeClass ?>"><?= number_format($typePct, 1) ?>%</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    function filterComparisonTable() {
        const input = document.getElementById('comparisonSearch');
        const filter = input.value.toLowerCase().trim();
        const table = document.getElementById('comparisonTable');
        const rows = table.getElementsByClassName('account-row');
        
        for (let i = 0; i < rows.length; i++) {
            const searchData = rows[i].getAttribute('data-search') || '';
            if (searchData.includes(filter)) {
                rows[i].style.display = "";
            } else {
                rows[i].style.display = "none";
            }
        }
    }
</script>
