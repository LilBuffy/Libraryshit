<?php
define('BASE_URL', '/library_management');
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (isLoggedIn()) { header('Location: '.BASE_URL.'/dashboard.php'); exit(); }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        $pdo  = getDB();
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();
        if ($admin && password_verify($password, $admin['password'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id']       = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_name']     = $admin['full_name'];
            logActivity('Login', 'Admin logged in: '.$username, $username);
            header('Location: '.BASE_URL.'/dashboard.php'); exit();
        } else {
            $error = 'Invalid username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login | Library Management System</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Inter',Arial,sans-serif;background:#0a0a0a;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;position:relative;overflow:hidden;}
/* Grid background */
body::before{content:'';position:absolute;inset:0;background-image:linear-gradient(rgba(255,255,255,.03) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.03) 1px,transparent 1px);background-size:48px 48px;pointer-events:none;}
/* Glow */
body::after{content:'';position:absolute;top:20%;left:50%;transform:translate(-50%,-50%);width:600px;height:600px;background:radial-gradient(circle,rgba(255,255,255,.04) 0%,transparent 70%);pointer-events:none;}

@keyframes fadeInUp{from{opacity:0;transform:translateY(24px);}to{opacity:1;transform:translateY(0);}}
@keyframes float{0%,100%{transform:translateY(0);}50%{transform:translateY(-8px);}}

.login-shell{display:flex;width:880px;max-width:100%;min-height:540px;border-radius:20px;overflow:hidden;box-shadow:0 40px 80px rgba(0,0,0,.7);border:1px solid rgba(255,255,255,.08);animation:fadeInUp .5s cubic-bezier(0,.2,.2,1) both;}

/* Left panel */
.l-left{flex:1;background:linear-gradient(145deg,#111 0%,#1a1a1a 100%);padding:52px 44px;display:flex;flex-direction:column;justify-content:space-between;position:relative;overflow:hidden;border-right:1px solid rgba(255,255,255,.06);}
.l-left::before{content:'';position:absolute;bottom:-60px;right:-60px;width:220px;height:220px;border-radius:50%;background:rgba(255,255,255,.03);pointer-events:none;}
.l-left::after{content:'';position:absolute;top:-40px;left:-40px;width:160px;height:160px;border-radius:50%;background:rgba(255,255,255,.02);pointer-events:none;}

.l-logo{display:flex;align-items:center;gap:14px;}
.l-logo-icon{width:44px;height:44px;background:#fff;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:22px;color:#111;box-shadow:0 4px 16px rgba(0,0,0,.3);flex-shrink:0;}
.l-logo-text{font-size:13px;font-weight:700;color:#fff;line-height:1.3;}
.l-logo-text small{font-size:10px;color:rgba(255,255,255,.35);font-weight:400;display:block;margin-top:1px;letter-spacing:.4px;text-transform:uppercase;}

.l-main{margin-top:40px;}
.l-title{font-size:34px;font-weight:700;color:#fff;line-height:1.15;letter-spacing:-.03em;margin-bottom:14px;}
.l-title span{color:rgba(255,255,255,.4);}
.l-desc{font-size:13.5px;color:rgba(255,255,255,.4);line-height:1.75;max-width:300px;}

.l-features{margin-top:32px;display:flex;flex-direction:column;gap:8px;}
.l-feat{display:flex;align-items:center;gap:10px;font-size:12.5px;color:rgba(255,255,255,.4);}
.l-feat i{font-size:14px;color:rgba(255,255,255,.25);}

.l-stats{display:flex;gap:28px;margin-top:40px;}
.l-stat-num{font-size:22px;font-weight:700;color:#fff;line-height:1;}
.l-stat-lbl{font-size:10px;color:rgba(255,255,255,.3);text-transform:uppercase;letter-spacing:.5px;margin-top:3px;}

/* Right panel */
.l-right{width:380px;flex-shrink:0;background:#fff;padding:52px 44px;display:flex;flex-direction:column;justify-content:center;}
.l-right h2{font-size:22px;font-weight:700;color:#111;letter-spacing:-.02em;margin-bottom:6px;}
.l-right p{font-size:13px;color:#999;margin-bottom:32px;}

.frm-lbl{display:block;font-size:11px;font-weight:600;color:#666;text-transform:uppercase;letter-spacing:.5px;margin-bottom:7px;}
.frm-wrap{position:relative;margin-bottom:18px;}
.frm-icon{position:absolute;left:13px;top:50%;transform:translateY(-50%);color:#bbb;font-size:15px;pointer-events:none;}
.frm-input{width:100%;padding:12px 14px 12px 40px;border:1.5px solid #e5e5e5;border-radius:10px;font-size:14px;font-family:'Inter',Arial,sans-serif;color:#111;background:#fafafa;outline:none;transition:all .2s;}
.frm-input:focus{border-color:#111;background:#fff;box-shadow:0 0 0 3px rgba(0,0,0,.06);}
.frm-input::placeholder{color:#ccc;}

.btn-sign{width:100%;padding:13px;background:#111;color:#fff;border:none;border-radius:10px;font-size:14px;font-family:'Inter',Arial,sans-serif;font-weight:600;cursor:pointer;transition:all .2s;letter-spacing:-.01em;display:flex;align-items:center;justify-content:center;gap:8px;margin-top:6px;}
.btn-sign:hover{background:#333;transform:translateY(-1px);box-shadow:0 8px 20px rgba(0,0,0,.12);}
.btn-sign:active{transform:translateY(0);}

.err-msg{background:#fef2f2;border:1px solid #fecaca;border-left:3px solid #dc2626;color:#dc2626;padding:11px 14px;border-radius:8px;font-size:13px;margin-bottom:18px;display:flex;align-items:center;gap:8px;animation:fadeInUp .2s ease;}
.hint-box{margin-top:20px;padding:13px 16px;background:#f8f8f8;border:1px solid #eee;border-radius:8px;font-size:11.5px;color:#999;line-height:1.8;}
.hint-box strong{color:#666;}
.user-link{text-align:center;margin-top:18px;font-size:12.5px;color:#aaa;}
.user-link a{color:#555;font-weight:600;text-decoration:none;}
.user-link a:hover{color:#111;text-decoration:underline;}

@media(max-width:700px){.l-left{display:none;}.login-shell{width:100%;max-width:420px;}.l-right{width:100%;padding:36px 28px;}}
</style>
</head>
<body>
<div class="login-shell">
  <div class="l-left">
    <div class="l-logo">
      <div class="l-logo-icon"><i class="bi bi-book-half"></i></div>
      <div class="l-logo-text">Library Management System<small>School Library</small></div>
    </div>
    <div class="l-main">
      <div class="l-title">Your complete<br>library <span>solution.</span></div>
      <div class="l-desc">Manage books, borrowers, and transactions from one clean, powerful dashboard.</div>
      <div class="l-features">
        <div class="l-feat"><i class="bi bi-check-circle-fill"></i>2,000+ book records ready to use</div>
        <div class="l-feat"><i class="bi bi-check-circle-fill"></i>Student &amp; admin role system</div>
        <div class="l-feat"><i class="bi bi-check-circle-fill"></i>Borrow, return &amp; fine tracking</div>
        <div class="l-feat"><i class="bi bi-check-circle-fill"></i>Printable availability reports</div>
      </div>
    </div>
    <div class="l-stats">
      <div><div class="l-stat-num">2,000+</div><div class="l-stat-lbl">Books</div></div>
      <div><div class="l-stat-num">100%</div><div class="l-stat-lbl">Web-based</div></div>
      <div><div class="l-stat-num">Free</div><div class="l-stat-lbl">No installer</div></div>
    </div>
  </div>

  <div class="l-right">
    <h2>Welcome back</h2>
    <p>Sign in to your administrator account</p>

    <?php if ($error): ?>
    <div class="err-msg"><i class="bi bi-exclamation-circle-fill"></i><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" autocomplete="off">
      <label class="frm-lbl">Username</label>
      <div class="frm-wrap">
        <i class="bi bi-person frm-icon"></i>
        <input type="text" name="username" class="frm-input" placeholder="admin"
               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" autocomplete="username" required>
      </div>
      <label class="frm-lbl">Password</label>
      <div class="frm-wrap">
        <i class="bi bi-lock frm-icon"></i>
        <input type="password" name="password" class="frm-input" placeholder="••••••••" autocomplete="current-password" required>
      </div>
      <button type="submit" class="btn-sign"><i class="bi bi-arrow-right-circle"></i>Sign In</button>
    </form>

    <div class="user-link">
      Borrower? <a href="<?= BASE_URL ?>/auth/login.php">Sign in here →</a>
    </div>
  </div>
</div>
</body>
</html>
