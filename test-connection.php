<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Connection Test - Breakthrough Trading</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .test-container {
            max-width: 700px;
            margin: 120px auto 50px;
            padding: 3rem;
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            border: 1px solid rgba(34, 139, 34, 0.2);
            box-shadow: 0 15px 35px rgba(34, 139, 34, 0.1);
        }
        
        .test-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .test-header h1 {
            color: #fff;
            margin-bottom: 1rem;
            background: linear-gradient(45deg, #228b22, #ffd700);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .test-result {
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            border: 1px solid;
        }
        
        .test-success {
            background: rgba(34, 139, 34, 0.1);
            color: #228b22;
            border-color: rgba(34, 139, 34, 0.3);
        }
        
        .test-error {
            background: rgba(255, 82, 82, 0.1);
            color: #ff5252;
            border-color: rgba(255, 82, 82, 0.3);
        }
        
        .test-info {
            background: rgba(255, 215, 0, 0.1);
            color: #ffd700;
            border-color: rgba(255, 215, 0, 0.3);
        }
        
        .connection-details {
            background: rgba(255, 255, 255, 0.05);
            padding: 1.5rem;
            border-radius: 10px;
            margin-top: 1rem;
        }
        
        .detail-item {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid rgba(34, 139, 34, 0.1);
        }
        
        .detail-item:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            color: rgba(255, 255, 255, 0.7);
        }
        
        .detail-value {
            color: #ffd700;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <div class="test-header">
            <h1><i class="fas fa-plug"></i> Database Connection Test</h1>
            <p style="color: rgba(255, 255, 255, 0.8);">Testing connection to Breakthrough Trading database</p>
        </div>

        <?php
        // Database configuration
        $host = 'localhost';
        $username = 'root';
        $password = '';
        $database = 'breakthrough_trading';
        
        $tests = [];
        
        // Test 1: MySQL Server Connection
        try {
            $pdo_server = new PDO("mysql:host=$host", $username, $password);
            $tests[] = [
                'name' => 'MySQL Server Connection',
                'status' => 'success',
                'message' => 'Successfully connected to MySQL server'
            ];
            
            // Get MySQL version
            $version = $pdo_server->query('SELECT VERSION()')->fetchColumn();
            $tests[] = [
                'name' => 'MySQL Version',
                'status' => 'info',
                'message' => "MySQL Version: $version"
            ];
            
        } catch(PDOException $e) {
            $tests[] = [
                'name' => 'MySQL Server Connection',
                'status' => 'error',
                'message' => 'Failed to connect to MySQL server: ' . $e->getMessage()
            ];
        }
        
        // Test 2: Database Connection
        if (isset($pdo_server)) {
            try {
                $pdo_db = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
                $pdo_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                $tests[] = [
                    'name' => 'Database Connection',
                    'status' => 'success',
                    'message' => "Successfully connected to database '$database'"
                ];
                
                // Test 3: Check Tables
                $tables_query = $pdo_db->query("SHOW TABLES");
                $tables = $tables_query->fetchAll(PDO::FETCH_COLUMN);
                
                if (count($tables) > 0) {
                    $tests[] = [
                        'name' => 'Database Tables',
                        'status' => 'success',
                        'message' => 'Found ' . count($tables) . ' tables: ' . implode(', ', array_slice($tables, 0, 5)) . (count($tables) > 5 ? '...' : '')
                    ];
                    
                    // Test 4: Check Users Table
                    if (in_array('users', $tables)) {
                        $user_count = $pdo_db->query("SELECT COUNT(*) FROM users")->fetchColumn();
                        $tests[] = [
                            'name' => 'Users Table',
                            'status' => 'success',
                            'message' => "Users table contains $user_count records"
                        ];
                        
                        // Check for admin user
                        $admin_check = $pdo_db->query("SELECT COUNT(*) FROM users WHERE user_type = 'admin'")->fetchColumn();
                        if ($admin_check > 0) {
                            $tests[] = [
                                'name' => 'Admin Account',
                                'status' => 'success',
                                'message' => "Found $admin_check admin account(s)"
                            ];
                        } else {
                            $tests[] = [
                                'name' => 'Admin Account',
                                'status' => 'error',
                                'message' => 'No admin accounts found'
                            ];
                        }
                    }
                    
                    // Test 5: Check Trading Levels
                    if (in_array('trading_levels', $tables)) {
                        $levels_count = $pdo_db->query("SELECT COUNT(*) FROM trading_levels")->fetchColumn();
                        $tests[] = [
                            'name' => 'Trading Levels',
                            'status' => 'success',
                            'message' => "Trading levels table contains $levels_count levels"
                        ];
                    }
                    
                } else {
                    $tests[] = [
                        'name' => 'Database Tables',
                        'status' => 'error',
                        'message' => 'No tables found in database. Run setup first.'
                    ];
                }
                
            } catch(PDOException $e) {
                $tests[] = [
                    'name' => 'Database Connection',
                    'status' => 'error',
                    'message' => "Failed to connect to database '$database': " . $e->getMessage()
                ];
            }
        }
        
        // Display test results
        foreach ($tests as $test) {
            $class = 'test-' . $test['status'];
            $icon = $test['status'] === 'success' ? 'check-circle' : 
                   ($test['status'] === 'error' ? 'times-circle' : 'info-circle');
            
            echo "<div class='test-result $class'>";
            echo "<h4><i class='fas fa-$icon'></i> {$test['name']}</h4>";
            echo "<p>{$test['message']}</p>";
            echo "</div>";
        }
        ?>

        <div class="connection-details">
            <h4 style="color: #ffd700; margin-bottom: 1rem;"><i class="fas fa-cog"></i> Connection Details</h4>
            <div class="detail-item">
                <span class="detail-label">Host:</span>
                <span class="detail-value"><?php echo $host; ?></span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Username:</span>
                <span class="detail-value"><?php echo $username; ?></span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Database:</span>
                <span class="detail-value"><?php echo $database; ?></span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Charset:</span>
                <span class="detail-value">utf8mb4</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Purpose:</span>
                <span class="detail-value">Ethiopian Birr Trading</span>
            </div>
        </div>

        <div style="text-align: center; margin-top: 2rem;">
            <a href="setup.php" class="btn-primary" style="margin-right: 1rem;">
                <i class="fas fa-tools"></i> Run Setup
            </a>
            <a href="../index.html" class="btn-outline">
                <i class="fas fa-home"></i> Back to Home
            </a>
        </div>
    </div>
</body>
</html>