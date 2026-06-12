-- Create missing tables for CyberAware

-- ========================================
-- Table: user_training_summary
-- ========================================
CREATE TABLE `user_training_summary` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total_modules_completed` int(11) DEFAULT 0,
  `total_score` int(11) DEFAULT 0,
  `average_score` decimal(5,2) DEFAULT 0.00,
  `risk_score` int(11) DEFAULT 0,
  `last_training_date` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- ========================================
-- Add Primary Key
-- ========================================
ALTER TABLE `user_training_summary` ADD PRIMARY KEY (`id`);
ALTER TABLE `user_training_summary` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- ========================================
-- Add Foreign Key
-- ========================================
ALTER TABLE `user_training_summary` ADD CONSTRAINT `fk_user_training_summary_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

-- ========================================
-- Optional: Create view for easier querying
-- ========================================
CREATE OR REPLACE VIEW user_training_summary_view AS
SELECT 
    u.id,
    u.username,
    u.full_name,
    u.email,
    COUNT(DISTINCT ts.id) as total_sessions,
    COUNT(DISTINCT ts.module_id) as modules_completed,
    AVG(ts.final_score) as average_score,
    MAX(ts.completed_at) as last_training_date,
    CASE 
        WHEN AVG(ts.final_score) < 60 THEN 80
        WHEN AVG(ts.final_score) < 70 THEN 60
        WHEN AVG(ts.final_score) < 80 THEN 40
        ELSE 20
    END as risk_score
FROM users u
LEFT JOIN training_sessions ts ON u.id = ts.user_id AND ts.completed_at IS NOT NULL
WHERE u.role = 'trainee'
GROUP BY u.id, u.username, u.full_name, u.email;
