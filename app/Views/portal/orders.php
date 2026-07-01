<div class="portal-layout">
    <!-- Sidebar Menu navigation -->
    <div class="portal-menu-card">
        <h4 style="font-size: 11px; text-transform:uppercase; color:var(--text-muted); font-weight:700; margin-bottom: 15px; letter-spacing:0.5px;">Navigation Menu</h4>
        <ul class="portal-menu-list">
            <li><a href="<?= APP_URL ?>/portal" class="portal-menu-link"><i class="ph ph-squares-four"></i> Dashboard</a></li>
            <li><a href="<?= APP_URL ?>/portal/orders" class="portal-menu-link active"><i class="ph ph-receipt"></i> Order History</a></li>
            <li><a href="<?= APP_URL ?>/portal/wishlist" class="portal-menu-link"><i class="ph ph-heart"></i> My Wishlist</a></li>
            <li><a href="<?= APP_URL ?>/portal/returns" class="portal-menu-link"><i class="ph ph-arrow-counter-clockwise"></i> Return Requests</a></li>
            <li><a href="<?= APP_URL ?>/portal/profile" class="portal-menu-link"><i class="ph ph-user-gear"></i> Profile Settings</a></li>
        </ul>
    </div>

    <!-- Main Workspace Content -->
    <div class="card">
        <h3 style="font-size: 16px; font-weight:700; border-bottom:1px solid var(--mega-divider); padding-bottom:10px; margin-bottom:15px;">Your Order History</h3>
        
        <?php if(empty($data['orders'])): ?>
            <p style="text-align:center; padding: 40px; color:var(--text-muted); font-size:13.5px;">No order history found for your account profile.</p>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table style="width:100%; border-collapse:collapse; font-size:13.5px; text-align:left;">
                    <thead>
                        <tr style="border-bottom:1px solid var(--card-border); color:var(--text-muted); font-weight:600;">
                            <th style="padding:12px;">Order Number</th>
                            <th style="padding:12px;">Purchase Date</th>
                            <th style="padding:12px;">Status</th>
                            <th style="padding:12px; text-align:right;">Subtotal</th>
                            <th style="padding:12px; text-align:right;">Grand Total</th>
                            <th style="padding:12px; text-align:right; width:80px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($data['orders'] as $ord): ?>
                            <tr style="border-bottom:1px solid var(--mega-divider);">
                                <td style="padding:12px; font-family:monospace;"><?= htmlspecialchars($ord->order_number) ?></td>
                                <td style="padding:12px;"><?= date('F d, Y', strtotime($ord->order_date)) ?></td>
                                <td style="padding:12px;">
                                    <?php if($ord->status === 'Delivered'): ?>
                                        <span class="pill-badge pill-success">Delivered</span>
                                    <?php elseif($ord->status === 'Pending'): ?>
                                        <span class="pill-badge pill-warning">Pending</span>
                                    <?php else: ?>
                                        <span class="pill-badge pill-danger"><?= htmlspecialchars($ord->status) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding:12px; text-align:right; color:var(--text-muted);">$<?= number_format($ord->subtotal, 2) ?></td>
                                <td style="padding:12px; text-align:right; font-weight:700;">$<?= number_format($ord->grand_total, 2) ?></td>
                                <td style="padding:12px; text-align:right;">
                                    <a href="<?= APP_URL ?>/portal/order_details/<?= $ord->id ?>" class="btn-primary" style="padding: 4px 8px; font-size:11px; border-radius:4px;">View Invoice</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
