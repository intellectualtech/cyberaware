-- Create tables for Phish Inbox functionality

-- ========================================
-- Table: inbox_messages
-- ========================================
CREATE TABLE `inbox_messages` (
  `id` int(11) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `sender_name` varchar(100) DEFAULT NULL,
  `sender_email` varchar(150) DEFAULT NULL,
  `body_html` mediumtext NOT NULL,
  `has_attachment` tinyint(1) DEFAULT 0,
  `attachment_name` varchar(150) DEFAULT NULL,
  `is_phish` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- ========================================
-- Table: inbox_assignments
-- ========================================
CREATE TABLE `inbox_assignments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message_id` int(11) NOT NULL,
  `week_start` date NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- ========================================
-- Table: inbox_actions
-- ========================================
CREATE TABLE `inbox_actions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message_id` int(11) NOT NULL,
  `week_start` date NOT NULL,
  `action` enum('safe','phish') NOT NULL,
  `is_correct` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- ========================================
-- Add Primary Keys
-- ========================================
ALTER TABLE `inbox_messages` ADD PRIMARY KEY (`id`);
ALTER TABLE `inbox_assignments` ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `unique_assignment` (`user_id`, `message_id`, `week_start`);
ALTER TABLE `inbox_actions` ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `unique_action` (`user_id`, `message_id`, `week_start`);

-- ========================================
-- Add Auto Increment
-- ========================================
ALTER TABLE `inbox_messages` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `inbox_assignments` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `inbox_actions` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- ========================================
-- Add Foreign Keys
-- ========================================
ALTER TABLE `inbox_assignments` ADD CONSTRAINT `fk_inbox_assignments_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
ALTER TABLE `inbox_assignments` ADD CONSTRAINT `fk_inbox_assignments_message` FOREIGN KEY (`message_id`) REFERENCES `inbox_messages` (`id`) ON DELETE CASCADE;
ALTER TABLE `inbox_actions` ADD CONSTRAINT `fk_inbox_actions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
ALTER TABLE `inbox_actions` ADD CONSTRAINT `fk_inbox_actions_message` FOREIGN KEY (`message_id`) REFERENCES `inbox_messages` (`id`) ON DELETE CASCADE;

-- ========================================
-- Insert Sample Inbox Messages
-- ========================================
INSERT INTO `inbox_messages` (`subject`, `sender_name`, `sender_email`, `body_html`, `has_attachment`, `attachment_name`, `is_phish`, `is_active`) VALUES
('Urgent: Verify Your Account', 'Security Team', 'security@company-verify.com', '<p>Dear User,</p><p>We detected suspicious activity on your account. Please verify your identity immediately by clicking the link below.</p><p><a href="#">Verify Account</a></p>', 0, NULL, 1, 1),

('Your Invoice #12345', 'Accounting', 'accounting@company.com', '<p>Hi,</p><p>Please find attached your invoice for services rendered. Payment is due within 30 days.</p>', 1, 'Invoice_12345.pdf', 0, 1),

('Password Reset Required', 'IT Support', 'itsupport@company.com', '<p>Hello,</p><p>As part of our security policy, all employees must reset their passwords. Click here to reset: <a href="#">Reset Password</a></p>', 0, NULL, 1, 1),

('Meeting Reminder - Q1 Planning', 'HR Department', 'hr@company.com', '<p>Hi Team,</p><p>Just a reminder about our Q1 planning meeting tomorrow at 2 PM in Conference Room B.</p>', 0, NULL, 0, 1),

('Confirm Your Delivery', 'Shipping Services', 'notify@shipping.com', '<p>We attempted to deliver your package. Please confirm your delivery address by clicking the link below.</p><p><a href="#">Confirm Delivery</a></p>', 0, NULL, 1, 1),

('Team Lunch This Friday', 'Office Manager', 'office@company.com', '<p>Hi Everyone,</p><p>We are having a team lunch this Friday at noon. Please RSVP by Thursday.</p>', 0, NULL, 0, 1),

('Update Your Payment Method', 'Billing', 'billing@company-secure.com', '<p>Your payment method is expiring soon. Please update it here: <a href="#">Update Payment</a></p>', 0, NULL, 1, 1),

('Project Status Update', 'Project Manager', 'pm@company.com', '<p>Hi Team,</p><p>Here is the latest status update on our current projects. Please review and provide feedback.</p>', 1, 'Project_Status.docx', 0, 1);
