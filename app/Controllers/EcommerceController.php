<?php
class EcommerceController extends Controller {
    private $db;

    public function __construct() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . APP_URL . '/auth/login');
            exit;
        }
        // Admin or Manager access only
        if ($_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Manager') {
            die("Access Denied: You do not have permission to access E-Commerce operations.");
        }
        $this->db = new Database();
    }

    /**
     * E-Commerce Dashboard
     */
    public function index() {
        // Today's Date
        $today = date('Y-m-d');

        // Order counters
        $this->db->query("SELECT COUNT(*) as count FROM sales_orders WHERE order_number LIKE 'ECO-%' AND DATE(created_at) = :today");
        $this->db->bind(':today', $today);
        $todayOrders = $this->db->single()->count ?? 0;

        $this->db->query("SELECT COUNT(*) as count FROM sales_orders WHERE order_number LIKE 'ECO-%' AND LOWER(status) = 'pending'");
        $pendingOrders = $this->db->single()->count ?? 0;

        $this->db->query("SELECT COUNT(*) as count FROM sales_orders WHERE order_number LIKE 'ECO-%' AND LOWER(status) = 'processing'");
        $processingOrders = $this->db->single()->count ?? 0;

        $this->db->query("SELECT COUNT(*) as count FROM sales_orders WHERE order_number LIKE 'ECO-%' AND LOWER(status) IN ('delivered', 'completed')");
        $completedOrders = $this->db->single()->count ?? 0;

        $this->db->query("SELECT COUNT(*) as count FROM sales_orders WHERE order_number LIKE 'ECO-%' AND LOWER(status) = 'cancelled'");
        $cancelledOrders = $this->db->single()->count ?? 0;

        // Sales Metrics
        $this->db->query("SELECT SUM(grand_total) as total FROM sales_orders WHERE order_number LIKE 'ECO-%' AND LOWER(status) != 'cancelled'");
        $totalSales = $this->db->single()->total ?? 0.00;

        $this->db->query("SELECT AVG(grand_total) as avg_val FROM sales_orders WHERE order_number LIKE 'ECO-%' AND LOWER(status) != 'cancelled'");
        $avgOrderValue = $this->db->single()->avg_val ?? 0.00;

        // Top Selling Products
        $this->db->query("SELECT name, sku, SUM(qty) as total_qty, SUM(total) as total_sales 
                          FROM sales_order_items soi 
                          JOIN sales_orders so ON soi.sales_order_id = so.id 
                          WHERE so.order_number LIKE 'ECO-%' 
                          GROUP BY sku, name 
                          ORDER BY total_qty DESC 
                          LIMIT 5");
        $topProducts = $this->db->resultSet() ?: [];

        // Low Stock Products
        $this->db->query("SELECT name, item_code as sku, qty, unit, alert_qty 
                          FROM items 
                          WHERE status = 'active' AND qty <= alert_qty 
                          ORDER BY qty ASC 
                          LIMIT 5");
        $lowStock = $this->db->resultSet() ?: [];

        // Recent Customers
        $this->db->query("SELECT name, email, phone, city, created_at 
                          FROM ecommerce_retail_customers 
                          ORDER BY created_at DESC 
                          LIMIT 5");
        $recentCustomers = $this->db->resultSet() ?: [];

        // Recent Reviews
        $this->db->query("SELECT r.*, i.name as item_name 
                          FROM ecommerce_reviews r 
                          JOIN items i ON r.item_id = i.id 
                          ORDER BY r.created_at DESC 
                          LIMIT 5");
        $recentReviews = $this->db->resultSet() ?: [];

        // Abandoned Carts (Saved carts not converted to orders)
        $this->db->query("SELECT esc.*, 
                            CASE 
                                WHEN esc.customer_type = 'wholesaler' THEN (SELECT name FROM customers WHERE id = esc.customer_id)
                                WHEN esc.customer_type = 'retail' THEN (SELECT name FROM ecommerce_retail_customers WHERE id = esc.customer_id)
                            END as customer_name
                          FROM ecommerce_saved_carts esc 
                          ORDER BY esc.updated_at DESC 
                          LIMIT 5");
        $abandonedCarts = $this->db->resultSet() ?: [];

        // Visitor Stats
        $this->db->query("SELECT SUM(page_views) as page_views, COUNT(DISTINCT ip_address) as visitors FROM ecommerce_visitors");
        $visitorStats = $this->db->single();
        $totalViews = $visitorStats->page_views ?? 0;
        $totalVisitors = $visitorStats->visitors ?? 0;

        $data = [
            'title' => 'E-Commerce Dashboard',
            'content_view' => 'ecommerce/dashboard',
            'todayOrders' => $todayOrders,
            'pendingOrders' => $pendingOrders,
            'processingOrders' => $processingOrders,
            'completedOrders' => $completedOrders,
            'cancelledOrders' => $cancelledOrders,
            'totalSales' => $totalSales,
            'avgOrderValue' => $avgOrderValue,
            'topProducts' => $topProducts,
            'lowStock' => $lowStock,
            'recentCustomers' => $recentCustomers,
            'recentReviews' => $recentReviews,
            'abandonedCarts' => $abandonedCarts,
            'totalViews' => $totalViews,
            'totalVisitors' => $totalVisitors
        ];

        $this->view('layouts/main', $data);
    }

    /**
     * E-Commerce Storefront Website Settings
     */
    public function settings() {
        $success = '';
        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $this->db->beginTransaction();
                
                foreach ($_POST['settings'] as $key => $value) {
                    $this->db->query("INSERT INTO ecommerce_settings (`key`, `value`) 
                                      VALUES (:key, :val) 
                                      ON DUPLICATE KEY UPDATE `value` = :val2");
                    $this->db->bind(':key', $key);
                    $this->db->bind(':val', $value);
                    $this->db->bind(':val2', $value);
                    $this->db->execute();
                }

                // Handle logo upload
                if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                    $logoName = 'logo_' . time() . '_' . $_FILES['logo']['name'];
                    $uploadPath = '../public/uploads/store/' . $logoName;
                    if (!is_dir('../public/uploads/store/')) {
                        mkdir('../public/uploads/store/', 0777, true);
                    }
                    if (move_uploaded_file($_FILES['logo']['tmp_name'], $uploadPath)) {
                        $this->db->query("INSERT INTO ecommerce_settings (`key`, `value`) VALUES ('logo', :val) ON DUPLICATE KEY UPDATE `value` = :val");
                        $this->db->bind(':val', $logoName);
                        $this->db->execute();
                    }
                }

                // Handle favicon upload
                if (isset($_FILES['favicon']) && $_FILES['favicon']['error'] === UPLOAD_ERR_OK) {
                    $faviconName = 'favicon_' . time() . '_' . $_FILES['favicon']['name'];
                    $uploadPath = '../public/uploads/store/' . $faviconName;
                    if (move_uploaded_file($_FILES['favicon']['tmp_name'], $uploadPath)) {
                        $this->db->query("INSERT INTO ecommerce_settings (`key`, `value`) VALUES ('favicon', :val) ON DUPLICATE KEY UPDATE `value` = :val");
                        $this->db->bind(':val', $faviconName);
                        $this->db->execute();
                    }
                }

                $this->db->commit();
                $success = "Store settings updated successfully.";
            } catch (Exception $e) {
                $this->db->rollBack();
                $error = "Error updating settings: " . $e->getMessage();
            }
        }

        // Fetch settings
        $this->db->query("SELECT * FROM ecommerce_settings");
        $rows = $this->db->resultSet() ?: [];
        $settings = [];
        foreach ($rows as $row) {
            $settings[$row->key] = $row->value;
        }

        $data = [
            'title' => 'E-Commerce Website Settings',
            'content_view' => 'ecommerce/settings',
            'settings' => $settings,
            'success' => $success,
            'error' => $error
        ];

        $this->view('layouts/main', $data);
    }

    /**
     * Homepage Builder Sections Config
     */
    public function homepage_builder() {
        $success = '';
        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
            $action = $_POST['action'];

            if ($action === 'update_sections') {
                try {
                    $this->db->beginTransaction();
                    $sections = $_POST['sections'] ?? [];

                    foreach ($sections as $id => $secData) {
                        $isEnabled = isset($secData['is_enabled']) ? 1 : 0;
                        $sortOrder = intval($secData['sort_order']);
                        
                        // Parse optional configuration fields based on section
                        $configJson = null;
                        if (isset($secData['config'])) {
                            $configJson = json_encode($secData['config']);
                        }

                        $this->db->query("UPDATE homepage_sections 
                                          SET is_enabled = :enabled, sort_order = :sort, config = :config 
                                          WHERE id = :id");
                        $this->db->bind(':enabled', $isEnabled);
                        $this->db->bind(':sort', $sortOrder);
                        $this->db->bind(':config', $configJson);
                        $this->db->bind(':id', $id);
                        $this->db->execute();
                    }

                    $this->db->commit();
                    $success = "Homepage layout structure updated successfully.";
                } catch (Exception $e) {
                    $this->db->rollBack();
                    $error = "Failed to update layout: " . $e->getMessage();
                }
            }
        }

        // Fetch sections sorted by sort_order
        $this->db->query("SELECT * FROM homepage_sections ORDER BY sort_order ASC");
        $sections = $this->db->resultSet() ?: [];

        $data = [
            'title' => 'Homepage Layout Builder',
            'content_view' => 'ecommerce/homepage_builder',
            'sections' => $sections,
            'success' => $success,
            'error' => $error
        ];

        $this->view('layouts/main', $data);
    }

    /**
     * Storefront Banners
     */
    public function banners() {
        $success = '';
        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
            $action = $_POST['action'];

            if ($action === 'add') {
                $type = $_POST['banner_type'];
                $title = trim($_POST['title'] ?? '');
                $desc = trim($_POST['description'] ?? '');
                $btnText = trim($_POST['button_text'] ?? '');
                $btnLink = trim($_POST['button_link'] ?? '');
                $start = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
                $end = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
                $isActive = isset($_POST['is_active']) ? 1 : 0;

                // File Upload
                if (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] === UPLOAD_ERR_OK) {
                    $filename = 'banner_' . time() . '_' . $_FILES['banner_image']['name'];
                    $dest = '../public/uploads/banners/' . $filename;
                    if (!is_dir('../public/uploads/banners/')) {
                        mkdir('../public/uploads/banners/', 0777, true);
                    }

                    if (move_uploaded_file($_FILES['banner_image']['tmp_name'], $dest)) {
                        $this->db->query("INSERT INTO ecommerce_banners 
                                          (banner_type, image_path, title, description, button_text, button_link, start_date, end_date, is_active) 
                                          VALUES (:type, :img, :title, :desc, :btn, :link, :start, :end, :active)");
                        $this->db->bind(':type', $type);
                        $this->db->bind(':img', $filename);
                        $this->db->bind(':title', $title);
                        $this->db->bind(':desc', $desc);
                        $this->db->bind(':btn', $btnText);
                        $this->db->bind(':link', $btnLink);
                        $this->db->bind(':start', $start);
                        $this->db->bind(':end', $end);
                        $this->db->bind(':active', $isActive);

                        if ($this->db->execute()) {
                            $success = "New banner added successfully.";
                        } else {
                            $error = "Database insertion failed.";
                        }
                    } else {
                        $error = "Failed to upload image.";
                    }
                } else {
                    $error = "Banner image is required.";
                }
            } elseif ($action === 'edit') {
                $id = intval($_POST['banner_id']);
                $type = $_POST['banner_type'];
                $title = trim($_POST['title'] ?? '');
                $desc = trim($_POST['description'] ?? '');
                $btnText = trim($_POST['button_text'] ?? '');
                $btnLink = trim($_POST['button_link'] ?? '');
                $start = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
                $end = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
                $isActive = isset($_POST['is_active']) ? 1 : 0;

                try {
                    $this->db->beginTransaction();

                    $filename = '';
                    if (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] === UPLOAD_ERR_OK) {
                        $filename = 'banner_' . time() . '_' . $_FILES['banner_image']['name'];
                        $dest = '../public/uploads/banners/' . $filename;
                        if (move_uploaded_file($_FILES['banner_image']['tmp_name'], $dest)) {
                            // Update image path
                            $this->db->query("UPDATE ecommerce_banners SET image_path = :img WHERE id = :id");
                            $this->db->bind(':img', $filename);
                            $this->db->bind(':id', $id);
                            $this->db->execute();
                        }
                    }

                    $this->db->query("UPDATE ecommerce_banners 
                                      SET banner_type = :type, title = :title, description = :desc, 
                                          button_text = :btn, button_link = :link, start_date = :start, 
                                          end_date = :end, is_active = :active 
                                      WHERE id = :id");
                    $this->db->bind(':type', $type);
                    $this->db->bind(':title', $title);
                    $this->db->bind(':desc', $desc);
                    $this->db->bind(':btn', $btnText);
                    $this->db->bind(':link', $btnLink);
                    $this->db->bind(':start', $start);
                    $this->db->bind(':end', $end);
                    $this->db->bind(':active', $isActive);
                    $this->db->bind(':id', $id);
                    $this->db->execute();

                    $this->db->commit();
                    $success = "Banner configuration updated successfully.";
                } catch (Exception $ex) {
                    $this->db->rollBack();
                    $error = "Update failed: " . $ex->getMessage();
                }
            } elseif ($action === 'delete') {
                $id = intval($_POST['banner_id']);
                $this->db->query("DELETE FROM ecommerce_banners WHERE id = :id");
                $this->db->bind(':id', $id);
                if ($this->db->execute()) {
                    $success = "Banner deleted successfully.";
                } else {
                    $error = "Delete query failed.";
                }
            }
        }

        // Fetch banners
        $this->db->query("SELECT * FROM ecommerce_banners ORDER BY created_at DESC");
        $banners = $this->db->resultSet() ?: [];

        $data = [
            'title' => 'Banner Management',
            'content_view' => 'ecommerce/banners',
            'banners' => $banners,
            'success' => $success,
            'error' => $error
        ];

        $this->view('layouts/main', $data);
    }

    /**
     * E-Commerce Products Configurations
     */
    public function products() {
        $success = '';
        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
            $action = $_POST['action'];

            if ($action === 'configure') {
                $itemId = intval($_POST['item_id']);
                $isPublished = isset($_POST['is_published']) ? 1 : 0;
                $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
                $isBestseller = isset($_POST['is_bestseller']) ? 1 : 0;
                $isNewArrival = isset($_POST['is_new_arrival']) ? 1 : 0;
                $isClearance = isset($_POST['is_clearance']) ? 1 : 0;
                $isSpecialOffer = isset($_POST['is_special_offer']) ? 1 : 0;
                $stockVisible = isset($_POST['online_stock_visible']) ? 1 : 0;
                
                $price = floatval($_POST['price']);
                $wholesalePrice = floatval($_POST['wholesale_price']);

                $this->db->query("UPDATE items 
                                  SET is_published = :pub, is_featured = :feat, is_bestseller = :best, 
                                      is_new_arrival = :new, is_clearance = :clear, is_special_offer = :special, 
                                      online_stock_visible = :stock, price = :price, wholesale_price = :ws_price 
                                  WHERE id = :id");
                $this->db->bind(':pub', $isPublished);
                $this->db->bind(':feat', $isFeatured);
                $this->db->bind(':best', $isBestseller);
                $this->db->bind(':new', $isNewArrival);
                $this->db->bind(':clear', $isClearance);
                $this->db->bind(':special', $isSpecialOffer);
                $this->db->bind(':stock', $stockVisible);
                $this->db->bind(':price', $price);
                $this->db->bind(':ws_price', $wholesalePrice);
                $this->db->bind(':id', $itemId);

                if ($this->db->execute()) {
                    $success = "Product configuration saved successfully.";
                } else {
                    $error = "Failed to update item settings.";
                }
            }
        }

        // Fetch products with their category details
        $this->db->query("SELECT i.*, c.name as category_name 
                          FROM items i 
                          LEFT JOIN item_categories c ON i.category_id = c.id 
                          ORDER BY i.name ASC");
        $products = $this->db->resultSet() ?: [];

        $data = [
            'title' => 'Product Catalogue Settings',
            'content_view' => 'ecommerce/products',
            'products' => $products,
            'success' => $success,
            'error' => $error
        ];

        $this->view('layouts/main', $data);
    }

    /**
     * Categories Hierarchy & SEO
     */
    public function categories() {
        $success = '';
        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
            $action = $_POST['action'];

            if ($action === 'save_extra') {
                $id = intval($_POST['category_id']);
                $parentId = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null;
                $icon = trim($_POST['icon'] ?? '');
                $seoUrl = trim($_POST['seo_url'] ?? '');
                $isFeatured = isset($_POST['is_featured']) ? 1 : 0;

                try {
                    $this->db->beginTransaction();
                    
                    // Upload Image
                    $imagePath = '';
                    if (isset($_FILES['cat_image']) && $_FILES['cat_image']['error'] === UPLOAD_ERR_OK) {
                        $imagePath = 'cat_' . time() . '_' . $_FILES['cat_image']['name'];
                        $dest = '../public/uploads/categories/' . $imagePath;
                        if (!is_dir('../public/uploads/categories/')) {
                            mkdir('../public/uploads/categories/', 0777, true);
                        }
                        if (move_uploaded_file($_FILES['cat_image']['tmp_name'], $dest)) {
                            $this->db->query("UPDATE item_categories SET image_path = :img WHERE id = :id");
                            $this->db->bind(':img', $imagePath);
                            $this->db->bind(':id', $id);
                            $this->db->execute();
                        }
                    }

                    $this->db->query("UPDATE item_categories 
                                      SET parent_id = :parent, icon = :icon, seo_url = :seo, is_featured = :feat 
                                      WHERE id = :id");
                    $this->db->bind(':parent', $parentId);
                    $this->db->bind(':icon', $icon);
                    $this->db->bind(':seo', $seoUrl);
                    $this->db->bind(':feat', $isFeatured);
                    $this->db->bind(':id', $id);
                    $this->db->execute();

                    $this->db->commit();
                    $success = "Category settings saved successfully.";
                } catch (Exception $ex) {
                    $this->db->rollBack();
                    $error = "Error updating category: " . $ex->getMessage();
                }
            }
        }

        // Fetch all categories
        $this->db->query("SELECT c.*, p.name as parent_name 
                          FROM item_categories c 
                          LEFT JOIN item_categories p ON c.parent_id = p.id 
                          ORDER BY c.name ASC");
        $categories = $this->db->resultSet() ?: [];

        $data = [
            'title' => 'Product Categories Structure',
            'content_view' => 'ecommerce/categories',
            'categories' => $categories,
            'success' => $success,
            'error' => $error
        ];

        $this->view('layouts/main', $data);
    }

    /**
     * Discount Coupons
     */
    public function coupons() {
        $success = '';
        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
            $action = $_POST['action'];

            if ($action === 'add') {
                $code = strtoupper(trim($_POST['code']));
                $type = $_POST['type'];
                $val = floatval($_POST['value']);
                $minSpend = floatval($_POST['min_spend']);
                $expiry = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
                $active = isset($_POST['is_active']) ? 1 : 0;

                $this->db->query("INSERT INTO ecommerce_coupons (code, type, value, min_spend, expiry_date, is_active) 
                                  VALUES (:code, :type, :val, :min, :expiry, :active)");
                $this->db->bind(':code', $code);
                $this->db->bind(':type', $type);
                $this->db->bind(':val', $val);
                $this->db->bind(':min', $minSpend);
                $this->db->bind(':expiry', $expiry);
                $this->db->bind(':active', $active);

                if ($this->db->execute()) {
                    $success = "Discount coupon rule generated successfully.";
                } else {
                    $error = "Coupon generation failed.";
                }
            } elseif ($action === 'edit') {
                $id = intval($_POST['coupon_id']);
                $code = strtoupper(trim($_POST['code']));
                $type = $_POST['type'];
                $val = floatval($_POST['value']);
                $minSpend = floatval($_POST['min_spend']);
                $expiry = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
                $active = isset($_POST['is_active']) ? 1 : 0;

                $this->db->query("UPDATE ecommerce_coupons 
                                  SET code = :code, type = :type, value = :val, min_spend = :min, 
                                      expiry_date = :expiry, is_active = :active 
                                  WHERE id = :id");
                $this->db->bind(':code', $code);
                $this->db->bind(':type', $type);
                $this->db->bind(':val', $val);
                $this->db->bind(':min', $minSpend);
                $this->db->bind(':expiry', $expiry);
                $this->db->bind(':active', $active);
                $this->db->bind(':id', $id);

                if ($this->db->execute()) {
                    $success = "Coupon details updated.";
                } else {
                    $error = "Failed to update coupon details.";
                }
            } elseif ($action === 'delete') {
                $id = intval($_POST['coupon_id']);
                $this->db->query("DELETE FROM ecommerce_coupons WHERE id = :id");
                $this->db->bind(':id', $id);
                if ($this->db->execute()) {
                    $success = "Coupon deleted successfully.";
                } else {
                    $error = "Failed to delete coupon.";
                }
            }
        }

        // Fetch coupons
        $this->db->query("SELECT * FROM ecommerce_coupons ORDER BY created_at DESC");
        $coupons = $this->db->resultSet() ?: [];

        $data = [
            'title' => 'Coupon Code Management',
            'content_view' => 'ecommerce/coupons',
            'coupons' => $coupons,
            'success' => $success,
            'error' => $error
        ];

        $this->view('layouts/main', $data);
    }

    /**
     * Customer Product Reviews Approval
     */
    public function reviews() {
        $success = '';
        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
            $action = $_POST['action'];
            $reviewId = intval($_POST['review_id']);

            if ($action === 'approve') {
                $this->db->query("UPDATE ecommerce_reviews SET status = 'approved' WHERE id = :id");
                $this->db->bind(':id', $reviewId);
                if ($this->db->execute()) {
                    $success = "Review approved and published to website.";
                } else {
                    $error = "Failed to approve review.";
                }
            } elseif ($action === 'reject') {
                $this->db->query("UPDATE ecommerce_reviews SET status = 'rejected' WHERE id = :id");
                $this->db->bind(':id', $reviewId);
                if ($this->db->execute()) {
                    $success = "Review rejected successfully.";
                } else {
                    $error = "Failed to reject review.";
                }
            } elseif ($action === 'hide') {
                $this->db->query("UPDATE ecommerce_reviews SET status = 'hidden' WHERE id = :id");
                $this->db->bind(':id', $reviewId);
                if ($this->db->execute()) {
                    $success = "Review hidden from storefront.";
                } else {
                    $error = "Failed to hide review.";
                }
            }
        }

        // Fetch all reviews
        $this->db->query("SELECT r.*, i.name as item_name 
                          FROM ecommerce_reviews r 
                          JOIN items i ON r.item_id = i.id 
                          ORDER BY r.created_at DESC");
        $reviews = $this->db->resultSet() ?: [];

        $data = [
            'title' => 'Storefront Customer Reviews',
            'content_view' => 'ecommerce/reviews',
            'reviews' => $reviews,
            'success' => $success,
            'error' => $error
        ];

        $this->view('layouts/main', $data);
    }

    /**
     * Blog Module
     */
    public function blog() {
        $success = '';
        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
            $action = $_POST['action'];

            if ($action === 'add') {
                $title = trim($_POST['title']);
                $content = trim($_POST['content']);
                $cat = trim($_POST['category'] ?? '');
                $author = trim($_POST['author'] ?? 'Admin');
                $featured = isset($_POST['is_featured']) ? 1 : 0;
                $seoUrl = trim($_POST['seo_url'] ?? '');

                if (empty($seoUrl)) {
                    $seoUrl = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));
                }

                // File Upload
                $filename = '';
                if (isset($_FILES['blog_image']) && $_FILES['blog_image']['error'] === UPLOAD_ERR_OK) {
                    $filename = 'blog_' . time() . '_' . $_FILES['blog_image']['name'];
                    $dest = '../public/uploads/blog/' . $filename;
                    if (!is_dir('../public/uploads/blog/')) {
                        mkdir('../public/uploads/blog/', 0777, true);
                    }
                    move_uploaded_file($_FILES['blog_image']['tmp_name'], $dest);
                }

                $this->db->query("INSERT INTO ecommerce_blog_posts (title, content, category, author, image_path, is_featured, seo_url) 
                                  VALUES (:title, :content, :cat, :author, :img, :feat, :seo)");
                $this->db->bind(':title', $title);
                $this->db->bind(':content', $content);
                $this->db->bind(':cat', $cat);
                $this->db->bind(':author', $author);
                $this->db->bind(':img', $filename);
                $this->db->bind(':feat', $featured);
                $this->db->bind(':seo', $seoUrl);

                if ($this->db->execute()) {
                    $success = "Blog article published successfully.";
                } else {
                    $error = "Failed to insert blog article.";
                }
            } elseif ($action === 'edit') {
                $id = intval($_POST['post_id']);
                $title = trim($_POST['title']);
                $content = trim($_POST['content']);
                $cat = trim($_POST['category'] ?? '');
                $author = trim($_POST['author'] ?? 'Admin');
                $featured = isset($_POST['is_featured']) ? 1 : 0;
                $seoUrl = trim($_POST['seo_url'] ?? '');

                if (empty($seoUrl)) {
                    $seoUrl = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));
                }

                try {
                    $this->db->beginTransaction();

                    if (isset($_FILES['blog_image']) && $_FILES['blog_image']['error'] === UPLOAD_ERR_OK) {
                        $filename = 'blog_' . time() . '_' . $_FILES['blog_image']['name'];
                        $dest = '../public/uploads/blog/' . $filename;
                        if (move_uploaded_file($_FILES['blog_image']['tmp_name'], $dest)) {
                            $this->db->query("UPDATE ecommerce_blog_posts SET image_path = :img WHERE id = :id");
                            $this->db->bind(':img', $filename);
                            $this->db->bind(':id', $id);
                            $this->db->execute();
                        }
                    }

                    $this->db->query("UPDATE ecommerce_blog_posts 
                                      SET title = :title, content = :content, category = :cat, 
                                          author = :author, is_featured = :feat, seo_url = :seo 
                                      WHERE id = :id");
                    $this->db->bind(':title', $title);
                    $this->db->bind(':content', $content);
                    $this->db->bind(':cat', $cat);
                    $this->db->bind(':author', $author);
                    $this->db->bind(':feat', $featured);
                    $this->db->bind(':seo', $seoUrl);
                    $this->db->bind(':id', $id);
                    $this->db->execute();

                    $this->db->commit();
                    $success = "Blog post updated successfully.";
                } catch (Exception $ex) {
                    $this->db->rollBack();
                    $error = "Error updating blog: " . $ex->getMessage();
                }
            } elseif ($action === 'delete') {
                $id = intval($_POST['post_id']);
                $this->db->query("DELETE FROM ecommerce_blog_posts WHERE id = :id");
                $this->db->bind(':id', $id);
                if ($this->db->execute()) {
                    $success = "Blog post removed.";
                } else {
                    $error = "Failed to remove blog post.";
                }
            }
        }

        // Fetch posts
        $this->db->query("SELECT * FROM ecommerce_blog_posts ORDER BY created_at DESC");
        $posts = $this->db->resultSet() ?: [];

        $data = [
            'title' => 'Blog Management',
            'content_view' => 'ecommerce/blog',
            'posts' => $posts,
            'success' => $success,
            'error' => $error
        ];

        $this->view('layouts/main', $data);
    }

    /**
     * Dedicated E-Commerce Reports
     */
    public function reports() {
        $reportType = $_GET['report_type'] ?? 'sales_summary';
        $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $_GET['end_date'] ?? date('Y-m-d');

        $reportData = [];

        if ($reportType === 'sales_summary') {
            // General Sales Report
            $this->db->query("SELECT DATE(created_at) as date, COUNT(*) as total_orders, 
                                     SUM(grand_total) as revenue, AVG(grand_total) as aov
                              FROM sales_orders 
                              WHERE order_number LIKE 'ECO-%' AND LOWER(status) != 'cancelled'
                                AND DATE(created_at) BETWEEN :start AND :end
                              GROUP BY DATE(created_at) 
                              ORDER BY date ASC");
            $this->db->bind(':start', $startDate);
            $this->db->bind(':end', $endDate);
            $reportData = $this->db->resultSet() ?: [];
        } elseif ($reportType === 'product_performance') {
            // Product Sales Performance
            $this->db->query("SELECT name, sku, SUM(qty) as units_sold, SUM(total) as revenue
                              FROM sales_order_items soi
                              JOIN sales_orders so ON soi.sales_order_id = so.id
                              WHERE so.order_number LIKE 'ECO-%' AND so.status != 'Cancelled'
                                AND so.order_date BETWEEN :start AND :end
                              GROUP BY sku, name
                              ORDER BY units_sold DESC");
            $this->db->bind(':start', $startDate);
            $this->db->bind(':end', $endDate);
            $reportData = $this->db->resultSet() ?: [];
        } elseif ($reportType === 'customer_segmentation') {
            // Wholesaler vs Retail Sales
            $this->db->query("SELECT billing_type, COUNT(*) as total_orders, SUM(grand_total) as revenue, AVG(grand_total) as aov
                              FROM sales_orders
                              WHERE order_number LIKE 'ECO-%' AND status != 'Cancelled'
                                AND order_date BETWEEN :start AND :end
                              GROUP BY billing_type");
            $this->db->bind(':start', $startDate);
            $this->db->bind(':end', $endDate);
            $reportData = $this->db->resultSet() ?: [];
        } elseif ($reportType === 'abandoned_carts') {
            // Abandoned Carts Details
            $this->db->query("SELECT esc.*, 
                                CASE 
                                    WHEN esc.customer_type = 'wholesaler' THEN (SELECT name FROM customers WHERE id = esc.customer_id)
                                    WHEN esc.customer_type = 'retail' THEN (SELECT name FROM ecommerce_retail_customers WHERE id = esc.customer_id)
                                END as customer_name
                              FROM ecommerce_saved_carts esc
                              WHERE esc.updated_at BETWEEN :start AND :end
                              ORDER BY esc.updated_at DESC");
            $this->db->bind(':start', $startDate . ' 00:00:00');
            $this->db->bind(':end', $endDate . ' 23:59:59');
            $reportData = $this->db->resultSet() ?: [];
        }

        $data = [
            'title' => 'E-Commerce Analytics Reports',
            'content_view' => 'ecommerce/reports',
            'report_type' => $reportType,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'report_data' => $reportData
        ];

        $this->view('layouts/main', $data);
    }

    /**
     * Wholesaler Requests (Preserved from existing controller)
     */
    public function requests() {
        $success = '';
        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
            $action = $_POST['action'];
            $requestId = intval($_POST['request_id']);

            if ($action === 'approve') {
                $linkAction = $_POST['link_action'] ?? 'create_new';
                $username = trim($_POST['approve_username'] ?? '');
                $password = $_POST['approve_password'] ?? '';

                if (empty($username) || empty($password)) {
                    $error = 'Username and Password are required to approve wholesaler profiles.';
                } else {
                    try {
                        $this->db->beginTransaction();

                        // Fetch request details
                        $this->db->query("SELECT * FROM wholesaler_requests WHERE id = :id");
                        $this->db->bind(':id', $requestId);
                        $req = $this->db->single();

                        if (!$req) {
                            throw new Exception("Wholesaler request not found.");
                        }

                        $customerId = null;

                        if ($linkAction === 'link_existing') {
                            $customerId = intval($_POST['existing_customer_id']);
                            
                            // Check if customer exists
                            $this->db->query("SELECT id FROM customers WHERE id = :cid");
                            $this->db->bind(':cid', $customerId);
                            if (!$this->db->single()) {
                                throw new Exception("Selected ERP customer profile does not exist.");
                            }

                            // Update existing customer credentials
                            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                            $this->db->query("UPDATE customers SET username = :username, password = :pass, email = :email WHERE id = :id");
                            $this->db->bind(':username', $username);
                            $this->db->bind(':pass', $hashedPassword);
                            $this->db->bind(':email', $req->email_address);
                            $this->db->bind(':id', $customerId);
                            $this->db->execute();

                        } else {
                            // Create new customer profile
                            $this->db->query("INSERT INTO customers (name, email, username, password, phone, address, territory) 
                                              VALUES (:name, :email, :username, :pass, :phone, :address, :territory)");
                            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                            $this->db->bind(':name', $req->business_name);
                            $this->db->bind(':email', $req->email_address);
                            $this->db->bind(':username', $username);
                            $this->db->bind(':pass', $hashedPassword);
                            $this->db->bind(':phone', $req->contact_number);
                            $this->db->bind(':address', $req->address);
                            $this->db->bind(':territory', $req->city);
                            $this->db->execute();
                            $customerId = $this->db->lastInsertId();
                        }

                        // Update wholesaler request status
                        $this->db->query("UPDATE wholesaler_requests SET status = 'approved', linked_customer_id = :cid WHERE id = :id");
                        $this->db->bind(':cid', $customerId);
                        $this->db->bind(':id', $requestId);
                        $this->db->execute();

                        $this->db->commit();
                        $success = "Wholesaler request approved successfully. Credentials linked to ERP Customer profile.";
                    } catch (Exception $e) {
                        $this->db->rollBack();
                        $error = "Error: " . $e->getMessage();
                    }
                }
            } elseif ($action === 'decline') {
                $this->db->query("UPDATE wholesaler_requests SET status = 'declined' WHERE id = :id");
                $this->db->bind(':id', $requestId);
                if ($this->db->execute()) {
                    $success = "Wholesaler request declined.";
                } else {
                    $error = "Failed to update wholesaler request status.";
                }
            }
        }

        // Fetch requests
        $this->db->query("SELECT r.*, c.name as linked_customer_name FROM wholesaler_requests r 
                          LEFT JOIN customers c ON r.linked_customer_id = c.id 
                          ORDER BY r.created_at DESC");
        $requests = $this->db->resultSet() ?: [];

        // Fetch all ERP customers for linking dropdown
        $this->db->query("SELECT id, name, email, phone FROM customers ORDER BY name ASC");
        $erpCustomers = $this->db->resultSet() ?: [];

        $data = [
            'title' => 'Wholesaler Requests',
            'content_view' => 'ecommerce/requests',
            'requests' => $requests,
            'erp_customers' => $erpCustomers,
            'success' => $success,
            'error' => $error
        ];

        $this->view('layouts/main', $data);
    }

    /**
     * Retail Customers Directory (Preserved from existing controller)
     */
    public function retail() {
        // Fetch retail customers directory
        $this->db->query("SELECT * FROM ecommerce_retail_customers ORDER BY name ASC");
        $retailCustomers = $this->db->resultSet() ?: [];

        $data = [
            'title' => 'Retail Customers',
            'content_view' => 'ecommerce/retail',
            'customers' => $retailCustomers
        ];

        $this->view('layouts/main', $data);
    }
}
