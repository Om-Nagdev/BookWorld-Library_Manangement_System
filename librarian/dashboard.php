<?php
require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../config/session.php';
require_once '../includes/functions.php';
require_once '../includes/csrf.php';
$required_role = 'librarian';
require_once '../includes/auth_check.php';
$active = 'dashboard';
$page_title = 'Librarian Dashboard';

$pending_requests = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM book_issues WHERE status='requested'"))['c'];
$pending_returns = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM return_requests WHERE status='pending'"))['c'];
$overdue_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM book_issues WHERE status='issued' AND due_date < CURDATE()"))['c'];
$pending_donations = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM donations WHERE status='pending'"))['c'];
$total_books = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM books WHERE status='active'"))['c'];
$low_stock_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM book_inventory WHERE available_quantity <= " . LOW_STOCK_THRESHOLD))['c'];

$new_arrivals = mysqli_query($conn, "SELECT title, author, created_at FROM books WHERE status='active' ORDER BY created_at DESC LIMIT 5");
$pending_issue_list = mysqli_query($conn, "SELECT bi.*, b.title, m.name AS member_name FROM book_issues bi JOIN books b ON b.book_id=bi.book_id JOIN member m ON m.member_id=bi.member_id WHERE bi.status='requested' ORDER BY bi.request_date ASC LIMIT 6");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Librarian Dashboard - BookWorld</title>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
<div id="bw-loader"><div class="bw-loader-book"><i class="fa-solid fa-book-open-reader"></i></div><div class="bw-loader-text">Loading dashboard...</div></div>
<div class="bw-app">
<?php include '../includes/sidebar_librarian.php'; ?>
<main class="bw-main">
<?php include '../includes/dashboard_topbar.php'; ?>
<div class="bw-content">

  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3"><div class="bw-stat-card accent-brass"><i class="fa-solid fa-inbox icon"></i><div class="num"><?= $pending_requests ?></div><div class="label">Pending Issue Requests</div></div></div>
    <div class="col-6 col-md-3"><div class="bw-stat-card accent-green"><i class="fa-solid fa-rotate-left icon"></i><div class="num"><?= $pending_returns ?></div><div class="label">Pending Return Requests</div></div></div>
    <div class="col-6 col-md-3"><div class="bw-stat-card accent-danger"><i class="fa-solid fa-clock icon"></i><div class="num"><?= $overdue_count ?></div><div class="label">Overdue Books</div></div></div>
    <div class="col-6 col-md-3"><div class="bw-stat-card"><i class="fa-solid fa-hand-holding-heart icon"></i><div class="num"><?= $pending_donations ?></div><div class="label">Pending Donations</div></div></div>
  </div>

  <?php if ($low_stock_count > 0): ?>
  <div class="alert alert-warning"><i class="fa-solid fa-triangle-exclamation me-2"></i><strong><?= $low_stock_count ?></strong> title(s) are low on stock. <a href="inventory.php">Review inventory</a>.</div>
  <?php endif; ?>

  <div class="row g-3">
    <div class="col-lg-7">
      <div class="bw-panel h-100">
        <div class="panel-title">Pending issue requests</div>
        <table class="table table-bw">
          <thead><tr><th>Member</th><th>Book</th><th>Requested</th><th></th></tr></thead>
          <tbody>
            <?php if (mysqli_num_rows($pending_issue_list) === 0): ?>
              <tr><td colspan="4" class="text-center text-muted py-3">No pending requests.</td></tr>
            <?php endif; ?>
            <?php while ($r = mysqli_fetch_assoc($pending_issue_list)): ?>
              <tr>
                <td><?= clean($r['member_name']) ?></td>
                <td><?= clean($r['title']) ?></td>
                <td class="text-muted small"><?= fmt_date($r['request_date']) ?></td>
                <td class="text-end"><a href="issue_book.php" class="btn btn-sm btn-bw-outline">Review</a></td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
    <div class="col-lg-5">
      <div class="bw-panel h-100">
        <div class="panel-title">New arrivals</div>
        <table class="table table-bw">
          <tbody>
            <?php while ($n = mysqli_fetch_assoc($new_arrivals)): ?>
              <tr><td><?= clean($n['title']) ?><div class="small text-muted"><?= clean($n['author']) ?></div></td></tr>
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
<script>window.BW_FLASH = <?= ($f = get_flash()) ? json_encode($f) : 'null' ?>;</script>
<script src="../assets/js/main.js"></script>
</body>
</html>
