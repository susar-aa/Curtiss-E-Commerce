<?php

require_once __DIR__ . '/RbacInterface.php';
require_once __DIR__ . '/../app/Models/User.php';

class RbacService implements RbacInterface {
    private static $instance = null;
    private $userModel;

    private function __construct() {
        $this->userModel = new User();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Check if a user has permission to access a module/action.
     */
    public function check($userId, $module, $action = 'view') {
        if (!$userId) {
            return false;
        }

        // 1. Role inheritance: Admin role always bypasses constraints
        if ($this->hasRole($userId, 'Admin')) {
            return true;
        }

        // 2. Fetch permissions from DB or session
        $permissions = $this->getPermissions($userId);

        if (isset($permissions[$module])) {
            $perm = $permissions[$module];
            if ($action === 'view') {
                return (bool)($perm['can_view'] ?? false);
            } elseif ($action === 'create_edit') {
                return (bool)($perm['can_create_edit'] ?? false);
            } elseif ($action === 'delete') {
                return (bool)($perm['can_delete'] ?? false);
            }
        }

        return false;
    }

    /**
     * Get aggregated module permissions for a user.
     */
    public function getPermissions($userId) {
        if (!$userId) {
            return [];
        }
        return $this->userModel->getUserPermissions($userId);
    }

    /**
     * Check if a user has a specific role.
     */
    public function hasRole($userId, $roleName) {
        if (!$userId) {
            return false;
        }
        
        $roles = $this->userModel->getUserRoles($userId);
        foreach ($roles as $r) {
            if (strtolower($r->name) === strtolower($roleName)) {
                return true;
            }
        }
        return false;
    }
}
