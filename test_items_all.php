<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/Controller.php';
require_once __DIR__ . '/app/Models/Item.php';

$itemModel = new Item();
$items = $itemModel->getItems();
$has_stock_count = 0;
foreach ($items as $item) {
    if ($item->qty > 0) {
        echo "SKU: " . $item->item_code . " | Name: " . $item->name . " | qty: " . $item->qty . " | quantity_on_hand: " . $item->quantity_on_hand . "\n";
        $has_stock_count++;
    }
}
echo "Total items with qty > 0: " . $has_stock_count . " out of " . count($items) . "\n";
