<?php
require_once 'config/database.php';
require_once 'core/Database.php';
$db = new Database();
$db->query("SELECT id, account_code, account_name, account_type FROM chart_of_accounts WHERE account_type = 'Asset'");
$rows = $db->resultSet();
foreach ($rows as $row) {
    echo "ID: {$row->id} | Code: {$row->account_code} | Name: {$row->account_name}\n";
}
