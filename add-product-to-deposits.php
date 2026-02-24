<?php
/**
 * Add product_id column to deposits table
 */

try {
    $pdo = new PDO("mysql:host=localhost;dbname=concordial_nexus;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Adding product_id to deposits table...</h2>";
    
    // Check if column already exists
    $check = $pdo->query("SHOW COLUMNS FROM deposits LIKE 'product_id'")->fetch();
    
    if ($check) {
        echo "<p style='color: orange;'>Column 'product_id' already exists!</p>";
    } else {
        // Add product_id column
        $pdo->exec("ALTER TABLE deposits ADD COLUMN product_id INT DEFAULT NULL AFTER user_id");
        $pdo->exec("ALTER TABLE deposits ADD FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL");
        
        echo "<p style='color: green;'>✅ Column 'product_id' added successfully!</p>";
        echo "<p style='color: green;'>✅ Foreign key constraint added!</p>";
    }
    
    echo "<p><a href='../dashboard/deposit.php'>Go to Deposit Page</a></p>";
    
} catch(PDOException $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>
