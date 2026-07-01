<?php include __DIR__ . '/_header.php'; ?>
<table class="rpt">
    <thead>
        <tr>
            <th>Route / Territory</th><th>Rep</th><th>Start</th><th>Status</th>
            <th class="num">Invoices</th><th class="num">Route Sales (Rs)</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $tSales = 0; $tInv = 0;
        if (empty($data['rows'])): ?>
        <tr><td colspan="6" class="empty-msg">No rep routes in this period.</td></tr>
        <?php else: foreach ($data['rows'] as $r):
            $tSales += $r->route_sales; $tInv += $r->invoice_count;
        ?>
        <tr>
            <td><strong><?= htmlspecialchars($r->route_name) ?></strong></td>
            <td><?= htmlspecialchars(trim($r->rep_first . ' ' . $r->rep_last)) ?></td>
            <td><?= date('M j, Y h:i A', strtotime($r->start_time)) ?></td>
            <td><?= htmlspecialchars($r->status) ?></td>
            <td class="num"><?= intval($r->invoice_count) ?></td>
            <td class="num"><?= number_format($r->route_sales, 2) ?></td>
        </tr>
        <?php endforeach; endif; ?>
        <?php if (!empty($data['rows'])): ?>
        <tr class="total-row">
            <td colspan="4">Total</td>
            <td class="num"><?= intval($tInv) ?></td>
            <td class="num">Rs <?= number_format($tSales, 2) ?></td>
        </tr>
        <?php endif; ?>
    </tbody>
</table>
<?php include __DIR__ . '/_footer.php'; ?>
