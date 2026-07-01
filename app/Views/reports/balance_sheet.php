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
    
    .total-row { font-weight: bold; background: rgba(0,0,0,0.01); }
    .total-row td { border-bottom: 2px solid var(--text-dark, #333); font-size: 15px; }
    @media (prefers-color-scheme: dark) {
        .total-row td { border-bottom-color: #52525b; }
    }
    
    .grand-total td { font-size: 16px; border-bottom: 4px double #0066cc !important; color: #0066cc; }
    @media (prefers-color-scheme: dark) {
        .grand-total td { border-bottom-color: #3b82f6 !important; color: #3b82f6; }
    }
    
    .status-alert {
        padding: 15px;
        border-radius: 8px;
        text-align: center;
        font-weight: bold;
        margin-top: 30px;
    }
    .status-ok { background: #e8f5e9; color: #2e7d32; border: 1px solid #a5d6a7; }
    .status-warn { background: #ffebee; color: #c62828; border: 1px solid #ef9a9a; }
    
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
            <label style="font-weight: 600; font-size: 14px;">As of Date:</label>
            <input type="date" name="end_date" value="<?= htmlspecialchars($data['end_date'] ?? date('Y-m-d')) ?>" style="padding: 6px 12px; border: 1px solid var(--mac-border, #e2e8f0); border-radius: 6px; background: transparent; color: inherit; font-size: 14px;">
            <button type="submit" class="btn" style="padding: 6px 15px; font-size: 14px;">Update</button>
        </form>
        <button onclick="window.print()" class="btn">🖨️ Print Statement</button>
    </div>

    <div class="report-card">
        <div class="report-header">
            <div class="company-name"><?= htmlspecialchars($data['company']->company_name) ?></div>
            <h2 class="report-title">Balance Sheet</h2>
            <p class="report-date">As of <?= date('F j, Y', strtotime($data['end_date'] ?? date('Y-m-d'))) ?></p>
        </div>

        <table class="report-table">
            <!-- ASSETS -->
            <tr><td colspan="2" class="header-row">Assets</td></tr>
            <?php foreach($data['assets'] as $acc): ?>
                <tr>
                    <td style="padding-left: 15px;"><?= htmlspecialchars($acc->account_name) ?> (GL-<?= htmlspecialchars($acc->account_code) ?>)</td>
                    <td class="num">Rs <?= number_format($acc->balance, 2) ?></td>
                </tr>
            <?php endforeach; ?>
            <tr class="total-row grand-total">
                <td>Total Assets</td>
                <td class="num">Rs <?= number_format($data['total_assets'], 2) ?></td>
            </tr>

            <!-- LIABILITIES -->
            <tr><td colspan="2" class="header-row" style="padding-top: 30px;">Liabilities</td></tr>
            <?php foreach($data['liabilities'] as $acc): ?>
                <tr>
                    <td style="padding-left: 15px;"><?= htmlspecialchars($acc->account_name) ?> (GL-<?= htmlspecialchars($acc->account_code) ?>)</td>
                    <td class="num">Rs <?= number_format($acc->balance, 2) ?></td>
                </tr>
            <?php endforeach; ?>
            <tr class="total-row">
                <td>Total Liabilities</td>
                <td class="num">Rs <?= number_format($data['total_liabilities'], 2) ?></td>
            </tr>

            <!-- EQUITY -->
            <tr><td colspan="2" class="header-row" style="padding-top: 30px;">Equity</td></tr>
            <?php foreach($data['equities'] as $acc): ?>
                <tr>
                    <td style="padding-left: 15px;"><?= htmlspecialchars($acc->account_name) ?> (GL-<?= htmlspecialchars($acc->account_code) ?>)</td>
                    <td class="num">Rs <?= number_format($acc->balance, 2) ?></td>
                </tr>
            <?php endforeach; ?>
            <tr>
                <td style="padding-left: 15px; font-style: italic;">Net Income (Current Period P&L)</td>
                <td class="num" style="font-style: italic;">Rs <?= number_format($data['net_income'], 2) ?></td>
            </tr>
            <tr class="total-row">
                <td>Total Equity</td>
                <td class="num">Rs <?= number_format($data['total_equity'], 2) ?></td>
            </tr>

            <!-- TOTAL LIABILITIES & EQUITY -->
            <tr class="total-row grand-total">
                <td style="padding-top: 30px;">Total Liabilities & Equity</td>
                <td class="num" style="padding-top: 30px;">Rs <?= number_format($data['total_liabilities_equity'], 2) ?></td>
            </tr>
        </table>
        
        <?php if(round($data['total_assets'], 2) === round($data['total_liabilities_equity'], 2)): ?>
            <div class="status-alert status-ok">
                ✓ Balance Sheet Integrity Check Passed: Assets = Liabilities + Equity
            </div>
        <?php else: ?>
            <div class="status-alert status-warn">
                ⚠ Balance Sheet integrity warning: Out of balance by Rs <?= number_format(abs($data['total_assets'] - $data['total_liabilities_equity']), 2) ?>
            </div>
        <?php endif; ?>
    </div>
</div>