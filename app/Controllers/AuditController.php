<?php
class AuditController extends Controller {
    private $auditModel;
    private $userModel;

    public function __construct() {
        // Must be logged in
        if (!isset($_SESSION['user_id'])) { header('Location: ' . APP_URL . '/auth/login'); exit; }
        
        // STRICT RBAC: Only Admins can view the Audit Trail
        if ($_SESSION['role'] !== 'Admin') {
            die("Access Denied: Only Administrators can view the System Audit Logs.");
        }
        
        $this->auditModel = $this->model('AuditLog');
        $this->userModel = $this->model('User');
    }

    public function index() {
        $filters = [
            'user_id' => $_GET['user_id'] ?? '',
            'module' => $_GET['module'] ?? '',
            'action' => $_GET['action'] ?? '',
            'date_from' => $_GET['date_from'] ?? '',
            'date_to' => $_GET['date_to'] ?? '',
            'search' => $_GET['search'] ?? '',
        ];

        $logs = $this->auditModel->getFilteredLogs($filters, 250);

        $data = [
            'title' => 'System Audit Logs',
            'content_view' => 'audits/index',
            'logs' => $logs,
            'users' => $this->userModel->getAllUsers(),
            'modules' => $this->auditModel->getUniqueModules(),
            'actions' => $this->auditModel->getUniqueActions(),
            'filters' => $filters
        ];

        $this->view('layouts/main', $data);
    }
}