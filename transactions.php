<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions - Concordial Nexus</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php
    session_start();
    
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../auth/login.php');
        exit();
    }
    
    try {
        $pdo = new PDO("mysql:host=localhost;dbname=concordial_nexus;charset=utf8mb4", "root", "");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Get user info
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        // Get recent transactions
        $trans_stmt = $pdo->prepare("SELECT t.*, 
                                     COALESCE(p.name, 'N/A') as product_name
                                     FROM transactions t 
                                     LEFT JOIN deposits d ON t.user_id = d.user_id AND t.reference_number = CONCAT('TXN-', DATE_FORMAT(d.created_at, '%Y%m%d'))
                                     LEFT JOIN products p ON d.product_id = p.id
                                     WHERE t.user_id = ? 
                                     ORDER BY t.created_at DESC 
                                     LIMIT 50");
        $trans_stmt->execute([$_SESSION['user_id']]);
        $transactions = $trans_stmt->fetchAll();
        
    } catch(PDOException $e) {
        $error_message = "Database error: " . $e->getMessage();
        $transactions = [];
    }
    ?>
    
    <header class="header">
        <nav class="navbar">
            <div class="nav-container">
                <div class="nav-logo">
                    <a href="../index.html"><i class="fas fa-chart-line"></i> Concordial Nexus</a>
                </div>
                <ul class="nav-menu">
                    <li class="nav-item"><a href="index.php" class="nav-link">Dashboard</a></li>
                    <li class="nav-item"><a href="transactions.php" class="nav-link active">Transactions</a></li>
                    <li class="nav-item"><a href="investments.php" class="nav-link">Investments</a></li>
                    <li class="nav-item"><a href="deposit.php" class="nav-link">Deposit</a></li>
                    <li class="nav-item"><a href="profile.php" class="nav-link">Profile</a></li>
                    <li class="nav-item"><a href="../auth/logout.php" class="nav-link">Logout</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <main style="margin-top: 100px; padding: 2rem;">
        <div class="container">
            <div style="background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(20px); border-radius: 20px; padding: 3rem; border: 1px solid rgba(34, 139, 34, 0.2);">
                
                <div style="text-align: center; margin-bottom: 3rem;">
                    <h1 style="color: #ffd700; margin-bottom: 1rem;">
                        <i class="fas fa-exchange-alt"></i> Transaction History
                    </h1>
                    <p style="color: rgba(255, 255, 255, 0.8);">
                        View all your transactions and account activity
                    </p>
                </div>

                <?php if (isset($error_message)): ?>
                    <div style="background: rgba(255, 82, 82, 0.2); color: #ff5252; padding: 1rem; border-radius: 8px; margin-bottom: 2rem; text-align: center; border: 1px solid rgba(255, 82, 82, 0.3);">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>

                <!-- Account Balance -->
                <div style="background: rgba(255, 215, 0, 0.1); border: 1px solid rgba(255, 215, 0, 0.3); border-radius: 15px; padding: 2rem; margin-bottom: 3rem; text-align: center;">
                    <h3 style="color: #ffd700; margin-bottom: 1rem;">
                        <i class="fas fa-wallet"></i> Current Balance
                    </h3>
                    <div style="font-size: 2.5rem; color: #228b22; font-weight: bold;">
                        Br<?php echo number_format($user['account_balance'], 2); ?>
                    </div>
                    <p style="color: rgba(255, 255, 255, 0.8); margin-top: 1rem;">
                        Available for investments
                    </p>
                    <div style="margin-top: 2rem;">
                        <a href="deposit.php" style="background: linear-gradient(45deg, #228b22, #32cd32); color: white; padding: 1rem 2rem; border-radius: 8px; text-decoration: none; margin: 0 0.5rem; display: inline-block;">
                            <i class="fas fa-plus-circle"></i> Make Deposit
                        </a>
                        <a href="investments.php" style="background: linear-gradient(45deg, #ffd700, #ffed4e); color: #0a0e1a; padding: 1rem 2rem; border-radius: 8px; text-decoration: none; margin: 0 0.5rem; display: inline-block;">
                            <i class="fas fa-chart-line"></i> View Investments
                        </a>
                    </div>
                </div>

                <!-- Recent Transactions -->
                <div style="background: rgba(255, 255, 255, 0.05); border-radius: 15px; padding: 2rem; border: 1px solid rgba(34, 139, 34, 0.2);">
                    <h3 style="color: #ffd700; margin-bottom: 2rem; text-align: center;">
                        <i class="fas fa-history"></i> Recent Transactions
                    </h3>
                    
                    <?php if (empty($transactions)): ?>
                        <div style="text-align: center; color: rgba(255, 255, 255, 0.6); padding: 2rem;">
                            <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                            <p>No transactions yet. Start by making a deposit!</p>
                            <a href="deposit.php" style="background: linear-gradient(45deg, #228b22, #32cd32); color: white; padding: 1rem 2rem; border-radius: 8px; text-decoration: none; display: inline-block; margin-top: 1rem;">
                                <i class="fas fa-plus-circle"></i> Make Your First Deposit
                            </a>
                        </div>
                    <?php else: ?>
                        <div style="overflow-x: auto;">
                            <table style="width: 100%; border-collapse: collapse;">
                                <thead>
                                    <tr style="border-bottom: 2px solid rgba(34, 139, 34, 0.3);">
                                        <th style="padding: 1rem; text-align: left; color: #ffd700;">Date</th>
                                        <th style="padding: 1rem; text-align: left; color: #ffd700;">Type</th>
                                        <th style="padding: 1rem; text-align: left; color: #ffd700;">Description</th>
                                        <th style="padding: 1rem; text-align: right; color: #ffd700;">Amount</th>
                                        <th style="padding: 1rem; text-align: center; color: #ffd700;">Status</th>
                                        <th style="padding: 1rem; text-align: left; color: #ffd700;">Reference</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($transactions as $transaction): ?>
                                        <tr style="border-bottom: 1px solid rgba(34, 139, 34, 0.1);">
                                            <td style="padding: 1rem; color: rgba(255, 255, 255, 0.9);">
                                                <?php echo date('M j, Y H:i', strtotime($transaction['created_at'])); ?>
                                            </td>
                                            <td style="padding: 1rem;">
                                                <?php
                                                $type_colors = [
                                                    'deposit' => '#228b22',
                                                    'withdrawal' => '#ffd700',
                                                    'investment' => '#32cd32',
                                                    'return' => '#00ff7f',
                                                    'profit' => '#ffd700',
                                                    'commission' => '#ff6b6b'
                                                ];
                                                $type_icons = [
                                                    'deposit' => 'plus-circle',
                                                    'withdrawal' => 'minus-circle',
                                                    'investment' => 'chart-line',
                                                    'return' => 'coins',
                                                    'profit' => 'trophy',
                                                    'commission' => 'percentage'
                                                ];
                                                $color = $type_colors[$transaction['transaction_type']] ?? '#228b22';
                                                $icon = $type_icons[$transaction['transaction_type']] ?? 'exchange-alt';
                                                ?>
                                                <span style="background: rgba(34, 139, 34, 0.2); color: <?php echo $color; ?>; padding: 0.25rem 0.75rem; border-radius: 15px; font-size: 0.875rem;">
                                                    <i class="fas fa-<?php echo $icon; ?>"></i>
                                                    <?php echo ucfirst($transaction['transaction_type']); ?>
                                                </span>
                                            </td>
                                            <td style="padding: 1rem; color: rgba(255, 255, 255, 0.9);">
                                                <?php echo htmlspecialchars($transaction['description'] ?? 'Transaction'); ?>
                                                <?php if ($transaction['product_name'] && $transaction['product_name'] !== 'N/A'): ?>
                                                    <br><small style="color: rgba(255, 255, 255, 0.6);"><?php echo htmlspecialchars($transaction['product_name']); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td style="padding: 1rem; text-align: right; color: <?php echo $transaction['transaction_type'] === 'withdrawal' ? '#ff6b6b' : '#228b22'; ?>; font-weight: 600;">
                                                <?php echo $transaction['transaction_type'] === 'withdrawal' ? '-' : '+'; ?>Br<?php echo number_format($transaction['amount'], 2); ?>
                                            </td>
                                            <td style="padding: 1rem; text-align: center;">
                                                <?php
                                                $status_colors = [
                                                    'completed' => '#228b22',
                                                    'pending' => '#ffd700',
                                                    'processing' => '#32cd32',
                                                    'failed' => '#ff5252',
                                                    'cancelled' => '#ff6b6b'
                                                ];
                                                $status_color = $status_colors[$transaction['status']] ?? '#228b22';
                                                ?>
                                                <span style="background: rgba(34, 139, 34, 0.2); color: <?php echo $status_color; ?>; padding: 0.25rem 0.75rem; border-radius: 15px; font-size: 0.875rem;">
                                                    <?php echo ucfirst($transaction['status']); ?>
                                                </span>
                                            </td>
                                            <td style="padding: 1rem; color: rgba(255, 255, 255, 0.7); font-family: monospace;">
                                                <?php echo htmlspecialchars($transaction['reference_number'] ?? 'N/A'); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <div class="footer-bottom">
                <p>&copy; 2026 Concordial Nexus. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>
