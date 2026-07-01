<style>
    .portal-layout {
        display: grid;
        grid-template-columns: 240px 1fr;
        gap: 30px;
        margin-top: 15px;
    }
    @media (max-width: 768px) {
        .portal-layout { grid-template-columns: 1fr; }
    }

    /* Portal navigation menu */
    .portal-menu-card {
        background: var(--card-bg);
        border: 1px solid var(--card-border);
        border-radius: var(--rounded);
        padding: 20px;
        box-shadow: var(--card-shadow);
        height: fit-content;
    }
    .portal-menu-list {
        list-style: none;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    .portal-menu-link {
        color: var(--text-main);
        text-decoration: none;
        font-size: 14px;
        font-weight: 500;
        padding: 10px 12px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        gap: 10px;
        transition: all 0.2s;
    }
    .portal-menu-link:hover, .portal-menu-link.active {
        background: rgba(0, 118, 255, 0.08);
        color: var(--text-accent);
        font-weight: 600;
    }

    /* Grid counters */
    .portal-stats-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
        margin-bottom: 30px;
    }
    @media (max-width: 576px) {
        .portal-stats-grid { grid-template-columns: 1fr; }
    }
    .stat-counter-card {
        background: var(--card-bg);
        border: 1px solid var(--card-border);
        border-radius: var(--rounded);
        padding: 20px;
        box-shadow: var(--card-shadow);
        display: flex;
        align-items: center;
        gap: 15px;
    }
    .stat-icon {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        background: rgba(0,118,255,0.06);
        color: var(--text-accent);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
    }
</style>

<div class="portal-layout">
    <!-- Sidebar Menu navigation -->
    <div class="portal-menu-card">
        <h4 style="font-size: 11px; text-transform:uppercase; color:var(--text-muted); font-weight:700; margin-bottom: 15px; letter-spacing:0.5px;">Navigation Menu</h4>
        <ul class="portal-menu-list">
            <li><a href="<?= APP_URL ?>/portal" class="portal-menu-link active"><i class="ph ph-squares-four"></i> Dashboard</a></li>
            <li><a href="<?= APP_URL ?>/portal/orders" class="portal-menu-link"><i class="ph ph-receipt"></i> Order History</a></li>
            <li><a href="<?= APP_URL ?>/portal/wishlist" class="portal-menu-link"><i class="ph ph-heart"></i> My Wishlist</a></li>
            <li><a href="<?= APP_URL ?>/portal/returns" class="portal-menu-link"><i class="ph ph-arrow-counter-clockwise"></i> Return Requests</a></li>
            <li><a href="<?= APP_URL ?>/portal/profile" class="portal-menu-link"><i class="ph ph-user-gear"></i> Profile Settings</a></li>
        </ul>
    </div>

    <!-- Main Workspace Content -->
    <div style="display:flex; flex-direction:column; gap:25px;">
        <div class="card" style="padding: 24px;">
            <h2 style="font-size: 20px; font-weight: 800; color:var(--text-main);">Welcome Back, <?= htmlspecialchars($data['customer']->name) ?>!</h2>
            <p style="color:var(--text-muted); font-size:13.5px; margin-top:4px;">Manage transactions, return authorizations, and account specifications directly from your partner portal ledger.</p>
        </div>

        <!-- Summary Stats grid -->
        <div class="portal-stats-grid">
            <div class="stat-counter-card">
                <div class="stat-icon"><i class="ph ph-receipt"></i></div>
                <div>
                    <span style="font-size: 22px; font-weight:800; color:var(--text-main);"><?= count($data['recent_orders']) ?></span>
                    <span style="display:block; font-size:12px; color:var(--text-muted);">Total Orders</span>
                </div>
            </div>
            <div class="stat-counter-card">
                <div class="stat-icon" style="color: #af52de; background: rgba(175,82,222,0.06);"><i class="ph ph-heart"></i></div>
                <div>
                    <span style="font-size: 22px; font-weight:800; color:var(--text-main);"><?= $data['wish_count'] ?></span>
                    <span style="display:block; font-size:12px; color:var(--text-muted);">Wishlist Items</span>
                </div>
            </div>
            <div class="stat-counter-card">
                <div class="stat-icon" style="color: #ff9500; background: rgba(255,149,0,0.06);"><i class="ph ph-arrow-counter-clockwise"></i></div>
                <div>
                    <span style="font-size: 22px; font-weight:800; color:var(--text-main);"><?= count($data['returns']) ?></span>
                    <span style="display:block; font-size:12px; color:var(--text-muted);">Return Requests</span>
                </div>
            </div>
        </div>

        <!-- Recent orders list -->
        <div class="card">
            <h3 style="font-size: 15px; font-weight:700; border-bottom:1px solid var(--mega-divider); padding-bottom:10px; margin-bottom:15px;">Recent Orders</h3>
            
            <?php if(empty($data['recent_orders'])): ?>
                <p style="text-align:center; padding: 30px; color:var(--text-muted); font-size:13.5px;">No recent transactions located in ledger.</p>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table style="width:100%; border-collapse:collapse; font-size:13.5px; text-align:left;">
                        <thead>
                            <tr style="border-bottom:1px solid var(--card-border); color:var(--text-muted); font-weight:600;">
                                <th style="padding:10px;">Order No.</th>
                                <th style="padding:10px;">Date</th>
                                <th style="padding:10px;">Status</th>
                                <th style="padding:10px; text-align:right;">Amount</th>
                                <th style="padding:10px; text-align:right; width:80px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($data['recent_orders'] as $ord): ?>
                                <tr style="border-bottom:1px solid var(--mega-divider);">
                                    <td style="padding:12px; font-family:monospace;"><?= htmlspecialchars($ord->order_number) ?></td>
                                    <td style="padding:12px;"><?= date('M d, Y', strtotime($ord->order_date)) ?></td>
                                    <td style="padding:12px;">
                                        <?php if($ord->status === 'Delivered'): ?>
                                            <span class="pill-badge pill-success">Delivered</span>
                                        <?php elseif($ord->status === 'Pending'): ?>
                                            <span class="pill-badge pill-warning">Pending</span>
                                        <?php else: ?>
                                            <span class="pill-badge pill-danger"><?= htmlspecialchars($ord->status) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding:12px; text-align:right; font-weight:700;">$<?= number_format($ord->grand_total, 2) ?></td>
                                    <td style="padding:12px; text-align:right;">
                                        <a href="<?= APP_URL ?>/portal/order_details/<?= $ord->id ?>" class="btn-primary" style="padding: 4px 8px; font-size:11px; border-radius:4px;">Invoice</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
