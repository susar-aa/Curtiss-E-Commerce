<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/core/Database.php';

$db = new Database();
$db->query("SELECT item_code, name, qty, quantity_on_hand FROM items LIMIT 20");
$rows = $db->resultSet();
foreach ($rows as $r) {
    echo $r->item_code . " | " . $r->name . " | " . $r->qty . "\n";
}
