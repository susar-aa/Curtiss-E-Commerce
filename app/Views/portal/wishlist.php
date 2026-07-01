<div class="portal-layout">
    <!-- Sidebar Menu navigation -->
    <div class="portal-menu-card">
        <h4 style="font-size: 11px; text-transform:uppercase; color:var(--text-muted); font-weight:700; margin-bottom: 15px; letter-spacing:0.5px;">Navigation Menu</h4>
        <ul class="portal-menu-list">
            <li><a href="<?= APP_URL ?>/portal" class="portal-menu-link"><i class="ph ph-squares-four"></i> Dashboard</a></li>
            <li><a href="<?= APP_URL ?>/portal/orders" class="portal-menu-link"><i class="ph ph-receipt"></i> Order History</a></li>
            <li><a href="<?= APP_URL ?>/portal/wishlist" class="portal-menu-link active"><i class="ph ph-heart"></i> My Wishlist</a></li>
            <li><a href="<?= APP_URL ?>/portal/returns" class="portal-menu-link"><i class="ph ph-arrow-counter-clockwise"></i> Return Requests</a></li>
            <li><a href="<?= APP_URL ?>/portal/profile" class="portal-menu-link"><i class="ph ph-user-gear"></i> Profile Settings</a></li>
        </ul>
    </div>

    <!-- Main Workspace Content -->
    <div class="card">
        <h3 style="font-size: 16px; font-weight:700; border-bottom:1px solid var(--mega-divider); padding-bottom:10px; margin-bottom:15px;">My Wishlist</h3>
        
        <?php if(empty($data['items'])): ?>
            <p style="text-align:center; padding: 40px; color:var(--text-muted); font-size:13.5px;">Your wishlist is currently empty.</p>
        <?php else: ?>
            <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 20px; margin-top: 15px;">
                <?php foreach($data['items'] as $wish): 
                    $price = ($_SESSION['ec_role'] ?? 'guest') === 'wholesaler' ? $wish->wholesale_price : $wish->price;
                ?>
                    <div class="prod-showcase-card" style="height: 100%;">
                        <div class="prod-image-wrapper">
                            <?php if(!empty($wish->image_path)): ?>
                                <img src="<?= APP_URL ?>/uploads/products/<?= htmlspecialchars($wish->image_path) ?>" alt="Product graph">
                            <?php else: ?>
                                <i class="ph ph-image" style="font-size: 32px; color:#ccc;"></i>
                            <?php endif; ?>
                        </div>
                        <div class="prod-info-box">
                            <span class="prod-name" style="font-size:13px;"><?= htmlspecialchars($wish->item_name) ?></span>
                            <div class="prod-price-row">
                                <span class="prod-price" style="font-size:14.5px;">$<?= number_format($price, 2) ?></span>
                            </div>
                            
                            <div style="display:flex; gap:10px; margin-top: 10px;">
                                <a href="<?= APP_URL ?>/shop/item/<?= $wish->item_id ?>" class="btn-primary" style="flex:1; padding: 6px 10px; font-size:11px; border-radius:6px;">View details</a>
                                
                                <form action="<?= APP_URL ?>/portal/wishlist" method="POST">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="item_id" value="<?= $wish->item_id ?>">
                                    <button type="submit" class="btn-secondary" style="border-color: #ff3b30; color: #ff3b30; padding:6px 10px; font-size:11px; border-radius:6px;">
                                        <i class="ph ph-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
