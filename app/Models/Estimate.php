<?php
class Estimate {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function getAllEstimates() {
        $this->db->query("SELECT e.*, c.name as customer_name 
                          FROM estimates e 
                          JOIN customers c ON e.customer_id = c.id 
                          ORDER BY e.created_at DESC");
        return $this->db->resultSet();
    }

    public function getEstimateById($id) {
        $this->db->query("SELECT e.*, c.name as customer_name, c.email, c.phone, c.address 
                          FROM estimates e 
                          JOIN customers c ON e.customer_id = c.id 
                          WHERE e.id = :id");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    public function getEstimateItems($id) {
        $this->db->query("SELECT * FROM estimate_items WHERE estimate_id = :id");
        $this->db->bind(':id', $id);
        return $this->db->resultSet();
    }

    public function createEstimate($estimateData, $items, $userId) {
        try {
            $this->db->beginTransaction();

            $totalAmount = 0;
            foreach ($items as $item) {
                $totalAmount += ($item['qty'] * $item['price']);
            }

            // Create Estimate Header
            $this->db->query("INSERT INTO estimates (estimate_number, customer_id, estimate_date, expiry_date, total_amount, status, created_by) 
                              VALUES (:est_num, :cust_id, :edate, :expdate, :total, 'Draft', :uid)");
            $this->db->bind(':est_num', $estimateData['estimate_number']);
            $this->db->bind(':cust_id', $estimateData['customer_id']);
            $this->db->bind(':edate', $estimateData['date']);
            $this->db->bind(':expdate', $estimateData['expiry_date']);
            $this->db->bind(':total', $totalAmount);
            $this->db->bind(':uid', $userId);
            $this->db->execute();
            
            $estimateId = $this->db->lastInsertId();

            // Create Estimate Items
            foreach ($items as $item) {
                $itemTotal = $item['qty'] * $item['price'];
                $this->db->query("INSERT INTO estimate_items (estimate_id, description, quantity, unit_price, total) 
                                  VALUES (:eid, :desc, :qty, :price, :total)");
                $this->db->bind(':eid', $estimateId);
                $this->db->bind(':desc', $item['desc']);
                $this->db->bind(':qty', $item['qty']);
                $this->db->bind(':price', $item['price']);
                $this->db->bind(':total', $itemTotal);
                $this->db->execute();
            }

            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function updateStatus($id, $status) {
        $this->db->query("UPDATE estimates SET status = :status WHERE id = :id");
        $this->db->bind(':status', $status);
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }
}