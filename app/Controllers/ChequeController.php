<?php
class ChequeController extends Controller {
    private $chequeModel;
    private $customerModel;
    private $supplierModel;
    private $companyModel;

    public function __construct() {
        if (!isset($_SESSION['user_id'])) { header('Location: ' . APP_URL . '/auth/login'); exit; }
        $this->chequeModel = $this->model('Cheque');
        $this->customerModel = $this->model('Customer');
        $this->supplierModel = $this->model('Supplier');
        $this->companyModel = $this->model('Company');
    }

    public function index() {
        $cheques = $this->chequeModel->getAllCheques();
        $company = $this->companyModel->getSettings();
        
        // Calculate KPIs
        $totalPending = 0;
        $totalCleared = 0;
        $nextBankingDate = null;
        $nextBankingAmount = 0;

        $groupedCheques = [];

        foreach ($cheques as $chk) {
            // Group by Date for UI display
            $dateKey = date('Y-m-d', strtotime($chk->banking_date));
            $groupedCheques[$dateKey][] = $chk;

            if ($chk->status == 'Pending') {
                $totalPending += $chk->amount;
                
                // Find nearest future/today date
                if ($nextBankingDate === null && strtotime($chk->banking_date) >= strtotime('today')) {
                    $nextBankingDate = $chk->banking_date;
                    $nextBankingAmount = $chk->amount;
                } elseif ($nextBankingDate === $chk->banking_date) {
                    $nextBankingAmount += $chk->amount;
                }
            } elseif ($chk->status == 'Cleared') {
                $totalCleared += $chk->amount;
            }
        }

        $data = [
            'title' => 'Cheque Management',
            'content_view' => 'cheques/index',
            'grouped_cheques' => $groupedCheques,
            'customers' => $this->customerModel->getAllCustomers() ?: [],
            'suppliers' => $this->supplierModel->getAllSuppliers() ?: [],
            'company_name' => $company->company_name,
            'kpi_pending' => $totalPending,
            'kpi_cleared' => $totalCleared,
            'kpi_next_date' => $nextBankingDate,
            'kpi_next_amount' => $nextBankingAmount,
            'error' => '',
            'success' => ''
        ];

        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
            if ($_POST['action'] == 'add_cheque') {
                $chkData = [
                    'customer_id' => !empty($_POST['customer_id']) ? intval($_POST['customer_id']) : null,
                    'vendor_id' => !empty($_POST['vendor_id']) ? intval($_POST['vendor_id']) : null,
                    'bank_name' => trim($_POST['bank_name']),
                    'cheque_number' => trim($_POST['cheque_number']),
                    'amount' => floatval($_POST['amount']),
                    'banking_date' => $_POST['banking_date'],
                    'created_by' => $_SESSION['user_id']
                ];
                if ($this->chequeModel->addCheque($chkData)) {
                    header('Location: ' . APP_URL . '/cheque?success=added'); exit;
                } else {
                    $data['error'] = 'Failed to add cheque.';
                }
            } elseif ($_POST['action'] == 'edit_cheque') {
                $chkData = [
                    'id' => $_POST['cheque_id'],
                    'customer_id' => !empty($_POST['customer_id']) ? intval($_POST['customer_id']) : null,
                    'vendor_id' => !empty($_POST['vendor_id']) ? intval($_POST['vendor_id']) : null,
                    'bank_name' => trim($_POST['bank_name']),
                    'cheque_number' => trim($_POST['cheque_number']),
                    'amount' => floatval($_POST['amount']),
                    'banking_date' => $_POST['banking_date'],
                    'status' => $_POST['status']
                ];
                if ($this->chequeModel->updateCheque($chkData)) {
                    header('Location: ' . APP_URL . '/cheque?success=updated'); exit;
                } else {
                    $data['error'] = 'Failed to update cheque.';
                }
            } elseif ($_POST['action'] == 'delete_cheque') {
                if ($this->chequeModel->deleteCheque($_POST['delete_id'])) {
                    header('Location: ' . APP_URL . '/cheque?success=deleted'); exit;
                }
            }
        }

        if (isset($_GET['success'])) {
            $data['success'] = "Cheque record " . htmlspecialchars($_GET['success']) . " successfully!";
        }

        $this->view('layouts/main', $data);
    }
}