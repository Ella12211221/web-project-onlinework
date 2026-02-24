<!DOCTYPE html>
<html>
<head>
    <title>Withdrawals - Concordial Nexus</title>
    <style>
        body { font-family: Arial; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; }
        h1 { color: #333; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th { background: #4a90e2; color: white; padding: 10px; text-align: left; }
        td { padding: 10px; border-bottom: 1px solid #ddd; }
        .btn { padding: 8px 15px; border: none; border-radius: 5px; cursor: pointer; margin: 2px; }
        .btn-approve { background: #28a745; color: white; }
        .btn-reject { background: #dc3545; color: white; }
        .back { background: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>💰 Withdrawal Management</h1>
        
        <?php
        // Show all errors
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        
        echo "<p><strong>Debug Info:</strong></p>";
        
        // Check session
        session_start();
        echo "<p>✅ Session started</p>";
        
        if (!isset($_SESSION['user_id'])) {
            echo "<div class='error'>❌ Not logged in. <a href='../auth/login.php'>Login here</a></div>";
            exit();
        }
        
        echo "<p>✅ User ID: " . $_SESSION['user_id'] . "</p>";
        echo "<p>✅ User Type: " . ($_SESSION['user_type'] ?? 'not set') . "</p>";
        
        if ($_SESSION['user_type'] !== 'admin') {
            echo "<div class='error'>❌ Not an admin. <a href='../dashboard/index.php'>Go to dashboard</a></div>";
            exit();
        }
        
        // Check database
        try {
            $pdo = new PDO("mysql:host=localhost;dbname=concordial_nexus;charset=utf8mb4", "root", "");
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            echo "<p>✅ Database connected</p>";
            
            // Check if table exists
            $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            echo "<p>✅ Tables found: " . count($tables) . "</p>";
            
            if (!in_array('payment_transactions', $tables)) {
                echo "<div class='error'>❌ payment_transactions table not found<br>";
                echo "<a href='../setup-payment-system.php' style='color: white; background: #17a2b8; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 10px;'>Run Setup</a>";
                echo "</div>";
                exit();
            }
            
            echo "<p>✅ payment_transactions table exists</p>";
            
            // Get withdrawals
            $withdrawals = $pdo->query("
                SELECT pt.*, u.full_name, u.email, u.account_balance
                FROM payment_transactions pt
                JOIN users u ON pt.user_id = u.id
                WHERE pt.payment_method = 'withdrawal_request'
                ORDER BY pt.created_at DESC
            ")->fetchAll();
            
            echo "<p>✅ Found " . count($withdrawals) . " withdrawal requests</p>";
            
            if (empty($withdrawals)) {
                echo "<div class='success'>No withdrawal requests yet.</div>";
            } else {
                echo "<table>";
                echo "<tr><th>ID</th><th>User</th><th>Amount</th><th>Bank</th><th>Status</th><th>Date</th></tr>";
                foreach ($withdrawals as $w) {
                    echo "<tr>";
                    echo "<td>#" . $w['id'] . "</td>";
                    echo "<td>" . htmlspecialchars($w['full_name']) . "<br><small>" . htmlspecialchars($w['email']) . "</small></td>";
                    echo "<td><strong>Br" . number_format($w['amount'], 2) . "</strong></td>";
                    echo "<td>" . htmlspecialchars($w['bank_name'] ?? 'N/A') . "</td>";
                    echo "<td>" . ucfirst($w['status']) . "</td>";
                    echo "<td>" . date('M j, Y', strtotime($w['created_at'])) . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
            
        } catch(PDOException $e) {
            echo "<div class='error'>❌ Database Error: " . $e->getMessage() . "</div>";
        }
        ?>
        
        <a href="dashboard.php" class="back">← Back to Dashboard</a>
    </div>
</body>
</html>