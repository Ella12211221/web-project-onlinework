<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Invitation Table - Breakthrough Trading</title>
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
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Fix Invitation Codes Table</h1>
        
        <?php
        if (isset($_POST['fix_table'])) {
            $host = 'localhost';
            $username = 'root';
            $password = '';
            $database = 'breakthrough_trading';
            
            try {
                echo "<div class='info'>Connecting to database...</div>";
                $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                echo "<div class='success'>✅ Connected to database successfully!</div>";
                
                // Create invitation_codes table
                echo "<div class='info'>Creating invitation_codes table...</div>";
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
                echo "<div class='success'>✅ Invitation codes table created successfully!</div>";
                
                // Add missing columns to users table if they don't exist
                echo "<div class='info'>Updating users table...</div>";
                
                // Add invitation_code_used column
                try {
                    $pdo->exec("ALTER TABLE users ADD COLUMN invitation_code_used VARCHAR(20) DEFAULT NULL");
                    echo "<div class='success'>✅ Added invitation_code_used column to users table!</div>";
                } catch(PDOException $e) {
                    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                        echo "<div class='info'>ℹ️ invitation_code_used column already exists</div>";
                    } else {
                        throw $e;
                    }
                }
                
                // Add referral_code column
                try {
                    $pdo->exec("ALTER TABLE users ADD COLUMN referral_code VARCHAR(20) DEFAULT NULL");
                    echo "<div class='success'>✅ Added referral_code column to users table!</div>";
                } catch(PDOException $e) {
                    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                        echo "<div class='info'>ℹ️ referral_code column already exists</div>";
                    } else {
                        throw $e;
                    }
                }
                
                // Add total_commission column
                try {
                    $pdo->exec("ALTER TABLE users ADD COLUMN total_commission DECIMAL(15,2) DEFAULT 0.00");
                    echo "<div class='success'>✅ Added total_commission column to users table!</div>";
                } catch(PDOException $e) {
                    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                        echo "<div class='info'>ℹ️ total_commission column already exists</div>";
                    } else {
                        throw $e;
                    }
                }
                
                // Add approved_by column
                try {
                    $pdo->exec("ALTER TABLE users ADD COLUMN approved_by INT NULL");
                    echo "<div class='success'>✅ Added approved_by column to users table!</div>";
                } catch(PDOException $e) {
                    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                        echo "<div class='info'>ℹ️ approved_by column already exists</div>";
                    } else {
                        throw $e;
                    }
                }
                
                // Add approved_at column
                try {
                    $pdo->exec("ALTER TABLE users ADD COLUMN approved_at TIMESTAMP NULL");
                    echo "<div class='success'>✅ Added approved_at column to users table!</div>";
                } catch(PDOException $e) {
                    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                        echo "<div class='info'>ℹ️ approved_at column already exists</div>";
                    } else {
                        throw $e;
                    }
                }
                
                // Update status column to include pending option
                try {
                    $pdo->exec("ALTER TABLE users MODIFY COLUMN status ENUM('pending','active','inactive','suspended') DEFAULT 'pending'");
                    echo "<div class='success'>✅ Updated status column with pending option!</div>";
                } catch(PDOException $e) {
                    echo "<div class='info'>ℹ️ Status column update: " . $e->getMessage() . "</div>";
                }
                
                // Insert sample invitation codes
                echo "<div class='info'>Inserting sample invitation codes...</div>";
                $pdo->exec("INSERT IGNORE INTO invitation_codes (code, max_uses, bonus_amount, description, is_active) VALUES
                    ('WELCOME2026', 100, 500.00, 'Welcome bonus for new Ethiopian Birr traders', 1),
                    ('ELITE2026', 50, 1000.00, 'Elite invitation for premium traders', 1),
                    ('FRIEND2026', 25, 250.00, 'Friend referral bonus', 1),
                    ('STARTER2026', 200, 100.00, 'Starter bonus for beginners', 1),
                    ('VIP2026', 10, 2000.00, 'VIP exclusive invitation', 1)");
                echo "<div class='success'>✅ Sample invitation codes inserted successfully!</div>";
                
                // Update transactions table for payment methods and commission
                echo "<div class='info'>Updating transactions table...</div>";
                
                // Add payment_method column
                try {
                    $pdo->exec("ALTER TABLE transactions ADD COLUMN payment_method ENUM('cbe','anbesa','wegagen','mastercard','paypal','cash','bank_transfer') DEFAULT NULL");
                    echo "<div class='success'>✅ Added payment_method column to transactions table!</div>";
                } catch(PDOException $e) {
                    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                        echo "<div class='info'>ℹ️ payment_method column already exists</div>";
                    } else {
                        echo "<div class='info'>ℹ️ Payment method column: " . $e->getMessage() . "</div>";
                    }
                }
                
                // Add payment_details column
                try {
                    $pdo->exec("ALTER TABLE transactions ADD COLUMN payment_details TEXT DEFAULT NULL");
                    echo "<div class='success'>✅ Added payment_details column to transactions table!</div>";
                } catch(PDOException $e) {
                    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                        echo "<div class='info'>ℹ️ payment_details column already exists</div>";
                    } else {
                        echo "<div class='info'>ℹ️ Payment details column: " . $e->getMessage() . "</div>";
                    }
                }
                
                // Add commission_rate column
                try {
                    $pdo->exec("ALTER TABLE transactions ADD COLUMN commission_rate DECIMAL(5,2) DEFAULT 0.00");
                    echo "<div class='success'>✅ Added commission_rate column to transactions table!</div>";
                } catch(PDOException $e) {
                    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                        echo "<div class='info'>ℹ️ commission_rate column already exists</div>";
                    } else {
                        echo "<div class='info'>ℹ️ Commission rate column: " . $e->getMessage() . "</div>";
                    }
                }
                
                // Add commission_amount column
                try {
                    $pdo->exec("ALTER TABLE transactions ADD COLUMN commission_amount DECIMAL(15,2) DEFAULT 0.00");
                    echo "<div class='success'>✅ Added commission_amount column to transactions table!</div>";
                } catch(PDOException $e) {
                    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                        echo "<div class='info'>ℹ️ commission_amount column already exists</div>";
                    } else {
                        echo "<div class='info'>ℹ️ Commission amount column: " . $e->getMessage() . "</div>";
                    }
                }
                
                // Update existing users with referral codes if they don't have them
                echo "<div class='info'>Updating existing users with referral codes...</div>";
                $users = $pdo->query("SELECT id, full_name FROM users WHERE referral_code IS NULL OR referral_code = ''")->fetchAll();
                foreach ($users as $user) {
                    $referral_code = strtoupper(substr($user['full_name'], 0, 3) . rand(1000, 9999));
                    $update_stmt = $pdo->prepare("UPDATE users SET referral_code = ? WHERE id = ?");
                    $update_stmt->execute([$referral_code, $user['id']]);
                }
                echo "<div class='success'>✅ Updated " . count($users) . " users with referral codes!</div>";
                
                // Create personal invitation codes for existing users who don't have them
                echo "<div class='info'>Creating personal invitation codes for existing users...</div>";
                $users_without_codes = $pdo->query("
                    SELECT u.id, u.full_name, u.email 
                    FROM users u 
                    LEFT JOIN invitation_codes ic ON u.id = ic.created_by 
                    WHERE ic.id IS NULL AND u.user_type = 'user'
                ")->fetchAll();
                
                $created_codes = 0;
                foreach ($users_without_codes as $user) {
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
                    $personal_stmt = $pdo->prepare("INSERT INTO invitation_codes (code, created_by, max_uses, bonus_amount, description, is_active) VALUES (?, ?, 50, 200.00, ?, 1)");
                    if ($personal_stmt->execute([$personal_code, $user['id'], $personal_description])) {
                        $created_codes++;
                    }
                }
                echo "<div class='success'>✅ Created $created_codes personal invitation codes for existing users!</div>";
                
                // Verify the fix
                echo "<div class='info'>Verifying the fix...</div>";
                $table_exists = $pdo->query("SHOW TABLES LIKE 'invitation_codes'")->fetchColumn();
                $code_count = $pdo->query("SELECT COUNT(*) FROM invitation_codes")->fetchColumn();
                
                if ($table_exists && $code_count > 0) {
                    echo "<div class='success' style='font-size: 18px; text-align: center; margin: 30px 0;'>";
                    echo "🎉 <strong>FIX COMPLETED SUCCESSFULLY!</strong><br><br>";
                    echo "✅ invitation_codes table created<br>";
                    echo "✅ $code_count invitation codes available<br>";
                    echo "✅ Users table updated with referral system<br>";
                    echo "✅ Personal invitation codes created for existing users<br>";
                    echo "✅ System ready for automatic invitation code generation<br>";
                    echo "</div>";
                    
                    echo "<div style='text-align: center; margin: 30px 0;'>";
                    echo "<a href='../admin/invitations.php' class='btn'>🎫 Manage Invitations</a>";
                    echo "<a href='../auth/register.php' class='btn'>📝 Test Registration</a>";
                    echo "<a href='status.php' class='btn'>📊 Check Status</a>";
                    echo "</div>";
                } else {
                    echo "<div class='error'>❌ Fix verification failed. Please try again.</div>";
                }
                
            } catch(PDOException $e) {
                echo "<div class='error'>";
                echo "<h4>❌ Database Error:</h4>";
                echo "<strong>Error:</strong> " . $e->getMessage() . "<br>";
                echo "</div>";
            }
        } else {
            echo "<div class='info'>";
            echo "<h3>🔧 What This Will Fix:</h3>";
            echo "✓ Create missing 'invitation_codes' table<br>";
            echo "✓ Add referral system columns to users table<br>";
            echo "✓ Insert 5 sample invitation codes with bonuses<br>";
            echo "✓ Update existing users with referral codes<br>";
            echo "✓ Fix the invitation system error<br>";
            echo "</div>";
            
            echo "<form method='POST' style='text-align: center; margin: 30px 0;'>";
            echo "<button type='submit' name='fix_table' class='btn' style='font-size: 18px; padding: 20px 40px;'>";
            echo "🔧 Fix Invitation System Now";
            echo "</button>";
            echo "</form>";
        }
        ?>
        
        <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid rgba(255, 215, 0, 0.3);">
            <a href="../index.html" style="color: #ffd700; text-decoration: none; margin: 0 15px;">🏠 Home</a>
            <a href="status.php" style="color: #ffd700; text-decoration: none; margin: 0 15px;">📊 Database Status</a>
            <a href="../admin/dashboard.php" style="color: #ffd700; text-decoration: none; margin: 0 15px;">👑 Admin Dashboard</a>
        </div>
    </div>
</body>
</html>