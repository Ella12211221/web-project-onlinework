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
    
    // Initialize variables to prevent undefined variable errors
    $investments = [];
    $available_levels = [];
    $total_investments = 0;
    $active_investments = 0;
    $completed_investments = 0;
    $total_expected_returns = 0;
    $total_actual_returns = 0;
    
    // Get user investments with trading level info
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
        // Investments table might not exist yet
        $investments = [];
    }
    
    // Get available investment levels for new investments
    try {
        $levels_stmt = $pdo->query("SELECT * FROM trading_levels WHERE is_active = 1 ORDER BY level_number");
        $available_levels = $levels_stmt->fetchAll();
    } catch(PDOException $e) {
        // Trading levels table might not exist yet
        $available_levels = [];
    }
    
    // Calculate investment statistics
    foreach ($investments as $investment) {
        $total_investments++;
        if ($investment['status'] === 'active') {
            $active_investments++;
            $total_expected_returns += $investment['expected_return'] ?? 0;
        } elseif ($investment['status'] === 'completed') {
            $completed_investments++;
            $total_actual_returns += $investment['actual_return'] ?? 0;
        }
    }
    
} catch(PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
    // Initialize default values
    $user = ['full_name' => 'User', 'account_balance' => 0, 'total_invested' => 0];
    $investments = [];
    $available_levels = [];
    $total_investments = 0;
    $active_investments = 0;
    $completed_investments = 0;
    $total_expected_returns = 0;
    $total_actual_returns = 0;
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
                    <a href="transactions.php" style="background: rgba(255, 255, 255, 0.1); color: #ffd700; padding: 1rem 2rem; border: 1px solid rgba(255, 215, 0, 0.3); border-radius: 10px; text-decoration: none; font-weight: 600; margin: 0 1rem;">
                        <i class="fas fa-history"></i> View Transactions
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
                            // Handle missing category key safely
                            $level_category = isset($level['category']) ? $level['category'] : 'Regular';
                            return $level_category === $category;
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
                                $daily_return = $profit / ($level['duration_days'] ?? 1);
                                $level_id = isset($level['id']) ? $level['id'] : 0;
                                $level_name = isset($level['level_name']) ? $level['level_name'] : 'Investment Package';
                                $min_investment = isset($level['min_investment']) ? $level['min_investment'] : 0;
                                $expected_return = isset($level['expected_return_percentage']) ? $level['expected_return_percentage'] : 0;
                                $duration_days = isset($level['duration_days']) ? $level['duration_days'] : 1;
                            ?>
                            <div style="background: rgba(255, 255, 255, 0.1); border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 10px; padding: 1.5rem; position: relative;">
                                <!-- Return Badge -->
                                <div style="position: absolute; top: -10px; right: 15px; background: <?php echo $colors['text']; ?>; color: white; padding: 0.5rem 1rem; border-radius: 20px; font-weight: bold; font-size: 0.9rem;">
                                    <?php echo $expected_return; ?>% Return
                                </div>
                                
                                <h5 style="color: #ffd700; margin-bottom: 1rem; font-size: 1.1rem;">
                                    <?php echo htmlspecialchars($level_name); ?>
                                </h5>
                                
                                <div style="color: rgba(255, 255, 255, 0.9); line-height: 1.6; margin-bottom: 1.5rem;">
                                    <p style="font-size: 1.3rem; font-weight: bold; color: #ffd700; margin-bottom: 0.5rem;">
                                        <i class="fas fa-coins"></i> Br<?php echo number_format($min_investment); ?>
                                    </p>
                                    <p><strong>Duration:</strong> <?php echo $duration_days; ?> days</p>
                                    <p><strong>Expected Profit:</strong> <span style="color: #228b22; font-weight: bold;">Br<?php echo number_format($profit); ?></span></p>
                                    <p><strong>Daily Return:</strong> <span style="color: #32cd32;">Br<?php echo number_format($daily_return); ?>/day</span></p>
                                    <p><strong>Total Return:</strong> <span style="color: #ffd700; font-weight: bold;">Br<?php echo number_format($min_investment + $profit); ?></span></p>
                                </div>
                                
                                <div style="text-align: center;">
                                    <a href="transactions.php?invest=<?php echo $level_id; ?>" style="background: linear-gradient(45deg, #228b22, #ffd700); color: #0a0e1a; padding: 0.75rem 1.5rem; border-radius: 8px; text-decoration: none; font-weight: 600; display: inline-block; width: 100%;">
                                        <i class="fas fa-rocket"></i> Invest Now
                                    </a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- Investments List -->
                <div style="background: rgba(255, 255, 255, 0.05); border-radius: 15px; padding: 2rem; border: 1px solid rgba(34, 139, 34, 0.2);">
                    <h3 style="color: #ffd700; margin-bottom: 2rem; text-align: center;">
                        <i class="fas fa-list"></i> Investment Portfolio
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
                                <?php
                                // Calculate progress and days remaining
                                $start_date = new DateTime($investment['start_date'] ?? date('Y-m-d'));
                                $end_date = new DateTime($investment['end_date'] ?? date('Y-m-d'));
                                $current_date = new DateTime();
                                
                                $total_days = $start_date->diff($end_date)->days;
                                $elapsed_days = $start_date->diff($current_date)->days;
                                $remaining_days = $current_date->diff($end_date)->days;
                                
                                if ($current_date > $end_date) {
                                    $remaining_days = 0;
                                    $progress = 100;
                                } else {
                                    $progress = min(100, ($elapsed_days / max($total_days, 1)) * 100);
                                }
                                
                                // Status colors
                                $status_colors = [
                                    'active' => '#228b22',
                                    'completed' => '#ffd700',
                                    'pending' => '#32cd32',
                                    'cancelled' => '#ff5252'
                                ];
                                
                                $investment_status = $investment['status'] ?? 'pending';
                                $status_color = $status_colors[$investment_status] ?? '#32cd32';
                                ?>
                                
                                <div style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(34, 139, 34, 0.2); border-radius: 15px; padding: 2rem; position: relative; overflow: hidden;">
                                    <!-- Status Badge -->
                                    <div style="position: absolute; top: 1rem; right: 1rem;">
                                        <span style="background: rgba(<?php echo hexdec(substr($status_color, 1, 2)); ?>, <?php echo hexdec(substr($status_color, 3, 2)); ?>, <?php echo hexdec(substr($status_color, 5, 2)); ?>, 0.2); color: <?php echo $status_color; ?>; padding: 0.5rem 1rem; border-radius: 20px; font-size: 0.875rem; font-weight: 600;">
                                            <?php echo ucfirst($investment_status); ?>
                                        </span>
                                    </div>
                                    
                                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem; align-items: center;">
                                        <!-- Investment Details -->
                                        <div>
                                            <h4 style="color: #ffd700; margin-bottom: 1rem; font-size: 1.3rem;">
                                                <i class="fas fa-layer-group"></i> <?php echo htmlspecialchars($investment['level_name'] ?? 'Investment Package'); ?>
                                            </h4>
                                            <div style="color: rgba(255, 255, 255, 0.9); line-height: 1.6;">
                                                <p><strong>Investment:</strong> Br<?php echo number_format($investment['amount'] ?? 0, 2); ?></p>
                                                <p><strong>Expected Return:</strong> Br<?php echo number_format($investment['expected_return'] ?? 0, 2); ?> (<?php echo ($investment['expected_return_percentage'] ?? 0); ?>%)</p>
                                                <p><strong>Total Expected:</strong> Br<?php echo number_format(($investment['amount'] ?? 0) + ($investment['expected_return'] ?? 0), 2); ?></p>
                                                <?php if (($investment['status'] ?? '') === 'completed'): ?>
                                                    <p><strong>Actual Return:</strong> Br<?php echo number_format($investment['actual_return'] ?? 0, 2); ?></p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <!-- Timeline -->
                                        <div>
                                            <div style="margin-bottom: 1rem;">
                                                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                                    <span style="color: rgba(255, 255, 255, 0.8); font-size: 0.9rem;">Progress</span>
                                                    <span style="color: #ffd700; font-weight: 600;"><?php echo round($progress); ?>%</span>
                                                </div>
                                                <div style="background: rgba(255, 255, 255, 0.1); height: 8px; border-radius: 4px; overflow: hidden;">
                                                    <div style="background: linear-gradient(90deg, #228b22, #ffd700); height: 100%; width: <?php echo $progress; ?>%; transition: width 0.3s ease;"></div>
                                                </div>
                                            </div>
                                            
                                            <div style="color: rgba(255, 255, 255, 0.8); font-size: 0.9rem;">
                                                <p><strong>Start Date:</strong> <?php echo date('M j, Y', strtotime($investment['start_date'] ?? date('Y-m-d'))); ?></p>
                                                <p><strong>End Date:</strong> <?php echo date('M j, Y', strtotime($investment['end_date'] ?? date('Y-m-d'))); ?></p>
                                                <?php if ($investment_status === 'active'): ?>
                                                    <p><strong>Days Remaining:</strong> 
                                                        <span style="color: <?php echo $remaining_days <= 3 ? '#ffd700' : '#228b22'; ?>;">
                                                            <?php echo $remaining_days; ?> days
                                                        </span>
                                                    </p>
                                                <?php elseif ($investment_status === 'completed'): ?>
                                                    <p><strong>Completed:</strong> <?php echo date('M j, Y', strtotime($investment['completed_date'] ?? date('Y-m-d'))); ?></p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <!-- Actions -->
                                        <div style="text-align: center;">
                                            <?php if ($investment_status === 'active' && $remaining_days <= 0): ?>
                                                <button style="background: linear-gradient(45deg, #228b22, #ffd700); color: #0a0e1a; padding: 0.75rem 1.5rem; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; margin-bottom: 0.5rem; width: 100%;">
                                                    <i class="fas fa-coins"></i> Claim Returns
                                                </button>
                                            <?php endif; ?>
                                            
                                            <div style="font-size: 0.8rem; color: rgba(255, 255, 255, 0.6);">
                                                ID: #<?php echo str_pad($investment['id'] ?? 0, 6, '0', STR_PAD_LEFT); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Performance Summary -->
                        <?php if ($completed_investments > 0): ?>
                            <div style="background: rgba(34, 139, 34, 0.1); border: 1px solid rgba(34, 139, 34, 0.3); border-radius: 15px; padding: 2rem; margin-top: 2rem;">
                                <h4 style="color: #228b22; margin-bottom: 1rem; text-align: center;">
                                    <i class="fas fa-trophy"></i> Performance Summary
                                </h4>
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; text-align: center;">
                                    <div>
                                        <div style="color: #ffd700; font-size: 1.5rem; font-weight: bold;">
                                            Br<?php echo number_format($total_actual_returns, 2); ?>
                                        </div>
                                        <div style="color: rgba(255, 255, 255, 0.8); font-size: 0.9rem;">Total Returns Earned</div>
                                    </div>
                                    <div>
                                        <div style="color: #228b22; font-size: 1.5rem; font-weight: bold;">
                                            <?php echo $completed_investments; ?>
                                        </div>
                                        <div style="color: rgba(255, 255, 255, 0.8); font-size: 0.9rem;">Successful Investments</div>
                                    </div>
                                    <div>
                                        <div style="color: #ffd700; font-size: 1.5rem; font-weight: bold;">
                                            <?php echo $total_actual_returns > 0 ? round(($total_actual_returns / $user['total_invested']) * 100, 1) : 0; ?>%
                                        </div>
                                        <div style="color: rgba(255, 255, 255, 0.8); font-size: 0.9rem;">Average Return Rate</div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
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