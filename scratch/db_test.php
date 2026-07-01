<?php
require 'config/database.php';
require 'core/Database.php';

try {
    $db = new Database();
    echo "Connection successful!\n";
    
    // Check product_attributes
    $db->query("SELECT * FROM product_attributes ORDER BY name ASC");
    $attrs = $db->resultSet();
    echo "Attributes count: " . count($attrs) . "\n";
    foreach ($attrs as $a) {
        echo "- ID: {$a->id}, Name: {$a->name}, Slug: {$a->slug}\n";
        
        $db->query("SELECT * FROM product_attribute_terms WHERE attribute_id = :id ORDER BY name ASC");
        $db->bind(':id', $a->id);
        $terms = $db->resultSet();
        echo "  Terms count: " . count($terms) . "\n";
        foreach ($terms as $t) {
            echo "    * ID: {$t->id}, Name: {$t->name}, Slug: {$t->slug}\n";
        }
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
