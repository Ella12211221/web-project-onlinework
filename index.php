<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Management - Breakthrough Trading</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .db-container {
            max-width: 1000px;
            margin: 120px auto 50px;
            padding: 3rem;
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            border: 1px solid rgba(34, 139, 34, 0.2);
            box-shadow: 0 15px 35px rgba(34, 139, 34, 0.1);
        }
        
        .db-header {
            text-align: center;
            margin-bottom: 3rem;
        }
        
        .db-header h1 {
            color: #fff;
            margin-bottom: 1rem;
            background: linear-gradient(45deg, #228b22, #ffd700);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 3rem;
        }
        
        .tools-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        .tool-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            padding: 2.5rem;
            border-radius: 20px;
            border: 1px solid rgba(34, 139, 34, 0.2);
            text-align: center;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        
        .tool-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #228b22, #ffd700);
        }
        
        .tool-card:hover {
            transform: translateY(-10px);
            border-color: rgba(34, 139, 34, 0.5);
            box-shadow: 0 20px 40px rgba(34, 139, 34, 0.2);
        }
        
        .tool-icon {
            font-size: 3rem;
            background: linear-gradient(45deg, #228b22, #ffd700);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1.5rem;
            display: block;
        }
        
        .tool-card h3 {
            color: #ffd700;
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }
        
        .tool-card p {
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        
        .tool-btn {
            background: linear-gradient(45deg, #228b22, #ffd700);
            color: #0a0e1a;
            padding: 1rem 2rem;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .tool-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(34, 139, 34, 0.4);
        }
        
        .status-section {
            background: rgba(255, 255, 255, 0.05);
            padding: 2rem;
            border-radius: 15px;
            border: 1px solid rgba(34, 139, 34, 0.2);
            margin-bottom: 2rem;
        }
        
        .status-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid rgba(34, 139, 34, 0.1);
        }
        
        .status-item:last-child {
            border-bottom: none;
        }
        
        .status-label {
            color: #fff;
            font-weight: 600;
        }
        
        .status-success {
            color: #228b22;
        }
        
        .status-error {
            color: #ff5252;
        }
        
        .quick-links {
            text-align: center;
            margin-top: 2rem;
        }
        
        .quick-links a {
            color: #ffd700;
            text-decoration: none;
            margin: 0 1rem;
            padding: 0.5rem 1rem;
            border: 1px solid rgba(255, 215, 0, 0.3);
            border-radius: 5px;
            transition: all 0.3s;
        }
        
        .quick-links a:hover {
            background: rgba(255, 215, 0, 0.1);
            border-color: rgba(255, 215, 0, 0.5);
        }
    </style>
</head>
<body>
    <div class="db-container">
        <div class="db-header">
            <h1><i class="fas fa-database"></i> Database Management</h1>
            <p style="color: rgba(255, 255, 255, 0.8);">Breakthrough Online Trading Database Tools</p>
        </div>

        <?php
        // Quick status check
        $host = 'localhost';
        $username = 'root';
        $password = '';
        $database = 'breakthrough_trading';
        
        $mysql_status = false;
        $db_status = false;
        $tables_count = 0;
        
        try {
            $pdo_check = new PDO("mysql:host=$host", $username, $password);
            $mysql_status = true;
            
            try {
                $pdo_db = new PDO("mysql:host=$host;dbname=$database", $username, $password);
                $db_status = true;
                
                $tables_result = $pdo_db->query("SHOW TABLES");
                $tables_count = $tables_result->rowCount();
            } catch(PDOException $e) {
                // Database doesn't exist
            }
        } catch(PDOException $e) {
            // MySQL not available
        }
        ?>

        <div class="status-section">
            <h3 style="color: #ffd700; margin-bottom: 1rem;"><i class="fas fa-heartbeat"></i> System Status</h3>
            
            <div class="status-item">
                <span class="status-label">MySQL Server</span>
                <span class="<?php echo $mysql_status ? 'status-success' : 'status-error'; ?>">
                    <i class="fas fa-<?php echo $mysql_status ? 'check-circle' : 'times-circle'; ?>"></i>
                    <?php echo $mysql_status ? 'Running' : 'Offline'; ?>
                </span>
            </div>
            
            <div class="status-item">
                <span class="status-label">Database</span>
                <span class="<?php echo $db_status ? 'status-success' : 'status-error'; ?>">
                    <i class="fas fa-<?php echo $db_status ? 'check-circle' : 'times-circle'; ?>"></i>
                    <?php echo $db_status ? 'Connected' : 'Not Found'; ?>
                </span>
            </div>
            
            <div class="status-item">
                <span class="status-label">Tables</span>
                <span class="<?php echo $tables_count > 0 ? 'status-success' : 'status-error'; ?>">
                    <i class="fas fa-table"></i>
                    <?php echo $tables_count; ?> tables
                </span>
            </div>
        </div>

        <div class="tools-grid">
            <div class="tool-card">
                <i class="fas fa-tools tool-icon"></i>
                <h3>Database Setup</h3>
                <p>Complete database initialization with 14 optimized tables for Ethiopian Birr trading, sample data, and admin account creation.</p>
                <a href="setup.php" class="tool-btn">
                    <i class="fas fa-play"></i> Run Setup
                </a>
            </div>
            
            <div class="tool-card">
                <i class="fas fa-plug tool-icon"></i>
                <h3>Connection Test</h3>
                <p>Test database connectivity and verify all components are working properly for the trading platform.</p>
                <a href="test-connection.php" class="tool-btn">
                    <i class="fas fa-check"></i> Test Connection
                </a>
            </div>
            
            <div class="tool-card">
                <i class="fas fa-user-shield tool-icon"></i>
                <h3>Create Admin</h3>
                <p>Create or update admin accounts with custom credentials for Ethiopian Birr trading platform management.</p>
                <a href="create-admin.php" class="tool-btn">
                    <i class="fas fa-user-plus"></i> Create Admin
                </a>
            </div>
        </div>

        <div class="status-section">
            <h3 style="color: #ffd700; margin-bottom: 1rem;"><i class="fas fa-info-circle"></i> Database Information</h3>
            <div style="color: rgba(255, 255, 255, 0.8); line-height: 1.8;">
                <p><strong>Database Name:</strong> breakthrough_trading</p>
                <p><strong>Default Admin:</strong> elias@gmail.com / admin123</p>
                <p><strong>Currency:</strong> Ethiopian Birr (Br)</p>
                <p><strong>Trading Levels:</strong> Level 1 (Br1,000-3,000), Level 2 (Br10,000-30,000), Level 3 (Br300,000+)</p>
                <p><strong>Tables:</strong> 14 optimized tables for trading platform</p>
                <p><strong>Features:</strong> User management, investments, transactions, portfolio tracking, market data, notifications</p>
            </div>
        </div>

        <div class="quick-links">
            <a href="../index.html"><i class="fas fa-home"></i> Website Home</a>
            <a href="../auth/login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
            <a href="../auth/register.php"><i class="fas fa-user-plus"></i> Register</a>
        </div>
    </div>
</body>
</html>