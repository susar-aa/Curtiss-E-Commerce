<?php
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'test_admin';
$_SESSION['role'] = 'admin';

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../app/Controllers/RepTrackingController.php';

$controller = new RepTrackingController();
$routeId = 54;

$endpoints = [
    'api_get_route_details' => [$routeId],
    'api_get_route_path' => [$routeId],
    'api_get_outstanding_bills' => [$routeId],
    'api_get_route_variances' => [$routeId],
    'api_get_route_collections' => [$routeId],
];

foreach ($endpoints as $method => $args) {
    echo "=== Testing $method ===\n";
    ob_start();
    try {
        call_user_func_array([$controller, $method], $args);
    } catch (Exception $e) {
        echo "Exception: " . $e->getMessage() . "\n";
    }
    $output = ob_get_clean();
    echo "Output:\n$output\n\n";
}
