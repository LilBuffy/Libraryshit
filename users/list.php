<?php
// users/list.php — Admin manages registered users
define('BASE_URL', '/library_management');
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$pdo       = getDB();
$pageTitle = 'Manage Users';

$search  = trim($_GET['search'] ?? '');
$type    = $_GET['type']   ?? '';
$status  = $_GET['status'] ?? '';
$page    = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$offset  = ($page - 1) * $perPage;

$where  = [];
$params = [];
if ($search !== '') {
    $where[] = "(u.full_name LIKE ? OR u.username LIKE ? OR u.member_id LIKE ? OR u.email LIKE ?)";
    $s = "%$search%"; $params[] = $s; $params[] = $s; $params[] = $s; $params[] = $s;
}
if ($type   !== '') { $where[] = "u.membership_type = ?"; $params[] = $type; }
if ($status !== '') { $where[] = "u.status = ?";          $params[] = $status; }

$whereSQL   = $where ? 'WHERE '.implode(' AND ',$where) : '';
$countStmt  = $pdo->prepare("SELECT COUNT(*) FROM users u $whereSQL");
$countStmt->execute($params);
$total      = $countStmt->fetchColumn();
$totalPages = max(1, ceil($total / $perPage));

$stmt = $pdo->prepare("
    SELECT u.*,
        (SELECT COUNT(*) FROM transactions t WHERE t.user_id=u.id AND t.status='borrowed') AS active_borrows
    FROM users u $whereSQL
    ORDER BY u.created_at DESC
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$users = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="section-card">
    <div class="section-header">
        <h3><i class="bi bi-people me-2"></i>Registered Users</h3>
    </div>

    <form method="GET" class="filter-bar">
        <div class="filter-row">
            <div class="search-wrap">
                <i class="bi bi-search search-icon"></i>
                <input type="text" name="search" class="filter-input search-input"
                       placeholder="Search name, username, member ID, email..."
                       value="<?= htmlspecialchars($search) ?>">
            </div>
            <select name="type" class="filter-input filter-select">
                <option value="">All Types</option>
                <option value="student"  <?= $type==='student'?'selected':'' ?>>Student</option>
                <option value="faculty"  <?= $type==='faculty'?'selected':'' ?>>Faculty</option>
                <option value="staff"    <?= $type==='staff'?'selected':'' ?>>Staff</option>
                <option value="public"   <?= $type==='public'?'selected':'' ?>>Public</option>
            </select>
            <select name="status" class="filter-input filter-select">
                <option value="">All Status</option>
                <option value="active"    <?= $status==='active'?'selected':'' ?>>Active</option>
                <option value="inactive"  <?= $status==='inactive'?'selected':'' ?>>Inactive</option>
                <option value="suspended" <?= $status==='suspended'?'selected':'' ?>>Suspended</option>
            </select>
            <button type="submit" class="btn-filter"><i class="bi bi-search me-1"></i>Search</button>
            <?php if ($search||$type||$status): ?><a href="<?= BASE_URL ?>/users/list.php" class="btn-filter-reset">Clear</a><?php endif; ?>
        </div>
    </form>

    <div class="table-info">Showing <strong><?= count($users) ?></strong> of <strong><?= $total ?></strong> users</div>

    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th><th>Member ID</th><th>Username</th><th>Full Name</th>
                    <th>Email</th><th>Type</th><th>Status</th><th>Borrows</th><th>Joined</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                <tr><td colspan="10" class="text-center text-muted py-5">No users found.</td></tr>
                <?php else: foreach ($users as $i => $u): ?>
                <tr>
                    <td><?= $offset+$i+1 ?></td>
                    <td><span class="isbn-code"><?= htmlspecialchars($u['member_id']) ?></span></td>
                    <td><?= htmlspecialchars($u['username']) ?></td>
                    <td><strong><?= htmlspecialchars($u['full_name']) ?></strong></td>
                    <td><?= htmlspecialchars($u['email'] ?? '—') ?></td>
                    <td><span class="type-badge"><?= ucfirst($u['membership_type']) ?></span></td>
                    <td>
                        <?php $sc=['active'=>'badge-available','inactive'=>'badge-borrowed','suspended'=>'badge-overdue']; ?>
                        <span class="badge-status <?= $sc[$u['status']]??'' ?>"><?= ucfirst($u['status']) ?></span>
                    </td>
                    <td class="text-center">
                        <?php if ($u['active_borrows']>0): ?>
                        <span class="badge-status badge-borrowed"><?= $u['active_borrows'] ?></span>
                        <?php else: ?><span class="text-muted">0</span><?php endif; ?>
                    </td>
                    <td><?= date('M j, Y', strtotime($u['joined_date'])) ?></td>
                    <td>
                        <div class="action-btns">
                            <a href="<?= BASE_URL ?>/users/edit.php?id=<?= $u['id'] ?>" class="btn-action btn-edit" title="Edit"><i class="bi bi-pencil-fill"></i></a>
                            <a href="<?= BASE_URL ?>/users/delete.php?id=<?= $u['id'] ?>" class="btn-action btn-delete" title="Delete"
                               onclick="return confirm('Delete this user account? This cannot be undone.')"><i class="bi bi-trash-fill"></i></a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages>1): ?>
    <nav class="pagination-wrap">
        <ul class="custom-pagination">
            <li class="<?= $page<=1?'disabled':'' ?>">
                <a href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>&type=<?= $type ?>&status=<?= $status ?>"><i class="bi bi-chevron-left"></i></a>
            </li>
            <?php for ($i=1;$i<=$totalPages;$i++): ?>
            <li class="<?= $i===$page?'active':'' ?>">
                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&type=<?= $type ?>&status=<?= $status ?>"><?= $i ?></a>
            </li>
            <?php endfor; ?>
            <li class="<?= $page>=$totalPages?'disabled':'' ?>">
                <a href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>&type=<?= $type ?>&status=<?= $status ?>"><i class="bi bi-chevron-right"></i></a>
            </li>
        </ul>
    </nav>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
