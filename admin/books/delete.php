<?php
require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';
require_once '../../includes/csrf.php';
$required_role = 'admin';
require_once '../../includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token($_POST['csrf_token'] ?? '')) {
    set_flash('error', 'Invalid request.');
    redirect('list.php');
}

$book_id = (int)($_POST['book_id'] ?? 0);

$stmt = mysqli_prepare($conn, "SELECT title FROM books WHERE book_id = ?");
mysqli_stmt_bind_param($stmt, 'i', $book_id);
mysqli_stmt_execute($stmt);
$book = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$book) {
    set_flash('error', 'Book not found.');
    redirect('list.php');
}

// Books with active issues can't be hard-deleted without breaking history,
// so "delete" here removes the title from the public catalog (soft delete).
// This keeps book_issues / fines / reservations history intact for reporting.
$stmt = mysqli_prepare($conn, "UPDATE books SET status = 'inactive' WHERE book_id = ?");
mysqli_stmt_bind_param($stmt, 'i', $book_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

log_activity($conn, 'admin', $current_user_id, 'delete_book', "Removed book from catalog: {$book['title']}");
set_flash('success', "\"{$book['title']}\" was removed from the catalog.");
redirect('list.php');
