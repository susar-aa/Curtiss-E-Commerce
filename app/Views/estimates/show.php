<?php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estimate <?= htmlspecialchars($data['estimate']->estimate_number) ?> - <?= APP_NAME ?></title>
    <style>
        body { background-color: #e5e7eb; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; margin: 0; padding: 40px 20px; color: #333; }
        .invoice-box { max-width: 800px; margin: auto; padding: 50px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1); font-size: 15px; line-height: 24px; background: #fff; border-radius: 8px; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 40px; }
        .header-left { max-width: 50%; }
        .header-right { text-align: right; }
        .logo { max-height: 80px; max-width: 200px; object-fit: contain; margin-bottom: 15px; }
        .company-name { font-size: 24px; font-weight: bold; margin: 0 0 5px 0; color: #111; }
        .company-details { font-size: 13px; color: #666; }
        .invoice-title { font-size: 36px; font-weight: 300; color: #666; margin: 0 0 10px 0; letter-spacing: 1px; }
        .invoice-meta { font-size: 14px; color: #555; }
        .invoice-meta strong { color: #333; display: inline-block; width: 100px; }
        .billing-section { display: flex; justify-content: space-between; margin-bottom: 40px; padding-top: 20px; border-top: 2px solid #f4f5f7; }
        .bill-to h4 { margin: 0 0 10px 0; color: #888; text-transform: uppercase; font-size: 12px; letter-spacing: 1px; }
        .bill-to p { margin: 0; }
        table.items { width: 100%; line-height: inherit; text-align: left; border-collapse: collapse; margin-bottom: 30px; }
        table.items th { background: #f8f9fa; padding: 12px; border-bottom: 2px solid #ddd; font-weight: 600; color: #444; font-size: 14px; }
        table.items td { padding: 12px; border-bottom: 1px solid #eee; }
        table.items th.num, table.items td.num { text-align: right; }
        .totals-section { width: 100%; display: flex; justify-content: flex-end; }
        .totals-box { width: 300px; }
        .totals-row { display: flex; justify-content: space-between; padding: 8px 0; font-size: 15px; }
        .totals-row.grand-total { border-top: 2px solid #333; font-weight: bold; font-size: 18px; padding-top: 12px; margin-top: 5px;}
        .controls { text-align: center; margin-bottom: 30px; }
        .btn { padding: 10px 20px; background: #0066cc; color: #fff; text-decoration: none; border-radius: 4px; font-weight: 500; cursor: pointer; border: none; margin: 0 5px; }
        .btn-outline { background: transparent; border: 1px solid #0066cc; color: #0066cc; }
        @media print { body { background: #fff; padding: 0; margin: 0; -webkit-print-color-adjust: exact; } .invoice-box { box-shadow: none; border: none; padding: 0; max-width: 100%; border-radius: 0; } .controls { display: none; } }
    </style>
</head>
<body>
    <div class="controls">
        <a href="<?= APP_URL ?>/estimate" class="btn btn-outline">&larr; Back to Estimates</a>
        <button onclick="window.print()" class="btn">Print / Save as PDF</button>
    </div>
    <div class="invoice-box">
        <div class="header">
            <div class="header-left">
                <?php if(!empty($data['company']->logo_path)): ?>
                    <img src="<?= APP_URL ?>/uploads/<?= htmlspecialchars($data['company']->logo_path) ?>" class="logo">
                <?php else: ?>
                    <h2 class="company-name"><?= htmlspecialchars($data['company']->company_name) ?></h2>
                <?php endif; ?>
                <div class="company-details">
                    <?php if(!empty($data['company']->address)) echo nl2br(htmlspecialchars($data['company']->address)) . '<br>'; ?>
                    <?php if(!empty($data['company']->phone)) echo htmlspecialchars($data['company']->phone) . '<br>'; ?>
                    <?php if(!empty($data['company']->email)) echo htmlspecialchars($data['company']->email); ?>
                </div>
            </div>
            <div class="header-right">
                <h1 class="invoice-title">ESTIMATE</h1>
                <div class="invoice-meta">
                    <div><strong>Estimate No:</strong> <?= htmlspecialchars($data['estimate']->estimate_number) ?></div>
                    <div><strong>Date:</strong> <?= date('F j, Y', strtotime($data['estimate']->estimate_date)) ?></div>
                    <div><strong>Valid Until:</strong> <?= date('F j, Y', strtotime($data['estimate']->expiry_date)) ?></div>
                </div>
            </div>
        </div>
        <div class="billing-section">
            <div class="bill-to">
                <h4>Estimate For:</h4>
                <p><strong><?= htmlspecialchars($data['estimate']->customer_name) ?></strong><br>
                <?php if(!empty($data['estimate']->address)) echo nl2br(htmlspecialchars($data['estimate']->address)) . '<br>'; ?>
                <?php if(!empty($data['estimate']->email)) echo htmlspecialchars($data['estimate']->email); ?></p>
            </div>
        </div>
        <table class="items">
            <thead>
                <tr>
                    <th style="width:40px;">#</th>
                    <th>Description</th>
                    <th class="num" style="width: 10%;">Qty</th>
                    <th class="num" style="width: 20%;">Unit Price</th>
                    <th class="num" style="width: 20%;">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php $rowNum = 1; foreach($data['items'] as $item): ?>
                <tr>
                    <td style="text-align:center; color:#888;"><?= $rowNum++ ?></td>
                    <td><?= htmlspecialchars($item->description) ?></td>
                    <td class="num"><?= number_format($item->quantity, 0) ?></td>
                    <td class="num">Rs: <?= number_format($item->unit_price, 2) ?></td>
                    <td class="num">Rs: <?= number_format($item->total, 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="totals-section">
            <div class="totals-box">
                <div class="totals-row grand-total">
                    <span>Estimated Total:</span>
                    <span>Rs: <?= number_format($data['estimate']->total_amount, 2) ?></span>
                </div>
            </div>
        </div>
        <div style="margin-top: 60px; border-top: 1px solid #eee; padding-top: 20px; font-size: 12px; color: #888; text-align: center;">
            This is an estimate, not a contract or bill. Prices are valid until <?= date('F j, Y', strtotime($data['estimate']->expiry_date)) ?>.
        </div>
    </div>
</body>
</html>