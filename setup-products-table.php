<?php
/**
 * Setup Products Table
 * Creates the products table for admin product management
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Setup Products Table - Concordial Nexus</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 900px; margin: 50px auto; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .container { background: white; padding: 40px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
        h1 { color: #27ae60; text-align: center; margin-bottom: 30px; }
        .success { background: #d4edda; color: #155724; padding: 20px; border-radius: 10px; margin: 20px 0; border-left: 5px solid #28a745; }
        .error { background: #f8d7da; color: #721c24; padding: 20px; border-radius: 10px; margin: 20px 0; border-left: 5px solid #dc3545; }
        .info { background: #d1ecf1; color: #0c5460; padding: 20px; border-radius: 10px; margin: 20px 0; border-left: 5px solid #17a2b8; }
        .btn { display: inline-block; background: #27ae60; color: white; padding: 15px 30px; border-radius: 8px; text-decoration: none; font-weight: bold; margin: 10px 5px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        th { background: #27ae60; color: white; }
    </style>
</head>
<body>
<div class="container">
    <h1>🛍️ Setup Products Table</h1>
    
<?php
try {
    $pdo = new PDO("mysql:host=localhost;dbname=concordial_nexus;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<div class='info'><strong>📡 Database Connection:</strong> Connected successfully</div>";
    
    // Check if table exists
    $table_check = $pdo->query("SHOW TABLES LIKE 'products'")->fetch();
    
    if ($table_check) {
        echo "<div class='success'><h2>✅ Table Already Exists!</h2><p>The 'products' table already exists.</p></div>";
    } else {
        // Create products table
        $sql = "
        CREATE TABLE products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            category VARCHAR(100) DEFAULT 'investment',
            price DECIMAL(15,2) NOT NULL,
            min_investment DECIMAL(15,2) DEFAULT NULL,
            max_investment DECIMAL(15,2) DEFAULT NULL,
            return_percentage DECIMAL(5,2) DEFAULT NULL,
            duration_days INT DEFAULT NULL,
            image_url VARCHAR(500) DEFAULT NULL,
            status ENUM('active', 'inactive') DEFAULT 'active',
            featured TINYINT(1) DEFAULT 0,
            created_by INT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY created_by (created_by),
            KEY status (status),
            KEY category (category),
            CONSTRAINT products_ibfk_1 FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        $pdo->exec($sql);
        
        echo "<div class='success'><h2>🎉 Success!</h2><p>The 'products' table has been created successfully!</p></div>";
    }
    
    // Show table structure
    echo "<h3>📋 Table Structure:</h3>";
    $columns = $pdo->query("DESCRIBE products")->fetchAll();
    echo "<table><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td><strong>" . htmlspecialchars($col['Field']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<div class='info'>";
    echo "<h3>✅ Product Management Features:</h3>";
    echo "<ul>";
    echo "<li>✓ Add new products/investment packages</li>";
    echo "<li>✓ Edit existing products</li>";
    echo "<li>✓ Delete products</li>";
    echo "<li>✓ Set product status (active/inactive)</li>";
    echo "<li>✓ Feature products on homepage</li>";
    echo "<li>✓ Track who created each product</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div style='text-align: center; margin-top: 30px;'>";
    echo "<a href='../admin/products.php' class='btn'>🛍️ Manage Products</a>";
    echo "<a href='../admin/dashboard.php' class='btn' style='background: #4a90e2;'>🏠 Admin Dashboard</a>";
    echo "</div>";
    
} catch(PDOException $e) {
    echo "<div class='error'>";
    echo "<h2>❌ Database Error</h2>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>

</div>
</body>
</html>
