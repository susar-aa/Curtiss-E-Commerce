<style>
    .header-actions { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .btn { padding: 8px 16px; background: #0066cc; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-size: 14px;}
    .btn-outline { background: transparent; border: 1px solid #0066cc; color: #0066cc; }
    .btn-danger { background: #ffebee; color: #c62828; border: none; }
    .data-table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.05);}
    .data-table th, .data-table td { padding: 12px 15px; text-align: left; border-bottom: 1px solid var(--mac-border); font-size: 14px;}
    .data-table th { background-color: rgba(0,0,0,0.02); font-weight: 600; color:#555;}
    
    /* QuickBooks-Style Modal Styling */
    .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); z-index: 2000; align-items: center; justify-content: center; }
    .modal-content { background: var(--mac-bg); backdrop-filter: blur(20px); border: 1px solid var(--mac-border); padding: 25px; border-radius: 12px; width: 550px; box-shadow: 0 10px 30px rgba(0,0,0,0.15); }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; margin-bottom: 5px; font-size: 13px; font-weight: 600; }
    .form-control { width: 100%; padding: 8px 12px; border: 1px solid var(--mac-border); border-radius: 4px; background: transparent; color: var(--text-main); box-sizing: border-box; font-size: 13px;}
    .input-inline { display: inline-block; width: 80px; text-align: center; margin: 0 5px; }
    
    .qb-section {
        border: 1px solid var(--mac-border);
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
        background: rgba(0,0,0,0.01);
        transition: 0.3s;
    }
    .qb-section.disabled {
        opacity: 0.5;
        pointer-events: none;
    }
    
    .qb-section-header {
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: bold;
        margin-bottom: 12px;
        font-size: 14px;
        color: var(--text-main);
    }
    .status-badge { padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: bold; }
    .status-active { background: #e8f5e9; color: #2e7d32; }
    .status-inactive { background: #ffebee; color: #c62828; }
</style>

<div class="card">
    <div class="header-actions">
        <div>
            <h2 style="margin: 0 0 5px 0;">Payment Terms</h2>
            <p style="margin: 0; color: #666; font-size: 14px;">Configure standard Net intervals and date-driven billing rules.</p>
        </div>
        <button class="btn" onclick="openModal('add')">+ Add Payment Term</button>
    </div>

    <?php if(!empty($data['error'])): ?><div style="padding: 10px; background:#ffebee; color:#c62828; border-radius:4px; margin-bottom:15px;"><?= $data['error'] ?></div><?php endif; ?>
    <?php if(!empty($data['success'])): ?><div style="padding: 10px; background:#e8f5e9; color:#2e7d32; border-radius:4px; margin-bottom:15px;"><?= $data['success'] ?></div><?php endif; ?>

    <table class="data-table">
        <thead>
            <tr>
                <th>Term Name</th>
                <th>Type</th>
                <th>Payment Rules / Description</th>
                <th style="text-align: center;">Status</th>
                <th style="text-align: center; width: 150px;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($data['terms'] as $term): ?>
            <tr style="<?= $term->is_inactive ? 'opacity:0.6;' : '' ?>">
                <td><strong><?= htmlspecialchars($term->name) ?></strong></td>
                <td>
                    <span style="text-transform: capitalize; font-weight:600; font-size:12px; color:#0066cc;">
                        <?= str_replace('_', ' ', $term->term_type) ?>
                    </span>
                </td>
                <td style="font-size: 13px; color:#555;">
                    <?php if ($term->term_type === 'standard'): ?>
                        Net due in <strong><?= $term->net_due_days ?></strong> days. 
                        <?php if ($term->discount_percent > 0): ?>
                            Discount of <strong><?= $term->discount_percent ?>%</strong> if paid within <strong><?= $term->discount_days ?></strong> days.
                        <?php endif; ?>
                    <?php else: ?>
                        Net due before the <strong><?= $term->net_due_day_of_month ?>th</strong> day of the month. 
                        Due next month if issued within <strong><?= $term->due_next_month_within_days ?></strong> days of due date. 
                        <?php if ($term->discount_percent > 0): ?>
                            Discount of <strong><?= $term->discount_percent ?>%</strong> if paid before the <strong><?= $term->discount_day_of_month ?>th</strong> day of the month.
                        <?php endif; ?>
                    <?php endif; ?>
                </td>
                <td style="text-align: center;">
                    <?php if (!$term->is_inactive): ?>
                        <span class="status-badge status-active">Active</span>
                    <?php else: ?>
                        <span class="status-badge status-inactive">Inactive</span>
                    <?php endif; ?>
                </td>
                <td style="text-align: center;">
                    <button class="btn btn-outline" style="padding: 4px 8px; font-size: 11px;" 
                            onclick="openModal('edit', '<?= $term->id ?>', '<?= htmlspecialchars(addslashes($term->name)) ?>', '<?= $term->term_type ?>', <?= $term->net_due_days ?>, <?= $term->discount_percent ?>, <?= $term->discount_days ?>, <?= $term->net_due_day_of_month ?>, <?= $term->due_next_month_within_days ?>, <?= $term->discount_day_of_month ?>, <?= $term->is_inactive ?>)">Edit</button>
                    <form action="<?= APP_URL ?>/paymentterm" method="POST" style="display:inline;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="term_id" value="<?= $term->id ?>">
                        <button type="submit" class="btn btn-danger" style="padding: 4px 8px; font-size: 11px;" onclick="return confirm('Delete this term?');">Del</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- QuickBooks New Terms Dialog Modal -->
<div class="modal" id="termModal">
    <div class="modal-content">
        <h3 id="modalTitle" style="margin-top:0; border-bottom:1px solid var(--mac-border); padding-bottom:10px;">New Terms</h3>
        <form action="<?= APP_URL ?>/paymentterm" method="POST">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="term_id" id="formTermId" value="">
            
            <div class="form-group" style="display: flex; gap: 15px; align-items: center; margin-bottom: 20px;">
                <label style="margin: 0; min-width: 80px;">Terms:</label>
                <input type="text" name="name" id="f_name" class="form-control" placeholder="e.g. Net 30" required style="width: 250px;">
                
                <label style="margin:0; display:flex; align-items:center; gap:5px; margin-left:auto; font-weight:normal; cursor:pointer;">
                    <input type="checkbox" name="is_inactive" id="f_inactive" value="1"> Term is inactive
                </label>
            </div>
            
            <!-- Standard Radio Segment -->
            <div class="qb-section" id="section_standard">
                <div class="qb-section-header">
                    <input type="radio" name="term_type" id="type_standard" value="standard" checked onclick="toggleQBSections('standard')">
                    <label for="type_standard" style="cursor:pointer; margin:0;">Standard</label>
                </div>
                <div style="font-size: 13px; line-height: 2.2;">
                    Net due in <input type="number" name="net_due_days" id="f_net_due_days" class="form-control input-inline" value="0" min="0"> days. <br>
                    Discount percentage is <input type="number" step="0.1" name="discount_percent_std" id="f_discount_percent_std" class="form-control input-inline" value="0.0" min="0" max="100">% . <br>
                    Discount if paid within <input type="number" name="discount_days" id="f_discount_days" class="form-control input-inline" value="0" min="0"> days.
                </div>
            </div>

            <!-- Date Driven Radio Segment -->
            <div class="qb-section" id="section_date_driven">
                <div class="qb-section-header">
                    <input type="radio" name="term_type" id="type_date_driven" value="date_driven" onclick="toggleQBSections('date_driven')">
                    <label for="type_date_driven" style="cursor:pointer; margin:0;">Date Driven</label>
                </div>
                <div style="font-size: 13px; line-height: 2.2;">
                    Net due before the <input type="number" name="net_due_day_of_month" id="f_net_due_day_of_month" class="form-control input-inline" value="31" min="1" max="31"> th day of the month. <br>
                    Due the next month if issued within <input type="number" name="due_next_month_within_days" id="f_due_next_month_within_days" class="form-control input-inline" value="5" min="0"> days of due date. <br>
                    Discount percentage is <input type="number" step="0.1" name="discount_percent_dd" id="f_discount_percent_dd" class="form-control input-inline" value="0.0" min="0" max="100">% . <br>
                    Discount if paid before the <input type="number" name="discount_day_of_month" id="f_discount_day_of_month" class="form-control input-inline" value="10" min="1" max="31"> th day of the month.
                </div>
            </div>
            
            <!-- Hidden input to transport calculated active discount value -->
            <input type="hidden" name="discount_percent" id="f_discount_percent" value="0.00">
            
            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; border-top:1px solid var(--mac-border); padding-top:15px;">
                <button type="button" class="btn btn-outline" style="min-width: 80px;" onclick="document.getElementById('termModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn" style="min-width: 80px;" onclick="setDiscountCarrier()">OK</button>
            </div>
        </form>
    </div>
</div>

<script>
    function toggleQBSections(activeType) {
        const secStd = document.getElementById('section_standard');
        const secDD = document.getElementById('section_date_driven');
        const stdRadio = document.getElementById('type_standard');
        const ddRadio = document.getElementById('type_date_driven');

        if (activeType === 'standard') {
            stdRadio.checked = true;
            secStd.className = 'qb-section';
            secDD.className = 'qb-section disabled';
        } else {
            ddRadio.checked = true;
            secStd.className = 'qb-section disabled';
            secDD.className = 'qb-section';
        }
    }

    function setDiscountCarrier() {
        const stdRadio = document.getElementById('type_standard');
        const stdPercent = parseFloat(document.getElementById('f_discount_percent_std').value) || 0;
        const ddPercent = parseFloat(document.getElementById('f_discount_percent_dd').value) || 0;
        
        document.getElementById('f_discount_percent').value = stdRadio.checked ? stdPercent : ddPercent;
    }

    function openModal(mode, id = '', name = '', type = 'standard', net_days = 0, discount_pct = 0.0, discount_days = 0, net_day_month = 31, next_month_days = 5, discount_day_month = 10, is_inactive = 0) {
        document.getElementById('termModal').style.display = 'flex';
        
        if (mode === 'add') {
            document.getElementById('modalTitle').innerText = 'New Terms';
            document.getElementById('formAction').value = 'add';
            document.getElementById('formTermId').value = '';
            document.getElementById('f_name').value = '';
            document.getElementById('f_inactive').checked = false;
            
            document.getElementById('f_net_due_days').value = 0;
            document.getElementById('f_discount_percent_std').value = 0.0;
            document.getElementById('f_discount_days').value = 0;
            
            document.getElementById('f_net_due_day_of_month').value = 31;
            document.getElementById('f_due_next_month_within_days').value = 5;
            document.getElementById('f_discount_percent_dd').value = 0.0;
            document.getElementById('f_discount_day_of_month').value = 10;
            
            toggleQBSections('standard');
        } else {
            document.getElementById('modalTitle').innerText = 'Edit Terms';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('formTermId').value = id;
            document.getElementById('f_name').value = name;
            document.getElementById('f_inactive').checked = (is_inactive == 1);
            
            if (type === 'standard') {
                document.getElementById('f_net_due_days').value = net_days;
                document.getElementById('f_discount_percent_std').value = discount_pct;
                document.getElementById('f_discount_days').value = discount_days;
                
                document.getElementById('f_net_due_day_of_month').value = 31;
                document.getElementById('f_due_next_month_within_days').value = 5;
                document.getElementById('f_discount_percent_dd').value = 0.0;
                document.getElementById('f_discount_day_of_month').value = 10;
                
                toggleQBSections('standard');
            } else {
                document.getElementById('f_net_due_days').value = 0;
                document.getElementById('f_discount_percent_std').value = 0.0;
                document.getElementById('f_discount_days').value = 0;
                
                document.getElementById('f_net_due_day_of_month').value = net_day_month;
                document.getElementById('f_due_next_month_within_days').value = next_month_days;
                document.getElementById('f_discount_percent_dd').value = discount_pct;
                document.getElementById('f_discount_day_of_month').value = discount_day_month;
                
                toggleQBSections('date_driven');
            }
        }
    }
</script>