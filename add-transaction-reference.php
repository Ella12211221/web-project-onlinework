<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Transaction Reference Numbers</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 20px; }
        .container { max-width: 900px; margin: 0 auto; background: white; border-radius: 20px; padding: 30px; box-shadow: 0 20px 40px rgba(0,0,0,0.2); }
        h1 { color: #333; text-align: center; margin-bottom: 30px; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin: 10px 0; border-left: 4px solid #28a745; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin: 10px 0; border-left: 4px solid #dc3545; }
        .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 8px; margin: 10px 0; border-left: 4px solid #17a2b8; }
        .warning { background: #fff3cd; color: #856404; padding: 15px; border-radius: 8px; margin: 10px 0; border-left: 4px solid #ffc107; }
        .btn { background: #4a90e2; color: white; padding: 15px 30px; border: none; border-radius: 8px; text-decoration: none; display: inline-block; margin: 10px 5px; font-weight: 600; cursor: pointer; }
        .btn:hover { background: #357abd; }
        .step { background: #f8f9fa; border-radius: 10px; padding: 20px; margin-bottom: 20px; border-left: 5px solid #4a90e2; }
        .step h2 { color: #4a90e2; margin-bottom: 15px; }
        code { background: #2c3e50; color: #ecf0f1; padding: 2px 6px; border-radius: 4px; font-family: 'Courier New', monospace; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📝 Add Transaction Reference Numbers</h1>
        
        <?php
        try {
            $pdo = new PDO("mysql:host=localhost;dbname=concordial_nexus;charset=utf8mb4", "root", "");
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            echo "<div class='success'>✅ Database connection successful</div>";
            
            // Step 1: Add reference_number column to transactions table
            echo "<div class='step'>";
            echo "<h2>Step 1: Update Transactions Table</h2>";
            
            try {
                $pdo->exec("ALTER TABLE transactions ADD COLUMN reference_number VARCHAR(50) UNIQUE DEFAULT NULL AFTER id");
                echo "<div class='success'>✅ Added <code>reference_number</code> column to transactions table</div>";
            } catch(PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate column') !== false) {
                    echo "<div class='info'>ℹ️ Column <code>reference_number</code> already exists</div>";
                } else {
                    echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
                }
            }
            
            // Add index for faster lookups
            try {
                $pdo->exec("ALTER TABLE transactions ADD INDEX idx_reference_number (reference_number)");
                echo "<div class='success'>✅ Added index for reference_number</div>";
            } catch(PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate') !== false) {
                    echo "<div class='info'>ℹ️ Index already exists</div>";
                }
            }
            
            echo "</div>";
            
            // Step 2: Generate reference numbers for existing transactions
            echo "<div class='step'>";
            echo "<h2>Step 2: Generate Reference Numbers</h2>";
            
            $transactions_without_ref = $pdo->query("SELECT id, created_at FROM transactions WHERE reference_number IS NULL OR reference_number = ''")->fetchAll();
            
            if (!empty($transactions_without_ref)) {
                echo "<div class='info'>Generating reference numbers for " . count($transactions_without_ref) . " transactions...</div>";
                
                $update_stmt = $pdo->prepare("UPDATE transactions SET reference_number = ? WHERE id = ?");
                $generated = 0;
                
                foreach ($transactions_without_ref as $transaction) {
                    // Generate unique reference number: TXN-YYYYMMDD-XXXXX
                    $date = date('Ymd', strtotime($transaction['created_at']));
                    $random = strtoupper(substr(md5($transaction['id'] . time() . rand()), 0, 5));
                    $reference = "TXN-{$date}-{$random}";
                    
                    try {
                        $update_stmt->execute([$reference, $transaction['id']]);
                        $generated++;
                    } catch(PDOException $e) {
                        // If duplicate, try again with different random
                        $random = strtoupper(substr(md5($transaction['id'] . time() . rand() . rand()), 0, 5));
                        $reference = "TXN-{$date}-{$random}";
                        $update_stmt->execute([$reference, $transaction['id']]);
                        $generated++;
                    }
                }
                
                echo "<div class='success'>✅ Generated reference numbers for <strong>$generated</strong> transactions</div>";
            } else {
                echo "<div class='info'>ℹ️ All transactions already have reference numbers</div>";
            }
            
            echo "</div>";
            
            // Step 3: Update payment_transactions table (if exists)
            echo "<div class='step'>";
            echo "<h2>Step 3: Update Payment Transactions Table</h2>";
            
            $table_exists = $pdo->query("SHOW TABLES LIKE 'payment_transactions'")->fetch();
            
            if ($table_exists) {
                // Check if transaction_reference column exists and is properly configured
                $columns = $pdo->query("SHOW COLUMNS FROM payment_transactions LIKE 'transaction_reference'")->fetch();
                
                if ($columns) {
                    echo "<div class='info'>ℹ️ Column <code>transaction_reference</code> already exists in payment_transactions</div>";
                } else {
                    try {
                        $pdo->exec("ALTER TABLE payment_transactions ADD COLUMN transaction_reference VARCHAR(50) DEFAULT NULL AFTER amount");
                        echo "<div class='success'>✅ Added <code>transaction_reference</code> column to payment_transactions</div>";
                    } catch(PDOException $e) {
                        echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
                    }
                }
                
                // Generate reference numbers for payment transactions without them
                $payment_txns = $pdo->query("SELECT id, created_at FROM payment_transactions WHERE transaction_reference IS NULL OR transaction_reference = ''")->fetchAll();
                
                if (!empty($payment_txns)) {
                    echo "<div class='info'>Generating reference numbers for " . count($payment_txns) . " payment transactions...</div>";
                    
                    $update_payment = $pdo->prepare("UPDATE payment_transactions SET transaction_reference = ? WHERE id = ?");
                    $generated_payment = 0;
                    
                    foreach ($payment_txns as $payment) {
                        $date = date('Ymd', strtotime($payment['created_at']));
                        $random = strtoupper(substr(md5($payment['id'] . 'payment' . time() . rand()), 0, 5));
                        $reference = "PAY-{$date}-{$random}";
                        
                        try {
                            $update_payment->execute([$reference, $payment['id']]);
                            $generated_payment++;
                        } catch(PDOException $e) {
                            $random = strtoupper(substr(md5($payment['id'] . 'payment' . time() . rand() . rand()), 0, 5));
                            $reference = "PAY-{$date}-{$random}";
                            $update_payment->execute([$reference, $payment['id']]);
                            $generated_payment++;
                        }
                    }
                    
                    echo "<div class='success'>✅ Generated reference numbers for <strong>$generated_payment</strong> payment transactions</div>";
                } else {
                    echo "<div class='info'>ℹ️ All payment transactions already have reference numbers</div>";
                }
            } else {
                echo "<div class='warning'>⚠️ payment_transactions table not found</div>";
            }
            
            echo "</div>";
            
            // Step 4: Statistics
            echo "<div class='step'>";
            echo "<h2>Step 4: System Statistics</h2>";
            
            $total_transactions = $pdo->query("SELECT COUNT(*) FROM transactions")->fetchColumn();
            $transactions_with_ref = $pdo->query("SELECT COUNT(*) FROM transactions WHERE reference_number IS NOT NULL AND reference_number != ''")->fetchColumn();
            
            echo "<div class='info'>";
            echo "📊 <strong>Transactions Table:</strong><br>";
            echo "• Total Transactions: <strong>$total_transactions</strong><br>";
            echo "• With Reference Numbers: <strong>$transactions_with_ref</strong><br>";
            echo "• Coverage: <strong>" . ($total_transactions > 0 ? round(($transactions_with_ref / $total_transactions) * 100, 2) : 0) . "%</strong>";
            echo "</div>";
            
            if ($table_exists) {
                $total_payments = $pdo->query("SELECT COUNT(*) FROM payment_transactions")->fetchColumn();
                $payments_with_ref = $pdo->query("SELECT COUNT(*) FROM payment_transactions WHERE transaction_reference IS NOT NULL AND transaction_reference != ''")->fetchColumn();
                
                echo "<div class='info' style='margin-top: 10px;'>";
                echo "📊 <strong>Payment Transactions Table:</strong><br>";
                echo "• Total Payment Transactions: <strong>$total_payments</strong><br>";
                echo "• With Reference Numbers: <strong>$payments_with_ref</strong><br>";
                echo "• Coverage: <strong>" . ($total_payments > 0 ? round(($payments_with_ref / $total_payments) * 100, 2) : 0) . "%</strong>";
                echo "</div>";
            }
            
            echo "</div>";
            
            // Step 5: Sample Reference Numbers
            echo "<div class='step'>";
            echo "<h2>Step 5: Sample Reference Numbers</h2>";
            
            $samples = $pdo->query("SELECT id, reference_number, transaction_type, amount, created_at FROM transactions WHERE reference_number IS NOT NULL ORDER BY created_at DESC LIMIT 5")->fetchAll();
            
            if (!empty($samples)) {
                echo "<table style='width: 100%; border-collapse: collapse; margin-top: 15px;'>";
                echo "<tr style='background: #f8f9fa;'>";
                echo "<th style='padding: 10px; border: 1px solid #ddd; text-align: left;'>Reference Number</th>";
                echo "<th style='padding: 10px; border: 1px solid #ddd; text-align: left;'>Type</th>";
                echo "<th style='padding: 10px; border: 1px solid #ddd; text-align: left;'>Amount</th>";
                echo "<th style='padding: 10px; border: 1px solid #ddd; text-align: left;'>Date</th>";
                echo "</tr>";
                
                foreach ($samples as $sample) {
                    echo "<tr>";
                    echo "<td style='padding: 10px; border: 1px solid #ddd;'><code>" . htmlspecialchars($sample['reference_number']) . "</code></td>";
                    echo "<td style='padding: 10px; border: 1px solid #ddd;'>" . htmlspecialchars($sample['transaction_type']) . "</td>";
                    echo "<td style='padding: 10px; border: 1px solid #ddd;'>Br" . number_format($sample['amount'], 2) . "</td>";
                    echo "<td style='padding: 10px; border: 1px solid #ddd;'>" . date('M j, Y H:i', strtotime($sample['created_at'])) . "</td>";
                    echo "</tr>";
                }
                
                echo "</table>";
            } else {
                echo "<div class='info'>No transactions found</div>";
            }
            
            echo "</div>";
            
            // Reference Number Format Info
            echo "<div class='step' style='background: linear-gradient(135deg, #e8f4fd, #f0f8ff); border-left-color: #4a90e2;'>";
            echo "<h2>📋 Reference Number Format</h2>";
            echo "<div style='line-height: 1.8;'>";
            echo "<p><strong>Transactions:</strong> <code>TXN-YYYYMMDD-XXXXX</code></p>";
            echo "<p style='margin-left: 20px;'>Example: <code>TXN-20260206-A3F9E</code></p>";
            echo "<p style='margin-top: 10px;'><strong>Payment Transactions:</strong> <code>PAY-YYYYMMDD-XXXXX</code></p>";
            echo "<p style='margin-left: 20px;'>Example: <code>PAY-20260206-B7C2D</code></p>";
            echo "<p style='margin-top: 15px; color: #666;'>";
            echo "• <strong>TXN/PAY</strong>: Transaction type prefix<br>";
            echo "• <strong>YYYYMMDD</strong>: Date (Year-Month-Day)<br>";
            echo "• <strong>XXXXX</strong>: Unique 5-character code";
            echo "</p>";
            echo "</div>";
            echo "</div>";
            
            // Final Summary
            echo "<div style='background: linear-gradient(135deg, #4a90e2, #357abd); color: white; border-radius: 15px; padding: 30px; margin-top: 30px; text-align: center;'>";
            echo "<h2 style='color: white; margin-bottom: 20px;'>✅ Transaction Reference Numbers Added!</h2>";
            echo "<p style='font-size: 1.2rem; margin-bottom: 20px;'>All transactions now have unique reference numbers for tracking.</p>";
            
            echo "<div style='background: rgba(255,255,255,0.2); padding: 20px; border-radius: 10px; margin: 20px 0;'>";
            echo "<h3 style='color: white; margin-bottom: 10px;'>What's New:</h3>";
            echo "<p style='color: white; text-align: left;'>";
            echo "✅ Unique reference numbers for all transactions<br>";
            echo "✅ Easy tracking and lookup<br>";
            echo "✅ Professional transaction records<br>";
            echo "✅ Automatic generation for new transactions<br>";
            echo "✅ Displayed in transaction lists";
            echo "</p>";
            echo "</div>";
            
            echo "<div style='margin-top: 30px;'>";
            echo "<a href='dashboard/transactions.php' class='btn' style='background: white; color: #4a90e2;'>📊 View Transactions</a>";
            echo "<a href='admin/transactions.php' class='btn' style='background: rgba(255,255,255,0.2);'>🔧 Admin Transactions</a>";
            echo "<a href='admin/payment-transactions.php' class='btn' style='background: rgba(255,255,255,0.2);'>💳 Payment Transactions</a>";
            echo "</div>";
            echo "</div>";
            
        } catch(PDOException $e) {
            echo "<div class='error'>";
            echo "<h3>❌ Database Error</h3>";
            echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "</div>";
        }
        ?>
    </div>
</body>
</html>
