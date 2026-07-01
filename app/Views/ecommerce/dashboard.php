<style>
    /* Premium Dashboard Specific Style Injections */
    .kpi-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    .kpi-card {
        background: var(--card-bg);
        border: 1px solid var(--card-border);
        border-radius: 16px;
        padding: 20px;
        box-shadow: var(--card-shadow);
        backdrop-filter: blur(var(--glass-blur));
        -webkit-backdrop-filter: blur(var(--glass-blur));
        transition: transform 0.2s, box-shadow 0.2s;
        display: flex;
        align-items: center;
        gap: 15px;
    }
    .kpi-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
    }
    .kpi-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        color: #fff;
    }
    .bg-blue { background: linear-gradient(135deg, #007aff, #0056b3); }
    .bg-orange { background: linear-gradient(135deg, #ff9500, #c77400); }
    .bg-green { background: linear-gradient(135deg, #34c759, #208739); }
    .bg-purple { background: linear-gradient(135deg, #af52de, #7a329e); }
    .bg-red { background: linear-gradient(135deg, #ff3b30, #b82017); }

    .kpi-details {
        display: flex;
        flex-direction: column;
    }
    .kpi-val {
        font-size: 22px;
        font-weight: 700;
        color: var(--text-main);
        line-height: 1.2;
    }
    .kpi-label {
        font-size: 12px;
        color: var(--text-muted);
        font-weight: 500;
        margin-top: 2px;
    }

    .dashboard-layout {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 25px;
    }
    @media (max-width: 1024px) {
        .dashboard-layout {
            grid-template-columns: 1fr;
        }
    }

    .panel-card {
        background: var(--card-bg);
        border: 1px solid var(--card-border);
        border-radius: 16px;
        padding: 22px;
        box-shadow: var(--card-shadow);
        backdrop-filter: blur(var(--glass-blur));
        margin-bottom: 25px;
    }
    .panel-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 18px;
        border-bottom: 1px solid var(--mega-divider);
        padding-bottom: 10px;
    }
    .panel-header h3 {
        font-size: 15px;
        font-weight: 700;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .panel-header h3 i {
        font-size: 18px;
        color: var(--text-accent);
    }

    /* List items styling */
    .item-list-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 0;
        border-bottom: 1px solid var(--mega-divider);
    }
    .item-list-row:last-child {
        border-bottom: none;
    }
    .item-detail-main {
        display: flex;
        flex-direction: column;
        gap: 3px;
        flex: 1;
    }
    .item-name {
        font-size: 13.5px;
        font-weight: 600;
        color: var(--text-main);
    }
    .item-sub {
        font-size: 11px;
        color: var(--text-muted);
    }
    .metric-bar-container {
        width: 100px;
        background: rgba(0,0,0,0.05);
        border-radius: 6px;
        height: 6px;
        overflow: hidden;
        margin-top: 4px;
    }
    @media (prefers-color-scheme: dark) {
        .metric-bar-container { background: rgba(255,255,255,0.08); }
    }
    .metric-bar-fill {
        height: 100%;
        background: var(--text-accent);
        border-radius: 6px;
    }

    .pill-badge {
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
    }
    .pill-warning { background: rgba(255,149,0,0.15); color: #ff9500; }
    .pill-danger { background: rgba(255,59,48,0.15); color: #ff3b30; }
    .pill-success { background: rgba(52,199,89,0.15); color: #34c759; }

    .rating-stars {
        color: #ffcc00;
        font-size: 13px;
    }
</style>

<div class="header-actions" style="margin-bottom: 25px;">
    <h2 style="font-weight: 800; font-size: 24px; letter-spacing: -0.5px;">E-Commerce Administration</h2>
    <p style="color: var(--text-muted); margin-top: 4px;">Monitor storefront performance, manage customer pipelines, catalog configurations and promotional rules.</p>
</div>

<!-- KPI Indicators Grid -->
<div class="kpi-grid">
    <div class="kpi-card">
        <div class="kpi-icon bg-blue">
            <i class="ph ph-shopping-bag"></i>
        </div>
        <div class="kpi-details">
            <div class="kpi-val"><?= $data['todayOrders'] ?></div>
            <div class="kpi-label">Today's Orders</div>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon bg-orange">
            <i class="ph ph-hourglass"></i>
        </div>
        <div class="kpi-details">
            <div class="kpi-val"><?= $data['pendingOrders'] ?></div>
            <div class="kpi-label">Pending Approval</div>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon bg-green">
            <i class="ph ph-currency-dollar"></i>
        </div>
        <div class="kpi-details">
            <div class="kpi-val">$<?= number_format($data['totalSales'], 2) ?></div>
            <div class="kpi-label">Total Online Revenue</div>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon bg-purple">
            <i class="ph ph-users"></i>
        </div>
        <div class="kpi-details">
            <div class="kpi-val"><?= $data['totalVisitors'] ?></div>
            <div class="kpi-label">Unique Online Visitors</div>
        </div>
    </div>
</div>

<div class="dashboard-layout">
    <!-- Left column: Sales details and catalogue alerts -->
    <div>
        <!-- Top Selling Products -->
        <div class="panel-card">
            <div class="panel-header">
                <h3><i class="ph ph-trend-up"></i> Top Selling Online Products</h3>
                <a href="<?= APP_URL ?>/ecommerce/reports?report_type=product_performance" style="font-size: 12px; color: var(--text-accent); text-decoration: none; font-weight: 600;">View Report &rarr;</a>
            </div>
            
            <?php if(empty($data['topProducts'])): ?>
                <div style="text-align: center; color: var(--text-muted); padding: 30px; font-size: 13px;">No online sales recorded yet.</div>
            <?php else: ?>
                <?php 
                $maxSales = 1;
                foreach($data['topProducts'] as $tp) {
                    if($tp->total_qty > $maxSales) $maxSales = $tp->total_qty;
                }
                foreach($data['topProducts'] as $tp): 
                    $pct = ($tp->total_qty / $maxSales) * 100;
                ?>
                    <div class="item-list-row">
                        <div class="item-detail-main">
                            <span class="item-name"><?= htmlspecialchars($tp->name) ?></span>
                            <span class="item-sub">SKU: <?= htmlspecialchars($tp->sku) ?></span>
                            <div class="metric-bar-container">
                                <div class="metric-bar-fill" style="width: <?= $pct ?>%;"></div>
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <strong style="font-size: 14px; color: var(--text-main);"><?= $tp->total_qty ?> Units</strong>
                            <div style="font-size: 11px; color: var(--text-muted); margin-top: 2px;">$<?= number_format($tp->total_sales, 2) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Abandoned Shopping Carts -->
        <div class="panel-card">
            <div class="panel-header">
                <h3><i class="ph ph-shopping-cart-simple"></i> Abandoned Shopping Carts</h3>
                <a href="<?= APP_URL ?>/ecommerce/reports?report_type=abandoned_carts" style="font-size: 12px; color: var(--text-accent); text-decoration: none; font-weight: 600;">View Carts &rarr;</a>
            </div>

            <?php if(empty($data['abandonedCarts'])): ?>
                <div style="text-align: center; color: var(--text-muted); padding: 30px; font-size: 13px;">No active abandoned carts detected.</div>
            <?php else: ?>
                <?php foreach($data['abandonedCarts'] as $cart): 
                    $items = json_decode($cart->cart_data, true) ?: [];
                    $totalItems = count($items);
                    $cartVal = 0;
                    foreach($items as $i) { $cartVal += ($i['price'] * $i['qty']); }
                ?>
                    <div class="item-list-row">
                        <div class="item-detail-main">
                            <span class="item-name"><?= htmlspecialchars($cart->customer_name ?: 'Guest Buyer') ?></span>
                            <span class="item-sub">Type: <?= ucfirst($cart->customer_type) ?> | Last Active: <?= date('M d, Y H:i', strtotime($cart->updated_at)) ?></span>
                        </div>
                        <div style="text-align: right;">
                            <strong style="font-size: 14px; color: var(--text-main);">$<?= number_format($cartVal, 2) ?></strong>
                            <div style="font-size: 11px; color: var(--text-muted); margin-top: 2px;"><?= $totalItems ?> items in cart</div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Right column: Reviews, alerts and registrations -->
    <div>
        <!-- Low Stock Alerts -->
        <div class="panel-card">
            <div class="panel-header">
                <h3><i class="ph ph-warning"></i> Low Stock Alerts</h3>
            </div>
            
            <?php if(empty($data['lowStock'])): ?>
                <div style="text-align: center; color: #208739; padding: 20px; font-size: 12px; font-weight: 600;">
                    <i class="ph ph-check-circle" style="vertical-align: middle;"></i> All products healthy.
                </div>
            <?php else: ?>
                <?php foreach($data['lowStock'] as $ls): ?>
                    <div class="item-list-row">
                        <div class="item-detail-main">
                            <span class="item-name"><?= htmlspecialchars($ls->name) ?></span>
                            <span class="item-sub">SKU: <?= htmlspecialchars($ls->sku) ?></span>
                        </div>
                        <div style="text-align: right;">
                            <span class="pill-badge <?= ($ls->qty <= 0) ? 'pill-danger' : 'pill-warning' ?>">
                                <?= $ls->qty ?> <?= htmlspecialchars($ls->unit ?? 'pcs') ?> Left
                            </span>
                            <div style="font-size: 10px; color: var(--text-muted); margin-top: 4px;">Min: <?= $ls->alert_qty ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Recent Customer Reviews -->
        <div class="panel-card">
            <div class="panel-header">
                <h3><i class="ph ph-chats-teardrop"></i> Recent Customer Reviews</h3>
                <a href="<?= APP_URL ?>/ecommerce/reviews" style="font-size: 12px; color: var(--text-accent); text-decoration: none; font-weight: 600;">Moderate &rarr;</a>
            </div>

            <?php if(empty($data['recentReviews'])): ?>
                <div style="text-align: center; color: var(--text-muted); padding: 30px; font-size: 13px;">No product reviews pending review.</div>
            <?php else: ?>
                <?php foreach($data['recentReviews'] as $rev): ?>
                    <div class="item-list-row">
                        <div class="item-detail-main">
                            <span class="item-name"><?= htmlspecialchars($rev->reviewer_name) ?></span>
                            <span class="item-sub"><?= htmlspecialchars($rev->item_name) ?></span>
                            <div class="rating-stars" style="margin-top: 3px;">
                                <?php for($i=1; $i<=5; $i++): ?>
                                    <i class="ph-fill ph-star" style="color: <?= ($i <= $rev->rating) ? '#ffcc00' : 'rgba(0,0,0,0.1)' ?>;"></i>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <span class="pill-badge" style="background: rgba(0,0,0,0.05); color: var(--text-muted);">
                                <?= ucfirst($rev->status) ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
