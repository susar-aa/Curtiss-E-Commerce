<?php
// Smart Failsafe Data Fetcher
$db = new Database();



$catalog_items = $data['catalog_items'] ?? [];
if (empty($catalog_items)) {
    $db->query("SELECT * FROM items ORDER BY name ASC");
    $catalog_items = $db->resultSet();
}

// Enforce Wholesale B2B pricing preference and load variations from JSON if needed
foreach ($catalog_items as $key => $item) {
    $billingPrice = 0.00;
    if (is_object($item)) {
        if (isset($item->wholesale_price) && floatval($item->wholesale_price) > 0) {
            $billingPrice = floatval($item->wholesale_price);
        } elseif (isset($item->selling_price) && floatval($item->selling_price) > 0) {
            $billingPrice = floatval($item->selling_price);
        } elseif (isset($item->price) && floatval($item->price) > 0) {
            $billingPrice = floatval($item->price);
        } elseif (isset($item->regular_price) && floatval($item->regular_price) > 0) {
            $billingPrice = floatval($item->regular_price);
        }
        $item->price = $billingPrice;
        $item->selling_price = $billingPrice;
        $item->regular_price = $billingPrice;
        $item->wholesale_price = $billingPrice;

        if (!isset($item->variations) || empty($item->variations)) {
            $db->query("SELECT ivo.*, v.name as variation_name, vv.value_name 
                        FROM item_variation_options ivo
                        JOIN variations v ON ivo.variation_id = v.id
                        JOIN variation_values vv ON ivo.variation_value_id = vv.id
                        WHERE ivo.item_id = :id");
            $db->bind(':id', $item->id);
            $item->variations = $db->resultSet() ?: [];

            if (empty($item->variations) && !empty($item->variations_json)) {
                $decoded = json_decode($item->variations_json);
                if (is_array($decoded)) {
                    $item->variations = [];
                    foreach ($decoded as $v) {
                        $vObj = new stdClass();
                        $vObj->id = $v->id ?? 0;
                        $vObj->variation_name = 'Option';
                        $vObj->value_name = $v->attribute ?? '';
                        $vObj->sku = $v->sku ?? '';
                        $vObj->quantity_on_hand = $v->qty ?? $item->qty ?? $item->quantity_on_hand ?? 0;
                        $vObj->quantity_reserved = $v->quantity_reserved ?? 0;

                        $vPrice = 0.00;
                        if (isset($v->wholesale_price) && floatval($v->wholesale_price) > 0) {
                            $vPrice = floatval($v->wholesale_price);
                        } elseif (isset($v->price) && floatval($v->price) > 0) {
                            $vPrice = floatval($v->price);
                        } else {
                            $vPrice = $billingPrice;
                        }
                        $vObj->price = $vPrice;
                        $item->variations[] = $vObj;
                    }
                }
            } else if (!empty($item->variations)) {
                foreach ($item->variations as $var) {
                    $varPrice = 0.00;
                    if (isset($var->wholesale_price) && floatval($var->wholesale_price) > 0) {
                        $varPrice = floatval($var->wholesale_price);
                    } elseif (isset($var->price) && floatval($var->price) > 0) {
                        $varPrice = floatval($var->price);
                    } else {
                        $varPrice = $billingPrice;
                    }
                    $var->price = $varPrice;
                }
            }
        }
    } elseif (is_array($item)) {
        if (isset($item['wholesale_price']) && floatval($item['wholesale_price']) > 0) {
            $billingPrice = floatval($item['wholesale_price']);
        } elseif (isset($item['selling_price']) && floatval($item['selling_price']) > 0) {
            $billingPrice = floatval($item['selling_price']);
        } elseif (isset($item['price']) && floatval($item['price']) > 0) {
            $billingPrice = floatval($item['price']);
        } elseif (isset($item['regular_price']) && floatval($item['regular_price']) > 0) {
            $billingPrice = floatval($item['regular_price']);
        }
        $catalog_items[$key]['price'] = $billingPrice;
        $catalog_items[$key]['selling_price'] = $billingPrice;
        $catalog_items[$key]['regular_price'] = $billingPrice;
        $catalog_items[$key]['wholesale_price'] = $billingPrice;
    }
}

// Fetch Customers WITH Territory Name and Real-Time Outstanding Balance
$db->query("SELECT c.*, m.name as mca_name,
               (SELECT COALESCE(SUM(total_amount - COALESCE(CASE WHEN global_discount_type = '%' THEN (total_amount * global_discount_val / 100) ELSE global_discount_val END, 0) + COALESCE(tax_amount, 0)), 0) FROM invoices WHERE customer_id = c.id AND status != 'Voided') 
               - 
               (SELECT COALESCE(SUM(amount), 0) FROM customer_payments WHERE customer_id = c.id) 
               - 
               (SELECT COALESCE(SUM(total_amount), 0) FROM credit_notes WHERE customer_id = c.id) 
               AS outstanding_balance
            FROM customers c 
            LEFT JOIN mca_areas m ON c.mca_id = m.id 
            ORDER BY c.name ASC");
$customers = $db->resultSet();

// Fetch Employees specifically marked as Reps
$db->query("SELECT * FROM employees WHERE status = 'Active' AND job_title = 'Rep' ORDER BY first_name ASC");
$reps = $db->resultSet();
?>
<style>
    /* Full-Screen App Layout CSS */
    html, body { overflow: hidden; height: 100%; margin: 0; }
    
    .qb-wrapper {
        background-color: #f2f5f8;
        font-family: Arial, Helvetica, sans-serif;
        font-size: 12px;
        color: #000;
        height: calc(100vh - 30px); /* Account for Mac top bar */
        display: flex;
        flex-direction: column;
        overflow: hidden;
        padding: 10px;
        box-sizing: border-box;
    }
    
    .qb-container {
        background: #fff;
        width: 100%;
        max-width: 1300px;
        margin: 0 auto;
        flex: 1;
        display: flex;
        flex-direction: column;
        border: 1px solid #b0c4de;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        padding: 10px 15px;
        box-sizing: border-box;
        overflow: hidden;
    }

    #invoiceForm {
        display: flex;
        flex-direction: column;
        height: 100%;
        overflow: hidden;
    }

    .qb-title { font-size: 26px; color: #7994b5; font-weight: bold; margin: 0 0 10px 0; letter-spacing: -1px; }

    .qb-input { border: 1px solid #999; padding: 4px; font-size: 12px; font-family: Arial, sans-serif; box-sizing: border-box; }
    .qb-input:focus { border-color: #0066cc; outline: none; background: #f0f8ff;}

    .qb-box { border: 1px solid #999; background: #fff; }
    .qb-box-header { background: #7a7a7a; color: #fff; font-weight: bold; padding: 3px 6px; font-size: 11px; }

    .qb-grid-top { display: flex; justify-content: space-between; margin-bottom: 10px; flex-shrink: 0;}
    .qb-grid-mid { display: flex; border: 1px solid #999; margin-bottom: 10px; background:#fff; flex-shrink: 0;}
    .qb-mid-col { flex: 1; border-right: 1px solid #999; display: flex; flex-direction: column; }
    .qb-mid-col:last-child { border-right: none; }
    .qb-mid-header { background: #7a7a7a; color: #fff; padding: 3px; text-align: center; font-size: 11px; font-weight: bold;}
    .qb-mid-body { padding: 4px; }
    .qb-mid-body input, .qb-mid-body select { width: 100%; border: none; text-align: center; background: transparent; font-size: 12px;}

    .search-wrapper { position: relative; margin-bottom: 10px; flex-shrink: 0;}
    .item-search-bar { width: 100%; padding: 8px; border: 2px solid #7994b5; font-size: 13px; font-weight: bold; border-radius: 4px; box-sizing: border-box; background: #f0f8ff;}
    
    .search-results { position: absolute; top: 100%; left: 0; width: 100%; background: #fff; border: 1px solid #999; border-top: none; max-height: 200px; overflow-y: auto; z-index: 1000; display: none; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
    .search-results li { padding: 8px 10px; cursor: pointer; list-style: none; border-bottom: 1px solid #eee; font-size: 13px; display: flex; justify-content: space-between;}
    .search-results li:hover { background: #0066cc; color: #fff; }

    .table-scroll-container {
        flex: 1;
        overflow-y: auto;
        border: 1px solid #999;
        margin-bottom: 10px;
        background: #fff;
    }

    .qb-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
    .qb-table thead th { position: sticky; top: 0; background: #7a7a7a; color: #fff; padding: 4px; text-align: left; font-size: 11px; font-weight: bold; border-right: 1px solid #999; border-bottom: 1px solid #555; z-index: 10;}
    .qb-table td { padding: 2px; border-bottom: 1px solid #ccc; border-right: 1px solid #ccc; vertical-align: top;}
    .qb-table input { width: 100%; border: none; background: transparent; padding: 4px; font-size: 12px; font-family: Arial; box-sizing: border-box;}
    .qb-table input:focus { background: #f0f8ff; outline: 1px solid #0066cc; }
    .qb-table .num { text-align: right; }
    
    .discount-cell { display: flex; border: 1px solid transparent; border-radius: 2px; background: #fff; overflow: hidden; }
    .discount-cell:focus-within { border-color: #0066cc; outline: 1px solid #0066cc;}
    .discount-cell input { border: none; width: 60%; }
    .discount-cell select { border: none; border-left: 1px solid #ccc; width: 40%; padding: 2px; background: #f9f9f9; font-size: 12px;}

    .qb-footer { display: flex; justify-content: space-between; align-items: flex-end; flex-shrink: 0;}
    .qb-totals { text-align: right; font-size: 13px;}
    .qb-totals-row { display: flex; justify-content: flex-end; margin-bottom: 5px; gap: 15px;}
    .qb-totals-row strong { width: 120px; text-align: left; }
    .qb-totals-row span { width: 100px; text-align: right; font-family: Tahoma, sans-serif;}

    .qb-action-bar { background: #e8ecf0; padding: 10px; border-top: 1px solid #b0c4de; display: flex; justify-content: space-between; align-items: center; margin-top: 10px; flex-shrink: 0;}
    .qb-btn { background: #f0f0f0; border: 1px solid #999; padding: 5px 15px; cursor: pointer; font-size: 12px; border-radius: 2px; font-weight: bold;}
    .qb-btn:hover { background: #e0e0e0; }
    .qb-btn-primary { background: #e0eaf5; border-color: #7994b5; }
    
    .wa-btn { background: #25D366; color: #fff; border-color: #1da851; }
    .wa-btn:hover { background: #20ba56; }
</style>

<div class="qb-wrapper">
    <?php if(!empty($data['error'])): ?>
        <div style="padding: 10px; background:#ffebee; color:#c62828; border:1px solid #ef9a9a; margin-bottom:5px; font-weight:bold; flex-shrink:0;"><?= $data['error'] ?></div>
    <?php endif; ?>
    <?php if(isset($_SESSION['flash_error'])): ?>
        <div style="padding: 10px; background:#ffebee; color:#c62828; border:1px solid #ef9a9a; margin-bottom:5px; font-weight:bold; flex-shrink:0;">
            <?= $_SESSION['flash_error']; unset($_SESSION['flash_error']); ?>
        </div>
    <?php endif; ?>

    <div class="qb-container">
        <form action="<?= APP_URL ?>/salesorder/store" method="POST" id="invoiceForm">
            
            <div class="qb-grid-top">
                <div style="width: 300px;">
                    <div class="qb-title">New Sales Order</div>
                    <div class="qb-box" style="position: relative;">
                        <div class="qb-box-header">Bill To</div>
                        <input type="hidden" name="customer_id" id="customerIdInput" required>
                        <input type="text" id="customerSearch" class="qb-input" style="width: 100%; border:none; border-bottom:1px solid #ccc; font-weight:bold; padding: 6px;" placeholder="🔍 Search Customer by Name, Route..." onkeyup="filterCustomerSearch(event)" autocomplete="off" required>
                        <ul id="customerSearchResults" class="search-results" style="width: 100%;"></ul>
                        <textarea id="billToAddress" class="qb-input" style="width: 100%; height: 60px; border:none; resize:none;" readonly placeholder="Customer address will appear here..."></textarea>
                    </div>
                    <div id="customerOutstanding" style="font-size: 11px; padding: 5px; border-radius: 4px; margin-top: 5px; display: none;"></div>
                </div>

                <div style="display: flex; gap: 10px; align-items: flex-start;">
                    <div class="qb-box" style="width: 120px;">
                        <div class="qb-box-header">Date</div>
                        <input type="date" name="invoice_date" class="qb-input" style="width: 100%; border:none; text-align:center;" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="qb-box" style="width: 120px;">
                        <div class="qb-box-header">Order #</div>
                        <input type="text" name="order_number" class="qb-input" style="width: 100%; border:none; text-align:center;" value="<?= htmlspecialchars((string)($data['order_number'] ?? '')) ?>" required readonly style="background:#eee;">
                    </div>
                </div>
            </div>

            <div class="qb-grid-mid">
                <div class="qb-mid-col">
                    <div class="qb-mid-header">P.O. No.</div>
                    <div class="qb-mid-body"><input type="text" name="po_number" placeholder="Optional"></div>
                </div>
                <div class="qb-mid-col">
                    <div class="qb-mid-header">Terms</div>
                    <div class="qb-mid-body">
                        <select name="payment_term_id" id="paymentTermSelect" onchange="calculateDueDateOffset()" style="width: 100%; border: none; text-align: center; background: transparent; font-size: 12px;">
                            <option value="">Select Term...</option>
                            <?php foreach($data['payment_terms'] as $term): ?>
                                <option value="<?= $term->id ?>" data-days="<?= $term->days_due ?>">
                                    <?= htmlspecialchars($term->name) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="qb-mid-col">
                    <div class="qb-mid-header">Expected Due Date</div>
                    <div class="qb-mid-body"><input type="date" name="due_date" id="dueDate" value="<?= date('Y-m-d') ?>" required></div>
                </div>
                <div class="qb-mid-col">
                    <div class="qb-mid-header">Rep</div>
                    <div class="qb-mid-body">
                        <select name="rep_name">
                            <option value="">Select Rep...</option>
                            <?php foreach($reps as $rep): ?>
                                <option value="<?= htmlspecialchars((string)($rep->first_name . ' ' . $rep->last_name)) ?>">
                                    <?= htmlspecialchars((string)($rep->first_name . ' ' . $rep->last_name)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="qb-mid-col">
                    <div class="qb-mid-header">MCA Route</div>
                    <div class="qb-mid-body"><input type="text" name="mca" id="displayMca" placeholder="Route Info"></div>
                </div>
                <div class="qb-mid-col">
                    <div class="qb-mid-header">Rep TP#</div>
                    <div class="qb-mid-body"><input type="text" name="rep_tp" id="displayPhone" placeholder="Phone #"></div>
                </div>
            </div>

            <div class="search-wrapper">
                <input type="text" id="itemSearch" class="item-search-bar" placeholder="🔍 Search Catalog by Item Code or Name to add to Sales Order..." onkeyup="filterSearch(event)" autocomplete="off">
                <ul id="searchResults" class="search-results"></ul>
            </div>

            <div class="table-scroll-container">
                <table class="qb-table">
                    <thead>
                        <tr>
                            <th style="width: 30px; text-align:center;">#</th>
                            <th style="width: 12%;">Item Code</th>
                            <th style="width: 7%;">Qty</th>
                            <th style="width: 32%;">Description</th>
                            <th style="width: 13%; text-align:right;">Rate (Rs:)</th>
                            <th style="width: 14%; text-align:right;">Discount</th>
                            <th style="width: 14%; text-align:right;">Amount (Rs:)</th>
                            <th style="width: 28px; background:#c62828;"></th>
                        </tr>
                    </thead>
                    <tbody id="invoiceBody">
                        <!-- Items Prepended Here -->
                    </tbody>
                </table>
            </div>

            <div class="qb-footer">
                <div style="width: 350px;">
                    <div style="margin-bottom: 5px;">
                        <label style="font-weight:bold; display:block; margin-bottom:2px; font-size:11px;">Customer Message</label>
                        <select class="qb-input" style="width:100%; margin-bottom:5px;">
                            <option value="">Thank you for your order.</option>
                            <option value="">Stock reservation holds for 7 days.</option>
                        </select>
                    </div>
                    <div>
                        <label style="font-weight:bold; display:block; margin-bottom:2px; font-size:11px;">Memo / Internal Notes</label>
                        <input type="text" name="notes" class="qb-input" style="width:100%;">
                    </div>
                </div>

                <div class="qb-totals">
                    <div class="qb-totals-row">
                        <strong>Subtotal LKR</strong>
                        <span id="subTotal">0.00</span>
                    </div>
                    <div class="qb-totals-row" style="align-items: center;">
                        <strong>Bill Discount</strong>
                        <div class="discount-cell" style="width: 100px; border-color:#ccc;">
                            <input type="number" name="global_discount_val" id="globalDiscVal" value="0" class="qb-input num" style="width:60px; border:none;" oninput="calcTotals()">
                            <select name="global_discount_type" id="globalDiscType" class="qb-input" style="width:40px; padding:0; border:none; border-left:1px solid #ccc; background:#f9f9f9;" onchange="calcTotals()">
                                <option value="Rs">Rs</option>
                                <option value="%">%</option>
                            </select>
                        </div>
                    </div>
                    <div class="qb-totals-row" style="font-size: 14px; margin-top: 5px;">
                        <strong>Total LKR</strong>
                        <span id="grandTotal" style="font-weight: bold;">0.00</span>
                    </div>
                </div>
            </div>

            <div class="qb-action-bar">
                <div style="display:flex; gap: 10px; align-items:center;">
                    <span style="color:#0066cc; font-weight:bold;">● Sales Order Workspace (Pending Approval)</span>
                </div>
                <div style="display:flex; gap: 10px;">
                    <button type="submit" class="qb-btn qb-btn-primary">Save Sales Order</button>
                    <button type="button" class="qb-btn" onclick="window.location.href='<?= APP_URL ?>/sales'">Cancel</button>
                </div>
            </div>

        </form>
    </div>
</div>

<script>
    const catalog = [
        <?php foreach($catalog_items as $item): ?>
            <?php 
            $hasVars = !empty($item->variations);
            $stockQty = isset($item->qty) ? $item->qty : ($item->quantity_on_hand ?? 0);
            $baseAvailable = $stockQty - ($item->quantity_reserved ?? 0);
            $sampleCode = htmlspecialchars(addslashes((string)($item->sample_code ?? '')));
            ?>
            <?php if($hasVars): ?>
                { id: "<?= $item->id ?>|MIX|1", type: "<?= $item->type ?? 'Inventory' ?>", stock: <?= floatval($baseAvailable) ?>, code: "<?= htmlspecialchars((string)($item->item_code ?? '')) ?>", name: "<?= htmlspecialchars(addslashes((string)($item->name ?? ''))) ?> (MIX)", price: <?= floatval($item->price ?? 0) ?>, sample_code: "<?= $sampleCode ?>" },
                <?php foreach($item->variations as $var): ?>
                <?php $varAvailable = ($var->quantity_on_hand ?? 0) - ($var->quantity_reserved ?? 0); ?>
                { id: "<?= $item->id ?>|<?= $var->id ?>|0", type: "<?= $item->type ?? 'Inventory' ?>", stock: <?= floatval($varAvailable) ?>, code: "<?= htmlspecialchars(addslashes((string)($var->sku ?? $item->item_code ?? ''))) ?>", name: "<?= htmlspecialchars(addslashes((string)($item->name ?? ''))) ?> - <?= htmlspecialchars(addslashes((string)($var->variation_name ?? ''))) ?>: <?= htmlspecialchars(addslashes((string)($var->value_name ?? ''))) ?>", price: <?= floatval(isset($var->price) && $var->price > 0 ? $var->price : ($item->price ?? 0)) ?>, sample_code: "<?= $sampleCode ?>" },
                <?php endforeach; ?>
            <?php else: ?>
                { id: "<?= $item->id ?>|0|0", type: "<?= $item->type ?? 'Inventory' ?>", stock: <?= floatval($baseAvailable) ?>, code: "<?= htmlspecialchars((string)($item->item_code ?? '')) ?>", name: "<?= htmlspecialchars(addslashes((string)($item->name ?? ''))) ?>", price: <?= floatval($item->price ?? 0) ?>, sample_code: "<?= $sampleCode ?>" },
            <?php endif; ?>
        <?php endforeach; ?>
    ];

    const customers = [
        <?php foreach($customers as $c): ?>
        {
            id: "<?= $c->id ?>",
            name: <?= json_encode((string)($c->name ?? '')) ?>,
            phone: <?= json_encode((string)($c->phone ?? '')) ?>,
            address: <?= json_encode((string)($c->address ?? '')) ?>,
            mca: <?= json_encode((string)($c->mca_name ?? $c->territory ?? '')) ?>,
            outstanding: <?= floatval($c->outstanding_balance ?? 0) ?>
        },
        <?php endforeach; ?>
    ];

    function escapeHtml(str) {
        if (!str) return '';
        return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
    }

    function filterCustomerSearch(e) {
        const val = e.target.value.toLowerCase().trim();
        const resList = document.getElementById('customerSearchResults');
        resList.innerHTML = '';
        if(!val) { resList.style.display = 'none'; return; }

        const filtered = customers.filter(c => 
            c.name.toLowerCase().includes(val) || 
            c.phone.toLowerCase().includes(val) || 
            c.mca.toLowerCase().includes(val) || 
            c.address.toLowerCase().includes(val)
        ).slice(0, 10);

        if(filtered.length === 0) {
            const li = document.createElement('li');
            li.style.padding = '8px 10px';
            li.style.color = '#888';
            li.innerText = 'No customers found';
            resList.appendChild(li);
            resList.style.display = 'block';
            return;
        }

        filtered.forEach(cust => {
            const li = document.createElement('li');
            li.style.padding = '8px 10px';
            li.style.borderBottom = '1px solid #eee';
            li.style.cursor = 'pointer';
            li.style.display = 'flex';
            li.style.flexDirection = 'column';
            
            li.innerHTML = `
                <div style="width: 100%;">
                    <strong style="font-size: 12px; color: #111;">${escapeHtml(cust.name)}</strong>
                    <div style="font-size: 10px; color: #666; margin-top: 3px; line-height: 1.3;">
                        ${cust.phone ? `📞 ${escapeHtml(cust.phone)}<br>` : ''}
                        ${cust.mca ? `📍 Route: <strong>${escapeHtml(cust.mca)}</strong><br>` : ''}
                        ${cust.address ? `<span style="font-style: italic; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 260px;">🏠 ${escapeHtml(cust.address)}</span>` : ''}
                    </div>
                </div>
            `;
            
            li.onclick = () => { selectCustomer(cust); };
            resList.appendChild(li);
        });
        resList.style.display = 'block';
    }

    function selectCustomer(cust) {
        document.getElementById('customerIdInput').value = cust.id;
        document.getElementById('customerSearch').value = cust.name;
        document.getElementById('customerSearchResults').style.display = 'none';
        document.getElementById('billToAddress').value = cust.address || '';
        
        const mcaInput = document.getElementById('displayMca');
        if(mcaInput) mcaInput.value = cust.mca || '';
        
        const phoneInput = document.getElementById('displayPhone');
        if(phoneInput) phoneInput.value = cust.phone || '';

        const outBal = parseFloat(cust.outstanding) || 0;
        const outDiv = document.getElementById('customerOutstanding');
        
        if (cust.id !== "" && (outBal > 0.01 || outBal < -0.01)) {
            outDiv.style.display = 'block';
            if (outBal > 0) {
                outDiv.style.background = '#ffebee';
                outDiv.style.color = '#c62828';
                outDiv.style.border = '1px solid #ef9a9a';
                outDiv.innerHTML = `⚠ Outstanding Balance: Rs <span>${outBal.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>`;
            } else {
                outDiv.style.background = '#e8f5e9';
                outDiv.style.color = '#2e7d32';
                outDiv.style.border = '1px solid #a5d6a7';
                outDiv.innerHTML = `✓ Available Credit: Rs <span>${Math.abs(outBal).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>`;
            }
        } else {
            outDiv.style.display = 'none';
        }
    }

    function filterSearch(e) {
        const val = e.target.value.toLowerCase().trim();
        const resList = document.getElementById('searchResults');
        resList.innerHTML = '';
        if(!val) { resList.style.display = 'none'; return; }

        const filtered = catalog.filter(i => 
            i.name.toLowerCase().includes(val) || 
            i.code.toLowerCase().includes(val) ||
            (i.sample_code && i.sample_code.toLowerCase().includes(val))
        ).slice(0, 15);
        if(filtered.length === 0) { resList.style.display = 'none'; return; }

        filtered.forEach(item => {
            const li = document.createElement('li');
            let stockBadge = item.type === 'Service' ? 'Service' : `Stock: ${item.stock}`;
            li.innerHTML = `
                <div><strong>${item.name}</strong><br><span style="font-size: 11px; color: #888;">SKU: ${item.code || 'N/A'}${item.sample_code ? ' | Sample: ' + item.sample_code : ''} | ${stockBadge}</span></div>
                <div style="color: #0066cc; font-family: monospace; font-weight: bold; font-size: 14px;">Rs: ${item.price.toFixed(2)}</div>
            `;
            li.onclick = () => { 
                addItemRow(item); 
                e.target.value = ''; 
                resList.style.display = 'none'; 
                document.getElementById('itemSearch').focus(); 
            };
            resList.appendChild(li);
        });
        resList.style.display = 'block';
    }

    document.addEventListener('click', function(e) {
        if(e.target.id !== 'itemSearch') { document.getElementById('searchResults').style.display = 'none'; }
        if(e.target.id !== 'customerSearch') { document.getElementById('customerSearchResults').style.display = 'none'; }
    });

    function renumberInvoiceRows() {
        document.querySelectorAll('#invoiceBody tr').forEach((tr, i) => {
            const cell = tr.querySelector('.line-row-num');
            if (cell) cell.textContent = i + 1;
        });
    }

    function addItemRow(item, code = null, qty = 1, desc = null, price = null, discVal = 0, discType = 'Rs') {
        // Check for duplicates in the existing invoice rows
        let isDuplicate = false;
        document.querySelectorAll('input[name="item_selection[]"]').forEach(input => {
            if (input.value === item.id) {
                isDuplicate = true;
            }
        });

        const tbody = document.getElementById('invoiceBody');
        const tr = document.createElement('tr');
        
        let stockHtml = item.type === 'Service' ? '' : `<br><span style="font-size: 10px; color: #888;">Stock: ${item.stock}</span>`;

        tr.innerHTML = `
            <td class="line-row-num" style="text-align:center; color:#888; font-weight:bold; vertical-align:middle;"></td>
            <td>
                <input type="text" value="${item.code || 'ITEM'}" readonly style="color:#666; font-size:12px; width: 100%; border:none; background:transparent;">
                <input type="hidden" name="item_selection[]" value="${item.id}">
            </td>
            <td style="text-align: center;">
                <input type="number" class="num" name="qty[]" value="${qty}" min="0.01" step="0.01" oninput="calcTotals()" required style="width: 60px; margin-bottom: 2px;">
                ${stockHtml}
            </td>
            <td><input type="text" name="desc[]" value="${item.name}" required style="width: 100%; border:none; background:transparent;"></td>
            <td><input type="number" class="num" name="price[]" value="${parseFloat(item.price).toFixed(2)}" step="0.01" min="0" oninput="calcTotals()" required style="width: 100%; text-align: right; box-sizing: border-box; padding-right: 4px;"></td>
            <td>
                <div class="discount-cell">
                    <input type="number" class="num" name="item_discount_val[]" value="${parseFloat(discVal).toFixed(2)}" step="0.01" oninput="calcTotals()">
                    <select name="item_discount_type[]" onchange="calcTotals()">
                        <option value="Rs">Rs</option>
                        <option value="%">%</option>
                    </select>
                </div>
            </td>
            <td><input type="text" class="num line-total" value="0.00" readonly style="font-weight:bold; background: transparent; border: none; width: 100%; text-align: right; padding-right: 4px;"></td>
            <td style="text-align:center;"><button type="button" tabindex="-1" style="background:transparent; color:#c62828; border:none; cursor:pointer; font-weight:bold; font-size: 16px; padding: 4px;" onclick="this.closest('tr').remove(); renumberInvoiceRows(); calcTotals();">&times;</button></td>
        `;
        tbody.insertAdjacentElement('afterbegin', tr);
        renumberInvoiceRows();
        calcTotals();
        document.querySelector('.table-scroll-container').scrollTop = 0;

        if (isDuplicate) {
            showDuplicateWarning(item.name, null, () => {
                tr.remove();
                renumberInvoiceRows();
                calcTotals();
            });
        }
    }

    function showDuplicateWarning(itemName, onKeep, onRemove) {
        // Create backdrop
        const backdrop = document.createElement('div');
        backdrop.style.position = 'fixed';
        backdrop.style.top = '0';
        backdrop.style.left = '0';
        backdrop.style.width = '100%';
        backdrop.style.height = '100%';
        backdrop.style.backgroundColor = 'rgba(0, 0, 0, 0.5)';
        backdrop.style.zIndex = '9999';
        backdrop.style.display = 'flex';
        backdrop.style.alignItems = 'center';
        backdrop.style.justifyContent = 'center';

        // Create modal content panel
        const panel = document.createElement('div');
        panel.style.backgroundColor = '#fff';
        panel.style.padding = '24px';
        panel.style.borderRadius = '8px';
        panel.style.boxShadow = '0 10px 25px rgba(0, 0, 0, 0.2)';
        panel.style.width = '90%';
        panel.style.maxWidth = '400px';
        panel.style.textAlign = 'center';
        panel.style.fontFamily = 'system-ui, -apple-system, sans-serif';

        // Add Icon / Title
        const title = document.createElement('h3');
        title.innerText = '⚠️ Duplicate Product Warning';
        title.style.margin = '0 0 12px 0';
        title.style.color = '#d97706';
        title.style.fontSize = '18px';

        // Add message
        const msg = document.createElement('p');
        msg.innerHTML = `Product <strong>${itemName}</strong> is already added to this sales order.<br><br>Do you want to keep both or remove the duplicate?`;
        msg.style.margin = '0 0 20px 0';
        msg.style.color = '#4b5563';
        msg.style.fontSize = '14px';
        msg.style.lineHeight = '1.5';

        // Create actions container
        const actions = document.createElement('div');
        actions.style.display = 'flex';
        actions.style.justifyContent = 'center';
        actions.style.gap = '12px';

        // Remove button
        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.innerText = '🗑️ Remove Duplicate';
        removeBtn.style.padding = '8px 16px';
        removeBtn.style.border = '1px solid #dc2626';
        removeBtn.style.backgroundColor = '#fff';
        removeBtn.style.color = '#dc2626';
        removeBtn.style.borderRadius = '6px';
        removeBtn.style.fontWeight = '600';
        removeBtn.style.cursor = 'pointer';
        removeBtn.style.fontSize = '13px';
        removeBtn.onclick = () => {
            document.body.removeChild(backdrop);
            if (onRemove) onRemove();
        };

        // Keep button
        const keepBtn = document.createElement('button');
        keepBtn.type = 'button';
        keepBtn.innerText = '✓ Keep Both';
        keepBtn.style.padding = '8px 16px';
        keepBtn.style.border = 'none';
        keepBtn.style.backgroundColor = '#10b981';
        keepBtn.style.color = '#fff';
        keepBtn.style.borderRadius = '6px';
        keepBtn.style.fontWeight = '600';
        keepBtn.style.cursor = 'pointer';
        keepBtn.style.fontSize = '13px';
        keepBtn.onclick = () => {
            document.body.removeChild(backdrop);
            if (onKeep) onKeep();
        };

        actions.appendChild(removeBtn);
        actions.appendChild(keepBtn);
        panel.appendChild(title);
        panel.appendChild(msg);
        panel.appendChild(actions);
        backdrop.appendChild(panel);
        document.body.appendChild(backdrop);
    }

    function calcTotals() {
        let subTotal = 0;
        document.querySelectorAll('#invoiceBody tr').forEach(row => {
            const qty = parseFloat(row.querySelector('input[name="qty[]"]').value) || 0;
            const price = parseFloat(row.querySelector('input[name="price[]"]').value) || 0;
            const discVal = parseFloat(row.querySelector('input[name="item_discount_val[]"]').value) || 0;
            const discType = row.querySelector('select[name="item_discount_type[]"]').value;

            let rowGross = qty * price;
            let rowDisc = (discType === '%') ? (rowGross * discVal / 100) : discVal;
            let rowNet = Math.max(0.00, rowGross - rowDisc);
            
            const totalInput = row.querySelector('.line-total');
            if (totalInput) totalInput.value = rowNet.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            subTotal += rowNet;
        });

        const globalDiscVal = parseFloat(document.getElementById('globalDiscVal').value) || 0;
        const globalDiscType = document.getElementById('globalDiscType').value;
        let globalDisc = (globalDiscType === '%') ? (subTotal * globalDiscVal / 100) : globalDiscVal;
        let grandTotal = Math.max(0.00, subTotal - globalDisc);

        document.getElementById('subTotal').innerText = subTotal.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        document.getElementById('grandTotal').innerText = grandTotal.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    function calculateDueDateOffset() {
        const termSelect = document.getElementById('paymentTermSelect');
        const invoiceDateInput = document.querySelector('input[name="invoice_date"]');
        const dueDateInput = document.getElementById('dueDate');
        
        if (!termSelect || !invoiceDateInput || !dueDateInput) return;
        
        const selectedOpt = termSelect.options[termSelect.selectedIndex];
        if (!selectedOpt || selectedOpt.value === '') return;
        
        const days = parseInt(selectedOpt.getAttribute('data-days')) || 0;
        const invDate = new Date(invoiceDateInput.value);
        if (isNaN(invDate.getTime())) return;
        
        invDate.setDate(invDate.getDate() + days);
        
        const year = invDate.getFullYear();
        const month = String(invDate.getMonth() + 1).padStart(2, '0');
        const day = String(invDate.getDate()).padStart(2, '0');
        
        dueDateInput.value = `${year}-${month}-${day}`;
    }

    document.addEventListener("DOMContentLoaded", function() {
        const invoiceDateInput = document.querySelector('input[name="invoice_date"]');
        if (invoiceDateInput) {
            invoiceDateInput.addEventListener('change', calculateDueDateOffset);
        }
    });
</script>
