<?php include __DIR__ . '/_header.php'; ?>
<table class="rpt">
    <thead>
        <tr>
            <th>Vendor</th><th class="num">POs</th><th class="num">PO Value</th>
            <th class="num">GRNs</th><th class="num">GRN Received Value</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $tPo = 0; $tGrn = 0;
        if (empty($data['rows'])): ?>
        <tr><td colspan="5" class="empty-msg">No purchase or GRN activity in this period.</td></tr>
        <?php else: foreach ($data['rows'] as $r):
            $tPo += $r->po_total; $tGrn += $r->grn_received_value;
        ?>
        <tr>
            <td><strong><?= htmlspecialchars($r->vendor_name) ?></strong></td>
            <td class="num"><?= intval($r->po_count) ?></td>
            <td class="num"><?= number_format($r->po_total, 2) ?></td>
            <td class="num"><?= intval($r->grn_count) ?></td>
            <td class="num"><?= number_format($r->grn_received_value, 2) ?></td>
        </tr>
        <?php endforeach; endif; ?>
        <?php if (!empty($data['rows'])): ?>
        <tr class="total-row">
            <td>Total</td><td></td>
            <td class="num">Rs <?= number_format($tPo, 2) ?></td><td></td>
            <td class="num">Rs <?= number_format($tGrn, 2) ?></td>
        </tr>
        <?php endif; ?>
    </tbody>
</table>
<?php include __DIR__ . '/_footer.php'; ?>
