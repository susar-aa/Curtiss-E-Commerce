-- ==========================================
-- E-COMMERCE STATIONERY SYSTEM UPGRADE SCRIPT
-- ==========================================

-- 1. Create E-Commerce Settings Table
CREATE TABLE IF NOT EXISTS ecommerce_settings (
    `key` VARCHAR(100) PRIMARY KEY,
    `value` TEXT NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed Default Settings
INSERT IGNORE INTO ecommerce_settings (`key`, `value`) VALUES 
('store_name', 'Curtiss Stationery Store'),
('logo', ''),
('favicon', ''),
('contact_email', 'support@curtissstationery.com'),
('contact_phone', '+94 11 123 4567'),
('contact_address', 'No. 45, Galle Road, Colombo 03, Sri Lanka'),
('about_us', 'Your premium stationery and office equipment partner in Sri Lanka.'),
('terms_conditions', 'Standard business terms apply.'),
('privacy_policy', 'We value your privacy.'),
('return_policy', '14 days return policy for unused items.'),
('delivery_policy', 'Delivery within 2-3 business days islandwide.'),
('social_facebook', 'https://facebook.com/curtiss'),
('social_instagram', 'https://instagram.com/curtiss'),
('social_twitter', 'https://twitter.com/curtiss'),
('seo_title', 'Curtiss Stationery - Premium Office & School Supplies'),
('seo_meta_desc', 'Buy school and office stationery, files, pens, paper products and office accessories online in Sri Lanka at retail and wholesale prices.'),
('seo_keywords', 'stationery, office supplies, school supplies, pens, notebooks, files, wholesale stationery Sri Lanka'),
('google_analytics', ''),
('meta_tags', '');

-- 2. Create Homepage Builder Sections Table
CREATE TABLE IF NOT EXISTS homepage_sections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    section_name VARCHAR(100) UNIQUE NOT NULL,
    title VARCHAR(150) NOT NULL,
    is_enabled TINYINT DEFAULT 1,
    sort_order INT DEFAULT 0,
    config JSON NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed Homepage Sections (Support Drag and Drop)
INSERT IGNORE INTO homepage_sections (section_name, title, is_enabled, sort_order) VALUES 
('hero_banner', 'Hero Banner', 1, 1),
('featured_categories', 'Featured Categories', 1, 2),
('featured_products', 'Featured Products', 1, 3),
('new_arrivals', 'New Arrivals', 1, 4),
('best_sellers', 'Best Sellers', 1, 5),
('promotional_banner', 'Promotional Banner', 1, 6),
('school_essentials', 'School Essentials', 1, 7),
('office_essentials', 'Office Essentials', 1, 8),
('art_supplies', 'Art Supplies', 1, 9),
('special_offers', 'Special Offers', 1, 10),
('brands', 'Brands Showcase', 1, 11),
('customer_testimonials', 'Customer Testimonials', 1, 12),
('blog_articles', 'Blog Articles', 1, 13),
('newsletter_subscription', 'Newsletter Subscription', 1, 14);

-- 3. Create Banner Management Table
CREATE TABLE IF NOT EXISTS ecommerce_banners (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Extend ERP Items Table for E-Commerce configurations
ALTER TABLE items ADD COLUMN IF NOT EXISTS is_published TINYINT DEFAULT 1;
ALTER TABLE items ADD COLUMN IF NOT EXISTS is_featured TINYINT DEFAULT 0;
ALTER TABLE items ADD COLUMN IF NOT EXISTS is_bestseller TINYINT DEFAULT 0;
ALTER TABLE items ADD COLUMN IF NOT EXISTS is_new_arrival TINYINT DEFAULT 0;
ALTER TABLE items ADD COLUMN IF NOT EXISTS is_clearance TINYINT DEFAULT 0;
ALTER TABLE items ADD COLUMN IF NOT EXISTS is_special_offer TINYINT DEFAULT 0;
ALTER TABLE items ADD COLUMN IF NOT EXISTS online_stock_visible TINYINT DEFAULT 1;

-- 5. Extend Item Categories for E-Commerce Hierarchy & Styling
ALTER TABLE item_categories ADD COLUMN IF NOT EXISTS parent_id INT NULL;
ALTER TABLE item_categories ADD COLUMN IF NOT EXISTS icon VARCHAR(100) NULL;
ALTER TABLE item_categories ADD COLUMN IF NOT EXISTS image_path VARCHAR(255) NULL;
ALTER TABLE item_categories ADD COLUMN IF NOT EXISTS seo_url VARCHAR(255) NULL;
ALTER TABLE item_categories ADD COLUMN IF NOT EXISTS is_featured TINYINT DEFAULT 0;

-- 6. Create Customer Reviews Table
CREATE TABLE IF NOT EXISTS ecommerce_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    customer_id INT NULL,
    customer_name VARCHAR(100) NOT NULL,
    rating INT NOT NULL DEFAULT 5,
    review_text TEXT NOT NULL,
    status ENUM('pending', 'approved', 'rejected', 'hidden') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Create Blog Posts Table
CREATE TABLE IF NOT EXISTS ecommerce_blog_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    category VARCHAR(100) NULL,
    author VARCHAR(100) NULL,
    image_path VARCHAR(255) NULL,
    is_featured TINYINT DEFAULT 0,
    seo_url VARCHAR(200) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed Default Blog Posts
INSERT IGNORE INTO ecommerce_blog_posts (id, title, content, category, author, is_featured, seo_url) VALUES 
(1, 'Essential Stationery for Modern Offices', 'Running an office smoothly requires the right set of tools. From standard files and binders to quality pens and desk organizers, having a reliable stationery supply keeps operations seamless and professional. In this post, we discuss the top 10 items every modern office should have in stock.', 'Office Guide', 'Admin', 1, 'essential-stationery-modern-offices'),
(2, 'Choosing the Right Art Supplies for Kids', 'Art and craft activities are crucial for a child\'s development. However, selecting the right paints, pencils, and papers that are non-toxic, easy to wash, and fun to use can be challenging. Here is our comprehensive guide to child-friendly art supplies.', 'School & Art', 'Admin', 0, 'choosing-right-art-supplies-kids');

-- 8. Create Customer Wishlist Table
CREATE TABLE IF NOT EXISTS ecommerce_wishlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    customer_type ENUM('retail', 'wholesaler') NOT NULL,
    item_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. Create Persistent Saved Carts Table
CREATE TABLE IF NOT EXISTS ecommerce_saved_carts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    customer_type ENUM('retail', 'wholesaler') NOT NULL,
    cart_data LONGTEXT NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY (customer_id, customer_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10. Create E-Commerce Return Requests Table
CREATE TABLE IF NOT EXISTS ecommerce_returns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sales_order_id INT NOT NULL,
    customer_id INT NOT NULL,
    customer_type ENUM('retail', 'wholesaler') NOT NULL,
    reason VARCHAR(255) NOT NULL,
    details TEXT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sales_order_id) REFERENCES sales_orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 11. Create E-Commerce Coupon Rules Table
CREATE TABLE IF NOT EXISTS ecommerce_coupons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    type ENUM('percent', 'fixed') NOT NULL DEFAULT 'percent',
    value DECIMAL(10,2) NOT NULL,
    min_spend DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    expiry_date DATE NULL,
    is_active TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed Default Coupon Code
INSERT IGNORE INTO ecommerce_coupons (code, type, value, min_spend, expiry_date, is_active) VALUES 
('WELCOME10', 'percent', 10.00, 1000.00, DATE_ADD(CURRENT_DATE, INTERVAL 1 YEAR), 1);

-- 12. Create Website Visitor Analytics Table (Future Ready)
CREATE TABLE IF NOT EXISTS ecommerce_visitors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    visit_date DATE NOT NULL,
    page_views INT DEFAULT 1,
    UNIQUE KEY (ip_address, visit_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
