<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

$message = '';
$error = '';
$table_missing = false;

try {
    $pdo = new PDO("mysql:host=localhost;dbname=concordial_nexus;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if deposits table exists
    $table_check = $pdo->query("SHOW TABLES LIKE 'deposits'")->fetch();
    if (!$table_check) {
        $table_missing = true;
        throw new Exception("Deposits table does not exist. Please run the setup script.");
    }
    
    // Handle approve/reject actions
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['deposit_id'])) {
        $deposit_id = (int)$_POST['deposit_id'];
        $action = $_POST['action'];
        $admin_notes = $_POST['admin_notes'] ?? '';
        
        // Get deposit details
        $deposit_stmt = $pdo->prepare("SELECT * FROM deposits WHERE id = ?");
        $deposit_stmt->execute([$deposit_id]);
        $deposit = $deposit_stmt->fetch();
        
        if ($deposit && $deposit['status'] === 'pending') {
            if ($action === 'approve') {
                $pdo->beginTransaction();
                
                try {
                    // Update deposit status
                    $update = $pdo->prepare("UPDATE deposits SET status = 'approved', admin_notes = ?, approved_by = ?, approved_at = NOW() WHERE id = ?");
                    $update->execute([$admin_notes, $_SESSION['user_id'], $deposit_id]);
                    
                    // Add amount to user balance
                    $add_balance = $pdo->prepare("UPDATE users SET account_balance = account_balance + ? WHERE id = ?");
                    $add_balance->execute([$deposit['amount'], $deposit['user_id']]);
                    
                    // Create transaction record
                    $date = date('Ymd');
                    $random = strtoupper(substr(md5($deposit_id . time() . rand()), 0, 5));
                    $reference = "TXN-{$date}-{$random}";
                    
                    $transaction = $pdo->prepare("
                        INSERT INTO transactions 
                        (user_id, transaction_type, amount, reference_number, description, status, created_at) 
                        VALUES (?, 'deposit', ?, ?, ?, 'completed', NOW())
                    ");
                    $transaction->execute([
                        $deposit['user_id'],
                        $deposit['amount'],
                        $reference,
                        "Deposit approved - Payment Ref: " . $deposit['payment_reference']
                    ]);
                    
                    $pdo->commit();
                    $message = "Deposit approved successfully! Br" . number_format($deposit['amount'], 2) . " added to user account.";
                } catch (Exception $e) {
                    $pdo->rollback();
                    $error = "Failed to approve deposit: " . $e->getMessage();
                }
            } elseif ($action === 'reject') {
                $update = $pdo->prepare("UPDATE deposits SET status = 'rejected', admin_notes = ?, approved_by = ?, approved_at = NOW() WHERE id = ?");
                if ($update->execute([$admin_notes, $_SESSION['user_id'], $deposit_id])) {
                    $message = "Deposit rejected.";
                }
            }
        }
    }
    
    // Get all deposits with product info
    $deposits = $pdo->query("
        SELECT d.*, u.full_name, u.email, u.account_balance,
               admin.full_name as admin_name,
               p.name as product_name, p.return_percentage, p.duration_days
        FROM deposits d
        JOIN users u ON d.user_id = u.id
        LEFT JOIN users admin ON d.approved_by = admin.id
        LEFT JOIN products p ON d.product_id = p.id
        ORDER BY 
            CASE d.status 
                WHEN 'pending' THEN 1
                WHEN 'approved' THEN 2
                WHEN 'rejected' THEN 3
            END,
            d.created_at DESC
    ")->fetchAll();
    
    // Get statistics
    $stats = [
        'pending' => $pdo->query("SELECT COUNT(*) FROM deposits WHERE status = 'pending'")->fetchColumn(),
        'approved' => $pdo->query("SELECT COUNT(*) FROM deposits WHERE status = 'approved'")->fetchColumn(),
        'rejected' => $pdo->query("SELECT COUNT(*) FROM deposits WHERE status = 'rejected'")->fetchColumn(),
        'total_pending_amount' => $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM deposits WHERE status = 'pending'")->fetchColumn(),
        'total_approved_amount' => $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM deposits WHERE status = 'approved'")->fetchColumn()
    ];
    
} catch(PDOException $e) {
    $error = "Database error: " . $e->getMessage();
    $stats = [
        'pending' => 0,
        'approved' => 0,
        'rejected' => 0,
        'total_pending_amount' => 0,
        'total_approved_amount' => 0
    ];
    $deposits = [];
} catch(Exception $e) {
    $error = $e->getMessage();
    $stats = [
        'pending' => 0,
        'approved' => 0,
        'rejected' => 0,
        'total_pending_amount' => 0,
        'total_approved_amount' => 0
    ];
    $deposits = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deposit Management - Concordial Nexus</title>
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
        .stat-card .number { font-size: 2.5rem; font-weight: bold; margin-bottom: 5px; }
        
        .message { background: #d4edda; color: #155724; padding: 15px; border-radius: 10px; margin-bottom: 20px; text-align: center; font-weight: 600; border-left: 4px solid #28a745; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 10px; margin-bottom: 20px; text-align: center; font-weight: 600; border-left: 4px solid #dc3545; }
        
        .deposits-table { background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.1); overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 1200px; }
        th { background: linear-gradient(135deg, #4a90e2, #357abd); color: white; padding: 15px 10px; text-align: left; font-weight: 600; font-size: 0.9rem; }
        td { padding: 12px 10px; border-bottom: 1px solid #e9ecef; vertical-align: top; font-size: 0.9rem; }
        tr:hover { background: #f8f9fa; }
        
        .status { padding: 5px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; text-transform: uppercase; }
        .status.pending { background: #fff3cd; color: #856404; }
        .status.approved { background: #d4edda; color: #155724; }
        .status.rejected { background: #f8d7da; color: #721c24; }
        
        .btn { padding: 8px 15px; border: none; border-radius: 8px; cursor: pointer; font-size: 0.9rem; font-weight: 600; text-decoration: none; display: inline-block; transition: transform 0.2s; margin: 2px; }
        .btn:hover { transform: translateY(-2px); }
        .btn-approve { background: #28a745; color: white; }
        .btn-reject { background: #dc3545; color: white; }
        .btn-back { background: #6c757d; color: white; margin-bottom: 20px; padding: 12px 24px; }
        
        .notes-input { width: 180px; padding: 6px; border: 1px solid #ddd; border-radius: 5px; margin: 5px; font-size: 0.85rem; }
        .user-info { font-size: 0.85rem; color: #666; }
        .amount { font-weight: bold; color: #2c5aa0; font-size: 1.1rem; }
        .reference { font-family: monospace; font-weight: bold; color: #333; background: #f8f9fa; padding: 4px 8px; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>💰 Deposit Management</h1>
            <p>Concordial Nexus - Administrative Panel</p>
        </div>
        
        <a href="dashboard.php" class="btn-back">← Back to Dashboard</a>
        
        <?php if ($table_missing): ?>
            <div style="background: linear-gradient(135deg, #e74c3c, #c0392b); color: white; padding: 30px; border-radius: 15px; margin-bottom: 30px; box-shadow: 0 10px 30px rgba(231, 76, 60, 0.4);">
                <h2 style="margin: 0 0 15px 0; font-size: 2rem;">⚠️ Deposits Table Not Found</h2>
                <p style="font-size: 1.1rem; margin-bottom: 20px;">The deposits table doesn't exist in your database yet. This is required for the deposit system to work.</p>
                
                <div style="background: rgba(255,255,255,0.2); padding: 20px; border-radius: 10px; margin-bottom: 20px;">
                    <h3 style="margin: 0 0 10px 0;">🚀 Quick Fix (30 seconds):</h3>
                    <p style="margin-bottom: 15px;">Click the button below to automatically create the deposits table:</p>
                    <a href="../create-deposits-table-now.php" style="display: inline-block; background: white; color: #e74c3c; padding: 15px 30px; border-radius: 8px; text-decoration: none; font-weight: bold; font-size: 1.1rem;">
                        ✅ Create Deposits Table Now
                    </a>
                </div>
                
                <div style="background: rgba(255,255,255,0.1); padding: 15px; border-radius: 10px;">
                    <p style="margin: 0; font-size: 0.9rem;"><strong>Alternative:</strong> Run the setup script at <code style="background: rgba(0,0,0,0.2); padding: 3px 8px; border-radius: 4px;">/database/setup-deposits-table.php</code></p>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($message): ?>
            <div class="message">✅ <?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error">❌ <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="stats-grid">
            <div class="stat-card pending">
                <h3>Pending Deposits</h3>
                <div class="number"><?php echo $stats['pending']; ?></div>
                <div>Br<?php echo number_format($stats['total_pending_amount'], 2); ?></div>
            </div>
            <div class="stat-card approved">
                <h3>Approved Deposits</h3>
                <div class="number"><?php echo $stats['approved']; ?></div>
                <div>Br<?php echo number_format($stats['total_approved_amount'], 2); ?></div>
            </div>
            <div class="stat-card rejected">
                <h3>Rejected Deposits</h3>
                <div class="number"><?php echo $stats['rejected']; ?></div>
            </div>
        </div>
        
        <div class="deposits-table">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Amount</th>
                        <th>Product/Package</th>
                        <th>Payment Method</th>
                        <th>Payment Reference</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($deposits)): ?>
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 40px; color: #666;">
                                No deposit requests found
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($deposits as $deposit): ?>
                            <tr>
                                <td><strong>#<?php echo str_pad($deposit['id'], 6, '0', STR_PAD_LEFT); ?></strong></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($deposit['full_name']); ?></strong><br>
                                    <div class="user-info"><?php echo htmlspecialchars($deposit['email']); ?></div>
                                    <div class="user-info">Balance: Br<?php echo number_format($deposit['account_balance'], 2); ?></div>
                                </td>
                                <td>
                                    <div class="amount">Br<?php echo number_format($deposit['amount'], 2); ?></div>
                                </td>
                                <td>
                                    <?php if (isset($deposit['product_name']) && $deposit['product_name']): ?>
                                        <strong><?php echo htmlspecialchars($deposit['product_name']); ?></strong><br>
                                        <?php if (isset($deposit['return_percentage']) && $deposit['return_percentage']): ?>
                                            <div class="user-info">📈 <?php echo $deposit['return_percentage']; ?>% returns</div>
                                        <?php endif; ?>
                                        <?php if (isset($deposit['duration_days']) && $deposit['duration_days']): ?>
                                            <div class="user-info">⏱️ <?php echo $deposit['duration_days']; ?> days</div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div class="user-info" style="color: #999;">Custom amount</div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo ucwords(str_replace('_', ' ', $deposit['payment_method'])); ?></strong><br>
                                    <?php if (isset($deposit['payment_service']) && $deposit['payment_service']): ?>
                                        <div class="user-info"><?php echo htmlspecialchars($deposit['payment_service']); ?></div>
                                    <?php endif; ?>
                                    <?php if (isset($deposit['bank_name']) && $deposit['bank_name']): ?>
                                        <div class="user-info"><?php echo htmlspecialchars($deposit['bank_name']); ?></div>
                                    <?php endif; ?>
                                    <?php if (isset($deposit['mobile_number']) && $deposit['mobile_number']): ?>
                                        <div class="user-info">📱 <?php echo htmlspecialchars($deposit['mobile_number']); ?></div>
                                    <?php endif; ?>
                                    <?php if (isset($deposit['account_number']) && $deposit['account_number']): ?>
                                        <div class="user-info">Acc: <?php echo htmlspecialchars($deposit['account_number']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="reference"><?php echo htmlspecialchars($deposit['payment_reference']); ?></span>
                                </td>
                                <td>
                                    <span class="status <?php echo $deposit['status']; ?>">
                                        <?php echo ucfirst($deposit['status']); ?>
                                    </span>
                                    <?php if (isset($deposit['admin_name']) && $deposit['admin_name']): ?>
                                        <div class="user-info">By: <?php echo htmlspecialchars($deposit['admin_name']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo date('M j, Y', strtotime($deposit['created_at'])); ?><br>
                                    <div class="user-info"><?php echo date('H:i', strtotime($deposit['created_at'])); ?></div>
                                    <?php if (isset($deposit['approved_at']) && $deposit['approved_at']): ?>
                                        <div class="user-info">Processed: <?php echo date('M j, H:i', strtotime($deposit['approved_at'])); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($deposit['status'] === 'pending'): ?>
                                        <form method="POST" style="display: inline-block;">
                                            <input type="hidden" name="deposit_id" value="<?php echo $deposit['id']; ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <input type="text" name="admin_notes" placeholder="Admin notes..." class="notes-input">
                                            <button type="submit" class="btn btn-approve" onclick="return confirm('Approve this deposit? Amount will be added to user account.')">
                                                ✅ Approve
                                            </button>
                                        </form>
                                        <br>
                                        <form method="POST" style="display: inline-block;">
                                            <input type="hidden" name="deposit_id" value="<?php echo $deposit['id']; ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <input type="text" name="admin_notes" placeholder="Rejection reason..." class="notes-input" required>
                                            <button type="submit" class="btn btn-reject" onclick="return confirm('Reject this deposit?')">
                                                ❌ Reject
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <div class="user-info">
                                            <?php if (isset($deposit['admin_notes']) && $deposit['admin_notes']): ?>
                                                Notes: <?php echo htmlspecialchars($deposit['admin_notes']); ?>
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
