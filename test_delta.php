<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/Controller.php';
require_once __DIR__ . '/app/Models/Item.php';

$itemModel = new Item();
$items = $itemModel->getItemsDelta();
echo "Total items: " . count($items) . "\n";
$printed = 0;
foreach ($items as $item) {
    if (intval($item->qty) > 0 && $printed < 5) {
        echo "SKU: " . ($item->item_code ?? 'N/A') . "\n";
        echo "qty: " . var_export($item->qty ?? null, true) . "\n";
        echo "quantity_on_hand: " . var_export($item->quantity_on_hand ?? null, true) . "\n";
        $printed++;
    }
}
