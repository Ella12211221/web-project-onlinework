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
        
        // Check user credentials
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND password = ?");
        $stmt->execute([$email, $password]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Check if user is approved
            if ($user['status'] === 'pending') {
                $error_message = 'Your account is pending admin approval. Please wait for activation.';
            } elseif ($user['status'] === 'suspended') {
                $error_message = 'Your account has been suspended. Contact admin for assistance.';
            } elseif ($user['status'] === 'inactive') {
                $error_message = 'Your account is inactive. Contact admin for assistance.';
            } else {
                // Set session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_type'] = $user['user_type'];
                
                // Redirect based on user type - THIS HAPPENS BEFORE ANY HTML
                if ($user['user_type'] === 'admin') {
                    header('Location: ../admin/dashboard.php');
                    exit();
                } else {
                    header('Location: ../dashboard/index.php');
                    exit();
                }
            }
        } else {
            $error_message = 'Invalid email or password!';
        }
    } catch(PDOException $e) {
        $error_message = 'Database error. Please run database setup first.';
        $db_setup_link = true;
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
    <title>Login - Concordial Nexus</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 50%, #7e22ce 100%);
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
            background: radial-gradient(circle, rgba(74, 144, 226, 0.3) 0%, transparent 70%);
            top: -250px;
            right: -250px;
            animation: float 6s ease-in-out infinite;
        }
        
        body::after {
            content: '';
            position: absolute;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(126, 34, 206, 0.3) 0%, transparent 70%);
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
            max-width: 480px;
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.4), 0 0 0 1px rgba(255, 255, 255, 0.1);
            position: relative;
            z-index: 1;
            animation: slideUp 0.6s ease-out;
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
            background: linear-gradient(135deg, #4a90e2 0%, #357abd 50%, #2a5298 100%);
            color: white;
            width: 90px;
            height: 90px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2.5rem;
            box-shadow: 0 15px 35px rgba(74, 144, 226, 0.4);
            animation: pulse 2s ease-in-out infinite;
            position: relative;
        }
        
        .auth-icon::before {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: linear-gradient(135deg, #4a90e2, #357abd);
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
            background: linear-gradient(135deg, #1e3c72, #2a5298, #7e22ce);
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
            color: #4a90e2;
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
            border-color: #4a90e2;
            background: white;
            box-shadow: 0 0 0 5px rgba(74, 144, 226, 0.15), 0 10px 25px rgba(74, 144, 226, 0.1);
            transform: translateY(-2px);
        }
        
        .form-input::placeholder {
            color: #94a3b8;
            font-weight: 500;
        }
        
        .btn-login {
            width: 100%;
            padding: 20px;
            background: linear-gradient(135deg, #4a90e2 0%, #357abd 50%, #2a5298 100%);
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
            box-shadow: 0 15px 35px rgba(74, 144, 226, 0.4);
            position: relative;
            overflow: hidden;
        }
        
        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s;
        }
        
        .btn-login:hover::before {
            left: 100%;
        }
        
        .btn-login:hover {
            background: linear-gradient(135deg, #357abd 0%, #2a5298 50%, #1e3c72 100%);
            transform: translateY(-3px);
            box-shadow: 0 20px 45px rgba(74, 144, 226, 0.5);
        }
        
        .btn-login:active {
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
        
        .btn-register {
            width: 100%;
            padding: 18px;
            background: white;
            color: #4a90e2;
            border: 3px solid #4a90e2;
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
        
        .btn-register:hover {
            background: linear-gradient(135deg, #4a90e2, #357abd);
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(74, 144, 226, 0.4);
            border-color: transparent;
        }
        
        .alert {
            padding: 16px 20px;
            border-radius: 15px;
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
            text-align: left;
            display: flex;
            align-items: center;
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
        }
        
        .alert-error {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            color: #991b1b;
            border: 2px solid #fca5a5;
        }
        
        .alert-info {
            background: linear-gradient(135deg, #dbeafe, #bfdbfe);
            color: #1e40af;
            border: 2px solid #93c5fd;
        }
        
        .alert-success {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            color: #065f46;
            border: 2px solid #6ee7b7;
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
        
        @media (max-width: 480px) {
            .auth-container {
                padding: 2.5rem 2rem;
                border-radius: 25px;
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
                <i class="fas fa-chart-line"></i>
            </div>
            <h1 class="auth-title">Concordial Nexus</h1>
            <p class="auth-subtitle">Trading Management System</p>
        </div>
        
        <?php
        // Show logout message
        if (isset($_GET['logout'])) {
            echo '<div class="alert alert-success"><i class="fas fa-check-circle"></i> You have been logged out successfully!</div>';
        }
        
        // Show error messages from form processing
        if (isset($error_message)) {
            if (isset($db_setup_link)) {
                echo '<div class="alert alert-error"><i class="fas fa-database"></i> ' . $error_message . '</div>';
                echo '<div class="alert alert-info"><a href="../database/simple-setup.php" style="color: #1976d2; text-decoration: none;"><i class="fas fa-tools"></i> Click here to setup database</a></div>';
            } else {
                $icon = 'fas fa-exclamation-triangle';
                if (strpos($error_message, 'pending') !== false) $icon = 'fas fa-clock';
                if (strpos($error_message, 'suspended') !== false) $icon = 'fas fa-ban';
                if (strpos($error_message, 'inactive') !== false) $icon = 'fas fa-user-slash';
                
                echo '<div class="alert alert-error"><i class="' . $icon . '"></i> ' . $error_message . '</div>';
            }
        }
        ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="email"><i class="fas fa-user"></i> Username or Email</label>
                <div class="input-group">
                    <i class="fas fa-user"></i>
                    <input type="email" id="email" name="email" class="form-input" required placeholder="Enter username or email">
                </div>
            </div>
            
            <div class="form-group">
                <label for="password"><i class="fas fa-lock"></i> Password</label>
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" class="form-input" required placeholder="Enter password">
                </div>
            </div>
            
            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt"></i> Login
            </button>
        </form>
        
        <div class="divider">Don't have an account?</div>
        
        <a href="register.php" class="btn-register">
            <i class="fas fa-user-plus"></i> Register Now
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