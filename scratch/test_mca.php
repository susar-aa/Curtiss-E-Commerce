<?php
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/core/Database.php';

$db = new Database();

$db->query("SELECT DISTINCT route_name FROM rep_daily_routes LIMIT 10");
echo "Sample route names:\n";
foreach ($db->resultSet() as $row) {
    echo "- " . $row->route_name . "\n";
}

$db->query("SELECT DISTINCT name FROM mca_areas LIMIT 10");
echo "\nSample MCA area names:\n";
foreach ($db->resultSet() as $row) {
    echo "- " . $row->name . "\n";
}

$db->query("SELECT r.id, r.route_name, m.name as mca_name 
            FROM rep_daily_routes r 
            LEFT JOIN mca_areas m ON r.route_name = m.name 
            LIMIT 5");
echo "\nJoined check:\n";
foreach ($db->resultSet() as $row) {
    echo "Route ID: {$row->id} | Route Name: {$row->route_name} | MCA Name: {$row->mca_name}\n";
}
