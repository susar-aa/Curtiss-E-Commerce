<div class="card" style="max-width: 600px; margin: 40px auto; text-align: center; padding: 50px 30px;">
    <div style="width:80px; height:80px; border-radius:50%; background: rgba(52,199,89,0.1); color: #34c759; display:flex; align-items:center; justify-content:center; font-size:42px; margin: 0 auto 20px auto;">
        <i class="ph-fill ph-check-circle"></i>
    </div>
    
    <h2 style="font-size: 24px; font-weight: 800; color: var(--text-main); margin-bottom: 10px;">Order Placed Successfully!</h2>
    
    <?php if(!empty($data['order_number'])): ?>
        <p style="font-size: 15px; color: var(--text-muted); margin-bottom: 20px;">
            Your transaction reference number is: <strong style="color:var(--text-main); font-family:monospace;"><?= htmlspecialchars($data['order_number']) ?></strong>.
        </p>
    <?php endif; ?>
    
    <p style="font-size: 14px; color: var(--text-muted); line-height: 1.6; margin-bottom: 30px;">
        Thank you for purchasing with us. An email summary will be sent shortly, and our logistics team is preparing dispatch instructions. You can monitor progress under your customer portal account dashboard.
    </p>

    <div style="display:flex; justify-content:center; gap:12px;">
        <a href="<?= APP_URL ?>/shop/category" class="btn-primary">Browse More Products</a>
        <a href="<?= APP_URL ?>/portal" class="btn-secondary">Go to Account Portal</a>
    </div>
</div>
