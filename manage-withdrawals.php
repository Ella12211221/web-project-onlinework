<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

$message = '';
$message_type = '';

try {
    $pdo = new PDO("mysql:host=localhost;dbname=concordial_nexus", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Handle Approve/Reject actions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['approve_withdrawal'])) {
            $id = $_POST['withdrawal_id'];
            
            // Get withdrawal details
            $stmt = $pdo->prepare("SELECT * FROM withdrawals WHERE id = ?");
            $stmt->execute([$id]);
            $withdrawal = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($withdrawal) {
                // Update withdrawal status
                $update = $pdo->prepare("UPDATE withdrawals SET status = 'approved', processed_at = NOW() WHERE id = ?");
                $update->execute([$id]);
                
                // Add amount to user's account balance
                $balance_update = $pdo->prepare("UPDATE users SET account_balance = account_balance + ? WHERE id = ?");
                $balance_update->execute([$withdrawal['amount'], $withdrawal['user_id']]);
                
                $message = 'Withdrawal approved and amount credited to user account!';
                $message_type = 'success';
            }
        }
        
        if (isset($_POST['reject_withdrawal'])) {
            $id = $_POST['withdrawal_id'];
            $reason = $_POST['reject_reason'] ?? 'Rejected by admin';
            
            $stmt = $pdo->prepare("UPDATE withdrawals SET status = 'rejected', admin_notes = ?, processed_at = NOW() WHERE id = ?");
            $stmt->execute([$reason, $id]);
            
            $message = 'Withdrawal rejected!';
            $message_type = 'error';
        }
    }
    
    // Get all withdrawals
    $withdrawals = $pdo->query("
        SELECT w.*, u.full_name, u.email, u.account_balance
        FROM withdrawals w 
        LEFT JOIN users u ON w.user_id = u.id 
        ORDER BY w.created_at DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Get statistics
    $stats = [
        'pending' => $pdo->query("SELECT COUNT(*) FROM withdrawals WHERE status = 'pending'")->fetchColumn(),
        'approved' => $pdo->query("SELECT COUNT(*) FROM withdrawals WHERE status = 'approved'")->fetchColumn(),
        'rejected' => $pdo->query("SELECT COUNT(*) FROM withdrawals WHERE status = 'rejected'")->fetchColumn(),
        'total_amount' => $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM withdrawals WHERE status = 'approved'")->fetchColumn()
    ];
    
} catch (PDOException $e) {
    $message = 'Database error: ' . $e->getMessage();
    $message_type = 'error';
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Manage Withdrawals - Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea, #764ba2);
            padding: 20px;
        }
        .container { max-width: 1400px; margin: 0 auto; }
        .header {
            background: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 15px;
            text-align: center;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
        }
        .stat-label {
            color: #7f8c8d;
            margin-top: 5px;
        }
        .btn {
            padding: 10px 20px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }
        .btn-approve {
            background: #27ae60;
        }
        .btn-reject {
            background: #e74c3c;
        }
        .card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #667eea;
            color: white;
        }
        .status {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-approved { background: #d4edda; color: #155724; }
        .status-rejected { background: #f8d7da; color: #721c24; }
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-error { background: #f8d7da; color: #721c24; }
        .actions {
            display: flex;
            gap: 10px;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
        }
        .modal-content {
            background: white;
            max-width: 500px;
            margin: 100px auto;
            padding: 30px;
            border-radius: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 style="margin: 0; color: #2c3e50;">
                <i class="fas fa-money-bill-wave"></i> Manage Withdrawals
            </h1>
            <a href="dashboard.php" class="btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number" style="color: #f39c12;"><?php echo $stats['pending']; ?></div>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" style="color: #27ae60;"><?php echo $stats['approved']; ?></div>
                <div class="stat-label">Approved</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" style="color: #e74c3c;"><?php echo $stats['rejected']; ?></div>
                <div class="stat-label">Rejected</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">Br <?php echo number_format($stats['total_amount']); ?></div>
                <div class="stat-label">Total Paid</div>
            </div>
        </div>
        
        <div class="card">
            <h2 style="margin-bottom: 20px;">All Withdrawal Requests</h2>
            
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
                    <?php if (count($withdrawals) > 0): ?>
                        <?php foreach ($withdrawals as $w): ?>
                            <tr>
                                <td>#<?php echo $w['id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($w['full_name'] ?? 'N/A'); ?></strong><br>
                                    <small><?php echo htmlspecialchars($w['email'] ?? ''); ?></small><br>
                                    <small style="color: #27ae60;">Balance: Br <?php echo number_format($w['account_balance'] ?? 0); ?></small>
                                </td>
                                <td><strong style="color: #e74c3c;">Br <?php echo number_format($w['amount'] ?? 0, 2); ?></strong></td>
                                <td><?php echo ucfirst($w['payment_method'] ?? 'N/A'); ?></td>
                                <td>
                                    <small>
                                        <?php if ($w['payment_method'] === 'bank'): ?>
                                            Bank: <?php echo htmlspecialchars($w['bank_name'] ?? 'N/A'); ?><br>
                                            Account: <?php echo htmlspecialchars($w['account_number'] ?? 'N/A'); ?>
                                        <?php elseif ($w['payment_method'] === 'telebirr'): ?>
                                            Phone: <?php echo htmlspecialchars($w['phone_number'] ?? 'N/A'); ?>
                                        <?php endif; ?>
                                    </small>
                                </td>
                                <td>
                                    <span class="status status-<?php echo $w['status']; ?>">
                                        <?php echo ucfirst($w['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($w['created_at'])); ?></td>
                                <td>
                                    <?php if ($w['status'] === 'pending'): ?>
                                        <div class="actions">
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="withdrawal_id" value="<?php echo $w['id']; ?>">
                                                <button type="submit" name="approve_withdrawal" class="btn btn-approve"
                                                        onclick="return confirm('Approve this withdrawal of Br <?php echo number_format($w['amount']); ?>?')">
                                                    <i class="fas fa-check"></i> Approve
                                                </button>
                                            </form>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="withdrawal_id" value="<?php echo $w['id']; ?>">
                                                <input type="hidden" name="reject_reason" value="Rejected by admin">
                                                <button type="submit" name="reject_withdrawal" class="btn btn-reject" 
                                                        onclick="return confirm('Are you sure you want to reject this withdrawal?')">
                                                    <i class="fas fa-times"></i> Reject
                                                </button>
                                            </form>
                                        </div>
                                    <?php else: ?>
                                        <span style="color: #7f8c8d;">
                                            <?php echo $w['status'] === 'approved' ? 'Paid' : 'Rejected'; ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 40px; color: #7f8c8d;">
                                <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 10px;"></i><br>
                                No withdrawal requests found
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
