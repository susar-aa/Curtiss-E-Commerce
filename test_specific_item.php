<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/Controller.php';
require_once __DIR__ . '/app/Models/Item.php';

$itemModel = new Item();
$item = $itemModel->getItemByCode('APPAS6');
if ($item) {
    echo "SKU: " . $item->item_code . "\n";
    echo "Name: " . $item->name . "\n";
    echo "qty: " . $item->qty . "\n";
    echo "quantity_on_hand: " . $item->quantity_on_hand . "\n";
} else {
    echo "Item APPAS6 not found!\n";
}
