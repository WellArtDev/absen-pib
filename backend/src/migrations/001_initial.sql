-- ============================================================
-- AbsenPIB Multi-Tenant Schema
-- cPanel Compatible — MySQL 5.7+ / MariaDB 10.3+
-- Cara pakai: copy-paste ke phpMyAdmin → tab SQL → Execute
-- ============================================================

-- Companies (multi-tenant parent)
CREATE TABLE IF NOT EXISTS `companies` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `code` VARCHAR(20) NOT NULL,
  `owner_id` INT DEFAULT NULL,
  `address` TEXT,
  `phone` VARCHAR(30),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Offices (each company can have multiple offices)
CREATE TABLE IF NOT EXISTS `offices` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `company_id` INT NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `latitude` DOUBLE NOT NULL,
  `longitude` DOUBLE NOT NULL,
  `radius_meters` INT DEFAULT 200,
  `work_start` TIME DEFAULT '08:00',
  `work_end` TIME DEFAULT '17:00',
  `enforce_geofence` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Users (multi-tenant, RBAC)
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `company_id` INT NOT NULL,
  `office_id` INT DEFAULT NULL,
  `role` ENUM('superadmin','owner','admin','sales','karyawan') NOT NULL DEFAULT 'karyawan',
  `nip` VARCHAR(30) NOT NULL,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `avatar_url` VARCHAR(255) DEFAULT NULL,
  `phone` VARCHAR(30) DEFAULT NULL,
  `leave_quota_total` INT DEFAULT 12,
  `leave_quota_used` INT DEFAULT 0,
  `fcm_token` VARCHAR(255) DEFAULT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`office_id`) REFERENCES `offices`(`id`) ON DELETE SET NULL,
  UNIQUE KEY `uk_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Attendances
CREATE TABLE IF NOT EXISTS `attendances` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `company_id` INT NOT NULL,
  `office_id` INT DEFAULT NULL,
  `type` ENUM('check_in','check_out') NOT NULL,
  `photo_url` VARCHAR(255) NOT NULL,
  `latitude` DOUBLE NOT NULL,
  `longitude` DOUBLE NOT NULL,
  `altitude` DOUBLE DEFAULT NULL,
  `gps_accuracy` DOUBLE DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `device_info` JSON DEFAULT NULL,
  `gps_providers` JSON DEFAULT NULL,
  `gps_timestamp` TIMESTAMP NULL DEFAULT NULL,
  `server_timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `is_late` TINYINT(1) DEFAULT 0,
  `suspicion_score` INT DEFAULT 0,
  `suspicion_flags` JSON DEFAULT NULL,
  `is_suspect` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  INDEX `idx_user_date` (`user_id`, `server_timestamp`),
  INDEX `idx_company_date` (`company_id`, `server_timestamp`),
  INDEX `idx_suspect` (`is_suspect`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Overtimes
CREATE TABLE IF NOT EXISTS `overtimes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `company_id` INT NOT NULL,
  `check_in_photo_url` VARCHAR(255) NOT NULL,
  `check_out_photo_url` VARCHAR(255) DEFAULT NULL,
  `check_in_lat` DOUBLE NOT NULL,
  `check_in_lng` DOUBLE NOT NULL,
  `check_out_lat` DOUBLE DEFAULT NULL,
  `check_out_lng` DOUBLE DEFAULT NULL,
  `check_in_address` TEXT DEFAULT NULL,
  `check_out_address` TEXT DEFAULT NULL,
  `check_in_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `check_out_at` TIMESTAMP NULL DEFAULT NULL,
  `duration_minutes` INT DEFAULT NULL,
  `status` ENUM('pending','approved','rejected') DEFAULT 'pending',
  `approved_by` INT DEFAULT NULL,
  `approved_at` TIMESTAMP NULL DEFAULT NULL,
  `rejection_reason` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  INDEX `idx_user_overtime` (`user_id`),
  INDEX `idx_company_overtime` (`company_id`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Leaves
CREATE TABLE IF NOT EXISTS `leaves` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `company_id` INT NOT NULL,
  `leave_type` ENUM('tahunan','sakit','darurat','lainnya') NOT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `total_days` INT NOT NULL,
  `reason` TEXT NOT NULL,
  `attachment_url` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('pending','approved','rejected') DEFAULT 'pending',
  `approved_by` INT DEFAULT NULL,
  `approved_at` TIMESTAMP NULL DEFAULT NULL,
  `rejection_reason` TEXT DEFAULT NULL,
  `quota_deducted` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  INDEX `idx_user_leave` (`user_id`),
  INDEX `idx_company_leave` (`company_id`),
  INDEX `idx_status_leave` (`status`),
  INDEX `idx_dates` (`start_date`, `end_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notifications
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `company_id` INT NOT NULL,
  `user_id` INT DEFAULT NULL,
  `title` VARCHAR(200) NOT NULL,
  `message` TEXT NOT NULL,
  `type` ENUM('attendance','overtime','leave','approval','system') DEFAULT 'system',
  `is_read` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  INDEX `idx_user_notif` (`user_id`),
  INDEX `idx_company_notif` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- SEED DATA — hapus baris ini kalau tidak perlu
-- ============================================================

-- System company (placeholder untuk superadmin)
INSERT INTO `companies` (`name`, `code`, `address`) VALUES ('System', 'SYSTEM', 'Platform');

-- Superadmin: email superadmin@absenpib.com, password admin123
INSERT INTO `users` (`company_id`, `office_id`, `role`, `nip`, `full_name`, `email`, `password_hash`)
VALUES (1, NULL, 'superadmin', '0000000000', 'Super Admin', 'superadmin@absenpib.com',
        '$2y$12$/3WUseqLixXpLVD.SNkQfu52UyBEnDnfJio5CNNDgPKZxcs5wErlK');
