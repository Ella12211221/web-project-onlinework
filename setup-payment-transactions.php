<?php
// Setup Payment Transactions Table
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Payment Transactions Table - Concordial Nexus</title>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            margin: 0; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #333;
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .btn {
            background: #4a90e2;
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            font-weight: 600;
            margin: 10px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .btn:hover {
            background: #357abd;
        }
        .btn.success {
            background: #28a745;
        }
        .btn.success:hover {
            background: #218838;
        }
        .success-msg {
            background: #d4edda;
            border: 2px solid #c3e6cb;
            color: #155724;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }
        .error-msg {
            background: #f8d7da;
            border: 2px solid #f5c6cb;
            color: #721c24;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }
        .info-msg {
            background: #d1ecf1;
            border: 2px solid #bee5eb;
            color: #0c5460;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 style="text-align: center; color: #2c5aa0; margin-bottom: 30px;">
            💳 Payment Transactions Table Setup
        </h1>
        
        <?php
        if (isset($_POST['setup_payment_table'])) {
            try {
                $pdo = new PDO("mysql:host=localhost;dbname=concordial_nexus;charset=utf8mb4", "root", "");
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                echo "<div class='info-msg'><h3>🔧 Setting up payment transactions table...</h3></div>";
                
                // Create payment_transactions table
                $create_table_sql = "
                    CREATE TABLE IF NOT EXISTS payment_transactions (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        user_id INT NOT NULL,
                        payment_method ENUM('mobile_banking', 'bank_transfer', 'digital_wallet', 'withdrawal_request') NOT NULL,
                        
                        -- Mobile Banking fields
                        payment_service VARCHAR(50) NULL COMMENT 'CBE Birr, M-Birr, HelloCash, Amole',
                        mobile_number VARCHAR(20) NULL,
                        
                        -- Bank Transfer fields
                        bank_name VARCHAR(100) NULL,
                        account_number VARCHAR(50) NULL,
                        account_holder VARCHAR(100) NULL,
                        branch_code VARCHAR(20) NULL,
                        
                        -- Common fields
                        amount DECIMAL(15,2) NOT NULL,
                        reference_number VARCHAR(100) NULL,
                        purpose VARCHAR(50) NULL COMMENT 'investment, trading, withdrawal, transfer, profit_withdrawal, investment_return, emergency, personal_use',
                        
                        -- Status and tracking
                        status ENUM('pending', 'processing', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
                        admin_notes TEXT NULL,
                        processed_by INT NULL COMMENT 'Admin user ID who processed',
                        processed_at TIMESTAMP NULL,
                        
                        -- Timestamps
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        
                        -- Foreign key constraints
                        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                        FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL,
                        
                        -- Indexes for performance
                        INDEX idx_user_id (user_id),
                        INDEX idx_payment_method (payment_method),
                        INDEX idx_status (status),
                        INDEX idx_created_at (created_at)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ";
                
                $pdo->exec($create_table_sql);
                echo "<p style='color: green;'>✅ Payment transactions table created successfully</p>";
                
                // Check if table exists and show structure
                $columns = $pdo->query("DESCRIBE payment_transactions")->fetchAll();
                
                echo "<div class='success-msg'>";
                echo "<h2>🎉 Payment Transactions Table Setup Complete!</h2>";
                echo "<h3>✅ Table Structure:</h3>";
                echo "<table style='width: 100%; border-collapse: collapse; margin: 15px 0;'>";
                echo "<tr style='background: #f8f9fa;'>";
                echo "<th style='padding: 10px; border: 1px solid #ddd; text-align: left;'>Field</th>";
                echo "<th style='padding: 10px; border: 1px solid #ddd; text-align: left;'>Type</th>";
                echo "<th style='padding: 10px; border: 1px solid #ddd; text-align: left;'>Description</th>";
                echo "</tr>";
                
                $field_descriptions = [
                    'id' => 'Primary key',
                    'user_id' => 'User making the payment',
                    'payment_method' => 'mobile_banking, bank_transfer, or digital_wallet',
                    'payment_service' => 'CBE Birr, M-Birr, HelloCash, Amole (for mobile)',
                    'mobile_number' => 'Mobile number for mobile banking',
                    'bank_name' => 'Bank name for transfers',
                    'account_number' => 'Account number for transfers',
                    'account_holder' => 'Account holder name',
                    'branch_code' => 'Bank branch code (optional)',
                    'amount' => 'Payment amount in Birr',
                    'reference_number' => 'Transaction reference from bank/mobile service',
                    'purpose' => 'investment, trading, withdrawal, transfer',
                    'status' => 'pending, processing, completed, failed, cancelled',
                    'admin_notes' => 'Admin notes for processing',
                    'processed_by' => 'Admin who processed the payment',
                    'processed_at' => 'When payment was processed',
                    'created_at' => 'When payment was submitted',
                    'updated_at' => 'Last update time'
                ];
                
                foreach ($columns as $column) {
                    $field = $column['Field'];
                    $type = $column['Type'];
                    $description = $field_descriptions[$field] ?? '';
                    
                    echo "<tr>";
                    echo "<td style='padding: 8px; border: 1px solid #ddd; font-weight: bold;'>$field</td>";
                    echo "<td style='padding: 8px; border: 1px solid #ddd; font-family: monospace;'>$type</td>";
                    echo "<td style='padding: 8px; border: 1px solid #ddd;'>$description</td>";
                    echo "</tr>";
                }
                echo "</table>";
                echo "</div>";
                
                // Show features
                echo "<div style='background: #e8f5e8; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
                echo "<h3 style='color: #27ae60;'>🚀 Payment System Features:</h3>";
                echo "<ul style='color: #2c3e50; line-height: 1.8;'>";
                echo "<li>✅ <strong>Mobile Banking:</strong> CBE Birr, M-Birr, HelloCash, Amole support</li>";
                echo "<li>✅ <strong>Bank Transfer:</strong> All major Ethiopian banks supported</li>";
                echo "<li>✅ <strong>Digital Wallet:</strong> Instant payments from platform balance</li>";
                echo "<li>✅ <strong>Reference Tracking:</strong> All transactions tracked with reference numbers</li>";
                echo "<li>✅ <strong>Admin Processing:</strong> Admin approval workflow for payments</li>";
                echo "<li>✅ <strong>Status Management:</strong> Complete payment lifecycle tracking</li>";
                echo "<li>✅ <strong>Security:</strong> No password storage, reference-based verification</li>";
                echo "</ul>";
                echo "</div>";
                
                $setup_complete = true;
                
            } catch(PDOException $e) {
                echo "<div class='error-msg'>";
                echo "<h3>❌ Database Error:</h3>";
                echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
                echo "<p><strong>Solution:</strong> Make sure your database server is running and the 'concordial_nexus' database exists.</p>";
                echo "</div>";
            }
        }
        
        // Display current status or setup button
        try {
            $pdo = new PDO("mysql:host=localhost;dbname=concordial_nexus;charset=utf8mb4", "root", "");
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Check if table exists
            $table_exists = false;
            try {
                $pdo->query("SELECT 1 FROM payment_transactions LIMIT 1");
                $table_exists = true;
                $count = $pdo->query("SELECT COUNT(*) FROM payment_transactions")->fetchColumn();
            } catch(PDOException $e) {
                $table_exists = false;
            }
            
            if (!$table_exists) {
                echo "<div style='text-align: center; padding: 40px; background: #fff3cd; border: 2px solid #ffeaa7; border-radius: 10px; margin: 20px 0;'>";
                echo "<h3 style='color: #856404;'>⚠️ Payment Transactions Table Not Found</h3>";
                echo "<p>Click the button below to create the payment transactions table for processing payments.</p>";
                echo "<form method='POST' style='margin-top: 20px;'>";
                echo "<button type='submit' name='setup_payment_table' class='btn success' style='font-size: 18px; padding: 20px 40px;'>";
                echo "🚀 Create Payment Transactions Table";
                echo "</button>";
                echo "</form>";
                echo "</div>";
            } else {
                echo "<div class='success-msg'>";
                echo "<h3>✅ Payment Transactions Table Status</h3>";
                echo "<p><strong>$count payment transactions</strong> are currently stored in the system.</p>";
                echo "<form method='POST' style='margin-top: 20px; text-align: center;'>";
                echo "<button type='submit' name='setup_payment_table' class='btn' style='background: #dc3545;'>";
                echo "🔄 Recreate Payment Transactions Table";
                echo "</button>";
                echo "</form>";
                echo "</div>";
            }
            
        } catch(PDOException $e) {
            echo "<div class='error-msg'>";
            echo "<h3>❌ Database Connection Error:</h3>";
            echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<p>Please make sure your database server is running and the database 'concordial_nexus' exists.</p>";
            echo "<p><a href='simple-setup.php' style='color: #721c24; text-decoration: none; font-weight: bold;'>🔧 Click here to setup database first</a></p>";
            echo "</div>";
        }
        ?>
        
        <div style="text-align: center; margin: 30px 0; padding: 20px; background: #f8f9fa; border-radius: 10px;">
            <h3>🔗 Quick Navigation</h3>
            <a href="../dashboard/payment-methods.php" class="btn success">💳 Test Payment Methods</a>
            <a href="../admin/dashboard.php" class="btn">📊 Admin Dashboard</a>
            <a href="../dashboard/index.php" class="btn">🏠 User Dashboard</a>
        </div>
    </div>
</body>
</html>
</content>