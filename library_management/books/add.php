<?php
// books/add.php
define('BASE_URL', '/library_management');
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$pdo = getDB();
$pageTitle = 'Add Book';
$errors = [];
$data   = [];

// Auto-generate next Book Number (SA####)
function nextBookNumber($pdo) {
    $last = $pdo->query("SELECT book_number FROM books WHERE book_number LIKE 'SA%' ORDER BY id DESC LIMIT 1")->fetchColumn();
    $num  = 1;
    if ($last && preg_match('/SA(\d+)/i', $last, $m)) $num = intval($m[1]) + 1;
    return 'SA' . str_pad($num, 4, '0', STR_PAD_LEFT);
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
        $chk = $pdo->prepare("SELECT id FROM books WHERE book_number = ?");
        $chk->execute([$data['book_number']]);
        if ($chk->fetchColumn()) $errors[] = 'Book Number "' . $data['book_number'] . '" already exists.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("
            INSERT INTO books (book_number, title, copyright_year, edition, author, course_code, status)
            VALUES (?, ?, ?, ?, ?, ?, 'Available')
        ");
        $stmt->execute([
            $data['book_number'],
            $data['title'],
            $data['copyright_year'] ?: null,
            $data['edition'] ?: null,
            $data['author'],
            $data['course_code'] ?: null,
        ]);
        logActivity('Book Added', 'Added: ' . $data['title'] . ' (' . $data['book_number'] . ')');
        flash('success', 'Book "' . $data['title'] . '" added. Book Number: ' . $data['book_number']);
        header('Location: ' . BASE_URL . '/books/list.php');
        exit();
    }
} else {
    $data['book_number'] = nextBookNumber($pdo);
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="section-card">
    <div class="section-header">
        <h3><i class="bi bi-plus-circle me-2"></i>Add New Book</h3>
        <a href="<?= BASE_URL ?>/books/list.php" class="btn-back">
            <i class="bi bi-arrow-left me-1"></i>Back to Records
        </a>
    </div>

    <?php if ($errors): ?>
    <div class="alert-error-box">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <ul class="mb-0 ps-3"><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
    </div>
    <?php endif; ?>

    <form method="POST" class="form-custom">
        <div class="form-row-2">
            <div class="form-group">
                <label class="form-lbl">Book Number (Accession No.) <span class="req">*</span></label>
                <input type="text" name="book_number" class="form-ctrl" required
                       value="<?= htmlspecialchars($data['book_number'] ?? '') ?>"
                       placeholder="e.g. SA0001" style="font-weight:bold;letter-spacing:.5px;">
                <span class="form-hint">Auto-generated. Format: SA0001, SA0002, SA0003...</span>
            </div>
            <div class="form-group">
                <label class="form-lbl">Title <span class="req">*</span></label>
                <input type="text" name="title" class="form-ctrl" required
                       value="<?= htmlspecialchars($data['title'] ?? '') ?>"
                       placeholder="Enter book title">
            </div>
        </div>

        <div class="form-group">
            <label class="form-lbl">Author <span class="req">*</span></label>
            <input type="text" name="author" class="form-ctrl" required
                   value="<?= htmlspecialchars($data['author'] ?? '') ?>"
                   placeholder="e.g. Glenn, Paul J. or Catalino, Marcelo G.">
        </div>

        <div class="form-row-3">
            <div class="form-group">
                <label class="form-lbl">Copyright Year</label>
                <input type="text" name="copyright_year" class="form-ctrl"
                       value="<?= htmlspecialchars($data['copyright_year'] ?? '') ?>"
                       placeholder="e.g. 2025 or leave blank">
            </div>
            <div class="form-group">
                <label class="form-lbl">Edition</label>
                <input type="text" name="edition" class="form-ctrl"
                       value="<?= htmlspecialchars($data['edition'] ?? '') ?>"
                       placeholder="e.g. 1Ed, 2nd Ed, or -">
            </div>
            <div class="form-group">
                <label class="form-lbl">Course Code</label>
                <input type="text" name="course_code" class="form-ctrl"
                       value="<?= htmlspecialchars($data['course_code'] ?? '') ?>"
                       placeholder="e.g. CP CRIM1 or -">
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-primary-custom">
                <i class="bi bi-check-lg me-1"></i>Save Book
            </button>
            <a href="<?= BASE_URL ?>/books/list.php" class="btn-cancel">Cancel</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
