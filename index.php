<?php
session_start();

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'suzxlabs');
define('DB_PASS', 'Susara@200611003614');
define('DB_NAME', 'curtiss_erp');

// Connect to Database
try {
    $db = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
    ]);

    // Self-healing database schema migrations
    try {
        // Ensure customers table has username and password columns
        $stmt = $db->query("SHOW COLUMNS FROM customers LIKE 'username'");
        if (!$stmt->fetch()) {
            $db->exec("ALTER TABLE customers ADD COLUMN username VARCHAR(100) UNIQUE NULL AFTER email");
        }
        $stmt = $db->query("SHOW COLUMNS FROM customers LIKE 'password'");
        if (!$stmt->fetch()) {
            $db->exec("ALTER TABLE customers ADD COLUMN password VARCHAR(255) NULL AFTER username");
        }

        // Ensure wholesaler_requests table exists
        $db->exec("CREATE TABLE IF NOT EXISTS wholesaler_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            business_name VARCHAR(150) NOT NULL,
            address TEXT NOT NULL,
            contact_number VARCHAR(50) NOT NULL,
            city VARCHAR(100) NOT NULL,
            email_address VARCHAR(150) NOT NULL UNIQUE,
            username VARCHAR(100) NOT NULL,
            password VARCHAR(255) NOT NULL,
            notes TEXT NULL,
            status ENUM('pending', 'approved', 'declined') DEFAULT 'pending',
            linked_customer_id INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (linked_customer_id) REFERENCES customers(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Ensure ecommerce_retail_customers table exists
        $db->exec("CREATE TABLE IF NOT EXISTS ecommerce_retail_customers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(150) NOT NULL UNIQUE,
            username VARCHAR(100) UNIQUE NULL,
            password VARCHAR(255) NOT NULL,
            phone VARCHAR(50) NULL,
            address TEXT NULL,
            city VARCHAR(100) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Ensure ecommerce_wishlist table exists
        $db->exec("CREATE TABLE IF NOT EXISTS ecommerce_wishlist (
            id INT AUTO_INCREMENT PRIMARY KEY,
            customer_id INT NOT NULL,
            customer_type VARCHAR(50) NOT NULL,
            item_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY cust_item (customer_id, customer_type, item_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Ensure ecommerce_reviews table exists
        $db->exec("CREATE TABLE IF NOT EXISTS ecommerce_reviews (
            id INT AUTO_INCREMENT PRIMARY KEY,
            item_id INT NOT NULL,
            customer_name VARCHAR(150) NOT NULL,
            customer_email VARCHAR(150) NOT NULL,
            rating INT NOT NULL,
            review_text TEXT NOT NULL,
            status ENUM('pending', 'approved', 'declined') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Ensure ecommerce_returns table exists
        $db->exec("CREATE TABLE IF NOT EXISTS ecommerce_returns (
            id INT AUTO_INCREMENT PRIMARY KEY,
            sales_order_id INT NOT NULL,
            reason VARCHAR(255) NOT NULL,
            details TEXT NULL,
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Ensure ecommerce_visitors table exists
        $db->exec("CREATE TABLE IF NOT EXISTS ecommerce_visitors (
            ip_address VARCHAR(45) NOT NULL,
            visit_date DATE NOT NULL,
            page_views INT DEFAULT 1,
            PRIMARY KEY (ip_address, visit_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    } catch (Exception $schemaEx) {
        // Safe fallback in case of concurrency or lock
    }

} catch (PDOException $e) {
    die("<div style='padding:40px; font-family:sans-serif; text-align:center;'>
            <h2 style='color:#ff3b30;'>E-Commerce Offline</h2>
            <p>Could not connect to the Curtiss ERP Database. Please verify settings.</p>
            <p style='color:#666; font-size:12px;'>Error: {$e->getMessage()}</p>
         </div>");
}

// Fetch ecommerce settings
$settings = [];
try {
    $rows = $db->query("SELECT * FROM ecommerce_settings")->fetchAll();
    foreach ($rows as $row) {
        $settings[$row->key] = $row->value;
    }
} catch (Exception $e) {
    // Fallback if settings table is empty
}

// Helper to get ERP Base URL dynamically based on hosting environment
function getErpBaseUrl() {
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    if (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false) {
        return 'http://localhost/Curtiss-ERP/';
    }
    return 'https://curtiss.suzxlabs.com/';
}

// Clean and format product image URL
function getProductImageUrl($path) {
    if (empty($path)) {
        return '';
    }
    $base = getErpBaseUrl();
    $path = ltrim($path, '/');
    if (strpos($path, 'public/') === 0 || strpos($path, 'uploads/') === 0) {
        return $base . $path;
    }
    return $base . 'public/uploads/products/' . $path;
}

// Clean and format banner image URL
function getBannerImageUrl($path) {
    if (empty($path)) {
        return '';
    }
    $base = getErpBaseUrl();
    $path = ltrim($path, '/');
    if (strpos($path, 'public/') === 0 || strpos($path, 'uploads/') === 0) {
        return $base . $path;
    }
    return $base . 'public/uploads/banners/' . $path;
}

// Simple Router
$page = $_GET['p'] ?? 'home';

// Cart Initializer
if (!isset($_SESSION['ec_cart'])) {
    $_SESSION['ec_cart'] = [];
}

// Authentication Info
$isLoggedIn = isset($_SESSION['ec_user_id']);
$userRole = $_SESSION['ec_role'] ?? 'guest'; // 'retail', 'wholesaler'
$userName = $_SESSION['ec_name'] ?? '';

// Track visitor views
try {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $today = date('Y-m-d');
    $vStmt = $db->prepare("INSERT INTO ecommerce_visitors (ip_address, visit_date, page_views) 
                           VALUES (:ip, :vdate, 1) 
                           ON DUPLICATE KEY UPDATE page_views = page_views + 1");
    $vStmt->execute([':ip' => $ip, ':vdate' => $today]);
} catch (Exception $e) {
    // Silence
}

// --- HELPER FUNCTION: Get Price for Item ---
function getItemPrice($item, $role) {
    if ($role === 'wholesaler') {
        return (float)($item->wholesale_price ?? 0);
    }
    return (float)($item->price ?? 0);
}

// Handle Form Submissions
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. LOGIN
    if ($action === 'login') {
        $loginInput = trim($_POST['username_or_email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($loginInput) || empty($password)) {
            $error = 'Both login ID and password are required.';
        } else {
            // Check wholesaler accounts in customers table
            $stmt = $db->prepare("SELECT * FROM customers WHERE username = :login OR email = :login LIMIT 1");
            $stmt->execute([':login' => $loginInput]);
            $wholesaler = $stmt->fetch();

            if ($wholesaler && !empty($wholesaler->password) && password_verify($password, $wholesaler->password)) {
                $_SESSION['ec_user_id'] = $wholesaler->id;
                $_SESSION['ec_role'] = 'wholesaler';
                $_SESSION['ec_name'] = $wholesaler->name;
                $_SESSION['ec_customer_id'] = $wholesaler->id;
                header('Location: index.php?p=shop');
                exit;
            }

            // Check retail customer table
            $stmt = $db->prepare("SELECT * FROM ecommerce_retail_customers WHERE username = :login OR email = :login LIMIT 1");
            $stmt->execute([':login' => $loginInput]);
            $retail = $stmt->fetch();

            if ($retail && password_verify($password, $retail->password)) {
                $_SESSION['ec_user_id'] = $retail->id;
                $_SESSION['ec_role'] = 'retail';
                $_SESSION['ec_name'] = $retail->name;
                header('Location: index.php?p=shop');
                exit;
            }

            $error = 'Invalid credentials. Please try again.';
        }
    }

    // 2. REGISTER RETAIL
    if ($action === 'register_retail') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $phone = trim($_POST['phone'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $address = trim($_POST['address'] ?? '');

        if (empty($name) || empty($email) || empty($username) || empty($password)) {
            $error = 'All primary fields (Name, Email, Username, Password) are required.';
        } else {
            try {
                $hashed = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $db->prepare("INSERT INTO ecommerce_retail_customers (name, email, username, password, phone, address, city) 
                                      VALUES (:name, :email, :username, :pass, :phone, :address, :city)");
                $stmt->execute([
                    ':name' => $name,
                    ':email' => $email,
                    ':username' => $username,
                    ':pass' => $hashed,
                    ':phone' => $phone,
                    ':address' => $address,
                    ':city' => $city
                ]);
                
                $_SESSION['ec_user_id'] = $db->lastInsertId();
                $_SESSION['ec_role'] = 'retail';
                $_SESSION['ec_name'] = $name;
                header('Location: index.php?p=shop');
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

    // 3. WHOLESALER REQUEST
    if ($action === 'submit_wholesaler_request') {
        $business_name = trim($_POST['business_name'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $contact_number = trim($_POST['contact_number'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $email_address = trim($_POST['email_address'] ?? '');
        $req_username = trim($_POST['username'] ?? '');
        $req_password = $_POST['password'] ?? '';
        $notes = trim($_POST['notes'] ?? '');

        if (empty($business_name) || empty($email_address) || empty($req_username) || empty($req_password)) {
            $error = 'Business Name, Email Address, Username, and Password are required.';
        } else {
            try {
                $check = $db->prepare("SELECT id FROM wholesaler_requests WHERE email_address = :email");
                $check->execute([':email' => $email_address]);
                if ($check->fetch()) {
                    $error = 'A wholesaler registration request with this email already exists.';
                } else {
                    $stmt = $db->prepare("INSERT INTO wholesaler_requests (business_name, address, contact_number, city, email_address, username, password, notes, status) 
                                          VALUES (:bname, :address, :phone, :city, :email, :username, :pass, :notes, 'pending')");
                    $stmt->execute([
                        ':bname' => $business_name,
                        ':address' => $address,
                        ':phone' => $contact_number,
                        ':city' => $city,
                        ':email' => $email_address,
                        ':username' => $req_username,
                        ':pass' => $req_password,
                        ':notes' => $notes
                    ]);
                    $message = 'Your wholesaler registration request has been submitted successfully!';
                    $page = 'request_success';
                }
            } catch (PDOException $e) {
                $error = 'Failed to submit request: ' . $e->getMessage();
            }
        }
    }

    // 4. ADD TO CART
    if ($action === 'add_to_cart') {
        $itemId = intval($_POST['item_id'] ?? 0);
        $qty = intval($_POST['qty'] ?? 1);
        
        $stmt = $db->prepare("SELECT * FROM items WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $itemId]);
        $item = $stmt->fetch();

        if ($item) {
            $price = getItemPrice($item, $userRole);
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
            header('Location: index.php?p=cart');
            exit;
        }
    }

    // 5. UPDATE CART
    if ($action === 'update_cart') {
        foreach ($_POST['qty'] as $cartKey => $newQty) {
            $newQty = intval($newQty);
            if ($newQty <= 0) {
                unset($_SESSION['ec_cart'][$cartKey]);
            } else {
                $_SESSION['ec_cart'][$cartKey]['qty'] = $newQty;
            }
        }
        header('Location: index.php?p=cart');
        exit;
    }

    // 6. DELETE FROM CART
    if ($action === 'delete_cart_item') {
        $cartKey = $_POST['cart_key'] ?? '';
        if (isset($_SESSION['ec_cart'][$cartKey])) {
            unset($_SESSION['ec_cart'][$cartKey]);
        }
        header('Location: index.php?p=cart');
        exit;
    }

    // 7. CHECKOUT ORDER SUBMIT
    if ($action === 'submit_order') {
        if (empty($_SESSION['ec_cart'])) {
            $error = 'Your cart is empty.';
        } else {
            try {
                $db->beginTransaction();

                $subtotal = 0;
                foreach($_SESSION['ec_cart'] as $item) {
                    $subtotal += $item['price'] * $item['qty'];
                }
                
                $discount = 0;
                $couponCode = trim($_POST['coupon_code'] ?? '');
                if (!empty($couponCode)) {
                    $cStmt = $db->prepare("SELECT * FROM ecommerce_coupons WHERE code = :code AND is_active = 1 AND (expiry_date IS NULL OR expiry_date >= CURRENT_DATE) LIMIT 1");
                    $cStmt->execute([':code' => $couponCode]);
                    $coupon = $cStmt->fetch();
                    if ($coupon && $subtotal >= $coupon->min_spend) {
                        if ($coupon->type === 'percent') {
                            $discount = ($subtotal * ($coupon->value / 100));
                        } else {
                            $discount = floatval($coupon->value);
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

                if ($userRole === 'wholesaler') {
                    $customerId = $_SESSION['ec_customer_id'];
                    $customerName = $_SESSION['ec_name'];
                    $cStmt = $db->prepare("SELECT phone FROM customers WHERE id = :id LIMIT 1");
                    $cStmt->execute([':id' => $customerId]);
                    $cRow = $cStmt->fetch();
                    $customerPhone = $cRow->phone ?? '';
                } else {
                    $cStmt = $db->query("SELECT id FROM customers WHERE name = 'E-Commerce Retail Customer' LIMIT 1");
                    $cRow = $cStmt->fetch();
                    if ($cRow) {
                        $customerId = $cRow->id;
                    } else {
                        $ins = $db->prepare("INSERT INTO customers (name, email, phone, address, territory) VALUES ('E-Commerce Retail Customer', 'ecommerce@retail.com', '0000000000', 'Online Storefront', 'E-Commerce')");
                        $ins->execute();
                        $customerId = $db->lastInsertId();
                    }
                    $customerName = $_POST['billing_name'] ?? 'E-Commerce Customer';
                    $customerPhone = $_POST['billing_phone'] ?? '';
                }

                $shippingAddress = $_POST['shipping_address'] ?? 'Not specified';
                $notes = 'Order placed online via E-Commerce portal. Shipping to: ' . $shippingAddress;
                if (!empty($couponCode)) {
                    $notes .= ' | Coupon: ' . $couponCode;
                }

                $oStmt = $db->prepare("INSERT INTO sales_orders (order_number, customer_id, customer_name, customer_phone, billing_type, subtotal, discount, grand_total, order_date, due_date, status, notes) 
                                      VALUES (:order_num, :cid, :cname, :cphone, :btype, :sub, :disc, :grand, :odate, :ddate, 'Pending', :notes)");
                $oStmt->execute([
                    ':order_num' => $orderNo,
                    ':cid' => $customerId,
                    ':cname' => $customerName,
                    ':cphone' => $customerPhone,
                    ':btype' => ($userRole === 'wholesaler' ? 'wholesale' : 'retail'),
                    ':sub' => $subtotal,
                    ':disc' => $discount,
                    ':grand' => $grandTotal,
                    ':odate' => $orderDate,
                    ':ddate' => $dueDate,
                    ':notes' => $notes
                ]);

                $orderId = $db->lastInsertId();
                $itemStmt = $db->prepare("INSERT INTO sales_order_items (sales_order_id, item_id, sku, name, billing_price, qty, total) 
                                          VALUES (:oid, :item_id, :sku, :name, :price, :qty, :total)");

                foreach ($_SESSION['ec_cart'] as $cartItem) {
                    $totalPrice = $cartItem['price'] * $cartItem['qty'];
                    $itemStmt->execute([
                        ':oid' => $orderId,
                        ':item_id' => $cartItem['item_id'],
                        ':sku' => $cartItem['sku'],
                        ':name' => $cartItem['name'],
                        ':price' => $cartItem['price'],
                        ':qty' => $cartItem['qty'],
                        ':total' => $totalPrice
                    ]);

                    // Subtract stock levels
                    $uStmt = $db->prepare("UPDATE items SET qty = qty - :qty WHERE id = :id");
                    $uStmt->execute([':qty' => $cartItem['qty'], ':id' => $cartItem['item_id']]);
                }

                $db->commit();
                $_SESSION['ec_cart'] = []; // Clear Cart
                $message = "Order placed successfully! Reference Code: <strong>{$orderNo}</strong>";
                $page = 'order_success';
            } catch (Exception $e) {
                $db->rollBack();
                $error = 'Failed to submit order: ' . $e->getMessage();
            }
        }
    }

    // 8. SUBMIT REVIEW
    if ($action === 'submit_review') {
        $itemId = intval($_POST['item_id']);
        $reviewerName = trim($_POST['reviewer_name'] ?? 'Anonymous Buyer');
        $reviewerEmail = trim($_POST['reviewer_email'] ?? '');
        $rating = intval($_POST['rating'] ?? 5);
        $comment = trim($_POST['comment'] ?? '');

        if ($itemId > 0 && !empty($reviewerEmail) && !empty($comment)) {
            $stmt = $db->prepare("INSERT INTO ecommerce_reviews (item_id, customer_name, customer_email, rating, review_text, status) 
                                  VALUES (:item_id, :cname, :cemail, :rating, :comment, 'pending')");
            $stmt->execute([
                ':item_id' => $itemId,
                ':cname' => $reviewerName,
                ':cemail' => $reviewerEmail,
                ':rating' => $rating,
                ':comment' => $comment
            ]);
            $message = 'Review submitted successfully. It will display online once verified.';
        }
    }

    // 9. WISHLIST ACTION
    if ($action === 'wishlist_action') {
        if (!$isLoggedIn) {
            header('Location: index.php?p=login');
            exit;
        }
        $itemId = intval($_POST['item_id'] ?? 0);
        $act = $_POST['act'] ?? 'add';
        
        if ($itemId > 0) {
            if ($act === 'add') {
                $stmt = $db->prepare("INSERT IGNORE INTO ecommerce_wishlist (customer_id, customer_type, item_id) VALUES (:cid, :ctype, :item_id)");
                $stmt->execute([
                    ':cid' => $_SESSION['ec_user_id'],
                    ':ctype' => $_SESSION['ec_role'],
                    ':item_id' => $itemId
                ]);
            } elseif ($act === 'delete') {
                $stmt = $db->prepare("DELETE FROM ecommerce_wishlist WHERE customer_id = :cid AND customer_type = :ctype AND item_id = :item_id");
                $stmt->execute([
                    ':cid' => $_SESSION['ec_user_id'],
                    ':ctype' => $_SESSION['ec_role'],
                    ':item_id' => $itemId
                ]);
            }
        }
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }

    // 10. SUBMIT RETURN
    if ($action === 'request_return') {
        if (!$isLoggedIn) {
            header('Location: index.php?p=login');
            exit;
        }
        $orderId = intval($_POST['sales_order_id'] ?? 0);
        $reason = trim($_POST['reason'] ?? '');
        $details = trim($_POST['details'] ?? '');

        if ($orderId > 0 && !empty($reason)) {
            $stmt = $db->prepare("INSERT INTO ecommerce_returns (sales_order_id, reason, details, status) VALUES (:oid, :reason, :details, 'pending')");
            $stmt->execute([
                ':oid' => $orderId,
                ':reason' => $reason,
                ':details' => $details
            ]);
            $message = 'RMA authorization request submitted successfully.';
            $page = 'portal-returns';
        }
    }

    // 11. UPDATE PROFILE
    if ($action === 'update_profile') {
        if (!$isLoggedIn) {
            header('Location: index.php?p=login');
            exit;
        }
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!empty($name) && !empty($email) && !empty($username)) {
            try {
                if ($_SESSION['ec_role'] === 'retail') {
                    $sql = "UPDATE ecommerce_retail_customers SET name = :name, email = :email, username = :uname, phone = :phone, address = :addr, city = :city";
                    $params = [
                        ':name' => $name,
                        ':email' => $email,
                        ':uname' => $username,
                        ':phone' => $phone,
                        ':addr' => $address,
                        ':city' => $city,
                        ':id' => $_SESSION['ec_user_id']
                    ];
                    if (!empty($password)) {
                        $sql .= ", password = :pass";
                        $params[':pass'] = password_hash($password, PASSWORD_BCRYPT);
                    }
                    $sql .= " WHERE id = :id";
                    $stmt = $db->prepare($sql);
                    $stmt->execute($params);
                } else {
                    $sql = "UPDATE customers SET name = :name, email = :email, username = :uname, phone = :phone, address = :addr, territory = :city";
                    $params = [
                        ':name' => $name,
                        ':email' => $email,
                        ':uname' => $username,
                        ':phone' => $phone,
                        ':addr' => $address,
                        ':city' => $city,
                        ':id' => $_SESSION['ec_user_id']
                    ];
                    if (!empty($password)) {
                        $sql .= ", password = :pass";
                        $params[':pass'] = password_hash($password, PASSWORD_BCRYPT);
                    }
                    $sql .= " WHERE id = :id";
                    $stmt = $db->prepare($sql);
                    $stmt->execute($params);
                }
                $_SESSION['ec_name'] = $name;
                $message = 'Profile specifications updated successfully.';
            } catch (Exception $e) {
                $error = 'Failed to update settings: ' . $e->getMessage();
            }
        }
    }
}

// Fetch categories
$categories = $db->query("SELECT * FROM item_categories ORDER BY name ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($settings['meta_title'] ?? 'Curtiss Stationery Store') ?></title>
    <meta name="description" content="<?= htmlspecialchars($settings['meta_description'] ?? 'Premium corporate and wholesale stationery supplies.') ?>">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        :root {
            --primary: #0066cc;
            --primary-hover: #005bb5;
            --bg-site: #f4f5f8;
            --card-bg: #ffffff;
            --card-border: rgba(0,0,0,0.06);
            --card-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
            --text-main: #1e1e2d;
            --text-muted: #6c7a89;
            --text-accent: #0066cc;
            --mega-divider: rgba(0, 0, 0, 0.06);
            --rounded: 12px;
            --glass-blur: 16px;
        }

        [data-theme="dark"] {
            --bg-site: #0f0f12;
            --card-bg: #1a1a20;
            --card-border: rgba(255,255,255,0.06);
            --card-shadow: 0 4px 20px rgba(0, 0, 0, 0.25);
            --text-main: #f3f4f6;
            --text-muted: #9ca3af;
            --text-accent: #3b82f6;
            --mega-divider: rgba(255, 255, 255, 0.08);
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg-site);
            color: var(--text-main);
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            transition: background 0.3s, color 0.3s;
        }

        header {
            background-color: var(--card-bg);
            border-bottom: 1px solid var(--card-border);
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: var(--card-shadow);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            width: 100%;
            box-sizing: border-box;
        }

        .navbar {
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .brand-logo {
            font-size: 22px;
            font-weight: 800;
            color: var(--primary);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            letter-spacing: -0.5px;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .nav-links a {
            color: var(--text-main);
            text-decoration: none;
            font-weight: 500;
            font-size: 14.5px;
            transition: color 0.2s;
        }

        .nav-links a:hover, .nav-links a.active {
            color: var(--primary);
        }

        .theme-toggle {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 20px;
            color: var(--text-main);
            padding: 5px;
            display: flex;
            align-items: center;
        }

        .cart-btn {
            position: relative;
            display: flex;
            align-items: center;
            padding: 8px 16px;
            background: rgba(0, 102, 204, 0.08);
            border-radius: 20px;
            color: var(--primary);
            font-weight: 600;
            font-size: 13.5px;
            text-decoration: none;
            gap: 6px;
        }

        .cart-badge {
            background: #ff3b30;
            color: #fff;
            font-size: 10px;
            font-weight: bold;
            border-radius: 10px;
            padding: 2px 6px;
        }

        .hero {
            padding: 70px 20px;
            background: linear-gradient(135deg, rgba(0, 102, 204, 0.05) 0%, rgba(138, 43, 226, 0.05) 100%);
            border: 1px solid var(--card-border);
            text-align: center;
            margin-bottom: 35px;
            border-radius: var(--rounded);
        }
        .hero h1 {
            font-size: 42px;
            font-weight: 800;
            margin: 0 0 12px 0;
            letter-spacing: -1px;
            background: linear-gradient(to right, var(--primary), #8a2be2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .hero p {
            font-size: 16px;
            color: var(--text-muted);
            max-width: 600px;
            margin: 0 auto 25px auto;
        }

        .card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: var(--rounded);
            padding: 24px;
            box-shadow: var(--card-shadow);
        }

        .btn-primary, .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: var(--primary);
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
            font-size: 14.5px;
        }
        .btn-primary:hover, .btn:hover {
            background: var(--primary-hover);
        }

        .btn-secondary {
            background: transparent;
            border: 1px solid var(--card-border);
            color: var(--text-main);
            padding: 9px 18px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            cursor: pointer;
        }
        .btn-secondary:hover {
            background: rgba(0,0,0,0.02);
        }

        /* Forms */
        .form-box, .form-group {
            margin-bottom: 18px;
        }
        .form-box label, .form-group label {
            display: block;
            margin-bottom: 6px;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-muted);
        }
        .form-control {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid var(--card-border);
            border-radius: 8px;
            background: rgba(255,255,255,0.05);
            color: var(--text-main);
            box-sizing: border-box;
            font-size: 14px;
            font-family: inherit;
        }
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
        }

        /* Sidebar catalog layouts */
        .browse-layout {
            display: grid;
            grid-template-columns: 260px 1fr;
            gap: 30px;
            margin-top: 15px;
        }
        @media (max-width: 768px) {
            .browse-layout { grid-template-columns: 1fr; }
        }

        .filter-sidebar {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .category-filter-list {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .category-filter-link {
            color: var(--text-main);
            text-decoration: none;
            font-size: 13.5px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .category-filter-link.active, .category-filter-link:hover {
            color: var(--primary);
            font-weight: 600;
        }

        .catalog-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
            gap: 20px;
        }

        .prod-showcase-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: var(--rounded);
            overflow: hidden;
            box-shadow: var(--card-shadow);
            text-decoration: none;
            color: inherit;
            display: flex;
            flex-direction: column;
            transition: transform 0.25s, box-shadow 0.25s;
        }
        .prod-showcase-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.08);
        }

        .prod-image-wrapper {
            height: 180px;
            background: rgba(0,0,0,0.01);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }
        .prod-image-wrapper img {
            max-width: 90%;
            max-height: 90%;
            object-fit: contain;
        }

        .prod-info-box {
            padding: 16px;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }
        .prod-name {
            font-size: 14.5px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        .prod-sku {
            font-size: 11px;
            color: var(--text-muted);
            margin-bottom: 12px;
        }
        .prod-price-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: auto;
        }
        .prod-price {
            font-size: 16px;
            font-weight: 800;
            color: var(--text-accent);
        }

        .pill-badge {
            font-size: 9.5px;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 20px;
            text-transform: uppercase;
        }
        .pill-success { background: rgba(52,199,89,0.1); color: #34c759; }
        .pill-warning { background: rgba(255,149,0,0.1); color: #ff9500; }
        .pill-danger { background: rgba(255,59,48,0.1); color: #ff3b30; }

        .alert-box {
            padding: 12px 18px;
            border-radius: 8px;
            font-size: 13.5px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Portal Layouts */
        .portal-layout {
            display: grid;
            grid-template-columns: 240px 1fr;
            gap: 30px;
            margin-top: 15px;
        }
        @media (max-width: 768px) {
            .portal-layout { grid-template-columns: 1fr; }
        }

        .portal-menu-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: var(--rounded);
            padding: 20px;
            height: fit-content;
        }
        .portal-menu-list {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .portal-menu-link {
            color: var(--text-main);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            padding: 10px 12px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.2s;
        }
        .portal-menu-link:hover, .portal-menu-link.active {
            background: rgba(0, 102, 204, 0.08);
            color: var(--primary);
            font-weight: 600;
        }

        .portal-stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }
        @media (max-width: 576px) {
            .portal-stats-grid { grid-template-columns: 1fr; }
        }

        .stat-counter-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: var(--rounded);
            padding: 18px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .stat-icon {
            width: 44px;
            height: 44px;
            border-radius: 8px;
            background: rgba(0,102,204,0.06);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
        }

        footer {
            margin-top: auto;
            background-color: var(--card-bg);
            border-top: 1px solid var(--card-border);
            padding: 25px 0;
            text-align: center;
            color: var(--text-muted);
            font-size: 13.5px;
        }
    </style>
</head>
<body>

    <header>
        <div class="container navbar">
            <a href="index.php" class="brand-logo">
                <i class="ph-bold ph-shopping-bag-open"></i> <?= htmlspecialchars($settings['store_name'] ?? 'CURTISS STORE') ?>
            </a>
            
            <div class="nav-links">
                <a href="index.php?p=home" class="<?= $page === 'home' ? 'active' : '' ?>">Home</a>
                <a href="index.php?p=shop" class="<?= strpos($page, 'shop') === 0 || $page === 'product' ? 'active' : '' ?>">Shop</a>
                
                <?php if ($isLoggedIn): ?>
                    <a href="index.php?p=portal" class="<?= strpos($page, 'portal') === 0 ? 'active' : '' ?>"><i class="ph ph-user"></i> Portal</a>
                    <a href="index.php?logout=1" style="color:#ff3b30;"><i class="ph ph-sign-out"></i></a>
                <?php else: ?>
                    <a href="index.php?p=login" class="btn" style="padding: 6px 14px; font-size: 13px;">Login / Register</a>
                <?php endif; ?>

                <a href="index.php?p=cart" class="cart-btn">
                    <i class="ph ph-shopping-cart"></i> Cart
                    <span class="cart-badge"><?= count($_SESSION['ec_cart']) ?></span>
                </a>

                <button class="theme-toggle" onclick="toggleTheme()">
                    <i class="ph ph-sun" id="theme-icon"></i>
                </button>
            </div>
        </div>
    </header>

    <main class="container" style="flex-grow: 1; padding-top: 30px; padding-bottom: 50px;">
        
        <?php if (!empty($error)): ?>
            <div class="alert-box pill-danger">
                <i class="ph ph-warning-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($message)): ?>
            <div class="alert-box pill-success">
                <i class="ph ph-check-circle"></i> <?= $message ?>
            </div>
        <?php endif; ?>

        <?php
        // ------------------ PAGE: HOME ------------------
        if ($page === 'home'):
            // Fetch layout sections
            $banners = $db->query("SELECT * FROM ecommerce_banners WHERE is_active = 1 ORDER BY id DESC LIMIT 1")->fetchAll();
            $featuredCats = $db->query("SELECT * FROM item_categories LIMIT 4")->fetchAll();
            $featuredItems = $db->query("SELECT * FROM items WHERE status = 'active' LIMIT 8")->fetchAll();
        ?>
            <!-- Hero banner -->
            <?php if(!empty($banners)): ?>
                <div class="hero" style="background-image: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), url('<?= getBannerImageUrl($banners[0]->image_path) ?>'); background-size: cover; color:#fff; min-height: 280px; display:flex; flex-direction:column; justify-content:center; align-items:center;">
                    <h1 style="color:#fff;"><?= htmlspecialchars($banners[0]->title) ?></h1>
                    <p style="color:#eee;"><?= htmlspecialchars($banners[0]->subtitle) ?></p>
                    <a href="index.php?p=shop" class="btn">Explore Store Collection</a>
                </div>
            <?php else: ?>
                <div class="hero">
                    <h1>Stationery & Office Enterprise Catalog</h1>
                    <p>Access top tier office instrumentation, paper products, and writing tools. Log in to claim corporate wholesale rates.</p>
                    <div style="display:flex; justify-content:center; gap:15px;">
                        <a href="index.php?p=shop" class="btn">Browse Shop catalog</a>
                        <?php if(!$isLoggedIn): ?>
                            <a href="index.php?p=login" class="btn btn-secondary">Create B2B Account</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Categories -->
            <div style="margin-bottom:50px;">
                <h2 style="font-weight:700; margin-bottom:20px;">Featured Departments</h2>
                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                    <?php foreach($featuredCats as $cat): ?>
                        <a href="index.php?p=shop&category=<?= htmlspecialchars($cat->seo_url) ?>" style="text-decoration:none; color:inherit;">
                            <div class="card" style="text-align:center; transition:transform 0.2s;" onmouseover="this.style.transform='translateY(-4px)'" onmouseout="this.style.transform='none'">
                                <div style="font-size:32px; color:var(--primary); margin-bottom:10px;">
                                    <i class="<?= !empty($cat->icon) ? htmlspecialchars($cat->icon) : 'ph ph-package' ?>"></i>
                                </div>
                                <h4 style="margin:0; font-size:16px; font-weight:600;"><?= htmlspecialchars($cat->name) ?></h4>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Products -->
            <div style="margin-bottom:50px;">
                <h2 style="font-weight:700; margin-bottom:20px;">Featured Hardware Products</h2>
                <div class="catalog-grid">
                    <?php foreach($featuredItems as $prod): 
                        $price = getItemPrice($prod, $userRole);
                    ?>
                        <a href="index.php?p=product&id=<?= $prod->id ?>" class="prod-showcase-card">
                            <div class="prod-image-wrapper">
                                <?php if(!empty($prod->image_path)): ?>
                                    <img src="<?= getProductImageUrl($prod->image_path) ?>" alt="Item preview">
                                <?php else: ?>
                                    <i class="ph ph-image" style="font-size:36px; color:#ccc;"></i>
                                <?php endif; ?>
                            </div>
                            <div class="prod-info-box">
                                <span class="prod-name"><?= htmlspecialchars($prod->name) ?></span>
                                <span class="prod-sku">SKU: <?= htmlspecialchars($prod->item_code) ?></span>
                                <div class="prod-price-row">
                                    <span class="prod-price"><?= htmlspecialchars($settings['store_currency'] ?? 'Rs.') ?> <?= number_format($price, 2) ?></span>
                                    <span class="pill-badge <?= $prod->qty > 0 ? 'pill-success' : 'pill-danger' ?>">
                                        <?= $prod->qty > 0 ? 'In Stock' : 'Out of Stock' ?>
                                    </span>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

        <?php
        // ------------------ PAGE: SHOP ------------------
        elseif ($page === 'shop'):
            $catFilter = $_GET['category'] ?? null;
            $search = $_GET['q'] ?? '';
            $minPrice = $_GET['min_price'] ?? '';
            $maxPrice = $_GET['max_price'] ?? '';
            $sort = $_GET['sort'] ?? 'newest';

            // Query construction
            $sql = "SELECT i.*, c.name as category_name, c.seo_url as category_seo 
                    FROM items i 
                    LEFT JOIN item_categories c ON i.category_id = c.id 
                    WHERE i.status = 'active'";
            $params = [];

            if ($catFilter) {
                $sql .= " AND c.seo_url = :cat";
                $params[':cat'] = $catFilter;
            }
            if (!empty($search)) {
                $sql .= " AND (i.name LIKE :search OR i.item_code LIKE :search)";
                $params[':search'] = '%' . $search . '%';
            }
            if ($minPrice !== '') {
                $sql .= " AND i.price >= :min";
                $params[':min'] = floatval($minPrice);
            }
            if ($maxPrice !== '') {
                $sql .= " AND i.price <= :max";
                $params[':max'] = floatval($maxPrice);
            }

            if ($sort === 'price_asc') {
                $sql .= " ORDER BY i.price ASC";
            } elseif ($sort === 'price_desc') {
                $sql .= " ORDER BY i.price DESC";
            } else {
                $sql .= " ORDER BY i.id DESC";
            }

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $products = $stmt->fetchAll();
        ?>
            <div class="browse-layout">
                <!-- Filters -->
                <div class="filter-sidebar">
                    <div class="card">
                        <h4 style="margin-top:0; font-size:13px; text-transform:uppercase; color:var(--text-muted); border-bottom:1px solid var(--mega-divider); padding-bottom:8px; margin-bottom:15px;">Departments</h4>
                        <ul class="category-filter-list">
                            <li>
                                <a href="index.php?p=shop" class="category-filter-link <?= !$catFilter ? 'active' : '' ?>">
                                    <i class="ph ph-package"></i> All Products
                                </a>
                            </li>
                            <?php foreach($categories as $cat): ?>
                                <li>
                                    <a href="index.php?p=shop&category=<?= htmlspecialchars($cat->seo_url) ?>" class="category-filter-link <?= $catFilter == $cat->seo_url ? 'active' : '' ?>">
                                        <i class="<?= !empty($cat->icon) ? htmlspecialchars($cat->icon) : 'ph ph-folder' ?>"></i>
                                        <?= htmlspecialchars($cat->name) ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <div class="card">
                        <h4 style="margin-top:0; font-size:13px; text-transform:uppercase; color:var(--text-muted); border-bottom:1px solid var(--mega-divider); padding-bottom:8px; margin-bottom:15px;">Search Filters</h4>
                        <form action="index.php" method="GET">
                            <input type="hidden" name="p" value="shop">
                            <?php if($catFilter): ?>
                                <input type="hidden" name="category" value="<?= htmlspecialchars($catFilter) ?>">
                            <?php endif; ?>

                            <div class="form-box">
                                <label>Keyword Search</label>
                                <input type="text" name="q" class="form-control" placeholder="Search product..." value="<?= htmlspecialchars($search) ?>">
                            </div>

                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                                <div class="form-box">
                                    <label>Min Price</label>
                                    <input type="number" step="0.01" name="min_price" class="form-control" placeholder="Min" value="<?= htmlspecialchars($minPrice) ?>">
                                </div>
                                <div class="form-box">
                                    <label>Max Price</label>
                                    <input type="number" step="0.01" name="max_price" class="form-control" placeholder="Max" value="<?= htmlspecialchars($maxPrice) ?>">
                                </div>
                            </div>

                            <div class="form-box">
                                <label>Sort By</label>
                                <select name="sort" class="form-control">
                                    <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest arrivals</option>
                                    <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price: Low to High</option>
                                    <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price: High to Low</option>
                                </select>
                            </div>

                            <button type="submit" class="btn" style="width:100%;">Apply filters</button>
                        </form>
                    </div>
                </div>

                <!-- Product items grid -->
                <div>
                    <div style="display:flex; justify-content:space-between; align-items:center; background:var(--card-bg); border:1px solid var(--card-border); padding: 15px 20px; border-radius:var(--rounded); margin-bottom: 20px; box-shadow:var(--card-shadow);">
                        <span style="font-weight:600; color:var(--text-muted);">Products cataloged: <?= count($products) ?> items</span>
                        <span class="pill-badge pill-success" style="font-size:8.5px;"><?= $userRole === 'wholesaler' ? 'Wholesale price list active' : 'Standard retail buyer' ?></span>
                    </div>

                    <div class="catalog-grid">
                        <?php if(empty($products)): ?>
                            <div class="card" style="grid-column:1/-1; text-align:center; padding: 50px 20px; color:var(--text-muted);">
                                <i class="ph ph-magnifying-glass" style="font-size:42px; margin-bottom:10px;"></i>
                                <p>No matching items cataloged in this department.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach($products as $prod): 
                                $price = getItemPrice($prod, $userRole);
                            ?>
                                <a href="index.php?p=product&id=<?= $prod->id ?>" class="prod-showcase-card">
                                    <div class="prod-image-wrapper">
                                        <?php if(!empty($prod->image_path)): ?>
                                            <img src="<?= getProductImageUrl($prod->image_path) ?>" alt="Item preview">
                                        <?php else: ?>
                                            <i class="ph ph-image" style="font-size:36px; color:#ccc;"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="prod-info-box">
                                        <span class="prod-name"><?= htmlspecialchars($prod->name) ?></span>
                                        <span class="prod-sku">SKU: <?= htmlspecialchars($prod->item_code) ?></span>
                                        <div class="prod-price-row">
                                            <span class="prod-price"><?= htmlspecialchars($settings['store_currency'] ?? 'Rs.') ?> <?= number_format($price, 2) ?></span>
                                            <span class="pill-badge <?= $prod->qty > 0 ? 'pill-success' : 'pill-danger' ?>">
                                                <?= $prod->qty > 0 ? 'In stock' : 'Out of stock' ?>
                                            </span>
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        <?php
        // ------------------ PAGE: PRODUCT DETAILS ------------------
        elseif ($page === 'product'):
            $prodId = intval($_GET['id'] ?? 0);
            $stmt = $db->prepare("SELECT i.*, c.name as category_name FROM items i LEFT JOIN item_categories c ON i.category_id = c.id WHERE i.id = :id LIMIT 1");
            $stmt->execute([':id' => $prodId]);
            $product = $stmt->fetch();

            if (!$product) {
                echo "<p style='padding:50px; text-align:center;'>Product not found.</p>";
            } else {
                $price = getItemPrice($product, $userRole);

                // Fetch approved reviews
                $rStmt = $db->prepare("SELECT * FROM ecommerce_reviews WHERE item_id = :id AND status = 'approved' ORDER BY id DESC");
                $rStmt->execute([':id' => $product->id]);
                $reviews = $rStmt->fetchAll();

                // Fetch wishlist state
                $wishlisted = false;
                if ($isLoggedIn) {
                    $wStmt = $db->prepare("SELECT id FROM ecommerce_wishlist WHERE customer_id = :cid AND customer_type = :ctype AND item_id = :item_id LIMIT 1");
                    $wStmt->execute([':cid' => $_SESSION['ec_user_id'], ':ctype' => $_SESSION['ec_role'], ':item_id' => $product->id]);
                    $wishlisted = (bool)$wStmt->fetch();
                }

                // Related items
                $relStmt = $db->prepare("SELECT * FROM items WHERE category_id = :cat AND id != :id AND status='active' LIMIT 4");
                $relStmt->execute([':cat' => $product->category_id, ':id' => $product->id]);
                $related = $relStmt->fetchAll();
        ?>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:40px; margin-top:20px;">
                <!-- Left panel image -->
                <div class="card" style="display:flex; align-items:center; justify-content:center; height:400px; padding:0;">
                    <?php if(!empty($product->image_path)): ?>
                           <img src="<?= getProductImageUrl($product->image_path) ?>" alt="Product graph" style="max-height:90%; max-width:90%; object-fit:contain;">
                    <?php else: ?>
                        <i class="ph ph-image-square" style="font-size:64px; color:#ccc;"></i>
                    <?php endif; ?>
                </div>

                <!-- Right details -->
                <div style="display:flex; flex-direction:column; gap:15px;">
                    <span style="font-size:11.5px; font-weight:700; color:var(--primary); text-transform:uppercase; letter-spacing:0.5px;"><?= htmlspecialchars($product->category_name ?? 'Stationery') ?></span>
                    <h1 style="font-size:32px; font-weight:800; margin:0; line-height:1.2;"><?= htmlspecialchars($product->name) ?></h1>
                    <span style="font-size:12px; color:var(--text-muted);">SKU Code: <code><?= htmlspecialchars($product->item_code) ?></code></span>

                    <div style="border-top:1px solid var(--mega-divider); border-bottom:1px solid var(--mega-divider); padding:15px 0;">
                        <span style="font-size:26px; font-weight:800; color:var(--text-accent);"><?= htmlspecialchars($settings['store_currency'] ?? 'Rs.') ?> <?= number_format($price, 2) ?></span>
                        <span style="display:block; font-size:11px; color:var(--text-muted); margin-top:4px;">Billing type: <?= $userRole === 'wholesaler' ? 'B2B Wholesale contract pricing' : 'Retail standard pricing' ?></span>
                    </div>

                    <div>
                        Stock Availability: 
                        <?php if($product->qty > 0): ?>
                            <span class="pill-badge pill-success">In Stock (<?= $product->qty ?> available)</span>
                        <?php else: ?>
                            <span class="pill-badge pill-danger">Out of Stock</span>
                        <?php endif; ?>
                    </div>

                    <p style="font-size:14.5px; line-height:1.6; color:var(--text-muted);"><?= nl2br(htmlspecialchars($product->description ?? 'No specifications registered.')) ?></p>

                    <!-- Add to cart -->
                    <?php if($product->qty > 0): ?>
                        <form action="index.php?p=product&id=<?= $product->id ?>" method="POST" style="margin-top: 10px;">
                            <input type="hidden" name="action" value="add_to_cart">
                            <input type="hidden" name="item_id" value="<?= $product->id ?>">
                            <div style="display:flex; gap:10px; align-items:flex-end;">
                                <div class="form-box" style="margin-bottom:0; width:90px;">
                                    <label>Quantity</label>
                                    <input type="number" name="qty" class="form-control" value="1" min="1" max="<?= $product->qty ?>">
                                </div>
                                <button type="submit" class="btn" style="height:44px; flex-grow:1;"><i class="ph ph-shopping-cart-simple"></i> Add to Shopping Cart</button>
                            </div>
                        </form>
                    <?php endif; ?>

                    <!-- Wishlist toggle button -->
                    <?php if($isLoggedIn): ?>
                        <form action="index.php?p=product&id=<?= $product->id ?>" method="POST">
                            <input type="hidden" name="action" value="wishlist_action">
                            <input type="hidden" name="item_id" value="<?= $product->id ?>">
                            <?php if($wishlisted): ?>
                                <input type="hidden" name="act" value="delete">
                                <button type="submit" class="btn-secondary" style="width:100%;"><i class="ph ph-heart-break" style="color:#ff3b30;"></i> Remove from Wishlist</button>
                            <?php else: ?>
                                <input type="hidden" name="act" value="add">
                                <button type="submit" class="btn-secondary" style="width:100%;"><i class="ph ph-heart"></i> Add to Wishlist</button>
                            <?php endif; ?>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Reviews and related items split -->
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:30px; margin-top:50px;">
                <!-- Review lists -->
                <div class="card">
                    <h3 style="margin-top:0; font-size:16px; border-bottom:1px solid var(--mega-divider); padding-bottom:10px; margin-bottom:15px;">Customer Reviews (<?= count($reviews) ?>)</h3>
                    <div style="display:flex; flex-direction:column; gap:15px;">
                        <?php if(empty($reviews)): ?>
                            <p style="color:var(--text-muted); font-size:13px; text-align:center;">No verified reviews posted yet.</p>
                        <?php else: ?>
                            <?php foreach($reviews as $rev): ?>
                                <div style="background:rgba(0,0,0,0.015); border:1px solid var(--card-border); padding:12px; border-radius:8px;">
                                    <div style="display:flex; justify-content:space-between; font-size:12.5px;">
                                        <strong><?= htmlspecialchars($rev->customer_name) ?></strong>
                                        <span style="color:var(--text-muted);"><?= date('M d, Y', strtotime($rev->created_at)) ?></span>
                                    </div>
                                    <div style="color:#ffcc00; font-size:11px; margin-top:2px;">
                                        <?php for($i=1; $i<=5; $i++): ?>
                                            <i class="ph-fill ph-star" style="color: <?= $i <= $rev->rating ? '#ffcc00' : 'rgba(0,0,0,0.08)' ?>;"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <p style="margin:5px 0 0 0; font-size:13px; font-style:italic;">"<?= htmlspecialchars($rev->review_text) ?>"</p>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Review submission -->
                <div class="card">
                    <h3 style="margin-top:0; font-size:16px; border-bottom:1px solid var(--mega-divider); padding-bottom:10px; margin-bottom:15px;">Post a Verified Review</h3>
                    <form action="index.php?p=product&id=<?= $product->id ?>" method="POST">
                        <input type="hidden" name="action" value="submit_review">
                        <input type="hidden" name="item_id" value="<?= $product->id ?>">

                        <div class="form-box">
                            <label>Display name</label>
                            <input type="text" name="reviewer_name" class="form-control" required placeholder="e.g. John Doe" value="<?= htmlspecialchars($userName) ?>">
                        </div>
                        <div class="form-box">
                            <label>Email Address</label>
                            <input type="email" name="reviewer_email" class="form-control" required placeholder="e.g. john@example.com">
                        </div>
                        <div class="form-box">
                            <label>Rating star evaluation</label>
                            <select name="rating" class="form-control">
                                <option value="5">⭐⭐⭐⭐⭐ (5 - Excellent)</option>
                                <option value="4">⭐⭐⭐⭐ (4 - Very Good)</option>
                                <option value="3">⭐⭐⭐ (3 - Average)</option>
                                <option value="2">⭐⭐ (2 - Poor)</option>
                                <option value="1">⭐ (1 - Unacceptable)</option>
                            </select>
                        </div>
                        <div class="form-box">
                            <label>Commentary</label>
                            <textarea name="comment" class="form-control" rows="3" required placeholder="Describe item quality, packaging..."></textarea>
                        </div>
                        <button type="submit" class="btn" style="width:100%;">Submit Moderated Review</button>
                    </form>
                </div>
            </div>

            <!-- Related Showcase -->
            <?php if(!empty($related)): ?>
                <div style="margin-top:50px;">
                    <h3 style="margin-bottom:20px; border-bottom:1px solid var(--mega-divider); padding-bottom:10px;">Similar Items in Catalog</h3>
                    <div class="catalog-grid">
                        <?php foreach($related as $relItem): 
                            $relPrice = getItemPrice($relItem, $userRole);
                        ?>
                            <a href="index.php?p=product&id=<?= $relItem->id ?>" class="prod-showcase-card">
                                <div class="prod-image-wrapper">
                                    <?php if(!empty($relItem->image_path)): ?>
                                        <img src="<?= getProductImageUrl($relItem->image_path) ?>" alt="Item preview">
                                    <?php else: ?>
                                        <i class="ph ph-image" style="font-size:32px; color:#ccc;"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="prod-info-box">
                                    <span class="prod-name"><?= htmlspecialchars($relItem->name) ?></span>
                                    <div class="prod-price-row">
                                        <span class="prod-price"><?= htmlspecialchars($settings['store_currency'] ?? 'Rs.') ?> <?= number_format($relPrice, 2) ?></span>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php
            }

        // ------------------ PAGE: CART ------------------
        elseif ($page === 'cart'):
        ?>
            <h2 style="font-weight:700; margin-bottom:20px;">Shopping Cart</h2>
            
            <?php if(empty($_SESSION['ec_cart'])): ?>
                <div class="card" style="text-align:center; padding: 50px 20px;">
                    <i class="ph ph-shopping-cart" style="font-size:48px; color:var(--text-muted); margin-bottom:10px;"></i>
                    <p style="color:var(--text-muted);">Your cart is currently empty.</p>
                    <a href="index.php?p=shop" class="btn" style="margin-top:15px;">Continue catalog browsing</a>
                </div>
            <?php else: ?>
                <form action="index.php?p=cart" method="POST">
                    <input type="hidden" name="action" value="update_cart">
                    <div class="card" style="padding:0; overflow-x:auto; margin-bottom: 25px;">
                        <table style="width:100%; border-collapse:collapse; font-size:14px; text-align:left;">
                            <thead>
                                <tr style="border-bottom:1px solid var(--card-border); background:rgba(0,0,0,0.015); color:var(--text-muted);">
                                    <th style="padding:15px;">Product specs</th>
                                    <th style="padding:15px;">SKU code</th>
                                    <th style="padding:15px;">Unit Price</th>
                                    <th style="padding:15px; width:100px;">Qty</th>
                                    <th style="padding:15px; text-align:right; width:120px;">Total</th>
                                    <th style="padding:15px; text-align:right; width:60px;">Delete</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $subtotal = 0;
                                foreach($_SESSION['ec_cart'] as $k => $item): 
                                    $itemSub = $item['price'] * $item['qty'];
                                    $subtotal += $itemSub;
                                ?>
                                    <tr style="border-bottom:1px solid var(--mega-divider);">
                                        <td style="padding:15px;">
                                            <strong style="color:var(--text-main);"><?= htmlspecialchars($item['name']) ?></strong>
                                        </td>
                                        <td style="padding:15px; font-family:monospace;"><?= htmlspecialchars($item['sku']) ?></td>
                                        <td style="padding:15px;"><?= htmlspecialchars($settings['store_currency'] ?? 'Rs.') ?> <?= number_format($item['price'], 2) ?></td>
                                        <td style="padding:15px;">
                                            <input type="number" name="qty[<?= $k ?>]" value="<?= $item['qty'] ?>" min="1" class="form-control" style="text-align:center; padding:5px;">
                                        </td>
                                        <td style="padding:15px; text-align:right; font-weight:700;"><?= htmlspecialchars($settings['store_currency'] ?? 'Rs.') ?> <?= number_format($itemSub, 2) ?></td>
                                        <td style="padding:15px; text-align:right;">
                                            <button type="submit" class="btn-secondary" style="border-color:#ff3b30; color:#ff3b30; padding:6px 10px;" onclick="this.form.action.value='delete_cart_item'; document.getElementById('delKey').value='<?= $k ?>';">
                                                <i class="ph ph-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr style="background:rgba(0,0,0,0.01);">
                                    <td colspan="4" style="text-align:right; font-weight:700; padding:20px;">Estimated Subtotal:</td>
                                    <td style="text-align:right; font-weight:800; font-size:18px; color:var(--text-accent); padding:20px;"><?= htmlspecialchars($settings['store_currency'] ?? 'Rs.') ?> <?= number_format($subtotal, 2) ?></td>
                                    <td></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <input type="hidden" name="cart_key" id="delKey">

                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <button type="submit" class="btn-secondary"><i class="ph ph-arrows-clockwise"></i> Update Quantities</button>
                        <div style="display:flex; gap:10px;">
                            <a href="index.php?p=shop" class="btn-secondary">Browse Shop</a>
                            <a href="index.php?p=checkout" class="btn">Proceed to checkout <i class="ph ph-arrow-right"></i></a>
                        </div>
                    </div>
                </form>
            <?php endif; ?>

        <?php
        // ------------------ PAGE: CHECKOUT ------------------
        elseif ($page === 'checkout'):
            if(empty($_SESSION['ec_cart'])) {
                header('Location: index.php?p=cart');
                exit;
            }
            $subtotal = 0;
            foreach($_SESSION['ec_cart'] as $item) {
                $subtotal += $item['price'] * $item['qty'];
            }
        ?>
            <div style="display:grid; grid-template-columns:1fr 380px; gap:30px;">
                <div class="card">
                    <h3 style="margin-top:0; border-bottom:1px solid var(--mega-divider); padding-bottom:10px; margin-bottom:20px;">Recipient & Delivery Address</h3>
                    <form action="index.php?p=checkout" method="POST">
                        <input type="hidden" name="action" value="submit_order">

                        <?php if ($userRole === 'wholesaler'): ?>
                            <div class="alert-box pill-success" style="margin-bottom:20px;">
                                <i class="ph ph-check-circle"></i> Logged in B2B Customer: <strong><?= htmlspecialchars($userName) ?></strong>. Order will be posted to corporate ledger.
                            </div>
                        <?php else: ?>
                            <div class="form-box">
                                <label>Recipient name *</label>
                                <input type="text" name="billing_name" class="form-control" required placeholder="e.g. John Doe">
                            </div>
                            <div class="form-box">
                                <label>Recipient phone number *</label>
                                <input type="text" name="billing_phone" class="form-control" required placeholder="e.g. +94 77 123 4567">
                            </div>
                        <?php endif; ?>

                        <div class="form-box">
                            <label>Delivery/Dispatch Address *</label>
                            <textarea name="shipping_address" class="form-control" rows="4" required placeholder="Street address, city, postal code..."></textarea>
                        </div>

                        <div class="form-box">
                            <label>Coupon Code (Optional)</label>
                            <input type="text" name="coupon_code" class="form-control" placeholder="e.g. SAVE10">
                        </div>

                        <button type="submit" class="btn" style="width:100%; height:44px;"><i class="ph ph-check-square"></i> Finalize and Post Order</button>
                    </form>
                </div>

                <div class="card" style="height:fit-content;">
                    <h3 style="margin-top:0; border-bottom:1px solid var(--mega-divider); padding-bottom:10px; margin-bottom:15px;">Order Items</h3>
                    <div style="display:flex; flex-direction:column; gap:12px; margin-bottom:15px;">
                        <?php foreach($_SESSION['ec_cart'] as $item): ?>
                            <div style="display:flex; justify-content:space-between; font-size:13.5px;">
                                <span><strong><?= $item['qty'] ?>x</strong> <?= htmlspecialchars($item['name']) ?></span>
                                <span style="font-weight:600;"><?= htmlspecialchars($settings['store_currency'] ?? 'Rs.') ?> <?= number_format($item['price'] * $item['qty'], 2) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div style="border-top:1px dashed var(--card-border); padding-top:12px; display:flex; justify-content:space-between; font-size:18px; font-weight:800;">
                        <span>Grand Total:</span>
                        <span style="color:var(--text-accent);"><?= htmlspecialchars($settings['store_currency'] ?? 'Rs.') ?> <?= number_format($subtotal, 2) ?></span>
                    </div>
                </div>
            </div>

        // ------------------ PAGE: LOGIN / REGISTER ------------------
        elseif ($page === 'login'):
        ?>
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:30px; max-width:900px; margin: 0 auto;">
                <!-- Login form -->
                <div class="card">
                    <h3 style="margin-top:0; border-bottom:1px solid var(--mega-divider); padding-bottom:10px; margin-bottom:20px;">Access Account</h3>
                    <form action="index.php?p=login" method="POST">
                        <input type="hidden" name="action" value="login">
                        <div class="form-box">
                            <label>Username / Email *</label>
                            <input type="text" name="username_or_email" class="form-control" required placeholder="e.g. john_doe">
                        </div>
                        <div class="form-box">
                            <label>Password *</label>
                            <input type="password" name="password" class="form-control" required placeholder="••••••••">
                        </div>
                        <button type="submit" class="btn" style="width:100%; height:42px;">Sign In</button>
                    </form>
                </div>

                <!-- Registration options toggle -->
                <div class="card">
                    <h3 style="margin-top:0; border-bottom:1px solid var(--mega-divider); padding-bottom:10px; margin-bottom:15px;">Create E-Commerce Profile</h3>
                    <div style="display:flex; flex-direction:column; gap:12px;">
                        <a href="index.php?p=register-retail" class="btn-secondary" style="padding:15px;"><i class="ph ph-user-plus"></i> Standard Retail Sign Up</a>
                        <a href="index.php?p=wholesaler-request" class="btn" style="padding:15px; background:#8a2be2;"><i class="ph ph-briefcase"></i> Onboard Wholesaler Partnership</a>
                    </div>
                </div>
            </div>

        // ------------------ PAGE: REGISTER RETAIL ------------------
        elseif ($page === 'register-retail'):
        ?>
            <div class="card" style="max-width:550px; margin:0 auto;">
                <h3 style="margin-top:0; border-bottom:1px solid var(--mega-divider); padding-bottom:10px; margin-bottom:20px; text-align:center;">Register Retail Account</h3>
                <form action="index.php?p=register-retail" method="POST">
                    <input type="hidden" name="action" value="register_retail">
                    
                    <div class="form-box">
                        <label>Full Display Name *</label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g. Jane Doe">
                    </div>
                    <div class="form-box">
                        <label>Email Address *</label>
                        <input type="email" name="email" class="form-control" required placeholder="e.g. jane@example.com">
                    </div>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                        <div class="form-box">
                            <label>Choose Username *</label>
                            <input type="text" name="username" class="form-control" required placeholder="jane_retail">
                        </div>
                        <div class="form-box">
                            <label>Choose Password *</label>
                            <input type="password" name="password" class="form-control" required placeholder="••••••••">
                        </div>
                    </div>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                        <div class="form-box">
                            <label>Phone Number</label>
                            <input type="text" name="phone" class="form-control" placeholder="+94 77 123 4567">
                        </div>
                        <div class="form-box">
                            <label>City</label>
                            <input type="text" name="city" class="form-control" placeholder="Colombo">
                        </div>
                    </div>
                    <div class="form-box">
                        <label>Delivery Location Address</label>
                        <textarea name="address" class="form-control" rows="2" placeholder="Street details..."></textarea>
                    </div>
                    <button type="submit" class="btn" style="width:100%; height:42px;"><i class="ph ph-user-plus"></i> Create Account</button>
                </form>
            </div>

        // ------------------ PAGE: WHOLESALER REQUEST ------------------
        elseif ($page === 'wholesaler-request'):
        ?>
            <div class="card" style="max-width:600px; margin:0 auto;">
                <h3 style="margin-top:0; border-bottom:1px solid var(--mega-divider); padding-bottom:10px; margin-bottom:15px; text-align:center;">Request B2B Wholesale Pricing</h3>
                <p style="font-size:13px; color:var(--text-muted); line-height:1.5; margin-bottom:20px; text-align:center;">Submit credentials for your organization. Verified B2B accounts receive special wholesale rates and invoice credit settlement privileges.</p>
                
                <form action="index.php?p=wholesaler-request" method="POST">
                    <input type="hidden" name="action" value="submit_wholesaler_request">

                    <div class="form-box">
                        <label>Business / Company Entity Name *</label>
                        <input type="text" name="business_name" class="form-control" required placeholder="e.g. Candent Paper Ltd">
                    </div>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                        <div class="form-box">
                            <label>Procurement Contact Phone *</label>
                            <input type="text" name="contact_number" class="form-control" required placeholder="+94 11 123 4567">
                        </div>
                        <div class="form-box">
                            <label>Official Business Email *</label>
                            <input type="email" name="email_address" class="form-control" required placeholder="procurement@candent.lk">
                        </div>
                    </div>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                        <div class="form-box">
                            <label>Requested Login Username *</label>
                            <input type="text" name="username" class="form-control" required placeholder="candent_b2b">
                        </div>
                        <div class="form-box">
                            <label>Requested Account Password *</label>
                            <input type="password" name="password" class="form-control" required placeholder="••••••••">
                        </div>
                    </div>
                    <div class="form-box">
                        <label>City *</label>
                        <input type="text" name="city" class="form-control" required placeholder="Colombo">
                    </div>
                    <div class="form-box">
                        <label>Company Address *</label>
                        <textarea name="address" class="form-control" rows="2" required placeholder="Headquarters street location..."></textarea>
                    </div>
                    <div class="form-box">
                        <label>Additional Verification References (BRN documents, tax reference...)</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                    <button type="submit" class="btn" style="width:100%; height:42px;"><i class="ph ph-briefcase"></i> Submit Partnership Application</button>
                </form>
            </div>

        // ------------------ PAGE: REQUEST SUCCESS ------------------
        elseif ($page === 'request_success'):
        ?>
            <div class="card" style="max-width:550px; margin: 0 auto; text-align:center; padding:40px 20px;">
                <div style="width:60px; height:60px; border-radius:50%; background:rgba(52,199,89,0.1); color:#34c759; display:flex; align-items:center; justify-content:center; font-size:32px; margin:0 auto 20px auto;"><i class="ph-fill ph-check-circle"></i></div>
                <h3>Application Filed Successfully</h3>
                <p style="color:var(--text-muted); font-size:14px; line-height:1.6; margin-bottom:25px;">Our support department is currently auditing your organization parameters. You will receive activation alerts via your registered email shortly.</p>
                <a href="index.php?p=shop" class="btn">Browse Shop</a>
            </div>

        // ------------------ PAGE: ORDER SUCCESS ------------------
        elseif ($page === 'order_success'):
        ?>
            <div class="card" style="max-width:550px; margin: 0 auto; text-align:center; padding:40px 20px;">
                <div style="width:60px; height:60px; border-radius:50%; background:rgba(52,199,89,0.1); color:#34c759; display:flex; align-items:center; justify-content:center; font-size:32px; margin:0 auto 20px auto;"><i class="ph-fill ph-check-circle"></i></div>
                <h3>Order Posted!</h3>
                <p style="color:var(--text-muted); font-size:14.5px; line-height:1.6; margin-bottom:25px;"><?= $message ?></p>
                <div style="display:flex; justify-content:center; gap:10px;">
                    <a href="index.php?p=shop" class="btn">Continue Catalog Shopping</a>
                    <a href="index.php?p=portal" class="btn-secondary">Go to Account Portal</a>
                </div>
            </div>

        // ------------------ PAGES: CUSTOMER PORTAL WORKSPACE ------------------
        elseif (strpos($page, 'portal') === 0):
            if (!$isLoggedIn) {
                header('Location: index.php?p=login');
                exit;
            }

            // Fetch details depending on type
            $customerObj = null;
            if ($_SESSION['ec_role'] === 'retail') {
                $cStmt = $db->prepare("SELECT * FROM ecommerce_retail_customers WHERE id = :id LIMIT 1");
                $cStmt->execute([':id' => $_SESSION['ec_user_id']]);
                $customerObj = $cStmt->fetch();
            } else {
                $cStmt = $db->prepare("SELECT id, name, username, email, phone, address, territory as city FROM customers WHERE id = :id LIMIT 1");
                $cStmt->execute([':id' => $_SESSION['ec_user_id']]);
                $customerObj = $cStmt->fetch();
            }

            if(!$customerObj) {
                session_destroy();
                header('Location: index.php?p=login');
                exit;
            }
        ?>
            <div class="portal-layout">
                <!-- Sidebar Menu Navigation -->
                <div class="portal-menu-card">
                    <h4 style="margin-top:0; font-size:11px; text-transform:uppercase; color:var(--text-muted); font-weight:700; margin-bottom:12px; letter-spacing:0.5px;">Navigation Menu</h4>
                    <ul class="portal-menu-list">
                        <li><a href="index.php?p=portal" class="portal-menu-link <?= $page==='portal' ? 'active' : '' ?>"><i class="ph ph-squares-four"></i> Dashboard</a></li>
                        <li><a href="index.php?p=portal-orders" class="portal-menu-link <?= $page==='portal-orders' || $page==='portal-order-details' ? 'active' : '' ?>"><i class="ph ph-receipt"></i> Order History</a></li>
                        <li><a href="index.php?p=portal-wishlist" class="portal-menu-link <?= $page==='portal-wishlist' ? 'active' : '' ?>"><i class="ph ph-heart"></i> My Wishlist</a></li>
                        <li><a href="index.php?p=portal-returns" class="portal-menu-link <?= $page==='portal-returns' ? 'active' : '' ?>"><i class="ph ph-arrow-counter-clockwise"></i> Returns &amp; RMAs</a></li>
                        <li><a href="index.php?p=portal-profile" class="portal-menu-link <?= $page==='portal-profile' ? 'active' : '' ?>"><i class="ph ph-user-gear"></i> Settings</a></li>
                    </ul>
                </div>

                <!-- Right content pane -->
                <div>
                    <?php
                    // --- PORTAL SUBPAGE: DASHBOARD INDEX ---
                    if ($page === 'portal'):
                        // Counts
                        // Orders count
                        $ordCountStmt = $db->prepare("SELECT COUNT(*) as cnt FROM sales_orders WHERE customer_id = :cid");
                        $ordCountStmt->execute([':cid' => ($userRole==='wholesaler' ? $_SESSION['ec_customer_id'] : 0)]); // approximation for B2C vs B2B
                        $ordCount = $ordCountStmt->fetch()->cnt ?? 0;

                        // Wishlist count
                        $wishCountStmt = $db->prepare("SELECT COUNT(*) as cnt FROM ecommerce_wishlist WHERE customer_id = :cid AND customer_type = :ctype");
                        $wishCountStmt->execute([':cid' => $_SESSION['ec_user_id'], ':ctype' => $_SESSION['ec_role']]);
                        $wishCount = $wishCountStmt->fetch()->cnt ?? 0;

                        // Returns count
                        $retCountStmt = $db->prepare("SELECT COUNT(*) as cnt FROM ecommerce_returns r JOIN sales_orders so ON r.sales_order_id = so.id WHERE so.customer_id = :cid");
                        $retCountStmt->execute([':cid' => ($userRole==='wholesaler' ? $_SESSION['ec_customer_id'] : 0)]);
                        $retCount = $retCountStmt->fetch()->cnt ?? 0;

                        // Recent order histories
                        $recOrdStmt = $db->prepare("SELECT * FROM sales_orders WHERE customer_id = :cid ORDER BY id DESC LIMIT 5");
                        $recOrdStmt->execute([':cid' => ($userRole==='wholesaler' ? $_SESSION['ec_customer_id'] : 0)]);
                        $recentOrders = $recOrdStmt->fetchAll();
                    ?>
                        <div class="card" style="margin-bottom:20px;">
                            <h2 style="margin:0; font-size:20px; font-weight:800;">Welcome Back, <?= htmlspecialchars($customerObj->name) ?>!</h2>
                            <p style="color:var(--text-muted); font-size:13.5px; margin-top:4px;">Manage transactions, return authorizations, and account specifications directly from your partner portal ledger.</p>
                        </div>

                        <div class="portal-stats-grid">
                            <div class="stat-counter-card">
                                <div class="stat-icon"><i class="ph ph-receipt"></i></div>
                                <div>
                                    <span style="font-size:20px; font-weight:800; display:block; color:var(--text-main);"><?= $ordCount ?></span>
                                    <span style="font-size:11.5px; color:var(--text-muted);">Total Orders</span>
                                </div>
                            </div>
                            <div class="stat-counter-card">
                                <div class="stat-icon" style="color: #af52de; background: rgba(175,82,222,0.06);"><i class="ph ph-heart"></i></div>
                                <div>
                                    <span style="font-size:20px; font-weight:800; display:block; color:var(--text-main);"><?= $wishCount ?></span>
                                    <span style="font-size:11.5px; color:var(--text-muted);">Wishlist items</span>
                                </div>
                            </div>
                            <div class="stat-counter-card">
                                <div class="stat-icon" style="color: #ff9500; background: rgba(255,149,0,0.06);"><i class="ph ph-arrow-counter-clockwise"></i></div>
                                <div>
                                    <span style="font-size:20px; font-weight:800; display:block; color:var(--text-main);"><?= $retCount ?></span>
                                    <span style="font-size:11.5px; color:var(--text-muted);">RMAs Submitted</span>
                                </div>
                            </div>
                        </div>

                        <!-- Recent orders -->
                        <div class="card">
                            <h4 style="margin-top:0; border-bottom:1px solid var(--mega-divider); padding-bottom:10px; margin-bottom:15px;">Recent Orders</h4>
                            <?php if(empty($recentOrders)): ?>
                                <p style="color:var(--text-muted); font-size:13px; text-align:center;">No recent order records registered.</p>
                            <?php else: ?>
                                <table style="width:100%; border-collapse:collapse; font-size:13.5px; text-align:left;">
                                    <thead>
                                        <tr style="border-bottom:1px solid var(--card-border); color:var(--text-muted); font-weight:600;">
                                            <th style="padding:10px;">Order Reference</th>
                                            <th style="padding:10px;">Date</th>
                                            <th style="padding:10px;">Status</th>
                                            <th style="padding:10px; text-align:right;">Grand Total</th>
                                            <th style="padding:10px; text-align:right;">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($recentOrders as $ord): ?>
                                            <tr style="border-bottom:1px solid var(--mega-divider);">
                                                <td style="padding:12px; font-family:monospace;"><?= htmlspecialchars($ord->order_number) ?></td>
                                                <td style="padding:12px;"><?= date('M d, Y', strtotime($ord->order_date)) ?></td>
                                                <td style="padding:12px;">
                                                    <span class="pill-badge <?= $ord->status==='Delivered' ? 'pill-success' : ($ord->status==='Pending' ? 'pill-warning' : 'pill-danger') ?>"><?= htmlspecialchars($ord->status) ?></span>
                                                </td>
                                                <td style="padding:12px; text-align:right; font-weight:700;"><?= htmlspecialchars($settings['store_currency'] ?? 'Rs.') ?> <?= number_format($ord->grand_total, 2) ?></td>
                                                <td style="padding:12px; text-align:right;"><a href="index.php?p=portal-order-details&id=<?= $ord->id ?>" class="btn" style="padding:4px 8px; font-size:11px;">Invoice</a></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>

                    <?php
                    // --- PORTAL SUBPAGE: ORDER HISTORY ---
                    elseif ($page === 'portal-orders'):
                        $recOrdStmt = $db->prepare("SELECT * FROM sales_orders WHERE customer_id = :cid ORDER BY id DESC");
                        $recOrdStmt->execute([':cid' => ($userRole==='wholesaler' ? $_SESSION['ec_customer_id'] : 0)]); // B2C / B2B approximation
                        $allOrders = $recOrdStmt->fetchAll();
                    ?>
                        <div class="card">
                            <h3 style="margin-top:0; border-bottom:1px solid var(--mega-divider); padding-bottom:10px; margin-bottom:15px;">Your Order Ledger</h3>
                            <?php if(empty($allOrders)): ?>
                                <p style="color:var(--text-muted); font-size:13px; text-align:center; padding:30px;">No historical orders listed in ledger.</p>
                            <?php else: ?>
                                <table style="width:100%; border-collapse:collapse; font-size:13px; text-align:left;">
                                    <thead>
                                        <tr style="border-bottom:1px solid var(--card-border); color:var(--text-muted);">
                                            <th style="padding:10px;">Order No.</th>
                                            <th style="padding:10px;">Date</th>
                                            <th style="padding:10px;">Status</th>
                                            <th style="padding:10px; text-align:right;">Subtotal</th>
                                            <th style="padding:10px; text-align:right;">Grand Total</th>
                                            <th style="padding:10px; text-align:right; width:80px;">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($allOrders as $ord): ?>
                                            <tr style="border-bottom:1px solid var(--mega-divider);">
                                                <td style="padding:12px; font-family:monospace;"><?= htmlspecialchars($ord->order_number) ?></td>
                                                <td style="padding:12px;"><?= date('M d, Y', strtotime($ord->order_date)) ?></td>
                                                <td style="padding:12px;">
                                                    <span class="pill-badge <?= $ord->status==='Delivered' ? 'pill-success' : ($ord->status==='Pending' ? 'pill-warning' : 'pill-danger') ?>"><?= htmlspecialchars($ord->status) ?></span>
                                                </td>
                                                <td style="padding:12px; text-align:right;"><?= htmlspecialchars($settings['store_currency'] ?? 'Rs.') ?> <?= number_format($ord->subtotal, 2) ?></td>
                                                <td style="padding:12px; text-align:right; font-weight:700;"><?= htmlspecialchars($settings['store_currency'] ?? 'Rs.') ?> <?= number_format($ord->grand_total, 2) ?></td>
                                                <td style="padding:12px; text-align:right;"><a href="index.php?p=portal-order-details&id=<?= $ord->id ?>" class="btn" style="padding:4px 8px; font-size:11px;">Invoice</a></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>

                    <?php
                    // --- PORTAL SUBPAGE: ORDER DETAILS (INVOICE STYLE) ---
                    elseif ($page === 'portal-order-details'):
                        $orderId = intval($_GET['id'] ?? 0);
                        // Fetch order
                        $oStmt = $db->prepare("SELECT * FROM sales_orders WHERE id = :id LIMIT 1");
                        $oStmt->execute([':id' => $orderId]);
                        $order = $oStmt->fetch();

                        if(!$order) {
                            echo "<p style='text-align:center;'>Order profile not located.</p>";
                        } else {
                            // Fetch items
                            $iStmt = $db->prepare("SELECT * FROM sales_order_items WHERE sales_order_id = :id");
                            $iStmt->execute([':id' => $order->id]);
                            $orderItems = $iStmt->fetchAll();
                        ?>
                            <div class="card" id="print-area">
                                <div style="display:flex; justify-content:space-between; border-bottom:1px solid var(--mega-divider); padding-bottom:15px; margin-bottom:20px;">
                                    <div>
                                        <h2 style="margin:0; font-size:20px; font-weight:800; color:var(--primary);"><?= htmlspecialchars($settings['store_name'] ?? 'CURTISS STORE') ?></h2>
                                        <span style="font-size:12px; color:var(--text-muted);"><?= htmlspecialchars($settings['store_email'] ?? '') ?> | <?= htmlspecialchars($settings['store_phone'] ?? '') ?></span>
                                    </div>
                                    <div style="text-align:right;">
                                        <span class="pill-badge pill-success"><?= htmlspecialchars($order->status) ?></span>
                                        <span style="display:block; font-size:12px; font-family:monospace; margin-top:4px;"><?= htmlspecialchars($order->order_number) ?></span>
                                    </div>
                                </div>

                                <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; font-size:13.5px; margin-bottom:25px;">
                                    <div>
                                        <strong style="font-size:11px; text-transform:uppercase; color:var(--text-muted); display:block; margin-bottom:4px;">Billing Target</strong>
                                        <strong style="display:block;"><?= htmlspecialchars($order->customer_name) ?></strong>
                                        <span>Phone: <?= htmlspecialchars($order->customer_phone) ?></span>
                                    </div>
                                    <div>
                                        <strong style="font-size:11px; text-transform:uppercase; color:var(--text-muted); display:block; margin-bottom:4px;">Invoice date</strong>
                                        <span>Order Date: <?= date('F d, Y', strtotime($order->order_date)) ?></span><br>
                                        <span>Dispatch notes: <?= htmlspecialchars($order->notes) ?></span>
                                    </div>
                                </div>

                                <table style="width:100%; border-collapse:collapse; font-size:13px; text-align:left; margin-bottom:20px;">
                                    <thead>
                                        <tr style="border-bottom:1px solid var(--card-border); background:rgba(0,0,0,0.01); color:var(--text-muted);">
                                            <th style="padding:10px;">Item Description</th>
                                            <th style="padding:10px;">SKU</th>
                                            <th style="padding:10px; text-align:right;">Unit Price</th>
                                            <th style="padding:10px; text-align:center;">Qty</th>
                                            <th style="padding:10px; text-align:right;">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($orderItems as $item): ?>
                                            <tr style="border-bottom:1px solid var(--mega-divider);">
                                                <td style="padding:12px; font-weight:600;"><?= htmlspecialchars($item->name) ?></td>
                                                <td style="padding:12px; font-family:monospace;"><?= htmlspecialchars($item->sku) ?></td>
                                                <td style="padding:12px; text-align:right;"><?= htmlspecialchars($settings['store_currency'] ?? 'Rs.') ?> <?= number_format($item->billing_price, 2) ?></td>
                                                <td style="padding:12px; text-align:center;"><?= $item->qty ?></td>
                                                <td style="padding:12px; text-align:right; font-weight:700;"><?= htmlspecialchars($settings['store_currency'] ?? 'Rs.') ?> <?= number_format($item->total, 2) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <tr>
                                            <td colspan="3"></td>
                                            <td style="padding:10px; text-align:right; color:var(--text-muted);">Subtotal:</td>
                                            <td style="padding:10px; text-align:right; font-weight:600;"><?= htmlspecialchars($settings['store_currency'] ?? 'Rs.') ?> <?= number_format($order->subtotal, 2) ?></td>
                                        </tr>
                                        <?php if(floatval($order->discount) > 0): ?>
                                            <tr>
                                                <td colspan="3"></td>
                                                <td style="padding:10px; text-align:right; color:var(--text-muted);">Discount:</td>
                                                <td style="padding:10px; text-align:right; font-weight:600; color:#ff3b30;">-<?= htmlspecialchars($settings['store_currency'] ?? 'Rs.') ?> <?= number_format($order->discount, 2) ?></td>
                                            </tr>
                                        <?php endif; ?>
                                        <tr style="border-top:1px dashed var(--card-border);">
                                            <td colspan="3"></td>
                                            <td style="padding:15px 10px; text-align:right; font-weight:800; font-size:15px;">Grand Total:</td>
                                            <td style="padding:15px 10px; text-align:right; font-weight:800; font-size:17px; color:var(--text-accent);"><?= htmlspecialchars($settings['store_currency'] ?? 'Rs.') ?> <?= number_format($order->grand_total, 2) ?></td>
                                        </tr>
                                    </tbody>
                                </table>

                                <div style="display:flex; justify-content:space-between; align-items:center;">
                                    <button class="btn-secondary" onclick="window.print()"><i class="ph ph-printer"></i> Print Statement</button>
                                    <a href="index.php?p=portal-orders" class="btn">Back to Order list</a>
                                </div>
                            </div>
                        <?php
                        }

                    // --- PORTAL SUBPAGE: WISHLIST ---
                    elseif ($page === 'portal-wishlist'):
                        $wStmt = $db->prepare("SELECT w.item_id, i.name as item_name, i.price, i.wholesale_price, i.image_path, i.item_code 
                                               FROM ecommerce_wishlist w 
                                               JOIN items i ON w.item_id = i.id 
                                               WHERE w.customer_id = :cid AND w.customer_type = :ctype");
                        $wStmt->execute([':cid' => $_SESSION['ec_user_id'], ':ctype' => $_SESSION['ec_role']]);
                        $wishItems = $wStmt->fetchAll();
                    ?>
                        <div class="card">
                            <h3 style="margin-top:0; border-bottom:1px solid var(--mega-divider); padding-bottom:10px; margin-bottom:15px;">My Wishlist</h3>
                            <?php if(empty($wishItems)): ?>
                                <p style="color:var(--text-muted); font-size:13px; text-align:center; padding:30px;">Your wishlist is empty.</p>
                            <?php else: ?>
                                <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(210px, 1fr)); gap:20px;">
                                    <?php foreach($wishItems as $wish): 
                                        $price = getItemPrice($wish, $userRole);
                                    ?>
                                        <div class="prod-showcase-card">
                                            <div class="prod-image-wrapper">
                                                <?php if(!empty($wish->image_path)): ?>
                                                    <img src="<?= getProductImageUrl($wish->image_path) ?>" alt="Item preview">
                                                <?php else: ?>
                                                    <i class="ph ph-image" style="font-size:32px; color:#ccc;"></i>
                                                <?php endif; ?>
                                            </div>
                                            <div class="prod-info-box">
                                                <span class="prod-name" style="font-size:13.5px;"><?= htmlspecialchars($wish->item_name) ?></span>
                                                <div class="prod-price-row">
                                                    <span class="prod-price"><?= htmlspecialchars($settings['store_currency'] ?? 'Rs.') ?> <?= number_format($price, 2) ?></span>
                                                </div>
                                                <div style="display:flex; gap:10px; margin-top:12px;">
                                                    <a href="index.php?p=product&id=<?= $wish->item_id ?>" class="btn" style="flex-grow:1; padding:6px; font-size:11px;">View</a>
                                                    <form action="index.php?p=portal-wishlist" method="POST" style="margin:0;">
                                                        <input type="hidden" name="action" value="wishlist_action">
                                                        <input type="hidden" name="item_id" value="<?= $wish->item_id ?>">
                                                        <input type="hidden" name="act" value="delete">
                                                        <button type="submit" class="btn-secondary" style="border-color:#ff3b30; color:#ff3b30; padding:6px 10px; font-size:11px;"><i class="ph ph-trash"></i></button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                    <?php
                    // --- PORTAL SUBPAGE: RETURNS & RMAS ---
                    elseif ($page === 'portal-returns'):
                        // Eligible delivered orders
                        $eoStmt = $db->prepare("SELECT id, order_number, order_date FROM sales_orders WHERE customer_id = :cid AND status = 'Delivered'");
                        $eoStmt->execute([':cid' => ($userRole==='wholesaler' ? $_SESSION['ec_customer_id'] : 0)]); // B2C / B2B approximation
                        $eligibleOrders = $eoStmt->fetchAll();

                        // Return requests history
                        $retStmt = $db->prepare("SELECT r.*, so.order_number 
                                                 FROM ecommerce_returns r 
                                                 JOIN sales_orders so ON r.sales_order_id = so.id 
                                                 WHERE so.customer_id = :cid 
                                                 ORDER BY r.id DESC");
                        $retStmt->execute([':cid' => ($userRole==='wholesaler' ? $_SESSION['ec_customer_id'] : 0)]);
                        $returnsList = $retStmt->fetchAll();
                    ?>
                        <div class="card" style="margin-bottom:25px;">
                            <h3 style="margin-top:0; border-bottom:1px solid var(--mega-divider); padding-bottom:10px; margin-bottom:15px;">Request Return authorization</h3>
                            <?php if(empty($eligibleOrders)): ?>
                                <p style="font-size:13px; color:var(--text-muted);">No delivered orders are currently eligible for return requests.</p>
                            <?php else: ?>
                                <form action="index.php?p=portal-returns" method="POST">
                                    <input type="hidden" name="action" value="request_return">
                                    <div class="form-box">
                                        <label>Select Delivered Order *</label>
                                        <select name="sales_order_id" class="form-control" required>
                                            <option value="">-- Choose Order --</option>
                                            <?php foreach($eligibleOrders as $eo): ?>
                                                <option value="<?= $eo->id ?>">Order <?= htmlspecialchars($eo->order_number) ?> (Purchased <?= date('M d, Y', strtotime($eo->order_date)) ?>)</option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-box">
                                        <label>Reason for Return / Exchange *</label>
                                        <input type="text" name="reason" class="form-control" required placeholder="e.g. Defective items, wrong dimensions...">
                                    </div>
                                    <div class="form-box">
                                        <label>Details & comments</label>
                                        <textarea name="details" class="form-control" rows="3" placeholder="Provide quantity details..."></textarea>
                                    </div>
                                    <button type="submit" class="btn"><i class="ph ph-arrow-counter-clockwise"></i> Submit return request</button>
                                </form>
                            <?php endif; ?>
                        </div>

                        <div class="card">
                            <h3 style="margin-top:0; border-bottom:1px solid var(--mega-divider); padding-bottom:10px; margin-bottom:15px;">Return Request History</h3>
                            <?php if(empty($returnsList)): ?>
                                <p style="color:var(--text-muted); font-size:13px; text-align:center;">No returns requests filed.</p>
                            <?php else: ?>
                                <table style="width:100%; border-collapse:collapse; font-size:13px; text-align:left;">
                                    <thead>
                                        <tr style="border-bottom:1px solid var(--card-border); color:var(--text-muted);">
                                            <th style="padding:10px;">RMA ID</th>
                                            <th style="padding:10px;">Order No.</th>
                                            <th style="padding:10px;">Reason</th>
                                            <th style="padding:10px;">Date Filed</th>
                                            <th style="padding:10px; text-align:right;">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($returnsList as $ret): ?>
                                            <tr style="border-bottom:1px solid var(--mega-divider);">
                                                <td style="padding:12px; font-weight:600;">#RMA-<?= $ret->id ?></td>
                                                <td style="padding:12px; font-family:monospace;"><?= htmlspecialchars($ret->order_number) ?></td>
                                                <td style="padding:12px;"><?= htmlspecialchars($ret->reason) ?></td>
                                                <td style="padding:12px;"><?= date('M d, Y', strtotime($ret->created_at)) ?></td>
                                                <td style="padding:12px; text-align:right;">
                                                    <span class="pill-badge <?= $ret->status==='approved' ? 'pill-success' : ($ret->status==='pending' ? 'pill-warning' : 'pill-danger') ?>"><?= htmlspecialchars($ret->status) ?></span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>

                    <?php
                    // --- PORTAL SUBPAGE: PROFILE SETTINGS ---
                    elseif ($page === 'portal-profile'):
                    ?>
                        <div class="card">
                            <h3 style="margin-top:0; border-bottom:1px solid var(--mega-divider); padding-bottom:10px; margin-bottom:20px;">Profile Settings</h3>
                            <form action="index.php?p=portal-profile" method="POST">
                                <input type="hidden" name="action" value="update_profile">

                                <div class="form-box">
                                    <label>Display / Company Name *</label>
                                    <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($customerObj->name) ?>">
                                </div>
                                <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                                    <div class="form-box">
                                        <label>Email Address *</label>
                                        <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($customerObj->email ?? '') ?>">
                                    </div>
                                    <div class="form-box">
                                        <label>Username *</label>
                                        <input type="text" name="username" class="form-control" required value="<?= htmlspecialchars($customerObj->username ?? '') ?>">
                                    </div>
                                </div>
                                <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                                    <div class="form-box">
                                        <label>Phone Number</label>
                                        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($customerObj->phone ?? '') ?>">
                                    </div>
                                    <div class="form-box">
                                        <label>City</label>
                                        <input type="text" name="city" class="form-control" value="<?= htmlspecialchars($customerObj->city ?? '') ?>">
                                    </div>
                                </div>
                                <div class="form-box">
                                    <label>Delivery/Billing Address</label>
                                    <textarea name="address" class="form-control" rows="3"><?= htmlspecialchars($customerObj->address ?? '') ?></textarea>
                                </div>
                                <div class="form-box" style="border-top:1px dashed var(--mega-divider); padding-top:15px; margin-top:15px;">
                                    <label>Update Password (Leave empty to keep current)</label>
                                    <input type="password" name="password" class="form-control" placeholder="••••••••">
                                </div>
                                <button type="submit" class="btn"><i class="ph ph-floppy-disk"></i> Update Profile Details</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

    </main>

    <footer>
        <div class="container">
            <p>&copy; <?= date('Y') ?> <?= htmlspecialchars($settings['store_name'] ?? 'CURTISS STORE') ?>. All Rights Reserved. Direct integration with Curtiss ERP Catalog.</p>
        </div>
    </footer>

    <script>
        // Light / Dark Theme script
        function toggleTheme() {
            const body = document.body;
            const icon = document.getElementById('theme-icon');
            const currentTheme = body.getAttribute('data-theme');
            
            if (currentTheme === 'light') {
                body.setAttribute('data-theme', 'dark');
                icon.className = 'ph ph-moon';
                localStorage.setItem('ec-theme', 'dark');
            } else {
                body.setAttribute('data-theme', 'light');
                icon.className = 'ph ph-sun';
                localStorage.setItem('ec-theme', 'light');
            }
        }

        // Apply saved theme
        window.addEventListener('DOMContentLoaded', () => {
            const savedTheme = localStorage.getItem('ec-theme') || 'light';
            document.body.setAttribute('data-theme', savedTheme);
            const icon = document.getElementById('theme-icon');
            if (savedTheme === 'dark') {
                if (icon) icon.className = 'ph ph-moon';
            } else {
                if (icon) icon.className = 'ph ph-sun';
            }
        });
    </script>
</body>
</html>
<?php
// Handle logout url
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}
?>
