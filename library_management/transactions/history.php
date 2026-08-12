<?php
// transactions/history.php — Admin full transaction history (FIXED v6)
define('BASE_URL', '/library_management');
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$pdo       = getDB();
$pageTitle = 'Transaction History';

$search  = trim($_GET['search'] ?? '');
$status  = $_GET['status'] ?? '';
$page    = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$offset  = ($page - 1) * $perPage;

$where  = [];
$params = [];

if ($search !== '') {
    // Search across borrower name/id and book fields
    $where[]  = "(COALESCE(u.full_name,'') LIKE ? OR b.title LIKE ? OR COALESCE(u.member_id,'') LIKE ? OR t.book_number LIKE ?)";
    $s = "%$search%";
    $params[] = $s; $params[] = $s; $params[] = $s; $params[] = $s;
}
if ($status !== '') {
    $where[]  = "t.status = ?";
    $params[] = $status;
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// LEFT JOIN so transactions with null user_id are NOT excluded
$countStmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM transactions t
    LEFT JOIN users u ON t.user_id = u.id
    JOIN books b ON t.book_id = b.id
    $whereSQL
");
$countStmt->execute($params);
$total      = $countStmt->fetchColumn();
$totalPages = max(1, ceil($total / $perPage));

$stmt = $pdo->prepare("
    SELECT
        t.*,
        COALESCE(u.full_name, 'Walk-in Borrower') AS full_name,
        COALESCE(u.member_id, '—') AS mid,
        b.title AS book_title,
        b.author
    FROM transactions t
    LEFT JOIN users u ON t.user_id = u.id
    JOIN books b ON t.book_id = b.id
    $whereSQL
    ORDER BY t.created_at DESC
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$transactions = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="section-card">
    <div class="section-header">
        <h3><i class="bi bi-clock-history me-2"></i>Transaction History</h3>
        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>/transactions/borrow.php"
               class="btn-primary-custom" style="font-size:12.5px;padding:7px 14px;">
                <i class="bi bi-box-arrow-right me-1"></i>Borrow
            </a>
            <a href="<?= BASE_URL ?>/transactions/return.php"
               class="btn-back" style="font-size:12.5px;padding:7px 14px;">
                <i class="bi bi-box-arrow-in-left me-1"></i>Return
            </a>
        </div>
    </div>

    <form method="GET" class="filter-bar">
        <div class="filter-row">
            <div class="search-wrap">
                <i class="bi bi-search search-icon"></i>
                <input type="text" name="search" class="filter-input search-input"
                       placeholder="Search member name, book title, book number..."
                       value="<?= htmlspecialchars($search) ?>">
            </div>
            <select name="status" class="filter-input filter-select">
                <option value="">All Status</option>
                <option value="borrowed"  <?= $status === 'borrowed'  ? 'selected' : '' ?>>Borrowed</option>
                <option value="returned"  <?= $status === 'returned'  ? 'selected' : '' ?>>Returned</option>
                <option value="overdue"   <?= $status === 'overdue'   ? 'selected' : '' ?>>Overdue</option>
            </select>
            <button type="submit" class="btn-filter">
                <i class="bi bi-search me-1"></i>Search
            </button>
            <?php if ($search || $status): ?>
            <a href="<?= BASE_URL ?>/transactions/history.php" class="btn-filter-reset">Clear</a>
            <?php endif; ?>
        </div>
    </form>

    <div class="table-info">
        Showing <strong><?= count($transactions) ?></strong> of <strong><?= $total ?></strong> transactions
    </div>

    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Book Number</th>
                    <th>Book Title</th>
                    <th>Borrower</th>
                    <th>Borrow Date</th>
                    <th>Due Date</th>
                    <th>Return Date</th>
                    <th>Status</th>
                    <th>Fine</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($transactions)): ?>
                <tr>
                    <td colspan="9" class="text-center text-muted py-5">No transactions found.</td>
                </tr>
                <?php else: foreach ($transactions as $i => $tx):
                    $isOverdue = ($tx['status'] === 'borrowed' && strtotime($tx['due_date']) < time());
                ?>
                <tr>
                    <td><?= $offset + $i + 1 ?></td>
                    <td><span class="book-num"><?= htmlspecialchars($tx['book_number']) ?></span></td>
                    <td>
                        <?= htmlspecialchars(mb_strimwidth($tx['book_title'], 0, 35, '…')) ?>
                        <br><small class="text-muted"><?= htmlspecialchars($tx['author']) ?></small>
                    </td>
                    <td>
                        <?= htmlspecialchars($tx['full_name']) ?>
                        <br><small class="text-muted"><?= htmlspecialchars($tx['mid']) ?></small>
                    </td>
                    <td><?= date('M j, Y', strtotime($tx['borrow_date'])) ?></td>
                    <td><?= date('M j, Y', strtotime($tx['due_date'])) ?></td>
                    <td><?= $tx['return_date'] ? date('M j, Y', strtotime($tx['return_date'])) : '—' ?></td>
                    <td>
                        <?php if ($tx['status'] === 'returned'): ?>
                            <span class="badge-status badge-returned">Returned</span>
                        <?php elseif ($isOverdue): ?>
                            <span class="badge-status badge-overdue">Overdue</span>
                        <?php else: ?>
                            <span class="badge-status badge-borrowed">Borrowed</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($tx['fine_amount'] > 0): ?>
                        <span style="color:<?= $tx['fine_paid'] ? '#666' : '#c2410c' ?>;
                                     font-weight:<?= $tx['fine_paid'] ? 'normal' : '600' ?>;">
                            ₱<?= number_format($tx['fine_amount'], 2) ?>
                            <?= $tx['fine_paid'] ? '<br><small style="color:#888;font-weight:normal;">(Paid)</small>' : '' ?>
                        </span>
                        <?php else: ?>
                        <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
    <nav class="pagination-wrap">
        <ul class="custom-pagination">
            <li class="<?= $page <= 1 ? 'disabled' : '' ?>">
                <a href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>&status=<?= $status ?>">
                    <i class="bi bi-chevron-left"></i>
                </a>
            </li>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <li class="<?= $i === $page ? 'active' : '' ?>">
                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= $status ?>">
                    <?= $i ?>
                </a>
            </li>
            <?php endfor; ?>
            <li class="<?= $page >= $totalPages ? 'disabled' : '' ?>">
                <a href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>&status=<?= $status ?>">
                    <i class="bi bi-chevron-right"></i>
                </a>
            </li>
        </ul>
    </nav>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
