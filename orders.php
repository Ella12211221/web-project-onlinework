<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders - Concordial Nexus</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .order-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(34, 139, 34, 0.3);
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        .order-card:hover {
            background: rgba(34, 139, 34, 0.1);
            border-color: rgba(34, 139, 34, 0.5);
        }
        .order-tabs {
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
        .status-pending { color: #ffd700; }
        .status-filled { color: #228b22; }
        .status-cancelled { color: #ff5252; }
        .status-partial { color: #32cd32; }
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
        
        // Handle order cancellation
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order'])) {
            $order_id = $_POST['order_id'];
            // In real implementation, update order status to cancelled
            $success_message = "Order #$order_id has been cancelled successfully.";
        }
        
        // Sample orders data - in real implementation, this would come from database
        $orders = [
            'open' => [
                [
                    'id' => 'ORD001',
                    'symbol' => 'AAPL',
                    'name' => 'Apple Inc.',
                    'type' => 'buy',
                    'order_type' => 'limit',
                    'quantity' => 5,
                    'price' => 170.00,
                    'filled_quantity' => 0,
                    'status' => 'pending',
                    'created_at' => '2026-02-02 09:30:00',
                    'expires_at' => '2026-02-09 16:00:00'
                ],
                [
                    'id' => 'ORD002',
                    'symbol' => 'BTC',
                    'name' => 'Bitcoin',
                    'type' => 'sell',
                    'order_type' => 'stop',
                    'quantity' => 0.1,
                    'price' => 45000.00,
                    'filled_quantity' => 0,
                    'status' => 'pending',
                    'created_at' => '2026-02-02 10:15:00',
                    'expires_at' => '2026-02-09 16:00:00'
                ],
                [
                    'id' => 'ORD003',
                    'symbol' => 'GOLD',
                    'name' => 'Gold Spot',
                    'type' => 'buy',
                    'order_type' => 'limit',
                    'quantity' => 1,
                    'price' => 2000.00,
                    'filled_quantity' => 0.5,
                    'status' => 'partial',
                    'created_at' => '2026-02-02 11:00:00',
                    'expires_at' => '2026-02-09 16:00:00'
                ]
            ],
            'history' => [
                [
                    'id' => 'ORD004',
                    'symbol' => 'MSFT',
                    'name' => 'Microsoft Corp.',
                    'type' => 'buy',
                    'order_type' => 'market',
                    'quantity' => 3,
                    'price' => 375.00,
                    'filled_quantity' => 3,
                    'status' => 'filled',
                    'created_at' => '2026-02-01 14:30:00',
                    'filled_at' => '2026-02-01 14:30:15'
                ],
                [
                    'id' => 'ORD005',
                    'symbol' => 'ETH',
                    'name' => 'Ethereum',
                    'type' => 'sell',
                    'order_type' => 'limit',
                    'quantity' => 2,
                    'price' => 2700.00,
                    'filled_quantity' => 0,
                    'status' => 'cancelled',
                    'created_at' => '2026-01-31 16:45:00',
                    'cancelled_at' => '2026-02-01 09:00:00'
                ],
                [
                    'id' => 'ORD006',
                    'symbol' => 'TSLA',
                    'name' => 'Tesla Inc.',
                    'type' => 'buy',
                    'order_type' => 'market',
                    'quantity' => 2,
                    'price' => 250.00,
                    'filled_quantity' => 2,
                    'status' => 'filled',
                    'created_at' => '2026-01-30 11:20:00',
                    'filled_at' => '2026-01-30 11:20:08'
                ]
            ]
        ];
        
        $active_tab = $_GET['tab'] ?? 'open';
        
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
                <?php renderNavigation('orders'); ?>
            </div>
        </nav>
    </header>

    <main style="margin-top: 100px; padding: 2rem;">
        <div class="container">
            <div style="background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(20px); border-radius: 20px; padding: 3rem; border: 1px solid rgba(34, 139, 34, 0.2);">
                
                <div style="text-align: center; margin-bottom: 3rem;">
                    <h1 style="color: #ffd700; margin-bottom: 1rem;">
                        <i class="fas fa-list-alt"></i> Order Management
                    </h1>
                    <p style="color: rgba(255, 255, 255, 0.8);">
                        Track and manage your trading orders
                    </p>
                </div>

                <?php if (isset($success_message)): ?>
                    <div style="background: rgba(34, 139, 34, 0.2); color: #228b22; padding: 1rem; border-radius: 8px; margin-bottom: 2rem; text-align: center; border: 1px solid rgba(34, 139, 34, 0.3);">
                        <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>

                <!-- Order Tabs -->
                <div class="order-tabs">
                    <button class="tab-button <?php echo $active_tab === 'open' ? 'active' : ''; ?>" onclick="switchTab('open')">
                        <i class="fas fa-clock"></i> Open Orders (<?php echo count($orders['open']); ?>)
                    </button>
                    <button class="tab-button <?php echo $active_tab === 'history' ? 'active' : ''; ?>" onclick="switchTab('history')">
                        <i class="fas fa-history"></i> Order History (<?php echo count($orders['history']); ?>)
                    </button>
                </div>

                <!-- Order Statistics -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 3rem;">
                    <div style="background: rgba(255, 215, 0, 0.1); border: 1px solid rgba(255, 215, 0, 0.3); border-radius: 15px; padding: 1.5rem; text-align: center;">
                        <h4 style="color: #ffd700; margin-bottom: 0.5rem;">
                            <i class="fas fa-clock"></i> Pending Orders
                        </h4>
                        <div style="font-size: 1.5rem; color: #228b22; font-weight: bold;">
                            <?php 
                            $pending = 0;
                            foreach($orders['open'] as $order) {
                                if($order['status'] === 'pending') $pending++;
                            }
                            echo $pending;
                            ?>
                        </div>
                    </div>
                    
                    <div style="background: rgba(34, 139, 34, 0.1); border: 1px solid rgba(34, 139, 34, 0.3); border-radius: 15px; padding: 1.5rem; text-align: center;">
                        <h4 style="color: #228b22; margin-bottom: 0.5rem;">
                            <i class="fas fa-check-circle"></i> Filled Orders
                        </h4>
                        <div style="font-size: 1.5rem; color: #ffd700; font-weight: bold;">
                            <?php 
                            $filled = 0;
                            foreach($orders['history'] as $order) {
                                if($order['status'] === 'filled') $filled++;
                            }
                            echo $filled;
                            ?>
                        </div>
                    </div>
                    
                    <div style="background: rgba(50, 205, 50, 0.1); border: 1px solid rgba(50, 205, 50, 0.3); border-radius: 15px; padding: 1.5rem; text-align: center;">
                        <h4 style="color: #32cd32; margin-bottom: 0.5rem;">
                            <i class="fas fa-chart-pie"></i> Partial Fills
                        </h4>
                        <div style="font-size: 1.5rem; color: #ffd700; font-weight: bold;">
                            <?php 
                            $partial = 0;
                            foreach($orders['open'] as $order) {
                                if($order['status'] === 'partial') $partial++;
                            }
                            echo $partial;
                            ?>
                        </div>
                    </div>
                    
                    <div style="background: rgba(255, 82, 82, 0.1); border: 1px solid rgba(255, 82, 82, 0.3); border-radius: 15px; padding: 1.5rem; text-align: center;">
                        <h4 style="color: #ff5252; margin-bottom: 0.5rem;">
                            <i class="fas fa-times-circle"></i> Cancelled
                        </h4>
                        <div style="font-size: 1.5rem; color: #ffd700; font-weight: bold;">
                            <?php 
                            $cancelled = 0;
                            foreach($orders['history'] as $order) {
                                if($order['status'] === 'cancelled') $cancelled++;
                            }
                            echo $cancelled;
                            ?>
                        </div>
                    </div>
                </div>

                <!-- Orders List -->
                <div style="background: rgba(255, 255, 255, 0.05); border-radius: 15px; padding: 2rem; border: 1px solid rgba(34, 139, 34, 0.2);">
                    <?php if (empty($orders[$active_tab])): ?>
                        <div style="text-align: center; color: rgba(255, 255, 255, 0.6); padding: 3rem;">
                            <i class="fas fa-list-alt" style="font-size: 4rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                            <h4>No <?php echo ucfirst($active_tab); ?> Orders</h4>
                            <p><?php echo $active_tab === 'open' ? 'You have no pending orders.' : 'No order history available.'; ?></p>
                            <?php if ($active_tab === 'open'): ?>
                                <a href="trading.php" style="background: linear-gradient(45deg, #228b22, #32cd32); color: white; padding: 1rem 2rem; border-radius: 8px; text-decoration: none; margin-top: 1rem; display: inline-block;">
                                    <i class="fas fa-plus"></i> Place New Order
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div style="overflow-x: auto;">
                            <table style="width: 100%; border-collapse: collapse;">
                                <thead>
                                    <tr style="border-bottom: 2px solid rgba(34, 139, 34, 0.3);">
                                        <th style="padding: 1rem; text-align: left; color: #ffd700;">Order ID</th>
                                        <th style="padding: 1rem; text-align: left; color: #ffd700;">Asset</th>
                                        <th style="padding: 1rem; text-align: center; color: #ffd700;">Type</th>
                                        <th style="padding: 1rem; text-align: right; color: #ffd700;">Quantity</th>
                                        <th style="padding: 1rem; text-align: right; color: #ffd700;">Price</th>
                                        <th style="padding: 1rem; text-align: right; color: #ffd700;">Total Value</th>
                                        <th style="padding: 1rem; text-align: center; color: #ffd700;">Status</th>
                                        <th style="padding: 1rem; text-align: center; color: #ffd700;">Date</th>
                                        <?php if ($active_tab === 'open'): ?>
                                            <th style="padding: 1rem; text-align: center; color: #ffd700;">Actions</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($orders[$active_tab] as $order): ?>
                                        <tr style="border-bottom: 1px solid rgba(34, 139, 34, 0.1);">
                                            <td style="padding: 1rem; color: #ffd700; font-weight: bold; font-family: monospace;">
                                                <?php echo $order['id']; ?>
                                            </td>
                                            <td style="padding: 1rem;">
                                                <div>
                                                    <div style="color: white; font-weight: bold;">
                                                        <?php echo $order['symbol']; ?>
                                                    </div>
                                                    <div style="color: rgba(255, 255, 255, 0.7); font-size: 0.9rem;">
                                                        <?php echo $order['name']; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td style="padding: 1rem; text-align: center;">
                                                <span style="background: rgba(<?php echo $order['type'] === 'buy' ? '34, 139, 34' : '255, 82, 82'; ?>, 0.2); color: <?php echo $order['type'] === 'buy' ? '#228b22' : '#ff5252'; ?>; padding: 0.5rem 1rem; border-radius: 15px; font-size: 0.9rem; text-transform: uppercase;">
                                                    <i class="fas fa-<?php echo $order['type'] === 'buy' ? 'arrow-up' : 'arrow-down'; ?>"></i>
                                                    <?php echo $order['type']; ?>
                                                </span>
                                                <div style="color: rgba(255, 255, 255, 0.6); font-size: 0.8rem; margin-top: 0.25rem;">
                                                    <?php echo strtoupper($order['order_type']); ?>
                                                </div>
                                            </td>
                                            <td style="padding: 1rem; text-align: right; color: white; font-weight: 600;">
                                                <?php if ($order['filled_quantity'] > 0): ?>
                                                    <div><?php echo number_format($order['filled_quantity'], 4); ?></div>
                                                    <div style="color: rgba(255, 255, 255, 0.6); font-size: 0.9rem;">
                                                        of <?php echo number_format($order['quantity'], 4); ?>
                                                    </div>
                                                <?php else: ?>
                                                    <?php echo number_format($order['quantity'], 4); ?>
                                                <?php endif; ?>
                                            </td>
                                            <td style="padding: 1rem; text-align: right; color: white; font-weight: 600;">
                                                $<?php echo number_format($order['price'], 2); ?>
                                            </td>
                                            <td style="padding: 1rem; text-align: right; color: #228b22; font-weight: 600; font-size: 1.1rem;">
                                                Br<?php echo number_format($order['quantity'] * $order['price'], 2); ?>
                                            </td>
                                            <td style="padding: 1rem; text-align: center;">
                                                <span class="status-<?php echo $order['status']; ?>" style="background: rgba(<?php 
                                                    $colors = [
                                                        'pending' => '255, 215, 0',
                                                        'filled' => '34, 139, 34',
                                                        'cancelled' => '255, 82, 82',
                                                        'partial' => '50, 205, 50'
                                                    ];
                                                    echo $colors[$order['status']];
                                                ?>, 0.2); padding: 0.5rem 1rem; border-radius: 15px; font-size: 0.9rem; text-transform: uppercase;">
                                                    <?php echo $order['status']; ?>
                                                </span>
                                            </td>
                                            <td style="padding: 1rem; text-align: center; color: rgba(255, 255, 255, 0.8);">
                                                <div><?php echo date('M j, Y', strtotime($order['created_at'])); ?></div>
                                                <div style="font-size: 0.9rem; color: rgba(255, 255, 255, 0.6);">
                                                    <?php echo date('H:i', strtotime($order['created_at'])); ?>
                                                </div>
                                            </td>
                                            <?php if ($active_tab === 'open'): ?>
                                                <td style="padding: 1rem; text-align: center;">
                                                    <?php if ($order['status'] === 'pending' || $order['status'] === 'partial'): ?>
                                                        <form method="POST" style="display: inline;">
                                                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                            <button type="submit" name="cancel_order" 
                                                                    onclick="return confirm('Are you sure you want to cancel this order?')"
                                                                    style="background: rgba(255, 82, 82, 0.2); color: #ff5252; padding: 0.5rem 1rem; border: 1px solid rgba(255, 82, 82, 0.3); border-radius: 6px; cursor: pointer; font-size: 0.9rem;">
                                                                <i class="fas fa-times"></i> Cancel
                                                            </button>
                                                        </form>
                                                    <?php else: ?>
                                                        <span style="color: rgba(255, 255, 255, 0.5);">-</span>
                                                    <?php endif; ?>
                                                </td>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <?php if ($active_tab === 'open'): ?>
                            <div style="text-align: center; margin-top: 2rem;">
                                <a href="trading.php" style="background: linear-gradient(45deg, #228b22, #32cd32); color: white; padding: 1rem 2rem; border-radius: 8px; text-decoration: none; font-weight: 600;">
                                    <i class="fas fa-plus"></i> Place New Order
                                </a>
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

    <script>
        function switchTab(tab) {
            window.location.href = '?tab=' + tab;
        }

        // Auto-refresh orders every 30 seconds for open orders
        if (window.location.search.includes('tab=open') || !window.location.search.includes('tab=')) {
            setInterval(function() {
                // In real implementation, this would fetch live data via AJAX
                console.log('Orders refresh...');
            }, 30000);
        }
    </script>
</body>
</html>