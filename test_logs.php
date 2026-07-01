<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/core/Database.php';

$db = new Database();
$db->query("SELECT * FROM audit_logs ORDER BY id DESC LIMIT 20");
print_r($db->resultSet());
