<?php
class EstimateController extends Controller {
    private $customerModel;
    private $estimateModel;
    private $itemModel;
    private $companyModel;
    private $coaModel;
    private $invoiceModel;

    public function __construct() {
        if (!isset($_SESSION['user_id'])) { header('Location: ' . APP_URL . '/auth/login'); exit; }
        $this->customerModel = $this->model('Customer');
        $this->estimateModel = $this->model('Estimate');
        $this->itemModel = $this->model('Item');
        $this->companyModel = $this->model('Company');
        $this->coaModel = $this->model('ChartOfAccount');
        $this->invoiceModel = $this->model('Invoice');
    }

    public function index() {
        $accounts = $this->coaModel->getAccounts();
        $assets = array_filter($accounts, function($a) { return $a->account_type == 'Asset'; });
        $revenues = array_filter($accounts, function($a) { return $a->account_type == 'Revenue'; });

        $data = [
            'title' => 'Estimates & Quotes',
            'content_view' => 'estimates/index',
            'estimates' => $this->estimateModel->getAllEstimates(),
            'assets' => $assets,
            'revenues' => $revenues,
            'error' => '',
            'success' => ''
        ];

        // Handle Status Updates
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_status') {
            $this->estimateModel->updateStatus($_POST['estimate_id'], $_POST['new_status']);
            $data['success'] = "Estimate status updated.";
            $data['estimates'] = $this->estimateModel->getAllEstimates();
        }

        // Handle Conversion to Invoice
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'convert_to_invoice') {
            $estimateId = $_POST['estimate_id'];
            $arAccount = $_POST['ar_account'];
            $revAccount = $_POST['revenue_account'];

            $estimate = $this->estimateModel->getEstimateById($estimateId);
            $estimateItems = $this->estimateModel->getEstimateItems($estimateId);

            if ($estimate && count($estimateItems) > 0) {
                // Map items and calculate subtotal
                $subtotal = 0.00;
                $items = [];
                
                foreach ($estimateItems as $i) {
                    $itemTotal = floatval($i->quantity) * floatval($i->unit_price);
                    $subtotal += $itemTotal;

                    // Try to resolve item_id by searching by description (name)
                    $itemId = 0;
                    $this->db->query("SELECT id FROM items WHERE name = :name LIMIT 1");
                    $this->db->bind(':name', $i->description);
                    $rowItem = $this->db->single();
                    if ($rowItem) {
                        $itemId = $rowItem->id;
                    }

                    $items[] = [
                        'item_selection' => $itemId . '|0',
                        'description' => $i->description,
                        'quantity' => floatval($i->quantity),
                        'unit_price' => floatval($i->unit_price),
                        'discount_value' => 0.00,
                        'discount_type' => 'Rs',
                        'total' => $itemTotal
                    ];
                }

                // Generate new invoice number
                $this->db->query("SELECT id FROM invoices ORDER BY id DESC LIMIT 1");
                $lastRow = $this->db->single();
                $nextId = $lastRow ? ($lastRow->id + 1) : 1;
                $invoiceNumber = 'INV-' . date('Ymd') . '-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);

                // Map estimate data to invoice data array
                $invoiceData = [
                    'customer_id' => $estimate->customer_id,
                    'invoice_number' => $invoiceNumber,
                    'invoice_date' => date('Y-m-d'),
                    'due_date' => date('Y-m-d', strtotime('+14 days')),
                    'subtotal' => $subtotal,
                    'global_discount_val' => 0.00,
                    'global_discount_type' => 'Rs',
                    'notes' => 'Converted from Estimate ' . $estimate->estimate_number,
                    'grand_total' => $subtotal,
                    'rep_route_id' => null,
                    'stock_status' => 'deducted'
                ];

                // Push to the true accounting engine
                if ($this->invoiceModel->createInvoiceWithAccounting($invoiceData, $items, $arAccount, $revAccount, $_SESSION['user_id'])) {
                    // Mark estimate as invoiced
                    $this->estimateModel->updateStatus($estimateId, 'Invoiced');
                    header('Location: ' . APP_URL . '/sales?success=1');
                    exit;
                } else {
                    $data['error'] = 'Accounting Engine Error: Failed to generate invoice and ledger entries.';
                }
            }
        }

        $this->view('layouts/main', $data);
    }

    public function create() {
        $data = [
            'title' => 'Create Estimate',
            'content_view' => 'estimates/create',
            'customers' => $this->customerModel->getAllCustomers(),
            'catalog_items' => $this->itemModel->getAllItems(), 
            'estimate_number' => 'EST-' . time(),
            'error' => ''
        ];

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $estimateData = [
                'customer_id' => $_POST['customer_id'],
                'estimate_number' => $_POST['estimate_number'],
                'date' => $_POST['estimate_date'],
                'expiry_date' => $_POST['expiry_date']
            ];
            
            $items = [];
            for ($i = 0; $i < count($_POST['desc']); $i++) {
                if (!empty($_POST['desc'][$i]) && $_POST['qty'][$i] > 0 && $_POST['price'][$i] >= 0) {
                    $items[] = [
                        'desc' => $_POST['desc'][$i],
                        'qty' => $_POST['qty'][$i],
                        'price' => $_POST['price'][$i]
                    ];
                }
            }

            if (empty($items)) {
                $data['error'] = 'You must add at least one item.';
            } else {
                if ($this->estimateModel->createEstimate($estimateData, $items, $_SESSION['user_id'])) {
                    header('Location: ' . APP_URL . '/estimate?success=1');
                    exit;
                } else {
                    $data['error'] = 'Database Error: Failed to create estimate.';
                }
            }
        }
        $this->view('layouts/main', $data);
    }

    public function show($id = null) {
        if (!$id) { header('Location: ' . APP_URL . '/estimate'); exit; }

        $estimate = $this->estimateModel->getEstimateById($id);
        if (!$estimate) { die("Estimate not found."); }

        $data = [
            'estimate' => $estimate,
            'items' => $this->estimateModel->getEstimateItems($id),
            'company' => $this->companyModel->getSettings()
        ];
        $this->view('estimates/show', $data);
    }
}