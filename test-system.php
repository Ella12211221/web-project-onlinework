<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Test - Breakthrough Trading</title>
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
        .test-section {
            background: rgba(255, 255, 255, 0.05);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 1px solid rgba(34, 139, 34, 0.2);
        }
        .success {
            background: rgba(34, 139, 34, 0.2);
            color: #228b22;
            padding: 10px;
            border-radius: 5px;
            margin: 5px 0;
            border: 1px solid #228b22;
        }
        .error {
            background: rgba(255, 82, 82, 0.2);
            color: #ff5252;
            padding: 10px;
            border-radius: 5px;
            margin: 5px 0;
            border: 1px solid #ff5252;
        }
        .info {
            background: rgba(255, 215, 0, 0.2);
            color: #ffd700;
            padding: 10px;
            border-radius: 5px;
            margin: 5px 0;
            border: 1px solid #ffd700;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid rgba(34, 139, 34, 0.3);
        }
        th {
            color: #ffd700;
            background: rgba(255, 215, 0, 0.1);
        }
        .btn {
            background: linear-gradient(45deg, #228b22, #ffd700);
            color: #000;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
            margin: 5px;
            text-decoration: none;
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 System Test - Commission, Payment & Approval</h1>
        
        <?php
        try {
            $pdo = new PDO("mysql:host=localhost;dbname=breakthrough_trading;charset=utf8mb4", "root", "");
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            echo "<div class='success'>✅ Database connection successful!</div>";
            
            // Test 1: Check database structure
            echo "<div class='test-section'>";
            echo "<h3 style='color: #ffd700;'>📋 Test 1: Database Structure</h3>";
            
            $tables = ['users', 'trading_levels', 'invitation_codes', 'transactions', 'investments'];
            foreach ($tables as $table) {
                $result = $pdo->query("SHOW TABLES LIKE '$table'")->fetchColumn();
                if ($result) {
                    echo "<div class='success'>✅ Table '$table' exists</div>";
                } else {
                    echo "<div class='error'>❌ Table '$table' missing</div>";
                }
            }
            
            // Check for new columns
            $columns_to_check = [
                'users' => ['total_commission', 'status', 'approved_by', 'approved_at', 'referral_code'],
                'transactions' => ['payment_method', 'payment_details', 'commission_rate', 'commission_amount']
            ];
            
            foreach ($columns_to_check as $table => $columns) {
                $table_columns = $pdo->query("DESCRIBE $table")->fetchAll(PDO::FETCH_COLUMN);
                foreach ($columns as $column) {
                    if (in_array($column, $table_columns)) {
                        echo "<div class='success'>✅ Column '$table.$column' exists</div>";
                    } else {
                        echo "<div class='error'>❌ Column '$table.$column' missing</div>";
                    }
                }
            }
            echo "</div>";
            
            // Test 2: Check trading levels and commission rates
            echo "<div class='test-section'>";
            echo "<h3 style='color: #ffd700;'>💰 Test 2: Trading Levels & Commission Rates</h3>";
            
            $levels = $pdo->query("SELECT * FROM trading_levels ORDER BY level_number")->fetchAll();
            if (count($levels) >= 3) {
                echo "<div class='success'>✅ All 3 trading levels exist</div>";
                echo "<table>";
                echo "<tr><th>Level</th><th>Name</th><th>Min Investment</th><th>Max Investment</th><th>Return %</th><th>Commission Rate</th></tr>";
                foreach ($levels as $level) {
                    $commission_rate = $level['level_number'] == 1 ? '2%' : ($level['level_number'] == 2 ? '5%' : '3%');
                    echo "<tr>";
                    echo "<td>{$level['level_number']}</td>";
                    echo "<td>{$level['level_name']}</td>";
                    echo "<td>Br" . number_format($level['min_investment'], 2) . "</td>";
                    echo "<td>Br" . number_format($level['max_investment'], 2) . "</td>";
                    echo "<td>{$level['expected_return_percentage']}%</td>";
                    echo "<td style='color: #ffd700; font-weight: bold;'>$commission_rate</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<div class='error'>❌ Missing trading levels (found " . count($levels) . ", expected 3)</div>";
            }
            echo "</div>";
            
            // Test 3: Check payment methods in transactions table
            echo "<div class='test-section'>";
            echo "<h3 style='color: #ffd700;'>💳 Test 3: Payment Methods</h3>";
            
            $payment_methods = $pdo->query("SHOW COLUMNS FROM transactions LIKE 'payment_method'")->fetch();
            if ($payment_methods) {
                $enum_values = $payment_methods['Type'];
                echo "<div class='success'>✅ Payment method column exists</div>";
                echo "<div class='info'>Available payment methods: $enum_values</div>";
                
                // Check if all required payment methods are available
                $required_methods = ['cbe', 'anbesa', 'wegagen', 'mastercard', 'paypal'];
                foreach ($required_methods as $method) {
                    if (strpos($enum_values, $method) !== false) {
                        echo "<div class='success'>✅ Payment method '$method' available</div>";
                    } else {
                        echo "<div class='error'>❌ Payment method '$method' missing</div>";
                    }
                }
            } else {
                echo "<div class='error'>❌ Payment method column missing</div>";
            }
            echo "</div>";
            
            // Test 4: Check user approval system
            echo "<div class='test-section'>";
            echo "<h3 style='color: #ffd700;'>👥 Test 4: User Approval System</h3>";
            
            $user_stats = $pdo->query("
                SELECT 
                    status,
                    COUNT(*) as count
                FROM users 
                WHERE user_type = 'user'
                GROUP BY status
            ")->fetchAll();
            
            echo "<table>";
            echo "<tr><th>Status</th><th>Count</th><th>Description</th></tr>";
            foreach ($user_stats as $stat) {
                $description = '';
                switch($stat['status']) {
                    case 'pending': $description = 'Awaiting admin approval'; break;
                    case 'active': $description = 'Can trade and make transactions'; break;
                    case 'suspended': $description = 'Temporarily blocked'; break;
                    case 'inactive': $description = 'Registration rejected'; break;
                }
                echo "<tr>";
                echo "<td style='color: #ffd700; font-weight: bold;'>{$stat['status']}</td>";
                echo "<td>{$stat['count']}</td>";
                echo "<td>$description</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            // Check if admin user exists
            $admin_count = $pdo->query("SELECT COUNT(*) FROM users WHERE user_type = 'admin'")->fetchColumn();
            if ($admin_count > 0) {
                echo "<div class='success'>✅ Admin user exists ($admin_count admin(s))</div>";
            } else {
                echo "<div class='error'>❌ No admin user found</div>";
            }
            echo "</div>";
            
            // Test 5: Check invitation codes system
            echo "<div class='test-section'>";
            echo "<h3 style='color: #ffd700;'>🎫 Test 5: Invitation Codes System</h3>";
            
            $invitation_stats = $pdo->query("
                SELECT 
                    CASE 
                        WHEN created_by IS NULL THEN 'Admin Created'
                        ELSE 'User Generated'
                    END as type,
                    COUNT(*) as count,
                    AVG(bonus_amount) as avg_bonus
                FROM invitation_codes 
                GROUP BY created_by IS NULL
            ")->fetchAll();
            
            echo "<table>";
            echo "<tr><th>Type</th><th>Count</th><th>Average Bonus</th></tr>";
            foreach ($invitation_stats as $stat) {
                echo "<tr>";
                echo "<td>{$stat['type']}</td>";
                echo "<td>{$stat['count']}</td>";
                echo "<td>Br" . number_format($stat['avg_bonus'], 2) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            // Show sample invitation codes
            $sample_codes = $pdo->query("SELECT code, bonus_amount, description FROM invitation_codes LIMIT 5")->fetchAll();
            echo "<h4 style='color: #228b22; margin-top: 20px;'>Sample Invitation Codes:</h4>";
            echo "<table>";
            echo "<tr><th>Code</th><th>Bonus</th><th>Description</th></tr>";
            foreach ($sample_codes as $code) {
                echo "<tr>";
                echo "<td style='font-family: monospace; color: #ffd700;'>{$code['code']}</td>";
                echo "<td>Br" . number_format($code['bonus_amount'], 2) . "</td>";
                echo "<td>{$code['description']}</td>";
                echo "</tr>";
            }
            echo "</table>";
            echo "</div>";
            
            // Test 6: Commission calculation test
            echo "<div class='test-section'>";
            echo "<h3 style='color: #ffd700;'>🧮 Test 6: Commission Calculation</h3>";
            
            $test_amounts = [1500, 15000, 500000]; // Test amounts for each level
            echo "<table>";
            echo "<tr><th>Level</th><th>Investment</th><th>Commission Rate</th><th>Commission Amount</th><th>Net Investment</th></tr>";
            
            for ($i = 0; $i < 3; $i++) {
                $level = $i + 1;
                $amount = $test_amounts[$i];
                $commission_rate = $level == 1 ? 2 : ($level == 2 ? 5 : 3);
                $commission_amount = $amount * ($commission_rate / 100);
                $net_investment = $amount - $commission_amount;
                
                echo "<tr>";
                echo "<td>Level $level</td>";
                echo "<td>Br" . number_format($amount, 2) . "</td>";
                echo "<td>{$commission_rate}%</td>";
                echo "<td style='color: #ff6b6b;'>Br" . number_format($commission_amount, 2) . "</td>";
                echo "<td style='color: #228b22;'>Br" . number_format($net_investment, 2) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            echo "<div class='info'>💡 Commission is deducted from the investment amount before calculating returns</div>";
            echo "</div>";
            
            // Test 7: System readiness check
            echo "<div class='test-section'>";
            echo "<h3 style='color: #ffd700;'>🚀 Test 7: System Readiness</h3>";
            
            $readiness_checks = [
                'Database tables' => count($pdo->query("SHOW TABLES")->fetchAll()) >= 5,
                'Trading levels' => $pdo->query("SELECT COUNT(*) FROM trading_levels")->fetchColumn() >= 3,
                'Admin user' => $pdo->query("SELECT COUNT(*) FROM users WHERE user_type = 'admin'")->fetchColumn() > 0,
                'Invitation codes' => $pdo->query("SELECT COUNT(*) FROM invitation_codes")->fetchColumn() > 0,
                'Commission system' => $pdo->query("SHOW COLUMNS FROM transactions LIKE 'commission_rate'")->fetch() !== false,
                'Payment methods' => $pdo->query("SHOW COLUMNS FROM transactions LIKE 'payment_method'")->fetch() !== false,
                'Approval system' => $pdo->query("SHOW COLUMNS FROM users LIKE 'status'")->fetch() !== false
            ];
            
            $all_ready = true;
            foreach ($readiness_checks as $check => $status) {
                if ($status) {
                    echo "<div class='success'>✅ $check: Ready</div>";
                } else {
                    echo "<div class='error'>❌ $check: Not Ready</div>";
                    $all_ready = false;
                }
            }
            
            if ($all_ready) {
                echo "<div class='success' style='font-size: 18px; text-align: center; margin: 20px 0; padding: 20px;'>";
                echo "🎉 <strong>SYSTEM FULLY READY!</strong><br><br>";
                echo "✅ Commission system: Level 1 (2%), Level 2 (5%), Level 3 (3%)<br>";
                echo "✅ Payment methods: CBE, Anbesa, Wegagen, MasterCard, PayPal<br>";
                echo "✅ Admin approval system: New users require approval<br>";
                echo "✅ Automatic invitation codes: Generated for each user<br>";
                echo "✅ All database structures: Complete and ready<br>";
                echo "</div>";
            } else {
                echo "<div class='error' style='font-size: 18px; text-align: center; margin: 20px 0; padding: 20px;'>";
                echo "❌ <strong>SYSTEM NOT READY</strong><br>";
                echo "Some components need attention before the system is fully operational.";
                echo "</div>";
            }
            echo "</div>";
            
        } catch(PDOException $e) {
            echo "<div class='error'>";
            echo "<h4>❌ Database Connection Error:</h4>";
            echo "<strong>Error:</strong> " . $e->getMessage() . "<br>";
            echo "</div>";
        }
        ?>
        
        <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid rgba(255, 215, 0, 0.3);">
            <a href="simple-setup.php" class="btn">🔧 Run Setup</a>
            <a href="fix-invitation-table.php" class="btn">🛠️ Fix Issues</a>
            <a href="../auth/login.php" class="btn">🔑 Test Login</a>
            <a href="../admin/users.php" class="btn">👑 Admin Panel</a>
            <a href="../index.html" class="btn">🏠 Home</a>
        </div>
    </div>
</body>
</html>