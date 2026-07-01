<?php
?>
<style>
    .header-actions { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .btn { padding: 8px 16px; background: #0066cc; color: #fff; border: none; border-radius: 4px; cursor: pointer; }
    .btn-danger { background: #ff3b30; }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; margin-bottom: 5px; font-size: 13px; font-weight: 500; }
    .form-control { width: 100%; padding: 8px 12px; border: 1px solid var(--mac-border); border-radius: 4px; background: transparent; color: var(--text-main); box-sizing: border-box; }
    .grid-4 { display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 15px; }
    .estimate-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    .estimate-table th, .estimate-table td { padding: 10px; border-bottom: 1px solid var(--mac-border); }
    .estimate-table th { background-color: rgba(0,0,0,0.02); text-align: left; }
    .estimate-table input, .estimate-table select { width: 100%; border: 1px solid transparent; background: transparent; color: var(--text-main); padding: 5px; box-sizing: border-box; }
    .estimate-table input:focus, .estimate-table select:focus { border: 1px solid #0066cc; outline: none; border-radius: 4px;}
    .estimate-table input[type="number"] { text-align: right; }
    .total-box { float: right; width: 300px; padding: 20px; background: rgba(0,0,0,0.02); border-radius: 8px; margin-top: 20px; text-align: right; font-size: 18px; font-weight: bold;}
</style>

<div class="card">
    <div class="header-actions">
        <h2>Create Estimate</h2>
        <a href="<?= APP_URL ?>/estimate" style="color: #666; text-decoration:none;">&larr; Back</a>
    </div>

    <?php if(!empty($data['error'])): ?>
        <div style="padding: 10px; background:#ffebee; color:#c62828; border-radius:4px; margin-bottom:15px;"><?= $data['error'] ?></div>
    <?php endif; ?>

    <form action="<?= APP_URL ?>/estimate/create" method="POST" id="estimateForm">
        
        <div class="grid-4">
            <div class="form-group">
                <label>Customer *</label>
                <select name="customer_id" class="form-control" required>
                    <option value="">Select Customer...</option>
                    <?php foreach($data['customers'] as $cust): ?>
                        <option value="<?= $cust->id ?>"><?= htmlspecialchars($cust->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Estimate Number</label>
                <input type="text" name="estimate_number" class="form-control" value="<?= $data['estimate_number'] ?>" required>
            </div>
            <div class="form-group">
                <label>Date</label>
                <input type="date" name="estimate_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="form-group">
                <label>Valid Until</label>
                <input type="date" name="expiry_date" class="form-control" value="<?= date('Y-m-d', strtotime('+30 days')) ?>" required>
            </div>
        </div>

        <table class="estimate-table" id="linesTable">
            <thead>
                <tr>
                    <th style="width:40px;">#</th>
                    <th style="width: 50%;">Product / Service</th>
                    <th style="width: 15%; text-align:right;">Qty</th>
                    <th style="width: 15%; text-align:right;">Price (Rs:)</th>
                    <th style="width: 15%; text-align:right;">Total (Rs:)</th>
                    <th style="width: 5%;"></th>
                </tr>
            </thead>
            <tbody id="estimateBody">
                <tr>
                    <td class="line-row-num" style="text-align:center; color:#888; font-weight:bold;">1</td>
                    <td>
                        <select name="desc[]" onchange="autoFillPrice(this)" required>
                            <option value="">Select Product...</option>
                            <?php foreach($data['catalog_items'] as $item): ?>
                                <option value="<?= htmlspecialchars($item->name) ?>" data-price="<?= $item->price ?>" data-sku="<?= htmlspecialchars($item->item_code ?? '') ?>" data-sample-code="<?= htmlspecialchars($item->sample_code ?? '') ?>">
                                    <?= htmlspecialchars($item->name) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td><input type="number" name="qty[]" step="1" min="1" value="1" onchange="calcTotals()" required></td>
                    <td><input type="number" name="price[]" step="0.01" min="0" value="0.00" onchange="calcTotals()" required></td>
                    <td><input type="text" class="line-total" value="0.00" readonly style="font-weight:bold; color:var(--text-main);"></td>
                    <td></td>
                </tr>
            </tbody>
        </table>
        
        <button type="button" class="btn btn-outline" style="margin-top: 10px; font-size:12px;" onclick="addRow()">+ Add Line</button>

        <div class="total-box">
            Grand Total: Rs: <span id="grandTotal">0.00</span>
        </div>

        <div style="clear:both; margin-top: 20px; text-align: right;">
            <button type="submit" class="btn" style="padding: 12px 24px; font-size: 16px;">Save Estimate</button>
        </div>
    </form>
</div>

<script>
    const catalogOptions = `
        <option value="">Select Product...</option>
        <?php foreach($data['catalog_items'] as $item): ?>
            <option value="<?= htmlspecialchars($item->name) ?>" data-price="<?= $item->price ?>" data-sku="<?= htmlspecialchars($item->item_code ?? '') ?>" data-sample-code="<?= htmlspecialchars($item->sample_code ?? '') ?>">
                <?= htmlspecialchars($item->name) ?>
            </option>
        <?php endforeach; ?>
    `;

    function autoFillPrice(selectElement) {
        const selectedOption = selectElement.options[selectElement.selectedIndex];
        const price = selectedOption.getAttribute('data-price') || 0;
        const tr = selectElement.closest('tr');
        const priceInput = tr.querySelector('input[name="price[]"]');
        priceInput.value = parseFloat(price).toFixed(2);
        calcTotals();
    }

    function renumberLineRows(tbodyId) {
        document.querySelectorAll(`#${tbodyId} tr`).forEach((tr, i) => {
            const cell = tr.querySelector('.line-row-num');
            if (cell) cell.textContent = i + 1;
        });
    }

    function addRow() {
        const tbody = document.getElementById('estimateBody');
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td class="line-row-num" style="text-align:center; color:#888; font-weight:bold;"></td>
            <td><select name="desc[]" onchange="autoFillPrice(this)" required>${catalogOptions}</select></td>
            <td><input type="number" name="qty[]" step="1" min="1" value="1" onchange="calcTotals()" required></td>
            <td><input type="number" name="price[]" step="0.01" min="0" value="0.00" onchange="calcTotals()" required></td>
            <td><input type="text" class="line-total" value="0.00" readonly style="font-weight:bold; color:var(--text-main);"></td>
            <td><button type="button" class="btn btn-danger" style="padding: 4px 8px; font-size:10px;" onclick="removeRow(this)">X</button></td>
        `;
        tbody.appendChild(tr);
        renumberLineRows('estimateBody');
    }

    function removeRow(btn) {
        btn.closest('tr').remove();
        renumberLineRows('estimateBody');
        calcTotals();
    }

    function calcTotals() {
        let grandTotal = 0;
        document.querySelectorAll('#estimateBody tr').forEach(row => {
            const qty = parseFloat(row.querySelector('input[name="qty[]"]').value) || 0;
            const price = parseFloat(row.querySelector('input[name="price[]"]').value) || 0;
            const total = qty * price;
            row.querySelector('.line-total').value = total.toFixed(2);
            grandTotal += total;
        });
        document.getElementById('grandTotal').innerText = grandTotal.toFixed(2);
    }
</script>