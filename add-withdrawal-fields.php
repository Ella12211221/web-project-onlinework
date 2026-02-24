<?php
// Add Withdrawal Fields to Users Table
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Withdrawal Fields - Breakthrough Trading</title>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            margin: 0; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #333;
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .btn {
            background: #4a90e2;
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            font-weight: 600;
            margin: 10px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .btn:hover {
            background: #357abd;
        }
        .btn.success {
            background: #28a745;
        }
        .btn.success:hover {
            background: #218838;
        }
        .success-msg {
            background: #d4edda;
            border: 2px solid #c3e6cb;
            color: #155724;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }
        .error-msg {
            background: #f8d7da;
            border: 2px solid #f5c6cb;
            color: #721c24;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }
        .info-msg {
            background: #d1ecf1;
            border: 2px solid #bee5eb;
            color: #0c5460;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 style="text-align: center; color: #2c5aa0; margin-bottom: 30px;">
            💳 Add Withdrawal Fields to Users Table
        </h1>
        
        <?php
        if (isset($_POST['add_withdrawal_fields'])) {
            try {
                $pdo = new PDO("mysql:host=localhost;dbname=breakthrough_trading;charset=utf8mb4", "root", "");
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                echo "<div class='info-msg'><h3>🔧 Adding withdrawal fields to users table...</h3></div>";
                
                // Check if fields already exist
                $columns = $pdo->query("DESCRIBE users")->fetchAll();
                $existing_columns = array_column($columns, 'Field');
                
                $fields_to_add = [
                    'first_name' => "VARCHAR(100) NULL COMMENT 'First name for withdrawal verification'",
                    'last_name' => "VARCHAR(100) NULL COMMENT 'Last name for withdrawal verification'",
                    'withdrawal_account_number' => "VARCHAR(50) NULL COMMENT 'Bank account number for withdrawals'",
                    'withdrawal_phone' => "VARCHAR(20) NULL COMMENT 'Phone number for withdrawal verification'"
                ];
                
                $added_fields = [];
                $existing_fields = [];
                
                foreach ($fields_to_add as $field_name => $field_definition) {
                    if (!in_array($field_name, $existing_columns)) {
                        $alter_sql = "ALTER TABLE users ADD COLUMN $field_name $field_definition";
                        $pdo->exec($alter_sql);
                        $added_fields[] = $field_name;
                        echo "<p style='color: green;'>✅ Added field: $field_name</p>";
                    } else {
                        $existing_fields[] = $field_name;
                        echo "<p style='color: orange;'>⚠️ Field already exists: $field_name</p>";
                    }
                }
                
                // Show updated table structure
                $columns = $pdo->query("DESCRIBE users")->fetchAll();
                
                echo "<div class='success-msg'>";
                echo "<h2>🎉 Withdrawal Fields Update Complete!</h2>";
                
                if (!empty($added_fields)) {
                    echo "<h3>✅ Added Fields:</h3>";
                    echo "<ul>";
                    foreach ($added_fields as $field) {
                        echo "<li><strong>$field</strong></li>";
                    }
                    echo "</ul>";
                }
                
                if (!empty($existing_fields)) {
                    echo "<h3>⚠️ Already Existing Fields:</h3>";
                    echo "<ul>";
                    foreach ($existing_fields as $field) {
                        echo "<li><strong>$field</strong></li>";
                    }
                    echo "</ul>";
                }
                
                echo "<h3>📋 Current Users Table Structure:</h3>";
                echo "<table style='width: 100%; border-collapse: collapse; margin: 15px 0;'>";
                echo "<tr style='background: #f8f9fa;'>";
                echo "<th style='padding: 10px; border: 1px solid #ddd; text-align: left;'>Field</th>";
                echo "<th style='padding: 10px; border: 1px solid #ddd; text-align: left;'>Type</th>";
                echo "<th style='padding: 10px; border: 1px solid #ddd; text-align: left;'>Null</th>";
                echo "</tr>";
                
                foreach ($columns as $column) {
                    $field = $column['Field'];
                    $type = $column['Type'];
                    $null = $column['Null'];
                    
                    $highlight = in_array($field, ['first_name', 'last_name', 'withdrawal_account_number', 'withdrawal_phone']) ? 'background: #e8f5e8;' : '';
                    
                    echo "<tr style='$highlight'>";
                    echo "<td style='padding: 8px; border: 1px solid #ddd; font-weight: bold;'>$field</td>";
                    echo "<td style='padding: 8px; border: 1px solid #ddd; font-family: monospace;'>$type</td>";
                    echo "<td style='padding: 8px; border: 1px solid #ddd;'>$null</td>";
                    echo "</tr>";
                }
                echo "</table>";
                echo "</div>";
                
                // Show features
                echo "<div style='background: #e8f5e8; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
                echo "<h3 style='color: #27ae60;'>🚀 Withdrawal System Features:</h3>";
                echo "<ul style='color: #2c3e50; line-height: 1.8;'>";
                echo "<li>✅ <strong>First Name:</strong> User's first name for verification</li>";
                echo "<li>✅ <strong>Last Name:</strong> User's last name for verification</li>";
                echo "<li>✅ <strong>Account Number:</strong> Bank account number for withdrawals</li>";
                echo "<li>✅ <strong>Phone Number:</strong> Phone number for withdrawal verification</li>";
                echo "<li>✅ <strong>Security:</strong> All fields are optional and can be updated by users</li>";
                echo "<li>✅ <strong>Verification:</strong> Required for withdrawal processing</li>";
                echo "</ul>";
                echo "</div>";
                
                $setup_complete = true;
                
            } catch(PDOException $e) {
                echo "<div class='error-msg'>";
                echo "<h3>❌ Database Error:</h3>";
                echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
                echo "<p><strong>Solution:</strong> Make sure your database server is running and the 'breakthrough_trading' database exists.</p>";
                echo "</div>";
            }
        }
        
        // Display current status or setup button
        try {
            $pdo = new PDO("mysql:host=localhost;dbname=breakthrough_trading;charset=utf8mb4", "root", "");
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Check current table structure
            $columns = $pdo->query("DESCRIBE users")->fetchAll();
            $existing_columns = array_column($columns, 'Field');
            
            $required_fields = ['first_name', 'last_name', 'withdrawal_account_number', 'withdrawal_phone'];
            $missing_fields = array_diff($required_fields, $existing_columns);
            
            if (!empty($missing_fields)) {
                echo "<div style='text-align: center; padding: 40px; background: #fff3cd; border: 2px solid #ffeaa7; border-radius: 10px; margin: 20px 0;'>";
                echo "<h3 style='color: #856404;'>⚠️ Missing Withdrawal Fields</h3>";
                echo "<p>The following fields are missing from the users table:</p>";
                echo "<ul style='color: #856404; text-align: left; display: inline-block;'>";
                foreach ($missing_fields as $field) {
                    echo "<li><strong>$field</strong></li>";
                }
                echo "</ul>";
                echo "<form method='POST' style='margin-top: 20px;'>";
                echo "<button type='submit' name='add_withdrawal_fields' class='btn success' style='font-size: 18px; padding: 20px 40px;'>";
                echo "🚀 Add Withdrawal Fields";
                echo "</button>";
                echo "</form>";
                echo "</div>";
            } else {
                echo "<div class='success-msg'>";
                echo "<h3>✅ Withdrawal Fields Status</h3>";
                echo "<p>All required withdrawal fields are present in the users table:</p>";
                echo "<ul>";
                foreach ($required_fields as $field) {
                    echo "<li><strong>$field</strong> ✅</li>";
                }
                echo "</ul>";
                echo "<form method='POST' style='margin-top: 20px; text-align: center;'>";
                echo "<button type='submit' name='add_withdrawal_fields' class='btn' style='background: #dc3545;'>";
                echo "🔄 Re-check and Update Fields";
                echo "</button>";
                echo "</form>";
                echo "</div>";
            }
            
        } catch(PDOException $e) {
            echo "<div class='error-msg'>";
            echo "<h3>❌ Database Connection Error:</h3>";
            echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<p>Please make sure your database server is running and the database 'breakthrough_trading' exists.</p>";
            echo "<p><a href='simple-setup.php' style='color: #721c24; text-decoration: none; font-weight: bold;'>🔧 Click here to setup database first</a></p>";
            echo "</div>";
        }
        ?>
        
        <div style="text-align: center; margin: 30px 0; padding: 20px; background: #f8f9fa; border-radius: 10px;">
            <h3>🔗 Quick Navigation</h3>
            <a href="../dashboard/payment-methods.php" class="btn success">💳 Test Payment Methods</a>
            <a href="../admin/dashboard.php" class="btn">📊 Admin Dashboard</a>
            <a href="../dashboard/index.php" class="btn">🏠 User Dashboard</a>
        </div>
    </div>
</body>
</html>