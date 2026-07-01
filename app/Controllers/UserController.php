<?php
class UserController extends Controller {
    private $userModel;
    private $employeeModel;

    private $modules = [
        'crm' => 'CRM & Leads',
        'customer' => 'Customers Center',
        'estimate' => 'Quotes & Estimates',
        'sales' => 'Invoices & AR',
        'creditnote' => 'Credit Notes & Damaged Log',
        'dunning' => 'Dunning Reminders',
        'discount' => 'Discount Feed',
        'reptracking' => 'Rep Route Tracking',
        'delivery' => 'Arranged Deliveries',
        'territory' => 'Territory & Routing',
        'inventory' => 'Products & Inventory',
        'category' => 'Product Categories',
        'variation' => 'Variations & Attributes',
        'warehouse' => 'Warehouse Management',
        'supplier' => 'Suppliers Center',
        'purchase' => 'Purchase Orders',
        'grn' => 'Goods Receipts (GRN)',
        'supplier_return' => 'Supplier Returns',
        'expenses' => 'Expenses & AP',
        'hrm' => 'HRM, Employees & Payroll',
        'project' => 'Projects & Tasks',
        'vehicle' => 'Vehicle Management',
        'cheque' => 'Cheque Management',
        'accounting' => 'General Accounting',
        'customerpayment' => 'Customer Payments',
        'supplierpayment' => 'Supplier Payments',
        'asset' => 'Fixed Assets Register',
        'report' => 'Financial Reports & Budgets',
        'ecommerce' => 'E-Commerce Operations',
        'settings' => 'Company Settings',
        'user' => 'Users & Permissions Control',
        'tax' => 'Tax Rates & Rules',
        'paymentterm' => 'Payment Terms & Rules',
        'audit' => 'System Audit Trail'
    ];

    public function __construct() {
        if (!isset($_SESSION['user_id'])) { 
            header('Location: ' . APP_URL . '/auth/login'); 
            exit; 
        }

        // Auto-initialize the user_permissions table if not exists
        $db = new Database();
        $db->query("CREATE TABLE IF NOT EXISTS user_permissions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            module VARCHAR(50) NOT NULL,
            can_view TINYINT(1) DEFAULT 0,
            can_create_edit TINYINT(1) DEFAULT 0,
            can_delete TINYINT(1) DEFAULT 0,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY user_module (user_id, module)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        $db->execute();

        $this->userModel = $this->model('User');
        $this->employeeModel = $this->model('Employee');

        // Restrict access using the permission system
        $this->checkPermission('user', 'view');
    }

    /**
     * Display users list
     */
    /**
     * Display users list
     */
    public function index() {
        $staff = $this->userModel->getUnifiedStaffList();
        
        // Load roles and permissions for each staff member who has a user account
        foreach ($staff as $s) {
            if ($s->user_id) {
                $s->roles = $this->userModel->getUserRoles($s->user_id);
                $s->permissions = $this->userModel->getUserPermissions($s->user_id);
            } else {
                $s->roles = [];
                $s->permissions = [];
            }
        }

        // Fetch all system users (excluding current user) for the data transfer option
        $allUsers = $this->userModel->getAllUsers();

        $data = [
            'title' => 'User & Employee Directory',
            'content_view' => 'users/index',
            'staff' => $staff,
            'users' => $allUsers,
            'roles' => $this->userModel->getAllRoles()
        ];

        // Handle success/error flash messages
        if (isset($_SESSION['flash_success'])) {
            $data['success'] = $_SESSION['flash_success'];
            unset($_SESSION['flash_success']);
        }
        if (isset($_SESSION['flash_error'])) {
            $data['error'] = $_SESSION['flash_error'];
            unset($_SESSION['flash_error']);
        }

        $this->view('layouts/main', $data);
    }

    /**
     * Create / Add a Staff Member (Employee + Optional User Account)
     */
    public function create() {
        $this->checkPermission('user', 'create_edit');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Retrieve Employee inputs
            $empData = [
                'first_name' => trim($_POST['first_name'] ?? ''),
                'last_name' => trim($_POST['last_name'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
                'phone' => trim($_POST['phone'] ?? ''),
                'department' => trim($_POST['department'] ?? ''),
                'job_title' => trim($_POST['job_title'] ?? ''),
                'base_salary' => floatval($_POST['base_salary'] ?? 0),
                'hire_date' => $_POST['hire_date'] ?? date('Y-m-d'),
                'status' => $_POST['status'] ?? 'Active'
            ];

            $createLogin = isset($_POST['create_login']) && $_POST['create_login'] == '1';
            $userData = null;

            if ($createLogin) {
                $username = trim($_POST['username'] ?? '');
                $password = $_POST['password'] ?? '';
                $selectedRoleIds = $_POST['roles'] ?? []; // Array of role IDs
                $appsArray = $_POST['accessible_apps'] ?? ['ERP System'];
                $accessibleApps = implode(',', $appsArray);

                // Determine primary role name to keep backward compatibility
                $primaryRoleName = 'office';
                if (!empty($selectedRoleIds)) {
                    $firstRole = $this->userModel->findRoleById($selectedRoleIds[0]);
                    if ($firstRole) {
                        $primaryRoleName = $firstRole->name;
                    }
                }

                // Handle Signature Upload
                $signaturePath = null;
                if (isset($_FILES['signature']) && $_FILES['signature']['error'] === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($_FILES['signature']['name'], PATHINFO_EXTENSION));
                    if (in_array($ext, ['png', 'jpg', 'jpeg'])) {
                        $signaturePath = 'sig_' . time() . '_' . rand(100, 999) . '.' . $ext;
                        if (!file_exists('../public/uploads')) {
                            @mkdir('../public/uploads', 0777, true);
                        }
                        move_uploaded_file($_FILES['signature']['tmp_name'], '../public/uploads/' . $signaturePath);
                    }
                }

                if (empty($username) || empty($password)) {
                    $_SESSION['flash_error'] = 'Username and Password are required when enabling system login.';
                    header('Location: ' . APP_URL . '/user');
                    exit;
                }

                if ($this->userModel->findUserByUsername($username)) {
                    $_SESSION['flash_error'] = 'Username is already taken.';
                    header('Location: ' . APP_URL . '/user');
                    exit;
                }

                $userData = [
                    'username' => $username,
                    'email' => $empData['email'],
                    'password' => $password,
                    'role' => $primaryRoleName,
                    'signature_path' => $signaturePath,
                    'status' => 'Active',
                    'accessible_apps' => $accessibleApps
                ];
            }

            try {
                // Save employee (and base user details if provided)
                if ($this->employeeModel->addEmployee($empData, $userData)) {
                    // Fetch the newly created user to link roles
                    if ($createLogin && !empty($username)) {
                        $newUser = $this->userModel->findUserByUsername($username);
                        if ($newUser && !empty($selectedRoleIds)) {
                            $this->userModel->saveUserRoles($newUser->id, $selectedRoleIds);
                        }
                    }

                    $_SESSION['flash_success'] = $createLogin 
                        ? 'Staff member and system login created successfully.' 
                        : 'Staff member added successfully.';
                } else {
                    $_SESSION['flash_error'] = 'Failed to save staff member.';
                }
            } catch (Exception $e) {
                $_SESSION['flash_error'] = 'Database Error: ' . $e->getMessage();
            }

            header('Location: ' . APP_URL . '/user');
            exit;
        }

        header('Location: ' . APP_URL . '/user');
        exit;
    }

    /**
     * Edit / Update a Staff Member (Employee and/or User Account)
     */
    public function edit($id) {
        $this->checkPermission('user', 'create_edit');

        $db = new Database();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Determine if we are editing an existing user or a pure employee
            $isUser = false;
            $db->query("SELECT * FROM users WHERE id = :id");
            $db->bind(':id', $id);
            $userObj = $db->single();
            if ($userObj) {
                $isUser = true;
                $employeeId = $userObj->employee_id;
                
                // If it is a System Account Only user (no linked employee record yet)
                if (empty($employeeId)) {
                    // Create the employee record first so we have an ID to link
                    $db->query("INSERT INTO employees (first_name, last_name, email, phone, department, job_title, base_salary, hire_date, status) 
                                VALUES (:fname, :lname, :email, :phone, :dept, :title, :salary, :hdate, :status)");
                    
                    $email = !empty($_POST['email']) ? trim($_POST['email']) : null;
                    $phone = !empty($_POST['phone']) ? trim($_POST['phone']) : null;
                    $dept = !empty($_POST['department']) ? trim($_POST['department']) : null;

                    $db->bind(':fname', trim($_POST['first_name'] ?? ''));
                    $db->bind(':lname', trim($_POST['last_name'] ?? ''));
                    $db->bind(':email', $email);
                    $db->bind(':phone', $phone);
                    $db->bind(':dept', $dept);
                    $db->bind(':title', trim($_POST['job_title'] ?? 'Office'));
                    $db->bind(':salary', floatval($_POST['base_salary'] ?? 0));
                    $db->bind(':hdate', $_POST['hire_date'] ?? date('Y-m-d'));
                    $db->bind(':status', $_POST['status'] ?? 'Active');
                    
                    $db->execute();
                    $employeeId = $db->lastInsertId();
                    
                    // Link user to this new employee
                    $db->query("UPDATE users SET employee_id = :emp_id WHERE id = :user_id");
                    $db->bind(':emp_id', $employeeId);
                    $db->bind(':user_id', $id);
                    $db->execute();
                    
                    // Update userObj with new employee_id for any subsequent logic
                    $userObj->employee_id = $employeeId;
                }
            } else {
                $employeeId = intval($id);
            }

            // Retrieve Employee inputs
            $empData = [
                'id' => $employeeId,
                'first_name' => trim($_POST['first_name'] ?? ''),
                'last_name' => trim($_POST['last_name'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
                'phone' => trim($_POST['phone'] ?? ''),
                'department' => trim($_POST['department'] ?? ''),
                'job_title' => trim($_POST['job_title'] ?? ''),
                'base_salary' => floatval($_POST['base_salary'] ?? 0),
                'hire_date' => $_POST['hire_date'] ?? date('Y-m-d'),
                'status' => $_POST['status'] ?? 'Active'
            ];

            $createLogin = isset($_POST['create_login']) && $_POST['create_login'] == '1';
            $userData = null;

            if ($createLogin) {
                $username = trim($_POST['username'] ?? '');
                $password = $_POST['password'] ?? '';
                $selectedRoleIds = $_POST['roles'] ?? [];
                $appsArray = $_POST['accessible_apps'] ?? ['ERP System'];
                $accessibleApps = implode(',', $appsArray);

                // Determine primary role name to keep backward compatibility
                $primaryRoleName = 'office';
                if (!empty($selectedRoleIds)) {
                    $firstRole = $this->userModel->findRoleById($selectedRoleIds[0]);
                    if ($firstRole) {
                        $primaryRoleName = $firstRole->name;
                    }
                }

                // Check username conflict
                $existing = $this->userModel->findUserByUsername($username);
                if ($existing && (!$isUser || $existing->id != $id) && ($existing->employee_id != $employeeId)) {
                    $_SESSION['flash_error'] = 'Failed to update: Username is already taken by another account.';
                    header('Location: ' . APP_URL . '/user');
                    exit;
                }

                // Handle signature delete/upload
                $signaturePath = $isUser ? $userObj->signature_path : null;
                if (isset($_POST['delete_signature']) && $_POST['delete_signature'] == '1') {
                    $signaturePath = null;
                }
                if (isset($_FILES['signature']) && $_FILES['signature']['error'] === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($_FILES['signature']['name'], PATHINFO_EXTENSION));
                    if (in_array($ext, ['png', 'jpg', 'jpeg'])) {
                        $signaturePath = 'sig_' . time() . '_' . rand(100, 999) . '.' . $ext;
                        if (!file_exists('../public/uploads')) {
                            @mkdir('../public/uploads', 0777, true);
                        }
                        move_uploaded_file($_FILES['signature']['tmp_name'], '../public/uploads/' . $signaturePath);
                    }
                }

                $userData = [
                    'username' => $username,
                    'email' => $empData['email'],
                    'password' => $password, // empty password handled inside updateEmployee
                    'role' => $primaryRoleName,
                    'signature_path' => $signaturePath,
                    'status' => $_POST['user_status'] ?? 'Active',
                    'accessible_apps' => $accessibleApps
                ];
            }

            try {
                // Update employee and user
                if ($this->employeeModel->updateEmployee($empData, $userData)) {
                    // If login was created/updated, save roles in user_roles
                    if ($createLogin) {
                        $userToRole = $this->userModel->findUserByUsername($username);
                        if ($userToRole && !empty($selectedRoleIds)) {
                            $this->userModel->saveUserRoles($userToRole->id, $selectedRoleIds);
                        }
                    }

                    $_SESSION['flash_success'] = 'Staff details updated successfully.';
                } else {
                    $_SESSION['flash_error'] = 'Failed to update staff details.';
                }
            } catch (Exception $e) {
                $_SESSION['flash_error'] = 'Error: ' . $e->getMessage();
            }

            header('Location: ' . APP_URL . '/user');
            exit;
        }

        // GET request: load user for editing (to keep backward compatibility for direct URL access)
        $user = $this->userModel->findUserByUsername($this->getUsernameById($id));
        if (!$user) {
            die("User not found.");
        }

        $userRoleIds = $this->userModel->getUserRoleIds($id);

        $data = [
            'title' => 'Edit User Account',
            'content_view' => 'users/edit',
            'user' => $user,
            'employees' => $this->employeeModel->getAllEmployees(),
            'roles' => $this->userModel->getAllRoles(),
            'userRoleIds' => $userRoleIds,
            'modules' => $this->modules,
            'error' => '',
            'success' => ''
        ];

        $this->view('layouts/main', $data);
    }

    /**
     * Delete user account
     */
    public function delete($id) {
        $this->checkPermission('user', 'delete');

        if ($id == $_SESSION['user_id']) {
            $_SESSION['flash_error'] = 'You cannot delete your own account.';
            header('Location: ' . APP_URL . '/user');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $transferToId = !empty($_POST['transfer_to']) ? intval($_POST['transfer_to']) : null;
            
            if (!$transferToId) {
                $_SESSION['flash_error'] = 'You must select a user to transfer data to.';
                header('Location: ' . APP_URL . '/user');
                exit;
            }

            $db = new Database();
            try {
                $db->beginTransaction();

                // Get employee_id and username of the user being deleted
                $db->query("SELECT employee_id, username FROM users WHERE id = :id");
                $db->bind(':id', $id);
                $deletedUser = $db->single();
                
                if (!$deletedUser) {
                    throw new Exception("User not found.");
                }

                $employeeId = $deletedUser->employee_id;
                $deletedUsername = $deletedUser->username;

                // List of tables and columns to transfer
                $transfers = [
                    ['table' => 'invoices', 'column' => 'created_by'],
                    ['table' => 'pending_collections', 'column' => 'created_by'],
                    ['table' => 'pending_collections', 'column' => 'mobile_rep_id'],
                    ['table' => 'rep_daily_routes', 'column' => 'user_id'],
                    ['table' => 'cheques', 'column' => 'created_by'],
                    ['table' => 'credit_notes', 'column' => 'created_by'],
                    ['table' => 'customer_payments', 'column' => 'created_by'],
                    ['table' => 'estimates', 'column' => 'created_by'],
                    ['table' => 'expenses', 'column' => 'created_by'],
                    ['table' => 'goods_receipt_notes', 'column' => 'created_by'],
                    ['table' => 'journal_entries', 'column' => 'created_by'],
                    ['table' => 'notifications', 'column' => 'user_id'],
                    ['table' => 'product_substitutions', 'column' => 'user_id'],
                    ['table' => 'purchase_orders', 'column' => 'created_by'],
                    ['table' => 'route_bindings', 'column' => 'created_by'],
                    ['table' => 'saved_reports', 'column' => 'user_id'],
                    ['table' => 'scheduled_reports', 'column' => 'user_id'],
                    ['table' => 'stock_ledger', 'column' => 'user_id'],
                    ['table' => 'supplier_payments', 'column' => 'created_by'],
                    ['table' => 'supplier_returns', 'column' => 'created_by'],
                    ['table' => 'warehouse_transfers', 'column' => 'created_by'],
                    ['table' => 'audit_logs', 'column' => 'user_id']
                ];

                foreach ($transfers as $t) {
                    $table = $t['table'];
                    $column = $t['column'];
                    
                    // First check if the table exists to avoid SQL errors
                    $db->query("SHOW TABLES LIKE :table_name");
                    $db->bind(':table_name', $table);
                    if ($db->single()) {
                        // Run update query
                        $db->query("UPDATE `{$table}` SET `{$column}` = :new_id WHERE `{$column}` = :old_id");
                        $db->bind(':new_id', $transferToId);
                        $db->bind(':old_id', $id);
                        $db->execute();
                    }
                }

                // Delete user roles
                $db->query("DELETE FROM user_roles WHERE user_id = :id");
                $db->bind(':id', $id);
                $db->execute();

                // Delete user permissions
                $db->query("DELETE FROM user_permissions WHERE user_id = :id");
                $db->bind(':id', $id);
                $db->execute();

                // Delete user
                $db->query("DELETE FROM users WHERE id = :id");
                $db->bind(':id', $id);
                $db->execute();

                // Delete linked employee if exists
                if ($employeeId) {
                    $db->query("DELETE FROM employees WHERE id = :emp_id");
                    $db->bind(':emp_id', $employeeId);
                    $db->execute();
                }

                $db->commit();
                
                $_SESSION['flash_success'] = "User '{$deletedUsername}' and their linked employee record were successfully deleted. All associated sales/route data has been transferred.";
            } catch (Exception $e) {
                $db->rollBack();
                $_SESSION['flash_error'] = "Failed to delete user and transfer data: " . $e->getMessage();
            }

            header('Location: ' . APP_URL . '/user');
            exit;
        }

        $_SESSION['flash_error'] = 'Invalid request method for user deletion.';
        header('Location: ' . APP_URL . '/user');
        exit;
    }

    /**
     * Delete employee account directly (since it has no user/login and no sales data)
     */
    public function delete_employee($id) {
        $this->checkPermission('hrm', 'delete');

        try {
            if ($this->employeeModel->deleteEmployee($id)) {
                $_SESSION['flash_success'] = 'Employee deleted successfully.';
            } else {
                $_SESSION['flash_error'] = 'Failed to delete employee.';
            }
        } catch (Exception $e) {
            $_SESSION['flash_error'] = 'Database Error: ' . $e->getMessage();
        }

        header('Location: ' . APP_URL . '/user');
        exit;
    }

    /**
     * List all system roles
     */
    public function roles() {
        $this->checkPermission('user', 'view');
        
        $roles = $this->userModel->getAllRoles();
        foreach ($roles as $r) {
            $r->permissions = $this->userModel->getRolePermissions($r->id);
        }

        $data = [
            'title' => 'Roles & Permissions',
            'content_view' => 'users/roles',
            'roles' => $roles
        ];

        $this->view('layouts/main', $data);
    }

    /**
     * Create system role
     */
    public function create_role() {
        $this->checkPermission('user', 'create_edit');

        $data = [
            'title' => 'Create Role',
            'content_view' => 'users/create_role',
            'modules' => $this->modules,
            'error' => '',
            'success' => ''
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $permissions = $_POST['permissions'] ?? [];

            if (empty($name)) {
                $data['error'] = 'Role name is required.';
            } else {
                if ($this->userModel->createRole($name, $description)) {
                    // Get newly created role's ID
                    $db = new Database();
                    $db->query("SELECT id FROM roles WHERE name = :name");
                    $db->bind(':name', $name);
                    $newRole = $db->single();
                    if ($newRole) {
                        $this->userModel->saveRolePermissions($newRole->id, $permissions);
                    }

                    $this->logActivity('Role Created', 'Security', "Role '{$name}' created with custom permissions successfully.");
                    $_SESSION['flash_success'] = 'Role created successfully!';
                    header('Location: ' . APP_URL . '/user/roles');
                    exit;
                } else {
                    $data['error'] = 'Failed to create role. Name might be already taken.';
                }
            }
        }

        $this->view('layouts/main', $data);
    }

    /**
     * Edit system role and permissions
     */
    public function edit_role($id) {
        $this->checkPermission('user', 'create_edit');

        $role = $this->userModel->findRoleById($id);
        if (!$role) {
            die("Role not found.");
        }

        $currentPermissions = $this->userModel->getRolePermissions($id);

        $data = [
            'title' => 'Edit Role',
            'content_view' => 'users/edit_role',
            'role' => $role,
            'modules' => $this->modules,
            'currentPermissions' => $currentPermissions,
            'error' => '',
            'success' => ''
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $permissions = $_POST['permissions'] ?? [];

            if (empty($name)) {
                $data['error'] = 'Role name is required.';
            } else {
                if ($this->userModel->updateRole($id, $name, $description)) {
                    $this->userModel->saveRolePermissions($id, $permissions);

                    // Refresh session permissions for all users possessing this role if they are currently logged in
                    if (in_array(intval($id), $this->userModel->getUserRoleIds($_SESSION['user_id']))) {
                        $_SESSION['permissions'] = $this->userModel->getUserPermissions($_SESSION['user_id']);
                    }

                    $this->logActivity('Role Updated', 'Security', "Role '{$name}' updated successfully.", $id);
                    $_SESSION['flash_success'] = 'Role updated successfully!';
                    header('Location: ' . APP_URL . '/user/roles');
                    exit;
                } else {
                    $data['error'] = 'Failed to update role.';
                }
            }
        }

        $this->view('layouts/main', $data);
    }

    /**
     * Delete system role
     */
    public function delete_role($id) {
        $this->checkPermission('user', 'delete');

        $role = $this->userModel->findRoleById($id);
        if ($role) {
            // Check if system default roles are protected
            $protected = ['admin', 'office staff', 'driver', 'rep (sales representative)', 'accountant'];
            if (in_array(strtolower($role->name), $protected)) {
                $_SESSION['flash_error'] = 'System default roles cannot be deleted.';
                header('Location: ' . APP_URL . '/user/roles');
                exit;
            }

            if ($this->userModel->deleteRole($id)) {
                $this->logActivity('Role Deleted', 'Security', "Role '{$role->name}' deleted successfully.", $id);
                $_SESSION['flash_success'] = "Role '{$role->name}' deleted successfully.";
            } else {
                $_SESSION['flash_error'] = 'Failed to delete role.';
            }
        }

        header('Location: ' . APP_URL . '/user/roles');
        exit;
    }

    private function getUsernameById($id) {
        $db = new Database();
        $db->query("SELECT username FROM users WHERE id = :id");
        $db->bind(':id', $id);
        $row = $db->single();
        return $row ? $row->username : null;
    }
}