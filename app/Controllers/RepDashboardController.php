<?php
class RepDashboardController extends Controller {
    private $userModel;
    private $itemModel;
    private $customerModel;
    private $trackingModel;
    private $discountModel;
    private $invoiceModel;
    private $db;

    public function __construct() {
        $this->userModel = $this->model('User');
        $this->itemModel = $this->model('Item');
        $this->customerModel = $this->model('Customer');
        $this->trackingModel = $this->model('RepTracking');
        $this->discountModel = $this->model('DiscountRule');
        $this->invoiceModel = $this->model('Invoice');
        $this->db = new Database();
    }

    public function api_login() {
        header('Content-Type: application/json');
        
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        
        if (!$data || !isset($data['username']) || !isset($data['password'])) {
            echo json_encode(['success' => false, 'message' => 'Missing username or password.']);
            exit;
        }
        
        $username = trim($data['username']);
        $password = trim($data['password']);
        
        $user = $this->userModel->login($username, $password);
        if ($user) {
            // Check if user has representative role (either directly in users.role or via user_roles table)
            $isRep = (strtolower($user->role) === 'rep' || strpos(strtolower($user->role), 'rep') !== false);
            if (!$isRep) {
                $userRoles = $this->userModel->getUserRoles($user->id);
                foreach ($userRoles as $r) {
                    if (strpos(strtolower($r->name), 'rep') !== false) {
                        $isRep = true;
                        break;
                    }
                }
            }
            
            if (!$isRep) {
                echo json_encode(['success' => false, 'message' => 'Access denied: not a representative account.']);
                exit;
            }
            
            // Get employee first and last name if possible
            $this->db->query("SELECT first_name, last_name FROM employees WHERE id = :id");
            $this->db->bind(':id', $user->employee_id);
            $emp = $this->db->single();
            
            $firstName = $emp ? $emp->first_name : $user->username;
            $lastName = $emp ? $emp->last_name : '';
            
            echo json_encode([
                'success' => true,
                'user' => [
                    'id' => intval($user->id),
                    'employee_id' => intval($user->employee_id),
                    'first_name' => $firstName,
                    'last_name' => $lastName
                ]
            ]);
            exit;
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid username or password.']);
            exit;
        }
    }

    public function sync_pull() {
        header('Content-Type: application/json');
        try {
            $userId = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
            if ($userId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid user ID.']);
                exit;
            }

            // Validate that user exists and is active
            $this->db->query("SELECT id, status FROM users WHERE id = :id");
            $this->db->bind(':id', $userId);
            $userRow = $this->db->single();
            if (!$userRow) {
                echo json_encode(['success' => false, 'unauthorized' => true, 'message' => 'Unauthorized: User ID does not exist on server.']);
                exit;
            }
            if (isset($userRow->status) && strtolower($userRow->status) !== 'active') {
                echo json_encode(['success' => false, 'unauthorized' => true, 'message' => 'Unauthorized: User account is blocked/inactive.']);
                exit;
            }

            $lastSync = isset($_GET['last_sync']) ? trim($_GET['last_sync']) : (isset($_GET['last_sync_timestamp']) ? trim($_GET['last_sync_timestamp']) : '');

            static $columnsCache = [];
            $columnExists = function($table, $column) use (&$columnsCache) {
                $table = strtolower($table);
                $column = strtolower($column);
                if (!isset($columnsCache[$table])) {
                    try {
                        $this->db->query("SHOW COLUMNS FROM `$table`");
                        $cols = $this->db->resultSet() ?: [];
                        $columnsCache[$table] = [];
                        foreach ($cols as $col) {
                            $field = is_object($col) ? ($col->Field ?? $col->field ?? '') : ($col['Field'] ?? $col['field'] ?? '');
                            if ($field) {
                                $columnsCache[$table][strtolower($field)] = true;
                            }
                        }
                    } catch (Exception $e) {
                        $columnsCache[$table] = [];
                    }
                }
                return isset($columnsCache[$table][$column]);
            };

            $getDeltaFilter = function($table, $lastSync, $hasWhere = false, $alias = null) use ($columnExists) {
                if (empty($lastSync)) {
                    return '';
                }
                $col = null;
                if ($columnExists($table, 'updated_at')) {
                    $col = 'updated_at';
                } elseif ($columnExists($table, 'created_at')) {
                    $col = 'created_at';
                }
                if ($col) {
                    $target = $alias ? $alias : $table;
                    return ($hasWhere ? " AND " : " WHERE ") . "`$target`.`$col` > :last_sync";
                }
                return '';
            };
            
            // 1. Get products (items)
            $items = $this->itemModel->getItemsDelta($lastSync);
            $productsJson = [];
            foreach ($items as $item) {
                $wholesalePrice = floatval($item->wholesale_price ?? 0);
                if ($wholesalePrice <= 0) {
                    $wholesalePrice = floatval($item->selling_price ?? 0);
                }
                $productsJson[] = [
                    'id' => intval($item->id),
                    'name' => $item->name,
                    'category_name' => $item->category_name ?? 'General',
                    'selling_price' => floatval($item->selling_price ?? 0),
                    'wholesale_price' => $wholesalePrice,
                    'cost_price' => floatval($item->cost_price ?? 0),
                    'qty' => intval($item->qty ?? $item->quantity_on_hand ?? 0),
                    'quantity_reserved' => intval($item->quantity_reserved),
                    'image_path' => $item->image_path ?? '',
                    'sku' => $item->item_code ?? $item->sku ?? '',
                    'sample_code' => $item->sample_code ?? '',
                    'variations_json' => $item->variations_json ?? '',
                    'brand' => $item->brand ?? '',
                    'description' => $item->description ?? '',
                    'status' => $item->status ?? 'active'
                ];
            }
            
            // 2. Get categories
            $this->db->query("SELECT id, name, status FROM item_categories ORDER BY name ASC");
            $cats = $this->db->resultSet() ?: [];
            $categoriesJson = [];
            foreach ($cats as $cat) {
                $categoriesJson[] = [
                    'id' => intval($cat->id),
                    'name' => $cat->name,
                    'status' => $cat->status ?? 'active'
                ];
            }
            
            // 3. Get customers
            $deltaCust = $getDeltaFilter('customers', $lastSync, true, 'c');
            $this->db->query("
                SELECT c.id, c.name, c.phone, c.whatsapp, c.address, c.territory, c.latitude, c.longitude, c.mca_id, m.name as mca_name, c.updated_at,
                       c.email, c.credit_limit, c.customer_type, c.notes, c.status,
                       ((SELECT COALESCE(SUM(total_amount - COALESCE(CASE WHEN global_discount_type = '%' THEN (total_amount * global_discount_val / 100) ELSE global_discount_val END, 0) + COALESCE(tax_amount, 0)), 0) FROM invoices WHERE customer_id = c.id AND status != 'Voided') 
                       - 
                       (SELECT COALESCE(SUM(amount), 0) FROM customer_payments WHERE customer_id = c.id AND status = 'Active') 
                       - 
                       (SELECT COALESCE(SUM(total_amount), 0) FROM credit_notes WHERE customer_id = c.id)) 
                       AS balance
                FROM customers c 
                LEFT JOIN mca_areas m ON c.mca_id = m.id
                WHERE 1=1 $deltaCust
                ORDER BY c.name ASC
            ");
            if (!empty($deltaCust)) {
                $this->db->bind(':last_sync', $lastSync);
            }
            $customers = $this->db->resultSet() ?: [];
            $customersJson = [];
            foreach ($customers as $c) {
                $customersJson[] = [
                    'id' => intval($c->id),
                    'name' => $c->name,
                    'phone' => $c->phone ?? '',
                    'whatsapp' => $c->whatsapp ?? '',
                    'address' => $c->address ?? '',
                    'territory' => $c->territory ?? '',
                    'latitude' => floatval($c->latitude ?? 0.0),
                    'longitude' => floatval($c->longitude ?? 0.0),
                    'outstanding' => floatval($c->balance ?? 0.0),
                    'mca_id' => intval($c->mca_id ?? 0),
                    'mca_name' => $c->mca_name ?? '',
                    'email' => $c->email ?? '',
                    'credit_limit' => floatval($c->credit_limit ?? 0.00),
                    'customer_type' => $c->customer_type ?? 'Standard',
                    'notes' => $c->notes ?? '',
                    'status' => $c->status ?? 'active',
                    'updated_at' => $c->updated_at ?? ''
                ];
            }
            
            // 4. Get server routes (territories) - ALWAYS FULL LIST
            $this->db->query("SELECT id, name, main_area_id, status FROM mca_areas ORDER BY name ASC");
            $routes = $this->db->resultSet() ?: [];
            $routesJson = [];
            foreach ($routes as $r) {
                $routesJson[] = [
                    'id' => intval($r->id),
                    'name' => $r->name,
                    'main_area_id' => intval($r->main_area_id ?? 0),
                    'status' => $r->status ?? 'active'
                ];
            }
            
            // 5. Get representatives - ALWAYS FULL LIST (Only active ones)
            $this->db->query("SELECT u.id, u.username, u.password_hash, u.employee_id, e.first_name, e.last_name 
                        FROM users u 
                        LEFT JOIN employees e ON u.employee_id = e.id 
                        WHERE u.role = 'rep' AND (u.status IS NULL OR u.status = 'Active')");
            $reps = $this->db->resultSet() ?: [];
            $repsJson = [];
            foreach ($reps as $rep) {
                $repsJson[] = [
                    'id' => intval($rep->id),
                    'username' => $rep->username,
                    'password_hash' => $rep->password_hash,
                    'employee_id' => intval($rep->employee_id),
                    'first_name' => $rep->first_name ?? $rep->username,
                    'last_name' => $rep->last_name ?? ''
                ];
            }
            
            // 6. Get payment terms - ALWAYS FULL LIST
            $this->db->query("SELECT id, name, days_due FROM payment_terms ORDER BY days_due ASC");
            $terms = $this->db->resultSet() ?: [];
            $termsJson = [];
            foreach ($terms as $t) {
                $termsJson[] = [
                    'id' => intval($t->id),
                    'name' => $t->name,
                    'days_due' => intval($t->days_due)
                ];
            }
            
            // 7. Get outstanding credit invoices for the customers - ALWAYS FULL LIST
            $this->db->query("SELECT i.id, i.invoice_number, i.customer_id, i.invoice_date, 
                               (i.total_amount - COALESCE(CASE WHEN i.global_discount_type = '%' THEN (i.total_amount * i.global_discount_val / 100) ELSE i.global_discount_val END, 0) + COALESCE(i.tax_amount, 0)) as true_grand_total,
                               c.name as customer_name, c.address as customer_address
                        FROM invoices i
                        JOIN customers c ON i.customer_id = c.id
                        WHERE (i.status = 'Unpaid' OR i.status = 'Partially Paid')
                        ORDER BY i.invoice_date ASC");
            $creditInvs = $this->db->resultSet() ?: [];
            $creditInvsJson = [];
            foreach ($creditInvs as $ci) {
                $creditInvsJson[] = [
                    'id' => intval($ci->id),
                    'invoice_number' => $ci->invoice_number,
                    'customer_id' => intval($ci->customer_id),
                    'invoice_date' => $ci->invoice_date,
                    'true_grand_total' => floatval($ci->true_grand_total),
                    'customer_name' => $ci->customer_name,
                    'customer_address' => $ci->customer_address
                ];
            }
        
        // 8. Get ongoing active route for this rep
        $this->db->query("SELECT * FROM rep_daily_routes WHERE user_id = :uid AND status = 'Active' ORDER BY id DESC LIMIT 1");
        $this->db->bind(':uid', $userId);
        $activeRoute = $this->db->single();
        $activeRouteJson = null;
        $activeRouteInvsJson = [];
        $activeRouteItemsJson = [];
        
        if ($activeRoute) {
            $activeRouteJson = [
                'id' => intval($activeRoute->id),
                'route_name' => $activeRoute->route_name,
                'start_meter' => floatval($activeRoute->start_meter),
                'start_time' => $activeRoute->start_time,
                'start_lat' => floatval($activeRoute->start_lat),
                'start_lng' => floatval($activeRoute->start_lng),
                'status' => $activeRoute->status
            ];
            
            // Invoices on this route
            $this->db->query("SELECT * FROM invoices WHERE rep_route_id = :rid AND status != 'Voided'");
            $this->db->bind(':rid', $activeRoute->id);
            $invs = $this->db->resultSet() ?: [];
            foreach ($invs as $inv) {
                $activeRouteInvsJson[] = [
                    'id' => intval($inv->id),
                    'invoice_number' => $inv->invoice_number,
                    'customer_id' => intval($inv->customer_id),
                    'invoice_date' => $inv->invoice_date,
                    'due_date' => $inv->due_date,
                    'payment_term_id' => $inv->payment_term_id ? intval($inv->payment_term_id) : 0,
                    'total_amount' => floatval($inv->total_amount),
                    'global_discount_val' => floatval($inv->global_discount_val),
                    'tax_amount' => floatval($inv->tax_amount),
                    'global_discount_type' => $inv->global_discount_type
                ];
                
                // Invoice items
                $this->db->query("SELECT * FROM invoice_items WHERE invoice_id = :iid");
                $this->db->bind(':iid', $inv->id);
                $items = $this->db->resultSet() ?: [];
                foreach ($items as $item) {
                    $activeRouteItemsJson[] = [
                        'invoice_id' => intval($item->invoice_id),
                        'item_id' => intval($item->item_id),
                        'description' => $item->description,
                        'quantity' => intval($item->quantity),
                        'unit_price' => floatval($item->unit_price),
                        'discount_value' => floatval($item->discount_value),
                        'total' => floatval($item->total)
                    ];
                }
            }
        }
        
        // 9. Fetch active discount rules and tiers
        $activeRules = $this->discountModel->getActiveRules();
        $discountRulesJson = [];
        foreach ($activeRules as $rule) {
            $tiersJson = [];
            foreach ($rule->tiers as $t) {
                $tiersJson[] = [
                    'id' => intval($t->id),
                    'rule_id' => intval($t->rule_id),
                    'min_threshold' => floatval($t->min_threshold),
                    'max_threshold' => $t->max_threshold !== null ? floatval($t->max_threshold) : null,
                    'reward_val' => floatval($t->reward_val)
                ];
            }
            $discountRulesJson[] = [
                'id' => intval($rule->id),
                'name' => $rule->name,
                'rule_type' => $rule->rule_type,
                'target_item_id' => $rule->target_item_id !== null ? intval($rule->target_item_id) : null,
                'status' => $rule->status,
                'tiers' => $tiersJson
            ];
        }

            echo json_encode([
                'success' => true,
                'products' => $productsJson,
                'categories' => $categoriesJson,
                'customers' => $customersJson,
                'routes' => $routesJson,
                'reps' => $repsJson,
                'payment_terms' => $termsJson,
                'credit_invoices' => $creditInvsJson,
                'active_route' => $activeRouteJson,
                'active_route_invoices' => $activeRouteInvsJson,
                'active_route_invoice_items' => $activeRouteItemsJson,
                'discount_rules' => $discountRulesJson
            ]);
            exit;
        } catch (Throwable $e) {
            http_response_code(500);
            
            $schemaDebug = [];
            try {
                $tables = ['invoices', 'customer_payments', 'credit_notes', 'customers', 'item_categories', 'mca_areas'];
                foreach ($tables as $tbl) {
                    $this->db->query("DESCRIBE `$tbl`");
                    $rows = $this->db->resultSet() ?: [];
                    $cols = [];
                    foreach ($rows as $r) {
                        $cols[] = $r->Field . ' (' . $r->Type . ')';
                    }
                    $schemaDebug[$tbl] = $cols;
                }
            } catch (Throwable $dbEx) {
                $schemaDebug['error'] = $dbEx->getMessage();
            }

            echo json_encode([
                'success' => false,
                'message' => 'Internal server error during pull sync: ' . $e->getMessage(),
                'schema' => $schemaDebug,
                'trace' => $e->getTraceAsString()
            ]);
            exit;
        }
    }

    public function sync_push() {
        header('Content-Type: application/json');
        
        $json = file_get_contents('php://input');
        $payload = json_decode($json, true);
        
        if (!$payload) {
            echo json_encode(['success' => false, 'message' => 'Invalid JSON payload.']);
            exit;
        }

        $userId = isset($payload['user_id']) ? intval($payload['user_id']) : 0;
        if ($userId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Missing or invalid user ID.']);
            exit;
        }

        // Validate that user exists in database and is active
        $this->db->query("SELECT id, status FROM users WHERE id = :id");
        $this->db->bind(':id', $userId);
        $userRow = $this->db->single();
        if (!$userRow) {
            echo json_encode(['success' => false, 'unauthorized' => true, 'message' => 'Unauthorized: User ID does not exist on server.']);
            exit;
        }
        if (isset($userRow->status) && strtolower($userRow->status) !== 'active') {
            echo json_encode(['success' => false, 'unauthorized' => true, 'message' => 'Unauthorized: User account is blocked/inactive.']);
            exit;
        }

        // Set session user ID temporarily for audit log tracking
        $_SESSION['user_id'] = $userId;

        $mappings = [
            'customers' => [],
            'routes' => [],
            'invoices' => []
        ];

        try {
            // 1. Process Customers
            if (isset($payload['customers']) && is_array($payload['customers'])) {
                foreach ($payload['customers'] as $c) {
                    $localId = intval($c['local_id']);
                    $serverId = isset($c['server_id']) ? intval($c['server_id']) : 0;
                    
                    if ($serverId > 0) {
                        // This is an update to an existing customer
                        $mcaId = null;
                        $territory = $c['territory'] ?? null;
                        $this->db->query("SELECT mca_id, territory FROM customers WHERE id = :id");
                        $this->db->bind(':id', $serverId);
                        $existingInfo = $this->db->single();
                        if ($existingInfo) {
                            $mcaId = $existingInfo->mca_id;
                            if (!$territory) {
                                $territory = $existingInfo->territory;
                            }
                        }

                        $this->customerModel->updateCustomer([
                            'id' => $serverId,
                            'name' => $c['name'],
                            'email' => $c['email'] ?? null,
                            'phone' => $c['phone'] ?? null,
                            'whatsapp' => $c['whatsapp'] ?? null,
                            'address' => $c['address'] ?? null,
                            'lat' => $c['latitude'] ?? null,
                            'lng' => $c['longitude'] ?? null,
                            'mca_id' => $mcaId,
                            'territory' => $territory,
                            'credit_limit' => isset($c['credit_limit']) ? floatval($c['credit_limit']) : 0.00,
                            'customer_type' => $c['customer_type'] ?? 'Standard',
                            'notes' => $c['notes'] ?? null,
                            'uuid' => $c['uuid'] ?? null
                        ]);
                        $this->logActivity('Update Customer', 'Customer', "Updated customer profile via mobile sync: {$c['name']}", $serverId);
                    } else {
                        // Check if customer with same UUID or same name and phone already exists
                        $existingCust = null;
                        if (!empty($c['uuid'])) {
                            $this->db->query("SELECT id FROM customers WHERE uuid = :uuid LIMIT 1");
                            $this->db->bind(':uuid', $c['uuid']);
                            $existingCust = $this->db->single();
                        }
                        if (!$existingCust) {
                            $this->db->query("SELECT id FROM customers WHERE name = :name AND phone = :phone LIMIT 1");
                            $this->db->bind(':name', $c['name']);
                            $this->db->bind(':phone', $c['phone'] ?? null);
                            $existingCust = $this->db->single();
                        }

                        if ($existingCust) {
                            $serverId = $existingCust->id;
                            $mcaId = null;
                            $territory = $c['territory'] ?? null;
                            $this->db->query("SELECT mca_id, territory FROM customers WHERE id = :id");
                            $this->db->bind(':id', $serverId);
                            $existingInfo = $this->db->single();
                            if ($existingInfo) {
                                $mcaId = $existingInfo->mca_id;
                                if (!$territory) {
                                    $territory = $existingInfo->territory;
                                }
                            }

                            $this->customerModel->updateCustomer([
                                'id' => $serverId,
                                'name' => $c['name'],
                                'email' => $c['email'] ?? null,
                                'phone' => $c['phone'] ?? null,
                                'whatsapp' => $c['whatsapp'] ?? null,
                                'address' => $c['address'] ?? null,
                                'lat' => $c['latitude'] ?? null,
                                'lng' => $c['longitude'] ?? null,
                                'mca_id' => $mcaId,
                                'territory' => $territory,
                                'credit_limit' => isset($c['credit_limit']) ? floatval($c['credit_limit']) : 0.00,
                                'customer_type' => $c['customer_type'] ?? 'Standard',
                                'notes' => $c['notes'] ?? null,
                                'uuid' => $c['uuid'] ?? null
                            ]);
                            $this->logActivity('Update Customer', 'Customer', "Updated customer profile via mobile sync: {$c['name']}", $serverId);
                        } else {
                            $this->customerModel->addCustomer([
                                'name' => $c['name'],
                                'email' => $c['email'] ?? null,
                                'phone' => $c['phone'] ?? null,
                                'whatsapp' => $c['whatsapp'] ?? null,
                                'address' => $c['address'] ?? null,
                                'lat' => $c['latitude'] ?? null,
                                'lng' => $c['longitude'] ?? null,
                                'mca_id' => null,
                                'territory' => $c['territory'] ?? null,
                                'credit_limit' => isset($c['credit_limit']) ? floatval($c['credit_limit']) : 0.00,
                                'customer_type' => $c['customer_type'] ?? 'Standard',
                                'notes' => $c['notes'] ?? null,
                                'uuid' => $c['uuid'] ?? null
                            ]);
                            $serverId = $this->customerModel->getLastInsertId();
                            $this->logActivity('Add Customer', 'Customer', "Registered new customer profile via mobile sync: {$c['name']}", $serverId);
                        }
                    }
                    
                    $mappings['customers'][] = [
                        'local_id' => $localId,
                        'server_id' => intval($serverId)
                    ];
                }
            }

            // 2. Process Routes
            if (isset($payload['routes']) && is_array($payload['routes'])) {
                foreach ($payload['routes'] as $r) {
                    $localId = intval($r['local_id']);
                    
                    // Check if a route with the same UUID or user_id, route_name and start_time already exists to prevent duplicate
                    $existingRoute = null;
                    if (!empty($r['uuid'])) {
                        $this->db->query("SELECT id FROM rep_daily_routes WHERE uuid = :uuid LIMIT 1");
                        $this->db->bind(':uuid', $r['uuid']);
                        $existingRoute = $this->db->single();
                    }
                    if (!$existingRoute) {
                        $this->db->query("SELECT id FROM rep_daily_routes WHERE user_id = :user_id AND route_name = :route_name AND start_time = :start_time LIMIT 1");
                        $this->db->bind(':user_id', $userId);
                        $this->db->bind(':route_name', $r['route_name']);
                        $this->db->bind(':start_time', $r['start_time']);
                        $existingRoute = $this->db->single();
                    }

                    if ($existingRoute) {
                        $serverId = $existingRoute->id;
                        $this->db->query("UPDATE rep_daily_routes SET 
                                          end_meter = :end_meter, 
                                          end_time = :end_time, 
                                          end_lat = :end_lat, 
                                          end_lng = :end_lng, 
                                          status = :status,
                                          uuid = :uuid 
                                          WHERE id = :id");
                        $this->db->bind(':end_meter', isset($r['end_meter']) && $r['end_meter'] !== '' ? $r['end_meter'] : null);
                        $this->db->bind(':end_time', $r['end_time'] ?? null);
                        $this->db->bind(':end_lat', $r['end_lat'] ?? null);
                        $this->db->bind(':end_lng', $r['end_lng'] ?? null);
                        $this->db->bind(':status', ($r['status'] ?? 'Active') === 'Completed' ? 'Pending GL' : ($r['status'] ?? 'Active'));
                        $this->db->bind(':uuid', $r['uuid'] ?? null);
                        $this->db->bind(':id', $serverId);
                        $this->db->execute();
                        $this->logActivity('Update Route', 'RepTracking', "Finalized and closed daily representative route via mobile sync: {$r['route_name']}", $serverId);
                    } else {
                        $this->db->query("INSERT INTO rep_daily_routes (user_id, route_name, start_meter, start_time, start_lat, start_lng, end_meter, end_time, end_lat, end_lng, status, uuid) 
                                          VALUES (:user_id, :route_name, :start_meter, :start_time, :start_lat, :start_lng, :end_meter, :end_time, :end_lat, :end_lng, :status, :uuid)");
                        $this->db->bind(':user_id', $userId);
                        $this->db->bind(':route_name', $r['route_name']);
                        $this->db->bind(':start_meter', $r['start_meter']);
                        $this->db->bind(':start_time', $r['start_time']);
                        $this->db->bind(':start_lat', $r['start_lat']);
                        $this->db->bind(':start_lng', $r['start_lng']);
                        $this->db->bind(':end_meter', isset($r['end_meter']) && $r['end_meter'] !== '' ? $r['end_meter'] : null);
                        $this->db->bind(':end_time', $r['end_time'] ?? null);
                        $this->db->bind(':end_lat', $r['end_lat'] ?? null);
                        $this->db->bind(':end_lng', $r['end_lng'] ?? null);
                        $this->db->bind(':status', ($r['status'] ?? 'Active') === 'Completed' ? 'Pending GL' : ($r['status'] ?? 'Active'));
                        $this->db->bind(':uuid', $r['uuid'] ?? null);
                        $this->db->execute();
                        $serverId = $this->db->lastInsertId();
                        $this->logActivity('Create Route', 'RepTracking', "Created daily representative route via mobile sync: {$r['route_name']}", $serverId);
                    }

                    $mappings['routes'][] = [
                        'local_id' => $localId,
                        'server_id' => intval($serverId)
                    ];
                }
            }

            // Helpers to find mapped customer/route server IDs
            $getCustomerServerId = function($localCustId) use (&$mappings) {
                foreach ($mappings['customers'] as $map) {
                    if ($map['local_id'] == $localCustId) return $map['server_id'];
                }
                return $localCustId;
            };

            $getRouteServerId = function($localRouteId) use (&$mappings) {
                foreach ($mappings['routes'] as $map) {
                    if ($map['local_id'] == $localRouteId) return $map['server_id'];
                }
                return $localRouteId;
            };

            // Resolve AR and Revenue accounting accounts
            $this->db->query("SELECT id FROM chart_of_accounts WHERE account_type = 'Asset' AND (account_name LIKE '%Receivable%' OR account_code LIKE '1100%' OR account_code = '1200') LIMIT 1");
            $arRow = $this->db->single();
            $arAccountId = $arRow ? $arRow->id : null;

            $this->db->query("SELECT id FROM chart_of_accounts WHERE account_type = 'Revenue' AND (account_name LIKE '%Sales%' OR account_name LIKE '%Revenue%' OR account_code LIKE '4000%') LIMIT 1");
            $revRow = $this->db->single();
            $revenueAccountId = $revRow ? $revRow->id : null;

            if (!$arAccountId || !$revenueAccountId) {
                throw new Exception("Accounting accounts could not be resolved.");
            }

            // 3. Process Invoices
            if (isset($payload['invoices']) && is_array($payload['invoices'])) {
                foreach ($payload['invoices'] as $inv) {
                    $localId = intval($inv['local_id']);
                    $custServerId = $getCustomerServerId(intval($inv['customer_id']));
                    
                    // Validate customer ID on the server, fallback if not found
                    $this->db->query("SELECT id FROM customers WHERE id = :id");
                    $this->db->bind(':id', $custServerId);
                    $custRow = $this->db->single();
                    if (!$custRow) {
                        $this->db->query("SELECT id FROM customers LIMIT 1");
                        $firstCust = $this->db->single();
                        $custServerId = $firstCust ? intval($firstCust->id) : 1;
                    }
                    
                    $localRouteId = intval($inv['local_route_id'] ?? 0);
                    $serverRouteIdFromApp = intval($inv['server_route_id'] ?? 0);
                    $routeUuid = $inv['route_uuid'] ?? '';
                    $routeServerId = 0;

                    if ($serverRouteIdFromApp > 0) {
                        $routeServerId = $serverRouteIdFromApp;
                    }
                    if ($routeServerId <= 0 && !empty($routeUuid)) {
                        $this->db->query("SELECT id FROM rep_daily_routes WHERE uuid = :uuid LIMIT 1");
                        $this->db->bind(':uuid', $routeUuid);
                        $rRow = $this->db->single();
                        if ($rRow) {
                            $routeServerId = intval($rRow->id);
                        }
                    }
                    if ($routeServerId <= 0) {
                        $routeServerId = $getRouteServerId($localRouteId);
                    }
                    
                    // Generate new invoice number if sequence is used or keep what mobile generated
                    $invNo = $inv['invoice_number'];
                    
                    // Idempotency: Check if an invoice with the same uuid or invoice_number already exists
                    $existingInv = null;
                    if (!empty($inv['uuid'])) {
                        $this->db->query("SELECT id, invoice_number, invoice_date, stock_status FROM invoices WHERE uuid = :uuid LIMIT 1");
                        $this->db->bind(':uuid', $inv['uuid']);
                        $existingInv = $this->db->single();
                    }
                    if (!$existingInv) {
                        $this->db->query("SELECT id, invoice_number, invoice_date, stock_status FROM invoices WHERE invoice_number = :invoice_number LIMIT 1");
                        $this->db->bind(':invoice_number', $invNo);
                        $existingInv = $this->db->single();
                    }
                    if ($existingInv) {
                        $isReserved = (isset($existingInv->stock_status) && $existingInv->stock_status === 'reserved');
                        if ($isReserved) {
                            // Invoice exists but is not finalized, allow updating it
                            $itemsPayload = [];
                            if (isset($inv['items']) && is_array($inv['items'])) {
                                foreach ($inv['items'] as $item) {
                                    $prodId = intval($item['product_id']);
                                    $prodName = $item['product_name'];
                                    
                                    // Check if product ID exists on the server
                                    $this->db->query("SELECT id FROM items WHERE id = :id");
                                    $this->db->bind(':id', $prodId);
                                    $pRow = $this->db->single();
                                    if (!$pRow) {
                                        $this->db->query("SELECT id FROM items WHERE name = :name LIMIT 1");
                                        $this->db->bind(':name', $prodName);
                                        $pByName = $this->db->single();
                                        if ($pByName) {
                                            $prodId = intval($pByName->id);
                                        } else {
                                            $this->db->query("SELECT id FROM items LIMIT 1");
                                            $firstProd = $this->db->single();
                                            $prodId = $firstProd ? intval($firstProd->id) : 1;
                                        }
                                    }

                                    $itemsPayload[] = [
                                        'item_selection' => $prodId . '|0',
                                        'description' => $prodName,
                                        'quantity' => intval($item['quantity']),
                                        'unit_price' => floatval($item['unit_price']),
                                        'discount_value' => floatval($item['discount_val'] ?? 0.0),
                                        'discount_type' => 'Rs',
                                        'total' => floatval($item['total'])
                                    ];
                                }
                            }

                            $invoiceData = [
                                'customer_id' => $custServerId,
                                'invoice_number' => $invNo,
                                'uuid' => $inv['uuid'] ?? null,
                                'invoice_date' => $inv['invoice_date'],
                                'due_date' => $inv['due_date'],
                                'payment_term_id' => !empty($inv['payment_term_id']) ? intval($inv['payment_term_id']) : null,
                                'subtotal' => floatval($inv['subtotal']),
                                'global_discount_val' => floatval($inv['discount'] ?? 0.00),
                                'global_discount_type' => 'Rs',
                                'notes' => 'Updated via Mobile App Sync',
                                'rep_route_id' => $routeServerId ?: null,
                                'grand_total' => floatval($inv['grand_total'])
                            ];

                            $updateOk = $this->invoiceModel->updateInvoiceWithAccounting(
                                intval($existingInv->id),
                                $invoiceData,
                                $itemsPayload,
                                $arAccountId,
                                $revenueAccountId,
                                $userId
                            );

                            if ($updateOk) {
                                $this->logActivity('Update Invoice', 'Billing', "Updated Invoice {$invNo} via mobile sync", $existingInv->id);
                                
                                $this->db->query("SELECT invoice_date FROM invoices WHERE id = :id");
                                $this->db->bind(':id', $existingInv->id);
                                $invRow = $this->db->single();
                                $serverTime = $invRow ? $invRow->invoice_date : date('Y-m-d H:i:s');

                                $mappings['invoices'][] = [
                                    'local_id' => $localId,
                                    'server_id' => intval($existingInv->id),
                                    'invoice_number' => $invNo,
                                    'server_timestamp' => $serverTime
                                ];
                                continue;
                            } else {
                                $err = isset($_SESSION['invoice_error']) ? $_SESSION['invoice_error'] : 'Unknown edit error';
                                unset($_SESSION['invoice_error']);
                                throw new Exception("Invoice update failed for invoice number {$invNo}: " . $err);
                            }
                        } else {
                            // Already finalized, treat as synced and skip
                            $mappings['invoices'][] = [
                                'local_id' => $localId,
                                'server_id' => intval($existingInv->id),
                                'invoice_number' => $existingInv->invoice_number,
                                'server_timestamp' => $existingInv->invoice_date
                            ];
                            continue;
                        }
                    }

                    // Format invoice items for createInvoiceWithAccounting method
                    $itemsPayload = [];
                    if (isset($inv['items']) && is_array($inv['items'])) {
                        foreach ($inv['items'] as $item) {
                            $prodId = intval($item['product_id']);
                            $prodName = $item['product_name'];
                            
                            // Check if product ID exists on the server
                            $this->db->query("SELECT id FROM items WHERE id = :id");
                            $this->db->bind(':id', $prodId);
                            $pRow = $this->db->single();
                            if (!$pRow) {
                                // Try to look up by name
                                $this->db->query("SELECT id FROM items WHERE name = :name LIMIT 1");
                                $this->db->bind(':name', $prodName);
                                $pByName = $this->db->single();
                                if ($pByName) {
                                    $prodId = intval($pByName->id);
                                } else {
                                    // Fallback to first available product
                                    $this->db->query("SELECT id FROM items LIMIT 1");
                                    $firstProd = $this->db->single();
                                    $prodId = $firstProd ? intval($firstProd->id) : 1;
                                }
                            }

                            $itemsPayload[] = [
                                'item_selection' => $prodId . '|0', // format: "product_id|var_id"
                                'description' => $prodName,
                                'quantity' => intval($item['quantity']),
                                'unit_price' => floatval($item['unit_price']),
                                'discount_value' => floatval($item['discount_val'] ?? 0.0),
                                'discount_type' => 'Rs',
                                'total' => floatval($item['total'])
                            ];
                        }
                    }

                    $invoiceData = [
                        'customer_id' => $custServerId,
                        'invoice_number' => $invNo,
                        'uuid' => $inv['uuid'] ?? null,
                        'invoice_date' => $inv['invoice_date'],
                        'due_date' => $inv['due_date'],
                        'payment_term_id' => !empty($inv['payment_term_id']) ? intval($inv['payment_term_id']) : null,
                        'subtotal' => floatval($inv['subtotal']),
                        'global_discount_val' => floatval($inv['discount'] ?? 0.00),
                        'global_discount_type' => 'Rs', // default mobile discount type is Rs
                        'notes' => 'Created via Mobile App Sync',
                        'rep_route_id' => $routeServerId ?: null,
                        'grand_total' => floatval($inv['grand_total']),
                        'stock_status' => 'reserved' // Keep as reserved Sales Order until finalized
                    ];

                    $invoiceId = $this->invoiceModel->createInvoiceWithAccounting(
                        $invoiceData,
                        $itemsPayload,
                        $arAccountId,
                        $revenueAccountId,
                        $userId
                    );

                    if ($invoiceId) {
                        $this->logActivity('Create Invoice', 'Billing', "Created and posted Invoice {$invNo} for Customer ID {$custServerId} via mobile sync", $invoiceId);
                        
                        // If discount was applied, log this to system_audit_trail specifically
                        if ($invoiceData['global_discount_val'] > 0) {
                            $this->logActivity('Apply Discount', 'Billing', "Applied global discount Rs: " . number_format($invoiceData['global_discount_val'], 2) . " on Invoice {$invNo}", $invoiceId);
                        }
                        
                        // Log item-wise discount applications
                        foreach ($itemsPayload as $it) {
                            if ($it['discount_value'] > 0) {
                                $this->logActivity('Apply Discount', 'Billing', "Applied item-wise discount Rs: " . number_format($it['discount_value'], 2) . " on product in Invoice {$invNo}", $invoiceId);
                            }
                        }

                        $this->db->query("SELECT invoice_date FROM invoices WHERE id = :id");
                        $this->db->bind(':id', $invoiceId);
                        $invRow = $this->db->single();
                        $serverTime = $invRow ? $invRow->invoice_date : date('Y-m-d H:i:s');

                        $mappings['invoices'][] = [
                            'local_id' => $localId,
                            'server_id' => intval($invoiceId),
                            'invoice_number' => $invNo,
                            'server_timestamp' => $serverTime
                        ];
                    } else {
                        $err = isset($_SESSION['invoice_error']) ? $_SESSION['invoice_error'] : 'Unknown error during creation';
                        unset($_SESSION['invoice_error']);
                        throw new Exception("Invoice creation failed for invoice number {$invNo}: " . $err);
                    }
                }
            }

            // 4. Process Payments
            if (isset($payload['payments']) && is_array($payload['payments'])) {
                foreach ($payload['payments'] as $p) {
                    $localId = isset($p['local_id']) ? intval($p['local_id']) : 0;
                    $custServerId = $getCustomerServerId(intval($p['customer_id']));
                    
                    // Validate customer ID on the server, fallback if not found
                    $this->db->query("SELECT id FROM customers WHERE id = :id");
                    $this->db->bind(':id', $custServerId);
                    $custRow = $this->db->single();
                    if (!$custRow) {
                        $this->db->query("SELECT id FROM customers LIMIT 1");
                        $firstCust = $this->db->single();
                        $custServerId = $firstCust ? intval($firstCust->id) : 1;
                    }

                    $localRouteId = intval($p['local_route_id'] ?? 0);
                    $serverRouteIdFromApp = intval($p['server_route_id'] ?? 0);
                    $routeUuid = $p['route_uuid'] ?? '';
                    $routeServerId = 0;

                    if ($serverRouteIdFromApp > 0) {
                        $routeServerId = $serverRouteIdFromApp;
                    }
                    if ($routeServerId <= 0 && !empty($routeUuid)) {
                        $this->db->query("SELECT id FROM rep_daily_routes WHERE uuid = :uuid LIMIT 1");
                        $this->db->bind(':uuid', $routeUuid);
                        $rRow = $this->db->single();
                        if ($rRow) {
                            $routeServerId = intval($rRow->id);
                        }
                    }
                    if ($routeServerId <= 0) {
                        $routeServerId = $getRouteServerId($localRouteId);
                    }
                    
                    // Idempotency: Check if this payment was already synced via UUID or mobile_local_id and mobile_rep_id
                    $existingPmt = null;
                    if (!empty($p['uuid'])) {
                        $this->db->query("SELECT id FROM pending_collections WHERE uuid = :uuid LIMIT 1");
                        $this->db->bind(':uuid', $p['uuid']);
                        $existingPmt = $this->db->single();
                    }
                    if (!$existingPmt && $localId > 0) {
                        $this->db->query("SELECT id FROM pending_collections WHERE mobile_local_id = :mlid AND mobile_rep_id = :mrepid LIMIT 1");
                        $this->db->bind(':mlid', $localId);
                        $this->db->bind(':mrepid', $userId);
                        $existingPmt = $this->db->single();
                    }
                    if ($existingPmt) {
                        continue; // Already processed, skip creating duplicate
                    }

                    $this->db->query("INSERT INTO pending_collections (customer_id, route_id, payment_method, amount, bank_name, cheque_number, cheque_date, status, notes, mobile_local_id, mobile_rep_id, uuid) 
                                      VALUES (:customer_id, :route_id, :payment_method, :amount, :bank_name, :cheque_number, :cheque_date, 'Pending', 'Synced from mobile app', :mobile_local_id, :mobile_rep_id, :uuid)");
                    $this->db->bind(':customer_id', $custServerId);
                    $this->db->bind(':route_id', $routeServerId ?: null);
                    $this->db->bind(':payment_method', $p['payment_method']);
                    $this->db->bind(':amount', floatval($p['amount']));
                    $this->db->bind(':bank_name', $p['bank_name'] ?? null);
                    $this->db->bind(':cheque_number', $p['cheque_number'] ?? null);
                    $this->db->bind(':cheque_date', !empty($p['cheque_date']) ? $p['cheque_date'] : null);
                    $this->db->bind(':mobile_local_id', $localId > 0 ? $localId : null);
                    $this->db->bind(':mobile_rep_id', $userId);
                    $this->db->bind(':uuid', $p['uuid'] ?? null);
                    $this->db->execute();
                    
                    $this->logActivity('Record Collection', 'Billing', "Recorded payment collection Rs: " . number_format(floatval($p['amount']), 2) . " for Customer ID {$custServerId} via mobile sync");
                }
            }

            echo json_encode([
                'success' => true,
                'mappings' => $mappings
            ]);
            exit;

        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Sync push processing exception: ' . $e->getMessage()
            ]);
            exit;
        }
    }

    public function sync_verify() {
        header('Content-Type: application/json');
        
        $json = file_get_contents('php://input');
        $payload = json_decode($json, true);
        
        if (!$payload || !isset($payload['uuids']) || !is_array($payload['uuids'])) {
            echo json_encode(['success' => false, 'message' => 'Missing UUIDs list.']);
            exit;
        }

        $results = [];
        foreach ($payload['uuids'] as $item) {
            $uuid = $item['uuid'] ?? '';
            $type = $item['type'] ?? ''; // 'invoice', 'payment', 'route', 'customer'
            
            if (empty($uuid) || empty($type)) continue;

            $exists = false;
            $serverId = 0;

            try {
                if ($type === 'invoice') {
                    $this->db->query("SELECT id FROM invoices WHERE uuid = :uuid LIMIT 1");
                    $this->db->bind(':uuid', $uuid);
                    $row = $this->db->single();
                    if ($row) {
                        $exists = true;
                        $serverId = intval($row->id);
                    }
                } elseif ($type === 'payment') {
                    $this->db->query("SELECT id FROM pending_collections WHERE uuid = :uuid LIMIT 1");
                    $this->db->bind(':uuid', $uuid);
                    $row = $this->db->single();
                    if ($row) {
                        $exists = true;
                        $serverId = intval($row->id);
                    }
                } elseif ($type === 'route') {
                    $this->db->query("SELECT id FROM rep_daily_routes WHERE uuid = :uuid LIMIT 1");
                    $this->db->bind(':uuid', $uuid);
                    $row = $this->db->single();
                    if ($row) {
                        $exists = true;
                        $serverId = intval($row->id);
                    }
                } elseif ($type === 'customer') {
                    $this->db->query("SELECT id FROM customers WHERE uuid = :uuid LIMIT 1");
                    $this->db->bind(':uuid', $uuid);
                    $row = $this->db->single();
                    if ($row) {
                        $exists = true;
                        $serverId = intval($row->id);
                    }
                }
            } catch (Exception $e) {
                // query failed or table column doesn't exist
            }

            $results[] = [
                'uuid' => $uuid,
                'type' => $type,
                'verified' => $exists,
                'server_id' => $serverId
            ];
        }

        echo json_encode([
            'success' => true,
            'results' => $results
        ]);
        exit;
    }

    public function api_logout() {
        header('Content-Type: application/json');
        
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        
        $userId = isset($data['user_id']) ? intval($data['user_id']) : 0;
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (isset($_SESSION['username'])) {
            $this->logActivity('API Logout', 'Auth', "User '{$_SESSION['username']}' logged out via API.", $_SESSION['user_id'] ?? $userId);
        } else if ($userId > 0) {
            $this->logActivity('API Logout', 'Auth', "User ID {$userId} logged out via API.", $userId);
        }
        
        unset($_SESSION['user_id']);
        unset($_SESSION['username']);
        unset($_SESSION['role']);
        session_destroy();
        
        echo json_encode(['success' => true, 'message' => 'Logged out successfully on server.']);
        exit;
    }
}
