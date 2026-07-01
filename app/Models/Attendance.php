<?php
class Attendance {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function getAllAttendanceRecords() {
        $this->db->query("SELECT a.*, e.first_name, e.last_name, e.department, e.job_title 
                          FROM attendance a 
                          JOIN employees e ON a.employee_id = e.id 
                          ORDER BY a.work_date DESC, a.clock_in DESC");
        return $this->db->resultSet();
    }

    public function getActiveClockIn($employeeId, $date) {
        $this->db->query("SELECT * FROM attendance WHERE employee_id = :employee_id AND work_date = :work_date AND clock_out IS NULL LIMIT 1");
        $this->db->bind(':employee_id', $employeeId);
        $this->db->bind(':work_date', $date);
        return $this->db->single();
    }

    public function recordClockIn($employeeId, $date, $time) {
        $this->db->query("INSERT INTO attendance (employee_id, work_date, clock_in, clock_out, status) 
                          VALUES (:employee_id, :work_date, :clock_in, NULL, :status)");
        $this->db->bind(':employee_id', $employeeId);
        $this->db->bind(':work_date', $date);
        $this->db->bind(':clock_in', $time);
        
        // Mark as late if clock in is after 09:00:00
        $status = (strtotime($time) > strtotime('09:00:00')) ? 'Late' : 'Present';
        $this->db->bind(':status', $status);

        try {
            return $this->db->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    public function recordClockOut($id, $time) {
        $this->db->query("UPDATE attendance SET clock_out = :clock_out WHERE id = :id");
        $this->db->bind(':clock_out', $time);
        $this->db->bind(':id', $id);

        try {
            return $this->db->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    public function addManualAttendance($data) {
        $this->db->query("INSERT INTO attendance (employee_id, work_date, clock_in, clock_out, status) 
                          VALUES (:employee_id, :work_date, :clock_in, :clock_out, :status)");
        $this->db->bind(':employee_id', $data['employee_id']);
        $this->db->bind(':work_date', $data['work_date']);
        $this->db->bind(':clock_in', $data['clock_in']);
        $this->db->bind(':clock_out', !empty($data['clock_out']) ? $data['clock_out'] : null);
        $this->db->bind(':status', $data['status']);

        try {
            return $this->db->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    public function deleteAttendance($id) {
        $this->db->query("DELETE FROM attendance WHERE id = :id");
        $this->db->bind(':id', $id);

        try {
            return $this->db->execute();
        } catch (PDOException $e) {
            return false;
        }
    }
}
