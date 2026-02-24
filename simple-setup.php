<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simple Database Setup - Breakthrough Trading</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #0a0e1a 0%, #1a1f2e 100%);
            color: white;
            margin: 0;
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.1);
            padding: 30px;
            border-radius: 15px;
            border: 1px solid rgba(34, 139, 34, 0.3);
        }
        h1 {
            text-align: center;
            color: #ffd700;
            margin-bottom: 30px;
        }
        .btn {
            background: linear-gradient(45deg, #228b22, #ffd700);
            color: #000;
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            margin: 10px;
            text-decoration: none;
            display: inline-block;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(34, 139, 34, 0.4);
        }
        .success {
            background: rgba(34, 139, 34, 0.2);
            color: #228b22;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
            border: 1px solid #228b22;
        }
        .error {
            background: rgba(255, 82, 82, 0.2);
            color: #ff5252;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
            border: 1px solid #ff5252;
        }
        .info {
            background: rgba(255, 215, 0, 0.2);
            color: #ffd700;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
            border: 1px solid #ffd700;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Simple Database Setup</h1>
        
        <?php
        if (isset($_POST['setup'])) {
            $host = 'localhost';
            $username = 'root';
            $password = '';
            $database = 'breakthrough_trading';
            
            try {
                echo "<h3 style='color: #ffd700; text-align: center;'>🚀 Setting up Breakthrough Trading Database...</h3>";
                
                // Step 1: Create database
                echo "<div class='info'>Step 1: Creating database...</div>";
                $pdo = new PDO("mysql:host=$host", $username, $password);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $pdo->exec("CREATE DATABASE IF NOT EXISTS $database CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                echo "<div class='success'>✓ Database '$database' created successfully!</div>";
                
                // Step 2: Connect to database
                echo "<div class='info'>Step 2: Connecting to database...</div>";
                $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                echo "<div class='success'>✓ Connected to database successfully!</div>";
                
                // Step 3: Create users table with all required fields
                echo "<div class='info'>Step 3: Creating users table...</div>";
                $pdo->exec("CREATE TABLE IF NOT EXISTS users (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    email VARCHAR(255) UNIQUE NOT NULL,
                    password VARCHAR(255) NOT NULL,
                    full_name VARCHAR(255) NOT NULL,
                    user_type ENUM('user', 'admin') DEFAULT 'user',
                    trading_level INT NULL,
                    account_balance DECIMAL(15,2) DEFAULT 0.00,
                    total_invested DECIMAL(15,2) DEFAULT 0.00,
                    total_profit DECIMAL(15,2) DEFAULT 0.00,
                    total_commission DECIMAL(15,2) DEFAULT 0.00,
                    status ENUM('pending','active','inactive','suspended') DEFAULT 'pending',
                    phone VARCHAR(20) DEFAULT NULL,
                    address TEXT DEFAULT NULL,
                    city VARCHAR(100) DEFAULT 'Addis Ababa',
                    country VARCHAR(100) DEFAULT 'Ethiopia',
                    invitation_code_used VARCHAR(20) DEFAULT NULL,
                    referral_code VARCHAR(20) DEFAULT NULL,
                    approved_by INT NULL,
                    approved_at TIMESTAMP NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                echo "<div class='success'>✓ Users table created successfully!</div>";
                
                // Step 4: Create trading levels table
                echo "<div class='info'>Step 4: Creating trading levels table...</div>";
                $pdo->exec("CREATE TABLE IF NOT EXISTS trading_levels (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    level_number INT UNIQUE NOT NULL,
                    level_name VARCHAR(100) NOT NULL,
                    min_investment DECIMAL(15,2) NOT NULL,
                    max_investment DECIMAL(15,2) NOT NULL,
                    expected_return_percentage DECIMAL(5,2) NOT NULL,
                    duration_days INT NOT NULL,
                    description TEXT,
                    is_active TINYINT(1) DEFAULT 1,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                echo "<div class='success'>✓ Trading levels table created successfully!</div>";
                
                // Step 5: Create invitation codes table
                echo "<div class='info'>Step 5: Creating invitation codes table...</div>";
                $pdo->exec("CREATE TABLE IF NOT EXISTS invitation_codes (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    code VARCHAR(20) UNIQUE NOT NULL,
                    created_by INT NULL,
                    used_by INT NULL,
                    max_uses INT DEFAULT 1,
                    current_uses INT DEFAULT 0,
                    expires_at DATETIME NULL,
                    is_active TINYINT(1) DEFAULT 1,
                    bonus_amount DECIMAL(15,2) DEFAULT 0.00,
                    description TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    used_at TIMESTAMP NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                echo "<div class='success'>✓ Invitation codes table created successfully!</div>";
                
                // Step 6: Create transactions table with payment methods and commission
                echo "<div class='info'>Step 6: Creating transactions table...</div>";
                $pdo->exec("CREATE TABLE IF NOT EXISTS transactions (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    investment_id INT NULL,
                    transaction_type ENUM('deposit','withdrawal','investment','return','profit','commission') NOT NULL,
                    amount DECIMAL(15,2) NOT NULL,
                    currency VARCHAR(10) DEFAULT 'ETB',
                    payment_method ENUM('cbe','wegagen','abyssinia','mastercard','visa','paypal','stripe','telebirr','mpesa','bitcoin','bank_transfer','cash','other') DEFAULT NULL,
                    payment_details TEXT DEFAULT NULL,
                    commission_rate DECIMAL(5,2) DEFAULT 0.00,
                    commission_amount DECIMAL(15,2) DEFAULT 0.00,
                    description TEXT DEFAULT NULL,
                    status ENUM('pending','processing','completed','failed','cancelled') DEFAULT 'pending',
                    reference_number VARCHAR(100) DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY reference_number (reference_number)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                echo "<div class='success'>✓ Transactions table created successfully!</div>";
                
                // Step 7: Create investments table
                echo "<div class='info'>Step 7: Creating investments table...</div>";
                $pdo->exec("CREATE TABLE IF NOT EXISTS investments (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    trading_level_id INT NOT NULL,
                    amount DECIMAL(15,2) NOT NULL,
                    expected_return DECIMAL(15,2) NOT NULL,
                    actual_return DECIMAL(15,2) DEFAULT 0.00,
                    status ENUM('pending','active','completed','cancelled') DEFAULT 'pending',
                    start_date DATE NOT NULL,
                    end_date DATE NOT NULL,
                    completed_date DATE DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                echo "<div class='success'>✓ Investments table created successfully!</div>";
                
                // Step 8: Create notifications table
                echo "<div class='info'>Step 8: Creating notifications table...</div>";
                $pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    title VARCHAR(255) NOT NULL,
                    message TEXT NOT NULL,
                    type ENUM('info','success','warning','error','investment','withdrawal','profit') DEFAULT 'info',
                    is_read TINYINT(1) DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                echo "<div class='success'>✓ Notifications table created successfully!</div>";
                
                // Step 9: Create contact messages table
                echo "<div class='info'>Step 9: Creating contact messages table...</div>";
                $pdo->exec("CREATE TABLE IF NOT EXISTS contact_messages (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    email VARCHAR(255) NOT NULL,
                    subject VARCHAR(255) NOT NULL,
                    message TEXT NOT NULL,
                    status ENUM('new','read','replied','closed') DEFAULT 'new',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                echo "<div class='success'>✓ Contact messages table created successfully!</div>";
                
                // Step 10: Insert trading levels data
                echo "<div class='info'>Step 10: Inserting Ethiopian Birr trading levels...</div>";
                $pdo->exec("INSERT IGNORE INTO trading_levels (level_number, level_name, min_investment, max_investment, expected_return_percentage, duration_days, description, is_active) VALUES
                    (1, 'Level 1 - Beginner', 1000.00, 3000.00, 15.00, 30, 'Perfect for new Ethiopian Birr traders - Low risk, steady returns with basic trading tools and monthly reports', 1),
                    (2, 'Level 2 - Intermediate', 10000.00, 30000.00, 25.00, 21, 'Advanced Ethiopian Birr trading - Higher returns for experienced traders with weekly reports and priority support', 1),
                    (3, 'Level 3 - Elite', 300000.00, 999999999.99, 35.00, 14, 'Premium Ethiopian Birr trading - Maximum returns for serious investors with VIP support and dedicated management', 1)");
                echo "<div class='success'>✓ Trading levels data inserted successfully!</div>";
                
                // Step 11: Insert invitation codes
                echo "<div class='info'>Step 11: Creating invitation codes...</div>";
                $pdo->exec("INSERT IGNORE INTO invitation_codes (code, max_uses, bonus_amount, description, is_active) VALUES
                    ('WELCOME2026', 100, 500.00, 'Welcome bonus for new Ethiopian Birr traders', 1),
                    ('ELITE2026', 50, 1000.00, 'Elite invitation for premium traders', 1),
                    ('FRIEND2026', 25, 250.00, 'Friend referral bonus', 1),
                    ('STARTER2026', 200, 100.00, 'Starter bonus for beginners', 1),
                    ('VIP2026', 10, 2000.00, 'VIP exclusive invitation', 1)");
                echo "<div class='success'>✓ Invitation codes created successfully!</div>";
                
                // Step 12: Create admin user with referral code
                echo "<div class='info'>Step 12: Creating admin account...</div>";
                $pdo->exec("INSERT IGNORE INTO users (email, password, full_name, user_type, city, country, status, referral_code) VALUES 
                    ('elias@gmail.com', 'admin123', 'Elias Admin', 'admin', 'Addis Ababa', 'Ethiopia', 'active', 'ADMIN2026')");
                echo "<div class='success'>✓ Admin account created successfully!</div>";
                
                // Step 13: Create sample user for testing
                echo "<div class='info'>Step 13: Creating test user...</div>";
                $pdo->exec("INSERT IGNORE INTO users (email, password, full_name, user_type, city, country, status, referral_code) VALUES 
                    ('test@example.com', 'user123', 'Test User', 'user', 'Addis Ababa', 'Ethiopia', 'active', 'TEST2026')");
                echo "<div class='success'>✓ Test user created successfully!</div>";
                
                // Step 14: Create personal invitation codes for users
                echo "<div class='info'>Step 14: Creating personal invitation codes...</div>";
                $users = $pdo->query("SELECT id, full_name FROM users WHERE user_type = 'user'")->fetchAll();
                foreach ($users as $user) {
                    $personal_code = strtoupper(substr($user['full_name'], 0, 4) . date('y') . rand(100, 999));
                    $personal_description = "Personal invitation code for " . $user['full_name'];
                    
                    // Make sure the personal code is unique
                    $check_personal = $pdo->prepare("SELECT COUNT(*) FROM invitation_codes WHERE code = ?");
                    $check_personal->execute([$personal_code]);
                    
                    // If code exists, generate a new one
                    while ($check_personal->fetchColumn() > 0) {
                        $personal_code = strtoupper(substr($user['full_name'], 0, 4) . date('y') . rand(100, 999));
                        $check_personal->execute([$personal_code]);
                    }
                    
                    // Insert personal invitation code
                    $personal_stmt = $pdo->prepare("INSERT IGNORE INTO invitation_codes (code, created_by, max_uses, bonus_amount, description, is_active) VALUES (?, ?, 50, 200.00, ?, 1)");
                    $personal_stmt->execute([$personal_code, $user['id'], $personal_description]);
                }
                echo "<div class='success'>✓ Personal invitation codes created successfully!</div>";
                
                // Final verification
                echo "<div class='info'>Step 15: Verifying setup...</div>";
                $user_count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
                $level_count = $pdo->query("SELECT COUNT(*) FROM trading_levels")->fetchColumn();
                $code_count = $pdo->query("SELECT COUNT(*) FROM invitation_codes")->fetchColumn();
                $admin_count = $pdo->query("SELECT COUNT(*) FROM users WHERE user_type = 'admin'")->fetchColumn();
                $notification_count = $pdo->query("SELECT COUNT(*) FROM notifications")->fetchColumn();
                $contact_count = $pdo->query("SELECT COUNT(*) FROM contact_messages")->fetchColumn();
                
                echo "<div class='success' style='font-size: 18px; text-align: center; margin: 20px 0;'>";
                echo "🎉 <strong>COMPLETE SETUP SUCCESSFUL!</strong><br><br>";
                echo "✅ Database: breakthrough_trading<br>";
                echo "✅ Users: $user_count (including $admin_count admin)<br>";
                echo "✅ Trading Levels: $level_count levels with commission system<br>";
                echo "✅ Invitation Codes: $code_count codes with auto-generation<br>";
                echo "✅ Payment Methods: CBE, Anbesa, Wegagen, MasterCard, PayPal<br>";
                echo "✅ Commission System: Level 1 (2%), Level 2 (5%), Level 3 (3%)<br>";
                echo "✅ Admin Approval System: New users require approval<br>";
                echo "✅ Notifications System: Ready for user alerts<br>";
                echo "✅ Contact System: Ready for customer support<br>";
                echo "✅ Admin Login: elias@gmail.com / admin123<br>";
                echo "✅ Test Login: test@example.com / user123<br>";
                echo "</div>";
                
                echo "<div style='text-align: center; margin: 30px 0;'>";
                echo "<a href='../auth/login.php' class='btn' style='margin: 10px;'>🔑 Test Login</a>";
                echo "<a href='../auth/register.php' class='btn' style='margin: 10px;'>📝 Test Registration</a>";
                echo "<a href='../index.html' class='btn' style='margin: 10px;'>🏠 Go to Website</a>";
                echo "</div>";
                
            } catch(PDOException $e) {
                echo "<div class='error'>";
                echo "<h4>❌ Database Setup Failed</h4>";
                echo "<strong>Error:</strong> " . $e->getMessage() . "<br><br>";
                
                if (strpos($e->getMessage(), 'Access denied') !== false) {
                    echo "<h4>🔧 Solution:</h4>";
                    echo "1. Make sure WAMP/XAMPP server is running<br>";
                    echo "2. Check if MySQL service is started<br>";
                    echo "3. Verify MySQL is accessible on localhost<br>";
                } elseif (strpos($e->getMessage(), 'Connection refused') !== false) {
                    echo "<h4>🔧 Solution:</h4>";
                    echo "1. Start your local server (WAMP/XAMPP)<br>";
                    echo "2. Make sure MySQL is running on port 3306<br>";
                    echo "3. Check server status in control panel<br>";
                }
                echo "</div>";
            }
        } else {
            echo "<div class='info'>";
            echo "<h3>🎯 What This Will Do:</h3>";
            echo "✓ Create 'breakthrough_trading' database<br>";
            echo "✓ Create users table with commission and approval system<br>";
            echo "✓ Create trading_levels table with commission rates<br>";
            echo "✓ Create invitation_codes table with auto-generation<br>";
            echo "✓ Create transactions table with payment methods<br>";
            echo "✓ Create investments table with commission tracking<br>";
            echo "✓ Create notifications table for user alerts<br>";
            echo "✓ Create contact_messages table for support<br>";
            echo "✓ Insert 3 Ethiopian Birr trading levels with commission<br>";
            echo "✓ Insert 5 sample invitation codes with bonuses<br>";
            echo "✓ Create admin account (elias@gmail.com / admin123)<br>";
            echo "✓ Create test user account (test@example.com / user123)<br>";
            echo "✓ Setup commission system: Level 1 (2%), Level 2 (5%), Level 3 (3%)<br>";
            echo "✓ Setup payment methods: CBE, Anbesa, Wegagen, MasterCard, PayPal<br>";
            echo "✓ Setup admin approval system for new users<br>";
            echo "</div>";
            
            echo "<form method='POST' style='text-align: center; margin: 30px 0;'>";
            echo "<button type='submit' name='setup' class='btn' style='font-size: 18px; padding: 20px 40px;'>🚀 Setup Database Now</button>";
            echo "</form>";
        }
        ?>
    </div>
</body>
</html>