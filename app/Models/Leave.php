<?php
class Leave {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function getAllLeaveRequests() {
        $this->db->query("SELECT l.*, e.first_name, e.last_name, e.department, e.job_title 
                          FROM leave_requests l 
                          JOIN employees e ON l.employee_id = e.id 
                          ORDER BY l.start_date DESC");
        return $this->db->resultSet();
    }

    public function addLeaveRequest($data) {
        $this->db->query("INSERT INTO leave_requests (employee_id, leave_type, start_date, end_date, reason, status) 
                          VALUES (:employee_id, :leave_type, :start_date, :end_date, :reason, 'Pending')");
        $this->db->bind(':employee_id', $data['employee_id']);
        $this->db->bind(':leave_type', $data['leave_type']);
        $this->db->bind(':start_date', $data['start_date']);
        $this->db->bind(':end_date', $data['end_date']);
        $this->db->bind(':reason', !empty($data['reason']) ? $data['reason'] : null);
        
        try {
            return $this->db->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    public function updateLeaveStatus($id, $status) {
        $this->db->query("UPDATE leave_requests SET status = :status WHERE id = :id");
        $this->db->bind(':status', $status);
        $this->db->bind(':id', $id);
        
        try {
            return $this->db->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    public function deleteLeaveRequest($id) {
        $this->db->query("DELETE FROM leave_requests WHERE id = :id");
        $this->db->bind(':id', $id);
        
        try {
            return $this->db->execute();
        } catch (PDOException $e) {
            return false;
        }
    }
}
