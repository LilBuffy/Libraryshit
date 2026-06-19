<?php
// books/list.php
define('BASE_URL', '/library_management');
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$pdo = getDB();
$pageTitle = 'Book Records';

$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? '';
$page   = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$offset  = ($page - 1) * $perPage;

$where  = [];
$params = [];

if ($search !== '') {
    // Search across all fields including status
    $where[]  = "(b.book_number LIKE ? OR b.title LIKE ? OR b.author LIKE ? OR b.course_code LIKE ? OR b.copyright_year LIKE ? OR b.edition LIKE ? OR b.status LIKE ?)";
    $s = "%$search%";
    $params[] = $s; $params[] = $s; $params[] = $s; $params[] = $s; $params[] = $s; $params[] = $s; $params[] = $s;
}
if ($status !== '') {
    $where[]  = "b.status = ?";
    $params[] = $status;
}

$whereSQL   = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$countStmt  = $pdo->prepare("SELECT COUNT(*) FROM books b $whereSQL");
$countStmt->execute($params);
$total      = $countStmt->fetchColumn();
$totalPages = max(1, ceil($total / $perPage));

$stmt = $pdo->prepare("SELECT * FROM books b $whereSQL ORDER BY b.book_number ASC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$books = $stmt->fetchAll();

// Stats
$totalBooks     = $pdo->query("SELECT COUNT(*) FROM books")->fetchColumn();
$availableCount = $pdo->query("SELECT COUNT(*) FROM books WHERE status='Available'")->fetchColumn();
$borrowedCount  = $pdo->query("SELECT COUNT(*) FROM books WHERE status='Borrowed'")->fetchColumn();

require_once __DIR__ . '/../includes/header.php';
?>

<!-- Mini Stats -->
<div class="mini-stats-row">
    <div class="mini-stat">
        <i class="bi bi-journals"></i>
        <div>
            <div class="ms-num"><?= number_format($totalBooks) ?></div>
            <div class="ms-lbl">Total Books</div>
        </div>
    </div>
    <div class="mini-stat ms-green">
        <i class="bi bi-bookmark-check"></i>
        <div>
            <div class="ms-num"><?= number_format($availableCount) ?></div>
            <div class="ms-lbl">Available</div>
        </div>
    </div>
    <div class="mini-stat ms-orange">
        <i class="bi bi-box-arrow-right"></i>
        <div>
            <div class="ms-num"><?= number_format($borrowedCount) ?></div>
            <div class="ms-lbl">Borrowed</div>
        </div>
    </div>
</div>

<div class="section-card mt-3">
    <div class="section-header">
        <h3><i class="bi bi-journals me-2"></i>Book Records</h3>
        <a href="<?= BASE_URL ?>/books/add.php" class="btn-primary-custom">
            <i class="bi bi-plus-lg me-1"></i>Add Book
        </a>
    </div>

    <!-- Search & Filter -->
    <form method="GET" class="filter-bar">
        <div class="filter-row">
            <div class="search-wrap">
                <i class="bi bi-search search-icon"></i>
                <input type="text" name="search" class="filter-input search-input"
                       placeholder="Search by Book No., Title, Author, Course Code, Copyright, Edition, Status..."
                       value="<?= htmlspecialchars($search) ?>">
            </div>
            <select name="status" class="filter-input filter-select">
                <option value="">All Status</option>
                <option value="Available" <?= $status === 'Available' ? 'selected' : '' ?>>Available</option>
                <option value="Borrowed"  <?= $status === 'Borrowed'  ? 'selected' : '' ?>>Borrowed</option>
            </select>
            <button type="submit" class="btn-filter"><i class="bi bi-search me-1"></i>Search</button>
            <?php if ($search || $status): ?>
            <a href="<?= BASE_URL ?>/books/list.php" class="btn-filter-reset">Clear</a>
            <?php endif; ?>
        </div>
    </form>

    <div class="table-info">
        <?php if ($search || $status): ?>
            Search results for
            <?php if ($search): ?><strong>"<?= htmlspecialchars($search) ?>"</strong><?php endif; ?>
            <?php if ($status): ?><strong>[<?= htmlspecialchars($status) ?>]</strong><?php endif; ?>
            — found <strong><?= $total ?></strong> record(s)
        <?php else: ?>
            Showing <strong><?= count($books) ?></strong> of <strong><?= $total ?></strong> books
        <?php endif; ?>
    </div>

    <div class="table-responsive">
        <table class="data-table books-table">
            <thead>
                <tr>
                    <th>Book Number</th>
                    <th>Title</th>
                    <th>Copyright</th>
                    <th>Edition</th>
                    <th>Author</th>
                    <th>Course Code</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($books)): ?>
                <tr><td colspan="8" class="text-center text-muted py-5">No book records found.</td></tr>
                <?php else: foreach ($books as $b): ?>
                <tr>
                    <td><span class="book-num"><?= htmlspecialchars($b['book_number']) ?></span></td>
                    <td><?= htmlspecialchars($b['title']) ?></td>
                    <td><?= htmlspecialchars($b['copyright_year'] ?: '—') ?></td>
                    <td><?= htmlspecialchars($b['edition'] ?: '—') ?></td>
                    <td><?= htmlspecialchars($b['author']) ?></td>
                    <td><span class="course-code"><?= htmlspecialchars($b['course_code'] ?: '—') ?></span></td>
                    <td>
                        <?php if ($b['status'] === 'Available'): ?>
                        <span class="badge-status badge-available">Available</span>
                        <?php else: ?>
                        <span class="badge-status badge-borrowed">Borrowed</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="action-btns">
                            <a href="<?= BASE_URL ?>/books/edit.php?id=<?= $b['id'] ?>" class="btn-action btn-edit" title="Edit">
                                <i class="bi bi-pencil-fill"></i>
                            </a>
                            <a href="<?= BASE_URL ?>/books/delete.php?id=<?= $b['id'] ?>"
                               class="btn-action btn-delete" title="Delete"
                               onclick="return confirm('Delete this book record? This cannot be undone.')">
                                <i class="bi bi-trash-fill"></i>
                            </a>
                        </div>
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
                <a href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>&status=<?= $status ?>"><i class="bi bi-chevron-left"></i></a>
            </li>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <li class="<?= $i === $page ? 'active' : '' ?>">
                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= $status ?>"><?= $i ?></a>
            </li>
            <?php endfor; ?>
            <li class="<?= $page >= $totalPages ? 'disabled' : '' ?>">
                <a href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>&status=<?= $status ?>"><i class="bi bi-chevron-right"></i></a>
            </li>
        </ul>
    </nav>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
