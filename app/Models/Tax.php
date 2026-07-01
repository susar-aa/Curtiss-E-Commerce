<?php
class Tax {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function getAllTaxRates() {
        $this->db->query("SELECT t.*, c.account_name, c.account_code 
                          FROM tax_rates t 
                          JOIN chart_of_accounts c ON t.liability_account_id = c.id 
                          ORDER BY t.is_active DESC, t.tax_name ASC");
        return $this->db->resultSet();
    }

    public function addTaxRate($data) {
        $this->db->query("INSERT INTO tax_rates (tax_name, rate_percentage, liability_account_id) 
                          VALUES (:name, :rate, :acc_id)");
        $this->db->bind(':name', $data['tax_name']);
        $this->db->bind(':rate', $data['rate_percentage']);
        $this->db->bind(':acc_id', $data['liability_account_id']);
        return $this->db->execute();
    }

    public function toggleStatus($id, $status) {
        $this->db->query("UPDATE tax_rates SET is_active = :status WHERE id = :id");
        $this->db->bind(':status', $status);
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }
}