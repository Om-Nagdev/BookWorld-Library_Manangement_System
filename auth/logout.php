<?php
require_once '../config/session.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

if (isset($_SESSION['role'], $_SESSION['user_id'])) {
    log_activity($conn, $_SESSION['role'], $_SESSION['user_id'], 'logout', 'User logged out');
}

$_SESSION = [];
session_unset();
session_destroy();

session_start();
set_flash('success', 'You have been logged out successfully.');
redirect('../auth/login.php');
