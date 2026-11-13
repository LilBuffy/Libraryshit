<?php

// main
define('DB_HOST',    'localhost');
define('DB_NAME',    'library_management');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_CHARSET', 'utf8mb4');

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        $dsn     = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die('<div style="font-family:sans-serif;padding:40px;background:#fff0f0;border-left:5px solid red;margin:20px;">
                <h2>Database Connection Failed</h2>
                <p>' . htmlspecialchars($e->getMessage()) . '</p>
                <p>Make sure XAMPP MySQL is running and <strong>library_management</strong> is imported.</p>
            </div>');
        }
    }
    return $pdo;
}

// Fine amount per overdue day (in PHP)
if (!defined('FINE_PER_DAY')) {
    define('FINE_PER_DAY', 5.00);
}
