<style>
    .header-actions { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .btn { padding: 8px 16px; background: #2e7d32; color: #fff; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; font-size: 13px; font-weight: 600; }
    .btn:hover { background: #1b5e20; }
    .btn-outline { background: transparent; border: 1px solid #2e7d32; color: #2e7d32; }
    .btn-outline:hover { background: #e8f5e9; }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; margin-bottom: 5px; font-size: 13px; font-weight: 500; }
    .form-control { width: 100%; padding: 8px 12px; border: 1px solid var(--mac-border); border-radius: 4px; background: transparent; color: var(--text-main); box-sizing: border-box; }
    .grid-4 { display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 15px; }
    .line-row-num { text-align: center; color: #888; font-weight: bold; font-size: 12px; vertical-align: middle; }
    .po-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    .po-table th, .po-table td { padding: 10px; border-bottom: 1px solid var(--mac-border); vertical-align: middle;}
    .po-table th { background-color: rgba(0,0,0,0.02); text-align: left; }
    .po-table input, .po-table select { width: 100%; border: 1px solid transparent; background: transparent; color: var(--text-main); padding: 5px; box-sizing: border-box; }
    .po-table input:focus { border: 1px solid #2e7d32; outline: none; border-radius: 4px;}
    
    .price-badge { font-size: 11px; font-weight: bold; padding: 3px 8px; border-radius: 4px; display: inline-block; }
    .price-retail { background: #e3f2fd; color: #1565c0; border: 1px solid #bbdefb; }
    .price-wholesale { background: #f3e5f5; color: #7b1fa2; border: 1px solid #ce93d8; }
    .total-box { float: right; width: 300px; padding: 20px; background: rgba(0,0,0,0.02); border-radius: 8px; margin-top: 20px; text-align: right; font-size: 18px; font-weight: bold;}
</style>

<?php
$isEdit = isset($data['grn']);
$actionUrl = APP_URL . '/grn/' . ($isEdit ? "edit/{$data['grn']->id}" : "create");
?>
<div class="card">
    <div class="header-actions">
        <h2><?= $isEdit ? 'Edit Goods Receipt Note (GRN)' : 'Create Goods Receipt Note (GRN)' ?></h2>
        <a href="<?= APP_URL ?>/grn" style="color: #666; text-decoration:none;">&larr; Back to GRNs</a>
    </div>

    <?php if(!empty($data['error'])): ?>
        <div style="padding: 10px; background:#ffebee; color:#c62828; border-radius:4px; margin-bottom:15px;"><?= $data['error'] ?></div>
    <?php endif; ?>

    <form action="<?= $actionUrl ?>" method="POST" id="grnForm">
        <input type="hidden" name="action" value="<?= $isEdit ? 'update_grn' : 'save_grn' ?>">
        
        <?php if($data['linked_po']): ?>
            <div style="background:#e8f5e9; color:#2e7d32; padding:15px; border-radius:8px; margin-bottom:20px; border: 1px solid rgba(46,125,50,0.2);">
                <strong style="font-size: 16px;">🔗 Linked to Purchase Order: <?= htmlspecialchars($data['linked_po']->po_number) ?></strong>
                <p style="margin:5px 0 0 0; font-size:13px;">Saving this GRN will automatically mark the PO as Received and update your physical inventory stock.</p>
                <input type="hidden" name="po_id" value="<?= $data['linked_po']->id ?>">
            </div>
        <?php endif; ?>

        <div class="grid-4">
            <div class="form-group">
                <label>Supplier / Vendor *</label>
                <select name="vendor_id" id="vendorSelect" class="form-control" onchange="onVendorChange()" required <?= ($data['linked_po'] || $isEdit) ? 'style="pointer-events:none; background:rgba(0,0,0,0.02);"' : '' ?>>
                    <option value="">Select Vendor...</option>
                    <?php foreach($data['vendors'] as $ven): ?>
                        <option value="<?= $ven->id ?>" <?= $data['prefilled_vendor'] == $ven->id ? 'selected' : '' ?>><?= htmlspecialchars($ven->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>GRN Number</label>
                <input type="text" name="grn_number" class="form-control" value="<?= htmlspecialchars($isEdit ? $data['grn']->grn_number : $data['grn_number']) ?>" required <?= $isEdit ? 'readonly style="pointer-events:none; background:rgba(0,0,0,0.02);"' : '' ?>>
            </div>
            <div class="form-group">
                <label>Receipt Date</label>
                <input type="date" name="grn_date" class="form-control" value="<?= htmlspecialchars($isEdit ? $data['grn']->grn_date : date('Y-m-d')) ?>" required>
            </div>
            <div class="form-group">
                <label>Supplier Invoice / Receipt No.</label>
                <input type="text" name="receipt_number" class="form-control" placeholder="Invoice number from supplier" value="<?= htmlspecialchars($isEdit ? ($data['grn']->receipt_number ?? '') : '') ?>">
            </div>
        </div>

        <table class="po-table" id="linesTable">
            <thead>
                <tr>
                    <th style="width: 30px;">#</th>
                    <th style="width: 25%;">Specific Product / Variation Received</th>
                    <th style="width: 8%; text-align:right;">Qty</th>
                    <th style="width: 10%; text-align:right;">Unit Cost (Rs:)</th>
                    <th style="width: 10%; text-align:right;">Line Total</th>
                    <th style="width: 8%; text-align:right;">Retail Margin %</th>
                    <th style="width: 8%; text-align:right;">Wholesale Margin %</th>
                    <th style="width: 12%; text-align:right; color: #1565c0;">Retail Price (Calculated)</th>
                    <th style="width: 12%; text-align:right; color: #7b1fa2;">Wholesale B2B (Calculated)</th>
                    <th style="width: 4%;"></th>
                </tr>
            </thead>
            <tbody id="poBody">
                <?php if(!empty($data['prefilled_items'])): ?>
                    <?php $lineNum = 1; foreach($data['prefilled_items'] as $item): ?>
                        <?php 
                            $retailMargin = isset($item->retail_margin) ? floatval($item->retail_margin) : 0.0;
                            $wholesaleMargin = isset($item->wholesale_margin) ? floatval($item->wholesale_margin) : 0.0;
                            $displayName = $item->description;
                            if ($item->item_id) {
                                foreach($data['catalog_items'] as $catItem) {
                                    if ($catItem->id == $item->item_id) {
                                        if (!isset($item->retail_margin)) {
                                            $retailMargin = floatval($catItem->retail_margin ?? 0);
                                        }
                                        if (!isset($item->wholesale_margin)) {
                                            $wholesaleMargin = floatval($catItem->wholesale_margin ?? 0);
                                        }
                                        $displayName = $catItem->name;
                                        if ($item->item_variation_option_id && !empty($catItem->variations)) {
                                            foreach($catItem->variations as $v) {
                                                if ($v->id == $item->item_variation_option_id) {
                                                    $displayName = "{$catItem->name} - {$v->variation_name}: {$v->value_name}";
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                            $unitCost = isset($item->unit_cost) ? $item->unit_cost : ($item->unit_price ?? 0);
                        ?>
                        <tr>
                            <td class="line-row-num"><?= $lineNum++ ?></td>
                            <td style="position: relative; overflow: visible;">
                                <input type="text" class="form-control autocomplete-search-input" value="<?= htmlspecialchars($displayName) ?>" placeholder="Type product name/SKU..." autocomplete="off" style="border: 1px solid var(--mac-border); border-radius: 4px; padding: 6px 10px; background: transparent; color: var(--text-main); font-size:12px;" required>
                                <div class="autocomplete-suggestions-wrapper" style="position: absolute; top: calc(100% + 2px); left: 10px; right: 10px; background: #ffffff; border: 1px solid var(--mac-border); border-radius: 6px; max-height: 200px; overflow-y: auto; box-shadow: 0 4px 15px rgba(0,0,0,0.15); z-index: 9999; display: none;"></div>
                                <input type="hidden" name="item_selection[]" class="item-selection-hidden" value="<?= $item->item_id ?>|<?= $item->item_variation_option_id ?: '0' ?>" required>
                                <input type="hidden" name="desc[]" class="desc-hidden" value="<?= htmlspecialchars($displayName) ?>">
                            </td>
                            <td><input type="number" name="qty[]" step="1" min="1" value="<?= $item->quantity ?>" oninput="calculateRowPrices(this.closest('tr'))" required style="border: 1px solid var(--mac-border); border-radius: 4px; padding: 4px 6px;"></td>
                            <td><input type="number" name="price[]" step="0.01" min="0" value="<?= number_format($unitCost, 2, '.', '') ?>" oninput="calculateRowPrices(this.closest('tr'))" required style="border: 1px solid var(--mac-border); border-radius: 4px; padding: 4px 6px; font-weight:600; text-align:right;"></td>
                            <td style="text-align: right; vertical-align: middle;">
                                <span class="line-total-display" style="font-weight:bold; color:var(--text-main);">0.00</span>
                            </td>
                            <td><input type="number" name="retail_margin[]" step="0.1" value="<?= number_format($retailMargin, 1, '.', '') ?>" oninput="calculateRowPrices(this.closest('tr'))" required style="border: 1px solid var(--mac-border); border-radius: 4px; padding: 4px 6px; text-align:right;"></td>
                            <td><input type="number" name="wholesale_margin[]" step="0.1" value="<?= number_format($wholesaleMargin, 1, '.', '') ?>" oninput="calculateRowPrices(this.closest('tr'))" required style="border: 1px solid var(--mac-border); border-radius: 4px; padding: 4px 6px; text-align:right;"></td>
                            <td style="text-align: right; vertical-align: middle;">
                                <span class="price-badge price-retail display-retail">0.00</span>
                                <input type="hidden" name="selling_price[]" value="0.00">
                            </td>
                            <td style="text-align: right; vertical-align: middle;">
                                <span class="price-badge price-wholesale display-wholesale">0.00</span>
                                <input type="hidden" name="wholesale_price[]" value="0.00">
                            </td>
                            <td><button type="button" class="btn btn-danger" style="padding: 4px 8px; font-size:10px; background:#c62828; color:#fff;" onclick="removeRow(this)">X</button></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Blank Row -->
                    <tr>
                        <td class="line-row-num">1</td>
                        <td style="position: relative; overflow: visible;">
                            <input type="text" class="form-control autocomplete-search-input" placeholder="Type product name/SKU..." autocomplete="off" style="border: 1px solid var(--mac-border); border-radius: 4px; padding: 6px 10px; background: transparent; color: var(--text-main); font-size:12px;" required>
                            <div class="autocomplete-suggestions-wrapper" style="position: absolute; top: calc(100% + 2px); left: 10px; right: 10px; background: #ffffff; border: 1px solid var(--mac-border); border-radius: 6px; max-height: 200px; overflow-y: auto; box-shadow: 0 4px 15px rgba(0,0,0,0.15); z-index: 9999; display: none;"></div>
                            <input type="hidden" name="item_selection[]" class="item-selection-hidden" required>
                            <input type="hidden" name="desc[]" class="desc-hidden">
                        </td>
                        <td><input type="number" name="qty[]" step="1" min="1" value="1" oninput="calculateRowPrices(this.closest('tr'))" required style="border: 1px solid var(--mac-border); border-radius: 4px; padding: 4px 6px;"></td>
                        <td><input type="number" name="price[]" step="0.01" min="0" value="0.00" oninput="calculateRowPrices(this.closest('tr'))" required style="border: 1px solid var(--mac-border); border-radius: 4px; padding: 4px 6px; font-weight:600; text-align:right;"></td>
                        <td style="text-align: right; vertical-align: middle;">
                            <span class="line-total-display" style="font-weight:bold; color:var(--text-main);">0.00</span>
                        </td>
                        <td><input type="number" name="retail_margin[]" step="0.1" value="0.0" oninput="calculateRowPrices(this.closest('tr'))" required style="border: 1px solid var(--mac-border); border-radius: 4px; padding: 4px 6px; text-align:right;"></td>
                        <td><input type="number" name="wholesale_margin[]" step="0.1" value="0.0" oninput="calculateRowPrices(this.closest('tr'))" required style="border: 1px solid var(--mac-border); border-radius: 4px; padding: 4px 6px; text-align:right;"></td>
                        <td style="text-align: right; vertical-align: middle;">
                            <span class="price-badge price-retail display-retail">0.00</span>
                            <input type="hidden" name="selling_price[]" value="0.00">
                        </td>
                        <td style="text-align: right; vertical-align: middle;">
                            <span class="price-badge price-wholesale display-wholesale">0.00</span>
                            <input type="hidden" name="wholesale_price[]" value="0.00">
                        </td>
                        <td></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <button type="button" class="btn btn-outline" style="margin-top: 10px; font-size:12px;" onclick="addRow()">+ Add Received Item</button>

        <div style="display: flex; justify-content: space-between; margin-top: 20px; align-items: flex-start;">
            <div class="form-group" style="width: 50%;">
                <label>Inspection Notes / Damages</label>
                <textarea name="notes" class="form-control" rows="3" placeholder="Log any damages or notes regarding this delivery..."><?= htmlspecialchars($isEdit ? ($data['grn']->notes ?? '') : '') ?></textarea>
            </div>
            
            <div class="total-box">
                Grand Total: Rs: <span id="grandTotal">0.00</span>
            </div>
        </div>

        <div style="margin-top: 20px; text-align: right;">
            <button type="submit" class="btn" style="padding: 12px 24px; font-size: 16px;">Save GRN & Update Inventory</button>
        </div>
    </form>
</div>

<script>
    // Injected catalog items with preloaded variations
    var catalogItems = <?= json_encode($data['catalog_items']) ?>;
    
    // Generate flattened list of searchable elements (main items + variation options)
    var searchableItems = [];
    catalogItems.forEach(item => {
        if (item.variations && item.variations.length > 0) {
            item.variations.forEach(v => {
                searchableItems.push({
                    item_id: item.id,
                    var_opt_id: v.id,
                    display_name: `${item.name} - ${v.variation_name}: ${v.value_name}`,
                    sku: v.sku || item.item_code || '',
                    vendor_id: item.vendor_id,
                    cost: parseFloat(v.cost && parseFloat(v.cost) > 0 ? v.cost : (item.cost_price && parseFloat(item.cost_price) > 0 ? item.cost_price : (item.cost ?? 0))),
                    price: parseFloat(v.price ?? item.price ?? 0),
                    retail_margin: parseFloat(item.retail_margin ?? 0),
                    wholesale_margin: parseFloat(item.wholesale_margin ?? 0),
                    wholesale_price: parseFloat(item.wholesale_price ?? 0)
                });
            });
        } else {
            searchableItems.push({
                item_id: item.id,
                var_opt_id: 0,
                display_name: item.name,
                sku: item.item_code || '',
                vendor_id: item.vendor_id,
                cost: parseFloat(item.cost_price && parseFloat(item.cost_price) > 0 ? item.cost_price : (item.cost ?? 0)),
                price: parseFloat(item.price ?? 0),
                retail_margin: parseFloat(item.retail_margin ?? 0),
                wholesale_margin: parseFloat(item.wholesale_margin ?? 0),
                wholesale_price: parseFloat(item.wholesale_price ?? 0)
            });
        }
    });

    function onVendorChange() {
        const tbody = document.getElementById('poBody');
        tbody.innerHTML = ''; // Clear all current lines to prevent vendor mixing
        addRow(); // Insert a single clean row
    }

    function initAutocomplete(row) {
        const input = row.querySelector('.autocomplete-search-input');
        const wrapper = row.querySelector('.autocomplete-suggestions-wrapper');
        const hiddenSelection = row.querySelector('.item-selection-hidden');
        const hiddenDesc = row.querySelector('.desc-hidden');

        input.addEventListener('input', function() {
            const query = this.value.toLowerCase().trim();
            const vendorId = document.getElementById('vendorSelect').value;
            
            wrapper.innerHTML = '';
            
            if (!vendorId) {
                wrapper.innerHTML = '<div style="padding: 8px 12px; font-size:11px; color:#c62828; font-weight:600;">⚠️ Select Supplier / Vendor first</div>';
                wrapper.style.display = 'block';
                return;
            }

            if (query.length < 1) {
                wrapper.style.display = 'none';
                return;
            }

            // Filter by selected vendor and search string
            const matches = searchableItems.filter(item => 
                parseInt(item.vendor_id) === parseInt(vendorId) && 
                (item.display_name.toLowerCase().includes(query) || item.sku.toLowerCase().includes(query))
            ).slice(0, 10);

            if (matches.length === 0) {
                wrapper.innerHTML = '<div style="padding: 8px 12px; font-size:11px; color:#888; font-style:italic;">No vendor products found</div>';
                wrapper.style.display = 'block';
                return;
            }

            matches.forEach(m => {
                const itemDiv = document.createElement('div');
                itemDiv.className = 'autocomplete-suggestion-item';
                itemDiv.style.padding = '8px 12px';
                itemDiv.style.fontSize = '12px';
                itemDiv.style.cursor = 'pointer';
                itemDiv.style.borderBottom = '1px solid #f5f5f7';
                itemDiv.style.display = 'flex';
                itemDiv.style.justifyContent = 'space-between';
                itemDiv.style.alignItems = 'center';
                
                itemDiv.innerHTML = `
                    <span style="font-weight: 500; color: #333;">${escapeHtml(m.display_name)}</span>
                    <span style="font-size: 10px; color:#888; font-family:monospace;">${escapeHtml(m.sku)}</span>
                `;
                
                itemDiv.addEventListener('mouseover', () => {
                    itemDiv.style.background = '#e8f5e9';
                    itemDiv.style.color = '#2e7d32';
                });
                itemDiv.addEventListener('mouseout', () => {
                    itemDiv.style.background = '';
                    itemDiv.style.color = '';
                });

                itemDiv.addEventListener('click', () => {
                    input.value = m.display_name;
                    hiddenSelection.value = `${m.item_id}|${m.var_opt_id}`;
                    hiddenDesc.value = m.display_name;
                    wrapper.style.display = 'none';

                    // Populate prices, margins and costs
                    row.querySelector('input[name="price[]"]').value = m.cost.toFixed(2);
                    row.querySelector('input[name="retail_margin[]"]').value = m.retail_margin.toFixed(1);
                    row.querySelector('input[name="wholesale_margin[]"]').value = m.wholesale_margin.toFixed(1);
                    
                    calculateRowPrices(row);
                });

                wrapper.appendChild(itemDiv);
            });

            wrapper.style.display = 'block';
        });

        // Hide suggestions when clicking outside
        document.addEventListener('click', function(e) {
            if (!input.contains(e.target) && !wrapper.contains(e.target)) {
                wrapper.style.display = 'none';
            }
        });
    }

    function calculateRowPrices(row) {
        const qty = parseFloat(row.querySelector('input[name="qty[]"]').value) || 0;
        const cost = parseFloat(row.querySelector('input[name="price[]"]').value) || 0;
        const retailMargin = parseFloat(row.querySelector('input[name="retail_margin[]"]').value) || 0;
        const wholesaleMargin = parseFloat(row.querySelector('input[name="wholesale_margin[]"]').value) || 0;

        const lineTotal = qty * cost;
        const lineTotalSpan = row.querySelector('.line-total-display');
        if (lineTotalSpan) {
            lineTotalSpan.textContent = lineTotal.toFixed(2);
        }

        const calculatedRetail = cost + (cost * retailMargin / 100);
        const calculatedWholesale = cost + (cost * wholesaleMargin / 100);

        row.querySelector('.display-retail').textContent = calculatedRetail.toFixed(2);
        row.querySelector('.display-wholesale').textContent = calculatedWholesale.toFixed(2);
        
        // Populate inputs to submit
        row.querySelector('input[name="selling_price[]"]').value = calculatedRetail.toFixed(2);
        row.querySelector('input[name="wholesale_price[]"]').value = calculatedWholesale.toFixed(2);

        calcGrandTotal();
    }

    function calcGrandTotal() {
        let grandTotal = 0;
        document.querySelectorAll('#poBody tr').forEach(row => {
            const qty = parseFloat(row.querySelector('input[name="qty[]"]').value) || 0;
            const cost = parseFloat(row.querySelector('input[name="price[]"]').value) || 0;
            grandTotal += qty * cost;
        });
        const grandTotalSpan = document.getElementById('grandTotal');
        if (grandTotalSpan) {
            grandTotalSpan.textContent = grandTotal.toFixed(2);
        }
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
            <td style="position: relative; overflow: visible;">
                <input type="text" class="form-control autocomplete-search-input" placeholder="Type product name/SKU..." autocomplete="off" style="border: 1px solid var(--mac-border); border-radius: 4px; padding: 6px 10px; background: transparent; color: var(--text-main); font-size:12px;" required>
                <div class="autocomplete-suggestions-wrapper" style="position: absolute; top: calc(100% + 2px); left: 10px; right: 10px; background: #ffffff; border: 1px solid var(--mac-border); border-radius: 6px; max-height: 200px; overflow-y: auto; box-shadow: 0 4px 15px rgba(0,0,0,0.15); z-index: 9999; display: none;"></div>
                <input type="hidden" name="item_selection[]" class="item-selection-hidden" required>
                <input type="hidden" name="desc[]" class="desc-hidden">
            </td>
            <td><input type="number" name="qty[]" step="1" min="1" value="1" oninput="calculateRowPrices(this.closest('tr'))" required style="border: 1px solid var(--mac-border); border-radius: 4px; padding: 4px 6px;"></td>
            <td><input type="number" name="price[]" step="0.01" min="0" value="0.00" oninput="calculateRowPrices(this.closest('tr'))" required style="border: 1px solid var(--mac-border); border-radius: 4px; padding: 4px 6px; font-weight:600; text-align:right;"></td>
            <td style="text-align: right; vertical-align: middle;">
                <span class="line-total-display" style="font-weight:bold; color:var(--text-main);">0.00</span>
            </td>
            <td><input type="number" name="retail_margin[]" step="0.1" value="0.0" oninput="calculateRowPrices(this.closest('tr'))" required style="border: 1px solid var(--mac-border); border-radius: 4px; padding: 4px 6px; text-align:right;"></td>
            <td><input type="number" name="wholesale_margin[]" step="0.1" value="0.0" oninput="calculateRowPrices(this.closest('tr'))" required style="border: 1px solid var(--mac-border); border-radius: 4px; padding: 4px 6px; text-align:right;"></td>
            <td style="text-align: right; vertical-align: middle;">
                <span class="price-badge price-retail display-retail">0.00</span>
                <input type="hidden" name="selling_price[]" value="0.00">
            </td>
            <td style="text-align: right; vertical-align: middle;">
                <span class="price-badge price-wholesale display-wholesale">0.00</span>
                <input type="hidden" name="wholesale_price[]" value="0.00">
            </td>
            <td><button type="button" class="btn btn-danger" style="padding: 4px 8px; font-size:10px; background:#c62828; color:#fff;" onclick="removeRow(this)">X</button></td>
        `;
        tbody.appendChild(tr);
        initAutocomplete(tr);
        renumberLineRows('poBody');
    }

    function removeRow(btn) {
        btn.closest('tr').remove();
        renumberLineRows('poBody');
        calcGrandTotal();
    }

    function escapeHtml(str) {
        if (!str) return '';
        return str
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    document.addEventListener("DOMContentLoaded", () => {
        // Initialize autocomplete on any prefilled rows
        document.querySelectorAll('#poBody tr').forEach(row => {
            initAutocomplete(row);
            calculateRowPrices(row);
        });
        renumberLineRows('poBody');

        const form = document.getElementById('grnForm');
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
                                    const newInput = newLastTr.querySelector('.autocomplete-search-input');
                                    if (newInput) newInput.focus();
                                }
                            }, 50);
                        }
                    }
                }
            });
        }
    });
</script>