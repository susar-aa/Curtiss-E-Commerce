<?php
class CustomerPaymentController extends Controller {
    private $paymentModel;
    private $customerModel;
    private $coaModel;

    public function __construct() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . APP_URL . '/auth/login');
            exit;
        }
        $this->paymentModel = $this->model('Payment');
        $this->customerModel = $this->model('Customer');
        $this->coaModel = $this->model('ChartOfAccount');
    }

    public function index() {
        $customers = $this->paymentModel->getCustomerOutstandingList();
        $accounts = $this->coaModel->getAccounts() ?: [];

        // Filter Asset accounts (e.g. Cash/Bank)
        $assets = array_filter($accounts, function($a) {
            return $a->account_type == 'Asset';
        });

        // Find default Accounts Receivable account (Code 1200 or similar name)
        $arAccount = null;
        foreach ($accounts as $acc) {
            if ($acc->account_code === '1200' || strpos(strtolower($acc->account_name), 'receivable') !== false) {
                $arAccount = $acc;
                break;
            }
        }

        // Fetch customer payment history
        $filters = [
            'start_date' => $_GET['start_date'] ?? '',
            'end_date' => $_GET['end_date'] ?? '',
            'method' => $_GET['method'] ?? '',
            'limit' => 100,
            'offset' => 0
        ];
        $paymentsHistory = $this->paymentModel->getCustomerPaymentHistory($filters);

        $data = [
            'title' => 'Customer Payments (AR)',
            'content_view' => 'customer_payments/index',
            'customers' => $customers,
            'assets' => $assets,
            'ar_account' => $arAccount,
            'payments_history' => $paymentsHistory,
            'filters' => $filters,
            'error' => '',
            'success' => ''
        ];

        if (isset($_GET['success'])) {
            if ($_GET['success'] === 'customer_payment') {
                $data['success'] = 'Customer payment recorded successfully!';
            } elseif ($_GET['success'] === 'reversed') {
                $data['success'] = 'Payment reversed successfully and ledger updated!';
            } elseif ($_GET['success'] === 'credit_applied') {
                $data['success'] = 'Available credit balance successfully applied to unpaid invoices!';
            }
        }

        if (isset($_GET['error'])) {
            $data['error'] = htmlspecialchars($_GET['error']);
        }

        $this->view('layouts/main', $data);
    }

    /**
     * API to fetch customer unpaid invoices in JSON
     */
    public function getCustomerInvoicesJson($customerId) {
        header('Content-Type: application/json');
        $invoices = $this->paymentModel->getCustomerUnpaidInvoices(intval($customerId));
        echo json_encode(array_values($invoices));
        exit;
    }

    /**
     * Record customer payment
     */
    public function recordCustomerPayment() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . APP_URL . '/customerpayment');
            exit;
        }

        $paymentData = [
            'customer_id' => intval($_POST['customer_id'] ?? 0),
            'amount' => floatval($_POST['amount'] ?? 0),
            'date' => $_POST['payment_date'] ?? date('Y-m-d'),
            'method' => $_POST['payment_method'] ?? 'Cash',
            'reference' => trim($_POST['reference'] ?? ''),
            'notes' => trim($_POST['notes'] ?? ''),
            'asset_account_id' => intval($_POST['asset_account_id'] ?? 0),
            'ar_account_id' => intval($_POST['ar_account_id'] ?? 0),
            'allocation_type' => $_POST['allocation_type'] ?? 'auto',
            'allocations' => $_POST['allocations'] ?? [],
            // Cheque details
            'cheque_bank' => trim($_POST['cheque_bank'] ?? ''),
            'cheque_number' => trim($_POST['cheque_number'] ?? ''),
            'cheque_date' => $_POST['cheque_date'] ?? ''
        ];

        if ($paymentData['amount'] <= 0) {
            header('Location: ' . APP_URL . '/customerpayment?error=Payment amount must be greater than zero.');
            exit;
        }

        if (empty($paymentData['customer_id'])) {
            header('Location: ' . APP_URL . '/customerpayment?error=Please select a customer.');
            exit;
        }

        if (empty($paymentData['asset_account_id']) || empty($paymentData['ar_account_id'])) {
            header('Location: ' . APP_URL . '/customerpayment?error=Ledger accounts must be specified.');
            exit;
        }

        if ($paymentData['method'] === 'Cheque' && (empty($paymentData['cheque_bank']) || empty($paymentData['cheque_number']) || empty($paymentData['cheque_date']))) {
            header('Location: ' . APP_URL . '/customerpayment?error=Cheque details are required.');
            exit;
        }

        $paymentId = $this->paymentModel->recordCustomerPayment($paymentData, $_SESSION['user_id']);
        if ($paymentId) {
            $this->logActivity('Record Customer Payment', 'Payments', "Recorded customer payment of Rs: " . number_format($paymentData['amount'], 2) . " for Customer ID {$paymentData['customer_id']} via {$paymentData['method']}");
            header('Location: ' . APP_URL . '/customerpayment?success=customer_payment');
        } else {
            header('Location: ' . APP_URL . '/customerpayment?error=Failed to record payment and update ledger.');
        }
        exit;
    }

    /**
     * Reverse Customer Payment
     */
    public function reverseCustomerPayment($id) {
        if ($this->paymentModel->reverseCustomerPayment(intval($id), $_SESSION['user_id'])) {
            $this->logActivity('Reverse Customer Payment', 'Payments', "Reversed customer payment ID: {$id}");
            header('Location: ' . APP_URL . '/customerpayment?success=reversed');
        } else {
            header('Location: ' . APP_URL . '/customerpayment?error=Failed to reverse customer payment.');
        }
        exit;
    }

    /**
     * Apply Customer Credit to unpaid invoices
     */
    public function applyCredit($customerId) {
        if ($this->paymentModel->settleCustomerInvoicesWithCredit(intval($customerId), $_SESSION['user_id'])) {
            $this->logActivity('Apply Customer Credit', 'Payments', "Applied advance credit balance for Customer ID: {$customerId}");
            header('Location: ' . APP_URL . '/customerpayment?success=credit_applied');
        } else {
            header('Location: ' . APP_URL . '/customerpayment?error=Failed to apply credit or no outstanding balance exists.');
        }
        exit;
    }

    /**
     * Generate Customer Receipt print view
     */
    public function receipt($id) {
        $payment = $this->paymentModel->getCustomerPaymentById(intval($id));
        if (!$payment) {
            die('Payment record not found.');
        }
        $allocations = $this->paymentModel->getCustomerPaymentAllocations(intval($id));

        $data = [
            'title' => 'Customer Receipt - ' . $payment->reference,
            'payment' => $payment,
            'allocations' => $allocations
        ];
        $this->view('payments/customer_receipt', $data);
    }

    /**
     * Generate Customer Statement print view
     */
    public function statement($customerId) {
        $customer = $this->customerModel->getCustomerById(intval($customerId));
        if (!$customer) {
            die('Customer not found.');
        }

        $startDate = $_GET['start_date'] ?? '';
        $endDate = $_GET['end_date'] ?? '';

        $statement = $this->paymentModel->getCustomerStatement(intval($customerId), $startDate, $endDate);

        $data = [
            'title' => 'Customer Statement - ' . $customer->name,
            'customer' => $customer,
            'statement' => $statement,
            'start_date' => $startDate,
            'end_date' => $endDate
        ];
        $this->view('payments/customer_statement', $data);
    }
}
