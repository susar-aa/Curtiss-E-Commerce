<?php
class Vehicle {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function getAllVehicles() {
        $this->db->query("SELECT * FROM vehicles ORDER BY vehicle_number ASC");
        return $this->db->resultSet();
    }

    public function getVehiclesPaginated($search = '', $limit = 10, $offset = 0) {
        $this->db->query("SELECT * FROM vehicles 
                          WHERE vehicle_number LIKE :search OR model LIKE :search OR type LIKE :search 
                          ORDER BY vehicle_number ASC 
                          LIMIT :limit OFFSET :offset");
        $this->db->bind(':search', "%$search%");
        $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        $this->db->bind(':offset', $offset, PDO::PARAM_INT);
        return $this->db->resultSet();
    }

    public function getTotalVehicles($search = '') {
        $this->db->query("SELECT COUNT(*) as total FROM vehicles WHERE vehicle_number LIKE :search OR model LIKE :search OR type LIKE :search");
        $this->db->bind(':search', "%$search%");
        $row = $this->db->single();
        return $row->total ?? 0;
    }

    public function getVehicleById($id) {
        $this->db->query("SELECT * FROM vehicles WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    public function addVehicle($data) {
        try {
            $this->db->beginTransaction();

            $this->db->query("INSERT INTO vehicles (vehicle_number, model, type, status) 
                              VALUES (:vehicle_number, :model, :type, :status)");
            $this->db->bind(':vehicle_number', $data['vehicle_number']);
            $this->db->bind(':model', $data['model']);
            $this->db->bind(':type', $data['type']);
            $this->db->bind(':status', $data['status']);
            $this->db->execute();

            // AUTOMATIC ACCOUNT CREATION FOR VEHICLE (Fuel/Maintenance Expenses)
            // 1. Search for parent "6700 - Travel Transport (Fuel)"
            $this->db->query("SELECT id, account_code FROM chart_of_accounts WHERE account_code = '6700' OR account_name LIKE '%Travel%Transport%' LIMIT 1");
            $parent = $this->db->single();
            if (!$parent) {
                // Search for any Travel/Fuel account as fallback
                $this->db->query("SELECT id, account_code FROM chart_of_accounts WHERE account_type = 'Expense' AND (account_name LIKE '%Fuel%' OR account_name LIKE '%Travel%') LIMIT 1");
                $parent = $this->db->single();
            }

            if (!$parent) {
                // If still missing, create the 6700 parent account
                $this->db->query("INSERT INTO chart_of_accounts (account_code, account_name, account_type, parent_id) VALUES ('6700', 'Travel Transport (Fuel)', 'Expense', NULL)");
                $this->db->execute();
                $parentId = $this->db->lastInsertId();
                $parentCode = '6700';
            } else {
                $parentId = $parent->id;
                $parentCode = $parent->account_code;
            }

            // 2. Insert dynamic sub-account for the registered vehicle under the parent
            $this->db->query("SELECT COUNT(*) as cnt FROM chart_of_accounts WHERE parent_id = :pid");
            $this->db->bind(':pid', $parentId);
            $childCount = intval($this->db->single()->cnt ?? 0);
            $subAccountCode = strval(intval($parentCode) + $childCount + 1);

            $this->db->query("SELECT id FROM chart_of_accounts WHERE account_name = :name");
            $this->db->bind(':name', "Vehicle Expense - " . $data['vehicle_number']);
            if (!$this->db->single()) {
                $this->db->query("INSERT INTO chart_of_accounts (account_code, account_name, account_type, parent_id) VALUES (:code, :name, 'Expense', :pid)");
                $this->db->bind(':code', $subAccountCode);
                $this->db->bind(':name', "Vehicle Expense - " . $data['vehicle_number']);
                $this->db->bind(':pid', $parentId);
                $this->db->execute();
            }

            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function updateVehicle($id, $data) {
        $this->db->query("UPDATE vehicles 
                          SET vehicle_number = :vehicle_number, model = :model, type = :type, status = :status 
                          WHERE id = :id");
        $this->db->bind(':id', $id);
        $this->db->bind(':vehicle_number', $data['vehicle_number']);
        $this->db->bind(':model', $data['model']);
        $this->db->bind(':type', $data['type']);
        $this->db->bind(':status', $data['status']);
        try {
            return $this->db->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    public function deleteVehicle($id) {
        $this->db->query("DELETE FROM vehicles WHERE id = :id");
        $this->db->bind(':id', $id);
        try {
            return $this->db->execute();
        } catch (PDOException $e) {
            return false;
        }
    }
}
