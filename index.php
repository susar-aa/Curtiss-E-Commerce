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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Curtiss Storefront - Premium E-Commerce</title>
    <!-- Outfit Font & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        :root {
            /* Palette: Modern HSL Tailored Colors */
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --primary-rgb: 79, 70, 229;
            --primary-light: rgba(79, 70, 229, 0.08);
            
            --secondary: #0ea5e9;
            --secondary-hover: #0284c7;
            
            --bg-site: #f8fafc;
            --card-bg: #ffffff;
            --card-bg-rgb: 255, 255, 255;
            
            --text-dark: #0f172a;
            --text-muted: #64748b;
            
            --border-color: #e2e8f0;
            --border-hover: #cbd5e1;
            
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.05), 0 1px 2px rgba(0, 0, 0, 0.03);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.03);
            --shadow-premium: 0 20px 25px -5px rgba(0, 0, 0, 0.05), 0 10px 10px -5px rgba(0, 0, 0, 0.02);
            
            --rounded: 12px;
            --rounded-sm: 8px;
            --rounded-lg: 20px;
            --rounded-full: 9999px;
            
            --font-main: 'Outfit', sans-serif;
            --header-height: 72px;
            
            --badge-success-bg: #dcfce7;
            --badge-success-text: #166534;
            --badge-danger-bg: #fee2e2;
            --badge-danger-text: #991b1b;
        }

        [data-theme="dark"] {
            --bg-site: #09090b;
            --card-bg: #18181b;
            --card-bg-rgb: 24, 24, 27;
            
            --text-dark: #f4f4f5;
            --text-muted: #a1a1aa;
            
            --border-color: #27272a;
            --border-hover: #3f3f46;
            
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.3);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.25);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.25);
            --shadow-premium: 0 20px 25px -5px rgba(0, 0, 0, 0.3);
            
            --badge-success-bg: rgba(22, 101, 52, 0.2);
            --badge-success-text: #4ade80;
            --badge-danger-bg: rgba(153, 27, 27, 0.2);
            --badge-danger-text: #f87171;
            
            --primary-light: rgba(79, 70, 229, 0.15);
        }

        /* Base & Resets */
        * {
            box-sizing: border-box;
            outline-color: var(--primary);
        }

        body {
            font-family: var(--font-main);
            background-color: var(--bg-site);
            color: var(--text-dark);
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            text-rendering: optimizeLegibility;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            transition: background-color 0.3s, color 0.3s, border-color 0.3s;
        }

        /* Layout Container */
        .container {
            width: 100%;
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 24px;
        }

        @media (max-width: 640px) {
            .container {
                padding: 0 16px;
            }
        }

        /* Header & Glassmorphic Navigation */
        header {
            background-color: rgba(var(--card-bg-rgb), 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border-color);
            position: sticky;
            top: 0;
            z-index: 1000;
            height: var(--header-height);
            display: flex;
            align-items: center;
            transition: background-color 0.3s, border-color 0.3s;
        }

        .navbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
        }

        .brand-logo {
            font-size: 24px;
            font-weight: 800;
            color: var(--primary);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            letter-spacing: -0.75px;
            transition: transform 0.2s ease;
        }

        .brand-logo:hover {
            transform: scale(1.02);
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 24px;
        }

        .nav-links a {
            color: var(--text-dark);
            text-decoration: none;
            font-weight: 500;
            font-size: 15px;
            position: relative;
            padding: 4px 0;
            transition: color 0.2s ease;
        }

        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background-color: var(--primary);
            transition: width 0.2s ease;
        }

        .nav-links a:hover::after, .nav-links a.active::after {
            width: 100%;
        }

        .nav-links a:hover, .nav-links a.active {
            color: var(--primary);
        }

        /* Action Buttons & Badges */
        .cart-btn {
            display: inline-flex;
            align-items: center;
            padding: 8px 18px;
            background-color: var(--primary-light);
            border-radius: var(--rounded-full);
            color: var(--primary);
            font-weight: 600;
            font-size: 14px;
            text-decoration: none;
            gap: 8px;
            transition: all 0.2s ease;
        }

        .cart-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.15);
        }

        .cart-badge {
            background-color: #ef4444;
            color: #ffffff;
            font-size: 11px;
            font-weight: 700;
            border-radius: var(--rounded-full);
            padding: 2px 7px;
            min-width: 18px;
            text-align: center;
            display: inline-block;
        }

        .theme-toggle {
            background: none;
            border: 1px solid var(--border-color);
            border-radius: var(--rounded-sm);
            cursor: pointer;
            width: 38px;
            height: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-dark);
            transition: all 0.2s ease;
        }

        .theme-toggle:hover {
            background-color: var(--border-color);
            transform: scale(1.05);
        }

        /* Mobile Hamburger & Menu */
        .menu-toggle {
            display: none;
            background: none;
            border: 1px solid var(--border-color);
            border-radius: var(--rounded-sm);
            cursor: pointer;
            width: 38px;
            height: 38px;
            align-items: center;
            justify-content: center;
            color: var(--text-dark);
            font-size: 20px;
            transition: all 0.2s ease;
        }

        .menu-toggle:hover {
            background-color: var(--border-color);
        }

        /* Mobile Menu Drawer */
        .mobile-drawer-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background-color: rgba(0, 0, 0, 0.4);
            z-index: 1999;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
            backdrop-filter: blur(4px);
        }

        .mobile-drawer-overlay.open {
            opacity: 1;
            pointer-events: auto;
        }

        .mobile-drawer {
            position: fixed;
            top: 0;
            right: -320px;
            width: 300px;
            height: 100vh;
            background-color: var(--card-bg);
            z-index: 2000;
            box-shadow: var(--shadow-premium);
            transition: right 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            padding: 24px;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .mobile-drawer.open {
            right: 0;
        }

        .drawer-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 16px;
        }

        .close-drawer {
            background: none;
            border: none;
            font-size: 22px;
            cursor: pointer;
            color: var(--text-muted);
            transition: color 0.2s;
        }

        .close-drawer:hover {
            color: var(--text-dark);
        }

        .drawer-content {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .drawer-content a {
            color: var(--text-dark);
            text-decoration: none;
            font-size: 16px;
            font-weight: 500;
            padding: 10px 14px;
            border-radius: var(--rounded-sm);
            transition: all 0.2s;
        }

        .drawer-content a:hover, .drawer-content a.active {
            background-color: var(--primary-light);
            color: var(--primary);
            padding-left: 18px;
        }

        .drawer-user-info {
            padding: 12px 14px;
            background-color: var(--bg-site);
            border-radius: var(--rounded-sm);
            font-size: 14px;
        }

        /* Buttons styling */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background-color: var(--primary);
            color: #ffffff;
            padding: 12px 24px;
            border-radius: var(--rounded);
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            border: 1px solid transparent;
            cursor: pointer;
            font-size: 15px;
            box-shadow: 0 4px 10px rgba(79, 70, 229, 0.15);
        }

        .btn:hover {
            background-color: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(79, 70, 229, 0.25);
        }

        .btn:active {
            transform: translateY(0);
        }

        .btn-outline {
            background-color: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
            box-shadow: none;
        }

        .btn-outline:hover {
            background-color: var(--primary-light);
            color: var(--primary-hover);
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.05);
        }

        .btn:disabled, .btn-outline:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none !important;
            box-shadow: none !important;
        }

        /* Hero Banner */
        .hero {
            padding: 80px 24px;
            background: linear-gradient(135deg, rgba(79, 70, 229, 0.05) 0%, rgba(14, 165, 233, 0.05) 100%);
            border-radius: var(--rounded-lg);
            border: 1px solid var(--border-color);
            text-align: center;
            margin-top: 24px;
            margin-bottom: 48px;
            box-shadow: var(--shadow-sm);
        }

        .hero h1 {
            font-size: 44px;
            font-weight: 800;
            margin: 0 0 16px 0;
            letter-spacing: -1.25px;
            line-height: 1.15;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero p {
            font-size: 17px;
            color: var(--text-muted);
            max-width: 600px;
            margin: 0 auto 32px auto;
            line-height: 1.6;
        }

        .hero-actions {
            display: flex;
            justify-content: center;
            gap: 16px;
            flex-wrap: wrap;
        }

        /* Featured Categories */
        .featured-section {
            margin-bottom: 56px;
        }

        .featured-title {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 24px;
            letter-spacing: -0.5px;
        }

        .categories-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }

        .category-card {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: var(--rounded);
            padding: 28px 20px;
            text-align: center;
            box-shadow: var(--shadow-sm);
            transition: all 0.25s ease;
            text-decoration: none;
            color: inherit;
        }

        .category-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-md);
            border-color: var(--primary);
        }

        .category-icon {
            font-size: 36px;
            color: var(--primary);
            margin-bottom: 12px;
            transition: transform 0.2s ease;
        }

        .category-card:hover .category-icon {
            transform: scale(1.1);
        }

        .category-name {
            margin: 0;
            font-size: 15px;
            font-weight: 600;
            letter-spacing: -0.25px;
        }

        /* Shop Layout (Filters + Grid) */
        .shop-layout {
            display: grid;
            grid-template-columns: 260px 1fr;
            gap: 32px;
            margin-top: 24px;
            margin-bottom: 56px;
            align-items: start;
        }

        .filters-card {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: var(--rounded);
            padding: 24px;
            position: sticky;
            top: calc(var(--header-height) + 24px);
            box-shadow: var(--shadow-sm);
            transition: background-color 0.3s, border-color 0.3s;
        }

        .filter-section h4 {
            margin: 0 0 16px 0;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-muted);
            font-weight: 700;
        }

        .filter-list {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .filter-list a {
            color: var(--text-dark);
            text-decoration: none;
            font-size: 14.5px;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 12px;
            border-radius: var(--rounded-sm);
            transition: all 0.2s ease;
            font-weight: 500;
        }

        .filter-list a:hover {
            background-color: var(--bg-site);
            color: var(--primary);
        }

        .filter-list a.active {
            background-color: var(--primary-light);
            color: var(--primary);
            font-weight: 600;
        }

        /* Products Grid */
        .products-grid-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .products-grid-title {
            margin: 0;
            font-size: 22px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 24px;
        }

        .prod-card {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: var(--rounded);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            display: flex;
            flex-direction: column;
            text-decoration: none;
            color: inherit;
            height: 100%;
        }

        .prod-card:hover {
            transform: translateY(-6px);
            box-shadow: var(--shadow-premium);
            border-color: var(--border-hover);
        }

        .prod-img {
            aspect-ratio: 1 / 1;
            width: 100%;
            height: auto;
            background-color: rgba(0,0,0,0.01);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
            border-bottom: 1px solid var(--border-color);
            padding: 16px;
        }

        .prod-img img {
            max-width: 90%;
            max-height: 90%;
            object-fit: contain;
            transition: transform 0.3s ease;
        }

        .prod-card:hover .prod-img img {
            transform: scale(1.06);
        }

        .prod-details {
            padding: 20px;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }

        .prod-brand {
            font-size: 11px;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .prod-details h3 {
            margin: 0 0 10px 0;
            font-size: 16px;
            font-weight: 600;
            line-height: 1.4;
            letter-spacing: -0.25px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            height: 44px; /* Fixed height for alignments */
        }

        .prod-status-row {
            margin-bottom: 16px;
        }

        .prod-price-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: auto;
            border-top: 1px solid var(--border-color);
            padding-top: 14px;
        }

        .price-badge {
            font-size: 18px;
            font-weight: 800;
            color: var(--primary);
            letter-spacing: -0.5px;
        }

        .role-indicator {
            font-size: 11px;
            background-color: var(--bg-site);
            color: var(--text-muted);
            padding: 4px 10px;
            border-radius: var(--rounded-full);
            font-weight: 600;
            border: 1px solid var(--border-color);
        }

        .role-indicator-wholesale {
            background-color: rgba(79, 70, 229, 0.1);
            color: var(--primary);
            border-color: rgba(79, 70, 229, 0.15);
        }

        .stock-badge {
            font-size: 11px;
            font-weight: 600;
            padding: 3px 10px;
            border-radius: var(--rounded-full);
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .stock-in {
            background-color: var(--badge-success-bg);
            color: var(--badge-success-text);
        }

        .stock-out {
            background-color: var(--badge-danger-bg);
            color: var(--badge-danger-text);
        }

        /* Search Form Layout */
        .search-bar-row {
            display: flex;
            gap: 12px;
            margin-top: 24px;
            margin-bottom: 32px;
            width: 100%;
        }

        .search-bar-row .form-control {
            max-width: 500px;
            flex-grow: 1;
        }

        /* Detail page redesign */
        .detail-grid {
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            gap: 48px;
            margin: 40px 0;
            align-items: start;
        }

        .detail-img {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: var(--rounded-lg);
            height: 480px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            padding: 24px;
            box-shadow: var(--shadow-sm);
        }

        .detail-img img {
            max-width: 95%;
            max-height: 95%;
            object-fit: contain;
        }

        .detail-content {
            padding: 8px 0;
        }

        .detail-brand {
            font-size: 12px;
            color: var(--primary);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            display: block;
            margin-bottom: 8px;
        }

        .detail-title {
            font-size: 32px;
            font-weight: 800;
            margin: 0 0 16px 0;
            letter-spacing: -0.75px;
            line-height: 1.2;
        }

        .detail-price {
            font-size: 26px;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 24px;
            letter-spacing: -0.75px;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .detail-price-type {
            font-size: 13px;
            font-weight: 500;
            color: var(--text-muted);
        }

        .detail-description {
            color: var(--text-muted);
            line-height: 1.6;
            margin-bottom: 32px;
            font-size: 15px;
        }

        .detail-form {
            border-top: 1px solid var(--border-color);
            padding-top: 32px;
        }

        .action-row {
            display: flex;
            gap: 16px;
            align-items: flex-end;
            margin-top: 16px;
        }

        /* Cart Page Table & Grid */
        .cart-title {
            font-size: 28px;
            font-weight: 800;
            margin-top: 32px;
            margin-bottom: 24px;
            letter-spacing: -0.5px;
        }

        .cart-wrapper {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: var(--rounded-lg);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            margin-bottom: 32px;
        }

        .cart-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 15px;
            text-align: left;
        }

        .cart-table th {
            padding: 16px 24px;
            font-weight: 600;
            background-color: rgba(0, 0, 0, 0.015);
            color: var(--text-muted);
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid var(--border-color);
        }

        .cart-table td {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border-color);
            vertical-align: middle;
        }

        .cart-table tr:last-child td {
            border-bottom: none;
        }

        .cart-product-title {
            font-weight: 600;
            color: var(--text-dark);
            font-size: 15px;
        }

        .cart-product-option {
            font-size: 12px;
            color: var(--text-muted);
            display: block;
            margin-top: 4px;
        }

        .cart-sku {
            font-family: monospace;
            background-color: var(--bg-site);
            padding: 2px 8px;
            border-radius: var(--rounded-sm);
            font-size: 13px;
        }

        .cart-qty-input {
            width: 80px !important;
            text-align: center;
            padding: 6px 10px;
        }

        .cart-total-row {
            background-color: rgba(0, 0, 0, 0.01);
        }

        .cart-total-label {
            font-weight: 600;
            font-size: 16px;
            text-align: right;
            padding-right: 24px;
        }

        .cart-total-value {
            font-weight: 800;
            font-size: 22px;
            color: var(--primary);
            text-align: right;
            letter-spacing: -0.5px;
        }

        .cart-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 56px;
            gap: 16px;
            flex-wrap: wrap;
        }

        /* Empty Cart State */
        .empty-state {
            background-color: var(--card-bg);
            border: 1px dashed var(--border-color);
            border-radius: var(--rounded-lg);
            padding: 64px 32px;
            text-align: center;
            box-shadow: var(--shadow-sm);
            max-width: 500px;
            margin: 48px auto;
        }

        .empty-icon {
            font-size: 56px;
            color: var(--text-muted);
            margin-bottom: 16px;
        }

        .empty-state p {
            color: var(--text-muted);
            font-size: 16px;
            margin-bottom: 24px;
        }

        /* Checkout page layout */
        .checkout-grid {
            display: grid;
            grid-template-columns: 1fr 380px;
            gap: 32px;
            margin-top: 32px;
            margin-bottom: 56px;
            align-items: start;
        }

        .checkout-card {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: var(--rounded-lg);
            padding: 32px;
            box-shadow: var(--shadow-sm);
        }

        .checkout-title {
            margin-top: 0;
            font-weight: 800;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 16px;
            margin-bottom: 24px;
            font-size: 20px;
            letter-spacing: -0.5px;
        }

        .summary-card {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: var(--rounded-lg);
            padding: 24px;
            box-shadow: var(--shadow-sm);
            position: sticky;
            top: calc(var(--header-height) + 24px);
        }

        .summary-item-row {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            margin-bottom: 16px;
            gap: 12px;
        }

        .summary-item-name {
            color: var(--text-dark);
            line-height: 1.4;
        }

        .summary-grand-total {
            border-top: 1px dashed var(--border-color);
            padding-top: 16px;
            display: flex;
            justify-content: space-between;
            font-weight: 800;
            font-size: 18px;
            letter-spacing: -0.5px;
            margin-top: 16px;
        }

        /* Forms Elements */
        .card-form {
            max-width: 500px;
            margin: 48px auto;
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: var(--rounded-lg);
            padding: 40px;
            box-shadow: var(--shadow-premium);
        }

        .card-form-title {
            margin-top: 0;
            font-weight: 800;
            font-size: 24px;
            text-align: center;
            margin-bottom: 8px;
            letter-spacing: -0.75px;
        }

        .card-form-desc {
            text-align: center;
            color: var(--text-muted);
            font-size: 14px;
            margin-bottom: 32px;
            line-height: 1.5;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 13.5px;
            font-weight: 600;
            color: var(--text-dark);
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 1.5px solid var(--border-color);
            border-radius: var(--rounded-sm);
            background-color: var(--input-bg);
            color: var(--text-dark);
            box-sizing: border-box;
            font-size: 14.5px;
            font-family: var(--font-main);
            transition: all 0.2s ease;
        }

        .form-control:hover {
            border-color: var(--border-hover);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12);
        }

        .form-row {
            display: flex;
            gap: 16px;
        }

        @media (max-width: 640px) {
            .form-row {
                flex-direction: column;
                gap: 0;
            }
        }

        /* Success/Error Alerts */
        .alert-box {
            padding: 14px 20px;
            border-radius: var(--rounded);
            font-size: 14.5px;
            margin-bottom: 24px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            line-height: 1.5;
        }

        .alert-danger {
            background-color: var(--badge-danger-bg);
            color: var(--badge-danger-text);
            border: 1px solid rgba(239, 68, 68, 0.15);
        }

        .alert-success {
            background-color: var(--badge-success-bg);
            color: var(--badge-success-text);
            border: 1px solid rgba(34, 197, 94, 0.15);
        }

        /* Success State Cards */
        .card-success {
            text-align: center;
            padding: 48px 32px;
            max-width: 550px;
            margin: 48px auto;
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: var(--rounded-lg);
            box-shadow: var(--shadow-lg);
        }

        .success-icon {
            font-size: 64px;
            color: #10b981;
            margin-bottom: 16px;
        }

        /* Footer */
        footer {
            margin-top: auto;
            background-color: var(--card-bg);
            border-top: 1px solid var(--border-color);
            padding: 40px 0;
            text-align: center;
            color: var(--text-muted);
            font-size: 14.5px;
            transition: background-color 0.3s, border-color 0.3s;
        }

        footer p {
            margin: 0;
        }

        /* Responsive Breakpoints & Overrides */
        @media (max-width: 1024px) {
            .nav-links {
                display: none; /* Hide standard navbar link group */
            }

            .menu-toggle {
                display: flex; /* Show menu toggle */
            }

            .categories-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .shop-layout {
                grid-template-columns: 1fr;
                gap: 24px;
            }

            .filters-card {
                padding: 16px;
                border: none;
                background: transparent;
                box-shadow: none;
                margin-bottom: 8px;
                position: static;
            }

            .filter-section h4 {
                display: none;
            }

            .filter-list {
                flex-direction: row;
                overflow-x: auto;
                white-space: nowrap;
                padding: 8px 0;
                gap: 8px;
                scrollbar-width: none;
            }

            .filter-list::-webkit-scrollbar {
                display: none;
            }

            .filter-list li {
                display: inline-block;
            }

            .filter-list a {
                display: inline-flex;
                padding: 8px 16px;
                background-color: var(--card-bg);
                border: 1px solid var(--border-color);
                border-radius: 24px;
                font-size: 13px;
                box-shadow: var(--shadow-sm);
            }

            .filter-list a.active {
                background-color: var(--primary);
                color: #ffffff !important;
                border-color: var(--primary);
            }
        }

        @media (max-width: 768px) {
            .hero {
                padding: 64px 16px;
                margin-top: 16px;
                margin-bottom: 32px;
            }

            .hero h1 {
                font-size: 32px;
            }

            .hero p {
                font-size: 15px;
            }

            .categories-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 16px;
            }

            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
                gap: 16px;
            }

            .prod-details {
                padding: 14px;
            }

            .prod-details h3 {
                font-size: 14px;
                height: 40px;
            }

            .price-badge {
                font-size: 15px;
            }

            /* Cart Table Responsive View (Transforms table to list block) */
            .cart-table thead {
                display: none;
            }

            .cart-table tbody tr {
                display: flex;
                flex-direction: column;
                padding: 16px 20px;
                border-bottom: 1px solid var(--border-color);
                position: relative;
            }

            .cart-table td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 8px 0;
                border-bottom: none;
                width: 100% !important;
            }

            .cart-table td::before {
                content: attr(data-label);
                font-weight: 700;
                font-size: 13px;
                color: var(--text-muted);
                text-transform: uppercase;
            }

            .cart-table td:first-child {
                padding-top: 0;
            }

            .cart-qty-input {
                width: 100px !important;
            }

            .cart-total-row {
                display: flex !important;
                flex-direction: row !important;
                justify-content: space-between !important;
                padding: 20px !important;
            }

            .cart-total-row td {
                width: auto !important;
                padding: 0 !important;
                display: block !important;
            }

            .cart-total-row td::before {
                display: none;
            }

            .cart-total-label {
                text-align: left;
            }
        }

        @media (max-width: 480px) {
            .categories-grid {
                grid-template-columns: 1fr;
            }

            .products-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
            }

            .prod-img {
                padding: 8px;
            }

            .prod-details {
                padding: 10px;
            }

            .prod-details h3 {
                font-size: 13px;
                height: 36px;
                margin-bottom: 6px;
            }

            .price-badge {
                font-size: 14px;
            }

            .role-indicator {
                font-size: 9px;
                padding: 2px 6px;
            }

            .card-form {
                padding: 24px 16px;
                margin: 24px auto;
            }

            .detail-img {
                height: 280px;
            }

            .action-row {
                flex-direction: column;
                align-items: stretch;
            }

            .action-row .btn {
                width: 100%;
            }
        }

        /* Large & Ultra-wide screen improvements */
        @media (min-width: 1440px) {
            .container {
                max-width: 1400px;
            }
            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            }
        }

        @media (min-width: 1920px) {
            .container {
                max-width: 1680px;
            }
            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            }
        }

        .form-row {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
        }
        .form-row > .form-group {
            flex: 1;
            min-width: 220px;
            margin-bottom: 16px;
        }
    </style>
</head>
<body data-theme="light">

    <header>
        <div class="container navbar">
            <a href="index.php" class="brand-logo">
                <i class="ph-bold ph-shopping-bag-open"></i> CURTISS STORE
            </a>
            
            <div class="nav-links">
                <a href="index.php?p=home" class="<?= $page === 'home' ? 'active' : '' ?>">Home</a>
                <a href="index.php?p=shop" class="<?= $page === 'shop' ? 'active' : '' ?>">Shop</a>
                
                <?php if ($userRole === 'wholesaler'): ?>
                    <span class="role-indicator role-indicator-wholesale"><i class="ph ph-shield-check"></i> Wholesaler Portal</span>
                <?php elseif ($userRole === 'retail'): ?>
                    <span class="role-indicator"><i class="ph ph-user"></i> Retail Buyer</span>
                <?php else: ?>
                    <a href="index.php?p=wholesaler-request" class="<?= $page === 'wholesaler-request' ? 'active' : '' ?>">Wholesale Request</a>
                <?php endif; ?>

                <?php if ($isLoggedIn): ?>
                    <span style="font-weight:600; font-size:14px;"><i class="ph ph-user"></i> <?= htmlspecialchars($userName) ?></span>
                    <a href="index.php?logout=1" style="color:#ff3b30; font-size: 13px;">Logout</a>
                <?php else: ?>
                    <a href="index.php?p=login">Login</a>
                    <a href="index.php?p=register-retail" class="btn" style="padding: 6px 14px; font-size: 13px; box-shadow: none;">Register</a>
                <?php endif; ?>

                <a href="index.php?p=cart" class="cart-btn">
                    <i class="ph ph-shopping-cart"></i> Cart
                    <span class="cart-badge"><?= count($_SESSION['ec_cart']) ?></span>
                </a>

                <button class="theme-toggle" onclick="toggleTheme()" aria-label="Toggle Theme">
                    <i class="ph ph-sun" id="theme-icon"></i>
                </button>
            </div>

            <!-- Mobile Controls -->
            <div style="display: flex; align-items: center; gap: 8px;">
                <!-- Theme toggle and Cart always accessible on mobile too -->
                <a href="index.php?p=cart" class="cart-btn" style="padding: 8px 12px; font-size: 13px; display: inline-flex; border-radius: var(--rounded-full);">
                    <i class="ph ph-shopping-cart"></i>
                    <span class="cart-badge" style="margin-left: 2px;"><?= count($_SESSION['ec_cart']) ?></span>
                </a>
                <button class="theme-toggle" onclick="toggleTheme()" aria-label="Toggle Theme">
                    <i class="ph ph-sun" id="theme-icon-mobile"></i>
                </button>
                <button class="menu-toggle" onclick="toggleMenu()" aria-label="Open Navigation Menu">
                    <i class="ph ph-list"></i>
                </button>
            </div>
        </div>
    </header>

    <!-- Mobile Drawer Navigation -->
    <div class="mobile-drawer-overlay" id="mobileDrawerOverlay" onclick="toggleMenu()"></div>
    <div class="mobile-drawer" id="mobileDrawer">
        <div class="drawer-header">
            <a href="index.php" class="brand-logo" style="font-size: 20px;">
                <i class="ph-bold ph-shopping-bag-open"></i> CURTISS STORE
            </a>
            <button class="close-drawer" onclick="toggleMenu()" aria-label="Close Menu">
                <i class="ph ph-x"></i>
            </button>
        </div>
        <div class="drawer-content">
            <a href="index.php?p=home" class="<?= $page === 'home' ? 'active' : '' ?>">Home</a>
            <a href="index.php?p=shop" class="<?= $page === 'shop' ? 'active' : '' ?>">Shop</a>
            
            <?php if ($userRole === 'wholesaler'): ?>
                <div class="drawer-user-info" style="color: var(--primary); font-weight:600;"><i class="ph ph-shield-check"></i> Wholesaler Portal</div>
            <?php elseif ($userRole === 'retail'): ?>
                <div class="drawer-user-info" style="color: var(--secondary); font-weight:600;"><i class="ph ph-user"></i> Retail Buyer</div>
            <?php else: ?>
                <a href="index.php?p=wholesaler-request" class="<?= $page === 'wholesaler-request' ? 'active' : '' ?>">Wholesale Request</a>
            <?php endif; ?>

            <?php if ($isLoggedIn): ?>
                <div class="drawer-user-info">
                    <span style="font-weight:600; display: block; margin-bottom: 8px;"><i class="ph ph-user-circle"></i> <?= htmlspecialchars($userName) ?></span>
                    <a href="index.php?logout=1" style="color:#ff3b30; padding: 0; font-size: 14px; font-weight: 600; text-decoration: none;">Logout</a>
                </div>
            <?php else: ?>
                <a href="index.php?p=login" class="<?= $page === 'login' ? 'active' : '' ?>">Login</a>
                <a href="index.php?p=register-retail" class="btn" style="margin-top: 8px;">Register</a>
            <?php endif; ?>
        </div>
    </div>

    <main class="container" style="flex-grow: 1;">
        
        <?php if (!empty($error)): ?>
            <div class="alert-box alert-danger" style="margin-top:20px;">
                <i class="ph ph-warning-circle"></i> <?= $error ?>
            </div>
        <?php endif; ?>

        <?php
        // ------------------ PAGE: HOME ------------------
        if ($page === 'home'):
        ?>
            <div class="hero">
                <h1>Direct Industrial & Retail Catalog</h1>
                <p>Access Sri Lanka's leading machinery and parts inventory catalog. Log in to claim authorized wholesale pricing tiers.</p>
                <div class="hero-actions">
                    <a href="index.php?p=shop" class="btn">Browse Collection</a>
                    <?php if(!$isLoggedIn): ?>
                        <a href="index.php?p=wholesaler-request" class="btn btn-outline">Apply for Wholesale Price</a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="featured-section">
                <h2 class="featured-title">Featured Hardware Categories</h2>
                <div class="categories-grid">
                    <?php foreach(array_slice($categories, 0, 4) as $cat): ?>
                        <a href="index.php?p=shop&category=<?= $cat->id ?>" class="category-card">
                            <div class="category-icon"><i class="ph ph-package"></i></div>
                            <h4 class="category-name"><?= htmlspecialchars($cat->name) ?></h4>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

        <?php
        // ------------------ PAGE: SHOP ------------------
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
            <div>
                <form action="index.php" method="GET" class="search-bar-row">
                    <input type="hidden" name="p" value="shop">
                    <?php if($catFilter): ?>
                        <input type="hidden" name="category" value="<?= $catFilter ?>">
                    <?php endif; ?>
                    <input type="text" name="q" class="form-control" placeholder="Search by product name or item code..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="btn"><i class="ph ph-magnifying-glass"></i> Search</button>
                </form>

                <div class="shop-layout">
                    <!-- Filters -->
                    <div class="filters-card">
                        <div class="filter-section">
                            <h4>All Categories</h4>
                            <ul class="filter-list">
                                <li>
                                    <a href="index.php?p=shop" class="<?= !$catFilter ? 'active' : '' ?>">
                                        <i class="ph ph-circles-four"></i> View All
                                    </a>
                                </li>
                                <?php foreach($categories as $cat): ?>
                                    <li>
                                        <a href="index.php?p=shop&category=<?= $cat->id ?>" class="<?= $catFilter == $cat->id ? 'active' : '' ?>">
                                            <i class="ph ph-tag"></i> <?= htmlspecialchars($cat->name) ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>

                    <!-- Products Grid -->
                    <div>
                        <div class="products-grid-header">
                            <h3 class="products-grid-title">Products Available (<?= count($products) ?>)</h3>
                        </div>
                        <div class="products-grid">
                            <?php if(empty($products)): ?>
                                <p style="grid-column: 1/-1; color: var(--text-muted); text-align: center; padding: 40px 0;">No active items matching your criteria.</p>
                            <?php else: ?>
                                <?php foreach($products as $prod): ?>
                                    <a href="index.php?p=product&id=<?= $prod->id ?>" class="prod-card">
                                        <div class="prod-img">
                                            <?php if(!empty($prod->image_path)): ?>
                                                <?php
                                                    $imgPath = $prod->image_path;
                                                    $erpUrl = getErpBaseUrl();
                                                    if(strpos($imgPath, 'public/') === 0) {
                                                        $imgSrc = $erpUrl . substr($imgPath, 7);
                                                    } else {
                                                        $imgSrc = $erpUrl . 'uploads/products/' . $imgPath;
                                                    }
                                                ?>
                                                <img src="<?= $imgSrc ?>" alt="<?= htmlspecialchars($prod->name) ?>" onload="console.log('Successfully loaded image: ' + this.src);" onerror="console.error('Failed to load image: ' + this.src);">
                                                <script>console.log('Product "<?= htmlspecialchars($prod->name) ?>" image path: <?= $imgSrc ?>');</script>
                                            <?php else: ?>
                                                <div class="no-img"><i class="ph ph-image-square"></i></div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="prod-details">
                                            <div class="prod-brand"><?= htmlspecialchars($prod->brand ?? 'Generic') ?></div>
                                            <h3><?= htmlspecialchars($prod->name) ?></h3>
                                            
                                            <div class="prod-status-row">
                                                <?php if($prod->qty > 0): ?>
                                                    <span class="stock-badge stock-in">In Stock (<?= $prod->qty ?> <?= $prod->unit ?>)</span>
                                                <?php else: ?>
                                                    <span class="stock-badge stock-out">Out of Stock</span>
                                                <?php endif; ?>
                                            </div>

                                            <div class="prod-price-row">
                                                <div class="price-badge">
                                                    Rs. <?= number_format(getItemPrice($prod, $userRole), 2) ?>
                                                </div>
                                                <?php if($userRole === 'wholesaler'): ?>
                                                    <span class="role-indicator role-indicator-wholesale">Wholesale</span>
                                                <?php else: ?>
                                                    <span class="role-indicator">Retail</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

        <?php
        // ------------------ PAGE: PRODUCT DETAILS ------------------
        elseif ($page === 'product'):
            $prodId = intval($_GET['id'] ?? 0);
            $stmt = $db->prepare("SELECT * FROM items WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => $prodId]);
            $product = $stmt->fetch();

            if (!$product) {
                echo "<p style='padding:50px; text-align:center;'>Product profile not found.</p>";
            } else {
                $varsList = json_decode($product->variations_json ?? '[]', true);
        ?>
            <div class="detail-grid">
                <div class="detail-img">
                    <?php if(!empty($product->image_path)): ?>
                        <?php
                            $imgPath = $product->image_path;
                            $erpUrl = getErpBaseUrl();
                            if(strpos($imgPath, 'public/') === 0) {
                                $imgSrc = $erpUrl . substr($imgPath, 7);
                            } else {
                                $imgSrc = $erpUrl . 'uploads/products/' . $imgPath;
                            }
                        ?>
                        <img src="<?= $imgSrc ?>" alt="<?= htmlspecialchars($product->name) ?>" onload="console.log('Successfully loaded details image: ' + this.src);" onerror="console.error('Failed to load details image: ' + this.src);">
                        <script>console.log('Product Details image path: <?= $imgSrc ?>');</script>
                    <?php else: ?>
                        <div class="no-img" style="font-size:64px;"><i class="ph ph-image-square"></i></div>
                    <?php endif; ?>
                </div>

                <div class="detail-content">
                    <span class="detail-brand">
                        <?= htmlspecialchars($product->brand ?? 'Generic') ?>
                    </span>
                    <h1 class="detail-title"><?= htmlspecialchars($product->name) ?></h1>
                    
                    <div style="margin-bottom: 20px;">
                        <?php if($product->qty > 0): ?>
                            <span class="stock-badge stock-in" style="font-size:13px; padding:4px 12px;" id="stockBadge">In Stock (<?= $product->qty ?> <?= $product->unit ?>)</span>
                        <?php else: ?>
                            <span class="stock-badge stock-out" style="font-size:13px; padding:4px 12px;" id="stockBadge">Out of Stock</span>
                        <?php endif; ?>
                    </div>

                    <div class="detail-price" id="priceDisplay">
                        Rs. <?= number_format(getItemPrice($product, $userRole), 2) ?>
                        <span class="detail-price-type">
                            Price type: <?= ($userRole === 'wholesaler' ? 'Wholesale Price Account' : 'Retail standard') ?>
                        </span>
                    </div>

                    <p class="detail-description">
                        <?= nl2br(htmlspecialchars($product->description ?? 'No product details cataloged yet.')) ?>
                    </p>

                    <form action="index.php?p=product&id=<?= $product->id ?>" method="POST" class="detail-form">
                        <input type="hidden" name="action" value="add_to_cart">
                        <input type="hidden" name="item_id" value="<?= $product->id ?>">
                        
                        <!-- Variation Price Overrides -->
                        <input type="hidden" name="variation_sku" id="varSku">
                        <input type="hidden" name="variation_price" id="varPrice">
                        <input type="hidden" name="variation_wholesale_price" id="varWholesalePrice">
                        <input type="hidden" name="variation_attribute" id="varAttr">

                        <?php if(!empty($varsList)): ?>
                            <div class="form-group" style="max-width:300px;">
                                <label for="variationSelect">Select Options/Size</label>
                                <select id="variationSelect" class="form-control" onchange="updateVariation()">
                                    <option value="" data-price="<?= $product->price ?>" data-wholesale="<?= $product->wholesale_price ?>" data-sku="<?= $product->item_code ?>" data-qty="<?= $product->qty ?>">Default option</option>
                                    <?php foreach($varsList as $v): ?>
                                        <option value="<?= htmlspecialchars($v['sku']) ?>" 
                                                data-sku="<?= htmlspecialchars($v['sku']) ?>" 
                                                data-price="<?= $v['price'] ?>" 
                                                data-wholesale="<?= $v['wholesale_price'] ?>"
                                                data-attr="<?= htmlspecialchars($v['attribute']) ?>"
                                                data-qty="10"> <!-- fallback qty -->
                                            <?= htmlspecialchars($v['attribute']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>

                        <div class="action-row">
                            <div class="form-group" style="margin-bottom:0; width:100px;">
                                <label for="qtyInput">Quantity</label>
                                <input type="number" name="qty" id="qtyInput" class="form-control" value="1" min="1" max="<?= max(1, $product->qty) ?>">
                            </div>
                            <button type="submit" class="btn" style="flex-grow:1; height:45px;" <?= ($product->qty <= 0) ? 'disabled style="opacity:0.5; cursor:not-allowed;"' : '' ?>>
                                <i class="ph ph-shopping-cart-simple"></i> Add To Cart
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

                    // Update hidden inputs
                    document.getElementById('varSku').value = sku;
                    document.getElementById('varPrice').value = price;
                    document.getElementById('varWholesalePrice').value = wholesale;
                    document.getElementById('varAttr').value = attr;

                    // Update UI price
                    const selectedPrice = (role === 'wholesaler') ? wholesale : price;
                    document.getElementById('priceDisplay').innerHTML = 'Rs. ' + selectedPrice.toFixed(2) + 
                        '<span class="detail-price-type">' +
                        'Price type: ' + (role === 'wholesaler' ? 'Wholesale Price Account' : 'Retail standard') + '</span>';
                }
            </script>
        <?php
            }
        // ------------------ PAGE: LOGIN ------------------
        elseif ($page === 'login'):
        ?>
            <div class="card-form">
                <h3 style="margin-top:0; font-weight:700; font-size:22px; text-align:center; margin-bottom:20px;">Account Login</h3>
                <form action="index.php?p=login" method="POST">
                    <input type="hidden" name="action" value="login">
                    
                    <div class="form-group">
                        <label>Username or Email Address</label>
                        <input type="text" name="username_or_email" class="form-control" required placeholder="e.g. johndoe">
                    </div>

                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" class="form-control" required placeholder="••••••••">
                    </div>

                    <button type="submit" class="btn" style="width:100%;">Sign In</button>
                </form>
                <div style="margin-top:20px; text-align:center; font-size:14px; color:var(--text-muted);">
                    Don't have an account? <a href="index.php?p=register-retail" style="color:var(--primary); font-weight:600;">Register as Retail Customer</a>
                    <div style="margin-top:10px;">
                        Applying for B2B pricing? <a href="index.php?p=wholesaler-request" style="color:var(--primary); font-weight:600;">Request Wholesaler Access</a>
                    </div>
                </div>
            </div>

        <?php
        // ------------------ PAGE: REGISTER RETAIL ------------------
        elseif ($page === 'register-retail'):
        ?>
            <div class="card-form">
                <h3 style="margin-top:0; font-weight:700; font-size:22px; text-align:center; margin-bottom:20px;">Create Retail Account</h3>
                <form action="index.php?p=register-retail" method="POST">
                    <input type="hidden" name="action" value="register_retail">

                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g. John Doe">
                    </div>

                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" class="form-control" required placeholder="e.g. john@example.com">
                    </div>

                    <div class="form-group">
                        <label>Choose Username</label>
                        <input type="text" name="username" class="form-control" required placeholder="e.g. johndoe">
                    </div>

                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" class="form-control" required placeholder="••••••••">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Phone Number</label>
                            <input type="text" name="phone" class="form-control" placeholder="e.g. +94 77 123 4567">
                        </div>
                        <div class="form-group">
                            <label>City</label>
                            <input type="text" name="city" class="form-control" placeholder="e.g. Kurunegala">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Delivery Address</label>
                        <textarea name="address" class="form-control" rows="2" placeholder="Street name, block..."></textarea>
                    </div>

                    <button type="submit" class="btn" style="width:100%;">Create Account</button>
                </form>
                <div style="margin-top:20px; text-align:center; font-size:14px; color:var(--text-muted);">
                    Already have an account? <a href="index.php?p=login" style="color:var(--primary); font-weight:600;">Sign In</a>
                </div>
            </div>

        <?php
        // ------------------ PAGE: WHOLESALER REQUEST ------------------
        elseif ($page === 'wholesaler-request'):
        ?>
            <div class="card-form" style="max-width: 600px;">
                <h3 style="margin-top:0; font-weight:700; font-size:22px; text-align:center; margin-bottom:10px;">Request B2B Wholesale Account</h3>
                <p style="color:var(--text-muted); font-size:14px; text-align:center; margin-bottom:25px;">Fill out this request form to register your business entity. Once reviewed, our support agents will verify details, link your company profile, and activate wholesale pricing.</p>
                
                <form action="index.php?p=wholesaler-request" method="POST">
                    <input type="hidden" name="action" value="submit_wholesaler_request">

                    <div class="form-group">
                        <label>Business Name (Company Name) *</label>
                        <input type="text" name="business_name" class="form-control" required placeholder="e.g. Candent Hardware PLC">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Contact Phone Number *</label>
                            <input type="text" name="contact_number" class="form-control" required placeholder="e.g. +94 37 123 4567">
                        </div>
                        <div class="form-group">
                            <label>Business Email Address *</label>
                            <input type="email" name="email_address" class="form-control" required placeholder="e.g. purchase@candent.lk">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Requested Login Username *</label>
                            <input type="text" name="username" class="form-control" required placeholder="e.g. candent_b2b">
                        </div>
                        <div class="form-group">
                            <label>Requested Account Password *</label>
                            <input type="password" name="password" class="form-control" required placeholder="••••••••">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Business Operations City *</label>
                        <input type="text" name="city" class="form-control" required placeholder="e.g. Kurunegala">
                    </div>

                    <div class="form-group">
                        <label>Registered Business Address *</label>
                        <textarea name="address" class="form-control" rows="3" required placeholder="No., Street, Town name..."></textarea>
                    </div>

                    <div class="form-group">
                        <label>Verification notes / Special Instructions</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="e.g. Business registration document references, BRN-12345..."></textarea>
                    </div>

                    <button type="submit" class="btn" style="width:100%;">Submit Access Request</button>
                </form>
            </div>

        <?php
        // ------------------ PAGE: REQUEST SUCCESS ------------------
        elseif ($page === 'request_success'):
        ?>
            <div class="card-form style-success" style="text-align:center; padding: 40px 30px;">
                <div style="font-size: 64px; color:#2e7d32; margin-bottom:15px;"><i class="ph-fill ph-check-circle"></i></div>
                <h3 style="margin-top:0; font-weight:700; font-size:24px;">Application Received</h3>
                <p style="color:var(--text-muted); font-size:15px; line-height:1.6; margin-bottom:30px;">
                    <?= $message ?>
                </p>
                <a href="index.php?p=shop" class="btn">Return to Shop</a>
            </div>

        <?php
        // ------------------ PAGE: ORDER SUCCESS ------------------
        elseif ($page === 'order_success'):
        ?>
            <div class="card-form style-success" style="text-align:center; padding: 40px 30px;">
                <div style="font-size: 64px; color:#2e7d32; margin-bottom:15px;"><i class="ph-fill ph-check-circle"></i></div>
                <h3 style="margin-top:0; font-weight:700; font-size:24px;">Order Placed Successfully!</h3>
                <p style="color:var(--text-muted); font-size:15px; line-height:1.6; margin-bottom:30px;">
                    <?= $message ?>
                </p>
                <a href="index.php?p=shop" class="btn">Continue Shopping</a>
            </div>

        <?php
        // ------------------ PAGE: CART ------------------
        elseif ($page === 'cart'):
        ?>
            <div>
                <h2 class="cart-title">Your Shopping Cart</h2>

                <?php if(empty($_SESSION['ec_cart'])): ?>
                    <div style="background:var(--card-bg); border:1px solid var(--border-color); border-radius:var(--rounded); padding:50px; text-align:center; box-shadow:var(--shadow-sm);">
                        <div style="font-size:48px; color:var(--text-muted); margin-bottom:10px;"><i class="ph ph-shopping-cart-simple"></i></div>
                        <p style="color:var(--text-muted); font-size:16px;">Your cart is empty.</p>
                        <a href="index.php?p=shop" class="btn" style="margin-top:15px;">Go Browse Products</a>
                    </div>
                <?php else: ?>
                    <form action="index.php?p=cart" method="POST">
                        <input type="hidden" name="action" value="update_cart">
                        
                        <div class="cart-wrapper">
                            <table class="cart-table">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th>SKU</th>
                                        <th>Price</th>
                                        <th style="width:100px;">Quantity</th>
                                        <th style="text-align:right;">Subtotal</th>
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
                                            <td data-label="Item">
                                                <strong><?= htmlspecialchars($item['name']) ?></strong>
                                                <?php if(!empty($item['attribute'])): ?>
                                                    <span style="font-size:11px; color:var(--text-muted); display:block;">Option: <?= htmlspecialchars($item['attribute']) ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td data-label="SKU" style="font-family:monospace;"><?= htmlspecialchars($item['sku']) ?></td>
                                            <td data-label="Price">Rs. <?= number_format($item['price'], 2) ?></td>
                                            <td data-label="Quantity">
                                                <input type="number" name="qty[<?= $k ?>]" value="<?= $item['qty'] ?>" min="0" class="form-control" style="padding:4px 8px; font-size:13px; text-align:center;">
                                            </td>
                                            <td data-label="Subtotal" style="text-align:right; font-weight:700;">Rs. <?= number_format($itemSub, 2) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <tr class="cart-total-row">
                                        <td colspan="4" style="text-align:right; font-weight:600; font-size:16px;">Total Subtotal:</td>
                                        <td style="text-align:right; font-weight:800; font-size:20px; color:var(--primary);">Rs. <?= number_format($subtotal, 2) ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="action-row" style="margin-top: 24px; margin-bottom: 48px; justify-content: space-between;">
                            <button type="submit" class="btn btn-outline" style="align-self: center;"><i class="ph ph-arrows-clockwise"></i> Recalculate Quantities</button>
                            <a href="index.php?p=checkout" class="btn" style="align-self: center;">Proceed to Checkout <i class="ph ph-arrow-right"></i></a>
                        </div>
                    </form>
                <?php endif; ?>
            </div>

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
            <div class="checkout-grid">
                <div class="card" style="background:var(--card-bg); border:1px solid var(--border-color); border-radius:var(--rounded); padding:30px; box-shadow:var(--shadow-sm);">
                    <h3 style="margin-top:0; font-weight:700; border-bottom:1px solid var(--border-color); padding-bottom:10px; margin-bottom:20px;">Shipping & Billing Information</h3>
                    
                    <form action="index.php?p=checkout" method="POST" id="checkoutForm">
                        <input type="hidden" name="action" value="submit_order">

                        <?php if ($userRole === 'wholesaler'): ?>
                            <div class="alert-box alert-success" style="margin-bottom:20px;">
                                <i class="ph ph-check-circle"></i> B2B wholesale order account verified. Details will sync to your customer ledger profile: <strong><?= htmlspecialchars($userName) ?></strong>.
                            </div>
                        <?php else: ?>
                            <div class="form-group">
                                <label>Recipient's Full Name *</label>
                                <input type="text" name="billing_name" class="form-control" required placeholder="e.g. John Doe">
                            </div>
                            <div class="form-group">
                                <label>Recipient's Phone Number *</label>
                                <input type="text" name="billing_phone" class="form-control" required placeholder="e.g. +94 77 123 4567">
                            </div>
                        <?php endif; ?>

                        <div class="form-group">
                            <label>Full Delivery / Dispatch Address *</label>
                            <textarea name="shipping_address" class="form-control" rows="4" required placeholder="No., Street, Town, District..."></textarea>
                        </div>

                        <div class="form-group">
                            <label>Special Shipping Notes or Logistics Instructions</label>
                            <textarea name="shipping_notes" class="form-control" rows="2" placeholder="e.g. Please deliver during business hours..."></textarea>
                        </div>

                        <button type="submit" class="btn" style="width:100%; height:45px; margin-top:10px;">Confirm & Submit Order</button>
                    </form>
                </div>

                <div class="card" style="background:var(--card-bg); border:1px solid var(--border-color); border-radius:var(--rounded); padding:25px; box-shadow:var(--shadow-sm); height:fit-content;">
                    <h3 style="margin-top:0; font-weight:700; border-bottom:1px solid var(--border-color); padding-bottom:10px; margin-bottom:15px;">Order Summary</h3>
                    
                    <div style="display:flex; flex-direction:column; gap:12px; margin-bottom:20px;">
                        <?php foreach($_SESSION['ec_cart'] as $item): ?>
                            <div style="display:flex; justify-content:space-between; font-size:14px;">
                                <div>
                                    <strong><?= $item['qty'] ?>x</strong> <?= htmlspecialchars($item['name']) ?>
                                    <?php if(!empty($item['attribute'])): ?>
                                        <span style="font-size:11px; color:var(--text-muted); display:block;">Option: <?= htmlspecialchars($item['attribute']) ?></span>
                                    <?php endif; ?>
                                </div>
                                <span style="font-weight:600;">Rs. <?= number_format($item['price'] * $item['qty'], 2) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div style="border-top:1px dashed var(--border-color); padding-top:15px; display:flex; justify-content:space-between; font-weight:800; font-size:18px;">
                        <span>Grand Total:</span>
                        <span style="color:var(--primary);">Rs. <?= number_format($subtotal, 2) ?></span>
                    </div>
                </div>
            </div>

        <?php endif; ?>

    </main>

    <footer>
        <div class="container">
            <p>&copy; <?= date('Y') ?> Curtiss E-Commerce Platform. Direct integration with Curtiss ERP Catalog.</p>
        </div>
    </footer>

    <script>
        // Light / Dark Theme script
        function updateThemeIcons(theme) {
            const iconDesktop = document.getElementById('theme-icon');
            const iconMobile = document.getElementById('theme-icon-mobile');
            const iconClass = (theme === 'dark') ? 'ph ph-moon' : 'ph ph-sun';
            if (iconDesktop) iconDesktop.className = iconClass;
            if (iconMobile) iconMobile.className = iconClass;
        }

        function toggleTheme() {
            const body = document.body;
            const currentTheme = body.getAttribute('data-theme');
            const newTheme = (currentTheme === 'light') ? 'dark' : 'light';
            
            body.setAttribute('data-theme', newTheme);
            localStorage.setItem('ec-theme', newTheme);
            updateThemeIcons(newTheme);
        }

        // Apply saved theme
        window.addEventListener('DOMContentLoaded', () => {
            const savedTheme = localStorage.getItem('ec-theme') || 'light';
            document.body.setAttribute('data-theme', savedTheme);
            updateThemeIcons(savedTheme);
        });

        // Mobile Menu Drawer Toggling
        function toggleMenu() {
            const drawer = document.getElementById('mobileDrawer');
            const overlay = document.getElementById('mobileDrawerOverlay');
            if (drawer && overlay) {
                drawer.classList.toggle('active');
                overlay.classList.toggle('active');
            }
        }
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
