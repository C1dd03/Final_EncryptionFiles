-- Account management / first-login / deterministic Super Admin queue support.
-- The application applies these changes automatically from models/User.php;
-- this file is provided for controlled/manual deployments.

USE `encryption_system`;

ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `session_version` INT NOT NULL DEFAULT 0 AFTER `status`,
  ADD COLUMN IF NOT EXISTS `must_change_password` TINYINT(1) NOT NULL DEFAULT 0 AFTER `session_version`,
  ADD COLUMN IF NOT EXISTS `superadmin_eligible` TINYINT(1) NOT NULL DEFAULT 0 AFTER `must_change_password`,
  ADD COLUMN IF NOT EXISTS `superadmin_queue_at` DATETIME DEFAULT NULL AFTER `superadmin_eligible`,
  ADD COLUMN IF NOT EXISTS `created_by` VARCHAR(20) DEFAULT NULL AFTER `superadmin_queue_at`,
  ADD COLUMN IF NOT EXISTS `contact_number` VARCHAR(20) DEFAULT NULL AFTER `email`,
  ADD COLUMN IF NOT EXISTS `updated_at` TIMESTAMP NULL DEFAULT NULL AFTER `created_at`,
  ADD COLUMN IF NOT EXISTS `last_login` TIMESTAMP NULL DEFAULT NULL AFTER `updated_at`;

CREATE TABLE IF NOT EXISTS `admin_privileges` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `id_number` VARCHAR(20) NOT NULL,
  `privilege_key` VARCHAR(50) NOT NULL,
  `granted_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_admin_priv` (`id_number`, `privilege_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

UPDATE `users`
SET `superadmin_eligible` = 1,
    `superadmin_queue_at` = COALESCE(`superadmin_queue_at`, `created_at`)
WHERE `role` = 'superadmin';
