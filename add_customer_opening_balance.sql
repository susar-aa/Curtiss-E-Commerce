-- SQL Migration: Add opening_balance column to customers table
-- Run this on your MySQL server to support Customer opening balance tracking.

ALTER TABLE `customers` ADD COLUMN `opening_balance` DECIMAL(15,2) DEFAULT 0.00 AFTER `credit_limit`;
