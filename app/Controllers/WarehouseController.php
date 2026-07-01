<?php
class WarehouseController extends Controller {
    private $warehouseModel;

    public function __construct() {
        if (!isset($_SESSION['user_id'])) { header('Location: ' . APP_URL . '/auth/login'); exit; }
        $this->warehouseModel = $this->model('Warehouse');
    }

    public function index() {
        $data = [
            'title' => 'Warehouse Management',
            'content_view' => 'warehouses/index',
            'warehouses' => $this->warehouseModel->getAllWarehouses(),
            'error' => '',
            'success' => ''
        ];

        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
            $whData = [
                'name' => trim($_POST['name']),
                'location' => trim($_POST['location']),
                'is_default' => isset($_POST['is_default']) ? 1 : 0
            ];

            if ($_POST['action'] == 'add_warehouse') {
                if ($this->warehouseModel->addWarehouse($whData)) {
                    $data['success'] = 'Warehouse created successfully.';
                } else { $data['error'] = 'Failed to create warehouse.'; }
            } 
            elseif ($_POST['action'] == 'edit_warehouse') {
                $whData['id'] = $_POST['warehouse_id'];
                if ($this->warehouseModel->updateWarehouse($whData)) {
                    $data['success'] = 'Warehouse updated successfully.';
                } else { $data['error'] = 'Failed to update warehouse.'; }
            }
            elseif ($_POST['action'] == 'delete_warehouse') {
                if ($this->warehouseModel->deleteWarehouse($_POST['warehouse_id'])) {
                    $data['success'] = 'Warehouse deleted successfully.';
                } else { $data['error'] = 'Failed to delete. It is likely linked to existing products.'; }
            }
            $data['warehouses'] = $this->warehouseModel->getAllWarehouses();
        }

        $this->view('layouts/main', $data);
    }

    /**
     * View past transfers list and create new transfer
     */
    public function transfer() {
        $transferModel = $this->model('WarehouseTransfer');
        $transfers = $transferModel->getAllTransfers();

        $data = [
            'title' => 'Warehouse Stock Transfer',
            'content_view' => 'warehouses/transfer',
            'transfers' => $transfers,
            'error' => $_SESSION['flash_error'] ?? '',
            'success' => $_SESSION['flash_success'] ?? ''
        ];
        unset($_SESSION['flash_error'], $_SESSION['flash_success']);

        $this->view('layouts/main', $data);
    }

    /**
     * Store a newly created stock transfer
     */
    public function storeTransfer() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . APP_URL . '/warehouse/transfer');
            exit;
        }

        $itemId = intval($_POST['item_id']);
        $fromWh = intval($_POST['from_warehouse_id']);
        $toWh = intval($_POST['to_warehouse_id']);
        $qty = intval($_POST['qty']); // Transfer partial stock quantity
        $date = trim($_POST['transfer_date']);
        $notes = trim($_POST['notes']);

        if (!$itemId || !$toWh || !$date || $qty <= 0) {
            $_SESSION['flash_error'] = 'Invalid transfer payload. Please fill out all required fields and enter a valid quantity.';
            header('Location: ' . APP_URL . '/warehouse/transfer');
            exit;
        }

        if ($fromWh === $toWh) {
            $_SESSION['flash_error'] = 'Source and Destination Warehouses cannot be the same.';
            header('Location: ' . APP_URL . '/warehouse/transfer');
            exit;
        }

        // Generate a standard unique transfer reference
        $transferNum = 'TRF-' . strtoupper(substr(uniqid(), 5));

        $transferModel = $this->model('WarehouseTransfer');
        $payload = [
            'transfer_number' => $transferNum,
            'item_id' => $itemId,
            'qty' => $qty,
            'from_warehouse_id' => $fromWh,
            'to_warehouse_id' => $toWh,
            'transfer_date' => $date,
            'notes' => $notes,
            'created_by' => $_SESSION['user_id']
        ];

        $transferId = $transferModel->createTransfer($payload);
        if ($transferId) {
            $this->logActivity('Warehouse Transfer', 'Supply Chain', "Transferred {$qty} unit(s) of Item ID {$itemId} from Warehouse ID {$fromWh} to Warehouse ID {$toWh} (Ref: {$transferNum})", $transferId, null, $payload);
            $_SESSION['flash_success'] = "Stock transfer record posted successfully: " . $transferNum;
        } else {
            $_SESSION['flash_error'] = 'Failed to execute stock transfer transaction.';
        }

        header('Location: ' . APP_URL . '/warehouse/transfer');
        exit;
    }
}