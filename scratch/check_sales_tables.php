<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'suzxlabs');
define('DB_PASS', 'Susara@200611003614');
define('DB_NAME', 'curtiss_erp');

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get all tables
    $tablesStmt = $pdo->query("SHOW TABLES");
    $tables = $tablesStmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($tables as $table) {
        $colsStmt = $pdo->query("SHOW COLUMNS FROM `$table`");
        $cols = $colsStmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($cols as $col) {
            $name = strtolower($col['Field']);
            if (strpos($name, 'user_id') !== false || strpos($name, 'employee_id') !== false || strpos($name, 'rep_id') !== false || strpos($name, 'sales_rep') !== false || strpos($name, 'created_by') !== false) {
                echo "Table: $table, Column: {$col['Field']}\n";
            }
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
