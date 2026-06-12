-- Create tables for Subscription Management functionality

-- ========================================
-- Table: organizations
-- ========================================
CREATE TABLE `organizations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `industry` varchar(100) DEFAULT NULL,
  `employee_count` int(11) DEFAULT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `contact_email` varchar(255) DEFAULT NULL,
  `contact_phone` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_email` (`email`),
  KEY `idx_is_active` (`is_active`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- Table: subscriptions
-- ========================================
CREATE TABLE `subscriptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `organization_id` int(11) NOT NULL,
  `plan_type` enum('trial','basic','professional','enterprise') DEFAULT 'trial',
  `status` enum('trial','active','expired','cancelled','suspended') DEFAULT 'trial',
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `trial_days` int(11) DEFAULT 14,
  `max_users` int(11) DEFAULT 100,
  `max_modules` int(11) DEFAULT 7,
  `features_enabled` json DEFAULT NULL,
  `price_per_month` decimal(10,2) DEFAULT 0.00,
  `billing_cycle` enum('monthly','quarterly','annual') DEFAULT 'monthly',
  `auto_renew` tinyint(1) DEFAULT 1,
  `payment_method` varchar(100) DEFAULT NULL,
  `last_payment_date` datetime DEFAULT NULL,
  `next_payment_date` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_organization_id` (`organization_id`),
  KEY `idx_status` (`status`),
  KEY `idx_end_date` (`end_date`),
  KEY `idx_plan_type` (`plan_type`),
  CONSTRAINT `fk_subscriptions_organization` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- Table: subscription_features
-- ========================================
CREATE TABLE `subscription_features` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `plan_type` enum('trial','basic','professional','enterprise') NOT NULL,
  `feature_name` varchar(100) NOT NULL,
  `feature_description` text DEFAULT NULL,
  `is_enabled` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_plan_feature` (`plan_type`, `feature_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- Table: subscription_history
-- ========================================
CREATE TABLE `subscription_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `subscription_id` int(11) NOT NULL,
  `action` enum('created','upgraded','downgraded','renewed','expired','cancelled','suspended','reactivated') NOT NULL,
  `old_status` varchar(50) DEFAULT NULL,
  `new_status` varchar(50) DEFAULT NULL,
  `old_plan` varchar(50) DEFAULT NULL,
  `new_plan` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `performed_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_subscription_id` (`subscription_id`),
  KEY `idx_action` (`action`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_subscription_history_subscription` FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_subscription_history_user` FOREIGN KEY (`performed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- Table: expiry_reminders
-- ========================================
CREATE TABLE `expiry_reminders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `subscription_id` int(11) NOT NULL,
  `reminder_type` enum('7_days','3_days','1_day','expired') NOT NULL,
  `sent_at` datetime DEFAULT NULL,
  `email_sent_to` varchar(255) DEFAULT NULL,
  `status` enum('pending','sent','failed') DEFAULT 'pending',
  `retry_count` int(11) DEFAULT 0,
  `last_retry_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_subscription_id` (`subscription_id`),
  KEY `idx_status` (`status`),
  KEY `idx_reminder_type` (`reminder_type`),
  CONSTRAINT `fk_expiry_reminders_subscription` FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- Table: subscription_payments
-- ========================================
CREATE TABLE `subscription_payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `subscription_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) DEFAULT 'USD',
  `payment_date` datetime NOT NULL,
  `payment_method` varchar(100) DEFAULT NULL,
  `transaction_id` varchar(255) DEFAULT NULL,
  `status` enum('pending','completed','failed','refunded') DEFAULT 'pending',
  `invoice_number` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_subscription_id` (`subscription_id`),
  KEY `idx_status` (`status`),
  KEY `idx_payment_date` (`payment_date`),
  UNIQUE KEY `unique_transaction_id` (`transaction_id`),
  CONSTRAINT `fk_subscription_payments_subscription` FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- Add organization_id to users table (if not exists)
-- ========================================
ALTER TABLE `users` ADD COLUMN `organization_id` int(11) DEFAULT NULL AFTER `department_id`;
ALTER TABLE `users` ADD CONSTRAINT `fk_users_organization` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE SET NULL;

-- ========================================
-- Insert default subscription features
-- ========================================
INSERT INTO `subscription_features` (`plan_type`, `feature_name`, `feature_description`, `is_enabled`) VALUES
('trial', 'basic_training', 'Access to basic training modules', 1),
('trial', 'limited_users', 'Up to 10 users', 1),
('trial', 'phishing_simulation', 'Basic phishing simulation', 1),
('trial', 'reports', 'Basic reporting', 1),

('basic', 'basic_training', 'Access to all training modules', 1),
('basic', 'up_to_50_users', 'Up to 50 users', 1),
('basic', 'phishing_simulation', 'Advanced phishing simulation', 1),
('basic', 'reports', 'Detailed reporting', 1),
('basic', 'email_notifications', 'Email notifications', 1),

('professional', 'advanced_training', 'Advanced training modules', 1),
('professional', 'up_to_200_users', 'Up to 200 users', 1),
('professional', 'phishing_simulation', 'Advanced phishing simulation', 1),
('professional', 'reports', 'Advanced reporting and analytics', 1),
('professional', 'email_notifications', 'Email notifications', 1),
('professional', 'api_access', 'API access', 1),
('professional', 'custom_branding', 'Custom branding', 1),
('professional', 'priority_support', 'Priority support', 1),

('enterprise', 'advanced_training', 'Advanced training modules', 1),
('enterprise', 'unlimited_users', 'Unlimited users', 1),
('enterprise', 'phishing_simulation', 'Advanced phishing simulation', 1),
('enterprise', 'reports', 'Advanced reporting and analytics', 1),
('enterprise', 'email_notifications', 'Email notifications', 1),
('enterprise', 'api_access', 'API access', 1),
('enterprise', 'custom_branding', 'Custom branding', 1),
('enterprise', 'priority_support', '24/7 Priority support', 1),
('enterprise', 'sso', 'Single Sign-On (SSO)', 1),
('enterprise', 'advanced_security', 'Advanced security features', 1),
('enterprise', 'dedicated_account_manager', 'Dedicated account manager', 1);
