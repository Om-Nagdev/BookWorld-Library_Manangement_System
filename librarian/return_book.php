<?php
require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../config/session.php';
require_once '../includes/functions.php';
require_once '../includes/csrf.php';
$required_role = 'librarian';
require_once '../includes/auth_check.php';
$active = 'return';
$page_title = 'Return Book';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) { set_flash('error', 'Invalid request.'); redirect('return_book.php'); }
    $action = $_POST['action'] ?? '';
    $issue_id = (int)($_POST['issue_id'] ?? 0);

    if ($action === 'approve_return') {
        // Came from a member's self-service return request
        $stmt = mysqli_prepare($conn, "SELECT issue_id FROM return_requests WHERE issue_id = ? AND status = 'pending'");
        mysqli_stmt_bind_param($stmt, 'i', $issue_id);
        mysqli_stmt_execute($stmt);
        $rr = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        $result = process_book_return($conn, $issue_id, $current_user_id);

        if ($rr) {
            $stmt = mysqli_prepare($conn, "UPDATE return_requests SET status='completed', approved_date=NOW(), return_date=CURDATE() WHERE issue_id=?");
            mysqli_stmt_bind_param($stmt, 'i', $issue_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }

        if ($result['success']) {
            log_activity($conn, 'librarian', $current_user_id, 'return_book', "Processed return for issue_id $issue_id");
            set_flash('success', $result['message']);
        } else {
            set_flash('error', $result['message']);
        }
        redirect('return_book.php');
    }

    if ($action === 'direct_return') {
        $result = process_book_return($conn, $issue_id, $current_user_id);
        if ($result['success']) {
            log_activity($conn, 'librarian', $current_user_id, 'return_book', "Processed direct return for issue_id $issue_id");
            set_flash('success', $result['message']);
        } else {
            set_flash('error', $result['message']);
        }
        redirect('return_book.php');
    }
}

$return_requests = mysqli_query($conn, "SELECT rr.*, bi.due_date, b.title, m.name AS member_name
                                         FROM return_requests rr
                                         JOIN book_issues bi ON bi.issue_id = rr.issue_id
                                         JOIN books b ON b.book_id = bi.book_id
                                         JOIN member m ON m.member_id = rr.member_id
                                         WHERE rr.status = 'pending' ORDER BY rr.request_date ASC");

$currently_issued = mysqli_query($conn, "SELECT bi.*, b.title, m.name AS member_name,
                                          DATEDIFF(CURDATE(), bi.due_date) AS days_overdue
                                          FROM book_issues bi JOIN books b ON b.book_id=bi.book_id JOIN member m ON m.member_id=bi.member_id
                                          WHERE bi.status='issued' ORDER BY bi.due_date ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Return Book - BookWorld Librarian</title>
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
    <div class="panel-title">Member return requests</div>
    <div class="table-responsive">
      <table class="table table-bw align-middle">
        <thead><tr><th>Member</th><th>Book</th><th>Due Date</th><th>Requested</th><th></th></tr></thead>
        <tbody>
          <?php if (mysqli_num_rows($return_requests) === 0): ?>
            <tr><td colspan="5" class="text-center text-muted py-3">No pending return requests.</td></tr>
          <?php endif; ?>
          <?php while ($rr = mysqli_fetch_assoc($return_requests)): ?>
            <tr>
              <td><?= clean($rr['member_name']) ?></td>
              <td><?= clean($rr['title']) ?></td>
              <td><?= fmt_date($rr['due_date']) ?></td>
              <td class="text-muted small"><?= fmt_date($rr['request_date']) ?></td>
              <td class="text-end">
                <form method="POST">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="approve_return">
                  <input type="hidden" name="issue_id" value="<?= $rr['issue_id'] ?>">
                  <button type="button" class="btn btn-sm btn-bw-green bw-confirm-action" data-action-label="confirm this return" data-action-icon="question">Confirm Return</button>
                </form>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="bw-panel">
    <div class="panel-title">All currently issued books</div>
    <div class="table-responsive">
      <table class="table table-bw align-middle">
        <thead><tr><th>Member</th><th>Book</th><th>Due Date</th><th>Status</th><th></th></tr></thead>
        <tbody>
          <?php if (mysqli_num_rows($currently_issued) === 0): ?>
            <tr><td colspan="5" class="text-center text-muted py-3">No books are currently issued.</td></tr>
          <?php endif; ?>
          <?php while ($ci = mysqli_fetch_assoc($currently_issued)): ?>
            <tr>
              <td><?= clean($ci['member_name']) ?></td>
              <td><?= clean($ci['title']) ?></td>
              <td><?= fmt_date($ci['due_date']) ?></td>
              <td>
                <?php if ($ci['days_overdue'] > 0): ?>
                  <span class="badge-bw-danger"><?= $ci['days_overdue'] ?> day(s) overdue</span>
                <?php else: ?>
                  <span class="badge-bw-success">On time</span>
                <?php endif; ?>
              </td>
              <td class="text-end">
                <form method="POST">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="direct_return">
                  <input type="hidden" name="issue_id" value="<?= $ci['issue_id'] ?>">
                  <button type="button" class="btn btn-sm btn-bw-outline bw-confirm-action" data-action-label="mark this book as returned" data-action-icon="question">Mark Returned</button>
                </form>
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
