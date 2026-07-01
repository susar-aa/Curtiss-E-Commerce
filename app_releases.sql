CREATE TABLE IF NOT EXISTS `app_releases` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `version` VARCHAR(50) NOT NULL UNIQUE,
  `build_version` INT NULL,
  `version_name` VARCHAR(50) NULL,
  `package_name` VARCHAR(255) NULL,
  `app_name` VARCHAR(255) NULL,
  `major` INT NOT NULL,
  `minor` INT NOT NULL,
  `patch` INT NOT NULL,
  `release_notes` TEXT NULL,
  `apk_path` VARCHAR(255) NOT NULL,
  `force_update` TINYINT(1) NOT NULL DEFAULT 0,
  `is_latest` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
