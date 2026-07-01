<style>
    .checkout-layout {
        display: grid;
        grid-template-columns: 1fr 380px;
        gap: 30px;
        margin-top: 15px;
    }
    @media (max-width: 768px) {
        .checkout-layout { grid-template-columns: 1fr; }
    }
</style>

<div class="header-actions" style="margin-bottom: 25px;">
    <h2>Checkout Order</h2>
    <p style="color: var(--text-muted); margin-top: 4px;">Confirm delivery address, apply promotional coupons, and finalize your purchase.</p>
</div>

<?php if(!empty($data['error'])): ?>
    <div class="alert-box pill-danger" style="background: rgba(255,59,48,0.1); color: #ff3b30;">
        <i class="ph ph-warning-circle"></i> <?= htmlspecialchars($data['error']) ?>
    </div>
<?php endif; ?>

<div class="checkout-layout">
    <!-- Form details -->
    <div class="card">
        <h3 style="font-size:16px; border-bottom:1px solid var(--mega-divider); padding-bottom:10px; margin-bottom:20px;">Shipping & Recipient Details</h3>
        
        <form action="<?= APP_URL ?>/shop/checkout" method="POST">
            <input type="hidden" name="action" value="submit_order">

            <?php if (isset($_SESSION['ec_role']) && $_SESSION['ec_role'] === 'wholesaler'): ?>
                <div class="alert-box pill-success" style="background: rgba(52,199,89,0.1); color: #34c759; margin-bottom: 20px;">
                    <i class="ph ph-check-circle"></i> Logged in Wholesaler: <strong><?= htmlspecialchars($_SESSION['ec_name']) ?></strong>. Order will be posted to your B2B account profile.
                </div>
            <?php else: ?>
                <div class="form-box">
                    <label>Recipient's Full Name *</label>
                    <input type="text" name="billing_name" class="form-control" required placeholder="e.g. John Doe">
                </div>

                <div class="form-box">
                    <label>Recipient's Mobile Phone Number *</label>
                    <input type="text" name="billing_phone" class="form-control" required placeholder="e.g. +94 77 123 4567">
                </div>
            <?php endif; ?>

            <div class="form-box">
                <label>Dispatch / Shipping Address *</label>
                <textarea name="shipping_address" class="form-control" rows="4" required placeholder="Provide exact delivery street, city, postal code..."></textarea>
            </div>

            <div class="form-box">
                <label>Coupon Code (Optional)</label>
                <div style="display:flex; gap:10px;">
                    <input type="text" name="coupon_code" id="couponCode" class="form-control" placeholder="e.g. WELCOME10">
                </div>
            </div>

            <button type="submit" class="btn-primary" style="width: 100%; height: 45px; margin-top: 15px;">
                <i class="ph ph-check-square"></i> Finalize & Submit Order
            </button>
        </form>
    </div>

    <!-- Summary Details -->
    <div class="card" style="height: fit-content;">
        <h3 style="font-size:16px; border-bottom:1px solid var(--mega-divider); padding-bottom:10px; margin-bottom:15px;">Order Summary</h3>
        
        <div style="display:flex; flex-direction:column; gap:15px; margin-bottom: 15px;">
            <?php 
            $subtotal = 0;
            foreach($_SESSION['ec_cart'] as $item): 
                $subtotal += $item['price'] * $item['qty'];
            ?>
                <div style="display:flex; justify-content:space-between; font-size:13.5px;">
                    <span style="color:var(--text-main); max-width: 220px; line-height: 1.3;">
                        <strong><?= $item['qty'] ?>x</strong> <?= htmlspecialchars($item['name']) ?>
                    </span>
                    <span style="font-weight:600; color:var(--text-main);">$<?= number_format($item['price'] * $item['qty'], 2) ?></span>
                </div>
            <?php endforeach; ?>
        </div>

        <div style="border-top: 1px dashed var(--mega-divider); padding-top: 15px; display:flex; justify-content:space-between; align-items:center; font-weight:800; font-size: 18px;">
            <span style="color:var(--text-main);">Grand Total:</span>
            <span style="color:var(--text-accent);">$<?= number_format($subtotal, 2) ?></span>
        </div>
    </div>
</div>
