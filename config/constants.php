<?php
/**
 * Global constants
 */

define('SITE_NAME', 'BookWorld');
define('BASE_URL', 'http://localhost/BookWorld/');

// Business rules
define('DEFAULT_LOAN_DAYS', 14);
define('FINE_PER_DAY', 50);          // ₹50/day overdue fine
define('OVERDUE_GRACE_DAYS', 30);    // 1 month => forfeiture trigger
define('LOW_STOCK_THRESHOLD', 10);   // notify when available_quantity <= 10

// Upload paths
define('BOOK_IMG_PATH', 'assets/uploads/books/');
define('PROFILE_IMG_PATH', 'assets/uploads/profiles/');
define('MAX_UPLOAD_SIZE', 2 * 1024 * 1024); // 2MB
define('ALLOWED_IMG_TYPES', ['jpg', 'jpeg', 'png', 'webp']);

// Error display - turn off in production
ini_set('display_errors', 0);
error_reporting(E_ALL);
