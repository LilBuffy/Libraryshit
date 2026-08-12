<?php
// books/delete.php
define('BASE_URL', '/library_management');
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$pdo = getDB();
$id  = intval($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM books WHERE id = ?");
$stmt->execute([$id]);
$book = $stmt->fetch();

if (!$book) {
    flash('error', 'Book record not found.');
    header('Location: ' . BASE_URL . '/books/list.php');
    exit();
}

// Guard: cannot delete if currently borrowed
if ($book['status'] === 'Borrowed') {
    flash('error', 'Cannot delete "' . $book['title'] . '" — it is currently Borrowed. Process the return first.');
    header('Location: ' . BASE_URL . '/books/list.php');
    exit();
}

$pdo->prepare("DELETE FROM books WHERE id = ?")->execute([$id]);
logActivity('Book Deleted', 'Deleted: ' . $book['title'] . ' (' . $book['book_number'] . ')');
flash('success', 'Book "' . $book['title'] . '" (' . $book['book_number'] . ') has been deleted.');
header('Location: ' . BASE_URL . '/books/list.php');
exit();
