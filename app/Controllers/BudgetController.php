<?php
class BudgetController extends Controller {
    private $budgetModel;

    public function __construct() {
        if (!isset($_SESSION['user_id'])) { header('Location: ' . APP_URL . '/auth/login'); exit; }
        $this->budgetModel = $this->model('Budget');
    }

    public function index() {
        // Default to the current year
        $currentYear = date('Y');

        $data = [
            'title' => 'Budgeting & Variance',
            'content_view' => 'budgets/index',
            'year' => $currentYear,
            'budgets' => $this->budgetModel->getExpenseBudgets($currentYear),
            'error' => '',
            'success' => ''
        ];

        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_budget') {
            $accountId = $_POST['account_id'];
            $amount = floatval($_POST['budget_amount']);
            
            if ($this->budgetModel->setBudget($accountId, $currentYear, $amount)) {
                $data['success'] = "Budget limit updated successfully.";
                $data['budgets'] = $this->budgetModel->getExpenseBudgets($currentYear); // Refresh data
            } else {
                $data['error'] = "Database Error: Failed to update budget.";
            }
        }

        $this->view('layouts/main', $data);
    }
}