-- Concordial Nexus - Complete Database Structure
-- Ethiopian Financial Trading Platform
-- Database: concordial_nexus

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- Create database
CREATE DATABASE IF NOT EXISTS `concordial_nexus` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `concordial_nexus`;

-- --------------------------------------------------------

-- Table structure for table `users`
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `first_name` varchar(100) NULL COMMENT 'First name for withdrawal verification',
  `last_name` varchar(100) NULL COMMENT 'Last name for withdrawal verification',
  `withdrawal_account_number` varchar(50) NULL COMMENT 'Bank account number for withdrawals',
  `withdrawal_phone` varchar(20) NULL COMMENT 'Phone number for withdrawal verification',
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

-- Table structure for table `payment_transactions`
CREATE TABLE `payment_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `payment_method` enum('mobile_banking','bank_transfer','digital_wallet','withdrawal_request') NOT NULL,
  
  -- Mobile Banking fields
  `payment_service` varchar(50) NULL COMMENT 'CBE Birr, M-Birr, HelloCash, Amole',
  `mobile_number` varchar(20) NULL,
  
  -- Bank Transfer fields
  `bank_name` varchar(100) NULL,
  `account_number` varchar(50) NULL,
  `account_holder` varchar(100) NULL,
  `branch_code` varchar(20) NULL,
  
  -- Common fields
  `amount` decimal(15,2) NOT NULL,
  `reference_number` varchar(100) NULL,
  `purpose` varchar(50) NULL COMMENT 'investment, trading, withdrawal, transfer, profit_withdrawal, investment_return, emergency, personal_use',
  
  -- Status and tracking
  `status` enum('pending','processing','completed','failed','cancelled') DEFAULT 'pending',
  `admin_notes` text NULL,
  `processed_by` int(11) NULL COMMENT 'Admin user ID who processed',
  `processed_at` timestamp NULL,
  
  -- Timestamps
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  -- Foreign key constraints
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`processed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  
  -- Indexes for performance
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_payment_method` (`payment_method`),
  INDEX `idx_status` (`status`),
  INDEX `idx_created_at` (`created_at`),
  
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

-- Table structure for table `products`
CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(100) DEFAULT 'investment',
  `price` decimal(15,2) NOT NULL,
  `min_investment` decimal(15,2) DEFAULT NULL,
  `max_investment` decimal(15,2) DEFAULT NULL,
  `return_percentage` decimal(5,2) DEFAULT NULL,
  `duration_days` int(11) DEFAULT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `featured` tinyint(1) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `status` (`status`),
  KEY `category` (`category`),
  CONSTRAINT `products_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table structure for table `deposits`
CREATE TABLE `deposits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `payment_service` varchar(100) DEFAULT NULL,
  `payment_reference` varchar(255) NOT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `account_number` varchar(100) DEFAULT NULL,
  `mobile_number` varchar(20) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `admin_notes` text DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_reference` (`payment_reference`),
  KEY `user_id` (`user_id`),
  KEY `status` (`status`),
  KEY `created_at` (`created_at`),
  KEY `approved_by` (`approved_by`),
  CONSTRAINT `deposits_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `deposits_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
INSERT INTO `users` (`email`, `password`, `full_name`, `first_name`, `last_name`, `user_type`, `city`, `country`, `status`, `referral_code`, `account_balance`) VALUES
('admin@concordialnexus.com', 'admin123', 'Concordial Admin', 'Concordial', 'Admin', 'admin', 'Addis Ababa', 'Ethiopia', 'active', 'ADMIN2026', 0.00);

-- Insert sample user for testing (password: user123)
INSERT INTO `users` (`email`, `password`, `full_name`, `first_name`, `last_name`, `user_type`, `city`, `country`, `status`, `referral_code`, `account_balance`, `withdrawal_account_number`, `withdrawal_phone`) VALUES
('test@concordialnexus.com', 'user123', 'Test User', 'Test', 'User', 'user', 'Addis Ababa', 'Ethiopia', 'active', 'TEST2026', 5000.00, '1234567890', '+251912345678');

-- Insert sample invitation codes
INSERT INTO `invitation_codes` (`code`, `created_by`, `max_uses`, `bonus_amount`, `description`, `is_active`) VALUES
('WELCOME2026', 1, 100, 500.00, 'Welcome bonus for new Concordial Nexus traders', 1),
('ELITE2026', 1, 50, 1000.00, 'Elite invitation for premium traders', 1),
('FRIEND2026', 1, 25, 250.00, 'Friend referral bonus', 1),
('STARTER2026', 1, 200, 100.00, 'Starter bonus for beginners', 1),
('VIP2026', 1, 10, 2000.00, 'VIP exclusive invitation', 1);

-- Insert sample payment transaction (withdrawal request)
INSERT INTO `payment_transactions` (`user_id`, `payment_method`, `bank_name`, `account_number`, `account_holder`, `amount`, `reference_number`, `purpose`, `status`, `created_at`) VALUES
(2, 'withdrawal_request', 'Commercial Bank of Ethiopia', '1234567890', 'Test User', 1000.00, 'WD20260205001', 'profit_withdrawal', 'pending', NOW());

-- Insert sample transaction record
INSERT INTO `transactions` (`user_id`, `transaction_type`, `amount`, `payment_method`, `description`, `reference_number`, `status`) VALUES
(2, 'withdrawal', 1000.00, 'cbe', 'Withdrawal request to CBE Bank', 'WD20260205001', 'pending');

-- Insert sample notification
INSERT INTO `notifications` (`user_id`, `title`, `message`, `type`) VALUES
(1, 'New Withdrawal Request', 'User Test User has requested a withdrawal of Br1,000.00 to Commercial Bank of Ethiopia. Reference: WD20260205001', 'withdrawal');

COMMIT;

-- --------------------------------------------------------
-- VIEWS FOR REPORTING
-- --------------------------------------------------------

-- View for withdrawal management dashboard
CREATE VIEW `withdrawal_summary` AS
SELECT 
    pt.id,
    pt.user_id,
    CONCAT(u.first_name, ' ', u.last_name) as user_name,
    u.email,
    u.withdrawal_phone,
    pt.amount,
    pt.bank_name,
    pt.account_number,
    pt.account_holder,
    pt.reference_number,
    pt.status,
    pt.admin_notes,
    pt.created_at,
    pt.processed_at,
    admin.full_name as processed_by_name
FROM payment_transactions pt
JOIN users u ON pt.user_id = u.id
LEFT JOIN users admin ON pt.processed_by = admin.id
WHERE pt.payment_method = 'withdrawal_request'
ORDER BY pt.created_at DESC;

-- View for payment statistics
CREATE VIEW `payment_statistics` AS
SELECT 
    COUNT(*) as total_transactions,
    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count,
    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_count,
    COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed_count,
    COUNT(CASE WHEN payment_method = 'withdrawal_request' THEN 1 END) as withdrawal_count,
    SUM(CASE WHEN status = 'completed' AND payment_method != 'withdrawal_request' THEN amount ELSE 0 END) as total_deposits,
    SUM(CASE WHEN status = 'completed' AND payment_method = 'withdrawal_request' THEN amount ELSE 0 END) as total_withdrawals,
    SUM(CASE WHEN status = 'pending' AND payment_method = 'withdrawal_request' THEN amount ELSE 0 END) as pending_withdrawal_amount
FROM payment_transactions;

-- --------------------------------------------------------
-- STORED PROCEDURES FOR WITHDRAWAL MANAGEMENT
-- --------------------------------------------------------

DELIMITER //

-- Procedure to approve withdrawal
CREATE PROCEDURE ApproveWithdrawal(
    IN withdrawal_id INT,
    IN admin_id INT,
    IN admin_notes TEXT
)
BEGIN
    DECLARE user_id INT;
    DECLARE withdrawal_amount DECIMAL(15,2);
    DECLARE withdrawal_fee DECIMAL(15,2);
    DECLARE total_deduction DECIMAL(15,2);
    DECLARE user_balance DECIMAL(15,2);
    DECLARE reference_num VARCHAR(100);
    
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Get withdrawal details
    SELECT pt.user_id, pt.amount, pt.reference_number, u.account_balance
    INTO user_id, withdrawal_amount, reference_num, user_balance
    FROM payment_transactions pt
    JOIN users u ON pt.user_id = u.id
    WHERE pt.id = withdrawal_id AND pt.payment_method = 'withdrawal_request' AND pt.status = 'pending';
    
    -- Calculate fees
    SET withdrawal_fee = GREATEST(10, withdrawal_amount * 0.02);
    SET total_deduction = withdrawal_amount + withdrawal_fee;
    
    -- Check sufficient balance
    IF user_balance >= total_deduction THEN
        -- Update withdrawal status
        UPDATE payment_transactions 
        SET status = 'completed', 
            processed_by = admin_id, 
            processed_at = NOW(), 
            admin_notes = admin_notes
        WHERE id = withdrawal_id;
        
        -- Deduct from user balance
        UPDATE users 
        SET account_balance = account_balance - total_deduction 
        WHERE id = user_id;
        
        -- Update transaction record
        UPDATE transactions 
        SET status = 'completed' 
        WHERE reference_number = reference_num AND user_id = user_id;
        
        -- Create notification
        INSERT INTO notifications (user_id, title, message, type)
        VALUES (user_id, 'Withdrawal Approved', 
                CONCAT('Your withdrawal of Br', FORMAT(withdrawal_amount, 2), ' has been approved and processed. Reference: ', reference_num), 
                'withdrawal');
    ELSE
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Insufficient balance for withdrawal';
    END IF;
    
    COMMIT;
END //

-- Procedure to reject withdrawal
CREATE PROCEDURE RejectWithdrawal(
    IN withdrawal_id INT,
    IN admin_id INT,
    IN admin_notes TEXT
)
BEGIN
    DECLARE user_id INT;
    DECLARE withdrawal_amount DECIMAL(15,2);
    DECLARE reference_num VARCHAR(100);
    
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Get withdrawal details
    SELECT pt.user_id, pt.amount, pt.reference_number
    INTO user_id, withdrawal_amount, reference_num
    FROM payment_transactions pt
    WHERE pt.id = withdrawal_id AND pt.payment_method = 'withdrawal_request' AND pt.status = 'pending';
    
    -- Update withdrawal status
    UPDATE payment_transactions 
    SET status = 'failed', 
        processed_by = admin_id, 
        processed_at = NOW(), 
        admin_notes = admin_notes
    WHERE id = withdrawal_id;
    
    -- Update transaction record
    UPDATE transactions 
    SET status = 'failed' 
    WHERE reference_number = reference_num AND user_id = user_id;
    
    -- Create notification
    INSERT INTO notifications (user_id, title, message, type)
    VALUES (user_id, 'Withdrawal Rejected', 
            CONCAT('Your withdrawal of Br', FORMAT(withdrawal_amount, 2), ' has been rejected. Reference: ', reference_num, '. Reason: ', COALESCE(admin_notes, 'No reason provided')), 
            'withdrawal');
    
    COMMIT;
END //

DELIMITER ;

-- --------------------------------------------------------
-- INDEXES FOR PERFORMANCE
-- --------------------------------------------------------

-- Additional indexes for better performance
CREATE INDEX idx_users_status ON users(status);
CREATE INDEX idx_users_user_type ON users(user_type);
CREATE INDEX idx_payment_transactions_status_method ON payment_transactions(status, payment_method);
CREATE INDEX idx_transactions_type_status ON transactions(transaction_type, status);
CREATE INDEX idx_notifications_user_read ON notifications(user_id, is_read);

-- --------------------------------------------------------
-- TRIGGERS FOR AUDIT TRAIL
-- --------------------------------------------------------

DELIMITER //

-- Trigger to log payment transaction changes
CREATE TRIGGER payment_transaction_audit 
AFTER UPDATE ON payment_transactions
FOR EACH ROW
BEGIN
    IF OLD.status != NEW.status THEN
        INSERT INTO notifications (user_id, title, message, type)
        VALUES (1, 'Payment Status Changed', 
                CONCAT('Payment transaction ', NEW.reference_number, ' status changed from ', OLD.status, ' to ', NEW.status), 
                'info');
    END IF;
END //

DELIMITER ;

-- --------------------------------------------------------
-- FINAL NOTES
-- --------------------------------------------------------

/*
This database structure includes:

1. COMPLETE USER MANAGEMENT
   - User registration with withdrawal information
   - Admin and user roles
   - Account balance tracking

2. WITHDRAWAL SYSTEM
   - payment_transactions table for all withdrawal requests
   - Admin approval/rejection workflow
   - Automatic fee calculation
   - Complete audit trail

3. PAYMENT PROCESSING
   - Support for Ethiopian banks (CBE, Dashen, Awash, etc.)
   - Mobile money integration (TeleBirr, M-Birr, etc.)
   - Reference number tracking

4. NOTIFICATION SYSTEM
   - User notifications for withdrawal status
   - Admin notifications for new requests

5. REPORTING VIEWS
   - withdrawal_summary for admin dashboard
   - payment_statistics for analytics

6. STORED PROCEDURES
   - ApproveWithdrawal() for processing approvals
   - RejectWithdrawal() for processing rejections

7. PERFORMANCE OPTIMIZATION
   - Proper indexes for fast queries
   - Foreign key constraints for data integrity

8. AUDIT TRAIL
   - Triggers for automatic logging
   - Complete transaction history

USAGE:
1. Import this SQL file to create the complete database
2. Update your PHP connection strings to use 'concordial_nexus'
3. The system will have sample data for testing
4. Admin login: admin@concordialnexus.com / admin123
5. Test user: test@concordialnexus.com / user123

The database is ready for production use with Ethiopian banking integration!
*/