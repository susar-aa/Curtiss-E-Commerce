<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/core/Database.php';

$db = new Database();
$db->query("SELECT COUNT(*) AS total FROM items");
echo "Total items: " . $db->single()->total . "\n";

$db->query("SELECT item_code, qty, quantity_on_hand FROM items LIMIT 10");
print_r($db->resultSet());
