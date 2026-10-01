<?php
require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';
require_once '../../includes/csrf.php';
$required_role = 'admin';
require_once '../../includes/auth_check.php';
$active = 'members';
$page_title = 'Manage Members';

$search = trim($_GET['q'] ?? '');
$sql = "SELECT m.*,
        (SELECT COUNT(*) FROM book_issues WHERE member_id = m.member_id AND status='issued') AS active_issues,
        (SELECT status FROM subscriptions WHERE member_id = m.member_id AND status='active' ORDER BY subscription_id DESC LIMIT 1) AS sub_status,
        (SELECT plan_id FROM subscriptions WHERE member_id = m.member_id AND status='active' ORDER BY subscription_id DESC LIMIT 1) AS plan_id
        FROM member m WHERE 1=1";
$params = []; $types = '';
if ($search !== '') {
    $sql .= " AND (m.name LIKE ? OR m.email LIKE ?)";
    $like = "%$search%"; $params = [$like, $like]; $types = 'ss';
}
$sql .= " ORDER BY m.registration_date DESC";
$stmt = mysqli_prepare($conn, $sql);
if ($params) mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$members = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Members - BookWorld Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="../../assets/css/style.css">
<link rel="stylesheet" href="../../assets/css/dashboard.css">
</head>
<body>
<div class="bw-app">
<?php include '../../includes/sidebar_admin.php'; ?>
<main class="bw-main">
<?php include '../../includes/dashboard_topbar.php'; ?>
<div class="bw-content">
  <form class="d-flex gap-2 mb-3" method="GET">
    <input type="text" name="q" class="form-control bw-form-control" style="width:300px;" placeholder="Search members..." value="<?= clean($search) ?>">
    <button class="btn btn-bw-outline" type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
  </form>
  <div class="bw-panel">
    <div class="table-responsive">
      <table class="table table-bw align-middle">
        <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Joined</th><th>Active Issues</th><th>Subscription</th><th>Status</th><th></th></tr></thead>
        <tbody>
          <?php if (mysqli_num_rows($members) === 0): ?>
            <tr><td colspan="8" class="text-center text-muted py-4">No members found.</td></tr>
          <?php endif; ?>
          <?php while ($m = mysqli_fetch_assoc($members)): ?>
            <tr>
              <td class="fw-semibold text-navy"><?= clean($m['name']) ?></td>
              <td><?= clean($m['email']) ?></td>
              <td><?= clean($m['phone']) ?></td>
              <td><?= fmt_date($m['registration_date']) ?></td>
              <td><?= (int)$m['active_issues'] ?></td>
              <td><?= $m['sub_status'] === 'active' ? '<span class="badge-bw-success">Active</span>' : '<span class="badge-bw-navy">None</span>' ?></td>
              <td><?= $m['status']==='active' ? '<span class="badge-bw-success">Active</span>' : '<span class="badge-bw-danger">'.ucfirst($m['status']).'</span>' ?></td>
              <td class="text-end">
                <?php if ($m['status'] !== 'inactive'): ?>
                <form action="delete.php" method="POST" class="d-inline">
                  <?= csrf_field() ?>
                  <input type="hidden" name="member_id" value="<?= $m['member_id'] ?>">
                  <button type="button" class="btn btn-sm btn-bw-danger bw-confirm-delete" data-item="<?= clean($m['name']) ?>"><i class="fa-solid fa-user-slash"></i></button>
                </form>
                <?php else: ?>
                  <span class="text-muted small">Removed</span>
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
<script src="../../assets/js/main.js"></script>
</body>
</html>
