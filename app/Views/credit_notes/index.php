<?php
?>
<style>
    .header-actions { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .btn { padding: 8px 16px; background: #c62828; color: #fff; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; font-size: 14px;}
    .btn:hover { background: #b71c1c; }
    .btn-outline { background: transparent; border: 1px solid #c62828; color: #c62828; }
    .data-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    .data-table th, .data-table td { padding: 12px; text-align: left; border-bottom: 1px solid var(--mac-border); }
    .data-table th { background-color: rgba(0,0,0,0.02); font-weight: 600; font-size: 13px; }
    .status-badge { padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: bold; }
    .status-Issued { background: #e8f5e9; color: #2e7d32; }
</style>

<div class="card">
    <div class="header-actions">
        <h2>Credit Notes & Refunds</h2>
        <a href="<?= APP_URL ?>/creditnote/create" class="btn">+ Issue Credit Note</a>
    </div>

    <?php if(isset($_GET['success'])): ?>
        <div style="padding: 10px; background:#e8f5e9; color:#2e7d32; border-radius:4px; margin-bottom:15px;">Credit Note generated and ledger successfully reversed!</div>
    <?php endif; ?>

    <table class="data-table">
        <thead>
            <tr>
                <th>Credit Note #</th>
                <th>Date</th>
                <th>Customer</th>
                <th>Status</th>
                <th style="text-align: right;">Amount (Rs:)</th>
                <th style="text-align: center;">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if(empty($data['credit_notes'])): ?>
            <tr><td colspan="6" style="text-align: center; color: #888;">No credit notes found.</td></tr>
            <?php else: foreach($data['credit_notes'] as $cn): ?>
            <tr>
                <td><strong><?= htmlspecialchars($cn->credit_note_number) ?></strong></td>
                <td><?= date('M d, Y', strtotime($cn->note_date)) ?></td>
                <td><?= htmlspecialchars($cn->customer_name) ?></td>
                <td><span class="status-badge status-<?= $cn->status ?>"><?= $cn->status ?></span></td>
                <td style="text-align: right; font-weight:bold; color:#c62828;">-<?= number_format($cn->total_amount, 2) ?></td>
                <td style="text-align: center;">
                    <a href="<?= APP_URL ?>/creditnote/show/<?= $cn->id ?>" class="btn-outline" style="padding: 4px 8px; font-size: 11px; border-radius:4px; text-decoration:none;">View / Print</a>
                </td>
            </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>