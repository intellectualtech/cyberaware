<?php
/**
 * Setup script for Incident Reporting tables
 * Run this once to create the necessary database tables
 */

require_once '../config/database.php';

try {
    $pdo = getDBConnection();
    
    echo "<h2>Setting up Incident Reporting Tables...</h2>";
    
    // Drop existing tables if they exist (to start fresh)
    $drop_statements = [
        "DROP TABLE IF EXISTS `incident_report_attachments`",
        "DROP TABLE IF EXISTS `incident_report_comments`",
        "DROP TABLE IF EXISTS `incident_reports`"
    ];
    
    foreach ($drop_statements as $stmt) {
        try {
            $pdo->exec($stmt);
            echo "✓ Dropped existing table<br>";
        } catch (Exception $e) {
            echo "⚠ " . $e->getMessage() . "<br>";
        }
    }
    
    echo "<hr>";
    echo "<h3>Creating new tables...</h3>";
    
    // Create incident_reports table
    $pdo->exec("
        CREATE TABLE `incident_reports` (
          `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
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
          `reported_at` datetime DEFAULT CURRENT_TIMESTAMP,
          `reviewed_by` int(11) DEFAULT NULL,
          `reviewed_at` datetime DEFAULT NULL,
          `resolution_notes` mediumtext DEFAULT NULL,
          `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
          `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          KEY `idx_user_id` (`user_id`),
          KEY `idx_status` (`status`),
          KEY `idx_severity` (`severity`),
          KEY `idx_reported_at` (`reported_at`),
          KEY `idx_incident_type` (`incident_type`),
          CONSTRAINT `fk_incident_reports_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
          CONSTRAINT `fk_incident_reports_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Created incident_reports table<br>";
    
    // Create incident_report_comments table
    $pdo->exec("
        CREATE TABLE `incident_report_comments` (
          `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
          `incident_id` int(11) NOT NULL,
          `user_id` int(11) NOT NULL,
          `comment_text` mediumtext NOT NULL,
          `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
          `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          KEY `idx_incident_id` (`incident_id`),
          CONSTRAINT `fk_incident_comments_incident` FOREIGN KEY (`incident_id`) REFERENCES `incident_reports` (`id`) ON DELETE CASCADE,
          CONSTRAINT `fk_incident_comments_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Created incident_report_comments table<br>";
    
    // Create incident_report_attachments table
    $pdo->exec("
        CREATE TABLE `incident_report_attachments` (
          `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
          `incident_id` int(11) NOT NULL,
          `filename` varchar(255) NOT NULL,
          `file_path` varchar(255) NOT NULL,
          `file_size` int(11) NOT NULL,
          `mime_type` varchar(100) NOT NULL,
          `uploaded_by` int(11) NOT NULL,
          `uploaded_at` datetime DEFAULT CURRENT_TIMESTAMP,
          KEY `idx_incident_id` (`incident_id`),
          CONSTRAINT `fk_incident_attachments_incident` FOREIGN KEY (`incident_id`) REFERENCES `incident_reports` (`id`) ON DELETE CASCADE,
          CONSTRAINT `fk_incident_attachments_user` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Created incident_report_attachments table<br>";
    
    echo "<hr>";
    echo "<h2 style='color: green;'>✓ Database setup completed successfully!</h2>";
    echo "<p>All incident reporting tables have been created and are ready to use.</p>";
    echo "<p><a href='../trainee/report-incident.php' style='padding: 10px 20px; background: #FF8C42; color: white; text-decoration: none; border-radius: 5px; display: inline-block;'>Go to Incident Report Form</a></p>";
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>✗ Setup failed</h2>";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "<p>Make sure your database connection is working and the 'users' table exists.</p>";
}
?>
