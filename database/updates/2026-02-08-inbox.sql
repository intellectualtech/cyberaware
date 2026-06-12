-- Phish Inbox Lite tables and seed data (2026-02-08)

CREATE TABLE IF NOT EXISTS `inbox_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `subject` varchar(200) NOT NULL,
  `sender_name` varchar(120) DEFAULT NULL,
  `sender_email` varchar(150) DEFAULT NULL,
  `body_html` mediumtext NOT NULL,
  `is_phish` tinyint(1) DEFAULT 1,
  `difficulty` tinyint(3) UNSIGNED DEFAULT 3,
  `has_attachment` tinyint(1) DEFAULT 0,
  `attachment_name` varchar(150) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

CREATE TABLE IF NOT EXISTS `inbox_assignments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `message_id` int(11) NOT NULL,
  `week_start` date NOT NULL,
  `assigned_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_inbox_assignment` (`user_id`,`message_id`,`week_start`),
  KEY `idx_inbox_assignment_week` (`week_start`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

CREATE TABLE IF NOT EXISTS `inbox_actions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `message_id` int(11) NOT NULL,
  `week_start` date NOT NULL,
  `action` enum('safe','phish') NOT NULL,
  `is_correct` tinyint(1) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_inbox_action` (`user_id`,`message_id`,`week_start`),
  KEY `idx_inbox_action_week` (`week_start`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Seed inbox messages (mix of safe and phish)
INSERT INTO `inbox_messages` (`subject`, `sender_name`, `sender_email`, `body_html`, `is_phish`, `difficulty`, `has_attachment`, `attachment_name`) VALUES
('Payroll Update: Direct Deposit Info', 'HR Team', 'hr@company.com', '<p>Hi there,</p><p>We are confirming your direct deposit details for the next payroll cycle. If you recently changed banks, reply to this message through the HR portal.</p><p>Thanks,<br>HR</p>', 0, 2, 0, NULL),
('Team Lunch RSVP', 'Office Coordinator', 'office@company.com', '<p>Quick reminder: team lunch this Friday at 12:30. RSVP in the calendar invite.</p>', 0, 1, 0, NULL),
('Invoice Attached: Q1 Services', 'Accounts Payable', 'ap@vendor-billing.com', '<p>Please see the attached invoice for Q1 services. Let us know if you need a PO reference.</p>', 0, 2, 1, 'Invoice_Q1.pdf'),
('Security Alert: Password Reset Required', 'Security Team', 'security-alert@company-verify.com', '<p><strong>Action required:</strong> your password expires today.</p><p>Reset now: <a href="#">https://company-verify.com/reset</a></p>', 1, 4, 0, NULL),
('Shared Document: Updated Benefits Guide', 'Benefits Desk', 'benefits@company.com', '<p>We updated the benefits guide for 2026. You can find it in the HR portal.</p>', 0, 1, 0, NULL),
('Delivery Exception: Confirm Address', 'Shipping Support', 'support@ship-track-service.com', '<p>Your package could not be delivered. Confirm your address:</p><p><a href="#">http://ship-track-service.com/confirm</a></p>', 1, 3, 0, NULL),
('Quarterly Policy Review', 'Compliance Office', 'compliance@company.com', '<p>This quarter''s policy review is now available in the compliance portal.</p>', 0, 2, 0, NULL),
('Unusual Sign-in Attempt', 'Microsoft 365', 'no-reply@micr0soft-secure.com', '<p>We detected a sign-in from a new device. Verify your account:</p><p><a href="#">https://micr0soft-secure.com/verify</a></p>', 1, 4, 0, NULL),
('Wire Transfer Request - Urgent', 'CEO Office', 'ceo-office@company.com', '<p>I need a wire transfer completed in the next 30 minutes. Reply with confirmation and the auth code.</p>', 1, 5, 0, NULL),
('Meeting Notes: Sales Sync', 'Sales Ops', 'sales-ops@company.com', '<p>Notes from today''s sales sync are in the shared drive. No action needed.</p>', 0, 1, 0, NULL),
('Updated Vendor Contract', 'Legal Desk', 'legal@company.com', '<p>Please review the attached updated vendor contract and add any comments.</p>', 0, 2, 1, 'Vendor_Contract.pdf'),
('Final Notice: Account Suspension', 'IT Support', 'it-support@company-secure.com', '<p>Your account will be suspended in 12 hours.</p><p><a href="#">https://company-secure.com/verify</a></p>', 1, 4, 0, NULL);
