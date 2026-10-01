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

$id = (int)($_POST['member_id'] ?? 0);
$stmt = mysqli_prepare($conn, "SELECT name FROM member WHERE member_id = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$member = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$member) { set_flash('error', 'Member not found.'); redirect('list.php'); }

$active_issues = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM book_issues WHERE member_id = $id AND status = 'issued'"))['c'];
if ($active_issues > 0) {
    set_flash('error', "{$member['name']} still has $active_issues book(s) issued. They must be returned before the account can be removed.");
    redirect('list.php');
}

// Deactivating (not hard-deleting) preserves issue/fine/payment history for reports.
$stmt = mysqli_prepare($conn, "UPDATE member SET status = 'inactive' WHERE member_id = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

log_activity($conn, 'admin', $current_user_id, 'delete_member', "Removed member account: {$member['name']}");
set_flash('success', "{$member['name']}'s account was removed.");
redirect('list.php');
