<?php
// Start session at the very beginning
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

try {
    $pdo = new PDO("mysql:host=localhost;dbname=concordial_nexus;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Handle user actions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['approve_user'])) {
            $user_id = $_POST['user_id'];
            $welcome_bonus = floatval($_POST['welcome_bonus'] ?? 500);
            
            // Approve user
            $approve_stmt = $pdo->prepare("UPDATE users SET status = 'active', approved_by = ?, approved_at = NOW() WHERE id = ?");
            $approve_stmt->execute([$_SESSION['user_id'], $user_id]);
            
            // Add welcome bonus
            if ($welcome_bonus > 0) {
                $update_balance = $pdo->prepare("UPDATE users SET account_balance = account_balance + ? WHERE id = ?");
                $update_balance->execute([$welcome_bonus, $user_id]);
                
                // Create welcome bonus transaction
                $reference = 'APR' . date('Ymd') . rand(1000, 9999);
                $trans_stmt = $pdo->prepare("INSERT INTO transactions (user_id, transaction_type, amount, description, reference_number, status) VALUES (?, 'deposit', ?, 'Welcome bonus - Account approved', ?, 'completed')");
                $trans_stmt->execute([$user_id, $welcome_bonus, $reference]);
            }
            
            $success_message = "User approved successfully!";
        }
        
        if (isset($_POST['reject_user'])) {
            $user_id = $_POST['user_id'];
            $reject_stmt = $pdo->prepare("UPDATE users SET status = 'inactive' WHERE id = ?");
            $reject_stmt->execute([$user_id]);
            $success_message = "User rejected successfully!";
        }
        
        if (isset($_POST['suspend_user'])) {
            $user_id = $_POST['user_id'];
            $suspend_stmt = $pdo->prepare("UPDATE users SET status = 'suspended' WHERE id = ?");
            $suspend_stmt->execute([$user_id]);
            $success_message = "User suspended successfully!";
        }
    }
    
    // Get filter from URL
    $filter = $_GET['filter'] ?? 'all';
    $search = $_GET['search'] ?? '';
    
    // Get user statistics
    $stats = [
        'total' => $pdo->query("SELECT COUNT(*) FROM users WHERE user_type = 'user'")->fetchColumn(),
        'pending' => $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'pending'")->fetchColumn(),
        'approved' => $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn(),
        'rejected' => $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'inactive'")->fetchColumn()
    ];
    
    // Build query based on filter and search
    $where_clause = "WHERE u.user_type = 'user'";
    if ($filter === 'pending') {
        $where_clause .= " AND u.status = 'pending'";
    } elseif ($filter === 'approved') {
        $where_clause .= " AND u.status = 'active'";
    } elseif ($filter === 'rejected') {
        $where_clause .= " AND u.status = 'inactive'";
    }
    
    if (!empty($search)) {
        $where_clause .= " AND (u.full_name LIKE '%$search%' OR u.email LIKE '%$search%')";
    }
    
    // Get users with their invitation codes
    $users_stmt = $pdo->query("
        SELECT u.*, ic.code as invitation_used_code, ic.bonus_amount as invitation_bonus,
               approver.full_name as approved_by_name
        FROM users u 
        LEFT JOIN invitation_codes ic ON u.invitation_code_used = ic.code
        LEFT JOIN users approver ON u.approved_by = approver.id
        {$where_clause}
        ORDER BY 
            CASE u.status 
                WHEN 'pending' THEN 1 
                WHEN 'active' THEN 2 
                WHEN 'suspended' THEN 3 
                WHEN 'inactive' THEN 4 
            END, u.created_at DESC
    ");
    $users = $users_stmt->fetchAll();
    
} catch(PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
    $users = [];
    $stats = ['total' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Breakthrough Trading</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }
        .transport-header {
            background: linear-gradient(135deg, #4a90e2 0%, #357abd 100%);
            color: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .transport-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .transport-logo {
            font-size: 1.5rem;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .transport-menu {
            display: flex;
            gap: 2rem;
            list-style: none;
        }
        .transport-menu a {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            transition: background 0.3s;
        }
        .transport-menu a:hover, .transport-menu a.active {
            background: rgba(255,255,255,0.2);
        }
        .page-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }
        .page-header {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .page-title {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .page-title h1 {
            color: #333;
            font-size: 1.8rem;
        }
        .page-icon {
            background: linear-gradient(135deg, #4a90e2, #357abd);
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }
        .search-section {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .search-form {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        .search-input {
            flex: 1;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }
        .btn {
            background: #4a90e2;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
            transition: background 0.3s;
            cursor: pointer;
        }
        .btn:hover {
            background: #357abd;
        }
        .btn.success {
            background: #27ae60;
        }
        .btn.success:hover {
            background: #219a52;
        }
        .filter-tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 2rem;
        }
        .filter-tab {
            background: white;
            color: #666;
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .filter-tab.active {
            background: #4a90e2;
            color: white;
        }
        .filter-tab:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        }
        .users-table-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .table-header {
            background: #f8f9fa;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        .table th,
        .table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
            text-transform: uppercase;
            font-size: 0.85rem;
        }
        .table tbody tr:hover {
            background: #f8f9fa;
        }
        .badge {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .badge.success {
            background: #d4edda;
            color: #155724;
        }
        .badge.warning {
            background: #fff3cd;
            color: #856404;
        }
        .badge.danger {
            background: #f8d7da;
            color: #721c24;
        }
        .badge.secondary {
            background: #e2e3e5;
            color: #383d41;
        }
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
        }
        .alert {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
        }
        @media (max-width: 768px) {
            .transport-menu {
                display: none;
            }
            .page-container {
                padding: 1rem;
            }
            .page-header {
                flex-direction: column;
                gap: 1rem;
            }
            .search-form {
                flex-direction: column;
            }
            .filter-tabs {
                flex-wrap: wrap;
            }
            .table {
                font-size: 0.85rem;
            }
        }
    </style>
</head>
<body>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Breakthrough Trading</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
        }
        
        .header {
            background: #4a90e2;
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 1.5rem;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .breadcrumb {
            background: white;
            padding: 1rem 0;
            border-bottom: 1px solid #e1e8ed;
        }
        
        .breadcrumb-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            color: #666;
            font-size: 0.9rem;
        }
        
        .main-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .page-header {
            background: white;
            border-radius: 8px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .page-title {
            color: #2c3e50;
            font-size: 2rem;
            margin: 0 0 0.5rem 0;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .page-subtitle {
            color: #7f8c8d;
            margin: 0;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-left: 4px solid #4a90e2;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: #7f8c8d;
            font-size: 0.9rem;
        }
        
        .content-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .card-header {
            background: #f8f9fa;
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #e1e8ed;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-title {
            color: #2c3e50;
            font-size: 1.2rem;
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .filter-tabs {
            display: flex;
            gap: 0.5rem;
        }
        
        .filter-tab {
            padding: 0.5rem 1rem;
            border: 1px solid #e1e8ed;
            background: white;
            color: #666;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .filter-tab.active {
            background: #4a90e2;
            color: white;
            border-color: #4a90e2;
        }
        
        .filter-tab:hover:not(.active) {
            background: #f8f9fa;
        }
        
        .users-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .users-table th {
            background: #f8f9fa;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: #2c3e50;
            border-bottom: 2px solid #e1e8ed;
            font-size: 0.9rem;
        }
        
        .users-table td {
            padding: 1rem;
            border-bottom: 1px solid #f1f3f4;
            vertical-align: middle;
        }
        
        .users-table tr:hover {
            background: #f8f9fa;
        }
        
        .user-info-cell {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #4a90e2, #357abd);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 1.1rem;
        }
        
        .user-details h4 {
            margin: 0 0 0.25rem 0;
            color: #2c3e50;
            font-size: 0.95rem;
        }
        
        .user-details p {
            margin: 0;
            color: #7f8c8d;
            font-size: 0.85rem;
        }
        
        .status-badge {
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .status-active {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .status-suspended {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .status-inactive {
            background: #e2e3e5;
            color: #383d41;
            border: 1px solid #d6d8db;
        }
        
        .role-badge {
            padding: 0.3rem 0.6rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .role-admin {
            background: #dc3545;
            color: white;
        }
        
        .role-user {
            background: #17a2b8;
            color: white;
        }
        
        .role-transport {
            background: #28a745;
            color: white;
        }
        
        .role-traffic {
            background: #fd7e14;
            color: white;
        }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn {
            padding: 0.4rem 0.8rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.8rem;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }
        
        .btn-approve {
            background: #28a745;
            color: white;
        }
        
        .btn-approve:hover {
            background: #218838;
        }
        
        .btn-reject {
            background: #dc3545;
            color: white;
        }
        
        .btn-reject:hover {
            background: #c82333;
        }
        
        .btn-suspend {
            background: #ffc107;
            color: #212529;
        }
        
        .btn-suspend:hover {
            background: #e0a800;
        }
        
        .btn-edit {
            background: #007bff;
            color: white;
        }
        
        .btn-edit:hover {
            background: #0056b3;
        }
        
        .btn-delete {
            background: #6c757d;
            color: white;
        }
        
        .btn-delete:hover {
            background: #545b62;
        }
        
        .add-user-btn {
            background: #28a745;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .add-user-btn:hover {
            background: #218838;
            transform: translateY(-1px);
        }
        
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #7f8c8d;
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        @media (max-width: 768px) {
            .main-container {
                padding: 1rem;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .filter-tabs {
                flex-wrap: wrap;
            }
            
            .users-table {
                font-size: 0.85rem;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    
    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <i class="fas fa-chart-line"></i>
                <span>Concordial Nexus</span>
            </div>
            <div class="user-info">
                <i class="fas fa-user-shield"></i>
                <span>Admin Panel</span>
                <a href="../auth/logout.php" style="color: white; text-decoration: none; margin-left: 1rem;">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </header>
    
    <!-- Breadcrumb -->
    <div class="breadcrumb">
        <div class="breadcrumb-content">
            <i class="fas fa-home"></i> Dashboard / <strong>User Management</strong>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-container">
        
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-users"></i>
                User Management
            </h1>
            <p class="page-subtitle">Manage and monitor all registered users</p>
        </div>
        
        <!-- Success/Error Messages -->
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total']; ?></div>
                <div class="stat-label">All Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['pending']; ?></div>
                <div class="stat-label">Pending Requests</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['approved']; ?></div>
                <div class="stat-label">Approved</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['rejected']; ?></div>
                <div class="stat-label">Rejected</div>
            </div>
        </div>
        
        <!-- Users Table -->
        <div class="content-card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-list"></i>
                    Users (<?php echo count($users); ?>)
                </h3>
                <div class="filter-tabs">
                    <a href="?filter=all" class="filter-tab <?php echo $filter === 'all' ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i>
                        All Users
                        <span style="background: rgba(255,255,255,0.2); padding: 0.2rem 0.5rem; border-radius: 10px; font-size: 0.75rem;">
                            <?php echo $stats['total']; ?>
                        </span>
                    </a>
                    <a href="?filter=pending" class="filter-tab <?php echo $filter === 'pending' ? 'active' : ''; ?>">
                        <i class="fas fa-clock"></i>
                        Pending Requests
                        <span style="background: rgba(255,255,255,0.2); padding: 0.2rem 0.5rem; border-radius: 10px; font-size: 0.75rem;">
                            <?php echo $stats['pending']; ?>
                        </span>
                    </a>
                    <a href="?filter=approved" class="filter-tab <?php echo $filter === 'approved' ? 'active' : ''; ?>">
                        <i class="fas fa-check"></i>
                        Approved
                        <span style="background: rgba(255,255,255,0.2); padding: 0.2rem 0.5rem; border-radius: 10px; font-size: 0.75rem;">
                            <?php echo $stats['approved']; ?>
                        </span>
                    </a>
                    <a href="?filter=rejected" class="filter-tab <?php echo $filter === 'rejected' ? 'active' : ''; ?>">
                        <i class="fas fa-times"></i>
                        Rejected
                        <span style="background: rgba(255,255,255,0.2); padding: 0.2rem 0.5rem; border-radius: 10px; font-size: 0.75rem;">
                            <?php echo $stats['rejected']; ?>
                        </span>
                    </a>
                </div>
            </div>
            
            <div style="overflow-x: auto;">
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Approval</th>
                            <th>Status</th>
                            <th>Registered</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($users)): ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo str_pad($user['id'], 4, '0', STR_PAD_LEFT); ?></td>
                                    <td>
                                        <div class="user-info-cell">
                                            <div class="user-avatar">
                                                <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                                            </div>
                                            <div class="user-details">
                                                <h4><?php echo htmlspecialchars(explode(' ', $user['full_name'])[0]); ?></h4>
                                                <p><?php echo htmlspecialchars($user['email']); ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <span class="role-badge role-<?php echo $user['user_type']; ?>">
                                            <?php echo ucfirst($user['user_type']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($user['status'] === 'active'): ?>
                                            <span class="status-badge status-active">
                                                <i class="fas fa-check"></i> Approved
                                            </span>
                                        <?php else: ?>
                                            <span class="status-badge status-pending">
                                                <i class="fas fa-clock"></i> Pending
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $user['status']; ?>">
                                            <?php 
                                            $status_icons = [
                                                'pending' => 'clock',
                                                'active' => 'check-circle',
                                                'suspended' => 'ban',
                                                'inactive' => 'times-circle'
                                            ];
                                            ?>
                                            <i class="fas fa-<?php echo $status_icons[$user['status']]; ?>"></i>
                                            <?php echo ucfirst($user['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <?php if ($user['status'] === 'pending'): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <input type="hidden" name="welcome_bonus" value="500">
                                                    <button type="submit" name="approve_user" class="btn btn-approve">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </form>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <button type="submit" name="reject_user" class="btn btn-reject">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </form>
                                            <?php elseif ($user['status'] === 'active'): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <button type="submit" name="suspend_user" class="btn btn-suspend">
                                                        <i class="fas fa-ban"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            <button class="btn btn-edit" onclick="window.location.href='edit-user.php?id=<?php echo $user['id']; ?>'">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-delete" onclick="deleteUser(<?php echo $user['id']; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9">
                                    <div class="empty-state">
                                        <i class="fas fa-users"></i>
                                        <h4>No Users Found</h4>
                                        <p>No users match the current filter criteria.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <script>
        function editUser(userId) {
            alert('Edit user functionality will be implemented soon. User ID: ' + userId);
        }
        
        function deleteUser(userId) {
            if (confirm('Are you sure you want to delete this user?')) {
                alert('Delete user functionality will be implemented soon. User ID: ' + userId);
            }
        }
        
        // Auto-refresh every 30 seconds
        setTimeout(function() {
            location.reload();
        }, 30000);
    </script>
</body>
</html>
    <!-- Header -->
    <header class="transport-header">
        <nav class="transport-nav">
            <div class="transport-logo">
                <i class="fas fa-shield-alt"></i>
                Admin Panel - Concordial Nexus
            </div>
            <ul class="transport-menu">
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="users.php" class="active"><i class="fas fa-users"></i> Users</a></li>
                <li><a href="transactions.php"><i class="fas fa-exchange-alt"></i> Transactions</a></li>
                <li><a href="invitations.php"><i class="fas fa-ticket-alt"></i> Invitations</a></li>
                <li><a href="profile.php"><i class="fas fa-user-shield"></i> Profile</a></li>
                <li><a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>
    </header>

    <!-- Main Content -->
    <div class="page-container">
        <!-- Page Header -->
        <div class="page-header">
            <div class="page-title">
                <div class="page-icon">
                    <i class="fas fa-users"></i>
                </div>
                <h1>User Management</h1>
            </div>
            <div style="display: flex; gap: 1rem;">
                <a href="dashboard.php" class="btn">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
                <a href="edit-user.php?action=new" class="btn success">
                    <i class="fas fa-plus"></i> Register New User
                </a>
            </div>
        </div>

        <!-- Success Message -->
        <?php if (isset($success_message)): ?>
            <div class="alert">
                <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <!-- Search Section -->
        <div class="search-section">
            <form method="GET" class="search-form">
                <input type="text" name="search" class="search-input" placeholder="Search by name, email, phone, or city..." value="<?php echo htmlspecialchars($search); ?>">
                <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
                <button type="submit" class="btn">
                    <i class="fas fa-search"></i> Search
                </button>
            </form>
        </div>

        <!-- Filter Tabs -->
        <div class="filter-tabs">
            <a href="?filter=all&search=<?php echo urlencode($search); ?>" class="filter-tab <?php echo $filter === 'all' ? 'active' : ''; ?>">
                All Users (<?php echo $stats['total']; ?>)
            </a>
            <a href="?filter=pending&search=<?php echo urlencode($search); ?>" class="filter-tab <?php echo $filter === 'pending' ? 'active' : ''; ?>">
                Pending (<?php echo $stats['pending']; ?>)
            </a>
            <a href="?filter=approved&search=<?php echo urlencode($search); ?>" class="filter-tab <?php echo $filter === 'approved' ? 'active' : ''; ?>">
                Approved (<?php echo $stats['approved']; ?>)
            </a>
            <a href="?filter=rejected&search=<?php echo urlencode($search); ?>" class="filter-tab <?php echo $filter === 'rejected' ? 'active' : ''; ?>">
                Rejected (<?php echo $stats['rejected']; ?>)
            </a>
        </div>

        <!-- Users Table -->
        <div class="users-table-container">
            <div class="table-header">
                <h3><i class="fas fa-table"></i> Users List</h3>
                <span><?php echo count($users); ?> users found</span>
            </div>

            <?php if (empty($users)): ?>
                <div style="text-align: center; padding: 3rem; color: #666;">
                    <i class="fas fa-users" style="font-size: 4rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                    <h3>No users found</h3>
                    <p>No users match your current filter criteria.</p>
                </div>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>User ID</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>City</th>
                            <th>Balance</th>
                            <th>Status</th>
                            <th>Registration Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td>
                                    <strong>#<?php echo str_pad($user['id'], 6, '0', STR_PAD_LEFT); ?></strong>
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                                        <div style="width: 32px; height: 32px; border-radius: 50%; background: #4a90e2; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 0.8rem;">
                                            <?php echo strtoupper(substr($user['full_name'], 0, 2)); ?>
                                        </div>
                                        <div>
                                            <div style="font-weight: 600;"><?php echo htmlspecialchars($user['full_name']); ?></div>
                                            <?php if ($user['invitation_used_code']): ?>
                                                <div style="font-size: 0.75rem; color: #666;">
                                                    Invited: <?php echo $user['invitation_used_code']; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['phone'] ?: 'Not provided'); ?></td>
                                <td><?php echo htmlspecialchars($user['city']); ?></td>
                                <td>
                                    <strong>Br<?php echo number_format($user['account_balance'], 2); ?></strong>
                                </td>
                                <td>
                                    <span class="badge <?php 
                                        echo $user['status'] === 'active' ? 'success' : 
                                            ($user['status'] === 'pending' ? 'warning' : 
                                            ($user['status'] === 'suspended' ? 'danger' : 'secondary')); 
                                    ?>">
                                        <?php echo ucfirst($user['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div><?php echo date('M j, Y', strtotime($user['created_at'])); ?></div>
                                    <div style="font-size: 0.75rem; color: #666;">
                                        <?php echo date('H:i', strtotime($user['created_at'])); ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="edit-user.php?id=<?php echo $user['id']; ?>" class="btn btn-sm">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        
                                        <?php if ($user['status'] === 'pending'): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <input type="hidden" name="welcome_bonus" value="500">
                                                <button type="submit" name="approve_user" class="btn btn-sm success" onclick="return confirm('Approve this user and give Br500 welcome bonus?')">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" name="reject_user" class="btn btn-sm" style="background: #e74c3c;" onclick="return confirm('Reject this user?')">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </form>
                                        <?php elseif ($user['status'] === 'active'): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" name="suspend_user" class="btn btn-sm" style="background: #f39c12;" onclick="return confirm('Suspend this user?')">
                                                    <i class="fas fa-ban"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Quick Stats -->
        <div style="margin-top: 2rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
            <div style="background: white; padding: 1.5rem; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center;">
                <div style="font-size: 2rem; font-weight: bold; color: #4a90e2;"><?php echo $stats['total']; ?></div>
                <div style="color: #666;">Total Users</div>
            </div>
            <div style="background: white; padding: 1.5rem; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center;">
                <div style="font-size: 2rem; font-weight: bold; color: #f39c12;"><?php echo $stats['pending']; ?></div>
                <div style="color: #666;">Pending Approval</div>
            </div>
            <div style="background: white; padding: 1.5rem; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center;">
                <div style="font-size: 2rem; font-weight: bold; color: #27ae60;"><?php echo $stats['approved']; ?></div>
                <div style="color: #666;">Active Users</div>
            </div>
            <div style="background: white; padding: 1.5rem; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center;">
                <div style="font-size: 2rem; font-weight: bold; color: #e74c3c;"><?php echo $stats['rejected']; ?></div>
                <div style="color: #666;">Rejected</div>
            </div>
        </div>
    </div>
</body>
</html>