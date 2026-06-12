-- Sample Incident Reporting Data for Testing
-- This file contains sample data to test the incident reporting feature
-- Run this after creating the tables with create_incident_reporting.sql

-- Insert sample incident reports
INSERT INTO `incident_reports` (
    `user_id`, `incident_type`, `subject`, `description`, 
    `status`, `severity`, `email_sender`, `email_subject`, 
    `email_content`, `reported_at`
) VALUES
(
    2, 'phishing', 'Suspicious email from IT Department',
    'Received an email claiming to be from IT asking me to verify my account credentials. The email looks suspicious with poor grammar and urgent language.',
    'new', 'high',
    'it-support@suspicious-domain.com', 'URGENT: Verify Your Account Now',
    'Dear User, Your account has been compromised. Please click here to verify your credentials immediately. This is urgent!',
    NOW() - INTERVAL 2 HOUR
),
(
    3, 'malware', 'Suspicious attachment in email',
    'Received email with attachment named "invoice.exe" from unknown sender. The file extension seems suspicious.',
    'under_review', 'high',
    'unknown@external-company.com', 'Invoice for your order',
    'Please find attached the invoice for your recent order.',
    NOW() - INTERVAL 4 HOUR
),
(
    4, 'suspicious_link', 'Shortened URL in email',
    'Email contains a shortened URL that redirects to a suspicious website. The domain looks similar to our company domain but with slight variations.',
    'resolved', 'medium',
    'sender@lookalike-domain.com', 'Check out this important update',
    'Click here for important security update: bit.ly/xyz123',
    NOW() - INTERVAL 1 DAY
),
(
    5, 'credential_theft', 'Password reset request',
    'Received email asking me to reset my password through a link. The link does not match our official domain.',
    'new', 'high',
    'noreply@fake-company.com', 'Password Reset Required',
    'Your password has expired. Please reset it here: http://fake-domain.com/reset',
    NOW() - INTERVAL 30 MINUTE
),
(
    6, 'social_engineering', 'Caller claiming to be from IT',
    'Received a phone call from someone claiming to be from IT support asking for my login credentials. I did not provide any information.',
    'under_review', 'medium',
    NULL, NULL, NULL,
    NOW() - INTERVAL 6 HOUR
),
(
    7, 'phishing', 'Bank account verification email',
    'Email claiming to be from our bank asking to verify account details. Looks like a phishing attempt.',
    'resolved', 'high',
    'security@bank-lookalike.com', 'Verify Your Bank Account',
    'Your bank account requires verification. Please provide your account number and PIN.',
    NOW() - INTERVAL 2 DAY
),
(
    2, 'other', 'Unusual network activity',
    'Noticed unusual network activity on my computer. Multiple failed login attempts in the logs.',
    'new', 'medium',
    NULL, NULL, NULL,
    NOW() - INTERVAL 1 HOUR
),
(
    3, 'phishing', 'CEO impersonation email',
    'Received email appearing to be from CEO asking for urgent wire transfer. Email address looks similar but not exact.',
    'under_review', 'critical',
    'ceo@company-lookalike.com', 'URGENT: Wire Transfer Needed',
    'I need you to process an urgent wire transfer of $50,000 to this account immediately.',
    NOW() - INTERVAL 3 HOUR
);

-- Insert sample comments on incidents
INSERT INTO `incident_report_comments` (
    `incident_id`, `user_id`, `comment_text`, `created_at`
) VALUES
(
    1, 1, 'Confirmed as phishing attempt. Email domain is spoofed. Blocking sender domain.',
    NOW() - INTERVAL 1 HOUR 30 MINUTE
),
(
    1, 2, 'Thank you for reporting. We have taken action.',
    NOW() - INTERVAL 1 HOUR
),
(
    2, 1, 'File analyzed - confirmed as malware. Quarantined on all systems.',
    NOW() - INTERVAL 2 HOUR
),
(
    3, 1, 'URL checked - redirects to phishing site. Blocked at gateway.',
    NOW() - INTERVAL 12 HOUR
),
(
    4, 1, 'This is a known phishing campaign. Added to blocklist.',
    NOW() - INTERVAL 20 MINUTE
),
(
    6, 1, 'Social engineering attempt confirmed. Caller did not have valid employee ID.',
    NOW() - INTERVAL 3 HOUR
),
(
    8, 1, 'CRITICAL: CEO impersonation detected. Notifying finance team immediately.',
    NOW() - INTERVAL 2 HOUR 30 MINUTE
),
(
    8, 1, 'All employees notified. No funds transferred. Incident contained.',
    NOW() - INTERVAL 2 HOUR
);

-- Display summary of inserted data
SELECT 
    'Total Incidents' as Metric,
    COUNT(*) as Count
FROM incident_reports
UNION ALL
SELECT 
    'New Reports',
    COUNT(*) 
FROM incident_reports 
WHERE status = 'new'
UNION ALL
SELECT 
    'Under Review',
    COUNT(*) 
FROM incident_reports 
WHERE status = 'under_review'
UNION ALL
SELECT 
    'Resolved',
    COUNT(*) 
FROM incident_reports 
WHERE status = 'resolved'
UNION ALL
SELECT 
    'Critical Severity',
    COUNT(*) 
FROM incident_reports 
WHERE severity = 'critical'
UNION ALL
SELECT 
    'Total Comments',
    COUNT(*) 
FROM incident_report_comments;
