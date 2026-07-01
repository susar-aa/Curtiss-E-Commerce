<div class="portal-layout">
    <!-- Sidebar Menu navigation -->
    <div class="portal-menu-card">
        <h4 style="font-size: 11px; text-transform:uppercase; color:var(--text-muted); font-weight:700; margin-bottom: 15px; letter-spacing:0.5px;">Navigation Menu</h4>
        <ul class="portal-menu-list">
            <li><a href="<?= APP_URL ?>/portal" class="portal-menu-link"><i class="ph ph-squares-four"></i> Dashboard</a></li>
            <li><a href="<?= APP_URL ?>/portal/orders" class="portal-menu-link"><i class="ph ph-receipt"></i> Order History</a></li>
            <li><a href="<?= APP_URL ?>/portal/wishlist" class="portal-menu-link"><i class="ph ph-heart"></i> My Wishlist</a></li>
            <li><a href="<?= APP_URL ?>/portal/returns" class="portal-menu-link"><i class="ph ph-arrow-counter-clockwise"></i> Return Requests</a></li>
            <li><a href="<?= APP_URL ?>/portal/profile" class="portal-menu-link active"><i class="ph ph-user-gear"></i> Profile Settings</a></li>
        </ul>
    </div>

    <!-- Main Workspace Content -->
    <div class="card">
        <h3 style="font-size: 16px; font-weight:700; border-bottom:1px solid var(--mega-divider); padding-bottom:10px; margin-bottom:20px;">Profile Settings</h3>
        
        <?php if(!empty($data['success'])): ?>
            <div class="alert-box pill-success" style="background: rgba(52,199,89,0.1); color: #34c759; margin-bottom: 20px;">
                <i class="ph ph-check-circle"></i> <?= htmlspecialchars($data['success']) ?>
            </div>
        <?php endif; ?>
        <?php if(!empty($data['error'])): ?>
            <div class="alert-box pill-danger" style="background: rgba(255,59,48,0.1); color: #ff3b30; margin-bottom: 20px;">
                <i class="ph ph-warning-circle"></i> <?= htmlspecialchars($data['error']) ?>
            </div>
        <?php endif; ?>

        <form action="<?= APP_URL ?>/portal/profile" method="POST">
            <div class="form-box">
                <label>Display / Company Name *</label>
                <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($data['customer']->name) ?>">
            </div>

            <div class="settings-grid">
                <div class="form-box">
                    <label>Email Address *</label>
                    <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($data['customer']->email ?? $data['customer']->email_address ?? '') ?>">
                </div>
                <div class="form-box">
                    <label>Username *</label>
                    <input type="text" name="username" class="form-control" required value="<?= htmlspecialchars($data['customer']->username) ?>">
                </div>
            </div>

            <div class="settings-grid">
                <div class="form-box">
                    <label>Phone Number</label>
                    <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($data['customer']->phone ?? $data['customer']->contact_number ?? '') ?>">
                </div>
                <div class="form-box">
                    <label>City</label>
                    <input type="text" name="city" class="form-control" value="<?= htmlspecialchars($data['customer']->city ?? $data['customer']->territory ?? '') ?>">
                </div>
            </div>

            <div class="form-box">
                <label>Billing & Delivery Address</label>
                <textarea name="address" class="form-control" rows="3"><?= htmlspecialchars($data['customer']->address) ?></textarea>
            </div>

            <div class="form-box" style="border-top: 1px dashed var(--mega-divider); padding-top:20px; margin-top:20px;">
                <label>Update Password (Leave blank to keep current password)</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••">
            </div>

            <button type="submit" class="btn-primary" style="margin-top:10px;"><i class="ph ph-floppy-disk"></i> Update Profile Details</button>
        </form>
    </div>
</div>
