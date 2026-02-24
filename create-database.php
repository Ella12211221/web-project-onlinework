<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Database - Breakthrough Trading</title>
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
            max-width: 900px;
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
        .step {
            background: rgba(255, 255, 255, 0.05);
            padding: 20px;
            margin: 15px 0;
            border-radius: 10px;
            border-left: 4px solid #ffd700;
        }
        .sql-code {
            background: rgba(0, 0, 0, 0.3);
            padding: 15px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            color: #ffd700;
            margin: 10px 0;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🗄️ Database Creator for Breakthrough Trading</h1>
        
        <?php
        // Database configuration
        $host = 'localhost';
        $username = 'root';
        $password = '';
        $database = 'breakthrough_trading';
        
        echo "<div class='info'>";
        echo "<h3>📋 Database Configuration:</h3>";
        echo "<strong>Host:</strong> $host<br>";
        echo "<strong>Username:</strong> $username<br>";
        echo "<strong>Password:</strong> " . (empty($password) ? '(empty)' : '***') . "<br>";
        echo "<strong>Database:</strong> $database";
        echo "</div>";
        
        if (isset($_POST['create_all'])) {
            echo "<h2>🚀 Creating Database...</h2>";
            
            try {
                // Step 1: Connect to MySQL server
                echo "<div class='step'>";
                echo "<h4>Step 1: Connecting to MySQL Server...</h4>";
                $pdo = new PDO("mysql:host=$host", $username, $password);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                echo "<div class='success'>✅ Connected to MySQL server successfully!</div>";
                echo "</div>";
                
                // Step 2: Create database
                echo "<div class='step'>";
                echo "<h4>Step 2: Creating Database '$database'...</h4>";
                $sql = "CREATE DATABASE IF NOT EXISTS `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
                echo "<div class='sql-code'>$sql</div>";
                $pdo->exec($sql);
                echo "<div class='success'>✅ Database '$database' created successfully!</div>";
                echo "</div>";
                
                // Step 3: Connect to the new database
                echo "<div class='step'>";
                echo "<h4>Step 3: Connecting to Database '$database'...</h4>";
                $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                echo "<div class='success'>✅ Connected to database '$database' successfully!</div>";
                echo "</div>";
                
                // Step 4: Create users table
                echo "<div class='step'>";
                echo "<h4>Step 4: Creating Users Table...</h4>";
                $users_sql = "CREATE TABLE IF NOT EXISTS `users` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `email` VARCHAR(255) UNIQUE NOT NULL,
                    `password` VARCHAR(255) NOT NULL,
                    `full_name` VARCHAR(255) NOT NULL,
                    `user_type` ENUM('user', 'admin') DEFAULT 'user',
                    `trading_level` INT NULL,
                    `account_balance` DECIMAL(15,2) DEFAULT 0.00,
                    `total_invested` DECIMAL(15,2) DEFAULT 0.00,
                    `total_profit` DECIMAL(15,2) DEFAULT 0.00,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
                echo "<div class='sql-code'>" . htmlspecialchars($users_sql) . "</div>";
                $pdo->exec($users_sql);
                echo "<div class='success'>✅ Users table created successfully!</div>";
                echo "</div>";
                
                // Step 5: Create trading levels table
                echo "<div class='step'>";
                echo "<h4>Step 5: Creating Trading Levels Table...</h4>";
                $levels_sql = "CREATE TABLE IF NOT EXISTS `trading_levels` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `level_number` INT UNIQUE NOT NULL,
                    `level_name` VARCHAR(100) NOT NULL,
                    `min_investment` DECIMAL(15,2) NOT NULL,
                    `max_investment` DECIMAL(15,2) NOT NULL,
                    `expected_return_percentage` DECIMAL(5,2) NOT NULL,
                    `duration_days` INT NOT NULL,
                    `description` TEXT,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
                echo "<div class='sql-code'>" . htmlspecialchars($levels_sql) . "</div>";
                $pdo->exec($levels_sql);
                echo "<div class='success'>✅ Trading levels table created successfully!</div>";
                echo "</div>";
                
                // Step 6: Insert trading levels data
                echo "<div class='step'>";
                echo "<h4>Step 6: Inserting Trading Levels Data...</h4>";
                $insert_levels = "INSERT IGNORE INTO `trading_levels` 
                    (`level_number`, `level_name`, `min_investment`, `max_investment`, `expected_return_percentage`, `duration_days`, `description`) 
                    VALUES 
                    (1, 'Level 1 - Beginner', 1000.00, 3000.00, 15.00, 30, 'Perfect for new Ethiopian Birr traders - Low risk, steady returns'),
                    (2, 'Level 2 - Intermediate', 10000.00, 30000.00, 25.00, 21, 'Advanced Ethiopian Birr trading - Higher returns for experienced traders'),
                    (3, 'Level 3 - Elite', 300000.00, 999999999.99, 35.00, 14, 'Premium Ethiopian Birr trading - Maximum returns for serious investors')";
                echo "<div class='sql-code'>" . htmlspecialchars($insert_levels) . "</div>";
                $pdo->exec($insert_levels);
                echo "<div class='success'>✅ Trading levels data inserted successfully!</div>";
                echo "</div>";
                
                // Step 7: Create admin user
                echo "<div class='step'>";
                echo "<h4>Step 7: Creating Admin User...</h4>";
                $admin_sql = "INSERT IGNORE INTO `users` 
                    (`email`, `password`, `full_name`, `user_type`) 
                    VALUES 
                    ('elias@gmail.com', 'admin123', 'Elias Admin', 'admin')";
                echo "<div class='sql-code'>" . htmlspecialchars($admin_sql) . "</div>";
                $pdo->exec($admin_sql);
                echo "<div class='success'>✅ Admin user created successfully!</div>";
                echo "<div class='info'><strong>Admin Login:</strong> elias@gmail.com / admin123</div>";
                echo "</div>";
                
                // Final success message
                echo "<div class='success' style='font-size: 18px; text-align: center; margin: 30px 0;'>";
                echo "🎉 <strong>DATABASE SETUP COMPLETE!</strong><br><br>";
                echo "✅ Database: breakthrough_trading<br>";
                echo "✅ Tables: users, trading_levels<br>";
                echo "✅ Admin: elias@gmail.com / admin123<br>";
                echo "✅ Trading Levels: 3 levels configured<br>";
                echo "</div>";
                
                echo "<div style='text-align: center; margin: 30px 0;'>";
                echo "<a href='../auth/register.php' class='btn'>📝 Test Registration</a>";
                echo "<a href='../auth/login.php' class='btn'>🔑 Test Login</a>";
                echo "<a href='../index.html' class='btn'>🏠 Go to Website</a>";
                echo "</div>";
                
            } catch(PDOException $e) {
                echo "<div class='error'>";
                echo "<h4>❌ Database Error:</h4>";
                echo "<strong>Error:</strong> " . $e->getMessage() . "<br>";
                echo "<strong>Code:</strong> " . $e->getCode() . "<br><br>";
                
                if (strpos($e->getMessage(), 'Access denied') !== false) {
                    echo "<h4>🔧 Possible Solutions:</h4>";
                    echo "1. Make sure WAMP server is running (green icon)<br>";
                    echo "2. Check if MySQL service is started<br>";
                    echo "3. Verify MySQL username/password<br>";
                    echo "4. Try restarting WAMP services<br>";
                } elseif (strpos($e->getMessage(), 'Connection refused') !== false) {
                    echo "<h4>🔧 Possible Solutions:</h4>";
                    echo "1. Start WAMP server<br>";
                    echo "2. Check if port 3306 is available<br>";
                    echo "3. Restart MySQL service<br>";
                }
                echo "</div>";
            }
        } else {
            // Show setup form
            echo "<div class='info'>";
            echo "<h3>🎯 What This Will Do:</h3>";
            echo "1. Create database 'breakthrough_trading'<br>";
            echo "2. Create 'users' table for registration/login<br>";
            echo "3. Create 'trading_levels' table with 3 levels<br>";
            echo "4. Insert sample trading levels (Level 1, 2, 3)<br>";
            echo "5. Create admin account (elias@gmail.com / admin123)<br>";
            echo "</div>";
            
            echo "<div class='step'>";
            echo "<h4>📋 Requirements Check:</h4>";
            
            // Check if WAMP is running
            try {
                $test_connection = new PDO("mysql:host=$host", $username, $password);
                echo "<div class='success'>✅ MySQL connection available</div>";
                $can_proceed = true;
            } catch(PDOException $e) {
                echo "<div class='error'>❌ Cannot connect to MySQL: " . $e->getMessage() . "</div>";
                echo "<div class='info'>Please start WAMP server and make sure MySQL is running</div>";
                $can_proceed = false;
            }
            echo "</div>";
            
            if ($can_proceed) {
                echo "<form method='POST' style='text-align: center; margin: 30px 0;'>";
                echo "<button type='submit' name='create_all' class='btn' style='font-size: 18px; padding: 20px 40px;'>";
                echo "🚀 CREATE DATABASE NOW";
                echo "</button>";
                echo "</form>";
            } else {
                echo "<div style='text-align: center; margin: 30px 0;'>";
                echo "<div class='error'>Please fix the MySQL connection issue first</div>";
                echo "</div>";
            }
        }
        ?>
        
        <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid rgba(255, 215, 0, 0.3);">
            <a href="../index.html" style="color: #ffd700; text-decoration: none;">🏠 Back to Website</a> |
            <a href="test-connection.php" style="color: #ffd700; text-decoration: none;">🔌 Test Connection</a>
        </div>
    </div>
</body>
</html>