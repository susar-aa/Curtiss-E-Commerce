<?php
class SupplierPaymentController extends Controller {
    private $paymentModel;
    private $supplierModel;
    private $coaModel;

    public function __construct() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . APP_URL . '/auth/login');
            exit;
        }
        $this->paymentModel = $this->model('Payment');
        $this->supplierModel = $this->model('Supplier');
        $this->coaModel = $this->model('ChartOfAccount');
    }

    public function index() {
        $suppliers = $this->paymentModel->getSupplierOutstandingList();
        $accounts = $this->coaModel->getAccounts() ?: [];

        // Filter Asset accounts (e.g. Cash/Bank)
        $assets = array_filter($accounts, function($a) {
            return $a->account_type == 'Asset';
        });

        // Find default Accounts Payable account (Code 2000 or similar name)
        $apAccount = null;
        foreach ($accounts as $acc) {
            if ($acc->account_code === '2000' || strpos(strtolower($acc->account_name), 'payable') !== false) {
                $apAccount = $acc;
                break;
            }
        }

        // Fetch supplier payment history
        $filters = [
            'start_date' => $_GET['start_date'] ?? '',
            'end_date' => $_GET['end_date'] ?? '',
            'method' => $_GET['method'] ?? '',
            'limit' => 100,
            'offset' => 0
        ];
        $paymentsHistory = $this->paymentModel->getSupplierPaymentHistory($filters);

        $data = [
            'title' => 'Supplier Payments (AP)',
            'content_view' => 'supplier_payments/index',
            'suppliers' => $suppliers,
            'assets' => $assets,
            'ap_account' => $apAccount,
            'payments_history' => $paymentsHistory,
            'filters' => $filters,
            'error' => '',
            'success' => ''
        ];

        if (isset($_GET['success'])) {
            if ($_GET['success'] === 'supplier_payment') {
                $data['success'] = 'Supplier payment recorded successfully!';
            } elseif ($_GET['success'] === 'reversed') {
                $data['success'] = 'Payment reversed successfully and ledger updated!';
            } elseif ($_GET['success'] === 'credit_applied') {
                $data['success'] = 'Available credit balance successfully applied to unpaid GRNs!';
            }
        }

        if (isset($_GET['error'])) {
            $data['error'] = htmlspecialchars($_GET['error']);
        }

        $this->view('layouts/main', $data);
    }

    /**
     * API to fetch supplier unpaid GRNs in JSON
     */
    public function getSupplierGRNsJson($supplierId) {
        header('Content-Type: application/json');
        $grns = $this->paymentModel->getSupplierUnpaidGRNs(intval($supplierId));
        echo json_encode(array_values($grns));
        exit;
    }

    /**
     * Record supplier payment
     */
    public function recordSupplierPayment() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . APP_URL . '/supplierpayment');
            exit;
        }

        $paymentData = [
            'supplier_id' => intval($_POST['supplier_id'] ?? 0),
            'amount' => floatval($_POST['amount'] ?? 0),
            'date' => $_POST['payment_date'] ?? date('Y-m-d'),
            'method' => $_POST['payment_method'] ?? 'Cash',
            'reference' => trim($_POST['reference'] ?? ''),
            'notes' => trim($_POST['notes'] ?? ''),
            'asset_account_id' => intval($_POST['asset_account_id'] ?? 0),
            'ap_account_id' => intval($_POST['ap_account_id'] ?? 0),
            'allocation_type' => $_POST['allocation_type'] ?? 'auto',
            'allocations' => $_POST['allocations'] ?? [],
            // Cheque details
            'cheque_bank' => trim($_POST['cheque_bank'] ?? ''),
            'cheque_number' => trim($_POST['cheque_number'] ?? ''),
            'cheque_date' => $_POST['cheque_date'] ?? ''
        ];

        if ($paymentData['amount'] <= 0) {
            header('Location: ' . APP_URL . '/supplierpayment?error=Payment amount must be greater than zero.');
            exit;
        }

        if (empty($paymentData['supplier_id'])) {
            header('Location: ' . APP_URL . '/supplierpayment?error=Please select a supplier.');
            exit;
        }

        if (empty($paymentData['asset_account_id']) || empty($paymentData['ap_account_id'])) {
            header('Location: ' . APP_URL . '/supplierpayment?error=Ledger accounts must be specified.');
            exit;
        }

        if ($paymentData['method'] === 'Cheque' && (empty($paymentData['cheque_bank']) || empty($paymentData['cheque_number']) || empty($paymentData['cheque_date']))) {
            header('Location: ' . APP_URL . '/supplierpayment?error=Cheque details are required.');
            exit;
        }

        $paymentId = $this->paymentModel->recordSupplierPayment($paymentData, $_SESSION['user_id']);
        if ($paymentId) {
            $this->logActivity('Record Supplier Payment', 'Payments', "Recorded supplier payment of Rs: " . number_format($paymentData['amount'], 2) . " for Supplier ID {$paymentData['supplier_id']} via {$paymentData['method']}");
            header('Location: ' . APP_URL . '/supplierpayment?success=supplier_payment');
        } else {
            header('Location: ' . APP_URL . '/supplierpayment?error=Failed to record payment and update ledger.');
        }
        exit;
    }

    /**
     * Reverse Supplier Payment
     */
    public function reverseSupplierPayment($id) {
        if ($this->paymentModel->reverseSupplierPayment(intval($id), $_SESSION['user_id'])) {
            $this->logActivity('Reverse Supplier Payment', 'Payments', "Reversed supplier payment ID: {$id}");
            header('Location: ' . APP_URL . '/supplierpayment?success=reversed');
        } else {
            header('Location: ' . APP_URL . '/supplierpayment?error=Failed to reverse supplier payment.');
        }
        exit;
    }

    /**
     * Apply Supplier Credit to unpaid GRNs
     */
    public function applyCredit($supplierId) {
        if ($this->paymentModel->settleSupplierGRNsWithCredit(intval($supplierId), $_SESSION['user_id'])) {
            $this->logActivity('Apply Supplier Credit', 'Payments', "Applied advance credit balance for Supplier ID: {$supplierId}");
            header('Location: ' . APP_URL . '/supplierpayment?success=credit_applied');
        } else {
            header('Location: ' . APP_URL . '/supplierpayment?error=Failed to apply credit or no outstanding balance exists.');
        }
        exit;
    }

    /**
     * Generate Supplier Receipt/Voucher print view
     */
    public function receipt($id) {
        $payment = $this->paymentModel->getSupplierPaymentById(intval($id));
        if (!$payment) {
            die('Payment record not found.');
        }
        $allocations = $this->paymentModel->getSupplierPaymentAllocations(intval($id));

        $data = [
            'title' => 'Supplier Voucher - ' . $payment->reference,
            'payment' => $payment,
            'allocations' => $allocations
        ];
        $this->view('payments/supplier_receipt', $data);
    }

    /**
     * Generate Supplier Statement print view
     */
    public function statement($supplierId) {
        $supplier = $this->supplierModel->getSupplierById(intval($supplierId));
        if (!$supplier) {
            die('Supplier not found.');
        }

        $startDate = $_GET['start_date'] ?? '';
        $endDate = $_GET['end_date'] ?? '';

        $statement = $this->paymentModel->getSupplierStatement(intval($supplierId), $startDate, $endDate);

        $data = [
            'title' => 'Supplier Statement - ' . $supplier->name,
            'supplier' => $supplier,
            'statement' => $statement,
            'start_date' => $startDate,
            'end_date' => $endDate
        ];
        $this->view('payments/supplier_statement', $data);
    }
}
