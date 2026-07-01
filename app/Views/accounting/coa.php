<?php
// Build the Hierarchical Tree Data Structure supporting 3 levels (Main -> Sub-Account -> Sub-Sub-Account)
$accountsMap = [];
foreach($data['accounts'] as $acc) {
    $acc->children = [];
    $accountsMap[$acc->id] = $acc;
}

$tree = [];
foreach($data['accounts'] as $acc) {
    if (empty($acc->parent_id)) {
        $tree[] = $accountsMap[$acc->id];
    } else {
        $parent = $accountsMap[$acc->parent_id] ?? null;
        if ($parent) {
            $parent->children[] = $accountsMap[$acc->id];
        } else {
            $tree[] = $accountsMap[$acc->id];
        }
    }
}
?>

<!-- Inter Font & FontAwesome Icons -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

<style>
/* ============================================================
   SF PRO + APPLE DESIGN LANGUAGE — CHART OF ACCOUNTS
   ============================================================ */

:root {
    --c-bg:           #f2f2f7;
    --c-surface:      #ffffff;
    --c-surface2:     #f9f9fb;
    --c-fill:         rgba(120,120,128,0.12);
    --c-fill2:        rgba(120,120,128,0.16);
    --c-separator:    rgba(60,60,67,0.12);
    --c-separator2:   rgba(60,60,67,0.06);

    --c-blue:         #007aff;
    --c-blue-light:   #e5f2ff;
    --c-blue-mid:     #b3d6ff;
    --c-green:        #34c759;
    --c-green-light:  #e6f9ec;
    --c-orange:       #ff9500;
    --c-orange-light: #fff4e5;
    --c-red:          #ff3b30;
    --c-red-light:    #fff0ef;

    --f-system: -apple-system, 'SF Pro Display', 'SF Pro Text', 'Inter', 'Helvetica Neue', sans-serif;
    --f-mono:   ui-monospace, 'SF Mono', 'Menlo', 'Monaco', monospace;

    --t-primary:   #1c1c1e;
    --t-secondary: #636366;
    --t-tertiary:  #aeaeb2;
    --t-label:     #8e8e93;

    --shadow-xs:  0 1px 2px rgba(0,0,0,0.04);
    --shadow-sm:  0 2px 8px rgba(0,0,0,0.06), 0 1px 3px rgba(0,0,0,0.04);
    --shadow-md:  0 8px 24px rgba(0,0,0,0.08), 0 2px 6px rgba(0,0,0,0.04);
    --shadow-xl:  0 24px 48px rgba(0,0,0,0.14), 0 4px 12px rgba(0,0,0,0.06);

    --r-xs: 6px;
    --r-sm: 10px;
    --r-md: 14px;
    --r-lg: 20px;
    --r-xl: 26px;
    --r-pill: 999px;

    --ease-spring: cubic-bezier(0.34, 1.56, 0.64, 1);
    --ease-ios:    cubic-bezier(0.25, 0.1, 0.25, 1);
    --dur-fast:    0.18s;
    --dur-mid:     0.28s;
    --dur-slow:    0.42s;
}

@media (prefers-color-scheme: dark) {
    :root {
        --c-bg:           #121212;
        --c-surface:      #1e1e2e;
        --c-surface2:     #161622;
        --c-fill:         rgba(255,255,255,0.08);
        --c-fill2:        rgba(255,255,255,0.12);
        --c-separator:    rgba(255,255,255,0.15);
        --c-separator2:   rgba(255,255,255,0.08);
        --t-primary:   #f5f5f7;
        --t-secondary: #a1a1aa;
        --t-tertiary:  #71717a;
        --t-label:     #52525b;
    }
}

.coa-root {
    font-family: var(--f-system);
    font-size: 15px;
    color: var(--t-primary);
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
    padding-bottom: 100px;
}

/* ---- Table Panel ---- */
.table-panel {
    background: var(--c-surface);
    border-radius: var(--r-xl);
    border: 0.5px solid var(--c-separator);
    box-shadow: var(--shadow-sm);
    overflow: hidden;
    position: relative;
    margin-top: 10px;
}
.cust-table { width: 100%; border-collapse: collapse; }
.cust-table thead th {
    padding: 13px 18px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: var(--t-label);
    background: var(--c-surface2);
    border-bottom: 0.5px solid var(--c-separator);
    white-space: nowrap;
    text-align: left;
}
.cust-table tbody tr {
    transition: background var(--dur-fast);
    border-bottom: 0.5px solid var(--c-separator2);
}
.cust-table tbody tr:last-child { border-bottom: none; }
.cust-table tbody tr:hover { background: var(--c-fill2); }
.cust-table td {
    padding: 14px 18px;
    font-size: 14px;
    color: var(--t-primary);
    vertical-align: middle;
}

/* ---- Badges ---- */
.sf-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 8px; border-radius: var(--r-xs);
    font-size: 11px; font-weight: 700;
    text-transform: uppercase; letter-spacing: 0.02em;
}
.sf-badge.badge-active { background: var(--c-green-light); color: var(--c-green); }
.sf-badge.badge-owed   { background: var(--c-red-light);   color: var(--c-red); }
.sf-badge .dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }

.sf-badge.type-Asset { background: var(--c-blue-light); color: var(--c-blue); }
.sf-badge.type-Liability { background: var(--c-red-light); color: var(--c-red); }
.sf-badge.type-Equity { background: #f3e5f5; color: #6a1b9a; }
.sf-badge.type-Revenue { background: var(--c-green-light); color: var(--c-green); }
.sf-badge.type-Expense { background: var(--c-orange-light); color: var(--c-orange); }

@media (prefers-color-scheme: dark) {
    .sf-badge.type-Asset { background: rgba(0, 122, 255, 0.15); color: #64b5f6; }
    .sf-badge.type-Liability { background: rgba(255, 59, 48, 0.15); color: #ff6b62; }
    .sf-badge.type-Equity { background: rgba(106, 27, 154, 0.15); color: #ba68c8; }
    .sf-badge.type-Revenue { background: rgba(52, 199, 89, 0.15); color: #81c784; }
    .sf-badge.type-Expense { background: rgba(255, 149, 0, 0.15); color: #ffb74d; }
}

/* ---- Alerts ---- */
.sf-alert {
    display: flex; align-items: flex-start; gap: 12px;
    background: var(--c-surface);
    border-radius: var(--r-md);
    padding: 14px 16px;
    margin-bottom: 20px;
    box-shadow: var(--shadow-xs);
    border: 0.5px solid var(--c-separator);
    border-left-width: 3.5px;
    font-size: 14px;
}
.sf-alert.success { border-left-color: var(--c-green); }
.sf-alert.error   { border-left-color: var(--c-red); }
.sf-alert-icon { font-size: 18px; flex-shrink: 0; padding-top: 1px; }
.sf-alert.success .sf-alert-icon { color: var(--c-green); }
.sf-alert.error   .sf-alert-icon { color: var(--c-red); }
.sf-alert-title { font-weight: 700; color: var(--t-primary); margin-bottom: 2px; }
.sf-alert-msg   { color: var(--t-secondary); font-size: 13px; }
.sf-alert-close {
    margin-left: auto; flex-shrink: 0; background: none; border: none;
    color: var(--t-tertiary); cursor: pointer; font-size: 15px; padding: 2px;
}
.sf-alert-close:hover { color: var(--t-secondary); }

/* ---- Modal System ---- */
.modal-veil {
    position: fixed; inset: 0; z-index: 2000;
    background: rgba(0, 0, 0, 0.4);
    backdrop-filter: blur(10px);
    display: flex; align-items: center; justify-content: center;
    transition: opacity var(--dur-mid) var(--ease-ios);
}
.modal-veil.hidden { display: none !important; }
.sf-modal {
    background: var(--c-surface);
    border: 0.5px solid var(--c-separator);
    border-radius: var(--r-xl);
    box-shadow: var(--shadow-xl);
    width: 520px; max-width: 95vw;
    animation: sfModalSlide var(--dur-mid) var(--ease-spring);
    overflow: hidden;
    display: flex;
    flex-direction: column;
}
@keyframes sfModalSlide {
    from { transform: translateY(20px) scale(0.97); opacity: 0; }
    to { transform: translateY(0) scale(1); opacity: 1; }
}
.modal-head {
    display: flex; justify-content: space-between; align-items: center;
    padding: 18px 24px; border-bottom: 0.5px solid var(--c-separator);
}
.modal-title { font-size: 16px; font-weight: 700; margin: 0; }
.modal-close {
    background: var(--c-fill); border: none; width: 26px; height: 26px;
    border-radius: 50%; display: flex; align-items: center; justify-content: center;
    color: var(--t-label); cursor: pointer; font-size: 12px;
}
.modal-close:hover { background: var(--c-fill2); color: var(--t-secondary); }
.modal-body { padding: 24px; }
.modal-foot {
    display: flex; justify-content: flex-end; gap: 10px;
    padding: 16px 24px; background: var(--c-surface2);
    border-top: 0.5px solid var(--c-separator);
}

/* ---- Input elements ---- */
.sf-group { margin-bottom: 16px; }
.sf-group label { display: block; margin-bottom: 6px; font-size: 12px; font-weight: 600; color: var(--t-secondary); text-transform: uppercase; }
.sf-input {
    width: 100%; padding: 10px 14px;
    border-radius: var(--r-sm); border: 0.5px solid var(--c-separator);
    background: var(--c-surface2); color: var(--t-primary);
    font-size: 14px; outline: none; transition: border-color var(--dur-fast);
    box-sizing: border-box;
}
.sf-input:focus { border-color: var(--c-blue); background: var(--c-surface); }
.grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }

/* ---- Buttons ---- */
.sf-btn {
    padding: 8px 14px;
    border-radius: var(--r-md);
    font-size: 13px; font-weight: 600;
    display: inline-flex; align-items: center; gap: 6px;
    border: 0.5px solid transparent; cursor: pointer;
    transition: transform var(--dur-fast) var(--ease-spring), filter var(--dur-fast);
    text-decoration: none;
}
.sf-btn:active { transform: scale(0.97); }
.sf-btn.primary { background: var(--c-blue); color: #fff; }
.sf-btn.neutral { background: var(--c-surface); border-color: var(--c-separator); color: var(--t-primary); box-shadow: var(--shadow-xs); }
.sf-btn.neutral:hover { background: var(--c-surface2); }
.sf-btn.danger { background: var(--c-red); color: #fff; }
.sf-btn.success { background: var(--c-green); color: #fff; }

.btn-small {
    padding: 5px 10px;
    border-radius: var(--r-xs);
    font-size: 11px;
    font-weight: 600;
    border: 0.5px solid var(--c-separator);
    background: var(--c-surface);
    color: var(--t-secondary);
    cursor: pointer;
    transition: all var(--dur-fast);
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}
.btn-small:hover {
    background: var(--c-blue-light);
    color: var(--c-blue);
    border-color: var(--c-blue-mid);
}

/* ---- Command Bar (Dynamic Island style) ---- */
.cmd-bar {
    position: fixed;
    bottom: 28px; left: 50%;
    transform: translateX(-50%);
    background: rgba(28, 28, 30, 0.92);
    backdrop-filter: saturate(180%) blur(28px);
    -webkit-backdrop-filter: saturate(180%) blur(28px);
    border: 0.5px solid rgba(255,255,255,0.12);
    border-radius: var(--r-pill);
    padding: 7px 10px;
    display: flex; align-items: center; gap: 4px;
    box-shadow: var(--shadow-xl), 0 0 0 0.5px rgba(0,0,0,0.3);
    z-index: 1000;
}
.cmd-search {
    display: flex; align-items: center; gap: 9px;
    background: rgba(255,255,255,0.1);
    border-radius: var(--r-pill);
    padding: 8px 14px;
    width: 196px;
    transition: width var(--dur-slow) var(--ease-ios),
                background var(--dur-mid);
}
.cmd-search:focus-within {
    width: 300px;
    background: rgba(255,255,255,0.18);
}
.cmd-search i { color: rgba(255,255,255,0.55); font-size: 14px; flex-shrink: 0; }
.cmd-search input {
    background: transparent; border: none; outline: none;
    color: #fff; font-size: 14px; font-weight: 500;
    font-family: var(--f-system); width: 100%;
}
.cmd-search input::placeholder { color: rgba(255,255,255,0.45); }
.cmd-divider { width: 0.5px; height: 22px; background: rgba(255,255,255,0.15); margin: 0 3px; }
.cmd-icon {
    width: 38px; height: 38px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    color: rgba(255,255,255,0.8); font-size: 15px;
    background: transparent; border: none; cursor: pointer; text-decoration: none;
    transition: background var(--dur-fast);
}
.cmd-icon:hover { background: rgba(255,255,255,0.12); color: #fff; }
</style>

<div class="coa-root">

    <!-- Notifications -->
    <?php if (!empty($data['error'])): ?>
        <div class="sf-alert error" id="error-alert">
            <i class="fa-solid fa-circle-exclamation sf-alert-icon"></i>
            <div>
                <div class="sf-alert-title">Error</div>
                <div class="sf-alert-msg"><?= htmlspecialchars($data['error']) ?></div>
            </div>
            <button type="button" class="sf-alert-close" onclick="document.getElementById('error-alert').style.display='none'"><i class="fa-solid fa-xmark"></i></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($data['success'])): ?>
        <div class="sf-alert success" id="success-alert">
            <i class="fa-solid fa-circle-check sf-alert-icon"></i>
            <div>
                <div class="sf-alert-title">Success</div>
                <div class="sf-alert-msg"><?= htmlspecialchars($data['success']) ?></div>
            </div>
            <button type="button" class="sf-alert-close" onclick="document.getElementById('success-alert').style.display='none'"><i class="fa-solid fa-xmark"></i></button>
        </div>
    <?php endif; ?>

    <!-- Table View -->
    <div class="table-panel">
        <table class="cust-table">
            <thead>
                <tr>
                    <th style="width: 40%;">Account Code & Name</th>
                    <th style="width: 15%;">Type</th>
                    <th style="width: 20%; text-align: right;">Current Balance</th>
                    <th style="width: 13%; text-align: center;">Status</th>
                    <th style="width: 12%; text-align: center;">Actions</th>
                </tr>
            </thead>
            <tbody id="tableBody">
                <?php if(empty($tree)): ?>
                    <tr><td colspan="5" style="text-align: center; color: var(--t-secondary); padding: 40px;"><i class="fa-solid fa-folder-open" style="font-size: 24px; margin-bottom: 8px;"></i><br>No accounts found.</td></tr>
                <?php else: foreach($tree as $parent): ?>
                    
                    <!-- PARENT ROW (Level 1) -->
                    <tr class="coa-row">
                        <td>
                            <i class="fa-solid fa-folder" style="color: var(--c-blue); margin-right: 8px;"></i>
                            <a href="<?= APP_URL ?>/accounting/history/<?= $parent->id ?>" style="text-decoration:none; color: var(--c-blue); font-weight:700;">
                                <?= htmlspecialchars($parent->account_code) ?> - <?= htmlspecialchars($parent->account_name) ?>
                            </a>
                        </td>
                        <td><span class="sf-badge type-<?= $parent->account_type ?>"><?= $parent->account_type ?></span></td>
                        <td style="text-align: right; font-family: var(--f-mono); font-weight: 600; font-size: 14.5px;">Rs: <?= number_format($parent->balance, 2) ?></td>
                        <td style="text-align: center;">
                            <?php if($parent->is_active): ?>
                                <span class="sf-badge badge-active"><span class="dot"></span>Active</span>
                            <?php else: ?>
                                <span class="sf-badge badge-owed"><span class="dot"></span>Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: center;">
                            <button type="button" class="btn-small" onclick="openModal('edit', '<?= $parent->id ?>', '<?= addslashes($parent->account_code) ?>', '<?= addslashes($parent->account_name) ?>', '<?= $parent->account_type ?>', '', <?= $parent->is_active ?>)">
                                <i class="fa-solid fa-pen-to-square"></i> Edit
                            </button>
                        </td>
                    </tr>

                    <!-- SUB-ACCOUNT ROWS (Level 2) -->
                    <?php foreach($parent->children as $child): ?>
                    <tr class="coa-row">
                        <td style="padding-left: 36px;">
                            <i class="fa-solid fa-chevron-right" style="font-size: 9px; color: var(--t-tertiary); margin-right: 8px; vertical-align: middle;"></i>
                            <i class="fa-solid fa-folder-open" style="color: var(--t-secondary); margin-right: 8px;"></i>
                            <a href="<?= APP_URL ?>/accounting/history/<?= $child->id ?>" style="text-decoration:none; color:inherit; font-weight:600;">
                                <?= htmlspecialchars($child->account_code) ?> - <?= htmlspecialchars($child->account_name) ?>
                            </a>
                        </td>
                        <td><span class="sf-badge type-<?= $child->account_type ?>"><?= $child->account_type ?></span></td>
                        <td style="text-align: right; font-family: var(--f-mono); font-weight: 500; font-size: 14px;">Rs: <?= number_format($child->balance, 2) ?></td>
                        <td style="text-align: center;">
                            <?php if($child->is_active): ?>
                                <span class="sf-badge badge-active"><span class="dot"></span>Active</span>
                            <?php else: ?>
                                <span class="sf-badge badge-owed"><span class="dot"></span>Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: center;">
                            <button type="button" class="btn-small" onclick="openModal('edit', '<?= $child->id ?>', '<?= addslashes($child->account_code) ?>', '<?= addslashes($child->account_name) ?>', '<?= $child->account_type ?>', '<?= $child->parent_id ?>', <?= $child->is_active ?>)">
                                <i class="fa-solid fa-pen-to-square"></i> Edit
                            </button>
                        </td>
                    </tr>

                    <!-- SUB-SUB-ACCOUNT ROWS (Level 3) -->
                    <?php foreach($child->children as $subsub): ?>
                    <tr class="coa-row" style="background: rgba(0,0,0,0.01);">
                        <td style="padding-left: 64px;">
                            <i class="fa-solid fa-circle" style="font-size: 5px; color: var(--t-tertiary); margin-right: 8px; vertical-align: middle;"></i>
                            <i class="fa-solid fa-file-invoice-dollar" style="color: var(--t-tertiary); margin-right: 8px;"></i>
                            <a href="<?= APP_URL ?>/accounting/history/<?= $subsub->id ?>" style="text-decoration:none; color:inherit; font-weight:500; font-style: italic;">
                                <?= htmlspecialchars($subsub->account_code) ?> - <?= htmlspecialchars($subsub->account_name) ?>
                            </a>
                        </td>
                        <td><span class="sf-badge type-<?= $subsub->account_type ?>" style="opacity: 0.85;"><?= $subsub->account_type ?></span></td>
                        <td style="text-align: right; font-family: var(--f-mono); font-size: 13.5px; color: var(--t-secondary);">Rs: <?= number_format($subsub->balance, 2) ?></td>
                        <td style="text-align: center;">
                            <?php if($subsub->is_active): ?>
                                <span class="sf-badge badge-active"><span class="dot"></span>Active</span>
                            <?php else: ?>
                                <span class="sf-badge badge-owed"><span class="dot"></span>Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: center;">
                            <button type="button" class="btn-small" onclick="openModal('edit', '<?= $subsub->id ?>', '<?= addslashes($subsub->account_code) ?>', '<?= addslashes($subsub->account_name) ?>', '<?= $subsub->account_type ?>', '<?= $subsub->parent_id ?>', <?= $subsub->is_active ?>)">
                                <i class="fa-solid fa-pen-to-square"></i> Edit
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>

                    <!-- CUSTOMER ACCOUNTS UNDER AR (Virtual sub-sub accounts) -->
                    <?php if (($child->account_code == '1200' || stripos($child->account_name, 'Receivable') !== false) && !empty($data['customers'])): ?>
                        <?php foreach($data['customers'] as $cust): ?>
                            <?php if (floatval($cust->outstanding_balance) != 0): ?>
                            <tr class="coa-row" style="background: rgba(0, 122, 255, 0.02);">
                                <td style="padding-left: 64px;">
                                    <i class="fa-solid fa-user-tag" style="color: var(--c-blue); margin-right: 8px;"></i>
                                    <span style="font-weight: 600; color: var(--c-blue);"><?= htmlspecialchars($cust->name) ?></span>
                                    <span style="font-size: 11px; color: var(--t-secondary); font-style: italic; margin-left: 6px;">(Customer Ledger)</span>
                                </td>
                                <td><span class="sf-badge" style="background: var(--c-blue-light); color: var(--c-blue);">Customer AR</span></td>
                                <td style="text-align: right; font-family: var(--f-mono); font-size: 13.5px; color: var(--c-blue); font-weight: 700;">Rs: <?= number_format($cust->outstanding_balance, 2) ?></td>
                                <td style="text-align: center;"><span class="sf-badge badge-owed"><span class="dot"></span>Outstanding</span></td>
                                <td style="text-align: center;">
                                    <a href="<?= APP_URL ?>/customer/edit/<?= $cust->id ?>" class="btn-small" style="text-decoration: none;">
                                        <i class="fa-solid fa-address-card"></i> View Profile
                                    </a>
                                </td>
                            </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <?php endforeach; ?>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Add / Edit Account -->
<div class="modal-veil hidden" id="coaModal">
    <div class="sf-modal">
        <div class="modal-head">
            <h3 class="modal-title" id="modalTitle">Manage Account</h3>
            <button type="button" class="modal-close" onclick="closeModal()"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form action="<?= APP_URL ?>/accounting/coa" method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" id="formAction" value="add_main">
                <input type="hidden" name="account_id" id="formId" value="">
                
                <div class="sf-group" id="parentGroup" style="display:none; background: var(--c-surface2); padding:16px; border-radius: var(--r-sm); border:0.5px solid var(--c-separator); margin-bottom: 16px;">
                    <label>Parent Account</label>
                    <select name="parent_id" id="formParent" class="sf-input" style="width: 100%;">
                        <option value="">-- No Parent (Make Main Account) --</option>
                        <?php foreach($data['accounts'] as $acc): ?>
                            <option value="<?= $acc->id ?>"><?= $acc->account_code ?> - <?= htmlspecialchars($acc->account_name) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <span style="font-size:11px; color: var(--t-secondary); display: block; margin-top: 6px;">Sub-accounts automatically inherit the financial Type of their Parent.</span>
                </div>

                <div class="grid-2">
                    <div class="sf-group">
                        <label>Account Code *</label>
                        <input type="text" name="account_code" id="formCode" class="sf-input" placeholder="e.g. 1010" required>
                    </div>
                    <div class="sf-group">
                        <label>Account Name *</label>
                        <input type="text" name="account_name" id="formName" class="sf-input" placeholder="e.g. Cash in Bank" required>
                    </div>
                </div>

                <div class="sf-group" id="typeGroup">
                    <label>Financial Type *</label>
                    <select name="account_type" id="formType" class="sf-input" required>
                        <option value="Asset">Asset (Cash, Receivables, Property)</option>
                        <option value="Liability">Liability (Payables, Loans, Tax)</option>
                        <option value="Equity">Equity (Capital, Retained Earnings)</option>
                        <option value="Revenue">Revenue (Income, Sales)</option>
                        <option value="Expense">Expense (COGS, Rent, Salaries)</option>
                    </select>
                </div>

                <div class="sf-group" id="statusGroup" style="display:none;">
                    <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-weight: 600; text-transform: none;">
                        <input type="checkbox" name="is_active" id="formStatus" value="1" style="width:16px; height:16px;"> Active Ledger
                    </label>
                </div>
            </div>
            
            <div class="modal-foot">
                <button type="button" class="sf-btn neutral" onclick="closeModal()">Cancel</button>
                <button type="submit" class="sf-btn primary" id="submitBtn">Save Account</button>
            </div>
        </form>
    </div>
</div>

<!-- Floating Command Bar -->
<div class="cmd-bar">
    <div class="cmd-search" onclick="document.getElementById('searchInput').focus()">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" id="searchInput" placeholder="Search accounts..." onkeyup="filterTable()">
    </div>
    <div class="cmd-divider"></div>
    <button type="button" class="cmd-icon" onclick="openModal('add_main')" title="Add Main Account"><i class="fa-solid fa-folder-plus"></i></button>
    <button type="button" class="cmd-icon" onclick="openModal('add_sub')" title="Add Sub-Account"><i class="fa-solid fa-file-circle-plus"></i></button>
</div>

<script>
    function openModal(mode, id = '', code = '', name = '', type = '', parentId = '', status = 1) {
        const modal = document.getElementById('coaModal');
        modal.classList.remove('hidden');
        modal.style.opacity = '1';
        
        const actionInput = document.getElementById('formAction');
        const titleInput = document.getElementById('modalTitle');
        const parentGroup = document.getElementById('parentGroup');
        const typeGroup = document.getElementById('typeGroup');
        const statusGroup = document.getElementById('statusGroup');
        const btn = document.getElementById('submitBtn');

        // Reset fields
        document.getElementById('formId').value = id;
        document.getElementById('formCode').value = code;
        document.getElementById('formName').value = name;
        document.getElementById('formType').value = type || 'Asset';
        document.getElementById('formParent').value = parentId;
        document.getElementById('formStatus').checked = status == 1;

        if (mode === 'add_main') {
            titleInput.innerText = 'Create Main Account';
            actionInput.value = 'add_main';
            parentGroup.style.display = 'none';
            typeGroup.style.display = 'block';
            statusGroup.style.display = 'none';
            btn.innerText = 'Save Main Account';
            document.getElementById('formType').required = true;
            document.getElementById('formParent').required = false;
        } 
        else if (mode === 'add_sub') {
            titleInput.innerText = 'Create Sub-Account';
            actionInput.value = 'add_sub';
            parentGroup.style.display = 'block';
            typeGroup.style.display = 'none';
            statusGroup.style.display = 'none';
            btn.innerText = 'Save Sub-Account';
            document.getElementById('formParent').required = true;
            document.getElementById('formType').required = false; // Inherited
        } 
        else if (mode === 'edit') {
            titleInput.innerText = 'Edit Ledger Account';
            actionInput.value = 'edit_account';
            parentGroup.style.display = 'block';
            typeGroup.style.display = 'block';
            statusGroup.style.display = 'block';
            btn.innerText = 'Update Account';
            document.getElementById('formType').required = true;
            document.getElementById('formParent').required = false;
        }
    }

    function closeModal() {
        const modal = document.getElementById('coaModal');
        modal.style.opacity = '0';
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 150);
    }

    function filterTable() {
        const query = document.getElementById('searchInput').value.toLowerCase();
        const rows = document.querySelectorAll('.coa-row');
        
        rows.forEach(row => {
            const text = row.innerText.toLowerCase();
            if (text.includes(query)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }
</script>