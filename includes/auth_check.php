<?php
/**
 * Role-based access guard.
 * Usage: define $required_role ('admin' | 'librarian' | 'member') BEFORE including this file.
 */

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    redirect(rtrim(BASE_URL, '/') . '/auth/login.php?msg=login_required');
}

if (isset($required_role) && $_SESSION['role'] !== $required_role) {
    // Logged in, but wrong role trying to access this area
    redirect(rtrim(BASE_URL, '/') . '/auth/login.php?msg=unauthorized');
}

// Convenience variables available to every protected page
$current_user_id   = $_SESSION['user_id'];
$current_user_role = $_SESSION['role'];
$current_user_name = $_SESSION['name'] ?? '';
