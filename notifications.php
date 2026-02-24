<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

$message = '';
$error = '';

try {
    $pdo = new PDO("mysql:host=localhost;dbname=concordial_nexus;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Handle quick actions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action_type = $_POST['action_type'] ?? '';
        $item_id = (int)($_POST['item_id'] ?? 0);
        $action = $_POST['action'] ?? '';
        $notes = $_POST['notes'] ?? '';
        
        if ($action_type === 'user' && $item_id > 0) {
            if ($action === 'approve') {
                $pdo->prepare("UPDATE users SET status = 'active', approved_by = ?, approved_at = NOW() WHERE id = ?")->execute([$_SESSION['user_id'], $item_id]);
                $message = "User approved successfully!";
            } elseif ($action === 'reject') {
                $pdo->prepare("UPDATE users SET status = 'rejected', approved_by = ?, approved_at = NOW() WHERE id = ?")->execute([$_SESSION['user_id'], $item_id]);
                $message = "User rejected.";
            }
        } elseif ($action_type === 'deposit' && $item_id > 0) {
            $deposit = $pdo->prepare("SELECT * FROM deposits WHERE id = ?")->execute([$item_id]) ? $pdo->query("SELECT * FROM deposits WHERE id = $item_id")->fetch() : null;
            
            if ($deposit && $action === 'approve') {
                $pdo->beginTransaction();
                try {
                    $pdo->prepare("UPDATE deposits SET status = 'approved', admin_notes = ?, approved_by = ?, approved_at = NOW() WHERE id = ?")->execute([$notes, $_SESSION['user_id'], $item_id]);
                    $pdo->prepare("UPDATE users SET account_balance = account_balance + ? WHERE id = ?")->execute([$deposit['amount'], $deposit['user_id']]);
                    
                    $ref = 'TXN-' . date('Ymd') . '-' . strtoupper(substr(md5($item_id . time()), 0, 6));
                    $pdo->prepare("INSERT INTO transactions (user_id, transaction_type, amount, reference_number, description, status, created_at) VALUES (?, 'deposit', ?, ?, ?, 'completed', NOW())")->execute([$deposit['user_id'], $deposit['amount'], $ref, "Deposit approved"]);
                    
                    $pdo->commit();
                    $message = "Deposit approved! Br" . number_format($deposit['amount'], 2) . " added to user wallet.";
                } catch (Exception $e) {
                    $pdo->rollback();
                    $error = "Failed: " . $e->getMessage();
                }
            } elseif ($action === 'reject') {
                $pdo->prepare("UPDATE deposits SET status = 'rejected', admin_notes = ?, approved_by = ?, approved_at = NOW() WHERE id = ?")->execute([$notes, $_SESSION['user_id'], $item_id]);
                $message = "Deposit rejected.";
            }
        } elseif ($action_type === 'withdrawal' && $item_id > 0) {
            // Check if withdrawals table exists
            $table_check = $pdo->query("SHOW TABLES LIKE 'withdrawals'")->fetch();
            if ($table_check) {
                $withdrawal = $pdo->query("SELECT * FROM withdrawals WHERE id = $item_id")->fetch();
                
                if ($withdrawal && $action === 'approve') {
                    $pdo->beginTransaction();
                    try {
                        $pdo->prepare("UPDATE withdrawals SET status = 'approved', admin_notes = ?, approved_by = ?, approved_at = NOW() WHERE id = ?")->execute([$notes, $_SESSION['user_id'], $item_id]);
                        
                        $ref = 'WTH-' . date('Ymd') . '-' . strtoupper(substr(md5($item_id . time()), 0, 6));
                        $pdo->prepare("INSERT INTO transactions (user_id, transaction_type, amount, reference_number, description, status, created_at) VALUES (?, 'withdrawal', ?, ?, ?, 'completed', NOW())")->execute([$withdrawal['user_id'], $withdrawal['amount'], $ref, "Withdrawal approved"]);
                        
                        $pdo->commit();
                        $message = "Withdrawal approved! Process payment of Br" . number_format($withdrawal['amount'], 2);
                    } catch (Exception $e) {
                        $pdo->rollback();
                        $error = "Failed: " . $e->getMessage();
                    }
                } elseif ($action === 'reject') {
                    $pdo->beginTransaction();
                    try {
                        $pdo->prepare("UPDATE withdrawals SET status = 'rejected', admin_notes = ?, approved_by = ?, approved_at = NOW() WHERE id = ?")->execute([$notes, $_SESSION['user_id'], $item_id]);
                        $pdo->prepare("UPDATE users SET account_balance = account_balance + ? WHERE id = ?")->execute([$withdrawal['amount'], $withdrawal['user_id']]);
                        $pdo->commit();
                        $message = "Withdrawal rejected. Money refunded to user wallet.";
                    } catch (Exception $e) {
                        $pdo->rollback();
                        $error = "Failed: " . $e->getMessage();
                    }
                }
            }
        }
    }
    
    // Get all pending items
    $pending_users = $pdo->query("SELECT *, 'user' as type FROM users WHERE status = 'pending' ORDER BY created_at DESC")->fetchAll();
    
    $pending_deposits = $pdo->query("
        SELECT d.*, u.full_name, u.email, 'deposit' as type 
        FROM deposits d 
        JOIN users u ON d.user_id = u.id 
        WHERE d.status = 'pending' 
        ORDER BY d.created_at DESC
    ")->fetchAll();
    
    // Check if withdrawals table exists
    $pending_withdrawals = [];
    $table_check = $pdo->query("SHOW TABLES LIKE 'withdrawals'")->fetch();
    if ($table_check) {
        $pending_withdrawals = $pdo->query("
            SELECT w.*, u.full_name, u.email, 'withdrawal' as type 
            FROM withdrawals w 
            JOIN users u ON w.user_id = u.id 
            WHERE w.status = 'pending' 
            ORDER BY w.created_at DESC
        ")->fetchAll();
    }
    
    // Combine all notifications
    $all_notifications = array_merge($pending_users, $pending_deposits, $pending_withdrawals);
    
    // Sort by created_at
    usort($all_notifications, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    
    // Get counts
    $counts = [
        'users' => count($pending_users),
        'deposits' => count($pending_deposits),
        'withdrawals' => count($pending_withdrawals),
        'total' => count($all_notifications)
    ];
    
} catch(PDOException $e) {
    $error = "Database error: " . $e->getMessage();
    $all_notifications = [];
    $counts = ['users' => 0, 'deposits' => 0, 'withdrawals' => 0, 'total' => 0];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Notifications - Concordial Nexus</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 20px; }
        .container { max-width: 1400px; margin: 0 auto; background: rgba(255, 255, 255, 0.98); border-radius: 20px; padding: 30px; box-shadow: 0 20px 40px rgba(0,0,0,0.2); }
        
        .header { text-align: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 2px solid #e9ecef; }
        .header h1 { color: #333; font-size: 2.5rem; margin-bottom: 10px; }
        .header .subtitle { color: #666; font-size: 1.1rem; }
        
        .stats-bar { display: flex; gap: 15px; margin-bottom: 30px; flex-wrap: wrap; }
        .stat-badge { flex: 1; min-width: 150px; background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 20px; border-radius: 15px; text-align: center; box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3); }
        .stat-badge.users { background: linear-gradient(135deg, #f39c12, #e67e22); }
        .stat-badge.deposits { background: linear-gradient(135deg, #27ae60, #229954); }
        .stat-badge.withdrawals { background: linear-gradient(135deg, #e74c3c, #c0392b); }
        .stat-badge .number { font-size: 2.5rem; font-weight: bold; margin-bottom: 5px; }
        .stat-badge .label { font-size: 0.9rem; opacity: 0.9; }
        
        .message { background: #d4edda; color: #155724; padding: 15px; border-radius: 10px; margin-bottom: 20px; border-left: 4px solid #28a745; animation: slideIn 0.3s; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 10px; margin-bottom: 20px; border-left: 4px solid #dc3545; animation: slideIn 0.3s; }
        
        @keyframes slideIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        
        .notification-card { background: white; border-radius: 15px; padding: 25px; margin: 15px 0; box-shadow: 0 5px 15px rgba(0,0,0,0.1); border-left: 5px solid #667eea; transition: transform 0.2s; }
        .notification-card:hover { transform: translateX(5px); box-shadow: 0 8px 25px rgba(0,0,0,0.15); }
        .notification-card.user { border-left-color: #f39c12; }
        .notification-card.deposit { border-left-color: #27ae60; }
        .notification-card.withdrawal { border-left-color: #e74c3c; }
        
        .notification-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .notification-type { display: inline-block; padding: 5px 15px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; text-transform: uppercase; }
        .notification-type.user { background: #fff3cd; color: #856404; }
        .notification-type.deposit { background: #d4edda; color: #155724; }
        .notification-type.withdrawal { background: #f8d7da; color: #721c24; }
        .notification-time { color: #999; font-size: 0.9rem; }
        
        .notification-content { margin: 15px 0; }
        .notification-content .info-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f0f0f0; }
        .notification-content .info-row:last-child { border-bottom: none; }
        .notification-content .label { color: #666; font-weight: 600; }
        .notification-content .value { color: #333; font-weight: bold; }
        
        .notification-actions { display: flex; gap: 10px; margin-top: 15px; padding-top: 15px; border-top: 2px solid #f0f0f0; }
        .notification-actions input[type="text"] { flex: 1; padding: 10px; border: 2px solid #e9ecef; border-radius: 8px; font-size: 0.9rem; }
        .notification-actions input[type="text"]:focus { outline: none; border-color: #667eea; }
        
        .btn { padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; font-size: 0.95rem; font-weight: 600; transition: all 0.2s; }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.2); }
        .btn-approve { background: #28a745; color: white; }
        .btn-reject { background: #dc3545; color: white; }
        .btn-back { background: #6c757d; color: white; padding: 12px 24px; text-decoration: none; display: inline-block; margin-bottom: 20px; }
        
        .empty-state { text-align: center; padding: 60px 20px; color: #666; }
        .empty-state i { font-size: 5rem; color: #28a745; margin-bottom: 20px; opacity: 0.3; }
        .empty-state h3 { font-size: 1.8rem; margin-bottom: 10px; }
        .empty-state p { font-size: 1.1rem; }
        
        .filter-tabs { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
        .filter-tab { padding: 10px 20px; background: #f8f9fa; border: 2px solid #e9ecef; border-radius: 10px; cursor: pointer; font-weight: 600; transition: all 0.2s; }
        .filter-tab:hover { background: #e9ecef; }
        .filter-tab.active { background: #667eea; color: white; border-color: #667eea; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-bell"></i> Admin Notifications Center</h1>
            <p class="subtitle">All pending actions requiring your approval</p>
        </div>
        
        <a href="dashboard.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        
        <?php if ($message): ?>
            <div class="message"><i class="fas fa-check-circle"></i> <?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>
        
        <!-- Statistics Bar -->
        <div class="stats-bar">
            <div class="stat-badge">
                <div class="number"><?php echo $counts['total']; ?></div>
                <div class="label">Total Pending</div>
            </div>
            <div class="stat-badge users">
                <div class="number"><?php echo $counts['users']; ?></div>
                <div class="label">User Registrations</div>
            </div>
            <div class="stat-badge deposits">
                <div class="number"><?php echo $counts['deposits']; ?></div>
                <div class="label">Deposits</div>
            </div>
            <div class="stat-badge withdrawals">
                <div class="number"><?php echo $counts['withdrawals']; ?></div>
                <div class="label">Withdrawals</div>
            </div>
        </div>
        
        <!-- Filter Tabs -->
        <div class="filter-tabs">
            <div class="filter-tab active" onclick="filterNotifications('all')">All (<?php echo $counts['total']; ?>)</div>
            <div class="filter-tab" onclick="filterNotifications('user')">Users (<?php echo $counts['users']; ?>)</div>
            <div class="filter-tab" onclick="filterNotifications('deposit')">Deposits (<?php echo $counts['deposits']; ?>)</div>
            <div class="filter-tab" onclick="filterNotifications('withdrawal')">Withdrawals (<?php echo $counts['withdrawals']; ?>)</div>
        </div>
        
        <!-- Notifications List -->
        <?php if (empty($all_notifications)): ?>
            <div class="empty-state">
                <i class="fas fa-check-circle"></i>
                <h3>All Caught Up!</h3>
                <p>No pending actions at the moment. Great job!</p>
            </div>
        <?php else: ?>
            <?php foreach ($all_notifications as $notification): ?>
                <div class="notification-card <?php echo $notification['type']; ?>" data-type="<?php echo $notification['type']; ?>">
                    <div class="notification-header">
                        <span class="notification-type <?php echo $notification['type']; ?>">
                            <i class="fas fa-<?php echo $notification['type'] === 'user' ? 'user-plus' : ($notification['type'] === 'deposit' ? 'money-bill-wave' : 'hand-holding-usd'); ?>"></i>
                            <?php echo ucfirst($notification['type']); ?> Request
                        </span>
                        <span class="notification-time">
                            <i class="fas fa-clock"></i> <?php echo date('M j, Y H:i', strtotime($notification['created_at'])); ?>
                        </span>
                    </div>
                    
                    <div class="notification-content">
                        <?php if ($notification['type'] === 'user'): ?>
                            <div class="info-row">
                                <span class="label">Full Name:</span>
                                <span class="value"><?php echo htmlspecialchars($notification['full_name']); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="label">Email:</span>
                                <span class="value"><?php echo htmlspecialchars($notification['email']); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="label">Referral Code:</span>
                                <span class="value"><?php echo htmlspecialchars($notification['referral_code'] ?? 'None'); ?></span>
                            </div>
                        <?php elseif ($notification['type'] === 'deposit'): ?>
                            <div class="info-row">
                                <span class="label">User:</span>
                                <span class="value"><?php echo htmlspecialchars($notification['full_name']); ?> (<?php echo htmlspecialchars($notification['email']); ?>)</span>
                            </div>
                            <div class="info-row">
                                <span class="label">Amount:</span>
                                <span class="value" style="color: #28a745; font-size: 1.3rem;">Br<?php echo number_format($notification['amount'], 2); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="label">Payment Reference:</span>
                                <span class="value" style="font-family: monospace; background: #f8f9fa; padding: 5px 10px; border-radius: 5px;"><?php echo htmlspecialchars($notification['payment_reference']); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="label">Payment Method:</span>
                                <span class="value"><?php echo htmlspecialchars($notification['payment_method'] ?? 'N/A'); ?></span>
                            </div>
                        <?php elseif ($notification['type'] === 'withdrawal'): ?>
                            <div class="info-row">
                                <span class="label">User:</span>
                                <span class="value"><?php echo htmlspecialchars($notification['full_name']); ?> (<?php echo htmlspecialchars($notification['email']); ?>)</span>
                            </div>
                            <div class="info-row">
                                <span class="label">Amount:</span>
                                <span class="value" style="color: #dc3545; font-size: 1.3rem;">Br<?php echo number_format($notification['amount'], 2); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="label">Payment Method:</span>
                                <span class="value"><?php echo htmlspecialchars($notification['payment_method'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="label">Account Details:</span>
                                <span class="value"><?php echo htmlspecialchars($notification['account_details'] ?? 'N/A'); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="notification-actions">
                        <form method="POST" style="display: flex; gap: 10px; flex: 1;">
                            <input type="hidden" name="action_type" value="<?php echo $notification['type']; ?>">
                            <input type="hidden" name="item_id" value="<?php echo $notification['id']; ?>">
                            <input type="hidden" name="action" value="approve">
                            <input type="text" name="notes" placeholder="Add notes (optional)...">
                            <button type="submit" class="btn btn-approve" onclick="return confirm('Approve this <?php echo $notification['type']; ?>?')">
                                <i class="fas fa-check"></i> Approve
                            </button>
                        </form>
                        <form method="POST" style="display: flex; gap: 10px;">
                            <input type="hidden" name="action_type" value="<?php echo $notification['type']; ?>">
                            <input type="hidden" name="item_id" value="<?php echo $notification['id']; ?>">
                            <input type="hidden" name="action" value="reject">
                            <input type="text" name="notes" placeholder="Rejection reason..." required>
                            <button type="submit" class="btn btn-reject" onclick="return confirm('Reject this <?php echo $notification['type']; ?>?')">
                                <i class="fas fa-times"></i> Reject
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <script>
        function filterNotifications(type) {
            // Update active tab
            document.querySelectorAll('.filter-tab').forEach(tab => tab.classList.remove('active'));
            event.target.classList.add('active');
            
            // Filter cards
            document.querySelectorAll('.notification-card').forEach(card => {
                if (type === 'all' || card.dataset.type === type) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }
        
        // Auto-refresh every 30 seconds
        setTimeout(() => location.reload(), 30000);
    </script>
</body>
</html>
