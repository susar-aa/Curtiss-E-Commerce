<?php
class User {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function getAllUsers() {
        $this->db->query("SELECT u.id, u.username, u.email, u.role, u.signature_path, u.created_at, u.employee_id, u.status, e.first_name, e.last_name 
                          FROM users u 
                          LEFT JOIN employees e ON u.employee_id = e.id 
                          ORDER BY u.created_at DESC");
        return $this->db->resultSet();
    }

    public function getUnifiedStaffList() {
        $this->db->query("
            SELECT 
                e.id AS employee_id, e.first_name, e.last_name, e.email AS employee_email, e.phone, e.department, e.job_title, e.base_salary, e.hire_date, e.status AS employee_status,
                u.id AS user_id, u.username, u.email AS user_email, u.role AS user_role, u.status AS user_status, u.accessible_apps, u.signature_path
            FROM employees e
            LEFT JOIN users u ON u.employee_id = e.id
            
            UNION
            
            SELECT 
                NULL AS employee_id, NULL AS first_name, NULL AS last_name, NULL AS employee_email, NULL AS phone, NULL AS department, NULL AS job_title, NULL AS base_salary, NULL AS hire_date, NULL AS employee_status,
                u.id AS user_id, u.username, u.email AS user_email, u.role AS user_role, u.status AS user_status, u.accessible_apps, u.signature_path
            FROM users u
            WHERE u.employee_id IS NULL OR u.employee_id NOT IN (SELECT id FROM employees)
        ");
        return $this->db->resultSet();
    }

    public function findUserByUsername($username) {
        $this->db->query("SELECT * FROM users WHERE username = :username");
        $this->db->bind(':username', $username);
        $row = $this->db->resultSet();
        return count($row) > 0 ? $row[0] : false;
    }

    public function login($username, $password) {
        $row = $this->findUserByUsername($username);
        if ($row) {
            if (isset($row->status) && strtolower($row->status) !== 'active') {
                return false;
            }
            $hashed_password = $row->password_hash;
            if (password_verify($password, $hashed_password)) {
                return $row;
            }
        }
        return false;
    }

    public function createUser($data) {
        $this->db->query("INSERT INTO users (username, email, password_hash, role, signature_path, employee_id, status, accessible_apps) 
                          VALUES (:username, :email, :password, :role, :sig, :employee_id, :status, :accessible_apps)");
        $this->db->bind(':username', $data['username']);
        $this->db->bind(':email', $data['email']);
        $this->db->bind(':password', password_hash($data['password'], PASSWORD_DEFAULT));
        $this->db->bind(':role', $data['role']);
        $this->db->bind(':sig', $data['signature_path']);
        $this->db->bind(':employee_id', $data['employee_id']);
        $this->db->bind(':status', $data['status'] ?? 'Active');
        $this->db->bind(':accessible_apps', $data['accessible_apps'] ?? 'ERP System');
        return $this->db->execute();
    }

    public function updatePassword($username, $new_password) {
        $this->db->query("UPDATE users SET password_hash = :hash WHERE username = :username");
        $this->db->bind(':hash', password_hash($new_password, PASSWORD_DEFAULT));
        $this->db->bind(':username', $username);
        return $this->db->execute();
    }

    public function getUserPermissions($userId) {
        // Query to fetch permissions aggregated from all roles assigned to this user
        $this->db->query("
            SELECT rp.module, 
                   MAX(rp.can_view) AS can_view, 
                   MAX(rp.can_create_edit) AS can_create_edit, 
                   MAX(rp.can_delete) AS can_delete
            FROM user_roles ur
            JOIN role_permissions rp ON ur.role_id = rp.role_id
            WHERE ur.user_id = :user_id
            GROUP BY rp.module
        ");
        $this->db->bind(':user_id', $userId);
        $rows = $this->db->resultSet();
        $perms = [];
        foreach ($rows as $row) {
            $perms[$row->module] = [
                'can_view' => (bool)$row->can_view,
                'can_create_edit' => (bool)$row->can_create_edit,
                'can_delete' => (bool)$row->can_delete
            ];
        }
        
        // If user has direct permissions as fallback (backward compatibility)
        if (empty($perms)) {
            $this->db->query("SELECT * FROM user_permissions WHERE user_id = :user_id");
            $this->db->bind(':user_id', $userId);
            $rowsFallback = $this->db->resultSet();
            foreach ($rowsFallback as $row) {
                $perms[$row->module] = [
                    'can_view' => (bool)$row->can_view,
                    'can_create_edit' => (bool)$row->can_create_edit,
                    'can_delete' => (bool)$row->can_delete
                ];
            }
        }
        
        return $perms;
    }

    public function saveUserPermissions($userId, $permissions) {
        // Delete existing
        $this->db->query("DELETE FROM user_permissions WHERE user_id = :user_id");
        $this->db->bind(':user_id', $userId);
        $this->db->execute();

        // Insert new ones
        if (!empty($permissions)) {
            foreach ($permissions as $module => $actions) {
                $canView = !empty($actions['view']) ? 1 : 0;
                $canCreateEdit = !empty($actions['create_edit']) ? 1 : 0;
                $canDelete = !empty($actions['delete']) ? 1 : 0;

                // Only insert if at least one permission is true
                if ($canView || $canCreateEdit || $canDelete) {
                    $this->db->query("INSERT INTO user_permissions (user_id, module, can_view, can_create_edit, can_delete) 
                                      VALUES (:user_id, :module, :can_view, :can_create_edit, :can_delete)");
                    $this->db->bind(':user_id', $userId);
                    $this->db->bind(':module', $module);
                    $this->db->bind(':can_view', $canView);
                    $this->db->bind(':can_create_edit', $canCreateEdit);
                    $this->db->bind(':can_delete', $canDelete);
                    $this->db->execute();
                }
            }
        }
        return true;
    }

    public function getUserRoles($userId) {
        $this->db->query("
            SELECT r.* 
            FROM roles r
            JOIN user_roles ur ON r.id = ur.role_id
            WHERE ur.user_id = :user_id
        ");
        $this->db->bind(':user_id', $userId);
        return $this->db->resultSet();
    }

    public function getUserRoleIds($userId) {
        $this->db->query("SELECT role_id FROM user_roles WHERE user_id = :user_id");
        $this->db->bind(':user_id', $userId);
        $rows = $this->db->resultSet();
        $ids = [];
        foreach ($rows as $row) {
            $ids[] = intval($row->role_id);
        }
        return $ids;
    }

    public function saveUserRoles($userId, $roleIds) {
        $this->db->query("DELETE FROM user_roles WHERE user_id = :user_id");
        $this->db->bind(':user_id', $userId);
        $this->db->execute();

        if (!empty($roleIds)) {
            foreach ($roleIds as $roleId) {
                $this->db->query("INSERT INTO user_roles (user_id, role_id) VALUES (:user_id, :role_id)");
                $this->db->bind(':user_id', $userId);
                $this->db->bind(':role_id', $roleId);
                $this->db->execute();
            }
        }
        return true;
    }

    public function getAllRoles() {
        $this->db->query("SELECT * FROM roles ORDER BY name ASC");
        return $this->db->resultSet();
    }

    public function getRolePermissions($roleId) {
        $this->db->query("SELECT * FROM role_permissions WHERE role_id = :role_id");
        $this->db->bind(':role_id', $roleId);
        $rows = $this->db->resultSet();
        $perms = [];
        foreach ($rows as $row) {
            $perms[$row->module] = [
                'can_view' => (bool)$row->can_view,
                'can_create_edit' => (bool)$row->can_create_edit,
                'can_delete' => (bool)$row->can_delete
            ];
        }
        return $perms;
    }

    public function saveRolePermissions($roleId, $permissions) {
        $this->db->query("DELETE FROM role_permissions WHERE role_id = :role_id");
        $this->db->bind(':role_id', $roleId);
        $this->db->execute();

        if (!empty($permissions)) {
            foreach ($permissions as $module => $actions) {
                $canView = !empty($actions['view']) ? 1 : 0;
                $canCreateEdit = !empty($actions['create_edit']) ? 1 : 0;
                $canDelete = !empty($actions['delete']) ? 1 : 0;

                if ($canView || $canCreateEdit || $canDelete) {
                    $this->db->query("INSERT INTO role_permissions (role_id, module, can_view, can_create_edit, can_delete) 
                                      VALUES (:role_id, :module, :can_view, :can_create_edit, :can_delete)");
                    $this->db->bind(':role_id', $roleId);
                    $this->db->bind(':module', $module);
                    $this->db->bind(':can_view', $canView);
                    $this->db->bind(':can_create_edit', $canCreateEdit);
                    $this->db->bind(':can_delete', $canDelete);
                    $this->db->execute();
                }
            }
        }
        return true;
    }

    public function createRole($name, $description) {
        $this->db->query("INSERT INTO roles (name, description) VALUES (:name, :description)");
        $this->db->bind(':name', $name);
        $this->db->bind(':description', $description);
        return $this->db->execute();
    }

    public function updateRole($id, $name, $description) {
        $this->db->query("UPDATE roles SET name = :name, description = :description WHERE id = :id");
        $this->db->bind(':name', $name);
        $this->db->bind(':description', $description);
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    public function deleteRole($id) {
        $this->db->query("DELETE FROM roles WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    public function findRoleById($id) {
        $this->db->query("SELECT * FROM roles WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    public function updateUser($data) {
        $this->db->query("UPDATE users SET username = :username, email = :email, role = :role, employee_id = :employee_id, signature_path = :sig, status = :status, accessible_apps = :accessible_apps WHERE id = :id");
        $this->db->bind(':username', $data['username']);
        $this->db->bind(':email', $data['email']);
        $this->db->bind(':role', $data['role']);
        $this->db->bind(':employee_id', $data['employee_id']);
        $this->db->bind(':sig', $data['signature_path']);
        $this->db->bind(':status', $data['status'] ?? 'Active');
        $this->db->bind(':accessible_apps', $data['accessible_apps'] ?? 'ERP System');
        $this->db->bind(':id', $data['id']);
        return $this->db->execute();
    }

    public function deleteUser($id) {
        $this->db->query("DELETE FROM users WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }
}