<?php
// Start session at the very beginning
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Handle form processing FIRST, before any HTML output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Simple database connection
        $pdo = new PDO("mysql:host=localhost;dbname=concordial_nexus;charset=utf8mb4", "root", "");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $email = $_POST['email'];
        $password = $_POST['password'];
        $full_name = $_POST['full_name'];
        $invitation_code = trim($_POST['invitation_code']);
        $referrer_code = $_POST['referrer_code'] ?? null;
        
        // Validate invitation code only if provided
        $invitation = null;
        if (!empty($invitation_code)) {
            // Check if invitation_codes table exists
            try {
                $code_stmt = $pdo->prepare("SELECT * FROM invitation_codes WHERE code = ? AND is_active = 1 AND (expires_at IS NULL OR expires_at > NOW()) AND current_uses < max_uses");
                $code_stmt->execute([strtoupper($invitation_code)]);
                $invitation = $code_stmt->fetch();
                
                if (!$invitation) {
                    // Check if code exists but is inactive/expired
                    $check_code = $pdo->prepare("SELECT * FROM invitation_codes WHERE code = ?");
                    $check_code->execute([strtoupper($invitation_code)]);
                    $code_exists = $check_code->fetch();
                    
                    if ($code_exists) {
                        if ($code_exists['is_active'] == 0) {
                            $error_message = 'This invitation code has been deactivated. You can register without a code.';
                        } elseif ($code_exists['current_uses'] >= $code_exists['max_uses']) {
                            $error_message = 'This invitation code has reached its maximum uses. You can register without a code.';
                        } elseif ($code_exists['expires_at'] && strtotime($code_exists['expires_at']) < time()) {
                            $error_message = 'This invitation code has expired. You can register without a code.';
                        } else {
                            $error_message = 'Invalid invitation code! You can register without a code.';
                        }
                    } else {
                        $error_message = 'Invalid invitation code! You can register without a code.';
                    }
                }
            } catch (PDOException $e) {
                // If invitation_codes table doesn't exist, just continue without validation
                $invitation = null;
            }
        }
        
        if (!isset($error_message)) {
            // Check if email exists
            $check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
            $check->execute([$email]);
            
            if ($check->fetchColumn() > 0) {
                $error_message = 'Email already exists!';
            } else {
                // Find referrer if referral code provided
                $referred_by = null;
                if ($referrer_code) {
                    $referrer_stmt = $pdo->prepare("SELECT id FROM users WHERE referral_code = ?");
                    $referrer_stmt->execute([$referrer_code]);
                    $referrer = $referrer_stmt->fetch();
                    if ($referrer) {
                        $referred_by = $referrer['id'];
                    }
                }
                
                // Generate unique referral code for new user
                $new_referral_code = strtoupper(substr(md5($email . time()), 0, 8));
                
                // Insert new user with pending status (requires admin approval)
                // No bonus at registration - bonuses given after purchases/investments
                $stmt = $pdo->prepare("INSERT INTO users (email, password, full_name, user_type, invitation_code_used, referral_code, referred_by, account_balance, status) VALUES (?, ?, ?, 'user', ?, ?, ?, 0, 'pending')");
                
                if ($stmt->execute([$email, $password, $full_name, $invitation_code, $new_referral_code, $referred_by])) {
                    $user_id = $pdo->lastInsertId();
                    
                    // Update invitation code usage only if code was provided and table exists
                    if (!empty($invitation_code) && $invitation) {
                        try {
                            $update_code = $pdo->prepare("UPDATE invitation_codes SET current_uses = current_uses + 1, used_by = ?, used_at = NOW() WHERE id = ?");
                            $update_code->execute([$user_id, $invitation['id']]);
                        } catch (PDOException $e) {
                            // Silently continue if invitation_codes table doesn't exist
                        }
                    }
                    
                    // If referred by someone, create a welcome commission
                    if ($referred_by) {
                        try {
                            $welcome_commission = $pdo->prepare("INSERT INTO commissions (user_id, from_user_id, commission_type, amount, description, status) VALUES (?, ?, 'referral', 50.00, 'Welcome bonus for new referral', 'pending')");
                            $welcome_commission->execute([$referred_by, $user_id]);
                        } catch (PDOException $e) {
                            // Silently continue if commissions table doesn't exist
                        }
                    }
                    
                    // Create personal invitation code for the new user
                    try {
                        $personal_code = strtoupper(substr($full_name, 0, 4) . date('y') . rand(100, 999));
                        $personal_description = "Personal invitation code for " . $full_name;
                        
                        // Make sure the personal code is unique
                        $check_personal = $pdo->prepare("SELECT COUNT(*) FROM invitation_codes WHERE code = ?");
                        $check_personal->execute([$personal_code]);
                        
                        // If code exists, generate a new one
                        while ($check_personal->fetchColumn() > 0) {
                            $personal_code = strtoupper(substr($full_name, 0, 4) . date('y') . rand(100, 999));
                            $check_personal->execute([$personal_code]);
                        }
                        
                        // Insert personal invitation code
                        $personal_stmt = $pdo->prepare("INSERT INTO invitation_codes (code, created_by, max_uses, bonus_amount, description, is_active) VALUES (?, ?, 50, 200.00, ?, 1)");
                        $personal_stmt->execute([$personal_code, $user_id, $personal_description]);
                        
                        $user_info['personal_code'] = $personal_code;
                    } catch (PDOException $e) {
                        // Silently continue if invitation_codes table doesn't exist
                    }
                    
                    $success_message = 'Registration successful! Your account is pending admin approval.';
                    $user_info = [
                        'email' => $email,
                        'referral_code' => $new_referral_code
                    ];
                } else {
                    $error_message = 'Registration failed!';
                }
            }
        }
    } catch(PDOException $e) {
        $error_message = 'Database error: ' . $e->getMessage();
        $db_setup_link = true;
        $db_error_details = $e->getMessage();
    }
}

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_type'] === 'admin') {
        header('Location: ../admin/dashboard.php');
        exit();
    } else {
        header('Location: ../dashboard/index.php');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Concordial Nexus</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg, #10b981 0%, #059669 50%, #047857 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }
        
        body::before {
            content: '';
            position: absolute;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(16, 185, 129, 0.3) 0%, transparent 70%);
            top: -250px;
            right: -250px;
            animation: float 6s ease-in-out infinite;
        }
        
        body::after {
            content: '';
            position: absolute;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(4, 120, 87, 0.3) 0%, transparent 70%);
            bottom: -200px;
            left: -200px;
            animation: float 8s ease-in-out infinite reverse;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
        
        .auth-container {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border-radius: 30px;
            padding: 3.5rem 3rem;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.4), 0 0 0 1px rgba(255, 255, 255, 0.1);
            position: relative;
            z-index: 1;
            animation: slideUp 0.6s ease-out;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .auth-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }
        
        .auth-icon {
            background: linear-gradient(135deg, #10b981 0%, #059669 50%, #047857 100%);
            color: white;
            width: 90px;
            height: 90px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2.5rem;
            box-shadow: 0 15px 35px rgba(16, 185, 129, 0.4);
            animation: pulse 2s ease-in-out infinite;
            position: relative;
        }
        
        .auth-icon::before {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: linear-gradient(135deg, #10b981, #059669);
            opacity: 0.3;
            animation: ripple 2s ease-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        @keyframes ripple {
            0% {
                transform: scale(1);
                opacity: 0.3;
            }
            100% {
                transform: scale(1.3);
                opacity: 0;
            }
        }
        
        .auth-title {
            background: linear-gradient(135deg, #10b981, #059669, #047857);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 2.2rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            letter-spacing: -0.5px;
        }
        
        .auth-subtitle {
            color: #64748b;
            font-size: 1.05rem;
            font-weight: 500;
        }
        
        .form-group {
            margin-bottom: 1.8rem;
            text-align: left;
        }
        
        .form-group label {
            display: block;
            color: #1e293b;
            font-weight: 700;
            margin-bottom: 0.7rem;
            font-size: 0.95rem;
            letter-spacing: 0.3px;
        }
        
        .input-group {
            position: relative;
        }
        
        .input-group i {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: #10b981;
            font-size: 1.3rem;
            z-index: 2;
        }
        
        .form-input {
            width: 100%;
            padding: 18px 20px 18px 55px;
            border: 3px solid #e2e8f0;
            border-radius: 15px;
            font-size: 1.05rem;
            transition: all 0.3s ease;
            background: #f8fafc;
            color: #000000 !important;
            font-weight: 600;
            -webkit-text-fill-color: #000000 !important;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #10b981;
            background: white;
            box-shadow: 0 0 0 5px rgba(16, 185, 129, 0.15), 0 10px 25px rgba(16, 185, 129, 0.1);
            transform: translateY(-2px);
        }
        
        .form-input::placeholder {
            color: #94a3b8;
            font-weight: 500;
        }
        
        input[type="text"], input[type="email"], input[type="password"] {
            color: #000000 !important;
            -webkit-text-fill-color: #000000 !important;
        }
        
        .btn-register {
            width: 100%;
            padding: 20px;
            background: linear-gradient(135deg, #10b981 0%, #059669 50%, #047857 100%);
            color: white;
            border: none;
            border-radius: 15px;
            font-size: 1.15rem;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.4s ease;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-top: 1rem;
            box-shadow: 0 15px 35px rgba(16, 185, 129, 0.4);
            position: relative;
            overflow: hidden;
        }
        
        .btn-register::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s;
        }
        
        .btn-register:hover::before {
            left: 100%;
        }
        
        .btn-register:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 50%, #065f46 100%);
            transform: translateY(-3px);
            box-shadow: 0 20px 45px rgba(16, 185, 129, 0.5);
        }
        
        .btn-register:active {
            transform: translateY(-1px);
        }
        
        .divider {
            margin: 2.5rem 0 2rem;
            color: #64748b;
            font-size: 0.95rem;
            font-weight: 600;
            position: relative;
            text-align: center;
        }
        
        .divider::before,
        .divider::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 38%;
            height: 2px;
            background: linear-gradient(to right, transparent, #cbd5e1, transparent);
        }
        
        .divider::before {
            left: 0;
        }
        
        .divider::after {
            right: 0;
        }
        
        .btn-login {
            width: 100%;
            padding: 18px;
            background: white;
            color: #10b981;
            border: 3px solid #10b981;
            border-radius: 15px;
            font-size: 1.05rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            letter-spacing: 0.5px;
        }
        
        .btn-login:hover {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(16, 185, 129, 0.4);
            border-color: transparent;
        }
        
        .alert {
            padding: 16px 20px;
            border-radius: 15px;
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
            text-align: left;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            font-weight: 600;
            animation: slideIn 0.4s ease-out;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        .alert i {
            font-size: 1.4rem;
            margin-top: 2px;
        }
        
        .alert-success {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            color: #065f46;
            border: 2px solid #6ee7b7;
        }
        
        .alert-error {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            color: #991b1b;
            border: 2px solid #fca5a5;
        }
        
        .alert-warning {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            color: #92400e;
            border: 2px solid #fcd34d;
        }
        
        .alert-info {
            background: linear-gradient(135deg, #dbeafe, #bfdbfe);
            color: #1e40af;
            border: 2px solid #93c5fd;
        }
        
        .back-link {
            position: absolute;
            top: 25px;
            left: 25px;
            color: white;
            text-decoration: none;
            font-size: 1.05rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 24px;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 12px;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 255, 255, 0.2);
            z-index: 10;
        }
        
        .back-link:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateX(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }
        
        small {
            color: #64748b;
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        @media (max-width: 480px) {
            .auth-container {
                padding: 2.5rem 2rem;
                border-radius: 25px;
                margin: 10px;
                max-width: 100%;
            }
            
            .auth-icon {
                width: 75px;
                height: 75px;
                font-size: 2rem;
            }
            
            .auth-title {
                font-size: 1.8rem;
            }
            
            .back-link {
                position: static;
                margin-bottom: 20px;
                display: inline-flex;
            }
        }
    </style>
</head>
<body>
    
    <a href="../index.php" class="back-link">
        <i class="fas fa-arrow-left"></i> Back to Home
    </a>
    
    <div class="auth-container">
        <div class="auth-header">
            <div class="auth-icon">
                <i class="fas fa-user-plus"></i>
            </div>
            <h1 class="auth-title">Join Concordial Nexus</h1>
            <p class="auth-subtitle">Trading Management System</p>
        </div>
        
        <?php
        // Show success/error messages from form processing
        if (isset($success_message)) {
            echo '<div class="alert alert-success"><i class="fas fa-check-circle"></i> ' . $success_message . '</div>';
            if (isset($user_info)) {
                echo '<div class="alert alert-info">';
                echo '<strong>Email:</strong> ' . htmlspecialchars($user_info['email']) . '<br>';
                echo '<strong>Referral Code:</strong> ' . $user_info['referral_code'];
                if (isset($user_info['personal_code'])) {
                    echo '<br><strong>Personal Invitation Code:</strong> ' . $user_info['personal_code'];
                }
                echo '</div>';
                echo '<div class="alert alert-warning"><i class="fas fa-clock"></i> Your account will be activated once approved by admin.</div>';
            }
        }
        
        if (isset($error_message)) {
            if (isset($db_setup_link)) {
                echo '<div class="alert alert-error"><i class="fas fa-database"></i> ' . htmlspecialchars($error_message) . '</div>';
                
                // Check specific error types
                if (isset($db_error_details)) {
                    if (strpos($db_error_details, "Table") !== false && strpos($db_error_details, "doesn't exist") !== false) {
                        echo '<div class="alert alert-warning" style="margin-top: 10px;">';
                        echo '<i class="fas fa-tools"></i> <strong>Missing Database Tables!</strong><br>';
                        echo 'Some required tables are missing from your database.';
                        echo '</div>';
                        echo '<div class="alert alert-info">';
                        echo '<a href="../fix-registration-database.php" class="btn" style="display:inline-block; margin:10px 0; background:linear-gradient(135deg, #10b981, #059669); color:white; padding:12px 24px; text-decoration:none; border-radius:10px; font-weight:700;">';
                        echo '<i class="fas fa-magic"></i> Fix Database Now (1 Click)</a>';
                        echo '</div>';
                    } elseif (strpos($db_error_details, "Unknown column") !== false) {
                        echo '<div class="alert alert-warning" style="margin-top: 10px;">';
                        echo '<i class="fas fa-columns"></i> <strong>Missing Database Columns!</strong><br>';
                        echo 'Your database structure needs to be updated.';
                        echo '</div>';
                        echo '<div class="alert alert-info">';
                        echo '<a href="../fix-registration-database.php" class="btn" style="display:inline-block; margin:10px 0; background:linear-gradient(135deg, #10b981, #059669); color:white; padding:12px 24px; text-decoration:none; border-radius:10px; font-weight:700;">';
                        echo '<i class="fas fa-magic"></i> Fix Database Now (1 Click)</a>';
                        echo '</div>';
                    } else {
                        echo '<div class="alert alert-info"><a href="../database/simple-setup.php" style="color: #0c5460; text-decoration: none;"><i class="fas fa-tools"></i> Click here to setup database</a></div>';
                    }
                }
            } else {
                $icon = 'fas fa-info-circle';
                $alertClass = 'alert-warning';
                
                if (strpos($error_message, 'Email already exists') !== false) {
                    $icon = 'fas fa-user-times';
                    $alertClass = 'alert-warning';
                } elseif (strpos($error_message, 'Invalid') !== false || strpos($error_message, 'invitation code') !== false) {
                    $icon = 'fas fa-ticket-alt';
                    $alertClass = 'alert-info';
                }
                
                echo '<div class="alert ' . $alertClass . '"><i class="' . $icon . '"></i> ' . $error_message . '</div>';
                
                // Add helpful message for invalid invitation code
                if (strpos($error_message, 'invitation code') !== false) {
                    echo '<div class="alert alert-info" style="margin-top: 10px;">';
                    echo '<i class="fas fa-lightbulb"></i> <strong>Tip:</strong> Invitation codes are optional. You can leave it empty and register without a code!';
                    echo '</div>';
                }
            }
        }
        ?>
        
        <form method="POST">
            <?php 
            // Get referral code from URL if present
            $ref_code = $_GET['ref'] ?? '';
            if ($ref_code) {
                echo '<input type="hidden" name="referrer_code" value="' . htmlspecialchars($ref_code) . '">';
                echo '<div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center;">';
                echo '<i class="fas fa-users"></i> <strong>Referred by: ' . htmlspecialchars($ref_code) . '</strong><br>';
                echo '<small>You will be registered under this referrer\'s network</small>';
                echo '</div>';
            }
            ?>
            
            <div class="form-group">
                <label for="invitation_code"><i class="fas fa-ticket-alt"></i> Invitation Code (Optional)</label>
                <div class="input-group">
                    <i class="fas fa-ticket-alt"></i>
                    <input type="text" id="invitation_code" name="invitation_code" class="form-input" placeholder="Enter invitation code (optional)" style="text-transform: uppercase;">
                </div>
                <small style="color: #666; margin-top: 5px; display: block;">Leave empty if you don't have an invitation code</small>
            </div>
            
            <div class="form-group">
                <label for="full_name"><i class="fas fa-user"></i> Full Name</label>
                <div class="input-group">
                    <i class="fas fa-user"></i>
                    <input type="text" id="full_name" name="full_name" class="form-input" required placeholder="Enter your full name">
                </div>
            </div>
            
            <div class="form-group">
                <label for="email"><i class="fas fa-envelope"></i> Email Address</label>
                <div class="input-group">
                    <i class="fas fa-envelope"></i>
                    <input type="email" id="email" name="email" class="form-input" required placeholder="Enter your email">
                </div>
            </div>
            
            <div class="form-group">
                <label for="password"><i class="fas fa-lock"></i> Password</label>
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" class="form-input" required placeholder="Enter your password">
                </div>
            </div>
            
            <button type="submit" class="btn-register">
                <i class="fas fa-user-plus"></i> Register Now
            </button>
        </form>
        
        <div class="divider">Already have an account?</div>
        
        <a href="login.php" class="btn-login">
            <i class="fas fa-sign-in-alt"></i> Login
        </a>
    </div>
    
    <script>
        // Ensure text visibility in all input fields
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('.form-input');
            inputs.forEach(input => {
                // Force text color on input
                input.addEventListener('input', function() {
                    this.style.color = '#000000';
                    this.style.webkitTextFillColor = '#000000';
                });
                
                // Force text color on focus
                input.addEventListener('focus', function() {
                    this.style.color = '#000000';
                    this.style.webkitTextFillColor = '#000000';
                    this.style.backgroundColor = '#ffffff';
                });
                
                // Force text color on blur
                input.addEventListener('blur', function() {
                    this.style.color = '#000000';
                    this.style.webkitTextFillColor = '#000000';
                });
            });
        });
    </script>
</body>
</html>