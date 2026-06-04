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
            --primary: #0066cc;
            --primary-hover: #005bb5;
            --bg-site: #f4f5f8;
            --card-bg: #ffffff;
            --text-dark: #1e1e2d;
            --text-muted: #6c7a89;
            --border-color: rgba(0,0,0,0.08);
            --shadow-sm: 0 2px 8px rgba(0,0,0,0.04);
            --shadow-md: 0 8px 24px rgba(0,0,0,0.06);
            --rounded: 12px;
        }

        [data-theme="dark"] {
            --bg-site: #121214;
            --card-bg: #1e1e24;
            --text-dark: #f3f4f6;
            --text-muted: #9ca3af;
            --border-color: rgba(255,255,255,0.08);
            --shadow-sm: 0 2px 8px rgba(0,0,0,0.2);
            --shadow-md: 0 8px 24px rgba(0,0,0,0.3);
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg-site);
            color: var(--text-dark);
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            transition: background 0.3s, color 0.3s;
        }

        header {
            background-color: var(--card-bg);
            border-bottom: 1px solid var(--border-color);
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: var(--shadow-sm);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
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
            gap: 24px;
        }

        .nav-links a {
            color: var(--text-dark);
            text-decoration: none;
            font-weight: 500;
            font-size: 15px;
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
            color: var(--text-dark);
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
            font-size: 14px;
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

        /* Hero Banner */
        .hero {
            padding: 80px 0;
            background: linear-gradient(135deg, rgba(0, 102, 204, 0.05) 0%, rgba(138, 43, 226, 0.05) 100%);
            border-bottom: 1px solid var(--border-color);
            text-align: center;
            margin-bottom: 40px;
        }
        .hero h1 {
            font-size: 48px;
            font-weight: 800;
            margin: 0 0 16px 0;
            letter-spacing: -1px;
            background: linear-gradient(to right, var(--primary), #8a2be2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .hero p {
            font-size: 18px;
            color: var(--text-muted);
            max-width: 600px;
            margin: 0 auto 30px auto;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--primary);
            color: white;
            padding: 12px 24px;
            border-radius: var(--rounded);
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
            font-size: 15px;
        }
        .btn:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
        }

        .btn-outline {
            background: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
        }
        .btn-outline:hover {
            background: rgba(0, 102, 204, 0.05);
            color: var(--primary-hover);
        }

        /* Products Grid */
        .shop-layout {
            display: grid;
            grid-template-columns: 240px 1fr;
            gap: 30px;
            margin-bottom: 50px;
        }

        .filters-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: var(--rounded);
            padding: 20px;
            height: fit-content;
            box-shadow: var(--shadow-sm);
        }
        .filter-section {
            margin-bottom: 25px;
        }
        .filter-section h4 {
            margin: 0 0 12px 0;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-muted);
        }
        .filter-list {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .filter-list a {
            color: var(--text-dark);
            text-decoration: none;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .filter-list a:hover, .filter-list a.active {
            color: var(--primary);
            font-weight: 600;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 24px;
        }

        .prod-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: var(--rounded);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            transition: all 0.3s;
            display: flex;
            flex-direction: column;
            text-decoration: none;
            color: inherit;
        }
        .prod-card:hover {
            transform: translateY(-6px);
            box-shadow: var(--shadow-md);
        }
        .prod-img {
            height: 180px;
            background-color: rgba(0,0,0,0.02);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }
        .prod-img img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        .no-img {
            color: var(--text-muted);
            font-size: 32px;
        }
        .prod-details {
            padding: 18px;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }
        .prod-details h3 {
            margin: 0 0 8px 0;
            font-size: 16px;
            font-weight: 600;
            line-height: 1.4;
        }
        .prod-brand {
            font-size: 12px;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 12px;
        }
        .prod-price-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: auto;
        }
        .price-badge {
            font-size: 18px;
            font-weight: 800;
            color: var(--primary);
        }
        .role-indicator {
            font-size: 10px;
            background: rgba(0, 102, 204, 0.1);
            color: var(--primary);
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: 600;
        }

        .stock-badge {
            font-size: 11px;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 12px;
        }
        .stock-in { background-color: #e8f5e9; color: #2e7d32; }
        .stock-out { background-color: #ffebee; color: #c62828; }

        /* Form styling */
        .card-form {
            max-width: 500px;
            margin: 40px auto;
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: var(--rounded);
            padding: 30px;
            box-shadow: var(--shadow-md);
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-muted);
        }
        .form-control {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background: transparent;
            color: var(--text-dark);
            box-sizing: border-box;
            font-size: 14px;
            font-family: inherit;
        }
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
        }

        /* Detail page */
        .detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin: 40px 0;
        }
        .detail-img {
            background-color: rgba(0,0,0,0.02);
            border: 1px solid var(--border-color);
            border-radius: var(--rounded);
            height: 400px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .detail-img img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .alert-box {
            padding: 12px 18px;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 20px;
        }
        .alert-success { background: #e8f5e9; color: #2e7d32; border: 1px solid #c3e6cb; }
        .alert-danger { background: #ffebee; color: #c62828; border: 1px solid #f5c6cb; }

        footer {
            margin-top: auto;
            background-color: var(--card-bg);
            border-top: 1px solid var(--border-color);
            padding: 30px 0;
            text-align: center;
            color: var(--text-muted);
            font-size: 14px;
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
                    <span class="role-indicator"><i class="ph ph-shield-check"></i> Wholesaler Portal</span>
                <?php elseif ($userRole === 'retail'): ?>
                    <span class="role-indicator" style="background:rgba(138,43,226,0.1); color:#8a2be2;">Retail Buyer</span>
                <?php else: ?>
                    <a href="index.php?p=wholesaler-request" class="<?= $page === 'wholesaler-request' ? 'active' : '' ?>">Wholesale Request</a>
                <?php endif; ?>

                <?php if ($isLoggedIn): ?>
                    <span style="font-weight:600; font-size:14px;"><i class="ph ph-user"></i> <?= htmlspecialchars($userName) ?></span>
                    <a href="index.php?logout=1" style="color:#ff3b30; font-size: 13px;">Logout</a>
                <?php else: ?>
                    <a href="index.php?p=login">Login</a>
                    <a href="index.php?p=register-retail" class="btn" style="padding: 6px 14px; font-size: 13px;">Register</a>
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
            <div class="hero" style="border-radius: var(--rounded); margin-top:20px;">
                <h1>Direct Industrial & Retail Catalog</h1>
                <p>Access Sri Lanka's leading machinery and parts inventory catalog. Log in to claim authorized wholesale pricing tiers.</p>
                <div style="display:flex; justify-content:center; gap:15px;">
                    <a href="index.php?p=shop" class="btn">Browse Collection</a>
                    <?php if(!$isLoggedIn): ?>
                        <a href="index.php?p=wholesaler-request" class="btn btn-outline">Apply for Wholesale Price</a>
                    <?php endif; ?>
                </div>
            </div>

            <div style="margin-bottom:50px;">
                <h2 style="font-weight:700; margin-bottom:20px;">Featured Hardware Categories</h2>
                <div style="display:grid; grid-template-columns: repeat(4, 1fr); gap: 20px;">
                    <?php foreach(array_slice($categories, 0, 4) as $cat): ?>
                        <a href="index.php?p=shop&category=<?= $cat->id ?>" style="text-decoration:none; color:inherit;">
                            <div style="background:var(--card-bg); border:1px solid var(--border-color); border-radius:var(--rounded); padding:24px; text-align:center; box-shadow:var(--shadow-sm); transition:transform 0.2s;" onmouseover="this.style.transform='translateY(-4px)'" onmouseout="this.style.transform='none'">
                                <div style="font-size:32px; color:var(--primary); margin-bottom:10px;"><i class="ph ph-package"></i></div>
                                <h4 style="margin:0; font-size:16px; font-weight:600;"><?= htmlspecialchars($cat->name) ?></h4>
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
            <div style="margin-top: 30px;">
                <form action="index.php" method="GET" style="display:flex; gap:12px; margin-bottom:30px;">
                    <input type="hidden" name="p" value="shop">
                    <?php if($catFilter): ?>
                        <input type="hidden" name="category" value="<?= $catFilter ?>">
                    <?php endif; ?>
                    <input type="text" name="q" class="form-control" placeholder="Search by product name or item code..." value="<?= htmlspecialchars($search) ?>" style="max-width:500px;">
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
                        <h3 style="margin-top:0; margin-bottom:15px; font-weight:700;">Products Available (<?= count($products) ?>)</h3>
                        <div class="products-grid">
                            <?php if(empty($products)): ?>
                                <p style="grid-column: 1/-1; color: var(--text-muted); text-align: center; padding: 40px 0;">No active items matching your criteria.</p>
                            <?php else: ?>
                                <?php foreach($products as $prod): ?>
                                    <a href="index.php?p=product&id=<?= $prod->id ?>" class="prod-card">
                                        <div class="prod-img">
                                            <?php if(!empty($prod->image_path)): ?>
                                                <!-- Handle both ERP public path and direct filename formats -->
                                                <?php
                                                    $imgPath = $prod->image_path;
                                                    if(strpos($imgPath, 'public/') === 0) {
                                                        $imgSrc = 'http://localhost/Curtiss-ERP/' . $imgPath;
                                                    } else {
                                                        $imgSrc = 'http://localhost/Curtiss-ERP/public/uploads/products/' . $imgPath;
                                                    }
                                                ?>
                                                <img src="<?= $imgSrc ?>" alt="<?= htmlspecialchars($prod->name) ?>">
                                            <?php else: ?>
                                                <div class="no-img"><i class="ph ph-image-square"></i></div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="prod-details">
                                            <div class="prod-brand"><?= htmlspecialchars($prod->brand ?? 'Generic') ?></div>
                                            <h3><?= htmlspecialchars($prod->name) ?></h3>
                                            
                                            <div style="margin-bottom: 12px;">
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
                                                    <span class="role-indicator">Wholesale</span>
                                                <?php else: ?>
                                                    <span class="role-indicator" style="background:rgba(0,0,0,0.05); color:#666;">Retail</span>
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
                            if(strpos($imgPath, 'public/') === 0) {
                                $imgSrc = 'http://localhost/Curtiss-ERP/' . $imgPath;
                            } else {
                                $imgSrc = 'http://localhost/Curtiss-ERP/public/uploads/products/' . $imgPath;
                            }
                        ?>
                        <img src="<?= $imgSrc ?>" alt="<?= htmlspecialchars($product->name) ?>">
                    <?php else: ?>
                        <div class="no-img" style="font-size:64px;"><i class="ph ph-image-square"></i></div>
                    <?php endif; ?>
                </div>

                <div>
                    <span style="font-size:12px; color:var(--primary); font-weight:700; text-transform:uppercase; letter-spacing:1px;">
                        <?= htmlspecialchars($product->brand ?? 'Generic') ?>
                    </span>
                    <h1 style="font-size:32px; font-weight:800; margin:5px 0 15px 0;"><?= htmlspecialchars($product->name) ?></h1>
                    
                    <div style="margin-bottom: 20px;">
                        <?php if($product->qty > 0): ?>
                            <span class="stock-badge stock-in" style="font-size:13px; padding:4px 12px;" id="stockBadge">In Stock (<?= $product->qty ?> <?= $product->unit ?>)</span>
                        <?php else: ?>
                            <span class="stock-badge stock-out" style="font-size:13px; padding:4px 12px;" id="stockBadge">Out of Stock</span>
                        <?php endif; ?>
                    </div>

                    <div style="font-size:24px; font-weight:800; color:var(--primary); margin-bottom:20px;" id="priceDisplay">
                        Rs. <?= number_format(getItemPrice($product, $userRole), 2) ?>
                        <span style="font-size: 12px; font-weight: 500; color: var(--text-muted); display:block; margin-top:4px;">
                            Price type: <?= ($userRole === 'wholesaler' ? 'Wholesale Price Account' : 'Retail standard') ?>
                        </span>
                    </div>

                    <p style="color:var(--text-muted); line-height:1.6; margin-bottom:25px;">
                        <?= nl2br(htmlspecialchars($product->description ?? 'No product details cataloged yet.')) ?>
                    </p>

                    <form action="index.php?p=product&id=<?= $product->id ?>" method="POST" style="border-top:1px solid var(--border-color); padding-top:25px;">
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

                        <div style="display:flex; gap:15px; align-items:flex-end;">
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
                        '<span style="font-size: 12px; font-weight: 500; color: var(--text-muted); display:block; margin-top:4px;">' +
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

                    <div style="display:flex; gap:12px;">
                        <div class="form-group" style="flex:1;">
                            <label>Phone Number</label>
                            <input type="text" name="phone" class="form-control" placeholder="e.g. +94 77 123 4567">
                        </div>
                        <div class="form-group" style="flex:1;">
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

                    <div style="display:flex; gap:12px;">
                        <div class="form-group" style="flex:1;">
                            <label>Contact Phone Number *</label>
                            <input type="text" name="contact_number" class="form-control" required placeholder="e.g. +94 37 123 4567">
                        </div>
                        <div class="form-group" style="flex:1;">
                            <label>Business Email Address *</label>
                            <input type="email" name="email_address" class="form-control" required placeholder="e.g. purchase@candent.lk">
                        </div>
                    </div>

                    <div style="display:flex; gap:12px;">
                        <div class="form-group" style="flex:1;">
                            <label>Requested Login Username *</label>
                            <input type="text" name="username" class="form-control" required placeholder="e.g. candent_b2b">
                        </div>
                        <div class="form-group" style="flex:1;">
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
            <div style="margin-top: 30px;">
                <h2 style="font-weight:700; margin-bottom:20px;">Your Shopping Cart</h2>

                <?php if(empty($_SESSION['ec_cart'])): ?>
                    <div style="background:var(--card-bg); border:1px solid var(--border-color); border-radius:var(--rounded); padding:50px; text-align:center; box-shadow:var(--shadow-sm);">
                        <div style="font-size:48px; color:var(--text-muted); margin-bottom:10px;"><i class="ph ph-shopping-cart-simple"></i></div>
                        <p style="color:var(--text-muted); font-size:16px;">Your cart is empty.</p>
                        <a href="index.php?p=shop" class="btn" style="margin-top:15px;">Go Browse Products</a>
                    </div>
                <?php else: ?>
                    <form action="index.php?p=cart" method="POST">
                        <input type="hidden" name="action" value="update_cart">
                        
                        <div style="background:var(--card-bg); border:1px solid var(--border-color); border-radius:var(--rounded); overflow:hidden; box-shadow:var(--shadow-sm); margin-bottom:30px;">
                            <table style="width:100%; border-collapse:collapse; font-size:14px;">
                                <thead>
                                    <tr style="border-bottom:1px solid var(--border-color); background:rgba(0,0,0,0.02); text-align:left;">
                                        <th style="padding:15px;">Item</th>
                                        <th style="padding:15px;">SKU</th>
                                        <th style="padding:15px;">Price</th>
                                        <th style="padding:15px; width:100px;">Quantity</th>
                                        <th style="padding:15px; text-align:right;">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $subtotal = 0;
                                    foreach($_SESSION['ec_cart'] as $k => $item): 
                                        $itemSub = $item['price'] * $item['qty'];
                                        $subtotal += $itemSub;
                                    ?>
                                        <tr style="border-bottom:1px solid var(--border-color);">
                                            <td style="padding:15px;">
                                                <strong><?= htmlspecialchars($item['name']) ?></strong>
                                                <?php if(!empty($item['attribute'])): ?>
                                                    <span style="font-size:11px; color:var(--text-muted); display:block;">Option: <?= htmlspecialchars($item['attribute']) ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="padding:15px; font-family:monospace;"><?= htmlspecialchars($item['sku']) ?></td>
                                            <td style="padding:15px;">Rs. <?= number_format($item['price'], 2) ?></td>
                                            <td style="padding:15px;">
                                                <input type="number" name="qty[<?= $k ?>]" value="<?= $item['qty'] ?>" min="0" class="form-control" style="padding:4px 8px; font-size:13px; text-align:center;">
                                            </td>
                                            <td style="padding:15px; text-align:right; font-weight:700;">Rs. <?= number_format($itemSub, 2) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <tr style="background:rgba(0,0,0,0.01);">
                                        <td colspan="4" style="padding:20px; text-align:right; font-weight:600; font-size:16px;">Total Subtotal:</td>
                                        <td style="padding:20px; text-align:right; font-weight:800; font-size:20px; color:var(--primary);">Rs. <?= number_format($subtotal, 2) ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:50px;">
                            <button type="submit" class="btn btn-outline"><i class="ph ph-arrows-clockwise"></i> Recalculate Quantities</button>
                            <a href="index.php?p=checkout" class="btn">Proceed to Checkout <i class="ph ph-arrow-right"></i></a>
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
            <div style="margin-top: 30px; display:grid; grid-template-columns: 1fr 400px; gap:30px; margin-bottom:50px;">
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
                icon.className = 'ph ph-moon';
            } else {
                icon.className = 'ph ph-sun';
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
