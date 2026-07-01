<?php
?>
<style>
    .header-actions { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 20px; }
    .ledger-table { width: 100%; border-collapse: collapse; margin-top: 10px; background: #fff; }
    @media (prefers-color-scheme: dark) { .ledger-table { background: transparent; } }
    .ledger-table th, .ledger-table td { padding: 12px; text-align: left; border-bottom: 1px solid var(--mac-border); font-size: 14px; }
    .ledger-table th { background-color: rgba(0,0,0,0.02); font-weight: 600; font-size: 13px; }
    .num-col { text-align: right !important; }
    .debit { color: #2e7d32; font-weight: 500; }
    .credit { color: #c62828; font-weight: 500; }
</style>

<div class="card">
    <div class="header-actions">
        <div>
            <a href="<?= APP_URL ?>/banking" style="color: #666; text-decoration:none; font-size:13px;">&larr; Back to Banking</a>
            <h2 style="margin: 10px 0 0 0;">Account Register</h2>
            <p style="margin: 0; color: #888; font-size: 14px;"><?= htmlspecialchars($data['account']->account_code) ?> - <?= htmlspecialchars($data['account']->account_name) ?></p>
        </div>
        <div style="text-align: right;">
            <div style="font-size: 12px; color: #888; text-transform: uppercase;">Current Balance</div>
            <div style="font-size: 28px; font-weight: bold; color: #0066cc;">Rs: <?= number_format($data['account']->balance, 2) ?></div>
        </div>
    </div>

    <table class="ledger-table">
        <thead>
            <tr>
                <th style="width: 120px;">Date</th>
                <th style="width: 150px;">Reference</th>
                <th>Description</th>
                <th class="num-col" style="width: 150px;">Debit (Rs:)</th>
                <th class="num-col" style="width: 150px;">Credit (Rs:)</th>
                <th style="width: 100px; text-align: center;">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if(empty($data['transactions'])): ?>
                <tr><td colspan="6" style="text-align: center; color: #888; padding: 30px;">No transactions found for this account.</td></tr>
            <?php else: foreach($data['transactions'] as $t): ?>
                <tr>
                    <td><?= date('M d, Y', strtotime($t->entry_date)) ?></td>
                    <td><strong><?= htmlspecialchars($t->reference) ?></strong></td>
                    <td><?= htmlspecialchars($t->description) ?></td>
                    <td class="num-col debit"><?= $t->debit > 0 ? number_format($t->debit, 2) : '-' ?></td>
                    <td class="num-col credit"><?= $t->credit > 0 ? number_format($t->credit, 2) : '-' ?></td>
                    <td style="text-align: center;">
                        <?php if ($t->is_cleared): ?>
                            <span style="background: #e8f5e9; color: #2e7d32; padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: bold;">✓ Cleared</span>
                        <?php else: ?>
                            <span style="background: #fff3e0; color: #ef6c00; padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: bold;">Uncleared</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>