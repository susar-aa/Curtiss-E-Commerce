<?php
class CreditNoteController extends Controller {
    private $customerModel;
    private $creditNoteModel;
    private $coaModel;
    private $companyModel;
    private $itemModel;

    public function __construct() {
        if (!isset($_SESSION['user_id'])) { header('Location: ' . APP_URL . '/auth/login'); exit; }
        $this->customerModel = $this->model('Customer');
        $this->creditNoteModel = $this->model('CreditNote');
        $this->coaModel = $this->model('ChartOfAccount');
        $this->companyModel = $this->model('Company');
        $this->itemModel = $this->model('Item');
    }

    public function index() {
        $data = [
            'title' => 'Credit Notes & Refunds',
            'content_view' => 'credit_notes/index',
            'credit_notes' => $this->creditNoteModel->getAllCreditNotes(),
            'error' => '',
            'success' => ''
        ];

        $this->view('layouts/main', $data);
    }

    public function show($id = null) {
        if (!$id) { header('Location: ' . APP_URL . '/creditnote'); exit; }

        $creditNote = $this->creditNoteModel->getCreditNoteById($id);
        if (!$creditNote) { die("Credit Note not found."); }

        $data = [
            'credit_note' => $creditNote,
            'items' => $this->creditNoteModel->getCreditNoteItems($id),
            'company' => $this->companyModel->getSettings()
        ];

        $this->view('credit_notes/show', $data);
    }

    /**
     * AJAX endpoint to fetch products sold to a customer
     */
    public function get_customer_products() {
        $customerId = intval($_GET['customer_id'] ?? 0);
        $products = $this->creditNoteModel->getCustomerProducts($customerId);
        header('Content-Type: application/json');
        echo json_encode($products);
        exit;
    }

    /**
     * AJAX endpoint to fetch sales history for a specific customer & product
     */
    public function get_product_sale_history() {
        $customerId = intval($_GET['customer_id'] ?? 0);
        $productVal = $_GET['product_val'] ?? '';
        
        $parts = explode('|', $productVal);
        $itemId = intval($parts[0] ?? 0);
        $varOptId = intval($parts[1] ?? 0);

        $history = $this->creditNoteModel->getProductSaleHistory($customerId, $itemId, $varOptId);
        header('Content-Type: application/json');
        echo json_encode($history);
        exit;
    }

    /**
     * Dashboard page to view all damaged returned products
     */
    public function damaged() {
        $damagedItems = $this->creditNoteModel->getDamagedProducts();
        
        $data = [
            'title' => 'Damaged Products Log',
            'content_view' => 'credit_notes/damaged',
            'items' => $damagedItems
        ];

        $this->view('layouts/main', $data);
    }

    public function create() {
        $data = [
            'title' => 'Issue Credit Note',
            'content_view' => 'credit_notes/create',
            'customers' => $this->customerModel->getAllCustomers(),
            'credit_note_number' => 'CN-' . time(),
            'error' => ''
        ];

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $cnData = [
                'customer_id' => $_POST['customer_id'],
                'credit_note_number' => $_POST['credit_note_number'],
                'date' => $_POST['note_date']
            ];
            $arAccount = $_POST['ar_account'] ?? null;
            $revAccount = $_POST['revenue_account'] ?? null;
            $expenseAccount = $_POST['expense_account'] ?? null;
            
            $items = [];
            if (isset($_POST['item_selection'])) {
                for ($i = 0; $i < count($_POST['item_selection']); $i++) {
                    if (!empty($_POST['item_selection'][$i]) && $_POST['qty'][$i] > 0 && $_POST['price'][$i] >= 0) {
                        $parts = explode('|', $_POST['item_selection'][$i]);
                        $itemId = intval($parts[0] ?? 0);
                        $varOptId = intval($parts[1] ?? 0);

                        $items[] = [
                            'item_id' => $itemId,
                            'var_opt_id' => $varOptId,
                            'invoice_id' => !empty($_POST['invoice_id'][$i]) ? intval($_POST['invoice_id'][$i]) : null,
                            'invoice_item_id' => !empty($_POST['invoice_item_id'][$i]) ? intval($_POST['invoice_item_id'][$i]) : null,
                            'desc' => $_POST['desc'][$i],
                            'qty' => floatval($_POST['qty'][$i]),
                            'price' => floatval($_POST['price'][$i]),
                            'condition' => $_POST['condition'][$i] ?? 'Good'
                        ];
                    }
                }
            }

            if (empty($items)) {
                $data['error'] = 'You must add at least one valid returned item.';
            } else {
                $creditNoteId = $this->creditNoteModel->createCreditNoteWithAccounting($cnData, $items, $arAccount, $revAccount, $expenseAccount, $_SESSION['user_id']);
                if ($creditNoteId) {
                    $newValues = [
                        'credit_note' => $cnData,
                        'items' => $items
                    ];
                    $this->logActivity('Create Credit Note', 'Billing', "Issued Credit Note {$cnData['credit_note_number']} for Customer ID {$cnData['customer_id']}", $creditNoteId, null, $newValues);
                    header('Location: ' . APP_URL . '/creditnote?success=1');
                    exit;
                } else {
                    $data['error'] = 'Database Error: Failed to issue credit note and post ledger entries.';
                }
            }
        }

        $this->view('layouts/main', $data);
    }
}