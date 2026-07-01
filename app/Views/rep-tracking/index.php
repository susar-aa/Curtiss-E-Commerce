<?php $isHistory = $data['is_history'] ?? false; ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
    /* Workspace Active Visual Density Optimizations */
    body.workspace-showing .app-container {
        margin-top: 68px !important;
        height: calc(100vh - 68px) !important;
    }
    body.workspace-showing .main-content {
        padding: 12px 16px 12px 16px !important;
        height: 100% !important;
        overflow: hidden !important;
    }
    body.workspace-showing .app-workspace {
        height: 100% !important;
        border-radius: var(--r-lg) !important;
    }

    /* Dots Menu styles */
    .dots-menu-container {
        position: relative;
        display: inline-block;
    }
    .dots-btn {
        background: none;
        border: none;
        cursor: pointer;
        padding: 6px;
        font-size: 16px;
        color: var(--t-secondary);
        border-radius: 50%;
        transition: background 0.2s;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 28px;
        height: 28px;
    }
    .dots-btn:hover {
        background: var(--c-fill);
        color: var(--t-primary);
    }
    .dots-dropdown {
        display: none;
        position: absolute;
        right: 0;
        top: 100%;
        background: rgba(255, 255, 255, 0.78) !important;
        backdrop-filter: blur(20px) saturate(190%) !important;
        -webkit-backdrop-filter: blur(20px) saturate(190%) !important;
        border: 0.5px solid rgba(0, 0, 0, 0.15) !important;
        border-radius: 12px !important;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08), 0 1px 3px rgba(0, 0, 0, 0.02) !important;
        z-index: 1001;
        min-width: 190px;
        padding: 6px;
        margin-top: 6px;
        overflow: hidden;
    }
    .dots-dropdown.show {
        display: block;
    }
    .dots-dropdown-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-direction: row-reverse;
        gap: 12px;
        padding: 9px 12px;
        font-size: 14px;
        font-weight: 450;
        color: #0f172a !important;
        cursor: pointer;
        text-align: left;
        transition: background 0.12s;
        width: 100%;
        border: none;
        background: none;
        border-radius: 8px;
    }
    .dots-dropdown-item:hover {
        background: rgba(0, 0, 0, 0.05) !important;
    }
    .dots-dropdown-item i {
        font-size: 16px;
        color: rgba(15, 23, 42, 0.6) !important;
    }
    .dots-dropdown-item.danger {
        color: #ff3b30 !important;
    }
    .dots-dropdown-item.danger i {
        color: #ff3b30 !important;
    }
    .dots-dropdown-item.danger:hover {
        background: rgba(255, 59, 48, 0.08) !important;
    }
    .dots-dropdown-divider {
        height: 0.5px;
        background: rgba(0, 0, 0, 0.1);
        margin: 4px 6px;
    }
    
    @media (prefers-color-scheme: dark) {
        .dots-dropdown {
            background: rgba(28, 28, 30, 0.82) !important;
            border: 0.5px solid rgba(255, 255, 255, 0.15) !important;
        }
        .dots-dropdown-item {
            color: #f8fafc !important;
        }
        .dots-dropdown-item i {
            color: rgba(248, 250, 252, 0.6) !important;
        }
        .dots-dropdown-item:hover {
            background: rgba(255, 255, 255, 0.08) !important;
        }
        .dots-dropdown-divider {
            background: rgba(255, 255, 255, 0.1);
        }
    }

    /* ============================================================
       SF PRO + APPLE DESIGN LANGUAGE — REP TRACKING MODULE
       ============================================================ */

    :root {
        --c-bg:           #f2f2f7;
        --c-surface:      #ffffff;
        --c-surface2:     #f9f9fb;
        --c-fill:         rgba(120,120,128,0.08);
        --c-fill2:        rgba(120,120,128,0.12);
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
        --r-xl: 20px;
        --r-xl2: 26px;
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

    /* 3-Pane Layout System */
    .app-workspace {
        display: flex;
        height: calc(100vh - 55px);
        background: var(--c-bg);
        border-radius: var(--r-xl);
        overflow: hidden;
        border: 0.5px solid var(--c-separator);
        position: relative;
        font-family: var(--f-system);
        color: var(--t-primary);
    }

    /* Left Pane: Route List */
    .pane-left {
        width: 360px;
        background: var(--c-surface);
        border-right: 0.5px solid var(--c-separator);
        display: flex;
        flex-direction: column;
        z-index: 10;
        flex-shrink: 0;
    }

    .route-item {
        padding: 16px;
        border-bottom: 0.5px solid var(--c-separator2);
        cursor: pointer;
        user-select: none;
        transition: background var(--dur-fast), transform var(--dur-fast);
        display: flex;
        flex-direction: column;
        background: var(--c-surface);
        border: 0.5px solid var(--c-separator);
        border-radius: var(--r-md);
        margin: 12px 12px 0 12px;
        box-shadow: var(--shadow-xs);
    }
    .route-item:hover {
        background: var(--c-surface2);
        transform: translateY(-1px);
        box-shadow: var(--shadow-sm);
    }
    .route-item.active {
        background: var(--c-surface);
        border-color: var(--c-blue);
        box-shadow: 0 0 0 1.5px var(--c-blue), var(--shadow-md);
    }
    
    .r-title {
        font-weight: 700;
        font-size: 15px;
        color: var(--t-primary);
        margin-bottom: 4px;
        line-height: 1.25;
    }
    .r-sub {
        font-size: 11px;
        color: var(--t-secondary);
        font-weight: 700;
        text-transform: uppercase;
        margin-bottom: 6px;
        letter-spacing: 0.04em;
    }
    .r-meta {
        font-size: 12.5px;
        color: var(--t-secondary);
        display: flex;
        justify-content: space-between;
    }
    
    .status-dot {
        display: inline-block;
        width: 8px;
        height: 8px;
        border-radius: 50%;
        margin-right: 6px;
    }
    .status-Active { background: var(--c-orange); }
    .status-Completed { background: var(--c-green); }

    /* Middle Pane: Workspace */
    .pane-middle {
        flex: 1;
        display: flex;
        flex-direction: column;
        background: var(--c-surface2);
        position: relative;
        min-width: 0;
    }
    .mid-header {
        padding: 12px 20px;
        border-bottom: 0.5px solid var(--c-separator);
        background: var(--c-surface);
        display: flex;
        flex-direction: row;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
    }
    
    .data-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
        background: var(--c-surface);
        border-radius: var(--r-md);
        overflow: hidden;
        border: 0.5px solid var(--c-separator);
    }
    .data-table th {
        padding: 8px 12px;
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
    .data-table td {
        padding: 8px 12px;
        font-size: 13.5px;
        color: var(--t-primary);
        border-bottom: 0.5px solid var(--c-separator2);
        vertical-align: middle;
    }
    .data-table tbody tr:last-child td {
        border-bottom: none;
    }
    .data-table tbody tr {
        transition: background var(--dur-fast);
    }
    .data-table tbody tr:hover {
        background: var(--c-fill);
    }
    
    .bill-row { cursor: pointer; transition: 0.1s; user-select: none; }
    .bill-row:hover { background: var(--c-fill2); }

    .empty-state {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        height: 100%;
        color: var(--t-tertiary);
    }
    .empty-state span { font-size: 48px; margin-bottom: 12px; opacity: 0.4; }

    /* Right Pane: Slide-out Invoice Mini-View */
    .pane-right-slider { 
        position: absolute; top: 0; right: 0; bottom: 0; width: 50%; 
        background: var(--c-surface); border-left: 0.5px solid var(--c-separator); 
        box-shadow: var(--shadow-xl); transform: translateX(100%); 
        transition: transform var(--dur-mid) var(--ease-ios); z-index: 150;
        display: flex; flex-direction: column;
    }
    .pane-right-slider.open { transform: translateX(0); }
    
    .slider-header {
        padding: 16px 20px;
        background: var(--c-surface2);
        border-bottom: 0.5px solid var(--c-separator);
        color: var(--t-primary);
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-weight: 700;
        font-size: 15px;
    }
    .slider-header button {
        cursor: pointer;
    }
    
    #invoiceIframe { width: 100%; flex: 1; border: none; background: #525659; }

    /* Highlights & Stats */
    .stat-box {
        background: var(--c-surface2);
        border: 0.5px solid var(--c-separator);
        padding: 10px 16px;
        border-radius: var(--r-md);
        text-align: center;
        box-shadow: var(--shadow-xs);
    }
    .stat-box span {
        display: block;
        font-size: 10px;
        color: var(--t-label);
        text-transform: uppercase;
        font-weight: 700;
        margin-bottom: 4px;
        letter-spacing: 0.04em;
    }
    .stat-box strong {
        font-size: 18px;
        color: var(--t-primary);
        font-weight: 700;
    }

    /* Modal styles */
    .modal-backdrop {
        display: none;
        position: fixed;
        top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0, 0, 0, 0.4);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        z-index: 2000;
        align-items: center;
        justify-content: center;
    }
    .modal-panel {
        background: var(--c-surface);
        width: 100%;
        max-width: 480px;
        border-radius: var(--r-xl);
        box-shadow: var(--shadow-xl);
        border: 0.5px solid var(--c-separator);
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }
    .modal-header {
        padding: 16px 20px;
        background: var(--c-surface2) !important;
        border-bottom: 0.5px solid var(--c-separator) !important;
        color: var(--t-primary) !important;
        font-weight: 700;
        font-size: 15px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .modal-header button {
        background: var(--c-fill) !important;
        border: none !important;
        color: var(--t-secondary) !important;
        width: 26px !important;
        height: 26px !important;
        border-radius: 50% !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        font-size: 11px !important;
        cursor: pointer !important;
        font-weight: 700 !important;
        transition: background var(--dur-fast), color var(--dur-fast) !important;
    }
    .modal-header button:hover {
        background: var(--c-fill2) !important;
        color: var(--t-primary) !important;
    }
    .modal-body {
        padding: 20px;
        display: flex;
        flex-direction: column;
        gap: 16px;
    }
    .modal-body label {
        font-weight: 600;
        font-size: 11px;
        text-transform: uppercase;
        color: var(--t-label);
        margin-bottom: 6px;
        display: block;
        letter-spacing: 0.04em;
    }
    .modal-body input, .modal-body select, .modal-body textarea {
        width: 100%;
        padding: 8px 12px;
        border: 0.5px solid var(--c-separator);
        border-radius: var(--r-xs);
        box-sizing: border-box;
        font-size: 13.5px;
        background: var(--c-surface);
        color: var(--t-primary);
        outline: none;
        transition: border-color var(--dur-fast);
    }
    .modal-body input:focus, .modal-body select:focus, .modal-body textarea:focus {
        border-color: var(--c-blue);
    }
    .modal-footer {
        padding: 16px 20px;
        background: var(--c-surface2);
        border-top: 0.5px solid var(--c-separator);
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }

    /* GPS path map overlay */
    .map-empty-overlay {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(255,255,255,0.8);
        color: var(--t-secondary);
        font-size: 13.5px;
        font-weight: 600;
        text-align: center;
        padding: 20px;
        z-index: 500;
        pointer-events: none;
    }
    @media (prefers-color-scheme: dark) {
        .map-empty-overlay { background: rgba(30,30,46,0.85); color: var(--t-secondary); }
    }
    
    .path-step-list {
        max-height: 120px;
        overflow-y: auto;
        padding: 12px 20px;
        font-size: 11px;
        color: var(--t-secondary);
        background: var(--c-surface);
        border-top: 0.5px solid var(--c-separator);
        flex-shrink: 0;
    }
    .path-step-list ol { margin: 0; padding-left: 18px; }
    .path-step-list li { margin-bottom: 2px; }
    .path-step-start { color: var(--c-green); font-weight: 700; }
    .path-step-invoice { color: var(--c-blue); }
    .path-step-end { color: var(--c-red); font-weight: 700; }

    /* Left side Tabs styling */
    .left-tab-btn {
        flex: 1;
        padding: 8px 12px;
        font-size: 12px;
        font-weight: 600;
        border-radius: var(--r-sm);
        border: 0.5px solid var(--c-separator);
        background: var(--c-surface);
        color: var(--t-secondary);
        cursor: pointer;
        white-space: nowrap;
        transition: all var(--dur-fast);
    }
    .left-tab-btn.active {
        background: var(--c-blue);
        color: white;
        border-color: var(--c-blue);
        box-shadow: var(--shadow-xs);
    }

    /* Binding panel */
    .rb-slot-column {
        background: var(--c-surface2);
        border: 0.5px solid var(--c-separator);
        border-radius: var(--r-md);
        padding: 16px;
        display: flex;
        flex-direction: column;
        gap: 12px;
        box-shadow: var(--shadow-xs);
    }
    .rb-slot-box {
        border: 1.5px dashed var(--c-separator);
        border-radius: var(--r-sm);
        padding: 20px;
        text-align: center;
        background: var(--c-surface);
        cursor: pointer;
        transition: all var(--dur-fast) var(--ease-ios);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        min-height: 80px;
    }
    .rb-slot-box:hover {
        border-color: var(--c-blue);
        background: var(--c-blue-light);
    }
    .rb-slot-select {
        width: 100%;
        padding: 8px 12px;
        border: 0.5px solid var(--c-separator);
        border-radius: var(--r-xs);
        font-size: 13px;
        background: var(--c-surface);
        color: var(--t-primary);
        margin-top: 5px;
    }
    .rb-bill-list {
        max-height: 180px;
        overflow-y: auto;
        border: 0.5px solid var(--c-separator);
        border-radius: var(--r-sm);
        background: var(--c-surface);
        padding: 8px;
        font-size: 12px;
        display: none;
    }
    .rb-bill-item {
        display: flex;
        justify-content: space-between;
        padding: 6px 8px;
        border-bottom: 0.5px solid var(--c-separator2);
        align-items: center;
    }
    .rb-bill-item:last-child {
        border-bottom: none;
    }
    .rb-bound-tag {
        margin-top: 6px;
        font-size: 11px;
        background: var(--c-blue-light);
        color: var(--c-blue);
        padding: 3px 8px;
        border-radius: var(--r-xs);
        display: inline-block;
        font-weight: 700;
        border: 0.5px solid var(--c-blue-mid);
    }

    /* Scrollable Stage Tab styling (deprecated/hidden for sidebar, kept for JS compatibility) */
    .scroll-tabs {
        display: none !important;
    }
    
    .global-filter-btn {
        padding: 6px 14px;
        background: var(--c-surface2);
        border: 0.5px solid var(--c-separator);
        border-radius: var(--r-pill);
        color: var(--t-secondary);
        font-size: 12.5px;
        font-weight: 600;
        cursor: pointer;
        transition: all var(--dur-fast);
        white-space: nowrap;
    }
    .global-filter-btn:hover {
        background: var(--c-fill);
        color: var(--t-primary);
    }
    .global-filter-btn.active {
        background: var(--c-blue-light);
        color: var(--c-blue);
        border-color: var(--c-blue-mid);
        box-shadow: var(--shadow-xs);
    }

    /* Premium Workflow Sidebar Styling */
    .workflow-sidebar {
        width: 260px;
        background: var(--c-surface);
        border-right: 0.5px solid var(--c-separator);
        display: flex;
        flex-direction: column;
        overflow-y: auto;
        padding: 16px 8px;
        flex-shrink: 0;
    }
    
    .workflow-sidebar-steps {
        position: relative;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
    .workflow-sidebar-steps::before {
        content: '';
        position: absolute;
        left: 25px; /* Align with center of 26px step-dot */
        top: 20px;
        bottom: 20px;
        width: 1.5px;
        background: var(--c-separator);
        z-index: 0;
    }
    .sidebar-step-item {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 8px 12px;
        border-radius: var(--r-md);
        cursor: pointer;
        user-select: none;
        transition: background var(--dur-fast), transform var(--dur-fast);
        border: 0.5px solid transparent;
        background: transparent;
    }
    .sidebar-step-item:hover {
        background: var(--c-fill);
    }
    .sidebar-step-item.active {
        background: var(--c-blue-light);
        border-color: var(--c-blue-mid);
    }
    .sidebar-step-item.locked {
        opacity: 1;
    }
    
    .step-dot {
        width: 26px;
        height: 26px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 11px;
        font-weight: 700;
        flex-shrink: 0;
        background: var(--c-surface);
        color: var(--t-secondary);
        transition: background var(--dur-fast), color var(--dur-fast), box-shadow var(--dur-fast);
        border: 1px solid var(--c-separator);
        z-index: 2;
    }
    .sidebar-step-item.active .step-dot {
        background: var(--c-blue);
        color: #fff;
        border-color: var(--c-blue);
        box-shadow: 0 0 0 3.5px rgba(0, 122, 255, 0.15);
    }
    .sidebar-step-item.completed .step-dot {
        background: var(--c-green-light);
        color: var(--c-green);
        border-color: var(--c-green);
    }
    .sidebar-step-item.locked .step-dot {
        background: var(--c-surface);
        color: var(--t-secondary);
        border-color: var(--c-separator);
    }

    .step-info {
        display: flex;
        flex-direction: column;
        gap: 2px;
        flex: 1;
        min-width: 0;
    }
    .step-title {
        font-size: 13.5px;
        font-weight: 600;
        color: var(--t-primary);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .sidebar-step-item.active .step-title {
        color: var(--c-blue);
        font-weight: 700;
    }
    .sidebar-step-item.completed .step-title {
        color: var(--t-primary);
    }
    .step-desc {
        font-size: 11px;
        color: var(--t-label);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .sidebar-step-item.active .step-desc {
        color: rgba(0, 122, 255, 0.85);
    }

    /* Modern Dashboard Navigation flow toggles */
    .app-workspace.workspace-active .pane-left {
        display: none !important;
    }
    .app-workspace.workspace-active .pane-middle {
        display: flex !important;
        flex: 1;
        width: 100%;
    }
    .app-workspace:not(.workspace-active) .pane-left {
        width: 100% !important;
        background: transparent !important;
        border-right: none !important;
        flex: 1;
        display: flex !important;
    }
    .app-workspace:not(.workspace-active) #routeListItemsContainer {
        display: grid !important;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)) !important;
        gap: 20px !important;
        padding: 20px !important;
        align-content: start !important;
    }
    .global-filter-scroll {
        scrollbar-width: none; /* Firefox */
    }
    .global-filter-scroll::-webkit-scrollbar {
        display: none; /* Safari and Chrome */
    }
    
    .app-workspace:not(.workspace-active) .route-item {
        background: var(--c-surface) !important;
        border: 0.5px solid var(--c-separator) !important;
        border-radius: var(--r-md) !important;
        box-shadow: var(--shadow-sm) !important;
        display: flex !important;
        flex-direction: column !important;
        transition: all var(--dur-fast) var(--ease-ios) !important;
        padding: 18px !important;
        margin: 0 !important;
    }
    .app-workspace:not(.workspace-active) .route-item:hover {
        transform: translateY(-2px) !important;
        box-shadow: var(--shadow-md) !important;
        border-color: var(--c-blue) !important;
        background: var(--c-surface) !important;
    }
    .app-workspace:not(.workspace-active) .route-item.active {
        background: var(--c-surface) !important;
        color: inherit !important;
        border-color: var(--c-blue) !important;
        box-shadow: 0 0 0 1.5px var(--c-blue), var(--shadow-md) !important;
    }
    .app-workspace:not(.workspace-active) .pane-middle {
        display: none !important;
    }
    body.workspace-showing .header-actions,
    body.workspace-showing .global-status-filter-bar {
        display: none !important;
    }

    @media (prefers-color-scheme: dark) {
        .app-workspace:not(.workspace-active) .route-item {
            background: var(--c-surface) !important;
            border-color: var(--c-separator) !important;
        }
        .app-workspace:not(.workspace-active) .route-item:hover {
            background: var(--c-surface2) !important;
        }
    }

    /* Premium Button Stylings inside Workspace */
    .workspace-tab-panel button, 
    .mid-header button, 
    .header-actions button,
    #boundRouteSummaryContainer button,
    #completedArchiveBanner button {
        padding: 8px 16px !important;
        border-radius: var(--r-md) !important;
        font-size: 13px !important;
        font-weight: 600 !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 6px !important;
        border: 0.5px solid transparent !important;
        cursor: pointer !important;
        transition: transform var(--dur-fast) var(--ease-spring), filter var(--dur-fast), background var(--dur-fast) !important;
        box-shadow: var(--shadow-xs) !important;
    }
    
    .workspace-tab-panel button:active, 
    .mid-header button:active, 
    .header-actions button:active,
    #boundRouteSummaryContainer button:active,
    #completedArchiveBanner button:active {
        transform: scale(0.97) !important;
    }
    
    /* Auto-assign theme colors based on original background colors */
    button[style*="#2e7d32"], button[style*="background:#2e7d32"], button[style*="background: #2e7d32"] {
        background: var(--c-green) !important;
        color: #fff !important;
    }
    button[style*="#3f51b5"], button[style*="background:#3f51b5"], button[style*="background: #3f51b5"],
    button[style*="#0066cc"], button[style*="background:#0066cc"], button[style*="background: #0066cc"] {
        background: var(--c-blue) !important;
        color: #fff !important;
    }
    button[style*="#ef4444"], button[style*="background:#ef4444"], button[style*="background: #ef4444"],
    button[style*="#c62828"], button[style*="background:#c62828"], button[style*="background: #c62828"] {
        background: var(--c-red) !important;
        color: #fff !important;
    }
    button[style*="#e2e8f0"], button[style*="background:#e2e8f0"], button[style*="background: #e2e8f0"] {
        background: var(--c-surface) !important;
        color: var(--t-primary) !important;
        border-color: var(--c-separator) !important;
    }
    button[style*="#e2e8f0"]:hover, button[style*="background:#e2e8f0"]:hover, button[style*="background: #e2e8f0"]:hover {
        background: var(--c-surface2) !important;
    }

    /* Input elements style inside workspace tabs */
    .workspace-tab-panel input, 
    .workspace-tab-panel select, 
    .workspace-tab-panel textarea {
        padding: 8px 12px;
        border: 0.5px solid var(--c-separator);
        border-radius: var(--r-xs);
        font-size: 13.5px;
        background: var(--c-surface);
        color: var(--t-primary);
        outline: none;
        transition: border-color var(--dur-fast);
    }
    .workspace-tab-panel input:focus, 
    .workspace-tab-panel select:focus, 
    .workspace-tab-panel textarea:focus {
        border-color: var(--c-blue);
    }

    /* Header styling customization */
    .header-actions h2 i {
        color: var(--c-blue) !important;
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
        display: flex;
        align-items: center;
        gap: 9px;
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
    body.workspace-showing .cmd-bar {
        display: none !important;
    }

    /* macOS Style UI Elements */
    .macos-window {
        background: #ffffff;
        border: 1px solid #d1d1d6;
        border-radius: 12px;
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.08), 0 1px 3px rgba(0, 0, 0, 0.02);
        max-width: 680px;
        margin: 20px auto;
        overflow: hidden;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    }
    .macos-titlebar {
        background: #f5f5f7;
        border-bottom: 1px solid #d1d1d6;
        padding: 10px 16px;
        display: flex;
        align-items: center;
        position: relative;
    }
    .macos-dots {
        display: flex;
        gap: 8px;
    }
    .macos-dot {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        display: inline-block;
    }
    .macos-dot.close { background: #ff5f56; border: 0.5px solid #e0443e; }
    .macos-dot.minimize { background: #ffbd2e; border: 0.5px solid #dfa123; }
    .macos-dot.zoom { background: #27c93f; border: 0.5px solid #1aab29; }
    
    .macos-title {
        position: absolute;
        left: 50%;
        transform: translateX(-50%);
        font-size: 13px;
        font-weight: 600;
        color: #1d1d1f;
    }
    .macos-content {
        padding: 24px;
        display: flex;
        flex-direction: column;
        gap: 20px;
    }
    .macos-label {
        font-size: 12px;
        font-weight: 600;
        color: #86868b;
        margin-bottom: 6px;
        display: block;
    }
    .macos-input, .macos-select {
        width: 100%;
        background: #ffffff;
        border: 1px solid #d1d1d6;
        border-radius: 6px;
        padding: 6px 10px;
        font-size: 13px;
        color: #1d1d1f;
        outline: none;
        transition: box-shadow 0.15s, border-color 0.15s;
    }
    .macos-input:focus, .macos-select:focus {
        border-color: #007aff;
        box-shadow: 0 0 0 3px rgba(0, 122, 255, 0.25);
    }
    .macos-btn-primary {
        background: #007aff;
        color: #ffffff;
        border: none;
        border-radius: 6px;
        padding: 8px 16px;
        font-size: 13px;
        font-weight: 500;
        cursor: pointer;
        transition: background 0.15s;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .macos-btn-primary:hover {
        background: #0066d6;
    }
    .macos-btn-primary:active {
        background: #0051a8;
    }
    .macos-checkbox-list {
        border: 1px solid #d1d1d6;
        border-radius: 6px;
        max-height: 180px;
        overflow-y: auto;
        background: #ffffff;
        padding: 4px;
    }
    .macos-checkbox-item {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        padding: 8px 10px;
        border-radius: 5px;
        cursor: pointer;
        transition: background 0.12s, color 0.12s;
    }
    .macos-checkbox-item:hover {
        background: rgba(0, 122, 255, 0.08);
    }
    .macos-banner {
        background: rgba(0, 122, 255, 0.05);
        border: 1px solid rgba(0, 122, 255, 0.2);
        padding: 12px 16px;
        border-radius: 8px;
        font-size: 13px;
        color: #007aff;
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 500;
        margin-bottom: 20px;
    }
</style>

<?php if (isset($_SESSION['flash_success'])): ?>
    <div style="background: #e2f0d9; border: 1px solid #2e7d32; color: #2e7d32; padding: 12px 20px; border-radius: 8px; margin-bottom: 20px; font-weight: bold; font-size: 13px;">
        <?= htmlspecialchars($_SESSION['flash_success']) ?>
    </div>
    <?php unset($_SESSION['flash_success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['flash_error'])): ?>
    <div style="background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 12px 20px; border-radius: 8px; margin-bottom: 20px; font-weight: bold; font-size: 13px;">
        <?= htmlspecialchars($_SESSION['flash_error']) ?>
    </div>
    <?php unset($_SESSION['flash_error']); ?>
<?php endif; ?>

<div class="header-actions" style="margin-bottom: 15px; display: flex; justify-content: <?= $isHistory ? 'flex-start' : 'flex-end' ?>; align-items: center; flex-wrap: wrap; gap: 10px;">
    <?php if ($isHistory): ?>
        <div>
            <a href="<?= APP_URL ?>/RepTracking/index" style="padding: 10px 18px; border: none; background: #64748b; color: #fff; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 13px; display: flex; align-items: center; gap: 8px; box-shadow: 0 4px 6px rgba(100, 116, 139, 0.2); transition: all 0.2s ease; text-decoration: none;">
                <i class="ph-bold ph-arrow-left"></i> Back to Control Panel
            </a>
        </div>
    <?php else: ?>
        <div style="display: flex; gap: 10px;">
            <a href="<?= APP_URL ?>/RepTracking/history" style="padding: 10px 18px; border: none; background: #64748b; color: #fff; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 13px; display: flex; align-items: center; gap: 8px; box-shadow: 0 4px 6px rgba(100, 116, 139, 0.2); transition: all 0.2s ease; text-decoration: none;">
                <i class="ph-bold ph-clock-counter-clockwise"></i> Route History
            </a>
            <button onclick="openCreateRouteModal()" style="padding: 10px 18px; border: none; background: #2e7d32; color: #fff; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 13px; display: flex; align-items: center; gap: 8px; box-shadow: 0 4px 6px rgba(46, 125, 50, 0.2); transition: all 0.2s ease;">
                <i class="ph-bold ph-plus-circle"></i> Create Route Manually
            </button>
            <button id="btnOpenRouteBinding" onclick="openRouteBindingModal()" style="padding: 10px 18px; border: none; background: #3f51b5; color: #fff; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 13px; display: flex; align-items: center; gap: 8px; box-shadow: 0 4px 6px rgba(63, 81, 181, 0.2); transition: all 0.2s ease;">
                <i class="ph-bold ph-link"></i> Route Binding Panel
            </button>
        </div>
    <?php endif; ?>
</div>

<!-- TOP GLOBAL MULTI-FACETED FILTERS BAR -->
<div class="global-status-filter-bar" style="display: flex; gap: 12px; background: var(--c-surface); border: 0.5px solid var(--c-separator); border-radius: var(--r-md); padding: 12px 20px; margin-bottom: 20px; box-shadow: var(--shadow-sm); align-items: center; justify-content: space-between; flex-wrap: wrap;">
    <div style="display: flex; gap: 16px; align-items: center; flex-wrap: wrap; flex: 1;">
        <!-- Rep-wise filter -->
        <div style="display: flex; flex-direction: column; gap: 4px;">
            <label style="font-size: 10px; font-weight: 700; color: var(--t-label); text-transform: uppercase; letter-spacing: 0.05em;">Representative</label>
            <select id="filterRepSelect" onchange="searchRouteList()" style="padding: 6px 12px; border: 0.5px solid var(--c-separator); border-radius: var(--r-xs); font-size: 13px; background: var(--c-surface2); color: var(--t-primary); min-width: 160px; outline: none;">
                <option value="">All Representatives</option>
                <?php foreach ($data['reps'] as $r): ?>
                    <option value="<?= htmlspecialchars($r->first_name . ' ' . $r->last_name) ?>"><?= htmlspecialchars($r->first_name . ' ' . $r->last_name) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <!-- Route-wise filter -->
        <div style="display: flex; flex-direction: column; gap: 4px;">
            <label style="font-size: 10px; font-weight: 700; color: var(--t-label); text-transform: uppercase; letter-spacing: 0.05em;">Route</label>
            <select id="filterRouteSelect" onchange="searchRouteList()" style="padding: 6px 12px; border: 0.5px solid var(--c-separator); border-radius: var(--r-xs); font-size: 13px; background: var(--c-surface2); color: var(--t-primary); min-width: 160px; outline: none;">
                <option value="">All Routes</option>
                <?php 
                $uniqueRouteNames = [];
                foreach ($data['routes'] as $r) {
                    if (!in_array($r->route_name, $uniqueRouteNames)) {
                        $uniqueRouteNames[] = $r->route_name;
                    }
                }
                sort($uniqueRouteNames);
                foreach ($uniqueRouteNames as $name): ?>
                    <option value="<?= htmlspecialchars($name) ?>"><?= htmlspecialchars($name) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <!-- Date-wise filter -->
        <div style="display: flex; flex-direction: column; gap: 4px;">
            <label style="font-size: 10px; font-weight: 700; color: var(--t-label); text-transform: uppercase; letter-spacing: 0.05em;">Date</label>
            <input type="date" id="filterDateInput" onchange="searchRouteList()" style="padding: 5px 12px; border: 0.5px solid var(--c-separator); border-radius: var(--r-xs); font-size: 13px; background: var(--c-surface2); color: var(--t-primary); min-width: 140px; outline: none;">
        </div>
        <!-- Territory-wise filter -->
        <div style="display: flex; flex-direction: column; gap: 4px;">
            <label style="font-size: 10px; font-weight: 700; color: var(--t-label); text-transform: uppercase; letter-spacing: 0.05em;">Territory</label>
            <select id="filterTerritorySelect" onchange="searchRouteList()" style="padding: 6px 12px; border: 0.5px solid var(--c-separator); border-radius: var(--r-xs); font-size: 13px; background: var(--c-surface2); color: var(--t-primary); min-width: 160px; outline: none;">
                <option value="">All Territories</option>
                <?php foreach ($data['mca_areas'] as $area): ?>
                    <option value="<?= htmlspecialchars($area->name) ?>"><?= htmlspecialchars($area->name) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div style="display: flex; align-items: flex-end; align-self: flex-end;">
        <button type="button" onclick="clearFilters()" style="padding: 7px 14px; border: 0.5px solid var(--c-separator); background: var(--c-surface2); color: var(--t-secondary); border-radius: var(--r-xs); font-size: 13px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 6px; transition: all var(--dur-fast);" onmouseover="this.style.background='var(--c-fill)'; this.style.color='var(--t-primary)';" onmouseout="this.style.background='var(--c-surface2)'; this.style.color='var(--t-secondary)';">
            <i class="ph ph-arrow-counter-clockwise"></i> Clear Filters
        </button>
    </div>
</div>

<div class="app-workspace">
    <!-- Left Pane: Routes Master List -->
    <div class="pane-left">
        <div style="flex: 1; overflow-y: auto;" id="routeListItemsContainer">
            <?php foreach($data['routes'] as $route): ?>
                <?php 
                    $status = $route->status;
                    if ($status === 'Active') {
                        $dataType = 'active';
                    } elseif ($status === 'Pending GL') {
                        $dataType = 'pending_gl';
                    } elseif ($status === 'Adjustments') {
                        $dataType = 'adjustments';
                    } elseif ($status === 'Loading') {
                        $dataType = 'loading';
                    } elseif ($status === 'Variance Adjustment') {
                        $dataType = 'variance';
                    } elseif ($status === 'Finalizing') {
                        $dataType = 'finalizing';
                    } else {
                        $dataType = 'completed';
                    }
                ?>
                <div class="route-item" id="route_<?= $route->id ?>" data-route-type="<?= $dataType ?>" onclick="loadRouteDetails(<?= $route->id ?>, this)" style="cursor: pointer; border: 1px solid var(--mac-border); border-radius: 8px; padding: 15px; margin-bottom: 12px; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.02); display: flex; flex-direction: column; justify-content: space-between;">
                    
                    <!-- Top row: Route Number and status badge -->
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <span style="font-family: monospace; font-weight: bold; background: rgba(0, 102, 204, 0.1); color: #0066cc; padding: 2px 6px; border-radius: 4px; font-size: 11px;">
                            #RT-<?= str_pad($route->id, 5, '0', STR_PAD_LEFT) ?>
                        </span>
                        <span style="font-size: 10px; font-weight: bold; padding: 2px 8px; border-radius: 12px; background: <?= ($route->status === 'Completed' || $route->status === 'Finalized') ? '#e2f0d9' : '#fff3cd' ?>; color: <?= ($route->status === 'Completed' || $route->status === 'Finalized') ? '#2e7d32' : '#d97706' ?>; border: 1px solid <?= ($route->status === 'Completed' || $route->status === 'Finalized') ? '#2e7d32' : '#d97706' ?>;">
                            <?= htmlspecialchars($route->status) ?>
                        </span>
                    </div>

                    <!-- Route Name -->
                    <div class="r-title" style="font-size: 15px; font-weight: 700; color: #1e293b; margin-bottom: 6px; line-height: 1.2;">
                        <?= htmlspecialchars($route->route_name) ?>
                    </div>

                    <!-- Rep Name -->
                    <div class="r-sub" style="font-size: 11px; color: #64748b; margin-bottom: 8px; font-weight: bold; text-transform: uppercase;">
                        Rep: <?= htmlspecialchars($route->first_name . ' ' . $route->last_name) ?>
                    </div>

                    <!-- Meta stats -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; border-top: 1px solid #f1f5f9; border-bottom: 1px solid #f1f5f9; padding: 8px 0; margin-bottom: 8px;">
                        <div>
                            <div style="font-size: 9px; color: #94a3b8; text-transform: uppercase; font-weight: bold;">Route Total</div>
                            <div style="font-size: 12px; font-weight: bold; color: #2e7d32; font-family: monospace;">Rs <?= number_format($route->total_sales, 2) ?></div>
                        </div>
                        <div>
                            <div style="font-size: 9px; color: #94a3b8; text-transform: uppercase; font-weight: bold;">Customers</div>
                            <div style="font-size: 12px; font-weight: bold; color: #1e293b; display: inline-flex; align-items: center; gap: 4px;">
                                <i class="ph ph-users" style="color: #64748b; font-size: 14px;"></i> <?= intval($route->customer_count ?? 0) ?>
                            </div>
                        </div>
                    </div>

                    <!-- Footer line: Last Updated -->
                    <div style="display: flex; justify-content: space-between; align-items: center; font-size: 10px; color: #94a3b8;">
                        <span>Updated: <strong><?= date('M d, Y H:i', strtotime($route->created_at ?? $route->start_time)) ?></strong></span>
                    </div>

                    <?php if (!empty($route->is_bound_group)): ?>
                        <div class="rb-bound-tag" style="background: #e0f2fe; color: #0369a1; display: inline-flex; align-items: center; gap: 4px; margin-top: 8px; font-size: 10px; border-radius: 4px; padding: 4px 8px;">
                            <i class="ph ph-link"></i> Group: <?= htmlspecialchars($route->constituent_routes_info) ?>
                        </div>
                    <?php elseif (!empty($route->binding_name)): ?>
                        <div class="rb-bound-tag" style="display: inline-flex; align-items: center; gap: 4px; margin-top: 8px; font-size: 10px; border-radius: 4px; padding: 4px 8px;">
                            <i class="ph ph-link"></i> Bound: <?= htmlspecialchars($route->binding_name) ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Hidden data payload -->
                <div id="route_data_<?= $route->id ?>" style="display:none;" 
                     data-rep="<?= htmlspecialchars($route->first_name . ' ' . $route->last_name) ?>"
                     data-rname="<?= htmlspecialchars($route->route_name) ?>"
                     data-date="<?= date('Y-m-d', strtotime($route->start_time)) ?>"
                     data-territory="<?= htmlspecialchars($route->route_name) ?>"
                     data-constituent="<?= htmlspecialchars($route->constituent_routes_info ?? '') ?>"
                     data-start="<?= $route->start_meter ?>"
                     data-end="<?= $route->end_meter ?: 'Active' ?>"
                      data-start-time="<?= date('Y-m-d H:i:s', strtotime($route->start_time)) ?>"
                      data-end-time="<?= $route->end_time ? date('Y-m-d H:i:s', strtotime($route->end_time)) : 'Active' ?>"
                     data-sales="<?= number_format($route->total_sales, 2) ?>"
                     data-bills="<?= $route->bill_count ?>"
                     data-status="<?= $route->status ?>"
                     data-unfinalized="<?= $route->unfinalized_count ?>"
                     data-bound="<?= !empty($route->is_bound_group) ? '1' : '0' ?>"
                     data-binding-id="<?= $route->route_binding_id ?: '' ?>"
                     data-delivery-id="<?= $route->delivery_id ?: '' ?>"
                     data-delivery-status="<?= $route->delivery_status ?: '' ?>"
                     data-merged="<?= $route->is_merged_route ? '1' : '0' ?>">
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Middle Pane: Workspace -->
    <div class="pane-middle">
        <!-- Header -->
        <div class="mid-header" id="midHeader" style="display: none !important; visibility: hidden; padding: 12px 20px; display: flex; align-items: center; justify-content: space-between; gap: 20px; background: var(--c-surface); border-bottom: 1px solid var(--c-separator);">
            
            <!-- Left Side: Back button + Route Name + Status Badge -->
            <div style="display: flex; align-items: center; gap: 16px; min-width: 0;">
                <button type="button" onclick="goBackToRoutes()" style="background: var(--c-fill); border: none; color: var(--c-blue); font-size: 13px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 6px; padding: 8px 12px; border-radius: var(--r-sm);">
                    <i class="ph-bold ph-arrow-left"></i> Back
                </button>
                <div style="min-width: 0;">
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 2px;">
                        <span id="mhRouteNumber" style="font-family: var(--f-mono); font-weight: 700; background: var(--c-blue-light); color: var(--c-blue); padding: 2px 6px; border-radius: var(--r-xs); font-size: 11px;">Route #RT-00000</span>
                        <span id="mhRouteStatusBadge" style="font-size: 10px; font-weight: 700; padding: 1.5px 6px; border-radius: var(--r-pill); background: var(--c-orange-light); color: var(--c-orange); border: 0.5px solid var(--c-orange);">Active</span>
                    </div>
                    <h2 style="margin:0; color:var(--t-primary); font-size: 16px; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 320px;" id="mhRouteName">Route Name</h2>
                </div>
            </div>

            <!-- Middle: Metadata details (Representative & Odometer) -->
            <div style="display: flex; align-items: center; gap: 15px; font-size: 12px; color: var(--t-secondary); background: var(--c-surface2); padding: 8px 14px; border-radius: var(--r-md); border: 0.5px solid var(--c-separator);">
                <div>Rep: <strong id="mhRepName" style="color: var(--t-primary);"></strong></div>
                <div style="width: 1px; height: 12px; background: var(--c-separator);"></div>
                <div>ODO: <strong id="mhStart"></strong> - <strong id="mhEnd"></strong></div>
            </div>

            <!-- Right Side: Stats + Global Action Buttons -->
            <div style="display: flex; align-items: center; gap: 12px; flex-shrink: 0;">
                <div class="stat-box" style="padding: 6px 12px; border: 0.5px solid var(--c-separator); border-radius: var(--r-sm); text-align: left; display: flex; flex-direction: column;">
                    <span style="font-size: 9px; color: var(--t-label); text-transform: uppercase; font-weight: 700; letter-spacing: 0.04em;">Sales Value</span>
                    <strong style="color: var(--c-green); font-size: 14px; font-weight: 700; font-family: var(--f-mono);">Rs <span id="mhSales"></span></strong>
                </div>
                <div class="stat-box" style="padding: 6px 12px; border: 0.5px solid var(--c-separator); border-radius: var(--r-sm); text-align: left; display: flex; flex-direction: column;">
                    <span style="font-size: 9px; color: var(--t-label); text-transform: uppercase; font-weight: 700; letter-spacing: 0.04em;">Bills</span>
                    <strong id="mhBills" style="font-size: 14px; font-weight: 700; font-family: var(--f-mono);"></strong>
                </div>

                <div style="display: flex; gap: 6px; align-items: center; border-left: 1px solid var(--c-separator); padding-left: 12px;">
                    <button type="button" onclick="openRouteSwitcherModal()" style="padding: 8px 12px; border: 0.5px solid var(--c-blue-mid); background: var(--c-surface); color: var(--c-blue); border-radius: var(--r-sm); font-weight: 600; cursor: pointer; font-size: 12px; display: flex; align-items: center; gap: 6px; box-shadow: var(--shadow-xs);">
                        <i class="ph ph-swap"></i> Switch
                    </button>
                    <button type="button" id="btnViewMap" onclick="openMapModal()" style="padding: 8px 12px; border: none; background: var(--c-orange); color: #fff; border-radius: var(--r-sm); font-weight: 600; cursor: pointer; font-size: 12px; display: none; align-items: center; gap: 4px; box-shadow: var(--shadow-xs);"><i class="ph ph-map-pin"></i> Map</button>
                </div>
            </div>
        </div>

        <!-- Route Workspace Tabs -->
        <div class="scroll-tabs" id="routeWorkspaceTabs" style="display: none; border-bottom: 2px solid #cbd5e1; margin-bottom: 0;">
            <button class="scroll-tab-btn active" onclick="switchRouteTab(1, this)"><i class="ph ph-clipboard-text"></i> 1. Route Details</button>
            <button class="scroll-tab-btn" onclick="switchRouteTab(2, this)"><i class="ph ph-coins"></i> 2. Credit Collections</button>
            <button class="scroll-tab-btn" onclick="switchRouteTab(3, this)"><i class="ph ph-gear"></i> 3. Bill Adjustments</button>
            <button class="scroll-tab-btn" onclick="switchRouteTab(4, this)"><i class="ph ph-truck"></i> 4. Loading</button>
            <button class="scroll-tab-btn" onclick="switchRouteTab(5, this)"><i class="ph ph-scales"></i> 5. Variance Audit</button>
            <button class="scroll-tab-btn" onclick="switchRouteTab(6, this)"><i class="ph ph-map-trifold"></i> 6. Delivery Arrangement</button>
            <button class="scroll-tab-btn" onclick="switchRouteTab(7, this)"><i class="ph ph-steering-wheel"></i> 7. Delivery</button>
            <button class="scroll-tab-btn" onclick="switchRouteTab(8, this)"><i class="ph ph-currency-dollar"></i> 8. Reconciliation</button>
            <button class="scroll-tab-btn" onclick="switchRouteTab(9, this)"><i class="ph ph-package"></i> 9. Return Stock Verification</button>
            <button class="scroll-tab-btn" onclick="switchRouteTab(10, this)"><i class="ph ph-briefcase"></i> 10. Accounting</button>
        </div>

        <!-- Workspace Layout Container (Sidebar + Content Body) -->
        <div id="workspaceLayoutWrapper" style="display: none; flex: 1; flex-direction: row; min-height: 0; width: 100%;">
            <!-- Left Side: Workflow Sidebar -->
            <div class="workflow-sidebar" id="workflowSidebar">
                <div style="padding: 0 8px 16px 8px; border-bottom: 1.5px solid var(--c-separator); margin-bottom: 16px;">
                    <button type="button" onclick="goBackToRoutes()" style="width: 100%; background: var(--c-fill); border: 0.5px solid var(--c-separator); color: var(--c-blue); font-size: 13px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 10px; border-radius: var(--r-md); transition: 0.2s;">
                        <i class="ph ph-arrow-left" style="font-weight: bold;"></i> Back to Route List
                    </button>
                </div>
                <div class="workflow-sidebar-steps">
                    <div class="sidebar-step-item active" id="sb-step-1" onclick="switchRouteTab(1)">
                        <div class="step-dot">1</div>
                        <div class="step-info">
                            <span class="step-title">Route Details</span>
                            <span class="step-desc">Representative & Odo</span>
                        </div>
                    </div>
                    <div class="sidebar-step-item" id="sb-step-2" onclick="switchRouteTab(2)">
                        <div class="step-dot">2</div>
                        <div class="step-info">
                            <span class="step-title">Credit Collections</span>
                            <span class="step-desc">Audit credit payments</span>
                        </div>
                    </div>
                    <div class="sidebar-step-item" id="sb-step-3" onclick="switchRouteTab(3)">
                        <div class="step-dot">3</div>
                        <div class="step-info">
                            <span class="step-title">Bill Adjustments</span>
                            <span class="step-desc">Attach/detach SOs</span>
                        </div>
                    </div>
                    <div class="sidebar-step-item" id="sb-step-4" onclick="switchRouteTab(4)">
                        <div class="step-dot">4</div>
                        <div class="step-info">
                            <span class="step-title">Loading Checklist</span>
                            <span class="step-desc">Verify loaded stock</span>
                        </div>
                    </div>
                    <div class="sidebar-step-item" id="sb-step-5" onclick="switchRouteTab(5)">
                        <div class="step-dot">5</div>
                        <div class="step-info">
                            <span class="step-title">Variance Audit</span>
                            <span class="step-desc">Confirm product variances</span>
                        </div>
                    </div>
                    <div class="sidebar-step-item" id="sb-step-6" onclick="switchRouteTab(6)">
                        <div class="step-dot">6</div>
                        <div class="step-info">
                            <span class="step-title">Delivery Arrange</span>
                            <span class="step-desc">Assign driver & vehicle</span>
                        </div>
                    </div>
                    <div class="sidebar-step-item" id="sb-step-7" onclick="switchRouteTab(7)">
                        <div class="step-dot">7</div>
                        <div class="step-info">
                            <span class="step-title">Delivery Execution</span>
                            <span class="step-desc">Track live status</span>
                        </div>
                    </div>
                    <div class="sidebar-step-item" id="sb-step-8" onclick="switchRouteTab(8)">
                        <div class="step-dot">8</div>
                        <div class="step-info">
                            <span class="step-title">Reconciliation</span>
                            <span class="step-desc">Discrepancies & cash</span>
                        </div>
                    </div>
                    <div class="sidebar-step-item" id="sb-step-9" onclick="switchRouteTab(9)">
                        <div class="step-dot">9</div>
                        <div class="step-info">
                            <span class="step-title">Return Stock</span>
                            <span class="step-desc">Verify returned items</span>
                        </div>
                    </div>
                    <div class="sidebar-step-item" id="sb-step-10" onclick="switchRouteTab(10)">
                        <div class="step-dot">10</div>
                        <div class="step-info">
                            <span class="step-title">Accounting</span>
                            <span class="step-desc">Double-entry GL posting</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Side: Content Area (previously #workspaceBody) -->
            <div style="flex:1; overflow-y:auto; position:relative; background:var(--c-bg);" id="workspaceBody">
                <!-- Loading Indicator -->
                <div id="midLoader" style="display:none; position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); text-align:center; font-weight:bold; color:var(--c-blue); z-index: 10;">
                    Loading Workspace Information... <i class="fa-solid fa-spinner fa-spin"></i>
                </div>

                <!-- Dynamic Stage Containers -->
                <div id="stageContentWrapper" style="display:none; padding: 16px 20px;">
                

                <!-- COMPLETED ARCHIVE OPTIONS (READ ONLY AT THE TOP IF FINALIZED) -->
                <div id="completedArchiveBanner" style="display:none; background:#f1f5f9; border:1px solid #cbd5e1; border-radius:8px; padding:15px; margin-bottom:20px; display:flex; justify-content:space-between; align-items:center;">
                    <div>
                        <h4 style="margin:0; font-size:14px; font-weight:bold; color:#2e7d32;"><i class="ph ph-flag-checkered"></i> Route Settle Balancing Finalized</h4>
                        <p style="margin:5px 0 0 0; font-size:12px; color:#666;">This route is read-only. All transactions, inventories, and GL postings are successfully finalized.</p>
                    </div>
                    <div style="display:flex; gap:10px;">
                        <button onclick="printBalancingReport()" style="padding:8px 12px; background:#0066cc; color:#fff; border:none; border-radius:4px; font-weight:bold; cursor:pointer; font-size:12px;"><i class="ph ph-printer"></i> Print Balancing Report</button>
                        <button onclick="printLoadingSheetSpreadsheet()" style="padding:8px 12px; background:#e2e8f0; color:#333; border:none; border-radius:4px; font-weight:bold; cursor:pointer; font-size:12px;"><i class="ph ph-chart-bar"></i> Print Spreadsheet</button>
                        <button onclick="printLoadingSheet('summary')" style="padding:8px 12px; background:#e2e8f0; color:#333; border:none; border-radius:4px; font-weight:bold; cursor:pointer; font-size:12px;"><i class="ph ph-truck"></i> Print Loading Summary</button>
                        <button onclick="exportCSV()" style="padding:8px 12px; background:#e2e8f0; color:#333; border:none; border-radius:4px; font-weight:bold; cursor:pointer; font-size:12px;"><i class="ph ph-download"></i> Export CSV</button>
                    </div>
                </div>

                <!-- TAB 1: DETAILS -->
                <div class="workspace-tab-panel" id="tabpanel-1" style="display:none;">
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; margin-bottom:20px;">
                        <div style="border:1px solid #cbd5e1; border-radius:8px; padding:20px; background:#fff;">
                            <h4 style="margin:0 0 15px 0; color:var(--primary); font-size:15px; font-weight:bold;"><i class="ph ph-clipboard-text"></i> Route & Representative Info</h4>
                            <table style="width:100%; border-collapse:collapse; font-size:13px;">
                                <tr style="border-bottom:1px solid #f1f5f9;"><td style="padding:10px 0; color:#64748b; font-weight:bold;">Route Code</td><td style="padding:10px 0; font-weight:bold; font-family:var(--f-mono);" id="tab1RouteNumber">-</td></tr>
                                <tr style="border-bottom:1px solid #f1f5f9;"><td style="padding:10px 0; color:#64748b; font-weight:bold;">Route Name</td><td style="padding:10px 0; font-weight:bold;" id="tab1RouteName">-</td></tr>
                                <tr style="border-bottom:1px solid #f1f5f9;"><td style="padding:10px 0; color:#64748b; font-weight:bold;">Representative</td><td style="padding:10px 0; font-weight:bold;" id="tab1RepName">-</td></tr>
                                <tr style="border-bottom:1px solid #f1f5f9;"><td style="padding:10px 0; color:#64748b; font-weight:bold;">Current Status</td><td style="padding:10px 0;"><span id="tab1Status" style="font-weight:bold; background:#e2e8f0; padding:2px 8px; border-radius:4px; font-size:11px;">-</span></td></tr>
                                <tr style="border-bottom:1px solid #f1f5f9;"><td style="padding:10px 0; color:#64748b; font-weight:bold;">Start Time</td><td style="padding:10px 0;" id="tab1StartTime">-</td></tr>
                                <tr style="border-bottom:1px solid #f1f5f9;"><td style="padding:10px 0; color:#64748b; font-weight:bold;">End Time</td><td style="padding:10px 0;" id="tab1EndTime">-</td></tr>
                                <tr style="border-bottom:1px solid #f1f5f9;"><td style="padding:10px 0; color:#64748b; font-weight:bold;">Total Sales Value</td><td style="padding:10px 0; font-weight:bold; color:var(--c-green);" id="tab1SalesValue">-</td></tr>
                                <tr style="border-bottom:1px solid #f1f5f9;"><td style="padding:10px 0; color:#64748b; font-weight:bold;">Total Bills Count</td><td style="padding:10px 0; font-weight:bold;" id="tab1BillsCount">-</td></tr>
                            </table>
                            <div style="display: flex; gap: 8px; margin-top: 15px;">
                                <button type="button" onclick="openRouteSwitcherModal()" style="flex:1; justify-content:center; display:inline-flex; align-items:center; gap:6px; padding:8px 12px; border:0.5px solid var(--c-blue-mid); background:var(--c-surface); color:var(--c-blue); border-radius:var(--r-sm); font-weight:600; cursor:pointer; font-size:12px; box-shadow:var(--shadow-xs);">
                                    <i class="ph ph-swap"></i> Switch Route
                                </button>
                                <button type="button" id="btnViewMapDetails" onclick="openMapModal()" style="flex:1; justify-content:center; display:inline-flex; align-items:center; gap:6px; padding:8px 12px; border:none; background:var(--c-orange); color:#fff; border-radius:var(--r-sm); font-weight:600; cursor:pointer; font-size:12px; box-shadow:var(--shadow-xs);">
                                    <i class="ph ph-map-pin"></i> View Map
                                </button>
                            </div>
                        </div>
                        <div style="border:1px solid #cbd5e1; border-radius:8px; padding:20px; background:#fff;">
                            <h4 style="margin:0 0 15px 0; color:var(--primary); font-size:15px; font-weight:bold;"><i class="ph ph-gauge"></i> Odometer Readings</h4>
                            <table style="width:100%; border-collapse:collapse; font-size:13px; margin-bottom:15px;">
                                <tr style="border-bottom:1px solid #f1f5f9;"><td style="padding:10px 0; color:#64748b; font-weight:bold;">Start ODO</td><td style="padding:10px 0; font-weight:bold;" id="tab1StartMeter">-</td></tr>
                                <tr style="border-bottom:1px solid #f1f5f9;"><td style="padding:10px 0; color:#64748b; font-weight:bold;">End ODO</td><td style="padding:10px 0; font-weight:bold;" id="tab1EndMeter">-</td></tr>
                                <tr style="border-bottom:1px solid #f1f5f9;"><td style="padding:10px 0; color:#64748b; font-weight:bold;">Total Distance</td><td style="padding:10px 0; font-weight:bold; color:#0f172a;" id="tab1Distance">-</td></tr>
                            </table>
                        </div>
                    </div>
                    <div style="border:1px solid #cbd5e1; border-radius:8px; padding:20px; background:#fff; margin-bottom:20px;">
                        <h4 style="margin:0 0 10px 0; color:var(--primary); font-size:15px; font-weight:bold;"><i class="ph ph-note-pencil"></i> Route General Notes</h4>
                        <textarea id="tab1RouteNotes" style="width:100%; height:100px; padding:10px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px; resize:vertical;" placeholder="Write any remarks or observations regarding this route..."></textarea>
                        <div style="text-align:right; margin-top:10px;">
                            <button id="btnSaveRouteNotes" onclick="saveRouteNotes()" style="padding:8px 16px; background:#3f51b5; color:#fff; border:none; border-radius:6px; font-weight:bold; font-size:12px; cursor:pointer; display:inline-flex; align-items:center; gap:4px;"><i class="ph ph-floppy-disk"></i> Save Route Notes</button>
                        </div>
                    </div>
                </div>

                <!-- TAB 2: CREDIT COLLECTIONS -->
                <div class="workspace-tab-panel" id="tabpanel-2" style="display:none;">
                    <!-- Collections Verification Table Card -->
                    <div style="border:0.5px solid var(--c-separator); border-radius:var(--r-lg); padding:20px; background:var(--c-surface); box-shadow:var(--shadow-sm); display:flex; flex-direction:column; justify-content:space-between; margin-bottom:20px;">
                        <div>
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
                                <h5 style="margin:0; font-size:14px; font-weight:700; color:var(--t-primary);"><i class="ph ph-coins"></i> Credit Collections & Verification</h5>
                                <button id="btnSaveCollectionsVerification2" onclick="saveCollectionsVerificationStage2()" style="padding:8px 16px; background:var(--c-green); color:#fff; border:none; border-radius:var(--r-sm); font-size:12px; font-weight:bold; cursor:pointer; transition:0.2s;"><i class="ph ph-floppy-disk"></i> Save Verification</button>
                            </div>
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-bottom:15px;">
                                <div style="background:var(--c-bg); border:0.5px solid var(--c-separator); padding:12px; border-radius:var(--r-md); font-size:13px; text-align:center; font-weight:500;">
                                    Cash: <strong id="glTotalCash" style="font-family:var(--f-mono); color:var(--c-green); font-size:16px; margin-left:6px;">Rs 0.00</strong>
                                </div>
                                <div style="background:var(--c-bg); border:0.5px solid var(--c-separator); padding:12px; border-radius:var(--r-md); font-size:13px; text-align:center; font-weight:500;">
                                    Cheque: <strong id="glTotalCheque" style="font-family:var(--f-mono); color:var(--c-blue); font-size:16px; margin-left:6px;">Rs 0.00</strong>
                                </div>
                            </div>
                            <div style="border: 0.5px solid var(--c-separator); border-radius: var(--r-md); background: var(--c-surface); overflow: hidden;">
                                <table class="data-table" style="margin-top:0;">
                                    <thead>
                                        <tr style="background:var(--c-surface2);">
                                            <th style="text-align:left; width:20%;">Customer / Pay</th>
                                            <th style="text-align:right; width:12%;">Collected</th>
                                            <th style="text-align:center; width:8%;">Approve</th>
                                            <th style="text-align:left; width:20%;">Debit Account</th>
                                            <th style="text-align:left; width:20%;">Credit Account</th>
                                            <th style="text-align:right; width:10%;">Adjusted</th>
                                            <th style="text-align:left; width:10%;">Notes</th>
                                        </tr>
                                    </thead>
                                    <tbody id="glCollectionsTableBody"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 3: ADJUSTMENTS -->
                <div class="workspace-tab-panel" id="tabpanel-3" style="display:none; margin: -16px -20px; height: calc(100% + 32px); background: var(--c-surface);">
                    <div style="border-bottom: 1.5px solid var(--c-separator); padding: 16px 20px; background: var(--c-surface); display:flex; justify-content:space-between; align-items:center;">
                        <h4 style="margin:0; font-size:14px; font-weight:bold; display:flex; align-items:center; gap:6px; color: var(--t-primary);"><i class="ph ph-wrench"></i> Sales Order Operations</h4>
                        <div style="display:flex; gap:10px;">
                            <button id="btnTab3CreateSO" onclick="redirectToAddInvoice()" style="padding:8px 16px; background:#0066cc; border:none; color:#fff; border-radius:var(--r-sm); font-weight:bold; font-size:12px; cursor:pointer; display:inline-flex; align-items:center; gap:4px;"><i class="ph ph-plus-circle"></i> Create Sales Order</button>
                            <button id="btnTab3AttachSO" onclick="openAttachInvoiceModal()" style="padding:8px 16px; background:#5c6bc0; border:none; color:#fff; border-radius:var(--r-sm); font-weight:bold; font-size:12px; cursor:pointer; display:inline-flex; align-items:center; gap:4px;"><i class="ph ph-link"></i> Attach Sales Order</button>
                            <button id="btnTab3PrintInvoices" onclick="printRouteInvoices()" style="padding:8px 16px; background:#1e293b; border:none; color:#fff; border-radius:var(--r-sm); font-weight:bold; font-size:12px; cursor:pointer; display:inline-flex; align-items:center; gap:4px;"><i class="ph ph-printer"></i> Print All Invoices</button>
                        </div>
                    </div>
                    <div style="padding: 16px 20px; background: var(--c-surface);">
                        <table class="data-table" style="margin-top:0;">
                            <thead>
                                <tr>
                                    <th>Invoice Number</th>
                                    <th>Time</th>
                                    <th>Customer Name</th>
                                    <th style="text-align:right;">Grand Total (Rs)</th>
                                    <th style="text-align:center; width:100px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="adjustmentsInvoicesTbody"></tbody>
                        </table>
                    </div>
                </div>

                <!-- TAB 4: LOADING -->
                <div class="workspace-tab-panel" id="tabpanel-4" style="display:none;">
                    <div id="loadingBox" style="margin-bottom:20px;"></div>
                </div>

                <!-- TAB 5: VARIANCE -->
                <div class="workspace-tab-panel" id="tabpanel-5" style="display:none;">
                    <div id="varianceAuditBox" style="margin-bottom:20px;"></div>
                </div>

                <!-- TAB 6: DISPATCH -->
                <div class="workspace-tab-panel" id="tabpanel-6" style="display:none;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <div>
                            <h3 style="margin:0; font-size:18px; font-weight:700; color:var(--t-primary);">Logistics Binding & Dispatch</h3>
                            <p style="margin:4px 0 0 0; font-size:13px; color:var(--t-secondary);">Assign driver, vehicle, helper and select outstanding credit bills to dispatch with this delivery manifest.</p>
                        </div>
                    </div>
                    
                    <!-- Global Status Banner -->
                    <div id="adjDeliveryStatusBanner" class="macos-banner" style="display:none;">
                        <i class="ph-bold ph-paperclip" style="font-size: 16px;"></i>
                        <span>Delivery Manifest <strong id="adjDeliveryStatusId">#--</strong> successfully generated. Ready for warehouse.</span>
                    </div>

                    <!-- Form View (Always Visible macOS Split Layout) -->
                    <div id="adjDeliveryFormView">
                        <form id="adjDeliveryArrangeForm" style="display: flex; flex-wrap: wrap; gap: 20px; max-width: 1150px; margin: 0 auto; align-items: stretch;">
                            
                            <!-- Left Card: Delivery Details -->
                            <div class="macos-window" style="flex: 1 1 380px; margin: 0; display: flex; flex-direction: column;">
                                <div class="macos-titlebar">
                                    <div class="macos-dots">
                                        <span class="macos-dot close"></span>
                                        <span class="macos-dot minimize"></span>
                                        <span class="macos-dot zoom"></span>
                                    </div>
                                    <span class="macos-title">Delivery Details</span>
                                </div>
                                <div class="macos-content" style="flex: 1; display: flex; flex-direction: column; justify-content: space-between;">
                                    <div style="display: flex; flex-direction: column; gap: 15px;">
                                        <div>
                                            <label class="macos-label">Delivery Date</label>
                                            <input type="date" id="adjDaDate" class="macos-input" value="<?= date('Y-m-d') ?>">
                                        </div>

                                        <div>
                                            <label class="macos-label">Vehicle Number *</label>
                                            <select id="adjDaVehicle" class="macos-select" required>
                                                <option value="">-- Select Vehicle --</option>
                                                <?php foreach($data['vehicles'] as $v): ?>
                                                    <?php if($v->status === 'Active'): ?>
                                                        <option value="<?= htmlspecialchars($v->vehicle_number) ?>"><?= htmlspecialchars($v->vehicle_number) ?></option>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div>
                                            <label class="macos-label">Driver Name *</label>
                                            <select id="adjDaDriver" class="macos-select" required>
                                                <option value="">-- Select Driver --</option>
                                                <?php foreach($data['drivers'] as $d): ?>
                                                    <option value="<?= htmlspecialchars($d->first_name . ' ' . $d->last_name) ?>"><?= htmlspecialchars($d->first_name . ' ' . $d->last_name) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div>
                                            <label class="macos-label">Partner / Helper</label>
                                            <select id="adjDaPartner" class="macos-select">
                                                <option value="">-- None --</option>
                                                <?php foreach($data['employees'] as $e): ?>
                                                    <?php if($e->status === 'Active'): ?>
                                                        <option value="<?= htmlspecialchars($e->first_name . ' ' . $e->last_name) ?>"><?= htmlspecialchars($e->first_name . ' ' . $e->last_name) ?> (<?= htmlspecialchars($e->job_title) ?>)</option>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div style="text-align: right; margin-top: 20px;">
                                        <button type="button" onclick="submitAdjustmentsLogisticsArrange()" class="macos-btn-primary" style="width: 100%; justify-content: center; padding: 10px;">
                                            <i class="ph ph-truck"></i> Save Delivery Arrangement
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Right Card: Credit Bills Dispatch -->
                            <div class="macos-window" style="flex: 1.2 1 480px; margin: 0; display: flex; flex-direction: column;">
                                <div class="macos-titlebar">
                                    <div class="macos-dots">
                                        <span class="macos-dot close"></span>
                                        <span class="macos-dot minimize"></span>
                                        <span class="macos-dot zoom"></span>
                                    </div>
                                    <span class="macos-title">Credit Bills Selection</span>
                                </div>
                                <div class="macos-content" style="flex: 1; display: flex; flex-direction: column;">
                                    <label class="macos-label" style="margin-bottom: 10px;">Select Territory Credit Invoices to dispatch with this vehicle</label>
                                    <div id="adjDaBillsContainer" class="macos-checkbox-list" style="flex: 1; min-height: 280px; max-height: none;">
                                        <!-- Outstanding credit bills list -->
                                    </div>
                                </div>
                            </div>

                        </form>
                    </div>
                </div>

                <!-- TAB 7: DELIVERY (LIVE MONITORING) -->
                <div class="workspace-tab-panel" id="tabpanel-7" style="display:none;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <div>
                            <h3 style="margin:0; font-size:18px; font-weight:700; color:var(--t-primary);">Delivery Live Execution Status</h3>
                            <p style="margin:4px 0 0 0; font-size:13px; color:var(--t-secondary);">Track live progress of customer dispatches and collections on the route.</p>
                        </div>
                    </div>
                    <div style="border:0.5px solid var(--c-separator); border-radius:var(--r-lg); padding:20px; background:var(--c-surface); box-shadow:var(--shadow-sm); margin-bottom:20px;">
                        <h4 style="margin:0 0 15px 0; color:var(--primary); font-size:15px; font-weight:bold;"><i class="ph ph-chart-bar"></i> Delivery Performance Summary</h4>
                        <div style="display:grid; grid-template-columns:repeat(4, 1fr); gap:15px;" id="deliveryTabSummaryCards">
                            <!-- Populated dynamically -->
                        </div>
                    </div>
                    <div style="border:0.5px solid var(--c-separator); border-radius:var(--r-lg); padding:20px; background:var(--c-surface); box-shadow:var(--shadow-sm);">
                        <h4 style="margin:0 0 15px 0; color:var(--primary); font-size:15px; font-weight:bold;"><i class="ph ph-map-pin"></i> Customer Visit & Dispatch Status</h4>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Customer Name</th>
                                    <th>Invoice Number</th>
                                    <th style="text-align:right;">Grand Total (Rs)</th>
                                    <th style="text-align:center;">Delivery Status</th>
                                    <th style="text-align:center;">Payment Status</th>
                                    <th style="text-align:center;">Action</th>
                                </tr>
                            </thead>
                            <tbody id="deliveryTabInvoicesTbody">
                                <!-- Populated dynamically -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- TAB 8: RECONCILIATION -->
                <div class="workspace-tab-panel" id="tabpanel-8" style="display:none;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <div>
                            <h3 style="margin:0; font-size:18px; font-weight:700; color:var(--t-primary);">Route Collections & Variance Reconciliation</h3>
                            <p style="margin:4px 0 0 0; font-size:13px; color:var(--t-secondary);">Count cash, verify cheques and document financial variances. Save draft or submit for final settlement.</p>
                        </div>
                    </div>
                    
                    <div id="tab8ContentContainer">
                        <div style="display:grid; grid-template-columns:1.2fr 0.8fr; gap:20px;">
                            <div>
                                <!-- Cash Reconciliation Card -->
                                <div style="border:0.5px solid var(--c-separator); border-radius:var(--r-lg); padding:20px; background:var(--c-surface); box-shadow:var(--shadow-sm); margin-bottom:20px;">
                                    <h4 style="margin:0 0 15px 0; color:var(--primary); font-size:15px; font-weight:bold;"><i class="ph ph-coins"></i> Cash Collections Counter</h4>
                                    <table style="width:100%; border-collapse:collapse; font-size:13px; margin-bottom:15px;">
                                        <tr style="border-bottom:1px solid #f1f5f9;"><td style="padding:10px 0; color:#64748b; font-weight:bold;">Expected Cash Sales</td><td style="padding:10px 0; font-weight:bold; font-family:monospace; text-align:right;" id="reconExpectedCash">Rs 0.00</td></tr>
                                        <tr style="border-bottom:1px solid #f1f5f9;"><td style="padding:10px 0; color:#64748b; font-weight:bold;">Expected Cash Collections</td><td style="padding:10px 0; font-weight:bold; font-family:monospace; text-align:right;" id="reconExpectedCollections">Rs 0.00</td></tr>
                                        <tr style="border-bottom:1px solid #f1f5f9;"><td style="padding:10px 0; color:#64748b; font-weight:bold;">Total Expected Cash</td><td style="padding:10px 0; font-weight:bold; font-family:monospace; text-align:right; color:#2e7d32;" id="reconTotalExpectedCash">Rs 0.00</td></tr>
                                        <tr style="border-bottom:1px solid #f1f5f9;"><td style="padding:10px 0; color:#64748b; font-weight:bold;">Actual Counted Cash</td><td style="padding:10px 0; text-align:right;"><input type="number" step="0.01" min="0" id="reconActualCash" oninput="calculateCashVariance()" style="padding:6px; border:1px solid #ccc; border-radius:4px; width:150px; text-align:right; font-weight:bold; font-family:monospace;" value="0.00"></td></tr>
                                        <tr><td style="padding:10px 0; color:#64748b; font-weight:bold;">Cash Variance</td><td style="padding:10px 0; font-weight:bold; font-family:monospace; text-align:right;" id="reconCashVariance">Rs 0.00</td></tr>
                                    </table>
                                </div>

                                <!-- Cheque Reconciliation Card -->
                                <div style="border:1px solid #cbd5e1; border-radius:8px; padding:20px; background:#fff;">
                                    <h4 style="margin:0 0 15px 0; color:var(--primary); font-size:15px; font-weight:bold;"><i class="ph ph-bank"></i> Cheques Verification</h4>
                                    <table class="data-table" style="font-size:11px;">
                                        <thead>
                                            <tr>
                                                <th>Customer Name</th>
                                                <th>Cheque Number</th>
                                                <th style="text-align:right;">Amount (Rs)</th>
                                                <th style="text-align:center;">Approve</th>
                                            </tr>
                                        </thead>
                                        <tbody id="reconChequesTbody">
                                            <!-- Dynamically populated -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div>
                                <!-- Notes & Save Draft Card -->
                                <div style="border:0.5px solid var(--c-separator); border-radius:var(--r-lg); padding:20px; background:var(--c-surface); box-shadow:var(--shadow-sm); height:100%; display:flex; flex-direction:column; justify-content:space-between;">
                                    <div>
                                        <h4 style="margin:0 0 15px 0; color:var(--primary); font-size:15px; font-weight:bold;"><i class="ph ph-note"></i> Audit Remarks</h4>
                                        <textarea id="reconAuditNotes" style="width:100%; height:180px; padding:10px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px; resize:none;" placeholder="Write any audit notes regarding cash discrepancy, bank transfer receipts verified, etc..."></textarea>
                                    </div>
                                    <div style="text-align:right; margin-top:20px;">
                                        <button id="btnSaveReconciliationDraft" onclick="saveReconciliationDraft()" style="padding:10px 20px; background:#2e7d32; color:#fff; border:none; border-radius:6px; font-weight:bold; font-size:13px; cursor:pointer; width:100%;"><i class="ph ph-floppy-disk"></i> Save Reconciliation Draft</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="tab8GuardContainer" style="display:none;"></div>
                </div>

                <!-- TAB 9: RETURN STOCK VERIFICATION -->
                <div class="workspace-tab-panel" id="tabpanel-9" style="display:none;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <div>
                            <h3 style="margin:0; font-size:18px; font-weight:700; color:var(--t-primary);">Return Stock Verification</h3>
                            <p style="margin:4px 0 0 0; font-size:13px; color:var(--t-secondary);">Verify returned physical stocks and confirm route inventory updates.</p>
                        </div>
                    </div>
                    <div style="border:0.5px solid var(--c-separator); border-radius:var(--r-lg); padding:20px; background:var(--c-surface); box-shadow:var(--shadow-sm); margin-bottom:20px;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
                            <h4 style="margin:0; color:var(--primary); font-size:15px; font-weight:bold;">Returned Stock Settle Verification</h4>
                            <label style="font-weight:bold; font-size:12px; display:flex; align-items:center; gap:6px; cursor:pointer;">
                                <input type="checkbox" id="settleVerifyStock" onchange="checkSettleVerification()" style="width:16px; height:16px;">
                                I have physically verified all returned inventory and confirm quantities are correct.
                            </label>
                        </div>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Product Name</th>
                                    <th style="text-align:center;">Loaded</th>
                                    <th style="text-align:center;">Delivered</th>
                                    <th style="text-align:center;">Expected Returned</th>
                                    <th style="text-align:right; width:150px;">Actual Counted Returns</th>
                                </tr>
                            </thead>
                            <tbody id="settleStockTableBody">
                                <!-- Dynamically populated -->
                            </tbody>
                        </table>
                        <div style="text-align:right; margin-top:20px;">
                            <button id="btnSaveReturnStockDraft" onclick="saveReturnStockDraft()" style="padding:10px 20px; background:#2e7d32; color:#fff; border:none; border-radius:6px; font-weight:bold; font-size:13px; cursor:pointer;">💾 Save Return Stock Draft</button>
                        </div>
                    </div>
                </div>

                <!-- TAB 10: ACCOUNTING -->
                <div class="workspace-tab-panel" id="tabpanel-10" style="display:none;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <div>
                            <h3 style="margin:0; font-size:18px; font-weight:700; color:var(--t-primary);">General Ledger Postings</h3>
                            <p style="margin:4px 0 0 0; font-size:13px; color:var(--t-secondary);">Map route transactions to general ledger double-entries and finalize settlement.</p>
                        </div>
                    </div>

                    <!-- Dispatch Assignment Section inside Accounting final tab (Hidden to streamline interface) -->
                    <div style="display:none;">
                        <select id="settleDaVehicle">
                            <option value="">-- Select Vehicle --</option>
                            <?php foreach($data['vehicles'] as $v): ?>
                                <?php if($v->status === 'Active'): ?>
                                    <option value="<?= htmlspecialchars($v->vehicle_number) ?>"><?= htmlspecialchars($v->vehicle_number) ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                        <select id="settleDaDriver">
                            <option value="">-- Select Driver --</option>
                            <?php foreach($data['drivers'] as $d): ?>
                                <option value="<?= htmlspecialchars($d->first_name . ' ' . $d->last_name) ?>"><?= htmlspecialchars($d->first_name . ' ' . $d->last_name) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select id="settleDaPartner">
                            <option value="">-- None --</option>
                            <?php foreach($data['employees'] as $e): ?>
                                <?php if($e->status === 'Active'): ?>
                                    <option value="<?= htmlspecialchars($e->first_name . ' ' . $e->last_name) ?>"><?= htmlspecialchars($e->first_name . ' ' . $e->last_name) ?> (<?= htmlspecialchars($e->job_title) ?>)</option>
                                 <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div id="tab10ContentContainer">
                        <!-- General Ledger account double entry mappings card -->
                        <div style="border:0.5px solid var(--c-separator); border-radius:var(--r-lg); padding:20px; background:var(--c-surface); box-shadow:var(--shadow-sm); margin-bottom:20px;">
                            <h4 style="margin:0 0 15px 0; color:var(--primary); font-size:15px; font-weight:bold;"><i class="ph ph-briefcase"></i> Account Mappings</h4>
                            <div style="display: flex; gap: 10px; border-bottom: 1px solid #eee; margin-bottom: 15px;">
                                <button type="button" class="left-tab-btn active" id="settleDeTabCollectionsBtn" onclick="switchSettleDeTab('collections')"><i class="ph ph-coins"></i> Cash/Cheques Posting</button>
                                <button type="button" class="left-tab-btn" id="settleDeTabSalesBtn" onclick="switchSettleDeTab('sales')"><i class="ph ph-file-text"></i> Invoices Sales Posting</button>
                            </div>
                            <div id="settleDeCollectionsContainer"></div>
                            <div id="settleDeSalesContainer" style="display:none;"></div>
                            <div style="text-align:right; margin-top:20px;">
                                <button id="btnSaveAccountingDraft" onclick="saveAccountingDraft()" style="padding:10px 20px; background:#2e7d32; color:#fff; border:none; border-radius:6px; font-weight:bold; font-size:13px; cursor:pointer;"><i class="ph ph-floppy-disk"></i> Save Account Mappings Draft</button>
                            </div>
                        </div>

                        <!-- Settle Actions -->
                        <div style="border:0.5px solid var(--c-separator); border-radius:var(--r-lg); padding:20px; background:var(--c-surface); box-shadow:var(--shadow-sm); display:flex; justify-content:space-between; align-items:center;">
                            <div id="settleStatusText" style="font-size:12px; color:#c62828; font-weight:bold;">
                                Please verify Cash, Cheques, and Return stock counts under Reconciliation & Return Stock tabs to unlock Finalization.
                            </div>
                            <button id="settleSubmitBtn" onclick="submitFinalSettle()" style="padding:12px 24px; background:#2e7d32; color:#fff; border:none; border-radius:6px; font-weight:bold; font-size:14px; opacity:0.5; cursor:not-allowed;" disabled>
                                <i class="ph ph-scales"></i> Settle Balancing & Finalize Route
                            </button>
                        </div>
                    </div>
                    <div id="tab10GuardContainer" style="display:none;"></div>
                </div>

                <!-- COMPLETED / READ ONLY VIEW -->
                <div class="stage-section-panel" id="ssec-Completed" style="display:none;">
                    <div style="background:#f1f5f9; border:1px solid #cbd5e1; border-radius:8px; padding:15px; margin-bottom:20px; display:flex; justify-content:space-between; align-items:center;">
                        <div>
                            <h4 style="margin:0; font-size:14px; font-weight:bold; color:#2e7d32;"><i class="ph ph-flag-checkered"></i> Route Settle Balancing Finalized</h4>
                            <p style="margin:5px 0 0 0; font-size:12px; color:#666;">This route is read-only. All transactions, inventories, and GL postings are successfully finalized.</p>
                        </div>
                        <div style="display:flex; gap:10px;">
                            <button onclick="printBalancingReport()" style="padding:8px 12px; background:#0066cc; color:#fff; border:none; border-radius:4px; font-weight:bold; cursor:pointer; font-size:12px;"><i class="ph ph-printer"></i> Print Balancing Report</button>
                            <button onclick="printLoadingSheetSpreadsheet()" style="padding:8px 12px; background:#e2e8f0; color:#333; border:none; border-radius:4px; font-weight:bold; cursor:pointer; font-size:12px;"><i class="ph ph-chart-bar"></i> Print Spreadsheet</button>
                            <button onclick="printLoadingSheet('summary')" style="padding:8px 12px; background:#e2e8f0; color:#333; border:none; border-radius:4px; font-weight:bold; cursor:pointer; font-size:12px;"><i class="ph ph-truck"></i> Print Loading Summary</button>
                            <button onclick="exportCSV()" style="padding:8px 12px; background:#e2e8f0; color:#333; border:none; border-radius:4px; font-weight:bold; cursor:pointer; font-size:12px;"><i class="ph ph-download"></i> Export CSV</button>
                        </div>
                    </div>
                    
                    <div style="display:flex; gap:10px; border-bottom:1px solid #eee; margin-bottom:15px;">
                        <button class="left-tab-btn active" id="compTabInvoicesBtn" onclick="switchCompletedTab('invoices')"><i class="ph ph-file-text"></i> Invoices</button>
                        <button class="left-tab-btn" id="compTabCollectionsBtn" onclick="switchCompletedTab('collections')"><i class="ph ph-coins"></i> Settled Collections</button>
                        <button class="left-tab-btn" id="compTabVariancesBtn" onclick="switchCompletedTab('variances')"><i class="ph ph-scales"></i> Variances</button>
                    </div>

                    <div id="completedInvoicesTab">
                        <table class="data-table">
                            <thead>
                                <tr><th>Invoice Number</th><th>Time</th><th>Customer Name</th><th style="text-align:right;">Grand Total (Rs)</th><th style="text-align:center;">Status</th></tr>
                            </thead>
                            <tbody class="render-invoices-tbody"></tbody>
                        </table>
                    </div>
                    <div id="completedCollectionsTab" style="display:none;"></div>
                    <div id="completedVariancesTab" style="display:none;"></div>
                </div>

                </div> <!-- closes #stageContentWrapper -->
            </div> <!-- closes #workspaceBody -->
        </div> <!-- closes #workspaceLayoutWrapper -->

        <!-- Empty State (when no route selected) -->
        <div class="empty-state" id="midEmptyState" style="flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; color: var(--t-tertiary);">
            <span style="font-size: 50px; margin-bottom: 15px; opacity: 0.5;"><i class="ph ph-map-pin"></i></span>
            Please select a route from the left to view details.
        </div>
    </div> <!-- closes .pane-middle -->

    <!-- Centered Invoice Popup Modal -->
    <!-- Global Context Menu Backdrop Blur -->
    <div id="menuBackdrop" onclick="closeAllDotsMenus()" style="display: none; position: fixed; inset: 0; z-index: 999; background: rgba(0, 0, 0, 0.08); backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px); transition: all 0.2s ease;"></div>

    <div class="modal-backdrop" id="invoiceSliderBackdrop" style="display: none; z-index: 2000;">
        <div class="modal-panel" style="max-width: 950px; width: 90%; height: 85vh; display: flex; flex-direction: column; position: relative;">
            <button onclick="closeInvoiceSlider()" style="position: absolute; top: 12px; right: 16px; background: var(--c-fill); border: none; font-size: 16px; font-weight: bold; color: var(--t-secondary); cursor: pointer; width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; z-index: 10;">✕</button>
            <iframe id="invoiceIframe" src="about:blank" style="width: 100%; flex: 1; border: none; border-radius: var(--r-lg); background: #fff;"></iframe>
        </div>
    </div>

    <!-- Secure Delete Confirmation Modal -->
    <div class="modal-backdrop" id="deleteConfirmModal" style="display: none; z-index: 2005;">
        <div class="modal-panel">
            <div class="modal-header" style="background: var(--c-red-light); color: var(--c-red); border-bottom: 0.5px solid var(--c-separator); padding: 16px 20px;">
                <h3 style="margin:0; font-size:16px; font-weight:700;"><i class="ph ph-warning-octagon"></i> Secure Deletion</h3>
            </div>
            <div style="padding: 20px; display: flex; flex-direction: column; gap: 15px;">
                <p style="margin:0; font-size:13px; color:var(--t-secondary);">
                    You are about to delete Sales Order <strong id="deleteTargetInvNum" style="color:var(--t-primary);"></strong>. This will permanently reverse general ledger entries and restore stock to inventory.
                </p>
                <div>
                    <label style="font-weight:bold; font-size:11px; text-transform:uppercase; color:var(--t-secondary); display:block; margin-bottom:4px;">Administrator Password *</label>
                    <input type="password" id="deleteConfirmPassword" placeholder="Enter password" style="width:100%; padding:10px; border:1px solid var(--c-separator); border-radius:var(--r-md); background:var(--c-bg); color:var(--t-primary); font-size:13px;" required>
                </div>
                <div>
                    <label style="font-weight:bold; font-size:11px; text-transform:uppercase; color:var(--t-secondary); display:block; margin-bottom:4px;">Reason for Deletion *</label>
                    <textarea id="deleteConfirmReason" placeholder="Reason (e.g. Cancelled by customer)" style="width:100%; height:70px; padding:10px; border:1px solid var(--c-separator); border-radius:var(--r-md); background:var(--c-bg); color:var(--t-primary); font-size:13px; resize:none;" required></textarea>
                </div>
            </div>
            <div style="padding: 12px 20px; background: var(--c-surface2); border-top: 0.5px solid var(--c-separator); display:flex; justify-content:flex-end; gap:10px;">
                <button onclick="closeDeleteConfirmModal()" style="padding:8px 16px; background:var(--c-fill); border:1px solid var(--c-separator); color:var(--t-secondary); border-radius:var(--r-sm); font-size:12px; font-weight:bold; cursor:pointer;">Cancel</button>
                <button onclick="submitDeleteSalesOrder()" style="padding:8px 16px; background:var(--c-red); border:none; color:#fff; border-radius:var(--r-sm); font-size:12px; font-weight:bold; cursor:pointer;">Permanently Delete</button>
            </div>
        </div>
    </div>

    <!-- Move Sales Order Modal -->
    <div class="modal-backdrop" id="moveInvoiceModal" style="display: none; z-index: 2005;">
        <div class="modal-panel">
            <div class="modal-header" style="background: var(--c-blue-light); color: var(--c-blue); border-bottom: 0.5px solid var(--c-separator); padding: 16px 20px;">
                <h3 style="margin:0; font-size:16px; font-weight:700;"><i class="ph ph-arrow-square-out"></i> Move Sales Order</h3>
            </div>
            <div style="padding: 20px; display: flex; flex-direction: column; gap: 15px;">
                <p style="margin:0; font-size:13px; color:var(--t-secondary);">
                    Select the destination route to move Sales Order <strong id="moveTargetInvNum" style="color:var(--t-primary);"></strong> to.
                </p>
                <div>
                    <label style="font-weight:bold; font-size:11px; text-transform:uppercase; color:var(--t-secondary); display:block; margin-bottom:4px;">Destination Route *</label>
                    <select id="moveDestinationRouteSelect" style="width:100%; padding:10px; border:1px solid var(--c-separator); border-radius:var(--r-md); background:var(--c-bg); color:var(--t-primary); font-size:13px;" required>
                        <option value="">-- Select Destination Route --</option>
                    </select>
                </div>
            </div>
            <div style="padding: 12px 20px; background: var(--c-surface2); border-top: 0.5px solid var(--c-separator); display:flex; justify-content:flex-end; gap:10px;">
                <button onclick="closeMoveInvoiceModal()" style="padding:8px 16px; background:var(--c-fill); border:1px solid var(--c-separator); color:var(--t-secondary); border-radius:var(--r-sm); font-size:12px; font-weight:bold; cursor:pointer;">Cancel</button>
                <button onclick="submitMoveSalesOrder()" style="padding:8px 16px; background:var(--c-blue); border:none; color:#fff; border-radius:var(--r-sm); font-size:12px; font-weight:bold; cursor:pointer;">Move Sales Order</button>
            </div>
        </div>
    </div>
</div>

<!-- Dynamic GPS Map Modal -->
<div class="modal-backdrop" id="mapModalBackdrop">
    <div class="modal-panel" style="max-width: 950px; width: 90%; height: 80vh; display: flex; flex-direction: column;">
        <div style="background: #3f51b5; color: #fff; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; font-weight: bold; font-size: 15px;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <span><i class="ph ph-map-pin"></i> GPS Route Path Tracking</span>
                <span id="modalRouteName" style="background: rgba(255,255,255,0.2); padding: 2px 8px; border-radius: 4px; font-size: 11px;"></span>
                <span id="pathPointCount" style="font-weight: normal; font-size: 11px;"></span>
            </div>
            <button class="close-slider" onclick="closeMapModal()">✕</button>
        </div>
        <div style="flex: 1; display: flex; flex-direction: column; position: relative; background: #eef1f4;">
            <div style="flex: 1; position: relative; min-height: 350px;">
                <div id="mapEmptyOverlay" class="map-empty-overlay">Loading...</div>
                <div id="routePathMap" style="height: 100%; width: 100%;"></div>
            </div>
            <div class="path-step-list" id="pathStepList" style="display: none;">
                <ol id="pathStepOl"></ol>
            </div>
        </div>
    </div>
</div>

<!-- Create Manual Route Modal -->
<div class="modal-backdrop" id="createManualRouteModal">
    <div class="modal-panel" style="max-width: 480px; width: 95%;">
        <div class="modal-header" style="background: #2e7d32;">
            <span><i class="ph ph-plus-circle"></i> Create Route Manually</span>
            <button onclick="closeCreateRouteModal()" style="background:transparent; border:none; color:#fff; font-size:18px; cursor:pointer; font-weight:bold;">✕</button>
        </div>
        <form action="<?= APP_URL ?>/RepTracking/create_route_manual" method="POST">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
            <div class="modal-body">
                <div>
                    <label for="mrRep">Select Representative *</label>
                    <select name="user_id" id="mrRep" style="width: 100%; padding: 8px 12px; border: 1px solid #ccc; border-radius: 4px; font-size: 13px; background: white;" required>
                        <option value="">-- Select Rep --</option>
                        <?php foreach($data['reps'] as $rep): ?>
                            <option value="<?= $rep->id ?>"><?= htmlspecialchars(($rep->first_name ? $rep->first_name . ' ' . $rep->last_name : $rep->username)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="mrRouteName">Route Name (Territory/Area) *</label>
                    <input list="mca_areas_list" name="route_name" id="mrRouteName" placeholder="Select or type route name..." style="width: 100%; padding: 8px 12px; border: 1px solid #ccc; border-radius: 4px; font-size: 13px; background: white;" required autocomplete="off">
                    <datalist id="mca_areas_list">
                        <?php foreach($data['mca_areas'] as $area): ?>
                            <option value="<?= htmlspecialchars($area->name) ?>"></option>
                        <?php endforeach; ?>
                    </datalist>
                </div>
                <div>
                    <label for="mrStartMeter">Starting Odometer / Meter *</label>
                    <input type="number" step="0.1" name="start_meter" id="mrStartMeter" value="0.0" min="0" max="999999" oninput="if(this.value.length > 6) this.value = this.value.slice(0,6);" style="width: 100%; padding: 8px 12px; border: 1px solid #ccc; border-radius: 4px; font-size: 13px; background: white;" required>
                </div>
                <div>
                    <label for="mrStartTime">Start Date & Time *</label>
                    <input type="datetime-local" name="start_time" id="mrStartTime" value="<?= date('Y-m-d\TH:i') ?>" style="width: 100%; padding: 8px 12px; border: 1px solid #ccc; border-radius: 4px; font-size: 13px; background: white;" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="qb-btn" onclick="closeCreateRouteModal()" style="border:1px solid #ccc; padding:8px 18px; border-radius:4px; font-size:12px; cursor:pointer;">Cancel</button>
                <button type="submit" class="qb-btn" style="background:#2e7d32; color:#fff; border:none; padding:8px 18px; border-radius:4px; font-size:12px; cursor:pointer; font-weight: bold;"><i class="ph ph-lightning"></i> Create Route</button>
            </div>
        </form>
    </div>
</div>

<!-- Route Multi-Binding Modal -->
<div class="modal-backdrop" id="routeBindingModal">
    <div class="modal-panel" style="max-width: 900px; width: 95%; max-height: 90vh;">
        <div class="modal-header" style="background: #3f51b5;">
            <span><i class="ph ph-link"></i> Rep Route Multi-Binding Panel</span>
            <button onclick="closeRouteBindingModal()" style="background:transparent; border:none; color:#fff; font-size:18px; cursor:pointer; font-weight:bold;">✕</button>
        </div>
        <div class="modal-body" style="overflow-y:auto; flex:1;">
            <div style="margin-bottom: 20px;">
                <label>Custom Name for Bound Group</label>
                <input type="text" id="rbBoundName" placeholder="e.g. Western Route Combined - June 15">
            </div>
            <label>Route Slots</label>
            <div id="rbSlotsContainer" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(250px,1fr)); gap:15px; margin-bottom:15px;"></div>
            <button type="button" onclick="addBindingSlot()" style="background: #eef2ff; color: #3f51b5; border: 1px dashed #3f51b5; padding: 10px; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 13px; display: block; width: 100%;"><i class="ph ph-plus-circle"></i> Add Route Slot</button>
        </div>
        <div class="modal-footer">
            <button class="qb-btn" onclick="closeRouteBindingModal()" style="border:1px solid #ccc; padding:8px 18px; border-radius:4px; font-size:12px; cursor:pointer;">Cancel</button>
            <button class="qb-btn" onclick="submitRouteBinding()" style="background:#2e7d32; color:#fff; border:none; padding:8px 18px; border-radius:4px; font-size:12px; cursor:pointer; font-weight: bold;"><i class="ph ph-lightning"></i> Confirm & Create Route Binding</button>
        </div>
    </div>
</div>

<!-- Attach Sales Order Modal -->
<div class="modal-backdrop" id="attachInvoiceModal">
    <div class="modal-panel" style="max-width: 580px; width: 90%;">
        <div class="modal-header" style="background: #5c6bc0;">
            <span><i class="ph ph-link"></i> Attach Sales Orders to Route</span>
            <button onclick="closeAttachInvoiceModal()" style="background:transparent; border:none; color:#fff; font-size:18px; cursor:pointer; font-weight:bold;">✕</button>
        </div>
        <div class="modal-body">
            <div>
                <label>Search Sales Order or Customer</label>
                <input type="text" id="invoiceSearchInput" onkeyup="searchUnattachedInvoices()" placeholder="Type SO-XXXX or customer name...">
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <div>
                    <label>Start Date</label>
                    <input type="date" id="soFilterStartDate" onchange="searchUnattachedInvoices()">
                </div>
                <div>
                    <label>End Date</label>
                    <input type="date" id="soFilterEndDate" onchange="searchUnattachedInvoices()">
                </div>
            </div>
            <div>
                <label>Status Filter</label>
                <select id="soFilterStatus" onchange="searchUnattachedInvoices()" style="width: 100%; padding: 8px 12px; border: 1px solid #ccc; border-radius: 4px; font-size: 13px; background: white;">
                    <option value="">All Statuses</option>
                    <option value="Unpaid">Unpaid</option>
                    <option value="Paid">Paid</option>
                    <option value="Pending">Pending</option>
                </select>
            </div>
            <div id="unattachedInvoicesContainer" style="border: 1px solid #ccc; border-radius: 6px; padding: 10px; max-height: 200px; overflow-y: auto; background: #fafafa; font-size: 12px; margin-top: 5px;">
                <p style="text-align: center; color: #888; margin: 10px 0;">Start typing to search...</p>
            </div>
        </div>
        <div class="modal-footer">
            <button class="qb-btn" onclick="closeAttachInvoiceModal()" style="border:1px solid #ccc; padding:6px 14px; border-radius:4px; font-size:12px; cursor:pointer;">Cancel</button>
            <button class="qb-btn" onclick="confirmAttachInvoices()" style="background:#5c6bc0; color:#fff; border:none; padding:6px 14px; border-radius:4px; font-size:12px; cursor:pointer; font-weight: bold;">Attach Selected</button>
        </div>
    </div>
</div>

<!-- Route Switcher Modal -->
<div class="modal-backdrop" id="routeSwitcherModalBackdrop">
    <div class="modal-panel" style="max-width: 550px; width: 90%; max-height: 80vh; display: flex; flex-direction: column;">
        <div class="modal-header" style="background: #3f51b5; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; color: #fff; font-weight: bold;">
            <span><i class="ph ph-swap"></i> Switch Route</span>
            <button onclick="closeRouteSwitcherModal()" style="background:transparent; border:none; color:#fff; font-size:18px; cursor:pointer; font-weight:bold;">✕</button>
        </div>
        <div style="padding: 15px; border-bottom: 1px solid #e2e8f0; background: #fff;">
            <input type="text" id="routeSwitcherSearchInput" placeholder="Search routes by name, rep..." style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px;" oninput="searchRouteSwitcherList()" />
        </div>
        <div class="modal-body" style="overflow-y: auto; flex: 1; padding: 15px; display: flex; flex-direction: column; gap: 10px; background: #fafafa;" id="routeSwitcherItemsContainer">
            <?php foreach($data['routes'] as $route): ?>
                <div class="switcher-route-item" onclick="selectRouteFromSwitcher(<?= $route->id ?>)" style="padding: 12px; border: 1px solid #e2e8f0; border-radius: 6px; background: #fff; cursor: pointer; transition: 0.2s;" data-rname="<?= htmlspecialchars($route->route_name) ?>" data-rep="<?= htmlspecialchars($route->first_name . ' ' . $route->last_name) ?>">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                        <strong style="color: #3f51b5; font-size: 12px; font-family: monospace;">#RT-<?= str_pad($route->id, 5, '0', STR_PAD_LEFT) ?></strong>
                        <span style="font-size: 10px; padding: 2px 6px; border-radius: 4px; background: #e2e8f0; font-weight: bold; color: #555;"><?= htmlspecialchars($route->status) ?></span>
                    </div>
                    <div style="font-weight: bold; font-size: 13px; color: #333;"><?= htmlspecialchars($route->route_name) ?></div>
                    <div style="font-size: 11px; color: #666;">Rep: <?= htmlspecialchars($route->first_name . ' ' . $route->last_name) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Server-side Delivery Process Modal -->
<div class="modal-backdrop" id="serverDeliveryProcessModal" style="display: none; align-items: center; justify-content: center;">
    <div class="modal-panel" style="max-width: 750px; width: 95%; max-height: 90vh; display: flex; flex-direction: column;">
        <div class="modal-header" style="background: #0066cc; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; color: #fff; font-weight: bold;">
            <span><i class="ph ph-steering-wheel"></i> Process Visit: <span id="sdpCustomerName"></span></span>
            <button onclick="closeServerDeliveryProcessModal()" style="background:transparent; border:none; color:#fff; font-size:18px; cursor:pointer; font-weight:bold;">✕</button>
        </div>
        <div class="modal-body" style="overflow-y: auto; flex: 1; padding: 20px; display: flex; flex-direction: column; gap: 20px; background: #fafafa;">
            
            <!-- Hidden details -->
            <input type="hidden" id="sdpInvoiceId" />
            <input type="hidden" id="sdpCustomerId" />

            <!-- Visit Status & Info -->
            <div style="background: #fff; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; gap: 15px;">
                <div>
                    <div style="font-size: 11px; text-transform: uppercase; color: #888; font-weight: bold;">Processing Invoice</div>
                    <strong id="sdpInvoiceNumber" style="font-size: 15px; color: #0066cc;"></strong>
                </div>
                <div>
                    <div style="font-size: 11px; text-transform: uppercase; color: #888; font-weight: bold;">Delivery Status</div>
                    <select id="sdpDeliveryStatus" style="padding: 6px 12px; border-radius: 6px; border: 1px solid #cbd5e1; font-weight: bold; font-size: 13px;">
                        <option value="Pending">Pending</option>
                        <option value="Delivered">Delivered</option>
                        <option value="Cancelled">Cancelled</option>
                        <option value="Postponed">Postponed</option>
                    </select>
                </div>
            </div>

            <!-- Arrears & Outstanding Info -->
            <div id="sdpArrearsInfoBox" style="background: #fffbeb; padding: 12px 15px; border-radius: 8px; border: 1px solid #fef3c7; color: #b45309; font-size: 13px; font-weight: 500;">
                <i class="ph ph-warning"></i> Customer Outstanding Balance: <strong id="sdpOutstandingArrears">Rs 0.00</strong>
            </div>

            <!-- Items Section (Adjust Bills) -->
            <div style="background: #fff; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0;">
                <h4 style="margin: 0 0 10px 0; font-size: 13px; font-weight: bold; color: #333; display: flex; align-items: center; gap: 6px;">
                    <i class="ph ph-package" style="color:#0066cc;"></i> Adjust Invoice Items (Bill Quantity)
                </h4>
                <div style="max-height: 200px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 6px;">
                    <table class="data-table" style="margin-top: 0; font-size: 12px;">
                        <thead style="position: sticky; top: 0; background: #f8fafc; z-index: 5;">
                            <tr>
                                <th>Item Description</th>
                                <th style="text-align: right; width: 80px;">Loaded</th>
                                <th style="text-align: right; width: 120px;">Delivered Qty</th>
                            </tr>
                        </thead>
                        <tbody id="sdpItemsTbody">
                            <!-- Populated in JS -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Collections Section (Record Payments & Credit Collections) -->
            <div style="background: #fff; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0;">
                <h4 style="margin: 0 0 15px 0; font-size: 13px; font-weight: bold; color: #333; display: flex; align-items: center; gap: 6px;">
                    <i class="ph ph-coins" style="color:#2e7d32;"></i> Record Payments & Collections
                </h4>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                    <div>
                        <label>Cash Amount (Rs)</label>
                        <input type="number" step="0.01" min="0" id="sdpCashAmount" style="width:100%; padding:8px 12px; border:1px solid #ccc; border-radius:6px;" value="0.00">
                    </div>
                    <div>
                        <label>Bank Transfer (Rs)</label>
                        <input type="number" step="0.01" min="0" id="sdpBankAmount" style="width:100%; padding:8px 12px; border:1px solid #ccc; border-radius:6px;" value="0.00">
                    </div>
                </div>

                <!-- Cheques list section -->
                <div style="border-top: 1px dashed #e2e8f0; padding-top: 15px;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                        <strong style="font-size:12px; color:#555;">Cheque Collections</strong>
                        <button type="button" onclick="addSdpChequeRow()" class="btn-premium secondary" style="padding:4px 8px; font-size:11px; cursor:pointer;"><i class="ph ph-plus"></i> Add Cheque</button>
                    </div>
                    <div id="sdpChequesContainer" style="display:flex; flex-direction:column; gap:10px;">
                        <!-- Cheque rows go here -->
                    </div>
                </div>
            </div>

        </div>
        <div class="modal-footer" style="background: #f8fafc; border-top: 1px solid #e2e8f0;">
            <button class="qb-btn" onclick="closeServerDeliveryProcessModal()" style="border:1px solid #ccc; padding:8px 18px; border-radius:6px; font-size:12px; cursor:pointer;">Cancel</button>
            <button class="qb-btn" onclick="submitServerDeliveryProcess()" style="background:#0066cc; color:#fff; border:none; padding:8px 18px; border-radius:6px; font-size:12px; cursor:pointer; font-weight: bold;">Save & Process Visit</button>
        </div>
    </div>
</div>

<!-- FLOATING COMMAND BAR -->
<div class="cmd-bar">
    <div class="cmd-search" onclick="document.getElementById('floatingSearchInput').focus()">
        <i class="ph ph-magnifying-glass"></i>
        <input type="text" id="floatingSearchInput" oninput="searchRouteList()" placeholder="Search routes...">
    </div>
    <div class="cmd-divider"></div>
    <button type="button" onclick="window.location.reload()" class="cmd-icon" title="Refresh page">
        <i class="ph ph-arrows-clockwise"></i>
    </button>
</div>

<script>
    const globalBankAccounts = <?php echo json_encode($data['bank_accounts'] ?? []); ?>;
    const globalAllAccounts = <?php echo json_encode($data['all_accounts'] ?? []); ?>;
    let currentRouteId = null;
    let routeMap = null;
    let routeMapLayers = [];
    let rbSlotsCount = 2;
    let activeRouteBills = [];
    let currentDeliveryDetails = null;

    const pathGreenIcon = new L.Icon({
        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png',
        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
        iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34]
    });
    const pathRedIcon = new L.Icon({
        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
        iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34]
    });
    const pathBlueIcon = new L.Icon({
        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png',
        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
        iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34]
    });

    window.addEventListener('DOMContentLoaded', () => {
        const urlParams = new URLSearchParams(window.location.search);
        const routeId = urlParams.get('route_id');

        searchRouteList();

        if (routeId) {
            const routeEl = document.getElementById('route_' + routeId);
            if (routeEl) {
                loadRouteDetails(routeId, routeEl);
                routeEl.scrollIntoView({ block: 'nearest' });
            }
        }
    });

    function filterLeftPane(type, btn) {
        // Backwards compatibility stub
    }

    function searchRouteList() {
        const query = document.getElementById('floatingSearchInput').value.toLowerCase().trim();
        const selectedRep = document.getElementById('filterRepSelect').value.toLowerCase().trim();
        const selectedRoute = document.getElementById('filterRouteSelect').value.toLowerCase().trim();
        const selectedDate = document.getElementById('filterDateInput').value;
        const selectedTerritory = document.getElementById('filterTerritorySelect').value.toLowerCase().trim();

        document.querySelectorAll('.route-item').forEach(item => {
            const routeId = item.id.replace('route_', '');
            const dataEl = document.getElementById('route_data_' + routeId);
            if (!dataEl) return;

            const repName = dataEl.getAttribute('data-rep').toLowerCase();
            const routeName = dataEl.getAttribute('data-rname').toLowerCase();
            const routeDate = dataEl.getAttribute('data-date');
            const routeTerritory = dataEl.getAttribute('data-territory').toLowerCase();
            const constituent = dataEl.getAttribute('data-constituent').toLowerCase();
            
            const routeNo = '#rt-' + routeId.padStart(5, '0');

            // 1. Matches query (search bar)
            const matchesQuery = !query || 
                routeName.includes(query) || 
                repName.includes(query) || 
                routeId.includes(query) || 
                routeNo.includes(query) ||
                constituent.includes(query);

            // 2. Matches Rep
            const matchesRep = !selectedRep || repName === selectedRep;

            // 3. Matches Route Name
            const matchesRoute = !selectedRoute || routeName === selectedRoute;

            // 4. Matches Date
            const matchesDate = !selectedDate || routeDate === selectedDate;

            // 5. Matches Territory
            const matchesTerritory = !selectedTerritory || 
                routeTerritory === selectedTerritory || 
                constituent.includes(selectedTerritory);

            if (matchesQuery && matchesRep && matchesRoute && matchesDate && matchesTerritory) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });
    }

    function clearFilters() {
        document.getElementById('filterRepSelect').value = '';
        document.getElementById('filterRouteSelect').value = '';
        document.getElementById('filterDateInput').value = '';
        document.getElementById('filterTerritorySelect').value = '';
        document.getElementById('floatingSearchInput').value = '';
        searchRouteList();
    }

    function openRouteSwitcherModal() {
        const modal = document.getElementById('routeSwitcherModalBackdrop');
        if (modal) {
            modal.style.display = 'flex';
            document.getElementById('routeSwitcherSearchInput').value = '';
            searchRouteSwitcherList();
            document.getElementById('routeSwitcherSearchInput').focus();
        }
    }

    function closeRouteSwitcherModal() {
        const modal = document.getElementById('routeSwitcherModalBackdrop');
        if (modal) modal.style.display = 'none';
    }

    function openCreateRouteModal() {
        const modal = document.getElementById('createManualRouteModal');
        if (modal) {
            modal.style.display = 'flex';
            // Set current date/time to now
            const now = new Date();
            const offset = now.getTimezoneOffset() * 60000;
            const localISOTime = (new Date(now - offset)).toISOString().slice(0, 16);
            document.getElementById('mrStartTime').value = localISOTime;
        }
    }

    function closeCreateRouteModal() {
        const modal = document.getElementById('createManualRouteModal');
        if (modal) modal.style.display = 'none';
    }

    function searchRouteSwitcherList() {
        const query = document.getElementById('routeSwitcherSearchInput').value.toLowerCase().trim();
        document.querySelectorAll('.switcher-route-item').forEach(item => {
            const rname = item.getAttribute('data-rname').toLowerCase();
            const rep = item.getAttribute('data-rep').toLowerCase();
            if (rname.includes(query) || rep.includes(query)) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });
    }

    function selectRouteFromSwitcher(routeId) {
        closeRouteSwitcherModal();
        const routeEl = document.getElementById('route_' + routeId);
        loadRouteDetails(routeId, routeEl);
        
        // Update URL query param to reflect the new route active without reloading
        const url = new URL(window.location);
        url.searchParams.set('route_id', routeId);
        window.history.replaceState({}, '', url);
    }

    function goBackToRoutes() {
        currentRouteId = null;
        document.body.classList.remove('workspace-showing');
        document.querySelector('.app-workspace').classList.remove('workspace-active');
        
        document.querySelectorAll('.route-item').forEach(i => i.classList.remove('active'));
        
        // Clear route_id query parameter from the URL
        const url = new URL(window.location);
        url.searchParams.delete('route_id');
        window.history.replaceState({}, '', url);
    }

    const CSRF_TOKEN = '<?= $_SESSION['csrf_token'] ?? '' ?>';
    let currentTabIndex = 1;
    let currentRouteStatus = 'Active';

    // Fetch wrapper to inject CSRF token to headers and bodies
    function fetchSecure(url, options = {}) {
        options.headers = options.headers || {};
        options.headers['X-CSRF-TOKEN'] = CSRF_TOKEN;
        
        if (options.body && typeof options.body === 'string') {
            try {
                const parsed = JSON.parse(options.body);
                if (typeof parsed === 'object' && parsed !== null) {
                    parsed.csrf_token = CSRF_TOKEN;
                    options.body = JSON.stringify(parsed);
                }
            } catch (e) {
                // Ignore parsing errors
            }
        }
        return fetch(url, options);
    }

    // Observer trigger to refresh Loading and Variance stages
    function onRouteDataChanged() {
        if (!currentRouteId) return;
        loadLoadingStage(currentRouteId);
        loadVarianceAdjustmentStage(currentRouteId);
    }

    function updateSidebarProgress() {
        const steps = [
            { id: 1, name: 'Route Details', statusKey: 'Active' },
            { id: 2, name: 'Credit Collections', statusKey: 'Pending GL' },
            { id: 3, name: 'Bill Adjustments', statusKey: 'Adjustments' },
            { id: 4, name: 'Loading', statusKey: 'Loading' },
            { id: 5, name: 'Variance Audit', statusKey: 'Variance Adjustment' },
            { id: 6, name: 'Delivery Arrange', statusKey: 'Finalizing' },
            { id: 7, name: 'Delivery Execution', statusKey: 'Finalizing' },
            { id: 8, name: 'Reconciliation', statusKey: 'Finalizing' },
            { id: 9, name: 'Return Stock', statusKey: 'Finalizing' },
            { id: 10, name: 'Accounting', statusKey: 'Finalizing' }
        ];

        const statusSequence = ['Active', 'Pending GL', 'Adjustments', 'Loading', 'Variance Adjustment', 'Finalizing', 'Completed', 'Finalized'];
        const currentRouteStatusIndex = statusSequence.indexOf(currentRouteStatus);

        steps.forEach(step => {
            const el = document.getElementById('sb-step-' + step.id);
            if (!el) return;

            // Remove all states
            el.classList.remove('active', 'completed', 'pending', 'locked');
            
            // Step dot element
            const dot = el.querySelector('.step-dot');
            dot.innerHTML = step.id; // Default back to step number

            // Determine active tab
            if (step.id === currentTabIndex) {
                el.classList.add('active');
            }

            let stepRequiredStatusIndex = statusSequence.indexOf(step.statusKey);
            let isStepCompleted = false;

            // Mark as completed if the route's current status is past the step's required status
            if (currentRouteStatus === 'Completed' || currentRouteStatus === 'Finalized') {
                isStepCompleted = true;
            } else if (currentRouteStatusIndex > stepRequiredStatusIndex) {
                isStepCompleted = true;
            } else if (currentRouteStatus === 'Finalizing') {
                // Inside Finalizing, sub-stages can have completion heuristics:
                const d = document.getElementById('route_data_' + currentRouteId);
                const delId = d ? d.getAttribute('data-delivery-id') : null;
                const delStatus = d ? d.getAttribute('data-delivery-status') : null;

                if (step.id === 6 && delId && delId !== '0' && delId !== '') {
                    isStepCompleted = true; // Arranged is completed if delivery ID exists
                } else if (step.id === 7 && delStatus === 'Completed') {
                    isStepCompleted = true; // Delivery is completed if delivery status is completed
                } else if (step.id === 8) {
                    // Check if reconciliation draft was saved
                    const cashVal = parseFloat(document.getElementById('reconActualCash')?.value || 0);
                    if (cashVal > 0) isStepCompleted = true;
                } else if (step.id === 9) {
                    // Check if stock verified checkbox is checked
                    if (document.getElementById('settleVerifyStock')?.checked) {
                        isStepCompleted = true;
                    }
                }
            }

            if (isStepCompleted) {
                el.classList.add('completed');
                dot.innerHTML = '<i class="fa-solid fa-check"></i>';
            } else {
                el.classList.add('pending');
            }
        });
    }

    function updateWizardProgress(status) {
        updateSidebarProgress();
    }

    function loadRouteDetails(routeId, el) {
        currentRouteId = routeId;
        
        document.querySelectorAll('.route-item').forEach(i => i.classList.remove('active'));
        if (el) el.classList.add('active');
        else {
            const sidebarEl = document.getElementById('route_' + routeId);
            if (sidebarEl) sidebarEl.classList.add('active');
        }

        const d = document.getElementById('route_data_' + routeId);
        const routeName = d.getAttribute('data-rname');
        const repName = d.getAttribute('data-rep');
        const status = d.getAttribute('data-status');
        const bindingId = d.getAttribute('data-binding-id');
        const isBound = d.getAttribute('data-bound') === '1';

        currentRouteStatus = status;

        document.getElementById('mhRouteName').innerText = routeName;
        document.getElementById('mhRepName').innerText = repName;
        document.getElementById('mhStart').innerText = d.getAttribute('data-start');
        document.getElementById('mhEnd').innerText = d.getAttribute('data-end');
        document.getElementById('mhSales').innerText = d.getAttribute('data-sales');
        document.getElementById('mhBills').innerText = d.getAttribute('data-bills');

        const formattedRouteNo = '#RT-' + String(routeId).padStart(5, '0');
        document.getElementById('mhRouteNumber').innerText = 'Route ' + formattedRouteNo;
        
        const statusBadge = document.getElementById('mhRouteStatusBadge');
        if (statusBadge) {
            statusBadge.innerText = status;
            statusBadge.style.background = (status === 'Completed' || status === 'Finalized') ? '#e2f0d9' : '#fff3cd';
            statusBadge.style.color = (status === 'Completed' || status === 'Finalized') ? '#2e7d32' : '#d97706';
            statusBadge.style.borderColor = (status === 'Completed' || status === 'Finalized') ? '#2e7d32' : '#d97706';
        }

        const boundSummary = document.getElementById('boundRouteSummaryContainer');
        if (boundSummary) {
            boundSummary.style.display = 'none';
        }
        const isMerged = d.getAttribute('data-merged') === '1';
        if (isMerged) {
            fetchSecure('<?= APP_URL ?>/RepTracking/api_get_bound_routes_summary/' + routeId)
                .then(res => res.json())
                .then(resData => {
                    if (resData.status === 'success') {
                        document.getElementById('brsConstituentsList').innerText = resData.constituents.map(c => `${c.route_name} (ID: #${c.id})`).join(', ');
                        document.getElementById('brsTotalCustomers').innerText = resData.total_customers;
                        document.getElementById('brsTotalInvoices').innerText = resData.total_invoices;
                        document.getElementById('brsTotalValue').innerText = 'Rs ' + parseFloat(resData.total_value).toLocaleString('en-IN', {minimumFractionDigits:2});
                        document.getElementById('brsTotalProducts').innerText = `${resData.unique_products} unique items (Total Qty: ${resData.total_products_qty})`;
                        if (boundSummary) {
                            boundSummary.style.display = 'block';
                        }
                    }
                });
        }

        document.getElementById('midHeader').style.visibility = 'visible';
        document.getElementById('midEmptyState').style.display = 'none';
        document.getElementById('workspaceLayoutWrapper').style.display = 'flex';
        document.getElementById('btnViewMap').style.display = 'inline-flex';

        const btnUnbind = document.getElementById('btnUnbindRoute');
        if (btnUnbind) {
            if (isBound && bindingId) {
                btnUnbind.style.display = 'inline-flex';
                btnUnbind.setAttribute('data-binding-id', bindingId);
            } else {
                btnUnbind.style.display = 'none';
            }
        }

        updateWizardProgress(status);

        document.getElementById('routeWorkspaceTabs').style.display = 'flex';
        document.getElementById('stageContentWrapper').style.display = 'block';

        // Toggle completed archive banner
        const archiveBanner = document.getElementById('completedArchiveBanner');
        if (archiveBanner) {
            if (status === 'Completed' || status === 'Finalized') {
                archiveBanner.style.display = 'flex';
            } else {
                archiveBanner.style.display = 'none';
            }
        }

        // Close slider
        closeInvoiceSlider();

        // Switch to the last selected index, default to 1 (Details)
        switchRouteTab(currentTabIndex);

        // Transition views
        document.body.classList.add('workspace-showing');
        document.querySelector('.app-workspace').classList.add('workspace-active');
    }

    function switchRouteTab(tabIndex) {
        currentTabIndex = tabIndex;
        
        // Update tab buttons styling
        document.querySelectorAll('#routeWorkspaceTabs .scroll-tab-btn').forEach((btn, idx) => {
            if (idx + 1 === tabIndex) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        });
        
        // Toggle tab panels display
        document.querySelectorAll('.workspace-tab-panel').forEach(panel => {
            panel.style.display = 'none';
        });
        const activePanel = document.getElementById('tabpanel-' + tabIndex);
        if (activePanel) {
            activePanel.style.display = 'block';
        }
        
        updateSidebarProgress();
        if (!currentRouteId) return;
        
        // Dynamically load tab data
        switch (tabIndex) {
            case 1:
                loadTab1Details(currentRouteId);
                break;
            case 2:
                loadCollectionsVerificationStage2(currentRouteId);
                break;
            case 3:
                loadAdjustmentsStage(currentRouteId);
                break;
            case 4:
                loadLoadingStage(currentRouteId);
                break;
            case 5:
                loadVarianceAdjustmentStage(currentRouteId);
                break;
            case 6:
                loadDispatchStage(currentRouteId);
                break;
            case 7:
                loadDeliveryLiveStage(currentRouteId);
                break;
            case 8:
                loadTab8Reconciliation(currentRouteId);
                break;
            case 9:
                loadTab9ReturnStock(currentRouteId);
                break;
            case 10:
                loadTab10Accounting(currentRouteId);
                break;
        }
    }

    function loadTab1Details(routeId) {
        const d = document.getElementById('route_data_' + routeId);
        if (!d) return;

        const formattedRouteNo = '#RT-' + String(routeId).padStart(5, '0');

        document.getElementById('tab1RouteNumber').innerText = formattedRouteNo;
        document.getElementById('tab1RouteName').innerText = d.getAttribute('data-rname') || '';
        document.getElementById('tab1RepName').innerText = d.getAttribute('data-rep') || '';
        
        const status = d.getAttribute('data-status') || '';
        const statusBadge = document.getElementById('tab1Status');
        if (statusBadge) {
            statusBadge.innerText = status;
            statusBadge.style.background = (status === 'Completed' || status === 'Finalized') ? '#e2f0d9' : '#fff3cd';
            statusBadge.style.color = (status === 'Completed' || status === 'Finalized') ? '#2e7d32' : '#d97706';
            statusBadge.style.borderColor = (status === 'Completed' || status === 'Finalized') ? '#2e7d32' : '#d97706';
        }

        document.getElementById('tab1SalesValue').innerText = 'Rs ' + (d.getAttribute('data-sales') || '0.00');
        document.getElementById('tab1BillsCount').innerText = d.getAttribute('data-bills') || '0';

        document.getElementById('tab1StartTime').innerText = d.getAttribute('data-start-time') || '';
        document.getElementById('tab1EndTime').innerText = d.getAttribute('data-end-time') || '';
        document.getElementById('tab1StartMeter').innerText = d.getAttribute('data-start') || '';
        document.getElementById('tab1EndMeter').innerText = d.getAttribute('data-end') || '';
        
        const start = parseFloat(d.getAttribute('data-start')) || 0;
        const end = parseFloat(d.getAttribute('data-end')) || 0;
        document.getElementById('tab1Distance').innerText = (end > start) ? (end - start) + ' km' : 'Active';

        const isReadOnly = (currentRouteStatus === 'Completed' || currentRouteStatus === 'Finalized');
        const notesTextarea = document.getElementById('tab1RouteNotes');
        const saveNotesBtn = document.getElementById('btnSaveRouteNotes');

        notesTextarea.readOnly = isReadOnly;
        saveNotesBtn.disabled = isReadOnly;
        saveNotesBtn.style.opacity = isReadOnly ? '0.5' : '1';
        saveNotesBtn.style.cursor = isReadOnly ? 'not-allowed' : 'pointer';

        fetchSecure('<?= APP_URL ?>/RepTracking/api_get_route_details/' + routeId)
            .then(res => res.json())
            .then(data => {
                notesTextarea.value = data.notes || '';
            });
    }

    function saveRouteNotes() {
        if (!currentRouteId) return;
        const notes = document.getElementById('tab1RouteNotes').value;
        fetchSecure('<?= APP_URL ?>/RepTracking/api_save_route_notes', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ route_id: currentRouteId, notes: notes })
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                alert("Route notes saved successfully!");
            } else {
                alert("Error: " + data.message);
            }
        });
    }

    let currentGLCollectionsState = [];

    function loadCollectionsVerificationStage2(routeId) {
        const tbody = document.getElementById('glCollectionsTableBody');
        tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;">Loading collections... </td></tr>';
        currentGLCollectionsState = [];
        
        document.getElementById('glTotalCash').innerText = 'Rs 0.00';
        document.getElementById('glTotalCheque').innerText = 'Rs 0.00';

        const isReadOnly = (currentRouteStatus === 'Completed' || currentRouteStatus === 'Finalized');
        const saveBtn = document.getElementById('btnSaveCollectionsVerification2');
        if (saveBtn) {
            saveBtn.disabled = isReadOnly;
            saveBtn.style.opacity = isReadOnly ? '0.5' : '1';
            saveBtn.style.cursor = isReadOnly ? 'not-allowed' : 'pointer';
        }

        fetchSecure('<?= APP_URL ?>/RepTracking/api_get_route_collections/' + routeId)
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    currentGLCollectionsState = data.collections || [];
                    renderGLCollectionsVerification();
                } else {
                    tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; color:red;">Failed to load collections.</td></tr>';
                }
            });
    }

    function renderGLCollectionsVerification() {
        const tbody = document.getElementById('glCollectionsTableBody');
        tbody.innerHTML = '';

        let cashSum = 0;
        let chequeSum = 0;

        if (currentGLCollectionsState.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; color:#888; padding: 10px;">No payments collected on this route.</td></tr>';
            checkGLVerification();
            return;
        }

        const isReadOnly = (currentRouteStatus === 'Completed' || currentRouteStatus === 'Finalized');

        currentGLCollectionsState.forEach((col, index) => {
            const isVerified = parseInt(col.is_verified) === 1;
            const isFinalized = col.status === 'Finalized' || isReadOnly;
            const amt = parseFloat(col.amount);
            
            if (col.payment_method === 'Cash') {
                cashSum += amt;
            } else {
                chequeSum += amt;
            }

            let statusSelect = `
                <input type="checkbox" onchange="updateGLCollectionApproval(${index}, this.checked)" 
                       ${(isVerified || isFinalized) ? 'checked' : ''} ${isFinalized ? 'disabled' : ''} 
                       style="width:16px; height:16px; cursor:${isFinalized ? 'not-allowed' : 'pointer'};" />
            `;

            const adjustedVal = col.adjusted_amount !== null ? col.adjusted_amount : col.amount;

            tbody.innerHTML += `
                <tr style="border-bottom:1px solid #f1f5f9;">
                    <td style="padding:6px 4px;">
                        <strong>${col.customer_name}</strong><br>
                        <span style="font-size:10px; color:#64748b;">${col.payment_method} ${col.reference ? '(' + col.reference + ')' : ''}</span>
                        ${isFinalized ? '<br><span style="font-size:10px; color:#2e7d32; font-weight:bold;">Posted to GL</span>' : ''}
                    </td>
                    <td style="padding:6px 4px; text-align:right; font-family:monospace; font-weight:bold;">
                        Rs ${amt.toFixed(2)}
                    </td>
                    <td style="padding:6px 4px; text-align:center;">
                        ${statusSelect}
                    </td>
                    <td style="padding:6px 4px;">
                        <select onchange="updateGLCollectionDebitAccount(${index}, this.value)" 
                                style="width:100%; max-width:180px; padding:4px; font-size:11px; border:1px solid #cbd5e1; border-radius:4px;" 
                                ${isFinalized ? 'disabled' : ''}>
                            ${buildAccountOptions(col.debit_account_id, col.payment_method === 'Cash' ? '1000' : (col.payment_method === 'Cheque' ? '1010' : '1605'))}
                        </select>
                    </td>
                    <td style="padding:6px 4px;">
                        <select onchange="updateGLCollectionCreditAccount(${index}, this.value)" 
                                style="width:100%; max-width:180px; padding:4px; font-size:11px; border:1px solid #cbd5e1; border-radius:4px;" 
                                ${isFinalized ? 'disabled' : ''}>
                            ${buildAccountOptions(col.credit_account_id, '1200')}
                        </select>
                    </td>
                    <td style="padding:6px 4px; text-align:center;">
                        <input type="number" step="0.01" min="0" value="${parseFloat(adjustedVal).toFixed(2)}"
                               oninput="updateGLCollectionAdjustedAmount(${index}, this.value)"
                               ${isFinalized ? 'disabled' : ''}
                               style="width:80px; padding:3px; border:1px solid #cbd5e1; border-radius:4px; text-align:right; font-family:monospace; font-size:11px;" />
                    </td>
                    <td style="padding:6px 4px; text-align:center;">
                        <input type="text" value="${col.verification_notes || ''}" placeholder="Notes"
                               oninput="updateGLCollectionNotes(${index}, this.value)"
                               ${isFinalized ? 'disabled' : ''}
                               style="width:100px; padding:3px; border:1px solid #cbd5e1; border-radius:4px; font-size:11px;" />
                    </td>
                </tr>
            `;
        });

        document.getElementById('glTotalCash').innerText = 'Rs ' + cashSum.toLocaleString('en-US', {minimumFractionDigits: 2});
        document.getElementById('glTotalCheque').innerText = 'Rs ' + chequeSum.toLocaleString('en-US', {minimumFractionDigits: 2});

        checkGLVerification();
    }

    function updateGLCollectionApproval(index, checked) {
        const col = currentGLCollectionsState[index];
        if (col) {
            col.is_verified = checked ? 1 : 0;
            col.is_flagged = 0;
        }
        checkGLVerification();
    }

    function updateGLCollectionDebitAccount(index, val) {
        const col = currentGLCollectionsState[index];
        if (col) {
            col.debit_account_id = val !== '' ? parseInt(val) : null;
        }
    }

    function updateGLCollectionCreditAccount(index, val) {
        const col = currentGLCollectionsState[index];
        if (col) {
            col.credit_account_id = val !== '' ? parseInt(val) : null;
        }
    }

    function updateGLCollectionAdjustedAmount(index, val) {
        const col = currentGLCollectionsState[index];
        if (col) {
            col.adjusted_amount = val !== '' ? parseFloat(val) : null;
        }
        checkGLVerification();
    }

    function updateGLCollectionNotes(index, val) {
        const col = currentGLCollectionsState[index];
        if (col) {
            col.verification_notes = val;
        }
    }

    function saveCollectionsVerificationStage2() {
        if (!currentRouteId) return;

        const updates = currentGLCollectionsState.map(col => ({
            id: col.id,
            is_verified: col.is_verified,
            is_flagged: col.is_flagged,
            adjusted_amount: col.adjusted_amount !== null ? parseFloat(col.adjusted_amount) : parseFloat(col.amount),
            verification_notes: col.verification_notes,
            debit_account_id: col.debit_account_id || getAccountIdByCode(col.payment_method === 'Cash' ? '1000' : (col.payment_method === 'Cheque' ? '1010' : '1605')),
            credit_account_id: col.credit_account_id || getAccountIdByCode('1200')
        }));

        fetchSecure('<?= APP_URL ?>/RepTracking/api_verify_collections', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ updates: updates })
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                alert(data.message);
                loadCollectionsVerificationStage2(currentRouteId);
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(err => {
            console.error(err);
            alert('An error occurred while saving verification.');
        });
    }

    function checkGLVerification() {
        const btn = document.getElementById('glApproveSalesBtn');
        const text = document.getElementById('glVerificationStatusText');
        if (!btn || !text) return;

        let allCollectionsApproved = true;
        let pendingOrFlaggedCount = 0;
        currentGLCollectionsState.forEach(col => {
            if (parseInt(col.is_verified) !== 1) {
                allCollectionsApproved = false;
                pendingOrFlaggedCount++;
            }
        });

        if (allCollectionsApproved) {
            btn.disabled = false;
            btn.style.opacity = '1';
            btn.style.cursor = 'pointer';
            text.innerHTML = '<span style="color:#2e7d32; font-weight:bold;">Verification Complete!</span> collections approved.';
        } else {
            btn.disabled = true;
            btn.style.opacity = '0.5';
            btn.style.cursor = 'not-allowed';
            text.innerHTML = `<span style="color:#dc2626; font-weight:bold;">Verification Pending:</span> ${pendingOrFlaggedCount} collections remaining.`;
        }
    }

    function loadOutstandingBillsChecklist(routeId, delId) {
        const container = document.getElementById('adjDaBillsContainer');
        if (!container) return;

        container.innerHTML = '<p style="text-align:center; color:#888;">Loading credit bills... </p>';
        const isReadOnly = (currentRouteStatus === 'Completed' || currentRouteStatus === 'Finalized');
        let selectedInvoices = [];

        const renderBills = (selectedIds) => {
            fetchSecure('<?= APP_URL ?>/RepTracking/api_get_outstanding_bills/' + routeId)
                .then(res => res.json())
                .then(data => {
                    if (data.status !== 'success' || !data.bills || data.bills.length === 0) {
                        container.innerHTML = '<p style="text-align:center; color:#888; margin:10px 0;">No outstanding credit bills found in these territories.</p>';
                    } else {
                        let html = '<div style="display:flex; flex-direction:column; gap:8px;">';
                        data.bills.forEach(cust => {
                            cust.bills.forEach(b => {
                                let amtFormatted = parseFloat(b.true_grand_total).toLocaleString('en-IN', {minimumFractionDigits:2});
                                const isChecked = selectedIds.includes(parseInt(b.id)) ? 'checked' : '';
                                html += `
                                    <label style="display:flex; align-items:flex-start; gap:10px; cursor:pointer; padding:6px; border-bottom:1px solid #f0f0f0;">
                                        <input type="checkbox" class="adj-da-bill-checkbox" value="${b.id}" style="width:16px; height:16px;" ${isReadOnly ? 'disabled' : ''} ${isChecked}>
                                        <div style="flex:1;">
                                            <div style="font-weight:bold;">${b.invoice_number}</div>
                                            <div style="font-size:11px; color:#666;">Customer: <strong>${cust.customer_name}</strong> | Date: ${b.invoice_date}</div>
                                        </div>
                                        <div style="font-weight:bold; font-family:monospace; color:#c62828;">Rs ${amtFormatted}</div>
                                    </label>
                                `;
                            });
                        });
                        html += '</div>';
                        container.innerHTML = html;
                    }
                });
        };

        if (delId && delId !== '0' && delId !== '') {
            fetchSecure('<?= APP_URL ?>/RepTracking/api_get_delivery_details/' + delId)
                .then(res => res.json())
                .then(dData => {
                    if (dData.delivery && dData.delivery.selected_credit_invoices) {
                        try {
                            selectedInvoices = JSON.parse(dData.delivery.selected_credit_invoices).map(id => parseInt(id));
                        } catch (e) {}
                    }
                    renderBills(selectedInvoices);
                });
        } else {
            renderBills([]);
        }
    }

    function loadAdjustmentsStage(routeId) {
        const tbody = document.getElementById('adjustmentsInvoicesTbody');
        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;">Loading Sales Orders... </td></tr>';

        const isReadOnly = (currentRouteStatus === 'Completed' || currentRouteStatus === 'Finalized');

        // Hide/Show operational buttons header in Tab 3
        const opsHeader = document.querySelector("#tabpanel-3 button")?.parentElement;
        if (opsHeader) {
            opsHeader.style.display = isReadOnly ? 'none' : 'flex';
        }

        // Load outstanding bills and pre-check already selected ones if delivery is already arranged
        const rdata = document.getElementById('route_data_' + routeId);
        const delId = rdata ? rdata.getAttribute('data-delivery-id') : null;
        loadOutstandingBillsChecklist(routeId, delId);

        fetchSecure('<?= APP_URL ?>/RepTracking/api_get_route_details/' + routeId)
            .then(res => res.json())
            .then(data => {
                const bills = data.bills || [];
                tbody.innerHTML = '';
                if (bills.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:20px; color:#888;">No sales orders attached to this route.</td></tr>';
                } else {
                    bills.forEach(bill => {
                        let time = new Date(bill.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                        
                        let dropdownHtml = '';
                        if (!isReadOnly) {
                            dropdownHtml += `
                                <button class="dots-dropdown-item" onclick="event.stopPropagation(); editSalesOrder(${bill.id})">
                                    <i class="ph ph-pencil"></i> Edit
                                </button>
                                <button class="dots-dropdown-item danger" onclick="event.stopPropagation(); confirmDeleteSalesOrder(${bill.id}, '${bill.invoice_number}')">
                                    <i class="ph ph-trash"></i> Delete
                                </button>
                                <button class="dots-dropdown-item" onclick="event.stopPropagation(); detachInvoice(${bill.id})">
                                    <i class="ph ph-link-break"></i> Remove
                                </button>
                                <button class="dots-dropdown-item" onclick="event.stopPropagation(); openMoveInvoiceModal(${bill.id}, '${bill.invoice_number}')">
                                    <i class="ph ph-arrow-square-out"></i> Move
                                </button>
                                <div class="dots-dropdown-divider"></div>
                            `;
                        }
                        dropdownHtml += `
                            <button class="dots-dropdown-item" onclick="event.stopPropagation(); openInvoiceSlider(${bill.id})">
                                <i class="ph ph-eye"></i> View Invoice
                            </button>
                            <button class="dots-dropdown-item" onclick="event.stopPropagation(); printInvoice(${bill.id})">
                                <i class="ph ph-printer"></i> Print
                            </button>
                            <button class="dots-dropdown-item" data-customer="${bill.customer_name.replace(/"/g, '&quot;')}" onclick="event.stopPropagation(); viewCustomerProfile(this.getAttribute('data-customer'))">
                                <i class="ph ph-user"></i> View Customer
                            </button>
                            <button class="dots-dropdown-item" onclick="event.stopPropagation(); downloadInvoicePdf(${bill.id})">
                                <i class="ph ph-file-pdf"></i> Download PDF
                            </button>
                            <button class="dots-dropdown-item" onclick="event.stopPropagation(); exportInvoiceExcel(${bill.id})">
                                <i class="ph ph-file-xls"></i> Export Excel
                            </button>
                        `;

                        let actionBtn = `
                            <div class="dots-menu-container">
                                <button class="dots-btn" onclick="toggleDotsMenu(event, ${bill.id})">
                                    <i class="ph-bold ph-dots-three-vertical"></i>
                                </button>
                                <div class="dots-dropdown" id="dots-dropdown-${bill.id}">
                                    ${dropdownHtml}
                                </div>
                            </div>
                        `;

                        tbody.innerHTML += `
                            <tr>
                                <td style="font-weight:bold; color:var(--primary); cursor:pointer;" onclick="openInvoiceSlider(${bill.id})">${bill.invoice_number}</td>
                                <td>${time}</td>
                                <td><strong>${bill.customer_name}</strong></td>
                                <td style="text-align:right; font-family:monospace; font-weight:bold;">${parseFloat(bill.true_grand_total).toLocaleString('en-IN', {minimumFractionDigits:2})}</td>
                                <td style="text-align:center;">${actionBtn}</td>
                            </tr>
                        `;
                    });
                }
            });
    }

    function detachInvoice(invoiceId) {
        if (!confirm("Are you sure you want to remove/detach this Sales Order from this route?")) return;
        fetchSecure('<?= APP_URL ?>/RepTracking/api_detach_invoice', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ invoice_id: invoiceId })
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                alert("Sales Order successfully detached from route!");
                onRouteDataChanged();
                loadAdjustmentsStage(currentRouteId);
            } else {
                alert("Error: " + data.message);
            }
        });
    }

    function submitAdjustmentsLogisticsArrange() {
        const date = document.getElementById('adjDaDate').value;
        const vehicle = document.getElementById('adjDaVehicle').value;
        const driver = document.getElementById('adjDaDriver').value;
        const partner = document.getElementById('adjDaPartner').value;

        if (!vehicle) { alert("Please select a Vehicle Number."); return; }
        if (!driver) { alert("Please select a Driver Name."); return; }

        const checkedBills = [];
        document.querySelectorAll('.adj-da-bill-checkbox:checked').forEach(cb => {
            checkedBills.push(parseInt(cb.value));
        });

        const payload = {
            rep_route_id: currentRouteId,
            secondary_rep_route_id: null,
            delivery_date: date,
            vehicle_number: vehicle,
            driver_name: driver,
            partner_name: partner,
            selected_credit_invoices: checkedBills
        };

        fetchSecure('<?= APP_URL ?>/RepTracking/arrange', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                alert("Delivery arranged successfully!");
                const rdata = document.getElementById('route_data_' + currentRouteId);
                if (rdata) {
                    rdata.setAttribute('data-delivery-id', data.delivery_id);
                }
                onRouteDataChanged();
                loadDispatchStage(currentRouteId);
            } else {
                alert("Error: " + data.message);
            }
        });
    }

    function loadLoadingStage(routeId) {
        const box = document.getElementById('loadingBox');
        if (!box) return;
        box.innerHTML = 'Loading loading items checklist... ';

        fetchSecure('<?= APP_URL ?>/RepTracking/api_get_route_variances/' + routeId)
            .then(res => res.json())
            .then(data => {
                if (data.status !== 'success') {
                    box.innerHTML = '<p style="color:red; text-align:center; padding:10px;">Error loading data.</p>';
                    return;
                }

                const deliveries = data.deliveries || [];
                const loadingItems = data.loading_items || [];

                // Verification has started if we have at least one delivery AND its verified_items count is > 0.
                const hasVerificationStarted = (deliveries.length > 0 && deliveries[0].verified_items > 0);

                let listHtml = '';
                let printButtonsHtml = `
                    <div style="margin-bottom: 15px; text-align: right; display: flex; justify-content: flex-end; gap: 10px;">
                        <button class="btn btn-primary" onclick="printLoadingSheet('final')" style="padding:8px 16px; background:#3f51b5; border:none; color:#fff; border-radius:4px; font-weight:bold; font-size:12px; cursor:pointer; display:inline-flex; align-items:center; gap:6px;"><i class="ph ph-printer"></i> Print Loading Sheet</button>
                        <button class="btn btn-secondary" onclick="printLoadingSheet('summary')" style="padding:8px 16px; background:#673ab7; border:none; color:#fff; border-radius:4px; font-weight:bold; font-size:12px; cursor:pointer; display:inline-flex; align-items:center; gap:6px;"><i class="ph ph-printer"></i> Print Loading Summary</button>
                    </div>
                `;

                if (!hasVerificationStarted) {
                    if (loadingItems.length === 0) {
                        listHtml = '<tr><td colspan="2" style="text-align:center; padding:15px; color:#64748b;">No products required for loading on this route.</td></tr>';
                    } else {
                        loadingItems.forEach(item => {
                            listHtml += `
                                <tr style="border-bottom:1px solid #e2e8f0;">
                                    <td style="padding:10px; font-weight:600; color:#1e293b;">${item.item_name}</td>
                                    <td style="padding:10px; text-align:center; font-weight:bold; font-family:monospace; font-size:13px;">${item.total_qty}</td>
                                </tr>
                            `;
                        });
                    }

                    box.innerHTML = `
                        ${printButtonsHtml}
                        <div style="background:#fff7ed; border:1px solid #ffedd5; padding:12px; border-radius:6px; margin-bottom:15px; color:#c2410c; font-size:12px; font-weight:600; display:flex; align-items:center; gap:6px;">
                            <i class="ph ph-info"></i> Verification has not started yet. Showing original required loading quantities.
                        </div>
                        <table class="data-table">
                            <thead>
                                <tr style="background:#f8fafc;">
                                    <th style="padding:10px; text-align:left;">Product Name</th>
                                    <th style="padding:10px; text-align:center; width:150px;">Required Qty</th>
                                </tr>
                            </thead>
                            <tbody>${listHtml}</tbody>
                        </table>
                    `;
                } else {
                    const del = deliveries[0];
                    if (del.items.length === 0) {
                        listHtml = '<tr><td colspan="4" style="text-align:center; padding:15px; color:#64748b;">No verification records found.</td></tr>';
                    } else {
                        del.items.forEach(item => {
                            const req = parseFloat(item.required_qty);
                            const loaded = item.final_loaded_qty !== null ? parseFloat(item.final_loaded_qty) : parseFloat(item.pre_loaded_qty);
                            const diff = loaded - req;

                            let rowBg = '';
                            let diffIndicator = '';

                            if (Math.abs(diff) < 0.01) {
                                rowBg = 'background-color: #d1fae5; color: #065f46;';
                                diffIndicator = '<i class="ph ph-check-circle" style="color: #16a34a; font-size: 14px;"></i> Match';
                            } else if (diff < 0) {
                                rowBg = 'background-color: #fee2e2; color: #991b1b;';
                                diffIndicator = `Shortage (${diff.toFixed(1)})`;
                            } else {
                                rowBg = 'background-color: #fff3e0; color: #e65100;';
                                diffIndicator = `Overage (+${diff.toFixed(1)})`;
                            }

                            let nameHtml = `<div style="font-weight:600;">${item.item_name}</div>`;
                            if (item.replaced_by_name) {
                                nameHtml += `<div style="font-size:11px; color:#673ab7; font-weight:bold; margin-top:2px;">→ Replaced By ${item.replaced_by_name} (Qty: ${item.replacement_qty})</div>`;
                            } else if (item.replaces_name) {
                                nameHtml += `<div style="font-size:11px; color:#16a34a; font-weight:bold; margin-top:2px;"><i class="ph ph-star" style="color: #16a34a;"></i> Replacement for ${item.replaces_name}</div>`;
                            }

                            listHtml += `
                                <tr style="border-bottom:1px solid #e2e8f0; ${rowBg}">
                                    <td style="padding:10px;">${nameHtml}</td>
                                    <td style="padding:10px; text-align:center; font-weight:bold; font-family:monospace; font-size:13px;">${req}</td>
                                    <td style="padding:10px; text-align:center; font-weight:bold; font-family:monospace; font-size:13px;">${loaded}</td>
                                    <td style="padding:10px; text-align:center; font-weight:bold; font-size:12px;">${diffIndicator}</td>
                                </tr>
                            `;
                        });
                    }

                    box.innerHTML = `
                        ${printButtonsHtml}
                        <div style="display:flex; justify-content:space-between; align-items:center; background:#f1f5f9; padding:12px; border-radius:6px; margin-bottom:15px; font-size:12px;">
                            <div>Loading Sheet Verification ID: <strong>#${del.delivery_id}</strong></div>
                            <div>Verification Status: <strong>${del.verified_items} / ${del.total_items} verified</strong></div>
                        </div>
                        <table class="data-table">
                            <thead>
                                <tr style="background:#f8fafc;">
                                    <th style="padding:10px; text-align:left;">Product Name</th>
                                    <th style="padding:10px; text-align:center; width:120px;">Required Qty</th>
                                    <th style="padding:10px; text-align:center; width:120px;">Verified Qty</th>
                                    <th style="padding:10px; text-align:center; width:150px;">Status / Variance</th>
                                </tr>
                            </thead>
                            <tbody>${listHtml}</tbody>
                        </table>
                    `;
                }
            });
    }

    let currentVarianceState = {};
    let currentSubstitutions = [];

    function loadVarianceAdjustmentStage(routeId) {
        const box = document.getElementById('varianceAuditBox');
        if (!box) return;
        box.innerHTML = '<div style="padding:20px; text-align:center;">Loading shortages & overages... </div>';
        currentVarianceState = {};
        currentSubstitutions = [];

        fetchSecure('<?= APP_URL ?>/RepTracking/api_get_route_variances/' + routeId)
            .then(res => res.json())
            .then(data => {
                if (data.status !== 'success' || !data.deliveries || data.deliveries.length === 0) {
                    box.innerHTML = '<p style="color:red; padding:10px;">No variance records found.</p>';
                    return;
                }
                const del = data.deliveries[0];
                const items = del.items || [];
                currentSubstitutions = data.substitutions || [];
                
                if (items.length === 0) {
                    box.innerHTML = '<p style="color:green; padding:10px; font-weight:bold;">No products picked on this route.</p>';
                    return;
                }

                let fetchPromises = [];
                items.forEach(item => {
                    const itemId = item.item_id;
                    currentVarianceState[itemId] = {
                        item_id: itemId,
                        item_name: item.item_name,
                        required_qty: parseFloat(item.required_qty),
                        pre_loaded_qty: parseFloat(item.pre_loaded_qty),
                        final_loaded_qty: item.final_loaded_qty !== null ? parseFloat(item.final_loaded_qty) : parseFloat(item.required_qty),
                        variance: parseFloat(item.variance),
                        invoices: []
                    };

                    if (parseFloat(item.variance) !== 0) {
                        const p = fetchSecure('<?= APP_URL ?>/RepTracking/api_get_product_invoices?route_id=' + routeId + '&item_id=' + itemId)
                            .then(res => res.json())
                            .then(invData => {
                                if (invData.status === 'success') {
                                    currentVarianceState[itemId].invoices = invData.invoices.map(inv => ({
                                        invoice_id: parseInt(inv.invoice_id),
                                        invoice_number: inv.invoice_number,
                                        customer_name: inv.customer_name,
                                        original_qty: parseFloat(inv.original_qty !== undefined ? inv.original_qty : inv.quantity),
                                        quantity: parseFloat(inv.quantity),
                                        unit_price: parseFloat(inv.unit_price),
                                        remove_completely: 0
                                    }));
                                }
                            });
                        fetchPromises.push(p);
                    }
                });

                Promise.all(fetchPromises).then(() => {
                    renderVarianceReconciliation();
                });
            });
    }

    function renderVarianceReconciliation() {
        const box = document.getElementById('varianceAuditBox');
        if (!box) return;
        let html = '';

        let totalShortages = 0;
        let totalOverages = 0;
        let hasUnbalanced = false;

        let tableRows = '';
        const isReadOnly = (currentRouteStatus === 'Completed' || currentRouteStatus === 'Finalized');

        Object.values(currentVarianceState).forEach(item => {
            const variance = item.variance;
            if (variance < 0) totalShortages += Math.abs(variance);
            if (variance > 0) totalOverages += variance;

            let varColor = '#2e7d32';
            let varText = 'Match (0)';
            if (variance < 0) {
                varColor = '#c62828';
                varText = `${variance} (Shortage)`;
            } else if (variance > 0) {
                varColor = '#ef6c00';
                varText = `+${variance} (Overage)`;
            }

            let allocatedSum = 0;
            if (variance === 0) {
                allocatedSum = item.final_loaded_qty;
            } else {
                item.invoices.forEach(inv => {
                    allocatedSum += inv.quantity;
                });
            }

            const isItemBalanced = (item.invoices.length === 0 || Math.abs(allocatedSum - item.final_loaded_qty) < 0.01);
            if (!isItemBalanced) {
                hasUnbalanced = true;
            }

            const statusBadge = isItemBalanced 
                ? `<span style="background:#d1fae5; color:#065f46; padding:3px 8px; border-radius:12px; font-size:11px; font-weight:bold; display:inline-flex; align-items:center; gap:4px;"><i class="ph ph-check-circle" style="font-size:13px;"></i> Balanced</span>`
                : `<span style="background:#fee2e2; color:#991b1b; padding:3px 8px; border-radius:12px; font-size:11px; font-weight:bold; display:inline-flex; align-items:center; gap:4px;"><i class="ph ph-warning" style="font-size:13px;"></i> Unbalanced</span>`;

            tableRows += `
                <tr style="border-bottom:1px solid #e2e8f0;">
                    <td style="padding:10px; font-weight:bold; color:#1e293b;">${item.item_name}</td>
                    <td style="padding:10px; text-align:center; font-weight:bold; font-family:monospace;">${item.required_qty}</td>
                    <td style="padding:10px; text-align:center; font-family:monospace;">${item.pre_loaded_qty}</td>
                    <td style="padding:10px; text-align:center; font-weight:bold; font-family:monospace; background:#f8fafc;">${item.final_loaded_qty}</td>
                    <td style="padding:10px; text-align:center; font-weight:bold; color:${varColor}; font-family:monospace;">${varText}</td>
                    <td style="padding:10px; text-align:center;">${statusBadge}</td>
                </tr>
            `;
        });

        // Substitutions Panel
        let substitutionHtml = '';
        if (currentSubstitutions.length > 0) {
            let subRows = '';
            currentSubstitutions.forEach(sub => {
                let actionPart = '';
                if (sub.status === 'Pending Bill Update') {
                    actionPart = `
                        <div style="display:flex; flex-direction:column; gap:8px; align-items:flex-end;">
                            <div>
                                <label style="font-size:11px; font-weight:bold; color:#475569; margin-right:5px;">Pricing Decision:</label>
                                <select id="pricing_choice_${sub.id}" style="padding:4px 8px; font-size:11px; border-radius:4px; border:1px solid #cbd5e1; background:#fff;">
                                    <option value="original">Use Original Product Price</option>
                                    <option value="replacement" selected>Use Replacement Product Price</option>
                                </select>
                            </div>
                            <button onclick="applyProductSubstitution(${sub.id})" style="padding:6px 12px; background:#673ab7; color:#fff; border:none; border-radius:4px; font-size:11px; font-weight:bold; cursor:pointer;">
                                Apply Substitution To Bills
                            </button>
                        </div>
                    `;
                } else {
                    actionPart = `
                        <div style="text-align:right; font-size:11px; color:#64748b; line-height:1.4;">
                            Applied by: <strong>${sub.creator_name || 'System'}</strong><br>
                            Date: <strong>${sub.applied_at}</strong><br>
                            Original Bill Value: <strong>Rs. ${parseFloat(sub.original_bill_value).toFixed(2)}</strong><br>
                            Updated Bill Value: <strong>Rs. ${parseFloat(sub.updated_bill_value).toFixed(2)}</strong>
                        </div>
                    `;
                }

                subRows += `
                    <div style="display:flex; justify-content:space-between; align-items:center; padding:12px; border-bottom:1px solid #e2e8f0; gap:15px;">
                        <div style="line-height:1.5; font-size:12px;">
                            Original Product: <strong style="color:#ef6c00;">${sub.original_item_name}</strong> (Required Qty: <strong>${parseFloat(sub.required_qty)}</strong>)<br>
                            Replacement Product: <strong style="color:#2e7d32;">${sub.replacement_item_name}</strong> (Loaded Qty: <strong>${parseFloat(sub.loaded_qty)}</strong>)<br>
                            Status: <span style="font-weight:bold; color:${sub.status === 'Applied' ? '#16a34a' : '#e65100'};">${sub.status}</span>
                        </div>
                        ${actionPart}
                    </div>
                `;
            });

            substitutionHtml = `
                <h5 style="margin:0 0 10px 0; font-size:13px; color:#475569; text-transform:uppercase; font-weight:bold; display:flex; align-items:center; gap:6px;"><i class="ph ph-swap"></i> Product Substitutions</h5>
                <div style="background:#fff; border:1px solid #cbd5e1; border-radius:8px; padding:10px; margin-bottom:25px;">
                    ${subRows}
                </div>
            `;
        }

        let reconciliationPanels = '';
        let hasAnyVariance = false;

        Object.values(currentVarianceState).forEach(item => {
            if (item.variance === 0) return;
            hasAnyVariance = true;

            let subBadge = '';
            const subInfo = currentSubstitutions.find(s => parseInt(s.replacement_item_id) === parseInt(item.item_id) || parseInt(s.original_item_id) === parseInt(item.item_id));
            if (subInfo) {
                if (parseInt(subInfo.replacement_item_id) === parseInt(item.item_id)) {
                    subBadge = `<span style="display:inline-flex; align-items:center; gap:4px; margin-top:4px; padding:2px 6px; background:#eff6ff; color:#1d4ed8; border-radius:4px; font-size:10px; font-weight:bold; margin-bottom:5px;"><i class="ph ph-swap"></i> Replaced "${subInfo.original_item_name}"</span>`;
                } else {
                    subBadge = `<span style="display:inline-flex; align-items:center; gap:4px; margin-top:4px; padding:2px 6px; background:#fef2f2; color:#b91c1c; border-radius:4px; font-size:10px; font-weight:bold; margin-bottom:5px;"><i class="ph ph-swap"></i> Replaced by "${subInfo.replacement_item_name}"</span>`;
                }
            }

            let invoiceRows = '';
            let currentTotal = 0;

            item.invoices.forEach((inv, index) => {
                currentTotal += inv.quantity;

                 let actionSelect = '';
                 if (inv.quantity === 0) {
                     actionSelect = `
                         <div style="margin-top:5px; color:#dc2626; font-size:11px; font-weight:bold; display:flex; align-items:center; gap:4px;">
                             <i class="ph ph-warning"></i> Product will be removed from invoice
                         </div>
                     `;
                 }

                invoiceRows += `
                    <div style="display:grid; grid-template-columns:1.5fr 1fr 1fr 1.2fr; gap:10px; align-items:center; padding:8px 0; border-bottom:1px solid #f1f5f9;">
                        <div style="font-size:12px; font-weight:500;">
                            <i class="ph ph-file-text"></i> <strong>${inv.invoice_number}</strong><br>
                            <span style="font-size:10px; color:#64748b;">${inv.customer_name}</span>
                            ${actionSelect}
                        </div>
                        <div style="text-align:center; font-family:monospace;">
                            Original: <strong>${inv.original_qty}</strong>
                        </div>
                        <div style="text-align:center;">
                            <input type="number" step="1" min="0" value="${inv.quantity}" 
                                   oninput="updateInvoiceAllocation(${item.item_id}, ${inv.invoice_id}, this.value)" 
                                   ${isReadOnly ? 'disabled' : ''}
                                   style="width:70px; padding:4px 8px; border:1px solid #cbd5e1; border-radius:4px; text-align:center; font-weight:bold; font-family:monospace;" />
                        </div>
                        <div style="text-align:right;">
                            <span style="font-size:11px; color:#64748b;">Rs. ${(inv.quantity * inv.unit_price).toFixed(2)}</span>
                        </div>
                    </div>
                `;
            });

            if (item.invoices.length === 0) {
                invoiceRows = `<p style="color:#64748b; font-size:12px; margin:10px 0; font-style:italic;">No invoices contain this product on this route.</p>`;
            }

            const unbalancedVal = item.final_loaded_qty - currentTotal;
            const panelStatus = (Math.abs(unbalancedVal) < 0.01)
                ? `<span style="color:#16a34a; font-weight:bold; display:inline-flex; align-items:center; gap:4px;"><i class="ph ph-check-circle" style="font-size:14px;"></i> Balanced</span>`
                : `<span style="color:#dc2626; font-weight:bold; display:inline-flex; align-items:center; gap:4px;"><i class="ph ph-warning" style="font-size:14px;"></i> Unbalanced (${unbalancedVal > 0 ? '+' : ''}${unbalancedVal.toFixed(1)} pcs)</span>`;

            let autoDistBtn = isReadOnly ? '' : `
                <button onclick="autoDistributeVariance(${item.item_id})" style="padding:4px 10px; background:#3b82f6; color:#fff; border:none; border-radius:4px; font-size:11px; font-weight:bold; cursor:pointer; margin-right:10px; display:inline-flex; align-items:center; gap:4px;"><i class="ph ph-lightning"></i> Auto-Distribute</button>
            `;

            reconciliationPanels += `
                <div style="border:1px solid #cbd5e1; border-radius:8px; padding:15px; margin-bottom:20px; background:#fff; box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; border-bottom:1px solid #e2e8f0; padding-bottom:8px;">
                        <div>
                            <strong style="font-size:13px; color:#0f172a;"><i class="ph ph-wrench"></i> Reconcile: ${item.item_name}</strong><br>
                            ${subBadge ? subBadge + '<br>' : ''}
                            <span style="font-size:11px; color:#475569;">Variance: <strong>${item.variance > 0 ? '+' : ''}${item.variance}</strong> | Required: <strong>${item.required_qty}</strong> | Final Loaded: <strong>${item.final_loaded_qty}</strong></span>
                        </div>
                        <div style="text-align:right;">
                            ${autoDistBtn}
                            ${panelStatus}
                        </div>
                    </div>
                    <div>
                        ${invoiceRows}
                    </div>
                    <div style="display:flex; justify-content:space-between; margin-top:10px; font-size:11px; color:#475569; background:#f8fafc; padding:8px; border-radius:4px;">
                        <span>Allocated: <strong style="font-family:monospace; font-size:12px;">${currentTotal.toFixed(1)}</strong></span>
                        <span>Remaining to Allocate: <strong style="font-family:monospace; font-size:12px; color:${unbalancedVal !== 0 ? '#dc2626' : '#16a34a'};">${unbalancedVal.toFixed(1)}</strong></span>
                    </div>
                </div>
            `;
        });

        if (!hasAnyVariance) {
            reconciliationPanels = `
                <div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px; padding:20px; text-align:center; color:#166534; font-weight:bold; margin-bottom:20px; display:flex; align-items:center; justify-content:center; gap:8px;">
                    <i class="ph ph-check-circle" style="font-size:16px;"></i> All items are fully balanced! No billing adjustments required.
                </div>
            `;
        }

        // --- CONSTRUCT THE HTML: Reconciliation Engine at the Top, Table and totals at the bottom ---
        
        // 1. Bill Reconciliation Engine & Submit/Approve Button
        html += `
            <h5 style="margin:0 0 10px 0; font-size:13px; color:#475569; text-transform:uppercase; font-weight:bold; display:flex; align-items:center; gap:6px;"><i class="ph ph-scales"></i> Bill Reconciliation Engine</h5>
            ${reconciliationPanels}
        `;

        if (!isReadOnly) {
            html += `
                <div style="text-align:right; margin-top:10px; margin-bottom:25px;">
                    <button id="btnSubmitVarianceAdjustments" onclick="submitVarianceAdjustments()" 
                            ${hasUnbalanced ? 'disabled' : ''}
                            style="padding:10px 20px; background:#2e7d32; color:#fff; border:none; border-radius:6px; font-weight:bold; font-size:13px; cursor:${hasUnbalanced ? 'not-allowed' : 'pointer'}; opacity:${hasUnbalanced ? 0.5 : 1}; display:inline-flex; align-items:center; gap:6px;">
                        <i class="ph ph-scales"></i> Approve & Apply Billing Adjustments
                    </button>
                </div>
            `;
        }

        // 2. Shortages / Overages Total Cards
        html += `
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-bottom:20px;">
                <div style="background:#fee2e2; border:1px solid #fca5a5; border-radius:6px; padding:12px; text-align:center; color:#991b1b;">
                    <span>Shortages to Reconcile</span><br><strong style="font-size:16px;">${totalShortages} pcs</strong>
                </div>
                <div style="background:#fff3e0; border:1px solid #ffe0b2; border-radius:6px; padding:12px; text-align:center; color:#e65100;">
                    <span>Overages to Reconcile</span><br><strong style="font-size:16px;">${totalOverages} pcs</strong>
                </div>
            </div>
            
            ${substitutionHtml}
            
            <h5 style="margin:0 0 10px 0; font-size:13px; color:#475569; text-transform:uppercase; font-weight:bold; display:flex; align-items:center; gap:6px;"><i class="ph ph-package"></i> Product Loading Variances</h5>
            <table class="data-table" style="margin-bottom:25px; border:1px solid #e2e8f0; border-radius:6px; overflow:hidden;">
                <thead>
                    <tr style="background:#f1f5f9;">
                        <th style="padding:10px; text-align:left;">Product Name</th>
                        <th style="padding:10px; text-align:center;">Required</th>
                        <th style="padding:10px; text-align:center;">Pre-Loaded</th>
                        <th style="padding:10px; text-align:center; background:#e2e8f0;">Final Loaded</th>
                        <th style="padding:10px; text-align:center;">Variance</th>
                        <th style="padding:10px; text-align:center;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    ${tableRows}
                </tbody>
            </table>
        `;

        box.innerHTML = html;
    }

    function updateRemoveCompletelyChoice(itemId, invoiceId, value) {
        const item = currentVarianceState[itemId];
        if (item) {
            const inv = item.invoices.find(i => i.invoice_id === invoiceId);
            if (inv) {
                inv.remove_completely = parseInt(value);
            }
        }
        renderVarianceReconciliation();
    }

    function applyProductSubstitution(subId) {
        const choiceSelect = document.getElementById('pricing_choice_' + subId);
        const choice = choiceSelect ? choiceSelect.value : 'replacement';

        if (!confirm('Are you sure you want to apply this product substitution to the bills? This will modify the invoice items list, adjust stock, and update the pricing.')) {
            return;
        }

        fetchSecure('<?= APP_URL ?>/RepTracking/api_apply_substitution', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                substitution_id: subId,
                pricing_choice: choice
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                alert(data.message);
                onRouteDataChanged();
                switchRouteTab(5);
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(err => {
            console.error(err);
            alert('An unexpected error occurred.');
        });
    }

    function updateInvoiceAllocation(itemId, invoiceId, value) {
        let qty = parseFloat(value);
        if (isNaN(qty) || qty < 0) {
            qty = 0;
        }
        
        const item = currentVarianceState[itemId];
        if (item) {
            const inv = item.invoices.find(i => i.invoice_id === invoiceId);
            if (inv) {
                inv.quantity = qty;
                inv.remove_completely = (qty === 0 ? 1 : 0);
            }
        }
        renderVarianceReconciliation();
    }

    function autoDistributeVariance(itemId) {
        const item = currentVarianceState[itemId];
        if (!item || item.invoices.length === 0) return;

        let targetTotal = item.final_loaded_qty;
        let originalTotal = item.required_qty;
        let diff = targetTotal - originalTotal;

        if (diff === 0) {
            item.invoices.forEach(inv => {
                inv.quantity = inv.original_qty;
                inv.remove_completely = (inv.quantity === 0 ? 1 : 0);
            });
        } else if (diff < 0) {
            let shortageToDeduct = Math.abs(diff);
            item.invoices.forEach(inv => {
                if (shortageToDeduct <= 0) {
                    inv.remove_completely = (inv.quantity === 0 ? 1 : 0);
                    return;
                }
                if (inv.quantity >= shortageToDeduct) {
                    inv.quantity -= shortageToDeduct;
                    shortageToDeduct = 0;
                } else {
                    shortageToDeduct -= inv.quantity;
                    inv.quantity = 0;
                }
                inv.remove_completely = (inv.quantity === 0 ? 1 : 0);
            });
        } else {
            item.invoices[0].quantity += diff;
            item.invoices[0].remove_completely = (item.invoices[0].quantity === 0 ? 1 : 0);
            // Ensure other invoices also have correct remove_completely
            for (let i = 1; i < item.invoices.length; i++) {
                item.invoices[i].remove_completely = (item.invoices[i].quantity === 0 ? 1 : 0);
            }
        }

        renderVarianceReconciliation();
    }

    function submitVarianceAdjustments() {
        if (!currentRouteId) return;

        const adjustments = [];
        Object.values(currentVarianceState).forEach(item => {
            if (item.variance === 0) return;
            
            const invoiceAdjustments = item.invoices.map(inv => ({
                invoice_id: inv.invoice_id,
                new_qty: inv.quantity,
                remove_completely: inv.remove_completely ? 1 : 0
            }));

            adjustments.push({
                item_id: item.item_id,
                invoice_adjustments: invoiceAdjustments
            });
        });

        if (adjustments.length > 0 && !confirm('Are you sure you want to approve these variance adjustments and update invoice billing? This action will modify invoice line quantities.')) {
            return;
        }

        const payload = {
            route_id: currentRouteId,
            adjustments: adjustments
        };

        fetchSecure('<?= APP_URL ?>/RepTracking/api_adjust_variance_billing', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                alert(data.message);
                onRouteDataChanged();
                switchRouteTab(5);
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(err => {
            console.error(err);
            alert('An unexpected error occurred during submission.');
        });
    }

    function printLoadingSheet(type) {
        if (!currentRouteId) return;
        window.open('<?= APP_URL ?>/RepTracking/print_loading/' + currentRouteId + '?type=' + type, '_blank');
    }

    function loadDispatchStage(routeId) {
        const formView = document.getElementById('adjDeliveryFormView');
        if (!formView) return;

        const isReadOnly = (currentRouteStatus === 'Completed' || currentRouteStatus === 'Finalized');

        // Form is always visible
        formView.style.display = 'block';

        const rdata = document.getElementById('route_data_' + routeId);
        const delId = rdata ? rdata.getAttribute('data-delivery-id') : null;

        const statusBanner = document.getElementById('adjDeliveryStatusBanner');
        const statusId = document.getElementById('adjDeliveryStatusId');

        // Load outstanding bills and pre-check
        loadOutstandingBillsChecklist(routeId, delId);

        if (!delId || delId === '0' || delId === '') {
            // Not arranged yet
            if (statusBanner) statusBanner.style.display = 'none';
            
            // Set default date if blank
            const dateInput = document.getElementById('adjDaDate');
            if (dateInput && !dateInput.value) {
                dateInput.value = new Date().toISOString().split('T')[0];
            }
            
            // Reset dropdowns
            document.getElementById('adjDaVehicle').value = '';
            document.getElementById('adjDaDriver').value = '';
            document.getElementById('adjDaPartner').value = '';
        } else {
            // Already arranged
            if (statusBanner) {
                statusBanner.style.display = 'flex';
                statusId.innerText = '#' + delId;
            }
            
            fetchSecure('<?= APP_URL ?>/RepTracking/api_get_delivery_details/' + delId)
                .then(res => res.json())
                .then(dData => {
                    if (dData.delivery) {
                        if (document.getElementById('adjDaDate')) document.getElementById('adjDaDate').value = dData.delivery.delivery_date || '';
                        if (document.getElementById('adjDaVehicle')) document.getElementById('adjDaVehicle').value = dData.delivery.vehicle_number || '';
                        if (document.getElementById('adjDaDriver')) document.getElementById('adjDaDriver').value = dData.delivery.driver_name || '';
                        if (document.getElementById('adjDaPartner')) document.getElementById('adjDaPartner').value = dData.delivery.partner_name || '';
                    }
                });
        }
    }

    function loadDeliveryLiveStage(routeId) {
        const summaryCards = document.getElementById('deliveryTabSummaryCards');
        const tbody = document.getElementById('deliveryTabInvoicesTbody');
        if (!summaryCards || !tbody) return;

        summaryCards.innerHTML = '<div style="grid-column: span 4; text-align:center; padding:10px;">Loading performance summary... </div>';
        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;">Loading customer dispatches... </td></tr>';

        const rdata = document.getElementById('route_data_' + routeId);
        const delId = rdata ? rdata.getAttribute('data-delivery-id') : null;

        if (!delId || delId === '0' || delId === '') {
            summaryCards.innerHTML = '<div style="grid-column: span 4; text-align:center; padding:10px; color:#888;">Delivery has not been arranged/dispatched yet.</div>';
            tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; color:#888;">No dispatch data available.</td></tr>';
            return;
        }

        fetchSecure('<?= APP_URL ?>/RepTracking/api_get_delivery_details/' + delId)
            .then(res => res.json())
            .then(data => {
                if (data.status !== 'success' || !data.delivery) {
                    summaryCards.innerHTML = '<div style="grid-column: span 4; text-align:center; color:red;">Error loading delivery.</div>';
                    tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; color:red;">Failed to load details.</td></tr>';
                    return;
                }

                const invoices = data.invoices || [];
                const totalInvoices = invoices.length;
                const delivered = invoices.filter(inv => inv.delivery_status === 'Delivered').length;
                const pending = totalInvoices - delivered;
                const collections = parseFloat(data.balancing.total_payments || 0);

                summaryCards.innerHTML = `
                    <div style="background:#f0f9ff; border:1px solid #bae6fd; padding:12px; border-radius:6px; text-align:center;">
                        <span style="font-size:11px; color:#0369a1; font-weight:bold;">Total Invoices</span><br>
                        <strong style="font-size:15px; color:#0f172a;">${totalInvoices}</strong>
                    </div>
                    <div style="background:#f0fdf4; border:1px solid #bbf7d0; padding:12px; border-radius:6px; text-align:center;">
                        <span style="font-size:11px; color:#166534; font-weight:bold;">Delivered</span><br>
                        <strong style="font-size:15px; color:#166534;">${delivered}</strong>
                    </div>
                    <div style="background:#fff7ed; border:1px solid #ffedd5; padding:12px; border-radius:6px; text-align:center;">
                        <span style="font-size:11px; color:#c2410c; font-weight:bold;">Pending Visit</span><br>
                        <strong style="font-size:15px; color:#c2410c;">${pending}</strong>
                    </div>
                    <div style="background:#f0fdf4; border:1px solid #bbf7d0; padding:12px; border-radius:6px; text-align:center;">
                        <span style="font-size:11px; color:#166534; font-weight:bold;">Collected Amount</span><br>
                        <strong style="font-size:15px; color:#166534;">Rs ${collections.toLocaleString('en-US', {minimumFractionDigits: 2})}</strong>
                    </div>
                `;

                tbody.innerHTML = '';
                if (invoices.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; color:#888;">No invoices dispatched.</td></tr>';
                    return;
                }

                invoices.forEach(inv => {
                    let dColor = '#d05d00';
                    if (inv.delivery_status === 'Delivered') dColor = '#2e7d32';
                    else if (inv.delivery_status === 'Cancelled') dColor = '#ef4444';
                    else if (inv.delivery_status === 'Postponed') dColor = '#6b7280';

                    let pColor = inv.status === 'Paid' ? '#2e7d32' : '#d05d00';
                    
                    let actionHtml = '';
                    if (currentRouteStatus === 'Completed' || currentRouteStatus === 'Finalized') {
                        actionHtml = `<span style="color:#888; font-size:11px; font-weight:bold;">Closed</span>`;
                    } else {
                        actionHtml = `
                            <button onclick="openServerDeliveryProcessModal(${inv.id}, ${inv.customer_id}, '${inv.invoice_number}', '${inv.customer_name.replace(/'/g, "\\'")}', ${inv.true_grand_total})" 
                                    class="btn-premium primary" 
                                    style="padding:4px 8px; font-size:11px; display:inline-flex; align-items:center; gap:4px; font-weight:bold; cursor:pointer;">
                                <i class="ph ph-gear"></i> Process Visit
                            </button>
                        `;
                    }

                    let statusSelectHtml = '';
                    const isReadOnly = (currentRouteStatus === 'Completed' || currentRouteStatus === 'Finalized');
                    if (isReadOnly) {
                        statusSelectHtml = `<span style="color:${dColor}; font-weight:bold;">${inv.delivery_status || 'Pending'}</span>`;
                    } else {
                        statusSelectHtml = `
                            <select onchange="updateSingleInvoiceDeliveryStatus(${inv.id}, ${inv.customer_id}, this.value)" 
                                    style="padding: 4px 8px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 12px; font-weight: bold; color: ${dColor}; background: #fff; outline: none; cursor: pointer; transition: all 0.2s; box-shadow: var(--shadow-xs);">
                                <option value="Pending" ${inv.delivery_status === 'Pending' ? 'selected' : ''} style="color:#d05d00;">Pending</option>
                                <option value="Delivered" ${inv.delivery_status === 'Delivered' ? 'selected' : ''} style="color:#2e7d32;">Delivered</option>
                                <option value="Cancelled" ${inv.delivery_status === 'Cancelled' ? 'selected' : ''} style="color:#ef4444;">Cancelled</option>
                                <option value="Postponed" ${inv.delivery_status === 'Postponed' ? 'selected' : ''} style="color:#6b7280;">Postponed</option>
                            </select>
                        `;
                    }

                    tbody.innerHTML += `
                        <tr>
                            <td><strong>${inv.customer_name}</strong></td>
                            <td style="font-weight:bold; color:var(--primary);">${inv.invoice_number}</td>
                            <td style="text-align:right; font-family:monospace; font-weight:bold;">Rs ${parseFloat(inv.true_grand_total).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                            <td style="text-align:center;">${statusSelectHtml}</td>
                            <td style="text-align:center; color:${pColor}; font-weight:bold;">${inv.status}</td>
                            <td style="text-align:center;">${actionHtml}</td>
                        </tr>
                    `;
                });
            });
    }

    function openServerDeliveryProcessModal(invoiceId, customerId, invoiceNumber, customerName, grandTotal) {
        document.getElementById('sdpInvoiceId').value = invoiceId;
        document.getElementById('sdpCustomerId').value = customerId;
        document.getElementById('sdpCustomerName').innerText = customerName;
        document.getElementById('sdpInvoiceNumber').innerText = invoiceNumber;
        document.getElementById('sdpCashAmount').value = '0.00';
        document.getElementById('sdpBankAmount').value = '0.00';
        document.getElementById('sdpChequesContainer').innerHTML = '';
        document.getElementById('sdpItemsTbody').innerHTML = '<tr><td colspan="3" style="text-align:center;">Loading items... </td></tr>';
        
        document.getElementById('serverDeliveryProcessModal').style.display = 'flex';
        
        fetchSecure('<?= APP_URL ?>/RepTracking/api_get_invoice_for_delivery/' + invoiceId)
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    document.getElementById('sdpDeliveryStatus').value = data.invoice.delivery_status || 'Pending';
                    
                    const arrears = parseFloat(data.arrears || 0);
                    document.getElementById('sdpOutstandingArrears').innerText = 'Rs ' + arrears.toLocaleString('en-US', {minimumFractionDigits: 2});
                    
                    let tbody = document.getElementById('sdpItemsTbody');
                    tbody.innerHTML = '';
                    
                    if (!data.items || data.items.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="3" style="text-align:center; color:#888;">No items in this invoice.</td></tr>';
                    } else {
                        data.items.forEach(item => {
                            tbody.innerHTML += `
                                <tr data-item-id="${item.id}">
                                    <td><strong>${item.description}</strong></td>
                                    <td style="text-align:right; font-family:monospace;">${parseFloat(item.loaded_quantity)}</td>
                                    <td style="text-align:right;">
                                        <input type="number" step="any" min="0" max="${item.loaded_quantity}" class="sdp-delivered-qty" 
                                               value="${item.quantity}" style="width: 80px; text-align: right; padding: 4px 8px; border: 1px solid #cbd5e1; border-radius: 4px;" />
                                    </td>
                                </tr>
                            `;
                        });
                    }
                } else {
                    alert('Error: ' + data.message);
                    closeServerDeliveryProcessModal();
                }
            })
            .catch(err => {
                console.error(err);
                alert('Failed to load invoice items.');
                closeServerDeliveryProcessModal();
            });
    }

    function closeServerDeliveryProcessModal() {
        document.getElementById('serverDeliveryProcessModal').style.display = 'none';
    }

    function addSdpChequeRow() {
        const container = document.getElementById('sdpChequesContainer');
        const row = document.createElement('div');
        row.className = 'sdp-cheque-row';
        row.style.display = 'grid';
        row.style.gridTemplateColumns = '1.5fr 1fr 1.2fr 1fr 40px';
        row.style.gap = '10px';
        row.style.alignItems = 'center';
        row.style.background = '#f8fafc';
        row.style.padding = '10px';
        row.style.borderRadius = '6px';
        row.style.border = '1px solid #e2e8f0';
        
        row.innerHTML = `
            <input type="text" placeholder="Bank Name" class="sdp-ch-bank" style="padding:6px; border:1px solid #ccc; border-radius:4px; font-size:12px; width:100%;" required />
            <input type="text" placeholder="Cheque #" class="sdp-ch-number" style="padding:6px; border:1px solid #ccc; border-radius:4px; font-size:12px; width:100%;" required />
            <input type="date" class="sdp-ch-date" style="padding:6px; border:1px solid #ccc; border-radius:4px; font-size:12px; width:100%;" required />
            <input type="number" step="0.01" min="0" placeholder="Amount" class="sdp-ch-amount" style="padding:6px; border:1px solid #ccc; border-radius:4px; font-size:12px; width:100%;" required />
            <button type="button" onclick="this.closest('.sdp-cheque-row').remove()" style="background:none; border:none; color:#ef4444; font-size:18px; cursor:pointer; display:flex; align-items:center; justify-content:center;"><i class="ph ph-trash"></i></button>
        `;
        container.appendChild(row);
    }

    function submitServerDeliveryProcess() {
        const routeId = currentRouteId;
        const customerId = document.getElementById('sdpCustomerId').value;
        const invoiceId = document.getElementById('sdpInvoiceId').value;
        const deliveryStatus = document.getElementById('sdpDeliveryStatus').value;
        
        // 1. Gather invoice items updates
        const items = [];
        document.querySelectorAll('#sdpItemsTbody tr').forEach(row => {
            const itemId = row.getAttribute('data-item-id');
            const qtyInput = row.querySelector('.sdp-delivered-qty');
            if (itemId && qtyInput) {
                items.push({
                    invoice_item_id: parseInt(itemId),
                    delivered_qty: parseFloat(qtyInput.value) || 0
                });
            }
        });
        
        // 2. Gather payments & collections
        const cash = parseFloat(document.getElementById('sdpCashAmount').value) || 0;
        const bank = parseFloat(document.getElementById('sdpBankAmount').value) || 0;
        
        const cheques = [];
        let chequeValidationFailed = false;
        document.querySelectorAll('.sdp-cheque-row').forEach(row => {
            const bankName = row.querySelector('.sdp-ch-bank').value.trim();
            const chNum = row.querySelector('.sdp-ch-number').value.trim();
            const chDate = row.querySelector('.sdp-ch-date').value;
            const chAmt = parseFloat(row.querySelector('.sdp-ch-amount').value) || 0;
            
            if (!bankName || !chNum || !chDate || chAmt <= 0) {
                chequeValidationFailed = true;
            }
            
            cheques.push({
                bank: bankName,
                number: chNum,
                date: chDate,
                amount: chAmt
            });
        });
        
        if (chequeValidationFailed) {
            alert('Please fill out all fields in the added cheque rows with valid values.');
            return;
        }
        
        // Confirm action
        if (!confirm('Are you sure you want to process this visit and save changes? This will modify the delivery status, adjust quantities, and record payments.')) {
            return;
        }
        
        const payload = {
            route_id: parseInt(routeId),
            customer_id: parseInt(customerId),
            deliveries: [
                {
                    invoice_id: parseInt(invoiceId),
                    delivery_status: deliveryStatus,
                    items: items
                }
            ],
            collections: {
                cash: cash,
                bank: bank,
                cheques: cheques
            }
        };
        
        fetchSecure('<?= APP_URL ?>/RepTracking/api_process_delivery_visit', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                alert(data.message);
                closeServerDeliveryProcessModal();
                loadDeliveryLiveStage(routeId);
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(err => {
            console.error(err);
            alert('An unexpected error occurred during submission.');
        });
    }

    function applyDefensiveGuard(delId, guardId, contentId) {
        const guardEl = document.getElementById(guardId);
        const contentEl = document.getElementById(contentId);
        if (!guardEl || !contentEl) return false;

        let isBlocked = false;
        let title = '';
        let desc = '';

        if (!delId || delId === '0' || delId === '') {
            isBlocked = true;
            title = 'Delivery Data Incomplete';
            desc = 'Reconciliation and postings are unavailable because delivery has not been arranged for this route yet.';
        } else if (currentRouteStatus !== 'Completed' && currentRouteStatus !== 'Finalized') {
            isBlocked = true;
            title = 'Preview Not Available';
            desc = 'Reconciliation and GL postings can only be performed once the route delivery has completed execution and is marked as Completed or Finalized.';
        }

        if (isBlocked) {
            guardEl.innerHTML = `
                <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 45px 20px; text-align: center; max-width: 580px; margin: 40px auto; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                    <div style="width: 60px; height: 60px; background: #fffbeb; color: #d97706; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px auto; font-size: 28px;">
                        <i class="ph ph-warning-circle"></i>
                    </div>
                    <h4 style="margin: 0 0 10px 0; font-size: 15px; font-weight: bold; color: #0f172a;">${title}</h4>
                    <p style="margin: 0; font-size: 12px; color: #64748b; line-height: 1.6;">${desc}</p>
                </div>
            `;
            guardEl.style.display = 'block';
            contentEl.style.display = 'none';
            return true;
        } else {
            guardEl.style.display = 'none';
            contentEl.style.display = 'block';
            return false;
        }
    }

    function loadTab8Reconciliation(routeId) {
        const rdata = document.getElementById('route_data_' + routeId);
        const delId = rdata ? rdata.getAttribute('data-delivery-id') : null;

        if (applyDefensiveGuard(delId, 'tab8GuardContainer', 'tab8ContentContainer')) {
            return;
        }

        document.getElementById('reconExpectedCash').innerText = 'Rs 0.00';
        document.getElementById('reconExpectedCollections').innerText = 'Rs 0.00';
        document.getElementById('reconTotalExpectedCash').innerText = 'Rs 0.00';
        document.getElementById('reconActualCash').value = '0.00';
        document.getElementById('reconCashVariance').innerText = 'Rs 0.00';
        document.getElementById('reconAuditNotes').value = '';
        document.getElementById('reconChequesTbody').innerHTML = '<tr><td colspan="4" style="text-align:center;">Loading cheques... </td></tr>';

        const isReadOnly = (currentRouteStatus === 'Completed' || currentRouteStatus === 'Finalized');
        const saveBtn = document.getElementById('btnSaveReconciliationDraft');
        if (saveBtn) {
            saveBtn.disabled = isReadOnly;
            saveBtn.style.opacity = isReadOnly ? '0.5' : '1';
            saveBtn.style.cursor = isReadOnly ? 'not-allowed' : 'pointer';
        }
        document.getElementById('reconActualCash').disabled = isReadOnly;
        document.getElementById('reconAuditNotes').disabled = isReadOnly;

        fetchSecure('<?= APP_URL ?>/RepTracking/api_get_delivery_details/' + delId)
            .then(res => res.json())
            .then(data => {
                if (data.status !== 'success') return;

                currentDeliveryDetails = data;
                const balancing = data.balancing;

                const expectedCashSales = parseFloat(balancing.cash_sales || 0);
                const expectedCashColls = parseFloat(balancing.cash_collections || 0);
                const totalExpectedCash = expectedCashSales + expectedCashColls;

                document.getElementById('reconExpectedCash').innerText = 'Rs ' + expectedCashSales.toLocaleString('en-US', {minimumFractionDigits: 2});
                document.getElementById('reconExpectedCollections').innerText = 'Rs ' + expectedCashColls.toLocaleString('en-US', {minimumFractionDigits: 2});
                document.getElementById('reconTotalExpectedCash').innerText = 'Rs ' + totalExpectedCash.toLocaleString('en-US', {minimumFractionDigits: 2});

                let actualCash = 0;
                let remarks = '';
                if (data.delivery && data.delivery.reconciliation_json) {
                    try {
                        const recon = JSON.parse(data.delivery.reconciliation_json);
                        actualCash = parseFloat(recon.actual_cash || 0);
                        remarks = recon.audit_remarks || '';
                    } catch(e) {}
                }

                // Fallback: if actualCash is 0, try to populate it using the driver's submitted cash_denominations total
                if (actualCash === 0 && data.delivery && data.delivery.cash_denominations) {
                    try {
                        const denoms = JSON.parse(data.delivery.cash_denominations);
                        let sum = 0;
                        const denomList = [5000, 2000, 1000, 500, 100, 50, 20];
                        denomList.forEach(den => {
                            const count = parseInt(denoms[den] || 0);
                            sum += den * count;
                        });
                        sum += parseFloat(denoms.coins || 0);
                        if (sum > 0) {
                            actualCash = sum;
                        }
                    } catch(e) {}
                }

                document.getElementById('reconActualCash').value = actualCash.toFixed(2);
                document.getElementById('reconAuditNotes').value = remarks;

                calculateCashVariance();

                // Render cheques
                const chequesTbody = document.getElementById('reconChequesTbody');
                chequesTbody.innerHTML = '';
                const cheques = balancing.payments ? balancing.payments.filter(p => p.payment_method === 'Cheque') : [];
                if (cheques.length === 0) {
                    chequesTbody.innerHTML = '<tr><td colspan="4" style="text-align:center; color:#888; padding:10px;">No cheques collected.</td></tr>';
                } else {
                    cheques.forEach((ch, idx) => {
                        let isChApproved = parseInt(ch.is_verified) === 1;
                        let approveBox = `
                            <input type="checkbox" onchange="toggleReconChequeApproval(${ch.id}, this.checked)" 
                                   ${isChApproved ? 'checked' : ''} ${isReadOnly ? 'disabled' : ''} 
                                   style="width:16px; height:16px; cursor:${isReadOnly ? 'not-allowed' : 'pointer'};" />
                        `;
                        chequesTbody.innerHTML += `
                            <tr>
                                <td><strong>${ch.customer_name}</strong></td>
                                <td>${ch.reference || 'N/A'}</td>
                                <td style="text-align:right; font-family:monospace; font-weight:bold;">Rs ${parseFloat(ch.amount).toFixed(2)}</td>
                                <td style="text-align:center;">${approveBox}</td>
                            </tr>
                        `;
                    });
                }
            });
    }

    function calculateCashVariance() {
        const expectedStr = document.getElementById('reconTotalExpectedCash').innerText.replace('Rs ', '').replace(/,/g, '');
        const expected = parseFloat(expectedStr) || 0;
        const actual = parseFloat(document.getElementById('reconActualCash').value) || 0;
        const variance = actual - expected;

        const el = document.getElementById('reconCashVariance');
        el.innerText = 'Rs ' + variance.toLocaleString('en-US', {minimumFractionDigits: 2});
        if (variance < 0) {
            el.style.color = '#c62828';
        } else if (variance > 0) {
            el.style.color = '#2e7d32';
        } else {
            el.style.color = '#000';
        }
    }

    function toggleReconChequeApproval(paymentId, checked) {
        if (!currentDeliveryDetails) return;
        const payments = currentDeliveryDetails.balancing.payments || [];
        const ch = payments.find(p => p.id === paymentId);
        if (ch) {
            ch.is_verified = checked ? 1 : 0;
        }
    }

    function saveReconciliationDraft() {
        if (!currentRouteId || !currentDeliveryDetails) return;
        const actualCash = parseFloat(document.getElementById('reconActualCash').value) || 0;
        const remarks = document.getElementById('reconAuditNotes').value;

        const chequeApprovals = {};
        const payments = currentDeliveryDetails.balancing.payments || [];
        payments.forEach(p => {
            if (p.payment_method === 'Cheque') {
                chequeApprovals[p.id] = parseInt(p.is_verified) === 1;
            }
        });

        const reconData = {
            actual_cash: actualCash,
            audit_remarks: remarks,
            cheque_approvals: chequeApprovals
        };

        const deliveryId = currentDeliveryDetails.delivery.id;

        fetchSecure('<?= APP_URL ?>/RepTracking/api_save_reconciliation', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ delivery_id: deliveryId, reconciliation_data: reconData })
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                alert("Reconciliation draft saved successfully!");
                onRouteDataChanged();
            } else {
                alert("Error: " + data.message);
            }
        });
    }

    function loadTab9ReturnStock(routeId) {
        const rdata = document.getElementById('route_data_' + routeId);
        const delId = rdata ? rdata.getAttribute('data-delivery-id') : null;

        const tbody = document.getElementById('settleStockTableBody');
        tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;">Loading return stock counts... </td></tr>';

        const verifyStockCheck = document.getElementById('settleVerifyStock');
        if (verifyStockCheck) {
            verifyStockCheck.checked = false;
        }

        if (!delId || delId === '0' || delId === '') {
            tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; color:#888;">Delivery has not been arranged.</td></tr>';
            return;
        }

        const isReadOnly = (currentRouteStatus === 'Completed' || currentRouteStatus === 'Finalized');
        const saveBtn = document.getElementById('btnSaveReturnStockDraft');
        if (saveBtn) {
            saveBtn.disabled = isReadOnly;
            saveBtn.style.opacity = isReadOnly ? '0.5' : '1';
            saveBtn.style.cursor = isReadOnly ? 'not-allowed' : 'pointer';
        }
        if (verifyStockCheck) {
            verifyStockCheck.disabled = isReadOnly;
        }

        fetchSecure('<?= APP_URL ?>/RepTracking/api_get_delivery_details/' + delId)
            .then(res => res.json())
            .then(data => {
                if (data.status !== 'success') return;
                currentDeliveryDetails = data;

                let savedReturnStock = null;
                if (data.delivery && data.delivery.return_stock_json) {
                    try {
                        savedReturnStock = JSON.parse(data.delivery.return_stock_json);
                    } catch(e) {}
                }

                tbody.innerHTML = '';
                if (!data.balancing.stock_items || data.balancing.stock_items.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; color:#888;">No stock items loaded.</td></tr>';
                } else {
                    data.balancing.stock_items.forEach(st => {
                        let expectedReturned = parseInt(st.loaded_qty) - parseInt(st.delivered_qty);
                        if (expectedReturned < 0) expectedReturned = 0;
                        
                        let actualCounted = expectedReturned;
                        if (savedReturnStock) {
                            const savedVal = savedReturnStock.find(x => x.item_id === st.item_id && x.variation_option_id === st.variation_option_id);
                            if (savedVal) {
                                actualCounted = savedVal.actual_returned_qty;
                            }
                        }

                        tbody.innerHTML += `
                            <tr>
                                <td><strong>${st.item_name}</strong></td>
                                <td style="text-align:center; font-weight:bold;">${parseInt(st.loaded_qty)}</td>
                                <td style="text-align:center; color:#2e7d32; font-weight:bold;">${parseInt(st.delivered_qty)}</td>
                                <td style="text-align:center; font-weight:bold; font-family:monospace; background:#fafafa;">${expectedReturned}</td>
                                <td style="text-align:right;">
                                    <input type="number" class="actual-returned-input" 
                                           data-name="${st.item_name}" data-item-id="${st.item_id}" data-var-id="${st.variation_option_id || 0}"
                                           data-loaded="${st.loaded_qty}" data-delivered="${st.delivered_qty}" 
                                           value="${actualCounted}" min="0" ${isReadOnly ? 'disabled' : ''}
                                           style="width:80px; text-align:right; padding:4px; font-family:monospace; font-weight:bold;" />
                                </td>
                            </tr>
                        `;
                    });
                }

                if (isReadOnly && verifyStockCheck) {
                    verifyStockCheck.checked = true;
                }

                checkSettleVerification();
            });
    }

    function saveReturnStockDraft() {
        if (!currentRouteId || !currentDeliveryDetails) return;

        const returnedItems = [];
        document.querySelectorAll('.actual-returned-input').forEach(input => {
            returnedItems.push({
                item_name: input.getAttribute('data-name'),
                item_id: parseInt(input.getAttribute('data-item-id') || 0),
                variation_option_id: parseInt(input.getAttribute('data-var-id') || 0),
                loaded_qty: parseFloat(input.getAttribute('data-loaded') || 0),
                delivered_qty: parseFloat(input.getAttribute('data-delivered') || 0),
                actual_returned_qty: parseFloat(input.value || 0)
            });
        });

        const deliveryId = currentDeliveryDetails.delivery.id;

        fetchSecure('<?= APP_URL ?>/RepTracking/api_save_return_stock', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ delivery_id: deliveryId, return_stock_data: returnedItems })
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                alert("Return stock draft saved successfully!");
                onRouteDataChanged();
            } else {
                alert("Error: " + data.message);
            }
        });
    }

    function loadTab10Accounting(routeId) {
        const rdata = document.getElementById('route_data_' + routeId);
        const delId = rdata ? rdata.getAttribute('data-delivery-id') : null;

        if (applyDefensiveGuard(delId, 'tab10GuardContainer', 'tab10ContentContainer')) {
            return;
        }

        const colContainer = document.getElementById('settleDeCollectionsContainer');
        const salesContainer = document.getElementById('settleDeSalesContainer');
        
        colContainer.innerHTML = '<p style="text-align:center; color:#888;">Loading account mappings... </p>';
        salesContainer.innerHTML = '';

        document.getElementById('settleDaVehicle').value = '';
        document.getElementById('settleDaDriver').value = '';
        document.getElementById('settleDaPartner').value = '';

        const isReadOnly = (currentRouteStatus === 'Completed' || currentRouteStatus === 'Finalized');
        const saveBtn = document.getElementById('btnSaveAccountingDraft');
        if (saveBtn) {
            saveBtn.disabled = isReadOnly;
            saveBtn.style.opacity = isReadOnly ? '0.5' : '1';
            saveBtn.style.cursor = isReadOnly ? 'not-allowed' : 'pointer';
        }
        document.getElementById('settleDaVehicle').disabled = isReadOnly;
        document.getElementById('settleDaDriver').disabled = isReadOnly;
        document.getElementById('settleDaPartner').disabled = isReadOnly;

        fetchSecure('<?= APP_URL ?>/RepTracking/api_get_delivery_details/' + delId)
            .then(res => res.json())
            .then(data => {
                if (data.status !== 'success') return;
                currentDeliveryDetails = data;

                if (data.delivery) {
                    document.getElementById('settleDaVehicle').value = data.delivery.vehicle_number || '';
                    document.getElementById('settleDaDriver').value = data.delivery.driver_name || '';
                    document.getElementById('settleDaPartner').value = data.delivery.partner_name || '';
                }

                renderSettleDoubleEntries();

                if (data.delivery && data.delivery.accounting_entries_json) {
                    try {
                        const accEntries = JSON.parse(data.delivery.accounting_entries_json);
                        document.querySelectorAll('.settle-de-select').forEach(sel => {
                            const id = sel.getAttribute('data-id');
                            const type = sel.getAttribute('data-type');
                            if (accEntries[type] && accEntries[type][id]) {
                                sel.value = accEntries[type][id];
                            }
                        });
                    } catch(e) {}
                }

                if (isReadOnly) {
                    document.querySelectorAll('.settle-de-select, .settle-payment-chk, .settle-invoice-chk').forEach(el => {
                        el.disabled = true;
                    });
                }

                checkSettleVerification();
            });
    }

    function saveAccountingDraft() {
        if (!currentRouteId || !currentDeliveryDetails) return;

        const debitAccounts = {};
        const creditAccounts = {};
        document.querySelectorAll('.settle-de-select').forEach(sel => {
            const id = sel.getAttribute('data-id');
            const type = sel.getAttribute('data-type');
            const val = parseInt(sel.value);
            if (type === 'debit') { debitAccounts[id] = val; } else { creditAccounts[id] = val; }
        });

        const accountingData = {
            debit: debitAccounts,
            credit: creditAccounts
        };

        const deliveryId = currentDeliveryDetails.delivery.id;

        fetchSecure('<?= APP_URL ?>/RepTracking/api_save_accounting_entries', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ delivery_id: deliveryId, accounting_entries_json: accountingData })
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                alert("Accounting mappings draft saved successfully!");
                onRouteDataChanged();
            } else {
                alert("Error: " + data.message);
            }
        });
    }

    function checkSettleVerification() {
        const verifyStockCheck = document.getElementById('settleVerifyStock');
        const verifyStock = verifyStockCheck ? verifyStockCheck.checked : false;

        const btn = document.getElementById('settleSubmitBtn');
        const text = document.getElementById('settleStatusText');
        if (!btn || !text) return;

        if (currentRouteStatus === 'Completed' || currentRouteStatus === 'Finalized') {
            btn.disabled = true;
            btn.style.opacity = '0.5';
            btn.style.cursor = 'not-allowed';
            text.innerHTML = '<span style="color:#2e7d32; font-weight:bold;">Route Finalized & Settled</span>';
            return;
        }

        let allCollectionsApproved = true;
        let pendingOrFlaggedCount = 0;
        
        // Check collections verification from Tab 2
        currentGLCollectionsState.forEach(col => {
            if (parseInt(col.is_verified) !== 1) {
                allCollectionsApproved = false;
                pendingOrFlaggedCount++;
            }
        });

        if (allCollectionsApproved && verifyStock) {
            btn.disabled = false;
            btn.style.opacity = '1';
            btn.style.cursor = 'pointer';
            text.innerHTML = '<span style="color:#2e7d32; font-weight:bold;">Verification Complete!</span> Ready to settle balancing.';
        } else {
            btn.disabled = true;
            btn.style.opacity = '0.5';
            btn.style.cursor = 'not-allowed';
            
            let msg = '';
            if (!allCollectionsApproved) {
                msg += `Please approve/verify all collections under Collection Verification tab (${pendingOrFlaggedCount} remaining). `;
            }
            if (!verifyStock) {
                msg += 'Please verify Return Stock checkbox under Return Stock tab.';
            }
            text.innerHTML = `<span style="color:#dc2626; font-weight:bold;">Locked:</span> ${msg}`;
        }
    }

    let settleActiveDeTab = 'collections';
    function switchSettleDeTab(tab) {
        settleActiveDeTab = tab;
        document.getElementById('settleDeTabCollectionsBtn').classList.toggle('active', tab === 'collections');
        document.getElementById('settleDeTabSalesBtn').classList.toggle('active', tab === 'sales');
        document.getElementById('settleDeCollectionsContainer').style.display = tab === 'collections' ? 'block' : 'none';
        document.getElementById('settleDeSalesContainer').style.display = tab === 'sales' ? 'block' : 'none';
    }

    function renderSettleDeAccountSelect(id, type, selectedCode) {
        let optionsHtml = '';
        globalAllAccounts.forEach(acc => {
            let isSel = acc.account_code === selectedCode ? 'selected' : '';
            optionsHtml += `<option value="${acc.id}" ${isSel}>${acc.account_code} - ${acc.account_name}</option>`;
        });
        return `<select class="settle-de-select" data-id="${id}" data-type="${type}" style="padding:4px 8px; font-size:12px; border-radius:4px; border:1px solid #ccc; width:100%;">${optionsHtml}</select>`;
    }

    function renderSettleDoubleEntries() {
        const colContainer = document.getElementById('settleDeCollectionsContainer');
        const salesContainer = document.getElementById('settleDeSalesContainer');
        
        colContainer.innerHTML = '';
        salesContainer.innerHTML = '';

        if (!currentDeliveryDetails) return;

        const payments = currentDeliveryDetails.balancing.payments || [];
        const invoices = currentDeliveryDetails.invoices || [];

        // 1. Collections
        if (payments.length === 0) {
            colContainer.innerHTML = '<p style="color:#888; text-align:center;">No payments logged on this trip.</p>';
        } else {
            payments.forEach(p => {
                let defaultDebitCode = '1000'; // Cash
                if (p.payment_method === 'Cheque') { defaultDebitCode = '1010'; }
                else if (p.payment_method === 'Bank Transfer') { defaultDebitCode = '1605'; }

                colContainer.innerHTML += `
                    <div style="display:flex; justify-content:space-between; align-items:center; background:#fafafa; border:1px solid #eee; padding:10px; margin-bottom:8px; border-radius:6px; flex-wrap:wrap; gap:10px;">
                        <div style="flex:1; min-width:200px;">
                            <label style="display:flex; align-items:center; gap:8px; font-weight:bold;">
                                <input type="checkbox" class="settle-payment-chk" value="${p.id}" checked>
                                ${p.customer_name} (${p.payment_method})
                            </label>
                        </div>
                        <div style="font-weight:bold; color:#2e7d32;">Rs ${parseFloat(p.amount).toFixed(2)}</div>
                        <div style="display:flex; gap:10px; flex:2;">
                            <div style="flex:1;">
                                <span style="font-size:9px; color:#666;">Debit Account</span>
                                ${renderSettleDeAccountSelect(p.id, 'debit', defaultDebitCode)}
                            </div>
                            <div style="flex:1;">
                                <span style="font-size:9px; color:#666;">Credit Account</span>
                                ${renderSettleDeAccountSelect(p.id, 'credit', '1090')}
                            </div>
                        </div>
                    </div>
                `;
            });
        }

        // 2. Sales
        const deliveredInvoices = invoices.filter(inv => inv.delivery_status === 'Delivered');
        if (deliveredInvoices.length === 0) {
            salesContainer.innerHTML = '<p style="color:#888; text-align:center;">No delivered sales invoices on this trip.</p>';
        } else {
            deliveredInvoices.forEach(inv => {
                salesContainer.innerHTML += `
                    <div style="display:flex; justify-content:space-between; align-items:center; background:#fafafa; border:1px solid #eee; padding:10px; margin-bottom:8px; border-radius:6px; flex-wrap:wrap; gap:10px;">
                        <div style="flex:1; min-width:200px;">
                            <label style="display:flex; align-items:center; gap:8px; font-weight:bold;">
                                <input type="checkbox" class="settle-invoice-chk" value="${inv.id}" checked>
                                ${inv.invoice_number} (${inv.customer_name})
                            </label>
                        </div>
                        <div style="font-weight:bold; color:#0066cc;">Rs ${parseFloat(inv.true_grand_total).toFixed(2)}</div>
                        <div style="display:flex; gap:10px; flex:2;">
                            <div style="flex:1;">
                                <span style="font-size:9px; color:#666;">Debit Account (AR)</span>
                                ${renderSettleDeAccountSelect(inv.id, 'debit', '1090')}
                            </div>
                            <div style="flex:1;">
                                <span style="font-size:9px; color:#666;">Credit Account (Sales)</span>
                                ${renderSettleDeAccountSelect(inv.id, 'credit', '3000')}
                            </div>
                        </div>
                    </div>
                `;
            });
        }
    }

    function submitFinalSettle() {
        const vehicle = document.getElementById('settleDaVehicle').value;
        const driver = document.getElementById('settleDaDriver').value;
        const partner = document.getElementById('settleDaPartner').value;

        if (!vehicle) { alert("Please select a Vehicle Number."); return; }
        if (!driver) { alert("Please select a Driver Name."); return; }

        if (!confirm("Are you sure you want to FINALIZE and SETTLE this delivery route?\n\nThis will post all selected collections to GL and update inventory.")) {
            return;
        }

        const d = document.getElementById('route_data_' + currentRouteId);
        const delId = d.getAttribute('data-delivery-id');

        const selectedPaymentIds = [];
        document.querySelectorAll('.settle-payment-chk:checked').forEach(cb => {
            selectedPaymentIds.push(parseInt(cb.value));
        });

        const selectedInvoiceIds = [];
        document.querySelectorAll('.settle-invoice-chk:checked').forEach(cb => {
            selectedInvoiceIds.push(parseInt(cb.value));
        });

        const debitAccounts = {};
        const creditAccounts = {};
        document.querySelectorAll('.settle-de-select').forEach(sel => {
            const id = sel.getAttribute('data-id');
            const type = sel.getAttribute('data-type');
            const val = parseInt(sel.value);
            if (type === 'debit') { debitAccounts[id] = val; } else { creditAccounts[id] = val; }
        });

        const returnedItems = [];
        document.querySelectorAll('.actual-returned-input').forEach(input => {
            returnedItems.push({
                item_name: input.getAttribute('data-name'),
                item_id: parseInt(input.getAttribute('data-item-id') || 0),
                variation_option_id: parseInt(input.getAttribute('data-var-id') || 0),
                loaded_qty: parseFloat(input.getAttribute('data-loaded') || 0),
                delivered_qty: parseFloat(input.getAttribute('data-delivered') || 0),
                actual_returned_qty: parseFloat(input.value || 0)
            });
        });

        const btn = document.getElementById('settleSubmitBtn');
        btn.disabled = true;
        btn.innerText = 'Settling Route... ';

        fetchSecure('<?= APP_URL ?>/RepTracking/finalize', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                delivery_id: parseInt(delId),
                selected_payment_ids: selectedPaymentIds,
                selected_invoice_ids: selectedInvoiceIds,
                debit_accounts: debitAccounts,
                credit_accounts: creditAccounts,
                returned_items: returnedItems,
                vehicle_number: vehicle,
                driver_name: driver,
                partner_name: partner
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                alert("Settle balancing successfully completed! Route marked as Completed.");
                window.location.reload();
            } else {
                alert("Error: " + data.message);
                btn.disabled = false;
                btn.innerText = '<i class="ph ph-scales"></i> Settle Balancing & Finalize Route';
                checkSettleVerification();
            }
        });
    }

    function printBalancingReport() {
        const d = document.getElementById('route_data_' + currentRouteId);
        const delId = d ? d.getAttribute('data-delivery-id') : null;
        if (delId) { window.open('<?= APP_URL ?>/RepTracking/balancing_report/' + delId, '_blank'); }
    }

    function printLoadingSheetSpreadsheet() {
        const d = document.getElementById('route_data_' + currentRouteId);
        const delId = d ? d.getAttribute('data-delivery-id') : null;
        if (delId) { window.open('<?= APP_URL ?>/RepTracking/spreadsheet/' + delId, '_blank'); }
    }

    function exportCSV() {
        const d = document.getElementById('route_data_' + currentRouteId);
        const delId = d ? d.getAttribute('data-delivery-id') : null;
        if (delId) { window.location.href = '<?= APP_URL ?>/RepTracking/export_csv/' + delId; }
    }

    function getDataTypeFromStatus(status) {
        if (status === 'Active') return 'active';
        if (status === 'Pending GL') return 'pending_gl';
        if (status === 'Adjustments') return 'adjustments';
        if (status === 'Loading') return 'loading';
        if (status === 'Variance Adjustment') return 'variance';
        if (status === 'Finalizing') return 'finalizing';
        return 'completed';
    }

    function advanceRouteStatus(targetStatus) {
        if (!confirm(`Are you sure you want to advance this route to "${targetStatus}" stage?`)) {
            return;
        }
        fetchSecure('<?= APP_URL ?>/RepTracking/api_update_route_status', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ route_id: currentRouteId, status: targetStatus })
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                alert(`Route advanced to ${targetStatus}`);
                const filterType = getDataTypeFromStatus(targetStatus);
                window.location.href = window.location.pathname + `?route_id=${currentRouteId}&filter=${filterType}`;
            } else {
                alert("Error: " + data.message);
            }
        });
    }

    function redirectToAddInvoice() {
        if (currentRouteId) {
            window.location.href = '<?= APP_URL ?>/sales/create?type=sales_order&route_id=' + currentRouteId + '&back_url=' + encodeURIComponent(window.location.href);
        }
    }

    function printRouteInvoices() {
        if (currentRouteId) {
            window.open('<?= APP_URL ?>/RepTracking/print_route_invoices/' + currentRouteId, '_blank');
        }
    }

    function openInvoiceSlider(invoiceId) {
        const backdrop = document.getElementById('invoiceSliderBackdrop');
        const iframe = document.getElementById('invoiceIframe');
        iframe.src = 'about:blank';
        setTimeout(() => {
            iframe.src = '<?= APP_URL ?>/sales/show/' + invoiceId + '?hide_buttons=1';
        }, 50);
        backdrop.style.display = 'flex';
    }

    function closeInvoiceSlider() {
        document.getElementById('invoiceSliderBackdrop').style.display = 'none';
        document.getElementById('invoiceIframe').src = 'about:blank';
    }
    function deleteSalesOrder() {
        const invoiceId = document.getElementById('btnDeleteInvoice').getAttribute('data-invoice-id');
        if (!invoiceId) return;
        if (!confirm("Are you sure you want to delete this Sales Order? This will release reserved stock back to inventory and cannot be undone.")) {
            return;
        }
        fetchSecure('<?= APP_URL ?>/RepTracking/api_delete_sales_order/' + invoiceId, {
            method: 'POST'
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                alert("Success: " + data.message);
                closeInvoiceSlider();
                loadRouteDetails(currentRouteId);
            } else {
                alert("Error: " + data.message);
            }
        });
    }
    // --- GPS Path Map Handlers ---
    function openMapModal() {
        document.getElementById('mapModalBackdrop').style.display = 'flex';
        loadRoutePath(currentRouteId);
    }

    function closeMapModal() {
        document.getElementById('mapModalBackdrop').style.display = 'none';
    }

    function initRoutePathMap() {
        if (routeMap !== null) return;
        routeMap = L.map('routePathMap').setView([7.8731, 80.7718], 8);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap'
        }).addTo(routeMap);
    }

    function clearRoutePathMap() {
        routeMapLayers.forEach(layer => routeMap.removeLayer(layer));
        routeMapLayers = [];
    }

    function loadRoutePath(routeId) {
        document.getElementById('mapEmptyOverlay').style.display = 'flex';
        document.getElementById('mapEmptyOverlay').innerText = 'Loading route path...';
        document.getElementById('pathStepList').style.display = 'none';

        initRoutePathMap();
        clearRoutePathMap();

        fetch('<?= APP_URL ?>/RepTracking/api_get_route_path/' + routeId)
            .then(r => r.json())
            .then(data => {
                if (data.status !== 'success' || !data.path) {
                    document.getElementById('mapEmptyOverlay').innerText = 'Could not load route path.';
                    return;
                }
                renderRoutePath(data.path);
            })
            .catch(() => {
                document.getElementById('mapEmptyOverlay').innerText = 'Failed to load route path.';
            });
    }

    function renderRoutePath(path) {
        const wps = path.waypoints || [];
        document.getElementById('pathPointCount').innerText = wps.length ? `(${wps.length} points)` : '(no GPS)';
        document.getElementById('modalRouteName').innerText = path.route_name || '';

        const stepOl = document.getElementById('pathStepOl');
        stepOl.innerHTML = '';

        if (wps.length === 0) {
            document.getElementById('mapEmptyOverlay').style.display = 'flex';
            document.getElementById('mapEmptyOverlay').innerHTML = 'No GPS points recorded for this route.';
            document.getElementById('pathStepList').style.display = 'none';
            setTimeout(() => routeMap.invalidateSize(), 100);
            return;
        }

        document.getElementById('mapEmptyOverlay').style.display = 'none';
        document.getElementById('pathStepList').style.display = 'block';

        const latlngs = [];
        wps.forEach((wp) => {
            const latlng = [wp.lat, wp.lng];
            latlngs.push(latlng);

            let icon = pathBlueIcon;
            let stepClass = 'path-step-invoice';
            if (wp.type === 'start') { icon = pathGreenIcon; stepClass = 'path-step-start'; }
            else if (wp.type === 'end') { icon = pathRedIcon; stepClass = 'path-step-end'; }

            const marker = L.marker(latlng, { icon: icon }).addTo(routeMap);
            marker.bindPopup(`<strong>${wp.name}</strong><br>${wp.description}<br><span style="font-size:10px; color:#666;">${wp.time}</span>`);
            routeMapLayers.push(marker);

            stepOl.innerHTML += `<li class="${stepClass}"><strong>${wp.time}</strong> - ${wp.name} (${wp.description})</li>`;
        });

        if (latlngs.length > 1) {
            const polyline = L.polyline(latlngs, { color: '#0066cc', weight: 4, opacity: 0.7 }).addTo(routeMap);
            routeMapLayers.push(polyline);
            routeMap.fitBounds(polyline.getBounds(), { padding: [30, 30] });
        } else {
            routeMap.setView(latlngs[0], 14);
        }

        setTimeout(() => routeMap.invalidateSize(), 100);
    }

    // --- Route Binding Handlers ---
    function getEligibleBindingRoutes() {
        const routes = [];
        document.querySelectorAll('.route-item').forEach(item => {
            const rType = item.getAttribute('data-route-type');
            if (rType && rType !== 'completed') {
                const id = item.id.replace('route_', '');
                const dataDiv = document.getElementById('route_data_' + id);
                if (dataDiv) {
                    routes.push({ id: parseInt(id), name: dataDiv.getAttribute('data-rname'), rep: dataDiv.getAttribute('data-rep') });
                }
            }
        });
        return routes;
    }

    function openRouteBindingModal() {
        document.getElementById('rbBoundName').value = '';
        document.getElementById('rbSlotsContainer').innerHTML = '';
        rbSlotsCount = 0;
        addBindingSlot();
        addBindingSlot();
        document.getElementById('routeBindingModal').style.display = 'flex';
    }

    function closeRouteBindingModal() {
        document.getElementById('routeBindingModal').style.display = 'none';
    }

    function addBindingSlot() {
        rbSlotsCount++;
        const index = rbSlotsCount;
        const eligibleRoutes = getEligibleBindingRoutes();
        
        let optionsHtml = '<option value="">-- Choose Route --</option>';
        eligibleRoutes.forEach(r => {
            optionsHtml += `<option value="${r.id}">${r.name} (Rep: ${r.rep})</option>`;
        });
        
        const slotHtml = `
            <div class="rb-slot-column" id="rb_slot_col_${index}" style="position: relative;">
                ${index > 2 ? `<button type="button" onclick="removeBindingSlot(${index})" style="position: absolute; top: 10px; right: 10px; border: none; background: #dc2626; color: #fff; width: 22px; height: 22px; border-radius: 50%; cursor: pointer; font-size: 11px; display: flex; align-items: center; justify-content: center; font-weight: bold; padding:0;">✕</button>` : ''}
                <h5 style="margin: 0 0 5px 0; color: #3f51b5; font-size: 12px; font-weight: bold; text-transform: uppercase;">Slot ${index}</h5>
                <div class="rb-slot-box">
                    <div style="font-size: 20px; color: #cbd5e1; margin-bottom: 6px;" id="rb_slot_icon_${index}">+</div>
                    <select class="rb-slot-select" id="rb_select_${index}" onchange="onBindingSlotRouteSelect(${index}, this)">
                        ${optionsHtml}
                    </select>
                </div>
                <div class="rb-bill-list" id="rb_bills_${index}"></div>
            </div>
        `;
        document.getElementById('rbSlotsContainer').insertAdjacentHTML('beforeend', slotHtml);
    }

    function removeBindingSlot(index) {
        const el = document.getElementById(`rb_slot_col_${index}`);
        if (el) el.remove();
    }

    function onBindingSlotRouteSelect(index, select) {
        const routeId = select.value;
        const billsContainer = document.getElementById(`rb_bills_${index}`);
        const icon = document.getElementById(`rb_slot_icon_${index}`);
        
        if (!routeId) {
            billsContainer.style.display = 'none';
            billsContainer.innerHTML = '';
            icon.innerText = '+';
            return;
        }
        
        // Find the selected route object
        const eligibleRoutes = getEligibleBindingRoutes();
        const selectedRoute = eligibleRoutes.find(r => r.id === parseInt(routeId));
        const selectedRouteName = selectedRoute ? selectedRoute.name : '';
        const selectedRepName = selectedRoute ? selectedRoute.rep : '';

        icon.innerText = '<i class="ph ph-link"></i>';
        billsContainer.style.display = 'block';
        billsContainer.innerHTML = '<p style="text-align: center; color: #888;">Loading details... </p>';
        
        fetch('<?= APP_URL ?>/RepTracking/api_get_route_details/' + routeId)
            .then(res => res.json())
            .then(data => {
                if (data.status !== 'success' || !data.bills) {
                    billsContainer.innerHTML = '<p style="text-align: center; color: #888;">Error loading details.</p>';
                    return;
                }
                
                // Calculate values for Route Preview
                const invoices = data.bills;
                const invoiceCount = invoices.length;
                const uniqueCustomers = new Set(invoices.map(b => b.customer_id)).size;
                const totalRouteValue = invoices.reduce((sum, b) => sum + parseFloat(b.true_grand_total), 0);

                let previewHtml = `
                    <div style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 6px; padding: 10px; margin-bottom: 12px; font-size: 11px; line-height: 1.5; color: #334155; text-align: left; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                        <div style="margin-bottom: 3px;"><strong>Route:</strong> ${selectedRouteName}</div>
                        <div style="margin-bottom: 3px;"><strong>Route ID:</strong> #${routeId}</div>
                        <div style="margin-bottom: 3px;"><strong>Rep Name:</strong> ${selectedRepName}</div>
                        <div style="margin-bottom: 3px;"><strong>Customers Count:</strong> ${uniqueCustomers}</div>
                        <div style="margin-bottom: 3px;"><strong>Invoice Count:</strong> ${invoiceCount}</div>
                        <div><strong>Total Value:</strong> <strong style="color: #16a34a;">Rs ${totalRouteValue.toLocaleString('en-IN', {minimumFractionDigits: 2})}</strong></div>
                    </div>
                `;

                if (invoices.length === 0) {
                    previewHtml += '<p style="text-align: center; color: #888; font-size: 11px;">No sales orders in this route.</p>';
                    billsContainer.innerHTML = previewHtml;
                    return;
                }
                
                previewHtml += '<div style="font-weight: bold; border-bottom: 1px solid #cbd5e1; padding-bottom: 4px; margin-bottom: 8px; font-size: 10px; text-transform: uppercase; color: #475569;">Sales Orders / Bills</div>';
                previewHtml += '<div style="max-height: 150px; overflow-y: auto; display: flex; flex-direction: column; gap: 4px;">';
                invoices.forEach(b => {
                    let trueTotal = parseFloat(b.true_grand_total).toLocaleString('en-IN', {minimumFractionDigits: 2});
                    previewHtml += `
                        <div class="rb-bill-item" style="display: flex; justify-content: space-between; align-items: flex-start; background: #fff; border: 1px solid #e2e8f0; padding: 6px; border-radius: 4px; font-size: 11px;">
                            <div style="display: flex; flex-direction: column; max-width: 65%;">
                                <strong>${b.invoice_number}</strong>
                                <span style="color: #64748b; font-size: 10px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${b.customer_name}</span>
                            </div>
                            <strong style="font-family: monospace; color: #0f172a; white-space: nowrap;">Rs ${trueTotal}</strong>
                        </div>
                    `;
                });
                previewHtml += '</div>';
                billsContainer.innerHTML = previewHtml;
            });
    }

    function submitRouteBinding() {
        const boundName = document.getElementById('rbBoundName').value.trim();
        if (!boundName) { alert("Please enter a custom name for the bound route."); return; }
        
        const routeIds = [];
        document.querySelectorAll('.rb-slot-select').forEach(select => {
            if (select.value) { routeIds.push(parseInt(select.value)); }
        });
        
        const uniqueRouteIds = [...new Set(routeIds)];
        if (uniqueRouteIds.length < 2) { alert("Please select at least 2 distinct routes to bind."); return; }
        if (uniqueRouteIds.length !== routeIds.length) { alert("Please make sure you do not select the same route in multiple slots."); return; }
        
        if (!confirm(`Are you sure you want to bind these ${uniqueRouteIds.length} routes together under "${boundName}"?`)) { return; }
        
        fetch('<?= APP_URL ?>/RepTracking/api_create_binding', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ binding_name: boundName, route_ids: uniqueRouteIds })
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                alert("Success: " + data.message);
                closeRouteBindingModal();
                // Redirect/reload switching filter to Adjustments
                window.location.href = window.location.pathname + `?filter=adjustments`;
            } else {
                alert("Error: " + data.message);
            }
        });
    }

    function unbindActiveRoute() {
        const btnUnbind = document.getElementById('btnUnbindRoute');
        const bindingId = btnUnbind ? btnUnbind.getAttribute('data-binding-id') : null;
        if (!bindingId) { alert("No active route binding identified."); return; }

        if (!confirm("Are you sure you want to undo this route binding? The routes will be separated back to their original states and listed individually.")) { return; }

        fetch('<?= APP_URL ?>/RepTracking/api_unbind_route', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ binding_id: parseInt(bindingId) })
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                alert("Success: " + data.message);
                window.location.href = window.location.pathname + `?filter=adjustments`;
            } else {
                alert("Error: " + data.message);
            }
        });
    }

    function unbindCombinedRoute() {
        if (!currentRouteId) return;
        if (!confirm("Are you sure you want to Undo this route binding? All invoices, loading data, and collections will be restored to their original separate routes, and this combined route will be removed.")) {
            return;
        }
        
        fetch('<?= APP_URL ?>/RepTracking/api_unbind_route', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ route_id: currentRouteId })
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                alert(data.message);
                window.location.href = window.location.pathname + `?filter=adjustments`;
            } else {
                alert("Error: " + data.message);
            }
        });
    }

    // --- Attach Invoice Modal Handlers ---
    function openAttachInvoiceModal() {
        document.getElementById('invoiceSearchInput').value = '';
        document.getElementById('soFilterStartDate').value = '';
        document.getElementById('soFilterEndDate').value = '';
        document.getElementById('soFilterStatus').value = '';
        document.getElementById('unattachedInvoicesContainer').innerHTML = '<p style="text-align: center; color: #888;">Type search text or modify filters to query unattached sales orders...</p>';
        document.getElementById('attachInvoiceModal').style.display = 'flex';
    }

    function closeAttachInvoiceModal() {
        document.getElementById('attachInvoiceModal').style.display = 'none';
    }

    function searchUnattachedInvoices() {
        const query = document.getElementById('invoiceSearchInput').value;
        const startDate = document.getElementById('soFilterStartDate').value;
        const endDate = document.getElementById('soFilterEndDate').value;
        const status = document.getElementById('soFilterStatus').value;
        const container = document.getElementById('unattachedInvoicesContainer');
        
        container.innerHTML = '<p style="text-align: center; color: #888; margin: 10px 0;">Searching... </p>';
        
        let url = '<?= APP_URL ?>/RepTracking/api_get_unattached_invoices?search=' + encodeURIComponent(query);
        if (startDate) url += '&start_date=' + encodeURIComponent(startDate);
        if (endDate) url += '&end_date=' + encodeURIComponent(endDate);
        if (status) url += '&status=' + encodeURIComponent(status);
        
        fetch(url)
            .then(res => res.json())
            .then(data => {
                if (data.status !== 'success' || !data.invoices || data.invoices.length === 0) {
                    container.innerHTML = '<p style="text-align: center; color: #888; margin: 10px 0;">No unattached sales orders found.</p>';
                    return;
                }
                
                let html = '<div style="display: flex; flex-direction: column; gap: 8px;">';
                data.invoices.forEach(inv => {
                    let amtFormatted = parseFloat(inv.true_grand_total).toLocaleString('en-IN', {minimumFractionDigits: 2});
                    html += `
                        <label style="display: flex; align-items: flex-start; gap: 10px; cursor: pointer; padding: 6px; border-bottom: 1px solid #f0f0f0; margin-bottom: 0;">
                            <input type="checkbox" class="attach-invoice-checkbox" value="${inv.id}" style="width: 16px; height: 16px;">
                            <div style="flex: 1;">
                                <div style="font-weight: bold; color: #333;">${inv.invoice_number} <span style="font-size: 10px; font-weight: bold; background: #e0f2fe; color: #0369a1; padding: 2px 6px; border-radius: 4px; margin-left: 8px;">${inv.status}</span></div>
                                <div style="font-size: 11px; color: #666;">Customer: <strong>${inv.customer_name}</strong> | Date: ${inv.invoice_date}</div>
                            </div>
                            <div style="font-weight: bold; font-family: monospace; color: #2e7d32;">Rs ${amtFormatted}</div>
                        </label>
                    `;
                });
                html += '</div>';
                container.innerHTML = html;
            });
    }

    function confirmAttachInvoices() {
        const checkedInvoices = [];
        document.querySelectorAll('.attach-invoice-checkbox:checked').forEach(cb => {
            checkedInvoices.push(cb.value);
        });
        
        if (checkedInvoices.length === 0) { alert("Please select at least one sales order to attach."); return; }
        
        closeAttachInvoiceModal();
        
        fetchSecure('<?= APP_URL ?>/RepTracking/api_attach_invoices', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ route_id: currentRouteId, invoice_ids: checkedInvoices })
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                alert("Attached successfully!");
                loadRouteDetails(currentRouteId);
            } else {
                alert("Error: " + data.message);
            }
        });
    }

    function resetSalesOrderFilters() {
        document.getElementById('invoiceSearchInput').value = '';
        document.getElementById('soFilterStartDate').value = '';
        document.getElementById('soFilterEndDate').value = '';
        document.getElementById('soFilterStatus').value = '';
        searchUnattachedInvoices();
    }


    /* Dots Menu Handlers */
    function toggleDotsMenu(e, id) {
        e.stopPropagation();
        const btn = e.currentTarget;
        const dropdown = document.getElementById('dots-dropdown-' + id);
        if (!dropdown) return;
        
        const isShowing = dropdown.classList.contains('show');
        
        // Hide all other dropdowns
        closeAllDotsMenus();
        
        if (!isShowing) {
            // Determine vertical position to open upwards if near the bottom
            const rect = btn.getBoundingClientRect();
            const windowHeight = window.innerHeight;
            
            // If the button is in the bottom 40% of the viewport, open upwards
            if (rect.top > windowHeight * 0.6) {
                dropdown.style.top = 'auto';
                dropdown.style.bottom = '100%';
                dropdown.style.marginTop = '0';
                dropdown.style.marginBottom = '6px';
            } else {
                dropdown.style.top = '100%';
                dropdown.style.bottom = 'auto';
                dropdown.style.marginTop = '6px';
                dropdown.style.marginBottom = '0';
            }
            
            dropdown.classList.add('show');
            
            const backdrop = document.getElementById('menuBackdrop');
            if (backdrop) backdrop.style.display = 'block';
            
            // Lift the menu container's z-index above the backdrop
            const container = btn.closest('.dots-menu-container');
            if (container) {
                container.style.zIndex = '1001';
            }
        }
    }

    function closeAllDotsMenus() {
        document.querySelectorAll('.dots-dropdown').forEach(d => d.classList.remove('show'));
        const backdrop = document.getElementById('menuBackdrop');
        if (backdrop) backdrop.style.display = 'none';
        document.querySelectorAll('.dots-menu-container').forEach(c => c.style.zIndex = '');
    }

    // Close dropdowns on click outside
    document.addEventListener('click', function() {
        closeAllDotsMenus();
    });

    function updateSingleInvoiceDeliveryStatus(invoiceId, customerId, newStatus) {
        const payload = {
            route_id: parseInt(currentRouteId),
            customer_id: parseInt(customerId),
            deliveries: [
                {
                    invoice_id: parseInt(invoiceId),
                    delivery_status: newStatus,
                    items: []
                }
            ],
            collections: null
        };
        
        fetchSecure('<?= APP_URL ?>/RepTracking/api_process_delivery_visit', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                loadDeliveryLiveStage(currentRouteId);
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(err => {
            console.error(err);
            alert('An unexpected error occurred during status update.');
        });
    }

    function editSalesOrder(id) {
        window.open('<?= APP_URL ?>/sales/edit/' + id + '?type=sales_order&back_url=' + encodeURIComponent(window.location.href), '_blank');
    }

    function printInvoice(id) {
        window.open('<?= APP_URL ?>/sales/show/' + id + '?print=1', '_blank');
    }

    function viewCustomerProfile(customerName) {
        window.open('<?= APP_URL ?>/customers?search=' + encodeURIComponent(customerName), '_blank');
    }

    function downloadInvoicePdf(id) {
        window.open('<?= APP_URL ?>/sales/show/' + id + '?pdf=1', '_blank');
    }

    function exportInvoiceExcel(id) {
        window.open('<?= APP_URL ?>/sales/show/' + id + '?excel=1', '_blank');
    }

    /* Secure Delete Handlers */
    let deleteTargetId = null;

    function confirmDeleteSalesOrder(id, invNum) {
        deleteTargetId = id;
        document.getElementById('deleteTargetInvNum').innerText = invNum;
        document.getElementById('deleteConfirmPassword').value = '';
        document.getElementById('deleteConfirmReason').value = '';
        document.getElementById('deleteConfirmModal').style.display = 'flex';
    }

    function closeDeleteConfirmModal() {
        document.getElementById('deleteConfirmModal').style.display = 'none';
        deleteTargetId = null;
    }

    function submitDeleteSalesOrder() {
        const password = document.getElementById('deleteConfirmPassword').value;
        const reason = document.getElementById('deleteConfirmReason').value.trim();
        
        if (!password) { alert("Please enter the administrator password."); return; }
        if (!reason) { alert("Please enter a deletion reason."); return; }
        
        const formData = new URLSearchParams();
        formData.append('password', password);
        formData.append('delete_reason', reason);
        
        closeDeleteConfirmModal();
        
        fetchSecure('<?= APP_URL ?>/sales/delete/' + deleteTargetId, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: formData.toString()
        })
        .then(res => {
            alert("Sales Order successfully deleted and stock balances reversed!");
            onRouteDataChanged();
            loadAdjustmentsStage(currentRouteId);
        })
        .catch(err => {
            alert("Error deleting sales order: " + err.message);
        });
    }

    /* Move Sales Order Handlers */
    let moveTargetId = null;

    function openMoveInvoiceModal(id, invNum) {
        moveTargetId = id;
        document.getElementById('moveTargetInvNum').innerText = invNum;
        
        const select = document.getElementById('moveDestinationRouteSelect');
        select.innerHTML = '<option value="">-- Select Destination Route --</option>';
        
        document.querySelectorAll('.route-item').forEach(el => {
            const rId = el.id.replace('route_', '');
            if (parseInt(rId) === parseInt(currentRouteId)) return;
            
            const d = document.getElementById('route_data_' + rId);
            if (!d) return;
            const rName = d.getAttribute('data-rname') || '';
            const repName = d.getAttribute('data-rep') || '';
            const date = d.getAttribute('data-date') || '';
            
            const opt = document.createElement('option');
            opt.value = rId;
            opt.innerText = `#RT-${String(rId).padStart(5, '0')} - ${rName} (${repName} | ${date})`;
            select.appendChild(opt);
        });
        
        document.getElementById('moveInvoiceModal').style.display = 'flex';
    }

    function closeMoveInvoiceModal() {
        document.getElementById('moveInvoiceModal').style.display = 'none';
        moveTargetId = null;
    }

    function submitMoveSalesOrder() {
        const targetRouteId = document.getElementById('moveDestinationRouteSelect').value;
        if (!targetRouteId) { alert("Please select a destination route."); return; }
        
        closeMoveInvoiceModal();
        
        fetchSecure('<?= APP_URL ?>/RepTracking/api_attach_invoices', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ route_id: targetRouteId, invoice_ids: ['route:' + moveTargetId] })
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                alert("Sales Order successfully moved to the destination route!");
                onRouteDataChanged();
                loadAdjustmentsStage(currentRouteId);
            } else {
                alert("Error: " + data.message);
            }
        });
    }

</script>