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
    
    // Handle transaction actions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['action']) && isset($_POST['transaction_id'])) {
            $transaction_id = (int)$_POST['transaction_id'];
            $action = $_POST['action'];
            $admin_notes = $_POST['admin_notes'] ?? '';
            
            if ($action === 'approve') {
                $update_stmt = $pdo->prepare("UPDATE payment_transactions SET status = 'approved', admin_notes = ?, processed_at = NOW(), processed_by = ? WHERE id = ?");
                if ($update_stmt->execute([$admin_notes, $_SESSION['user_id'], $transaction_id])) {
                    $message = "Transaction approved successfully!";
                } else {
                    $error = "Failed to approve transaction.";
                }
            } elseif ($action === 'reject') {
                $update_stmt = $pdo->prepare("UPDATE payment_transactions SET status = 'rejected', admin_notes = ?, processed_at = NOW(), processed_by = ? WHERE id = ?");
                if ($update_stmt->execute([$admin_notes, $_SESSION['user_id'], $transaction_id])) {
                    $message = "Transaction rejected successfully.";
                } else {
                    $error = "Failed to reject transaction.";
                }
            } elseif ($action === 'delete') {
                $delete_stmt = $pdo->prepare("DELETE FROM payment_transactions WHERE id = ?");
                if ($delete_stmt->execute([$transaction_id])) {
                    $message = "Transaction deleted successfully.";
                } else {
                    $error = "Failed to delete transaction.";
                }
            }
        }
    }
    
    // Get filter parameters
    $status_filter = $_GET['status'] ?? 'all';
    $method_filter = $_GET['method'] ?? 'all';
    $search = $_GET['search'] ?? '';
    
    // Build query
    $where_conditions = [];
    $params = [];
    
    if ($status_filter !== 'all') {
        $where_conditions[] = "pt.status = ?";
        $params[] = $status_filter;
    }
    
    if ($method_filter !== 'all') {
        $where_conditions[] = "pt.payment_method = ?";
        $params[] = $method_filter;
    }
    
    if ($search) {
        $where_conditions[] = "(u.full_name LIKE ? OR u.email LIKE ? OR pt.transaction_reference LIKE ?)";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }
    
    $where_clause = '';
    if (!empty($where_conditions)) {
        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
    }
    
    // Get all payment transactions
    $query = "
        SELECT pt.*, u.full_name, u.email, u.account_balance,
               admin.full_name as processed_by_name
        FROM payment_transactions pt
        JOIN users u ON pt.user_id = u.id
        LEFT JOIN users admin ON pt.processed_by = admin.id
        $where_clause
        ORDER BY pt.created_at DESC
        LIMIT 100
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $transactions = $stmt->fetchAll();
    
    // Get statistics
    $stats = [
        'total' => $pdo->query("SELECT COUNT(*) FROM payment_transactions")->fetchColumn(),
        'pending' => $pdo->query("SELECT COUNT(*) FROM payment_transactions WHERE status = 'pending'")->fetchColumn(),
        'approved' => $pdo->query("SELECT COUNT(*) FROM payment_transactions WHERE status = 'approved'")->fetchColumn(),
        'rejected' => $pdo->query("SELECT COUNT(*) FROM payment_transactions WHERE status = 'rejected'")->fetchColumn(),
        'total_amount' => $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM payment_transactions WHERE status = 'approved'")->fetchColumn(),
        'withdrawals' => $pdo->query("SELECT COUNT(*) FROM payment_transactions WHERE payment_method = 'withdrawal_request'")->fetchColumn(),
        'deposits' => $pdo->query("SELECT COUNT(*) FROM payment_transactions WHERE payment_method != 'withdrawal_request'")->fetchColumn()
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
    <title>Payment Transactions - Concordial Nexus</title>
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
            max-width: 1600px;
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
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #4a90e2, #357abd);
            color: white;
            padding: 20px;
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
        
        .stat-card.withdrawals {
            background: linear-gradient(135deg, #9b59b6, #8e44ad);
        }
        
        .stat-card h3 {
            font-size: 1rem;
            margin-bottom: 8px;
            opacity: 0.9;
        }
        
        .stat-card .number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .filters {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .filter-group label {
            font-weight: 600;
            color: #333;
            font-size: 0.9rem;
        }
        
        .filter-group select,
        .filter-group input {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 0.9rem;
        }
        
        .btn-filter {
            background: #4a90e2;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            margin-top: 20px;
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
        
        .transactions-table {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1200px;
        }
        
        th {
            background: linear-gradient(135deg, #4a90e2, #357abd);
            color: white;
            padding: 15px 10px;
            text-align: left;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        td {
            padding: 12px 10px;
            border-bottom: 1px solid #e9ecef;
            vertical-align: top;
            font-size: 0.9rem;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .status {
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
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
        
        .method-badge {
            padding: 4px 8px;
            border-radius: 10px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .method-badge.withdrawal_request {
            background: #e1bee7;
            color: #7b1fa2;
        }
        
        .method-badge.mobile_banking {
            background: #c8e6c9;
            color: #2e7d32;
        }
        
        .method-badge.bank_transfer {
            background: #bbdefb;
            color: #1565c0;
        }
        
        .method-badge.digital_wallet {
            background: #ffe0b2;
            color: #ef6c00;
        }
        
        .action-form {
            display: inline-block;
            margin: 2px;
        }
        
        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.8rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: transform 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-1px);
        }
        
        .btn-approve {
            background: #28a745;
            color: white;
        }
        
        .btn-reject {
            background: #dc3545;
            color: white;
        }
        
        .btn-delete {
            background: #6c757d;
            color: white;
        }
        
        .btn-back {
            background: #6c757d;
            color: white;
            margin-bottom: 20px;
            padding: 10px 20px;
        }
        
        .notes-input {
            width: 150px;
            padding: 4px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin: 2px;
            font-size: 0.8rem;
        }
        
        .user-info {
            font-size: 0.8rem;
            color: #666;
        }
        
        .amount {
            font-weight: bold;
            color: #2c5aa0;
        }
        
        .transaction-details {
            font-size: 0.8rem;
            color: #666;
            line-height: 1.3;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            .header h1 {
                font-size: 2rem;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .filters {
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>💳 Payment Transactions</h1>
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
            <div class="stat-card">
                <h3>Total Transactions</h3>
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
            <div class="stat-card withdrawals">
                <h3>Withdrawals</h3>
                <div class="number"><?php echo $stats['withdrawals']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Total Amount</h3>
                <div class="number" style="font-size: 1.5rem;">Br<?php echo number_format($stats['total_amount'], 0); ?></div>
            </div>
        </div>
        
        <form method="GET" class="filters">
            <div class="filter-group">
                <label>Status</label>
                <select name="status">
                    <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                    <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label>Method</label>
                <select name="method">
                    <option value="all" <?php echo $method_filter === 'all' ? 'selected' : ''; ?>>All Methods</option>
                    <option value="withdrawal_request" <?php echo $method_filter === 'withdrawal_request' ? 'selected' : ''; ?>>Withdrawals</option>
                    <option value="mobile_banking" <?php echo $method_filter === 'mobile_banking' ? 'selected' : ''; ?>>Mobile Banking</option>
                    <option value="bank_transfer" <?php echo $method_filter === 'bank_transfer' ? 'selected' : ''; ?>>Bank Transfer</option>
                    <option value="digital_wallet" <?php echo $method_filter === 'digital_wallet' ? 'selected' : ''; ?>>Digital Wallet</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label>Search</label>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Name, email, reference...">
            </div>
            
            <button type="submit" class="btn-filter">Filter</button>
        </form>
        
        <div class="transactions-table">
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
                    <?php if (empty($transactions)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 40px; color: #666;">
                                No transactions found
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($transactions as $transaction): ?>
                            <tr>
                                <td><strong>#<?php echo str_pad($transaction['id'], 6, '0', STR_PAD_LEFT); ?></strong></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($transaction['full_name']); ?></strong><br>
                                    <div class="user-info"><?php echo htmlspecialchars($transaction['email']); ?></div>
                                    <div class="user-info">Balance: Br<?php echo number_format($transaction['account_balance'], 2); ?></div>
                                </td>
                                <td>
                                    <span class="method-badge <?php echo $transaction['payment_method']; ?>">
                                        <?php echo str_replace('_', ' ', ucfirst($transaction['payment_method'])); ?>
                                    </span>
                                    <?php if ($transaction['payment_service']): ?>
                                        <div class="user-info"><?php echo htmlspecialchars($transaction['payment_service']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="amount">Br<?php echo number_format($transaction['amount'], 2); ?></div>
                                    <?php if (isset($transaction['fee_amount']) && $transaction['fee_amount'] > 0): ?>
                                        <div class="user-info">Fee: Br<?php echo number_format($transaction['fee_amount'], 2); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="transaction-details">
                                        <?php if (isset($transaction['bank_name']) && $transaction['bank_name']): ?>
                                            <strong><?php echo htmlspecialchars($transaction['bank_name']); ?></strong><br>
                                        <?php endif; ?>
                                        <?php if (isset($transaction['account_number']) && $transaction['account_number']): ?>
                                            Acc: <?php echo htmlspecialchars($transaction['account_number']); ?><br>
                                        <?php endif; ?>
                                        <?php if (isset($transaction['account_holder_name']) && $transaction['account_holder_name']): ?>
                                            Name: <?php echo htmlspecialchars($transaction['account_holder_name']); ?><br>
                                        <?php endif; ?>
                                        <?php if (isset($transaction['mobile_number']) && $transaction['mobile_number']): ?>
                                            Phone: <?php echo htmlspecialchars($transaction['mobile_number']); ?><br>
                                        <?php endif; ?>
                                        <?php if (isset($transaction['transaction_reference']) && $transaction['transaction_reference']): ?>
                                            Ref: <?php echo htmlspecialchars($transaction['transaction_reference']); ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="status <?php echo $transaction['status']; ?>">
                                        <?php echo ucfirst($transaction['status']); ?>
                                    </span>
                                    <?php if (isset($transaction['processed_by_name']) && $transaction['processed_by_name']): ?>
                                        <div class="user-info">By: <?php echo htmlspecialchars($transaction['processed_by_name']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo date('M j, Y', strtotime($transaction['created_at'])); ?><br>
                                    <div class="user-info"><?php echo date('H:i', strtotime($transaction['created_at'])); ?></div>
                                    <?php if ($transaction['processed_at']): ?>
                                        <div class="user-info">Processed: <?php echo date('M j, H:i', strtotime($transaction['processed_at'])); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($transaction['status'] === 'pending'): ?>
                                        <form method="POST" class="action-form">
                                            <input type="hidden" name="transaction_id" value="<?php echo $transaction['id']; ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <input type="text" name="admin_notes" placeholder="Notes..." class="notes-input">
                                            <button type="submit" class="btn btn-approve" onclick="return confirm('Approve this transaction?')">
                                                ✅
                                            </button>
                                        </form>
                                        <form method="POST" class="action-form">
                                            <input type="hidden" name="transaction_id" value="<?php echo $transaction['id']; ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <input type="text" name="admin_notes" placeholder="Reason..." class="notes-input">
                                            <button type="submit" class="btn btn-reject" onclick="return confirm('Reject this transaction?')">
                                                ❌
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <div class="transaction-details">
                                            <?php if ($transaction['admin_notes']): ?>
                                                Notes: <?php echo htmlspecialchars($transaction['admin_notes']); ?>
                                            <?php else: ?>
                                                No notes
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <form method="POST" class="action-form">
                                        <input type="hidden" name="transaction_id" value="<?php echo $transaction['id']; ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <button type="submit" class="btn btn-delete" onclick="return confirm('Delete this transaction? This cannot be undone!')">
                                            🗑️
                                        </button>
                                    </form>
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