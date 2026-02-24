<?php
session_start();

// Check admin access
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

$message = '';
$error = '';

try {
    $pdo = new PDO("mysql:host=localhost;dbname=concordial_nexus;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Handle withdrawal actions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['action']) && isset($_POST['withdrawal_id'])) {
            $withdrawal_id = (int)$_POST['withdrawal_id'];
            $action = $_POST['action'];
            $admin_notes = $_POST['admin_notes'] ?? '';
            
            if ($action === 'approve') {
                // Get withdrawal details
                $stmt = $pdo->prepare("SELECT * FROM payment_transactions WHERE id = ? AND payment_method = 'withdrawal_request'");
                $stmt->execute([$withdrawal_id]);
                $withdrawal = $stmt->fetch();
                
                if ($withdrawal && $withdrawal['status'] === 'pending') {
                    // Check user balance
                    $user_stmt = $pdo->prepare("SELECT account_balance FROM users WHERE id = ?");
                    $user_stmt->execute([$withdrawal['user_id']]);
                    $user = $user_stmt->fetch();
                    
                    if ($user && $user['account_balance'] >= $withdrawal['amount']) {
                        // Start transaction
                        $pdo->beginTransaction();
                        
                        try {
                            // Update withdrawal status
                            $update_stmt = $pdo->prepare("UPDATE payment_transactions SET status = 'approved', admin_notes = ?, processed_at = NOW(), processed_by = ? WHERE id = ?");
                            $update_stmt->execute([$admin_notes, $_SESSION['user_id'], $withdrawal_id]);
                            
                            // Deduct from user balance
                            $balance_stmt = $pdo->prepare("UPDATE users SET account_balance = account_balance - ? WHERE id = ?");
                            $balance_stmt->execute([$withdrawal['amount'], $withdrawal['user_id']]);
                            
                            $pdo->commit();
                            $message = "Withdrawal approved successfully! Amount deducted from user balance.";
                        } catch (Exception $e) {
                            $pdo->rollback();
                            $error = "Failed to approve withdrawal: " . $e->getMessage();
                        }
                    } else {
                        $error = "Insufficient user balance for this withdrawal.";
                    }
                } else {
                    $error = "Withdrawal not found or already processed.";
                }
            } elseif ($action === 'reject') {
                $update_stmt = $pdo->prepare("UPDATE payment_transactions SET status = 'rejected', admin_notes = ?, processed_at = NOW(), processed_by = ? WHERE id = ?");
                if ($update_stmt->execute([$admin_notes, $_SESSION['user_id'], $withdrawal_id])) {
                    $message = "Withdrawal rejected successfully.";
                } else {
                    $error = "Failed to reject withdrawal.";
                }
            }
        }
    }
    
    // Get all withdrawal requests
    $withdrawals = $pdo->query("
        SELECT pt.*, u.full_name, u.email, u.account_balance,
               admin.full_name as processed_by_name
        FROM payment_transactions pt
        JOIN users u ON pt.user_id = u.id
        LEFT JOIN users admin ON pt.processed_by = admin.id
        WHERE pt.payment_method = 'withdrawal_request'
        ORDER BY pt.created_at DESC
    ")->fetchAll();
    
    // Get statistics
    $stats = [
        'pending' => $pdo->query("SELECT COUNT(*) FROM payment_transactions WHERE payment_method = 'withdrawal_request' AND status = 'pending'")->fetchColumn(),
        'approved' => $pdo->query("SELECT COUNT(*) FROM payment_transactions WHERE payment_method = 'withdrawal_request' AND status = 'approved'")->fetchColumn(),
        'rejected' => $pdo->query("SELECT COUNT(*) FROM payment_transactions WHERE payment_method = 'withdrawal_request' AND status = 'rejected'")->fetchColumn(),
        'total_pending_amount' => $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM payment_transactions WHERE payment_method = 'withdrawal_request' AND status = 'pending'")->fetchColumn()
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
    <title>Withdrawal Management - Concordial Nexus</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e9ecef;
        }
        
        .header h1 {
            color: #333;
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #4a90e2, #357abd);
            color: white;
            padding: 25px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 10px 20px rgba(74, 144, 226, 0.3);
        }
        
        .stat-card.pending {
            background: linear-gradient(135deg, #f39c12, #e67e22);
        }
        
        .stat-card.approved {
            background: linear-gradient(135deg, #27ae60, #229954);
        }
        
        .stat-card.rejected {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
        }
        
        .stat-card h3 {
            font-size: 1.2rem;
            margin-bottom: 10px;
            opacity: 0.9;
        }
        
        .stat-card .number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .message, .error {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 600;
        }
        
        .message {
            background: #d4edda;
            color: #155724;
            border: 2px solid #c3e6cb;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 2px solid #f5c6cb;
        }
        
        .withdrawals-table {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            background: linear-gradient(135deg, #4a90e2, #357abd);
            color: white;
            padding: 15px 10px;
            text-align: left;
            font-weight: 600;
        }
        
        td {
            padding: 15px 10px;
            border-bottom: 1px solid #e9ecef;
            vertical-align: top;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .status {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status.pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status.approved {
            background: #d4edda;
            color: #155724;
        }
        
        .status.rejected {
            background: #f8d7da;
            color: #721c24;
        }
        
        .action-form {
            display: inline-block;
            margin: 5px;
        }
        
        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: transform 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .btn-approve {
            background: #28a745;
            color: white;
        }
        
        .btn-reject {
            background: #dc3545;
            color: white;
        }
        
        .btn-back {
            background: #6c757d;
            color: white;
            margin-bottom: 20px;
        }
        
        .notes-input {
            width: 200px;
            padding: 5px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin: 5px;
        }
        
        .user-info {
            font-size: 0.9rem;
            color: #666;
        }
        
        .amount {
            font-weight: bold;
            color: #2c5aa0;
            font-size: 1.1rem;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            .header h1 {
                font-size: 2rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            table {
                font-size: 0.9rem;
            }
            
            th, td {
                padding: 10px 5px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>💰 Withdrawal Management</h1>
            <p>Concordial Nexus - Administrative Panel</p>
        </div>
        
        <a href="dashboard.php" class="btn btn-back">← Back to Dashboard</a>
        
        <?php if ($message): ?>
            <div class="message">✅ <?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error">❌ <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="stats-grid">
            <div class="stat-card pending">
                <h3>Pending Withdrawals</h3>
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
        
        <div class="withdrawals-table">
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
                    <?php if (empty($withdrawals)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px; color: #666;">
                                No withdrawal requests found
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($withdrawals as $withdrawal): ?>
                            <tr>
                                <td><strong>#<?php echo str_pad($withdrawal['id'], 6, '0', STR_PAD_LEFT); ?></strong></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($withdrawal['full_name']); ?></strong><br>
                                    <div class="user-info"><?php echo htmlspecialchars($withdrawal['email']); ?></div>
                                    <div class="user-info">Balance: Br<?php echo number_format($withdrawal['account_balance'], 2); ?></div>
                                </td>
                                <td>
                                    <div class="amount">Br<?php echo number_format($withdrawal['amount'], 2); ?></div>
                                    <?php if (isset($withdrawal['fee_amount']) && $withdrawal['fee_amount'] > 0): ?>
                                        <div class="user-info">Fee: Br<?php echo number_format($withdrawal['fee_amount'], 2); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($withdrawal['bank_name'] ?? 'N/A'); ?></strong><br>
                                    <div class="user-info">Account: <?php echo htmlspecialchars($withdrawal['account_number'] ?? 'N/A'); ?></div>
                                    <div class="user-info">Name: <?php echo htmlspecialchars($withdrawal['account_holder_name'] ?? 'N/A'); ?></div>
                                    <?php if (isset($withdrawal['mobile_number']) && $withdrawal['mobile_number']): ?>
                                        <div class="user-info">Phone: <?php echo htmlspecialchars($withdrawal['mobile_number']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status <?php echo $withdrawal['status']; ?>">
                                        <?php echo ucfirst($withdrawal['status']); ?>
                                    </span>
                                    <?php if (isset($withdrawal['processed_by_name']) && $withdrawal['processed_by_name']): ?>
                                        <div class="user-info">By: <?php echo htmlspecialchars($withdrawal['processed_by_name']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo date('M j, Y', strtotime($withdrawal['created_at'])); ?><br>
                                    <div class="user-info"><?php echo date('H:i', strtotime($withdrawal['created_at'])); ?></div>
                                    <?php if (isset($withdrawal['processed_at']) && $withdrawal['processed_at']): ?>
                                        <div class="user-info">Processed: <?php echo date('M j, H:i', strtotime($withdrawal['processed_at'])); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($withdrawal['status'] === 'pending'): ?>
                                        <form method="POST" class="action-form">
                                            <input type="hidden" name="withdrawal_id" value="<?php echo $withdrawal['id']; ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <input type="text" name="admin_notes" placeholder="Admin notes..." class="notes-input">
                                            <button type="submit" class="btn btn-approve" onclick="return confirm('Approve this withdrawal?')">
                                                ✅ Approve
                                            </button>
                                        </form>
                                        <form method="POST" class="action-form">
                                            <input type="hidden" name="withdrawal_id" value="<?php echo $withdrawal['id']; ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <input type="text" name="admin_notes" placeholder="Rejection reason..." class="notes-input">
                                            <button type="submit" class="btn btn-reject" onclick="return confirm('Reject this withdrawal?')">
                                                ❌ Reject
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <div class="user-info">
                                            <?php if (isset($withdrawal['admin_notes']) && $withdrawal['admin_notes']): ?>
                                                Notes: <?php echo htmlspecialchars($withdrawal['admin_notes']); ?>
                                            <?php else: ?>
                                                No notes
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>