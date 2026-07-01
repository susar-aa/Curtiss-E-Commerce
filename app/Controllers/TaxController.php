<?php
class TaxController extends Controller {
    private $taxModel;
    private $coaModel;

    public function __construct() {
        if (!isset($_SESSION['user_id'])) { header('Location: ' . APP_URL . '/auth/login'); exit; }
        
        // Strict RBAC: Only Admins or Accountants can configure Tax settings
        if ($_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Accountant') {
            die("Access Denied: Only Administrators or Accountants can manage Tax Configuration.");
        }
        
        $this->taxModel = $this->model('Tax');
        $this->coaModel = $this->model('ChartOfAccount');
    }

    public function index() {
        $accounts = $this->coaModel->getAccounts();
        
        // Tax collected on behalf of the government is a Liability
        $liabilities = array_filter($accounts, function($a) { return $a->account_type == 'Liability'; });

        $data = [
            'title' => 'Tax Configuration',
            'content_view' => 'taxes/index',
            'tax_rates' => $this->taxModel->getAllTaxRates(),
            'liabilities' => $liabilities,
            'error' => '',
            'success' => ''
        ];

        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
            if ($_POST['action'] == 'add_tax') {
                $taxData = [
                    'tax_name' => trim($_POST['tax_name']),
                    'rate_percentage' => floatval($_POST['rate_percentage']),
                    'liability_account_id' => $_POST['liability_account_id']
                ];
                
                if (!empty($taxData['tax_name']) && $taxData['rate_percentage'] >= 0) {
                    if ($this->taxModel->addTaxRate($taxData)) {
                        $data['success'] = 'Tax Rate created successfully.';
                        $data['tax_rates'] = $this->taxModel->getAllTaxRates(); // Refresh data
                    } else {
                        $data['error'] = 'Database Error: Failed to create tax rate.';
                    }
                } else {
                    $data['error'] = 'Invalid tax configuration provided.';
                }
            } elseif ($_POST['action'] == 'toggle_status') {
                $this->taxModel->toggleStatus($_POST['tax_id'], $_POST['status']);
                $data['success'] = 'Tax status updated.';
                $data['tax_rates'] = $this->taxModel->getAllTaxRates();
            }
        }

        $this->view('layouts/main', $data);
    }
}