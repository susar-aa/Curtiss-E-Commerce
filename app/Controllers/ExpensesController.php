<?php
class ExpensesController extends Controller {
    private $vendorModel;
    private $expenseModel;
    private $coaModel;

    public function __construct() {
        if (!isset($_SESSION['user_id'])) { header('Location: ' . APP_URL . '/auth/login'); exit; }
        $this->vendorModel = $this->model('Supplier');
        $this->expenseModel = $this->model('Expense');
        $this->coaModel = $this->model('ChartOfAccount');
    }

    public function index() {
        $data = [
            'title' => 'Expenses & AP',
            'content_view' => 'expenses/index',
            'expenses' => $this->expenseModel->getAllExpenses(),
            'vendors' => $this->vendorModel->getAllVendors(),
            'error' => '',
            'success' => ''
        ];

        // REMOVED: Vendor Add Logic is now handled by VendorController.php

        $this->view('layouts/main', $data);
    }

    public function create() {
        $accounts = $this->coaModel->getAccounts();
        $expenses = array_filter($accounts, function($a) { return $a->account_type == 'Expense'; });
        $payment_accounts = array_filter($accounts, function($a) { return in_array($a->account_type, ['Asset', 'Liability']); });

        $data = [
            'title' => 'Record Expense',
            'content_view' => 'expenses/create',
            'vendors' => $this->vendorModel->getAllVendors(),
            'expense_accounts' => $expenses,
            'payment_accounts' => $payment_accounts,
            'reference' => 'EXP-' . time(),
            'error' => ''
        ];

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $expenseData = [
                'vendor_id' => $_POST['vendor_id'] ?? '',
                'reference' => trim($_POST['reference'] ?? ''),
                'expense_date' => $_POST['expense_date'] ?? '',
                'amount' => floatval($_POST['amount'] ?? 0),
                'description' => trim($_POST['description'] ?? '')
            ];
            $expenseAccount = $_POST['expense_account'] ?? '';
            $paymentAccount = $_POST['payment_account'] ?? '';

            if ($expenseData['amount'] <= 0) {
                $data['error'] = 'Amount must be greater than zero.';
            } else {
                if ($this->expenseModel->createExpenseWithAccounting($expenseData, $expenseAccount, $paymentAccount, $_SESSION['user_id'])) {
                    header('Location: ' . APP_URL . '/expenses?success=1');
                    exit;
                } else {
                    $data['error'] = 'Database Error: Failed to record expense.';
                }
            }
        }

        $this->view('layouts/main', $data);
    }
}