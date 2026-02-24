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

// Get user ID from URL
$user_id = $_GET['id'] ?? null;
if (!$user_id) {
    header('Location: users.php');
    exit();
}

try {
    $pdo = new PDO("mysql:host=localhost;dbname=concordial_nexus;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $full_name = $_POST['full_name'];
        $email = $_POST['email'];
        $user_type = $_POST['user_type'];
        $status = $_POST['status'];
        $account_balance = floatval($_POST['account_balance']);
        $password = $_POST['password'];
        
        // Update user
        if (!empty($password)) {
            // Update with new password
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, user_type = ?, status = ?, account_balance = ?, password = ? WHERE id = ?");
            $stmt->execute([$full_name, $email, $user_type, $status, $account_balance, $password, $user_id]);
        } else {
            // Update without changing password
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, user_type = ?, status = ?, account_balance = ? WHERE id = ?");
            $stmt->execute([$full_name, $email, $user_type, $status, $account_balance, $user_id]);
        }
        
        $success_message = "User updated successfully!";
    }
    
    // Get user data
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        header('Location: users.php');
        exit();
    }
    
} catch(PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - Concordial Nexus</title>
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
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .edit-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .card-header {
            background: #f8f9fa;
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #e1e8ed;
        }
        
        .card-title {
            color: #2c3e50;
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .card-body {
            padding: 2rem;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .form-input {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e1e8ed;
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
            box-sizing: border-box;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #4a90e2;
        }
        
        .form-select {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e1e8ed;
            border-radius: 6px;
            font-size: 1rem;
            background: white;
            cursor: pointer;
            box-sizing: border-box;
        }
        
        .form-select:focus {
            outline: none;
            border-color: #4a90e2;
        }
        
        .btn-group {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .btn {
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-primary {
            background: #4a90e2;
            color: white;
        }
        
        .btn-primary:hover {
            background: #357abd;
            transform: translateY(-1px);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #545b62;
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
        
        .user-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #4a90e2, #357abd);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 2rem;
            margin: 0 auto 1rem;
        }
        
        .user-info {
            text-align: center;
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .help-text {
            font-size: 0.85rem;
            color: #6c757d;
            margin-top: 0.25rem;
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
            <i class="fas fa-home"></i> Dashboard / 
            <a href="users.php" style="color: #4a90e2; text-decoration: none;">User Management</a> / 
            <strong>Edit User</strong>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-container">
        
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
        
        <!-- Edit User Card -->
        <div class="edit-card">
            <div class="card-header">
                <h1 class="card-title">
                    <i class="fas fa-user-edit"></i>
                    Edit User
                </h1>
            </div>
            
            <div class="card-body">
                <!-- User Info Display -->
                <div class="user-info">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                    </div>
                    <h3 style="margin: 0 0 0.5rem 0; color: #2c3e50;"><?php echo htmlspecialchars($user['full_name']); ?></h3>
                    <p style="margin: 0; color: #6c757d;">User ID: #<?php echo str_pad($user['id'], 4, '0', STR_PAD_LEFT); ?></p>
                    <p style="margin: 0; color: #6c757d;">Registered: <?php echo date('M j, Y', strtotime($user['created_at'])); ?></p>
                </div>
                
                <!-- Edit Form -->
                <form method="POST">
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label" for="full_name">
                                <i class="fas fa-user"></i> Full Name
                            </label>
                            <input type="text" id="full_name" name="full_name" class="form-input" 
                                   value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="email">
                                <i class="fas fa-envelope"></i> Email Address
                            </label>
                            <input type="email" id="email" name="email" class="form-input" 
                                   value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="user_type">
                                <i class="fas fa-user-tag"></i> User Type
                            </label>
                            <select id="user_type" name="user_type" class="form-select" required>
                                <option value="user" <?php echo $user['user_type'] === 'user' ? 'selected' : ''; ?>>Regular User</option>
                                <option value="admin" <?php echo $user['user_type'] === 'admin' ? 'selected' : ''; ?>>Administrator</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="status">
                                <i class="fas fa-toggle-on"></i> Account Status
                            </label>
                            <select id="status" name="status" class="form-select" required>
                                <option value="pending" <?php echo $user['status'] === 'pending' ? 'selected' : ''; ?>>Pending Approval</option>
                                <option value="active" <?php echo $user['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="suspended" <?php echo $user['status'] === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                                <option value="inactive" <?php echo $user['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="account_balance">
                                <i class="fas fa-wallet"></i> Account Balance (Br)
                            </label>
                            <input type="number" id="account_balance" name="account_balance" class="form-input" 
                                   value="<?php echo $user['account_balance']; ?>" step="0.01" min="0">
                            <div class="help-text">Current balance in Ethiopian Birr</div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="password">
                                <i class="fas fa-lock"></i> New Password
                            </label>
                            <input type="password" id="password" name="password" class="form-input" 
                                   placeholder="Leave blank to keep current password">
                            <div class="help-text">Only enter if you want to change the password</div>
                        </div>
                    </div>
                    
                    <div class="btn-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            Update User
                        </button>
                        <a href="users.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i>
                            Back to Users
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>