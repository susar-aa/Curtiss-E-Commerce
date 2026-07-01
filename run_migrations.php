<?php
require_once __DIR__ . '/config/database.php';
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $queries = [
        "ALTER TABLE `customers` ADD COLUMN `status` VARCHAR(20) DEFAULT 'active' AFTER `notes`" => "customers.status",
        "ALTER TABLE `item_categories` ADD COLUMN `status` VARCHAR(20) DEFAULT 'active'" => "item_categories.status",
        "ALTER TABLE `mca_areas` ADD COLUMN `status` VARCHAR(20) DEFAULT 'active'" => "mca_areas.status"
    ];
    
    foreach ($queries as $sql => $col) {
        try {
            $pdo->exec($sql);
            echo "Successfully added column for $col.\n";
        } catch (PDOException $e) {
            echo "Column for $col might already exist or failed: " . $e->getMessage() . "\n";
        }
    }
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
}
