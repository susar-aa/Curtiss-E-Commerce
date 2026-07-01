<?php
class GRNController extends Controller {
    private $vendorModel;
    private $grnModel;
    private $poModel;
    private $itemModel;
    private $companyModel;

    public function __construct() {
        if (!isset($_SESSION['user_id'])) { header('Location: ' . APP_URL . '/auth/login'); exit; }
        $this->vendorModel = $this->model('Supplier');
        $this->grnModel = $this->model('GRN');
        $this->poModel = $this->model('PurchaseOrder');
        $this->itemModel = $this->model('Item');
        $this->companyModel = $this->model('Company');
    }

    public function index() {
        $search = $_GET['search'] ?? '';
        $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;
        $filters = ['vendor_id' => $_GET['vendor_id'] ?? ''];

        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'delete_grn') {
            $grnId = intval($_POST['grn_id']);
            $grn = $this->grnModel->getGRNById($grnId);
            $grnItems = $this->grnModel->getGRNItems($grnId);
            $oldValues = [
                'grn' => $grn,
                'items' => $grnItems
            ];
            if ($this->grnModel->deleteGRN($grnId)) {
                $grnNum = $grn ? $grn->grn_number : $grnId;
                $this->logActivity('GRN Deleted', 'Inventory', "GRN '{$grnNum}' deleted and stock reversed.", $grnId, $oldValues, null);
                header("Location: " . APP_URL . "/grn?success=GRN deleted and Inventory Stock reversed successfully"); exit;
            }
        }

        $totalGRNs = $this->grnModel->getTotalGRNs($search, $filters);
        
        $data = [
            'title' => 'Goods Receipt Notes (GRN)',
            'content_view' => 'grns/index',
            'grns' => $this->grnModel->getGRNsPaginated($search, $limit, $offset, $filters),
            'vendors' => $this->vendorModel->getAllVendors(),
            'search' => $search,
            'filters' => $filters,
            'page' => $page,
            'total_pages' => ceil($totalGRNs / $limit),
            'error' => '',
            'success' => $_GET['success'] ?? ''
        ];
        $this->view('layouts/main', $data);
    }

    public function show($id = null) {
        if (!$id) { header('Location: ' . APP_URL . '/grn'); exit; }
        $grn = $this->grnModel->getGRNById($id);
        if (!$grn) { die("GRN not found."); }

        $data = [
            'grn' => $grn,
            'items' => $this->grnModel->getGRNItems($id),
            'company' => $this->companyModel->getSettings()
        ];
        $this->view('grns/view', $data);
    }

    public function create() {
        $poId = $_GET['po_id'] ?? null;
        $prefilledVendor = '';
        $prefilledItems = [];
        $linkedPO = null;

        if ($poId) {
            $linkedPO = $this->poModel->getPOById($poId);
            $prefilledVendor = $linkedPO->vendor_id;
            $prefilledItems = $this->poModel->getPOItems($poId);
        }

        $catalogItems = $this->itemModel->getAllItems();
        foreach($catalogItems as $item) {
            $item->variations = $this->itemModel->getItemVariations($item->id);
        }

        $data = [
            'title' => 'Create Goods Receipt Note (GRN)',
            'content_view' => 'grns/create',
            'vendors' => $this->vendorModel->getAllVendors(),
            'catalog_items' => $catalogItems,
            'grn_number' => 'GRN-' . time(),
            'prefilled_vendor' => $prefilledVendor,
            'linked_po' => $linkedPO,
            'prefilled_items' => $prefilledItems,
            'error' => ''
        ];

        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'save_grn') {
            $grnData = [
                'vendor_id' => $_POST['vendor_id'],
                'po_id' => !empty($_POST['po_id']) ? $_POST['po_id'] : null,
                'grn_number' => $_POST['grn_number'],
                'receipt_number' => trim($_POST['receipt_number'] ?? '') ?: null,
                'grn_date' => $_POST['grn_date'],
                'notes' => trim($_POST['notes'])
            ];
            
            $items = [];
            if (isset($_POST['item_selection'])) {
                for ($i = 0; $i < count($_POST['item_selection']); $i++) {
                    $selection = $_POST['item_selection'][$i];
                    if (!empty($selection) && $_POST['qty'][$i] > 0 && $_POST['price'][$i] >= 0) {
                        list($itemId, $varOptId) = explode('|', $selection);
                        $items[] = [
                            'item_id' => $itemId,
                            'var_opt_id' => ($varOptId === '0') ? null : $varOptId,
                            'desc' => $_POST['desc'][$i],
                            'qty' => $_POST['qty'][$i],
                            'price' => $_POST['price'][$i],
                            'selling_price' => floatval($_POST['selling_price'][$i] ?? 0),
                            'wholesale_price' => floatval($_POST['wholesale_price'][$i] ?? 0),
                            'retail_margin' => floatval($_POST['retail_margin'][$i] ?? 0),
                            'wholesale_margin' => floatval($_POST['wholesale_margin'][$i] ?? 0)
                        ];
                    }
                }
            }

             if (empty($items)) { $data['error'] = 'You must add at least one item to receive.'; } 
             else {
                 try {
                     $grnId = $this->grnModel->createGRN($grnData, $items, $_SESSION['user_id']);
                     if ($grnId) {
                         $newValues = [
                             'grn' => $grnData,
                             'items' => $items
                         ];
                          $this->logActivity('GRN Created', 'Inventory', "GRN '{$grnData['grn_number']}' created, pending approval.", $grnId, null, $newValues);
                          header('Location: ' . APP_URL . '/grn?success=' . urlencode('GRN created successfully. Pending admin approval.')); exit; 
                      } else { $data['error'] = 'Database Error: Failed to create GRN.'; }
                 } catch (Exception $e) {
                     $data['error'] = 'Database Error: Failed to create GRN. Details: ' . $e->getMessage();
                 }
             }
         }
        $this->view('layouts/main', $data);
    }

    public function edit($id = null) {
        if (!$id) { header('Location: ' . APP_URL . '/grn'); exit; }
        $grn = $this->grnModel->getGRNById($id);
        if (!$grn) { die("GRN not found."); }
        if ($grn->is_approved) {
            header('Location: ' . APP_URL . '/grn?error=' . urlencode('Approved Goods Receipt Notes cannot be edited.'));
            exit;
        }

        $catalogItems = $this->itemModel->getAllItems();
        foreach($catalogItems as $item) {
            $item->variations = $this->itemModel->getItemVariations($item->id);
        }

        $data = [
            'title' => 'Edit Goods Receipt Note (GRN)',
            'content_view' => 'grns/create',
            'vendors' => $this->vendorModel->getAllVendors(),
            'catalog_items' => $catalogItems,
            'grn' => $grn,
            'prefilled_vendor' => $grn->vendor_id,
            'prefilled_items' => $this->grnModel->getGRNItems($id),
            'linked_po' => $grn->po_id ? $this->poModel->getPOById($grn->po_id) : null,
            'error' => '',
            'success' => ''
        ];

        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_grn') {
            $grnData = [
                'vendor_id' => $_POST['vendor_id'],
                'receipt_number' => trim($_POST['receipt_number'] ?? '') ?: null,
                'grn_date' => $_POST['grn_date'],
                'notes' => trim($_POST['notes'])
            ];
            
            $items = [];
            if (isset($_POST['item_selection'])) {
                for ($i = 0; $i < count($_POST['item_selection']); $i++) {
                    $selection = $_POST['item_selection'][$i];
                    if (!empty($selection) && $_POST['qty'][$i] > 0 && $_POST['price'][$i] >= 0) {
                        list($itemId, $varOptId) = explode('|', $selection);
                        $items[] = [
                            'item_id' => $itemId,
                            'var_opt_id' => ($varOptId === '0') ? null : $varOptId,
                            'desc' => $_POST['desc'][$i],
                            'qty' => $_POST['qty'][$i],
                            'price' => $_POST['price'][$i],
                            'selling_price' => floatval($_POST['selling_price'][$i] ?? 0),
                            'wholesale_price' => floatval($_POST['wholesale_price'][$i] ?? 0),
                            'retail_margin' => floatval($_POST['retail_margin'][$i] ?? 0),
                            'wholesale_margin' => floatval($_POST['wholesale_margin'][$i] ?? 0)
                        ];
                    }
                }
            }

            if (empty($items)) {
                $data['error'] = 'You must add at least one item to receive.';
            } else {
                try {
                    if ($this->grnModel->updateGRN($id, $grnData, $items, $_SESSION['user_id'])) {
                        $newValues = [
                            'grn' => $grnData,
                            'items' => $items
                        ];
                        $this->logActivity('GRN Updated', 'Inventory', "GRN '{$grn->grn_number}' updated.", $id, null, $newValues);
                        header('Location: ' . APP_URL . '/grn?success=' . urlencode('GRN updated successfully.')); exit;
                    } else {
                        $data['error'] = 'Database Error: Failed to update GRN.';
                    }
                } catch (Exception $e) {
                    $data['error'] = 'Database Error: Failed to update GRN. Details: ' . $e->getMessage();
                }
            }
        }
        $this->view('layouts/main', $data);
    }

    public function approve($id = null) {
        if (!$id) { header('Location: ' . APP_URL . '/grn'); exit; }
        
        $role = strtolower($_SESSION['role'] ?? '');
        if ($role !== 'admin') {
            header('Location: ' . APP_URL . '/grn?error=Unauthorized: Only administrators can approve GRNs.');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['password'])) {
            header('Location: ' . APP_URL . '/grn?error=Password verification is required for approval.');
            exit;
        }

        $userModel = $this->model('User');
        $adminUser = $userModel->findUserByUsername($_SESSION['username']);
        if (!$adminUser || !password_verify($_POST['password'], $adminUser->password_hash)) {
            header('Location: ' . APP_URL . '/grn?error=Invalid administrator password. Approval failed.');
            exit;
        }

        try {
            if ($this->grnModel->approveGRN($id, $_SESSION['user_id'])) {
                $grn = $this->grnModel->getGRNById($id);
                $grnNum = $grn ? $grn->grn_number : $id;
                $this->logActivity('GRN Approved', 'Inventory', "GRN '{$grnNum}' approved and stock updated.", $id, null, null);
                header("Location: " . APP_URL . "/grn?success=GRN approved and stock updated successfully.");
                exit;
            } else {
                header("Location: " . APP_URL . "/grn?error=Failed to approve GRN.");
                exit;
            }
        } catch (Exception $e) {
            header("Location: " . APP_URL . "/grn?error=" . urlencode($e->getMessage()));
            exit;
        }
    }
}