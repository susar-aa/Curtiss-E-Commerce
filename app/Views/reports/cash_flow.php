<style>
    /* Styling & Theme Variables */
    .report-container {
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        color: var(--text-dark, #333);
        margin: 20px auto;
        max-width: 900px;
    }
    .report-card {
        background: #fff;
        border: 1px solid var(--mac-border, #e2e8f0);
        border-radius: 12px;
        padding: 40px;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
        position: relative;
    }
    @media (prefers-color-scheme: dark) {
        .report-card { background: #1e1e2d; border-color: #2d2d3d; color: #f1f5f9; }
    }
    
    .report-header {
        text-align: center;
        margin-bottom: 30px;
        border-bottom: 1px solid var(--mac-border, #e2e8f0);
        padding-bottom: 20px;
    }
    @media (prefers-color-scheme: dark) {
        .report-header { border-color: #27272a; }
    }
    
    .company-name { font-size: 24px; font-weight: 800; color: #0066cc; margin-bottom: 5px; }
    .report-title { font-size: 20px; font-weight: 700; margin: 5px 0; }
    .report-date { font-size: 14px; color: #64748b; margin: 0; }
    
    .report-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    .report-table td { padding: 12px 0; border-bottom: 1px solid var(--mac-border, #e2e8f0); font-size: 14px; }
    @media (prefers-color-scheme: dark) {
        .report-table td { border-color: #27272a; }
    }
    
    .num { text-align: right !important; font-family: monospace; font-size: 15px; font-weight: bold; }
    .header-row { font-weight: bold; font-size: 16px; border-bottom: 2px solid var(--text-dark, #333); padding-top: 25px; color: #0066cc; }
    @media (prefers-color-scheme: dark) {
        .header-row { border-bottom-color: #52525b; color: #3b82f6; }
    }
    .sub-row { padding-left: 20px; color: #475569; }
    @media (prefers-color-scheme: dark) { .sub-row { color: #94a3b8; } }
    
    .total-row { font-weight: bold; background: rgba(0,0,0,0.01); }
    .total-row td { border-bottom: 2px solid var(--text-dark, #333); font-size: 15px; }
    @media (prefers-color-scheme: dark) {
        .total-row td { border-bottom-color: #52525b; }
    }
    
    .grand-total td { font-size: 16px; border-bottom: 4px double #0066cc !important; color: #0066cc; }
    @media (prefers-color-scheme: dark) {
        .grand-total td { border-bottom-color: #3b82f6 !important; color: #3b82f6; }
    }
    .grand-total-success td { font-size: 18px; border-bottom: 4px double #2e7d32 !important; color: #2e7d32; }
    @media (prefers-color-scheme: dark) {
        .grand-total-success td { border-bottom-color: #10b981 !important; color: #10b981; }
    }
    
    .actions-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .btn {
        padding: 10px 20px;
        background: #0066cc;
        color: #fff;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    .btn:hover { background: #0056b3; }
    
    @media print {
        .glass-nav, .nav-back-btn, header, footer, .actions-bar, .fs-overlay, .fs-inner, .fs-close, button {
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
        <form method="GET" action="" style="display: inline-flex; align-items: center; gap: 10px; margin: 0;">
            <label style="font-weight: 600; font-size: 14px;">End Date:</label>
            <input type="date" name="end_date" value="<?= htmlspecialchars($data['end_date'] ?? date('Y-m-d')) ?>" style="padding: 6px 12px; border: 1px solid var(--mac-border, #e2e8f0); border-radius: 6px; background: transparent; color: inherit; font-size: 14px;">
            <button type="submit" class="btn" style="padding: 6px 15px; font-size: 14px;">Update</button>
        </form>
        <button onclick="window.print()" class="btn">🖨️ Print Statement</button>
    </div>

    <div class="report-card">
        <div class="report-header">
            <div class="company-name"><?= htmlspecialchars($data['company']->company_name) ?></div>
            <h2 class="report-title">Statement of Cash Flows</h2>
            <p class="report-date">For the period ending <?= date('F j, Y', strtotime($data['end_date'] ?? date('Y-m-d'))) ?></p>
        </div>

        <?php 
            $opTotal = $data['net_income'];
            foreach($data['operating'] as $op) { $opTotal += $op['amount']; }
            
            $invTotal = 0;
            foreach($data['investing'] as $inv) { $invTotal += $inv['amount']; }
            
            $finTotal = 0;
            foreach($data['financing'] as $fin) { $finTotal += $fin['amount']; }
            
            $netCashIncrease = $opTotal + $invTotal + $finTotal;
        ?>

        <table class="report-table">
            <!-- OPERATING ACTIVITIES -->
            <tr><td colspan="2" class="header-row">Cash Flows from Operating Activities</td></tr>
            <tr>
                <td style="padding-left: 15px;">Net Income (from P&L)</td>
                <td class="num">Rs <?= number_format($data['net_income'], 2) ?></td>
            </tr>
            <?php foreach($data['operating'] as $op): ?>
                <tr>
                    <td class="sub-row" style="padding-left: 30px;"><?= htmlspecialchars($op['name']) ?></td>
                    <td class="num">Rs <?= number_format($op['amount'], 2) ?></td>
                </tr>
            <?php endforeach; ?>
            <tr class="total-row">
                <td>Net Cash from Operating Activities</td>
                <td class="num">Rs <?= number_format($opTotal, 2) ?></td>
            </tr>

            <!-- INVESTING ACTIVITIES -->
            <tr><td colspan="2" class="header-row" style="padding-top: 30px;">Cash Flows from Investing Activities</td></tr>
            <?php if(empty($data['investing'])): ?>
                <tr>
                    <td class="sub-row" style="padding-left: 30px; font-style: italic;">No investing transactions in period</td>
                    <td class="num">Rs 0.00</td>
                </tr>
            <?php else: foreach($data['investing'] as $inv): ?>
                <tr>
                    <td class="sub-row" style="padding-left: 30px;"><?= htmlspecialchars($inv['name']) ?></td>
                    <td class="num">Rs <?= number_format($inv['amount'], 2) ?></td>
                </tr>
            <?php endforeach; endif; ?>
            <tr class="total-row">
                <td>Net Cash from Investing Activities</td>
                <td class="num">Rs <?= number_format($invTotal, 2) ?></td>
            </tr>

            <!-- FINANCING ACTIVITIES -->
            <tr><td colspan="2" class="header-row" style="padding-top: 30px;">Cash Flows from Financing Activities</td></tr>
            <?php if(empty($data['financing'])): ?>
                <tr>
                    <td class="sub-row" style="padding-left: 30px; font-style: italic;">No financing transactions in period</td>
                    <td class="num">Rs 0.00</td>
                </tr>
            <?php else: foreach($data['financing'] as $fin): ?>
                <tr>
                    <td class="sub-row" style="padding-left: 30px;"><?= htmlspecialchars($fin['name']) ?></td>
                    <td class="num">Rs <?= number_format($fin['amount'], 2) ?></td>
                </tr>
            <?php endforeach; endif; ?>
            <tr class="total-row">
                <td>Net Cash from Financing Activities</td>
                <td class="num">Rs <?= number_format($finTotal, 2) ?></td>
            </tr>

            <!-- GRAND TOTALS -->
            <tr class="grand-total" style="height: 60px;">
                <td style="padding-top: 25px; font-weight: bold;">Net Increase (Decrease) in Cash</td>
                <td class="num" style="padding-top: 25px;">Rs <?= number_format($netCashIncrease, 2) ?></td>
            </tr>
            <tr class="grand-total-success" style="height: 60px;">
                <td style="padding-top: 15px; font-weight: bold;">Ending Cash & Bank Balance</td>
                <td class="num" style="padding-top: 15px;">Rs <?= number_format($data['ending_cash'], 2) ?></td>
            </tr>
        </table>
    </div>
</div>