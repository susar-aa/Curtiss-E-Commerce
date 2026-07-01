<?php
class CustomerController extends Controller {
    private $customerModel;
    private $coaModel;
    private $db;

    public function __construct() {
        if (!isset($_SESSION['user_id'])) { header('Location: ' . APP_URL . '/auth/login'); exit; }
        $this->customerModel = $this->model('Customer');
        $this->coaModel = $this->model('ChartOfAccount');
        $this->db = new Database();
    }

    public function index($id = null) {
        $customers = $this->customerModel->getAllCustomers();
        
        $selectedCustomer = null;
        $stats = null;
        $ledger = [];
        $invoices = [];
        $cheques = [];

        // Pre-fetch Accounts for the Payment Modal
        $accounts = $this->coaModel->getAccounts();
        $assets = array_filter($accounts, function($a) { return $a->account_type == 'Asset'; });
        $arAccount = null;
        foreach($assets as $acc) {
            if (strpos(strtolower($acc->account_name), 'receivable') !== false) {
                $arAccount = $acc; break;
            }
        }

        if ($id) {
            $selectedCustomer = $this->customerModel->getCustomerById($id);
            if ($selectedCustomer) {
                $stats = $this->customerModel->getCustomerStats($id);
                $ledger = $this->customerModel->getActivityLedger($id);
                $invoices = $this->customerModel->getCustomerInvoices($id, 5); // Limit to latest 5
                $cheques = $this->customerModel->getCustomerCheques($id, 5); // Limit to latest 5
            }
        }

        $mcaAreas = $this->customerModel->getMcaAreas();

        $data = [
            'title' => 'Customer Profile',
            'content_view' => 'customers/index',
            'customers' => $customers,
            'selected_customer' => $selectedCustomer,
            'stats' => $stats,
            'ledger' => $ledger,
            'invoices' => $invoices,
            'cheques' => $cheques,
            'assets' => $assets,
            'ar_account' => $arAccount,
            'mca_areas' => $mcaAreas,
            'error' => '',
            'success' => ''
        ];

        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
            if ($_POST['action'] == 'add_customer') {
                $mcaId = !empty($_POST['mca_id']) ? intval($_POST['mca_id']) : null;
                $territoryName = null;
                if ($mcaId) {
                    foreach ($mcaAreas as $area) {
                        if ($area->id == $mcaId) {
                            $territoryName = $area->name;
                            break;
                        }
                    }
                }

                $addData = [
                    'name' => trim($_POST['name'] ?? ''),
                    'email' => trim($_POST['email'] ?? ''),
                    'phone' => trim($_POST['phone'] ?? ''),
                    'whatsapp' => trim($_POST['whatsapp'] ?? ''),
                    'address' => trim($_POST['address'] ?? ''),
                    'lat' => !empty($_POST['latitude']) ? $_POST['latitude'] : null,
                    'lng' => !empty($_POST['longitude']) ? $_POST['longitude'] : null,
                    'mca_id' => $mcaId,
                    'territory' => $territoryName,
                    'credit_limit' => isset($_POST['credit_limit']) ? floatval($_POST['credit_limit']) : 0.00,
                    'opening_balance' => isset($_POST['opening_balance']) ? floatval($_POST['opening_balance']) : 0.00
                ];

                if (!empty($addData['name'])) {
                    if ($this->customerModel->addCustomer($addData)) {
                        $this->logActivity('Add Customer', 'Customer', "Registered new customer profile: {$addData['name']}");
                        header('Location: ' . APP_URL . '/customer/index?success=add'); exit;
                    } else { $data['error'] = 'Failed to create new customer profile.'; }
                } else { $data['error'] = 'Customer/Company name is required.'; }

            } elseif ($_POST['action'] == 'update_customer') {
                $mcaId = !empty($_POST['mca_id']) ? intval($_POST['mca_id']) : null;
                $territoryName = null;
                if ($mcaId) {
                    foreach ($mcaAreas as $area) {
                        if ($area->id == $mcaId) {
                            $territoryName = $area->name;
                            break;
                        }
                    }
                }

                $updateData = [
                    'id' => $_POST['customer_id'],
                    'name' => trim($_POST['name'] ?? ''),
                    'email' => trim($_POST['email'] ?? ''),
                    'phone' => trim($_POST['phone'] ?? ''),
                    'whatsapp' => trim($_POST['whatsapp'] ?? ''),
                    'address' => trim($_POST['address'] ?? ''),
                    'lat' => !empty($_POST['latitude']) ? $_POST['latitude'] : null,
                    'lng' => !empty($_POST['longitude']) ? $_POST['longitude'] : null,
                    'mca_id' => $mcaId,
                    'territory' => $territoryName,
                    'credit_limit' => isset($_POST['credit_limit']) ? floatval($_POST['credit_limit']) : 0.00,
                    'opening_balance' => isset($_POST['opening_balance']) ? floatval($_POST['opening_balance']) : 0.00
                ];

                if (!empty($updateData['name'])) {
                    if ($this->customerModel->updateCustomer($updateData)) {
                        $this->logActivity('Update Customer', 'Customer', "Updated profile details for Customer ID {$updateData['id']} ({$updateData['name']})");
                        header('Location: ' . APP_URL . '/customer/index/' . $updateData['id'] . '?success=1'); exit;
                    } else { $data['error'] = 'Failed to update customer details.'; }
                }

            }
        }

        if (isset($_GET['success'])) {
            if ($_GET['success'] == 'add') {
                $data['success'] = "New customer profile registered successfully!";
            } else {
                $data['success'] = "Customer profile updated successfully!";
            }
        }

        $this->view('layouts/main', $data);
    }

    public function api_add_customer() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Invalid Request']);
            exit;
        }

        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        if (empty($name)) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Customer name is required.']);
            exit;
        }

        $mcaId = !empty($_POST['mca_id']) ? intval($_POST['mca_id']) : null;
        $territoryName = null;
        if ($mcaId) {
            $mcaAreas = $this->customerModel->getMcaAreas();
            foreach ($mcaAreas as $area) {
                if ($area->id == $mcaId) {
                    $territoryName = $area->name;
                    break;
                }
            }
        }

        $addData = [
            'name' => $name,
            'email' => isset($_POST['email']) ? trim($_POST['email']) : '',
            'phone' => isset($_POST['phone']) ? trim($_POST['phone']) : '',
            'whatsapp' => isset($_POST['whatsapp']) ? trim($_POST['whatsapp']) : '',
            'address' => isset($_POST['address']) ? trim($_POST['address']) : '',
            'lat' => !empty($_POST['latitude']) ? $_POST['latitude'] : null,
            'lng' => !empty($_POST['longitude']) ? $_POST['longitude'] : null,
            'mca_id' => $mcaId,
            'territory' => $territoryName,
            'credit_limit' => isset($_POST['credit_limit']) ? floatval($_POST['credit_limit']) : 0.00,
            'opening_balance' => isset($_POST['opening_balance']) ? floatval($_POST['opening_balance']) : 0.00
        ];

        if ($this->customerModel->addCustomer($addData)) {
            $newId = $this->customerModel->getLastInsertId();
            $this->logActivity('Add Customer', 'Customer', "Registered new customer profile via AJAX: {$name}");
            
            $addData['id'] = $newId;
            $addData['outstanding'] = 0.00;
            $addData['mca'] = $territoryName ?? '';
            
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'customer' => $addData]);
            exit;
        } else {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Failed to insert customer record.']);
            exit;
        }
    }

    /**
     * Export all customers to a standard CSV file.
     */
    public function exportCSV() {
        if (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="customers_export_' . date('Y-m-d_His') . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        
        // Write UTF-8 BOM for Excel compatibility
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // Column headers matching standard customer attributes
        fputcsv($output, [
            'Name',
            'Email',
            'Phone',
            'WhatsApp',
            'Address',
            'Latitude',
            'Longitude',
            'Territory',
            'Opening Balance'
        ]);

        $customers = $this->customerModel->getAllCustomers();

        foreach ($customers as $c) {
            fputcsv($output, [
                $c->name,
                $c->email ?? '',
                $c->phone ?? '',
                $c->whatsapp ?? '',
                $c->address ?? '',
                $c->latitude ?? '',
                $c->longitude ?? '',
                $c->mca_name ?? '',
                $c->opening_balance ?? 0.00
            ]);
        }

        fclose($output);
        exit;
    }

    /**
     * Import customers from standard CSV file with self-healing territory matching.
     */
    public function importCSV() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . APP_URL . '/customer/index');
            exit;
        }

        $addedCount = 0;
        $updatedCount = 0;
        $errors = [];
        $successLogs = [];

        if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['csv_file']['tmp_name'];
            $handle = fopen($fileTmpPath, 'r');

            // Skip UTF-8 BOM if present
            $bom = fread($handle, 3);
            if ($bom !== "\xEF\xBB\xBF") {
                rewind($handle);
            }

            // Read header row
            $header = fgetcsv($handle, 1000, ",");
            if (!$header) {
                $_SESSION['customer_import_results'] = [
                    'added' => 0,
                    'updated' => 0,
                    'errors' => ['CSV file is empty or malformed.'],
                    'success_logs' => []
                ];
                header('Location: ' . APP_URL . '/customer/index');
                exit;
            }

            // Trim headers to avoid spacing issues
            $header = array_map('trim', $header);

            // Column Indexes Map (Case-Insensitive)
            $colMap = [];
            foreach ($header as $idx => $colName) {
                $colMap[strtolower($colName)] = $idx;
            }

            // Required validation: Name must exist in headers
            if (!isset($colMap['name'])) {
                $_SESSION['customer_import_results'] = [
                    'added' => 0,
                    'updated' => 0,
                    'errors' => ["CSV file is missing the required 'Name' column header."],
                    'success_logs' => []
                ];
                header('Location: ' . APP_URL . '/customer/index');
                exit;
            }

            // Map columns to CSV index
            $nameIdx      = $colMap['name'];
            $emailIdx     = $colMap['email'] ?? -1;
            $phoneIdx     = $colMap['phone'] ?? -1;
            $whatsappIdx  = $colMap['whatsapp'] ?? -1;
            $addressIdx   = $colMap['address'] ?? -1;
            $latIdx       = $colMap['latitude'] ?? ($colMap['lat'] ?? -1);
            $lngIdx       = $colMap['longitude'] ?? ($colMap['lng'] ?? -1);
            $territoryIdx = $colMap['territory'] ?? ($colMap['mca'] ?? ($colMap['route'] ?? -1));
            $openingBalIdx = $colMap['opening balance'] ?? ($colMap['opening_balance'] ?? ($colMap['opening'] ?? -1));

            // Load lookup maps to avoid N+1 queries during insertion
            $territoryMap = [];
            $this->db->query("SELECT id, name FROM mca_areas");
            foreach ($this->db->resultSet() as $r) {
                $territoryMap[strtolower(trim($r->name))] = $r->id;
            }

            $customerMap = [];
            $this->db->query("SELECT id, name, phone, email, whatsapp, address, latitude, longitude, mca_id, territory, opening_balance FROM customers");
            foreach ($this->db->resultSet() as $c) {
                $customerMap[strtolower(trim($c->name))] = $c;
            }

            try {
                $this->db->beginTransaction();
                $rowCount = 1;
                $logsToRecord = [];

                while (($row = fgetcsv($handle, 1000, ",")) !== false) {
                    $rowCount++;
                    
                    // Basic sanity check
                    if (count($row) < 1 || empty(trim($row[$nameIdx] ?? ''))) {
                        continue; 
                    }

                    $name          = trim($row[$nameIdx] ?? '');
                    $email         = $emailIdx     !== -1 ? trim($row[$emailIdx] ?? '') : '';
                    $phone         = $phoneIdx     !== -1 ? trim($row[$phoneIdx] ?? '') : '';
                    $whatsapp      = $whatsappIdx  !== -1 ? trim($row[$whatsappIdx] ?? '') : '';
                    $address       = $addressIdx   !== -1 ? trim($row[$addressIdx] ?? '') : '';
                    $latitude      = $latIdx       !== -1 ? trim($row[$latIdx] ?? '') : '';
                    $longitude     = $lngIdx       !== -1 ? trim($row[$lngIdx] ?? '') : '';
                    $territoryName = $territoryIdx !== -1 ? trim($row[$territoryIdx] ?? '') : '';
                    $openingBal    = $openingBalIdx !== -1 ? floatval(trim($row[$openingBalIdx] ?? 0.00)) : 0.00;

                    // Resolve territory (MCA area) on-the-fly
                    $mcaId = null;
                    if (!empty($territoryName)) {
                        $tKey = strtolower($territoryName);
                        if (isset($territoryMap[$tKey])) {
                            $mcaId = $territoryMap[$tKey];
                        } else {
                            // Find default main_area_id if available
                            $this->db->query("SELECT id FROM main_areas LIMIT 1");
                            $mainArea = $this->db->single();
                            $mainAreaId = $mainArea ? $mainArea->id : null;

                            // Insert new MCA area
                            $this->db->query("INSERT INTO mca_areas (name, main_area_id, start_lat, start_lng, end_lat, end_lng, budget_km, actual_route_km) 
                                              VALUES (:name, :mid, '0.000000', '0.000000', '0.000000', '0.000000', 0, 0)");
                            $this->db->bind(':name', $territoryName);
                            $this->db->bind(':mid', $mainAreaId);
                            
                            if ($this->db->execute()) {
                                $newMcaId = $this->db->lastInsertId();
                                $territoryMap[$tKey] = $newMcaId;
                                $mcaId = $newMcaId;
                                $successLogs[] = "Auto-created Territory '{$territoryName}'";
                            }
                        }
                    }

                    // Check if customer already exists by name (case-insensitive)
                    $custKey = strtolower($name);
                    $existingCustomer = $customerMap[$custKey] ?? null;

                    $customerData = [
                        'name' => $name,
                        'email' => !empty($email) ? $email : null,
                        'phone' => !empty($phone) ? $phone : null,
                        'whatsapp' => !empty($whatsapp) ? $whatsapp : null,
                        'address' => !empty($address) ? $address : null,
                        'lat' => !empty($latitude) ? $latitude : null,
                        'lng' => !empty($longitude) ? $longitude : null,
                        'mca_id' => $mcaId,
                        'territory' => !empty($territoryName) ? $territoryName : null,
                        'opening_balance' => $openingBal
                    ];

                    if ($existingCustomer) {
                        // Update existing customer
                        $customerData['id'] = $existingCustomer->id;
                        
                        // Compare fields to see if changes actually exist
                        $changesExist = false;
                        $oldValues = [];
                        $newValues = [];
                        $changes = [];

                        $fieldsToCompare = [
                            'name' => 'Name',
                            'email' => 'Email',
                            'phone' => 'Phone',
                            'whatsapp' => 'WhatsApp',
                            'address' => 'Address',
                            'latitude' => 'Latitude',
                            'longitude' => 'Longitude',
                            'mca_id' => 'Territory ID',
                            'territory' => 'Territory Name',
                            'opening_balance' => 'Opening Balance'
                        ];

                        foreach ($fieldsToCompare as $dbKey => $label) {
                            $oldVal = $existingCustomer->$dbKey ?? null;
                            $newVal = $customerData[$dbKey === 'latitude' ? 'lat' : ($dbKey === 'longitude' ? 'lng' : $dbKey)];

                            if (trim($oldVal ?? '') != trim($newVal ?? '')) {
                                $changesExist = true;
                                $oldValues[$dbKey] = $oldVal;
                                $newValues[$dbKey] = $newVal;
                                $changes[] = "$label changed from '" . ($oldVal ?: 'None') . "' to '" . ($newVal ?: 'None') . "'";
                            }
                        }

                        if ($this->customerModel->updateCustomer($customerData)) {
                            $updatedCount++;
                            if ($changesExist) {
                                $logsToRecord[] = [
                                    'action' => 'Update Customer',
                                    'description' => "Customer '{$name}' details updated via CSV import: " . implode(', ', $changes),
                                    'record_id' => $existingCustomer->id,
                                    'old_values' => $oldValues,
                                    'new_values' => $newValues
                                ];
                            }
                        } else {
                            $errors[] = "Row {$rowCount}: Failed to update customer record for '{$name}'.";
                        }
                    } else {
                        // Insert new customer
                        if ($this->customerModel->addCustomer($customerData)) {
                            $addedCount++;
                            $newCustomerId = $this->db->lastInsertId();
                            
                            $logsToRecord[] = [
                                'action' => 'Add Customer',
                                'description' => "Registered new customer profile '{$name}' via CSV import.",
                                'record_id' => $newCustomerId,
                                'old_values' => null,
                                'new_values' => $customerData
                            ];

                            // Add to cache map to prevent duplicate inserts if the same name appears twice in CSV
                            $newCustomerObj = (object)[
                                'id' => $newCustomerId,
                                'name' => $name,
                                'email' => $email,
                                'phone' => $phone,
                                'whatsapp' => $whatsapp,
                                'address' => $address,
                                'latitude' => $latitude,
                                'longitude' => $longitude,
                                'mca_id' => $mcaId,
                                'territory' => $territoryName,
                                'opening_balance' => $openingBal
                            ];
                            $customerMap[$custKey] = $newCustomerObj;
                        } else {
                            $errors[] = "Row {$rowCount}: Failed to insert new customer '{$name}'.";
                        }
                    }
                }

                $this->db->commit();

                // Safely log activities after transaction is committed to prevent implicit commit DDL queries inside transaction
                foreach ($logsToRecord as $log) {
                    $this->logActivity($log['action'], 'Customer', $log['description'], $log['record_id'], $log['old_values'], $log['new_values']);
                }
            } catch (Exception $e) {
                $this->db->rollBack();
                $errors[] = "Database Transaction Failure: " . $e->getMessage();
            }

            fclose($handle);
        } else {
            $errors[] = "Could not read uploaded CSV file.";
        }

        // Store outcomes in session
        $_SESSION['customer_import_results'] = [
            'added' => $addedCount,
            'updated' => $updatedCount,
            'errors' => $errors,
            'success_logs' => $successLogs
        ];

        header('Location: ' . APP_URL . '/customer/index');
        exit;
    }

    public function delete($id) {
        $this->checkPermission('customer', 'delete');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
            exit;
        }

        header('Content-Type: application/json');
        
        $password = $_POST['password'] ?? '';
        $username = $_SESSION['username'] ?? '';

        $userModel = $this->model('User');
        $user = $userModel->login($username, $password);

        if ($user) {
            $customer = $this->customerModel->getCustomerById($id);
            if (!$customer) {
                echo json_encode(['success' => false, 'error' => 'Customer not found.']);
                exit;
            }

            try {
                if ($this->customerModel->deleteCustomer($id)) {
                    $this->logActivity(
                        'Customer Deleted',
                        'Customer',
                        "Customer '{$customer->name}' (ID: {$id}) deleted by '{$username}'.",
                        $id
                    );
                    echo json_encode(['success' => true, 'message' => 'Customer deleted successfully!']);
                    exit;
                } else {
                    echo json_encode(['success' => false, 'error' => 'Failed to delete customer. There might be active transactions associated with this customer.']);
                    exit;
                }
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => 'Failed to delete customer. This customer might have transaction records (Invoices/Payments/PDC) linked to their account.']);
                exit;
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid password. Please enter your correct logged-in password.']);
            exit;
        }
    }
}