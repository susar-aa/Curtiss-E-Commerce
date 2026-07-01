-- Database upgrade for Final Loading Verification
ALTER TABLE `delivery_picking_items`
ADD COLUMN `final_loaded_qty` DECIMAL(10,2) DEFAULT NULL,
ADD COLUMN `is_verified` TINYINT(1) DEFAULT 0,
ADD COLUMN `variance` DECIMAL(10,2) DEFAULT 0,
ADD COLUMN `verified_at` TIMESTAMP NULL DEFAULT NULL,
ADD COLUMN `verified_by` INT(11) DEFAULT NULL;
