<?php
if (!defined('BASE_URL')) define('BASE_URL', '/library_management');
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$currentDir  = basename(dirname($_SERVER['PHP_SELF']));
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?> | Library Management System</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="wrapper">

<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon"><i class="bi bi-book-half"></i></div>
        <div class="brand-text">
            <span class="brand-title">Library Management System</span>
            <span class="brand-sub">Admin Panel</span>
        </div>
    </div>

    <div class="sidebar-admin">
        <div class="admin-avatar"><i class="bi bi-shield-fill"></i></div>
        <div class="admin-info">
            <span class="admin-name"><?= htmlspecialchars($_SESSION['admin_name'] ?? 'Admin') ?></span>
            <span class="admin-role">System Administrator</span>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-label">Overview</div>
        <a href="<?= BASE_URL ?>/dashboard.php" class="nav-item <?= $currentPage==='dashboard'?'active':'' ?>">
            <i class="bi bi-speedometer2"></i><span>Dashboard</span>
        </a>

        <div class="nav-label">Books</div>
        <a href="<?= BASE_URL ?>/books/list.php" class="nav-item <?= ($currentDir==='books' && in_array($currentPage,['list','edit']))?'active':'' ?>">
            <i class="bi bi-journals"></i><span>Book Records</span>
        </a>
        <a href="<?= BASE_URL ?>/books/add.php" class="nav-item <?= ($currentDir==='books' && $currentPage==='add')?'active':'' ?>">
            <i class="bi bi-plus-circle"></i><span>Add Book</span>
        </a>

        <div class="nav-label">Users</div>
        <a href="<?= BASE_URL ?>/users/list.php" class="nav-item <?= ($currentDir==='users' && in_array($currentPage,['list','edit']))?'active':'' ?>">
            <i class="bi bi-people"></i><span>Manage Users</span>
        </a>

        <div class="nav-label">Transactions</div>
        <a href="<?= BASE_URL ?>/transactions/borrow.php" class="nav-item <?= $currentPage==='borrow'?'active':'' ?>">
            <i class="bi bi-box-arrow-right"></i><span>Borrow Book</span>
        </a>
        <a href="<?= BASE_URL ?>/transactions/return.php" class="nav-item <?= $currentPage==='return'?'active':'' ?>">
            <i class="bi bi-box-arrow-in-left"></i><span>Return Book</span>
        </a>
        <a href="<?= BASE_URL ?>/transactions/history.php" class="nav-item <?= $currentPage==='history'?'active':'' ?>">
            <i class="bi bi-clock-history"></i><span>Transaction History</span>
        </a>

        <div class="nav-label">Reports</div>
        <a href="<?= BASE_URL ?>/reports/index.php" class="nav-item <?= $currentDir==='reports'?'active':'' ?>">
            <i class="bi bi-file-earmark-bar-graph"></i><span>Generate Reports</span>
        </a>
        <a href="<?= BASE_URL ?>/books/list.php?status=Available" class="nav-item">
            <i class="bi bi-bookmark-check"></i><span>Availability Monitor</span>
        </a>

        <div class="nav-label">Account</div>
        <a href="<?= BASE_URL ?>/logout.php" class="nav-item logout-item">
            <i class="bi bi-box-arrow-left"></i><span>Logout</span>
        </a>
    </nav>
</aside>

<div class="main-content">
    <header class="topbar">
        <button class="sidebar-toggle" id="sidebarToggle"><i class="bi bi-list"></i></button>
        <div class="topbar-title"><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?></div>
        <div class="topbar-right">
            <span class="topbar-date"><i class="bi bi-calendar3 me-1"></i><?= date('M j, Y') ?></span>
            <a href="<?= BASE_URL ?>/logout.php"
               style="display:inline-flex;align-items:center;gap:6px;padding:7px 14px;background:#f3f3f3;border:1px solid #e0e0e0;border-radius:8px;color:#555;text-decoration:none;font-size:12.5px;font-weight:500;transition:all .2s;"
               onmouseover="this.style.background='#111';this.style.color='#fff';this.style.borderColor='#111';"
               onmouseout="this.style.background='#f3f3f3';this.style.color='#555';this.style.borderColor='#e0e0e0';">
                <i class="bi bi-box-arrow-left"></i>Logout
            </a>
        </div>
    </header>

    <div class="page-content">
        <?php if ($flash): ?>
        <div class="custom-alert alert-<?= $flash['type']==='success'?'success':'danger' ?>" role="alert"
             style="display:flex;align-items:center;justify-content:space-between;">
            <span>
                <i class="bi bi-<?= $flash['type']==='success'?'check-circle-fill':'exclamation-triangle-fill' ?> me-2"></i>
                <?= htmlspecialchars($flash['message']) ?>
            </span>
            <button onclick="this.parentElement.remove()" style="background:none;border:none;cursor:pointer;color:inherit;font-size:16px;padding:0;margin-left:12px;">&times;</button>
        </div>
        <?php endif; ?>
