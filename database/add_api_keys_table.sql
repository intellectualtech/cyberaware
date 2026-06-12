-- Create API Keys table for managing API access
-- This replaces hardcoded API keys with database-managed keys

CREATE TABLE IF NOT EXISTS `api_keys` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key_hash` varchar(255) NOT NULL UNIQUE,
  `key_name` varchar(100) NOT NULL,
  `description` text,
  `user_id` int(11),
  `permissions` json DEFAULT '["read"]',
  `rate_limit` int(11) DEFAULT 100,
  `rate_limit_window` int(11) DEFAULT 3600,
  `is_active` tinyint(1) DEFAULT 1,
  `last_used_at` datetime,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `expires_at` datetime,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key_hash` (`key_hash`),
  KEY `user_id` (`user_id`),
  KEY `is_active` (`is_active`),
  CONSTRAINT `fk_api_keys_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create API Key Usage Log table for tracking API usage
CREATE TABLE IF NOT EXISTS `api_key_usage_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `api_key_id` int(11) NOT NULL,
  `endpoint` varchar(255) NOT NULL,
  `method` varchar(10) NOT NULL,
  `status_code` int(11),
  `response_time_ms` int(11),
  `ip_address` varchar(45),
  `user_agent` varchar(255),
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `api_key_id` (`api_key_id`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `fk_api_key_usage_log` FOREIGN KEY (`api_key_id`) REFERENCES `api_keys` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create JWT Tokens table for managing user sessions
CREATE TABLE IF NOT EXISTS `jwt_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token_hash` varchar(255) NOT NULL UNIQUE,
  `token_family` varchar(255),
  `ip_address` varchar(45),
  `user_agent` varchar(255),
  `is_revoked` tinyint(1) DEFAULT 0,
  `issued_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `expires_at` datetime NOT NULL,
  `refreshed_at` datetime,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token_hash` (`token_hash`),
  KEY `user_id` (`user_id`),
  KEY `expires_at` (`expires_at`),
  KEY `is_revoked` (`is_revoked`),
  CONSTRAINT `fk_jwt_tokens_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default API keys (these should be regenerated in production)
INSERT INTO `api_keys` (`key_hash`, `key_name`, `description`, `permissions`, `rate_limit`, `is_active`) VALUES
(
  SHA2('demo_key_12345', 256),
  'Demo Key',
  'Read-only demo key for testing',
  '["read"]',
  100,
  1
),
(
  SHA2('admin_key_67890', 256),
  'Admin Key',
  'Full access admin key',
  '["read", "write", "delete"]',
  1000,
  1
);

-- Create index for faster lookups
CREATE INDEX idx_api_keys_active ON `api_keys`(`is_active`, `expires_at`);
CREATE INDEX idx_jwt_tokens_user_active ON `jwt_tokens`(`user_id`, `is_revoked`, `expires_at`);
