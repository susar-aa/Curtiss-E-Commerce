<?php
class Setting {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function getCompanyInfo() {
        $this->db->query("SELECT * FROM company_settings ORDER BY id ASC LIMIT 1");
        return $this->db->single();
    }

    public function updateCompanyInfo($data) {
        $this->db->query("UPDATE company_settings 
                          SET company_name = :company_name, 
                              email = :email, 
                              phone = :phone, 
                              address = :address, 
                              tax_id = :tax_id 
                          WHERE id = :id");
        
        $this->db->bind(':company_name', $data['company_name']);
        $this->db->bind(':email', $data['email']);
        $this->db->bind(':phone', $data['phone']);
        $this->db->bind(':address', $data['address']);
        $this->db->bind(':tax_id', $data['tax_id']);
        $this->db->bind(':id', $data['id']);

        return $this->db->execute();
    }

    public function updateLogo($id, $logoPath) {
        $this->db->query("UPDATE company_settings SET logo_path = :logo_path WHERE id = :id");
        $this->db->bind(':logo_path', $logoPath);
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }
}