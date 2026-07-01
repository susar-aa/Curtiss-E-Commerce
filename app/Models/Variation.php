<?php
class Variation {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function getVariationsPaginated($search = '', $limit = 10, $offset = 0) {
        $this->db->query("SELECT * FROM variations WHERE name LIKE :search ORDER BY name ASC LIMIT :limit OFFSET :offset");
        $this->db->bind(':search', "%$search%");
        $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        $this->db->bind(':offset', $offset, PDO::PARAM_INT);
        $variations = $this->db->resultSet();

        // Fetch values for each variation
        foreach ($variations as $var) {
            $this->db->query("SELECT * FROM variation_values WHERE variation_id = :vid ORDER BY value_name ASC");
            $this->db->bind(':vid', $var->id);
            $var->values = $this->db->resultSet();
        }
        return $variations;
    }

    public function getTotalVariations($search = '') {
        $this->db->query("SELECT COUNT(*) as total FROM variations WHERE name LIKE :search");
        $this->db->bind(':search', "%$search%");
        $row = $this->db->single();
        return $row->total ?? 0;
    }

    // Used by Inventory to populate the dropdowns dynamically
    public function getAllVariationsWithValues() {
        $this->db->query("SELECT * FROM variations ORDER BY name ASC");
        $variations = $this->db->resultSet();
        foreach ($variations as $var) {
            $this->db->query("SELECT * FROM variation_values WHERE variation_id = :vid ORDER BY value_name ASC");
            $this->db->bind(':vid', $var->id);
            $var->values = $this->db->resultSet();
        }
        return $variations;
    }

    public function addVariation($name, $description, $valuesArray) {
        try {
            $this->db->beginTransaction();
            
            $this->db->query("INSERT INTO variations (name, description) VALUES (:name, :desc)");
            $this->db->bind(':name', $name);
            $this->db->bind(':desc', $description);
            $this->db->execute();
            $varId = $this->db->lastInsertId();

            foreach ($valuesArray as $val) {
                $cleaned = trim($val);
                if (!empty($cleaned)) {
                    $this->db->query("INSERT INTO variation_values (variation_id, value_name) VALUES (:vid, :val)");
                    $this->db->bind(':vid', $varId);
                    $this->db->bind(':val', $cleaned);
                    $this->db->execute();
                }
            }
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function updateVariation($id, $name, $description, $valuesArray) {
        try {
            $this->db->beginTransaction();
            
            $this->db->query("UPDATE variations SET name = :name, description = :desc WHERE id = :id");
            $this->db->bind(':id', $id);
            $this->db->bind(':name', $name);
            $this->db->bind(':desc', $description);
            $this->db->execute();

            // Smart Sync: Only add new values, delete missing ones. Keep existing IDs intact!
            $this->db->query("SELECT * FROM variation_values WHERE variation_id = :vid");
            $this->db->bind(':vid', $id);
            $existing = $this->db->resultSet();
            
            $existingMap = [];
            foreach ($existing as $e) { $existingMap[strtolower(trim($e->value_name))] = $e->id; }

            $newMap = [];
            foreach ($valuesArray as $v) {
                $cleaned = trim($v);
                if (!empty($cleaned)) { $newMap[strtolower($cleaned)] = $cleaned; }
            }

            // Insert new values
            foreach ($newMap as $lower => $original) {
                if (!isset($existingMap[$lower])) {
                    $this->db->query("INSERT INTO variation_values (variation_id, value_name) VALUES (:vid, :val)");
                    $this->db->bind(':vid', $id);
                    $this->db->bind(':val', $original);
                    $this->db->execute();
                }
            }

            // Delete removed values
            foreach ($existingMap as $lower => $valId) {
                if (!isset($newMap[$lower])) {
                    $this->db->query("DELETE FROM variation_values WHERE id = :id");
                    $this->db->bind(':id', $valId);
                    $this->db->execute();
                }
            }

            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function deleteVariation($id) {
        $this->db->query("DELETE FROM variations WHERE id = :id");
        $this->db->bind(':id', $id);
        try { return $this->db->execute(); } catch (PDOException $e) { return false; }
    }
}