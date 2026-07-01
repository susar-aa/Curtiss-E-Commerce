<?php

class Category {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function getCategories() {
        $this->db->query("SELECT cat.*, COUNT(i.id) AS product_count 
                          FROM item_categories cat 
                          LEFT JOIN items i ON cat.id = i.category_id 
                          GROUP BY cat.id 
                          ORDER BY cat.name ASC");
        return $this->db->resultSet();
    }

    public function getCategoryById($id) {
        $this->db->query("SELECT * FROM item_categories WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    public function addCategory($name, $description, $wooCategoryId = null) {
        $this->db->query("INSERT INTO item_categories (name, description, woo_category_id) VALUES (:name, :description, :woo_category_id)");
        $this->db->bind(':name', $name);
        $this->db->bind(':description', $description);
        $this->db->bind(':woo_category_id', $wooCategoryId);
        return $this->db->execute();
    }

    public function updateCategory($id, $name, $description, $wooCategoryId = null) {
        $this->db->query("UPDATE item_categories SET name = :name, description = :description, woo_category_id = :woo_category_id WHERE id = :id");
        $this->db->bind(':id', $id);
        $this->db->bind(':name', $name);
        $this->db->bind(':description', $description);
        $this->db->bind(':woo_category_id', $wooCategoryId);
        return $this->db->execute();
    }

    public function deleteCategory($id) {
        // Prevent deleting categories that have linked products
        $this->db->query("SELECT COUNT(*) as count FROM items WHERE category_id = :id");
        $this->db->bind(':id', $id);
        $row = $this->db->single();
        if ($row && intval($row->count) > 0) {
            return false;
        }

        $this->db->query("DELETE FROM item_categories WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }
}