-- Database migration script to add debit/credit account selection columns to pending_collections table
ALTER TABLE pending_collections ADD COLUMN `debit_account_id` INT(11) NULL DEFAULT NULL;
ALTER TABLE pending_collections ADD COLUMN `credit_account_id` INT(11) NULL DEFAULT NULL;
