<style>
    .invoice-box { max-width: 800px; margin: auto; padding: 30px; border: 1px solid var(--mac-border); background: #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.03); border-radius: 8px; color: #333; }
    .invoice-box table { width: 100%; line-height: inherit; text-align: left; border-collapse: collapse; }
    .invoice-box table td { padding: 8px; vertical-align: top; }
    .invoice-box table tr td:nth-child(2) { text-align: right; }
    .invoice-box table tr.top table td { padding: 0; }
    .invoice-box table tr.top table td.title { font-size: 45px; line-height: 45px; color: #333; }
    .invoice-box table tr.information table td { padding-bottom: 20px; }
    .invoice-box table tr.heading td { background: #eee; border-bottom: 1px solid #ddd; font-weight: bold; padding: 10px 8px; }
    .invoice-box table tr.details td { padding-bottom: 20px; }
    .invoice-box table tr.item td { border-bottom: 1px solid #eee; padding: 10px 8px; }
    .invoice-box table tr.item.last td { border-bottom: none; }
    .invoice-box table tr.total td:nth-child(2) { border-top: 2px solid #eee; font-weight: bold; font-size: 16px; color: #c62828; }
    @media (prefers-color-scheme: dark) {
        .invoice-box { background: #1e1e2d; border-color: rgba(255,255,255,0.1); color: #fff; }
        .invoice-box table tr.heading td { background: #2b2b3c; border-bottom-color: #3b3b4c; }
        .invoice-box table tr.total td:nth-child(2) { border-top-color: #3b3b4c; }
    }
</style>

<div class="card">
    <div style="margin-bottom: 20px; display:flex; justify-content:space-between; align-items:center;">
        <h2>Supplier Return Details</h2>
        <div>
            <button onclick="window.print()" class="btn" style="background:#2e7d32; padding: 8px 16px; color:white; border:none; border-radius:4px; cursor:pointer;">🖨️ Print / Save PDF</button>
            <a href="<?= APP_URL ?>/supplier-return" class="btn" style="background:#666; padding: 8px 16px; color:white; border:none; border-radius:4px; text-decoration:none; margin-left: 5px;">Back to List</a>
        </div>
    </div>

    <div class="invoice-box">
        <table>
            <tr class="top">
                <td colspan="5">
                    <table>
                        <tr>
                            <td class="title">
                                <h2 style="color: #c62828; margin: 0; font-size: 28px;"> RETURN NOTE</h2>
                            </td>
                            <td>
                                <strong>Return #: <?= htmlspecialchars($data['return']->return_number) ?></strong><br>
                                Date: <?= date('M d, Y', strtotime($data['return']->return_date)) ?><br>
                                Creator: <?= htmlspecialchars($data['return']->creator_name) ?>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            
            <tr class="information">
                <td colspan="5">
                    <table>
                        <tr>
                            <td>
                                <strong>Returned To (Supplier):</strong><br>
                                <?= htmlspecialchars($data['return']->vendor_name) ?><br>
                                Phone: <?= htmlspecialchars($data['return']->phone ?? 'N/A') ?><br>
                                Email: <?= htmlspecialchars($data['return']->email ?? 'N/A') ?><br>
                                Address: <?= htmlspecialchars($data['return']->address ?? 'N/A') ?>
                            </td>
                            <td>
                                <strong>Returned From:</strong><br>
                                <?= APP_NAME ?> ERP Inventory<br>
                                Physical Stock Dispatch
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            
            <tr class="heading">
                <td>#</td>
                <td>Description</td>
                <td style="text-align: right;">Returned Qty</td>
                <td style="text-align: right;">Purchase Cost (LKR)</td>
                <td style="text-align: right;">Total Return Value</td>
            </tr>
            
            <?php $i = 1; foreach($data['items'] as $item): ?>
                <tr class="item <?= ($i === count($data['items'])) ? 'last' : '' ?>">
                    <td><?= $i++ ?></td>
                    <td>
                        <strong><?= htmlspecialchars($item->description) ?></strong>
                        <?php if($item->grn_number): ?>
                            <div style="font-size:10px; color:#888; margin-top:2px;">🔗 Sourced from: <?= htmlspecialchars($item->grn_number) ?></div>
                        <?php endif; ?>
                    </td>
                    <td style="text-align: right;"><?= number_format($item->quantity, 0) ?></td>
                    <td style="text-align: right;">Rs: <?= number_format($item->unit_cost, 2) ?></td>
                    <td style="text-align: right; font-weight: bold;">Rs: <?= number_format($item->total, 2) ?></td>
                </tr>
            <?php endforeach; ?>
            
            <tr class="total">
                <td colspan="3"></td>
                <td style="text-align: right; font-weight: bold; font-size: 14px;">Grand Total:</td>
                <td>Rs: <?= number_format($data['return']->total_amount, 2) ?></td>
            </tr>
        </table>

        <?php if(!empty($data['return']->notes)): ?>
            <div style="margin-top: 30px; padding: 15px; background: rgba(0,0,0,0.02); border-radius: 6px; border-left: 4px solid #c62828;">
                <strong style="display:block; font-size:12px; margin-bottom:5px; text-transform:uppercase; color:#666;">Return Reasons / Notes:</strong>
                <p style="margin: 0; font-size: 13px; font-style: italic; line-height: 1.5;"><?= nl2br(htmlspecialchars($data['return']->notes)) ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>
