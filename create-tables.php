<?php
try {
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Users table - Core user management
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        full_name VARCHAR(255) NOT NULL,
        user_type ENUM('user', 'admin', 'trader', 'manager') DEFAULT 'user',
        trading_level INT NULL,
        account_balance DECIMAL(15,2) DEFAULT 0.00,
        total_invested DECIMAL(15,2) DEFAULT 0.00,
        total_profit DECIMAL(15,2) DEFAULT 0.00,
        total_withdrawn DECIMAL(15,2) DEFAULT 0.00,
        status ENUM('active', 'inactive', 'suspended', 'pending') DEFAULT 'active',
        email_verified BOOLEAN DEFAULT FALSE,
        phone VARCHAR(20),
        address TEXT,
        city VARCHAR(100) DEFAULT 'Addis Ababa',
        country VARCHAR(100) DEFAULT 'Ethiopia',
        date_of_birth DATE,
        profile_picture VARCHAR(255),
        last_login TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_email (email),
        INDEX idx_user_type (user_type),
        INDEX idx_trading_level (trading_level),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    // Trading levels table
    $pdo->exec("CREATE TABLE IF NOT EXISTS trading_levels (
        id INT AUTO_INCREMENT PRIMARY KEY,
        level_number INT UNIQUE NOT NULL,
        level_name VARCHAR(100) NOT NULL,
        min_investment DECIMAL(15,2) NOT NULL,
        max_investment DECIMAL(15,2) NOT NULL,
        expected_return_percentage DECIMAL(5,2) NOT NULL,
        duration_days INT NOT NULL,
        description TEXT,
        features JSON,
        commission_rate DECIMAL(5,2) DEFAULT 5.00,
        risk_level ENUM('low', 'medium', 'high') DEFAULT 'medium',
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_level_number (level_number),
        INDEX idx_is_active (is_active)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    // Investments table
    $pdo->exec("CREATE TABLE IF NOT EXISTS investments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        trading_level_id INT NOT NULL,
        amount DECIMAL(15,2) NOT NULL,
        expected_return DECIMAL(15,2) NOT NULL,
        actual_return DECIMAL(15,2) DEFAULT 0.00,
        profit_percentage DECIMAL(5,2) DEFAULT 0.00,
        status ENUM('pending', 'active', 'completed', 'cancelled', 'paused') DEFAULT 'pending',
        start_date DATE NOT NULL,
        end_date DATE NOT NULL,
        completed_date DATE NULL,
        auto_reinvest BOOLEAN DEFAULT FALSE,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (trading_level_id) REFERENCES trading_levels(id),
        INDEX idx_user_id (user_id),
        INDEX idx_status (status),
        INDEX idx_dates (start_date, end_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    // Transactions table
    $pdo->exec("CREATE TABLE IF NOT EXISTS transactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        investment_id INT NULL,
        transaction_type ENUM('deposit', 'withdrawal', 'investment', 'return', 'profit', 'commission', 'bonus', 'penalty') NOT NULL,
        amount DECIMAL(15,2) NOT NULL,
        currency VARCHAR(10) DEFAULT 'ETB',
        exchange_rate DECIMAL(10,4) DEFAULT 1.0000,
        description TEXT,
        status ENUM('pending', 'processing', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
        reference_number VARCHAR(100) UNIQUE,
        payment_method VARCHAR(50),
        bank_details JSON,
        processed_by INT NULL,
        processed_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (investment_id) REFERENCES investments(id) ON DELETE SET NULL,
        FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL,
        INDEX idx_user_id (user_id),
        INDEX idx_type (transaction_type),
        INDEX idx_status (status),
        INDEX idx_reference (reference_number)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    // Portfolio performance table
    $pdo->exec("CREATE TABLE IF NOT EXISTS portfolio_performance (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        date DATE NOT NULL,
        total_investment DECIMAL(15,2) DEFAULT 0.00,
        total_returns DECIMAL(15,2) DEFAULT 0.00,
        total_profit DECIMAL(15,2) DEFAULT 0.00,
        portfolio_value DECIMAL(15,2) DEFAULT 0.00,
        roi_percentage DECIMAL(5,2) DEFAULT 0.00,
        active_investments INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_user_date (user_id, date),
        INDEX idx_date (date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    // Market data table
    $pdo->exec("CREATE TABLE IF NOT EXISTS market_data (
        id INT AUTO_INCREMENT PRIMARY KEY,
        date DATE NOT NULL,
        currency_pair VARCHAR(10) NOT NULL DEFAULT 'ETB/USD',
        opening_rate DECIMAL(10,4) NOT NULL,
        closing_rate DECIMAL(10,4) NOT NULL,
        high_rate DECIMAL(10,4) NOT NULL,
        low_rate DECIMAL(10,4) NOT NULL,
        volume DECIMAL(15,2) DEFAULT 0.00,
        market_trend ENUM('bullish', 'bearish', 'stable') DEFAULT 'stable',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_date_pair (date, currency_pair),
        INDEX idx_date (date),
        INDEX idx_currency (currency_pair)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    // Notifications table
    $pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        type ENUM('info', 'success', 'warning', 'error', 'investment', 'withdrawal', 'profit') DEFAULT 'info',
        priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
        is_read BOOLEAN DEFAULT FALSE,
        action_url VARCHAR(500),
        expires_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_user_id (user_id),
        INDEX idx_is_read (is_read),
        INDEX idx_type (type)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    // Contact messages table
    $pdo->exec("CREATE TABLE IF NOT EXISTS contact_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        subject VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        category ENUM('general', 'support', 'investment', 'technical', 'complaint') DEFAULT 'general',
        priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
        status ENUM('new', 'read', 'in_progress', 'replied', 'closed') DEFAULT 'new',
        assigned_to INT NULL,
        replied_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
        INDEX idx_status (status),
        INDEX idx_email (email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    // System settings table
    $pdo->exec("CREATE TABLE IF NOT EXISTS system_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) UNIQUE NOT NULL,
        setting_value TEXT,
        setting_type ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
        category VARCHAR(50) DEFAULT 'general',
        description TEXT,
        is_public BOOLEAN DEFAULT FALSE,
        updated_by INT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
        INDEX idx_category (category),
        INDEX idx_public (is_public)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    // Audit logs table
    $pdo->exec("CREATE TABLE IF NOT EXISTS audit_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NULL,
        action VARCHAR(255) NOT NULL,
        table_name VARCHAR(100),
        record_id INT NULL,
        old_values JSON NULL,
        new_values JSON NULL,
        ip_address VARCHAR(45),
        user_agent TEXT,
        session_id VARCHAR(255),
        severity ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
        INDEX idx_user_id (user_id),
        INDEX idx_action (action),
        INDEX idx_table (table_name),
        INDEX idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    // Password resets table
    $pdo->exec("CREATE TABLE IF NOT EXISTS password_resets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        token VARCHAR(255) NOT NULL,
        expires_at TIMESTAMP NOT NULL,
        used BOOLEAN DEFAULT FALSE,
        ip_address VARCHAR(45),
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_email (email),
        INDEX idx_token (token),
        INDEX idx_expires (expires_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    // Login attempts table
    $pdo->exec("CREATE TABLE IF NOT EXISTS login_attempts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        ip_address VARCHAR(45) NOT NULL,
        success BOOLEAN NOT NULL,
        failure_reason VARCHAR(255),
        user_agent TEXT,
        country VARCHAR(100),
        city VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_email (email),
        INDEX idx_ip (ip_address),
        INDEX idx_success (success),
        INDEX idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    // Trading signals table
    $pdo->exec("CREATE TABLE IF NOT EXISTS trading_signals (
        id INT AUTO_INCREMENT PRIMARY KEY,
        signal_type ENUM('buy', 'sell', 'hold', 'alert') NOT NULL,
        currency_pair VARCHAR(10) NOT NULL DEFAULT 'ETB/USD',
        target_price DECIMAL(10,4),
        current_price DECIMAL(10,4),
        confidence_level DECIMAL(3,2) DEFAULT 0.00,
        description TEXT,
        valid_until TIMESTAMP,
        status ENUM('active', 'expired', 'executed') DEFAULT 'active',
        created_by INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES users(id),
        INDEX idx_type (signal_type),
        INDEX idx_status (status),
        INDEX idx_valid (valid_until)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    $setup_log[] = "✓ All 14 tables created successfully for Ethiopian Birr trading";
    $setup_status['tables'] = true;
    
} catch(PDOException $e) {
    $setup_log[] = "✗ Failed to create tables: " . $e->getMessage();
    $setup_status['tables'] = false;
}
?>