<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Security functions
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: auth/login.php');
        exit();
    }
}

function isAdmin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: dashboard/index.php');
        exit();
    }
}

// User functions
function getUserById($id) {
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function getUserByEmail($email) {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    } catch (Exception $e) {
        error_log("Error in getUserByEmail: " . $e->getMessage());
        return false;
    }
}

function getUserByUsername($username) {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetch();
    } catch (Exception $e) {
        error_log("Error in getUserByUsername: " . $e->getMessage());
        return false;
    }
}

function verifyPassword($inputPassword, $storedPassword, $userType) {
    if ($userType === 'admin') {
        // For admin accounts, use plain text comparison
        return $inputPassword === $storedPassword;
    } else {
        // For regular users, use password_verify for hashed passwords
        return password_verify($inputPassword, $storedPassword);
    }
}

function createUser($data) {
    try {
        $pdo = getConnection();
        
        // Check if username already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$data['username']]);
        if ($stmt->fetch()) {
            return false; // Username already exists
        }
        
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, first_name, last_name, phone, user_type, trading_level, verification_token) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        // For admin accounts, use plain text password. For regular users, hash it.
        $userType = $data['user_type'] ?? 'trader';
        if ($userType === 'admin') {
            $password = $data['password']; // Plain text for admin
        } else {
            $password = password_hash($data['password'], PASSWORD_DEFAULT); // Hashed for regular users
        }
        
        $verificationToken = generateToken();
        
        return $stmt->execute([
            $data['username'],
            $data['email'],
            $password,
            $data['first_name'],
            $data['last_name'],
            $data['phone'] ?? null,
            $userType,
            $data['trading_level'] ?? null,
            $verificationToken
        ]);
    } catch (Exception $e) {
        error_log("Error in createUser: " . $e->getMessage());
        return false;
    }
}

function updateUser($id, $data) {
    $pdo = getConnection();
    
    $fields = [];
    $values = [];
    
    foreach ($data as $key => $value) {
        if ($key !== 'id') {
            $fields[] = "$key = ?";
            $values[] = $value;
        }
    }
    
    $values[] = $id;
    $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
    
    $stmt = $pdo->prepare($sql);
    return $stmt->execute($values);
}

function verifyUser($token) {
    $pdo = getConnection();
    $stmt = $pdo->prepare("UPDATE users SET email_verified = TRUE, verification_token = NULL WHERE verification_token = ?");
    return $stmt->execute([$token]);
}

// Service functions
function getServices($limit = null, $category = null, $search = null) {
    $pdo = getConnection();
    
    $sql = "SELECT s.*, u.username, u.profile_image, c.name as category_name 
            FROM services s 
            JOIN users u ON s.user_id = u.id 
            JOIN categories c ON s.category_id = c.id 
            WHERE s.status = 'active'";
    
    $params = [];
    
    if ($category) {
        $sql .= " AND s.category_id = ?";
        $params[] = $category;
    }
    
    if ($search) {
        $sql .= " AND (s.title LIKE ? OR s.description LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    $sql .= " ORDER BY s.created_at DESC";
    
    if ($limit) {
        $sql .= " LIMIT ?";
        $params[] = $limit;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getServiceById($id) {
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT s.*, u.username, u.profile_image, u.first_name, u.last_name, c.name as category_name 
                          FROM services s 
                          JOIN users u ON s.user_id = u.id 
                          JOIN categories c ON s.category_id = c.id 
                          WHERE s.id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function createService($data) {
    $pdo = getConnection();
    
    $stmt = $pdo->prepare("INSERT INTO services (user_id, category_id, title, description, price, delivery_time, features, images) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    
    return $stmt->execute([
        $data['user_id'],
        $data['category_id'],
        $data['title'],
        $data['description'],
        $data['price'],
        $data['delivery_time'],
        $data['features'] ?? null,
        $data['images'] ?? null
    ]);
}

// Order functions
function createOrder($data) {
    $pdo = getConnection();
    
    $stmt = $pdo->prepare("INSERT INTO orders (service_id, client_id, freelancer_id, title, description, amount, delivery_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    return $stmt->execute([
        $data['service_id'],
        $data['client_id'],
        $data['freelancer_id'],
        $data['title'],
        $data['description'],
        $data['amount'],
        $data['delivery_date']
    ]);
}

function getOrdersByUser($userId, $type = 'all') {
    $pdo = getConnection();
    
    $sql = "SELECT o.*, s.title as service_title, 
            client.username as client_username, 
            freelancer.username as freelancer_username 
            FROM orders o 
            JOIN services s ON o.service_id = s.id 
            JOIN users client ON o.client_id = client.id 
            JOIN users freelancer ON o.freelancer_id = freelancer.id 
            WHERE ";
    
    if ($type === 'client') {
        $sql .= "o.client_id = ?";
    } elseif ($type === 'freelancer') {
        $sql .= "o.freelancer_id = ?";
    } else {
        $sql .= "(o.client_id = ? OR o.freelancer_id = ?)";
    }
    
    $sql .= " ORDER BY o.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    
    if ($type === 'all') {
        $stmt->execute([$userId, $userId]);
    } else {
        $stmt->execute([$userId]);
    }
    
    return $stmt->fetchAll();
}

// Category functions
function getCategories() {
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE status = 'active' ORDER BY name");
    $stmt->execute();
    return $stmt->fetchAll();
}

// Notification functions
function createNotification($userId, $title, $message, $type = 'info') {
    $pdo = getConnection();
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)");
    return $stmt->execute([$userId, $title, $message, $type]);
}

function getNotifications($userId, $unreadOnly = false) {
    $pdo = getConnection();
    
    $sql = "SELECT * FROM notifications WHERE user_id = ?";
    if ($unreadOnly) {
        $sql .= " AND is_read = FALSE";
    }
    $sql .= " ORDER BY created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function markNotificationRead($id, $userId) {
    $pdo = getConnection();
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = TRUE WHERE id = ? AND user_id = ?");
    return $stmt->execute([$id, $userId]);
}

// Message functions
function sendMessage($senderId, $receiverId, $subject, $message, $orderId = null) {
    $pdo = getConnection();
    $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, order_id, subject, message) VALUES (?, ?, ?, ?, ?)");
    return $stmt->execute([$senderId, $receiverId, $orderId, $subject, $message]);
}

function getMessages($userId, $conversationWith = null) {
    $pdo = getConnection();
    
    if ($conversationWith) {
        $sql = "SELECT m.*, sender.username as sender_username, receiver.username as receiver_username 
                FROM messages m 
                JOIN users sender ON m.sender_id = sender.id 
                JOIN users receiver ON m.receiver_id = receiver.id 
                WHERE (m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?) 
                ORDER BY m.created_at ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId, $conversationWith, $conversationWith, $userId]);
    } else {
        $sql = "SELECT m.*, sender.username as sender_username, receiver.username as receiver_username 
                FROM messages m 
                JOIN users sender ON m.sender_id = sender.id 
                JOIN users receiver ON m.receiver_id = receiver.id 
                WHERE m.receiver_id = ? 
                ORDER BY m.created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);
    }
    
    return $stmt->fetchAll();
}

// File upload functions
function uploadFile($file, $directory = 'uploads/') {
    $targetDir = $directory;
    $fileName = time() . '_' . basename($file["name"]);
    $targetFile = $targetDir . $fileName;
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
    
    // Check if file already exists
    if (file_exists($targetFile)) {
        return ['success' => false, 'message' => 'File already exists.'];
    }
    
    // Check file size (5MB limit)
    if ($file["size"] > 5000000) {
        return ['success' => false, 'message' => 'File is too large.'];
    }
    
    // Allow certain file formats
    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'zip'];
    if (!in_array($imageFileType, $allowedTypes)) {
        return ['success' => false, 'message' => 'File type not allowed.'];
    }
    
    // Create directory if it doesn't exist
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    if (move_uploaded_file($file["tmp_name"], $targetFile)) {
        return ['success' => true, 'filename' => $fileName, 'path' => $targetFile];
    } else {
        return ['success' => false, 'message' => 'Upload failed.'];
    }
}

// Email functions
function sendEmail($to, $subject, $message, $headers = null) {
    if (!$headers) {
        $headers = "From: noreply@workhub.com\r\n";
        $headers .= "Reply-To: noreply@workhub.com\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    }
    
    return mail($to, $subject, $message, $headers);
}

// Utility functions
function formatCurrency($amount) {
    return 'Br' . number_format($amount, 2);
}

function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    if ($time < 31536000) return floor($time/2592000) . ' months ago';
    return floor($time/31536000) . ' years ago';
}

function generateSlug($string) {
    return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $string)));
}

// Search function
function searchContent($query) {
    $pdo = getConnection();
    
    $results = [];
    
    // Search services
    $stmt = $pdo->prepare("SELECT 'service' as type, id, title, description FROM services WHERE title LIKE ? OR description LIKE ? AND status = 'active' LIMIT 10");
    $stmt->execute(["%$query%", "%$query%"]);
    $services = $stmt->fetchAll();
    
    foreach ($services as $service) {
        $results[] = [
            'type' => 'service',
            'title' => $service['title'],
            'description' => substr($service['description'], 0, 150) . '...',
            'url' => 'service.php?id=' . $service['id']
        ];
    }
    
    // Search users
    $stmt = $pdo->prepare("SELECT 'user' as type, id, username, CONCAT(first_name, ' ', last_name) as name, bio FROM users WHERE username LIKE ? OR first_name LIKE ? OR last_name LIKE ? AND status = 'active' LIMIT 5");
    $stmt->execute(["%$query%", "%$query%", "%$query%"]);
    $users = $stmt->fetchAll();
    
    foreach ($users as $user) {
        $results[] = [
            'type' => 'user',
            'title' => $user['name'] . ' (@' . $user['username'] . ')',
            'description' => $user['bio'] ? substr($user['bio'], 0, 150) . '...' : 'Professional freelancer',
            'url' => 'profile.php?id=' . $user['id']
        ];
    }
    
    return $results;
}

// Dashboard stats
function getDashboardStats($userId) {
    $pdo = getConnection();
    
    $stats = [];
    
    // Total projects
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders WHERE freelancer_id = ?");
    $stmt->execute([$userId]);
    $stats['total_projects'] = $stmt->fetch()['count'];
    
    // Active orders
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders WHERE (client_id = ? OR freelancer_id = ?) AND status IN ('pending', 'in_progress')");
    $stmt->execute([$userId, $userId]);
    $stats['active_orders'] = $stmt->fetch()['count'];
    
    // Total earnings
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE payee_id = ? AND status = 'completed'");
    $stmt->execute([$userId]);
    $stats['total_earnings'] = $stats['total_earnings'] = formatCurrency($stmt->fetch()['total']);
    
    // Pending payments
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE payee_id = ? AND status = 'pending'");
    $stmt->execute([$userId]);
    $stats['pending_payments'] = formatCurrency($stmt->fetch()['total']);
    
    return $stats;
}
?>