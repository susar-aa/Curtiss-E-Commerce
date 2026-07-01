-- Database Schema Upgrades for Sales Orders & Invoice Deletion Audit Log

-- 1. Create Deleted Invoices Audit Trail Table
CREATE TABLE IF NOT EXISTS deleted_invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(50) NOT NULL,
    customer_name VARCHAR(150) NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    deleted_user_name VARCHAR(100) NOT NULL,
    deleted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    delete_reason TEXT NOT NULL,
    record_type VARCHAR(20) NOT NULL DEFAULT 'Invoice'
);

-- 2. Create Sales Orders Table
CREATE TABLE IF NOT EXISTS sales_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(50) NOT NULL UNIQUE,
    customer_id INT NOT NULL,
    customer_name VARCHAR(150) NOT NULL,
    customer_phone VARCHAR(50) NULL,
    billing_type ENUM('retail', 'wholesale') NOT NULL DEFAULT 'retail',
    subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    discount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    grand_total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    notes TEXT NULL,
    rep_name VARCHAR(100) NULL,
    mca VARCHAR(100) NULL,
    rep_tp VARCHAR(50) NULL,
    po_number VARCHAR(50) NULL,
    order_date DATE NOT NULL,
    due_date DATE NOT NULL,
    payment_term_id INT NULL DEFAULT NULL,
    status VARCHAR(50) DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. Create Sales Order Items Table
CREATE TABLE IF NOT EXISTS sales_order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sales_order_id INT NOT NULL,
    item_id INT NOT NULL,
    variation_option_id INT NULL DEFAULT NULL,
    sku VARCHAR(100) NOT NULL,
    name VARCHAR(255) NOT NULL,
    billing_price DECIMAL(10,2) NOT NULL,
    qty INT NOT NULL,
    discount_value DECIMAL(10,2) DEFAULT 0.00,
    discount_type VARCHAR(10) DEFAULT 'Rs',
    total DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (sales_order_id) REFERENCES sales_orders(id) ON DELETE CASCADE
);

-- 4. Failsafe upgrades to invoices and invoice_items tables (in case columns are missing on live system)
ALTER TABLE invoices ADD COLUMN IF NOT EXISTS stock_status VARCHAR(20) DEFAULT 'deducted' AFTER status;
ALTER TABLE invoices ADD COLUMN IF NOT EXISTS notes TEXT NULL AFTER global_discount_type;
ALTER TABLE invoices ADD COLUMN IF NOT EXISTS cheque_date DATE NULL AFTER due_date;
ALTER TABLE invoices ADD COLUMN IF NOT EXISTS payment_term_id INT NULL DEFAULT NULL AFTER due_date;
ALTER TABLE invoices ADD COLUMN IF NOT EXISTS uuid VARCHAR(100) UNIQUE NULL AFTER invoice_number;

ALTER TABLE invoice_items ADD COLUMN IF NOT EXISTS item_id INT NULL DEFAULT NULL AFTER invoice_id;
ALTER TABLE invoice_items ADD COLUMN IF NOT EXISTS variation_option_id INT NULL DEFAULT NULL AFTER item_id;

-- 5. Add accessible_apps column to users table
ALTER TABLE users ADD COLUMN IF NOT EXISTS accessible_apps VARCHAR(255) DEFAULT 'ERP System' AFTER status;
