<?php include __DIR__ . '/_header.php'; ?>
<div class="kpi-row">
    <div class="kpi"><span>Items in Stock</span><strong><?= count($data['rows']) ?></strong></div>
    <div class="kpi"><span>Value at Cost</span><strong>Rs <?= number_format($data['total_cost'], 2) ?></strong></div>
    <div class="kpi"><span>Value at Retail</span><strong>Rs <?= number_format($data['total_retail'], 2) ?></strong></div>
</div>
<table class="rpt">
    <thead>
        <tr>
            <th>Code</th><th>Product</th><th>Category</th>
            <th class="num">Qty</th><th class="num">Unit Cost</th><th class="num">Stock @ Cost</th><th class="num">Stock @ Retail</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($data['rows'])): ?>
        <tr><td colspan="7" class="empty-msg">No stock on hand.</td></tr>
        <?php else: foreach ($data['rows'] as $r): ?>
        <tr>
            <td><?= htmlspecialchars($r->item_code ?? '') ?></td>
            <td><?= htmlspecialchars($r->name) ?></td>
            <td><?= htmlspecialchars($r->category_name) ?></td>
            <td class="num"><?= number_format($r->qty_on_hand, 0) ?></td>
            <td class="num"><?= number_format($r->unit_cost, 2) ?></td>
            <td class="num"><?= number_format($r->stock_value_cost, 2) ?></td>
            <td class="num"><?= number_format($r->stock_value_retail, 2) ?></td>
        </tr>
        <?php endforeach; endif; ?>
        <tr class="total-row">
            <td colspan="5">Grand Total</td>
            <td class="num">Rs <?= number_format($data['total_cost'], 2) ?></td>
            <td class="num">Rs <?= number_format($data['total_retail'], 2) ?></td>
        </tr>
    </tbody>
</table>
<?php include __DIR__ . '/_footer.php'; ?>
