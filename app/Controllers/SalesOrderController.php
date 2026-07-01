<?php

class SalesOrderController extends Controller {
    private $customerModel;
    private $itemModel;
    private $companyModel;
    private $db;

    public function __construct() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . APP_URL . '/auth/login');
            exit;
        }

        $this->customerModel = $this->model('Customer');
        $this->itemModel = $this->model('Item');
        $this->companyModel = $this->model('Company');
        $this->db = new Database();
        
        $this->ensureSalesOrderTablesExist();
    }

    /**
     * Self-healing migration to support Sales Order tables
     */
    private function ensureSalesOrderTablesExist() {
        // Handled centrally by MigrationManager
    }

    /**
     * Display all sales orders (Standard & Route) with search, filtering, and pagination
     */
    public function index() {
        $limit = 10;
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        if ($page < 1) $page = 1;
        $offset = ($page - 1) * $limit;

        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $startDate = isset($_GET['start_date']) ? trim($_GET['start_date']) : '';
        $endDate = isset($_GET['end_date']) ? trim($_GET['end_date']) : '';
        $customerId = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : 0;
        $status = isset($_GET['status']) ? trim($_GET['status']) : '';
        $sourceType = isset($_GET['source_type']) ? trim($_GET['source_type']) : '';
        $repName = isset($_GET['rep_name']) ? trim($_GET['rep_name']) : '';

        // Build the combined union query
        $queryStr = "SELECT * FROM (
            SELECT 
                so.id, 
                so.order_number as document_number, 
                so.customer_id, 
                so.customer_name, 
                so.customer_phone, 
                so.subtotal, 
                so.discount, 
                so.grand_total, 
                so.status, 
                so.order_date as document_date, 
                so.due_date, 
                so.rep_name, 
                so.mca, 
                so.notes,
                'standard' as source_type,
                NULL as route_name
            FROM sales_orders so

            UNION ALL

            SELECT 
                i.id, 
                i.invoice_number as document_number, 
                i.customer_id, 
                c.name as customer_name, 
                c.phone as customer_phone, 
                i.total_amount as subtotal, 
                COALESCE(CASE WHEN i.global_discount_type = '%' THEN (i.total_amount * i.global_discount_val / 100) ELSE i.global_discount_val END, 0) as discount,
                (i.total_amount - COALESCE(CASE WHEN i.global_discount_type = '%' THEN (i.total_amount * i.global_discount_val / 100) ELSE i.global_discount_val END, 0) + COALESCE(i.tax_amount, 0)) as grand_total,
                i.status, 
                i.invoice_date as document_date, 
                i.due_date, 
                (SELECT CONCAT(e.first_name, ' ', e.last_name) FROM employees e JOIN users u ON u.employee_id = e.id JOIN rep_daily_routes r ON r.user_id = u.id WHERE r.id = i.rep_route_id LIMIT 1) as rep_name,
                (SELECT name FROM mca_areas WHERE id = c.mca_id LIMIT 1) as mca,
                i.notes,
                'route' as source_type,
                (SELECT route_name FROM rep_daily_routes WHERE id = i.rep_route_id LIMIT 1) as route_name
            FROM invoices i
            JOIN customers c ON i.customer_id = c.id
            WHERE i.stock_status = 'reserved'
        ) as combined WHERE 1=1";

        $params = [];

        if (!empty($search)) {
            $queryStr .= " AND (document_number LIKE :search OR customer_name LIKE :search OR notes LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }

        if (!empty($startDate)) {
            $queryStr .= " AND document_date >= :start_date";
            $params[':start_date'] = $startDate;
        }

        if (!empty($endDate)) {
            $queryStr .= " AND document_date <= :end_date";
            $params[':end_date'] = $endDate;
        }

        if ($customerId > 0) {
            $queryStr .= " AND customer_id = :customer_id";
            $params[':customer_id'] = $customerId;
        }

        if (!empty($status)) {
            $queryStr .= " AND status = :status";
            $params[':status'] = $status;
        }

        if (!empty($sourceType)) {
            $queryStr .= " AND source_type = :source_type";
            $params[':source_type'] = $sourceType;
        }

        if (!empty($repName)) {
            $queryStr .= " AND rep_name = :rep_name";
            $params[':rep_name'] = $repName;
        }

        // Count total records
        $countQuery = "SELECT COUNT(*) as total FROM (" . $queryStr . ") as count_table";
        $this->db->query($countQuery);
        foreach ($params as $key => $val) {
            $this->db->bind($key, $val);
        }
        $countRow = $this->db->single();
        $totalRecords = $countRow ? intval($countRow->total) : 0;
        $totalPages = ceil($totalRecords / $limit);
        if ($totalPages < 1) $totalPages = 1;
        if ($page > $totalPages) $page = $totalPages;

        // Fetch paginated results
        $queryStr .= " ORDER BY document_date DESC, id DESC LIMIT :limit OFFSET :offset";
        $this->db->query($queryStr);
        foreach ($params as $key => $val) {
            $this->db->bind($key, $val);
        }
        $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        $this->db->bind(':offset', $offset, PDO::PARAM_INT);
        $orders = $this->db->resultSet() ?: [];

        // Fetch customers for filter dropdown
        $this->db->query("SELECT id, name FROM customers ORDER BY name ASC");
        $customers = $this->db->resultSet() ?: [];

        // Fetch active sales reps for filter dropdown
        $this->db->query("SELECT CONCAT(first_name, ' ', last_name) as name FROM employees WHERE status = 'Active' ORDER BY first_name ASC, last_name ASC");
        $salesReps = $this->db->resultSet() ?: [];

        $data = [
            'title' => 'Sales Order Center',
            'orders' => $orders,
            'customers' => $customers,
            'sales_reps' => $salesReps,
            'search' => $search,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'customer_id' => $customerId,
            'status' => $status,
            'source_type' => $sourceType,
            'rep_name' => $repName,
            'page' => $page,
            'total_pages' => $totalPages,
            'total_records' => $totalRecords,
            'content_view' => 'sales_orders/list'
        ];

        $this->view('layouts/main', $data);
    }

    /**
     * Render the Sales Order creation view
     */
    public function create() {
        $repRouteId = !empty($_GET['rep_route_id']) ? intval($_GET['rep_route_id']) : null;
        $backUrl = !empty($_GET['back_url']) ? $_GET['back_url'] : '';
        
        $url = APP_URL . '/sales/create?type=sales_order';
        if ($repRouteId) {
            $url .= '&rep_route_id=' . $repRouteId;
        }
        if (!empty($backUrl)) {
            $url .= '&back_url=' . urlencode($backUrl);
        }
        header('Location: ' . $url);
        exit;
    }

    /**
     * Store Sales Order (without inventory depletion or ledger posts)
     */
    public function store() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS);

            $orderNumber = trim($_POST['order_number'] ?? '');
            $customerId = intval($_POST['customer_id'] ?? 0);
            
            $this->db->query("SELECT name, phone FROM customers WHERE id = :id");
            $this->db->bind(':id', $customerId);
            $custRow = $this->db->single();
            $customerName = $custRow ? $custRow->name : 'Walk-In Customer';
            $customerPhone = $custRow ? $custRow->phone : '';

            $itemSelections = $_POST['item_selection'] ?? [];
            $qtys = $_POST['qty'] ?? [];
            $descs = $_POST['desc'] ?? [];
            $prices = $_POST['price'] ?? [];
            $discVals = $_POST['item_discount_val'] ?? [];
            $discTypes = $_POST['item_discount_type'] ?? [];

            if (empty($itemSelections)) {
                $_SESSION['flash_error'] = "Sales Order creation failed: Please select at least one item.";
                header('Location: ' . APP_URL . '/salesorder/create');
                exit;
            }

            try {
                $this->db->beginTransaction();

                $subtotal = 0.00;
                $itemsPayload = [];

                foreach ($itemSelections as $index => $compositeId) {
                    $qty = floatval($qtys[$index] ?? 1);
                    $price = floatval($prices[$index] ?? 0.00);
                    $discVal = floatval($discVals[$index] ?? 0.00);
                    $discType = $discTypes[$index] ?? 'Rs';

                    $parts = explode('|', $compositeId);
                    $itemId = intval($parts[0] ?? 0);
                    $varId = isset($parts[1]) && $parts[1] !== 'MIX' && $parts[1] !== '0' ? intval($parts[1]) : null;

                    $lineGross = $qty * $price;
                    $lineDisc = ($discType === '%') ? ($lineGross * $discVal / 100) : $discVal;
                    $lineTotal = max(0.00, $lineGross - $lineDisc);

                    $subtotal += $lineTotal;

                    // Get SKU
                    $sku = 'ITEM';
                    $this->db->query("SELECT item_code FROM items WHERE id = :id");
                    $this->db->bind(':id', $itemId);
                    $itemRow = $this->db->single();
                    if ($itemRow) {
                        $sku = $itemRow->item_code;
                    }

                    $itemsPayload[] = [
                        'item_id' => $itemId,
                        'variation_option_id' => $varId,
                        'sku' => $sku,
                        'name' => $descs[$index] ?? 'Product',
                        'billing_price' => $price,
                        'qty' => $qty,
                        'discount_value' => $discVal,
                        'discount_type' => $discType,
                        'total' => $lineTotal
                    ];
                }

                $globalDiscountVal = floatval($_POST['global_discount_val'] ?? 0.00);
                $globalDiscountType = $_POST['global_discount_type'] ?? 'Rs';
                $globalDiscount = ($globalDiscountType === '%') ? ($subtotal * $globalDiscountVal / 100) : $globalDiscountVal;
                $grandTotal = max(0.00, $subtotal - $globalDiscount);

                // Insert Sales Order (No Stock depletion, No ledger entries)
                $this->db->query("INSERT INTO sales_orders (order_number, customer_id, customer_name, customer_phone, billing_type, subtotal, discount, grand_total, notes, rep_name, mca, rep_tp, po_number, order_date, due_date, payment_term_id, status) 
                                  VALUES (:order_num, :cust_id, :cust_name, :cust_phone, 'wholesale', :sub, :disc, :grand, :notes, :rep, :mca, :rep_tp, :po, :o_date, :d_date, :term_id, 'Pending')");
                $this->db->bind(':order_num', $orderNumber);
                $this->db->bind(':cust_id', $customerId);
                $this->db->bind(':cust_name', $customerName);
                $this->db->bind(':cust_phone', $customerPhone);
                $this->db->bind(':sub', $subtotal);
                $this->db->bind(':disc', $globalDiscount);
                $this->db->bind(':grand', $grandTotal);
                $this->db->bind(':notes', $_POST['notes'] ?? '');
                $this->db->bind(':rep', $_POST['rep_name'] ?? '');
                $this->db->bind(':mca', $_POST['mca'] ?? '');
                $this->db->bind(':rep_tp', $_POST['rep_tp'] ?? '');
                $this->db->bind(':po', $_POST['po_number'] ?? '');
                $this->db->bind(':o_date', $_POST['invoice_date'] ?? date('Y-m-d'));
                $this->db->bind(':d_date', $_POST['due_date'] ?? date('Y-m-d'));
                $this->db->bind(':term_id', !empty($_POST['payment_term_id']) ? intval($_POST['payment_term_id']) : null);
                $this->db->execute();
                
                $orderId = $this->db->lastInsertId();

                // Insert sales order items
                foreach ($itemsPayload as $line) {
                    $this->db->query("INSERT INTO sales_order_items (sales_order_id, item_id, variation_option_id, sku, name, billing_price, qty, discount_value, discount_type, total) 
                                      VALUES (:so_id, :item_id, :var_id, :sku, :name, :price, :qty, :disc_val, :disc_type, :total)");
                    $this->db->bind(':so_id', $orderId);
                    $this->db->bind(':item_id', $line['item_id']);
                    $this->db->bind(':var_id', $line['variation_option_id']);
                    $this->db->bind(':sku', $line['sku']);
                    $this->db->bind(':name', $line['name']);
                    $this->db->bind(':price', $line['billing_price']);
                    $this->db->bind(':qty', $line['qty']);
                    $this->db->bind(':disc_val', $line['discount_value']);
                    $this->db->bind(':disc_type', $line['discount_type']);
                    $this->db->bind(':total', $line['total']);
                    $this->db->execute();
                }

                $this->db->commit();
                $newValues = [
                    'order' => [
                        'order_number' => $orderNumber,
                        'customer_id' => $customerId,
                        'customer_name' => $customerName,
                        'customer_phone' => $customerPhone,
                        'subtotal' => $subtotal,
                        'discount' => $globalDiscount,
                        'grand_total' => $grandTotal,
                        'notes' => $_POST['notes'] ?? '',
                        'rep_name' => $_POST['rep_name'] ?? '',
                        'mca' => $_POST['mca'] ?? '',
                        'rep_tp' => $_POST['rep_tp'] ?? '',
                        'po_number' => $_POST['po_number'] ?? '',
                        'order_date' => $_POST['invoice_date'] ?? date('Y-m-d'),
                        'due_date' => $_POST['due_date'] ?? date('Y-m-d'),
                        'payment_term_id' => !empty($_POST['payment_term_id']) ? intval($_POST['payment_term_id']) : null
                    ],
                    'items' => $itemsPayload
                ];
                $this->logActivity('Create Sales Order', 'Sales Order', "Saved Sales Order {$orderNumber} for Customer ID {$customerId} totaling Rs: " . number_format($grandTotal, 2), $orderId, null, $newValues);
                $_SESSION['flash_success'] = "Sales Order {$orderNumber} successfully saved (Inventory unchanged)!";
                header('Location: ' . APP_URL . '/salesorder/show/' . $orderId);
                exit;

            } catch (Exception $e) {
                $this->db->rollBack();
                $_SESSION['flash_error'] = "Sales Order Saving Error: " . $e->getMessage();
                header('Location: ' . APP_URL . '/salesorder/create');
                exit;
            }
        }
    }

    /**
     * Show / Print a Sales Order
     */
    public function show($id = null) {
        if (!$id) {
            header('Location: ' . APP_URL . '/salesorder/create');
            exit;
        }

        $this->db->query("SELECT * FROM sales_orders WHERE id = :id");
        $this->db->bind(':id', $id);
        $order = $this->db->single();
        if (!$order) {
            die("Sales Order not found.");
        }

        $this->db->query("SELECT * FROM sales_order_items WHERE sales_order_id = :id");
        $this->db->bind(':id', $id);
        $items = $this->db->resultSet() ?: [];

        $data = [
            'title' => 'Sales Order ' . $order->order_number,
            'order' => $order,
            'items' => $items,
            'company' => $this->companyModel->getSettings()
        ];

        $this->view('sales_orders/show', $data);
    }

    /**
     * Delete a Standard Sales Order with administrative re-authentication
     */
    public function delete($id = null) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . APP_URL . '/salesorder');
            exit;
        }

        if (!$id) {
            $_SESSION['flash_error'] = "Record ID is missing!";
            header('Location: ' . APP_URL . '/salesorder');
            exit;
        }

        $password = $_POST['password'] ?? '';
        $reason = trim($_POST['delete_reason'] ?? '');

        if (empty($reason)) {
            $_SESSION['flash_error'] = "Deletion reason is required!";
            header('Location: ' . APP_URL . '/salesorder');
            exit;
        }

        // Authenticate password
        $userModel = $this->model('User');
        $username = $_SESSION['username'] ?? '';
        $user = $userModel->login($username, $password);

        if (!$user) {
            $_SESSION['flash_error'] = "Authentication failed: Incorrect password!";
            header('Location: ' . APP_URL . '/salesorder');
            exit;
        }

        // Verify Delete Permission
        if (isset($_SESSION['role']) && strtolower($_SESSION['role']) !== 'admin') {
            $perms = $_SESSION['permissions'] ?? [];
            if (!($perms['sales']['can_delete'] ?? false)) {
                $_SESSION['flash_error'] = "Access denied: You do not have permission to delete sales orders.";
                header('Location: ' . APP_URL . '/salesorder');
                exit;
            }
        }

        // Fetch Sales Order details
        $this->db->query("SELECT * FROM sales_orders WHERE id = :id");
        $this->db->bind(':id', $id);
        $so = $this->db->single();

        if (!$so) {
            $_SESSION['flash_error'] = "Sales Order not found!";
            header('Location: ' . APP_URL . '/salesorder');
            exit;
        }

        $this->db->query("SELECT * FROM sales_order_items WHERE sales_order_id = :id");
        $this->db->bind(':id', $id);
        $soItems = $this->db->resultSet() ?: [];

        $oldValues = [
            'sales_order' => $so,
            'items' => $soItems
        ];

        // Start transaction
        $this->db->beginTransaction();
        try {
            // Write to deleted_invoices audit table
            $this->db->query("INSERT INTO deleted_invoices (invoice_number, customer_name, total_amount, deleted_user_name, delete_reason, record_type) 
                              VALUES (:inv_num, :cust_name, :total, :deleted_user, :reason, 'Sales Order')");
            $this->db->bind(':inv_num', $so->order_number);
            $this->db->bind(':cust_name', $so->customer_name);
            $this->db->bind(':total', $so->grand_total);
            $this->db->bind(':deleted_user', $_SESSION['username'] ?? 'System');
            $this->db->bind(':reason', $reason);
            $this->db->execute();

            // Delete items
            $this->db->query("DELETE FROM sales_order_items WHERE sales_order_id = :id");
            $this->db->bind(':id', $id);
            $this->db->execute();

            // Delete sales order
            $this->db->query("DELETE FROM sales_orders WHERE id = :id");
            $this->db->bind(':id', $id);
            $this->db->execute();

            $this->db->commit();

            // Log general system activity
            $this->logActivity('Delete Sales Order', 'Sales Order', "Deleted Sales Order {$so->order_number} for customer {$so->customer_name} totaling Rs: " . number_format($so->grand_total, 2) . ". Reason: {$reason}", $id, $oldValues, null);

            $_SESSION['flash_success'] = "Sales Order {$so->order_number} deleted and logged to audit trail successfully!";
        } catch (Exception $e) {
            $this->db->rollBack();
            $_SESSION['flash_error'] = "Failed to delete sales order: " . $e->getMessage();
        }

        $getParams = $_GET;
        unset($getParams['url']);
        $queryString = http_build_query($getParams);
        $redirectUrl = APP_URL . '/salesorder';
        if (!empty($queryString)) {
            $redirectUrl .= '?' . $queryString;
        }
        header('Location: ' . $redirectUrl);
        exit;
    }

    /**
     * Process bulk action (edit dates, change reps, or delete) on multiple sales orders/route bookings.
     */
    public function bulk_action() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . APP_URL . '/salesorder');
            exit;
        }

        $ids = $_POST['ids'] ?? [];
        $action = $_POST['bulk_action'] ?? '';
        $newDate = $_POST['bulk_date'] ?? '';
        $newRep = $_POST['bulk_rep'] ?? '';
        $adminPassword = $_POST['admin_password'] ?? '';

        if (empty($ids)) {
            $_SESSION['flash_error'] = "No records selected.";
            header('Location: ' . APP_URL . '/salesorder');
            exit;
        }

        // Authenticate admin password if action is delete
        if ($action === 'delete') {
            if (empty($adminPassword)) {
                $_SESSION['flash_error'] = "Admin password is required for bulk deletion.";
                header('Location: ' . APP_URL . '/salesorder');
                exit;
            }
            $userModel = $this->model('User');
            $username = $_SESSION['username'] ?? '';
            $user = $userModel->login($username, $adminPassword);

            if (!$user) {
                $_SESSION['flash_error'] = "Authentication failed: Incorrect password!";
                header('Location: ' . APP_URL . '/salesorder');
                exit;
            }

            // Verify Delete Permission
            if (isset($_SESSION['role']) && strtolower($_SESSION['role']) !== 'admin') {
                $perms = $_SESSION['permissions'] ?? [];
                if (!($perms['sales']['can_delete'] ?? false)) {
                    $_SESSION['flash_error'] = "Access denied: You do not have permission to delete.";
                    header('Location: ' . APP_URL . '/salesorder');
                    exit;
                }
            }
        }

        $successCount = 0;
        $failureCount = 0;
        $invoiceModel = $this->model('Invoice');

        if ($action === 'delete') {
            foreach ($ids as $compositeId) {
                $parts = explode(':', $compositeId);
                $type = $parts[0] ?? '';
                $id = isset($parts[1]) ? intval($parts[1]) : 0;

                if ($type === 'standard') {
                    // Standard Sales Order
                    $this->db->query("SELECT * FROM sales_orders WHERE id = :id");
                    $this->db->bind(':id', $id);
                    $so = $this->db->single();
                    if ($so) {
                        $this->db->beginTransaction();
                        try {
                            $this->db->query("INSERT INTO deleted_invoices (invoice_number, customer_name, total_amount, deleted_user_name, delete_reason, record_type) 
                                              VALUES (:inv_num, :cust_name, :total, :deleted_user, :reason, 'Sales Order')");
                            $this->db->bind(':inv_num', $so->order_number);
                            $this->db->bind(':cust_name', $so->customer_name);
                            $this->db->bind(':total', $so->grand_total);
                            $this->db->bind(':deleted_user', $_SESSION['username'] ?? 'System');
                            $this->db->bind(':reason', "Bulk deleted by admin");
                            $this->db->execute();

                            $this->db->query("DELETE FROM sales_order_items WHERE sales_order_id = :id");
                            $this->db->bind(':id', $id);
                            $this->db->execute();

                            $this->db->query("DELETE FROM sales_orders WHERE id = :id");
                            $this->db->bind(':id', $id);
                            $this->db->execute();

                            $this->db->commit();
                            $this->logActivity('Bulk Delete Sales Order', 'Sales Order', "Bulk deleted Sales Order {$so->order_number}", $id);
                            $successCount++;
                        } catch (Exception $e) {
                            $this->db->rollBack();
                            $failureCount++;
                        }
                    } else {
                        $failureCount++;
                    }
                } elseif ($type === 'route') {
                    // Route booking (Invoice record with status reserved)
                    $inv = $invoiceModel->getInvoiceById($id);
                    if ($inv) {
                        $success = $invoiceModel->deleteInvoiceWithAccounting($id, $_SESSION['user_id']);
                        if ($success) {
                            $this->db->query("INSERT INTO deleted_invoices (invoice_number, customer_name, total_amount, deleted_user_name, delete_reason, record_type) 
                                              VALUES (:inv_num, :cust_name, :total, :deleted_user, :reason, 'Invoice')");
                            $this->db->bind(':inv_num', $inv->invoice_number);
                            $this->db->bind(':cust_name', $inv->customer_name);
                            $this->db->bind(':total', $inv->total_amount);
                            $this->db->bind(':deleted_user', $_SESSION['username'] ?? 'System');
                            $this->db->bind(':reason', "Bulk deleted by admin");
                            $this->db->execute();

                            $this->logActivity('Bulk Delete Route Booking', 'Sales Order', "Bulk deleted Route Booking Invoice {$inv->invoice_number}", $id);
                            $successCount++;
                        } else {
                            $failureCount++;
                        }
                    } else {
                        $failureCount++;
                    }
                }
            }
            $_SESSION['flash_success'] = "Successfully bulk deleted {$successCount} records." . ($failureCount > 0 ? " {$failureCount} failed." : "");
        } elseif ($action === 'change_date') {
            if (empty($newDate)) {
                $_SESSION['flash_error'] = "New date is required.";
                header('Location: ' . APP_URL . '/salesorder');
                exit;
            }
            foreach ($ids as $compositeId) {
                $parts = explode(':', $compositeId);
                $type = $parts[0] ?? '';
                $id = isset($parts[1]) ? intval($parts[1]) : 0;

                if ($type === 'standard') {
                    $this->db->query("UPDATE sales_orders SET order_date = :new_date WHERE id = :id");
                    $this->db->bind(':new_date', $newDate);
                    $this->db->bind(':id', $id);
                    $this->db->execute();
                    $successCount++;
                } elseif ($type === 'route') {
                    $inv = $invoiceModel->getInvoiceById($id);
                    if ($inv) {
                        $this->db->query("UPDATE invoices SET invoice_date = :new_date WHERE id = :id");
                        $this->db->bind(':new_date', $newDate);
                        $this->db->bind(':id', $id);
                        $this->db->execute();

                        if ($inv->journal_entry_id) {
                            $this->db->query("UPDATE journal_entries SET entry_date = :new_date WHERE id = :jid");
                            $this->db->bind(':new_date', $newDate);
                            $this->db->bind(':jid', $inv->journal_entry_id);
                            $this->db->execute();
                        }
                        $successCount++;
                    } else {
                        $failureCount++;
                    }
                }
            }
            $_SESSION['flash_success'] = "Successfully updated date for {$successCount} records." . ($failureCount > 0 ? " {$failureCount} failed." : "");
        } elseif ($action === 'change_rep') {
            if (empty($newRep)) {
                $_SESSION['flash_error'] = "New representative is required.";
                header('Location: ' . APP_URL . '/salesorder');
                exit;
            }
            foreach ($ids as $compositeId) {
                $parts = explode(':', $compositeId);
                $type = $parts[0] ?? '';
                $id = isset($parts[1]) ? intval($parts[1]) : 0;

                if ($type === 'standard') {
                    $this->db->query("UPDATE sales_orders SET rep_name = :rep_name WHERE id = :id");
                    $this->db->bind(':rep_name', $newRep);
                    $this->db->bind(':id', $id);
                    $this->db->execute();
                    $successCount++;
                } elseif ($type === 'route') {
                    $inv = $invoiceModel->getInvoiceById($id);
                    if ($inv) {
                        $routeId = $this->getOrCreateRouteForRep($newRep, $inv->invoice_date);
                        if ($routeId) {
                            $this->db->query("UPDATE invoices SET rep_route_id = :route_id WHERE id = :id");
                            $this->db->bind(':route_id', $routeId);
                            $this->db->bind(':id', $id);
                            $this->db->execute();
                            $successCount++;
                        } else {
                            $failureCount++;
                        }
                    } else {
                        $failureCount++;
                    }
                }
            }
            $_SESSION['flash_success'] = "Successfully updated representative for {$successCount} records." . ($failureCount > 0 ? " {$failureCount} failed." : "");
        }

        header('Location: ' . APP_URL . '/salesorder');
        exit;
    }

    /**
     * Get or create daily route for a representative name and date.
     */
    private function getOrCreateRouteForRep($repName, $date) {
        $this->db->query("SELECT u.id as user_id 
                          FROM users u 
                          JOIN employees e ON u.employee_id = e.id 
                          WHERE CONCAT(e.first_name, ' ', e.last_name) = :rep_name LIMIT 1");
        $this->db->bind(':rep_name', $repName);
        $userRow = $this->db->single();
        if (!$userRow) {
            $this->db->query("SELECT id as user_id FROM users WHERE username = :rep_name LIMIT 1");
            $this->db->bind(':rep_name', $repName);
            $userRow = $this->db->single();
        }
        
        if (!$userRow) {
            return null;
        }
        
        $userId = $userRow->user_id;
        $dateStr = date('Y-m-d', strtotime($date));
        
        $this->db->query("SELECT id FROM rep_daily_routes WHERE user_id = :user_id AND DATE(start_time) = :date LIMIT 1");
        $this->db->bind(':user_id', $userId);
        $this->db->bind(':date', $dateStr);
        $routeRow = $this->db->single();
        if ($routeRow) {
            return $routeRow->id;
        }
        
        $routeName = $repName . " Route - " . $dateStr;
        $startTime = $dateStr . " 08:00:00";
        $this->db->query("INSERT INTO rep_daily_routes (user_id, route_name, start_time, status) 
                          VALUES (:user_id, :route_name, :start_time, 'Active')");
        $this->db->bind(':user_id', $userId);
        $this->db->bind(':route_name', $routeName);
        $this->db->bind(':start_time', $startTime);
        $this->db->execute();
        
        return $this->db->lastInsertId();
    }
}
