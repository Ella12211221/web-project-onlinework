<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trading - Concordial Nexus</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .trading-panel {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(34, 139, 34, 0.3);
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        .order-form {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
        }
        .buy-button {
            background: linear-gradient(45deg, #228b22, #32cd32);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
        }
        .sell-button {
            background: linear-gradient(45deg, #ff5252, #ff7979);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
        }
        .order-type-tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }
        .tab-btn {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(34, 139, 34, 0.3);
            color: rgba(255, 255, 255, 0.8);
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .tab-btn.active {
            background: linear-gradient(45deg, #228b22, #32cd32);
            color: white;
            border-color: #228b22;
        }
        .price-input {
            width: 100%;
            padding: 1rem;
            border: 1px solid rgba(34, 139, 34, 0.3);
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            font-size: 1rem;
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
        
        // Handle trade execution
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['execute_trade'])) {
            $symbol = $_POST['symbol'];
            $trade_type = $_POST['trade_type']; // buy/sell
            $order_type = $_POST['order_type']; // market/limit/stop
            $quantity = floatval($_POST['quantity']);
            $price = floatval($_POST['price']);
            $payment_method = $_POST['payment_method'];
            
            $total_amount = $quantity * $price;
            
            if ($trade_type === 'buy' && $total_amount <= $user['account_balance']) {
                // Execute buy order
                $reference = 'TRD' . date('Ymd') . rand(1000, 9999);
                
                // Insert transaction
                $trans_stmt = $pdo->prepare("INSERT INTO transactions (user_id, transaction_type, amount, payment_method, description, reference_number, status) VALUES (?, 'investment', ?, ?, ?, ?, 'completed')");
                $trans_stmt->execute([$_SESSION['user_id'], $total_amount, $payment_method, "Buy $quantity $symbol at $price", $reference]);
                
                // Update user balance
                $update_stmt = $pdo->prepare("UPDATE users SET account_balance = account_balance - ?, total_invested = total_invested + ? WHERE id = ?");
                $update_stmt->execute([$total_amount, $total_amount, $_SESSION['user_id']]);
                
                $success_message = "Successfully bought $quantity $symbol for Br" . number_format($total_amount, 2);
                
                // Refresh user data
                $stmt->execute([$_SESSION['user_id']]);
                $user = $stmt->fetch();
            } else {
                $error_message = "Insufficient balance or invalid trade parameters!";
            }
        }
        
        // Sample watchlist
        $watchlist = [
            ['symbol' => 'AAPL', 'name' => 'Apple Inc.', 'price' => 175.43, 'change' => 2.15],
            ['symbol' => 'BTC', 'name' => 'Bitcoin', 'price' => 43250.00, 'change' => 1250.00],
            ['symbol' => 'EUR/USD', 'name' => 'Euro/Dollar', 'price' => 1.0875, 'change' => 0.0023],
            ['symbol' => 'GOLD', 'name' => 'Gold Spot', 'price' => 2045.50, 'change' => 15.75]
        ];
        
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
                <?php 
                // Navigation removed - use dashboard menu instead
                ?>
            </div>
        </nav>
    </header>

    <main style="margin-top: 100px; padding: 2rem;">
        <div class="container">
            <div style="background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(20px); border-radius: 20px; padding: 3rem; border: 1px solid rgba(34, 139, 34, 0.2);">
                
                <div style="text-align: center; margin-bottom: 3rem;">
                    <h1 style="color: #ffd700; margin-bottom: 1rem;">
                        <i class="fas fa-exchange-alt"></i> Trading Platform
                    </h1>
                    <p style="color: rgba(255, 255, 255, 0.8);">
                        Execute trades with Ethiopian Birr across global markets
                    </p>
                </div>

                <?php if (isset($success_message)): ?>
                    <div style="background: rgba(34, 139, 34, 0.2); color: #228b22; padding: 1rem; border-radius: 8px; margin-bottom: 2rem; text-align: center; border: 1px solid rgba(34, 139, 34, 0.3);">
                        <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($error_message)): ?>
                    <div style="background: rgba(255, 82, 82, 0.2); color: #ff5252; padding: 1rem; border-radius: 8px; margin-bottom: 2rem; text-align: center; border: 1px solid rgba(255, 82, 82, 0.3);">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>

                <!-- Account Summary -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 3rem;">
                    <div style="background: rgba(34, 139, 34, 0.1); border: 1px solid rgba(34, 139, 34, 0.3); border-radius: 15px; padding: 1.5rem; text-align: center;">
                        <h4 style="color: #228b22; margin-bottom: 0.5rem;">Available Balance</h4>
                        <div style="font-size: 1.5rem; color: #ffd700; font-weight: bold;">
                            Br<?php echo number_format($user['account_balance'], 2); ?>
                        </div>
                    </div>
                    
                    <div style="background: rgba(255, 215, 0, 0.1); border: 1px solid rgba(255, 215, 0, 0.3); border-radius: 15px; padding: 1.5rem; text-align: center;">
                        <h4 style="color: #ffd700; margin-bottom: 0.5rem;">Total Invested</h4>
                        <div style="font-size: 1.5rem; color: #228b22; font-weight: bold;">
                            Br<?php echo number_format($user['total_invested'], 2); ?>
                        </div>
                    </div>
                    
                    <div style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(34, 139, 34, 0.3); border-radius: 15px; padding: 1.5rem; text-align: center;">
                        <h4 style="color: #ffd700; margin-bottom: 0.5rem;">Total Profit</h4>
                        <div style="font-size: 1.5rem; color: #228b22; font-weight: bold;">
                            Br<?php echo number_format($user['total_profit'], 2); ?>
                        </div>
                    </div>
                    
                    <div style="background: rgba(34, 139, 34, 0.1); border: 1px solid rgba(34, 139, 34, 0.3); border-radius: 15px; padding: 1.5rem; text-align: center;">
                        <h4 style="color: #228b22; margin-bottom: 0.5rem;">Trading Level</h4>
                        <div style="font-size: 1.2rem; color: #ffd700; font-weight: bold;">
                            Level <?php echo $user['trading_level']; ?>
                        </div>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 3rem;">
                    
                    <!-- Trading Panel -->
                    <div class="trading-panel">
                        <h3 style="color: #ffd700; margin-bottom: 2rem; text-align: center;">
                            <i class="fas fa-chart-line"></i> Place Order
                        </h3>
                        
                        <form method="POST" id="tradeForm">
                            <!-- Order Type Selection -->
                            <div class="order-type-tabs">
                                <button type="button" class="tab-btn active" onclick="setOrderType('market')" id="market-tab">
                                    Market Order
                                </button>
                                <button type="button" class="tab-btn" onclick="setOrderType('limit')" id="limit-tab">
                                    Limit Order
                                </button>
                                <button type="button" class="tab-btn" onclick="setOrderType('stop')" id="stop-tab">
                                    Stop Order
                                </button>
                            </div>
                            
                            <input type="hidden" name="order_type" id="order_type" value="market">
                            
                            <!-- Symbol Selection -->
                            <div style="margin-bottom: 1.5rem;">
                                <label style="color: rgba(255, 255, 255, 0.9); display: block; margin-bottom: 0.5rem;">
                                    <i class="fas fa-search"></i> Asset Symbol
                                </label>
                                <select name="symbol" required class="price-input">
                                    <option value="">Select Asset</option>
                                    <optgroup label="📈 Stocks">
                                        <option value="AAPL">AAPL - Apple Inc.</option>
                                        <option value="GOOGL">GOOGL - Alphabet Inc.</option>
                                        <option value="MSFT">MSFT - Microsoft Corp.</option>
                                        <option value="TSLA">TSLA - Tesla Inc.</option>
                                    </optgroup>
                                    <optgroup label="₿ Cryptocurrency">
                                        <option value="BTC">BTC - Bitcoin</option>
                                        <option value="ETH">ETH - Ethereum</option>
                                        <option value="BNB">BNB - Binance Coin</option>
                                    </optgroup>
                                    <optgroup label="💱 Forex">
                                        <option value="EUR/USD">EUR/USD</option>
                                        <option value="GBP/USD">GBP/USD</option>
                                        <option value="USD/JPY">USD/JPY</option>
                                    </optgroup>
                                    <optgroup label="🥇 Commodities">
                                        <option value="GOLD">GOLD - Gold Spot</option>
                                        <option value="SILVER">SILVER - Silver Spot</option>
                                        <option value="OIL">OIL - Crude Oil</option>
                                    </optgroup>
                                </select>
                            </div>
                            
                            <!-- Trade Type -->
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                                <button type="button" class="buy-button" onclick="setTradeType('buy')" id="buy-btn">
                                    <i class="fas fa-arrow-up"></i> BUY
                                </button>
                                <button type="button" class="sell-button" onclick="setTradeType('sell')" id="sell-btn" style="opacity: 0.6;">
                                    <i class="fas fa-arrow-down"></i> SELL
                                </button>
                            </div>
                            
                            <input type="hidden" name="trade_type" id="trade_type" value="buy">
                            
                            <!-- Quantity -->
                            <div style="margin-bottom: 1.5rem;">
                                <label style="color: rgba(255, 255, 255, 0.9); display: block; margin-bottom: 0.5rem;">
                                    <i class="fas fa-layer-group"></i> Quantity
                                </label>
                                <input type="number" name="quantity" step="0.01" min="0.01" required class="price-input" placeholder="Enter quantity">
                            </div>
                            
                            <!-- Price -->
                            <div style="margin-bottom: 1.5rem;">
                                <label style="color: rgba(255, 255, 255, 0.9); display: block; margin-bottom: 0.5rem;">
                                    <i class="fas fa-dollar-sign"></i> Price (USD)
                                </label>
                                <input type="number" name="price" step="0.01" min="0.01" required class="price-input" placeholder="Enter price per unit">
                            </div>
                            
                            <!-- Payment Method -->
                            <div style="margin-bottom: 1.5rem;">
                                <label style="color: rgba(255, 255, 255, 0.9); display: block; margin-bottom: 0.5rem;">
                                    <i class="fas fa-credit-card"></i> Payment Method
                                </label>
                                <select name="payment_method" required class="price-input">
                                    <option value="">Select Payment Method</option>
                                    <optgroup label="🏦 Ethiopian Banks">
                                        <option value="cbe">🏛️ Commercial Bank of Ethiopia (CBE)</option>
                                        <option value="wegagen">💰 Wegagen Bank</option>
                                        <option value="abyssinia">🦁 Bank of Abyssinia</option>
                                    </optgroup>
                                    <optgroup label="💳 Cards & Digital">
                                        <option value="mastercard">💳 MasterCard</option>
                                        <option value="visa">💎 Visa Card</option>
                                        <option value="paypal">🅿️ PayPal</option>
                                    </optgroup>
                                    <optgroup label="📱 Mobile & Other">
                                        <option value="telebirr">📱 TeleBirr</option>
                                        <option value="mpesa">📲 M-Pesa</option>
                                    </optgroup>
                                </select>
                            </div>
                            
                            <!-- Total Amount Display -->
                            <div style="background: rgba(255, 215, 0, 0.1); border: 1px solid rgba(255, 215, 0, 0.3); border-radius: 8px; padding: 1rem; margin-bottom: 1.5rem;">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <span style="color: #ffd700;">Total Amount:</span>
                                    <span id="totalAmount" style="color: #228b22; font-weight: bold; font-size: 1.2rem;">Br0.00</span>
                                </div>
                            </div>
                            
                            <!-- Execute Button -->
                            <button type="submit" name="execute_trade" id="executeBtn" 
                                    style="width: 100%; background: linear-gradient(45deg, #228b22, #32cd32); color: white; padding: 1.5rem; border: none; border-radius: 8px; font-size: 1.1rem; font-weight: 600; cursor: pointer;">
                                <i class="fas fa-bolt"></i> Execute Trade
                            </button>
                        </form>
                    </div>

                    <!-- Watchlist & Quick Actions -->
                    <div>
                        <!-- Watchlist -->
                        <div class="trading-panel">
                            <h3 style="color: #ffd700; margin-bottom: 2rem; text-align: center;">
                                <i class="fas fa-eye"></i> Watchlist
                            </h3>
                            
                            <?php foreach($watchlist as $asset): ?>
                                <div style="background: rgba(255, 255, 255, 0.03); border-radius: 8px; padding: 1rem; margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <div style="color: #ffd700; font-weight: bold;"><?php echo $asset['symbol']; ?></div>
                                        <div style="color: rgba(255, 255, 255, 0.7); font-size: 0.9rem;"><?php echo $asset['name']; ?></div>
                                    </div>
                                    <div style="text-align: right;">
                                        <div style="color: white; font-weight: bold;">$<?php echo number_format($asset['price'], 2); ?></div>
                                        <div style="color: <?php echo $asset['change'] >= 0 ? '#228b22' : '#ff5252'; ?>; font-size: 0.9rem;">
                                            <?php echo ($asset['change'] >= 0 ? '+' : '') . number_format($asset['change'], 2); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            
                            <div style="text-align: center; margin-top: 1.5rem;">
                                <a href="markets.php" style="background: rgba(34, 139, 34, 0.2); color: #228b22; padding: 0.75rem 1.5rem; border: 1px solid rgba(34, 139, 34, 0.3); border-radius: 8px; text-decoration: none;">
                                    <i class="fas fa-plus"></i> Add to Watchlist
                                </a>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="trading-panel">
                            <h3 style="color: #ffd700; margin-bottom: 2rem; text-align: center;">
                                <i class="fas fa-bolt"></i> Quick Actions
                            </h3>
                            
                            <div style="display: grid; gap: 1rem;">
                                <a href="orders.php" style="background: rgba(255, 215, 0, 0.1); color: #ffd700; padding: 1rem; border: 1px solid rgba(255, 215, 0, 0.3); border-radius: 8px; text-decoration: none; text-align: center;">
                                    <i class="fas fa-list-alt"></i> View Orders
                                </a>
                                <a href="portfolio.php" style="background: rgba(34, 139, 34, 0.1); color: #228b22; padding: 1rem; border: 1px solid rgba(34, 139, 34, 0.3); border-radius: 8px; text-decoration: none; text-align: center;">
                                    <i class="fas fa-briefcase"></i> Portfolio
                                </a>
                                <a href="analysis.php" style="background: rgba(255, 255, 255, 0.05); color: white; padding: 1rem; border: 1px solid rgba(34, 139, 34, 0.3); border-radius: 8px; text-decoration: none; text-align: center;">
                                    <i class="fas fa-chart-line"></i> Analysis
                                </a>
                                <a href="wallet.php" style="background: rgba(255, 215, 0, 0.1); color: #ffd700; padding: 1rem; border: 1px solid rgba(255, 215, 0, 0.3); border-radius: 8px; text-decoration: none; text-align: center;">
                                    <i class="fas fa-wallet"></i> Manage Funds
                                </a>
                            </div>
                        </div>
                    </div>
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
        let currentTradeType = 'buy';
        let currentOrderType = 'market';

        function setTradeType(type) {
            currentTradeType = type;
            document.getElementById('trade_type').value = type;
            
            const buyBtn = document.getElementById('buy-btn');
            const sellBtn = document.getElementById('sell-btn');
            
            if (type === 'buy') {
                buyBtn.style.opacity = '1';
                sellBtn.style.opacity = '0.6';
            } else {
                buyBtn.style.opacity = '0.6';
                sellBtn.style.opacity = '1';
            }
            
            updateExecuteButton();
        }

        function setOrderType(type) {
            currentOrderType = type;
            document.getElementById('order_type').value = type;
            
            // Update tab appearance
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.getElementById(type + '-tab').classList.add('active');
        }

        function updateExecuteButton() {
            const btn = document.getElementById('executeBtn');
            if (currentTradeType === 'buy') {
                btn.innerHTML = '<i class="fas fa-arrow-up"></i> Execute Buy Order';
                btn.style.background = 'linear-gradient(45deg, #228b22, #32cd32)';
            } else {
                btn.innerHTML = '<i class="fas fa-arrow-down"></i> Execute Sell Order';
                btn.style.background = 'linear-gradient(45deg, #ff5252, #ff7979)';
            }
        }

        // Calculate total amount
        function updateTotal() {
            const quantity = parseFloat(document.querySelector('input[name="quantity"]').value) || 0;
            const price = parseFloat(document.querySelector('input[name="price"]').value) || 0;
            const total = quantity * price;
            
            document.getElementById('totalAmount').textContent = 'Br' + total.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }

        // Add event listeners for real-time calculation
        document.addEventListener('DOMContentLoaded', function() {
            const quantityInput = document.querySelector('input[name="quantity"]');
            const priceInput = document.querySelector('input[name="price"]');
            
            quantityInput.addEventListener('input', updateTotal);
            priceInput.addEventListener('input', updateTotal);
        });
    </script>
</body>
</html>