-- SQL Migration: Add missing customer fields (Credit Limit, Customer Type, Notes)
-- Run this on your MySQL server to support Customer profile editing and outstanding balance/credit limits.

ALTER TABLE `customers` ADD COLUMN `credit_limit` DECIMAL(15,2) DEFAULT 0.00 AFTER `territory`;
ALTER TABLE `customers` ADD COLUMN `customer_type` VARCHAR(50) DEFAULT 'Standard' AFTER `credit_limit`;
ALTER TABLE `customers` ADD COLUMN `notes` TEXT NULL AFTER `customer_type`;
