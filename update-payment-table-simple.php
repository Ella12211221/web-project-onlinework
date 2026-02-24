<?php
// Simple script to update payment_transactions table for withdrawal support
try {
    $pdo = new PDO("mysql:host=localhost;dbname=concordial_nexus;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Updating payment_transactions table...\n";
    
    // Update payment_method enum to include withdrawal_request
    $alter_sql = "ALTER TABLE payment_transactions MODIFY COLUMN payment_method ENUM('mobile_banking', 'bank_transfer', 'digital_wallet', 'withdrawal_request') NOT NULL";
    $pdo->exec($alter_sql);
    echo "✅ Updated payment_method enum to include withdrawal_request\n";
    
    // Update purpose enum to include withdrawal reasons
    $alter_purpose_sql = "ALTER TABLE payment_transactions MODIFY COLUMN purpose VARCHAR(50) NULL COMMENT 'investment, trading, withdrawal, transfer, profit_withdrawal, investment_return, emergency, personal_use'";
    $pdo->exec($alter_purpose_sql);
    echo "✅ Updated purpose field to include withdrawal reasons\n";
    
    echo "✅ Payment transactions table update complete!\n";
    
} catch(PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>