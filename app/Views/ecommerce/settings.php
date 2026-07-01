<style>
    /* CSS Tabs Layout */
    .tabs-wrapper {
        display: flex;
        flex-direction: column;
    }
    .tabs-nav {
        display: flex;
        gap: 8px;
        border-bottom: 1px solid var(--mega-divider);
        padding-bottom: 8px;
        margin-bottom: 25px;
        overflow-x: auto;
    }
    .tab-btn {
        background: transparent;
        border: none;
        padding: 10px 18px;
        font-size: 13.5px;
        font-weight: 600;
        color: var(--text-muted);
        cursor: pointer;
        border-radius: 8px;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: background 0.2s, color 0.2s;
    }
    .tab-btn:hover {
        background: rgba(0, 0, 0, 0.04);
        color: var(--text-main);
    }
    @media (prefers-color-scheme: dark) {
        .tab-btn:hover { background: rgba(255, 255, 255, 0.05); }
    }
    .tab-btn.active {
        background: var(--text-accent);
        color: #fff;
    }

    .tab-content {
        display: none;
        animation: fadeIn 0.25s ease;
    }
    .tab-content.active {
        display: block;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(4px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Grid layouts inside tabs */
    .settings-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
    }
    .form-box {
        margin-bottom: 18px;
    }
    .form-box label {
        display: block;
        margin-bottom: 6px;
        font-size: 12.5px;
        font-weight: 600;
        color: var(--text-muted);
    }
    .form-control {
        width: 100%;
        padding: 10px 14px;
        border: 1px solid var(--card-border);
        background: rgba(255, 255, 255, 0.5);
        border-radius: 8px;
        color: var(--text-main);
        font-size: 13.5px;
        box-sizing: border-box;
        transition: border-color 0.2s, box-shadow 0.2s;
    }
    @media (prefers-color-scheme: dark) {
        .form-control { background: rgba(0, 0, 0, 0.2); }
    }
    .form-control:focus {
        border-color: var(--text-accent);
        outline: none;
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15);
    }

    .branding-preview-box {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 15px;
        background: rgba(0,0,0,0.02);
        border-radius: 8px;
        border: 1px solid var(--card-border);
        margin-top: 8px;
    }
    .img-preview {
        max-height: 48px;
        max-width: 150px;
        object-fit: contain;
        background: #fdfdfd;
        padding: 4px;
        border-radius: 4px;
        border: 1px solid #ddd;
    }

    .save-bar {
        margin-top: 35px;
        display: flex;
        justify-content: flex-end;
        border-top: 1px solid var(--mega-divider);
        padding-top: 20px;
    }
</style>

<div class="header-actions" style="margin-bottom: 25px;">
    <h2>Website Settings</h2>
    <p style="color: var(--text-muted); margin-top: 4px;">Configure public storefront identity, branding assets, SEO parameters, and legal terms.</p>
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

<form action="<?= APP_URL ?>/ecommerce/settings" method="POST" enctype="multipart/form-data">
    <div class="card tabs-wrapper">
        <div class="tabs-nav">
            <button type="button" class="tab-btn active" onclick="switchTab(event, 'general')"><i class="ph ph-storefront"></i> General Profile</button>
            <button type="button" class="tab-btn" onclick="switchTab(event, 'branding')"><i class="ph ph-palette"></i> Branding &amp; SEO</button>
            <button type="button" class="tab-btn" onclick="switchTab(event, 'shipping')"><i class="ph ph-truck"></i> Shipping &amp; Payments</button>
            <button type="button" class="tab-btn" onclick="switchTab(event, 'legal')"><i class="ph ph-shield-check"></i> Terms &amp; Policies</button>
        </div>

        <?php
        $getSetting = function($key) use ($data) {
            return htmlspecialchars($data['settings'][$key] ?? '');
        };
        ?>

        <!-- Tab Content: General -->
        <div class="tab-content active" id="tab-general">
            <div class="settings-grid">
                <div class="form-box">
                    <label>Store Name</label>
                    <input type="text" name="settings[store_name]" class="form-control" value="<?= $getSetting('store_name') ?>" required>
                </div>
                <div class="form-box">
                    <label>Contact Phone Number</label>
                    <input type="text" name="settings[store_phone]" class="form-control" value="<?= $getSetting('store_phone') ?>">
                </div>
                <div class="form-box">
                    <label>Contact Email Address</label>
                    <input type="email" name="settings[store_email]" class="form-control" value="<?= $getSetting('store_email') ?>">
                </div>
                <div class="form-box">
                    <label>Currency Identifier</label>
                    <input type="text" name="settings[store_currency]" class="form-control" placeholder="e.g. USD, LKR" value="<?= $getSetting('store_currency') ?>">
                </div>
            </div>
            <div class="form-box" style="margin-top: 10px;">
                <label>Physical Address</label>
                <textarea name="settings[store_address]" class="form-control" rows="3"><?= $getSetting('store_address') ?></textarea>
            </div>
            <div class="form-box">
                <label>Google Maps Embed iframe Link</label>
                <input type="text" name="settings[contact_map_iframe]" class="form-control" placeholder="https://www.google.com/maps/embed?..." value="<?= $getSetting('contact_map_iframe') ?>">
            </div>
        </div>

        <!-- Tab Content: Branding & SEO -->
        <div class="tab-content" id="tab-branding">
            <div class="settings-grid" style="margin-bottom: 20px;">
                <div class="form-box">
                    <label>Store Logo Image</label>
                    <input type="file" name="logo" class="form-control" accept="image/*">
                    <?php if(!empty($data['settings']['logo'])): ?>
                        <div class="branding-preview-box">
                            <img src="<?= APP_URL ?>/uploads/store/<?= $data['settings']['logo'] ?>" class="img-preview" alt="Logo">
                            <span style="font-size: 11px; color: var(--text-muted);">Current Logo</span>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="form-box">
                    <label>Website Favicon Image</label>
                    <input type="file" name="favicon" class="form-control" accept="image/*">
                    <?php if(!empty($data['settings']['favicon'])): ?>
                        <div class="branding-preview-box">
                            <img src="<?= APP_URL ?>/uploads/store/<?= $data['settings']['favicon'] ?>" class="img-preview" style="max-height: 24px; max-width: 24px;" alt="Favicon">
                            <span style="font-size: 11px; color: var(--text-muted);">Current Favicon</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div style="border-top: 1px solid var(--mega-divider); padding-top: 20px; margin-top: 20px;">
                <h4 style="font-size:14px; margin-bottom: 15px; font-weight:700;"><i class="ph ph-globe-hemisphere-west"></i> Search Engine Optimization (SEO) Metadata</h4>
                <div class="form-box">
                    <label>Meta Title Tag</label>
                    <input type="text" name="settings[meta_title]" class="form-control" value="<?= $getSetting('meta_title') ?>">
                </div>
                <div class="form-box">
                    <label>Meta Description Tag</label>
                    <textarea name="settings[meta_description]" class="form-control" rows="3"><?= $getSetting('meta_description') ?></textarea>
                </div>
                <div class="form-box">
                    <label>Meta Keywords</label>
                    <input type="text" name="settings[meta_keywords]" class="form-control" placeholder="stationery, pencils, paper, office" value="<?= $getSetting('meta_keywords') ?>">
                </div>
            </div>
        </div>

        <!-- Tab Content: Shipping -->
        <div class="tab-content" id="tab-shipping">
            <div class="settings-grid">
                <div class="form-box">
                    <label>Flat Rate Shipping Fee ($ / LKR)</label>
                    <input type="number" step="0.01" name="settings[shipping_fee]" class="form-control" value="<?= $getSetting('shipping_fee') ?>">
                </div>
                <div class="form-box">
                    <label>Free Shipping Threshold ($ / LKR)</label>
                    <input type="number" step="0.01" name="settings[free_shipping_threshold]" class="form-control" value="<?= $getSetting('free_shipping_threshold') ?>">
                </div>
            </div>
            
            <div style="border-top: 1px solid var(--mega-divider); padding-top: 20px; margin-top: 20px;">
                <h4 style="font-size:14px; margin-bottom: 15px; font-weight:700;"><i class="ph ph-envelope-simple"></i> Social Media Profiles</h4>
                <div class="settings-grid">
                    <div class="form-box">
                        <label><i class="ph ph-facebook-logo"></i> Facebook URL</label>
                        <input type="text" name="settings[social_facebook]" class="form-control" value="<?= $getSetting('social_facebook') ?>">
                    </div>
                    <div class="form-box">
                        <label><i class="ph ph-instagram-logo"></i> Instagram URL</label>
                        <input type="text" name="settings[social_instagram]" class="form-control" value="<?= $getSetting('social_instagram') ?>">
                    </div>
                    <div class="form-box">
                        <label><i class="ph ph-linkedin-logo"></i> LinkedIn URL</label>
                        <input type="text" name="settings[social_linkedin]" class="form-control" value="<?= $getSetting('social_linkedin') ?>">
                    </div>
                    <div class="form-box">
                        <label><i class="ph ph-twitter-logo"></i> Twitter URL</label>
                        <input type="text" name="settings[social_twitter]" class="form-control" value="<?= $getSetting('social_twitter') ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab Content: Legal & Policies -->
        <div class="tab-content" id="tab-legal">
            <div class="form-box">
                <label>Footer Copywrite Info</label>
                <input type="text" name="settings[footer_text]" class="form-control" placeholder="© 2026 Curtiss Stationery. All Rights Reserved." value="<?= $getSetting('footer_text') ?>">
            </div>
            <div class="form-box">
                <label>Terms and Conditions</label>
                <textarea name="settings[terms_and_conditions]" class="form-control" rows="6"><?= $getSetting('terms_and_conditions') ?></textarea>
            </div>
            <div class="form-box">
                <label>Privacy Policy</label>
                <textarea name="settings[privacy_policy]" class="form-control" rows="6"><?= $getSetting('privacy_policy') ?></textarea>
            </div>
            <div class="form-box">
                <label>Refund & Returns Policy</label>
                <textarea name="settings[refund_policy]" class="form-control" rows="6"><?= $getSetting('refund_policy') ?></textarea>
            </div>
        </div>

        <div class="save-bar">
            <button type="submit" class="btn-primary" style="padding: 12px 28px; font-size: 14px; border-radius: 8px;">
                <i class="ph ph-floppy-disk" style="vertical-align: middle; margin-right: 5px;"></i> Save Store Settings
            </button>
        </div>
    </div>
</form>

<script>
    function switchTab(e, tabId) {
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
        
        e.currentTarget.classList.add('active');
        document.getElementById('tab-' + tabId).classList.add('active');
    }
</script>
