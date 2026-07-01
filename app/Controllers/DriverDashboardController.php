<?php
class DriverDashboardController extends Controller {
    private $routeModel;

    public function __construct() {
        $url = $_GET['url'] ?? '';
        $isApi = (strpos($url, 'api_sync_') !== false);
        if (!$isApi && !isset($_SESSION['user_id'])) {
            header('Location: ' . APP_URL . '/driver/auth/login');
            exit;
        }
        $this->routeModel = $this->model('DriverRoute');
    }

    public function index() {
        $activeDelivery = $this->routeModel->getAssignedDelivery($_SESSION['user_id']);
        
        $shops = [];
        $employees = [];
        $todayCashCollected = 0.0;
        $routeCreditBills = [];

        if ($activeDelivery) {
            if ($activeDelivery->status === 'In Transit') {
                $shops = $this->routeModel->getDeliveryShops($activeDelivery->rep_route_id);
                
                // Fetch today's cash collections
                $db = new Database();
                $db->query("SELECT COALESCE(SUM(amount), 0) as cash_total 
                            FROM customer_payments 
                            WHERE rep_route_id = :rid AND payment_method = 'Cash'");
                $db->bind(':rid', $activeDelivery->rep_route_id);
                $row = $db->single();
                $todayCashCollected = $row ? floatval($row->cash_total) : 0.0;

                // Heal invoice payment statuses dynamically before fetching
                $db->query("SELECT DISTINCT customer_id FROM invoices WHERE status = 'Unpaid'");
                $custs = $db->resultSet();
                foreach ($custs as $c) {
                    $this->autoApplyPaymentsToInvoices($c->customer_id);
                }

                // Fetch all previous credit bills for the active delivery (or fall back to territory-wide)
                $selectedIds = [];
                if ($activeDelivery && !empty($activeDelivery->selected_credit_invoices)) {
                    $selectedIds = json_decode($activeDelivery->selected_credit_invoices, true);
                }

                if (!empty($selectedIds) && is_array($selectedIds)) {
                    $idList = implode(',', array_map('intval', $selectedIds));
                    $db->query("
                        SELECT i.customer_id, c.name as customer_name, MIN(i.invoice_number) as invoice_number, MIN(i.invoice_date) as invoice_date,
                            SUM(i.total_amount - COALESCE(CASE WHEN i.global_discount_type = '%' THEN (i.total_amount * i.global_discount_val / 100) ELSE i.global_discount_val END, 0) + COALESCE(i.tax_amount, 0)) as true_grand_total
                        FROM invoices i
                        JOIN customers c ON i.customer_id = c.id
                        WHERE i.customer_id IN ($idList) AND i.status = 'Unpaid'
                        GROUP BY i.customer_id, c.name
                        ORDER BY c.name ASC
                    ");
                } else {
                    // Do not show territory-wide fallback outstanding credit bills anymore
                    // Only show checked/ticked ones. If none checked, show empty.
                    $db->query("SELECT 1 FROM invoices WHERE 1=0");
                }
                $routeCreditBills = $db->resultSet();
                foreach ($routeCreditBills as $bill) {
                    $db->query("SELECT COUNT(*) as cnt FROM customer_payments WHERE customer_id = :cid AND rep_route_id = :rid");
                    $db->bind(':cid', $bill->customer_id);
                    $db->bind(':rid', $activeDelivery->rep_route_id);
                    $row = $db->single();
                    $bill->is_completed = ($row && intval($row->cnt) > 0);
                }
            }
            $employees = $this->routeModel->getActiveEmployees();
        }

        $data = [
            'title' => 'Driver Hub',
            'content_view' => 'dashboard',
            'active_delivery' => $activeDelivery,
            'shops' => $shops,
            'employees' => $employees,
            'today_cash_collected' => $todayCashCollected,
            'route_credit_bills' => $routeCreditBills,
            'success' => $_GET['success'] ?? '',
            'error' => $_GET['error'] ?? ''
        ];
        
        $this->view('layout', $data);
    }

    public function accept() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $deliveryId = intval($_POST['delivery_id'] ?? 0);
            if ($deliveryId > 0) {
                $this->routeModel->acceptRoute($deliveryId);
                header('Location: ' . APP_URL . '/driver?success=Delivery route accepted.');
                exit;
            }
        }
        header('Location: ' . APP_URL . '/driver');
    }

    public function start_trip() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $deliveryId = intval($_POST['delivery_id'] ?? 0);
            $startMeter = floatval($_POST['start_meter'] ?? 0);
            $driverName = trim($_POST['driver_name'] ?? '');
            $partnerName = trim($_POST['partner_name'] ?? '');

            if ($deliveryId <= 0 || $startMeter <= 0 || empty($driverName)) {
                header('Location: ' . APP_URL . '/driver?error=Invalid odometer reading or driver selection.');
                exit;
            }

            if ($this->routeModel->startTrip($deliveryId, $startMeter, $driverName, $partnerName)) {
                header('Location: ' . APP_URL . '/driver?success=Trip started successfully! Drive safely.');
                exit;
            }
        }
        header('Location: ' . APP_URL . '/driver');
    }

    public function end_trip() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $deliveryId = intval($_POST['delivery_id'] ?? 0);
            $endMeter = floatval($_POST['end_meter'] ?? 0);

            if ($deliveryId <= 0 || $endMeter <= 0) {
                header('Location: ' . APP_URL . '/driver?error=Invalid ending odometer reading.');
                exit;
            }

            $delivery = $this->routeModel->getDeliveryById($deliveryId);
            if (!$delivery) {
                header('Location: ' . APP_URL . '/driver?error=Delivery route not found.');
                exit;
            }

            if ($endMeter < floatval($delivery->start_meter)) {
                header('Location: ' . APP_URL . '/driver?error=Ending odometer cannot be less than starting odometer (' . $delivery->start_meter . ').');
                exit;
            }

            $denoms = $_POST['denom'] ?? [];
            $cashDenomJson = json_encode($denoms);

            if ($this->routeModel->endTrip($deliveryId, $endMeter, $cashDenomJson)) {
                header('Location: ' . APP_URL . '/driver/trip_summary/' . $deliveryId);
                exit;
            } else {
                header('Location: ' . APP_URL . '/driver?error=Failed to end trip due to database error.');
                exit;
            }
        }
        header('Location: ' . APP_URL . '/driver');
    }

    public function trip_summary($deliveryId = null) {
        if (!$deliveryId) {
            header('Location: ' . APP_URL . '/driver');
            exit;
        }

        $delivery = $this->routeModel->getDeliveryById($deliveryId);
        if (!$delivery) {
            die("Invalid trip summary ID.");
        }

        // Fetch collections for this specific route session
        $db = new Database();
        $db->query("SELECT payment_method, COUNT(*) as tx_count, COALESCE(SUM(amount), 0) as total_collected 
                    FROM customer_payments 
                    WHERE rep_route_id = :rid
                    GROUP BY payment_method");
        $db->bind(':rid', $delivery->rep_route_id);
        $collections = $db->resultSet();

        $data = [
            'title' => 'Trip Completion Summary',
            'content_view' => 'trip_summary',
            'delivery' => $delivery,
            'collections' => $collections
        ];

        $this->view('layout', $data);
    }

    public function vehicle_stock() {
        $activeDelivery = $this->routeModel->getAssignedDelivery($_SESSION['user_id']);
        $stockItems = [];

        if ($activeDelivery) {
            $db = new Database();
            $db->query("
                SELECT 
                    MAX(ii.item_id) as item_id, 
                    MAX(ii.variation_option_id) as variation_option_id,
                    TRIM(ii.description) as item_name,
                    SUM(ii.loaded_quantity) as loaded_qty,
                    SUM(CASE WHEN i.delivery_status = 'Delivered' THEN ii.quantity ELSE 0 END) as delivered_qty,
                    (SUM(ii.loaded_quantity) - SUM(CASE WHEN i.delivery_status = 'Delivered' THEN ii.quantity ELSE 0 END)) as remaining_qty
                FROM invoice_items ii
                JOIN invoices i ON ii.invoice_id = i.id
                WHERE i.rep_route_id = :rid AND i.status != 'Voided'
                GROUP BY TRIM(ii.description)
                ORDER BY TRIM(ii.description) ASC
            ");
            $db->bind(':rid', $activeDelivery->rep_route_id);
            $stockItems = $db->resultSet();
        }

        $data = [
            'title' => 'Vehicle Stock Balance',
            'content_view' => 'vehicle_stock',
            'active_delivery' => $activeDelivery,
            'stock_items' => $stockItems
        ];
        
        $this->view('layout', $data);
    }

    // JSON API for Native Mobile App Pull Synchronization
    public function api_sync_pull() {
        header('Content-Type: application/json');
        
        $userId = intval($_GET['user_id'] ?? 0);
        if ($userId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid User ID.']);
            exit;
        }
        
        $activeDelivery = $this->routeModel->getAssignedDelivery($userId);
        
        $shops = [];
        $invoices = [];
        $invoiceItems = [];
        $employees = [];
        
        $creditInvoices = [];
        if ($activeDelivery) {
            $shops = $this->routeModel->getDeliveryShops($activeDelivery->rep_route_id);
            $employees = $this->routeModel->getActiveEmployees();
            
            $billingModel = $this->model('DriverInvoice');
            foreach ($shops as $shop) {
                $shopInvs = $billingModel->getCustomerInvoices($shop->id, $activeDelivery->rep_route_id);
                foreach ($shopInvs as $inv) {
                    $invoices[] = $inv;
                    $items = $billingModel->getInvoiceItems($inv->id);
                    foreach ($items as $item) {
                        $invoiceItems[] = $item;
                    }
                }
            }
        }

        // Heal invoice payment statuses dynamically before fetching
        $db = new Database();
        $db->query("SELECT DISTINCT customer_id FROM invoices WHERE status = 'Unpaid'");
        $custs = $db->resultSet();
        foreach ($custs as $c) {
            $this->autoApplyPaymentsToInvoices($c->customer_id);
        }

        // Fetch outstanding credit invoices: ONLY selected/ticked ones for the active delivery if specified
        $selectedIds = [];
        if ($activeDelivery && !empty($activeDelivery->selected_credit_invoices)) {
            $selectedIds = json_decode($activeDelivery->selected_credit_invoices, true);
        }

        if ($activeDelivery && !empty($selectedIds) && is_array($selectedIds)) {
            $idList = implode(',', array_map('intval', $selectedIds));
            $db->query("
                SELECT i.id, i.invoice_number, i.customer_id, i.invoice_date, i.status,
                       (i.total_amount - COALESCE(CASE WHEN i.global_discount_type = '%' THEN (i.total_amount * i.global_discount_val / 100) ELSE i.global_discount_val END, 0) + COALESCE(i.tax_amount, 0)) as true_grand_total,
                       c.name as customer_name, c.address as customer_address
                FROM invoices i
                JOIN customers c ON i.customer_id = c.id
                WHERE i.status = 'Unpaid' AND i.customer_id IN ($idList)
                ORDER BY i.invoice_date ASC
            ");
        } else {
            // Default fallback if no specific invoices were selected: return empty
            $db->query("SELECT 1 FROM invoices WHERE 1=0");
        }
        $creditInvoices = $db->resultSet();
        
        // Fetch visual catalog and categories for driver app local catalog support
        $products = [];
        $categories = [];
        try {
            require_once __DIR__ . '/../Models/RepCatalog.php';
            $catalogModel = new RepCatalog();
            $products = $catalogModel->getVisualCatalog(false);
            
            $db->query("SELECT id, name FROM item_categories ORDER BY name ASC");
            $categories = $db->resultSet() ?: [];
        } catch (Exception $e) {
            error_log("Failed to fetch products/categories in driver api_sync_pull: " . $e->getMessage());
        }

        // Debug helper: fetch ALL active deliveries (status != 'Completed') in the DB to see why none matched
        $db = new Database();
        $db->query("SELECT d.id, d.rep_route_id, d.vehicle_number, d.driver_name, d.partner_name, d.status FROM deliveries d WHERE d.status != 'Completed'");
        $allActive = $db->resultSet();
        
        $db->query("SELECT id, username, employee_id, email, role FROM users");
        $allUsers = $db->resultSet();
        
        $userDetails = $this->routeModel->getUserDetails($userId);
        
        echo json_encode([
            'success' => true,
            'assigned_delivery' => $activeDelivery ?: null,
            'shops' => $shops,
            'invoices' => $invoices,
            'invoice_items' => $invoiceItems,
            'employees' => $employees,
            'credit_invoices' => $creditInvoices,
            'products' => $products,
            'categories' => $categories,
            'debug' => [
                'user_id' => $userId,
                'user_details' => $userDetails,
                'search_full_name' => $userDetails ? ($userDetails->full_name ?: $userDetails->username) : null,
                'search_username' => $userDetails ? $userDetails->username : null,
                'all_active_deliveries_in_db' => $allActive,
                'all_users_in_db' => $allUsers
            ]
        ], JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }

    // JSON API for Native Mobile App Push Synchronization
    public function api_sync_push() {
        header('Content-Type: application/json');
        
        $logPath = dirname(dirname(__DIR__)) . '/sync_debug.log';
        
        $rawInput = file_get_contents('php://input');
        file_put_contents($logPath, "[" . date('Y-m-d H:i:s') . "] PUSH SYNC REQUEST:\nRAW: " . $rawInput . "\n\n", FILE_APPEND);
        
        $postData = json_decode($rawInput, true) ?: [];
        file_put_contents($logPath, "[" . date('Y-m-d H:i:s') . "] DECODED PAYLOAD:\n" . print_r($postData, true) . "\n\n", FILE_APPEND);
        
        $userId = intval($postData['user_id'] ?? 0);
        $routeId = intval($postData['route_id'] ?? 0);
        
        if ($userId <= 0 || $routeId <= 0) {
            file_put_contents($logPath, "[" . date('Y-m-d H:i:s') . "] VALIDATION FAILED: userId=$userId, routeId=$routeId\n\n", FILE_APPEND);
            echo json_encode(['success' => false, 'message' => 'Missing route or user parameters.']);
            exit;
        }
        
        try {
            $billingModel = $this->model('DriverInvoice');
            file_put_contents($logPath, "[" . date('Y-m-d H:i:s') . "] Successfully loaded DriverInvoice model\n\n", FILE_APPEND);
            
            // 1. Process trip odometer & status updates
            if (isset($postData['trip_details'])) {
                file_put_contents($logPath, "[" . date('Y-m-d H:i:s') . "] Processing trip details...\n", FILE_APPEND);
                $td = $postData['trip_details'];
                $status = $td['status'] ?? '';
                $deliveryId = intval($td['id'] ?? 0);
                
                if ($deliveryId > 0) {
                    if ($status === 'Accepted') {
                        $this->routeModel->acceptRoute($deliveryId);
                        $this->logActivity('Accept Route', 'Delivery Route', "Driver accepted Delivery Route ID {$deliveryId} via Driver Mobile App Sync.", $deliveryId, null, $td);
                    } elseif ($status === 'In Transit') {
                        $startMeter = floatval($td['start_meter'] ?? 0);
                        $driverName = $td['driver_name'] ?? '';
                        $partnerName = $td['partner_name'] ?? '';
                        $this->routeModel->startTrip($deliveryId, $startMeter, $driverName, $partnerName);
                        $this->logActivity('Start Trip', 'Delivery Route', "Driver started trip for Delivery Route ID {$deliveryId} with Odometer {$startMeter} via Driver Mobile App Sync.", $deliveryId, null, $td);
                    } elseif ($status === 'Completed') {
                        $endMeter = floatval($td['end_meter'] ?? 0);
                        $cashDenoms = isset($td['cash_denominations']) ? json_encode($td['cash_denominations']) : null;
                        $this->routeModel->endTrip($deliveryId, $endMeter, $cashDenoms);
                        $this->logActivity('End Trip', 'Delivery Route', "Driver completed trip for Delivery Route ID {$deliveryId} with Ending Odometer {$endMeter} via Driver Mobile App Sync.", $deliveryId, null, $td);
                    }
                }
            }
            
            // 2. Process invoice item quantity modifications
            if (isset($postData['deliveries']) && is_array($postData['deliveries'])) {
                file_put_contents($logPath, "[" . date('Y-m-d H:i:s') . "] Processing " . count($postData['deliveries']) . " deliveries...\n", FILE_APPEND);
                foreach ($postData['deliveries'] as $del) {
                    $invoiceId = intval($del['invoice_id'] ?? 0);
                    $status = $del['delivery_status'] ?? 'Delivered';
                    
                    $oldValues = null;
                    try {
                        $oldValues = [
                            'invoice' => $billingModel->getInvoiceDetails($invoiceId),
                            'items' => $billingModel->getInvoiceItems($invoiceId)
                        ];
                    } catch (Exception $e) {}

                    // Update overall invoice delivery status
                    $billingModel->updateInvoiceDeliveryStatus($invoiceId, $status);
                    
                    if (isset($del['items']) && is_array($del['items'])) {
                        foreach ($del['items'] as $item) {
                            $itemId = intval($item['server_item_id'] ?? 0);
                            $qty = floatval($item['delivered_qty'] ?? 0);
                            if ($itemId > 0) {
                                if ($qty <= 0) {
                                    $billingModel->deleteInvoiceItem($itemId);
                                } else {
                                    $billingModel->updateInvoiceItemQty($itemId, $qty);
                                }
                            }
                        }
                    }

                    $newValues = null;
                    try {
                        $newValues = [
                            'invoice' => $billingModel->getInvoiceDetails($invoiceId),
                            'items' => $billingModel->getInvoiceItems($invoiceId)
                        ];
                    } catch (Exception $e) {}

                    $this->logActivity('Edit Invoice', 'Billing', "Delivery status set to '{$status}' for Invoice ID {$invoiceId} via Driver Mobile App Sync.", $invoiceId, $oldValues, $newValues);
                }
            }
            
            // 3. Process payment collections
            if (isset($postData['payments']) && is_array($postData['payments'])) {
                file_put_contents($logPath, "[" . date('Y-m-d H:i:s') . "] Processing " . count($postData['payments']) . " payments...\n", FILE_APPEND);
                foreach ($postData['payments'] as $idx => $pmt) {
                    file_put_contents($logPath, "[" . date('Y-m-d H:i:s') . "] Payment #$idx: " . print_r($pmt, true) . "\n", FILE_APPEND);
                    
                    $customerId = intval($pmt['customer_id'] ?? 0);
                    $method = $pmt['payment_method'] ?? 'Cash';
                    $amount = floatval($pmt['amount'] ?? 0);
                    
                    if ($customerId > 0 && $amount > 0) {
                        $collections = [
                            'cash' => $method === 'Cash' ? $amount : 0,
                            'bank' => ($method === 'Bank' || $method === 'Bank Transfer') ? $amount : 0,
                            'cheque' => $method === 'Cheque' ? $amount : 0,
                            'cheque_bank' => $pmt['bank_name'] ?? '',
                            'cheque_number' => $pmt['cheque_number'] ?? '',
                            'cheque_date' => $pmt['cheque_date'] ?? ''
                        ];
                        
                        file_put_contents($logPath, "[" . date('Y-m-d H:i:s') . "] Calling checkoutShop: cust=$customerId, route=$routeId, user=$userId, collections=" . print_r($collections, true) . "\n", FILE_APPEND);
                        $billingModel->checkoutShop($customerId, $routeId, $userId, $collections);
                        $this->autoApplyPaymentsToInvoices($customerId);
                        $this->logActivity('Collection Received', 'Billing', "Collected Rs. " . number_format($amount, 2) . " via {$method} from Customer ID {$customerId} (Route ID: {$routeId}) via Driver Mobile App Sync.", null, null, $pmt);
                        file_put_contents($logPath, "[" . date('Y-m-d H:i:s') . "] checkoutShop succeeded and invoice payment statuses healed\n", FILE_APPEND);
                    } else {
                        file_put_contents($logPath, "[" . date('Y-m-d H:i:s') . "] Skipping payment: customerId=$customerId, amount=$amount\n", FILE_APPEND);
                    }
                }
            }
            
            file_put_contents($logPath, "[" . date('Y-m-d H:i:s') . "] PUSH SYNC COMPLETED SUCCESSFULLY\n\n", FILE_APPEND);
            echo json_encode(['success' => true, 'message' => 'Offline driver changes synchronized successfully!']);
            exit;
        } catch (Exception $e) {
            file_put_contents($logPath, "[" . date('Y-m-d H:i:s') . "] FATAL ERROR DURING PUSH: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n\n", FILE_APPEND);
            echo json_encode(['success' => false, 'message' => 'Server sync processing error: ' . $e->getMessage()]);
            exit;
        }
    }

    // Helper to auto-apply customer payments to non-voided invoices in chronological (FIFO) order
    private function autoApplyPaymentsToInvoices($customerId) {
        $db = new Database();
        
        // 1. Get total paid amount for this customer
        $db->query("SELECT COALESCE(SUM(amount), 0) as total_paid FROM customer_payments WHERE customer_id = :cid");
        $db->bind(':cid', $customerId);
        $rowPaid = $db->single();
        $totalPaid = $rowPaid ? floatval($rowPaid->total_paid) : 0.0;
        
        // 2. Get all non-voided invoices in chronological order
        $db->query("
            SELECT id, 
                   (total_amount - COALESCE(CASE WHEN global_discount_type = '%' THEN (total_amount * global_discount_val / 100) ELSE global_discount_val END, 0) + COALESCE(tax_amount, 0)) as true_grand_total,
                   status
            FROM invoices
            WHERE customer_id = :cid AND status != 'Voided'
            ORDER BY invoice_date ASC, id ASC
        ");
        $db->bind(':cid', $customerId);
        $invoices = $db->resultSet();
        
        $remainingPaid = $totalPaid;
        
        foreach ($invoices as $inv) {
            $grandTotal = floatval($inv->true_grand_total);
            
            if ($remainingPaid >= $grandTotal - 0.01) { // Allow minor rounding differences
                $newStatus = 'Paid';
                $remainingPaid -= $grandTotal;
            } else {
                $newStatus = 'Unpaid';
                $remainingPaid = 0;
            }
            
            if ($inv->status !== $newStatus) {
                $db->query("UPDATE invoices SET status = :status WHERE id = :id");
                $db->bind(':status', $newStatus);
                $db->bind(':id', $inv->id);
                $db->execute();
            }
        }
    }
}
