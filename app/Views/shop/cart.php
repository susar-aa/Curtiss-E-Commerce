<style>
    .cart-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
        font-size: 14px;
    }
    .cart-table th {
        background: rgba(0,0,0,0.02);
        padding: 15px;
        text-align: left;
        font-weight: 600;
        color: var(--text-muted);
        border-bottom: 2px solid var(--card-border);
    }
    @media (prefers-color-scheme: dark) {
        .cart-table th { background: rgba(255,255,255,0.03); }
    }
    .cart-table td {
        padding: 15px;
        border-bottom: 1px solid var(--mega-divider);
        vertical-align: middle;
    }
    .cart-table tr:hover {
        background: rgba(0,0,0,0.01);
    }
</style>

<div class="header-actions" style="margin-bottom: 25px;">
    <h2>Your Shopping Cart</h2>
    <p style="color: var(--text-muted); margin-top: 4px;">Review items, adjust quantities, or proceed to secure invoice checkout.</p>
</div>

<?php if(empty($_SESSION['ec_cart'])): ?>
    <div class="card" style="text-align: center; color: var(--text-muted); padding: 60px 20px;">
        <i class="ph ph-shopping-cart-simple" style="font-size: 48px; opacity: 0.5; margin-bottom: 10px;"></i>
        <p>Your shopping cart is currently empty.</p>
        <a href="<?= APP_URL ?>/shop/category" class="btn-primary" style="margin-top: 15px;">
            <i class="ph ph-arrow-left"></i> Continue Shopping
        </a>
    </div>
<?php else: ?>
    <form action="<?= APP_URL ?>/shop/cart" method="POST">
        <input type="hidden" name="action" value="update">
        
        <div class="card" style="padding:0; overflow:hidden; margin-bottom: 25px;">
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Product Details</th>
                        <th>SKU Code</th>
                        <th>Billing Price</th>
                        <th style="width: 120px; text-align: center;">Qty</th>
                        <th style="text-align: right;">Subtotal</th>
                        <th style="text-align: right; width: 80px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $subtotal = 0;
                    foreach($_SESSION['ec_cart'] as $k => $item): 
                        $itemTotal = $item['price'] * $item['qty'];
                        $subtotal += $itemTotal;
                    ?>
                        <tr>
                            <td>
                                <div style="display:flex; align-items:center; gap:12px;">
                                    <?php if(!empty($item['image_path'])): ?>
                                        <img src="<?= APP_URL ?>/uploads/products/<?= htmlspecialchars($item['image_path']) ?>" alt="Thumbnail" style="width:40px; height:40px; object-fit:contain; border:1px solid var(--card-border); border-radius:6px; background:#fff;">
                                    <?php else: ?>
                                        <div style="width:40px; height:40px; border:1px solid var(--card-border); border-radius:6px; background:#f0f2f6; display:flex; align-items:center; justify-content:center; color:#999;"><i class="ph ph-image"></i></div>
                                    <?php endif; ?>
                                    <strong><?= htmlspecialchars($item['name']) ?></strong>
                                </div>
                            </td>
                            <td><code><?= htmlspecialchars($item['sku']) ?></code></td>
                            <td>$<?= number_format($item['price'], 2) ?></td>
                            <td>
                                <input type="number" name="qty[<?= $k ?>]" value="<?= $item['qty'] ?>" min="1" class="form-control" style="text-align:center;">
                            </td>
                            <td style="text-align: right; font-weight:700;">$<?= number_format($itemTotal, 2) ?></td>
                            <td style="text-align: right;">
                                <button type="submit" name="action" value="delete" class="btn-secondary" style="border-color: #ff3b30; color: #ff3b30; padding:6px 10px; font-size:12px;" onclick="document.getElementById('deleteKey').value = '<?= $k ?>';">
                                    <i class="ph ph-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <tr style="background: rgba(0,0,0,0.015);">
                        <td colspan="4" style="text-align: right; font-size:15px; font-weight:600; padding: 20px;">Estimated Subtotal:</td>
                        <td style="text-align: right; font-size:20px; font-weight:800; color: var(--text-accent); padding: 20px;">$<?= number_format($subtotal, 2) ?></td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 50px;">
            <button type="submit" class="btn-secondary">
                <i class="ph ph-arrows-clockwise"></i> Recalculate Quantities
            </button>
            
            <div style="display:flex; gap:12px;">
                <a href="<?= APP_URL ?>/shop/category" class="btn-secondary">Continue Shopping</a>
                <a href="<?= APP_URL ?>/shop/checkout" class="btn-primary">Proceed to Checkout <i class="ph ph-arrow-right"></i></a>
            </div>
        </div>
    </form>

    <!-- Hidden form for deleting items -->
    <form action="<?= APP_URL ?>/shop/cart" method="POST" id="deleteForm" style="display:none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="cart_key" id="deleteKey">
    </form>
    <script>
        document.querySelectorAll('button[value="delete"]').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const key = this.getAttribute('onclick').match(/'([^']+)'/)[1];
                document.getElementById('deleteKey').value = key;
                document.getElementById('deleteForm').submit();
            });
        });
    </script>
<?php endif; ?>
