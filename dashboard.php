<?php
define('BASE_URL', '/library_management');
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$pdo       = getDB();
$pageTitle = 'Dashboard';

$totalBooks     = $pdo->query("SELECT COUNT(*) FROM books")->fetchColumn() ?: 0;
$availableBooks = $pdo->query("SELECT COUNT(*) FROM books WHERE status='Available'")->fetchColumn() ?: 0;
$borrowedBooks  = $pdo->query("SELECT COUNT(*) FROM books WHERE status='Borrowed'")->fetchColumn() ?: 0;
$totalUsers     = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn() ?: 0;
$returnedBooks  = $pdo->query("SELECT COUNT(*) FROM transactions WHERE status='returned'")->fetchColumn() ?: 0;
$overdueBooks   = $pdo->query("SELECT COUNT(*) FROM transactions WHERE status='borrowed' AND due_date < CURDATE()")->fetchColumn() ?: 0;

$activities  = $pdo->query("SELECT * FROM activity_log ORDER BY created_at DESC LIMIT 8")->fetchAll();

$recentTx = $pdo->query("
    SELECT t.*,
           COALESCE(u.full_name, 'Walk-in Borrower') AS member_name,
           b.title AS book_title, b.book_number
    FROM transactions t
    LEFT JOIN users u ON t.user_id = u.id
    JOIN books b ON t.book_id = b.id
    ORDER BY t.created_at DESC LIMIT 8
")->fetchAll();

$topBooks = $pdo->query("
    SELECT b.book_number, b.title, b.author, COUNT(t.id) AS borrow_count
    FROM transactions t JOIN books b ON t.book_id = b.id
    GROUP BY b.id ORDER BY borrow_count DESC LIMIT 5
")->fetchAll();

// Chart data: last 7 days borrows & returns
$chartLabels  = [];
$chartBorrows = [];
$chartReturns = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $chartLabels[] = date('M j', strtotime($date));
    $b = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE borrow_date = ? AND status IN ('borrowed','returned','overdue')");
    $b->execute([$date]); $chartBorrows[] = (int)$b->fetchColumn();
    $r = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE return_date = ?");
    $r->execute([$date]); $chartReturns[] = (int)$r->fetchColumn();
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
window._chartLabels  = <?= json_encode($chartLabels) ?>;
window._chartBorrows = <?= json_encode($chartBorrows) ?>;
window._chartReturns = <?= json_encode($chartReturns) ?>;
</script>

<!-- Stat Cards -->
<div class="dash-grid">
    <div class="stat-card stat-primary">
        <div class="stat-icon"><i class="bi bi-journals"></i></div>
        <div class="stat-info"><div class="stat-value"><?= number_format($totalBooks) ?></div><div class="stat-label">Total Books</div></div>
    </div>
    <div class="stat-card stat-green">
        <div class="stat-icon"><i class="bi bi-bookmark-check-fill"></i></div>
        <div class="stat-info"><div class="stat-value"><?= number_format($availableBooks) ?></div><div class="stat-label">Available</div></div>
    </div>
    <div class="stat-card stat-orange">
        <div class="stat-icon"><i class="bi bi-box-arrow-right"></i></div>
        <div class="stat-info"><div class="stat-value"><?= number_format($borrowedBooks) ?></div><div class="stat-label">Currently Borrowed</div></div>
    </div>
    <div class="stat-card stat-blue">
        <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
        <div class="stat-info"><div class="stat-value"><?= number_format($totalUsers) ?></div><div class="stat-label">Registered Users</div></div>
    </div>
    <div class="stat-card stat-teal">
        <div class="stat-icon"><i class="bi bi-check-circle-fill"></i></div>
        <div class="stat-info"><div class="stat-value"><?= number_format($returnedBooks) ?></div><div class="stat-label">Total Returned</div></div>
    </div>
    <div class="stat-card stat-red">
        <div class="stat-icon"><i class="bi bi-exclamation-triangle-fill"></i></div>
        <div class="stat-info"><div class="stat-value"><?= number_format($overdueBooks) ?></div><div class="stat-label">Overdue</div></div>
    </div>
</div>

<!-- Quick Actions -->
<div class="section-card mt-4">
    <div class="section-header"><h3><i class="bi bi-grid-3x3-gap"></i>Quick Actions</h3></div>
    <div class="quick-actions">
        <a href="<?= BASE_URL ?>/books/add.php"            class="quick-btn"><i class="bi bi-plus-circle-fill"></i><span>Add Book</span></a>
        <a href="<?= BASE_URL ?>/users/list.php"           class="quick-btn"><i class="bi bi-people-fill"></i><span>Manage Users</span></a>
        <a href="<?= BASE_URL ?>/transactions/borrow.php"  class="quick-btn"><i class="bi bi-box-arrow-right"></i><span>Borrow Book</span></a>
        <a href="<?= BASE_URL ?>/transactions/return.php"  class="quick-btn"><i class="bi bi-box-arrow-in-left"></i><span>Return Book</span></a>
        <a href="<?= BASE_URL ?>/reports/index.php"        class="quick-btn"><i class="bi bi-file-earmark-text"></i><span>Reports</span></a>
        <a href="<?= BASE_URL ?>/books/list.php?status=Available" class="quick-btn"><i class="bi bi-bookmark-check"></i><span>Availability</span></a>
        <a href="<?= BASE_URL ?>/books/list.php"           class="quick-btn"><i class="bi bi-search"></i><span>Search Books</span></a>
        <a href="<?= BASE_URL ?>/transactions/history.php" class="quick-btn"><i class="bi bi-clock-history"></i><span>History</span></a>
    </div>
</div>

<div class="row mt-4">
    <!-- Activity Chart + Recent Transactions -->
    <div class="col-lg-8 mb-4">
        <!-- 7-day chart -->
        <div class="section-card" style="margin-bottom:16px;">
            <div class="section-header">
                <h3><i class="bi bi-bar-chart-line"></i>Library Activity — Last 7 Days</h3>
            </div>
            <div class="chart-wrap">
                <canvas id="activityChart"></canvas>
            </div>
        </div>

        <!-- Recent transactions -->
        <div class="section-card">
            <div class="section-header">
                <h3><i class="bi bi-clock-history"></i>Recent Transactions</h3>
                <a href="<?= BASE_URL ?>/transactions/history.php" class="btn-link">View all <i class="bi bi-arrow-right"></i></a>
            </div>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr><th>Book No.</th><th>Title</th><th>Member</th><th>Date</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentTx)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4" style="font-size:13px;">No transactions yet.</td></tr>
                        <?php else: foreach ($recentTx as $tx): ?>
                        <tr>
                            <td><span class="book-num"><?= htmlspecialchars($tx['book_number']) ?></span></td>
                            <td><?= htmlspecialchars(mb_strimwidth($tx['book_title'], 0, 32, '…')) ?></td>
                            <td><?= htmlspecialchars($tx['member_name']) ?></td>
                            <td style="color:var(--text-soft);font-size:12.5px;"><?= date('M j, Y', strtotime($tx['borrow_date'])) ?></td>
                            <td>
                                <?php $ov = ($tx['status']==='borrowed' && strtotime($tx['due_date'])<time()); ?>
                                <?php if ($tx['status']==='returned'): ?><span class="badge-status badge-returned">Returned</span>
                                <?php elseif ($ov): ?><span class="badge-status badge-overdue">Overdue</span>
                                <?php else: ?><span class="badge-status badge-borrowed">Borrowed</span><?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Right column -->
    <div class="col-lg-4 mb-4">
        <div class="section-card" style="margin-bottom:16px;">
            <div class="section-header"><h3><i class="bi bi-trophy"></i>Most Borrowed</h3></div>
            <div class="top-books-list">
                <?php if (empty($topBooks)): ?>
                <p class="text-muted text-center py-4" style="font-size:13px;">No data yet.</p>
                <?php else: foreach ($topBooks as $i => $b): ?>
                <div class="top-book-item">
                    <div class="top-book-rank"><?= $i+1 ?></div>
                    <div style="flex:1;min-width:0;">
                        <div class="top-book-title"><?= htmlspecialchars(mb_strimwidth($b['title'], 0, 26, '…')) ?></div>
                        <div class="top-book-author"><?= htmlspecialchars($b['book_number']) ?></div>
                    </div>
                    <div class="top-book-count"><?= $b['borrow_count'] ?>×</div>
                </div>
                <?php endforeach; endif; ?>
            </div>
        </div>

        <div class="section-card">
            <div class="section-header"><h3><i class="bi bi-activity"></i>Recent Activity</h3></div>
            <div class="activity-list">
                <?php foreach (array_slice($activities, 0, 6) as $act): ?>
                <div class="activity-item">
                    <div class="activity-dot"></div>
                    <div>
                        <div class="activity-action"><?= htmlspecialchars($act['action']) ?></div>
                        <div class="activity-time"><?= date('M j, g:i A', strtotime($act['created_at'])) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
