<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/core/Database.php';

$db = new Database();
$db->query("DESCRIBE item_variation_options");
foreach ($db->resultSet() as $row) {
    echo "Field: " . $row->Field . " | Type: " . $row->Type . "\n";
}
