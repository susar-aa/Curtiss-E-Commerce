<?php
class PayrollController extends Controller {
    private $payrollModel;
    private $employeeModel;
    private $coaModel;

    public function __construct() {
        if (!isset($_SESSION['user_id'])) { header('Location: ' . APP_URL . '/auth/login'); exit; }
        $this->payrollModel = $this->model('Payroll');
        $this->employeeModel = $this->model('Employee');
        $this->coaModel = $this->model('ChartOfAccount');
    }

    public function index() {
        $this->checkPermission('hrm', 'view');

        $activeEmployees = $this->employeeModel->getActiveEmployees();
        $estimatedGross = 0;
        foreach ($activeEmployees as $emp) {
            $estimatedGross += floatval($emp->base_salary);
        }

        $accounts = $this->coaModel->getAccounts();
        $expenses = array_filter($accounts, function($a) { return $a->account_type == 'Expense'; });
        $banks = array_filter($accounts, function($a) { return $a->account_type == 'Asset' && (stripos($a->account_name, 'bank') !== false || stripos($a->account_name, 'cash') !== false); });

        // Fallback for banks if empty
        if (empty($banks)) {
            $banks = array_filter($accounts, function($a) { return $a->account_type == 'Asset'; });
        }

        $data = [
            'title' => 'Payroll Processing',
            'content_view' => 'hrm/payroll',
            'active_employees_count' => count($activeEmployees),
            'estimated_gross' => $estimatedGross,
            'expenses' => $expenses,
            'banks' => $banks,
            'payroll_runs' => $this->payrollModel->getAllPayrollRuns(),
            'error' => '',
            'success' => ''
        ];

        if (isset($_SESSION['flash_success'])) {
            $data['success'] = $_SESSION['flash_success'];
            unset($_SESSION['flash_success']);
        }
        if (isset($_SESSION['flash_error'])) {
            $data['error'] = $_SESSION['flash_error'];
            unset($_SESSION['flash_error']);
        }

        $this->view('layouts/main', $data);
    }

    public function run() {
        $this->checkPermission('hrm', 'create_edit');
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'run_payroll') {
            $periodStart = $_POST['period_start'];
            $periodEnd = $_POST['period_end'];
            $runDate = $_POST['run_date'];
            $wageExpenseAccId = intval($_POST['wage_expense_account_id']);
            $bankAccId = intval($_POST['bank_account_id']);
            
            $activeEmployees = $this->employeeModel->getActiveEmployees();
            $totalGross = 0;
            foreach ($activeEmployees as $emp) {
                $totalGross += floatval($emp->base_salary);
            }

            if ($totalGross <= 0) {
                $_SESSION['flash_error'] = 'Failed to run payroll: Active employees have a total gross salary of 0.';
                header('Location: ' . APP_URL . '/payroll');
                exit;
            }

            if ($this->payrollModel->processPayroll($periodStart, $periodEnd, $runDate, $totalGross, $wageExpenseAccId, $bankAccId, $_SESSION['user_id'])) {
                $this->logActivity('Payroll Processed', 'HRM', "Processed payroll of Rs: " . number_format($totalGross, 2) . " for period $periodStart to $periodEnd.");
                $_SESSION['flash_success'] = 'Payroll processed and posted to ledger successfully.';
            } else {
                $_SESSION['flash_error'] = 'Failed to process payroll.';
            }
        }
        header('Location: ' . APP_URL . '/payroll');
        exit;
    }
}
