<?php include __DIR__ . '/_header.php'; ?>
<table class="rpt">
    <tr><td colspan="2" class="section-h" style="border:none;">Income</td></tr>
    <?php foreach ($data['revenues'] as $acc): ?>
    <tr><td><?= htmlspecialchars($acc->account_name) ?></td><td class="num"><?= number_format($acc->period_balance, 2) ?></td></tr>
    <?php endforeach; ?>
    <tr class="total-row"><td>Total Income</td><td class="num">Rs <?= number_format($data['total_revenue'], 2) ?></td></tr>

    <tr><td colspan="2" class="section-h" style="border:none; padding-top:20px;">Expenses</td></tr>
    <?php foreach ($data['expenses'] as $acc): ?>
    <tr><td><?= htmlspecialchars($acc->account_name) ?></td><td class="num"><?= number_format($acc->period_balance, 2) ?></td></tr>
    <?php endforeach; ?>
    <tr class="total-row"><td>Total Expenses</td><td class="num">Rs <?= number_format($data['total_expense'], 2) ?></td></tr>

    <tr class="total-row">
        <td style="padding-top:20px;">Net Income</td>
        <td class="num <?= $data['net_income'] >= 0 ? 'text-success' : 'text-danger' ?>" style="padding-top:20px;">
            Rs <?= number_format($data['net_income'], 2) ?>
        </td>
    </tr>
</table>
<?php include __DIR__ . '/_footer.php'; ?>
