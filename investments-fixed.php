<?php
// Start session at the very beginning - BEFORE any HTML output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

// Initialize all variables to prevent undefined variable errors
$user = ['full_name' => 'User', 'account_balance' => 0];
$investments = [];
$available_levels = [];
$total_investments = 0;
$active_investments = 0;
$completed_investments = 0;
$total_expected_returns = 0;
$total_actual_returns = 0;
$error_message = '';

try {
    $pdo = new PDO("mysql:host=localhost;dbname=concordial_nexus;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get user info
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!$user) {
        session_destroy();
        header('Location: ../auth/login.php');
        exit();
    }
    
    // Get available investment levels
    try {
        $levels_stmt = $pdo->query("SELECT * FROM trading_levels WHERE is_active = 1 ORDER BY level_number");
        $available_levels = $levels_stmt->fetchAll();
    } catch(PDOException $e) {
        // Table might not exist yet
        $available_levels = [];
    }
    
    // Get user investments
    try {
        $inv_stmt = $pdo->prepare("
            SELECT i.*, tl.level_name, tl.level_number, tl.expected_return_percentage, tl.duration_days, tl.category
            FROM investments i 
            JOIN trading_levels tl ON i.trading_level_id = tl.id 
            WHERE i.user_id = ? 
            ORDER BY i.created_at DESC
        ");
        $inv_stmt->execute([$_SESSION['user_id']]);
        $investments = $inv_stmt->fetchAll();
    } catch(PDOException $e) {
        // Tables might not exist yet
        $investments = [];
    }
    
    // Calculate statistics
    foreach ($investments as $investment) {
        $total_investments++;
        if (isset($investment['status'])) {
            if ($investment['status'] === 'active') {
                $active_investments++;
                $total_expected_returns += $investment['expected_return'] ?? 0;
            } elseif ($investment['status'] === 'completed') {
                $completed_investments++;
                $total_actual_returns += $investment['actual_return'] ?? 0;
            }
        }
    }
    
} catch(PDOException $e) {
    $error_message = "Database connection error. Please make sure the database is set up properly.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Investments - Concordial Nexus</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <header class="header">
        <nav class="navbar">
            <div class="nav-container">
                <div class="nav-logo">
                    <a href="../index.html"><i class="fas fa-chart-line"></i> Concordial Nexus</a>
                </div>
                <ul class="nav-menu">
                    <li class="nav-item"><a href="index.php" class="nav-link">Dashboard</a></li>
                    <li class="nav-item"><a href="transactions.php" class="nav-link">Transactions</a></li>
                    <li class="nav-item"><a href="investments.php" class="nav-link active">Investments</a></li>
                    <li class="nav-item"><a href="profile.php" class="nav-link">Profile</a></li>
                    <li class="nav-item"><a href="../auth/logout.php" class="nav-link">Logout</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <main style="margin-top: 100px; padding: 2rem;">
        <div class="container">
            <div style="background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(20px); border-radius: 20px; padding: 3rem; border: 1px solid rgba(34, 139, 34, 0.2);">
                
                <?php if ($error_message): ?>
                    <div style="background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 10px; margin-bottom: 2rem; text-align: center;">
                        <h3>⚠️ Database Error</h3>
                        <p><?php echo htmlspecialchars($error_message); ?></p>
                        <p><a href="../setup-investment-system-fixed.php" style="color: #721c24; font-weight: bold;">Click here to setup the investment system</a></p>
                    </div>
                <?php endif; ?>
                
                <div style="text-align: center; margin-bottom: 3rem;">
                    <h1 style="color: #ffd700; margin-bottom: 1rem;">
                        <i class="fas fa-chart-line"></i> My Investments
                    </h1>
                    <p style="color: rgba(255, 255, 255, 0.8);">
                        Track your Ethiopian Birr trading investments
                    </p>
                </div>

                <!-- Investment Statistics -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 2rem; margin-bottom: 3rem;">
                    <div style="background: rgba(34, 139, 34, 0.1); border: 1px solid rgba(34, 139, 34, 0.3); border-radius: 15px; padding: 2rem; text-align: center;">
                        <h4 style="color: #228b22; margin-bottom: 1rem;">
                            <i class="fas fa-chart-pie"></i> Total Investments
                        </h4>
                        <div style="font-size: 2rem; color: #ffd700; font-weight: bold;">
                            <?php echo $total_investments; ?>
                        </div>
                    </div>
                    
                    <div style="background: rgba(255, 215, 0, 0.1); border: 1px solid rgba(255, 215, 0, 0.3); border-radius: 15px; padding: 2rem; text-align: center;">
                        <h4 style="color: #ffd700; margin-bottom: 1rem;">
                            <i class="fas fa-clock"></i> Active
                        </h4>
                        <div style="font-size: 2rem; color: #228b22; font-weight: bold;">
                            <?php echo $active_investments; ?>
                        </div>
                    </div>
                    
                    <div style="background: rgba(34, 139, 34, 0.1); border: 1px solid rgba(34, 139, 34, 0.3); border-radius: 15px; padding: 2rem; text-align: center;">
                        <h4 style="color: #228b22; margin-bottom: 1rem;">
                            <i class="fas fa-check-circle"></i> Completed
                        </h4>
                        <div style="font-size: 2rem; color: #ffd700; font-weight: bold;">
                            <?php echo $completed_investments; ?>
                        </div>
                    </div>
                    
                    <div style="background: rgba(255, 215, 0, 0.1); border: 1px solid rgba(255, 215, 0, 0.3); border-radius: 15px; padding: 2rem; text-align: center;">
                        <h4 style="color: #ffd700; margin-bottom: 1rem;">
                            <i class="fas fa-coins"></i> Expected Returns
                        </h4>
                        <div style="font-size: 1.5rem; color: #228b22; font-weight: bold;">
                            Br<?php echo number_format($total_expected_returns, 2); ?>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div style="text-align: center; margin-bottom: 3rem;">
                    <a href="#investment-packages" style="background: linear-gradient(45deg, #228b22, #ffd700); color: #0a0e1a; padding: 1rem 2rem; border-radius: 10px; text-decoration: none; font-weight: 600; margin: 0 1rem;">
                        <i class="fas fa-plus"></i> Browse Investment Packages
                    </a>
                    <a href="payment-methods.php" style="background: rgba(255, 255, 255, 0.1); color: #ffd700; padding: 1rem 2rem; border: 1px solid rgba(255, 215, 0, 0.3); border-radius: 10px; text-decoration: none; font-weight: 600; margin: 0 1rem;">
                        <i class="fas fa-credit-card"></i> Payment Methods
                    </a>
                </div>

                <!-- Available Investment Packages -->
                <?php if (!empty($available_levels)): ?>
                <div id="investment-packages" style="background: rgba(255, 255, 255, 0.05); border-radius: 15px; padding: 2rem; border: 1px solid rgba(34, 139, 34, 0.2); margin-bottom: 3rem;">
                    <h3 style="color: #ffd700; margin-bottom: 2rem; text-align: center;">
                        <i class="fas fa-gem"></i> Available Investment Packages
                    </h3>
                    
                    <?php
                    // Group levels by category
                    $categories = ['Regular', 'Premium', 'Advanced Premium'];
                    foreach ($categories as $category):
                        $category_levels = array_filter($available_levels, function($level) use ($category) {
                            return ($level['category'] ?? 'Regular') === $category;
                        });
                        
                        if (empty($category_levels)) continue;
                        
                        // Category colors
                        $category_colors = [
                            'Regular' => ['bg' => 'rgba(74, 144, 226, 0.1)', 'border' => 'rgba(74, 144, 226, 0.3)', 'text' => '#4a90e2'],
                            'Premium' => ['bg' => 'rgba(255, 215, 0, 0.1)', 'border' => 'rgba(255, 215, 0, 0.3)', 'text' => '#ffd700'],
                            'Advanced Premium' => ['bg' => 'rgba(231, 76, 60, 0.1)', 'border' => 'rgba(231, 76, 60, 0.3)', 'text' => '#e74c3c']
                        ];
                        $colors = $category_colors[$category];
                    ?>
                    
                    <div style="background: <?php echo $colors['bg']; ?>; border: 1px solid <?php echo $colors['border']; ?>; border-radius: 15px; padding: 2rem; margin-bottom: 2rem;">
                        <h4 style="color: <?php echo $colors['text']; ?>; text-align: center; margin-bottom: 1.5rem; font-size: 1.5rem;">
                            <?php if ($category === 'Regular'): ?>
                                🥉 <?php echo $category; ?> Levels
                            <?php elseif ($category === 'Premium'): ?>
                                🥈 <?php echo $category; ?> Levels
                            <?php else: ?>
                                🥇 <?php echo $category; ?> Levels
                            <?php endif; ?>
                        </h4>
                        
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
                            <?php foreach ($category_levels as $level): 
                                $profit = ($level['min_investment'] * $level['expected_return_percentage']) / 100;
                                $daily_return = $profit / $level['duration_days'];
                            ?>
                            <div style="background: rgba(255, 255, 255, 0.1); border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 10px; padding: 1.5rem; position: relative;">
                                <!-- Return Badge -->
                                <div style="position: absolute; top: -10px; right: 15px; background: <?php echo $colors['text']; ?>; color: white; padding: 0.5rem 1rem; border-radius: 20px; font-weight: bold; font-size: 0.9rem;">
                                    <?php echo $level['expected_return_percentage']; ?>% Return
                                </div>
                                
                                <h5 style="color: #ffd700; margin-bottom: 1rem; font-size: 1.1rem;">
                                    <?php echo htmlspecialchars($level['level_name']); ?>
                                </h5>
                                
                                <div style="color: rgba(255, 255, 255, 0.9); line-height: 1.6; margin-bottom: 1.5rem;">
                                    <p style="font-size: 1.3rem; font-weight: bold; color: #ffd700; margin-bottom: 0.5rem;">
                                        <i class="fas fa-coins"></i> Br<?php echo number_format($level['min_investment']); ?>
                                    </p>
                                    <p><strong>Duration:</strong> <?php echo $level['duration_days']; ?> days</p>
                                    <p><strong>Expected Profit:</strong> <span style="color: #228b22; font-weight: bold;">Br<?php echo number_format($profit); ?></span></p>
                                    <p><strong>Daily Return:</strong> <span style="color: #32cd32;">Br<?php echo number_format($daily_return); ?>/day</span></p>
                                    <p><strong>Total Return:</strong> <span style="color: #ffd700; font-weight: bold;">Br<?php echo number_format($level['min_investment'] + $profit); ?></span></p>
                                </div>
                                
                                <div style="text-align: center;">
                                    <a href="transactions.php?invest=<?php echo $level['id']; ?>" style="background: linear-gradient(45deg, #228b22, #ffd700); color: #0a0e1a; padding: 0.75rem 1.5rem; border-radius: 8px; text-decoration: none; font-weight: 600; display: inline-block; width: 100%;">
                                        <i class="fas fa-rocket"></i> Invest Now
                                    </a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 3rem; background: rgba(255, 255, 255, 0.05); border-radius: 15px; margin-bottom: 3rem;">
                        <i class="fas fa-exclamation-triangle" style="font-size: 4rem; color: #ffd700; margin-bottom: 1rem;"></i>
                        <h3 style="color: #ffd700; margin-bottom: 1rem;">No Investment Packages Available</h3>
                        <p style="color: rgba(255, 255, 255, 0.8); margin-bottom: 2rem;">The investment system needs to be set up first.</p>
                        <a href="../setup-investment-system-fixed.php" style="background: linear-gradient(45deg, #228b22, #ffd700); color: #0a0e1a; padding: 1rem 2rem; border-radius: 10px; text-decoration: none; font-weight: 600;">
                            <i class="fas fa-cog"></i> Setup Investment System
                        </a>
                    </div>
                <?php endif; ?>

                <!-- Investments List -->
                <div style="background: rgba(255, 255, 255, 0.05); border-radius: 15px; padding: 2rem; border: 1px solid rgba(34, 139, 34, 0.2);">
                    <h3 style="color: #ffd700; margin-bottom: 2rem; text-align: center;">
                        <i class="fas fa-list"></i> My Investment Portfolio
                    </h3>
                    
                    <?php if (empty($investments)): ?>
                        <div style="text-align: center; color: rgba(255, 255, 255, 0.6); padding: 3rem;">
                            <i class="fas fa-chart-line" style="font-size: 4rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                            <h4 style="color: rgba(255, 255, 255, 0.7); margin-bottom: 1rem;">No Investments Yet</h4>
                            <p style="margin-bottom: 2rem;">Start your Ethiopian Birr trading journey by choosing from our investment packages above!</p>
                            <a href="#investment-packages" style="background: linear-gradient(45deg, #228b22, #ffd700); color: #0a0e1a; padding: 1rem 2rem; border-radius: 10px; text-decoration: none; font-weight: 600;">
                                <i class="fas fa-arrow-up"></i> Browse Investment Packages
                            </a>
                        </div>
                    <?php else: ?>
                        <div style="display: grid; gap: 2rem;">
                            <?php foreach ($investments as $investment): ?>
                                <div style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(34, 139, 34, 0.2); border-radius: 15px; padding: 2rem;">
                                    <h4 style="color: #ffd700; margin-bottom: 1rem;">
                                        <i class="fas fa-layer-group"></i> <?php echo htmlspecialchars($investment['level_name'] ?? 'Investment Package'); ?>
                                    </h4>
                                    <div style="color: rgba(255, 255, 255, 0.9);">
                                        <p><strong>Investment:</strong> Br<?php echo number_format($investment['amount'] ?? 0, 2); ?></p>
                                        <p><strong>Expected Return:</strong> Br<?php echo number_format($investment['expected_return'] ?? 0, 2); ?></p>
                                        <p><strong>Status:</strong> <?php echo ucfirst($investment['status'] ?? 'pending'); ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
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