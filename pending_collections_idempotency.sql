-- SQL commands to add mobile_local_id and mobile_rep_id to pending_collections table
-- This allows the server to prevent duplicate collections from retry sync operations.

ALTER TABLE pending_collections ADD COLUMN mobile_local_id INT NULL;
ALTER TABLE pending_collections ADD COLUMN mobile_rep_id INT NULL;
