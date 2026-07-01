<?php
?>
<style>
    .grid-2 { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; margin-bottom: 5px; font-size: 13px; font-weight: 500; }
    .form-control { width: 100%; padding: 10px; border: 1px solid var(--mac-border); border-radius: 4px; background: transparent; color: var(--text-main); box-sizing: border-box; }
    .btn { padding: 10px 20px; background: #0066cc; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; }
    .btn:hover { background: #005bb5; }
    .alert { padding: 10px; border-radius: 4px; margin-bottom: 15px; font-size: 14px; }
    .alert-success { background: #e8f5e9; color: #2e7d32; border: 1px solid #a5d6a7; }
    .alert-error { background: #ffebee; color: #c62828; border: 1px solid #ef9a9a; }
    
    .logo-preview {
        width: 150px; height: 150px;
        border: 2px dashed var(--mac-border);
        border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        margin-bottom: 15px;
        overflow: hidden;
        background: rgba(0,0,0,0.02);
    }
    .logo-preview img { max-width: 100%; max-height: 100%; object-fit: contain; }
</style>

<div class="header-actions" style="margin-bottom: 20px;">
    <h2>Company Settings</h2>
    <p style="color:#666; margin-top:0;">Manage your business identity for invoices and reports.</p>
</div>

<?php if(!empty($data['success'])): ?>
    <div class="alert alert-success"><?= $data['success'] ?></div>
<?php endif; ?>
<?php if(!empty($data['error'])): ?>
    <div class="alert alert-error"><?= $data['error'] ?></div>
<?php endif; ?>

<div class="grid-2">
    <!-- Profile Form -->
    <div class="card">
        <h3 style="margin-top:0; border-bottom: 1px solid var(--mac-border); padding-bottom: 10px;">Business Profile</h3>
        <form action="<?= APP_URL ?>/settings" method="POST">
            <input type="hidden" name="update_profile" value="1">
            
            <div class="form-group">
                <label>Registered Company Name *</label>
                <input type="text" name="company_name" class="form-control" value="<?= htmlspecialchars($data['settings']->company_name) ?>" required>
            </div>
            
            <div style="display: flex; gap: 15px;">
                <div class="form-group" style="flex: 1;">
                    <label>Business Email</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($data['settings']->email ?? '') ?>">
                </div>
                <div class="form-group" style="flex: 1;">
                    <label>Phone Number</label>
                    <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($data['settings']->phone ?? '') ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label>Tax/VAT Number</label>
                <input type="text" name="tax_number" class="form-control" value="<?= htmlspecialchars($data['settings']->tax_number ?? '') ?>" placeholder="e.g. VAT-12345678">
            </div>

            <div class="form-group">
                <label>Business Address</label>
                <textarea name="address" class="form-control" rows="3"><?= htmlspecialchars($data['settings']->address ?? '') ?></textarea>
            </div>

            <div class="form-group">
                <label>E-Commerce Storefront URL</label>
                <input type="url" name="ecommerce_store_url" class="form-control" value="<?= htmlspecialchars($data['settings']->ecommerce_store_url ?? '') ?>" placeholder="e.g. http://localhost/Curtiss%20E%20Commerce">
            </div>

            <!-- Facebook Integration Section -->
            <div style="margin-top: 25px; margin-bottom: 20px; border-top: 1px dashed var(--mac-border); padding-top: 20px;">
                <h4 style="margin: 0 0 10px 0; color: #0066cc; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-brands fa-facebook"></i> Facebook Page Autopost Integration
                </h4>
                <p style="font-size: 12px; color: #666; margin: 0 0 15px 0;">Automatically publish newly created products directly to your Facebook Business Page feed.</p>
                
                <div class="form-group">
                    <label>Facebook Page ID</label>
                    <input type="text" name="facebook_page_id" class="form-control" value="<?= htmlspecialchars($data['settings']->facebook_page_id ?? '') ?>" placeholder="e.g. 104859672859">
                </div>
                
                <div class="form-group">
                    <label>Facebook Page Access Token</label>
                    <input type="text" name="facebook_access_token" class="form-control" value="<?= htmlspecialchars($data['settings']->facebook_access_token ?? '') ?>" placeholder="e.g. EAAGzD... (Permanent Page Token)">
                    <small style="display: block; font-size: 11px; color: #888; margin-top: 4px;">Use a Facebook Page Access Token with <code>pages_manage_posts</code> and <code>pages_read_engagement</code> permissions.</small>
                </div>
            </div>

            <button type="submit" class="btn">Save Profile Settings</button>
        </form>
    </div>

    <!-- Logo Upload -->
    <div class="card">
        <h3 style="margin-top:0; border-bottom: 1px solid var(--mac-border); padding-bottom: 10px;">Company Logo</h3>
        
        <div class="logo-preview">
            <?php if(!empty($data['settings']->logo_path)): ?>
                <img src="<?= APP_URL ?>/uploads/<?= $data['settings']->logo_path ?>" alt="Company Logo">
            <?php else: ?>
                <span style="color:#aaa; font-size: 12px;">No Logo Uploaded</span>
            <?php endif; ?>
        </div>

        <form action="<?= APP_URL ?>/settings" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="upload_logo" value="1">
            <div class="form-group">
                <label>Select Image (JPG, PNG)</label>
                <input type="file" name="logo" class="form-control" accept=".jpg, .jpeg, .png, .gif" required style="padding: 6px;">
            </div>
            <button type="submit" class="btn" style="width: 100%; background: #333;">Upload Logo</button>
        </form>
        <p style="font-size: 11px; color:#888; margin-top: 15px;">This logo will appear on all client-facing Invoices, Quotes, and Financial Reports.</p>
    </div>
</div>