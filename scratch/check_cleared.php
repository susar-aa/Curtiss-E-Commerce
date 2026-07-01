<?php
require 'c:\\xampp\\htdocs\\Curtiss-ERP\\config\\database.php';
require 'c:\\xampp\\htdocs\\Curtiss-ERP\\core\\Database.php';
$db = new Database();
try {
    $db->query("SHOW TABLES LIKE 'financial_years'");
    $res = $db->resultSet();
    if (empty($res)) {
        echo "financial_years does NOT exist\n";
    } else {
        echo "financial_years exists\n";
        $db->query("DESCRIBE financial_years");
        print_r($db->resultSet());
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
