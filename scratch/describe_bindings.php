<?php
require 'config/database.php';
require 'core/Database.php';

try {
    $db = new Database();
    echo "=== TABLE: route_bindings ===\n";
    $db->query("DESCRIBE `route_bindings`");
    $rows = $db->resultSet();
    foreach ($rows as $r) {
        echo "  {$r->Field} - {$r->Type} - Null: {$r->Null} - Key: {$r->Key} - Default: {$r->Default}\n";
    }
    
    echo "\n=== RECENT BINDINGS ===\n";
    $db->query("SELECT * FROM route_bindings ORDER BY id DESC LIMIT 5");
    $bindings = $db->resultSet();
    foreach ($bindings as $b) {
        print_r($b);
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
