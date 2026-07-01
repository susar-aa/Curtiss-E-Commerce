<?php
// Smart Failsafe Data Loader
$db = new Database();

// Load catalog items and warehouses dynamically
$db->query("SELECT *, price AS selling_price, qty AS quantity_on_hand FROM items ORDER BY name ASC");
$items = $db->resultSet() ?: [];

$db->query("SELECT * FROM warehouses ORDER BY is_default DESC, name ASC");
$warehouses = $db->resultSet() ?: [];
?>
<style>
    .transfer-grid {
        display: grid;
        grid-template-columns: 1fr 2fr;
        gap: 25px;
        margin-top: 15px;
    }
    
    .card-title {
        font-size: 18px;
        font-weight: 700;
        margin-top: 0;
        margin-bottom: 20px;
        color: var(--text-main);
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .form-group {
        margin-bottom: 18px;
        position: relative;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 6px;
        font-size: 13px;
        font-weight: 600;
        color: var(--text-main);
    }
    
    .form-control {
        width: 100%;
        padding: 10px 14px;
        border: 1px solid var(--mac-border);
        border-radius: 8px;
        background: transparent;
        color: var(--text-main);
        box-sizing: border-box;
        font-size: 13px;
        transition: all 0.2s;
    }
    .form-control:focus {
        border-color: #0066cc;
        outline: none;
        background: rgba(0, 102, 204, 0.02);
    }
    .form-control[readonly] {
        background: rgba(0,0,0,0.03);
        cursor: not-allowed;
    }
    @media (prefers-color-scheme: dark) {
        .form-control[readonly] {
            background: rgba(255,255,255,0.03);
        }
    }
    
    .btn {
        padding: 10px 20px;
        background: #0066cc;
        color: #fff;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-weight: bold;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 13px;
        transition: background 0.2s;
    }
    .btn:hover {
        background: #0052a3;
    }
    .btn-outline {
        background: transparent;
        border: 1px solid #0066cc;
        color: #0066cc;
    }
    .btn-outline:hover {
        background: rgba(0, 102, 204, 0.05);
    }
    
    .data-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
    }
    .data-table th, .data-table td {
        padding: 12px 16px;
        text-align: left;
        border-bottom: 1px solid var(--mac-border);
    }
    .data-table th {
        background: rgba(0,0,0,0.02);
        font-weight: 600;
        color: var(--text-muted);
    }
    @media (prefers-color-scheme: dark) {
        .data-table th {
            background: rgba(255,255,255,0.02);
        }
    }
    .data-table tr:hover {
        background: rgba(0,0,0,0.01);
    }
    @media (prefers-color-scheme: dark) {
        .data-table tr:hover {
            background: rgba(255,255,255,0.01);
        }
    }
    
    .search-results {
        position: absolute;
        top: 100%;
        left: 0;
        width: 100%;
        background: var(--mega-bg);
        border: 1px solid var(--mac-border);
        border-radius: 8px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        z-index: 1000;
        display: none;
        max-height: 200px;
        overflow-y: auto;
        padding: 0;
        margin: 5px 0 0 0;
        list-style: none;
    }
    .search-results li {
        padding: 10px 14px;
        cursor: pointer;
        border-bottom: 1px solid var(--mega-divider);
        display: flex;
        justify-content: space-between;
        font-size: 13px;
    }
    .search-results li:hover {
        background: #0066cc;
        color: #fff;
    }
    .search-results li:hover span {
        color: #fff !important;
    }
    
    .badge {
        padding: 4px 8px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: bold;
    }
    .badge-primary {
        background: rgba(0, 102, 204, 0.1);
        color: #0066cc;
    }
    
    .filter-bar {
        display: flex;
        gap: 10px;
        margin-bottom: 15px;
        align-items: center;
        justify-content: space-between;
    }
</style>

<div class="header-actions" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <div>
        <h2 style="margin: 0 0 5px 0; font-size: 24px;">Warehouse Stock Transfer</h2>
        <p style="margin: 0; color: var(--text-muted); font-size: 14px;">Move product inventories between different warehouse depots transactionally.</p>
    </div>
</div>

<?php if(!empty($data['error'])): ?>
    <div style="padding: 12px 16px; background:#ffebee; color:#c62828; border-radius:8px; margin-bottom:15px; font-weight:bold;">
        <i class="ph ph-warning-circle" style="vertical-align: middle; font-size:16px; margin-right:5px;"></i> <?= $data['error'] ?>
    </div>
<?php endif; ?>
<?php if(!empty($data['success'])): ?>
    <div style="padding: 12px 16px; background:#e8f5e9; color:#2e7d32; border-radius:8px; margin-bottom:15px; font-weight:bold;">
        <i class="ph ph-check-circle" style="vertical-align: middle; font-size:16px; margin-right:5px;"></i> <?= $data['success'] ?>
    </div>
<?php endif; ?>

<div class="transfer-grid">
    <!-- Left Column: Transfer Form -->
    <div class="card">
        <h3 class="card-title"><i class="ph-bold ph-arrows-left-right"></i> New Stock Transfer</h3>
        <form action="<?= APP_URL ?>/warehouse/storeTransfer" method="POST" id="transferForm">
            
            <div class="form-group">
                <label>Select Item to Move *</label>
                <input type="hidden" name="item_id" id="itemIdInput" required>
                <input type="text" id="itemSearch" class="form-control" placeholder="🔍 Search item name or SKU..." autocomplete="off" required>
                <ul id="itemSearchResults" class="search-results"></ul>
            </div>
            
            <div class="form-group">
                <label>Current Warehouse</label>
                <input type="hidden" name="from_warehouse_id" id="fromWhId">
                <input type="text" id="fromWhName" class="form-control" readonly placeholder="Will automatically resolve...">
            </div>
            
            <div class="form-group">
                <label>Current Stock Quantity</label>
                <input type="text" id="currentStock" class="form-control" readonly placeholder="0">
            </div>

            <div class="form-group">
                <label>Quantity to Transfer *</label>
                <input type="number" name="qty" id="qtyInput" class="form-control" min="1" placeholder="Enter transfer quantity..." required>
            </div>

            <div class="form-group">
                <label>Destination Warehouse *</label>
                <select name="to_warehouse_id" id="toWhSelect" class="form-control" required>
                    <option value="">Select Destination Warehouse...</option>
                    <?php foreach($warehouses as $wh): ?>
                        <option value="<?= $wh->id ?>">
                            <?= htmlspecialchars($wh->name) ?> (<?= htmlspecialchars($wh->location) ?: 'No Location' ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Transfer Date *</label>
                <input type="date" name="transfer_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>

            <div class="form-group">
                <label>Memo / Notes</label>
                <textarea name="notes" class="form-control" rows="3" placeholder="Optional internal logs..."></textarea>
            </div>

            <button type="submit" class="btn" style="width: 100%; justify-content: center;">
                <i class="ph-bold ph-check"></i> Post Transfer Record
            </button>
        </form>
    </div>

    <!-- Right Column: Past Transfers Log -->
    <div class="card" style="display: flex; flex-direction: column;">
        <h3 class="card-title"><i class="ph-bold ph-clock-counter-clockwise"></i> Transfer Audit Trail</h3>
        
        <div class="filter-bar">
            <div style="flex: 1; max-width: 300px;">
                <input type="text" id="transferSearch" class="form-control" placeholder="🔍 Search transfers..." onkeyup="filterTransfers()">
            </div>
            <div>
                <button type="button" class="btn btn-outline" onclick="window.print()"><i class="ph ph-printer"></i> Print</button>
            </div>
        </div>

        <div style="flex: 1; overflow-x: auto; border: 1px solid var(--mac-border); border-radius: 8px;">
            <table class="data-table" id="transfersTable">
                <thead>
                    <tr>
                        <th>Transfer #</th>
                        <th>Transfer Date</th>
                        <th>Product Item</th>
                        <th>From Warehouse</th>
                        <th>To Warehouse</th>
                        <th>Posted By</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($data['transfers'])): ?>
                        <tr><td colspan="7" style="text-align:center; color:#888; padding:30px;">No transfer logs found.</td></tr>
                    <?php else: foreach($data['transfers'] as $t): ?>
                        <tr>
                            <td><span class="badge badge-primary"><?= htmlspecialchars($t->transfer_number) ?></span></td>
                            <td><strong><?= htmlspecialchars($t->transfer_date) ?></strong></td>
                            <td>
                                <strong><?= htmlspecialchars($t->item_name) ?></strong><br>
                                <span style="font-size:11px; color:var(--text-muted);"><?= htmlspecialchars($t->item_code) ?></span>
                            </td>
                            <td><span style="color:#d32f2f; font-weight:bold;"><i class="ph ph-minus"></i> <?= htmlspecialchars($t->from_warehouse_name) ?></span></td>
                            <td><span style="color:#388e3c; font-weight:bold;"><i class="ph ph-plus"></i> <?= htmlspecialchars($t->to_warehouse_name) ?></span></td>
                            <td><?= htmlspecialchars($t->creator_name) ?></td>
                            <td style="max-width:180px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="<?= htmlspecialchars($t->notes) ?>">
                                <?= htmlspecialchars($t->notes) ?: '<em style="color:#aaa;">No memo</em>' ?>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    // Live Items Search Autocomplete Database
    const catalog = [
        <?php foreach($items as $i): ?>
        {
            id: "<?= $i->id ?>",
            name: <?= json_encode($i->name) ?>,
            sku: <?= json_encode($i->item_code) ?>,
            qty: <?= floatval($i->quantity_on_hand) ?>,
            wh_id: "<?= $i->warehouse_id ?>",
            wh_name: <?= json_encode(array_values(array_filter($warehouses, function($w) use ($i) { return $w->id == $i->warehouse_id; }))[0]->name ?? 'Default Warehouse') ?>
        },
        <?php endforeach; ?>
    ];

    const input = document.getElementById('itemSearch');
    const resList = document.getElementById('itemSearchResults');

    input.addEventListener('keyup', function(e) {
        const val = e.target.value.toLowerCase().trim();
        resList.innerHTML = '';
        if(!val) { resList.style.display = 'none'; return; }

        const filtered = catalog.filter(i => i.name.toLowerCase().includes(val) || i.sku.toLowerCase().includes(val)).slice(0, 10);
        if(filtered.length === 0) { resList.style.display = 'none'; return; }

        filtered.forEach(item => {
            const li = document.createElement('li');
            li.innerHTML = `
                <div><strong>${item.name}</strong><br><span style="font-size:11px; color:#888;">SKU: ${item.sku} | Wh: ${item.wh_name}</span></div>
                <div style="font-weight:bold; font-size:12px; color:#0066cc;">Qty: ${item.qty}</div>
            `;
            li.onclick = () => {
                document.getElementById('itemIdInput').value = item.id;
                input.value = item.name;
                document.getElementById('fromWhId').value = item.wh_id;
                document.getElementById('fromWhName').value = item.wh_name;
                document.getElementById('currentStock').value = item.qty;
                resList.style.display = 'none';
                
                // Exclude current warehouse from destination options
                const select = document.getElementById('toWhSelect');
                for (let opt of select.options) {
                    if (opt.value === item.wh_id) {
                        opt.disabled = true;
                    } else {
                        opt.disabled = false;
                    }
                }
            };
            resList.appendChild(li);
        });
        resList.style.display = 'block';
    });

    document.addEventListener('click', function(e) {
        if(e.target.id !== 'itemSearch') { resList.style.display = 'none'; }
    });

    // Real-Time Transfers Filtering
    function filterTransfers() {
        const query = document.getElementById('transferSearch').value.toLowerCase().trim();
        const trs = document.querySelectorAll('#transfersTable tbody tr');
        
        trs.forEach(tr => {
            if(tr.cells.length < 2) return;
            const text = tr.innerText.toLowerCase();
            if(text.includes(query)) {
                tr.style.display = '';
            } else {
                tr.style.display = 'none';
            }
        });
    }

    // Front-End Form Safety Checks
    document.getElementById('transferForm').addEventListener('submit', function(e) {
        const fromWh = document.getElementById('fromWhId').value;
        const toWh = document.getElementById('toWhSelect').value;
        const currentStock = parseFloat(document.getElementById('currentStock').value || 0);
        const qtyToTransfer = parseFloat(document.getElementById('qtyInput').value || 0);
        
        if (fromWh === toWh) {
            e.preventDefault();
            alert('Error: Source and Destination warehouses must be different!');
            return;
        }

        if (qtyToTransfer <= 0) {
            e.preventDefault();
            alert('Error: Transfer quantity must be greater than 0!');
            return;
        }

        if (qtyToTransfer > currentStock) {
            e.preventDefault();
            alert('Error: Cannot transfer more than the available stock quantity (' + currentStock + ')!');
            return;
        }
    });
</script>
