<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Database.php';

$db = new Database();
echo "--- ITEMS ---\n";
$db->query("SELECT name, image_path FROM items WHERE image_path IS NOT NULL AND image_path != '' LIMIT 5");
print_r($db->resultSet());

echo "\n--- BANNERS ---\n";
$db->query("SELECT title, image_path FROM ecommerce_banners LIMIT 5");
print_r($db->resultSet());
