CREATE DATABASE IF NOT EXISTS library_management
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE library_management;

CREATE TABLE IF NOT EXISTS admins (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    username   VARCHAR(100) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    full_name  VARCHAR(150) NOT NULL,
    email      VARCHAR(150),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Admin account
-- Username: admin | Password: admin123
INSERT IGNORE INTO admins (username, password, full_name, email) VALUES
('admin', '$2b$12$lKNrEgI10vDIH8MXmLywduzDsTJ9W/0.4rLvxual4ZCQqxphMhWTu', 'System Administrator', 'admin@library.com');

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

-- Sample borrower account
-- Username: test | Password: test123
INSERT IGNORE INTO users (member_id, username, password, full_name, email, membership_type, status, joined_date) VALUES
('MEM-2025-001', 'test', '$2b$12$k/lFB4IyuWQiS1oJEV/68un96Y96AIeJmdFiCiz7yAPq2QR3yfA6C', 'Test Borrower', 'test@email.com', 'student', 'active', CURDATE());

-- booksz
CREATE TABLE IF NOT EXISTS books (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    book_number    VARCHAR(20)  NOT NULL UNIQUE COMMENT 'Accession No. e.g. SA0001',
    title          VARCHAR(500) NOT NULL,
    copyright_year VARCHAR(10)  DEFAULT NULL,
    edition        VARCHAR(50)  DEFAULT NULL,
    author         VARCHAR(255) NOT NULL,
    course_code    VARCHAR(50)  DEFAULT NULL,
    status         ENUM('Available','Borrowed') NOT NULL DEFAULT 'Available',
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- trans
CREATE TABLE IF NOT EXISTS transactions (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT          DEFAULT NULL COMMENT 'links to users table',
    member_id   INT          DEFAULT NULL COMMENT 'legacy: links to members table',
    book_id     INT          NOT NULL,
    book_number VARCHAR(20)  NOT NULL,
    borrow_date DATE         NOT NULL,
    due_date    DATE         NOT NULL,
    return_date DATE         DEFAULT NULL,
    status      ENUM('borrowed','returned','overdue') DEFAULT 'borrowed',
    fine_amount DECIMAL(10,2) DEFAULT 0.00,
    fine_paid   TINYINT(1)   DEFAULT 0,
    notes       TEXT,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- act
CREATE TABLE IF NOT EXISTS activity_log (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    action       VARCHAR(255) NOT NULL,
    details      TEXT,
    performed_by VARCHAR(100),
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO activity_log (action, details, performed_by) VALUES
('System Initialized', 'Library Management System set up with user roles', 'admin');
