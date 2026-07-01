<?php
$isEdit = isset($data['po']);
$actionUrl = APP_URL . '/purchase/' . ($isEdit ? "edit/{$data['po']->id}" : "create"); 
?>
<style>
    .header-actions { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .btn { padding: 8px 16px; background: #0066cc; color: #fff; border: none; border-radius: 4px; cursor: pointer; }
    .btn-danger { background: #ff3b30; }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; margin-bottom: 5px; font-size: 13px; font-weight: 500; }
    .form-control { width: 100%; padding: 8px 12px; border: 1px solid var(--mac-border); border-radius: 4px; background: transparent; color: var(--text-main); box-sizing: border-box; }
    .grid-4 { display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 15px; }
    .po-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    .po-table th, .po-table td { padding: 10px; border-bottom: 1px solid var(--mac-border); }
    .po-table th { background-color: rgba(0,0,0,0.02); text-align: left; }
    .po-table input, .po-table select { width: 100%; border: 1px solid transparent; background: transparent; color: var(--text-main); padding: 5px; box-sizing: border-box; }
    .po-table input:focus, .po-table select:focus { border: 1px solid #0066cc; outline: none; border-radius: 4px;}
    .po-table input[type="number"] { text-align: right; }
    .total-box { float: right; width: 300px; padding: 20px; background: rgba(0,0,0,0.02); border-radius: 8px; margin-top: 20px; text-align: right; font-size: 18px; font-weight: bold;}
    
    /* Enhance optgroup styling in standard browsers */
    optgroup { font-weight: bold; color: #0066cc; background: rgba(0,102,204,0.05); }
    option { font-weight: normal; color: #333; background: #fff;}
    .line-row-num { text-align: center; color: #888; font-weight: bold; font-size: 12px; vertical-align: middle; width: 40px; }
</style>

<div class="card">
    <div class="header-actions">
        <h2><?= $isEdit ? 'Edit Purchase Order' : 'Create Purchase Order' ?></h2>
        <a href="<?= APP_URL ?>/purchase" style="color: #666; text-decoration:none;">&larr; Back to POs</a>
    </div>

    <?php if(!empty($data['error'])): ?>
        <div style="padding: 10px; background:#ffebee; color:#c62828; border-radius:4px; margin-bottom:15px;"><?= $data['error'] ?></div>
    <?php endif; ?>

    <form action="<?= $actionUrl ?>" method="POST" id="poForm">
        <input type="hidden" name="action" value="<?= $isEdit ? 'update_po' : 'save_po' ?>">
        
        <div class="grid-4">
            <div class="form-group">
                <label>Supplier / Vendor *</label>
                <select name="vendor_id" id="vendorSelect" class="form-control" onchange="filterProducts()" required>
                    <option value="">Select Vendor...</option>
                    <?php foreach($data['vendors'] as $ven): ?>
                        <option value="<?= $ven->id ?>" <?= $data['prefilled_vendor'] == $ven->id ? 'selected' : '' ?>><?= htmlspecialchars($ven->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>PO Number</label>
                <input type="text" name="po_number" class="form-control" value="<?= htmlspecialchars($data['po']->po_number ?? $data['po_number']) ?>" <?= $isEdit ? 'readonly' : 'required' ?>>
            </div>
            <div class="form-group">
                <label>Order Date</label>
                <input type="date" name="po_date" class="form-control" value="<?= htmlspecialchars($data['po']->po_date ?? date('Y-m-d')) ?>" required>
            </div>
            <div class="form-group">
                <label>Expected Delivery</label>
                <input type="date" name="expected_date" class="form-control" value="<?= htmlspecialchars($data['po']->expected_date ?? date('Y-m-d', strtotime('+7 days'))) ?>" required>
            </div>
        </div>

        <div style="background:#e3f2fd; color:#1565c0; padding:10px; border-radius:4px; font-size:12px; margin-top:10px;">
            ℹ️ Filtered by Vendor. Select specific variants directly, or choose "(MIX)" if the exact colors/sizes will be decided upon delivery.
        </div>

        <table class="po-table" id="linesTable">
            <thead>
                <tr>
                    <th class="line-row-num">#</th>
                    <th style="width: 50%;">Product / Variation</th>
                    <th style="width: 15%; text-align:right;">Qty</th>
                    <th style="width: 15%; text-align:right;">Unit Cost (Rs:)</th>
                    <th style="width: 15%; text-align:right;">Total (Rs:)</th>
                    <th style="width: 5%;"></th>
                </tr>
            </thead>
            <tbody id="poBody">
                <?php if($isEdit || !empty($data['prefilled_items'])): ?>
                    <?php $loopItems = $isEdit ? $data['items'] : $data['prefilled_items']; ?>
                    <?php $lineNum = 1; foreach($loopItems as $item): ?>
                        
                        <?php 
                            // Determine the exact value string to pre-select
                            if ($isEdit) {
                                $vId = $item->item_id;
                                $vOpt = $item->item_variation_option_id ?: ($item->is_mix ? 'MIX' : '0');
                                $vMix = $item->is_mix;
                                $selectedValue = "{$vId}|{$vOpt}|{$vMix}";
                            } else {
                                // Coming from AI Suggester (prefilled) -> defaults to MIX if variations exist
                                $itemRef = array_filter($data['catalog_items'], function($c) use ($item) { return $c->id == $item['item_id']; });
                                $itemRef = reset($itemRef);
                                $hasVars = !empty($itemRef->variations);
                                $selectedValue = $item['item_id'] . '|' . ($hasVars ? 'MIX' : '0') . '|' . ($hasVars ? '1' : '0');
                            }
                        ?>

                        <tr>
                            <td class="line-row-num"><?= $lineNum++ ?></td>
                            <td>
                                <select name="item_selection[]" class="item-select" onchange="autoFillSelection(this)" required>
                                    <option value="">Select Product...</option>
                                    <?php foreach($data['catalog_items'] as $catItem): ?>
                                        <?php if(!empty($catItem->variations)): ?>
                                            <optgroup label="<?= htmlspecialchars($catItem->name) ?>" data-vendor="<?= $catItem->vendor_id ?>">
                                                <option value="<?= $catItem->id ?>|MIX|1" data-price="<?= floatval($catItem->cost_price ?? 0) > 0 ? $catItem->cost_price : ($catItem->cost ?? 0.00) ?>" data-name="<?= htmlspecialchars($catItem->name) ?> (MIX)" data-sku="<?= htmlspecialchars($catItem->item_code ?? '') ?>" data-sample-code="<?= htmlspecialchars($catItem->sample_code ?? '') ?>" <?= $selectedValue === "{$catItem->id}|MIX|1" ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($catItem->name) ?> (MIX)
                                                </option>
                                                <?php foreach($catItem->variations as $var): ?>
                                                    <option value="<?= $catItem->id ?>|<?= $var->id ?>|0" data-price="<?= floatval($var->cost ?? 0) > 0 ? $var->cost : (floatval($catItem->cost_price ?? 0) > 0 ? $catItem->cost_price : ($catItem->cost ?? 0.00)) ?>" data-name="<?= htmlspecialchars($catItem->name) ?> - <?= htmlspecialchars($var->variation_name) ?>: <?= htmlspecialchars($var->value_name) ?>" data-sku="<?= htmlspecialchars($var->sku ?? $catItem->item_code ?? '') ?>" data-sample-code="<?= htmlspecialchars($catItem->sample_code ?? '') ?>" <?= $selectedValue === "{$catItem->id}|{$var->id}|0" ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($catItem->name) ?> - <?= htmlspecialchars($var->variation_name) ?>: <?= htmlspecialchars($var->value_name) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </optgroup>
                                        <?php else: ?>
                                            <option class="base-opt" value="<?= $catItem->id ?>|0|0" data-price="<?= floatval($catItem->cost_price ?? 0) > 0 ? $catItem->cost_price : ($catItem->cost ?? 0.00) ?>" data-vendor="<?= $catItem->vendor_id ?>" data-name="<?= htmlspecialchars($catItem->name) ?>" data-sku="<?= htmlspecialchars($catItem->item_code ?? '') ?>" data-sample-code="<?= htmlspecialchars($catItem->sample_code ?? '') ?>" <?= $selectedValue === "{$catItem->id}|0|0" ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($catItem->name) ?>
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                                <input type="hidden" name="desc[]" class="desc-hidden" value="<?= htmlspecialchars($isEdit ? $item->description : $item['name']) ?>">
                            </td>
                            <td><input type="number" name="qty[]" step="1" min="1" value="<?= $isEdit ? $item->quantity : $item['qty'] ?>" onchange="calcTotals()" required></td>
                            <td><input type="number" name="price[]" step="0.01" min="0" value="<?= number_format($isEdit ? $item->unit_price : $item['cost'], 2, '.', '') ?>" onchange="calcTotals()" required></td>
                            <td><input type="text" class="line-total" value="<?= number_format($isEdit ? $item->total : ($item['qty'] * $item['cost']), 2, '.', '') ?>" readonly style="font-weight:bold; color:var(--text-main);"></td>
                            <td><button type="button" class="btn btn-danger" style="padding: 4px 8px; font-size:10px; background:#c62828;" onclick="removeRow(this)">X</button></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Standard Blank Row -->
                    <tr>
                        <td class="line-row-num">1</td>
                        <td>
                            <select name="item_selection[]" class="item-select" onchange="autoFillSelection(this)" required>
                                <option value="">Select Product...</option>
                                <?php foreach($data['catalog_items'] as $item): ?>
                                    <?php if(!empty($item->variations)): ?>
                                        <optgroup label="<?= htmlspecialchars($item->name) ?>" data-vendor="<?= $item->vendor_id ?>">
                                            <option value="<?= $item->id ?>|MIX|1" data-price="<?= floatval($item->cost_price ?? 0) > 0 ? $item->cost_price : ($item->cost ?? 0.00) ?>" data-name="<?= htmlspecialchars($item->name) ?> (MIX)" data-sku="<?= htmlspecialchars($item->item_code ?? '') ?>" data-sample-code="<?= htmlspecialchars($item->sample_code ?? '') ?>">
                                                <?= htmlspecialchars($item->name) ?> (MIX)
                                            </option>
                                            <?php foreach($item->variations as $var): ?>
                                                <option value="<?= $item->id ?>|<?= $var->id ?>|0" data-price="<?= floatval($var->cost ?? 0) > 0 ? $var->cost : (floatval($item->cost_price ?? 0) > 0 ? $item->cost_price : ($item->cost ?? 0.00)) ?>" data-name="<?= htmlspecialchars($item->name) ?> - <?= htmlspecialchars($var->variation_name) ?>: <?= htmlspecialchars($var->value_name) ?>" data-sku="<?= htmlspecialchars($var->sku ?? $item->item_code ?? '') ?>" data-sample-code="<?= htmlspecialchars($item->sample_code ?? '') ?>">
                                                    <?= htmlspecialchars($item->name) ?> - <?= htmlspecialchars($var->variation_name) ?>: <?= htmlspecialchars($var->value_name) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    <?php else: ?>
                                        <option class="base-opt" value="<?= $item->id ?>|0|0" data-price="<?= floatval($item->cost_price ?? 0) > 0 ? $item->cost_price : ($item->cost ?? 0.00) ?>" data-vendor="<?= $item->vendor_id ?>" data-name="<?= htmlspecialchars($item->name) ?>" data-sku="<?= htmlspecialchars($item->item_code ?? '') ?>" data-sample-code="<?= htmlspecialchars($item->sample_code ?? '') ?>">
                                            <?= htmlspecialchars($item->name) ?>
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                            <input type="hidden" name="desc[]" class="desc-hidden">
                        </td>
                        <td><input type="number" name="qty[]" step="1" min="1" value="1" onchange="calcTotals()" required></td>
                        <td><input type="number" name="price[]" step="0.01" min="0" value="0.00" onchange="calcTotals()" required></td>
                        <td><input type="text" class="line-total" value="0.00" readonly style="font-weight:bold; color:var(--text-main);"></td>
                        <td></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <button type="button" class="btn btn-outline" style="margin-top: 10px; font-size:12px; background:transparent; border:1px solid #0066cc; color:#0066cc;" onclick="addRow()">+ Add Item</button>

        <div style="display: flex; justify-content: space-between; margin-top: 20px;">
            <div class="form-group" style="width: 50%;">
                <label>Terms / Notes</label>
                <textarea name="notes" class="form-control" rows="3" placeholder="Enter terms of delivery or specific requests..."><?= htmlspecialchars($data['po']->notes ?? '') ?></textarea>
            </div>
            
            <div class="total-box">
                Grand Total: Rs: <span id="grandTotal">0.00</span>
            </div>
        </div>

        <div style="clear:both; margin-top: 20px; text-align: right;">
            <button type="submit" class="btn" style="padding: 12px 24px; font-size: 16px;"><?= $isEdit ? 'Update Purchase Order' : 'Generate Purchase Order' ?></button>
        </div>
    </form>
</div>

<script>
    var catalogOptions = `
        <option value="">Select Product...</option>
        <?php foreach($data['catalog_items'] as $item): ?>
            <?php if(!empty($item->variations)): ?>
                <optgroup label="<?= htmlspecialchars($item->name) ?>" data-vendor="<?= $item->vendor_id ?>">
                    <option value="<?= $item->id ?>|MIX|1" data-price="<?= floatval($item->cost_price ?? 0) > 0 ? $item->cost_price : ($item->cost ?? 0.00) ?>" data-name="<?= htmlspecialchars($item->name) ?> (MIX)" data-sku="<?= htmlspecialchars($item->item_code ?? '') ?>" data-sample-code="<?= htmlspecialchars($item->sample_code ?? '') ?>">
                        <?= htmlspecialchars($item->name) ?> (MIX)
                    </option>
                    <?php foreach($item->variations as $var): ?>
                        <option value="<?= $item->id ?>|<?= $var->id ?>|0" data-price="<?= floatval($var->cost ?? 0) > 0 ? $var->cost : (floatval($item->cost_price ?? 0) > 0 ? $item->cost_price : ($item->cost ?? 0.00)) ?>" data-name="<?= htmlspecialchars($item->name) ?> - <?= htmlspecialchars($var->variation_name) ?>: <?= htmlspecialchars($var->value_name) ?>" data-sku="<?= htmlspecialchars($var->sku ?? $item->item_code ?? '') ?>" data-sample-code="<?= htmlspecialchars($item->sample_code ?? '') ?>">
                            <?= htmlspecialchars($item->name) ?> - <?= htmlspecialchars($var->variation_name) ?>: <?= htmlspecialchars($var->value_name) ?>
                        </option>
                    <?php endforeach; ?>
                </optgroup>
            <?php else: ?>
                <option class="base-opt" value="<?= $item->id ?>|0|0" data-price="<?= floatval($item->cost_price ?? 0) > 0 ? $item->cost_price : ($item->cost ?? 0.00) ?>" data-vendor="<?= $item->vendor_id ?>" data-name="<?= htmlspecialchars($item->name) ?>" data-sku="<?= htmlspecialchars($item->item_code ?? '') ?>" data-sample-code="<?= htmlspecialchars($item->sample_code ?? '') ?>">
                    <?= htmlspecialchars($item->name) ?>
                </option>
            <?php endif; ?>
        <?php endforeach; ?>
    `;

    function filterProducts() {
        const vendorId = document.getElementById('vendorSelect').value;
        document.querySelectorAll('.item-select').forEach(select => {
            select.querySelectorAll('optgroup, option.base-opt').forEach(group => {
                if (group.getAttribute('data-vendor') === vendorId || vendorId === "" || !group.getAttribute('data-vendor')) {
                    group.style.display = '';
                } else {
                    group.style.display = 'none';
                }
            });
            // Reset if previously selected item is now hidden
            const selectedOpt = select.options[select.selectedIndex];
            if(selectedOpt && (selectedOpt.style.display === 'none' || (selectedOpt.parentElement && selectedOpt.parentElement.style.display === 'none'))) {
                select.value = "";
                const tr = select.closest('tr');
                tr.querySelector('input[name="price[]"]').value = "0.00";
                tr.querySelector('.desc-hidden').value = "";
                calcTotals();
            }
        });
    }

    function autoFillSelection(selectElement) {
        const selectedOption = selectElement.options[selectElement.selectedIndex];
        const price = selectedOption.getAttribute('data-price') || 0;
        const name = selectedOption.getAttribute('data-name') || '';
        
        const tr = selectElement.closest('tr');
        tr.querySelector('input[name="price[]"]').value = parseFloat(price).toFixed(2);
        tr.querySelector('.desc-hidden').value = name;
        calcTotals();
    }

    function renumberLineRows(tbodyId) {
        document.querySelectorAll(`#${tbodyId} tr`).forEach((tr, i) => {
            const cell = tr.querySelector('.line-row-num');
            if (cell) cell.textContent = i + 1;
        });
    }

    function addRow() {
        const tbody = document.getElementById('poBody');
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td class="line-row-num"></td>
            <td>
                <select name="item_selection[]" class="item-select" onchange="autoFillSelection(this)" required>${catalogOptions}</select>
                <input type="hidden" name="desc[]" class="desc-hidden">
            </td>
            <td><input type="number" name="qty[]" step="1" min="1" value="1" onchange="calcTotals()" required></td>
            <td><input type="number" name="price[]" step="0.01" min="0" value="0.00" onchange="calcTotals()" required></td>
            <td><input type="text" class="line-total" value="0.00" readonly style="font-weight:bold; color:var(--text-main);"></td>
            <td><button type="button" class="btn" style="padding: 4px 8px; font-size:10px; background:#c62828; color:#fff;" onclick="removeRow(this)">X</button></td>
        `;
        tbody.appendChild(tr);
        filterProducts();
        renumberLineRows('poBody');
    }

    function removeRow(btn) {
        btn.closest('tr').remove();
        renumberLineRows('poBody');
        calcTotals();
    }

    function calcTotals() {
        let grandTotal = 0;
        document.querySelectorAll('#poBody tr').forEach(row => {
            const qty = parseFloat(row.querySelector('input[name="qty[]"]').value) || 0;
            const price = parseFloat(row.querySelector('input[name="price[]"]').value) || 0;
            const total = qty * price;
            
            const totalInput = row.querySelector('.line-total');
            if(totalInput) { totalInput.value = total.toFixed(2); }
            grandTotal += total;
        });
        document.getElementById('grandTotal').innerText = grandTotal.toFixed(2);
    }

    document.addEventListener("DOMContentLoaded", () => {
        calcTotals();
        filterProducts();
        renumberLineRows('poBody');

        const form = document.getElementById('poForm');
        if (form) {
            form.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    const target = e.target;
                    if (target.tagName === 'INPUT' || target.tagName === 'SELECT') {
                        const row = target.closest('tr');
                        if (row && row.closest('#poBody')) {
                            e.preventDefault();
                            
                            const tbody = document.getElementById('poBody');
                            const lastTr = tbody.querySelector('tr:last-child');
                            if (row === lastTr) {
                                addRow();
                            }
                            
                            setTimeout(() => {
                                const newLastTr = tbody.querySelector('tr:last-child');
                                if (newLastTr) {
                                    const select = newLastTr.querySelector('.item-select');
                                    if (select) {
                                        if (select.tomselect) {
                                            select.tomselect.focus();
                                        } else {
                                            select.focus();
                                        }
                                    }
                                }
                            }, 50);
                        }
                    }
                }
            });
        }
    });
</script>