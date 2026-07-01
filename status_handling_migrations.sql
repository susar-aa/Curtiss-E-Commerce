-- Migration: Add status column to customers, item_categories, and mca_areas tables to support inactive record filtering.
ALTER TABLE `customers` ADD COLUMN `status` VARCHAR(20) DEFAULT 'active' AFTER `notes`;
ALTER TABLE `item_categories` ADD COLUMN `status` VARCHAR(20) DEFAULT 'active';
ALTER TABLE `mca_areas` ADD COLUMN `status` VARCHAR(20) DEFAULT 'active';
