<?php

class MigrationManager {
    private static $initialized = false;

    /**
     * List of all system migrations.
     * The key is the unique migration identifier.
     * The value is either a SQL string or a callable function.
     */
    private static function getMigrations() {
        return [
            'create_migrations_table' => "
                CREATE TABLE IF NOT EXISTS migrations (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    migration VARCHAR(255) UNIQUE NOT NULL,
                    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ",
            'customers_username' => "ALTER TABLE customers ADD COLUMN username VARCHAR(100) UNIQUE NULL AFTER email",
            'customers_password' => "ALTER TABLE customers ADD COLUMN password VARCHAR(255) NULL AFTER username",
            'create_wholesaler_requests' => "
                CREATE TABLE IF NOT EXISTS wholesaler_requests (
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
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ",
            'create_ecommerce_retail_customers' => "
                CREATE TABLE IF NOT EXISTS ecommerce_retail_customers (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(100) NOT NULL,
                    email VARCHAR(150) NOT NULL UNIQUE,
                    username VARCHAR(100) UNIQUE NULL,
                    password VARCHAR(255) NOT NULL,
                    phone VARCHAR(50) NULL,
                    address TEXT NULL,
                    city VARCHAR(100) NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ",
            'add_username_to_ecommerce_retail_customers' => "ALTER TABLE ecommerce_retail_customers ADD COLUMN username VARCHAR(100) UNIQUE NULL AFTER email",
            'add_ecommerce_store_url_to_company_settings' => "ALTER TABLE company_settings ADD COLUMN ecommerce_store_url VARCHAR(255) NULL DEFAULT ''",
            'add_facebook_page_id_to_company_settings' => "ALTER TABLE company_settings ADD COLUMN facebook_page_id VARCHAR(100) NULL DEFAULT ''",
            'add_facebook_access_token_to_company_settings' => "ALTER TABLE company_settings ADD COLUMN facebook_access_token TEXT NULL",
            'add_tax_number_to_company_settings' => "ALTER TABLE company_settings ADD COLUMN tax_number VARCHAR(100) NULL",
            'customers_status' => "ALTER TABLE customers ADD COLUMN status VARCHAR(20) DEFAULT 'active' AFTER notes",
            'item_categories_status' => "ALTER TABLE item_categories ADD COLUMN status VARCHAR(20) DEFAULT 'active'",
            'item_categories_woo_category_id' => "ALTER TABLE item_categories ADD COLUMN woo_category_id INT NULL DEFAULT NULL",
            'item_categories_description' => "ALTER TABLE item_categories ADD COLUMN description TEXT NULL",
            'mca_areas_status' => "ALTER TABLE mca_areas ADD COLUMN status VARCHAR(20) DEFAULT 'active'",
            'items_quantity_reserved' => "ALTER TABLE items ADD COLUMN quantity_reserved INT DEFAULT 0 AFTER quantity_on_hand",
            'item_variation_options_quantity_reserved' => "ALTER TABLE item_variation_options ADD COLUMN quantity_reserved INT DEFAULT 0 AFTER quantity_on_hand",
            'invoice_items_variation_option_id' => "ALTER TABLE invoice_items ADD COLUMN variation_option_id INT NULL DEFAULT NULL AFTER item_id",
            'audit_logs_record_id' => "ALTER TABLE audit_logs ADD COLUMN record_id INT NULL DEFAULT NULL",
            'audit_logs_old_values' => "ALTER TABLE audit_logs ADD COLUMN old_values LONGTEXT NULL DEFAULT NULL",
            'audit_logs_new_values' => "ALTER TABLE audit_logs ADD COLUMN new_values LONGTEXT NULL DEFAULT NULL",
            'audit_logs_browser_device' => "ALTER TABLE audit_logs ADD COLUMN browser_device VARCHAR(255) NULL DEFAULT NULL",
            'deliveries_selected_credit_invoices' => "ALTER TABLE deliveries ADD COLUMN selected_credit_invoices TEXT NULL",
            'deliveries_secondary_rep_route_id' => "ALTER TABLE deliveries ADD COLUMN secondary_rep_route_id INT NULL AFTER rep_route_id",
            'deliveries_reconciliation_json' => "ALTER TABLE deliveries ADD COLUMN reconciliation_json TEXT NULL",
            'deliveries_return_stock_json' => "ALTER TABLE deliveries ADD COLUMN return_stock_json TEXT NULL",
            'deliveries_accounting_entries_json' => "ALTER TABLE deliveries ADD COLUMN accounting_entries_json TEXT NULL",
            'rep_daily_routes_notes' => "ALTER TABLE rep_daily_routes ADD COLUMN notes TEXT NULL",
            'invoices_stock_status' => "ALTER TABLE invoices ADD COLUMN stock_status VARCHAR(20) DEFAULT 'deducted' AFTER status",
            'invoices_notes' => "ALTER TABLE invoices ADD COLUMN notes TEXT NULL AFTER global_discount_type",
            'invoice_items_item_id' => "ALTER TABLE invoice_items ADD COLUMN item_id INT NULL DEFAULT NULL AFTER invoice_id",
            'invoices_cheque_date' => "ALTER TABLE invoices ADD COLUMN cheque_date DATE NULL AFTER due_date",
            'invoices_payment_term_id' => "ALTER TABLE invoices ADD COLUMN payment_term_id INT NULL DEFAULT NULL AFTER due_date",
            'invoices_uuid' => "ALTER TABLE invoices ADD COLUMN uuid VARCHAR(100) UNIQUE NULL AFTER invoice_number",
            'sales_orders_payment_term_id' => "ALTER TABLE sales_orders ADD COLUMN payment_term_id INT NULL DEFAULT NULL AFTER due_date",
            'goods_receipt_notes_receipt_number' => "ALTER TABLE goods_receipt_notes ADD COLUMN receipt_number VARCHAR(100) NULL AFTER grn_number",
            'goods_receipt_notes_is_approved' => "ALTER TABLE goods_receipt_notes ADD COLUMN is_approved TINYINT(1) DEFAULT 0 AFTER notes",
            'goods_receipt_notes_approved_by' => "ALTER TABLE goods_receipt_notes ADD COLUMN approved_by INT NULL AFTER is_approved",
            
            // Item model self-healing columns
            'items_variations_json' => "ALTER TABLE items ADD COLUMN variations_json TEXT NULL",
            'items_image_path' => "ALTER TABLE items ADD COLUMN image_path VARCHAR(255) NULL",
            'items_additional_images' => "ALTER TABLE items ADD COLUMN additional_images TEXT NULL",
            'items_barcode' => "ALTER TABLE items ADD COLUMN barcode VARCHAR(100) NULL",
            'items_category_id' => "ALTER TABLE items ADD COLUMN category_id INT NULL",
            'items_warehouse_id' => "ALTER TABLE items ADD COLUMN warehouse_id INT NULL",
            'items_vendor_id' => "ALTER TABLE items ADD COLUMN vendor_id INT NULL",
            'items_cost_price' => "ALTER TABLE items ADD COLUMN cost_price DECIMAL(10,2) NOT NULL DEFAULT 0.00",
            'items_brand' => "ALTER TABLE items ADD COLUMN brand VARCHAR(100) NULL",
            'items_warehouse' => "ALTER TABLE items ADD COLUMN warehouse VARCHAR(100) NULL",
            'items_alert_qty' => "ALTER TABLE items ADD COLUMN alert_qty INT NOT NULL DEFAULT 5",
            'items_unit' => "ALTER TABLE items ADD COLUMN unit VARCHAR(20) NOT NULL DEFAULT 'pcs'",
            'items_status' => "ALTER TABLE items ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'active'",
            'items_weight' => "ALTER TABLE items ADD COLUMN weight VARCHAR(50) NULL",
            'items_sync_woo' => "ALTER TABLE items ADD COLUMN sync_woo TINYINT NOT NULL DEFAULT 1",
            'items_sample_code' => "ALTER TABLE items ADD COLUMN sample_code VARCHAR(100) NULL",
            'items_price' => "ALTER TABLE items ADD COLUMN price DECIMAL(10,2) NOT NULL DEFAULT 0.00",
            'items_wholesale_price' => "ALTER TABLE items ADD COLUMN wholesale_price DECIMAL(10,2) NOT NULL DEFAULT 0.00",
            'items_item_code' => "ALTER TABLE items ADD COLUMN item_code VARCHAR(100) NULL",
            'items_name' => "ALTER TABLE items ADD COLUMN name VARCHAR(255) NOT NULL DEFAULT ''",
            'items_qty' => "ALTER TABLE items ADD COLUMN qty INT NOT NULL DEFAULT 0",
            'items_description' => "ALTER TABLE items ADD COLUMN description TEXT NULL",
            'create_stock_batches' => "
                CREATE TABLE IF NOT EXISTS stock_batches (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    item_id INT NOT NULL,
                    variation_option_id INT NULL DEFAULT NULL,
                    grn_id INT NULL DEFAULT NULL,
                    quantity_received DECIMAL(15,2) NOT NULL,
                    quantity_remaining DECIMAL(15,2) NOT NULL,
                    unit_cost DECIMAL(15,2) NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX (item_id),
                    INDEX (variation_option_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ",
            'create_invoice_item_batches' => "
                CREATE TABLE IF NOT EXISTS invoice_item_batches (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    invoice_item_id INT NULL DEFAULT NULL,
                    sales_invoice_item_id INT NULL DEFAULT NULL,
                    stock_batch_id INT NOT NULL,
                    quantity DECIMAL(15,2) NOT NULL,
                    unit_cost DECIMAL(15,2) NOT NULL,
                    INDEX (invoice_item_id),
                    INDEX (sales_invoice_item_id),
                    INDEX (stock_batch_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ",
            'invoice_items_cost_at_sale' => "ALTER TABLE invoice_items ADD COLUMN cost_at_sale DECIMAL(15,2) DEFAULT 0.00",
            'sales_invoice_items_cost_at_sale' => "ALTER TABLE sales_invoice_items ADD COLUMN cost_at_sale DECIMAL(15,2) DEFAULT 0.00",
            'add_indexes_for_performance' => function(PDO $dbh) {
                $indexes = [
                    ['items', 'category_id', 'idx_items_category_id'],
                    ['items', 'vendor_id', 'idx_items_vendor_id'],
                    ['items', 'status', 'idx_items_status'],
                    ['items', 'barcode', 'idx_items_barcode'],
                    ['invoices', 'customer_id', 'idx_invoices_customer_id'],
                    ['invoices', 'rep_route_id', 'idx_invoices_rep_route_id'],
                    ['invoices', 'status', 'idx_invoices_status'],
                    ['invoice_items', 'invoice_id', 'idx_invoice_items_invoice_id'],
                    ['invoice_items', 'item_id', 'idx_invoice_items_item_id'],
                    ['rep_daily_routes', 'user_id', 'idx_rep_routes_user_id'],
                    ['customers', 'mca_id', 'idx_customers_mca_id'],
                    ['customers', 'status', 'idx_customers_status']
                ];
                foreach ($indexes as $idx) {
                    list($table, $col, $idxName) = $idx;
                    try {
                        $stmt = $dbh->prepare("SHOW INDEX FROM `$table` WHERE Key_name = :idx");
                        $stmt->execute([':idx' => $idxName]);
                        if (!$stmt->fetch()) {
                            $dbh->exec("CREATE INDEX `$idxName` ON `$table` (`$col`)");
                        }
                    } catch (PDOException $e) {
                        // Ignore if table/column does not exist
                    }
                }
                return true;
            },
            'rbac_setup' => function(PDO $dbh) {
                // 1. Create tables
                $dbh->exec("CREATE TABLE IF NOT EXISTS roles (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(100) NOT NULL UNIQUE,
                    description VARCHAR(255) NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

                $dbh->exec("CREATE TABLE IF NOT EXISTS role_permissions (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    role_id INT NOT NULL,
                    module VARCHAR(50) NOT NULL,
                    can_view TINYINT(1) DEFAULT 0,
                    can_create_edit TINYINT(1) DEFAULT 0,
                    can_delete TINYINT(1) DEFAULT 0,
                    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
                    UNIQUE KEY role_module (role_id, module)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

                $dbh->exec("CREATE TABLE IF NOT EXISTS user_roles (
                    user_id INT NOT NULL,
                    role_id INT NOT NULL,
                    PRIMARY KEY (user_id, role_id),
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

                // 2. Seed default roles
                $defaultRoles = [
                    ['Admin', 'Full System Administrator Access'],
                    ['Office Staff', 'Standard back-office operation access'],
                    ['Driver', 'Logistics and delivery application access'],
                    ['Rep (Sales Representative)', 'Mobile sales representative app access'],
                    ['Accountant', 'Full accounting, budgeting, and financial reports access']
                ];

                $roleIds = [];
                $stmt = $dbh->prepare("INSERT INTO roles (name, description) VALUES (:name, :description) ON DUPLICATE KEY UPDATE description = VALUES(description)");
                foreach ($defaultRoles as $r) {
                    $stmt->execute([':name' => $r[0], ':description' => $r[1]]);
                }

                // Get role IDs
                $stmt = $dbh->query("SELECT id, name FROM roles");
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $roleIds[strtolower($row['name'])] = $row['id'];
                }

                // Seed Admin permissions: grant view, create_edit, delete to all modules
                $modules = [
                    'crm', 'customer', 'estimate', 'sales', 'creditnote', 'dunning', 'discount',
                    'reptracking', 'delivery', 'territory', 'inventory', 'category', 'variation',
                    'warehouse', 'supplier', 'purchase', 'grn', 'supplier_return', 'expenses',
                    'hrm', 'project', 'vehicle', 'cheque', 'accounting', 'customerpayment',
                    'supplierpayment', 'asset', 'report', 'ecommerce', 'settings', 'user', 'tax',
                    'paymentterm', 'audit'
                ];

                if (isset($roleIds['admin'])) {
                    $adminId = $roleIds['admin'];
                    $permStmt = $dbh->prepare("INSERT INTO role_permissions (role_id, module, can_view, can_create_edit, can_delete) 
                                               VALUES (:role_id, :module, 1, 1, 1) 
                                               ON DUPLICATE KEY UPDATE can_view = 1, can_create_edit = 1, can_delete = 1");
                    foreach ($modules as $m) {
                        $permStmt->execute([':role_id' => $adminId, ':module' => $m]);
                    }
                }

                // Seed Accountant permissions
                if (isset($roleIds['accountant'])) {
                    $accId = $roleIds['accountant'];
                    $accPerms = [
                        'accounting' => [1, 1, 1],
                        'customerpayment' => [1, 1, 1],
                        'supplierpayment' => [1, 1, 1],
                        'expenses' => [1, 1, 1],
                        'report' => [1, 1, 0],
                        'sales' => [1, 1, 0]
                    ];
                    $permStmt = $dbh->prepare("INSERT INTO role_permissions (role_id, module, can_view, can_create_edit, can_delete) 
                                               VALUES (:role_id, :module, :can_view, :can_create_edit, :can_delete) 
                                               ON DUPLICATE KEY UPDATE can_view = VALUES(can_view), can_create_edit = VALUES(can_create_edit), can_delete = VALUES(can_delete)");
                    foreach ($accPerms as $m => $p) {
                        $permStmt->execute([':role_id' => $accId, ':module' => $m, ':can_view' => $p[0], ':can_create_edit' => $p[1], ':can_delete' => $p[2]]);
                    }
                }

                // Seed Representative permissions
                if (isset($roleIds['rep (sales representative)'])) {
                    $repId = $roleIds['rep (sales representative)'];
                    $repPerms = [
                        'crm' => [1, 1, 0],
                        'customer' => [1, 1, 0],
                        'estimate' => [1, 1, 0],
                        'sales' => [1, 1, 0],
                        'reptracking' => [1, 1, 0]
                    ];
                    $permStmt = $dbh->prepare("INSERT INTO role_permissions (role_id, module, can_view, can_create_edit, can_delete) 
                                               VALUES (:role_id, :module, :can_view, :can_create_edit, :can_delete) 
                                               ON DUPLICATE KEY UPDATE can_view = VALUES(can_view), can_create_edit = VALUES(can_create_edit), can_delete = VALUES(can_delete)");
                    foreach ($repPerms as $m => $p) {
                        $permStmt->execute([':role_id' => $repId, ':module' => $m, ':can_view' => $p[0], ':can_create_edit' => $p[1], ':can_delete' => $p[2]]);
                    }
                }

                // Seed Driver permissions
                if (isset($roleIds['driver'])) {
                    $driverId = $roleIds['driver'];
                    $driverPerms = [
                        'delivery' => [1, 1, 0]
                    ];
                    $permStmt = $dbh->prepare("INSERT INTO role_permissions (role_id, module, can_view, can_create_edit, can_delete) 
                                               VALUES (:role_id, :module, :can_view, :can_create_edit, :can_delete) 
                                               ON DUPLICATE KEY UPDATE can_view = VALUES(can_view), can_create_edit = VALUES(can_create_edit), can_delete = VALUES(can_delete)");
                    foreach ($driverPerms as $m => $p) {
                        $permStmt->execute([':role_id' => $driverId, ':module' => $m, ':can_view' => $p[0], ':can_create_edit' => $p[1], ':can_delete' => $p[2]]);
                    }
                }

                // 3. Migrate existing users from `users.role` to `user_roles`
                $userStmt = $dbh->query("SELECT id, role FROM users");
                $userRolesStmt = $dbh->prepare("INSERT IGNORE INTO user_roles (user_id, role_id) VALUES (:user_id, :role_id)");
                while ($user = $userStmt->fetch(PDO::FETCH_ASSOC)) {
                    $roleName = strtolower(trim($user['role']));
                    $targetRoleId = null;
                    if ($roleName === 'admin') {
                        $targetRoleId = $roleIds['admin'] ?? null;
                    } elseif ($roleName === 'accountant') {
                        $targetRoleId = $roleIds['accountant'] ?? null;
                    } elseif ($roleName === 'driver') {
                        $targetRoleId = $roleIds['driver'] ?? null;
                    } elseif ($roleName === 'rep') {
                        $targetRoleId = $roleIds['rep (sales representative)'] ?? null;
                    } else {
                        $targetRoleId = $roleIds['office staff'] ?? null;
                    }

                    if ($targetRoleId) {
                        $userRolesStmt->execute([':user_id' => $user['id'], ':role_id' => $targetRoleId]);
                    }
                }
                return true;
            },
            'ecommerce_stationery_upgrade' => function(PDO $dbh) {
                // 1. Create ecommerce_settings table
                $dbh->exec("CREATE TABLE IF NOT EXISTS ecommerce_settings (
                    `key` VARCHAR(100) PRIMARY KEY,
                    `value` TEXT NULL,
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

                // Seed default settings if empty
                $stmt = $dbh->query("SELECT COUNT(*) FROM ecommerce_settings");
                if ($stmt->fetchColumn() == 0) {
                    $defaults = [
                        'store_name' => 'Curtiss Stationery Store',
                        'logo' => '',
                        'favicon' => '',
                        'contact_email' => 'support@curtissstationery.com',
                        'contact_phone' => '+94 11 123 4567',
                        'contact_address' => 'No. 45, Galle Road, Colombo 03, Sri Lanka',
                        'about_us' => 'Your premium stationery and office equipment partner in Sri Lanka.',
                        'terms_conditions' => 'Standard business terms apply.',
                        'privacy_policy' => 'We value your privacy.',
                        'return_policy' => '14 days return policy for unused items.',
                        'delivery_policy' => 'Delivery within 2-3 business days islandwide.',
                        'social_facebook' => 'https://facebook.com/curtiss',
                        'social_instagram' => 'https://instagram.com/curtiss',
                        'social_twitter' => 'https://twitter.com/curtiss',
                        'seo_title' => 'Curtiss Stationery - Premium Office & School Supplies',
                        'seo_meta_desc' => 'Buy school and office stationery, files, pens, paper products and office accessories online in Sri Lanka at retail and wholesale prices.',
                        'seo_keywords' => 'stationery, office supplies, school supplies, pens, notebooks, files, wholesale stationery Sri Lanka',
                        'google_analytics' => '',
                        'meta_tags' => ''
                    ];
                    $ins = $dbh->prepare("INSERT INTO ecommerce_settings (`key`, `value`) VALUES (:key, :val)");
                    foreach ($defaults as $k => $v) {
                        $ins->execute([':key' => $k, ':val' => $v]);
                    }
                }

                // 2. Create homepage_sections table
                $dbh->exec("CREATE TABLE IF NOT EXISTS homepage_sections (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    section_name VARCHAR(100) UNIQUE NOT NULL,
                    title VARCHAR(150) NOT NULL,
                    is_enabled TINYINT DEFAULT 1,
                    sort_order INT DEFAULT 0,
                    config JSON NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

                $stmt = $dbh->query("SELECT COUNT(*) FROM homepage_sections");
                if ($stmt->fetchColumn() == 0) {
                    $sections = [
                        ['hero_banner', 'Hero Banner', 1, 1],
                        ['featured_categories', 'Featured Categories', 1, 2],
                        ['featured_products', 'Featured Products', 1, 3],
                        ['new_arrivals', 'New Arrivals', 1, 4],
                        ['best_sellers', 'Best Sellers', 1, 5],
                        ['promotional_banner', 'Promotional Banner', 1, 6],
                        ['school_essentials', 'School Essentials', 1, 7],
                        ['office_essentials', 'Office Essentials', 1, 8],
                        ['art_supplies', 'Art Supplies', 1, 9],
                        ['special_offers', 'Special Offers', 1, 10],
                        ['brands', 'Brands Showcase', 1, 11],
                        ['customer_testimonials', 'Customer Testimonials', 1, 12],
                        ['blog_articles', 'Blog Articles', 1, 13],
                        ['newsletter_subscription', 'Newsletter Subscription', 1, 14]
                    ];
                    $ins = $dbh->prepare("INSERT INTO homepage_sections (section_name, title, is_enabled, sort_order) VALUES (?, ?, ?, ?)");
                    foreach ($sections as $s) {
                        $ins->execute($s);
                    }
                }

                // 3. Create ecommerce_banners table
                $dbh->exec("CREATE TABLE IF NOT EXISTS ecommerce_banners (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    banner_type ENUM('desktop', 'mobile', 'popup', 'promotional') NOT NULL,
                    image_path VARCHAR(255) NOT NULL,
                    title VARCHAR(150) NULL,
                    description TEXT NULL,
                    button_text VARCHAR(50) NULL,
                    button_link VARCHAR(255) NULL,
                    start_date DATE NULL,
                    end_date DATE NULL,
                    is_active TINYINT DEFAULT 1,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

                // 4. Add E-Commerce configurations to items table
                $cols = [
                    'is_published' => 'TINYINT DEFAULT 1',
                    'is_featured' => 'TINYINT DEFAULT 0',
                    'is_bestseller' => 'TINYINT DEFAULT 0',
                    'is_new_arrival' => 'TINYINT DEFAULT 0',
                    'is_clearance' => 'TINYINT DEFAULT 0',
                    'is_special_offer' => 'TINYINT DEFAULT 0',
                    'online_stock_visible' => 'TINYINT DEFAULT 1'
                ];
                foreach ($cols as $col => $type) {
                    try {
                        $dbh->exec("ALTER TABLE items ADD COLUMN $col $type");
                    } catch (PDOException $e) {
                        // Suppress if column exists
                    }
                }

                // 5. Add parent_id and other columns to item_categories
                $catCols = [
                    'parent_id' => 'INT NULL',
                    'icon' => 'VARCHAR(100) NULL',
                    'image_path' => 'VARCHAR(255) NULL',
                    'seo_url' => 'VARCHAR(255) NULL',
                    'is_featured' => 'TINYINT DEFAULT 0'
                ];
                foreach ($catCols as $col => $type) {
                    try {
                        $dbh->exec("ALTER TABLE item_categories ADD COLUMN $col $type");
                    } catch (PDOException $e) {
                        // Suppress if column exists
                    }
                }

                // 6. Create ecommerce_reviews table
                $dbh->exec("CREATE TABLE IF NOT EXISTS ecommerce_reviews (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    item_id INT NOT NULL,
                    customer_id INT NULL,
                    customer_name VARCHAR(100) NOT NULL,
                    rating INT NOT NULL DEFAULT 5,
                    review_text TEXT NOT NULL,
                    status ENUM('pending', 'approved', 'rejected', 'hidden') DEFAULT 'pending',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

                // 7. Create ecommerce_blog_posts table
                $dbh->exec("CREATE TABLE IF NOT EXISTS ecommerce_blog_posts (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    title VARCHAR(200) NOT NULL,
                    content TEXT NOT NULL,
                    category VARCHAR(100) NULL,
                    author VARCHAR(100) NULL,
                    image_path VARCHAR(255) NULL,
                    is_featured TINYINT DEFAULT 0,
                    seo_url VARCHAR(200) NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

                // Seed default blog posts if empty
                $stmt = $dbh->query("SELECT COUNT(*) FROM ecommerce_blog_posts");
                if ($stmt->fetchColumn() == 0) {
                    $dbh->exec("INSERT INTO ecommerce_blog_posts (title, content, category, author, is_featured, seo_url) VALUES 
                        ('Essential Stationery for Modern Offices', 'Running an office smoothly requires the right set of tools. From standard files and binders to quality pens and desk organizers, having a reliable stationery supply keeps operations seamless and professional. In this post, we discuss the top 10 items every modern office should have in stock.', 'Office Guide', 'Admin', 1, 'essential-stationery-modern-offices'),
                        ('Choosing the Right Art Supplies for Kids', 'Art and craft activities are crucial for a child\'s development. However, selecting the right paints, pencils, and papers that are non-toxic, easy to wash, and fun to use can be challenging. Here is our comprehensive guide to child-friendly art supplies.', 'School & Art', 'Admin', 0, 'choosing-right-art-supplies-kids')");
                }

                // 8. Create ecommerce_wishlist table
                $dbh->exec("CREATE TABLE IF NOT EXISTS ecommerce_wishlist (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    customer_id INT NOT NULL,
                    customer_type ENUM('retail', 'wholesaler') NOT NULL,
                    item_id INT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

                // 9. Create ecommerce_saved_carts table
                $dbh->exec("CREATE TABLE IF NOT EXISTS ecommerce_saved_carts (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    customer_id INT NOT NULL,
                    customer_type ENUM('retail', 'wholesaler') NOT NULL,
                    cart_data LONGTEXT NOT NULL,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY (customer_id, customer_type)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

                // 10. Create ecommerce_returns table
                $dbh->exec("CREATE TABLE IF NOT EXISTS ecommerce_returns (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    sales_order_id INT NOT NULL,
                    customer_id INT NOT NULL,
                    customer_type ENUM('retail', 'wholesaler') NOT NULL,
                    reason VARCHAR(255) NOT NULL,
                    details TEXT NULL,
                    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (sales_order_id) REFERENCES sales_orders(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

                // 11. Create ecommerce_coupons table
                $dbh->exec("CREATE TABLE IF NOT EXISTS ecommerce_coupons (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    code VARCHAR(50) UNIQUE NOT NULL,
                    type ENUM('percent', 'fixed') NOT NULL DEFAULT 'percent',
                    value DECIMAL(10,2) NOT NULL,
                    min_spend DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                    expiry_date DATE NULL,
                    is_active TINYINT DEFAULT 1,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

                // Seed a default coupon code if empty
                $stmt = $dbh->query("SELECT COUNT(*) FROM ecommerce_coupons");
                if ($stmt->fetchColumn() == 0) {
                    $dbh->exec("INSERT INTO ecommerce_coupons (code, type, value, min_spend, expiry_date, is_active) VALUES 
                        ('WELCOME10', 'percent', 10.00, 1000.00, DATE_ADD(CURRENT_DATE, INTERVAL 1 YEAR), 1)");
                }

                // 12. Create ecommerce_visitors table
                $dbh->exec("CREATE TABLE IF NOT EXISTS ecommerce_visitors (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    ip_address VARCHAR(45) NOT NULL,
                    visit_date DATE NOT NULL,
                    page_views INT DEFAULT 1,
                    UNIQUE KEY (ip_address, visit_date)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

                return true;
            }
        ];
    }

    /**
     * Executes pending migrations.
     */
    public static function run(PDO $dbh) {
        if (self::$initialized) {
            return;
        }

        // 1. Ensure migrations table exists
        try {
            $dbh->exec("
                CREATE TABLE IF NOT EXISTS migrations (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    migration VARCHAR(255) UNIQUE NOT NULL,
                    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        } catch (PDOException $e) {
            // If we can't even create migrations table, return silently to preserve system uptime
            return;
        }

        // 2. Fetch already executed migrations
        $executed = [];
        try {
            $stmt = $dbh->query("SELECT migration FROM migrations");
            if ($stmt) {
                $executed = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
            }
        } catch (PDOException $e) {
            return;
        }

        // 3. Run pending migrations in order
        $migrations = self::getMigrations();
        $anyRan = false;
        foreach ($migrations as $name => $sql) {
            if (in_array($name, $executed)) {
                continue;
            }

            $success = false;
            try {
                if (is_callable($sql)) {
                    $success = call_user_func($sql, $dbh);
                } else {
                    $dbh->exec($sql);
                    $success = true;
                }
            } catch (PDOException $e) {
                // If migration fails because column/table already exists, we count it as success (previously manually run)
                // MySQL Error Codes: 1050 (table exists), 1060 (column exists), 1061 (duplicate key)
                $errorCode = $e->errorInfo[1] ?? 0;
                if (in_array($errorCode, [1050, 1060, 1061])) {
                    $success = true;
                }
            }

            if ($success) {
                $anyRan = true;
                try {
                    $stmt = $dbh->prepare("INSERT INTO migrations (migration) VALUES (:migration)");
                    $stmt->execute([':migration' => $name]);
                } catch (PDOException $e) {
                    // Ignore duplicate key or other errors inserting log
                }
            }
        }

        if ($anyRan) {
            if (class_exists('Cache')) {
                Cache::clear();
            }
        }

        self::$initialized = true;
    }
}
