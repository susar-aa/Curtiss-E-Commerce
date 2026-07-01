<?php
class PaymentTerm {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    /**
     * Self-healing DB routine to gracefully add new QuickBooks-style terms columns
     */
    private function updateSchema() {
        // Handled centrally by MigrationManager
    }

    public function getAllTerms() {
        $this->db->query("SELECT * FROM payment_terms ORDER BY is_inactive ASC, days_due ASC");
        return $this->db->resultSet();
    }

    public function getTermById($id) {
        $this->db->query("SELECT * FROM payment_terms WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    public function addTerm($data) {
        $this->db->query("INSERT INTO payment_terms (
                            name, days_due, term_type, net_due_days, discount_percent, 
                            discount_days, net_due_day_of_month, due_next_month_within_days, 
                            discount_day_of_month, is_inactive
                          ) VALUES (
                            :name, :days_due, :term_type, :net_due_days, :discount_percent, 
                            :discount_days, :net_due_day_of_month, :due_next_month_within_days, 
                            :discount_day_of_month, :is_inactive
                          )");
        
        $this->db->bind(':name', $data['name']);
        $this->db->bind(':days_due', $data['days_due']);
        $this->db->bind(':term_type', $data['term_type']);
        $this->db->bind(':net_due_days', $data['net_due_days']);
        $this->db->bind(':discount_percent', $data['discount_percent']);
        $this->db->bind(':discount_days', $data['discount_days']);
        $this->db->bind(':net_due_day_of_month', $data['net_due_day_of_month']);
        $this->db->bind(':due_next_month_within_days', $data['due_next_month_within_days']);
        $this->db->bind(':discount_day_of_month', $data['discount_day_of_month']);
        $this->db->bind(':is_inactive', $data['is_inactive']);
        
        return $this->db->execute();
    }

    public function updateTerm($data) {
        $this->db->query("UPDATE payment_terms SET 
                            name = :name, 
                            days_due = :days_due, 
                            term_type = :term_type, 
                            net_due_days = :net_due_days, 
                            discount_percent = :discount_percent, 
                            discount_days = :discount_days, 
                            net_due_day_of_month = :net_due_day_of_month, 
                            due_next_month_within_days = :due_next_month_within_days, 
                            discount_day_of_month = :discount_day_of_month, 
                            is_inactive = :is_inactive 
                          WHERE id = :id");
        
        $this->db->bind(':id', $data['id']);
        $this->db->bind(':name', $data['name']);
        $this->db->bind(':days_due', $data['days_due']);
        $this->db->bind(':term_type', $data['term_type']);
        $this->db->bind(':net_due_days', $data['net_due_days']);
        $this->db->bind(':discount_percent', $data['discount_percent']);
        $this->db->bind(':discount_days', $data['discount_days']);
        $this->db->bind(':net_due_day_of_month', $data['net_due_day_of_month']);
        $this->db->bind(':due_next_month_within_days', $data['due_next_month_within_days']);
        $this->db->bind(':discount_day_of_month', $data['discount_day_of_month']);
        $this->db->bind(':is_inactive', $data['is_inactive']);
        
        return $this->db->execute();
    }

    public function deleteTerm($id) {
        $this->db->query("DELETE FROM payment_terms WHERE id = :id");
        $this->db->bind(':id', $id);
        try { return $this->db->execute(); } catch (PDOException $e) { return false; }
    }
}