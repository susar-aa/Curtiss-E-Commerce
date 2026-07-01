<?php
class PerformanceController extends Controller {
    private $performanceModel;
    private $employeeModel;

    public function __construct() {
        if (!isset($_SESSION['user_id'])) { header('Location: ' . APP_URL . '/auth/login'); exit; }
        $this->performanceModel = $this->model('Performance');
        $this->employeeModel = $this->model('Employee');
    }

    public function index() {
        $this->checkPermission('hrm', 'view');

        $data = [
            'title' => 'Performance Reviews',
            'content_view' => 'hrm/performance',
            'employees' => $this->employeeModel->getActiveEmployees(),
            'reviews' => $this->performanceModel->getAllPerformanceReviews(),
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

    public function create() {
        $this->checkPermission('hrm', 'create_edit');
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $reviewData = [
                'employee_id' => intval($_POST['employee_id']),
                'reviewer_id' => $_SESSION['user_id'],
                'review_date' => $_POST['review_date'],
                'rating' => intval($_POST['rating']),
                'feedback' => trim($_POST['feedback'] ?? '')
            ];

            if ($this->performanceModel->addPerformanceReview($reviewData)) {
                $emp = $this->employeeModel->getEmployeeById($reviewData['employee_id']);
                $empName = $emp ? ($emp->first_name . ' ' . $emp->last_name) : 'ID: ' . $reviewData['employee_id'];
                $this->logActivity('Performance Review Added', 'HRM', "Added performance review for $empName with rating {$reviewData['rating']}/5.");
                $_SESSION['flash_success'] = 'Performance review submitted successfully.';
            } else {
                $_SESSION['flash_error'] = 'Failed to submit performance review.';
            }
        }
        header('Location: ' . APP_URL . '/performance');
        exit;
    }

    public function delete($id) {
        $this->checkPermission('hrm', 'delete');
        if ($this->performanceModel->deletePerformanceReview($id)) {
            $this->logActivity('Performance Review Deleted', 'HRM', "Deleted performance review ID: $id.");
            $_SESSION['flash_success'] = 'Performance review deleted successfully.';
        } else {
            $_SESSION['flash_error'] = 'Failed to delete performance review.';
        }
        header('Location: ' . APP_URL . '/performance');
        exit;
    }
}
