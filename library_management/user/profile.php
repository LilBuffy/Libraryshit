<?php
// user/profile.php — User manages their own profile
define('BASE_URL', '/library_management');
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireUserLogin();

$pdo       = getDB();
$pageTitle = 'My Profile';
$userId    = $_SESSION['user_id'];
$errors    = [];
$success   = '';

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_info') {
        $full_name = trim($_POST['full_name'] ?? '');
        $email     = trim($_POST['email']     ?? '');
        $phone     = trim($_POST['phone']     ?? '');
        $address   = trim($_POST['address']   ?? '');

        if (empty($full_name)) $errors[] = 'Full name is required.';
        if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email.';

        if ($email) {
            $chk = $pdo->prepare("SELECT id FROM users WHERE email=? AND id != ?");
            $chk->execute([$email, $userId]);
            if ($chk->fetchColumn()) $errors[] = 'That email is already used by another account.';
        }

        if (empty($errors)) {
            $pdo->prepare("UPDATE users SET full_name=?, email=?, phone=?, address=? WHERE id=?")
                ->execute([$full_name, $email ?: null, $phone ?: null, $address ?: null, $userId]);
            $_SESSION['user_name'] = $full_name;
            flash('success', 'Profile updated successfully.');
            header('Location: ' . BASE_URL . '/user/profile.php');
            exit();
        }
    }

    if ($action === 'change_password') {
        $current  = $_POST['current_password']  ?? '';
        $newPass  = $_POST['new_password']      ?? '';
        $confirm  = $_POST['confirm_password']  ?? '';

        if (!password_verify($current, $user['password'])) $errors[] = 'Current password is incorrect.';
        if (strlen($newPass) < 6) $errors[] = 'New password must be at least 6 characters.';
        if ($newPass !== $confirm) $errors[] = 'Passwords do not match.';

        if (empty($errors)) {
            $hash = password_hash($newPass, PASSWORD_BCRYPT);
            $pdo->prepare("UPDATE users SET password=? WHERE id=?")->execute([$hash, $userId]);
            flash('success', 'Password changed successfully.');
            header('Location: ' . BASE_URL . '/user/profile.php');
            exit();
        }
    }
}

require_once __DIR__ . '/header.php';
?>

<div class="row">
    <div class="col-lg-8 mb-4">
        <div class="section-card">
            <div class="section-header">
                <h3><i class="bi bi-person-gear me-2"></i>My Profile</h3>
                <span class="isbn-code"><?= htmlspecialchars($user['member_id']) ?></span>
            </div>

            <?php if ($errors): ?>
            <div class="alert-error-box" style="margin:16px 20px 0;">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <ul class="mb-0 ps-3"><?php foreach($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
            </div>
            <?php endif; ?>

            <form method="POST" class="form-custom">
                <input type="hidden" name="action" value="update_info">
                <div class="form-row-2">
                    <div class="form-group">
                        <label class="form-lbl">Full Name <span class="req">*</span></label>
                        <input type="text" name="full_name" class="form-ctrl" required
                               value="<?= htmlspecialchars($user['full_name']) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-lbl">Username</label>
                        <input type="text" class="form-ctrl" readonly
                               value="<?= htmlspecialchars($user['username']) ?>"
                               style="background:#f5f5f5;color:#888;">
                        <span class="form-hint">Username cannot be changed.</span>
                    </div>
                </div>
                <div class="form-row-2">
                    <div class="form-group">
                        <label class="form-lbl">Email Address</label>
                        <input type="email" name="email" class="form-ctrl"
                               value="<?= htmlspecialchars($user['email'] ?? '') ?>"
                               placeholder="email@example.com">
                    </div>
                    <div class="form-group">
                        <label class="form-lbl">Phone Number</label>
                        <input type="text" name="phone" class="form-ctrl"
                               value="<?= htmlspecialchars($user['phone'] ?? '') ?>"
                               placeholder="09XXXXXXXXX">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-lbl">Address</label>
                    <textarea name="address" class="form-ctrl" rows="2"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-primary-custom"><i class="bi bi-check-lg me-1"></i>Update Profile</button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-lg-4 mb-4">
        <!-- Account Info Card -->
        <div class="section-card" style="margin-bottom:16px;">
            <div class="section-header"><h3><i class="bi bi-info-circle me-2"></i>Account Info</h3></div>
            <div style="padding:16px 20px;font-size:13px;line-height:2;">
                <strong>Member ID:</strong> <?= htmlspecialchars($user['member_id']) ?><br>
                <strong>Type:</strong> <?= ucfirst($user['membership_type']) ?><br>
                <strong>Status:</strong>
                <span class="badge-status badge-available" style="font-size:11px;"><?= ucfirst($user['status']) ?></span><br>
                <strong>Joined:</strong> <?= date('F j, Y', strtotime($user['joined_date'])) ?>
            </div>
        </div>

        <!-- Change Password -->
        <div class="section-card">
            <div class="section-header"><h3><i class="bi bi-key me-2"></i>Change Password</h3></div>
            <form method="POST" class="form-custom">
                <input type="hidden" name="action" value="change_password">
                <div class="form-group">
                    <label class="form-lbl">Current Password</label>
                    <input type="password" name="current_password" class="form-ctrl" required placeholder="Current password">
                </div>
                <div class="form-group">
                    <label class="form-lbl">New Password</label>
                    <input type="password" name="new_password" class="form-ctrl" required placeholder="Min. 6 characters">
                </div>
                <div class="form-group">
                    <label class="form-lbl">Confirm New Password</label>
                    <input type="password" name="confirm_password" class="form-ctrl" required placeholder="Repeat new password">
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-primary-custom" style="font-size:13px;padding:8px 16px;">
                        <i class="bi bi-shield-lock me-1"></i>Change Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
