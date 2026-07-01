<?php
class MasterRouteController extends Controller {
    private $trackingModel;
    private $deliveryModel;

    public function __construct() {
        if (!isset($_SESSION['user_id'])) { 
            header('Location: ' . APP_URL . '/auth/login'); 
            exit; 
        }
        $this->trackingModel = $this->model('RepTracking');
        $this->deliveryModel = $this->model('Delivery');
    }

    public function index() {
        $vehicleModel = $this->model('Vehicle');
        $employeeModel = $this->model('Employee');

        $vehicles = $vehicleModel->getAllVehicles();
        $allEmployees = $employeeModel->getAllEmployees();

        $drivers = array_filter($allEmployees, function($emp) {
            return strtolower($emp->job_title) === 'driver' && $emp->status === 'Active';
        });

        $db = new Database();
        $db->query("SELECT id FROM chart_of_accounts WHERE account_code = '1600'");
        $parent = $db->single();
        $parentId = $parent ? $parent->id : 0;
        
        $db->query("SELECT * FROM chart_of_accounts WHERE parent_id = :pid ORDER BY account_code ASC");
        $db->bind(':pid', $parentId);
        $bankAccounts = $db->resultSet() ?: [];

        $db->query("SELECT id, account_code, account_name FROM chart_of_accounts ORDER BY account_code ASC");
        $allAccounts = $db->resultSet() ?: [];

        $data = [
            'title' => 'Master Route Control Panel',
            'content_view' => 'master-route/index',
            'routes' => $this->getUnifiedRoutes(),
            'vehicles' => $vehicles,
            'drivers' => $drivers,
            'employees' => $allEmployees,
            'bank_accounts' => $bankAccounts,
            'all_accounts' => $allAccounts
        ];
        
        $this->view('layouts/main', $data);
    }

    private function getUnifiedRoutes() {
        $db = new Database();
        $db->query("
            SELECT r.*, COALESCE(e.first_name, u.username) as first_name, COALESCE(e.last_name, '') as last_name,
                (SELECT COUNT(*) FROM invoices WHERE rep_route_id = r.id AND status != 'Voided') as bill_count,
                (SELECT COALESCE(SUM(total_amount - COALESCE(CASE WHEN global_discount_type = '%' THEN (total_amount * global_discount_val / 100) ELSE global_discount_val END, 0) + COALESCE(tax_amount, 0)), 0) 
                 FROM invoices WHERE rep_route_id = r.id AND status != 'Voided') as total_sales,
                (SELECT COUNT(*) FROM pending_collections WHERE route_id = r.id AND status = 'Pending') as unfinalized_count,
                rb.name as binding_name,
                d.id as delivery_id, d.vehicle_number, d.driver_name, d.partner_name, d.status as delivery_status,
                (SELECT COUNT(*) FROM delivery_picking_items WHERE delivery_id = d.id) as total_items,
                (SELECT COUNT(*) FROM delivery_picking_items WHERE delivery_id = d.id AND is_picked = 1) as picked_items,
                (SELECT COUNT(*) FROM delivery_picking_items WHERE delivery_id = d.id AND is_verified = 1) as verified_items
            FROM rep_daily_routes r
            LEFT JOIN users u ON r.user_id = u.id
            LEFT JOIN employees e ON u.email = e.email
            LEFT JOIN route_bindings rb ON r.route_binding_id = rb.id
            LEFT JOIN deliveries d ON d.rep_route_id = r.id OR d.secondary_rep_route_id = r.id
            ORDER BY r.start_time DESC
        ");
        $rawRoutes = $db->resultSet() ?: [];
        
        $grouped = [];
        $unbound = [];
        
        foreach ($rawRoutes as $route) {
            if ($route->route_binding_id) {
                $grouped[$route->route_binding_id][] = $route;
            } else {
                $unbound[] = $route;
            }
        }
        
        $finalRoutes = [];
        foreach ($unbound as $route) {
            $route->is_bound_group = false;
            $finalRoutes[] = $route;
        }
        
        foreach ($grouped as $bindingId => $routesList) {
            usort($routesList, function($a, $b) { return $a->id - $b->id; });
            $rep = $routesList[0];
            
            $merged = clone $rep;
            $merged->route_name = $rep->binding_name;
            $merged->bill_count = 0;
            $merged->total_sales = 0.0;
            $merged->unfinalized_count = 0;
            $merged->is_bound_group = true;
            
            $names = [];
            foreach ($routesList as $r) {
                $merged->bill_count += intval($r->bill_count);
                $merged->total_sales += floatval($r->total_sales);
                $merged->unfinalized_count += intval($r->unfinalized_count);
                $names[] = $r->route_name;
            }
            
            $merged->constituent_routes_info = implode(' & ', $names);
            $finalRoutes[] = $merged;
        }
        
        usort($finalRoutes, function($a, $b) {
            return strtotime($b->start_time) - strtotime($a->start_time);
        });
        
        return $finalRoutes;
    }

    public function api_get_route_details($routeId) {
        $bills = $this->trackingModel->getRouteBills($routeId);
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'bills' => $bills]);
        exit;
    }

    public function api_get_route_path($routeId) {
        $path = $this->trackingModel->getRoutePath($routeId);
        header('Content-Type: application/json');
        if (!$path) {
            echo json_encode(['status' => 'error', 'message' => 'Route not found']);
            exit;
        }
        echo json_encode(['status' => 'success', 'path' => $path]);
        exit;
    }

    public function api_get_outstanding_bills($routeId) {
        $db = new Database();
        $routeIds = [$routeId];
        $db->query("SELECT route_binding_id FROM rep_daily_routes WHERE id = :rid LIMIT 1");
        $db->bind(':rid', $routeId);
        $routeRow = $db->single();
        if ($routeRow && $routeRow->route_binding_id) {
            $db->query("SELECT id FROM rep_daily_routes WHERE route_binding_id = :bid");
            $db->bind(':bid', $routeRow->route_binding_id);
            $boundRoutes = $db->resultSet();
            foreach ($boundRoutes as $br) {
                $routeIds[] = intval($br->id);
            }
        }
        $routeIds = array_unique($routeIds);
        
        $mainAreaIds = [];
        foreach ($routeIds as $rid) {
            $db->query("
                SELECT m.main_area_id
                FROM mca_areas m
                JOIN rep_daily_routes r ON r.route_name = m.name
                WHERE r.id = :rid
                LIMIT 1
            ");
            $db->bind(':rid', $rid);
            $row = $db->single();
            if ($row && $row->main_area_id) {
                $mainAreaIds[] = intval($row->main_area_id);
            } else {
                $db->query("
                    SELECT DISTINCT m.main_area_id
                    FROM invoices i
                    JOIN customers c ON i.customer_id = c.id
                    JOIN mca_areas m ON c.mca_id = m.id
                    WHERE i.rep_route_id = :rid AND c.mca_id IS NOT NULL
                    LIMIT 1
                ");
                $db->bind(':rid', $rid);
                $rowFallback = $db->single();
                if ($rowFallback && $rowFallback->main_area_id) {
                    $mainAreaIds[] = intval($rowFallback->main_area_id);
                }
            }
        }
        
        if (empty($mainAreaIds)) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'bills' => []]);
            exit;
        }
        
        $areaIdsStr = implode(',', array_unique($mainAreaIds));
        
        $db->query("
            SELECT DISTINCT c.id
            FROM customers c
            JOIN mca_areas m ON c.mca_id = m.id
            JOIN invoices i ON i.customer_id = c.id
            WHERE m.main_area_id IN ($areaIdsStr) AND i.status != 'Voided'
        ");
        $custs = $db->resultSet();
        foreach ($custs as $c) {
            $this->autoApplyPaymentsToInvoices($c->id);
        }
        
        $routeIdsStr = implode(',', array_map('intval', $routeIds));
        
        $db->query("
            SELECT c.id as customer_id, c.name as customer_name, m.name as mca_name,
                   (SELECT COALESCE(SUM(total_amount - COALESCE(CASE WHEN global_discount_type = '%' THEN (total_amount * global_discount_val / 100) ELSE global_discount_val END, 0) + COALESCE(tax_amount, 0)), 0) 
                    FROM invoices WHERE customer_id = c.id AND status = 'Unpaid') as outstanding_amount
            FROM customers c
            JOIN mca_areas m ON c.mca_id = m.id
            WHERE m.main_area_id IN ($areaIdsStr)
            HAVING outstanding_amount > 0
            ORDER BY c.name ASC
        ");
        $outstandingCustomers = $db->resultSet();
        
        $customersWithBills = [];
        foreach ($outstandingCustomers as $cust) {
            $db->query("
                SELECT i.id, i.invoice_number, i.invoice_date,
                       (total_amount - COALESCE(CASE WHEN global_discount_type = '%' THEN (total_amount * global_discount_val / 100) ELSE global_discount_val END, 0) + COALESCE(tax_amount, 0)) as true_grand_total
                FROM invoices i
                WHERE i.customer_id = :cid AND i.status = 'Unpaid'
                  AND i.rep_route_id NOT IN ($routeIdsStr)
                ORDER BY i.invoice_date ASC
            ");
            $db->bind(':cid', $cust->customer_id);
            $bills = $db->resultSet() ?: [];
            
            if (!empty($bills)) {
                $customersWithBills[] = [
                    'customer_id' => $cust->customer_id,
                    'customer_name' => $cust->customer_name,
                    'mca_name' => $cust->mca_name,
                    'outstanding_amount' => $cust->outstanding_amount,
                    'bills' => $bills
                ];
            }
        }
        
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'bills' => $customersWithBills]);
        exit;
    }

    private function autoApplyPaymentsToInvoices($customerId) {
        $db = new Database();
        $db->query("SELECT COALESCE(SUM(amount), 0) as total_paid FROM customer_payments WHERE customer_id = :cid");
        $db->bind(':cid', $customerId);
        $rowPaid = $db->single();
        $totalPaid = $rowPaid ? floatval($rowPaid->total_paid) : 0.0;
        
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
            if ($remainingPaid >= $grandTotal - 0.01) {
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

    public function api_get_route_variances($routeId) {
        $db = new Database();

        // Fetch route details first
        $db->query("SELECT status, route_name, route_binding_id FROM rep_daily_routes WHERE id = :id");
        $db->bind(':id', $routeId);
        $route = $db->single();

        $db->query("
            SELECT d.id as id, d.vehicle_number, d.driver_name, d.status
            FROM deliveries d
            WHERE d.rep_route_id = :rid OR d.secondary_rep_route_id = :rid
        ");
        $db->bind(':rid', $routeId);
        $deliveries = $db->resultSet() ?: [];

        // If no delivery exists but route status is 'Loading', auto-create it
        if (empty($deliveries) && $route && $route->status === 'Loading') {
            $db->query("SELECT r.route_name, COALESCE(e.first_name, u.username) as first_name, COALESCE(e.last_name, '') as last_name 
                        FROM rep_daily_routes r 
                        LEFT JOIN users u ON r.user_id = u.id 
                        LEFT JOIN employees e ON u.employee_id = e.id 
                        WHERE r.id = :rid");
            $db->bind(':rid', $routeId);
            $routeInfo = $db->single();
            $repName = $routeInfo ? trim($routeInfo->first_name . ' ' . $routeInfo->last_name) : 'Pending Rep';

            $db->query("INSERT INTO deliveries (rep_route_id, delivery_date, vehicle_number, driver_name, partner_name, status) 
                        VALUES (:rid, CURDATE(), 'Pending Vehicle', :driver, '', 'Arranged')");
            $db->bind(':rid', $routeId);
            $db->bind(':driver', $repName);
            $db->execute();
            
            // Re-fetch deliveries
            $db->query("
                SELECT d.id as id, d.vehicle_number, d.driver_name, d.status
                FROM deliveries d
                WHERE d.rep_route_id = :rid OR d.secondary_rep_route_id = :rid
            ");
            $db->bind(':rid', $routeId);
            $deliveries = $db->resultSet() ?: [];
        }

        $results = [];
        foreach ($deliveries as $del) {
            // Check if picking items are populated
            $db->query("SELECT COUNT(*) as cnt FROM delivery_picking_items WHERE delivery_id = :did");
            $db->bind(':did', $del->id);
            $check = $db->single();

            if (!$check || intval($check->cnt) === 0) {
                // Populate picking items
                $rids = [intval($routeId)];
                if ($route && $route->route_binding_id) {
                    $db->query("SELECT id FROM rep_daily_routes WHERE route_binding_id = :bid");
                    $db->bind(':bid', $route->route_binding_id);
                    $boundRoutes = $db->resultSet();
                    foreach ($boundRoutes as $br) {
                        $rids[] = intval($br->id);
                    }
                }
                $rids = array_unique($rids);
                $ridsStr = implode(',', $rids);

                $db->query("
                    SELECT ii.item_id, ii.variation_option_id, ii.description as item_name, SUM(ii.quantity) as required_qty
                    FROM invoice_items ii
                    JOIN invoices i ON ii.invoice_id = i.id
                    WHERE i.rep_route_id IN ($ridsStr) AND i.status != 'Voided'
                    GROUP BY ii.item_id, ii.variation_option_id, ii.description
                ");
                $invoiceItems = $db->resultSet() ?: [];
                foreach ($invoiceItems as $item) {
                    $db->query("
                        INSERT INTO delivery_picking_items (delivery_id, item_name, item_id, variation_option_id, required_qty, loaded_qty, is_picked)
                        VALUES (:delivery_id, :item_name, :item_id, :variation_option_id, :required_qty, :loaded_qty, 0)
                    ");
                    $db->bind(':delivery_id', $del->id);
                    $db->bind(':item_name', $item->item_name);
                    $db->bind(':item_id', $item->item_id);
                    $db->bind(':variation_option_id', $item->variation_option_id);
                    $db->bind(':required_qty', $item->required_qty);
                    $db->bind(':loaded_qty', $item->required_qty);
                    $db->execute();
                }
            }

            $db->query("
                SELECT dpi.item_id, dpi.item_name, dpi.required_qty, dpi.loaded_qty as pre_loaded_qty, 
                       dpi.final_loaded_qty, dpi.variance, dpi.is_verified, dpi.verified_at, u.username as verifier_name
                FROM delivery_picking_items dpi
                LEFT JOIN users u ON dpi.verified_by = u.id
                WHERE dpi.delivery_id = :did
            ");
            $db->bind(':did', $del->id);
            $items = $db->resultSet() ?: [];
            
            $shortages = 0;
            $overages = 0;
            $totalItems = count($items);
            $verifiedItems = 0;
            
            foreach ($items as $item) {
                $item->required_qty = floatval($item->required_qty);
                $item->pre_loaded_qty = floatval($item->pre_loaded_qty);
                $item->final_loaded_qty = $item->final_loaded_qty !== null ? floatval($item->final_loaded_qty) : null;
                $item->variance = floatval($item->variance);
                $item->is_verified = intval($item->is_verified);
                
                if ($item->is_verified) {
                    $verifiedItems++;
                }
                if ($item->variance < 0) {
                    $shortages += abs($item->variance);
                } elseif ($item->variance > 0) {
                    $overages += $item->variance;
                }
            }
            
            $results[] = [
                'delivery_id' => $del->id,
                'vehicle_number' => $del->vehicle_number,
                'driver_name' => $del->driver_name,
                'status' => $del->status,
                'total_items' => $totalItems,
                'verified_items' => $verifiedItems,
                'shortages' => $shortages,
                'overages' => $overages,
                'items' => $items
            ];
        }
        
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'deliveries' => $results]);
        exit;
    }

    public function api_get_delivery_details($id) {
        $delivery = $this->deliveryModel->getDeliveryById($id);
        if (!$delivery) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Delivery not found.']);
            exit;
        }

        $invoices = $this->deliveryModel->getDeliveryInvoices($delivery->rep_route_id, $delivery->secondary_rep_route_id ?? null);
        $creditInvoices = $this->deliveryModel->getDeliveryCreditInvoices($delivery->rep_route_id, $delivery->secondary_rep_route_id ?? null);
        $balancing = $this->deliveryModel->getDeliveryBalancingData($id);

        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'delivery' => $delivery,
            'invoices' => $invoices,
            'credit_invoices' => $creditInvoices,
            'balancing' => $balancing
        ]);
        exit;
    }

    public function arrange() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Invalid Request Method']);
            exit;
        }

        $rawInput = file_get_contents('php://input');
        $postData = json_decode($rawInput, true);
        if (!$postData) {
            $postData = $_POST;
        }

        $deliveryData = [
            'rep_route_id' => intval($postData['rep_route_id'] ?? 0),
            'secondary_rep_route_id' => !empty($postData['secondary_rep_route_id']) ? intval($postData['secondary_rep_route_id']) : null,
            'delivery_date' => trim($postData['delivery_date'] ?? ''),
            'vehicle_number' => trim($postData['vehicle_number'] ?? ''),
            'driver_name' => trim($postData['driver_name'] ?? ''),
            'partner_name' => trim($postData['partner_name'] ?? ''),
            'selected_credit_invoices' => !empty($postData['selected_credit_invoices']) ? json_encode($postData['selected_credit_invoices']) : null
        ];

        if (empty($deliveryData['rep_route_id']) || empty($deliveryData['delivery_date'])) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'All mandatory fields (Route, Date) are required.']);
            exit;
        }

        $deliveryId = $this->deliveryModel->createDelivery($deliveryData);

        header('Content-Type: application/json');
        if ($deliveryId) {
            // Update route status to Loading
            $db = new Database();
            $db->query("UPDATE rep_daily_routes SET status = 'Loading' WHERE id = :id");
            $db->bind(':id', $deliveryData['rep_route_id']);
            $db->execute();

            if ($deliveryData['secondary_rep_route_id']) {
                $db->query("UPDATE rep_daily_routes SET status = 'Loading' WHERE id = :id");
                $db->bind(':id', $deliveryData['secondary_rep_route_id']);
                $db->execute();
            }

            $this->logActivity('Arrange Delivery', 'RepTracking', "Created delivery arrangement ID: {$deliveryId} and moved routes to Loading status", $deliveryData['rep_route_id']);

            echo json_encode(['status' => 'success', 'message' => 'Delivery arranged successfully!', 'delivery_id' => $deliveryId]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to arrange delivery. Database transaction error.']);
        }
        exit;
    }

    public function finalize() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Invalid Request Method']);
            exit;
        }

        $rawInput = file_get_contents('php://input');
        $postData = json_decode($rawInput, true);
        if (!$postData) {
            $postData = $_POST;
        }

        $deliveryId = intval($postData['delivery_id'] ?? 0);
        if ($deliveryId <= 0) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Delivery ID is required.']);
            exit;
        }

        try {
            $adminUserId = $_SESSION['user_id'];
            $selectedPaymentIds = isset($postData['selected_payment_ids']) ? array_map('intval', $postData['selected_payment_ids']) : [];
            $selectedInvoiceIds = isset($postData['selected_invoice_ids']) ? array_map('intval', $postData['selected_invoice_ids']) : [];
            $debitAccounts = $postData['debit_accounts'] ?? [];
            $creditAccounts = $postData['credit_accounts'] ?? [];
            $returnedItems = $postData['returned_items'] ?? [];

            $this->deliveryModel->finalizeDelivery(
                $deliveryId, 
                $adminUserId, 
                $selectedPaymentIds, 
                $selectedInvoiceIds, 
                $debitAccounts, 
                $creditAccounts,
                $returnedItems
            );

            // Update route status to Completed
            $delivery = $this->deliveryModel->getDeliveryById($deliveryId);
            if ($delivery) {
                $db = new Database();
                $db->query("UPDATE rep_daily_routes SET status = 'Completed' WHERE id = :id OR route_binding_id = (SELECT route_binding_id FROM rep_daily_routes WHERE id = :id2)");
                $db->bind(':id', $delivery->rep_route_id);
                $db->bind(':id2', $delivery->rep_route_id);
                $db->execute();
            }

            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'message' => 'Delivery route finalized successfully! Route marked as Completed.']);
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function api_update_route_status() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Invalid Request Method']);
            exit;
        }

        $rawInput = file_get_contents('php://input');
        $postData = json_decode($rawInput, true);
        if (!$postData) {
            $postData = $_POST;
        }

        $routeId = intval($postData['route_id'] ?? 0);
        $targetStatus = trim($postData['status'] ?? '');

        $allowedStatuses = [
            'Active', 'Pending GL', 'Adjustments', 'Loading', 
            'Variance Adjustment', 'Finalizing', 'Completed'
        ];

        header('Content-Type: application/json');
        if (!$routeId || !in_array($targetStatus, $allowedStatuses)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid parameters: ID=' . $routeId . ', Status=' . $targetStatus]);
            exit;
        }

        $db = new Database();
        $db->query("SELECT status, route_name, route_binding_id FROM rep_daily_routes WHERE id = :id");
        $db->bind(':id', $routeId);
        $oldRoute = $db->single();
        $oldStatus = $oldRoute ? $oldRoute->status : 'Unknown';
        $routeName = $oldRoute ? $oldRoute->route_name : '';

        // Automatically create and expose loading task if moving to Loading status
        if ($targetStatus === 'Loading') {
            $db->query("SELECT id FROM deliveries WHERE rep_route_id = :rid OR secondary_rep_route_id = :rid");
            $db->bind(':rid', $routeId);
            $del = $db->single();
            
            $deliveryId = null;
            if (!$del) {
                $db->query("SELECT r.route_name, COALESCE(e.first_name, u.username) as first_name, COALESCE(e.last_name, '') as last_name 
                            FROM rep_daily_routes r 
                            LEFT JOIN users u ON r.user_id = u.id 
                            LEFT JOIN employees e ON u.employee_id = e.id 
                            WHERE r.id = :rid");
                $db->bind(':rid', $routeId);
                $routeInfo = $db->single();
                $repName = $routeInfo ? trim($routeInfo->first_name . ' ' . $routeInfo->last_name) : 'Pending Rep';

                $db->query("INSERT INTO deliveries (rep_route_id, delivery_date, vehicle_number, driver_name, partner_name, status) 
                            VALUES (:rid, CURDATE(), 'Pending Vehicle', :driver, '', 'Arranged')");
                $db->bind(':rid', $routeId);
                $db->bind(':driver', $repName);
                $db->execute();
                $deliveryId = $db->lastInsertId();
            } else {
                $deliveryId = $del->id;
            }

            if ($deliveryId) {
                // Ensure picking items are populated
                $db->query("SELECT COUNT(*) as cnt FROM delivery_picking_items WHERE delivery_id = :did");
                $db->bind(':did', $deliveryId);
                $check = $db->single();

                if (!$check || intval($check->cnt) === 0) {
                    // Populate picking items
                    $rids = [intval($routeId)];
                    if ($oldRoute && $oldRoute->route_binding_id) {
                        $db->query("SELECT id FROM rep_daily_routes WHERE route_binding_id = :bid");
                        $db->bind(':bid', $oldRoute->route_binding_id);
                        $boundRoutes = $db->resultSet();
                        foreach ($boundRoutes as $br) {
                            $rids[] = intval($br->id);
                        }
                    }
                    $rids = array_unique($rids);
                    $ridsStr = implode(',', $rids);

                    $db->query("
                        SELECT ii.item_id, ii.variation_option_id, ii.description as item_name, SUM(ii.quantity) as required_qty
                        FROM invoice_items ii
                        JOIN invoices i ON ii.invoice_id = i.id
                        WHERE i.rep_route_id IN ($ridsStr) AND i.status != 'Voided'
                        GROUP BY ii.item_id, ii.variation_option_id, ii.description
                    ");
                    $invoiceItems = $db->resultSet() ?: [];
                    foreach ($invoiceItems as $item) {
                        $db->query("
                            INSERT INTO delivery_picking_items (delivery_id, item_name, item_id, variation_option_id, required_qty, loaded_qty, is_picked)
                            VALUES (:delivery_id, :item_name, :item_id, :variation_option_id, :required_qty, :loaded_qty, 0)
                        ");
                        $db->bind(':delivery_id', $deliveryId);
                        $db->bind(':item_name', $item->item_name);
                        $db->bind(':item_id', $item->item_id);
                        $db->bind(':variation_option_id', $item->variation_option_id);
                        $db->bind(':required_qty', $item->required_qty);
                        $db->bind(':loaded_qty', $item->required_qty);
                        $db->execute();
                    }
                }
            }
        }

        $db->query("UPDATE rep_daily_routes SET status = :status WHERE id = :id");
        $db->bind(':status', $targetStatus);
        $db->bind(':id', $routeId);
        $db->execute();

        if ($oldRoute && $oldRoute->route_binding_id) {
            $db->query("UPDATE rep_daily_routes SET status = :status WHERE route_binding_id = :bid");
            $db->bind(':status', $targetStatus);
            $db->bind(':bid', $oldRoute->route_binding_id);
            $db->execute();
        }

        // Keep deliveries in sync
        $delStatus = 'Arranged';
        if ($targetStatus === 'Completed') {
            $delStatus = 'Completed';
        }
        $db->query("UPDATE deliveries SET status = :status WHERE rep_route_id = :rid OR secondary_rep_route_id = :rid");
        $db->bind(':status', $delStatus);
        $db->bind(':rid', $routeId);
        $db->execute();

        $this->logActivity('Route Status Update', 'RepTracking', "Moved route '{$routeName}' status from '{$oldStatus}' to '{$targetStatus}'", $routeId);

        echo json_encode(['status' => 'success', 'message' => 'Route status updated successfully to ' . $targetStatus]);
        exit;
    }

    private function logActivity($action, $module, $desc, $refId = null) {
        try {
            $db = new Database();
            $db->query("INSERT INTO audit_logs (user_id, action, module, description, reference_id, ip_address) 
                        VALUES (:uid, :action, :module, :desc, :ref, :ip)");
            $db->bind(':uid', $_SESSION['user_id'] ?? null);
            $db->bind(':action', $action);
            $db->bind(':module', $module);
            $db->bind(':desc', $desc);
            $db->bind(':ref', $refId);
            $db->bind(':ip', $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
            $db->execute();
        } catch (Exception $e) {}
    }

    public function balancing_report($id) {
        $delivery = $this->deliveryModel->getDeliveryById($id);
        if (!$delivery) {
            die("<div style='padding:20px; font-family:sans-serif; color:red;'><h3>Delivery Not Found</h3></div>");
        }
        $balancing = $this->deliveryModel->getDeliveryBalancingData($id);
        $data = [
            'title' => 'Delivery Balancing & Settlement Report',
            'delivery' => $delivery,
            'balancing' => $balancing
        ];
        $this->view('deliveries/balancing_report', $data);
    }

    public function spreadsheet($id) {
        $delivery = $this->deliveryModel->getDeliveryById($id);
        if (!$delivery) {
            die("<div style='padding:20px; font-family:sans-serif; color:red;'><h3>Delivery Not Found</h3></div>");
        }
        $data = [
            'title' => 'Delivery Loading Spreadsheet',
            'delivery' => $delivery,
            'items' => $this->deliveryModel->getDeliverySpreadsheetData($delivery->rep_route_id, $delivery->secondary_rep_route_id ?? null),
            'bills' => $this->deliveryModel->getDeliveryInvoices($delivery->rep_route_id, $delivery->secondary_rep_route_id ?? null)
        ];
        $this->view('deliveries/spreadsheet', $data);
    }

    public function export_csv($id) {
        $delivery = $this->deliveryModel->getDeliveryById($id);
        if (!$delivery) {
            die("Delivery not found.");
        }
        $items = $this->deliveryModel->getDeliverySpreadsheetData($delivery->rep_route_id, $delivery->secondary_rep_route_id ?? null);
        $filename = "Loading_Sheet_" . str_replace(" ", "_", $delivery->route_name) . "_" . $delivery->delivery_date . ".csv";
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        $output = fopen('php://output', 'w');
        fputcsv($output, ['DELIVERY LOADING SHEET SUMMARY']);
        fputcsv($output, ['Route Name:', $delivery->route_name, 'Delivery Date:', $delivery->delivery_date]);
        fputcsv($output, ['Vehicle Number:', $delivery->vehicle_number, 'Representative:', $delivery->first_name . ' ' . $delivery->last_name]);
        fputcsv($output, ['Driver Name:', $delivery->driver_name]);
        fputcsv($output, ['']);
        fputcsv($output, ['Product / Item Description', 'Total Quantity to Load']);
        foreach ($items as $item) {
            fputcsv($output, [$item->item_name, $item->total_qty]);
        }
        fclose($output);
        exit;
    }

    public function api_detach_invoice() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { die("Invalid Request"); }
        $payload = json_decode(file_get_contents('php://input'), true);
        $invoiceId = intval($payload['invoice_id'] ?? 0);
        if ($invoiceId <= 0) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Invalid invoice ID.']);
            exit;
        }
        try {
            $db = new Database();
            $db->query("UPDATE invoices SET rep_route_id = NULL WHERE id = :id");
            $db->bind(':id', $invoiceId);
            $db->execute();
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'message' => 'Invoice detached successfully!']);
            exit;
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            exit;
        }
    }
}
