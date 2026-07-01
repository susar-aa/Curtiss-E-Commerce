<style>
    .item-layout {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 40px;
        margin-top: 15px;
    }
    @media (max-width: 768px) {
        .item-layout { grid-template-columns: 1fr; }
    }

    .item-gallery {
        background: var(--card-bg);
        border: 1px solid var(--card-border);
        border-radius: var(--rounded);
        height: 420px;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        box-shadow: var(--card-shadow);
    }
    .item-gallery img {
        max-width: 90%;
        max-height: 90%;
        object-fit: contain;
    }

    .item-details-panel {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }
    .item-category-path {
        font-size: 12px;
        text-transform: uppercase;
        font-weight: 700;
        color: var(--text-accent);
        letter-spacing: 0.5px;
    }
    .item-name-heading {
        font-size: 28px;
        font-weight: 800;
        color: var(--text-main);
        line-height: 1.2;
    }
    .item-price-tag {
        font-size: 24px;
        font-weight: 800;
        color: var(--text-accent);
    }

    .review-bubble {
        background: rgba(0,0,0,0.01);
        border: 1px solid var(--card-border);
        border-radius: 10px;
        padding: 15px;
        display: flex;
        flex-direction: column;
        gap: 5px;
    }
    @media (prefers-color-scheme: dark) {
        .review-bubble { background: rgba(255,255,255,0.02); }
    }
</style>

<div class="browse-layout" style="margin-top: 15px; grid-template-columns: 1fr;">
    <div style="margin-bottom: 20px;">
        <a href="<?= APP_URL ?>/shop/category" style="text-decoration:none; color:var(--text-muted); font-size:13.5px; font-weight:600;"><i class="ph ph-arrow-left"></i> Back to Catalog</a>
    </div>

    <div class="item-layout">
        <!-- Gallery image -->
        <div class="item-gallery">
            <?php if(!empty($data['item']->image_path)): ?>
                <img src="<?= APP_URL ?>/uploads/products/<?= htmlspecialchars($data['item']->image_path) ?>" alt="Product graph">
            <?php else: ?>
                <i class="ph ph-image" style="font-size: 80px; color:#ccc;"></i>
            <?php endif; ?>
        </div>

        <!-- Details -->
        <div class="item-details-panel">
            <span class="item-category-path"><?= htmlspecialchars($data['item']->category_name ?? 'Stationery') ?></span>
            <h1 class="item-name-heading"><?= htmlspecialchars($data['item']->name) ?></h1>
            <span style="font-size: 12px; color: var(--text-muted);">SKU Reference: <code><?= htmlspecialchars($data['item']->item_code) ?></code></span>

            <!-- Pricing display -->
            <div style="border-top:1px solid var(--mega-divider); border-bottom:1px solid var(--mega-divider); padding: 15px 0;">
                <?php 
                    $price = ($_SESSION['ec_role'] ?? 'guest') === 'wholesaler' ? $data['item']->wholesale_price : $data['item']->price;
                ?>
                <span class="item-price-tag">$<?= number_format($price, 2) ?></span>
                <span style="font-size: 11.5px; display:block; color:var(--text-muted); margin-top:4px;">
                    Price Type: <strong><?= ($_SESSION['ec_role'] ?? 'guest') === 'wholesaler' ? 'Wholesale Tier Account' : 'Retail Standard' ?></strong>
                </span>
            </div>

            <!-- Stock visibility -->
            <?php if(intval($data['item']->online_stock_visible) === 1): ?>
                <div style="font-size: 13px;">
                    Stock Available: 
                    <?php if(intval($data['item']->qty) > 0): ?>
                        <span class="pill-badge pill-success" style="font-size:9px;">In Stock (<?= $data['item']->qty ?> available)</span>
                    <?php else: ?>
                        <span class="pill-badge pill-danger" style="font-size:9px;">Temporarily Sold Out</span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Add to cart form -->
            <?php if(intval($data['item']->qty) > 0): ?>
                <form action="<?= APP_URL ?>/shop/cart" method="POST" style="margin-top: 10px;">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="item_id" value="<?= $data['item']->id ?>">
                    
                    <div style="display:flex; gap:12px; align-items:center;">
                        <div class="form-box" style="margin-bottom:0; width: 100px;">
                            <label>Quantity</label>
                            <input type="number" name="qty" class="form-control" value="1" min="1" max="<?= $data['item']->qty ?>" style="text-align:center;">
                        </div>
                        <button type="submit" class="btn-primary" style="height: 42px; margin-top:20px; flex: 1;">
                            <i class="ph ph-shopping-cart-simple"></i> Add to Shopping Cart
                        </button>
                    </div>
                </form>
            <?php endif; ?>

            <!-- Wishlist form -->
            <?php if(isset($_SESSION['ec_user_id'])): ?>
                <form action="<?= APP_URL ?>/portal/wishlist" method="POST" style="margin-top: 5px;">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="item_id" value="<?= $data['item']->id ?>">
                    <button type="submit" class="btn-secondary" style="width: 100%; height: 38px;">
                        <i class="ph ph-heart"></i> Add to Wishlist
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Description / Specifications -->
    <div class="card" style="margin-top: 40px;">
        <h3 style="font-size:16px; border-bottom:1px solid var(--mega-divider); padding-bottom:10px; margin-bottom:15px;">Product Overview</h3>
        <p style="font-size: 14px; line-height: 1.6; color:var(--text-main);"><?= nl2br(htmlspecialchars($data['item']->description ?: 'No overview available for this product.')) ?></p>
    </div>

    <!-- Reviews panel -->
    <div style="margin-top: 40px; display:grid; grid-template-columns: 1fr 1fr; gap:30px;">
        <!-- Read Reviews -->
        <div class="card">
            <h3 style="font-size:16px; border-bottom:1px solid var(--mega-divider); padding-bottom:10px; margin-bottom:15px;">Customer Reviews (<?= count($data['reviews']) ?>)</h3>
            <div style="display:flex; flex-direction:column; gap:15px;">
                <?php if(empty($data['reviews'])): ?>
                    <p style="color:var(--text-muted); font-size:13px; text-align:center; padding: 20px;">No verified reviews yet. Be the first to leave feedback!</p>
                <?php else: ?>
                    <?php foreach($data['reviews'] as $r): ?>
                        <div class="review-bubble">
                            <div style="display:flex; justify-content:space-between; align-items:center;">
                                <strong style="font-size:13.5px;"><?= htmlspecialchars($r->customer_name) ?></strong>
                                <span style="font-size:10.5px; color:var(--text-muted);"><?= date('M d, Y', strtotime($r->created_at)) ?></span>
                            </div>
                            <div style="color: #ffcc00; font-size: 12px; margin-top:2px;">
                                <?php for($i=1; $i<=5; $i++): ?>
                                    <i class="ph-fill ph-star" style="color: <?= ($i <= $r->rating) ? '#ffcc00' : 'rgba(0,0,0,0.1)' ?>;"></i>
                                <?php endfor; ?>
                            </div>
                            <p style="font-size:12.5px; font-style:italic; color:#555; margin-top:5px;">"<?= htmlspecialchars($r->review_text) ?>"</p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Write a Review -->
        <div class="card">
            <h3 style="font-size:16px; border-bottom:1px solid var(--mega-divider); padding-bottom:10px; margin-bottom:15px;">Submit Your Feedback</h3>
            <form action="<?= APP_URL ?>/shop/submit_review" method="POST">
                <input type="hidden" name="item_id" value="<?= $data['item']->id ?>">

                <div class="form-box">
                    <label>Your Display Name</label>
                    <input type="text" name="reviewer_name" class="form-control" required placeholder="e.g. Jane Doe" value="<?= htmlspecialchars($_SESSION['ec_name'] ?? '') ?>">
                </div>

                <div class="form-box">
                    <label>Your Email Address (For validation)</label>
                    <input type="email" name="reviewer_email" class="form-control" required placeholder="e.g. jane@example.com">
                </div>

                <div class="form-box">
                    <label>Product Rating (Stars)</label>
                    <select name="rating" class="form-control">
                        <option value="5">⭐⭐⭐⭐⭐ (5 - Excellent)</option>
                        <option value="4">⭐⭐⭐⭐ (4 - Very Good)</option>
                        <option value="3">⭐⭐⭐ (3 - Average)</option>
                        <option value="2">⭐⭐ (2 - Poor)</option>
                        <option value="1">⭐ (1 - Terrible)</option>
                    </select>
                </div>

                <div class="form-box">
                    <label>Review Commentary</label>
                    <textarea name="comment" class="form-control" rows="4" required placeholder="Describe your experience with this item..."></textarea>
                </div>

                <button type="submit" class="btn-primary" style="width:100%; margin-top:10px;">Submit Moderated Review</button>
            </form>
        </div>
    </div>

    <!-- Related items -->
    <?php if(!empty($data['related'])): ?>
        <div style="margin-top: 50px;">
            <h3 style="font-size:16px; border-bottom:1px solid var(--mega-divider); padding-bottom:10px; margin-bottom:20px;">You Might Also Like</h3>
            <div class="product-showcase-grid">
                <?php foreach($data['related'] as $relItem): 
                    $relPrice = ($_SESSION['ec_role'] ?? 'guest') === 'wholesaler' ? $relItem->wholesale_price : $relItem->price;
                ?>
                    <a href="<?= APP_URL ?>/shop/item/<?= $relItem->id ?>" class="prod-showcase-card">
                        <div class="prod-image-wrapper">
                            <?php if(!empty($relItem->image_path)): ?>
                                <img src="<?= APP_URL ?>/uploads/products/<?= htmlspecialchars($relItem->image_path) ?>" alt="Product image">
                            <?php else: ?>
                                <i class="ph ph-image" style="font-size: 32px; color:#ccc;"></i>
                            <?php endif; ?>
                        </div>
                        <div class="prod-info-box">
                            <span class="prod-name" style="font-size:13px;"><?= htmlspecialchars($relItem->name) ?></span>
                            <div class="prod-price-row">
                                <span class="prod-price" style="font-size:14px;">$<?= number_format($relPrice, 2) ?></span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
