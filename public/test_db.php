<?php
require_once '../config/database.php';
require_once '../core/Database.php';

header('Content-Type: application/json');

try {
    $db = new Database();
    
    // 1. Get all users with username kaveen
    $db->query("SELECT * FROM users WHERE username = 'kaveen'");
    $kaveenUser = $db->resultSet();
    
    // 2. Get all columns of the users table
    $db->query("SHOW COLUMNS FROM users");
    $userColumns = $db->resultSet();
    
    // 3. Get all columns of the employees table
    $db->query("SHOW COLUMNS FROM employees");
    $employeeColumns = $db->resultSet();
    
    // 4. Run the user details query with error checking
    $db->query("SELECT u.id, u.username, u.role, u.employee_id, u.email,
                       CONCAT(e.first_name, ' ', e.last_name) as full_name
                 FROM users u
                 LEFT JOIN employees e ON u.employee_id = e.id");
    $testJoin = $db->resultSet();
    
    // 5. Active deliveries
    $db->query("SELECT * FROM deliveries WHERE status != 'Completed'");
    $activeDeliveries = $db->resultSet();
    
    echo json_encode([
        'kaveen_user' => $kaveenUser,
        'user_columns' => $userColumns,
        'employee_columns' => $employeeColumns,
        'active_deliveries' => $activeDeliveries,
        'test_join_count' => count($testJoin)
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode([
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
