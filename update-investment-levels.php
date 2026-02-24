<?php
// Update Investment Levels - New tiered purchase amounts
echo "<h2>📊 Updating Investment Levels</h2>";

$host = 'localhost';
$username = 'root';
$password = '';
$database = 'concordial_nexus';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p style='color: green;'>✅ Database connected</p>";
    
    // Step 1: Clear existing trading levels
    echo "<h3>🧹 Step 1: Clearing old trading levels...</h3>";
    $pdo->exec("DELETE FROM trading_levels");
    echo "<p style='color: green;'>✅ Old trading levels cleared</p>";
    
    // Step 2: Insert new investment levels with specific amounts
    echo "<h3>💰 Step 2: Adding new investment levels...</h3>";
    
    $investment_levels = [
        // Level 1 - Beginner
        [
            'level_number' => 1,
            'level_name' => 'Level 1 - Beginner (Br1,000)',
            'min_investment' => 1000.00,
            'max_investment' => 1000.00,
            'expected_return_percentage' => 15.00,
            'duration_days' => 30,
            'description' => 'Beginner investment package - Br1,000 for 30 days with 15% return'
        ],
        [
            'level_number' => 2,
            'level_name' => 'Level 1 - Beginner (Br2,000)',
            'min_investment' => 2000.00,
            'max_investment' => 2000.00,
            'expected_return_percentage' => 18.00,
            'duration_days' => 30,
            'description' => 'Beginner investment package - Br2,000 for 30 days with 18% return'
        ],
        [
            'level_number' => 3,
            'level_name' => 'Level 1 - Beginner (Br3,000)',
            'min_investment' => 3000.00,
            'max_investment' => 3000.00,
            'expected_return_percentage' => 20.00,
            'duration_days' => 30,
            'description' => 'Beginner investment package - Br3,000 for 30 days with 20% return'
        ],
        
        // Level 2 - Intermediate
        [
            'level_number' => 4,
            'level_name' => 'Level 2 - Intermediate (Br4,000)',
            'min_investment' => 4000.00,
            'max_investment' => 4000.00,
            'expected_return_percentage' => 22.00,
            'duration_days' => 21,
            'description' => 'Intermediate investment package - Br4,000 for 21 days with 22% return'
        ],
        [
            'level_number' => 5,
            'level_name' => 'Level 2 - Intermediate (Br6,000)',
            'min_investment' => 6000.00,
            'max_investment' => 6000.00,
            'expected_return_percentage' => 25.00,
            'duration_days' => 21,
            'description' => 'Intermediate investment package - Br6,000 for 21 days with 25% return'
        ],
        [
            'level_number' => 6,
            'level_name' => 'Level 2 - Intermediate (Br8,000)',
            'min_investment' => 8000.00,
            'max_investment' => 8000.00,
            'expected_return_percentage' => 28.00,
            'duration_days' => 21,
            'description' => 'Intermediate investment package - Br8,000 for 21 days with 28% return'
        ],
        
        // Level 3 - Advanced
        [
            'level_number' => 7,
            'level_name' => 'Level 3 - Advanced (Br10,000)',
            'min_investment' => 10000.00,
            'max_investment' => 10000.00,
            'expected_return_percentage' => 30.00,
            'duration_days' => 14,
            'description' => 'Advanced investment package - Br10,000 for 14 days with 30% return'
        ],
        [
            'level_number' => 8,
            'level_name' => 'Level 3 - Advanced (Br14,000)',
            'min_investment' => 14000.00,
            'max_investment' => 14000.00,
            'expected_return_percentage' => 33.00,
            'duration_days' => 14,
            'description' => 'Advanced investment package - Br14,000 for 14 days with 33% return'
        ],
        [
            'level_number' => 9,
            'level_name' => 'Level 3 - Advanced (Br16,000)',
            'min_investment' => 16000.00,
            'max_investment' => 16000.00,
            'expected_return_percentage' => 35.00,
            'duration_days' => 14,
            'description' => 'Advanced investment package - Br16,000 for 14 days with 35% return'
        ]
    ];
    
    $insert_stmt = $pdo->prepare("
        INSERT INTO trading_levels 
        (level_number, level_name, min_investment, max_investment, expected_return_percentage, duration_days, description, is_active) 
        VALUES (?, ?, ?, ?, ?, ?, ?, 1)
    ");
    
    foreach ($investment_levels as $level) {
        $insert_stmt->execute([
            $level['level_number'],
            $level['level_name'],
            $level['min_investment'],
            $level['max_investment'],
            $level['expected_return_percentage'],
            $level['duration_days'],
            $level['description']
        ]);
        
        echo "<p style='color: green;'>✅ Added: " . $level['level_name'] . " - " . $level['expected_return_percentage'] . "% return</p>";
    }
    
    // Step 3: Display the new investment structure
    echo "<h3>📋 Step 3: New Investment Structure</h3>";
    
    $levels = $pdo->query("SELECT * FROM trading_levels ORDER BY level_number")->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 20px 0;'>";
    echo "<tr style='background: #4a90e2; color: white;'>";
    echo "<th>Level</th><th>Package Name</th><th>Amount</th><th>Return %</th><th>Duration</th><th>Expected Profit</th>";
    echo "</tr>";
    
    $level_groups = ['Level 1 - Beginner', 'Level 2 - Intermediate', 'Level 3 - Advanced'];
    $current_group = '';
    
    foreach ($levels as $level) {
        // Determine which group this level belongs to
        if (strpos($level['level_name'], 'Level 1') !== false) {
            $group = 'Level 1 - Beginner';
            $color = '#e8f5e8';
        } elseif (strpos($level['level_name'], 'Level 2') !== false) {
            $group = 'Level 2 - Intermediate';
            $color = '#fff3cd';
        } else {
            $group = 'Level 3 - Advanced';
            $color = '#f8d7da';
        }
        
        $profit = ($level['min_investment'] * $level['expected_return_percentage']) / 100;
        
        echo "<tr style='background: $color;'>";
        echo "<td style='font-weight: bold;'>" . $group . "</td>";
        echo "<td>" . $level['level_name'] . "</td>";
        echo "<td style='font-weight: bold; color: #2c5aa0;'>Br" . number_format($level['min_investment']) . "</td>";
        echo "<td style='color: #28a745; font-weight: bold;'>" . $level['expected_return_percentage'] . "%</td>";
        echo "<td>" . $level['duration_days'] . " days</td>";
        echo "<td style='font-weight: bold; color: #dc3545;'>Br" . number_format($profit) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<div style='background: #d4edda; border: 2px solid #c3e6cb; color: #155724; padding: 30px; border-radius: 15px; margin: 30px 0; text-align: center;'>";
    echo "<h1>🎉 INVESTMENT LEVELS UPDATED!</h1>";
    echo "<h3>✅ 9 investment packages created</h3>";
    echo "<h3>✅ 3 levels with 3 amounts each</h3>";
    echo "<h3>✅ Progressive returns: 15% - 35%</h3>";
    echo "<h3>✅ Shorter durations for higher levels</h3>";
    echo "<br>";
    echo "<p><strong>Users can now choose from 9 different investment options!</strong></p>";
    echo "</div>";
    
    echo "<div style='text-align: center; margin: 30px 0;'>";
    echo "<a href='dashboard/investments.php' style='background: #28a745; color: white; padding: 20px 40px; text-decoration: none; border-radius: 10px; margin: 15px; display: inline-block; font-size: 18px; font-weight: bold; box-shadow: 0 4px 15px rgba(0,0,0,0.2);'>💰 View Investment Options</a><br>";
    echo "<a href='admin/dashboard.php' style='background: #007bff; color: white; padding: 20px 40px; text-decoration: none; border-radius: 10px; margin: 15px; display: inline-block; font-size: 18px; font-weight: bold; box-shadow: 0 4px 15px rgba(0,0,0,0.2);'>📊 Admin Dashboard</a>";
    echo "</div>";
    
} catch(PDOException $e) {
    echo "<div style='background: #f8d7da; border: 2px solid #f5c6cb; color: #721c24; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    echo "<h3>❌ Database Error:</h3>";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "</div>";
}
?>

<style>
body { 
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
    margin: 20px; 
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #333;
    min-height: 100vh;
}
.container {
    max-width: 1200px;
    margin: 0 auto;
    background: white;
    padding: 30px;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
}
table { 
    margin: 15px 0; 
    background: white;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    border-radius: 8px;
    overflow: hidden;
}
th, td { 
    padding: 15px; 
    text-align: left; 
    border: 1px solid #dee2e6; 
}
th { 
    background: linear-gradient(135deg, #4a90e2, #357abd);
    color: white;
    font-weight: 600;
}
h1, h2, h3 {
    color: #2c3e50;
}
</style>

<div class="container">
<?php // Content will be inserted here ?>
</div>