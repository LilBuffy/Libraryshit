-- UPGRADE SCRIPT PUTANG INA PAGOD NA KO - alter_table.sql
-- Run ONLY if upgrading from the previous version.
-- For fresh installs, use database.sql instead.
USE library_management;

-- 1. Create users table if not exists
CREATE TABLE IF NOT EXISTS users (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    member_id       VARCHAR(30)  NOT NULL UNIQUE,
    username        VARCHAR(100) NOT NULL UNIQUE,
    password        VARCHAR(255) NOT NULL,
    full_name       VARCHAR(150) NOT NULL,
    email           VARCHAR(150),
    phone           VARCHAR(30),
    address         TEXT,
    membership_type ENUM('student','faculty','staff','public') DEFAULT 'student',
    status          ENUM('active','inactive','suspended') DEFAULT 'active',
    joined_date     DATE NOT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. Add user_id to transactions if not exists
ALTER TABLE transactions
    ADD COLUMN IF NOT EXISTS user_id INT DEFAULT NULL COMMENT 'links to users table' AFTER id;

-- 3. Widen title column for long book titles
ALTER TABLE books MODIFY COLUMN title VARCHAR(500) NOT NULL;

-- 4. Add book_number to transactions if not exists
ALTER TABLE transactions
    ADD COLUMN IF NOT EXISTS book_number VARCHAR(20) DEFAULT NULL AFTER book_id;

-- Back-fill book_number from books table
UPDATE transactions t JOIN books b ON t.book_id = b.id
SET t.book_number = b.book_number WHERE t.book_number IS NULL;

-- 5. Fix books status column to correct ENUM
ALTER TABLE books
    MODIFY COLUMN status ENUM('Available','Borrowed') NOT NULL DEFAULT 'Available';

-- 6. Ensure admin account exists with password admin123
INSERT IGNORE INTO admins (username, password, full_name, email) VALUES
('admin', '$2b$12$lKNrEgI10vDIH8MXmLywduzDsTJ9W/0.4rLvxual4ZCQqxphMhWTu', 'System Administrator', 'admin@library.com');
UPDATE admins SET password='$2b$12$lKNrEgI10vDIH8MXmLywduzDsTJ9W/0.4rLvxual4ZCQqxphMhWTu' WHERE username='admin';

-- 7. Ensure test borrower account exists with password test123
INSERT IGNORE INTO users (member_id, username, password, full_name, email, membership_type, status, joined_date) VALUES
('MEM-2025-001', 'test', '$2b$12$k/lFB4IyuWQiS1oJEV/68un96Y96AIeJmdFiCiz7yAPq2QR3yfA6C', 'Test Borrower', 'test@email.com', 'student', 'active', CURDATE());

-- Done. Now import csv_books_import.sql to load all 2,000 books.
-- Default Accounts:
--   Admin    -> username: admin | password: admin123
--   Borrower -> username: test  | password: test123
