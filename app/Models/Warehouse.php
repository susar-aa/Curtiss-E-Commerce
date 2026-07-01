<?php
class Warehouse {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function getAllWarehouses() {
        $this->db->query("SELECT * FROM warehouses ORDER BY is_default DESC, name ASC");
        return $this->db->resultSet();
    }

    public function addWarehouse($data) {
        if (!empty($data['is_default'])) {
            $this->db->query("UPDATE warehouses SET is_default = 0");
            $this->db->execute();
        }

        $this->db->query("INSERT INTO warehouses (name, location, is_default) VALUES (:name, :location, :is_default)");
        $this->db->bind(':name', $data['name']);
        $this->db->bind(':location', $data['location']);
        $this->db->bind(':is_default', $data['is_default'] ? 1 : 0);
        return $this->db->execute();
    }

    public function updateWarehouse($data) {
        if (!empty($data['is_default'])) {
            $this->db->query("UPDATE warehouses SET is_default = 0");
            $this->db->execute();
        }

        $this->db->query("UPDATE warehouses SET name = :name, location = :location, is_default = :is_default WHERE id = :id");
        $this->db->bind(':id', $data['id']);
        $this->db->bind(':name', $data['name']);
        $this->db->bind(':location', $data['location']);
        $this->db->bind(':is_default', $data['is_default'] ? 1 : 0);
        return $this->db->execute();
    }
    
    public function deleteWarehouse($id) {
        $this->db->query("DELETE FROM warehouses WHERE id = :id");
        $this->db->bind(':id', $id);
        try { return $this->db->execute(); } catch (PDOException $e) { return false; }
    }
}