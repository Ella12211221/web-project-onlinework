<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Referral System - Concordial Nexus</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 20px; }
        .container { max-width: 900px; margin: 0 auto; background: white; border-radius: 20px; padding: 30px; box-shadow: 0 20px 40px rgba(0,0,0,0.2); }
        h1 { color: #333; text-align: center; margin-bottom: 30px; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin: 10px 0; border-left: 4px solid #28a745; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin: 10px 0; border-left: 4px solid #dc3545; }
        .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 8px; margin: 10px 0; border-left: 4px solid #17a2b8; }
        .btn { background: #4a90e2; color: white; padding: 15px 30px; border: none; border-radius: 8px; text-decoration: none; display: inline-block; margin: 10px 5px; font-weight: 600; cursor: pointer; }
        .btn:hover { background: #357abd; }
        .step { background: #f8f9fa; border-radius: 10px; padding: 20px; margin-bottom: 20px; border-left: 5px solid #4a90e2; }
        .step h2 { color: #4a90e2; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🌐 Setup Referral/Network System</h1>
        
        <?php
        try {
            $pdo = new PDO("mysql:host=localhost;dbname=concordial_nexus;charset=utf8mb4", "root", "");
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            echo "<div class='success'>✅ Database connection successful</div>";
            
            // Step 1: Add referral columns to users table
            echo "<div class='step'>";
            echo "<h2>Step 1: Update Users Table</h2>";
            
            $columns_to_add = [
                'referral_code' => "VARCHAR(20) UNIQUE DEFAULT NULL",
                'referred_by' => "INT DEFAULT NULL"
            ];
            
            foreach ($columns_to_add as $column => $definition) {
                try {
                    $pdo->exec("ALTER TABLE users ADD COLUMN $column $definition");
                    echo "<div class='success'>✅ Added column: <strong>$column</strong></div>";
                } catch(PDOException $e) {
                    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
                        echo "<div class='info'>ℹ️ Column <strong>$column</strong> already exists</div>";
                    } else {
                        echo "<div class='error'>❌ Error adding $column: " . $e->getMessage() . "</div>";
                    }
                }
            }
            
            // Add foreign key
            try {
                $pdo->exec("ALTER TABLE users ADD CONSTRAINT fk_referred_by FOREIGN KEY (referred_by) REFERENCES users(id) ON DELETE SET NULL");
                echo "<div class='success'>✅ Added foreign key constraint</div>";
            } catch(PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate') !== false || strpos($e->getMessage(), 'already exists') !== false) {
                    echo "<div class='info'>ℹ️ Foreign key already exists</div>";
                }
            }
            
            echo "</div>";
            
            // Step 2: Create commissions table
            echo "<div class='step'>";
            echo "<h2>Step 2: Create Commissions Table</h2>";
            
            $create_commissions = "
            CREATE TABLE IF NOT EXISTS commissions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                from_user_id INT NOT NULL,
                commission_type ENUM('referral', 'investment', 'withdrawal', 'bonus') DEFAULT 'referral',
                amount DECIMAL(15,2) NOT NULL,
                percentage DECIMAL(5,2) DEFAULT NULL,
                reference_id INT DEFAULT NULL,
                reference_type VARCHAR(50) DEFAULT NULL,
                status ENUM('pending', 'paid', 'cancelled') DEFAULT 'pending',
                description TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                paid_at TIMESTAMP NULL DEFAULT NULL,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (from_user_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_user_id (user_id),
                INDEX idx_from_user_id (from_user_id),
                INDEX idx_status (status),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            
            $pdo->exec($create_commissions);
            echo "<div class='success'>✅ Commissions table created/verified</div>";
            echo "</div>";
            
            // Step 3: Generate referral codes for existing users
            echo "<div class='step'>";
            echo "<h2>Step 3: Generate Referral Codes</h2>";
            
            $users_without_code = $pdo->query("SELECT id, email FROM users WHERE referral_code IS NULL OR referral_code = ''")->fetchAll();
            
            if (!empty($users_without_code)) {
                echo "<div class='info'>Generating referral codes for " . count($users_without_code) . " users...</div>";
                
                $update_stmt = $pdo->prepare("UPDATE users SET referral_code = ? WHERE id = ?");
                
                foreach ($users_without_code as $user) {
                    $referral_code = strtoupper(substr(md5($user['id'] . $user['email'] . time() . rand()), 0, 8));
                    $update_stmt->execute([$referral_code, $user['id']]);
                }
                
                echo "<div class='success'>✅ Generated referral codes for all users</div>";
            } else {
                echo "<div class='info'>ℹ️ All users already have referral codes</div>";
            }
            
            echo "</div>";
            
            // Step 4: Create commission settings table
            echo "<div class='step'>";
            echo "<h2>Step 4: Create Commission Settings</h2>";
            
            $create_settings = "
            CREATE TABLE IF NOT EXISTS commission_settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                setting_name VARCHAR(100) UNIQUE NOT NULL,
                setting_value VARCHAR(255) NOT NULL,
                description TEXT,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            
            $pdo->exec($create_settings);
            echo "<div class='success'>✅ Commission settings table created</div>";
            
            // Insert default settings
            $default_settings = [
                ['referral_commission_percentage', '10.00', 'Percentage commission on referral investments'],
                ['min_commission_payout', '100.00', 'Minimum commission amount for payout'],
                ['commission_auto_approve', '0', 'Auto-approve commissions (1=yes, 0=no)'],
                ['max_referral_levels', '3', 'Maximum referral levels for MLM']
            ];
            
            $insert_setting = $pdo->prepare("INSERT IGNORE INTO commission_settings (setting_name, setting_value, description) VALUES (?, ?, ?)");
            
            foreach ($default_settings as $setting) {
                $insert_setting->execute($setting);
            }
            
            echo "<div class='success'>✅ Default commission settings configured</div>";
            echo "<div class='info'>📊 Default Settings:<br>";
            echo "• Referral Commission: 10% of investment<br>";
            echo "• Minimum Payout: Br100.00<br>";
            echo "• Auto-Approve: Disabled<br>";
            echo "• Max Levels: 3 levels deep</div>";
            
            echo "</div>";
            
            // Step 5: Statistics
            echo "<div class='step'>";
            echo "<h2>Step 5: System Statistics</h2>";
            
            $total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
            $users_with_referrals = $pdo->query("SELECT COUNT(DISTINCT referred_by) FROM users WHERE referred_by IS NOT NULL")->fetchColumn();
            $total_referrals = $pdo->query("SELECT COUNT(*) FROM users WHERE referred_by IS NOT NULL")->fetchColumn();
            $total_commissions = $pdo->query("SELECT COUNT(*) FROM commissions")->fetchColumn();
            $total_commission_amount = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM commissions WHERE status = 'paid'")->fetchColumn();
            
            echo "<div class='info'>";
            echo "📊 <strong>System Statistics:</strong><br>";
            echo "• Total Users: <strong>$total_users</strong><br>";
            echo "• Users with Referrals: <strong>$users_with_referrals</strong><br>";
            echo "• Total Referrals: <strong>$total_referrals</strong><br>";
            echo "• Total Commissions: <strong>$total_commissions</strong><br>";
            echo "• Total Paid Commissions: <strong>Br" . number_format($total_commission_amount, 2) . "</strong>";
            echo "</div>";
            
            echo "</div>";
            
            // Final Summary
            echo "<div style='background: linear-gradient(135deg, #4a90e2, #357abd); color: white; border-radius: 15px; padding: 30px; margin-top: 30px; text-align: center;'>";
            echo "<h2 style='color: white; margin-bottom: 20px;'>🎉 Referral System Setup Complete!</h2>";
            echo "<p style='font-size: 1.2rem; margin-bottom: 20px;'>Your pyramid/network marketing system is ready to use!</p>";
            
            echo "<div style='margin-top: 30px;'>";
            echo "<a href='dashboard/referrals.php' class='btn' style='background: white; color: #4a90e2;'>🌐 View My Referrals</a>";
            echo "<a href='admin/dashboard.php' class='btn' style='background: rgba(255,255,255,0.2);'>📊 Admin Dashboard</a>";
            echo "<a href='dashboard/index.php' class='btn' style='background: rgba(255,255,255,0.2);'>🏠 User Dashboard</a>";
            echo "</div>";
            echo "</div>";
            
        } catch(PDOException $e) {
            echo "<div class='error'>";
            echo "<h3>❌ Database Error</h3>";
            echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "</div>";
        }
        ?>
    </div>
</body>
</html>
