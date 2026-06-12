-- Add password reset columns to users table
-- This allows users to reset their passwords via email

ALTER TABLE `users` ADD COLUMN `password_reset_token` VARCHAR(255) DEFAULT NULL AFTER `password_hash`;
ALTER TABLE `users` ADD COLUMN `password_reset_expires` DATETIME DEFAULT NULL AFTER `password_reset_token`;

-- Create index for faster token lookups
CREATE INDEX `idx_password_reset_token` ON `users` (`password_reset_token`);
