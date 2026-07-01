<?php
$root = dirname(__DIR__, 3);
require $root . '/config/database.php';
require $root . '/core/Database.php';

$db = new Database();
$db->query("DESCRIBE journal_entries");
$res = $db->resultSet();
foreach ($res as $row) {
    echo $row->Field . " - " . $row->Type . " - " . $row->Null . " - " . $row->Default . "\n";
}
