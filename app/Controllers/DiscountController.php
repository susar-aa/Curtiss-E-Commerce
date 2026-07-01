<?php

class DiscountController extends Controller {
    private $discountModel;
    private $itemModel;

    public function __construct() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . APP_URL . '/auth/login');
            exit;
        }
        $this->discountModel = $this->model('DiscountRule');
        $this->itemModel = $this->model('Item');
    }

    public function index() {
        $rules = $this->discountModel->getAllRules();
        $items = $this->itemModel->getItems();

        $data = [
            'title' => 'Customizable Discount Feed',
            'content_view' => 'discounts/index',
            'rules' => $rules,
            'items' => $items,
            'error' => '',
            'success' => ''
        ];

        if (isset($_GET['success'])) {
            if ($_GET['success'] == 'add') {
                $data['success'] = "New discount rule configured successfully!";
            } elseif ($_GET['success'] == 'delete') {
                $data['success'] = "Discount rule deleted successfully!";
            } elseif ($_GET['success'] == 'toggle') {
                $data['success'] = "Discount rule status updated!";
            }
        }

        $this->view('layouts/main', $data);
    }

    public function add() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . APP_URL . '/discount');
            exit;
        }

        $_POST = filter_input_array(INPUT_POST, FILTER_UNSAFE_RAW);

        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        $rule_type = isset($_POST['rule_type']) ? trim($_POST['rule_type']) : '';
        $target_item_id = isset($_POST['target_item_id']) && $_POST['target_item_id'] !== '' ? intval($_POST['target_item_id']) : null;
        
        if (empty($name) || empty($rule_type)) {
            $_SESSION['discount_error'] = "Rule Name and Rule Type are required.";
            header('Location: ' . APP_URL . '/discount');
            exit;
        }

        // Parse Tiers
        $tiers = [];
        if (isset($_POST['min_threshold']) && is_array($_POST['min_threshold'])) {
            for ($i = 0; $i < count($_POST['min_threshold']); $i++) {
                $min = trim($_POST['min_threshold'][$i]);
                $max = isset($_POST['max_threshold'][$i]) ? trim($_POST['max_threshold'][$i]) : '';
                $reward = isset($_POST['reward_val'][$i]) ? trim($_POST['reward_val'][$i]) : '';

                if ($min !== '' && $reward !== '') {
                    $tiers[] = [
                        'min_threshold' => floatval($min),
                        'max_threshold' => $max !== '' ? floatval($max) : null,
                        'reward_val' => floatval($reward)
                    ];
                }
            }
        }

        if (empty($tiers)) {
            $_SESSION['discount_error'] = "At least one discount tier must be configured.";
            header('Location: ' . APP_URL . '/discount');
            exit;
        }

        $addData = [
            'name' => $name,
            'rule_type' => $rule_type,
            'target_item_id' => $target_item_id,
            'status' => 'Active'
        ];

        $ruleId = $this->discountModel->addRule($addData, $tiers);
        if ($ruleId) {
            $this->logActivity('Create Discount', 'Billing', "Created customizable discount rule ID {$ruleId}: {$name} ({$rule_type})");
            header('Location: ' . APP_URL . '/discount?success=add');
            exit;
        } else {
            $_SESSION['discount_error'] = "Failed to configure new discount rule in database.";
            header('Location: ' . APP_URL . '/discount');
            exit;
        }
    }

    public function delete($id) {
        $rule = $this->discountModel->getRuleById($id);
        if ($rule) {
            if ($this->discountModel->deleteRule($id)) {
                $this->logActivity('Delete Discount', 'Billing', "Deleted discount rule ID {$id}: {$rule->name}");
                header('Location: ' . APP_URL . '/discount?success=delete');
                exit;
            }
        }
        header('Location: ' . APP_URL . '/discount');
        exit;
    }

    public function toggle($id) {
        $rule = $this->discountModel->getRuleById($id);
        if ($rule) {
            $newStatus = ($rule->status === 'Active') ? 'Inactive' : 'Active';
            if ($this->discountModel->toggleRuleStatus($id, $newStatus)) {
                $this->logActivity('Toggle Discount Status', 'Billing', "Updated status of discount rule ID {$id} to {$newStatus}");
                header('Location: ' . APP_URL . '/discount?success=toggle');
                exit;
            }
        }
        header('Location: ' . APP_URL . '/discount');
        exit;
    }

    public function api_get_active_rules() {
        header('Content-Type: application/json');
        $rules = $this->discountModel->getActiveRules();
        echo json_encode($rules);
        exit;
    }
}
