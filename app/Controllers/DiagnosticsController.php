<?php
class DiagnosticsController extends Controller {
    private $db;

    public function __construct() {
        if (!isset($_SESSION['user_id'])) { header('Location: ' . APP_URL . '/auth/login'); exit; }
        // Restrict to Admin only for security reasons
        if ($_SESSION['role'] !== 'Admin') {
            die("Access Denied: Only System Administrators can access diagnostic metrics.");
        }
        $this->db = new Database();
    }

    public function index() {
        $pdo = $this->db->getDbHandler();

        // 1. MySQL Version & Statistics
        $mysqlVersion = 'N/A';
        try {
            $stmt = $pdo->query("SELECT VERSION()");
            $mysqlVersion = $stmt->fetchColumn();
        } catch (Exception $e) {}

        $dbSize = 0;
        try {
            $stmt = $pdo->prepare("SELECT SUM(data_length + index_length) AS db_size 
                                   FROM information_schema.TABLES 
                                   WHERE table_schema = :db_name");
            $stmt->execute([':db_name' => DB_NAME]);
            $dbSize = $stmt->fetchColumn() ?: 0;
        } catch (Exception $e) {}

        $tableCount = 0;
        try {
            $stmt = $pdo->query("SHOW TABLES");
            $tableCount = $stmt->rowCount();
        } catch (Exception $e) {}

        // 2. Disk Space Metrics
        $diskFree = @disk_free_space(".") ?: 0;
        $diskTotal = @disk_total_space(".") ?: 0;
        $diskUsed = $diskTotal - $diskFree;
        $diskPercentage = $diskTotal > 0 ? round(($diskUsed / $diskTotal) * 100, 2) : 0;

        // 3. Writable Folder Verifications
        $folders = [
            'App Root' => '../',
            'Writable Backups' => '../writable/backups/',
            'Writable Cache' => '../writable/cache/',
            'Public Uploads' => '../public/uploads/'
        ];
        $folderStatuses = [];
        foreach ($folders as $name => $path) {
            $folderStatuses[$name] = [
                'path' => realpath($path) ?: $path,
                'exists' => is_dir($path),
                'writable' => is_writable($path)
            ];
        }

        // 4. Critical PHP Extensions check
        $extensions = ['pdo_mysql', 'curl', 'mbstring', 'openssl', 'gd', 'zip', 'json', 'xml'];
        $extStatuses = [];
        foreach ($extensions as $ext) {
            $extStatuses[$ext] = extension_loaded($ext);
        }

        $data = [
            'title' => 'System Diagnostics & Health',
            'content_view' => 'admin/diagnostics',
            'server_info' => [
                'os' => PHP_OS,
                'php_version' => PHP_VERSION,
                'web_server' => $_SERVER['SERVER_SOFTWARE'] ?? 'N/A',
                'mysql_version' => $mysqlVersion,
                'db_name' => DB_NAME,
                'db_size' => $this->formatBytes($dbSize),
                'table_count' => $tableCount
            ],
            'php_settings' => [
                'memory_limit' => ini_get('memory_limit'),
                'max_execution_time' => ini_get('max_execution_time') . ' seconds',
                'upload_max_filesize' => ini_get('upload_max_filesize'),
                'post_max_size' => ini_get('post_max_size')
            ],
            'disk_metrics' => [
                'free' => $this->formatBytes($diskFree),
                'total' => $this->formatBytes($diskTotal),
                'used' => $this->formatBytes($diskUsed),
                'percentage' => $diskPercentage
            ],
            'folders' => $folderStatuses,
            'extensions' => $extStatuses
        ];

        $this->view('layouts/main', $data);
    }

    private function formatBytes($bytes, $precision = 2) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
