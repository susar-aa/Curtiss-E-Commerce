<?php
class PurchaseController extends Controller {
    private $vendorModel;
    private $poModel;
    private $itemModel;
    private $companyModel;

    public function __construct() {
        if (!isset($_SESSION['user_id'])) { header('Location: ' . APP_URL . '/auth/login'); exit; }
        $this->vendorModel = $this->model('Supplier');
        $this->poModel = $this->model('PurchaseOrder');
        $this->itemModel = $this->model('Item');
        $this->companyModel = $this->model('Company');
    }

    public function index() {
        $search = $_GET['search'] ?? '';
        $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;

        $filters = [
            'vendor_id' => $_GET['vendor_id'] ?? '',
            'status' => $_GET['status'] ?? ''
        ];

        // Handle Actions
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
            if ($_POST['action'] == 'delete_po') {
                if ($this->poModel->deletePO($_POST['po_id'])) {
                    header("Location: " . APP_URL . "/purchase?success=PO deleted successfully"); exit;
                }
            }
        }

        $totalPOs = $this->poModel->getTotalPOs($search, $filters);
        $pos = $this->poModel->getPOsPaginated($search, $limit, $offset, $filters);
        
        // Check if any POs contain unresolved MIX items
        foreach($pos as $po) {
            $po->has_mix = $this->poModel->hasMixItems($po->id);
        }
        
        $data = [
            'title' => 'Procurement Dashboard',
            'content_view' => 'purchases/index',
            'pos' => $pos,
            'vendors' => $this->vendorModel->getAllVendors(),
            'search' => $search,
            'filters' => $filters,
            'page' => $page,
            'total_pages' => ceil($totalPOs / $limit),
            'error' => $_GET['error'] ?? '',
            'success' => $_GET['success'] ?? '',
            'debug' => $_GET['debug'] ?? ''
        ];
        $this->view('layouts/main', $data);
    }

    // --- GRN MIX RESOLVER PIPELINE ---
    
    public function resolve_mix_grn($id = null) {
        if (!$id) { header('Location: ' . APP_URL . '/purchase'); exit; }
        
        $po = $this->poModel->getPOById($id);
        if (!$po || $po->status === 'Received') { die("Invalid or already received PO."); }
        
        $items = $this->poModel->getPOItems($id);
        
        foreach($items as $item) {
            if ($item->is_mix && $item->item_id) {
                $item->available_variations = $this->itemModel->getItemVariations($item->item_id);
            }
        }
        
        $data = [
            'title' => 'Resolve Variation Mix',
            'content_view' => 'purchases/resolve_mix',
            'po' => $po,
            'items' => $items
        ];
        $this->view('layouts/main', $data);
    }

    public function process_mix_grn() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $poId = $_POST['po_id'];
            $po = $this->poModel->getPOById($poId);
            $resolvedItems = [];
            
            $originalItems = $this->poModel->getPOItems($poId);
            
            foreach($originalItems as $orig) {
                if ($orig->is_mix) {
                    if (isset($_POST['resolve'][$orig->id])) {
                        $resolutions = $_POST['resolve'][$orig->id];
                        for ($i=0; $i < count($resolutions['var_opt_id']); $i++) {
                            $qty = floatval($resolutions['qty'][$i]);
                            if ($qty > 0) {
                                $resolvedItems[] = [
                                    'item_id' => $orig->item_id,
                                    'var_opt_id' => !empty($resolutions['var_opt_id'][$i]) ? $resolutions['var_opt_id'][$i] : null,
                                    'is_mix' => 0,
                                    'desc' => $resolutions['desc'][$i],
                                    'qty' => $qty,
                                    'price' => $orig->unit_price
                                ];
                            }
                        }
                    }
                } else {
                    $resolvedItems[] = [
                        'item_id' => $orig->item_id,
                        'var_opt_id' => $orig->item_variation_option_id,
                        'is_mix' => 0,
                        'desc' => $orig->description,
                        'qty' => $orig->quantity,
                        'price' => $orig->unit_price
                    ];
                }
            }

            // Save the exact variations back to the PO so GRN page pre-fills perfectly
            $poData = [
                'id' => $poId,
                'vendor_id' => $po->vendor_id,
                'po_date' => $po->po_date,
                'expected_date' => $po->expected_date,
                'notes' => $po->notes
            ];
            $this->poModel->updatePO($poData, $resolvedItems);

            // Forward safely to GRN creation page instead of instantly receiving stock
            header('Location: ' . APP_URL . '/grn/create?po_id=' . $poId);
            exit;
        }
    }

    // --- STANDARD PO PIPELINE ---

    public function show($id = null) {
        if (!$id) { header('Location: ' . APP_URL . '/purchase'); exit; }
        $po = $this->poModel->getPOById($id);
        if (!$po) { die("Purchase Order not found."); }

        $data = ['po' => $po, 'items' => $this->poModel->getPOItems($id), 'company' => $this->companyModel->getSettings()];
        $this->view('purchases/po_view', $data);
    }

    public function wizard() {
        $data = ['title' => 'Procurement Wizard', 'content_view' => 'purchases/wizard', 'vendors' => $this->vendorModel->getAllVendors()];
        $this->view('layouts/main', $data);
    }

    public function wizard_process() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $vendorId = $_POST['vendor_id'];
            if ($_POST['po_mode'] === 'manual') { header('Location: ' . APP_URL . '/purchase/create?vendor_id=' . $vendorId); exit; }
            else {
                $range = $_POST['date_range']; $buffer = floatval($_POST['buffer_percent'] ?? 0);
                if ($range === 'week') { $startDate = date('Y-m-d', strtotime('-1 week')); $endDate = date('Y-m-d'); } 
                elseif ($range === 'month') { $startDate = date('Y-m-d', strtotime('-1 month')); $endDate = date('Y-m-d'); } 
                else { $startDate = $_POST['start_date']; $endDate = $_POST['end_date']; }
                
                $salesData = $this->poModel->getVendorProductsSales($vendorId, $startDate, $endDate);
                foreach($salesData as $item) {
                    $projectedNeed = ceil($item->sold_qty * (1 + ($buffer / 100)));
                    $suggestedOrder = $projectedNeed - $item->quantity_on_hand;
                    $item->suggested_qty = $suggestedOrder > 0 ? $suggestedOrder : 0;
                }
                $data = ['title' => 'Smart Suggestions', 'content_view' => 'purchases/suggest', 'vendor' => $this->vendorModel->getVendorById($vendorId), 'sales_data' => $salesData, 'start_date' => $startDate, 'end_date' => $endDate, 'buffer' => $buffer];
                $this->view('layouts/main', $data);
            }
        } else { header('Location: ' . APP_URL . '/purchase/wizard'); }
    }

    public function create() {
        $prefilledVendor = $_GET['vendor_id'] ?? ($_POST['vendor_id'] ?? '');
        $prefilledItems = [];
        
        $catalogItems = $this->itemModel->getAllItems();
        foreach($catalogItems as $item) {
            $item->variations = $this->itemModel->getItemVariations($item->id);
        }
        
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'from_suggest') {
            if(isset($_POST['selected_items'])) {
                foreach($_POST['selected_items'] as $index) {
                    $qty = floatval($_POST['suggested_qty'][$index]);
                    if ($qty > 0) { 
                        $prefilledItems[] = [
                            'item_id' => $_POST['item_id'][$index],
                            'name' => $_POST['item_name'][$index], 
                            'cost' => $_POST['item_cost'][$index], 
                            'qty' => $qty
                        ]; 
                    }
                }
            }
        }

        $data = [
            'title' => 'Create Purchase Order',
            'content_view' => 'purchases/create',
            'vendors' => $this->vendorModel->getAllVendors(),
            'catalog_items' => $catalogItems,
            'po_number' => 'PO-' . time(),
            'prefilled_vendor' => $prefilledVendor,
            'prefilled_items' => $prefilledItems,
            'error' => ''
        ];

        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'save_po') {
            $poData = ['vendor_id' => $_POST['vendor_id'], 'po_number' => $_POST['po_number'], 'po_date' => $_POST['po_date'], 'expected_date' => $_POST['expected_date'], 'notes' => trim($_POST['notes'])];
            $items = [];
            
            if (isset($_POST['item_selection'])) {
                for ($i = 0; $i < count($_POST['item_selection']); $i++) {
                    $selection = $_POST['item_selection'][$i];
                    if (!empty($selection) && $_POST['qty'][$i] > 0 && $_POST['price'][$i] >= 0) {
                        list($itemId, $varOptId, $isMix) = explode('|', $selection);
                        $items[] = [
                            'item_id' => $itemId,
                            'var_opt_id' => ($varOptId === 'MIX' || $varOptId === '0') ? null : $varOptId,
                            'is_mix' => $isMix,
                            'desc' => $_POST['desc'][$i],
                            'qty' => $_POST['qty'][$i],
                            'price' => $_POST['price'][$i]
                        ];
                    }
                }
            }

            if (empty($items)) { $data['error'] = 'You must add at least one item.'; } 
            else {
                if ($this->poModel->createPO($poData, $items, $_SESSION['user_id'])) { header('Location: ' . APP_URL . '/purchase?success=PO Created Successfully'); exit; } 
                else { $data['error'] = 'Database Error: Failed to create Purchase Order.'; }
            }
        }
        $this->view('layouts/main', $data);
    }

    public function edit($id = null) {
        if (!$id) { header('Location: ' . APP_URL . '/purchase'); exit; }
        $po = $this->poModel->getPOById($id);
        if (!$po) { die("PO not found."); }
        if ($po->status === 'Received') { die("Cannot edit a PO that has already been converted to a GRN."); }

        $catalogItems = $this->itemModel->getAllItems();
        foreach($catalogItems as $item) {
            $item->variations = $this->itemModel->getItemVariations($item->id);
        }

        $data = [
            'title' => 'Edit Purchase Order',
            'content_view' => 'purchases/create',
            'vendors' => $this->vendorModel->getAllVendors(),
            'catalog_items' => $catalogItems,
            'po' => $po,
            'items' => $this->poModel->getPOItems($id),
            'prefilled_vendor' => $po->vendor_id,
            'error' => ''
        ];

        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_po') {
            $poData = ['id' => $id, 'vendor_id' => $_POST['vendor_id'], 'po_date' => $_POST['po_date'], 'expected_date' => $_POST['expected_date'], 'notes' => trim($_POST['notes'])];
            $items = [];
            
            if (isset($_POST['item_selection'])) {
                for ($i = 0; $i < count($_POST['item_selection']); $i++) {
                    $selection = $_POST['item_selection'][$i];
                    if (!empty($selection) && $_POST['qty'][$i] > 0 && $_POST['price'][$i] >= 0) {
                        list($itemId, $varOptId, $isMix) = explode('|', $selection);
                        $items[] = [
                            'item_id' => $itemId,
                            'var_opt_id' => ($varOptId === 'MIX' || $varOptId === '0') ? null : $varOptId,
                            'is_mix' => $isMix,
                            'desc' => $_POST['desc'][$i],
                            'qty' => $_POST['qty'][$i],
                            'price' => $_POST['price'][$i]
                        ];
                    }
                }
            }

            if ($this->poModel->updatePO($poData, $items)) { header('Location: ' . APP_URL . '/purchase?success=PO Updated Successfully'); exit; } 
            else { $data['error'] = 'Failed to update PO.'; }
        }
        $this->view('layouts/main', $data);
    }

    // --- NEW: Email PO Engine ---
    public function email_po() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['po_id'])) {
            $poId = $_POST['po_id'];
            $po = $this->poModel->getPOById($poId);
            
            if (!$po || empty($po->email)) {
                header('Location: ' . APP_URL . '/purchase?error=Vendor does not have a valid email address on file.');
                exit;
            }

            $items = $this->poModel->getPOItems($poId);
            $company = $this->companyModel->getSettings();

            // 1. Generate the Email Body
            $htmlContent = "<div style='font-family: sans-serif; color: #333;'>";
            $htmlContent .= "<h3 style='color: #0066cc;'>Purchase Order: {$po->po_number}</h3>";
            $htmlContent .= "<p>Dear {$po->vendor_name},</p>";
            $htmlContent .= "<p>Please find attached our Purchase Order <strong>{$po->po_number}</strong> from <strong>{$company->company_name}</strong>.</p>";
            $htmlContent .= "<div style='background: #f4f5f7; padding: 15px; border-radius: 6px; margin: 20px 0;'>";
            $htmlContent .= "<strong>Order Date:</strong> " . date('M d, Y', strtotime($po->po_date)) . "<br>";
            $htmlContent .= "<strong>Expected Delivery:</strong> " . date('M d, Y', strtotime($po->expected_date)) . "<br>";
            $htmlContent .= "<strong>Total Amount:</strong> Rs: " . number_format($po->total_amount, 2);
            $htmlContent .= "</div>";
            $htmlContent .= "<p>Thank you for your business,<br><strong>{$company->company_name}</strong></p>";
            $htmlContent .= "</div>";

            // 2. Capture the Printable PO View (Passing 'is_email' => true flag)
            ob_start();
            $data = ['po' => $po, 'items' => $items, 'company' => $company, 'is_email' => true];
            extract($data); 
            require '../app/Views/purchases/po_view.php';
            $attachmentHtml = ob_get_clean();

            // 3. Send via Brevo API
            require_once '../app/Services/BrevoMailer.php';
            $mailer = new BrevoMailer();
            
            $response = $mailer->sendEmail(
                $po->email, 
                $po->vendor_name, 
                "Purchase Order {$po->po_number} - {$company->company_name}", 
                $htmlContent,
                $attachmentHtml,
                "{$po->po_number}.html",
                $company->company_name // <-- Passes dynamic Company Name to the Mailer
            );

            if ($response['success']) {
                $db = new Database();
                $db->query("UPDATE purchase_orders SET status = 'Sent' WHERE id = :id AND status = 'Draft'");
                $db->bind(':id', $poId);
                $db->execute();

                header('Location: ' . APP_URL . '/purchase?success=Purchase Order emailed successfully to ' . urlencode($po->vendor_name));
            } else {
                $debugMsg = urlencode($response['error']);
                header('Location: ' . APP_URL . '/purchase?error=Failed to send email via Brevo. Check console for details.&debug=' . $debugMsg);
            }
            exit;
        }
    }
}