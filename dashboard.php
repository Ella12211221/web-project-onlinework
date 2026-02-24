<?php
// Start session at the very beginning
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

try {
    $pdo = new PDO("mysql:host=localhost;dbname=concordial_nexus;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get comprehensive statistics with error handling
    $stats = [
        'total_users' => $pdo->query("SELECT COUNT(*) FROM users WHERE user_type = 'user'")->fetchColumn(),
        'pending_users' => $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'pending'")->fetchColumn(),
        'active_users' => $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn(),
        'total_admins' => $pdo->query("SELECT COUNT(*) FROM users WHERE user_type = 'admin'")->fetchColumn(),
        'total_balance' => $pdo->query("SELECT COALESCE(SUM(account_balance), 0) FROM users")->fetchColumn(),
        'total_transactions' => $pdo->query("SELECT COUNT(*) FROM transactions")->fetchColumn(),
        'pending_transactions' => $pdo->query("SELECT COUNT(*) FROM transactions WHERE status = 'pending'")->fetchColumn(),
        'completed_transactions' => $pdo->query("SELECT COUNT(*) FROM transactions WHERE status = 'completed'")->fetchColumn()
    ];
    
    // Check if payment_transactions table exists and add withdrawal stats
    try {
        $table_check = $pdo->query("SHOW TABLES LIKE 'payment_transactions'")->fetch();
        if ($table_check) {
            $stats['pending_withdrawals'] = $pdo->query("SELECT COUNT(*) FROM payment_transactions WHERE payment_method = 'withdrawal_request' AND status = 'pending'")->fetchColumn();
            $stats['total_withdrawal_amount'] = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM payment_transactions WHERE payment_method = 'withdrawal_request' AND status = 'pending'")->fetchColumn();
        } else {
            $stats['pending_withdrawals'] = 0;
            $stats['total_withdrawal_amount'] = 0;
        }
    } catch(PDOException $e) {
        $stats['pending_withdrawals'] = 0;
        $stats['total_withdrawal_amount'] = 0;
    }
    
    // Get recent activities
    $recent_users = $pdo->query("SELECT * FROM users WHERE user_type = 'user' ORDER BY created_at DESC LIMIT 5")->fetchAll();
    $recent_transactions = $pdo->query("
        SELECT t.*, u.full_name, u.email 
        FROM transactions t 
        JOIN users u ON t.user_id = u.id 
        ORDER BY t.created_at DESC 
        LIMIT 5
    ")->fetchAll();
    
    // Get pending withdrawal requests with error handling
    $pending_withdrawals = [];
    try {
        $table_check = $pdo->query("SHOW TABLES LIKE 'payment_transactions'")->fetch();
        if ($table_check) {
            $pending_withdrawals = $pdo->query("
                SELECT pt.*, u.full_name, u.email, u.first_name, u.last_name
                FROM payment_transactions pt
                JOIN users u ON pt.user_id = u.id
                WHERE pt.payment_method = 'withdrawal_request' AND pt.status = 'pending'
                ORDER BY pt.created_at DESC
                LIMIT 5
            ")->fetchAll();
        }
    } catch(PDOException $e) {
        $pending_withdrawals = [];
    }
    
    // Get invitation codes statistics
    $invitation_stats = $pdo->query("SELECT COUNT(*) FROM invitation_codes WHERE is_active = 1")->fetchColumn();
    
    // Get admin user info for referral link
    $admin_user = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $admin_user->execute([$_SESSION['user_id']]);
    $user = $admin_user->fetch();
    
    // Get notification counts for all pending items
    $notification_counts = [
        'pending_users' => $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'pending'")->fetchColumn(),
        'pending_deposits' => $pdo->query("SELECT COUNT(*) FROM deposits WHERE status = 'pending'")->fetchColumn(),
        'pending_withdrawals' => 0
    ];
    
    // Check if withdrawals table exists
    $withdrawals_table_check = $pdo->query("SHOW TABLES LIKE 'withdrawals'")->fetch();
    if ($withdrawals_table_check) {
        $notification_counts['pending_withdrawals'] = $pdo->query("SELECT COUNT(*) FROM withdrawals WHERE status = 'pending'")->fetchColumn();
    }
    
    $notification_counts['total'] = $notification_counts['pending_users'] + $notification_counts['pending_deposits'] + $notification_counts['pending_withdrawals'];
    
} catch(PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
    $stats = array_fill_keys([
        'total_users', 'pending_users', 'active_users', 'total_admins', 
        'total_balance', 'total_transactions', 'pending_transactions', 
        'completed_transactions', 'pending_withdrawals', 'total_withdrawal_amount'
    ], 0);
    $recent_users = [];
    $recent_transactions = [];
    $pending_withdrawals = [];
    $invitation_stats = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Concordial Nexus</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }
        .transport-header {
            background: linear-gradient(135deg, #4a90e2 0%, #357abd 100%);
            color: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .transport-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .transport-logo {
            font-size: 1.5rem;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .transport-menu {
            display: flex;
            gap: 2rem;
            list-style: none;
        }
        .transport-menu a {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            transition: background 0.3s;
        }
        .transport-menu a:hover, .transport-menu a.active {
            background: rgba(255,255,255,0.2);
        }
        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        .dashboard-welcome {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .welcome-icon {
            background: linear-gradient(135deg, #4a90e2, #357abd);
            color: white;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
        }
        .stat-card.blue::before { background: #4a90e2; }
        .stat-card.green::before { background: #27ae60; }
        .stat-card.orange::before { background: #f39c12; }
        .stat-card.red::before { background: #e74c3c; }
        .stat-card.purple::before { background: #9b59b6; }
        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        .stat-title {
            font-size: 0.9rem;
            color: #666;
            text-transform: uppercase;
            font-weight: 600;
        }
        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
        }
        .stat-icon.blue { background: #4a90e2; }
        .stat-icon.green { background: #27ae60; }
        .stat-icon.orange { background: #f39c12; }
        .stat-icon.red { background: #e74c3c; }
        .stat-icon.purple { background: #9b59b6; }
        .stat-value {
            font-size: 2.5rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 0.5rem;
        }
        .stat-subtitle {
            font-size: 0.8rem;
            color: #999;
        }
        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }
        .content-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }
        .card-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #333;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        .table th,
        .table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }
        .badge {
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .badge.success {
            background: #d4edda;
            color: #155724;
        }
        .badge.warning {
            background: #fff3cd;
            color: #856404;
        }
        .badge.danger {
            background: #f8d7da;
            color: #721c24;
        }
        .btn {
            background: #4a90e2;
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            font-weight: 600;
            transition: background 0.3s;
        }
        .btn:hover {
            background: #357abd;
        }
        .btn.success {
            background: #27ae60;
        }
        .btn.success:hover {
            background: #219a52;
        }
        @media (max-width: 768px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
            .transport-menu {
                display: none;
            }
            .dashboard-container {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="transport-header">
        <nav class="transport-nav">
            <div class="transport-logo">
                <i class="fas fa-shield-alt"></i>
                Admin Panel - Concordial Nexus
            </div>
            <ul class="transport-menu">
                <li><a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li>
                    <a href="notifications.php" style="position: relative;">
                        <i class="fas fa-bell"></i> Notifications
                        <?php if ($notification_counts['total'] > 0): ?>
                            <span style="position: absolute; top: -8px; right: -8px; background: #e74c3c; color: white; border-radius: 50%; width: 22px; height: 22px; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: bold; box-shadow: 0 2px 5px rgba(0,0,0,0.3);">
                                <?php echo $notification_counts['total']; ?>
                            </span>
                        <?php endif; ?>
                    </a>
                </li>
                <li><a href="products.php" style="background: rgba(255,255,255,0.15); font-weight: 700;"><i class="fas fa-box"></i> Products</a></li>
                <li><a href="users.php"><i class="fas fa-users"></i> Users</a></li>
                <li><a href="deposits.php"><i class="fas fa-money-bill-wave"></i> Deposits</a></li>
                <li><a href="transactions.php"><i class="fas fa-exchange-alt"></i> Transactions</a></li>
                <li><a href="invitations.php"><i class="fas fa-ticket-alt"></i> Invitations</a></li>
                <li><a href="profile.php"><i class="fas fa-user-shield"></i> Profile</a></li>
                <li><a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>
    </header>

    <!-- Main Content -->
    <div class="dashboard-container">
        <!-- Welcome Section -->
        <div class="dashboard-welcome">
            <div class="welcome-icon">
                <i class="fas fa-tachometer-alt"></i>
            </div>
            <div>
                <h2>Admin Dashboard</h2>
                <p>Welcome back, Administrator</p>
            </div>
        </div>

        <!-- Notification Center Alert -->
        <?php if ($notification_counts['total'] > 0): ?>
        <a href="notifications.php" style="text-decoration: none; display: block; background: linear-gradient(135deg, #ff6b6b, #ee5a6f); border-radius: 20px; padding: 2rem; margin-bottom: 2rem; box-shadow: 0 15px 35px rgba(255, 107, 107, 0.4); transition: transform 0.3s, box-shadow 0.3s; animation: pulse 2s infinite;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 20px 45px rgba(255, 107, 107, 0.5)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 15px 35px rgba(255, 107, 107, 0.4)';">
            <style>
                @keyframes pulse {
                    0%, 100% { box-shadow: 0 15px 35px rgba(255, 107, 107, 0.4); }
                    50% { box-shadow: 0 15px 45px rgba(255, 107, 107, 0.6); }
                }
            </style>
            <div style="display: flex; align-items: center; gap: 2rem;">
                <div style="background: rgba(255, 255, 255, 0.2); width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 3rem; position: relative;">
                    <i class="fas fa-bell" style="color: white;"></i>
                    <span style="position: absolute; top: 5px; right: 5px; background: white; color: #e74c3c; border-radius: 50%; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; font-size: 0.9rem; font-weight: bold; box-shadow: 0 2px 10px rgba(0,0,0,0.3);">
                        <?php echo $notification_counts['total']; ?>
                    </span>
                </div>
                <div style="flex: 1;">
                    <h2 style="color: white; font-size: 2rem; margin: 0 0 0.5rem 0; font-weight: 700;">
                        <i class="fas fa-exclamation-circle"></i> Action Required!
                    </h2>
                    <p style="color: rgba(255, 255, 255, 0.95); margin: 0 0 1rem 0; font-size: 1.2rem;">
                        You have <strong><?php echo $notification_counts['total']; ?> pending approval<?php echo $notification_counts['total'] > 1 ? 's' : ''; ?></strong> waiting for your review
                    </p>
                    <div style="display: flex; gap: 1.5rem; flex-wrap: wrap;">
                        <?php if ($notification_counts['pending_users'] > 0): ?>
                            <div style="background: rgba(255, 255, 255, 0.2); padding: 0.5rem 1rem; border-radius: 10px;">
                                <i class="fas fa-user-plus"></i> <strong><?php echo $notification_counts['pending_users']; ?></strong> User<?php echo $notification_counts['pending_users'] > 1 ? 's' : ''; ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($notification_counts['pending_deposits'] > 0): ?>
                            <div style="background: rgba(255, 255, 255, 0.2); padding: 0.5rem 1rem; border-radius: 10px;">
                                <i class="fas fa-money-bill-wave"></i> <strong><?php echo $notification_counts['pending_deposits']; ?></strong> Deposit<?php echo $notification_counts['pending_deposits'] > 1 ? 's' : ''; ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($notification_counts['pending_withdrawals'] > 0): ?>
                            <div style="background: rgba(255, 255, 255, 0.2); padding: 0.5rem 1rem; border-radius: 10px;">
                                <i class="fas fa-hand-holding-usd"></i> <strong><?php echo $notification_counts['pending_withdrawals']; ?></strong> Withdrawal<?php echo $notification_counts['pending_withdrawals'] > 1 ? 's' : ''; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div style="color: white; font-size: 2.5rem;">
                    <i class="fas fa-arrow-circle-right"></i>
                </div>
            </div>
        </a>
        <?php endif; ?>

        <!-- Investment & Payment Information -->
        <div style="background: white; border-radius: 10px; padding: 2rem; margin-bottom: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <h3 style="color: #2c3e50; margin-bottom: 1.5rem; text-align: center; font-size: 1.4rem;">
                <i class="fas fa-coins"></i> Investment Packages & Payment Methods
            </h3>
            
            <!-- Investment Levels Quick View -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                <!-- Regular Levels -->
                <div style="background: linear-gradient(135deg, #e8f4fd, #f0f8ff); border: 2px solid #4a90e2; border-radius: 12px; padding: 1.5rem;">
                    <h4 style="color: #2c5aa0; margin-bottom: 1rem; text-align: center;">
                        🥉 Regular Levels (9 packages)
                    </h4>
                    <div style="font-size: 0.9rem; color: #2c3e50; line-height: 1.6;">
                        <p><strong>Level 1:</strong> Br1,000 - Br3,000 (15%-20% returns, 30 days)</p>
                        <p><strong>Level 2:</strong> Br4,000 - Br8,000 (22%-28% returns, 21 days)</p>
                        <p><strong>Level 3:</strong> Br10,000 - Br16,000 (30%-35% returns, 14 days)</p>
                    </div>
                </div>
                
                <!-- Premium Levels -->
                <div style="background: linear-gradient(135deg, #fff8e1, #fffef7); border: 2px solid #f39c12; border-radius: 12px; padding: 1.5rem;">
                    <h4 style="color: #e67e22; margin-bottom: 1rem; text-align: center;">
                        🥈 Premium Levels (6 packages)
                    </h4>
                    <div style="font-size: 0.9rem; color: #2c3e50; line-height: 1.6;">
                        <p><strong>Premium 1:</strong> Br20,000 - Br30,000 (40%-45% returns, 10 days)</p>
                        <p><strong>Premium 2:</strong> Br40,000 - Br60,000 (50%-60% returns, 7 days)</p>
                        <p style="color: #e67e22; font-weight: bold;">Higher returns, shorter duration</p>
                    </div>
                </div>
                
                <!-- Advanced Premium -->
                <div style="background: linear-gradient(135deg, #ffebee, #fff5f5); border: 2px solid #e74c3c; border-radius: 12px; padding: 1.5rem;">
                    <h4 style="color: #c0392b; margin-bottom: 1rem; text-align: center;">
                        🥇 Advanced Premium (3 packages)
                    </h4>
                    <div style="font-size: 0.9rem; color: #2c3e50; line-height: 1.6;">
                        <p><strong>Ultra High:</strong> Br100,000+ (70%-100% returns)</p>
                        <p><strong>Duration:</strong> 3-5 days only</p>
                        <p style="color: #c0392b; font-weight: bold;">Maximum returns, minimum time</p>
                    </div>
                </div>
            </div>
            
            <!-- Payment Methods -->
            <div style="background: #f8f9fa; border-radius: 10px; padding: 1.5rem;">
                <h4 style="color: #2c3e50; margin-bottom: 1rem; text-align: center;">
                    💳 Available Payment Methods
                </h4>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                    <div style="background: white; padding: 1rem; border-radius: 8px; border-left: 4px solid #27ae60;">
                        <h5 style="color: #27ae60; margin-bottom: 0.5rem;">📱 Mobile Banking</h5>
                        <p style="font-size: 0.85rem; color: #666; margin: 0;">CBE Birr, M-Birr, HelloCash, Amole | Instant | Free | Br50,000/day</p>
                    </div>
                    <div style="background: white; padding: 1rem; border-radius: 8px; border-left: 4px solid #4a90e2;">
                        <h5 style="color: #4a90e2; margin-bottom: 0.5rem;">🏦 Bank Transfer</h5>
                        <p style="font-size: 0.85rem; color: #666; margin: 0;">CBE, Dashen, Awash, BOA | 1-2 hours | 0.5% fee | Br500,000/day</p>
                    </div>
                    <div style="background: white; padding: 1rem; border-radius: 8px; border-left: 4px solid #f39c12;">
                        <h5 style="color: #f39c12; margin-bottom: 0.5rem;">💳 Digital Wallet</h5>
                        <p style="font-size: 0.85rem; color: #666; margin: 0;">Platform Balance | Instant | Free | No limit</p>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div style="text-align: center; margin-top: 1.5rem;">
                <a href="../setup-investment-system.php" style="background: #27ae60; color: white; padding: 0.75rem 1.5rem; border-radius: 8px; text-decoration: none; margin: 0 0.5rem; font-weight: 600;">
                    <i class="fas fa-cog"></i> Setup Investment System
                </a>
                <a href="products.php" style="background: #4a90e2; color: white; padding: 0.75rem 1.5rem; border-radius: 8px; text-decoration: none; margin: 0 0.5rem; font-weight: 600;">
                    <i class="fas fa-box"></i> Manage Products
                </a>
                <a href="transactions.php" style="background: #f39c12; color: white; padding: 0.75rem 1.5rem; border-radius: 8px; text-decoration: none; margin: 0 0.5rem; font-weight: 600;">
                    <i class="fas fa-exchange-alt"></i> All Transactions
                </a>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card blue">
                <div class="stat-header">
                    <div class="stat-title">Total Users</div>
                    <div class="stat-icon blue">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo $stats['total_users']; ?></div>
                <div class="stat-subtitle">Registered users</div>
            </div>

            <div class="stat-card green">
                <div class="stat-header">
                    <div class="stat-title">Active Users</div>
                    <div class="stat-icon green">
                        <i class="fas fa-user-check"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo $stats['active_users']; ?></div>
                <div class="stat-subtitle">Approved accounts</div>
            </div>

            <div class="stat-card orange">
                <div class="stat-header">
                    <div class="stat-title">Pending Approvals</div>
                    <div class="stat-icon orange">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo $stats['pending_users']; ?></div>
                <div class="stat-subtitle">Awaiting approval</div>
            </div>

            <div class="stat-card red">
                <div class="stat-header">
                    <div class="stat-title">Pending Withdrawals</div>
                    <div class="stat-icon red">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo $stats['pending_withdrawals']; ?></div>
                <div class="stat-subtitle">Br<?php echo number_format($stats['total_withdrawal_amount'], 0); ?> pending</div>
            </div>

            <div class="stat-card purple">
                <div class="stat-header">
                    <div class="stat-title">Total Transactions</div>
                    <div class="stat-icon purple">
                        <i class="fas fa-exchange-alt"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo $stats['total_transactions']; ?></div>
                <div class="stat-subtitle">All transactions</div>
            </div>
        </div>

        <!-- Content Grid -->
        <div class="content-grid">
            <!-- Recent Users -->
            <div class="content-card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-user-plus"></i> Recent Users</h3>
                    <a href="users.php" class="btn">View All</a>
                </div>
                
                <?php if (empty($recent_users)): ?>
                    <div style="text-align: center; color: #999; padding: 2rem;">
                        <i class="fas fa-users" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                        <p>No users registered yet</p>
                    </div>
                <?php else: ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_users as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <span class="badge <?php echo $user['status'] === 'active' ? 'success' : ($user['status'] === 'pending' ? 'warning' : 'danger'); ?>">
                                            <?php echo $user['status']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <!-- Recent Transactions -->
            <div class="content-card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-exchange-alt"></i> Recent Transactions</h3>
                    <a href="transactions.php" class="btn success">View All</a>
                </div>
                
                <?php if (empty($recent_transactions)): ?>
                    <div style="text-align: center; color: #999; padding: 2rem;">
                        <i class="fas fa-exchange-alt" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                        <p>No transactions yet</p>
                    </div>
                <?php else: ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Type</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_transactions as $transaction): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($transaction['full_name']); ?></td>
                                    <td><?php echo ucfirst($transaction['transaction_type']); ?></td>
                                    <td>Br<?php echo number_format($transaction['amount'], 2); ?></td>
                                    <td>
                                        <span class="badge <?php echo $transaction['status'] === 'completed' ? 'success' : ($transaction['status'] === 'pending' ? 'warning' : 'danger'); ?>">
                                            <?php echo $transaction['status']; ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <!-- Pending Withdrawals -->
            <div class="content-card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-money-bill-wave"></i> Pending Withdrawals</h3>
                    <a href="withdrawal-management.php" class="btn" style="background: #e74c3c;">Manage All</a>
                </div>
                
                <?php if (empty($pending_withdrawals)): ?>
                    <div style="text-align: center; color: #999; padding: 2rem;">
                        <i class="fas fa-money-bill-wave" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                        <p>No pending withdrawals</p>
                    </div>
                <?php else: ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Amount</th>
                                <th>Bank</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pending_withdrawals as $withdrawal): ?>
                                <tr>
                                    <td>
                                        <div style="font-weight: 600;">
                                            <?php 
                                            if (!empty($withdrawal['first_name']) && !empty($withdrawal['last_name'])) {
                                                echo htmlspecialchars($withdrawal['first_name'] . ' ' . $withdrawal['last_name']);
                                            } else {
                                                echo htmlspecialchars($withdrawal['full_name']);
                                            }
                                            ?>
                                        </div>
                                        <div style="font-size: 0.8rem; color: #666;">
                                            <?php echo htmlspecialchars($withdrawal['email']); ?>
                                        </div>
                                    </td>
                                    <td style="font-weight: 600; color: #e74c3c;">
                                        Br<?php echo number_format($withdrawal['amount'], 2); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($withdrawal['bank_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo date('M j, H:i', strtotime($withdrawal['created_at'])); ?></td>
                                    <td>
                                        <a href="withdrawal-management.php?filter=pending#withdrawal-<?php echo $withdrawal['id']; ?>" 
                                           class="btn" style="background: #f39c12; padding: 0.5rem 1rem; font-size: 0.8rem;">
                                            <i class="fas fa-eye"></i> Review
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Actions -->
        
        <!-- Featured Management Cards -->
        <div style="margin-top: 2rem; margin-bottom: 2rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 2rem;">
            <a href="deposits.php" style="text-decoration: none; display: block; background: linear-gradient(135deg, #27ae60, #229954); border-radius: 20px; padding: 3rem 2rem; box-shadow: 0 15px 35px rgba(39, 174, 96, 0.4); transition: transform 0.3s, box-shadow 0.3s;" onmouseover="this.style.transform='translateY(-10px)'; this.style.boxShadow='0 20px 45px rgba(39, 174, 96, 0.5)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 15px 35px rgba(39, 174, 96, 0.4)';">
                <div style="display: flex; align-items: center; gap: 2rem;">
                    <div style="background: rgba(255, 255, 255, 0.2); width: 80px; height: 80px; border-radius: 20px; display: flex; align-items: center; justify-content: center; font-size: 3rem;">
                        💵
                    </div>
                    <div style="flex: 1;">
                        <h2 style="color: white; font-size: 2rem; margin: 0 0 0.5rem 0; font-weight: 700;">Deposit Management</h2>
                        <p style="color: rgba(255, 255, 255, 0.9); margin: 0; font-size: 1.1rem;">Approve or reject deposit requests</p>
                        <?php 
                        $pending_deposits = 0;
                        try {
                            $pending_deposits = $pdo->query("SELECT COUNT(*) FROM deposits WHERE status = 'pending'")->fetchColumn();
                        } catch(PDOException $e) {}
                        if ($pending_deposits > 0): 
                        ?>
                            <div style="margin-top: 1rem; background: rgba(255, 255, 255, 0.2); display: inline-block; padding: 0.5rem 1rem; border-radius: 10px;">
                                <span style="color: white; font-weight: 600; font-size: 1.2rem;"><?php echo $pending_deposits; ?> Pending</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div style="color: white; font-size: 2rem;">
                        <i class="fas fa-arrow-right"></i>
                    </div>
                </div>
            </a>
            
            <a href="withdrawal-management.php" style="text-decoration: none; display: block; background: linear-gradient(135deg, #e74c3c, #c0392b); border-radius: 20px; padding: 3rem 2rem; box-shadow: 0 15px 35px rgba(231, 76, 60, 0.4); transition: transform 0.3s, box-shadow 0.3s;" onmouseover="this.style.transform='translateY(-10px)'; this.style.boxShadow='0 20px 45px rgba(231, 76, 60, 0.5)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 15px 35px rgba(231, 76, 60, 0.4)';">
                <div style="display: flex; align-items: center; gap: 2rem;">
                    <div style="background: rgba(255, 255, 255, 0.2); width: 80px; height: 80px; border-radius: 20px; display: flex; align-items: center; justify-content: center; font-size: 3rem;">
                        💰
                    </div>
                    <div style="flex: 1;">
                        <h2 style="color: white; font-size: 2rem; margin: 0 0 0.5rem 0; font-weight: 700;">Withdrawal Management</h2>
                        <p style="color: rgba(255, 255, 255, 0.9); margin: 0; font-size: 1.1rem;">Manage and approve withdrawal requests</p>
                        <?php if ($stats['pending_withdrawals'] > 0): ?>
                            <div style="margin-top: 1rem; background: rgba(255, 255, 255, 0.2); display: inline-block; padding: 0.5rem 1rem; border-radius: 10px;">
                                <span style="color: white; font-weight: 600; font-size: 1.2rem;"><?php echo $stats['pending_withdrawals']; ?> Pending</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div style="color: white; font-size: 2rem;">
                        <i class="fas fa-arrow-right"></i>
                    </div>
                </div>
            </a>
            
            <a href="payment-transactions.php" style="text-decoration: none; display: block; background: linear-gradient(135deg, #f39c12, #e67e22); border-radius: 20px; padding: 3rem 2rem; box-shadow: 0 15px 35px rgba(243, 156, 18, 0.4); transition: transform 0.3s, box-shadow 0.3s;" onmouseover="this.style.transform='translateY(-10px)'; this.style.boxShadow='0 20px 45px rgba(243, 156, 18, 0.5)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 15px 35px rgba(243, 156, 18, 0.4)';">
                <div style="display: flex; align-items: center; gap: 2rem;">
                    <div style="background: rgba(255, 255, 255, 0.2); width: 80px; height: 80px; border-radius: 20px; display: flex; align-items: center; justify-content: center; font-size: 3rem;">
                        💳
                    </div>
                    <div style="flex: 1;">
                        <h2 style="color: white; font-size: 2rem; margin: 0 0 0.5rem 0; font-weight: 700;">Payment Transactions</h2>
                        <p style="color: rgba(255, 255, 255, 0.9); margin: 0; font-size: 1.1rem;">View and manage all payment transactions</p>
                        <?php if ($stats['pending_transactions'] > 0): ?>
                            <div style="margin-top: 1rem; background: rgba(255, 255, 255, 0.2); display: inline-block; padding: 0.5rem 1rem; border-radius: 10px;">
                                <span style="color: white; font-weight: 600; font-size: 1.2rem;"><?php echo $stats['pending_transactions']; ?> Pending</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div style="color: white; font-size: 2rem;">
                        <i class="fas fa-arrow-right"></i>
                    </div>
                </div>
            </a>
        </div>
        
        <!-- Other Quick Actions -->
        <div style="margin-top: 2rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
            <a href="notifications.php" class="btn" style="justify-content: center; padding: 1rem; background: linear-gradient(135deg, #ff6b6b, #ee5a6f); position: relative;">
                <i class="fas fa-bell"></i> Notification Center
                <?php if ($notification_counts['total'] > 0): ?>
                    <span style="position: absolute; top: -8px; right: -8px; background: white; color: #e74c3c; border-radius: 50%; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; font-weight: bold; box-shadow: 0 2px 5px rgba(0,0,0,0.3);">
                        <?php echo $notification_counts['total']; ?>
                    </span>
                <?php endif; ?>
            </a>
            <a href="users.php" class="btn" style="justify-content: center; padding: 1rem;">
                <i class="fas fa-users"></i> Manage Users
            </a>
            <a href="transactions.php" class="btn success" style="justify-content: center; padding: 1rem;">
                <i class="fas fa-exchange-alt"></i> View Transactions
            </a>
            <a href="invitations.php" class="btn" style="justify-content: center; padding: 1rem; background: #f39c12;">
                <i class="fas fa-ticket-alt"></i> Manage Invitations
            </a>
            <a href="../database/test-system.php" class="btn" style="justify-content: center; padding: 1rem; background: #9b59b6;">
                <i class="fas fa-cog"></i> System Status
            </a>
        </div>
    </div>
</body>
</html>