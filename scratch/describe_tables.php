<?php
require 'config/database.php';
require 'core/Database.php';

try {
    $db = new Database();
    $tables = ['rep_daily_routes', 'invoices', 'deliveries', 'delivery_picking_items'];
    foreach ($tables as $t) {
        echo "=== TABLE: $t ===\n";
        try {
            $db->query("DESCRIBE `$t`");
            $rows = $db->resultSet();
            foreach ($rows as $r) {
                echo "  {$r->Field} - {$r->Type} - Null: {$r->Null} - Key: {$r->Key} - Default: {$r->Default}\n";
            }
        } catch (Exception $e) {
            echo "  Error: " . $e->getMessage() . "\n";
        }
        echo "\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
