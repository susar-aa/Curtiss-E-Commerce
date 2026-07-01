<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/core/Database.php';

$db = new Database();
$db->query("SELECT id, username, role, status FROM users");
print_r($db->resultSet());
