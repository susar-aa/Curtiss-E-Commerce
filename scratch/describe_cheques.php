<?php
require_once 'config/database.php';
require_once 'core/Database.php';
$db = new Database();
$db->query("DESCRIBE cheques");
$rows = $db->resultSet();
foreach ($rows as $row) {
    echo "{$row->Field} | {$row->Type} | {$row->Null} | {$row->Key}\n";
}
