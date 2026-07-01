<?php include __DIR__ . '/_header.php'; ?>
<h3 class="section-h">By Payment Method</h3>
<table class="rpt">
    <thead><tr><th>Method</th><th class="num">Transactions</th><th class="num">Collected (Rs)</th></tr></thead>
    <tbody>
        <?php $t = 0; foreach ($data['by_method'] as $m): $t += $m->total_collected; ?>
        <tr>
            <td><strong><?= htmlspecialchars($m->payment_method) ?></strong></td>
            <td class="num"><?= intval($m->tx_count) ?></td>
            <td class="num"><?= number_format($m->total_collected, 2) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!empty($data['by_method'])): ?>
        <tr class="total-row"><td>Total</td><td></td><td class="num">Rs <?= number_format($t, 2) ?></td></tr>
        <?php endif; ?>
    </tbody>
</table>

<h3 class="section-h">All Collections (Detail)</h3>
<table class="rpt">
    <thead>
        <tr><th>Date</th><th>Customer</th><th>Method</th><th>Reference</th><th class="num">Amount</th><th>By</th></tr>
    </thead>
    <tbody>
        <?php if (empty($data['detail'])): ?>
        <tr><td colspan="6" class="empty-msg">No collections in this period.</td></tr>
        <?php else: foreach ($data['detail'] as $p): ?>
        <tr>
            <td><?= date('M j, Y', strtotime($p->payment_date)) ?></td>
            <td><?= htmlspecialchars($p->customer_name) ?></td>
            <td><?= htmlspecialchars($p->payment_method) ?></td>
            <td><?= htmlspecialchars($p->reference ?? '') ?></td>
            <td class="num"><?= number_format($p->amount, 2) ?></td>
            <td><?= htmlspecialchars($p->collected_by ?? '') ?></td>
        </tr>
        <?php endforeach; endif; ?>
    </tbody>
</table>
<?php include __DIR__ . '/_footer.php'; ?>
