<?php include __DIR__ . '/_header.php'; $s = $data['summary']; ?>
<div class="kpi-row">
    <div class="kpi"><span>Invoices</span><strong><?= intval($s->invoice_count ?? 0) ?></strong></div>
    <div class="kpi"><span>Gross Sales</span><strong>Rs <?= number_format($s->gross_sales ?? 0, 2) ?></strong></div>
    <div class="kpi"><span>Tax</span><strong>Rs <?= number_format($s->total_tax ?? 0, 2) ?></strong></div>
    <div class="kpi"><span>Paid</span><strong class="text-success">Rs <?= number_format($s->paid_sales ?? 0, 2) ?></strong></div>
    <div class="kpi"><span>Outstanding</span><strong class="text-danger">Rs <?= number_format($s->unpaid_sales ?? 0, 2) ?></strong></div>
</div>
<h3 class="section-h">Daily Sales</h3>
<table class="rpt">
    <thead><tr><th>Date</th><th class="num">Invoices</th><th class="num">Sales (Rs)</th></tr></thead>
    <tbody>
        <?php if (empty($data['daily'])): ?>
        <tr><td colspan="3" class="empty-msg">No sales in this period.</td></tr>
        <?php else: foreach ($data['daily'] as $d): ?>
        <tr>
            <td><?= date('M j, Y', strtotime($d->period_date)) ?></td>
            <td class="num"><?= intval($d->cnt) ?></td>
            <td class="num"><?= number_format($d->daily_total, 2) ?></td>
        </tr>
        <?php endforeach; ?>
        <tr class="total-row">
            <td>Total</td>
            <td class="num"><?= intval($s->invoice_count ?? 0) ?></td>
            <td class="num">Rs <?= number_format($s->gross_sales ?? 0, 2) ?></td>
        </tr>
        <?php endif; ?>
    </tbody>
</table>
<?php include __DIR__ . '/_footer.php'; ?>
