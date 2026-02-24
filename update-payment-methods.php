<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Payment Methods - Breakthrough Trading</title>
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
            max-width: 800px;
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
        .success {
            background: rgba(34, 139, 34, 0.2);
            color: #228b22;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
            border: 1px solid #228b22;
        }
        .error {
            background: rgba(255, 82, 82, 0.2);
            color: #ff5252;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
            border: 1px solid #ff5252;
        }
        .info {
            background: rgba(255, 215, 0, 0.2);
            color: #ffd700;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
            border: 1px solid #ffd700;
        }
        .btn {
            background: linear-gradient(45deg, #228b22, #ffd700);
            color: #000;
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            margin: 10px;
            text-decoration: none;
            display: inline-block;
        }
        .payment-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin: 20px 0;
        }
        .payment-item {
            background: rgba(34, 139, 34, 0.1);
            border: 1px solid rgba(34, 139, 34, 0.3);
            border-radius: 8px;
            padding: 1rem;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>💳 Update Payment Methods</h1>
        
        <?php
        if (isset($_POST['update_methods'])) {
            $host = 'localhost';
            $username = 'root';
            $password = '';
            $database = 'breakthrough_trading';
            
            try {
                echo "<div class='info'>Connecting to database...</div>";
                $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                echo "<div class='success'>✅ Connected to database successfully!</div>";
                
                // Update payment_method enum in transactions table
                echo "<div class='info'>Updating payment methods in transactions table...</div>";
                $pdo->exec("ALTER TABLE transactions MODIFY COLUMN payment_method ENUM(
                    'cbe',
                    'wegagen', 
                    'abyssinia',
                    'mastercard',
                    'visa',
                    'paypal',
                    'stripe',
                    'telebirr',
                    'mpesa',
                    'bitcoin',
                    'bank_transfer',
                    'cash',
                    'other'
                ) DEFAULT NULL");
                echo "<div class='success'>✅ Updated transactions table payment methods!</div>";
                
                // Verify the update
                echo "<div class='info'>Verifying payment methods...</div>";
                $result = $pdo->query("SHOW COLUMNS FROM transactions LIKE 'payment_method'")->fetch();
                if ($result) {
                    echo "<div class='success'>✅ Payment method column updated successfully!</div>";
                    echo "<div class='info'>Available methods: " . $result['Type'] . "</div>";
                } else {
                    echo "<div class='error'>❌ Failed to verify payment method column</div>";
                }
                
                echo "<div class='success' style='font-size: 18px; text-align: center; margin: 30px 0; padding: 20px;'>";
                echo "🎉 <strong>PAYMENT METHODS UPDATED!</strong><br><br>";
                echo "✅ Ethiopian Banks: CBE, Wegagen, Bank of Abyssinia<br>";
                echo "✅ Cards: MasterCard, Visa<br>";
                echo "✅ Digital: PayPal, Stripe<br>";
                echo "✅ Mobile: TeleBirr, M-Pesa<br>";
                echo "✅ Crypto: Bitcoin<br>";
                echo "✅ Other: Bank Transfer, Cash, Custom<br>";
                echo "</div>";
                
                // Display all payment methods
                echo "<h3 style='color: #ffd700; text-align: center; margin: 30px 0;'>Available Payment Methods</h3>";
                echo "<div class='payment-grid'>";
                
                $payment_methods = [
                    'cbe' => '🏛️ CBE Bank',
                    'wegagen' => '💰 Wegagen Bank', 
                    'abyssinia' => '🦁 Bank of Abyssinia',
                    'mastercard' => '💳 MasterCard',
                    'visa' => '💎 Visa Card',
                    'paypal' => '🅿️ PayPal',
                    'stripe' => '💰 Stripe',
                    'telebirr' => '📱 TeleBirr',
                    'mpesa' => '📲 M-Pesa',
                    'bitcoin' => '₿ Bitcoin',
                    'bank_transfer' => '🏦 Bank Transfer',
                    'cash' => '💵 Cash Payment',
                    'other' => '🔄 Other Methods'
                ];
                
                foreach ($payment_methods as $key => $name) {
                    echo "<div class='payment-item'>";
                    echo "<strong>$name</strong><br>";
                    echo "<small style='color: rgba(255, 255, 255, 0.7);'>$key</small>";
                    echo "</div>";
                }
                
                echo "</div>";
                
                echo "<div style='text-align: center; margin: 30px 0;'>";
                echo "<a href='../dashboard/transactions.php' class='btn'>💰 Test Investment Flow</a>";
                echo "<a href='test-system.php' class='btn'>🧪 Test System</a>";
                echo "</div>";
                
            } catch(PDOException $e) {
                echo "<div class='error'>";
                echo "<h4>❌ Database Error:</h4>";
                echo "<strong>Error:</strong> " . $e->getMessage() . "<br>";
                echo "</div>";
            }
        } else {
            echo "<div class='info'>";
            echo "<h3>🎯 What This Will Update:</h3>";
            echo "<strong>Ethiopian Banks:</strong><br>";
            echo "✓ CBE (Commercial Bank of Ethiopia)<br>";
            echo "✓ Wegagen Bank<br>";
            echo "✓ Bank of Abyssinia<br><br>";
            
            echo "<strong>Cards & Digital Payments:</strong><br>";
            echo "✓ MasterCard<br>";
            echo "✓ Visa Card<br>";
            echo "✓ PayPal<br>";
            echo "✓ Stripe<br><br>";
            
            echo "<strong>Mobile & Other Payments:</strong><br>";
            echo "✓ TeleBirr (Ethiopian Mobile Payment)<br>";
            echo "✓ M-Pesa (Mobile Money)<br>";
            echo "✓ Bitcoin (Cryptocurrency)<br>";
            echo "✓ Bank Transfer<br>";
            echo "✓ Cash Payment<br>";
            echo "✓ Other Payment Methods<br>";
            echo "</div>";
            
            echo "<form method='POST' style='text-align: center; margin: 30px 0;'>";
            echo "<button type='submit' name='update_methods' class='btn' style='font-size: 18px; padding: 20px 40px;'>";
            echo "💳 Update Payment Methods Now";
            echo "</button>";
            echo "</form>";
        }
        ?>
        
        <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid rgba(255, 215, 0, 0.3);">
            <a href="../index.html" style="color: #ffd700; text-decoration: none; margin: 0 15px;">🏠 Home</a>
            <a href="simple-setup.php" style="color: #ffd700; text-decoration: none; margin: 0 15px;">🔧 Main Setup</a>
            <a href="../dashboard/transactions.php" style="color: #ffd700; text-decoration: none; margin: 0 15px;">💰 Test Transactions</a>
        </div>
    </div>
</body>
</html>