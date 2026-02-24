<?php
// Database Configuration - Updated for new database structure
require_once __DIR__ . '/../database/config.php';

// Legacy functions for backward compatibility
function getConnection() {
    global $pdo;
    return $pdo;
}

function getDBConnection() {
    global $pdo;
    return $pdo;
}
?>