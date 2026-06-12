-- Insert Training Modules into CyberAware database

INSERT INTO `training_modules` (`id`, `code`, `title`, `description`, `category`, `difficulty`, `estimated_minutes`, `is_active`, `created_at`, `updated_at`, `video_path`, `content_html`) VALUES
(1, 'PHISH_001', 'Phishing Email Recognition', 'Learn to identify and report phishing emails that attempt to steal credentials or deliver malware.', 'phishing', 3, 15, 1, NOW(), NOW(), 'assets/videos/modules/module_1.mp4', '<h2>Phishing Email Recognition</h2><p>Phishing is one of the most common cyber attacks. Learn how to spot suspicious emails and protect yourself.</p>'),

(2, 'CRED_001', 'Credential Harvesting Awareness', 'Recognize fake login pages designed to steal your username and password.', 'credential', 4, 10, 1, NOW(), NOW(), 'assets/videos/modules/module_2.mp4', '<h2>Credential Harvesting</h2><p>Attackers create fake login pages to steal your credentials. Learn the warning signs.</p>'),

(3, 'SOCIAL_001', 'Social Engineering Defense', 'Identify manipulation tactics used by attackers to trick you into revealing information.', 'social', 4, 12, 1, NOW(), NOW(), 'assets/videos/modules/module_3.mp4', '<h2>Social Engineering Defense</h2><p>Social engineering is the art of manipulating people. Learn how to protect yourself.</p>'),

(4, 'MALWARE_001', 'Malware & Attachment Safety', 'Learn to identify dangerous file attachments before they infect your system.', 'malware', 3, 10, 1, NOW(), NOW(), 'assets/videos/modules/module_4.mp4', '<h2>Malware & Attachment Safety</h2><p>Malicious attachments are a common attack vector. Learn how to stay safe.</p>'),

(5, 'LINK_001', 'Website & Link Safety', 'Master URL inspection and identify malicious websites.', 'link', 3, 8, 1, NOW(), NOW(), 'assets/videos/modules/module_5.mp4', '<h2>Website & Link Safety</h2><p>Not all links are safe. Learn how to inspect URLs and avoid malicious websites.</p>'),

(6, 'PASSWORD_001', 'Password Security', 'Create strong passwords and protect your accounts from unauthorized access.', 'password', 3, 9, 1, NOW(), NOW(), 'assets/videos/modules/module_6.mp4', '<h2>Password Security</h2><p>Strong passwords are your first line of defense. Learn best practices for password security.</p>'),

(7, 'RANSOMWARE_001', 'Ransomware Awareness', 'Understand ransomware threats and learn how to prevent infections.', 'ransomware', 4, 11, 1, NOW(), NOW(), 'assets/videos/modules/module_7.mp4', '<h2>Ransomware Awareness</h2><p>Ransomware can lock your files and demand payment. Learn how to protect yourself.</p>');
