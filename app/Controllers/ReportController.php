<?php
/**
 * Curtiss ERP - Central Report Viewer Controller
 */
require_once '../app/Services/ReportEngine.php';

class ReportController extends Controller {
    private $engine;

    public function __construct() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . APP_URL . '/auth/login');
            exit;
        }
        $this->generateCsrfToken();
        $this->engine = new ReportEngine();
    }

    /**
     * Centralized Reporting Hub Dashboard
     */
    public function index() {
        $categories = ReportEngine::getCategories();
        $reports = ReportEngine::getReportsRegistry();

        // Group reports by category
        $groupedReports = [];
        foreach ($categories as $catKey => $catTitle) {
            $groupedReports[$catKey] = [];
        }
        foreach ($reports as $key => $rep) {
            $groupedReports[$rep['category']][$key] = $rep;
        }

        $data = [
            'title' => 'Central Reporting Hub',
            'content_view' => 'reports/index',
            'categories' => $categories,
            'grouped_reports' => $groupedReports
        ];
        $this->view('layouts/main', $data);
    }

    /**
     * Render the Reusable Central Report Viewer Page
     */
    public function viewer($reportKey = null) {
        if (!$reportKey) {
            header('Location: ' . APP_URL . '/report');
            exit;
        }

        $registry = ReportEngine::getReportsRegistry();
        if (!isset($registry[$reportKey])) {
            die("Report '$reportKey' is not registered.");
        }

        if ($reportKey === 'balance_sheet') {
            $companyModel = $this->model('Company');
            $company = $companyModel->getSettings();
            $reportModel = $this->model('Report');
            $endDate = $_GET['end_date'] ?? date('Y-m-d');

            // Calculate Net Income (P&L Accounts: Revenue - Expense)
            $plAccounts = $reportModel->getAccountsByTypes(['Revenue', 'Expense'], $endDate);
            $netIncome = 0;
            foreach ($plAccounts as $acc) {
                if ($acc->account_type == 'Revenue') {
                    $netIncome += $acc->balance;
                } else {
                    $netIncome -= $acc->balance;
                }
            }

            // Get balance sheet accounts (Asset, Liability, Equity)
            $bsAccounts = $reportModel->getAccountsByTypes(['Asset', 'Liability', 'Equity'], $endDate);
            $assets = [];
            $liabilities = [];
            $equities = [];
            $totalAssets = 0;
            $totalLiabilities = 0;
            $totalEquity = 0;

            foreach ($bsAccounts as $acc) {
                if ($acc->account_type == 'Asset') {
                    $assets[] = $acc;
                    $totalAssets += $acc->balance;
                } elseif ($acc->account_type == 'Liability') {
                    $liabilities[] = $acc;
                    $totalLiabilities += $acc->balance;
                } elseif ($acc->account_type == 'Equity') {
                    $equities[] = $acc;
                    $totalEquity += $acc->balance;
                }
            }

            $totalEquity += $netIncome;
            $totalLiabilitiesAndEquity = $totalLiabilities + $totalEquity;

            $data = [
                'title' => 'Balance Sheet',
                'content_view' => 'reports/balance_sheet',
                'company' => $company,
                'assets' => $assets,
                'liabilities' => $liabilities,
                'equities' => $equities,
                'total_assets' => $totalAssets,
                'total_liabilities' => $totalLiabilities,
                'net_income' => $netIncome,
                'total_equity' => $totalEquity,
                'total_liabilities_equity' => $totalLiabilitiesAndEquity,
                'end_date' => $endDate
            ];
            $this->view('layouts/main', $data);
            return;
        }

        if ($reportKey === 'cash_flow') {
            $companyModel = $this->model('Company');
            $company = $companyModel->getSettings();
            $reportModel = $this->model('Report');
            $endDate = $_GET['end_date'] ?? date('Y-m-d');

            $accounts = $reportModel->getAccountsByTypes(['Asset', 'Liability', 'Equity', 'Revenue', 'Expense'], $endDate);

            $netIncome = 0; $operating = []; $investing = []; $financing = []; $cashBalance = 0;

            foreach($accounts as $acc) {
                $nameStr = strtolower($acc->account_name);
                if ($acc->account_type == 'Revenue') { $netIncome += $acc->balance; }
                elseif ($acc->account_type == 'Expense') { $netIncome -= $acc->balance; }
                
                if ($acc->account_type == 'Asset') {
                    if (strpos($nameStr, 'cash') !== false || strpos($nameStr, 'bank') !== false) {
                        $cashBalance += $acc->balance; 
                    } elseif (strpos($nameStr, 'equipment') !== false || strpos($nameStr, 'asset') !== false) {
                        $investing[] = ['name' => 'Purchase of ' . $acc->account_name, 'amount' => -$acc->balance]; 
                    } else {
                        $operating[] = ['name' => 'Change in ' . $acc->account_name, 'amount' => -$acc->balance]; 
                    }
                } elseif ($acc->account_type == 'Liability') {
                    $operating[] = ['name' => 'Change in ' . $acc->account_name, 'amount' => $acc->balance]; 
                } elseif ($acc->account_type == 'Equity') {
                    if (strpos($nameStr, 'retained') === false) { 
                        $financing[] = ['name' => 'Change in ' . $acc->account_name, 'amount' => $acc->balance];
                    }
                }
            }

            $data = [
                'title' => 'Statement of Cash Flows',
                'content_view' => 'reports/cash_flow',
                'company' => $company,
                'net_income' => $netIncome,
                'operating' => $operating,
                'investing' => $investing,
                'financing' => $financing,
                'ending_cash' => $cashBalance,
                'end_date' => $endDate
            ];
            $this->view('layouts/main', $data);
            return;
        }

        if ($reportKey === 'multi_period_comparison') {
            $companyModel = $this->model('Company');
            $company = $companyModel->getSettings();
            $reportModel = $this->model('Report');

            // Get selected dates or defaults
            $startDate = $_GET['start_date'] ?? date('Y-m-01');
            $endDate = $_GET['end_date'] ?? date('Y-m-t');
            $comparisonType = $_GET['comparison_type'] ?? 'mom';

            if ($comparisonType === 'yoy') {
                $compStartDate = date('Y-m-d', strtotime('-1 year', strtotime($startDate)));
                $compEndDate = date('Y-m-d', strtotime('-1 year', strtotime($endDate)));
            } else {
                $compStartDate = date('Y-m-d', strtotime('-1 month', strtotime($startDate)));
                $compEndDate = date('Y-m-d', strtotime('-1 month', strtotime($endDate)));
            }

            $db = new Database();
            $db->query("SELECT c.id, c.account_code, c.account_name, c.account_type,
                               SUM(CASE WHEN c.account_type IN ('Asset', 'Expense') THEN (COALESCE(t.debit, 0) - COALESCE(t.credit, 0))
                                        ELSE (COALESCE(t.credit, 0) - COALESCE(t.debit, 0)) END) as balance
                        FROM chart_of_accounts c
                        LEFT JOIN transactions t ON c.id = t.account_id
                        LEFT JOIN journal_entries je ON t.journal_entry_id = je.id AND je.status = 'Posted'
                            AND je.entry_date BETWEEN :start AND :end
                        GROUP BY c.id, c.account_code, c.account_name, c.account_type
                        ORDER BY c.account_code ASC");
            $db->bind(':start', $startDate);
            $db->bind(':end', $endDate);
            $baseBalances = $db->resultSet() ?: [];

            $db->query("SELECT c.id,
                               SUM(CASE WHEN c.account_type IN ('Asset', 'Expense') THEN (COALESCE(t.debit, 0) - COALESCE(t.credit, 0))
                                        ELSE (COALESCE(t.credit, 0) - COALESCE(t.debit, 0)) END) as balance
                        FROM chart_of_accounts c
                        LEFT JOIN transactions t ON c.id = t.account_id
                        LEFT JOIN journal_entries je ON t.journal_entry_id = je.id AND je.status = 'Posted'
                            AND je.entry_date BETWEEN :start AND :end
                        GROUP BY c.id");
            $db->bind(':start', $compStartDate);
            $db->bind(':end', $compEndDate);
            $compBalancesRaw = $db->resultSet() ?: [];
            
            $compBalances = [];
            foreach ($compBalancesRaw as $cb) {
                $compBalances[$cb->id] = $cb->balance;
            }

            $comparisonData = [];
            foreach ($baseBalances as $bb) {
                $compVal = $compBalances[$bb->id] ?? 0.0;
                $variance = $bb->balance - $compVal;
                $pctChange = 0.0;
                if (round($compVal, 2) != 0.0) {
                    $pctChange = ($variance / abs($compVal)) * 100;
                } elseif (round($bb->balance, 2) != 0.0) {
                    $pctChange = 100.0;
                }

                $comparisonData[] = [
                    'account_code' => $bb->account_code,
                    'account_name' => $bb->account_name,
                    'account_type' => $bb->account_type,
                    'base_balance' => $bb->balance,
                    'comp_balance' => $compVal,
                    'variance' => $variance,
                    'pct_change' => $pctChange
                ];
            }

            $data = [
                'title' => 'Multi-Period Comparative Report',
                'content_view' => 'reports/multi_period_comparison',
                'company' => $company,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'comp_start_date' => $compStartDate,
                'comp_end_date' => $compEndDate,
                'comparison_type' => $comparisonType,
                'comparison_data' => $comparisonData
            ];
            $this->view('layouts/main', $data);
            return;
        }

        $reportMetadata = $registry[$reportKey];

        // Fetch dynamic filter options from the database
        $db = new Database();

        $db->query("SELECT id, name FROM customers ORDER BY name ASC");
        $customers = $db->resultSet() ?: [];

        $db->query("SELECT id, name FROM vendors ORDER BY name ASC");
        $suppliers = $db->resultSet() ?: [];

        $db->query("SELECT id, name FROM items ORDER BY name ASC");
        $products = $db->resultSet() ?: [];

        $db->query("SELECT id, name FROM warehouses ORDER BY name ASC");
        $warehouses = $db->resultSet() ?: [];

        $db->query("SELECT id, route_name FROM rep_daily_routes ORDER BY route_name ASC");
        $routes = $db->resultSet() ?: [];

        $db->query("SELECT id, name FROM item_categories ORDER BY name ASC");
        $categories = $db->resultSet() ?: [];

        // Additional global filters:
        $db->query("SELECT DISTINCT brand FROM items WHERE brand IS NOT NULL AND brand != '' ORDER BY brand ASC");
        $brands = $db->resultSet() ?: [];

        $db->query("SELECT DISTINCT customer_type as name FROM customers WHERE customer_type IS NOT NULL AND customer_type != '' ORDER BY customer_type ASC");
        $groups = $db->resultSet() ?: [];

        $db->query("SELECT id, vehicle_number FROM vehicles WHERE status = 'Active' ORDER BY vehicle_number ASC");
        $vehicles = $db->resultSet() ?: [];

        $db->query("SELECT id, CONCAT(first_name, ' ', last_name) as name FROM employees WHERE job_title = 'Driver' AND status = 'Active' ORDER BY first_name ASC");
        $drivers = $db->resultSet() ?: [];

        $db->query("SELECT DISTINCT partner_name as name FROM deliveries WHERE partner_name IS NOT NULL AND partner_name != '' ORDER BY partner_name ASC");
        $partners = $db->resultSet() ?: [];

        $db->query("SELECT DISTINCT territory FROM customers WHERE territory IS NOT NULL AND territory != '' ORDER BY territory ASC");
        $territories = $db->resultSet() ?: [];

        $db->query("SELECT id, username as name FROM users WHERE role = 'rep' ORDER BY username ASC");
        $reps = $db->resultSet() ?: [];

        $payment_methods = [
            (object)['id' => 'Cash', 'name' => 'Cash'],
            (object)['id' => 'Bank Transfer', 'name' => 'Bank Transfer'],
            (object)['id' => 'Cheque', 'name' => 'Cheque']
        ];

        $statuses = [
            (object)['id' => 'Active', 'name' => 'Active'],
            (object)['id' => 'Paid', 'name' => 'Paid'],
            (object)['id' => 'Unpaid', 'name' => 'Unpaid'],
            (object)['id' => 'Pending', 'name' => 'Pending'],
            (object)['id' => 'Cleared', 'name' => 'Cleared'],
            (object)['id' => 'Bounced', 'name' => 'Bounced'],
            (object)['id' => 'Voided', 'name' => 'Voided']
        ];

        $data = [
            'title' => $reportMetadata['title'] . ' - Viewer',
            'content_view' => 'reports/viewer',
            'reportKey' => $reportKey,
            'metadata' => $reportMetadata,
            'customers' => $customers,
            'suppliers' => $suppliers,
            'products' => $products,
            'warehouses' => $warehouses,
            'routes' => $routes,
            'categories' => $categories,
            'brands' => $brands,
            'groups' => $groups,
            'vehicles' => $vehicles,
            'drivers' => $drivers,
            'partners' => $partners,
            'territories' => $territories,
            'reps' => $reps,
            'payment_methods' => $payment_methods,
            'statuses' => $statuses
        ];

        $this->view('layouts/main', $data);
    }

    /**
     * JSON API Endpoint to fetch paginated report data
     */
    public function fetch_data() {
        header('Content-Type: application/json');
        
        $reportKey = $_GET['report'] ?? '';
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
        $sortCol = $_GET['sort_col'] ?? null;
        $sortDir = $_GET['sort_dir'] ?? 'ASC';

        // Extract active filters
        $filters = [
            'start_date' => $_GET['start_date'] ?? null,
            'end_date' => $_GET['end_date'] ?? null,
            'customer' => $_GET['customer'] ?? null,
            'supplier' => $_GET['supplier'] ?? null,
            'product' => $_GET['product'] ?? null,
            'warehouse' => $_GET['warehouse'] ?? null,
            'route' => $_GET['route'] ?? null,
            'category' => $_GET['category'] ?? null,
            'rep' => $_GET['rep'] ?? null,
            'payment_method' => $_GET['payment_method'] ?? null,
            'status' => $_GET['status'] ?? null,
            'brand' => $_GET['brand'] ?? null,
            'group' => $_GET['group'] ?? null,
            'vehicle' => $_GET['vehicle'] ?? null,
            'driver' => $_GET['driver'] ?? null,
            'partner' => $_GET['partner'] ?? null,
            'territory' => $_GET['territory'] ?? null,
        ];

        try {
            $result = $this->engine->fetchData($reportKey, $filters, $page, $limit, $sortCol, $sortDir);
            echo json_encode(array_merge(['success' => true], $result));
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * AJAX Endpoint for Side Panel Quick View
     */
    public function quick_view() {
        header('Content-Type: application/json');
        
        $type = $_GET['type'] ?? '';
        $id = $_GET['id'] ?? '';
        $number = $_GET['number'] ?? '';

        if (empty($type)) {
            echo json_encode(['success' => false, 'message' => 'Type is required.']);
            exit;
        }

        $db = new Database();

        try {
            switch ($type) {
                case 'customer':
                    if ($id) {
                        $db->query("SELECT * FROM customers WHERE id = :id");
                        $db->bind(':id', $id);
                    } else {
                        $db->query("SELECT * FROM customers WHERE name = :name LIMIT 1");
                        $db->bind(':name', $number);
                    }
                    $customer = $db->single();
                    if (!$customer) {
                        echo json_encode(['success' => false, 'message' => 'Customer not found.']);
                        exit;
                    }

                    // Outstanding balance formula
                    $db->query("
                        SELECT 
                            (SELECT COALESCE(SUM(total_amount - COALESCE(CASE WHEN global_discount_type = '%' THEN (total_amount * global_discount_val / 100) ELSE global_discount_val END, 0) + COALESCE(tax_amount, 0)), 0) FROM invoices WHERE customer_id = :cid1 AND status != 'Voided') - 
                            (SELECT COALESCE(SUM(amount), 0) FROM customer_payments WHERE customer_id = :cid2 AND status = 'Active') - 
                            (SELECT COALESCE(SUM(total_amount), 0) FROM credit_notes WHERE customer_id = :cid3)
                            AS outstanding_balance
                    ");
                    $db->bind(':cid1', $customer->id);
                    $db->bind(':cid2', $customer->id);
                    $db->bind(':cid3', $customer->id);
                    $balanceRow = $db->single();
                    $outstanding = $balanceRow ? floatval($balanceRow->outstanding_balance) : 0.00;

                    // Recent Invoices (limit 5)
                    $db->query("
                        SELECT id, invoice_number, invoice_date, status, 
                               (total_amount - COALESCE(CASE WHEN global_discount_type = '%' THEN (total_amount * global_discount_val / 100) ELSE global_discount_val END, 0) + COALESCE(tax_amount, 0)) as total_amount 
                        FROM invoices 
                        WHERE customer_id = :cid 
                        ORDER BY invoice_date DESC, id DESC LIMIT 5
                    ");
                    $db->bind(':cid', $customer->id);
                    $invoices = $db->resultSet() ?: [];

                    // Recent Payments (limit 5)
                    $db->query("
                        SELECT id, payment_date, payment_method, reference, amount, status 
                        FROM customer_payments 
                        WHERE customer_id = :cid AND status = 'Active'
                        ORDER BY payment_date DESC, id DESC LIMIT 5
                    ");
                    $db->bind(':cid', $customer->id);
                    $payments = $db->resultSet() ?: [];

                    echo json_encode([
                        'success' => true,
                        'entity' => [
                            'id' => $customer->id,
                            'name' => $customer->name,
                            'phone' => $customer->phone,
                            'email' => $customer->email,
                            'address' => $customer->address,
                            'territory' => $customer->territory,
                            'customer_type' => $customer->customer_type ?? 'Standard',
                            'outstanding_balance' => $outstanding
                        ],
                        'invoices' => $invoices,
                        'payments' => $payments
                    ]);
                    break;

                case 'product':
                    if ($id) {
                        $db->query("SELECT * FROM items WHERE id = :id");
                        $db->bind(':id', $id);
                    } else {
                        $db->query("SELECT * FROM items WHERE name = :name OR item_code = :code LIMIT 1");
                        $db->bind(':name', $number);
                        $db->bind(':code', $number);
                    }
                    $product = $db->single();
                    if (!$product) {
                        echo json_encode(['success' => false, 'message' => 'Product not found.']);
                        exit;
                    }

                    // Warehouse stock
                    $db->query("
                        SELECT w.name as warehouse_name, COALESCE(SUM(sl.quantity_in - sl.quantity_out), 0) as quantity 
                        FROM stock_ledger sl 
                        JOIN warehouses w ON sl.warehouse_id = w.id 
                        WHERE sl.item_id = :id 
                        GROUP BY w.id, w.name
                    ");
                    $db->bind(':id', $product->id);
                    $warehouseStock = $db->resultSet() ?: [];

                    // Recent Sales movement (limit 5)
                    $db->query("
                        SELECT sl.transaction_date as date, sl.reference_number as ref, sl.quantity_out as qty, sl.unit_cost, sl.total_value 
                        FROM stock_ledger sl 
                        WHERE sl.item_id = :id AND sl.quantity_out > 0 
                        ORDER BY sl.transaction_date DESC, sl.id DESC LIMIT 5
                    ");
                    $db->bind(':id', $product->id);
                    $recentSales = $db->resultSet() ?: [];

                    echo json_encode([
                        'success' => true,
                        'entity' => [
                            'id' => $product->id,
                            'name' => $product->name,
                            'item_code' => $product->item_code,
                            'brand' => $product->brand ?? 'N/A',
                            'price' => $product->selling_price ?? $product->price ?? 0.00,
                            'cost' => $product->cost ?? $product->cost_price ?? 0.00,
                            'qty_on_hand' => $product->quantity_on_hand
                        ],
                        'stock' => $warehouseStock,
                        'sales' => $recentSales
                    ]);
                    break;

                case 'invoice':
                    if ($id) {
                        $db->query("SELECT i.*, c.name as customer_name FROM invoices i JOIN customers c ON i.customer_id = c.id WHERE i.id = :id");
                        $db->bind(':id', $id);
                    } else {
                        $db->query("SELECT i.*, c.name as customer_name FROM invoices i JOIN customers c ON i.customer_id = c.id WHERE i.invoice_number = :num LIMIT 1");
                        $db->bind(':num', $number);
                    }
                    $invoice = $db->single();
                    if (!$invoice) {
                        echo json_encode(['success' => false, 'message' => 'Invoice not found.']);
                        exit;
                    }

                    // Get line items
                    $db->query("
                        SELECT ii.*, item.name as item_name, item.item_code 
                        FROM invoice_items ii 
                        JOIN items item ON ii.item_id = item.id 
                        WHERE ii.invoice_id = :id
                    ");
                    $db->bind(':id', $invoice->id);
                    $items = $db->resultSet() ?: [];

                    // Get payment allocations
                    $db->query("
                        SELECT pa.amount, cp.payment_date, cp.payment_method, cp.reference 
                        FROM customer_payment_allocations pa 
                        JOIN customer_payments cp ON pa.customer_payment_id = cp.id 
                        WHERE pa.invoice_id = :id AND pa.is_reversed = 0
                    ");
                    $db->bind(':id', $invoice->id);
                    $allocations = $db->resultSet() ?: [];

                    echo json_encode([
                        'success' => true,
                        'entity' => [
                            'id' => $invoice->id,
                            'invoice_number' => $invoice->invoice_number,
                            'invoice_date' => $invoice->invoice_date,
                            'due_date' => $invoice->due_date,
                            'customer_name' => $invoice->customer_name,
                            'status' => $invoice->status,
                            'total' => $invoice->total_amount,
                            'discount' => $invoice->global_discount_val,
                            'tax' => $invoice->tax_amount,
                            'net_total' => ($invoice->total_amount - ($invoice->global_discount_type === '%' ? ($invoice->total_amount * $invoice->global_discount_val / 100) : $invoice->global_discount_val) + $invoice->tax_amount)
                        ],
                        'items' => $items,
                        'allocations' => $allocations
                    ]);
                    break;

                case 'route':
                    if ($id) {
                        $db->query("SELECT * FROM rep_daily_routes WHERE id = :id");
                        $db->bind(':id', $id);
                    } else {
                        $db->query("SELECT * FROM rep_daily_routes WHERE route_name = :name LIMIT 1");
                        $db->bind(':name', $number);
                    }
                    $route = $db->single();
                    if (!$route) {
                        echo json_encode(['success' => false, 'message' => 'Route not found.']);
                        exit;
                    }

                    // Route statistics
                    $db->query("SELECT COUNT(*) as cust_count FROM customers WHERE route_id = :rid OR territory = :route_name");
                    $db->bind(':rid', $route->id);
                    $db->bind(':route_name', $route->route_name);
                    $custCountRow = $db->single();
                    $custCount = $custCountRow ? intval($custCountRow->cust_count) : 0;

                    $db->query("
                        SELECT COUNT(*) as inv_count, COALESCE(SUM(total_amount),0) as total_sales 
                        FROM invoices i 
                        JOIN customers c ON i.customer_id = c.id 
                        WHERE c.route_id = :rid OR c.territory = :route_name
                    ");
                    $db->bind(':rid', $route->id);
                    $db->bind(':route_name', $route->route_name);
                    $invStats = $db->single();
                    $invCount = $invStats ? intval($invStats->inv_count) : 0;
                    $totalSales = $invStats ? floatval($invStats->total_sales) : 0.00;

                    $db->query("
                        SELECT COALESCE(SUM(cp.amount),0) as total_collections 
                        FROM customer_payments cp 
                        JOIN customers c ON cp.customer_id = c.id 
                        WHERE (c.route_id = :rid OR c.territory = :route_name) AND cp.status = 'Active'
                    ");
                    $db->bind(':rid', $route->id);
                    $db->bind(':route_name', $route->route_name);
                    $collRow = $db->single();
                    $totalCollections = $collRow ? floatval($collRow->total_collections) : 0.00;

                    echo json_encode([
                        'success' => true,
                        'entity' => [
                            'id' => $route->id,
                            'route_name' => $route->route_name,
                            'description' => $route->description ?? 'N/A',
                            'cust_count' => $custCount,
                            'inv_count' => $invCount,
                            'total_sales' => $totalSales,
                            'total_collections' => $totalCollections,
                            'outstanding' => $totalSales - $totalCollections
                        ]
                    ]);
                    break;

                case 'supplier':
                    if ($id) {
                        $db->query("SELECT * FROM vendors WHERE id = :id");
                        $db->bind(':id', $id);
                    } else {
                        $db->query("SELECT * FROM vendors WHERE name = :name LIMIT 1");
                        $db->bind(':name', $number);
                    }
                    $supplier = $db->single();
                    if (!$supplier) {
                        echo json_encode(['success' => false, 'message' => 'Supplier not found.']);
                        exit;
                    }

                    // Outstanding balance formula
                    $db->query("
                        SELECT 
                            (SELECT COALESCE(SUM(gri.total), 0) FROM grn_items gri JOIN goods_receipt_notes grn ON gri.grn_id = grn.id WHERE grn.vendor_id = :vid1 AND grn.is_approved = 1) - 
                            (SELECT COALESCE(SUM(amount), 0) FROM supplier_payments WHERE vendor_id = :vid2 AND status = 'Active') - 
                            (SELECT COALESCE(SUM(total_amount), 0) FROM supplier_returns WHERE vendor_id = :vid3) 
                            AS outstanding_balance
                    ");
                    $db->bind(':vid1', $supplier->id);
                    $db->bind(':vid2', $supplier->id);
                    $db->bind(':vid3', $supplier->id);
                    $balanceRow = $db->single();
                    $outstanding = $balanceRow ? floatval($balanceRow->outstanding_balance) : 0.00;

                    // Recent GRNs
                    $db->query("
                        SELECT grn.id, grn.grn_number, grn.grn_date, COALESCE(SUM(gri.total), 0) as total 
                        FROM goods_receipt_notes grn 
                        LEFT JOIN grn_items gri ON gri.grn_id = grn.id 
                        WHERE grn.vendor_id = :vid AND grn.is_approved = 1 
                        GROUP BY grn.id, grn.grn_number, grn.grn_date 
                        ORDER BY grn.grn_date DESC LIMIT 5
                    ");
                    $db->bind(':vid', $supplier->id);
                    $grns = $db->resultSet() ?: [];

                    echo json_encode([
                        'success' => true,
                        'entity' => [
                            'id' => $supplier->id,
                            'name' => $supplier->name,
                            'phone' => $supplier->phone,
                            'email' => $supplier->email,
                            'address' => $supplier->address,
                            'outstanding_balance' => $outstanding
                        ],
                        'grns' => $grns
                    ]);
                    break;

                case 'po':
                    if ($id) {
                        $db->query("SELECT p.*, v.name as supplier_name FROM purchase_orders p JOIN vendors v ON p.vendor_id = v.id WHERE p.id = :id");
                        $db->bind(':id', $id);
                    } else {
                        $db->query("SELECT p.*, v.name as supplier_name FROM purchase_orders p JOIN vendors v ON p.vendor_id = v.id WHERE p.po_number = :num LIMIT 1");
                        $db->bind(':num', $number);
                    }
                    $po = $db->single();
                    if (!$po) {
                        echo json_encode(['success' => false, 'message' => 'PO not found.']);
                        exit;
                    }
                    echo json_encode([
                        'success' => true,
                        'entity' => [
                            'id' => $po->id,
                            'po_number' => $po->po_number,
                            'po_date' => $po->po_date ?? $po->created_at,
                            'supplier_name' => $po->supplier_name,
                            'status' => $po->status ?? 'Pending',
                            'total' => $po->total_amount ?? 0.00
                        ]
                    ]);
                    break;

                case 'grn':
                    if ($id) {
                        $db->query("SELECT g.*, v.name as supplier_name FROM goods_receipt_notes g JOIN vendors v ON g.vendor_id = v.id WHERE g.id = :id");
                        $db->bind(':id', $id);
                    } else {
                        $db->query("SELECT g.*, v.name as supplier_name FROM goods_receipt_notes g JOIN vendors v ON g.vendor_id = v.id WHERE g.grn_number = :num LIMIT 1");
                        $db->bind(':num', $number);
                    }
                    $grn = $db->single();
                    if (!$grn) {
                        echo json_encode(['success' => false, 'message' => 'GRN not found.']);
                        exit;
                    }
                    $db->query("SELECT COALESCE(SUM(total), 0) as total FROM grn_items WHERE grn_id = :id");
                    $db->bind(':id', $grn->id);
                    $totRow = $db->single();
                    $total = $totRow ? floatval($totRow->total) : 0.00;

                    echo json_encode([
                        'success' => true,
                        'entity' => [
                            'id' => $grn->id,
                            'grn_number' => $grn->grn_number,
                            'grn_date' => $grn->grn_date,
                            'supplier_name' => $grn->supplier_name,
                            'is_approved' => $grn->is_approved,
                            'total' => $total
                        ]
                    ]);
                    break;

                case 'payment':
                    if ($id) {
                        $db->query("SELECT p.*, c.name as customer_name FROM customer_payments p JOIN customers c ON p.customer_id = c.id WHERE p.id = :id");
                        $db->bind(':id', $id);
                    } else {
                        $db->query("SELECT p.*, c.name as customer_name FROM customer_payments p JOIN customers c ON p.customer_id = c.id WHERE p.reference = :ref LIMIT 1");
                        $db->bind(':ref', $number);
                    }
                    $payment = $db->single();
                    if (!$payment) {
                        echo json_encode(['success' => false, 'message' => 'Payment not found.']);
                        exit;
                    }
                    echo json_encode([
                        'success' => true,
                        'entity' => [
                            'id' => $payment->id,
                            'reference' => $payment->reference,
                            'payment_date' => $payment->payment_date,
                            'payment_method' => $payment->payment_method,
                            'amount' => $payment->amount,
                            'customer_name' => $payment->customer_name,
                            'status' => $payment->status
                        ]
                    ]);
                    break;

                case 'cheque':
                    if ($id) {
                        $db->query("SELECT ch.*, c.name as customer_name FROM cheques ch LEFT JOIN customers c ON ch.customer_id = c.id WHERE ch.id = :id");
                        $db->bind(':id', $id);
                    } else {
                        $db->query("SELECT ch.*, c.name as customer_name FROM cheques ch LEFT JOIN customers c ON ch.customer_id = c.id WHERE ch.cheque_number = :num LIMIT 1");
                        $db->bind(':num', $number);
                    }
                    $cheque = $db->single();
                    if (!$cheque) {
                        echo json_encode(['success' => false, 'message' => 'Cheque not found.']);
                        exit;
                    }
                    echo json_encode([
                        'success' => true,
                        'entity' => [
                            'id' => $cheque->id,
                            'cheque_number' => $cheque->cheque_number,
                            'bank_name' => $cheque->bank_name,
                            'amount' => $cheque->amount,
                            'banking_date' => $cheque->banking_date,
                            'customer_name' => $cheque->customer_name ?? 'N/A',
                            'status' => $cheque->status
                        ]
                    ]);
                    break;

                case 'driver':
                    if ($id) {
                        $db->query("SELECT id, CONCAT(first_name, ' ', last_name) as name, email, phone FROM employees WHERE id = :id");
                        $db->bind(':id', $id);
                    } else {
                        $db->query("SELECT id, CONCAT(first_name, ' ', last_name) as name, email, phone FROM employees WHERE CONCAT(first_name, ' ', last_name) = :name LIMIT 1");
                        $db->bind(':name', $number);
                    }
                    $driver = $db->single();
                    if (!$driver) {
                        echo json_encode(['success' => false, 'message' => 'Driver not found.']);
                        exit;
                    }
                    echo json_encode([
                        'success' => true,
                        'entity' => [
                            'id' => $driver->id,
                            'name' => $driver->name,
                            'email' => $driver->email ?? 'N/A',
                            'phone' => $driver->phone ?? 'N/A',
                            'role' => 'Driver'
                        ]
                    ]);
                    break;

                case 'vehicle':
                    if ($id) {
                        $db->query("SELECT * FROM vehicles WHERE id = :id");
                        $db->bind(':id', $id);
                    } else {
                        $db->query("SELECT * FROM vehicles WHERE vehicle_number = :num LIMIT 1");
                        $db->bind(':num', $number);
                    }
                    $vehicle = $db->single();
                    if (!$vehicle) {
                        echo json_encode(['success' => false, 'message' => 'Vehicle not found.']);
                        exit;
                    }
                    echo json_encode([
                        'success' => true,
                        'entity' => [
                            'id' => $vehicle->id,
                            'vehicle_number' => $vehicle->vehicle_number,
                            'vehicle_type' => $vehicle->vehicle_type ?? 'N/A',
                            'status' => $vehicle->status
                        ]
                    ]);
                    break;

                case 'rep':
                    if ($id) {
                        $db->query("SELECT id, username, email FROM users WHERE id = :id");
                        $db->bind(':id', $id);
                    } else {
                        $db->query("SELECT id, username, email FROM users WHERE username = :name LIMIT 1");
                        $db->bind(':name', $number);
                    }
                    $rep = $db->single();
                    if (!$rep) {
                        echo json_encode(['success' => false, 'message' => 'Sales Rep not found.']);
                        exit;
                    }
                    echo json_encode([
                        'success' => true,
                        'entity' => [
                            'id' => $rep->id,
                            'name' => $rep->username,
                            'email' => $rep->email ?? 'N/A',
                            'role' => 'Sales Representative'
                        ]
                    ]);
                    break;

                case 'warehouse':
                    if ($id) {
                        $db->query("SELECT * FROM warehouses WHERE id = :id");
                        $db->bind(':id', $id);
                    } else {
                        $db->query("SELECT * FROM warehouses WHERE name = :name LIMIT 1");
                        $db->bind(':name', $number);
                    }
                    $warehouse = $db->single();
                    if (!$warehouse) {
                        echo json_encode(['success' => false, 'message' => 'Warehouse not found.']);
                        exit;
                    }
                    echo json_encode([
                        'success' => true,
                        'entity' => [
                            'id' => $warehouse->id,
                            'name' => $warehouse->name,
                            'code' => $warehouse->code ?? 'N/A',
                            'address' => $warehouse->address ?? 'N/A'
                        ]
                    ]);
                    break;

                default:
                    echo json_encode(['success' => false, 'message' => 'Invalid quick view type.']);
                    exit;
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    protected function validateCsrf() {
        $this->validateCsrfOrDie();
    }

    /**
     * Export Report to Excel, CSV, Word, PDF, JSON, XML
     */
    public function export($reportKey = null) {
        if (!$reportKey) {
            die("Report key is required.");
        }

        $registry = ReportEngine::getReportsRegistry();
        if (!isset($registry[$reportKey])) {
            die("Report not registered.");
        }

        $metadata = $registry[$reportKey];
        $format = $_GET['format'] ?? 'csv';

        // Extract filters
        $filters = [
            'start_date' => $_GET['start_date'] ?? null,
            'end_date' => $_GET['end_date'] ?? null,
            'customer' => $_GET['customer'] ?? null,
            'supplier' => $_GET['supplier'] ?? null,
            'product' => $_GET['product'] ?? null,
            'warehouse' => $_GET['warehouse'] ?? null,
            'route' => $_GET['route'] ?? null,
            'category' => $_GET['category'] ?? null,
            'rep' => $_GET['rep'] ?? null,
            'payment_method' => $_GET['payment_method'] ?? null,
            'status' => $_GET['status'] ?? null,
            'brand' => $_GET['brand'] ?? null,
            'group' => $_GET['group'] ?? null,
            'vehicle' => $_GET['vehicle'] ?? null,
            'driver' => $_GET['driver'] ?? null,
            'partner' => $_GET['partner'] ?? null,
            'territory' => $_GET['territory'] ?? null,
        ];

        // Fetch all matching data (large limit for exports)
        $dataResult = $this->engine->fetchData($reportKey, $filters, 1, 10000);
        $rows = $dataResult['rows'];

        $filename = strtolower(str_replace(' ', '_', $metadata['title'])) . '_' . date('Ymd_His');

        switch ($format) {
            case 'excel':
                header('Content-Type: application/vnd.ms-excel');
                header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
                
                // Output as structured HTML Table that Excel loads natively
                echo '<html><head><meta charset="utf-8"></head><body>';
                echo '<h2>' . htmlspecialchars($metadata['title']) . '</h2>';
                echo '<table border="1">';
                echo '<tr style="background:#e0e0e0; font-weight:bold;">';
                foreach ($metadata['columns'] as $c) {
                    echo '<th>' . htmlspecialchars($c['label']) . '</th>';
                }
                echo '</tr>';
                foreach ($rows as $r) {
                    echo '<tr>';
                    foreach ($metadata['columns'] as $colKey => $c) {
                        $val = $r->$colKey ?? '';
                        if ($c['type'] === 'currency') {
                            $val = 'Rs. ' . number_format((float)$val, 2);
                        }
                        echo '<td>' . htmlspecialchars($val) . '</td>';
                    }
                    echo '</tr>';
                }
                echo '</table></body></html>';
                break;

            case 'word':
                header('Content-Type: application/msword');
                header('Content-Disposition: attachment; filename="' . $filename . '.doc"');
                
                echo '<html><head><meta charset="utf-8"></head><body>';
                echo '<h2>' . htmlspecialchars($metadata['title']) . '</h2>';
                echo '<table border="1" cellpadding="5">';
                echo '<tr style="background:#0066cc; color:#ffffff; font-weight:bold;">';
                foreach ($metadata['columns'] as $c) {
                    echo '<th>' . htmlspecialchars($c['label']) . '</th>';
                }
                echo '</tr>';
                foreach ($rows as $r) {
                    echo '<tr>';
                    foreach ($metadata['columns'] as $colKey => $c) {
                        echo '<td>' . htmlspecialchars($r->$colKey ?? '') . '</td>';
                    }
                    echo '</tr>';
                }
                echo '</table></body></html>';
                break;

            case 'json':
                header('Content-Type: application/json');
                header('Content-Disposition: attachment; filename="' . $filename . '.json"');
                echo json_encode($rows, JSON_PRETTY_PRINT);
                break;

            case 'xml':
                header('Content-Type: text/xml');
                header('Content-Disposition: attachment; filename="' . $filename . '.xml"');
                echo '<?xml version="1.0" encoding="UTF-8"?>';
                echo '<report>';
                echo '<title>' . htmlspecialchars($metadata['title']) . '</title>';
                echo '<data>';
                foreach ($rows as $r) {
                    echo '<row>';
                    foreach ($metadata['columns'] as $colKey => $c) {
                        echo '<' . $colKey . '>' . htmlspecialchars($r->$colKey ?? '') . '</' . $colKey . '>';
                    }
                    echo '</row>';
                }
                echo '</data>';
                echo '</report>';
                break;

            case 'csv':
            default:
                header('Content-Type: text/csv');
                header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
                $out = fopen('php://output', 'w');

                // Output header columns
                $headers = [];
                foreach ($metadata['columns'] as $c) {
                    $headers[] = $c['label'];
                }
                fputcsv($out, $headers);

                // Output data rows
                foreach ($rows as $r) {
                    $vals = [];
                    foreach ($metadata['columns'] as $colKey => $c) {
                        $vals[] = $r->$colKey ?? '';
                    }
                    fputcsv($out, $vals);
                }
                fclose($out);
                break;
        }
        exit;
    }

    /**
     * Dedicated accounting-grade high-fidelity print output
     */
    public function print_report($reportKey = null) {
        if (!$reportKey) {
            die("Report key is required.");
        }

        $registry = ReportEngine::getReportsRegistry();
        if (!isset($registry[$reportKey])) {
            die("Report not registered.");
        }

        $metadata = $registry[$reportKey];

        // Extract filters
        $filters = [
            'start_date' => $_GET['start_date'] ?? null,
            'end_date' => $_GET['end_date'] ?? null,
            'customer' => $_GET['customer'] ?? null,
            'supplier' => $_GET['supplier'] ?? null,
            'product' => $_GET['product'] ?? null,
            'warehouse' => $_GET['warehouse'] ?? null,
            'route' => $_GET['route'] ?? null,
            'category' => $_GET['category'] ?? null,
            'rep' => $_GET['rep'] ?? null,
            'payment_method' => $_GET['payment_method'] ?? null,
            'status' => $_GET['status'] ?? null,
            'brand' => $_GET['brand'] ?? null,
            'group' => $_GET['group'] ?? null,
            'vehicle' => $_GET['vehicle'] ?? null,
            'driver' => $_GET['driver'] ?? null,
            'partner' => $_GET['partner'] ?? null,
            'territory' => $_GET['territory'] ?? null,
        ];

        // Fetch data
        $dataResult = $this->engine->fetchData($reportKey, $filters, 1, 10000);
        $rows = $dataResult['rows'];
        $grandTotals = $dataResult['grand_totals'];

        // Get company settings
        $db = new Database();
        $db->query("SELECT * FROM company_settings LIMIT 1");
        $company = $db->single() ?: (object)[
            'company_name' => 'Falcon Stationary (Pvt) Ltd',
            'phone' => '', 'email' => '', 'address' => ''
        ];

        // Resolve active filter values to descriptive labels
        $filterLabels = [];
        if (!empty($filters['start_date'])) {
            $filterLabels['From Date'] = date('d/m/Y', strtotime($filters['start_date']));
        } else {
            $filterLabels['From Date'] = date('01/m/Y');
        }

        if (!empty($filters['end_date'])) {
            $filterLabels['To Date'] = date('d/m/Y', strtotime($filters['end_date']));
        } else {
            $filterLabels['To Date'] = date('d/m/Y');
        }

        if (!empty($filters['customer'])) {
            $db->query("SELECT name FROM customers WHERE id = :id");
            $db->bind(':id', $filters['customer']);
            $row = $db->single();
            if ($row) $filterLabels['Customer'] = $row->name;
        }

        if (!empty($filters['supplier'])) {
            $db->query("SELECT name FROM vendors WHERE id = :id");
            $db->bind(':id', $filters['supplier']);
            $row = $db->single();
            if ($row) $filterLabels['Supplier'] = $row->name;
        }

        if (!empty($filters['product'])) {
            $db->query("SELECT name, item_code FROM items WHERE id = :id");
            $db->bind(':id', $filters['product']);
            $row = $db->single();
            if ($row) $filterLabels['Product'] = $row->item_code . ' - ' . $row->name;
        }

        if (!empty($filters['warehouse'])) {
            $db->query("SELECT name FROM warehouses WHERE id = :id");
            $db->bind(':id', $filters['warehouse']);
            $row = $db->single();
            if ($row) $filterLabels['Warehouse'] = $row->name;
        }

        if (!empty($filters['category'])) {
            $db->query("SELECT name FROM item_categories WHERE id = :id");
            $db->bind(':id', $filters['category']);
            $row = $db->single();
            if ($row) $filterLabels['Category'] = $row->name;
        }

        if (!empty($filters['route'])) {
            $db->query("SELECT route_name FROM rep_daily_routes WHERE id = :id");
            $db->bind(':id', $filters['route']);
            $row = $db->single();
            if ($row) $filterLabels['Route'] = $row->route_name;
        }

        if (!empty($filters['rep'])) {
            $db->query("SELECT username FROM users WHERE id = :id");
            $db->bind(':id', $filters['rep']);
            $row = $db->single();
            if ($row) $filterLabels['Sales Rep'] = $row->username;
        }

        if (!empty($filters['payment_method'])) {
            $filterLabels['Payment Method'] = $filters['payment_method'];
        }

        if (!empty($filters['status'])) {
            $filterLabels['Status'] = $filters['status'];
        }

        if (!empty($filters['brand'])) {
            $filterLabels['Brand'] = $filters['brand'];
        }

        if (!empty($filters['group'])) {
            $filterLabels['Customer/Supplier Group'] = $filters['group'];
        }

        if (!empty($filters['vehicle'])) {
            $filterLabels['Vehicle'] = $filters['vehicle'];
        }

        if (!empty($filters['driver'])) {
            $db->query("SELECT CONCAT(first_name, ' ', last_name) as name FROM employees WHERE id = :id");
            $db->bind(':id', $filters['driver']);
            $row = $db->single();
            if ($row) $filterLabels['Driver'] = $row->name;
        }

        if (!empty($filters['partner'])) {
            $filterLabels['Partner'] = $filters['partner'];
        }

        if (!empty($filters['territory'])) {
            $filterLabels['Territory'] = $filters['territory'];
        }

        $data = [
            'reportKey' => $reportKey,
            'metadata' => $metadata,
            'rows' => $rows,
            'grandTotals' => $grandTotals,
            'company' => $company,
            'filters' => $filters,
            'filterLabels' => $filterLabels
        ];

        // Load the dedicated printing template
        $this->view('reports/print', $data);
    }

    public function trial_balance() {
        $reportModel = $this->model('Report');
        $companyModel = $this->model('Company');
        $endDate = $_GET['end_date'] ?? date('Y-m-d');
        $accounts = $reportModel->getTrialBalanceData($endDate);
        $company = $companyModel->getSettings();
        
        $tbData = []; $totalDebit = 0; $totalCredit = 0;
        foreach($accounts as $acc) {
            $debit = 0; $credit = 0;
            if (in_array($acc->account_type, ['Asset', 'Expense'])) {
                if ($acc->balance >= 0) { $debit = $acc->balance; } else { $credit = abs($acc->balance); }
            } else {
                if ($acc->balance >= 0) { $credit = $acc->balance; } else { $debit = abs($acc->balance); }
            }
            $tbData[] = ['code' => $acc->account_code, 'name' => $acc->account_name, 'type' => $acc->account_type, 'debit' => $debit, 'credit' => $credit];
            $totalDebit += $debit; $totalCredit += $credit;
        }

        $data = [
            'title' => 'Trial Balance',
            'company' => $company,
            'tb_data' => $tbData,
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'end_date' => $endDate,
            'dated' => true,
            'filter_action' => 'trial_balance'
        ];
        $this->view('reports/trial_balance', $data);
    }

    public function profit_loss() {
        $reportModel = $this->model('Report');
        $companyModel = $this->model('Company');
        $startDate = $_GET['start_date'] ?? date('Y-01-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-d');
        $accounts = $reportModel->getAccountsByTypes(['Revenue', 'Expense'], $endDate, $startDate);
        $company = $companyModel->getSettings();

        $revenues = []; $expenses = []; $totalRevenue = 0; $totalExpense = 0;
        foreach($accounts as $acc) {
            if ($acc->account_type == 'Revenue') { $revenues[] = $acc; $totalRevenue += $acc->balance; } 
            elseif ($acc->account_type == 'Expense') { $expenses[] = $acc; $totalExpense += $acc->balance; }
        }
        $netIncome = $totalRevenue - $totalExpense;

        $data = [
            'title' => 'Profit & Loss',
            'company' => $company,
            'revenues' => $revenues,
            'expenses' => $expenses,
            'total_revenue' => $totalRevenue,
            'total_expense' => $totalExpense,
            'net_income' => $netIncome,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'dated' => true,
            'filter_action' => 'profit_loss'
        ];
        $this->view('reports/profit_loss', $data);
    }

    public function balance_sheet() {
        $reportModel = $this->model('Report');
        $companyModel = $this->model('Company');
        $endDate = $_GET['end_date'] ?? date('Y-m-d');
        $plAccounts = $reportModel->getAccountsByTypes(['Revenue', 'Expense'], $endDate);
        $netIncome = 0;
        foreach($plAccounts as $acc) {
            if ($acc->account_type == 'Revenue') { $netIncome += $acc->balance; } else { $netIncome -= $acc->balance; }
        }

        $bsAccounts = $reportModel->getAccountsByTypes(['Asset', 'Liability', 'Equity'], $endDate);
        $company = $companyModel->getSettings();

        $assets = []; $liabilities = []; $equities = [];
        $totalAssets = 0; $totalLiabilities = 0; $totalEquity = 0;

        foreach($bsAccounts as $acc) {
            if ($acc->account_type == 'Asset') { $assets[] = $acc; $totalAssets += $acc->balance; } 
            elseif ($acc->account_type == 'Liability') { $liabilities[] = $acc; $totalLiabilities += $acc->balance; } 
            elseif ($acc->account_type == 'Equity') { $equities[] = $acc; $totalEquity += $acc->balance; }
        }

        $totalEquity += $netIncome;
        $totalLiabilitiesAndEquity = $totalLiabilities + $totalEquity;

        $data = [
            'title' => 'Balance Sheet', 'company' => $company, 'assets' => $assets, 'liabilities' => $liabilities, 'equities' => $equities,
            'total_assets' => $totalAssets, 'total_liabilities' => $totalLiabilities, 'total_equity_before_ni' => ($totalEquity - $netIncome),
            'net_income' => $netIncome, 'total_equity' => $totalEquity, 'total_liabilities_equity' => $totalLiabilitiesAndEquity,
            'end_date' => $endDate, 'dated' => true, 'filter_action' => 'balance_sheet'
        ];
        $this->view('reports/balance_sheet', $data);
    }

    public function cash_flow() {
        $reportModel = $this->model('Report');
        $companyModel = $this->model('Company');
        $endDate = $_GET['end_date'] ?? date('Y-m-d');
        $accounts = $reportModel->getAccountsByTypes(['Asset', 'Liability', 'Equity', 'Revenue', 'Expense'], $endDate);
        $company = $companyModel->getSettings();

        $netIncome = 0; $operating = []; $investing = []; $financing = []; $cashBalance = 0;

        foreach($accounts as $acc) {
            $nameStr = strtolower($acc->account_name);
            if ($acc->account_type == 'Revenue') { $netIncome += $acc->balance; }
            elseif ($acc->account_type == 'Expense') { $netIncome -= $acc->balance; }
            
            if ($acc->account_type == 'Asset') {
                if (strpos($nameStr, 'cash') !== false || strpos($nameStr, 'bank') !== false) {
                    $cashBalance += $acc->balance; 
                } elseif (strpos($nameStr, 'equipment') !== false || strpos($nameStr, 'asset') !== false) {
                    $investing[] = ['name' => 'Purchase of ' . $acc->account_name, 'amount' => -$acc->balance]; 
                } else {
                    $operating[] = ['name' => 'Change in ' . $acc->account_name, 'amount' => -$acc->balance]; 
                }
            } elseif ($acc->account_type == 'Liability') {
                $operating[] = ['name' => 'Change in ' . $acc->account_name, 'amount' => $acc->balance]; 
            } elseif ($acc->account_type == 'Equity') {
                if (strpos($nameStr, 'retained') === false) { 
                    $financing[] = ['name' => 'Change in ' . $acc->account_name, 'amount' => $acc->balance];
                }
            }
        }

        $data = [
            'title' => 'Statement of Cash Flows',
            'company' => $company,
            'net_income' => $netIncome,
            'operating' => $operating,
            'investing' => $investing,
            'financing' => $financing,
            'ending_cash' => $cashBalance,
            'end_date' => $endDate,
            'dated' => true,
            'filter_action' => 'cash_flow'
        ];
        $this->view('reports/cash_flow', $data);
    }

    public function ar_aging() {
        $reportModel = $this->model('Report');
        $companyModel = $this->model('Company');
        $invoices = $reportModel->getARAging();
        $company = $companyModel->getSettings();

        $agingData = [];
        $totals = ['current' => 0, 'thirty' => 0, 'sixty' => 0, 'ninety' => 0, 'older' => 0, 'total' => 0];

        foreach ($invoices as $inv) {
            $cust = $inv->customer_name;
            if (!isset($agingData[$cust])) {
                $agingData[$cust] = ['current' => 0, 'thirty' => 0, 'sixty' => 0, 'ninety' => 0, 'older' => 0, 'total' => 0];
            }

            $amount = $inv->total_amount;
            $days = $inv->days_overdue;

            if ($days <= 0) { $agingData[$cust]['current'] += $amount; $totals['current'] += $amount; }
            elseif ($days <= 30) { $agingData[$cust]['thirty'] += $amount; $totals['thirty'] += $amount; }
            elseif ($days <= 60) { $agingData[$cust]['sixty'] += $amount; $totals['sixty'] += $amount; }
            elseif ($days <= 90) { $agingData[$cust]['ninety'] += $amount; $totals['ninety'] += $amount; }
            else { $agingData[$cust]['older'] += $amount; $totals['older'] += $amount; }

            $agingData[$cust]['total'] += $amount;
            $totals['total'] += $amount;
        }

        $data = ['title' => 'A/R Aging Summary', 'company' => $company, 'aging_data' => $agingData, 'totals' => $totals];
        $this->view('reports/ar_aging', $data);
    }

    public function fifo_profit() {
        $reportModel = $this->model('Report');
        $companyModel = $this->model('Company');
        $invoices = $reportModel->getFIFOSalesData();
        $company = $companyModel->getSettings();

        $data = [
            'title' => 'FIFO Profit & Margin Report',
            'company' => $company,
            'invoices' => $invoices
        ];
        $this->view('reports/fifo_profit', $data);
    }
}
