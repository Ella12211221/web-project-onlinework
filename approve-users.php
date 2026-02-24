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
    
    // Handle approve/reject actions
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['user_id'])) {
        $user_id = (int)$_POST['user_id'];
        $action = $_POST['action'];
        $admin_notes = $_POST['admin_notes'] ?? '';
        
        if ($action === 'approve') {
            $update = $pdo->prepare("UPDATE users SET status = 'active', approved_by = ?, approved_at = NOW() WHERE id = ?");
            if ($update->execute([$_SESSION['user_id'], $user_id])) {
                $message = "User approved successfully!";
            }
        } elseif ($action === 'reject') {
            $update = $pdo->prepare("UPDATE users SET status = 'rejected', approved_by = ?, approved_at = NOW() WHERE id = ?");
            if ($update->execute([$_SESSION['user_id'], $user_id])) {
                $message = "User rejected.";
            }
        }
    }
    
    // Get all users grouped by status
    $pending_users = $pdo->query("SELECT * FROM users WHERE status = 'pending' ORDER BY created_at DESC")->fetchAll();
    $active_users = $pdo->query("SELECT * FROM users WHERE status = 'active' ORDER BY created_at DESC LIMIT 20")->fetchAll();
    $rejected_users = $pdo->query("SELECT * FROM users WHERE status = 'rejected' ORDER BY created_at DESC LIMIT 10")->fetchAll();
    
    // Get statistics
    $stats = [
        'pending' => $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'pending'")->fetchColumn(),
        'active' => $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn(),
        'rejected' => $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'rejected'")->fetchColumn(),
        'total' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn()
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
    <title>User Approvals - Concordial Nexus Admin</title>
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
        .stat-card.active { background: linear-gradient(135deg, #27ae60, #229954); }
        .stat-card.rejected { background: linear-gradient(135deg, #e74c3c, #c0392b); }
        .stat-card h3 { font-size: 1rem; margin-bottom: 10px; opacity: 0.9; }
        .stat-card .number { font-size: 2.5rem; font-weight: bold; }
        
        .message { background: #d4edda; color: #155724; padding: 15px; border-radius: 10px; margin-bottom: 20px; border-left: 4px solid #28a745; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 10px; margin-bottom: 20px; border-left: 4px solid #dc3545; }
        
        .section { background: white; border-radius: 15px; padding: 20px; margin: 20px 0; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .section-title { font-size: 1.5rem; color: #333; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #667eea; }
        
        table { width: 100%; border-collapse: collapse; }
        th { background: linear-gradient(135deg, #4a90e2, #357abd); color: white; padding: 15px 10px; text-align: left; font-weight: 600; }
        td { padding: 12px 10px; border-bottom: 1px solid #e9ecef; }
        tr:hover { background: #f8f9fa; }
        
        .status { padding: 5px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; text-transform: uppercase; }
        .status.pending { background: #fff3cd; color: #856404; }
        .status.active { background: #d4edda; color: #155724; }
        .status.rejected { background: #f8d7da; color: #721c24; }
        
        .btn { padding: 8px 15px; border: none; border-radius: 8px; cursor: pointer; font-size: 0.9rem; font-weight: 600; margin: 2px; }
        .btn:hover { transform: translateY(-2px); }
        .btn-approve { background: #28a745; color: white; }
        .btn-reject { background: #dc3545; color: white; }
        .btn-back { background: #6c757d; color: white; padding: 12px 24px; text-decoration: none; display: inline-block; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-user-check"></i> User Registration Approvals</h1>
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
                <h3>Pending Approval</h3>
                <div class="number"><?php echo $stats['pending']; ?></div>
            </div>
            <div class="stat-card active">
                <h3>Active Users</h3>
                <div class="number"><?php echo $stats['active']; ?></div>
            </div>
            <div class="stat-card rejected">
                <h3>Rejected</h3>
                <div class="number"><?php echo $stats['rejected']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Total Users</h3>
                <div class="number"><?php echo $stats['total']; ?></div>
            </div>
        </div>
        
        <!-- Pending Users -->
        <?php if (!empty($pending_users)): ?>
        <div class="section">
            <h2 class="section-title"><i class="fas fa-clock"></i> Pending Registrations (<?php echo count($pending_users); ?>)</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Registered</th>
                        <th>Referral Code</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pending_users as $user): ?>
                        <tr>
                            <td><strong>#<?php echo $user['id']; ?></strong></td>
                            <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo date('M j, Y H:i', strtotime($user['created_at'])); ?></td>
                            <td><?php echo htmlspecialchars($user['referral_code'] ?? 'N/A'); ?></td>
                            <td><span class="status pending">Pending</span></td>
                            <td>
                                <form method="POST" style="display: inline-block;">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <input type="text" name="admin_notes" placeholder="Notes..." style="padding: 5px; margin-right: 5px;">
                                    <button type="submit" class="btn btn-approve" onclick="return confirm('Approve this user?')">
                                        <i class="fas fa-check"></i> Approve
                                    </button>
                                </form>
                                <form method="POST" style="display: inline-block;">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <input type="text" name="admin_notes" placeholder="Reason..." style="padding: 5px; margin-right: 5px;" required>
                                    <button type="submit" class="btn btn-reject" onclick="return confirm('Reject this user?')">
                                        <i class="fas fa-times"></i> Reject
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="section" style="text-align: center; padding: 40px;">
            <i class="fas fa-check-circle" style="font-size: 4rem; color: #28a745; margin-bottom: 20px;"></i>
            <h3>No Pending Registrations</h3>
            <p style="color: #666;">All user registrations have been processed</p>
        </div>
        <?php endif; ?>
        
        <!-- Active Users -->
        <?php if (!empty($active_users)): ?>
        <div class="section">
            <h2 class="section-title"><i class="fas fa-users"></i> Recent Active Users</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Balance</th>
                        <th>Registered</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($active_users as $user): ?>
                        <tr>
                            <td><strong>#<?php echo $user['id']; ?></strong></td>
                            <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>Br<?php echo number_format($user['account_balance'], 2); ?></td>
                            <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                            <td><span class="status active">Active</span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
