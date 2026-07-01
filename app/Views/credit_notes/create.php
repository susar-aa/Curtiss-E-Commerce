<style>
    .header-actions { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .btn { padding: 8px 16px; background: #c62828; color: #fff; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; font-size: 13px; font-weight: 600; }
    .btn:hover { background: #b71c1c; }
    .btn-outline { background: transparent; border: 1px solid #c62828; color: #c62828; }
    .btn-outline:hover { background: #ffebee; }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; margin-bottom: 5px; font-size: 13px; font-weight: 500; }
    .form-control { width: 100%; padding: 8px 12px; border: 1px solid var(--mac-border); border-radius: 4px; background: transparent; color: var(--text-main); box-sizing: border-box; }
    .grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; }
    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
    .cn-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    .cn-table th, .cn-table td { padding: 10px; border-bottom: 1px solid var(--mac-border); vertical-align: middle; }
    .cn-table th { background-color: rgba(198, 40, 40, 0.05); text-align: left; color:#c62828;}
    .cn-table input, .cn-table select { width: 100%; border: 1px solid transparent; background: transparent; color: var(--text-main); padding: 5px; box-sizing: border-box; }
    .cn-table input:focus, .cn-table select:focus { border: 1px solid #c62828; outline: none; border-radius: 4px;}
    .cn-table input[type="number"] { text-align: right; }
    .total-box { float: right; width: 300px; padding: 20px; background: rgba(198, 40, 40, 0.05); border-radius: 8px; margin-top: 20px; text-align: right; font-size: 18px; font-weight: bold; color:#c62828;}
    
    .history-card { background: rgba(0,0,0,0.01); border: 1px solid var(--mac-border); border-radius: 6px; padding: 15px; margin-top: 20px; display: none; }
    .history-table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 12px; }
    .history-table th, .history-table td { padding: 8px; border-bottom: 1px dotted var(--mac-border); text-align: left; }
    .history-table th { background: rgba(0,0,0,0.03); font-weight: bold; }
    .history-title { font-size: 13px; font-weight: bold; color: #c62828; display: flex; align-items: center; gap: 6px; margin-bottom: 8px; }

    .autocomplete-items {
        position: absolute;
        border: 1px solid var(--mac-border);
        border-bottom: none;
        border-top: none;
        z-index: 99;
        top: 100%;
        left: 0;
        right: 0;
        background: var(--bg-card);
        max-height: 200px;
        overflow-y: auto;
        border-radius: 0 0 8px 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .autocomplete-item {
        padding: 10px;
        cursor: pointer;
        border-bottom: 1px solid var(--mac-border);
    }
    .autocomplete-item:hover {
        background-color: rgba(198, 40, 40, 0.05);
    }
    .autocomplete-item strong {
        color: #c62828;
    }
</style>

<div class="card">
    <div class="header-actions">
        <h2 style="color:#c62828; display:flex; align-items:center; gap:8px;">
            <span>🔄</span> Issue Credit Note & Customer Return
        </h2>
        <a href="<?= APP_URL ?>/creditnote" style="color: #666; text-decoration:none;">&larr; Back</a>
    </div>

    <?php if(!empty($data['error'])): ?>
        <div style="padding: 10px; background:#ffebee; color:#c62828; border-radius:4px; margin-bottom:15px;"><?= $data['error'] ?></div>
    <?php endif; ?>

    <form action="<?= APP_URL ?>/creditnote/create" method="POST" id="cnForm">
        
        <div style="background: rgba(46, 125, 50, 0.04); padding: 18px 24px; border-radius: 12px; margin-bottom: 25px; border: 1px solid rgba(46, 125, 50, 0.15); display: flex; align-items: center; gap: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.02);">
            <div style="font-size: 26px; background: rgba(46, 125, 50, 0.1); width: 48px; height: 48px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #2e7d32; flex-shrink:0;">⚡</div>
            <div>
                <h4 style="margin: 0 0 4px 0; color: #2e7d32; font-size: 14px; font-weight: 600;">Automated Double-Entry Accounting Enabled</h4>
                <p style="font-size: 11.5px; color: #555; margin: 0; line-height: 1.45;">Manual ledger configuration has been eliminated. The system will automatically reverse sales revenue, deduct the customer's outstanding balance (Accounts Receivable), restock undamaged items, and expense damaged inventory cost adjustments to their respective accounts.</p>
            </div>
        </div>

        <div class="grid-3">
            <div class="form-group">
                <label>Customer *</label>
                <select name="customer_id" id="customerSelect" class="form-control" onchange="onCustomerChange()" required>
                    <option value="">Select Customer...</option>
                    <?php foreach($data['customers'] as $cust): ?>
                        <option value="<?= $cust->id ?>"><?= htmlspecialchars($cust->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Credit Note Number</label>
                <input type="text" name="credit_note_number" class="form-control" value="<?= $data['credit_note_number'] ?>" required>
            </div>
            <div class="form-group">
                <label>Date</label>
                <input type="date" name="note_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>
        </div>

        <table class="cn-table" id="linesTable">
            <thead>
                <tr>
                    <th style="width:40px; text-align:center;">#</th>
                    <th style="width: 35%;">Product returned</th>
                    <th style="width: 12%; text-align:right;">Qty</th>
                    <th style="width: 12%; text-align:right;">Price (Rs:)</th>
                    <th style="width: 15%; text-align:right;">Total (Rs:)</th>
                    <th style="width: 15%;">Condition</th>
                    <th style="width: 5%;"></th>
                </tr>
            </thead>
            <tbody id="cnBody">
                <!-- Items will be appended here -->
            </tbody>
        </table>
        
        <button type="button" class="btn btn-outline" style="margin-top: 10px; font-size:12px; padding:6px 10px;" onclick="addRow()">+ Add Line</button>

        <!-- Product Sales History Section (similar to vendor returns) -->
        <div id="historyContainer" class="history-card">
            <div class="history-title">
                <span style="font-size: 15px;">📊</span>
                <span>Product Invoice Sales History</span>
            </div>
            <p style="font-size: 11px; color:#666; margin: 0 0 10px 0;">Select a sale record below to automatically apply the historical cost price and link the original invoice batch.</p>
            <div id="historyTableContainer"></div>
        </div>

        <div class="total-box">
            Total Credit: Rs: <span id="grandTotal">0.00</span>
        </div>

        <div style="clear:both; margin-top: 20px; text-align: right;">
            <button type="submit" class="btn" style="padding: 12px 24px; font-size: 16px;">Issue Credit Note & Post Ledger</button>
        </div>
    </form>
</div>

<script>
    let customerProducts = [];
    let currentActiveRow = null;

    function onCustomerChange() {
        const customerId = document.getElementById('customerSelect').value;
        const tbody = document.getElementById('cnBody');
        tbody.innerHTML = ''; // Clear all lines
        document.getElementById('historyContainer').style.display = 'none';
        calcTotals();

        if (!customerId) {
            customerProducts = [];
            return;
        }

        // Fetch products sold to this customer
        fetch(`<?= APP_URL ?>/creditnote/get_customer_products?customer_id=${customerId}`)
            .then(res => res.json())
            .then(data => {
                customerProducts = data;
                addRow(); // Add first line automatically
            });
    }

    function addRow() {
        const customerId = document.getElementById('customerSelect').value;
        if (!customerId) {
            alert('Please select a Customer first.');
            return;
        }

        const tbody = document.getElementById('cnBody');
        const tr = document.createElement('tr');
        
        tr.innerHTML = `
            <td class="line-row-num" style="text-align:center; color:#888; font-weight:bold;"></td>
            <td style="position: relative;">
                <input type="text" class="product-search-input" placeholder="Type product name to search..." onfocus="showSuggestions(this)" oninput="filterSuggestions(this)" required>
                <div class="autocomplete-items" style="display: none;"></div>
                <input type="hidden" name="item_selection[]" class="item-selection-hidden">
                <input type="hidden" name="desc[]" class="desc-hidden">
                <input type="hidden" name="invoice_id[]" class="invoice-id-hidden">
                <input type="hidden" name="invoice_item_id[]" class="invoice-item-id-hidden">
            </td>
            <td>
                <div style="position: relative;">
                    <input type="number" name="qty[]" step="1" min="1" value="1" oninput="validateQty(this); calcTotals();" required>
                    <span class="max-qty-badge" style="position: absolute; right: 5px; top: -18px; font-size: 9px; background: #666; color: #fff; padding: 2px 4px; border-radius: 3px; display: none;"></span>
                </div>
            </td>
            <td><input type="number" name="price[]" step="0.01" min="0" value="0.00" oninput="calcTotals()" required></td>
            <td><input type="text" class="line-total" value="0.00" readonly style="font-weight:bold; color:#c62828; text-align:right;"></td>
            <td>
                <select name="condition[]" onchange="calcTotals()" style="padding:4px; font-weight:500;">
                    <option value="Good" style="color:#2e7d32;">Good (Restock)</option>
                    <option value="Damaged" style="color:#d32f2f;">Damaged (Loss)</option>
                </select>
            </td>
            <td style="text-align:center;"><button type="button" class="btn" style="padding: 4px 8px; font-size:10px; background:#666;" onclick="removeRow(this)">X</button></td>
        `;
        tbody.appendChild(tr);
        renumberRows();
    }

    function removeRow(btn) {
        btn.closest('tr').remove();
        renumberRows();
        calcTotals();
        document.getElementById('historyContainer').style.display = 'none';
    }

    function renumberRows() {
        document.querySelectorAll('#cnBody tr').forEach((tr, i) => {
            tr.querySelector('.line-row-num').textContent = i + 1;
        });
    }

    function showSuggestions(input) {
        const tr = input.closest('tr');
        currentActiveRow = tr;
        filterSuggestions(input);
    }

    function filterSuggestions(input) {
        const query = input.value.toLowerCase().trim();
        const container = input.nextElementSibling;
        container.innerHTML = '';
        const tr = input.closest('tr');

        const matched = customerProducts.filter(p => {
            const name = (p.product_name || '').toLowerCase();
            const sku = (p.sku || '').toLowerCase();
            const sampleCode = (p.sample_code || '').toLowerCase();
            return name.includes(query) || sku.includes(query) || sampleCode.includes(query);
        });

        if (matched.length === 0) {
            container.style.display = 'none';
            return;
        }

        container.style.display = 'block';
        matched.forEach(p => {
            const div = document.createElement('div');
            div.className = 'autocomplete-item';
            
            let skuDisplay = '';
            if (p.sku) {
                skuDisplay = ` <span style="font-size:9px; color:#666; font-family:monospace; margin-left:8px;">[SKU: ${escapeHtml(p.sku)}]</span>`;
            }
            if (p.sample_code) {
                skuDisplay += ` <span style="font-size:9px; color:#0066cc; font-family:monospace; margin-left:4px;">[Code: ${escapeHtml(p.sample_code)}]</span>`;
            }

            div.innerHTML = `<strong>${escapeHtml(p.product_name)}</strong>${skuDisplay} <span style="font-size:10px; color:#888;">(Sold: ${parseFloat(p.total_sold).toFixed(0)} Pcs)</span>`;
            div.onclick = function() {
                input.value = p.product_name;
                tr.querySelector('.item-selection-hidden').value = `${p.item_id}|${p.variation_option_id || '0'}`;
                tr.querySelector('.desc-hidden').value = p.product_name;
                
                // Show max quantity badge and enforce limit
                const qtyInput = tr.querySelector('input[name="qty[]"]');
                qtyInput.max = p.max_returnable;
                
                const badge = tr.querySelector('.max-qty-badge');
                badge.textContent = `Max: ${parseFloat(p.max_returnable).toFixed(0)}`;
                badge.style.display = 'inline-block';

                container.style.display = 'none';
                loadProductHistory(p.item_id, p.variation_option_id);
            };
            container.appendChild(div);
        });
    }

    function validateQty(input) {
        const max = parseFloat(input.max) || 0;
        if (max > 0 && parseFloat(input.value) > max) {
            alert(`Quantity exceeds the total returnable balance (${max} Pcs) purchased by this customer.`);
            input.value = max;
        }
    }

    function loadProductHistory(itemId, varOptId) {
        const customerId = document.getElementById('customerSelect').value;
        const val = `${itemId}|${varOptId || '0'}`;

        fetch(`<?= APP_URL ?>/creditnote/get_product_sale_history?customer_id=${customerId}&product_val=${encodeURIComponent(val)}`)
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
            container.innerHTML = '<p style="color: #666; font-style: italic;">No purchase/sale records found for this product.</p>';
            return;
        }

        let html = `
            <table class="history-table">
                <thead>
                    <tr>
                        <th>Invoice Number</th>
                        <th>Sale Date</th>
                        <th style="text-align: right;">Quantity Sold</th>
                        <th style="text-align: right;">Returned Qty</th>
                        <th style="text-align: right;">Selling Price (Rs:)</th>
                        <th style="text-align: center;">Action</th>
                    </tr>
                </thead>
                <tbody>
        `;

        history.forEach(h => {
            html += `
                <tr>
                    <td style="font-weight: bold; color: #c62828;">${escapeHtml(h.invoice_number)}</td>
                    <td>${escapeHtml(h.invoice_date)}</td>
                    <td style="text-align: right;">${parseFloat(h.quantity).toFixed(0)}</td>
                    <td style="text-align: right; color:#888;">${parseFloat(h.returned_qty).toFixed(0)}</td>
                    <td style="text-align: right; font-weight: bold; color:#0066cc;">Rs: ${parseFloat(h.unit_price).toFixed(2)}</td>
                    <td style="text-align: center;">
                        <button type="button" class="btn" style="padding: 4px 8px; font-size:11px; background: #2e7d32;" onclick="applyInvoiceRecord(${h.invoice_id}, ${h.invoice_item_id}, ${h.unit_price}, ${h.max_returnable})">Apply Sale Price</button>
                    </td>
                </tr>
            `;
        });

        html += '</tbody></table>';
        container.innerHTML = html;
    }

    function applyInvoiceRecord(invoiceId, invoiceItemId, price, maxReturnable) {
        if (!currentActiveRow) return;

        currentActiveRow.querySelector('input[name="price[]"]').value = price.toFixed(2);
        currentActiveRow.querySelector('.invoice-id-hidden').value = invoiceId;
        currentActiveRow.querySelector('.invoice-item-id-hidden').value = invoiceItemId;

        const qtyInput = currentActiveRow.querySelector('input[name="qty[]"]');
        qtyInput.max = maxReturnable;
        
        const badge = currentActiveRow.querySelector('.max-qty-badge');
        badge.textContent = `Max: ${maxReturnable}`;
        badge.style.display = 'inline-block';

        calcTotals();

        const card = document.getElementById('historyContainer');
        card.style.backgroundColor = '#e8f5e9';
        setTimeout(() => {
            card.style.backgroundColor = 'rgba(0,0,0,0.01)';
        }, 300);
    }

    function calcTotals() {
        let grandTotal = 0;
        document.querySelectorAll('#cnBody tr').forEach(row => {
            const qty = parseFloat(row.querySelector('input[name="qty[]"]').value) || 0;
            const price = parseFloat(row.querySelector('input[name="price[]"]').value) || 0;
            const total = qty * price;
            row.querySelector('.line-total').value = total.toFixed(2);
            grandTotal += total;
        });
        document.getElementById('grandTotal').innerText = grandTotal.toFixed(2);
    }

    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    // Handle clicking outside autocomplete
    document.addEventListener('click', function(e) {
        document.querySelectorAll('.autocomplete-items').forEach(list => {
            if (!list.contains(e.target) && !list.previousElementSibling.contains(e.target)) {
                list.style.display = 'none';
            }
        });
    });

    document.getElementById('linesTable').addEventListener('click', (e) => {
        const tr = e.target.closest('tr');
        if (tr) currentActiveRow = tr;
    });
</script>