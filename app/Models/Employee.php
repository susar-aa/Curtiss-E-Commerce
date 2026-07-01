<?php
class Employee {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function getAllEmployees() {
        // Fetch employees combined with their system user credentials and accessible apps
        $this->db->query("SELECT e.*, u.id AS user_id, u.username, u.role as user_role, u.accessible_apps 
                          FROM employees e 
                          LEFT JOIN users u ON u.employee_id = e.id 
                          ORDER BY e.status ASC, e.last_name ASC");
        return $this->db->resultSet();
    }

    public function getActiveEmployees() {
        $this->db->query("SELECT * FROM employees WHERE status = 'Active'");
        return $this->db->resultSet();
    }

    public function addEmployee($empData, $userData = null) {
        try {
            $this->db->beginTransaction();

            $this->db->query("INSERT INTO employees (first_name, last_name, email, phone, department, job_title, base_salary, hire_date, status) 
                              VALUES (:fname, :lname, :email, :phone, :dept, :title, :salary, :hdate, 'Active')");
            
            $email = !empty($empData['email']) ? trim($empData['email']) : null;
            $phone = !empty($empData['phone']) ? trim($empData['phone']) : null;
            $dept = !empty($empData['department']) ? trim($empData['department']) : null;

            $this->db->bind(':fname', $empData['first_name']);
            $this->db->bind(':lname', $empData['last_name']);
            $this->db->bind(':email', $email);
            $this->db->bind(':phone', $phone);
            $this->db->bind(':dept', $dept);
            $this->db->bind(':title', $empData['job_title']);
            $this->db->bind(':salary', $empData['base_salary']);
            $this->db->bind(':hdate', $empData['hire_date']);
            
            $this->db->execute();
            $employeeId = $this->db->lastInsertId();

            if ($userData) {
                $this->db->query("INSERT INTO users (username, email, password_hash, role, signature_path, employee_id, status, accessible_apps) 
                                  VALUES (:username, :email, :password, :role, :sig, :employee_id, :status, :accessible_apps)");
                $this->db->bind(':username', $userData['username']);
                $this->db->bind(':email', $userData['email'] ?: null);
                $this->db->bind(':password', password_hash($userData['password'], PASSWORD_DEFAULT));
                $this->db->bind(':role', $userData['role']);
                $this->db->bind(':sig', $userData['signature_path'] ?: null);
                $this->db->bind(':employee_id', $employeeId);
                $this->db->bind(':status', $userData['status']);
                $this->db->bind(':accessible_apps', $userData['accessible_apps']);
                $this->db->execute();
            }

            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function getEmployeeById($id) {
        $this->db->query("SELECT e.*, u.id AS user_id, u.username, u.role as user_role, u.accessible_apps 
                          FROM employees e 
                          LEFT JOIN users u ON u.employee_id = e.id 
                          WHERE e.id = :id");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    public function updateEmployee($empData, $userData = null) {
        try {
            $this->db->beginTransaction();

            $this->db->query("UPDATE employees 
                              SET first_name = :fname, last_name = :lname, email = :email, 
                                  phone = :phone, department = :dept, job_title = :title, 
                                  base_salary = :salary, hire_date = :hdate, status = :status 
                              WHERE id = :id");
            
            $email = !empty($empData['email']) ? trim($empData['email']) : null;
            $phone = !empty($empData['phone']) ? trim($empData['phone']) : null;
            $dept = !empty($empData['department']) ? trim($empData['department']) : null;

            $this->db->bind(':fname', $empData['first_name']);
            $this->db->bind(':lname', $empData['last_name']);
            $this->db->bind(':email', $email);
            $this->db->bind(':phone', $phone);
            $this->db->bind(':dept', $dept);
            $this->db->bind(':title', $empData['job_title']);
            $this->db->bind(':salary', $empData['base_salary']);
            $this->db->bind(':hdate', $empData['hire_date']);
            $this->db->bind(':status', $empData['status'] ?? 'Active');
            $this->db->bind(':id', $empData['id']);
            $this->db->execute();

            if ($userData) {
                // Check if user already exists for this employee
                $this->db->query("SELECT id FROM users WHERE employee_id = :employee_id");
                $this->db->bind(':employee_id', $empData['id']);
                $existingUser = $this->db->single();

                if ($existingUser) {
                    // Update user
                    if (!empty($userData['password'])) {
                        $this->db->query("UPDATE users 
                                          SET username = :username, email = :email, password_hash = :password, 
                                              role = :role, status = :status, accessible_apps = :accessible_apps 
                                          WHERE employee_id = :employee_id");
                        $this->db->bind(':password', password_hash($userData['password'], PASSWORD_DEFAULT));
                    } else {
                        $this->db->query("UPDATE users 
                                          SET username = :username, email = :email, role = :role, 
                                              status = :status, accessible_apps = :accessible_apps 
                                          WHERE employee_id = :employee_id");
                    }
                    $this->db->bind(':username', $userData['username']);
                    $this->db->bind(':email', $userData['email'] ?: null);
                    $this->db->bind(':role', $userData['role']);
                    $this->db->bind(':status', $userData['status']);
                    $this->db->bind(':accessible_apps', $userData['accessible_apps']);
                    $this->db->bind(':employee_id', $empData['id']);
                    $this->db->execute();
                } else {
                    // Create user
                    $this->db->query("INSERT INTO users (username, email, password_hash, role, signature_path, employee_id, status, accessible_apps) 
                                      VALUES (:username, :email, :password, :role, :sig, :employee_id, :status, :accessible_apps)");
                    $this->db->bind(':username', $userData['username']);
                    $this->db->bind(':email', $userData['email'] ?: null);
                    $this->db->bind(':password', password_hash($userData['password'], PASSWORD_DEFAULT));
                    $this->db->bind(':role', $userData['role']);
                    $this->db->bind(':sig', null);
                    $this->db->bind(':employee_id', $empData['id']);
                    $this->db->bind(':status', $userData['status']);
                    $this->db->bind(':accessible_apps', $userData['accessible_apps']);
                    $this->db->execute();
                }
            }

            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function deleteEmployee($id) {
        try {
            $this->db->beginTransaction();

            // First delete/cascade linked user permissions
            $this->db->query("SELECT id FROM users WHERE employee_id = :employee_id");
            $this->db->bind(':employee_id', $id);
            $userRow = $this->db->single();
            if ($userRow) {
                $this->db->query("DELETE FROM user_permissions WHERE user_id = :user_id");
                $this->db->bind(':user_id', $userRow->id);
                $this->db->execute();

                $this->db->query("DELETE FROM users WHERE id = :user_id");
                $this->db->bind(':user_id', $userRow->id);
                $this->db->execute();
            }

            $this->db->query("DELETE FROM employees WHERE id = :id");
            $this->db->bind(':id', $id);
            $this->db->execute();

            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}