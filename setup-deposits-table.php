<?php
/**
 * Setup Deposits Table
 * Creates the deposits table for managing user deposit requests
 */

echo "<!DOCTYPE html>
<html>
<head>
    <title>Setup Deposits Table - Concordial Nexus</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 900px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h2 { color: #2c3e50; border-bottom: 3px solid #27ae60; padding-bottom: 10px; }
        h3 { color: #27ae60; margin-top: 30px; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 15px 0; border-left: 4px solid #28a745; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 15px 0; border-left: 4px solid #dc3545; }
        .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 5px; margin: 15px 0; border-left: 4px solid #17a2b8; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border: 1px solid #ddd; }
        th { background: #27ae60; color: white; }
        tr:nth-child(even) { background: #f8f9fa; }
        .links { margin-top: 30px; }
        .links a { display: inline-block; background: #27ae60; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; margin: 5px; }
        .links a:hover { background: #229954; }
        .code { background: #f4f4f4; padding: 10px; border-radius: 5px; font-family: monospace; margin: 10px 0; }
    </style>
</head>
<body>
<div class='container'>";

try {
    $pdo = new PDO("mysql:host=localhost;dbname=concordial_nexus;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>🚀 Setting up Deposits Table...</h2>";
    
    // Check if table already exists
    $table_exists = $pdo->query("SHOW TABLES LIKE 'deposits'")->fetch();
    
    if ($table_exists) {
        echo "<div class='info'>ℹ️ <strong>Table Already Exists:</strong> The 'deposits' table already exists in the database. Skipping creation.</div>";
    } else {
        // Create deposits table
        $create_table = "
        CREATE TABLE IF NOT EXISTS deposits (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            amount DECIMAL(15,2) NOT NULL,
            payment_method VARCHAR(50) NOT NULL,
            payment_service VARCHAR(100) DEFAULT NULL,
            payment_reference VARCHAR(255) NOT NULL UNIQUE,
            bank_name VARCHAR(100) DEFAULT NULL,
            account_number VARCHAR(100) DEFAULT NULL,
            mobile_number VARCHAR(20) DEFAULT NULL,
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            admin_notes TEXT DEFAULT NULL,
            approved_by INT DEFAULT NULL,
            approved_at DATETIME DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_user_id (user_id),
            INDEX idx_status (status),
            INDEX idx_payment_reference (payment_reference),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        $pdo->exec($create_table);
        echo "<div class='success'>✅ <strong>Success!</strong> Deposits table created successfully!</div>";
    }
    
    // Verify table exists
    $check = $pdo->query("SHOW TABLES LIKE 'deposits'")->fetch();
    if ($check) {
        echo "<div class='success'>✅ <strong>Verified:</strong> Table 'deposits' exists in database</div>";
        
        // Show table structure
        echo "<h3>📋 Table Structure:</h3>";
        $columns = $pdo->query("DESCRIBE deposits")->fetchAll();
        echo "<table>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        foreach ($columns as $col) {
            echo "<tr>";
            echo "<td><strong>" . htmlspecialchars($col['Field']) . "</strong></td>";
            echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($col['Default'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($col['Extra']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Show record count
        $count = $pdo->query("SELECT COUNT(*) FROM deposits")->fetchColumn();
        echo "<div class='info'>📊 <strong>Current Records:</strong> {$count} deposit(s) in the database</div>";
    }
    
    echo "<h3>✅ Setup Complete!</h3>";
    echo "<div class='success'>";
    echo "<p><strong>The deposits table is ready to use!</strong></p>";
    echo "<p>You can now:</p>";
    echo "<ul>";
    echo "<li>Users can submit deposit requests with payment references</li>";
    echo "<li>Admins can approve or reject deposits</li>";
    echo "<li>Approved deposits automatically update user balances</li>";
    echo "<li>Complete audit trail is maintained</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div class='links'>";
    echo "<h3>🔗 Quick Access Links:</h3>";
    echo "<a href='../dashboard/deposit.php'>💰 User Deposit Page</a>";
    echo "<a href='../admin/deposits.php'>👨‍💼 Admin Deposit Management</a>";
    echo "<a href='../dashboard/index.php'>📊 User Dashboard</a>";
    echo "<a href='../admin/dashboard.php'>🛡️ Admin Dashboard</a>";
    echo "</div>";
    
    echo "<div class='info' style='margin-top: 30px;'>";
    echo "<h4>📖 Next Steps:</h4>";
    echo "<ol>";
    echo "<li><strong>Test User Deposit:</strong> Login as a user and submit a test deposit</li>";
    echo "<li><strong>Test Admin Approval:</strong> Login as admin and approve the deposit</li>";
    echo "<li><strong>Verify Balance Update:</strong> Check that user balance increased</li>";
    echo "</ol>";
    echo "</div>";
    
} catch(PDOException $e) {
    echo "<div class='error'>";
    echo "<h3>❌ Database Error</h3>";
    echo "<p><strong>Error Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Error Code:</strong> " . $e->getCode() . "</p>";
    echo "</div>";
    
    echo "<div class='info'>";
    echo "<h4>🔧 Troubleshooting:</h4>";
    echo "<ul>";
    echo "<li><strong>Check Database Connection:</strong> Verify database credentials in <code>config/database.php</code></li>";
    echo "<li><strong>Database Exists:</strong> Make sure 'concordial_nexus' database exists</li>";
    echo "<li><strong>Users Table:</strong> The 'users' table must exist (required for foreign keys)</li>";
    echo "<li><strong>Permissions:</strong> Database user needs CREATE TABLE permissions</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div class='code'>";
    echo "<strong>Database Connection Details:</strong><br>";
    echo "Host: localhost<br>";
    echo "Database: concordial_nexus<br>";
    echo "User: root<br>";
    echo "Password: (empty)<br>";
    echo "</div>";
}

echo "</div></body></html>";
?>
