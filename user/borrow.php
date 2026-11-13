<?php
// user/borrow.php — User self-borrow page
define('BASE_URL', '/library_management');
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireUserLogin();

$pdo       = getDB();
$pageTitle = 'Borrow a Book';
$userId    = $_SESSION['user_id'];
$errors    = [];

// AJAX book lookup
if (isset($_GET['lookup'])) {
    header('Content-Type: application/json');
    $q    = trim($_GET['lookup']);
    $stmt = $pdo->prepare("SELECT * FROM books WHERE book_number = ? OR title LIKE ? LIMIT 1");
    $stmt->execute([$q, "%$q%"]);
    echo json_encode($stmt->fetch() ?: ['error' => 'Book not found.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bookId    = intval($_POST['book_id'] ?? 0);
    $dueDate   = $_POST['due_date'] ?? '';
    $notes     = trim($_POST['notes'] ?? '');

    if (!$bookId)  $errors[] = 'Please find and select a book first.';
    if (!$dueDate) $errors[] = 'Due date is required.';

    $bookRow = null;
    if ($bookId) {
        $s = $pdo->prepare("SELECT * FROM books WHERE id = ?");
        $s->execute([$bookId]);
        $bookRow = $s->fetch();
        if (!$bookRow)                              $errors[] = 'Book not found.';
        elseif ($bookRow['status'] !== 'Available') $errors[] = 'Sorry, this book is currently not available.';
    }

    // Check if user already has this book borrowed
    if ($bookRow && empty($errors)) {
        $dup = $pdo->prepare("SELECT id FROM transactions WHERE user_id=? AND book_id=? AND status='borrowed'");
        $dup->execute([$userId, $bookId]);
        if ($dup->fetchColumn()) $errors[] = 'You already have this book borrowed.';
    }

    if (empty($errors) && $bookRow) {
        $pdo->beginTransaction();
        try {
            $ins = $pdo->prepare("
                INSERT INTO transactions (user_id, book_id, book_number, borrow_date, due_date, status, notes)
                VALUES (?, ?, ?, CURDATE(), ?, 'borrowed', ?)
            ");
            $ins->execute([$userId, $bookRow['id'], $bookRow['book_number'], $dueDate, $notes ?: null]);
            $pdo->prepare("UPDATE books SET status='Borrowed' WHERE id=?")->execute([$bookRow['id']]);
            $pdo->commit();
            logActivity('Book Borrowed (User)', $_SESSION['user_name'] . ' borrowed "' . $bookRow['title'] . '" (' . $bookRow['book_number'] . ')', $_SESSION['user_username']);
            flash('success', '"' . $bookRow['title'] . '" borrowed! Please return it by ' . date('F j, Y', strtotime($dueDate)) . '.');
            header('Location: ' . BASE_URL . '/user/history.php');
            exit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Could not process borrow. Please try again.';
        }
    }
}

require_once __DIR__ . '/header.php';
?>

<div class="section-card">
    <div class="section-header">
        <h3><i class="bi bi-box-arrow-right me-2"></i>Borrow a Book</h3>
        <a href="<?= BASE_URL ?>/user/books.php" class="btn-back">
            <i class="bi bi-search me-1"></i>Browse Books
        </a>
    </div>

    <?php if ($errors): ?>
    <div class="alert-error-box" style="margin:16px 20px 0;">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <ul class="mb-0 ps-3"><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
    </div>
    <?php endif; ?>

    <form method="POST" class="form-custom" id="borrowForm">
        <!-- Book Search -->
        <div class="lookup-box">
            <div class="lookup-label"><i class="bi bi-search me-1"></i>Find Book by Book Number or Title</div>
            <div class="lookup-row">
                <input type="text" id="bookLookup" class="form-ctrl"
                       placeholder="Type Book Number (e.g. SA0001) or part of title and press Enter or click Find"
                       autocomplete="off">
                <button type="button" class="btn-filter" onclick="lookupBook()">Find</button>
            </div>
            <div id="bookResult" class="book-lookup-result" style="display:none;"></div>
        </div>

        <input type="hidden" name="book_id" id="bookIdHidden">

        <div class="form-row-2" style="margin-top:14px;">
            <div class="form-group">
                <label class="form-lbl">Borrower</label>
                <input type="text" class="form-ctrl" readonly
                       value="<?= htmlspecialchars($_SESSION['user_name'] . ' (' . $_SESSION['user_member_id'] . ')') ?>"
                       style="background:#f5f5f5;color:#666;">
            </div>
            <div class="form-group">
                <label class="form-lbl">Due Date <span class="req">*</span></label>
                <input type="date" name="due_date" id="dueDateInput" class="form-ctrl" required
                       value="<?= date('Y-m-d', strtotime('+14 days')) ?>"
                       min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
            </div>
        </div>

        <div class="form-group">
            <label class="form-lbl">Notes</label>
            <textarea name="notes" class="form-ctrl" rows="2" placeholder="Optional notes..."></textarea>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-primary-custom" id="submitBtn" disabled style="opacity:.5;cursor:not-allowed;">
                <i class="bi bi-check-lg me-1"></i>Confirm Borrow
            </button>
            <span style="font-size:12px;color:#999;margin-left:8px;" id="submitHint">Find a book first to enable this button.</span>
        </div>
    </form>
</div>

<!-- Currently borrowing -->
<?php
$mine = $pdo->prepare("
    SELECT t.*, b.title AS book_title, b.book_number
    FROM transactions t JOIN books b ON t.book_id = b.id
    WHERE t.user_id = ? AND t.status = 'borrowed'
    ORDER BY t.due_date ASC
");
$mine->execute([$userId]);
$myActive = $mine->fetchAll();
?>
<?php if (!empty($myActive)): ?>
<div class="section-card mt-4">
    <div class="section-header">
        <h3><i class="bi bi-list-check me-2"></i>My Currently Borrowed Books</h3>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead><tr><th>Book Number</th><th>Title</th><th>Borrow Date</th><th>Due Date</th><th>Status</th></tr></thead>
            <tbody>
                <?php foreach ($myActive as $tx): ?>
                <tr>
                    <td><span class="book-num"><?= htmlspecialchars($tx['book_number']) ?></span></td>
                    <td><?= htmlspecialchars($tx['book_title']) ?></td>
                    <td><?= date('M j, Y', strtotime($tx['borrow_date'])) ?></td>
                    <td><?= date('M j, Y', strtotime($tx['due_date'])) ?></td>
                    <td>
                        <?php if (strtotime($tx['due_date']) < time()): ?>
                        <span class="badge-status badge-overdue">Overdue — please return ASAP</span>
                        <?php else: ?>
                        <span class="badge-status badge-borrowed">Active</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<script>
function lookupBook() {
    var q = document.getElementById('bookLookup').value.trim();
    if (!q) { alert('Please enter a Book Number or title to search.'); return; }
    fetch('<?= BASE_URL ?>/user/borrow.php?lookup=' + encodeURIComponent(q))
        .then(r => r.json()).then(data => {
            var div  = document.getElementById('bookResult');
            var btn  = document.getElementById('submitBtn');
            var hint = document.getElementById('submitHint');
            div.style.display = 'flex';
            if (data.error) {
                div.className = 'book-lookup-result lookup-error';
                div.innerHTML = '<i class="bi bi-x-circle me-2"></i>' + data.error;
                document.getElementById('bookIdHidden').value = '';
                btn.disabled = true; btn.style.opacity = '.5'; btn.style.cursor = 'not-allowed';
                hint.textContent = 'Book not found.';
            } else if (data.status !== 'Available') {
                div.className = 'book-lookup-result lookup-warning';
                div.innerHTML = '<i class="bi bi-exclamation-triangle me-2"></i><strong>' + data.book_number + '</strong> — ' + data.title + ' is currently <strong>Borrowed</strong> and not available.';
                document.getElementById('bookIdHidden').value = '';
                btn.disabled = true; btn.style.opacity = '.5'; btn.style.cursor = 'not-allowed';
                hint.textContent = 'This book is not available.';
            } else {
                div.className = 'book-lookup-result lookup-success';
                div.innerHTML = '<i class="bi bi-check-circle me-2"></i><strong>' + data.book_number + '</strong> — ' + data.title + ' by ' + data.author + ' &nbsp;<span class="badge-status badge-available" style="font-size:11px;">Available</span>';
                document.getElementById('bookIdHidden').value = data.id;
                btn.disabled = false; btn.style.opacity = '1'; btn.style.cursor = 'pointer';
                hint.textContent = '';
            }
        });
}
document.getElementById('bookLookup').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') { e.preventDefault(); lookupBook(); }
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
