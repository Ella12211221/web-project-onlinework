<?php
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'breakthrough_trading';

$setup_status = [];
$setup_log = [];

// Check MySQL connection
try {
    $pdo_check = new PDO("mysql:host=$host", $username, $password);
    $setup_status['mysql'] = true;
    $setup_log[] = "✓ MySQL connection successful";
} catch(PDOException $e) {
    $setup_status['mysql'] = false;
    $setup_log[] = "✗ MySQL connection failed: " . $e->getMessage();
}

// Check if database exists
if ($setup_status['mysql']) {
    try {
        $pdo_db = new PDO("mysql:host=$host;dbname=$database", $username, $password);
        $setup_status['database'] = true;
        $setup_log[] = "✓ Database '$database' exists";
    } catch(PDOException $e) {
        $setup_status['database'] = false;
        $setup_log[] = "✗ Database '$database' not found";
    }
}

// Handle setup actions
if (isset($_POST['action'])) {
    if ($_POST['action'] === 'create_database') {
        try {
            $pdo = new PDO("mysql:host=$host", $username, $password);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS $database CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $setup_log[] = "✓ Database '$database' created successfully";
            $setup_status['database'] = true;
        } catch(PDOException $e) {
            $setup_log[] = "✗ Failed to create database: " . $e->getMessage();
        }
    }
    
    if ($_POST['action'] === 'create_tables' && $setup_status['database']) {
        include 'create-tables.php';
    }
    
    if ($_POST['action'] === 'insert_data' && isset($setup_status['tables']) && $setup_status['tables']) {
        include 'insert-data.php';
    }
}
?>

<div class="status-card">
    <h3 style="color: #ffd700; margin-bottom: 1rem;"><i class="fas fa-clipboard-check"></i> Setup Status</h3>
    
    <div class="status-item">
        <span class="status-label">MySQL Connection</span>
        <span class="<?php echo $setup_status['mysql'] ? 'status-success' : 'status-error'; ?>">
            <i class="fas fa-<?php echo $setup_status['mysql'] ? 'check-circle' : 'times-circle'; ?>"></i>
            <?php echo $setup_status['mysql'] ? 'Connected' : 'Failed'; ?>
        </span>
    </div>
    
    <div class="status-item">
        <span class="status-label">Database Created</span>
        <span class="<?php echo isset($setup_status['database']) && $setup_status['database'] ? 'status-success' : 'status-error'; ?>">
            <i class="fas fa-<?php echo isset($setup_status['database']) && $setup_status['database'] ? 'check-circle' : 'times-circle'; ?>"></i>
            <?php echo isset($setup_status['database']) && $setup_status['database'] ? 'Ready' : 'Not Found'; ?>
        </span>
    </div>
    
    <div class="status-item">
        <span class="status-label">Tables Created (14 tables)</span>
        <span class="<?php echo isset($setup_status['tables']) && $setup_status['tables'] ? 'status-success' : 'status-error'; ?>">
            <i class="fas fa-<?php echo isset($setup_status['tables']) && $setup_status['tables'] ? 'check-circle' : 'times-circle'; ?>"></i>
            <?php echo isset($setup_status['tables']) && $setup_status['tables'] ? 'Complete' : 'Pending'; ?>
        </span>
    </div>
    
    <div class="status-item">
        <span class="status-label">Sample Data & Admin</span>
        <span class="<?php echo isset($setup_status['data']) && $setup_status['data'] ? 'status-success' : 'status-error'; ?>">
            <i class="fas fa-<?php echo isset($setup_status['data']) && $setup_status['data'] ? 'check-circle' : 'times-circle'; ?>"></i>
            <?php echo isset($setup_status['data']) && $setup_status['data'] ? 'Inserted' : 'Pending'; ?>
        </span>
    </div>
</div>