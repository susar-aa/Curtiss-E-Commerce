<?php include __DIR__ . '/_header.php'; ?>
<table class="rpt">
    <thead>
        <tr>
            <th>Customer</th><th>Phone</th><th class="num">Invoices</th>
            <th class="num">Total Sales</th><th class="num">Paid</th><th class="num">Outstanding</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $tSales = 0; $tPaid = 0; $tOut = 0;
        if (empty($data['rows'])): ?>
        <tr><td colspan="6" class="empty-msg">No sales in this period.</td></tr>
        <?php else: foreach ($data['rows'] as $r):
            $tSales += $r->total_sales; $tPaid += $r->paid_amount; $tOut += $r->outstanding;
        ?>
        <tr>
            <td><strong><?= htmlspecialchars($r->customer_name) ?></strong></td>
            <td><?= htmlspecialchars($r->phone ?? '') ?></td>
            <td class="num"><?= intval($r->invoice_count) ?></td>
            <td class="num"><?= number_format($r->total_sales, 2) ?></td>
            <td class="num text-success"><?= number_format($r->paid_amount, 2) ?></td>
            <td class="num <?= $r->outstanding > 0 ? 'text-danger' : '' ?>"><?= number_format($r->outstanding, 2) ?></td>
        </tr>
        <?php endforeach; endif; ?>
        <?php if (!empty($data['rows'])): ?>
        <tr class="total-row">
            <td colspan="3">Grand Total</td>
            <td class="num">Rs <?= number_format($tSales, 2) ?></td>
            <td class="num">Rs <?= number_format($tPaid, 2) ?></td>
            <td class="num">Rs <?= number_format($tOut, 2) ?></td>
        </tr>
        <?php endif; ?>
    </tbody>
</table>
<?php include __DIR__ . '/_footer.php'; ?>
