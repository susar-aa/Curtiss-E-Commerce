<style>
    .cat-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
        font-size: 13.5px;
    }
    .cat-table th {
        background: rgba(0,0,0,0.02);
        padding: 12px 14px;
        text-align: left;
        font-weight: 600;
        color: var(--text-muted);
        border-bottom: 2px solid var(--card-border);
    }
    @media (prefers-color-scheme: dark) {
        .cat-table th { background: rgba(255,255,255,0.03); }
    }
    .cat-table td {
        padding: 12px 14px;
        border-bottom: 1px solid var(--mega-divider);
        vertical-align: middle;
    }
    .cat-table tr:hover {
        background: rgba(0,0,0,0.01);
    }

    .cat-img-badge {
        width: 38px;
        height: 38px;
        border-radius: 6px;
        object-fit: cover;
        background: #f0f2f6;
        border: 1px solid var(--card-border);
    }
    .cat-icon-box {
        width: 32px;
        height: 32px;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(0,0,0,0.03);
        font-size: 18px;
        color: var(--text-main);
    }
    @media (prefers-color-scheme: dark) {
        .cat-icon-box { background: rgba(255,255,255,0.05); }
    }
</style>

<div class="header-actions" style="margin-bottom: 25px;">
    <h2>Product Categories Hierarchy</h2>
    <p style="color: var(--text-muted); margin-top: 4px;">Assign parent/sub-category navigation trees, map styling icons, upload category index banners, and customize SEO routing slugs.</p>
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
    <table class="cat-table">
        <thead>
            <tr>
                <th style="width: 50px;">Image</th>
                <th style="width: 50px;">Icon</th>
                <th>Category Name</th>
                <th>Parent Category</th>
                <th>SEO Slug URL</th>
                <th>Status</th>
                <th style="text-align: right;">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if(empty($data['categories'])): ?>
                <tr>
                    <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 30px;">No categories available in the database. Add categories in ERP settings.</td>
                </tr>
            <?php else: ?>
                <?php foreach($data['categories'] as $cat): ?>
                    <tr>
                        <td>
                            <?php if(!empty($cat->image_path)): ?>
                                <img src="<?= APP_URL ?>/uploads/categories/<?= htmlspecialchars($cat->image_path) ?>" class="cat-img-badge" alt="Cat Image">
                            <?php else: ?>
                                <div class="cat-img-badge" style="display:flex; align-items:center; justify-content:center; color:#ccc;"><i class="ph ph-image" style="font-size:18px;"></i></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="cat-icon-box">
                                <i class="<?= !empty($cat->icon) ? htmlspecialchars($cat->icon) : 'ph ph-folder' ?>"></i>
                            </div>
                        </td>
                        <td>
                            <strong><?= htmlspecialchars($cat->name) ?></strong>
                        </td>
                        <td>
                            <?= htmlspecialchars($cat->parent_name ?: 'Root level') ?>
                        </td>
                        <td>
                            <code><?= htmlspecialchars($cat->seo_url ?: '-') ?></code>
                        </td>
                        <td>
                            <?php if($cat->is_featured): ?>
                                <span class="pill-badge pill-success">Featured Grid</span>
                            <?php else: ?>
                                <span class="pill-badge" style="background: rgba(0,0,0,0.05); color: var(--text-muted);">Standard</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: right;">
                            <button type="button" class="btn-secondary" style="padding: 6px 12px; font-size:12px;" onclick="openCatModal(<?= htmlspecialchars(json_encode($cat)) ?>)">
                                <i class="ph ph-pencil"></i> Configure
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal: Category settings configuration -->
<div class="modal-backdrop" id="catModal">
    <div class="modal-box" style="width: 500px;">
        <div style="display:flex; justify-content:space-between; align-items:center; border-bottom: 1px solid var(--mega-divider); padding-bottom:12px; margin-bottom: 18px;">
            <h3 style="font-size: 16px; font-weight:700;">Configure Storefront Category</h3>
            <button class="close-btn" onclick="closeModal()">&times;</button>
        </div>

        <form action="<?= APP_URL ?>/ecommerce/categories" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="save_extra">
            <input type="hidden" name="category_id" id="modalCatId">

            <div class="form-box" style="padding: 8px; background:rgba(0,0,0,0.02); border-radius:6px; margin-bottom: 15px;">
                <label>Editing ERP Category Profile</label>
                <strong id="modalCatName" style="font-size:14px;">Category Name</strong>
            </div>

            <div class="form-box">
                <label>Parent Category (Navigation Hierarchy)</label>
                <select name="parent_id" id="modalParentId" class="form-control">
                    <option value="">-- Root Level Category --</option>
                    <?php foreach($data['categories'] as $pOption): ?>
                        <option value="<?= $pOption->id ?>" id="opt-<?= $pOption->id ?>"><?= htmlspecialchars($pOption->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="settings-grid">
                <div class="form-box">
                    <label>Menu Icon Class (Phosphor Icon)</label>
                    <input type="text" name="icon" id="modalIcon" class="form-control" placeholder="e.g. ph ph-pencil-line">
                    <span style="font-size: 10.5px; color: var(--text-muted); margin-top:3px; display:block;">Use e.g. <code>ph ph-book-open</code></span>
                </div>
                <div class="form-box">
                    <label>Category Graphics Image</label>
                    <input type="file" name="cat_image" class="form-control" accept="image/*">
                    <span style="font-size: 10.5px; color: var(--text-muted); margin-top:3px; display:block;">Optional banner overlay.</span>
                </div>
            </div>

            <div class="form-box">
                <label>SEO Keyword Slug URL</label>
                <input type="text" name="seo_url" id="modalSeoUrl" class="form-control" placeholder="e.g. writing-accessories">
            </div>

            <div class="form-box" style="margin-top: 10px;">
                <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                    <input type="checkbox" name="is_featured" id="modalIsFeatured" value="1" style="width: 16px; height:16px;">
                    Feature Category on Storefront Homepage Grid
                </label>
            </div>

            <div class="btn-actions" style="margin-top:25px;">
                <button type="button" class="btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn-primary">Save Category Structure</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openCatModal(cat) {
        document.getElementById('modalCatId').value = cat.id;
        document.getElementById('modalCatName').innerText = cat.name;
        document.getElementById('modalParentId').value = cat.parent_id || "";
        document.getElementById('modalIcon').value = cat.icon || "";
        document.getElementById('modalSeoUrl').value = cat.seo_url || "";
        document.getElementById('modalIsFeatured').checked = parseInt(cat.is_featured) === 1;

        // Prevent selecting itself as parent category
        const options = document.getElementById('modalParentId').options;
        for (let i = 0; i < options.length; i++) {
            if (options[i].value === cat.id.toString()) {
                options[i].disabled = true;
            } else {
                options[i].disabled = false;
            }
        }
        
        document.getElementById('catModal').style.display = "flex";
    }

    function closeModal() {
        document.getElementById('catModal').style.display = "none";
    }
</script>
