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
    
    // Check if table exists
    $table_check = $pdo->query("SHOW TABLES LIKE 'payment_transactions'")->fetch();
    
    if (!$table_check) {
        $error = "Payment transactions table not found. Please run the setup script first.";
        $transactions = [];
        $stats = ['total' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0];
    } else {
        // Handle actions
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
            $transaction_id = (int)$_POST['transaction_id'];
            $action = $_POST['action'];
            $notes = $_POST['admin_notes'] ?? '';
            
            if ($action === 'approve') {
                $pdo->exec("UPDATE payment_transactions SET status = 'approved', admin_notes = '$notes', processed_at = NOW(), processed_by = {$_SESSION['user_id']} WHERE id = $transaction_id");
                $message = "Transaction approved!";
            } elseif ($action === 'reject') {
                $pdo->exec("UPDATE payment_transactions SET status = 'rejected', admin_notes = '$notes', processed_at = NOW(), processed_by = {$_SESSION['user_id']} WHERE id = $transaction_id");
                $message = "Transaction rejected!";
            } elseif ($action === 'delete') {
                $pdo->exec("DELETE FROM payment_transactions WHERE id = $transaction_id");
                $message = "Transaction deleted!";
            }
        }
        
        // Get transactions
        $transactions = $pdo->query("
            SELECT pt.*, u.full_name, u.email
            FROM payment_transactions pt
            JOIN users u ON pt.user_id = u.id
            ORDER BY pt.created_at DESC
            LIMIT 100
        ")->fetchAll();
        
        // Get stats
        $stats = [
            'total' => $pdo->query("SELECT COUNT(*) FROM payment_transactions")->fetchColumn(),
            'pending' => $pdo->query("SELECT COUNT(*) FROM payment_transactions WHERE status = 'pending'")->fetchColumn(),
            'approved' => $pdo->query("SELECT COUNT(*) FROM payment_transactions WHERE status = 'approved'")->fetchColumn(),
            'rejected' => $pdo->query("SELECT COUNT(*) FROM payment_transactions WHERE status = 'rejected'")->fetchColumn()
        ];
    }
    
} catch(PDOException $e) {
    $error = "Database error: " . $e->getMessage();
    $transactions = [];
    $stats = ['total' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0];
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Payment Transactions - Concordial Nexus</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 20px; }
        .container { max-width: 1600px; margin: 0 auto; background: white; border-radius: 15px; padding: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
        h1 { color: #333; text-align: center; margin-bottom: 30px; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 30px; }
        .stat-card { background: linear-gradient(135deg, #4a90e2, #357abd); color: white; padding: 20px; border-radius: 10px; text-align: center; }
        .stat-card.pending { background: linear-gradient(135deg, #f39c12, #e67e22); }
        .stat-card.approved { background: linear-gradient(135deg, #27ae60, #229954); }
        .stat-card.rejected { background: linear-gradient(135deg, #e74c3c, #c0392b); }
        .stat-card h3 { font-size: 1rem; margin-bottom: 8px; }
        .stat-card .number { font-size: 2rem; font-weight: bold; }
        .message { padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center; }
        .message.success { background: #d4edda; color: #155724; }
        .message.error { background: #f8d7da; color: #721c24; }
        table { width: 100%; border-collapse: collapse; background: white; }
        th { background: #4a90e2; color: white; padding: 12px; text-align: left; font-size: 0.9rem; }
        td { padding: 10px 12px; border-bottom: 1px solid #ddd; font-size: 0.9rem; }
        tr:hover { background: #f8f9fa; }
        .status { padding: 4px 8px; border-radius: 12px; font-size: 0.75rem; font-weight: bold; text-transform: uppercase; }
        .status.pending { background: #fff3cd; color: #856404; }
        .status.approved { background: #d4edda; color: #155724; }
        .status.rejected { background: #f8d7da; color: #721c24; }
        .method { padding: 4px 8px; border-radius: 10px; font-size: 0.75rem; font-weight: bold; }
        .method.withdrawal_request { background: #e1bee7; color: #7b1fa2; }
        .method.mobile_banking { background: #c8e6c9; color: #2e7d32; }
        .method.bank_transfer { background: #bbdefb; color: #1565c0; }
        .btn { padding: 6px 12px; border: none; border-radius: 4px; cursor: pointer; font-size: 0.8rem; margin: 2px; }
        .btn-approve { background: #28a745; color: white; }
        .btn-reject { background: #dc3545; color: white; }
        .btn-delete { background: #6c757d; color: white; }
        .btn-back { background: #6c757d; color: white; text-decoration: none; display: inline-block; padding: 10px 20px; border-radius: 5px; margin-bottom: 20px; }
        input[type="text"] { padding: 4px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.8rem; width: 100px; }
        .setup-link { background: #17a2b8; color: white; padding: 15px 30px; border-radius: 8px; text-decoration: none; display: inline-block; margin: 20px 0; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <h1>💳 Payment Transactions</h1>
        
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
            <div class="stat-card">
                <h3>Total</h3>
                <div class="number"><?php echo $stats['total']; ?></div>
            </div>
            <div class="stat-card pending">
                <h3>Pending</h3>
                <div class="number"><?php echo $stats['pending']; ?></div>
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
        
        <?php if (empty($transactions)): ?>
            <div style="text-align: center; padding: 60px 20px; color: #666;">
                <h2>No Transactions Found</h2>
                <p>Payment transactions will appear here.</p>
            </div>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Method</th>
                            <th>Amount</th>
                            <th>Details</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $t): ?>
                            <tr>
                                <td><strong>#<?php echo str_pad($t['id'], 6, '0', STR_PAD_LEFT); ?></strong></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($t['full_name']); ?></strong><br>
                                    <small><?php echo htmlspecialchars($t['email']); ?></small>
                                </td>
                                <td>
                                    <span class="method <?php echo $t['payment_method']; ?>">
                                        <?php echo str_replace('_', ' ', ucwords($t['payment_method'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <strong style="color: #2c5aa0;">Br<?php echo number_format($t['amount'], 2); ?></strong>
                                    <?php if ($t['fee_amount'] > 0): ?>
                                        <br><small>Fee: Br<?php echo number_format($t['fee_amount'], 2); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small>
                                        <?php if ($t['bank_name']): ?>
                                            <strong><?php echo htmlspecialchars($t['bank_name']); ?></strong><br>
                                        <?php endif; ?>
                                        <?php if ($t['account_number']): ?>
                                            Acc: <?php echo htmlspecialchars($t['account_number']); ?><br>
                                        <?php endif; ?>
                                        <?php if ($t['mobile_number']): ?>
                                            Phone: <?php echo htmlspecialchars($t['mobile_number']); ?>
                                        <?php endif; ?>
                                    </small>
                                </td>
                                <td>
                                    <span class="status <?php echo $t['status']; ?>">
                                        <?php echo ucfirst($t['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo date('M j, Y', strtotime($t['created_at'])); ?><br>
                                    <small><?php echo date('H:i', strtotime($t['created_at'])); ?></small>
                                </td>
                                <td>
                                    <?php if ($t['status'] === 'pending'): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="transaction_id" value="<?php echo $t['id']; ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <input type="text" name="admin_notes" placeholder="Notes...">
                                            <button type="submit" class="btn btn-approve">✅</button>
                                        </form>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="transaction_id" value="<?php echo $t['id']; ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <input type="text" name="admin_notes" placeholder="Reason...">
                                            <button type="submit" class="btn btn-reject">❌</button>
                                        </form>
                                    <?php else: ?>
                                        <small><?php echo htmlspecialchars($t['admin_notes'] ?? 'No notes'); ?></small>
                                    <?php endif; ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="transaction_id" value="<?php echo $t['id']; ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <button type="submit" class="btn btn-delete" onclick="return confirm('Delete?')">🗑️</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>