<?php
// transactions/return.php — Admin return (FIXED v6)
define('BASE_URL', '/library_management');
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$pdo = getDB();
$pageTitle = 'Return Book';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $txId       = intval($_POST['transaction_id'] ?? 0);
    $returnDate = $_POST['return_date'] ?? date('Y-m-d');
    $finePaid   = isset($_POST['fine_paid']) ? 1 : 0;

    if (!$txId) $errors[] = 'Please select a transaction.';

    $txRow = null;
    if ($txId) {
        // LEFT JOIN so transactions with null user_id still load
        $stmt = $pdo->prepare("
            SELECT t.*,
                   COALESCE(u.full_name, 'Walk-in Borrower') AS full_name,
                   COALESCE(u.member_id, '—') AS mid,
                   b.title AS book_title,
                   b.book_number
            FROM transactions t
            LEFT JOIN users u ON t.user_id = u.id
            JOIN books b ON t.book_id = b.id
            WHERE t.id = ? AND t.status = 'borrowed'
        ");
        $stmt->execute([$txId]);
        $txRow = $stmt->fetch();
        if (!$txRow) $errors[] = 'Transaction not found or book already returned.';
    }

    if (empty($errors) && $txRow) {
        $fine = 0;
        $due  = strtotime($txRow['due_date']);
        $ret  = strtotime($returnDate);
        if ($ret > $due) {
            $fine = ceil(($ret - $due) / 86400) * FINE_PER_DAY;
        }

        $pdo->beginTransaction();
        try {
            $pdo->prepare("
                UPDATE transactions
                SET return_date = ?, status = 'returned', fine_amount = ?, fine_paid = ?, updated_at = NOW()
                WHERE id = ?
            ")->execute([$returnDate, $fine, $finePaid, $txId]);

            $pdo->prepare("
                UPDATE books SET status = 'Available', updated_at = NOW()
                WHERE id = ?
            ")->execute([$txRow['book_id']]);

            $pdo->commit();

            logActivity(
                'Book Returned',
                $txRow['full_name'] . ' returned "' . $txRow['book_title'] . '" (' . $txRow['book_number'] . ')'
                . ($fine > 0 ? " | Fine: ₱{$fine}" : '')
            );

            flash('success',
                '"' . $txRow['book_title'] . '" returned successfully. Status → Available.'
                . ($fine > 0 ? " Fine: ₱{$fine}" : '')
            );

            header('Location: ' . BASE_URL . '/transactions/return.php');
            exit();

        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Return failed: ' . $e->getMessage() . '. Please try again.';
        }
    }
}

// Load ALL active borrows — LEFT JOIN so no transactions are excluded
$borrowed = $pdo->query("
    SELECT
        t.id,
        t.borrow_date,
        t.due_date,
        t.book_number,
        COALESCE(u.full_name, 'Walk-in Borrower') AS full_name,
        COALESCE(u.member_id, '—') AS mid,
        b.title AS book_title,
        DATEDIFF(CURDATE(), t.due_date) AS days_overdue
    FROM transactions t
    LEFT JOIN users u ON t.user_id = u.id
    JOIN books b ON t.book_id = b.id
    WHERE t.status = 'borrowed'
    ORDER BY t.due_date ASC
")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="section-card">
    <div class="section-header">
        <h3><i class="bi bi-box-arrow-in-left me-2"></i>Return Book</h3>
        <a href="<?= BASE_URL ?>/transactions/history.php" class="btn-back">
            <i class="bi bi-clock-history me-1"></i>History
        </a>
    </div>

    <?php if ($errors): ?>
    <div class="alert-error-box" style="margin:16px 20px 0;">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <ul class="mb-0 ps-3">
            <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <form method="POST" class="form-custom">
        <div class="form-row-2">
            <div class="form-group">
                <label class="form-lbl">Select Borrowed Transaction <span class="req">*</span></label>
                <?php if (empty($borrowed)): ?>
                <p style="padding:10px 0;color:#888;font-size:13px;">
                    <i class="bi bi-info-circle me-1"></i>No active borrows found.
                </p>
                <?php else: ?>
                <select name="transaction_id" class="form-ctrl" required id="txSelect">
                    <option value="">-- Select a transaction --</option>
                    <?php foreach ($borrowed as $b): ?>
                    <option value="<?= $b['id'] ?>"
                            data-overdue="<?= max(0, (int)$b['days_overdue']) ?>"
                            data-fine="<?= number_format(max(0, (int)$b['days_overdue']) * FINE_PER_DAY, 2) ?>">
                        <?= htmlspecialchars(
                            $b['book_number'] . ' — ' . $b['book_title']
                            . ' | ' . $b['full_name'] . ' (' . $b['mid'] . ')'
                            . ' | Due: ' . date('M j, Y', strtotime($b['due_date']))
                            . ((int)$b['days_overdue'] > 0 ? ' [OVERDUE ' . (int)$b['days_overdue'] . 'd]' : '')
                        ) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label class="form-lbl">Return Date</label>
                <input type="date" name="return_date" class="form-ctrl"
                       value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>">
            </div>
        </div>

        <div id="fineInfo" class="fine-info-box" style="display:none;">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <span id="fineText"></span>
        </div>

        <div class="form-group" id="finePaidGroup" style="display:none;">
            <label class="form-lbl-check">
                <input type="checkbox" name="fine_paid">
                <span>Fine has been collected / paid by borrower</span>
            </label>
        </div>

        <?php if (!empty($borrowed)): ?>
        <div class="form-actions">
            <button type="submit" class="btn-primary-custom">
                <i class="bi bi-check-lg me-1"></i>Process Return
            </button>
        </div>
        <?php endif; ?>
    </form>
</div>

<!-- Active Borrows Table -->
<div class="section-card mt-4">
    <div class="section-header">
        <h3><i class="bi bi-list-ul me-2"></i>All Active Borrows</h3>
        <span style="font-size:12px;color:#999;"><?= count($borrowed) ?> record(s)</span>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Book Number</th>
                    <th>Title</th>
                    <th>Borrower</th>
                    <th>Borrow Date</th>
                    <th>Due Date</th>
                    <th>Overdue</th>
                    <th>Est. Fine</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($borrowed)): ?>
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">
                        <i class="bi bi-check-circle me-2" style="color:#16a34a;"></i>
                        No active borrows at this time.
                    </td>
                </tr>
                <?php else: foreach ($borrowed as $i => $b):
                    $daysOv = max(0, (int)$b['days_overdue']);
                ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><span class="book-num"><?= htmlspecialchars($b['book_number']) ?></span></td>
                    <td><?= htmlspecialchars($b['book_title']) ?></td>
                    <td>
                        <?= htmlspecialchars($b['full_name']) ?>
                        <br><small class="text-muted"><?= htmlspecialchars($b['mid']) ?></small>
                    </td>
                    <td><?= date('M j, Y', strtotime($b['borrow_date'])) ?></td>
                    <td><?= date('M j, Y', strtotime($b['due_date'])) ?></td>
                    <td>
                        <?php if ($daysOv > 0): ?>
                        <span class="badge-status badge-overdue"><?= $daysOv ?> day(s)</span>
                        <?php else: ?>
                        <span class="text-muted">On time</span>
                        <?php endif; ?>
                    </td>
                    <td>₱<?= number_format($daysOv * FINE_PER_DAY, 2) ?></td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
(function () {
    var sel = document.getElementById('txSelect');
    if (!sel) return;
    sel.addEventListener('change', function () {
        var opt  = this.options[this.selectedIndex];
        var ov   = parseInt(opt.getAttribute('data-overdue')) || 0;
        var fine = parseFloat(opt.getAttribute('data-fine'))  || 0;
        var box  = document.getElementById('fineInfo');
        var txt  = document.getElementById('fineText');
        var grp  = document.getElementById('finePaidGroup');
        if (ov > 0) {
            txt.textContent = 'Overdue by ' + ov + ' day(s). Fine: ₱' + fine.toFixed(2)
                            + ' (₱<?= FINE_PER_DAY ?>/day)';
            box.style.display = 'flex';
            grp.style.display = 'block';
        } else {
            box.style.display = 'none';
            grp.style.display = 'none';
        }
    });
}());
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
