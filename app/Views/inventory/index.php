<?php
// ==========================================
// VARIABLE SAFETY & ROBUST FALLBACK ENGINE
// ==========================================

$items = $data['items'] ?? [];

$stats = $data['stats'] ?? (object)[
    'total_items' => count($items),
    'low_stock_count' => 0,
    'out_of_stock_count' => 0
];

$filters = $data['filters'] ?? [];
$filters['search'] = $filters['search'] ?? '';
$filters['min_price'] = $filters['min_price'] ?? '';
$filters['max_price'] = $filters['max_price'] ?? '';
$filters['stock_status'] = $filters['stock_status'] ?? '';
$filters['category_id'] = $filters['category_id'] ?? '';
$filters['status'] = $filters['status'] ?? '';
$categories = $data['categories'] ?? [];
$isCurrentlyAdmin = (strtolower($_SESSION['role'] ?? '') === 'admin');

$pagination = $data['pagination'] ?? [
    'current_page' => 1,
    'per_page' => 1000,
    'total_items' => count($items),
    'total_pages' => 1
];

$currentPage = (int)$pagination['current_page'];
$perPage = (int)$pagination['per_page'];
$totalItems = (int)$pagination['total_items'];
$totalPages = (int)$pagination['total_pages'];

$startIndex = $totalItems > 0 ? (($currentPage - 1) * $perPage) + 1 : 0;
$endIndex = min($currentPage * $perPage, $totalItems);

$flashSuccess = $_SESSION['flash_success'] ?? null;
if ($flashSuccess) unset($_SESSION['flash_success']);
$flashError = $_SESSION['flash_error'] ?? null;
if ($flashError) unset($_SESSION['flash_error']);
$importResults = $_SESSION['import_results'] ?? null;
if ($importResults) unset($_SESSION['import_results']);
?>

<!-- Inter Font & FontAwesome Icons -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

<style>
/* ============================================================
   SF PRO + APPLE DESIGN LANGUAGE — INVENTORY CATALOG
   ============================================================ */

:root {
    /* True iOS system palette */
    --c-bg:           #f2f2f7;
    --c-surface:      #ffffff;
    --c-surface2:     #f9f9fb;
    --c-fill:         rgba(120,120,128,0.12);
    --c-fill2:        rgba(120,120,128,0.16);
    --c-separator:    rgba(60,60,67,0.12);
    --c-separator2:   rgba(60,60,67,0.06);

    /* iOS system colors */
    --c-blue:         #007aff;
    --c-blue-light:   #e5f2ff;
    --c-blue-mid:     #b3d6ff;
    --c-green:        #34c759;
    --c-green-light:  #e6f9ec;
    --c-orange:       #ff9500;
    --c-orange-light: #fff4e5;
    --c-red:          #ff3b30;
    --c-red-light:    #fff0ef;
    --c-purple:       #af52de;
    --c-purple-light: #f5eeff;
    --c-teal:         #5ac8fa;
    --c-teal-light:   #eaf8ff;

    /* Typography */
    --f-system: -apple-system, 'SF Pro Display', 'SF Pro Text', 'Inter', 'Helvetica Neue', sans-serif;
    --f-mono:   ui-monospace, 'SF Mono', 'Menlo', 'Monaco', monospace;

    /* Text hierarchy */
    --t-primary:   #1c1c1e;
    --t-secondary: #636366;
    --t-tertiary:  #aeaeb2;
    --t-label:     #8e8e93;

    /* Elevation */
    --shadow-xs:  0 1px 2px rgba(0,0,0,0.04);
    --shadow-sm:  0 2px 8px rgba(0,0,0,0.06), 0 1px 3px rgba(0,0,0,0.04);
    --shadow-md:  0 8px 24px rgba(0,0,0,0.08), 0 2px 6px rgba(0,0,0,0.04);
    --shadow-xl:  0 24px 48px rgba(0,0,0,0.14), 0 4px 12px rgba(0,0,0,0.06);

    /* Geometry */
    --r-xs: 6px;
    --r-sm: 10px;
    --r-md: 14px;
    --r-lg: 20px;
    --r-xl: 26px;
    --r-pill: 999px;

    /* Motion */
    --ease-spring: cubic-bezier(0.34, 1.56, 0.64, 1);
    --ease-ios:    cubic-bezier(0.25, 0.1, 0.25, 1);
    --dur-fast:    0.18s;
    --dur-mid:     0.28s;
    --dur-slow:    0.42s;
}

/* ---- Reset & Base ---- */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

.inv-root {
    font-family: var(--f-system);
    font-size: 15px;
    color: var(--t-primary);
    background: var(--c-bg);
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
}

/* ---- Layout ---- */
.inv-wrap {
    max-width: 1420px;
    margin: 0 auto;
    padding: 0px 24px 160px;
}
.inv-split {
    display: flex;
    gap: 18px;
    align-items: flex-start;
}
.inv-main { flex: 1; min-width: 0; }

/* ---- Page Header ---- */
.inv-header {
    margin-bottom: 28px;
}
.inv-eyebrow {
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: var(--c-blue);
    margin-bottom: 6px;
}
.inv-title {
    font-size: 32px;
    font-weight: 700;
    letter-spacing: -0.03em;
    line-height: 1.1;
    color: var(--t-primary);
    margin-bottom: 24px;
}

/* ---- Stat Cards (SF Widgets style) ---- */
.stat-row {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 14px;
    margin-bottom: 28px;
}
.stat-card {
    background: var(--c-surface);
    border-radius: var(--r-xl);
    padding: 16px 20px;
    box-shadow: var(--shadow-sm);
    border: 0.5px solid var(--c-separator);
    transition: transform var(--dur-fast) var(--ease-ios),
                box-shadow var(--dur-fast) var(--ease-ios);
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
    height: 2px;
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
    width: 48px; height: 48px;
    border-radius: var(--r-sm);
    display: flex; align-items: center; justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
}
.stat-card.blue  .stat-icon { background: var(--c-blue-light);   color: var(--c-blue); }
.stat-card.orange .stat-icon { background: var(--c-orange-light); color: var(--c-orange); }
.stat-card.red   .stat-icon { background: var(--c-red-light);    color: var(--c-red); }
.stat-info {
    display: flex; flex-direction: column; justify-content: center;
}
.stat-num {
    font-size: 24px;
    font-weight: 700;
    letter-spacing: -0.04em;
    font-family: var(--f-mono);
    line-height: 1;
    margin-bottom: 2px;
}
.stat-card.blue   .stat-num { color: var(--t-primary); }
.stat-card.orange .stat-num { color: var(--c-orange); }
.stat-card.red    .stat-num { color: var(--c-red); }
.stat-lbl {
    font-size: 12px;
    font-weight: 600;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: var(--t-label);
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
    border-left-width: 3px;
    font-size: 14px;
}
.sf-alert.success { border-left-color: var(--c-green); }
.sf-alert.error   { border-left-color: var(--c-red); }
.sf-alert-icon { font-size: 18px; flex-shrink: 0; padding-top: 1px; }
.sf-alert.success .sf-alert-icon { color: var(--c-green); }
.sf-alert.error   .sf-alert-icon { color: var(--c-red); }
.sf-alert-title { font-weight: 600; color: var(--t-primary); margin-bottom: 2px; }
.sf-alert-msg   { color: var(--t-secondary); font-size: 13px; }
.sf-alert-close {
    margin-left: auto; flex-shrink: 0; background: none; border: none;
    color: var(--t-tertiary); cursor: pointer; font-size: 15px; padding: 2px;
    transition: color var(--dur-fast);
}
.sf-alert-close:hover { color: var(--t-secondary); }

/* ---- Filter Shelf ---- */
.filter-shelf {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    align-items: center;
    margin-bottom: 18px;
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
    font-size: 12px;
    font-weight: 600;
    letter-spacing: 0.02em;
    color: var(--t-label);
    text-transform: uppercase;
}
.filter-chip input[type="number"] {
    border: none; outline: none; background: transparent;
    font-size: 14px; font-weight: 600; font-family: var(--f-system);
    color: var(--t-primary); width: 72px; padding: 0;
}
.filter-chip input[type="number"]::-webkit-inner-spin-button,
.filter-chip input[type="number"]::-webkit-outer-spin-button { -webkit-appearance: none; }
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
    font-size: 14px; font-weight: 600; color: var(--t-primary);
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
    min-width: 196px;
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
    font-size: 14px;
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
#table-loader {
    position: absolute; inset: 0; z-index: 10;
    background: rgba(255,255,255,0.75);
    backdrop-filter: blur(4px);
    display: flex; align-items: center; justify-content: center;
    opacity: 0; pointer-events: none;
    transition: opacity var(--dur-mid);
}
#table-loader.active { opacity: 1; pointer-events: auto; }

.inv-table { width: 100%; border-collapse: collapse; }
.inv-table thead th {
    padding: 13px 18px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: var(--t-label);
    background: var(--c-surface2);
    border-bottom: 0.5px solid var(--c-separator);
    white-space: nowrap;
}
.inv-table thead th:first-child { border-radius: var(--r-xl) 0 0 0; }
.inv-table thead th:last-child  { border-radius: 0 var(--r-xl) 0 0; }
.inv-table tbody tr {
    transition: background var(--dur-fast);
    border-bottom: 0.5px solid var(--c-separator2);
}
.inv-table tbody tr:last-child { border-bottom: none; }
.inv-table tbody tr:hover { background: var(--c-fill2); }
.inv-table td {
    padding: 14px 18px;
    font-size: 14px;
    color: var(--t-primary);
    vertical-align: middle;
}

/* ---- iOS Checkbox ---- */
.sf-check {
    appearance: none;
    width: 21px; height: 21px;
    border-radius: 50%;
    border: 1.5px solid var(--c-separator);
    background: var(--c-fill);
    cursor: pointer;
    position: relative;
    flex-shrink: 0;
    transition: all var(--dur-fast) var(--ease-spring);
    display: block;
}
.sf-check:checked {
    background: var(--c-blue);
    border-color: var(--c-blue);
    box-shadow: 0 2px 8px rgba(0,122,255,0.35);
}
.sf-check:checked::after {
    content: '';
    position: absolute;
    left: 6px; top: 4px;
    width: 5px; height: 9px;
    border: 2px solid #fff; border-top: none; border-left: none;
    transform: rotate(45deg);
}

/* ---- Product Cell ---- */
.prod-thumb {
    width: 42px; height: 42px;
    border-radius: var(--r-sm);
    object-fit: cover;
    background: var(--c-fill);
    border: 0.5px solid var(--c-separator);
    flex-shrink: 0;
}
.prod-name {
    font-size: 14px; font-weight: 600; color: var(--t-primary);
    margin-bottom: 2px; line-height: 1.3;
}
.prod-meta {
    font-size: 11px; font-weight: 500;
    color: var(--t-tertiary);
    font-family: var(--f-mono);
    letter-spacing: 0.02em;
}
.d-flex-row { display: flex; align-items: center; gap: 10px; }

/* ---- Numbers ---- */
.num { font-family: var(--f-mono); font-size: 13px; font-weight: 600; }
.num-muted { color: var(--t-label); }

/* ---- Status Badges ---- */
.sf-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 10px;
    border-radius: var(--r-pill);
    font-size: 12px; font-weight: 700;
    letter-spacing: 0.01em;
    white-space: nowrap;
}
.sf-badge .dot {
    width: 5px; height: 5px; border-radius: 50%; flex-shrink: 0;
}
.badge-active  { background: var(--c-green-light);  color: #1a7f3c; }
.badge-active  .dot { background: var(--c-green); }
.badge-low     { background: var(--c-orange-light); color: #c05d00; }
.badge-low     .dot { background: var(--c-orange); }
.badge-out     { background: var(--c-red-light);    color: #c0291f; }
.badge-out     .dot { background: var(--c-red); }

/* ---- Row Actions ---- */
.row-acts { display: flex; gap: 6px; justify-content: flex-end; }
.act-btn {
    width: 32px; height: 32px;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    background: var(--c-fill);
    color: var(--t-label);
    border: none; cursor: pointer; text-decoration: none;
    font-size: 13px;
    transition: all var(--dur-fast) var(--ease-spring);
}
.act-btn:hover { transform: scale(1.12); }
.act-btn.view:hover   { background: var(--c-blue-light);   color: var(--c-blue); }
.act-btn.edit:hover   { background: var(--c-purple-light); color: var(--c-purple); }
.act-btn.trash:hover  { background: var(--c-red-light);    color: var(--c-red); }

/* ---- Pagination ---- */
.sf-pagination {
    display: flex; justify-content: space-between; align-items: center;
    padding: 14px 18px;
    background: var(--c-surface2);
    border-top: 0.5px solid var(--c-separator);
}
.pg-info { font-size: 13px; color: var(--t-secondary); font-weight: 500; }
.pg-info strong { color: var(--t-primary); font-weight: 700; }
.pg-right { display: flex; align-items: center; gap: 14px; }
.pg-size-wrap { display: flex; align-items: center; gap: 7px; }
.pg-size-lbl { font-size: 12px; color: var(--t-label); font-weight: 500; }
.pg-size-sel {
    font-family: var(--f-system); font-size: 13px; font-weight: 600;
    color: var(--t-primary);
    background: var(--c-fill);
    border: 0.5px solid var(--c-separator);
    border-radius: var(--r-sm);
    padding: 5px 9px;
    outline: none; cursor: pointer;
    transition: border-color var(--dur-fast);
}
.pg-size-sel:hover { border-color: var(--c-blue); }
.pg-nav { display: flex; border: 0.5px solid var(--c-separator); border-radius: var(--r-sm); overflow: hidden; }
.pg-btn {
    width: 34px; height: 32px;
    display: flex; align-items: center; justify-content: center;
    background: var(--c-surface);
    border: none; cursor: pointer; color: var(--t-primary); font-size: 12px;
    transition: background var(--dur-fast);
}
.pg-btn:hover:not(:disabled) { background: var(--c-fill); }
.pg-btn:disabled { opacity: 0.35; cursor: not-allowed; }
.pg-btn + .pg-btn { border-left: 0.5px solid var(--c-separator); }
.pg-current {
    padding: 0 14px;
    display: flex; align-items: center;
    font-size: 13px; font-weight: 600; color: var(--t-primary);
    background: var(--c-surface);
    border-left: 0.5px solid var(--c-separator);
    border-right: 0.5px solid var(--c-separator);
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

/* ---- Bulk Toolbar ---- */
.bulk-bar {
    position: fixed;
    bottom: 100px; left: 50%;
    transform: translateX(-50%) translateY(12px);
    background: var(--c-blue);
    border-radius: var(--r-pill);
    padding: 10px 22px;
    display: flex; align-items: center; gap: 16px;
    box-shadow: var(--shadow-xl), 0 4px 16px rgba(0,122,255,0.35);
    z-index: 90;
    opacity: 0; pointer-events: none;
    transition: opacity var(--dur-mid) var(--ease-ios),
                transform var(--dur-mid) var(--ease-ios);
}
.bulk-bar:not(.hidden) {
    opacity: 1; pointer-events: auto;
    transform: translateX(-50%) translateY(0);
}
.bulk-count {
    background: rgba(255,255,255,0.22);
    color: #fff; font-size: 13px; font-weight: 700;
    padding: 3px 10px; border-radius: var(--r-pill);
}
.bulk-lbl { font-size: 14px; font-weight: 600; color: rgba(255,255,255,0.85); }
.bulk-divider { width: 0.5px; height: 20px; background: rgba(255,255,255,0.25); }
.bulk-action {
    background: rgba(255,255,255,0.2);
    color: #fff; border: none; border-radius: var(--r-pill);
    padding: 7px 16px; font-size: 13px; font-weight: 700;
    font-family: var(--f-system); cursor: pointer;
    display: flex; align-items: center; gap: 6px;
    transition: background var(--dur-fast);
}
.bulk-action:hover { background: rgba(255,255,255,0.3); }
.bulk-cancel {
    background: transparent; border: none; color: rgba(255,255,255,0.65);
    font-size: 13px; font-weight: 600; font-family: var(--f-system);
    cursor: pointer; padding: 6px;
    transition: color var(--dur-fast);
}
.bulk-cancel:hover { color: #fff; }

/* ---- Modals ---- */
.modal-veil {
    position: fixed; inset: 0; z-index: 500;
    background: rgba(0,0,0,0.4);
    backdrop-filter: saturate(180%) blur(14px);
    -webkit-backdrop-filter: saturate(180%) blur(14px);
    display: flex; align-items: center; justify-content: center;
    padding: 20px;
    opacity: 0; pointer-events: none;
    transition: opacity var(--dur-mid) var(--ease-ios);
}
.modal-veil:not(.hidden) { opacity: 1; pointer-events: auto; }
.sf-modal {
    background: var(--c-surface);
    border-radius: var(--r-xl);
    border: 0.5px solid var(--c-separator);
    box-shadow: var(--shadow-xl);
    width: 100%; max-width: 420px;
    overflow: hidden;
    transform: translateY(16px) scale(0.97);
    transition: transform var(--dur-slow) var(--ease-spring);
}
.modal-veil:not(.hidden) .sf-modal { transform: translateY(0) scale(1); }
.modal-head {
    padding: 20px 22px 18px;
    text-align: center;
    border-bottom: 0.5px solid var(--c-separator);
    position: relative;
}
.modal-title { font-size: 17px; font-weight: 700; color: var(--t-primary); }
.modal-close {
    position: absolute; right: 14px; top: 14px;
    width: 28px; height: 28px; border-radius: 50%;
    background: var(--c-fill); border: none; cursor: pointer;
    color: var(--t-label); font-size: 13px;
    display: flex; align-items: center; justify-content: center;
    transition: background var(--dur-fast);
}
.modal-close:hover { background: var(--c-fill2); }
.modal-body { padding: 22px; max-height: 60vh; overflow-y: auto; }
.modal-foot {
    padding: 14px 22px;
    background: var(--c-surface2);
    border-top: 0.5px solid var(--c-separator);
    display: flex; gap: 10px;
}
.sf-btn {
    flex: 1; padding: 12px;
    border-radius: var(--r-md);
    font-size: 15px; font-weight: 700;
    font-family: var(--f-system); text-align: center;
    cursor: pointer; border: none;
    transition: transform var(--dur-fast) var(--ease-spring), filter var(--dur-fast);
    text-decoration: none; display: flex; align-items: center; justify-content: center;
}
.sf-btn:hover { filter: brightness(0.94); }
.sf-btn:active { transform: scale(0.97); }
.sf-btn.neutral { background: var(--c-fill); color: var(--t-primary); }
.sf-btn.primary { background: var(--t-primary); color: #fff; }
.sf-btn.danger  { background: var(--c-red);    color: #fff; }

/* ---- Inputs ---- */
.sf-input {
    width: 100%; padding: 12px 14px;
    background: var(--c-fill);
    border: 0.5px solid var(--c-separator);
    border-radius: var(--r-md);
    font-size: 15px; font-weight: 500; font-family: var(--f-system);
    color: var(--t-primary); outline: none;
    transition: border-color var(--dur-fast), box-shadow var(--dur-fast), background var(--dur-fast);
    box-sizing: border-box;
}
.sf-input:focus {
    background: var(--c-surface);
    border-color: var(--c-blue);
    box-shadow: 0 0 0 3.5px rgba(0,122,255,0.14);
}
.sf-input:disabled { opacity: 0.45; cursor: not-allowed; }
.sf-input-group { display: flex; flex-direction: column; gap: 14px; }

/* ---- Bulk section ---- */
.bulk-field {
    background: var(--c-fill);
    border-radius: var(--r-md);
    padding: 14px 16px;
}
.bulk-field-head {
    display: flex; align-items: center; gap: 10px;
    cursor: pointer; margin-bottom: 10px;
}
.bulk-field-label { font-size: 14px; font-weight: 600; color: var(--t-primary); }
.bulk-row { display: flex; gap: 8px; }

/* ---- Modal error ---- */
.modal-err {
    padding: 11px 14px;
    background: var(--c-red-light);
    border-radius: var(--r-md);
    font-size: 13px; font-weight: 600;
    color: #c0291f; text-align: center;
    margin-bottom: 18px;
}

/* ---- CSV drop zone ---- */
.drop-zone {
    border: 1.5px dashed var(--c-separator);
    border-radius: var(--r-lg);
    padding: 32px 20px;
    text-align: center;
    background: var(--c-fill);
    cursor: pointer; position: relative;
    margin-bottom: 18px;
    transition: border-color var(--dur-fast), background var(--dur-fast);
}
.drop-zone:hover { border-color: var(--c-blue); background: var(--c-blue-light); }
.drop-zone input[type="file"] {
    position: absolute; inset: 0; opacity: 0; cursor: pointer;
}
.drop-zone-icon { font-size: 30px; color: var(--c-blue); margin-bottom: 10px; }
.drop-zone-title { font-size: 15px; font-weight: 600; color: var(--t-primary); }
.drop-zone-sub   { font-size: 13px; color: var(--t-secondary); margin-top: 4px; }

/* ---- Import hint ---- */
.import-hint {
    background: var(--c-surface2);
    border-radius: var(--r-md);
    padding: 14px 16px;
    border: 0.5px solid var(--c-separator);
    display: flex; align-items: flex-start; gap: 10px;
}
.import-hint-title { font-size: 13px; font-weight: 700; color: var(--t-primary); margin-bottom: 4px; display: flex; gap: 6px; align-items: center; }
.import-hint-body  { font-size: 12px; color: var(--t-secondary); line-height: 1.5; }

/* ---- Quick View Panel ---- */
.qv-panel {
    width: 0; opacity: 0; overflow: hidden;
    background: var(--c-surface);
    border-radius: var(--r-xl);
    border: 0.5px solid var(--c-separator);
    box-shadow: var(--shadow-md);
    flex-shrink: 0;
    position: sticky; top: 20px;
    height: fit-content;
    max-height: calc(100vh - 40px);
    overflow-y: auto;
    transition: width var(--dur-slow) var(--ease-ios),
                opacity var(--dur-slow) var(--ease-ios);
}
.qv-panel.open { width: 320px; opacity: 1; overflow-y: auto; }
.qv-inner { padding: 22px; min-width: 320px; }
.qv-close {
    position: absolute; top: 14px; right: 14px;
    width: 28px; height: 28px; border-radius: 50%;
    background: var(--c-fill); border: none; cursor: pointer;
    color: var(--t-label); font-size: 13px;
    display: flex; align-items: center; justify-content: center;
    transition: background var(--dur-fast);
}
.qv-close:hover { background: var(--c-fill2); }
.qv-carousel-container {
    position: relative;
    width: 100%;
    aspect-ratio: 1 / 1;
    border-radius: var(--r-lg);
    overflow: hidden;
    margin-bottom: 14px;
    border: 0.5px solid var(--c-separator);
    background: var(--c-surface2);
}
.qv-carousel-track {
    display: flex;
    width: 100%;
    height: 100%;
    transition: transform var(--dur-mid) var(--ease-ios);
}
.qv-carousel-slide {
    width: 100%;
    height: 100%;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}
.qv-carousel-slide img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.qv-carousel-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: rgba(0,0,0,0.2);
    border: none;
    padding: 0;
    transition: background var(--dur-fast), transform var(--dur-fast);
    cursor: pointer;
}
.qv-carousel-dot.active {
    background: var(--c-blue);
    transform: scale(1.2);
}
.qv-name { font-size: 17px; font-weight: 700; color: var(--t-primary); text-align: center; margin-bottom: 3px; }
.qv-sku  { font-size: 11px; font-family: var(--f-mono); color: var(--t-label); text-align: center; margin-bottom: 8px; }
.qv-tags { display: flex; justify-content: center; gap: 8px; margin-bottom: 18px; flex-wrap: wrap; }
.qv-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 12px; }
.qv-tile {
    background: var(--c-fill);
    border-radius: var(--r-md);
    padding: 14px;
    text-align: center;
}
.qv-tile-lbl { font-size: 10px; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: var(--t-label); margin-bottom: 6px; }
.qv-tile-val { font-size: 22px; font-weight: 700; font-family: var(--f-mono); color: var(--t-primary); }
.qv-prices {
    background: var(--c-fill);
    border-radius: var(--r-md);
    padding: 14px 16px;
    margin-bottom: 12px;
}
.qv-price-row {
    display: flex; justify-content: space-between; align-items: center;
    padding: 7px 0;
    border-bottom: 0.5px solid var(--c-separator2);
}
.qv-price-row:last-child { border-bottom: none; padding-bottom: 0; }
.qv-price-lbl { font-size: 13px; color: var(--t-secondary); font-weight: 500; }
.qv-price-val { font-size: 14px; font-weight: 700; font-family: var(--f-mono); color: var(--t-primary); }
.qv-desc {
    font-size: 13px; color: var(--t-secondary); line-height: 1.55;
    margin-bottom: 18px;
    max-height: 80px; overflow-y: auto;
}
.qv-actions { display: flex; gap: 8px; }
.qv-btn {
    flex: 1; padding: 10px 14px;
    border-radius: var(--r-md);
    font-size: 13px; font-weight: 700;
    font-family: var(--f-system);
    display: flex; align-items: center; justify-content: center; gap: 6px;
    text-decoration: none; border: none; cursor: pointer;
    transition: transform var(--dur-fast) var(--ease-spring), filter var(--dur-fast);
}
.qv-btn:hover { filter: brightness(0.92); }
.qv-btn:active { transform: scale(0.97); }
.qv-btn.primary { background: var(--t-primary); color: #fff; }
.qv-btn.muted   { background: var(--c-fill); color: var(--t-primary); }

/* ---- Empty state ---- */
.tbl-empty { text-align: center; padding: 64px 20px; }
.tbl-empty-icon { font-size: 36px; color: var(--t-tertiary); margin-bottom: 12px; }
.tbl-empty-title { font-size: 16px; font-weight: 600; color: var(--t-secondary); margin-bottom: 6px; }
.tbl-empty-sub   { font-size: 13px; color: var(--t-tertiary); }

/* ---- Spinner ---- */
@keyframes spin { to { transform: rotate(360deg); } }
.spin { animation: spin 0.7s linear infinite; display: inline-block; }

/* ---- Helpers ---- */
.hidden { display: none !important; }
.txt-right { text-align: right; }
.txt-center { text-align: center; }
.gap-2 { gap: 8px; }

/* ---- Custom Heading Styles matching Category Page ---- */
.inv-header {
    padding-top: 16px !important;
    margin-bottom: 28px !important;
}
.inv-header-title {
    font-family: 'Inter', sans-serif !important;
    font-size: 30px !important;
    font-weight: 800 !important;
    letter-spacing: -0.025em !important;
    color: #0f172a !important;
    margin: 0 0 4px 0 !important;
    line-height: 2.25rem !important;
}
.inv-header-desc {
    font-family: 'Inter', sans-serif !important;
    font-size: 14px !important;
    color: #64748b !important;
    margin: 4px 0 24px 0 !important;
    line-height: 1.25rem !important;
    font-weight: 400 !important;
}
</style>

<div class="inv-root">
<form id="filterForm" action="<?= APP_URL ?>/inventory" method="GET" onsubmit="event.preventDefault(); applyAjaxFilters();">
    <input type="hidden" name="page" id="currentPageInput" value="<?= $currentPage ?>">
    <input type="hidden" name="per_page" id="perPageInput" value="<?= $perPage ?>">

    <div class="inv-wrap">

        <!-- Header -->
        <div class="inv-header">

            <!-- Stat Cards -->
            <div class="stat-row">
                <div class="stat-card blue">
                    <div class="stat-icon"><i class="fa-solid fa-layer-group"></i></div>
                    <div class="stat-info">
                        <div class="stat-num" id="stat-total-items"><?= number_format($stats->total_items) ?></div>
                        <div class="stat-lbl">Total Items</div>
                    </div>
                </div>
                <div class="stat-card orange">
                    <div class="stat-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
                    <div class="stat-info">
                        <div class="stat-num" id="stat-low-stock"><?= number_format($stats->low_stock_count) ?></div>
                        <div class="stat-lbl">Low Stock</div>
                    </div>
                </div>
                <div class="stat-card red">
                    <div class="stat-icon"><i class="fa-solid fa-circle-xmark"></i></div>
                    <div class="stat-info">
                        <div class="stat-num" id="stat-out-of-stock"><?= number_format($stats->out_of_stock_count) ?></div>
                        <div class="stat-lbl">Out of Stock</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alerts -->
        <?php if ($flashSuccess || isset($_GET['flash_success'])): ?>
        <div id="flash-success-alert" class="sf-alert success">
            <i class="fa-solid fa-circle-check sf-alert-icon"></i>
            <div>
                <div class="sf-alert-title">Success</div>
                <div class="sf-alert-msg"><?= htmlspecialchars($flashSuccess ?? $_GET['flash_success'] ?? '') ?></div>
            </div>
            <button type="button" class="sf-alert-close" onclick="document.getElementById('flash-success-alert').style.display='none'"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <?php endif; ?>

        <?php if ($flashError): ?>
        <div id="flash-error-alert" class="sf-alert error">
            <i class="fa-solid fa-circle-exclamation sf-alert-icon"></i>
            <div>
                <div class="sf-alert-title">Error</div>
                <div class="sf-alert-msg"><?= htmlspecialchars($flashError) ?></div>
            </div>
            <button type="button" class="sf-alert-close" onclick="document.getElementById('flash-error-alert').style.display='none'"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <?php endif; ?>

        <?php if ($importResults): ?>
        <div id="import-results-alert" class="sf-alert" style="border-left-color: var(--c-blue); display: flex; flex-direction: column; gap: 12px; width: 100%;">
            <div style="display: flex; align-items: flex-start; gap: 12px; width: 100%;">
                <i class="fa-solid fa-wand-magic-sparkles sf-alert-icon" style="color: var(--c-blue);"></i>
                <div style="flex: 1;">
                    <div class="sf-alert-title">CSV Import Completed</div>
                    <div class="sf-alert-msg">
                        <ul style="margin: 6px 0 0 16px; padding: 0; display: flex; gap: 20px; list-style-type: disc;">
                            <li>Added: <strong style="color: var(--c-green); font-family: var(--f-mono);"><?= intval($importResults['added']) ?></strong> new products</li>
                            <li>Updated: <strong style="color: var(--c-blue); font-family: var(--f-mono);"><?= intval($importResults['updated']) ?></strong> existing products</li>
                        </ul>
                    </div>
                </div>
                <button type="button" class="sf-alert-close" onclick="document.getElementById('import-results-alert').style.display='none'"><i class="fa-solid fa-xmark"></i></button>
            </div>
            
            <?php if (!empty($importResults['success_logs'])): ?>
            <div style="display: flex; flex-direction: column; gap: 4px; background: rgba(0, 122, 255, 0.05); border-radius: var(--r-sm); padding: 10px 14px; max-height: 100px; overflow-y: auto; width: 100%; border: 0.5px solid rgba(0, 122, 255, 0.1);">
                <div style="font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--c-blue); margin-bottom: 2px;">Action Log</div>
                <?php foreach ($importResults['success_logs'] as $log): ?>
                    <div style="font-size: 11px; color: #0056b3; display: flex; align-items: center; gap: 6px;">
                        <i class="fa-solid fa-circle-check" style="font-size: 8px;"></i>
                        <span><?= htmlspecialchars($log) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($importResults['errors'])): ?>
            <div style="display: flex; flex-direction: column; gap: 4px; background: rgba(255, 59, 48, 0.05); border-radius: var(--r-sm); padding: 10px 14px; max-height: 150px; overflow-y: auto; width: 100%; border: 0.5px solid rgba(255, 59, 48, 0.1);">
                <div style="font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--c-red); margin-bottom: 2px;">Errors / Warnings</div>
                <?php foreach ($importResults['errors'] as $err): ?>
                    <div style="font-size: 11px; color: var(--c-red); display: flex; align-items: flex-start; gap: 6px; line-height: 1.4;">
                        <i class="fa-solid fa-circle-exclamation" style="font-size: 8px; margin-top: 3px; flex-shrink: 0;"></i>
                        <span style="font-family: var(--f-mono);">
                            <?php if (is_array($err)): ?>
                                Row <?= $err['row'] ?> (SKU: <?= htmlspecialchars($err['sku']) ?>): <?= htmlspecialchars(implode(', ', $err['messages'])) ?>
                            <?php else: ?>
                                <?= htmlspecialchars($err) ?>
                            <?php endif; ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="filter-shelf">
            <!-- Category -->
            <div class="filter-chip">
                <span class="filter-chip-label">Category</span>
                <div class="sf-dropdown" tabindex="0">
                    <?php
                    $selectedCatName = 'All';
                    foreach ($categories as $cat) {
                        if ((string)$filters['category_id'] === (string)$cat->id) $selectedCatName = $cat->name;
                    }
                    ?>
                    <div class="sf-dropdown-val" id="cat-dropdown-val"><?= htmlspecialchars($selectedCatName) ?></div>
                    <div class="sf-dropdown-menu">
                        <div class="sf-dropdown-item <?= $filters['category_id'] === '' ? 'active' : '' ?>" onclick="selectCategory('', 'All')">All Categories</div>
                        <?php foreach ($categories as $cat): ?>
                        <div class="sf-dropdown-item <?= (string)$filters['category_id'] === (string)$cat->id ? 'active' : '' ?>" onclick="selectCategory('<?= $cat->id ?>', '<?= htmlspecialchars(addslashes($cat->name)) ?>')"><?= htmlspecialchars($cat->name) ?></div>
                        <?php endforeach; ?>
                    </div>
                    <input type="hidden" name="category_id" id="categoryInput" value="<?= htmlspecialchars($filters['category_id']) ?>">
                </div>
            </div>

            <!-- Stock Status -->
            <div class="filter-chip">
                <span class="filter-chip-label">Stock Status</span>
                <div class="sf-dropdown" tabindex="0">
                    <?php
                    $statusNames = ['' => 'All', 'instock' => 'In Stock', 'lowstock' => 'Low Stock', 'outstock' => 'Out of Stock'];
                    $selectedStatusName = $statusNames[$filters['stock_status']] ?? 'All';
                    ?>
                    <div class="sf-dropdown-val" id="status-dropdown-val"><?= $selectedStatusName ?></div>
                    <div class="sf-dropdown-menu">
                        <div class="sf-dropdown-item <?= $filters['stock_status'] === '' ? 'active' : '' ?>" onclick="selectStatus('', 'All')">All Statuses</div>
                        <div class="sf-dropdown-item <?= $filters['stock_status'] === 'instock' ? 'active' : '' ?>" onclick="selectStatus('instock', 'In Stock')">In Stock</div>
                        <div class="sf-dropdown-item <?= $filters['stock_status'] === 'lowstock' ? 'active' : '' ?>" onclick="selectStatus('lowstock', 'Low Stock')">Low Stock</div>
                        <div class="sf-dropdown-item <?= $filters['stock_status'] === 'outstock' ? 'active' : '' ?>" onclick="selectStatus('outstock', 'Out of Stock')">Out of Stock</div>
                    </div>
                    <input type="hidden" name="stock_status" id="stockStatusInput" value="<?= htmlspecialchars($filters['stock_status']) ?>">
                </div>
            </div>

            <!-- Operational Status (Active/Inactive) -->
            <div class="filter-chip">
                <span class="filter-chip-label">Status</span>
                <div class="sf-dropdown" tabindex="0">
                    <?php
                    $itemStatuses = ['' => 'All', 'active' => 'Active', 'inactive' => 'Inactive'];
                    $selectedItemStatusName = $itemStatuses[$filters['status'] ?? ''] ?? 'All';
                    ?>
                    <div class="sf-dropdown-val" id="item-status-dropdown-val"><?= htmlspecialchars($selectedItemStatusName) ?></div>
                    <div class="sf-dropdown-menu">
                        <div class="sf-dropdown-item <?= ($filters['status'] ?? '') === '' ? 'active' : '' ?>" onclick="selectItemStatus('', 'All')">All</div>
                        <div class="sf-dropdown-item <?= ($filters['status'] ?? '') === 'active' ? 'active' : '' ?>" onclick="selectItemStatus('active', 'Active')">Active</div>
                        <div class="sf-dropdown-item <?= ($filters['status'] ?? '') === 'inactive' ? 'active' : '' ?>" onclick="selectItemStatus('inactive', 'Inactive')">Inactive</div>
                    </div>
                    <input type="hidden" name="status" id="itemStatusInput" value="<?= htmlspecialchars($filters['status'] ?? '') ?>">
                </div>
            </div>

            <div class="filter-chip">
                <span class="filter-chip-label">Min Rs</span>
                <input type="number" step="0.01" name="min_price" id="minPriceInput" value="<?= htmlspecialchars($filters['min_price']) ?>" oninput="triggerSearchDelay()" placeholder="0.00">
            </div>
            <div class="filter-chip">
                <span class="filter-chip-label">Max Rs</span>
                <input type="number" step="0.01" name="max_price" id="maxPriceInput" value="<?= htmlspecialchars($filters['max_price']) ?>" oninput="triggerSearchDelay()" placeholder="0.00">
            </div>

            <button type="button" onclick="clearAllFilters()" class="filter-reset">Reset</button>

            <div class="filter-count">
                <strong id="matching-count"><?= $totalItems ?></strong> items
            </div>
        </div>

        <!-- Split Layout -->
        <div class="inv-split">

            <!-- Main Table -->
            <div class="inv-main">
                <div class="table-panel">
                    <div id="table-loader">
                        <i class="fa-solid fa-spinner spin" style="font-size:22px; color: var(--c-blue);"></i>
                    </div>

                    <div id="table-container">
                        <table class="inv-table">
                            <thead>
                                <tr>
                                    <th style="width:42px;" class="txt-center">
                                        <input type="checkbox" id="selectAllCheckbox" onchange="toggleSelectAll(this)" class="sf-check">
                                    </th>
                                    <th>Product</th>
                                    <th class="txt-right">Retail</th>
                                    <th class="txt-right">B2B</th>
                                    <th class="txt-center">Qty</th>
                                    <th>Status</th>
                                    <th class="txt-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($items)): ?>
                                <tr>
                                    <td colspan="7">
                                        <div class="tbl-empty">
                                            <div class="tbl-empty-icon"><i class="fa-solid fa-cube"></i></div>
                                            <div class="tbl-empty-title">No products found</div>
                                            <div class="tbl-empty-sub">Try adjusting your filters or add a new product</div>
                                        </div>
                                    </td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($items as $item):
                                        $qty     = intval($item->qty ?? 0);
                                        $price   = floatval($item->selling_price ?? $item->price ?? 0);
                                        $b2b     = floatval($item->wholesale_price ?? 0);
                                        $sku     = !empty($item->item_code) ? $item->item_code : ($item->sku ?? '-');
                                        $image   = $item->image_path ?? '';
                                        $img_src = empty($image) ? 'https://placehold.co/100x100/f2f2f7/8e8e93?text=' . urlencode(substr($item->name ?? 'P', 0, 1)) : APP_URL . '/uploads/products/' . basename($image);

                                        $itemStatus = $item->status ?? 'active';
                                        if ($itemStatus === 'inactive') {
                                            $badgeCls = 'badge-out';
                                            $badgeTxt = 'Inactive';
                                        } elseif ($qty <= 0) {
                                            $badgeCls = 'badge-out';
                                            $badgeTxt = 'Out of stock';
                                        } elseif ($qty <= 5) {
                                            $badgeCls = 'badge-low';
                                            $badgeTxt = 'Low stock';
                                        } else {
                                            $badgeCls = 'badge-active';
                                            $badgeTxt = 'Active';
                                        }

                                        // Build all images gallery array for quick view slider
                                        $all_imgs = [];
                                        if (!empty($image)) {
                                            $all_imgs[] = APP_URL . '/uploads/products/' . basename($image);
                                        } else {
                                            $all_imgs[] = 'https://placehold.co/300x300/f2f2f7/8e8e93?text=' . urlencode(substr($item->name ?? 'P', 0, 1));
                                        }

                                        if (!empty($item->additional_images)) {
                                            $decoded = json_decode($item->additional_images, true);
                                            if (is_array($decoded)) {
                                                foreach ($decoded as $add_img) {
                                                    $all_imgs[] = APP_URL . '/uploads/products/' . basename($add_img);
                                                }
                                            }
                                        }

                                        if (!empty($item->variations_json)) {
                                            $vars = json_decode(html_entity_decode($item->variations_json, ENT_QUOTES, 'UTF-8'), true);
                                            if (is_array($vars)) {
                                                foreach ($vars as $v) {
                                                    if (!empty($v['image_path'])) {
                                                        $all_imgs[] = APP_URL . '/uploads/products/' . basename($v['image_path']);
                                                    }
                                                }
                                            }
                                        }

                                        $all_imgs = array_values(array_unique($all_imgs));
                                        $all_imgs_json = json_encode($all_imgs);
                                    ?>
                                    <tr>
                                        <td class="txt-center">
                                            <input type="checkbox" name="selected_items[]" value="<?= $item->id ?>" class="item-select-checkbox sf-check" onchange="updateSelection()">
                                        </td>
                                        <td>
                                            <div class="d-flex-row">
                                                <img src="<?= $img_src ?>" class="prod-thumb" alt="" onerror="this.src='https://placehold.co/100x100/f2f2f7/8e8e93?text=?'">
                                                <div>
                                                    <div class="prod-name"><?= htmlspecialchars($item->name ?? 'Unnamed Item') ?></div>
                                                    <div class="prod-meta"><?= htmlspecialchars($sku) ?><?= !empty($item->sample_code) ? ' · ' . htmlspecialchars($item->sample_code) : '' ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="txt-right"><span class="num"><?= number_format($price, 2) ?></span></td>
                                        <td class="txt-right"><span class="num num-muted"><?= number_format($b2b, 2) ?></span></td>
                                        <td class="txt-center">
                                            <span class="num" style="color: <?= $qty <= 0 ? 'var(--c-red)' : ($qty <= 5 ? 'var(--c-orange)' : 'var(--t-primary)') ?>; font-weight: 700;"><?= $qty ?></span>
                                        </td>
                                        <td>
                                            <span class="sf-badge <?= $badgeCls ?>">
                                                <span class="dot"></span><?= $badgeTxt ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="row-acts">
                                                <button type="button" class="act-btn view"
                                                    onclick="openQuickView(this)"
                                                    data-id="<?= $item->id ?>"
                                                    data-name="<?= htmlspecialchars($item->name ?? 'Unnamed') ?>"
                                                    data-sku="<?= htmlspecialchars($sku) ?>"
                                                    data-code="<?= htmlspecialchars($item->sample_code ?? '') ?>"
                                                    data-price="<?= number_format($price, 2) ?>"
                                                    data-b2b="<?= number_format($b2b, 2) ?>"
                                                    data-qty="<?= $qty ?>"
                                                    data-status="<?= htmlspecialchars($itemStatus) ?>"
                                                    data-img="<?= $img_src ?>"
                                                    data-images='<?= htmlspecialchars($all_imgs_json, ENT_QUOTES, 'UTF-8') ?>'
                                                    data-desc="<?= htmlspecialchars($item->description ?? '') ?>"
                                                    data-category="<?= htmlspecialchars($item->category_name ?? 'Uncategorized') ?>"
                                                    data-brand="<?= htmlspecialchars($item->brand ?? '') ?>"
                                                    title="Quick view">
                                                    <i class="fa-solid fa-eye"></i>
                                                </button>
                                                <a href="<?= APP_URL ?>/inventory/edit/<?= $item->id ?>" class="act-btn edit" title="Edit">
                                                    <i class="fa-solid fa-pen-to-square"></i>
                                                </a>
                                                <button type="button" class="act-btn trash" onclick="confirmDelete(<?= $item->id ?>, '<?= htmlspecialchars(addslashes($item->name)) ?>')" title="Delete">
                                                    <i class="fa-solid fa-trash-can"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>

                        <!-- Pagination -->
                        <div class="sf-pagination">
                            <div class="pg-info">
                                <?= $totalItems > 0 ? "Showing <strong>$startIndex</strong> – <strong>$endIndex</strong> of <strong>$totalItems</strong>" : 'No results' ?>
                            </div>
                            <div class="pg-right">
                                <div class="pg-size-wrap">
                                    <span class="pg-size-lbl">Per page</span>
                                    <select class="pg-size-sel native-select" data-search="false" onchange="updatePageSize(this.value)">
                                        <option value="15"   <?= $perPage === 15   ? 'selected' : '' ?>>15</option>
                                        <option value="50"   <?= $perPage === 50   ? 'selected' : '' ?>>50</option>
                                        <option value="100"  <?= $perPage === 100  ? 'selected' : '' ?>>100</option>
                                        <option value="1000" <?= $perPage === 1000 ? 'selected' : '' ?>>All</option>
                                    </select>
                                </div>
                                <?php if ($totalPages > 1): ?>
                                <div class="pg-nav">
                                    <button type="button" class="pg-btn" onclick="navigatePage(<?= max(1, $currentPage - 1) ?>)" <?= $currentPage <= 1 ? 'disabled' : '' ?>>
                                        <i class="fa-solid fa-chevron-left"></i>
                                    </button>
                                    <div class="pg-current"><?= $currentPage ?> / <?= $totalPages ?></div>
                                    <button type="button" class="pg-btn" onclick="navigatePage(<?= min($totalPages, $currentPage + 1) ?>)" <?= $currentPage >= $totalPages ? 'disabled' : '' ?>>
                                        <i class="fa-solid fa-chevron-right"></i>
                                    </button>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div><!-- /main -->

            <!-- Quick View Panel -->
            <div class="qv-panel" id="quickViewPanel">
                <div class="qv-inner" style="position:relative;">
                    <button type="button" class="qv-close" onclick="closeQuickView()"><i class="fa-solid fa-xmark"></i></button>
                    <!-- Modern Image Carousel -->
                    <div class="qv-carousel-container">
                        <div id="qv-carousel-track" class="qv-carousel-track">
                            <!-- Images will be dynamically inserted here -->
                        </div>
                        <!-- Navigation Arrows -->
                        <button type="button" onclick="prevQvImage()" class="absolute left-2 top-1/2 -translate-y-1/2 w-6 h-6 rounded-full bg-white/95 hover:bg-white text-slate-800 border border-slate-200 shadow flex items-center justify-center transition-all cursor-pointer text-[10px]" style="z-index:20; position: absolute; border: 0.5px solid var(--c-separator);"><i class="fa-solid fa-chevron-left"></i></button>
                        <button type="button" onclick="nextQvImage()" class="absolute right-2 top-1/2 -translate-y-1/2 w-6 h-6 rounded-full bg-white/95 hover:bg-white text-slate-800 border border-slate-200 shadow flex items-center justify-center transition-all cursor-pointer text-[10px]" style="z-index:20; position: absolute; border: 0.5px solid var(--c-separator);"><i class="fa-solid fa-chevron-right"></i></button>
                        <!-- Indicators dots -->
                        <div id="qv-carousel-dots" class="absolute bottom-2 left-1/2 -translate-x-1/2 flex gap-1 z-25" style="position: absolute;">
                            <!-- Dots will be dynamically inserted here -->
                        </div>
                    </div>
                    <div id="qv-name" class="qv-name"></div>
                    <div id="qv-sku"  class="qv-sku"></div>
                    <div class="qv-tags">
                        <span id="qv-category" class="sf-badge" style="background: var(--c-fill); color: var(--t-secondary);"><i class="fa-solid fa-tag"></i> Category</span>
                        <span id="qv-brand" class="sf-badge" style="background: var(--c-fill); color: var(--t-secondary);"><i class="fa-solid fa-building"></i> Brand</span>
                    </div>

                    <div class="qv-grid">
                        <div class="qv-tile">
                            <div class="qv-tile-lbl">Stock</div>
                            <div id="qv-qty" class="qv-tile-val"></div>
                        </div>
                        <div class="qv-tile" style="display:flex;flex-direction:column;align-items:center;justify-content:center;">
                            <div class="qv-tile-lbl">Status</div>
                            <div id="qv-status" style="margin-top:4px;"></div>
                        </div>
                    </div>

                    <div class="qv-prices">
                        <div class="qv-price-row">
                            <span class="qv-price-lbl">Retail price</span>
                            <span id="qv-price" class="qv-price-val"></span>
                        </div>
                        <div class="qv-price-row">
                            <span class="qv-price-lbl">B2B price</span>
                            <span id="qv-b2b" class="qv-price-val" style="color:var(--t-secondary);"></span>
                        </div>
                    </div>

                    <div id="qv-desc" class="qv-desc"></div>

                    <div class="qv-actions">
                        <a id="qv-edit-link" href="#" class="qv-btn primary"><i class="fa-solid fa-pen"></i> Edit</a>
                        <a id="qv-ledger-link" href="#" class="qv-btn muted"><i class="fa-solid fa-chart-line"></i> Ledger</a>
                    </div>
                </div>
            </div>

        </div><!-- /split -->
    </div><!-- /wrap -->

    <!-- Command Bar -->
    <div class="cmd-bar">
        <div class="cmd-search">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" name="search" id="searchInput"
                   value="<?= htmlspecialchars($filters['search']) ?>"
                   oninput="triggerSearchDelay()"
                   placeholder="Search catalog…">
        </div>
        <div class="cmd-divider"></div>
        <a href="<?= APP_URL ?>/inventory/exportCSV" class="cmd-icon" title="Export CSV"><i class="fa-solid fa-download"></i></a>
        <button type="button" onclick="openCsvModal()" class="cmd-icon" title="Import CSV"><i class="fa-solid fa-upload"></i></button>
        <a href="<?= APP_URL ?>/inventory/add" class="cmd-cta"><i class="fa-solid fa-plus" style="font-size:13px;"></i> New</a>
    </div>
</form>

<!-- Bulk Toolbar -->
<div id="bulkEditToolbar" class="bulk-bar hidden">
    <span id="selectedCountBadge" class="bulk-count">0</span>
    <span class="bulk-lbl">selected</span>
    <div class="bulk-divider"></div>
    <button type="button" onclick="clearSelection()" class="bulk-cancel">Cancel</button>
    <button type="button" onclick="openBulkEditModal()" class="bulk-action"><i class="fa-solid fa-pen"></i> Edit</button>
</div>


<!-- ============================================================
     MODALS
     ============================================================ -->

<!-- CSV Import -->
<div id="csvImportModal" class="modal-veil hidden">
    <div class="sf-modal">
        <div class="modal-head">
            <h3 class="modal-title">Import CSV</h3>
            <button type="button" onclick="closeCsvModal()" class="modal-close"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <form id="csvImportForm" action="<?= APP_URL ?>/inventory/importERPCSV" method="POST" enctype="multipart/form-data" onsubmit="return handleCsvImportSubmit(event)">
                <div class="drop-zone">
                    <input type="file" name="csv_file" id="csvFileInput" accept=".csv" required onchange="logFileSelection(this)">
                    <div class="drop-zone-icon"><i class="fa-solid fa-file-csv"></i></div>
                    <div class="drop-zone-title">Drop your file here</div>
                    <div class="drop-zone-sub">.csv format · click to browse</div>
                </div>
                <div id="importDebugInfo" style="display:none; background: #f5f5f7; border-radius: 8px; padding: 12px; margin-bottom: 12px; font-size: 12px; font-family: monospace; color: #666; max-height: 100px; overflow-y: auto;">
                    <strong>File Info:</strong> <span id="fileInfoText"></span>
                </div>
                <div class="import-hint">
                    <i class="fa-solid fa-wand-magic-sparkles" style="color:var(--c-blue); font-size:16px; flex-shrink:0; margin-top:2px;"></i>
                    <div>
                        <div class="import-hint-title">Auto Mapping</div>
                        <div class="import-hint-body">Categories, warehouses, and relations are resolved automatically based on SKUs.</div>
                    </div>
                </div>
                <div style="display:flex; gap:10px; margin-top:20px;">
                    <button type="button" onclick="closeCsvModal()" class="sf-btn neutral">Cancel</button>
                    <button type="submit" id="csvImportBtn" class="sf-btn primary">Import</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirm -->
<div id="deleteProductModal" class="modal-veil hidden">
    <div class="sf-modal">
        <div class="modal-head">
            <h3 class="modal-title" style="color:var(--c-red);">Delete Product</h3>
            <button type="button" onclick="closeDeleteModal()" class="modal-close"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form id="deleteProductForm" onsubmit="submitDeleteProduct(event)">
            <div class="modal-body">
                <input type="hidden" id="deleteItemId" name="item_id">
                <p style="font-size:15px; line-height:1.5; text-align:center; color:var(--t-primary); margin-bottom:20px;">
                    Permanently delete <strong id="deleteItemName"></strong>?<br>
                    <span style="font-size:13px; color:var(--t-secondary);">This cannot be undone.</span>
                </p>
                <div id="deleteErrorContainer" class="modal-err hidden"></div>
                <div class="sf-group">
                    <label style="font-size: 12px; font-weight: 600; color: var(--t-label); display: block; margin-bottom: 6px;">Your Account Password *</label>
                    <input type="password" name="password" id="deleteAdminPassword" class="sf-input" placeholder="Enter your password" required autocomplete="current-password">
                </div>
            </div>
            <div class="modal-foot">
                <button type="button" onclick="closeDeleteModal()" class="sf-btn neutral">Cancel</button>
                <button type="submit" id="deleteSubmitBtn" class="sf-btn danger">
                    <i id="deleteBtnSpinner" class="fa-solid fa-spinner spin hidden" style="margin-right:6px;"></i>Delete
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Bulk Edit -->
<div id="bulkEditModal" class="modal-veil hidden">
    <div class="sf-modal">
        <div class="modal-head">
            <h3 class="modal-title">Bulk Edit</h3>
            <button type="button" onclick="closeBulkEditModal()" class="modal-close"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form id="bulkEditForm" onsubmit="submitBulkEdit(event)">
            <div class="modal-body">
                <p style="font-size:14px; color:var(--t-secondary); text-align:center; margin-bottom:20px;">
                    Editing <strong id="bulkSelectedCount" style="color:var(--t-primary);">0</strong> products
                </p>
                <div id="bulkEditErrorContainer" class="modal-err hidden"></div>
                <div class="sf-input-group">

                    <!-- Category -->
                    <div class="bulk-field">
                        <label class="bulk-field-head">
                            <input type="checkbox" name="update_category" value="1" id="bulkUpdateCategory" onchange="toggleBulkField('category')" class="sf-check">
                            <span class="bulk-field-label">Category</span>
                        </label>
                        <select name="category_id" id="bulkCategorySelect" class="sf-input native-select" data-search="false" style="pointer-events:none; opacity:0.45;">
                            <option value="">No Category</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat->id ?>"><?= htmlspecialchars($cat->name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Retail Price -->
                    <div class="bulk-field">
                        <label class="bulk-field-head">
                            <input type="checkbox" name="update_selling_price" value="1" id="bulkUpdateSellingPrice" onchange="toggleBulkField('selling_price')" class="sf-check">
                            <span class="bulk-field-label">Retail Price</span>
                        </label>
                        <div class="bulk-row">
                            <select name="selling_price_type" id="bulkSellingPriceType" class="sf-input native-select" data-search="false" style="width:40%; padding:10px 8px; pointer-events:none; opacity:0.45;">
                                <option value="flat">Flat</option>
                                <option value="pct_inc">+ %</option>
                                <option value="pct_dec">− %</option>
                            </select>
                            <input type="number" step="0.01" name="selling_price_val" id="bulkSellingPriceVal" class="sf-input" style="pointer-events:none; opacity:0.45;" placeholder="0.00">
                        </div>
                    </div>

                    <!-- B2B Price -->
                    <div class="bulk-field">
                        <label class="bulk-field-head">
                            <input type="checkbox" name="update_wholesale_price" value="1" id="bulkUpdateWholesalePrice" onchange="toggleBulkField('wholesale_price')" class="sf-check">
                            <span class="bulk-field-label">B2B Price</span>
                        </label>
                        <div class="bulk-row">
                            <select name="wholesale_price_type" id="bulkWholesalePriceType" class="sf-input native-select" data-search="false" style="width:40%; padding:10px 8px; pointer-events:none; opacity:0.45;">
                                <option value="flat">Flat</option>
                                <option value="pct_inc">+ %</option>
                                <option value="pct_dec">− %</option>
                            </select>
                            <input type="number" step="0.01" name="wholesale_price_val" id="bulkWholesalePriceVal" class="sf-input" style="pointer-events:none; opacity:0.45;" placeholder="0.00">
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="bulk-field">
                        <label class="bulk-field-head">
                            <input type="checkbox" name="update_status" value="1" id="bulkUpdateStatus" onchange="toggleBulkField('status')" class="sf-check">
                            <span class="bulk-field-label">Status</span>
                        </label>
                        <select name="status" id="bulkStatusSelect" class="sf-input native-select" data-search="false" style="pointer-events:none; opacity:0.45;">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>

                </div>
            </div>
            <div class="modal-foot">
                <button type="button" onclick="closeBulkEditModal()" class="sf-btn neutral">Cancel</button>
                <button type="submit" id="bulkSubmitBtn" class="sf-btn primary">
                    <i id="bulkBtnSpinner" class="fa-solid fa-spinner spin hidden" style="margin-right:6px;"></i>Apply
                </button>
            </div>
        </form>
    </div>
</div>

</div><!-- /inv-root -->

<script>
window.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    if (!urlParams.has('per_page') && document.getElementById('perPageInput').value != '1000') {
        document.getElementById('perPageInput').value = '1000';
        applyAjaxFilters();
    }
});

let qvImages = [];
let qvCurrentIndex = 0;
let qvAutoRotateInterval = null;

function renderQvCarousel() {
    const track = document.getElementById('qv-carousel-track');
    const dotsContainer = document.getElementById('qv-carousel-dots');
    if (!track || !dotsContainer) return;

    track.innerHTML = '';
    dotsContainer.innerHTML = '';

    if (!qvImages || qvImages.length === 0) {
        track.innerHTML = `<div class="qv-carousel-slide"><img src="https://placehold.co/300x300/f2f2f7/8e8e93?text=No+Img" alt=""></div>`;
        return;
    }

    qvImages.forEach((imgSrc, idx) => {
        const slide = document.createElement('div');
        slide.className = 'qv-carousel-slide';
        slide.innerHTML = `<img src="${imgSrc}" alt="" onerror="this.src='https://placehold.co/300x300/f2f2f7/8e8e93?text=Err'">`;
        track.appendChild(slide);

        const dot = document.createElement('button');
        dot.type = 'button';
        dot.className = `qv-carousel-dot ${idx === 0 ? 'active' : ''}`;
        dot.onclick = () => goToQvImage(idx);
        dotsContainer.appendChild(dot);
    });

    qvCurrentIndex = 0;
    updateQvCarouselPosition();
    startQvAutoRotate();
}

function updateQvCarouselPosition() {
    const track = document.getElementById('qv-carousel-track');
    if (!track) return;
    track.style.transform = `translateX(-${qvCurrentIndex * 100}%)`;

    const dots = document.querySelectorAll('.qv-carousel-dot');
    dots.forEach((dot, idx) => {
        if (idx === qvCurrentIndex) {
            dot.classList.add('active');
        } else {
            dot.classList.remove('active');
        }
    });
}

function goToQvImage(index) {
    if (index < 0) {
        qvCurrentIndex = qvImages.length - 1;
    } else if (index >= qvImages.length) {
        qvCurrentIndex = 0;
    } else {
        qvCurrentIndex = index;
    }
    updateQvCarouselPosition();
    resetQvAutoRotate();
}

function prevQvImage() {
    if (!qvImages || qvImages.length <= 1) return;
    goToQvImage(qvCurrentIndex - 1);
}

function nextQvImage() {
    if (!qvImages || qvImages.length <= 1) return;
    goToQvImage(qvCurrentIndex + 1);
}

function startQvAutoRotate() {
    stopQvAutoRotate();
    if (qvImages && qvImages.length > 1) {
        qvAutoRotateInterval = setInterval(() => {
            qvCurrentIndex = (qvCurrentIndex + 1) % qvImages.length;
            updateQvCarouselPosition();
        }, 3000);
    }
}

function stopQvAutoRotate() {
    if (qvAutoRotateInterval) {
        clearInterval(qvAutoRotateInterval);
        qvAutoRotateInterval = null;
    }
}

function resetQvAutoRotate() {
    startQvAutoRotate();
}

function openQuickView(btn) {
    const id    = btn.dataset.id;
    const name  = btn.dataset.name;
    const sku   = btn.dataset.sku;
    const code  = btn.dataset.code;
    const price = btn.dataset.price;
    const b2b   = btn.dataset.b2b;
    const qty   = parseInt(btn.dataset.qty, 10);
    const desc  = btn.dataset.desc;
    const category = btn.dataset.category;
    const brand = btn.dataset.brand;
    const status = btn.dataset.status || 'active';

    try {
        qvImages = JSON.parse(btn.dataset.images || '[]');
    } catch (e) {
        qvImages = [btn.dataset.img || ''];
    }
    if ((!qvImages || qvImages.length === 0) && btn.dataset.img) {
        qvImages = [btn.dataset.img];
    }

    document.getElementById('qv-name').textContent  = name;
    document.getElementById('qv-sku').textContent   = 'SKU: ' + sku + (code ? ' · ' + code : '');
    document.getElementById('qv-price').textContent = 'LKR ' + price;
    document.getElementById('qv-b2b').textContent   = 'LKR ' + b2b;
    document.getElementById('qv-category').innerHTML = '<i class="fa-solid fa-tag"></i> ' + (category || 'Uncategorized');
    document.getElementById('qv-brand').innerHTML = '<i class="fa-solid fa-building"></i> ' + (brand || 'N/A');

    const qvQty = document.getElementById('qv-qty');
    qvQty.textContent = qty;

    let badge = '';
    if (status === 'inactive') {
        badge = '<span class="sf-badge badge-out"><span class="dot"></span>Inactive</span>';
        qvQty.style.color = 'var(--t-secondary)';
    } else if (qty <= 0)     { badge = '<span class="sf-badge badge-out"><span class="dot"></span>Out of stock</span>';  qvQty.style.color = 'var(--c-red)'; }
    else if (qty <= 5){ badge = '<span class="sf-badge badge-low"><span class="dot"></span>Low stock</span>';    qvQty.style.color = 'var(--c-orange)'; }
    else              { badge = '<span class="sf-badge badge-active"><span class="dot"></span>Active</span>';   qvQty.style.color = 'var(--t-primary)'; }
    document.getElementById('qv-status').innerHTML = badge;

    document.getElementById('qv-desc').textContent = desc || 'No description available.';
    document.getElementById('qv-edit-link').href   = '<?= APP_URL ?>/inventory/edit/' + id;
    document.getElementById('qv-ledger-link').href = '<?= APP_URL ?>/stockledger/product/' + id;

    renderQvCarousel();

    document.getElementById('quickViewPanel').classList.add('open');
}

function closeQuickView() {
    document.getElementById('quickViewPanel').classList.remove('open');
    stopQvAutoRotate();
}

function selectCategory(val, name) {
    document.getElementById('categoryInput').value    = val;
    document.getElementById('cat-dropdown-val').textContent = name || 'All';
    document.activeElement.blur();
    document.getElementById('currentPageInput').value = '1';
    applyAjaxFilters();
}

function selectStatus(val, name) {
    document.getElementById('stockStatusInput').value       = val;
    document.getElementById('status-dropdown-val').textContent = name || 'All';
    document.activeElement.blur();
    document.getElementById('currentPageInput').value = '1';
    applyAjaxFilters();
}

function selectItemStatus(val, name) {
    document.getElementById('itemStatusInput').value       = val;
    document.getElementById('item-status-dropdown-val').textContent = name || 'All';
    document.activeElement.blur();
    document.getElementById('currentPageInput').value = '1';
    applyAjaxFilters();
}

let searchTimeout = null;
function triggerSearchDelay() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        document.getElementById('currentPageInput').value = '1';
        applyAjaxFilters();
    }, 350);
}

function applyAjaxFilters() {
    const form   = document.getElementById('filterForm');
    const loader = document.getElementById('table-loader');
    if (loader) loader.classList.add('active');

    const formData     = new FormData(form);
    const paramsToKeep = new FormData();
    for (let [key, value] of formData.entries()) {
        if (!['selected_items[]','update_category','update_selling_price','update_wholesale_price','update_status'].includes(key)) {
            paramsToKeep.append(key, value);
        }
    }

    const queryParams = new URLSearchParams(paramsToKeep).toString();
    const requestUrl  = form.getAttribute('action') + '?' + queryParams;

    fetch(requestUrl)
        .then(r => { if (!r.ok) throw new Error('Network error'); return r.text(); })
        .then(html => {
            const doc      = new DOMParser().parseFromString(html, 'text/html');
            const newTable = doc.getElementById('table-container');
            const oldTable = document.getElementById('table-container');
            if (newTable && oldTable) oldTable.innerHTML = newTable.innerHTML;

            ['stat-total-items','stat-low-stock','stat-out-of-stock','matching-count'].forEach(id => {
                const n = doc.getElementById(id), o = document.getElementById(id);
                if (n && o) o.innerHTML = n.innerHTML;
            });

            window.history.pushState({ path: requestUrl }, '', requestUrl);
            clearSelection();
        })
        .catch(err => console.error('Filter error:', err))
        .finally(() => { if (loader) loader.classList.remove('active'); });
}

function navigatePage(n) {
    document.getElementById('currentPageInput').value = n;
    applyAjaxFilters();
}

function updatePageSize(size) {
    document.getElementById('perPageInput').value    = size;
    document.getElementById('currentPageInput').value = '1';
    applyAjaxFilters();
}

function clearAllFilters() {
    document.getElementById('searchInput').value   = '';
    document.getElementById('minPriceInput').value = '';
    document.getElementById('maxPriceInput').value = '';
    selectStatus('', 'All');
    selectItemStatus('', 'All');
    selectCategory('', 'All');
}

function openCsvModal()  { 
    console.log('[CSV Import] Modal opened');
    document.getElementById('csvImportModal').classList.remove('hidden'); 
}
function closeCsvModal() { document.getElementById('csvImportModal').classList.add('hidden'); }

// CSV Import Debug Functions
function logFileSelection(input) {
    console.log('[CSV Import] File selected:', input.files[0]);
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const fileInfo = {
            name: file.name,
            size: file.size + ' bytes',
            type: file.type,
            lastModified: new Date(file.lastModified).toLocaleString()
        };
        console.log('[CSV Import] File details:', fileInfo);
        
        const debugInfo = document.getElementById('importDebugInfo');
        const fileInfoText = document.getElementById('fileInfoText');
        if (debugInfo && fileInfoText) {
            fileInfoText.textContent = JSON.stringify(fileInfo, null, 2);
            debugInfo.style.display = 'block';
        }
    }
}

function handleCsvImportSubmit(event) {
    event.preventDefault();
    
    const fileInput = document.getElementById('csvFileInput');
    const importBtn = document.getElementById('csvImportBtn');
    
    console.log('[CSV Import] Form submit triggered');
    console.log('[CSV Import] File input:', fileInput);
    console.log('[CSV Import] File selected:', fileInput.files.length > 0 ? fileInput.files[0].name : 'No file');
    
    if (!fileInput.files || fileInput.files.length === 0) {
        console.error('[CSV Import] ERROR: No file selected!');
        alert('Please select a CSV file first.');
        return false;
    }
    
    const form = document.getElementById('csvImportForm');
    const formData = new FormData(form);
    
    console.log('[CSV Import] FormData entries:');
    for (let [key, value] of formData.entries()) {
        if (value instanceof File) {
            console.log('  ' + key + ':', value.name, '(' + value.size + ' bytes)');
        } else {
            console.log('  ' + key + ':', value);
        }
    }
    
    // Disable button and show loading state
    importBtn.disabled = true;
    importBtn.innerHTML = '<i class="fa-solid fa-spinner spin"></i> Importing...';
    
    console.log('[CSV Import] Sending POST request to:', form.action);
    
    // Submit the form
    fetch(form.action + '?ajax=1', {
        method: 'POST',
        body: formData,
        headers: {
            'Accept': 'application/json'
        }
    })
    .then(response => {
        console.log('[CSV Import] Response status:', response.status);
        console.log('[CSV Import] Response ok:', response.ok);
        
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            return response.json().then(data => {
                console.log('[CSV Import] JSON Response data:', data);
                if (response.ok) {
                    console.log('[CSV Import] Import completed successfully, reloading...');
                    window.location.reload();
                } else {
                    const errMsgs = data.errors ? data.errors.join('\n') : 'Unknown error';
                    throw new Error(errMsgs);
                }
            });
        } else {
            return response.text().then(text => {
                console.error('[CSV Import] Non-JSON Server response:', text);
                if (response.ok) {
                    window.location.reload();
                } else {
                    throw new Error('Server returned ' + response.status + ': ' + text.substring(0, 200));
                }
            });
        }
    })
    .catch(error => {
        console.error('[CSV Import] Fetch error:', error);
        alert('Import failed:\n' + error.message);
        importBtn.disabled = false;
        importBtn.innerHTML = 'Import';
    });
    
    return false; // Prevent default form submission
}

let activeDeleteId = null;

function confirmDelete(id, name) {
    activeDeleteId = id;
    document.getElementById('deleteItemId').value    = id;
    document.getElementById('deleteItemName').textContent = name;
    const err = document.getElementById('deleteErrorContainer');
    if (err) { err.classList.add('hidden'); err.textContent = ''; }
    const pw = document.getElementById('deleteAdminPassword');
    if (pw) pw.value = '';
    document.getElementById('deleteProductModal').classList.remove('hidden');
}

function closeDeleteModal() {
    document.getElementById('deleteProductModal').classList.add('hidden');
    activeDeleteId = null;
}

function submitDeleteProduct(e) {
    e.preventDefault();
    if (!activeDeleteId) return;

    const form    = document.getElementById('deleteProductForm');
    const btn     = document.getElementById('deleteSubmitBtn');
    const spinner = document.getElementById('deleteBtnSpinner');
    const err     = document.getElementById('deleteErrorContainer');

    if (btn)     btn.disabled = true;
    if (spinner) spinner.classList.remove('hidden');
    if (err)     { err.classList.add('hidden'); err.textContent = ''; }

    fetch('<?php echo APP_URL; ?>/inventory/delete/' + activeDeleteId, { method: 'POST', body: new FormData(form) })
        .then(r => { if (!r.ok) throw new Error('Failed'); return r.json(); })
        .then(data => {
            if (data.success) { closeDeleteModal(); applyAjaxFilters(); }
            else if (err) { err.textContent = data.error || 'Authorization failed.'; err.classList.remove('hidden'); }
        })
        .catch(ex => { if (err) { err.textContent = ex.message; err.classList.remove('hidden'); } })
        .finally(() => { if (btn) btn.disabled = false; if (spinner) spinner.classList.add('hidden'); });
}

function toggleSelectAll(cb) {
    document.querySelectorAll('.item-select-checkbox').forEach(c => c.checked = cb.checked);
    updateSelection();
}

function updateSelection() {
    const checkboxes  = document.querySelectorAll('.item-select-checkbox');
    const selected    = [...checkboxes].filter(c => c.checked);
    const toolbar     = document.getElementById('bulkEditToolbar');
    const badge       = document.getElementById('selectedCountBadge');
    const selectAllCb = document.getElementById('selectAllCheckbox');

    if (selected.length > 0) {
        if (badge)   badge.textContent  = selected.length;
        if (toolbar) toolbar.classList.remove('hidden');
        if (selectAllCb) selectAllCb.checked = (selected.length === checkboxes.length);
    } else {
        if (toolbar) toolbar.classList.add('hidden');
        if (selectAllCb) selectAllCb.checked = false;
    }
}

function clearSelection() {
    document.querySelectorAll('.item-select-checkbox').forEach(c => c.checked = false);
    const cb = document.getElementById('selectAllCheckbox');
    if (cb) cb.checked = false;
    updateSelection();
}

function openBulkEditModal() {
    const form = document.getElementById('bulkEditForm');
    form.querySelectorAll('input[name="item_ids[]"]').forEach(i => i.remove());

    let count = 0;
    document.querySelectorAll('.item-select-checkbox').forEach(cb => {
        if (cb.checked) {
            count++;
            const h = document.createElement('input');
            h.type = 'hidden'; h.name = 'item_ids[]'; h.value = cb.value;
            form.appendChild(h);
        }
    });
    document.getElementById('bulkSelectedCount').textContent = count;

    ['category','selling_price','wholesale_price','status'].forEach(f => {
        const ids = {
            category:       'bulkUpdateCategory',
            selling_price:  'bulkUpdateSellingPrice',
            wholesale_price:'bulkUpdateWholesalePrice',
            status:         'bulkUpdateStatus',
        };
        const cb = document.getElementById(ids[f]);
        if (cb) cb.checked = false;
        toggleBulkField(f);
    });

    const err = document.getElementById('bulkEditErrorContainer');
    if (err) { err.classList.add('hidden'); err.textContent = ''; }
    document.getElementById('bulkEditModal').classList.remove('hidden');
}

function closeBulkEditModal() {
    document.getElementById('bulkEditModal').classList.add('hidden');
}

function toggleBulkField(field) {
    const map = {
        category:       { cb: 'bulkUpdateCategory',      els: ['bulkCategorySelect'] },
        selling_price:  { cb: 'bulkUpdateSellingPrice',  els: ['bulkSellingPriceType','bulkSellingPriceVal'] },
        wholesale_price:{ cb: 'bulkUpdateWholesalePrice',els: ['bulkWholesalePriceType','bulkWholesalePriceVal'] },
        status:         { cb: 'bulkUpdateStatus',        els: ['bulkStatusSelect'] },
    };
    const cfg = map[field];
    if (!cfg) return;
    const isOn = document.getElementById(cfg.cb)?.checked;
    cfg.els.forEach(id => {
        const el = document.getElementById(id);
        if (el) { el.style.pointerEvents = isOn ? 'auto' : 'none'; el.style.opacity = isOn ? '1' : '0.45'; }
    });
}

function submitBulkEdit(e) {
    e.preventDefault();

    const form    = document.getElementById('bulkEditForm');
    const btn     = document.getElementById('bulkSubmitBtn');
    const spinner = document.getElementById('bulkBtnSpinner');
    const err     = document.getElementById('bulkEditErrorContainer');

    if (btn)     btn.disabled = true;
    if (spinner) spinner.classList.remove('hidden');
    if (err)     { err.classList.add('hidden'); err.textContent = ''; }

    fetch('<?php echo APP_URL; ?>/inventory/bulkUpdate', { method: 'POST', body: new FormData(form) })
        .then(r => { if (!r.ok) throw new Error('Bulk update failed'); return r.json(); })
        .then(data => {
            if (data.success) { closeBulkEditModal(); clearSelection(); applyAjaxFilters(); }
            else if (err) { err.textContent = data.error || 'Update failed.'; err.classList.remove('hidden'); }
        })
        .catch(ex => { if (err) { err.textContent = ex.message; err.classList.remove('hidden'); } })
        .finally(() => { if (btn) btn.disabled = false; if (spinner) spinner.classList.add('hidden'); });
}
</script>