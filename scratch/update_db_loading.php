<?php
require 'config/database.php';
require 'core/Database.php';

try {
    $db = new Database();
    $db->query("UPDATE rep_daily_routes SET status = 'Loading' WHERE status IN ('Pending Loading', 'Final Loading')");
    $db->execute();
    echo "SUCCESS: Database migrated successfully!\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
