-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jan 16, 2026 at 11:13 PM
-- Server version: 10.11.15-MariaDB
-- PHP Version: 8.3.29

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `intellectualt915_awareness`
--

-- --------------------------------------------------------

--
-- Table structure for table `campaigns`
--

CREATE TABLE `campaigns` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `status` enum('draft','active','completed','archived') DEFAULT 'draft',
  `target_all` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `campaigns`
--

INSERT INTO `campaigns` (`id`, `name`, `description`, `start_date`, `end_date`, `created_by`, `status`, `target_all`, `created_at`, `updated_at`) VALUES
(1, 'Q1 2026 Security Awareness', 'Quarterly phishing awareness campaign for all employees', '2026-01-01', '2026-03-31', 1, 'active', 1, '2026-01-11 15:11:44', NULL),
(2, 'Finance Department Training', 'Targeted training for finance team on wire transfer fraud', '2026-01-15', '2026-02-15', 1, 'active', 0, '2026-01-11 15:11:44', NULL),
(3, 'New Employee Onboarding', 'Security awareness for new hires', '2026-01-01', '2026-12-31', 1, 'active', 0, '2026-01-11 15:11:44', NULL),
(4, 'First Capital', 'Password security', '2026-01-12', '2026-01-16', 10, 'archived', 1, '2026-01-11 15:50:22', '2026-01-12 00:37:26'),
(5, 'Agra', 'Phishing attacks', '2026-01-12', '2026-01-16', 10, 'active', 0, '2026-01-11 15:53:16', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `campaign_departments`
--

CREATE TABLE `campaign_departments` (
  `campaign_id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `campaign_departments`
--

INSERT INTO `campaign_departments` (`campaign_id`, `department_id`) VALUES
(2, 3),
(3, 1),
(3, 2);

-- --------------------------------------------------------

--
-- Table structure for table `campaign_user_assignments`
--

CREATE TABLE `campaign_user_assignments` (
  `campaign_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `assigned_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(20) DEFAULT NULL,
  `manager_user_id` int(11) DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `name`, `code`, `manager_user_id`, `parent_id`, `created_at`) VALUES
(1, 'Information Technology', 'IT', NULL, NULL, '2026-01-11 15:11:44'),
(2, 'Human Resources', 'HR', NULL, NULL, '2026-01-11 15:11:44'),
(3, 'Finance', 'FIN', NULL, NULL, '2026-01-11 15:11:44'),
(4, 'Sales', 'SALES', NULL, NULL, '2026-01-11 15:11:44'),
(5, 'Operations', 'OPS', NULL, NULL, '2026-01-11 15:11:44'),
(6, 'Marketing', 'MKT', NULL, NULL, '2026-01-11 15:11:44');

-- --------------------------------------------------------

--
-- Table structure for table `module_chapters`
--

CREATE TABLE `module_chapters` (
  `id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `chapter_number` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `content_html` mediumtext DEFAULT NULL,
  `video_path` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `module_chapters`
--

INSERT INTO `module_chapters` (`id`, `module_id`, `chapter_number`, `title`, `description`, `content_html`, `video_path`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'Phishing', 'derftyui', 'xfcgvhij', 'assets/videos/chapters/chapter_1_1_1768176191.mp4', '2026-01-12 02:03:11', '2026-01-12 02:03:11'),
(2, 1, 2, 'phishing email recogonition', '', '', 'assets/videos/chapters/chapter_1_2_1768177341.mp4', '2026-01-12 02:19:35', '2026-01-12 02:22:21'),
(3, 1, 3, 'phishingavoid', 'edfrtgyhuji', 'defrgtyhui', 'assets/videos/chapters/chapter_1_3_1768177280.mp4', '2026-01-12 02:20:50', '2026-01-12 02:21:20'),
(4, 1, 4, 'phishing advanced', 'high level phishing', '', 'assets/videos/chapters/chapter_1_4_1768177579.mp4', '2026-01-12 02:23:45', '2026-01-12 02:26:19'),
(5, 4, 1, 'Malware', 'malware', 'tfrdszdrtfyguhijkl', 'assets/videos/chapters/chapter_4_1_1768181899.mp4', '2026-01-12 03:18:23', '2026-01-12 03:38:19'),
(6, 2, 1, 'credential Harvesting', 'keep learning', '', 'assets/videos/chapters/chapter_2_1_1768181069.mp4', '2026-01-12 03:19:40', '2026-01-12 03:28:45'),
(7, 3, 1, 'Introduction', 'Keep learn Fam', '', 'assets/videos/chapters/chapter_3_1_1768181576.mp4', '2026-01-12 03:20:07', '2026-01-12 03:32:56'),
(8, 5, 1, 'Introduction link safety', '', '', 'assets/videos/chapters/chapter_5_1_1768184254.mp4', '2026-01-12 03:21:01', '2026-01-12 04:17:34'),
(9, 2, 2, 'credential Harvesting', '', '', 'assets/videos/chapters/chapter_2_2_1768181425.mp4', '2026-01-12 03:28:59', '2026-01-12 03:30:25');

-- --------------------------------------------------------

--
-- Table structure for table `phishing_templates`
--

CREATE TABLE `phishing_templates` (
  `id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `sender_name` varchar(100) DEFAULT NULL,
  `sender_email` varchar(150) DEFAULT NULL,
  `subject` varchar(200) NOT NULL,
  `body_html` mediumtext NOT NULL,
  `has_attachment` tinyint(1) DEFAULT 0,
  `attachment_name` varchar(150) DEFAULT NULL,
  `difficulty` tinyint(3) UNSIGNED DEFAULT 3,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `phishing_templates`
--

INSERT INTO `phishing_templates` (`id`, `module_id`, `title`, `sender_name`, `sender_email`, `subject`, `body_html`, `has_attachment`, `attachment_name`, `difficulty`, `is_active`, `created_by`, `created_at`) VALUES
(1, 1, 'Urgent Account Verification', 'Security Team', 'security-alert@banksecure-verify.com', 'URGENT: Your account will be suspended in 24 hours', '<p>Dear Valued Customer,</p>\r\n<p>We have detected suspicious activity on your account. For your security, we need you to verify your identity immediately.</p>\r\n<p><strong style=\"color: #e74c3c;\">Your account will be suspended in 24 hours if you do not take action.</strong></p>\r\n<p>Please click the link below to verify your account:</p>\r\n<p><a href=\"#\">https://banksecure-verify.com/account/verify</a></p>\r\n<p>Thank you for your immediate attention to this matter.</p>\r\n<p>Sincerely,<br>Security Team</p>', 0, NULL, 3, 1, 1, '2026-01-11 15:11:44'),
(2, 1, 'IT Password Reset Required', 'IT Department', 'it-support@company-secure.com', 'Mandatory Password Reset - Action Required', '<p>Hello,</p>\r\n<p>As part of our new security policy, all employees must reset their passwords immediately.</p>\r\n<p><strong style=\"color: #e74c3c;\">Failure to reset your password within 12 hours will result in account lockout.</strong></p>\r\n<p>Click here to reset your password: <a href=\"#\">https://company-secure.com/reset-password</a></p>\r\n<p>If you have any questions, contact IT support at the number below.</p>\r\n<p>Best regards,<br>IT Security Team</p>', 0, NULL, 4, 1, 1, '2026-01-11 15:11:44'),
(3, 1, 'Package Delivery Notification', 'Shipping Services', 'notify@shipping-services.com', 'Your package is waiting - Confirm delivery details', '<p>Dear Customer,</p>\r\n<p>We attempted to deliver your package but no one was available to receive it.</p>\r\n<p>To reschedule delivery, please confirm your details by clicking the link below:</p>\r\n<p><a href=\"#\">http://track-package-delivery.com/confirm/78456</a></p>\r\n<p>Your tracking number: <strong>78456-9823-4512</strong></p>\r\n<p>Please respond within 48 hours or your package will be returned to sender.</p>\r\n<p>Thank you,<br>Shipping Services</p>', 1, 'delivery_invoice.pdf.exe', 3, 1, 1, '2026-01-11 15:11:44'),
(4, 2, 'Microsoft Office 365 Login', 'Microsoft', 'noreply@micros0ft-secure.com', 'Verify your Office 365 account', '<p>Your Office 365 account requires verification.</p>\r\n<p>Please log in to confirm your identity:</p>\r\n<p><a href=\"#\">https://micros0ft-login.com/office365/verify</a></p>\r\n<p>This is required to maintain access to your account.</p>', 0, NULL, 4, 1, 1, '2026-01-11 15:11:44'),
(5, 3, 'CEO Urgent Wire Transfer', 'CEO Office', 'ceo@company.com', 'URGENT: Wire Transfer Needed', '<p>Hi,</p>\r\n<p>I am currently in a meeting with a potential client and need you to process an urgent wire transfer.</p>\r\n<p>Please send $15,000 to the following account immediately:</p>\r\n<p>Account: 8475-2938-4756<br>Routing: 021000021</p>\r\n<p>Time is critical. Please handle this ASAP and confirm once done.</p>\r\n<p>Thanks,<br>CEO</p>', 0, NULL, 5, 1, 1, '2026-01-11 15:11:44'),
(6, 4, 'Invoice Payment Due', 'Accounting', 'accounting@supplier-company.com', 'Invoice #4829 - Payment Required', '<p>Hello,</p>\r\n<p>Please find attached invoice for services rendered. Payment is due within 7 days.</p>\r\n<p>Please review and process at your earliest convenience.</p>\r\n<p>Thank you,<br>Accounts Receivable</p>', 1, 'Invoice_4829.pdf.exe', 4, 1, 1, '2026-01-11 15:11:44');

-- --------------------------------------------------------

--
-- Table structure for table `training_actions`
--

CREATE TABLE `training_actions` (
  `id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `step_number` int(11) NOT NULL,
  `action_type` varchar(50) NOT NULL,
  `action_value` text DEFAULT NULL,
  `is_correct` tinyint(1) DEFAULT NULL,
  `points` int(11) DEFAULT 0,
  `timestamp` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `training_actions`
--

INSERT INTO `training_actions` (`id`, `session_id`, `step_number`, `action_type`, `action_value`, `is_correct`, `points`, `timestamp`) VALUES
(1, 1, 1, 'email_action', 'reported', 1, 15, '2026-01-11 15:11:44'),
(2, 2, 1, 'email_action', 'reported', 1, 15, '2026-01-11 15:11:44'),
(3, 3, 1, 'email_action', 'clicked', 0, -10, '2026-01-11 15:11:44'),
(4, 4, 1, 'email_action', 'reported', 1, 15, '2026-01-11 15:11:44'),
(5, 5, 1, 'email_action', 'clicked', 0, -10, '2026-01-11 15:11:44'),
(6, 6, 1, 'email_action', 'deleted', 0, 5, '2026-01-11 15:11:44'),
(7, 7, 1, 'email_action', 'reported', 1, 15, '2026-01-11 15:11:44'),
(8, 8, 1, 'email_action', 'reported', 1, 15, '2026-01-11 15:11:44'),
(9, 9, 1, 'email_action', 'reported', 1, 15, '2026-01-11 15:11:44'),
(10, 10, 1, 'open', NULL, 0, -5, '2026-01-11 16:53:06'),
(11, 11, 1, 'click', NULL, 0, -5, '2026-01-11 16:53:13'),
(12, 12, 1, 'report', NULL, 1, 10, '2026-01-11 16:53:58'),
(13, 13, 1, 'report', NULL, 1, 10, '2026-01-11 16:54:03'),
(14, 14, 1, 'delete', NULL, 0, -5, '2026-01-11 16:54:08'),
(15, 15, 1, 'delete', NULL, 0, -5, '2026-01-11 16:54:36'),
(16, 16, 1, 'report', NULL, 1, 10, '2026-01-11 16:54:40'),
(17, 17, 1, 'delete', NULL, 0, -5, '2026-01-11 16:54:46'),
(18, 18, 1, 'delete', NULL, 0, -5, '2026-01-11 16:54:50'),
(19, 19, 1, 'report', NULL, 1, 10, '2026-01-11 16:55:19'),
(20, 20, 1, 'click', NULL, 0, -5, '2026-01-11 16:55:31'),
(21, 21, 1, 'no', NULL, 1, 10, '2026-01-11 17:02:40'),
(22, 23, 1, 'delete', NULL, 1, 10, '2026-01-11 17:06:32'),
(23, 25, 1, 'inspect', NULL, 1, 10, '2026-01-11 17:09:59'),
(24, 26, 1, 'report', NULL, 1, 10, '2026-01-11 17:10:06'),
(25, 27, 1, 'ignore', NULL, 0, -10, '2026-01-11 17:10:14'),
(26, 28, 1, 'click', NULL, 0, -10, '2026-01-11 17:10:33'),
(27, 30, 1, 'click', NULL, 0, -5, '2026-01-11 17:29:10'),
(28, 31, 1, 'click', NULL, 0, -5, '2026-01-11 17:29:40'),
(29, 38, 1, 'open', NULL, 0, -5, '2026-01-11 18:42:03'),
(30, 39, 1, 'report', NULL, 1, 10, '2026-01-11 18:47:55'),
(31, 40, 1, 'no', NULL, 1, 10, '2026-01-11 18:48:10'),
(32, 41, 1, 'hangup', NULL, 1, 10, '2026-01-11 18:48:27'),
(33, 42, 1, 'report', NULL, 1, 10, '2026-01-11 18:48:42'),
(34, 43, 1, 'report', NULL, 1, 10, '2026-01-11 18:48:50'),
(35, 44, 1, 'report', NULL, 1, 10, '2026-01-11 18:49:41'),
(36, 45, 1, 'no', NULL, 1, 10, '2026-01-11 18:49:51'),
(37, 46, 1, 'no', NULL, 1, 10, '2026-01-11 18:49:57'),
(38, 47, 1, 'hangup', NULL, 1, 10, '2026-01-11 18:50:05'),
(39, 48, 1, 'report', NULL, 1, 10, '2026-01-11 18:50:10'),
(40, 49, 1, 'inspect', NULL, 1, 10, '2026-01-11 18:50:17'),
(41, 54, 1, 'hangup', NULL, 1, 10, '2026-01-11 19:24:19'),
(42, 56, 1, 'hangup', NULL, 1, 10, '2026-01-11 19:24:39'),
(43, 52, 1, 'report', NULL, 1, 10, '2026-01-11 19:25:15'),
(44, 53, 1, 'no', NULL, 1, 10, '2026-01-11 19:25:32'),
(45, 58, 1, 'report', NULL, 1, 10, '2026-01-11 19:25:54'),
(46, 55, 1, 'report', NULL, 1, 10, '2026-01-11 19:26:09'),
(47, 65, 1, 'forward', NULL, 0, -10, '2026-01-11 19:30:21'),
(48, 75, 1, 'hangup', NULL, 1, 10, '2026-01-11 19:51:18'),
(49, 76, 1, 'report', NULL, 1, 10, '2026-01-11 19:51:27'),
(50, 82, 1, 'report', NULL, 1, 10, '2026-01-11 20:24:28'),
(51, 87, 1, 'report', NULL, 1, 10, '2026-01-11 20:24:33'),
(52, 88, 1, 'report', NULL, 1, 10, '2026-01-11 20:24:36'),
(53, 89, 1, 'delete', NULL, 0, -5, '2026-01-11 20:24:41'),
(54, 90, 1, 'click', NULL, 0, -5, '2026-01-11 20:24:45'),
(55, 91, 1, 'open', NULL, 0, -5, '2026-01-11 20:24:49'),
(56, 92, 1, 'report', NULL, 1, 10, '2026-01-11 20:24:53'),
(57, 97, 1, 'email_action', 'report', 1, 15, '2026-01-12 00:52:07'),
(58, 98, 1, 'email_action', 'report', 1, 15, '2026-01-12 00:52:14'),
(59, 99, 1, 'email_action', 'report', 1, 15, '2026-01-12 00:52:20'),
(60, 101, 1, 'email_action', 'report', 1, 15, '2026-01-12 00:52:41'),
(61, 102, 1, 'email_action', 'report', 1, 15, '2026-01-12 00:52:47'),
(62, 103, 1, 'email_action', 'report', 1, 15, '2026-01-12 00:53:10'),
(63, 104, 1, 'email_action', 'report', 1, 15, '2026-01-12 00:53:16'),
(64, 105, 1, 'email_action', 'report', 1, 15, '2026-01-12 00:53:21'),
(65, 106, 1, 'email_action', 'report', 1, 15, '2026-01-12 00:53:27'),
(66, 108, 1, 'email_action', 'open', 0, -10, '2026-01-12 00:55:31'),
(67, 109, 1, 'email_action', 'report', 1, 15, '2026-01-12 00:55:38'),
(68, 111, 1, 'credential_decision', 'no', 1, 15, '2026-01-12 03:26:51'),
(69, 111, 1, 'credential_decision', 'no', 1, 15, '2026-01-12 03:29:09'),
(70, 111, 2, 'credential_decision', 'no', 1, 15, '2026-01-12 03:29:35'),
(71, 111, 2, 'credential_decision', 'no', 1, 15, '2026-01-12 03:30:34'),
(72, 107, 1, 'social_action', 'give', 0, -10, '2026-01-12 03:31:53'),
(73, 107, 1, 'social_action', 'transfer', 0, -10, '2026-01-12 03:31:57'),
(74, 107, 1, 'social_action', 'ask', 0, -10, '2026-01-12 03:32:01'),
(75, 107, 1, 'social_action', 'hangup', 1, 15, '2026-01-12 03:32:10'),
(76, 107, 1, 'social_action', 'hangup', 1, 15, '2026-01-12 03:32:54'),
(77, 107, 1, 'social_action', 'hangup', 1, 15, '2026-01-12 03:33:13'),
(78, 100, 1, 'link_action', 'inspect', 1, 15, '2026-01-12 03:51:32'),
(79, 100, 1, 'link_action', 'report', 1, 15, '2026-01-12 03:51:47'),
(80, 110, 1, 'email_action', 'report', 1, 15, '2026-01-12 04:03:54'),
(81, 112, 1, 'credential_decision', 'no', 1, 15, '2026-01-12 04:09:33'),
(82, 112, 1, 'credential_decision', 'no', 1, 15, '2026-01-12 04:10:40'),
(83, 112, 1, 'credential_decision', 'no', 1, 15, '2026-01-12 04:10:40'),
(84, 112, 2, 'credential_decision', 'no', 1, 15, '2026-01-12 04:10:55'),
(85, 112, 2, 'credential_decision', 'no', 1, 15, '2026-01-12 04:11:12'),
(86, 113, 2, 'credential_decision', 'no', 1, 15, '2026-01-12 04:11:12'),
(87, 114, 2, 'credential_decision', 'no', 1, 15, '2026-01-12 04:11:12'),
(88, 115, 1, 'social_action', 'hangup', 1, 15, '2026-01-12 04:13:15'),
(89, 115, 1, 'social_action', 'hangup', 1, 15, '2026-01-12 04:13:37'),
(90, 116, 1, 'email_action', 'report', 1, 15, '2026-01-12 04:14:09'),
(91, 116, 1, 'email_action', 'report', 1, 15, '2026-01-12 04:15:14'),
(92, 117, 1, 'link_action', 'report', 1, 15, '2026-01-12 04:18:04'),
(93, 117, 1, 'link_action', 'report', 1, 15, '2026-01-12 04:18:07'),
(94, 117, 1, 'link_action', 'report', 1, 15, '2026-01-12 04:19:13'),
(95, 118, 1, 'credential_decision', 'no', 1, 15, '2026-01-13 11:09:46'),
(96, 118, 1, 'credential_decision', 'no', 1, 15, '2026-01-13 11:09:51'),
(97, 118, 1, 'credential_decision', 'no', 1, 15, '2026-01-14 22:00:25');

-- --------------------------------------------------------

--
-- Table structure for table `training_modules`
--

CREATE TABLE `training_modules` (
  `id` int(11) NOT NULL,
  `code` varchar(30) NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `difficulty` tinyint(3) UNSIGNED DEFAULT 3,
  `estimated_minutes` smallint(6) DEFAULT 5,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp(),
  `video_path` varchar(255) DEFAULT NULL,
  `content_html` mediumtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `training_modules`
--

INSERT INTO `training_modules` (`id`, `code`, `title`, `description`, `category`, `difficulty`, `estimated_minutes`, `is_active`, `created_at`, `updated_at`, `video_path`, `content_html`) VALUES
(1, 'PHISH_001', 'Phishing Email Recognition', 'Learn to identify and report phishing emails that attempt to steal credentials or deliver malware.', 'phishing', 3, 15, 1, '2026-01-11 15:11:44', '2026-01-11 15:11:44', 'assets/videos/modules/module_1_1768173103.mp4', ''),
(2, 'CRED_001', 'Credential Harvesting Awareness', 'Recognize fake login pages designed to steal your username and password.', 'credential', 4, 10, 1, '2026-01-11 15:11:44', '2026-01-11 15:11:44', 'assets/videos/modules/module_2_1768172380.mp4', ''),
(3, 'SOCIAL_001', 'Social Engineering Defense', 'Identify manipulation tactics used by attackers to trick you into revealing information.', 'social', 4, 12, 1, '2026-01-11 15:11:44', '2026-01-11 15:11:44', 'assets/videos/modules/module_3_1768175511.mp4', ''),
(4, 'MALWARE_001', 'Malware & Attachment Safety', 'Learn to identify dangerous file attachments before they infect your system.', 'malware', 3, 10, 1, '2026-01-11 15:11:44', '2026-01-11 15:11:44', 'assets/videos/modules/module_4_1768172060.mp4', ''),
(5, 'LINK_001', 'Website & Link Safety', 'Master URL inspection and identify malicious websites.', 'link', 3, 8, 1, '2026-01-11 15:11:44', '2026-01-11 15:11:44', 'assets/videos/modules/module_5_1768172663.mp4', '');

-- --------------------------------------------------------

--
-- Table structure for table `training_sessions`
--

CREATE TABLE `training_sessions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `campaign_id` int(11) DEFAULT NULL,
  `template_id` int(11) DEFAULT NULL,
  `started_at` datetime DEFAULT current_timestamp(),
  `completed_at` datetime DEFAULT NULL,
  `duration_seconds` int(11) DEFAULT NULL,
  `final_score` int(11) DEFAULT NULL,
  `passed` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `training_sessions`
--

INSERT INTO `training_sessions` (`id`, `user_id`, `module_id`, `campaign_id`, `template_id`, `started_at`, `completed_at`, `duration_seconds`, `final_score`, `passed`) VALUES
(1, 3, 1, 1, 1, '2026-01-05 09:30:00', '2026-01-05 09:45:00', 900, 15, 1),
(2, 3, 2, 1, 4, '2026-01-06 14:20:00', '2026-01-06 14:32:00', 720, 15, 1),
(3, 3, 3, 1, 5, '2026-01-07 10:15:00', '2026-01-07 10:28:00', 780, -10, 0),
(4, 3, 4, 1, 6, '2026-01-08 11:00:00', '2026-01-08 11:12:00', 720, 15, 1),
(5, 4, 1, 1, 1, '2026-01-05 10:00:00', '2026-01-05 10:18:00', 1080, -10, 0),
(6, 4, 2, 1, 4, '2026-01-07 15:30:00', '2026-01-07 15:42:00', 720, 5, 0),
(7, 5, 1, 1, 2, '2026-01-05 08:45:00', '2026-01-05 09:00:00', 900, 15, 1),
(8, 5, 2, 1, 4, '2026-01-06 09:30:00', '2026-01-06 09:42:00', 720, 15, 1),
(9, 5, 3, 1, 5, '2026-01-07 13:15:00', '2026-01-07 13:28:00', 780, 15, 1),
(10, 11, 1, NULL, 3, '2026-01-11 16:52:01', '2026-01-11 16:53:06', 65, -5, NULL),
(11, 11, 1, NULL, 3, '2026-01-11 16:53:13', '2026-01-11 16:53:13', 0, -5, NULL),
(12, 11, 1, NULL, 2, '2026-01-11 16:53:58', '2026-01-11 16:53:58', 0, 10, NULL),
(13, 11, 1, NULL, 2, '2026-01-11 16:54:03', '2026-01-11 16:54:03', 0, 10, NULL),
(14, 11, 1, NULL, 2, '2026-01-11 16:54:08', '2026-01-11 16:54:08', 0, -5, NULL),
(15, 11, 1, NULL, 2, '2026-01-11 16:54:36', '2026-01-11 16:54:36', 0, -5, NULL),
(16, 11, 1, NULL, 2, '2026-01-11 16:54:40', '2026-01-11 16:54:40', 0, 10, NULL),
(17, 11, 1, NULL, 3, '2026-01-11 16:54:46', '2026-01-11 16:54:46', 0, -5, NULL),
(18, 11, 1, NULL, 2, '2026-01-11 16:54:50', '2026-01-11 16:54:50', 0, -5, NULL),
(19, 11, 1, NULL, 1, '2026-01-11 16:55:19', '2026-01-11 16:55:19', 0, 10, NULL),
(20, 11, 1, NULL, 2, '2026-01-11 16:55:31', '2026-01-11 16:55:31', 0, -5, NULL),
(21, 11, 2, NULL, NULL, '2026-01-11 17:02:06', '2026-01-11 17:02:40', 34, 10, NULL),
(22, 11, 2, NULL, NULL, '2026-01-11 17:03:47', NULL, NULL, NULL, NULL),
(23, 11, 4, NULL, NULL, '2026-01-11 17:06:16', '2026-01-11 17:06:32', 16, 10, NULL),
(24, 11, 4, NULL, NULL, '2026-01-11 17:06:40', NULL, NULL, NULL, NULL),
(25, 11, 5, NULL, NULL, '2026-01-11 17:09:22', '2026-01-11 17:09:59', 37, 10, NULL),
(26, 11, 5, NULL, NULL, '2026-01-11 17:10:06', '2026-01-11 17:10:06', 0, 10, NULL),
(27, 11, 5, NULL, NULL, '2026-01-11 17:10:14', '2026-01-11 17:10:14', 0, -10, NULL),
(28, 11, 5, NULL, NULL, '2026-01-11 17:10:33', '2026-01-11 17:10:33', 0, -10, NULL),
(29, 11, 5, NULL, NULL, '2026-01-11 17:10:44', NULL, NULL, NULL, NULL),
(30, 11, 1, NULL, 2, '2026-01-11 17:25:55', '2026-01-11 17:29:10', 195, -5, NULL),
(31, 11, 1, NULL, 2, '2026-01-11 17:29:40', '2026-01-11 17:29:40', 0, -5, NULL),
(32, 11, 1, NULL, 3, '2026-01-11 17:30:16', NULL, NULL, NULL, NULL),
(33, 11, 3, NULL, NULL, '2026-01-11 17:51:53', NULL, NULL, NULL, NULL),
(34, 11, 5, NULL, NULL, '2026-01-11 18:22:20', NULL, NULL, NULL, NULL),
(35, 11, 4, NULL, NULL, '2026-01-11 18:22:41', NULL, NULL, NULL, NULL),
(36, 11, 3, NULL, NULL, '2026-01-11 18:22:44', NULL, NULL, NULL, NULL),
(37, 11, 2, NULL, NULL, '2026-01-11 18:23:47', NULL, NULL, NULL, NULL),
(38, 11, 1, NULL, 3, '2026-01-11 18:23:48', '2026-01-11 18:42:03', 1095, -5, NULL),
(39, 12, 1, NULL, 2, '2026-01-11 18:43:21', '2026-01-11 18:47:55', 274, 10, NULL),
(40, 12, 2, NULL, NULL, '2026-01-11 18:43:29', '2026-01-11 18:48:10', 281, 10, NULL),
(41, 12, 3, NULL, NULL, '2026-01-11 18:43:31', '2026-01-11 18:48:27', 296, 10, NULL),
(42, 12, 4, NULL, NULL, '2026-01-11 18:43:33', '2026-01-11 18:48:42', 309, 10, NULL),
(43, 12, 5, NULL, NULL, '2026-01-11 18:43:35', '2026-01-11 18:48:50', 315, 10, NULL),
(44, 12, 1, NULL, 3, '2026-01-11 18:49:37', '2026-01-11 18:49:41', 4, 10, NULL),
(45, 12, 2, NULL, NULL, '2026-01-11 18:49:47', '2026-01-11 18:49:51', 4, 10, NULL),
(46, 12, 2, NULL, NULL, '2026-01-11 18:49:57', '2026-01-11 18:49:57', 0, 10, NULL),
(47, 12, 3, NULL, NULL, '2026-01-11 18:50:00', '2026-01-11 18:50:05', 5, 10, NULL),
(48, 12, 4, NULL, NULL, '2026-01-11 18:50:07', '2026-01-11 18:50:10', 3, 10, NULL),
(49, 12, 5, NULL, NULL, '2026-01-11 18:50:13', '2026-01-11 18:50:17', 4, 10, NULL),
(50, 12, 1, NULL, 2, '2026-01-11 18:50:58', NULL, NULL, NULL, NULL),
(51, 15, 1, NULL, 2, '2026-01-11 19:23:01', NULL, NULL, NULL, NULL),
(52, 15, 1, NULL, 2, '2026-01-11 19:23:30', '2026-01-11 19:25:15', 105, 10, NULL),
(53, 15, 2, NULL, NULL, '2026-01-11 19:23:36', '2026-01-11 19:25:32', 116, 10, NULL),
(54, 15, 3, NULL, NULL, '2026-01-11 19:23:38', '2026-01-11 19:24:19', 41, 10, NULL),
(55, 15, 4, NULL, NULL, '2026-01-11 19:24:29', '2026-01-11 19:26:09', 100, 10, NULL),
(56, 15, 3, NULL, NULL, '2026-01-11 19:24:32', '2026-01-11 19:24:39', 7, 10, NULL),
(57, 15, 3, NULL, NULL, '2026-01-11 19:24:45', NULL, NULL, NULL, NULL),
(58, 15, 5, NULL, NULL, '2026-01-11 19:25:43', '2026-01-11 19:25:54', 11, 10, NULL),
(59, 15, 5, NULL, NULL, '2026-01-11 19:26:00', NULL, NULL, NULL, NULL),
(60, 15, 1, NULL, 3, '2026-01-11 19:26:22', NULL, NULL, NULL, NULL),
(61, 15, 2, NULL, NULL, '2026-01-11 19:26:27', NULL, NULL, NULL, NULL),
(62, 15, 4, NULL, NULL, '2026-01-11 19:26:29', NULL, NULL, NULL, NULL),
(63, 15, 5, NULL, NULL, '2026-01-11 19:28:12', NULL, NULL, NULL, NULL),
(64, 15, 1, NULL, 1, '2026-01-11 19:28:59', NULL, NULL, NULL, NULL),
(65, 15, 4, NULL, NULL, '2026-01-11 19:30:06', '2026-01-11 19:30:21', 15, -10, NULL),
(66, 15, 3, NULL, NULL, '2026-01-11 19:30:40', NULL, NULL, NULL, NULL),
(67, 15, 4, NULL, NULL, '2026-01-11 19:30:45', NULL, NULL, NULL, NULL),
(68, 15, 2, NULL, NULL, '2026-01-11 19:30:51', NULL, NULL, NULL, NULL),
(69, 12, 1, NULL, 3, '2026-01-11 19:49:36', NULL, NULL, NULL, NULL),
(70, 12, 2, NULL, NULL, '2026-01-11 19:49:41', NULL, NULL, NULL, NULL),
(71, 12, 3, NULL, NULL, '2026-01-11 19:49:45', NULL, NULL, NULL, NULL),
(72, 12, 4, NULL, NULL, '2026-01-11 19:49:47', NULL, NULL, NULL, NULL),
(73, 12, 5, NULL, NULL, '2026-01-11 19:49:49', NULL, NULL, NULL, NULL),
(74, 11, 2, NULL, NULL, '2026-01-11 19:51:11', NULL, NULL, NULL, NULL),
(75, 11, 3, NULL, NULL, '2026-01-11 19:51:14', '2026-01-11 19:51:18', 4, 10, NULL),
(76, 11, 4, NULL, NULL, '2026-01-11 19:51:21', '2026-01-11 19:51:27', 6, 10, NULL),
(77, 15, 1, NULL, 3, '2026-01-11 19:52:01', NULL, NULL, NULL, NULL),
(78, 15, 4, NULL, NULL, '2026-01-11 19:52:12', NULL, NULL, NULL, NULL),
(79, 15, 2, NULL, NULL, '2026-01-11 19:52:24', NULL, NULL, NULL, NULL),
(80, 15, 3, NULL, NULL, '2026-01-11 19:52:26', NULL, NULL, NULL, NULL),
(81, 15, 5, NULL, NULL, '2026-01-11 19:52:28', NULL, NULL, NULL, NULL),
(82, 11, 1, NULL, 2, '2026-01-11 19:58:16', '2026-01-11 20:24:28', 1572, 10, NULL),
(83, 11, 2, NULL, NULL, '2026-01-11 19:58:18', NULL, NULL, NULL, NULL),
(84, 11, 3, NULL, NULL, '2026-01-11 19:58:19', NULL, NULL, NULL, NULL),
(85, 11, 4, NULL, NULL, '2026-01-11 19:58:22', NULL, NULL, NULL, NULL),
(86, 11, 5, NULL, NULL, '2026-01-11 19:58:25', NULL, NULL, NULL, NULL),
(87, 11, 1, NULL, 1, '2026-01-11 20:24:33', '2026-01-11 20:24:33', 0, 10, NULL),
(88, 11, 1, NULL, 3, '2026-01-11 20:24:36', '2026-01-11 20:24:36', 0, 10, NULL),
(89, 11, 1, NULL, 1, '2026-01-11 20:24:41', '2026-01-11 20:24:41', 0, -5, NULL),
(90, 11, 1, NULL, 1, '2026-01-11 20:24:45', '2026-01-11 20:24:45', 0, -5, NULL),
(91, 11, 1, NULL, 3, '2026-01-11 20:24:49', '2026-01-11 20:24:49', 0, -5, NULL),
(92, 11, 1, NULL, 2, '2026-01-11 20:24:53', '2026-01-11 20:24:53', 0, 10, NULL),
(93, 11, 1, NULL, 3, '2026-01-11 20:47:37', NULL, NULL, NULL, NULL),
(94, 10, 5, NULL, NULL, '2026-01-11 21:42:07', NULL, NULL, NULL, NULL),
(95, 10, 1, NULL, 1, '2026-01-11 21:42:10', NULL, NULL, NULL, NULL),
(96, 11, 1, NULL, 1, '2026-01-12 00:39:38', NULL, NULL, NULL, NULL),
(97, 11, 4, NULL, NULL, '2026-01-12 00:49:03', '2026-01-12 00:52:07', 184, 15, 1),
(98, 11, 4, NULL, NULL, '2026-01-12 00:52:14', '2026-01-12 00:52:14', 0, 15, 1),
(99, 11, 4, NULL, NULL, '2026-01-12 00:52:20', '2026-01-12 00:52:20', 0, 15, 1),
(100, 11, 5, NULL, NULL, '2026-01-12 00:52:24', NULL, NULL, NULL, NULL),
(101, 11, 4, NULL, NULL, '2026-01-12 00:52:36', '2026-01-12 00:52:41', 5, 15, 1),
(102, 11, 4, NULL, NULL, '2026-01-12 00:52:47', '2026-01-12 00:52:47', 0, 15, 1),
(103, 11, 4, NULL, NULL, '2026-01-12 00:53:06', '2026-01-12 00:53:10', 4, 15, 1),
(104, 11, 4, NULL, NULL, '2026-01-12 00:53:16', '2026-01-12 00:53:16', 0, 15, 1),
(105, 11, 4, NULL, NULL, '2026-01-12 00:53:21', '2026-01-12 00:53:21', 0, 15, 1),
(106, 11, 4, NULL, NULL, '2026-01-12 00:53:27', '2026-01-12 00:53:27', 0, 15, 1),
(107, 11, 3, NULL, NULL, '2026-01-12 00:54:28', '2026-01-12 03:38:55', 9867, NULL, 1),
(108, 11, 4, NULL, NULL, '2026-01-12 00:54:31', '2026-01-12 00:55:31', 60, -10, 0),
(109, 11, 4, NULL, NULL, '2026-01-12 00:55:38', '2026-01-12 00:55:38', 0, 15, 1),
(110, 11, 4, NULL, NULL, '2026-01-12 00:57:26', '2026-01-12 04:03:54', 11188, NULL, 1),
(111, 11, 2, NULL, NULL, '2026-01-12 00:59:16', '2026-01-12 03:42:16', 9780, NULL, 1),
(112, 16, 2, NULL, NULL, '2026-01-12 04:09:33', '2026-01-12 04:11:12', 99, 75, 1),
(113, 16, 2, NULL, NULL, '2026-01-12 04:11:12', '2026-01-12 04:11:12', 0, 15, 1),
(114, 16, 2, NULL, NULL, '2026-01-12 04:11:12', '2026-01-12 04:11:12', 0, 15, 1),
(115, 16, 3, NULL, NULL, '2026-01-12 04:13:15', '2026-01-12 04:13:37', 22, 30, 1),
(116, 16, 4, NULL, NULL, '2026-01-12 04:14:09', '2026-01-12 04:15:14', 65, 30, 1),
(117, 16, 5, NULL, NULL, '2026-01-12 04:18:04', '2026-01-12 04:19:13', 69, 45, 1),
(118, 15, 2, NULL, NULL, '2026-01-13 11:09:46', NULL, NULL, 45, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(150) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `role` enum('trainee','admin','manager','compliance') NOT NULL DEFAULT 'trainee',
  `department_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `last_login` datetime DEFAULT NULL,
  `failed_login_attempts` int(11) DEFAULT 0,
  `password_changed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password_hash`, `full_name`, `email`, `role`, `department_id`, `is_active`, `created_at`, `last_login`, `failed_login_attempts`, `password_changed_at`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin User', 'admin@company.com', 'admin', 1, 1, '2026-01-11 15:11:44', NULL, 0, NULL),
(2, 'manager1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sarah Johnson', 'sarah.j@company.com', 'manager', 1, 1, '2026-01-11 15:11:44', NULL, 0, NULL),
(3, 'trainee', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John Doe', 'john.doe@company.com', 'trainee', 1, 1, '2026-01-11 15:11:44', NULL, 0, NULL),
(4, 'jsmith', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Jane Smith', 'jane.smith@company.com', 'trainee', 2, 1, '2026-01-11 15:11:44', NULL, 0, NULL),
(5, 'mwilliams', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Michael Williams', 'michael.w@company.com', 'trainee', 3, 1, '2026-01-11 15:11:44', NULL, 0, NULL),
(6, 'ebrown', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Emily Brown', 'emily.b@company.com', 'trainee', 4, 1, '2026-01-11 15:11:44', NULL, 0, NULL),
(7, 'rdavis', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Robert Davis', 'robert.d@company.com', 'trainee', 5, 1, '2026-01-11 15:11:44', NULL, 0, NULL),
(8, 'lgarcia', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Lisa Garcia', 'lisa.g@company.com', 'trainee', 6, 1, '2026-01-11 15:11:44', NULL, 0, NULL),
(9, 'dav1', '$2y$10$2xq4NPeMy2ibFjcRi3MPpOmeCKSxR0WF0PTQZwl5AFGPFlMfbjbTa', 'David David', 'david@intellectualtechnology.com.na', 'admin', NULL, 1, '2026-01-11 15:27:22', '2026-01-12 05:29:25', 0, NULL),
(10, 'megone533', '$2y$10$3.TEKgQcwgBZU/0avYY/MeWrv5EmxbxohJ9yhyfoVkGI5wCAswvrW', 'Meinold', 'megone533@gmail.com', 'admin', NULL, 1, '2026-01-11 15:48:47', '2026-01-16 21:32:13', 0, NULL),
(11, 'davv', '$2y$10$HehLet52DCN4/ppJrCI1ze//oY13GCRalmpZ9fyDmp9Ej46dsymPq', 'David David', 'david1@intellectualtechnology.com.na', 'trainee', NULL, 1, '2026-01-11 16:24:52', '2026-01-12 05:30:21', 0, NULL),
(12, 'david', '$2y$10$3wcBwS4arpG6EBuN9pR/4eg0itQhsaTCdnqr09MUCUiOFdigvWwqC', 'davd dav', 'dav@gmail.com', 'trainee', NULL, 1, '2026-01-11 18:42:53', '2026-01-11 19:50:41', 0, NULL),
(13, 'Noisy', '$2y$10$wVRXCSyS8w3m1/n3RATFS.FRi94eYXjz49nbymKw.2wA5/.hbYBYu', 'John', 'noisy@gmail.com', 'trainee', NULL, 1, '2026-01-11 19:00:27', NULL, 0, NULL),
(14, 'Noisyy', '$2y$10$7GHA/k2ktuGG4Wn0rCDWiuHlqd4GF6dFI.fuQbE6aw.BEc1PDajBi', 'John', 'noisyy@gmail.com', 'trainee', NULL, 1, '2026-01-11 19:02:06', NULL, 9, NULL),
(15, 'John', '$2y$10$k.wD/BXtVeNiHhYzNJmro.jK7obNVsofzCF3wtZHdNsVMTURaXWy.', 'Johnny', 'john@gmail.com', 'trainee', NULL, 1, '2026-01-11 19:22:45', '2026-01-16 21:54:03', 0, NULL),
(16, 'davvv', '$2y$10$g7m47b4qZQdhG3HAk7f.u.l1Hx5dmUaY89gxMvwEMMNq1pYNgT8Te', 'dsquare', 'dsquare@intellectualtechnology.com.na', 'trainee', 3, 1, '2026-01-12 04:05:13', '2026-01-13 11:32:52', 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_chapter_progress`
--

CREATE TABLE `user_chapter_progress` (
  `user_id` int(11) NOT NULL,
  `chapter_id` int(11) NOT NULL,
  `completed_at` datetime DEFAULT NULL,
  `watched_seconds` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `user_chapter_progress`
--

INSERT INTO `user_chapter_progress` (`user_id`, `chapter_id`, `completed_at`, `watched_seconds`) VALUES
(11, 1, '2026-01-12 02:19:54', -1),
(11, 2, '2026-01-12 02:22:29', -1),
(11, 3, '2026-01-12 02:26:57', -1),
(11, 4, '2026-01-12 02:28:34', -1),
(11, 5, '2026-01-12 04:03:54', -1),
(11, 6, '2026-01-12 03:29:09', -1),
(11, 7, '2026-01-12 03:33:13', -1),
(11, 8, '2026-01-12 03:51:47', 0),
(11, 9, '2026-01-12 03:30:34', -1),
(15, 1, '2026-01-13 10:48:05', -1),
(15, 5, NULL, 0),
(15, 6, '2026-01-14 22:00:25', 0),
(15, 7, NULL, 0),
(15, 8, NULL, 0),
(16, 1, '2026-01-12 04:07:10', -1),
(16, 2, '2026-01-12 04:07:28', -1),
(16, 3, '2026-01-12 04:07:43', -1),
(16, 4, '2026-01-12 04:08:45', -1),
(16, 5, '2026-01-12 04:15:14', -1),
(16, 6, '2026-01-12 04:10:40', -1),
(16, 7, '2026-01-12 04:13:37', -1),
(16, 8, '2026-01-12 04:19:13', -1),
(16, 9, '2026-01-12 04:11:12', -1);

-- --------------------------------------------------------

--
-- Table structure for table `user_training_summary`
--

CREATE TABLE `user_training_summary` (
  `user_id` int(11) NOT NULL,
  `total_sessions` int(11) DEFAULT 0,
  `completed_sessions` int(11) DEFAULT 0,
  `phishing_clicks` int(11) DEFAULT 0,
  `phishing_reports` int(11) DEFAULT 0,
  `last_training_date` datetime DEFAULT NULL,
  `risk_score` int(11) DEFAULT 100,
  `updated_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `user_training_summary`
--

INSERT INTO `user_training_summary` (`user_id`, `total_sessions`, `completed_sessions`, `phishing_clicks`, `phishing_reports`, `last_training_date`, `risk_score`, `updated_at`) VALUES
(3, 5, 4, 1, 3, '2026-01-08 11:12:00', 35, '2026-01-11 15:11:44'),
(4, 3, 3, 2, 1, '2026-01-07 15:42:00', 55, '2026-01-11 15:11:44'),
(5, 7, 6, 0, 6, '2026-01-07 13:28:00', 15, '2026-01-11 15:11:44'),
(6, 2, 2, 3, 0, NULL, 85, '2026-01-11 15:11:44'),
(7, 4, 3, 1, 2, NULL, 45, '2026-01-11 15:11:44'),
(8, 1, 1, 0, 1, NULL, 25, '2026-01-11 15:11:44');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `campaigns`
--
ALTER TABLE `campaigns`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `campaign_departments`
--
ALTER TABLE `campaign_departments`
  ADD PRIMARY KEY (`campaign_id`,`department_id`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `campaign_user_assignments`
--
ALTER TABLE `campaign_user_assignments`
  ADD PRIMARY KEY (`campaign_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `manager_user_id` (`manager_user_id`),
  ADD KEY `parent_id` (`parent_id`);

--
-- Indexes for table `module_chapters`
--
ALTER TABLE `module_chapters`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_chapter_per_module` (`module_id`,`chapter_number`);

--
-- Indexes for table `phishing_templates`
--
ALTER TABLE `phishing_templates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `module_id` (`module_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `training_actions`
--
ALTER TABLE `training_actions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `session_id` (`session_id`);

--
-- Indexes for table `training_modules`
--
ALTER TABLE `training_modules`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `training_sessions`
--
ALTER TABLE `training_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `module_id` (`module_id`),
  ADD KEY `campaign_id` (`campaign_id`),
  ADD KEY `template_id` (`template_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `user_chapter_progress`
--
ALTER TABLE `user_chapter_progress`
  ADD PRIMARY KEY (`user_id`,`chapter_id`),
  ADD KEY `chapter_id` (`chapter_id`);

--
-- Indexes for table `user_training_summary`
--
ALTER TABLE `user_training_summary`
  ADD PRIMARY KEY (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `campaigns`
--
ALTER TABLE `campaigns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `module_chapters`
--
ALTER TABLE `module_chapters`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `phishing_templates`
--
ALTER TABLE `phishing_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `training_actions`
--
ALTER TABLE `training_actions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=98;

--
-- AUTO_INCREMENT for table `training_modules`
--
ALTER TABLE `training_modules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `training_sessions`
--
ALTER TABLE `training_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=119;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `campaigns`
--
ALTER TABLE `campaigns`
  ADD CONSTRAINT `campaigns_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `campaign_departments`
--
ALTER TABLE `campaign_departments`
  ADD CONSTRAINT `campaign_departments_ibfk_1` FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `campaign_departments_ibfk_2` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `campaign_user_assignments`
--
ALTER TABLE `campaign_user_assignments`
  ADD CONSTRAINT `campaign_user_assignments_ibfk_1` FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `campaign_user_assignments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `departments`
--
ALTER TABLE `departments`
  ADD CONSTRAINT `departments_ibfk_1` FOREIGN KEY (`manager_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `departments_ibfk_2` FOREIGN KEY (`parent_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `module_chapters`
--
ALTER TABLE `module_chapters`
  ADD CONSTRAINT `module_chapters_ibfk_1` FOREIGN KEY (`module_id`) REFERENCES `training_modules` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `phishing_templates`
--
ALTER TABLE `phishing_templates`
  ADD CONSTRAINT `phishing_templates_ibfk_1` FOREIGN KEY (`module_id`) REFERENCES `training_modules` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `phishing_templates_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `training_actions`
--
ALTER TABLE `training_actions`
  ADD CONSTRAINT `training_actions_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `training_sessions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `training_sessions`
--
ALTER TABLE `training_sessions`
  ADD CONSTRAINT `training_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `training_sessions_ibfk_2` FOREIGN KEY (`module_id`) REFERENCES `training_modules` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `training_sessions_ibfk_3` FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `training_sessions_ibfk_4` FOREIGN KEY (`template_id`) REFERENCES `phishing_templates` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `user_chapter_progress`
--
ALTER TABLE `user_chapter_progress`
  ADD CONSTRAINT `user_chapter_progress_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_chapter_progress_ibfk_2` FOREIGN KEY (`chapter_id`) REFERENCES `module_chapters` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_training_summary`
--
ALTER TABLE `user_training_summary`
  ADD CONSTRAINT `user_training_summary_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
