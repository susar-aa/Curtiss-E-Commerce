<?php
require 'config/database.php';
require 'core/Database.php';

try {
    $db = new Database();
    $db->query("SELECT TABLE_NAME, COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = :db AND (COLUMN_NAME LIKE '%route_id%' OR COLUMN_NAME LIKE '%rep_route_id%' OR COLUMN_NAME LIKE '%delivery_id%')");
    $db->bind(':db', DB_NAME);
    $cols = $db->resultSet();
    foreach ($cols as $c) {
        echo "Table: {$c->TABLE_NAME} -> Column: {$c->COLUMN_NAME}\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
