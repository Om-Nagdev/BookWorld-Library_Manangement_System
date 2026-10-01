<?php
require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../config/session.php';
require_once '../includes/functions.php';
require_once '../includes/csrf.php';
$required_role = 'admin';
require_once '../includes/auth_check.php';
$active = 'reports';
$page_title = 'Reports';

$total_books = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM books"))['c'];
$issued_books = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM book_issues WHERE status='issued'"))['c'];
$returned_books = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM book_issues WHERE status='returned'"))['c'];
$overdue_books = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM book_issues WHERE status='issued' AND due_date < CURDATE()"))['c'];
$total_fines = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(amount),0) c FROM fines"))['c'];
$paid_fines = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(amount),0) c FROM fines WHERE status='paid'"))['c'];
$total_donations = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM donations"))['c'];
$total_purchases = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(total_amount),0) c FROM book_purchases WHERE status='completed'"))['c'];

$most_viewed = mysqli_query($conn, "SELECT title, view_count FROM books ORDER BY view_count DESC LIMIT 10");

// Monthly issues trend (last 6 months)
$months = []; $month_data = [];
for ($i = 5; $i >= 0; $i--) {
    $m = date('Y-m', strtotime("-$i months"));
    $months[] = date('M Y', strtotime("-$i months"));
    $r = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM book_issues WHERE DATE_FORMAT(request_date, '%Y-%m') = '$m'"));
    $month_data[] = (int)$r['c'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reports - BookWorld Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
<div class="bw-app">
<?php include '../includes/sidebar_admin.php'; ?>
<main class="bw-main">
<?php include '../includes/dashboard_topbar.php'; ?>
<div class="bw-content">

  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3"><div class="bw-stat-card"><div class="num"><?= $total_books ?></div><div class="label">Total Books</div></div></div>
    <div class="col-6 col-md-3"><div class="bw-stat-card accent-green"><div class="num"><?= $issued_books ?></div><div class="label">Currently Issued</div></div></div>
    <div class="col-6 col-md-3"><div class="bw-stat-card accent-brass"><div class="num"><?= $returned_books ?></div><div class="label">Total Returned</div></div></div>
    <div class="col-6 col-md-3"><div class="bw-stat-card accent-danger"><div class="num"><?= $overdue_books ?></div><div class="label">Overdue Now</div></div></div>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3"><div class="bw-stat-card"><div class="num"><?= inr($total_fines) ?></div><div class="label">Total Fines Levied</div></div></div>
    <div class="col-6 col-md-3"><div class="bw-stat-card accent-green"><div class="num"><?= inr($paid_fines) ?></div><div class="label">Fines Collected</div></div></div>
    <div class="col-6 col-md-3"><div class="bw-stat-card accent-brass"><div class="num"><?= $total_donations ?></div><div class="label">Total Donations</div></div></div>
    <div class="col-6 col-md-3"><div class="bw-stat-card"><div class="num"><?= inr($total_purchases) ?></div><div class="label">Book Sales Revenue</div></div></div>
  </div>

  <div class="row g-3">
    <div class="col-lg-7">
      <div class="bw-panel h-100">
        <div class="panel-title">Issue requests — last 6 months</div>
        <canvas id="monthlyChart" height="110"></canvas>
      </div>
    </div>
    <div class="col-lg-5">
      <div class="bw-panel h-100">
        <div class="panel-title">Most viewed books</div>
        <table class="table table-bw">
          <tbody>
            <?php while ($mv = mysqli_fetch_assoc($most_viewed)): ?>
              <tr><td><?= clean($mv['title']) ?></td><td class="text-end text-muted"><?= $mv['view_count'] ?></td></tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
</main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script src="../assets/js/dashboard-charts.js"></script>
<script>bwCreateBarChart(document.getElementById('monthlyChart'), <?= json_encode($months) ?>, <?= json_encode($month_data) ?>, 'Issues');</script>
</body>
</html>
