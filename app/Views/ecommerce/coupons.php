<style>
    .coupon-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
        font-size: 13.5px;
    }
    .coupon-table th {
        background: rgba(0,0,0,0.02);
        padding: 12px 14px;
        text-align: left;
        font-weight: 600;
        color: var(--text-muted);
        border-bottom: 2px solid var(--card-border);
    }
    @media (prefers-color-scheme: dark) {
        .coupon-table th { background: rgba(255,255,255,0.03); }
    }
    .coupon-table td {
        padding: 12px 14px;
        border-bottom: 1px solid var(--mega-divider);
        vertical-align: middle;
    }
    .coupon-table tr:hover {
        background: rgba(0,0,0,0.01);
    }
    .code-badge {
        font-family: monospace;
        font-size: 13px;
        font-weight: 700;
        background: rgba(0,0,0,0.04);
        padding: 4px 10px;
        border-radius: 6px;
        color: var(--text-main);
        letter-spacing: 0.5px;
        border: 1px solid var(--card-border);
        display: inline-block;
    }
    @media (prefers-color-scheme: dark) {
        .code-badge { background: rgba(255,255,255,0.05); }
    }
</style>

<div class="header-actions" style="margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center;">
    <div>
        <h2>Discount Coupon Rules</h2>
        <p style="color: var(--text-muted); margin-top: 4px;">Establish marketing promotion codes, fixed/percentage shopping cart rebates, and validity periods.</p>
    </div>
    <button type="button" class="btn-primary" onclick="openAddModal()" style="padding: 10px 20px; border-radius: 8px; font-size: 13px;">
        <i class="ph ph-plus-circle" style="vertical-align: middle; margin-right: 5px;"></i> Add Coupon Code
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

<div class="card">
    <table class="coupon-table">
        <thead>
            <tr>
                <th>Coupon Code</th>
                <th>Discount Model</th>
                <th>Discount Value</th>
                <th>Min Spend Required</th>
                <th>Expires Date</th>
                <th>Status</th>
                <th style="text-align: right;">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if(empty($data['coupons'])): ?>
                <tr>
                    <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 30px;">No discount coupon rules defined yet.</td>
                </tr>
            <?php else: ?>
                <?php foreach($data['coupons'] as $coupon): 
                    $expired = (!empty($coupon->expiry_date) && strtotime($coupon->expiry_date) < time());
                ?>
                    <tr>
                        <td>
                            <span class="code-badge"><?= htmlspecialchars($coupon->code) ?></span>
                        </td>
                        <td>
                            <?= ($coupon->type === 'percentage') ? 'Percentage Off (%)' : 'Fixed Amount Deduct' ?>
                        </td>
                        <td>
                            <strong><?= ($coupon->type === 'percentage') ? $coupon->value . '%' : '$' . number_format($coupon->value, 2) ?></strong>
                        </td>
                        <td>
                            $<?= number_format($coupon->min_spend, 2) ?>
                        </td>
                        <td>
                            <?= !empty($coupon->expiry_date) ? date('M d, Y', strtotime($coupon->expiry_date)) : 'Never Expires' ?>
                        </td>
                        <td>
                            <?php if ($expired): ?>
                                <span class="pill-badge pill-danger">Expired</span>
                            <?php elseif (!$coupon->is_active): ?>
                                <span class="pill-badge pill-warning">Paused</span>
                            <?php else: ?>
                                <span class="pill-badge pill-success">Active</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: right;">
                            <button type="button" class="btn-secondary" style="padding: 6px 12px; font-size:12px;" onclick="openEditModal(<?= htmlspecialchars(json_encode($coupon)) ?>)">
                                <i class="ph ph-pencil"></i> Edit
                            </button>
                            
                            <form action="<?= APP_URL ?>/ecommerce/coupons" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this coupon?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="coupon_id" value="<?= $coupon->id ?>">
                                <button type="submit" class="btn-secondary" style="border-color: #ff3b30; color: #ff3b30; padding: 6px 12px; font-size:12px;">
                                    <i class="ph ph-trash"></i> Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal: Add / Edit Coupon Rules -->
<div class="modal-backdrop" id="couponModal">
    <div class="modal-box" style="width: 500px;">
        <div style="display:flex; justify-content:space-between; align-items:center; border-bottom: 1px solid var(--mega-divider); padding-bottom:12px; margin-bottom: 18px;">
            <h3 id="modalTitle" style="font-size: 16px; font-weight:700;">Add Coupon Rule</h3>
            <button class="close-btn" onclick="closeModal()">&times;</button>
        </div>

        <form action="<?= APP_URL ?>/ecommerce/coupons" method="POST">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="coupon_id" id="formCouponId">

            <div class="form-box">
                <label>Coupon Code Name</label>
                <input type="text" name="code" id="formCode" class="form-control" required placeholder="e.g. SAVE20" style="text-transform: uppercase;">
            </div>

            <div class="settings-grid">
                <div class="form-box">
                    <label>Discount Type</label>
                    <select name="type" id="formType" class="form-control" required>
                        <option value="percentage">Percentage (%) Discount</option>
                        <option value="fixed">Fixed Amount Discount</option>
                    </select>
                </div>
                <div class="form-box">
                    <label>Rebate Discount Value</label>
                    <input type="number" step="0.01" name="value" id="formValue" class="form-control" required placeholder="e.g. 20">
                </div>
            </div>

            <div class="settings-grid">
                <div class="form-box">
                    <label>Minimum Shopping Cart Spend ($ / LKR)</label>
                    <input type="number" step="0.01" name="min_spend" id="formMin" class="form-control" value="0.00">
                </div>
                <div class="form-box">
                    <label>Expiry Expiration Date</label>
                    <input type="date" name="expiry_date" id="formExpiry" class="form-control">
                </div>
            </div>

            <div class="form-box" style="margin-top: 10px;">
                <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                    <input type="checkbox" name="is_active" id="formActive" value="1" checked style="width: 16px; height:16px;">
                    Make coupon code active for immediate use
                </label>
            </div>

            <div class="btn-actions" style="margin-top: 25px;">
                <button type="button" class="btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn-primary" id="submitBtn">Generate Coupon</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openAddModal() {
        document.getElementById('modalTitle').innerText = "Add Coupon Rule";
        document.getElementById('formAction').value = "add";
        document.getElementById('formCouponId').value = "";
        
        document.getElementById('formCode').value = "";
        document.getElementById('formCode').readOnly = false;
        document.getElementById('formType').value = "percentage";
        document.getElementById('formValue').value = "";
        document.getElementById('formMin').value = "0.00";
        document.getElementById('formExpiry').value = "";
        document.getElementById('formActive').checked = true;
        
        document.getElementById('submitBtn').innerText = "Generate Coupon";
        document.getElementById('couponModal').style.display = "flex";
    }

    function openEditModal(coupon) {
        document.getElementById('modalTitle').innerText = "Edit Coupon Details";
        document.getElementById('formAction').value = "edit";
        document.getElementById('formCouponId').value = coupon.id;
        
        document.getElementById('formCode').value = coupon.code;
        document.getElementById('formCode').readOnly = true;
        document.getElementById('formType').value = coupon.type;
        document.getElementById('formValue').value = coupon.value;
        document.getElementById('formMin').value = coupon.min_spend;
        document.getElementById('formExpiry').value = coupon.expiry_date || "";
        document.getElementById('formActive').checked = parseInt(coupon.is_active) === 1;
        
        document.getElementById('submitBtn').innerText = "Save Coupon Details";
        document.getElementById('couponModal').style.display = "flex";
    }

    function closeModal() {
        document.getElementById('couponModal').style.display = "none";
    }

    // Force uppercase on code input
    document.getElementById('formCode').addEventListener('input', function(e) {
        e.target.value = e.target.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
    });
</script>
