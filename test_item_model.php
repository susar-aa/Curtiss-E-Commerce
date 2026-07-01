<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/Controller.php';
require_once __DIR__ . '/app/Models/Item.php';

$item = new Item();
// We can use Reflection to get the private property $qtyColumn
$ref = new ReflectionClass($item);
$prop = $ref->getProperty('qtyColumn');
$prop->setAccessible(true);
echo "Resolved qtyColumn: " . $prop->getValue($item) . "\n";

$items = $item->getItems();
echo "Sample item qty: " . $items[0]->qty . "\n";
echo "Sample item quantity_on_hand: " . $items[0]->quantity_on_hand . "\n";
