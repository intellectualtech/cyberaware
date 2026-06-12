-- Add Multi-Factor Authentication (MFA) columns to users table
-- Supports TOTP (Time-based One-Time Password) via authenticator apps

ALTER TABLE `users` ADD COLUMN `mfa_enabled` TINYINT(1) DEFAULT 0 AFTER `is_active`;
ALTER TABLE `users` ADD COLUMN `mfa_secret` VARCHAR(255) DEFAULT NULL AFTER `mfa_enabled`;
ALTER TABLE `users` ADD COLUMN `mfa_backup_codes` TEXT DEFAULT NULL AFTER `mfa_secret`;
ALTER TABLE `users` ADD COLUMN `mfa_enabled_at` DATETIME DEFAULT NULL AFTER `mfa_backup_codes`;

-- Create index for faster lookups
CREATE INDEX `idx_mfa_enabled` ON `users` (`mfa_enabled`);

-- Create table for MFA login attempts (for rate limiting)
CREATE TABLE `mfa_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `attempt_type` enum('success','failure') DEFAULT 'failure',
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` TEXT DEFAULT NULL,
  `attempted_at` DATETIME DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  INDEX `idx_user_attempts` (`user_id`, `attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
