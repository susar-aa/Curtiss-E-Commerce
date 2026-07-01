<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Receipt - Curtiss ERP</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #333;
            margin: 30px;
            font-size: 14px;
            line-height: 1.5;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .logo-section h2 {
            margin: 0;
            font-size: 26px;
            font-weight: 800;
            letter-spacing: 0.5px;
            color: #1a1a1a;
        }
        .logo-section p {
            margin: 3px 0 0 0;
            font-size: 12px;
            color: #666;
        }
        .receipt-title {
            text-align: right;
        }
        .receipt-title h1 {
            margin: 0;
            font-size: 24px;
            color: #4f46e5;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .receipt-title p {
            margin: 5px 0 0 0;
            font-family: monospace;
            font-size: 13px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-bottom: 30px;
        }
        .info-block h3 {
            margin: 0 0 8px 0;
            font-size: 13px;
            text-transform: uppercase;
            color: #777;
            letter-spacing: 0.5px;
        }
        .info-block p {
            margin: 0 0 4px 0;
            font-weight: 500;
        }
        .amount-box {
            background: #f3f4f6;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 15px 20px;
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .amount-box span.lbl {
            font-size: 13px;
            color: #666;
            text-transform: uppercase;
        }
        .amount-box span.val {
            color: #10b981;
            font-family: monospace;
            font-size: 22px;
        }
        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 40px;
        }
        .details-table th, .details-table td {
            padding: 10px 12px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        .details-table th {
            background-color: #f9fafb;
            font-weight: 600;
            color: #666;
            text-transform: uppercase;
            font-size: 11px;
        }
        .details-table td {
            font-size: 13px;
        }
        .signatures {
            margin-top: 60px;
            display: flex;
            justify-content: space-between;
        }
        .sig-block {
            text-align: center;
            width: 200px;
        }
        .sig-line {
            border-top: 1px solid #888;
            margin-top: 50px;
            padding-top: 8px;
            font-size: 12px;
            font-weight: 600;
            color: #555;
        }
        @media print {
            body { margin: 20px; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 20px; background: #e0e7ff; padding: 10px; border-radius: 6px; display: flex; justify-content: space-between; align-items: center;">
        <span style="font-weight:600; color:#4338ca;">Print Preview Mode</span>
        <button onclick="window.print()" style="padding: 6px 15px; background: #4f46e5; color:#fff; border:none; border-radius:4px; font-weight:600; cursor:pointer;">
            🖨 Print Receipt
        </button>
    </div>

    <div class="header">
        <div class="logo-section">
            <h2>CURTISS ENTERPRISES</h2>
            <p>123 Business Avenue, Colombo, Sri Lanka<br>Phone: +94 11 2345678 | Email: finance@curtiss.com</p>
        </div>
        <div class="receipt-title">
            <h1>Payment Receipt</h1>
            <p>Receipt #: REC-PAY-<?= str_pad($data['payment']->id, 5, '0', STR_PAD_LEFT) ?></p>
            <p style="margin-top:2px;">Date: <?= date('Y-m-d', strtotime($data['payment']->payment_date)) ?></p>
        </div>
    </div>

    <div class="info-grid">
        <div class="info-block">
            <h3>Received From</h3>
            <p style="font-size: 16px; color:#111; font-weight:700;"><?= htmlspecialchars($data['payment']->customer_name) ?></p>
            <p><?= htmlspecialchars($data['payment']->customer_address ?: 'No Address Stated') ?></p>
            <p>Phone: <?= htmlspecialchars($data['payment']->customer_phone ?: 'N/A') ?></p>
            <p>Email: <?= htmlspecialchars($data['payment']->customer_email ?: 'N/A') ?></p>
        </div>
        <div class="info-block" style="text-align: right;">
            <h3>Payment Information</h3>
            <p>Payment Method: <strong><?= $data['payment']->payment_method ?></strong></p>
            <?php if ($data['payment']->reference): ?>
                <p>Reference: <strong><?= htmlspecialchars($data['payment']->reference) ?></strong></p>
            <?php endif; ?>
            <p>Status: <strong style="color: <?= $data['payment']->status === 'Reversed' ? '#ef4444' : '#10b981' ?>;"><?= $data['payment']->status ?></strong></p>
            <p>Posted Entry: <strong>JE-<?= str_pad($data['payment']->journal_entry_id, 5, '0', STR_PAD_LEFT) ?></strong></p>
        </div>
    </div>

    <div class="amount-box">
        <span class="lbl">Amount Received</span>
        <span class="val">Rs: <?= number_format($data['payment']->amount, 2) ?></span>
    </div>

    <h3 style="font-size: 13px; text-transform: uppercase; color: #777; letter-spacing: 0.5px; margin-bottom: 12px;">
        Document Allocation Breakdown
    </h3>
    <table class="details-table">
        <thead>
            <tr>
                <th>Invoice Number</th>
                <th>Invoice Date</th>
                <th style="text-align: right;">Allocated Amount</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($data['allocations'])): ?>
                <tr>
                    <td colspan="3" style="text-align: center; color: #666; font-style: italic; padding: 20px;">
                        No direct invoice allocation. Paid as Advance Credit.
                    </td>
                </tr>
            <?php else: foreach ($data['allocations'] as $alloc): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($alloc->invoice_number) ?></strong></td>
                    <td><?= date('Y-m-d', strtotime($alloc->invoice_date)) ?></td>
                    <td style="text-align: right; font-weight: 700; font-family: monospace;">Rs: <?= number_format($alloc->amount, 2) ?></td>
                </tr>
            <?php endforeach; endif; ?>
            
            <?php if ($data['payment']->unallocated_amount > 0.01): ?>
                <tr style="background: #f9fafb;">
                    <td colspan="2" style="text-align: right; font-weight: 600; color: #666;">Remaining Advance Balance (Unallocated)</td>
                    <td style="text-align: right; font-weight: 700; font-family: monospace; color: #4f46e5;">Rs: <?= number_format($data['payment']->unallocated_amount, 2) ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if ($data['payment']->notes): ?>
        <div style="background: #fafafa; border: 1px solid #eee; padding: 12px; border-radius: 6px; margin-bottom: 30px;">
            <strong style="font-size:12px; text-transform:uppercase; color:#666;">Memo / Notes:</strong>
            <p style="margin: 5px 0 0 0; font-size:13px; color:#444;"><?= nl2br(htmlspecialchars($data['payment']->notes)) ?></p>
        </div>
    <?php endif; ?>

    <div class="signatures">
        <div class="sig-block">
            <div class="sig-line">Prepared By (<?= htmlspecialchars($data['payment']->creator_name ?: 'Accounts Officer') ?>)</div>
        </div>
        <div class="sig-block">
            <div class="sig-line">Authorized Signatory</div>
        </div>
    </div>
</body>
</html>
