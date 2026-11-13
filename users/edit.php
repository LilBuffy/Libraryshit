<?php
// users/edit.php — Admin edits a user account
define('BASE_URL', '/library_management');
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$pdo       = getDB();
$pageTitle = 'Edit User';
$errors    = [];

$id   = intval($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    flash('error', 'User not found.');
    header('Location: ' . BASE_URL . '/users/list.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'full_name'       => trim($_POST['full_name']       ?? ''),
        'email'           => trim($_POST['email']           ?? ''),
        'phone'           => trim($_POST['phone']           ?? ''),
        'address'         => trim($_POST['address']         ?? ''),
        'membership_type' => $_POST['membership_type']      ?? 'student',
        'status'          => $_POST['status']               ?? 'active',
    ];
    $newPass = trim($_POST['new_password'] ?? '');

    if (empty($data['full_name'])) $errors[] = 'Full name is required.';
    if ($data['email'] && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email.';

    if ($data['email']) {
        $chk = $pdo->prepare("SELECT id FROM users WHERE email=? AND id!=?");
        $chk->execute([$data['email'], $id]);
        if ($chk->fetchColumn()) $errors[] = 'Email already used by another user.';
    }

    if ($newPass && strlen($newPass) < 6) $errors[] = 'New password must be at least 6 characters.';

    if (empty($errors)) {
        if ($newPass) {
            $pdo->prepare("UPDATE users SET full_name=?,email=?,phone=?,address=?,membership_type=?,status=?,password=? WHERE id=?")
                ->execute([$data['full_name'],$data['email']?:null,$data['phone']?:null,$data['address']?:null,$data['membership_type'],$data['status'],password_hash($newPass,PASSWORD_BCRYPT),$id]);
        } else {
            $pdo->prepare("UPDATE users SET full_name=?,email=?,phone=?,address=?,membership_type=?,status=? WHERE id=?")
                ->execute([$data['full_name'],$data['email']?:null,$data['phone']?:null,$data['address']?:null,$data['membership_type'],$data['status'],$id]);
        }
        logActivity('User Updated', 'Admin updated user: '.$user['full_name'].' ('.$user['username'].')');
        flash('success', 'User account updated.');
        header('Location: ' . BASE_URL . '/users/list.php');
        exit();
    }
    $user = array_merge($user, $data);
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="section-card">
    <div class="section-header">
        <h3><i class="bi bi-pencil-square me-2"></i>Edit User Account</h3>
        <a href="<?= BASE_URL ?>/users/list.php" class="btn-back"><i class="bi bi-arrow-left me-1"></i>Back to Users</a>
    </div>

    <div class="book-status-bar">
        <i class="bi bi-info-circle me-2"></i>
        Username: <strong style="margin-left:4px;"><?= htmlspecialchars($user['username']) ?></strong>
        &nbsp;|&nbsp; Member ID: <strong><?= htmlspecialchars($user['member_id']) ?></strong>
    </div>

    <?php if ($errors): ?>
    <div class="alert-error-box" style="margin:16px 20px 0;">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <ul class="mb-0 ps-3"><?php foreach($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
    </div>
    <?php endif; ?>

    <form method="POST" class="form-custom">
        <div class="form-row-2">
            <div class="form-group">
                <label class="form-lbl">Full Name <span class="req">*</span></label>
                <input type="text" name="full_name" class="form-ctrl" required value="<?= htmlspecialchars($user['full_name']) ?>">
            </div>
            <div class="form-group">
                <label class="form-lbl">Email Address</label>
                <input type="email" name="email" class="form-ctrl" value="<?= htmlspecialchars($user['email']??'') ?>">
            </div>
        </div>
        <div class="form-row-2">
            <div class="form-group">
                <label class="form-lbl">Phone</label>
                <input type="text" name="phone" class="form-ctrl" value="<?= htmlspecialchars($user['phone']??'') ?>">
            </div>
            <div class="form-group">
                <label class="form-lbl">New Password <span class="form-hint" style="display:inline;">(leave blank to keep current)</span></label>
                <input type="password" name="new_password" class="form-ctrl" placeholder="Enter new password or leave blank">
            </div>
        </div>
        <div class="form-row-3">
            <div class="form-group">
                <label class="form-lbl">Membership Type</label>
                <select name="membership_type" class="form-ctrl">
                    <?php foreach(['student','faculty','staff','public'] as $t): ?>
                    <option value="<?= $t ?>" <?= $user['membership_type']===$t?'selected':'' ?>><?= ucfirst($t) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-lbl">Account Status</label>
                <select name="status" class="form-ctrl">
                    <?php foreach(['active','inactive','suspended'] as $s): ?>
                    <option value="<?= $s ?>" <?= $user['status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-lbl">Address</label>
                <input type="text" name="address" class="form-ctrl" value="<?= htmlspecialchars($user['address']??'') ?>">
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn-primary-custom"><i class="bi bi-check-lg me-1"></i>Update User</button>
            <a href="<?= BASE_URL ?>/users/list.php" class="btn-cancel">Cancel</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
