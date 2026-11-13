<?php
// books/edit.php
define('BASE_URL', '/library_management');
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$pdo = getDB();
$pageTitle = 'Edit Book';
$errors = [];

$id   = intval($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM books WHERE id = ?");
$stmt->execute([$id]);
$book = $stmt->fetch();

if (!$book) {
    flash('error', 'Book record not found.');
    header('Location: ' . BASE_URL . '/books/list.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'book_number'    => strtoupper(trim($_POST['book_number'] ?? '')),
        'title'          => trim($_POST['title'] ?? ''),
        'copyright_year' => trim($_POST['copyright_year'] ?? ''),
        'edition'        => trim($_POST['edition'] ?? ''),
        'author'         => trim($_POST['author'] ?? ''),
        'course_code'    => trim($_POST['course_code'] ?? ''),
    ];

    if (empty($data['book_number'])) $errors[] = 'Book Number is required.';
    if (empty($data['title']))       $errors[] = 'Title is required.';
    if (empty($data['author']))      $errors[] = 'Author is required.';

    if (!empty($data['book_number'])) {
        $chk = $pdo->prepare("SELECT id FROM books WHERE book_number = ? AND id != ?");
        $chk->execute([$data['book_number'], $id]);
        if ($chk->fetchColumn()) $errors[] = 'Book Number "' . $data['book_number'] . '" is already used by another record.';
    }

    // Cannot edit status if book is currently borrowed
    if ($book['status'] === 'Borrowed') {
        $chkTx = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE book_id = ? AND status = 'borrowed'");
        $chkTx->execute([$id]);
        if ($chkTx->fetchColumn() > 0) {
            $errors[] = 'This book is currently Borrowed and cannot be fully edited until returned.';
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("
            UPDATE books SET book_number=?, title=?, copyright_year=?, edition=?, author=?, course_code=?
            WHERE id=?
        ");
        $stmt->execute([
            $data['book_number'],
            $data['title'],
            $data['copyright_year'] ?: null,
            $data['edition'] ?: null,
            $data['author'],
            $data['course_code'] ?: null,
            $id,
        ]);
        logActivity('Book Updated', 'Updated: ' . $data['title'] . ' (' . $data['book_number'] . ')');
        flash('success', 'Book record updated successfully.');
        header('Location: ' . BASE_URL . '/books/list.php');
        exit();
    }
    $book = array_merge($book, $data);
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="section-card">
    <div class="section-header">
        <h3><i class="bi bi-pencil-square me-2"></i>Edit Book Record</h3>
        <a href="<?= BASE_URL ?>/books/list.php" class="btn-back">
            <i class="bi bi-arrow-left me-1"></i>Back to Records
        </a>
    </div>

    <!-- Status bar -->
    <div class="book-status-bar">
        <i class="bi bi-info-circle me-2"></i>
        Current Status:&nbsp;
        <?php if ($book['status'] === 'Available'): ?>
        <span class="badge-status badge-available" style="font-size:12px;">Available</span>
        <?php else: ?>
        <span class="badge-status badge-borrowed" style="font-size:12px;">Borrowed</span>
        &nbsp;<small style="color:#b35900;">(Return the book first before editing)</small>
        <?php endif; ?>
        &nbsp;&nbsp;Book Number: <strong style="margin-left:4px;"><?= htmlspecialchars($book['book_number']) ?></strong>
    </div>

    <?php if ($errors): ?>
    <div class="alert-error-box" style="margin:16px 20px 0;">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <ul class="mb-0 ps-3"><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
    </div>
    <?php endif; ?>

    <form method="POST" class="form-custom">
        <div class="form-row-2">
            <div class="form-group">
                <label class="form-lbl">Book Number (Accession No.) <span class="req">*</span></label>
                <input type="text" name="book_number" class="form-ctrl" required
                       value="<?= htmlspecialchars($book['book_number']) ?>"
                       style="font-weight:bold;letter-spacing:.5px;">
            </div>
            <div class="form-group">
                <label class="form-lbl">Title <span class="req">*</span></label>
                <input type="text" name="title" class="form-ctrl" required
                       value="<?= htmlspecialchars($book['title']) ?>">
            </div>
        </div>

        <div class="form-group">
            <label class="form-lbl">Author <span class="req">*</span></label>
            <input type="text" name="author" class="form-ctrl" required
                   value="<?= htmlspecialchars($book['author']) ?>"
                   placeholder="e.g. Glenn, Paul J.">
        </div>

        <div class="form-row-3">
            <div class="form-group">
                <label class="form-lbl">Copyright Year</label>
                <input type="text" name="copyright_year" class="form-ctrl"
                       value="<?= htmlspecialchars($book['copyright_year'] ?? '') ?>"
                       placeholder="e.g. 2025">
            </div>
            <div class="form-group">
                <label class="form-lbl">Edition</label>
                <input type="text" name="edition" class="form-ctrl"
                       value="<?= htmlspecialchars($book['edition'] ?? '') ?>"
                       placeholder="e.g. 1Ed or -">
            </div>
            <div class="form-group">
                <label class="form-lbl">Course Code</label>
                <input type="text" name="course_code" class="form-ctrl"
                       value="<?= htmlspecialchars($book['course_code'] ?? '') ?>"
                       placeholder="e.g. CP CRIM1 or -">
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-primary-custom"
                    <?= $book['status'] === 'Borrowed' ? 'disabled style="opacity:.5;cursor:not-allowed;"' : '' ?>>
                <i class="bi bi-check-lg me-1"></i>Update Book
            </button>
            <a href="<?= BASE_URL ?>/books/list.php" class="btn-cancel">Cancel</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
