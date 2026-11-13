<?php
define('BASE_URL', '/library_management');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (isUserLoggedIn()) { header('Location: '.BASE_URL.'/user/dashboard.php'); exit(); }
if (isLoggedIn())     { header('Location: '.BASE_URL.'/dashboard.php'); exit(); }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if (!$username || !$password) { $error = 'Please enter your username and password.'; }
    else {
        $pdo  = getDB();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND status = 'active' LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id']        = $user['id'];
            $_SESSION['user_username']  = $user['username'];
            $_SESSION['user_name']      = $user['full_name'];
            $_SESSION['user_member_id'] = $user['member_id'];
            $_SESSION['user_type']      = $user['membership_type'];
            logActivity('User Login','User logged in: '.$username,$username);
            flash('success','Welcome, '.$user['full_name'].'!');
            header('Location: '.BASE_URL.'/user/dashboard.php'); exit();
        } else { $error = 'Invalid username or password, or account is inactive.'; }
    }
}
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Borrower Login | Library Management System</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Inter',Arial,sans-serif;background:#f1f1f1;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;}
@keyframes fadeInUp{from{opacity:0;transform:translateY(20px);}to{opacity:1;transform:translateY(0);}}
.shell{display:flex;width:820px;max-width:100%;border-radius:16px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,.1);border:1px solid #e5e5e5;animation:fadeInUp .4s ease both;}
.l-panel{flex:1;background:#111;padding:48px 40px;display:flex;flex-direction:column;justify-content:space-between;}
.l-logo{display:flex;align-items:center;gap:12px;}
.l-logo-icon{width:40px;height:40px;background:#fff;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:20px;color:#111;}
.l-logo-text{font-size:12.5px;font-weight:700;color:#fff;line-height:1.3;}
.l-logo-text small{display:block;font-size:10px;color:rgba(255,255,255,.3);font-weight:400;}
.l-body{margin-top:32px;}
.l-title{font-size:28px;font-weight:700;color:#fff;letter-spacing:-.02em;margin-bottom:12px;line-height:1.2;}
.l-desc{font-size:13px;color:rgba(255,255,255,.4);line-height:1.7;margin-bottom:24px;}
.l-items{display:flex;flex-direction:column;gap:7px;}
.l-item{font-size:12.5px;color:rgba(255,255,255,.4);display:flex;align-items:center;gap:8px;}
.l-item i{color:rgba(255,255,255,.2);}
.l-foot{margin-top:32px;font-size:11.5px;color:rgba(255,255,255,.2);border-top:1px solid rgba(255,255,255,.06);padding-top:16px;}
.r-panel{width:360px;flex-shrink:0;background:#fff;padding:48px 40px;display:flex;flex-direction:column;justify-content:center;}
.r-panel h2{font-size:20px;font-weight:700;color:#111;letter-spacing:-.02em;margin-bottom:4px;}
.r-panel p{font-size:12.5px;color:#aaa;margin-bottom:28px;}
.frm-lbl{display:block;font-size:11px;font-weight:600;color:#888;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px;}
.frm-wrap{position:relative;margin-bottom:16px;}
.frm-icon{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#ccc;font-size:14px;pointer-events:none;}
.frm-input{width:100%;padding:11px 12px 11px 38px;border:1.5px solid #e8e8e8;border-radius:8px;font-size:13.5px;font-family:'Inter',Arial,sans-serif;color:#111;outline:none;transition:all .2s;background:#fafafa;}
.frm-input:focus{border-color:#111;background:#fff;box-shadow:0 0 0 3px rgba(0,0,0,.05);}
.frm-input::placeholder{color:#ccc;}
.btn-in{width:100%;padding:12px;background:#111;color:#fff;border:none;border-radius:8px;font-size:14px;font-family:'Inter',Arial,sans-serif;font-weight:600;cursor:pointer;transition:all .2s;margin-top:4px;}
.btn-in:hover{background:#333;transform:translateY(-1px);box-shadow:0 6px 16px rgba(0,0,0,.1);}
.err-box{background:#fef2f2;border:1px solid #fecaca;border-left:3px solid #dc2626;color:#dc2626;padding:10px 13px;border-radius:7px;font-size:12.5px;margin-bottom:16px;display:flex;align-items:center;gap:7px;}
.ok-box{background:#f0fdf4;border:1px solid #bbf7d0;border-left:3px solid #16a34a;color:#16a34a;padding:10px 13px;border-radius:7px;font-size:12.5px;margin-bottom:16px;display:flex;align-items:center;gap:7px;}
.links{margin-top:20px;text-align:center;font-size:12px;color:#bbb;line-height:2;}
.links a{color:#555;font-weight:600;text-decoration:none;}
.links a:hover{color:#111;text-decoration:underline;}
.divider{border:none;border-top:1px solid #f0f0f0;margin:12px 0;}
@media(max-width:600px){.l-panel{display:none;}.shell{width:100%;max-width:400px;}.r-panel{width:100%;padding:36px 24px;}}
</style>
</head>
<body>
<div class="shell">
  <div class="l-panel">
    <div class="l-logo">
      <div class="l-logo-icon"><i class="bi bi-book-half"></i></div>
      <div class="l-logo-text">Library Management System<small>Borrower Portal</small></div>
    </div>
    <div class="l-body">
      <div class="l-title">Borrow books from<br>your school library.</div>
      <div class="l-desc">Search, borrow, and track your borrowed books online.</div>
      <div class="l-items">
        <div class="l-item"><i class="bi bi-check-circle-fill"></i>Browse 2,000+ library books</div>
        <div class="l-item"><i class="bi bi-check-circle-fill"></i>Borrow available titles online</div>
        <div class="l-item"><i class="bi bi-check-circle-fill"></i>View your full borrow history</div>
        <div class="l-item"><i class="bi bi-check-circle-fill"></i>Manage your profile</div>
      </div>
    </div>
    <div class="l-foot">Library Management System &mdash; School Library</div>
  </div>

  <div class="r-panel">
    <h2>Borrower Login</h2>
    <p>Sign in with your library account</p>

    <?php if ($flash && $flash['type']==='success'): ?>
    <div class="ok-box"><i class="bi bi-check-circle-fill"></i><?= htmlspecialchars($flash['message']) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="err-box"><i class="bi bi-exclamation-circle-fill"></i><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
      <label class="frm-lbl">Username</label>
      <div class="frm-wrap">
        <i class="bi bi-person frm-icon"></i>
        <input type="text" name="username" class="frm-input" required placeholder="Your username"
               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" autocomplete="username">
      </div>
      <label class="frm-lbl">Password</label>
      <div class="frm-wrap">
        <i class="bi bi-lock frm-icon"></i>
        <input type="password" name="password" class="frm-input" required placeholder="••••••••" autocomplete="current-password">
      </div>
      <button type="submit" class="btn-in">Sign In</button>
    </form>

    <div class="links">
      <a href="<?= BASE_URL ?>/auth/register.php">Create a new account</a>
      <hr class="divider">
      Admin? <a href="<?= BASE_URL ?>/login.php">Admin login →</a>
    </div>
  </div>
</div>
</body>
</html>
