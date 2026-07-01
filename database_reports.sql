-- Enterprise Reporting Module Database Schema
-- Run these commands on your MariaDB/MySQL database to enable Saved Views and Scheduled Reports.

CREATE TABLE IF NOT EXISTS `saved_reports` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `report_key` VARCHAR(100) NOT NULL,
  `view_name` VARCHAR(255) NOT NULL,
  `filters` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `scheduled_reports` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `report_key` VARCHAR(100) NOT NULL,
  `frequency` VARCHAR(50) NOT NULL, -- 'daily', 'weekly', 'monthly'
  `email_recipient` VARCHAR(255) NOT NULL,
  `filters` TEXT NOT NULL,
  `last_run_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
