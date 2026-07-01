<?php
require_once '../core/Database.php';

class ShopController extends Controller {
    private $db;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->db = new Database();
        $this->trackVisitor();
    }

    /**
     * Storefront Analytics: Log unique visitors & page views
     */
    private function trackVisitor() {
        try {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $today = date('Y-m-d');
            
            $this->db->query("INSERT INTO ecommerce_visitors (ip_address, visit_date, page_views) 
                              VALUES (:ip, :vdate, 1) 
                              ON DUPLICATE KEY UPDATE page_views = page_views + 1");
            $this->db->bind(':ip', $ip);
            $this->db->bind(':vdate', $today);
            $this->db->execute();
        } catch (Exception $e) {
            // Silence exceptions to keep storefront running
        }
    }

    /**
     * Helper to load settings from db
     */
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
     * Helper to calculate item price depending on wholesale/retailer session role
     */
    private function getItemPrice($item) {
        $role = $_SESSION['ec_role'] ?? 'guest';
        if ($role === 'wholesaler') {
            return (float)($item->wholesale_price ?? 0);
        }
        return (float)($item->price ?? 0);
    }

    /**
     * 1. Storefront Home Page
     */
    public function index() {
        $settings = $this->getSettings();
        
        // Fetch active banners
        $this->db->query("SELECT * FROM ecommerce_banners WHERE is_active = 1 AND (start_date IS NULL OR start_date <= CURRENT_DATE) AND (end_date IS NULL OR end_date >= CURRENT_DATE) ORDER BY id DESC");
        $banners = $this->db->resultSet() ?: [];

        // Fetch enabled homepage sections
        $this->db->query("SELECT * FROM homepage_sections WHERE is_enabled = 1 ORDER BY sort_order ASC");
        $sections = $this->db->resultSet() ?: [];

        $layoutData = [];
        foreach ($sections as $sec) {
            $secKey = $sec->section_name;
            $config = json_encode($sec->config) ? json_decode($sec->config, true) : [];
            $limit = intval($config['limit'] ?? 8);

            switch($secKey) {
                case 'featured_categories':
                    $this->db->query("SELECT * FROM item_categories WHERE is_featured = 1 ORDER BY name ASC LIMIT :lim");
                    $this->db->bind(':lim', $limit);
                    $layoutData['featured_categories'] = $this->db->resultSet();
                    break;

                case 'featured_products':
                    $this->db->query("SELECT * FROM items WHERE is_published = 1 AND is_featured = 1 LIMIT :lim");
                    $this->db->bind(':lim', $limit);
                    $layoutData['featured_products'] = $this->db->resultSet();
                    break;

                case 'new_arrivals':
                    $this->db->query("SELECT * FROM items WHERE is_published = 1 AND is_new_arrival = 1 ORDER BY id DESC LIMIT :lim");
                    $this->db->bind(':lim', $limit);
                    $layoutData['new_arrivals'] = $this->db->resultSet();
                    break;

                case 'best_sellers':
                    $this->db->query("SELECT * FROM items WHERE is_published = 1 AND is_bestseller = 1 LIMIT :lim");
                    $this->db->bind(':lim', $limit);
                    $layoutData['best_sellers'] = $this->db->resultSet();
                    break;

                case 'special_offers':
                    $this->db->query("SELECT * FROM items WHERE is_published = 1 AND is_special_offer = 1 LIMIT :lim");
                    $this->db->bind(':lim', $limit);
                    $layoutData['special_offers'] = $this->db->resultSet();
                    break;

                case 'blog_articles':
                    $this->db->query("SELECT * FROM ecommerce_blog_posts ORDER BY id DESC LIMIT :lim");
                    $this->db->bind(':lim', $limit);
                    $layoutData['blog_articles'] = $this->db->resultSet();
                    break;
            }
        }

        // Render main layout
        $this->view('layouts/shop', [
            'title' => $settings['seo_title'] ?? 'Curtiss Stationery Store',
            'settings' => $settings,
            'content_view' => 'shop/home',
            'banners' => $banners,
            'sections' => $sections,
            'layout_data' => $layoutData
        ]);
    }

    /**
     * 2. Category Browse & Search
     */
    public function category($catSeoUrl = null) {
        $settings = $this->getSettings();
        
        // Fetch all categories for sidebar filter
        $this->db->query("SELECT * FROM item_categories ORDER BY name ASC");
        $categories = $this->db->resultSet() ?: [];

        // Build product query
        $queryStr = "SELECT i.*, c.name as category_name 
                     FROM items i 
                     LEFT JOIN item_categories c ON i.category_id = c.id 
                     WHERE i.is_published = 1";
        
        $params = [];

        if ($catSeoUrl !== null) {
            $queryStr .= " AND c.seo_url = :seo";
            $params[':seo'] = $catSeoUrl;
        }

        // Text Search
        if (!empty($_GET['q'])) {
            $queryStr .= " AND (i.name LIKE :search OR i.item_code LIKE :search)";
            $params[':search'] = '%' . $_GET['q'] . '%';
        }

        // Min/Max Price filter
        if (isset($_GET['min_price']) && $_GET['min_price'] !== '') {
            $queryStr .= " AND i.price >= :min";
            $params[':min'] = floatval($_GET['min_price']);
        }
        if (isset($_GET['max_price']) && $_GET['max_price'] !== '') {
            $queryStr .= " AND i.price <= :max";
            $params[':max'] = floatval($_GET['max_price']);
        }

        // Sort ordering
        $sort = $_GET['sort'] ?? 'newest';
        if ($sort === 'price_asc') {
            $queryStr .= " ORDER BY i.price ASC";
        } elseif ($sort === 'price_desc') {
            $queryStr .= " ORDER BY i.price DESC";
        } else {
            $queryStr .= " ORDER BY i.id DESC";
        }

        $this->db->query($queryStr);
        foreach ($params as $k => $v) {
            $this->db->bind($k, $v);
        }
        $products = $this->db->resultSet() ?: [];

        // Current Category Name
        $currentCatName = 'All Stationery Products';
        if ($catSeoUrl !== null) {
            foreach ($categories as $c) {
                if ($c->seo_url === $catSeoUrl) {
                    $currentCatName = $c->name;
                    break;
                }
            }
        }

        $this->view('layouts/shop', [
            'title' => $currentCatName . ' | ' . ($settings['store_name'] ?? 'Curtiss Store'),
            'settings' => $settings,
            'content_view' => 'shop/category',
            'categories' => $categories,
            'products' => $products,
            'current_category' => $currentCatName,
            'current_seo' => $catSeoUrl
        ]);
    }

    /**
     * 3. Product Details Page
     */
    public function item($id) {
        $settings = $this->getSettings();
        
        $this->db->query("SELECT i.*, c.name as category_name 
                          FROM items i 
                          LEFT JOIN item_categories c ON i.category_id = c.id 
                          WHERE i.id = :id AND i.is_published = 1 LIMIT 1");
        $this->db->bind(':id', intval($id));
        $item = $this->db->single();

        if (!$item) {
            die("Product not found or has been unpublished.");
        }

        // Fetch approved reviews
        $this->db->query("SELECT * FROM ecommerce_reviews WHERE item_id = :id AND status = 'approved' ORDER BY id DESC");
        $this->db->bind(':id', $item->id);
        $reviews = $this->db->resultSet() ?: [];

        // Fetch related products (same category)
        $this->db->query("SELECT * FROM items WHERE category_id = :cat AND id != :id AND is_published = 1 LIMIT 4");
        $this->db->bind(':cat', $item->category_id);
        $this->db->bind(':id', $item->id);
        $related = $this->db->resultSet() ?: [];

        $this->view('layouts/shop', [
            'title' => htmlspecialchars($item->name) . ' | ' . ($settings['store_name'] ?? 'Curtiss Store'),
            'settings' => $settings,
            'content_view' => 'shop/item',
            'item' => $item,
            'reviews' => $reviews,
            'related' => $related
        ]);
    }

    /**
     * 4. Shopping Cart
     */
    public function cart() {
        $settings = $this->getSettings();
        
        if (!isset($_SESSION['ec_cart'])) {
            $_SESSION['ec_cart'] = [];
        }

        // Process POST actions (add/update/delete)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';

            if ($action === 'add') {
                $itemId = intval($_POST['item_id']);
                $qty = intval($_POST['qty'] ?? 1);
                
                $this->db->query("SELECT * FROM items WHERE id = :id LIMIT 1");
                $this->db->bind(':id', $itemId);
                $item = $this->db->single();

                if ($item) {
                    $price = $this->getItemPrice($item);
                    $cartKey = $itemId;

                    if (isset($_SESSION['ec_cart'][$cartKey])) {
                        $_SESSION['ec_cart'][$cartKey]['qty'] += $qty;
                    } else {
                        $_SESSION['ec_cart'][$cartKey] = [
                            'item_id' => $item->id,
                            'name' => $item->name,
                            'sku' => $item->item_code,
                            'price' => $price,
                            'qty' => $qty,
                            'image_path' => $item->image_path
                        ];
                    }
                    header('Location: ' . APP_URL . '/shop/cart');
                    exit;
                }
            }

            if ($action === 'update') {
                $quantities = $_POST['qty'] ?? [];
                foreach ($quantities as $key => $newQty) {
                    $newQty = intval($newQty);
                    if ($newQty <= 0) {
                        unset($_SESSION['ec_cart'][$key]);
                    } else {
                        $_SESSION['ec_cart'][$key]['qty'] = $newQty;
                    }
                }
                header('Location: ' . APP_URL . '/shop/cart');
                exit;
            }

            if ($action === 'delete') {
                $key = $_POST['cart_key'] ?? '';
                if (isset($_SESSION['ec_cart'][$key])) {
                    unset($_SESSION['ec_cart'][$key]);
                }
                header('Location: ' . APP_URL . '/shop/cart');
                exit;
            }
        }

        $this->view('layouts/shop', [
            'title' => 'Shopping Cart | ' . ($settings['store_name'] ?? 'Curtiss Store'),
            'settings' => $settings,
            'content_view' => 'shop/cart'
        ]);
    }

    /**
     * 5. Checkout Process
     */
    public function checkout() {
        $settings = $this->getSettings();
        
        if (empty($_SESSION['ec_cart'])) {
            header('Location: ' . APP_URL . '/shop/cart');
            exit;
        }

        $error = '';
        $success = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_order') {
            try {
                $this->db->beginTransaction();

                // Compute cart subtotal
                $subtotal = 0;
                foreach($_SESSION['ec_cart'] as $item) {
                    $subtotal += $item['price'] * $item['qty'];
                }

                $discount = 0;
                $couponCode = trim($_POST['coupon_code'] ?? '');
                if (!empty($couponCode)) {
                    $this->db->query("SELECT * FROM ecommerce_coupons WHERE code = :code AND is_active = 1 AND (expiry_date IS NULL OR expiry_date >= CURRENT_DATE) LIMIT 1");
                    $this->db->bind(':code', $couponCode);
                    $coupon = $this->db->single();
                    if ($coupon) {
                        if ($subtotal >= $coupon->min_spend) {
                            if ($coupon->type === 'percent') {
                                $discount = ($subtotal * ($coupon->value / 100));
                            } else {
                                $discount = floatval($coupon->value);
                            }
                        }
                    }
                }

                $grandTotal = max(0, $subtotal - $discount);
                $orderNo = 'ECO-' . time() . '-' . rand(1000, 9999);
                $orderDate = date('Y-m-d');
                $dueDate = date('Y-m-d', strtotime('+7 days'));

                $customerId = 0;
                $customerName = '';
                $customerPhone = '';
                $userRole = $_SESSION['ec_role'] ?? 'guest';

                if ($userRole === 'wholesaler') {
                    $customerId = $_SESSION['ec_customer_id'];
                    $customerName = $_SESSION['ec_name'];
                    
                    $this->db->query("SELECT phone FROM customers WHERE id = :id LIMIT 1");
                    $this->db->bind(':id', $customerId);
                    $cRow = $this->db->single();
                    $customerPhone = $cRow->phone ?? '';
                } else {
                    // Fetch or generate retail customer profile
                    $this->db->query("SELECT id FROM customers WHERE name = 'E-Commerce Retail Customer' LIMIT 1");
                    $cRow = $this->db->single();
                    if ($cRow) {
                        $customerId = $cRow->id;
                    } else {
                        $this->db->query("INSERT INTO customers (name, email, phone, address, territory) 
                                          VALUES ('E-Commerce Retail Customer', 'ecommerce@retail.com', '0000000000', 'Online Storefront', 'E-Commerce')");
                        $this->db->execute();
                        $this->db->query("SELECT LAST_INSERT_ID() as id");
                        $res = $this->db->single();
                        $customerId = $res->id;
                    }
                    $customerName = trim($_POST['billing_name'] ?? 'E-Commerce Customer');
                    $customerPhone = trim($_POST['billing_phone'] ?? '');
                }

                // Insert order into sales_orders
                $this->db->query("INSERT INTO sales_orders (order_number, customer_id, customer_name, customer_phone, billing_type, subtotal, discount, grand_total, order_date, due_date, status, notes) 
                                  VALUES (:order_num, :cid, :cname, :cphone, :btype, :sub, :disc, :grand, :odate, :ddate, 'Pending', :notes)");
                $this->db->bind(':order_num', $orderNo);
                $this->db->bind(':cid', $customerId);
                $this->db->bind(':cname', $customerName);
                $this->db->bind(':cphone', $customerPhone);
                $this->db->bind(':btype', ($userRole === 'wholesaler' ? 'wholesale' : 'retail'));
                $this->db->bind(':sub', $subtotal);
                $this->db->bind(':disc', $discount);
                $this->db->bind(':grand', $grandTotal);
                $this->db->bind(':odate', $orderDate);
                $this->db->bind(':ddate', $dueDate);
                
                $shippingAddress = trim($_POST['shipping_address'] ?? 'Not specified');
                $notes = "Order placed online via E-Commerce portal. Shipping to: " . $shippingAddress;
                if (!empty($couponCode)) {
                    $notes .= " | Coupon Applied: " . $couponCode;
                }
                $this->db->bind(':notes', $notes);
                $this->db->execute();

                $this->db->query("SELECT LAST_INSERT_ID() as id");
                $res = $this->db->single();
                $orderId = $res->id;

                // Insert items & subtract stock quantities
                foreach ($_SESSION['ec_cart'] as $cartItem) {
                    $itemTotal = $cartItem['price'] * $cartItem['qty'];
                    
                    // Insert
                    $this->db->query("INSERT INTO sales_order_items (sales_order_id, item_id, sku, name, billing_price, qty, total) 
                                      VALUES (:oid, :item_id, :sku, :name, :price, :qty, :total)");
                    $this->db->bind(':oid', $orderId);
                    $this->db->bind(':item_id', $cartItem['item_id']);
                    $this->db->bind(':sku', $cartItem['sku']);
                    $this->db->bind(':name', $cartItem['name']);
                    $this->db->bind(':price', $cartItem['price']);
                    $this->db->bind(':qty', $cartItem['qty']);
                    $this->db->bind(':total', $itemTotal);
                    $this->db->execute();

                    // Subtract stock
                    $this->db->query("UPDATE items SET qty = qty - :qty WHERE id = :item_id");
                    $this->db->bind(':qty', $cartItem['qty']);
                    $this->db->bind(':item_id', $cartItem['item_id']);
                    $this->db->execute();
                }

                $this->db->commit();
                $_SESSION['ec_cart'] = []; // Clear Cart
                $_SESSION['last_order_no'] = $orderNo;
                header('Location: ' . APP_URL . '/shop/order_success');
                exit;
            } catch (Exception $e) {
                $this->db->rollBack();
                $error = 'Failed to submit order: ' . $e->getMessage();
            }
        }

        $this->view('layouts/shop', [
            'title' => 'Checkout | ' . ($settings['store_name'] ?? 'Curtiss Store'),
            'settings' => $settings,
            'content_view' => 'shop/checkout',
            'error' => $error
        ]);
    }

    /**
     * 6. Order Success Confirmation Screen
     */
    public function order_success() {
        $settings = $this->getSettings();
        $orderNo = $_SESSION['last_order_no'] ?? '';
        unset($_SESSION['last_order_no']);

        $this->view('layouts/shop', [
            'title' => 'Thank you for your order | ' . ($settings['store_name'] ?? 'Curtiss Store'),
            'settings' => $settings,
            'content_view' => 'shop/order_success',
            'order_number' => $orderNo
        ]);
    }

    /**
     * 7. Customer Portal: Register / Login Form
     */
    public function login() {
        $settings = $this->getSettings();
        
        if (isset($_SESSION['ec_user_id'])) {
            header('Location: ' . APP_URL . '/shop');
            exit;
        }

        $error = '';
        $success = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';

            if ($action === 'login') {
                $loginInput = trim($_POST['username_or_email'] ?? '');
                $password = $_POST['password'] ?? '';

                if (empty($loginInput) || empty($password)) {
                    $error = 'Both login identifier and password are required.';
                } else {
                    // Check wholesaler accounts in customers table
                    $this->db->query("SELECT * FROM customers WHERE (username = :login OR email = :login) AND password IS NOT NULL LIMIT 1");
                    $this->db->bind(':login', $loginInput);
                    $wholesaler = $this->db->single();

                    if ($wholesaler && password_verify($password, $wholesaler->password)) {
                        $_SESSION['ec_user_id'] = $wholesaler->id;
                        $_SESSION['ec_role'] = 'wholesaler';
                        $_SESSION['ec_name'] = $wholesaler->name;
                        $_SESSION['ec_customer_id'] = $wholesaler->id;
                        header('Location: ' . APP_URL . '/shop');
                        exit;
                    }

                    // Check retail customer table
                    $this->db->query("SELECT * FROM ecommerce_retail_customers WHERE username = :login OR email = :login LIMIT 1");
                    $this->db->bind(':login', $loginInput);
                    $retail = $this->db->single();

                    if ($retail && password_verify($password, $retail->password)) {
                        $_SESSION['ec_user_id'] = $retail->id;
                        $_SESSION['ec_role'] = 'retail';
                        $_SESSION['ec_name'] = $retail->name;
                        header('Location: ' . APP_URL . '/shop');
                        exit;
                    }

                    $error = 'Invalid credentials. Please verify your login details.';
                }
            }

            if ($action === 'register_retail') {
                $name = trim($_POST['name'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $username = trim($_POST['username'] ?? '');
                $password = $_POST['password'] ?? '';
                $phone = trim($_POST['phone'] ?? '');
                $city = trim($_POST['city'] ?? '');
                $address = trim($_POST['address'] ?? '');

                if (empty($name) || empty($email) || empty($username) || empty($password)) {
                    $error = 'All primary registration fields are required.';
                } else {
                    try {
                        $hashed = password_hash($password, PASSWORD_BCRYPT);
                        $this->db->query("INSERT INTO ecommerce_retail_customers (name, email, username, password, phone, address, city) 
                                          VALUES (:name, :email, :username, :pass, :phone, :address, :city)");
                        $this->db->bind(':name', $name);
                        $this->db->bind(':email', $email);
                        $this->db->bind(':username', $username);
                        $this->db->bind(':pass', $hashed);
                        $this->db->bind(':phone', $phone);
                        $this->db->bind(':address', $address);
                        $this->db->bind(':city', $city);
                        $this->db->execute();

                        $this->db->query("SELECT LAST_INSERT_ID() as id");
                        $res = $this->db->single();

                        $_SESSION['ec_user_id'] = $res->id;
                        $_SESSION['ec_role'] = 'retail';
                        $_SESSION['ec_name'] = $name;
                        header('Location: ' . APP_URL . '/shop');
                        exit;
                    } catch (PDOException $e) {
                        if ($e->getCode() == 23000) {
                            $error = 'Email or Username already in use.';
                        } else {
                            $error = 'Registration failed: ' . $e->getMessage();
                        }
                    }
                }
            }

            if ($action === 'submit_wholesaler_request') {
                $businessName = trim($_POST['business_name'] ?? '');
                $address = trim($_POST['address'] ?? '');
                $contact = trim($_POST['contact_number'] ?? '');
                $city = trim($_POST['city'] ?? '');
                $email = trim($_POST['email_address'] ?? '');
                $username = trim($_POST['username'] ?? '');
                $password = $_POST['password'] ?? '';
                $notes = trim($_POST['notes'] ?? '');

                if (empty($businessName) || empty($email) || empty($username) || empty($password)) {
                    $error = 'Business Name, Email Address, Username, and Password are required.';
                } else {
                    try {
                        $this->db->query("INSERT INTO wholesaler_requests (business_name, address, contact_number, city, email_address, username, password, notes, status) 
                                          VALUES (:bname, :address, :contact, :city, :email, :uname, :pass, :notes, 'pending')");
                        $this->db->bind(':bname', $businessName);
                        $this->db->bind(':address', $address);
                        $this->db->bind(':contact', $contact);
                        $this->db->bind(':city', $city);
                        $this->db->bind(':email', $email);
                        $this->db->bind(':uname', $username);
                        $this->db->bind(':pass', $password); // Admin will hash upon approval
                        $this->db->bind(':notes', $notes);
                        $this->db->execute();

                        $success = 'Your wholesaler registration request has been submitted successfully. Our sales team will verify details and notify you soon.';
                    } catch (Exception $ex) {
                        $error = 'Failed to submit onboarding request: ' . $ex->getMessage();
                    }
                }
            }
        }

        $this->view('layouts/shop', [
            'title' => 'Sign In / Sign Up | ' . ($settings['store_name'] ?? 'Curtiss Store'),
            'settings' => $settings,
            'content_view' => 'shop/login',
            'error' => $error,
            'success' => $success
        ]);
    }

    /**
     * 8. End customer session
     */
    public function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        unset($_SESSION['ec_user_id']);
        unset($_SESSION['ec_role']);
        unset($_SESSION['ec_name']);
        unset($_SESSION['ec_customer_id']);
        header('Location: ' . APP_URL . '/shop');
        exit;
    }

    /**
     * 9. Write product review
     */
    public function submit_review() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $itemId = intval($_POST['item_id']);
            $reviewerName = trim($_POST['reviewer_name'] ?? 'Guest Buyer');
            $reviewerEmail = trim($_POST['reviewer_email'] ?? '');
            $rating = intval($_POST['rating'] ?? 5);
            $comment = trim($_POST['comment'] ?? '');

            if ($itemId > 0 && !empty($reviewerEmail) && !empty($comment)) {
                $this->db->query("INSERT INTO ecommerce_reviews (item_id, customer_name, rating, review_text, status) 
                                  VALUES (:item_id, :cname, :rating, :comment, 'pending')");
                $this->db->bind(':item_id', $itemId);
                $this->db->bind(':cname', $reviewerName);
                $this->db->bind(':rating', $rating);
                $this->db->bind(':comment', $comment);
                $this->db->execute();
            }
        }
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }

    /**
     * 10. Storefront Blog
     */
    public function blog() {
        $settings = $this->getSettings();
        
        $this->db->query("SELECT * FROM ecommerce_blog_posts ORDER BY id DESC");
        $posts = $this->db->resultSet() ?: [];

        $this->view('layouts/shop', [
            'title' => 'Company Blog & Guides | ' . ($settings['store_name'] ?? 'Curtiss Store'),
            'settings' => $settings,
            'content_view' => 'shop/blog',
            'posts' => $posts
        ]);
    }

    /**
     * 11. Blog Post Details
     */
    public function blog_post($slug) {
        $settings = $this->getSettings();
        
        $this->db->query("SELECT * FROM ecommerce_blog_posts WHERE seo_url = :slug LIMIT 1");
        $this->db->bind(':slug', $slug);
        $post = $this->db->single();

        if (!$post) {
            die("Article not found.");
        }

        $this->view('layouts/shop', [
            'title' => htmlspecialchars($post->title) . ' | ' . ($settings['store_name'] ?? 'Curtiss Store'),
            'settings' => $settings,
            'content_view' => 'shop/blog_post',
            'post' => $post
        ]);
    }
}
