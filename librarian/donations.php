<?php
require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../config/session.php';
require_once '../includes/functions.php';
require_once '../includes/csrf.php';
$required_role = 'librarian';
require_once '../includes/auth_check.php';
$active = 'donations';
$page_title = 'Donations';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) { set_flash('error', 'Invalid request.'); redirect('donations.php'); }
    $action = $_POST['action'] ?? '';
    $donation_id = (int)($_POST['donation_id'] ?? 0);

    $stmt = mysqli_prepare($conn, "SELECT * FROM donations WHERE donation_id = ? AND status = 'pending'");
    mysqli_stmt_bind_param($stmt, 'i', $donation_id);
    mysqli_stmt_execute($stmt);
    $donation = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if ($donation) {
        if ($action === 'approve') {
            mysqli_begin_transaction($conn);
            try {
                // Check if this ISBN already exists in the catalog — if so, just add a copy
                $existing_book_id = null;
                if (!empty($donation['isbn'])) {
                    $stmt = mysqli_prepare($conn, "SELECT book_id FROM books WHERE isbn = ?");
                    mysqli_stmt_bind_param($stmt, 's', $donation['isbn']);
                    mysqli_stmt_execute($stmt);
                    $existing = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
                    mysqli_stmt_close($stmt);
                    $existing_book_id = $existing['book_id'] ?? null;
                }

                if ($existing_book_id) {
                    $stmt = mysqli_prepare($conn, "UPDATE book_inventory SET total_quantity = total_quantity + 1, available_quantity = available_quantity + 1 WHERE book_id = ?");
                    mysqli_stmt_bind_param($stmt, 'i', $existing_book_id);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                } else {
                    $isbn_val = $donation['isbn'] ?: null;
                    $stmt = mysqli_prepare($conn, "INSERT INTO books (title, author, isbn, publisher, status, price) VALUES (?,?,?,?, 'active', 0)");
                    mysqli_stmt_bind_param($stmt, 'ssss', $donation['book_title'], $donation['author'], $isbn_val, $donation['publisher']);
                    mysqli_stmt_execute($stmt);
                    $new_book_id = mysqli_insert_id($conn);
                    mysqli_stmt_close($stmt);

                    $stmt = mysqli_prepare($conn, "INSERT INTO book_inventory (book_id, total_quantity, available_quantity) VALUES (?, 1, 1)");
                    mysqli_stmt_bind_param($stmt, 'i', $new_book_id);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                }

                $stmt = mysqli_prepare($conn, "UPDATE donations SET status='approved', approved_by=?, approval_date=NOW() WHERE donation_id=?");
                mysqli_stmt_bind_param($stmt, 'ii', $current_user_id, $donation_id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);

                mysqli_commit($conn);
                log_activity($conn, 'librarian', $current_user_id, 'approve_donation', "Approved donation: {$donation['book_title']}");
                set_flash('success', "Donation approved and added to the catalog. Thank you!");
            } catch (Exception $e) {
                mysqli_rollback($conn);
                set_flash('error', 'Failed to process donation.');
            }
        } elseif ($action === 'reject') {
            $stmt = mysqli_prepare($conn, "UPDATE donations SET status='rejected', approved_by=?, approval_date=NOW(), remarks='Does not meet library collection guidelines.' WHERE donation_id=?");
            mysqli_stmt_bind_param($stmt, 'ii', $current_user_id, $donation_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            log_activity($conn, 'librarian', $current_user_id, 'reject_donation', "Rejected donation: {$donation['book_title']}");
            set_flash('success', 'Donation rejected.');
        }
    }
    redirect('donations.php');
}

$pending = mysqli_query($conn, "SELECT d.*, m.name AS member_name FROM donations d JOIN member m ON m.member_id=d.member_id WHERE d.status='pending' ORDER BY d.donation_date ASC");
$history = mysqli_query($conn, "SELECT d.*, m.name AS member_name FROM donations d JOIN member m ON m.member_id=d.member_id WHERE d.status!='pending' ORDER BY d.approval_date DESC LIMIT 20");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Donations - BookWorld Librarian</title>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
<div class="bw-app">
<?php include '../includes/sidebar_librarian.php'; ?>
<main class="bw-main">
<?php include '../includes/dashboard_topbar.php'; ?>
<div class="bw-content">

  <div class="bw-panel mb-4">
    <div class="panel-title">Pending donations</div>
    <div class="table-responsive">
      <table class="table table-bw align-middle">
        <thead><tr><th>Book</th><th>Author</th><th>Donor</th><th>Date</th><th></th></tr></thead>
        <tbody>
          <?php if (mysqli_num_rows($pending) === 0): ?>
            <tr><td colspan="5" class="text-center text-muted py-3">No pending donations.</td></tr>
          <?php endif; ?>
          <?php while ($d = mysqli_fetch_assoc($pending)): ?>
            <tr>
              <td class="fw-semibold text-navy"><?= clean($d['book_title']) ?></td>
              <td><?= clean($d['author']) ?></td>
              <td><?= clean($d['member_name']) ?></td>
              <td class="text-muted small"><?= fmt_date($d['donation_date']) ?></td>
              <td class="text-end">
                <form method="POST" class="d-inline">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="approve">
                  <input type="hidden" name="donation_id" value="<?= $d['donation_id'] ?>">
                  <button type="button" class="btn btn-sm btn-bw-green bw-confirm-action" data-action-label="approve this donation" data-action-icon="question">Approve</button>
                </form>
                <form method="POST" class="d-inline">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="reject">
                  <input type="hidden" name="donation_id" value="<?= $d['donation_id'] ?>">
                  <button type="button" class="btn btn-sm btn-bw-danger bw-confirm-action" data-action-label="reject this donation" data-action-icon="warning">Reject</button>
                </form>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="bw-panel">
    <div class="panel-title">Recent history</div>
    <div class="table-responsive">
      <table class="table table-bw align-middle">
        <thead><tr><th>Book</th><th>Donor</th><th>Status</th><th>Date</th></tr></thead>
        <tbody>
          <?php while ($h = mysqli_fetch_assoc($history)): ?>
            <tr>
              <td><?= clean($h['book_title']) ?></td>
              <td><?= clean($h['member_name']) ?></td>
              <td><?= $h['status']==='approved' ? '<span class="badge-bw-success">Approved</span>' : '<span class="badge-bw-danger">Rejected</span>' ?></td>
              <td class="text-muted small"><?= fmt_date($h['approval_date']) ?></td>
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
