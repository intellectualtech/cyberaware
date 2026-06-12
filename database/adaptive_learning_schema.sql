-- ========================================
-- Adaptive Learning Feature - Database Schema
-- ========================================
-- This script creates the necessary tables and extends existing tables
-- for the Adaptive Learning feature in CyberApp

-- ========================================
-- Table 1: adaptive_learning_recommendations
-- ========================================
-- Stores personalized training recommendations for trainees
CREATE TABLE IF NOT EXISTS `adaptive_learning_recommendations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `reason` varchar(255) NOT NULL,
  `difficulty_level` enum('beginner','intermediate','advanced') DEFAULT 'beginner',
  `priority_score` decimal(5,2) DEFAULT 0.00,
  `created_at` datetime DEFAULT current_timestamp(),
  `accepted_at` datetime DEFAULT NULL,
  `declined_at` datetime DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `status` enum('pending','accepted','declined','expired') DEFAULT 'pending',
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`module_id`) REFERENCES `training_modules` (`id`) ON DELETE CASCADE,
  KEY `idx_user_status` (`user_id`, `status`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- Table 2: performance_analysis_cache
-- ========================================
-- Caches Category_Average calculations for performance optimization
CREATE TABLE IF NOT EXISTS `performance_analysis_cache` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `category` varchar(50) NOT NULL,
  `category_average` decimal(5,2) DEFAULT 0.00,
  `weighted_average` decimal(5,2) DEFAULT 0.00,
  `session_count` int(11) DEFAULT 0,
  `recent_session_count` int(11) DEFAULT 0,
  `is_weak` tinyint(1) DEFAULT 0,
  `is_mastered` tinyint(1) DEFAULT 0,
  `difficulty_tier` enum('beginner','intermediate','advanced') DEFAULT 'beginner',
  `last_updated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `cache_expires_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_category` (`user_id`, `category`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  KEY `idx_is_weak` (`is_weak`),
  KEY `idx_is_mastered` (`is_mastered`),
  KEY `idx_cache_expires` (`cache_expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- Table 3: remediation_tracking
-- ========================================
-- Tracks remediation attempts and progress for struggling learners
CREATE TABLE IF NOT EXISTS `remediation_tracking` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `category` varchar(50) NOT NULL,
  `remediation_level` int(11) DEFAULT 1,
  `consecutive_failures` int(11) DEFAULT 0,
  `last_failure_date` datetime DEFAULT NULL,
  `remediation_module_id` int(11) DEFAULT NULL,
  `remediation_score` int(11) DEFAULT NULL,
  `remediation_completed_at` datetime DEFAULT NULL,
  `status` enum('active','resolved','escalated') DEFAULT 'active',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_category` (`user_id`, `category`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`remediation_module_id`) REFERENCES `training_modules` (`id`) ON DELETE SET NULL,
  KEY `idx_status` (`status`),
  KEY `idx_user_category` (`user_id`, `category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- Extend Table: training_sessions
-- ========================================
-- Add columns to track adaptive learning context
ALTER TABLE `training_sessions` ADD COLUMN IF NOT EXISTS `module_category` varchar(50) DEFAULT NULL;
ALTER TABLE `training_sessions` ADD COLUMN IF NOT EXISTS `difficulty_level` enum('beginner','intermediate','advanced') DEFAULT 'beginner';
ALTER TABLE `training_sessions` ADD COLUMN IF NOT EXISTS `is_remediation` tinyint(1) DEFAULT 0;
ALTER TABLE `training_sessions` ADD COLUMN IF NOT EXISTS `recommendation_id` int(11) DEFAULT NULL;

-- Add foreign key for recommendation_id if not exists
ALTER TABLE `training_sessions` ADD CONSTRAINT `fk_training_sessions_recommendation` 
  FOREIGN KEY (`recommendation_id`) REFERENCES `adaptive_learning_recommendations` (`id`) ON DELETE SET NULL;

-- Add indexes for performance
ALTER TABLE `training_sessions` ADD KEY IF NOT EXISTS `idx_user_category_date` (`user_id`, `module_category`, `completed_at`);
ALTER TABLE `training_sessions` ADD KEY IF NOT EXISTS `idx_module_category` (`module_category`);

-- ========================================
-- Extend Table: user_module_progress
-- ========================================
-- Add columns for adaptive learning tracking
ALTER TABLE `user_module_progress` ADD COLUMN IF NOT EXISTS `recommended_at` datetime DEFAULT NULL;
ALTER TABLE `user_module_progress` ADD COLUMN IF NOT EXISTS `recommendation_reason` varchar(255) DEFAULT NULL;
ALTER TABLE `user_module_progress` ADD COLUMN IF NOT EXISTS `difficulty_tier` enum('beginner','intermediate','advanced') DEFAULT 'beginner';
ALTER TABLE `user_module_progress` ADD COLUMN IF NOT EXISTS `is_mastered` tinyint(1) DEFAULT 0;

-- Add indexes for performance
ALTER TABLE `user_module_progress` ADD KEY IF NOT EXISTS `idx_is_mastered` (`is_mastered`);

-- ========================================
-- Create View: user_performance_summary
-- ========================================
-- Provides quick access to user performance summaries
CREATE OR REPLACE VIEW `user_performance_summary` AS
SELECT 
    u.id as user_id,
    u.username,
    u.full_name,
    COUNT(DISTINCT ts.id) as total_sessions,
    COUNT(DISTINCT ts.module_id) as modules_completed,
    AVG(ts.final_score) as average_score,
    MAX(ts.completed_at) as last_training_date,
    CASE 
        WHEN AVG(ts.final_score) < 60 THEN 'high_risk'
        WHEN AVG(ts.final_score) < 70 THEN 'medium_risk'
        WHEN AVG(ts.final_score) < 80 THEN 'low_risk'
        ELSE 'compliant'
    END as risk_level
FROM users u
LEFT JOIN training_sessions ts ON u.id = ts.user_id AND ts.completed_at IS NOT NULL
WHERE u.role = 'trainee'
GROUP BY u.id, u.username, u.full_name;

-- ========================================
-- Create View: category_performance_summary
-- ========================================
-- Provides category-level performance summaries
CREATE OR REPLACE VIEW `category_performance_summary` AS
SELECT 
    ts.user_id,
    ts.module_category as category,
    COUNT(ts.id) as session_count,
    AVG(ts.final_score) as category_average,
    MIN(ts.final_score) as min_score,
    MAX(ts.final_score) as max_score,
    MAX(ts.completed_at) as last_session_date,
    CASE 
        WHEN COUNT(ts.id) < 2 THEN 'insufficient_data'
        WHEN AVG(ts.final_score) < 70 THEN 'weak'
        WHEN AVG(ts.final_score) >= 80 THEN 'mastered'
        ELSE 'proficient'
    END as performance_level
FROM training_sessions ts
WHERE ts.completed_at IS NOT NULL AND ts.module_category IS NOT NULL
GROUP BY ts.user_id, ts.module_category;

-- ========================================
-- Verification Queries
-- ========================================
-- Run these to verify the schema was created correctly:
-- SELECT * FROM adaptive_learning_recommendations LIMIT 1;
-- SELECT * FROM performance_analysis_cache LIMIT 1;
-- SELECT * FROM remediation_tracking LIMIT 1;
-- SELECT * FROM user_performance_summary LIMIT 1;
-- SELECT * FROM category_performance_summary LIMIT 1;
