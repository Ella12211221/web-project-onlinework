<?php
// Add Admin Department Column - Concordial Nexus
echo "<h1>🔧 Adding Admin Department Column</h1>";
echo "<p>Adding the missing admin_department column to the users table...</p>";

try {
    $pdo = new PDO("mysql:host=localhost;dbname=concordial_nexus;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<div style='background: #e7f3ff; color: #2c5aa0; padding: 15px; border-radius: 8px; margin: 10px 0;'>";
    echo "✅ <strong>Database Connection Successful!</strong><br>";
    echo "Connected to: <strong>concordial_nexus</strong> database";
    echo "</div>";
    
    // Check if admin_department column exists
    echo "<h3>🔍 Checking admin_department column...</h3>";
    
    $columns = $pdo->query("DESCRIBE users")->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('admin_department', $columns)) {
        echo "<p style='color: orange;'>⚠️ admin_department column missing, adding it...</p>";
        
        // Add admin_department column
        $pdo->exec("ALTER TABLE users ADD COLUMN admin_department ENUM('administration','trading','finance','customer_service','compliance','it') DEFAULT NULL COMMENT 'Admin department for organizational structure'");
        
        echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin: 10px 0;'>";
        echo "✅ <strong>Successfully added admin_department column!</strong><br>";
        echo "Column type: ENUM with departments<br>";
        echo "Available values: administration, trading, finance, customer_service, compliance, it";
        echo "</div>";
    } else {
        echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin: 10px 0;'>";
        echo "✅ admin_department column already exists";
        echo "</div>";
    }
    
    // Verify the column was added correctly
    echo "<h3>📋 Verifying Column Structure</h3>";
    
    $column_info = $pdo->query("SHOW COLUMNS FROM users LIKE 'admin_department'")->fetch();
    
    if ($column_info) {
        echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 10px 0; border-left: 4px solid #28a745;'>";
        echo "<strong>Column Details:</strong><br>";
        echo "Field: " . $column_info['Field'] . "<br>";
        echo "Type: " . $column_info['Type'] . "<br>";
        echo "Null: " . $column_info['Null'] . "<br>";
        echo "Default: " . ($column_info['Default'] ?: 'NULL') . "<br>";
        echo "</div>";
    }
    
    // Show current admin users and their departments
    echo "<h3>👥 Current Admin Users</h3>";
    
    $admins = $pdo->query("SELECT id, full_name, email, admin_department FROM users WHERE user_type = 'admin'")->fetchAll();
    
    if ($admins) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
        echo "<tr style='background: #f8f9fa;'><th>ID</th><th>Name</th><th>Email</th><th>Department</th></tr>";
        
        foreach ($admins as $admin) {
            echo "<tr>";
            echo "<td>" . $admin['id'] . "</td>";
            echo "<td>" . htmlspecialchars($admin['full_name']) . "</td>";
            echo "<td>" . htmlspecialchars($admin['email']) . "</td>";
            echo "<td>" . ($admin['admin_department'] ? htmlspecialchars($admin['admin_department']) : '<em>Not set</em>') . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>No admin users found.</p>";
    }
    
    echo "<div style='background: #d4edda; color: #155724; padding: 20px; border-radius: 10px; margin: 20px 0; text-align: center;'>";
    echo "<h3 style='margin: 0 0 10px 0;'>🎉 Admin Department Column Added Successfully!</h3>";
    echo "<p style='margin: 0;'>The admin profile form will now work correctly with department selection.</p>";
    echo "</div>";
    
    echo "<div style='background: #e7f3ff; color: #2c5aa0; padding: 15px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h4>📋 Available Departments:</h4>";
    echo "<ul style='margin: 10px 0;'>";
    echo "<li><strong>administration</strong> - General administrative tasks</li>";
    echo "<li><strong>trading</strong> - Trading platform management</li>";
    echo "<li><strong>finance</strong> - Financial oversight</li>";
    echo "<li><strong>customer_service</strong> - User support and assistance</li>";
    echo "<li><strong>compliance</strong> - Regulatory compliance</li>";
    echo "<li><strong>it</strong> - Technical infrastructure</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div style='text-align: center; margin: 30px 0;'>";
    echo "<a href='admin/profile.php' style='background: #4a90e2; color: white; padding: 15px 30px; border-radius: 8px; text-decoration: none; margin: 10px; font-weight: bold;'>🛡️ Test Admin Profile</a>";
    echo "<a href='admin/dashboard.php' style='background: #28a745; color: white; padding: 15px 30px; border-radius: 8px; text-decoration: none; margin: 10px; font-weight: bold;'>📊 Admin Dashboard</a>";
    echo "</div>";
    
} catch(PDOException $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin: 10px 0;'>";
    echo "❌ <strong>Database Error!</strong><br>";
    echo "Error: " . $e->getMessage() . "<br><br>";
    echo "<strong>Possible Solutions:</strong><br>";
    echo "1. Make sure your database server (MySQL/XAMPP) is running<br>";
    echo "2. Verify the database 'concordial_nexus' exists<br>";
    echo "3. Check database credentials (host, username, password)<br>";
    echo "4. Import the database: <code>database/concordial_nexus_complete.sql</code>";
    echo "</div>";
}

echo "<footer style='text-align: center; margin: 40px 0; color: #666;'>";
echo "<p>© 2026 Concordial Nexus - Ethiopian Trading Platform</p>";
echo "</footer>";
?>