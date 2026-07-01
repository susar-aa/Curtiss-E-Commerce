-- SQL commands to add verification and tracking columns to pending_collections table
-- Run this on the live database to ensure compatibility with the Route Management Collections Audit.

ALTER TABLE pending_collections ADD COLUMN is_verified TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE pending_collections ADD COLUMN is_flagged TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE pending_collections ADD COLUMN adjusted_amount DECIMAL(12,2) NULL;
ALTER TABLE pending_collections ADD COLUMN verification_notes TEXT NULL;
ALTER TABLE pending_collections ADD COLUMN verified_by INT(11) NULL;
ALTER TABLE pending_collections ADD COLUMN verified_at DATETIME NULL;
ALTER TABLE pending_collections ADD COLUMN mobile_local_id INT NULL;
ALTER TABLE pending_collections ADD COLUMN mobile_rep_id INT NULL;
