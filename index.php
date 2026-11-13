<?php
// index.php — Public landing page
define('BASE_URL', '/library_management');
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

// Already logged in? send to the right dashboard
if (isLoggedIn())     { header('Location: ' . BASE_URL . '/dashboard.php');      exit(); }
if (isUserLoggedIn()) { header('Location: ' . BASE_URL . '/user/dashboard.php'); exit(); }

$pdo = getDB();
$totalBooks     = (int) $pdo->query("SELECT COUNT(*) FROM books")->fetchColumn();
$availableBooks = (int) $pdo->query("SELECT COUNT(*) FROM books WHERE status='Available'")->fetchColumn();
$borrowedBooks  = (int) $pdo->query("SELECT COUNT(*) FROM books WHERE status='Borrowed'")->fetchColumn();
$totalUsers     = (int) $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Library Management System | School Library</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
html{scroll-behavior:smooth;}
body{font-family:'Inter',Arial,sans-serif;background:#f1f1f1;color:#111;line-height:1.6;-webkit-font-smoothing:antialiased;}

@keyframes fadeInUp{from{opacity:0;transform:translateY(24px);}to{opacity:1;transform:translateY(0);}}
@keyframes fadeIn{from{opacity:0;}to{opacity:1;}}
@keyframes float{0%,100%{transform:translateY(0);}50%{transform:translateY(-10px);}}
@keyframes pulse{0%,100%{opacity:1;transform:scale(1);}50%{opacity:.5;transform:scale(1.4);}}

/* navbar */
.nav{position:sticky;top:0;z-index:100;background:rgba(255,255,255,.85);backdrop-filter:blur(10px);border-bottom:1px solid #eaeaea;}
.nav-inner{max-width:1180px;margin:0 auto;padding:14px 24px;display:flex;align-items:center;justify-content:space-between;}
.nav-brand{display:flex;align-items:center;gap:10px;text-decoration:none;color:#111;}
.nav-brand-icon{width:36px;height:36px;background:#111;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:17px;flex-shrink:0;}
.nav-brand-text{font-size:14px;font-weight:700;letter-spacing:-.01em;line-height:1.2;}
.nav-brand-text small{display:block;font-size:10px;font-weight:400;color:#999;text-transform:uppercase;letter-spacing:.5px;}
.nav-links{display:flex;align-items:center;gap:8px;}
.nav-btn{padding:9px 18px;border-radius:8px;font-size:13px;font-weight:500;text-decoration:none;transition:all .2s;border:1px solid transparent;}
.nav-btn-ghost{color:#555;}
.nav-btn-ghost:hover{background:#f3f3f3;color:#111;}
.nav-btn-dark{background:#111;color:#fff;}
.nav-btn-dark:hover{background:#333;transform:translateY(-1px);box-shadow:0 6px 16px rgba(0,0,0,.12);}

/* hero */
.hero{max-width:1180px;margin:0 auto;padding:80px 24px 60px;display:flex;align-items:center;gap:60px;}
.hero-text{flex:1;animation:fadeInUp .6s ease both;}
.hero-badge{display:inline-flex;align-items:center;gap:8px;background:#fff;border:1px solid #e5e5e5;padding:6px 14px;border-radius:20px;font-size:12px;font-weight:600;color:#666;margin-bottom:24px;box-shadow:0 1px 3px rgba(0,0,0,.04);}
.hero-badge .dot{width:7px;height:7px;background:#16a34a;border-radius:50%;animation:pulse 2s ease-in-out infinite;}
.hero h1{font-size:52px;font-weight:800;letter-spacing:-.03em;line-height:1.08;color:#111;margin-bottom:20px;}
.hero h1 span{color:#999;}
.hero p{font-size:16px;color:#777;line-height:1.75;max-width:480px;margin-bottom:32px;}
.hero-ctas{display:flex;gap:12px;flex-wrap:wrap;margin-bottom:40px;}
.btn-cta{display:inline-flex;align-items:center;gap:8px;padding:13px 26px;border-radius:10px;font-size:14px;font-weight:600;text-decoration:none;transition:all .2s;}
.btn-cta-dark{background:#111;color:#fff;}
.btn-cta-dark:hover{background:#333;transform:translateY(-2px);box-shadow:0 10px 24px rgba(0,0,0,.15);}
.btn-cta-light{background:#fff;color:#111;border:1px solid #e0e0e0;}
.btn-cta-light:hover{background:#f8f8f8;border-color:#ccc;transform:translateY(-2px);box-shadow:0 6px 16px rgba(0,0,0,.06);}

.hero-stats{display:flex;gap:32px;flex-wrap:wrap;}
.hero-stat-num{font-size:28px;font-weight:800;letter-spacing:-.03em;color:#111;}
.hero-stat-lbl{font-size:12px;color:#999;text-transform:uppercase;letter-spacing:.5px;margin-top:2px;}

/* herovisual */
.hero-visual{flex:0 0 420px;display:flex;align-items:center;justify-content:center;animation:fadeIn .8s ease both;}
.visual-card{width:100%;background:#111;border-radius:20px;padding:32px;box-shadow:0 30px 60px rgba(0,0,0,.18);position:relative;overflow:hidden;animation:float 5s ease-in-out infinite;}
.visual-card::before{content:'';position:absolute;top:-60px;right:-60px;width:180px;height:180px;border-radius:50%;background:rgba(255,255,255,.04);}
.visual-card::after{content:'';position:absolute;bottom:-40px;left:-40px;width:140px;height:140px;border-radius:50%;background:rgba(255,255,255,.03);}
.visual-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;position:relative;z-index:1;}
.visual-top-icon{width:40px;height:40px;background:rgba(255,255,255,.1);border-radius:10px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:18px;}
.visual-top-badge{font-size:11px;color:rgba(255,255,255,.4);text-transform:uppercase;letter-spacing:1px;}
.visual-stats{display:grid;grid-template-columns:1fr 1fr;gap:14px;position:relative;z-index:1;}
.visual-stat{background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.08);border-radius:12px;padding:18px;}
.visual-stat-icon{font-size:18px;color:rgba(255,255,255,.5);margin-bottom:10px;}
.visual-stat-num{font-size:26px;font-weight:800;color:#fff;letter-spacing:-.03em;line-height:1;}
.visual-stat-lbl{font-size:11px;color:rgba(255,255,255,.35);margin-top:4px;}
.visual-foot{margin-top:20px;padding-top:18px;border-top:1px solid rgba(255,255,255,.08);font-size:12px;color:rgba(255,255,255,.35);display:flex;align-items:center;gap:8px;position:relative;z-index:1;}

/* portal */
.section{max-width:1180px;margin:0 auto;padding:50px 24px;}
.section-head{text-align:center;margin-bottom:40px;}
.section-head h2{font-size:30px;font-weight:800;letter-spacing:-.03em;color:#111;margin-bottom:10px;}
.section-head p{font-size:14px;color:#999;max-width:480px;margin:0 auto;}

.portal-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;}
.portal-card{background:#fff;border:1px solid #e8e8e8;border-radius:16px;padding:32px 28px;transition:all .25s ease;animation:fadeInUp .5s ease both;text-decoration:none;color:inherit;display:flex;flex-direction:column;}
.portal-card:nth-child(1){animation-delay:.05s;}
.portal-card:nth-child(2){animation-delay:.12s;}
.portal-card:nth-child(3){animation-delay:.19s;}
.portal-card:hover{transform:translateY(-5px);box-shadow:0 16px 40px rgba(0,0,0,.08);border-color:#ddd;}
.portal-icon{width:52px;height:52px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:24px;margin-bottom:20px;transition:transform .25s cubic-bezier(.34,1.56,.64,1);}
.portal-card:hover .portal-icon{transform:scale(1.08) rotate(-4deg);}
.portal-icon.dark{background:#111;color:#fff;}
.portal-icon.blue{background:#eff6ff;color:#1d4ed8;}
.portal-icon.green{background:#f0fdf4;color:#16a34a;}
.portal-card h3{font-size:17px;font-weight:700;letter-spacing:-.01em;margin-bottom:8px;}
.portal-card p{font-size:13px;color:#999;line-height:1.7;margin-bottom:20px;flex:1;}
.portal-link{display:inline-flex;align-items:center;gap:6px;font-size:13px;font-weight:600;color:#111;}
.portal-card:hover .portal-link i{transform:translateX(4px);}
.portal-link i{transition:transform .2s;}

/* feats ngga */
.features{background:#fff;border-top:1px solid #eee;border-bottom:1px solid #eee;}
.feat-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:24px;}
.feat-item{text-align:center;padding:20px;animation:fadeInUp .5s ease both;}
.feat-item:nth-child(1){animation-delay:.05s;}
.feat-item:nth-child(2){animation-delay:.10s;}
.feat-item:nth-child(3){animation-delay:.15s;}
.feat-item:nth-child(4){animation-delay:.20s;}
.feat-icon{width:48px;height:48px;background:#f3f3f3;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px;color:#111;margin:0 auto 14px;transition:all .2s;}
.feat-item:hover .feat-icon{background:#111;color:#fff;transform:scale(1.08);}
.feat-item h4{font-size:14px;font-weight:700;margin-bottom:6px;}
.feat-item p{font-size:12.5px;color:#999;line-height:1.7;}

/* footer */
.foot{max-width:1180px;margin:0 auto;padding:36px 24px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;font-size:12.5px;color:#aaa;}
.foot a{color:#777;text-decoration:none;}
.foot a:hover{color:#111;}

/* fit */
@media(max-width:980px){
  .hero{flex-direction:column;text-align:center;padding-top:50px;}
  .hero-text{display:flex;flex-direction:column;align-items:center;}
  .hero p{margin-left:auto;margin-right:auto;}
  .hero-ctas{justify-content:center;}
  .hero-stats{justify-content:center;}
  .hero-visual{flex:none;width:100%;max-width:420px;}
  .portal-grid{grid-template-columns:1fr;}
  .feat-grid{grid-template-columns:1fr 1fr;}
}
@media(max-width:560px){
  .hero h1{font-size:36px;}
  .nav-links{gap:4px;}
  .nav-btn{padding:8px 12px;font-size:12px;}
  .nav-brand-text{font-size:12px;}
  .feat-grid{grid-template-columns:1fr;}
  .hero-stats{gap:24px;}
}
</style>
</head>
<body>

<!-- Navbar -->
<nav class="nav">
  <div class="nav-inner">
    <a href="<?= BASE_URL ?>/index.php" class="nav-brand">
      <div class="nav-brand-icon"><i class="bi bi-book-half"></i></div>
      <div class="nav-brand-text">Library Management System<small>School Library</small></div>
    </a>
    <div class="nav-links">
      <a href="<?= BASE_URL ?>/auth/login.php" class="nav-btn nav-btn-ghost" style="display:none;">Borrower Login</a>
      <a href="<?= BASE_URL ?>/login.php" class="nav-btn nav-btn-dark">Admin Login</a>
    </div>
  </div>
</nav>

<!-- Hero -->
<section class="hero">
  <div class="hero-text">
    <div class="hero-badge"><span class="dot"></span> System online &amp; ready</div>
    <h1>The school's<br>library, <span>organized.</span></h1>
    <p>Search the catalog, borrow books, and track due dates. All from one clean, modern dashboard built for students, faculty, and librarians.</p>
    <div class="hero-ctas">
      <a href="<?= BASE_URL ?>/auth/register.php" class="btn-cta btn-cta-dark"><i class="bi bi-person-plus"></i> Create an Account</a>
      <a href="<?= BASE_URL ?>/auth/login.php" class="btn-cta btn-cta-light"><i class="bi bi-box-arrow-in-right"></i> Borrower Login</a>
    </div>
    <div class="hero-stats">
      <div>
        <div class="hero-stat-num"><?= number_format($totalBooks) ?>+</div>
        <div class="hero-stat-lbl">Books in Catalog</div>
      </div>
      <div>
        <div class="hero-stat-num"><?= number_format($availableBooks) ?>+</div>
        <div class="hero-stat-lbl">Available Now</div>
      </div>
      <div>
        <div class="hero-stat-num"><?= number_format($totalUsers) ?></div>
        <div class="hero-stat-lbl">Registered Members</div>
      </div>
    </div>
  </div>

  <div class="hero-visual">
    <div class="visual-card">
      <div class="visual-top">
        <div class="visual-top-icon"><i class="bi bi-book-half"></i></div>
        <div class="visual-top-badge">Live Catalog</div>
      </div>
      <div class="visual-stats">
        <div class="visual-stat">
          <div class="visual-stat-icon"><i class="bi bi-journals"></i></div>
          <div class="visual-stat-num"><?= number_format($totalBooks) ?></div>
          <div class="visual-stat-lbl">Total Books</div>
        </div>
        <div class="visual-stat">
          <div class="visual-stat-icon"><i class="bi bi-bookmark-check"></i></div>
          <div class="visual-stat-num"><?= number_format($availableBooks) ?></div>
          <div class="visual-stat-lbl">Available</div>
        </div>
        <div class="visual-stat">
          <div class="visual-stat-icon"><i class="bi bi-box-arrow-right"></i></div>
          <div class="visual-stat-num"><?= number_format($borrowedBooks) ?></div>
          <div class="visual-stat-lbl">Borrowed</div>
        </div>
        <div class="visual-stat">
          <div class="visual-stat-icon"><i class="bi bi-people"></i></div>
          <div class="visual-stat-num"><?= number_format($totalUsers) ?></div>
          <div class="visual-stat-lbl">Members</div>
        </div>
      </div>
      <div class="visual-foot"><i class="bi bi-arrow-repeat"></i> Updated in real time from the database</div>
    </div>
  </div>
</section>

<!-- Portals -->
<section class="section">
  <div class="section-head">
    <h2>Choose your portal</h2>
    <p>Sign in based on your role, or create a free borrower account to get started.</p>
  </div>
  <div class="portal-grid">
    <a href="<?= BASE_URL ?>/login.php" class="portal-card">
      <div class="portal-icon dark"><i class="bi bi-shield-lock"></i></div>
      <h3>Admin Login</h3>
      <p>Manage the book catalog, registered users, borrowing transactions, and generate reports.</p>
      <span class="portal-link">Go to Admin Panel <i class="bi bi-arrow-right"></i></span>
    </a>

    <a href="<?= BASE_URL ?>/auth/login.php" class="portal-card">
      <div class="portal-icon blue"><i class="bi bi-person-badge"></i></div>
      <h3>Borrower Login</h3>
      <p>Already have an account? Sign in to browse books, borrow titles, and view your history.</p>
      <span class="portal-link">Sign In <i class="bi bi-arrow-right"></i></span>
    </a>

    <a href="<?= BASE_URL ?>/auth/register.php" class="portal-card">
      <div class="portal-icon green"><i class="bi bi-person-plus"></i></div>
      <h3>Create an Account</h3>
      <p>New here? Register as a student, faculty, or staff member to start borrowing books.</p>
      <span class="portal-link">Register Now <i class="bi bi-arrow-right"></i></span>
    </a>
  </div>
</section>

<!-- Features -->
<section class="features">
  <div class="section">
    <div class="section-head">
      <h2>Everything the library needs</h2>
      <p>A complete, modern toolkit for managing books, members, and transactions.</p>
    </div>
    <div class="feat-grid">
      <div class="feat-item">
        <div class="feat-icon"><i class="bi bi-search"></i></div>
        <h4>Powerful Search</h4>
        <p>Find books instantly by title, author, book number, or course code.</p>
      </div>
      <div class="feat-item">
        <div class="feat-icon"><i class="bi bi-arrow-left-right"></i></div>
        <h4>Borrow &amp; Return</h4>
        <p>Simple borrowing workflow with automatic due dates and fine tracking.</p>
      </div>
      <div class="feat-item">
        <div class="feat-icon"><i class="bi bi-bar-chart"></i></div>
        <h4>Live Reports</h4>
        <p>Generate printable reports on availability, borrowing trends, and inventory.</p>
      </div>
      <div class="feat-item">
        <div class="feat-icon"><i class="bi bi-shield-check"></i></div>
        <h4>Secure Accounts</h4>
        <p>Role-based access for admins and borrowers, with encrypted passwords.</p>
      </div>
    </div>
  </div>
</section>

<!-- Footer -->
<footer class="foot">
  <div>Library Management System &copy; <?= date('Y') ?> — School Library</div>
  <div>
    <a href="<?= BASE_URL ?>/login.php">Admin</a> &nbsp;·&nbsp;
    <a href="<?= BASE_URL ?>/auth/login.php">Borrower Login</a> &nbsp;·&nbsp;
    <a href="<?= BASE_URL ?>/auth/register.php">Register</a>
  </div>
</footer>

</body>
</html>
