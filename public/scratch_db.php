<?php
require_once '../config/database.php';
require_once '../core/Database.php';

$db = new Database();

echo "=== DAILY ROUTES (IMPORTED DB) ===\n";
$db->query("SELECT id, route_name, status FROM rep_daily_routes");
$routes = $db->resultSet();
print_r($routes);

echo "\n=== DELIVERIES ===\n";
$db->query("SELECT id, rep_route_id, delivery_date, vehicle_number, driver_name, status FROM deliveries");
$deliveries = $db->resultSet();
print_r($deliveries);
