<!-- Inter Font & FontAwesome Icons -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

<style>
/* ============================================================
   SF PRO + APPLE DESIGN LANGUAGE — LEDGER TRANSACTION HISTORY
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

.history-root {
    font-family: var(--f-system);
    font-size: 15px;
    color: var(--t-primary);
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
}

.ledger-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.table-panel {
    background: var(--c-surface);
    border-radius: var(--r-xl);
    border: 0.5px solid var(--c-separator);
    box-shadow: var(--shadow-sm);
    overflow: hidden;
    position: relative;
    margin-bottom: 25px;
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
.filter-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 15px;
    margin-bottom: 15px;
}

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

.quick-ranges {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    align-items: center;
}
.quick-range-btn {
    padding: 6px 14px;
    background: var(--c-surface);
    border: 0.5px solid var(--c-separator);
    border-radius: var(--r-pill);
    font-size: 12.5px;
    font-weight: 600;
    cursor: pointer;
    color: var(--t-secondary);
    text-decoration: none;
    transition: all var(--dur-fast);
}
.quick-range-btn:hover, .quick-range-btn.active {
    background: var(--c-blue);
    color: #fff;
    border-color: var(--c-blue);
}

.amount-debit { color: var(--c-green); font-family: var(--f-mono); font-weight: 700; }
.amount-credit { color: var(--c-red); font-family: var(--f-mono); font-weight: 700; }
.amount-neutral { color: var(--t-tertiary); font-family: var(--f-mono); }
</style>

<div class="history-root">



    <?php if ($data['selected_account']): ?>
        <?php $acc = $data['selected_account']; ?>
        
        <div class="split-view-container" style="display: flex; gap: 20px; width: 100%;">
            <!-- Main Ledger Table Panel -->
            <div id="ledgerMainPanel" style="width: 100%; flex: 1; overflow-x: auto;">
                
                <!-- Account Information Summary -->
                <div style="background: var(--c-surface); border: 0.5px solid var(--c-separator); border-radius: var(--r-xl); padding: 20px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; box-shadow: var(--shadow-sm);">
                    <div>
                        <span class="sf-badge type-<?= $acc->account_type ?>" style="margin-bottom:8px;"><?= $acc->account_type ?></span>
                        <h3 style="margin: 0 0 5px 0; font-size: 20px; font-weight: 700; color: var(--t-primary);"><?= htmlspecialchars($acc->account_code) ?> - <?= htmlspecialchars($acc->account_name) ?></h3>
                        <p style="margin: 0; color: var(--t-secondary); font-size: 13px; font-weight: 500;">Parent Account: <?= $acc->parent_id ? 'Sub-ledger Account' : 'Main Account Ledger' ?></p>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-size: 11px; text-transform: uppercase; color: var(--t-label); font-weight: 700; letter-spacing: 0.05em; margin-bottom: 5px;">Current Ledger Balance</div>
                        <div style="font-size: 24px; font-weight: 800; color: var(--c-blue); font-family: var(--f-mono);">Rs. <?= number_format($acc->balance, 2) ?></div>
                    </div>
                </div>

                <!-- Filters Panel -->
                <div style="background: var(--c-surface); border: 0.5px solid var(--c-separator); border-radius: var(--r-xl); padding: 20px; margin-bottom: 20px; box-shadow: var(--shadow-sm);">
                    <h4 style="margin: 0 0 16px 0; font-size: 11px; font-weight: 700; text-transform:uppercase; letter-spacing:0.06em; color: var(--t-label);">Query & Filter Ledger</h4>
                    <form action="<?= APP_URL ?>/accounting/history/<?= $data['selected_id'] ?>" method="GET" id="filterForm">
                        <input type="hidden" name="quick_range" id="quickRangeInput" value="<?= htmlspecialchars($data['quick_range']) ?>">
                        
                        <div class="filter-grid">
                            <div class="sf-group">
                                <label>From Date</label>
                                <input type="date" name="start_date" id="startDate" class="sf-input" value="<?= htmlspecialchars($data['start_date']) ?>">
                            </div>
                            <div class="sf-group">
                                <label>To Date</label>
                                <input type="date" name="end_date" id="endDate" class="sf-input" value="<?= htmlspecialchars($data['end_date']) ?>">
                            </div>
                            <div class="sf-group">
                                <label>Reference/Description Search</label>
                                <input type="text" name="search" class="sf-input" placeholder="Search memo or ref..." value="<?= htmlspecialchars($data['search']) ?>">
                            </div>
                            <div class="sf-group">
                                <label>Transaction Type</label>
                                <select name="tx_type" class="sf-input">
                                    <option value="all" <?= $data['tx_type'] === 'all' ? 'selected' : '' ?>>All Transactions</option>
                                    <option value="debit" <?= $data['tx_type'] === 'debit' ? 'selected' : '' ?>>Debits Only (+)</option>
                                    <option value="credit" <?= $data['tx_type'] === 'credit' ? 'selected' : '' ?>>Credits Only (-)</option>
                                </select>
                            </div>
                        </div>

                        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:15px; margin-top:15px; border-top: 0.5px solid var(--c-separator); padding-top:15px;">
                            <div class="quick-ranges">
                                <span style="font-size:12px; font-weight:600; color: var(--t-secondary); margin-right:5px;">Quick Ranges:</span>
                                <button type="button" class="quick-range-btn <?= $data['quick_range'] === 'today' ? 'active' : '' ?>" onclick="setQuickRange('today')">Today</button>
                                <button type="button" class="quick-range-btn <?= $data['quick_range'] === 'last_week' ? 'active' : '' ?>" onclick="setQuickRange('last_week')">Last 7 Days</button>
                                <button type="button" class="quick-range-btn <?= $data['quick_range'] === 'last_month' ? 'active' : '' ?>" onclick="setQuickRange('last_month')">Last 30 Days</button>
                                <a href="<?= APP_URL ?>/accounting/history/<?= $data['selected_id'] ?>" class="quick-range-btn" style="background: var(--c-red-light); color: var(--c-red); border-color: var(--c-red-light);">Clear Filters</a>
                            </div>
                            <div>
                                <button type="submit" class="sf-btn primary">Apply Filters</button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Ledger Table -->
                <div class="table-panel">
                    <table class="cust-table">
                        <thead>
                            <tr>
                                <th style="width: 12%;">Date</th>
                                <th style="width: 20%;">Reference</th>
                                <th style="width: 28%;">Description / Memo</th>
                                <th style="width: 13%; text-align: right;">Debit (Dr)</th>
                                <th style="width: 13%; text-align: right;">Credit (Cr)</th>
                                <th style="width: 14%; text-align: right;">Running Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Prior/Starting Balance Row -->
                            <tr style="background: var(--c-surface2); font-weight: 600;">
                                <td colspan="3" style="color: var(--t-secondary);">Opening / Prior Cumulative Balance</td>
                                <td colspan="2" style="text-align: right; color: var(--t-label);">-</td>
                                <td style="text-align: right; font-family: var(--f-mono); font-size:14px; color: var(--c-blue); font-weight: 700;">
                                    Rs. <?= number_format($data['prior_balance'], 2) ?>
                                </td>
                            </tr>

                            <?php 
                            $runningBalance = $data['prior_balance'];
                            if(empty($data['transactions'])): 
                            ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; color: var(--t-secondary); padding: 30px;">
                                        <i class="fa-solid fa-receipt" style="font-size: 24px; margin-bottom: 8px; color: var(--t-tertiary);"></i><br>
                                        No transaction ledger entries found matching the filter criteria.
                                    </td>
                                </tr>
                            <?php 
                            else: 
                                foreach($data['transactions'] as $tx): 
                                    // Cumulative running balance computation
                                    if (in_array($acc->account_type, ['Asset', 'Expense'])) {
                                        $runningBalance += floatval($tx->debit) - floatval($tx->credit);
                                    } else {
                                        $runningBalance += floatval($tx->credit) - floatval($tx->debit);
                                    }
                            ?>
                                <tr>
                                    <td style="font-weight: 500;"><?= date('Y-m-d', strtotime($tx->entry_date)) ?></td>
                                    <td>
                                        <span style="background: var(--c-blue-light); color: var(--c-blue); padding: 2px 6px; border-radius: var(--r-xs); font-weight:700; font-size:11px; font-family: var(--f-mono);"><?= htmlspecialchars($tx->reference) ?></span>
                                        <?php if (!empty($tx->invoice_id)): ?>
                                            <a href="javascript:void(0)" onclick="openInvoiceMiniView(<?= $tx->invoice_id ?>, '<?= htmlspecialchars($tx->invoice_number) ?>')" style="margin-left: 8px; font-size: 11px; color: var(--c-blue); text-decoration: underline; font-weight: 600; white-space: nowrap;" class="mini-view-link">
                                                <i class="fa-solid fa-window-restore"></i> View
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($tx->description) ?></td>
                                    <td style="text-align: right;" class="<?= $tx->debit > 0 ? 'amount-debit' : 'amount-neutral' ?>">
                                        <?= $tx->debit > 0 ? 'Rs. ' . number_format($tx->debit, 2) : '-' ?>
                                    </td>
                                    <td style="text-align: right;" class="<?= $tx->credit > 0 ? 'amount-credit' : 'amount-neutral' ?>">
                                        <?= $tx->credit > 0 ? 'Rs. ' . number_format($tx->credit, 2) : '-' ?>
                                    </td>
                                    <td style="text-align: right; font-family: var(--f-mono); font-weight: 700; font-size:14px; color: var(--t-primary);">
                                        Rs. <?= number_format($runningBalance, 2) ?>
                                    </td>
                                </tr>
                            <?php 
                                endforeach; 
                            endif; 
                            ?>
                        </tbody>
                    </table>
                </div>

            </div>

            <!-- Dynamic Slide-out Split-screen Panel for Invoice mini-view -->
            <div id="invoiceSidePanel" style="width: 0; min-width: 0; display: none; background: var(--c-surface); border: 1px solid var(--c-separator); border-radius: var(--r-xl); box-shadow: var(--shadow-xl); overflow: hidden; transition: all 0.3s ease; flex-direction: column;">
                <div style="padding: 15px; border-bottom: 1px solid var(--c-separator); display: flex; justify-content: space-between; align-items: center; background: var(--c-surface2);">
                    <h3 style="margin: 0; font-size: 14px; font-weight: 700; color: var(--t-primary);" id="sidePanelTitle">Invoice Mini View</h3>
                    <button onclick="closeInvoiceMiniView()" style="background: transparent; border: none; font-size: 18px; color: var(--t-label); cursor: pointer; display: flex; align-items: center; justify-content: center; width: 28px; height: 28px; border-radius: 50%; transition: background 0.2s;"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <div style="flex: 1; position: relative; background: #525659; overflow: hidden; min-height: 600px;">
                    <iframe id="invoiceIframe" src="" style="width: 100%; height: 100%; border: none;"></iframe>
                </div>
            </div>
        </div>

    <?php else: ?>
        <div style="padding:40px; background: var(--c-surface); border-radius: var(--r-xl); text-align:center; box-shadow: var(--shadow-sm); border: 0.5px solid var(--c-separator);">
            <i class="fa-solid fa-receipt" style="font-size: 28px; margin-bottom: 8px; color: var(--t-tertiary);"></i>
            <p style="color: var(--t-secondary); margin:0;">Please select a ledger account above to display history.</p>
        </div>
    <?php endif; ?>

</div>

<script>
    function setQuickRange(range) {
        document.getElementById('quickRangeInput').value = range;
        
        // Let form submit automatically
        document.getElementById('filterForm').submit();
    }

    function openInvoiceMiniView(id, number) {
        const mainPanel = document.getElementById('ledgerMainPanel');
        const sidePanel = document.getElementById('invoiceSidePanel');
        const iframe = document.getElementById('invoiceIframe');
        const title = document.getElementById('sidePanelTitle');

        // Configure Title
        title.innerHTML = `<i class="fa-solid fa-file-invoice" style="color: var(--c-blue); margin-right: 6px;"></i> Invoice ${number} <span style="font-size:11px; font-weight:normal; color: var(--t-secondary);">(Split-Screen)</span>`;
        
        // Set secure dynamic src URL to invoice print view
        iframe.src = `<?= APP_URL ?>/sales/show/${id}`;

        // Trigger dynamic layout sizing transformation
        sidePanel.style.display = 'flex';
        setTimeout(() => {
            sidePanel.style.width = '48%';
            mainPanel.style.width = '52%';
            mainPanel.style.flex = 'none';
        }, 10);
    }

    function closeInvoiceMiniView() {
        const mainPanel = document.getElementById('ledgerMainPanel');
        const sidePanel = document.getElementById('invoiceSidePanel');
        const iframe = document.getElementById('invoiceIframe');

        // Trigger collapse transition
        sidePanel.style.width = '0';
        mainPanel.style.width = '100%';
        mainPanel.style.flex = '1';

        setTimeout(() => {
            iframe.src = '';
            sidePanel.style.display = 'none';
        }, 300);
    }
</script>
