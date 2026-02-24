<?php
// Access Control System for Breakthrough Trading

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if user is admin
 */
function isAdmin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
}

/**
 * Require login - redirect to login page if not logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ../auth/login.php');
        exit();
    }
}

/**
 * Require admin access - redirect if not admin
 */
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: ../dashboard/index.php');
        exit();
    }
}

/**
 * Redirect logged-in users from public pages
 */
function redirectIfLoggedIn() {
    if (isLoggedIn()) {
        if (isAdmin()) {
            header('Location: admin/dashboard.php');
        } else {
            header('Location: dashboard/index.php');
        }
        exit();
    }
}

/**
 * Get user dashboard URL based on role
 */
function getDashboardUrl() {
    if (isAdmin()) {
        return '../admin/dashboard.php';
    } else {
        return '../dashboard/index.php';
    }
}

/**
 * Get navigation items based on user role
 */
function getNavigationItems($current_page = '') {
    if (!isLoggedIn()) {
        // Public navigation
        return [
            'home' => ['url' => '../index.php', 'label' => 'Home', 'icon' => 'fas fa-home'],
            'about' => ['url' => '../about.html', 'label' => 'About', 'icon' => 'fas fa-info-circle'],
            'login' => ['url' => '../auth/login.php', 'label' => 'Login', 'icon' => 'fas fa-sign-in-alt'],
            'register' => ['url' => '../auth/register.php', 'label' => 'Sign Up', 'icon' => 'fas fa-user-plus', 'class' => 'btn-primary']
        ];
    } elseif (isAdmin()) {
        // Admin navigation
        return [
            'dashboard' => ['url' => '../admin/dashboard.php', 'label' => 'Dashboard', 'icon' => 'fas fa-tachometer-alt'],
            'users' => ['url' => '../admin/users.php', 'label' => 'Users', 'icon' => 'fas fa-users'],
            'transactions' => ['url' => '../admin/transactions.php', 'label' => 'Transactions', 'icon' => 'fas fa-exchange-alt'],
            'invitations' => ['url' => '../admin/invitations.php', 'label' => 'Invitations', 'icon' => 'fas fa-ticket-alt'],
            'profile' => ['url' => '../admin/profile.php', 'label' => 'Profile', 'icon' => 'fas fa-user-shield'],
            'logout' => ['url' => '../auth/logout.php', 'label' => 'Logout', 'icon' => 'fas fa-sign-out-alt']
        ];
    } else {
        // User navigation
        return [
            'dashboard' => ['url' => '../dashboard/index.php', 'label' => 'Dashboard', 'icon' => 'fas fa-tachometer-alt'],
            'wallet' => ['url' => '../dashboard/wallet.php', 'label' => 'Wallet', 'icon' => 'fas fa-wallet'],
            'markets' => ['url' => '../dashboard/markets.php', 'label' => 'Markets', 'icon' => 'fas fa-chart-bar'],
            'trading' => ['url' => '../dashboard/trading.php', 'label' => 'Trading', 'icon' => 'fas fa-exchange-alt'],
            'portfolio' => ['url' => '../dashboard/portfolio.php', 'label' => 'Portfolio', 'icon' => 'fas fa-briefcase'],
            'orders' => ['url' => '../dashboard/orders.php', 'label' => 'Orders', 'icon' => 'fas fa-list-alt'],
            'analysis' => ['url' => '../dashboard/analysis.php', 'label' => 'Analysis', 'icon' => 'fas fa-chart-line'],
            'notifications' => ['url' => '../dashboard/notifications.php', 'label' => 'Notifications', 'icon' => 'fas fa-bell'],
            'profile' => ['url' => '../dashboard/profile.php', 'label' => 'Profile', 'icon' => 'fas fa-user-circle'],
            'support' => ['url' => '../dashboard/support.php', 'label' => 'Support', 'icon' => 'fas fa-life-ring'],
            'logout' => ['url' => '../auth/logout.php', 'label' => 'Logout', 'icon' => 'fas fa-sign-out-alt']
        ];
    }
}

/**
 * Render navigation HTML
 */
function renderNavigation($current_page = '') {
    $items = getNavigationItems($current_page);
    
    echo '<ul class="nav-menu">';
    foreach ($items as $key => $item) {
        $active_class = ($current_page === $key) ? ' active' : '';
        $item_class = isset($item['class']) ? ' ' . $item['class'] : '';
        
        echo '<li class="nav-item">';
        echo '<a href="' . $item['url'] . '" class="nav-link' . $active_class . $item_class . '">';
        echo '<i class="' . $item['icon'] . '"></i> ' . $item['label'];
        echo '</a>';
        echo '</li>';
    }
    echo '</ul>';
}

/**
 * Check user account status and redirect if necessary
 */
function checkUserStatus() {
    if (isLoggedIn() && !isAdmin()) {
        try {
            $pdo = new PDO("mysql:host=localhost;dbname=breakthrough_trading;charset=utf8mb4", "root", "");
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $stmt = $pdo->prepare("SELECT status FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();
            
            if ($user && $user['status'] !== 'active') {
                // User is not active, redirect to login with message
                session_destroy();
                header('Location: ../auth/login.php?status=' . $user['status']);
                exit();
            }
        } catch(PDOException $e) {
            // Database error, continue normally
        }
    }
}
?>