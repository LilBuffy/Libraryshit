<?php
// auth/logout.php — User logout
define('BASE_URL', '/library_management');
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

if (isUserLoggedIn()) {
    logActivity('User Logout', 'User logged out: ' . ($_SESSION['user_username'] ?? ''), $_SESSION['user_username'] ?? '');
}

// Clear only user session keys (keep admin session if somehow both active)
$keys = ['user_id','user_username','user_name','user_member_id','user_type'];
foreach ($keys as $k) unset($_SESSION[$k]);

// Full destroy
session_destroy();
header('Location: ' . BASE_URL . '/auth/login.php');
exit();
