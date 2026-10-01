<?php
require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../config/session.php';
require_once '../includes/functions.php';
require_once '../includes/csrf.php';
$required_role = 'member';
require_once '../includes/auth_check.php';
$active = 'feedback';
$page_title = 'Feedback';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) { set_flash('error', 'Invalid request.'); redirect('feedback.php'); }
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $rating = max(1, min(5, (int)($_POST['rating'] ?? 5)));

    if (strlen($subject) < 3 || strlen($message) < 10) {
        set_flash('error', 'Please enter a subject and a message of at least 10 characters.');
        redirect('feedback.php');
    }

    $stmt = mysqli_prepare($conn, "INSERT INTO feedback (member_id, subject, message, rating, submitted_at, status) VALUES (?,?,?,?, NOW(), 'new')");
    mysqli_stmt_bind_param($stmt, 'issi', $current_user_id, $subject, $message, $rating);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    log_activity($conn, 'member', $current_user_id, 'submit_feedback', "Submitted feedback: $subject");
    set_flash('success', 'Thank you for your feedback!');
    redirect('feedback.php');
}

$my_feedback = mysqli_query($conn, "SELECT * FROM feedback WHERE member_id=$current_user_id ORDER BY submitted_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Feedback - BookWorld</title>
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
        <div class="panel-title">Share your feedback</div>
        <form method="POST">
          <?= csrf_field() ?>
          <label class="bw-label">Subject</label>
          <input type="text" class="form-control bw-form-control mb-3" name="subject" required>
          <label class="bw-label">Rating</label>
          <select class="form-select bw-form-control mb-3" name="rating">
            <option value="5">5 - Excellent</option>
            <option value="4">4 - Good</option>
            <option value="3">3 - Average</option>
            <option value="2">2 - Poor</option>
            <option value="1">1 - Very Poor</option>
          </select>
          <label class="bw-label">Message</label>
          <textarea class="form-control bw-form-control mb-3" name="message" rows="4" required></textarea>
          <button type="submit" class="btn btn-bw-primary w-100">Submit Feedback</button>
        </form>
      </div>
    </div>
    <div class="col-lg-7">
      <div class="bw-panel">
        <div class="panel-title">Your feedback history</div>
        <table class="table table-bw">
          <tbody>
            <?php if (mysqli_num_rows($my_feedback) === 0): ?>
              <tr><td class="text-center text-muted py-3">No feedback submitted yet.</td></tr>
            <?php endif; ?>
            <?php while ($f = mysqli_fetch_assoc($my_feedback)): ?>
              <tr>
                <td>
                  <div class="fw-semibold text-navy"><?= clean($f['subject']) ?></div>
                  <div class="small text-muted"><?= clean($f['message']) ?></div>
                  <div class="small text-muted"><?= fmt_date($f['submitted_at']) ?> · <?= str_repeat('★', $f['rating']) ?><?= str_repeat('☆', 5 - $f['rating']) ?></div>
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
