<?php
class AuditLog {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function getAllLogs($limit = 100) {
        $this->db->query("SELECT a.*, u.username, u.role 
                          FROM audit_logs a 
                          LEFT JOIN users u ON a.user_id = u.id 
                          ORDER BY a.created_at DESC 
                          LIMIT :limit");
        $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        return $this->db->resultSet();
    }

    public function logAction($userId, $action, $module, $description, $recordId = null, $oldValues = null, $newValues = null) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        $browserDevice = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        
        $oldJson = $oldValues !== null ? (is_string($oldValues) ? $oldValues : json_encode($oldValues)) : null;
        $newJson = $newValues !== null ? (is_string($newValues) ? $newValues : json_encode($newValues)) : null;
        
        $this->db->query("INSERT INTO audit_logs (user_id, action, module, description, ip_address, record_id, old_values, new_values, browser_device) 
                          VALUES (:uid, :act, :mod, :desc, :ip, :rec_id, :old_val, :new_val, :device)");
        
        $this->db->bind(':uid', $userId);
        $this->db->bind(':act', $action);
        $this->db->bind(':mod', $module);
        $this->db->bind(':desc', $description);
        $this->db->bind(':ip', $ip);
        $this->db->bind(':rec_id', $recordId);
        $this->db->bind(':old_val', $oldJson);
        $this->db->bind(':new_val', $newJson);
        $this->db->bind(':device', $browserDevice);
        
        return $this->db->execute();
    }

    public function getFilteredLogs($filters = [], $limit = 250) {
        $sql = "SELECT a.*, u.username, u.role 
                FROM audit_logs a 
                LEFT JOIN users u ON a.user_id = u.id 
                WHERE 1=1";
        
        $binds = [];
        
        if (!empty($filters['user_id'])) {
            $sql .= " AND a.user_id = :user_id";
            $binds['user_id'] = intval($filters['user_id']);
        }
        if (!empty($filters['module'])) {
            $sql .= " AND a.module = :module";
            $binds['module'] = $filters['module'];
        }
        if (!empty($filters['action'])) {
            $sql .= " AND a.action = :action";
            $binds['action'] = $filters['action'];
        }
        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(a.created_at) >= :date_from";
            $binds['date_from'] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(a.created_at) <= :date_to";
            $binds['date_to'] = $filters['date_to'];
        }
        if (!empty($filters['search'])) {
            $sql .= " AND (a.description LIKE :search OR a.action LIKE :search OR u.username LIKE :search OR CAST(a.record_id AS CHAR) = :search_exact)";
            $binds['search'] = '%' . $filters['search'] . '%';
            $binds['search_exact'] = $filters['search'];
        }
        
        $sql .= " ORDER BY a.created_at DESC LIMIT :limit";
        
        $this->db->query($sql);
        
        foreach ($binds as $key => $val) {
            $this->db->bind(':' . $key, $val);
        }
        $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        
        return $this->db->resultSet() ?: [];
    }

    public function getUniqueModules() {
        $this->db->query("SELECT DISTINCT module FROM audit_logs WHERE module IS NOT NULL AND module != '' ORDER BY module ASC");
        return $this->db->resultSet() ?: [];
    }

    public function getUniqueActions() {
        $this->db->query("SELECT DISTINCT action FROM audit_logs WHERE action IS NOT NULL AND action != '' ORDER BY action ASC");
        return $this->db->resultSet() ?: [];
    }
}