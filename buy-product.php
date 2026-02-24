<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

$message = '';
$error = '';
$product = null;

try {
    $pdo = new PDO("mysql:host=localhost;dbname=concordial_nexus;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get user info
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    // Get product if ID provided
    if (isset($_GET['id'])) {
        $product_stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND status = 'active'");
        $product_stmt->execute([$_GET['id']]);
        $product = $product_stmt->fetch();
    }
    
    // Handle purchase
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buy_product'])) {
        $product_id = intval($_POST['product_id']);
        
        // Get product details
        $product_stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND status = 'active'");
        $product_stmt->execute([$product_id]);
        $product = $product_stmt->fetch();
        
        if (!$product) {
            $error = "Product not found or not available";
        } elseif ($user['account_balance'] < $product['min_amount']) {
            $error = "Insufficient balance! You need Br" . number_format($product['min_amount'], 2) . " but have Br" . number_format($user['account_balance'], 2);
        } else {
            $pdo->beginTransaction();
            
            try {
                $amount = $product['min_amount'];
                
                // Calculate returns
                $profit = ($amount * $product['return_percentage']) / 100;
                $total_return = $amount + $profit;
                $start_date = date('Y-m-d');
                $end_date = date('Y-m-d', strtotime("+{$product['duration_days']} days"));
                
                // Create investment
                $inv_stmt = $pdo->prepare("
                    INSERT INTO investments 
                    (user_id, product_id, amount, expected_return, start_date, end_date, status, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, 'active', NOW())
                ");
                $inv_stmt->execute([$user['id'], $product_id, $amount, $profit, $start_date, $end_date]);
                $investment_id = $pdo->lastInsertId();
                
                // Deduct from wallet
                $update_balance = $pdo->prepare("UPDATE users SET account_balance = account_balance - ? WHERE id = ?");
                $update_balance->execute([$amount, $user['id']]);
                
                // Create transaction record
                $ref = 'INV-' . date('Ymd') . '-' . strtoupper(substr(md5($investment_id . time()), 0, 6));
                $trans_stmt = $pdo->prepare("
                    INSERT INTO transactions 
                    (user_id, transaction_type, amount, description, reference_number, status, created_at) 
                    VALUES (?, 'investment', ?, ?, ?, 'completed', NOW())
                ");
                $trans_stmt->execute([
                    $user['id'],
                    $amount,
                    "Purchased {$product['name']} - {$product['return_percentage']}% return in {$product['duration_days']} days",
                    $ref
                ]);
                
                $pdo->commit();
                
                $message = "Success! You purchased {$product['name']} for Br" . number_format($amount, 2) . ". Expected profit: Br" . number_format($profit, 2) . " in {$product['duration_days']} days.";
                
                // Refresh user balance
                $stmt->execute([$_SESSION['user_id']]);
                $user = $stmt->fetch();
                
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "Purchase failed: " . $e->getMessage();
            }
        }
    }
    
} catch(PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buy Product - Concordial Nexus</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .container { max-width: 800px; margin: 0 auto; padding: 20px; }
        .card { background: white; border-radius: 20px; padding: 40px; margin: 20px 0; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
        
        .wallet-balance { background: linear-gradient(135deg, #28a745, #20c997); color: white; border-radius: 15px; padding: 20px; text-align: center; margin-bottom: 30px; }
        .balance-label { font-size: 1rem; opacity: 0.9; }
        .balance-amount { font-size: 2.5rem; font-weight: bold; margin: 10px 0; }
        
        .product-details { background: #f8f9fa; border-radius: 15px; padding: 30px; margin: 20px 0; }
        .product-name { font-size: 2rem; color: #333; margin-bottom: 15px; }
        .product-category { display: inline-block; background: #667eea; color: white; padding: 5px 15px; border-radius: 20px; font-size: 0.9rem; margin-bottom: 15px; }
        .product-info { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin: 20px 0; }
        .info-item { background: white; padding: 20px; border-radius: 10px; text-align: center; }
        .info-label { color: #666; font-size: 0.9rem; margin-bottom: 5px; }
        .info-value { font-size: 1.5rem; font-weight: bold; color: #667eea; }
        
        .calculation { background: #e8f5e9; border: 2px solid #28a745; border-radius: 15px; padding: 25px; margin: 20px 0; }
        .calc-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #c8e6c9; }
        .calc-row:last-child { border-bottom: none; font-size: 1.3rem; font-weight: bold; color: #28a745; }
        
        .btn { padding: 15px 30px; border: none; border-radius: 10px; font-size: 1.1rem; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; transition: transform 0.2s; }
        .btn:hover { transform: translateY(-2px); }
        .btn-primary { background: linear-gradient(135deg, #667eea, #764ba2); color: white; width: 100%; }
        .btn-secondary { background: #6c757d; color: white; }
        
        .message { background: #d4edda; color: #155724; padding: 15px; border-radius: 10px; margin-bottom: 20px; border-left: 4px solid #28a745; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 10px; margin-bottom: 20px; border-left: 4px solid #dc3545; }
        
        .warning { background: #fff3cd; border: 2px solid #ffc107; border-radius: 10px; padding: 20px; margin: 20px 0; color: #856404; }
    </style>
</head>
<body>
    <?php include '../includes/navigation.php'; ?>
    
    <div class="container" style="margin-top: 100px;">
        <div class="card">
            <h1 style="text-align: center; color: #333; margin-bottom: 30px;">
                <i class="fas fa-shopping-cart"></i> Buy Investment Product
            </h1>
            
            <?php if ($message): ?>
                <div class="message">
                    <i class="fas fa-check-circle"></i> <?php echo $message; ?>
                    <div style="margin-top: 15px;">
                        <a href="investments.php" class="btn btn-primary" style="width: auto; margin-right: 10px;">
                            <i class="fas fa-chart-line"></i> View My Investments
                        </a>
                        <a href="vip-levels.php" class="btn btn-secondary" style="width: auto;">
                            <i class="fas fa-shopping-cart"></i> Buy More
                        </a>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    <?php if (strpos($error, 'Insufficient balance') !== false): ?>
                        <div style="margin-top: 15px;">
                            <a href="deposit.php" class="btn btn-primary" style="width: auto;">
                                <i class="fas fa-plus-circle"></i> Add Money to Wallet
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <!-- Wallet Balance -->
            <div class="wallet-balance">
                <div class="balance-label">Your Wallet Balance</div>
                <div class="balance-amount">Br<?php echo number_format($user['account_balance'], 2); ?></div>
                <p style="opacity: 0.9; font-size: 0.9rem;">Available for purchases</p>
            </div>
            
            <?php if ($product): ?>
                <!-- Product Details -->
                <div class="product-details">
                    <div class="product-category"><?php echo strtoupper(str_replace('_', ' ', $product['category'])); ?></div>
                    <h2 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h2>
                    <p style="color: #666; margin-bottom: 20px;"><?php echo htmlspecialchars($product['description'] ?? 'Premium investment package'); ?></p>
                    
                    <div class="product-info">
                        <div class="info-item">
                            <div class="info-label">Investment Amount</div>
                            <div class="info-value">Br<?php echo number_format($product['min_amount']); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Return Rate</div>
                            <div class="info-value"><?php echo $product['return_percentage']; ?>%</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Duration</div>
                            <div class="info-value"><?php echo $product['duration_days']; ?> days</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Expected Profit</div>
                            <div class="info-value" style="color: #28a745;">
                                Br<?php echo number_format(($product['min_amount'] * $product['return_percentage']) / 100); ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Calculation Breakdown -->
                <div class="calculation">
                    <h3 style="color: #28a745; margin-bottom: 15px;">
                        <i class="fas fa-calculator"></i> Investment Breakdown
                    </h3>
                    <div class="calc-row">
                        <span>Investment Amount:</span>
                        <span>Br<?php echo number_format($product['min_amount'], 2); ?></span>
                    </div>
                    <div class="calc-row">
                        <span>Return Rate:</span>
                        <span><?php echo $product['return_percentage']; ?>%</span>
                    </div>
                    <div class="calc-row">
                        <span>Expected Profit:</span>
                        <span>Br<?php echo number_format(($product['min_amount'] * $product['return_percentage']) / 100, 2); ?></span>
                    </div>
                    <div class="calc-row">
                        <span>Total Return:</span>
                        <span>Br<?php echo number_format($product['min_amount'] + (($product['min_amount'] * $product['return_percentage']) / 100), 2); ?></span>
                    </div>
                </div>
                
                <?php if ($user['account_balance'] < $product['min_amount']): ?>
                    <div class="warning">
                        <h4 style="margin-bottom: 10px;"><i class="fas fa-exclamation-triangle"></i> Insufficient Balance</h4>
                        <p>You need Br<?php echo number_format($product['min_amount'], 2); ?> but have Br<?php echo number_format($user['account_balance'], 2); ?></p>
                        <p style="margin-top: 10px;">Please add Br<?php echo number_format($product['min_amount'] - $user['account_balance'], 2); ?> more to your wallet.</p>
                        <a href="deposit.php" class="btn btn-primary" style="margin-top: 15px; width: auto;">
                            <i class="fas fa-plus-circle"></i> Add Money Now
                        </a>
                    </div>
                <?php else: ?>
                    <!-- Purchase Form -->
                    <form method="POST" onsubmit="return confirm('Confirm purchase of <?php echo htmlspecialchars($product['name']); ?> for Br<?php echo number_format($product['min_amount'], 2); ?>?');">
                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                        
                        <div style="background: #e3f2fd; border: 2px solid #2196f3; border-radius: 10px; padding: 20px; margin: 20px 0;">
                            <h4 style="color: #1976d2; margin-bottom: 10px;">
                                <i class="fas fa-info-circle"></i> Payment Method
                            </h4>
                            <p style="color: #1565c0; font-size: 1.1rem; font-weight: 600;">
                                <i class="fas fa-wallet"></i> Pay from Wallet Balance
                            </p>
                            <p style="color: #666; margin-top: 10px; font-size: 0.9rem;">
                                Amount will be deducted instantly from your wallet
                            </p>
                        </div>
                        
                        <button type="submit" name="buy_product" class="btn btn-primary">
                            <i class="fas fa-shopping-cart"></i> Buy Now for Br<?php echo number_format($product['min_amount'], 2); ?>
                        </button>
                    </form>
                <?php endif; ?>
                
                <div style="text-align: center; margin-top: 20px;">
                    <a href="vip-levels.php" style="color: #667eea; text-decoration: none;">
                        <i class="fas fa-arrow-left"></i> Back to Products
                    </a>
                </div>
                
            <?php else: ?>
                <div style="text-align: center; padding: 40px; color: #666;">
                    <i class="fas fa-shopping-cart" style="font-size: 4rem; opacity: 0.3; margin-bottom: 20px;"></i>
                    <h3>No Product Selected</h3>
                    <p style="margin: 20px 0;">Please select a product to purchase</p>
                    <a href="vip-levels.php" class="btn btn-primary" style="width: auto;">
                        <i class="fas fa-th-large"></i> Browse Products
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
