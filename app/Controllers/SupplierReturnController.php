<?php
class SupplierReturnController extends Controller {
    private $vendorModel;
    private $returnModel;

    public function __construct() {
        if (!isset($_SESSION['user_id'])) { 
            header('Location: ' . APP_URL . '/auth/login'); 
            exit; 
        }
        $this->vendorModel = $this->model('Supplier');
        $this->returnModel = $this->model('SupplierReturn');
    }

    public function index() {
        $search = $_GET['search'] ?? '';
        $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;
        $filters = ['vendor_id' => $_GET['vendor_id'] ?? ''];

        $totalReturns = $this->returnModel->getTotalReturns($search, $filters);
        
        $data = [
            'title' => 'Supplier Returns',
            'content_view' => 'supplier_returns/index',
            'returns' => $this->returnModel->getReturnsPaginated($search, $limit, $offset, $filters),
            'vendors' => $this->vendorModel->getAllVendors(),
            'search' => $search,
            'filters' => $filters,
            'page' => $page,
            'total_pages' => ceil($totalReturns / $limit),
            'error' => '',
            'success' => $_GET['success'] ?? ''
        ];
        $this->view('layouts/main', $data);
    }

    public function create() {
        $data = [
            'title' => 'Create Supplier Return',
            'content_view' => 'supplier_returns/create',
            'vendors' => $this->vendorModel->getAllVendors(),
            'return_number' => 'RET-' . time(),
            'error' => ''
        ];

        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'save_return') {
            $returnData = [
                'vendor_id' => $_POST['vendor_id'],
                'return_number' => $_POST['return_number'],
                'return_date' => $_POST['return_date'],
                'notes' => trim($_POST['notes']),
                'total_amount' => floatval($_POST['total_amount_hidden'] ?? 0)
            ];

            $items = [];
            if (isset($_POST['item_selection'])) {
                for ($i = 0; $i < count($_POST['item_selection']); $i++) {
                    $selection = $_POST['item_selection'][$i];
                    if (!empty($selection) && $_POST['qty'][$i] > 0) {
                        list($itemId, $varOptId) = explode('|', $selection);
                        $items[] = [
                            'item_id' => $itemId,
                            'var_opt_id' => ($varOptId === '0' || empty($varOptId)) ? null : $varOptId,
                            'desc' => $_POST['desc'][$i],
                            'qty' => floatval($_POST['qty'][$i]),
                            'price' => floatval($_POST['price'][$i]),
                            'grn_id' => !empty($_POST['grn_id'][$i]) ? $_POST['grn_id'][$i] : null
                        ];
                    }
                }
            }

            if (empty($items)) {
                $data['error'] = 'You must add at least one item to return.';
            } else {
                if ($this->returnModel->createReturn($returnData, $items, $_SESSION['user_id'])) {
                    header('Location: ' . APP_URL . '/supplier-return?success=Supplier Return saved successfully and stock deducted.');
                    exit;
                } else {
                    $data['error'] = 'Database Error: Failed to save Supplier Return.';
                }
            }
        }

        $this->view('layouts/main', $data);
    }

    public function show($id = null) {
        if (!$id) { 
            header('Location: ' . APP_URL . '/supplier-return'); 
            exit; 
        }
        $return = $this->returnModel->getReturnById($id);
        if (!$return) { 
            die("Supplier Return not found."); 
        }

        $data = [
            'return' => $return,
            'items' => $this->returnModel->getReturnItems($id)
        ];
        $this->view('supplier_returns/view', $data);
    }

    /**
     * Ajax endpoint: Get products purchased from a vendor
     */
    public function get_vendor_products() {
        $vendorId = $_GET['vendor_id'] ?? null;
        if (!$vendorId) {
            echo json_encode([]);
            exit;
        }
        $products = $this->returnModel->getProductsPurchasedFromVendor($vendorId);
        header('Content-Type: application/json');
        echo json_encode($products);
        exit;
    }

    /**
     * Ajax endpoint: Get purchase history of a product from a vendor
     */
    public function get_product_history() {
        $vendorId = $_GET['vendor_id'] ?? null;
        $productVal = $_GET['product_val'] ?? null;

        if (!$vendorId || !$productVal) {
            echo json_encode([]);
            exit;
        }

        list($itemId, $varOptId) = explode('|', $productVal);
        $varOptId = ($varOptId === '0' || empty($varOptId)) ? null : $varOptId;

        $history = $this->returnModel->getProductPurchaseHistory($vendorId, $itemId, $varOptId);
        header('Content-Type: application/json');
        echo json_encode($history);
        exit;
    }
}
