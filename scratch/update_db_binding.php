<?php
require 'config/database.php';
require 'core/Database.php';

try {
    $db = new Database();
    
    // Add columns to route_bindings table
    $cols_rb = [
        'created_by' => 'INT NULL',
        'undo_by' => 'INT NULL',
        'undo_at' => 'TIMESTAMP NULL',
        'snapshot' => 'LONGTEXT NULL'
    ];
    foreach ($cols_rb as $col => $type) {
        try {
            $db->query("SHOW COLUMNS FROM route_bindings LIKE :col");
            $db->bind(':col', $col);
            if (!$db->single()) {
                $db->query("ALTER TABLE route_bindings ADD COLUMN `$col` $type");
                $db->execute();
                echo "Added column $col to route_bindings.\n";
            } else {
                echo "Column $col already exists in route_bindings.\n";
            }
        } catch (Exception $e) {
            echo "Error adding $col to route_bindings: " . $e->getMessage() . "\n";
        }
    }
    
    // Add columns to rep_daily_routes table
    $cols_r = [
        'bound_to_route_id' => 'INT NULL',
        'is_merged_route' => 'TINYINT(1) NOT NULL DEFAULT 0'
    ];
    foreach ($cols_r as $col => $type) {
        try {
            $db->query("SHOW COLUMNS FROM rep_daily_routes LIKE :col");
            $db->bind(':col', $col);
            if (!$db->single()) {
                $db->query("ALTER TABLE rep_daily_routes ADD COLUMN `$col` $type");
                $db->execute();
                echo "Added column $col to rep_daily_routes.\n";
            } else {
                echo "Column $col already exists in rep_daily_routes.\n";
            }
        } catch (Exception $e) {
            echo "Error adding $col to rep_daily_routes: " . $e->getMessage() . "\n";
        }
    }
    
    echo "DB Schema update completed successfully!\n";
} catch (Exception $e) {
    echo "DB Connection Error: " . $e->getMessage() . "\n";
}
