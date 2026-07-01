<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/Controller.php';
require_once __DIR__ . '/app/Models/Item.php';

$itemModel = new Item();
$items = $itemModel->getItemsPaged(10, 0, ['search' => 'ARTPL2']);
if (!empty($items)) {
    $item = $items[0];
    echo "SKU: " . $item->item_code . " | Name: " . $item->name . " | qty: " . $item->qty . " | quantity_on_hand: " . $item->quantity_on_hand . "\n";
} else {
    echo "ARTPL2 not found in getItemsPaged!\n";
}
