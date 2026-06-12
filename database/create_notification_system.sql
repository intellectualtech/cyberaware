-- Notification System Tables

-- Notification Templates
CREATE TABLE IF NOT EXISTS notification_templates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    subject VARCHAR(255) NOT NULL,
    body_html LONGTEXT NOT NULL,
    body_text LONGTEXT,
    type ENUM('training_assigned', 'campaign_reminder', 'deadline_approaching', 'incident_update', 'certificate_completion') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Notification History
CREATE TABLE IF NOT EXISTS notification_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    template_id INT NOT NULL,
    recipient_email VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    body_html LONGTEXT,
    body_text LONGTEXT,
    type ENUM('training_assigned', 'campaign_reminder', 'deadline_approaching', 'incident_update', 'certificate_completion') NOT NULL,
    status ENUM('pending', 'sent', 'failed', 'bounced') DEFAULT 'pending',
    sent_at TIMESTAMP NULL,
    error_message TEXT,
    related_entity_type VARCHAR(50),
    related_entity_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (template_id) REFERENCES notification_templates(id),
    INDEX idx_user_status (user_id, status),
    INDEX idx_type_created (type, created_at),
    INDEX idx_sent_at (sent_at)
);

-- User Notification Preferences
CREATE TABLE IF NOT EXISTS user_notification_preferences (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL UNIQUE,
    training_assigned BOOLEAN DEFAULT 1,
    campaign_reminder BOOLEAN DEFAULT 1,
    deadline_approaching BOOLEAN DEFAULT 1,
    incident_update BOOLEAN DEFAULT 1,
    certificate_completion BOOLEAN DEFAULT 1,
    email_frequency ENUM('immediate', 'daily_digest', 'weekly_digest') DEFAULT 'immediate',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Notification Queue (for batch processing)
CREATE TABLE IF NOT EXISTS notification_queue (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    template_id INT NOT NULL,
    template_data JSON,
    priority ENUM('low', 'normal', 'high') DEFAULT 'normal',
    scheduled_for TIMESTAMP NULL,
    attempts INT DEFAULT 0,
    max_attempts INT DEFAULT 3,
    status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (template_id) REFERENCES notification_templates(id),
    INDEX idx_status_scheduled (status, scheduled_for),
    INDEX idx_priority (priority)
);

-- Insert default notification templates
INSERT INTO notification_templates (name, subject, body_html, body_text, type) VALUES
(
    'training_assigned',
    'New Training Module Assigned: {module_title}',
    '<h2>New Training Assigned</h2><p>Hello {user_name},</p><p>A new training module has been assigned to you:</p><p><strong>{module_title}</strong></p><p>Category: {module_category}</p><p>Estimated Duration: {module_duration}</p><p><a href="{dashboard_url}">View in Dashboard</a></p><p>Best regards,<br>CyberAware Team</p>',
    'New Training Assigned\n\nHello {user_name},\n\nA new training module has been assigned to you:\n\n{module_title}\nCategory: {module_category}\nEstimated Duration: {module_duration}\n\nView in Dashboard: {dashboard_url}\n\nBest regards,\nCyberAware Team',
    'training_assigned'
),
(
    'campaign_reminder',
    'Campaign Reminder: {campaign_name}',
    '<h2>Campaign Reminder</h2><p>Hello {user_name},</p><p>This is a reminder about the ongoing campaign:</p><p><strong>{campaign_name}</strong></p><p>Description: {campaign_description}</p><p>Progress: {campaign_progress}%</p><p><a href="{campaign_url}">View Campaign</a></p><p>Best regards,<br>CyberAware Team</p>',
    'Campaign Reminder\n\nHello {user_name},\n\nThis is a reminder about the ongoing campaign:\n\n{campaign_name}\nDescription: {campaign_description}\nProgress: {campaign_progress}%\n\nView Campaign: {campaign_url}\n\nBest regards,\nCyberAware Team',
    'campaign_reminder'
),
(
    'deadline_approaching',
    'Deadline Approaching: {item_name}',
    '<h2>Deadline Approaching</h2><p>Hello {user_name},</p><p>A deadline is approaching:</p><p><strong>{item_name}</strong></p><p>Deadline: {deadline_date}</p><p>Days Remaining: {days_remaining}</p><p><a href="{item_url}">View Details</a></p><p>Best regards,<br>CyberAware Team</p>',
    'Deadline Approaching\n\nHello {user_name},\n\nA deadline is approaching:\n\n{item_name}\nDeadline: {deadline_date}\nDays Remaining: {days_remaining}\n\nView Details: {item_url}\n\nBest regards,\nCyberAware Team',
    'deadline_approaching'
),
(
    'incident_update',
    'Incident Status Update: {incident_title}',
    '<h2>Incident Status Update</h2><p>Hello {user_name},</p><p>There is an update on the incident:</p><p><strong>{incident_title}</strong></p><p>Status: {incident_status}</p><p>Description: {incident_description}</p><p><a href="{incident_url}">View Incident</a></p><p>Best regards,<br>CyberAware Team</p>',
    'Incident Status Update\n\nHello {user_name},\n\nThere is an update on the incident:\n\n{incident_title}\nStatus: {incident_status}\nDescription: {incident_description}\n\nView Incident: {incident_url}\n\nBest regards,\nCyberAware Team',
    'incident_update'
),
(
    'certificate_completion',
    'Certificate Awarded: {module_title}',
    '<h2>Certificate Awarded</h2><p>Hello {user_name},</p><p>Congratulations! You have successfully completed the training module and earned a certificate:</p><p><strong>{module_title}</strong></p><p>Score: {final_score}%</p><p>Completion Date: {completion_date}</p><p><a href="{certificate_url}">Download Certificate</a></p><p>Best regards,<br>CyberAware Team</p>',
    'Certificate Awarded\n\nHello {user_name},\n\nCongratulations! You have successfully completed the training module and earned a certificate:\n\n{module_title}\nScore: {final_score}%\nCompletion Date: {completion_date}\n\nDownload Certificate: {certificate_url}\n\nBest regards,\nCyberAware Team',
    'certificate_completion'
);
