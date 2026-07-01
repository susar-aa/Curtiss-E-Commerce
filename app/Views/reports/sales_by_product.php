<?php include __DIR__ . '/_header.php'; ?>
<table class="rpt">
    <thead>
        <tr>
            <th>Product / Description</th><th class="num">Qty Sold</th>
            <th class="num">Revenue</th><th class="num">Cost</th><th class="num">Gross Profit</th><th class="num">Margin %</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $tRev = 0; $tCost = 0; $tProfit = 0;
        if (empty($data['rows'])): ?>
        <tr><td colspan="6" class="empty-msg">No line items in this period.</td></tr>
        <?php else: foreach ($data['rows'] as $r):
            $tRev += $r->total_revenue; $tCost += $r->total_cost; $tProfit += $r->gross_profit;
            $margin = $r->total_revenue > 0 ? ($r->gross_profit / $r->total_revenue) * 100 : 0;
        ?>
        <tr>
            <td><?= htmlspecialchars($r->item_name) ?></td>
            <td class="num"><?= number_format($r->total_qty, 0) ?></td>
            <td class="num"><?= number_format($r->total_revenue, 2) ?></td>
            <td class="num"><?= number_format($r->total_cost, 2) ?></td>
            <td class="num"><?= number_format($r->gross_profit, 2) ?></td>
            <td class="num"><?= number_format($margin, 1) ?>%</td>
        </tr>
        <?php endforeach; endif; ?>
        <?php if (!empty($data['rows'])): ?>
        <tr class="total-row">
            <td colspan="2">Total</td>
            <td class="num">Rs <?= number_format($tRev, 2) ?></td>
            <td class="num">Rs <?= number_format($tCost, 2) ?></td>
            <td class="num">Rs <?= number_format($tProfit, 2) ?></td>
            <td class="num"><?= $tRev > 0 ? number_format(($tProfit / $tRev) * 100, 1) : 0 ?>%</td>
        </tr>
        <?php endif; ?>
    </tbody>
</table>
<?php include __DIR__ . '/_footer.php'; ?>
