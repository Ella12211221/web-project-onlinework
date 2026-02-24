<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

$success_message = '';
$error_message = '';

try {
    $pdo = new PDO("mysql:host=localhost;dbname=concordial_nexus;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get admin info
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $admin = $stmt->fetch();
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $address = trim($_POST['address'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $country = trim($_POST['country'] ?? '');
        $admin_department = $_POST['admin_department'] ?? null;
        
        // Check if email is already taken by another user
        $email_check = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $email_check->execute([$email, $_SESSION['user_id']]);
        
        if ($email_check->fetch()) {
            $error_message = "Email address is already taken by another user.";
        } else {
            // Update profile
            $update_stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, address = ?, city = ?, country = ?, admin_department = ?, updated_at = NOW() WHERE id = ?");
            if ($update_stmt->execute([$full_name, $email, $address, $city, $country, $admin_department, $_SESSION['user_id']])) {
                $success_message = "Admin profile updated successfully!";
                // Refresh admin data
                $stmt->execute([$_SESSION['user_id']]);
                $admin = $stmt->fetch();
            } else {
                $error_message = "Failed to update profile. Please try again.";
            }
        }
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
    <title>Admin Profile - Concordial Nexus</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        input, select, textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            box-sizing: border-box;
        }
        input:focus, select:focus, textarea:focus {
            border-color: #4a90e2;
            outline: none;
        }
        button {
            background: #4a90e2;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            width: 100%;
        }
        button:hover {
            background: #357abd;
        }
        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .nav-links {
            text-align: center;
            margin-top: 30px;
        }
        .nav-links a {
            display: inline-block;
            margin: 0 10px;
            padding: 10px 20px;
            background: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
        .nav-links a:hover {
            background: #5a6268;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🛡️ Admin Profile - Concordial Nexus</h1>
        
        <?php if ($success_message): ?>
            <div class="message success">
                ✅ <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="message error">
                ❌ <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="full_name">Full Name *</label>
                <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($admin['full_name'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email Address *</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($admin['email'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="admin_department">Department</label>
                <select id="admin_department" name="admin_department">
                    <option value="">Select Department</option>
                    <option value="administration" <?php echo ($admin['admin_department'] ?? '') === 'administration' ? 'selected' : ''; ?>>Administration</option>
                    <option value="trading" <?php echo ($admin['admin_department'] ?? '') === 'trading' ? 'selected' : ''; ?>>Trading Operations</option>
                    <option value="finance" <?php echo ($admin['admin_department'] ?? '') === 'finance' ? 'selected' : ''; ?>>Finance & Accounting</option>
                    <option value="customer_service" <?php echo ($admin['admin_department'] ?? '') === 'customer_service' ? 'selected' : ''; ?>>Customer Service</option>
                    <option value="compliance" <?php echo ($admin['admin_department'] ?? '') === 'compliance' ? 'selected' : ''; ?>>Compliance & Risk</option>
                    <option value="it" <?php echo ($admin['admin_department'] ?? '') === 'it' ? 'selected' : ''; ?>>IT & Technology</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="address">Address</label>
                <textarea id="address" name="address" rows="3" placeholder="Your full address"><?php echo htmlspecialchars($admin['address'] ?? ''); ?></textarea>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label for="city">City</label>
                    <input type="text" id="city" name="city" value="<?php echo htmlspecialchars($admin['city'] ?? 'Addis Ababa'); ?>">
                </div>
                <div class="form-group">
                    <label for="country">Country</label>
                    <input type="text" id="country" name="country" value="<?php echo htmlspecialchars($admin['country'] ?? 'Ethiopia'); ?>">
                </div>
            </div>
            
            <button type="submit" name="update_profile">
                💾 Update Profile
            </button>
        </form>
        
        <div class="nav-links">
            <a href="dashboard.php">📊 Dashboard</a>
            <a href="withdrawal-management.php">💰 Withdrawals</a>
            <a href="users.php">👥 Users</a>
            <a href="../auth/logout.php">🚪 Logout</a>
        </div>
    </div>
</body>
</html>