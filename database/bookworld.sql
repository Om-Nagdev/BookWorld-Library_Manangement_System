-- ============================================================
-- BookWorld Library Management System
-- Database: bookworld
-- ============================================================

DROP DATABASE IF EXISTS bookworld;
CREATE DATABASE bookworld CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE bookworld;

-- ============================================================
-- 1. ADMIN
-- ============================================================
CREATE TABLE admin (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(15),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active','inactive') DEFAULT 'active'
) ENGINE=InnoDB;

-- ============================================================
-- 2. LIBRARIAN
-- ============================================================
CREATE TABLE librarian (
    librarian_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(15),
    address VARCHAR(255),
    joining_date DATE DEFAULT (CURRENT_DATE),
    status ENUM('active','inactive') DEFAULT 'active',
    created_by INT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- 3. MEMBER
-- ============================================================
CREATE TABLE member (
    member_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(15),
    address VARCHAR(255),
    profile_photo VARCHAR(255) DEFAULT 'default.png',
    registration_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active','inactive','suspended') DEFAULT 'active'
) ENGINE=InnoDB;

-- ============================================================
-- 4. CATEGORIES  (supports search/filter by genre)
-- ============================================================
CREATE TABLE categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE,
    description VARCHAR(255)
) ENGINE=InnoDB;

-- ============================================================
-- 5. BOOKS
-- ============================================================
CREATE TABLE books (
    book_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    author VARCHAR(150) NOT NULL,
    isbn VARCHAR(20) UNIQUE,
    publisher VARCHAR(150),
    publication_year YEAR,
    language VARCHAR(50) DEFAULT 'English',
    category_id INT,
    price DECIMAL(10,2) DEFAULT 0.00,
    description TEXT,
    book_image VARCHAR(255) DEFAULT 'default-book.png',
    status ENUM('active','inactive') DEFAULT 'active',
    view_count INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- 6. BOOK INVENTORY
-- ============================================================
CREATE TABLE book_inventory (
    inventory_id INT AUTO_INCREMENT PRIMARY KEY,
    book_id INT NOT NULL UNIQUE,
    total_quantity INT NOT NULL DEFAULT 0,
    available_quantity INT NOT NULL DEFAULT 0,
    issued_quantity INT NOT NULL DEFAULT 0,
    damaged_quantity INT NOT NULL DEFAULT 0,
    lost_quantity INT NOT NULL DEFAULT 0,
    FOREIGN KEY (book_id) REFERENCES books(book_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 7. SUBSCRIPTION PLANS (defines what Gold/Silver/Bronze mean)
-- ============================================================
CREATE TABLE subscription_plans (
    plan_id INT AUTO_INCREMENT PRIMARY KEY,
    plan_name ENUM('Bronze','Silver','Gold') NOT NULL UNIQUE,
    max_books INT NOT NULL,
    loan_days INT NOT NULL DEFAULT 14,
    price DECIMAL(10,2) NOT NULL,
    duration_days INT NOT NULL DEFAULT 365,
    description VARCHAR(255)
) ENGINE=InnoDB;

-- ============================================================
-- 8. SUBSCRIPTIONS
-- ============================================================
CREATE TABLE subscriptions (
    subscription_id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    plan_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('active','expired','cancelled') DEFAULT 'active',
    FOREIGN KEY (member_id) REFERENCES member(member_id) ON DELETE CASCADE,
    FOREIGN KEY (plan_id) REFERENCES subscription_plans(plan_id)
) ENGINE=InnoDB;

-- ============================================================
-- 9. BOOK ISSUES
-- ============================================================
CREATE TABLE book_issues (
    issue_id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    book_id INT NOT NULL,
    librarian_id INT NULL,
    request_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    approval_date DATETIME NULL,
    issue_date DATE NULL,
    due_date DATE NULL,
    return_date DATE NULL,
    status ENUM('requested','approved','rejected','issued','returned','overdue') DEFAULT 'requested',
    remarks VARCHAR(255),
    FOREIGN KEY (member_id) REFERENCES member(member_id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(book_id) ON DELETE CASCADE,
    FOREIGN KEY (librarian_id) REFERENCES librarian(librarian_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- 10. RETURN REQUESTS
-- ============================================================
CREATE TABLE return_requests (
    return_request_id INT AUTO_INCREMENT PRIMARY KEY,
    issue_id INT NOT NULL,
    member_id INT NOT NULL,
    request_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    approved_date DATETIME NULL,
    return_date DATE NULL,
    status ENUM('pending','approved','rejected','completed') DEFAULT 'pending',
    remarks VARCHAR(255),
    FOREIGN KEY (issue_id) REFERENCES book_issues(issue_id) ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES member(member_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 11. RESERVATIONS
-- ============================================================
CREATE TABLE reservations (
    reservation_id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    book_id INT NOT NULL,
    reservation_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending','fulfilled','cancelled') DEFAULT 'pending',
    fulfilled_date DATETIME NULL,
    FOREIGN KEY (member_id) REFERENCES member(member_id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(book_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 12. FINES
-- ============================================================
CREATE TABLE fines (
    fine_id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    issue_id INT NULL,
    fine_type ENUM('overdue','lost_book','damage') DEFAULT 'overdue',
    overdue_days INT DEFAULT 0,
    amount DECIMAL(10,2) NOT NULL,
    fine_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('unpaid','paid','waived') DEFAULT 'unpaid',
    remarks VARCHAR(255),
    FOREIGN KEY (member_id) REFERENCES member(member_id) ON DELETE CASCADE,
    FOREIGN KEY (issue_id) REFERENCES book_issues(issue_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- 13. DONATIONS
-- ============================================================
CREATE TABLE donations (
    donation_id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    book_title VARCHAR(200) NOT NULL,
    author VARCHAR(150),
    isbn VARCHAR(20),
    publisher VARCHAR(150),
    donation_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    approved_by INT NULL,
    approval_date DATETIME NULL,
    remarks VARCHAR(255),
    FOREIGN KEY (member_id) REFERENCES member(member_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 14. BOOK PURCHASES (permanent buy)
-- ============================================================
CREATE TABLE book_purchases (
    purchase_id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    book_id INT NOT NULL,
    quantity INT DEFAULT 1,
    price DECIMAL(10,2) NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    purchase_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending','completed','cancelled') DEFAULT 'pending',
    FOREIGN KEY (member_id) REFERENCES member(member_id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(book_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 15. PAYMENTS  (covers purchases, fines, subscriptions)
-- ============================================================
CREATE TABLE payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    purchase_id INT NULL,
    fine_id INT NULL,
    subscription_id INT NULL,
    member_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('cash','debit_card','online') NOT NULL,
    transaction_id VARCHAR(100),
    payment_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('success','failed','pending') DEFAULT 'pending',
    payment_type ENUM('purchase','fine','subscription') NOT NULL,
    FOREIGN KEY (purchase_id) REFERENCES book_purchases(purchase_id) ON DELETE SET NULL,
    FOREIGN KEY (fine_id) REFERENCES fines(fine_id) ON DELETE SET NULL,
    FOREIGN KEY (subscription_id) REFERENCES subscriptions(subscription_id) ON DELETE SET NULL,
    FOREIGN KEY (member_id) REFERENCES member(member_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 16. FEEDBACK
-- ============================================================
CREATE TABLE feedback (
    feedback_id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    subject VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    rating TINYINT DEFAULT 5,
    submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('new','reviewed') DEFAULT 'new',
    FOREIGN KEY (member_id) REFERENCES member(member_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 17. ACTIVITY LOGS
-- ============================================================
CREATE TABLE activity_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_type ENUM('admin','librarian','member') NOT NULL,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    description VARCHAR(255),
    date_time DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- 18. NOTIFICATIONS (low stock, overdue, donation alerts)
-- ============================================================
CREATE TABLE notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    recipient_type ENUM('admin','librarian') NOT NULL,
    recipient_id INT NULL, -- NULL = broadcast to all of that role
    message VARCHAR(255) NOT NULL,
    type ENUM('low_stock','overdue','donation','system') DEFAULT 'system',
    is_read TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- 19. PASSWORD RESETS
-- ============================================================
CREATE TABLE password_resets (
    reset_id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(150) NOT NULL,
    user_type ENUM('admin','librarian','member') NOT NULL,
    token VARCHAR(100) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- INDEXES for performance
-- ============================================================
CREATE INDEX idx_books_title ON books(title);
CREATE INDEX idx_books_author ON books(author);
CREATE INDEX idx_issues_status ON book_issues(status);
CREATE INDEX idx_issues_member ON book_issues(member_id);
CREATE INDEX idx_reservations_status ON reservations(status);
CREATE INDEX idx_fines_status ON fines(status);

-- ============================================================
-- SAMPLE DATA
-- ============================================================

-- Admin (password = Admin@123, hashed with PHP password_hash bcrypt)
INSERT INTO admin (name, email, password, phone, status) VALUES
('Om Patel', 'admin@bookworld.com', '$2b$10$mYE/mIAiiwaWXYiJcpBAJ.DnCZ7pxEw1SYrF26Sju7Vbs7/fC/vwa', '9876543210', 'active');
-- NOTE: this hash corresponds to plaintext "Admin@123" - change after first login.

-- Librarians (password = Lib@123)
INSERT INTO librarian (name, email, password, phone, address, joining_date, status) VALUES
('Riya Shah', 'riya.librarian@bookworld.com', '$2b$10$a8BO3h8.R3HAEGENqrKa5uJ2VHMlxs4KNdIdvUwLoL1oUQoAVNqOS', '9876500001', 'Vadodara, Gujarat', '2023-01-10', 'active'),
('Karan Mehta', 'karan.librarian@bookworld.com', '$2b$10$a8BO3h8.R3HAEGENqrKa5uJ2VHMlxs4KNdIdvUwLoL1oUQoAVNqOS', '9876500002', 'Vadodara, Gujarat', '2023-03-15', 'active');

-- Members (password = Member@123)
INSERT INTO member (name, email, password, phone, address, registration_date, status) VALUES
('Aarav Shah', 'aarav@example.com', '$2b$10$OrrATlywbZ64tBbGD80ttOAHEPgHM73wMDUPBIuqqDYn2lRzol98q', '9876511111', 'Vadodara', NOW(), 'active'),
('Priya Nair', 'priya@example.com', '$2b$10$OrrATlywbZ64tBbGD80ttOAHEPgHM73wMDUPBIuqqDYn2lRzol98q', '9876522222', 'Ahmedabad', NOW(), 'active'),
('Rohan Desai', 'rohan@example.com', '$2b$10$OrrATlywbZ64tBbGD80ttOAHEPgHM73wMDUPBIuqqDYn2lRzol98q', '9876533333', 'Surat', NOW(), 'active');

-- Categories
INSERT INTO categories (category_name, description) VALUES
('Programming', 'Software development and coding'),
('Fiction', 'Novels and short stories'),
('Data Science', 'Statistics, ML and analytics'),
('Biography', 'Life stories of notable people'),
('Self-Help', 'Personal growth and productivity'),
('History', 'Historical events and analysis');

-- Books
INSERT INTO books (title, author, isbn, publisher, publication_year, language, category_id, price, description, status, view_count) VALUES
('Clean Code', 'Robert C. Martin', '9780132350884', 'Prentice Hall', 2008, 'English', 1, 650.00, 'A handbook of agile software craftsmanship.', 'active', 120),
('Python Crash Course', 'Eric Matthes', '9781593279288', 'No Starch Press', 2019, 'English', 1, 550.00, 'A hands-on, project-based introduction to Python.', 'active', 95),
('Hands-On Machine Learning', 'Aurelien Geron', '9781492032649', "O'Reilly", 2019, 'English', 3, 900.00, 'Practical ML with Scikit-Learn, Keras & TensorFlow.', 'active', 80),
('The Alchemist', 'Paulo Coelho', '9780062315007', 'HarperOne', 1988, 'English', 2, 350.00, 'A shepherd boy journeys to find his personal legend.', 'active', 210),
('Atomic Habits', 'James Clear', '9780735211292', 'Avery', 2018, 'English', 5, 450.00, 'Tiny changes, remarkable results.', 'active', 300),
('Steve Jobs', 'Walter Isaacson', '9781451648539', 'Simon & Schuster', 2011, 'English', 4, 500.00, 'The exclusive biography of Steve Jobs.', 'active', 150),
('Sapiens', 'Yuval Noah Harari', '9780062316097', 'Harper', 2015, 'English', 6, 480.00, 'A brief history of humankind.', 'active', 175),
('Introduction to Algorithms', 'Thomas H. Cormen', '9780262033848', 'MIT Press', 2009, 'English', 1, 1200.00, 'Comprehensive guide to algorithms.', 'active', 60),
('Data Science from Scratch', 'Joel Grus', '9781492041139', "O'Reilly", 2019, 'English', 3, 700.00, 'First principles with Python.', 'active', 55),
('Rich Dad Poor Dad', 'Robert Kiyosaki', '9781612680194', 'Plata Publishing', 1997, 'English', 5, 300.00, 'What the rich teach their kids about money.', 'active', 220);

-- Inventory
INSERT INTO book_inventory (book_id, total_quantity, available_quantity, issued_quantity, damaged_quantity, lost_quantity) VALUES
(1, 5, 3, 2, 0, 0),
(2, 6, 5, 1, 0, 0),
(3, 4, 4, 0, 0, 0),
(4, 8, 6, 2, 0, 0),
(5, 10, 8, 2, 0, 0),
(6, 5, 5, 0, 0, 0),
(7, 6, 4, 2, 0, 0),
(8, 3, 3, 0, 0, 0),
(9, 4, 4, 0, 0, 0),
(10, 7, 7, 0, 0, 0);

-- Subscription plans
INSERT INTO subscription_plans (plan_name, max_books, loan_days, price, duration_days, description) VALUES
('Bronze', 2, 14, 199.00, 365, 'Up to 2 books at a time, 14-day loan period'),
('Silver', 4, 14, 399.00, 365, 'Up to 4 books at a time, 14-day loan period'),
('Gold', 6, 21, 699.00, 365, 'Up to 6 books at a time, extended 21-day loan period');

-- Sample subscriptions
INSERT INTO subscriptions (member_id, plan_id, start_date, end_date, status) VALUES
(1, 3, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 365 DAY), 'active'),
(2, 2, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 365 DAY), 'active');

-- Sample issues
INSERT INTO book_issues (member_id, book_id, librarian_id, request_date, approval_date, issue_date, due_date, status) VALUES
(1, 1, 1, NOW(), NOW(), CURDATE(), DATE_ADD(CURDATE(), INTERVAL 14 DAY), 'issued'),
(1, 5, 1, NOW(), NOW(), CURDATE(), DATE_ADD(CURDATE(), INTERVAL 14 DAY), 'issued'),
(2, 4, 2, NOW(), NOW(), CURDATE(), DATE_ADD(CURDATE(), INTERVAL 14 DAY), 'issued');

-- Sample donation
INSERT INTO donations (member_id, book_title, author, isbn, publisher, status) VALUES
(3, 'Wings of Fire', 'A.P.J. Abdul Kalam', '9788173711466', 'Universities Press', 'pending');

-- Sample feedback
INSERT INTO feedback (member_id, subject, message, rating) VALUES
(1, 'Great collection', 'Loved the range of programming books available.', 5);
