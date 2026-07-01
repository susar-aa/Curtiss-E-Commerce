<?php
header('Content-Type: application/json');
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
    header('Access-Control-Allow-Credentials: true');
} else {
    header('Access-Control-Allow-Origin: *');
}
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

class PickingController extends Controller {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    // Helper to resolve route bindings (reused from Delivery model)
    private function resolveAllBoundRouteIds($rids) {
        if (empty($rids)) return [];
        $rids = array_map('intval', $rids);
        $ridsStr = implode(',', $rids);
        
        $this->db->query("SELECT DISTINCT route_binding_id FROM rep_daily_routes WHERE id IN ($ridsStr) AND route_binding_id IS NOT NULL");
        $bindings = $this->db->resultSet();
        
        if (!empty($bindings)) {
            $bindingIds = [];
            foreach ($bindings as $b) {
                $bindingIds[] = intval($b->route_binding_id);
            }
            $bindingIdsStr = implode(',', $bindingIds);
            
            $this->db->query("SELECT id FROM rep_daily_routes WHERE route_binding_id IN ($bindingIdsStr)");
            $allRoutes = $this->db->resultSet();
            foreach ($allRoutes as $r) {
                $rids[] = intval($r->id);
            }
        }
        return array_unique($rids);
    }

    // Update delivery status based on picking items completion and route status
    private function updateDeliveryStatus($deliveryId) {
        // Automatically transition to Variance Adjustment if all items are verified
        $this->db->query("SELECT COUNT(*) as unverified FROM delivery_picking_items WHERE delivery_id = :did AND is_verified = 0");
        $this->db->bind(':did', $deliveryId);
        $row = $this->db->single();
        if ($row && intval($row->unverified) === 0) {
            // Also check if there is at least one item
            $this->db->query("SELECT COUNT(*) as total FROM delivery_picking_items WHERE delivery_id = :did");
            $this->db->bind(':did', $deliveryId);
            $totalRow = $this->db->single();
            if ($totalRow && intval($totalRow->total) > 0) {
                // Fetch delivery details to get route ID
                $this->db->query("SELECT rep_route_id, secondary_rep_route_id FROM deliveries WHERE id = :id");
                $this->db->bind(':id', $deliveryId);
                $delivery = $this->db->single();
                if ($delivery) {
                    // Update delivery status to Completed
                    $this->db->query("UPDATE deliveries SET status = 'Completed' WHERE id = :id");
                    $this->db->bind(':id', $deliveryId);
                    $this->db->execute();

                    // Update route status
                    $rids = [intval($delivery->rep_route_id)];
                    if ($delivery->secondary_rep_route_id) {
                        $rids[] = intval($delivery->secondary_rep_route_id);
                    }
                    $rids = $this->resolveAllBoundRouteIds($rids);
                    $ridsStr = implode(',', $rids);

                    $this->db->query("UPDATE rep_daily_routes SET status = 'Variance Adjustment' WHERE id IN ($ridsStr)");
                    $this->db->execute();

                    $this->logPickingActivity('Loading Completed (Auto)', 'Picking', "All picking items verified. Automatically completed loading for delivery #{$deliveryId}. Route(s) moved to Variance Adjustment.", $delivery->rep_route_id);
                }
            }
        }
    }

    // Helper to validate PWA user session
    private function checkAuth() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'unauthorized' => true,
                'error' => 'Session expired. Please log in again.'
            ]);
            exit;
        }

        // Validate user is active in DB
        $userId = intval($_SESSION['user_id']);
        $this->db->query("SELECT id, status FROM users WHERE id = :id");
        $this->db->bind(':id', $userId);
        $userRow = $this->db->single();
        if (!$userRow) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'unauthorized' => true,
                'error' => 'User not found.'
            ]);
            exit;
        }
        if (isset($userRow->status) && strtolower($userRow->status) !== 'active') {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'unauthorized' => true,
                'error' => 'Account is inactive.'
            ]);
            exit;
        }
    }

    // API: Verify login credentials
    public function api_login() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $username = trim($input['username'] ?? '');
        $password = trim($input['password'] ?? '');

        if (empty($username) || empty($password)) {
            echo json_encode(['success' => false, 'error' => 'Username and password are required']);
            exit;
        }

        $userModel = $this->model('User');
        $user = $userModel->login($username, $password);

        if ($user) {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user->id;
            $_SESSION['username'] = $user->username;
            $_SESSION['user_role'] = $user->role;

            echo json_encode([
                'success' => true,
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'role' => $user->role
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid username or password']);
        }
        exit;
    }

    // API: Fetch all loading sheets (deliveries) in Loading stage
    public function api_get_sheets() {
        $this->checkAuth();
        // Auto-create deliveries for any routes in non-completed status that don't have one
        $this->db->query("
            SELECT r.id, r.route_name, r.route_binding_id, 
                   COALESCE(e.first_name, u.username) as first_name, COALESCE(e.last_name, '') as last_name
            FROM rep_daily_routes r
            LEFT JOIN users u ON r.user_id = u.id
            LEFT JOIN employees e ON u.employee_id = e.id
            WHERE r.status NOT IN ('Completed', 'Finalized') 
              AND r.id NOT IN (SELECT rep_route_id FROM deliveries WHERE rep_route_id IS NOT NULL)
              AND r.id NOT IN (SELECT secondary_rep_route_id FROM deliveries WHERE secondary_rep_route_id IS NOT NULL)
        ");
        $missingDeliveries = $this->db->resultSet() ?: [];
        foreach ($missingDeliveries as $route) {
            $repName = trim($route->first_name . ' ' . $route->last_name);
            if (empty($repName)) {
                $repName = 'Pending Rep';
            }
            $this->db->query("INSERT INTO deliveries (rep_route_id, delivery_date, vehicle_number, driver_name, partner_name, status) 
                              VALUES (:rid, CURDATE(), 'Pending Vehicle', :driver, '', 'Arranged')");
            $this->db->bind(':rid', $route->id);
            $this->db->bind(':driver', $repName);
            $this->db->execute();
        }

        $this->db->query("
            SELECT d.id, d.delivery_date, d.vehicle_number, d.driver_name, d.status as db_status, 
                   r.route_name, r.id as route_id, d.secondary_rep_route_id,
                   r.status as route_status,
                   (SELECT COUNT(*) FROM delivery_picking_items WHERE delivery_id = d.id) as total_items,
                   (SELECT COUNT(*) FROM delivery_picking_items WHERE delivery_id = d.id AND is_picked = 1) as picked_items
            FROM deliveries d
            JOIN rep_daily_routes r ON d.rep_route_id = r.id
            WHERE r.status NOT IN ('Completed', 'Finalized')
            ORDER BY d.delivery_date DESC, d.id DESC
        ");
        $sheets = $this->db->resultSet() ?: [];

        foreach ($sheets as $sheet) {
            // Retrieve customer names for this delivery's routes
            $rids = [$sheet->route_id];
            if ($sheet->secondary_rep_route_id) {
                $rids[] = $sheet->secondary_rep_route_id;
            }
            $rids = $this->resolveAllBoundRouteIds($rids);
            
            if (!empty($rids)) {
                $ridsStr = implode(',', $rids);
                $this->db->query("
                    SELECT DISTINCT c.name 
                    FROM invoices i 
                    JOIN customers c ON i.customer_id = c.id 
                    WHERE i.rep_route_id IN ($ridsStr) AND i.status != 'Voided'
                ");
                $custs = $this->db->resultSet();
                $custNames = array_map(function($c) { return $c->name; }, $custs);
                $sheet->customer_info = !empty($custNames) ? implode(', ', $custNames) : 'No Customer Invoices';
            } else {
                $sheet->customer_info = 'No Customer Invoices';
            }

            // Determine picking status based on actual items picking progress
            if ($sheet->picked_items > 0 && $sheet->picked_items < $sheet->total_items) {
                $sheet->status = 'In Progress';
            } else if ($sheet->total_items > 0 && $sheet->picked_items == $sheet->total_items) {
                $sheet->status = 'Ready for Final';
            } else {
                $sheet->status = 'Pending';
            }
        }

        echo json_encode(['success' => true, 'sheets' => $sheets]);
        exit;
    }

    // API: Fetch single loading sheet detail with items list
    public function api_get_sheet_details($deliveryId) {
        $this->checkAuth();
        $deliveryId = intval($deliveryId);
        
        $this->db->query("
            SELECT d.*, r.route_name, r.id as route_id, r.status as route_status 
            FROM deliveries d 
            JOIN rep_daily_routes r ON d.rep_route_id = r.id 
            WHERE d.id = :id
        ");
        $this->db->bind(':id', $deliveryId);
        $delivery = $this->db->single();

        if (!$delivery) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Loading sheet not found']);
            exit;
        }

        // Fetch customer names
        $rids = [$delivery->route_id];
        if ($delivery->secondary_rep_route_id) {
            $rids[] = $delivery->secondary_rep_route_id;
        }
        $rids = $this->resolveAllBoundRouteIds($rids);
        $ridsStr = implode(',', $rids);

        $this->db->query("
            SELECT DISTINCT c.name 
            FROM invoices i 
            JOIN customers c ON i.customer_id = c.id 
            WHERE i.rep_route_id IN ($ridsStr) AND i.status != 'Voided'
        ");
        $custs = $this->db->resultSet();
        $custNames = array_map(function($c) { return $c->name; }, $custs);
        $delivery->customer_info = !empty($custNames) ? implode(', ', $custNames) : 'No Customer Invoices';

        // Check if items are already populated in delivery_picking_items
        $this->db->query("SELECT COUNT(*) as cnt FROM delivery_picking_items WHERE delivery_id = :id");
        $this->db->bind(':id', $deliveryId);
        $check = $this->db->single();

        if (!$check || intval($check->cnt) === 0) {
            // Initialize from aggregated invoices items on the route
            $this->db->query("
                SELECT ii.item_id, ii.variation_option_id, ii.description as item_name, SUM(ii.quantity) as required_qty
                FROM invoice_items ii
                JOIN invoices i ON ii.invoice_id = i.id
                WHERE i.rep_route_id IN ($ridsStr) AND i.status != 'Voided'
                GROUP BY ii.item_id, ii.variation_option_id, ii.description
            ");
            $invoiceItems = $this->db->resultSet();

            foreach ($invoiceItems as $item) {
                $this->db->query("
                    INSERT INTO delivery_picking_items (delivery_id, item_name, item_id, variation_option_id, required_qty, loaded_qty, is_picked)
                    VALUES (:delivery_id, :item_name, :item_id, :variation_option_id, :required_qty, :loaded_qty, 0)
                ");
                $this->db->bind(':delivery_id', $deliveryId);
                $this->db->bind(':item_name', $item->item_name);
                $this->db->bind(':item_id', $item->item_id);
                $this->db->bind(':variation_option_id', $item->variation_option_id);
                $this->db->bind(':required_qty', $item->required_qty);
                $this->db->bind(':loaded_qty', $item->required_qty);
                $this->db->execute();
            }
        }

        // Fetch picking items details with categories and primary images
        $this->db->query("
            SELECT dpi.*, 
                   COALESCE(c.name, 'Uncategorized') as category_name,
                   COALESCE(it.image_path, (SELECT image_path FROM item_images WHERE item_id = it.id AND is_primary = 1 LIMIT 1)) as image_path
            FROM delivery_picking_items dpi
            LEFT JOIN items it ON dpi.item_id = it.id
            LEFT JOIN item_categories c ON it.category_id = c.id
            WHERE dpi.delivery_id = :delivery_id
        ");
        $this->db->bind(':delivery_id', $deliveryId);
        $items = $this->db->resultSet() ?: [];

        // Fetch substitutions for this delivery
        $this->db->query("
            SELECT ps.*, 
                   oi.name as original_item_name, 
                   ri.name as replacement_item_name
            FROM product_substitutions ps
            JOIN items oi ON ps.original_item_id = oi.id
            JOIN items ri ON ps.replacement_item_id = ri.id
            WHERE ps.delivery_id = :delivery_id
        ");
        $this->db->bind(':delivery_id', $deliveryId);
        $substitutions = $this->db->resultSet() ?: [];

        // Convert types appropriately for JSON
        foreach ($items as $item) {
            $item->id = intval($item->id);
            $item->delivery_id = intval($item->delivery_id);
            $item->item_id = $item->item_id ? intval($item->item_id) : null;
            $item->variation_option_id = $item->variation_option_id ? intval($item->variation_option_id) : null;
            $item->required_qty = floatval($item->required_qty);
            $item->loaded_qty = floatval($item->loaded_qty);
            $item->is_picked = intval($item->is_picked);
            $item->final_loaded_qty = $item->final_loaded_qty !== null ? floatval($item->final_loaded_qty) : null;
            $item->is_verified = intval($item->is_verified);
            $item->variance = floatval($item->variance);
            $item->verified_by = $item->verified_by ? intval($item->verified_by) : null;

            // Attach substitution info
            $item->replaced_by_name = null;
            $item->replacement_qty = null;
            $item->replaces_name = null;

            foreach ($substitutions as $sub) {
                if (intval($sub->original_item_id) === $item->item_id) {
                    $item->replaced_by_name = $sub->replacement_item_name;
                    $item->replacement_qty = floatval($sub->loaded_qty);
                }
                if (intval($sub->replacement_item_id) === $item->item_id && floatval($item->required_qty) === 0.0) {
                    $item->replaces_name = $sub->original_item_name;
                }
            }
        }

        echo json_encode([
            'success' => true,
            'delivery' => $delivery,
            'items' => $items
        ]);
        exit;
    }

    // API: Update single item details (loaded_qty, is_picked)
    public function api_update_item() {
        $this->checkAuth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $id = intval($input['id'] ?? 0);
        $loadedQty = floatval($input['loaded_qty'] ?? 0);
        $isPicked = intval($input['is_picked'] ?? 0);
        $updatedAt = $input['updated_at'] ?? date('Y-m-d H:i:s');

        if (!$id) {
            echo json_encode(['success' => false, 'error' => 'Item ID is required']);
            exit;
        }

        // Fetch current item to know the delivery ID
        $this->db->query("SELECT delivery_id FROM delivery_picking_items WHERE id = :id");
        $this->db->bind(':id', $id);
        $item = $this->db->single();

        if (!$item) {
            echo json_encode(['success' => false, 'error' => 'Item not found']);
            exit;
        }

        // Simple direct overwrite update to ensure data reliability
        $this->db->query("
            UPDATE delivery_picking_items 
            SET loaded_qty = :loaded_qty, is_picked = :is_picked, updated_at = :updated_at 
            WHERE id = :id
        ");
        $this->db->bind(':loaded_qty', $loadedQty);
        $this->db->bind(':is_picked', $isPicked);
        $this->db->bind(':updated_at', $updatedAt);
        $this->db->bind(':id', $id);
        $this->db->execute();

        // Recalculate and update the overall delivery status
        $this->updateDeliveryStatus($item->delivery_id);

        echo json_encode(['success' => true]);
        exit;
    }

    // API: Update single item details for final loading (final_loaded_qty, is_verified, variance)
    public function api_update_final_item() {
        $this->checkAuth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $id = intval($input['id'] ?? 0);
        $finalLoadedQty = floatval($input['final_loaded_qty'] ?? 0);
        $isVerified = intval($input['is_verified'] ?? 0);
        $userId = intval($input['user_id'] ?? 0);

        if (!$id) {
            echo json_encode(['success' => false, 'error' => 'Item ID is required']);
            exit;
        }

        // Fetch item details (required_qty) to compute variance
        $this->db->query("SELECT required_qty, delivery_id FROM delivery_picking_items WHERE id = :id");
        $this->db->bind(':id', $id);
        $item = $this->db->single();

        if (!$item) {
            echo json_encode(['success' => false, 'error' => 'Item not found']);
            exit;
        }

        // Variance = Final Loaded Quantity - Required Quantity
        $variance = $finalLoadedQty - floatval($item->required_qty);

        $this->db->query("
            UPDATE delivery_picking_items 
            SET final_loaded_qty = :final_loaded_qty, 
                is_verified = :is_verified, 
                variance = :variance, 
                verified_at = NOW(), 
                verified_by = :verified_by
            WHERE id = :id
        ");
        $this->db->bind(':final_loaded_qty', $finalLoadedQty);
        $this->db->bind(':is_verified', $isVerified);
        $this->db->bind(':variance', $variance);
        $this->db->bind(':verified_by', $userId ? $userId : null);
        $this->db->bind(':id', $id);
        $this->db->execute();

        // Recalculate and update the overall delivery status
        $this->updateDeliveryStatus($item->delivery_id);

        echo json_encode(['success' => true]);
        exit;
    }

    // API: Bulk Sync updates from offline PWA
    public function api_sync() {
        $this->checkAuth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $updates = $input['updates'] ?? [];

        if (empty($updates) || !is_array($updates)) {
            echo json_encode(['success' => true, 'message' => 'No updates to sync']);
            exit;
        }

        $affectedDeliveries = [];

        foreach ($updates as $upd) {
            $id = intval($upd['id'] ?? 0);
            if (!$id) continue;

            // Find current details
            $this->db->query("SELECT required_qty, delivery_id FROM delivery_picking_items WHERE id = :id");
            $this->db->bind(':id', $id);
            $item = $this->db->single();
            if ($item) {
                $affectedDeliveries[] = intval($item->delivery_id);
            } else {
                continue;
            }

            if (isset($upd['final_loaded_qty'])) {
                // Final Loading Update
                $finalLoadedQty = floatval($upd['final_loaded_qty']);
                $isVerified = intval($upd['is_verified'] ?? 0);
                $userId = intval($upd['user_id'] ?? 0);
                $variance = $finalLoadedQty - floatval($item->required_qty);

                $this->db->query("
                    UPDATE delivery_picking_items 
                    SET final_loaded_qty = :final_loaded_qty, 
                        is_verified = :is_verified, 
                        variance = :variance, 
                        verified_at = NOW(), 
                        verified_by = :verified_by
                    WHERE id = :id
                ");
                $this->db->bind(':final_loaded_qty', $finalLoadedQty);
                $this->db->bind(':is_verified', $isVerified);
                $this->db->bind(':variance', $variance);
                $this->db->bind(':verified_by', $userId ? $userId : null);
                $this->db->bind(':id', $id);
                $this->db->execute();
            } else {
                // Pre-Loading Update (Warehouse Pick)
                $loadedQty = floatval($upd['loaded_qty'] ?? 0);
                $isPicked = intval($upd['is_picked'] ?? 0);
                $updatedAt = $upd['updated_at'] ?? date('Y-m-d H:i:s');

                $this->db->query("
                    UPDATE delivery_picking_items 
                    SET loaded_qty = :loaded_qty, is_picked = :is_picked, updated_at = :updated_at 
                    WHERE id = :id
                ");
                $this->db->bind(':loaded_qty', $loadedQty);
                $this->db->bind(':is_picked', $isPicked);
                $this->db->bind(':updated_at', $updatedAt);
                $this->db->bind(':id', $id);
                $this->db->execute();
            }
        }

        // Update overall statuses for all affected loading sheets
        $affectedDeliveries = array_unique($affectedDeliveries);
        foreach ($affectedDeliveries as $delId) {
            $this->updateDeliveryStatus($delId);
        }

        echo json_encode(['success' => true, 'synced_count' => count($updates)]);
        exit;
    }

    // API: Complete the loading process and transition the route to 'Variance Adjustment'
    public function api_complete_loading() {
        $this->checkAuth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $deliveryId = intval($input['delivery_id'] ?? 0);

        if (!$deliveryId) {
            echo json_encode(['success' => false, 'error' => 'Delivery ID is required']);
            exit;
        }

        // Fetch delivery details to get route ID
        $this->db->query("SELECT rep_route_id, secondary_rep_route_id FROM deliveries WHERE id = :id");
        $this->db->bind(':id', $deliveryId);
        $delivery = $this->db->single();

        if (!$delivery) {
            echo json_encode(['success' => false, 'error' => 'Delivery not found']);
            exit;
        }

        $this->db->beginTransaction();
        try {
            // Update delivery status to Completed
            $this->db->query("UPDATE deliveries SET status = 'Completed' WHERE id = :id");
            $this->db->bind(':id', $deliveryId);
            $this->db->execute();

            // Mark all items as verified if they are not already verified
            $this->db->query("
                UPDATE delivery_picking_items 
                SET final_loaded_qty = COALESCE(final_loaded_qty, loaded_qty, required_qty),
                    is_verified = 1,
                    variance = COALESCE(final_loaded_qty, loaded_qty, required_qty) - required_qty
                WHERE delivery_id = :delivery_id AND is_verified = 0
            ");
            $this->db->bind(':delivery_id', $deliveryId);
            $this->db->execute();

            // Update route status to 'Variance Adjustment'
            $rids = [intval($delivery->rep_route_id)];
            if ($delivery->secondary_rep_route_id) {
                $rids[] = intval($delivery->secondary_rep_route_id);
            }
            $rids = $this->resolveAllBoundRouteIds($rids);
            $ridsStr = implode(',', $rids);

            $this->db->query("UPDATE rep_daily_routes SET status = 'Variance Adjustment' WHERE id IN ($ridsStr)");
            $this->db->execute();

            // Log activity
            $this->logPickingActivity('Loading Completed', 'Picking', "Completed loading process for delivery #{$deliveryId} from Curtiss Portal App. Route(s) moved to Variance Adjustment.", $delivery->rep_route_id);

            $this->db->commit();
            echo json_encode(['success' => true, 'message' => 'Loading completed. Route moved to Variance Audit.']);
        } catch (Exception $e) {
            $this->db->rollBack();
            echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
        }
        exit;
    }

    protected function logPickingActivity($action, $module, $desc, $refId = null) {
        try {
            $this->db->query("INSERT INTO audit_logs (user_id, action, module, description, reference_id, ip_address) 
                              VALUES (:uid, :action, :module, :desc, :ref, :ip)");
            $this->db->bind(':uid', null); // System/PWA action
            $this->db->bind(':action', $action);
            $this->db->bind(':module', $module);
            $this->db->bind(':desc', $desc);
            $this->db->bind(':ref', $refId);
            $this->db->bind(':ip', $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
            $this->db->execute();
        } catch (Exception $e) {}
    }

    public function api_substitute_product() {
        $this->checkAuth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $deliveryId = intval($input['delivery_id'] ?? 0);
        $originalItemId = intval($input['original_item_id'] ?? 0);
        $replacementItemId = intval($input['replacement_item_id'] ?? 0);
        $replacementQty = floatval($input['replacement_qty'] ?? 0);
        $userId = intval($input['user_id'] ?? 0);

        if (!$deliveryId || !$originalItemId || !$replacementItemId || $replacementQty <= 0) {
            echo json_encode(['success' => false, 'error' => 'Missing required fields or invalid quantity']);
            exit;
        }

        $this->db->query("SELECT * FROM items WHERE id = :id");
        $this->db->bind(':id', $originalItemId);
        $origItem = $this->db->single();

        if (!$origItem) {
            $this->db->query("SELECT item_name as name FROM delivery_picking_items WHERE delivery_id = :did AND item_id = :item_id LIMIT 1");
            $this->db->bind(':did', $deliveryId);
            $this->db->bind(':item_id', $originalItemId);
            $dpiItem = $this->db->single();
            if ($dpiItem) {
                $origItem = (object)[
                    'id' => $originalItemId,
                    'name' => $dpiItem->name,
                    'item_code' => 'DELETED'
                ];
            }
        }

        $this->db->query("SELECT * FROM items WHERE id = :id");
        $this->db->bind(':id', $replacementItemId);
        $replItem = $this->db->single();

        if (!$origItem || !$replItem) {
            echo json_encode([
                'success' => false,
                'error' => 'Original or replacement product not found. Original ID: ' . $originalItemId . ' (' . ($origItem ? 'Found' : 'Not Found') . '), Replacement ID: ' . $replacementItemId . ' (' . ($replItem ? 'Found' : 'Not Found') . ')'
            ]);
            exit;
        }

        $this->db->query("SELECT rep_route_id FROM deliveries WHERE id = :id");
        $this->db->bind(':id', $deliveryId);
        $del = $this->db->single();
        $routeId = $del ? intval($del->rep_route_id) : 0;

        $this->db->query("SELECT * FROM delivery_picking_items WHERE delivery_id = :did AND item_id = :item_id LIMIT 1");
        $this->db->bind(':did', $deliveryId);
        $this->db->bind(':item_id', $originalItemId);
        $pickingItem = $this->db->single();

        $requiredQty = $pickingItem ? floatval($pickingItem->required_qty) : 0;

        $this->db->beginTransaction();
        try {
            $this->db->query("INSERT INTO product_substitutions (delivery_id, route_id, original_item_id, replacement_item_id, required_qty, loaded_qty, user_id, status)
                              VALUES (:did, :rid, :oid, :rid2, :req_qty, :load_qty, :uid, 'Pending Bill Update')");
            $this->db->bind(':did', $deliveryId);
            $this->db->bind(':rid', $routeId);
            $this->db->bind(':oid', $originalItemId);
            $this->db->bind(':rid2', $replacementItemId);
            $this->db->bind(':req_qty', $requiredQty);
            $this->db->bind(':load_qty', $replacementQty);
            $this->db->bind(':uid', $userId ? $userId : null);
            $this->db->execute();

            if ($pickingItem) {
                $this->db->query("UPDATE delivery_picking_items 
                                  SET final_loaded_qty = 0, is_verified = 1, variance = -required_qty, verified_at = NOW(), verified_by = :uid
                                  WHERE id = :id");
                $this->db->bind(':uid', $userId ? $userId : null);
                $this->db->bind(':id', $pickingItem->id);
                $this->db->execute();
            }

            $this->db->query("SELECT * FROM delivery_picking_items WHERE delivery_id = :did AND item_id = :item_id LIMIT 1");
            $this->db->bind(':did', $deliveryId);
            $this->db->bind(':item_id', $replacementItemId);
            $replPicking = $this->db->single();

            if ($replPicking) {
                $newFinal = floatval($replPicking->final_loaded_qty) + $replacementQty;
                $newVariance = $newFinal - floatval($replPicking->required_qty);
                $this->db->query("UPDATE delivery_picking_items 
                                  SET final_loaded_qty = :final, is_verified = 1, variance = :var, verified_at = NOW(), verified_by = :uid
                                  WHERE id = :id");
                $this->db->bind(':final', $newFinal);
                $this->db->bind(':var', $newVariance);
                $this->db->bind(':uid', $userId ? $userId : null);
                $this->db->bind(':id', $replPicking->id);
                $this->db->execute();
            } else {
                $this->db->query("INSERT INTO delivery_picking_items (delivery_id, item_name, item_id, variation_option_id, required_qty, loaded_qty, final_loaded_qty, variance, is_picked, is_verified, verified_at, verified_by)
                                  VALUES (:did, :name, :item_id, NULL, 0, :qty, :qty, :qty, 1, 1, NOW(), :uid)");
                $this->db->bind(':did', $deliveryId);
                $this->db->bind(':name', $replItem->name);
                $this->db->bind(':item_id', $replacementItemId);
                $this->db->bind(':qty', $replacementQty);
                $this->db->bind(':uid', $userId ? $userId : null);
                $this->db->execute();
            }

            $this->updateDeliveryStatus($deliveryId);

            $this->db->commit();
            echo json_encode(['success' => true, 'message' => 'Product substitution recorded successfully.']);
        } catch (Exception $e) {
            $this->db->rollBack();
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    public function api_search_products() {
        $this->checkAuth();
        $q = trim($_GET['q'] ?? '');
        if (empty($q)) {
            echo json_encode(['success' => true, 'products' => []]);
            exit;
        }

        $this->db->query("SELECT id, name, item_code, price AS selling_price FROM items 
                          WHERE (name LIKE :q OR item_code LIKE :q) AND status != 'Inactive' LIMIT 20");
        $this->db->bind(':q', "%$q%");
        $products = $this->db->resultSet() ?: [];

        foreach ($products as $p) {
            $p->id = intval($p->id);
            $p->selling_price = floatval($p->selling_price);
        }

        echo json_encode(['success' => true, 'products' => $products]);
        exit;
    }
}
