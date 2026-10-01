<?php
require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../config/session.php';
require_once '../includes/functions.php';
require_once '../includes/csrf.php';
$required_role = 'member';
require_once '../includes/auth_check.php';
$active = 'dashboard';
$page_title = 'My Dashboard';

$active_issues = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM book_issues WHERE member_id=$current_user_id AND status='issued'"))['c'];
$active_reservations = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM reservations WHERE member_id=$current_user_id AND status='pending'"))['c'];
$unpaid_fines = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(amount),0) c FROM fines WHERE member_id=$current_user_id AND status='unpaid'"))['c'];
$books_read = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM book_issues WHERE member_id=$current_user_id AND status='returned'"))['c'];

$subscription = mysqli_fetch_assoc(mysqli_query($conn, "SELECT s.*, sp.plan_name, sp.max_books, sp.loan_days FROM subscriptions s
                                                         JOIN subscription_plans sp ON sp.plan_id = s.plan_id
                                                         WHERE s.member_id=$current_user_id AND s.status='active' AND s.end_date >= CURDATE()
                                                         ORDER BY s.subscription_id DESC LIMIT 1"));

$my_current_books = mysqli_query($conn, "SELECT bi.*, b.title, b.author FROM book_issues bi JOIN books b ON b.book_id=bi.book_id
                                          WHERE bi.member_id=$current_user_id AND bi.status='issued' ORDER BY bi.due_date ASC");

$new_arrivals = mysqli_query($conn, "SELECT title, author FROM books WHERE status='active' ORDER BY created_at DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Dashboard - BookWorld</title>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
<div id="bw-loader"><div class="bw-loader-book"><i class="fa-solid fa-book-open-reader"></i></div><div class="bw-loader-text">Loading your library...</div></div>
<div class="bw-app">
<?php include '../includes/sidebar_member.php'; ?>
<main class="bw-main">
<?php include '../includes/dashboard_topbar.php'; ?>
<div class="bw-content">

  <?php if (!$subscription): ?>
  <div class="alert alert-warning d-flex justify-content-between align-items-center">
    <span><i class="fa-solid fa-triangle-exclamation me-2"></i>You don't have an active subscription. Subscribe to start borrowing books.</span>
    <a href="subscription.php" class="btn btn-sm btn-bw-primary">View Plans</a>
  </div>
  <?php endif; ?>

  <?php if ($unpaid_fines > 0): ?>
  <div class="alert alert-danger d-flex justify-content-between align-items-center">
    <span><i class="fa-solid fa-circle-exclamation me-2"></i>You have <?= inr($unpaid_fines) ?> in unpaid fines.</span>
    <a href="my_issues.php" class="btn btn-sm btn-bw-danger">View Details</a>
  </div>
  <?php endif; ?>

  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
      <div class="bw-stat-card accent-green"><i class="fa-solid fa-book-open icon"></i><div class="num"><?= $active_issues ?></div><div class="label">Books with You</div></div>
    </div>
    <div class="col-6 col-md-3">
      <div class="bw-stat-card accent-brass"><i class="fa-solid fa-bookmark icon"></i><div class="num"><?= $active_reservations ?></div><div class="label">Active Reservations</div></div>
    </div>
    <div class="col-6 col-md-3">
      <div class="bw-stat-card"><i class="fa-solid fa-book icon"></i><div class="num"><?= $books_read ?></div><div class="label">Books Read</div></div>
    </div>
    <div class="col-6 col-md-3">
      <div class="bw-stat-card <?= $unpaid_fines > 0 ? 'accent-danger' : '' ?>"><i class="fa-solid fa-indian-rupee-sign icon"></i><div class="num"><?= inr($unpaid_fines) ?></div><div class="label">Unpaid Fines</div></div>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-lg-7">
      <div class="bw-panel h-100">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <div class="panel-title mb-0">Books currently with you</div>
          <a href="my_issues.php" class="small">View all</a>
        </div>
        <table class="table table-bw">
          <tbody>
            <?php if (mysqli_num_rows($my_current_books) === 0): ?>
              <tr><td class="text-center text-muted py-3">You have no books issued right now. <a href="browse_books.php">Browse the catalog</a></td></tr>
            <?php endif; ?>
            <?php while ($b = mysqli_fetch_assoc($my_current_books)):
              $overdue = strtotime($b['due_date']) < strtotime(date('Y-m-d')); ?>
              <tr>
                <td><?= clean($b['title']) ?><div class="small text-muted"><?= clean($b['author']) ?></div></td>
                <td class="text-end"><?= $overdue ? '<span class="badge-bw-danger">Overdue since '.fmt_date($b['due_date']).'</span>' : '<span class="badge-bw-navy">Due '.fmt_date($b['due_date']).'</span>' ?></td>
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
        <a href="browse_books.php" class="btn btn-bw-outline w-100 mt-2">Browse Full Catalog</a>
      </div>
    </div>
  </div>

  <?php if ($subscription): ?>
  <div class="bw-panel mt-4">
    <div class="panel-title">Your subscription</div>
    <div class="d-flex justify-content-between align-items-center flex-wrap">
      <div>
        <span class="badge-bw-warning fs-6"><?= clean($subscription['plan_name']) ?> Plan</span>
        <p class="text-muted small mt-2 mb-0">Borrow up to <?= $subscription['max_books'] ?> books at a time · <?= $subscription['loan_days'] ?>-day loan period · Valid until <?= fmt_date($subscription['end_date']) ?></p>
      </div>
      <a href="subscription.php" class="btn btn-bw-outline btn-sm">Manage Subscription</a>
    </div>
  </div>
  <?php endif; ?>
</div>
</main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>window.BW_FLASH = <?= ($f = get_flash()) ? json_encode($f) : 'null' ?>;</script>
<script src="../assets/js/main.js"></script>
</body>
</html>
