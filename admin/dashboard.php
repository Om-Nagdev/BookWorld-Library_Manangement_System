<?php
require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../config/session.php';
require_once '../includes/functions.php';
require_once '../includes/csrf.php';
$required_role = 'admin';
require_once '../includes/auth_check.php';
$active = 'dashboard';
$page_title = 'Dashboard';

$total_books = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM books"))['c'];
$total_issued = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM book_issues WHERE status='issued'"))['c'];
$total_members = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM member WHERE status='active'"))['c'];
$total_librarians = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM librarian WHERE status='active'"))['c'];
$pending_donations = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM donations WHERE status='pending'"))['c'];
$overdue_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM book_issues WHERE status='issued' AND due_date < CURDATE()"))['c'];
$total_fines_collected = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(amount),0) c FROM fines WHERE status='paid'"))['c'];
$low_stock_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM book_inventory WHERE available_quantity <= " . LOW_STOCK_THRESHOLD))['c'];

// Issues over last 7 days
$issue_trend_labels = [];
$issue_trend_data = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $issue_trend_labels[] = date('D', strtotime($d));
    $r = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM book_issues WHERE DATE(request_date) = '$d'"));
    $issue_trend_data[] = (int)$r['c'];
}

// Most viewed books
$most_viewed = mysqli_query($conn, "SELECT title, view_count FROM books ORDER BY view_count DESC LIMIT 5");

// Category distribution
$cat_dist = mysqli_query($conn, "SELECT c.category_name, COUNT(b.book_id) cnt FROM categories c LEFT JOIN books b ON b.category_id = c.category_id GROUP BY c.category_id ORDER BY cnt DESC LIMIT 6");
$cat_labels = []; $cat_data = [];
while ($r = mysqli_fetch_assoc($cat_dist)) { $cat_labels[] = $r['category_name']; $cat_data[] = (int)$r['cnt']; }

// Recent activity
$recent_activity = mysqli_query($conn, "SELECT * FROM activity_logs ORDER BY date_time DESC LIMIT 8");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard - BookWorld</title>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
<div id="bw-loader"><div class="bw-loader-book"><i class="fa-solid fa-book-open-reader"></i></div><div class="bw-loader-text">Loading dashboard...</div></div>

<div class="bw-app">
<?php include '../includes/sidebar_admin.php'; ?>
<main class="bw-main">
<?php include '../includes/dashboard_topbar.php'; ?>

<div class="bw-content">
  <?php if ($overdue_count > 0 || $low_stock_count > 0): ?>
  <div class="alert alert-warning d-flex align-items-center gap-2 mb-4">
    <i class="fa-solid fa-triangle-exclamation"></i>
    <div>
      <?php if ($overdue_count > 0): ?><strong><?= $overdue_count ?></strong> book(s) are overdue. <?php endif; ?>
      <?php if ($low_stock_count > 0): ?><strong><?= $low_stock_count ?></strong> title(s) are running low on stock. <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>

  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
      <div class="bw-stat-card"><i class="fa-solid fa-book icon"></i><div class="num"><?= $total_books ?></div><div class="label">Total Books</div></div>
    </div>
    <div class="col-6 col-md-3">
      <div class="bw-stat-card accent-green"><i class="fa-solid fa-book-open icon"></i><div class="num"><?= $total_issued ?></div><div class="label">Books Issued</div></div>
    </div>
    <div class="col-6 col-md-3">
      <div class="bw-stat-card accent-brass"><i class="fa-solid fa-users icon"></i><div class="num"><?= $total_members ?></div><div class="label">Active Members</div></div>
    </div>
    <div class="col-6 col-md-3">
      <div class="bw-stat-card accent-danger"><i class="fa-solid fa-clock icon"></i><div class="num"><?= $overdue_count ?></div><div class="label">Overdue Books</div></div>
    </div>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
      <div class="bw-stat-card"><i class="fa-solid fa-user-tie icon"></i><div class="num"><?= $total_librarians ?></div><div class="label">Librarians</div></div>
    </div>
    <div class="col-6 col-md-3">
      <div class="bw-stat-card accent-brass"><i class="fa-solid fa-hand-holding-heart icon"></i><div class="num"><?= $pending_donations ?></div><div class="label">Pending Donations</div></div>
    </div>
    <div class="col-6 col-md-3">
      <div class="bw-stat-card accent-green"><i class="fa-solid fa-indian-rupee-sign icon"></i><div class="num"><?= inr($total_fines_collected) ?></div><div class="label">Fines Collected</div></div>
    </div>
    <div class="col-6 col-md-3">
      <div class="bw-stat-card accent-danger"><i class="fa-solid fa-boxes-stacked icon"></i><div class="num"><?= $low_stock_count ?></div><div class="label">Low Stock Titles</div></div>
    </div>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-lg-7">
      <div class="bw-panel h-100">
        <div class="panel-title">Book requests — last 7 days</div>
        <canvas id="issueTrendChart" height="110"></canvas>
      </div>
    </div>
    <div class="col-lg-5">
      <div class="bw-panel h-100">
        <div class="panel-title">Books by category</div>
        <canvas id="categoryChart" height="110"></canvas>
      </div>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-lg-6">
      <div class="bw-panel h-100">
        <div class="panel-title">Most viewed books</div>
        <table class="table table-bw">
          <tbody>
            <?php while ($mv = mysqli_fetch_assoc($most_viewed)): ?>
              <tr><td><?= clean($mv['title']) ?></td><td class="text-end text-muted"><?= $mv['view_count'] ?> views</td></tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
    <div class="col-lg-6">
      <div class="bw-panel h-100">
        <div class="panel-title">Recent activity</div>
        <table class="table table-bw">
          <tbody>
            <?php while ($log = mysqli_fetch_assoc($recent_activity)): ?>
              <tr>
                <td><span class="badge-bw-navy text-capitalize"><?= clean($log['user_type']) ?></span></td>
                <td><?= clean($log['action']) ?></td>
                <td class="text-end text-muted small"><?= date('d M, h:i A', strtotime($log['date_time'])) ?></td>
              </tr>
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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>window.BW_FLASH = <?= ($f = get_flash()) ? json_encode($f) : 'null' ?>;</script>
<script src="../assets/js/main.js"></script>
<script src="../assets/js/dashboard-charts.js"></script>
<script>
  bwCreateLineChart(document.getElementById('issueTrendChart'), <?= json_encode($issue_trend_labels) ?>, <?= json_encode($issue_trend_data) ?>, 'Requests');
  bwCreateDoughnutChart(document.getElementById('categoryChart'), <?= json_encode($cat_labels) ?>, <?= json_encode($cat_data) ?>);
</script>
</body>
</html>
