<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

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
    
    // Check if payment_transactions table exists
    $table_check = $pdo->query("SHOW TABLES LIKE 'payment_transactions'")->fetch();
    
    if (!$table_check) {
        $error = "Payment transactions table not found. Please run the setup script first.";
        $withdrawals = [];
        $stats = ['pending' => 0, 'approved' => 0, 'rejected' => 0, 'total_pending_amount' => 0];
    } else {
        // Handle actions
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
            $withdrawal_id = (int)$_POST['withdrawal_id'];
            $action = $_POST['action'];
            $notes = $_POST['admin_notes'] ?? '';
            
            if ($action === 'approve') {
                $pdo->exec("UPDATE payment_transactions SET status = 'approved', admin_notes = '$notes', processed_at = NOW(), processed_by = {$_SESSION['user_id']} WHERE id = $withdrawal_id");
                $message = "Withdrawal approved!";
            } elseif ($action === 'reject') {
                $pdo->exec("UPDATE payment_transactions SET status = 'rejected', admin_notes = '$notes', processed_at = NOW(), processed_by = {$_SESSION['user_id']} WHERE id = $withdrawal_id");
                $message = "Withdrawal rejected!";
            }
        }
        
        // Get withdrawals
        $withdrawals = $pdo->query("
            SELECT pt.*, u.full_name, u.email, u.account_balance
            FROM payment_transactions pt
            JOIN users u ON pt.user_id = u.id
            WHERE pt.payment_method = 'withdrawal_request'
            ORDER BY pt.created_at DESC
        ")->fetchAll();
        
        // Get stats
        $stats = [
            'pending' => $pdo->query("SELECT COUNT(*) FROM payment_transactions WHERE payment_method = 'withdrawal_request' AND status = 'pending'")->fetchColumn(),
            'approved' => $pdo->query("SELECT COUNT(*) FROM payment_transactions WHERE payment_method = 'withdrawal_request' AND status = 'approved'")->fetchColumn(),
            'rejected' => $pdo->query("SELECT COUNT(*) FROM payment_transactions WHERE payment_method = 'withdrawal_request' AND status = 'rejected'")->fetchColumn(),
            'total_pending_amount' => $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM payment_transactions WHERE payment_method = 'withdrawal_request' AND status = 'pending'")->fetchColumn()
        ];
    }
    
} catch(PDOException $e) {
    $error = "Database error: " . $e->getMessage();
    $withdrawals = [];
    $stats = ['pending' => 0, 'approved' => 0, 'rejected' => 0, 'total_pending_amount' => 0];
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Withdrawal Management - Concordial Nexus</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 20px; }
        .container { max-width: 1400px; margin: 0 auto; background: white; border-radius: 15px; padding: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
        h1 { color: #333; text-align: center; margin-bottom: 30px; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: linear-gradient(135deg, #4a90e2, #357abd); color: white; padding: 20px; border-radius: 10px; text-align: center; }
        .stat-card.pending { background: linear-gradient(135deg, #f39c12, #e67e22); }
        .stat-card.approved { background: linear-gradient(135deg, #27ae60, #229954); }
        .stat-card.rejected { background: linear-gradient(135deg, #e74c3c, #c0392b); }
        .stat-card h3 { font-size: 1rem; margin-bottom: 10px; }
        .stat-card .number { font-size: 2rem; font-weight: bold; }
        .message { padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center; }
        .message.success { background: #d4edda; color: #155724; }
        .message.error { background: #f8d7da; color: #721c24; }
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 10px; overflow: hidden; }
        th { background: #4a90e2; color: white; padding: 15px; text-align: left; }
        td { padding: 12px 15px; border-bottom: 1px solid #ddd; }
        tr:hover { background: #f8f9fa; }
        .status { padding: 5px 10px; border-radius: 15px; font-size: 0.85rem; font-weight: bold; text-transform: uppercase; }
        .status.pending { background: #fff3cd; color: #856404; }
        .status.approved { background: #d4edda; color: #155724; }
        .status.rejected { background: #f8d7da; color: #721c24; }
        .btn { padding: 8px 15px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; margin: 2px; }
        .btn-approve { background: #28a745; color: white; }
        .btn-reject { background: #dc3545; color: white; }
        .btn-back { background: #6c757d; color: white; text-decoration: none; display: inline-block; padding: 10px 20px; border-radius: 5px; margin-bottom: 20px; }
        input[type="text"] { padding: 5px; border: 1px solid #ddd; border-radius: 4px; margin: 2px; }
        .setup-link { background: #17a2b8; color: white; padding: 15px 30px; border-radius: 8px; text-decoration: none; display: inline-block; margin: 20px 0; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <h1>💰 Withdrawal Management</h1>
        
        <a href="dashboard.php" class="btn-back">← Back to Dashboard</a>
        
        <?php if ($message): ?>
            <div class="message success">✅ <?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error">
                ❌ <?php echo htmlspecialchars($error); ?>
                <br><br>
                <a href="../setup-payment-system.php" class="setup-link">🔧 Run Setup Script</a>
            </div>
        <?php endif; ?>
        
        <div class="stats">
            <div class="stat-card pending">
                <h3>Pending</h3>
                <div class="number"><?php echo $stats['pending']; ?></div>
                <div>Br<?php echo number_format($stats['total_pending_amount'], 2); ?></div>
            </div>
            <div class="stat-card approved">
                <h3>Approved</h3>
                <div class="number"><?php echo $stats['approved']; ?></div>
            </div>
            <div class="stat-card rejected">
                <h3>Rejected</h3>
                <div class="number"><?php echo $stats['rejected']; ?></div>
            </div>
        </div>
        
        <?php if (empty($withdrawals)): ?>
            <div style="text-align: center; padding: 60px 20px; color: #666;">
                <h2>No Withdrawal Requests Found</h2>
                <p>Withdrawal requests will appear here when users submit them.</p>
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Amount</th>
                        <th>Bank Details</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($withdrawals as $w): ?>
                        <tr>
                            <td><strong>#<?php echo str_pad($w['id'], 6, '0', STR_PAD_LEFT); ?></strong></td>
                            <td>
                                <strong><?php echo htmlspecialchars($w['full_name']); ?></strong><br>
                                <small><?php echo htmlspecialchars($w['email']); ?></small><br>
                                <small>Balance: Br<?php echo number_format($w['account_balance'], 2); ?></small>
                            </td>
                            <td>
                                <strong style="color: #2c5aa0; font-size: 1.1rem;">Br<?php echo number_format($w['amount'], 2); ?></strong>
                                <?php if ($w['fee_amount'] > 0): ?>
                                    <br><small>Fee: Br<?php echo number_format($w['fee_amount'], 2); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($w['bank_name'] ?? 'N/A'); ?></strong><br>
                                <small>Acc: <?php echo htmlspecialchars($w['account_number'] ?? 'N/A'); ?></small><br>
                                <small>Name: <?php echo htmlspecialchars($w['account_holder_name'] ?? 'N/A'); ?></small>
                                <?php if ($w['mobile_number']): ?>
                                    <br><small>Phone: <?php echo htmlspecialchars($w['mobile_number']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="status <?php echo $w['status']; ?>">
                                    <?php echo ucfirst($w['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php echo date('M j, Y', strtotime($w['created_at'])); ?><br>
                                <small><?php echo date('H:i', strtotime($w['created_at'])); ?></small>
                            </td>
                            <td>
                                <?php if ($w['status'] === 'pending'): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="withdrawal_id" value="<?php echo $w['id']; ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <input type="text" name="admin_notes" placeholder="Notes..." size="10">
                                        <button type="submit" class="btn btn-approve" onclick="return confirm('Approve?')">✅</button>
                                    </form>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="withdrawal_id" value="<?php echo $w['id']; ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <input type="text" name="admin_notes" placeholder="Reason..." size="10">
                                        <button type="submit" class="btn btn-reject" onclick="return confirm('Reject?')">❌</button>
                                    </form>
                                <?php else: ?>
                                    <small><?php echo htmlspecialchars($w['admin_notes'] ?? 'No notes'); ?></small>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>