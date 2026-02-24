<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

$message = '';

try {
    $pdo = new PDO("mysql:host=localhost;dbname=concordial_nexus;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get admin info
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $admin = $stmt->fetch();
    
    if (!$admin) {
        die("Admin user not found");
    }
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $address = trim($_POST['address'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $country = trim($_POST['country'] ?? '');
        
        // Check if admin_department column exists
        $columns = $pdo->query("DESCRIBE users")->fetchAll();
        $existing_columns = array_column($columns, 'Field');
        
        if (in_array('admin_department', $existing_columns)) {
            $admin_department = $_POST['admin_department'] ?? null;
            $update_stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, address = ?, city = ?, country = ?, admin_department = ?, updated_at = NOW() WHERE id = ?");
            $result = $update_stmt->execute([$full_name, $email, $address, $city, $country, $admin_department, $_SESSION['user_id']]);
        } else {
            $update_stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, address = ?, city = ?, country = ?, updated_at = NOW() WHERE id = ?");
            $result = $update_stmt->execute([$full_name, $email, $address, $city, $country, $_SESSION['user_id']]);
        }
        
        if ($result) {
            $message = "✅ Profile updated successfully!";
            // Refresh admin data
            $stmt->execute([$_SESSION['user_id']]);
            $admin = $stmt->fetch();
        } else {
            $message = "❌ Failed to update profile";
        }
    }
    
} catch(PDOException $e) {
    $message = "❌ Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile - Concordial Nexus</title>
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
            max-width: 900px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        
        .header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .header h1 {
            color: #333;
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        
        .header p {
            color: #666;
            font-size: 1.1rem;
        }
        
        .message {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 30px;
            text-align: center;
            font-weight: 600;
        }
        
        .success {
            background: #d4edda;
            color: #155724;
            border: 2px solid #c3e6cb;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 2px solid #f5c6cb;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .form-section {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 15px;
            border: 2px solid #e9ecef;
        }
        
        .form-section h3 {
            color: #495057;
            margin-bottom: 20px;
            font-size: 1.3rem;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #495057;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #dee2e6;
            border-radius: 10px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #4a90e2;
            box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.1);
        }
        
        .btn {
            background: linear-gradient(135deg, #4a90e2, #357abd);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
            width: 100%;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(74, 144, 226, 0.3);
        }
        
        .nav-links {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 40px;
            flex-wrap: wrap;
        }
        
        .nav-links a {
            padding: 12px 25px;
            background: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: background 0.3s;
        }
        
        .nav-links a:hover {
            background: #5a6268;
        }
        
        .nav-links a.primary {
            background: linear-gradient(135deg, #28a745, #20c997);
        }
        
        .nav-links a.primary:hover {
            background: linear-gradient(135deg, #218838, #1ea080);
        }
        
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .container {
                padding: 20px;
            }
            
            .header h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🛡️ Admin Profile</h1>
            <p>Concordial Nexus - Administrative Panel</p>
        </div>
        
        <?php if ($message): ?>
            <div class="message <?php echo strpos($message, '✅') !== false ? 'success' : 'error'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-grid">
                <div class="form-section">
                    <h3>👤 Personal Information</h3>
                    
                    <div class="form-group">
                        <label for="full_name">Full Name *</label>
                        <input type="text" id="full_name" name="full_name" 
                               value="<?php echo htmlspecialchars($admin['full_name'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" 
                               value="<?php echo htmlspecialchars($admin['email'] ?? ''); ?>" required>
                    </div>
                    
                    <?php 
                    // Check if admin_department column exists
                    $columns = $pdo->query("DESCRIBE users")->fetchAll();
                    $existing_columns = array_column($columns, 'Field');
                    if (in_array('admin_department', $existing_columns)): 
                    ?>
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
                    <?php endif; ?>
                </div>
                
                <div class="form-section">
                    <h3>📍 Location Information</h3>
                    
                    <div class="form-group">
                        <label for="address">Address</label>
                        <textarea id="address" name="address" rows="3" 
                                  placeholder="Your full address"><?php echo htmlspecialchars($admin['address'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="city">City</label>
                        <input type="text" id="city" name="city" 
                               value="<?php echo htmlspecialchars($admin['city'] ?? 'Addis Ababa'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="country">Country</label>
                        <input type="text" id="country" name="country" 
                               value="<?php echo htmlspecialchars($admin['country'] ?? 'Ethiopia'); ?>">
                    </div>
                </div>
            </div>
            
            <button type="submit" class="btn">
                💾 Update Admin Profile
            </button>
        </form>
        
        <div class="nav-links">
            <a href="dashboard.php" class="primary">📊 Dashboard</a>
            <a href="withdrawal-management.php">💰 Withdrawals</a>
            <a href="users.php">👥 Users</a>
            <a href="transactions.php">📈 Transactions</a>
            <a href="../auth/logout.php">🚪 Logout</a>
        </div>
    </div>
</body>
</html>