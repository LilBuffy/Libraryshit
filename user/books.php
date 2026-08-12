<?php
// user/books.php — Browse books (read-only for users)
define('BASE_URL', '/library_management');
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireUserLogin();

$pdo       = getDB();
$pageTitle = 'Browse Books';

$search  = trim($_GET['search'] ?? '');
$status  = $_GET['status'] ?? '';
$page    = max(1, intval($_GET['page'] ?? 1));
$perPage = 25;
$offset  = ($page - 1) * $perPage;

$where  = [];
$params = [];

if ($search !== '') {
    $where[]  = "(book_number LIKE ? OR title LIKE ? OR author LIKE ? OR course_code LIKE ? OR copyright_year LIKE ? OR edition LIKE ? OR status LIKE ?)";
    $s = "%$search%";
    for ($i = 0; $i < 7; $i++) $params[] = $s;
}
if ($status !== '') { $where[] = "status = ?"; $params[] = $status; }

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$total      = $pdo->prepare("SELECT COUNT(*) FROM books $whereSQL");
$total->execute($params);
$total      = $total->fetchColumn();
$totalPages = max(1, ceil($total / $perPage));

$stmt = $pdo->prepare("SELECT * FROM books $whereSQL ORDER BY book_number ASC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$books = $stmt->fetchAll();

$availCount  = $pdo->query("SELECT COUNT(*) FROM books WHERE status='Available'")->fetchColumn();
$borrowCount = $pdo->query("SELECT COUNT(*) FROM books WHERE status='Borrowed'")->fetchColumn();

require_once __DIR__ . '/header.php';
?>

<div class="mini-stats-row">
    <div class="mini-stat"><i class="bi bi-journals"></i><div><div class="ms-num"><?= number_format($total) ?></div><div class="ms-lbl">Total Books</div></div></div>
    <div class="mini-stat ms-green"><i class="bi bi-bookmark-check"></i><div><div class="ms-num"><?= number_format($availCount) ?></div><div class="ms-lbl">Available</div></div></div>
    <div class="mini-stat ms-orange"><i class="bi bi-box-arrow-right"></i><div><div class="ms-num"><?= number_format($borrowCount) ?></div><div class="ms-lbl">Borrowed</div></div></div>
</div>

<div class="section-card mt-3">
    <div class="section-header">
        <h3><i class="bi bi-search me-2"></i>Search Book Records</h3>
        <a href="<?= BASE_URL ?>/user/borrow.php" class="btn-primary-custom" style="font-size:12.5px;padding:7px 14px;">
            <i class="bi bi-box-arrow-right me-1"></i>Borrow a Book
        </a>
    </div>

    <form method="GET" class="filter-bar">
        <div class="filter-row">
            <div class="search-wrap">
                <i class="bi bi-search search-icon"></i>
                <input type="text" name="search" class="filter-input search-input"
                       placeholder="Search Book No., Title, Author, Course Code, Year, Status..."
                       value="<?= htmlspecialchars($search) ?>">
            </div>
            <select name="status" class="filter-input filter-select">
                <option value="">All Status</option>
                <option value="Available" <?= $status === 'Available' ? 'selected' : '' ?>>Available</option>
                <option value="Borrowed"  <?= $status === 'Borrowed'  ? 'selected' : '' ?>>Borrowed</option>
            </select>
            <button type="submit" class="btn-filter"><i class="bi bi-search me-1"></i>Search</button>
            <?php if ($search || $status): ?>
            <a href="<?= BASE_URL ?>/user/books.php" class="btn-filter-reset">Clear</a>
            <?php endif; ?>
        </div>
    </form>

    <div class="table-info">
        <?php if ($search || $status): ?>
        Search: <strong>"<?= htmlspecialchars($search ?: $status) ?>"</strong> — <strong><?= $total ?></strong> result(s)
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
                </tr>
            </thead>
            <tbody>
                <?php if (empty($books)): ?>
                <tr><td colspan="7" class="text-center text-muted py-5">No books found matching your search.</td></tr>
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
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
    <nav class="pagination-wrap">
        <ul class="custom-pagination">
            <li class="<?= $page<=1?'disabled':'' ?>">
                <a href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>&status=<?= $status ?>"><i class="bi bi-chevron-left"></i></a>
            </li>
            <?php
            $start = max(1, $page - 2);
            $end   = min($totalPages, $page + 2);
            if ($start > 1): ?><li><a href="?page=1&search=<?= urlencode($search) ?>&status=<?= $status ?>">1</a></li><?php endif;
            for ($i = $start; $i <= $end; $i++): ?>
            <li class="<?= $i===$page?'active':'' ?>">
                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= $status ?>"><?= $i ?></a>
            </li>
            <?php endfor;
            if ($end < $totalPages): ?><li><a href="?page=<?= $totalPages ?>&search=<?= urlencode($search) ?>&status=<?= $status ?>"><?= $totalPages ?></a></li><?php endif; ?>
            <li class="<?= $page>=$totalPages?'disabled':'' ?>">
                <a href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>&status=<?= $status ?>"><i class="bi bi-chevron-right"></i></a>
            </li>
        </ul>
    </nav>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
