<?php
class Performance {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function getAllPerformanceReviews() {
        $this->db->query("SELECT p.*, e.first_name, e.last_name, e.department, e.job_title, u.username as reviewer_name 
                          FROM performance_reviews p 
                          JOIN employees e ON p.employee_id = e.id 
                          JOIN users u ON p.reviewer_id = u.id 
                          ORDER BY p.review_date DESC");
        return $this->db->resultSet();
    }

    public function addPerformanceReview($data) {
        $this->db->query("INSERT INTO performance_reviews (employee_id, reviewer_id, review_date, rating, feedback) 
                          VALUES (:employee_id, :reviewer_id, :review_date, :rating, :feedback)");
        $this->db->bind(':employee_id', $data['employee_id']);
        $this->db->bind(':reviewer_id', $data['reviewer_id']);
        $this->db->bind(':review_date', $data['review_date']);
        $this->db->bind(':rating', $data['rating']);
        $this->db->bind(':feedback', !empty($data['feedback']) ? $data['feedback'] : null);

        try {
            return $this->db->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    public function deletePerformanceReview($id) {
        $this->db->query("DELETE FROM performance_reviews WHERE id = :id");
        $this->db->bind(':id', $id);

        try {
            return $this->db->execute();
        } catch (PDOException $e) {
            return false;
        }
    }
}
