-- Create module registration and progress tracking tables

-- ========================================
-- Table: user_module_registrations
-- ========================================
CREATE TABLE `user_module_registrations` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `registration_order` int(11) NOT NULL,
  `status` enum('pending','in_progress','passed','failed') DEFAULT 'pending',
  `registered_at` datetime DEFAULT current_timestamp(),
  `started_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- ========================================
-- Table: user_module_progress
-- ========================================
CREATE TABLE `user_module_progress` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `total_attempts` int(11) DEFAULT 0,
  `best_score` int(11) DEFAULT 0,
  `average_score` decimal(5,2) DEFAULT 0.00,
  `last_attempt_date` datetime DEFAULT NULL,
  `passed` tinyint(1) DEFAULT 0,
  `passed_date` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- ========================================
-- Add Primary Keys
-- ========================================
ALTER TABLE `user_module_registrations` ADD PRIMARY KEY (`id`);
ALTER TABLE `user_module_progress` ADD PRIMARY KEY (`id`);

-- ========================================
-- Add Unique Keys
-- ========================================
ALTER TABLE `user_module_registrations` ADD UNIQUE KEY `unique_registration` (`user_id`, `module_id`);
ALTER TABLE `user_module_progress` ADD UNIQUE KEY `unique_progress` (`user_id`, `module_id`);

-- ========================================
-- Add Auto Increment
-- ========================================
ALTER TABLE `user_module_registrations` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `user_module_progress` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- ========================================
-- Add Foreign Keys
-- ========================================
ALTER TABLE `user_module_registrations` ADD CONSTRAINT `fk_user_module_registrations_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
ALTER TABLE `user_module_registrations` ADD CONSTRAINT `fk_user_module_registrations_module` FOREIGN KEY (`module_id`) REFERENCES `training_modules` (`id`) ON DELETE CASCADE;
ALTER TABLE `user_module_progress` ADD CONSTRAINT `fk_user_module_progress_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
ALTER TABLE `user_module_progress` ADD CONSTRAINT `fk_user_module_progress_module` FOREIGN KEY (`module_id`) REFERENCES `training_modules` (`id`) ON DELETE CASCADE;
