<?php
// includes/auth.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Admin helpers ──────────────────────────────────────────
function isLoggedIn() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/login.php');
        exit();
    }
}

// ── User helpers ───────────────────────────────────────────
function isUserLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireUserLogin() {
    if (!isUserLoggedIn()) {
        header('Location: ' . BASE_URL . '/auth/login.php');
        exit();
    }
}

function isAnyLoggedIn() {
    return isLoggedIn() || isUserLoggedIn();
}

// ── Shared helpers ─────────────────────────────────────────
function logActivity($action, $details = '', $by = '') {
    try {
        $pdo   = getDB();
        $admin = $by ?: ($_SESSION['admin_username'] ?? $_SESSION['user_username'] ?? 'system');
        $stmt  = $pdo->prepare("INSERT INTO activity_log (action, details, performed_by) VALUES (?, ?, ?)");
        $stmt->execute([$action, $details, $admin]);
    } catch (Exception $e) { /* silent */ }
}

function flash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

// Auto-generate next member ID
function nextMemberId($pdo) {
    $last    = $pdo->query("SELECT member_id FROM users ORDER BY id DESC LIMIT 1")->fetchColumn();
    $nextNum = 1;
    if ($last && preg_match('/(\d+)$/', $last, $m)) {
        $nextNum = intval($m[1]) + 1;
    }
    return 'MEM-' . date('Y') . '-' . str_pad($nextNum, 3, '0', STR_PAD_LEFT);
}
