<?php
// user/dashboard.php
define('BASE_URL', '/library_management');
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireUserLogin();

$pdo       = getDB();
$pageTitle = 'Dashboard';
$userId    = $_SESSION['user_id'];

$totalBooks     = $pdo->query("SELECT COUNT(*) FROM books")->fetchColumn();
$availableBooks = $pdo->query("SELECT COUNT(*) FROM books WHERE status='Available'")->fetchColumn();

$myBorrowed = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE user_id=? AND status='borrowed'");
$myBorrowed->execute([$userId]);
$myBorrowedCount = $myBorrowed->fetchColumn();

$myTotal = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE user_id=?");
$myTotal->execute([$userId]);
$myTotalCount = $myTotal->fetchColumn();

$myHistory = $pdo->prepare("
    SELECT t.*, b.title AS book_title, b.author, b.book_number
    FROM transactions t
    JOIN books b ON t.book_id = b.id
    WHERE t.user_id = ?
    ORDER BY t.created_at DESC LIMIT 5
");
$myHistory->execute([$userId]);
$recentBorrows = $myHistory->fetchAll();

require_once __DIR__ . '/header.php';
?>

<div class="dash-grid">
    <div class="stat-card stat-primary">
        <div class="stat-icon"><i class="bi bi-journals"></i></div>
        <div class="stat-info"><div class="stat-value"><?= number_format($totalBooks) ?></div><div class="stat-label">Total Books in Library</div></div>
    </div>
    <div class="stat-card stat-green">
        <div class="stat-icon"><i class="bi bi-bookmark-check-fill"></i></div>
        <div class="stat-info"><div class="stat-value"><?= number_format($availableBooks) ?></div><div class="stat-label">Available to Borrow</div></div>
    </div>
    <div class="stat-card stat-orange">
        <div class="stat-icon"><i class="bi bi-box-arrow-right"></i></div>
        <div class="stat-info"><div class="stat-value"><?= number_format($myBorrowedCount) ?></div><div class="stat-label">My Active Borrows</div></div>
    </div>
    <div class="stat-card stat-blue">
        <div class="stat-icon"><i class="bi bi-clock-history"></i></div>
        <div class="stat-info"><div class="stat-value"><?= number_format($myTotalCount) ?></div><div class="stat-label">My Total Transactions</div></div>
    </div>
</div>

<div class="section-card mt-4">
    <div class="section-header">
        <h3><i class="bi bi-grid me-2"></i>Quick Actions</h3>
    </div>
    <div class="quick-actions">
        <a href="<?= BASE_URL ?>/user/books.php" class="quick-btn"><i class="bi bi-search"></i><span>Search Books</span></a>
        <a href="<?= BASE_URL ?>/user/borrow.php" class="quick-btn"><i class="bi bi-box-arrow-right"></i><span>Borrow a Book</span></a>
        <a href="<?= BASE_URL ?>/user/history.php" class="quick-btn"><i class="bi bi-clock-history"></i><span>My History</span></a>
        <a href="<?= BASE_URL ?>/user/profile.php" class="quick-btn"><i class="bi bi-person-gear"></i><span>My Profile</span></a>
        <a href="<?= BASE_URL ?>/user/books.php?status=Available" class="quick-btn"><i class="bi bi-bookmark-check"></i><span>Available Books</span></a>
    </div>
</div>

<div class="section-card mt-4">
    <div class="section-header">
        <h3><i class="bi bi-clock-history me-2"></i>My Recent Borrows</h3>
        <a href="<?= BASE_URL ?>/user/history.php" class="btn-link">View All</a>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr><th>Book Number</th><th>Title</th><th>Author</th><th>Borrow Date</th><th>Due Date</th><th>Status</th></tr>
            </thead>
            <tbody>
                <?php if (empty($recentBorrows)): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">You have not borrowed any books yet.</td></tr>
                <?php else: foreach ($recentBorrows as $tx): ?>
                <tr>
                    <td><span class="book-num"><?= htmlspecialchars($tx['book_number']) ?></span></td>
                    <td><?= htmlspecialchars(mb_strimwidth($tx['book_title'], 0, 45, '...')) ?></td>
                    <td><?= htmlspecialchars($tx['author']) ?></td>
                    <td><?= date('M j, Y', strtotime($tx['borrow_date'])) ?></td>
                    <td><?= date('M j, Y', strtotime($tx['due_date'])) ?></td>
                    <td>
                        <?php
                        $isOverdue = ($tx['status'] === 'borrowed' && strtotime($tx['due_date']) < time());
                        if ($tx['status'] === 'returned'): ?>
                        <span class="badge-status badge-returned">Returned</span>
                        <?php elseif ($isOverdue): ?>
                        <span class="badge-status badge-overdue">Overdue</span>
                        <?php else: ?>
                        <span class="badge-status badge-borrowed">Active</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
