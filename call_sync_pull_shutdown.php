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

register_shutdown_function(function() {
    $output = ob_get_clean();
    $data = json_decode($output, true);
    if (isset($data['products'])) {
        echo "\n\n=== FILTERED SHUTDOWN RESULTS ===\n";
        echo "Total products in sync_pull: " . count($data['products']) . "\n";
        $found = false;
        foreach ($data['products'] as $p) {
            if ($p['sku'] === 'BDIPC25') {
                print_r($p);
                $found = true;
            }
        }
        if (!$found) {
            echo "BDIPC25 not found in sync_pull products list!\n";
        }
    } else {
        echo "\n\n=== NO PRODUCTS FOUND ===\n";
        echo "Raw length: " . strlen($output) . "\n";
        echo "Snippet: " . substr($output, 0, 500) . "\n";
    }
});

$ctrl = new RepDashboardController();
$ctrl->sync_pull();
