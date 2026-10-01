<?php
require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../config/session.php';
require_once '../includes/functions.php';
require_once '../includes/csrf.php';
$required_role = 'admin';
require_once '../includes/auth_check.php';
$active = 'donations';
$page_title = 'Donations';

$donations = mysqli_query($conn, "SELECT d.*, m.name AS member_name, l.name AS approver_name
                                   FROM donations d
                                   JOIN member m ON m.member_id = d.member_id
                                   LEFT JOIN librarian l ON l.librarian_id = d.approved_by
                                   ORDER BY d.donation_date DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Donations - BookWorld Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
<div class="bw-app">
<?php include '../includes/sidebar_admin.php'; ?>
<main class="bw-main">
<?php include '../includes/dashboard_topbar.php'; ?>
<div class="bw-content">
  <div class="bw-panel">
    <div class="table-responsive">
      <table class="table table-bw align-middle">
        <thead><tr><th>Book Title</th><th>Author</th><th>Donor</th><th>Date</th><th>Status</th><th>Approved By</th></tr></thead>
        <tbody>
          <?php if (mysqli_num_rows($donations) === 0): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">No donations recorded yet.</td></tr>
          <?php endif; ?>
          <?php while ($d = mysqli_fetch_assoc($donations)): ?>
            <tr>
              <td class="fw-semibold text-navy"><?= clean($d['book_title']) ?></td>
              <td><?= clean($d['author']) ?></td>
              <td><?= clean($d['member_name']) ?></td>
              <td><?= fmt_date($d['donation_date']) ?></td>
              <td>
                <?php if ($d['status']==='approved'): ?><span class="badge-bw-success">Approved</span>
                <?php elseif ($d['status']==='rejected'): ?><span class="badge-bw-danger">Rejected</span>
                <?php else: ?><span class="badge-bw-warning">Pending</span><?php endif; ?>
              </td>
              <td class="text-muted small"><?= clean($d['approver_name'] ?? '-') ?></td>
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
