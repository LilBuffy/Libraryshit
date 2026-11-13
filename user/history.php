<?php
// user/history.php — User's own borrow history (FIXED v6)
define('BASE_URL', '/library_management');
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireUserLogin();

$pdo    = getDB();
$pageTitle = 'My Borrow History';
$userId = $_SESSION['user_id'];

$status  = $_GET['status'] ?? '';
$page    = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$offset  = ($page - 1) * $perPage;

$where  = ["t.user_id = ?"];
$params = [$userId];
if ($status !== '') { $where[] = "t.status = ?"; $params[] = $status; }
$whereSQL = 'WHERE ' . implode(' AND ', $where);

// Count total
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM transactions t $whereSQL");
$countStmt->execute($params);
$total      = (int)$countStmt->fetchColumn();
$totalPages = max(1, ceil($total / $perPage));

// Fetch records — no JOIN to users needed since we already know the user
$stmt = $pdo->prepare("
    SELECT t.*, b.title AS book_title, b.author, b.book_number, b.course_code
    FROM transactions t
    JOIN books b ON t.book_id = b.id
    $whereSQL
    ORDER BY t.created_at DESC
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$history = $stmt->fetchAll();

// Stats — fetch as integers immediately
$cntBorrowed = (int)$pdo->prepare("SELECT COUNT(*) FROM transactions WHERE user_id=? AND status='borrowed'")->execute([$userId])
    ? (function() use ($pdo, $userId) {
        $s = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE user_id=? AND status='borrowed'");
        $s->execute([$userId]);
        return (int)$s->fetchColumn();
    })() : 0;

// Simpler approach - just run each query clean
$s1 = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE user_id=? AND status='borrowed'");
$s1->execute([$userId]);
$cntBorrowed = (int)$s1->fetchColumn();

$s2 = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE user_id=? AND status='returned'");
$s2->execute([$userId]);
$cntReturned = (int)$s2->fetchColumn();

$s3 = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE user_id=? AND status='borrowed' AND due_date < CURDATE()");
$s3->execute([$userId]);
$cntOverdue = (int)$s3->fetchColumn();

require_once __DIR__ . '/header.php';
?>

<div class="mini-stats-row">
    <div class="mini-stat ms-orange">
        <i class="bi bi-box-arrow-right"></i>
        <div><div class="ms-num"><?= $cntBorrowed ?></div><div class="ms-lbl">Currently Borrowed</div></div>
    </div>
    <div class="mini-stat ms-green">
        <i class="bi bi-check-circle"></i>
        <div><div class="ms-num"><?= $cntReturned ?></div><div class="ms-lbl">Returned</div></div>
    </div>
    <div class="mini-stat">
        <i class="bi bi-exclamation-triangle" style="color:#dc2626;"></i>
        <div><div class="ms-num"><?= $cntOverdue ?></div><div class="ms-lbl">Overdue</div></div>
    </div>
</div>

<div class="section-card mt-3">
    <div class="section-header">
        <h3><i class="bi bi-clock-history me-2"></i>My Borrow History</h3>
        <a href="<?= BASE_URL ?>/user/borrow.php" class="btn-primary-custom"
           style="font-size:12.5px;padding:7px 14px;">
            <i class="bi bi-box-arrow-right me-1"></i>Borrow a Book
        </a>
    </div>

    <form method="GET" class="filter-bar">
        <div class="filter-row">
            <select name="status" class="filter-input filter-select">
                <option value="">All Transactions</option>
                <option value="borrowed" <?= $status==='borrowed'?'selected':'' ?>>Active Borrows</option>
                <option value="returned" <?= $status==='returned'?'selected':'' ?>>Returned</option>
            </select>
            <button type="submit" class="btn-filter">
                <i class="bi bi-funnel me-1"></i>Filter
            </button>
            <?php if ($status): ?>
            <a href="<?= BASE_URL ?>/user/history.php" class="btn-filter-reset">Clear</a>
            <?php endif; ?>
        </div>
    </form>

    <div class="table-info">
        Showing <strong><?= count($history) ?></strong> of <strong><?= $total ?></strong> transactions
    </div>

    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Book Number</th>
                    <th>Title</th>
                    <th>Author</th>
                    <th>Borrow Date</th>
                    <th>Due Date</th>
                    <th>Return Date</th>
                    <th>Status</th>
                    <th>Fine</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($history)): ?>
                <tr>
                    <td colspan="9" class="text-center text-muted py-5">No transactions found.</td>
                </tr>
                <?php else: foreach ($history as $i => $tx):
                    $isOverdue = ($tx['status'] === 'borrowed' && strtotime($tx['due_date']) < time());
                ?>
                <tr>
                    <td><?= $offset + $i + 1 ?></td>
                    <td><span class="book-num"><?= htmlspecialchars($tx['book_number']) ?></span></td>
                    <td><?= htmlspecialchars(mb_strimwidth($tx['book_title'], 0, 40, '…')) ?></td>
                    <td><?= htmlspecialchars($tx['author']) ?></td>
                    <td><?= date('M j, Y', strtotime($tx['borrow_date'])) ?></td>
                    <td><?= date('M j, Y', strtotime($tx['due_date'])) ?></td>
                    <td><?= $tx['return_date'] ? date('M j, Y', strtotime($tx['return_date'])) : '—' ?></td>
                    <td>
                        <?php if ($tx['status'] === 'returned'): ?>
                            <span class="badge-status badge-returned">Returned</span>
                        <?php elseif ($isOverdue): ?>
                            <span class="badge-status badge-overdue">Overdue</span>
                        <?php else: ?>
                            <span class="badge-status badge-borrowed">Active</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($tx['fine_amount'] > 0): ?>
                            <span style="color:<?= $tx['fine_paid']?'#666':'#c2410c' ?>;
                                         font-weight:<?= $tx['fine_paid']?'normal':'600' ?>;">
                                ₱<?= number_format($tx['fine_amount'], 2) ?>
                                <?= $tx['fine_paid']?'<small style="color:#888;"> (Paid)</small>':'' ?>
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
            <li class="<?= $page<=1?'disabled':'' ?>">
                <a href="?page=<?= $page-1 ?>&status=<?= $status ?>"><i class="bi bi-chevron-left"></i></a>
            </li>
            <?php for ($i=1; $i<=$totalPages; $i++): ?>
            <li class="<?= $i===$page?'active':'' ?>">
                <a href="?page=<?= $i ?>&status=<?= $status ?>"><?= $i ?></a>
            </li>
            <?php endfor; ?>
            <li class="<?= $page>=$totalPages?'disabled':'' ?>">
                <a href="?page=<?= $page+1 ?>&status=<?= $status ?>"><i class="bi bi-chevron-right"></i></a>
            </li>
        </ul>
    </nav>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
