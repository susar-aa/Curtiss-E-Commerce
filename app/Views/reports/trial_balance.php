<?php
/* STREAMING_CHUNK:Trial Balance Printable View */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Trial Balance - <?= APP_NAME ?></title>
    <style>
        body { background: #f4f5f7; font-family: sans-serif; padding: 40px; color: #333; }
        .report-box { max-width: 800px; margin: auto; background: #fff; padding: 50px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .text-center { text-align: center; }
        .company-name { font-size: 24px; font-weight: bold; margin-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        td, th { padding: 10px; border-bottom: 1px solid #eee; text-align: left; }
        th { background: #f8f9fa; font-weight: bold; }
        .num { text-align: right; }
        .grand-total td { font-weight: bold; font-size: 16px; border-top: 2px solid #333; border-bottom: 4px double #333; }
        @media print { body { padding: 0; background: #fff; } .report-box { box-shadow: none; padding: 0; } .no-print { display: none !important; } }
    </style>
</head>
<body>
    <div class="no-print" style="max-width: 800px; margin: 0 auto 20px auto; display: flex; justify-content: space-between; align-items: center; font-family: sans-serif;">
        <a href="<?= APP_URL ?>/report" style="padding: 8px 16px; background: #64748b; color: #fff; text-decoration: none; border-radius: 4px; font-weight: bold; font-size: 14px;">&larr; Back to Hub</a>
        
        <form method="GET" action="" style="display: flex; align-items: center; gap: 10px; margin: 0;">
            <label style="font-weight: bold; font-size: 14px;">As of Date:</label>
            <input type="date" name="end_date" value="<?= htmlspecialchars($data['end_date'] ?? date('Y-m-d')) ?>" style="padding: 6px 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px;">
            <button type="submit" style="padding: 6px 12px; background: #0066cc; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 14px;">Update</button>
        </form>
    </div>

    <div class="report-box">
        <div class="text-center">
            <div class="company-name"><?= htmlspecialchars($data['company']->company_name) ?></div>
            <h2>Trial Balance</h2>
            <p>As of <?= date('F j, Y', strtotime($data['end_date'] ?? date('Y-m-d'))) ?></p>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Account Name</th>
                    <th class="num">Debit (Rs:)</th>
                    <th class="num">Credit (Rs:)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($data['tb_data'] as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['code']) ?></td>
                    <td><?= htmlspecialchars($row['name']) ?></td>
                    <td class="num"><?= $row['debit'] > 0 ? number_format($row['debit'], 2) : '-' ?></td>
                    <td class="num"><?= $row['credit'] > 0 ? number_format($row['credit'], 2) : '-' ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="grand-total">
                    <td colspan="2" class="num">Grand Total:</td>
                    <td class="num"><?= number_format($data['total_debit'], 2) ?></td>
                    <td class="num"><?= number_format($data['total_credit'], 2) ?></td>
                </tr>
            </tfoot>
        </table>
        
        <?php if(round($data['total_debit'], 2) === round($data['total_credit'], 2)): ?>
            <p style="text-align: center; color: #2e7d32; font-weight: bold; margin-top: 20px;">✓ The Trial Balance is balanced.</p>
        <?php endif; ?>
    </div>
    <div class="text-center" style="margin-top: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; cursor: pointer;">Print Report</button>
    </div>
</body>
</html>