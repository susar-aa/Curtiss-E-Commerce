<style>
    .invoice-card { background: #fff; border: 1px solid var(--mac-border); border-radius: 8px; padding: 40px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); color: #1d1d1f; max-width: 900px; margin: 0 auto; }
    .invoice-header { display: flex; justify-content: space-between; border-bottom: 2px solid #e1e1e3; padding-bottom: 20px; margin-bottom: 30px; }
    .company-logo { font-size: 24px; font-weight: bold; color: #c62828; margin-bottom: 5px; }
    .company-details { font-size: 13px; color: #515154; line-height: 1.5; }
    .invoice-title { font-size: 28px; font-weight: bold; color: #c62828; text-align: right; margin-bottom: 10px; }
    .invoice-meta { font-size: 13px; text-align: right; color: #515154; line-height: 1.6; }
    .bill-to { margin-bottom: 30px; font-size: 13px; line-height: 1.6; }
    .bill-to-title { font-weight: bold; color: #86868b; text-transform: uppercase; margin-bottom: 8px; font-size: 11px; }
    .bill-to-name { font-size: 16px; font-weight: bold; color: #1d1d1f; margin-bottom: 4px; }
    .item-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; font-size: 13px; }
    .item-table th, .item-table td { padding: 12px 10px; border-bottom: 1px solid #e1e1e3; }
    .item-table th { background: #f5f5f7; text-align: left; font-weight: 600; color: #1d1d1f; }
    .item-table td { color: #515154; }
    .total-section { float: right; width: 300px; margin-top: 10px; font-size: 13px; line-height: 1.8; }
    .total-row { display: flex; justify-content: space-between; padding: 5px 0; }
    .grand-total { font-size: 18px; font-weight: bold; color: #c62828; border-top: 1.5px solid #e1e1e3; padding-top: 10px; margin-top: 5px; }
    .footer-note { clear: both; margin-top: 60px; text-align: center; font-size: 12px; color: #86868b; border-top: 1px solid #e1e1e3; padding-top: 20px; }
    .print-actions { display: flex; justify-content: space-between; max-width: 900px; margin: 0 auto 20px auto; }
    .btn { padding: 8px 16px; background: #c62828; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; text-decoration: none; font-size: 13px; }
    .btn-secondary { background: #666; }

    @media print {
        body { background: #fff !important; color: #000 !important; }
        .mac-menubar, .mac-sidebar, .print-actions, .btn, hr, header, footer { display: none !important; }
        .invoice-card { border: none !important; box-shadow: none !important; padding: 0 !important; max-width: 100% !important; margin: 0 !important; }
        .item-table th { background: #f0f0f2 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    }
</style>

<div class="print-actions">
    <a href="<?= APP_URL ?>/creditnote" class="btn btn-secondary">&larr; Back to Credit Notes</a>
    <button onclick="window.print()" class="btn">🖨️ Print Credit Note</button>
</div>

<div class="invoice-card">
    <div class="invoice-header">
        <div>
            <div class="company-logo"> <?= htmlspecialchars($data['company']->company_name ?? 'Curtiss ERP') ?></div>
            <div class="company-details">
                <?= htmlspecialchars($data['company']->address ?? 'No. 100, Galle Road') ?><br>
                Colombo, Sri Lanka<br>
                Phone: <?= htmlspecialchars($data['company']->phone ?? '+94 11 234 5678') ?><br>
                Email: <?= htmlspecialchars($data['company']->email ?? 'billing@curtisserp.com') ?>
            </div>
        </div>
        <div>
            <div class="invoice-title">Credit Note</div>
            <div class="invoice-meta">
                <strong>CN Number:</strong> <?= htmlspecialchars($data['credit_note']->credit_note_number) ?><br>
                <strong>Date Issued:</strong> <?= date('Y-m-d', strtotime($data['credit_note']->note_date)) ?><br>
                <strong>Status:</strong> <span style="color:#2e7d32; font-weight:bold;"><?= htmlspecialchars($data['credit_note']->status) ?></span>
            </div>
        </div>
    </div>

    <div class="bill-to">
        <div class="bill-to-title">Customer / Client Information</div>
        <div class="bill-to-name"><?= htmlspecialchars($data['credit_note']->customer_name) ?></div>
        <?php if(!empty($data['credit_note']->address)): ?>
            <?= nl2br(htmlspecialchars($data['credit_note']->address)) ?><br>
        <?php endif; ?>
        <?php if(!empty($data['credit_note']->phone)): ?>
            <strong>Phone:</strong> <?= htmlspecialchars($data['credit_note']->phone) ?><br>
        <?php endif; ?>
        <?php if(!empty($data['credit_note']->email)): ?>
            <strong>Email:</strong> <?= htmlspecialchars($data['credit_note']->email) ?><br>
        <?php endif; ?>
    </div>

    <table class="item-table">
        <thead>
            <tr>
                <th style="width: 50px; text-align:center;">#</th>
                <th>Item Description</th>
                <th style="text-align: right; width: 100px;">Unit Price (Rs:)</th>
                <th style="text-align: right; width: 80px;">Quantity</th>
                <th style="text-align: right; width: 120px;">Total (Rs:)</th>
            </tr>
        </thead>
        <tbody>
            <?php $i = 1; foreach ($data['items'] as $item): ?>
                <tr>
                    <td style="text-align:center;"><?= $i++ ?></td>
                    <td style="font-weight: 500; color: #1d1d1f;">
                        <?= htmlspecialchars($item->description) ?>
                    </td>
                    <td style="text-align: right;">Rs: <?= number_format($item->unit_price, 2) ?></td>
                    <td style="text-align: right;"><?= number_format($item->quantity, 0) ?></td>
                    <td style="text-align: right; font-weight: bold; color: #1d1d1f;">Rs: <?= number_format($item->total, 2) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="total-section">
        <div class="total-row">
            <span>Subtotal:</span>
            <span>Rs: <?= number_format($data['credit_note']->total_amount, 2) ?></span>
        </div>
        <div class="total-row grand-total">
            <span>Grand Total:</span>
            <span>Rs: <?= number_format($data['credit_note']->total_amount, 2) ?></span>
        </div>
    </div>

    <div class="footer-note">
        Thank you for your business. This is a computer-generated credit note. No signature required.
    </div>
</div>
