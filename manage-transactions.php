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
        if (isset($_POST['approve_transaction'])) {
            $id = $_POST['transaction_id'];
            $stmt = $pdo->prepare("UPDATE transactions SET status = 'completed' WHERE id = ?");
            $stmt->execute([$id]);
            $message = 'Transaction approved successfully!';
            $message_type = 'success';
        }
        
        if (isset($_POST['reject_transaction'])) {
            $id = $_POST['transaction_id'];
            $stmt = $pdo->prepare("UPDATE transactions SET status = 'rejected' WHERE id = ?");
            $stmt->execute([$id]);
            $message = 'Transaction rejected!';
            $message_type = 'error';
        }
    }
    
    // Get all transactions
    $transactions = $pdo->query("
        SELECT t.*, u.full_name, u.email 
        FROM transactions t 
        LEFT JOIN users u ON t.user_id = u.id 
        ORDER BY t.created_at DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $message = 'Database error: ' . $e->getMessage();
    $message_type = 'error';
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Manage Transactions - Admin</title>
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
        .status-completed { background: #d4edda; color: #155724; }
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
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 style="margin: 0; color: #2c3e50;">
                <i class="fas fa-exchange-alt"></i> Manage Transactions
            </h1>
            <a href="dashboard.php" class="btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <h2 style="margin-bottom: 20px;">All Transactions</h2>
            
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($transactions) > 0): ?>
                        <?php foreach ($transactions as $trans): ?>
                            <tr>
                                <td>#<?php echo $trans['id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($trans['full_name'] ?? 'N/A'); ?></strong><br>
                                    <small><?php echo htmlspecialchars($trans['email'] ?? ''); ?></small>
                                </td>
                                <td><?php echo ucfirst($trans['transaction_type'] ?? 'N/A'); ?></td>
                                <td><strong>Br <?php echo number_format($trans['amount'] ?? 0, 2); ?></strong></td>
                                <td>
                                    <span class="status status-<?php echo $trans['status']; ?>">
                                        <?php echo ucfirst($trans['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($trans['created_at'])); ?></td>
                                <td>
                                    <?php if ($trans['status'] === 'pending'): ?>
                                        <div class="actions">
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="transaction_id" value="<?php echo $trans['id']; ?>">
                                                <button type="submit" name="approve_transaction" class="btn btn-approve">
                                                    <i class="fas fa-check"></i> Approve
                                                </button>
                                            </form>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="transaction_id" value="<?php echo $trans['id']; ?>">
                                                <button type="submit" name="reject_transaction" class="btn btn-reject" 
                                                        onclick="return confirm('Are you sure you want to reject this transaction?')">
                                                    <i class="fas fa-times"></i> Reject
                                                </button>
                                            </form>
                                        </div>
                                    <?php else: ?>
                                        <span style="color: #7f8c8d;">No action needed</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px; color: #7f8c8d;">
                                <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 10px;"></i><br>
                                No transactions found
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
