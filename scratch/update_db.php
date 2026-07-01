<?php
try {
    $db = new PDO('mysql:host=localhost;dbname=curtiss_erp', 'root', '');
    $db->exec("ALTER TABLE pending_collections ADD COLUMN debit_account_id INT(11) NULL DEFAULT NULL");
    $db->exec("ALTER TABLE pending_collections ADD COLUMN credit_account_id INT(11) NULL DEFAULT NULL");
    echo "Columns added successfully!\n";
} catch (Exception $e) {
    echo "Error or columns already exist: " . $e->getMessage() . "\n";
}
