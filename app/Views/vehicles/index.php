<?php
?>
<style>
    .header-actions { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .btn { padding: 8px 16px; background: #0066cc; color: #fff; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; font-size: 14px; font-weight: bold; transition: 0.2s;}
    .btn:hover { background: #005bb5; }
    .btn-outline { background: transparent; border: 1px solid #0066cc; color: #0066cc; }
    .btn-outline:hover { background: rgba(0,102,204,0.05); }
    .btn-danger { background: #ffebee; color: #c62828; border: none; }
    .btn-danger:hover { background: #ffcdd2; }
    .btn-small { padding: 4px 8px; font-size: 11px; margin-right: 5px; cursor: pointer; border-radius: 4px;}
    
    .quick-links { display: flex; gap: 10px; margin-bottom: 20px; align-items: center; background: rgba(0,0,0,0.02); padding: 10px 15px; border-radius: 8px; border: 1px solid var(--mac-border); }
    .btn-quick { padding: 6px 12px; background: #fff; color: #555; border: 1px solid var(--mac-border); border-radius: 6px; font-size: 12px; font-weight: 600; text-decoration: none; transition: 0.2s; }
    .btn-quick:hover { background: rgba(0,102,204,0.05); color: #0066cc; border-color: #0066cc; }
    .btn-quick.active { background: #0066cc; color: #fff; border-color: #0066cc; }
    
    /* KPI Panel */
    .kpi-container { display: flex; gap: 15px; margin-bottom: 20px; flex-wrap: wrap; }
    .kpi-card { background: #fff; padding: 15px 20px; border-radius: 8px; border: 1px solid var(--mac-border); display: flex; flex-direction: column; width: 180px; box-shadow: 0 1px 3px rgba(0,0,0,0.03); }
    @media (prefers-color-scheme: dark) { .kpi-card { background: #1e1e2d; } }
    .kpi-card.active-border { border-left: 4px solid #2e7d32; }
    .kpi-card.maint-border { border-left: 4px solid #ef6c00; }
    .kpi-card.inact-border { border-left: 4px solid #c62828; }
    .kpi-title { font-size: 11px; color: #888; text-transform: uppercase; margin-bottom: 5px; font-weight: bold; }
    .kpi-val { font-size: 22px; font-weight: bold; color: var(--text-main); }
    
    .search-bar { width: 100%; padding: 10px 15px; border: 1px solid var(--mac-border); border-radius: 8px; font-size: 14px; margin-bottom: 15px; background: transparent; color: var(--text-main); box-sizing: border-box; }
    
    .data-table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.05);}
    @media (prefers-color-scheme: dark) { .data-table { background: #1e1e2d; } }
    .data-table th, .data-table td { padding: 12px 15px; text-align: left; border-bottom: 1px solid var(--mac-border); font-size: 13px;}
    .data-table th { background-color: rgba(0,0,0,0.02); font-weight: 600; color:#555; text-transform: uppercase; font-size: 11px; }
    
    .status-badge { padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: bold; display: inline-block; }
    .status-Active { background: #e8f5e9; color: #2e7d32; }
    .status-Maintenance { background: #fff3e0; color: #e65100; }
    .status-Inactive { background: #ffebee; color: #c62828; }
    
    .pagination { display: flex; justify-content: flex-end; gap: 5px; margin-top: 15px; }
    .page-btn { padding: 6px 12px; border: 1px solid var(--mac-border); border-radius: 4px; background: #fff; color: #333; text-decoration: none; font-size: 12px;}
    .page-btn.active { background: #0066cc; color: #fff; border-color: #0066cc; }
    
    .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 2000; align-items: center; justify-content: center; }
    .modal-content { background: var(--mac-bg); padding: 25px; border-radius: 12px; width: 450px; border: 1px solid var(--mac-border); backdrop-filter: blur(20px); }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; margin-bottom: 5px; font-size: 13px; font-weight: 600; }
    .form-control { width: 100%; padding: 8px 12px; border: 1px solid var(--mac-border); border-radius: 4px; background: transparent; color: var(--text-main); box-sizing: border-box;}
</style>

<div class="header-actions">
    <div>
        <h2 style="margin: 0 0 5px 0;">🚚 Vehicle Management System</h2>
        <p style="margin: 0; color: #666; font-size: 14px;">Manage delivery fleet, vehicles, and operational status.</p>
    </div>
    <button class="btn" onclick="openModal('add')">+ Add Vehicle</button>
</div>

<!-- Quick Navigation Links -->
<div class="quick-links">
    <span style="font-size: 11px; color: #888; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px;">Related:</span>
    <a href="<?= APP_URL ?>/user" class="btn-quick">👤 Employee Directory</a>
    <a href="<?= APP_URL ?>/RepTracking/index" class="btn-quick">📍 Route Tracking</a>
    <a href="<?= APP_URL ?>/vehicle" class="btn-quick active">🚚 Vehicle Management</a>
</div>

<!-- KPI Summary Cards -->
<div class="kpi-container">
    <div class="kpi-card active-border">
        <div class="kpi-title">Active Vehicles</div>
        <div class="kpi-val"><?= $data['active_count'] ?></div>
    </div>
    <div class="kpi-card maint-border">
        <div class="kpi-title">Under Maintenance</div>
        <div class="kpi-val"><?= $data['maintenance_count'] ?></div>
    </div>
    <div class="kpi-card inact-border">
        <div class="kpi-title">Inactive Vehicles</div>
        <div class="kpi-val"><?= $data['inactive_count'] ?></div>
    </div>
    <div class="kpi-card">
        <div class="kpi-title">Total Registered</div>
        <div class="kpi-val"><?= $data['total_vehicles'] ?></div>
    </div>
</div>

<?php if(!empty($data['error'])): ?><div style="padding: 10px; background:#ffebee; color:#c62828; border-radius:4px; margin-bottom:15px; font-weight: 500;"><?= $data['error'] ?></div><?php endif; ?>
<?php if(!empty($data['success'])): ?><div style="padding: 10px; background:#e8f5e9; color:#2e7d32; border-radius:4px; margin-bottom:15px; font-weight: 500;"><?= $data['success'] ?></div><?php endif; ?>

<input type="text" id="searchInput" class="search-bar" placeholder="🔍 Search Vehicles... (Enter number, model or type to search live)" value="<?= htmlspecialchars($data['search']) ?>">

<div id="tableContainer">
    <table class="data-table">
        <thead>
            <tr>
                <th>Vehicle Number</th>
                <th>Model / Specification</th>
                <th>Type</th>
                <th>Status</th>
                <th style="text-align: center;">Actions</th>
            </tr>
        </thead>
        <tbody id="tableBody">
            <?php if(empty($data['vehicles'])): ?>
            <tr><td colspan="5" style="text-align: center; color: #888; padding: 25px;">No vehicles found in fleet.</td></tr>
            <?php else: foreach($data['vehicles'] as $v): ?>
            <tr>
                <td><strong><?= htmlspecialchars($v->vehicle_number) ?></strong></td>
                <td><?= htmlspecialchars($v->model) ?></td>
                <td><span style="background: rgba(0,0,0,0.05); padding: 4px 10px; border-radius: 12px; font-weight: 500; font-size:11px;"><?= htmlspecialchars($v->type) ?></span></td>
                <td><span class="status-badge status-<?= $v->status ?>"><?= $v->status ?></span></td>
                <td style="text-align: center;">
                    <button class="btn btn-small btn-outline" onclick="openModal('edit', '<?= $v->id ?>', '<?= htmlspecialchars(addslashes($v->vehicle_number)) ?>', '<?= htmlspecialchars(addslashes($v->model)) ?>', '<?= htmlspecialchars(addslashes($v->type)) ?>', '<?= $v->status ?>')">Edit</button>
                    <form action="<?= APP_URL ?>/vehicle" method="POST" style="display:inline;">
                        <input type="hidden" name="action" value="delete_vehicle">
                        <input type="hidden" name="vehicle_id" value="<?= $v->id ?>">
                        <button type="submit" class="btn btn-small btn-danger" onclick="return confirm('Remove this vehicle from the fleet permanently?');">Del</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>

    <div class="pagination" id="paginationContainer">
        <?php if($data['total_pages'] > 1): ?>
            <?php for($i = 1; $i <= $data['total_pages']; $i++): ?>
                <a href="?page=<?= $i ?>&search=<?= urlencode($data['search']) ?>" class="page-btn <?= ($data['page'] == $i) ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Form -->
<div class="modal" id="vehicleModal">
    <div class="modal-content">
        <h3 id="modalTitle" style="margin-top:0;">Add Vehicle to Fleet</h3>
        <form action="<?= APP_URL ?>/vehicle?page=<?= $data['page'] ?>&search=<?= urlencode($data['search']) ?>" method="POST">
            <input type="hidden" name="action" id="formAction" value="add_vehicle">
            <input type="hidden" name="vehicle_id" id="formVehicleId" value="">
            
            <div class="form-group">
                <label>Vehicle Number *</label>
                <input type="text" name="vehicle_number" id="f_number" class="form-control" placeholder="e.g. WP NB-4589" required>
            </div>
            
            <div class="form-group">
                <label>Model / Specification *</label>
                <input type="text" name="model" id="f_model" class="form-control" placeholder="e.g. Tata LPT 709" required>
            </div>

            <div class="form-group">
                <label>Type *</label>
                <select name="type" id="f_type" class="form-control" required>
                    <option value="Lorry">Lorry</option>
                    <option value="Van">Van</option>
                    <option value="Three-Wheel">Three-Wheel</option>
                    <option value="Car">Car</option>
                    <option value="Motorcycle">Motorcycle</option>
                </select>
            </div>

            <div class="form-group">
                <label>Status</label>
                <select name="status" id="f_status" class="form-control">
                    <option value="Active">Active (Ready for delivery)</option>
                    <option value="Maintenance">Maintenance</option>
                    <option value="Inactive">Inactive</option>
                </select>
            </div>
            
            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('vehicleModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn" id="modalSubmitBtn">Save Vehicle</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Live Search
    let searchTimeout = null;
    document.getElementById('searchInput').addEventListener('input', function(e) {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            const query = encodeURIComponent(e.target.value);
            const url = `?search=${query}&page=1`;
            fetch(url)
                .then(response => response.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    document.getElementById('tableBody').innerHTML = doc.getElementById('tableBody').innerHTML;
                    document.getElementById('paginationContainer').innerHTML = doc.getElementById('paginationContainer').innerHTML;
                    
                    // Update KPI cards in the header dynamically as well!
                    document.querySelector('.kpi-container').innerHTML = doc.querySelector('.kpi-container').innerHTML;
                    
                    window.history.pushState({}, '', url);
                });
        }, 300);
    });

    function openModal(mode, id = '', number = '', model = '', type = 'Lorry', status = 'Active') {
        document.getElementById('vehicleModal').style.display = 'flex';
        if (mode === 'add') {
            document.getElementById('modalTitle').innerText = 'Add Vehicle to Fleet';
            document.getElementById('formAction').value = 'add_vehicle';
            document.getElementById('modalSubmitBtn').innerText = 'Save Vehicle';
            document.getElementById('f_number').value = '';
            document.getElementById('f_model').value = '';
            document.getElementById('f_type').value = 'Lorry';
            document.getElementById('f_status').value = 'Active';
        } else {
            document.getElementById('modalTitle').innerText = 'Edit Vehicle Specification';
            document.getElementById('formAction').value = 'edit_vehicle';
            document.getElementById('formVehicleId').value = id;
            document.getElementById('modalSubmitBtn').innerText = 'Update Changes';
            document.getElementById('f_number').value = number;
            document.getElementById('f_model').value = model;
            document.getElementById('f_type').value = type;
            document.getElementById('f_status').value = status;
        }
    }
</script>
