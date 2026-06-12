-- Create tables for Incident Reporting functionality

-- ========================================
-- Table: incident_reports
-- ========================================
CREATE TABLE `incident_reports` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `incident_type` enum('phishing','malware','suspicious_link','credential_theft','social_engineering','other') NOT NULL,
  `subject` varchar(255) NOT NULL,
  `description` mediumtext NOT NULL,
  `status` enum('new','under_review','resolved','false_positive') DEFAULT 'new',
  `severity` enum('low','medium','high','critical') DEFAULT 'medium',
  `attachment_path` varchar(255) DEFAULT NULL,
  `attachment_filename` varchar(255) DEFAULT NULL,
  `attachment_size` int(11) DEFAULT NULL,
  `attachment_mime_type` varchar(100) DEFAULT NULL,
  `email_content` mediumtext DEFAULT NULL,
  `email_sender` varchar(255) DEFAULT NULL,
  `email_subject` varchar(255) DEFAULT NULL,
  `reported_at` datetime DEFAULT current_timestamp(),
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `resolution_notes` mediumtext DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- Table: incident_report_comments
-- ========================================
CREATE TABLE `incident_report_comments` (
  `id` int(11) NOT NULL,
  `incident_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comment_text` mediumtext NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- Table: incident_report_attachments
-- ========================================
CREATE TABLE `incident_report_attachments` (
  `id` int(11) NOT NULL,
  `incident_id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_size` int(11) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `uploaded_by` int(11) NOT NULL,
  `uploaded_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- Add Primary Keys
-- ========================================
ALTER TABLE `incident_reports` ADD PRIMARY KEY (`id`);
ALTER TABLE `incident_report_comments` ADD PRIMARY KEY (`id`);
ALTER TABLE `incident_report_attachments` ADD PRIMARY KEY (`id`);

-- ========================================
-- Add Auto Increment
-- ========================================
ALTER TABLE `incident_reports` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `incident_report_comments` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `incident_report_attachments` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- ========================================
-- Add Foreign Keys
-- ========================================
ALTER TABLE `incident_reports` ADD CONSTRAINT `fk_incident_reports_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
ALTER TABLE `incident_reports` ADD CONSTRAINT `fk_incident_reports_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
ALTER TABLE `incident_report_comments` ADD CONSTRAINT `fk_incident_comments_incident` FOREIGN KEY (`incident_id`) REFERENCES `incident_reports` (`id`) ON DELETE CASCADE;
ALTER TABLE `incident_report_comments` ADD CONSTRAINT `fk_incident_comments_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
ALTER TABLE `incident_report_attachments` ADD CONSTRAINT `fk_incident_attachments_incident` FOREIGN KEY (`incident_id`) REFERENCES `incident_reports` (`id`) ON DELETE CASCADE;
ALTER TABLE `incident_report_attachments` ADD CONSTRAINT `fk_incident_attachments_user` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

-- ========================================
-- Add Indexes for Performance
-- ========================================
ALTER TABLE `incident_reports` ADD INDEX `idx_user_id` (`user_id`);
ALTER TABLE `incident_reports` ADD INDEX `idx_status` (`status`);
ALTER TABLE `incident_reports` ADD INDEX `idx_severity` (`severity`);
ALTER TABLE `incident_reports` ADD INDEX `idx_reported_at` (`reported_at`);
ALTER TABLE `incident_reports` ADD INDEX `idx_incident_type` (`incident_type`);
ALTER TABLE `incident_report_comments` ADD INDEX `idx_incident_id` (`incident_id`);
ALTER TABLE `incident_report_attachments` ADD INDEX `idx_incident_id` (`incident_id`);
