<?php
?>
<style>
    .header-actions { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .btn { padding: 8px 16px; background: #0066cc; color: #fff; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; font-size: 14px;}
    .btn:hover { background: #005bb5; }
    .btn-outline { background: transparent; border: 1px solid #0066cc; color: #0066cc; }
    .btn-small { padding: 4px 8px; font-size: 11px; background: #333; }
    .data-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    .data-table th, .data-table td { padding: 12px; text-align: left; border-bottom: 1px solid var(--mac-border); }
    .data-table th { background-color: rgba(0,0,0,0.02); font-weight: 600; font-size: 13px; }
    .status-badge { padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: bold; background: #e8f5e9; color: #2e7d32; }
    
    .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 2000; align-items: center; justify-content: center; }
    .modal-content { background: var(--mac-bg); backdrop-filter: blur(20px); padding: 30px; border-radius: 12px; width: 600px; border: 1px solid var(--mac-border); max-height: 90vh; overflow-y: auto;}
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; margin-bottom: 5px; font-size: 13px; font-weight: 500; }
    .form-control { width: 100%; padding: 8px 12px; border: 1px solid var(--mac-border); border-radius: 4px; background: transparent; color: var(--text-main); box-sizing: border-box;}
    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
    
    .acct-routing { background: rgba(0,102,204,0.05); padding: 15px; border-radius: 8px; margin-bottom: 15px; border: 1px solid rgba(0,102,204,0.2); }
</style>

<div class="card">
    <div class="header-actions">
        <h2>Fixed Assets Register</h2>
        <button class="btn" onclick="document.getElementById('assetModal').style.display='flex'">+ Register Asset</button>
    </div>

    <?php if(!empty($data['error'])): ?>
        <div style="padding: 10px; background:#ffebee; color:#c62828; border-radius:4px; margin-bottom:15px;"><?= $data['error'] ?></div>
    <?php endif; ?>
    <?php if(!empty($data['success'])): ?>
        <div style="padding: 10px; background:#e8f5e9; color:#2e7d32; border-radius:4px; margin-bottom:15px;"><?= $data['success'] ?></div>
    <?php endif; ?>

    <table class="data-table">
        <thead>
            <tr>
                <th>Asset Name</th>
                <th>Purchased</th>
                <th style="text-align: right;">Cost (Rs:)</th>
                <th style="text-align: right;">Accum. Dep (Rs:)</th>
                <th style="text-align: right;">Book Value (Rs:)</th>
                <th style="text-align: center;">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if(empty($data['fixed_assets'])): ?>
            <tr><td colspan="6" style="text-align: center; color: #888;">No fixed assets registered.</td></tr>
            <?php else: foreach($data['fixed_assets'] as $ast): ?>
            <tr>
                <td><strong><?= htmlspecialchars($ast->asset_name) ?></strong><br><span style="font-size:11px; color:#888;">Life: <?= $ast->useful_life_years ?> Yrs</span></td>
                <td><?= date('M d, Y', strtotime($ast->purchase_date)) ?></td>
                <td style="text-align: right;"><?= number_format($ast->purchase_price, 2) ?></td>
                <td style="text-align: right; color:#c62828;"><?= number_format($ast->accumulated, 2) ?></td>
                <td style="text-align: right; font-weight:bold; color:#0066cc;"><?= number_format($ast->book_value, 2) ?></td>
                <td style="text-align: center;">
                    <?php if($ast->book_value > $ast->salvage_value): ?>
                        <button class="btn btn-small" onclick="openDepModal(<?= $ast->id ?>, '<?= addslashes($ast->asset_name) ?>', <?= $ast->annual_dep ?>)">Run Dep.</button>
                    <?php else: ?>
                        <span style="font-size: 11px; color: #888;">Fully Depreciated</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<!-- Register Asset Modal -->
<div class="modal" id="assetModal">
    <div class="modal-content">
        <h3 style="margin-top:0;">Register Fixed Asset</h3>
        <form action="<?= APP_URL ?>/asset" method="POST">
            <input type="hidden" name="action" value="add_asset">
            
            <div class="form-group"><label>Asset Name / Description *</label><input type="text" name="asset_name" class="form-control" required></div>
            
            <div class="grid-2">
                <div class="form-group"><label>Purchase Date</label><input type="date" name="purchase_date" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
                <div class="form-group"><label>Useful Life (Years)</label><input type="number" name="useful_life_years" class="form-control" min="1" value="5" required></div>
            </div>

            <div class="grid-2">
                <div class="form-group"><label>Purchase Cost (Rs:) *</label><input type="number" name="purchase_price" step="0.01" min="0" class="form-control" required></div>
                <div class="form-group"><label>Salvage Value (Rs:)</label><input type="number" name="salvage_value" step="0.01" min="0" class="form-control" value="0.00"></div>
            </div>

            <div class="acct-routing">
                <h4 style="margin-top:0; margin-bottom: 10px; color:#0066cc;">Ledger Mapping</h4>
                <div class="form-group">
                    <label>Asset Account (e.g. Equipment)</label>
                    <select name="asset_account_id" class="form-control" required>
                        <?php foreach($data['assets_accounts'] as $acc): ?>
                            <option value="<?= $acc->id ?>"><?= $acc->account_code ?> - <?= htmlspecialchars($acc->account_name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="grid-2">
                    <div class="form-group">
                        <label>Depreciation Expense Acct</label>
                        <select name="dep_expense_account_id" class="form-control" required>
                            <?php foreach($data['expense_accounts'] as $acc): ?>
                                <option value="<?= $acc->id ?>"><?= $acc->account_code ?> - <?= htmlspecialchars($acc->account_name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Accumulated Dep. Acct (Contra)</label>
                        <select name="accum_dep_account_id" class="form-control" required>
                            <?php foreach($data['all_accounts'] as $acc): ?>
                                <option value="<?= $acc->id ?>"><?= $acc->account_code ?> - <?= htmlspecialchars($acc->account_name) ?> (<?= $acc->account_type ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            
            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('assetModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn">Save Asset</button>
            </div>
        </form>
    </div>
</div>

<!-- Run Depreciation Modal -->
<div class="modal" id="depModal">
    <div class="modal-content" style="width: 400px;">
        <h3 style="margin-top:0; color:#0066cc;">Post Depreciation</h3>
        <p id="depAssetName" style="font-weight: bold; margin-bottom: 20px;"></p>
        <form action="<?= APP_URL ?>/asset" method="POST">
            <input type="hidden" name="action" value="run_depreciation">
            <input type="hidden" name="asset_id" id="depAssetId">
            
            <div class="form-group">
                <label>Posting Date</label>
                <input type="date" name="run_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>

            <div class="form-group">
                <label>Depreciation Amount (Rs:)</label>
                <input type="number" name="amount" id="depAmount" step="0.01" min="0.01" class="form-control" required style="font-size: 18px; font-weight:bold;">
                <span style="font-size: 11px; color:#888;">Pre-filled with Straight-Line Annual Calculation</span>
            </div>
            
            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('depModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn">Post to Ledger</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openDepModal(id, name, annualAmount) {
        document.getElementById('depModal').style.display = 'flex';
        document.getElementById('depAssetId').value = id;
        document.getElementById('depAssetName').innerText = name;
        document.getElementById('depAmount').value = annualAmount.toFixed(2);
    }
</script>