-- Separate database for registration requests awaiting administrator approval.
-- Import this file once in phpMyAdmin if the application database user cannot
-- create databases automatically. The PHP application also runs these CREATE
-- statements with IF NOT EXISTS during startup.

CREATE DATABASE IF NOT EXISTS `pending_encryption_system`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;

USE `pending_encryption_system`;

CREATE TABLE IF NOT EXISTS `pending_registrations` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` VARCHAR(20) NOT NULL,
  `first_name` VARCHAR(50) NOT NULL,
  `middle_name` VARCHAR(50) DEFAULT NULL,
  `last_name` VARCHAR(50) NOT NULL,
  `extension` VARCHAR(10) DEFAULT NULL,
  `birthdate` DATE DEFAULT NULL,
  `gender` ENUM('male','female') DEFAULT NULL,
  `age` INT DEFAULT NULL,
  `username` VARCHAR(50) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` VARCHAR(20) NOT NULL DEFAULT 'user',
  `status` ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `street` VARCHAR(100) DEFAULT NULL,
  `barangay` VARCHAR(100) DEFAULT NULL,
  `city_municipality` VARCHAR(100) DEFAULT NULL,
  `province` VARCHAR(100) DEFAULT NULL,
  `country` VARCHAR(100) DEFAULT NULL,
  `zip_code` VARCHAR(10) DEFAULT NULL,
  `security_answers` JSON DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `reviewed_at` DATETIME DEFAULT NULL,
  `reviewed_by` VARCHAR(100) DEFAULT NULL,
  `rejection_reason` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pr_user_id` (`user_id`),
  UNIQUE KEY `uq_pr_email` (`email`),
  UNIQUE KEY `uq_pr_username` (`username`),
  KEY `idx_pr_status` (`status`),
  KEY `idx_pr_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
