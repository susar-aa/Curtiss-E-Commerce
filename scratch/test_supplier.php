<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'suzxlabs');
define('DB_PASS', 'Susara@200611003614');
define('DB_NAME', 'curtiss_erp');

require_once 'core/Database.php';
require_once 'app/Models/Supplier.php';

try {
    $model = new Supplier();
    
    // Find the first vendor
    $db = new Database();
    $db->query("SELECT id, name FROM vendors LIMIT 1");
    $vendor = $db->single();
    if (!$vendor) {
        die("No vendors found in database.\n");
    }
    
    echo "Testing Vendor: {$vendor->name} (ID: {$vendor->id})\n";
    
    echo "\n1. Testing stats:\n";
    $stats = $model->getSupplierStats($vendor->id);
    print_r($stats);
    
    echo "\n2. Testing Activity Ledger:\n";
    $ledger = $model->getActivityLedger($vendor->id);
    print_r($ledger);
    
    echo "\n3. Testing POs:\n";
    $pos = $model->getSupplierPOs($vendor->id);
    print_r($pos);
    
    echo "\n4. Testing Products:\n";
    $products = $model->getSupplierProducts($vendor->id);
    print_r($products);
    
    echo "\nSUCCESS!\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "TRACE:\n" . $e->getTraceAsString() . "\n";
}
