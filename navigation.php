<?php
// Navigation component with role-based access control
function renderNavigation($current_page = '') {
    // Check if user is logged in
    $is_logged_in = isset($_SESSION['user_id']);
    $user_type = $_SESSION['user_type'] ?? '';
    
    // Determine base path based on current location
    $base_path = '';
    if (strpos($_SERVER['REQUEST_URI'], '/dashboard/') !== false) {
        $base_path = '../';
    } elseif (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false) {
        $base_path = '../';
    } elseif (strpos($_SERVER['REQUEST_URI'], '/auth/') !== false) {
        $base_path = '../';
    }
    
    // Base navigation for non-logged-in users
    $nav_items = [
        'public' => [
            'home' => ['url' => $base_path . 'index.php', 'label' => 'Home', 'icon' => 'fas fa-home'],
            'about' => ['url' => $base_path . 'about.html', 'label' => 'About', 'icon' => 'fas fa-info-circle'],
            'services' => ['url' => $base_path . 'services.html', 'label' => 'Services', 'icon' => 'fas fa-cogs'],
            'pricing' => ['url' => $base_path . 'pricing.html', 'label' => 'Pricing', 'icon' => 'fas fa-tags'],
            'contact' => ['url' => $base_path . 'contact.html', 'label' => 'Contact', 'icon' => 'fas fa-envelope'],
            'login' => ['url' => $base_path . 'auth/login.php', 'label' => 'Login', 'icon' => 'fas fa-sign-in-alt'],
            'register' => ['url' => $base_path . 'auth/register.php', 'label' => 'Sign Up', 'icon' => 'fas fa-user-plus', 'class' => 'btn-primary']
        ],
        'user' => [
            'dashboard' => ['url' => $base_path . 'dashboard/index.php', 'label' => 'Overview', 'icon' => 'fas fa-tachometer-alt'],
            'wallet' => ['url' => $base_path . 'dashboard/wallet.php', 'label' => 'Wallet', 'icon' => 'fas fa-wallet'],
            'markets' => ['url' => $base_path . 'dashboard/markets.php', 'label' => 'Markets', 'icon' => 'fas fa-chart-bar'],
            'trading' => ['url' => $base_path . 'dashboard/trading.php', 'label' => 'Trading', 'icon' => 'fas fa-exchange-alt'],
            'orders' => ['url' => $base_path . 'dashboard/orders.php', 'label' => 'Orders', 'icon' => 'fas fa-list-alt'],
            'portfolio' => ['url' => $base_path . 'dashboard/portfolio.php', 'label' => 'Portfolio', 'icon' => 'fas fa-briefcase'],
            'analysis' => ['url' => $base_path . 'dashboard/analysis.php', 'label' => 'Analysis', 'icon' => 'fas fa-chart-line'],
            'transactions' => ['url' => $base_path . 'dashboard/transactions.php', 'label' => 'History', 'icon' => 'fas fa-history'],
            'investments' => ['url' => $base_path . 'dashboard/investments.php', 'label' => 'Investments', 'icon' => 'fas fa-seedling'],
            'payment_methods' => ['url' => $base_path . 'dashboard/payment-methods.php', 'label' => 'Payments', 'icon' => 'fas fa-credit-card'],
            'profile' => ['url' => $base_path . 'dashboard/profile.php', 'label' => 'Profile', 'icon' => 'fas fa-user-circle'],
            'logout' => ['url' => $base_path . 'auth/logout.php', 'label' => 'Logout', 'icon' => 'fas fa-sign-out-alt']
        ],
        'admin' => [
            'dashboard' => ['url' => $base_path . 'admin/dashboard.php', 'label' => 'Dashboard', 'icon' => 'fas fa-tachometer-alt'],
            'users' => ['url' => $base_path . 'admin/users.php', 'label' => 'Users', 'icon' => 'fas fa-users'],
            'withdrawals' => ['url' => $base_path . 'admin/withdrawal-management.php', 'label' => 'Withdrawals', 'icon' => 'fas fa-money-bill-wave'],
            'payment_transactions' => ['url' => $base_path . 'admin/payment-transactions.php', 'label' => 'Payments', 'icon' => 'fas fa-credit-card'],
            'transactions' => ['url' => $base_path . 'admin/transactions.php', 'label' => 'Transactions', 'icon' => 'fas fa-exchange-alt'],
            'invitations' => ['url' => $base_path . 'admin/invitations.php', 'label' => 'Invitations', 'icon' => 'fas fa-ticket-alt'],
            'profile' => ['url' => $base_path . 'admin/profile.php', 'label' => 'Profile', 'icon' => 'fas fa-user-shield'],
            'logout' => ['url' => $base_path . 'auth/logout.php', 'label' => 'Logout', 'icon' => 'fas fa-sign-out-alt']
        ]
    ];
    
    // Determine which navigation to show
    if (!$is_logged_in) {
        $items = $nav_items['public'];
    } elseif ($user_type === 'admin') {
        $items = $nav_items['admin'];
    } else {
        $items = $nav_items['user'];
    }
    
    // Generate navigation HTML
    echo '<button class="nav-toggle" onclick="toggleMobileNav()"><i class="fas fa-bars"></i></button>';
    echo '<ul class="nav-menu" id="navMenu">';
    foreach ($items as $key => $item) {
        $active_class = ($current_page === $key) ? ' active' : '';
        $item_class = isset($item['class']) ? ' ' . $item['class'] : '';
        
        echo '<li class="nav-item">';
        echo '<a href="' . $item['url'] . '" class="nav-link' . $active_class . $item_class . '">';
        echo '<i class="' . $item['icon'] . '"></i> <span>' . $item['label'] . '</span>';
        echo '</a>';
        echo '</li>';
    }
    echo '</ul>';
    
    // Add mobile navigation script
    echo '<script>
        function toggleMobileNav() {
            const navMenu = document.getElementById("navMenu");
            navMenu.classList.toggle("active");
        }
        
        // Close mobile nav when clicking outside
        document.addEventListener("click", function(event) {
            const navMenu = document.getElementById("navMenu");
            const navToggle = document.querySelector(".nav-toggle");
            
            if (!navMenu.contains(event.target) && !navToggle.contains(event.target)) {
                navMenu.classList.remove("active");
            }
        });
        
        // Close mobile nav when window is resized to desktop
        window.addEventListener("resize", function() {
            if (window.innerWidth > 768) {
                document.getElementById("navMenu").classList.remove("active");
            }
        });
    </script>';
}

// Function to check if user has access to a page
function checkPageAccess($required_role = 'user') {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../auth/login.php');
        exit();
    }
    
    if ($required_role === 'admin' && $_SESSION['user_type'] !== 'admin') {
        header('Location: ../dashboard/index.php');
        exit();
    }
}

// Function to redirect logged-in users from public pages
function redirectIfLoggedIn() {
    if (isset($_SESSION['user_id'])) {
        if ($_SESSION['user_type'] === 'admin') {
            header('Location: admin/dashboard.php');
        } else {
            header('Location: dashboard/index.php');
        }
        exit();
    }
}
?>