<style>
    .header-actions { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .btn { padding: 8px 16px; background: #c62828; color: #fff; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; font-size: 13px; font-weight: 600; }
    .btn:hover { background: #b71c1c; }
    .btn-secondary { background: #666; }
    .btn-secondary:hover { background: #555; }
    .btn-outline { background: transparent; border: 1px solid #c62828; color: #c62828; }
    .btn-outline:hover { background: #ffebee; }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; margin-bottom: 5px; font-size: 13px; font-weight: 500; }
    .form-control { width: 100%; padding: 8px 12px; border: 1px solid var(--mac-border); border-radius: 4px; background: transparent; color: var(--text-main); box-sizing: border-box; }
    .grid-4 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; }
    .po-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    .po-table th, .po-table td { padding: 10px; border-bottom: 1px solid var(--mac-border); vertical-align: top;}
    .po-table th { background-color: rgba(0,0,0,0.02); text-align: left; }
    .po-table input, .po-table select { width: 100%; border: 1px solid transparent; background: transparent; color: var(--text-main); padding: 5px; box-sizing: border-box; }
    .po-table input:focus, .po-table select:focus { border: 1px solid #c62828; outline: none; border-radius: 4px;}
    .line-row-num { text-align: center; color: #888; font-weight: bold; font-size: 12px; vertical-align: middle; }
    
    .history-card { background: rgba(0,0,0,0.01); border: 1px solid var(--mac-border); border-radius: 6px; padding: 15px; margin-top: 10px; display: none; }
    .history-table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 12px; }
    .history-table th, .history-table td { padding: 8px; border-bottom: 1px dotted var(--mac-border); text-align: left; }
    .history-table th { background: rgba(0,0,0,0.03); font-weight: bold; }
    .history-title { font-size: 13px; font-weight: bold; color: #c62828; display: flex; align-items: center; gap: 6px; margin-bottom: 8px; }
</style>

<div class="card">
    <div class="header-actions">
        <h2>Create Supplier Return Note (SRN)</h2>
        <a href="<?= APP_URL ?>/supplier-return" style="color: #666; text-decoration:none;">&larr; Back to Returns</a>
    </div>

    <?php if(!empty($data['error'])): ?>
        <div style="padding: 10px; background:#ffebee; color:#c62828; border-radius:4px; margin-bottom:15px;"><?= $data['error'] ?></div>
    <?php endif; ?>

    <form action="<?= APP_URL ?>/supplier-return/create" method="POST" id="returnForm">
        <input type="hidden" name="action" value="save_return">
        <input type="hidden" name="total_amount_hidden" id="totalAmountHidden" value="0.00">

        <div class="grid-4">
            <div class="form-group">
                <label>Supplier / Vendor *</label>
                <select name="vendor_id" id="vendorSelect" class="form-control" onchange="onVendorChange()" required>
                    <option value="">Select Vendor...</option>
                    <?php foreach($data['vendors'] as $ven): ?>
                        <option value="<?= $ven->id ?>"><?= htmlspecialchars($ven->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Return Number</label>
                <input type="text" name="return_number" class="form-control" value="<?= htmlspecialchars($data['return_number']) ?>" required>
            </div>
            <div class="form-group">
                <label>Return Date</label>
                <input type="date" name="return_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>
        </div>

        <table class="po-table" id="linesTable">
            <thead>
                <tr>
                    <th style="width: 30px;">#</th>
                    <th style="width: 40%;">Select Purchased Product</th>
                    <th style="width: 10%; text-align:right;">Return Qty</th>
                    <th style="width: 15%; text-align:right;">Purchase Cost (Rs:)</th>
                    <th style="width: 15%; text-align:right;">Total Return (Rs:)</th>
                    <th style="width: 15%;">Original GRN batch</th>
                    <th style="width: 5%;"></th>
                </tr>
            </thead>
            <tbody id="linesBody">
                <!-- Line items will be added here -->
            </tbody>
        </table>

        <button type="button" class="btn btn-outline" style="margin-top: 10px; font-size:12px;" onclick="addRow()">+ Add Item to Return</button>

        <!-- Product Purchase History Section (Loads dynamically when item selected) -->
        <div id="historyContainer" class="history-card">
            <div class="history-title">
                <span style="font-size: 15px;">📊</span>
                <span>Product Purchase History (Cost & GRN Batches)</span>
            </div>
            <p style="font-size: 11px; color:#666; margin: 0 0 10px 0;">Select a cost record below to automatically apply it to the selected return line.</p>
            <div id="historyTableContainer"></div>
        </div>

        <div class="form-group" style="margin-top: 20px;">
            <label>Reason for Return / Additional Notes</label>
            <textarea name="notes" class="form-control" rows="3" placeholder="Explain the reason for returning these goods to the supplier..."></textarea>
        </div>

        <div style="margin-top: 20px; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--mac-border); padding-top: 15px;">
            <div style="font-size: 16px; font-weight: bold; color: #c62828;">
                Total Return Value: Rs: <span id="totalDisplay">0.00</span>
            </div>
            <button type="submit" class="btn" style="padding: 12px 24px; font-size: 16px;">Save Return & Deduct Stock</button>
        </div>
    </form>
</div>

<script>
    let vendorProducts = [];
    let currentActiveRow = null;

    function onVendorChange() {
        const vendorId = document.getElementById('vendorSelect').value;
        const tbody = document.getElementById('linesBody');
        tbody.innerHTML = ''; // Clear all lines
        document.getElementById('historyContainer').style.display = 'none';
        updateTotal();

        if (!vendorId) {
            vendorProducts = [];
            return;
        }

        // Fetch purchased products from vendor
        fetch(`<?= APP_URL ?>/supplier-return/get_vendor_products?vendor_id=${vendorId}`)
            .then(res => res.json())
            .then(data => {
                vendorProducts = data;
                addRow(); // Add first line automatically
            });
    }

    function addRow() {
        const vendorId = document.getElementById('vendorSelect').value;
        if (!vendorId) {
            alert('Please select a Supplier first.');
            return;
        }

        const tbody = document.getElementById('linesBody');
        const tr = document.createElement('tr');
        
        let selectOptions = '<option value="">Select product...</option>';
        vendorProducts.forEach(p => {
            const skuVal = p.var_sku || p.sku || '';
            const sampleCodeVal = p.sample_code || '';
            selectOptions += `<option value="${p.item_id}|${p.var_opt_id || '0'}" data-sku="${escapeHtml(skuVal)}" data-sample-code="${escapeHtml(sampleCodeVal)}">${escapeHtml(p.product_name)}</option>`;
        });

        tr.innerHTML = `
            <td class="line-row-num"></td>
            <td>
                <select name="item_selection[]" class="item-select" onchange="onItemChange(this)" required>
                    ${selectOptions}
                </select>
                <input type="hidden" name="desc[]" class="desc-hidden">
                <input type="hidden" name="grn_id[]" class="grn-id-hidden">
            </td>
            <td><input type="number" name="qty[]" step="1" min="1" value="1" oninput="calculateLineTotal(this)" required></td>
            <td><input type="number" name="price[]" step="0.01" min="0" value="0.00" oninput="calculateLineTotal(this)" required></td>
            <td style="text-align: right; font-weight: bold; padding-top: 15px;">Rs: <span class="line-total-display">0.00</span></td>
            <td>
                <input type="text" name="grn_display[]" class="grn-display-input" placeholder="No batch linked" readonly style="font-size: 11px; background: rgba(0,0,0,0.03); border-radius: 4px; padding: 4px 8px;">
            </td>
            <td><button type="button" class="btn btn-danger" style="padding: 4px 8px; font-size:10px; background:#c62828;" onclick="removeRow(this)">X</button></td>
        `;

        tbody.appendChild(tr);
        renumberRows();
    }

    function removeRow(btn) {
        btn.closest('tr').remove();
        renumberRows();
        updateTotal();
        document.getElementById('historyContainer').style.display = 'none';
    }

    function renumberRows() {
        document.querySelectorAll('#linesBody tr').forEach((tr, i) => {
            tr.querySelector('.line-row-num').textContent = i + 1;
        });
    }

    function onItemChange(select) {
        const tr = select.closest('tr');
        currentActiveRow = tr;
        
        const selectedOption = select.options[select.selectedIndex];
        tr.querySelector('.desc-hidden').value = selectedOption.textContent;

        const val = select.value;
        if (!val) {
            document.getElementById('historyContainer').style.display = 'none';
            return;
        }

        const vendorId = document.getElementById('vendorSelect').value;

        // Fetch purchase history of this specific item from this vendor
        fetch(`<?= APP_URL ?>/supplier-return/get_product_history?vendor_id=${vendorId}&product_val=${encodeURIComponent(val)}`)
            .then(res => res.json())
            .then(history => {
                renderHistoryTable(history);
            });
    }

    function renderHistoryTable(history) {
        const container = document.getElementById('historyTableContainer');
        const card = document.getElementById('historyContainer');
        card.style.display = 'block';

        if (history.length === 0) {
            container.innerHTML = '<p style="color: #666; font-style: italic;">No purchase history found for this product from this supplier.</p>';
            return;
        }

        let html = `
            <table class="history-table">
                <thead>
                    <tr>
                        <th>GRN Number</th>
                        <th>Purchase Date</th>
                        <th style="text-align: right;">Unit Cost (Rs:)</th>
                        <th style="text-align: right;">Qty Purchased</th>
                        <th style="text-align: center;">Action</th>
                    </tr>
                </thead>
                <tbody>
        `;

        history.forEach(h => {
            html += `
                <tr>
                    <td style="font-weight: bold; color: #c62828;">${escapeHtml(h.grn_number)}</td>
                    <td>${escapeHtml(h.grn_date)}</td>
                    <td style="text-align: right; font-weight: bold;">Rs: ${parseFloat(h.unit_cost).toFixed(2)}</td>
                    <td style="text-align: right;">${parseFloat(h.quantity).toFixed(0)}</td>
                    <td style="text-align: center;">
                        <button type="button" class="btn" style="padding: 4px 8px; font-size:11px; background: #2e7d32;" onclick="selectHistoryRecord('${h.grn_id}', '${escapeHtml(h.grn_number)}', ${h.unit_cost})">Apply Batch</button>
                    </td>
                </tr>
            `;
        });

        html += '</tbody></table>';
        container.innerHTML = html;
    }

    function selectHistoryRecord(grnId, grnNumber, cost) {
        if (!currentActiveRow) return;
        
        currentActiveRow.querySelector('input[name="price[]"]').value = cost.toFixed(2);
        currentActiveRow.querySelector('.grn-id-hidden').value = grnId;
        currentActiveRow.querySelector('.grn-display-input').value = grnNumber;
        
        calculateLineTotal(currentActiveRow.querySelector('input[name="price[]"]'));
        
        // Flash display to show selection succeeded
        const card = document.getElementById('historyContainer');
        card.style.backgroundColor = '#e8f5e9';
        setTimeout(() => {
            card.style.backgroundColor = 'rgba(0,0,0,0.01)';
        }, 300);
    }

    function calculateLineTotal(input) {
        const tr = input.closest('tr');
        const qty = parseFloat(tr.querySelector('input[name="qty[]"]').value) || 0;
        const cost = parseFloat(tr.querySelector('input[name="price[]"]').value) || 0;
        
        const lineTotal = qty * cost;
        tr.querySelector('.line-total-display').textContent = lineTotal.toFixed(2);
        
        updateTotal();
    }

    function updateTotal() {
        let grandTotal = 0;
        document.querySelectorAll('#linesBody tr').forEach(tr => {
            const qty = parseFloat(tr.querySelector('input[name="qty[]"]').value) || 0;
            const cost = parseFloat(tr.querySelector('input[name="price[]"]').value) || 0;
            grandTotal += qty * cost;
        });

        document.getElementById('totalDisplay').textContent = grandTotal.toFixed(2);
        document.getElementById('totalAmountHidden').value = grandTotal.toFixed(2);
    }

    function escapeHtml(str) {
        return str
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    // Set focus handler to update current active row when clicking on a row's components
    document.getElementById('linesBody').addEventListener('click', (e) => {
        const tr = e.target.closest('tr');
        if (tr) currentActiveRow = tr;
    });
</script>
