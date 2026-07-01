<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Database.php';

try {
    $db = new Database();
    $db->query("SELECT id, route_name, status FROM rep_daily_routes ORDER BY id DESC LIMIT 5");
    $routes = $db->resultSet();
    foreach ($routes as $r) {
        echo "ID: {$r->id} | Name: {$r->route_name} | Status: {$r->status}\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
