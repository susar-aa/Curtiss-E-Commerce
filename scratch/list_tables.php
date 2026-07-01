<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Database.php';

$db = new Database();
$db->query("SHOW TABLES");
foreach ($db->resultSet() as $row) {
    echo array_values((array)$row)[0] . "\n";
}
