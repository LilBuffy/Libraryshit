<?php
// transactions/borrow.php — Admin borrow (supports both users and walk-in)
define('BASE_URL', '/library_management');
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$pdo = getDB();
$pageTitle = 'Borrow Book';
$errors = [];

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
    $borrowerId = intval($_POST['borrower_id'] ?? 0); // user.id
    $bookId     = intval($_POST['book_id']     ?? 0);
    $borrowDate = $_POST['borrow_date'] ?? date('Y-m-d');
    $dueDate    = $_POST['due_date']    ?? '';
    $notes      = trim($_POST['notes']  ?? '');

    if (!$borrowerId) $errors[] = 'Please select a borrower.';
    if (!$bookId)     $errors[] = 'Please select or find a book.';
    if (!$dueDate)    $errors[] = 'Due date is required.';

    $borrower = null;
    if ($borrowerId) {
        $s = $pdo->prepare("SELECT * FROM users WHERE id = ? AND status = 'active'");
        $s->execute([$borrowerId]);
        $borrower = $s->fetch();
        if (!$borrower) $errors[] = 'Borrower not found or account not active.';
    }

    $bookRow = null;
    if ($bookId) {
        $s = $pdo->prepare("SELECT * FROM books WHERE id = ?");
        $s->execute([$bookId]);
        $bookRow = $s->fetch();
        if (!$bookRow)                              $errors[] = 'Book not found.';
        elseif ($bookRow['status'] !== 'Available') $errors[] = 'This book is not available (currently Borrowed).';
    }

    if ($borrower && $bookRow && empty($errors)) {
        $dup = $pdo->prepare("SELECT id FROM transactions WHERE user_id=? AND book_id=? AND status='borrowed'");
        $dup->execute([$borrower['id'], $bookId]);
        if ($dup->fetchColumn()) $errors[] = 'This borrower already has this book borrowed.';
    }

    if (empty($errors) && $bookRow && $borrower) {
        $pdo->beginTransaction();
        try {
            $ins = $pdo->prepare("
                INSERT INTO transactions (user_id, book_id, book_number, borrow_date, due_date, status, notes)
                VALUES (?, ?, ?, ?, ?, 'borrowed', ?)
            ");
            $ins->execute([$borrower['id'], $bookRow['id'], $bookRow['book_number'], $borrowDate, $dueDate, $notes ?: null]);
            $pdo->prepare("UPDATE books SET status='Borrowed' WHERE id=?")->execute([$bookRow['id']]);
            $pdo->commit();
            logActivity('Book Borrowed', $borrower['full_name'] . ' borrowed "' . $bookRow['title'] . '" (' . $bookRow['book_number'] . ')');
            flash('success', '"' . $bookRow['title'] . '" issued to ' . $borrower['full_name'] . '. Due: ' . date('M j, Y', strtotime($dueDate)));
            header('Location: ' . BASE_URL . '/transactions/borrow.php');
            exit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Transaction failed. Please try again.';
        }
    }
}

$borrowers  = $pdo->query("SELECT id, member_id, full_name, membership_type FROM users WHERE status='active' ORDER BY full_name")->fetchAll();
$availBooks = $pdo->query("SELECT id, book_number, title, author FROM books WHERE status='Available' ORDER BY book_number")->fetchAll();
$activeBorrows = $pdo->query("
    SELECT t.*, COALESCE(u.full_name,'Walk-in Borrower') AS full_name, COALESCE(u.member_id,'—') AS mid, b.title AS book_title, b.book_number
    FROM transactions t
    LEFT JOIN users u ON t.user_id = u.id
    JOIN books b ON t.book_id = b.id
    WHERE t.status = 'borrowed'
    ORDER BY t.due_date ASC LIMIT 30
")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="section-card">
    <div class="section-header">
        <h3><i class="bi bi-box-arrow-right me-2"></i>Borrow Book</h3>
        <a href="<?= BASE_URL ?>/transactions/history.php" class="btn-back"><i class="bi bi-clock-history me-1"></i>History</a>
    </div>

    <?php if ($errors): ?>
    <div class="alert-error-box" style="margin:16px 20px 0;">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <ul class="mb-0 ps-3"><?php foreach($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
    </div>
    <?php endif; ?>

    <form method="POST" class="form-custom" id="borrowForm">
        <div class="lookup-box">
            <div class="lookup-label"><i class="bi bi-search me-1"></i>Find Book by Book Number or Title</div>
            <div class="lookup-row">
                <input type="text" id="bookLookup" class="form-ctrl"
                       placeholder="Type Book Number (e.g. SA0001) or title — press Enter or click Find" autocomplete="off">
                <button type="button" class="btn-filter" onclick="lookupBook()">Find</button>
            </div>
            <div id="bookResult" class="book-lookup-result" style="display:none;"></div>
        </div>
        <input type="hidden" name="book_id" id="bookIdHidden">

        <div class="form-row-2" style="margin-top:14px;">
            <div class="form-group">
                <label class="form-lbl">Borrower (Member) <span class="req">*</span></label>
                <select name="borrower_id" class="form-ctrl" required>
                    <option value="">-- Select Borrower --</option>
                    <?php foreach ($borrowers as $b): ?>
                    <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['member_id'] . ' — ' . $b['full_name'] . ' (' . ucfirst($b['membership_type']) . ')') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-lbl">Or Select from Available Books</label>
                <select id="bookSelect" class="form-ctrl" onchange="selectBook(this)">
                    <option value="">-- Select Available Book --</option>
                    <?php foreach ($availBooks as $b): ?>
                    <option value="<?= $b['id'] ?>" data-num="<?= htmlspecialchars($b['book_number']) ?>" data-title="<?= htmlspecialchars($b['title']) ?>" data-author="<?= htmlspecialchars($b['author']) ?>">
                        <?= htmlspecialchars($b['book_number'] . ' — ' . $b['title']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-row-2">
            <div class="form-group">
                <label class="form-lbl">Borrow Date</label>
                <input type="date" name="borrow_date" class="form-ctrl" value="<?= date('Y-m-d') ?>">
            </div>
            <div class="form-group">
                <label class="form-lbl">Due Date <span class="req">*</span></label>
                <input type="date" name="due_date" id="dueDateInput" class="form-ctrl" required value="<?= date('Y-m-d', strtotime('+14 days')) ?>">
            </div>
        </div>
        <div class="form-group">
            <label class="form-lbl">Notes</label>
            <textarea name="notes" class="form-ctrl" rows="2" placeholder="Optional notes..."></textarea>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn-primary-custom"><i class="bi bi-check-lg me-1"></i>Issue Book</button>
        </div>
    </form>
</div>

<div class="section-card mt-4">
    <div class="section-header">
        <h3><i class="bi bi-list-check me-2"></i>Currently Borrowed Books</h3>
        <span style="font-size:12px;color:#666;"><?= count($activeBorrows) ?> active</span>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead><tr><th>Book Number</th><th>Title</th><th>Borrower</th><th>Borrow Date</th><th>Due Date</th><th>Status</th></tr></thead>
            <tbody>
                <?php if (empty($activeBorrows)): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">No active borrows.</td></tr>
                <?php else: foreach ($activeBorrows as $tx): ?>
                <tr>
                    <td><span class="book-num"><?= htmlspecialchars($tx['book_number']) ?></span></td>
                    <td><?= htmlspecialchars($tx['book_title']) ?></td>
                    <td><?= htmlspecialchars($tx['full_name']) ?><br><small class="text-muted"><?= $tx['mid'] ?></small></td>
                    <td><?= date('M j, Y', strtotime($tx['borrow_date'])) ?></td>
                    <td><?= date('M j, Y', strtotime($tx['due_date'])) ?></td>
                    <td><?= strtotime($tx['due_date'])<time() ? '<span class="badge-status badge-overdue">Overdue</span>' : '<span class="badge-status badge-borrowed">Active</span>' ?></td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function lookupBook() {
    var q = document.getElementById('bookLookup').value.trim();
    if (!q) { alert('Please enter a Book Number or title.'); return; }
    fetch('<?= BASE_URL ?>/transactions/borrow.php?lookup=' + encodeURIComponent(q))
        .then(r=>r.json()).then(data=>{
            var div=document.getElementById('bookResult'); div.style.display='flex';
            if(data.error){div.className='book-lookup-result lookup-error';div.innerHTML='<i class="bi bi-x-circle me-2"></i>'+data.error;document.getElementById('bookIdHidden').value='';}
            else if(data.status!=='Available'){div.className='book-lookup-result lookup-warning';div.innerHTML='<i class="bi bi-exclamation-triangle me-2"></i><strong>'+data.book_number+'</strong> — '+data.title+' is currently <strong>Borrowed</strong>.';document.getElementById('bookIdHidden').value='';}
            else{div.className='book-lookup-result lookup-success';div.innerHTML='<i class="bi bi-check-circle me-2"></i><strong>'+data.book_number+'</strong> — '+data.title+' by '+data.author+' &nbsp;<span class="badge-status badge-available" style="font-size:11px;">Available</span>';document.getElementById('bookIdHidden').value=data.id;}
        });
}
function selectBook(sel){
    var opt=sel.options[sel.selectedIndex]; if(!opt.value)return;
    var div=document.getElementById('bookResult');div.style.display='flex';div.className='book-lookup-result lookup-success';
    div.innerHTML='<i class="bi bi-check-circle me-2"></i><strong>'+opt.getAttribute('data-num')+'</strong> — '+opt.getAttribute('data-title')+' by '+opt.getAttribute('data-author')+' &nbsp;<span class="badge-status badge-available" style="font-size:11px;">Available</span>';
    document.getElementById('bookIdHidden').value=opt.value;
}
document.getElementById('bookLookup').addEventListener('keydown',function(e){if(e.key==='Enter'){e.preventDefault();lookupBook();}});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
