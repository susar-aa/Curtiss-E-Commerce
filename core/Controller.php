<?php
class Controller {
    // Load model
    public function model($model) {
        require_once '../app/Models/' . $model . '.php';
        return new $model();
    }

    // Load view
    public function view($view, $data = []) {
        // Check for view file
        if (file_exists('../app/Views/' . $view . '.php')) {
            require_once '../app/Views/' . $view . '.php';
        } else {
            // View does not exist
            die("View '" . $view . "' does not exist.");
        }
    }

    /**
     * Helper to log system activity
     */
    protected function logActivity($action, $module, $description, $recordId = null, $oldValues = null, $newValues = null) {
        try {
            $userId = $_SESSION['user_id'] ?? 0;
            require_once '../app/Models/AuditLog.php';
            $audit = new AuditLog();
            $audit->logAction($userId, $action, $module, $description, $recordId, $oldValues, $newValues);
        } catch (Exception $e) {
            // Failsafe to avoid crashing the main application flow
        }
    }

    /**
     * Check permissions for module access
     */
    public function checkPermission($module, $action = 'view') {
        // If not logged in, redirect to login
        if (!isset($_SESSION['user_id'])) {
            $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || 
                      (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
            if ($isAjax) {
                header('Content-Type: application/json');
                http_response_code(401);
                echo json_encode([
                    'success' => false,
                    'session_expired' => true,
                    'message' => 'Session Expired. Please login again.'
                ]);
                exit;
            }

            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
            }

            header('Location: ' . APP_URL . '/auth/login');
            exit;
        }

        // Consume centralized RbacService
        require_once __DIR__ . '/RbacService.php';
        $rbac = RbacService::getInstance();
        $hasAccess = $rbac->check($_SESSION['user_id'], $module, $action);

        if (!$hasAccess) {
            // If AJAX request, return a clean JSON or plain error message
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                http_response_code(403);
                echo "Access Denied: You do not have permission to perform this action.";
                exit;
            }

            // Load error layout or page
            $this->view('layouts/main', [
                'title' => 'Access Denied',
                'content_view' => 'auth/access_denied',
                'error_message' => "You do not have permission to " . str_replace('_', ' and ', $action) . " the " . strtoupper($module) . " module. Please contact your system administrator."
            ]);
            exit;
        }
        return true;
    }

    /**
     * Generate a CSRF token if one does not exist.
     * @return string The generated or existing CSRF token.
     */
    protected function generateCsrfToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Validate the CSRF token from request parameters, request headers, or JSON body.
     * @return bool True if valid, false otherwise.
     */
    protected function validateCsrf() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? null;
        if (!$token && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $input = file_get_contents('php://input');
            $json = json_decode($input, true);
            $token = $json['csrf_token'] ?? null;
        }
        return ($token && isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token));
    }

    /**
     * Validate CSRF token or terminate the request with 403.
     */
    protected function validateCsrfOrDie() {
        if (!$this->validateCsrf()) {
            $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || 
                      (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
            if ($isAjax) {
                header('HTTP/1.1 403 Forbidden');
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'success' => false, 'message' => 'CSRF token validation failed.']);
                exit;
            } else {
                http_response_code(403);
                die("CSRF validation failed.");
            }
        }
    }
}