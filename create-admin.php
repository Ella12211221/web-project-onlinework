<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Admin Account - Breakthrough Trading</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="form-container">
        <h2><i class="fas fa-user-shield"></i> Create Admin Account</h2>
        <p style="color: rgba(255, 255, 255, 0.8); margin-bottom: 2rem;">Create admin account for Breakthrough Online Trading platform</p>
        
        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_once 'config.php';
            
            $email = $_POST['email'];
            $password = $_POST['password']; // Plain text as requested
            $full_name = $_POST['full_name'];
            
            try {
                // Check if admin already exists
                $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
                $check_stmt->execute([$email]);
                
                if ($check_stmt->fetchColumn() > 0) {
                    echo '<div class="alert alert-error">Email already exists!</div>';
                } else {
                    // Create admin account
                    $stmt = $pdo->prepare("INSERT INTO users (email, password, full_name, user_type, city, country, status, email_verified) VALUES (?, ?, ?, 'admin', 'Addis Ababa', 'Ethiopia', 'active', 1)");
                    
                    if ($stmt->execute([$email, $password, $full_name])) {
                        echo '<div class="alert alert-success">Admin account created successfully!</div>';
                        echo '<div class="alert alert-info">Email: ' . htmlspecialchars($email) . '<br>Password: ' . htmlspecialchars($password) . '<br>Type: Admin</div>';
                    } else {
                        echo '<div class="alert alert-error">Failed to create admin account!</div>';
                    }
                }
            } catch(PDOException $e) {
                echo '<div class="alert alert-error">Database error: ' . $e->getMessage() . '</div>';
            }
        }
        ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name" required value="Elias Admin">
            </div>
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required value="elias@gmail.com">
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="text" id="password" name="password" required value="admin123">
                <small style="color: rgba(255, 255, 255, 0.6);">Password will be stored as plain text as requested</small>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn-submit">
                    <i class="fas fa-user-plus"></i> Create Admin Account
                </button>
            </div>
        </form>
        
        <div style="text-align: center; margin-top: 2rem;">
            <a href="setup.php" style="color: #ffd700; text-decoration: none; margin-right: 1rem;">
                <i class="fas fa-arrow-left"></i> Back to Setup
            </a>
            <a href="test-connection.php" style="color: #ffd700; text-decoration: none;">
                <i class="fas fa-plug"></i> Test Connection
            </a>
        </div>
    </div>
</body>
</html>