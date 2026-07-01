<?php
class PaymentTermController extends Controller {
    private $termModel;

    public function __construct() {
        if (!isset($_SESSION['user_id'])) { header('Location: ' . APP_URL . '/auth/login'); exit; }
        $this->termModel = $this->model('PaymentTerm');
    }

    public function index() {
        $data = [
            'title' => 'Payment Terms',
            'content_view' => 'payment_terms/index',
            'terms' => $this->termModel->getAllTerms(),
            'error' => '',
            'success' => ''
        ];

        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
            if ($_POST['action'] == 'add') {
                $termType = $_POST['term_type'] ?? 'standard';
                $netDueDays = intval($_POST['net_due_days'] ?? 0);
                $netDueDayOfMonth = intval($_POST['net_due_day_of_month'] ?? 31);
                
                // Compatibility mapping: days_due equals net_due_days or Net Due Day of Month
                $daysDue = ($termType === 'standard') ? $netDueDays : $netDueDayOfMonth;

                $termData = [
                    'name' => trim($_POST['name']),
                    'days_due' => $daysDue,
                    'term_type' => $termType,
                    'net_due_days' => $netDueDays,
                    'discount_percent' => floatval($_POST['discount_percent'] ?? 0),
                    'discount_days' => intval($_POST['discount_days'] ?? 0),
                    'net_due_day_of_month' => $netDueDayOfMonth,
                    'due_next_month_within_days' => intval($_POST['due_next_month_within_days'] ?? 5),
                    'discount_day_of_month' => intval($_POST['discount_day_of_month'] ?? 10),
                    'is_inactive' => isset($_POST['is_inactive']) ? 1 : 0
                ];

                if ($this->termModel->addTerm($termData)) {
                    header('Location: ' . APP_URL . '/paymentterm?success=1'); exit;
                } else { $data['error'] = 'Failed to create payment term.'; }
            } elseif ($_POST['action'] == 'edit') {
                $termType = $_POST['term_type'] ?? 'standard';
                $netDueDays = intval($_POST['net_due_days'] ?? 0);
                $netDueDayOfMonth = intval($_POST['net_due_day_of_month'] ?? 31);
                
                // Compatibility mapping: days_due equals net_due_days or Net Due Day of Month
                $daysDue = ($termType === 'standard') ? $netDueDays : $netDueDayOfMonth;

                $termData = [
                    'id' => $_POST['term_id'],
                    'name' => trim($_POST['name']),
                    'days_due' => $daysDue,
                    'term_type' => $termType,
                    'net_due_days' => $netDueDays,
                    'discount_percent' => floatval($_POST['discount_percent'] ?? 0),
                    'discount_days' => intval($_POST['discount_days'] ?? 0),
                    'net_due_day_of_month' => $netDueDayOfMonth,
                    'due_next_month_within_days' => intval($_POST['due_next_month_within_days'] ?? 5),
                    'discount_day_of_month' => intval($_POST['discount_day_of_month'] ?? 10),
                    'is_inactive' => isset($_POST['is_inactive']) ? 1 : 0
                ];

                if ($this->termModel->updateTerm($termData)) {
                    header('Location: ' . APP_URL . '/paymentterm?success=1'); exit;
                } else { $data['error'] = 'Failed to update payment term.'; }
            } elseif ($_POST['action'] == 'delete') {
                if ($this->termModel->deleteTerm($_POST['term_id'])) {
                    header('Location: ' . APP_URL . '/paymentterm?success=1'); exit;
                } else { $data['error'] = 'Failed to delete term. It may be linked to existing invoices.'; }
            }
        }

        if(isset($_GET['success'])) $data['success'] = "Payment Terms updated successfully!";
        
        $this->view('layouts/main', $data);
    }
}