<?php
/**
 * Scheduled task: run daily (via cron / Windows Task Scheduler / XAMPP cron helper)
 * to flag overdue issues and automatically forfeit books overdue by more than
 * OVERDUE_GRACE_DAYS (30 days) — charging the fine + book price and cancelling
 * the member's subscription, even if the member never submits a return request.
 *
 * Usage: php cron/calculate_fines.php   (CLI)
 *        or visit in browser for a manual run during a demo.
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';

$processed = 0;
$forfeited = 0;

// Mark any issued book past due_date as 'overdue' (visual/reporting flag; fine is applied on return)
mysqli_query($conn, "UPDATE book_issues SET status='overdue' WHERE status='issued' AND due_date < CURDATE()");

// Find issues overdue by more than the grace period that are still not returned — auto-forfeit them
$result = mysqli_query($conn, "SELECT issue_id FROM book_issues WHERE status IN ('issued','overdue') AND due_date < DATE_SUB(CURDATE(), INTERVAL " . OVERDUE_GRACE_DAYS . " DAY)");

while ($row = mysqli_fetch_assoc($result)) {
    $fine_info = calculate_overdue_fine($conn, $row['issue_id']);
    if ($fine_info && $fine_info['forfeited']) {
        // Check a forfeiture fine hasn't already been logged for this issue
        $exists = mysqli_fetch_assoc(mysqli_query($conn, "SELECT 1 FROM fines WHERE issue_id = {$row['issue_id']} AND fine_type = 'lost_book'"));
        if (!$exists) {
            $stmt = mysqli_prepare($conn, "INSERT INTO fines (member_id, issue_id, fine_type, overdue_days, amount, status, remarks) VALUES (?,?, 'lost_book', ?, ?, 'unpaid', 'Auto-forfeited: not returned within 30 days of due date.')");
            mysqli_stmt_bind_param($stmt, 'iiid', $fine_info['member_id'], $row['issue_id'], $fine_info['overdue_days'], $fine_info['amount']);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            $stmt = mysqli_prepare($conn, "UPDATE book_issues SET status='returned', return_date=CURDATE(), remarks='Auto-forfeited (lost)' WHERE issue_id=?");
            mysqli_stmt_bind_param($stmt, 'i', $row['issue_id']);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            $forfeited++;
        }
    }
    $processed++;
}

$msg = "Fine calculation run complete. Checked $processed overdue issue(s), auto-forfeited $forfeited.";
echo $msg . PHP_EOL;
log_activity($conn, 'admin', 0, 'cron_calculate_fines', $msg);
