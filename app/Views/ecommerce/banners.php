<style>
    .banner-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 25px;
        margin-top: 20px;
    }
    .banner-card {
        background: var(--card-bg);
        border: 1px solid var(--card-border);
        border-radius: 16px;
        overflow: hidden;
        box-shadow: var(--card-shadow);
        backdrop-filter: blur(var(--glass-blur));
        display: flex;
        flex-direction: column;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .banner-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 30px rgba(0,0,0,0.12);
    }
    .banner-img-preview {
        height: 160px;
        width: 100%;
        object-fit: cover;
        background: #f0f2f6;
        border-bottom: 1px solid var(--mega-divider);
    }
    .banner-body {
        padding: 18px;
        display: flex;
        flex-direction: column;
        flex: 1;
        gap: 8px;
    }
    .banner-meta {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 5px;
    }
    .banner-type-badge {
        padding: 3px 8px;
        border-radius: 6px;
        font-size: 10.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .type-desktop { background: rgba(0,122,255,0.12); color: #007aff; }
    .type-mobile { background: rgba(175,82,222,0.12); color: #af52de; }
    .type-popup { background: rgba(255,149,0,0.12); color: #ff9500; }

    .banner-title {
        font-size: 15px;
        font-weight: 700;
        color: var(--text-main);
        margin: 0;
    }
    .banner-desc {
        font-size: 12px;
        color: var(--text-muted);
        line-height: 1.4;
        flex: 1;
    }
    .banner-schedule {
        font-size: 11px;
        color: var(--text-muted);
        display: flex;
        align-items: center;
        gap: 4px;
        background: rgba(0,0,0,0.02);
        padding: 6px 10px;
        border-radius: 6px;
        border: 1px solid var(--card-border);
    }
    @media (prefers-color-scheme: dark) {
        .banner-schedule { background: rgba(255,255,255,0.04); }
    }
    .banner-footer {
        display: flex;
        justify-content: flex-end;
        gap: 8px;
        border-top: 1px solid var(--mega-divider);
        padding-top: 12px;
        margin-top: 10px;
    }

    /* Modal dialog overrides */
    .modal-backdrop {
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(0, 0, 0, 0.45);
        backdrop-filter: blur(5px);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 3000;
    }
    .modal-box {
        background: var(--mega-bg);
        border: 1px solid var(--card-border);
        border-radius: 16px;
        width: 580px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.2);
        padding: 24px;
        box-sizing: border-box;
        max-height: 90vh;
        overflow-y: auto;
    }
</style>

<div class="header-actions" style="margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center;">
    <div>
        <h2>Banner Management</h2>
        <p style="color: var(--text-muted); margin-top: 4px;">Upload hero carousels, mobile view promotions, and promotional popups.</p>
    </div>
    <button type="button" class="btn-primary" onclick="openAddModal()" style="padding: 10px 20px; border-radius: 8px; font-size: 13px;">
        <i class="ph ph-plus-circle" style="vertical-align: middle; margin-right: 5px;"></i> Add New Banner
    </button>
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

<!-- Banners Gallery Grid -->
<div class="banner-grid">
    <?php if(empty($data['banners'])): ?>
        <div class="card" style="grid-column: 1/-1; text-align: center; color: var(--text-muted); padding: 50px;">
            <i class="ph ph-image-square" style="font-size: 48px; opacity: 0.5; margin-bottom: 10px;"></i>
            <p>No banners uploaded yet. Click "Add New Banner" to get started.</p>
        </div>
    <?php else: ?>
        <?php foreach($data['banners'] as $banner): ?>
            <div class="banner-card">
                <img src="<?= APP_URL ?>/uploads/banners/<?= htmlspecialchars($banner->image_path) ?>" class="banner-img-preview" alt="Banner Image">
                <div class="banner-body">
                    <div class="banner-meta">
                        <span class="banner-type-badge type-<?= $banner->banner_type ?>">
                            <?= $banner->banner_type ?>
                        </span>
                        <span class="pill-badge <?= $banner->is_active ? 'pill-success' : 'pill-danger' ?>">
                            <?= $banner->is_active ? 'Active' : 'Inactive' ?>
                        </span>
                    </div>
                    <h3 class="banner-title"><?= htmlspecialchars($banner->title ?: 'Untitled Banner') ?></h3>
                    <p class="banner-desc"><?= htmlspecialchars($banner->description ?: 'No description provided.') ?></p>
                    
                    <?php if(!empty($banner->start_date) || !empty($banner->end_date)): ?>
                        <div class="banner-schedule">
                            <i class="ph ph-calendar"></i>
                            <span>
                                <?= !empty($banner->start_date) ? date('M d', strtotime($banner->start_date)) : 'Always' ?> 
                                &rarr; 
                                <?= !empty($banner->end_date) ? date('M d, Y', strtotime($banner->end_date)) : 'Unlimited' ?>
                            </span>
                        </div>
                    <?php endif; ?>

                    <div class="banner-footer">
                        <button type="button" class="btn-secondary" style="padding: 6px 12px; font-size:12px;" onclick="openEditModal(<?= htmlspecialchars(json_encode($banner)) ?>)">
                            <i class="ph ph-pencil"></i> Edit
                        </button>
                        
                        <form action="<?= APP_URL ?>/ecommerce/banners" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this banner?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="banner_id" value="<?= $banner->id ?>">
                            <button type="submit" class="btn-secondary" style="border-color: #ff3b30; color: #ff3b30; padding: 6px 12px; font-size:12px;">
                                <i class="ph ph-trash"></i> Delete
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Modal: Add / Edit Banner -->
<div class="modal-backdrop" id="bannerModal">
    <div class="modal-box">
        <div style="display:flex; justify-content:space-between; align-items:center; border-bottom: 1px solid var(--mega-divider); padding-bottom:12px; margin-bottom: 18px;">
            <h3 id="modalTitle" style="font-size: 16px; font-weight:700;">Add Storefront Banner</h3>
            <button class="close-btn" onclick="closeModal()">&times;</button>
        </div>

        <form action="<?= APP_URL ?>/ecommerce/banners" method="POST" enctype="multipart/form-data" id="bannerForm">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="banner_id" id="formBannerId">

            <div class="settings-grid">
                <div class="form-box">
                    <label>Banner Segment Type</label>
                    <select name="banner_type" id="formType" class="form-control" required>
                        <option value="desktop">Desktop Main Carousel</option>
                        <option value="mobile">Mobile View Promo</option>
                        <option value="popup">Promotional Overlay Popup</option>
                    </select>
                </div>
                <div class="form-box">
                    <label>Banner Image Asset</label>
                    <input type="file" name="banner_image" id="formImage" class="form-control" accept="image/*">
                    <span style="font-size: 10.5px; color: var(--text-muted); margin-top:4px; display:block;" id="imageHelp">Leave empty to keep current image when editing.</span>
                </div>
            </div>

            <div class="form-box">
                <label>Header Title text</label>
                <input type="text" name="title" id="formTitle" class="form-control" placeholder="e.g. Back to School Mega Sale">
            </div>

            <div class="form-box">
                <label>Subheader Description Text</label>
                <textarea name="description" id="formDesc" class="form-control" rows="2" placeholder="e.g. Save up to 50% on all writing equipment and diaries."></textarea>
            </div>

            <div class="settings-grid">
                <div class="form-box">
                    <label>Call-to-Action Button Label</label>
                    <input type="text" name="button_text" id="formBtnText" class="form-control" placeholder="e.g. Shop Now">
                </div>
                <div class="form-box">
                    <label>Call-to-Action Redirect URL</label>
                    <input type="text" name="button_link" id="formBtnLink" class="form-control" placeholder="e.g. /category/stationery">
                </div>
            </div>

            <div class="settings-grid">
                <div class="form-box">
                    <label>Publish Schedule Start</label>
                    <input type="date" name="start_date" id="formStart" class="form-control">
                </div>
                <div class="form-box">
                    <label>Publish Schedule End</label>
                    <input type="date" name="end_date" id="formEnd" class="form-control">
                </div>
            </div>

            <div class="form-box" style="margin-top: 10px;">
                <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                    <input type="checkbox" name="is_active" id="formActive" value="1" checked style="width: 16px; height:16px;">
                    Publish Banner Immediately (Active)
                </label>
            </div>

            <div class="btn-actions">
                <button type="button" class="btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn-primary" id="submitBtn">Create Banner Asset</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openAddModal() {
        document.getElementById('modalTitle').innerText = "Add Storefront Banner";
        document.getElementById('formAction').value = "add";
        document.getElementById('formBannerId').value = "";
        
        document.getElementById('formType').value = "desktop";
        document.getElementById('formTitle').value = "";
        document.getElementById('formDesc').value = "";
        document.getElementById('formBtnText').value = "";
        document.getElementById('formBtnLink').value = "";
        document.getElementById('formStart').value = "";
        document.getElementById('formEnd').value = "";
        document.getElementById('formActive').checked = true;
        
        document.getElementById('formImage').required = true;
        document.getElementById('imageHelp').style.display = "none";
        document.getElementById('submitBtn').innerText = "Create Banner Asset";
        document.getElementById('bannerModal').style.display = "flex";
    }

    function openEditModal(banner) {
        document.getElementById('modalTitle').innerText = "Edit Banner Settings";
        document.getElementById('formAction').value = "edit";
        document.getElementById('formBannerId').value = banner.id;
        
        document.getElementById('formType').value = banner.banner_type;
        document.getElementById('formTitle').value = banner.title || "";
        document.getElementById('formDesc').value = banner.description || "";
        document.getElementById('formBtnText').value = banner.button_text || "";
        document.getElementById('formBtnLink').value = banner.button_link || "";
        document.getElementById('formStart').value = banner.start_date || "";
        document.getElementById('formEnd').value = banner.end_date || "";
        document.getElementById('formActive').checked = parseInt(banner.is_active) === 1;
        
        document.getElementById('formImage').required = false;
        document.getElementById('imageHelp').style.display = "block";
        document.getElementById('submitBtn').innerText = "Save Banner Changes";
        document.getElementById('bannerModal').style.display = "flex";
    }

    function closeModal() {
        document.getElementById('bannerModal').style.display = "none";
    }
</script>
