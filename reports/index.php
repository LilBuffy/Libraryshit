<?php
define('BASE_URL', '/library_management');
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$pdo       = getDB();
$pageTitle = 'Generate Reports';
$filter    = $_GET['filter'] ?? 'all';
$where     = $filter==='available' ? "WHERE status='Available'" : ($filter==='borrowed' ? "WHERE status='Borrowed'" : '');

$stmt = $pdo->prepare("SELECT * FROM books $where ORDER BY book_number ASC");
$stmt->execute();
$books = $stmt->fetchAll();

$totalAll       = $pdo->query("SELECT COUNT(*) FROM books")->fetchColumn();
$totalAvailable = $pdo->query("SELECT COUNT(*) FROM books WHERE status='Available'")->fetchColumn();
$totalBorrowed  = $pdo->query("SELECT COUNT(*) FROM books WHERE status='Borrowed'")->fetchColumn();
$reportTitles   = ['all'=>'All Books Report','available'=>'Available Books Report','borrowed'=>'Borrowed Books Report'];
$reportTitle    = $reportTitles[$filter] ?? 'All Books Report';

require_once __DIR__ . '/../includes/header.php';
?>

<div class="section-card">
    <div class="section-header">
        <h3><i class="bi bi-file-earmark-bar-graph"></i>Generate Reports</h3>
        <button onclick="window.print()" class="btn-primary-custom no-print">
            <i class="bi bi-printer me-1"></i>Print Report
        </button>
    </div>

    <!-- Tabs -->
    <div style="display:flex;gap:8px;padding:14px 22px;border-bottom:1px solid var(--border);background:var(--gray-50);flex-wrap:wrap;" class="no-print">
        <?php foreach(['all'=>'All Books','available'=>'Available','borrowed'=>'Borrowed'] as $k=>$label): ?>
        <a href="?filter=<?= $k ?>"
           style="display:inline-flex;align-items:center;gap:7px;padding:8px 18px;border-radius:8px;border:1px solid <?= $filter===$k?'#111':'var(--border-dark)' ?>;background:<?= $filter===$k?'#111':'#fff' ?>;color:<?= $filter===$k?'#fff':'var(--text-muted)' ?>;text-decoration:none;font-size:13px;font-weight:<?= $filter===$k?'600':'400' ?>;transition:all .2s;">
            <?= $label ?>
            <span style="background:<?= $filter===$k?'rgba(255,255,255,.15)':'var(--gray-100)' ?>;color:<?= $filter===$k?'#fff':'var(--text-muted)' ?>;padding:1px 8px;border-radius:10px;font-size:11px;">
                <?= $k==='all'?$totalAll:($k==='available'?$totalAvailable:$totalBorrowed) ?>
            </span>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Report area -->
    <div style="padding:24px 22px;">
        <div style="text-align:center;margin-bottom:20px;padding-bottom:16px;border-bottom:2px solid #111;">
            <div style="font-size:11px;color:#999;text-transform:uppercase;letter-spacing:.5px;">Library Management System — School Library</div>
            <div style="font-size:20px;font-weight:700;color:#111;margin:6px 0;letter-spacing:-.02em;"><?= htmlspecialchars($reportTitle) ?></div>
            <div style="font-size:12px;color:#aaa;">
                Generated: <?= date('F j, Y, g:i A') ?> &nbsp;|&nbsp;
                Total: <strong style="color:#666;"><?= count($books) ?></strong> &nbsp;|&nbsp;
                Available: <strong style="color:#16a34a;"><?= $totalAvailable ?></strong> &nbsp;|&nbsp;
                Borrowed: <strong style="color:#c2410c;"><?= $totalBorrowed ?></strong>
            </div>
        </div>

        <table style="width:100%;border-collapse:collapse;font-size:12.5px;font-family:'Inter',Arial,sans-serif;">
            <thead>
                <tr style="background:#111;color:#fff;">
                    <th style="padding:10px 12px;text-align:left;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.6px;">No.</th>
                    <th style="padding:10px 12px;text-align:left;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.6px;">Book Number</th>
                    <th style="padding:10px 12px;text-align:left;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.6px;">Title</th>
                    <th style="padding:10px 12px;text-align:left;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.6px;">Copyright</th>
                    <th style="padding:10px 12px;text-align:left;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.6px;">Edition</th>
                    <th style="padding:10px 12px;text-align:left;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.6px;">Author</th>
                    <th style="padding:10px 12px;text-align:left;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.6px;">Course Code</th>
                    <th style="padding:10px 12px;text-align:left;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.6px;">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($books)): ?>
                <tr><td colspan="8" style="text-align:center;padding:20px;color:#aaa;">No records found.</td></tr>
                <?php else: foreach ($books as $i => $b): ?>
                <tr style="background:<?= $i%2===0?'#fff':'#f8f8f8' ?>;">
                    <td style="padding:8px 12px;border-bottom:1px solid #f0f0f0;color:#999;"><?= $i+1 ?></td>
                    <td style="padding:8px 12px;border-bottom:1px solid #f0f0f0;font-weight:600;font-family:monospace;font-size:12px;"><?= htmlspecialchars($b['book_number']) ?></td>
                    <td style="padding:8px 12px;border-bottom:1px solid #f0f0f0;"><?= htmlspecialchars($b['title']) ?></td>
                    <td style="padding:8px 12px;border-bottom:1px solid #f0f0f0;color:#777;"><?= htmlspecialchars($b['copyright_year']?:'—') ?></td>
                    <td style="padding:8px 12px;border-bottom:1px solid #f0f0f0;color:#777;"><?= htmlspecialchars($b['edition']?:'—') ?></td>
                    <td style="padding:8px 12px;border-bottom:1px solid #f0f0f0;"><?= htmlspecialchars($b['author']) ?></td>
                    <td style="padding:8px 12px;border-bottom:1px solid #f0f0f0;color:#1d4ed8;"><?= htmlspecialchars($b['course_code']?:'—') ?></td>
                    <td style="padding:8px 12px;border-bottom:1px solid #f0f0f0;">
                        <span style="font-size:11px;font-weight:600;padding:2px 9px;border-radius:10px;<?= $b['status']==='Available'?'background:#f0fdf4;color:#16a34a;':'background:#fff7ed;color:#c2410c;' ?>">
                            <?= $b['status'] ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="8" style="text-align:right;font-size:11.5px;color:#aaa;padding:12px;border-top:1px solid #ddd;">
                        Total: <?= count($books) ?> &nbsp;|&nbsp; Available: <?= $totalAvailable ?> &nbsp;|&nbsp; Borrowed: <?= $totalBorrowed ?>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<style>
@media print {
    .no-print, .sidebar, .topbar, .sidebar-toggle { display:none!important; }
    .main-content { margin-left:0!important; }
    .wrapper { display:block; }
    .page-content { padding:0!important; }
    .section-card { border:none!important; box-shadow:none!important; }
    body { background:#fff!important; font-family:Arial,sans-serif!important; }
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
