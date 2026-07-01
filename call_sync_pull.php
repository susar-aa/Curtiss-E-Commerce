<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/Controller.php';
require_once __DIR__ . '/app/Controllers/RepDashboardController.php';
require_once __DIR__ . '/app/Models/Item.php';
require_once __DIR__ . '/app/Models/User.php';
require_once __DIR__ . '/app/Models/Customer.php';
require_once __DIR__ . '/app/Models/RepTracking.php';
require_once __DIR__ . '/app/Models/DiscountRule.php';
require_once __DIR__ . '/app/Models/Invoice.php';

$_GET['user_id'] = 15;
$_GET['last_sync_timestamp'] = '';

ob_start();
$ctrl = new RepDashboardController();
$ctrl->sync_pull();
$output = ob_get_clean();

$data = json_decode($output, true);
if (isset($data['products'])) {
    echo "Total products in sync_pull: " . count($data['products']) . "\n";
    foreach ($data['products'] as $p) {
        if ($p['sku'] === 'ARTPL2') {
            print_r($p);
        }
    }
} else {
    echo "No products found in response!\n";
    echo "Raw response: " . substr($output, 0, 500) . "\n";
}
