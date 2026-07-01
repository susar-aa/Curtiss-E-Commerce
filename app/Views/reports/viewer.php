<?php
$reportKey = $data['reportKey'];
$metadata = $data['metadata'];
$customers = $data['customers'];
$suppliers = $data['suppliers'];
$products = $data['products'];
$warehouses = $data['warehouses'];
$routes = $data['routes'];
$categories = $data['categories'];
$brands = $data['brands'] ?? [];
$groups = $data['groups'] ?? [];
$vehicles = $data['vehicles'] ?? [];
$drivers = $data['drivers'] ?? [];
$partners = $data['partners'] ?? [];
$territories = $data['territories'] ?? [];
$reps = $data['reps'] ?? [];
$payment_methods = $data['payment_methods'] ?? [];
$statuses = $data['statuses'] ?? [];
?>

<style>
    .viewer-layout {
        display: flex;
        gap: 20px;
        padding: 20px;
        max-width: 1600px;
        margin: 0 auto;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    /* Filter Sidebar */
    .filter-sidebar {
        width: 320px;
        flex-shrink: 0;
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .card {
        background: #ffffff;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
        padding: 20px;
    }

    .card-title {
        font-size: 15px;
        font-weight: 700;
        color: #1e293b;
        margin: 0 0 15px 0;
        display: flex;
        align-items: center;
        gap: 8px;
        border-bottom: 1px solid #f1f5f9;
        padding-bottom: 10px;
    }

    .card-title i {
        color: #0066cc;
        font-size: 18px;
    }

    .form-group {
        margin-bottom: 15px;
    }

    .form-group:last-child {
        margin-bottom: 0;
    }

    .form-group label {
        display: block;
        font-size: 12.5px;
        font-weight: 600;
        color: #475569;
        margin-bottom: 6px;
    }

    .form-control {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        font-size: 13.5px;
        background-color: #fff;
        color: #1e293b;
        transition: border-color 0.2s;
    }

    .form-control:focus {
        border-color: #0066cc;
        outline: none;
    }

    .btn {
        width: 100%;
        padding: 10px;
        border-radius: 6px;
        font-size: 13.5px;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: all 0.2s;
        border: none;
    }

    .btn-primary {
        background: #0066cc;
        color: #fff;
    }

    .btn-primary:hover {
        background: #0052a3;
    }

    .btn-secondary {
        background: #f1f5f9;
        color: #475569;
        border: 1px solid #cbd5e1;
        margin-top: 10px;
    }

    .btn-secondary:hover {
        background: #e2e8f0;
    }

    /* Main Preview Panel */
    .preview-panel {
        flex-grow: 1;
        display: flex;
        flex-direction: column;
        gap: 20px;
        min-width: 0; /* Prevents table overflow from breaking flex box */
    }

    .preview-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        flex-wrap: wrap;
        gap: 15px;
    }

    .report-meta h1 {
        font-size: 24px;
        font-weight: 700;
        color: #1e293b;
        margin: 0;
    }

    .report-meta .breadcrumb {
        font-size: 13px;
        color: #64748b;
        margin-bottom: 5px;
    }

    .report-actions {
        display: flex;
        gap: 10px;
    }

    .action-btn {
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        padding: 8px 14px;
        font-size: 13px;
        font-weight: 600;
        color: #475569;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 6px;
        position: relative;
    }

    .action-btn:hover {
        background: #f8fafc;
        border-color: #94a3b8;
    }

    .dropdown-menu {
        display: none;
        position: absolute;
        top: 100%;
        right: 0;
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
        z-index: 100;
        min-width: 140px;
        margin-top: 5px;
    }

    .dropdown-menu a {
        display: block;
        padding: 8px 12px;
        color: #475569;
        text-decoration: none;
        font-size: 13px;
    }

    .dropdown-menu a:hover {
        background: #f1f5f9;
        color: #0066cc;
    }

    /* Simulation mode warning alert */
    .sim-alert {
        background: #fef2f2;
        border: 1px solid #fee2e2;
        border-left: 4px solid #ef4444;
        border-radius: 8px;
        padding: 12px 16px;
        display: flex;
        align-items: center;
        gap: 12px;
        color: #991b1b;
        font-size: 13.5px;
    }

    .sim-alert i {
        font-size: 20px;
    }

    /* Search and filter controls inside table card */
    .table-toolbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
        gap: 15px;
    }

    .search-input-wrapper {
        position: relative;
        width: 280px;
    }

    .search-input-wrapper i {
        position: absolute;
        left: 10px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
    }

    .search-input-wrapper input {
        padding-left: 32px;
    }

    /* Elegant Responsive Table Styles */
    .table-container {
        overflow-x: auto;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
    }

    .report-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13.5px;
        text-align: left;
    }

    .report-table th {
        background: #f8fafc;
        color: #475569;
        font-weight: 650;
        padding: 12px 16px;
        border-bottom: 2px solid #e2e8f0;
        cursor: pointer;
        user-select: none;
        position: relative;
    }

    .report-table th:hover {
        background: #f1f5f9;
    }

    .report-table th.sorted-asc::after {
        content: ' ▲';
        font-size: 9px;
        color: #0066cc;
    }

    .report-table th.sorted-desc::after {
        content: ' ▼';
        font-size: 9px;
        color: #0066cc;
    }

    .report-table td {
        padding: 12px 16px;
        border-bottom: 1px solid #e2e8f0;
        color: #1e293b;
    }

    .report-table tr:hover {
        background: #f8fafc;
    }

    /* Subtotal & Grand Total Rows */
    .total-row {
        font-weight: 750;
        background: #f8fafc;
    }

    .total-row td {
        border-top: 2px double #cbd5e1;
        border-bottom: 2px double #cbd5e1;
        color: #0f172a;
    }

    /* Drilldown badge links */
    .drilldown-link {
        color: #0066cc;
        font-weight: 600;
        text-decoration: none;
        border-bottom: 1px dashed #0066cc;
    }

    .drilldown-link:hover {
        color: #0047b3;
        border-bottom-style: solid;
    }

    .badge-completed {
        background: #dcfce7;
        color: #15803d;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
    }

    .badge-pending {
        background: #fef3c7;
        color: #b45309;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
    }

    /* Pagination Footer */
    .pagination-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 15px;
        padding-top: 15px;
        border-top: 1px solid #e2e8f0;
    }

    .pagination-info {
        font-size: 13px;
        color: #64748b;
    }

    .pagination-controls {
        display: flex;
        gap: 5px;
        align-items: center;
    }

    .page-btn {
        background: #fff;
        border: 1px solid #cbd5e1;
        padding: 6px 12px;
        font-size: 13px;
        border-radius: 4px;
        cursor: pointer;
    }

    .page-btn:hover:not(:disabled) {
        background: #f1f5f9;
    }

    .page-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .page-btn.active {
        background: #0066cc;
        color: #fff;
        border-color: #0066cc;
    }

    /* Spinner loading overlay */
    .loading-overlay {
        position: relative;
    }

    .spinner {
        display: none;
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        border: 4px solid #f3f3f3;
        border-top: 4px solid #0066cc;
        border-radius: 50%;
        width: 40px;
        height: 40px;
        animation: spin 1s linear infinite;
        z-index: 10;
    }

    @keyframes spin {
        0% { transform: translate(-50%, -50%) rotate(0deg); }
        100% { transform: translate(-50%, -50%) rotate(360deg); }
    }

    /* Print Stylesheet integration */
    @media print {
        header, footer, .filter-sidebar, .preview-header, .table-toolbar, .pagination-footer, .no-print {
            display: none !important;
        }
        body, .viewer-layout, .preview-panel {
            background: #fff !important;
            padding: 0 !important;
            margin: 0 !important;
            width: 100% !important;
        }
        .table-container {
            border: none !important;
        }
        .report-table th {
            background: #e2e8f0 !important;
            color: #000 !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .report-table td {
            border-bottom: 1px solid #cbd5e1 !important;
        }
    }

    @media (prefers-color-scheme: dark) {
        .card {
            background: #1e1e1e;
            border-color: #2e2e2e;
        }
        .card-title {
            color: #e2e8f0;
            border-color: #2e2e2e;
        }
        .form-control {
            background-color: #252525;
            border-color: #3e3e3e;
            color: #e2e8f0;
        }
        .action-btn {
            background-color: #252525;
            border-color: #3e3e3e;
            color: #cbd5e1;
        }
        .action-btn:hover {
            background-color: #2d2d2d;
        }
        .dropdown-menu {
            background-color: #1e1e1e;
            border-color: #3e3e3e;
        }
        .dropdown-menu a {
            color: #cbd5e1;
        }
        .dropdown-menu a:hover {
            background-color: #2d2d2d;
        }
        .report-meta h1 {
            color: #ffffff;
        }
        .report-table th {
            background-color: #252525;
            color: #cbd5e1;
            border-color: #3e3e3e;
        }
        .report-table td {
            color: #cbd5e1;
            border-color: #2e2e2e;
        }
        .report-table tr:hover {
            background-color: rgba(255, 255, 255, 0.03);
        }
        .total-row {
            background-color: #252525;
        }
        .total-row td {
            color: #ffffff;
            border-top-color: #3e3e3e;
            border-bottom-color: #3e3e3e;
        }
    }
    
    /* Drilldown & Interactive UI Styles */
    .interactive-cell {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        position: relative;
        width: 100%;
    }

    .drilldown-trigger {
        color: #0066cc;
        font-weight: 600;
        cursor: pointer;
        border-bottom: 1px dashed #0066cc;
    }

    .drilldown-trigger:hover {
        color: #0047b3;
        border-bottom-style: solid;
    }

    .cell-actions {
        opacity: 0;
        display: inline-flex;
        gap: 4px;
        margin-left: auto;
        transition: opacity 0.2s ease;
    }

    .interactive-cell:hover .cell-actions {
        opacity: 1;
    }

    .cell-action-btn {
        background: none;
        border: none;
        padding: 2px;
        color: #64748b;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        border-radius: 4px;
        text-decoration: none;
    }

    .cell-action-btn:hover {
        background: #f1f5f9;
        color: #0066cc;
    }

    /* Side Panel Quick View */
    .quickview-panel {
        position: fixed;
        top: 0;
        right: -480px;
        width: 480px;
        height: 100%;
        background: #ffffff;
        box-shadow: -5px 0 25px rgba(0,0,0,0.15);
        z-index: 1000;
        transition: right 0.3s ease;
        display: flex;
        flex-direction: column;
        border-left: 1px solid #e2e8f0;
    }

    .quickview-panel.active {
        right: 0;
    }

    .quickview-header {
        padding: 16px 20px;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #f8fafc;
    }

    .quickview-header h3 {
        margin: 0;
        font-size: 15px;
        font-weight: 700;
        color: #0f172a;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .quickview-close {
        background: none;
        border: none;
        font-size: 22px;
        color: #64748b;
        cursor: pointer;
        line-height: 1;
        padding: 4px;
        border-radius: 50%;
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
    }

    .quickview-close:hover {
        background: #fee2e2;
        color: #ef4444;
    }

    .quickview-body {
        padding: 20px;
        overflow-y: auto;
        flex-grow: 1;
        position: relative;
    }

    .quickview-backdrop {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(15, 23, 42, 0.4);
        z-index: 999;
        display: none;
        backdrop-filter: blur(2px);
    }

    .quickview-backdrop.active {
        display: block;
    }

    .quickview-spinner {
        display: none;
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        border: 4px solid #f3f3f3;
        border-top: 4px solid #0066cc;
        border-radius: 50%;
        width: 40px;
        height: 40px;
        animation: spin 0.8s linear infinite;
        z-index: 10;
    }

    /* Quickview components styling */
    .qv-card {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
    }

    .qv-title {
        font-size: 13px;
        font-weight: 700;
        color: #475569;
        text-transform: uppercase;
        margin-bottom: 10px;
        border-bottom: 1px solid #e2e8f0;
        padding-bottom: 5px;
    }

    .qv-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
    }

    .qv-field {
        margin-bottom: 5px;
    }

    .qv-field label {
        display: block;
        font-size: 11px;
        color: #64748b;
        font-weight: 600;
        text-transform: uppercase;
    }

    .qv-field span {
        font-size: 13.5px;
        color: #0f172a;
        font-weight: 500;
    }

    .qv-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 12.5px;
        margin-top: 5px;
    }

    .qv-table th {
        text-align: left;
        background: #e2e8f0;
        padding: 6px 10px;
        color: #475569;
        font-weight: 600;
    }

    .qv-table td {
        padding: 8px 10px;
        border-bottom: 1px solid #e2e8f0;
        color: #1e293b;
    }

    .qv-table tr:hover {
        background: #f1f5f9;
    }

    .qv-badge-stat {
        display: inline-block;
        padding: 10px;
        border-radius: 6px;
        text-align: center;
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        color: #166534;
    }

    .qv-badge-stat.danger {
        background: #fef2f2;
        border: 1px solid #fecaca;
        color: #991b1b;
    }

    .qv-action-bar {
        display: flex;
        gap: 10px;
        margin-top: 15px;
    }

    .qv-btn {
        flex-grow: 1;
        padding: 8px 12px;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 600;
        text-align: center;
        cursor: pointer;
        text-decoration: none;
        border: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
    }

    .qv-btn-primary {
        background: #0066cc;
        color: white;
    }

    .qv-btn-primary:hover {
        background: #0052a3;
    }

    .qv-btn-secondary {
        background: #f1f5f9;
        color: #475569;
        border: 1px solid #cbd5e1;
    }

    .qv-btn-secondary:hover {
        background: #e2e8f0;
    }

    /* Dark Mode support */
    @media (prefers-color-scheme: dark) {
        .cell-action-btn:hover {
            background: #2d2d2d;
            color: #38bdf8;
        }
        .quickview-panel {
            background: #1e1e1e;
            border-left-color: #2e2e2e;
        }
        .quickview-header {
            background: #252525;
            border-bottom-color: #2e2e2e;
        }
        .quickview-header h3 {
            color: #ffffff;
        }
        .quickview-close:hover {
            background: #7f1d1d;
            color: #fca5a5;
        }
        .qv-card {
            background: #252525;
            border-color: #2e2e2e;
        }
        .qv-title {
            color: #cbd5e1;
            border-bottom-color: #2e2e2e;
        }
        .qv-field span {
            color: #ffffff;
        }
        .qv-table th {
            background: #2d2d2d;
            color: #cbd5e1;
        }
        .qv-table td {
            border-bottom-color: #2e2e2e;
            color: #cbd5e1;
        }
        .qv-table tr:hover {
            background: rgba(255,255,255,0.03);
        }
        .qv-btn-secondary {
            background: #252525;
            color: #cbd5e1;
            border-color: #3e3e3e;
        }
        .qv-btn-secondary:hover {
            background: #2d2d2d;
        }
    }
</style>

<div class="viewer-layout">
    <!-- Filter Panel Sidebar -->
    <div class="filter-sidebar no-print">
        <!-- Main Filter Selection -->
        <div class="card">
            <div class="card-title">
                <i class="ph ph-sliders"></i>
                <span>Report Filters</span>
            </div>
            <form id="filterForm">
                <?php if (in_array('date_range', $metadata['filters'] ?? [])): ?>
                    <div class="form-group">
                        <label>Start Date</label>
                        <input type="date" class="form-control" name="start_date" id="filter_start_date" value="<?= date('Y-m-01') ?>">
                    </div>
                    <div class="form-group">
                        <label>End Date</label>
                        <input type="date" class="form-control" name="end_date" id="filter_end_date" value="<?= date('Y-m-d') ?>">
                    </div>
                <?php endif; ?>

                <?php if (in_array('customer', $metadata['filters'] ?? [])): ?>
                    <div class="form-group">
                        <label>Customer</label>
                        <select class="form-control" name="customer" id="filter_customer">
                            <option value="">-- All Customers --</option>
                            <?php foreach ($customers as $c): ?>
                                <option value="<?= $c->id ?>"><?= htmlspecialchars($c->name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <?php if (in_array('supplier', $metadata['filters'] ?? [])): ?>
                    <div class="form-group">
                        <label>Supplier / Vendor</label>
                        <select class="form-control" name="supplier" id="filter_supplier">
                            <option value="">-- All Suppliers --</option>
                            <?php foreach ($suppliers as $s): ?>
                                <option value="<?= $s->id ?>"><?= htmlspecialchars($s->name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <?php if (in_array('product', $metadata['filters'] ?? [])): ?>
                    <div class="form-group">
                        <label>Product</label>
                        <select class="form-control" name="product" id="filter_product">
                            <option value="">-- All Products --</option>
                            <?php foreach ($products as $p): ?>
                                <option value="<?= $p->id ?>"><?= htmlspecialchars($p->name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <?php if (in_array('warehouse', $metadata['filters'] ?? [])): ?>
                    <div class="form-group">
                        <label>Warehouse</label>
                        <select class="form-control" name="warehouse" id="filter_warehouse">
                            <option value="">-- All Warehouses --</option>
                            <?php foreach ($warehouses as $w): ?>
                                <option value="<?= $w->id ?>"><?= htmlspecialchars($w->name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <?php if (in_array('category', $metadata['filters'] ?? [])): ?>
                    <div class="form-group">
                        <label>Item Category</label>
                        <select class="form-control" name="category" id="filter_category">
                            <option value="">-- All Categories --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat->id ?>"><?= htmlspecialchars($cat->name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <?php if (in_array('route', $metadata['filters'] ?? [])): ?>
                    <div class="form-group">
                        <label>Route</label>
                        <select class="form-control" name="route" id="filter_route">
                            <option value="">-- All Routes --</option>
                            <?php foreach ($routes as $r): ?>
                                <option value="<?= $r->id ?>"><?= htmlspecialchars($r->route_name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <?php if (in_array('rep', $metadata['filters'] ?? [])): ?>
                    <div class="form-group">
                        <label>Sales Rep</label>
                        <select class="form-control" name="rep" id="filter_rep">
                            <option value="">-- All Reps --</option>
                            <?php foreach ($reps as $r): ?>
                                <option value="<?= $r->id ?>"><?= htmlspecialchars($r->name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <?php if (in_array('payment_method', $metadata['filters'] ?? [])): ?>
                    <div class="form-group">
                        <label>Payment Method</label>
                        <select class="form-control" name="payment_method" id="filter_payment_method">
                            <option value="">-- All Methods --</option>
                            <?php foreach ($payment_methods as $pm): ?>
                                <option value="<?= $pm->id ?>"><?= htmlspecialchars($pm->name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <?php if (in_array('status', $metadata['filters'] ?? [])): ?>
                    <div class="form-group">
                        <label>Status</label>
                        <select class="form-control" name="status" id="filter_status">
                            <option value="">-- All Statuses --</option>
                            <?php foreach ($statuses as $st): ?>
                                <option value="<?= $st->id ?>"><?= htmlspecialchars($st->name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <?php if (in_array('brand', $metadata['filters'] ?? [])): ?>
                    <div class="form-group">
                        <label>Brand</label>
                        <select class="form-control" name="brand" id="filter_brand">
                            <option value="">-- All Brands --</option>
                            <?php foreach ($brands as $b): ?>
                                <option value="<?= htmlspecialchars($b->brand) ?>"><?= htmlspecialchars($b->brand) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <?php if (in_array('group', $metadata['filters'] ?? [])): ?>
                    <div class="form-group">
                        <label>Customer Group</label>
                        <select class="form-control" name="group" id="filter_group">
                            <option value="">-- All Groups --</option>
                            <?php foreach ($groups as $g): ?>
                                <option value="<?= htmlspecialchars($g->name) ?>"><?= htmlspecialchars($g->name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <?php if (in_array('vehicle', $metadata['filters'] ?? [])): ?>
                    <div class="form-group">
                        <label>Vehicle</label>
                        <select class="form-control" name="vehicle" id="filter_vehicle">
                            <option value="">-- All Vehicles --</option>
                            <?php foreach ($vehicles as $v): ?>
                                <option value="<?= htmlspecialchars($v->vehicle_number) ?>"><?= htmlspecialchars($v->vehicle_number) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <?php if (in_array('driver', $metadata['filters'] ?? [])): ?>
                    <div class="form-group">
                        <label>Driver</label>
                        <select class="form-control" name="driver" id="filter_driver">
                            <option value="">-- All Drivers --</option>
                            <?php foreach ($drivers as $d): ?>
                                <option value="<?= htmlspecialchars($d->name) ?>"><?= htmlspecialchars($d->name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <?php if (in_array('partner', $metadata['filters'] ?? [])): ?>
                    <div class="form-group">
                        <label>Partner / Helper</label>
                        <select class="form-control" name="partner" id="filter_partner">
                            <option value="">-- All Partners --</option>
                            <?php foreach ($partners as $p): ?>
                                <option value="<?= htmlspecialchars($p->name) ?>"><?= htmlspecialchars($p->name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <?php if (in_array('territory', $metadata['filters'] ?? [])): ?>
                    <div class="form-group">
                        <label>Territory</label>
                        <select class="form-control" name="territory" id="filter_territory">
                            <option value="">-- All Territories --</option>
                            <?php foreach ($territories as $t): ?>
                                <option value="<?= htmlspecialchars($t->territory) ?>"><?= htmlspecialchars($t->territory) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <button type="button" class="btn btn-primary" onclick="loadReportData(1)" style="margin-top: 15px;">
                    <i class="ph ph-arrow-clockwise"></i> Generate Report
                </button>
                <button type="button" class="btn btn-secondary" onclick="resetAllFilters()">
                    Reset Filters
                </button>
            </form>
        </div>
    </div>

    <!-- Main Report Preview Table -->
    <div class="preview-panel">
        <div class="preview-header">
            <div class="report-meta">
                <div class="breadcrumb">Reports / <?= htmlspecialchars(ucfirst($metadata['category'])) ?></div>
                <h1><?= htmlspecialchars($metadata['title']) ?></h1>
            </div>
            <div class="report-actions no-print">
                <!-- Share Button -->
                <div style="position:relative;">
                    <button class="action-btn" onclick="toggleDropdown('shareMenu')">
                        <i class="ph ph-share-network"></i> Share <i class="ph ph-caret-down"></i>
                    </button>
                    <div class="dropdown-menu" id="shareMenu">
                        <a href="javascript:void(0)" onclick="copyShareLink()"><i class="ph ph-link"></i> Copy Link</a>
                        <a href="javascript:void(0)" onclick="emailShare()"><i class="ph ph-envelope"></i> Email link</a>
                        <a href="javascript:void(0)" onclick="whatsappShare()"><i class="ph ph-whatsapp-logo"></i> WhatsApp Share</a>
                    </div>
                </div>

                <!-- Print Button -->
                <button class="action-btn" onclick="openPrintLayout()">
                    <i class="ph ph-printer"></i> Print
                </button>

                <!-- Export Dropdown -->
                <div style="position:relative;">
                    <button class="action-btn" onclick="toggleDropdown('exportMenu')">
                        <i class="ph ph-download"></i> Export <i class="ph ph-caret-down"></i>
                    </button>
                    <div class="dropdown-menu" id="exportMenu">
                        <a href="javascript:void(0)" onclick="triggerExport('excel')"><i class="ph ph-file-xls"></i> Excel (.xls)</a>
                        <a href="javascript:void(0)" onclick="triggerExport('csv')"><i class="ph ph-file-csv"></i> CSV (.csv)</a>
                        <a href="javascript:void(0)" onclick="triggerExport('word')"><i class="ph ph-file-doc"></i> Word (.doc)</a>
                        <a href="javascript:void(0)" onclick="triggerExport('xml')"><i class="ph ph-file-xml"></i> XML</a>
                        <a href="javascript:void(0)" onclick="triggerExport('json')"><i class="ph ph-file-code"></i> JSON</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Simulation mode warning alert -->
        <div class="sim-alert" id="simAlert" style="display:none;">
            <i class="ph ph-warning-circle"></i>
            <div>
                <strong>Simulation Mode Active:</strong> The table database relation for this report is currently undergoing system migration. Below values are high-fidelity mockups provided to preview formatting layout.
            </div>
        </div>

        <!-- Interactive Table Card -->
        <div class="card" style="padding: 15px; position:relative;">
            <div class="spinner" id="tableSpinner"></div>
            <div class="loading-overlay" id="tableOverlay">
                <div class="table-toolbar no-print">
                    <div class="search-input-wrapper">
                        <i class="ph ph-magnifying-glass"></i>
                        <input type="text" class="form-control" id="tableSearch" placeholder="Filter rows in view..." onkeyup="clientFilterRows()">
                    </div>
                    <div style="font-size: 13px; color: #64748b;">
                        Showing Page size: 
                        <select id="limitSelect" onchange="loadReportData(1)" style="padding:4px 8px; border-radius:4px; border:1px solid #cbd5e1;">
                            <option value="25">25 rows</option>
                            <option value="50" selected>50 rows</option>
                            <option value="100">100 rows</option>
                        </select>
                    </div>
                </div>

                <!-- Scrollable Table -->
                <div class="table-container" id="printableArea">
                    <table class="report-table" id="reportDataTable">
                        <thead>
                            <tr id="tableHeaders">
                                <!-- Headers injected dynamically by JS -->
                            </tr>
                        </thead>
                        <tbody id="tableBody">
                            <!-- Body rows injected dynamically -->
                        </tbody>
                        <tfoot id="tableFoot">
                            <!-- Totals injected dynamically -->
                        </tfoot>
                    </table>
                </div>

                <!-- Pagination footer controls -->
                <div class="pagination-footer no-print">
                    <div class="pagination-info" id="paginationInfo">
                        Showing 0 to 0 of 0 entries
                    </div>
                    <div class="pagination-controls" id="paginationControls">
                        <!-- Controls injected by JS -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Side Panel Quick View -->
<div class="quickview-panel no-print" id="quickviewPanel">
    <div class="quickview-header">
        <h3 id="quickviewTitle"><i class="ph ph-eye"></i> Quick View</h3>
        <button class="quickview-close" onclick="closeQuickView()">&times;</button>
    </div>
    <div class="quickview-body">
        <div class="quickview-spinner" id="quickviewSpinner"></div>
        <div id="quickviewContent">
            <!-- Dynamic content injected by javascript -->
        </div>
    </div>
</div>
<div class="quickview-backdrop no-print" id="quickviewBackdrop" onclick="closeQuickView()"></div>

<script>
    const reportKey = '<?= $reportKey ?>';
    const columnsMeta = <?= json_encode($metadata['columns']) ?>;
    let currentSortCol = null;
    let currentSortDir = 'ASC';
    let currentPage = 1;
    let totalEntries = 0;
    let currentRows = [];

    document.addEventListener('DOMContentLoaded', function() {
        // Render headers first
        const headerRow = document.getElementById('tableHeaders');
        headerRow.innerHTML = '';
        for (const [colKey, def] of Object.entries(columnsMeta)) {
            const th = document.createElement('th');
            th.textContent = def.label;
            if (def.align === 'right') {
                th.style.textAlign = 'right';
            }
            if (def.sortable) {
                th.style.cursor = 'pointer';
                th.onclick = function() { toggleTableSort(colKey); };
            }
            th.setAttribute('data-col-key', colKey);
            headerRow.appendChild(th);
        }

        // Initialize Breadcrumbs
        updateBreadcrumbs();

        // Load initial data
        loadReportData(1);

        // Click outside drops handler
        window.addEventListener('click', function(e) {
            if (!e.target.closest('.action-btn')) {
                document.querySelectorAll('.dropdown-menu').forEach(m => m.style.display = 'none');
            }
        });
    });

    function toggleDropdown(id) {
        document.querySelectorAll('.dropdown-menu').forEach(m => {
            if (m.id !== id) m.style.display = 'none';
        });
        const m = document.getElementById(id);
        m.style.display = m.style.display === 'block' ? 'none' : 'block';
    }

    function resetAllFilters() {
        document.getElementById('filterForm').reset();
        loadReportData(1);
    }

    function loadReportData(page) {
        currentPage = page;
        const spinner = document.getElementById('tableSpinner');
        const overlay = document.getElementById('tableOverlay');
        spinner.style.display = 'block';
        overlay.style.opacity = '0.4';

        // Collect filters
        const form = document.getElementById('filterForm');
        const formData = new FormData(form);
        const params = new URLSearchParams();
        params.append('report', reportKey);
        params.append('page', page);
        params.append('limit', document.getElementById('limitSelect').value);
        if (currentSortCol) {
            params.append('sort_col', currentSortCol);
            params.append('sort_dir', currentSortDir);
        }

        for (const [key, value] of formData.entries()) {
            if (value) params.append(key, value);
        }

        fetch('<?= APP_URL ?>/report/fetch_data?' + params.toString())
            .then(res => res.json())
            .then(data => {
                spinner.style.display = 'none';
                overlay.style.opacity = '1';

                if (!data.success) {
                    alert('Error loading report: ' + data.message);
                    return;
                }

                // Show simulation notice if applicable
                const simAlert = document.getElementById('simAlert');
                if (data.simulation) {
                    simAlert.style.display = 'flex';
                    let errorText = "Simulation Mode: Real Database Table is Missing. Displaying Simulated Data.";
                    if (data.db_error) {
                        errorText += " (Error: " + data.db_error + ")";
                    }
                    simAlert.querySelector('div').innerHTML = `<strong>Warning:</strong> ${errorText}`;
                } else {
                    simAlert.style.display = 'none';
                }

                currentRows = data.rows;
                totalEntries = data.total_rows;
                
                renderTableBody(data.rows);
                renderTableFoot(data.grand_totals);
                renderPagination(page, data.total_rows);
            })
            .catch(err => {
                spinner.style.display = 'none';
                overlay.style.opacity = '1';
                console.error(err);
            });
    }

    function renderTableBody(rows) {
        const tbody = document.getElementById('tableBody');
        tbody.innerHTML = '';

        if (rows.length === 0) {
            const colCount = Object.keys(columnsMeta).length;
            tbody.innerHTML = `<tr><td colspan="${colCount}" style="text-align:center;color:#888;padding:30px;">No matching records found.</td></tr>`;
            return;
        }

        rows.forEach(r => {
            const tr = document.createElement('tr');
            for (const [colKey, def] of Object.entries(columnsMeta)) {
                const td = document.createElement('td');
                let val = r[colKey] !== undefined ? r[colKey] : '';

                if (def.align === 'right') {
                    td.style.textAlign = 'right';
                }

                // Check for interactive drilldown
                const drill = detectDrilldown(colKey, r, def);

                // Formatting cell types
                if (def.type === 'currency') {
                    td.textContent = 'Rs. ' + parseFloat(val || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
                } else if (def.type === 'number') {
                    td.textContent = parseInt(val || 0).toLocaleString();
                } else if (def.type === 'badge') {
                    const cleanVal = (val || '').toLowerCase();
                    if (cleanVal === 'completed' || cleanVal === 'paid' || cleanVal === 'active') {
                        td.innerHTML = `<span class="badge-completed">${val}</span>`;
                    } else {
                        td.innerHTML = `<span class="badge-pending">${val}</span>`;
                    }
                } else if (drill) {
                    const tabUrl = getDrilldownUrl(drill.type, drill.id, drill.val);
                    td.innerHTML = `
                        <div class="interactive-cell">
                            <span class="drilldown-trigger" onclick="handleDrilldownClick(event, '${drill.type}', '${drill.id}', '${drill.val}')">${drill.val}</span>
                            <span class="cell-actions no-print">
                                <a href="${tabUrl}" target="_blank" title="Open in New Tab" class="cell-action-btn"><i class="ph ph-arrow-square-out"></i></a>
                                <button type="button" onclick="triggerQuickView('${drill.type}', '${drill.id}', '${drill.val}')" title="Quick View" class="cell-action-btn"><i class="ph ph-eye"></i></button>
                            </span>
                        </div>
                    `;
                } else {
                    td.textContent = val;
                }
                tr.appendChild(td);
            }
            tbody.appendChild(tr);
        });
    }

    function renderTableFoot(grandTotals) {
        const tfoot = document.getElementById('tableFoot');
        tfoot.innerHTML = '';

        // 1. Calculate Page Subtotals
        const subRow = document.createElement('tr');
        subRow.className = 'total-row';
        let firstCol = true;

        for (const [colKey, def] of Object.entries(columnsMeta)) {
            const td = document.createElement('td');
            if (def.align === 'right') {
                td.style.textAlign = 'right';
            }

            if (firstCol) {
                td.textContent = 'Page Subtotal';
                firstCol = false;
            } else if (def.total === 'sum') {
                let sum = 0;
                currentRows.forEach(r => {
                    sum += parseFloat(r[colKey] || 0);
                });
                td.textContent = (def.type === 'currency' ? 'Rs. ' : '') + sum.toLocaleString(undefined, {minimumFractionDigits: def.type === 'currency' ? 2 : 0, maximumFractionDigits: def.type === 'currency' ? 2 : 0});
            } else {
                td.textContent = '-';
            }
            subRow.appendChild(td);
        }
        tfoot.appendChild(subRow);

        // 2. Query Grand Totals (from server response)
        const grandRow = document.createElement('tr');
        grandRow.className = 'total-row';
        firstCol = true;

        for (const [colKey, def] of Object.entries(columnsMeta)) {
            const td = document.createElement('td');
            if (def.align === 'right') {
                td.style.textAlign = 'right';
            }

            if (firstCol) {
                td.textContent = 'Grand Total';
                firstCol = false;
            } else if (def.total === 'sum' && grandTotals && grandTotals[colKey] !== undefined) {
                const totalVal = parseFloat(grandTotals[colKey]);
                td.textContent = (def.type === 'currency' ? 'Rs. ' : '') + totalVal.toLocaleString(undefined, {minimumFractionDigits: def.type === 'currency' ? 2 : 0, maximumFractionDigits: def.type === 'currency' ? 2 : 0});
            } else {
                td.textContent = '-';
            }
            grandRow.appendChild(td);
        }
        tfoot.appendChild(grandRow);
    }

    function toggleTableSort(colKey) {
        if (currentSortCol === colKey) {
            currentSortDir = currentSortDir === 'ASC' ? 'DESC' : 'ASC';
        } else {
            currentSortCol = colKey;
            currentSortDir = 'ASC';
        }

        // Highlight header
        document.querySelectorAll('.report-table th').forEach(th => {
            th.className = '';
        });
        const activeTh = document.querySelector(`.report-table th[data-col-key="${colKey}"]`);
        if (activeTh) {
            activeTh.className = currentSortDir === 'ASC' ? 'sorted-asc' : 'sorted-desc';
        }

        loadReportData(1);
    }

    function clientFilterRows() {
        const query = document.getElementById('tableSearch').value.toLowerCase();
        const filtered = currentRows.filter(r => {
            return Object.values(r).some(val => String(val).toLowerCase().includes(query));
        });
        renderTableBody(filtered);
    }

    function renderPagination(page, total) {
        const limit = parseInt(document.getElementById('limitSelect').value);
        const totalPages = Math.ceil(total / limit) || 1;
        
        // Info label
        const start = total === 0 ? 0 : (page - 1) * limit + 1;
        const end = Math.min(page * limit, total);
        document.getElementById('paginationInfo').textContent = `Showing ${start} to ${end} of ${total} entries`;

        // Page buttons
        const controls = document.getElementById('paginationControls');
        controls.innerHTML = '';

        // Previous
        const prev = document.createElement('button');
        prev.className = 'page-btn';
        prev.textContent = 'Previous';
        prev.disabled = page === 1;
        prev.onclick = function() { loadReportData(page - 1); };
        controls.appendChild(prev);

        // Individual numbers
        let startPage = Math.max(1, page - 2);
        let endPage = Math.min(totalPages, page + 2);

        for (let i = startPage; i <= endPage; i++) {
            const btn = document.createElement('button');
            btn.className = 'page-btn' + (i === page ? ' active' : '');
            btn.textContent = i;
            btn.onclick = function() { loadReportData(i); };
            controls.appendChild(btn);
        }

        // Next
        const next = document.createElement('button');
        next.className = 'page-btn';
        next.textContent = 'Next';
        next.disabled = page === totalPages;
        next.onclick = function() { loadReportData(page + 1); };
        controls.appendChild(next);
    }

    // --- Share features ---
    function copyShareLink() {
        const url = window.location.href;
        navigator.clipboard.writeText(url).then(() => {
            alert('Share URL link copied to clipboard successfully!');
        });
    }

    function emailShare() {
        const url = encodeURIComponent(window.location.href);
        const subject = encodeURIComponent('Curtiss ERP Live Report View');
        window.location.href = `mailto:?subject=${subject}&body=Check out this live Curtiss ERP Report:%0D%0A${url}`;
    }

    function whatsappShare() {
        const url = encodeURIComponent(window.location.href);
        const text = encodeURIComponent('Check out this live Curtiss ERP Report: ') + url;
        window.open(`https://api.whatsapp.com/send?text=${text}`, '_blank');
    }

    // --- Export trigger ---
    function triggerExport(format) {
        const form = document.getElementById('filterForm');
        const formData = new FormData(form);
        const params = new URLSearchParams();
        params.append('format', format);

        for (const [key, value] of formData.entries()) {
            if (value) params.append(key, value);
        }

        window.location.href = `<?= APP_URL ?>/report/export/${reportKey}?` + params.toString();
    }

    // --- Dedicated high-fidelity print window layout ---
    function openPrintLayout() {
        const form = document.getElementById('filterForm');
        const formData = new FormData(form);
        const params = new URLSearchParams();
        for (const [key, value] of formData.entries()) {
            if (value) params.append(key, value);
        }
        window.open('<?= APP_URL ?>/report/print_report/' + reportKey + '?' + params.toString(), '_blank');
    }

    // --- Interactive BI Drill-down & Quick View Helpers ---
    function detectDrilldown(colKey, row, def) {
        let type = def.drilldown || null;
        let id = row.id || row[`${type}_id`] || '';
        let val = row[colKey] !== undefined ? row[colKey] : '';

        // Auto-detect based on colKey if not specified
        if (!type) {
            const keyLower = colKey.toLowerCase();
            if (keyLower.includes('customer')) {
                type = 'customer';
                id = row.customer_id || row.id || '';
            } else if (keyLower.includes('supplier') || keyLower.includes('vendor')) {
                type = 'supplier';
                id = row.vendor_id || row.supplier_id || row.id || '';
            } else if (keyLower.includes('item_code') || keyLower.includes('sku') || keyLower.includes('product_code')) {
                type = 'product';
                id = row.item_id || row.id || '';
            } else if (keyLower === 'product_name' || keyLower === 'item_name' || (keyLower === 'name' && reportKey.includes('stock'))) {
                type = 'product';
                id = row.item_id || row.id || '';
            } else if (keyLower.includes('invoice')) {
                type = 'invoice';
                id = row.invoice_id || row.id || '';
            } else if (keyLower.includes('route')) {
                type = 'route';
                id = row.route_id || row.id || '';
            } else if (keyLower === 'rep_name' || keyLower === 'sales_rep' || keyLower === 'rep' || keyLower === 'sales_representative') {
                type = 'rep';
                id = row.rep_id || row.user_id || row.id || '';
            } else if (keyLower.includes('warehouse')) {
                type = 'warehouse';
                id = row.warehouse_id || row.id || '';
            } else if (keyLower.includes('po_number') || keyLower.includes('po_no') || keyLower === 'purchase_order') {
                type = 'po';
                id = row.po_id || row.id || '';
            } else if (keyLower.includes('grn_number') || keyLower.includes('grn_no')) {
                type = 'grn';
                id = row.grn_id || row.id || '';
            } else if (keyLower.includes('cheque')) {
                type = 'cheque';
                id = row.cheque_id || row.id || '';
            } else if (keyLower.includes('driver')) {
                type = 'driver';
                id = row.driver_id || row.employee_id || row.id || '';
            } else if (keyLower.includes('vehicle')) {
                type = 'vehicle';
                id = row.vehicle_id || row.id || '';
            } else if (keyLower === 'payment_ref' || keyLower === 'reference' || keyLower === 'ref_doc' || keyLower === 'ref') {
                const valStr = String(val);
                if (valStr.startsWith('INV-')) {
                    type = 'invoice';
                } else if (valStr.startsWith('GRN-')) {
                    type = 'grn';
                } else if (valStr.startsWith('PO-')) {
                    type = 'po';
                } else if (valStr.startsWith('PAY-') || valStr.startsWith('REC-')) {
                    type = 'payment';
                } else {
                    type = 'payment';
                }
                id = row.id || '';
            }
        }

        if (!type || !val) return null;

        // Ensure we have correct id fallback
        if (!id && row.id) id = row.id;

        return { type, id, val };
    }

    function getDrilldownUrl(type, id, val) {
        let rKey = null;
        let filterParam = null;

        switch (type) {
            case 'customer':
                rKey = 'customer_statement';
                filterParam = 'customer';
                break;
            case 'supplier':
                rKey = 'supplier_statement';
                filterParam = 'supplier';
                break;
            case 'product':
                rKey = 'stock_ledger';
                filterParam = 'product';
                break;
            case 'warehouse':
                rKey = 'stock_balance';
                filterParam = 'warehouse';
                break;
            case 'route':
                rKey = 'stock_movement'; // or dynamic routes
                filterParam = 'route';
                break;
            case 'rep':
                rKey = 'sales_summary'; // or rep commission
                filterParam = 'rep';
                break;
            case 'invoice':
                return `<?= APP_URL ?>/sales/show/${id}`;
            case 'po':
                return `<?= APP_URL ?>/purchase/show/${id}`;
            case 'grn':
                return `<?= APP_URL ?>/grn/show/${id}`;
            case 'payment':
                return `<?= APP_URL ?>/payment/show/${id}`;
            case 'cheque':
                return `<?= APP_URL ?>/cheque/show/${id}`;
            default:
                return '#';
        }

        if (rKey) {
            const start = document.getElementById('filter_start_date') ? document.getElementById('filter_start_date').value : '';
            const end = document.getElementById('filter_end_date') ? document.getElementById('filter_end_date').value : '';
            let url = `<?= APP_URL ?>/report/viewer/${rKey}?${filterParam}=${id}`;
            if (start) url += `&start_date=${start}`;
            if (end) url += `&end_date=${end}`;
            return url;
        }
        return '#';
    }

    function handleDrilldownClick(event, type, id, val) {
        event.preventDefault();
        const url = getDrilldownUrl(type, id, val);
        if (url && url !== '#') {
            window.location.href = url;
        } else {
            triggerQuickView(type, id, val);
        }
    }

    function formatCurrency(val) {
        return 'Rs. ' + parseFloat(val || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    // --- Side Panel Quick View Actions ---
    function triggerQuickView(type, id, number) {
        const panel = document.getElementById('quickviewPanel');
        const backdrop = document.getElementById('quickviewBackdrop');
        const spinner = document.getElementById('quickviewSpinner');
        const content = document.getElementById('quickviewContent');
        const title = document.getElementById('quickviewTitle');

        // Show panel & spinner
        panel.classList.add('active');
        backdrop.classList.add('active');
        spinner.style.display = 'block';
        content.innerHTML = '';
        title.innerHTML = `<i class="ph ph-eye"></i> Loading...`;

        // Build request parameters
        const params = new URLSearchParams();
        params.append('type', type);
        if (id) params.append('id', id);
        if (number) params.append('number', number);

        fetch('<?= APP_URL ?>/report/quick_view?' + params.toString())
            .then(res => res.json())
            .then(data => {
                spinner.style.display = 'none';
                if (!data.success) {
                    title.innerHTML = `<i class="ph ph-warning-circle" style="color: #ef4444;"></i> Error`;
                    content.innerHTML = `<div style="color: #ef4444; padding: 10px; font-weight: 600;">${data.message || 'Record not found.'}</div>`;
                    return;
                }

                // Title header styling
                title.innerHTML = `<i class="ph ph-eye"></i> ${type.toUpperCase()} QUICK PREVIEW`;
                renderQuickViewContent(type, data);
            })
            .catch(err => {
                spinner.style.display = 'none';
                title.innerHTML = `<i class="ph ph-warning-circle" style="color: #ef4444;"></i> Error`;
                content.innerHTML = `<div style="color: #ef4444; padding: 10px;">An error occurred while loading record details.</div>`;
                console.error(err);
            });
    }

    function closeQuickView() {
        document.getElementById('quickviewPanel').classList.remove('active');
        document.getElementById('quickviewBackdrop').classList.remove('active');
    }

    function renderQuickViewContent(type, data) {
        const content = document.getElementById('quickviewContent');
        const ent = data.entity;
        let html = '';

        switch (type) {
            case 'customer':
                html += `
                    <div class="qv-card">
                        <div class="qv-title">Customer Profile</div>
                        <div class="qv-grid">
                            <div class="qv-field" style="grid-column: span 2;"><label>Name</label><span>${ent.name}</span></div>
                            <div class="qv-field"><label>Type</label><span>${ent.customer_type}</span></div>
                            <div class="qv-field"><label>Phone</label><span>${ent.phone || 'N/A'}</span></div>
                            <div class="qv-field"><label>Email</label><span>${ent.email || 'N/A'}</span></div>
                            <div class="qv-field" style="grid-column: span 2;"><label>Address</label><span>${ent.address || 'N/A'}</span></div>
                            <div class="qv-field"><label>Territory</label><span>${ent.territory || 'N/A'}</span></div>
                        </div>
                    </div>
                    
                    <div class="qv-card" style="text-align: center;">
                        <div class="qv-title">Outstanding Balance</div>
                        <div class="qv-badge-stat ${ent.outstanding_balance > 0 ? 'danger' : ''}">
                            <strong style="font-size: 16px;">${formatCurrency(ent.outstanding_balance)}</strong>
                        </div>
                    </div>

                    <div class="qv-card">
                        <div class="qv-title">Recent Invoices</div>
                        <table class="qv-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Inv Number</th>
                                    <th style="text-align: right;">Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${data.invoices && data.invoices.length > 0 ? data.invoices.map(inv => `
                                    <tr>
                                        <td>${inv.invoice_date}</td>
                                        <td><a href="javascript:void(0)" onclick="triggerQuickView('invoice', '${inv.id}', '${inv.invoice_number}')" style="color: #0066cc; font-weight: 600; text-decoration: none;">${inv.invoice_number}</a></td>
                                        <td style="text-align: right;">${formatCurrency(inv.total_amount)}</td>
                                        <td><span class="${inv.status.toLowerCase() === 'paid' ? 'badge-completed' : 'badge-pending'}">${inv.status}</span></td>
                                    </tr>
                                `).join('') : '<tr><td colspan="4" style="text-align: center; color: #888;">No recent invoices</td></tr>'}
                            </tbody>
                        </table>
                    </div>

                    <div class="qv-card">
                        <div class="qv-title">Recent Payments</div>
                        <table class="qv-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Reference</th>
                                    <th style="text-align: right;">Amount</th>
                                    <th>Method</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${data.payments && data.payments.length > 0 ? data.payments.map(pay => `
                                    <tr>
                                        <td>${pay.payment_date}</td>
                                        <td><a href="javascript:void(0)" onclick="triggerQuickView('payment', '${pay.id}', '${pay.reference}')" style="color: #0066cc; font-weight: 600; text-decoration: none;">${pay.reference}</a></td>
                                        <td style="text-align: right;">${formatCurrency(pay.amount)}</td>
                                        <td>${pay.payment_method}</td>
                                    </tr>
                                `).join('') : '<tr><td colspan="4" style="text-align: center; color: #888;">No recent payments</td></tr>'}
                            </tbody>
                        </table>
                    </div>

                    <div class="qv-action-bar">
                        <a href="${getDrilldownUrl('customer', ent.id, ent.name)}" class="qv-btn qv-btn-primary"><i class="ph ph-file-text"></i> View Statement</a>
                        <button onclick="closeQuickView()" class="qv-btn qv-btn-secondary">Close</button>
                    </div>
                `;
                break;

            case 'product':
                html += `
                    <div class="qv-card">
                        <div class="qv-title">Product Details</div>
                        <div class="qv-grid">
                            <div class="qv-field"><label>SKU / Code</label><span>${ent.item_code}</span></div>
                            <div class="qv-field"><label>Brand</label><span>${ent.brand}</span></div>
                            <div class="qv-field" style="grid-column: span 2;"><label>Name</label><span>${ent.name}</span></div>
                            <div class="qv-field"><label>Retail Price</label><span>${formatCurrency(ent.price)}</span></div>
                            <div class="qv-field"><label>Cost Price</label><span>${formatCurrency(ent.cost)}</span></div>
                        </div>
                    </div>

                    <div class="qv-card" style="text-align: center;">
                        <div class="qv-title">Total Stock Level</div>
                        <div class="qv-badge-stat">
                            <strong style="font-size: 16px;">${parseInt(ent.qty_on_hand || 0).toLocaleString()} Units</strong>
                        </div>
                    </div>

                    <div class="qv-card">
                        <div class="qv-title">Stock By Warehouse</div>
                        <table class="qv-table">
                            <thead>
                                <tr>
                                    <th>Warehouse</th>
                                    <th style="text-align: right;">Quantity</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${data.stock && data.stock.length > 0 ? data.stock.map(st => `
                                    <tr>
                                        <td>${st.warehouse_name}</td>
                                        <td style="text-align: right; font-weight: 600;">${parseInt(st.quantity).toLocaleString()}</td>
                                    </tr>
                                `).join('') : '<tr><td colspan="2" style="text-align: center; color: #888;">No warehouse stock info</td></tr>'}
                            </tbody>
                        </table>
                    </div>

                    <div class="qv-card">
                        <div class="qv-title">Recent Sales Movements</div>
                        <table class="qv-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Ref Doc</th>
                                    <th style="text-align: right;">Qty</th>
                                    <th style="text-align: right;">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${data.sales && data.sales.length > 0 ? data.sales.map(sl => `
                                    <tr>
                                        <td>${sl.date}</td>
                                        <td>${sl.ref}</td>
                                        <td style="text-align: right;">${parseInt(sl.qty).toLocaleString()}</td>
                                        <td style="text-align: right;">${formatCurrency(sl.total_value)}</td>
                                    </tr>
                                `).join('') : '<tr><td colspan="4" style="text-align: center; color: #888;">No recent sales records</td></tr>'}
                            </tbody>
                        </table>
                    </div>

                    <div class="qv-action-bar">
                        <a href="${getDrilldownUrl('product', ent.id, ent.name)}" class="qv-btn qv-btn-primary"><i class="ph ph-chart-line"></i> Stock Ledger</a>
                        <button onclick="closeQuickView()" class="qv-btn qv-btn-secondary">Close</button>
                    </div>
                `;
                break;

            case 'invoice':
                html += `
                    <div class="qv-card">
                        <div class="qv-title">Invoice Details</div>
                        <div class="qv-grid">
                            <div class="qv-field"><label>Number</label><strong style="color: #0066cc;">${ent.invoice_number}</strong></div>
                            <div class="qv-field"><label>Date</label><span>${ent.invoice_date}</span></div>
                            <div class="qv-field" style="grid-column: span 2;"><label>Customer</label><span>${ent.customer_name}</span></div>
                            <div class="qv-field"><label>Due Date</label><span>${ent.due_date || 'N/A'}</span></div>
                            <div class="qv-field"><label>Status</label><span class="${ent.status.toLowerCase() === 'paid' ? 'badge-completed' : 'badge-pending'}">${ent.status}</span></div>
                        </div>
                    </div>

                    <div class="qv-card">
                        <div class="qv-title">Items List</div>
                        <table class="qv-table">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th style="text-align: right;">Qty</th>
                                    <th style="text-align: right;">Price</th>
                                    <th style="text-align: right;">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${data.items && data.items.length > 0 ? data.items.map(item => `
                                    <tr>
                                        <td>${item.item_name}</td>
                                        <td style="text-align: right;">${parseInt(item.quantity).toLocaleString()}</td>
                                        <td style="text-align: right;">${formatCurrency(item.unit_price)}</td>
                                        <td style="text-align: right; font-weight: 600;">${formatCurrency(item.total_amount || (item.quantity * item.unit_price))}</td>
                                    </tr>
                                `).join('') : '<tr><td colspan="4" style="text-align: center; color: #888;">No items in invoice</td></tr>'}
                            </tbody>
                        </table>
                    </div>

                    <div class="qv-card">
                        <div class="qv-title">Financial Summary</div>
                        <div style="display: flex; flex-direction: column; gap: 6px; font-size: 13px;">
                            <div style="display: flex; justify-content: space-between;"><span>Subtotal</span><span>${formatCurrency(ent.total)}</span></div>
                            ${ent.discount > 0 ? `<div style="display: flex; justify-content: space-between; color: #dc2626;"><span>Discount</span><span>-${formatCurrency(ent.discount)}</span></div>` : ''}
                            ${ent.tax > 0 ? `<div style="display: flex; justify-content: space-between;"><span>Tax</span><span>${formatCurrency(ent.tax)}</span></div>` : ''}
                            <div style="display: flex; justify-content: space-between; font-weight: 750; border-top: 1px solid #cbd5e1; padding-top: 6px; font-size: 14px;">
                                <span>Net Total</span><span>${formatCurrency(ent.net_total)}</span>
                            </div>
                        </div>
                    </div>

                    <div class="qv-action-bar">
                        <a href="<?= APP_URL ?>/sales/show/${ent.id}" target="_blank" class="qv-btn qv-btn-primary"><i class="ph ph-printer"></i> Open Invoice</a>
                        <button onclick="closeQuickView()" class="qv-btn qv-btn-secondary">Close</button>
                    </div>
                `;
                break;

            case 'supplier':
                html += `
                    <div class="qv-card">
                        <div class="qv-title">Supplier Info</div>
                        <div class="qv-grid">
                            <div class="qv-field" style="grid-column: span 2;"><label>Name</label><span>${ent.name}</span></div>
                            <div class="qv-field"><label>Phone</label><span>${ent.phone || 'N/A'}</span></div>
                            <div class="qv-field"><label>Email</label><span>${ent.email || 'N/A'}</span></div>
                            <div class="qv-field" style="grid-column: span 2;"><label>Address</label><span>${ent.address || 'N/A'}</span></div>
                        </div>
                    </div>

                    <div class="qv-card" style="text-align: center;">
                        <div class="qv-title">Outstanding Balance</div>
                        <div class="qv-badge-stat ${ent.outstanding_balance > 0 ? 'danger' : ''}">
                            <strong style="font-size: 16px;">${formatCurrency(ent.outstanding_balance)}</strong>
                        </div>
                    </div>

                    <div class="qv-card">
                        <div class="qv-title">Recent Receipts (GRNs)</div>
                        <table class="qv-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>GRN Number</th>
                                    <th style="text-align: right;">Total Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${data.grns && data.grns.length > 0 ? data.grns.map(grn => `
                                    <tr>
                                        <td>${grn.grn_date}</td>
                                        <td><a href="javascript:void(0)" onclick="triggerQuickView('grn', '${grn.id}', '${grn.grn_number}')" style="color: #0066cc; font-weight: 600; text-decoration: none;">${grn.grn_number}</a></td>
                                        <td style="text-align: right; font-weight: 600;">${formatCurrency(grn.total)}</td>
                                    </tr>
                                `).join('') : '<tr><td colspan="3" style="text-align: center; color: #888;">No recent GRNs</td></tr>'}
                            </tbody>
                        </table>
                    </div>

                    <div class="qv-action-bar">
                        <a href="${getDrilldownUrl('supplier', ent.id, ent.name)}" class="qv-btn qv-btn-primary"><i class="ph ph-file-text"></i> Supplier Statement</a>
                        <button onclick="closeQuickView()" class="qv-btn qv-btn-secondary">Close</button>
                    </div>
                `;
                break;

            case 'route':
                html += `
                    <div class="qv-card">
                        <div class="qv-title">Route Summary</div>
                        <div class="qv-grid">
                            <div class="qv-field" style="grid-column: span 2;"><label>Route Name</label><span>${ent.route_name}</span></div>
                            <div class="qv-field" style="grid-column: span 2;"><label>Description</label><span>${ent.description}</span></div>
                        </div>
                    </div>

                    <div class="qv-card">
                        <div class="qv-title">Route Analytics</div>
                        <div style="display: flex; flex-direction: column; gap: 8px; font-size: 13px;">
                            <div style="display: flex; justify-content: space-between;"><span>Active Customers</span><span style="font-weight: 600;">${ent.cust_count}</span></div>
                            <div style="display: flex; justify-content: space-between;"><span>Invoices Raised</span><span style="font-weight: 600;">${ent.inv_count}</span></div>
                            <div style="display: flex; justify-content: space-between;"><span>Total Sales</span><span style="font-weight: 600; color: #1e293b;">${formatCurrency(ent.total_sales)}</span></div>
                            <div style="display: flex; justify-content: space-between;"><span>Total Collections</span><span style="font-weight: 600; color: #166534;">${formatCurrency(ent.total_collections)}</span></div>
                            <div style="display: flex; justify-content: space-between; font-weight: 750; border-top: 1px solid #cbd5e1; padding-top: 8px;">
                                <span>Outstanding Amount</span><span style="color: #dc2626;">${formatCurrency(ent.outstanding)}</span>
                            </div>
                        </div>
                    </div>

                    <div class="qv-action-bar">
                        <a href="${getDrilldownUrl('route', ent.id, ent.route_name)}" class="qv-btn qv-btn-primary"><i class="ph ph-compass"></i> Route Statement</a>
                        <button onclick="closeQuickView()" class="qv-btn qv-btn-secondary">Close</button>
                    </div>
                `;
                break;

            case 'rep':
                html += `
                    <div class="qv-card">
                        <div class="qv-title">Rep Profile</div>
                        <div class="qv-grid">
                            <div class="qv-field"><label>Rep Name</label><span style="font-weight: 600;">${ent.name}</span></div>
                            <div class="qv-field"><label>Designation</label><span>${ent.role}</span></div>
                            <div class="qv-field" style="grid-column: span 2;"><label>Email</label><span>${ent.email}</span></div>
                        </div>
                    </div>

                    <div class="qv-action-bar">
                        <a href="${getDrilldownUrl('rep', ent.id, ent.name)}" class="qv-btn qv-btn-primary"><i class="ph ph-users"></i> Rep Commission</a>
                        <button onclick="closeQuickView()" class="qv-btn qv-btn-secondary">Close</button>
                    </div>
                `;
                break;

            case 'grn':
                html += `
                    <div class="qv-card">
                        <div class="qv-title">GRN Details</div>
                        <div class="qv-grid">
                            <div class="qv-field"><label>GRN Number</label><strong>${ent.grn_number}</strong></div>
                            <div class="qv-field"><label>Date</label><span>${ent.grn_date}</span></div>
                            <div class="qv-field" style="grid-column: span 2;"><label>Supplier</label><span>${ent.supplier_name}</span></div>
                            <div class="qv-field"><label>Status</label><span class="${ent.is_approved ? 'badge-completed' : 'badge-pending'}">${ent.is_approved ? 'Approved' : 'Pending'}</span></div>
                            <div class="qv-field"><label>Total Value</label><span style="font-weight: 600;">${formatCurrency(ent.total)}</span></div>
                        </div>
                    </div>

                    <div class="qv-action-bar">
                        <a href="<?= APP_URL ?>/grn/show/${ent.id}" target="_blank" class="qv-btn qv-btn-primary"><i class="ph ph-printer"></i> Print GRN</a>
                        <button onclick="closeQuickView()" class="qv-btn qv-btn-secondary">Close</button>
                    </div>
                `;
                break;

            case 'po':
                html += `
                    <div class="qv-card">
                        <div class="qv-title">PO Details</div>
                        <div class="qv-grid">
                            <div class="qv-field"><label>PO Number</label><strong>${ent.po_number}</strong></div>
                            <div class="qv-field"><label>Date</label><span>${ent.po_date}</span></div>
                            <div class="qv-field" style="grid-column: span 2;"><label>Supplier</label><span>${ent.supplier_name}</span></div>
                            <div class="qv-field"><label>Status</label><span class="badge-completed">${ent.status}</span></div>
                            <div class="qv-field"><label>Total Amount</label><span style="font-weight: 600;">${formatCurrency(ent.total)}</span></div>
                        </div>
                    </div>

                    <div class="qv-action-bar">
                        <a href="<?= APP_URL ?>/purchase/show/${ent.id}" target="_blank" class="qv-btn qv-btn-primary"><i class="ph ph-printer"></i> Print PO</a>
                        <button onclick="closeQuickView()" class="qv-btn qv-btn-secondary">Close</button>
                    </div>
                `;
                break;

            case 'payment':
                html += `
                    <div class="qv-card">
                        <div class="qv-title">Payment Info</div>
                        <div class="qv-grid">
                            <div class="qv-field"><label>Reference</label><strong>${ent.reference}</strong></div>
                            <div class="qv-field"><label>Date</label><span>${ent.payment_date}</span></div>
                            <div class="qv-field" style="grid-column: span 2;"><label>Customer</label><span>${ent.customer_name}</span></div>
                            <div class="qv-field"><label>Method</label><span>${ent.payment_method}</span></div>
                            <div class="qv-field"><label>Status</label><span class="badge-completed">${ent.status}</span></div>
                        </div>
                    </div>

                    <div class="qv-card" style="text-align: center;">
                        <div class="qv-title">Paid Amount</div>
                        <div class="qv-badge-stat">
                            <strong style="font-size: 16px;">${formatCurrency(ent.amount)}</strong>
                        </div>
                    </div>

                    <div class="qv-action-bar">
                        <button onclick="closeQuickView()" class="qv-btn qv-btn-secondary" style="width:100%;">Close</button>
                    </div>
                `;
                break;

            case 'cheque':
                html += `
                    <div class="qv-card">
                        <div class="qv-title">Cheque details</div>
                        <div class="qv-grid">
                            <div class="qv-field"><label>Cheque Number</label><strong>${ent.cheque_number}</strong></div>
                            <div class="qv-field"><label>Banking Date</label><span>${ent.banking_date}</span></div>
                            <div class="qv-field" style="grid-column: span 2;"><label>Bank Name</label><span>${ent.bank_name}</span></div>
                            <div class="qv-field" style="grid-column: span 2;"><label>Customer</label><span>${ent.customer_name}</span></div>
                            <div class="qv-field"><label>Status</label><span class="${ent.status.toLowerCase() === 'cleared' ? 'badge-completed' : 'badge-pending'}">${ent.status}</span></div>
                        </div>
                    </div>

                    <div class="qv-card" style="text-align: center;">
                        <div class="qv-title">Cheque Amount</div>
                        <div class="qv-badge-stat">
                            <strong style="font-size: 16px;">${formatCurrency(ent.amount)}</strong>
                        </div>
                    </div>

                    <div class="qv-action-bar">
                        <button onclick="closeQuickView()" class="qv-btn qv-btn-secondary" style="width:100%;">Close</button>
                    </div>
                `;
                break;

            default:
                html += `
                    <div class="qv-card">
                        <div class="qv-title">Details</div>
                        <pre style="font-size: 12px; white-space: pre-wrap; word-break: break-all;">${JSON.stringify(ent, null, 2)}</pre>
                    </div>
                    <div class="qv-action-bar">
                        <button onclick="closeQuickView()" class="qv-btn qv-btn-secondary" style="width:100%;">Close</button>
                    </div>
                `;
                break;
        }

        content.innerHTML = html;
    }

    // --- Dynamic breadcrumb logic ---
    function updateBreadcrumbs() {
        let trail = [];
        try {
            trail = JSON.parse(sessionStorage.getItem('report_breadcrumbs') || '[]');
        } catch (e) {
            trail = [];
        }
        
        const currentTitle = '<?= htmlspecialchars($metadata['title']) ?>';
        const currentUrl = window.location.pathname + window.location.search;

        const existingIndex = trail.findIndex(item => item.url.split('?')[0] === currentUrl.split('?')[0]);
        if (existingIndex !== -1) {
            trail = trail.slice(0, existingIndex + 1);
        } else {
            if (document.referrer && document.referrer.includes('/report/viewer/')) {
                trail.push({ title: currentTitle, url: currentUrl });
            } else {
                trail = [{ title: currentTitle, url: currentUrl }];
            }
        }
        sessionStorage.setItem('report_breadcrumbs', JSON.stringify(trail));

        const container = document.querySelector('.report-meta .breadcrumb');
        if (container && trail.length > 1) {
            container.innerHTML = '';
            trail.forEach((item, idx) => {
                if (idx > 0) {
                    container.appendChild(document.createTextNode(' → '));
                }
                if (idx === trail.length - 1) {
                    const span = document.createElement('span');
                    span.textContent = item.title;
                    span.style.fontWeight = '600';
                    span.style.color = '#1e293b';
                    container.appendChild(span);
                } else {
                    const a = document.createElement('a');
                    a.href = item.url;
                    a.textContent = item.title;
                    a.style.color = '#0066cc';
                    a.style.textDecoration = 'none';
                    a.className = 'breadcrumb-link';
                    container.appendChild(a);
                }
            });
        }
    }
</script>
