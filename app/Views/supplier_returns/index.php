<style>
    .header-actions { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .btn { padding: 8px 16px; background: #0066cc; color: #fff; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; font-size: 13px; font-weight: 600; }
    .btn:hover { background: #0052a3; }
    .btn-secondary { background: #666; }
    .btn-secondary:hover { background: #555; }
    .filter-bar { background: rgba(0,0,0,0.02); padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid var(--mac-border); display: flex; gap: 15px; align-items: flex-end; }
    .filter-group { display: flex; flex-direction: column; gap: 5px; }
    .filter-group label { font-size: 11px; font-weight: bold; text-transform: uppercase; color: #666; }
    .filter-control { padding: 6px 12px; border: 1px solid var(--mac-border); border-radius: 4px; background: transparent; color: var(--text-main); }
    .po-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    .po-table th, .po-table td { padding: 12px 10px; border-bottom: 1px solid var(--mac-border); text-align: left; }
    .po-table th { background-color: rgba(0,0,0,0.02); font-weight: 600; }
    .pagination { display: flex; justify-content: center; gap: 5px; margin-top: 20px; }
    .pagination a { padding: 6px 12px; border: 1px solid var(--mac-border); text-decoration: none; color: var(--text-main); border-radius: 4px; }
    .pagination a.active { background: #0066cc; color: #fff; border-color: #0066cc; }
</style>

<div class="card">
    <div class="header-actions">
        <h2>Supplier Goods Returns</h2>
        <a href="<?= APP_URL ?>/supplier-return/create" class="btn">+ Create Supplier Return</a>
    </div>

    <?php if(!empty($data['success'])): ?>
        <div style="padding: 10px; background:#e8f5e9; color:#2e7d32; border-radius:4px; margin-bottom:15px; border: 1px solid rgba(46,125,50,0.2); font-weight: 500;"><?= $data['success'] ?></div>
    <?php endif; ?>

    <form method="GET" action="<?= APP_URL ?>/supplier-return" class="filter-bar">
        <div class="filter-group" style="flex: 1;">
            <label>Search Return No. / Supplier</label>
            <input type="text" name="search" class="filter-control" placeholder="Search by Return Number or Supplier..." value="<?= htmlspecialchars($data['search']) ?>">
        </div>
        <div class="filter-group">
            <label>Supplier</label>
            <select name="vendor_id" class="filter-control">
                <option value="">All Suppliers</option>
                <?php foreach($data['vendors'] as $v): ?>
                    <option value="<?= $v->id ?>" <?= $data['filters']['vendor_id'] == $v->id ? 'selected' : '' ?>><?= htmlspecialchars($v->name) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn">Filter</button>
        <a href="<?= APP_URL ?>/supplier-return" class="btn btn-secondary">Reset</a>
    </form>

    <div style="overflow-x: auto;">
        <table class="po-table">
            <thead>
                <tr>
                    <th>Return No:</th>
                    <th>Supplier</th>
                    <th>Return Date</th>
                    <th>Created By</th>
                    <th style="text-align: right;">Total Return Value</th>
                    <th style="text-align: center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($data['returns'])): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; color: #888; padding: 30px 10px; font-style: italic;">No supplier returns found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach($data['returns'] as $ret): ?>
                        <tr>
                            <td style="font-weight: bold; color: #0066cc;">
                                <a href="<?= APP_URL ?>/supplier-return/show/<?= $ret->id ?>" style="text-decoration:none; color:inherit;">
                                    <?= htmlspecialchars($ret->return_number) ?>
                                </a>
                            </td>
                            <td><?= htmlspecialchars($ret->vendor_name) ?></td>
                            <td><?= date('M d, Y', strtotime($ret->return_date)) ?></td>
                            <td><?= htmlspecialchars($ret->creator_name) ?></td>
                            <td style="text-align: right; font-weight: bold;">Rs: <?= number_format($ret->total_amount, 2) ?></td>
                            <td style="text-align: center;">
                                <a href="<?= APP_URL ?>/supplier-return/show/<?= $ret->id ?>" class="btn" style="padding: 4px 8px; font-size:11px;">View Details</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if($data['total_pages'] > 1): ?>
        <div class="pagination">
            <?php for($i = 1; $i <= $data['total_pages']; $i++): ?>
                <a href="<?= APP_URL ?>/supplier-return?page=<?= $i ?>&search=<?= urlencode($data['search']) ?>&vendor_id=<?= $data['filters']['vendor_id'] ?>" class="<?= $data['page'] == $i ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>
