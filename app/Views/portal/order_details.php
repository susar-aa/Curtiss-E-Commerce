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
    <div class="card" id="invoice-print-area">
        <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--mega-divider); padding-bottom:15px; margin-bottom:20px;">
            <div>
                <h2 style="font-size: 20px; font-weight:800; color:var(--text-main);"><?= htmlspecialchars($data['settings']['store_name'] ?? 'Curtiss Stationery') ?></h2>
                <span style="font-size:12px; color:var(--text-muted);"><?= htmlspecialchars($data['settings']['contact_email'] ?? '') ?> | <?= htmlspecialchars($data['settings']['contact_phone'] ?? '') ?></span>
            </div>
            <div style="text-align:right;">
                <span class="pill-badge pill-success" style="font-size:11px;"><?= htmlspecialchars($data['order']->status) ?></span>
                <span style="display:block; font-size:12px; font-family:monospace; margin-top:4px;"><?= htmlspecialchars($data['order']->order_number) ?></span>
            </div>
        </div>

        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; font-size:13.5px; margin-bottom:30px;">
            <div>
                <strong style="color:var(--text-muted); text-transform:uppercase; font-size:11px; display:block; margin-bottom:5px;">Billing Information</strong>
                <span style="font-weight:600; display:block;"><?= htmlspecialchars($data['order']->customer_name) ?></span>
                <span>Contact Phone: <?= htmlspecialchars($data['order']->customer_phone) ?></span>
            </div>
            <div>
                <strong style="color:var(--text-muted); text-transform:uppercase; font-size:11px; display:block; margin-bottom:5px;">Order & Dispatch Details</strong>
                <span>Order Date: <?= date('F d, Y', strtotime($data['order']->order_date)) ?></span><br>
                <span>Dispatch Details: <?= htmlspecialchars($data['order']->notes) ?></span>
            </div>
        </div>

        <!-- Items Table -->
        <table style="width:100%; border-collapse:collapse; font-size:13.5px; text-align:left; margin-bottom:25px;">
            <thead>
                <tr style="border-bottom:1px solid var(--card-border); background:rgba(0,0,0,0.01); color:var(--text-muted); font-weight:600;">
                    <th style="padding:10px;">Product Description</th>
                    <th style="padding:10px;">SKU Reference</th>
                    <th style="padding:10px; text-align:right;">Unit Price</th>
                    <th style="padding:10px; text-align:center; width:80px;">Qty</th>
                    <th style="padding:10px; text-align:right; width:100px;">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($data['items'] as $item): ?>
                    <tr style="border-bottom:1px solid var(--mega-divider);">
                        <td style="padding:12px; font-weight:600;"><?= htmlspecialchars($item->name) ?></td>
                        <td style="padding:12px; font-family:monospace;"><?= htmlspecialchars($item->sku) ?></td>
                        <td style="padding:12px; text-align:right;">$<?= number_format($item->billing_price, 2) ?></td>
                        <td style="padding:12px; text-align:center;"><?= $item->qty ?></td>
                        <td style="padding:12px; text-align:right; font-weight:700;">$<?= number_format($item->total, 2) ?></td>
                    </tr>
                <?php endforeach; ?>
                
                <tr>
                    <td colspan="3"></td>
                    <td style="padding:10px; text-align:right; font-weight:600; color:var(--text-muted);">Subtotal:</td>
                    <td style="padding:10px; text-align:right; font-weight:600;">$<?= number_format($data['order']->subtotal, 2) ?></td>
                </tr>
                <?php if(floatval($data['order']->discount) > 0): ?>
                    <tr>
                        <td colspan="3"></td>
                        <td style="padding:10px; text-align:right; font-weight:600; color:var(--text-muted);">Discount Applied:</td>
                        <td style="padding:10px; text-align:right; font-weight:600; color: #ff3b30;">-$<?= number_format($data['order']->discount, 2) ?></td>
                    </tr>
                <?php endif; ?>
                <tr style="border-top:1px dashed var(--card-border);">
                    <td colspan="3"></td>
                    <td style="padding:15px 10px; text-align:right; font-weight:800; font-size:16px; color:var(--text-main);">Grand Total:</td>
                    <td style="padding:15px 10px; text-align:right; font-weight:800; font-size:18px; color:var(--text-accent);">$<?= number_format($data['order']->grand_total, 2) ?></td>
                </tr>
            </tbody>
        </table>

        <div style="display:flex; justify-content:space-between; align-items:center;">
            <button type="button" class="btn-secondary" onclick="window.print()"><i class="ph ph-printer"></i> Print Invoice Receipt</button>
            <a href="<?= APP_URL ?>/portal/orders" class="btn-primary">Return to Order History</a>
        </div>
    </div>
</div>
