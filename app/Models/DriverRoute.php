<?php
class DriverRoute {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function getUserDetails($userId) {
        $this->db->query("SELECT u.id, u.username, u.role, u.employee_id, u.email,
                                 CONCAT(e.first_name, ' ', e.last_name) as full_name,
                                 e2.first_name as e2_first, e2.last_name as e2_last
                          FROM users u
                          LEFT JOIN employees e ON u.employee_id = e.id
                          LEFT JOIN employees e2 ON u.email = e2.email
                          WHERE u.id = :uid");
        $this->db->bind(':uid', $userId);
        return $this->db->single();
    }

    public function getAssignedDelivery($userId) {
        $user = $this->getUserDetails($userId);
        if (!$user) return false;

        // Gather all possible identifiers to uniquely match this employee/user
        $possibilities = [];
        
        // 1. Username and User ID
        $possibilities[] = $user->username;
        $possibilities[] = strval($user->id);
        
        // 2. Employee ID via direct u.employee_id link
        if (!empty($user->employee_id)) {
            $possibilities[] = strval($user->employee_id);
        }
        
        // 3. Full Name & First Name via direct u.employee_id link
        if (!empty($user->full_name)) {
            $possibilities[] = $user->full_name;
            $parts = explode(' ', $user->full_name);
            if (!empty($parts[0])) {
                $possibilities[] = $parts[0];
            }
        }
        
        // 4. Full Name & First Name via email fallback link (u.email = e.email)
        if (!empty($user->e2_first)) {
            $e2_fullname = trim($user->e2_first . ' ' . $user->e2_last);
            $possibilities[] = $e2_fullname;
            $possibilities[] = $user->e2_first;
        }

        // 5. Fallback full name and first name from username if full_name is empty
        if (empty($user->full_name)) {
            $cleanUsername = str_replace('@', '.', $user->username);
            $parts = explode('.', $cleanUsername);
            $firstName = ucfirst($parts[0]);
            $lastName = isset($parts[1]) ? ucfirst($parts[1]) : '';
            $virtualFullName = trim($firstName . ' ' . $lastName);
            
            $possibilities[] = $virtualFullName;
            $possibilities[] = $firstName;
            // Also include lowercase versions
            $possibilities[] = strtolower($virtualFullName);
            $possibilities[] = strtolower($firstName);
        }

        // 6. Username prefixes and subparts
        $userParts = explode('@', $user->username);
        if (!empty($userParts[0])) {
            $possibilities[] = $userParts[0];
            $subParts = explode('.', $userParts[0]);
            foreach ($subParts as $sp) {
                $possibilities[] = $sp;
                $possibilities[] = ucfirst($sp);
            }
        }

        // 7. Email and Email prefix
        if (!empty($user->email)) {
            $possibilities[] = $user->email;
            $emailParts = explode('@', $user->email);
            if (!empty($emailParts[0])) {
                $possibilities[] = $emailParts[0];
                $possibilities[] = ucfirst($emailParts[0]);
            }
        }
        
        // Clean, normalize, and unique-filter the possibilities
        $possibilities = array_unique(array_filter(array_map('trim', $possibilities)));
        
        if (empty($possibilities)) {
            return false;
        }

        // Build dynamically bound OR clauses for driver_name or partner_name
        $whereClauses = [];
        $binds = [];
        foreach ($possibilities as $index => $possibility) {
            $paramName = ":poss_" . $index;
            $whereClauses[] = "d.driver_name = $paramName";
            $whereClauses[] = "d.partner_name = $paramName";
            $binds[$paramName] = $possibility;
        }
        
        $whereSql = "(" . implode(" OR ", $whereClauses) . ")";

        $this->db->query("
            SELECT d.*, r.route_name, r.start_time,
                   COALESCE(CONCAT(rep_e.first_name, ' ', rep_e.last_name), rep_u.username) as rep_name,
                (SELECT COUNT(*) FROM invoices WHERE rep_route_id = r.id AND status != 'Voided') as bill_count,
                (SELECT COALESCE(SUM(total_amount - COALESCE(CASE WHEN global_discount_type = '%' THEN (total_amount * global_discount_val / 100) ELSE global_discount_val END, 0) + COALESCE(tax_amount, 0)), 0) 
                 FROM invoices WHERE rep_route_id = r.id AND status != 'Voided') as total_sales
            FROM deliveries d
            JOIN rep_daily_routes r ON d.rep_route_id = r.id
            LEFT JOIN users rep_u ON r.user_id = rep_u.id
            LEFT JOIN employees rep_e ON rep_u.employee_id = rep_e.id
            WHERE $whereSql AND d.status NOT IN ('Completed', 'Finalized')
            ORDER BY d.delivery_date DESC, d.created_at DESC
            LIMIT 1
        ");
        
        foreach ($binds as $param => $val) {
            $this->db->bind($param, $val);
        }
        
        return $this->db->single();
    }

    public function getDeliveryById($id) {
        $this->db->query("
            SELECT d.*, r.route_name, r.start_time,
                (SELECT COUNT(*) FROM invoices WHERE rep_route_id = r.id AND status != 'Voided') as bill_count,
                (SELECT COALESCE(SUM(total_amount - COALESCE(CASE WHEN global_discount_type = '%' THEN (total_amount * global_discount_val / 100) ELSE global_discount_val END, 0) + COALESCE(tax_amount, 0)), 0) 
                 FROM invoices WHERE rep_route_id = r.id AND status != 'Voided') as total_sales
            FROM deliveries d
            JOIN rep_daily_routes r ON d.rep_route_id = r.id
            WHERE d.id = :id
        ");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    public function acceptRoute($deliveryId) {
        $this->db->query("UPDATE deliveries SET status = 'Accepted', accepted_at = NOW() WHERE id = :id");
        $this->db->bind(':id', $deliveryId);
        return $this->db->execute();
    }

    public function startTrip($deliveryId, $startMeter, $driverName, $partnerName) {
        $this->db->query("UPDATE deliveries 
                          SET status = 'In Transit', 
                              start_meter = :startMeter, 
                              started_at = NOW(), 
                              driver_name = :driverName, 
                              partner_name = :partnerName 
                          WHERE id = :id");
        $this->db->bind(':id', $deliveryId);
        $this->db->bind(':startMeter', $startMeter);
        $this->db->bind(':driverName', $driverName);
        $this->db->bind(':partnerName', $partnerName);
        return $this->db->execute();
    }

    public function getActiveEmployees() {
        $this->db->query("SELECT *, CONCAT(first_name, ' ', last_name) as full_name 
                          FROM employees 
                          WHERE status = 'Active' 
                          ORDER BY first_name ASC");
        return $this->db->resultSet();
    }

    public function getDeliveryShops($routeId) {
        $this->db->query("
            SELECT c.id, c.name, c.phone, c.address,
                COUNT(i.id) as invoice_count,
                SUM(CASE WHEN i.delivery_status = 'Pending' THEN 1 ELSE 0 END) as pending_count,
                SUM(i.total_amount - COALESCE(CASE WHEN i.global_discount_type = '%' THEN (i.total_amount * i.global_discount_val / 100) ELSE i.global_discount_val END, 0) + COALESCE(i.tax_amount, 0)) as total_amount
            FROM invoices i
            JOIN customers c ON i.customer_id = c.id
            WHERE i.rep_route_id = :rid AND i.status != 'Voided'
            GROUP BY c.id, c.name, c.phone, c.address
            ORDER BY c.name ASC
        ");
        $this->db->bind(':rid', $routeId);
        return $this->db->resultSet();
    }

    public function endTrip($deliveryId, $endMeter, $cashDenomJson = null) {
        $this->db->beginTransaction();
        try {
            $delivery = $this->getDeliveryById($deliveryId);
            if (!$delivery) {
                throw new Exception("Delivery not found");
            }

            // 1. Update delivery status
            $this->db->query("UPDATE deliveries 
                              SET status = 'Completed', end_meter = :endMeter, cash_denominations = :cashDenoms, completed_at = NOW() 
                              WHERE id = :id");
            $this->db->bind(':id', $deliveryId);
            $this->db->bind(':endMeter', $endMeter);
            $this->db->bind(':cashDenoms', $cashDenomJson);
            $this->db->execute();

            // 2. Update rep_daily_routes status
            $this->db->query("UPDATE rep_daily_routes 
                              SET end_meter = :endMeter, end_time = NOW(), status = 'Completed' 
                              WHERE id = :route_id");
            $this->db->bind(':route_id', $delivery->rep_route_id);
            $this->db->bind(':endMeter', $endMeter);
            $this->db->execute();

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("endTrip error: " . $e->getMessage());
            return false;
        }
    }
}
