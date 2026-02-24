<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$product_id = $_GET['id'] ?? null;
if (!$product_id) {
    header('Location: deposit.php');
    exit;
}

try {
    $pdo = new PDO("mysql:host=localhost;dbname=concordial_nexus", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get product details
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        header('Location: deposit.php');
        exit;
    }
    
    // Get user info
    $user_stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $user_stmt->execute([$_SESSION['user_id']]);
    $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Calculate profit
    $profit = ($product['min_amount'] * $product['return_percentage']) / 100;
    $total_return = $product['min_amount'] + $profit;
    
    // Get duration in months/years
    $days = $product['duration_days'];
    if ($days >= 365) {
        $duration_text = round($days / 365, 1) . ' Year' . ($days > 365 ? 's' : '');
    } elseif ($days >= 30) {
        $duration_text = round($days / 30) . ' Months';
    } else {
        $duration_text = $days . ' Days';
    }
    
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($product['name']); ?> - Concordial Nexus</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea, #764ba2);
            min-height: 100vh;
            padding: 20px;
        }
        .container { max-width: 900px; margin: 0 auto; }
        .header {
            background: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .btn {
            padding: 10px 20px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            display: inline-block;
        }
        .product-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .product-badge {
            display: inline-block;
            padding: 8px 20px;
            border-radius: 20px;
            font-weight: bold;
            margin-bottom: 20px;
            text-transform: uppercase;
            font-size: 0.9rem;
        }
        .badge-regular { background: #3498db; color: white; }
        .badge-premium { background: #9b59b6; color: white; }
        .badge-vip { background: #e74c3c; color: white; }
        .product-title {
            font-size: 2.5rem;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        .product-amount {
            font-size: 3rem;
            color: #667eea;
            font-weight: bold;
            margin: 20px 0;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin: 30px 0;
        }
        .stat-box {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }
        .stat-label {
            color: #7f8c8d;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }
        .stat-value {
            font-size: 1.8rem;
            font-weight: bold;
            color: #2c3e50;
        }
        .features {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 15px;
            margin: 30px 0;
        }
        .features h3 {
            color: #2c3e50;
            margin-bottom: 20px;
        }
        .feature-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        .feature-item:last-child { border-bottom: none; }
        .feature-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        .invest-btn {
            width: 100%;
            padding: 20px;
            background: linear-gradient(135deg, #27ae60, #229954);
            color: white;
            border: none;
            border-radius: 15px;
            font-size: 1.3rem;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            display: block;
            text-align: center;
            margin-top: 30px;
        }
        .invest-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(39, 174, 96, 0.4);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2 style="margin: 0; color: #2c3e50;">Investment Details</h2>
            <a href="deposit.php" class="btn"><i class="fas fa-arrow-left"></i> Back to All Products</a>
        </div>
        
        <div class="product-card">
            <span class="product-badge badge-<?php echo $product['category']; ?>">
                <?php echo ucfirst($product['category']); ?> Package
            </span>
            
            <h1 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h1>
            
            <div class="product-amount">
                Br <?php echo number_format($product['min_amount']); ?>
            </div>
            
            <div class="stats-grid">
                <div class="stat-box">
                    <div class="stat-label">Return Rate</div>
                    <div class="stat-value" style="color: #27ae60;">
                        <?php echo $product['return_percentage']; ?>%
                    </div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">Duration</div>
                    <div class="stat-value" style="color: #3498db;">
                        <?php echo $duration_text; ?>
                    </div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">Total Profit</div>
                    <div class="stat-value" style="color: #e74c3c;">
                        Br <?php echo number_format($profit); ?>
                    </div>
                </div>
            </div>
            
            <div class="features">
                <h3><i class="fas fa-star"></i> Package Features</h3>
                
                <div class="feature-item">
                    <div class="feature-icon"><i class="fas fa-money-bill-wave"></i></div>
                    <div>
                        <strong>Investment Amount:</strong> Br <?php echo number_format($product['min_amount']); ?>
                    </div>
                </div>
                
                <div class="feature-item">
                    <div class="feature-icon"><i class="fas fa-chart-line"></i></div>
                    <div>
                        <strong>Expected Return:</strong> Br <?php echo number_format($total_return); ?> 
                        (<?php echo $product['return_percentage']; ?>% profit)
                    </div>
                </div>
                
                <div class="feature-item">
                    <div class="feature-icon"><i class="fas fa-calendar-alt"></i></div>
                    <div>
                        <strong>Investment Period:</strong> <?php echo $duration_text; ?> 
                        (<?php echo $product['duration_days']; ?> days)
                    </div>
                </div>
                
                <div class="feature-item">
                    <div class="feature-icon"><i class="fas fa-shield-alt"></i></div>
                    <div>
                        <strong>Security:</strong> 100% Secure & Guaranteed Returns
                    </div>
                </div>
                
                <div class="feature-item">
                    <div class="feature-icon"><i class="fas fa-users"></i></div>
                    <div>
                        <strong>Referral Bonus:</strong> Earn 10% commission + Br50 bonus
                    </div>
                </div>
            </div>
            
            <div style="background: #e8f5e9; padding: 20px; border-radius: 10px; margin: 20px 0;">
                <h4 style="color: #27ae60; margin-bottom: 10px;">
                    <i class="fas fa-calculator"></i> Investment Breakdown
                </h4>
                <p style="color: #2c3e50; line-height: 1.8;">
                    <strong>Initial Investment:</strong> Br <?php echo number_format($product['min_amount']); ?><br>
                    <strong>Profit After <?php echo $duration_text; ?>:</strong> Br <?php echo number_format($profit); ?><br>
                    <strong>Total Return:</strong> Br <?php echo number_format($total_return); ?>
                </p>
            </div>
            
            <a href="deposit.php?product_id=<?php echo $product['id']; ?>" class="invest-btn">
                <i class="fas fa-rocket"></i> Invest Now - Br <?php echo number_format($product['min_amount']); ?>
            </a>
            
            <p style="text-align: center; color: #7f8c8d; margin-top: 20px; font-size: 0.9rem;">
                <i class="fas fa-info-circle"></i> Your investment is secure and guaranteed. 
                Returns will be credited after <?php echo $duration_text; ?>.
            </p>
        </div>
    </div>
</body>
</html>
