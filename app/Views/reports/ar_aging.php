<?php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>A/R Aging Summary - <?= APP_NAME ?></title>
    <style>
        body { background: #f4f5f7; font-family: sans-serif; padding: 40px; color: #333; }
        .report-box { max-width: 1000px; margin: auto; background: #fff; padding: 50px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .text-center { text-align: center; }
        .company-name { font-size: 24px; font-weight: bold; margin-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 30px; font-size: 14px; }
        td, th { padding: 12px; border-bottom: 1px solid #eee; text-align: right; }
        th { background: #f8f9fa; font-weight: bold; color: #555; }
        th:first-child, td:first-child { text-align: left; font-weight: bold; color: #111;}
        .grand-total td { font-weight: bold; font-size: 16px; border-top: 2px solid #333; border-bottom: 4px double #333; color: #0066cc;}
        .danger { color: #c62828; font-weight: bold; }
        @media print { body { padding: 0; background: #fff; } .report-box { box-shadow: none; padding: 0; max-width: 100%; } }
    </style>
</head>
<body>
    <div class="report-box">
        <div class="text-center">
            <div class="company-name"><?= htmlspecialchars($data['company']->company_name) ?></div>
            <h2>A/R Aging Summary</h2>
            <p>As of <?= date('F j, Y') ?></p>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Customer Name</th>
                    <th>Current</th>
                    <th>1 - 30 Days</th>
                    <th>31 - 60 Days</th>
                    <th>61 - 90 Days</th>
                    <th>> 90 Days</th>
                    <th>Total Owed</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($data['aging_data'])): ?>
                <tr>
                    <td colspan="7" style="text-align: center; font-weight: normal; color: #888; padding: 40px;">No unpaid invoices found. Excellent!</td>
                </tr>
                <?php else: foreach($data['aging_data'] as $customer => $buckets): ?>
                <tr>
                    <td><?= htmlspecialchars($customer) ?></td>
                    <td><?= $buckets['current'] > 0 ? number_format($buckets['current'], 2) : '-' ?></td>
                    <td><?= $buckets['thirty'] > 0 ? number_format($buckets['thirty'], 2) : '-' ?></td>
                    <td class="<?= $buckets['sixty'] > 0 ? 'danger' : '' ?>"><?= $buckets['sixty'] > 0 ? number_format($buckets['sixty'], 2) : '-' ?></td>
                    <td class="<?= $buckets['ninety'] > 0 ? 'danger' : '' ?>"><?= $buckets['ninety'] > 0 ? number_format($buckets['ninety'], 2) : '-' ?></td>
                    <td class="<?= $buckets['older'] > 0 ? 'danger' : '' ?>"><?= $buckets['older'] > 0 ? number_format($buckets['older'], 2) : '-' ?></td>
                    <td style="font-weight: bold;"><?= number_format($buckets['total'], 2) ?></td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
            <tfoot>
                <tr class="grand-total">
                    <td>Grand Total:</td>
                    <td><?= number_format($data['totals']['current'], 2) ?></td>
                    <td><?= number_format($data['totals']['thirty'], 2) ?></td>
                    <td><?= number_format($data['totals']['sixty'], 2) ?></td>
                    <td><?= number_format($data['totals']['ninety'], 2) ?></td>
                    <td><?= number_format($data['totals']['older'], 2) ?></td>
                    <td>Rs: <?= number_format($data['totals']['total'], 2) ?></td>
                </tr>
            </tfoot>
        </table>

        <?php if (!empty($data['invoice_detail'])): ?>
        <h3 style="margin-top: 40px; font-size: 16px; color: #333;">Open Invoice Detail</h3>
        <table>
            <thead>
                <tr>
                    <th>Invoice #</th>
                    <th>Customer</th>
                    <th>Invoice Date</th>
                    <th>Due Date</th>
                    <th>Days Overdue</th>
                    <th>Status</th>
                    <th>Amount (Rs)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data['invoice_detail'] as $inv): ?>
                <tr>
                    <td><?= htmlspecialchars($inv->invoice_number) ?></td>
                    <td><?= htmlspecialchars($inv->customer_name) ?></td>
                    <td><?= date('M j, Y', strtotime($inv->invoice_date)) ?></td>
                    <td><?= date('M j, Y', strtotime($inv->due_date)) ?></td>
                    <td class="<?= $inv->days_overdue > 30 ? 'danger' : '' ?>"><?= intval($inv->days_overdue) ?></td>
                    <td><?= htmlspecialchars($inv->status) ?></td>
                    <td style="text-align: right;"><?= number_format($inv->total_amount, 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
    <div class="text-center" style="margin-top: 20px;">
        <a href="<?= APP_URL ?>/report" style="padding: 10px 20px; background: #666; color: #fff; text-decoration: none; border-radius: 4px; margin-right: 8px;">← Reports Hub</a>
        <button onclick="window.print()" style="padding: 10px 20px; cursor: pointer; background: #0066cc; color: #fff; border: none; border-radius: 4px;">Print Report</button>
    </div>
</body>
</html>