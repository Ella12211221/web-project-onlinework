<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Markets - Concordial Nexus</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .market-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(34, 139, 34, 0.3);
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        .market-card:hover {
            background: rgba(34, 139, 34, 0.1);
            border-color: rgba(34, 139, 34, 0.5);
            transform: translateY(-2px);
        }
        .price-positive { color: #228b22; }
        .price-negative { color: #ff5252; }
        .market-tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }
        .tab-button {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(34, 139, 34, 0.3);
            color: rgba(255, 255, 255, 0.8);
            padding: 1rem 2rem;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .tab-button.active {
            background: linear-gradient(45deg, #228b22, #32cd32);
            color: white;
            border-color: #228b22;
        }
        .market-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 1.5rem;
        }
    </style>
</head>
<body>
    <?php
    session_start();
    require_once '../includes/access-control.php';
    requireLogin();
    
    // Sample market data - in real implementation, this would come from API
    $markets = [
        'stocks' => [
            ['symbol' => 'AAPL', 'name' => 'Apple Inc.', 'price' => 175.43, 'change' => 2.15, 'change_percent' => 1.24],
            ['symbol' => 'GOOGL', 'name' => 'Alphabet Inc.', 'price' => 2847.63, 'change' => -15.42, 'change_percent' => -0.54],
            ['symbol' => 'MSFT', 'name' => 'Microsoft Corp.', 'price' => 378.85, 'change' => 4.67, 'change_percent' => 1.25],
            ['symbol' => 'TSLA', 'name' => 'Tesla Inc.', 'price' => 248.42, 'change' => -8.23, 'change_percent' => -3.21],
            ['symbol' => 'AMZN', 'name' => 'Amazon.com Inc.', 'price' => 3342.88, 'change' => 12.45, 'change_percent' => 0.37],
            ['symbol' => 'META', 'name' => 'Meta Platforms', 'price' => 331.26, 'change' => 7.89, 'change_percent' => 2.44]
        ],
        'crypto' => [
            ['symbol' => 'BTC', 'name' => 'Bitcoin', 'price' => 43250.00, 'change' => 1250.00, 'change_percent' => 2.98],
            ['symbol' => 'ETH', 'name' => 'Ethereum', 'price' => 2650.75, 'change' => -85.25, 'change_percent' => -3.11],
            ['symbol' => 'BNB', 'name' => 'Binance Coin', 'price' => 315.42, 'change' => 8.67, 'change_percent' => 2.83],
            ['symbol' => 'ADA', 'name' => 'Cardano', 'price' => 0.485, 'change' => 0.023, 'change_percent' => 4.98],
            ['symbol' => 'SOL', 'name' => 'Solana', 'price' => 98.76, 'change' => -3.24, 'change_percent' => -3.18],
            ['symbol' => 'DOT', 'name' => 'Polkadot', 'price' => 7.23, 'change' => 0.45, 'change_percent' => 6.64]
        ],
        'forex' => [
            ['symbol' => 'EUR/USD', 'name' => 'Euro / US Dollar', 'price' => 1.0875, 'change' => 0.0023, 'change_percent' => 0.21],
            ['symbol' => 'GBP/USD', 'name' => 'British Pound / US Dollar', 'price' => 1.2654, 'change' => -0.0087, 'change_percent' => -0.68],
            ['symbol' => 'USD/JPY', 'name' => 'US Dollar / Japanese Yen', 'price' => 149.85, 'change' => 0.75, 'change_percent' => 0.50],
            ['symbol' => 'USD/CHF', 'name' => 'US Dollar / Swiss Franc', 'price' => 0.8923, 'change' => 0.0034, 'change_percent' => 0.38],
            ['symbol' => 'AUD/USD', 'name' => 'Australian Dollar / US Dollar', 'price' => 0.6587, 'change' => -0.0045, 'change_percent' => -0.68],
            ['symbol' => 'USD/CAD', 'name' => 'US Dollar / Canadian Dollar', 'price' => 1.3542, 'change' => 0.0067, 'change_percent' => 0.50]
        ],
        'commodities' => [
            ['symbol' => 'GOLD', 'name' => 'Gold Spot', 'price' => 2045.50, 'change' => 15.75, 'change_percent' => 0.78],
            ['symbol' => 'SILVER', 'name' => 'Silver Spot', 'price' => 24.87, 'change' => -0.43, 'change_percent' => -1.70],
            ['symbol' => 'OIL', 'name' => 'Crude Oil WTI', 'price' => 78.45, 'change' => 2.34, 'change_percent' => 3.08],
            ['symbol' => 'NATGAS', 'name' => 'Natural Gas', 'price' => 2.87, 'change' => -0.12, 'change_percent' => -4.01],
            ['symbol' => 'COPPER', 'name' => 'Copper', 'price' => 3.85, 'change' => 0.08, 'change_percent' => 2.12],
            ['symbol' => 'WHEAT', 'name' => 'Wheat', 'price' => 6.42, 'change' => -0.15, 'change_percent' => -2.28]
        ]
    ];
    
    $active_tab = $_GET['tab'] ?? 'stocks';
    ?>
    
    <header class="header">
        <nav class="navbar">
            <div class="nav-container">
                <div class="nav-logo">
                    <a href="../index.php"><i class="fas fa-chart-line"></i> Concordial Nexus</a>
                </div>
                <?php renderNavigation('markets'); ?>
            </div>
        </nav>
    </header>

    <main style="margin-top: 100px; padding: 2rem;">
        <div class="container">
            <div style="background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(20px); border-radius: 20px; padding: 3rem; border: 1px solid rgba(34, 139, 34, 0.2);">
                
                <div style="text-align: center; margin-bottom: 3rem;">
                    <h1 style="color: #ffd700; margin-bottom: 1rem;">
                        <i class="fas fa-chart-bar"></i> Live Markets
                    </h1>
                    <p style="color: rgba(255, 255, 255, 0.8);">
                        Real-time market data and trading opportunities
                    </p>
                </div>

                <!-- Market Tabs -->
                <div class="market-tabs">
                    <button class="tab-button <?php echo $active_tab === 'stocks' ? 'active' : ''; ?>" onclick="switchTab('stocks')">
                        <i class="fas fa-building"></i> Stocks
                    </button>
                    <button class="tab-button <?php echo $active_tab === 'crypto' ? 'active' : ''; ?>" onclick="switchTab('crypto')">
                        <i class="fab fa-bitcoin"></i> Cryptocurrency
                    </button>
                    <button class="tab-button <?php echo $active_tab === 'forex' ? 'active' : ''; ?>" onclick="switchTab('forex')">
                        <i class="fas fa-exchange-alt"></i> Forex
                    </button>
                    <button class="tab-button <?php echo $active_tab === 'commodities' ? 'active' : ''; ?>" onclick="switchTab('commodities')">
                        <i class="fas fa-coins"></i> Commodities
                    </button>
                </div>

                <!-- Market Overview Stats -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 3rem;">
                    <div style="background: rgba(34, 139, 34, 0.1); border: 1px solid rgba(34, 139, 34, 0.3); border-radius: 15px; padding: 1.5rem; text-align: center;">
                        <h4 style="color: #228b22; margin-bottom: 0.5rem;">
                            <i class="fas fa-arrow-up"></i> Gainers
                        </h4>
                        <div style="font-size: 1.5rem; color: #ffd700; font-weight: bold;">
                            <?php 
                            $gainers = 0;
                            foreach($markets[$active_tab] as $item) {
                                if($item['change'] > 0) $gainers++;
                            }
                            echo $gainers;
                            ?>
                        </div>
                    </div>
                    
                    <div style="background: rgba(255, 82, 82, 0.1); border: 1px solid rgba(255, 82, 82, 0.3); border-radius: 15px; padding: 1.5rem; text-align: center;">
                        <h4 style="color: #ff5252; margin-bottom: 0.5rem;">
                            <i class="fas fa-arrow-down"></i> Losers
                        </h4>
                        <div style="font-size: 1.5rem; color: #ffd700; font-weight: bold;">
                            <?php 
                            $losers = 0;
                            foreach($markets[$active_tab] as $item) {
                                if($item['change'] < 0) $losers++;
                            }
                            echo $losers;
                            ?>
                        </div>
                    </div>
                    
                    <div style="background: rgba(255, 215, 0, 0.1); border: 1px solid rgba(255, 215, 0, 0.3); border-radius: 15px; padding: 1.5rem; text-align: center;">
                        <h4 style="color: #ffd700; margin-bottom: 0.5rem;">
                            <i class="fas fa-chart-line"></i> Total Assets
                        </h4>
                        <div style="font-size: 1.5rem; color: #228b22; font-weight: bold;">
                            <?php echo count($markets[$active_tab]); ?>
                        </div>
                    </div>
                    
                    <div style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(34, 139, 34, 0.3); border-radius: 15px; padding: 1.5rem; text-align: center;">
                        <h4 style="color: #ffd700; margin-bottom: 0.5rem;">
                            <i class="fas fa-clock"></i> Last Update
                        </h4>
                        <div style="font-size: 1rem; color: #228b22; font-weight: bold;">
                            <?php echo date('H:i:s'); ?>
                        </div>
                    </div>
                </div>

                <!-- Market Data Grid -->
                <div class="market-grid">
                    <?php foreach($markets[$active_tab] as $asset): ?>
                        <div class="market-card">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                                <div>
                                    <h3 style="color: #ffd700; margin: 0; font-size: 1.2rem;">
                                        <?php echo $asset['symbol']; ?>
                                    </h3>
                                    <p style="color: rgba(255, 255, 255, 0.7); margin: 0.5rem 0 0 0; font-size: 0.9rem;">
                                        <?php echo $asset['name']; ?>
                                    </p>
                                </div>
                                <div style="text-align: right;">
                                    <div style="font-size: 1.4rem; font-weight: bold; color: white; margin-bottom: 0.25rem;">
                                        $<?php echo number_format($asset['price'], 2); ?>
                                    </div>
                                    <div class="<?php echo $asset['change'] >= 0 ? 'price-positive' : 'price-negative'; ?>" style="font-size: 0.9rem;">
                                        <i class="fas fa-<?php echo $asset['change'] >= 0 ? 'arrow-up' : 'arrow-down'; ?>"></i>
                                        <?php echo ($asset['change'] >= 0 ? '+' : '') . number_format($asset['change'], 2); ?>
                                        (<?php echo ($asset['change_percent'] >= 0 ? '+' : '') . number_format($asset['change_percent'], 2); ?>%)
                                    </div>
                                </div>
                            </div>
                            
                            <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                                <button onclick="openTradeModal('<?php echo $asset['symbol']; ?>', 'buy', <?php echo $asset['price']; ?>)" 
                                        style="flex: 1; background: linear-gradient(45deg, #228b22, #32cd32); color: white; padding: 0.75rem; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">
                                    <i class="fas fa-arrow-up"></i> Buy
                                </button>
                                <button onclick="openTradeModal('<?php echo $asset['symbol']; ?>', 'sell', <?php echo $asset['price']; ?>)" 
                                        style="flex: 1; background: linear-gradient(45deg, #ff5252, #ff7979); color: white; padding: 0.75rem; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">
                                    <i class="fas fa-arrow-down"></i> Sell
                                </button>
                                <button onclick="viewChart('<?php echo $asset['symbol']; ?>')" 
                                        style="background: rgba(255, 215, 0, 0.2); color: #ffd700; padding: 0.75rem 1rem; border: 1px solid rgba(255, 215, 0, 0.3); border-radius: 8px; cursor: pointer;">
                                    <i class="fas fa-chart-line"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Quick Actions -->
                <div style="margin-top: 3rem; text-align: center;">
                    <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                        <a href="trading.php" style="background: linear-gradient(45deg, #228b22, #32cd32); color: white; padding: 1rem 2rem; border-radius: 8px; text-decoration: none; font-weight: 600;">
                            <i class="fas fa-exchange-alt"></i> Start Trading
                        </a>
                        <a href="analysis.php" style="background: rgba(255, 215, 0, 0.2); color: #ffd700; padding: 1rem 2rem; border: 1px solid rgba(255, 215, 0, 0.3); border-radius: 8px; text-decoration: none; font-weight: 600;">
                            <i class="fas fa-chart-line"></i> Market Analysis
                        </a>
                        <a href="portfolio.php" style="background: rgba(255, 255, 255, 0.1); color: white; padding: 1rem 2rem; border: 1px solid rgba(34, 139, 34, 0.3); border-radius: 8px; text-decoration: none; font-weight: 600;">
                            <i class="fas fa-briefcase"></i> View Portfolio
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Trade Modal (placeholder) -->
    <div id="tradeModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.8); z-index: 1000; align-items: center; justify-content: center;">
        <div style="background: rgba(10, 14, 26, 0.95); border: 1px solid rgba(34, 139, 34, 0.3); border-radius: 15px; padding: 2rem; max-width: 400px; width: 90%;">
            <h3 id="modalTitle" style="color: #ffd700; margin-bottom: 1rem;"></h3>
            <p style="color: rgba(255, 255, 255, 0.8); margin-bottom: 2rem;">
                Trading functionality will be available in the Trading section.
            </p>
            <div style="display: flex; gap: 1rem;">
                <button onclick="closeTradeModal()" style="flex: 1; background: rgba(255, 255, 255, 0.1); color: white; padding: 1rem; border: 1px solid rgba(34, 139, 34, 0.3); border-radius: 8px; cursor: pointer;">
                    Cancel
                </button>
                <a href="trading.php" style="flex: 1; background: linear-gradient(45deg, #228b22, #32cd32); color: white; padding: 1rem; border: none; border-radius: 8px; text-decoration: none; text-align: center;">
                    Go to Trading
                </a>
            </div>
        </div>
    </div>

    <footer class="footer">
        <div class="container">
            <div class="footer-bottom">
                <p>&copy; 2026 Concordial Nexus. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        function switchTab(tab) {
            window.location.href = '?tab=' + tab;
        }

        function openTradeModal(symbol, type, price) {
            document.getElementById('modalTitle').textContent = type.toUpperCase() + ' ' + symbol + ' at $' + price.toFixed(2);
            document.getElementById('tradeModal').style.display = 'flex';
        }

        function closeTradeModal() {
            document.getElementById('tradeModal').style.display = 'none';
        }

        function viewChart(symbol) {
            alert('Chart for ' + symbol + ' - Advanced charting will be available in the Analysis section.');
        }

        // Auto-refresh market data every 30 seconds
        setInterval(function() {
            // In real implementation, this would fetch live data via AJAX
            console.log('Market data refresh...');
        }, 30000);
    </script>
</body>
</html>