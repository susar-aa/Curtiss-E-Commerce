<?php
// Smart Failsafe Data Fetcher
// Ensures the view never crashes even if the controller doesn't pass the exact data arrays.
$db = new Database();



$catalog_items = $data['catalog_items'] ?? $data['items'] ?? [];
if (empty($catalog_items)) {
    $db->query("SELECT * FROM items ORDER BY name ASC");
    $catalog_items = $db->resultSet();
}

// Enforce Wholesale B2B pricing preference and load variations from JSON if needed
foreach ($catalog_items as $key => $item) {
    $billingPrice = 0.00;
    if (is_object($item)) {
        if (isset($item->wholesale_price) && floatval($item->wholesale_price) > 0) {
            $billingPrice = floatval($item->wholesale_price);
        } elseif (isset($item->selling_price) && floatval($item->selling_price) > 0) {
            $billingPrice = floatval($item->selling_price);
        } elseif (isset($item->price) && floatval($item->price) > 0) {
            $billingPrice = floatval($item->price);
        } elseif (isset($item->regular_price) && floatval($item->regular_price) > 0) {
            $billingPrice = floatval($item->regular_price);
        }
        $item->price = $billingPrice;
        $item->selling_price = $billingPrice;
        $item->regular_price = $billingPrice;
        $item->wholesale_price = $billingPrice;

        if (!isset($item->variations) || empty($item->variations)) {
            $db->query("SELECT ivo.*, v.name as variation_name, vv.value_name 
                        FROM item_variation_options ivo
                        JOIN variations v ON ivo.variation_id = v.id
                        JOIN variation_values vv ON ivo.variation_value_id = vv.id
                        WHERE ivo.item_id = :id");
            $db->bind(':id', $item->id);
            $item->variations = $db->resultSet() ?: [];

            if (empty($item->variations) && !empty($item->variations_json)) {
                $decoded = json_decode($item->variations_json);
                if (is_array($decoded)) {
                    $item->variations = [];
                    foreach ($decoded as $v) {
                        $vObj = new stdClass();
                        $vObj->id = $v->id ?? 0;
                        $vObj->variation_name = 'Option';
                        $vObj->value_name = $v->attribute ?? '';
                        $vObj->sku = $v->sku ?? '';
                        $vObj->quantity_on_hand = $v->qty ?? $item->qty ?? $item->quantity_on_hand ?? 0;
                        $vObj->quantity_reserved = $v->quantity_reserved ?? 0;

                        $vPrice = 0.00;
                        if (isset($v->wholesale_price) && floatval($v->wholesale_price) > 0) {
                            $vPrice = floatval($v->wholesale_price);
                        } elseif (isset($v->price) && floatval($v->price) > 0) {
                            $vPrice = floatval($v->price);
                        } else {
                            $vPrice = $billingPrice;
                        }
                        $vObj->price = $vPrice;
                        $item->variations[] = $vObj;
                    }
                }
            } else if (!empty($item->variations)) {
                foreach ($item->variations as $var) {
                    $varPrice = 0.00;
                    if (isset($var->wholesale_price) && floatval($var->wholesale_price) > 0) {
                        $varPrice = floatval($var->wholesale_price);
                    } elseif (isset($var->price) && floatval($var->price) > 0) {
                        $varPrice = floatval($var->price);
                    } else {
                        $varPrice = $billingPrice;
                    }
                    $var->price = $varPrice;
                }
            }
        }
    } elseif (is_array($item)) {
        if (isset($item['wholesale_price']) && floatval($item['wholesale_price']) > 0) {
            $billingPrice = floatval($item['wholesale_price']);
        } elseif (isset($item['selling_price']) && floatval($item['selling_price']) > 0) {
            $billingPrice = floatval($item['selling_price']);
        } elseif (isset($item['price']) && floatval($item['price']) > 0) {
            $billingPrice = floatval($item['price']);
        } elseif (isset($item['regular_price']) && floatval($item['regular_price']) > 0) {
            $billingPrice = floatval($item['regular_price']);
        }
        $catalog_items[$key]['price'] = $billingPrice;
        $catalog_items[$key]['selling_price'] = $billingPrice;
        $catalog_items[$key]['regular_price'] = $billingPrice;
        $catalog_items[$key]['wholesale_price'] = $billingPrice;
    }
}

// Fetch Customers WITH Territory Name and Real-Time Outstanding Balance
$db->query("SELECT c.*, m.name as mca_name,
               (SELECT COALESCE(SUM(total_amount - COALESCE(CASE WHEN global_discount_type = '%' THEN (total_amount * global_discount_val / 100) ELSE global_discount_val END, 0) + COALESCE(tax_amount, 0)), 0) FROM invoices WHERE customer_id = c.id AND status != 'Voided') 
               - 
               (SELECT COALESCE(SUM(amount), 0) FROM customer_payments WHERE customer_id = c.id) 
               - 
               (SELECT COALESCE(SUM(total_amount), 0) FROM credit_notes WHERE customer_id = c.id) 
               AS outstanding_balance
            FROM customers c 
            LEFT JOIN mca_areas m ON c.mca_id = m.id 
            ORDER BY c.name ASC");
$customers = $db->resultSet();

$db->query("SELECT * FROM mca_areas ORDER BY name ASC");
$mcaAreas = $db->resultSet() ?: [];

// Fetch Employees specifically marked as Reps/Sales (or fallback to all active employees)
$db->query("SELECT * FROM employees WHERE status = 'Active' AND job_title = 'Rep' ORDER BY first_name ASC");
$reps = $db->resultSet();
if (empty($reps)) {
    $db->query("SELECT * FROM employees WHERE status = 'Active' ORDER BY first_name ASC");
    $reps = $db->resultSet();
}

// Automate Accounting Defaults
$assets = $data['assets'] ?? [];
if (empty($assets)) {
    $db->query("SELECT * FROM chart_of_accounts WHERE account_type = 'Asset' AND account_name LIKE '%Receivable%' LIMIT 1");
    $assets = $db->resultSet();
    if(empty($assets)) {
        $db->query("SELECT * FROM chart_of_accounts WHERE account_type = 'Asset' LIMIT 1");
        $assets = $db->resultSet();
    }
}

$revenues = $data['revenues'] ?? [];
if (empty($revenues)) {
    $db->query("SELECT * FROM chart_of_accounts WHERE account_type = 'Revenue' LIMIT 1");
    $revenues = $db->resultSet();
}

// Extract Invoice Parameters
$inv = $data['editing_invoice'] ?? null;
$editingItems = $data['editing_items'] ?? [];

// Determine back URL
$rep_route_id = $_GET['rep_route_id'] ?? $_GET['route_id'] ?? (isset($inv->rep_route_id) ? $inv->rep_route_id : '');
$backUrl = $_GET['back_url'] ?? $_SERVER['HTTP_REFERER'] ?? '';
if (empty($backUrl) || strpos($backUrl, '/sales/create') !== false || strpos($backUrl, '/sales/store') !== false) {
    if (!empty($rep_route_id)) {
        $backUrl = APP_URL . '/RepTracking?route_id=' . $rep_route_id . '&filter=adjustments';
    } else {
        $backUrl = APP_URL . '/sales';
    }
}

// Previous & Next navigation IDs
$prevId = null;
$nextId = null;
$type = $data['type'] ?? 'invoice';
if ($inv && isset($inv->id)) {
    $currentId = intval($inv->id);
    if ($type === 'sales_order') {
        $db->query("SELECT id FROM sales_orders WHERE id < :id ORDER BY id DESC LIMIT 1");
        $db->bind(':id', $currentId);
        $row = $db->single();
        if ($row) $prevId = $row->id;

        $db->query("SELECT id FROM sales_orders WHERE id > :id ORDER BY id ASC LIMIT 1");
        $db->bind(':id', $currentId);
        $row = $db->single();
        if ($row) $nextId = $row->id;
    } else {
        $db->query("SELECT id FROM invoices WHERE id < :id AND status != 'Voided' ORDER BY id DESC LIMIT 1");
        $db->bind(':id', $currentId);
        $row = $db->single();
        if ($row) $prevId = $row->id;

        $db->query("SELECT id FROM invoices WHERE id > :id AND status != 'Voided' ORDER BY id ASC LIMIT 1");
        $db->bind(':id', $currentId);
        $row = $db->single();
        if ($row) $nextId = $row->id;
    }
} else {
    if ($type === 'sales_order') {
        $db->query("SELECT id FROM sales_orders ORDER BY id DESC LIMIT 1");
        $row = $db->single();
        if ($row) $prevId = $row->id;
    } else {
        $db->query("SELECT id FROM invoices WHERE status != 'Voided' ORDER BY id DESC LIMIT 1");
        $row = $db->single();
        if ($row) $prevId = $row->id;
    }
}
?>
<style>
    /* =====================================================
       MODERN BILLING PANEL — REDESIGNED UI
       ===================================================== */
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

    html, body { overflow: hidden; height: 100%; margin: 0; }

    :root {
        --primary:       #2563eb;
        --primary-hover: #1d4ed8;
        --primary-light: #eff6ff;
        --success:       #16a34a;
        --success-light: #f0fdf4;
        --danger:        #dc2626;
        --danger-light:  #fef2f2;
        --warning:       #d97706;
        --warning-light: #fffbeb;
        --slate-900:     #0f172a;
        --slate-800:     #1e293b;
        --slate-700:     #334155;
        --slate-600:     #475569;
        --slate-400:     #94a3b8;
        --slate-300:     #cbd5e1;
        --slate-200:     #e2e8f0;
        --slate-100:     #f1f5f9;
        --slate-50:      #f8fafc;
        --white:         #ffffff;
        --radius-sm:     6px;
        --radius-md:     10px;
        --radius-lg:     14px;
        --shadow-sm:     0 1px 3px rgba(0,0,0,0.08), 0 1px 2px rgba(0,0,0,0.04);
        --shadow-md:     0 4px 12px rgba(0,0,0,0.08), 0 2px 4px rgba(0,0,0,0.04);
        --font:          'Inter', system-ui, -apple-system, sans-serif;
    }

    /* ── Wrapper ── */
    .qb-wrapper {
        background: var(--slate-100);
        font-family: var(--font);
        font-size: 13px;
        color: var(--slate-800);
        height: calc(100vh - 30px);
        display: flex;
        flex-direction: column;
        overflow: hidden;
        padding: 10px 12px;
        box-sizing: border-box;
    }

    /* ── Main card ── */
    .qb-container {
        background: var(--white);
        width: 100%;
        max-width: 1400px;
        margin: 0 auto;
        flex: 1;
        display: flex;
        flex-direction: column;
        border: 1px solid var(--slate-200);
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-md);
        padding: 0;
        box-sizing: border-box;
        overflow: hidden;
    }

    #invoiceForm {
        display: flex;
        flex-direction: column;
        height: 100%;
        overflow: hidden;
    }

    /* ── Top nav bar ── */
    .inv-topbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 18px;
        border-bottom: 1px solid var(--slate-200);
        background: var(--white);
        border-radius: var(--radius-lg) var(--radius-lg) 0 0;
        flex-shrink: 0;
    }
    .inv-topbar-left { display: flex; align-items: center; gap: 10px; }
    .inv-topbar-right { display: flex; align-items: center; gap: 6px; }
    .inv-title {
        font-size: 17px;
        font-weight: 700;
        color: var(--slate-900);
        letter-spacing: -0.3px;
    }
    .inv-type-badge {
        display: inline-flex;
        align-items: center;
        padding: 2px 10px;
        border-radius: 99px;
        font-size: 11px;
        font-weight: 600;
        letter-spacing: 0.3px;
        text-transform: uppercase;
        background: var(--primary-light);
        color: var(--primary);
    }

    /* ── Buttons ── */
    .btn {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 6px 13px;
        font-family: var(--font);
        font-size: 12px;
        font-weight: 600;
        border-radius: var(--radius-sm);
        border: 1px solid var(--slate-300);
        background: var(--white);
        color: var(--slate-700);
        cursor: pointer;
        text-decoration: none;
        white-space: nowrap;
        transition: background 0.15s, border-color 0.15s, color 0.15s, box-shadow 0.15s;
        line-height: 1.4;
    }
    .btn:hover { background: var(--slate-100); border-color: var(--slate-400); }
    .btn:disabled, .btn[disabled] { background: var(--slate-100); color: var(--slate-400); border-color: var(--slate-200); cursor: not-allowed; pointer-events: none; }
    .btn-primary { background: var(--primary); color: var(--white); border-color: var(--primary); }
    .btn-primary:hover { background: var(--primary-hover); border-color: var(--primary-hover); }
    .btn-success { background: var(--success); color: var(--white); border-color: var(--success); }
    .btn-success:hover { background: #15803d; }
    .btn-wa { background: #25D366; color: var(--white); border-color: #1da851; }
    .btn-wa:hover { background: #1db954; }
    .btn-danger-outline { background: var(--white); color: var(--danger); border-color: #fca5a5; }
    .btn-danger-outline:hover { background: var(--danger-light); }
    .btn-sm { padding: 4px 10px; font-size: 11px; }

    /* ── Form body scroll area ── */
    .inv-body {
        flex: 1;
        overflow-y: auto;
        overflow-x: hidden;
        padding: 14px 18px 0 18px;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    /* ── Header fields row ── */
    .inv-header-row {
        display: flex;
        gap: 12px;
        align-items: stretch;
        flex-shrink: 0;
    }

    /* ── Customer card ── */
    .customer-card {
        width: 320px;
        flex-shrink: 0;
        border: 1px solid var(--slate-200);
        border-radius: var(--radius-md);
        overflow: visible;
        background: var(--white);
        box-shadow: var(--shadow-sm);
        display: flex;
        flex-direction: column;
    }
    .customer-card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 7px 12px;
        background: var(--slate-800);
        color: var(--white);
        border-radius: var(--radius-md) var(--radius-md) 0 0;
        font-size: 11px;
        font-weight: 600;
        letter-spacing: 0.5px;
        text-transform: uppercase;
    }
    .customer-card-body {
        padding: 10px;
        position: relative;
        flex: 1;
        display: flex;
        flex-direction: column;
    }
    .customer-search-input {
        width: 100%;
        padding: 7px 10px;
        border: 1px solid var(--slate-300);
        border-radius: var(--radius-sm);
        font-size: 12px;
        font-family: var(--font);
        font-weight: 600;
        color: var(--slate-800);
        box-sizing: border-box;
        background: var(--slate-50);
        transition: border-color 0.15s, box-shadow 0.15s;
        outline: none;
    }
    .customer-search-input:focus {
        border-color: var(--primary);
        background: var(--white);
        box-shadow: 0 0 0 3px rgba(37,99,235,0.12);
    }
    .customer-address-area {
        width: 100%;
        flex: 1;
        min-height: 50px;
        padding: 6px 10px;
        border: 1px solid var(--slate-200);
        border-radius: var(--radius-sm);
        font-size: 11px;
        font-family: var(--font);
        color: var(--slate-600);
        resize: none;
        box-sizing: border-box;
        background: var(--slate-50);
        margin-top: 8px;
    }
    .customer-actions {
        display: none;
        gap: 6px;
        margin-top: 8px;
    }
    .customer-outstanding {
        font-size: 11px;
        padding: 7px 10px;
        border-radius: var(--radius-sm);
        margin-top: 6px;
        display: none;
        line-height: 1.5;
    }

    /* ── Invoice meta card ── */
    .inv-meta-card {
        flex: 1;
        border: 1px solid var(--slate-200);
        border-radius: var(--radius-md);
        overflow: visible;
        background: var(--white);
        box-shadow: var(--shadow-sm);
        display: flex;
        flex-direction: column;
    }
    .inv-meta-card-header {
        padding: 7px 12px;
        background: var(--slate-800);
        color: var(--white);
        font-size: 11px;
        font-weight: 600;
        letter-spacing: 0.5px;
        text-transform: uppercase;
    }
    .inv-meta-body {
        padding: 10px 12px;
        display: flex;
        flex-direction: column;
        gap: 10px;
        flex: 1;
        justify-content: space-between;
    }
    /* Date + Invoice # row */
    .inv-id-row {
        display: flex;
        gap: 10px;
    }
    /* 5-column fields strip */
    .inv-fields-strip {
        display: flex;
        gap: 0;
        border: 1px solid var(--slate-200);
        border-radius: var(--radius-sm);
        overflow: visible;
    }
    .inv-field-col {
        flex: 1;
        border-right: 1px solid var(--slate-200);
        display: flex;
        flex-direction: column;
    }
    .inv-field-col:last-child { border-right: none; }
    .inv-field-label {
        background: var(--slate-100);
        color: var(--slate-600);
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 4px 8px;
        border-bottom: 1px solid var(--slate-200);
    }
    .inv-field-input {
        padding: 5px 8px;
        font-family: var(--font);
        font-size: 12px;
        color: var(--slate-800);
        border: none;
        background: transparent;
        width: 100%;
        box-sizing: border-box;
        outline: none;
        text-align: center;
    }
    .inv-field-input:focus { background: var(--primary-light); }
    .inv-field-select {
        padding: 5px 24px 5px 8px;
        font-family: var(--font);
        font-size: 12px;
        color: var(--slate-800);
        border: none;
        background: transparent url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 256 256'%3E%3Cpath fill='%23475569' d='M213.66,101.66l-80,80a8,8,0,0,1-11.32,0l-80-80a8,8,0,0,1,11.32-11.32L128,164.69l74.34-74.35a8,8,0,0,1,11.32,11.32Z'/%3E%3C/svg%3E") no-repeat right 8px center;
        background-size: 10px;
        width: 100%;
        box-sizing: border-box;
        outline: none;
        cursor: pointer;
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
    }

    /* ── Inline labeled field (date, invoice#) ── */
    .inv-labeled-field {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
    .inv-labeled-field label {
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: var(--slate-500, #64748b);
    }
    .inv-labeled-field input, .inv-labeled-field select {
        padding: 6px 10px;
        border: 1px solid var(--slate-300);
        border-radius: var(--radius-sm);
        font-family: var(--font);
        font-size: 12px;
        color: var(--slate-800);
        background: var(--slate-50);
        box-sizing: border-box;
        outline: none;
        transition: border-color 0.15s, box-shadow 0.15s;
        text-align: center;
    }
    .inv-labeled-field input:focus, .inv-labeled-field select:focus {
        border-color: var(--primary);
        background: var(--white);
        box-shadow: 0 0 0 3px rgba(37,99,235,0.12);
    }
    .inv-labeled-field input[readonly] { background: var(--slate-100); color: var(--slate-500, #64748b); cursor: default; }

    /* ── Item Search bar ── */
    .search-wrapper { position: relative; flex-shrink: 0; }
    .item-search-bar {
        width: 100%;
        padding: 9px 14px 9px 40px;
        border: 1.5px solid var(--slate-300);
        border-radius: var(--radius-md);
        font-size: 13px;
        font-family: var(--font);
        font-weight: 500;
        color: var(--slate-800);
        background: var(--slate-50);
        box-sizing: border-box;
        outline: none;
        transition: border-color 0.15s, box-shadow 0.15s;
    }
    .item-search-bar:focus {
        border-color: var(--primary);
        background: var(--white);
        box-shadow: 0 0 0 3px rgba(37,99,235,0.12);
    }
    .item-search-bar::placeholder { color: var(--slate-400); }
    .search-icon-prefix {
        position: absolute;
        left: 13px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--slate-400);
        font-size: 15px;
        pointer-events: none;
    }
    .search-results {
        position: absolute;
        top: calc(100% + 4px);
        left: 0;
        width: 100%;
        background: var(--white);
        border: 1px solid var(--slate-200);
        border-radius: var(--radius-md);
        max-height: 220px;
        overflow-y: auto;
        z-index: 1000;
        display: none;
        box-shadow: var(--shadow-md);
    }
    .search-results li {
        padding: 9px 14px;
        cursor: pointer;
        list-style: none;
        border-bottom: 1px solid var(--slate-100);
        font-size: 13px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: background 0.1s;
    }
    .search-results li:last-child { border-bottom: none; }
    .search-results li:hover { background: var(--primary); color: var(--white); }
    .search-results li:hover span { color: var(--white) !important; }
    .search-results li:hover .sr-price { color: var(--white) !important; }

    #searchResults, #customerSearchResults {
        width: 760px;
        max-width: calc(100vw - 32px);
    }
    #searchResults li, #customerSearchResults li {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 14px;
        width: 100%;
        box-sizing: border-box;
    }
    
    /* Product search results list item layout */
    #searchResults li .sr-name {
        flex: 2;
        min-width: 0;
        font-size: 13px;
        font-weight: 600;
        color: var(--slate-800);
        word-break: break-word;
    }
    #searchResults li .sr-sku {
        flex: 1.2;
        min-width: 0;
        font-size: 12px;
        color: var(--slate-500);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    #searchResults li .sr-sample {
        flex: 0.8;
        min-width: 0;
        font-size: 12px;
        color: var(--slate-500);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    #searchResults li .sr-stock {
        flex: 1;
        min-width: 0;
        text-align: center;
    }
    #searchResults li .sr-price {
        flex: 1;
        text-align: right;
        color: var(--primary);
        font-family: monospace;
        font-weight: 700;
        font-size: 13px;
        white-space: nowrap;
    }

    /* Customer search results list item layout */
    #customerSearchResults li .csr-name {
        flex: 1.8;
        min-width: 0;
        font-size: 13px;
        font-weight: 600;
        color: var(--slate-800);
        word-break: break-word;
    }
    #customerSearchResults li .csr-route {
        flex: 1.2;
        min-width: 0;
        font-size: 12px;
        color: var(--slate-500);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    #customerSearchResults li .csr-phone {
        flex: 1;
        min-width: 0;
        font-size: 12px;
        color: var(--slate-500);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    #customerSearchResults li .csr-address {
        flex: 1.8;
        min-width: 0;
        font-size: 12px;
        color: var(--slate-500);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    #customerSearchResults li .csr-outstanding {
        flex: 1.2;
        min-width: 0;
        font-size: 12px;
        font-weight: 600;
        text-align: right;
        white-space: nowrap;
    }
    #customerSearchResults li .csr-outstanding.has-balance {
        color: var(--danger);
    }
    #customerSearchResults li .csr-outstanding.overpaid {
        color: var(--success);
    }
    #customerSearchResults li .csr-outstanding.no-balance {
        color: var(--slate-500);
    }

    /* Hover and highlighted navigation states */
    #searchResults li:hover,
    #searchResults li.highlighted,
    #customerSearchResults li:hover,
    #customerSearchResults li.highlighted {
        background: var(--primary) !important;
        color: var(--white) !important;
    }
    #searchResults li:hover .sr-name,
    #searchResults li.highlighted .sr-name,
    #searchResults li:hover .sr-sku,
    #searchResults li.highlighted .sr-sku,
    #searchResults li:hover .sr-sample,
    #searchResults li.highlighted .sr-sample,
    #searchResults li:hover .sr-price,
    #searchResults li.highlighted .sr-price {
        color: var(--white) !important;
    }
    #customerSearchResults li:hover .csr-name,
    #customerSearchResults li.highlighted .csr-name,
    #customerSearchResults li:hover .csr-route,
    #customerSearchResults li.highlighted .csr-route,
    #customerSearchResults li:hover .csr-phone,
    #customerSearchResults li.highlighted .csr-phone,
    #customerSearchResults li:hover .csr-address,
    #customerSearchResults li.highlighted .csr-address,
    #customerSearchResults li:hover .csr-outstanding,
    #customerSearchResults li.highlighted .csr-outstanding {
        color: var(--white) !important;
    }
    #customerSearchResults li:hover i,
    #customerSearchResults li.highlighted i {
        color: var(--white) !important;
    }
    #searchResults li:hover span,
    #searchResults li.highlighted span {
        color: var(--white) !important;
        background: rgba(255, 255, 255, 0.2) !important;
    }

    /* ── Line items table ── */
    .table-scroll-container {
        flex: 1;
        overflow-y: auto;
        border: 1px solid var(--slate-200);
        border-radius: var(--radius-md);
        background: var(--white);
        box-shadow: var(--shadow-sm);
        margin-bottom: 4px;
    }
    .qb-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
    .qb-table thead th {
        position: sticky;
        top: 0;
        background: var(--slate-800);
        color: var(--white);
        padding: 8px 8px;
        text-align: left;
        font-size: 10.5px;
        font-weight: 700;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        border-right: 1px solid rgba(255,255,255,0.08);
        z-index: 10;
    }
    .qb-table thead th:last-child { border-right: none; }
    .qb-table tbody tr { transition: background 0.1s; }
    .qb-table tbody tr:nth-child(even) { background: var(--slate-50); }
    .qb-table tbody tr:hover { background: var(--primary-light); }
    .qb-table td {
        padding: 4px 6px;
        border-bottom: 1px solid var(--slate-100);
        border-right: 1px solid var(--slate-100);
        vertical-align: middle;
    }
    .qb-table td:last-child { border-right: none; }
    .qb-table input {
        width: 100%;
        border: none;
        background: transparent;
        padding: 4px 5px;
        font-size: 12px;
        font-family: var(--font);
        color: var(--slate-800);
        box-sizing: border-box;
        border-radius: 4px;
    }
    .qb-table input:focus {
        background: var(--primary-light);
        outline: 1.5px solid var(--primary);
    }
    .qb-table .num { text-align: right; }

    .discount-cell {
        display: flex;
        border: 1px solid var(--slate-200);
        border-radius: 5px;
        background: var(--white);
        overflow: hidden;
    }
    .discount-cell:focus-within {
        border-color: var(--primary);
        box-shadow: 0 0 0 2px rgba(37,99,235,0.12);
    }
    .discount-cell input { border: none; width: 60%; background: transparent; }
    .discount-cell select {
        border: none;
        border-left: 1px solid var(--slate-200);
        width: 40%;
        padding: 2px 3px;
        background: var(--slate-50);
        font-size: 11px;
        font-family: var(--font);
        color: var(--slate-700);
    }

    /* ── Footer section ── */
    .inv-footer {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        gap: 16px;
        flex-shrink: 0;
        padding: 10px 18px;
        border-top: 1px solid var(--slate-200);
        background: var(--slate-50);
    }
    .inv-footer-left { width: 340px; display: flex; flex-direction: column; gap: 8px; }
    .footer-field-label {
        display: block;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: var(--slate-500, #64748b);
        margin-bottom: 4px;
    }
    .footer-select, .footer-input {
        width: 100%;
        padding: 6px 10px;
        border: 1px solid var(--slate-300);
        border-radius: var(--radius-sm);
        font-family: var(--font);
        font-size: 12px;
        color: var(--slate-800);
        background: var(--white);
        box-sizing: border-box;
        outline: none;
        transition: border-color 0.15s;
    }
    .footer-select:focus, .footer-input:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(37,99,235,0.12);
    }

    /* ── Totals panel ── */
    .totals-panel {
        min-width: 280px;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
    .totals-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 5px 0;
    }
    .totals-row + .totals-row { border-top: 1px solid var(--slate-100); }
    .totals-label {
        font-size: 12px;
        font-weight: 500;
        color: var(--slate-600);
    }
    .totals-value {
        font-size: 13px;
        font-weight: 600;
        font-family: 'Courier New', monospace;
        color: var(--slate-800);
        min-width: 110px;
        text-align: right;
    }
    .totals-row-grand {
        padding: 7px 10px;
        background: var(--slate-800);
        border-radius: var(--radius-sm);
        margin-top: 4px;
    }
    .totals-row-grand .totals-label { color: #94a3b8; font-weight: 600; }
    .totals-row-grand .totals-value { color: var(--white); font-size: 15px; }
    .totals-row-balance {
        padding: 7px 10px;
        background: var(--primary);
        border-radius: var(--radius-sm);
    }
    .totals-row-balance .totals-label { color: rgba(255,255,255,0.8); font-weight: 600; }
    .totals-row-balance .totals-value { color: var(--white); font-size: 16px; }
    .totals-discount-row { display: flex; justify-content: flex-end; align-items: center; gap: 10px; padding: 4px 0; border-top: 1px solid var(--slate-100); }
    .totals-discount-row .totals-label { font-size: 12px; font-weight: 500; color: var(--slate-600); }

    /* ── Action bar ── */
    .inv-action-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 18px;
        border-top: 1px solid var(--slate-200);
        background: var(--white);
        border-radius: 0 0 var(--radius-lg) var(--radius-lg);
        flex-shrink: 0;
        gap: 10px;
    }
    .inv-action-status {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        font-weight: 500;
        color: var(--success);
    }
    .status-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: var(--success);
        box-shadow: 0 0 0 3px rgba(22,163,74,0.15);
    }
    .inv-action-buttons { display: flex; gap: 8px; }

    /* ── Hidden accounting fields ── */
    .acc-settings { display: none; }

    /* ── Spinner hide ── */
    .qb-wrapper input::-webkit-outer-spin-button,
    .qb-wrapper input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
    .qb-wrapper input[type=number] { -moz-appearance: textfield; }

    /* ── Flash messages ── */
    .flash-msg {
        padding: 9px 14px;
        border-radius: var(--radius-sm);
        font-weight: 600;
        font-size: 12px;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 6px;
    }
    .flash-success { background: var(--success-light); color: var(--success); border: 1px solid #bbf7d0; }
    .flash-error   { background: var(--danger-light);  color: var(--danger);  border: 1px solid #fecaca; }
    .flash-info    { background: #e0f7fa; color: #006064; border: 1px solid #b2ebf2; }

    /* ── Route badge ── */
    .route-badge {
        padding: 8px 14px;
        background: #e0f7fa;
        color: #006064;
        border: 1px solid #b2ebf2;
        border-radius: var(--radius-sm);
        font-weight: 600;
        font-size: 12px;
        display: flex;
        align-items: center;
        gap: 8px;
        flex-shrink: 0;
        margin-bottom: 6px;
    }

    /* ── New Customer Modal ── */
    .modal-backdrop-custom {
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 2000;
        position: fixed;
        top: 0; left: 0;
        width: 100%; height: 100%;
        background: rgba(15,23,42,0.55);
        backdrop-filter: blur(3px);
    }
    .modal-panel-custom {
        max-width: 500px;
        width: 92%;
        background: var(--white);
        border-radius: var(--radius-lg);
        overflow: hidden;
        box-shadow: 0 20px 50px rgba(0,0,0,0.2);
        border: 1px solid var(--slate-200);
        animation: modalIn 0.2s ease;
    }
    @keyframes modalIn {
        from { opacity: 0; transform: translateY(-10px) scale(0.98); }
        to   { opacity: 1; transform: translateY(0) scale(1); }
    }
    .modal-header-custom {
        background: var(--slate-800);
        color: var(--white);
        padding: 14px 18px;
        font-weight: 700;
        font-size: 14px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .modal-close-btn {
        background: transparent;
        border: none;
        color: var(--slate-400);
        font-size: 20px;
        cursor: pointer;
        padding: 0;
        line-height: 1;
        transition: color 0.15s;
    }
    .modal-close-btn:hover { color: var(--white); }
    .modal-body-custom {
        padding: 18px;
        display: flex;
        flex-direction: column;
        gap: 12px;
        max-height: 420px;
        overflow-y: auto;
    }
    .modal-field-label {
        display: block;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: var(--slate-600);
        margin-bottom: 5px;
    }
    .modal-input {
        width: 100%;
        padding: 7px 11px;
        border: 1px solid var(--slate-300);
        border-radius: var(--radius-sm);
        font-family: var(--font);
        font-size: 13px;
        color: var(--slate-800);
        box-sizing: border-box;
        outline: none;
        background: var(--slate-50);
        transition: border-color 0.15s, box-shadow 0.15s;
    }
    .modal-input:focus {
        border-color: var(--primary);
        background: var(--white);
        box-shadow: 0 0 0 3px rgba(37,99,235,0.12);
    }
    .modal-footer-custom {
        padding: 12px 18px;
        background: var(--slate-50);
        border-top: 1px solid var(--slate-200);
        display: flex;
        justify-content: flex-end;
        gap: 8px;
    }
    .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
</style>

<div class="qb-wrapper">
    <?php if(!empty($data['error'])): ?>
        <div class="flash-msg flash-error"><i class="ph ph-warning-circle"></i> <?= $data['error'] ?></div>
    <?php endif; ?>
    <?php if(isset($_SESSION['flash_success'])): ?>
        <div class="flash-msg flash-success"><i class="ph ph-check-circle"></i> <?= $_SESSION['flash_success']; unset($_SESSION['flash_success']); ?></div>
    <?php endif; ?>
    <?php if(isset($_SESSION['flash_error'])): ?>
        <div class="flash-msg flash-error"><i class="ph ph-warning-circle"></i> <?= $_SESSION['flash_error']; unset($_SESSION['flash_error']); ?></div>
    <?php endif; ?>

    <?php
    $rep_route_id = $_GET['rep_route_id'] ?? $_GET['route_id'] ?? (isset($inv->rep_route_id) ? $inv->rep_route_id : '');
    $rep_route_name = '';
    if (!empty($rep_route_id)) {
        try {
            $db->query("SELECT r.route_name, e.first_name, e.last_name 
                        FROM rep_daily_routes r 
                        JOIN employees e ON r.user_id = e.user_id 
                        WHERE r.id = :id");
            $db->bind(':id', $rep_route_id);
            $routeInfo = $db->single();
            if ($routeInfo) {
                $rep_route_name = $routeInfo->route_name . ' (' . $routeInfo->first_name . ' ' . $routeInfo->last_name . ')';
            }
        } catch(Exception $e) {}
    }
    ?>

    <?php if (!empty($rep_route_name)): ?>
        <div class="route-badge">
            <i class="ph ph-map-pin"></i>
            Adding Invoice to Rep Route: <strong><?= htmlspecialchars($rep_route_name) ?></strong>
        </div>
    <?php endif; ?>

    <div class="qb-container">
        <form action="<?= APP_URL ?>/sales/store" method="POST" id="invoiceForm">
            <input type="hidden" name="type" value="<?= htmlspecialchars((string)($data['type'] ?? 'invoice')) ?>">
            <input type="hidden" name="rep_route_id" value="<?= htmlspecialchars((string)$rep_route_id) ?>">
            <input type="hidden" name="from_sales_order_id" value="<?= htmlspecialchars((string)($data['from_sales_order_id'] ?? '0')) ?>">
            <input type="hidden" name="back_url" value="<?= htmlspecialchars($backUrl) ?>">
            <input type="hidden" name="mca" id="displayMca" value="<?= ($inv && isset($inv->mca)) ? htmlspecialchars((string)$inv->mca) : '' ?>">
            <input type="hidden" name="po_number" value="<?= ($inv && isset($inv->po_number)) ? htmlspecialchars((string)$inv->po_number) : '' ?>">
            <input type="hidden" name="rep_tp" id="displayPhone" value="<?= ($inv && isset($inv->rep_tp)) ? htmlspecialchars((string)$inv->rep_tp) : '' ?>">
            <?php if ($inv): ?>
                <input type="hidden" name="editing_invoice_id" value="<?= isset($inv->id) ? $inv->id : '' ?>">
            <?php endif; ?>

            <!-- ═══ TOP NAV BAR ═══ -->
            <div class="inv-topbar">
                <div class="inv-topbar-left">
                    <a href="<?= htmlspecialchars($backUrl) ?>" class="btn">
                        <i class="ph ph-arrow-left"></i> Back
                    </a>
                    <div class="inv-title"><?= htmlspecialchars($data['title'] ?? ($inv ? 'Edit Invoice' : 'New Invoice')) ?></div>
                    <span class="inv-type-badge">
                        <?= strtolower($type) === 'sales_order' ? 'Sales Order' : 'Invoice' ?>
                    </span>
                </div>
                <div class="inv-topbar-right" style="display: flex; gap: 6px; align-items: center;">
                    <?php if ($prevId): ?>
                        <a href="<?= APP_URL ?>/sales/edit/<?= $prevId ?>?type=<?= $type ?><?= !empty($rep_route_id) ? '&route_id='.$rep_route_id : '' ?>&back_url=<?= urlencode($backUrl) ?>" class="btn btn-sm">
                            <i class="ph ph-caret-left"></i> Previous
                        </a>
                    <?php else: ?>
                        <button type="button" class="btn btn-sm" disabled><i class="ph ph-caret-left"></i> Previous</button>
                    <?php endif; ?>

                    <?php if ($nextId): ?>
                        <a href="<?= APP_URL ?>/sales/edit/<?= $nextId ?>?type=<?= $type ?><?= !empty($rep_route_id) ? '&route_id='.$rep_route_id : '' ?>&back_url=<?= urlencode($backUrl) ?>" class="btn btn-sm">
                            Next <i class="ph ph-caret-right"></i>
                        </a>
                    <?php else: ?>
                        <button type="button" class="btn btn-sm" disabled>Next <i class="ph ph-caret-right"></i></button>
                    <?php endif; ?>

                    <a href="<?= APP_URL ?>/sales/create?type=<?= $type ?><?= !empty($rep_route_id) ? '&route_id='.$rep_route_id : '' ?>&back_url=<?= urlencode($backUrl) ?>" class="btn btn-sm btn-primary">
                        <i class="ph ph-plus"></i> New
                    </a>

                    <div style="width: 1px; height: 20px; background: var(--slate-200); margin: 0 4px;"></div>

                    <button type="submit" name="save_action" value="close" class="btn btn-sm btn-primary">
                        <i class="ph ph-floppy-disk"></i> Save &amp; Close
                    </button>
                    <button type="submit" name="save_action" value="new" class="btn btn-sm">
                        <i class="ph ph-plus"></i> Save &amp; New
                    </button>
                    <?php if (!$inv): ?>
                        <button type="submit" name="save_action" value="print" class="btn btn-sm">
                            <i class="ph ph-printer"></i> Save &amp; Print
                        </button>
                        <button type="submit" name="save_action" value="whatsapp" class="btn btn-sm btn-wa">
                            <i class="ph ph-whatsapp-logo"></i> Save &amp; WhatsApp
                        </button>
                    <?php endif; ?>
                    <?php if ($inv): ?>
                        <button type="button" class="btn btn-sm btn-danger" onclick="promptDeleteInvoice(<?= $inv->id ?>)">
                            <i class="ph ph-trash"></i> Delete
                        </button>
                    <?php endif; ?>
                    <button type="button" class="btn btn-sm btn-danger-outline"
                            onclick="window.location.href='<?= htmlspecialchars($backUrl) ?>'">
                        <i class="ph ph-x"></i> Cancel
                    </button>
                </div>
            </div>

            <!-- ═══ SCROLLABLE BODY ═══ -->
            <div class="inv-body">

                <!-- ── Header row: Customer + Invoice Meta ── -->
                <div class="inv-header-row">

                    <!-- Customer Card -->
                    <div class="customer-card">
                        <div class="customer-card-header">
                            <span><i class="ph ph-user" style="margin-right:5px;"></i>Bill To</span>
                            <button type="button" onclick="openNewCustomerModal()" class="btn btn-sm btn-success" style="font-size:10px; padding:3px 8px;">
                                <i class="ph ph-plus"></i> New Customer
                            </button>
                        </div>
                        <div class="customer-card-body">
                            <input type="hidden" name="customer_id" id="customerIdInput" required>
                            <input type="text" id="customerSearch" class="customer-search-input"
                                   placeholder="Search by name, route, phone, address..."
                                   autocomplete="off" required>
                            <ul id="customerSearchResults" class="search-results"></ul>
                            <textarea id="billToAddress" class="customer-address-area"
                                      readonly placeholder="Customer address will appear here..."></textarea>
                        </div>
                    </div>

                    <!-- Customer Account Card -->
                    <div class="customer-card" id="customerStatusCard" style="display: none; flex-direction: column;">
                        <div class="customer-card-header">
                            <span><i class="ph ph-cardholder" style="margin-right:5px;"></i>Customer Account</span>
                        </div>
                        <div class="customer-card-body" style="display: flex; flex-direction: column; gap: 8px; justify-content: space-between; flex: 1;">
                            <!-- Outstanding balance indicator -->
                            <div id="customerOutstanding" class="customer-outstanding" style="margin-top:0;"></div>

                            <!-- Customer action buttons -->
                            <div id="customerOptionsContainer" class="customer-actions" style="margin-top:auto; display:flex; gap:6px;">
                                <button type="button" onclick="openCustomerEdit()" class="btn btn-sm" style="flex:1; justify-content:center;">
                                    <i class="ph ph-pencil-simple"></i> Edit Profile
                                </button>
                                <button type="button" onclick="openCustomerHistory()" class="btn btn-sm" style="flex:1; justify-content:center;">
                                    <i class="ph ph-clock-counter-clockwise"></i> History
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Invoice Meta Card -->
                    <div class="inv-meta-card">
                        <div class="inv-meta-card-header">
                            <i class="ph ph-receipt" style="margin-right:5px;"></i>Invoice Details
                        </div>
                        <div class="inv-meta-body">
                            <!-- Date + Invoice Number -->
                            <div class="inv-id-row">
                                <div class="inv-labeled-field" style="flex:1;">
                                    <label>Invoice Date</label>
                                    <input type="date" name="invoice_date"
                                           value="<?= ($inv && isset($inv->invoice_date)) ? $inv->invoice_date : date('Y-m-d') ?>"
                                           required>
                                </div>
                                <div class="inv-labeled-field" style="flex:1;">
                                    <label>Invoice #</label>
                                    <input type="text" name="invoice_number"
                                           value="<?= htmlspecialchars((string)($data['invoice_number'] ?? '')) ?>"
                                           <?= $inv ? 'readonly' : '' ?> required
                                           style="<?= $inv ? 'background:var(--slate-100); color:var(--slate-500)' : '' ?>">
                                </div>
                            </div>

                            <!-- 3-field strip: Terms / Due Date / Rep -->
                            <div class="inv-fields-strip">
                                <div class="inv-field-col">
                                    <div class="inv-field-label">Terms</div>
                                    <select name="payment_term_id" id="paymentTermSelect"
                                            onchange="calculateDueDateOffset()" class="inv-field-select">
                                        <option value="">Select...</option>
                                        <?php foreach($data['payment_terms'] as $term): ?>
                                            <option value="<?= $term->id ?>" data-days="<?= $term->days_due ?>"
                                                <?= ($inv && isset($inv->payment_term_id) && $inv->payment_term_id == $term->id) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($term->name) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="inv-field-col">
                                    <div class="inv-field-label">Due Date</div>
                                    <input type="date" name="due_date" id="dueDate" class="inv-field-input"
                                           value="<?= ($inv && isset($inv->due_date)) ? $inv->due_date : date('Y-m-d') ?>"
                                           required>
                                </div>
                                <div class="inv-field-col">
                                    <div class="inv-field-label">Rep</div>
                                    <select name="rep_name" class="inv-field-select">
                                        <option value="">Select Rep...</option>
                                        <?php foreach($reps as $rep): ?>
                                            <option value="<?= htmlspecialchars((string)($rep->first_name . ' ' . $rep->last_name)) ?>"
                                                <?= ($inv && isset($inv->rep_name) && trim((string)$inv->rep_name) == trim((string)($rep->first_name . ' ' . $rep->last_name))) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars((string)($rep->first_name . ' ' . $rep->last_name)) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── Item Catalog Search ── -->
                <div class="search-wrapper">
                    <i class="ph ph-magnifying-glass search-icon-prefix"></i>
                    <input type="text" id="itemSearch" class="item-search-bar"
                           placeholder="Search catalog by item code, name, SKU, category, or sample code..."
                           autocomplete="off">
                    <ul id="searchResults" class="search-results"></ul>
                </div>

                <!-- ── Line Items Table ── -->
                <div class="table-scroll-container">
                    <table class="qb-table">
                        <thead>
                            <tr>
                                <th style="width:32px; text-align:center;">#</th>
                                <th style="width:11%;">Item Code</th>
                                <th style="width:7%; text-align:center;">Qty</th>
                                <th style="width:31%;">Description</th>
                                <th style="width:13%; text-align:right;">Rate (Rs)</th>
                                <th style="width:14%; text-align:right;">Discount</th>
                                <th style="width:14%; text-align:right;">Amount (Rs)</th>
                                <th style="width:30px; background:#7f1d1d;"></th>
                            </tr>
                        </thead>
                        <tbody id="invoiceBody">
                            <!-- JavaScript prepends rows here -->
                        </tbody>
                    </table>
                </div>

            </div><!-- /.inv-body -->

            <!-- ═══ FOOTER ═══ -->
            <div class="inv-footer">
                <!-- Left: Message + Memo -->
                <div class="inv-footer-left">
                    <div>
                        <label class="footer-field-label">Customer Message</label>
                        <select class="footer-select">
                            <option value="">Thank you for your business.</option>
                            <option value="">Please remit payment at your earliest convenience.</option>
                        </select>
                    </div>
                    <div>
                        <label class="footer-field-label">Memo / Notes</label>
                        <input type="text" name="notes" class="footer-input"
                               value="<?= ($inv && isset($inv->notes)) ? htmlspecialchars((string)$inv->notes) : '' ?>"
                               placeholder="Internal note or message to customer...">
                    </div>
                </div>

                <!-- Right: Totals panel -->
                <div class="totals-panel">
                    <div class="totals-row">
                        <span class="totals-label">Subtotal</span>
                        <span class="totals-value" id="subTotal">0.00</span>
                    </div>
                    <div class="totals-discount-row">
                        <span class="totals-label">Bill Discount</span>
                        <div class="discount-cell" style="width:130px;">
                            <input type="number" name="global_discount_val" id="globalDiscVal"
                                   value="<?= ($inv && isset($inv->global_discount_val)) ? floatval($inv->global_discount_val) : '0' ?>"
                                   class="num" style="border:none;" oninput="calcTotals()">
                            <select name="global_discount_type" id="globalDiscType" onchange="calcTotals()">
                                <option value="Rs" <?= ($inv && isset($inv->global_discount_type) && $inv->global_discount_type == 'Rs') ? 'selected' : '' ?>>Rs</option>
                                <option value="%" <?= ($inv && isset($inv->global_discount_type) && $inv->global_discount_type == '%') ? 'selected' : '' ?>>%</option>
                            </select>
                        </div>
                    </div>
                    <div class="totals-row totals-row-grand">
                        <span class="totals-label">Total LKR</span>
                        <span class="totals-value" id="grandTotal">0.00</span>
                    </div>
                    <div class="totals-row totals-row-balance">
                        <span class="totals-label">Total with Balance LKR</span>
                        <span class="totals-value" id="balanceDue">0.00</span>
                    </div>
                </div>
            </div>

            <!-- Hidden accounting fields -->
            <div class="acc-settings">
                <select name="ar_account" required>
                    <?php foreach($assets ?? [] as $acc): ?>
                        <option value="<?= $acc->id ?>" <?= strpos(strtolower((string)($acc->account_name ?? '')), 'receivable') !== false ? 'selected' : '' ?>>
                            <?= htmlspecialchars((string)($acc->account_name ?? '')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select name="revenue_account" required>
                    <?php foreach($revenues ?? [] as $acc): ?>
                        <option value="<?= $acc->id ?>"><?= htmlspecialchars((string)($acc->account_name ?? '')) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>



        </form>
    </div>
</div>

<!-- ═══ NEW CUSTOMER MODAL ═══ -->
<div class="modal-backdrop-custom" id="newCustomerModal">
    <div class="modal-panel-custom">
        <div class="modal-header-custom">
            <span><i class="ph ph-user-plus" style="margin-right:8px;"></i>Register New Customer</span>
            <button type="button" class="modal-close-btn" onclick="closeNewCustomerModal()">
                <i class="ph ph-x"></i>
            </button>
        </div>
        <form id="ajaxNewCustomerForm" onsubmit="submitAjaxNewCustomer(event)" style="margin:0;">
            <div class="modal-body-custom">
                <div>
                    <label class="modal-field-label">Customer / Company Name <span style="color:#dc2626;">*</span></label>
                    <input type="text" name="name" required class="modal-input" placeholder="e.g. ABC Enterprises">
                </div>
                <div class="two-col">
                    <div>
                        <label class="modal-field-label">Phone Number</label>
                        <input type="text" name="phone" class="modal-input" placeholder="+94 77 000 0000">
                    </div>
                    <div>
                        <label class="modal-field-label">WhatsApp Number</label>
                        <input type="text" name="whatsapp" class="modal-input" placeholder="+94 77 000 0000">
                    </div>
                </div>
                <div class="two-col">
                    <div>
                        <label class="modal-field-label">Email Address</label>
                        <input type="email" name="email" class="modal-input" placeholder="name@company.com">
                    </div>
                    <div>
                        <label class="modal-field-label">Route / Area (MCA)</label>
                        <select name="mca_id" class="modal-input" style="cursor:pointer;">
                            <option value="">Select Area...</option>
                            <?php foreach($mcaAreas as $area): ?>
                                <option value="<?= $area->id ?>"><?= htmlspecialchars($area->name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="modal-field-label">Billing Address</label>
                    <textarea name="address" class="modal-input" style="height:64px; resize:none;" placeholder="Street, City..."></textarea>
                </div>
                <input type="hidden" name="latitude" value="">
                <input type="hidden" name="longitude" value="">
            </div>
            <div class="modal-footer-custom">
                <button type="button" class="btn" onclick="closeNewCustomerModal()">Cancel</button>
                <button type="submit" class="btn btn-success">
                    <i class="ph ph-user-plus"></i> Add Customer
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ═══ DELETE INVOICE MODAL ═══ -->
<div class="modal-backdrop-custom" id="deleteInvoiceModal">
    <div class="modal-panel-custom" style="max-width: 400px;">
        <div class="modal-header-custom" style="background: var(--danger); color: var(--white); display: flex; justify-content: space-between; align-items: center; padding: 12px 18px; border-top-left-radius: inherit; border-top-right-radius: inherit;">
            <span style="font-weight: 600;"><i class="ph ph-warning-octagon" style="margin-right:5px;"></i>Delete Invoice</span>
            <button type="button" class="modal-close-btn" onclick="closeDeleteInvoiceModal()" style="color: var(--white); opacity: 0.8; border: none; background: transparent; cursor: pointer; font-size: 16px;">
                <i class="ph ph-x"></i>
            </button>
        </div>
        <form id="deleteInvoiceForm" method="POST" style="margin:0;">
            <div class="modal-body-custom" style="padding: 18px;">
                <p style="font-size: 13px; color: var(--slate-700); margin-bottom: 12px; line-height: 1.5;">
                    Are you sure you want to delete this invoice? This will reverse all stock and ledger entries. <strong>This action cannot be undone.</strong>
                </p>
                <div style="margin-bottom: 10px;">
                    <label class="modal-field-label" style="display: block; font-size: 11px; font-weight: 600; color: var(--slate-500); margin-bottom: 4px; text-transform: uppercase;">Admin Password</label>
                    <input type="password" name="password" id="deletePasswordInput" required class="modal-input" placeholder="Enter password to confirm">
                </div>
                <div style="margin-bottom: 0;">
                    <label class="modal-field-label" style="display: block; font-size: 11px; font-weight: 600; color: var(--slate-500); margin-bottom: 4px; text-transform: uppercase;">Reason for Deletion</label>
                    <input type="text" name="delete_reason" id="deleteReasonInput" required class="modal-input" placeholder="e.g. Incorrect items or quantity">
                </div>
            </div>
            <div class="modal-footer-custom" style="display: flex; gap: 8px; justify-content: flex-end; padding: 12px 18px; background: var(--slate-50); border-bottom-left-radius: inherit; border-bottom-right-radius: inherit;">
                <button type="button" class="btn" onclick="closeDeleteInvoiceModal()">Cancel</button>
                <button type="button" class="btn btn-danger" onclick="submitDeleteInvoice()">Confirm Delete</button>
            </div>
        </form>
    </div>
</div>

<?php if(isset($_GET['wa_id'])): ?>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const waPhone = "<?= htmlspecialchars($_GET['wa_phone']) ?>";
        const waName = "<?= htmlspecialchars($_GET['wa_name']) ?>";
        const waId = "<?= htmlspecialchars($_GET['wa_id']) ?>";
        const publicInvoiceLink = "<?= APP_URL ?>/sales/show/" + waId;

        if (waPhone && waPhone.trim() !== '') {
            let cleanPhone = waPhone.replace(/[^\d+]/g, '');
            if(cleanPhone.startsWith('0')) {
                cleanPhone = '94' + cleanPhone.substring(1);
            }
            const waMessage = `Dear ${waName},\n\nPlease find your invoice at the link below:\n${publicInvoiceLink}\n\nKindly review it and let us know if you have any questions.\n\nThank you.`;
            const waUrl = `https://wa.me/${cleanPhone}?text=${encodeURIComponent(waMessage)}`;
            window.open(waUrl, '_blank');
        } else {
            alert("This customer does not have a valid WhatsApp or Phone number saved in their profile.");
        }
    });
</script>
<?php endif; ?>

<?php if(isset($_GET['print_id'])): ?>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const printId = "<?= htmlspecialchars($_GET['print_id']) ?>";
        const printUrl = "<?= APP_URL ?>/sales/show/" + printId;
        window.open(printUrl, '_blank');
    });
</script>
<?php endif; ?>

<script>
    // Enhanced Catalog Data Engine providing Stock counts adjusted for reservation values
    const catalog = [
        <?php foreach($catalog_items as $item): ?>
            <?php
            if (isset($item->status) && strtolower(trim((string)$item->status)) === 'inactive') {
                continue;
            }
            $hasVars = !empty($item->variations);
            $stockQty = isset($item->qty) ? $item->qty : ($item->quantity_on_hand ?? 0);
            $baseAvailable = $stockQty - ($item->quantity_reserved ?? 0);
            $catName = htmlspecialchars(addslashes((string)($item->category_name ?? '')));
            $sampleCode = htmlspecialchars(addslashes((string)($item->sample_code ?? '')));
            ?>
            <?php if($hasVars): ?>
                { id: "<?= $item->id ?>|MIX|1", type: "<?= $item->type ?? 'Inventory' ?>", stock: <?= floatval($baseAvailable) ?>, code: "<?= htmlspecialchars((string)($item->item_code ?? '')) ?>", name: "<?= htmlspecialchars(addslashes((string)($item->name ?? ''))) ?> (MIX)", price: <?= floatval($item->price ?? 0) ?>, category: "<?= $catName ?>", sample_code: "<?= $sampleCode ?>" },
                <?php foreach($item->variations as $var): ?>
                <?php $varAvailable = ($var->quantity_on_hand ?? 0) - ($var->quantity_reserved ?? 0); ?>
                { id: "<?= $item->id ?>|<?= $var->id ?>|0", type: "<?= $item->type ?? 'Inventory' ?>", stock: <?= floatval($varAvailable) ?>, code: "<?= htmlspecialchars(addslashes((string)($var->sku ?? $item->item_code ?? ''))) ?>", name: "<?= htmlspecialchars(addslashes((string)($item->name ?? ''))) ?> - <?= htmlspecialchars(addslashes((string)($var->variation_name ?? ''))) ?>: <?= htmlspecialchars(addslashes((string)($var->value_name ?? ''))) ?>", price: <?= floatval(isset($var->price) && $var->price > 0 ? $var->price : ($item->price ?? 0)) ?>, category: "<?= $catName ?>", sample_code: "<?= $sampleCode ?>" },
                <?php endforeach; ?>
            <?php else: ?>
                { id: "<?= $item->id ?>|0|0", type: "<?= $item->type ?? 'Inventory' ?>", stock: <?= floatval($baseAvailable) ?>, code: "<?= htmlspecialchars((string)($item->item_code ?? '')) ?>", name: "<?= htmlspecialchars(addslashes((string)($item->name ?? ''))) ?>", price: <?= floatval($item->price ?? 0) ?>, category: "<?= $catName ?>", sample_code: "<?= $sampleCode ?>" },
            <?php endif; ?>
        <?php endforeach; ?>
    ];

    // Customizable Discount Rules Engine Configuration
    const activeDiscountRules = <?= json_encode($data['active_discount_rules'] ?? []) ?>;
    const promptedDiscounts = { itemRules: {}, billRules: {} };

    const customers = [
        <?php foreach($customers as $c): ?>
        {
            id: "<?= $c->id ?>",
            name: <?= json_encode((string)($c->name ?? '')) ?>,
            phone: <?= json_encode((string)($c->phone ?? '')) ?>,
            address: <?= json_encode((string)($c->address ?? '')) ?>,
            mca: <?= json_encode((string)($c->mca_name ?? $c->territory ?? '')) ?>,
            outstanding: <?= floatval($c->outstanding_balance ?? 0) ?>,
            credit_limit: <?= floatval($c->credit_limit ?? 0) ?>
        },
        <?php endforeach; ?>
    ];

    function escapeHtml(str) {
        if (!str) return '';
        return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
    }

    let activeCustomerSearchIndex = -1;

    function highlightCustomerSearchItem(items) {
        items.forEach((item, index) => {
            if (index === activeCustomerSearchIndex) {
                item.classList.add('highlighted');
                item.scrollIntoView({ block: 'nearest' });
            } else {
                item.classList.remove('highlighted');
            }
        });
    }

    function renderCustomerSearch(query) {
        const val = query.toLowerCase().trim();
        const resList = document.getElementById('customerSearchResults');
        resList.innerHTML = '';
        if(!val) { resList.style.display = 'none'; return; }

        const filtered = customers.filter(c =>
            c.name.toLowerCase().includes(val) ||
            c.phone.toLowerCase().includes(val) ||
            c.mca.toLowerCase().includes(val) ||
            c.address.toLowerCase().includes(val)
        ).slice(0, 10);

        if(filtered.length === 0) {
            const li = document.createElement('li');
            li.className = 'no-results';
            li.style.padding = '10px 14px';
            li.style.color = '#94a3b8';
            li.innerText = 'No customers found';
            resList.appendChild(li);
            resList.style.display = 'block';
            return;
        }

        filtered.forEach((cust, index) => {
            const li = document.createElement('li');
            li.setAttribute('data-index', index);
            
            const balanceVal = parseFloat(cust.outstanding) || 0;
            let balanceText = 'Rs. 0.00';
            let balanceClass = 'no-balance';
            if (balanceVal > 0) {
                balanceText = 'Rs. ' + balanceVal.toLocaleString('en-IN', {minimumFractionDigits:2, maximumFractionDigits:2});
                balanceClass = 'has-balance';
            } else if (balanceVal < 0) {
                balanceText = 'Rs. ' + Math.abs(balanceVal).toLocaleString('en-IN', {minimumFractionDigits:2, maximumFractionDigits:2}) + ' (CR)';
                balanceClass = 'overpaid';
            }

            li.innerHTML = `
                <div class="csr-name">${escapeHtml(cust.name)}</div>
                <div class="csr-route"><i class="ph ph-map-pin"></i> ${escapeHtml(cust.mca || 'No Route')}</div>
                <div class="csr-phone">${cust.phone ? `<i class="ph ph-phone"></i> ${escapeHtml(cust.phone)}` : 'N/A'}</div>
                <div class="csr-address">${cust.address ? `<i class="ph ph-house"></i> ${escapeHtml(cust.address)}` : 'N/A'}</div>
                <div class="csr-outstanding ${balanceClass}">Bal: ${balanceText}</div>
            `;
            li.addEventListener('click', () => { selectCustomer(cust); });
            resList.appendChild(li);
        });
        resList.style.display = 'block';
    }

    let selectedCustomerObj = null;

    function showCreditLimitBlockDialog(customerName, creditLimit, projected, exceeded) {
        const backdrop = document.createElement('div');
        backdrop.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.6);backdrop-filter:blur(4px);z-index:10000;display:flex;align-items:center;justify-content:center;';

        const panel = document.createElement('div');
        panel.style.cssText = 'background:#fff;padding:28px;border-radius:14px;box-shadow:0 20px 50px rgba(0,0,0,0.2);width:90%;max-width:450px;font-family:var(--font,system-ui);border:1px solid #fee2e2;';

        panel.innerHTML = `
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;">
                <div style="background:#fee2e2;color:#ef4444;width:42px;height:42px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0;"><i class="fa-solid fa-ban"></i></div>
                <h3 style="margin:0;color:#991b1b;font-size:17px;font-weight:700;">Credit Limit Exceeded</h3>
            </div>
            <div style="font-size:13px;color:#374151;line-height:1.6;margin-bottom:22px;">
                <p style="margin:0 0 12px 0;">This transaction is blocked. Finalizing will exceed the credit limit for <strong>${escapeHtml(customerName)}</strong>.</p>
                <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:14px;display:grid;gap:8px;font-family:monospace;">
                    <div style="display:flex;justify-content:space-between;"><span style="color:#6b7280;">Credit Limit:</span><span style="font-weight:bold;color:#111827;">Rs. ${creditLimit.toLocaleString('en-IN',{minimumFractionDigits:2,maximumFractionDigits:2})}</span></div>
                    <div style="display:flex;justify-content:space-between;"><span style="color:#6b7280;">Projected Balance:</span><span style="font-weight:bold;color:#991b1b;">Rs. ${projected.toLocaleString('en-IN',{minimumFractionDigits:2,maximumFractionDigits:2})}</span></div>
                    <hr style="border:0;border-top:1px solid #e5e7eb;margin:4px 0;">
                    <div style="display:flex;justify-content:space-between;font-size:12px;color:#b91c1c;font-weight:bold;"><span>Exceeded By:</span><span>Rs. ${exceeded.toLocaleString('en-IN',{minimumFractionDigits:2,maximumFractionDigits:2})}</span></div>
                </div>
            </div>
            <div style="display:flex;justify-content:flex-end;">
                <button type="button" id="creditDismissBtn" style="padding:8px 20px;background:#1e293b;color:#fff;border:none;border-radius:6px;font-weight:600;cursor:pointer;font-size:13px;">Dismiss</button>
            </div>
        `;
        panel.querySelector('#creditDismissBtn').onclick = () => backdrop.remove();
        backdrop.appendChild(panel);
        document.body.appendChild(backdrop);
    }

    function updateProjectedOutstandingIndicator() {
        if (!selectedCustomerObj) return;

        const outBal = parseFloat(selectedCustomerObj.outstanding) || 0;
        const limit = parseFloat(selectedCustomerObj.credit_limit) || 0;
        const outDiv = document.getElementById('customerOutstanding');
        if (!outDiv) return;

        const subTotalText = document.getElementById('grandTotal').innerText.replace(/,/g, '');
        const newTotal = parseFloat(subTotalText) || 0;

        let oldInvoiceTotal = 0;
        <?php if ($inv): ?>
            const oldSub = parseFloat("<?= isset($inv->total_amount) ? $inv->total_amount : 0 ?>") || 0;
            const oldDiscVal = parseFloat("<?= isset($inv->global_discount_val) ? $inv->global_discount_val : 0 ?>") || 0;
            const oldDiscType = "<?= isset($inv->global_discount_type) ? $inv->global_discount_type : 'Rs' ?>";
            const oldDisc = (oldDiscType === '%') ? (oldSub * oldDiscVal / 100) : oldDiscVal;
            oldInvoiceTotal = (oldSub - oldDisc) + (parseFloat("<?= isset($inv->tax_amount) ? $inv->tax_amount : 0 ?>") || 0);
        <?php endif; ?>

        const projected = outBal - oldInvoiceTotal + newTotal;
        outDiv.style.display = 'block';
        const balDueEl = document.getElementById('balanceDue');
        if (balDueEl) {
            balDueEl.innerText = projected.toLocaleString('en-IN', {minimumFractionDigits:2, maximumFractionDigits:2});
        }

        let html = '';
        if (limit > 0) {
            html += `<div style="margin-bottom:3px;"><strong>Credit Limit:</strong> Rs. ${limit.toLocaleString('en-IN',{minimumFractionDigits:2,maximumFractionDigits:2})}</div>`;
        } else {
            html += `<div style="margin-bottom:3px;"><strong>Credit Limit:</strong> <span style="color:#16a34a;font-weight:700;">No Limit</span></div>`;
        }

        if (outBal > 0.01) {
            html += `<div style="color:#dc2626;"><i class="ph ph-warning"></i> Outstanding: Rs. ${outBal.toLocaleString('en-IN',{minimumFractionDigits:2,maximumFractionDigits:2})}</div>`;
        } else if (outBal < -0.01) {
            html += `<div style="color:#16a34a;"><i class="ph ph-check"></i> Overpaid Credit: Rs. ${Math.abs(outBal).toLocaleString('en-IN',{minimumFractionDigits:2,maximumFractionDigits:2})}</div>`;
        } else {
            html += `<div style="color:#64748b;">No Outstanding Balance</div>`;
        }

        if (limit > 0) {
            const remaining = limit - projected;
            if (remaining >= 0) {
                html += `<div style="color:#16a34a;font-weight:700;margin-top:4px;padding-top:4px;border-top:1px solid #d1fae5;">Projected Remaining Credit: Rs. ${remaining.toLocaleString('en-IN',{minimumFractionDigits:2,maximumFractionDigits:2})}</div>`;
                outDiv.style.background = '#f0fdf4';
                outDiv.style.color = '#166534';
                outDiv.style.border = '1px solid #bbf7d0';
            } else {
                html += `<div style="color:#dc2626;font-weight:700;margin-top:4px;padding-top:4px;border-top:1px solid #fecaca;"><i class="ph ph-x-circle"></i> Exceeds Limit By: Rs. ${Math.abs(remaining).toLocaleString('en-IN',{minimumFractionDigits:2,maximumFractionDigits:2})}</div>`;
                outDiv.style.background = '#fef2f2';
                outDiv.style.color = '#dc2626';
                outDiv.style.border = '1px solid #fecaca';
            }
        } else {
            outDiv.style.background = '#f1f5f9';
            outDiv.style.color = '#334155';
            outDiv.style.border = '1px solid #e2e8f0';
        }

        outDiv.innerHTML = html;
    }

    function selectCustomer(cust) {
        selectedCustomerObj = cust;
        document.getElementById('customerIdInput').value = cust.id;
        document.getElementById('customerSearch').value = cust.name;
        document.getElementById('customerSearchResults').style.display = 'none';
        document.getElementById('billToAddress').value = cust.address || '';

        const mcaInput = document.getElementById('displayMca');
        if(mcaInput) mcaInput.value = cust.mca || '';

        const phoneInput = document.getElementById('displayPhone');
        if(phoneInput) phoneInput.value = cust.phone || '';

        const optsContainer = document.getElementById('customerOptionsContainer');
        if(optsContainer) optsContainer.style.display = 'flex';

        const statusCard = document.getElementById('customerStatusCard');
        if(statusCard) statusCard.style.display = 'flex';

        updateProjectedOutstandingIndicator();
    }

    function openCustomerEdit() {
        if (selectedCustomerObj && selectedCustomerObj.id) {
            window.open('<?= APP_URL ?>/customer/index/' + selectedCustomerObj.id + '?tab=profile', '_blank');
        }
    }

    function openCustomerHistory() {
        if (selectedCustomerObj && selectedCustomerObj.id) {
            window.open('<?= APP_URL ?>/customer/index/' + selectedCustomerObj.id + '?tab=invoices', '_blank');
        }
    }

    let activeSearchIndex = -1;

    document.addEventListener("DOMContentLoaded", function() {
        const itemSearch = document.getElementById('itemSearch');
        const resList = document.getElementById('searchResults');
        if (!itemSearch || !resList) return;

        itemSearch.addEventListener('input', function() {
            const val = this.value.toLowerCase().trim();
            resList.innerHTML = '';
            activeSearchIndex = -1;
            if(!val) { resList.style.display = 'none'; return; }

            const filtered = catalog.filter(i =>
                i.name.toLowerCase().includes(val) ||
                i.code.toLowerCase().includes(val) ||
                (i.category && i.category.toLowerCase().includes(val)) ||
                (i.sample_code && i.sample_code.toLowerCase().includes(val))
            ).slice(0, 15);

            if(filtered.length === 0) { resList.style.display = 'none'; return; }

            filtered.forEach((item, index) => {
                const li = document.createElement('li');
                li.setAttribute('data-index', index);

                let stockBadge = '';
                if (item.type === 'Service') {
                    stockBadge = `<span style="color:#2563eb;font-size:10px;font-weight:700;background:#eff6ff;padding:1px 6px;border-radius:99px;">Service</span>`;
                } else if (item.stock > 0) {
                    stockBadge = `<span style="color:#16a34a;font-size:10px;font-weight:700;background:#f0fdf4;padding:1px 6px;border-radius:99px;">Stock: ${item.stock}</span>`;
                } else {
                    stockBadge = `<span style="color:#dc2626;font-size:10px;font-weight:700;background:#fef2f2;padding:1px 6px;border-radius:99px;">Out of Stock</span>`;
                }

                li.innerHTML = `
                    <div class="sr-name">${item.name}</div>
                    <div class="sr-sku">SKU: ${item.code || 'N/A'}</div>
                    <div class="sr-sample">${item.sample_code ? 'Sample: ' + item.sample_code : ''}</div>
                    <div class="sr-stock">${stockBadge}</div>
                    <div class="sr-price">Rs ${item.price.toFixed(2)}</div>
                `;
                li.addEventListener('click', function() { selectSearchItem(item); });
                resList.appendChild(li);
            });
            resList.style.display = 'block';
        });

        itemSearch.addEventListener('keydown', function(e) {
            const items = resList.querySelectorAll('li');
            if (items.length === 0) return;
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                activeSearchIndex++;
                if (activeSearchIndex >= items.length) activeSearchIndex = 0;
                highlightSearchItem(items);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                activeSearchIndex--;
                if (activeSearchIndex < 0) activeSearchIndex = items.length - 1;
                highlightSearchItem(items);
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (activeSearchIndex >= 0 && activeSearchIndex < items.length) {
                    items[activeSearchIndex].click();
                } else if (items.length > 0) {
                    items[0].click();
                }
            }
        });

        // Customer Search Event Listeners & Keyboard Navigation
        const customerSearch = document.getElementById('customerSearch');
        const custResList = document.getElementById('customerSearchResults');
        if (customerSearch && custResList) {
            customerSearch.addEventListener('input', function() {
                activeCustomerSearchIndex = -1;
                renderCustomerSearch(this.value);
            });
            customerSearch.addEventListener('keydown', function(e) {
                const items = custResList.querySelectorAll('li:not(.no-results)');
                if (items.length === 0) return;
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    activeCustomerSearchIndex++;
                    if (activeCustomerSearchIndex >= items.length) activeCustomerSearchIndex = 0;
                    highlightCustomerSearchItem(items);
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    activeCustomerSearchIndex--;
                    if (activeCustomerSearchIndex < 0) activeCustomerSearchIndex = items.length - 1;
                    highlightCustomerSearchItem(items);
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    if (activeCustomerSearchIndex >= 0 && activeCustomerSearchIndex < items.length) {
                        items[activeCustomerSearchIndex].click();
                    } else if (items.length > 0) {
                        items[0].click();
                    }
                }
            });
        }
    });

    function highlightSearchItem(items) {
        items.forEach((item, index) => {
            if (index === activeSearchIndex) {
                item.classList.add('highlighted');
                item.scrollIntoView({ block: 'nearest' });
            } else {
                item.classList.remove('highlighted');
            }
        });
    }

    function selectSearchItem(item) {
        if (item.type !== 'Service' && item.stock <= 0) {
            alert("Cannot add item! It is currently out of stock.");
            return;
        }
        addItemRow(item);
        const itemSearch = document.getElementById('itemSearch');
        if (itemSearch) itemSearch.value = '';
        document.getElementById('searchResults').style.display = 'none';
        activeSearchIndex = -1;
    }

    function openNewCustomerModal() {
        document.getElementById('newCustomerModal').style.display = 'flex';
        const form = document.getElementById('ajaxNewCustomerForm');
        if (form) form.reset();
    }

    function closeNewCustomerModal() {
        document.getElementById('newCustomerModal').style.display = 'none';
    }

    function submitAjaxNewCustomer(e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);

        fetch('<?= APP_URL ?>/customer/api_add_customer', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                const newCust = {
                    id: String(data.customer.id),
                    name: data.customer.name,
                    phone: data.customer.phone,
                    address: data.customer.address,
                    mca: data.customer.mca || '',
                    outstanding: 0.00
                };
                customers.push(newCust);
                selectCustomer(newCust);
                closeNewCustomerModal();
                alert("Customer added and selected successfully!");
            } else {
                alert("Error: " + data.message);
            }
        })
        .catch(err => {
            alert("Connection error! Failed to register customer.");
            console.error(err);
        });
    }

    document.addEventListener('click', function(e) {
        if(e.target.id !== 'itemSearch') { document.getElementById('searchResults').style.display = 'none'; }
        if(e.target.id !== 'customerSearch') { document.getElementById('customerSearchResults').style.display = 'none'; }
    });

    function validateQty(input, maxStock) {
        if (maxStock !== null) {
            let val = parseFloat(input.value);
            if (val > maxStock) {
                alert("Insufficient inventory! Available stock for this item is: " + maxStock);
                input.value = maxStock;
            }
        }
    }

    function renumberInvoiceRows() {
        document.querySelectorAll('#invoiceBody tr').forEach((tr, i) => {
            const cell = tr.querySelector('.line-row-num');
            if (cell) cell.textContent = i + 1;
        });
    }

    function addItemRow(itemOrId, code = null, qty = 1, desc = null, price = null, discVal = 0, discType = 'Rs') {
        let item = {};
        let isDuplicate = false;
        if (typeof itemOrId === 'object' && itemOrId !== null) {
            item = itemOrId;
            qty = 1;
            desc = item.name;
            price = item.price;
            discVal = 0;
            discType = 'Rs';

            document.querySelectorAll('input[name="item_selection[]"]').forEach(input => {
                if (input.value === item.id) isDuplicate = true;
            });
        } else {
            let matched = catalog.find(c => c.id === itemOrId);
            item = {
                id: itemOrId,
                code: code,
                name: desc,
                price: price,
                stock: matched ? matched.stock : 9999,
                type: matched ? matched.type : 'Inventory'
            };
        }

        const tbody = document.getElementById('invoiceBody');
        const tr = document.createElement('tr');

        let stockHtml = item.type === 'Service' ? '' : `<br><span style="font-size:10px;color:#94a3b8;">Stock: ${item.stock}</span>`;
        let maxAttr = item.type === 'Service' ? '' : `max="${item.stock}"`;

        console.log("[addItemRow]", { item_id: item.id, sku: item.code || code, quantity: qty, name: desc, price_parsed: parseFloat(price).toFixed(2), item_discount: discVal, discount_type: discType });

        tr.innerHTML = `
            <td class="line-row-num" style="text-align:center;color:#94a3b8;font-weight:700;vertical-align:middle;font-size:11px;"></td>
            <td>
                <input type="text" value="${item.code || 'ITEM'}" readonly style="color:#64748b;font-size:11px;width:100%;border:none;background:transparent;">
                <input type="hidden" name="item_selection[]" value="${item.id}">
            </td>
            <td style="text-align:center;">
                <input type="number" class="num" name="qty[]" value="${qty}" min="0.01" step="0.01" ${maxAttr}
                       oninput="validateQty(this, ${item.type === 'Service' ? 'null' : item.stock}); calcTotals()"
                       onkeydown="handleQtyKeydown(event, this)" required style="width:60px;margin-bottom:2px;">
                ${stockHtml}
            </td>
            <td><input type="text" name="desc[]" value="${desc}" required style="width:100%;border:none;background:transparent;"></td>
            <td><input type="number" class="num" name="price[]" value="${parseFloat(price).toFixed(2)}" step="0.01" min="0" oninput="calcTotals()" required style="width:100%;text-align:right;box-sizing:border-box;padding-right:4px;"></td>
            <td>
                <div class="discount-cell">
                    <input type="number" class="num" name="item_discount_val[]" value="${parseFloat(discVal).toFixed(2)}" step="0.01" oninput="calcTotals()">
                    <select name="item_discount_type[]" onchange="calcTotals()">
                        <option value="Rs" ${discType === 'Rs' ? 'selected' : ''}>Rs</option>
                        <option value="%" ${discType === '%' ? 'selected' : ''}>%</option>
                    </select>
                </div>
            </td>
            <td><input type="text" class="num line-total" value="0.00" readonly style="font-weight:700;background:transparent;border:none;width:100%;text-align:right;padding-right:4px;color:#1e293b;"></td>
            <td style="text-align:center;">
                <button type="button" tabindex="-1" onclick="this.closest('tr').remove(); renumberInvoiceRows(); calcTotals();"
                        style="background:transparent;color:#dc2626;border:none;cursor:pointer;font-size:16px;padding:4px;line-height:1;border-radius:4px;"
                        title="Remove line">&times;</button>
            </td>
        `;
        tbody.insertAdjacentElement('afterbegin', tr);
        renumberInvoiceRows();

        const qtyInput = tr.querySelector('input[name="qty[]"]');
        validateQty(qtyInput, item.type === 'Service' ? null : item.stock);
        calcTotals();
        document.querySelector('.table-scroll-container').scrollTop = 0;

        if (typeof itemOrId === 'object' && itemOrId !== null) {
            setTimeout(() => { if (qtyInput) { qtyInput.focus(); qtyInput.select(); } }, 50);
        }

        if (isDuplicate) {
            showDuplicateWarning(item.name, null, () => {
                tr.remove();
                renumberInvoiceRows();
                calcTotals();
            });
        }
    }

    function showDuplicateWarning(itemName, onKeep, onRemove) {
        const backdrop = document.createElement('div');
        backdrop.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:9999;display:flex;align-items:center;justify-content:center;';

        const panel = document.createElement('div');
        panel.style.cssText = 'background:#fff;padding:28px;border-radius:12px;box-shadow:0 20px 50px rgba(0,0,0,0.2);width:90%;max-width:400px;text-align:center;font-family:system-ui,sans-serif;';

        const title = document.createElement('h3');
        title.innerHTML = '<i class="ph ph-warning" style="color:#d97706;font-size:20px;"></i> Duplicate Product';
        title.style.cssText = 'margin:0 0 12px 0;color:#d97706;font-size:17px;';

        const msg = document.createElement('p');
        msg.innerHTML = `<strong>${itemName}</strong> is already on this invoice.<br><br>Keep both lines or remove the duplicate?`;
        msg.style.cssText = 'margin:0 0 22px 0;color:#475569;font-size:14px;line-height:1.6;';

        const actions = document.createElement('div');
        actions.style.cssText = 'display:flex;justify-content:center;gap:12px;';

        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.innerHTML = '<i class="ph ph-trash"></i> Remove Duplicate';
        removeBtn.style.cssText = 'padding:8px 16px;border:1px solid #dc2626;background:#fff;color:#dc2626;border-radius:6px;font-weight:600;cursor:pointer;font-size:13px;';
        removeBtn.onclick = () => { document.body.removeChild(backdrop); if (onRemove) onRemove(); };

        const keepBtn = document.createElement('button');
        keepBtn.type = 'button';
        keepBtn.innerHTML = '<i class="ph ph-check"></i> Keep Both';
        keepBtn.style.cssText = 'padding:8px 16px;border:none;background:#16a34a;color:#fff;border-radius:6px;font-weight:600;cursor:pointer;font-size:13px;';
        keepBtn.onclick = () => { document.body.removeChild(backdrop); if (onKeep) onKeep(); };

        actions.appendChild(removeBtn);
        actions.appendChild(keepBtn);
        panel.appendChild(title);
        panel.appendChild(msg);
        panel.appendChild(actions);
        backdrop.appendChild(panel);
        document.body.appendChild(backdrop);
    }

    function handleQtyKeydown(event, input) {
        if (event.key === 'Enter') {
            event.preventDefault();
            const itemSearch = document.getElementById('itemSearch');
            if (itemSearch) { itemSearch.focus(); itemSearch.select(); }
        }
    }

    function calcTotals() {
        let subTotal = 0;
        document.querySelectorAll('#invoiceBody tr').forEach(row => {
            const qty     = parseFloat(row.querySelector('input[name="qty[]"]').value) || 0;
            const price   = parseFloat(row.querySelector('input[name="price[]"]').value) || 0;
            const discVal = parseFloat(row.querySelector('input[name="item_discount_val[]"]').value) || 0;
            const discType = row.querySelector('select[name="item_discount_type[]"]').value;

            let rowGross = qty * price;
            let rowDisc  = (discType === '%') ? (rowGross * discVal / 100) : discVal;
            let rowNet   = Math.max(rowGross - rowDisc, 0);

            const totalInput = row.querySelector('.line-total');
            if (totalInput) totalInput.value = rowNet.toLocaleString('en-IN', {minimumFractionDigits:2, maximumFractionDigits:2});
            subTotal += rowNet;
        });

        const globalDiscVal  = parseFloat(document.getElementById('globalDiscVal').value) || 0;
        const globalDiscType = document.getElementById('globalDiscType').value;
        let globalDisc = (globalDiscType === '%') ? (subTotal * globalDiscVal / 100) : globalDiscVal;
        let grandTotal = Math.max(subTotal - globalDisc, 0);

        console.log("[calcTotals]", { sub_total: subTotal, discount_deducted: globalDisc, grand_total: grandTotal });

        document.getElementById('subTotal').innerText  = subTotal.toLocaleString('en-IN', {minimumFractionDigits:2, maximumFractionDigits:2});
        document.getElementById('grandTotal').innerText = grandTotal.toLocaleString('en-IN', {minimumFractionDigits:2, maximumFractionDigits:2});
        document.getElementById('balanceDue').innerText = grandTotal.toLocaleString('en-IN', {minimumFractionDigits:2, maximumFractionDigits:2});

        checkDiscounts();
        updateProjectedOutstandingIndicator();
    }

    function showDiscountPrompt(title, message, onAccept, onReject) {
        const backdrop = document.createElement('div');
        backdrop.className = 'modal-backdrop';
        backdrop.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:9999;display:flex;align-items:center;justify-content:center;';

        const panel = document.createElement('div');
        panel.style.cssText = 'max-width:420px;width:90%;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 20px 50px rgba(0,0,0,0.2);border:1px solid #e2e8f0;font-family:system-ui,sans-serif;';

        const header = document.createElement('div');
        header.style.cssText = 'background:#1e3a8a;color:#fff;padding:14px 18px;font-weight:700;font-size:14px;display:flex;align-items:center;gap:8px;';
        header.innerHTML = `<i class="fa-solid fa-gift"></i> ${title}`;
        panel.appendChild(header);

        const body = document.createElement('div');
        body.style.cssText = 'padding:20px 18px;font-size:13px;color:#334155;line-height:1.6;';
        body.innerText = message;
        panel.appendChild(body);

        const footer = document.createElement('div');
        footer.style.cssText = 'padding:12px 18px;background:#f8fafc;border-top:1px solid #e2e8f0;display:flex;justify-content:flex-end;gap:10px;';

        const btnReject = document.createElement('button');
        btnReject.type = 'button';
        btnReject.style.cssText = 'padding:8px 16px;border-radius:6px;border:1px solid #cbd5e1;background:#fff;color:#475569;font-size:12px;font-weight:600;cursor:pointer;';
        btnReject.innerText = 'No, Thanks';
        btnReject.onclick = () => { backdrop.remove(); if (onReject) onReject(); };
        footer.appendChild(btnReject);

        const btnAccept = document.createElement('button');
        btnAccept.type = 'button';
        btnAccept.style.cssText = 'padding:8px 16px;border-radius:6px;border:none;background:#2563eb;color:#fff;font-size:12px;font-weight:600;cursor:pointer;';
        btnAccept.innerText = 'Accept Offer';
        btnAccept.onclick = () => { backdrop.remove(); if (onAccept) onAccept(); };
        footer.appendChild(btnAccept);

        panel.appendChild(footer);
        backdrop.appendChild(panel);
        document.body.appendChild(backdrop);
    }

    function checkDiscounts() {
        if (!activeDiscountRules || activeDiscountRules.length === 0) return;

        const cartItems = {};
        document.querySelectorAll('#invoiceBody tr').forEach(row => {
            const itemId = row.querySelector('input[name="item_selection[]"]')?.value;
            const qty    = parseFloat(row.querySelector('input[name="qty[]"]')?.value) || 0;
            const price  = parseFloat(row.querySelector('input[name="price[]"]')?.value) || 0;
            if (itemId && price > 0.001) {
                const baseItemId = itemId.split('|')[0];
                cartItems[baseItemId] = (cartItems[baseItemId] || 0) + qty;
            }
        });

        activeDiscountRules.forEach(rule => {
            if (rule.rule_type === 'item_wise' && rule.target_item_id) {
                const targetItemId = String(rule.target_item_id);
                const totalQty = cartItems[targetItemId] || 0;

                if (totalQty > 0) {
                    let bestTier = null;
                    rule.tiers.forEach(tier => {
                        const min = parseFloat(tier.min_threshold);
                        if (totalQty >= min) {
                            if (!bestTier || min > parseFloat(bestTier.min_threshold)) bestTier = tier;
                        }
                    });

                    const promptKey = `${rule.id}-${targetItemId}`;
                    if (bestTier && promptedDiscounts.itemRules[promptKey] !== String(bestTier.id)) {
                        promptedDiscounts.itemRules[promptKey] = String(bestTier.id);
                        const rewardQty = parseFloat(bestTier.reward_val);

                        let sourceCompositeId = null;
                        let maxQtyForComp = 0;
                        document.querySelectorAll('#invoiceBody tr').forEach(row => {
                            const rowCompId = row.querySelector('input[name="item_selection[]"]')?.value;
                            const rowPrice  = parseFloat(row.querySelector('input[name="price[]"]')?.value) || 0;
                            const rowQty    = parseFloat(row.querySelector('input[name="qty[]"]')?.value) || 0;
                            if (rowCompId && rowCompId.split('|')[0] === targetItemId && rowPrice > 0.001) {
                                if (rowQty > maxQtyForComp) { maxQtyForComp = rowQty; sourceCompositeId = rowCompId; }
                            }
                        });
                        if (!sourceCompositeId) sourceCompositeId = `${targetItemId}|0|0`;

                        const targetItem = catalog.find(c => c.id === sourceCompositeId) || catalog.find(c => c.id.split('|')[0] === targetItemId);
                        const itemName = targetItem ? targetItem.name : 'Product';

                        showDiscountPrompt(
                            "Free Issue Promotion!",
                            `Your billed quantity (${totalQty}) for ${itemName} qualifies for ${rewardQty} free unit(s). Add this free issue to the bill?`,
                            function() {
                                let foundFree = false;
                                document.querySelectorAll('#invoiceBody tr').forEach(row => {
                                    const rowId    = row.querySelector('input[name="item_selection[]"]')?.value;
                                    const rowPrice = parseFloat(row.querySelector('input[name="price[]"]')?.value) || 0;
                                    if (rowId === sourceCompositeId && rowPrice <= 0.001) {
                                        const qtyInput = row.querySelector('input[name="qty[]"]');
                                        if (qtyInput) { qtyInput.value = rewardQty; foundFree = true; }
                                    }
                                });
                                if (!foundFree) {
                                    addItemRow(sourceCompositeId, targetItem ? targetItem.code : 'FREE', rewardQty, `${itemName} [FREE ISSUE]`, 0.00, 0, 'Rs');
                                }
                                calcTotals();
                            },
                            function() {}
                        );
                    }
                }
            }
        });

        let subTotalBeforeGlobal = 0;
        document.querySelectorAll('#invoiceBody tr').forEach(row => {
            const qty      = parseFloat(row.querySelector('input[name="qty[]"]').value) || 0;
            const price    = parseFloat(row.querySelector('input[name="price[]"]').value) || 0;
            const discVal  = parseFloat(row.querySelector('input[name="item_discount_val[]"]').value) || 0;
            const discType = row.querySelector('select[name="item_discount_type[]"]').value;
            let rowGross = qty * price;
            let rowDisc  = (discType === '%') ? (rowGross * discVal / 100) : discVal;
            subTotalBeforeGlobal += Math.max(rowGross - rowDisc, 0);
        });

        activeDiscountRules.forEach(rule => {
            if (rule.rule_type === 'bill_wise') {
                let bestTier = null;
                rule.tiers.forEach(tier => {
                    const min = parseFloat(tier.min_threshold);
                    const max = tier.max_threshold ? parseFloat(tier.max_threshold) : Infinity;
                    if (subTotalBeforeGlobal >= min && subTotalBeforeGlobal <= max) {
                        if (!bestTier || min > parseFloat(bestTier.min_threshold)) bestTier = tier;
                    }
                });

                if (bestTier) {
                    const discountPct = parseFloat(bestTier.reward_val);
                    const promptKey   = String(rule.id);
                    if (promptedDiscounts.billRules[promptKey] !== String(bestTier.id)) {
                        promptedDiscounts.billRules[promptKey] = String(bestTier.id);
                        showDiscountPrompt(
                            "Bill Discount Promotion!",
                            `Your subtotal (Rs ${subTotalBeforeGlobal.toLocaleString('en-IN',{minimumFractionDigits:2})}) qualifies for a ${discountPct}% global bill discount. Apply this discount?`,
                            function() {
                                const globSelect = document.getElementById('globalDiscType');
                                const globInput  = document.getElementById('globalDiscVal');
                                if (globSelect && globInput) { globSelect.value = '%'; globInput.value = discountPct; calcTotals(); }
                            },
                            function() {}
                        );
                    }
                }
            }
        });
    }

    function calculateDueDateOffset() {
        const termSelect      = document.getElementById('paymentTermSelect');
        const invoiceDateInput = document.querySelector('input[name="invoice_date"]');
        const dueDateInput    = document.getElementById('dueDate');
        if (!termSelect || !invoiceDateInput || !dueDateInput) return;
        const selectedOpt = termSelect.options[termSelect.selectedIndex];
        if (!selectedOpt || selectedOpt.value === '') return;
        const days   = parseInt(selectedOpt.getAttribute('data-days')) || 0;
        const invDate = new Date(invoiceDateInput.value);
        if (isNaN(invDate.getTime())) return;
        invDate.setDate(invDate.getDate() + days);
        const year  = invDate.getFullYear();
        const month = String(invDate.getMonth() + 1).padStart(2, '0');
        const day   = String(invDate.getDate()).padStart(2, '0');
        dueDateInput.value = `${year}-${month}-${day}`;
    }

    // INITIALIZATION HOOK — Pre-populate form in Edit Mode
    document.addEventListener("DOMContentLoaded", function() {
        const invoiceDateInput = document.querySelector('input[name="invoice_date"]');
        if (invoiceDateInput) {
            invoiceDateInput.addEventListener('change', calculateDueDateOffset);
        }

        <?php if ($inv && isset($inv->customer_id)): ?>
            const initialCustomerId = "<?= $inv->customer_id ?>";
            const initialCust = customers.find(c => c.id === initialCustomerId);
            if (initialCust) selectCustomer(initialCust);
        <?php endif; ?>

        <?php if (!empty($editingItems)): ?>
            <?php foreach ($editingItems as $index => $ei): ?>
                <?php
                $itemId = isset($ei->item_id) ? $ei->item_id : null;
                $varId  = isset($ei->variation_option_id) ? $ei->variation_option_id : null;

                if (empty($itemId) && !empty($ei->description)) {
                    $descClean = trim((string)$ei->description);
                    if (strpos($descClean, ' - ') !== false) {
                        $parts     = explode(' - ', $descClean);
                        $descClean = trim($parts[0]);
                    }
                    $db->query("SELECT id, item_code FROM items WHERE name = :name LIMIT 1");
                    $db->bind(':name', $descClean);
                    $matchedItem = $db->single();
                    if ($matchedItem) {
                        $itemId = $matchedItem->id;
                        if (strpos((string)$ei->description, ' - ') !== false) {
                            $suffix = trim(str_replace($descClean . ' - ', '', (string)$ei->description));
                            if (strpos($suffix, ': ') !== false) {
                                $suffixParts = explode(': ', $suffix);
                                $valName     = trim($suffixParts[1] ?? '');
                                $db->query("SELECT ivo.id FROM item_variation_options ivo
                                            JOIN variation_values vv ON ivo.variation_value_id = vv.id
                                            WHERE ivo.item_id = :item_id AND vv.value_name = :val_name LIMIT 1");
                                $db->bind(':item_id', $itemId);
                                $db->bind(':val_name', $valName);
                                $matchedVar = $db->single();
                                if ($matchedVar) $varId = $matchedVar->id;
                            }
                        }
                    }
                }

                $compositeId = ($itemId ?: '0') . '|' . ($varId ?: '0') . '|0';
                $codeStr = 'ITEM';
                if ($itemId) {
                    $db->query("SELECT item_code FROM items WHERE id = :id");
                    $db->bind(':id', $itemId);
                    $itemRow = $db->single();
                    $codeStr = $itemRow ? $itemRow->item_code : 'ITEM';
                }
                ?>
                addItemRow(
                    "<?= $compositeId ?>",
                    <?= json_encode($codeStr) ?>,
                    <?= floatval(isset($ei->quantity) ? $ei->quantity : 0) ?>,
                    <?= json_encode(isset($ei->description) ? $ei->description : '') ?>,
                    <?= floatval(isset($ei->unit_price) ? $ei->unit_price : 0) ?>,
                    <?= floatval(isset($ei->discount_value) ? $ei->discount_value : 0) ?>,
                    <?= json_encode(isset($ei->discount_type) ? $ei->discount_type : 'Rs') ?>
                );
            <?php endforeach; ?>
            renumberInvoiceRows();
        <?php endif; ?>

        const serverError = <?= json_encode($data['error'] ?? '') ?>;
        if (serverError) {
            console.error("=== TRANSACTION ERROR ===");
            console.error("Backend Error:", serverError);
            console.error("========================");
        }

        const invForm = document.getElementById('invoiceForm');
        if (invForm) {
            invForm.addEventListener('submit', function(e) {
                const formType = document.querySelector('input[name="type"]')?.value;
                if (formType === 'sales_order') {
                    const repRouteId   = document.querySelector('input[name="rep_route_id"]')?.value;
                    const editingInvId = document.querySelector('input[name="editing_invoice_id"]')?.value;
                    if (!repRouteId && !editingInvId) return true;
                }

                const customerId = document.getElementById('customerIdInput').value;
                const cust = customers.find(c => String(c.id) === String(customerId));
                if (cust) {
                    const creditLimit = parseFloat(cust.credit_limit) || 0;
                    if (creditLimit > 0) {
                        const outBal       = parseFloat(cust.outstanding) || 0;
                        const subTotalText = document.getElementById('grandTotal').innerText.replace(/,/g, '');
                        const newTotal     = parseFloat(subTotalText) || 0;

                        let oldInvoiceTotal = 0;
                        <?php if ($inv): ?>
                            const oldSub      = parseFloat("<?= isset($inv->total_amount) ? $inv->total_amount : 0 ?>") || 0;
                            const oldDiscVal2 = parseFloat("<?= isset($inv->global_discount_val) ? $inv->global_discount_val : 0 ?>") || 0;
                            const oldDiscType2 = "<?= isset($inv->global_discount_type) ? $inv->global_discount_type : 'Rs' ?>";
                            const oldDisc2    = (oldDiscType2 === '%') ? (oldSub * oldDiscVal2 / 100) : oldDiscVal2;
                            oldInvoiceTotal = (oldSub - oldDisc2) + (parseFloat("<?= isset($inv->tax_amount) ? $inv->tax_amount : 0 ?>") || 0);
                        <?php endif; ?>

                        const projected = outBal - oldInvoiceTotal + newTotal;
                        if (projected > creditLimit) {
                            e.preventDefault();
                            const exceeded = projected - creditLimit;
                            showCreditLimitBlockDialog(cust.name, creditLimit, projected, exceeded);
                            return false;
                        }
                    }
                }
            });
        }
    });

    function promptDeleteInvoice(id) {
        document.getElementById('deleteInvoiceForm').action = '<?= APP_URL ?>/sales/delete/' + id;
        document.getElementById('deletePasswordInput').value = '';
        document.getElementById('deleteReasonInput').value = '';
        document.getElementById('deleteInvoiceModal').style.display = 'flex';
    }

    function closeDeleteInvoiceModal() {
        document.getElementById('deleteInvoiceModal').style.display = 'none';
    }

    function submitDeleteInvoice() {
        const pass = document.getElementById('deletePasswordInput').value.trim();
        const reason = document.getElementById('deleteReasonInput').value.trim();
        if (!pass) {
            alert('Please enter your admin password.');
            return;
        }
        if (!reason) {
            alert('Please enter the reason for deletion.');
            return;
        }
        document.getElementById('deleteInvoiceForm').submit();
    }
</script>

<?php include '../app/Views/layouts/resilient_loader.php'; ?>