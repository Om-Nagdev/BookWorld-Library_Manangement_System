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

$id = (int)($_POST['librarian_id'] ?? 0);

$stmt = mysqli_prepare($conn, "SELECT name FROM librarian WHERE librarian_id = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$lib = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$lib) { set_flash('error', 'Librarian not found.'); redirect('list.php'); }

// Historical book_issues reference librarian_id with ON DELETE SET NULL, so this is safe.
$stmt = mysqli_prepare($conn, "DELETE FROM librarian WHERE librarian_id = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

log_activity($conn, 'admin', $current_user_id, 'delete_librarian', "Removed librarian: {$lib['name']}");
set_flash('success', "{$lib['name']} was removed.");
redirect('list.php');
