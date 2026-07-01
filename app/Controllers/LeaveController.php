<?php
class LeaveController extends Controller {
    private $leaveModel;
    private $employeeModel;

    public function __construct() {
        if (!isset($_SESSION['user_id'])) { header('Location: ' . APP_URL . '/auth/login'); exit; }
        $this->leaveModel = $this->model('Leave');
        $this->employeeModel = $this->model('Employee');
    }

    public function index() {
        $this->checkPermission('hrm', 'view');

        $data = [
            'title' => 'Leave Management',
            'content_view' => 'hrm/leaves',
            'employees' => $this->employeeModel->getActiveEmployees(),
            'leave_requests' => $this->leaveModel->getAllLeaveRequests(),
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
            $leaveData = [
                'employee_id' => intval($_POST['employee_id']),
                'leave_type' => trim($_POST['leave_type']),
                'start_date' => $_POST['start_date'],
                'end_date' => $_POST['end_date'],
                'reason' => trim($_POST['reason'] ?? '')
            ];

            if ($this->leaveModel->addLeaveRequest($leaveData)) {
                $emp = $this->employeeModel->getEmployeeById($leaveData['employee_id']);
                $empName = $emp ? ($emp->first_name . ' ' . $emp->last_name) : 'ID: ' . $leaveData['employee_id'];
                $this->logActivity('Leave Requested', 'HRM', "Requested {$leaveData['leave_type']} leave for $empName from {$leaveData['start_date']} to {$leaveData['end_date']}.");
                $_SESSION['flash_success'] = 'Leave request submitted successfully.';
            } else {
                $_SESSION['flash_error'] = 'Failed to submit leave request.';
            }
        }
        header('Location: ' . APP_URL . '/leave');
        exit;
    }

    public function approve($id) {
        $this->checkPermission('hrm', 'create_edit');
        if ($this->leaveModel->updateLeaveStatus($id, 'Approved')) {
            $this->logActivity('Leave Approved', 'HRM', "Approved leave request ID: $id.");
            $_SESSION['flash_success'] = 'Leave request approved successfully.';
        } else {
            $_SESSION['flash_error'] = 'Failed to approve leave request.';
        }
        header('Location: ' . APP_URL . '/leave');
        exit;
    }

    public function reject($id) {
        $this->checkPermission('hrm', 'create_edit');
        if ($this->leaveModel->updateLeaveStatus($id, 'Rejected')) {
            $this->logActivity('Leave Rejected', 'HRM', "Rejected leave request ID: $id.");
            $_SESSION['flash_success'] = 'Leave request rejected successfully.';
        } else {
            $_SESSION['flash_error'] = 'Failed to reject leave request.';
        }
        header('Location: ' . APP_URL . '/leave');
        exit;
    }

    public function delete($id) {
        $this->checkPermission('hrm', 'delete');
        if ($this->leaveModel->deleteLeaveRequest($id)) {
            $this->logActivity('Leave Deleted', 'HRM', "Deleted leave request ID: $id.");
            $_SESSION['flash_success'] = 'Leave request deleted successfully.';
        } else {
            $_SESSION['flash_error'] = 'Failed to delete leave request.';
        }
        header('Location: ' . APP_URL . '/leave');
        exit;
    }
}
