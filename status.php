<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Status - Breakthrough Trading</title>
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
            max-width: 1000px;
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
        .status-card {
            background: rgba(255, 255, 255, 0.05);
            padding: 20px;
            margin: 15px 0;
            border-radius: 10px;
            border-left: 4px solid;
        }
        .status-success {
            border-left-color: #228b22;
            background: rgba(34, 139, 34, 0.1);
        }
        .status-error {
            border-left-color: #ff5252;
            background: rgba(255, 82, 82, 0.1);
        }
        .status-warning {
            border-left-color: #ffd700;
            background: rgba(255, 215, 0, 0.1);
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
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        .data-table th, .data-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid rgba(34, 139, 34, 0.2);
        }
        .data-table th {
            background: rgba(34, 139, 34, 0.2);
            color: #ffd700;
        }
        .summary-box {
            background: rgba(255, 215, 0, 0.1);
            border: 1px solid rgba(255, 215, 0, 0.3);
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📊 Database Status Check</h1>
        <p style="text-align: center; color: rgba(255, 255, 255, 0.8); margin-bottom: 30px;">
            Complete status of your Breakthrough Trading database
        </p>

        <?php
        $host = 'localhost';
        $username = 'root';
        $password = '';
        $database = 'breakthrough_trading';
        
        $overall_status = 'success';
        $issues = [];
        $recommendations = [];
        
        // Test 1: MySQL Server Connection
        echo "<div class='status-card ";
        try {
            $pdo_server = new PDO("mysql:host=$host", $username, $password);
            echo "status-success'>";
            echo "<h3>✅ MySQL Server Connection</h3>";
            echo "<p>Successfully connected to MySQL server at $host</p>";
            
            $version = $pdo_server->query('SELECT VERSION()')->fetchColumn();
            echo "<p><strong>MySQL Version:</strong> $version</p>";
        } catch(PDOException $e) {
            echo "status-error'>";
            echo "<h3>❌ MySQL Server Connection</h3>";
            echo "<p>Failed to connect to MySQL server: " . $e->getMessage() . "</p>";
            $overall_status = 'error';
            $issues[] = "MySQL server not accessible";
            $recommendations[] = "Start WAMP/XAMPP server and ensure MySQL service is running";
        }
        echo "</div>";
        
        // Test 2: Database Existence
        if (isset($pdo_server)) {
            echo "<div class='status-card ";
            try {
                $db_exists = $pdo_server->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$database'")->fetchColumn();
                
                if ($db_exists) {
                    echo "status-success'>";
                    echo "<h3>✅ Database '$database'</h3>";
                    echo "<p>Database exists and is accessible</p>";
                    
                    // Connect to database for further tests
                    $pdo_db = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
                    $pdo_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                } else {
                    echo "status-error'>";
                    echo "<h3>❌ Database '$database'</h3>";
                    echo "<p>Database does not exist</p>";
                    $overall_status = 'error';
                    $issues[] = "Database '$database' not found";
                    $recommendations[] = "Run database setup to create the database";
                }
            } catch(PDOException $e) {
                echo "status-error'>";
                echo "<h3>❌ Database '$database'</h3>";
                echo "<p>Error accessing database: " . $e->getMessage() . "</p>";
                $overall_status = 'error';
                $issues[] = "Cannot access database '$database'";
                $recommendations[] = "Check database permissions and run setup";
            }
            echo "</div>";
        }
        
        // Test 3: Tables Check
        if (isset($pdo_db)) {
            $required_tables = ['users', 'trading_levels', 'invitation_codes'];
            $existing_tables = [];
            
            try {
                $tables_result = $pdo_db->query("SHOW TABLES");
                while ($row = $tables_result->fetch(PDO::FETCH_NUM)) {
                    $existing_tables[] = $row[0];
                }
                
                foreach ($required_tables as $table) {
                    echo "<div class='status-card ";
                    if (in_array($table, $existing_tables)) {
                        echo "status-success'>";
                        echo "<h3>✅ Table '$table'</h3>";
                        
                        // Get table info
                        $count = $pdo_db->query("SELECT COUNT(*) FROM $table")->fetchColumn();
                        echo "<p>Table exists with $count records</p>";
                        
                        if ($table === 'users') {
                            $admin_count = $pdo_db->query("SELECT COUNT(*) FROM users WHERE user_type = 'admin'")->fetchColumn();
                            $user_count = $pdo_db->query("SELECT COUNT(*) FROM users WHERE user_type = 'user'")->fetchColumn();
                            echo "<p><strong>Admins:</strong> $admin_count | <strong>Users:</strong> $user_count</p>";
                            
                            if ($admin_count == 0) {
                                $issues[] = "No admin accounts found";
                                $recommendations[] = "Create admin account or run setup";
                            }
                        }
                        
                        if ($table === 'trading_levels') {
                            $levels = $pdo_db->query("SELECT level_number, level_name FROM trading_levels ORDER BY level_number")->fetchAll();
                            if (count($levels) > 0) {
                                echo "<p><strong>Levels:</strong> ";
                                foreach ($levels as $level) {
                                    echo "Level {$level['level_number']} ";
                                }
                                echo "</p>";
                            } else {
                                $issues[] = "No trading levels configured";
                                $recommendations[] = "Insert trading levels data";
                            }
                        }
                        
                        if ($table === 'invitation_codes') {
                            $active_codes = $pdo_db->query("SELECT COUNT(*) FROM invitation_codes WHERE is_active = 1")->fetchColumn();
                            echo "<p><strong>Active Codes:</strong> $active_codes</p>";
                            if ($active_codes == 0) {
                                $issues[] = "No active invitation codes";
                                $recommendations[] = "Create invitation codes for registration";
                            }
                        }
                    } else {
                        echo "status-error'>";
                        echo "<h3>❌ Table '$table'</h3>";
                        echo "<p>Required table is missing</p>";
                        $overall_status = 'error';
                        $issues[] = "Table '$table' missing";
                        if ($table === 'invitation_codes') {
                            $recommendations[] = "Run invitation system fix to create missing table";
                        } else {
                            $recommendations[] = "Run database setup to create missing tables";
                        }
                    }
                    echo "</div>";
                }
            } catch(PDOException $e) {
                echo "<div class='status-card status-error'>";
                echo "<h3>❌ Tables Check</h3>";
                echo "<p>Error checking tables: " . $e->getMessage() . "</p>";
                echo "</div>";
                $overall_status = 'error';
            }
        }
        
        // Test 4: Authentication Test
        if (isset($pdo_db) && in_array('users', $existing_tables ?? [])) {
            echo "<div class='status-card ";
            try {
                // Check admin login
                $admin = $pdo_db->query("SELECT * FROM users WHERE email = 'elias@gmail.com' AND user_type = 'admin'")->fetch();
                
                if ($admin) {
                    echo "status-success'>";
                    echo "<h3>✅ Admin Account</h3>";
                    echo "<p><strong>Email:</strong> elias@gmail.com</p>";
                    echo "<p><strong>Name:</strong> " . htmlspecialchars($admin['full_name']) . "</p>";
                    echo "<p><strong>Status:</strong> Ready for login</p>";
                } else {
                    echo "status-warning'>";
                    echo "<h3>⚠️ Admin Account</h3>";
                    echo "<p>Admin account not found or not properly configured</p>";
                    $issues[] = "Admin account missing";
                    $recommendations[] = "Create admin account (elias@gmail.com / admin123)";
                }
            } catch(PDOException $e) {
                echo "status-error'>";
                echo "<h3>❌ Admin Account</h3>";
                echo "<p>Error checking admin account: " . $e->getMessage() . "</p>";
            }
            echo "</div>";
        }
        
        // Overall Status Summary
        echo "<div class='summary-box'>";
        if ($overall_status === 'success' && empty($issues)) {
            echo "<h2 style='color: #228b22;'>🎉 Everything is Working!</h2>";
            echo "<p>Your Breakthrough Trading database is fully configured and ready to use.</p>";
            echo "<div style='margin-top: 20px;'>";
            echo "<a href='../auth/login.php' class='btn'>🔑 Login Now</a>";
            echo "<a href='../auth/register.php' class='btn'>📝 Register User</a>";
            echo "<a href='../admin/dashboard.php' class='btn'>👑 Admin Dashboard</a>";
            echo "</div>";
        } else {
            echo "<h2 style='color: #ffd700;'>⚠️ Issues Found</h2>";
            echo "<p>Some issues need to be resolved:</p>";
            
            if (!empty($issues)) {
                echo "<div style='text-align: left; margin: 15px 0;'>";
                echo "<h4>Issues:</h4>";
                foreach ($issues as $issue) {
                    echo "• $issue<br>";
                }
                echo "</div>";
            }
            
            if (!empty($recommendations)) {
                echo "<div style='text-align: left; margin: 15px 0;'>";
                echo "<h4>Recommendations:</h4>";
                foreach ($recommendations as $rec) {
                    echo "• $rec<br>";
                }
                echo "</div>";
            }
            
            echo "<div style='margin-top: 20px;'>";
            echo "<a href='simple-setup.php' class='btn'>🚀 Run Setup</a>";
            echo "<a href='fix-invitation-table.php' class='btn'>🔧 Fix Invitations</a>";
            echo "<a href='create-database.php' class='btn'>🛠️ Advanced Setup</a>";
            echo "</div>";
        }
        echo "</div>";
        
        // Recent Activity (if database is working)
        if (isset($pdo_db) && in_array('users', $existing_tables ?? [])) {
            try {
                $recent_users = $pdo_db->query("SELECT full_name, email, user_type, created_at FROM users ORDER BY created_at DESC LIMIT 5")->fetchAll();
                
                if (!empty($recent_users)) {
                    echo "<div class='status-card status-success'>";
                    echo "<h3>👥 Recent Users</h3>";
                    echo "<table class='data-table'>";
                    echo "<tr><th>Name</th><th>Email</th><th>Type</th><th>Joined</th></tr>";
                    
                    foreach ($recent_users as $user) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($user['full_name']) . "</td>";
                        echo "<td>" . htmlspecialchars($user['email']) . "</td>";
                        echo "<td>" . ucfirst($user['user_type']) . "</td>";
                        echo "<td>" . date('M j, Y', strtotime($user['created_at'])) . "</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                    echo "</div>";
                }
            } catch(PDOException $e) {
                // Silently ignore if we can't get recent users
            }
        }
        ?>

        <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid rgba(255, 215, 0, 0.3);">
            <a href="../index.html" style="color: #ffd700; text-decoration: none; margin: 0 15px;">🏠 Home</a>
            <a href="test-connection.php" style="color: #ffd700; text-decoration: none; margin: 0 15px;">🔌 Test Connection</a>
            <a href="simple-setup.php" style="color: #ffd700; text-decoration: none; margin: 0 15px;">⚙️ Setup</a>
        </div>
    </div>
</body>
</html>