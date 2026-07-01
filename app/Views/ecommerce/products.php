<style>
    .search-filter-row {
        display: flex;
        gap: 15px;
        margin-bottom: 20px;
        align-items: center;
        flex-wrap: wrap;
    }
    .catalog-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
        font-size: 13.5px;
    }
    .catalog-table th {
        background: rgba(0,0,0,0.02);
        padding: 12px 14px;
        text-align: left;
        font-weight: 600;
        color: var(--text-muted);
        border-bottom: 2px solid var(--card-border);
    }
    @media (prefers-color-scheme: dark) {
        .catalog-table th { background: rgba(255,255,255,0.03); }
    }
    .catalog-table td {
        padding: 12px 14px;
        border-bottom: 1px solid var(--mega-divider);
        vertical-align: middle;
    }
    .catalog-table tr:hover {
        background: rgba(0,0,0,0.01);
    }
    @media (prefers-color-scheme: dark) {
        .catalog-table tr:hover { background: rgba(255,255,255,0.01); }
    }

    .flag-group {
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
    }
    .flag-badge {
        font-size: 9.5px;
        font-weight: 700;
        padding: 2px 6px;
        border-radius: 4px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        display: inline-block;
    }
    .flag-published { background: rgba(52,199,89,0.12); color: #34c759; }
    .flag-unpublished { background: rgba(142,142,147,0.12); color: #8e8e93; }
    .flag-featured { background: rgba(0,122,255,0.12); color: #007aff; }
    .flag-bestseller { background: rgba(255,149,0,0.12); color: #ff9500; }
    .flag-new { background: rgba(175,82,222,0.12); color: #af52de; }
    .flag-clearance { background: rgba(255,59,48,0.12); color: #ff3b30; }

    .price-tag {
        font-family: monospace;
        font-weight: 600;
        font-size: 13px;
        color: var(--text-main);
    }
    .price-ws {
        color: var(--text-accent);
    }

    /* Checklist grid inside modal */
    .checkbox-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 12px;
        margin: 15px 0;
        padding: 15px;
        background: rgba(0,0,0,0.02);
        border-radius: 8px;
        border: 1px solid var(--card-border);
    }
    @media (prefers-color-scheme: dark) {
        .checkbox-grid { background: rgba(255,255,255,0.03); }
    }
    .checkbox-label {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 12.5px;
        font-weight: 500;
        cursor: pointer;
    }
    .checkbox-label input {
        width: 16px;
        height: 16px;
        cursor: pointer;
    }
</style>

<div class="header-actions" style="margin-bottom: 25px;">
    <h2>Product Storefront Catalogue</h2>
    <p style="color: var(--text-muted); margin-top: 4px;">Publish items online, establish wholesaler vs retail pricing engines, and tag special promo categories.</p>
</div>

<?php if(!empty($data['success'])): ?>
    <div class="alert alert-success" style="padding: 12px; background: #e8f5e9; color: #2e7d32; border: 1px solid #a5d6a7; border-radius: 6px; margin-bottom: 15px; font-size: 13px;">
        <i class="ph ph-check-circle" style="vertical-align: middle; font-size: 16px; margin-right: 5px;"></i> <?= $data['success'] ?>
    </div>
<?php endif; ?>
<?php if(!empty($data['error'])): ?>
    <div class="alert alert-error" style="padding: 12px; background: #ffebee; color: #c62828; border: 1px solid #ef9a9a; border-radius: 6px; margin-bottom: 15px; font-size: 13px;">
        <i class="ph ph-warning-circle" style="vertical-align: middle; font-size: 16px; margin-right: 5px;"></i> <?= $data['error'] ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="search-filter-row">
        <input type="text" id="catalogSearch" class="form-control" placeholder="🔍 Search product name, SKU or code..." style="max-width: 320px;">
        <select id="categoryFilter" class="form-control" style="max-width: 220px;">
            <option value="">All Categories</option>
            <?php 
            $cats = [];
            foreach($data['products'] as $p) {
                if(!empty($p->category_name)) $cats[$p->category_name] = true;
            }
            foreach(array_keys($cats) as $c):
            ?>
                <option value="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></option>
            <?php endforeach; ?>
        </select>
        <select id="statusFilter" class="form-control" style="max-width: 180px;">
            <option value="">All Publish Status</option>
            <option value="published">Published</option>
            <option value="draft">Draft (Offline)</option>
        </select>
    </div>

    <div style="overflow-x: auto;">
        <table class="catalog-table" id="productsTable">
            <thead>
                <tr>
                    <th>Product &amp; Code</th>
                    <th>Category</th>
                    <th>ERP Stock</th>
                    <th>Retail Price</th>
                    <th>Wholesale Price</th>
                    <th>E-Commerce Settings</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($data['products'])): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 30px;">No product records found in database.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach($data['products'] as $prod): ?>
                        <tr class="product-row" data-category="<?= htmlspecialchars($prod->category_name ?? '') ?>" data-published="<?= $prod->is_published ? 'published' : 'draft' ?>">
                            <td>
                                <strong class="prod-search-name"><?= htmlspecialchars($prod->name) ?></strong>
                                <div style="font-size: 11px; color: var(--text-muted); margin-top:2px;">
                                    SKU: <span class="prod-search-sku"><?= htmlspecialchars($prod->item_code) ?></span>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($prod->category_name ?? 'Uncategorized') ?></td>
                            <td>
                                <strong><?= $prod->qty ?></strong> <?= htmlspecialchars($prod->unit ?? 'pcs') ?>
                            </td>
                            <td>
                                <span class="price-tag">$<?= number_format($prod->price, 2) ?></span>
                            </td>
                            <td>
                                <span class="price-tag price-ws">$<?= number_format($prod->wholesale_price, 2) ?></span>
                            </td>
                            <td>
                                <div class="flag-group">
                                    <span class="flag-badge <?= $prod->is_published ? 'flag-published' : 'flag-unpublished' ?>">
                                        <?= $prod->is_published ? 'Published' : 'Offline' ?>
                                    </span>
                                    <?php if($prod->is_featured): ?><span class="flag-badge flag-featured">Featured</span><?php endif; ?>
                                    <?php if($prod->is_bestseller): ?><span class="flag-badge flag-bestseller">Best Seller</span><?php endif; ?>
                                    <?php if($prod->is_new_arrival): ?><span class="flag-badge flag-new">New</span><?php endif; ?>
                                    <?php if($prod->is_clearance): ?><span class="flag-badge flag-clearance">Clearance</span><?php endif; ?>
                                </div>
                            </td>
                            <td style="text-align: right;">
                                <button type="button" class="btn-secondary" style="padding: 6px 12px; font-size:12px;" onclick="openConfigModal(<?= htmlspecialchars(json_encode($prod)) ?>)">
                                    <i class="ph ph-gear"></i> Configure
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Configure E-Commerce Product Settings -->
<div class="modal-backdrop" id="productModal">
    <div class="modal-box" style="width: 540px;">
        <div style="display:flex; justify-content:space-between; align-items:center; border-bottom: 1px solid var(--mega-divider); padding-bottom:12px; margin-bottom: 18px;">
            <h3 style="font-size: 16px; font-weight:700;">Configure Storefront Product</h3>
            <button class="close-btn" onclick="closeModal()">&times;</button>
        </div>

        <form action="<?= APP_URL ?>/ecommerce/products" method="POST">
            <input type="hidden" name="action" value="configure">
            <input type="hidden" name="item_id" id="modalItemId">

            <div style="margin-bottom: 15px; padding: 10px; background:rgba(0,0,0,0.02); border-radius:8px;">
                <h4 id="modalProdName" style="font-size: 14px; margin: 0 0 4px 0;">Product Name</h4>
                <span id="modalProdSku" style="font-size: 11px; color: var(--text-muted);">SKU: </span>
            </div>

            <div class="settings-grid">
                <div class="form-box">
                    <label>Online Retail Price ($ / LKR)</label>
                    <input type="number" step="0.01" name="price" id="modalPrice" class="form-control" required>
                </div>
                <div class="form-box">
                    <label>Online Wholesale Price ($ / LKR)</label>
                    <input type="number" step="0.01" name="wholesale_price" id="modalWsPrice" class="form-control" required>
                </div>
            </div>

            <div class="checkbox-grid">
                <label class="checkbox-label">
                    <input type="checkbox" name="is_published" id="modalIsPublished" value="1">
                    Publish Online (Visible)
                </label>
                <label class="checkbox-label">
                    <input type="checkbox" name="is_featured" id="modalIsFeatured" value="1">
                    Featured Product
                </label>
                <label class="checkbox-label">
                    <input type="checkbox" name="is_bestseller" id="modalIsBestseller" value="1">
                    Best Seller Tag
                </label>
                <label class="checkbox-label">
                    <input type="checkbox" name="is_new_arrival" id="modalIsNew" value="1">
                    New Arrival Tag
                </label>
                <label class="checkbox-label">
                    <input type="checkbox" name="is_clearance" id="modalIsClearance" value="1">
                    Clearance Sale Tag
                </label>
                <label class="checkbox-label">
                    <input type="checkbox" name="is_special_offer" id="modalIsSpecial" value="1">
                    Special Offer Tag
                </label>
                <label class="checkbox-label" style="grid-column: 1/-1;">
                    <input type="checkbox" name="online_stock_visible" id="modalStockVisible" value="1">
                    Show Stock Quantity Visible to Storefront Buyers
                </label>
            </div>

            <div class="btn-actions">
                <button type="button" class="btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn-primary">Save Product Configuration</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openConfigModal(prod) {
        document.getElementById('modalItemId').value = prod.id;
        document.getElementById('modalProdName').innerText = prod.name;
        document.getElementById('modalProdSku').innerText = "SKU: " + prod.item_code;
        
        document.getElementById('modalPrice').value = parseFloat(prod.price || 0).toFixed(2);
        document.getElementById('modalWsPrice').value = parseFloat(prod.wholesale_price || 0).toFixed(2);
        
        document.getElementById('modalIsPublished').checked = parseInt(prod.is_published) === 1;
        document.getElementById('modalIsFeatured').checked = parseInt(prod.is_featured) === 1;
        document.getElementById('modalIsBestseller').checked = parseInt(prod.is_bestseller) === 1;
        document.getElementById('modalIsNew').checked = parseInt(prod.is_new_arrival) === 1;
        document.getElementById('modalIsClearance').checked = parseInt(prod.is_clearance) === 1;
        document.getElementById('modalIsSpecial').checked = parseInt(prod.is_special_offer) === 1;
        document.getElementById('modalStockVisible').checked = parseInt(prod.online_stock_visible) === 1;
        
        document.getElementById('productModal').style.display = "flex";
    }

    function closeModal() {
        document.getElementById('productModal').style.display = "none";
    }

    // Client-side search and filtering logic
    const searchInput = document.getElementById('catalogSearch');
    const catFilter = document.getElementById('categoryFilter');
    const statusFilter = document.getElementById('statusFilter');
    const rows = document.querySelectorAll('.product-row');

    function applyFilters() {
        const query = searchInput.value.toLowerCase().trim();
        const cat = catFilter.value;
        const status = statusFilter.value;

        rows.forEach(row => {
            const name = row.querySelector('.prod-search-name').innerText.toLowerCase();
            const sku = row.querySelector('.prod-search-sku').innerText.toLowerCase();
            const rowCat = row.getAttribute('data-category');
            const rowStatus = row.getAttribute('data-published');

            const matchesSearch = name.includes(query) || sku.includes(query);
            const matchesCat = cat === "" || rowCat === cat;
            const matchesStatus = status === "" || rowStatus === status;

            if (matchesSearch && matchesCat && matchesStatus) {
                row.style.display = "";
            } else {
                row.style.display = "none";
            }
        });
    }

    searchInput.addEventListener('input', applyFilters);
    catFilter.addEventListener('change', applyFilters);
    statusFilter.addEventListener('change', applyFilters);
</script>
