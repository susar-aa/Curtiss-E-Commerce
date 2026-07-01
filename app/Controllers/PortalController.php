<?php
require_once '../core/Database.php';

class PortalController extends Controller {
    private $db;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Ensure customer session exists
        if (!isset($_SESSION['ec_user_id'])) {
            header('Location: ' . APP_URL . '/shop/login');
            exit;
        }
        
        $this->db = new Database();
    }

    private function getSettings() {
        $this->db->query("SELECT * FROM ecommerce_settings");
        $rows = $this->db->resultSet() ?: [];
        $settings = [];
        foreach ($rows as $r) {
            $settings[$r->key] = $r->value;
        }
        return $settings;
    }

    /**
     * Helper to load customer database record
     */
    private function getCustomerRecord() {
        $role = $_SESSION['ec_role'] ?? 'retail';
        if ($role === 'wholesaler') {
            $this->db->query("SELECT * FROM customers WHERE id = :id LIMIT 1");
        } else {
            $this->db->query("SELECT * FROM ecommerce_retail_customers WHERE id = :id LIMIT 1");
        }
        $this->db->bind(':id', $_SESSION['ec_user_id']);
        return $this->db->single();
    }

    /**
     * 1. Portal Home Dashboard
     */
    public function index() {
        $settings = $this->getSettings();
        $customer = $this->getCustomerRecord();
        $role = $_SESSION['ec_role'] ?? 'retail';

        // Fetch recent orders
        $customerId = $_SESSION['ec_user_id'];
        $btype = ($role === 'wholesaler') ? 'wholesale' : 'retail';

        // For wholesalers, we look for orders matching their customer_id
        // For retail customers, since orders are mapped to a dummy customer but tracking names/phones,
        // we retrieve orders either by customer_id or matching their contact email/phone
        if ($role === 'wholesaler') {
            $this->db->query("SELECT * FROM sales_orders WHERE customer_id = :cid AND billing_type = :btype ORDER BY id DESC LIMIT 5");
            $this->db->bind(':cid', $customerId);
        } else {
            // Find by customer_id if mapped, or filter by retail profile contact phone
            $this->db->query("SELECT * FROM sales_orders WHERE (customer_id = :cid OR customer_phone = :phone) AND billing_type = :btype ORDER BY id DESC LIMIT 5");
            $this->db->bind(':cid', $customerId);
            $this->db->bind(':phone', $customer->phone ?? 'NONE');
        }
        $this->db->bind(':btype', $btype);
        $recentOrders = $this->db->resultSet() ?: [];

        // Count wishlist items
        $this->db->query("SELECT COUNT(*) as count FROM ecommerce_wishlist WHERE customer_id = :cid AND customer_type = :ctype");
        $this->db->bind(':cid', $customerId);
        $this->db->bind(':ctype', $role);
        $wishCount = $this->db->single()->count ?? 0;

        // Fetch returns status list
        $this->db->query("SELECT r.*, o.order_number 
                          FROM ecommerce_returns r 
                          LEFT JOIN sales_orders o ON r.sales_order_id = o.id 
                          WHERE r.customer_id = :cid AND r.customer_type = :ctype ORDER BY r.id DESC");
        $this->db->bind(':cid', $customerId);
        $this->db->bind(':ctype', $role);
        $returns = $this->db->resultSet() ?: [];

        $this->view('layouts/shop', [
            'title' => 'My Account Portal | ' . ($settings['store_name'] ?? 'Curtiss Store'),
            'settings' => $settings,
            'content_view' => 'portal/dashboard',
            'customer' => $customer,
            'recent_orders' => $recentOrders,
            'wish_count' => $wishCount,
            'returns' => $returns
        ]);
    }

    /**
     * 2. View All Orders
     */
    public function orders() {
        $settings = $this->getSettings();
        $customer = $this->getCustomerRecord();
        $role = $_SESSION['ec_role'] ?? 'retail';
        $customerId = $_SESSION['ec_user_id'];
        $btype = ($role === 'wholesaler') ? 'wholesale' : 'retail';

        if ($role === 'wholesaler') {
            $this->db->query("SELECT * FROM sales_orders WHERE customer_id = :cid AND billing_type = :btype ORDER BY id DESC");
            $this->db->bind(':cid', $customerId);
        } else {
            $this->db->query("SELECT * FROM sales_orders WHERE (customer_id = :cid OR customer_phone = :phone) AND billing_type = :btype ORDER BY id DESC");
            $this->db->bind(':cid', $customerId);
            $this->db->bind(':phone', $customer->phone ?? 'NONE');
        }
        $this->db->bind(':btype', $btype);
        $orders = $this->db->resultSet() ?: [];

        $this->view('layouts/shop', [
            'title' => 'My Order History | ' . ($settings['store_name'] ?? 'Curtiss Store'),
            'settings' => $settings,
            'content_view' => 'portal/orders',
            'orders' => $orders
        ]);
    }

    /**
     * 3. Individual Order details / Receipt invoice
     */
    public function order_details($id) {
        $settings = $this->getSettings();
        $customer = $this->getCustomerRecord();
        $role = $_SESSION['ec_role'] ?? 'retail';
        $customerId = $_SESSION['ec_user_id'];

        $this->db->query("SELECT * FROM sales_orders WHERE id = :id LIMIT 1");
        $this->db->bind(':id', intval($id));
        $order = $this->db->single();

        if (!$order) {
            die("Order not found.");
        }

        // Verify ownership
        if ($role === 'wholesaler' && $order->customer_id != $customerId) {
            die("Unauthorized access to this order.");
        } elseif ($role === 'retail' && $order->customer_id != $customerId && $order->customer_phone !== $customer->phone) {
            die("Unauthorized access to this order.");
        }

        // Fetch items
        $this->db->query("SELECT * FROM sales_order_items WHERE sales_order_id = :oid");
        $this->db->bind(':oid', $order->id);
        $items = $this->db->resultSet() ?: [];

        $this->view('layouts/shop', [
            'title' => 'Order ' . $order->order_number . ' | ' . ($settings['store_name'] ?? 'Curtiss Store'),
            'settings' => $settings,
            'content_view' => 'portal/order_details',
            'order' => $order,
            'items' => $items
        ]);
    }

    /**
     * 4. Returns Request Management
     */
    public function returns() {
        $settings = $this->getSettings();
        $customer = $this->getCustomerRecord();
        $role = $_SESSION['ec_role'] ?? 'retail';
        $customerId = $_SESSION['ec_user_id'];

        $success = '';
        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'request_return') {
            $orderId = intval($_POST['sales_order_id']);
            $reason = trim($_POST['reason'] ?? '');
            $details = trim($_POST['details'] ?? '');

            if ($orderId > 0 && !empty($reason)) {
                // Verify order exists
                $this->db->query("SELECT * FROM sales_orders WHERE id = :oid LIMIT 1");
                $this->db->bind(':oid', $orderId);
                $order = $this->db->single();

                if ($order) {
                    $this->db->query("INSERT INTO ecommerce_returns (sales_order_id, customer_id, customer_type, reason, details, status) 
                                      VALUES (:oid, :cid, :ctype, :reason, :details, 'pending')");
                    $this->db->bind(':oid', $orderId);
                    $this->db->bind(':cid', $customerId);
                    $this->db->bind(':ctype', $role);
                    $this->db->bind(':reason', $reason);
                    $this->db->bind(':details', $details);
                    $this->db->execute();
                    $success = 'Your return request has been submitted successfully for moderation.';
                } else {
                    $error = 'Invalid order reference.';
                }
            } else {
                $error = 'Please fill out the return reason field.';
            }
        }

        // Fetch return history
        $this->db->query("SELECT r.*, o.order_number 
                          FROM ecommerce_returns r 
                          LEFT JOIN sales_orders o ON r.sales_order_id = o.id 
                          WHERE r.customer_id = :cid AND r.customer_type = :ctype ORDER BY r.id DESC");
        $this->db->bind(':cid', $customerId);
        $this->db->bind(':ctype', $role);
        $returns = $this->db->resultSet() ?: [];

        // Fetch eligible orders for return selection
        if ($role === 'wholesaler') {
            $this->db->query("SELECT id, order_number, order_date FROM sales_orders WHERE customer_id = :cid AND status = 'Delivered' ORDER BY id DESC");
            $this->db->bind(':cid', $customerId);
        } else {
            $this->db->query("SELECT id, order_number, order_date FROM sales_orders WHERE (customer_id = :cid OR customer_phone = :phone) AND status = 'Delivered' ORDER BY id DESC");
            $this->db->bind(':cid', $customerId);
            $this->db->bind(':phone', $customer->phone ?? 'NONE');
        }
        $eligibleOrders = $this->db->resultSet() ?: [];

        $this->view('layouts/shop', [
            'title' => 'Returns & Exchange | ' . ($settings['store_name'] ?? 'Curtiss Store'),
            'settings' => $settings,
            'content_view' => 'portal/returns',
            'returns' => $returns,
            'eligible_orders' => $eligibleOrders,
            'success' => $success,
            'error' => $error
        ]);
    }

    /**
     * 5. Wishlist Management
     */
    public function wishlist() {
        $settings = $this->getSettings();
        $role = $_SESSION['ec_role'] ?? 'retail';
        $customerId = $_SESSION['ec_user_id'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
            $action = $_POST['action'];
            $itemId = intval($_POST['item_id']);

            if ($action === 'add' && $itemId > 0) {
                // Ensure not already in wishlist
                $this->db->query("SELECT id FROM ecommerce_wishlist WHERE customer_id = :cid AND customer_type = :ctype AND item_id = :item LIMIT 1");
                $this->db->bind(':cid', $customerId);
                $this->db->bind(':ctype', $role);
                $this->db->bind(':item', $itemId);
                if (!$this->db->single()) {
                    $this->db->query("INSERT INTO ecommerce_wishlist (customer_id, customer_type, item_id) VALUES (:cid, :ctype, :item)");
                    $this->db->bind(':cid', $customerId);
                    $this->db->bind(':ctype', $role);
                    $this->db->bind(':item', $itemId);
                    $this->db->execute();
                }
                header('Location: ' . $_SERVER['HTTP_REFERER']);
                exit;
            }

            if ($action === 'delete' && $itemId > 0) {
                $this->db->query("DELETE FROM ecommerce_wishlist WHERE customer_id = :cid AND customer_type = :ctype AND item_id = :item");
                $this->db->bind(':cid', $customerId);
                $this->db->bind(':ctype', $role);
                $this->db->bind(':item', $itemId);
                $this->db->execute();
                header('Location: ' . APP_URL . '/portal/wishlist');
                exit;
            }
        }

        // Fetch wishlist items
        $this->db->query("SELECT w.*, i.name as item_name, i.price, i.wholesale_price, i.image_path, i.qty 
                          FROM ecommerce_wishlist w 
                          LEFT JOIN items i ON w.item_id = i.id 
                          WHERE w.customer_id = :cid AND w.customer_type = :ctype ORDER BY w.id DESC");
        $this->db->bind(':cid', $customerId);
        $this->db->bind(':ctype', $role);
        $items = $this->db->resultSet() ?: [];

        $this->view('layouts/shop', [
            'title' => 'My Wishlist | ' . ($settings['store_name'] ?? 'Curtiss Store'),
            'settings' => $settings,
            'content_view' => 'portal/wishlist',
            'items' => $items
        ]);
    }

    /**
     * 6. Profile Management
     */
    public function profile() {
        $settings = $this->getSettings();
        $role = $_SESSION['ec_role'] ?? 'retail';
        $customerId = $_SESSION['ec_user_id'];

        $success = '';
        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $username = trim($_POST['username'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $city = trim($_POST['city'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $password = $_POST['password'] ?? '';

            if ($role === 'wholesaler') {
                // Update wholesaler customers profile
                try {
                    $sql = "UPDATE customers SET name = :name, email = :email, username = :uname, phone = :phone, address = :addr, territory = :city";
                    if (!empty($password)) {
                        $sql .= ", password = :pass";
                    }
                    $sql .= " WHERE id = :id";

                    $this->db->query($sql);
                    $this->db->bind(':name', $name);
                    $this->db->bind(':email', $email);
                    $this->db->bind(':uname', $username);
                    $this->db->bind(':phone', $phone);
                    $this->db->bind(':addr', $address);
                    $this->db->bind(':city', $city);
                    $this->db->bind(':id', $customerId);

                    if (!empty($password)) {
                        $this->db->bind(':pass', password_hash($password, PASSWORD_BCRYPT));
                    }
                    $this->db->execute();
                    $success = 'Profile details updated successfully.';
                    $_SESSION['ec_name'] = $name;
                } catch (Exception $e) {
                    $error = 'Failed to update profile: ' . $e->getMessage();
                }
            } else {
                // Update retail customer profile
                try {
                    $sql = "UPDATE ecommerce_retail_customers SET name = :name, email = :email, username = :uname, phone = :phone, address = :addr, city = :city";
                    if (!empty($password)) {
                        $sql .= ", password = :pass";
                    }
                    $sql .= " WHERE id = :id";

                    $this->db->query($sql);
                    $this->db->bind(':name', $name);
                    $this->db->bind(':email', $email);
                    $this->db->bind(':uname', $username);
                    $this->db->bind(':phone', $phone);
                    $this->db->bind(':addr', $address);
                    $this->db->bind(':city', $city);
                    $this->db->bind(':id', $customerId);

                    if (!empty($password)) {
                        $this->db->bind(':pass', password_hash($password, PASSWORD_BCRYPT));
                    }
                    $this->db->execute();
                    $success = 'Profile details updated successfully.';
                    $_SESSION['ec_name'] = $name;
                } catch (Exception $e) {
                    $error = 'Failed to update profile: ' . $e->getMessage();
                }
            }
        }

        $customer = $this->getCustomerRecord();

        $this->view('layouts/shop', [
            'title' => 'Profile Settings | ' . ($settings['store_name'] ?? 'Curtiss Store'),
            'settings' => $settings,
            'content_view' => 'portal/profile',
            'customer' => $customer,
            'success' => $success,
            'error' => $error
        ]);
    }
}