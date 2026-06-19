<?php
// users/delete.php — Admin deletes a user
define('BASE_URL', '/library_management');
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$pdo = getDB();
$id  = intval($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    flash('error', 'User not found.');
    header('Location: ' . BASE_URL . '/users/list.php'); exit();
}

$chk = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE user_id=? AND status='borrowed'");
$chk->execute([$id]);
if ($chk->fetchColumn() > 0) {
    flash('error', 'Cannot delete "'.$user['full_name'].'" — they still have borrowed books. Return them first.');
    header('Location: ' . BASE_URL . '/users/list.php'); exit();
}

$pdo->prepare("DELETE FROM users WHERE id=?")->execute([$id]);
logActivity('User Deleted','Deleted user: '.$user['full_name'].' ('.$user['username'].')');
flash('success', 'User "'.$user['full_name'].'" deleted.');
header('Location: ' . BASE_URL . '/users/list.php'); exit();
