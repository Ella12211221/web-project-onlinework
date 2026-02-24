<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

try {
    $pdo = new PDO("mysql:host=localhost;dbname=concordial_nexus;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!$user) {
        session_destroy();
        header('Location: ../auth/login.php');
        exit();
    }
    
    // Get ALL products
    $all_products = $pdo->query("SELECT * FROM products ORDER BY 
        CASE category 
            WHEN 'regular' THEN 1 
            WHEN 'premium' THEN 2 
            WHEN 'vip_one' THEN 3 
            WHEN 'vip_two' THEN 4 
            WHEN 'vip_three' THEN 5 
        END, min_amount ASC")->fetchAll();
    
} catch(PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Investment Levels - Concordial Nexus</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            min-height: 100vh;
            padding: 2rem;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .header {
            text-align: center;
            margin-bottom: 3rem;
            color: white;
        }
        
        .header h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
            background: linear-gradient(45deg, #ffd700, #ffed4e);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .header p {
            font-size: 1.2rem;
            color: rgba(255, 255, 255, 0.8);
        }
        
        .vip-section {
            margin-bottom: 4rem;
        }
        
        .section-title {
            text-align: center;
            font-size: 2rem;
            color: white;
            margin-bottom: 2rem;
            padding: 1rem;
            border-radius: 10px;
        }
        
        .section-title.vip-one {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
        }
        
        .section-title.vip-two {
            background: linear-gradient(135deg, #f39c12, #e67e22);
        }
        
        .section-title.vip-three {
            background: linear-gradient(135deg, #9b59b6, #8e44ad);
        }
        
        .levels-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .level-card {
            background: #2c3e50;
            border-radius: 20px;
            padding: 2rem;
            position: relative;
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
            border: 2px solid transparent;
        }
        
        .level-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
        }
        
        .level-card.popular {
            border-color: #ffd700;
        }
        
        .level-card.popular::before {
            content: 'Most Popular';
            position: absolute;
            top: 20px;
            right: -35px;
            background: linear-gradient(135deg, #ffd700, #ffed4e);
            color: #1a1a2e;
            padding: 5px 40px;
            transform: rotate(45deg);
            font-weight: bold;
            font-size: 0.85rem;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
        }
        
        .level-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .level-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: white;
            margin-bottom: 0.5rem;
        }
        
        .level-subtitle {
            font-size: 1.2rem;
            color: rgba(255, 255, 255, 0.7);
        }
        
        .amount-display {
            text-align: center;
            background: rgba(255, 255, 255, 0.1);
            padding: 1.5rem;
            border-radius: 15px;
            margin-bottom: 2rem;
        }
        
        .amount-label {
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 0.5rem;
        }
        
        .amount-value {
            font-size: 2.5rem;
            font-weight: bold;
            color: #ffd700;
        }
        
        .features-list {
            list-style: none;
            margin-bottom: 2rem;
        }
        
        .features-list li {
            padding: 0.75rem 0;
            color: white;
            display: flex;
            align-items: center;
            gap: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .features-list li:last-child {
            border-bottom: none;
        }
        
        .features-list i {
            color: #27ae60;
            font-size: 1.2rem;
        }
        
        .features-list .warning {
            color: #e74c3c;
        }
        
        .payment-methods {
            background: rgba(0, 0, 0, 0.3);
            padding: 1.5rem;
            border-radius: 15px;
            margin-bottom: 1.5rem;
        }
        
        .payment-methods h4 {
            color: white;
            margin-bottom: 1rem;
            font-size: 1rem;
            text-align: center;
        }
        
        .payment-options {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.75rem;
        }
        
        .payment-option {
            background: rgba(255, 255, 255, 0.1);
            padding: 0.75rem;
            border-radius: 10px;
            text-align: center;
            color: white;
            font-size: 0.85rem;
            transition: background 0.3s;
        }
        
        .payment-option:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        .payment-option i {
            display: block;
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: #27ae60;
        }
        
        .get-started-btn {
            width: 100%;
            padding: 1.25rem;
            background: linear-gradient(135deg, #ffd700, #ffed4e);
            color: #1a1a2e;
            border: none;
            border-radius: 15px;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.3s, box-shadow 0.3s;
            text-decoration: none;
            display: block;
            text-align: center;
        }
        
        .get-started-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 10px 30px rgba(255, 215, 0, 0.4);
        }
        
        .back-btn {
            display: inline-block;
            padding: 1rem 2rem;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            margin-bottom: 2rem;
            transition: background 0.3s;
        }
        
        .back-btn:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        @media (max-width: 768px) {
            .levels-grid {
                grid-template-columns: 1fr;
            }
            
            .header h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
        
        <div class="header">
            <h1><i class="fas fa-crown"></i> All Investment Levels</h1>
            <p>Choose your investment level and start earning premium returns</p>
        </div>
        
        <?php
        // Group products by category
        $regular = array_filter($all_products, fn($p) => $p['category'] === 'regular');
        $premium = array_filter($all_products, fn($p) => $p['category'] === 'premium');
        $vip_one = array_filter($all_products, fn($p) => $p['category'] === 'vip_one');
        $vip_two = array_filter($all_products, fn($p) => $p['category'] === 'vip_two');
        $vip_three = array_filter($all_products, fn($p) => $p['category'] === 'vip_three');
        ?>
        
        <!-- REGULAR Section -->
        <?php if (!empty($regular)): ?>
        <div class="vip-section">
            <div class="section-title" style="background: linear-gradient(135deg, #3498db, #2980b9);">
                <i class="fas fa-chart-line"></i> REGULAR PACKAGES
            </div>
            
            <div class="levels-grid">
                <?php 
                $level_counter = 1;
                foreach ($regular as $index => $product): 
                    $profit = $product['min_amount'] * $product['return_percentage'] / 100;
                    $is_popular = $index === 4; // Middle level
                ?>
                <div class="level-card <?php echo $is_popular ? 'popular' : ''; ?>">
                    <div class="level-header">
                        <div class="level-number">Level <?php echo $level_counter++; ?></div>
                        <div class="level-subtitle">Br<?php echo number_format($product['min_amount']); ?></div>
                    </div>
                    
                    <div class="amount-display">
                        <div class="amount-label">Investment Amount</div>
                        <div class="amount-value">Br<?php echo number_format($product['min_amount']); ?></div>
                    </div>
                    
                    <ul class="features-list">
                        <li>
                            <i class="fas fa-check-circle"></i>
                            <span><?php echo $product['return_percentage']; ?>% Expected Return</span>
                        </li>
                        <li>
                            <i class="fas fa-clock"></i>
                            <span><?php echo $product['duration_days']; ?> Days Duration</span>
                        </li>
                        <li>
                            <i class="fas fa-coins"></i>
                            <span>Br<?php echo number_format($profit); ?> Profit</span>
                        </li>
                        <li>
                            <i class="fas fa-chart-line"></i>
                            <span>Basic Trading Tools</span>
                        </li>
                        <li>
                            <i class="fas fa-file-alt"></i>
                            <span>Monthly Reports</span>
                        </li>
                    </ul>
                    
                    <div class="payment-methods">
                        <h4><i class="fas fa-credit-card"></i> Payment Methods</h4>
                        <div class="payment-options">
                            <div class="payment-option">
                                <i class="fas fa-mobile-alt"></i>
                                CBE Birr
                            </div>
                            <div class="payment-option">
                                <i class="fas fa-university"></i>
                                Bank Transfer
                            </div>
                            <div class="payment-option">
                                <i class="fas fa-wallet"></i>
                                TeleBirr
                            </div>
                            <div class="payment-option">
                                <i class="fas fa-money-bill-wave"></i>
                                M-Birr
                            </div>
                        </div>
                    </div>
                    
                    <a href="product-details.php?id=<?php echo $product['id']; ?>" class="get-started-btn">
                        Get Started <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- PREMIUM Section -->
        <?php if (!empty($premium)): ?>
        <div class="vip-section">
            <div class="section-title" style="background: linear-gradient(135deg, #9b59b6, #8e44ad);">
                <i class="fas fa-star"></i> PREMIUM PACKAGES
            </div>
            
            <div class="levels-grid">
                <?php 
                $level_counter = 1;
                foreach ($premium as $index => $product): 
                    $profit = $product['min_amount'] * $product['return_percentage'] / 100;
                    $is_popular = $index === 2;
                ?>
                <div class="level-card <?php echo $is_popular ? 'popular' : ''; ?>">
                    <div class="level-header">
                        <div class="level-number">Level <?php echo $level_counter++; ?></div>
                        <div class="level-subtitle">Br<?php echo number_format($product['min_amount']); ?></div>
                    </div>
                    
                    <div class="amount-display">
                        <div class="amount-label">Investment Amount</div>
                        <div class="amount-value">Br<?php echo number_format($product['min_amount']); ?></div>
                    </div>
                    
                    <ul class="features-list">
                        <li>
                            <i class="fas fa-check-circle"></i>
                            <span><?php echo $product['return_percentage']; ?>% Expected Return</span>
                        </li>
                        <li>
                            <i class="fas fa-clock"></i>
                            <span><?php echo $product['duration_days']; ?> Days Duration</span>
                        </li>
                        <li>
                            <i class="fas fa-coins"></i>
                            <span>Br<?php echo number_format($profit); ?> Profit</span>
                        </li>
                        <li>
                            <i class="fas fa-tools"></i>
                            <span>Advanced Tools</span>
                        </li>
                        <li>
                            <i class="fas fa-chart-bar"></i>
                            <span>Weekly Reports</span>
                        </li>
                        <li>
                            <i class="fas fa-headset"></i>
                            <span>Priority Support</span>
                        </li>
                    </ul>
                    
                    <div class="payment-methods">
                        <h4><i class="fas fa-credit-card"></i> Payment Methods</h4>
                        <div class="payment-options">
                            <div class="payment-option">
                                <i class="fas fa-mobile-alt"></i>
                                CBE Birr
                            </div>
                            <div class="payment-option">
                                <i class="fas fa-university"></i>
                                Bank Transfer
                            </div>
                            <div class="payment-option">
                                <i class="fas fa-wallet"></i>
                                TeleBirr
                            </div>
                            <div class="payment-option">
                                <i class="fas fa-money-bill-wave"></i>
                                M-Birr
                            </div>
                        </div>
                    </div>
                    
                    <a href="product-details.php?id=<?php echo $product['id']; ?>" class="get-started-btn">
                        Get Started <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- VIP ONE Section -->
        <?php if (!empty($vip_one)): ?>
        <div class="vip-section">
            <div class="section-title vip-one">
                <i class="fas fa-star"></i> VIP ONE - Entry Level
            </div>
            
            <div class="levels-grid">
                <?php foreach ($vip_one as $index => $product): 
                    $profit = $product['min_amount'] * $product['return_percentage'] / 100;
                    $is_popular = $index === 1; // Middle level is popular
                ?>
                <div class="level-card <?php echo $is_popular ? 'popular' : ''; ?>">
                    <div class="level-header">
                        <div class="level-number">Level <?php echo $index + 1; ?></div>
                        <div class="level-subtitle"><?php echo $index === 0 ? 'Beginner' : ($index === 1 ? 'Intermediate' : 'Elite'); ?></div>
                    </div>
                    
                    <div class="amount-display">
                        <div class="amount-label">Investment Amount</div>
                        <div class="amount-value">Br<?php echo number_format($product['min_amount']); ?></div>
                    </div>
                    
                    <ul class="features-list">
                        <li>
                            <i class="fas fa-check-circle"></i>
                            <span><?php echo $product['return_percentage']; ?>% Expected Return</span>
                        </li>
                        <li>
                            <i class="fas fa-clock"></i>
                            <span><?php echo $product['duration_days']; ?> Days Duration</span>
                        </li>
                        <li>
                            <i class="fas fa-coins"></i>
                            <span>Br<?php echo number_format($profit); ?> Profit</span>
                        </li>
                        <li>
                            <i class="fas fa-chart-line"></i>
                            <span>Basic Trading Tools</span>
                        </li>
                        <li>
                            <i class="fas fa-file-alt"></i>
                            <span>Monthly Reports</span>
                        </li>
                    </ul>
                    
                    <div class="payment-methods">
                        <h4><i class="fas fa-credit-card"></i> Payment Methods</h4>
                        <div class="payment-options">
                            <div class="payment-option">
                                <i class="fas fa-mobile-alt"></i>
                                CBE Birr
                            </div>
                            <div class="payment-option">
                                <i class="fas fa-university"></i>
                                Bank Transfer
                            </div>
                            <div class="payment-option">
                                <i class="fas fa-wallet"></i>
                                TeleBirr
                            </div>
                            <div class="payment-option">
                                <i class="fas fa-money-bill-wave"></i>
                                M-Birr
                            </div>
                        </div>
                    </div>
                    
                    <a href="product-details.php?id=<?php echo $product['id']; ?>" class="get-started-btn">
                        Get Started <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- VIP TWO Section -->
        <?php if (!empty($vip_two)): ?>
        <div class="vip-section">
            <div class="section-title vip-two">
                <i class="fas fa-gem"></i> VIP TWO - Mid Level
            </div>
            
            <div class="levels-grid">
                <?php foreach ($vip_two as $index => $product): 
                    $profit = $product['min_amount'] * $product['return_percentage'] / 100;
                    $is_popular = $index === 1;
                ?>
                <div class="level-card <?php echo $is_popular ? 'popular' : ''; ?>">
                    <div class="level-header">
                        <div class="level-number">Level <?php echo $index + 1; ?></div>
                        <div class="level-subtitle"><?php echo $index === 0 ? 'Beginner' : ($index === 1 ? 'Intermediate' : 'Elite'); ?></div>
                    </div>
                    
                    <div class="amount-display">
                        <div class="amount-label">Investment Amount</div>
                        <div class="amount-value">Br<?php echo number_format($product['min_amount']); ?></div>
                    </div>
                    
                    <ul class="features-list">
                        <li>
                            <i class="fas fa-check-circle"></i>
                            <span><?php echo $product['return_percentage']; ?>% Expected Return</span>
                        </li>
                        <li>
                            <i class="fas fa-clock"></i>
                            <span><?php echo $product['duration_days']; ?> Days Duration</span>
                        </li>
                        <li>
                            <i class="fas fa-coins"></i>
                            <span>Br<?php echo number_format($profit); ?> Profit</span>
                        </li>
                        <li>
                            <i class="fas fa-tools"></i>
                            <span>Advanced Tools</span>
                        </li>
                        <li>
                            <i class="fas fa-chart-bar"></i>
                            <span>Weekly Reports</span>
                        </li>
                        <li>
                            <i class="fas fa-headset"></i>
                            <span>Priority Support</span>
                        </li>
                    </ul>
                    
                    <div class="payment-methods">
                        <h4><i class="fas fa-credit-card"></i> Payment Methods</h4>
                        <div class="payment-options">
                            <div class="payment-option">
                                <i class="fas fa-mobile-alt"></i>
                                CBE Birr
                            </div>
                            <div class="payment-option">
                                <i class="fas fa-university"></i>
                                Bank Transfer
                            </div>
                            <div class="payment-option">
                                <i class="fas fa-wallet"></i>
                                TeleBirr
                            </div>
                            <div class="payment-option">
                                <i class="fas fa-money-bill-wave"></i>
                                M-Birr
                            </div>
                        </div>
                    </div>
                    
                    <a href="product-details.php?id=<?php echo $product['id']; ?>" class="get-started-btn">
                        Get Started <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- VIP THREE Section -->
        <?php if (!empty($vip_three)): ?>
        <div class="vip-section">
            <div class="section-title vip-three">
                <i class="fas fa-crown"></i> VIP THREE - Elite Level
            </div>
            
            <div class="levels-grid">
                <?php foreach ($vip_three as $index => $product): 
                    $profit = $product['min_amount'] * $product['return_percentage'] / 100;
                    $is_popular = $index === 0;
                    $has_referral_req = isset($product['min_referrals']) && $product['min_referrals'] > 0;
                    $has_commission = isset($product['has_own_commission']) && $product['has_own_commission'] == 1;
                ?>
                <div class="level-card <?php echo $is_popular ? 'popular' : ''; ?>">
                    <div class="level-header">
                        <div class="level-number">Level <?php echo $index + 1; ?></div>
                        <div class="level-subtitle"><?php echo $index === 0 ? 'Beginner' : ($index === 1 ? 'Intermediate' : ($index === 2 ? 'Advanced' : 'Elite')); ?></div>
                    </div>
                    
                    <div class="amount-display">
                        <div class="amount-label">Investment Amount</div>
                        <div class="amount-value">Br<?php echo number_format($product['min_amount']); ?></div>
                    </div>
                    
                    <ul class="features-list">
                        <li>
                            <i class="fas fa-check-circle"></i>
                            <span><?php echo $product['return_percentage']; ?>% Expected Return</span>
                        </li>
                        <li>
                            <i class="fas fa-clock"></i>
                            <span><?php echo $product['duration_days']; ?> Days Duration (<?php echo round($product['duration_days']/30); ?> months)</span>
                        </li>
                        <li>
                            <i class="fas fa-coins"></i>
                            <span>Br<?php echo number_format($profit); ?> Profit</span>
                        </li>
                        <?php if ($has_referral_req): ?>
                        <li>
                            <i class="fas fa-users warning"></i>
                            <span style="color: #e74c3c; font-weight: bold;">Requires <?php echo $product['min_referrals']; ?> Referrals</span>
                        </li>
                        <?php endif; ?>
                        <?php if ($has_commission): ?>
                        <li>
                            <i class="fas fa-percentage"></i>
                            <span style="color: #27ae60; font-weight: bold;">Own Commission Enabled</span>
                        </li>
                        <?php endif; ?>
                        <li>
                            <i class="fas fa-crown"></i>
                            <span>VIP Support</span>
                        </li>
                        <li>
                            <i class="fas fa-chart-line"></i>
                            <span>Daily Reports</span>
                        </li>
                        <li>
                            <i class="fas fa-user-tie"></i>
                            <span>Dedicated Manager</span>
                        </li>
                    </ul>
                    
                    <div class="payment-methods">
                        <h4><i class="fas fa-credit-card"></i> Payment Methods</h4>
                        <div class="payment-options">
                            <div class="payment-option">
                                <i class="fas fa-mobile-alt"></i>
                                CBE Birr
                            </div>
                            <div class="payment-option">
                                <i class="fas fa-university"></i>
                                Bank Transfer
                            </div>
                            <div class="payment-option">
                                <i class="fas fa-wallet"></i>
                                TeleBirr
                            </div>
                            <div class="payment-option">
                                <i class="fas fa-money-bill-wave"></i>
                                M-Birr
                            </div>
                        </div>
                    </div>
                    
                    <a href="product-details.php?id=<?php echo $product['id']; ?>" class="get-started-btn">
                        Get Started <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
