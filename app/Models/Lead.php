<?php
class Lead {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function getAllLeads() {
        $this->db->query("SELECT l.*, u.username as assigned_user 
                          FROM leads l 
                          LEFT JOIN users u ON l.assigned_to = u.id 
                          ORDER BY l.created_at DESC");
        return $this->db->resultSet();
    }

    public function getLeadById($id) {
        $this->db->query("SELECT * FROM leads WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    public function addLead($data) {
        $this->db->query("INSERT INTO leads (first_name, last_name, company_name, email, phone, source, status, assigned_to) 
                          VALUES (:fname, :lname, :company, :email, :phone, :source, :status, :assigned)");
        
        $this->db->bind(':fname', $data['first_name']);
        $this->db->bind(':lname', $data['last_name']);
        $this->db->bind(':company', $data['company_name']);
        $this->db->bind(':email', $data['email']);
        $this->db->bind(':phone', $data['phone']);
        $this->db->bind(':source', $data['source']);
        $this->db->bind(':status', $data['status']);
        $this->db->bind(':assigned', $data['assigned_to'] ?: null);
        
        return $this->db->execute();
    }

    public function updateStatus($id, $status) {
        $this->db->query("UPDATE leads SET status = :status WHERE id = :id");
        $this->db->bind(':status', $status);
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    public function convertToCustomer($leadId) {
        $lead = $this->getLeadById($leadId);
        if (!$lead) return false;

        try {
            $this->db->beginTransaction();

            // Insert into customers table
            $customerName = !empty($lead->company_name) ? $lead->company_name : trim($lead->first_name . ' ' . $lead->last_name);
            $this->db->query("INSERT INTO customers (name, email, phone) VALUES (:name, :email, :phone)");
            $this->db->bind(':name', $customerName);
            $this->db->bind(':email', $lead->email);
            $this->db->bind(':phone', $lead->phone);
            $this->db->execute();

            // Update lead status to Converted
            $this->updateStatus($leadId, 'Converted');

            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            return false;
        }
    }
}