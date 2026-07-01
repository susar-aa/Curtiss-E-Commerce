<?php
class App {
    protected $controller = 'DashboardController';
    protected $method = 'index';
    protected $params = [];

    public function __construct() {
        $url = $this->parseUrl();

        // Return 204 No Content for favicon.ico requests to prevent routing errors
        if (isset($url[0]) && strtolower($url[0]) === 'favicon.ico') {
            http_response_code(204);
            return;
        }

        // Strip the mobile 'rep' or 'driver' prefix if present
        $isMobileApi = false;
        $mobilePrefix = '';
        if (isset($url[0]) && (strtolower($url[0]) === 'rep' || strtolower($url[0]) === 'driver')) {
            $isMobileApi = true;
            $mobilePrefix = ucfirst(strtolower($url[0]));
            array_shift($url);
        }

        // Check if this is a stateless Mobile API sync request (bypasses CSRF only when no active web session exists)
        $isMobileSync = ($isMobileApi || isset($_GET['api_sync'])) && !isset($_SESSION['user_id']);

        // Global CSRF Protection
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isMobileSync) {
            $token = $_POST['csrf_token'] ?? '';
            if (empty($token) && isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
                $token = $_SERVER['HTTP_X_CSRF_TOKEN'];
            }
            if (empty($token)) {
                $input = file_get_contents('php://input');
                $json = json_decode($input, true);
                $token = $json['csrf_token'] ?? '';
            }
            if (empty($token) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
                $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || 
                          (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
                if ($isAjax) {
                    header('Content-Type: application/json');
                    http_response_code(403);
                    echo json_encode([
                        'success' => false,
                        'message' => 'CSRF token validation failed. Please refresh the page and try again.'
                    ]);
                    exit;
                } else {
                    http_response_code(403);
                    die("<!DOCTYPE html>
                    <html>
                    <head>
                        <title>Security Mismatch</title>
                        <style>
                            body { font-family: -apple-system, sans-serif; background: #f0f2f5; color: #333; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
                            .card { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); max-width: 400px; text-align: center; }
                            h3 { color: #ff3b30; margin-top: 0; }
                            p { font-size: 14px; color: #666; line-height: 1.5; }
                            .btn { display: inline-block; background: #0066cc; color: white; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-size: 14px; margin-top: 15px; }
                        </style>
                    </head>
                    <body>
                        <div class='card'>
                            <h3>Security Validation Failed</h3>
                            <p>Your session may have expired, or the request was flagged as unauthorized (CSRF Mismatch).</p>
                            <p>Please go back, refresh the page, and try again.</p>
                            <a href='javascript:history.back()\' class=\'btn\'>Go Back</a>
                        </div>
                    </body>
                    </html>");
                }
            }
        }

        // Allow public access to sales/show
        $isPublicInvoice = false;
        if (isset($url[0]) && strtolower($url[0]) === 'sales' && isset($url[1]) && strtolower($url[1]) === 'show' && isset($url[2])) {
            $isPublicInvoice = true;
        }

        // Identify if current controller is public (doesn't require ERP Employee login)
        $publicControllers = ['auth', 'shop', 'portal'];
        $currentController = isset($url[0]) ? strtolower($url[0]) : '';
        $isPublicController = in_array($currentController, $publicControllers);

        // Check if user is logged in. If not, force routing to AuthController (unless it is auth controller, public controllers, an API sync request, or a public invoice view)
        if (!isset($_SESSION['user_id']) && !$isMobileSync && !$isPublicInvoice && !$isPublicController) {
            $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || 
                      (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) ||
                      (strpos($_SERVER['REQUEST_URI'], '/fetch_data') !== false) ||
                      (strpos($_SERVER['REQUEST_URI'], '/quick_view') !== false);

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

            $this->controller = 'AuthController';
            $this->method = 'login';
        } else {

            if (isset($url[0])) {
                $cleanControllerName = str_replace('-', '', ucwords($url[0], '-'));
                if (strtolower($cleanControllerName) === 'reports') {
                    $cleanControllerName = 'Report';
                }
                
                // Case-insensitive controller file matching for cross-platform compatibility (Linux / Plesk)
                $matchedController = null;
                $controllersDir = '../app/Controllers/';
                if (is_dir($controllersDir)) {
                    $files = scandir($controllersDir);
                    
                    // 1. Try prepending the mobile prefix if applicable (e.g. DriverDashboardController)
                    if ($isMobileApi && !empty($mobilePrefix)) {
                        $prefixedControllerName = $mobilePrefix . $cleanControllerName . 'Controller';
                        $targetLowerPrefixed = strtolower($prefixedControllerName . '.php');
                        foreach ($files as $file) {
                            if (strtolower($file) === $targetLowerPrefixed) {
                                $matchedController = pathinfo($file, PATHINFO_FILENAME);
                                break;
                            }
                        }
                    }
                    
                    // 2. Fallback to normal controller name if no prefixed controller matches
                    if ($matchedController === null) {
                        $controllerName = $cleanControllerName . 'Controller';
                        $targetLower = strtolower($controllerName . '.php');
                        foreach ($files as $file) {
                            if (strtolower($file) === $targetLower) {
                                $matchedController = pathinfo($file, PATHINFO_FILENAME);
                                break;
                            }
                        }
                    }
                }

                if ($matchedController !== null) {
                    $this->controller = $matchedController;
                    unset($url[0]);
                } else {
                    $controllerName = $cleanControllerName . 'Controller';
                    // Force an error to show exactly what file is missing
                    die("<div style='padding:20px; font-family:sans-serif; color:red;'>
                            <h3>MVC Routing Error 404</h3>
                            <p>The router is looking for a file that does not exist.</p>
                            <p>Missing File: <strong>app/Controllers/" . $controllerName . ".php</strong></p>
                         </div>");
                }
            }
        }

        // Require the controller
        require_once '../app/Controllers/' . $this->controller . '.php';

        // Instantiate controller class
        $this->controller = new $this->controller;

        // Check for second part of url (method)
        if (isset($url[1])) {
            if (method_exists($this->controller, $url[1])) {
                $this->method = $url[1];
                unset($url[1]);
            }
        }

        // Get params
        $this->params = $url ? array_values($url) : [];

        // Call a callback with array of params
        call_user_func_array([$this->controller, $this->method], $this->params);
    }

    public function parseUrl() {
        if (isset($_GET['url'])) {
            return explode('/', filter_var(rtrim($_GET['url'], '/'), FILTER_SANITIZE_URL));
        }
        return [];
    }
}