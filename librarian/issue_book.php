<?php
require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../config/session.php';
require_once '../includes/functions.php';
require_once '../includes/csrf.php';
$required_role = 'librarian';
require_once '../includes/auth_check.php';
$active = 'issue';
$page_title = 'Issue Book';
// get_member_plan() and issue_the_book() now live in includes/functions.php for reuse.

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) { set_flash('error', 'Invalid request.'); redirect('issue_book.php'); }
    $action = $_POST['action'] ?? '';

    if ($action === 'approve_issue') {
        $issue_id = (int)$_POST['issue_id'];
        $stmt = mysqli_prepare($conn, "SELECT * FROM book_issues WHERE issue_id = ?");
        mysqli_stmt_bind_param($stmt, 'i', $issue_id);
        mysqli_stmt_execute($stmt);
        $req = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        if ($req) {
            $error = '';
            if (issue_the_book($conn, $issue_id, $req['member_id'], $req['book_id'], $current_user_id, $error)) {
                log_activity($conn, 'librarian', $current_user_id, 'issue_book', "Issued book_id {$req['book_id']} to member_id {$req['member_id']}");
                set_flash('success', 'Book issued successfully.');
            } else {
                set_flash('error', $error);
            }
        }
        redirect('issue_book.php');
    }

    if ($action === 'reject_issue') {
        $issue_id = (int)$_POST['issue_id'];
        $stmt = mysqli_prepare($conn, "UPDATE book_issues SET status='rejected', librarian_id=?, remarks='Rejected by librarian' WHERE issue_id=?");
        mysqli_stmt_bind_param($stmt, 'ii', $current_user_id, $issue_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        set_flash('success', 'Request rejected.');
        redirect('issue_book.php');
    }

    if ($action === 'direct_issue') {
        $member_id = (int)$_POST['member_id'];
        $book_id = (int)$_POST['book_id'];
        $error = '';
        if (issue_the_book($conn, null, $member_id, $book_id, $current_user_id, $error)) {
            log_activity($conn, 'librarian', $current_user_id, 'issue_book', "Directly issued book_id $book_id to member_id $member_id");
            set_flash('success', 'Book issued successfully.');
        } else {
            set_flash('error', $error);
        }
        redirect('issue_book.php');
    }
}

$pending = mysqli_query($conn, "SELECT bi.*, b.title, m.name AS member_name, m.email AS member_email
                                 FROM book_issues bi JOIN books b ON b.book_id=bi.book_id JOIN member m ON m.member_id=bi.member_id
                                 WHERE bi.status='requested' ORDER BY bi.request_date ASC");
$members_list = mysqli_query($conn, "SELECT member_id, name, email FROM member WHERE status='active' ORDER BY name");
$books_list = mysqli_query($conn, "SELECT book_id, title FROM books WHERE status='active' ORDER BY title");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Issue Book - BookWorld Librarian</title>
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

  <div class="row g-3">
    <div class="col-lg-7">
      <div class="bw-panel">
        <div class="panel-title">Pending issue requests</div>
        <div class="table-responsive">
          <table class="table table-bw align-middle">
            <thead><tr><th>Member</th><th>Book</th><th>Requested</th><th></th></tr></thead>
            <tbody>
              <?php if (mysqli_num_rows($pending) === 0): ?>
                <tr><td colspan="4" class="text-center text-muted py-3">No pending requests.</td></tr>
              <?php endif; ?>
              <?php while ($r = mysqli_fetch_assoc($pending)): ?>
                <tr>
                  <td><?= clean($r['member_name']) ?><div class="small text-muted"><?= clean($r['member_email']) ?></div></td>
                  <td><?= clean($r['title']) ?></td>
                  <td class="text-muted small"><?= fmt_date($r['request_date']) ?></td>
                  <td class="text-end">
                    <form method="POST" class="d-inline">
                      <?= csrf_field() ?>
                      <input type="hidden" name="action" value="approve_issue">
                      <input type="hidden" name="issue_id" value="<?= $r['issue_id'] ?>">
                      <button type="button" class="btn btn-sm btn-bw-green bw-confirm-action" data-action-label="issue this book" data-action-icon="question">Issue</button>
                    </form>
                    <form method="POST" class="d-inline">
                      <?= csrf_field() ?>
                      <input type="hidden" name="action" value="reject_issue">
                      <input type="hidden" name="issue_id" value="<?= $r['issue_id'] ?>">
                      <button type="button" class="btn btn-sm btn-bw-danger bw-confirm-action" data-action-label="reject this request" data-action-icon="warning">Reject</button>
                    </form>
                  </td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="col-lg-5">
      <div class="bw-panel">
        <div class="panel-title">Issue directly (walk-in member)</div>
        <form method="POST">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="direct_issue">
          <label class="bw-label">Member</label>
          <select class="form-select bw-form-control mb-3" name="member_id" required>
            <option value="">Select member</option>
            <?php while ($m = mysqli_fetch_assoc($members_list)): ?>
              <option value="<?= $m['member_id'] ?>"><?= clean($m['name']) ?> (<?= clean($m['email']) ?>)</option>
            <?php endwhile; ?>
          </select>
          <label class="bw-label">Book</label>
          <select class="form-select bw-form-control mb-3" name="book_id" required>
            <option value="">Select book</option>
            <?php while ($b = mysqli_fetch_assoc($books_list)): ?>
              <option value="<?= $b['book_id'] ?>"><?= clean($b['title']) ?></option>
            <?php endwhile; ?>
          </select>
          <button type="submit" class="btn btn-bw-primary w-100">Issue Book</button>
        </form>
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
