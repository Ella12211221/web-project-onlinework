<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portfolio - Concordial Nexus</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .portfolio-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(34, 139, 34, 0.3);
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        .portfolio-card:hover {
            background: rgba(34, 139, 34, 0.1);
            border-color: rgba(34, 139, 34, 0.5);
            transform: translateY(-2px);
        }
        .profit-positive { color: #228b22; }
        .profit-negative { color: #ff5252; }
        .allocation-bar {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            height: 8px;
            overflow: hidden;
            margin: 0.5rem 0;
        }
        .allocation-fill {
            height: 100%;
            background: linear-gradient(45deg, #228b22, #32cd32);
            transition: width 0.3s ease;
        }
    </style>
</head>
<body>
    <?php
    session_start();
    require_once '../includes/access-control.php';
    requireLogin();
    
    try {
        $pdo = new PDO("mysql:host=localhost;dbname=concordial_nexus;charset=utf8mb4", "root", "");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Get user info
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        // Sample portfolio data - in real implementation, this would come from database
        $portfolio_assets = [
            [
                'symbol' => 'AAPL',
                'name' => 'Apple Inc.',
                'quantity' => 10,
                'avg_price' => 170.25,
                'current_price' => 175.43,
                'total_invested' => 1702.50,
                'current_value' => 1754.30,
                'profit_loss' => 51.80,
                'profit_percent' => 3.04,
                'allocation' => 35.2
            ],
            [
                'symbol' => 'BTC',
                'name' => 'Bitcoin',
                'quantity' => 0.05,
                'avg_price' => 42000.00,
                'current_price' => 43250.00,
                'total_invested' => 2100.00,
                'current_value' => 2162.50,
                'profit_loss' => 62.50,
                'profit_percent' => 2.98,
                'allocation' => 43.4
            ],
            [
                'symbol' => 'GOLD',
                'name' => 'Gold Spot',
                'quantity' => 0.5,
                'avg_price' => 2030.00,
                'current_price' => 2045.50,
                'total_invested' => 1015.00,
                'current_value' => 1022.75,
                'profit_loss' => 7.75,
                'profit_percent' => 0.76,
                'allocation' => 20.5
            ],
            [
                'symbol' => 'EUR/USD',
                'name' => 'Euro/Dollar',
                'quantity' => 50,
                'avg_price' => 1.0850,
                'current_price' => 1.0875,
                'total_invested' => 54.25,
                'current_value' => 54.38,
                'profit_loss' => 0.13,
                'profit_percent' => 0.24,
                'allocation' => 1.1
            ]
        ];
        
        // Calculate totals
        $total_invested = array_sum(array_column($portfolio_assets, 'total_invested'));
        $total_current_value = array_sum(array_column($portfolio_assets, 'current_value'));
        $total_profit_loss = $total_current_value - $total_invested;
        $total_profit_percent = ($total_invested > 0) ? ($total_profit_loss / $total_invested) * 100 : 0;
        
    } catch(PDOException $e) {
        $error_message = "Database error: " . $e->getMessage();
    }
    ?>
    
    <header class="header">
        <nav class="navbar">
            <div class="nav-container">
                <div class="nav-logo">
                    <a href="../index.php"><i class="fas fa-chart-line"></i> Concordial Nexus</a>
                </div>
                <?php renderNavigation('portfolio'); ?>
            </div>
        </nav>
    </header>

    <main style="margin-top: 100px; padding: 2rem;">
        <div class="container">
            <div style="background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(20px); border-radius: 20px; padding: 3rem; border: 1px solid rgba(34, 139, 34, 0.2);">
                
                <div style="text-align: center; margin-bottom: 3rem;">
                    <h1 style="color: #ffd700; margin-bottom: 1rem;">
                        <i class="fas fa-briefcase"></i> Portfolio Overview
                    </h1>
                    <p style="color: rgba(255, 255, 255, 0.8);">
                        Track your investments and performance in Ethiopian Birr
                    </p>
                </div>

                <!-- Portfolio Summary -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem; margin-bottom: 3rem;">
                    <div style="background: linear-gradient(45deg, rgba(34, 139, 34, 0.2), rgba(255, 215, 0, 0.1)); border: 1px solid rgba(34, 139, 34, 0.3); border-radius: 15px; padding: 2rem; text-align: center;">
                        <h3 style="color: #228b22; margin-bottom: 1rem;">
                            <i class="fas fa-wallet"></i> Total Portfolio Value
                        </h3>
                        <div style="font-size: 2.5rem; color: #ffd700; font-weight: bold; margin-bottom: 0.5rem;">
                            Br<?php echo number_format($total_current_value, 2); ?>
                        </div>
                        <p style="color: rgba(255, 255, 255, 0.8);">Current market value</p>
                    </div>
                    
                    <div style="background: rgba(255, 215, 0, 0.1); border: 1px solid rgba(255, 215, 0, 0.3); border-radius: 15px; padding: 2rem; text-align: center;">
                        <h3 style="color: #ffd700; margin-bottom: 1rem;">
                            <i class="fas fa-chart-line"></i> Total Invested
                        </h3>
                        <div style="font-size: 2rem; color: #228b22; font-weight: bold; margin-bottom: 0.5rem;">
                            Br<?php echo number_format($total_invested, 2); ?>
                        </div>
                        <p style="color: rgba(255, 255, 255, 0.8);">Initial investment</p>
                    </div>
                    
                    <div style="background: rgba(<?php echo $total_profit_loss >= 0 ? '34, 139, 34' : '255, 82, 82'; ?>, 0.1); border: 1px solid rgba(<?php echo $total_profit_loss >= 0 ? '34, 139, 34' : '255, 82, 82'; ?>, 0.3); border-radius: 15px; padding: 2rem; text-align: center;">
                        <h3 style="color: <?php echo $total_profit_loss >= 0 ? '#228b22' : '#ff5252'; ?>; margin-bottom: 1rem;">
                            <i class="fas fa-<?php echo $total_profit_loss >= 0 ? 'arrow-up' : 'arrow-down'; ?>"></i> Total P&L
                        </h3>
                        <div style="font-size: 2rem; color: <?php echo $total_profit_loss >= 0 ? '#228b22' : '#ff5252'; ?>; font-weight: bold; margin-bottom: 0.5rem;">
                            <?php echo ($total_profit_loss >= 0 ? '+' : '') . 'Br' . number_format($total_profit_loss, 2); ?>
                        </div>
                        <p style="color: rgba(255, 255, 255, 0.8);">
                            <?php echo ($total_profit_percent >= 0 ? '+' : '') . number_format($total_profit_percent, 2); ?>%
                        </p>
                    </div>
                    
                    <div style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(34, 139, 34, 0.3); border-radius: 15px; padding: 2rem; text-align: center;">
                        <h3 style="color: #ffd700; margin-bottom: 1rem;">
                            <i class="fas fa-layer-group"></i> Total Assets
                        </h3>
                        <div style="font-size: 2rem; color: #228b22; font-weight: bold; margin-bottom: 0.5rem;">
                            <?php echo count($portfolio_assets); ?>
                        </div>
                        <p style="color: rgba(255, 255, 255, 0.8);">Different positions</p>
                    </div>
                </div>

                <!-- Portfolio Allocation Chart -->
                <div style="background: rgba(255, 255, 255, 0.05); border-radius: 15px; padding: 2rem; margin-bottom: 3rem; border: 1px solid rgba(34, 139, 34, 0.2);">
                    <h3 style="color: #ffd700; margin-bottom: 2rem; text-align: center;">
                        <i class="fas fa-pie-chart"></i> Portfolio Allocation
                    </h3>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                        <?php foreach($portfolio_assets as $asset): ?>
                            <div style="text-align: center;">
                                <div style="color: white; font-weight: bold; margin-bottom: 0.5rem;">
                                    <?php echo $asset['symbol']; ?>
                                </div>
                                <div style="color: #ffd700; font-size: 1.5rem; font-weight: bold; margin-bottom: 0.5rem;">
                                    <?php echo number_format($asset['allocation'], 1); ?>%
                                </div>
                                <div class="allocation-bar">
                                    <div class="allocation-fill" style="width: <?php echo $asset['allocation']; ?>%;"></div>
                                </div>
                                <div style="color: rgba(255, 255, 255, 0.7); font-size: 0.9rem;">
                                    Br<?php echo number_format($asset['current_value'], 2); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Holdings Details -->
                <div style="background: rgba(255, 255, 255, 0.05); border-radius: 15px; padding: 2rem; border: 1px solid rgba(34, 139, 34, 0.2);">
                    <h3 style="color: #ffd700; margin-bottom: 2rem; text-align: center;">
                        <i class="fas fa-list"></i> Holdings Details
                    </h3>
                    
                    <?php if (empty($portfolio_assets)): ?>
                        <div style="text-align: center; color: rgba(255, 255, 255, 0.6); padding: 3rem;">
                            <i class="fas fa-briefcase" style="font-size: 4rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                            <h4>No Holdings Yet</h4>
                            <p>Start trading to build your portfolio.</p>
                            <a href="trading.php" style="background: linear-gradient(45deg, #228b22, #32cd32); color: white; padding: 1rem 2rem; border-radius: 8px; text-decoration: none; margin-top: 1rem; display: inline-block;">
                                <i class="fas fa-plus"></i> Start Trading
                            </a>
                        </div>
                    <?php else: ?>
                        <div style="overflow-x: auto;">
                            <table style="width: 100%; border-collapse: collapse;">
                                <thead>
                                    <tr style="border-bottom: 2px solid rgba(34, 139, 34, 0.3);">
                                        <th style="padding: 1rem; text-align: left; color: #ffd700;">Asset</th>
                                        <th style="padding: 1rem; text-align: right; color: #ffd700;">Quantity</th>
                                        <th style="padding: 1rem; text-align: right; color: #ffd700;">Avg Price</th>
                                        <th style="padding: 1rem; text-align: right; color: #ffd700;">Current Price</th>
                                        <th style="padding: 1rem; text-align: right; color: #ffd700;">Market Value</th>
                                        <th style="padding: 1rem; text-align: right; color: #ffd700;">P&L</th>
                                        <th style="padding: 1rem; text-align: center; color: #ffd700;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($portfolio_assets as $asset): ?>
                                        <tr style="border-bottom: 1px solid rgba(34, 139, 34, 0.1);">
                                            <td style="padding: 1rem;">
                                                <div>
                                                    <div style="color: #ffd700; font-weight: bold; font-size: 1.1rem;">
                                                        <?php echo $asset['symbol']; ?>
                                                    </div>
                                                    <div style="color: rgba(255, 255, 255, 0.7); font-size: 0.9rem;">
                                                        <?php echo $asset['name']; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td style="padding: 1rem; text-align: right; color: white; font-weight: 600;">
                                                <?php echo number_format($asset['quantity'], 4); ?>
                                            </td>
                                            <td style="padding: 1rem; text-align: right; color: rgba(255, 255, 255, 0.8);">
                                                $<?php echo number_format($asset['avg_price'], 2); ?>
                                            </td>
                                            <td style="padding: 1rem; text-align: right; color: white; font-weight: 600;">
                                                $<?php echo number_format($asset['current_price'], 2); ?>
                                            </td>
                                            <td style="padding: 1rem; text-align: right; color: #228b22; font-weight: 600; font-size: 1.1rem;">
                                                Br<?php echo number_format($asset['current_value'], 2); ?>
                                            </td>
                                            <td style="padding: 1rem; text-align: right;">
                                                <div class="<?php echo $asset['profit_loss'] >= 0 ? 'profit-positive' : 'profit-negative'; ?>" style="font-weight: 600;">
                                                    <?php echo ($asset['profit_loss'] >= 0 ? '+' : '') . 'Br' . number_format($asset['profit_loss'], 2); ?>
                                                </div>
                                                <div class="<?php echo $asset['profit_percent'] >= 0 ? 'profit-positive' : 'profit-negative'; ?>" style="font-size: 0.9rem;">
                                                    <?php echo ($asset['profit_percent'] >= 0 ? '+' : '') . number_format($asset['profit_percent'], 2); ?>%
                                                </div>
                                            </td>
                                            <td style="padding: 1rem; text-align: center;">
                                                <div style="display: flex; gap: 0.5rem; justify-content: center;">
                                                    <button onclick="quickTrade('<?php echo $asset['symbol']; ?>', 'buy')" 
                                                            style="background: rgba(34, 139, 34, 0.2); color: #228b22; padding: 0.5rem; border: 1px solid rgba(34, 139, 34, 0.3); border-radius: 4px; cursor: pointer;">
                                                        <i class="fas fa-plus"></i>
                                                    </button>
                                                    <button onclick="quickTrade('<?php echo $asset['symbol']; ?>', 'sell')" 
                                                            style="background: rgba(255, 82, 82, 0.2); color: #ff5252; padding: 0.5rem; border: 1px solid rgba(255, 82, 82, 0.3); border-radius: 4px; cursor: pointer;">
                                                        <i class="fas fa-minus"></i>
                                                    </button>
                                                    <button onclick="viewChart('<?php echo $asset['symbol']; ?>')" 
                                                            style="background: rgba(255, 215, 0, 0.2); color: #ffd700; padding: 0.5rem; border: 1px solid rgba(255, 215, 0, 0.3); border-radius: 4px; cursor: pointer;">
                                                        <i class="fas fa-chart-line"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Portfolio Actions -->
                        <div style="display: flex; gap: 1rem; justify-content: center; margin-top: 2rem; flex-wrap: wrap;">
                            <a href="trading.php" style="background: linear-gradient(45deg, #228b22, #32cd32); color: white; padding: 1rem 2rem; border-radius: 8px; text-decoration: none; font-weight: 600;">
                                <i class="fas fa-plus"></i> Add Position
                            </a>
                            <a href="analysis.php" style="background: rgba(255, 215, 0, 0.2); color: #ffd700; padding: 1rem 2rem; border: 1px solid rgba(255, 215, 0, 0.3); border-radius: 8px; text-decoration: none; font-weight: 600;">
                                <i class="fas fa-chart-line"></i> Portfolio Analysis
                            </a>
                            <a href="orders.php" style="background: rgba(255, 255, 255, 0.1); color: white; padding: 1rem 2rem; border: 1px solid rgba(34, 139, 34, 0.3); border-radius: 8px; text-decoration: none; font-weight: 600;">
                                <i class="fas fa-list-alt"></i> View Orders
                            </a>
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

    <script>
        function quickTrade(symbol, type) {
            const action = type === 'buy' ? 'Buy more' : 'Sell position';
            if (confirm(`${action} ${symbol}? This will redirect you to the trading page.`)) {
                window.location.href = `trading.php?symbol=${symbol}&type=${type}`;
            }
        }

        function viewChart(symbol) {
            window.location.href = `analysis.php?symbol=${symbol}`;
        }

        // Auto-refresh portfolio data every 60 seconds
        setInterval(function() {
            // In real implementation, this would fetch live data via AJAX
            console.log('Portfolio data refresh...');
        }, 60000);
    </script>
</body>
</html>