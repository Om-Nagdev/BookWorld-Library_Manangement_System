<?php
/**
 * Scheduled task: run daily to mark subscriptions whose end_date has passed as 'expired'.
 * Usage: php cron/check_overdue_subscriptions.php
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';

mysqli_query($conn, "UPDATE subscriptions SET status='expired' WHERE status='active' AND end_date < CURDATE()");
$affected = mysqli_affected_rows($conn);

$msg = "Subscription expiry check complete. $affected subscription(s) marked expired.";
echo $msg . PHP_EOL;
log_activity($conn, 'admin', 0, 'cron_check_subscriptions', $msg);
