-- Breakthrough Online Trading Database
-- Created for Ethiopian Birr Trading Platform
-- Database: breakthrough_trading

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- Create database
CREATE DATABASE IF NOT EXISTS `breakthrough_trading` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `breakthrough_trading`;

-- --------------------------------------------------------

-- Table structure for table `invitation_codes`
CREATE TABLE `invitation_codes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(20) NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `used_by` int(11) DEFAULT NULL,
  `max_uses` int(11) DEFAULT 1,
  `current_uses` int(11) DEFAULT 0,
  `expires_at` datetime DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `bonus_amount` decimal(15,2) DEFAULT 0.00,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `used_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `created_by` (`created_by`),
  KEY `used_by` (`used_by`),
  CONSTRAINT `invitation_codes_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `invitation_codes_ibfk_2` FOREIGN KEY (`used_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

-- Table structure for table `users`
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `user_type` enum('user','admin') DEFAULT 'user',
  `trading_level` int(11) DEFAULT NULL,
  `account_balance` decimal(15,2) DEFAULT 0.00,
  `total_invested` decimal(15,2) DEFAULT 0.00,
  `total_profit` decimal(15,2) DEFAULT 0.00,
  `total_commission` decimal(15,2) DEFAULT 0.00,
  `status` enum('pending','active','inactive','suspended') DEFAULT 'pending',
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT 'Addis Ababa',
  `country` varchar(100) DEFAULT 'Ethiopia',
  `invitation_code_used` varchar(20) DEFAULT NULL,
  `referral_code` varchar(20) DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `approved_by` (`approved_by`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

-- Table structure for table `trading_levels`
CREATE TABLE `trading_levels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `level_number` int(11) NOT NULL,
  `level_name` varchar(100) NOT NULL,
  `min_investment` decimal(15,2) NOT NULL,
  `max_investment` decimal(15,2) NOT NULL,
  `expected_return_percentage` decimal(5,2) NOT NULL,
  `duration_days` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `level_number` (`level_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

-- Table structure for table `investments`
CREATE TABLE `investments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `trading_level_id` int(11) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `expected_return` decimal(15,2) NOT NULL,
  `actual_return` decimal(15,2) DEFAULT 0.00,
  `status` enum('pending','active','completed','cancelled') DEFAULT 'pending',
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `completed_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `trading_level_id` (`trading_level_id`),
  CONSTRAINT `investments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `investments_ibfk_2` FOREIGN KEY (`trading_level_id`) REFERENCES `trading_levels` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

-- Table structure for table `transactions`
CREATE TABLE `transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `investment_id` int(11) DEFAULT NULL,
  `transaction_type` enum('deposit','withdrawal','investment','return','profit','commission') NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `currency` varchar(10) DEFAULT 'ETB',
  `payment_method` enum('cbe','wegagen','abyssinia','mastercard','visa','paypal','stripe','telebirr','mpesa','bitcoin','bank_transfer','cash','other') DEFAULT NULL,
  `payment_details` text DEFAULT NULL,
  `commission_rate` decimal(5,2) DEFAULT 0.00,
  `commission_amount` decimal(15,2) DEFAULT 0.00,
  `description` text DEFAULT NULL,
  `status` enum('pending','processing','completed','failed','cancelled') DEFAULT 'pending',
  `reference_number` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `reference_number` (`reference_number`),
  KEY `user_id` (`user_id`),
  KEY `investment_id` (`investment_id`),
  CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `transactions_ibfk_2` FOREIGN KEY (`investment_id`) REFERENCES `investments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

-- Table structure for table `notifications`
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','success','warning','error','investment','withdrawal','profit') DEFAULT 'info',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

-- Table structure for table `contact_messages`
CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `status` enum('new','read','replied','closed') DEFAULT 'new',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

-- Insert sample data

-- Insert trading levels for Ethiopian Birr trading
INSERT INTO `trading_levels` (`level_number`, `level_name`, `min_investment`, `max_investment`, `expected_return_percentage`, `duration_days`, `description`, `is_active`) VALUES
(1, 'Level 1 - Beginner', 1000.00, 3000.00, 15.00, 30, 'Perfect for new Ethiopian Birr traders - Low risk, steady returns with basic trading tools and monthly reports', 1),
(2, 'Level 2 - Intermediate', 10000.00, 30000.00, 25.00, 21, 'Advanced Ethiopian Birr trading - Higher returns for experienced traders with weekly reports and priority support', 1),
(3, 'Level 3 - Elite', 300000.00, 999999999.99, 35.00, 14, 'Premium Ethiopian Birr trading - Maximum returns for serious investors with VIP support and dedicated management', 1);

-- Insert admin user (password: admin123)
INSERT INTO `users` (`email`, `password`, `full_name`, `user_type`, `city`, `country`, `status`, `referral_code`) VALUES
('elias@gmail.com', 'admin123', 'Elias Admin', 'admin', 'Addis Ababa', 'Ethiopia', 'active', 'ADMIN2026');

-- Insert sample user for testing (password: user123)
INSERT INTO `users` (`email`, `password`, `full_name`, `user_type`, `city`, `country`, `status`, `referral_code`) VALUES
('test@example.com', 'user123', 'Test User', 'user', 'Addis Ababa', 'Ethiopia', 'active', 'TEST2026');

-- Insert sample invitation codes
INSERT INTO `invitation_codes` (`code`, `created_by`, `max_uses`, `bonus_amount`, `description`, `is_active`) VALUES
('WELCOME2026', 1, 100, 500.00, 'Welcome bonus for new Ethiopian Birr traders', 1),
('ELITE2026', 1, 50, 1000.00, 'Elite invitation for premium traders', 1),
('FRIEND2026', 1, 25, 250.00, 'Friend referral bonus', 1),
('STARTER2026', 1, 200, 100.00, 'Starter bonus for beginners', 1),
('VIP2026', 1, 10, 2000.00, 'VIP exclusive invitation', 1);

COMMIT;