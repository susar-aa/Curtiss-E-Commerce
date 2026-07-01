<?php
class Cheque {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function getAllCheques() {
        $this->db->query("SELECT ch.*, c.name as customer_name, v.name as vendor_name 
                          FROM cheques ch 
                          LEFT JOIN customers c ON ch.customer_id = c.id 
                          LEFT JOIN vendors v ON ch.vendor_id = v.id 
                          ORDER BY ch.status ASC, ch.banking_date ASC");
        return $this->db->resultSet();
    }

    public function addCheque($data) {
        $this->db->query("INSERT INTO cheques (customer_id, vendor_id, bank_name, cheque_number, amount, banking_date, created_by) 
                          VALUES (:cid, :vid, :bank, :cnum, :amt, :bdate, :uid)");
        $this->db->bind(':cid', $data['customer_id']);
        $this->db->bind(':vid', $data['vendor_id']);
        $this->db->bind(':bank', $data['bank_name']);
        $this->db->bind(':cnum', $data['cheque_number']);
        $this->db->bind(':amt', $data['amount']);
        $this->db->bind(':bdate', $data['banking_date']);
        $this->db->bind(':uid', $data['created_by']);
        return $this->db->execute();
    }

    public function updateCheque($data) {
        $this->db->query("UPDATE cheques 
                          SET customer_id = :cid, vendor_id = :vid, bank_name = :bank, cheque_number = :cnum, amount = :amt, banking_date = :bdate, status = :status 
                          WHERE id = :id");
        $this->db->bind(':id', $data['id']);
        $this->db->bind(':cid', $data['customer_id']);
        $this->db->bind(':vid', $data['vendor_id']);
        $this->db->bind(':bank', $data['bank_name']);
        $this->db->bind(':cnum', $data['cheque_number']);
        $this->db->bind(':amt', $data['amount']);
        $this->db->bind(':bdate', $data['banking_date']);
        $this->db->bind(':status', $data['status']);
        return $this->db->execute();
    }

    public function deleteCheque($id) {
        $this->db->query("DELETE FROM cheques WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }
}