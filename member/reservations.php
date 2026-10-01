<?php
require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../config/session.php';
require_once '../includes/functions.php';
require_once '../includes/csrf.php';
$required_role = 'member';
require_once '../includes/auth_check.php';
$active = 'reservations';
$page_title = 'My Reservations';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'cancel') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) { set_flash('error', 'Invalid request.'); redirect('reservations.php'); }
    $reservation_id = (int)$_POST['reservation_id'];
    $stmt = mysqli_prepare($conn, "UPDATE reservations SET status='cancelled' WHERE reservation_id=? AND member_id=? AND status='pending'");
    mysqli_stmt_bind_param($stmt, 'ii', $reservation_id, $current_user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    set_flash('success', 'Reservation cancelled.');
    redirect('reservations.php');
}

$reservations = mysqli_query($conn, "SELECT r.*, b.title, b.author FROM reservations r JOIN books b ON b.book_id=r.book_id
                                      WHERE r.member_id=$current_user_id ORDER BY r.reservation_date DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Reservations - BookWorld</title>
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
  <div class="bw-panel">
    <div class="table-responsive">
      <table class="table table-bw align-middle">
        <thead><tr><th>Book</th><th>Reserved On</th><th>Status</th><th></th></tr></thead>
        <tbody>
          <?php if (mysqli_num_rows($reservations) === 0): ?>
            <tr><td colspan="4" class="text-center text-muted py-4">You have no reservations. <a href="browse_books.php">Browse the catalog</a></td></tr>
          <?php endif; ?>
          <?php while ($r = mysqli_fetch_assoc($reservations)): ?>
            <tr>
              <td class="fw-semibold text-navy"><?= clean($r['title']) ?><div class="small text-muted fw-normal"><?= clean($r['author']) ?></div></td>
              <td><?= fmt_date($r['reservation_date']) ?></td>
              <td>
                <?php if ($r['status']==='pending'): ?><span class="badge-bw-warning">Waiting</span>
                <?php elseif ($r['status']==='fulfilled'): ?><span class="badge-bw-success">Fulfilled — check My Issued Books</span>
                <?php else: ?><span class="badge-bw-navy">Cancelled</span><?php endif; ?>
              </td>
              <td class="text-end">
                <?php if ($r['status']==='pending'): ?>
                <form method="POST">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="cancel">
                  <input type="hidden" name="reservation_id" value="<?= $r['reservation_id'] ?>">
                  <button type="button" class="btn btn-sm btn-bw-danger bw-confirm-delete" data-item="this reservation">Cancel</button>
                </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
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
