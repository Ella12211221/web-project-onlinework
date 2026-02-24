<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Concordial Nexus</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php
    session_start();
    
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../auth/login.php');
        exit();
    }
    
    try {
        $pdo = new PDO("mysql:host=localhost;dbname=concordial_nexus;charset=utf8mb4", "root", "");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Get user info
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        // Ensure all optional fields exist (backward compatibility)
        $optional_fields = ['profile_photo', 'address', 'city', 'country', 'phone', 'date_of_birth', 'gender'];
        foreach ($optional_fields as $field) {
            if (!isset($user[$field])) {
                $user[$field] = null;
            }
        }
        
        // Set default values
        if (empty($user['city'])) $user['city'] = 'Addis Ababa';
        if (empty($user['country'])) $user['country'] = 'Ethiopia';
        
        // Handle form submissions
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            
            // Handle password change ONLY
            if (isset($_POST['change_password'])) {
                $current_password = $_POST['current_password'];
                $new_password = $_POST['new_password'];
                $confirm_password = $_POST['confirm_password'];
                
                // Verify current password (assuming plain text for now, should be hashed in production)
                if ($current_password !== $user['password']) {
                    $error_message = "Current password is incorrect.";
                } elseif ($new_password !== $confirm_password) {
                    $error_message = "New passwords do not match.";
                } elseif (strlen($new_password) < 6) {
                    $error_message = "New password must be at least 6 characters long.";
                } else {
                    // Update password (should be hashed in production)
                    $password_stmt = $pdo->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
                    if ($password_stmt->execute([$new_password, $_SESSION['user_id']])) {
                        $success_message = "Password changed successfully!";
                        // Refresh user data
                        $stmt->execute([$_SESSION['user_id']]);
                        $user = $stmt->fetch();
                    } else {
                        $error_message = "Failed to change password. Please try again.";
                    }
                }
            }
        }
        
    } catch(PDOException $e) {
        $error_message = "Database error: " . $e->getMessage();
    }
    ?>
    
    <header class="header">
        <nav class="navbar">
            <div class="nav-container">
                <div class="nav-logo">
                    <a href="../index.html"><i class="fas fa-chart-line"></i> Concordial Nexus</a>
                </div>
                <ul class="nav-menu">
                    <li class="nav-item"><a href="index.php" class="nav-link">Dashboard</a></li>
                    <li class="nav-item"><a href="transactions.php" class="nav-link">Transactions</a></li>
                    <li class="nav-item"><a href="investments.php" class="nav-link">Investments</a></li>
                    <li class="nav-item"><a href="profile.php" class="nav-link active">Profile</a></li>
                    <li class="nav-item"><a href="../auth/logout.php" class="nav-link">Logout</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <main style="margin-top: 100px; padding: 2rem;">
        <div class="container">
            <div style="background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(20px); border-radius: 20px; padding: 3rem; border: 1px solid rgba(34, 139, 34, 0.2);">
                
                <div style="text-align: center; margin-bottom: 3rem;">
                    <h1 style="color: #ffd700; margin-bottom: 1rem;">
                        <i class="fas fa-user-circle"></i> My Profile
                    </h1>
                    <p style="color: rgba(255, 255, 255, 0.8);">
                        Manage your personal information and account settings
                    </p>
                    
                    <!-- Back to Dashboard Button -->
                    <div style="margin-top: 2rem;">
                        <a href="index.php" style="background: linear-gradient(45deg, #228b22, #ffd700); color: #0a0e1a; padding: 1rem 2rem; border-radius: 10px; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 0.5rem; box-shadow: 0 4px 15px rgba(34, 139, 34, 0.3); transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                            <i class="fas fa-arrow-left"></i> Back to Dashboard
                        </a>
                    </div>
                </div>

                <?php if (isset($success_message)): ?>
                    <div style="background: rgba(34, 139, 34, 0.2); color: #228b22; padding: 1rem; border-radius: 8px; margin-bottom: 2rem; text-align: center; border: 1px solid rgba(34, 139, 34, 0.3);">
                        <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($error_message)): ?>
                    <div style="background: rgba(255, 82, 82, 0.2); color: #ff5252; padding: 1rem; border-radius: 8px; margin-bottom: 2rem; text-align: center; border: 1px solid rgba(255, 82, 82, 0.3);">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>

                <!-- Profile Photo Section - READ ONLY DISPLAY -->
                <div style="background: rgba(255, 255, 255, 0.05); border-radius: 15px; padding: 2rem; margin-bottom: 3rem; border: 1px solid rgba(34, 139, 34, 0.2); text-align: center;">
                    <h3 style="color: #ffd700; margin-bottom: 2rem;">
                        <i class="fas fa-user-circle"></i> Profile Picture
                    </h3>
                    
                    <div style="margin-bottom: 1rem;">
                        <div style="width: 150px; height: 150px; border-radius: 50%; background: linear-gradient(45deg, #228b22, #ffd700); display: flex; align-items: center; justify-content: center; margin: 0 auto; border: 3px solid #ffd700;">
                            <i class="fas fa-user" style="font-size: 4rem; color: #0a0e1a;"></i>
                        </div>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 3rem;">
                    
                    <!-- Personal Information - READ ONLY -->
                    <div style="background: rgba(255, 255, 255, 0.05); border-radius: 15px; padding: 2rem; border: 1px solid rgba(34, 139, 34, 0.2);">
                        <h3 style="color: #ffd700; margin-bottom: 2rem; text-align: center;">
                            <i class="fas fa-user"></i> Personal Information
                        </h3>
                        
                        <div style="background: rgba(34, 139, 34, 0.1); border: 1px solid rgba(34, 139, 34, 0.3); border-radius: 10px; padding: 1.5rem;">
                            <div style="display: grid; gap: 1rem;">
                                <div style="display: flex; justify-content: space-between; padding: 0.75rem 0; border-bottom: 1px solid rgba(34, 139, 34, 0.2);">
                                    <span style="color: rgba(255, 255, 255, 0.8); font-weight: 600;">Full Name:</span>
                                    <span style="color: #ffd700; font-weight: 600;"><?php echo htmlspecialchars($user['full_name']); ?></span>
                                </div>
                                
                                <div style="display: flex; justify-content: space-between; padding: 0.75rem 0; border-bottom: 1px solid rgba(34, 139, 34, 0.2);">
                                    <span style="color: rgba(255, 255, 255, 0.8); font-weight: 600;">Email:</span>
                                    <span style="color: #ffd700; font-weight: 600;"><?php echo htmlspecialchars($user['email']); ?></span>
                                </div>
                                
                                <div style="display: flex; justify-content: space-between; padding: 0.75rem 0; border-bottom: 1px solid rgba(34, 139, 34, 0.2);">
                                    <span style="color: rgba(255, 255, 255, 0.8); font-weight: 600;">User ID:</span>
                                    <span style="color: #228b22; font-weight: 600;">#<?php echo str_pad($user['id'], 6, '0', STR_PAD_LEFT); ?></span>
                                </div>
                                
                                <div style="display: flex; justify-content: space-between; padding: 0.75rem 0; border-bottom: 1px solid rgba(34, 139, 34, 0.2);">
                                    <span style="color: rgba(255, 255, 255, 0.8); font-weight: 600;">Account Status:</span>
                                    <span style="color: <?php echo $user['status'] === 'active' ? '#228b22' : '#ffd700'; ?>; font-weight: 600; text-transform: uppercase;">
                                        <?php echo htmlspecialchars($user['status']); ?>
                                    </span>
                                </div>
                                
                                <div style="display: flex; justify-content: space-between; padding: 0.75rem 0; border-bottom: 1px solid rgba(34, 139, 34, 0.2);">
                                    <span style="color: rgba(255, 255, 255, 0.8); font-weight: 600;">Account Balance:</span>
                                    <span style="color: #228b22; font-weight: 600; font-size: 1.2rem;">Br<?php echo number_format($user['account_balance'], 2); ?></span>
                                </div>
                                
                                <div style="display: flex; justify-content: space-between; padding: 0.75rem 0; border-bottom: 1px solid rgba(34, 139, 34, 0.2);">
                                    <span style="color: rgba(255, 255, 255, 0.8); font-weight: 600;">Member Since:</span>
                                    <span style="color: #ffd700; font-weight: 600;"><?php echo date('M j, Y', strtotime($user['created_at'])); ?></span>
                                </div>
                                
                                <?php if ($user['referral_code']): ?>
                                <div style="display: flex; justify-content: space-between; padding: 0.75rem 0;">
                                    <span style="color: rgba(255, 255, 255, 0.8); font-weight: 600;">Referral Code:</span>
                                    <span style="color: #ffd700; font-weight: 600;"><?php echo htmlspecialchars($user['referral_code']); ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div style="margin-top: 1.5rem; padding: 1rem; background: rgba(255, 215, 0, 0.1); border: 1px solid rgba(255, 215, 0, 0.3); border-radius: 8px; text-align: center;">
                            <p style="color: rgba(255, 255, 255, 0.8); font-size: 0.9rem; margin: 0;">
                                <i class="fas fa-info-circle"></i> To update your personal information, please contact support.
                            </p>
                        </div>
                    </div>

                    <!-- Security Settings -->
                    <div style="background: rgba(255, 255, 255, 0.05); border-radius: 15px; padding: 2rem; border: 1px solid rgba(34, 139, 34, 0.2);">
                        <h3 style="color: #ffd700; margin-bottom: 2rem; text-align: center;">
                            <i class="fas fa-shield-alt"></i> Security Settings
                        </h3>
                        
                        <!-- Change Password -->
                        <div style="background: rgba(255, 215, 0, 0.1); border: 1px solid rgba(255, 215, 0, 0.3); border-radius: 10px; padding: 1.5rem; margin-bottom: 2rem;">
                            <h4 style="color: #ffd700; margin-bottom: 1.5rem;">
                                <i class="fas fa-key"></i> Change Password
                            </h4>
                            
                            <form method="POST">
                                <div style="margin-bottom: 1rem;">
                                    <label style="color: rgba(255, 255, 255, 0.9); display: block; margin-bottom: 0.5rem;">Current Password</label>
                                    <input type="password" name="current_password" required
                                           style="width: 100%; padding: 1rem; border: 1px solid rgba(255, 215, 0, 0.3); border-radius: 8px; background: rgba(255, 255, 255, 0.1); color: white; font-size: 1rem;">
                                </div>
                                
                                <div style="margin-bottom: 1rem;">
                                    <label style="color: rgba(255, 255, 255, 0.9); display: block; margin-bottom: 0.5rem;">New Password</label>
                                    <input type="password" name="new_password" required minlength="6"
                                           style="width: 100%; padding: 1rem; border: 1px solid rgba(255, 215, 0, 0.3); border-radius: 8px; background: rgba(255, 255, 255, 0.1); color: white; font-size: 1rem;">
                                </div>
                                
                                <div style="margin-bottom: 1.5rem;">
                                    <label style="color: rgba(255, 255, 255, 0.9); display: block; margin-bottom: 0.5rem;">Confirm New Password</label>
                                    <input type="password" name="confirm_password" required minlength="6"
                                           style="width: 100%; padding: 1rem; border: 1px solid rgba(255, 215, 0, 0.3); border-radius: 8px; background: rgba(255, 255, 255, 0.1); color: white; font-size: 1rem;">
                                </div>
                                
                                <button type="submit" name="change_password"
                                        style="width: 100%; background: linear-gradient(45deg, #ffd700, #ffed4e); color: #0a0e1a; padding: 1rem; border: none; border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer;">
                                    <i class="fas fa-lock"></i> Change Password
                                </button>
                            </form>
                        </div>
                        
                        <!-- Account Information -->
                        <div style="background: rgba(34, 139, 34, 0.1); border: 1px solid rgba(34, 139, 34, 0.3); border-radius: 10px; padding: 1.5rem;">
                            <h4 style="color: #228b22; margin-bottom: 1.5rem;">
                                <i class="fas fa-info-circle"></i> Account Information
                            </h4>
                            
                            <div style="display: grid; gap: 1rem;">
                                <div style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid rgba(34, 139, 34, 0.2);">
                                    <span style="color: rgba(255, 255, 255, 0.8);">User ID:</span>
                                    <span style="color: #ffd700; font-weight: 600;">#<?php echo str_pad($user['id'], 6, '0', STR_PAD_LEFT); ?></span>
                                </div>
                                
                                <div style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid rgba(34, 139, 34, 0.2);">
                                    <span style="color: rgba(255, 255, 255, 0.8);">Account Status:</span>
                                    <span style="color: <?php echo $user['status'] === 'active' ? '#228b22' : '#ffd700'; ?>; font-weight: 600; text-transform: capitalize;">
                                        <?php echo $user['status']; ?>
                                    </span>
                                </div>
                                
                                <div style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid rgba(34, 139, 34, 0.2);">
                                    <span style="color: rgba(255, 255, 255, 0.8);">Member Since:</span>
                                    <span style="color: #ffd700; font-weight: 600;"><?php echo date('M j, Y', strtotime($user['created_at'])); ?></span>
                                </div>
                                
                                <div style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid rgba(34, 139, 34, 0.2);">
                                    <span style="color: rgba(255, 255, 255, 0.8);">Last Updated:</span>
                                    <span style="color: #ffd700; font-weight: 600;"><?php echo date('M j, Y H:i', strtotime($user['updated_at'])); ?></span>
                                </div>
                                
                                <?php if ($user['referral_code']): ?>
                                <div style="display: flex; justify-content: space-between; padding: 0.5rem 0;">
                                    <span style="color: rgba(255, 255, 255, 0.8);">Referral Code:</span>
                                    <span style="color: #228b22; font-weight: 600; font-family: monospace;"><?php echo $user['referral_code']; ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Bottom Navigation -->
                <div style="text-align: center; margin-top: 3rem; padding-top: 2rem; border-top: 1px solid rgba(34, 139, 34, 0.2);">
                    <a href="index.php" style="background: linear-gradient(45deg, #228b22, #ffd700); color: #0a0e1a; padding: 1rem 2rem; border-radius: 10px; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 0.5rem; box-shadow: 0 4px 15px rgba(34, 139, 34, 0.3); transition: transform 0.2s; margin-right: 1rem;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                    
                    <a href="transactions.php" style="background: rgba(255, 215, 0, 0.2); color: #ffd700; padding: 1rem 2rem; border: 1px solid rgba(255, 215, 0, 0.3); border-radius: 10px; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 0.5rem; transition: transform 0.2s; margin-right: 1rem;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                        <i class="fas fa-exchange-alt"></i> View Transactions
                    </a>
                    
                    <a href="investments.php" style="background: rgba(34, 139, 34, 0.2); color: #228b22; padding: 1rem 2rem; border: 1px solid rgba(34, 139, 34, 0.3); border-radius: 10px; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 0.5rem; transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                        <i class="fas fa-chart-line"></i> View Investments
                    </a>
                </div>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <div class="footer-bottom">
                <p>&copy; 2026 Concordial Nexus. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // Password confirmation validation
        document.querySelector('input[name="confirm_password"]').addEventListener('input', function() {
            const newPassword = document.querySelector('input[name="new_password"]').value;
            const confirmPassword = this.value;
            
            if (newPassword !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
        
        // Photo preview
        document.getElementById('photo-input').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    // You could add a preview here if needed
                };
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>