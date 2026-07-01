<?php
/* STREAMING_CHUNK:Simplifying the Dashboard Controller... */
class DashboardController extends Controller {

    public function __construct() {
        // Ensure user is logged in
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . APP_URL . '/auth/login');
            exit;
        }
    }

    public function index() {
        $data = [
            'title' => 'Workflow Dashboard',
            'username' => $_SESSION['username'],
            'role' => $_SESSION['role'],
            'content_view' => 'dashboard/index'
        ];
        
        $this->view('layouts/main', $data);
    }

    public function search() {
        header('Content-Type: application/json');
        
        $q = trim($_GET['q'] ?? '');
        if (empty($q)) {
            echo json_encode([]);
            exit;
        }

        $tag = '';
        $searchQuery = $q;

        if (strpos($q, '@') === 0) {
            $spacePos = strpos($q, ' ');
            if ($spacePos !== false) {
                $tag = strtolower(substr($q, 1, $spacePos - 1));
                $searchQuery = trim(substr($q, $spacePos + 1));
            } else {
                $tag = strtolower(substr($q, 1));
                $searchQuery = '';
            }
        }

        $db = new Database();
        $results = [];

        // If tag is present, we handle tagged search
        if (!empty($tag)) {
            $likeQuery = '%' . $searchQuery . '%';
            
            switch ($tag) {
                case 'customer':
                    if (empty($searchQuery)) {
                        $db->query("SELECT id, name, phone, territory FROM customers ORDER BY name ASC LIMIT 10");
                    } else {
                        $db->query("SELECT id, name, phone, territory FROM customers WHERE name LIKE :q OR phone LIKE :q OR territory LIKE :q OR id LIKE :q LIMIT 10");
                        $db->bind(':q', $likeQuery);
                    }
                    $rows = $db->resultSet() ?: [];
                    foreach ($rows as $row) {
                        $results[] = [
                            'id' => $row->id,
                            'title' => $row->name,
                            'subtitle' => 'Phone: ' . ($row->phone ?: 'N/A') . ' | Territory: ' . ($row->territory ?: 'N/A'),
                            'url' => APP_URL . '/customer/index/' . $row->id,
                            'tag' => 'customer'
                        ];
                    }
                    break;

                case 'supplier':
                    if (empty($searchQuery)) {
                        $db->query("SELECT id, name, phone, email FROM vendors ORDER BY name ASC LIMIT 10");
                    } else {
                        $db->query("SELECT id, name, phone, email FROM vendors WHERE name LIKE :q OR phone LIKE :q OR email LIKE :q OR id LIKE :q LIMIT 10");
                        $db->bind(':q', $likeQuery);
                    }
                    $rows = $db->resultSet() ?: [];
                    foreach ($rows as $row) {
                        $results[] = [
                            'id' => $row->id,
                            'title' => $row->name,
                            'subtitle' => 'Phone: ' . ($row->phone ?: 'N/A') . ' | Email: ' . ($row->email ?: 'N/A'),
                            'url' => APP_URL . '/supplier/index/' . $row->id,
                            'tag' => 'supplier'
                        ];
                    }
                    break;

                case 'product':
                case 'stock':
                    if (empty($searchQuery)) {
                        $db->query("SELECT id, name, item_code as sku, barcode, sample_code, price FROM items ORDER BY name ASC LIMIT 10");
                    } else {
                        $db->query("SELECT id, name, item_code as sku, barcode, sample_code, price FROM items WHERE name LIKE :q OR item_code LIKE :q OR barcode LIKE :q OR sample_code LIKE :q OR id LIKE :q LIMIT 10");
                        $db->bind(':q', $likeQuery);
                    }
                    $rows = $db->resultSet() ?: [];
                    foreach ($rows as $row) {
                        $results[] = [
                            'id' => $row->id,
                            'title' => $row->name,
                            'subtitle' => 'SKU: ' . $row->sku . ' | Sample Code: ' . ($row->sample_code ?: 'N/A') . ' | Price: Rs. ' . number_format($row->price, 2),
                            'url' => APP_URL . '/inventory/edit/' . $row->id,
                            'tag' => $tag
                        ];
                    }
                    break;

                case 'invoice':
                    if (empty($searchQuery)) {
                        $db->query("SELECT i.id, i.invoice_number, i.invoice_date, i.total_amount, i.status, c.name as customer_name FROM invoices i JOIN customers c ON i.customer_id = c.id ORDER BY i.id DESC LIMIT 10");
                    } else {
                        $db->query("SELECT i.id, i.invoice_number, i.invoice_date, i.total_amount, i.status, c.name as customer_name FROM invoices i JOIN customers c ON i.customer_id = c.id WHERE i.invoice_number LIKE :q OR c.name LIKE :q LIMIT 10");
                        $db->bind(':q', $likeQuery);
                    }
                    $rows = $db->resultSet() ?: [];
                    foreach ($rows as $row) {
                        $results[] = [
                            'id' => $row->id,
                            'title' => $row->invoice_number,
                            'subtitle' => 'Customer: ' . $row->customer_name . ' | Date: ' . $row->invoice_date . ' | Total: Rs. ' . number_format($row->total_amount, 2) . ' | Status: ' . $row->status,
                            'url' => APP_URL . '/sales/show/' . $row->id,
                            'tag' => 'invoice'
                        ];
                    }
                    break;

                case 'sales-order':
                    if (empty($searchQuery)) {
                        $db->query("SELECT id, order_number, customer_name, order_date, grand_total, status FROM sales_orders ORDER BY id DESC LIMIT 10");
                    } else {
                        $db->query("SELECT id, order_number, customer_name, order_date, grand_total, status FROM sales_orders WHERE order_number LIKE :q OR customer_name LIKE :q LIMIT 10");
                        $db->bind(':q', $likeQuery);
                    }
                    $rows = $db->resultSet() ?: [];
                    foreach ($rows as $row) {
                        $results[] = [
                            'id' => $row->id,
                            'title' => $row->order_number,
                            'subtitle' => 'Customer: ' . $row->customer_name . ' | Date: ' . $row->order_date . ' | Total: Rs. ' . number_format($row->grand_total, 2) . ' | Status: ' . $row->status,
                            'url' => APP_URL . '/salesorder/show/' . $row->id,
                            'tag' => 'sales-order'
                        ];
                    }
                    break;

                case 'quotation':
                case 'estimate':
                    if (empty($searchQuery)) {
                        $db->query("SELECT e.id, e.estimate_number, e.estimate_date, e.total_amount, e.status, c.name as customer_name FROM estimates e JOIN customers c ON e.customer_id = c.id ORDER BY e.id DESC LIMIT 10");
                    } else {
                        $db->query("SELECT e.id, e.estimate_number, e.estimate_date, e.total_amount, e.status, c.name as customer_name FROM estimates e JOIN customers c ON e.customer_id = c.id WHERE e.estimate_number LIKE :q OR c.name LIKE :q LIMIT 10");
                        $db->bind(':q', $likeQuery);
                    }
                    $rows = $db->resultSet() ?: [];
                    foreach ($rows as $row) {
                        $results[] = [
                            'id' => $row->id,
                            'title' => $row->estimate_number,
                            'subtitle' => 'Customer: ' . $row->customer_name . ' | Date: ' . $row->estimate_date . ' | Total: Rs. ' . number_format($row->total_amount, 2) . ' | Status: ' . $row->status,
                            'url' => APP_URL . '/estimate/show/' . $row->id,
                            'tag' => $tag
                        ];
                    }
                    break;

                case 'cheque':
                    if (empty($searchQuery)) {
                        $db->query("SELECT id, cheque_number, bank_name, amount, banking_date, status FROM cheques ORDER BY id DESC LIMIT 10");
                    } else {
                        $db->query("SELECT id, cheque_number, bank_name, amount, banking_date, status FROM cheques WHERE cheque_number LIKE :q OR bank_name LIKE :q LIMIT 10");
                        $db->bind(':q', $likeQuery);
                    }
                    $rows = $db->resultSet() ?: [];
                    foreach ($rows as $row) {
                        $results[] = [
                            'id' => $row->id,
                            'title' => 'Cheque: ' . $row->cheque_number,
                            'subtitle' => 'Bank: ' . $row->bank_name . ' | Amount: Rs. ' . number_format($row->amount, 2) . ' | Date: ' . $row->banking_date . ' | Status: ' . $row->status,
                            'url' => APP_URL . '/cheque',
                            'tag' => 'cheque'
                        ];
                    }
                    break;

                case 'payment':
                case 'collection':
                    if (empty($searchQuery)) {
                        $db->query("
                            (SELECT id, reference, payment_method, amount, payment_date, 'Customer' as pay_type FROM customer_payments)
                            UNION ALL
                            (SELECT id, reference, payment_method, amount, payment_date, 'Supplier' as pay_type FROM supplier_payments)
                            ORDER BY payment_date DESC LIMIT 10
                        ");
                    } else {
                        $db->query("
                            (SELECT id, reference, payment_method, amount, payment_date, 'Customer' as pay_type FROM customer_payments WHERE reference LIKE :q OR payment_method LIKE :q)
                            UNION ALL
                            (SELECT id, reference, payment_method, amount, payment_date, 'Supplier' as pay_type FROM supplier_payments WHERE reference LIKE :q OR payment_method LIKE :q)
                            ORDER BY payment_date DESC LIMIT 10
                        ");
                        $db->bind(':q', $likeQuery);
                    }
                    $rows = $db->resultSet() ?: [];
                    foreach ($rows as $row) {
                        $results[] = [
                            'id' => $row->id,
                            'title' => 'Payment Ref: ' . ($row->reference ?: 'N/A'),
                            'subtitle' => 'Type: ' . $row->pay_type . ' | Method: ' . $row->payment_method . ' | Amount: Rs. ' . number_format($row->amount, 2) . ' | Date: ' . $row->payment_date,
                            'url' => APP_URL . '/' . strtolower($row->pay_type) . 'payment',
                            'tag' => $tag
                        ];
                    }
                    break;

                case 'grn':
                    if (empty($searchQuery)) {
                        $db->query("SELECT g.id, g.grn_number, g.grn_date, g.status, v.name as supplier_name FROM goods_receipt_notes g JOIN vendors v ON g.vendor_id = v.id ORDER BY g.id DESC LIMIT 10");
                    } else {
                        $db->query("SELECT g.id, g.grn_number, g.grn_date, g.status, v.name as supplier_name FROM goods_receipt_notes g JOIN vendors v ON g.vendor_id = v.id WHERE g.grn_number LIKE :q OR v.name LIKE :q LIMIT 10");
                        $db->bind(':q', $likeQuery);
                    }
                    $rows = $db->resultSet() ?: [];
                    foreach ($rows as $row) {
                        $results[] = [
                            'id' => $row->id,
                            'title' => $row->grn_number,
                            'subtitle' => 'Supplier: ' . $row->supplier_name . ' | Date: ' . $row->grn_date . ' | Status: ' . $row->status,
                            'url' => APP_URL . '/grn/show/' . $row->id,
                            'tag' => 'grn'
                        ];
                    }
                    break;

                case 'po':
                    if (empty($searchQuery)) {
                        $db->query("SELECT p.id, p.po_number, p.po_date, p.total_amount, p.status, v.name as supplier_name FROM purchase_orders p JOIN vendors v ON p.vendor_id = v.id ORDER BY p.id DESC LIMIT 10");
                    } else {
                        $db->query("SELECT p.id, p.po_number, p.po_date, p.total_amount, p.status, v.name as supplier_name FROM purchase_orders p JOIN vendors v ON p.vendor_id = v.id WHERE p.po_number LIKE :q OR v.name LIKE :q LIMIT 10");
                        $db->bind(':q', $likeQuery);
                    }
                    $rows = $db->resultSet() ?: [];
                    foreach ($rows as $row) {
                        $results[] = [
                            'id' => $row->id,
                            'title' => $row->po_number,
                            'subtitle' => 'Supplier: ' . $row->supplier_name . ' | Date: ' . $row->po_date . ' | Total: Rs. ' . number_format($row->total_amount, 2) . ' | Status: ' . $row->status,
                            'url' => APP_URL . '/purchase/show/' . $row->id,
                            'tag' => 'po'
                        ];
                    }
                    break;

                case 'category':
                    if (empty($searchQuery)) {
                        $db->query("SELECT id, name, description FROM item_categories ORDER BY name ASC LIMIT 10");
                    } else {
                        $db->query("SELECT id, name, description FROM item_categories WHERE name LIKE :q OR description LIKE :q LIMIT 10");
                        $db->bind(':q', $likeQuery);
                    }
                    $rows = $db->resultSet() ?: [];
                    foreach ($rows as $row) {
                        $results[] = [
                            'id' => $row->id,
                            'title' => $row->name,
                            'subtitle' => 'Description: ' . ($row->description ?: 'N/A'),
                            'url' => APP_URL . '/category',
                            'tag' => 'category'
                        ];
                    }
                    break;

                case 'warehouse':
                    if (empty($searchQuery)) {
                        $db->query("SELECT id, name, location FROM warehouses ORDER BY name ASC LIMIT 10");
                    } else {
                        $db->query("SELECT id, name, location FROM warehouses WHERE name LIKE :q OR location LIKE :q LIMIT 10");
                        $db->bind(':q', $likeQuery);
                    }
                    $rows = $db->resultSet() ?: [];
                    foreach ($rows as $row) {
                        $results[] = [
                            'id' => $row->id,
                            'title' => $row->name,
                            'subtitle' => 'Location: ' . ($row->location ?: 'N/A'),
                            'url' => APP_URL . '/warehouse',
                            'tag' => 'warehouse'
                        ];
                    }
                    break;

                case 'route':
                    if (empty($searchQuery)) {
                        $db->query("SELECT id, route_name, status, start_time FROM rep_daily_routes ORDER BY id DESC LIMIT 10");
                    } else {
                        $db->query("SELECT id, route_name, status, start_time FROM rep_daily_routes WHERE route_name LIKE :q LIMIT 10");
                        $db->bind(':q', $likeQuery);
                    }
                    $rows = $db->resultSet() ?: [];
                    foreach ($rows as $row) {
                        $results[] = [
                            'id' => $row->id,
                            'title' => $row->route_name,
                            'subtitle' => 'Date: ' . $row->start_time . ' | Status: ' . $row->status,
                            'url' => APP_URL . '/reptracking',
                            'tag' => 'route'
                        ];
                    }
                    break;

                case 'driver':
                    if (empty($searchQuery)) {
                        $db->query("SELECT id, CONCAT(first_name, ' ', last_name) as name, phone FROM employees WHERE job_title = 'Driver' ORDER BY first_name ASC LIMIT 10");
                    } else {
                        $db->query("SELECT id, CONCAT(first_name, ' ', last_name) as name, phone FROM employees WHERE job_title = 'Driver' AND (first_name LIKE :q OR last_name LIKE :q OR phone LIKE :q) LIMIT 10");
                        $db->bind(':q', $likeQuery);
                    }
                    $rows = $db->resultSet() ?: [];
                    foreach ($rows as $row) {
                        $results[] = [
                            'id' => $row->id,
                            'title' => $row->name,
                            'subtitle' => 'Driver | Phone: ' . ($row->phone ?: 'N/A'),
                            'url' => APP_URL . '/user',
                            'tag' => 'driver'
                        ];
                    }
                    break;

                case 'vehicle':
                    if (empty($searchQuery)) {
                        $db->query("SELECT id, vehicle_number, brand, model FROM vehicles ORDER BY vehicle_number ASC LIMIT 10");
                    } else {
                        $db->query("SELECT id, vehicle_number, brand, model FROM vehicles WHERE vehicle_number LIKE :q OR brand LIKE :q OR model LIKE :q LIMIT 10");
                        $db->bind(':q', $likeQuery);
                    }
                    $rows = $db->resultSet() ?: [];
                    foreach ($rows as $row) {
                        $results[] = [
                            'id' => $row->id,
                            'title' => $row->vehicle_number,
                            'subtitle' => 'Vehicle | ' . ($row->brand ? $row->brand . ' ' . $row->model : 'N/A'),
                            'url' => APP_URL . '/vehicle',
                            'tag' => 'vehicle'
                        ];
                    }
                    break;

                case 'rep':
                    if (empty($searchQuery)) {
                        $db->query("SELECT id, username, email FROM users WHERE role = 'rep' ORDER BY username ASC LIMIT 10");
                    } else {
                        $db->query("SELECT id, username, email FROM users WHERE role = 'rep' AND (username LIKE :q OR email LIKE :q) LIMIT 10");
                        $db->bind(':q', $likeQuery);
                    }
                    $rows = $db->resultSet() ?: [];
                    foreach ($rows as $row) {
                        $results[] = [
                            'id' => $row->id,
                            'title' => $row->username,
                            'subtitle' => 'Sales Rep | Email: ' . ($row->email ?: 'N/A'),
                            'url' => APP_URL . '/user',
                            'tag' => 'rep'
                        ];
                    }
                    break;

                case 'user':
                    if (empty($searchQuery)) {
                        $db->query("SELECT id, username, email, role FROM users ORDER BY username ASC LIMIT 10");
                    } else {
                        $db->query("SELECT id, username, email, role FROM users WHERE username LIKE :q OR email LIKE :q OR role LIKE :q LIMIT 10");
                        $db->bind(':q', $likeQuery);
                    }
                    $rows = $db->resultSet() ?: [];
                    foreach ($rows as $row) {
                        $results[] = [
                            'id' => $row->id,
                            'title' => $row->username,
                            'subtitle' => 'Role: ' . $row->role . ' | Email: ' . ($row->email ?: 'N/A'),
                            'url' => APP_URL . '/user',
                            'tag' => 'user'
                        ];
                    }
                    break;

                case 'report':
                    // Report Engine lookup
                    if (file_exists('../app/Services/ReportEngine.php')) {
                        require_once '../app/Services/ReportEngine.php';
                    }
                    if (class_exists('ReportEngine')) {
                        $reportsRegistry = ReportEngine::getReportsRegistry();
                        foreach ($reportsRegistry as $key => $report) {
                            if (empty($searchQuery) || stripos($report['title'], $searchQuery) !== false || stripos($key, $searchQuery) !== false) {
                                $results[] = [
                                    'id' => $key,
                                    'title' => $report['title'],
                                    'subtitle' => 'Category: ' . ($report['category'] ?? 'General'),
                                    'url' => APP_URL . '/report/viewer/' . $key,
                                    'tag' => 'report'
                                ];
                            }
                        }
                    }
                    break;

                case 'journal':
                    if (empty($searchQuery)) {
                        $db->query("SELECT id, reference, entry_date, description FROM journal_entries ORDER BY entry_date DESC LIMIT 10");
                    } else {
                        $db->query("SELECT id, reference, entry_date, description FROM journal_entries WHERE reference LIKE :q OR description LIKE :q LIMIT 10");
                        $db->bind(':q', $likeQuery);
                    }
                    $rows = $db->resultSet() ?: [];
                    foreach ($rows as $row) {
                        $results[] = [
                            'id' => $row->id,
                            'title' => $row->reference,
                            'subtitle' => 'Date: ' . $row->entry_date . ' | Description: ' . ($row->description ?: 'N/A'),
                            'url' => APP_URL . '/accounting/journal',
                            'tag' => 'journal'
                        ];
                    }
                    break;

                case 'expense':
                    if (empty($searchQuery)) {
                        $db->query("SELECT e.id, e.reference, e.expense_date, e.amount, e.description, v.name as vendor_name FROM expenses e LEFT JOIN vendors v ON e.vendor_id = v.id ORDER BY e.expense_date DESC LIMIT 10");
                    } else {
                        $db->query("SELECT e.id, e.reference, e.expense_date, e.amount, e.description, v.name as vendor_name FROM expenses e LEFT JOIN vendors v ON e.vendor_id = v.id WHERE e.reference LIKE :q OR e.description LIKE :q OR v.name LIKE :q LIMIT 10");
                        $db->bind(':q', $likeQuery);
                    }
                    $rows = $db->resultSet() ?: [];
                    foreach ($rows as $row) {
                        $results[] = [
                            'id' => $row->id,
                            'title' => $row->reference,
                            'subtitle' => 'Vendor: ' . ($row->vendor_name ?: 'N/A') . ' | Date: ' . $row->expense_date . ' | Amount: Rs. ' . number_format($row->amount, 2),
                            'url' => APP_URL . '/expenses',
                            'tag' => 'expense'
                        ];
                    }
                    break;

                case 'income':
                    // Query customer payments as income
                    if (empty($searchQuery)) {
                        $db->query("SELECT p.id, p.reference, p.payment_date, p.amount, c.name as customer_name FROM customer_payments p JOIN customers c ON p.customer_id = c.id WHERE p.status = 'Active' ORDER BY p.payment_date DESC LIMIT 10");
                    } else {
                        $db->query("SELECT p.id, p.reference, p.payment_date, p.amount, c.name as customer_name FROM customer_payments p JOIN customers c ON p.customer_id = c.id WHERE p.status = 'Active' AND (p.reference LIKE :q OR c.name LIKE :q) LIMIT 10");
                        $db->bind(':q', $likeQuery);
                    }
                    $rows = $db->resultSet() ?: [];
                    foreach ($rows as $row) {
                        $results[] = [
                            'id' => $row->id,
                            'title' => 'Income Collection: ' . ($row->reference ?: 'N/A'),
                            'subtitle' => 'Customer: ' . $row->customer_name . ' | Date: ' . $row->payment_date . ' | Amount: Rs. ' . number_format($row->amount, 2),
                            'url' => APP_URL . '/customerpayment',
                            'tag' => 'income'
                        ];
                    }
                    break;

                case 'bank':
                    if (empty($searchQuery)) {
                        $db->query("SELECT id, account_code, account_name, balance FROM chart_of_accounts WHERE account_type = 'Asset' AND (account_name LIKE '%Bank%' OR account_name LIKE '%Cash%') LIMIT 10");
                    } else {
                        $db->query("SELECT id, account_code, account_name, balance FROM chart_of_accounts WHERE account_type = 'Asset' AND (account_name LIKE '%Bank%' OR account_name LIKE '%Cash%') AND (account_code LIKE :q OR account_name LIKE :q) LIMIT 10");
                        $db->bind(':q', $likeQuery);
                    }
                    $rows = $db->resultSet() ?: [];
                    foreach ($rows as $row) {
                        $results[] = [
                            'id' => $row->id,
                            'title' => $row->account_name,
                            'subtitle' => 'Code: ' . $row->account_code . ' | Balance: Rs. ' . number_format($row->balance, 2),
                            'url' => APP_URL . '/banking/ledger/' . $row->id,
                            'tag' => 'bank'
                        ];
                    }
                    break;

                case 'stock-transfer':
                    if (empty($searchQuery)) {
                        $db->query("SELECT wt.id, wt.transfer_number, wt.transfer_date, wt.qty, i.name as item_name FROM warehouse_transfers wt JOIN items i ON wt.item_id = i.id ORDER BY wt.id DESC LIMIT 10");
                    } else {
                        $db->query("SELECT wt.id, wt.transfer_number, wt.transfer_date, wt.qty, i.name as item_name FROM warehouse_transfers wt JOIN items i ON wt.item_id = i.id WHERE wt.transfer_number LIKE :q OR i.name LIKE :q LIMIT 10");
                        $db->bind(':q', $likeQuery);
                    }
                    $rows = $db->resultSet() ?: [];
                    foreach ($rows as $row) {
                        $results[] = [
                            'id' => $row->id,
                            'title' => $row->transfer_number,
                            'subtitle' => 'Product: ' . $row->item_name . ' | Date: ' . $row->transfer_date . ' | Qty: ' . $row->qty,
                            'url' => APP_URL . '/warehouse',
                            'tag' => 'stock-transfer'
                        ];
                    }
                    break;

                case 'stock-adjustment':
                    if (empty($searchQuery)) {
                        $db->query("SELECT sl.id, sl.reference_number, sl.transaction_date, (sl.quantity_in - sl.quantity_out) as qty_change, i.name as item_name FROM stock_ledger sl JOIN items i ON sl.item_id = i.id WHERE sl.transaction_type IN ('Adjustment', 'Damage') ORDER BY sl.transaction_date DESC LIMIT 10");
                    } else {
                        $db->query("SELECT sl.id, sl.reference_number, sl.transaction_date, (sl.quantity_in - sl.quantity_out) as qty_change, i.name as item_name FROM stock_ledger sl JOIN items i ON sl.item_id = i.id WHERE sl.transaction_type IN ('Adjustment', 'Damage') AND (sl.reference_number LIKE :q OR i.name LIKE :q) LIMIT 10");
                        $db->bind(':q', $likeQuery);
                    }
                    $rows = $db->resultSet() ?: [];
                    foreach ($rows as $row) {
                        $results[] = [
                            'id' => $row->id,
                            'title' => $row->reference_number ?: 'Adjustment #' . $row->id,
                            'subtitle' => 'Product: ' . $row->item_name . ' | Date: ' . $row->transaction_date . ' | Change: ' . $row->qty_change,
                            'url' => APP_URL . '/inventory',
                            'tag' => 'stock-adjustment'
                        ];
                    }
                    break;

                case 'delivery':
                    if (empty($searchQuery)) {
                        $db->query("SELECT id, delivery_number, delivery_date, status, driver_name FROM deliveries ORDER BY id DESC LIMIT 10");
                    } else {
                        $db->query("SELECT id, delivery_number, delivery_date, status, driver_name FROM deliveries WHERE delivery_number LIKE :q OR driver_name LIKE :q LIMIT 10");
                        $db->bind(':q', $likeQuery);
                    }
                    $rows = $db->resultSet() ?: [];
                    foreach ($rows as $row) {
                        $results[] = [
                            'id' => $row->id,
                            'title' => $row->delivery_number,
                            'subtitle' => 'Driver: ' . ($row->driver_name ?: 'N/A') . ' | Date: ' . $row->delivery_date . ' | Status: ' . $row->status,
                            'url' => APP_URL . '/delivery',
                            'tag' => 'delivery'
                        ];
                    }
                    break;

                case 'credit-note':
                    if (empty($searchQuery)) {
                        $db->query("SELECT c.id, c.credit_note_number, c.note_date, c.total_amount, cust.name as customer_name FROM credit_notes c JOIN customers cust ON c.customer_id = cust.id ORDER BY c.id DESC LIMIT 10");
                    } else {
                        $db->query("SELECT c.id, c.credit_note_number, c.note_date, c.total_amount, cust.name as customer_name FROM credit_notes c JOIN customers cust ON c.customer_id = cust.id WHERE c.credit_note_number LIKE :q OR cust.name LIKE :q LIMIT 10");
                        $db->bind(':q', $likeQuery);
                    }
                    $rows = $db->resultSet() ?: [];
                    foreach ($rows as $row) {
                        $results[] = [
                            'id' => $row->id,
                            'title' => $row->credit_note_number,
                            'subtitle' => 'Customer: ' . $row->customer_name . ' | Date: ' . $row->note_date . ' | Amount: Rs. ' . number_format($row->total_amount, 2),
                            'url' => APP_URL . '/creditnote/show/' . $row->id,
                            'tag' => 'credit-note'
                        ];
                    }
                    break;
            }
        } else {
            // Global search (no tag): search navigation items, modules, screens, and matching reports
            $screens = [
                ['title' => 'Customer Center', 'subtitle' => 'Manage customers, credit limits, accounts receivable (AR)', 'url' => APP_URL . '/customer', 'terms' => ['customer', 'customers', 'clients', 'receivables', 'accounts receivable', 'ar']],
                ['title' => 'Supplier Center', 'subtitle' => 'Manage suppliers, outstanding balances, accounts payable (AP)', 'url' => APP_URL . '/supplier', 'terms' => ['supplier', 'suppliers', 'vendors', 'payables', 'accounts payable', 'ap']],
                ['title' => 'Sales Orders', 'subtitle' => 'Manage customer purchase requests and pending orders', 'url' => APP_URL . '/salesorder', 'terms' => ['sales order', 'sales orders', 'so', 'orders']],
                ['title' => 'Invoices & Sales', 'subtitle' => 'Direct invoicing, billing, voiding, and print receipts', 'url' => APP_URL . '/sales', 'terms' => ['invoice', 'invoices', 'billing', 'sales']],
                ['title' => 'Inventory Catalog', 'subtitle' => 'Manage products, stocks, costs, prices, alerts, and margins', 'url' => APP_URL . '/inventory', 'terms' => ['inventory', 'items', 'products', 'stock', 'sku', 'warehouse stock']],
                ['title' => 'Route Control', 'subtitle' => 'Sales representative route scheduling, GPS tracks, and distribution', 'url' => APP_URL . '/reptracking', 'terms' => ['route', 'routes', 'rep tracking', 'tracking', 'gps', 'sales rep']],
                ['title' => 'Procurement & PO', 'subtitle' => 'Create and track vendor purchase orders', 'url' => APP_URL . '/purchase', 'terms' => ['procurement', 'purchases', 'po', 'purchase order', 'purchase orders']],
                ['title' => 'Customer Payments', 'subtitle' => 'Collect customer cash/cheque payments and reconcile AR', 'url' => APP_URL . '/customerpayment', 'terms' => ['payment', 'payments', 'receipts', 'collections', 'customer payments']],
                ['title' => 'Supplier Payments', 'subtitle' => 'Record AP payments to vendors and allocate to GRNs', 'url' => APP_URL . '/supplierpayment', 'terms' => ['payment', 'payments', 'supplier payments', 'vendor payments', 'ap']],
                ['title' => 'Warehouse & Transfers', 'subtitle' => 'Inventory location management and stock transfers', 'url' => APP_URL . '/warehouse', 'terms' => ['warehouse', 'warehouses', 'locations', 'transfers']],
                ['title' => 'General Journal', 'subtitle' => 'Manual double-entry accounting transactions', 'url' => APP_URL . '/accounting/journal', 'terms' => ['journal', 'general journal', 'entries', 'accounting']],
                ['title' => 'Chart of Accounts', 'subtitle' => 'Structure financial ledgers, assets, liabilities, equities', 'url' => APP_URL . '/accounting', 'terms' => ['chart of accounts', 'accounts', 'coa', 'ledgers']],
                ['title' => 'Banking Center', 'subtitle' => 'Bank statements, cash book, bank reconciliations', 'url' => APP_URL . '/banking', 'terms' => ['banking', 'bank', 'reconcile', 'deposits', 'cash book']],
                ['title' => 'Budgets & Planning', 'subtitle' => 'Fiscal budget allocation and variance monitoring', 'url' => APP_URL . '/budget', 'terms' => ['budget', 'budgets', 'planning']],
                ['title' => 'CRM & Leads', 'subtitle' => 'Manage client opportunities, lead scoring, and history', 'url' => APP_URL . '/crm', 'terms' => ['crm', 'leads', 'opportunities', 'pipeline']],
                ['title' => 'Estimates & Quotes', 'subtitle' => 'Draft and issue cost estimates or quotations', 'url' => APP_URL . '/estimate', 'terms' => ['estimate', 'estimates', 'quotations', 'quotes']],
                ['title' => 'Territories', 'subtitle' => 'Set up sales territories and MCAs', 'url' => APP_URL . '/territory', 'terms' => ['territory', 'territories', 'areas', 'mca']],
                ['title' => 'HRM & Employee Directory', 'subtitle' => 'Manage personnel records, payroll, job designations', 'url' => APP_URL . '/user', 'terms' => ['employee', 'employees', 'staff', 'hr', 'payroll', 'directory']],
                ['title' => 'Cheques Registry', 'subtitle' => 'Registry of issued/received cheques and clear/bounce status', 'url' => APP_URL . '/cheque', 'terms' => ['cheque', 'cheques', 'bank cheques']],
                ['title' => 'Goods Receipt Notes (GRN)', 'subtitle' => 'Receive vendor supplies and auto-allocate stock', 'url' => APP_URL . '/grn', 'terms' => ['grn', 'goods receipt note', 'goods receipt notes', 'receiving']],
                ['title' => 'System Settings', 'subtitle' => 'Company profile, taxation, payment terms, permissions', 'url' => APP_URL . '/settings', 'terms' => ['settings', 'config', 'setup', 'users', 'permissions']]
            ];

            foreach ($screens as $scr) {
                $matched = false;
                if (stripos($scr['title'], $searchQuery) !== false || stripos($scr['subtitle'], $searchQuery) !== false) {
                    $matched = true;
                } else {
                    foreach ($scr['terms'] as $term) {
                        if (stripos($term, $searchQuery) !== false) {
                            $matched = true;
                            break;
                        }
                    }
                }
                if ($matched) {
                    $results[] = [
                        'id' => $scr['url'],
                        'title' => $scr['title'],
                        'subtitle' => $scr['subtitle'],
                        'url' => $scr['url'],
                        'tag' => 'module'
                    ];
                }
            }

            // Also search report titles in ReportEngine
            if (file_exists('../app/Services/ReportEngine.php')) {
                require_once '../app/Services/ReportEngine.php';
            }
            if (class_exists('ReportEngine')) {
                $reportsRegistry = ReportEngine::getReportsRegistry();
                foreach ($reportsRegistry as $key => $report) {
                    if (stripos($report['title'], $searchQuery) !== false || stripos($key, $searchQuery) !== false) {
                        $results[] = [
                            'id' => $key,
                            'title' => 'Report: ' . $report['title'],
                            'subtitle' => 'Category: ' . ($report['category'] ?? 'General'),
                            'url' => APP_URL . '/report/viewer/' . $key,
                            'tag' => 'report'
                        ];
                    }
                }
            }
        }

        echo json_encode($results);
        exit;
    }

    public function getTodos() {
        header('Content-Type: application/json');
        $db = new Database();
        
        // Ensure table exists (self-healing migration as per sync framework principles)
        try {
            $db->query("SELECT 1 FROM todo_items LIMIT 1");
            $db->execute();
        } catch (Exception $e) {
            // Table doesn't exist, create it
            $db->query("CREATE TABLE IF NOT EXISTS todo_items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                task VARCHAR(255) NOT NULL,
                is_completed TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
            $db->execute();
        }

        $db->query("SELECT * FROM todo_items WHERE user_id = :uid ORDER BY created_at DESC");
        $db->bind(':uid', $_SESSION['user_id']);
        $todos = $db->resultSet() ?: [];
        echo json_encode($todos);
        exit;
    }

    public function addTodo() {
        header('Content-Type: application/json');
        $task = trim($_POST['task'] ?? '');
        if (empty($task)) {
            echo json_encode(['success' => false, 'error' => 'Task cannot be empty']);
            exit;
        }
        $db = new Database();
        
        // Ensure table exists
        try {
            $db->query("SELECT 1 FROM todo_items LIMIT 1");
            $db->execute();
        } catch (Exception $e) {
            $db->query("CREATE TABLE IF NOT EXISTS todo_items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                task VARCHAR(255) NOT NULL,
                is_completed TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
            $db->execute();
        }

        $db->query("INSERT INTO todo_items (user_id, task, is_completed) VALUES (:uid, :task, 0)");
        $db->bind(':uid', $_SESSION['user_id']);
        $db->bind(':task', $task);
        if ($db->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to save task']);
        }
        exit;
    }

    public function toggleTodo() {
        header('Content-Type: application/json');
        $id = intval($_POST['id'] ?? 0);
        if (!$id) {
            echo json_encode(['success' => false, 'error' => 'Invalid ID']);
            exit;
        }
        $db = new Database();
        $db->query("SELECT is_completed FROM todo_items WHERE id = :id AND user_id = :uid");
        $db->bind(':id', $id);
        $db->bind(':uid', $_SESSION['user_id']);
        $row = $db->single();
        if (!$row) {
            echo json_encode(['success' => false, 'error' => 'Task not found']);
            exit;
        }
        $newVal = $row->is_completed ? 0 : 1;
        $db->query("UPDATE todo_items SET is_completed = :val WHERE id = :id AND user_id = :uid");
        $db->bind(':val', $newVal);
        $db->bind(':id', $id);
        $db->bind(':uid', $_SESSION['user_id']);
        if ($db->execute()) {
            echo json_encode(['success' => true, 'is_completed' => $newVal]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to update task']);
        }
        exit;
    }

    public function deleteTodo() {
        header('Content-Type: application/json');
        $id = intval($_POST['id'] ?? 0);
        if (!$id) {
            echo json_encode(['success' => false, 'error' => 'Invalid ID']);
            exit;
        }
        $db = new Database();
        $db->query("DELETE FROM todo_items WHERE id = :id AND user_id = :uid");
        $db->bind(':id', $id);
        $db->bind(':uid', $_SESSION['user_id']);
        if ($db->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to delete task']);
        }
        exit;
    }
}
