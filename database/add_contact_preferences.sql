-- Add contact preference columns to contact_requests table
-- Allows users to specify how they want to be contacted (email or phone)

ALTER TABLE `contact_requests` ADD COLUMN `phone` VARCHAR(20) DEFAULT NULL AFTER `message`;
ALTER TABLE `contact_requests` ADD COLUMN `contact_preference` ENUM('email', 'phone') DEFAULT 'email' AFTER `phone`;

-- Create index for faster lookups
CREATE INDEX `idx_contact_preference` ON `contact_requests` (`contact_preference`);
