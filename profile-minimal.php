<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

$message = '';

try {
    $pdo = new PDO("mysql:host=localhost;dbname=concordial_nexus;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $admin = $stmt->fetch();
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $full_name = $_POST['full_name'];
        $email = $_POST['email'];
        $city = $_POST['city'] ?? 'Addis Ababa';
        $country = $_POST['country'] ?? 'Ethiopia';
        
        $update = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, city = ?, country = ? WHERE id = ?");
        if ($update->execute([$full_name, $email, $city, $country, $_SESSION['user_id']])) {
            $message = 'Profile updated successfully!';
            $stmt->execute([$_SESSION['user_id']]);
            $admin = $stmt->fetch();
        } else {
            $message = 'Update failed';
        }
    }
    
} catch(Exception $e) {
    $message = 'Error: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Profile - Concordial Nexus</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f0f0f0; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; }
        h1 { color: #333; text-align: center; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; }
        button { background: #007bff; color: white; padding: 12px 24px; border: none; border-radius: 5px; cursor: pointer; width: 100%; }
        button:hover { background: #0056b3; }
        .message { padding: 10px; margin-bottom: 20px; border-radius: 5px; background: #d4edda; color: #155724; text-align: center; }
        .nav { text-align: center; margin-top: 20px; }
        .nav a { display: inline-block; margin: 0 10px; padding: 8px 16px; background: #6c757d; color: white; text-decoration: none; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🛡️ Admin Profile</h1>
        <p style="text-align: center; color: #666;">Concordial Nexus Administrative Panel</p>
        
        <?php if ($message): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="full_name" value="<?php echo htmlspecialchars($admin['full_name'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($admin['email'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label>City</label>
                <input type="text" name="city" value="<?php echo htmlspecialchars($admin['city'] ?? 'Addis Ababa'); ?>">
            </div>
            
            <div class="form-group">
                <label>Country</label>
                <input type="text" name="country" value="<?php echo htmlspecialchars($admin['country'] ?? 'Ethiopia'); ?>">
            </div>
            
            <button type="submit">Update Profile</button>
        </form>
        
        <div class="nav">
            <a href="dashboard.php">📊 Dashboard</a>
            <a href="withdrawal-management.php">💰 Withdrawals</a>
            <a href="users.php">👥 Users</a>
            <a href="../auth/logout.php">🚪 Logout</a>
        </div>
    </div>
</body>
</html>