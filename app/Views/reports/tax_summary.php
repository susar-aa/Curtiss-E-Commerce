<?php include __DIR__ . '/_header.php'; $tot = $data['totals']; ?>
<div class="kpi-row">
    <div class="kpi"><span>Total Tax</span><strong>Rs <?= number_format($tot->total_tax ?? 0, 2) ?></strong></div>
    <div class="kpi"><span>Grand Sales</span><strong>Rs <?= number_format($tot->grand_total ?? 0, 2) ?></strong></div>
</div>
<table class="rpt">
    <thead>
        <tr><th>Date</th><th class="num">Invoices</th><th class="num">Subtotal</th>
        <th class="num">Discount</th><th class="num">Tax</th><th class="num">Grand Total</th></tr>
    </thead>
    <tbody>
        <?php if (empty($data['daily'])): ?>
        <tr><td colspan="6" class="empty-msg">No invoices in this period.</td></tr>
        <?php else: foreach ($data['daily'] as $d): ?>
        <tr>
            <td><?= date('M j, Y', strtotime($d->invoice_date)) ?></td>
            <td class="num"><?= intval($d->invoice_count) ?></td>
            <td class="num"><?= number_format($d->subtotal, 2) ?></td>
            <td class="num"><?= number_format($d->total_discount, 2) ?></td>
            <td class="num"><?= number_format($d->total_tax, 2) ?></td>
            <td class="num"><?= number_format($d->grand_total, 2) ?></td>
        </tr>
        <?php endforeach; endif; ?>
    </tbody>
</table>
<?php include __DIR__ . '/_footer.php'; ?>
