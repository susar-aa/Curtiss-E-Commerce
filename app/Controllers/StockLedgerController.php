<?php

class StockLedgerController extends Controller {
    private $ledgerModel;
    private $itemModel;
    private $categoryModel;
    private $warehouseModel;
    private $userModel;

    public function __construct() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . APP_URL . '/auth/login');
            exit;
        }
        $this->ledgerModel = $this->model('StockLedger');
        $this->itemModel = $this->model('Item');
        $this->categoryModel = $this->model('Category');
        $this->warehouseModel = $this->model('Warehouse');
        $this->userModel = $this->model('User');
    }

    public function index() {
        $search = $_GET['search'] ?? '';
        $itemId = $_GET['item_id'] ?? '';
        $categoryId = $_GET['category_id'] ?? '';
        $brand = $_GET['brand'] ?? '';
        $warehouseId = $_GET['warehouse_id'] ?? '';
        $transactionType = $_GET['transaction_type'] ?? '';
        $userId = $_GET['user_id'] ?? '';
        $startDate = $_GET['start_date'] ?? '';
        $endDate = $_GET['end_date'] ?? '';

        $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 25;
        $offset = ($page - 1) * $limit;

        $filters = [
            'search' => $search,
            'item_id' => $itemId,
            'category_id' => $categoryId,
            'brand' => $brand,
            'warehouse_id' => $warehouseId,
            'transaction_type' => $transactionType,
            'user_id' => $userId,
            'start_date' => $startDate,
            'end_date' => $endDate
        ];

        // Fetch paginated movements
        $movements = $this->ledgerModel->getMovements($filters, $limit, $offset);
        $totalItems = $this->ledgerModel->getMovementsCount($filters);
        $totalPages = ceil($totalItems / $limit);

        // Fetch metrics
        $metrics = $this->ledgerModel->getSummaryMetrics($filters);

        // Fetch lists for filters
        $items = $this->itemModel->getAllItems();
        $categories = $this->categoryModel->getCategories();
        $warehouses = $this->warehouseModel->getAllWarehouses();
        $users = $this->userModel->getAllUsers();

        // Unique brands list
        $db = new Database();
        $db->query("SELECT DISTINCT brand FROM items WHERE brand IS NOT NULL AND brand != '' ORDER BY brand ASC");
        $brands = $db->resultSet() ?: [];

        $data = [
            'title' => 'Stock Ledger',
            'content_view' => 'stock_ledger/index',
            'movements' => $movements,
            'totalItems' => $totalItems,
            'totalPages' => $totalPages,
            'currentPage' => $page,
            'metrics' => $metrics,
            'items' => $items,
            'categories' => $categories,
            'warehouses' => $warehouses,
            'users' => $users,
            'brands' => $brands,
            'filters' => $filters
        ];

        $this->view('layouts/main', $data);
    }

    public function product($itemId = null) {
        if (!$itemId) {
            header('Location: ' . APP_URL . '/stockledger');
            exit;
        }

        $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $_GET['end_date'] ?? '';
        $varOptId = $_GET['variation_option_id'] ?? null;

        $item = $this->itemModel->getItemById($itemId);
        if (!$item) {
            header('Location: ' . APP_URL . '/stockledger?error=Product not found');
            exit;
        }

        // Fetch variations
        $db = new Database();
        $db->query("SELECT * FROM item_variation_options WHERE item_id = :iid");
        $db->bind(':iid', $itemId);
        $variations = $db->resultSet() ?: [];

        // Fetch stock card details
        $stockCard = $this->ledgerModel->getStockCardForProduct($itemId, $varOptId, $startDate, $endDate);

        $data = [
            'title' => 'Stock Card - ' . $item->name,
            'content_view' => 'stock_ledger/product',
            'item' => $item,
            'variations' => $variations,
            'varOptId' => $varOptId,
            'startDate' => $startDate,
            'endDate' => $endDate ?: date('Y-m-d'),
            'opening_balance' => $stockCard['opening_balance'],
            'movements' => $stockCard['movements'],
            'closing_balance' => $stockCard['closing_balance']
        ];

        $this->view('layouts/main', $data);
    }

    public function exportCsv() {
        $filters = [
            'search' => $_GET['search'] ?? '',
            'item_id' => $_GET['item_id'] ?? '',
            'category_id' => $_GET['category_id'] ?? '',
            'brand' => $_GET['brand'] ?? '',
            'warehouse_id' => $_GET['warehouse_id'] ?? '',
            'transaction_type' => $_GET['transaction_type'] ?? '',
            'user_id' => $_GET['user_id'] ?? '',
            'start_date' => $_GET['start_date'] ?? '',
            'end_date' => $_GET['end_date'] ?? ''
        ];

        // Fetch all movements (no limits)
        $movements = $this->ledgerModel->getMovements($filters, 100000, 0);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=stock_ledger_' . date('Ymd_His') . '.csv');

        $output = fopen('php://output', 'w');
        
        // UTF-8 BOM for Excel compatibility
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // Header Row
        fputcsv($output, [
            'Date & Time',
            'Product SKU',
            'Product Name',
            'Variation',
            'Transaction Type',
            'Reference Number',
            'Warehouse',
            'Qty In',
            'Qty Out',
            'Running Balance',
            'Unit Cost',
            'Total Value Impact',
            'User',
            'Remarks'
        ]);

        foreach ($movements as $mv) {
            fputcsv($output, [
                $mv->transaction_date,
                $mv->sku,
                $mv->item_name,
                $mv->variation_name ?: 'None',
                $mv->transaction_type,
                $mv->reference_number,
                $mv->warehouse_name ?: 'None',
                $mv->quantity_in,
                $mv->quantity_out,
                $mv->running_balance,
                $mv->unit_cost,
                $mv->total_value,
                $mv->user_name,
                $mv->remarks
            ]);
        }

        fclose($output);
        exit;
    }
}
