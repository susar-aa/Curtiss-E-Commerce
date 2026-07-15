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

        // Double check if username column exists in wholesaler_requests
        $stmt = $db->query("SHOW COLUMNS FROM wholesaler_requests LIKE 'username'");
        if (!$stmt->fetch()) {
            $db->exec("ALTER TABLE wholesaler_requests ADD COLUMN username VARCHAR(100) NOT NULL AFTER email_address");
        }
        $stmt = $db->query("SHOW COLUMNS FROM wholesaler_requests LIKE 'password'");
        if (!$stmt->fetch()) {
            $db->exec("ALTER TABLE wholesaler_requests ADD COLUMN password VARCHAR(255) NOT NULL AFTER username");
        }

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

        // Double check if username column exists in ecommerce_retail_customers
        $stmt = $db->query("SHOW COLUMNS FROM ecommerce_retail_customers LIKE 'username'");
        if (!$stmt->fetch()) {
            $db->exec("ALTER TABLE ecommerce_retail_customers ADD COLUMN username VARCHAR(100) UNIQUE NULL AFTER email");
        }

        // Ensure company_settings has ecommerce_store_url
        $stmt = $db->query("SHOW TABLES LIKE 'company_settings'");
        if ($stmt->fetch()) {
            $stmtCol = $db->query("SHOW COLUMNS FROM company_settings LIKE 'ecommerce_store_url'");
            if (!$stmtCol->fetch()) {
                $db->exec("ALTER TABLE company_settings ADD COLUMN ecommerce_store_url VARCHAR(255) NULL DEFAULT ''");
            }
        }
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

// Helper to get ERP Base URL dynamically based on hosting environment
function getErpBaseUrl() {
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    if (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false) {
        return 'http://localhost/Curtiss-ERP/';
    }
    return 'https://curtiss.suzxlabs.com/';
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
                // Check if email already registered as wholesaler
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
                        ':pass' => $req_password, // Admin will verify and hash on approval
                        ':notes' => $notes
                    ]);
                    $message = 'Your wholesaler registration request has been submitted successfully! Our sales team will call you shortly to verify details and activate your wholesale access.';
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
        
        // Variation options
        $sku = $_POST['variation_sku'] ?? '';
        $varPrice = $_POST['variation_price'] ?? null;
        $varWholesalePrice = $_POST['variation_wholesale_price'] ?? null;
        $varAttr = $_POST['variation_attribute'] ?? '';

        $stmt = $db->prepare("SELECT * FROM items WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $itemId]);
        $item = $stmt->fetch();

        if ($item) {
            $price = getItemPrice($item, $userRole);
            
            // If variation was selected, override price and SKU
            if (!empty($sku)) {
                $price = ($userRole === 'wholesaler') ? (float)$varWholesalePrice : (float)$varPrice;
            } else {
                $sku = $item->item_code;
            }

            $cartKey = $itemId . '_' . md5($sku);

            if (isset($_SESSION['ec_cart'][$cartKey])) {
                $_SESSION['ec_cart'][$cartKey]['qty'] += $qty;
            } else {
                $_SESSION['ec_cart'][$cartKey] = [
                    'item_id' => $item->id,
                    'name' => $item->name,
                    'sku' => $sku,
                    'attribute' => $varAttr,
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

    // 6. CHECKOUT ORDER SUBMIT
    if ($action === 'submit_order') {
        if (empty($_SESSION['ec_cart'])) {
            $error = 'Your cart is empty.';
        } else {
            try {
                $db->beginTransaction();

                // Compute cart sums
                $subtotal = 0;
                foreach($_SESSION['ec_cart'] as $item) {
                    $subtotal += $item['price'] * $item['qty'];
                }
                
                $grandTotal = $subtotal;
                $orderNo = 'ECO-' . time() . '-' . rand(1000, 9999);

                $orderDate = date('Y-m-d');
                $dueDate = date('Y-m-d', strtotime('+7 days'));

                // Define customer profiles
                $customerId = 0;
                $customerName = '';
                $customerPhone = '';

                if ($userRole === 'wholesaler') {
                    $customerId = $_SESSION['ec_customer_id'];
                    $customerName = $_SESSION['ec_name'];
                    // Fetch phone
                    $cStmt = $db->prepare("SELECT phone FROM customers WHERE id = :id LIMIT 1");
                    $cStmt->execute([':id' => $customerId]);
                    $cRow = $cStmt->fetch();
                    $customerPhone = $cRow->phone ?? '';
                } else {
                    // Create generic Walk-in customer or fetch/create 'E-Commerce Retail' profile in ERP
                    // We can check if a retail customer profile with name 'E-Commerce Retail Customer' exists
                    $cStmt = $db->query("SELECT id FROM customers WHERE name = 'E-Commerce Retail Customer' LIMIT 1");
                    $cRow = $cStmt->fetch();
                    if ($cRow) {
                        $customerId = $cRow->id;
                    } else {
                        // Create customer profile for retail channel
                        $ins = $db->prepare("INSERT INTO customers (name, email, phone, address, territory) VALUES ('E-Commerce Retail Customer', 'ecommerce@retail.com', '0000000000', 'Online Storefront', 'E-Commerce')");
                        $ins->execute();
                        $customerId = $db->lastInsertId();
                    }
                    $customerName = $_POST['billing_name'] ?? 'E-Commerce Customer';
                    $customerPhone = $_POST['billing_phone'] ?? '';
                }

                // Insert into ERP sales_orders
                $oStmt = $db->prepare("INSERT INTO sales_orders (order_number, customer_id, customer_name, customer_phone, billing_type, subtotal, discount, grand_total, order_date, due_date, status, notes) 
                                      VALUES (:order_num, :cid, :cname, :cphone, :btype, :sub, 0.00, :grand, :odate, :ddate, 'Pending', :notes)");
                $oStmt->execute([
                    ':order_num' => $orderNo,
                    ':cid' => $customerId,
                    ':cname' => $customerName,
                    ':cphone' => $customerPhone,
                    ':btype' => ($userRole === 'wholesaler' ? 'wholesale' : 'retail'),
                    ':sub' => $subtotal,
                    ':grand' => $grandTotal,
                    ':odate' => $orderDate,
                    ':ddate' => $dueDate,
                    ':notes' => 'Order placed online via E-Commerce portal. Shipping to: ' . ($_POST['shipping_address'] ?? 'Not specified')
                ]);

                $orderId = $db->lastInsertId();

                // Insert order items
                $itemStmt = $db->prepare("INSERT INTO sales_order_items (sales_order_id, item_id, sku, name, billing_price, qty, total) 
                                          VALUES (:oid, :item_id, :sku, :name, :price, :qty, :total)");

                foreach ($_SESSION['ec_cart'] as $cartItem) {
                    $totalPrice = $cartItem['price'] * $cartItem['qty'];
                    $itemStmt->execute([
                        ':oid' => $orderId,
                        ':item_id' => $cartItem['item_id'],
                        ':sku' => $cartItem['sku'],
                        ':name' => $cartItem['name'] . (!empty($cartItem['attribute']) ? ' (' . $cartItem['attribute'] . ')' : ''),
                        ':price' => $cartItem['price'],
                        ':qty' => $cartItem['qty'],
                        ':total' => $totalPrice
                    ]);
                }

                $db->commit();
                $_SESSION['ec_cart'] = []; // Clear Cart
                $message = "Order placed successfully! Your Order Reference is: <strong>{$orderNo}</strong>. Thank you for shopping with Curtiss.";
                $page = 'order_success';
            } catch (Exception $e) {
                $db->rollBack();
                $error = 'Failed to submit order: ' . $e->getMessage();
            }
        }
    }
}

// Fetch categories for shop filter
$categories = $db->query("SELECT * FROM item_categories ORDER BY name ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>Curtiss Stationery - Premium E-Commerce</title>
    <!-- Elegant Fonts: Lora (Serif) for headings, Inter (Sans) for body -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Lora:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        /* * PREMIUM STATIONERY DESIGN SYSTEM 
         */
        :root {
            /* Colors - Light Mode (Warm, Elegant) */
            --bg-body: #FAF9F6; /* Soft off-white paper tone */
            --bg-card: #FFFFFF;
            --bg-input: #FFFFFF;
            --bg-subtle: #F0EFEA;
            
            --brand-primary: #2C4A3E; /* Deep sophisticated sage/forest green */
            --brand-primary-hover: #1E332B;
            --brand-accent: #C4A47C; /* Muted gold/bronze */
            
            --text-main: #2D2C2A;
            --text-muted: #6B6A65;
            --text-inverse: #FFFFFF;
            
            --border-light: #E8E6E1;
            --border-strong: #D1CEC7;
            
            /* Status */
            --status-success-bg: #E8F3EC;
            --status-success-text: #236B42;
            --status-danger-bg: #FCE8E8;
            --status-danger-text: #9B2C2C;

            /* Typography */
            --font-heading: 'Lora', serif;
            --font-body: 'Inter', sans-serif;
            
            /* UI Elements */
            --radius-sm: 4px;
            --radius-md: 8px;
            --radius-lg: 12px;
            --radius-full: 999px;
            
            --shadow-sm: 0 2px 8px rgba(44, 74, 62, 0.04);
            --shadow-md: 0 8px 24px rgba(44, 74, 62, 0.08);
            --shadow-float: 0 16px 40px rgba(44, 74, 62, 0.12);
            
            --transition-smooth: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            --header-height: 80px;
        }

        [data-theme="dark"] {
            /* Colors - Dark Mode (Deep, Luxurious) */
            --bg-body: #161817; 
            --bg-card: #1E201F;
            --bg-input: #161817;
            --bg-subtle: #2A2D2B;
            
            --brand-primary: #8FBCA3; /* Softened green for dark mode visibility */
            --brand-primary-hover: #A8CEB8;
            --brand-accent: #DBC1A0;
            
            --text-main: #F4F4F4;
            --text-muted: #A3A3A3;
            --text-inverse: #161817;
            
            --border-light: #333634;
            --border-strong: #4A4D4B;
            
            --status-success-bg: rgba(35, 107, 66, 0.2);
            --status-success-text: #8FBCA3;
            --status-danger-bg: rgba(155, 44, 44, 0.2);
            --status-danger-text: #FCA5A5;
            
            --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.4);
            --shadow-md: 0 8px 24px rgba(0, 0, 0, 0.5);
            --shadow-float: 0 16px 40px rgba(0, 0, 0, 0.6);
        }

        /* RESETS & BASE */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: var(--font-body);
            background-color: var(--bg-body);
            color: var(--text-main);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            transition: background-color 0.4s ease, color 0.4s ease;
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: var(--font-heading);
            font-weight: 500;
            line-height: 1.3;
            color: var(--text-main);
        }

        a {
            color: inherit;
            text-decoration: none;
            transition: var(--transition-smooth);
        }

        img {
            max-width: 100%;
            height: auto;
            display: block;
        }

        input, select, textarea, button {
            font-family: inherit;
        }

        /* LAYOUT */
        .container {
            width: 100%;
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 24px;
        }
        
        @media (max-width: 768px) {
            .container { padding: 0 16px; }
        }

        /* HEADER & NAVIGATION */
        .top-banner {
            background-color: var(--brand-primary);
            color: var(--text-inverse);
            text-align: center;
            font-size: 12px;
            font-weight: 500;
            letter-spacing: 1px;
            padding: 8px 16px;
            text-transform: uppercase;
        }

        header {
            background-color: rgba(var(--bg-card), 0.95);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border-light);
            position: sticky;
            top: 0;
            z-index: 1000;
            height: var(--header-height);
            display: flex;
            align-items: center;
            transition: background-color 0.4s, border-color 0.4s;
        }

        .nav-wrapper {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
        }

        .brand-logo {
            font-family: var(--font-heading);
            font-size: 24px;
            font-weight: 600;
            color: var(--text-main);
            display: flex;
            align-items: center;
            gap: 12px;
            letter-spacing: -0.5px;
        }
        
        .brand-logo i {
            color: var(--brand-primary);
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 32px;
        }

        .nav-link {
            font-size: 14px;
            font-weight: 500;
            color: var(--text-muted);
            position: relative;
            padding: 8px 0;
        }

        .nav-link:hover, .nav-link.active {
            color: var(--text-main);
        }

        .nav-link::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 1px;
            background-color: var(--text-main);
            transition: width 0.3s ease;
        }

        .nav-link:hover::after, .nav-link.active::after {
            width: 100%;
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .icon-btn {
            background: none;
            border: none;
            color: var(--text-main);
            font-size: 22px;
            cursor: pointer;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.2s;
        }

        .icon-btn:hover {
            transform: scale(1.05);
            color: var(--brand-primary);
        }

        .cart-badge {
            position: absolute;
            top: -6px;
            right: -8px;
            background-color: var(--brand-accent);
            color: #fff;
            font-size: 10px;
            font-weight: 600;
            height: 18px;
            min-width: 18px;
            border-radius: var(--radius-full);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 4px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .role-badge {
            font-size: 11px;
            font-weight: 500;
            padding: 4px 10px;
            border-radius: var(--radius-full);
            border: 1px solid var(--border-strong);
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: var(--text-muted);
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        /* MOBILE MENU DRAWER */
        .mobile-toggle {
            display: none;
        }

        .drawer-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            backdrop-filter: blur(4px);
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
            z-index: 1999;
        }
        .drawer-overlay.active {
            opacity: 1;
            pointer-events: auto;
        }

        .drawer {
            position: fixed;
            top: 0;
            right: -100%;
            width: 85%;
            max-width: 360px;
            height: 100vh;
            background: var(--bg-card);
            z-index: 2000;
            padding: 32px 24px;
            box-shadow: var(--shadow-float);
            transition: right 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
            display: flex;
            flex-direction: column;
        }
        .drawer.active {
            right: 0;
        }
        .drawer-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
        }
        .drawer-links {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }
        .drawer-links a {
            font-family: var(--font-heading);
            font-size: 20px;
            color: var(--text-main);
        }
        .drawer-footer {
            margin-top: auto;
            border-top: 1px solid var(--border-light);
            padding-top: 24px;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        /* BUTTONS */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 28px;
            font-size: 14px;
            font-weight: 500;
            letter-spacing: 0.5px;
            border-radius: var(--radius-sm);
            cursor: pointer;
            transition: var(--transition-smooth);
            border: 1px solid transparent;
            text-transform: uppercase;
        }
        
        .btn-primary {
            background-color: var(--brand-primary);
            color: var(--text-inverse);
        }
        .btn-primary:hover {
            background-color: var(--brand-primary-hover);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        
        .btn-outline {
            background-color: transparent;
            border-color: var(--border-strong);
            color: var(--text-main);
        }
        .btn-outline:hover {
            border-color: var(--brand-primary);
            color: var(--brand-primary);
            background-color: var(--bg-subtle);
        }
        
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
            box-shadow: none !important;
        }

        /* ALERTS */
        .alert {
            padding: 16px 20px;
            border-radius: var(--radius-sm);
            font-size: 14px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin: 24px 0;
            line-height: 1.5;
        }
        .alert-error {
            background-color: var(--status-danger-bg);
            color: var(--status-danger-text);
            border: 1px solid rgba(155, 44, 44, 0.2);
        }
        .alert-success {
            background-color: var(--status-success-bg);
            color: var(--status-success-text);
            border: 1px solid rgba(35, 107, 66, 0.2);
        }

        /* FORMS */
        .form-group {
            margin-bottom: 24px;
        }
        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: var(--text-muted);
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .form-control {
            width: 100%;
            padding: 14px 16px;
            background-color: var(--bg-input);
            border: 1px solid var(--border-strong);
            border-radius: var(--radius-sm);
            color: var(--text-main);
            font-size: 15px;
            transition: all 0.2s ease;
        }
        .form-control:focus {
            outline: none;
            border-color: var(--brand-primary);
            box-shadow: 0 0 0 3px rgba(44, 74, 62, 0.1);
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        /* HERO SECTION */
        .hero {
            position: relative;
            padding: 100px 24px;
            text-align: center;
            background-color: var(--bg-card);
            border-bottom: 1px solid var(--border-light);
            overflow: hidden;
        }
        .hero::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: radial-gradient(circle at center, var(--bg-subtle) 0%, transparent 70%);
            opacity: 0.6;
            z-index: 0;
        }
        .hero-content {
            position: relative;
            z-index: 1;
            max-width: 800px;
            margin: 0 auto;
        }
        .hero h1 {
            font-size: clamp(36px, 5vw, 56px);
            margin-bottom: 24px;
            letter-spacing: -1px;
        }
        .hero p {
            font-size: clamp(16px, 2vw, 20px);
            color: var(--text-muted);
            margin-bottom: 40px;
            max-width: 600px;
            margin-inline: auto;
        }

        /* CATEGORIES */
        .section-title {
            text-align: center;
            margin: 64px 0 40px;
            font-size: 28px;
        }
        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 24px;
            margin-bottom: 80px;
        }
        .cat-card {
            background-color: var(--bg-card);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-sm);
            padding: 40px 24px;
            text-align: center;
            transition: var(--transition-smooth);
        }
        .cat-card:hover {
            transform: translateY(-4px);
            border-color: var(--border-strong);
            box-shadow: var(--shadow-sm);
        }
        .cat-icon {
            font-size: 40px;
            color: var(--brand-accent);
            margin-bottom: 16px;
        }
        .cat-name {
            font-family: var(--font-heading);
            font-size: 18px;
            color: var(--text-main);
        }

        /* SHOP LAYOUT */
        .shop-header {
            margin: 40px 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 20px;
        }
        .search-bar {
            display: flex;
            width: 100%;
            max-width: 480px;
            position: relative;
        }
        .search-bar input {
            padding-right: 48px;
            border-radius: var(--radius-full);
        }
        .search-bar button {
            position: absolute;
            right: 6px;
            top: 6px;
            bottom: 6px;
            background: var(--brand-primary);
            color: white;
            border: none;
            border-radius: var(--radius-full);
            width: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background 0.2s;
        }
        .search-bar button:hover { background: var(--brand-primary-hover); }

        .shop-container {
            display: grid;
            grid-template-columns: 240px 1fr;
            gap: 40px;
            margin-bottom: 80px;
            align-items: start;
        }
        
        .sidebar {
            position: sticky;
            top: calc(var(--header-height) + 40px);
        }
        .sidebar-title {
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-muted);
            margin-bottom: 20px;
            font-family: var(--font-body);
        }
        .filter-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .filter-list a {
            font-size: 15px;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 10px;
            transition: color 0.2s;
        }
        .filter-list a:hover, .filter-list a.active {
            color: var(--brand-primary);
            font-weight: 500;
        }

        /* PRODUCT GRID */
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 32px;
        }
        .product-card {
            display: flex;
            flex-direction: column;
            background: transparent;
            transition: var(--transition-smooth);
            group: hover;
        }
        .product-img-wrapper {
            position: relative;
            background-color: var(--bg-card);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-sm);
            aspect-ratio: 1/1;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            margin-bottom: 16px;
            transition: border-color 0.3s;
        }
        .product-img-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        .product-card:hover .product-img-wrapper {
            border-color: var(--border-strong);
        }
        .product-card:hover .product-img-wrapper img {
            transform: scale(1.05);
        }
        .empty-img {
            color: var(--text-muted);
            opacity: 0.3;
            font-size: 48px;
        }
        
        .product-brand {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-muted);
            margin-bottom: 4px;
        }
        .product-title {
            font-family: var(--font-heading);
            font-size: 18px;
            margin-bottom: 8px;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .product-footer {
            margin-top: auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .product-price {
            font-size: 16px;
            font-weight: 600;
            color: var(--text-main);
        }
        .stock-indicator {
            font-size: 12px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .stock-indicator::before {
            content: '';
            width: 6px; height: 6px;
            border-radius: 50%;
        }
        .in-stock::before { background-color: var(--status-success-text); }
        .out-stock::before { background-color: var(--status-danger-text); }

        /* PRODUCT DETAIL */
        .detail-layout {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 64px;
            margin: 64px 0 100px;
        }
        .detail-gallery {
            background-color: var(--bg-card);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-sm);
            aspect-ratio: 4/5;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .detail-gallery img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        .detail-info {
            padding-top: 24px;
        }
        .detail-brand {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: var(--brand-accent);
            margin-bottom: 16px;
            display: block;
            font-weight: 600;
        }
        .detail-title {
            font-size: 40px;
            margin-bottom: 24px;
            line-height: 1.2;
        }
        .detail-price-wrap {
            margin-bottom: 32px;
            padding-bottom: 32px;
            border-bottom: 1px solid var(--border-light);
        }
        .detail-price {
            font-size: 28px;
            font-weight: 500;
            margin-bottom: 8px;
        }
        .detail-desc {
            font-size: 16px;
            color: var(--text-muted);
            line-height: 1.8;
            margin-bottom: 40px;
        }
        .detail-form {
            background: var(--bg-card);
            padding: 32px;
            border: 1px solid var(--border-light);
            border-radius: var(--radius-sm);
        }
        .qty-row {
            display: flex;
            gap: 16px;
            align-items: flex-end;
        }

        /* CART & CHECKOUT */
        .page-header {
            text-align: center;
            margin: 64px 0 48px;
        }
        .page-header h1 {
            font-size: 36px;
            margin-bottom: 16px;
        }
        
        .cart-table-wrap {
            background: var(--bg-card);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-sm);
            overflow-x: auto;
            margin-bottom: 32px;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }
        .table th {
            padding: 20px 24px;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-muted);
            border-bottom: 1px solid var(--border-light);
            font-weight: 500;
        }
        .table td {
            padding: 24px;
            border-bottom: 1px solid var(--border-light);
            vertical-align: middle;
        }
        .table tr:last-child td { border-bottom: none; }
        
        .cart-item-name {
            font-family: var(--font-heading);
            font-size: 18px;
            margin-bottom: 4px;
        }
        .cart-item-meta {
            font-size: 13px;
            color: var(--text-muted);
        }
        .cart-qty-input {
            width: 80px;
            text-align: center;
            padding: 8px;
        }
        
        .cart-summary {
            background: var(--bg-subtle);
            padding: 32px 40px;
            border-radius: var(--radius-sm);
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 40px;
            margin-bottom: 64px;
        }
        .summary-total {
            font-family: var(--font-heading);
            font-size: 24px;
        }

        .checkout-layout {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 64px;
            margin-bottom: 80px;
            align-items: start;
        }
        .checkout-box {
            background: var(--bg-card);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-sm);
            padding: 40px;
        }
        .checkout-box h3 {
            font-size: 20px;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--border-light);
        }
        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 16px;
            font-size: 15px;
        }
        .summary-item-title {
            color: var(--text-muted);
            padding-right: 16px;
        }

        /* AUTH FORMS */
        .auth-container {
            max-width: 480px;
            margin: 80px auto;
            background: var(--bg-card);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-sm);
            padding: 48px 40px;
            box-shadow: var(--shadow-sm);
        }
        .auth-container h2 {
            text-align: center;
            margin-bottom: 32px;
            font-size: 28px;
        }
        .auth-footer {
            margin-top: 24px;
            text-align: center;
            font-size: 14px;
            color: var(--text-muted);
        }
        .auth-footer a {
            color: var(--brand-primary);
            font-weight: 500;
            text-decoration: underline;
            text-underline-offset: 4px;
        }

        /* SUCCESS STATES */
        .success-state {
            max-width: 560px;
            margin: 100px auto;
            text-align: center;
            padding: 64px 40px;
            background: var(--bg-card);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-sm);
            box-shadow: var(--shadow-sm);
        }
        .success-icon {
            font-size: 72px;
            color: var(--status-success-text);
            margin-bottom: 24px;
        }

        /* FOOTER */
        footer {
            margin-top: auto;
            border-top: 1px solid var(--border-light);
            background-color: var(--bg-card);
            padding: 40px 0;
            text-align: center;
            color: var(--text-muted);
            font-size: 14px;
        }

        /* RESPONSIVE OVERRIDES */
        @media (max-width: 1024px) {
            .shop-container { grid-template-columns: 1fr; }
            .sidebar { position: static; margin-bottom: 32px; }
            .filter-list { flex-direction: row; flex-wrap: wrap; }
            .filter-list a { background: var(--bg-subtle); padding: 8px 16px; border-radius: var(--radius-full); }
            
            .detail-layout { grid-template-columns: 1fr; gap: 40px; }
            .detail-gallery { aspect-ratio: auto; height: 50vh; }
            
            .checkout-layout { grid-template-columns: 1fr; }
        }

        @media (max-width: 768px) {
            .nav-links { display: none; }
            .mobile-toggle { display: flex; }
            
            .form-row { grid-template-columns: 1fr; }
            
            /* Responsive Cart Table */
            .table thead { display: none; }
            .table tbody tr {
                display: flex;
                flex-direction: column;
                padding: 16px 0;
                border-bottom: 1px solid var(--border-light);
            }
            .table td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 8px 16px;
                border: none;
            }
            .table td::before {
                content: attr(data-label);
                font-size: 12px;
                text-transform: uppercase;
                color: var(--text-muted);
                font-weight: 500;
            }
            
            .cart-summary {
                flex-direction: column;
                align-items: stretch;
                text-align: center;
                gap: 20px;
                padding: 24px;
            }
            
            .auth-container { padding: 32px 20px; margin: 40px auto; border: none; box-shadow: none; background: transparent; }
        }
    </style>
</head>
<body data-theme="light">

    <!-- Top Announcement Banner -->
    <div class="top-banner">
        Complimentary shipping on curated collections over Rs. 5000
    </div>

    <!-- Main Navigation -->
    <header>
        <div class="container nav-wrapper">
            <!-- Mobile Toggle -->
            <button class="icon-btn mobile-toggle" onclick="toggleMenu()" aria-label="Menu">
                <i class="ph ph-list"></i>
            </button>

            <!-- Brand Logo -->
            <a href="index.php" class="brand-logo">
                <i class="ph-light ph-feather"></i>
                Lumière
            </a>
            
            <!-- Desktop Links -->
            <nav class="nav-links">
                <a href="index.php?p=home" class="nav-link <?= $page === 'home' ? 'active' : '' ?>">Home</a>
                <a href="index.php?p=shop" class="nav-link <?= $page === 'shop' ? 'active' : '' ?>">Collections</a>
                
                <?php if ($userRole === 'wholesaler'): ?>
                    <span class="role-badge"><i class="ph ph-briefcase"></i> Corporate Partner</span>
                <?php elseif ($userRole === 'retail'): ?>
                    <span class="role-badge"><i class="ph ph-user"></i> Member</span>
                <?php else: ?>
                    <a href="index.php?p=wholesaler-request" class="nav-link <?= $page === 'wholesaler-request' ? 'active' : '' ?>">Corporate Gifting</a>
                <?php endif; ?>
            </nav>

            <!-- Actions (Right) -->
            <div class="nav-actions">
                <button class="icon-btn" onclick="toggleTheme()" aria-label="Toggle Theme">
                    <i class="ph ph-moon" id="theme-icon"></i>
                </button>
                
                <?php if ($isLoggedIn): ?>
                    <a href="index.php?logout=1" class="icon-btn" title="Sign out <?= htmlspecialchars($userName) ?>">
                        <i class="ph ph-sign-out"></i>
                    </a>
                <?php else: ?>
                    <a href="index.php?p=login" class="icon-btn" title="Sign in">
                        <i class="ph ph-user-circle"></i>
                    </a>
                <?php endif; ?>

                <a href="index.php?p=cart" class="icon-btn">
                    <i class="ph ph-shopping-bag"></i>
                    <span class="cart-badge"><?= count($_SESSION['ec_cart']) ?></span>
                </a>
            </div>
        </div>
    </header>

    <!-- Mobile Drawer -->
    <div class="drawer-overlay" id="drawerOverlay" onclick="toggleMenu()"></div>
    <div class="drawer" id="mobileDrawer">
        <div class="drawer-header">
            <a href="index.php" class="brand-logo" style="font-size: 20px;">
                <i class="ph-light ph-feather"></i> Lumière
            </a>
            <button class="icon-btn" onclick="toggleMenu()" aria-label="Close Menu">
                <i class="ph ph-x"></i>
            </button>
        </div>
        
        <div class="drawer-links">
            <a href="index.php?p=home" class="<?= $page === 'home' ? 'active' : '' ?>">Home</a>
            <a href="index.php?p=shop" class="<?= $page === 'shop' ? 'active' : '' ?>">Collections</a>
            <?php if (!$isLoggedIn && $userRole !== 'wholesaler'): ?>
                <a href="index.php?p=wholesaler-request" class="<?= $page === 'wholesaler-request' ? 'active' : '' ?>">Corporate Gifting</a>
            <?php endif; ?>
        </div>

        <div class="drawer-footer">
            <?php if ($isLoggedIn): ?>
                <div style="font-size: 14px; color: var(--text-muted);">
                    Signed in as <strong><?= htmlspecialchars($userName) ?></strong>
                </div>
                <a href="index.php?logout=1" class="btn btn-outline" style="width: 100%;">Sign Out</a>
            <?php else: ?>
                <a href="index.php?p=login" class="btn btn-primary" style="width: 100%;">Sign In</a>
                <a href="index.php?p=register-retail" class="btn btn-outline" style="width: 100%;">Create Account</a>
            <?php endif; ?>
        </div>
    </div>

    <main class="container" style="flex-grow: 1;">
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <i class="ph ph-warning-circle" style="font-size: 20px;"></i>
                <div><?= htmlspecialchars($error) ?></div>
            </div>
        <?php endif; ?>

        <?php if (!empty($message) && $page !== 'request_success' && $page !== 'order_success'): ?>
             <div class="alert alert-success">
                <i class="ph ph-check-circle" style="font-size: 20px;"></i>
                <div><?= htmlspecialchars($message) ?></div>
            </div>
        <?php endif; ?>

        <?php
        // ==========================================
        // PAGE: HOME
        // ==========================================
        if ($page === 'home'):
        ?>
            <section class="hero">
                <div class="hero-content">
                    <h1>The Art of Fine Stationery</h1>
                    <p>Discover our curated collection of premium journals, exquisite writing instruments, and elegant desk accessories designed to inspire your thoughts.</p>
                    <div style="display: flex; gap: 16px; justify-content: center; flex-wrap: wrap;">
                        <a href="index.php?p=shop" class="btn btn-primary">Shop the Collection</a>
                        <?php if(!$isLoggedIn): ?>
                            <a href="index.php?p=wholesaler-request" class="btn btn-outline">Corporate Accounts</a>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <section>
                <h2 class="section-title">Curated Categories</h2>
                <div class="categories-grid">
                    <?php 
                    // Fallback icons for stationery if categories exist
                    $icons = ['ph-book-open', 'ph-pen-nib', 'ph-briefcase', 'ph-envelope-simple-open'];
                    $i = 0;
                    foreach(array_slice($categories, 0, 4) as $cat): 
                        $iconClass = $icons[$i % count($icons)];
                        $i++;
                    ?>
                        <a href="index.php?p=shop&category=<?= $cat->id ?>" class="cat-card">
                            <div class="cat-icon"><i class="ph <?= $iconClass ?>"></i></div>
                            <h3 class="cat-name"><?= htmlspecialchars($cat->name) ?></h3>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>

        <?php
        // ==========================================
        // PAGE: SHOP
        // ==========================================
        elseif ($page === 'shop'):
            $catFilter = $_GET['category'] ?? null;
            $search = $_GET['q'] ?? '';

            $sql = "SELECT * FROM items WHERE status = 'active'";
            $params = [];
            if ($catFilter) {
                $sql .= " AND category_id = :cat";
                $params[':cat'] = $catFilter;
            }
            if (!empty($search)) {
                $sql .= " AND (name LIKE :search OR item_code LIKE :search)";
                $params[':search'] = '%' . $search . '%';
            }
            $sql .= " ORDER BY name ASC";

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $products = $stmt->fetchAll();
        ?>
            <div class="shop-header">
                <h1 style="font-size: 32px;">Our Collection</h1>
                <form action="index.php" method="GET" class="search-bar">
                    <input type="hidden" name="p" value="shop">
                    <?php if($catFilter): ?>
                        <input type="hidden" name="category" value="<?= $catFilter ?>">
                    <?php endif; ?>
                    <input type="text" name="q" class="form-control" placeholder="Search fine items..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" aria-label="Search"><i class="ph ph-magnifying-glass"></i></button>
                </form>
            </div>

            <div class="shop-container">
                <!-- Sidebar Filters -->
                <aside class="sidebar">
                    <h4 class="sidebar-title">Categories</h4>
                    <ul class="filter-list">
                        <li>
                            <a href="index.php?p=shop" class="<?= !$catFilter ? 'active' : '' ?>">
                                All Items
                            </a>
                        </li>
                        <?php foreach($categories as $cat): ?>
                            <li>
                                <a href="index.php?p=shop&category=<?= $cat->id ?>" class="<?= $catFilter == $cat->id ? 'active' : '' ?>">
                                    <?= htmlspecialchars($cat->name) ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </aside>

                <!-- Products Grid -->
                <main>
                    <div style="margin-bottom: 24px; color: var(--text-muted); font-size: 14px;">
                        Showing <?= count($products) ?> items
                    </div>
                    
                    <div class="product-grid">
                        <?php if(empty($products)): ?>
                            <div style="grid-column: 1/-1; text-align: center; padding: 64px 0; color: var(--text-muted);">
                                <i class="ph-light ph-wind" style="font-size: 48px; margin-bottom: 16px;"></i>
                                <p>No pieces found matching your criteria.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach($products as $prod): ?>
                                <a href="index.php?p=product&id=<?= $prod->id ?>" class="product-card">
                                    <div class="product-img-wrapper">
                                        <?php if(!empty($prod->image_path)): ?>
                                            <?php
                                                $imgPath = $prod->image_path;
                                                $erpUrl = getErpBaseUrl();
                                                $imgSrc = (strpos($imgPath, 'public/') === 0) 
                                                    ? $erpUrl . substr($imgPath, 7) 
                                                    : $erpUrl . 'uploads/products/' . $imgPath;
                                            ?>
                                            <img src="<?= $imgSrc ?>" alt="<?= htmlspecialchars($prod->name) ?>" loading="lazy">
                                        <?php else: ?>
                                            <i class="ph-light ph-image empty-img"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="product-brand"><?= htmlspecialchars($prod->brand ?? 'Lumière Exclusive') ?></div>
                                    <h3 class="product-title"><?= htmlspecialchars($prod->name) ?></h3>
                                    
                                    <div class="product-footer">
                                        <div class="product-price">Rs. <?= number_format(getItemPrice($prod, $userRole), 2) ?></div>
                                        <div class="stock-indicator <?= $prod->qty > 0 ? 'in-stock' : 'out-stock' ?>">
                                            <?= $prod->qty > 0 ? 'Available' : 'Sold Out' ?>
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </main>
            </div>

        <?php
        // ==========================================
        // PAGE: PRODUCT DETAILS
        // ==========================================
        elseif ($page === 'product'):
            $prodId = intval($_GET['id'] ?? 0);
            $stmt = $db->prepare("SELECT * FROM items WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => $prodId]);
            $product = $stmt->fetch();

            if (!$product) {
                echo "<div style='padding: 100px 0; text-align: center;'><h2>Product not found</h2></div>";
            } else {
                $varsList = json_decode($product->variations_json ?? '[]', true);
        ?>
            <div class="detail-layout">
                <!-- Gallery Left -->
                <div class="detail-gallery">
                    <?php if(!empty($product->image_path)): ?>
                        <?php
                            $imgPath = $product->image_path;
                            $erpUrl = getErpBaseUrl();
                            $imgSrc = (strpos($imgPath, 'public/') === 0) 
                                ? $erpUrl . substr($imgPath, 7) 
                                : $erpUrl . 'uploads/products/' . $imgPath;
                        ?>
                        <img src="<?= $imgSrc ?>" alt="<?= htmlspecialchars($product->name) ?>">
                    <?php else: ?>
                        <i class="ph-light ph-image empty-img"></i>
                    <?php endif; ?>
                </div>

                <!-- Info Right -->
                <div class="detail-info">
                    <span class="detail-brand"><?= htmlspecialchars($product->brand ?? 'Lumière Exclusive') ?></span>
                    <h1 class="detail-title"><?= htmlspecialchars($product->name) ?></h1>
                    
                    <div class="detail-price-wrap">
                        <div class="detail-price" id="priceDisplay">
                            Rs. <?= number_format(getItemPrice($product, $userRole), 2) ?>
                        </div>
                        <div style="font-size: 13px; color: var(--text-muted);">
                            <?php if($userRole === 'wholesaler'): ?>
                                Corporate Partner Pricing Applied
                            <?php else: ?>
                                Standard Pricing
                            <?php endif; ?>
                            &bull; 
                            <span class="<?= $product->qty > 0 ? 'in-stock' : 'out-stock' ?>" style="color: inherit;">
                                <?= $product->qty > 0 ? "In Stock ({$product->qty})" : 'Out of Stock' ?>
                            </span>
                        </div>
                    </div>

                    <div class="detail-desc">
                        <?= nl2br(htmlspecialchars($product->description ?? 'An exquisite addition to your collection. Refined materials and careful craftsmanship define this piece.')) ?>
                    </div>

                    <form action="index.php?p=product&id=<?= $product->id ?>" method="POST" class="detail-form">
                        <input type="hidden" name="action" value="add_to_cart">
                        <input type="hidden" name="item_id" value="<?= $product->id ?>">
                        
                        <!-- Variation Overrides (Required for Backend Logic) -->
                        <input type="hidden" name="variation_sku" id="varSku">
                        <input type="hidden" name="variation_price" id="varPrice">
                        <input type="hidden" name="variation_wholesale_price" id="varWholesalePrice">
                        <input type="hidden" name="variation_attribute" id="varAttr">

                        <?php if(!empty($varsList)): ?>
                            <div class="form-group">
                                <label class="form-label" for="variationSelect">Select Specification</label>
                                <select id="variationSelect" class="form-control" onchange="updateVariation()">
                                    <option value="" data-price="<?= $product->price ?>" data-wholesale="<?= $product->wholesale_price ?>" data-sku="<?= $product->item_code ?>" data-qty="<?= $product->qty ?>">Standard Configuration</option>
                                    <?php foreach($varsList as $v): ?>
                                        <option value="<?= htmlspecialchars($v['sku']) ?>" 
                                                data-sku="<?= htmlspecialchars($v['sku']) ?>" 
                                                data-price="<?= $v['price'] ?>" 
                                                data-wholesale="<?= $v['wholesale_price'] ?>"
                                                data-attr="<?= htmlspecialchars($v['attribute']) ?>"
                                                data-qty="10"> 
                                            <?= htmlspecialchars($v['attribute']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>

                        <div class="qty-row">
                            <div style="width: 120px;">
                                <label class="form-label" for="qtyInput">Quantity</label>
                                <input type="number" name="qty" id="qtyInput" class="form-control" value="1" min="1" max="<?= max(1, $product->qty) ?>" style="text-align: center;">
                            </div>
                            <button type="submit" class="btn btn-primary" style="flex-grow: 1; height: 50px;" <?= ($product->qty <= 0) ? 'disabled' : '' ?>>
                                Add to Bag <i class="ph ph-shopping-bag"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <script>
                function updateVariation() {
                    const sel = document.getElementById('variationSelect');
                    if(!sel) return;
                    
                    const opt = sel.options[sel.selectedIndex];
                    const sku = opt.getAttribute('data-sku');
                    const price = parseFloat(opt.getAttribute('data-price'));
                    const wholesale = parseFloat(opt.getAttribute('data-wholesale'));
                    const attr = opt.getAttribute('data-attr') || '';
                    const role = "<?= $userRole ?>";

                    document.getElementById('varSku').value = sku;
                    document.getElementById('varPrice').value = price;
                    document.getElementById('varWholesalePrice').value = wholesale;
                    document.getElementById('varAttr').value = attr;

                    const selectedPrice = (role === 'wholesaler') ? wholesale : price;
                    document.getElementById('priceDisplay').textContent = 'Rs. ' + selectedPrice.toFixed(2);
                }
            </script>
        <?php
            }

        // ==========================================
        // PAGE: LOGIN
        // ==========================================
        elseif ($page === 'login'):
        ?>
            <div class="auth-container">
                <h2>Welcome Back</h2>
                <form action="index.php?p=login" method="POST">
                    <input type="hidden" name="action" value="login">
                    
                    <div class="form-group">
                        <label class="form-label">Email or Username</label>
                        <input type="text" name="username_or_email" class="form-control" required placeholder="Enter your credentials">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required placeholder="••••••••">
                    </div>

                    <button type="submit" class="btn btn-primary" style="width:100%; padding: 14px;">Sign In</button>
                </form>
                <div class="auth-footer">
                    <p>New to Lumière? <a href="index.php?p=register-retail">Create an Account</a></p>
                    <p style="margin-top: 12px;">Interested in Corporate Gifting? <a href="index.php?p=wholesaler-request">Apply Here</a></p>
                </div>
            </div>

        <?php
        // ==========================================
        // PAGE: REGISTER RETAIL
        // ==========================================
        elseif ($page === 'register-retail'):
        ?>
            <div class="auth-container" style="max-width: 600px;">
                <h2>Become a Member</h2>
                <form action="index.php?p=register-retail" method="POST">
                    <input type="hidden" name="action" value="register_retail">

                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control">
                        </div>
                        <div class="form-group">
                            <label class="form-label">City</label>
                            <input type="text" name="city" class="form-control">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Delivery Address</label>
                        <textarea name="address" class="form-control" rows="2"></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width:100%; padding: 14px;">Create Account</button>
                </form>
                <div class="auth-footer">
                    <p>Already a member? <a href="index.php?p=login">Sign In</a></p>
                </div>
            </div>

        <?php
        // ==========================================
        // PAGE: WHOLESALER REQUEST
        // ==========================================
        elseif ($page === 'wholesaler-request'):
        ?>
            <div class="auth-container" style="max-width: 700px;">
                <h2>Corporate Partner Application</h2>
                <p style="text-align: center; color: var(--text-muted); margin-bottom: 32px; font-size: 14px;">
                    Apply for an exclusive corporate account to access volume pricing on premium journals, pens, and desk accessories for your business or corporate gifting needs.
                </p>
                
                <form action="index.php?p=wholesaler-request" method="POST">
                    <input type="hidden" name="action" value="submit_wholesaler_request">

                    <div class="form-group">
                        <label class="form-label">Company Name *</label>
                        <input type="text" name="business_name" class="form-control" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Contact Number *</label>
                            <input type="text" name="contact_number" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Business Email *</label>
                            <input type="email" name="email_address" class="form-control" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Requested Username *</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Account Password *</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Corporate City *</label>
                        <input type="text" name="city" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Registered Address *</label>
                        <textarea name="address" class="form-control" rows="3" required></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Additional Requirements / Notes</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Tell us about your corporate gifting needs..."></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width:100%; padding: 14px;">Submit Application</button>
                </form>
            </div>

        <?php
        // ==========================================
        // PAGE: REQUEST SUCCESS
        // ==========================================
        elseif ($page === 'request_success'):
        ?>
            <div class="success-state">
                <i class="ph-light ph-check-circle success-icon"></i>
                <h2>Application Received</h2>
                <p style="color: var(--text-muted); margin: 16px 0 32px;"><?= $message ?></p>
                <a href="index.php?p=shop" class="btn btn-primary">Return to Collection</a>
            </div>

        <?php
        // ==========================================
        // PAGE: ORDER SUCCESS
        // ==========================================
        elseif ($page === 'order_success'):
        ?>
            <div class="success-state">
                <i class="ph-light ph-package success-icon"></i>
                <h2>Order Confirmed</h2>
                <p style="color: var(--text-muted); margin: 16px 0 32px;"><?= $message ?></p>
                <a href="index.php?p=shop" class="btn btn-primary">Continue Shopping</a>
            </div>

        <?php
        // ==========================================
        // PAGE: CART
        // ==========================================
        elseif ($page === 'cart'):
        ?>
            <div class="page-header">
                <h1>Your Shopping Bag</h1>
            </div>

            <?php if(empty($_SESSION['ec_cart'])): ?>
                <div style="text-align: center; padding: 64px 20px; background: var(--bg-card); border-radius: var(--radius-sm); border: 1px solid var(--border-light);">
                    <i class="ph-light ph-shopping-bag" style="font-size: 64px; color: var(--text-muted); margin-bottom: 24px;"></i>
                    <h3 style="margin-bottom: 16px;">Your bag is currently empty</h3>
                    <p style="color: var(--text-muted); margin-bottom: 32px;">Discover our collection of fine stationery and writing instruments.</p>
                    <a href="index.php?p=shop" class="btn btn-primary">Explore Collection</a>
                </div>
            <?php else: ?>
                <form action="index.php?p=cart" method="POST">
                    <input type="hidden" name="action" value="update_cart">
                    
                    <div class="cart-table-wrap">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Item Details</th>
                                    <th>SKU</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th style="text-align: right;">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $subtotal = 0;
                                foreach($_SESSION['ec_cart'] as $k => $item): 
                                    $itemSub = $item['price'] * $item['qty'];
                                    $subtotal += $itemSub;
                                ?>
                                    <tr>
                                        <td data-label="Item Details">
                                            <div class="cart-item-name"><?= htmlspecialchars($item['name']) ?></div>
                                            <?php if(!empty($item['attribute'])): ?>
                                                <div class="cart-item-meta">Specification: <?= htmlspecialchars($item['attribute']) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td data-label="SKU" class="cart-item-meta"><?= htmlspecialchars($item['sku']) ?></td>
                                        <td data-label="Price">Rs. <?= number_format($item['price'], 2) ?></td>
                                        <td data-label="Quantity">
                                            <input type="number" name="qty[<?= $k ?>]" value="<?= $item['qty'] ?>" min="0" class="form-control cart-qty-input">
                                        </td>
                                        <td data-label="Total" style="text-align: right; font-weight: 600;">Rs. <?= number_format($itemSub, 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="cart-summary">
                        <button type="submit" class="btn btn-outline"><i class="ph ph-arrows-clockwise"></i> Update Bag</button>
                        <div style="text-align: right;">
                            <div style="font-size: 14px; color: var(--text-muted); margin-bottom: 4px; text-transform: uppercase; letter-spacing: 1px;">Subtotal</div>
                            <div class="summary-total">Rs. <?= number_format($subtotal, 2) ?></div>
                        </div>
                    </div>

                    <div style="display: flex; justify-content: flex-end; margin-bottom: 80px;">
                        <a href="index.php?p=checkout" class="btn btn-primary" style="padding: 16px 40px; font-size: 16px;">
                            Proceed to Checkout <i class="ph ph-arrow-right"></i>
                        </a>
                    </div>
                </form>
            <?php endif; ?>

        <?php
        // ==========================================
        // PAGE: CHECKOUT
        // ==========================================
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
            <div class="page-header">
                <h1>Secure Checkout</h1>
            </div>

            <div class="checkout-layout">
                <!-- Shipping Form Left -->
                <div class="checkout-box">
                    <h3>Delivery Details</h3>
                    
                    <form action="index.php?p=checkout" method="POST" id="checkoutForm">
                        <input type="hidden" name="action" value="submit_order">

                        <?php if ($userRole === 'wholesaler'): ?>
                            <div class="alert alert-success">
                                <i class="ph ph-briefcase" style="font-size: 20px;"></i>
                                <div>Corporate billing profile active. Order will be recorded under: <strong><?= htmlspecialchars($userName) ?></strong></div>
                            </div>
                        <?php else: ?>
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Recipient Full Name *</label>
                                    <input type="text" name="billing_name" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Contact Number *</label>
                                    <input type="text" name="billing_phone" class="form-control" required>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="form-group">
                            <label class="form-label">Delivery Address *</label>
                            <textarea name="shipping_address" class="form-control" rows="4" required placeholder="Street, City, Postal Code..."></textarea>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Special Instructions (Optional)</label>
                            <textarea name="shipping_notes" class="form-control" rows="2" placeholder="E.g., leave at front desk, wrap as gift..."></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 16px; margin-top: 16px; font-size: 16px;">
                            Place Order
                        </button>
                    </form>
                </div>

                <!-- Summary Right -->
                <div class="checkout-box" style="position: sticky; top: calc(var(--header-height) + 40px); background: var(--bg-subtle);">
                    <h3>Order Summary</h3>
                    
                    <div style="display: flex; flex-direction: column; gap: 16px; margin-bottom: 24px; max-height: 40vh; overflow-y: auto; padding-right: 8px;">
                        <?php foreach($_SESSION['ec_cart'] as $item): ?>
                            <div class="summary-item">
                                <div class="summary-item-title">
                                    <span style="color: var(--text-main); font-weight: 500;"><?= $item['qty'] ?>x</span> 
                                    <?= htmlspecialchars($item['name']) ?>
                                    <?php if(!empty($item['attribute'])): ?>
                                        <div style="font-size: 12px; margin-top: 4px;"><?= htmlspecialchars($item['attribute']) ?></div>
                                    <?php endif; ?>
                                </div>
                                <div style="font-weight: 500; white-space: nowrap;">
                                    Rs. <?= number_format($item['price'] * $item['qty'], 2) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div style="border-top: 1px solid var(--border-strong); padding-top: 24px; display: flex; justify-content: space-between; align-items: baseline;">
                        <span style="font-size: 14px; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted);">Total</span>
                        <span style="font-family: var(--font-heading); font-size: 28px; font-weight: 500;">Rs. <?= number_format($subtotal, 2) ?></span>
                    </div>
                </div>
            </div>

        <?php endif; ?>

    </main>

    <footer>
        <div class="container">
            <div style="font-family: var(--font-heading); font-size: 20px; color: var(--text-main); margin-bottom: 16px;">
                <i class="ph-light ph-feather" style="color: var(--brand-accent);"></i> Lumière
            </div>
            <p>&copy; <?= date('Y') ?> Lumière Stationery Collection. All rights reserved.</p>
        </div>
    </footer>

    <script>
        // Light / Dark Theme Logic
        function updateThemeIcons(theme) {
            const iconDesktop = document.getElementById('theme-icon');
            const iconClass = (theme === 'dark') ? 'ph ph-sun' : 'ph ph-moon';
            if (iconDesktop) iconDesktop.className = iconClass;
        }

        function toggleTheme() {
            const body = document.body;
            const currentTheme = body.getAttribute('data-theme');
            const newTheme = (currentTheme === 'light') ? 'dark' : 'light';
            
            body.setAttribute('data-theme', newTheme);
            localStorage.setItem('ec-theme', newTheme);
            updateThemeIcons(newTheme);
        }

        window.addEventListener('DOMContentLoaded', () => {
            const savedTheme = localStorage.getItem('ec-theme') || 'light';
            document.body.setAttribute('data-theme', savedTheme);
            updateThemeIcons(savedTheme);
        });

        // Mobile Drawer Logic
        function toggleMenu() {
            const drawer = document.getElementById('mobileDrawer');
            const overlay = document.getElementById('drawerOverlay');
            if (drawer && overlay) {
                drawer.classList.toggle('active');
                overlay.classList.toggle('active');
            }
        }
    </script>
</body>
</html>
<?php
// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}
?>