<?php
/**
 * UPDATE VIP PRODUCTS - Add New VIP Levels
 * VIP One Level 1: Br1,000 - 5% return
 * VIP One Level 2: Br2,000 - 7% return
 * VIP One Level 3: Br3,000 - 10% return
 * Plus VIP Two and VIP Three levels
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html>
<head>
    <title>Update VIP Products - Concordial Nexus</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea, #764ba2);
            padding: 40px;
            margin: 0;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 { color: #2c3e50; margin-bottom: 30px; }
        h2 { color: #34495e; margin-top: 30px; border-bottom: 3px solid #667eea; padding-bottom: 10px; }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
            border-left: 5px solid #28a745;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
            border-left: 5px solid #17a2b8;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
            border-left: 5px solid #dc3545;
        }
        .product {
            background: #f8f9fa;
            padding: 15px;
            margin: 10px 0;
            border-radius: 8px;
            border-left: 5px solid #e74c3c;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .product.vip-one { border-left-color: #e74c3c; }
        .product.vip-two { border-left-color: #f39c12; }
        .product.vip-three { border-left-color: #9b59b6; }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            margin: 10px 5px;
            font-weight: 600;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #667eea;
            color: white;
        }
        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .badge-vip-one { background: #fee; color: #c00; }
        .badge-vip-two { background: #ffeaa7; color: #d63031; }
        .badge-vip-three { background: #e8daef; color: #6c3483; }
    </style>
</head>
<body>
<div class='container'>";

echo "<h1>🌟 Update VIP Products</h1>";

try {
    $pdo = new PDO("mysql:host=localhost;dbname=concordial_nexus;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<div class='success'>✅ Connected to database: concordial_nexus</div>";
    
    // STEP 1: Check if products table exists
    echo "<h2>📋 Step 1: Checking Products Table</h2>";
    
    $table_check = $pdo->query("SHOW TABLES LIKE 'products'")->fetch();
    
    if (!$table_check) {
        echo "<div class='error'>❌ Products table doesn't exist. Please run setup-all-products-complete.php first.</div>";
        exit;
    }
    
    echo "<div class='success'>✅ Products table exists</div>";
    
    // STEP 2: Update table structure to include new VIP levels and referral requirements
    echo "<h2>🔧 Step 2: Updating Table Structure</h2>";
    
    try {
        $pdo->exec("ALTER TABLE products MODIFY COLUMN category ENUM('regular', 'premium', 'vip', 'vip_one', 'vip_two', 'vip_three') NOT NULL");
        echo "<div class='success'>✅ Updated category column to include VIP One, VIP Two, VIP Three</div>";
    } catch(PDOException $e) {
        echo "<div class='info'>ℹ️ Category column already updated or doesn't need update</div>";
    }
    
    // Add referral requirement columns if they don't exist
    try {
        $pdo->exec("ALTER TABLE products ADD COLUMN min_referrals INT DEFAULT 0 AFTER duration_days");
        echo "<div class='success'>✅ Added min_referrals column</div>";
    } catch(PDOException $e) {
        echo "<div class='info'>ℹ️ min_referrals column already exists</div>";
    }
    
    try {
        $pdo->exec("ALTER TABLE products ADD COLUMN has_own_commission TINYINT(1) DEFAULT 0 AFTER min_referrals");
        echo "<div class='success'>✅ Added has_own_commission column</div>";
    } catch(PDOException $e) {
        echo "<div class='info'>ℹ️ has_own_commission column already exists</div>";
    }
    
    // STEP 3: Delete old VIP products
    echo "<h2>🗑️ Step 3: Removing Old VIP Products</h2>";
    
    $deleted = $pdo->exec("DELETE FROM products WHERE category = 'vip'");
    echo "<div class='info'>Removed {$deleted} old VIP products</div>";
    
    // STEP 4: Add new VIP products
    echo "<h2>✨ Step 4: Adding New VIP Products</h2>";
    
    $vip_products = [
        // VIP ONE - Entry Level VIP (3 levels)
        ['VIP One Level 1 - Br1,000', 'vip_one', 1000, 1000, 5, 30, 0, 0, 'VIP One entry level - 1 month'],
        ['VIP One Level 2 - Br2,000', 'vip_one', 2000, 2000, 7, 30, 0, 0, 'VIP One intermediate - 1 month'],
        ['VIP One Level 3 - Br3,000', 'vip_one', 3000, 3000, 10, 30, 0, 0, 'VIP One advanced - 1 month'],
        
        // VIP TWO - Mid Level VIP (3 levels)
        ['VIP Two Level 1 - Br4,000', 'vip_two', 4000, 4000, 15, 60, 0, 0, 'VIP Two entry level - 2 months'],
        ['VIP Two Level 2 - Br8,500', 'vip_two', 8500, 8500, 20, 60, 0, 0, 'VIP Two intermediate - 2 months'],
        ['VIP Two Level 3 - Br15,000', 'vip_two', 15000, 15000, 25, 60, 0, 0, 'VIP Two advanced - 2 months'],
        
        // VIP THREE - High Level VIP (4 levels with referral requirements)
        ['VIP Three Level 1 - Br25,000', 'vip_three', 25000, 25000, 30, 180, 15, 1, 'VIP Three entry - 6 months, requires 15 referrals with own commission'],
        ['VIP Three Level 2 - Br75,000', 'vip_three', 75000, 75000, 33, 270, 25, 1, 'VIP Three intermediate - 9 months, requires 25+ referrals with own commission'],
        ['VIP Three Level 3 - Br100,000', 'vip_three', 100000, 100000, 35, 365, 0, 0, 'VIP Three advanced - 12 months'],
        ['VIP Three Level 4 - Br125,000', 'vip_three', 125000, 125000, 37, 365, 0, 0, 'VIP Three elite - 12 months']
    ];
    
    $stmt = $pdo->prepare("INSERT INTO products (name, category, min_amount, max_amount, return_percentage, duration_days, min_referrals, has_own_commission, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $count = 0;
    foreach ($vip_products as $product) {
        $stmt->execute($product);
        $count++;
        $product_id = $pdo->lastInsertId();
        
        $category_class = str_replace('_', '-', $product[1]);
        $badge_class = 'badge-' . str_replace('_', '-', $product[1]);
        
        echo "<div class='product {$category_class}'>";
        echo "<div>";
        echo "<span class='badge {$badge_class}'>" . strtoupper(str_replace('_', ' ', $product[1])) . "</span><br>";
        echo "<strong>{$product[0]}</strong><br>";
        echo "Amount: Br " . number_format($product[2]) . " | ";
        echo "Return: {$product[4]}% | ";
        echo "Duration: {$product[5]} days | ";
        echo "Profit: Br " . number_format($product[2] * $product[4] / 100);
        if ($product[6] > 0) {
            echo "<br><span style='color: #e74c3c; font-weight: bold;'>⚠️ Requires {$product[6]} referrals</span>";
        }
        if ($product[7] == 1) {
            echo "<br><span style='color: #27ae60; font-weight: bold;'>✅ Own commission enabled</span>";
        }
        echo "</div>";
        echo "<div style='background: #e8f5e9; padding: 10px; border-radius: 5px; font-family: monospace;'>ID: {$product_id}</div>";
        echo "</div>";
    }
    
    echo "<div class='success'>✅ Added {$count} new VIP products</div>";
    
    // STEP 5: Display all products by category
    echo "<h2>📊 Step 5: All Products Summary</h2>";
    
    $all_products = $pdo->query("SELECT * FROM products ORDER BY 
        CASE category 
            WHEN 'regular' THEN 1 
            WHEN 'premium' THEN 2 
            WHEN 'vip_one' THEN 3 
            WHEN 'vip_two' THEN 4 
            WHEN 'vip_three' THEN 5 
        END, min_amount ASC")->fetchAll();
    
    echo "<table>";
    echo "<tr><th>Category</th><th>Product Name</th><th>Amount</th><th>Return</th><th>Duration</th><th>Profit</th><th>Referrals</th><th>Link</th></tr>";
    
    foreach ($all_products as $p) {
        $link = "dashboard/product-details.php?id=" . $p['id'];
        $profit = $p['min_amount'] * $p['return_percentage'] / 100;
        
        $category_colors = [
            'regular' => '#3498db',
            'premium' => '#9b59b6',
            'vip_one' => '#e74c3c',
            'vip_two' => '#f39c12',
            'vip_three' => '#8e44ad'
        ];
        
        $color = $category_colors[$p['category']] ?? '#95a5a6';
        
        echo "<tr>";
        echo "<td><span class='badge' style='background: {$color}; color: white;'>" . strtoupper(str_replace('_', ' ', $p['category'])) . "</span></td>";
        echo "<td style='font-weight: bold;'>" . htmlspecialchars($p['name']) . "</td>";
        echo "<td>Br " . number_format($p['min_amount']) . "</td>";
        echo "<td style='color: #27ae60; font-weight: bold;'>{$p['return_percentage']}%</td>";
        echo "<td>{$p['duration_days']} days</td>";
        echo "<td style='color: #e74c3c; font-weight: bold;'>Br " . number_format($profit) . "</td>";
        
        // Referral requirements
        $ref_text = '-';
        if (isset($p['min_referrals']) && $p['min_referrals'] > 0) {
            $ref_text = $p['min_referrals'] . ' refs';
            if (isset($p['has_own_commission']) && $p['has_own_commission'] == 1) {
                $ref_text .= ' + own comm.';
            }
        }
        echo "<td style='font-size: 0.85rem;'>{$ref_text}</td>";
        
        echo "<td><code style='background: #f0f0f0; padding: 5px; border-radius: 3px; font-size: 0.8rem;'>?id={$p['id']}</code></td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    // STEP 6: Category Summary
    echo "<h2>📈 Step 6: Category Breakdown</h2>";
    
    $categories = $pdo->query("SELECT 
        category, 
        COUNT(*) as count, 
        MIN(min_amount) as min, 
        MAX(max_amount) as max,
        MIN(return_percentage) as min_return,
        MAX(return_percentage) as max_return,
        MIN(duration_days) as min_days,
        MAX(duration_days) as max_days
        FROM products 
        GROUP BY category 
        ORDER BY CASE category 
            WHEN 'regular' THEN 1 
            WHEN 'premium' THEN 2 
            WHEN 'vip_one' THEN 3 
            WHEN 'vip_two' THEN 4 
            WHEN 'vip_three' THEN 5 
        END")->fetchAll();
    
    foreach ($categories as $cat) {
        $category_colors = [
            'regular' => ['bg' => '#e3f2fd', 'border' => '#2196f3', 'text' => '#1565c0'],
            'premium' => ['bg' => '#f3e5f5', 'border' => '#9c27b0', 'text' => '#6a1b9a'],
            'vip_one' => ['bg' => '#ffebee', 'border' => '#f44336', 'text' => '#c62828'],
            'vip_two' => ['bg' => '#fff3e0', 'border' => '#ff9800', 'text' => '#e65100'],
            'vip_three' => ['bg' => '#ede7f6', 'border' => '#673ab7', 'text' => '#4527a0']
        ];
        
        $colors = $category_colors[$cat['category']] ?? ['bg' => '#f5f5f5', 'border' => '#9e9e9e', 'text' => '#424242'];
        
        echo "<div style='background: {$colors['bg']}; border-left: 5px solid {$colors['border']}; padding: 20px; margin: 15px 0; border-radius: 8px;'>";
        echo "<h3 style='color: {$colors['text']}; margin: 0 0 15px 0;'>" . strtoupper(str_replace('_', ' ', $cat['category'])) . " PACKAGES</h3>";
        echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;'>";
        
        echo "<div>";
        echo "<strong style='color: {$colors['text']};'>Products:</strong> {$cat['count']}<br>";
        echo "<strong style='color: {$colors['text']};'>Amount Range:</strong> Br " . number_format($cat['min']) . " - Br " . number_format($cat['max']);
        echo "</div>";
        
        echo "<div>";
        echo "<strong style='color: {$colors['text']};'>Return Range:</strong> {$cat['min_return']}% - {$cat['max_return']}%<br>";
        echo "<strong style='color: {$colors['text']};'>Duration:</strong> {$cat['min_days']} - {$cat['max_days']} days";
        echo "</div>";
        
        echo "</div>";
        echo "</div>";
    }
    
    // STEP 7: Final Summary
    $total_products = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    
    echo "<div class='success' style='margin-top: 30px; padding: 30px;'>";
    echo "<h3>✅ VIP Products Update Complete!</h3>";
    echo "<p><strong>Total Products:</strong> {$total_products}</p>";
    echo "<p><strong>VIP One:</strong> 3 levels (Br1,000 - Br3,000) | 5% - 10% returns | 30 days</p>";
    echo "<p><strong>VIP Two:</strong> 3 levels (Br4,000 - Br15,000) | 15% - 25% returns | 60 days</p>";
    echo "<p><strong>VIP Three:</strong> 4 levels (Br25,000 - Br125,000) | 30% - 37% returns | 6-12 months</p>";
    echo "<p style='color: #e74c3c;'><strong>⚠️ VIP Three Level 1:</strong> Requires 15 referrals + own commission</p>";
    echo "<p style='color: #e74c3c;'><strong>⚠️ VIP Three Level 2:</strong> Requires 25+ referrals + own commission</p>";
    echo "<p><strong>Plus:</strong> Regular (9 products) and Premium (6 products) packages</p>";
    echo "</div>";
    
    echo "<div style='margin-top: 30px; text-align: center;'>";
    echo "<a href='dashboard/deposit.php' class='btn'>View All Products</a>";
    echo "<a href='admin/products.php' class='btn'>Manage Products (Admin)</a>";
    echo "<a href='dashboard/product-details.php?id=" . ($total_products - 8) . "' class='btn'>View VIP One Level 1</a>";
    echo "</div>";
    
} catch(PDOException $e) {
    echo "<div class='error'>";
    echo "<h3>❌ Error</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "</div></body></html>";
?>
