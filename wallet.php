<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

$message = '';
$error = '';

try {
    $pdo = new PDO("mysql:host=localhost;dbname=concordial_nexus;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get user info
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    // Get wallet transactions (deposits and purchases)
    $trans_stmt = $pdo->prepare("
        SELECT t.*, p.name as product_name 
        FROM transactions t
        LEFT JOIN deposits d ON t.reference_number LIKE CONCAT('TXN-%') AND t.transaction_type = 'deposit'
        LEFT JOIN products p ON d.product_id = p.id
        WHERE t.user_id = ? 
        ORDER BY t.created_at DESC 
        LIMIT 20
    ");
    $trans_stmt->execute([$_SESSION['user_id']]);
    $transactions = $trans_stmt->fetchAll();
    
    // Get pending deposits
    $pending_stmt = $pdo->prepare("SELECT * FROM deposits WHERE user_id = ? AND status = 'pending' ORDER BY created_at DESC");
    $pending_stmt->execute([$_SESSION['user_id']]);
    $pending_deposits = $pending_stmt->fetchAll();
    
} catch(PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wallet - Concordial Nexus</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .wallet-card { background: white; border-radius: 20px; padding: 40px; margin: 20px 0; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
        
        .balance-display { background: linear-gradient(135deg, #667eea, #764ba2); color: white; border-radius: 20px; padding: 40px; text-align: center; margin-bottom: 30px; position: relative; overflow: hidden; }
        .balance-display::before { content: ''; position: absolute; top: -50%; right: -50%; width: 200%; height: 200%; background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%); }
        .balance-label { font-size: 1.2rem; opacity: 0.9; margin-bottom: 10px; }
        .balance-amount { font-size: 3.5rem; font-weight: bold; margin: 20px 0; }
        .balance-actions { display: flex; gap: 15px; justify-content: center; margin-top: 30px; }
        .btn { padding: 15px 30px; border: none; border-radius: 10px; font-size: 1.1rem; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 10px; transition: transform 0.2s; }
        .btn:hover { transform: translateY(-2px); }
        .btn-primary { background: white; color: #667eea; }
        .btn-success { background: #28a745; color: white; }
        .btn-info { background: #17a2b8; color: white; }
        
        .section-title { font-size: 1.8rem; color: #333; margin: 30px 0 20px 0; padding-bottom: 10px; border-bottom: 3px solid #667eea; }
        
        .pending-deposits { background: #fff3cd; border: 2px solid #ffc107; border-radius: 15px; padding: 20px; margin-bottom: 30px; }
        .pending-item { background: white; padding: 15px; border-radius: 10px; margin: 10px 0; display: flex; justify-content: space-between; align-items: center; }
        
        .transaction-list { background: #f8f9fa; border-radius: 15px; padding: 20px; }
        .transaction-item { background: white; padding: 20px; border-radius: 10px; margin: 10px 0; display: flex; justify-content: space-between; align-items: center; border-left: 4px solid #667eea; }
        .transaction-item.deposit { border-left-color: #28a745; }
        .transaction-item.purchase { border-left-color: #dc3545; }
        .transaction-info { flex: 1; }
        .transaction-type { font-weight: bold; color: #667eea; margin-bottom: 5px; }
        .transaction-desc { color: #666; font-size: 0.9rem; }
        .transaction-date { color: #999; font-size: 0.85rem; margin-top: 5px; }
        .transaction-amount { font-size: 1.5rem; font-weight: bold; }
        .transaction-amount.positive { color: #28a745; }
        .transaction-amount.negative { color: #dc3545; }
        
        .quick-actions { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 30px 0; }
        .action-card { background: linear-gradient(135deg, #f8f9fa, #e9ecef); padding: 30px; border-radius: 15px; text-align: center; cursor: pointer; transition: all 0.3s; border: 2px solid transparent; }
        .action-card:hover { transform: translateY(-5px); border-color: #667eea; box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3); }
        .action-icon { font-size: 3rem; color: #667eea; margin-bottom: 15px; }
        .action-title { font-size: 1.3rem; font-weight: bold; color: #333; margin-bottom: 10px; }
        .action-desc { color: #666; font-size: 0.9rem; }
        
        .message { background: #d4edda; color: #155724; padding: 15px; border-radius: 10px; margin-bottom: 20px; border-left: 4px solid #28a745; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 10px; margin-bottom: 20px; border-left: 4px solid #dc3545; }
    </style>
</head>
<body>
    <?php include '../includes/navigation.php'; ?>
    
    <div class="container" style="margin-top: 100px;">
        <div class="wallet-card">
            <h1 style="text-align: center; color: #333; margin-bottom: 30px;">
                <i class="fas fa-wallet"></i> My Wallet
            </h1>
            
            <?php if ($message): ?>
                <div class="message"><i class="fas fa-check-circle"></i> <?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
            <?php endif; ?>
            
            <!-- Balance Display -->
            <div class="balance-display">
                <div class="balance-label">Available Balance</div>
                <div class="balance-amount">Br<?php echo number_format($user['account_balance'], 2); ?></div>
                <p style="opacity: 0.9;">Ready to invest in your future</p>
                
                <div class="balance-actions">
                    <a href="deposit.php" class="btn btn-primary" style="font-size: 1.2rem; padding: 18px 40px;">
                        <i class="fas fa-plus-circle"></i> Make Deposit
                    </a>
                    <a href="vip-levels.php" class="btn btn-success">
                        <i class="fas fa-shopping-cart"></i> Buy Products
                    </a>
                    <a href="transactions.php" class="btn btn-info">
                        <i class="fas fa-history"></i> History
                    </a>
                </div>
            </div>
            
            <!-- Pending Deposits -->
            <?php if (!empty($pending_deposits)): ?>
                <div class="pending-deposits">
                    <h3 style="color: #856404; margin-bottom: 15px;">
                        <i class="fas fa-clock"></i> Pending Deposits (<?php echo count($pending_deposits); ?>)
                    </h3>
                    <p style="color: #856404; margin-bottom: 15px;">These deposits are waiting for admin approval</p>
                    
                    <?php foreach ($pending_deposits as $deposit): ?>
                        <div class="pending-item">
                            <div>
                                <strong>Br<?php echo number_format($deposit['amount'], 2); ?></strong>
                                <br>
                                <small style="color: #666;">
                                    <?php echo date('M j, Y H:i', strtotime($deposit['created_at'])); ?> | 
                                    Ref: <?php echo htmlspecialchars($deposit['payment_reference']); ?>
                                </small>
                            </div>
                            <span style="background: #ffc107; color: #856404; padding: 5px 15px; border-radius: 20px; font-size: 0.9rem; font-weight: bold;">
                                <i class="fas fa-hourglass-half"></i> Pending
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <!-- Quick Actions -->
            <h2 class="section-title">Quick Actions</h2>
            <div class="quick-actions">
                <a href="deposit.php" class="action-card" style="text-decoration: none; border: 3px solid #667eea; background: linear-gradient(135deg, #667eea, #764ba2); color: white;">
                    <div class="action-icon" style="color: white;"><i class="fas fa-plus-circle"></i></div>
                    <div class="action-title" style="color: white;">Make Deposit</div>
                    <div class="action-desc" style="color: rgba(255,255,255,0.9);">Add money to your wallet</div>
                </a>
                
                <a href="vip-levels.php" class="action-card" style="text-decoration: none;">
                    <div class="action-icon"><i class="fas fa-shopping-cart"></i></div>
                    <div class="action-title">Buy Products</div>
                    <div class="action-desc">Purchase investment packages</div>
                </a>
                
                <a href="investments.php" class="action-card" style="text-decoration: none;">
                    <div class="action-icon"><i class="fas fa-chart-line"></i></div>
                    <div class="action-title">My Investments</div>
                    <div class="action-desc">View active investments</div>
                </a>
                
                <a href="referrals.php" class="action-card" style="text-decoration: none;">
                    <div class="action-icon"><i class="fas fa-users"></i></div>
                    <div class="action-title">Refer Friends</div>
                    <div class="action-desc">Earn commission rewards</div>
                </a>
            </div>
            
            <!-- Recent Transactions -->
            <h2 class="section-title">Recent Activity</h2>
            <div class="transaction-list">
                <?php if (empty($transactions)): ?>
                    <div style="text-align: center; padding: 40px; color: #666;">
                        <i class="fas fa-inbox" style="font-size: 3rem; opacity: 0.3; margin-bottom: 15px;"></i>
                        <p>No transactions yet</p>
                        <a href="deposit.php" class="btn btn-primary" style="margin-top: 20px;">
                            <i class="fas fa-plus-circle"></i> Make Your First Deposit
                        </a>
                    </div>
                <?php else: ?>
                    <?php foreach ($transactions as $trans): ?>
                        <div class="transaction-item <?php echo $trans['transaction_type']; ?>">
                            <div class="transaction-info">
                                <div class="transaction-type">
                                    <i class="fas fa-<?php echo $trans['transaction_type'] === 'deposit' ? 'plus-circle' : 'shopping-cart'; ?>"></i>
                                    <?php echo ucfirst($trans['transaction_type']); ?>
                                </div>
                                <div class="transaction-desc">
                                    <?php echo htmlspecialchars($trans['description'] ?? 'Transaction'); ?>
                                    <?php if ($trans['product_name']): ?>
                                        <br><small><?php echo htmlspecialchars($trans['product_name']); ?></small>
                                    <?php endif; ?>
                                </div>
                                <div class="transaction-date">
                                    <?php echo date('M j, Y \a\t H:i', strtotime($trans['created_at'])); ?>
                                </div>
                            </div>
                            <div class="transaction-amount <?php echo $trans['transaction_type'] === 'deposit' ? 'positive' : 'negative'; ?>">
                                <?php echo $trans['transaction_type'] === 'deposit' ? '+' : '-'; ?>Br<?php echo number_format($trans['amount'], 2); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <div style="text-align: center; margin-top: 20px;">
                        <a href="transactions.php" style="color: #667eea; text-decoration: none; font-weight: 600;">
                            View All Transactions <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
