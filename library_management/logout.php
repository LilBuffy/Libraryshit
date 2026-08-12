<?php
// logout.php
define('BASE_URL', '/library_management');
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    logActivity('Logout', 'Admin logged out: ' . ($_SESSION['admin_username'] ?? ''), $_SESSION['admin_username'] ?? '');
}

$_SESSION = [];
session_destroy();
header('Location: ' . BASE_URL . '/login.php');
exit();
