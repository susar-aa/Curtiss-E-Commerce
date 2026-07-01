<?php
$importResults = $_SESSION['customer_import_results'] ?? null;
if ($importResults) {
    unset($_SESSION['customer_import_results']);
}

// Dynamic Stats calculation from $data['customers']
$totalCustomers = count($data['customers'] ?? []);
$totalOutstanding = 0;
$owedCustomersCount = 0;
foreach ($data['customers'] ?? [] as $cust) {
    $bal = floatval($cust->outstanding_balance ?? 0);
    $totalOutstanding += $bal;
    if ($bal > 0) {
        $owedCustomersCount++;
    }
}
?>

<!-- Inter Font & FontAwesome Icons -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

<style>
/* ============================================================
   SF PRO + APPLE DESIGN LANGUAGE — CUSTOMER CATALOG
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

.cust-root {
    font-family: var(--f-system);
    font-size: 15px;
    color: var(--t-primary);
    background: var(--c-bg);
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
}

.cust-wrap {
    max-width: 1420px;
    margin: 0 auto;
    padding: 16px 24px 100px;
}

/* ---- Stat Cards ---- */
.stat-row {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 14px;
    margin-bottom: 24px;
}
.stat-card {
    background: var(--c-surface);
    border-radius: var(--r-xl);
    padding: 16px 20px;
    box-shadow: var(--shadow-sm);
    border: 0.5px solid var(--c-separator);
    transition: transform var(--dur-fast) var(--ease-ios), box-shadow var(--dur-fast) var(--ease-ios);
    cursor: default;
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
    gap: 16px;
}
.stat-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 2.5px;
    border-radius: var(--r-xl) var(--r-xl) 0 0;
}
.stat-card.blue::before  { background: var(--c-blue); }
.stat-card.orange::before { background: var(--c-orange); }
.stat-card.red::before   { background: var(--c-red); }
.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}
.stat-icon {
    width: 46px; height: 46px;
    border-radius: var(--r-sm);
    display: flex; align-items: center; justify-content: center;
    font-size: 18px;
    flex-shrink: 0;
}
.stat-card.blue  .stat-icon { background: var(--c-blue-light);   color: var(--c-blue); }
.stat-card.orange .stat-icon { background: var(--c-orange-light); color: var(--c-orange); }
.stat-card.red   .stat-icon { background: var(--c-red-light);    color: var(--c-red); }
.stat-info { display: flex; flex-direction: column; justify-content: center; }
.stat-num {
    font-size: 22px;
    font-weight: 700;
    letter-spacing: -0.04em;
    color: var(--t-primary);
    line-height: 1.1;
    margin-bottom: 2px;
}
.stat-lbl {
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: var(--t-label);
}

/* ---- Filter Shelf ---- */
.filter-shelf {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    align-items: center;
    margin-bottom: 20px;
}
.filter-chip {
    display: inline-flex; align-items: center; gap: 6px;
    background: var(--c-surface);
    border: 0.5px solid var(--c-separator);
    border-radius: var(--r-pill);
    padding: 7px 14px;
    font-size: 13px;
    font-weight: 500;
    color: var(--t-secondary);
    box-shadow: var(--shadow-xs);
    transition: border-color var(--dur-fast), box-shadow var(--dur-fast);
}
.filter-chip:focus-within {
    border-color: var(--c-blue);
    box-shadow: 0 0 0 3px rgba(0,122,255,0.12);
}
.filter-chip-label {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.04em;
    color: var(--t-label);
    text-transform: uppercase;
}
.filter-reset {
    background: transparent;
    border: 0.5px solid var(--c-separator);
    border-radius: var(--r-pill);
    padding: 7px 14px;
    font-size: 13px;
    font-weight: 600;
    color: var(--t-secondary);
    cursor: pointer;
    transition: all var(--dur-fast);
}
.filter-reset:hover { background: var(--c-fill); color: var(--t-primary); }
.filter-count {
    margin-left: auto;
    font-size: 13px;
    color: var(--t-secondary);
    font-weight: 500;
}
.filter-count strong { color: var(--t-primary); font-weight: 700; }

/* ---- Custom Dropdown ---- */
.sf-dropdown { position: relative; outline: none; cursor: pointer; }
.sf-dropdown-val {
    display: flex; align-items: center; gap: 5px;
    font-size: 13.5px; font-weight: 600; color: var(--t-primary);
}
.sf-dropdown-val::after {
    content: '';
    display: inline-block; width: 12px; height: 12px;
    background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%238e8e93' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E") center/contain no-repeat;
}
.sf-dropdown-menu {
    position: absolute; top: calc(100% + 10px); left: 0; z-index: 200;
    background: var(--c-surface);
    border-radius: var(--r-md);
    border: 0.5px solid var(--c-separator);
    box-shadow: var(--shadow-xl);
    min-width: 200px;
    max-height: 280px; overflow-y: auto;
    opacity: 0; visibility: hidden;
    transform: translateY(-6px) scale(0.98);
    transform-origin: top left;
    transition: opacity var(--dur-mid) var(--ease-ios),
                transform var(--dur-mid) var(--ease-ios),
                visibility var(--dur-mid);
    padding: 6px;
}
.sf-dropdown:focus-within .sf-dropdown-menu {
    opacity: 1; visibility: visible; transform: translateY(0) scale(1);
}
.sf-dropdown-item {
    padding: 9px 12px;
    font-size: 13.5px;
    font-weight: 500;
    color: var(--t-primary);
    border-radius: var(--r-sm);
    transition: background var(--dur-fast);
    cursor: pointer;
}
.sf-dropdown-item:hover { background: var(--c-fill); }
.sf-dropdown-item.active { background: var(--c-blue-light); color: var(--c-blue); font-weight: 600; }

/* ---- Table Panel ---- */
.table-panel {
    background: var(--c-surface);
    border-radius: var(--r-xl);
    border: 0.5px solid var(--c-separator);
    box-shadow: var(--shadow-sm);
    overflow: hidden;
    position: relative;
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
    cursor: pointer;
}
.cust-table tbody tr:last-child { border-bottom: none; }
.cust-table tbody tr:hover { background: var(--c-fill2); }
.cust-table td {
    padding: 14px 18px;
    font-size: 14px;
    color: var(--t-primary);
    vertical-align: middle;
}

/* ---- Badges & Labels ---- */
.sf-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 8px; border-radius: var(--r-xs);
    font-size: 11px; font-weight: 700;
    text-transform: uppercase; letter-spacing: 0.02em;
}
.sf-badge.badge-active { background: var(--c-green-light); color: var(--c-green); }
.sf-badge.badge-owed   { background: var(--c-red-light);   color: var(--c-red); }
.sf-badge .dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }

.avatar-circle {
    width: 36px; height: 36px;
    background: var(--c-fill);
    color: var(--t-secondary);
    border-radius: 50%;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 14px; font-weight: 700;
    flex-shrink: 0;
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

/* ---- Button Elements ---- */
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

.act-btn {
    width: 28px; height: 28px; border-radius: 50%;
    display: inline-flex; align-items: center; justify-content: center;
    border: none; background: transparent; cursor: pointer;
    font-size: 13.5px; transition: background var(--dur-fast);
    text-decoration: none;
}
.act-btn.view  { color: var(--c-blue); }
.act-btn.view:hover { background: var(--c-blue-light); }
.act-btn.edit  { color: var(--t-secondary); }
.act-btn.edit:hover { background: var(--c-fill); }

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
    z-index: 100;
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
.cmd-cta {
    display: flex; align-items: center; gap: 7px;
    background: #fff; color: #1c1c1e;
    border: none; border-radius: var(--r-pill);
    padding: 0 18px; height: 38px;
    font-size: 14px; font-weight: 700;
    font-family: var(--f-system);
    cursor: pointer; text-decoration: none;
    transition: transform var(--dur-fast) var(--ease-spring),
                background var(--dur-fast);
    margin-left: 2px;
}
.cmd-cta:hover { background: #e5e5ea; transform: scale(0.97); }

/* ---- Pagination styles ---- */
.sf-pagination {
    display: flex; justify-content: space-between; align-items: center;
    padding: 16px 20px; background: var(--c-surface2);
    border-top: 0.5px solid var(--c-separator);
}
.pg-info { font-size: 13px; color: var(--t-secondary); }
.pg-right { display: flex; align-items: center; gap: 20px; }
.pg-size-wrap { display: flex; align-items: center; gap: 8px; }
.pg-size-lbl { font-size: 12px; font-weight: 600; color: var(--t-label); text-transform: uppercase; }
.pg-size-sel {
    font-family: var(--f-system); font-size: 13px; font-weight: 600;
    color: var(--t-primary); background: var(--c-fill);
    border: 0.5px solid var(--c-separator); border-radius: var(--r-sm);
    padding: 5px 9px; outline: none; cursor: pointer;
    transition: border-color var(--dur-fast);
}
.pg-size-sel:hover { border-color: var(--c-blue); }
.pg-nav { display: flex; border: 0.5px solid var(--c-separator); border-radius: var(--r-sm); overflow: hidden; }
.pg-btn {
    width: 34px; height: 32px;
    display: flex; align-items: center; justify-content: center;
    background: var(--c-surface); border: none; cursor: pointer;
    color: var(--t-primary); font-size: 12px;
    transition: background var(--dur-fast);
}
.pg-btn:hover:not(:disabled) { background: var(--c-fill); }
.pg-btn:disabled { opacity: 0.35; cursor: not-allowed; }
.pg-btn + .pg-btn { border-left: 0.5px solid var(--c-separator); }
.pg-current {
    padding: 0 14px; display: flex; align-items: center;
    font-size: 13px; font-weight: 600; color: var(--t-primary);
    background: var(--c-surface);
    border-left: 0.5px solid var(--c-separator);
    border-right: 0.5px solid var(--c-separator);
}

/* ---- Modal System ---- */
.modal-veil {
    position: fixed; inset: 0; z-index: 2000;
    background: rgba(0, 0, 0, 0.4);
    backdrop-filter: blur(10px);
    display: flex; align-items: center; justify-content: center;
    transition: opacity var(--dur-mid) var(--ease-ios);
}
.modal-veil.hidden { display: none; }
.sf-modal {
    background: var(--c-surface);
    border: 0.5px solid var(--c-separator);
    border-radius: var(--r-xl);
    box-shadow: var(--shadow-xl);
    width: 520px; max-width: 95vw;
    animation: sfModalSlide var(--dur-mid) var(--ease-spring);
    overflow: hidden;
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

/* ---- Tabs in Modal ---- */
.tabs { display: flex; border-bottom: 0.5px solid var(--c-separator); background: var(--c-surface2); padding: 0 24px; }
.tab-btn {
    padding: 12px 18px; border: none; background: transparent; cursor: pointer;
    font-size: 13px; font-weight: 600; color: var(--t-secondary);
    border-bottom: 2.5px solid transparent; transition: 0.18s;
}
.tab-btn:hover { color: var(--c-blue); }
.tab-btn.active { color: var(--c-blue); border-bottom-color: var(--c-blue); }
.tab-content { display: none; }
.tab-content.active { display: block; }

.data-table { width: 100%; border-collapse: collapse; margin-top: 8px; }
.data-table th, .data-table td { padding: 10px 12px; text-align: left; border-bottom: 0.5px solid var(--c-separator2); font-size: 13px; }
.data-table th { color: var(--t-label); font-weight: 600; font-size: 10.5px; text-transform: uppercase; background: var(--c-surface2); }
.num-col { text-align: right !important; }

.status-badge { padding: 3px 6px; border-radius: var(--r-xs); font-size: 9.5px; font-weight: bold; text-transform: uppercase; }
.status-Paid, .status-Cleared { background: var(--c-green-light); color: var(--c-green); }
.status-Unpaid, .status-Pending { background: var(--c-orange-light); color: var(--c-orange); }
.status-Bounced { background: var(--c-red-light); color: var(--c-red); }

.map-box { width: 100%; height: 230px; border-radius: var(--r-md); border: 0.5px solid var(--c-separator); background: var(--c-surface2); overflow: hidden; margin-top: 8px; }

/* ---- Drop-Zone ---- */
.drop-zone {
    border: 1.5px dashed var(--c-separator); padding: 28px; border-radius: var(--r-md);
    text-align: center; background: var(--c-surface2); position: relative; cursor: pointer;
    transition: background var(--dur-fast);
}
.drop-zone:hover { background: var(--c-fill); }
.drop-zone input[type="file"] { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
.drop-zone-icon { font-size: 28px; margin-bottom: 8px; color: var(--t-tertiary); }
.drop-zone-title { font-size: 13px; font-weight: 700; color: var(--t-primary); margin-bottom: 2px; }
.drop-zone-sub { font-size: 11px; color: var(--t-secondary); }

/* ---- Spin ---- */
@keyframes spin { to { transform: rotate(360deg); } }
.spin { animation: spin 0.7s linear infinite; display: inline-block; }

.hidden { display: none !important; }
.truncate-text { max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block; }
</style>

<div class="cust-root">
    <div class="cust-wrap">

        <!-- Stat Cards Row -->
        <div class="stat-row" style="margin-top: 10px;">
            <div class="stat-card blue">
                <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
                <div class="stat-info">
                    <div class="stat-num"><?= number_format($totalCustomers) ?></div>
                    <div class="stat-lbl">Total Customers</div>
                </div>
            </div>
            <div class="stat-card orange">
                <div class="stat-icon"><i class="fa-solid fa-wallet"></i></div>
                <div class="stat-info">
                    <div class="stat-num">Rs. <?= number_format($totalOutstanding, 2) ?></div>
                    <div class="stat-lbl">Total Outstanding</div>
                </div>
            </div>
            <div class="stat-card red">
                <div class="stat-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
                <div class="stat-info">
                    <div class="stat-num"><?= number_format($owedCustomersCount) ?></div>
                    <div class="stat-lbl">Owed Accounts</div>
                </div>
            </div>
        </div>

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

        <?php if ($importResults): ?>
            <div class="sf-alert success" id="import-alert" style="display:flex; flex-direction:column; gap:12px; border-left-color: var(--c-blue);">
                <div style="display:flex; align-items:flex-start; gap:12px; width:100%;">
                    <i class="fa-solid fa-circle-check sf-alert-icon" style="color:var(--c-blue);"></i>
                    <div style="flex:1;">
                        <div class="sf-alert-title">CSV Import Completed</div>
                        <ul style="margin: 4px 0 0 16px; padding: 0; display: flex; gap: 20px; list-style-type: disc; font-size:13px;">
                            <li>Added: <strong><?= intval($importResults['added']) ?></strong> customers</li>
                            <li>Updated: <strong><?= intval($importResults['updated']) ?></strong> customers</li>
                        </ul>
                    </div>
                    <button type="button" class="sf-alert-close" onclick="document.getElementById('import-alert').style.display='none'"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <?php if (!empty($importResults['success_logs'])): ?>
                    <div style="background:rgba(0,122,255,0.05); border-radius:var(--r-xs); padding:8px 12px; font-size:11px; max-height:80px; overflow-y:auto; border:0.5px solid rgba(0,122,255,0.1); width:100%;">
                        <?php foreach ($importResults['success_logs'] as $log): ?>
                            <div style="margin-bottom:2px; color:#0056b3;">✅ <?= htmlspecialchars($log) ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($importResults['errors'])): ?>
                    <div style="background:rgba(255,59,48,0.05); border-radius:var(--r-xs); padding:8px 12px; font-size:11px; max-height:80px; overflow-y:auto; border:0.5px solid rgba(255,59,48,0.1); width:100%;">
                        <?php foreach ($importResults['errors'] as $err): ?>
                            <div style="margin-bottom:2px; color:var(--c-red);">❌ <?= htmlspecialchars($err) ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Filters Block -->
        <div class="filter-shelf">
            <!-- Filter by Route -->
            <div class="filter-chip">
                <span class="filter-chip-label">Route</span>
                <div class="sf-dropdown" tabindex="0">
                    <div class="sf-dropdown-val" id="route-dropdown-val">All Routes</div>
                    <div class="sf-dropdown-menu">
                        <div class="sf-dropdown-item active" data-val="" onclick="selectRoute('', 'All Routes')">All Routes</div>
                        <?php 
                        $routes = [];
                        foreach($data['customers'] as $c) {
                            if(!empty($c->mca_name) && !in_array($c->mca_name, $routes)) { $routes[] = $c->mca_name; }
                        }
                        sort($routes);
                        foreach($routes as $r): ?>
                            <div class="sf-dropdown-item" data-val="<?= htmlspecialchars(strtolower($r), ENT_QUOTES, 'UTF-8') ?>" onclick="selectRoute('<?= htmlspecialchars(strtolower($r), ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars($r, ENT_QUOTES, 'UTF-8') ?>')"><?= htmlspecialchars($r) ?></div>
                        <?php endforeach; ?>
                    </div>
                    <input type="hidden" id="filterRoute" value="">
                </div>
            </div>

            <!-- Payment Status -->
            <div class="filter-chip">
                <span class="filter-chip-label">Ledger</span>
                <div class="sf-dropdown" tabindex="0">
                    <div class="sf-dropdown-val" id="status-dropdown-val">All Accounts</div>
                    <div class="sf-dropdown-menu">
                        <div class="sf-dropdown-item active" data-val="" onclick="selectStatus('', 'All Accounts')">All Accounts</div>
                        <div class="sf-dropdown-item" data-val="owed" onclick="selectStatus('owed', 'Has Unpaid Balance')">Has Unpaid Balance</div>
                        <div class="sf-dropdown-item" data-val="cleared" onclick="selectStatus('cleared', 'Zero Balance')">Zero Balance</div>
                    </div>
                    <input type="hidden" id="filterStatus" value="">
                </div>
            </div>

            <!-- Reset Button -->
            <button type="button" onclick="clearAllFilters()" class="filter-reset">Reset</button>

            <!-- Counter -->
            <div class="filter-count">
                <strong id="matching-count"><?= count($data['customers']) ?></strong> customers
            </div>
        </div>

        <!-- Table View -->
        <div class="table-panel">
            <table class="cust-table">
                <thead>
                    <tr>
                        <th>Customer / Company</th>
                        <th>Contact Details</th>
                        <th class="txt-right">Outstanding Balance</th>
                        <th>Status</th>
                        <th style="width:120px;" class="txt-center">Actions</th>
                    </tr>
                </thead>
                <tbody id="custList">
                    <?php if (empty($data['customers'])): ?>
                        <tr>
                            <td colspan="5" style="text-align:center; padding:40px; color:var(--t-secondary);">
                                <i class="fa-solid fa-building" style="font-size:28px; margin-bottom:8px; color:var(--t-tertiary);"></i><br>
                                No customers registered yet.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach($data['customers'] as $c): ?>
                            <?php 
                            $bal = floatval($c->outstanding_balance); 
                            $badgeCls = $bal > 0 ? 'badge-owed' : 'badge-active';
                            $badgeTxt = $bal > 0 ? 'Owed' : 'Cleared';
                            ?>
                            <tr class="customer-row" 
                                onclick="showCustomerProfile(<?= $c->id ?>)"
                                data-id="<?= $c->id ?>"
                                data-name="<?= htmlspecialchars(strtolower($c->name ?? '')) ?>"
                                data-phone="<?= htmlspecialchars(strtolower($c->phone ?? '')) ?>"
                                data-email="<?= htmlspecialchars(strtolower($c->email ?? '')) ?>"
                                data-route="<?= htmlspecialchars(strtolower($c->mca_name ?? '')) ?>"
                                data-address="<?= htmlspecialchars(strtolower($c->address ?? '')) ?>"
                                data-outstanding="<?= $bal ?>">
                                
                                <td>
                                    <div style="display:flex; align-items:center; gap:12px;">
                                        <div class="avatar-circle"><?= strtoupper(substr($c->name ?? '', 0, 2)) ?></div>
                                        <div>
                                            <strong style="font-size:14.5px; font-weight:600; color:var(--t-primary);"><?= htmlspecialchars($c->name ?? '') ?></strong>
                                            <span style="font-size:11px; color:var(--t-secondary); display:block; margin-top:2px;" class="truncate-text">
                                                🏠 <?= !empty($c->address) ? htmlspecialchars($c->address) : '<span style="color:var(--c-red); font-weight:600;">Missing Address</span>' ?>
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span style="font-size:13px; font-weight:500; display:block;">
                                        📞 <?= !empty($c->phone) ? htmlspecialchars($c->phone) : '<span style="color:var(--c-red); font-weight:600;">Missing Phone</span>' ?>
                                    </span>
                                    <?php if (!empty($c->email)): ?>
                                        <span style="font-size:11px; color:var(--t-secondary); display:block; margin-top:2px;">
                                            <?= htmlspecialchars($c->email) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="txt-right" style="font-weight:700; font-family:var(--f-mono); font-size:14px; color: <?= $bal > 0 ? 'var(--c-red)' : 'var(--c-green)' ?>;">
                                    Rs: <?= number_format($bal, 2) ?>
                                </td>
                                <td>
                                    <span class="sf-badge <?= $badgeCls ?>">
                                        <span class="dot"></span><?= $badgeTxt ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="display:flex; justify-content:center; gap:6px;" onclick="event.stopPropagation()">
                                        <button type="button" class="act-btn view" onclick="showCustomerProfile(<?= $c->id ?>)" title="View ledger & profile">
                                            <i class="fa-solid fa-eye"></i>
                                        </button>
                                        <button type="button" class="act-btn edit" onclick="sharePortal(<?= $c->id ?>, '<?= htmlspecialchars(addslashes($c->phone ?? '')) ?>', '<?= htmlspecialchars(addslashes($c->name ?? '')) ?>')" title="Share B2B Portal Link">
                                            <i class="fa-solid fa-share-nodes"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Pagination Block -->
            <div class="sf-pagination">
                <div class="pg-info" id="pg-info-text">
                    Showing <strong>1</strong> – <strong>15</strong> of <strong><?= count($data['customers']) ?></strong>
                </div>
                <div class="pg-right">
                    <div class="pg-size-wrap">
                        <span class="pg-size-lbl">Per page</span>
                        <select class="pg-size-sel native-select" onchange="updatePageSize(this.value)">
                            <option value="15" selected>15</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                            <option value="1000">All</option>
                        </select>
                    </div>
                    <div class="pg-nav" id="pg-nav-container">
                        <button type="button" class="pg-btn" id="pg-prev-btn" onclick="navigatePage(currentPage - 1)">
                            <i class="fa-solid fa-chevron-left"></i>
                        </button>
                        <div class="pg-current" id="pg-current-text">1 / 1</div>
                        <button type="button" class="pg-btn" id="pg-next-btn" onclick="navigatePage(currentPage + 1)">
                            <i class="fa-solid fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- ============================================================
     FLOATING COMMAND / SEARCH BAR (DYNAMIC ISLAND)
     ============================================================ -->
<div class="cmd-bar">
    <div class="cmd-search">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" id="searchInput" placeholder="Search customers..." onkeyup="filterList()">
    </div>
    <div class="cmd-divider"></div>
    <a href="<?= APP_URL ?>/customer/exportCSV" class="cmd-icon" title="Export CSV"><i class="fa-solid fa-download"></i></a>
    <button type="button" class="cmd-icon" onclick="openModal('csvImportModal')" title="Import CSV"><i class="fa-solid fa-upload"></i></button>
    <button type="button" class="cmd-cta" onclick="openModal('addCustomerModal')"><i class="fa-solid fa-plus" style="font-size:13px;"></i> New</button>
</div>

<!-- ============================================================
     POPUP MODAL: CUSTOMER PROFILE LEDGER & DETAILS
     ============================================================ -->
<div id="customerProfileModal" class="modal-veil hidden" onclick="if(event.target === this) closeCustomerProfile()">
    <div class="sf-modal" style="width: 85%; max-width: 1000px; height: 85vh; display: flex; flex-direction: column; padding: 0; overflow: hidden; border-radius: var(--r-lg);">
        
        <!-- Modal Head Container -->
        <div class="modal-head" id="modal-header-container" style="padding: 18px 24px; border-bottom: 0.5px solid var(--c-separator); background: var(--c-surface2);">
            <div style="display: flex; gap: 15px; align-items: center;">
                <div class="avatar-circle" style="width: 40px; height: 40px; font-size: 16px;">C</div>
                <div>
                    <h3 class="modal-title" style="font-size: 18px; font-weight: 700;">Customer Details</h3>
                </div>
            </div>
            <button type="button" onclick="closeCustomerProfile()" class="modal-close" style="width:30px; height:30px;"><i class="fa-solid fa-xmark"></i></button>
        </div>
        
        <!-- Modal Loader -->
        <div id="modal-loader" style="display:none; flex:1; align-items:center; justify-content:center; flex-direction:column; gap:12px; background:var(--c-surface);">
            <i class="fa-solid fa-spinner spin" style="font-size:32px; color:var(--c-blue);"></i>
            <span style="font-size:14px; color:var(--t-secondary); font-weight:500;">Loading customer profile...</span>
        </div>
        
        <!-- Modal Content Container -->
        <div id="modal-profile-content" style="flex:1; display:flex; flex-direction:column; overflow:hidden;">
            <!-- Content will load dynamically here -->
        </div>

    </div>
</div>

<!-- ============================================================
     HIDDEN TEMPLATE SOURCES (EXTRACTED VIA DOM PARSER OR DIRECT)
     ============================================================ -->
<?php if ($data['selected_customer']): ?>
    <?php $c = $data['selected_customer']; $s = $data['stats']; ?>
    
    <div id="modal-header-source" class="hidden">
        <div style="display: flex; gap: 15px; align-items: center; min-width: 0; flex: 1;">
            <div class="avatar-circle" style="width: 40px; height: 40px; font-size: 16px; background: var(--c-blue-light); color: var(--c-blue); flex-shrink: 0;">
                <?= strtoupper(substr($c->name ?? '', 0, 2)) ?>
            </div>
            <div style="min-width: 0; max-width: 250px;">
                <h3 class="modal-title" style="font-size: 16.5px; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin: 0;" title="<?= htmlspecialchars($c->name ?? '') ?>">
                    <?= htmlspecialchars($c->name ?? '') ?>
                </h3>
                <div style="font-size: 11px; color: var(--t-secondary); display: flex; gap: 10px; margin-top: 2px; white-space: nowrap; overflow: hidden;">
                    <span>📞 <?= !empty($c->phone) ? htmlspecialchars($c->phone) : '<span style="color:var(--c-red); font-weight:600;">Missing</span>' ?></span>
                    <span>✉️ <?= !empty($c->email) ? htmlspecialchars($c->email) : '<span style="color:var(--c-red); font-weight:600;">Missing</span>' ?></span>
                    <span>📍 <?= !empty($c->mca_name) ? htmlspecialchars($c->mca_name) : '<span style="color:var(--c-red); font-weight:600;">Unassigned</span>' ?></span>
                </div>
            </div>
        </div>
        
        <!-- Header Statistics Cards -->
        <div style="display: flex; gap: 8px; margin-right: 18px; flex-shrink: 0; align-items: center;">
            <div style="background: var(--c-fill); padding: 5px 10px; border-radius: var(--r-sm); text-align: center; min-width: 70px;">
                <div style="font-size: 8.5px; color: var(--t-label); text-transform: uppercase; font-weight: 700; letter-spacing: 0.04em;">Orders</div>
                <div style="font-size: 13px; font-weight: bold; color: var(--t-primary); margin-top: 1px;"><?= $s->total_orders ?></div>
            </div>
            <div style="background: var(--c-fill); padding: 5px 10px; border-radius: var(--r-sm); text-align: center; min-width: 100px;">
                <div style="font-size: 8.5px; color: var(--t-label); text-transform: uppercase; font-weight: 700; letter-spacing: 0.04em;">Total Billed</div>
                <div style="font-size: 13px; font-weight: bold; color: var(--t-primary); margin-top: 1px; font-family: var(--f-mono);">Rs: <?= number_format($s->total_billed, 2) ?></div>
            </div>
            <div style="background: var(--c-green-light); padding: 5px 10px; border-radius: var(--r-sm); border: 0.5px solid rgba(52,199,89,0.2); text-align: center; min-width: 100px;">
                <div style="font-size: 8.5px; color: var(--c-green); text-transform: uppercase; font-weight: 700; letter-spacing: 0.04em;">Total Paid</div>
                <div style="font-size: 13px; font-weight: bold; color: var(--c-green); margin-top: 1px; font-family: var(--f-mono);">Rs: <?= number_format($s->total_paid, 2) ?></div>
            </div>
            <div style="background: <?= $s->outstanding > 0 ? 'var(--c-red-light)' : 'var(--c-green-light)' ?>; padding: 5px 10px; border-radius: var(--r-sm); border: 0.5px solid <?= $s->outstanding > 0 ? 'rgba(255,59,48,0.2)' : 'rgba(52,199,89,0.2)' ?>; text-align: center; min-width: 100px;">
                <div style="font-size: 8.5px; color: <?= $s->outstanding > 0 ? 'var(--c-red)' : 'var(--c-green)' ?>; text-transform: uppercase; font-weight: 700; letter-spacing: 0.04em;">Outstanding</div>
                <div style="font-size: 13px; font-weight: bold; color: <?= $s->outstanding > 0 ? 'var(--c-red)' : 'var(--c-green)' ?>; margin-top: 1px; font-family: var(--f-mono);">Rs: <?= number_format($s->outstanding, 2) ?></div>
            </div>
        </div>

        <button type="button" onclick="closeCustomerProfile()" class="modal-close" style="width:30px; height:30px; flex-shrink: 0;"><i class="fa-solid fa-xmark"></i></button>
    </div>

    <div id="modal-profile-content-source" class="hidden">
        <!-- Tab Navigation -->
        <div class="tabs">
            <button class="tab-btn active" onclick="switchModalTab('ledger')" id="mbtn_ledger">Activity Ledger</button>
            <button class="tab-btn" onclick="switchModalTab('invoices')" id="mbtn_invoices">Invoices</button>
            <button class="tab-btn" onclick="switchModalTab('cheques')" id="mbtn_cheques">Cheques (PDC)</button>
            <button class="tab-btn" onclick="switchModalTab('profile')" id="mbtn_profile">Profile</button>
        </div>

        <!-- TAB 1: Ledger -->
        <div id="mtab_ledger" class="tab-content active" style="padding: 22px; overflow-y: auto; flex: 1;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Description</th>
                        <th class="num-col">Debit (Dr)</th>
                        <th class="num-col">Credit (Cr)</th>
                        <th class="num-col">Running Balance</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($data['ledger'])): ?>
                        <tr><td colspan="5" style="text-align: center; color: var(--t-secondary); padding: 20px;">No financial activity yet.</td></tr>
                    <?php else: foreach($data['ledger'] as $l): ?>
                        <tr>
                            <td style="color:var(--t-secondary); font-size:12px;"><?= date('M d, Y', strtotime($l->date)) ?></td>
                            <td>
                                <strong><?= $l->type ?></strong>
                                <?php if($l->type == 'Invoice'): ?>
                                    <a href="<?= APP_URL ?>/sales/show/<?= $l->id ?>" target="_blank" style="color:var(--c-blue); font-size: 11px; margin-left: 5px; font-weight:bold; text-decoration:none;">
                                        <?= htmlspecialchars($l->ref) ?> ↗
                                    </a>
                                <?php elseif($l->type == 'Credit Note'): ?>
                                    <a href="<?= APP_URL ?>/creditnote/show/<?= $l->id ?>" target="_blank" style="color:var(--c-red); font-size: 11px; margin-left: 5px; font-weight:bold; text-decoration:none;">
                                        <?= htmlspecialchars($l->ref) ?> ↗
                                    </a>
                                <?php else: ?>
                                    <span style="color:var(--t-secondary); font-size: 11px; margin-left: 5px;"><?= htmlspecialchars($l->ref) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="num-col" style="color:var(--t-primary); font-weight:500; font-family:var(--f-mono);"><?= $l->debit > 0 ? 'Rs: ' . number_format($l->debit, 2) : '-' ?></td>
                            <td class="num-col" style="color:var(--c-green); font-weight:500; font-family:var(--f-mono);"><?= $l->credit > 0 ? 'Rs: ' . number_format($l->credit, 2) : '-' ?></td>
                            <td class="num-col" style="font-weight:bold; font-family:var(--f-mono); color: <?= $l->balance > 0 ? 'var(--c-red)' : 'var(--c-green)' ?>;">Rs: <?= number_format($l->balance, 2) ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <!-- TAB 2: Invoices -->
        <div id="mtab_invoices" class="tab-content" style="padding: 22px; overflow-y: auto; flex: 1;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Date</th>
                        <th class="num-col">Grand Total</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($data['invoices'])): ?>
                        <tr><td colspan="4" style="text-align:center; color:var(--t-secondary); padding: 20px;">No invoices found.</td></tr>
                    <?php else: foreach($data['invoices'] as $inv): ?>
                        <?php 
                            $trueInvTotal = $inv->total_amount;
                            if($inv->global_discount_val > 0) {
                                $trueInvTotal -= ($inv->global_discount_type == '%' ? ($inv->total_amount * $inv->global_discount_val / 100) : $inv->global_discount_val);
                            }
                            $trueInvTotal += $inv->tax_amount;
                        ?>
                        <tr>
                            <td><a href="<?= APP_URL ?>/sales/show/<?= $inv->id ?>" target="_blank" style="color:var(--c-blue); font-weight:bold; text-decoration:none;"><?= $inv->invoice_number ?></a></td>
                            <td style="color:var(--t-secondary); font-size:12px;"><?= date('M d, Y', strtotime($inv->invoice_date)) ?></td>
                            <td class="num-col" style="font-weight:bold; font-family:var(--f-mono);">Rs: <?= number_format($trueInvTotal, 2) ?></td>
                            <td><span class="status-badge status-<?= $inv->status ?>"><?= $inv->status ?></span></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <!-- TAB 3: Cheques -->
        <div id="mtab_cheques" class="tab-content" style="padding: 22px; overflow-y: auto; flex: 1;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Bank & Date</th>
                        <th class="num-col">Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($data['cheques'])): ?>
                        <tr><td colspan="3" style="text-align:center; color:var(--t-secondary); padding: 20px;">No cheques recorded.</td></tr>
                    <?php else: foreach($data['cheques'] as $chk): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($chk->bank_name) ?></strong><br>
                                <span style="font-size:11px; color:var(--t-secondary);"><?= date('M d, Y', strtotime($chk->banking_date)) ?></span>
                            </td>
                            <td class="num-col" style="font-weight:bold; font-family:var(--f-mono);">Rs: <?= number_format($chk->amount, 2) ?></td>
                            <td><span class="status-badge status-<?= $chk->status ?>"><?= $chk->status ?></span></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <!-- TAB 4: Profile & Map -->
        <div id="mtab_profile" class="tab-content" style="padding: 22px; overflow-y: auto; flex: 1;">
            <div class="grid-2">
                <div>
                    <h4 style="margin:0 0 14px 0; border-bottom: 0.5px solid var(--c-separator); padding-bottom: 6px; font-size:14px; font-weight:700; text-transform:uppercase; color:var(--t-secondary);">Edit Customer Profile</h4>
                    
                    <form action="<?= APP_URL ?>/customer/index/<?= $c->id ?>" method="POST">
                        <input type="hidden" name="action" value="update_customer">
                        <input type="hidden" name="customer_id" value="<?= $c->id ?>">
                        
                        <div class="sf-group">
                            <label>Customer Name *</label>
                            <input type="text" name="name" class="sf-input" value="<?= htmlspecialchars($c->name ?? '') ?>" required>
                        </div>
                        
                        <div class="grid-2">
                            <div class="sf-group">
                                <label>Email Address</label>
                                <input type="email" name="email" class="sf-input" value="<?= htmlspecialchars($c->email ?? '') ?>">
                            </div>
                            <div class="sf-group">
                                <label>Phone Number</label>
                                <input type="text" name="phone" class="sf-input" value="<?= htmlspecialchars($c->phone ?? '') ?>">
                            </div>
                        </div>
                        
                        <div class="grid-2">
                            <div class="sf-group">
                                <label>WhatsApp Number</label>
                                <input type="text" name="whatsapp" class="sf-input" value="<?= htmlspecialchars($c->whatsapp ?? '') ?>">
                            </div>
                            <div class="sf-group">
                                <label>Assigned Route</label>
                                <select name="mca_id" class="sf-input" style="height:41px;">
                                    <option value="">Unassigned</option>
                                    <?php foreach($data['mca_areas'] as $route): ?>
                                        <option value="<?= $route->id ?>" <?= $c->mca_id == $route->id ? 'selected' : '' ?>><?= htmlspecialchars($route->name ?? '') ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="grid-2">
                            <div class="sf-group">
                                <label>Opening Balance (Rs)</label>
                                <input type="number" step="0.01" name="opening_balance" class="sf-input" value="<?= htmlspecialchars($c->opening_balance ?? '0.00') ?>">
                            </div>
                            <div class="sf-group">
                                <label>Credit Limit (Rs)</label>
                                <input type="number" step="0.01" name="credit_limit" class="sf-input" value="<?= htmlspecialchars($c->credit_limit ?? '0.00') ?>">
                            </div>
                        </div>
                        
                        <div class="sf-group">
                            <label>Billing Address</label>
                            <textarea name="address" class="sf-input" rows="2" style="resize:vertical; min-height:50px;"><?= htmlspecialchars($c->address ?? '') ?></textarea>
                        </div>
                        
                        <div class="grid-2">
                            <div class="sf-group">
                                <label>Latitude (GPS)</label>
                                <input type="text" name="latitude" class="sf-input" value="<?= htmlspecialchars($c->latitude ?? '') ?>">
                            </div>
                            <div class="sf-group">
                                <label>Longitude (GPS)</label>
                                <input type="text" name="longitude" class="sf-input" value="<?= htmlspecialchars($c->longitude ?? '') ?>">
                            </div>
                        </div>
                        
                        <div style="text-align: right; margin-top: 10px;">
                            <button type="submit" class="sf-btn primary">Save Changes</button>
                        </div>
                    </form>
                </div>

                <!-- GPS Map View -->
                <div>
                    <h4 style="margin:0 0 14px 0; border-bottom: 0.5px solid var(--c-separator); padding-bottom: 6px; font-size:14px; font-weight:700; text-transform:uppercase; color:var(--t-secondary);">Map Location</h4>
                    <div class="map-box">
                        <?php if($c->latitude && $c->longitude): ?>
                            <iframe width="100%" height="100%" frameborder="0" style="border:0;" src="https://maps.google.com/maps?q=<?= $c->latitude ?>,<?= $c->longitude ?>&hl=en&z=14&output=embed"></iframe>
                        <?php else: ?>
                            <div style="display:flex; height:100%; align-items:center; justify-content:center; color:var(--t-tertiary); font-size:13px; flex-direction:column; gap:6px;">
                                <i class="fa-solid fa-map-location-dot" style="font-size: 28px;"></i>
                                No GPS coordinates registered.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Danger Zone: Delete Customer -->
            <div style="margin-top: 35px; padding-top: 20px; border-top: 1px dashed var(--c-separator);">
                <div style="background: rgba(255, 59, 48, 0.04); border: 0.5px solid rgba(255, 59, 48, 0.15); border-radius: var(--r-md); padding: 18px 22px; display: flex; justify-content: space-between; align-items: center; gap: 20px; flex-wrap: wrap;">
                    <div style="min-width: 250px; flex: 1;">
                        <h4 style="margin: 0; font-size: 13.5px; font-weight: 700; color: var(--c-red); display: flex; align-items: center; gap: 8px;">
                            <i class="fa-solid fa-triangle-exclamation"></i> Danger Zone
                        </h4>
                        <p style="margin: 4px 0 0 0; font-size: 12px; color: var(--t-secondary); line-height: 1.4;">
                            Permanently delete this customer record. This action cannot be undone and will fail if they have transaction records.
                        </p>
                    </div>
                    <div>
                        <button type="button" class="sf-btn danger" onclick="confirmDeleteCustomer(<?= $c->id ?>, '<?= htmlspecialchars(addslashes($c->name ?? '')) ?>')" style="padding: 8px 16px; font-weight: 700; display: flex; align-items: center; gap: 6px;">
                            <i class="fa-solid fa-trash-can"></i> Delete Customer
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </div>
<?php endif; ?>

<!-- ============================================================
     MODAL: CONFIRM CUSTOMER DELETE (WITH PASSWORD VERIFICATION)
     ============================================================ -->
<div class="modal-veil hidden" id="deleteCustomerModal" onclick="if(event.target === this) closeModal('deleteCustomerModal')">
    <div class="sf-modal" style="width: 400px;">
        <div class="modal-head" style="border-bottom: none; padding-bottom: 0;">
            <h3 class="modal-title" style="color: var(--c-red); display: flex; align-items: center; gap: 8px; font-size: 16px;">
                <i class="fa-solid fa-triangle-exclamation"></i> Delete Customer
            </h3>
            <button type="button" class="modal-close" onclick="closeModal('deleteCustomerModal')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form id="deleteCustomerForm" onsubmit="submitDeleteCustomer(event)">
            <div class="modal-body" style="padding-top: 12px; padding-bottom: 12px;">
                <p style="font-size: 13px; color: var(--t-secondary); line-height: 1.5; margin: 0 0 16px 0;">
                    Are you sure you want to delete <strong id="delete-customer-name" style="color: var(--t-primary);"></strong>?<br>
                    Please enter your logged-in user password to confirm this action:
                </p>
                <input type="hidden" id="delete-customer-id" value="">
                <div class="sf-group" style="margin: 0;">
                    <label>Your Account Password *</label>
                    <input type="password" id="delete-confirm-password" class="sf-input" placeholder="Enter your password" required autocomplete="current-password">
                    <div id="delete-error-msg" style="color: var(--c-red); font-size: 11.5px; font-weight: 600; margin-top: 6px; display: none;"></div>
                </div>
            </div>
            <div class="modal-foot" style="border-top: none; padding-top: 0; display: flex; justify-content: flex-end; gap: 10px; padding-bottom: 24px; background: transparent;">
                <button type="button" class="sf-btn neutral" onclick="closeModal('deleteCustomerModal')">Cancel</button>
                <button type="submit" class="sf-btn danger" id="delete-confirm-btn" style="display: flex; align-items: center; gap: 6px; font-weight: 700;">
                    Confirm Delete
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================
     MODAL: REGISTER NEW CUSTOMER
     ============================================================ -->
<div class="modal-veil hidden" id="addCustomerModal" onclick="if(event.target === this) closeModal('addCustomerModal')">
    <div class="sf-modal">
        <div class="modal-head">
            <h3 class="modal-title" style="color:var(--c-blue);"><i class="fa-solid fa-user-plus" style="margin-right:6px;"></i>Add New Customer</h3>
            <button type="button" class="modal-close" onclick="closeModal('addCustomerModal')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form action="<?= APP_URL ?>/customer/index" method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" value="add_customer">
                
                <div class="sf-group">
                    <label>Customer/Company Name *</label>
                    <input type="text" name="name" class="sf-input" placeholder="e.g. Acme Corporation" required>
                </div>
                
                <div class="grid-2">
                    <div class="sf-group"><label>Email Address</label><input type="email" name="email" class="sf-input" placeholder="e.g. acme@example.com"></div>
                    <div class="sf-group"><label>Phone Number</label><input type="text" name="phone" class="sf-input" placeholder="e.g. +94771234567"></div>
                </div>
                
                <div class="grid-2">
                    <div class="sf-group"><label>WhatsApp Number</label><input type="text" name="whatsapp" class="sf-input" placeholder="e.g. +94771234567"></div>
                    <div class="sf-group">
                        <label>Assign Route</label>
                        <select name="mca_id" class="sf-input" style="height:41px;">
                            <option value="">Unassigned</option>
                            <?php foreach($data['mca_areas'] as $route): ?>
                                <option value="<?= $route->id ?>"><?= htmlspecialchars($route->name ?? '') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="sf-group">
                        <label>Opening Balance (Rs)</label>
                        <input type="number" step="0.01" name="opening_balance" class="sf-input" placeholder="0.00" value="0.00">
                    </div>
                    <div class="sf-group">
                        <label>Credit Limit (Rs)</label>
                        <input type="number" step="0.01" name="credit_limit" class="sf-input" placeholder="0.00" value="0.00">
                    </div>
                </div>
                
                <div class="sf-group">
                    <label>Billing Address</label>
                    <textarea name="address" class="sf-input" rows="2" placeholder="e.g. 123 Main Street, Colombo" style="resize:vertical; min-height:50px;"></textarea>
                </div>
                
                <div class="grid-2">
                    <div class="sf-group"><label>Latitude (GPS)</label><input type="text" name="latitude" class="sf-input" placeholder="Optional"></div>
                    <div class="sf-group"><label>Longitude (GPS)</label><input type="text" name="longitude" class="sf-input" placeholder="Optional"></div>
                </div>
            </div>
            <div class="modal-foot">
                <button type="button" class="sf-btn neutral" onclick="closeModal('addCustomerModal')">Cancel</button>
                <button type="submit" class="sf-btn primary">Save & Register</button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================
     MODAL: CSV IMPORT
     ============================================================ -->
<div class="modal-veil hidden" id="csvImportModal" onclick="if(event.target === this) closeModal('csvImportModal')">
    <div class="sf-modal" style="width:480px;">
        <div class="modal-head">
            <h3 class="modal-title" style="color:var(--c-green);"><i class="fa-solid fa-file-csv" style="margin-right:6px;"></i>Import Customers (CSV)</h3>
            <button type="button" class="modal-close" onclick="closeModal('csvImportModal')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form action="<?= APP_URL ?>/customer/importCSV" method="POST" enctype="multipart/form-data">
            <div class="modal-body">
                <p style="font-size: 12px; color: var(--t-secondary); margin-top:-10px; margin-bottom: 20px; line-height:1.5;">
                    Select a CSV file containing customer details. Required column: <strong>Name</strong>.<br>
                    Supported columns: <strong>Name, Email, Phone, WhatsApp, Address, Latitude, Longitude, Route/Territory, Opening Balance</strong>.
                </p>
                <div class="drop-zone">
                    <input type="file" name="csv_file" accept=".csv" required>
                    <div class="drop-zone-icon"><i class="fa-solid fa-file-csv"></i></div>
                    <div class="drop-zone-title">Choose a CSV file</div>
                    <div class="drop-zone-sub">or drag and drop it here</div>
                </div>
            </div>
            <div class="modal-foot">
                <button type="button" class="sf-btn neutral" onclick="closeModal('csvImportModal')">Cancel</button>
                <button type="submit" class="sf-btn success">Import Customers</button>
            </div>
        </form>
    </div>
</div>

<script>
    // --- Pagination State ---
    let currentPage = 1;
    let pageSize = 15;
    let matchingRows = [];

    // --- Custom Dropdown Handlers ---
    function selectRoute(val, label) {
        document.getElementById('filterRoute').value = val;
        document.getElementById('route-dropdown-val').textContent = label;
        
        // Update active classes
        const dropdown = document.getElementById('route-dropdown-val').closest('.sf-dropdown');
        dropdown.querySelectorAll('.sf-dropdown-item').forEach(item => {
            if (item.getAttribute('data-val') === val) {
                item.classList.add('active');
            } else {
                item.classList.remove('active');
            }
        });
        
        document.activeElement.blur();
        currentPage = 1;
        filterList();
    }

    function selectStatus(val, label) {
        document.getElementById('filterStatus').value = val;
        document.getElementById('status-dropdown-val').textContent = label;
        
        // Update active classes
        const dropdown = document.getElementById('status-dropdown-val').closest('.sf-dropdown');
        dropdown.querySelectorAll('.sf-dropdown-item').forEach(item => {
            if (item.getAttribute('data-val') === val) {
                item.classList.add('active');
            } else {
                item.classList.remove('active');
            }
        });
        
        document.activeElement.blur();
        currentPage = 1;
        filterList();
    }

    // --- Search & Filter Logic ---
    function filterList() {
        const query = document.getElementById('searchInput').value.toLowerCase().trim();
        const routeFilter = document.getElementById('filterRoute').value.toLowerCase().trim();
        const statusFilter = document.getElementById('filterStatus').value;
        
        const rows = document.querySelectorAll('.customer-row');
        matchingRows = [];
        
        rows.forEach(row => {
            const name = row.getAttribute('data-name');
            const phone = row.getAttribute('data-phone');
            const email = row.getAttribute('data-email');
            const route = row.getAttribute('data-route');
            const address = row.getAttribute('data-address');
            const outstanding = parseFloat(row.getAttribute('data-outstanding'));
            
            const matchesSearch = query === '' || 
                                  name.includes(query) || 
                                  phone.includes(query) || 
                                  email.includes(query) || 
                                  route.includes(query) || 
                                  address.includes(query);
                                  
            const matchesRoute = routeFilter === '' || route === routeFilter;
            
            let matchesStatus = true;
            if (statusFilter === 'owed') {
                matchesStatus = outstanding > 0;
            } else if (statusFilter === 'cleared') {
                matchesStatus = outstanding <= 0;
            }
            
            if (matchesSearch && matchesRoute && matchesStatus) {
                matchingRows.push(row);
            } else {
                row.style.display = 'none';
            }
        });
        
        document.getElementById('matching-count').textContent = matchingRows.length;

        // Reset current page if it is out of bounds
        const totalPages = Math.ceil(matchingRows.length / pageSize) || 1;
        if (currentPage > totalPages) {
            currentPage = totalPages;
        }

        updatePaginationUI();
    }

    // --- Pagination rendering ---
    function updatePaginationUI() {
        const totalItems = matchingRows.length;
        const totalPages = Math.ceil(totalItems / pageSize) || 1;
        
        const startIndex = totalItems > 0 ? (currentPage - 1) * pageSize + 1 : 0;
        const endIndex = Math.min(currentPage * pageSize, totalItems);
        
        // Update pagination info text
        document.getElementById('pg-info-text').innerHTML = totalItems > 0 
            ? `Showing <strong>${startIndex}</strong> – <strong>${endIndex}</strong> of <strong>${totalItems}</strong>` 
            : 'No results';
            
        // Show/hide pagination nav container
        const pgNav = document.getElementById('pg-nav-container');
        if (totalPages > 1) {
            pgNav.classList.remove('hidden');
            document.getElementById('pg-current-text').textContent = `${currentPage} / ${totalPages}`;
            document.getElementById('pg-prev-btn').disabled = currentPage <= 1;
            document.getElementById('pg-next-btn').disabled = currentPage >= totalPages;
        } else {
            pgNav.classList.add('hidden');
        }
        
        // Hide all rows first, then show only page slice
        const rows = document.querySelectorAll('.customer-row');
        rows.forEach(r => r.style.display = 'none');
        
        const pageRows = matchingRows.slice((currentPage - 1) * pageSize, currentPage * pageSize);
        pageRows.forEach(r => r.style.display = '');
    }

    function navigatePage(pageNum) {
        currentPage = pageNum;
        updatePaginationUI();
    }

    function updatePageSize(size) {
        pageSize = parseInt(size);
        currentPage = 1;
        filterList();
    }

    function clearAllFilters() {
        document.getElementById('searchInput').value = '';
        selectRoute('', 'All Routes');
        selectStatus('', 'All Accounts');
    }

    // --- Modal Control Helper functions ---
    function openModal(id) { document.getElementById(id).classList.remove('hidden'); }
    function closeModal(id) { document.getElementById(id).classList.add('hidden'); }

    // --- Customer Profile Popup Modal Handlers ---
    function showCustomerProfile(id, tab = null) {
        const modal = document.getElementById('customerProfileModal');
        const loader = document.getElementById('modal-loader');
        const content = document.getElementById('modal-profile-content');
        
        modal.classList.remove('hidden');
        loader.style.display = 'flex';
        content.style.display = 'none';
        
        // Update URL
        let targetUrl = '<?= APP_URL ?>/customer/index/' + id;
        if (tab) {
            targetUrl += '?tab=' + tab;
        }
        window.history.pushState({ path: targetUrl }, '', targetUrl);
        
        fetch(targetUrl)
            .then(response => {
                if (!response.ok) throw new Error('Failed to load profile');
                return response.text();
            })
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                
                const newContent = doc.getElementById('modal-profile-content-source');
                const newHeader = doc.getElementById('modal-header-container');
                
                if (newContent && newHeader) {
                    document.getElementById('modal-header-container').innerHTML = newHeader.innerHTML;
                    content.innerHTML = newContent.innerHTML;
                    
                    loader.style.display = 'none';
                    content.style.display = 'flex';

                    if (tab) {
                        switchModalTab(tab);
                    }
                } else {
                    throw new Error('Malformed content returned from server');
                }
            })
            .catch(err => {
                console.error(err);
                content.innerHTML = `
                    <div style="padding:40px; text-align:center; color:var(--c-red);">
                        <i class="fa-solid fa-triangle-exclamation" style="font-size:32px; margin-bottom:12px;"></i><br>
                        Failed to load customer profile details.
                    </div>`;
                loader.style.display = 'none';
                content.style.display = 'block';
            });
    }

    function openPrepopulatedCustomerProfile(id) {
        const modal = document.getElementById('customerProfileModal');
        const content = document.getElementById('modal-profile-content');
        
        const headerSrc = document.getElementById('modal-header-source');
        const contentSrc = document.getElementById('modal-profile-content-source');
        
        if (headerSrc && contentSrc) {
            document.getElementById('modal-header-container').innerHTML = headerSrc.innerHTML;
            content.innerHTML = contentSrc.innerHTML;
            modal.classList.remove('hidden');

            const urlParams = new URLSearchParams(window.location.search);
            const tab = urlParams.get('tab');
            if (tab) {
                switchModalTab(tab);
            }
        }
    }

    function closeCustomerProfile() {
        document.getElementById('customerProfileModal').classList.add('hidden');
        const targetUrl = '<?= APP_URL ?>/customer/index';
        window.history.pushState({ path: targetUrl }, '', targetUrl);
    }

    // --- Tab Switching inside Profile Modal ---
    function switchModalTab(tabName) {
        const content = document.getElementById('modal-profile-content');
        content.querySelectorAll('.tab-content').forEach(el => el.style.display = 'none');
        content.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
        
        const activeTab = content.querySelector('#mtab_' + tabName);
        const activeBtn = content.querySelector('#mbtn_' + tabName);
        
        if (activeTab) activeTab.style.display = 'block';
        if (activeBtn) activeBtn.classList.add('active');
    }

    // --- Share Portal Helper ---
    function sharePortal(id, phone, name) {
        const encodedId = btoa(id);
        const portalLink = "<?= APP_URL ?>/portal/show/" + encodedId;
        const msg = `Hello ${name},\n\nYou can view your live account statement, outstanding balances, and download past invoices on our B2B Customer Portal here:\n\n${portalLink}\n\nThank you!`;
        
        if (phone && phone.trim() !== '') {
            let cleanPhone = phone.replace(/[^\d+]/g, '');
            if(cleanPhone.startsWith('0')) cleanPhone = '94' + cleanPhone.substring(1); 
            const waUrl = `https://wa.me/${cleanPhone}?text=${encodeURIComponent(msg)}`;
            window.open(waUrl, '_blank');
        } else {
            prompt("Customer has no phone number saved. Copy the link below to share manually:", portalLink);
        }
    }

    // --- Customer Delete Flow Scripts ---
    let activeDeleteCustomerId = null;

    function confirmDeleteCustomer(id, name) {
        activeDeleteCustomerId = id;
        document.getElementById('delete-customer-id').value = id;
        document.getElementById('delete-customer-name').textContent = name;
        document.getElementById('delete-confirm-password').value = '';
        document.getElementById('delete-error-msg').style.display = 'none';
        document.getElementById('delete-error-msg').textContent = '';
        openModal('deleteCustomerModal');
    }

    function submitDeleteCustomer(e) {
        e.preventDefault();
        const pwdInput = document.getElementById('delete-confirm-password');
        const errorMsg = document.getElementById('delete-error-msg');
        const submitBtn = document.getElementById('delete-confirm-btn');
        
        if (!pwdInput.value.trim()) {
            errorMsg.textContent = 'Password is required.';
            errorMsg.style.display = 'block';
            return;
        }

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Deleting...';
        errorMsg.style.display = 'none';

        const fd = new FormData();
        fd.append('password', pwdInput.value);

        fetch('<?= APP_URL ?>/customer/delete/' + activeDeleteCustomerId, {
            method: 'POST',
            body: fd
        })
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(data => {
            if (data.success) {
                window.location.href = '<?= APP_URL ?>/customer/index';
            } else {
                errorMsg.textContent = data.error || 'Failed to delete customer.';
                errorMsg.style.display = 'block';
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Confirm Delete';
            }
        })
        .catch(err => {
            console.error(err);
            errorMsg.textContent = 'A connection error occurred. Please try again.';
            errorMsg.style.display = 'block';
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Confirm Delete';
        });
    }

    // Handle initial state if selected customer exists on page load
    <?php if ($data['selected_customer']): ?>
    document.addEventListener('DOMContentLoaded', () => {
        openPrepopulatedCustomerProfile(<?= $data['selected_customer']->id ?>);
        filterList();
    });
    <?php else: ?>
    document.addEventListener('DOMContentLoaded', () => {
        filterList();
    });
    <?php endif; ?>
</script>