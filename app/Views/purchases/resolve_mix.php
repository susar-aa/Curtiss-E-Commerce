<?php
?>
<style>
    .header-actions { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 20px; }
    .btn { padding: 10px 20px; background: #2e7d32; color: #fff; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; font-size: 15px; font-weight:bold;}
    
    .info-panel { background: rgba(0,102,204,0.05); border: 1px solid rgba(0,102,204,0.2); padding: 15px; border-radius: 8px; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center;}
    
    .mix-box { background: #fff; border: 2px solid #ff9800; border-radius: 8px; margin-bottom: 30px; overflow: hidden; box-shadow: 0 4px 15px rgba(255, 152, 0, 0.15);}
    @media (prefers-color-scheme: dark) { .mix-box { background: #1e1e2d; border-color: #f57c00; } }
    .mix-header { background: rgba(255, 152, 0, 0.1); padding: 15px 20px; border-bottom: 1px solid #ffcc80; display: flex; justify-content: space-between; align-items: center;}
    
    .data-table { width: 100%; border-collapse: collapse; }
    .data-table th, .data-table td { padding: 12px 20px; text-align: left; border-bottom: 1px solid var(--mac-border); font-size: 14px;}
    .data-table th { background-color: rgba(0,0,0,0.02); font-weight: 600; color:#555;}
    
    .form-control { width: 120px; padding: 8px; border: 1px solid #ffb74d; border-radius: 4px; background: transparent; color: var(--text-main); text-align: center; font-weight: bold; font-size: 15px;}
    .form-control:focus { border-color: #ff9800; outline: none; box-shadow: 0 0 0 3px rgba(255, 152, 0, 0.1);}
    
    .static-item { background: #f9f9f9; padding: 15px 20px; border: 1px solid var(--mac-border); border-radius: 8px; margin-bottom: 10px; display: flex; justify-content: space-between; color: #666;}
    @media (prefers-color-scheme: dark) { .static-item { background: rgba(255,255,255,0.05); } }
</style>

<div class="card" style="max-width: 900px; margin: auto;">
    <div class="header-actions">
        <div>
            <a href="<?= APP_URL ?>/purchase" style="color: #666; text-decoration:none; font-size: 13px;">&larr; Back to Dashboard</a>
            <h2 style="margin: 10px 0 0 0; color: #f57c00;">Resolve Variation Quantities (GRN)</h2>
            <p style="margin: 0; color: #888; font-size: 14px;">Purchase Order: <?= htmlspecialchars($data['po']->po_number) ?></p>
        </div>
    </div>

    <div class="info-panel">
        <div>
            <span style="font-size: 12px; color: #666; text-transform: uppercase;">Instructions</span><br>
            <strong style="color:#111;">You ordered an unspecified mix of variations. Please specify exactly how many of each color/size arrived so we can update stock levels accurately.</strong>
        </div>
    </div>

    <form action="<?= APP_URL ?>/purchase/process_mix_grn" method="POST" id="resolveForm">
        <input type="hidden" name="po_id" value="<?= $data['po']->id ?>">

        <h3 style="margin-bottom: 10px; font-size: 15px; color:#555;">Items to Resolve:</h3>
        
        <?php foreach($data['items'] as $poItem): ?>
            <?php if($poItem->is_mix): ?>
                <div class="mix-box">
                    <div class="mix-header">
                        <div>
                            <h3 style="margin:0; font-size: 18px; color: #e65100;"><?= htmlspecialchars($poItem->description) ?></h3>
                            <span style="font-size: 12px; color:#888;">Expected Total Order Quantity: <strong style="color:#000;"><?= $poItem->quantity ?></strong></span>
                        </div>
                        <div style="text-align: right;">
                            <span style="font-size: 12px; color:#888;">Total Allocated</span><br>
                            <span style="font-size: 20px; font-weight:bold; color: #2e7d32;" id="allocated_<?= $poItem->id ?>">0</span> / <?= $poItem->quantity ?>
                        </div>
                    </div>
                    
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="width: 70%;">Specific Variation Received</th>
                                <th style="width: 30%; text-align: center;">Qty Received</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(!empty($poItem->available_variations)): ?>
                                <?php foreach($poItem->available_variations as $var): ?>
                                    <?php $desc = $poItem->description . ' -> ' . $var->variation_name . ': ' . $var->value_name; ?>
                                    <tr>
                                        <td>
                                            <strong style="color:#333;"><?= htmlspecialchars($var->variation_name) ?>: <?= htmlspecialchars($var->value_name) ?></strong>
                                        </td>
                                        <td style="text-align: center;">
                                            <input type="number" name="resolve[<?= $poItem->id ?>][qty][]" class="form-control qty-input-<?= $poItem->id ?>" min="0" step="1" oninput="calcAllocated(<?= $poItem->id ?>)">
                                        </td>
                                        <input type="hidden" name="resolve[<?= $poItem->id ?>][var_opt_id][]" value="<?= $var->id ?>">
                                        <input type="hidden" name="resolve[<?= $poItem->id ?>][desc][]" value="<?= htmlspecialchars(str_replace(' (MIX)', '', $desc)) ?>">
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            
                            <!-- Fallback row for generic stock -->
                            <tr style="background: rgba(0,0,0,0.02);">
                                <td>
                                    <strong style="color:#666;">General Stock (No specific variation)</strong><br>
                                    <span style="font-size: 11px; color:#aaa;">Adds to base item quantity only.</span>
                                </td>
                                <td style="text-align: center;">
                                    <input type="number" name="resolve[<?= $poItem->id ?>][qty][]" class="form-control qty-input-<?= $poItem->id ?>" min="0" step="1" oninput="calcAllocated(<?= $poItem->id ?>)">
                                </td>
                                <input type="hidden" name="resolve[<?= $poItem->id ?>][var_opt_id][]" value="">
                                <input type="hidden" name="resolve[<?= $poItem->id ?>][desc][]" value="<?= htmlspecialchars(str_replace(' (MIX)', '', $poItem->description)) ?>">
                            </tr>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <!-- Pre-defined or standard item -->
                <div class="static-item">
                    <div>
                        <strong><?= htmlspecialchars($poItem->description) ?></strong><br>
                        <span style="font-size: 11px;">Standard / Pre-selected</span>
                    </div>
                    <div style="font-weight:bold;">
                        Qty: <?= $poItem->quantity ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>

        <div style="margin-top: 30px; text-align: right; border-top: 2px solid var(--mac-border); padding-top: 20px;">
            <button type="submit" class="btn">Confirm Allocation & Generate GRN &rarr;</button>
        </div>
    </form>
</div>

<script>
    function calcAllocated(poItemId) {
        let total = 0;
        document.querySelectorAll('.qty-input-' + poItemId).forEach(input => {
            total += parseFloat(input.value) || 0;
        });
        document.getElementById('allocated_' + poItemId).innerText = total;
    }
</script>