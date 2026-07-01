<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/Controller.php';
require_once __DIR__ . '/app/Models/Item.php';

$db = new Database();
$db->query("SELECT id, item_code, barcode FROM items WHERE item_code LIKE '%APPAS6%' OR barcode LIKE '%APPAS6%'");
$rows = $db->resultSet();
print_r($rows);
