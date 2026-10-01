<?php
require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../config/session.php';
require_once '../includes/functions.php';
require_once '../includes/csrf.php';
$required_role = 'member';
require_once '../includes/auth_check.php';
$active = 'issues';
$page_title = 'My Issued Books';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) { set_flash('error', 'Invalid request.'); redirect('my_issues.php'); }
    $action = $_POST['action'] ?? '';

    if ($action === 'request_return') {
        $issue_id = (int)$_POST['issue_id'];
        // Verify ownership
        $stmt = mysqli_prepare($conn, "SELECT * FROM book_issues WHERE issue_id=? AND member_id=? AND status='issued'");
        mysqli_stmt_bind_param($stmt, 'ii', $issue_id, $current_user_id);
        mysqli_stmt_execute($stmt);
        $issue = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        if ($issue) {
            $existing = mysqli_fetch_assoc(mysqli_query($conn, "SELECT 1 FROM return_requests WHERE issue_id=$issue_id AND status='pending'"));
            if (!$existing) {
                $stmt = mysqli_prepare($conn, "INSERT INTO return_requests (issue_id, member_id, request_date, status) VALUES (?, ?, NOW(), 'pending')");
                mysqli_stmt_bind_param($stmt, 'ii', $issue_id, $current_user_id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                log_activity($conn, 'member', $current_user_id, 'request_return', "Requested return for issue_id $issue_id");
                set_flash('success', 'Return request submitted. Please drop the book at the library counter.');
            } else {
                set_flash('error', 'A return request for this book is already pending.');
            }
        }
        redirect('my_issues.php');
    }

    if ($action === 'pay_fine') {
        $fine_id = (int)$_POST['fine_id'];
        $method = in_array($_POST['payment_method'] ?? '', ['cash','debit_card','online']) ? $_POST['payment_method'] : 'cash';

        $stmt = mysqli_prepare($conn, "SELECT * FROM fines WHERE fine_id=? AND member_id=? AND status='unpaid'");
        mysqli_stmt_bind_param($stmt, 'ii', $fine_id, $current_user_id);
        mysqli_stmt_execute($stmt);
        $fine = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        if ($fine) {
            $txn_id = generate_transaction_id();
            $stmt = mysqli_prepare($conn, "INSERT INTO payments (fine_id, member_id, amount, payment_method, transaction_id, payment_date, status, payment_type) VALUES (?,?,?,?,?, NOW(), 'success', 'fine')");
            mysqli_stmt_bind_param($stmt, 'iidss', $fine_id, $current_user_id, $fine['amount'], $method, $txn_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            $stmt = mysqli_prepare($conn, "UPDATE fines SET status='paid' WHERE fine_id=?");
            mysqli_stmt_bind_param($stmt, 'i', $fine_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            log_activity($conn, 'member', $current_user_id, 'pay_fine', "Paid fine #$fine_id via $method");
            set_flash('success', "Payment of " . inr($fine['amount']) . " successful. Transaction ID: $txn_id");
        }
        redirect('my_issues.php');
    }
}

$current_books = mysqli_query($conn, "SELECT bi.*, b.title, b.author,
                                       (SELECT status FROM return_requests WHERE issue_id=bi.issue_id ORDER BY return_request_id DESC LIMIT 1) AS return_req_status
                                       FROM book_issues bi JOIN books b ON b.book_id=bi.book_id
                                       WHERE bi.member_id=$current_user_id AND bi.status='issued' ORDER BY bi.due_date ASC");

$requested = mysqli_query($conn, "SELECT bi.*, b.title FROM book_issues bi JOIN books b ON b.book_id=bi.book_id
                                   WHERE bi.member_id=$current_user_id AND bi.status='requested' ORDER BY bi.request_date DESC");

$history = mysqli_query($conn, "SELECT bi.*, b.title FROM book_issues bi JOIN books b ON b.book_id=bi.book_id
                                 WHERE bi.member_id=$current_user_id AND bi.status IN ('returned','rejected') ORDER BY bi.issue_id DESC LIMIT 15");

$unpaid_fines = mysqli_query($conn, "SELECT f.*, b.title FROM fines f
                                      LEFT JOIN book_issues bi ON bi.issue_id=f.issue_id LEFT JOIN books b ON b.book_id=bi.book_id
                                      WHERE f.member_id=$current_user_id AND f.status='unpaid' ORDER BY f.fine_date DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Issued Books - BookWorld</title>
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

  <?php if (mysqli_num_rows($unpaid_fines) > 0): ?>
  <div class="bw-panel mb-4" style="border-left:4px solid var(--bw-danger);">
    <div class="panel-title text-danger">Unpaid fines</div>
    <div class="table-responsive">
      <table class="table table-bw align-middle">
        <thead><tr><th>Book</th><th>Type</th><th>Overdue Days</th><th>Amount</th><th></th></tr></thead>
        <tbody>
          <?php while ($f = mysqli_fetch_assoc($unpaid_fines)): ?>
            <tr>
              <td><?= clean($f['title'] ?? 'N/A') ?></td>
              <td class="text-capitalize"><?= clean(str_replace('_',' ',$f['fine_type'])) ?></td>
              <td><?= $f['overdue_days'] ?></td>
              <td class="fw-bold"><?= inr($f['amount']) ?></td>
              <td class="text-end">
                <button class="btn btn-sm btn-bw-primary" data-bs-toggle="modal" data-bs-target="#payFine<?= $f['fine_id'] ?>">Pay Now</button>
              </td>
            </tr>
            <div class="modal fade" id="payFine<?= $f['fine_id'] ?>" tabindex="-1">
              <div class="modal-dialog modal-sm">
                <div class="modal-content">
                  <form method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="pay_fine">
                    <input type="hidden" name="fine_id" value="<?= $f['fine_id'] ?>">
                    <div class="modal-header"><h6 class="modal-title">Pay fine of <?= inr($f['amount']) ?></h6></div>
                    <div class="modal-body">
                      <label class="bw-label">Payment Method</label>
                      <select class="form-select bw-form-control" name="payment_method">
                        <option value="cash">Cash (at counter)</option>
                        <option value="debit_card">Debit Card</option>
                        <option value="online">Online / UPI</option>
                      </select>
                      <small class="text-muted d-block mt-2">This is a simulated payment for demo purposes.</small>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-sm btn-bw-outline" data-bs-dismiss="modal">Cancel</button>
                      <button type="submit" class="btn btn-sm btn-bw-primary">Pay</button>
                    </div>
                  </form>
                </div>
              </div>
            </div>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>

  <div class="bw-panel mb-4">
    <div class="panel-title">Books currently with you</div>
    <div class="table-responsive">
      <table class="table table-bw align-middle">
        <thead><tr><th>Book</th><th>Issue Date</th><th>Due Date</th><th>Status</th><th></th></tr></thead>
        <tbody>
          <?php if (mysqli_num_rows($current_books) === 0): ?>
            <tr><td colspan="5" class="text-center text-muted py-3">No books currently issued. <a href="browse_books.php">Browse the catalog</a></td></tr>
          <?php endif; ?>
          <?php while ($b = mysqli_fetch_assoc($current_books)):
            $overdue = strtotime($b['due_date']) < strtotime(date('Y-m-d')); ?>
            <tr>
              <td class="fw-semibold text-navy"><?= clean($b['title']) ?><div class="small text-muted fw-normal"><?= clean($b['author']) ?></div></td>
              <td><?= fmt_date($b['issue_date']) ?></td>
              <td><?= fmt_date($b['due_date']) ?></td>
              <td><?= $overdue ? '<span class="badge-bw-danger">Overdue</span>' : '<span class="badge-bw-success">On time</span>' ?></td>
              <td class="text-end">
                <?php if ($b['return_req_status'] === 'pending'): ?>
                  <span class="badge-bw-warning">Return Pending</span>
                <?php else: ?>
                  <form method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="request_return">
                    <input type="hidden" name="issue_id" value="<?= $b['issue_id'] ?>">
                    <button type="submit" class="btn btn-sm btn-bw-outline">Request Return</button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="bw-panel mb-4">
    <div class="panel-title">Pending issue requests</div>
    <table class="table table-bw">
      <tbody>
        <?php if (mysqli_num_rows($requested) === 0): ?>
          <tr><td class="text-center text-muted py-3">No pending requests.</td></tr>
        <?php endif; ?>
        <?php while ($r = mysqli_fetch_assoc($requested)): ?>
          <tr><td><?= clean($r['title']) ?></td><td class="text-end"><span class="badge-bw-warning">Awaiting librarian approval</span></td></tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>

  <div class="bw-panel">
    <div class="panel-title">History</div>
    <table class="table table-bw">
      <tbody>
        <?php if (mysqli_num_rows($history) === 0): ?>
          <tr><td class="text-center text-muted py-3">No history yet.</td></tr>
        <?php endif; ?>
        <?php while ($h = mysqli_fetch_assoc($history)): ?>
          <tr>
            <td><?= clean($h['title']) ?></td>
            <td class="text-end"><?= $h['status']==='returned' ? '<span class="badge-bw-success">Returned '.fmt_date($h['return_date']).'</span>' : '<span class="badge-bw-danger">Rejected</span>' ?></td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
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
