<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Customer Account Statement - Curtiss ERP</title>
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
            margin-bottom: 25px;
        }
        .logo-section h2 {
            margin: 0;
            font-size: 26px;
            font-weight: 800;
            color: #1a1a1a;
        }
        .logo-section p {
            margin: 3px 0 0 0;
            font-size: 12px;
            color: #666;
        }
        .statement-title {
            text-align: right;
        }
        .statement-title h1 {
            margin: 0;
            font-size: 22px;
            color: #4f46e5;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-bottom: 30px;
        }
        .info-block h3 {
            margin: 0 0 8px 0;
            font-size: 12px;
            text-transform: uppercase;
            color: #777;
            letter-spacing: 0.5px;
        }
        .info-block p {
            margin: 0 0 4px 0;
        }
        .stats-summary {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 30px;
        }
        .stat-item {
            text-align: center;
        }
        .stat-label {
            font-size: 11px;
            text-transform: uppercase;
            color: #666;
            margin-bottom: 4px;
            font-weight: 600;
        }
        .stat-val {
            font-size: 18px;
            font-weight: 700;
            font-family: monospace;
        }
        .ledger-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 40px;
        }
        .ledger-table th, .ledger-table td {
            padding: 10px 12px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
            font-size: 13px;
        }
        .ledger-table th {
            background-color: #f3f4f6;
            font-weight: 700;
            color: #444;
            text-transform: uppercase;
            font-size: 10px;
            letter-spacing: 0.5px;
        }
        .ledger-table tr.credit-row td {
            color: #065f46;
        }
        .ledger-table tr.debit-row td {
            color: #1e293b;
        }
        .no-print-filter {
            background: #f3f4f6;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
        }
        .no-print-filter form {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .form-input {
            padding: 6px 10px;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            font-size: 13px;
        }
        @media print {
            .no-print { display: none !important; }
            body { margin: 15px; }
        }
    </style>
</head>
<body>
    <div class="no-print no-print-filter">
        <form method="GET">
            <label style="font-weight: 600; font-size:13px;">Date Period Filter:</label>
            <input type="date" name="start_date" class="form-input" value="<?= htmlspecialchars($data['start_date']) ?>">
            <span style="color:#666;">to</span>
            <input type="date" name="end_date" class="form-input" value="<?= htmlspecialchars($data['end_date']) ?>">
            <button type="submit" style="padding: 6px 12px; background: #4f46e5; color:#fff; border:none; border-radius:4px; font-weight:600; cursor:pointer;">
                Filter
            </button>
            <?php if ($data['start_date'] || $data['end_date']): ?>
                <a href="<?= APP_URL ?>/customerpayment/statement/<?= $data['customer']->id ?>" style="color: #4f46e5; text-decoration: none; font-size: 13px; font-weight: 600; margin-left: 10px;">Clear</a>
            <?php endif; ?>
        </form>
        <button onclick="window.print()" style="padding: 8px 16px; background: #10b981; color:#fff; border:none; border-radius:4px; font-weight:600; cursor:pointer;">
            🖨 Print Statement
        </button>
    </div>

    <div class="header">
        <div class="logo-section">
            <h2>CURTISS ENTERPRISES</h2>
            <p>123 Business Avenue, Colombo, Sri Lanka<br>Phone: +94 11 2345678 | Email: finance@curtiss.com</p>
        </div>
        <div class="statement-title">
            <h1>Customer Statement</h1>
            <p style="margin: 3px 0 0 0; font-size: 13px; color:#555;">Generated: <?= date('Y-m-d H:i') ?></p>
            <?php if ($data['start_date'] || $data['end_date']): ?>
                <p style="margin: 3px 0 0 0; font-size: 11px; font-weight: 700; color: #4f46e5;">
                    Period: <?= htmlspecialchars($data['start_date'] ?: 'Beginning') ?> to <?= htmlspecialchars($data['end_date'] ?: 'Present') ?>
                </p>
            <?php endif; ?>
        </div>
    </div>

    <div class="info-grid">
        <div class="info-block">
            <h3>Statement Account For</h3>
            <p style="font-size: 16px; font-weight: 700; color: #111; margin-bottom: 6px;"><?= htmlspecialchars($data['customer']->name) ?></p>
            <p><?= htmlspecialchars($data['customer']->address ?: 'No Address Stated') ?></p>
            <p>Phone: <?= htmlspecialchars($data['customer']->phone ?: 'N/A') ?></p>
            <p>Email: <?= htmlspecialchars($data['customer']->email ?: 'N/A') ?></p>
        </div>
        <div class="info-block" style="text-align: right;">
            <h3>Financial Summary</h3>
            <p>Credit Limit: <strong>Rs <?= number_format($data['customer']->credit_limit, 2) ?></strong></p>
            <p>Outstanding Balance: <strong style="color: #ef4444;">Rs <?= number_format($data['stats']->outstanding, 2) ?></strong></p>
            <p>Total Revenue Billed: <strong>Rs <?= number_format($data['stats']->total_invoiced, 2) ?></strong></p>
        </div>
    </div>

    <div class="stats-summary">
        <div class="stat-item">
            <div class="stat-label">Total Invoiced (Debits)</div>
            <div class="stat-val" style="color: #1e293b;">Rs <?= number_format($data['stats']->total_invoiced, 2) ?></div>
        </div>
        <div class="stat-item">
            <div class="stat-label">Total Collections (Credits)</div>
            <div class="stat-val" style="color: #059669;">Rs <?= number_format($data['stats']->total_paid, 2) ?></div>
        </div>
        <div class="stat-item">
            <div class="stat-label">Balance Due</div>
            <div class="stat-val" style="color: #dc2626;">Rs <?= number_format($data['stats']->outstanding, 2) ?></div>
        </div>
    </div>

    <table class="ledger-table">
        <thead>
            <tr>
                <th style="width: 100px;">Date</th>
                <th style="width: 100px;">Type</th>
                <th>Reference / Description</th>
                <th style="text-align: right; width: 130px;">Debit (+)</th>
                <th style="text-align: right; width: 130px;">Credit (-)</th>
                <th style="text-align: right; width: 140px;">Running Balance</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($data['ledger'])): ?>
                <tr>
                    <td colspan="6" style="text-align: center; color: #666; font-style: italic; padding: 25px;">
                        No transactions recorded in statement history.
                    </td>
                </tr>
            <?php else: foreach ($data['ledger'] as $row): ?>
                <tr class="<?= $row->debit > 0 ? 'debit-row' : 'credit-row' ?>">
                    <td><?= date('Y-m-d', strtotime($row->date)) ?></td>
                    <td><strong><?= $row->type ?></strong></td>
                    <td><?= htmlspecialchars($row->ref) ?></td>
                    <td style="text-align: right; font-family: monospace;">
                        <?= $row->debit > 0 ? 'Rs ' . number_format($row->debit, 2) : '-' ?>
                    </td>
                    <td style="text-align: right; font-family: monospace;">
                        <?= $row->credit > 0 ? 'Rs ' . number_format($row->credit, 2) : '-' ?>
                    </td>
                    <td style="text-align: right; font-family: monospace; font-weight: 700;">
                        Rs <?= number_format($row->balance, 2) ?>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>

    <div style="margin-top: 50px; border-top: 1px dashed #ccc; padding-top: 15px; text-align: center; font-size: 11px; color: #777;">
        This is a system generated document. For inquiries regarding outstanding balance reconciliation, contact finance@curtiss.com.
    </div>
</body>
</html>
