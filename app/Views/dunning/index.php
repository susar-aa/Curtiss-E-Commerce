<?php
?>
<style>
    .header-actions { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .btn { padding: 8px 16px; background: #0066cc; color: #fff; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; font-size: 14px;}
    .data-table { width: 100%; border-collapse: collapse; margin-top: 10px; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.05);}
    @media (prefers-color-scheme: dark) { .data-table { background: #1e1e2d; } }
    .data-table th, .data-table td { padding: 12px; text-align: left; border-bottom: 1px solid var(--mac-border); font-size: 13px;}
    .data-table th { background-color: rgba(0,0,0,0.02); font-weight: 600; color:#555;}
    
    .badge { padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: bold; }
    .badge-danger { background: #ffebee; color: #c62828; }
    .badge-warning { background: #fff3e0; color: #ef6c00; }
    .badge-info { background: #e3f2fd; color: #1565c0; }
    
    .wa-btn { background: #25D366; color: #fff; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 11px; font-weight: bold; display: inline-block; border: none; cursor: pointer;}
    .wa-btn:hover { background: #20ba56; }
</style>

<div class="header-actions">
    <div>
        <h2 style="margin: 0 0 5px 0;">Automated Dunning & AR Reminders</h2>
        <p style="margin: 0; color: #666; font-size: 14px;">Monitor and send payment reminders for overdue invoices.</p>
    </div>
    <div style="text-align: right;">
        <span style="font-size: 11px; color: #888; display: block; margin-bottom: 5px;">Cron Job Endpoint: <code><?= APP_URL ?>/dunning/cron</code></span>
        <a href="<?= APP_URL ?>/dunning/cron" target="_blank" class="btn" style="background:#333;">Force Run Auto-Emails</a>
    </div>
</div>

<div class="card">
    <h3 style="margin-top:0; border-bottom: 1px solid var(--mac-border); padding-bottom: 10px;">Overdue Invoices Queue</h3>
    
    <table class="data-table">
        <thead>
            <tr>
                <th>Customer Name</th>
                <th>Invoice #</th>
                <th>Due Date</th>
                <th>Overdue Status</th>
                <th style="text-align: right;">Total Amount</th>
                <th style="text-align: center;">Manual Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if(empty($data['overdue_invoices'])): ?>
                <tr><td colspan="6" style="text-align: center; padding: 30px; color: #888;">No overdue invoices. Excellent!</td></tr>
            <?php else: foreach($data['overdue_invoices'] as $inv): ?>
                <?php 
                    $trueInvTotal = $inv->total_amount;
                    if($inv->global_discount_val > 0) {
                        $trueInvTotal -= ($inv->global_discount_type == '%' ? ($inv->total_amount * $inv->global_discount_val / 100) : $inv->global_discount_val);
                    }
                    $trueInvTotal += $inv->tax_amount;
                    
                    $badgeClass = 'badge-info';
                    if ($inv->days_overdue >= 30) $badgeClass = 'badge-danger';
                    elseif ($inv->days_overdue >= 7) $badgeClass = 'badge-warning';
                ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars($inv->customer_name) ?></strong><br>
                        <span style="font-size: 11px; color:#888;"><?= htmlspecialchars($inv->email ?: $inv->phone) ?></span>
                    </td>
                    <td><a href="<?= APP_URL ?>/sales/show/<?= $inv->id ?>" target="_blank" style="color: #0066cc; font-weight: bold; text-decoration: none;"><?= $inv->invoice_number ?></a></td>
                    <td><?= date('M d, Y', strtotime($inv->due_date)) ?></td>
                    <td><span class="badge <?= $badgeClass ?>"><?= $inv->days_overdue ?> Days Overdue</span></td>
                    <td style="font-weight: bold; text-align: right;">Rs: <?= number_format($trueInvTotal, 2) ?></td>
                    <td style="text-align: center;">
                        <button onclick="sendWaReminder('<?= htmlspecialchars(addslashes($inv->phone)) ?>', '<?= htmlspecialchars(addslashes($inv->customer_name)) ?>', '<?= $inv->invoice_number ?>', '<?= APP_URL ?>/sales/show/<?= $inv->id ?>')" class="wa-btn">💬 WhatsApp</button>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<script>
    function sendWaReminder(phone, name, invNum, link) {
        if (!phone || phone.trim() === '') {
            alert('No phone number on file for this customer.');
            return;
        }
        let cleanPhone = phone.replace(/[^\d+]/g, '');
        if(cleanPhone.startsWith('0')) cleanPhone = '94' + cleanPhone.substring(1); 
        
        const msg = `Hello ${name},\n\nThis is a friendly reminder that invoice ${invNum} is currently overdue. You can view and download your invoice here:\n${link}\n\nPlease let us know if payment has already been made. Thank you!`;
        
        const waUrl = `https://wa.me/${cleanPhone}?text=${encodeURIComponent(msg)}`;
        window.open(waUrl, '_blank');
    }
</script>