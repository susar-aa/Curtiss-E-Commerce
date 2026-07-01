<style>
    .mac-container { max-width: 1200px; margin: 0 auto; padding: 20px; }
    .search-card { background: #ffffff; border: 1px solid var(--mac-border); border-radius: 12px; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); margin-bottom: 25px; }
    .search-title { font-size: 18px; font-weight: 600; color: #1d1d1f; margin: 0 0 8px 0; display: flex; align-items: center; gap: 8px; }
    .search-subtitle { font-size: 13px; color: #86868b; margin: 0 0 20px 0; }
    
    /* Autocomplete Styles */
    .search-wrapper { position: relative; width: 100%; }
    .search-input { width: 100%; padding: 12px 16px; font-size: 14px; border: 1.5px solid #d2d2d7; border-radius: 8px; background: #f5f5f7; color: #1d1d1f; outline: none; transition: all 0.2s ease-in-out; box-sizing: border-box; }
    .search-input:focus { background: #ffffff; border-color: #0066cc; box-shadow: 0 0 0 4px rgba(0,102,204,0.15); }
    .autocomplete-list { position: absolute; top: calc(100% + 5px); left: 0; width: 100%; background: #ffffff; border: 1px solid #d2d2d7; border-radius: 8px; max-height: 250px; overflow-y: auto; box-shadow: 0 10px 25px rgba(0,0,0,0.1); z-index: 999; display: none; }
    .autocomplete-item { padding: 12px 16px; font-size: 13px; color: #1d1d1f; cursor: pointer; border-bottom: 1px solid #f5f5f7; display: flex; justify-content: space-between; align-items: center; }
    .autocomplete-item:hover { background: #f5f5f7; color: #0066cc; }
    .autocomplete-item .item-sku { font-size: 11px; color: #86868b; font-family: monospace; }

    /* Current Price Info Card */
    .info-card { display: none; background: #ffffff; border: 1px solid var(--mac-border); border-radius: 12px; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); margin-bottom: 25px; }
    .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 15px; }
    .info-stat { background: #f5f5f7; padding: 15px; border-radius: 8px; border: 1px solid rgba(0,0,0,0.02); }
    .stat-label { font-size: 11px; text-transform: uppercase; color: #86868b; font-weight: 600; margin-bottom: 4px; }
    .stat-val { font-size: 18px; font-weight: bold; color: #1d1d1f; }

    /* Timeline Table Styles */
    .history-card { display: none; background: #ffffff; border: 1px solid var(--mac-border); border-radius: 12px; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); }
    .history-table { width: 100%; border-collapse: collapse; margin-top: 15px; text-align: left; }
    .history-table th, .history-table td { padding: 12px 16px; border-bottom: 1px solid #f5f5f7; font-size: 13px; }
    .history-table th { background: #f5f5f7; font-weight: 600; color: #515154; }
    .history-table tr:hover { background: rgba(0,0,0,0.01); }

    /* Event Badge Styles */
    .badge { padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: 600; display: inline-block; }
    .badge-grn { background: #e3f2fd; color: #0d47a1; }
    .badge-invoice { background: #e8f5e9; color: #1b5e20; }
    .badge-return { background: #fff3e0; color: #e65100; }
</style>

<div class="mac-container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h1 style="margin: 0; font-size: 24px; font-weight: 600; color: #1d1d1f;">📊 Product Price & Cost History Audit</h1>
        <a href="<?= APP_URL ?>/inventory" class="btn btn-secondary" style="font-size:12px; background:#666; color:#fff; border-radius:4px; padding:8px 16px; text-decoration:none;">&larr; Inventory Center</a>
    </div>

    <!-- Product Autocomplete Search Bar -->
    <div class="search-card">
        <div class="search-title">🔍 Select Product to Audit</div>
        <div class="search-subtitle">Search by product name, SKU, variation, or barcode, select from autocomplete and click "Load History".</div>
        <div style="display: flex; gap: 12px; align-items: center; max-width: 750px;">
            <div class="search-wrapper" style="flex: 1;">
                <input type="text" id="productSearchInput" class="search-input" placeholder="Type name or SKU here..." autocomplete="off">
                <div id="autocompleteList" class="autocomplete-list"></div>
            </div>
            <button type="button" id="loadHistoryBtn" class="btn" style="background: #0066cc; color: #fff; padding: 12px 24px; font-size: 14px; font-weight: 600; border: none; border-radius: 8px; cursor: pointer; white-space: nowrap; transition: background 0.2s;">⚡ Load History</button>
        </div>
    </div>

    <!-- Current Pricing Information Stat Card -->
    <div id="currentInfoCard" class="info-card">
        <h3 style="margin: 0; font-size: 16px; font-weight: 600; color: #1d1d1f;" id="metaProdName">Product Name</h3>
        <div style="font-size: 12px; color: #86868b; margin-top: 4px;" id="metaProdSku">SKU: N/A</div>
        <div class="info-grid">
            <div class="info-stat">
                <div class="stat-label">Current Cost Price</div>
                <div class="stat-val" style="color: #c62828;" id="metaCost">Rs: 0.00</div>
            </div>
            <div class="info-stat">
                <div class="stat-label">Current Retail B2C Price</div>
                <div class="stat-val" style="color: #0066cc;" id="metaRetail">Rs: 0.00</div>
            </div>
            <div class="info-stat">
                <div class="stat-label">Current Wholesale B2B Price</div>
                <div class="stat-val" style="color: #2e7d32;" id="metaWholesale">Rs: 0.00</div>
            </div>
            <div class="info-stat">
                <div class="stat-label">Total Stock On Hand</div>
                <div class="stat-val" id="metaStock">0 Pcs</div>
            </div>
        </div>
    </div>

    <!-- History Audit Timeline Card -->
    <div id="historyTimelineCard" class="history-card">
        <div class="search-title" style="margin-bottom: 15px;">📜 Complete Pricing & Inventory History Trail</div>
        <table class="history-table">
            <thead>
                <tr>
                    <th style="width: 16%;">Date & Time</th>
                    <th style="width: 14%;">Event Type</th>
                    <th style="width: 10%; text-align: right;">Qty Changed</th>
                    <th style="width: 13%; text-align: right;">Cost Price (Rs:)</th>
                    <th style="width: 15%; text-align: right;">Retail Price (Rs:)</th>
                    <th style="width: 15%; text-align: right;">Wholesale Price (Rs:)</th>
                    <th style="width: 17%;">Reference / Details</th>
                </tr>
            </thead>
            <tbody id="timelineBody">
                <!-- Timeline items load here dynamically via Javascript -->
            </tbody>
        </table>
    </div>
</div>

<script>
    // Injected catalog items with preloaded variations
    <?php
        error_reporting(0);
        ini_set('display_errors', 0);
    ?>
    var catalogItems = <?php
        try {
            if (isset($data['catalog_items'])) {
                $json = json_encode($data['catalog_items']);
                if ($json === false) {
                    echo '[]';
                } else {
                    echo $json;
                }
            } else {
                echo '[]';
            }
        } catch (Exception $e) {
            echo '[]';
        }
    ?>;
    const searchInput = document.getElementById('productSearchInput');
    const autocompleteList = document.getElementById('autocompleteList');

    // Selection tracking states
    let selectedItemId = null;
    let selectedVarOptId = null;

    // Generate flattened list of searchable elements (main items + variation options)
    var searchableItems = [];
    catalogItems.forEach(item => {
        if (item.variations && item.variations.length > 0) {
            item.variations.forEach(v => {
                searchableItems.push({
                    item_id: item.id,
                    var_opt_id: v.id,
                    display_name: `${item.name} - ${v.variation_name}: ${v.value_name}`,
                    sku: v.sku || item.item_code || ''
                });
            });
        } else {
            searchableItems.push({
                item_id: item.id,
                var_opt_id: 0,
                display_name: item.name,
                sku: item.item_code || ''
            });
        }
    });

    // Handle real-time Autocomplete input typing
    searchInput.addEventListener('input', function() {
        const query = this.value.toLowerCase().trim();
        autocompleteList.innerHTML = '';
        if (query.length < 1) {
            autocompleteList.style.display = 'none';
            selectedItemId = null;
            selectedVarOptId = null;
            return;
        }

        const matches = searchableItems.filter(item => 
            item.display_name.toLowerCase().includes(query) || 
            item.sku.toLowerCase().includes(query)
        ).slice(0, 15);

        if (matches.length === 0) {
            autocompleteList.innerHTML = '<div style="padding: 12px; font-size:12px; color:#86868b; text-align:center;">No matching products found.</div>';
            autocompleteList.style.display = 'block';
            return;
        }

        matches.forEach(m => {
            const div = document.createElement('div');
            div.className = 'autocomplete-item';
            div.innerHTML = `
                <span>${escapeHtml(m.display_name)}</span>
                <span class="item-sku">${escapeHtml(m.sku)}</span>
            `;
            div.addEventListener('click', function() {
                searchInput.value = m.display_name;
                selectedItemId = m.item_id;
                selectedVarOptId = m.var_opt_id;
                autocompleteList.style.display = 'none';
            });
            autocompleteList.appendChild(div);
        });

        autocompleteList.style.display = 'block';
    });

    // Handle the button click explicitly
    document.getElementById('loadHistoryBtn').addEventListener('click', function() {
        if (!selectedItemId) {
            // Fallback: If user typed perfectly but didn't click, try to find an exact display_name match
            const exactQuery = searchInput.value.trim().toLowerCase();
            const matched = searchableItems.find(item => item.display_name.toLowerCase() === exactQuery);
            if (matched) {
                selectedItemId = matched.item_id;
                selectedVarOptId = matched.var_opt_id;
            } else {
                alert('Please select a product from the suggestion list first.');
                return;
            }
        }
        loadProductHistory(selectedItemId, selectedVarOptId);
    });

    // Close autocomplete lists when clicking outside the input container
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !autocompleteList.contains(e.target)) {
            autocompleteList.style.display = 'none';
        }
    });

    // Fetch and render the entire history trail
    function loadProductHistory(itemId, varOptId) {
        fetch(`<?= APP_URL ?>/inventory/get_price_history?item_id=${itemId}&var_opt_id=${varOptId}`)
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    alert(data.error);
                    return;
                }

                // Render current info card
                document.getElementById('metaProdName').textContent = data.current.product_name;
                document.getElementById('metaProdSku').textContent = `SKU: ${data.current.item_code || 'N/A'}`;
                document.getElementById('metaCost').textContent = 'Rs: ' + parseFloat(data.current.current_cost || 0).toFixed(2);
                document.getElementById('metaRetail').textContent = 'Rs: ' + parseFloat(data.current.current_retail || 0).toFixed(2);
                document.getElementById('metaWholesale').textContent = 'Rs: ' + parseFloat(data.current.current_wholesale || 0).toFixed(2);
                document.getElementById('metaStock').textContent = parseFloat(data.current.current_stock || 0).toFixed(0) + ' Pcs';

                document.getElementById('currentInfoCard').style.display = 'block';

                // Render timeline
                const tbody = document.getElementById('timelineBody');
                tbody.innerHTML = '';

                if (data.timeline.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; color:#86868b; padding:30px;">No historical purchase or sales transaction logs found for this product.</td></tr>';
                } else {
                    data.timeline.forEach(t => {
                        let badgeClass = 'badge-grn';
                        if (t.event_type.includes('Invoice')) badgeClass = 'badge-invoice';
                        if (t.event_type.includes('Return')) badgeClass = 'badge-return';

                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td style="color:#515154;">${t.date_occurred}</td>
                            <td><span class="badge ${badgeClass}">${t.event_type}</span></td>
                            <td style="text-align:right; font-weight:600;">${parseFloat(t.quantity_changed).toFixed(0)} Pcs</td>
                            <td style="text-align:right; color:#c62828; font-weight:500;">${t.cost_price && parseFloat(t.cost_price) > 0 ? 'Rs: ' + parseFloat(t.cost_price).toFixed(2) : '-'}</td>
                            <td style="text-align:right; color:#0066cc; font-weight:500;">${t.selling_price && parseFloat(t.selling_price) > 0 ? 'Rs: ' + parseFloat(t.selling_price).toFixed(2) : '-'}</td>
                            <td style="text-align:right; color:#2e7d32; font-weight:500;">${t.wholesale_price && parseFloat(t.wholesale_price) > 0 ? 'Rs: ' + parseFloat(t.wholesale_price).toFixed(2) : '-'}</td>
                            <td style="font-weight:600; color:#1d1d1f;">${escapeHtml(t.reference)}</td>
                        `;
                        tbody.appendChild(tr);
                    });
                }

                document.getElementById('historyTimelineCard').style.display = 'block';
            });
    }

    // Escape helper
    function escapeHtml(str) {
        if (!str) return '';
        return str
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
</script>
