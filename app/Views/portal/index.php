<?php
$c = $data['customer'];
$s = $data['stats'];
$company = $data['company'];

// Failsafe: Fetch cheques directly if the controller missed passing them
$cheques = $data['cheques'] ?? [];
if (empty($cheques) && isset($c->id)) {
    $db = new Database();
    $db->query("SELECT * FROM cheques WHERE customer_id = :cid ORDER BY banking_date DESC LIMIT 50");
    $db->bind(':cid', $c->id);
    $cheques = $db->resultSet();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($company->company_name) ?> - B2B Portal</title>
    <style>
        :root {
            --mac-bg: #f4f5f7;
            --mac-border: #e0e0e0;
            --text-main: #333;
        }
        
        body { background: #f4f5f7; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; margin: 0; padding: 0; color: var(--text-main); }
        
        @media (prefers-color-scheme: dark) {
            body { background: #121212; color: #eee; }
            :root { --mac-bg: #1e1e2d; --mac-border: #333; --text-main: #eee; }
        }

        .navbar { background: #fff; padding: 15px 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--mac-border);}
        @media (prefers-color-scheme: dark) { .navbar { background: #1e1e2d; border-color: #333; } }

        .main-container { max-width: 1200px; margin: 40px auto; padding: 0 20px; }

        .right-pane { width: 100%; display: flex; flex-direction: column; overflow: hidden; background: #fff; border-radius: 12px; border: 1px solid var(--mac-border); box-shadow: 0 10px 30px rgba(0,0,0,0.05);}
        @media (prefers-color-scheme: dark) { .right-pane { background: #1a1a2e; } }
        
        .right-header { padding: 30px 40px; border-bottom: 1px solid var(--mac-border); display: flex; justify-content: space-between; align-items: flex-start; background: #fff;}
        @media (prefers-color-scheme: dark) { .right-header { background: #1e1e2d; } }
        
        .avatar-circle { width: 70px; height: 70px; background: #e8f5e9; color: #2e7d32; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 28px; font-weight: bold; flex-shrink: 0;}
        
        /* Tabs System */
        .tabs { display: flex; border-bottom: 1px solid var(--mac-border); background: rgba(0,0,0,0.02); padding: 0 30px;}
        .tab-btn { padding: 15px 25px; border: none; background: transparent; cursor: pointer; font-size: 15px; font-weight: 600; color: #666; border-bottom: 3px solid transparent; transition: 0.2s;}
        .tab-btn:hover { color: #0066cc; }
        .tab-btn.active { color: #0066cc; border-bottom-color: #0066cc; }
        
        .tab-content { flex: 1; padding: 40px; display: none; }
        .tab-content.active { display: block; }
        
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th, .data-table td { padding: 15px 20px; text-align: left; border-bottom: 1px solid var(--mac-border); font-size: 15px;}
        .data-table th { color: #888; font-weight: 600; font-size: 12px; text-transform: uppercase; background: rgba(0,0,0,0.02);}
        .num-col { text-align: right !important; }
        
        .status-badge { padding: 6px 10px; border-radius: 6px; font-size: 11px; font-weight: bold; text-transform: uppercase; }
        .status-Paid { background: #e8f5e9; color: #2e7d32; }
        .status-Unpaid { background: #fff3e0; color: #ef6c00; }
        .status-Pending { background: #f5f5f5; color: #666; }
        .status-Cleared { background: #e8f5e9; color: #2e7d32; }
        .status-Bounced { background: #ffebee; color: #c62828; }

        .map-box { width: 100%; height: 350px; border-radius: 8px; border: 1px solid var(--mac-border); background: #eee; overflow: hidden; margin-top: 15px;}
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }

        .detail-box { background: rgba(0,0,0,0.02); padding: 15px 20px; border-radius: 8px; border: 1px solid var(--mac-border); margin-bottom: 15px; }
        .detail-label { font-size: 11px; color: #888; text-transform: uppercase; font-weight: bold; margin-bottom: 5px; }
        .detail-val { font-size: 16px; font-weight: 500; color: var(--text-main); }
    </style>
</head>
<body>
    <div class="navbar">
        <h2 style="margin:0; color:#0066cc; display:flex; align-items:center; gap:10px;">
            <?php if(!empty($company->logo_path)): ?>
                <img src="<?= APP_URL ?>/uploads/<?= htmlspecialchars($company->logo_path) ?>" style="height:30px;">
            <?php endif; ?>
            <?= htmlspecialchars($company->company_name) ?>
        </h2>
        <div style="font-size: 14px; font-weight:bold; color: #666;">B2B Customer Portal</div>
    </div>

    <div class="main-container">
        <div class="right-pane">
            <div class="right-header">
                <div style="display: flex; gap: 20px; align-items: center;">
                    <div class="avatar-circle"><?= strtoupper(substr($c->name, 0, 2)) ?></div>
                    <div>
                        <h2 style="margin: 0 0 5px 0; font-size: 28px;"><?= htmlspecialchars($c->name) ?></h2>
                        <div style="font-size: 15px; color: #666; display: flex; gap: 15px;">
                            <span>📞 <?= htmlspecialchars($c->phone ?: 'N/A') ?></span>
                            <span>✉️ <?= htmlspecialchars($c->email ?: 'N/A') ?></span>
                            <span>🗺️ <?= htmlspecialchars($c->mca_name ?: 'Route Unassigned') ?></span>
                        </div>
                    </div>
                </div>
                <div style="text-align: right;">
                    <div style="font-size: 13px; color: #888; text-transform: uppercase; font-weight: bold;">Total Unpaid Balance</div>
                    <div style="font-size: 32px; font-weight: bold; color: <?= $s->outstanding > 0 ? '#c62828' : '#2e7d32' ?>;">
                        Rs: <?= number_format($s->outstanding, 2) ?>
                    </div>
                </div>
            </div>

            <!-- Tab Navigation -->
            <div class="tabs">
                <button class="tab-btn active" onclick="switchTab('ledger')" id="btn_ledger">Activity Ledger</button>
                <button class="tab-btn" onclick="switchTab('invoices')" id="btn_invoices">Invoices</button>
                <button class="tab-btn" onclick="switchTab('cheques')" id="btn_cheques">Cheques (PDC)</button>
                <button class="tab-btn" onclick="switchTab('profile')" id="btn_profile">Profile & Map</button>
            </div>

            <!-- TAB 1: Activity Ledger -->
            <div class="tab-content active" id="tab_ledger">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Description</th>
                            <th class="num-col">Debit (Dr)</th>
                            <th class="num-col">Credit (Cr)</th>
                            <th class="num-col">Running Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($data['ledger'])): ?>
                            <tr><td colspan="5" style="text-align: center; color: #888; padding: 40px;">No financial activity yet.</td></tr>
                        <?php else: foreach($data['ledger'] as $l): ?>
                            <tr>
                                <td style="color:#666;"><?= date('M d, Y', strtotime($l->date)) ?></td>
                                <td>
                                    <strong><?= $l->type ?></strong>
                                    <?php if($l->type == 'Invoice'): ?>
                                        <a href="<?= APP_URL ?>/sales/show/<?= $l->id ?>" target="_blank" style="color:#0066cc; font-size: 13px; margin-left: 5px; font-weight:bold; text-decoration:none;">
                                            <?= htmlspecialchars($l->ref) ?> ↗
                                        </a>
                                    <?php elseif($l->type == 'Credit Note'): ?>
                                        <a href="<?= APP_URL ?>/creditnote/show/<?= $l->id ?>" target="_blank" style="color:#ff3b30; font-size: 13px; margin-left: 5px; font-weight:bold; text-decoration:none;">
                                            <?= htmlspecialchars($l->ref) ?> ↗
                                        </a>
                                    <?php else: ?>
                                        <span style="color:#888; font-size: 13px; margin-left: 5px;"><?= htmlspecialchars($l->ref) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="num-col" style="color:#333; font-weight:bold;"><?= $l->debit > 0 ? 'Rs: ' . number_format($l->debit, 2) : '-' ?></td>
                                <td class="num-col" style="color:#2e7d32; font-weight:bold;"><?= $l->credit > 0 ? 'Rs: ' . number_format($l->credit, 2) : '-' ?></td>
                                <td class="num-col" style="font-weight:bold; color: <?= $l->balance > 0 ? '#c62828' : '#2e7d32' ?>;">Rs: <?= number_format($l->balance, 2) ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- TAB 2: Latest Invoices -->
            <div class="tab-content" id="tab_invoices">
                <table class="data-table">
                    <thead><tr><th>Order #</th><th>Date</th><th class="num-col">Grand Total</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php if(empty($data['invoices'])): ?>
                            <tr><td colspan="4" style="text-align:center; color:#888; padding: 40px;">No invoices found.</td></tr>
                        <?php else: foreach($data['invoices'] as $inv): ?>
                            <?php 
                                $trueInvTotal = $inv->total_amount;
                                if($inv->global_discount_val > 0) {
                                    $trueInvTotal -= ($inv->global_discount_type == '%' ? ($inv->total_amount * $inv->global_discount_val / 100) : $inv->global_discount_val);
                                }
                                $trueInvTotal += $inv->tax_amount;
                            ?>
                            <tr>
                                <td><a href="<?= APP_URL ?>/sales/show/<?= $inv->id ?>" target="_blank" style="color:#0066cc; font-weight:bold; text-decoration:none; font-size: 16px;"><?= $inv->invoice_number ?></a></td>
                                <td style="color:#666;"><?= date('M d, Y', strtotime($inv->invoice_date)) ?></td>
                                <td class="num-col" style="font-weight:bold; font-size: 16px;">Rs: <?= number_format($trueInvTotal, 2) ?></td>
                                <td><span class="status-badge status-<?= $inv->status ?>"><?= $inv->status ?></span></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- TAB 3: Latest Cheques -->
            <div class="tab-content" id="tab_cheques">
                <table class="data-table">
                    <thead><tr><th>Bank & Date</th><th class="num-col">Amount</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php if(empty($cheques)): ?>
                            <tr><td colspan="3" style="text-align:center; color:#888; padding: 40px;">No cheques recorded.</td></tr>
                        <?php else: foreach($cheques as $chk): ?>
                            <tr>
                                <td>
                                    <strong style="font-size: 16px;"><?= htmlspecialchars($chk->bank_name) ?></strong><br>
                                    <span style="font-size:13px; color:#666;"><?= date('M d, Y', strtotime($chk->banking_date)) ?></span>
                                </td>
                                <td class="num-col" style="font-weight:bold; font-size: 16px;">Rs: <?= number_format($chk->amount, 2) ?></td>
                                <td><span class="status-badge status-<?= $chk->status ?>"><?= $chk->status ?></span></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- TAB 4: Profile & Map (Read-Only) -->
            <div class="tab-content" id="tab_profile">
                <div class="grid-2">
                    <!-- Profile Stats & Info -->
                    <div>
                        <div style="display:flex; gap:20px; margin-bottom: 25px;">
                            <div style="flex:1; background:rgba(0,0,0,0.02); padding:20px; border-radius:8px; border:1px solid var(--mac-border);">
                                <div style="font-size:12px; color:#888; text-transform:uppercase; font-weight:bold;">Total Orders</div>
                                <div style="font-size:24px; font-weight:bold; color:var(--text-main); margin-top:5px;"><?= $s->total_orders ?></div>
                            </div>
                            <div style="flex:1; background:rgba(0,0,0,0.02); padding:20px; border-radius:8px; border:1px solid var(--mac-border);">
                                <div style="font-size:12px; color:#888; text-transform:uppercase; font-weight:bold;">Total Billed</div>
                                <div style="font-size:24px; font-weight:bold; color:var(--text-main); margin-top:5px;">Rs: <?= number_format($s->total_billed, 2) ?></div>
                            </div>
                            <div style="flex:1; background:rgba(46,125,50,0.05); padding:20px; border-radius:8px; border:1px solid rgba(46,125,50,0.2);">
                                <div style="font-size:12px; color:#2e7d32; text-transform:uppercase; font-weight:bold;">Total Paid</div>
                                <div style="font-size:24px; font-weight:bold; color:#2e7d32; margin-top:5px;">Rs: <?= number_format($s->total_paid, 2) ?></div>
                            </div>
                        </div>

                        <h3 style="margin-top:0; border-bottom: 1px solid var(--mac-border); padding-bottom: 15px;">Account Details</h3>
                        
                        <div class="grid-2">
                            <div class="detail-box">
                                <div class="detail-label">Company / Name</div>
                                <div class="detail-val"><?= htmlspecialchars($c->name) ?></div>
                            </div>
                            <div class="detail-box">
                                <div class="detail-label">Email Address</div>
                                <div class="detail-val"><?= htmlspecialchars($c->email ?: 'N/A') ?></div>
                            </div>
                            <div class="detail-box">
                                <div class="detail-label">Phone Number</div>
                                <div class="detail-val"><?= htmlspecialchars($c->phone ?: 'N/A') ?></div>
                            </div>
                            <div class="detail-box">
                                <div class="detail-label">Billing Address</div>
                                <div class="detail-val"><?= nl2br(htmlspecialchars($c->address ?: 'N/A')) ?></div>
                            </div>
                        </div>
                        
                        <p style="font-size: 13px; color: #888; margin-top: 10px;">To update these details, please contact our support team.</p>
                    </div>

                    <!-- Map View -->
                    <div>
                        <h3 style="margin-top:0; border-bottom: 1px solid var(--mac-border); padding-bottom: 15px;">Location Map</h3>
                        <div class="map-box" style="height: 400px;">
                            <?php if($c->latitude && $c->longitude): ?>
                                <iframe width="100%" height="100%" frameborder="0" style="border:0;" src="https://maps.google.com/maps?q=<?= $c->latitude ?>,<?= $c->longitude ?>&hl=en&z=14&output=embed"></iframe>
                            <?php else: ?>
                                <div style="display:flex; height:100%; align-items:center; justify-content:center; color:#aaa; font-size:14px; flex-direction:column; background: #fafafa;">
                                    <span style="font-size: 40px; margin-bottom: 10px;">🗺️</span>
                                    No GPS coordinates saved for your account.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script>
        function switchTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
            document.getElementById('tab_' + tabName).classList.add('active');
            document.getElementById('btn_' + tabName).classList.add('active');
        }
    </script>
</body>
</html>