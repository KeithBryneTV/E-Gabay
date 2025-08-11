-- E-GABAY Database Backup
-- Generated on: 2025-08-06 22:20:35
-- Database: u315462064_egabay

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

-- --------------------------------------------------------
-- Table structure for table `chat_messages`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `chat_messages`;
CREATE TABLE `chat_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `chat_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `message_type` enum('user','system') NOT NULL DEFAULT 'user',
  `file_path` varchar(500) DEFAULT NULL,
  `file_url` varchar(500) DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `chat_messages_ibfk_1` (`chat_id`),
  KEY `chat_messages_ibfk_2` (`user_id`),
  CONSTRAINT `chat_messages_ibfk_1` FOREIGN KEY (`chat_id`) REFERENCES `chat_sessions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chat_messages_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=178 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `chat_messages`

INSERT INTO `chat_messages` (`id`, `chat_id`, `user_id`, `message`, `is_read`, `created_at`, `message_type`, `file_path`, `file_url`, `file_name`, `file_size`) VALUES
('173', '28', NULL, 'Chat session started by counselor. This conversation is private and confidential.', '1', '2025-08-05 13:15:01', 'system', NULL, NULL, NULL, NULL),
('174', '28', '24', 'hi', '1', '2025-08-05 13:15:39', 'user', NULL, NULL, NULL, NULL),
('175', '28', '24', '???? Shared a file: letter caps.docx', '1', '2025-08-05 13:15:54', 'user', 'uploads/chat_files/chat_6891938a13ed2_letter caps.docx', NULL, 'letter caps.docx', '1305524'),
('176', '28', '39', 'kjjgdfhgj', '1', '2025-08-05 13:15:54', 'user', NULL, NULL, NULL, NULL),
('177', '28', NULL, 'Chat session ended by student. The issue has been resolved.', '1', '2025-08-05 13:16:18', 'system', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------
-- Table structure for table `chat_sessions`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `chat_sessions`;
CREATE TABLE `chat_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `counselor_id` int(11) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `status` enum('active','closed') NOT NULL DEFAULT 'active',
  `consultation_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_chat_sessions_student` (`student_id`),
  KEY `idx_chat_sessions_counselor` (`counselor_id`),
  KEY `chat_sessions_ibfk_3` (`consultation_id`),
  CONSTRAINT `chat_sessions_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `chat_sessions_ibfk_2` FOREIGN KEY (`counselor_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `chat_sessions_ibfk_3` FOREIGN KEY (`consultation_id`) REFERENCES `consultation_requests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `chat_sessions`

INSERT INTO `chat_sessions` (`id`, `student_id`, `counselor_id`, `subject`, `status`, `consultation_id`, `created_at`, `updated_at`) VALUES
('28', '24', '39', 'Consultation #31', 'closed', '31', '2025-08-05 13:15:01', '2025-08-05 13:16:18');

-- --------------------------------------------------------
-- Table structure for table `consultation_requests`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `consultation_requests`;
CREATE TABLE `consultation_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `counselor_id` int(11) DEFAULT NULL,
  `issue_description` text NOT NULL,
  `issue_category` varchar(100) DEFAULT NULL,
  `preferred_date` date NOT NULL,
  `preferred_time` time NOT NULL,
  `communication_method` enum('in_person','phone','video','email','chat','voice') NOT NULL,
  `is_anonymous` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('pending','live','completed','cancelled') NOT NULL DEFAULT 'pending',
  `counselor_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_consultation_requests_student` (`student_id`),
  KEY `idx_consultation_requests_counselor` (`counselor_id`),
  KEY `idx_consultation_status` (`status`),
  KEY `idx_consultation_student` (`student_id`),
  KEY `idx_consultation_counselor` (`counselor_id`),
  KEY `idx_consultation_date` (`created_at`),
  CONSTRAINT `consultation_requests_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `consultation_requests_ibfk_2` FOREIGN KEY (`counselor_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `consultation_requests`

INSERT INTO `consultation_requests` (`id`, `student_id`, `counselor_id`, `issue_description`, `issue_category`, `preferred_date`, `preferred_time`, `communication_method`, `is_anonymous`, `status`, `counselor_notes`, `created_at`, `updated_at`) VALUES
('31', '24', '39', 'jwjsjdndnsn sjjans', 'Relationships', '2025-08-15', '13:00:00', '', '0', 'completed', '', '2025-08-05 13:13:47', '2025-08-05 13:16:18'),
('33', '51', '39', 'This is just a test.', 'Other', '2025-08-07', '09:00:00', '', '1', 'live', '', '2025-08-06 12:48:45', '2025-08-06 16:09:49');

-- --------------------------------------------------------
-- Table structure for table `counselor_profiles`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `counselor_profiles`;
CREATE TABLE `counselor_profiles` (
  `profile_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `specialization` varchar(100) DEFAULT NULL,
  `availability` text DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`profile_id`),
  KEY `counselor_profiles_ibfk_1` (`user_id`),
  CONSTRAINT `counselor_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `counselor_profiles`

INSERT INTO `counselor_profiles` (`profile_id`, `user_id`, `specialization`, `availability`, `contact_number`, `bio`, `created_at`, `updated_at`) VALUES
('5', '39', '', '', NULL, NULL, '2025-08-05 13:12:24', '2025-08-05 13:12:24'),
('6', '52', '', '{\"monday\":{\"available\":true,\"start_time\":\"08:00\",\"end_time\":\"12:00\"},\"tuesday\":{\"available\":false,\"start_time\":\"\",\"end_time\":\"\"},\"wednesday\":{\"available\":true,\"start_time\":\"08:00\",\"end_time\":\"12:00\"},\"thursday\":{\"available\":false,\"start_time\":\"\",\"end_time\":\"\"},\"friday\":{\"available\":true,\"start_time\":\"08:00\",\"end_time\":\"12:00\"},\"saturday\":{\"available\":false,\"start_time\":\"\",\"end_time\":\"\"},\"sunday\":{\"available\":false,\"start_time\":\"\",\"end_time\":\"\"}}', NULL, NULL, '2025-08-06 15:22:42', '2025-08-06 15:24:12');

-- --------------------------------------------------------
-- Table structure for table `email_queue`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `email_queue`;
CREATE TABLE `email_queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `to_email` varchar(255) NOT NULL,
  `subject` varchar(500) NOT NULL,
  `body` text NOT NULL,
  `status` enum('pending','sent','failed') DEFAULT 'pending',
  `attempts` int(11) DEFAULT 0,
  `last_attempt` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `sent_at` datetime DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_status_created` (`status`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `email_queue`

-- No data for table `email_queue`

-- --------------------------------------------------------
-- Table structure for table `email_template_variables`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `email_template_variables`;
CREATE TABLE `email_template_variables` (
  `variable_id` int(11) NOT NULL AUTO_INCREMENT,
  `variable_name` varchar(100) NOT NULL,
  `variable_description` text NOT NULL,
  `variable_example` varchar(255) DEFAULT NULL,
  `is_global` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`variable_id`),
  KEY `idx_variable_name` (`variable_name`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `email_template_variables`

INSERT INTO `email_template_variables` (`variable_id`, `variable_name`, `variable_description`, `variable_example`, `is_global`, `created_at`) VALUES
('1', '{{site_name}}', 'The name of the website/system', 'EGABAY ASC', '1', '2025-08-03 23:25:17'),
('2', '{{logo}}', 'Website logo image (automatically embedded)', '<img src=\"logo.png\" alt=\"Logo\">', '1', '2025-08-03 23:25:17'),
('3', '{{admin_email}}', 'Administrator email address', 'admin@egabay.edu.ph', '1', '2025-08-03 23:25:17'),
('4', '{{first_name}}', 'User\'s first name', 'John', '0', '2025-08-03 23:25:17'),
('5', '{{last_name}}', 'User\'s last name', 'Doe', '0', '2025-08-03 23:25:17'),
('6', '{{username}}', 'User\'s username', 'john_doe', '0', '2025-08-03 23:25:17'),
('7', '{{email}}', 'User\'s email address', 'john@example.com', '0', '2025-08-03 23:25:17'),
('8', '{{verification_link}}', 'Account verification link', 'https://site.com/verify.php?token=abc123', '0', '2025-08-03 23:25:17'),
('9', '{{reset_link}}', 'Password reset link', 'https://site.com/reset.php?token=abc123', '0', '2025-08-03 23:25:17'),
('10', '{{dashboard_link}}', 'User dashboard link', 'https://site.com/dashboard/', '0', '2025-08-03 23:25:17'),
('11', '{{consultation_date}}', 'Consultation date', 'January 15, 2025', '0', '2025-08-03 23:25:17'),
('12', '{{consultation_time}}', 'Consultation time', '2:00 PM - 3:00 PM', '0', '2025-08-03 23:25:17'),
('13', '{{counselor_name}}', 'Assigned counselor name', 'Dr. Jane Smith', '0', '2025-08-03 23:25:17'),
('14', '{{consultation_status}}', 'Current consultation status', 'Approved', '0', '2025-08-03 23:25:17'),
('15', '{{consultation_link}}', 'Link to consultation details', 'https://site.com/consultation/123', '0', '2025-08-03 23:25:17'),
('16', '{{subject}}', 'Email subject (for general notifications)', 'Important Update', '0', '2025-08-03 23:25:17'),
('17', '{{message_content}}', 'Main message content', 'Your consultation has been scheduled.', '0', '2025-08-03 23:25:17'),
('18', '{{action_text}}', 'Text for action button', 'View Details', '0', '2025-08-03 23:25:17'),
('19', '{{action_link}}', 'Link for action button', 'https://site.com/action', '0', '2025-08-03 23:25:17'),
('20', '{{notification_message}}', 'Notification message content', 'Your consultation has been approved.', '0', '2025-08-03 23:25:17');

-- --------------------------------------------------------
-- Table structure for table `email_templates`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `email_templates`;
CREATE TABLE `email_templates` (
  `template_id` int(11) NOT NULL AUTO_INCREMENT,
  `template_name` varchar(100) NOT NULL,
  `template_subject` varchar(255) NOT NULL,
  `template_body` text NOT NULL,
  `template_description` varchar(500) DEFAULT NULL,
  `template_type` enum('system','custom') DEFAULT 'system',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `header_title` varchar(255) DEFAULT NULL,
  `greeting_text` varchar(255) DEFAULT NULL,
  `main_message` text DEFAULT NULL,
  `button_text` varchar(100) DEFAULT NULL,
  `button_link` varchar(500) DEFAULT NULL,
  `fallback_message` text DEFAULT NULL,
  `footer_note` text DEFAULT NULL,
  `custom_logo` varchar(255) DEFAULT NULL,
  `use_structured_editor` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`template_id`),
  UNIQUE KEY `template_name` (`template_name`),
  KEY `idx_template_name` (`template_name`),
  KEY `idx_template_type` (`template_type`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `email_templates`

INSERT INTO `email_templates` (`template_id`, `template_name`, `template_subject`, `template_body`, `template_description`, `template_type`, `is_active`, `created_at`, `updated_at`, `header_title`, `greeting_text`, `main_message`, `button_text`, `button_link`, `fallback_message`, `footer_note`, `custom_logo`, `use_structured_editor`) VALUES
('1', 'user_verification', 'Verify your {{site_name}} account', '\n    <html>\n    <head>\n        <meta charset=\"UTF-8\">\n        <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n        <title>{{site_name}}</title>\n    </head>\n    <body style=\"font-family: Arial, Helvetica, sans-serif; background: #f8f9fa; padding: 0; margin: 0;\">\n        <table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" style=\"max-width: 600px; margin: auto; background: #ffffff; border: 1px solid #e0e0e0; border-radius: 8px; overflow: hidden;\">\n            <tr>\n                <td style=\"text-align: center; padding: 20px 0; background: #0d6efd;\">\n                    <img src=\"http://localhost/egabay//assets/images/egabay-logo.png\" alt=\"{{site_name}} Logo\" style=\"height:60px;\">\n                </td>\n            </tr>\n            <tr>\n                <td style=\"padding: 30px 25px; color: #212529;\">\n                    <h2 style=\"color: #0d6efd; margin-top: 0; margin-bottom: 20px; font-size: 24px;\">Welcome to {{site_name}}!</h2>\n                    <p style=\"font-size: 16px; margin-bottom: 20px; color: #495057;\">Hi {{first_name}},</p>\n                    <div style=\"margin: 25px 0; font-size: 15px; line-height: 1.6; color: #495057;\">Thank you for registering with {{site_name}}. To complete your registration and verify your email address, please click the button below:</div>\n                    \n        <div style=\"text-align: center; margin: 30px 0;\">\n            <a href=\"{{verification_link}}{{reset_link}}{{dashboard_link}}{{action_link}}\" \n               style=\"background-color: #007bff; color: white; padding: 12px 30px; \n                      text-decoration: none; border-radius: 5px; font-weight: bold; \n                      display: inline-block; text-decoration: none;\">Verify My Account</a>\n        </div>\n                    <div style=\"margin: 25px 0; font-size: 14px; color: #6c757d; line-height: 1.5;\">If the button above doesn\'t work, you can also copy and paste this link into your browser: {{verification_link}}</div>\n                </td>\n            </tr>\n            <tr>\n                <td style=\"background: #f1f3f5; color: #6c757d; font-size: 12px; text-align: center; padding: 20px 15px;\">\n                    If you didn\'t create an account with {{site_name}}, you can safely ignore this email.<br><br>\n                    {{site_name}} • This is an automated message, please do not reply.\n                </td>\n            </tr>\n        </table>\n    </body>\n    </html>', 'Email template for new user registration verification', 'system', '1', '2025-08-03 23:25:17', '2025-08-04 00:59:25', 'Welcome to E-GABAY ASC!', 'Hi {{first_name}},', 'Thank you for registering with E-GABAY ASC. To complete your registration and verify your email address, please click the button below:', 'Verify My Account', NULL, 'If the button above doesn\'t work, you can also copy and paste this link into your browser: {{verification_link}}', 'If you didn\'t create an account with E-GABAY ASC, you can safely ignore this email.', NULL, '1'),
('2', 'password_reset', 'Reset your {{site_name}} password', '\n    <html>\n    <head>\n        <meta charset=\"UTF-8\">\n        <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n        <title>{{site_name}}</title>\n    </head>\n    <body style=\"font-family: Arial, Helvetica, sans-serif; background: #f8f9fa; padding: 0; margin: 0;\">\n        <table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" style=\"max-width: 600px; margin: auto; background: #ffffff; border: 1px solid #e0e0e0; border-radius: 8px; overflow: hidden;\">\n            <tr>\n                <td style=\"text-align: center; padding: 20px 0; background: #0d6efd;\">\n                    <img src=\"http://localhost/egabay//assets/images/egabay-logo.png\" alt=\"{{site_name}} Logo\" style=\"height:60px;\">\n                </td>\n            </tr>\n            <tr>\n                <td style=\"padding: 30px 25px; color: #212529;\">\n                    <h2 style=\"color: #0d6efd; margin-top: 0; margin-bottom: 20px; font-size: 24px;\">Password Reset Request</h2>\n                    <p style=\"font-size: 16px; margin-bottom: 20px; color: #495057;\">Hello {{first_name}},</p>\n                    <div style=\"margin: 25px 0; font-size: 15px; line-height: 1.6; color: #495057;\">We received a request to reset your password for your {{site_name}} account. If you made this request, please click the button below to reset your password:</div>\n                    \n        <div style=\"text-align: center; margin: 30px 0;\">\n            <a href=\"{{verification_link}}{{reset_link}}{{dashboard_link}}{{action_link}}\" \n               style=\"background-color: #007bff; color: white; padding: 12px 30px; \n                      text-decoration: none; border-radius: 5px; font-weight: bold; \n                      display: inline-block; text-decoration: none;\">Reset Password</a>\n        </div>\n                    <div style=\"margin: 25px 0; font-size: 14px; color: #6c757d; line-height: 1.5;\">If the button doesn\'t work, copy and paste this link: {{reset_link}}</div>\n                </td>\n            </tr>\n            <tr>\n                <td style=\"background: #f1f3f5; color: #6c757d; font-size: 12px; text-align: center; padding: 20px 15px;\">\n                    If you didn\'t request a password reset, please ignore this email. Your password will remain unchanged.<br><br>\n                    {{site_name}} • This is an automated message, please do not reply.\n                </td>\n            </tr>\n        </table>\n    </body>\n    </html>', 'Email template for password reset requests', 'system', '1', '2025-08-03 23:25:17', '2025-08-04 00:49:32', 'Password Reset Request', 'Hello {{first_name}},', 'We received a request to reset your password for your {{site_name}} account. If you made this request, please click the button below to reset your password:', 'Reset Password', NULL, 'If the button doesn\'t work, copy and paste this link: {{reset_link}}', 'If you didn\'t request a password reset, please ignore this email. Your password will remain unchanged.', NULL, '1'),
('3', 'welcome_message', 'Welcome to {{site_name}} - Your Account is Ready!', '\n    <html>\n    <head>\n        <meta charset=\"UTF-8\">\n        <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n        <title>{{site_name}}</title>\n    </head>\n    <body style=\"font-family: Arial, Helvetica, sans-serif; background: #f8f9fa; padding: 0; margin: 0;\">\n        <table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" style=\"max-width: 600px; margin: auto; background: #ffffff; border: 1px solid #e0e0e0; border-radius: 8px; overflow: hidden;\">\n            <tr>\n                <td style=\"text-align: center; padding: 20px 0; background: #0d6efd;\">\n                    <img src=\"http://localhost/egabay//assets/images/egabay-logo.png\" alt=\"{{site_name}} Logo\" style=\"height:60px;\">\n                </td>\n            </tr>\n            <tr>\n                <td style=\"padding: 30px 25px; color: #212529;\">\n                    <h2 style=\"color: #0d6efd; margin-top: 0; margin-bottom: 20px; font-size: 24px;\">Welcome to {{site_name}}!</h2>\n                    <p style=\"font-size: 16px; margin-bottom: 20px; color: #495057;\">Hello {{first_name}},</p>\n                    <div style=\"margin: 25px 0; font-size: 15px; line-height: 1.6; color: #495057;\">Your account has been successfully verified! We\'re excited to have you as part of our community. You can now access all features of your account.</div>\n                    \n        <div style=\"text-align: center; margin: 30px 0;\">\n            <a href=\"{{verification_link}}{{reset_link}}{{dashboard_link}}{{action_link}}\" \n               style=\"background-color: #007bff; color: white; padding: 12px 30px; \n                      text-decoration: none; border-radius: 5px; font-weight: bold; \n                      display: inline-block; text-decoration: none;\">Go to Dashboard</a>\n        </div>\n                    <div style=\"margin: 25px 0; font-size: 14px; color: #6c757d; line-height: 1.5;\">You can access your dashboard by visiting: {{dashboard_link}}</div>\n                </td>\n            </tr>\n            <tr>\n                <td style=\"background: #f1f3f5; color: #6c757d; font-size: 12px; text-align: center; padding: 20px 15px;\">\n                    If you have any questions, feel free to contact us at {{admin_email}}.<br><br>\n                    {{site_name}} • This is an automated message, please do not reply.\n                </td>\n            </tr>\n        </table>\n    </body>\n    </html>', 'Welcome email sent after account verification', 'system', '1', '2025-08-03 23:25:17', '2025-08-04 00:49:53', 'Welcome to {{site_name}}!', 'Hello {{first_name}},', 'Your account has been successfully verified! We\'re excited to have you as part of our community. You can now access all features of your account.', 'Go to Dashboard', NULL, 'You can access your dashboard by visiting: {{dashboard_link}}', 'If you have any questions, feel free to contact us at {{admin_email}}.', NULL, '1'),
('4', 'consultation_notification', 'Consultation Update - {{site_name}}', '<div style=\"max-width: 600px; margin: 0 auto; font-family: Arial, sans-serif;\">\r\n    <div style=\"background: linear-gradient(135deg, #6f42c1 0%, #5a32a3 100%); padding: 30px; text-align: center;\">\r\n        {{logo}}\r\n        <h1 style=\"color: white; margin: 20px 0 0 0; font-size: 28px;\">Consultation Update</h1>\r\n    </div>\r\n    <div style=\"padding: 40px; background: #ffffff;\">\r\n        <h2 style=\"color: #2c3e50; margin-bottom: 20px;\">Hello {{first_name}},</h2>\r\n        <p style=\"font-size: 16px; line-height: 1.6; color: #555;\">{{notification_message}}</p>\r\n        \r\n        <div style=\"background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #6f42c1;\">\r\n            <h3 style=\"color: #2c3e50; margin-top: 0;\">???? Consultation Details</h3>\r\n            <p style=\"margin: 5px 0; color: #555;\"><strong>Date:</strong> {{consultation_date}}</p>\r\n            <p style=\"margin: 5px 0; color: #555;\"><strong>Time:</strong> {{consultation_time}}</p>\r\n            <p style=\"margin: 5px 0; color: #555;\"><strong>Counselor:</strong> {{counselor_name}}</p>\r\n            <p style=\"margin: 5px 0; color: #555;\"><strong>Status:</strong> {{consultation_status}}</p>\r\n        </div>\r\n        \r\n        <div style=\"text-align: center; margin: 30px 0;\">\r\n            <a href=\"{{consultation_link}}\" style=\"background: linear-gradient(135deg, #6f42c1 0%, #5a32a3 100%); color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 16px; display: inline-block; box-shadow: 0 4px 15px rgba(111, 66, 193, 0.3);\">???? View Consultation</a>\r\n        </div>\r\n    </div>\r\n</div>', 'Email template for consultation-related notifications', 'system', '1', '2025-08-03 23:25:17', '2025-08-04 00:16:59', 'Consultation Update', 'Hello {{first_name}},', '{{notification_message}}', 'View Consultation', NULL, 'You can view your consultation details by visiting: {{consultation_link}}', 'For any questions about your consultation, please contact us at {{admin_email}}.', NULL, '1'),
('5', 'general_notification', '{{subject}} - {{site_name}}', '\n    <html>\n    <head>\n        <meta charset=\"UTF-8\">\n        <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n        <title>{{site_name}}</title>\n    </head>\n    <body style=\"font-family: Arial, Helvetica, sans-serif; background: #f8f9fa; padding: 0; margin: 0;\">\n        <table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" style=\"max-width: 600px; margin: auto; background: #ffffff; border: 1px solid #e0e0e0; border-radius: 8px; overflow: hidden;\">\n            <tr>\n                <td style=\"text-align: center; padding: 20px 0; background: #0d6efd;\">\n                    <img src=\"http://localhost/egabay//assets/images/egabay-logo.png\" alt=\"{{site_name}} Logo\" style=\"height:60px;\">\n                </td>\n            </tr>\n            <tr>\n                <td style=\"padding: 30px 25px; color: #212529;\">\n                    <h2 style=\"color: #0d6efd; margin-top: 0; margin-bottom: 20px; font-size: 24px;\">{{subject}}</h2>\n                    <p style=\"font-size: 16px; margin-bottom: 20px; color: #495057;\">Hello {{first_name}},</p>\n                    <div style=\"margin: 25px 0; font-size: 15px; line-height: 1.6; color: #495057;\">{{message_content}}</div>\n                    \n        <div style=\"text-align: center; margin: 30px 0;\">\n            <a href=\"{{verification_link}}{{reset_link}}{{dashboard_link}}{{action_link}}\" \n               style=\"background-color: #007bff; color: white; padding: 12px 30px; \n                      text-decoration: none; border-radius: 5px; font-weight: bold; \n                      display: inline-block; text-decoration: none;\">{{action_text}}</a>\n        </div>\n                    <div style=\"margin: 25px 0; font-size: 14px; color: #6c757d; line-height: 1.5;\">You can also access this by visiting: {{action_link}}</div>\n                </td>\n            </tr>\n            <tr>\n                <td style=\"background: #f1f3f5; color: #6c757d; font-size: 12px; text-align: center; padding: 20px 15px;\">\n                    Thank you for using {{site_name}}. If you have any questions, please contact us.<br><br>\n                    {{site_name}} • This is an automated message, please do not reply.\n                </td>\n            </tr>\n        </table>\n    </body>\n    </html>', 'General purpose email template for notifications', 'system', '0', '2025-08-03 23:25:17', '2025-08-04 00:46:26', '{{subject}}', 'Hello {{first_name}},', '{{message_content}}', '{{action_text}}', NULL, 'You can also access this by visiting: {{action_link}}', 'Thank you for using {{site_name}}. If you have any questions, please contact us.', NULL, '1');

-- --------------------------------------------------------
-- Table structure for table `failed_emails`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `failed_emails`;
CREATE TABLE `failed_emails` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `recipient` varchar(255) NOT NULL,
  `subject` varchar(500) NOT NULL,
  `body` text NOT NULL,
  `retry_count` int(11) DEFAULT 0,
  `status` enum('pending','retrying','failed','sent') DEFAULT 'pending',
  `last_error` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_recipient` (`recipient`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `failed_emails`

-- No data for table `failed_emails`

-- --------------------------------------------------------
-- Table structure for table `feedback`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `feedback`;
CREATE TABLE `feedback` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `consultation_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL,
  `comments` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `feedback_ibfk_1` (`consultation_id`),
  KEY `feedback_ibfk_2` (`student_id`),
  CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`consultation_id`) REFERENCES `consultation_requests` (`id`) ON DELETE CASCADE,
  CONSTRAINT `feedback_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `feedback`

INSERT INTO `feedback` (`id`, `consultation_id`, `student_id`, `rating`, `comments`, `created_at`) VALUES
('14', '31', '24', '5', 'ndnsjs', '2025-08-05 13:16:41');

-- --------------------------------------------------------
-- Table structure for table `login_attempts`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `login_attempts`;
CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `username` varchar(255) DEFAULT NULL,
  `attempted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `success` tinyint(1) DEFAULT 0,
  `user_agent` text DEFAULT NULL,
  `blocked_until` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ip_time` (`ip_address`,`attempted_at`),
  KEY `idx_username_time` (`username`,`attempted_at`),
  KEY `idx_blocked_until` (`blocked_until`)
) ENGINE=InnoDB AUTO_INCREMENT=63 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `login_attempts`

INSERT INTO `login_attempts` (`id`, `ip_address`, `username`, `attempted_at`, `success`, `user_agent`, `blocked_until`) VALUES
('3', '::1', 'admin', '2025-08-02 22:26:05', '1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', NULL),
('4', '::1', 'secure', '2025-08-02 22:32:30', '1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', NULL),
('5', '::1', 'secure', '2025-08-02 22:35:33', '1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', NULL),
('6', '::1', 'keith', '2025-08-02 22:36:11', '1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', NULL),
('7', '::1', 'admin', '2025-08-02 22:36:24', '1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', NULL),
('8', '::1', 'keith', '2025-08-02 22:42:43', '1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', NULL),
('9', '::1', 'admin', '2025-08-02 22:52:13', '1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', NULL),
('10', '192.168.1.15', 'admin', '2025-08-02 23:22:01', '1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Mobile Safari/537.36', NULL),
('11', '192.168.1.15', 'keith', '2025-08-02 23:24:02', '1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Mobile Safari/537.36', NULL),
('12', '192.168.1.15', 'counsil', '2025-08-02 23:30:11', '1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Mobile Safari/537.36', NULL),
('13', '192.168.1.15', 'keith', '2025-08-02 23:32:58', '1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Mobile Safari/537.36', NULL),
('14', '192.168.1.15', 'counsil', '2025-08-02 23:33:30', '1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Mobile Safari/537.36', NULL),
('15', '::1', 'admin', '2025-08-03 08:26:03', '1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', NULL),
('16', '192.168.1.5', 'admin', '2025-08-03 08:29:31', '1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', NULL),
('17', '192.168.1.5', 'counsil', '2025-08-03 08:31:51', '1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', NULL),
('19', '192.168.1.5', 'keith', '2025-08-03 08:36:55', '1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', NULL),
('20', '192.168.1.5', 'admin', '2025-08-03 08:43:22', '1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', NULL),
('21', '192.168.1.15', 'admin', '2025-08-03 08:50:51', '1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Mobile Safari/537.36', NULL),
('22', '192.168.1.5', 'admin', '2025-08-03 09:24:59', '1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', NULL),
('23', '192.168.1.15', 'keith', '2025-08-03 09:27:00', '1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Mobile Safari/537.36', NULL),
('24', '192.168.1.5', 'keith', '2025-08-03 09:37:23', '1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', NULL),
('25', '192.168.1.5', 'admin', '2025-08-03 09:42:26', '1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', NULL),
('26', '192.168.1.15', 'keith', '2025-08-03 09:56:37', '1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Mobile Safari/537.36', NULL),
('27', '192.168.1.15', 'counsil', '2025-08-03 09:57:21', '1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Mobile Safari/537.36', NULL),
('28', '192.168.1.15', 'keith', '2025-08-03 09:58:35', '1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Mobile Safari/537.36', NULL),
('29', '::1', 'keith', '2025-08-03 10:17:16', '1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', NULL),
('30', '192.168.1.5', 'keith', '2025-08-03 10:17:22', '1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', NULL),
('31', '192.168.1.5', 'admin', '2025-08-03 10:39:18', '1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', NULL),
('32', '192.168.1.15', 'admin', '2025-08-03 10:39:37', '1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Mobile Safari/537.36', NULL),
('33', '192.168.1.15', 'admin', '2025-08-03 10:43:30', '1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Mobile Safari/537.36', NULL),
('34', '192.168.1.15', 'admin', '2025-08-03 10:46:42', '1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Mobile Safari/537.36', NULL),
('35', '192.168.1.15', 'keith', '2025-08-03 10:49:28', '1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Mobile Safari/537.36', NULL),
('36', '192.168.1.15', 'admin', '2025-08-03 10:49:47', '1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Mobile Safari/537.36', NULL),
('37', '192.168.1.15', 'counsil2', '2025-08-03 10:51:15', '1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Mobile Safari/537.36', NULL),
('38', '192.168.1.15', 'admin', '2025-08-03 10:53:26', '1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Mobile Safari/537.36', NULL),
('40', '192.168.1.15', 'counsil2', '2025-08-03 10:54:17', '1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Mobile Safari/537.36', NULL),
('41', '192.168.1.5', 'counsil2', '2025-08-03 10:56:53', '1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', NULL),
('42', '192.168.1.5', 'keith', '2025-08-03 10:57:31', '1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', NULL),
('43', '192.168.1.5', 'admin', '2025-08-03 10:58:13', '1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', NULL),
('44', '192.168.1.5', 'admin', '2025-08-03 10:59:07', '1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', NULL),
('45', '192.168.1.5', 'counsil', '2025-08-03 10:59:16', '1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', NULL),
('46', '192.168.1.5', 'admin', '2025-08-03 11:06:29', '1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', NULL),
('47', '192.168.1.15', 'keith', '2025-08-03 11:18:48', '1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Mobile Safari/537.36', NULL),
('48', '192.168.1.5', 'keith', '2025-08-03 11:30:36', '1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', NULL),
('49', '192.168.1.5', 'admin', '2025-08-03 11:35:00', '1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', NULL),
('50', '192.168.1.5', 'keith', '2025-08-03 11:40:31', '1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', NULL),
('51', '::1', 'admin', '2025-08-03 13:34:42', '1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', NULL),
('52', '::1', 'admin', '2025-08-03 13:37:52', '1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', NULL),
('53', '::1', 'admin', '2025-08-03 14:58:56', '1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', NULL),
('54', '::1', 'admin', '2025-08-03 15:14:07', '1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', NULL),
('55', '::1', 'admin', '2025-08-03 15:42:24', '1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', NULL),
('56', '::1', 'admin', '2025-08-03 15:52:32', '1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', NULL),
('57', '::1', 'admin', '2025-08-03 15:56:58', '1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', NULL),
('58', '::1', 'admin', '2025-08-03 15:58:48', '1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', NULL),
('59', '::1', 'admin', '2025-08-03 16:16:01', '1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Mobile Safari/537.36', NULL),
('60', '::1', 'admin', '2025-08-03 16:19:43', '1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', NULL),
('61', '::1', 'admin', '2025-08-03 16:20:50', '1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', NULL),
('62', '192.168.1.15', 'admin', '2025-08-03 16:21:05', '1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Mobile Safari/537.36', NULL);

-- --------------------------------------------------------
-- Table structure for table `notifications`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `type` varchar(50) NOT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `notifications_ibfk_1` (`user_id`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `notifications`

INSERT INTO `notifications` (`id`, `user_id`, `message`, `type`, `reference_id`, `is_read`, `created_at`) VALUES
('5', '23', 'Please provide feedback for your completed consultation session.', 'feedback_request', '27', '0', '2025-08-05 09:46:00');

-- --------------------------------------------------------
-- Table structure for table `password_resets`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `password_resets`;
CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_token` (`token`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `password_resets`

INSERT INTO `password_resets` (`id`, `user_id`, `email`, `token`, `created_at`) VALUES
('4', '45', 'princesshipol14@gmail.com', '499a742724d8c6ff81079b26ca2e62b3aacd87496b7f5fdcb7baaa6aa5f99193', '2025-08-05 21:12:31'),
('6', '50', 'ganibanjack53@gmail.com', 'c1f738dea643d7f345f351324894f556e6b30da27f9cdf18cd45a6119c39cea7', '2025-08-06 12:33:49');

-- --------------------------------------------------------
-- Table structure for table `rate_limits`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `rate_limits`;
CREATE TABLE `rate_limits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `attempt_count` int(11) DEFAULT 1,
  `first_attempt` datetime DEFAULT current_timestamp(),
  `last_attempt` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_ip` (`ip_address`),
  KEY `idx_last_attempt` (`last_attempt`)
) ENGINE=InnoDB AUTO_INCREMENT=57 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `rate_limits`

INSERT INTO `rate_limits` (`id`, `ip_address`, `attempt_count`, `first_attempt`, `last_attempt`) VALUES
('56', '139.135.192.102', '1', '2025-08-06 17:30:04', '2025-08-06 17:30:04');

-- --------------------------------------------------------
-- Table structure for table `roles`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `role_id` int(11) NOT NULL AUTO_INCREMENT,
  `role_name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`role_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `roles`

INSERT INTO `roles` (`role_id`, `role_name`, `description`, `is_active`, `created_at`, `updated_at`) VALUES
('1', 'student', 'Regular student who can request consultations', '1', '2025-07-22 18:09:32', '2025-07-22 18:09:32'),
('2', 'counselor', 'Provides guidance and counseling services', '1', '2025-07-22 18:09:32', '2025-07-22 18:09:32'),
('3', 'admin', 'System administrator with full access', '1', '2025-07-22 18:09:32', '2025-07-22 18:09:32'),
('4', 'staff', 'Support staff with limited access', '1', '2025-07-22 18:09:32', '2025-07-22 18:09:32');

-- --------------------------------------------------------
-- Table structure for table `security_logs`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `security_logs`;
CREATE TABLE `security_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) NOT NULL,
  `action` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `risk_level` enum('LOW','MEDIUM','HIGH','CRITICAL') DEFAULT 'LOW',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_action` (`user_id`,`action`),
  KEY `idx_ip_action` (`ip_address`,`action`),
  KEY `idx_risk_level` (`risk_level`,`created_at`),
  CONSTRAINT `security_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=121 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `security_logs`

INSERT INTO `security_logs` (`id`, `user_id`, `ip_address`, `action`, `details`, `risk_level`, `created_at`) VALUES
('1', NULL, '::1', 'login_failed', 'Username: admin, Attempts: 0', 'MEDIUM', '2025-08-02 22:25:25'),
('2', NULL, '::1', 'login_failed', 'Username: admin, Attempts: 1', 'MEDIUM', '2025-08-02 22:25:28'),
('3', NULL, '::1', 'login_success', 'Username: admin, Attempts: 1', 'LOW', '2025-08-02 22:26:05'),
('4', '1', '::1', 'login_success', 'Successful login', 'LOW', '2025-08-02 22:26:05'),
('5', NULL, '::1', 'login_success', 'Username: secure, Attempts: 1', 'LOW', '2025-08-02 22:32:30'),
('6', NULL, '::1', 'login_success', 'Successful login', 'LOW', '2025-08-02 22:32:30'),
('7', NULL, '::1', 'login_success', 'Username: secure, Attempts: 1', 'LOW', '2025-08-02 22:35:33'),
('8', NULL, '::1', 'login_success', 'Successful login', 'LOW', '2025-08-02 22:35:33'),
('9', NULL, '::1', 'login_success', 'Username: keith, Attempts: 1', 'LOW', '2025-08-02 22:36:11'),
('10', NULL, '::1', 'login_success', 'Successful login', 'LOW', '2025-08-02 22:36:11'),
('11', NULL, '::1', 'login_success', 'Username: admin, Attempts: 1', 'LOW', '2025-08-02 22:36:24'),
('12', '1', '::1', 'login_success', 'Successful login', 'LOW', '2025-08-02 22:36:24'),
('13', NULL, '::1', 'login_success', 'Username: keith, Attempts: 1', 'LOW', '2025-08-02 22:42:43'),
('14', NULL, '::1', 'login_success', 'Successful login', 'LOW', '2025-08-02 22:42:43'),
('15', NULL, '::1', 'login_success', 'Username: admin, Attempts: 1', 'LOW', '2025-08-02 22:52:13'),
('16', '1', '::1', 'login_success', 'Successful login', 'LOW', '2025-08-02 22:52:13'),
('17', NULL, '192.168.1.15', 'login_success', 'Username: admin, Attempts: 1', 'LOW', '2025-08-02 23:22:01'),
('18', '1', '192.168.1.15', 'login_success', 'Successful login', 'LOW', '2025-08-02 23:22:01'),
('19', NULL, '192.168.1.15', 'login_success', 'Username: keith, Attempts: 1', 'LOW', '2025-08-02 23:24:02'),
('20', NULL, '192.168.1.15', 'login_success', 'Successful login', 'LOW', '2025-08-02 23:24:02'),
('21', NULL, '192.168.1.15', 'login_success', 'Username: counsil, Attempts: 1', 'LOW', '2025-08-02 23:30:11'),
('22', NULL, '192.168.1.15', 'login_success', 'Successful login', 'LOW', '2025-08-02 23:30:11'),
('23', NULL, '192.168.1.15', 'login_success', 'Username: keith, Attempts: 1', 'LOW', '2025-08-02 23:32:58'),
('24', NULL, '192.168.1.15', 'login_success', 'Successful login', 'LOW', '2025-08-02 23:32:58'),
('25', NULL, '192.168.1.15', 'login_success', 'Username: counsil, Attempts: 1', 'LOW', '2025-08-02 23:33:30'),
('26', NULL, '192.168.1.15', 'login_success', 'Successful login', 'LOW', '2025-08-02 23:33:30'),
('27', NULL, '::1', 'login_success', 'Username: admin, Attempts: 1', 'LOW', '2025-08-03 08:26:03'),
('28', '1', '::1', 'login_success', 'Successful login', 'LOW', '2025-08-03 08:26:03'),
('29', NULL, '192.168.1.5', 'login_success', 'Username: admin, Attempts: 1', 'LOW', '2025-08-03 08:29:31'),
('30', '1', '192.168.1.5', 'login_success', 'Successful login', 'LOW', '2025-08-03 08:29:31'),
('31', NULL, '192.168.1.5', 'login_success', 'Username: counsil, Attempts: 1', 'LOW', '2025-08-03 08:31:51'),
('32', NULL, '192.168.1.5', 'login_success', 'Successful login', 'LOW', '2025-08-03 08:31:51'),
('33', NULL, '192.168.1.5', 'login_failed', 'Username: asdsa, Attempts: 0', 'MEDIUM', '2025-08-03 08:36:47'),
('34', NULL, '192.168.1.5', 'login_success', 'Username: keith, Attempts: 1', 'LOW', '2025-08-03 08:36:55'),
('35', NULL, '192.168.1.5', 'login_success', 'Successful login', 'LOW', '2025-08-03 08:36:55'),
('36', NULL, '192.168.1.5', 'login_success', 'Username: admin, Attempts: 1', 'LOW', '2025-08-03 08:43:22'),
('37', '1', '192.168.1.5', 'login_success', 'Successful login', 'LOW', '2025-08-03 08:43:22'),
('38', NULL, '192.168.1.15', 'login_success', 'Username: admin, Attempts: 1', 'LOW', '2025-08-03 08:50:51'),
('39', '1', '192.168.1.15', 'login_success', 'Successful login', 'LOW', '2025-08-03 08:50:51'),
('40', NULL, '192.168.1.5', 'login_success', 'Username: admin, Attempts: 1', 'LOW', '2025-08-03 09:24:59'),
('41', '1', '192.168.1.5', 'login_success', 'Successful login', 'LOW', '2025-08-03 09:24:59'),
('42', NULL, '192.168.1.15', 'login_success', 'Username: keith, Attempts: 1', 'LOW', '2025-08-03 09:27:00'),
('43', NULL, '192.168.1.15', 'login_success', 'Successful login', 'LOW', '2025-08-03 09:27:00'),
('44', NULL, '192.168.1.5', 'login_success', 'Username: keith, Attempts: 1', 'LOW', '2025-08-03 09:37:23'),
('45', NULL, '192.168.1.5', 'login_success', 'Successful login', 'LOW', '2025-08-03 09:37:23'),
('46', NULL, '192.168.1.5', 'login_success', 'Username: admin, Attempts: 1', 'LOW', '2025-08-03 09:42:26'),
('47', '1', '192.168.1.5', 'login_success', 'Successful login', 'LOW', '2025-08-03 09:42:26'),
('48', NULL, '192.168.1.15', 'login_success', 'Username: keith, Attempts: 1', 'LOW', '2025-08-03 09:56:37'),
('49', NULL, '192.168.1.15', 'login_success', 'Successful login', 'LOW', '2025-08-03 09:56:37'),
('50', NULL, '192.168.1.15', 'login_success', 'Username: counsil, Attempts: 1', 'LOW', '2025-08-03 09:57:21'),
('51', NULL, '192.168.1.15', 'login_success', 'Successful login', 'LOW', '2025-08-03 09:57:21'),
('52', NULL, '192.168.1.15', 'login_success', 'Username: keith, Attempts: 1', 'LOW', '2025-08-03 09:58:35'),
('53', NULL, '192.168.1.15', 'login_success', 'Successful login', 'LOW', '2025-08-03 09:58:35'),
('54', NULL, '::1', 'login_success', 'Username: keith, Attempts: 1', 'LOW', '2025-08-03 10:17:16'),
('55', NULL, '::1', 'login_success', 'Successful login', 'LOW', '2025-08-03 10:17:16'),
('56', NULL, '192.168.1.5', 'login_success', 'Username: keith, Attempts: 1', 'LOW', '2025-08-03 10:17:22'),
('57', NULL, '192.168.1.5', 'login_success', 'Successful login', 'LOW', '2025-08-03 10:17:23'),
('58', NULL, '192.168.1.5', 'login_success', 'Username: admin, Attempts: 1', 'LOW', '2025-08-03 10:39:18'),
('59', '1', '192.168.1.5', 'login_success', 'Successful login', 'LOW', '2025-08-03 10:39:18'),
('60', NULL, '192.168.1.15', 'login_success', 'Username: admin, Attempts: 1', 'LOW', '2025-08-03 10:39:37'),
('61', '1', '192.168.1.15', 'login_success', 'Successful login', 'LOW', '2025-08-03 10:39:37'),
('62', NULL, '192.168.1.15', 'login_success', 'Username: admin, Attempts: 1', 'LOW', '2025-08-03 10:43:30'),
('63', '1', '192.168.1.15', 'login_success', 'Successful login', 'LOW', '2025-08-03 10:43:30'),
('64', NULL, '192.168.1.15', 'login_success', 'Username: admin, Attempts: 1', 'LOW', '2025-08-03 10:46:42'),
('65', '1', '192.168.1.15', 'login_success', 'Successful login', 'LOW', '2025-08-03 10:46:42'),
('66', NULL, '192.168.1.15', 'login_success', 'Username: keith, Attempts: 1', 'LOW', '2025-08-03 10:49:28'),
('67', NULL, '192.168.1.15', 'login_success', 'Successful login', 'LOW', '2025-08-03 10:49:28'),
('68', NULL, '192.168.1.15', 'login_success', 'Username: admin, Attempts: 1', 'LOW', '2025-08-03 10:49:47'),
('69', '1', '192.168.1.15', 'login_success', 'Successful login', 'LOW', '2025-08-03 10:49:47'),
('70', NULL, '192.168.1.15', 'login_success', 'Username: counsil2, Attempts: 1', 'LOW', '2025-08-03 10:51:15'),
('71', NULL, '192.168.1.15', 'login_success', 'Successful login', 'LOW', '2025-08-03 10:51:15'),
('72', NULL, '192.168.1.15', 'login_success', 'Username: admin, Attempts: 1', 'LOW', '2025-08-03 10:53:26'),
('73', '1', '192.168.1.15', 'login_success', 'Successful login', 'LOW', '2025-08-03 10:53:26'),
('74', NULL, '192.168.1.15', 'login_failed', 'Username: counsil, Attempts: 0', 'MEDIUM', '2025-08-03 10:54:11'),
('75', NULL, '192.168.1.15', 'login_success', 'Username: counsil2, Attempts: 1', 'LOW', '2025-08-03 10:54:17'),
('76', NULL, '192.168.1.15', 'login_success', 'Successful login', 'LOW', '2025-08-03 10:54:17'),
('77', NULL, '192.168.1.5', 'login_success', 'Username: counsil2, Attempts: 1', 'LOW', '2025-08-03 10:56:53'),
('78', NULL, '192.168.1.5', 'login_success', 'Successful login', 'LOW', '2025-08-03 10:56:53'),
('79', NULL, '192.168.1.5', 'login_success', 'Username: keith, Attempts: 1', 'LOW', '2025-08-03 10:57:31'),
('80', NULL, '192.168.1.5', 'login_success', 'Successful login', 'LOW', '2025-08-03 10:57:31'),
('81', NULL, '192.168.1.5', 'login_success', 'Username: admin, Attempts: 1', 'LOW', '2025-08-03 10:58:13'),
('82', '1', '192.168.1.5', 'login_success', 'Successful login', 'LOW', '2025-08-03 10:58:13'),
('83', NULL, '192.168.1.5', 'login_success', 'Username: admin, Attempts: 1', 'LOW', '2025-08-03 10:59:07'),
('84', '1', '192.168.1.5', 'login_success', 'Successful login', 'LOW', '2025-08-03 10:59:07'),
('85', NULL, '192.168.1.5', 'login_success', 'Username: counsil, Attempts: 1', 'LOW', '2025-08-03 10:59:16'),
('86', NULL, '192.168.1.5', 'login_success', 'Successful login', 'LOW', '2025-08-03 10:59:16'),
('87', NULL, '192.168.1.5', 'login_success', 'Username: admin, Attempts: 1', 'LOW', '2025-08-03 11:06:29'),
('88', '1', '192.168.1.5', 'login_success', 'Successful login', 'LOW', '2025-08-03 11:06:29'),
('89', NULL, '192.168.1.15', 'login_success', 'Username: keith, Attempts: 1', 'LOW', '2025-08-03 11:18:48'),
('90', NULL, '192.168.1.15', 'login_success', 'Successful login', 'LOW', '2025-08-03 11:18:48'),
('91', NULL, '192.168.1.5', 'login_success', 'Username: keith, Attempts: 1', 'LOW', '2025-08-03 11:30:36'),
('92', NULL, '192.168.1.5', 'login_success', 'Successful login', 'LOW', '2025-08-03 11:30:36'),
('93', NULL, '192.168.1.5', 'login_success', 'Username: admin, Attempts: 1', 'LOW', '2025-08-03 11:35:00'),
('94', '1', '192.168.1.5', 'login_success', 'Successful login', 'LOW', '2025-08-03 11:35:00'),
('95', NULL, '192.168.1.5', 'login_success', 'Username: keith, Attempts: 1', 'LOW', '2025-08-03 11:40:31'),
('96', NULL, '192.168.1.5', 'login_success', 'Successful login', 'LOW', '2025-08-03 11:40:31'),
('97', NULL, '::1', 'login_success', 'Username: admin, Attempts: 1', 'LOW', '2025-08-03 13:34:42'),
('98', '1', '::1', 'login_success', 'Successful login', 'LOW', '2025-08-03 13:34:42'),
('99', NULL, '::1', 'login_success', 'Username: admin, Attempts: 1', 'LOW', '2025-08-03 13:37:52'),
('100', '1', '::1', 'login_success', 'Successful login', 'LOW', '2025-08-03 13:37:52'),
('101', NULL, '::1', 'login_success', 'Username: admin, Attempts: 1', 'LOW', '2025-08-03 14:58:56'),
('102', '1', '::1', 'login_success', 'Successful login', 'LOW', '2025-08-03 14:58:56'),
('103', NULL, '::1', 'login_success', 'Username: admin, Attempts: 1', 'LOW', '2025-08-03 15:14:07'),
('104', '1', '::1', 'login_success', 'Successful login', 'LOW', '2025-08-03 15:14:07'),
('105', NULL, '::1', 'login_success', 'Username: admin, Attempts: 1', 'LOW', '2025-08-03 15:42:24'),
('106', '1', '::1', 'login_success', 'Successful login', 'LOW', '2025-08-03 15:42:24'),
('107', NULL, '::1', 'login_success', 'Username: admin, Attempts: 1', 'LOW', '2025-08-03 15:52:32'),
('108', '1', '::1', 'login_success', 'Successful login', 'LOW', '2025-08-03 15:52:32'),
('109', NULL, '::1', 'login_success', 'Username: admin, Attempts: 1', 'LOW', '2025-08-03 15:56:58'),
('110', '1', '::1', 'login_success', 'Successful login', 'LOW', '2025-08-03 15:56:58'),
('111', NULL, '::1', 'login_success', 'Username: admin, Attempts: 1', 'LOW', '2025-08-03 15:58:48'),
('112', '1', '::1', 'login_success', 'Successful login', 'LOW', '2025-08-03 15:58:48'),
('113', NULL, '::1', 'login_success', 'Username: admin, Attempts: 1', 'LOW', '2025-08-03 16:16:01'),
('114', '1', '::1', 'login_success', 'Successful login', 'LOW', '2025-08-03 16:16:01'),
('115', NULL, '::1', 'login_success', 'Username: admin, Attempts: 1', 'LOW', '2025-08-03 16:19:43'),
('116', '1', '::1', 'login_success', 'Successful login', 'LOW', '2025-08-03 16:19:43'),
('117', NULL, '::1', 'login_success', 'Username: admin, Attempts: 1', 'LOW', '2025-08-03 16:20:50'),
('118', '1', '::1', 'login_success', 'Successful login', 'LOW', '2025-08-03 16:20:50'),
('119', NULL, '192.168.1.15', 'login_success', 'Username: admin, Attempts: 1', 'LOW', '2025-08-03 16:21:05'),
('120', '1', '192.168.1.15', 'login_success', 'Successful login', 'LOW', '2025-08-03 16:21:05');

-- --------------------------------------------------------
-- Table structure for table `settings`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `setting_id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `value` text NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`setting_id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `settings`

INSERT INTO `settings` (`setting_id`, `setting_key`, `value`, `description`, `is_active`, `created_at`, `updated_at`) VALUES
('1', 'site_title', 'E-GABAY ASC - Academic Support and Counseling System', 'The title of the website', '1', '2025-07-22 18:09:33', '2025-07-22 18:09:33'),
('2', 'site_description', 'A Comprehensive Academic Support and Counseling System', 'The description of the website', '1', '2025-07-22 18:09:33', '2025-07-22 18:09:33'),
('3', 'admin_email', 'admin@egabay.edu', 'The email address for system notifications', '1', '2025-07-22 18:09:33', '2025-07-22 18:09:33'),
('4', 'items_per_page', '10', 'Default number of items to show per page', '1', '2025-07-22 18:09:33', '2025-07-22 18:09:33'),
('5', 'current_academic_year', '2025-2026', 'The current academic year', '1', '2025-07-22 18:09:33', '2025-07-22 18:09:33'),
('6', 'site_name', 'E-GABAY AS', 'Added via settings page', '1', '2025-07-23 05:51:13', '2025-08-02 22:52:24'),
('7', 'maintenance_mode', '0', 'Added via settings page', '1', '2025-07-23 05:51:13', '2025-08-02 22:51:21'),
('8', 'allow_registrations', '1', 'Added via settings page', '1', '2025-07-23 05:51:13', '2025-08-02 22:55:02'),
('9', 'default_role', '1', 'Added via settings page', '1', '2025-07-23 05:51:13', '2025-07-23 05:51:13'),
('10', 'session_timeout', '30', 'Added via settings page', '1', '2025-07-23 05:51:13', '2025-07-23 05:51:13'),
('11', 'max_login_attempts', '3', 'Added via settings page', '1', '2025-07-23 05:51:13', '2025-07-23 05:58:55'),
('12', 'maintenance_message', 'We are currently performing scheduled maintenance. Please check back soon.', 'Added via settings page', '1', '2025-07-23 05:58:45', '2025-07-23 05:58:45'),
('13', 'maintenance_end_time', 'MGA KUPAL KAYO', 'Added via settings page', '1', '2025-07-23 05:58:45', '2025-08-02 22:51:02'),
('14', 'primary_color', '#6791d0', 'Added via settings page', '1', '2025-08-01 23:13:54', '2025-08-01 23:13:54'),
('15', 'secondary_color', '#6c757d', 'Added via settings page', '1', '2025-08-01 23:13:54', '2025-08-01 23:13:54'),
('16', 'logo_url', '', 'Added via settings page', '1', '2025-08-01 23:13:54', '2025-08-01 23:13:54'),
('17', 'favicon_url', '', 'Added via settings page', '1', '2025-08-01 23:13:54', '2025-08-01 23:13:54'),
('18', 'footer_text', '© 2025 E-Gabay ASC. All Rights Reserved.', 'Added via settings page', '1', '2025-08-01 23:13:54', '2025-08-01 23:13:54'),
('19', 'smtp_host', 'smtp.gmail.com', 'Added via settings page', '1', '2025-08-02 16:22:45', '2025-08-02 16:22:45'),
('20', 'smtp_port', '587', 'Added via settings page', '1', '2025-08-02 16:22:45', '2025-08-02 16:22:45'),
('21', 'smtp_username', 'keithniiyoow@gmail.com', 'Added via settings page', '1', '2025-08-02 16:22:45', '2025-08-03 15:18:07'),
('22', 'smtp_encryption', 'tls', 'Added via settings page', '1', '2025-08-02 16:22:45', '2025-08-02 16:22:45'),
('23', 'email_from_name', 'E-GABAY ASC', 'Added via settings page', '1', '2025-08-02 16:22:45', '2025-08-02 16:22:45'),
('24', 'email_from_address', 'keithniiyoow@gmail.com', 'Added via settings page', '1', '2025-08-02 16:22:45', '2025-08-03 15:18:07'),
('25', 'smtp_password', 'pfld ktld heru sjdw', 'Added via settings page', '1', '2025-08-02 16:22:45', '2025-08-03 15:18:07');

-- --------------------------------------------------------
-- Table structure for table `student_profiles`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `student_profiles`;
CREATE TABLE `student_profiles` (
  `profile_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `student_id` varchar(50) DEFAULT NULL,
  `course` varchar(100) DEFAULT NULL,
  `year_level` varchar(20) DEFAULT NULL,
  `section` varchar(50) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`profile_id`),
  UNIQUE KEY `student_id` (`student_id`),
  KEY `student_profiles_ibfk_1` (`user_id`),
  CONSTRAINT `student_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `student_profiles`

-- No data for table `student_profiles`

-- --------------------------------------------------------
-- Table structure for table `system_logs`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `system_logs`;
CREATE TABLE `system_logs` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`log_id`),
  KEY `system_logs_ibfk_1` (`user_id`),
  CONSTRAINT `system_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=791 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `system_logs`

INSERT INTO `system_logs` (`log_id`, `user_id`, `action`, `details`, `ip_address`, `created_at`) VALUES
('1', '1', 'login', 'User logged in', '::1', '2025-07-22 18:27:23'),
('2', '1', 'login', 'User logged in', '::1', '2025-07-22 18:28:24'),
('3', '1', 'logout', 'User logged out', '::1', '2025-07-22 19:40:42'),
('6', '1', 'login', 'User logged in', '::1', '2025-07-22 19:52:47'),
('7', '1', 'logout', 'User logged out', '::1', '2025-07-22 19:53:41'),
('10', '1', 'login', 'User logged in', '::1', '2025-07-22 19:53:57'),
('11', '1', 'logout', 'User logged out', '::1', '2025-07-22 20:12:46'),
('14', '1', 'login', 'User logged in', '::1', '2025-07-22 20:14:41'),
('15', '1', 'logout', 'User logged out', '::1', '2025-07-22 20:16:19'),
('18', '1', 'login', 'User logged in', '::1', '2025-07-22 20:24:00'),
('19', '1', 'logout', 'User logged out', '::1', '2025-07-22 20:25:59'),
('22', '1', 'login', 'User logged in', '::1', '2025-07-22 20:28:28'),
('23', '1', 'logout', 'User logged out', '::1', '2025-07-22 20:29:15'),
('26', '1', 'login', 'User logged in', '::1', '2025-07-22 20:48:14'),
('27', '1', 'logout', 'User logged out', '::1', '2025-07-22 22:49:24'),
('32', '1', 'login', 'User logged in', '::1', '2025-07-22 23:22:28'),
('33', '1', 'logout', 'User logged out', '::1', '2025-07-22 23:22:57'),
('34', '4', 'login', 'User logged in', '::1', '2025-07-22 23:23:00'),
('35', '4', 'logout', 'User logged out', '::1', '2025-07-22 23:40:20'),
('40', '1', 'login', 'User logged in', '::1', '2025-07-22 23:44:17'),
('41', '1', 'logout', 'User logged out', '::1', '2025-07-22 23:49:24'),
('52', '1', 'login', 'User logged in', '::1', '2025-07-23 04:48:49'),
('53', '1', 'logout', 'User logged out', '::1', '2025-07-23 04:54:26'),
('62', '1', 'login', 'User logged in', '::1', '2025-07-23 05:42:00'),
('63', '1', 'logout', 'User logged out', '::1', '2025-07-23 05:51:40'),
('66', '1', 'login', 'User logged in', '::1', '2025-07-23 05:52:01'),
('67', '1', 'logout', 'User logged out', '::1', '2025-07-23 05:58:57'),
('70', '1', 'login', 'User logged in', '::1', '2025-07-23 05:59:57'),
('71', '1', 'system', 'Deleted database backup: backup_2025-07-23_00-00-36.sql', '::1', '2025-07-23 06:00:42'),
('72', '1', 'logout', 'User logged out', '::1', '2025-07-23 06:01:32'),
('75', '1', 'login', 'User logged in', '::1', '2025-07-23 06:01:53'),
('76', '1', 'logout', 'User logged out', '::1', '2025-07-23 06:02:03'),
('81', '1', 'login', 'User logged in', '::1', '2025-07-23 07:47:24'),
('82', '1', 'login', 'User logged in', '::1', '2025-07-23 07:47:57'),
('83', '1', 'logout', 'User logged out', '::1', '2025-07-23 07:50:18'),
('86', '1', 'login', 'User logged in', '::1', '2025-07-23 07:55:00'),
('87', '1', 'login', 'User logged in', '::1', '2025-07-24 22:15:53'),
('88', '1', 'login', 'User logged in', '::1', '2025-07-25 05:14:54'),
('89', '1', 'login', 'User logged in', '::1', '2025-07-28 08:41:15'),
('90', '1', 'login', 'User logged in', '::1', '2025-07-28 08:45:39'),
('91', '1', 'login', 'User logged in', '::1', '2025-07-28 15:53:46'),
('92', '1', 'login', 'User logged in', '::1', '2025-07-28 16:15:40'),
('93', '1', 'logout', 'User logged out', '::1', '2025-07-28 16:24:54'),
('94', '1', 'login', 'User logged in', '::1', '2025-07-28 16:25:06'),
('95', '1', 'login', 'User logged in', '::1', '2025-07-31 17:33:47'),
('98', '1', 'login', 'User logged in', '::1', '2025-07-31 17:39:04'),
('99', '1', 'system', 'Deleted database backup: backup_2025-07-31_12-15-09.sql', '::1', '2025-07-31 18:15:13'),
('100', '1', 'login', 'User logged in', '::1', '2025-08-01 09:51:19'),
('101', '1', 'logout', 'User logged out', '::1', '2025-08-01 09:52:27'),
('102', '1', 'login', 'User logged in', '::1', '2025-08-01 17:35:01'),
('103', '1', 'system', 'Deleted database backup: backup_2025-08-01_11-58-59.sql', '::1', '2025-08-01 17:59:07'),
('104', '1', 'logout', 'User logged out', '::1', '2025-08-01 18:02:05'),
('107', '1', 'login', 'User logged in', '::1', '2025-08-01 18:03:44'),
('108', '1', 'logout', 'User logged out', '::1', '2025-08-01 18:54:22'),
('111', '1', 'login', 'User logged in', '::1', '2025-08-01 19:02:31'),
('112', '1', 'login', 'User logged in', '::1', '2025-08-01 20:05:49'),
('113', '1', 'logout', 'User logged out', '::1', '2025-08-01 20:08:57'),
('114', '1', 'login', 'User logged in', '::1', '2025-08-01 20:09:10'),
('115', '1', 'logout', 'User logged out', '::1', '2025-08-01 20:12:34'),
('118', '1', 'login', 'User logged in', '::1', '2025-08-01 20:13:28'),
('119', '1', 'logout', 'User logged out', '::1', '2025-08-01 20:13:51'),
('122', '1', 'login', 'User logged in', '::1', '2025-08-01 20:15:00'),
('123', '1', 'logout', 'User logged out', '::1', '2025-08-01 20:15:16'),
('140', '1', 'login', 'User logged in', '::1', '2025-08-01 20:41:13'),
('141', '1', 'logout', 'User logged out', '::1', '2025-08-01 20:46:07'),
('144', '1', 'logout', 'User logged out', '::1', '2025-08-01 21:12:45'),
('145', '1', 'login', 'User logged in', '::1', '2025-08-01 21:12:48'),
('146', '1', 'logout', 'User logged out', '::1', '2025-08-01 21:13:49'),
('152', '1', 'login', 'User logged in', '::1', '2025-08-01 21:40:17'),
('155', '1', 'login', 'User logged in', '::1', '2025-08-01 21:54:47'),
('156', '1', 'login', 'User logged in', '::1', '2025-08-01 22:06:24'),
('157', '1', 'logout', 'User logged out', '::1', '2025-08-01 22:17:00'),
('159', '1', 'login', 'User logged in', '::1', '2025-08-01 22:25:02'),
('160', '1', 'logout', 'User logged out', '::1', '2025-08-01 22:25:07'),
('161', '1', 'login', 'User logged in', '::1', '2025-08-01 22:26:27'),
('162', '1', 'logout', 'User logged out', '::1', '2025-08-01 22:33:28'),
('165', '1', 'login', 'User logged in', '::1', '2025-08-01 22:35:55'),
('166', '1', 'logout', 'User logged out', '::1', '2025-08-01 22:40:06'),
('168', '1', 'login', 'User logged in', '::1', '2025-08-01 22:46:11'),
('169', '1', 'logout', 'User logged out', '::1', '2025-08-01 22:46:19'),
('172', '1', 'login', 'User logged in', '::1', '2025-08-01 22:48:04'),
('173', '1', 'logout', 'User logged out', '::1', '2025-08-01 22:48:54'),
('174', '1', 'login', 'User logged in', '::1', '2025-08-01 22:49:19'),
('175', '1', 'login', 'User logged in', '::1', '2025-08-01 22:50:08'),
('176', '1', 'login', 'User logged in', '::1', '2025-08-01 22:51:37'),
('177', '1', 'logout', 'User logged out', '::1', '2025-08-01 22:52:40'),
('178', '1', 'login', 'User logged in', '::1', '2025-08-01 22:53:08'),
('179', '1', 'logout', 'User logged out', '::1', '2025-08-01 22:53:27'),
('182', '1', 'login', 'User logged in', '::1', '2025-08-01 22:59:33'),
('183', '1', 'login', 'User logged in', '::1', '2025-08-01 23:09:04'),
('184', '1', 'system', 'Cleared 0 logs older than 30 days', '::1', '2025-08-01 23:30:11'),
('185', '1', 'logout', 'User logged out', '::1', '2025-08-01 23:31:16'),
('190', '1', 'login', 'User logged in', '::1', '2025-08-01 23:50:11'),
('191', '1', 'login', 'User logged in', '::1', '2025-08-02 00:06:25'),
('192', '1', 'logout', 'User logged out', '::1', '2025-08-02 00:06:32'),
('194', '1', 'login', 'User logged in', '::1', '2025-08-02 07:56:19'),
('195', '1', 'logout', 'User logged out', '::1', '2025-08-02 07:56:48'),
('201', '1', 'login', 'User logged in', '::1', '2025-08-02 08:13:43'),
('202', '1', 'logout', 'User logged out', '::1', '2025-08-02 08:13:51'),
('220', '1', 'login', 'User logged in', '::1', '2025-08-02 09:52:51'),
('221', '1', 'logout', 'User logged out', '::1', '2025-08-02 09:52:54'),
('236', '1', 'login', 'User logged in', '::1', '2025-08-02 10:20:17'),
('237', '1', 'logout', 'User logged out', '::1', '2025-08-02 10:23:28'),
('240', '1', 'login', 'User logged in', '::1', '2025-08-02 10:28:46'),
('241', '1', 'login', 'User logged in', '::1', '2025-08-02 10:47:51'),
('242', '1', 'delete_consultation', 'Deleted consultation #7 for student keith torda. Removed: 18 messages, 1 chat sessions, 0 feedback, 0 notifications.', '::1', '2025-08-02 10:48:55'),
('243', '1', 'logout', 'User logged out', '::1', '2025-08-02 10:59:20'),
('254', '1', 'login', 'User logged in', '::1', '2025-08-02 11:37:12'),
('255', '1', 'logout', 'User logged out', '::1', '2025-08-02 11:37:22'),
('258', '1', 'login', 'User logged in', '::1', '2025-08-02 11:45:07'),
('259', '1', 'delete_consultation', 'Deleted consultation #5 for student keith torda. Removed: 8 messages, 1 chat sessions, 0 feedback, 0 notifications.', '::1', '2025-08-02 12:20:03'),
('260', '1', 'logout', 'User logged out', '::1', '2025-08-02 12:30:58'),
('262', '1', 'logout', 'User logged out', '::1', '2025-08-02 12:46:43'),
('264', '1', 'login', 'User logged in', '::1', '2025-08-02 12:57:01'),
('265', '1', 'login', 'User logged in', '::1', '2025-08-02 13:20:28'),
('266', '1', 'database_update', 'Created system_notifications table', '::1', '2025-08-02 13:20:33'),
('267', '1', 'database_update', 'Added profile_picture column to users table', '::1', '2025-08-02 13:20:56'),
('268', '1', 'send_notification', 'Admin sent a notification to student', '::1', '2025-08-02 13:21:22'),
('269', '1', 'logout', 'User logged out', '::1', '2025-08-02 13:21:25'),
('272', '1', 'login', 'User logged in', '::1', '2025-08-02 13:28:28'),
('273', '1', 'login', 'User logged in', '::1', '2025-08-02 13:35:21'),
('274', '1', 'logout', 'User logged out', '::1', '2025-08-02 13:37:07'),
('277', '1', 'login', 'User logged in', '::1', '2025-08-02 13:39:59'),
('278', '1', 'send_notification', 'Admin sent a notification to all users', '::1', '2025-08-02 13:53:06'),
('279', '1', 'send_notification', 'Admin sent a notification to student', '::1', '2025-08-02 14:45:19'),
('280', '1', 'logout', 'User logged out', '::1', '2025-08-02 14:45:22'),
('285', '1', 'login', 'User logged in', '::1', '2025-08-02 15:17:20'),
('286', '1', 'send_notification', 'Admin sent a notification to all users', '::1', '2025-08-02 15:17:56'),
('287', '1', 'logout', 'User logged out', '::1', '2025-08-02 15:18:06'),
('294', '1', 'login', 'User logged in', '::1', '2025-08-02 15:26:39'),
('295', '1', 'logout', 'User logged out', '::1', '2025-08-02 15:26:57'),
('306', '1', 'login', 'User logged in', '::1', '2025-08-02 15:54:35'),
('307', '1', 'delete_consultation', 'Deleted consultation #8 for student keith torda. Removed: 4 messages, 1 chat sessions, 0 feedback, 0 notifications.', '::1', '2025-08-02 15:55:48'),
('308', '1', 'delete_consultation', 'Deleted consultation #6 for student keith torda. Removed: 19 messages, 1 chat sessions, 0 feedback, 0 notifications.', '::1', '2025-08-02 15:55:54'),
('309', '1', 'delete_consultation', 'Deleted consultation #4 for student keith torda. Removed: 10 messages, 1 chat sessions, 0 feedback, 0 notifications.', '::1', '2025-08-02 15:55:57'),
('310', '1', 'system', 'Deleted database backup: backup_2025-08-02_09-56-10.sql', '::1', '2025-08-02 15:56:14'),
('311', '1', 'system', 'Created database backup: backup_2025-08-02_10-00-02.sql', '::1', '2025-08-02 16:00:02'),
('312', '1', 'system', 'Sent test email to keithniiyoow@gmail.com', '::1', '2025-08-02 16:22:59'),
('313', '1', 'logout', 'User logged out', '::1', '2025-08-02 16:28:48'),
('322', '1', 'login', 'User logged in', '::1', '2025-08-02 16:41:33'),
('323', '1', 'logout', 'User logged out', '::1', '2025-08-02 16:42:20'),
('328', '1', 'login', 'User logged in', '::1', '2025-08-02 16:45:23'),
('329', '1', 'update_user', 'Updated user: dave torda (ID: 0)', '::1', '2025-08-02 16:45:48'),
('330', '1', 'logout', 'User logged out', '::1', '2025-08-02 16:45:53'),
('339', '1', 'login', 'User logged in', '::1', '2025-08-02 16:58:11'),
('340', '1', 'logout', 'User logged out', '::1', '2025-08-02 16:58:54'),
('343', '1', 'login', 'User logged in', '::1', '2025-08-02 17:55:10'),
('344', '1', 'logout', 'User logged out', '::1', '2025-08-02 17:55:54'),
('345', '1', 'login', 'User logged in', '::1', '2025-08-02 17:56:25'),
('346', '1', 'update_user', 'Updated user: richael ulibas (ID: 6)', '::1', '2025-08-02 18:07:45'),
('347', '1', 'logout', 'User logged out', '::1', '2025-08-02 18:13:41'),
('348', '1', 'login', 'User logged in', '::1', '2025-08-02 18:14:02'),
('349', '1', 'logout', 'User logged out', '::1', '2025-08-02 18:14:42'),
('354', '1', 'login', 'User logged in', '::1', '2025-08-02 18:26:20'),
('355', '1', 'send_notification', 'Admin sent a notification to all users', '::1', '2025-08-02 18:27:51'),
('356', '1', 'logout', 'User logged out', '::1', '2025-08-02 18:27:58'),
('359', '1', 'login', 'User logged in', '::1', '2025-08-02 18:28:37'),
('360', '1', 'update_user', 'Updated user: keith torda (ID: 2)', '::1', '2025-08-02 18:29:47'),
('361', '1', 'profile_update', 'User updated their profile picture', '::1', '2025-08-02 18:31:41'),
('362', '1', 'profile_update', 'User updated their basic profile information', '::1', '2025-08-02 18:31:48'),
('363', '1', 'logout', 'User logged out', '::1', '2025-08-02 18:31:58'),
('364', '1', 'login', 'User logged in', '::1', '2025-08-02 18:32:09'),
('365', '1', 'update_user', 'Updated user: keith torda (ID: 2)', '::1', '2025-08-02 18:32:23'),
('366', '1', 'logout', 'User logged out', '::1', '2025-08-02 18:32:28'),
('371', '1', 'login', 'User logged in', '::1', '2025-08-02 18:35:05'),
('372', '1', 'logout', 'User logged out', '::1', '2025-08-02 18:35:55'),
('376', '1', 'login', 'User logged in', '::1', '2025-08-02 18:48:17'),
('379', '1', 'login', 'User logged in', '::1', '2025-08-02 19:05:39'),
('380', '1', 'delete_consultation', 'Deleted consultation #15 for student keith torda. Removed: 6 messages, 1 chat sessions, 1 feedback, 0 notifications.', '::1', '2025-08-02 19:10:16'),
('381', '1', 'logout', 'User logged out', '::1', '2025-08-02 19:11:22'),
('383', '1', 'login', 'User logged in', '::1', '2025-08-02 21:24:48'),
('384', '1', 'logout', 'User logged out', '::1', '2025-08-02 21:35:36'),
('385', '1', 'login', 'User logged in', '::1', '2025-08-02 22:10:02'),
('386', '1', 'logout', 'User logged out', '::1', '2025-08-02 22:10:07'),
('387', '1', 'login', 'User logged in successfully', '::1', '2025-08-02 22:26:05'),
('388', '1', 'update_user', 'Updated user: dave torda (ID: 7)', '::1', '2025-08-02 22:29:47'),
('389', '1', 'update_user', 'Updated user: richael ulibas (ID: 6)', '::1', '2025-08-02 22:29:56'),
('390', '1', 'logout', 'User logged out', '::1', '2025-08-02 22:30:10'),
('397', '1', 'login', 'User logged in successfully', '::1', '2025-08-02 22:36:24'),
('398', '1', 'system', 'Created database backup: backup_2025-08-02_16-42-07.sql', '::1', '2025-08-02 22:42:08'),
('399', '1', 'system', 'Deleted database backup: backup_2025-08-02_10-00-02.sql', '::1', '2025-08-02 22:42:12'),
('402', '1', 'logout', 'User logged out', '::1', '2025-08-02 22:51:39'),
('403', '1', 'login', 'User logged in successfully', '::1', '2025-08-02 22:52:13'),
('404', '1', 'logout', 'User logged out', '::1', '2025-08-02 22:55:21'),
('405', '1', 'login', 'User logged in successfully', '192.168.1.15', '2025-08-02 23:22:01'),
('406', '1', 'logout', 'User logged out', '192.168.1.15', '2025-08-02 23:23:57'),
('414', '1', 'login', 'User logged in successfully', '::1', '2025-08-03 08:26:03'),
('415', '1', 'update_user', 'Updated user: richael ulibas (ID: 6)', '::1', '2025-08-03 08:28:27'),
('416', '1', 'login', 'User logged in successfully', '192.168.1.5', '2025-08-03 08:29:31'),
('417', '1', 'logout', 'User logged out', '192.168.1.5', '2025-08-03 08:31:45'),
('422', '1', 'login', 'User logged in successfully', '192.168.1.5', '2025-08-03 08:43:22'),
('423', '1', 'login', 'User logged in successfully', '192.168.1.15', '2025-08-03 08:50:51'),
('424', '1', 'logout', 'User logged out', '192.168.1.5', '2025-08-03 09:23:51'),
('425', '1', 'login', 'User logged in successfully', '192.168.1.5', '2025-08-03 09:24:59'),
('426', '1', 'logout', 'User logged out', '192.168.1.15', '2025-08-03 09:26:53'),
('428', '1', 'logout', 'User logged out', '192.168.1.5', '2025-08-03 09:37:17'),
('431', '1', 'login', 'User logged in successfully', '192.168.1.5', '2025-08-03 09:42:26'),
('442', '1', 'login', 'User logged in successfully', '192.168.1.5', '2025-08-03 10:39:18'),
('443', '1', 'login', 'User logged in successfully', '192.168.1.15', '2025-08-03 10:39:37'),
('444', '1', 'logout', 'User logged out', '192.168.1.15', '2025-08-03 10:39:56'),
('445', '1', 'login', 'User logged in successfully', '192.168.1.15', '2025-08-03 10:43:30'),
('446', '1', 'login', 'User logged in successfully', '192.168.1.15', '2025-08-03 10:46:42'),
('447', '1', 'delete_consultation', 'Deleted consultation #14 for student dave torda. Removed: 0 messages, 0 chat sessions, 0 feedback, 0 notifications.', '192.168.1.15', '2025-08-03 10:47:10'),
('448', '1', 'delete_consultation', 'Deleted consultation #12 for student keith torda. Removed: 0 messages, 0 chat sessions, 1 feedback, 0 notifications.', '192.168.1.15', '2025-08-03 10:47:17'),
('449', '1', 'delete_consultation', 'Deleted consultation #13 for student dave torda. Removed: 0 messages, 0 chat sessions, 0 feedback, 0 notifications.', '192.168.1.15', '2025-08-03 10:47:21'),
('450', '1', 'delete_consultation', 'Deleted consultation #11 for student keith torda. Removed: 2 messages, 1 chat sessions, 1 feedback, 0 notifications.', '192.168.1.15', '2025-08-03 10:47:29'),
('451', '1', 'delete_consultation', 'Deleted consultation #10 for student keith torda. Removed: 6 messages, 1 chat sessions, 1 feedback, 0 notifications.', '192.168.1.15', '2025-08-03 10:47:32'),
('452', '1', 'update_user', 'Updated user: richael ulibas (ID: 6)', '192.168.1.15', '2025-08-03 10:48:32'),
('453', '1', 'logout', 'User logged out', '192.168.1.15', '2025-08-03 10:49:23'),
('456', '1', 'login', 'User logged in successfully', '192.168.1.15', '2025-08-03 10:49:47'),
('457', '1', 'logout', 'User logged out', '192.168.1.15', '2025-08-03 10:51:08'),
('460', '1', 'login', 'User logged in successfully', '192.168.1.15', '2025-08-03 10:53:26'),
('461', '1', 'update_user', 'Updated user: Kathreeza Castillo (ID: 3)', '192.168.1.15', '2025-08-03 10:53:57'),
('462', '1', 'logout', 'User logged out', '192.168.1.15', '2025-08-03 10:54:05'),
('464', '1', 'logout', 'User logged out', '192.168.1.5', '2025-08-03 10:56:40'),
('471', '1', 'login', 'User logged in successfully', '192.168.1.5', '2025-08-03 10:58:13'),
('472', '1', 'logout', 'User logged out', '192.168.1.5', '2025-08-03 10:58:42'),
('473', '1', 'login', 'User logged in successfully', '192.168.1.5', '2025-08-03 10:59:07'),
('474', '1', 'logout', 'User logged out', '192.168.1.5', '2025-08-03 10:59:13'),
('477', '1', 'login', 'User logged in successfully', '192.168.1.5', '2025-08-03 11:06:29'),
('478', '1', 'system', 'Cleared 0 logs older than 30 days', '192.168.1.5', '2025-08-03 11:06:46'),
('479', '1', 'system', 'Cleared 0 logs older than 30 days', '192.168.1.5', '2025-08-03 11:06:57'),
('481', '1', 'logout', 'User logged out', '192.168.1.5', '2025-08-03 11:21:00'),
('482', '1', 'logout', 'User logged out', '192.168.1.5', '2025-08-03 11:30:33'),
('487', '1', 'login', 'User logged in successfully', '192.168.1.5', '2025-08-03 11:35:00'),
('488', '1', 'update_user', 'Updated user: keith torda (ID: 2)', '192.168.1.5', '2025-08-03 11:40:23'),
('489', '1', 'logout', 'User logged out', '192.168.1.5', '2025-08-03 11:40:28'),
('492', '1', 'login', 'User logged in successfully', '::1', '2025-08-03 13:34:42'),
('493', '1', 'login', 'User logged in successfully', '::1', '2025-08-03 13:37:52'),
('494', '1', 'login', 'User logged in successfully', '::1', '2025-08-03 14:58:56'),
('495', '1', 'login', 'User logged in successfully', '::1', '2025-08-03 15:14:07'),
('496', '1', 'system', 'Sent test email to keithorario@gmail.com', '::1', '2025-08-03 15:18:23'),
('497', '1', 'logout', 'User logged out', '::1', '2025-08-03 15:19:30'),
('498', '1', 'login', 'User logged in successfully', '::1', '2025-08-03 15:42:24'),
('499', '1', 'logout', 'User logged out', '::1', '2025-08-03 15:46:41'),
('500', '1', 'login', 'User logged in successfully', '::1', '2025-08-03 15:52:32'),
('501', '1', 'login', 'User logged in successfully', '::1', '2025-08-03 15:56:58'),
('502', '1', 'logout', 'User logged out', '::1', '2025-08-03 15:58:06'),
('503', '1', 'login', 'User logged in successfully', '::1', '2025-08-03 15:58:48'),
('504', '1', 'logout', 'User logged out', '::1', '2025-08-03 15:58:53'),
('505', '1', 'login', 'User logged in successfully', '::1', '2025-08-03 16:16:01'),
('506', '1', 'logout', 'User logged out', '::1', '2025-08-03 16:17:13'),
('507', '1', 'login', 'User logged in successfully', '::1', '2025-08-03 16:19:43'),
('508', '1', 'logout', 'User logged out', '::1', '2025-08-03 16:20:13'),
('509', '1', 'login', 'User logged in successfully', '::1', '2025-08-03 16:20:50'),
('510', '1', 'login', 'User logged in successfully', '192.168.1.15', '2025-08-03 16:21:05'),
('511', '1', 'logout', 'User logged out', '192.168.1.15', '2025-08-03 16:21:29'),
('512', '1', 'login', 'User logged in successfully', '::1', '2025-08-03 16:46:38'),
('513', '1', 'update_user', 'Updated user: keith torda (ID: 2)', '::1', '2025-08-03 16:49:21'),
('514', '1', 'update_user', 'Updated user: keit torda (ID: 2)', '::1', '2025-08-03 16:49:29'),
('516', '1', 'login', 'User logged in successfully', '::1', '2025-08-03 17:11:42'),
('517', '1', 'login', 'User logged in successfully', '::1', '2025-08-03 17:18:05'),
('518', '1', 'login', 'User logged in successfully', '::1', '2025-08-03 17:20:52'),
('519', '1', 'login', 'User logged in successfully', '192.168.1.15', '2025-08-03 17:21:18'),
('520', '1', 'login', 'User logged in successfully', '192.168.1.15', '2025-08-03 17:23:03'),
('521', '1', 'update_user', 'Updated user: keith torda (ID: 2)', '192.168.1.15', '2025-08-03 17:23:29'),
('522', '1', 'login', 'User logged in successfully', '::1', '2025-08-03 17:29:49'),
('523', '1', 'login', 'User logged in successfully', '::1', '2025-08-03 17:31:51'),
('527', '1', 'login', 'User logged in successfully', '192.168.1.15', '2025-08-03 18:14:59'),
('528', '1', 'login', 'User logged in successfully', '192.168.1.15', '2025-08-03 18:21:15'),
('529', '1', 'system', 'Created database backup: backup_2025-08-03_12-21-30.sql', '192.168.1.15', '2025-08-03 18:21:31'),
('530', '1', 'login', 'User logged in successfully', '192.168.1.15', '2025-08-03 18:22:35'),
('531', '1', 'system', 'Cleared 0 logs older than 30 days', '192.168.1.15', '2025-08-03 18:22:46'),
('532', '1', 'login', 'User logged in successfully', '::1', '2025-08-03 18:32:33'),
('533', '1', 'send_notification', 'Admin sent a notification to all users', '::1', '2025-08-03 18:44:38'),
('535', '1', 'login', 'User logged in successfully', '192.168.1.15', '2025-08-03 18:51:29'),
('536', NULL, 'failed_login', 'Failed login attempt for username: counsilor', '192.168.1.15', '2025-08-03 18:53:59'),
('540', '1', 'login', 'User logged in successfully', '::1', '2025-08-03 19:34:22'),
('543', '1', 'login', 'User logged in successfully', '::1', '2025-08-03 19:49:59'),
('544', NULL, 'failed_login', 'Failed login attempt for username: keith', '::1', '2025-08-03 19:52:38'),
('545', NULL, 'failed_login', 'Failed login attempt for username: keith', '::1', '2025-08-03 19:52:43'),
('549', '1', 'login', 'User logged in successfully', '::1', '2025-08-03 22:54:49'),
('550', '1', 'login', 'User logged in successfully', '::1', '2025-08-03 23:22:38'),
('552', '1', 'login', 'User logged in successfully', '::1', '2025-08-04 00:45:56'),
('553', '1', 'login', 'User logged in successfully', '192.168.1.15', '2025-08-04 00:47:17'),
('554', '1', 'login', 'User logged in successfully', '::1', '2025-08-04 01:00:36'),
('555', '1', 'login', 'User logged in successfully', '::1', '2025-08-04 01:04:11'),
('560', '1', 'login', 'User logged in successfully', '::1', '2025-08-04 09:13:54'),
('561', '1', 'login', 'User logged in successfully', '::1', '2025-08-04 09:16:37'),
('562', '1', 'login', 'User logged in successfully', '::1', '2025-08-04 09:19:06'),
('563', '1', 'login', 'User logged in successfully', '192.168.100.118', '2025-08-04 09:26:06'),
('564', '1', 'login', 'User logged in successfully', '::1', '2025-08-04 14:25:56'),
('567', NULL, 'failed_login', 'Failed login attempt for username: keith', '::1', '2025-08-04 14:35:51'),
('568', '1', 'login', 'User logged in successfully', '::1', '2025-08-04 14:36:01'),
('569', '1', 'update_user', 'Updated user: keith torda (ID: 2)', '::1', '2025-08-04 14:36:13'),
('570', '1', 'login', 'User logged in successfully', '::1', '2025-08-04 14:36:23'),
('575', '1', 'login', 'User logged in successfully', '::1', '2025-08-04 21:13:30'),
('576', '1', 'login', 'User logged in successfully', '192.168.1.45', '2025-08-04 21:26:24'),
('577', NULL, 'failed_login', 'Failed login attempt for username: admin', '2001:4452:186:7900:c0a9:4fd7:a2c9:a2dd', '2025-08-05 00:05:57'),
('578', '1', 'login', 'User logged in successfully', '2001:4452:186:7900:c0a9:4fd7:a2c9:a2dd', '2025-08-05 00:06:01'),
('579', '1', 'login', 'User logged in successfully', '2001:4452:186:7900:c0a9:4fd7:a2c9:a2dd', '2025-08-05 00:09:31'),
('580', '1', 'login', 'User logged in successfully', '2001:4452:186:7900:c0a9:4fd7:a2c9:a2dd', '2025-08-05 00:18:23'),
('589', '1', 'login', 'User logged in successfully', '2001:4452:186:7900:c0a9:4fd7:a2c9:a2dd', '2025-08-05 00:48:17'),
('590', '1', 'login', 'User logged in successfully', '2001:4452:186:7900:c0a9:4fd7:a2c9:a2dd', '2025-08-05 00:53:30'),
('595', '1', 'login', 'User logged in successfully', '2001:4452:186:7900:c0a9:4fd7:a2c9:a2dd', '2025-08-05 01:39:35'),
('597', '1', 'login', 'User logged in successfully', '2001:4452:186:7900:c0a9:4fd7:a2c9:a2dd', '2025-08-05 01:43:55'),
('598', '1', 'login', 'User logged in successfully', '2001:4452:186:7900:c0a9:4fd7:a2c9:a2dd', '2025-08-05 01:48:25'),
('603', '1', 'login', 'User logged in successfully', '2001:fd8:60d:8222:1:1:df8:30c2', '2025-08-05 05:56:02'),
('604', '1', 'send_notification', 'Admin sent a notification to all users', '2001:fd8:60d:8222:1:1:df8:30c2', '2025-08-05 05:56:33'),
('605', NULL, 'failed_login', 'Failed login attempt for username: student', '2001:fd8:60d:8222:1:1:df8:30c2', '2025-08-05 05:57:08'),
('609', '1', 'login', 'User logged in successfully', '2001:4452:186:7900:2162:a5b5:c35f:6d47', '2025-08-05 06:30:13'),
('610', '1', 'delete_consultation', 'Deleted consultation #24 for student mail temp. Removed: 3 messages, 1 chat sessions, 0 feedback, 0 notifications.', '2001:4452:186:7900:2162:a5b5:c35f:6d47', '2025-08-05 06:34:50'),
('611', '1', 'delete_consultation', 'Deleted consultation #23 for student mail temp. Removed: 5 messages, 1 chat sessions, 0 feedback, 0 notifications.', '2001:4452:186:7900:2162:a5b5:c35f:6d47', '2025-08-05 06:34:54'),
('612', '1', 'delete_consultation', 'Deleted consultation #22 for student keith torda. Removed: 0 messages, 0 chat sessions, 0 feedback, 0 notifications.', '2001:4452:186:7900:2162:a5b5:c35f:6d47', '2025-08-05 06:34:57'),
('613', '1', 'delete_consultation', 'Deleted consultation #21 for student keith torda. Removed: 3 messages, 1 chat sessions, 1 feedback, 0 notifications.', '2001:4452:186:7900:2162:a5b5:c35f:6d47', '2025-08-05 06:35:01'),
('614', '1', 'delete_consultation', 'Deleted consultation #20 for student keith torda. Removed: 2 messages, 1 chat sessions, 0 feedback, 0 notifications.', '2001:4452:186:7900:2162:a5b5:c35f:6d47', '2025-08-05 06:35:04'),
('615', '1', 'delete_consultation', 'Deleted consultation #19 for student keith torda. Removed: 4 messages, 1 chat sessions, 1 feedback, 0 notifications.', '2001:4452:186:7900:2162:a5b5:c35f:6d47', '2025-08-05 06:35:08'),
('616', '1', 'delete_consultation', 'Deleted consultation #18 for student keith torda. Removed: 5 messages, 1 chat sessions, 0 feedback, 0 notifications.', '2001:4452:186:7900:2162:a5b5:c35f:6d47', '2025-08-05 06:35:11'),
('617', '1', 'delete_consultation', 'Deleted consultation #16 for student keith torda. Removed: 6 messages, 1 chat sessions, 1 feedback, 0 notifications.', '2001:4452:186:7900:2162:a5b5:c35f:6d47', '2025-08-05 06:35:14'),
('619', '1', 'login', 'User logged in successfully', '2001:4452:186:7900:2162:a5b5:c35f:6d47', '2025-08-05 06:36:44'),
('620', '1', 'login', 'User logged in successfully', '2001:4452:186:7900:2162:a5b5:c35f:6d47', '2025-08-05 06:40:43'),
('621', '1', 'login', 'User logged in successfully', '2405:8d40:4c05:1585:1858:86fe:c95a:8240', '2025-08-05 06:46:36'),
('623', '1', 'login', 'User logged in successfully', '2001:4452:186:7900:c8ed:545b:98d7:c248', '2025-08-05 06:48:47'),
('624', NULL, 'failed_login', 'Failed login attempt for username: antalay18@gmail.com', '2405:8d40:4c05:1585:1858:86fe:c95a:8240', '2025-08-05 06:48:48'),
('625', NULL, 'failed_login', 'Failed login attempt for username: antalay18@gmail.com', '2405:8d40:4c05:1585:1858:86fe:c95a:8240', '2025-08-05 06:49:07'),
('626', NULL, 'failed_login', 'Failed login attempt for username: antalay18@gmail.com', '2405:8d40:4c05:1585:1858:86fe:c95a:8240', '2025-08-05 06:50:34'),
('627', NULL, 'failed_login', 'Failed login attempt for username: antalay18@gmail.com', '2405:8d40:4c05:1585:1858:86fe:c95a:8240', '2025-08-05 06:50:54'),
('628', '1', 'login', 'User logged in successfully', '2001:4452:186:7900:c8ed:545b:98d7:c248', '2025-08-05 07:04:48'),
('629', '23', 'login', 'User logged in successfully', '2001:fd8:1793:92a2:1858:19fb:cbe7:8519', '2025-08-05 07:10:35'),
('631', '1', 'login', 'User logged in successfully', '2001:4452:186:7900:c8ed:545b:98d7:c248', '2025-08-05 07:14:56'),
('633', '24', 'login', 'User logged in successfully', '2001:4452:186:7900:2162:a5b5:c35f:6d47', '2025-08-05 07:27:41'),
('636', NULL, 'failed_login', 'Failed login attempt for username: keith', '2001:4452:186:7900:c8ed:545b:98d7:c248', '2025-08-05 07:44:19'),
('637', '24', 'login', 'User logged in successfully', '2001:4452:186:7900:c8ed:545b:98d7:c248', '2025-08-05 07:44:26'),
('638', NULL, 'failed_login', 'Failed login attempt for username: keith', '2001:4452:186:7900:c8ed:545b:98d7:c248', '2025-08-05 07:58:16'),
('639', '24', 'login', 'User logged in successfully', '2001:4452:186:7900:c8ed:545b:98d7:c248', '2025-08-05 07:58:21'),
('640', '1', 'login', 'User logged in successfully', '2001:4452:186:7900:c8ed:545b:98d7:c248', '2025-08-05 08:02:27'),
('641', '1', 'login', 'User logged in successfully', '2001:fd8:60d:8222:1:1:df8:30c2', '2025-08-05 08:05:31'),
('642', '24', 'login', 'User logged in successfully', '2001:fd8:60d:8222:1:1:df8:30c2', '2025-08-05 08:06:01'),
('644', '24', 'login', 'User logged in successfully', '2001:fd8:60d:8222:1:1:df8:30c2', '2025-08-05 08:07:19'),
('646', '24', 'login', 'User logged in successfully', '2001:fd8:60d:8222:b93c:1985:10c1:9c02', '2025-08-05 08:12:22'),
('647', '1', 'login', 'User logged in successfully', '2001:fd8:60d:8222:1:1:df8:30c2', '2025-08-05 08:15:12'),
('649', NULL, 'failed_login', 'Failed login attempt for username: keith', '2001:4452:1e0:e00:5c7:876a:dd22:6e6e', '2025-08-05 08:50:37'),
('650', '24', 'login', 'User logged in successfully', '2001:4452:1e0:e00:5c7:876a:dd22:6e6e', '2025-08-05 08:50:45'),
('652', '24', 'login', 'User logged in successfully', '2001:4452:1e0:e00:5c7:876a:dd22:6e6e', '2025-08-05 08:57:55'),
('653', '23', 'login', 'User logged in successfully', '2001:fd8:1793:92a2:1858:19fb:cbe7:8519', '2025-08-05 09:00:18'),
('655', '1', 'login', 'User logged in successfully', '2001:4452:1e0:e00:5c7:876a:dd22:6e6e', '2025-08-05 09:03:24'),
('656', '1', 'login', 'User logged in successfully', '2001:4452:1e0:e00:5c7:876a:dd22:6e6e', '2025-08-05 09:10:09'),
('657', '1', 'system', 'Created database backup: backup_2025-08-05_09-12-33.sql', '2001:4452:1e0:e00:bce8:f273:4e21:1edf', '2025-08-05 09:12:33'),
('658', '1', 'system', 'Deleted database backup: backup_2025-08-03_12-21-30.sql', '2001:4452:1e0:e00:bce8:f273:4e21:1edf', '2025-08-05 09:12:38'),
('659', '24', 'login', 'User logged in successfully', '2001:4452:1e0:e00:bce8:f273:4e21:1edf', '2025-08-05 09:12:56'),
('660', '1', 'login', 'User logged in successfully', '2001:4452:1e0:e00:5c7:876a:dd22:6e6e', '2025-08-05 09:25:59'),
('662', NULL, 'failed_login', 'Failed login attempt for username: keith', '2001:4452:1e0:e00:5c7:876a:dd22:6e6e', '2025-08-05 09:27:37'),
('663', '24', 'login', 'User logged in successfully', '2001:4452:1e0:e00:5c7:876a:dd22:6e6e', '2025-08-05 09:27:41'),
('664', NULL, 'failed_login', 'Failed login attempt for username: counsil', '2001:4452:1e0:e00:5c7:876a:dd22:6e6e', '2025-08-05 09:29:06'),
('666', '24', 'login', 'User logged in successfully', '2001:4452:1e0:e00:bce8:f273:4e21:1edf', '2025-08-05 09:30:58'),
('667', '23', 'login', 'User logged in successfully', '2001:fd8:1793:92a2:1858:19fb:cbe7:8519', '2025-08-05 09:31:46'),
('668', NULL, 'failed_login', 'Failed login attempt for username: espiritujhen028@gmail.com', '122.54.250.90', '2025-08-05 09:34:10'),
('669', NULL, 'failed_login', 'Failed login attempt for username: test2', '2001:4452:1e0:e00:5c7:876a:dd22:6e6e', '2025-08-05 09:40:51'),
('670', '32', 'login', 'User logged in successfully', '122.54.250.90', '2025-08-05 09:41:28'),
('671', '23', 'login', 'User logged in successfully', '2001:fd8:1793:92a2:1858:19fb:cbe7:8519', '2025-08-05 09:43:39'),
('672', '32', 'login', 'User logged in successfully', '122.54.250.90', '2025-08-05 09:44:14'),
('673', NULL, 'failed_login', 'Failed login attempt for username: keith', '2001:4452:1e0:e00:bce8:f273:4e21:1edf', '2025-08-05 09:47:34'),
('674', '24', 'login', 'User logged in successfully', '2001:4452:1e0:e00:bce8:f273:4e21:1edf', '2025-08-05 09:47:40'),
('675', '32', 'login', 'User logged in successfully', '122.54.250.90', '2025-08-05 09:51:02'),
('676', '1', 'login', 'User logged in successfully', '2001:4452:1e0:e00:5c7:876a:dd22:6e6e', '2025-08-05 09:52:48'),
('677', '1', 'login', 'User logged in successfully', '2001:4452:1e0:e00:5c7:876a:dd22:6e6e', '2025-08-05 09:55:17'),
('678', '24', 'login', 'User logged in successfully', '2001:fd8:60d:8222:1:1:df8:30c2', '2025-08-05 10:07:04'),
('679', '24', 'profile_update', 'User updated their profile picture', '2001:fd8:60d:8222:1:1:df8:30c2', '2025-08-05 10:07:33'),
('680', NULL, 'failed_login', 'Failed login attempt for username: admin', '2001:fd8:60d:8222:1:1:df8:30c2', '2025-08-05 10:08:18'),
('681', '1', 'login', 'User logged in successfully', '2001:fd8:60d:8222:1:1:df8:30c2', '2025-08-05 10:08:28'),
('682', '1', 'login', 'User logged in successfully', '2001:fd8:60d:8222:1:1:df8:30c2', '2025-08-05 10:36:48'),
('683', '1', 'login', 'User logged in successfully', '2001:fd8:60d:8222:1:1:df8:30c2', '2025-08-05 10:52:06'),
('684', '23', 'login', 'User logged in successfully', '2001:fd8:1793:92a2:1858:19fb:cbe7:8519', '2025-08-05 10:55:21'),
('686', '1', 'login', 'User logged in successfully', '2001:fd8:60d:8222:1:1:df8:30c2', '2025-08-05 11:08:12'),
('687', '1', 'login', 'User logged in successfully', '2001:fd8:60d:8222:1:1:df8:30c2', '2025-08-05 11:24:01'),
('688', '24', 'login', 'User logged in successfully', '2001:fd8:60d:8222:1:1:df8:30c2', '2025-08-05 11:25:58'),
('689', '1', 'login', 'User logged in successfully', '2001:fd8:60d:8222:1:1:df8:30c2', '2025-08-05 13:04:29'),
('690', '1', 'login', 'User logged in successfully', '2001:fd8:60d:8222:28e5:9616:7d6f:b671', '2025-08-05 13:07:11'),
('691', NULL, 'failed_login', 'Failed login attempt for username: kathreeza_guidance', '2001:fd8:60d:8222:28e5:9616:7d6f:b671', '2025-08-05 13:09:17'),
('692', NULL, 'failed_login', 'Failed login attempt for username: kathreeza_guidance', '2001:fd8:60d:8222:28e5:9616:7d6f:b671', '2025-08-05 13:09:32'),
('693', '38', 'login', 'User logged in successfully', '2001:fd8:60d:8222:28e5:9616:7d6f:b671', '2025-08-05 13:10:01'),
('694', '39', 'login', 'User logged in successfully', '2001:fd8:60d:8222:28e5:9616:7d6f:b671', '2025-08-05 13:12:35'),
('695', '24', 'login', 'User logged in successfully', '2001:fd8:60d:8222:1:1:df8:30c2', '2025-08-05 13:12:57'),
('696', '23', 'login', 'User logged in successfully', '2001:fd8:1793:92a2:1858:19fb:cbe7:8519', '2025-08-05 13:13:10'),
('697', '21', 'login', 'User logged in successfully', '2001:fd8:2280:ae93:1858:c8b6:2c52:3024', '2025-08-05 14:40:14'),
('698', '40', 'login', 'User logged in successfully', '2001:fd8:2280:ae93:eff1:6edb:7cd6:3b14', '2025-08-05 15:58:38'),
('699', NULL, 'failed_login', 'Failed login attempt for username: Dave1606', '2001:fd8:1793:92a2:1858:19fb:cbe7:8519', '2025-08-05 16:22:28'),
('700', '23', 'login', 'User logged in successfully', '2001:fd8:1793:92a2:1858:19fb:cbe7:8519', '2025-08-05 16:22:38'),
('701', '21', 'login', 'User logged in successfully', '2001:4452:1e0:e00:f024:5d18:8442:6403', '2025-08-05 16:27:44'),
('702', '1', 'login', 'User logged in successfully', '216.247.92.89', '2025-08-05 19:49:20'),
('703', '24', 'login', 'User logged in successfully', '2001:4452:1e0:e00:fbfb:406f:b209:fa90', '2025-08-05 20:38:25'),
('704', '1', 'login', 'User logged in successfully', '2001:4452:1e0:e00:fbfb:406f:b209:fa90', '2025-08-05 20:38:54'),
('705', NULL, 'failed_login', 'Failed login attempt for username: Rosana Arellano', '216.247.92.243', '2025-08-05 20:49:53'),
('706', NULL, 'failed_login', 'Failed login attempt for username: Rosana Arellano', '216.247.92.243', '2025-08-05 20:50:05'),
('707', NULL, 'failed_login', 'Failed login attempt for username: Rosana Udarbe Arellano', '216.247.92.243', '2025-08-05 20:50:15'),
('708', NULL, 'failed_login', 'Failed login attempt for username: arellanorosana121@gmail.com', '216.247.92.243', '2025-08-05 20:50:27'),
('709', '1', 'login', 'User logged in successfully', '2001:4452:1e0:e00:fbfb:406f:b209:fa90', '2025-08-05 20:50:55'),
('710', '1', 'update_user', 'Updated user: System Administrator (ID: 1)', '2001:4452:1e0:e00:fbfb:406f:b209:fa90', '2025-08-05 20:51:09'),
('711', NULL, 'failed_login', 'Failed login attempt for username: Qwerty001', '175.176.15.129', '2025-08-05 20:54:47'),
('712', NULL, 'failed_login', 'Failed login attempt for username: Qwerty001', '175.176.15.129', '2025-08-05 20:54:53'),
('713', NULL, 'failed_login', 'Failed login attempt for username: Qwerty001', '175.176.15.129', '2025-08-05 20:54:57'),
('714', NULL, 'failed_login', 'Failed login attempt for username: Qwerty001', '175.176.15.129', '2025-08-05 20:55:06'),
('715', NULL, 'failed_login', 'Failed login attempt for username: Qwerty001', '175.176.15.129', '2025-08-05 20:55:11'),
('716', NULL, 'failed_login', 'Failed login attempt for username: shao123', '175.176.2.109', '2025-08-05 20:55:13'),
('717', NULL, 'failed_login', 'Failed login attempt for username: Qwerty001', '175.176.15.129', '2025-08-05 20:56:01'),
('718', '1', 'login', 'User logged in successfully', '2001:4452:1e0:e00:fbfb:406f:b209:fa90', '2025-08-05 20:56:38'),
('719', '43', 'login', 'User logged in successfully', '175.176.2.109', '2025-08-05 20:57:14'),
('720', '44', 'login', 'User logged in successfully', '86.98.50.129', '2025-08-05 21:04:46'),
('721', NULL, 'failed_login', 'Failed login attempt for username: 2501-L-566', '216.247.89.115', '2025-08-05 21:07:47'),
('722', NULL, 'failed_login', 'Failed login attempt for username: Jane Dumapay', '175.176.2.41', '2025-08-05 21:08:33'),
('723', NULL, 'failed_login', 'Failed login attempt for username: counsil', '2001:4452:1e0:e00:fbfb:406f:b209:fa90', '2025-08-05 21:12:07'),
('724', NULL, 'failed_login', 'Failed login attempt for username: princesshipol14@gmail.com', '216.247.89.115', '2025-08-05 21:12:23'),
('725', NULL, 'failed_login', 'Failed login attempt for username: 250-L-1869', '216.247.89.146', '2025-08-05 21:12:39'),
('726', NULL, 'failed_login', 'Failed login attempt for username: admin', '2001:4452:1e0:e00:fbfb:406f:b209:fa90', '2025-08-05 21:13:27'),
('727', NULL, 'failed_login', 'Failed login attempt for username: admin', '2001:4452:1e0:e00:fbfb:406f:b209:fa90', '2025-08-05 21:13:32'),
('728', NULL, 'failed_login', 'Failed login attempt for username: admin', '2001:4452:1e0:e00:fbfb:406f:b209:fa90', '2025-08-05 21:13:39'),
('729', '1', 'login', 'User logged in successfully', '2001:4452:1e0:e00:fbfb:406f:b209:fa90', '2025-08-05 21:13:47'),
('730', '45', 'login', 'User logged in successfully', '216.247.89.115', '2025-08-05 21:14:37'),
('731', NULL, 'failed_login', 'Failed login attempt for username: Jessie Nangusan', '2001:4452:1e0:e00:28f0:ba6:1834:b5d3', '2025-08-05 21:18:52'),
('732', NULL, 'failed_login', 'Failed login attempt for username: Jessie Nangusan', '2001:4452:1e0:e00:28f0:ba6:1834:b5d3', '2025-08-05 21:19:12'),
('733', NULL, 'failed_login', 'Failed login attempt for username: Jessie Nangusan', '2001:4452:1e0:e00:28f0:ba6:1834:b5d3', '2025-08-05 21:19:47'),
('734', NULL, 'failed_login', 'Failed login attempt for username: Yna', '2405:8d40:4411:1e68:1858:df49:6861:4873', '2025-08-05 21:21:54'),
('735', NULL, 'failed_login', 'Failed login attempt for username: guiangczarina@gmail.com', '2405:8d40:4411:1e68:1858:df49:6861:4873', '2025-08-05 21:22:13'),
('736', '1', 'login', 'User logged in successfully', '2001:4452:1e0:e00:d139:4006:e8:28cd', '2025-08-05 21:23:40'),
('737', NULL, 'failed_login', 'Failed login attempt for username: Shiela Olivas', '110.54.128.183', '2025-08-05 21:30:02'),
('738', NULL, 'failed_login', 'Failed login attempt for username: shielaolivas6@gmail.com', '110.54.128.183', '2025-08-05 21:30:21'),
('739', NULL, 'failed_login', 'Failed login attempt for username: jessienangusan', '2001:4452:1e0:e00:28f0:ba6:1834:b5d3', '2025-08-05 21:32:22'),
('740', NULL, 'failed_login', 'Failed login attempt for username: shielaolivas6@gmail.com', '110.54.128.183', '2025-08-05 21:36:12'),
('741', '46', 'login', 'User logged in successfully', '110.54.128.183', '2025-08-05 21:36:22'),
('742', '1', 'login', 'User logged in successfully', '2001:4452:1e0:e00:fbfb:406f:b209:fa90', '2025-08-05 21:44:55'),
('743', NULL, 'failed_login', 'Failed login attempt for username: Mark Etinu', '175.176.1.46', '2025-08-05 21:46:00'),
('744', NULL, 'failed_login', 'Failed login attempt for username: admin', '2001:4452:1e0:e00:468c:4bbb:5fd3:c0e1', '2025-08-05 22:29:02'),
('745', '24', 'login', 'User logged in successfully', '2001:4452:1e0:e00:468c:4bbb:5fd3:c0e1', '2025-08-05 22:29:09'),
('746', NULL, 'failed_login', 'Failed login attempt for username: admin', '2001:4452:1e0:e00:468c:4bbb:5fd3:c0e1', '2025-08-05 23:19:28'),
('747', '1', 'login', 'User logged in successfully', '2001:4452:1e0:e00:468c:4bbb:5fd3:c0e1', '2025-08-05 23:19:35'),
('748', NULL, 'failed_login', 'Failed login attempt for username: Jupay', '112.198.121.116', '2025-08-06 05:11:42'),
('749', NULL, 'failed_login', 'Failed login attempt for username: jovelynvertudes@gmail.com', '112.198.121.116', '2025-08-06 05:16:43'),
('750', '1', 'login', 'User logged in successfully', '2001:4452:1e0:e00:b530:54bb:a825:cad2', '2025-08-06 06:34:40'),
('752', NULL, 'failed_login', 'Failed login attempt for username: Lawrence', '110.54.130.231', '2025-08-06 06:52:50'),
('753', NULL, 'failed_login', 'Failed login attempt for username: Lawrence', '110.54.130.231', '2025-08-06 06:54:02'),
('754', NULL, 'failed_login', 'Failed login attempt for username: eavrhalmleekingpaligatisaw@gmail.com', '175.176.1.1', '2025-08-06 07:28:11'),
('755', NULL, 'failed_login', 'Failed login attempt for username: eavrhalmleekingpaligatisaw@gmail.com', '175.176.1.1', '2025-08-06 07:30:29'),
('756', NULL, 'failed_login', 'Failed login attempt for username: eavrhalmleekingpaligatisaw@gmail.com', '175.176.1.1', '2025-08-06 07:30:49'),
('757', NULL, 'failed_login', 'Failed login attempt for username: eavrhalmleekingpaligatisaw@gmail.com', '175.176.1.1', '2025-08-06 07:31:12'),
('758', '1', 'login', 'User logged in successfully', '2001:fd8:1783:234b:1859:580:63af:887c', '2025-08-06 08:22:40'),
('759', NULL, 'failed_login', 'Failed login attempt for username: admin', '2001:4452:1e0:e00:b530:54bb:a825:cad2', '2025-08-06 10:55:06'),
('760', '1', 'login', 'User logged in successfully', '2001:4452:1e0:e00:b530:54bb:a825:cad2', '2025-08-06 10:55:12'),
('761', NULL, 'failed_login', 'Failed login attempt for username: bradley12', '122.54.250.90', '2025-08-06 12:31:53'),
('762', NULL, 'failed_login', 'Failed login attempt for username: ganibanjack53@gmail.com', '122.54.250.90', '2025-08-06 12:32:10'),
('763', NULL, 'failed_login', 'Failed login attempt for username: Bradley12', '122.54.250.90', '2025-08-06 12:32:32'),
('764', NULL, 'failed_login', 'Failed login attempt for username: Bradley', '122.54.250.90', '2025-08-06 12:33:07'),
('765', NULL, 'failed_login', 'Failed login attempt for username: Bradley12', '122.54.250.90', '2025-08-06 12:33:11'),
('766', NULL, 'failed_login', 'Failed login attempt for username: bradley12', '122.54.250.90', '2025-08-06 12:33:14'),
('767', NULL, 'failed_login', 'Failed login attempt for username: ganibanjack53@gmail.com', '122.54.250.90', '2025-08-06 12:33:17'),
('768', NULL, 'failed_login', 'Failed login attempt for username: bradley12', '122.54.250.90', '2025-08-06 12:33:20'),
('769', NULL, 'failed_login', 'Failed login attempt for username: Bradley', '122.54.250.90', '2025-08-06 12:33:22'),
('770', NULL, 'failed_login', 'Failed login attempt for username: isaganitabangay@gmail.com', '14.1.64.230', '2025-08-06 12:45:32'),
('771', NULL, 'failed_login', 'Failed login attempt for username: isaganitabangay@gmail.com', '14.1.64.230', '2025-08-06 12:45:55'),
('772', NULL, 'failed_login', 'Failed login attempt for username: isaganitabangay@gmail.com', '14.1.64.230', '2025-08-06 12:46:07'),
('773', '51', 'login', 'User logged in successfully', '14.1.64.230', '2025-08-06 12:46:22'),
('774', '21', 'login', 'User logged in successfully', '2001:4452:1e0:e00:b7d0:3f11:67ae:7ac4', '2025-08-06 15:12:07'),
('775', '1', 'login', 'User logged in successfully', '2001:4452:1e0:e00:b7d0:3f11:67ae:7ac4', '2025-08-06 15:19:03'),
('776', '52', 'login', 'User logged in successfully', '2001:4452:1e0:e00:b7d0:3f11:67ae:7ac4', '2025-08-06 15:23:05'),
('777', NULL, 'failed_login', 'Failed login attempt for username: keith', '2001:4452:1e0:e00:b7d0:3f11:67ae:7ac4', '2025-08-06 15:24:27'),
('778', '24', 'login', 'User logged in successfully', '2001:4452:1e0:e00:b7d0:3f11:67ae:7ac4', '2025-08-06 15:24:35'),
('779', '52', 'login', 'User logged in successfully', '2001:4452:1e0:e00:b7d0:3f11:67ae:7ac4', '2025-08-06 15:24:59'),
('780', '52', 'profile_update', 'User updated their basic profile information', '2001:4452:1e0:e00:b7d0:3f11:67ae:7ac4', '2025-08-06 15:25:42'),
('781', '52', 'profile_update', 'User updated their basic profile information', '2001:4452:1e0:e00:b7d0:3f11:67ae:7ac4', '2025-08-06 15:26:03'),
('782', '24', 'login', 'User logged in successfully', '2001:4452:1e0:e00:b7d0:3f11:67ae:7ac4', '2025-08-06 15:26:21'),
('783', '1', 'login', 'User logged in successfully', '2001:4452:1e0:e00:b7d0:3f11:67ae:7ac4', '2025-08-06 16:08:12'),
('784', NULL, 'failed_login', 'Failed login attempt for username: Louie Barbero', '2001:4452:1e0:e00:a553:37e4:c459:5845', '2025-08-06 16:20:43'),
('785', '53', 'login', 'User logged in successfully', '2001:4452:1e0:e00:a553:37e4:c459:5845', '2025-08-06 16:24:29'),
('786', '1', 'login', 'User logged in successfully', '2001:4452:1e0:e00:b530:54bb:a825:cad2', '2025-08-06 19:42:27'),
('787', '52', 'login', 'User logged in successfully', '2001:4452:1e0:e00:b530:54bb:a825:cad2', '2025-08-06 19:42:57'),
('788', '1', 'login', 'User logged in successfully', '2001:4452:1e0:e00:a919:fd53:50b8:cc17', '2025-08-06 20:28:24'),
('789', '1', 'system', 'Created database backup: backup_2025-08-06_20-28-30.sql', '2001:4452:1e0:e00:a919:fd53:50b8:cc17', '2025-08-06 20:28:30'),
('790', '1', 'login', 'User logged in successfully', '2001:4452:1e0:e00:b530:54bb:a825:cad2', '2025-08-06 22:20:26');

-- --------------------------------------------------------
-- Table structure for table `system_notifications`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `system_notifications`;
CREATE TABLE `system_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `target_role` varchar(50) DEFAULT NULL,
  `message` text NOT NULL,
  `notification_type` varchar(20) DEFAULT 'info',
  `category` varchar(50) DEFAULT 'system',
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `target_role` (`target_role`)
) ENGINE=InnoDB AUTO_INCREMENT=259 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `system_notifications`

INSERT INTO `system_notifications` (`id`, `user_id`, `target_role`, `message`, `notification_type`, `category`, `link`, `is_read`, `read_at`, `created_at`) VALUES
('23', NULL, 'staff', 'Test notification for staff role', 'info', 'system', '#', '0', NULL, '2025-08-02 13:35:59'),
('27', NULL, 'staff', 'hi', 'success', 'announcement', NULL, '0', NULL, '2025-08-02 13:36:08'),
('44', NULL, 'staff', 'Simple notification for all staffs!', 'success', 'system', NULL, '0', NULL, '2025-08-02 13:47:48'),
('48', '4', NULL, 'testing norif', 'info', 'announcement', '', '0', NULL, '2025-08-02 13:53:06'),
('65', NULL, 'staff', 'Test notification for all staffs at 08:18:46', 'info', 'system', NULL, '0', NULL, '2025-08-02 14:18:46'),
('72', '4', NULL, 'PLEASE BE CAREFUL WHEN SENDING YOUR INFORMATIONS', 'warning', 'announcement', '', '0', NULL, '2025-08-02 15:17:56'),
('112', '4', NULL, 'APAYAO STATE COLLEGE is commited', 'info', 'event', '', '0', NULL, '2025-08-02 18:27:51'),
('142', '4', NULL, 'test', 'info', 'system', '', '0', NULL, '2025-08-03 18:44:38'),
('194', '4', NULL, 'test notification', 'success', 'announcement', '', '0', NULL, '2025-08-05 05:56:33'),
('225', '23', NULL, 'Your consultation request has been approved.', 'success', 'consultation', 'https://egabayasc.online//dashboard/student/view_consultation.php?id=27', '0', NULL, '2025-08-05 09:38:18'),
('229', '32', NULL, 'Your consultation request has been approved.', 'success', 'consultation', 'https://egabayasc.online//dashboard/student/view_consultation.php?id=28', '0', NULL, '2025-08-05 09:43:37'),
('231', '23', NULL, 'Your consultation has been marked completed.', 'info', 'consultation', 'https://egabayasc.online//dashboard/student/view_consultation.php?id=27', '0', NULL, '2025-08-05 09:45:28'),
('234', '32', NULL, 'Your consultation has been marked completed.', 'info', 'consultation', 'https://egabayasc.online//dashboard/student/view_consultation.php?id=28', '0', NULL, '2025-08-05 09:51:45'),
('239', '23', NULL, 'Your consultation request has been approved.', 'success', 'consultation', 'https://egabayasc.online//dashboard/student/view_consultation.php?id=29', '0', NULL, '2025-08-05 10:56:26'),
('241', '23', NULL, 'Your consultation has been marked completed.', 'info', 'consultation', 'https://egabayasc.online//dashboard/student/view_consultation.php?id=29', '0', NULL, '2025-08-05 10:58:53'),
('247', '39', NULL, 'A new consultation has been assigned to you.', 'info', 'consultation', 'https://egabayasc.online//dashboard/counselor/view_consultation.php?id=31', '0', NULL, '2025-08-05 13:13:47'),
('249', '39', NULL, 'A new consultation has been assigned to you.', 'info', 'consultation', 'https://egabayasc.online//dashboard/counselor/view_consultation.php?id=32', '0', NULL, '2025-08-05 13:14:00'),
('250', '24', NULL, 'Your consultation request has been approved.', 'success', 'consultation', 'https://egabayasc.online//dashboard/student/view_consultation.php?id=31', '0', NULL, '2025-08-05 13:14:28'),
('251', '39', NULL, 'A new consultation has been assigned to you.', 'info', 'consultation', 'https://egabayasc.online//dashboard/counselor/view_consultation.php?id=31', '0', NULL, '2025-08-05 13:14:28'),
('252', '24', NULL, 'Your consultation has been marked completed.', 'info', 'consultation', 'https://egabayasc.online//dashboard/student/view_consultation.php?id=31', '0', NULL, '2025-08-05 13:16:18'),
('253', '39', NULL, 'Consultation marked completed.', 'info', 'consultation', 'https://egabayasc.online//dashboard/counselor/view_consultation.php?id=31', '0', NULL, '2025-08-05 13:16:18'),
('255', NULL, 'admin', 'A new consultation request has been submitted.', 'info', 'consultation', 'https://egabayasc.online//dashboard/admin/view_consultation.php?id=33', '1', '2025-08-06 19:42:40', '2025-08-06 12:48:45'),
('256', '39', NULL, 'A new consultation has been assigned to you.', 'info', 'consultation', 'https://egabayasc.online//dashboard/counselor/view_consultation.php?id=33', '0', NULL, '2025-08-06 12:48:45'),
('257', '51', NULL, 'Your consultation request has been approved.', 'success', 'consultation', 'https://egabayasc.online//dashboard/student/view_consultation.php?id=33', '0', NULL, '2025-08-06 16:09:49'),
('258', '39', NULL, 'A new consultation has been assigned to you.', 'info', 'consultation', 'https://egabayasc.online//dashboard/counselor/view_consultation.php?id=33', '0', NULL, '2025-08-06 16:09:49');

-- --------------------------------------------------------
-- Table structure for table `system_performance`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `system_performance`;
CREATE TABLE `system_performance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `metric_name` varchar(100) NOT NULL,
  `metric_value` decimal(10,2) NOT NULL,
  `recorded_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_metric_time` (`metric_name`,`recorded_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `system_performance`

-- No data for table `system_performance`

-- --------------------------------------------------------
-- Table structure for table `users`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role_id` int(11) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `profile_picture` varchar(255) DEFAULT NULL,
  `verification_token` varchar(64) DEFAULT NULL,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_users_role` (`role_id`),
  KEY `idx_users_username_fast` (`username`),
  KEY `idx_users_email_fast` (`email`),
  KEY `idx_users_verification_token` (`verification_token`),
  KEY `idx_users_active_verified` (`is_active`,`is_verified`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`)
) ENGINE=InnoDB AUTO_INCREMENT=54 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `users`

INSERT INTO `users` (`user_id`, `username`, `password`, `first_name`, `last_name`, `email`, `role_id`, `is_active`, `last_login`, `created_at`, `updated_at`, `profile_picture`, `verification_token`, `is_verified`) VALUES
('1', 'admin', '$2y$10$uGfREicAB18gMjHLkZjZMeHd.rooIMz9NWhqkoq5HjFDLt1jV.ZwC', 'System', 'Administrator', 'admin@egabay.edu.ph', '3', '1', '2025-08-06 22:20:26', '2025-07-22 18:09:33', '2025-08-06 22:20:26', 'profile_688de90d7ffad.jpg', NULL, '1'),
('4', 'staff', '$2y$10$UYbp1BVhPTZpTeBlk/R9tutIpBAELjWvmSpXYyzalgEhMVC8ROOFm', 'staff', '1', 'staff1@gmail.com', '4', '1', '2025-07-22 23:23:00', '2025-07-22 23:22:55', '2025-07-22 23:23:00', NULL, NULL, '0'),
('21', 'maintenance', '$2y$10$Ew2sRpQ2UQVn9OID356H8OjrgyF96wDypifAzx7h9ZBfkdxoI5bam', 'keith', 'orario', 'keithorario@gmail.com', '3', '1', '2025-08-06 15:12:07', '2025-08-05 06:37:51', '2025-08-06 15:12:07', NULL, NULL, '1'),
('23', 'Dave1606', '$2y$10$0Ls31HpG6XVtpVugftGAj.Uuro5dcCoY.lLGTqGlSwLHwrk5B8rFi', 'Christian dave', 'Ancheta', 'anchetadavechristian16@gmail.com', '1', '1', '2025-08-05 16:22:38', '2025-08-05 07:09:50', '2025-08-05 16:22:38', NULL, NULL, '1'),
('24', 'keith', '$2y$10$caqXwCNzeD.eMqEaFJo9UOJ96WvLgMVlHZwN3m8qVelbpbvFBQ9F2', 'keith', 'torda', 'keithniiyoow@gmail.com', '1', '1', '2025-08-06 15:26:21', '2025-08-05 07:27:21', '2025-08-06 15:26:21', 'profile_68916765a3985.jpg', NULL, '1'),
('32', 'Jenny', '$2y$10$9gW5uQm0obhG/d8qAITA1.HeVDTHCnYkLLOQ6KxXK8NGOwbPUpBaq', 'Jenny Rose', 'Espiritu', 'espiritujhen028@gmail.com', '1', '1', '2025-08-05 09:51:02', '2025-08-05 09:33:28', '2025-08-05 09:51:02', NULL, NULL, '1'),
('38', 'kathreeza_guidance', '$2y$10$tns1m3rSx/uq8if/T9NvjOa/OOM7C5KD6Sa0Mg.rFB4I3tvNMSrTa', 'Kathreeza', 'Ganiban', 'kathbarcellanocastillo@gmail.com', '3', '1', '2025-08-05 13:10:01', '2025-08-05 13:08:55', '2025-08-05 13:10:01', NULL, NULL, '1'),
('39', 'counselor1', '$2y$10$YkYpU6piMG5KLX2RtrPtCeNSY3F2/S70TtVVWBQu7ZmDfkOp9tTNS', 'kathreeza', 'Ganiban', 'admin@gmail.com', '2', '1', '2025-08-05 13:12:35', '2025-08-05 13:12:24', '2025-08-05 13:12:35', NULL, NULL, '1'),
('40', 'Chelly', '$2y$10$LZJv17k0lItcVEc2lAsKq.NVyXh9KLEjFYk.5TahmfvCa4fKDMuIC', 'Richael', 'Ulibas', 'richeleulibas@gmail.com', '1', '1', '2025-08-05 15:58:38', '2025-08-05 15:57:41', '2025-08-05 15:58:38', NULL, NULL, '1'),
('41', 'Norijie', '$2y$10$7p8zLwhN.xfbEybbenAcVOgf8k.qzjlCMsAnxe7.figCZpn9Cr3wu', 'NORIJIE MAE', 'CAIRO', 'norijiemaecairo7@gmail.com', '1', '1', '2025-08-05 18:55:25', '2025-08-05 18:55:11', '2025-08-05 18:55:25', NULL, '6eb13ea52e0ce58db7a892fb0404815f4058632e', '0'),
('43', 'shao123', '$2y$10$TCCJOCtE6Hj5Q3Gb/su9Repzy.83/0IVeQRCp.woHP8JcRaaLhW/G', 'Shaolen John Harry', 'Llacuna', 'shaolenllacuna3@gmail.com', '1', '1', '2025-08-05 20:57:14', '2025-08-05 20:56:03', '2025-08-05 20:57:14', NULL, NULL, '1'),
('44', 'anon.person', '$2y$10$kjqYzsIdEoV1sSqgKpg.Je6bVasiMgnehGKY05GTgmdad/5e9CO4G', 'Anon', 'Person', 'anon.person@yopmail.com', '1', '1', '2025-08-05 21:04:46', '2025-08-05 21:04:12', '2025-08-05 21:04:46', NULL, NULL, '1'),
('45', 'princess14', '$2y$10$JtjOSl8jng5ebGhQiwYOteSjWC01vT77sqcJz1lxVcMoJr6dinhsO', 'Princess Wilma', 'Hipol', 'princesshipol14@gmail.com', '1', '1', '2025-08-05 21:14:37', '2025-08-05 21:11:19', '2025-08-05 21:14:37', NULL, NULL, '1'),
('46', 'Shiela', '$2y$10$cqfLQQi.j/amUgM7c8OSquMmvDITnRDVMFGJA0DNyNXka6fWSGb76', 'Shiela', 'Olivas', 'shielaolivas6@gmail.com', '1', '1', '2025-08-05 21:36:22', '2025-08-05 21:29:34', '2025-08-05 21:36:22', NULL, NULL, '1'),
('47', 'Jupay', '$2y$10$A5N399yd/lSepQtoaF2N8eco9QyQcjPNZ9TMaoaCi4fvMFnGsGs76', 'Jupay', 'Vertuds', 'jovelynvertudes70@gmail.com', '1', '1', '2025-08-06 05:15:51', '2025-08-06 05:15:22', '2025-08-06 05:15:51', NULL, 'df0ccfd13c48b69bb8a0c456d291f08ff03b65be', '0'),
('49', 'Lawrence17', '$2y$10$97N/QuUvKgpZCbbhPopYjO15Nf67a/r8zma1hbTydZ4DhygPH0p8e', 'Alfred Lawrence', 'Canonizado', 'alfrence09@gmail.com', '1', '1', '2025-08-06 06:54:39', '2025-08-06 06:53:32', '2025-08-06 06:54:39', NULL, '2f633e5606093e661dc21717f455d191730cec6e', '0'),
('50', 'Bradley12', '$2y$10$BrwYlEnF6SXlzBqZpYx2IeSO3oEbDA/fIxzFQUhCrEslWdFHmpk12', 'Jack', 'Ganiban', 'ganibanjack53@gmail.com', '1', '1', NULL, '2025-08-06 12:31:30', '2025-08-06 12:32:53', NULL, NULL, '1'),
('51', 'unotabangay', '$2y$10$T9mBsqEGCQRN4miofBwB9.sL77OJ43LE2iME36AXdwH90V8.O8F4G', 'Isagani', 'Tabangay', 'isaganitabangay@gmail.com', '1', '1', '2025-08-06 12:46:22', '2025-08-06 12:43:36', '2025-08-06 12:46:22', NULL, NULL, '1'),
('52', 'developer', '$2y$10$X3TLpHlE5zFThVPdckZp8.REb6s0a3.Di3D40t1HOcISPvR70vSrK', 'System', 'Developer (Click if you have Queries About the System)', 'keith@gmail.com', '2', '1', '2025-08-06 19:42:57', '2025-08-06 15:22:42', '2025-08-06 19:42:57', NULL, NULL, '1'),
('53', 'louiebrb', '$2y$10$HSdzkkEr3A17.bz3jKPHbuMP36WS5JvlvBTYTd5wsqP1EAhVLMgN.', 'Louie', 'Barbero', 'barberolouie40@gmail.com', '1', '1', '2025-08-06 16:24:29', '2025-08-06 16:22:42', '2025-08-06 16:24:29', NULL, NULL, '1');

SET FOREIGN_KEY_CHECKS=1;
COMMIT;
