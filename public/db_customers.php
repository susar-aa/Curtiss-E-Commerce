<?php
define('APPROOT', dirname(dirname(__FILE__)));
chdir(APPROOT . '/public');
require_once APPROOT . '/config/database.php';
require_once APPROOT . '/core/Database.php';

$db = new Database();
$db->query("SELECT id, name, phone FROM customers ORDER BY id DESC LIMIT 50");
$customers = $db->resultSet();

echo json_encode($customers, JSON_PRETTY_PRINT) . "\n";
