<?php
class BackupController extends Controller {
    private $db;

    public function __construct() {
        if (!isset($_SESSION['user_id'])) { header('Location: ' . APP_URL . '/auth/login'); exit; }
        // Restrict to Admin only for security reasons
        if ($_SESSION['role'] !== 'Admin') {
            die("Access Denied: Only System Administrators can access backup and restore tools.");
        }
        $this->db = new Database();
    }

    public function index() {
        $backupDir = '../writable/backups/';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0777, true);
        }

        $files = [];
        if ($handle = opendir($backupDir)) {
            while (false !== ($entry = readdir($handle))) {
                if ($entry != "." && $entry != ".." && pathinfo($entry, PATHINFO_EXTENSION) == 'sql') {
                    $files[] = [
                        'filename' => $entry,
                        'size' => filesize($backupDir . $entry),
                        'date' => filemtime($backupDir . $entry)
                    ];
                }
            }
            closedir($handle);
        }

        // Sort files by date descending
        usort($files, function($a, $b) {
            return $b['date'] - $a['date'];
        });

        $data = [
            'title' => 'Backup & Restore',
            'content_view' => 'admin/backup',
            'files' => $files,
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

    public function generate() {
        try {
            $pdo = $this->db->getDbHandler();
            
            // Get all tables
            $tables = [];
            $result = $pdo->query("SHOW TABLES");
            while ($row = $result->fetch(PDO::FETCH_NUM)) {
                $tables[] = $row[0];
            }

            $sql = "-- Curtiss ERP System Backup\n";
            $sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
            $sql .= "-- Database: " . DB_NAME . "\n\n";
            $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

            foreach ($tables as $table) {
                // Get table structure
                $structResult = $pdo->query("SHOW CREATE TABLE `$table`");
                $structRow = $structResult->fetch(PDO::FETCH_NUM);
                $sql .= "DROP TABLE IF EXISTS `$table`;\n";
                $sql .= $structRow[1] . ";\n\n";

                // Get table data
                $dataResult = $pdo->query("SELECT * FROM `$table`");
                $columnCount = $dataResult->columnCount();

                while ($row = $dataResult->fetch(PDO::FETCH_NUM)) {
                    $sql .= "INSERT INTO `$table` VALUES(";
                    for ($j = 0; $j < $columnCount; $j++) {
                        if (isset($row[$j])) {
                            // Escape strings
                            $escaped = str_replace(array("\x00", "\n", "\r", "\\", "'", "\x1a"), array('\\0', '\\n', '\\r', '\\\\', "\\'", '\\Z'), $row[$j]);
                            $sql .= "'" . $escaped . "'";
                        } else {
                            $sql .= "NULL";
                        }
                        if ($j < ($columnCount - 1)) {
                            $sql .= ",";
                        }
                    }
                    $sql .= ");\n";
                }
                $sql .= "\n";
            }

            $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

            $backupDir = '../writable/backups/';
            if (!is_dir($backupDir)) {
                mkdir($backupDir, 0777, true);
            }

            $filename = 'backup_' . DB_NAME . '_' . date('Ymd_His') . '.sql';
            file_put_contents($backupDir . $filename, $sql);

            $this->logActivity('System Backup Generated', 'System', "Backup filename: $filename");
            $_SESSION['flash_success'] = "Backup file $filename generated successfully.";

        } catch (Exception $e) {
            $_SESSION['flash_error'] = "Backup failed: " . $e->getMessage();
        }

        header('Location: ' . APP_URL . '/backup');
        exit;
    }

    public function download($filename) {
        // Prevent directory traversal
        $filename = basename($filename);
        $filePath = '../writable/backups/' . $filename;

        if (file_exists($filePath) && pathinfo($filePath, PATHINFO_EXTENSION) == 'sql') {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($filePath));
            flush();
            readfile($filePath);
            exit;
        } else {
            $_SESSION['flash_error'] = "Backup file not found.";
            header('Location: ' . APP_URL . '/backup');
            exit;
        }
    }

    public function delete($filename) {
        $filename = basename($filename);
        $filePath = '../writable/backups/' . $filename;

        if (file_exists($filePath) && pathinfo($filePath, PATHINFO_EXTENSION) == 'sql') {
            unlink($filePath);
            $this->logActivity('System Backup Deleted', 'System', "Deleted backup file: $filename");
            $_SESSION['flash_success'] = "Backup file deleted successfully.";
        } else {
            $_SESSION['flash_error'] = "Backup file not found.";
        }
        header('Location: ' . APP_URL . '/backup');
        exit;
    }

    public function restore() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $pdo = $this->db->getDbHandler();
            $sqlContent = '';

            // Handle file upload or server file restoration
            if (isset($_POST['server_file']) && !empty($_POST['server_file'])) {
                $filename = basename($_POST['server_file']);
                $filePath = '../writable/backups/' . $filename;
                if (file_exists($filePath) && pathinfo($filePath, PATHINFO_EXTENSION) == 'sql') {
                    $sqlContent = file_get_contents($filePath);
                } else {
                    $_SESSION['flash_error'] = "Server backup file not found.";
                    header('Location: ' . APP_URL . '/backup');
                    exit;
                }
            } elseif (isset($_FILES['backup_file']) && $_FILES['backup_file']['error'] === UPLOAD_ERR_OK) {
                $fileTmpPath = $_FILES['backup_file']['tmp_name'];
                $fileName = $_FILES['backup_file']['name'];
                if (strtolower(pathinfo($fileName, PATHINFO_EXTENSION)) == 'sql') {
                    $sqlContent = file_get_contents($fileTmpPath);
                } else {
                    $_SESSION['flash_error'] = "Invalid file type. Only SQL files are supported.";
                    header('Location: ' . APP_URL . '/backup');
                    exit;
                }
            } else {
                $_SESSION['flash_error'] = "Please select a backup file to restore.";
                header('Location: ' . APP_URL . '/backup');
                exit;
            }

            if (!empty($sqlContent)) {
                try {
                    // Temporarily disable foreign keys and execute queries
                    $pdo->exec("SET FOREIGN_KEY_CHECKS=0;");
                    
                    // Execute the entire SQL script
                    $pdo->exec($sqlContent);
                    
                    $pdo->exec("SET FOREIGN_KEY_CHECKS=1;");
                    
                    $this->logActivity('System Restored', 'System', "Database restored successfully.");
                    $_SESSION['flash_success'] = "Database restored successfully!";
                } catch (Exception $e) {
                    $_SESSION['flash_error'] = "Restore failed: " . $e->getMessage();
                }
            }
        }
        header('Location: ' . APP_URL . '/backup');
        exit;
    }
}
