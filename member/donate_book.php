<?php
require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../config/session.php';
require_once '../includes/functions.php';
require_once '../includes/csrf.php';
$required_role = 'member';
require_once '../includes/auth_check.php';
$active = 'donate';
$page_title = 'Donate a Book';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) { set_flash('error', 'Invalid request.'); redirect('donate_book.php'); }

    $title = trim($_POST['book_title'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $isbn = trim($_POST['isbn'] ?? '');
    $publisher = trim($_POST['publisher'] ?? '');

    if (strlen($title) < 2 || strlen($author) < 2) {
        set_flash('error', 'Please enter both the book title and author.');
        redirect('donate_book.php');
    }

    $stmt = mysqli_prepare($conn, "INSERT INTO donations (member_id, book_title, author, isbn, publisher, donation_date, status) VALUES (?,?,?,?,?, NOW(), 'pending')");
    mysqli_stmt_bind_param($stmt, 'issss', $current_user_id, $title, $author, $isbn, $publisher);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    log_activity($conn, 'member', $current_user_id, 'donate_book', "Donated: $title");
    push_notification($conn, 'librarian', "New donation submitted: \"$title\" — awaiting approval.", 'donation');
    set_flash('success', "Thank you! Your donation of \"$title\" has been submitted for review.");
    redirect('donate_book.php');
}

$my_donations = mysqli_query($conn, "SELECT * FROM donations WHERE member_id=$current_user_id ORDER BY donation_date DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Donate a Book - BookWorld</title>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
<div class="bw-app">
<?php include '../includes/sidebar_member.php'; ?>
<main class="bw-main">
<?php include '../includes/dashboard_topbar.php'; ?>
<div class="bw-content">
  <div class="row g-4">
    <div class="col-lg-5">
      <div class="bw-panel">
        <div class="panel-title">Give a book a second home</div>
        <p class="text-muted small">Submit the details below and a librarian will review your donation.</p>
        <form method="POST">
          <?= csrf_field() ?>
          <label class="bw-label">Book Title</label>
          <input type="text" class="form-control bw-form-control mb-3" name="book_title" required>
          <label class="bw-label">Author</label>
          <input type="text" class="form-control bw-form-control mb-3" name="author" required>
          <label class="bw-label">ISBN (optional)</label>
          <input type="text" class="form-control bw-form-control mb-3" name="isbn">
          <label class="bw-label">Publisher (optional)</label>
          <input type="text" class="form-control bw-form-control mb-3" name="publisher">
          <button type="submit" class="btn btn-bw-primary w-100">Submit Donation</button>
        </form>
      </div>
    </div>
    <div class="col-lg-7">
      <div class="bw-panel">
        <div class="panel-title">Your donation history</div>
        <table class="table table-bw">
          <tbody>
            <?php if (mysqli_num_rows($my_donations) === 0): ?>
              <tr><td class="text-center text-muted py-3">You haven't donated any books yet.</td></tr>
            <?php endif; ?>
            <?php while ($d = mysqli_fetch_assoc($my_donations)): ?>
              <tr>
                <td><?= clean($d['book_title']) ?><div class="small text-muted"><?= fmt_date($d['donation_date']) ?></div></td>
                <td class="text-end">
                  <?php if ($d['status']==='approved'): ?><span class="badge-bw-success">Approved</span>
                  <?php elseif ($d['status']==='rejected'): ?><span class="badge-bw-danger">Rejected</span>
                  <?php else: ?><span class="badge-bw-warning">Pending Review</span><?php endif; ?>
                </td>
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
<script>window.BW_FLASH = <?= ($f = get_flash()) ? json_encode($f) : 'null' ?>;</script>
<script src="../assets/js/main.js"></script>
</body>
</html>
