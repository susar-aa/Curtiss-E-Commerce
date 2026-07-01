<style>
    /* Carousel styling */
    .hero-slider {
        position: relative;
        height: 400px;
        border-radius: var(--rounded);
        overflow: hidden;
        margin-bottom: 40px;
        border: 1px solid var(--card-border);
        box-shadow: var(--card-shadow);
    }
    .slide-item {
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        background-size: cover;
        background-position: center;
        display: none;
        align-items: center;
        padding: 60px;
        box-sizing: border-box;
    }
    .slide-item.active {
        display: flex;
    }
    .slide-item::before {
        content: "";
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        background: linear-gradient(90deg, rgba(0,0,0,0.65) 0%, rgba(0,0,0,0.2) 100%);
    }
    .slide-content {
        position: relative;
        z-index: 10;
        color: #fff;
        max-width: 550px;
        display: flex;
        flex-direction: column;
        gap: 15px;
    }
    .slide-title {
        font-size: 38px;
        font-weight: 800;
        line-height: 1.2;
    }
    .slide-desc {
        font-size: 16px;
        line-height: 1.5;
        opacity: 0.9;
    }

    /* Sections general styling */
    .shop-section {
        margin-bottom: 50px;
    }
    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
        border-bottom: 1px solid var(--mega-divider);
        padding-bottom: 12px;
    }
    .section-header h2 {
        font-size: 20px;
        font-weight: 800;
        letter-spacing: -0.5px;
        color: var(--text-main);
    }

    /* Category grid styling */
    .cat-highlight-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 20px;
    }
    .cat-highlight-card {
        background: var(--card-bg);
        border: 1px solid var(--card-border);
        border-radius: var(--rounded);
        padding: 24px;
        text-align: center;
        text-decoration: none;
        color: inherit;
        box-shadow: var(--card-shadow);
        transition: transform 0.2s, border-color 0.2s;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 12px;
    }
    .cat-highlight-card:hover {
        transform: translateY(-4px);
        border-color: var(--text-accent);
    }
    .cat-icon-circle {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: rgba(0, 118, 255, 0.08);
        color: var(--text-accent);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
        transition: background 0.2s, color 0.2s;
    }
    .cat-highlight-card:hover .cat-icon-circle {
        background: var(--text-accent);
        color: #fff;
    }

    /* Product card grids styling */
    .product-showcase-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 25px;
    }
    .prod-showcase-card {
        background: var(--card-bg);
        border: 1px solid var(--card-border);
        border-radius: var(--rounded);
        overflow: hidden;
        box-shadow: var(--card-shadow);
        text-decoration: none;
        color: inherit;
        display: flex;
        flex-direction: column;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .prod-showcase-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.08);
    }
    .prod-image-wrapper {
        height: 180px;
        background: rgba(0,0,0,0.01);
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        border-bottom: 1px solid var(--card-border);
        position: relative;
    }
    .prod-image-wrapper img {
        max-width: 90%;
        max-height: 90%;
        object-fit: contain;
    }
    .prod-info-box {
        padding: 16px;
        display: flex;
        flex-direction: column;
        flex: 1;
        gap: 6px;
    }
    .prod-name {
        font-size: 14.5px;
        font-weight: 700;
        color: var(--text-main);
        line-height: 1.3;
    }
    .prod-sku {
        font-size: 11px;
        color: var(--text-muted);
    }
    .prod-price-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: auto;
        padding-top: 10px;
    }
    .prod-price {
        font-size: 16px;
        font-weight: 800;
        color: var(--text-accent);
    }

    /* Blog posts roll styling */
    .blog-showcase-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 25px;
    }
    .blog-showcase-card {
        background: var(--card-bg);
        border: 1px solid var(--card-border);
        border-radius: var(--rounded);
        overflow: hidden;
        box-shadow: var(--card-shadow);
        text-decoration: none;
        color: inherit;
        display: flex;
        flex-direction: column;
        transition: transform 0.2s;
    }
    .blog-showcase-card:hover {
        transform: translateY(-4px);
    }
    .blog-showcase-img {
        height: 140px;
        background: #eee;
        background-size: cover;
        background-position: center;
    }
    .blog-showcase-body {
        padding: 15px;
        display: flex;
        flex-direction: column;
        gap: 6px;
        flex: 1;
    }
    .blog-showcase-title {
        font-size: 14px;
        font-weight: 700;
        color: var(--text-main);
    }
</style>

<!-- Banners carousel / hero area -->
<?php if(!empty($data['banners'])): ?>
    <div class="hero-slider">
        <?php foreach($data['banners'] as $idx => $b): ?>
            <div class="slide-item <?= $idx === 0 ? 'active' : '' ?>" style="background-image: url('<?= APP_URL ?>/uploads/banners/<?= htmlspecialchars($b->image_path) ?>');">
                <div class="slide-content">
                    <span class="pill-badge pill-success" style="width:fit-content; background: rgba(52,199,89,0.2); color:#fff;">Exclusive Promotion</span>
                    <h2 class="slide-title"><?= htmlspecialchars($b->title) ?></h2>
                    <p class="slide-desc"><?= htmlspecialchars($b->description) ?></p>
                    <?php if(!empty($b->button_text)): ?>
                        <a href="<?= APP_URL . htmlspecialchars($b->button_link) ?>" class="btn-primary" style="align-self: flex-start;">
                            <?= htmlspecialchars($b->button_text) ?> <i class="ph ph-arrow-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <!-- Fallback Hero Banner -->
    <div class="card" style="padding: 60px 40px; text-align: center; background: linear-gradient(135deg, rgba(0, 118, 255, 0.05) 0%, rgba(175, 82, 222, 0.05) 100%); margin-bottom: 40px;">
        <h1 style="font-size: 40px; font-weight: 800; letter-spacing:-1px; margin-bottom: 12px; background: linear-gradient(to right, var(--text-accent), #af52de); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Premium Stationery Storefront</h1>
        <p style="color: var(--text-muted); font-size:16px; max-width: 600px; margin: 0 auto 25px auto;">Browse our catalog of high-quality files, notebooks, pens, markers, and office supplies with retail and wholesale pricing options.</p>
        <div style="display:flex; justify-content:center; gap:12px;">
            <a href="<?= APP_URL ?>/shop/category" class="btn-primary">Browse Catalogue</a>
            <a href="<?= APP_URL ?>/shop/login" class="btn-secondary">Apply B2B Wholesale Pricing</a>
        </div>
    </div>
<?php endif; ?>

<!-- Dynamic layout blocks from homepage builder configuration -->
<?php foreach($data['sections'] as $sec): 
    $secKey = $sec->section_name;
    $itemsData = $data['layout_data'][$secKey] ?? [];
    if(empty($itemsData)) continue;
?>

    <!-- 1. Featured Categories Section -->
    <?php if($secKey === 'featured_categories'): ?>
        <div class="shop-section">
            <div class="section-header">
                <h2>Browse by Department</h2>
                <a href="<?= APP_URL ?>/shop/category" style="color:var(--text-accent); text-decoration:none; font-size:13.5px; font-weight:600;">View All Departments &rarr;</a>
            </div>
            <div class="cat-highlight-grid">
                <?php foreach($itemsData as $cat): ?>
                    <a href="<?= APP_URL ?>/shop/category/<?= htmlspecialchars($cat->seo_url) ?>" class="cat-highlight-card">
                        <div class="cat-icon-circle">
                            <i class="<?= !empty($cat->icon) ? htmlspecialchars($cat->icon) : 'ph ph-folder' ?>"></i>
                        </div>
                        <h4 style="font-size: 13.5px; font-weight:700; color:var(--text-main);"><?= htmlspecialchars($cat->name) ?></h4>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- 2. Featured Products Section -->
    <?php if($secKey === 'featured_products'): ?>
        <div class="shop-section">
            <div class="section-header">
                <h2>Featured Stationery Deals</h2>
            </div>
            <div class="product-showcase-grid">
                <?php foreach($itemsData as $item): ?>
                    <a href="<?= APP_URL ?>/shop/item/<?= $item->id ?>" class="prod-showcase-card">
                        <div class="prod-image-wrapper">
                            <?php if(!empty($item->image_path)): ?>
                                <img src="<?= APP_URL ?>/uploads/products/<?= htmlspecialchars($item->image_path) ?>" alt="Product Graphic">
                            <?php else: ?>
                                <i class="ph ph-image" style="font-size: 40px; color:#ccc;"></i>
                            <?php endif; ?>
                        </div>
                        <div class="prod-info-box">
                            <span class="prod-name"><?= htmlspecialchars($item->name) ?></span>
                            <span class="prod-sku">SKU: <?= htmlspecialchars($item->item_code) ?></span>
                            <div class="prod-price-row">
                                <span class="prod-price">
                                    $<?= number_format(($_SESSION['ec_role'] === 'wholesaler') ? $item->wholesale_price : $item->price, 2) ?>
                                </span>
                                <span class="pill-badge pill-success" style="font-size: 8.5px;">Featured</span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- 3. New Arrivals Section -->
    <?php if($secKey === 'new_arrivals'): ?>
        <div class="shop-section">
            <div class="section-header">
                <h2>Newly Arrived Supplies</h2>
            </div>
            <div class="product-showcase-grid">
                <?php foreach($itemsData as $item): ?>
                    <a href="<?= APP_URL ?>/shop/item/<?= $item->id ?>" class="prod-showcase-card">
                        <div class="prod-image-wrapper">
                            <?php if(!empty($item->image_path)): ?>
                                <img src="<?= APP_URL ?>/uploads/products/<?= htmlspecialchars($item->image_path) ?>" alt="Product Graphic">
                            <?php else: ?>
                                <i class="ph ph-image" style="font-size: 40px; color:#ccc;"></i>
                            <?php endif; ?>
                        </div>
                        <div class="prod-info-box">
                            <span class="prod-name"><?= htmlspecialchars($item->name) ?></span>
                            <span class="prod-sku">SKU: <?= htmlspecialchars($item->item_code) ?></span>
                            <div class="prod-price-row">
                                <span class="prod-price">
                                    $<?= number_format(($_SESSION['ec_role'] === 'wholesaler') ? $item->wholesale_price : $item->price, 2) ?>
                                </span>
                                <span class="pill-badge" style="font-size: 8.5px; background: rgba(0,118,255,0.1); color: var(--text-accent);">NEW</span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- 4. Best Sellers Section -->
    <?php if($secKey === 'best_sellers'): ?>
        <div class="shop-section">
            <div class="section-header">
                <h2>Best Selling Stationery</h2>
            </div>
            <div class="product-showcase-grid">
                <?php foreach($itemsData as $item): ?>
                    <a href="<?= APP_URL ?>/shop/item/<?= $item->id ?>" class="prod-showcase-card">
                        <div class="prod-image-wrapper">
                            <?php if(!empty($item->image_path)): ?>
                                <img src="<?= APP_URL ?>/uploads/products/<?= htmlspecialchars($item->image_path) ?>" alt="Product Graphic">
                            <?php else: ?>
                                <i class="ph ph-image" style="font-size: 40px; color:#ccc;"></i>
                            <?php endif; ?>
                        </div>
                        <div class="prod-info-box">
                            <span class="prod-name"><?= htmlspecialchars($item->name) ?></span>
                            <span class="prod-sku">SKU: <?= htmlspecialchars($item->item_code) ?></span>
                            <div class="prod-price-row">
                                <span class="prod-price">
                                    $<?= number_format(($_SESSION['ec_role'] === 'wholesaler') ? $item->wholesale_price : $item->price, 2) ?>
                                </span>
                                <span class="pill-badge pill-warning" style="font-size: 8.5px;">Bestseller</span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- 5. Blog Articles Section -->
    <?php if($secKey === 'blog_articles'): ?>
        <div class="shop-section">
            <div class="section-header">
                <h2>Product Buying Guides &amp; Insights</h2>
                <a href="<?= APP_URL ?>/shop/blog" style="color:var(--text-accent); text-decoration:none; font-size:13.5px; font-weight:600;">Browse All Articles &rarr;</a>
            </div>
            <div class="blog-showcase-grid">
                <?php foreach($itemsData as $post): ?>
                    <a href="<?= APP_URL ?>/shop/blog_post/<?= htmlspecialchars($post->seo_url) ?>" class="blog-showcase-card">
                        <?php if(!empty($post->image_path)): ?>
                            <div class="blog-showcase-img" style="background-image: url('<?= APP_URL ?>/uploads/blog/<?= htmlspecialchars($post->image_path) ?>');"></div>
                        <?php else: ?>
                            <div class="blog-showcase-img" style="display:flex; align-items:center; justify-content:center; color:#ccc;"><i class="ph ph-article" style="font-size:32px;"></i></div>
                        <?php endif; ?>
                        <div class="blog-showcase-body">
                            <span class="pill-badge" style="background: rgba(0,0,0,0.05); color:var(--text-muted); width:fit-content; font-size:8.5px;"><?= htmlspecialchars($post->category) ?></span>
                            <h4 class="blog-showcase-title"><?= htmlspecialchars($post->title) ?></h4>
                            <span style="font-size: 10.5px; color:var(--text-muted); margin-top: auto;"><?= date('F d, Y', strtotime($post->created_at)) ?></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

<?php endforeach; ?>

<!-- Automatic slider script -->
<script>
    const slides = document.querySelectorAll('.slide-item');
    let currentSlide = 0;

    if (slides.length > 1) {
        setInterval(() => {
            slides[currentSlide].classList.remove('active');
            currentSlide = (currentSlide + 1) % slides.length;
            slides[currentSlide].classList.add('active');
        }, 5000);
    }
</script>
