<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/core/Database.php';

$db = new Database();
$db->query("SELECT * FROM items WHERE item_code = 'ARTPL2'");
print_r($db->single());
