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
    
    // Check if withdrawals table exists
    $table_check = $pdo->query("SHOW TABLES LIKE 'withdrawals'")->fetch();
    
    if (!$table_check) {
        // Create withdrawals table
        $pdo->exec("CREATE TABLE IF NOT EXISTS withdrawals (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            amount DECIMAL(15,2) NOT NULL,
            payment_method VARCHAR(50),
            account_details TEXT,
            status ENUM('pending', 'approved', 'rejected', 'completed') DEFAULT 'pending',
            admin_notes TEXT,
            approved_by INT,
            approved_at DATETIME,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id),
            FOREIGN KEY (approved_by) REFERENCES users(id)
        )");
    }
    
    // Handle approve/reject actions
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['withdrawal_id'])) {
        $withdrawal_id = (int)$_POST['withdrawal_id'];
        $action = $_POST['action'];
        $admin_notes = $_POST['admin_notes'] ?? '';
        
        // Get withdrawal details
        $withdrawal_stmt = $pdo->prepare("SELECT * FROM withdrawals WHERE id = ?");
        $withdrawal_stmt->execute([$withdrawal_id]);
        $withdrawal = $withdrawal_stmt->fetch();
        
        if ($withdrawal && $withdrawal['status'] === 'pending') {
            if ($action === 'approve') {
                $pdo->beginTransaction();
                
                try {
                    // Update withdrawal status
                    $update = $pdo->prepare("UPDATE withdrawals SET status = 'approved', admin_notes = ?, approved_by = ?, approved_at = NOW() WHERE id = ?");
                    $update->execute([$admin_notes, $_SESSION['user_id'], $withdrawal_id]);
                    
                    // Create transaction record
                    $ref = 'WTH-' . date('Ymd') . '-' . strtoupper(substr(md5($withdrawal_id . time()), 0, 6));
                    $trans_stmt = $pdo->prepare("
                        INSERT INTO transactions 
                        (user_id, transaction_type, amount, description, reference_number, status, created_at) 
                        VALUES (?, 'withdrawal', ?, ?, ?, 'completed', NOW())
                    ");
                    $trans_stmt->execute([
                        $withdrawal['user_id'],
                        $withdrawal['amount'],
                        "Withdrawal approved - " . $withdrawal['payment_method'],
                        $ref
                    ]);
                    
                    $pdo->commit();
                    $message = "Withdrawal approved! Br" . number_format($withdrawal['amount'], 2) . " - Please process payment to user.";
                } catch (Exception $e) {
                    $pdo->rollback();
                    $error = "Failed to approve withdrawal: " . $e->getMessage();
                }
            } elseif ($action === 'reject') {
                $pdo->beginTransaction();
                
                try {
                    // Update withdrawal status
                    $update = $pdo->prepare("UPDATE withdrawals SET status = 'rejected', admin_notes = ?, approved_by = ?, approved_at = NOW() WHERE id = ?");
                    $update->execute([$admin_notes, $_SESSION['user_id'], $withdrawal_id]);
                    
                    // Refund amount to user balance
                    $refund = $pdo->prepare("UPDATE users SET account_balance = account_balance + ? WHERE id = ?");
                    $refund->execute([$withdrawal['amount'], $withdrawal['user_id']]);
                    
                    $pdo->commit();
                    $message = "Withdrawal rejected. Amount refunded to user's wallet.";
                } catch (Exception $e) {
                    $pdo->rollback();
                    $error = "Failed to reject withdrawal: " . $e->getMessage();
                }
            }
        }
    }
    
    // Get all withdrawals
    $withdrawals = $pdo->query("
        SELECT w.*, u.full_name, u.email, u.account_balance,
               admin.full_name as admin_name
        FROM withdrawals w
        JOIN users u ON w.user_id = u.id
        LEFT JOIN users admin ON w.approved_by = admin.id
        ORDER BY 
            CASE w.status 
                WHEN 'pending' THEN 1
                WHEN 'approved' THEN 2
                WHEN 'rejected' THEN 3
                WHEN 'completed' THEN 4
            END,
            w.created_at DESC
    ")->fetchAll();
    
    // Get statistics
    $stats = [
        'pending' => $pdo->query("SELECT COUNT(*) FROM withdrawals WHERE status = 'pending'")->fetchColumn(),
        'approved' => $pdo->query("SELECT COUNT(*) FROM withdrawals WHERE status = 'approved'")->fetchColumn(),
        'rejected' => $pdo->query("SELECT COUNT(*) FROM withdrawals WHERE status = 'rejected'")->fetchColumn(),
        'total_pending_amount' => $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM withdrawals WHERE status = 'pending'")->fetchColumn(),
        'total_approved_amount' => $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM withdrawals WHERE status = 'approved'")->fetchColumn()
    ];
    
} catch(PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Withdrawal Approvals - Concordial Nexus Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 20px; }
        .container { max-width: 1600px; margin: 0 auto; background: rgba(255, 255, 255, 0.98); border-radius: 20px; padding: 30px; box-shadow: 0 20px 40px rgba(0,0,0,0.2); }
        .header { text-align: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 2px solid #e9ecef; }
        .header h1 { color: #333; font-size: 2.5rem; margin-bottom: 10px; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: linear-gradient(135deg, #4a90e2, #357abd); color: white; padding: 25px; border-radius: 15px; text-align: center; box-shadow: 0 10px 20px rgba(74, 144, 226, 0.3); }
        .stat-card.pending { background: linear-gradient(135deg, #f39c12, #e67e22); }
        .stat-card.approved { background: linear-gradient(135deg, #27ae60, #229954); }
        .stat-card.rejected { background: linear-gradient(135deg, #e74c3c, #c0392b); }
        .stat-card h3 { font-size: 1rem; margin-bottom: 10px; opacity: 0.9; }
        .stat-card .number { font-size: 2.5rem; font-weight: bold; }
        
        .message { background: #d4edda; color: #155724; padding: 15px; border-radius: 10px; margin-bottom: 20px; border-left: 4px solid #28a745; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 10px; margin-bottom: 20px; border-left: 4px solid #dc3545; }
        
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 15px; overflow: hidden; }
        th { background: linear-gradient(135deg, #4a90e2, #357abd); color: white; padding: 15px 10px; text-align: left; font-weight: 600; }
        td { padding: 12px 10px; border-bottom: 1px solid #e9ecef; }
        tr:hover { background: #f8f9fa; }
        
        .status { padding: 5px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; text-transform: uppercase; }
        .status.pending { background: #fff3cd; color: #856404; }
        .status.approved { background: #d4edda; color: #155724; }
        .status.rejected { background: #f8d7da; color: #721c24; }
        
        .btn { padding: 8px 15px; border: none; border-radius: 8px; cursor: pointer; font-size: 0.9rem; font-weight: 600; margin: 2px; }
        .btn-approve { background: #28a745; color: white; }
        .btn-reject { background: #dc3545; color: white; }
        .btn-back { background: #6c757d; color: white; padding: 12px 24px; text-decoration: none; display: inline-block; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-money-bill-wave"></i> Withdrawal Approvals</h1>
            <p>Concordial Nexus - Administrative Panel</p>
        </div>
        
        <a href="dashboard.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        
        <?php if ($message): ?>
            <div class="message"><i class="fas fa-check-circle"></i> <?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>
        
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card pending">
                <h3>Pending Withdrawals</h3>
                <div class="number"><?php echo $stats['pending']; ?></div>
                <div>Br<?php echo number_format($stats['total_pending_amount'], 2); ?></div>
            </div>
            <div class="stat-card approved">
                <h3>Approved Withdrawals</h3>
                <div class="number"><?php echo $stats['approved']; ?></div>
                <div>Br<?php echo number_format($stats['total_approved_amount'], 2); ?></div>
            </div>
            <div class="stat-card rejected">
                <h3>Rejected Withdrawals</h3>
                <div class="number"><?php echo $stats['rejected']; ?></div>
            </div>
        </div>
        
        <!-- Withdrawals Table -->
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Amount</th>
                    <th>Payment Method</th>
                    <th>Account Details</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($withdrawals)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 40px;">No withdrawal requests</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($withdrawals as $withdrawal): ?>
                        <tr>
                            <td><strong>#<?php echo str_pad($withdrawal['id'], 6, '0', STR_PAD_LEFT); ?></strong></td>
                            <td>
                                <strong><?php echo htmlspecialchars($withdrawal['full_name']); ?></strong><br>
                                <small><?php echo htmlspecialchars($withdrawal['email']); ?></small>
                            </td>
                            <td><strong style="color: #dc3545;">Br<?php echo number_format($withdrawal['amount'], 2); ?></strong></td>
                            <td><?php echo htmlspecialchars($withdrawal['payment_method'] ?? 'N/A'); ?></td>
                            <td><small><?php echo htmlspecialchars($withdrawal['account_details'] ?? 'N/A'); ?></small></td>
                            <td><span class="status <?php echo $withdrawal['status']; ?>"><?php echo ucfirst($withdrawal['status']); ?></span></td>
                            <td><?php echo date('M j, Y H:i', strtotime($withdrawal['created_at'])); ?></td>
                            <td>
                                <?php if ($withdrawal['status'] === 'pending'): ?>
                                    <form method="POST" style="display: inline-block;">
                                        <input type="hidden" name="withdrawal_id" value="<?php echo $withdrawal['id']; ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <input type="text" name="admin_notes" placeholder="Notes..." style="padding: 5px; margin: 2px;">
                                        <button type="submit" class="btn btn-approve" onclick="return confirm('Approve this withdrawal?')">
                                            <i class="fas fa-check"></i> Approve
                                        </button>
                                    </form>
                                    <br>
                                    <form method="POST" style="display: inline-block;">
                                        <input type="hidden" name="withdrawal_id" value="<?php echo $withdrawal['id']; ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <input type="text" name="admin_notes" placeholder="Reason..." style="padding: 5px; margin: 2px;" required>
                                        <button type="submit" class="btn btn-reject" onclick="return confirm('Reject and refund?')">
                                            <i class="fas fa-times"></i> Reject
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <small><?php echo htmlspecialchars($withdrawal['admin_notes'] ?? 'No notes'); ?></small>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
