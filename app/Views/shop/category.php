<style>
    .browse-layout {
        display: grid;
        grid-template-columns: 260px 1fr;
        gap: 30px;
        margin-top: 15px;
    }
    @media (max-width: 768px) {
        .browse-layout { grid-template-columns: 1fr; }
    }

    /* Filter Sidebar */
    .filter-sidebar {
        display: flex;
        flex-direction: column;
        gap: 25px;
    }
    .filter-box-card {
        background: var(--card-bg);
        border: 1px solid var(--card-border);
        border-radius: var(--rounded);
        padding: 20px;
        box-shadow: var(--card-shadow);
        backdrop-filter: blur(var(--glass-blur));
    }
    .filter-heading {
        font-size: 13.5px;
        font-weight: 700;
        text-transform: uppercase;
        color: var(--text-muted);
        letter-spacing: 0.5px;
        margin-bottom: 15px;
        border-bottom: 1px solid var(--mega-divider);
        padding-bottom: 8px;
    }
    .category-filter-list {
        display: flex;
        flex-direction: column;
        gap: 10px;
        list-style: none;
    }
    .category-filter-link {
        color: var(--text-main);
        text-decoration: none;
        font-size: 13.5px;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: color 0.2s;
    }
    .category-filter-link:hover, .category-filter-link.active {
        color: var(--text-accent);
        font-weight: 600;
    }

    /* Products Roll Area */
    .catalog-main {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }
    .catalog-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 15px;
        background: var(--card-bg);
        border: 1px solid var(--card-border);
        border-radius: var(--rounded);
        padding: 15px 20px;
        box-shadow: var(--card-shadow);
    }
    .catalog-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 20px;
    }
</style>

<div class="header-actions" style="margin-bottom: 20px;">
    <h2>Stationery Catalog</h2>
    <p style="color: var(--text-muted); margin-top: 4px;">Browsing: <strong><?= htmlspecialchars($data['current_category']) ?></strong></p>
</div>

<div class="browse-layout">
    <!-- Sidebar Filters -->
    <div class="filter-sidebar">
        <!-- Categories Department list -->
        <div class="filter-box-card">
            <h4 class="filter-heading">Departments</h4>
            <ul class="category-filter-list">
                <li>
                    <a href="<?= APP_URL ?>/shop/category" class="category-filter-link <?= ($data['current_seo'] === null) ? 'active' : '' ?>">
                        <i class="ph ph-package"></i> All Products
                    </a>
                </li>
                <?php foreach($data['categories'] as $c): ?>
                    <li>
                        <a href="<?= APP_URL ?>/shop/category/<?= htmlspecialchars($c->seo_url) ?>" class="category-filter-link <?= ($data['current_seo'] === $c->seo_url) ? 'active' : '' ?>">
                            <i class="<?= !empty($c->icon) ? htmlspecialchars($c->icon) : 'ph ph-folder' ?>"></i>
                            <?= htmlspecialchars($c->name) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- Filter form parameters -->
        <div class="filter-box-card">
            <h4 class="filter-heading">Price Range &amp; Search</h4>
            <form action="" method="GET">
                <!-- Retain current query parameters if any -->
                <div class="form-box">
                    <label>Keyword Search</label>
                    <input type="text" name="q" class="form-control" placeholder="Search by name..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
                </div>

                <div class="settings-grid">
                    <div class="form-box">
                        <label>Min Price</label>
                        <input type="number" step="0.01" name="min_price" class="form-control" placeholder="Min" value="<?= htmlspecialchars($_GET['min_price'] ?? '') ?>">
                    </div>
                    <div class="form-box">
                        <label>Max Price</label>
                        <input type="number" step="0.01" name="max_price" class="form-control" placeholder="Max" value="<?= htmlspecialchars($_GET['max_price'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-box">
                    <label>Sort By</label>
                    <select name="sort" class="form-control">
                        <option value="newest" <?= ($_GET['sort'] ?? '') === 'newest' ? 'selected' : '' ?>>Newest Arrivals</option>
                        <option value="price_asc" <?= ($_GET['sort'] ?? '') === 'price_asc' ? 'selected' : '' ?>>Price: Low to High</option>
                        <option value="price_desc" <?= ($_GET['sort'] ?? '') === 'price_desc' ? 'selected' : '' ?>>Price: High to Low</option>
                    </select>
                </div>

                <button type="submit" class="btn-primary" style="width: 100%; margin-top: 10px;">
                    <i class="ph ph-funnel"></i> Apply Filter
                </button>
            </form>
        </div>
    </div>

    <!-- Products Grid list -->
    <div class="catalog-main">
        <div class="catalog-bar">
            <span style="font-size: 13.5px; font-weight:600; color:var(--text-muted);">
                Found <?= count($data['products']) ?> items in department
            </span>
            <span style="font-size: 11px; color: var(--text-accent); font-weight: 700; text-transform: uppercase;">
                Price tier: <?= ($_SESSION['ec_role'] ?? 'guest') === 'wholesaler' ? 'Wholesale Pricing Active' : 'Retail Pricing Tier' ?>
            </span>
        </div>

        <div class="catalog-grid">
            <?php if(empty($data['products'])): ?>
                <div class="card" style="grid-column: 1/-1; text-align:center; padding: 60px 20px; color: var(--text-muted);">
                    <i class="ph ph-magnifying-glass" style="font-size:48px; opacity:0.5; margin-bottom:10px;"></i>
                    <p>No products match your filter search parameters.</p>
                </div>
            <?php else: ?>
                <?php foreach($data['products'] as $item): 
                    $price = ($_SESSION['ec_role'] ?? 'guest') === 'wholesaler' ? $item->wholesale_price : $item->price;
                    $isOutOfStock = intval($item->qty) <= 0;
                ?>
                    <a href="<?= APP_URL ?>/shop/item/<?= $item->id ?>" class="prod-showcase-card">
                        <div class="prod-image-wrapper">
                            <?php if(!empty($item->image_path)): ?>
                                <img src="<?= APP_URL ?>/uploads/products/<?= htmlspecialchars($item->image_path) ?>" alt="Product image">
                            <?php else: ?>
                                <i class="ph ph-image" style="font-size: 40px; color:#ccc;"></i>
                            <?php endif; ?>
                            
                            <?php if($isOutOfStock): ?>
                                <span class="pill-badge pill-danger" style="position: absolute; top:10px; right:10px; font-size: 8.5px;">Sold Out</span>
                            <?php endif; ?>
                        </div>
                        <div class="prod-info-box">
                            <span class="prod-name"><?= htmlspecialchars($item->name) ?></span>
                            <span class="prod-sku">SKU: <?= htmlspecialchars($item->item_code) ?></span>
                            <div class="prod-price-row">
                                <span class="prod-price">$<?= number_format($price, 2) ?></span>
                                <span class="btn-primary" style="padding: 4px 10px; font-size:11px; border-radius:6px;">View &rarr;</span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
