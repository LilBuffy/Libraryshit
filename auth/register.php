<?php
define('BASE_URL', '/library_management');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (isUserLoggedIn()) { header('Location: '.BASE_URL.'/user/dashboard.php'); exit(); }
if (isLoggedIn())     { header('Location: '.BASE_URL.'/dashboard.php'); exit(); }

$pdo    = getDB();
$errors = [];
$data   = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'full_name'       => trim($_POST['full_name']       ?? ''),
        'username'        => strtolower(trim($_POST['username'] ?? '')),
        'email'           => trim($_POST['email']           ?? ''),
        'phone'           => trim($_POST['phone']           ?? ''),
        'password'        => $_POST['password']             ?? '',
        'confirm'         => $_POST['confirm_password']     ?? '',
        'membership_type' => $_POST['membership_type']      ?? 'student',
        'address'         => trim($_POST['address']         ?? ''),
    ];

    if (empty($data['full_name'])) $errors[] = 'Full name is required.';
    if (empty($data['username']))  $errors[] = 'Username is required.';
    if (strlen($data['username']) < 4) $errors[] = 'Username must be at least 4 characters.';
    if (!preg_match('/^[a-z0-9_]+$/', $data['username'])) $errors[] = 'Username: letters, numbers, underscores only.';
    if (empty($data['password']))  $errors[] = 'Password is required.';
    if (strlen($data['password']) < 6) $errors[] = 'Password must be at least 6 characters.';
    if ($data['password'] !== $data['confirm']) $errors[] = 'Passwords do not match.';
    if ($data['email'] && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email address.';

    if (empty($errors)) {
        $chk = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $chk->execute([$data['username']]);
        if ($chk->fetchColumn()) $errors[] = 'Username "'.$data['username'].'" is already taken.';
        if ($data['email']) {
            $chk2 = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $chk2->execute([$data['email']]);
            if ($chk2->fetchColumn()) $errors[] = 'That email is already registered.';
        }
    }

    if (empty($errors)) {
        $memberId = nextMemberId($pdo);
        $stmt = $pdo->prepare("INSERT INTO users (member_id,username,password,full_name,email,phone,address,membership_type,status,joined_date) VALUES (?,?,?,?,?,?,?,'".($data['membership_type'])."','active',CURDATE())");
        $stmt->execute([$memberId,$data['username'],password_hash($data['password'],PASSWORD_BCRYPT),$data['full_name'],$data['email']?:null,$data['phone']?:null,$data['address']?:null]);
        logActivity('User Registered','New account: '.$data['full_name'].' ('.$memberId.')',$data['username']);
        flash('success','Account created! You can now sign in.');
        header('Location: '.BASE_URL.'/auth/login.php'); exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Create Account | Library Management System</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Inter',Arial,sans-serif;background:#f1f1f1;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px;}
@keyframes fadeInUp{from{opacity:0;transform:translateY(20px);}to{opacity:1;transform:translateY(0);}}
.shell{width:600px;max-width:100%;background:#fff;border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,.09);border:1px solid #e8e8e8;overflow:hidden;animation:fadeInUp .4s ease both;}
.reg-head{background:#111;color:#fff;padding:28px 36px;display:flex;align-items:center;gap:16px;}
.reg-head-icon{width:40px;height:40px;background:#fff;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:20px;color:#111;flex-shrink:0;}
.reg-head h1{font-size:17px;font-weight:700;margin:0 0 2px;}
.reg-head p{font-size:11.5px;color:rgba(255,255,255,.4);margin:0;}
.reg-body{padding:30px 36px;}
.form-row-2{display:grid;grid-template-columns:1fr 1fr;gap:16px;}
.form-group{margin-bottom:16px;}
.frm-lbl{display:block;font-size:11px;font-weight:600;color:#888;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px;}
.req{color:#dc2626;}
.frm-input{width:100%;padding:10px 14px;border:1.5px solid #e5e5e5;border-radius:8px;font-size:13.5px;font-family:'Inter',Arial,sans-serif;color:#111;outline:none;transition:all .2s;background:#fafafa;}
.frm-input:focus{border-color:#111;background:#fff;box-shadow:0 0 0 3px rgba(0,0,0,.05);}
.frm-input::placeholder{color:#ccc;}
.frm-hint{font-size:11px;color:#bbb;margin-top:3px;display:block;}
.btn-reg{width:100%;padding:12px;background:#111;color:#fff;border:none;border-radius:8px;font-size:14px;font-family:'Inter',Arial,sans-serif;font-weight:600;cursor:pointer;transition:all .2s;margin-top:4px;display:flex;align-items:center;justify-content:center;gap:8px;}
.btn-reg:hover{background:#333;transform:translateY(-1px);box-shadow:0 8px 20px rgba(0,0,0,.1);}
.err-box{background:#fef2f2;border:1px solid #fecaca;border-left:3px solid #dc2626;color:#dc2626;padding:12px 14px;border-radius:8px;font-size:12.5px;margin-bottom:20px;}
.err-box ul{margin:6px 0 0 18px;padding:0;}
.bot-links{text-align:center;margin-top:18px;font-size:12.5px;color:#bbb;}
.bot-links a{color:#555;font-weight:600;text-decoration:none;}
.bot-links a:hover{color:#111;text-decoration:underline;}
.divider{border:none;border-top:1px solid #f0f0f0;margin:8px 0;}
@media(max-width:520px){.form-row-2{grid-template-columns:1fr;}.reg-body{padding:24px 20px;}.reg-head{padding:22px 20px;}}
</style>
</head>
<body>
<div class="shell">
  <div class="reg-head">
    <div class="reg-head-icon"><i class="bi bi-person-plus-fill"></i></div>
    <div>
      <h1>Create an Account</h1>
      <p>Library Management System — Borrower Registration</p>
    </div>
  </div>
  <div class="reg-body">
    <?php if ($errors): ?>
    <div class="err-box">
      <strong><i class="bi bi-exclamation-triangle-fill"></i> Please fix the following:</strong>
      <ul><?php foreach($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
    </div>
    <?php endif; ?>

    <form method="POST">
      <div class="form-row-2">
        <div class="form-group">
          <label class="frm-lbl">Full Name <span class="req">*</span></label>
          <input type="text" name="full_name" class="frm-input" required value="<?= htmlspecialchars($data['full_name']??'') ?>" placeholder="Your full name">
        </div>
        <div class="form-group">
          <label class="frm-lbl">Username <span class="req">*</span></label>
          <input type="text" name="username" class="frm-input" required value="<?= htmlspecialchars($data['username']??'') ?>" placeholder="e.g. juandelacruz">
          <span class="frm-hint">Letters, numbers, underscore — min 4 chars</span>
        </div>
      </div>
      <div class="form-row-2">
        <div class="form-group">
          <label class="frm-lbl">Password <span class="req">*</span></label>
          <input type="password" name="password" class="frm-input" required placeholder="Min. 6 characters">
        </div>
        <div class="form-group">
          <label class="frm-lbl">Confirm Password <span class="req">*</span></label>
          <input type="password" name="confirm_password" class="frm-input" required placeholder="Repeat password">
        </div>
      </div>
      <div class="form-row-2">
        <div class="form-group">
          <label class="frm-lbl">Email Address</label>
          <input type="email" name="email" class="frm-input" value="<?= htmlspecialchars($data['email']??'') ?>" placeholder="email@example.com">
        </div>
        <div class="form-group">
          <label class="frm-lbl">Phone Number</label>
          <input type="text" name="phone" class="frm-input" value="<?= htmlspecialchars($data['phone']??'') ?>" placeholder="09XXXXXXXXX">
        </div>
      </div>
      <div class="form-group">
        <label class="frm-lbl">Membership Type <span class="req">*</span></label>
        <select name="membership_type" class="frm-input">
          <?php foreach(['student'=>'Student','faculty'=>'Faculty','staff'=>'Staff','public'=>'Public'] as $v=>$l): ?>
          <option value="<?= $v ?>" <?= ($data['membership_type']??'student')===$v?'selected':'' ?>><?= $l ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="frm-lbl">Address</label>
        <textarea name="address" class="frm-input" rows="2" placeholder="Optional" style="resize:vertical;"><?= htmlspecialchars($data['address']??'') ?></textarea>
      </div>
      <button type="submit" class="btn-reg"><i class="bi bi-check-circle"></i>Create My Account</button>
    </form>

    <div class="bot-links">
      Already have an account? <a href="<?= BASE_URL ?>/auth/login.php">Sign in →</a>
      <hr class="divider">
      <a href="<?= BASE_URL ?>/login.php">Admin Login</a>
    </div>
  </div>
</div>
</body>
</html>
