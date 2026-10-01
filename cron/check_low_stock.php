<?php
/**
 * Scheduled task: run daily to scan inventory for low stock and push
 * notifications to admin & librarian dashboards. Also flags "highly demanded"
 * titles (high issue volume vs available copies) as a simple, transparent
 * demand-prediction signal — highest issues-per-copy ratio in the last 30 days.
 *
 * Usage: php cron/check_low_stock.php
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';

$low_stock = mysqli_query($conn, "SELECT bi.book_id, b.title, bi.available_quantity FROM book_inventory bi JOIN books b ON b.book_id=bi.book_id WHERE bi.available_quantity <= " . LOW_STOCK_THRESHOLD);

$notified = 0;
while ($row = mysqli_fetch_assoc($low_stock)) {
    // Avoid spamming duplicate notifications within the same day
    $exists = mysqli_fetch_assoc(mysqli_query($conn, "SELECT 1 FROM notifications WHERE type='low_stock' AND message LIKE '%{$row['title']}%' AND DATE(created_at) = CURDATE()"));
    if (!$exists) {
        $msg = "Low stock alert: \"{$row['title']}\" has only {$row['available_quantity']} copies left.";
        push_notification($conn, 'admin', $msg, 'low_stock');
        push_notification($conn, 'librarian', $msg, 'low_stock');
        $notified++;
    }
}

// Simple demand prediction: books with the highest issues-per-available-copy ratio
// in the last 30 days are "highly demanded" — surfaced for admin's restocking decisions.
$demand = mysqli_query($conn, "
    SELECT b.title, COUNT(bi2.issue_id) AS recent_issues, GREATEST(inv.available_quantity,1) AS avail,
           COUNT(bi2.issue_id) / GREATEST(inv.available_quantity,1) AS demand_score
    FROM books b
    JOIN book_inventory inv ON inv.book_id = b.book_id
    LEFT JOIN book_issues bi2 ON bi2.book_id = b.book_id AND bi2.request_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY b.book_id
    HAVING recent_issues > 0
    ORDER BY demand_score DESC
    LIMIT 5
");

$demand_titles = [];
while ($d = mysqli_fetch_assoc($demand)) {
    $demand_titles[] = "{$d['title']} ({$d['recent_issues']} requests / {$d['avail']} copies)";
}
if (!empty($demand_titles)) {
    push_notification($conn, 'admin', 'High demand this month: ' . implode(', ', $demand_titles), 'system');
}

$msg = "Low stock scan complete. $notified new alert(s) raised.";
echo $msg . PHP_EOL;
log_activity($conn, 'admin', 0, 'cron_check_low_stock', $msg);
