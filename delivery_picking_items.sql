-- Create delivery_picking_items table for pre-loading picking tracking
CREATE TABLE IF NOT EXISTS `delivery_picking_items` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `delivery_id` INT(11) NOT NULL,
  `item_name` VARCHAR(255) NOT NULL,
  `item_id` INT(11) DEFAULT NULL,
  `variation_option_id` INT(11) DEFAULT NULL,
  `required_qty` DECIMAL(10,2) NOT NULL,
  `loaded_qty` DECIMAL(10,2) NOT NULL,
  `is_picked` TINYINT(1) DEFAULT 0,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_picking_delivery_id` (`delivery_id`),
  CONSTRAINT `fk_picking_delivery_id` FOREIGN KEY (`delivery_id`) REFERENCES `deliveries` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
