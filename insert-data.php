<?php
try {
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Insert trading levels for Ethiopian Birr trading
    $levels_sql = "INSERT IGNORE INTO trading_levels (level_number, level_name, min_investment, max_investment, expected_return_percentage, duration_days, description, features, commission_rate, risk_level) VALUES
        (1, 'Level 1 - Beginner', 1000.00, 3000.00, 15.00, 30, 'Perfect for new traders starting their Ethiopian Birr trading journey with low risk and steady returns', '[\"Basic trading tools\", \"Monthly reports\", \"Email support\", \"Risk management\", \"Educational resources\"]', 5.00, 'low'),
        (2, 'Level 2 - Intermediate', 10000.00, 30000.00, 25.00, 21, 'Advanced trading package with higher returns for experienced Ethiopian Birr traders', '[\"Advanced analytics\", \"Weekly reports\", \"Priority support\", \"Personal advisor\", \"Market insights\", \"Portfolio optimization\"]', 4.00, 'medium'),
        (3, 'Level 3 - Elite', 300000.00, 999999999.99, 35.00, 14, 'Premium trading service for serious Ethiopian Birr investors with dedicated management and exclusive opportunities', '[\"Custom strategies\", \"Daily insights\", \"VIP support\", \"Dedicated manager\", \"Exclusive opportunities\", \"Real-time alerts\", \"Priority execution\"]', 3.00, 'high')";
    
    $pdo->exec($levels_sql);
    
    // Insert admin user with plain text password as requested
    $admin_password = 'admin123'; // Plain text as requested
    $admin_sql = "INSERT IGNORE INTO users (email, password, full_name, user_type, city, country, status, email_verified) VALUES ('elias@gmail.com', '$admin_password', 'Elias Admin', 'admin', 'Addis Ababa', 'Ethiopia', 'active', 1)";
    $pdo->exec($admin_sql);
    
    // Insert system settings for Ethiopian Birr trading
    $settings_sql = "INSERT IGNORE INTO system_settings (setting_key, setting_value, setting_type, category, description, is_public) VALUES
        ('site_name', 'Breakthrough Online Trading', 'string', 'general', 'Website name', 1),
        ('site_currency', 'Br', 'string', 'general', 'Default currency symbol (Ethiopian Birr)', 1),
        ('site_currency_code', 'ETB', 'string', 'general', 'Currency code for Ethiopian Birr', 1),
        ('min_investment', '1000', 'number', 'trading', 'Minimum investment amount in Birr', 0),
        ('max_investment', '999999999', 'number', 'trading', 'Maximum investment amount in Birr', 0),
        ('min_withdrawal', '100', 'number', 'trading', 'Minimum withdrawal amount in Birr', 0),
        ('max_withdrawal', '1000000', 'number', 'trading', 'Maximum withdrawal amount in Birr', 0),
        ('default_commission_rate', '5', 'number', 'trading', 'Default commission rate percentage', 0),
        ('trading_hours_start', '08:00', 'string', 'trading', 'Trading hours start time', 1),
        ('trading_hours_end', '18:00', 'string', 'trading', 'Trading hours end time', 1),
        ('support_email', 'support@breakthrough.et', 'string', 'contact', 'Support email address', 1),
        ('company_address', 'Bole Road, Addis Ababa, Ethiopia', 'string', 'contact', 'Company address', 1),
        ('company_phone', '+251 11 123 4567', 'string', 'contact', 'Company phone number', 1),
        ('maintenance_mode', 'false', 'boolean', 'system', 'Enable maintenance mode', 0),
        ('registration_enabled', 'true', 'boolean', 'system', 'Enable user registration', 0)";
    
    $pdo->exec($settings_sql);
    
    // Insert sample market data for Ethiopian Birr
    $market_sql = "INSERT IGNORE INTO market_data (date, currency_pair, opening_rate, closing_rate, high_rate, low_rate, volume, market_trend) VALUES
        (CURDATE(), 'ETB/USD', 55.25, 55.45, 55.60, 55.10, 1250000.00, 'stable'),
        (DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'ETB/USD', 55.10, 55.25, 55.40, 55.00, 1180000.00, 'bullish'),
        (DATE_SUB(CURDATE(), INTERVAL 2 DAY), 'ETB/USD', 55.30, 55.10, 55.35, 54.95, 1320000.00, 'bearish')";
    
    $pdo->exec($market_sql);
    
    $setup_log[] = "✓ Trading levels inserted (Level 1: Br1,000-3,000, Level 2: Br10,000-30,000, Level 3: Br300,000+)";
    $setup_log[] = "✓ Admin account created: elias@gmail.com / admin123";
    $setup_log[] = "✓ System settings configured for Ethiopian Birr trading";
    $setup_log[] = "✓ Sample market data inserted for ETB/USD";
    $setup_status['data'] = true;
    
} catch(PDOException $e) {
    $setup_log[] = "✗ Failed to insert data: " . $e->getMessage();
    $setup_status['data'] = false;
}
?>