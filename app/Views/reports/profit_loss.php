<?php
/* STREAMING_CHUNK:P&L Printable View */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Profit & Loss - <?= APP_NAME ?></title>
    <style>
        body { background: #f4f5f7; font-family: sans-serif; padding: 40px; color: #333; }
        .report-box { max-width: 800px; margin: auto; background: #fff; padding: 50px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .text-center { text-align: center; }
        .company-name { font-size: 24px; font-weight: bold; margin-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        td { padding: 10px 0; border-bottom: 1px solid #eee; }
        .num { text-align: right; }
        .header-row { font-weight: bold; font-size: 16px; border-bottom: 2px solid #333; padding-top: 20px; }
        .total-row { font-weight: bold; padding-top: 15px; }
        .total-row td { border-bottom: 2px solid #333; }
        .net-income td { font-size: 18px; border-bottom: 4px double #333; color: <?= $data['net_income'] >= 0 ? '#2e7d32' : '#c62828' ?>; }
        @media print { body { padding: 0; background: #fff; } .report-box { box-shadow: none; padding: 0; } .no-print { display: none !important; } }
    </style>
</head>
<body>
    <div class="no-print" style="max-width: 800px; margin: 0 auto 20px auto; display: flex; justify-content: space-between; align-items: center; font-family: sans-serif;">
        <a href="<?= APP_URL ?>/report" style="padding: 8px 16px; background: #64748b; color: #fff; text-decoration: none; border-radius: 4px; font-weight: bold; font-size: 14px;">&larr; Back to Hub</a>
        
        <form method="GET" action="" style="display: flex; align-items: center; gap: 10px; margin: 0;">
            <label style="font-weight: bold; font-size: 14px;">From:</label>
            <input type="date" name="start_date" value="<?= htmlspecialchars($data['start_date'] ?? date('Y-01-01')) ?>" style="padding: 6px 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px;">
            <label style="font-weight: bold; font-size: 14px;">To:</label>
            <input type="date" name="end_date" value="<?= htmlspecialchars($data['end_date'] ?? date('Y-m-d')) ?>" style="padding: 6px 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px;">
            <button type="submit" style="padding: 6px 12px; background: #0066cc; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 14px;">Update</button>
        </form>
    </div>

    <div class="report-box">
        <div class="text-center">
            <div class="company-name"><?= htmlspecialchars($data['company']->company_name) ?></div>
            <h2>Profit and Loss Statement</h2>
            <p>From <?= date('F j, Y', strtotime($data['start_date'] ?? date('Y-01-01'))) ?> to <?= date('F j, Y', strtotime($data['end_date'] ?? date('Y-m-d'))) ?></p>
        </div>

        <table>
            <!-- REVENUE -->
            <tr><td colspan="2" class="header-row">Income</td></tr>
            <?php foreach($data['revenues'] as $acc): ?>
                <tr><td><?= htmlspecialchars($acc->account_name) ?></td><td class="num"><?= number_format($acc->balance, 2) ?></td></tr>
            <?php endforeach; ?>
            <tr class="total-row"><td>Total Income</td><td class="num">Rs: <?= number_format($data['total_revenue'], 2) ?></td></tr>

            <!-- EXPENSES -->
            <tr><td colspan="2" class="header-row" style="padding-top: 30px;">Expenses</td></tr>
            <?php foreach($data['expenses'] as $acc): ?>
                <tr><td><?= htmlspecialchars($acc->account_name) ?></td><td class="num"><?= number_format($acc->balance, 2) ?></td></tr>
            <?php endforeach; ?>
            <tr class="total-row"><td>Total Expenses</td><td class="num">Rs: <?= number_format($data['total_expense'], 2) ?></td></tr>

            <!-- NET INCOME -->
            <tr class="total-row net-income">
                <td style="padding-top: 30px;">Net Income (Profit/Loss)</td>
                <td class="num" style="padding-top: 30px;">Rs: <?= number_format($data['net_income'], 2) ?></td>
            </tr>
        </table>
    </div>
    <div class="text-center" style="margin-top: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; cursor: pointer;">Print Report</button>
    </div>
</body>
</html>