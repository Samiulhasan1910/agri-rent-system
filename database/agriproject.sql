-- =====================================================================
-- AgriRent: Agricultural Equipment Rental and Management System
-- Database schema + demo data (MySQL 5.7+/MariaDB 10.3+)
--
-- Import from phpMyAdmin (Import tab) or the MySQL console:
--     mysql -u root < database/agriproject.sql
-- It creates (or resets) the `agriproject` database.
--
-- Demo accounts (all passwords shown):
--   Admin     admin@agrirent.test     Admin@123
--   Employee  employee@agrirent.test  Employee@123
--   Farmer    farmer@agrirent.test    Farmer@123   (verified & approved)
-- =====================================================================

SET NAMES utf8mb4;
SET time_zone = '+06:00';
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS `agriproject` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `agriproject`;

DROP TABLE IF EXISTS `sync_log`, `reports`, `price_change_logs`, `crop_rules`, `settings`, `security_logs`,
  `notifications`, `messages`, `emergency_requests`, `complaints`, `reviews`, `wishlist`, `inspections`,
  `usage_logs`, `maintenance_alerts`, `maintenance`, `delivery_updates`, `deliveries`, `delivery_teams`,
  `payments`, `coupons`, `booking_extensions`, `rental_extensions`, `bookings`, `equipment`, `categories`,
  `user_tokens`, `users`;

-- ---------------------------------------------------------------------
-- Users & authentication
-- ---------------------------------------------------------------------
CREATE TABLE `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(160) NOT NULL,
  `phone` VARCHAR(30) NOT NULL DEFAULT '',
  `address` VARCHAR(255) NOT NULL DEFAULT '',
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('farmer','employee','admin') NOT NULL,
  -- inactive until the email address is verified; suspended = deactivated by admin; deleted = closed by owner
  `status` ENUM('inactive','active','suspended','deleted') NOT NULL DEFAULT 'inactive',
  `email_verified_at` DATETIME DEFAULT NULL,
  -- account (document) verification performed by employees/admin
  `verification_status` ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `verification_note` VARCHAR(255) DEFAULT NULL,
  `verification_document` VARCHAR(255) DEFAULT NULL,
  `verified_by` INT UNSIGNED DEFAULT NULL,
  `verified_at` DATETIME DEFAULT NULL,
  `employee_code` VARCHAR(30) DEFAULT NULL,
  `profile_photo` VARCHAR(255) DEFAULT NULL,
  `language` ENUM('en','bn') NOT NULL DEFAULT 'en',
  `two_factor_enabled` TINYINT(1) NOT NULL DEFAULT 0,
  `google_id` VARCHAR(64) DEFAULT NULL,
  `failed_attempts` INT UNSIGNED NOT NULL DEFAULT 0,
  `locked_until` DATETIME DEFAULT NULL,
  `last_login_at` DATETIME DEFAULT NULL,
  `last_login_ip` VARCHAR(45) DEFAULT NULL,
  `last_login_agent` VARCHAR(255) DEFAULT NULL,
  `remember_selector` CHAR(24) DEFAULT NULL,
  `remember_hash` CHAR(64) DEFAULT NULL,
  `remember_expires` DATETIME DEFAULT NULL,
  `deleted_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`),
  UNIQUE KEY `uq_users_google` (`google_id`),
  UNIQUE KEY `uq_users_remember` (`remember_selector`),
  KEY `idx_users_role_status` (`role`,`status`,`verification_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Email verification links/codes, password reset links/OTPs and login (2FA) OTPs
CREATE TABLE `user_tokens` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `type` ENUM('email_verify','password_reset','login_otp') NOT NULL,
  `token_hash` CHAR(64) NOT NULL,
  `code_hash` CHAR(64) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `code_expires_at` DATETIME NOT NULL,
  `attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `used_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tokens_hash` (`token_hash`),
  KEY `idx_tokens_user_type` (`user_id`,`type`,`used_at`),
  CONSTRAINT `fk_tokens_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `security_logs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `email` VARCHAR(160) DEFAULT NULL,
  `event` VARCHAR(40) NOT NULL,
  `ip` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(255) DEFAULT NULL,
  `details` VARCHAR(500) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_security_user` (`user_id`,`created_at`),
  KEY `idx_security_event` (`event`,`created_at`),
  CONSTRAINT `fk_security_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Catalogue
-- ---------------------------------------------------------------------
CREATE TABLE `categories` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(80) NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `icon` VARCHAR(40) NOT NULL DEFAULT 'tractor',
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_categories_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `equipment` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `specs` VARCHAR(150) DEFAULT NULL,
  `description` TEXT NOT NULL,
  `price_per_day` DECIMAL(10,2) DEFAULT NULL,
  `quantity` INT UNSIGNED NOT NULL DEFAULT 1,
  `location` VARCHAR(150) NOT NULL,
  `latitude` DECIMAL(9,6) DEFAULT NULL,
  `longitude` DECIMAL(9,6) DEFAULT NULL,
  `image` VARCHAR(255) DEFAULT NULL,
  `owner_name` VARCHAR(120) DEFAULT NULL,
  `owner_phone` VARCHAR(30) DEFAULT NULL,
  `status` ENUM('available','unavailable','maintenance','inactive') NOT NULL DEFAULT 'available',
  -- health monitoring
  `health_status` ENUM('good','needs_maintenance','under_maintenance','unknown') NOT NULL DEFAULT 'good',
  `health_flag` VARCHAR(255) DEFAULT NULL,
  `usage_hours` DECIMAL(10,1) NOT NULL DEFAULT 0,
  `hours_since_service` DECIMAL(10,1) NOT NULL DEFAULT 0,
  `usage_threshold_hours` INT UNSIGNED NOT NULL DEFAULT 250,
  `service_interval_days` INT UNSIGNED NOT NULL DEFAULT 120,
  `last_service_date` DATE DEFAULT NULL,
  `added_by` INT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_equipment_category` (`category_id`),
  KEY `idx_equipment_status` (`status`,`health_status`),
  KEY `idx_equipment_location` (`location`),
  CONSTRAINT `fk_equipment_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_equipment_user` FOREIGN KEY (`added_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Bookings, extensions, payments
-- ---------------------------------------------------------------------
CREATE TABLE `bookings` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `booking_code` VARCHAR(20) DEFAULT NULL,
  `farmer_id` INT UNSIGNED NOT NULL,
  `equipment_id` INT UNSIGNED NOT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `days` INT UNSIGNED NOT NULL,
  `base_price` DECIMAL(10,2) NOT NULL,
  `price_per_day` DECIMAL(10,2) NOT NULL,
  `season` ENUM('normal','harvest','off_season') NOT NULL DEFAULT 'normal',
  `rental_cost` DECIMAL(12,2) NOT NULL,
  `delivery_charge` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `discount` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `total_amount` DECIMAL(12,2) NOT NULL,
  `delivery_address` VARCHAR(255) NOT NULL,
  `notes` VARCHAR(500) DEFAULT NULL,
  `status` ENUM('pending','confirmed','delivered','returned','completed','cancelled','rejected') NOT NULL DEFAULT 'pending',
  `status_note` VARCHAR(255) DEFAULT NULL,
  `cancellation_reason` VARCHAR(120) DEFAULT NULL,
  `cancellation_details` VARCHAR(500) DEFAULT NULL,
  `cancelled_at` DATETIME DEFAULT NULL,
  `client_uuid` CHAR(36) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_bookings_code` (`booking_code`),
  UNIQUE KEY `uq_bookings_uuid` (`client_uuid`),
  KEY `idx_bookings_farmer` (`farmer_id`,`status`),
  KEY `idx_bookings_equipment_dates` (`equipment_id`,`start_date`,`end_date`),
  KEY `idx_bookings_status_created` (`status`,`created_at`),
  CONSTRAINT `fk_bookings_farmer` FOREIGN KEY (`farmer_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_bookings_equipment` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `booking_extensions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `booking_id` INT UNSIGNED NOT NULL,
  `current_end_date` DATE NOT NULL,
  `requested_end_date` DATE NOT NULL,
  `extra_days` INT UNSIGNED NOT NULL,
  `extra_cost` DECIMAL(12,2) NOT NULL,
  `reason` VARCHAR(500) NOT NULL,
  `status` ENUM('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
  `response_note` VARCHAR(255) DEFAULT NULL,
  `reviewed_by` INT UNSIGNED DEFAULT NULL,
  `reviewed_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_extensions_booking` (`booking_id`,`status`),
  CONSTRAINT `fk_extensions_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_extensions_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `coupons` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(30) NOT NULL,
  `description` VARCHAR(150) DEFAULT NULL,
  `discount_type` ENUM('percent','fixed') NOT NULL DEFAULT 'percent',
  `discount_value` DECIMAL(10,2) NOT NULL,
  `min_amount` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `max_discount` DECIMAL(10,2) DEFAULT NULL,
  `starts_at` DATE DEFAULT NULL,
  `expires_at` DATE DEFAULT NULL,
  `usage_limit` INT UNSIGNED DEFAULT NULL,
  `used_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_coupons_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Payment ledger: one row per transaction attempt (booking or extension balance)
CREATE TABLE `payments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `invoice_no` VARCHAR(20) NOT NULL,
  `booking_id` INT UNSIGNED NOT NULL,
  `method` ENUM('bkash','nagad','card','cash') NOT NULL,
  `amount` DECIMAL(12,2) NOT NULL,
  `discount` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `coupon_id` INT UNSIGNED DEFAULT NULL,
  `transaction_id` VARCHAR(40) DEFAULT NULL,
  `account_ref` VARCHAR(40) DEFAULT NULL,
  `status` ENUM('pending','paid','failed','refunded','cancelled') NOT NULL DEFAULT 'pending',
  `failure_reason` VARCHAR(255) DEFAULT NULL,
  `paid_at` DATETIME DEFAULT NULL,
  `receipt_emailed` TINYINT(1) NOT NULL DEFAULT 0,
  `invoice_generated_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_payments_invoice` (`invoice_no`),
  KEY `idx_payments_booking` (`booking_id`,`status`),
  KEY `idx_payments_status_created` (`status`,`created_at`),
  CONSTRAINT `fk_payments_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_payments_coupon` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Delivery
-- ---------------------------------------------------------------------
CREATE TABLE `delivery_teams` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(80) NOT NULL,
  `leader_name` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(30) NOT NULL,
  `email` VARCHAR(160) NOT NULL,
  `vehicle` VARCHAR(80) DEFAULT NULL,
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_teams_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `deliveries` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `booking_id` INT UNSIGNED NOT NULL,
  `team_id` INT UNSIGNED DEFAULT NULL,
  `address` VARCHAR(255) NOT NULL,
  `scheduled_date` DATE DEFAULT NULL,
  `estimated_time` VARCHAR(40) DEFAULT NULL,
  `status` ENUM('pending','assigned','in_progress','delivered','returned','cancelled') NOT NULL DEFAULT 'pending',
  `progress` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `delivered_at` DATETIME DEFAULT NULL,
  `returned_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_deliveries_booking` (`booking_id`),
  KEY `idx_deliveries_team_status` (`team_id`,`status`),
  CONSTRAINT `fk_deliveries_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_deliveries_team` FOREIGN KEY (`team_id`) REFERENCES `delivery_teams` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `delivery_updates` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `delivery_id` INT UNSIGNED NOT NULL,
  `status` VARCHAR(20) NOT NULL,
  `note` VARCHAR(255) DEFAULT NULL,
  `updated_by` INT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_delivery_updates` (`delivery_id`,`created_at`),
  CONSTRAINT `fk_delivery_updates_delivery` FOREIGN KEY (`delivery_id`) REFERENCES `deliveries` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_delivery_updates_user` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Maintenance, health monitoring & inspections
-- ---------------------------------------------------------------------
CREATE TABLE `maintenance` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `equipment_id` INT UNSIGNED NOT NULL,
  `scheduled_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `type` ENUM('routine','repair','inspection') NOT NULL DEFAULT 'routine',
  `notes` VARCHAR(500) NOT NULL,
  `status` ENUM('scheduled','in_progress','completed','cancelled') NOT NULL DEFAULT 'scheduled',
  `created_by` INT UNSIGNED NOT NULL,
  `completed_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_maintenance_equipment` (`equipment_id`,`status`,`scheduled_date`,`end_date`),
  CONSTRAINT `fk_maintenance_equipment` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_maintenance_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `maintenance_alerts` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `equipment_id` INT UNSIGNED NOT NULL,
  `type` ENUM('usage_threshold','service_due','damaged','missing_data') NOT NULL,
  `message` VARCHAR(255) NOT NULL,
  `status` ENUM('open','resolved') NOT NULL DEFAULT 'open',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `resolved_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_alerts_equipment_status` (`equipment_id`,`status`,`type`),
  CONSTRAINT `fk_alerts_equipment` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `usage_logs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `equipment_id` INT UNSIGNED NOT NULL,
  `booking_id` INT UNSIGNED DEFAULT NULL,
  `hours` DECIMAL(8,1) DEFAULT NULL,
  `source` ENUM('return','manual') NOT NULL DEFAULT 'return',
  `note` VARCHAR(255) DEFAULT NULL,
  `flagged` TINYINT(1) NOT NULL DEFAULT 0,
  `logged_by` INT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_usage_equipment` (`equipment_id`,`created_at`),
  CONSTRAINT `fk_usage_equipment` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_usage_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_usage_user` FOREIGN KEY (`logged_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `inspections` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `booking_id` INT UNSIGNED NOT NULL,
  `equipment_id` INT UNSIGNED NOT NULL,
  `condition` ENUM('good','damaged','needs_service') NOT NULL,
  `comments` TEXT DEFAULT NULL,
  `usage_hours` DECIMAL(8,1) DEFAULT NULL,
  `inspected_by` INT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_inspections_booking` (`booking_id`),
  KEY `idx_inspections_equipment` (`equipment_id`),
  CONSTRAINT `fk_inspections_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_inspections_equipment` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_inspections_user` FOREIGN KEY (`inspected_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Farmer interaction & support
-- ---------------------------------------------------------------------
CREATE TABLE `wishlist` (
  `farmer_id` INT UNSIGNED NOT NULL,
  `equipment_id` INT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`farmer_id`,`equipment_id`),
  KEY `idx_wishlist_equipment` (`equipment_id`),
  CONSTRAINT `fk_wishlist_farmer` FOREIGN KEY (`farmer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_wishlist_equipment` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `reviews` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `booking_id` INT UNSIGNED NOT NULL,
  `farmer_id` INT UNSIGNED NOT NULL,
  `equipment_id` INT UNSIGNED NOT NULL,
  `rating` TINYINT UNSIGNED NOT NULL,
  `review` TEXT NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_reviews_booking` (`booking_id`),
  KEY `idx_reviews_equipment` (`equipment_id`),
  CONSTRAINT `fk_reviews_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_reviews_farmer` FOREIGN KEY (`farmer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_reviews_equipment` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chk_reviews_rating` CHECK (`rating` BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `complaints` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ticket_no` VARCHAR(20) DEFAULT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `booking_id` INT UNSIGNED DEFAULT NULL,
  `category` VARCHAR(40) NOT NULL,
  `priority` ENUM('low','medium','high') NOT NULL DEFAULT 'medium',
  `subject` VARCHAR(180) NOT NULL,
  `description` TEXT NOT NULL,
  `attachment` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('pending','in_review','resolved','closed') NOT NULL DEFAULT 'pending',
  `admin_response` TEXT DEFAULT NULL,
  `handled_by` INT UNSIGNED DEFAULT NULL,
  `client_uuid` CHAR(36) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_complaints_ticket` (`ticket_no`),
  UNIQUE KEY `uq_complaints_uuid` (`client_uuid`),
  KEY `idx_complaints_user_status` (`user_id`,`status`),
  CONSTRAINT `fk_complaints_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_complaints_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_complaints_handler` FOREIGN KEY (`handled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `emergency_requests` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `request_code` VARCHAR(20) DEFAULT NULL,
  `farmer_id` INT UNSIGNED NOT NULL,
  `category_id` INT UNSIGNED NOT NULL,
  `equipment_id` INT UNSIGNED DEFAULT NULL,
  `latitude` DECIMAL(9,6) NOT NULL,
  `longitude` DECIMAL(9,6) NOT NULL,
  `location_label` VARCHAR(150) DEFAULT NULL,
  `distance_km` DECIMAL(7,2) DEFAULT NULL,
  `needed_by` DATETIME NOT NULL,
  `details` VARCHAR(500) DEFAULT NULL,
  `status` ENUM('pending','approved','rejected','completed') NOT NULL DEFAULT 'pending',
  `handled_by` INT UNSIGNED DEFAULT NULL,
  `client_uuid` CHAR(36) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_emergency_code` (`request_code`),
  UNIQUE KEY `uq_emergency_uuid` (`client_uuid`),
  KEY `idx_emergency_farmer_status` (`farmer_id`,`status`),
  CONSTRAINT `fk_emergency_farmer` FOREIGN KEY (`farmer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_emergency_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_emergency_equipment` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_emergency_handler` FOREIGN KEY (`handled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `messages` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `sender_id` INT UNSIGNED NOT NULL,
  `receiver_id` INT UNSIGNED NOT NULL,
  `message` TEXT NOT NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_messages_pair` (`sender_id`,`receiver_id`,`id`),
  KEY `idx_messages_receiver_read` (`receiver_id`,`is_read`),
  CONSTRAINT `fk_messages_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_messages_receiver` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `notifications` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `type` ENUM('info','success','warning','danger') NOT NULL DEFAULT 'info',
  `title` VARCHAR(150) NOT NULL,
  `message` VARCHAR(500) NOT NULL,
  `link` VARCHAR(255) DEFAULT NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notifications_user_read` (`user_id`,`is_read`,`id`),
  CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Configuration, intelligence & audit
-- ---------------------------------------------------------------------
CREATE TABLE `settings` (
  `key` VARCHAR(60) NOT NULL,
  `value` VARCHAR(500) NOT NULL,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Smart equipment matching: crop + farming stage -> equipment category
CREATE TABLE `crop_rules` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `crop` VARCHAR(50) NOT NULL,
  `season` ENUM('land_preparation','planting','growing','harvest') NOT NULL,
  `category_id` INT UNSIGNED NOT NULL,
  `priority` TINYINT UNSIGNED NOT NULL DEFAULT 50,
  `min_farm_size` DECIMAL(8,2) NOT NULL DEFAULT 0,
  `max_farm_size` DECIMAL(8,2) DEFAULT NULL,
  `note` VARCHAR(150) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_crop_rule` (`crop`,`season`,`category_id`),
  CONSTRAINT `fk_crop_rules_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `price_change_logs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `equipment_id` INT UNSIGNED DEFAULT NULL,
  `field` VARCHAR(60) NOT NULL,
  `old_value` VARCHAR(100) DEFAULT NULL,
  `new_value` VARCHAR(100) DEFAULT NULL,
  `note` VARCHAR(255) DEFAULT NULL,
  `changed_by` INT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_price_logs_created` (`created_at`),
  CONSTRAINT `fk_price_logs_equipment` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_price_logs_user` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `reports` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `report_no` VARCHAR(20) NOT NULL,
  `type` ENUM('sales','payment','rental') NOT NULL,
  `date_from` DATE NOT NULL,
  `date_to` DATE NOT NULL,
  `row_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `total_amount` DECIMAL(14,2) NOT NULL DEFAULT 0,
  `checksum` CHAR(40) NOT NULL,
  `generated_by` INT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_reports_no` (`report_no`),
  CONSTRAINT `fk_reports_user` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Idempotency log for actions queued while offline (prevents duplicates on retry)
CREATE TABLE `sync_log` (
  `client_uuid` CHAR(36) NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `type` VARCHAR(30) NOT NULL,
  `result` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`client_uuid`),
  KEY `idx_sync_user` (`user_id`),
  CONSTRAINT `fk_sync_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- DEMO DATA
-- =====================================================================
INSERT INTO `settings` (`key`,`value`) VALUES
('platform_name','AgriRent'),('support_email','support@agrirent.com'),('support_phone','16247'),
('default_language','en'),('timezone','Asia/Dhaka'),('delivery_charge','300'),('emergency_radius_km','100'),
('enable_2fa_all','0'),('session_timeout_minutes','60'),('max_login_attempts','5'),('lockout_minutes','15'),
('suspicious_activity_alert','1'),
('season_mode','auto'),('current_season','normal'),('harvest_months','4,5,11,12'),('off_season_months','1,2,7'),
('harvest_increase_percent','20'),('off_season_discount_percent','10'),('min_rental_price','100'),
('email_notifications','1'),('payment_failure_alert','1'),('delivery_completion_alert','1'),
('complaint_ticket_alert','1'),('maintenance_alert','1'),
('method_bkash','1'),('method_nagad','1'),('method_card','1'),('method_cash','1'),
('default_usage_threshold','250'),('block_needs_maintenance','1');

-- password hashes: Admin@123 / Employee@123 / Farmer@123
INSERT INTO `users` (`id`,`name`,`email`,`phone`,`address`,`password`,`role`,`status`,`email_verified_at`,`verification_status`,`verification_document`,`verified_by`,`verified_at`,`employee_code`,`language`,`last_login_at`,`last_login_ip`,`created_at`) VALUES
(1,'System Admin','admin@agrirent.test','+8801700000001','Banani, Dhaka','$2y$10$a2SghD6gB3s7xWeh8MS0gu9GWswpSD2xoJO8y9FgU4O44xuZDrCeK','admin','active','2026-01-01 09:00:00','approved',NULL,NULL,'2026-01-01 09:00:00',NULL,'en','2026-10-21 09:15:00','127.0.0.1','2026-01-01 09:00:00'),
(2,'Karim Hossain','employee@agrirent.test','+8801711000002','Sherpur, Bogura','$2y$10$cng1PsiIQvcAVeOM.aS3QODzQiwJS/lLuitCJXCtcNsIevW2OPIU6','employee','active','2026-01-05 10:00:00','approved',NULL,1,'2026-01-05 12:00:00','EM-2026-01','en','2026-10-21 08:40:00','127.0.0.1','2026-01-05 10:00:00'),
(3,'Sumaiya Akter','sumaiya@agrirent.test','+8801711000003','Mirpur, Dhaka','$2y$10$cng1PsiIQvcAVeOM.aS3QODzQiwJS/lLuitCJXCtcNsIevW2OPIU6','employee','active','2026-02-10 10:00:00','approved',NULL,1,'2026-02-10 12:00:00','EM-2026-02','bn',NULL,NULL,'2026-02-10 10:00:00'),
(4,'Rahim Uddin','farmer@agrirent.test','+8801812000004','Sherpur, Bogura','$2y$10$ExipoU0Pwa7qPOQLIGXHwej9qemPczyPsBmotgLKtH/F5t4WoJEn.','farmer','active','2026-03-01 11:00:00','approved','uploads/verification/sample-nid.pdf',2,'2026-03-02 10:00:00',NULL,'en','2026-10-21 18:20:00','127.0.0.1','2026-03-01 11:00:00'),
(5,'Jamal Hasan','jamal@agrirent.test','+8801812000005','Paba, Rajshahi','$2y$10$ExipoU0Pwa7qPOQLIGXHwej9qemPczyPsBmotgLKtH/F5t4WoJEn.','farmer','active','2026-03-12 11:00:00','approved','uploads/verification/sample-nid.pdf',2,'2026-03-13 10:00:00',NULL,'bn',NULL,NULL,'2026-03-12 11:00:00'),
(6,'Salma Akter','salma@agrirent.test','+8801812000006','Dumuria, Khulna','$2y$10$ExipoU0Pwa7qPOQLIGXHwej9qemPczyPsBmotgLKtH/F5t4WoJEn.','farmer','active','2026-04-02 11:00:00','approved','uploads/verification/sample-nid.pdf',2,'2026-04-03 10:00:00',NULL,'en',NULL,NULL,'2026-04-02 11:00:00'),
(7,'Aminul Islam','aminul@agrirent.test','+8801812000007','Pirganj, Rangpur','$2y$10$ExipoU0Pwa7qPOQLIGXHwej9qemPczyPsBmotgLKtH/F5t4WoJEn.','farmer','active','2026-04-20 11:00:00','approved','uploads/verification/sample-nid.pdf',3,'2026-04-21 10:00:00',NULL,'bn',NULL,NULL,'2026-04-20 11:00:00'),
(8,'Nadia Islam','nadia@agrirent.test','+8801812000008','Trishal, Mymensingh','$2y$10$ExipoU0Pwa7qPOQLIGXHwej9qemPczyPsBmotgLKtH/F5t4WoJEn.','farmer','active','2026-10-18 15:00:00','pending','uploads/verification/sample-nid.pdf',NULL,NULL,NULL,'bn',NULL,NULL,'2026-10-18 15:00:00'),
(9,'Tania Sultana','tania@agrirent.test','+8801812000009','Madhupur, Tangail','$2y$10$ExipoU0Pwa7qPOQLIGXHwej9qemPczyPsBmotgLKtH/F5t4WoJEn.','farmer','active','2026-10-20 13:00:00','pending','uploads/verification/sample-nid.pdf',NULL,NULL,NULL,'en',NULL,NULL,'2026-10-20 13:00:00'),
(10,'Rafiq Mia','rafiq@agrirent.test','+8801812000010','Ishwardi, Pabna','$2y$10$ExipoU0Pwa7qPOQLIGXHwej9qemPczyPsBmotgLKtH/F5t4WoJEn.','farmer','active','2026-09-02 13:00:00','rejected',NULL,2,'2026-09-03 10:00:00',NULL,'en',NULL,NULL,'2026-09-02 13:00:00'),
(11,'Habib Rahman','habib@agrirent.test','+8801812000011','Natore Sadar, Natore','$2y$10$ExipoU0Pwa7qPOQLIGXHwej9qemPczyPsBmotgLKtH/F5t4WoJEn.','farmer','inactive',NULL,'pending',NULL,NULL,NULL,NULL,'en',NULL,NULL,'2026-10-21 10:00:00'),
(12,'Rasel Ahmed','rasel@agrirent.test','+8801711000012','Gazipur Sadar, Gazipur','$2y$10$cng1PsiIQvcAVeOM.aS3QODzQiwJS/lLuitCJXCtcNsIevW2OPIU6','employee','active','2026-10-19 09:00:00','pending','uploads/verification/sample-nid.pdf',NULL,NULL,'EM-2026-03','en',NULL,NULL,'2026-10-19 09:00:00');
UPDATE `users` SET `verification_note`='The submitted NID image was unreadable. Please upload a clear copy.' WHERE `id`=10;

INSERT INTO `categories` (`id`,`name`,`description`,`icon`) VALUES
(1,'Tractor','Tractors for ploughing, hauling and general field work','tractor'),
(2,'Power Tiller','Two-wheel tillers for small and medium plots','tiller'),
(3,'Harvester','Combine and reaper harvesters for rice, wheat and maize','harvester'),
(4,'Water Pump','Irrigation and drainage pumps','pump'),
(5,'Sprayer','Knapsack and power sprayers for crop protection','sprayer'),
(6,'Rotavator','Rotary tillers for seedbed preparation','rotavator'),
(7,'Seeder & Planter','Seed drills and rice transplanters','seeder'),
(8,'Thresher','Paddy threshers and maize shellers','thresher');

INSERT INTO `equipment` (`id`,`category_id`,`name`,`specs`,`description`,`price_per_day`,`quantity`,`location`,`latitude`,`longitude`,`image`,`owner_name`,`owner_phone`,`status`,`health_status`,`health_flag`,`usage_hours`,`hours_since_service`,`usage_threshold_hours`,`service_interval_days`,`last_service_date`,`added_by`,`created_at`) VALUES
(1,1,'Tractor 45 HP','45 HP | 4 Wheel Drive','This 45 HP tractor is suitable for ploughing, land preparation and other agricultural work. It is fuel efficient, easy to operate and suitable for medium to large farming areas.',1800,4,'Bogura',24.846500,89.377600,'assets/img/equipment/tractor.svg','MH Agro Service','+8801712345678','available','good',NULL,820.0,96.0,250,120,'2026-09-15',2,'2026-01-10 10:00:00'),
(2,1,'John Deere 5050D Tractor','50 HP | 2 Wheel Drive','Reliable 50 HP tractor for ploughing, hauling and general field operations. Comes with a trained operator on request.',2500,3,'Gazipur',23.999900,90.420300,'assets/img/equipment/tractor.svg','Gazipur Krishi Seba','+8801712000102','available','good',NULL,1210.0,140.0,250,120,'2026-07-01',2,'2026-01-12 10:00:00'),
(3,1,'Mahindra 575 DI Tractor','47 HP | 2 Wheel Drive','Powerful and economical tractor ideal for haulage, rotavator and cultivator work on medium farms.',2200,3,'Rajshahi',24.374500,88.604200,'assets/img/equipment/tractor.svg','Barind Agro Ltd.','+8801712000103','available','good',NULL,640.0,60.0,250,120,'2026-08-20',2,'2026-01-15 10:00:00'),
(4,2,'Power Tiller 7 HP','7 HP | Diesel','Fuel-efficient power tiller for small and medium plots. Easy to transport and operate in wet paddy fields.',1000,5,'Satkhira',22.718500,89.070500,'assets/img/equipment/tiller.svg','Shamol Krishi Ghor','+8801712000104','available','good',NULL,410.0,55.0,200,90,'2026-09-01',2,'2026-02-01 10:00:00'),
(5,2,'Power Tiller 9 HP','9 HP | Diesel | Rotary attachment','Heavy-duty power tiller with rotary attachment for faster seedbed preparation on clay soil.',1200,4,'Mymensingh',24.747100,90.420300,'assets/img/equipment/tiller.svg','Brahmaputra Agro','+8801712000105','available','good',NULL,530.0,72.0,200,90,'2026-08-25',2,'2026-02-03 10:00:00'),
(6,4,'Water Pump 3 inch','3 inch | 5 HP Diesel','Portable high-flow diesel pump with hose set for irrigation and drainage.',600,10,'Mymensingh',24.747100,90.420300,'assets/img/equipment/pump.svg','Brahmaputra Agro','+8801712000105','available','good',NULL,300.0,40.0,300,120,'2026-08-10',3,'2026-02-05 10:00:00'),
(7,4,'Diesel Water Pump 4 inch','4 inch | 8 HP Diesel','Large-capacity irrigation pump suitable for shallow tube-wells and canal lifting.',800,6,'Tangail',24.251300,89.916700,'assets/img/equipment/pump.svg','Tangail Sech Prokolpo','+8801712000107','available','good',NULL,260.0,30.0,300,120,'2026-09-05',3,'2026-02-07 10:00:00'),
(8,3,'Combine Harvester','Full feed | 2.0 m cutter','Combine harvester for rice and wheat that reaps, threshes and cleans grain in one pass. Operator included.',3500,2,'Rangpur',25.743900,89.275200,'assets/img/equipment/harvester.svg','North Bengal Harvest Co.','+8801712000108','available','good',NULL,690.0,180.0,250,120,'2026-07-20',2,'2026-02-10 10:00:00'),
(9,3,'Mini Rice Harvester','Reaper | 1.2 m cutter','Compact reaper-harvester for smaller paddy fields and terraced plots.',2800,2,'Dinajpur',25.621700,88.635500,'assets/img/equipment/harvester.svg','Dinajpur Agro Hub','+8801712000109','available','good',NULL,350.0,90.0,250,120,'2026-08-01',2,'2026-02-12 10:00:00'),
(10,5,'Sprayer 20L','20 L | Manual knapsack','Lightweight knapsack sprayer for pesticides, herbicides and foliar fertilizer.',300,12,'Khulna',22.845600,89.540300,'assets/img/equipment/sprayer.svg','BRI Agro Service','+8801712000110','available','good',NULL,120.0,20.0,400,180,'2026-07-01',3,'2026-02-15 10:00:00'),
(11,5,'Power Sprayer 25L','25 L | 2-stroke engine','Motorized power sprayer with 10 m reach for orchards and large fields.',450,8,'Jashore',23.166400,89.208100,'assets/img/equipment/sprayer.svg','Jashore Krishi Yantra','+8801712000111','available','good',NULL,210.0,35.0,300,180,'2026-07-15',3,'2026-02-18 10:00:00'),
(12,6,'Rotavator 6 ft','6 ft | 42 blades','Tractor-mounted rotavator for fine seedbed preparation and stubble mixing.',1500,3,'Kushtia',23.901300,89.120500,'assets/img/equipment/rotavator.svg','Kushtia Farm Tools','+8801712000112','available','good',NULL,380.0,45.0,250,120,'2026-08-10',2,'2026-03-01 10:00:00'),
(13,7,'Rice Transplanter 4-Row','4 rows | Walk-behind','Walk-behind rice transplanter that plants mat-type seedlings evenly and saves labour.',2000,2,'Natore',24.420600,88.984600,'assets/img/equipment/seeder.svg','Natore Agro Mechanics','+8801712000113','available','good',NULL,190.0,30.0,200,120,'2026-08-15',2,'2026-03-05 10:00:00'),
(14,7,'Seed Drill 9-Row','9 rows | Tractor drawn','Tractor-drawn seed drill for wheat, lentil and mustard with fertilizer box.',1300,2,'Pabna',24.006400,89.237200,'assets/img/equipment/seeder.svg','Pabna Krishi Seba','+8801712000114','available','needs_maintenance',NULL,240.0,140.0,200,120,'2026-05-20',2,'2026-03-08 10:00:00'),
(15,8,'Paddy Thresher','Pedal & engine | 1.5 t/h','Engine-driven paddy thresher with high output and low grain breakage.',900,5,'Sirajganj',24.453400,89.700700,'assets/img/equipment/thresher.svg','Jamuna Agro','+8801712000115','available','good',NULL,280.0,40.0,250,120,'2026-08-30',3,'2026-03-10 10:00:00'),
(16,8,'Maize Sheller','Electric | 2 t/h','Electric maize sheller that separates kernels quickly with minimal damage.',700,3,'Cumilla',23.460700,91.180900,'assets/img/equipment/thresher.svg','Comilla Krishi Bazar','+8801712000116','available','unknown','Usage hours were not recorded for the last return.',150.0,0.0,250,120,'2026-06-01',3,'2026-03-12 10:00:00'),
(17,3,'Kubota DC-70 Harvester','70 HP | Combine','High-capacity combine harvester for large paddy fields. Currently under engine overhaul.',3000,1,'Pabna',24.006400,89.237200,'assets/img/equipment/harvester.svg','Pabna Krishi Seba','+8801712000114','maintenance','under_maintenance',NULL,1490.0,262.0,250,120,'2026-04-02',2,'2026-03-15 10:00:00');

INSERT INTO `bookings` (`id`,`booking_code`,`farmer_id`,`equipment_id`,`start_date`,`end_date`,`days`,`base_price`,`price_per_day`,`season`,`rental_cost`,`delivery_charge`,`discount`,`total_amount`,`delivery_address`,`status`,`status_note`,`cancellation_reason`,`cancellation_details`,`cancelled_at`,`created_at`) VALUES
(1,'AGR00001',4,1,'2026-05-03','2026-05-06',4,1800,2160,'harvest',8640,300,0,8940,'Sherpur, Bogura','completed',NULL,NULL,NULL,NULL,'2026-04-28 10:15:00'),
(2,'AGR00002',5,8,'2026-05-10','2026-05-12',3,3500,4200,'harvest',12600,300,0,12900,'Paba, Rajshahi','completed',NULL,NULL,NULL,NULL,'2026-05-05 11:20:00'),
(3,'AGR00003',6,6,'2026-06-02','2026-06-05',4,600,600,'normal',2400,300,0,2700,'Dumuria, Khulna','completed',NULL,NULL,NULL,NULL,'2026-05-30 09:00:00'),
(4,'AGR00004',7,4,'2026-06-15','2026-06-17',3,1000,1000,'normal',3000,300,0,3300,'Pirganj, Rangpur','completed',NULL,NULL,NULL,NULL,'2026-06-10 16:45:00'),
(5,'AGR00005',4,12,'2026-07-05','2026-07-07',3,1500,1350,'off_season',4050,300,435,3915,'Sherpur, Bogura','completed',NULL,NULL,NULL,NULL,'2026-07-01 12:00:00'),
(6,'AGR00006',5,10,'2026-07-20','2026-07-22',3,300,270,'off_season',810,300,0,1110,'Paba, Rajshahi','completed',NULL,NULL,NULL,NULL,'2026-07-18 08:30:00'),
(7,'AGR00007',6,2,'2026-08-04','2026-08-07',4,2500,2500,'normal',10000,300,0,10300,'Dumuria, Khulna','completed',NULL,NULL,NULL,NULL,'2026-08-01 14:10:00'),
(8,'AGR00008',7,15,'2026-08-18','2026-08-20',3,900,900,'normal',2700,300,0,3000,'Pirganj, Rangpur','cancelled',NULL,'Change of plans','Harvest was delayed because of heavy rain.','2026-08-14 10:00:00','2026-08-12 10:00:00'),
(9,'AGR00009',4,6,'2026-09-01','2026-09-03',3,600,600,'normal',1800,300,0,2100,'Sherpur, Bogura','completed',NULL,NULL,NULL,NULL,'2026-08-28 17:30:00'),
(10,'AGR00010',5,13,'2026-09-10','2026-09-13',4,2000,2000,'normal',8000,300,0,8300,'Paba, Rajshahi','completed',NULL,NULL,NULL,NULL,'2026-09-05 10:05:00'),
(11,'AGR00011',6,11,'2026-09-22','2026-09-24',3,450,450,'normal',1350,300,0,1650,'Dumuria, Khulna','completed',NULL,NULL,NULL,NULL,'2026-09-20 13:00:00'),
(12,'AGR00012',7,5,'2026-10-05','2026-10-08',4,1200,1200,'normal',4800,300,0,5100,'Pirganj, Rangpur','returned',NULL,NULL,NULL,NULL,'2026-10-01 09:40:00'),
(13,'AGR00013',4,10,'2026-10-17','2026-10-20',4,300,300,'normal',1200,300,0,1500,'Sherpur, Bogura','returned',NULL,NULL,NULL,NULL,'2026-10-14 11:00:00'),
(14,'AGR00014',4,1,'2026-10-20','2026-10-24',5,1800,1800,'normal',9000,300,0,9300,'Sherpur, Bogura','delivered',NULL,NULL,NULL,NULL,'2026-10-16 15:30:00'),
(15,'AGR00015',5,7,'2026-10-21','2026-10-23',3,800,800,'normal',2400,300,0,2700,'Paba, Rajshahi','delivered',NULL,NULL,NULL,NULL,'2026-10-18 10:30:00'),
(16,'AGR00016',4,4,'2026-10-26','2026-10-29',4,1000,1000,'normal',4000,300,0,4300,'Sherpur, Bogura','confirmed',NULL,NULL,NULL,NULL,'2026-10-19 12:00:00'),
(17,'AGR00017',6,9,'2026-11-02','2026-11-04',3,2800,3360,'harvest',10080,300,0,10380,'Dumuria, Khulna','pending',NULL,NULL,NULL,NULL,'2026-10-20 18:00:00'),
(18,'AGR00018',7,3,'2026-11-05','2026-11-07',3,2200,2640,'harvest',7920,300,0,8220,'Pirganj, Rangpur','pending',NULL,NULL,NULL,NULL,'2026-10-21 09:10:00'),
(19,'AGR00019',4,5,'2026-11-10','2026-11-14',5,1200,1440,'harvest',7200,300,0,7500,'Sherpur, Bogura','pending',NULL,NULL,NULL,NULL,'2026-10-21 19:30:00'),
(20,'AGR00020',5,16,'2026-10-12','2026-10-14',3,700,700,'normal',2100,300,0,2400,'Paba, Rajshahi','rejected','Equipment was reserved for a scheduled inspection on these dates.',NULL,NULL,NULL,'2026-10-09 10:00:00'),
(21,'AGR00021',6,14,'2026-10-28','2026-10-30',3,1300,1300,'normal',3900,300,0,4200,'Dumuria, Khulna','cancelled',NULL,'Booked by mistake','Selected the wrong equipment.','2026-10-16 11:00:00','2026-10-15 16:20:00');

INSERT INTO `booking_extensions` (`booking_id`,`current_end_date`,`requested_end_date`,`extra_days`,`extra_cost`,`reason`,`status`,`reviewed_by`,`reviewed_at`,`response_note`,`created_at`) VALUES
(14,'2026-10-24','2026-10-26',2,3600,'Land preparation is taking longer because of wet soil.','pending',NULL,NULL,NULL,'2026-10-21 20:00:00'),
(7,'2026-08-06','2026-08-07',1,2500,'Need one more day to finish ploughing.','approved',2,'2026-08-05 10:00:00','Approved - equipment is free.','2026-08-05 08:00:00');

INSERT INTO `coupons` (`id`,`code`,`description`,`discount_type`,`discount_value`,`min_amount`,`max_discount`,`starts_at`,`expires_at`,`usage_limit`,`used_count`,`status`) VALUES
(1,'FARM10','10% off any rental (max ৳1,000)','percent',10,500,1000,'2026-01-01','2026-12-31',500,1,'active'),
(2,'HARVEST500','৳500 off rentals above ৳3,000','fixed',500,3000,NULL,'2026-01-01','2026-12-31',200,0,'active'),
(3,'MONSOON15','15% monsoon discount (expired)','percent',15,0,NULL,'2026-06-01','2026-08-31',NULL,0,'active');

INSERT INTO `payments` (`invoice_no`,`booking_id`,`method`,`amount`,`discount`,`coupon_id`,`transaction_id`,`account_ref`,`status`,`failure_reason`,`paid_at`,`receipt_emailed`,`invoice_generated_at`,`created_at`) VALUES
('INV-2026-0001',1,'bkash',8940,0,NULL,'TXN8F3K2A91','01812****04','paid',NULL,'2026-04-28 10:20:00',1,'2026-04-28 10:20:00','2026-04-28 10:20:00'),
('INV-2026-0002',2,'card',12900,0,NULL,'TXN2B7Q9C10','VISA **** 4242','paid',NULL,'2026-05-05 11:30:00',1,'2026-05-05 11:30:00','2026-05-05 11:30:00'),
('INV-2026-0003',3,'cash',2700,0,NULL,'CASH-0003',NULL,'paid',NULL,'2026-06-02 15:00:00',1,'2026-06-02 15:00:00','2026-05-30 09:05:00'),
('INV-2026-0004',4,'bkash',3300,0,NULL,'TXN5D1L8E22','01812****07','paid',NULL,'2026-06-10 16:50:00',1,'2026-06-10 16:50:00','2026-06-10 16:50:00'),
('INV-2026-0005',5,'card',3915,435,1,'TXN9H4M2F33','MASTERCARD **** 5100','paid',NULL,'2026-07-01 12:10:00',1,'2026-07-01 12:10:00','2026-07-01 12:10:00'),
('INV-2026-0006',6,'nagad',1110,0,NULL,'TXN3N6P7G44','01812****05','paid',NULL,'2026-07-18 08:35:00',1,NULL,'2026-07-18 08:35:00'),
('INV-2026-0007',7,'card',10300,0,NULL,'TXN7R2S5H55','VISA **** 1881','paid',NULL,'2026-08-01 14:15:00',1,'2026-08-01 14:15:00','2026-08-01 14:15:00'),
('INV-2026-0008',8,'bkash',3000,0,NULL,'TXN1T8U3J66','01812****07','refunded',NULL,'2026-08-12 10:05:00',1,NULL,'2026-08-12 10:05:00'),
('INV-2026-0009',9,'cash',2100,0,NULL,'CASH-0009',NULL,'paid',NULL,'2026-09-01 12:00:00',1,NULL,'2026-08-28 17:35:00'),
('INV-2026-0010',10,'bkash',8300,0,NULL,'TXN4V9W1K77','01812****05','paid',NULL,'2026-09-05 10:10:00',1,'2026-09-05 10:10:00','2026-09-05 10:10:00'),
('INV-2026-0011',11,'card',1650,0,NULL,'TXN6X2Y4L88','VISA **** 4242','paid',NULL,'2026-09-20 13:05:00',1,NULL,'2026-09-20 13:05:00'),
('INV-2026-0012',12,'bkash',5100,0,NULL,'TXN8Z5A6M99','01812****07','paid',NULL,'2026-10-01 09:45:00',1,NULL,'2026-10-01 09:45:00'),
('INV-2026-0013',13,'bkash',1500,0,NULL,'TXN2C7D8N10','01812****04','paid',NULL,'2026-10-14 11:05:00',1,'2026-10-14 11:05:00','2026-10-14 11:05:00'),
('INV-2026-0014',14,'card',9300,0,NULL,'TXN5E9F1P21','VISA **** 4242','paid',NULL,'2026-10-16 15:35:00',1,NULL,'2026-10-16 15:35:00'),
('INV-2026-0015',15,'cash',2700,0,NULL,NULL,NULL,'pending',NULL,NULL,0,NULL,'2026-10-18 10:35:00'),
('INV-2026-0016',16,'bkash',4300,0,NULL,'TXN7G3H4Q32','01812****04','paid',NULL,'2026-10-19 12:05:00',1,NULL,'2026-10-19 12:05:00'),
('INV-2026-0017',17,'card',10380,0,NULL,NULL,'VISA **** 0002','failed','Card declined by the issuing bank.',NULL,0,NULL,'2026-10-20 18:05:00'),
('INV-2026-0018',18,'cash',8220,0,NULL,NULL,NULL,'pending',NULL,NULL,0,NULL,'2026-10-21 09:15:00'),
('INV-2026-0019',21,'nagad',4200,0,NULL,'TXN9J6K2R43','01812****06','refunded',NULL,'2026-10-15 16:25:00',1,NULL,'2026-10-15 16:25:00');

INSERT INTO `delivery_teams` (`id`,`name`,`leader_name`,`phone`,`email`,`vehicle`) VALUES
(1,'Team A','Abdul Malek','+8801711500001','team.a@agrirent.com','Low-bed truck DHA-11-2034'),
(2,'Team B','Shafiqul Islam','+8801711500002','team.b@agrirent.com','Pickup BOG-15-8877'),
(3,'Team C','Mizanur Rahman','+8801711500003','team.c@agrirent.com','Covered van KHU-12-4410');

INSERT INTO `deliveries` (`id`,`booking_id`,`team_id`,`address`,`scheduled_date`,`estimated_time`,`status`,`progress`,`delivered_at`,`returned_at`,`created_at`) VALUES
(1,1,2,'Sherpur, Bogura','2026-05-03','10:00 AM','returned',100,'2026-05-03 10:30:00','2026-05-06 18:00:00','2026-04-28 10:15:00'),
(2,2,1,'Paba, Rajshahi','2026-05-10','09:00 AM','returned',100,'2026-05-10 09:40:00','2026-05-12 19:00:00','2026-05-05 11:20:00'),
(3,3,3,'Dumuria, Khulna','2026-06-02','11:00 AM','returned',100,'2026-06-02 11:20:00','2026-06-05 17:00:00','2026-05-30 09:00:00'),
(4,4,1,'Pirganj, Rangpur','2026-06-15','10:00 AM','returned',100,'2026-06-15 10:10:00','2026-06-17 17:30:00','2026-06-10 16:45:00'),
(5,5,2,'Sherpur, Bogura','2026-07-05','09:30 AM','returned',100,'2026-07-05 09:45:00','2026-07-07 18:10:00','2026-07-01 12:00:00'),
(6,6,1,'Paba, Rajshahi','2026-07-20','10:00 AM','returned',100,'2026-07-20 10:00:00','2026-07-22 17:00:00','2026-07-18 08:30:00'),
(7,7,3,'Dumuria, Khulna','2026-08-04','08:30 AM','returned',100,'2026-08-04 09:00:00','2026-08-07 18:30:00','2026-08-01 14:10:00'),
(8,8,NULL,'Pirganj, Rangpur',NULL,NULL,'cancelled',0,NULL,NULL,'2026-08-12 10:00:00'),
(9,9,2,'Sherpur, Bogura','2026-09-01','10:00 AM','returned',100,'2026-09-01 10:15:00','2026-09-03 17:45:00','2026-08-28 17:30:00'),
(10,10,1,'Paba, Rajshahi','2026-09-10','09:00 AM','returned',100,'2026-09-10 09:30:00','2026-09-13 18:00:00','2026-09-05 10:05:00'),
(11,11,3,'Dumuria, Khulna','2026-09-22','11:00 AM','returned',100,'2026-09-22 11:30:00','2026-09-24 16:00:00','2026-09-20 13:00:00'),
(12,12,1,'Pirganj, Rangpur','2026-10-05','10:00 AM','returned',100,'2026-10-05 10:20:00','2026-10-08 17:20:00','2026-10-01 09:40:00'),
(13,13,3,'Sherpur, Bogura','2026-10-17','10:00 AM','returned',100,'2026-10-17 10:05:00','2026-10-20 18:00:00','2026-10-14 11:00:00'),
(14,14,2,'Sherpur, Bogura','2026-10-20','09:00 AM','delivered',100,'2026-10-20 09:25:00',NULL,'2026-10-16 15:30:00'),
(15,15,1,'Paba, Rajshahi','2026-10-21','11:00 AM','delivered',100,'2026-10-21 11:40:00',NULL,'2026-10-18 10:30:00'),
(16,16,2,'Sherpur, Bogura','2026-10-26','10:00 AM','assigned',25,NULL,NULL,'2026-10-19 12:00:00'),
(17,17,NULL,'Dumuria, Khulna',NULL,NULL,'pending',0,NULL,NULL,'2026-10-20 18:00:00'),
(18,18,NULL,'Pirganj, Rangpur',NULL,NULL,'pending',0,NULL,NULL,'2026-10-21 09:10:00'),
(19,19,NULL,'Sherpur, Bogura',NULL,NULL,'pending',0,NULL,NULL,'2026-10-21 19:30:00'),
(20,21,NULL,'Dumuria, Khulna',NULL,NULL,'cancelled',0,NULL,NULL,'2026-10-15 16:20:00');

INSERT INTO `delivery_updates` (`delivery_id`,`status`,`note`,`updated_by`,`created_at`) VALUES
(14,'assigned','Assigned to Team B',1,'2026-10-18 10:00:00'),
(14,'in_progress','Loaded and on the way to Sherpur',1,'2026-10-20 07:30:00'),
(14,'delivered','Delivered to farmer and handed over keys',1,'2026-10-20 09:25:00'),
(15,'assigned','Assigned to Team A',1,'2026-10-19 09:00:00'),
(15,'delivered','Delivered and demo provided',1,'2026-10-21 11:40:00'),
(16,'assigned','Assigned to Team B for 26 Oct morning',1,'2026-10-20 12:00:00');

INSERT INTO `maintenance` (`equipment_id`,`scheduled_date`,`end_date`,`type`,`notes`,`status`,`created_by`,`completed_at`,`created_at`) VALUES
(17,'2026-10-20','2026-10-27','repair','Engine overhaul and feeder belt replacement after harvest season.','in_progress',2,NULL,'2026-10-18 10:00:00'),
(8,'2026-11-15','2026-11-16','routine','Pre-harvest servicing: blades, belts and grain tank check.','scheduled',2,NULL,'2026-10-19 11:00:00'),
(1,'2026-09-15','2026-09-15','routine','Engine oil, filters and hydraulic check.','completed',2,'2026-09-15 16:00:00','2026-09-10 09:00:00'),
(12,'2026-08-10','2026-08-10','routine','Blade sharpening and gearbox oil change.','completed',3,'2026-08-10 15:00:00','2026-08-05 09:00:00'),
(14,'2026-11-01','2026-11-02','inspection','Service interval exceeded - full inspection needed.','scheduled',3,NULL,'2026-10-21 10:00:00');

INSERT INTO `maintenance_alerts` (`equipment_id`,`type`,`message`,`status`,`created_at`) VALUES
(17,'usage_threshold','Kubota DC-70 Harvester exceeded its 250 h service threshold (262 h since last service).','open','2026-10-17 18:00:00'),
(14,'service_due','Seed Drill 9-Row service is overdue (interval 120 days, last serviced 20 May 2026).','open','2026-10-21 09:00:00'),
(16,'missing_data','Usage hours were not recorded for Maize Sheller. Please update the usage log.','open','2026-10-14 12:00:00'),
(2,'damaged','John Deere 5050D reported minor hydraulic leak on return (AGR00007).','resolved','2026-08-08 10:00:00');
UPDATE `maintenance_alerts` SET `resolved_at`='2026-08-12 10:00:00' WHERE `status`='resolved';

INSERT INTO `inspections` (`booking_id`,`equipment_id`,`condition`,`comments`,`usage_hours`,`inspected_by`,`created_at`) VALUES
(1,1,'good','Returned clean, no issues.',28,2,'2026-05-07 10:00:00'),
(2,8,'needs_service','Cutter blades worn; schedule sharpening.',26,2,'2026-05-13 10:00:00'),
(3,6,'good','Hose set complete.',30,3,'2026-06-06 10:00:00'),
(4,4,'good',NULL,22,2,'2026-06-18 10:00:00'),
(5,12,'good','Minor mud on blades, cleaned.',20,2,'2026-07-08 10:00:00'),
(6,10,'good',NULL,12,3,'2026-07-23 10:00:00'),
(7,2,'damaged','Minor hydraulic leak found on the left lift arm.',34,2,'2026-08-08 10:00:00'),
(9,6,'good',NULL,21,3,'2026-09-04 10:00:00'),
(10,13,'good','All rows working.',27,2,'2026-09-14 10:00:00'),
(11,11,'good',NULL,15,3,'2026-09-25 10:00:00');

INSERT INTO `usage_logs` (`equipment_id`,`booking_id`,`hours`,`source`,`note`,`flagged`,`logged_by`,`created_at`) VALUES
(1,1,28,'return',NULL,0,2,'2026-05-07 10:00:00'),(8,2,26,'return',NULL,0,2,'2026-05-13 10:00:00'),
(6,3,30,'return',NULL,0,3,'2026-06-06 10:00:00'),(4,4,22,'return',NULL,0,2,'2026-06-18 10:00:00'),
(12,5,20,'return',NULL,0,2,'2026-07-08 10:00:00'),(10,6,12,'return',NULL,0,3,'2026-07-23 10:00:00'),
(2,7,34,'return',NULL,0,2,'2026-08-08 10:00:00'),(6,9,21,'return',NULL,0,3,'2026-09-04 10:00:00'),
(13,10,27,'return',NULL,0,2,'2026-09-14 10:00:00'),(11,11,15,'return',NULL,0,3,'2026-09-25 10:00:00'),
(16,NULL,NULL,'manual','Hour meter reading missing after last rental.',1,3,'2026-10-14 12:00:00'),
(17,NULL,48,'manual','Field demo usage logged by employee.',0,2,'2026-10-16 17:00:00');

INSERT INTO `wishlist` (`farmer_id`,`equipment_id`,`created_at`) VALUES
(4,1,'2026-10-01 10:00:00'),(4,5,'2026-10-02 10:00:00'),(4,8,'2026-10-03 10:00:00'),(5,12,'2026-10-05 10:00:00');

INSERT INTO `reviews` (`booking_id`,`farmer_id`,`equipment_id`,`rating`,`review`,`created_at`) VALUES
(1,4,1,5,'The tractor was in excellent condition and arrived right on time.','2026-05-07 12:00:00'),
(2,5,8,4,'Harvested 6 acres in two days. Blades could be sharper.','2026-05-13 12:00:00'),
(3,6,6,5,'Strong water flow and fuel efficient. Highly recommended.','2026-06-06 12:00:00'),
(4,7,4,4,'Good tiller for wet land. Delivery team was helpful.','2026-06-18 12:00:00'),
(5,4,12,4,'Prepared a fine seedbed quickly.','2026-07-08 12:00:00'),
(7,6,2,5,'Powerful tractor and a very professional operator.','2026-08-08 12:00:00'),
(9,4,6,4,'Worked well for irrigation of my boro field.','2026-09-04 12:00:00'),
(10,5,13,5,'Saved a lot of labour cost during transplanting.','2026-09-14 12:00:00'),
(11,6,11,3,'Engine took time to start but sprayed evenly.','2026-09-25 12:00:00');

INSERT INTO `complaints` (`id`,`ticket_no`,`user_id`,`booking_id`,`category`,`priority`,`subject`,`description`,`status`,`admin_response`,`handled_by`,`created_at`) VALUES
(1,'CMP-2026-0001',4,14,'Equipment Issue','high','Tractor started but stopped suddenly','The tractor stopped in the middle of ploughing and would not restart for 30 minutes.','resolved','A technician visited and replaced the fuel filter. Sorry for the trouble.',2,'2026-10-20 15:00:00'),
(2,'CMP-2026-0002',5,2,'Equipment Issue','medium','Harvester not as described in listing','The cutter width was smaller than shown in the listing.','in_review',NULL,2,'2026-10-13 10:00:00'),
(3,'CMP-2026-0003',6,11,'Delivery Issue','low','Late delivery by owner','Equipment arrived 2 hours late.','pending',NULL,NULL,'2026-10-04 12:00:00'),
(4,'CMP-2026-0004',4,16,'Payment Issue','high','Payment confirmation not received','I paid with bKash but did not receive a receipt email.','pending',NULL,NULL,'2026-10-21 08:00:00');

INSERT INTO `emergency_requests` (`id`,`request_code`,`farmer_id`,`category_id`,`equipment_id`,`latitude`,`longitude`,`location_label`,`distance_km`,`needed_by`,`details`,`status`,`handled_by`,`created_at`) VALUES
(1,'EMR-2026-0001',4,4,7,24.846500,89.377600,'Sherpur, Bogura',85.75,'2026-10-15 08:00:00','Field flooding after heavy rain, need drainage pump urgently.','approved',2,'2026-10-14 21:00:00'),
(2,'EMR-2026-0002',7,3,8,25.750000,89.260000,'Pirganj, Rangpur',1.67,'2026-10-23 07:00:00','Storm forecast - need to harvest paddy before it arrives.','pending',NULL,'2026-10-21 20:30:00');

INSERT INTO `messages` (`sender_id`,`receiver_id`,`message`,`is_read`,`created_at`) VALUES
(2,4,'Hello Rahim! How can I help you today?',1,'2026-10-21 10:30:00'),
(4,2,'I want to know the delivery charge for the power tiller in my area.',1,'2026-10-21 10:32:00'),
(2,4,'Delivery inside Bogura district is ৳300. Could you please confirm your delivery location?',1,'2026-10-21 10:35:00'),
(4,2,'Sherpur, Bogura. Thank you!',0,'2026-10-21 10:40:00'),
(2,5,'Your diesel pump is out for delivery today.',0,'2026-10-21 09:00:00');

INSERT INTO `notifications` (`user_id`,`type`,`title`,`message`,`link`,`is_read`,`created_at`) VALUES
(4,'warning','Return date reminder','Your Tractor 45 HP rental ends on 24 Oct 2026.','index.php?r=booking/show&id=14',0,'2026-10-22 08:00:00'),
(4,'success','Delivery completed','Tractor 45 HP (AGR00014) has been delivered to Sherpur, Bogura.','index.php?r=booking/show&id=14',0,'2026-10-20 09:25:00'),
(4,'success','Booking confirmed','Your booking AGR00016 for Power Tiller 7 HP has been confirmed.','index.php?r=booking/show&id=16',1,'2026-10-19 13:00:00'),
(4,'info','Support ticket updated','Ticket CMP-2026-0001 is now Resolved.','index.php?r=complaint/show&id=1',1,'2026-10-20 18:00:00'),
(1,'danger','Payment failed','Payment for AGR00017 (Mini Rice Harvester) failed: Card declined by the issuing bank.','index.php?r=payment/admin',0,'2026-10-20 18:05:00'),
(1,'warning','Maintenance needed','Kubota DC-70 Harvester exceeded its usage threshold.','index.php?r=maintenance/health',0,'2026-10-17 18:00:00'),
(1,'info','New cancellation','AGR00021 was cancelled by Salma Akter (Booked by mistake).','index.php?r=rental/index',0,'2026-10-16 11:00:00'),
(1,'success','Delivery completed','AGR00015 was delivered to Paba, Rajshahi by Team A.','index.php?r=delivery/index',0,'2026-10-21 11:40:00'),
(1,'info','Verification request','Rasel Ahmed registered as an employee and is waiting for approval.','index.php?r=verification/index',0,'2026-10-19 09:00:00'),
(2,'info','New rental request','AGR00019 for Power Tiller 9 HP is waiting for approval.','index.php?r=rental/index',0,'2026-10-21 19:30:00'),
(2,'warning','Emergency request','Aminul Islam needs a Harvester urgently in Pirganj, Rangpur.','index.php?r=emergency/index',0,'2026-10-21 20:30:00'),
(2,'info','Extension requested','AGR00014 requests an extension to 26 Oct 2026.','index.php?r=rental/index',0,'2026-10-21 20:00:00');

INSERT INTO `crop_rules` (`crop`,`season`,`category_id`,`priority`,`min_farm_size`,`max_farm_size`,`note`) VALUES
('Rice','land_preparation',1,90,2,NULL,'Puddling and ploughing large paddy fields'),
('Rice','land_preparation',2,85,0,10,'Best for small wet paddy plots'),
('Rice','land_preparation',6,70,2,NULL,'Fine puddling with rotavator'),
('Rice','planting',7,95,0,NULL,'Mechanical transplanting saves labour'),
('Rice','planting',2,50,0,5,'Final puddling before transplanting'),
('Rice','growing',4,90,0,NULL,'Irrigation for boro / aman'),
('Rice','growing',5,80,0,NULL,'Pest and disease control'),
('Rice','harvest',3,95,1,NULL,'Combine harvesting'),
('Rice','harvest',8,80,0,NULL,'Threshing after manual reaping'),
('Wheat','land_preparation',1,90,2,NULL,'Ploughing'),
('Wheat','land_preparation',6,80,0,NULL,'Seedbed preparation'),
('Wheat','land_preparation',2,70,0,8,'Small plots'),
('Wheat','planting',7,90,0,NULL,'Line sowing with seed drill'),
('Wheat','growing',5,85,0,NULL,'Weed and rust control'),
('Wheat','growing',4,75,0,NULL,'Crown root irrigation'),
('Wheat','harvest',3,95,2,NULL,'Combine harvesting'),
('Wheat','harvest',8,85,0,NULL,'Threshing'),
('Maize','land_preparation',1,90,2,NULL,'Deep ploughing'),
('Maize','land_preparation',6,80,0,NULL,'Seedbed preparation'),
('Maize','planting',7,85,0,NULL,'Row planting'),
('Maize','growing',4,85,0,NULL,'Irrigation'),
('Maize','growing',5,80,0,NULL,'Fall armyworm control'),
('Maize','harvest',8,90,0,NULL,'Shelling'),
('Maize','harvest',3,75,3,NULL,'Combine with maize header'),
('Potato','land_preparation',1,85,2,NULL,'Ploughing and ridging'),
('Potato','land_preparation',2,85,0,8,'Small plots'),
('Potato','land_preparation',6,75,0,NULL,'Fine tilth for tubers'),
('Potato','planting',7,70,0,NULL,'Planter'),
('Potato','growing',5,90,0,NULL,'Late blight control'),
('Potato','growing',4,80,0,NULL,'Irrigation'),
('Potato','harvest',1,75,2,NULL,'Tractor-drawn digger'),
('Jute','land_preparation',2,90,0,NULL,'Tilling'),
('Jute','land_preparation',1,80,3,NULL,'Ploughing'),
('Jute','planting',7,70,0,NULL,'Line sowing'),
('Jute','growing',5,80,0,NULL,'Pest control'),
('Jute','growing',4,75,0,NULL,'Irrigation in dry spells'),
('Vegetables','land_preparation',2,90,0,NULL,'Bed preparation'),
('Vegetables','land_preparation',6,85,0,NULL,'Fine tilth'),
('Vegetables','planting',7,65,0,NULL,'Seeding'),
('Vegetables','growing',5,90,0,NULL,'Spraying'),
('Vegetables','growing',4,85,0,NULL,'Irrigation'),
('Mustard','land_preparation',2,85,0,NULL,'Tilling'),
('Mustard','land_preparation',1,80,2,NULL,'Ploughing'),
('Mustard','planting',7,85,0,NULL,'Seed drill'),
('Mustard','growing',5,80,0,NULL,'Aphid control'),
('Mustard','harvest',8,80,0,NULL,'Threshing'),
('Sugarcane','land_preparation',1,95,2,NULL,'Deep ploughing'),
('Sugarcane','land_preparation',6,80,0,NULL,'Furrow preparation'),
('Sugarcane','growing',4,85,0,NULL,'Irrigation'),
('Sugarcane','harvest',1,70,0,NULL,'Haulage to mill');

INSERT INTO `price_change_logs` (`equipment_id`,`field`,`old_value`,`new_value`,`note`,`changed_by`,`created_at`) VALUES
(NULL,'harvest_increase_percent','15','20','Harvest demand is higher this year',1,'2026-03-01 10:00:00'),
(NULL,'off_season_discount_percent','5','10','Encourage off-season rentals',1,'2026-03-01 10:00:00'),
(1,'price_per_day','1700','1800','Fuel price adjustment',2,'2026-06-01 10:00:00'),
(8,'price_per_day','3200','3500','Operator cost increase',1,'2026-04-15 10:00:00');

-- (no seeded login_success rows: the first real sign-in of each demo account registers its device)
INSERT INTO `security_logs` (`user_id`,`email`,`event`,`ip`,`user_agent`,`details`,`created_at`) VALUES
(NULL,'unknown@example.com','login_failed','10.0.0.24','Mozilla/5.0','Unknown email','2026-10-21 02:13:00'),
(5,'jamal@agrirent.test','login_failed','10.0.0.31','Mozilla/5.0','Wrong password (attempt 1)','2026-10-20 23:10:00'),
(NULL,NULL,'settings_pricing','127.0.0.1','Mozilla/5.0','Pricing rules updated by System Admin','2026-03-01 10:00:00');

INSERT INTO `reports` (`report_no`,`type`,`date_from`,`date_to`,`row_count`,`total_amount`,`checksum`,`generated_by`,`created_at`) VALUES
('RPT-2026-0001','sales','2026-05-01','2026-05-31',2,21840,'seed0000000000000000000000000000000000001',1,'2026-06-01 09:00:00'),
('RPT-2026-0002','rental','2026-09-01','2026-09-30',3,12050,'seed0000000000000000000000000000000000002',1,'2026-10-01 09:00:00'),
('RPT-2026-0003','payment','2026-10-01','2026-10-21',7,25100,'seed0000000000000000000000000000000000003',1,'2026-10-21 18:00:00');

SET FOREIGN_KEY_CHECKS = 1;
