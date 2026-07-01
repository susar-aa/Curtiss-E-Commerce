<?php
// Enable error reporting to prevent blank 500 errors in the future
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>
<style>
    /* Premium Glassmorphism & macOS Layout */
    .app-workspace { 
        display: flex; 
        height: calc(100vh - 120px); 
        background: #fdfdfd; 
        border-radius: 12px; 
        overflow: hidden; 
        border: 1px solid rgba(0, 0, 0, 0.08); 
        position: relative;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.04);
        transition: all 0.3s ease;
    }
    @media (prefers-color-scheme: dark) { 
        .app-workspace { 
            background: #181824; 
            border-color: rgba(255, 255, 255, 0.08);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
        } 
    }

    /* Left Pane: Route List */
    .pane-left { 
        width: 380px; 
        background: rgba(246, 248, 250, 0.6); 
        border-right: 1px solid rgba(0, 0, 0, 0.06); 
        display: flex; 
        flex-direction: column; 
        z-index: 10;
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
    }
    @media (prefers-color-scheme: dark) { 
        .pane-left { 
            background: rgba(26, 26, 38, 0.6); 
            border-right-color: rgba(255, 255, 255, 0.06); 
        } 
    }

    .search-container {
        padding: 15px;
        border-bottom: 1px solid rgba(0, 0, 0, 0.06);
    }
    @media (prefers-color-scheme: dark) {
        .search-container {
            border-bottom-color: rgba(255, 255, 255, 0.06);
        }
    }
    .search-input {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid rgba(0,0,0,0.1);
        border-radius: 6px;
        font-size: 13px;
        box-sizing: border-box;
        background: rgba(255, 255, 255, 0.9);
        transition: all 0.2s ease;
    }
    @media (prefers-color-scheme: dark) {
        .search-input {
            background: rgba(0,0,0,0.2);
            border-color: rgba(255,255,255,0.1);
            color: #fff;
        }
    }
    .search-input:focus {
        border-color: #007aff;
        outline: none;
        box-shadow: 0 0 0 3px rgba(0, 122, 255, 0.15);
    }

    .delivery-item { 
        padding: 18px 20px; 
        border-bottom: 1px solid rgba(0,0,0,0.04); 
        cursor: pointer; 
        user-select: none; 
        transition: all 0.2s ease;
        position: relative;
    }
    @media (prefers-color-scheme: dark) {
        .delivery-item {
            border-bottom-color: rgba(255,255,255,0.02);
        }
    }
    .delivery-item:hover { 
        background: rgba(0, 122, 255, 0.04); 
    }
    .delivery-item.active { 
        background: linear-gradient(135deg, #007aff, #0056b3); 
        color: #fff; 
        border-color: transparent; 
        box-shadow: 0 4px 12px rgba(0, 122, 255, 0.2);
    }
    .delivery-item.active .del-sub, 
    .delivery-item.active .del-meta, 
    .delivery-item.active .del-vehicle { 
        color: rgba(255, 255, 255, 0.85); 
    }
    
    .del-title { 
        font-weight: 600; 
        font-size: 14.5px; 
        margin-bottom: 6px; 
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .del-vehicle {
        font-size: 12px;
        font-weight: bold;
        color: #007aff;
        margin-bottom: 4px;
        display: flex;
        align-items: center;
        gap: 4px;
    }
    .del-sub { 
        font-size: 11px; 
        color: #666; 
        font-weight: 600; 
        text-transform: uppercase; 
        margin-bottom: 8px;
        letter-spacing: 0.5px;
    }
    .del-meta { 
        font-size: 12px; 
        color: #888; 
        display: flex; 
        justify-content: space-between; 
        align-items: center;
    }
    
    .status-badge {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 10px;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        background: rgba(0, 0, 0, 0.05);
        color: #555;
    }
    @media (prefers-color-scheme: dark) {
        .status-badge {
            background: rgba(255, 255, 255, 0.1);
            color: #ccc;
        }
    }
    .status-badge.status-arranged {
        background: rgba(0, 122, 255, 0.12);
        color: #007aff;
    }
    .status-badge.status-intransit {
        background: rgba(239, 108, 0, 0.12);
        color: #ef6c00;
    }
    .status-badge.status-completed {
        background: rgba(156, 39, 176, 0.12);
        color: #9c27b0;
    }
    .status-badge.status-finalized {
        background: rgba(46, 125, 50, 0.12);
        color: #2e7d32;
    }

    /* Left pane status pills */
    .status-filter-container {
        display: flex;
        gap: 4px;
        background: rgba(0, 0, 0, 0.04);
        padding: 3px;
        border-radius: 8px;
        margin-top: 10px;
    }
    @media (prefers-color-scheme: dark) {
        .status-filter-container {
            background: rgba(255, 255, 255, 0.04);
        }
    }
    .filter-pill {
        flex: 1;
        border: none;
        background: transparent;
        padding: 6px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 700;
        cursor: pointer;
        color: #777;
        transition: all 0.2s ease;
        text-align: center;
    }
    @media (prefers-color-scheme: dark) {
        .filter-pill {
            color: #aaa;
        }
    }
    .filter-pill:hover {
        color: var(--text-main);
    }
    .filter-pill.active {
        background: #fff;
        color: #007aff;
        box-shadow: 0 2px 6px rgba(0,0,0,0.06);
    }
    @media (prefers-color-scheme: dark) {
        .filter-pill.active {
            background: #181824;
            color: #fff;
            box-shadow: 0 2px 6px rgba(0,0,0,0.2);
        }
    }

    /* Middle Pane: Bills Table */
    .pane-middle { 
        flex: 1; 
        display: flex; 
        flex-direction: column; 
        background: #fff; 
        position: relative;
        overflow: hidden;
    }
    @media (prefers-color-scheme: dark) { 
        .pane-middle { background: #1d1d2b; } 
    }
    .mid-header { 
        padding: 24px 30px; 
        border-bottom: 1px solid rgba(0,0,0,0.06); 
        background: rgba(250,250,250,0.5); 
        display: flex; 
        justify-content: space-between; 
        align-items: flex-end;
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
    }
    @media (prefers-color-scheme: dark) { 
        .mid-header { 
            background: rgba(28,28,40,0.5); 
            border-bottom-color: rgba(255,255,255,0.06); 
        } 
    }
    
    /* Elegant tabs */
    .tabs-container {
        display: flex;
        border-bottom: 1px solid rgba(0,0,0,0.06);
        background: rgba(0,0,0,0.01);
        padding: 0 30px;
    }
    @media (prefers-color-scheme: dark) {
        .tabs-container {
            border-bottom-color: rgba(255,255,255,0.06);
            background: rgba(255,255,255,0.01);
        }
    }
    .tab-btn {
        padding: 14px 20px;
        border: none;
        background: transparent;
        cursor: pointer;
        font-weight: 600;
        font-size: 13px;
        color: #888;
        border-bottom: 3px solid transparent;
        transition: all 0.2s ease;
    }
    .tab-btn:hover {
        color: var(--text-main);
    }
    .tab-btn.active {
        color: #007aff;
        border-bottom-color: #007aff;
    }
    
    .data-table { 
        width: 100%; 
        border-collapse: collapse; 
    }
    .data-table th, .data-table td { 
        padding: 14px 20px; 
        text-align: left; 
        border-bottom: 1px solid rgba(0,0,0,0.05); 
        font-size: 13.5px;
    }
    @media (prefers-color-scheme: dark) {
        .data-table th, .data-table td {
            border-bottom-color: rgba(255,255,255,0.04);
        }
    }
    .data-table th { 
        color: #888; 
        font-weight: bold; 
        font-size: 10.5px; 
        text-transform: uppercase; 
        background: rgba(0,0,0,0.015); 
        position: sticky; 
        top: 0;
        letter-spacing: 0.5px;
        border-bottom: 2px solid rgba(0,0,0,0.05);
    }
    @media (prefers-color-scheme: dark) {
        .data-table th {
            background: rgba(255,255,255,0.015);
            border-bottom-color: rgba(255,255,255,0.05);
        }
    }
    
    .bill-row { 
        cursor: pointer; 
        transition: all 0.15s ease; 
        user-select: none;
    }
    .bill-row:hover { 
        background: rgba(0, 122, 255, 0.03); 
    }

    .empty-state { 
        display: flex; 
        flex-direction: column; 
        align-items: center; 
        justify-content: center; 
        height: 100%; 
        color: #888; 
        padding: 40px;
        text-align: center;
    }
    .empty-state span { 
        font-size: 64px; 
        margin-bottom: 20px; 
        opacity: 0.65;
        animation: float 3s ease-in-out infinite;
    }
    
    @keyframes float {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-8px); }
    }

    /* Right Pane: Slide-out Invoice Mini-View */
    .pane-right-slider { 
        position: absolute; 
        top: 0; 
        right: 0; 
        bottom: 0; 
        width: 580px; 
        background: #fff; 
        border-left: 1px solid rgba(0,0,0,0.08); 
        box-shadow: -10px 0 35px rgba(0,0,0,0.12); 
        transform: translateX(100%); 
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1); 
        z-index: 50;
        display: flex; 
        flex-direction: column;
    }
    @media (prefers-color-scheme: dark) {
        .pane-right-slider {
            background: #181824;
            border-left-color: rgba(255,255,255,0.08);
            box-shadow: -10px 0 35px rgba(0,0,0,0.4);
        }
    }
    .pane-right-slider.open { 
        transform: translateX(0); 
    }
    
    .slider-header { 
        padding: 16px 24px; 
        background: #222; 
        color: #fff; 
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
        font-weight: 600;
        font-size: 14px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    .close-slider { 
        background: transparent; 
        border: none; 
        color: #fff; 
        font-size: 22px; 
        cursor: pointer; 
        padding: 0;
        opacity: 0.75;
        transition: opacity 0.2s ease;
    }
    .close-slider:hover {
        opacity: 1;
    }
    
    #invoiceIframe { 
        width: 100%; 
        flex: 1; 
        border: none; 
        background: #f4f5f7; 
    }
    @media (prefers-color-scheme: dark) {
        #invoiceIframe {
            background: #2a2a3e;
        }
    }

    /* Highlights */
    .stat-box { 
        background: rgba(0, 0, 0, 0.02); 
        border: 1px solid rgba(0,0,0,0.06); 
        padding: 10px 18px; 
        border-radius: 8px; 
        text-align: center;
        min-width: 100px;
    }
    @media (prefers-color-scheme: dark) {
        .stat-box {
            background: rgba(255, 255, 255, 0.02);
            border-color: rgba(255,255,255,0.06);
        }
    }
    .stat-box span { 
        display: block; 
        font-size: 9.5px; 
        color: #888; 
        text-transform: uppercase; 
        font-weight: bold; 
        margin-bottom: 4px;
        letter-spacing: 0.5px;
    }
    .stat-box strong { 
        font-size: 15px; 
        color: var(--text-main); 
    }

    .btn-premium {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 18px;
        border: none;
        border-radius: 8px;
        font-weight: bold;
        cursor: pointer;
        font-size: 13px;
        transition: all 0.2s ease;
        text-decoration: none;
    }
    .btn-premium.primary {
        background: linear-gradient(135deg, #007aff, #0056b3);
        color: #fff;
        box-shadow: 0 4px 12px rgba(0, 122, 255, 0.2);
    }
    .btn-premium.primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 16px rgba(0, 122, 255, 0.3);
    }
    .btn-premium.secondary {
        background: rgba(0, 0, 0, 0.04);
        color: var(--text-main);
        border: 1px solid rgba(0, 0, 0, 0.06);
    }
    @media (prefers-color-scheme: dark) {
        .btn-premium.secondary {
            background: rgba(255, 255, 255, 0.04);
            border-color: rgba(255, 255, 255, 0.06);
        }
    }
    .btn-premium.secondary:hover {
        background: rgba(0, 0, 0, 0.08);
    }
    @media (prefers-color-scheme: dark) {
        .btn-premium.secondary:hover {
            background: rgba(255, 255, 255, 0.08);
        }
    }
</style>

<div class="header-actions" style="margin-bottom: 20px; display:flex; justify-content:space-between; align-items:center;">
    <div>
        <h2 style="margin: 0 0 5px 0; font-weight:700;">🚚 Delivery Arrangement Dashboard</h2>
        <p style="margin: 0; color: #888; font-size: 14px;">View arranged deliveries, customer loading sheets, and manage outstanding route collections.</p>
    </div>
</div>

<div class="app-workspace">
    <!-- Left Pane: Arranged Deliveries Master List -->
    <div class="pane-left">
        <div class="search-container">
            <input type="text" class="search-input" id="searchField" placeholder="🔍 Search route, vehicle or driver..." onkeyup="filterDeliveries()">
            <div class="status-filter-container" id="statusFilterContainer">
                <button class="filter-pill active" onclick="setStatusFilter('Arranged')">Arranged</button>
                <button class="filter-pill" onclick="setStatusFilter('Completed')">Ended</button>
                <button class="filter-pill" onclick="setStatusFilter('Finalized')">Finalized</button>
            </div>
        </div>
        <div style="overflow-y: auto; flex:1;" id="deliveryListContainer">
            <?php if(empty($data['deliveries'])): ?>
                <div style="padding:40px; text-align:center; color:#888; font-size:13.5px;">
                    <p style="font-size:24px; margin-bottom:10px;">📦</p>
                    No arranged deliveries found. Use <strong>Rep Route Tracking</strong> to arrange one.
                </div>
            <?php else: ?>
                <?php foreach($data['deliveries'] as $del): ?>
                    <div class="delivery-item" id="delivery_<?= $del->id ?>" onclick="loadDeliveryDetails(<?= $del->id ?>, this)">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 6px;">
                            <div class="del-vehicle">
                                🚚 <?= htmlspecialchars($del->vehicle_number) ?>
                            </div>
                            <span class="status-badge status-<?= strtolower(str_replace(' ', '', $del->status)) ?>">
                                <?= htmlspecialchars($del->status === 'Completed' ? 'Ended' : $del->status) ?>
                            </span>
                        </div>
                        <div class="del-title">
                            <?= htmlspecialchars($del->route_name) ?>
                        </div>
                        <div class="del-sub">Driver: <?= htmlspecialchars($del->driver_name) ?></div>
                        <div class="del-meta">
                            <span>📅 <?= date('M d, Y', strtotime($del->delivery_date)) ?></span>
                            <strong style="color: #2e7d32;">Rs: <?= number_format($del->total_sales, 2) ?></strong>
                        </div>
                    </div>
                    
                    <!-- Hidden metadata payload for quick access -->
                    <div id="del_data_<?= $del->id ?>" style="display:none;" 
                         data-rname="<?= htmlspecialchars($del->route_name) ?>"
                         data-rep="<?= htmlspecialchars($del->first_name . ' ' . $del->last_name) ?>"
                         data-vehicle="<?= htmlspecialchars($del->vehicle_number) ?>"
                         data-driver="<?= htmlspecialchars($del->driver_name) ?>"
                         data-partner="<?= htmlspecialchars($del->partner_name ?: 'None') ?>"
                         data-date="<?= date('M d, Y', strtotime($del->delivery_date)) ?>"
                         data-sales="<?= number_format($del->total_sales, 2) ?>"
                         data-bills="<?= $del->bill_count ?>"
                         data-status="<?= htmlspecialchars($del->status) ?>">
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Middle Pane: Invoices / Credit Bills List -->
    <div class="pane-middle">
        
        <!-- Header populates via JS -->
        <div class="mid-header" id="midHeader" style="visibility: hidden;">
            <div>
                <h3 style="margin:0 0 6px 0; color:#007aff; font-weight:700; font-size:18px;" id="mhRouteName">Route Name</h3>
                <div style="font-size: 13px; color: #888; font-weight: 500; display:flex; flex-wrap:wrap; gap:15px; margin-bottom:5px;">
                    <span>Rep: <strong style="color:var(--text-main);" id="mhRepName"></strong></span>
                    <span>Vehicle: <strong style="color:var(--text-main);" id="mhVehicle"></strong></span>
                    <span>Driver: <strong style="color:var(--text-main);" id="mhDriver"></strong></span>
                </div>
                <div style="font-size: 12px; color: #888;">
                    Partner: <span id="mhPartner"></span> &nbsp;|&nbsp; Delivery Date: <strong id="mhDate"></strong>
                </div>
            </div>
            <div style="display: flex; gap: 10px; align-items: center;">
                <div class="stat-box"><span>Sales Value</span><strong style="color:#2e7d32;">Rs <span id="mhSales"></span></strong></div>
                <div class="stat-box"><span>Total Bills</span><strong id="mhBills"></strong></div>
                
                <!-- Premium Loading summary sheet action -->
                <a id="btnSpreadsheet" href="#" class="btn-premium primary">📊 Spreadsheet Grid</a>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="tabs-container" id="midTabs" style="visibility: hidden;">
            <button class="tab-btn active" id="tabAll" onclick="switchTab('all')">📄 All Bills</button>
            <button class="tab-btn" id="tabCredit" onclick="switchTab('credit')">💳 Credit Collection (Unpaid)</button>
            <button class="tab-btn" id="tabBalancing" onclick="switchTab('balancing')" style="display: none;">⚖️ Delivery Balancing</button>
        </div>

        <div style="flex:1; overflow-y:auto; position:relative;">
            <div class="empty-state" id="midEmptyState">
                <span>🚚</span>
                Select an arranged delivery on the left to track its loading sheet and invoices.
            </div>

            <!-- Balancing Panel -->
            <div id="balancingPanel" style="display: none; padding: 30px;">
                <!-- KPI metrics cards -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
                    <div style="background: rgba(0, 122, 255, 0.04); border: 1px solid rgba(0, 122, 255, 0.1); padding: 20px; border-radius: 12px;">
                        <span style="font-size: 11px; text-transform: uppercase; color: #007aff; font-weight: bold; letter-spacing: 0.5px;">Today's Sales Summary</span>
                        <div style="margin-top: 10px; font-size: 14px;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                                <span>Cash Sales:</span>
                                <strong style="color: #2e7d32;" id="bpCashSales">Rs 0.00</strong>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span>Credit Sales:</span>
                                <strong style="color: #ef6c00;" id="bpCreditSales">Rs 0.00</strong>
                            </div>
                        </div>
                    </div>
                    
                    <div style="background: rgba(46, 125, 50, 0.04); border: 1px solid rgba(46, 125, 50, 0.1); padding: 20px; border-radius: 12px;">
                        <span style="font-size: 11px; text-transform: uppercase; color: #2e7d32; font-weight: bold; letter-spacing: 0.5px;">Driver collections today</span>
                        <div style="margin-top: 10px; font-size: 14px;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                                <span>Cash:</span>
                                <strong id="bpCashColl">Rs 0.00</strong>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                                <span>Cheque:</span>
                                <strong id="bpChequeColl">Rs 0.00</strong>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span>Bank Transfer:</span>
                                <strong id="bpBankColl">Rs 0.00</strong>
                            </div>
                        </div>
                    </div>

                    <div style="background: rgba(0,0,0,0.02); border: 1px solid rgba(0,0,0,0.06); padding: 20px; border-radius: 12px;">
                        <span style="font-size: 11px; text-transform: uppercase; color: #555; font-weight: bold; letter-spacing: 0.5px;">Trip mileage</span>
                        <div style="margin-top: 10px; font-size: 14px;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                                <span>Start Meter:</span>
                                <strong id="bpStartMeter">0.0 KM</strong>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                                <span>End Meter:</span>
                                <strong id="bpEndMeter">0.0 KM</strong>
                            </div>
                            <div style="display: flex; justify-content: space-between; border-top: 1px dashed rgba(0,0,0,0.1); padding-top: 4px;">
                                <span>Total Distance:</span>
                                <strong id="bpDistance">0.0 KM</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3 Sections of verification -->
                <div style="display: flex; flex-direction: column; gap: 30px;">
                    <!-- Section 1: Cash count & Denominations -->
                    <div style="background: #fff; border: 1px solid rgba(0,0,0,0.08); border-radius: 12px; padding: 25px; box-shadow: 0 4px 12px rgba(0,0,0,0.01);">
                        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(0,0,0,0.06); padding-bottom: 15px; margin-bottom: 20px;">
                            <h4 style="margin: 0; font-size: 15px; font-weight: 800; text-transform: uppercase; color: var(--text-main);">💵 Cash Count Denomination Check</h4>
                            <label style="display: inline-flex; align-items: center; gap: 8px; font-weight: bold; font-size: 13px; color: #2e7d32; cursor: pointer;">
                                <input type="checkbox" id="verifyCash" onchange="checkVerification()" style="width:16px; height:16px;"> Verified Cash Count
                            </label>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px;">
                            <div>
                                <table style="width: 100%; border-collapse: collapse; font-size: 13.5px;" id="denomsTable">
                                    <thead>
                                        <tr style="border-bottom: 2px solid rgba(0,0,0,0.05); text-align: left; color:#888; font-size:11px; text-transform:uppercase;">
                                            <th style="padding: 6px 0;">Denomination</th>
                                            <th style="padding: 6px 0; text-align: right;">Count</th>
                                            <th style="padding: 6px 0; text-align: right;">Total Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody id="denomsTableBody">
                                        <!-- JS populates -->
                                    </tbody>
                                </table>
                            </div>
                            <div style="background: rgba(0,0,0,0.015); padding: 20px; border-radius: 8px; border: 1px solid rgba(0,0,0,0.04); display: flex; flex-direction: column; justify-content: center; gap: 10px;">
                                <div style="display: flex; justify-content: space-between; font-size: 14px;">
                                    <span>Expected Cash Collections:</span>
                                    <strong style="font-family: monospace;" id="expectedCashVal">Rs 0.00</strong>
                                </div>
                                <div style="display: flex; justify-content: space-between; font-size: 14px;">
                                    <span>Total Cash Count Entered:</span>
                                    <strong style="font-family: monospace;" id="enteredCashVal">Rs 0.00</strong>
                                </div>
                                <div style="display: flex; justify-content: space-between; font-size: 15px; border-top: 1px solid rgba(0,0,0,0.08); padding-top: 8px; font-weight: bold;">
                                    <span>Variance / Difference:</span>
                                    <strong style="font-family: monospace;" id="diffCashVal">Rs 0.00</strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section 2: Cheques Verification -->
                    <div style="background: #fff; border: 1px solid rgba(0,0,0,0.08); border-radius: 12px; padding: 25px; box-shadow: 0 4px 12px rgba(0,0,0,0.01);">
                        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(0,0,0,0.06); padding-bottom: 15px; margin-bottom: 20px;">
                            <h4 style="margin: 0; font-size: 15px; font-weight: 800; text-transform: uppercase; color: var(--text-main);">💳 Collected Cheques Verification</h4>
                            <label style="display: inline-flex; align-items: center; gap: 8px; font-weight: bold; font-size: 13px; color: #2e7d32; cursor: pointer;">
                                <input type="checkbox" id="verifyCheques" onchange="checkVerification()" style="width:16px; height:16px;"> Verified Cheques
                            </label>
                        </div>
                        <table style="width: 100%; border-collapse: collapse; font-size: 13px;" class="data-table">
                            <thead>
                                <tr style="background: rgba(0,0,0,0.01);">
                                    <th style="width: 30px; text-align: center;">Verify</th>
                                    <th>Customer Name</th>
                                    <th>Bank Name</th>
                                    <th>Cheque Number</th>
                                    <th>Banking Date</th>
                                    <th style="text-align: right;">Amount (Rs)</th>
                                </tr>
                            </thead>
                            <tbody id="chequesTableBody">
                                <!-- JS populates -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Section 3: Stock Checklist -->
                    <div style="background: #fff; border: 1px solid rgba(0,0,0,0.08); border-radius: 12px; padding: 25px; box-shadow: 0 4px 12px rgba(0,0,0,0.01);">
                        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(0,0,0,0.06); padding-bottom: 15px; margin-bottom: 20px;">
                            <h4 style="margin: 0; font-size: 15px; font-weight: 800; text-transform: uppercase; color: var(--text-main);">📦 Vehicle Stock Balance Checklist</h4>
                            <label style="display: inline-flex; align-items: center; gap: 8px; font-weight: bold; font-size: 13px; color: #2e7d32; cursor: pointer;">
                                <input type="checkbox" id="verifyStock" onchange="checkVerification()" style="width:16px; height:16px;"> Verified Remaining Stock
                            </label>
                        </div>
                        <table style="width: 100%; border-collapse: collapse; font-size: 13px;" class="data-table">
                            <thead>
                                <tr style="background: rgba(0,0,0,0.01);">
                                    <th style="width: 30px; text-align: center;">Verify</th>
                                    <th>Product Name</th>
                                    <th style="text-align: right;">Loaded Qty</th>
                                    <th style="text-align: right;">Delivered Qty</th>
                                    <th style="text-align: right;">Returned Qty</th>
                                </tr>
                            </thead>
                            <tbody id="stockTableBody">
                                <!-- JS populates -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Section 4: Real-time Ledger Double Entry Control -->
                    <div style="background: #fff; border: 1px solid rgba(0,0,0,0.08); border-radius: 12px; padding: 25px; box-shadow: 0 4px 12px rgba(0,0,0,0.01);">
                        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(0,0,0,0.06); padding-bottom: 15px; margin-bottom: 20px;">
                            <h4 style="margin: 0; font-size: 15px; font-weight: 800; text-transform: uppercase; color: var(--text-main);">💼 Real-Time General Ledger Double-Entry Mapping</h4>
                            <div style="display: flex; gap: 15px; align-items: center;">
                                <button type="button" class="btn-premium secondary" onclick="toggleAllLedgerSelections(true)" style="padding: 4px 10px; font-size: 11px;">Select All</button>
                                <button type="button" class="btn-premium secondary" onclick="toggleAllLedgerSelections(false)" style="padding: 4px 10px; font-size: 11px;">Deselect All</button>
                            </div>
                        </div>

                        <!-- Double Entry Tabs -->
                        <div style="display: flex; border-bottom: 1px solid rgba(0,0,0,0.06); margin-bottom: 20px; gap: 10px;">
                            <button type="button" class="tab-btn active" id="deTabCollections" onclick="switchDeTab('collections')" style="font-size: 12px; padding: 8px 16px; border-bottom: 2px solid transparent; background: transparent; cursor: pointer; font-weight: bold; border: none; border-radius: 0;">💵 Collections & Clearing (Transit Sweep)</button>
                            <button type="button" class="tab-btn" id="deTabSales" onclick="switchDeTab('sales')" style="font-size: 12px; padding: 8px 16px; border-bottom: 2px solid transparent; background: transparent; cursor: pointer; font-weight: bold; border: none; border-radius: 0;">📦 Invoices Delivered (Sales Posting)</button>
                        </div>

                        <div id="deCollectionsSection">
                            <div style="display: flex; flex-direction: column; gap: 15px;" id="deCollectionsContainer">
                                <!-- JS populates -->
                            </div>
                        </div>

                        <div id="deSalesSection" style="display: none;">
                            <div style="display: flex; flex-direction: column; gap: 15px;" id="deSalesContainer">
                                <!-- JS populates -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Finalize Action Panel -->
                <div style="margin-top: 40px; padding-top: 25px; border-top: 1px solid rgba(0,0,0,0.08); display: flex; justify-content: space-between; align-items: center;">
                    <div id="balancingStatusText" style="font-size: 14px; color: #888;">
                        Please verify all 3 sections (Cash, Cheques, Stock) above to unlock Finalization.
                    </div>
                    <div>
                        <button id="btnFinalizeBalancing" class="btn-premium primary" style="padding: 14px 28px; font-size: 14px; opacity: 0.5; cursor: not-allowed;" disabled onclick="submitFinalization()">
                            ⚖️ Finalize & Balance Settle Route
                        </button>
                        <a id="btnBalancingReport" href="#" target="_blank" class="btn-premium primary" style="padding: 14px 28px; font-size: 14px; background: linear-gradient(135deg, #2e7d32, #1b5e20); display: none;">
                            📄 View Settle Balancing Report
                        </a>
                    </div>
                </div>
            </div>

            <table class="data-table" id="billsTable" style="display: none;">
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>Created Time</th>
                        <th>Customer Name</th>
                        <th style="text-align:right;">Grand Total (Rs)</th>
                        <th style="text-align:center;">Payment Status</th>
                    </tr>
                </thead>
                <tbody id="billsTableBody">
                    <!-- AJAX populates here -->
                </tbody>
            </table>
            
            <!-- Loading Indicator -->
            <div id="midLoader" style="display:none; position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); text-align:center; font-weight:bold; color:#007aff;">
                Retrieving Delivery Details... ⏳
            </div>
        </div>
    </div>

    <!-- Right Pane: Invoice Slide-Out Viewer -->
    <div class="pane-right-slider" id="invoiceSlider">
        <div class="slider-header">
            <span>Invoice Mini-Viewer</span>
            <div>
                <a id="btnEditInvoice" href="#" target="_blank" style="background: rgba(255,255,255,0.15); color: #fff; text-decoration: none; padding: 6px 12px; border-radius: 4px; font-size: 12px; margin-right: 15px; border: 1px solid rgba(255,255,255,0.3); font-weight:600;">✏️ Open in Sales</a>
                <button class="close-slider" onclick="closeInvoiceSlider()">✕</button>
            </div>
        </div>
        <!-- Iframe loads the actual generated invoice view -->
        <iframe id="invoiceIframe" src="about:blank"></iframe>
    </div>
</div>

<script>
    let currentDeliveryId = null;
    let currentInvoices = [];
    let currentCreditInvoices = [];
    let currentBalancingData = null;
    let activeTab = 'all'; // 'all', 'credit', or 'balancing'
    let currentStatusFilter = 'Arranged';

    // --- Page Load Initializer ---
    window.addEventListener('DOMContentLoaded', () => {
        setStatusFilter('Arranged');
    });

    // --- 1. Filter Deliveries on Left Side ---
    function setStatusFilter(status) {
        currentStatusFilter = status;
        document.querySelectorAll('.filter-pill').forEach(btn => {
            btn.classList.toggle('active', btn.innerText.includes(status) || (status === 'Arranged' && btn.innerText === 'Arranged'));
        });
        filterDeliveries();
    }

    function filterDeliveries() {
        const query = document.getElementById('searchField').value.toLowerCase();
        const items = document.querySelectorAll('.delivery-item');
        
        items.forEach(item => {
            const id = item.id.replace('delivery_', '');
            const meta = document.getElementById('del_data_' + id);
            const status = meta ? meta.getAttribute('data-status') : '';
            const text = item.innerText.toLowerCase();
            
            const matchesQuery = text.includes(query);
            
            let matchesStatus = false;
            if (currentStatusFilter === 'Arranged') {
                matchesStatus = (status === 'Arranged' || status === 'In Transit');
            } else if (currentStatusFilter === 'Completed') {
                matchesStatus = (status === 'Completed');
            } else if (currentStatusFilter === 'Finalized') {
                matchesStatus = (status === 'Finalized');
            }
            
            if (matchesQuery && matchesStatus) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });
    }

    // --- 2. Load Details via AJAX ---
    function loadDeliveryDetails(deliveryId, el) {
        currentDeliveryId = deliveryId;
        
        // Highlight active item
        document.querySelectorAll('.delivery-item').forEach(i => i.classList.remove('active'));
        if (el) {
            el.classList.add('active');
        } else {
            const item = document.getElementById('delivery_' + deliveryId);
            if (item) item.classList.add('active');
        }

        // Populate header details instantly from hidden dataset
        const d = document.getElementById('del_data_' + deliveryId);
        let status = 'Arranged';
        if (d) {
            status = d.getAttribute('data-status');
            document.getElementById('mhRouteName').innerText = d.getAttribute('data-rname');
            document.getElementById('mhRepName').innerText = d.getAttribute('data-rep');
            document.getElementById('mhVehicle').innerText = d.getAttribute('data-vehicle');
            document.getElementById('mhDriver').innerText = d.getAttribute('data-driver');
            document.getElementById('mhPartner').innerText = d.getAttribute('data-partner');
            document.getElementById('mhDate').innerText = d.getAttribute('data-date');
            document.getElementById('mhSales').innerText = d.getAttribute('data-sales');
            document.getElementById('mhBills').innerText = d.getAttribute('data-bills');
            
            // Update spreadsheet URL
            document.getElementById('btnSpreadsheet').href = '<?= APP_URL ?>/delivery/spreadsheet/' + deliveryId;
        }
        
        document.getElementById('midHeader').style.visibility = 'visible';
        document.getElementById('midTabs').style.visibility = 'visible';
        document.getElementById('midEmptyState').style.display = 'none';
        document.getElementById('billsTable').style.display = 'none';
        document.getElementById('balancingPanel').style.display = 'none';
        document.getElementById('midLoader').style.display = 'block';

        closeInvoiceSlider();

        // AJAX request to fetch bills & balancing summary
        fetch('<?= APP_URL ?>/delivery/api_get_delivery_details/' + deliveryId)
            .then(res => res.json())
            .then(res => {
                document.getElementById('midLoader').style.display = 'none';
                
                if (res.status === 'success') {
                    currentInvoices = res.invoices;
                    currentCreditInvoices = res.credit_invoices || [];
                    currentBalancingData = res.balancing;
                    
                    // Show or hide the balancing tab depending on delivery progress
                    const tabBalancing = document.getElementById('tabBalancing');
                    if (status === 'Completed' || status === 'Finalized') {
                        tabBalancing.style.display = 'inline-block';
                        // Auto switch to balancing tab for completed/finalized trips
                        switchTab('balancing');
                    } else {
                        tabBalancing.style.display = 'none';
                        switchTab('all');
                    }
                } else {
                    alert("Error: " + res.message);
                }
            })
            .catch(err => {
                document.getElementById('midLoader').style.display = 'none';
                alert("Failed to load delivery info. Network/server error.");
                console.error(err);
            });
    }

    // --- 3. Switch Tabs (All vs Credit vs Balancing) ---
    function switchTab(tab) {
        activeTab = tab;
        document.getElementById('tabAll').classList.toggle('active', tab === 'all');
        document.getElementById('tabCredit').classList.toggle('active', tab === 'credit');
        document.getElementById('tabBalancing').classList.toggle('active', tab === 'balancing');
        
        if (tab === 'balancing') {
            document.getElementById('billsTable').style.display = 'none';
            document.getElementById('balancingPanel').style.display = 'block';
            renderBalancing();
        } else {
            document.getElementById('balancingPanel').style.display = 'none';
            document.getElementById('billsTable').style.display = 'table';
            renderBills();
        }
    }

    // --- 4. Render Bills based on current invoices & active tab ---
    function renderBills() {
        const tbody = document.getElementById('billsTableBody');
        tbody.innerHTML = '';
        
        const invoicesToRender = activeTab === 'credit' 
            ? currentCreditInvoices 
            : currentInvoices;

        if (invoicesToRender.length === 0) {
            tbody.innerHTML = `<tr><td colspan="5" style="text-align:center; padding:40px; color:#888;">No bills match this filter.</td></tr>`;
            return;
        }

        invoicesToRender.forEach(bill => {
            let timeStr = new Date(bill.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
            let isUnpaid = bill.status === 'Unpaid';
            let statColor = bill.status === 'Paid' ? '#2e7d32' : (isUnpaid ? '#ef6c00' : '#666');
            let statBg = bill.status === 'Paid' ? 'rgba(46, 125, 50, 0.1)' : (isUnpaid ? 'rgba(239, 108, 0, 0.1)' : 'rgba(0,0,0,0.05)');

            tbody.innerHTML += `
                <tr class="bill-row" onclick="openInvoiceSlider(${bill.id})">
                    <td style="font-weight:700; color:#007aff;">${bill.invoice_number}</td>
                    <td style="color:#888;">${timeStr}</td>
                    <td style="font-weight:600;">${bill.customer_name}</td>
                    <td style="text-align:right; font-weight:700; font-family:monospace;">${parseFloat(bill.true_grand_total).toLocaleString('en-IN', {minimumFractionDigits:2})}</td>
                    <td style="text-align:center;">
                        <span style="color:${statColor}; background:${statBg}; padding:4px 10px; border-radius:12px; font-weight:700; font-size:10px; text-transform:uppercase; letter-spacing:0.3px;">
                            ${bill.status}
                        </span>
                    </td>
                </tr>
            `;
        });
    }

    // --- 5. Render Settle Balancing Info ---
    let activeDeTab = 'collections';

    function switchDeTab(tab) {
        activeDeTab = tab;
        document.getElementById('deTabCollections').classList.toggle('active', tab === 'collections');
        document.getElementById('deTabCollections').style.borderBottom = tab === 'collections' ? '2px solid #007aff' : '2px solid transparent';
        
        document.getElementById('deTabSales').classList.toggle('active', tab === 'sales');
        document.getElementById('deTabSales').style.borderBottom = tab === 'sales' ? '2px solid #007aff' : '2px solid transparent';

        document.getElementById('deCollectionsSection').style.display = tab === 'collections' ? 'block' : 'none';
        document.getElementById('deSalesSection').style.display = tab === 'sales' ? 'block' : 'none';
    }

    function toggleAllLedgerSelections(isChecked) {
        const checkBoxes = activeDeTab === 'collections'
            ? document.querySelectorAll('.de-payment-chk')
            : document.querySelectorAll('.de-invoice-chk');
        checkBoxes.forEach(cb => {
            if (!cb.disabled) cb.checked = isChecked;
        });
    }

    function renderAccountSelect(id, type, selectedCode) {
        if (!currentBalancingData || !currentBalancingData.all_accounts) return '';
        let optionsHtml = '';
        currentBalancingData.all_accounts.forEach(acc => {
            let isSel = acc.account_code === selectedCode ? 'selected' : '';
            optionsHtml += `<option value="${acc.id}" ${isSel}>${acc.account_code} - ${acc.account_name}</option>`;
        });
        return `
            <select class="de-select" data-id="${id}" data-type="${type}" style="padding: 6px 12px; border-radius: 6px; border: 1px solid rgba(0,0,0,0.12); font-size: 12px; min-width: 200px; background: #fff; color: #333; cursor: pointer; font-weight: 600; outline: none; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                ${optionsHtml}
            </select>
        `;
    }

    function renderDeCollections() {
        const container = document.getElementById('deCollectionsContainer');
        container.innerHTML = '';
        const payments = currentBalancingData.payments || [];
        const isFinal = currentBalancingData.delivery.status === 'Finalized';

        if (payments.length === 0) {
            container.innerHTML = `<div style="text-align:center; padding:20px; color:#888;">No payments logged on this trip.</div>`;
            return;
        }

        payments.forEach(p => {
            let defaultDebitCode = '1000'; // Cash default
            if (p.payment_method === 'Cheque') {
                defaultDebitCode = '1010';
            } else if (p.payment_method === 'Bank Transfer') {
                defaultDebitCode = '1605';
            }

            container.innerHTML += `
                <div class="payment-de-card" style="display:flex; justify-content:space-between; align-items:center; background: rgba(0,0,0,0.02); border: 1px solid rgba(0,0,0,0.05); padding: 15px 20px; border-radius: 10px; gap: 20px;">
                    <div style="display:flex; align-items:center; gap:12px; flex: 1;">
                        <input type="checkbox" class="de-payment-chk" value="${p.id}" ${isFinal ? 'checked disabled' : 'checked'} style="width:18px; height:18px; cursor: pointer;">
                        <div>
                            <div style="font-weight: 700; color: var(--text-main); font-size:14px;">${p.customer_name}</div>
                            <div style="font-size:11.5px; color:#888; margin-top:2px;">Method: <strong>${p.payment_method}</strong> | Ref: <strong>${p.reference || '-'}</strong></div>
                        </div>
                    </div>
                    <div style="font-family:monospace; font-weight:700; font-size:14px; text-align:right; min-width: 110px; color:#2e7d32;">
                        Rs ${parseFloat(p.amount).toLocaleString('en-IN', {minimumFractionDigits:2})}
                    </div>
                    <div style="display:flex; align-items:center; gap:15px;">
                        <div style="display:flex; flex-direction:column; gap:4px;">
                            <span style="font-size:9.5px; text-transform:uppercase; color:#888; font-weight:bold; letter-spacing:0.5px;">Debit Account (Asset Destination)</span>
                            ${renderAccountSelect(p.id, 'debit', defaultDebitCode)}
                        </div>
                        <div style="display:flex; flex-direction:column; gap:4px;">
                            <span style="font-size:9.5px; text-transform:uppercase; color:#888; font-weight:bold; letter-spacing:0.5px;">Credit Account (Transit Source)</span>
                            ${renderAccountSelect(p.id, 'credit', '1090')}
                        </div>
                    </div>
                </div>
            `;
        });

        if (isFinal) {
            document.querySelectorAll('.de-select').forEach(sel => sel.disabled = true);
        }
    }

    function renderDeSales() {
        const container = document.getElementById('deSalesContainer');
        container.innerHTML = '';
        const invoices = currentInvoices.filter(inv => inv.delivery_status === 'Delivered');
        const isFinal = currentBalancingData.delivery.status === 'Finalized';

        if (invoices.length === 0) {
            container.innerHTML = `<div style="text-align:center; padding:20px; color:#888;">No delivered sales invoices on this trip.</div>`;
            return;
        }

        invoices.forEach(inv => {
            container.innerHTML += `
                <div class="sales-de-card" style="display:flex; justify-content:space-between; align-items:center; background: rgba(0,0,0,0.02); border: 1px solid rgba(0,0,0,0.05); padding: 15px 20px; border-radius: 10px; gap: 20px;">
                    <div style="display:flex; align-items:center; gap:12px; flex: 1;">
                        <input type="checkbox" class="de-invoice-chk" value="${inv.id}" ${isFinal ? 'checked disabled' : 'checked'} style="width:18px; height:18px; cursor: pointer;">
                        <div>
                            <div style="font-weight: 700; color: #007aff; font-size:14px;">${inv.invoice_number}</div>
                            <div style="font-size:11.5px; color:#888; margin-top:2px;">Customer: <strong>${inv.customer_name}</strong></div>
                        </div>
                    </div>
                    <div style="font-family:monospace; font-weight:700; font-size:14px; text-align:right; min-width: 110px; color: var(--text-main);">
                        Rs ${parseFloat(inv.true_grand_total).toLocaleString('en-IN', {minimumFractionDigits:2})}
                    </div>
                    <div style="display:flex; align-items:center; gap:15px;">
                        <div style="display:flex; flex-direction:column; gap:4px;">
                            <span style="font-size:9.5px; text-transform:uppercase; color:#888; font-weight:bold; letter-spacing:0.5px;">Debit Account (Accounts Receivable)</span>
                            ${renderAccountSelect("inv_" + inv.id, 'debit', '1200')}
                        </div>
                        <div style="display:flex; flex-direction:column; gap:4px;">
                            <span style="font-size:9.5px; text-transform:uppercase; color:#888; font-weight:bold; letter-spacing:0.5px;">Credit Account (Sales Revenue)</span>
                            ${renderAccountSelect("inv_" + inv.id, 'credit', '4000')}
                        </div>
                    </div>
                </div>
            `;
        });

        if (isFinal) {
            document.querySelectorAll('.de-select').forEach(sel => sel.disabled = true);
        }
    }

    function renderBalancing() {
        if (!currentBalancingData) return;
        
        const b = currentBalancingData;
        const d = b.delivery;
        const isFinal = d.status === 'Finalized';

        // 1. Set Sales summary
        document.getElementById('bpCashSales').innerText = 'Rs ' + b.cash_sales.toLocaleString('en-IN', {minimumFractionDigits:2});
        document.getElementById('bpCreditSales').innerText = 'Rs ' + b.credit_sales.toLocaleString('en-IN', {minimumFractionDigits:2});

        // 2. Set Collections summary
        document.getElementById('bpCashColl').innerText = 'Rs ' + b.cash_collections.toLocaleString('en-IN', {minimumFractionDigits:2});
        document.getElementById('bpChequeColl').innerText = 'Rs ' + b.cheque_collections.toLocaleString('en-IN', {minimumFractionDigits:2});
        document.getElementById('bpBankColl').innerText = 'Rs ' + b.bank_collections.toLocaleString('en-IN', {minimumFractionDigits:2});

        // 3. Set Mileage
        const startMeter = parseFloat(d.start_meter || 0);
        const endMeter = parseFloat(d.end_meter || 0);
        const diffMeter = Math.max(0, endMeter - startMeter);
        document.getElementById('bpStartMeter').innerText = startMeter.toFixed(0) + ' KM';
        document.getElementById('bpEndMeter').innerText = endMeter.toFixed(0) + ' KM';
        document.getElementById('bpDistance').innerText = diffMeter.toFixed(0) + ' KM';

        // 4. Render Cash Denominations
        let cashDenoms = {};
        try {
            if (d.cash_denominations) {
                cashDenoms = JSON.parse(d.cash_denominations);
            }
        } catch(e) {
            console.error("JSON parsing error on denominations: ", e);
        }

        const denomList = [5000, 2000, 1000, 500, 100, 50, 20, 'coins'];
        const tbodyDenom = document.getElementById('denomsTableBody');
        tbodyDenom.innerHTML = '';
        let totalCashEntered = 0.0;

        denomList.forEach(den => {
            const count = parseInt(cashDenoms[den] || 0);
            let val = 0.0;
            let label = '';
            if (den === 'coins') {
                val = parseFloat(cashDenoms['coins'] || 0.0);
                label = 'Coins';
            } else {
                val = den * count;
                label = 'Rs ' + den;
            }
            totalCashEntered += val;

            tbodyDenom.innerHTML += `
                <tr style="border-bottom: 1px solid rgba(0,0,0,0.03);">
                    <td style="padding: 6px 0; font-weight: 600;">${label}</td>
                    <td style="padding: 6px 0; text-align: right;">${den === 'coins' ? '-' : count}</td>
                    <td style="padding: 6px 0; text-align: right; font-family: monospace;">${val.toLocaleString('en-IN', {minimumFractionDigits:2})}</td>
                </tr>
            `;
        });

        const expectedCash = b.cash_collections;
        const diffCash = totalCashEntered - expectedCash;

        document.getElementById('expectedCashVal').innerText = 'Rs ' + expectedCash.toLocaleString('en-IN', {minimumFractionDigits:2});
        document.getElementById('enteredCashVal').innerText = 'Rs ' + totalCashEntered.toLocaleString('en-IN', {minimumFractionDigits:2});
        
        const diffEl = document.getElementById('diffCashVal');
        diffEl.innerText = (diffCash >= 0 ? '+' : '') + 'Rs ' + diffCash.toLocaleString('en-IN', {minimumFractionDigits:2});
        if (Math.abs(diffCash) < 0.01) {
            diffEl.style.color = '#2e7d32'; // Match exactly
        } else if (diffCash < 0) {
            diffEl.style.color = '#c62828'; // Shortage
        } else {
            diffEl.style.color = '#ef6c00'; // Surplus
        }

        // 5. Render Cheques table
        const tbodyCheque = document.getElementById('chequesTableBody');
        tbodyCheque.innerHTML = '';
        if (!b.cheques || b.cheques.length === 0) {
            tbodyCheque.innerHTML = `<tr><td colspan="6" style="text-align:center; padding:20px; color:#888;">No cheques collected on this trip.</td></tr>`;
        } else {
            b.cheques.forEach(ch => {
                tbodyCheque.innerHTML += `
                    <tr style="border-bottom: 1px solid rgba(0,0,0,0.05);">
                        <td style="text-align: center; padding: 10px 0;">
                            <input type="checkbox" class="cheque-row-chk" onchange="checkVerification()" style="width:16px; height:16px;">
                        </td>
                        <td style="font-weight: 600;">${ch.customer_name}</td>
                        <td>${ch.bank_name}</td>
                        <td style="font-family: monospace;">${ch.cheque_number}</td>
                        <td>${new Date(ch.banking_date).toLocaleDateString()}</td>
                        <td style="text-align: right; font-weight: 700; font-family: monospace;">${parseFloat(ch.amount).toLocaleString('en-IN', {minimumFractionDigits:2})}</td>
                    </tr>
                `;
            });
        }

        // 6. Render Stock summary table
        const tbodyStock = document.getElementById('stockTableBody');
        tbodyStock.innerHTML = '';
        if (!b.stock_items || b.stock_items.length === 0) {
            tbodyStock.innerHTML = `<tr><td colspan="5" style="text-align:center; padding:20px; color:#888;">No products loaded on this route.</td></tr>`;
        } else {
            b.stock_items.forEach(st => {
                tbodyStock.innerHTML += `
                    <tr style="border-bottom: 1px solid rgba(0,0,0,0.05);">
                        <td style="text-align: center; padding: 10px 0;">
                            <input type="checkbox" class="stock-row-chk" onchange="checkVerification()" style="width:16px; height:16px;">
                        </td>
                        <td style="font-weight: 600;">${st.item_name}</td>
                        <td style="text-align: right; font-weight: 700;">${parseInt(st.loaded_qty)}</td>
                        <td style="text-align: right; font-weight: 700; color: #2e7d32;">${parseInt(st.delivered_qty)}</td>
                        <td style="text-align: right; font-weight: 700; color: #ef6c00;">
                            <input type="number" class="actual-returned-qty-input" 
                                   data-item-name="${st.item_name}" 
                                   data-item-id="${st.item_id}" 
                                   data-var-id="${st.variation_option_id || 0}" 
                                   data-loaded-qty="${st.loaded_qty}"
                                   data-delivered-qty="${st.delivered_qty}"
                                   value="${parseInt(st.remaining_qty)}" 
                                   min="0" 
                                   style="width: 80px; text-align: right; padding: 4px 8px; border: 1px solid rgba(0,0,0,0.15); border-radius: 6px; font-weight: bold; background: rgba(255,255,255,0.95); color: #333;" 
                                   ${isFinal ? 'disabled' : ''}>
                        </td>
                    </tr>
                `;
            });
        }

        // Render Section 4 interactive lists
        renderDeCollections();
        renderDeSales();
        switchDeTab('collections');

        // 7. Verify Checkboxes & Action Buttons based on status
        const verifyCashBox = document.getElementById('verifyCash');
        const verifyChequeBox = document.getElementById('verifyCheques');
        const verifyStockBox = document.getElementById('verifyStock');
        const btnFinalize = document.getElementById('btnFinalizeBalancing');
        const btnReport = document.getElementById('btnBalancingReport');
        const statusText = document.getElementById('balancingStatusText');

        if (isFinal) {
            // Already Finalized: Hide checkboxes, show finalized status and report button
            verifyCashBox.checked = true;
            verifyCashBox.disabled = true;
            verifyChequeBox.checked = true;
            verifyChequeBox.disabled = true;
            verifyStockBox.checked = true;
            verifyStockBox.disabled = true;

            document.querySelectorAll('.cheque-row-chk, .stock-row-chk').forEach(chk => {
                chk.checked = true;
                chk.disabled = true;
            });

            btnFinalize.style.display = 'none';
            btnReport.style.display = 'inline-block';
            btnReport.href = '<?= APP_URL ?>/delivery/balancing_report/' + d.id;
            statusText.innerHTML = `<span style="color: #2e7d32; font-weight: bold; font-size: 15px;">✅ Settle Balancing Finalized</span><br>Physical stock has been adjusted and transit payments cleared.`;
        } else {
            // Not Finalized: Show active inputs and finalize button
            verifyCashBox.checked = false;
            verifyCashBox.disabled = false;
            verifyChequeBox.checked = false;
            verifyChequeBox.disabled = false;
            verifyStockBox.checked = false;
            verifyStockBox.disabled = false;

            btnFinalize.style.display = 'inline-block';
            btnFinalize.disabled = true;
            btnFinalize.style.opacity = '0.5';
            btnFinalize.style.cursor = 'not-allowed';
            btnReport.style.display = 'none';
            statusText.innerHTML = `Please verify all 3 sections (Cash, Cheques, Stock) above to unlock Finalization.`;
        }
    }

    // --- 6. Verification state listener ---
    function checkVerification() {
        const verifyCash = document.getElementById('verifyCash').checked;
        const verifyChequesMain = document.getElementById('verifyCheques').checked;
        const verifyStockMain = document.getElementById('verifyStock').checked;

        // Individual item counts
        const chequeCheckboxes = document.querySelectorAll('.cheque-row-chk');
        let allChequesTicked = true;
        chequeCheckboxes.forEach(chk => {
            if (!chk.checked) allChequesTicked = false;
        });

        const stockCheckboxes = document.querySelectorAll('.stock-row-chk');
        let allStockTicked = true;
        stockCheckboxes.forEach(chk => {
            if (!chk.checked) allStockTicked = false;
        });

        const isFullyVerified = verifyCash && verifyChequesMain && verifyStockMain && allChequesTicked && allStockTicked;

        const btnFinalize = document.getElementById('btnFinalizeBalancing');
        const statusText = document.getElementById('balancingStatusText');

        if (isFullyVerified) {
            btnFinalize.disabled = false;
            btnFinalize.style.opacity = '1';
            btnFinalize.style.cursor = 'pointer';
            statusText.innerHTML = `<span style="color: #007aff; font-weight: bold;">Ready to Settle!</span> Click finalize below to post accounting entries and update inventory.`;
        } else {
            btnFinalize.disabled = true;
            btnFinalize.style.opacity = '0.5';
            btnFinalize.style.cursor = 'not-allowed';
            statusText.innerHTML = `Please verify all 3 sections (Cash, Cheques, Stock) above to unlock Finalization.`;
        }
    }

    // --- 7. Submit Finalization Request ---
    function submitFinalization() {
        if (!confirm("Are you sure you want to FINALIZE this delivery trip?\n\nThis will:\n1. Deduct delivered stock from warehouse inventory.\n2. Release reserved stock back into available stock.\n3. Transfer selected collections and post sales invoices to General Ledger with specified double-entry accounts.")) {
            return;
        }

        const btnFinalize = document.getElementById('btnFinalizeBalancing');
        btnFinalize.disabled = true;
        btnFinalize.innerText = "Finalizing Delivery... ⏳";

        const selectedPaymentIds = [];
        document.querySelectorAll('.de-payment-chk:checked').forEach(cb => {
            selectedPaymentIds.push(parseInt(cb.value));
        });

        const selectedInvoiceIds = [];
        document.querySelectorAll('.de-invoice-chk:checked').forEach(cb => {
            selectedInvoiceIds.push(parseInt(cb.value));
        });

        const debitAccounts = {};
        const creditAccounts = {};
        document.querySelectorAll('.de-select').forEach(sel => {
            const id = sel.getAttribute('data-id');
            const type = sel.getAttribute('data-type');
            const val = parseInt(sel.value);
            if (type === 'debit') {
                debitAccounts[id] = val;
            } else {
                creditAccounts[id] = val;
            }
        });

        const returnedItems = [];
        document.querySelectorAll('.actual-returned-qty-input').forEach(input => {
            returnedItems.push({
                item_name: input.getAttribute('data-item-name'),
                item_id: parseInt(input.getAttribute('data-item-id') || 0),
                variation_option_id: parseInt(input.getAttribute('data-var-id') || 0),
                loaded_qty: parseFloat(input.getAttribute('data-loaded-qty') || 0),
                delivered_qty: parseFloat(input.getAttribute('data-delivered-qty') || 0),
                actual_returned_qty: parseFloat(input.value || 0)
            });
        });

        fetch('<?= APP_URL ?>/delivery/finalize', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                delivery_id: currentDeliveryId,
                selected_payment_ids: selectedPaymentIds,
                selected_invoice_ids: selectedInvoiceIds,
                debit_accounts: debitAccounts,
                credit_accounts: creditAccounts,
                returned_items: returnedItems
            })
        })
        .then(res => res.json())
        .then(res => {
            if (res.status === 'success') {
                alert("Delivery settled and finalized successfully!");
                
                // Update local HTML status elements
                const meta = document.getElementById('del_data_' + currentDeliveryId);
                if (meta) meta.setAttribute('data-status', 'Finalized');
                
                const item = document.getElementById('delivery_' + currentDeliveryId);
                if (item) {
                    const badge = item.querySelector('.status-badge');
                    if (badge) {
                        badge.innerText = 'Finalized';
                        badge.className = 'status-badge status-finalized';
                    }
                }
                
                // Swap view status filter to Finalized list and reload details
                setStatusFilter('Finalized');
                loadDeliveryDetails(currentDeliveryId);
            } else {
                alert("Error during finalization: " + res.message);
                btnFinalize.disabled = false;
                btnFinalize.innerText = "⚖️ Finalize & Balance Settle Route";
            }
        })
        .catch(err => {
            alert("Failed to connect to server. Database transaction aborted.");
            console.error(err);
            btnFinalize.disabled = false;
            btnFinalize.innerText = "⚖️ Finalize & Balance Settle Route";
        });
    }

    // --- 8. Slide-out Invoice Mini-View iframe ---
    function openInvoiceSlider(invoiceId) {
        const slider = document.getElementById('invoiceSlider');
        const iframe = document.getElementById('invoiceIframe');
        
        // Setup direct link to standard invoice view
        document.getElementById('btnEditInvoice').href = '<?= APP_URL ?>/sales/show/' + invoiceId;

        // Load the invoice display page in the frame
        iframe.src = '<?= APP_URL ?>/sales/show/' + invoiceId;
        
        // Transform slide open
        slider.classList.add('open');
    }

    function closeInvoiceSlider() {
        const slider = document.getElementById('invoiceSlider');
        slider.classList.remove('open');
        setTimeout(() => { document.getElementById('invoiceIframe').src = 'about:blank'; }, 300);
    }
</script>
